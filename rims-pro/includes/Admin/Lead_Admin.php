<?php
declare(strict_types=1);

namespace RimsPro\Admin;

use RimsPro\Core\Container;
use RimsPro\Domain\PipelineStage;

final class Lead_Admin {

    public function __construct( private Container $c ) {}

    public function register(): void {
        add_submenu_page(
            'rims-pro',
            __( 'Leads', 'rims-pro' ),
            __( 'Leads', 'rims-pro' ),
            'manage_options',
            'rims-pro-leads',
            [ $this, 'render' ]
        );
    }

    public function render(): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;

        if ( isset( $_POST['rims_lead_action'] ) && check_admin_referer( 'rims_pro_leads' ) ) {
            $action = sanitize_text_field( (string) $_POST['rims_lead_action'] );
            if ( $action === 'move' ) {
                $id    = (int) ( $_POST['id'] ?? 0 );
                $stage = sanitize_text_field( (string) ( $_POST['stage'] ?? '' ) );
                $this->c->lead_management()->move( $tenant_id, $id, $stage, get_current_user_id() );
            }
        }

        $leads  = $this->c->lead_repository()->listForTenant( $tenant_id );
        $stages = PipelineStage::ordered();
        $columns = [];
        foreach ( $stages as $s ) {
            $columns[ $s->value ] = [ 'label' => $s->label(), 'leads' => [] ];
        }
        foreach ( $leads as $l ) {
            if ( isset( $columns[ $l->pipeline_stage ] ) ) {
                $columns[ $l->pipeline_stage ]['leads'][] = $l;
            }
        }
        include RIMS_PRO_DIR . 'templates/admin/leads.php';
    }
}
