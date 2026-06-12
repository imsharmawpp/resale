<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Lead_History_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'lead_history';
    }

    protected function tenant_scoped(): bool {
        return false;
    }

    public function record( int $lead_id, ?int $actor_id, ?string $from_stage, string $to_stage, ?string $note = null ): int {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return 0;
        }
        $wpdb->insert(
            $this->table(),
            [
                'lead_id'    => $lead_id,
                'actor_id'   => $actor_id,
                'from_stage' => $from_stage,
                'to_stage'   => $to_stage,
                'note'       => $note,
            ]
        );
        return (int) $wpdb->insert_id;
    }

    /** @return array<int, array<string, mixed>> */
    public function forLead( int $lead_id ): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE lead_id = %d ORDER BY id ASC", $lead_id ),
            ARRAY_A
        );
        return is_array( $rows ) ? $rows : [];
    }
}
