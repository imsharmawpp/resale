<?php
declare(strict_types=1);

namespace RimsPro\Tenancy;

use RimsPro\Domain\Tenant;
use RimsPro\Repositories\Tenant_Repository;

/**
 * Resolves Tenant context once per request and supplies branding.
 *
 * Single-site mode: resolved by host domain (or first tenant fallback).
 * SaaS mode: resolved by `tenant_id` mapping (header / query / option).
 */
class Tenant_Resolver {

    private ?Tenant $cached = null;

    /** @var array<int, array<string, mixed>> */
    private array $branding_cache = [];

    public function __construct(
        private Tenant_Repository $tenants,
    ) {
    }

    public function resolve(): Tenant {
        if ( $this->cached !== null ) {
            return $this->cached;
        }

        // Safety: if tables don't exist yet (activation incomplete), return default.
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return $this->cached = new Tenant( id: 1, name: 'Default', domain: 'localhost', mode: 'single' );
        }

        // Check if our tables exist (cheap SHOW TABLES query, cached after first check).
        static $tables_exist = null;
        if ( $tables_exist === null ) {
            $table = $wpdb->prefix . RIMS_PRO_DB_PREFIX . 'tenants';
            $tables_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== null;
        }
        if ( ! $tables_exist ) {
            return $this->cached = new Tenant( id: 1, name: 'Default', domain: 'localhost', mode: 'single' );
        }

        $explicit = (int) ( $_SERVER['HTTP_X_RIMS_TENANT'] ?? 0 );
        if ( $explicit > 0 ) {
            $tenant = $this->tenants->find( $explicit );
            if ( $tenant ) {
                return $this->cached = $tenant;
            }
        }

        if ( function_exists( 'home_url' ) ) {
            $host = (string) ( wp_parse_url( home_url(), PHP_URL_HOST ) ?? '' );
            if ( $host !== '' ) {
                $tenant = $this->tenants->findByDomain( $host );
                if ( $tenant ) {
                    return $this->cached = $tenant;
                }
            }
        }

        // Fallback: first tenant or anonymous tenant 0.
        $tenant = $this->tenants->first();
        return $this->cached = ( $tenant ?? new Tenant( id: 1, name: 'Default', domain: 'localhost', mode: 'single' ) );
    }

    /** @return array<string, mixed> */
    public function branding( int $tenant_id ): array {
        if ( isset( $this->branding_cache[ $tenant_id ] ) ) {
            return $this->branding_cache[ $tenant_id ];
        }
        $branding = $this->tenants->settings( $tenant_id );
        if ( ! $branding ) {
            $branding = [
                'company_name'             => 'GoldLine Estate',
                'logo_url'                 => '',
                'primary_color'            => '#1e3a8a',
                'contact_phone'            => '',
                'contact_whatsapp'         => '',
                'watermark_enabled'        => false,
                'watermark_path'           => '',
                'expiry_days'              => 90,
                'infinite_scroll_enabled'  => true,
                'rate_limit_window'        => 60,
                'rate_limit_max'           => 60,
                'status_color_map'         => \RimsPro\Domain\UnitStatus::default_color_map(),
            ];
        }
        return $this->branding_cache[ $tenant_id ] = $branding;
    }

    /** Test seam */
    public function override( Tenant $tenant ): void {
        $this->cached = $tenant;
    }
}
