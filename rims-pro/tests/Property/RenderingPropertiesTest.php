<?php
declare(strict_types=1);

namespace RimsPro\Tests\Property;

use PHPUnit\Framework\TestCase;
use RimsPro\Domain\Inventory_Unit;
use RimsPro\Services\Broker_Sheet_Formatter;
use RimsPro\Services\Contact_Link_Builder;
use RimsPro\Services\Field_Visibility_Serializer;
use RimsPro\Services\Featured_Carousel_Service;
use RimsPro\Services\Map_Partition_Service;
use RimsPro\Services\Price_Trend_Selector;
use RimsPro\Services\Table_Sort_Service;
use RimsPro\Domain\UnitStatus;

/**
 * Properties 1, 7, 8, 9, 10, 11, 14, 15, 16, 17 - rendering / formatting / mapping.
 */
final class RenderingPropertiesTest extends TestCase {

    use PropertyTrait;

    public function test_property_1_css_js_prefix_scoping(): void {
        // Feature: resale-inventory-management, Property 1: CSS/JS prefix scoping
        $css = file_get_contents( dirname( __DIR__, 2 ) . '/assets/dist/css/rims-frontend.css' );
        $js  = file_get_contents( dirname( __DIR__, 2 ) . '/assets/dist/js/rims-frontend.js' );
        $this->assertNotFalse( $css );
        $this->assertNotFalse( $js );
        $this->forAll(
            static function () use ( $css ): array {
                preg_match_all( '/\\.([a-zA-Z][a-zA-Z0-9_-]*)/', (string) $css, $m );
                $classes = $m[1] ?? [];
                $sample  = [];
                $count   = max( 1, count( $classes ) );
                for ( $i = 0; $i < 5; $i++ ) {
                    $sample[] = $classes[ mt_rand( 0, $count - 1 ) ] ?? 'rims-card';
                }
                return $sample;
            },
            function ( array $sample ): void {
                foreach ( $sample as $cls ) {
                    if ( $cls === '' || $cls === null ) {
                        continue;
                    }
                    // Allow @keyframes 'rims-' identifiers and 'rims-root', 'rims-internal' etc.
                    $this->assertStringStartsWith( 'rims-', strtolower( $cls ), "CSS class '{$cls}' lacks rims- prefix" );
                }
            }
        );
        // JS surface: scan for window.* identifiers ASSIGNED by the plugin (these are the
        // names this bundle exposes onto the global namespace and that must be rims- prefixed).
        preg_match_all( '/window\.([A-Za-z][A-Za-z0-9_]*)\s*=/', (string) $js, $m );
        $exposed = $m[1] ?? [];
        foreach ( $exposed as $ident ) {
            if ( in_array( $ident, [ 'RIMS_PROJECT', 'RimsProConfig', 'Chart' ], true ) ) {
                continue;
            }
            $this->assertStringStartsWith( 'rims', strtolower( $ident ), "JS identifier '{$ident}' lacks rims- prefix" );
        }
    }

    public function test_property_7_featured_carousel_selection(): void {
        // Feature: resale-inventory-management, Property 7: Featured carousel contains exactly the featured units
        $svc = new Featured_Carousel_Service();
        $this->forAll(
            static fn() => Generators::dataset( mt_rand( 0, 30 ) ),
            function ( array $dataset ) use ( $svc ): void {
                // Randomly mark some featured.
                foreach ( $dataset as $u ) {
                    $u->is_featured = mt_rand( 0, 4 ) === 0;
                }
                $featured = $svc->select( $dataset );
                $expected = array_values( array_filter( $dataset, static fn( Inventory_Unit $u ) => $u->is_featured && ! $u->expired ) );
                $this->assertSameSize( $expected, $featured );
                $eIds = array_map( static fn( $u ) => $u->id, $expected );
                $aIds = array_map( static fn( $u ) => $u->id, $featured );
                sort( $eIds );
                sort( $aIds );
                $this->assertSame( $eIds, $aIds );
            }
        );
    }

