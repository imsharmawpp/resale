<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Price_History_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'price_history';
    }

    /** @return array<int, array{recorded_at:string, price:float}> */
    public function forProject( int $tenant_id, int $project_id ): array {
        $rows = $this->selectAll( $tenant_id, [ 'project_id' => $project_id ], 'ORDER BY recorded_at ASC' );
        $out  = [];
        foreach ( $rows as $r ) {
            $out[] = [ 'recorded_at' => (string) $r['recorded_at'], 'price' => (float) $r['price'] ];
        }
        return $out;
    }

    public function record( int $tenant_id, int $project_id, float $price, string $recorded_at ): int {
        return $this->insert( $tenant_id, [
            'project_id'  => $project_id,
            'price'       => $price,
            'recorded_at' => $recorded_at,
        ] );
    }
}
