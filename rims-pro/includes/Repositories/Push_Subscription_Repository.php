<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Push_Subscription_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'push_subscriptions';
    }

    public function save( int $tenant_id, string $visitor_token, string $endpoint, string $p256dh, string $auth, bool $granted ): int {
        return $this->insert( $tenant_id, [
            'visitor_token' => $visitor_token,
            'endpoint'      => $endpoint,
            'p256dh'        => $p256dh,
            'auth'          => $auth,
            'granted'       => $granted ? 1 : 0,
        ] );
    }

    /** @return array<int, array<string, mixed>> */
    public function forVisitor( int $tenant_id, string $visitor_token ): array {
        return $this->selectAll( $tenant_id, [ 'visitor_token' => $visitor_token ] );
    }
}