    public function test_property_8_field_complete_rendering(): void {
        // Feature: resale-inventory-management, Property 8: Card and row rendering are field-complete
        $serializer = new Field_Visibility_Serializer();
        $this->forAll(
            static fn() => Generators::unit( mt_rand( 0, 1000 ) ),
            function ( Inventory_Unit $u ) use ( $serializer ): void {
                $public = $serializer->serialize( $u, false );
                foreach ( [ 'project_name', 'price_paise', 'area_sqft', 'bhk', 'status', 'floor', 'slug', 'id' ] as $field ) {
                    $this->assertArrayHasKey( $field, $public, "public field {$field} missing" );
                }
                foreach ( Field_Visibility_Serializer::INTERNAL_FIELDS as $f ) {
                    $this->assertArrayNotHasKey( $f, $public, "internal {$f} leaked into public" );
                }
            }
        );
    }

    public function test_property_9_owner_redaction(): void {
        // Feature: resale-inventory-management, Property 9: Owner fields are redacted from non-capability audiences
        $serializer = new Field_Visibility_Serializer();
        $this->forAll(
            static fn() => Generators::unit( mt_rand( 0, 1000 ) ),
            function ( Inventory_Unit $u ) use ( $serializer ): void {
                $public = $serializer->serialize( $u, false );
                $admin  = $serializer->serialize( $u, true );
                $this->assertArrayNotHasKey( 'owner_name', $public );
                $this->assertArrayNotHasKey( 'owner_phone', $public );
                $this->assertArrayHasKey( 'owner_name', $admin );
                $this->assertArrayHasKey( 'owner_phone', $admin );
            }
        );
    }

    public function test_property_10_contact_links(): void {
        // Feature: resale-inventory-management, Property 10: Contact links target the configured number and identify the unit
        $builder = new Contact_Link_Builder();
        $this->forAll(
            static fn() => [ Generators::unit( mt_rand( 0, 1000 ) ), '+91' . str_pad( (string) mt_rand( 7000000000, 9999999999 ), 10, '0', STR_PAD_LEFT ) ],
            function ( array $args ) use ( $builder ): void {
                /** @var Inventory_Unit $u */
                [ $u, $num ] = $args;
                $wa  = $builder->whatsapp( $num, $u );
                $tel = $builder->tel( $num );
                $digits = preg_replace( '/[^0-9]/', '', $num );
                $this->assertStringContainsString( 'wa.me/' . $digits, $wa );
                $this->assertStringContainsString( (string) $u->id, rawurldecode( $wa ) );
                $this->assertStringStartsWith( 'tel:', $tel );
                $this->assertStringContainsString( $digits, $tel );
            }
        );
    }

    public function test_property_11_status_color_total_and_injective(): void {
        // Feature: resale-inventory-management, Property 11: Status-to-color mapping is total and injective
        $map = UnitStatus::default_color_map();
        foreach ( UnitStatus::cases() as $s ) {
            $this->assertArrayHasKey( $s->value, $map, "color missing for {$s->value}" );
            $this->assertNotSame( '', $map[ $s->value ], "color empty for {$s->value}" );
        }
        $this->assertSame( count( $map ), count( array_unique( $map ) ), 'colors not pairwise distinct' );

        $this->forAll(
            static fn() => Generators::pickStatus(),
            function ( string $s ) use ( $map ): void {
                $this->assertArrayHasKey( $s, $map );
            }
        );
    }

    public function test_property_14_column_sort_asc_then_desc(): void {
        // Feature: resale-inventory-management, Property 14: Column sort orders ascending then toggles descending
        $svc = new Table_Sort_Service();
        $this->forAll(
            static fn() => [ Generators::dataset( mt_rand( 1, 30 ) ), Table_Sort_Service::COLUMNS[ mt_rand( 0, count( Table_Sort_Service::COLUMNS ) - 1 ) ] ],
            function ( array $args ) use ( $svc ): void {
                [ $rows, $col ] = $args;
                $asc = $svc->sort( $rows, $col, 'asc' );
                $desc = $svc->sort( $rows, $col, 'desc' );
                $get = function ( Inventory_Unit $u ) use ( $col ) {
                    return match ( $col ) {
                        'project' => strtolower( $u->project_name ),
                        'bhk'     => $u->bhk,
                        'area'    => $u->area_sqft,
                        'floor'   => $u->floor,
                        'price'   => $u->price_paise,
                        'status'  => $u->status,
                        default   => 0,
                    };
                };
                for ( $i = 1; $i < count( $asc ); $i++ ) {
                    $this->assertTrue( $get( $asc[ $i - 1 ] ) <= $get( $asc[ $i ] ), 'ascending invariant violated' );
                }
                for ( $i = 1; $i < count( $desc ); $i++ ) {
                    $this->assertTrue( $get( $desc[ $i - 1 ] ) >= $get( $desc[ $i ] ), 'descending invariant violated' );
                }
            }
        );
    }

