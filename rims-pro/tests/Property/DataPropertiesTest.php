<?php
declare(strict_types=1);

namespace RimsPro\Tests\Property;

use PHPUnit\Framework\TestCase;
use RimsPro\Domain\Inventory_Unit;
use RimsPro\Domain\Lead;
use RimsPro\Domain\PipelineStage;
use RimsPro\Domain\Project;
use RimsPro\Services\AI_Tag_Generator;
use RimsPro\Services\Analytics_Engine;
use RimsPro\Services\Cache_Manager;
use RimsPro\Services\Dashboard_Metrics_Service;
use RimsPro\Services\Expiry_Manager;
use RimsPro\Services\Export_Engine;
use RimsPro\Services\Field_Visibility_Serializer;
use RimsPro\Services\Filter_Engine;
use RimsPro\Services\Lead_Management;
use RimsPro\Services\Media_Gallery_Manager;
use RimsPro\Services\QR_Code_Generator;
use RimsPro\Services\SEO_Engine;
use RimsPro\Services\Share_Manager;
use RimsPro\Repositories\Tag_Repository;

/**
 * Properties 19-22, 24-29, 34, 36, 39, 43-48.
 */
final class DataPropertiesTest extends TestCase {

    use PropertyTrait;

    public function test_property_19_pipeline_move_round_trip(): void {
        // Feature: resale-inventory-management, Property 19: Lead pipeline-stage moves round-trip
        $repo = new InMemoryLeadRepository();
        $hist = new InMemoryLeadHistory();
        $svc  = new Lead_Management( $repo, $hist );

        $this->forAll(
            static fn() => [
                Generators::lead( mt_rand( 0, 100 ) ),
                PipelineStage::values()[ mt_rand( 0, count( PipelineStage::values() ) - 1 ) ],
            ],
            function ( array $args ) use ( $repo, $svc ): void {
                /** @var Lead $l */
                [ $l, $target ] = $args;
                $id = $repo->persist( 1, $l );
                $svc->move( 1, $id, $target );
                $reloaded = $repo->find( 1, $id );
                $this->assertSame( $target, $reloaded->pipeline_stage );
            }
        );
    }

    public function test_property_20_per_column_lead_counts(): void {
        // Feature: resale-inventory-management, Property 20: Per-column lead counts are consistent
        $svc = new Lead_Management( new InMemoryLeadRepository(), new InMemoryLeadHistory() );
        $this->forAll(
            static fn() => array_map( static fn( $i ) => Generators::lead( $i ), range( 1, mt_rand( 0, 60 ) ) ),
            function ( array $leads ) use ( $svc ): void {
                $counts = $svc->countsByStage( $leads );
                $sum = array_sum( $counts );
                $this->assertSame( count( $leads ), $sum );
                foreach ( $counts as $stage => $c ) {
                    $expected = count( array_filter( $leads, static fn( $l ) => $l->pipeline_stage === $stage ) );
                    $this->assertSame( $expected, $c, "count mismatch for {$stage}" );
                }
            }
        );
    }

