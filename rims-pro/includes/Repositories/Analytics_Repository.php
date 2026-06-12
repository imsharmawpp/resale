<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Analytics_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'analytics_events';
    }

    public function record( int $tenant_id, string $event_type, ?int $unit_id, ?int $project_id, string $session_hash ): int {
        return $this->insert( $tenant_id, [
            'event_type'   => $event_type,
            'unit_id'      => $unit_id,
            'project_id'   => $project_id,
            'session_hash' => $session_hash,
        ] );
    }

    /** @return array<int, array{entity_id:int, count:int}> */
    public function topByEvent( int $tenant_id, string $event_type, string $entity_col, int $limit = 10 ): array {
        global $wpdb;
        $col = $entity_col === 'unit_id' ? 'unit_id' : 'project_id';
        $sql = $wpdb->prepare(
            "SELECT {$col} AS entity_id, COUNT(*) AS c
             FROM {$this->table()}
             WHERE tenant_id = %d AND event_type = %s AND {$col} IS NOT NULL
             GROUP BY {$col}
             ORDER BY c DESC
             LIMIT %d",
            $tenant_id,
            $event_type,
            $limit
        );
        $rows = $wpdb->get_results( $sql, ARRAY_A );
        $out  = [];
        foreach ( $rows ?: [] as $r ) {
            $out[] = [ 'entity_id' => (int) $r['entity_id'], 'count' => (int) $r['c'] ];
        }
        return $out;
    }
}
