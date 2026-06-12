<?php
declare(strict_types=1);

namespace RimsPro\Admin;

use RimsPro\Core\Container;

final class Inventory_Admin {

    public function __construct( private Container $c ) {}

    public function register(): void {
        add_submenu_page(
            'rims-pro',
            __( 'Inventory', 'rims-pro' ),
            __( 'Inventory', 'rims-pro' ),
            'manage_options',
            'rims-pro-inventory',
            [ $this, 'render' ]
        );
    }

    public function render(): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;

        if ( isset( $_POST['rims_action'] ) && check_admin_referer( 'rims_pro_inventory' ) ) {
            $this->handle_action( $tenant_id );
        }

        $units = $this->c->inventory_repository()->listForTenant( $tenant_id, true );
        $projects = $this->c->project_repository()->listForTenant( $tenant_id );
        include RIMS_PRO_DIR . 'templates/admin/inventory.php';
    }

    private function handle_action( int $tenant_id ): void {
        $action = sanitize_text_field( (string) $_POST['rims_action'] );
        if ( $action === 'create' ) {
            $r = $this->c->inventory_manager()->create( $tenant_id, $_POST );
            if ( ! $r['result']->isOk() ) {
                add_settings_error( 'rims_pro_inventory', 'create_failed', $r['result']->firstError() ?? '' );
            } else {
                add_settings_error( 'rims_pro_inventory', 'created', 'Unit created.', 'updated' );
            }
        } elseif ( $action === 'delete' ) {
            $id = (int) ( $_POST['id'] ?? 0 );
            if ( $id > 0 ) {
                $this->c->inventory_manager()->delete_unit( $tenant_id, $id );
                add_settings_error( 'rims_pro_inventory', 'deleted', 'Unit deleted.', 'updated' );
            }
        } elseif ( $action === 'change_status' ) {
            $id     = (int) ( $_POST['id'] ?? 0 );
            $status = sanitize_text_field( (string) ( $_POST['status'] ?? '' ) );
            $this->c->inventory_manager()->changeStatus( $tenant_id, $id, $status );
        }
    }
}