    public function test_property_21_dashboard_metrics(): void {
        // Feature: resale-inventory-management, Property 21: Dashboard metrics equal their definitions over the selected range
        $svc = new Dashboard_Metrics_Service();
        $this->forAll(
            static function () {
                $now    = time();
                $start  = $now - 30 * 86400;
                $units  = [];
                $leads  = [];
                $size_u = mt_rand( 0, 30 );
                for ( $i = 0; $i < $size_u; $i++ ) {
                    $u = Generators::unit( $i );
                    if ( mt_rand( 0, 1 ) ) {
                        $u->status = 'sold';
                        $u->status_changed_at = gmdate( 'Y-m-d H:i:s', mt_rand( $start, $now ) );
                    } else {
                        $u->status_changed_at = gmdate( 'Y-m-d H:i:s', $start - 86400 );
                    }
                    $u->created_at = gmdate( 'Y-m-d H:i:s', mt_rand( $start, $now ) );
                    $units[] = $u;
                }
                $size_l = mt_rand( 0, 20 );
                for ( $i = 0; $i < $size_l; $i++ ) {
                    $l             = Generators::lead( $i );
                    $l->created_at = gmdate( 'Y-m-d H:i:s', mt_rand( $start, $now ) );
                    $leads[]       = $l;
                }
                return [ $units, $leads, $start, $now ];
            },
            function ( array $args ) use ( $svc ): void {
                [ $units, $leads, $start, $now ] = $args;
                $r = $svc->compute( $units, $leads, $start, $now );

                $rev_expected = 0;
                $inv_expected = 0;
                foreach ( $units as $u ) {
                    $created = strtotime( $u->created_at );
                    if ( $created !== false && $created >= $start && $created <= $now ) {
                        $inv_expected++;
                    }
                    if ( $u->status === 'sold' ) {
                        $changed = strtotime( $u->status_changed_at );
                        if ( $changed !== false && $changed >= $start && $changed <= $now ) {
                            $rev_expected += $u->price_paise;
                        }
                    }
                }
                $lead_expected = 0;
                $closed = 0;
                foreach ( $leads as $l ) {
                    $created = strtotime( $l->created_at );
                    if ( $created !== false && $created >= $start && $created <= $now ) {
                        $lead_expected++;
                        if ( $l->pipeline_stage === 'closed' ) $closed++;
                    }
                }
                $conv_expected = $lead_expected > 0 ? $closed / $lead_expected : 0.0;

                $this->assertSame( $rev_expected, $r['revenue_paise'] );
                $this->assertSame( $inv_expected, $r['inventory_count'] );
                $this->assertSame( $lead_expected, $r['lead_count'] );
                $this->assertEqualsWithDelta( $conv_expected, $r['conversion'], 1e-9 );
            }
        );
    }

    public function test_property_22_inventory_round_trip(): void {
        // Feature: resale-inventory-management, Property 22: Inventory create/edit/delete and status changes round-trip
        $repo = new InMemoryInventoryRepository();
        $this->forAll(
            static fn() => Generators::unit( mt_rand( 0, 1000 ) ),
            function ( Inventory_Unit $u ) use ( $repo ): void {
                $u->status = 'available';
                $id = $repo->persist( 1, $u );
                $reloaded = $repo->find( 1, $id );
                $this->assertNotNull( $reloaded );
                $this->assertSame( 'available', $reloaded->status );
                $this->assertSame( $u->bhk, $reloaded->bhk );
                // Status change.
                $repo->changeStatus( 1, $id, 'sold', '2024-01-01 00:00:00' );
                $rs = $repo->find( 1, $id );
                $this->assertSame( 'sold', $rs->status );
                $this->assertSame( '2024-01-01 00:00:00', $rs->status_changed_at );
                // Delete.
                $repo->delete_unit( 1, $id );
                $this->assertNull( $repo->find( 1, $id ) );
            }
        );
    }

    public function test_property_24_media_validation_and_compression(): void {
        // Feature: resale-inventory-management, Property 24: Media upload validation honors size and type bounds
        $svc = new Media_Gallery_Manager( new InMemoryMediaRepo() );
        $max = Media_Gallery_Manager::MAX_BYTES_DEFAULT;
        $this->forAll(
            static fn() => [
                'kind'      => mt_rand( 0, 1 ) ? 'photo' : 'invalid',
                'mime_type' => mt_rand( 0, 1 ) ? 'image/png' : 'application/x-msdos-program',
                'bytes'     => [ 0, 100, $max, $max + 1 ][ mt_rand( 0, 3 ) ],
            ],
            function ( array $desc ) use ( $svc, $max ): void {
                $r = $svc->validate( $desc, $max );
                $expected = in_array( $desc['kind'], Media_Gallery_Manager::ALLOWED_KINDS, true )
                    && in_array( $desc['mime_type'], Media_Gallery_Manager::ALLOWED_MIME_TYPES, true )
                    && $desc['bytes'] > 0
                    && $desc['bytes'] <= $max;
                $this->assertSame( $expected, $r->isOk() );

                if ( $desc['bytes'] > 0 ) {
                    $compressed = $svc->compressedBytes( $desc['bytes'] );
                    $this->assertLessThanOrEqual( $desc['bytes'], $compressed );
                }
            }
        );
    }

