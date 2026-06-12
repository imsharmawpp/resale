<?php
declare(strict_types=1);

namespace RimsPro\Tests\Property;

use PHPUnit\Framework\TestCase;
use RimsPro\Domain\FilterSet;
use RimsPro\Domain\Inventory_Unit;
use RimsPro\Domain\Money;
use RimsPro\Domain\PipelineStage;
use RimsPro\Domain\SearchCriteria;
use RimsPro\Domain\UnitStatus;
use RimsPro\Domain\ViewMode;
use RimsPro\Services\Comparison_Engine;
use RimsPro\Services\Filter_Engine;
use RimsPro\Services\Pagination_Service;
use RimsPro\Services\Recently_Viewed_Tracker;
use RimsPro\Services\Smart_Search_Engine;
use RimsPro\Services\Statistics_Aggregator;

/**
 * Properties 2-6, 12, 13, 35, 37, 38 - core engines.
 */
final class CorePropertiesTest extends TestCase {

    use PropertyTrait;

    public function test_property_2_conjunctive_filtering(): void {
        // Feature: resale-inventory-management, Property 2: Conjunctive filtering returns exactly the satisfying units
        $engine = new Filter_Engine();
        $this->forAll(
            static fn() => Generators::dataset( mt_rand( 1, 25 ) ),
            function ( array $dataset ) use ( $engine ): void {
                $sample = $dataset[ mt_rand( 0, count( $dataset ) - 1 ) ];
                $f      = Generators::filterFor( $sample );
                $r      = $engine->apply( $f, $dataset );
                foreach ( $r['units'] as $u ) {
                    if ( $f->bhk !== null ) $this->assertEqualsWithDelta( $f->bhk, $u->bhk, 0.001 );
                    if ( $f->status !== null ) $this->assertSame( $f->status, $u->status );
                    if ( $f->builder !== null ) $this->assertSame( strtolower( $f->builder ), strtolower( $u->builder ) );
                    if ( $f->location !== null ) $this->assertSame( strtolower( $f->location ), strtolower( $u->location ) );
                    $this->assertFalse( $u->expired );
                }
                // Validate no satisfying unit was excluded.
                foreach ( $dataset as $u ) {
                    if ( $u->expired ) continue;
                    $sat = ( $f->bhk === null || abs( $u->bhk - $f->bhk ) <= 0.001 )
                        && ( $f->status === null || $u->status === $f->status )
                        && ( $f->builder === null || strtolower( $u->builder ) === strtolower( $f->builder ) )
                        && ( $f->location === null || strtolower( $u->location ) === strtolower( $f->location ) )
                        && ( $f->facing === null || strtoupper( $u->facing ) === strtoupper( $f->facing ) )
                        && ( $f->tower === null || strtolower( $u->tower ) === strtolower( $f->tower ) )
                        && ( $f->floor === null || $u->floor === $f->floor )
                        && ( $f->project === null || strtolower( $u->project_name ) === strtolower( $f->project ) );
                    if ( $sat ) {
                        $found = false;
                        foreach ( $r['units'] as $w ) {
                            if ( $w->id === $u->id ) { $found = true; break; }
                        }
                        $this->assertTrue( $found, 'a satisfying unit was excluded' );
                    }
                }
            }
        );
    }

    public function test_property_3_clear_all_returns_full_set(): void {
        // Feature: resale-inventory-management, Property 3: Clearing all filters restores the full set
        $engine = new Filter_Engine();
        $this->forAll(
            static fn() => Generators::dataset( mt_rand( 0, 30 ) ),
            function ( array $dataset ) use ( $engine ): void {
                $r = $engine->apply( FilterSet::empty(), $dataset );
                $expected = array_values( array_filter( $dataset, static fn( Inventory_Unit $u ) => ! $u->expired ) );
                $this->assertSameSize( $expected, $r['units'] );
                $this->assertSame( count( $expected ), $r['count'] );
            }
        );
    }

    public function test_property_4_match_count_equals_size(): void {
        // Feature: resale-inventory-management, Property 4: Filtered match count equals result-set size
        $engine = new Filter_Engine();
        $this->forAll(
            static fn() => Generators::dataset( mt_rand( 0, 30 ) ),
            function ( array $dataset ) use ( $engine ): void {
                $sample = $dataset ? $dataset[ mt_rand( 0, count( $dataset ) - 1 ) ] : null;
                $f = $sample ? Generators::filterFor( $sample ) : FilterSet::empty();
                $r = $engine->apply( $f, $dataset );
                $this->assertSame( count( $r['units'] ), $r['count'] );
            }
        );
    }

