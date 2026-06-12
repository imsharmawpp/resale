<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\FilterSet;
use RimsPro\Domain\Inventory_Unit;

/**
 * Pure conjunctive filter over a dataset of Inventory_Unit. Returns
 * {units: list, count: total} so the matching count always equals the
 * filtered set size (Property 4).
 */
final class Filter_Engine {

    /**
     * @param Inventory_Unit[] $dataset
     * @return array{units: Inventory_Unit[], count: int}
     */
    public function apply( FilterSet $f, array $dataset ): array {
        if ( $f->isEmpty() ) {
            // Excludes expired units per Property 3 / Req 13.6 + 29.2.
            $units = array_values( array_filter( $dataset, static fn( Inventory_Unit $u ) => ! $u->expired ) );
            return [ 'units' => $units, 'count' => count( $units ) ];
        }
        $units = [];
        foreach ( $dataset as $u ) {
            if ( $u->expired ) {
                continue;
            }
            if ( ! $this->matches( $f, $u ) ) {
                continue;
            }
            $units[] = $u;
        }
        return [ 'units' => array_values( $units ), 'count' => count( $units ) ];
    }

    private function matches( FilterSet $f, Inventory_Unit $u ): bool {
        if ( $f->project !== null && $this->norm( $u->project_name ) !== $this->norm( $f->project ) ) {
            return false;
        }
        if ( $f->builder !== null && $this->norm( $u->builder ) !== $this->norm( $f->builder ) ) {
            return false;
        }
        if ( $f->location !== null && $this->norm( $u->location ) !== $this->norm( $f->location ) ) {
            return false;
        }
        if ( $f->sector !== null && $this->norm( $u->sector ) !== $this->norm( $f->sector ) ) {
            return false;
        }
        if ( $f->bhk !== null && abs( $u->bhk - $f->bhk ) > 0.001 ) {
            return false;
        }
        if ( $f->areaMin !== null && $u->area_sqft < $f->areaMin ) {
            return false;
        }
        if ( $f->areaMax !== null && $u->area_sqft > $f->areaMax ) {
            return false;
        }
        if ( $f->budgetMinPaise !== null && $u->price_paise < $f->budgetMinPaise ) {
            return false;
        }
        if ( $f->budgetMaxPaise !== null && $u->price_paise > $f->budgetMaxPaise ) {
            return false;
        }
        if ( $f->facing !== null && strtoupper( $u->facing ) !== strtoupper( $f->facing ) ) {
            return false;
        }
        if ( $f->tower !== null && $this->norm( $u->tower ) !== $this->norm( $f->tower ) ) {
            return false;
        }
        if ( $f->floor !== null && $u->floor !== $f->floor ) {
            return false;
        }
        if ( $f->status !== null && $u->status !== $f->status ) {
            return false;
        }
        return true;
    }

    private function norm( string $s ): string {
        return strtolower( trim( $s ) );
    }
}
