<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Repositories\Bookmark_Repository;

final class Bookmark_Manager {

    public function __construct(
        private Bookmark_Repository $repo,
    ) {
    }

    public function add( int $tenant_id, string $visitor_token, int $unit_id ): int {
        return $this->repo->add( $tenant_id, $visitor_token, $unit_id );
    }

    /** @return int[] */
    public function listForVisitor( int $tenant_id, string $visitor_token ): array {
        return $this->repo->listUnitIds( $tenant_id, $visitor_token );
    }

    public function remove( int $tenant_id, string $visitor_token, int $unit_id ): bool {
        return $this->repo->remove( $tenant_id, $visitor_token, $unit_id );
    }
}
