<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;

/**
 * Partitions a dataset into pinned (have lat+lng) and fallback (lack coords).
 * Disjoint and exhaustive (Property 16 / Req 11.4).
 */
final class Map_Partition_Service {

    /**
     * @param Inventory_Unit[] $units
     * @return array{pinned: Inventory_Unit[], fallback: Inventory_Unit[]}
     */
    public function partition( array $units ): array {
        $pinned = [];
        $fall   = [];
        foreach ( $units as $u ) {
            if ( $u->latitude !== null && $u->longitude !== null ) {
                $pinned[] = $u;
            } else {
                $fall[] = $u;
            }
        }
        return [ 'pinned' => $pinned, 'fallback' => $fall ];
    }
}