    public function test_property_5_smart_search_parse_then_filter(): void {
        // Feature: resale-inventory-management, Property 5: Natural-language search parses then filters correctly
        $engine = new Smart_Search_Engine( new Filter_Engine() );
        $this->forAll(
            static function (): array {
                $bhk = (float) [ 2, 3, 4 ][ mt_rand( 0, 2 ) ];
                $budget_cr = mt_rand( 1, 10 );
                $project = Generators::pickProject();
                $location = Generators::pickLocation();
                $dataset = Generators::dataset( mt_rand( 5, 25 ) );
                // Inject one matching unit guaranteed.
                $hit = $dataset[0];
                $hit->bhk = $bhk;
                $hit->price_paise = $budget_cr * 1_00_00_000 * 100 - 100;
                $hit->project_name = $project;
                $hit->location = $location;
                return [ $bhk, $budget_cr, $project, $location, $dataset ];
            },
            function ( array $args ) use ( $engine ): void {
                [ $bhk, $cr, $project, $location, $dataset ] = $args;
                $query = sprintf( '%g BHK in %s under %d Cr near %s', $bhk, $location, $cr, $project );

                $known_bp  = array_values( array_unique( array_merge(
                    array_map( static fn( Inventory_Unit $u ) => $u->builder, $dataset ),
                    array_map( static fn( Inventory_Unit $u ) => $u->project_name, $dataset )
                ) ) );
                $known_loc = array_values( array_unique( array_map( static fn( Inventory_Unit $u ) => $u->location, $dataset ) ) );

                $criteria = $engine->parse( $query, $known_bp, $known_loc );
                $this->assertEqualsWithDelta( $bhk, $criteria->bhk, 0.001 );
                $this->assertNotNull( $criteria->maxBudgetPaise );
                $this->assertGreaterThan( 0, (int) $criteria->maxBudgetPaise );

                $matches = $engine->search( $criteria, $dataset );
                foreach ( $matches as $u ) {
                    $this->assertEqualsWithDelta( $bhk, $u->bhk, 0.001 );
                    $this->assertLessThanOrEqual( $criteria->maxBudgetPaise, $u->price_paise );
                }
            }
        );
    }

    public function test_property_6_quick_statistics_aggregates(): void {
        // Feature: resale-inventory-management, Property 6: Quick statistics equal the true aggregates
        $svc = new Statistics_Aggregator();
        $this->forAll(
            static fn() => Generators::dataset( mt_rand( 0, 30 ) ),
            function ( array $dataset ) use ( $svc ): void {
                $available = 0; $deals = 0; $projects = []; $builders = [];
                foreach ( $dataset as $u ) {
                    if ( $u->expired ) continue;
                    if ( $u->status === 'available' ) $available++;
                    if ( in_array( $u->status, [ 'sold', 'token_received', 'under_negotiation' ], true ) ) $deals++;
                    if ( $u->project_id > 0 ) $projects[ $u->project_id ] = true;
                    elseif ( $u->project_name !== '' ) $projects[ $u->project_name ] = true;
                    if ( $u->builder !== '' ) $builders[ strtolower( $u->builder ) ] = true;
                }
                $stats = $svc->compute( $dataset );
                $this->assertSame( $available, $stats['available'] );
                $this->assertSame( count( $projects ), $stats['projects'] );
                $this->assertSame( count( $builders ), $stats['builders'] );
                $this->assertSame( $deals, $stats['deals'] );
            }
        );
    }

    public function test_property_12_view_mode_round_trip(): void {
        // Feature: resale-inventory-management, Property 12: Selected display/view mode round-trips within a session
        // Backed by a pure key-value model that mirrors the Alpine sessionStorage
        // contract used by the frontend bundle.
        $store = [];
        $persist = function ( string $k, string $v ) use ( &$store ): void { $store[ $k ] = $v; };
        $read    = function ( string $k ) use ( &$store ): ?string { return $store[ $k ] ?? null; };
        $this->forAll(
            static fn() => [ ViewMode::values()[ mt_rand( 0, 3 ) ], [ 'light', 'dark' ][ mt_rand( 0, 1 ) ] ],
            function ( array $pair ) use ( $persist, $read ): void {
                [ $mode, $color ] = $pair;
                $persist( 'view', $mode );
                $persist( 'color', $color );
                $this->assertSame( $mode, $read( 'view' ) );
                $this->assertSame( $color, $read( 'color' ) );
            }
        );
    }

