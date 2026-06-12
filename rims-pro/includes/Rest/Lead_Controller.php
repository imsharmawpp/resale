<?php
declare(strict_types=1);

namespace RimsPro\Rest;

final class Lead_Controller extends Rest_Controller_Base {

    public function register_routes(): void {
        $ns = RIMS_PRO_REST_NAMESPACE;

        register_rest_route( $ns, '/leads', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'index' ],
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create' ],
                'permission_callback' => '__return_true',
            ],
        ] );

        register_rest_route( $ns, '/leads/(?P<id>\d+)/move', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'move' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    public function index( \WP_REST_Request $request ): \WP_REST_Response {
        $tenant_id = $this->tenant_id();
        $page      = max( 1, (int) $request->get_param( 'page' ) ?: 1 );
        $per_page  = max( 1, min( 100, (int) $request->get_param( 'per_page' ) ?: 50 ) );
        $leads     = $this->c->lead_repository()->listForTenant( $tenant_id );
        $page_data = $this->c->pagination_service()->page( $leads, $page, $per_page );

        $items = array_map(
            static fn( $l ) => $l->toArray(),
            $page_data['items']
        );
        return $this->envelope(
            $items,
            [
                'total'       => (int) $page_data['total'],
                'page'        => (int) $page_data['page'],
                'per_page'    => (int) $page_data['per_page'],
                'total_pages' => (int) $page_data['total_pages'],
                'counts'      => $this->c->lead_management()->countsByStage( $leads ),
            ]
        );
    }

    public function create( \WP_REST_Request $request ): \WP_REST_Response {
        // Public lead capture - require nonce.
        $nonce = (string) $request->get_header( 'X-Rims-Nonce' );
        $r     = $this->c->authorization_guard()->authorize( [
            'nonce'     => $nonce,
            'action'    => RIMS_PRO_NONCE_ACTION,
            'client_id' => $this->clientId( $request ),
            'rate_limit' => $this->rateLimitArgs(),
        ] );
        if ( ! $r->isOk() ) {
            return $this->error( 'unauthorized', $r->firstError() ?? 'Unauthorized', 401 );
        }
        $body  = (array) $request->get_json_params();
        $out   = $this->c->lead_capture()->submit( $this->tenant_id(), $body );
        if ( ! $out['result']->isOk() ) {
            return $this->fromValidation( $out['result'] );
        }
        $lead_id = (int) ( $out['lead_id'] ?? 0 );
        // Async (best-effort) CRM forward.
        if ( $lead_id > 0 ) {
            $lead = $this->c->lead_repository()->find( $this->tenant_id(), $lead_id );
            if ( $lead ) {
                $this->c->crm_integration_service()->forward( $this->tenant_id(), $lead );
            }
        }
        return $this->envelope( [ 'id' => $lead_id ], [ 'created' => true ] );
    }

    public function move( \WP_REST_Request $request ): \WP_REST_Response {
        $err = $this->authorizeWrite( $request, 'manage_options' );
        if ( $err ) {
            return $err;
        }
        $id    = (int) $request->get_param( 'id' );
        $stage = (string) $request->get_param( 'stage' );
        $ok    = $this->c->lead_management()->move( $this->tenant_id(), $id, $stage, get_current_user_id() );
        if ( ! $ok ) {
            return $this->error( 'invalid_stage', 'Stage move rejected.', 422 );
        }
        return $this->envelope( [ 'id' => $id, 'stage' => $stage ], [ 'moved' => true ] );
    }
}
