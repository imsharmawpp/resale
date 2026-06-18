<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Saved_Alert_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'saved_alerts';
    }

    /** @return array<int, array{visitor_token:string, criteria: array<string,mixed>}> */
    public function listForTenant( int $tenant_id ): array {
        $rows = $this->selectAll( $tenant_id, [], 'ORDER BY id ASC' );
        $out  = [];
        foreach ( $rows as $r ) {
            $out[] = [
                'visitor_token' => (string) $r['visitor_token'],
                'criteria'      => json_decode( (string) $r['criteria_json'], true ) ?: [],
            ];
        }
        return $out;
    }

    public function save( int $tenant_id, string $visitor_token, array $criteria ): int {
        return $this->insert( $tenant_id, [
            'visitor_token' => $visitor_token,
            'criteria_json' => wp_json_encode( $criteria ),
        ] );
    }
}