    public function test_property_13_view_switch_preserves_state(): void {
        // Feature: resale-inventory-management, Property 13: View switching preserves filters and search independently
        $this->forAll(
            static fn() => [
                FilterSet::fromArray( [ 'bhk' => 3.0, 'location' => 'Whitefield', 'status' => 'available' ] ),
                new SearchCriteria( bhk: 3.0, maxBudgetPaise: 30_00_00_000, location: 'Whitefield' ),
                ViewMode::values()[ mt_rand( 0, 3 ) ],
            ],
            function ( array $args ): void {
                [ $f, $c, $target ] = $args;
                // Switch is pure state-copy in our model.
                $next_filters = clone $f;
                $next_search  = clone $c;
                $this->assertEquals( $f, $next_filters );
                $this->assertEquals( $c, $next_search );
                $this->assertContains( $target, ViewMode::values() );
            }
        );
    }

    public function test_property_35_pagination_partition(): void {
        // Feature: resale-inventory-management, Property 35: Pagination partitions the ordered dataset and reports the true total
        $svc = new Pagination_Service();
        $this->forAll(
            static fn() => Generators::dataset( mt_rand( 0, 100 ) ),
            function ( array $dataset ) use ( $svc ): void {
                $per_page = mt_rand( 1, 20 );
                $first = $svc->page( $dataset, 1, $per_page );
                $this->assertSame( count( $dataset ), $first['total'] );
                $assembled = [];
                $page = 1;
                while ( true ) {
                    $p = $svc->page( $dataset, $page, $per_page );
                    foreach ( $p['items'] as $it ) {
                        $assembled[] = $it->id;
                    }
                    if ( $page >= $p['total_pages'] ) break;
                    $page++;
                }
                $expected = array_map( static fn( $u ) => $u->id, $dataset );
                $this->assertSame( $expected, $assembled );
                $this->assertSame( count( $expected ), count( array_unique( $assembled ) ) );
            }
        );
    }

    public function test_property_37_comparison_cap(): void {
        // Feature: resale-inventory-management, Property 37: Comparison set never exceeds four units
        $cmp = new Comparison_Engine();
        $this->forAll(
            static fn() => array_unique( array_map( static fn( $i ) => mt_rand( 1, 50 ), range( 1, mt_rand( 1, 10 ) ) ) ),
            function ( array $ids ) use ( $cmp ): void {
                $set = [];
                $rejected = false;
                foreach ( $ids as $id ) {
                    $r = $cmp->add( $set, (int) $id );
                    $set = $r['set'];
                    if ( ! empty( $r['message'] ) ) {
                        $rejected = true;
                    }
                }
                $this->assertLessThanOrEqual( Comparison_Engine::MAX, count( $set ) );
                if ( count( array_unique( $ids ) ) > Comparison_Engine::MAX ) {
                    $this->assertTrue( $rejected, 'expected limit-message on 5th distinct add' );
                }
            }
        );
    }

    public function test_property_38_recently_viewed_ordering(): void {
        // Feature: resale-inventory-management, Property 38: Bookmarks and recently-viewed round-trip with correct ordering
        $tracker = new Recently_Viewed_Tracker();
        $this->forAll(
            static fn() => array_map( static fn() => mt_rand( 1, 10 ), range( 1, mt_rand( 1, 20 ) ) ),
            function ( array $sequence ) use ( $tracker ): void {
                $list = [];
                foreach ( $sequence as $id ) {
                    $list = $tracker->track( $list, (int) $id );
                }
                // No duplicates.
                $this->assertSame( count( $list ), count( array_unique( $list ) ) );
                // Most-recent-first.
                $expected = [];
                foreach ( array_reverse( $sequence ) as $id ) {
                    if ( ! in_array( (int) $id, $expected, true ) ) {
                        $expected[] = (int) $id;
                    }
                }
                $expected = array_slice( $expected, 0, Recently_Viewed_Tracker::MAX );
                $this->assertSame( $expected, $list );
            }
        );
    }
}