    public function test_property_15_broker_sheet_shorthand_and_variants(): void {
        // Feature: resale-inventory-management, Property 15: Broker-sheet shorthand and multi-variant rendering
        $svc = new Broker_Sheet_Formatter();
        $this->forAll(
            static function () {
                $u = Generators::unit( mt_rand( 0, 1000 ) );
                $u->price_label = [ '@ MP', '25:75 P/p', 'U/C', 'CR WITHOUT OC' ][ mt_rand( 0, 3 ) ];
                // Generate 0..2 additional area variants.
                for ( $j = 0; $j < mt_rand( 0, 2 ); $j++ ) {
                    $u->variants[] = [ 'area_sqft' => mt_rand( 800, 7000 ), 'label' => 'Variant ' . $j ];
                }
                return $u;
            },
            function ( Inventory_Unit $u ) use ( $svc ): void {
                $rows = $svc->format( [ $u ] );
                $this->assertCount( 1, $rows );
                $this->assertSame( $u->price_label, $rows[0]['price_label'] );
                $expected_areas = [ $u->area_sqft ];
                foreach ( $u->variants as $v ) {
                    $expected_areas[] = (int) $v['area_sqft'];
                }
                sort( $expected_areas );
                $actual = $rows[0]['areas'];
                sort( $actual );
                $this->assertSame( array_values( array_unique( $expected_areas ) ), array_values( array_unique( $actual ) ) );
            }
        );
    }

    public function test_property_16_map_partition(): void {
        // Feature: resale-inventory-management, Property 16: Map pinning partitions the dataset by coordinate presence
        $svc = new Map_Partition_Service();
        $this->forAll(
            static fn() => Generators::dataset( mt_rand( 0, 30 ) ),
            function ( array $dataset ) use ( $svc ): void {
                $part = $svc->partition( $dataset );
                $this->assertCount(
                    count( $dataset ),
                    array_merge( $part['pinned'], $part['fallback'] )
                );
                foreach ( $part['pinned'] as $u ) {
                    $this->assertNotNull( $u->latitude );
                    $this->assertNotNull( $u->longitude );
                }
                foreach ( $part['fallback'] as $u ) {
                    $this->assertTrue( $u->latitude === null || $u->longitude === null );
                }
                $pIds = array_map( static fn( $u ) => $u->id, $part['pinned'] );
                $fIds = array_map( static fn( $u ) => $u->id, $part['fallback'] );
                $this->assertSame( [], array_intersect( $pIds, $fIds ), 'pinned and fallback overlap' );
            }
        );
    }

    public function test_property_17_price_trend_window(): void {
        // Feature: resale-inventory-management, Property 17: Price-trend window selection returns only in-window points
        $svc = new Price_Trend_Selector();
        $this->forAll(
            static function () {
                $now    = time();
                $points = [];
                for ( $i = 0; $i < mt_rand( 1, 30 ); $i++ ) {
                    $offset_days = mt_rand( 0, 365 * 2 );
                    $points[]    = [
                        'recorded_at' => gmdate( 'Y-m-d H:i:s', $now - $offset_days * 86400 ),
                        'price'       => (float) mt_rand( 1_00_00_000, 200_00_00_000 ),
                    ];
                }
                return [ $points, [ 6, 12 ][ mt_rand( 0, 1 ) ] ];
            },
            function ( array $args ) use ( $svc ): void {
                [ $points, $months ] = $args;
                $now    = time();
                $cutoff = strtotime( "-{$months} months", $now );
                $out    = $svc->select( $points, $months, $now );
                foreach ( $out as $p ) {
                    $ts = strtotime( $p['recorded_at'] );
                    $this->assertTrue( $ts >= $cutoff && $ts <= $now, 'point outside window' );
                }
                foreach ( $points as $p ) {
                    $ts       = strtotime( $p['recorded_at'] );
                    $in_window = $ts >= $cutoff && $ts <= $now;
                    $present  = false;
                    foreach ( $out as $q ) {
                        if ( $q['recorded_at'] === $p['recorded_at'] && (float) $q['price'] === (float) $p['price'] ) {
                            $present = true;
                            break;
                        }
                    }
                    $this->assertSame( $in_window, $present );
                }
            }
        );
    }
}
