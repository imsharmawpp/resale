<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;
use RimsPro\Repositories\Inventory_Repository;
use RimsPro\Tenancy\Tenant_Resolver;

/**
 * Marks units expired when age since `published_at` exceeds the tenant's retention
 * window, excluding them from frontend results, and renews them by restoring
 * pre-expiry status + resetting the timer (Properties 26, 27).
 */
final class Expiry_Manager {

    public function __construct(
        private Inventory_Repository $units,
        private Tenant_Resolver $tenant,
    ) {
    }

    public function isExpired( Inventory_Unit $u, int $retention_days, int $now_ts ): bool {
        if ( ! $u->published_at ) {
            return false;
        }
        $published_ts = strtotime( $u->published_at );
        if ( $published_ts === false ) {
            return false;
        }
        $age_sec = $now_ts - $published_ts;
        return $age_sec > $retention_days * 86400;
    }

    /**
     * Pure version - given a dataset returns the units that should be marked expired.
     *
     * @param Inventory_Unit[] $dataset
     * @return Inventory_Unit[]
     */
    public function expirable( array $dataset, int $retention_days, ?int $now_ts = null ): array {
        $now_ts = $now_ts ?? time();
        $out    = [];
        foreach ( $dataset as $u ) {
            if ( ! $u->expired && $this->isExpired( $u, $retention_days, $now_ts ) ) {
                $out[] = $u;
            }
        }
        return $out;
    }

    /** Run from cron - marks all eligible units expired for the resolved tenant. */
    public function expire_all_due(): int {
        $tenant   = $this->tenant->resolve();
        $branding = $this->tenant->branding( $tenant->id );
        $days     = (int) ( $branding['expiry_days'] ?? 90 );
        $units    = $this->units->listForTenant( $tenant->id, true );
        $count    = 0;
        foreach ( $this->expirable( $units, $days ) as $u ) {
            $this->units->markExpired( $tenant->id, $u->id );
            $count++;
        }
        return $count;
    }

    public function renew( int $tenant_id, int $unit_id, string $previous_status ): bool {
        return $this->units->renew( $tenant_id, $unit_id, $previous_status );
    }
}