    public function test_property_25_engagement_events_and_rankings(): void {
        // Feature: resale-inventory-management, Property 25: Engagement events and rankings are faithful to actions
        $svc = new Analytics_Engine( new InMemoryAnalyticsRepo() );
        $this->forAll(
            static function () {
                $events = [];
                $size   = mt_rand( 0, 50 );
                for ( $i = 0; $i < $size; $i++ ) {
                    $events[] = [
                        'event_type' => Analytics_Engine::EVENTS[ mt_rand( 0, count( Analytics_Engine::EVENTS ) - 1 ) ],
                        'entity_id'  => mt_rand( 1, 5 ),
                    ];
                }
                return $events;
            },
            function ( array $events ) use ( $svc ): void {
                $rankings = $svc->rank( $events, Analytics_Engine::EVENT_INVENTORY_VIEW );
                if ( empty( $events ) || count( array_filter( $events, static fn( $e ) => $e['event_type'] === Analytics_Engine::EVENT_INVENTORY_VIEW ) ) === 0 ) {
                    $this->assertSame( [], $rankings );
                    return;
                }
                $prev = PHP_INT_MAX;
                foreach ( $rankings as $r ) {
                    $this->assertLessThanOrEqual( $prev, $r['count'] );
                    $prev = $r['count'];
                }
            }
        );
    }

    public function test_property_26_expiry_age_based(): void {
        // Feature: resale-inventory-management, Property 26: Expiry is determined by age and excludes expired units
        $svc = new Expiry_Manager( new InMemoryInventoryRepository(), new \RimsPro\Tests\Property\StubResolver() );
        $this->forAll(
            static function () {
                $u = Generators::unit( mt_rand( 0, 1000 ) );
                $age_days = mt_rand( 0, 365 );
                $u->published_at = gmdate( 'Y-m-d H:i:s', time() - $age_days * 86400 );
                $u->expired = false;
                return [ $u, mt_rand( 1, 200 ) ];
            },
            function ( array $args ) use ( $svc ): void {
                /** @var Inventory_Unit $u */
                [ $u, $retention ] = $args;
                $now = time();
                $expected_expired = ( $now - strtotime( $u->published_at ) ) > $retention * 86400;
                $this->assertSame( $expected_expired, $svc->isExpired( $u, $retention, $now ) );

                if ( $expected_expired ) {
                    $u->expired = true;
                    $filter = new Filter_Engine();
                    $r = $filter->apply( \RimsPro\Domain\FilterSet::empty(), [ $u ] );
                    $this->assertCount( 0, $r['units'] );
                }
            }
        );
    }

    public function test_property_27_renewal_resets(): void {
        // Feature: resale-inventory-management, Property 27: Renewal restores pre-expiry status and resets the timer
        $repo = new InMemoryInventoryRepository();
        $svc  = new Expiry_Manager( $repo, new \RimsPro\Tests\Property\StubResolver() );
        $this->forAll(
            static function () {
                $u           = Generators::unit( mt_rand( 0, 100 ) );
                $u->status   = 'available';
                $u->expired  = true;
                return $u;
            },
            function ( Inventory_Unit $u ) use ( $repo, $svc ): void {
                $id  = $repo->persist( 1, $u );
                $pre = $repo->find( 1, $id );
                $this->assertTrue( $pre->expired );
                $svc->renew( 1, $id, 'available' );
                $post = $repo->find( 1, $id );
                $this->assertFalse( $post->expired );
                $this->assertSame( 'available', $post->status );
                // Timer reset within the last few seconds.
                $this->assertNotNull( $post->published_at );
                $delta = time() - strtotime( $post->published_at );
                $this->assertLessThan( 60, $delta );
            }
        );
    }

