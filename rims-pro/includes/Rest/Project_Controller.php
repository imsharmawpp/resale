<?php
declare(strict_types=1);

namespace RimsPro\Rest;

final class Project_Controller extends Rest_Controller_Base {

    public function register_routes(): void {
        $ns = RIMS_PRO_REST_NAMESPACE;

        register_rest_route( $ns, '/projects', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'index' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( $ns, '/projects/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'show' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function index( \WP_REST_Request $request ): \WP_REST_Response {
        $tenant_id = $this->tenant_id();
        $page      = max( 1, (int) $request->get_param( 'page' ) ?: 1 );
        $per_page  = max( 1, min( 100, (int) $request->get_param( 'per_page' ) ?: 24 ) );
        $projects  = $this->c->project_repository()->listForTenant( $tenant_id );
        $page_data = $this->c->pagination_service()->page( $projects, $page, $per_page );

        $items = array_map(
            static fn( $p ) => [
                'id'                => $p->id,
                'tenant_id'         => $p->tenant_id,
                'name'              => $p->name,
                'slug'              => $p->slug,
                'builder'           => $p->builder,
                'location'          => $p->location,
                'sector'            => $p->sector,
                'latitude'          => $p->latitude,
                'longitude'         => $p->longitude,
                'possession_status' => $p->possession_status,
                'tower_count'       => $p->tower_count,
                'overview'          => $p->overview,
                'seo_title'         => $p->seo_title,
                'seo_description'   => $p->seo_description,
            ],
            $page_data['items']
        );
        return $this->envelope(
            $items,
            [
                'total'       => (int) $page_data['total'],
                'page'        => (int) $page_data['page'],
                'per_page'    => (int) $page_data['per_page'],
                'total_pages' => (int) $page_data['total_pages'],
            ]
        );
    }

    public function show( \WP_REST_Request $request ): \WP_REST_Response {
        $tenant_id = $this->tenant_id();
        $id        = (int) $request->get_param( 'id' );
        $project   = $this->c->project_repository()->find( $tenant_id, $id );
        if ( ! $project ) {
            return $this->error( 'not_found', 'Project not found.', 404 );
        }
        return $this->envelope( get_object_vars( $project ) );
    }
}
