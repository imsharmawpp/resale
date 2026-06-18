<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Media_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'media';
    }

    /** @return array<int, array<string, mixed>> */
    public function listForOwner( int $tenant_id, string $owner_type, int $owner_id ): array {
        return $this->selectAll(
            $tenant_id,
            [ 'owner_type' => $owner_type, 'owner_id' => $owner_id ],
            'ORDER BY sort_order ASC, id ASC'
        );
    }

    public function persist( int $tenant_id, array $data ): int {
        return $this->insert( $tenant_id, $data );
    }
}
