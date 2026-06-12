<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;

/**
 * Sortable columns: project / bhk / area / floor / price / status.
 * First activation = ascending; second = descending (Property 14 / Req 9.2).
 */
final class Table_Sort_Service {

    public const COLUMNS = [ 'project', 'bhk', 'area', 'floor', 'price', 'status' ];

    /**
     * @param Inventory_Unit[] $rows
     * @return Inventory_Unit[]
     */
    public function sort( array $rows, string $column, string $direction ): array {
        if ( ! in_array( $column, self::COLUMNS, true ) ) {
            return $rows;
        }
        $direction = strtolower( $direction ) === 'desc' ? 'desc' : 'asc';
        $cmp       = $this->comparator( $column );
        $copy      = $rows;
        usort( $copy, $cmp );
        if ( $direction === 'desc' ) {
            $copy = array_reverse( $copy );
        }
        return $copy;
    }

    /** Toggle helper: returns the next direction given the current state. */
    public function nextDirection( ?string $current_column, string $next_column, ?string $current_direction ): string {
        if ( $current_column !== $next_column ) {
            return 'asc';
        }
        return $current_direction === 'asc' ? 'desc' : 'asc';
    }

    private function comparator( string $column ): callable {
        return match ( $column ) {
            'project' => static fn( Inventory_Unit $a, Inventory_Unit $b ) => strcasecmp( $a->project_name, $b->project_name ),
            'bhk'     => static fn( Inventory_Unit $a, Inventory_Unit $b ) => $a->bhk <=> $b->bhk,
            'area'    => static fn( Inventory_Unit $a, Inventory_Unit $b ) => $a->area_sqft <=> $b->area_sqft,
            'floor'   => static fn( Inventory_Unit $a, Inventory_Unit $b ) => $a->floor <=> $b->floor,
            'price'   => static fn( Inventory_Unit $a, Inventory_Unit $b ) => $a->price_paise <=> $b->price_paise,
            'status'  => static fn( Inventory_Unit $a, Inventory_Unit $b ) => strcmp( $a->status, $b->status ),
            default   => static fn( Inventory_Unit $a, Inventory_Unit $b ) => 0,
        };
    }
}
