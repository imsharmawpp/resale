<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Lead;
use RimsPro\Domain\PipelineStage;
use RimsPro\Repositories\Lead_History_Repository;
use RimsPro\Repositories\Lead_Repository;

/**
 * Pipeline moves + per-column counts (Properties 19, 20 / Req 16.3, 16.6).
 */
final class Lead_Management {

    public function __construct(
        private Lead_Repository $leads,
        private Lead_History_Repository $history,
    ) {
    }

    public function move( int $tenant_id, int $lead_id, string $target_stage, ?int $actor_id = null, ?string $note = null ): bool {
        if ( ! in_array( $target_stage, PipelineStage::values(), true ) ) {
            return false;
        }
        $lead = $this->leads->find( $tenant_id, $lead_id );
        if ( ! $lead ) {
            return false;
        }
        $from = $lead->pipeline_stage;
        $ok   = $this->leads->setStage( $tenant_id, $lead_id, $target_stage );
        if ( $ok ) {
            $this->history->record( $lead_id, $actor_id, $from, $target_stage, $note );
        }
        return $ok;
    }

    /**
     * Compute per-column counts from a list of leads.
     *
     * @param Lead[] $leads
     * @return array<string,int>
     */
    public function countsByStage( array $leads ): array {
        $out = [];
        foreach ( PipelineStage::values() as $s ) {
            $out[ $s ] = 0;
        }
        foreach ( $leads as $l ) {
            if ( isset( $out[ $l->pipeline_stage ] ) ) {
                $out[ $l->pipeline_stage ]++;
            }
        }
        return $out;
    }
}
