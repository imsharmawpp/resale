<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\FilterSet;
use RimsPro\Domain\Inventory_Unit;
use RimsPro\Domain\Money;
use RimsPro\Domain\SearchCriteria;

/**
 * Natural-language search: extracts BHK, max budget, builder/project, location.
 * Pure parser + filter, conjunctive matching (Property 5).
 */
final class Smart_Search_Engine {

    public function __construct(
        private Filter_Engine $filters,
    ) {
    }

    public function parse( string $query, array $known_builders_or_projects = [], array $known_locations = [] ): SearchCriteria {
        $q = trim( $query );
        if ( $q === '' ) {
            return new SearchCriteria();
        }
        $bhk      = null;
        $budget   = null;
        $bp       = null;
        $location = null;

        // BHK: "3 BHK", "3bhk", "3.5 BHK"
        if ( preg_match( '/(\d+(?:\.\d+)?)\s*bhk/i', $q, $m ) ) {
            $bhk = (float) $m[1];
        }

        // Budget: "under 1.5 cr", "below 75 lac", "5 cr"
        if ( preg_match( '/(?:under|below|upto|up\s*to|max|within|<)\s*([0-9]+(?:\.[0-9]+)?\s*(?:cr|crore|crores|l|lac|lakh|lakhs|k|thousand)?)/i', $q, $m ) ) {
            $money = Money::parse( $m[1] );
            if ( $money !== null ) {
                $budget = $money->amount;
            }
        } elseif ( preg_match( '/([0-9]+(?:\.[0-9]+)?\s*(?:cr|crore|crores|l|lac|lakh|lakhs))/i', $q, $m ) ) {
            $money = Money::parse( $m[1] );
            if ( $money !== null ) {
                $budget = $money->amount;
            }
        }

        $needle = strtolower( $q );

        // Builder/Project — match against known list (case-insensitive whole phrase)
        foreach ( $known_builders_or_projects as $name ) {
            if ( $name === '' ) {
                continue;
            }
            if ( str_contains( $needle, strtolower( $name ) ) ) {
                $bp = $name;
                break;
            }
        }

        // Location — match against known list
        foreach ( $known_locations as $name ) {
            if ( $name === '' ) {
                continue;
            }
            if ( str_contains( $needle, strtolower( $name ) ) ) {
                $location = $name;
                break;
            }
        }

        return new SearchCriteria(
            bhk: $bhk,
            maxBudgetPaise: $budget,
            builderOrProject: $bp,
            location: $location,
            rawTerms: array_values( array_filter( preg_split( '/\s+/', $q ) ?: [] ) ),
        );
    }

    /**
     * @param Inventory_Unit[] $dataset
     * @return Inventory_Unit[]
     */
    public function search( SearchCriteria $c, array $dataset ): array {
        if ( $c->isEmpty() ) {
            return $dataset;
        }
        $result = [];
        foreach ( $dataset as $u ) {
            if ( $u->expired ) {
                continue;
            }
            if ( $c->bhk !== null && abs( $u->bhk - $c->bhk ) > 0.001 ) {
                continue;
            }
            if ( $c->maxBudgetPaise !== null && $u->price_paise > $c->maxBudgetPaise ) {
                continue;
            }
            if ( $c->builderOrProject !== null ) {
                $needle = strtolower( $c->builderOrProject );
                $hay    = strtolower( $u->builder . ' ' . $u->project_name );
                if ( ! str_contains( $hay, $needle ) ) {
                    continue;
                }
            }
            if ( $c->location !== null && stripos( $u->location, $c->location ) === false ) {
                continue;
            }
            $result[] = $u;
        }
        return $result;
    }
}