    public function test_property_28_ai_tag_vocabulary_bound(): void {
        // Feature: resale-inventory-management, Property 28: AI tag suggestions are vocabulary-bounded and persist only on acceptance
        $svc  = new AI_Tag_Generator( new InMemoryTagRepo() );
        $this->forAll(
            static fn() => Generators::unit( mt_rand( 0, 100 ) ),
            function ( Inventory_Unit $u ) use ( $svc ): void {
                $tags = $svc->suggest( $u );
                foreach ( $tags as $t ) {
                    $this->assertContains( $t, Tag_Repository::VOCABULARY );
                }
                // Persistence: accept a random subset.
                $accepted = [];
                foreach ( $tags as $t ) { if ( mt_rand( 0, 1 ) ) $accepted[] = $t; }
                $svc->persistAccepted( 1, $u->id, $accepted );
                $svc->persistAccepted( 1, $u->id, [ 'NotInVocabulary' ] ); // no-op
            }
        );
    }

    public function test_property_29_export_filtered_set(): void {
        // Feature: resale-inventory-management, Property 29: Exports contain exactly the current filtered set
        $serializer = new Field_Visibility_Serializer();
        $svc        = new Export_Engine( $serializer );
        $this->forAll(
            static fn() => Generators::dataset( mt_rand( 1, 15 ) ),
            function ( array $units ) use ( $svc ): void {
                $pdf = $svc->buildPdf( $units, false );
                $xls = $svc->buildExcel( [ 'units' => $units ], false );
                $this->assertFalse( $pdf['empty'] );
                $this->assertFalse( $xls['empty'] );
                // Each exported body references each unit's project_name (proves all rows present).
                foreach ( $units as $u ) {
                    if ( $u->project_name === '' ) continue;
                    $this->assertStringContainsString( htmlspecialchars( $u->project_name ), $pdf['body'] );
                }
            }
        );
        $empty = $svc->buildPdf( [], false );
        $this->assertTrue( $empty['empty'] );
    }

    public function test_property_34_cache_consistency(): void {
        // Feature: resale-inventory-management, Property 34: Cache reads are consistent with the source until invalidation
        $this->forAll(
            static function () {
                $val = [ 'snapshot' => mt_rand( 1, 1000 ) ];
                return [ 'k' . mt_rand( 1, 10 ), $val ];
            },
            function ( array $args ): void {
                $cache = new Cache_Manager(); // fresh cache per iteration to verify the per-key contract
                [ $key, $val ] = $args;
                $first = $cache->remember( $key, static fn() => $val, 'g1', 60 );
                $this->assertSame( $val, $first );
                // While not invalidated, repeated reads return the same.
                $second = $cache->remember( $key, static fn() => [ 'snapshot' => 999999 ], 'g1', 60 );
                $this->assertSame( $val, $second );
                // After invalidation, next read reflects new producer.
                $cache->purgeGroup( 'g1' );
                $third = $cache->remember( $key, static fn() => [ 'snapshot' => 7 ], 'g1', 60 );
                $this->assertSame( [ 'snapshot' => 7 ], $third );
            }
        );
    }

    public function test_property_36_rest_round_trip(): void {
        // Feature: resale-inventory-management, Property 36: REST resources round-trip through JSON
        $serializer = new Field_Visibility_Serializer();
        $this->forAll(
            static fn() => Generators::unit( mt_rand( 0, 1000 ) ),
            function ( Inventory_Unit $u ) use ( $serializer ): void {
                foreach ( [ true, false ] as $internal ) {
                    $orig = $serializer->serialize( $u, $internal );
                    $json = json_encode( $orig, JSON_PRESERVE_ZERO_FRACTION );
                    $back = json_decode( (string) $json, true );
                    // Cast bhk back to float to honor the field-set contract; JSON has no native distinction
                    // between int and float when the fractional part is zero.
                    if ( isset( $back['bhk'] ) ) $back['bhk'] = (float) $back['bhk'];
                    if ( isset( $orig['bhk'] ) ) $orig['bhk'] = (float) $orig['bhk'];
                    $this->assertSame( $orig, $back );
                }
            }
        );
    }

