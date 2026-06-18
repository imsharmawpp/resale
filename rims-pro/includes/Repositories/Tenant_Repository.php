<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

use RimsPro\Domain\Tenant;
use RimsPro\Domain\UnitStatus;

class Tenant_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'tenants';
    }

    protected function tenant_scoped(): bool {
        return false;
    }

    public function find( int $id ): ?Tenant {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return null;
        }
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $id ),
            ARRAY_A
        );
        return $row ? $this->hydrate( $row ) : null;
    }

    public function findByDomain( string $domain ): ?Tenant {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return null;
        }
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE domain = %s LIMIT 1", $domain ),
            ARRAY_A
        );
        return $row ? $this->hydrate( $row ) : null;
    }

    public function first(): ?Tenant {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return null;
        }
        $row = $wpdb->get_row( "SELECT * FROM {$this->table()} ORDER BY id ASC LIMIT 1", ARRAY_A );
        return $row ? $this->hydrate( $row ) : null;
    }

    /** @return array<string, mixed>|null */
    public function settings( int $tenant_id ): ?array {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return null;
        }
        $table = $wpdb->prefix . RIMS_PRO_DB_PREFIX . 'tenant_settings';
        $row   = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE tenant_id = %d", $tenant_id ),
            ARRAY_A
        );
        if ( ! $row ) {
            return null;
        }
        $row['watermark_enabled']       = (bool) $row['watermark_enabled'];
        $row['infinite_scroll_enabled'] = (bool) $row['infinite_scroll_enabled'];
        $row['expiry_days']             = (int) $row['expiry_days'];
        $row['rate_limit_window']       = (int) $row['rate_limit_window'];
        $row['rate_limit_max']          = (int) $row['rate_limit_max'];
        $colors                         = json_decode( (string) $row['status_color_map'], true );
        $row['status_color_map']        = is_array( $colors ) ? $colors : UnitStatus::default_color_map();
        return $row;
    }

    public function updateSettings( int $tenant_id, array $patch ): void {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return;
        }
        $table = $wpdb->prefix . RIMS_PRO_DB_PREFIX . 'tenant_settings';
        if ( isset( $patch['status_color_map'] ) && is_array( $patch['status_color_map'] ) ) {
            $patch['status_color_map'] = wp_json_encode( $patch['status_color_map'] );
        }
        $exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE tenant_id = %d", $tenant_id ) );
        if ( $exists > 0 ) {
            $wpdb->update( $table, $patch, [ 'tenant_id' => $tenant_id ] );
        } else {
            $patch['tenant_id'] = $tenant_id;
            $wpdb->insert( $table, $patch );
        }
    }

    private function hydrate( array $row ): Tenant {
        return new Tenant(
            id: (int) $row['id'],
            name: (string) $row['name'],
            domain: (string) $row['domain'],
            mode: (string) ( $row['mode'] ?? 'single' ),
        );
    }
}
