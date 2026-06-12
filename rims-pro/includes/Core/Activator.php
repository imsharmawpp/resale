<?php
declare(strict_types=1);

namespace RimsPro\Core;

use RimsPro\Migrations\Migration_Runner;

final class Activator {

    public function activate(): void {
        // Extend execution time to prevent 504 timeouts on shared hosting.
        if ( function_exists( 'set_time_limit' ) ) {
            @set_time_limit( 300 ); // Allow up to 5 minutes for table creation.
        }
        // Suppress PHP memory limit issues on constrained hosts.
        if ( function_exists( 'wp_raise_memory_limit' ) ) {
            wp_raise_memory_limit( 'admin' );
        }

        // Run migrations.
        ( new Migration_Runner() )->run();

        // Seed default tenant + tenant settings if missing.
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return;
        }
        $tenants = $wpdb->prefix . RIMS_PRO_DB_PREFIX . 'tenants';
        $settings = $wpdb->prefix . RIMS_PRO_DB_PREFIX . 'tenant_settings';

        $existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tenants}" );
        if ( $existing === 0 ) {
            $domain = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'localhost';
            $wpdb->insert(
                $tenants,
                [
                    'name'   => get_bloginfo( 'name' ) ?: 'Default Tenant',
                    'domain' => $domain,
                    'mode'   => 'single',
                ]
            );
            $tenant_id = (int) $wpdb->insert_id;

            $defaults = [
                'tenant_id'                => $tenant_id,
                'company_name'             => get_bloginfo( 'name' ) ?: 'GoldLine Estate',
                'logo_url'                 => '',
                'primary_color'            => '#1e3a8a',
                'contact_phone'            => '',
                'contact_whatsapp'         => '',
                'watermark_enabled'        => 0,
                'watermark_path'           => '',
                'expiry_days'              => 90,
                'infinite_scroll_enabled'  => 1,
                'rate_limit_window'        => 60,
                'rate_limit_max'           => 60,
                'status_color_map'         => wp_json_encode(
                    [
                        'available'         => '#10b981',
                        'blocked'           => '#f97316',
                        'token_received'    => '#3b82f6',
                        'under_negotiation' => '#a855f7',
                        'sold'              => '#6b7280',
                    ]
                ),
            ];
            $wpdb->insert( $settings, $defaults );
        }

        // Schedule cron job for expiry.
        if ( ! wp_next_scheduled( 'rims_pro_expire_units' ) ) {
            wp_schedule_event( time() + 600, 'daily', 'rims_pro_expire_units' );
        }

        // Defer flush_rewrite_rules() to admin_init to avoid activation timeout.
        // The actual flush happens on the next admin page load via Plugin::boot().
        set_transient( 'rims_pro_flush_rewrite', '1', 60 );
    }
}
