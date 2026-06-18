<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Bookmark_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'bookmarks';
    }

    public function add( int $tenant_id, string $visitor_token, int $unit_id ): int {
        $existing = $this->selectOne( $tenant_id, [ 'visitor_token' => $visitor_token, 'unit_id' => $unit_id ] );
        if ( $existing ) {
            return (int) $existing['id'];
        }
        return $this->insert( $tenant_id, [ 'visitor_token' => $visitor_token, 'unit_id' => $unit_id ] );
    }

    /** @return int[] */
    public function listUnitIds( int $tenant_id, string $visitor_token ): array {
        $rows = $this->selectAll( $tenant_id, [ 'visitor_token' => $visitor_token ], 'ORDER BY id DESC' );
        return array_map( static fn( array $r ) => (int) $r['unit_id'], $rows );
    }

    public function remove( int $tenant_id, string $visitor_token, int $unit_id ): bool {
        global $wpdb;
        $rows = $wpdb->delete( $this->table(), [
            'tenant_id'     => $tenant_id,
            'visitor_token' => $visitor_token,
            'unit_id'       => $unit_id,
        ] );
        return $rows !== false;
    }
}
