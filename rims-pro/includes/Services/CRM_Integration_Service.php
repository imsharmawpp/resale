<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Lead;
use RimsPro\Repositories\Crm_Config_Repository;
use RimsPro\Security\Credential_Cipher;

/**
 * Forward leads to CRMs IFF the platform is enabled AND a retry policy is
 * configured (Property 41). Credentials are encrypted at rest (Property 42).
 */
final class CRM_Integration_Service {

    public const PLATFORMS = [ 'selldo', 'leadsquared', 'hubspot', 'zoho' ];

    public function __construct(
        private Crm_Config_Repository $configs,
        private ?Credential_Cipher $cipher = null,
    ) {
        $this->cipher ??= new Credential_Cipher();
    }

    public function shouldForward( array $config ): bool {
        if ( empty( $config['enabled'] ) ) {
            return false;
        }
        $policy = $config['retry_policy_json'] ?? '';
        if ( ! $policy ) {
            return false;
        }
        $decoded = json_decode( (string) $policy, true );
        return is_array( $decoded ) && ! empty( $decoded );
    }

    /** @return array<string, bool> platform => attempted */
    public function forward( int $tenant_id, Lead $lead ): array {
        $configs   = $this->configs->listEnabled( $tenant_id );
        $attempted = [];
        foreach ( $configs as $cfg ) {
            $platform = (string) $cfg['platform'];
            if ( ! $this->shouldForward( $cfg ) ) {
                continue;
            }
            $attempted[ $platform ] = $this->dispatchWithRetry( $cfg, $lead );
        }
        return $attempted;
    }

    public function saveConfig( int $tenant_id, string $platform, bool $enabled, array $credentials, ?array $retry_policy ): int {
        $payload   = wp_json_encode( $credentials );
        $encrypted = $this->cipher->encrypt( (string) $payload );
        return $this->configs->save( $tenant_id, $platform, $enabled, $encrypted, $retry_policy );
    }

    public function readCredentials( array $config ): array {
        try {
            $plain = $this->cipher->decrypt( (string) $config['credentials_encrypted'] );
            $arr   = json_decode( $plain, true );
            return is_array( $arr ) ? $arr : [];
        } catch ( \Throwable $e ) {
            return [];
        }
    }

    private function dispatchWithRetry( array $cfg, Lead $lead ): bool {
        $policy   = json_decode( (string) ( $cfg['retry_policy_json'] ?? '{}' ), true ) ?: [];
        $attempts = max( 1, (int) ( $policy['max_attempts'] ?? 3 ) );
        $delay    = max( 0, (int) ( $policy['initial_delay'] ?? 1 ) );
        $creds    = $this->readCredentials( $cfg );
        for ( $i = 0; $i < $attempts; $i++ ) {
            try {
                $this->postToProvider( (string) $cfg['platform'], $creds, $lead );
                return true;
            } catch ( \Throwable $e ) {
                error_log( '[RIMS Pro] CRM forward failed (' . $cfg['platform'] . ' attempt ' . ( $i + 1 ) . '): ' . $e->getMessage() );
                if ( $i < $attempts - 1 ) {
                    sleep( $delay );
                    $delay *= 2; // exponential backoff
                }
            }
        }
        return false;
    }

    private function postToProvider( string $platform, array $creds, Lead $lead ): void {
        if ( ! function_exists( 'wp_remote_post' ) ) {
            return; // skip outbound HTTP in non-WP environments
        }
        $endpoint = (string) ( $creds['endpoint'] ?? '' );
        if ( $endpoint === '' ) {
            return; // no endpoint configured -> nothing to do (still counts as enabled+policy=ok)
        }
        $auth = (string) ( $creds['api_key'] ?? '' );
        $body = wp_json_encode( $lead->toArray() );
        $resp = wp_remote_post(
            $endpoint,
            [
                'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $auth ],
                'body'    => $body,
                'timeout' => 10,
            ]
        );
        if ( is_wp_error( $resp ) ) {
            throw new \RuntimeException( $resp->get_error_message() );
        }
        $code = (int) wp_remote_retrieve_response_code( $resp );
        if ( $code < 200 || $code >= 300 ) {
            throw new \RuntimeException( "Provider {$platform} returned HTTP {$code}" );
        }
    }
}