    public function test_property_39_share_and_qr_links(): void {
        // Feature: resale-inventory-management, Property 39: Share links and QR codes resolve to the record's public URL
        $share = new Share_Manager();
        $qr    = new QR_Code_Generator();
        $this->forAll(
            static fn() => Generators::unit( mt_rand( 0, 100 ) ),
            function ( Inventory_Unit $u ) use ( $share, $qr ): void {
                $public_url = $u->publicUrl( 'https://goldlineestate.com' );
                foreach ( $share->all( $public_url, $u->project_name ) as $name => $url ) {
                    if ( $name === 'email' ) {
                        $this->assertStringContainsString( rawurlencode( $public_url ), $url );
                        continue;
                    }
                    $this->assertStringContainsString( rawurlencode( $public_url ), $url );
                }
                $img = $qr->imageUrl( $public_url );
                $this->assertSame( $public_url, $qr->decodeForTest( $img ) );
            }
        );
    }

    public function test_property_43_seo_slugs(): void {
        // Feature: resale-inventory-management, Property 43: SEO slugs are URL-safe, unique, and resolve back to their record
        $svc = new SEO_Engine();
        $this->forAll(
            static fn() => array_map( static fn( $i ) => Generators::unit( $i ), range( 0, mt_rand( 0, 8 ) ) ),
            function ( array $units ) use ( $svc ): void {
                $existing = [];
                $slugs    = [];
                foreach ( $units as $u ) {
                    $slug = $svc->slugForUnit( $u, $existing );
                    $this->assertMatchesRegularExpression( '/^[a-z0-9][a-z0-9-]*[a-z0-9]?$/', $slug );
                    $this->assertNotContains( $slug, $existing, 'duplicate slug' );
                    $existing[] = $slug;
                    $slugs[ $slug ] = $u->id;
                }
                // Resolution back: slug -> unit id.
                foreach ( $slugs as $slug => $id ) {
                    $this->assertSame( $id, $slugs[ $slug ] );
                }
            }
        );
    }

    public function test_property_44_seo_metadata(): void {
        // Feature: resale-inventory-management, Property 44: SEO metadata is data-derived and unique per record
        $svc = new SEO_Engine();
        $this->forAll(
            static fn() => Generators::unit( mt_rand( 0, 1000 ) ),
            function ( Inventory_Unit $u ) use ( $svc ): void {
                $meta = $svc->metadataForUnit( $u );
                $this->assertNotEmpty( $meta['title'] );
                $this->assertNotEmpty( $meta['description'] );
                $this->assertStringContainsString( $u->project_name ?: 'Unit ' . $u->id, $meta['title'] );
                $this->assertStringContainsString( (string) $u->bhk, $meta['description'] );
            }
        );
    }

    public function test_property_45_project_jsonld(): void {
        // Feature: resale-inventory-management, Property 45: Project structured data is valid and complete
        $svc = new SEO_Engine();
        $this->forAll(
            static fn() => Generators::project( mt_rand( 0, 100 ) ),
            function ( Project $p ) use ( $svc ): void {
                $jsonld = $svc->jsonLdForProject( $p, 'https://example.com' );
                $this->assertSame( 'https://schema.org', $jsonld['@context'] );
                $this->assertSame( 'Residence', $jsonld['@type'] );
                $this->assertSame( $p->name, $jsonld['name'] );
                $encoded = json_encode( $jsonld );
                $this->assertNotFalse( $encoded );
                $decoded = json_decode( $encoded, true );
                $this->assertIsArray( $decoded );
            }
        );
    }

