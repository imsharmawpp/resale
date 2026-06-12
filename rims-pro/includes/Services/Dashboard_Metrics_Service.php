<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;
use RimsPro\Domain\Lead;

/**
 * Pure dashboard metrics: revenue, in-range inventory + lead counts, conversion
 * (Property 21 / Req 17.4, 17.5).
 */
final class Dashboard_Metrics_Service {

    /**
     * @param Inventory_Unit[] $units
     * @param Lead[]           $leads
     * @return array{revenue_paise:int, inventory_count:int, lead_count:int, conversion:float}
     */
    public function compute( array $units, array $leads, int $start_ts, int $end_ts ): array {
        $revenue   = 0;
        $inv_count = 0;
        foreach ( $units as $u ) {
            $created = strtotime( $u->created_at );
            if ( $created !== false && $created >= $start_ts && $created <= $end_ts ) {
                $inv_count++;
            }
            if ( $u->status === 'sold' ) {
                $changed = strtotime( $u->status_changed_at ?? '' );
                if ( $changed !== false && $changed >= $start_ts && $changed <= $end_ts ) {
                    $revenue += $u->price_paise;
                }
            }
        }
        $lead_count = 0;
        $closed     = 0;
        foreach ( $leads as $l ) {
            $created = strtotime( $l->created_at ?: '' );
            if ( $created === false || $created < $start_ts || $created > $end_ts ) {
                continue;
            }
            $lead_count++;
            if ( $l->pipeline_stage === 'closed' ) {
                $closed++;
            }
        }
        $conversion = $lead_count > 0 ? $closed / $lead_count : 0.0;
        return [
            'revenue_paise'   => (int) $revenue,
            'inventory_count' => $inv_count,
            'lead_count'      => $lead_count,
            'conversion'      => $conversion,
        ];
    }
}
