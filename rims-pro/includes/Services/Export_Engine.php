<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;

/**
 * PDF + Excel export. Uses Dompdf / PhpSpreadsheet at runtime when present;
 * otherwise emits a built-in HTML/CSV-fallback ("no records" notice still
 * generated when the result set is empty).
 *
 * Property 29: exported records == current filtered set.
 * Property 9: owner fields redacted for non-capability audiences (via serializer).
 */
final class Export_Engine {

    public function __construct(
        private Field_Visibility_Serializer $serializer,
    ) {
    }

    /**
     * @param Inventory_Unit[] $units
     * @return array{format:string, body:string, empty:bool}
     */
    public function buildPdf( array $units, bool $include_internal, array $branding = [] ): array {
        $rows = $this->serializer->serializeMany( $units, $include_internal );
        if ( empty( $rows ) ) {
            return [ 'format' => 'pdf', 'body' => 'No records to export.', 'empty' => true ];
        }
        // Try Dompdf if loaded.
        if ( class_exists( '\Dompdf\Dompdf' ) ) {
            $html = $this->htmlForPdf( $rows, $branding );
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml( $html );
            $dompdf->setPaper( 'A4', 'landscape' );
            $dompdf->render();
            return [ 'format' => 'pdf', 'body' => (string) $dompdf->output(), 'empty' => false ];
        }
        return [ 'format' => 'html', 'body' => $this->htmlForPdf( $rows, $branding ), 'empty' => false ];
    }

    /**
     * @param array{units: Inventory_Unit[], projects?: array<int,array>, leads?: array<int,array>} $data
     */
    public function buildExcel( array $data, bool $include_internal ): array {
        $units = $data['units'] ?? [];
        $rows  = $this->serializer->serializeMany( $units, $include_internal );
        if ( empty( $rows ) ) {
            return [ 'format' => 'xlsx', 'body' => 'No records to export.', 'empty' => true ];
        }
        if ( class_exists( '\PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
            $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $ws    = $sheet->getActiveSheet();
            $ws->setTitle( 'Inventory' );
            $headers = array_keys( $rows[0] );
            foreach ( $headers as $i => $h ) {
                $ws->setCellValueByColumnAndRow( $i + 1, 1, (string) $h );
            }
            foreach ( $rows as $r => $row ) {
                $c = 1;
                foreach ( $headers as $h ) {
                    $val = $row[ $h ] ?? '';
                    if ( is_array( $val ) ) {
                        $val = wp_json_encode( $val );
                    }
                    $ws->setCellValueByColumnAndRow( $c, $r + 2, (string) $val );
                    $c++;
                }
            }
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter( $sheet, 'Xlsx' );
            ob_start();
            $writer->save( 'php://output' );
            $body = (string) ob_get_clean();
            return [ 'format' => 'xlsx', 'body' => $body, 'empty' => false ];
        }
        // CSV fallback.
        $body    = '';
        $headers = array_keys( $rows[0] );
        $body   .= implode( ',', array_map( fn( $h ) => '"' . str_replace( '"', '""', (string) $h ) . '"', $headers ) ) . "\n";
        foreach ( $rows as $row ) {
            $line = [];
            foreach ( $headers as $h ) {
                $v      = $row[ $h ] ?? '';
                $v      = is_array( $v ) ? wp_json_encode( $v ) : (string) $v;
                $line[] = '"' . str_replace( '"', '""', (string) $v ) . '"';
            }
            $body .= implode( ',', $line ) . "\n";
        }
        return [ 'format' => 'csv', 'body' => $body, 'empty' => false ];
    }

    private function htmlForPdf( array $rows, array $branding ): string {
        $logo  = isset( $branding['logo_url'] ) ? sprintf( '<img src="%s" alt="logo" style="height:48px"/>', htmlspecialchars( (string) $branding['logo_url'] ) ) : '';
        $title = htmlspecialchars( (string) ( $branding['company_name'] ?? 'RIMS Pro Inventory' ) );
        $html  = "<html><head><meta charset='utf-8'><style>body{font-family:Arial,sans-serif;font-size:11px;color:#1f2937}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:4px 6px;text-align:left}thead{background:#1e3a8a;color:#fff}h1{color:#1e3a8a}</style></head><body>";
        $html .= "<header style='display:flex;align-items:center;gap:12px;margin-bottom:8px'>{$logo}<h1>{$title}</h1></header>";
        $html .= "<h2>Premium Resale Inventory</h2>";
        $headers = array_keys( $rows[0] );
        $html   .= '<table><thead><tr>';
        foreach ( $headers as $h ) {
            $html .= '<th>' . htmlspecialchars( (string) $h ) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ( $rows as $row ) {
            $html .= '<tr>';
            foreach ( $headers as $h ) {
                $val   = $row[ $h ] ?? '';
                $val   = is_array( $val ) ? wp_json_encode( $val ) : (string) $val;
                $html .= '<td>' . htmlspecialchars( $val ) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';
        return $html;
    }
}
