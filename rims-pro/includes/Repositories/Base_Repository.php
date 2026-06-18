<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

/**
 * $wpdb-backed base repository whose query helpers ALWAYS scope by tenant_id
 * (read) and stamp tenant_id from server context (write). Uses parameterized
 * statements only.
 *
 * For property-based testing, an `In_Memory_Backend` may be supplied (see tests).
 */
abstract class Base_Repository {

    abstract protected function table_suffix(): string;

    /** Whether this entity table contains a `tenant_id` column. */
    protected function tenant_scoped(): bool {
        return true;
    }

    protected function table(): string {
        global $wpdb;
        return $wpdb->prefix . RIMS_PRO_DB_PREFIX . $this->table_suffix();
    }

    /** @return array<int, array<string, mixed>> */
    protected function selectAll( int $tenant_id, array $where = [], string $order_sql = '', int $limit = 0, int $offset = 0 ): array {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return [];
        }
        $sql_parts = [];
        $params    = [];
        if ( $this->tenant_scoped() ) {
            $sql_parts[] = 'tenant_id = %d';
            $params[]    = $tenant_id;
        }
        foreach ( $where as $col => $val ) {
            if ( $val === null ) {
                continue;
            }
            $sql_parts[] = "{$col} = %s";
            $params[]    = $val;
        }
        $where_sql = $sql_parts ? 'WHERE ' . implode( ' AND ', $sql_parts ) : '';
        $sql       = "SELECT * FROM {$this->table()} {$where_sql} {$order_sql}";
        if ( $limit > 0 ) {
            $sql     .= ' LIMIT %d OFFSET %d';
            $params[] = $limit;
            $params[] = $offset;
        }
        $prepared = $params ? $wpdb->prepare( $sql, $params ) : $sql;
        $rows     = $wpdb->get_results( $prepared, ARRAY_A );
        return is_array( $rows ) ? $rows : [];
    }

    /** @return array<string, mixed>|null */
    protected function selectOne( int $tenant_id, array $where ): ?array {
        $rows = $this->selectAll( $tenant_id, $where, '', 1 );
        return $rows[0] ?? null;
    }

    protected function insert( int $tenant_id, array $data ): int {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return 0;
        }
        if ( $this->tenant_scoped() ) {
            $data['tenant_id'] = $tenant_id; // stamped from server context, NEVER from client
        }
        $wpdb->insert( $this->table(), $data );
        return (int) $wpdb->insert_id;
    }

    protected function update( int $tenant_id, int $id, array $data ): bool {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return false;
        }
        if ( $this->tenant_scoped() ) {
            unset( $data['tenant_id'] ); // never permit re-tenanting
            $where = [ 'id' => $id, 'tenant_id' => $tenant_id ];
        } else {
            $where = [ 'id' => $id ];
        }
        $rows = $wpdb->update( $this->table(), $data, $where );
        return $rows !== false;
    }

    protected function delete( int $tenant_id, int $id ): bool {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return false;
        }
        $where = $this->tenant_scoped() ? [ 'id' => $id, 'tenant_id' => $tenant_id ] : [ 'id' => $id ];
        $rows  = $wpdb->delete( $this->table(), $where );
        return $rows !== false;
    }

    protected function count( int $tenant_id, array $where = [] ): int {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return 0;
        }
        $sql_parts = [];
        $params    = [];
        if ( $this->tenant_scoped() ) {
            $sql_parts[] = 'tenant_id = %d';
            $params[]    = $tenant_id;
        }
        foreach ( $where as $col => $val ) {
            if ( $val === null ) {
                continue;
            }
            $sql_parts[] = "{$col} = %s";
            $params[]    = $val;
        }
        $where_sql = $sql_parts ? 'WHERE ' . implode( ' AND ', $sql_parts ) : '';
        $sql       = "SELECT COUNT(*) FROM {$this->table()} {$where_sql}";
        $prepared  = $params ? $wpdb->prepare( $sql, $params ) : $sql;
        return (int) $wpdb->get_var( $prepared );
    }
}
