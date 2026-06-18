<?php
declare(strict_types=1);

namespace RimsPro\Rest;

use RimsPro\Domain\FilterSet;
use RimsPro\Domain\Inventory_Unit;

final class Inventory_Controller extends Rest_Controller_Base {

    public function register_routes(): void {
        $ns = RIMS_PRO_REST_NAMESPACE;

        register_rest_route( $ns, '/inventory', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'index' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'page'     => [ 'type' => 'integer', 'default' => 1 ],
                    'per_page' => [ 'type' => 'integer', 'default' => 12 ],
                ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create' ],
                'permission_callback' => '__return_true',
            ],
        ] );

        register_rest_route( $ns, '/inventory/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'show' ],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update' ],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'destroy' ],
                'permission_callback' => '__return_true',
            ],
        ] );

        register_rest_route( $ns, '/inventory/search', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'search' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function index( \WP_REST_Request $request ): \WP_REST_Response {
        $tenant_id = $this->tenant_id();
        $filters   = FilterSet::fromArray( (array) $request->get_query_params() );
        $page      = max( 1, (int) $request->get_param( 'page' ) );
        $per_page  = max( 1, min( 100, (int) $request->get_param( 'per_page' ) ) );

        $units    = $this->c->inventory_repository()->listForTenant( $tenant_id, false );
        $filtered = $this->c->filter_engine()->apply( $filters, $units );
        $page_data = $this->c->pagination_service()->page( $filtered['units'], $page, $per_page );

        $include_internal = current_user_can( 'manage_options' );
        $payload          = $this->c->field_visibility_serializer()->serializeMany( $page_data['items'], $include_internal );

        return $this->envelope(
            $payload,
            [
                'total'       => (int) $page_data['total'],
                'page'        => (int) $page_data['page'],
                'per_page'    => (int) $page_data['per_page'],
                'total_pages' => (int) $page_data['total_pages'],
                'count'       => (int) $filtered['count'],
            ]
        );
    }

    public function show( \WP_REST_Request $request ): \WP_REST_Response {
        $tenant_id = $this->tenant_id();
        $id        = (int) $request->get_param( 'id' );
        $unit      = $this->c->inventory_repository()->find( $tenant_id, $id );
        if ( ! $unit || $unit->expired ) {
            return $this->error( 'not_found', 'Unit not found.', 404 );
        }
        $include_internal = current_user_can( 'manage_options' );
        return $this->envelope( $this->c->field_visibility_serializer()->serialize( $unit, $include_internal ) );
    }

    public function create( \WP_REST_Request $request ): \WP_REST_Response {
        $err = $this->authorizeWrite( $request, 'manage_options' );
        if ( $err ) {
            return $err;
        }
        $body = (array) $request->get_json_params();
        $r    = $this->c->inventory_manager()->create( $this->tenant_id(), $body );
        if ( ! $r['result']->isOk() ) {
            return $this->fromValidation( $r['result'] );
        }
        return $this->envelope( [ 'id' => $r['unit_id'] ?? 0 ], [ 'created' => true ] );
    }

    public function update( \WP_REST_Request $request ): \WP_REST_Response {
        $err = $this->authorizeWrite( $request, 'manage_options' );
        if ( $err ) {
            return $err;
        }
        $body = (array) $request->get_json_params();
        $id   = (int) $request->get_param( 'id' );
        $r    = $this->c->inventory_manager()->update_unit( $this->tenant_id(), $id, $body );
        if ( ! $r->isOk() ) {
            return $this->fromValidation( $r );
        }
        return $this->envelope( [ 'id' => $id ], [ 'updated' => true ] );
    }

    public function destroy( \WP_REST_Request $request ): \WP_REST_Response {
        $err = $this->authorizeWrite( $request, 'manage_options' );
        if ( $err ) {
            return $err;
        }
        $id = (int) $request->get_param( 'id' );
        $ok = $this->c->inventory_manager()->delete_unit( $this->tenant_id(), $id );
        return $this->envelope( [ 'id' => $id ], [ 'deleted' => $ok ] );
    }

    public function search( \WP_REST_Request $request ): \WP_REST_Response {
        $tenant_id = $this->tenant_id();
        $query     = (string) $request->get_param( 'q' );
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );

        $builders = array_values( array_unique( array_filter( array_map( static fn( Inventory_Unit $u ) => $u->builder, $units ) ) ) );
        $names    = array_values( array_unique( array_filter( array_map( static fn( Inventory_Unit $u ) => $u->project_name, $units ) ) ) );
        $known_bp = array_values( array_unique( array_merge( $builders, $names ) ) );
        $known_loc = array_values( array_unique( array_filter( array_map( static fn( Inventory_Unit $u ) => $u->location, $units ) ) ) );

        $criteria = $this->c->smart_search_engine()->parse( $query, $known_bp, $known_loc );
        $matched  = $this->c->smart_search_engine()->search( $criteria, $units );

        $include_internal = current_user_can( 'manage_options' );
        return $this->envelope(
            $this->c->field_visibility_serializer()->serializeMany( $matched, $include_internal ),
            [
                'count'    => count( $matched ),
                'criteria' => [
                    'bhk'                => $criteria->bhk,
                    'max_budget_paise'   => $criteria->maxBudgetPaise,
                    'builder_or_project' => $criteria->builderOrProject,
                    'location'           => $criteria->location,
                ],
            ]
        );
    }
}
