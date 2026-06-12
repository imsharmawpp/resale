<?php
declare(strict_types=1);

namespace RimsPro\Rest;

use RimsPro\Core\Container;
use RimsPro\Domain\Validation_Result;

abstract class Rest_Controller_Base {

    public function __construct( protected Container $c ) {}

    protected function tenant_id(): int {
        return (int) $this->c->tenant_resolver()->resolve()->id;
    }

    protected function envelope( mixed $data, array $meta = [] ): \WP_REST_Response {
        return new \WP_REST_Response( [ 'data' => $data, 'meta' => $meta ], 200 );
    }

    protected function error( string $code, string $message, int $status = 400, array $fields = [] ): \WP_REST_Response {
        $payload = [ 'error' => [ 'code' => $code, 'message' => $message ] ];
        if ( $fields ) {
            $payload['error']['fields'] = $fields;
        }
        return new \WP_REST_Response( $payload, $status );
    }

    protected function fromValidation( Validation_Result $r, int $status = 422 ): \WP_REST_Response {
        return $this->error( 'validation_failed', $r->firstError() ?? 'Validation failed.', $status, $r->errors() );
    }

    protected function clientId( \WP_REST_Request $request ): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = (string) $request->get_header( 'User-Agent' );
        return hash( 'sha256', $ip . '|' . $ua );
    }

    protected function rateLimitArgs(): array {
        $branding = $this->c->tenant_resolver()->branding( $this->tenant_id() );
        return [
            'max'    => (int) ( $branding['rate_limit_max'] ?? 60 ),
            'window' => (int) ( $branding['rate_limit_window'] ?? 60 ),
        ];
    }

    protected function authorizeWrite( \WP_REST_Request $request, ?string $capability = null ): ?\WP_REST_Response {
        $token = (string) $request->get_header( 'X-Rims-Token' );
        $valid = (array) ( get_option( 'rims_pro_api_tokens', [] ) ?: [] );

        $args = [
            'client_id'  => $this->clientId( $request ),
            'rate_limit' => $this->rateLimitArgs(),
        ];
        // Tokens are required on writes (Req 31.3)
        $args['valid_tokens'] = $valid;
        $args['token']        = $token;

        if ( $capability ) {
            $args['capability'] = $capability;
        }
        $r = $this->c->authorization_guard()->authorize( $args );
        if ( ! $r->isOk() ) {
            return $this->error( 'unauthorized', $r->firstError() ?? 'Unauthorized', 401 );
        }
        return null;
    }
}
