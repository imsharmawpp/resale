<?php
declare(strict_types=1);

namespace RimsPro\Security;

use RimsPro\Domain\Validation_Result;

/**
 * Centralized auth guard: nonce/CSRF, capability, REST token, rate-limit.
 * Always fails closed.
 */
final class Authorization_Guard {

    public const ERR_NONCE      = 'invalid_nonce';
    public const ERR_CAPABILITY = 'insufficient_capability';
    public const ERR_TOKEN      = 'invalid_token';
    public const ERR_RATE       = 'rate_limited';

    public function __construct(
        private Rate_Limiter $rate_limiter,
    ) {
    }

    /**
     * @param array{nonce?:string, action?:string, capability?:string, token?:string, valid_tokens?:string[], client_id?:string, rate_limit?:array{max:int,window:int}} $args
     */
    public function authorize( array $args ): Validation_Result {
        // Nonce check (only when action is provided).
        if ( ! empty( $args['action'] ) ) {
            $nonce = $args['nonce'] ?? '';
            if ( ! $this->verifyNonce( $nonce, (string) $args['action'] ) ) {
                return Validation_Result::fail( [ '_auth' => 'Invalid or missing nonce.' ] );
            }
        }

        // Capability check (only when capability requested).
        if ( ! empty( $args['capability'] ) ) {
            if ( ! $this->hasCapability( (string) $args['capability'] ) ) {
                return Validation_Result::fail( [ '_auth' => 'Insufficient capability.' ] );
            }
        }

        // Token check (REST writes).
        if ( isset( $args['valid_tokens'] ) ) {
            $token = (string) ( $args['token'] ?? '' );
            if ( ! $this->verifyToken( $token, (array) $args['valid_tokens'] ) ) {
                return Validation_Result::fail( [ '_auth' => 'Missing or invalid token.' ] );
            }
        }

        // Rate limiter.
        if ( ! empty( $args['client_id'] ) && ! empty( $args['rate_limit'] ) ) {
            $cfg     = $args['rate_limit'];
            $allowed = $this->rate_limiter->allow(
                (string) $args['client_id'],
                (int) $cfg['max'],
                (int) $cfg['window']
            );
            if ( ! $allowed ) {
                return Validation_Result::fail( [ '_auth' => 'Rate limit exceeded.' ] );
            }
        }

        return Validation_Result::ok();
    }

    public function verifyNonce( string $nonce, string $action ): bool {
        if ( $nonce === '' ) {
            return false;
        }
        if ( function_exists( 'wp_verify_nonce' ) ) {
            return (bool) wp_verify_nonce( $nonce, $action );
        }
        // Test fallback: HMAC-style stub
        return hash_equals( hash( 'sha256', $action . '|test-secret' ), $nonce );
    }

    public function hasCapability( string $capability ): bool {
        if ( function_exists( 'current_user_can' ) ) {
            return current_user_can( $capability );
        }
        return false;
    }

    /** @param string[] $valid */
    public function verifyToken( string $token, array $valid ): bool {
        if ( $token === '' ) {
            return false;
        }
        foreach ( $valid as $v ) {
            if ( hash_equals( $v, $token ) ) {
                return true;
            }
        }
        return false;
    }
}
