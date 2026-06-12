<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Crm_Config_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'crm_config';
    }

    /** @return array<int, array<string, mixed>> */
    public function listEnabled( int $tenant_id ): array {
        return $this->selectAll( $tenant_id, [ 'enabled' => 1 ] );
    }

    public function findByPlatform( int $tenant_id, string $platform ): ?array {
        return $this->selectOne( $tenant_id, [ 'platform' => $platform ] );
    }

    public function save( int $tenant_id, string $platform, bool $enabled, string $credentials_encrypted, ?array $retry_policy ): int {
        $existing = $this->findByPlatform( $tenant_id, $platform );
        $data     = [
            'platform'              => $platform,
            'enabled'               => $enabled ? 1 : 0,
            'credentials_encrypted' => $credentials_encrypted,
            'retry_policy_json'     => $retry_policy ? wp_json_encode( $retry_policy ) : null,
        ];
        if ( $existing ) {
            $this->update( $tenant_id, (int) $existing['id'], $data );
            return (int) $existing['id'];
        }
        return $this->insert( $tenant_id, $data );
    }
}