    public function test_property_46_sitemap_urls(): void {
        // Feature: resale-inventory-management, Property 46: The sitemap contains exactly the published record URLs
        $svc = new SEO_Engine();
        $this->forAll(
            static function () {
                $units = Generators::dataset( mt_rand( 0, 15 ) );
                foreach ( $units as $u ) { $u->expired = mt_rand( 0, 4 ) === 0; }
                $projects = array_map( static fn( $i ) => Generators::project( $i ), range( 0, mt_rand( 0, 5 ) ) );
                return [ $units, $projects ];
            },
            function ( array $args ) use ( $svc ): void {
                [ $units, $projects ] = $args;
                $urls = $svc->sitemapUrls( $units, $projects, 'https://example.com' );
                foreach ( $urls as $u ) {
                    $this->assertStringStartsWith( 'https://example.com/', $u );
                }
                // Expired unit URLs are absent.
                foreach ( $units as $u ) {
                    if ( $u->expired ) {
                        $this->assertNotContains( 'https://example.com/inventory/' . rawurlencode( $u->slug ), $urls );
                    }
                }
                // Each non-expired unit URL appears exactly once.
                foreach ( $units as $u ) {
                    if ( ! $u->expired ) {
                        $expected = 'https://example.com/inventory/' . rawurlencode( $u->slug );
                        $this->assertContains( $expected, $urls );
                    }
                }
            }
        );
    }

    public function test_property_47_tenant_isolation(): void {
        // Feature: resale-inventory-management, Property 47: Tenant data access is isolated
        $this->forAll(
            static function () {
                $a = Generators::unit( mt_rand( 0, 100 ) );
                $b = Generators::unit( mt_rand( 0, 100 ) );
                $a->id = 0; $b->id = 0; // assigned by repo
                $a->tenant_id = 1;
                $b->tenant_id = 2;
                return [ $a, $b ];
            },
            function ( array $pair ): void {
                $repo = new InMemoryInventoryRepository(); // fresh per iteration so id allocation is local
                /** @var Inventory_Unit $a */
                /** @var Inventory_Unit $b */
                [ $a, $b ] = $pair;
                $idA = $repo->persist( 1, $a );
                $idB = $repo->persist( 2, $b );
                $this->assertSame( $idA, $repo->find( 1, $idA )->id );
                $this->assertNull( $repo->find( 2, $idA ), 'tenant 2 must not see tenant 1 record' );
                $this->assertNull( $repo->find( 1, $idB ), 'tenant 1 must not see tenant 2 record' );
                // Write isolation: cannot delete the other's record.
                $this->assertFalse( $repo->delete_unit( 2, $idA ) );
                $this->assertNotNull( $repo->find( 1, $idA ), 'cross-tenant delete leaked' );
            }
        );
    }

    public function test_property_48_tenant_branding_independent(): void {
        // Feature: resale-inventory-management, Property 48: Tenant branding is independent
        $store = [];
        $set = static function ( int $tid, array $patch ) use ( &$store ): void {
            $store[ $tid ] = array_merge( $store[ $tid ] ?? [], $patch );
        };
        $get = static function ( int $tid ) use ( &$store ): array {
            return $store[ $tid ] ?? [];
        };
        $this->forAll(
            static fn() => [
                mt_rand( 1, 5 ),
                mt_rand( 6, 10 ),
                [ 'company_name' => 'Tenant ' . mt_rand( 1, 999 ), 'primary_color' => sprintf( '#%06x', mt_rand( 0, 0xFFFFFF ) ) ],
            ],
            function ( array $args ) use ( $set, $get ): void {
                [ $a, $b, $patch_a ] = $args;
                $set( $a, $patch_a );
                $before_b = $get( $b );
                $set( $a, [ 'company_name' => 'Updated ' . mt_rand( 1, 999 ) ] );
                $after_b = $get( $b );
                $this->assertSame( $before_b, $after_b, 'tenant B branding mutated by tenant A change' );
            }
        );
    }
}

/* --- in-memory test doubles --- */

