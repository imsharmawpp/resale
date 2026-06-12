<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;

/**
 * Broker-sheet formatter (Property 15 / Req 10.3-10.4).
 * - Renders verbatim shorthand label (`@ MP`, `P/p`, `U/C`, `CR WITHOUT OC`).
 * - Collapses multi-variant rows into a single project row containing every variant area.
 */
final class Broker_Sheet_Formatter {

    public const SHORTHAND_TOKENS = [ '@ MP', 'P/p', 'U/C', 'CR WITHOUT OC' ];

    /**
     * @param Inventory_Unit[] $units
     * @return array<int, array{project:string, bhk:float, areas:int[], floor:int, price_label:string, status:string, builder:string, location:string}>
     */
    public function format( array $units ): array {
        // Group units by project + bhk + price_label (variants share these).
        $groups = [];
        foreach ( $units as $u ) {
            $key = sprintf( '%s|%.1f|%s', strtolower( $u->project_name ), $u->bhk, $u->price_label );
            if ( ! isset( $groups[ $key ] ) ) {
                $groups[ $key ] = [
                    'project'     => $u->project_name,
                    'bhk'         => $u->bhk,
                    'areas'       => [],
                    'floor'       => $u->floor,
                    'price_label' => $u->price_label !== '' ? $u->price_label : '@ MP',
                    'status'      => $u->status,
                    'builder'     => $u->builder,
                    'location'    => $u->location,
                ];
            }
            $groups[ $key ]['areas'][] = $u->area_sqft;
            foreach ( $u->variants as $v ) {
                $groups[ $key ]['areas'][] = (int) $v['area_sqft'];
            }
        }
        // Deduplicate area lists, preserve order
        foreach ( $groups as &$g ) {
            $g['areas'] = array_values( array_unique( $g['areas'] ) );
        }
        return array_values( $groups );
    }

    /**
     * Render a single row's price label string verbatim (no transformations).
     */
    public function priceLabel( Inventory_Unit $u ): string {
        return $u->price_label !== '' ? $u->price_label : '@ MP';
    }
}
