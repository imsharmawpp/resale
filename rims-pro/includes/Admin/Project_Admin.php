<?php
declare(strict_types=1);

namespace RimsPro\Admin;

use RimsPro\Core\Container;
use RimsPro\Domain\Project;

final class Project_Admin {

    public function __construct( private Container $c ) {}

    public function register(): void {
        add_submenu_page(
            'rims-pro',
            __( 'Projects', 'rims-pro' ),
            __( 'Projects', 'rims-pro' ),
            'manage_options',
            'rims-pro-projects',
            [ $this, 'render' ]
        );
    }

    public function render(): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;

        // Handle form submission
        if ( isset( $_POST['rims_action'] ) && check_admin_referer( 'rims_pro_projects' ) ) {
            $this->handle_action( $tenant_id );
        }

        $projects = $this->c->project_repository()->listForTenant( $tenant_id );

        // Check if editing an existing project
        $editing = null;
        if ( isset( $_GET['edit'] ) ) {
            $editing = $this->c->project_repository()->find( $tenant_id, (int) $_GET['edit'] );
        }

        include RIMS_PRO_DIR . 'templates/admin/projects.php';
    }

    private function handle_action( int $tenant_id ): void {
        $action = sanitize_text_field( (string) $_POST['rims_action'] );

        if ( $action === 'create' || $action === 'update' ) {
            $name = sanitize_text_field( (string) ( $_POST['name'] ?? '' ) );
            if ( $name === '' ) {
                add_settings_error( 'rims_pro_projects', 'name_required', 'Project name is required.' );
                return;
            }

            $existing_slugs = array_map(
                static fn( Project $p ) => $p->slug,
                $this->c->project_repository()->listForTenant( $tenant_id )
            );

            $project = new Project(
                id: $action === 'update' ? (int) ( $_POST['id'] ?? 0 ) : 0,
                tenant_id: $tenant_id,
                name: $name,
                slug: '', // will be generated
                builder: sanitize_text_field( (string) ( $_POST['builder'] ?? '' ) ),
                location: sanitize_text_field( (string) ( $_POST['location'] ?? '' ) ),
                sector: sanitize_text_field( (string) ( $_POST['sector'] ?? '' ) ),
                latitude: ( $_POST['latitude'] ?? '' ) !== '' ? (float) $_POST['latitude'] : null,
                longitude: ( $_POST['longitude'] ?? '' ) !== '' ? (float) $_POST['longitude'] : null,
                possession_status: sanitize_text_field( (string) ( $_POST['possession_status'] ?? '' ) ),
                tower_count: (int) ( $_POST['tower_count'] ?? 0 ),
                seo_title: sanitize_text_field( (string) ( $_POST['seo_title'] ?? '' ) ),
                seo_description: sanitize_textarea_field( (string) ( $_POST['seo_description'] ?? '' ) ),
                overview: sanitize_textarea_field( (string) ( $_POST['overview'] ?? '' ) ),
            );

            // Generate slug
            $project->slug = $this->c->seo_engine()->slugForProject( $project, $existing_slugs );

            $this->c->project_repository()->persist( $tenant_id, $project );
            $msg = $action === 'update' ? 'Project updated.' : 'Project created.';
            add_settings_error( 'rims_pro_projects', 'saved', $msg, 'updated' );

        } elseif ( $action === 'delete' ) {
            $id = (int) ( $_POST['id'] ?? 0 );
            if ( $id > 0 ) {
                $this->c->project_repository()->delete_project( $tenant_id, $id );
                add_settings_error( 'rims_pro_projects', 'deleted', 'Project deleted.', 'updated' );
            }
        }
    }
}
