<?php
declare(strict_types=1);

namespace RimsPro\Ajax;

use RimsPro\Core\Container;
use RimsPro\Domain\FilterSet;

final class Ajax_Router {

    public function __construct( private Container $c ) {}

    public function register(): void {
        $actions = [
            'rims_filter_apply'      => [ $this, 'filter_apply' ],
            'rims_smart_search'      => [ $this, 'smart_search' ],
            'rims_view_switch'       => [ $this, 'view_switch' ],
            'rims_load_more'         => [ $this, 'load_more' ],
            'rims_lead_submit'       => [ $this, 'lead_submit' ],
            'rims_track_event'       => [ $this, 'track_event' ],
            'rims_bookmark_toggle'   => [ $this, 'bookmark_toggle' ],
        ];
        foreach ( $actions as $action => $cb ) {
            add_action( "wp_ajax_{$action}", $cb );
            add_action( "wp_ajax_nopriv_{$action}", $cb );
        }
    }

    private function tenant_id(): int {
        return (int) $this->c->tenant_resolver()->resolve()->id;
    }

    private function check_nonce(): bool {
        $nonce = (string) ( $_REQUEST['_wpnonce'] ?? $_REQUEST['nonce'] ?? '' );
        return (bool) $this->c->authorization_guard()->verifyNonce( $nonce, RIMS_PRO_NONCE_ACTION );
    }

    private function envelope( mixed $data, array $meta = [] ): void {
        wp_send_json( [ 'data' => $data, 'meta' => $meta ] );
    }

    private function error( string $code, string $message, int $status = 400, array $fields = [] ): void {
        status_header( $status );
        $payload = [ 'error' => [ 'code' => $code, 'message' => $message ] ];
        if ( $fields ) {
            $payload['error']['fields'] = $fields;
        }
        wp_send_json( $payload );
    }

    public function filter_apply(): void {
        $tenant_id = $this->tenant_id();
        $filters   = FilterSet::fromArray( (array) ( $_REQUEST['filters'] ?? [] ) );
        $page      = max( 1, (int) ( $_REQUEST['page'] ?? 1 ) );
        $per_page  = max( 1, min( 100, (int) ( $_REQUEST['per_page'] ?? 12 ) ) );

        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );
        $filtered  = $this->c->filter_engine()->apply( $filters, $units );
        $page_data = $this->c->pagination_service()->page( $filtered['units'], $page, $per_page );

        $include_internal = current_user_can( 'manage_options' );
        $payload          = $this->c->field_visibility_serializer()->serializeMany( $page_data['items'], $include_internal );

        $this->envelope(
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

    public function smart_search(): void {
        $tenant_id = $this->tenant_id();
        $query     = (string) ( $_REQUEST['q'] ?? '' );
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );

        $builders = array_values( array_unique( array_filter( array_map( static fn( $u ) => $u->builder, $units ) ) ) );
        $names    = array_values( array_unique( array_filter( array_map( static fn( $u ) => $u->project_name, $units ) ) ) );
        $loc      = array_values( array_unique( array_filter( array_map( static fn( $u ) => $u->location, $units ) ) ) );
        $criteria = $this->c->smart_search_engine()->parse( $query, array_merge( $builders, $names ), $loc );
        $matched  = $this->c->smart_search_engine()->search( $criteria, $units );
        $include_internal = current_user_can( 'manage_options' );
        $this->envelope(
            $this->c->field_visibility_serializer()->serializeMany( $matched, $include_internal ),
            [ 'count' => count( $matched ) ]
        );
    }

    public function view_switch(): void {
        // The View_Manager state lives client-side; this endpoint just ack's
        // the persisted preference + returns dataset counts.
        $tenant_id = $this->tenant_id();
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );
        $this->envelope( [ 'count' => count( $units ) ], [ 'view' => sanitize_text_field( (string) ( $_REQUEST['view'] ?? 'card' ) ) ] );
    }

    public function load_more(): void {
        $this->filter_apply();
    }

    public function lead_submit(): void {
        if ( ! $this->check_nonce() ) {
            $this->error( 'invalid_nonce', 'Invalid or missing nonce.', 401 );
            return;
        }
        $body = $_REQUEST;
        unset( $body['_wpnonce'], $body['action'], $body['nonce'] );
        $r = $this->c->lead_capture()->submit( $this->tenant_id(), $body );
        if ( ! $r['result']->isOk() ) {
            $this->error( 'validation_failed', $r['result']->firstError() ?? 'Validation failed.', 422, $r['result']->errors() );
            return;
        }
        $this->envelope( [ 'id' => (int) ( $r['lead_id'] ?? 0 ) ], [ 'created' => true ] );
    }

    public function track_event(): void {
        $tenant_id   = $this->tenant_id();
        $event_type  = sanitize_text_field( (string) ( $_REQUEST['event_type'] ?? '' ) );
        $unit_id     = (int) ( $_REQUEST['unit_id'] ?? 0 );
        $project_id  = (int) ( $_REQUEST['project_id'] ?? 0 );
        $session_hash = (string) ( $_REQUEST['session'] ?? '' );
        $ok = $this->c->analytics_engine()->record(
            $tenant_id,
            $event_type,
            $unit_id ?: null,
            $project_id ?: null,
            $session_hash
        );
        $this->envelope( [ 'recorded' => $ok ] );
    }

    public function bookmark_toggle(): void {
        if ( ! $this->check_nonce() ) {
            $this->error( 'invalid_nonce', 'Invalid or missing nonce.', 401 );
            return;
        }
        $tenant_id     = $this->tenant_id();
        $visitor_token = (string) ( $_COOKIE['rims_visitor'] ?? bin2hex( random_bytes( 16 ) ) );
        $unit_id       = (int) ( $_REQUEST['unit_id'] ?? 0 );
        $action        = (string) ( $_REQUEST['op'] ?? 'add' );
        if ( $action === 'remove' ) {
            $ok = $this->c->bookmark_manager()->remove( $tenant_id, $visitor_token, $unit_id );
            $this->envelope( [ 'removed' => $ok ] );
            return;
        }
        $id = $this->c->bookmark_manager()->add( $tenant_id, $visitor_token, $unit_id );
        $this->envelope( [ 'id' => $id ] );
    }
}
