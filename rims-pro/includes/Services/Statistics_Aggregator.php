<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;

/**
 * Pure aggregator computing Available-Inventory / Project / Builder / Resale-Deal
 * counts directly from the dataset (Property 6 / Req 5.3, 5.4).
 */
final class Statistics_Aggregator {

    /**
     * @param Inventory_Unit[] $dataset
     * @return array{available:int, projects:int, builders:int, deals:int}
     */
    public function compute( array $dataset ): array {
        $available = 0;
        $projects  = [];
        $builders  = [];
        $deals     = 0;
        foreach ( $dataset as $u ) {
            if ( $u->expired ) {
                continue;
            }
            if ( $u->status === 'available' ) {
                $available++;
            }
            if ( $u->status === 'sold' || $u->status === 'token_received' || $u->status === 'under_negotiation' ) {
                $deals++;
            }
            if ( $u->project_id > 0 ) {
                $projects[ $u->project_id ] = true;
            } elseif ( $u->project_name !== '' ) {
                $projects[ $u->project_name ] = true;
            }
            if ( $u->builder !== '' ) {
                $builders[ strtolower( $u->builder ) ] = true;
            }
        }
        return [
            'available' => $available,
            'projects'  => count( $projects ),
            'builders'  => count( $builders ),
            'deals'     => $deals,
        ];
    }
}