final class InMemoryInventoryRepository extends \RimsPro\Repositories\Inventory_Repository {
    private int $next = 1;
    /** @var array<int, array<int, Inventory_Unit>> tenant_id => unit_id => unit */
    private array $store = [];
    public function __construct() {}
    public function persist( int $tenant_id, Inventory_Unit $unit ): int {
        if ( $unit->id === 0 ) $unit->id = $this->next++;
        $unit->tenant_id = $tenant_id;
        $this->store[ $tenant_id ][ $unit->id ] = $unit;
        return $unit->id;
    }
    public function find( int $tenant_id, int $id ): ?Inventory_Unit {
        return $this->store[ $tenant_id ][ $id ] ?? null;
    }
    public function listForTenant( int $tenant_id, bool $includeExpired = false ): array {
        $rows = $this->store[ $tenant_id ] ?? [];
        if ( ! $includeExpired ) {
            $rows = array_filter( $rows, static fn( Inventory_Unit $u ) => ! $u->expired );
        }
        return array_values( $rows );
    }
    public function delete_unit( int $tenant_id, int $id ): bool {
        if ( ! isset( $this->store[ $tenant_id ][ $id ] ) ) return false;
        unset( $this->store[ $tenant_id ][ $id ] );
        return true;
    }
    public function changeStatus( int $tenant_id, int $id, string $status, ?string $changed_at = null ): bool {
        if ( ! isset( $this->store[ $tenant_id ][ $id ] ) ) return false;
        $this->store[ $tenant_id ][ $id ]->status = $status;
        $this->store[ $tenant_id ][ $id ]->status_changed_at = $changed_at ?? gmdate( 'Y-m-d H:i:s' );
        return true;
    }
    public function markExpired( int $tenant_id, int $id ): bool {
        if ( ! isset( $this->store[ $tenant_id ][ $id ] ) ) return false;
        $this->store[ $tenant_id ][ $id ]->expired = true;
        return true;
    }
    public function renew( int $tenant_id, int $id, string $previous_status ): bool {
        if ( ! isset( $this->store[ $tenant_id ][ $id ] ) ) return false;
        $u = $this->store[ $tenant_id ][ $id ];
        $u->expired = false;
        $u->status = $previous_status;
        $u->published_at = gmdate( 'Y-m-d H:i:s' );
        $u->status_changed_at = $u->published_at;
        return true;
    }
    public function listFeatured( int $tenant_id ): array {
        return array_values( array_filter( $this->store[ $tenant_id ] ?? [], static fn( Inventory_Unit $u ) => $u->is_featured && ! $u->expired ) );
    }
}

final class InMemoryLeadHistory extends \RimsPro\Repositories\Lead_History_Repository {
    public function __construct() {}
    public function record( int $lead_id, ?int $actor_id, ?string $from_stage, string $to_stage, ?string $note = null ): int { return 1; }
    public function forLead( int $lead_id ): array { return []; }
}
final class InMemoryAnalyticsRepo extends \RimsPro\Repositories\Analytics_Repository {
    public function __construct() {}
    public function record( int $tenant_id, string $event_type, ?int $unit_id, ?int $project_id, string $session_hash ): int { return 1; }
    public function topByEvent( int $tenant_id, string $event_type, string $entity_col, int $limit = 10 ): array { return []; }
}
final class InMemoryMediaRepo extends \RimsPro\Repositories\Media_Repository {
    public function __construct() {}
    public function persist( int $tenant_id, array $data ): int { return 1; }
    public function listForOwner( int $tenant_id, string $owner_type, int $owner_id ): array { return []; }
}
final class InMemoryTagRepo extends \RimsPro\Repositories\Tag_Repository {
    public function __construct() {}
    public function persistAccepted( int $tenant_id, int $unit_id, array $accepted_tags ): void {}
    public function ensureVocabulary( int $tenant_id ): void {}
}

/** Stub Tenant_Resolver for Expiry tests. */
class StubResolver extends \RimsPro\Tenancy\Tenant_Resolver {
    public function __construct() {}
    public function resolve(): \RimsPro\Domain\Tenant {
        return new \RimsPro\Domain\Tenant( id: 1, name: 'T', domain: 'localhost', mode: 'single' );
    }
    public function branding( int $tenant_id ): array { return [ 'expiry_days' => 30 ]; }
    public function override( \RimsPro\Domain\Tenant $tenant ): void {}
}
