<?php
declare(strict_types=1);

namespace RimsPro\Admin;

use RimsPro\Core\Container;

final class Admin_Dashboard {

    public function __construct( private Container $c ) {}

    public function register(): void {
        add_menu_page(
            __( 'RIMS Pro', 'rims-pro' ),
            __( 'RIMS Pro', 'rims-pro' ),
            'manage_options',
            'rims-pro',
            [ $this, 'render' ],
            'dashicons-building',
            56
        );
        add_submenu_page(
            'rims-pro',
            __( 'Dashboard', 'rims-pro' ),
            __( 'Dashboard', 'rims-pro' ),
            'manage_options',
            'rims-pro',
            [ $this, 'render' ]
        );
    }

    public function render(): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id, true );
        $leads     = $this->c->lead_repository()->listForTenant( $tenant_id );
        $now       = time();
        $start     = strtotime( '-30 days', $now ) ?: $now;
        $metrics   = $this->c->dashboard_metrics_service()->compute( $units, $leads, $start, $now );
        $top_proj  = $this->c->analytics_engine()->topProjects( $tenant_id, 10 );
        $top_unit  = $this->c->analytics_engine()->topUnits( $tenant_id, 10 );
        include RIMS_PRO_DIR . 'templates/admin/dashboard.php';
    }
}
