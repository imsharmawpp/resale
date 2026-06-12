<?php
declare(strict_types=1);

namespace RimsPro\Admin;

use RimsPro\Core\Container;

final class Settings_Admin {

    public function __construct( private Container $c ) {}

    public function register(): void {
        add_submenu_page(
            'rims-pro',
            __( 'Settings', 'rims-pro' ),
            __( 'Settings', 'rims-pro' ),
            'manage_options',
            'rims-pro-settings',
            [ $this, 'render' ]
        );
    }

    public function render(): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;

        if ( isset( $_POST['rims_settings_action'] ) && check_admin_referer( 'rims_pro_settings' ) ) {
            $patch = [
                'company_name'     => sanitize_text_field( (string) ( $_POST['company_name'] ?? '' ) ),
                'logo_url'         => esc_url_raw( (string) ( $_POST['logo_url'] ?? '' ) ),
                'primary_color'    => sanitize_hex_color( (string) ( $_POST['primary_color'] ?? '#1e3a8a' ) ),
                'contact_phone'    => sanitize_text_field( (string) ( $_POST['contact_phone'] ?? '' ) ),
                'contact_whatsapp' => sanitize_text_field( (string) ( $_POST['contact_whatsapp'] ?? '' ) ),
                'expiry_days'      => (int) ( $_POST['expiry_days'] ?? 90 ),
                'rate_limit_max'   => (int) ( $_POST['rate_limit_max'] ?? 60 ),
                'rate_limit_window' => (int) ( $_POST['rate_limit_window'] ?? 60 ),
                'infinite_scroll_enabled' => ! empty( $_POST['infinite_scroll_enabled'] ) ? 1 : 0,
                'watermark_enabled' => ! empty( $_POST['watermark_enabled'] ) ? 1 : 0,
            ];
            $this->c->tenant_repository()->updateSettings( $tenant_id, $patch );
            add_settings_error( 'rims_pro_settings', 'saved', 'Settings saved.', 'updated' );
        }

        $branding = $this->c->tenant_resolver()->branding( $tenant_id );
        include RIMS_PRO_DIR . 'templates/admin/settings.php';
    }
}
