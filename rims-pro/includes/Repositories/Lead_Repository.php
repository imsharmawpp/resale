<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

use RimsPro\Domain\Lead;

class Lead_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'leads';
    }

    /** @return Lead[] */
    public function listForTenant( int $tenant_id ): array {
        return array_map(
            static fn( array $r ) => Lead::fromArray( $r ),
            $this->selectAll( $tenant_id, [], 'ORDER BY id DESC' )
        );
    }

    public function find( int $tenant_id, int $id ): ?Lead {
        $row = $this->selectOne( $tenant_id, [ 'id' => $id ] );
        return $row ? Lead::fromArray( $row ) : null;
    }

    public function persist( int $tenant_id, Lead $l ): int {
        $data = [
            'name'            => $l->name,
            'mobile'          => $l->mobile,
            'email'           => $l->email,
            'requirements'    => $l->requirements,
            'source'          => $l->source,
            'unit_id'         => $l->unit_id,
            'pipeline_stage'  => $l->pipeline_stage ?: 'new',
            'crm_sync_status' => $l->crm_sync_status ?: 'pending',
        ];
        if ( $l->id > 0 ) {
            $this->update( $tenant_id, $l->id, $data );
            return $l->id;
        }
        return $this->insert( $tenant_id, $data );
    }

    public function setStage( int $tenant_id, int $lead_id, string $stage ): bool {
        return $this->update( $tenant_id, $lead_id, [ 'pipeline_stage' => $stage ] );
    }

    public function setCrmSyncStatus( int $tenant_id, int $lead_id, string $status ): bool {
        return $this->update( $tenant_id, $lead_id, [ 'crm_sync_status' => $status ] );
    }

    /** @return array<string,int> */
    public function countsByStage( int $tenant_id ): array {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT pipeline_stage, COUNT(*) AS c FROM {$this->table()} WHERE tenant_id = %d GROUP BY pipeline_stage",
            $tenant_id
        );
        $rows = $wpdb->get_results( $sql, ARRAY_A );
        $out  = [];
        foreach ( $rows ?: [] as $row ) {
            $out[ (string) $row['pipeline_stage'] ] = (int) $row['c'];
        }
        return $out;
    }
}
