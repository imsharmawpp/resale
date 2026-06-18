<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Nearby_Place_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'nearby_places';
    }

    protected function tenant_scoped(): bool {
        return false;
    }

    /** @return array<int, array<string, mixed>> */
    public function forProject( int $project_id ): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE project_id = %d ORDER BY category, distance_km ASC", $project_id ),
            ARRAY_A
        );
        return is_array( $rows ) ? $rows : [];
    }

    public function add( int $project_id, string $category, string $name, float $distance_km ): int {
        global $wpdb;
        $wpdb->insert( $this->table(), [
            'project_id' => $project_id,
            'category'   => $category,
            'name'       => $name,
            'distance_km' => $distance_km,
        ] );
        return (int) $wpdb->insert_id;
    }
}
