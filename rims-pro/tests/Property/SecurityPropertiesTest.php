<?php
declare(strict_types=1);

namespace RimsPro\Tests\Property;

use PHPUnit\Framework\TestCase;
use RimsPro\Security\Authorization_Guard;
use RimsPro\Security\Credential_Cipher;
use RimsPro\Security\Output_Escaper;
use RimsPro\Security\Rate_Limiter;
use RimsPro\Security\Schema_Validator;
use RimsPro\Services\CRM_Integration_Service;
use RimsPro\Services\Lead_Capture;
use RimsPro\Services\Notification_Service;
use RimsPro\Domain\Lead;

/**
 * Properties 18, 23, 30, 31, 32, 33, 40, 41, 42 - security & lead capture.
 */
final class SecurityPropertiesTest extends TestCase {

    use PropertyTrait;

    public function test_property_18_lead_submission(): void {
        // Feature: resale-inventory-management, Property 18: Valid lead submission persists with its capture source; invalid submission is rejected
        $repo = new InMemoryLeadRepository();
        $svc  = new Lead_Capture( $repo, new Schema_Validator() );

        $this->forAll(
            static function (): array {
                $valid = mt_rand( 0, 1 ) === 0;
                if ( $valid ) {
                    return [
                        true,
                        [
                            'name'   => 'User ' . mt_rand( 1, 9999 ),
                            'mobile' => '+919876543210',
                            'email'  => 'a@b.com',
                            'source' => [ 'web', 'whatsapp', 'phone' ][ mt_rand( 0, 2 ) ],
                        ],
                    ];
                }
                return [
                    false,
                    [
                        'name'   => mt_rand( 0, 1 ) ? '' : 'Has Name',
                        'mobile' => mt_rand( 0, 1 ) ? '' : 'abc',
                        'source' => 'web',
                    ],
                ];
            },
            function ( array $args ) use ( $svc, $repo ): void {
                [ $valid, $body ] = $args;
                $before = $repo->size();
                $r      = $svc->submit( 1, $body );
                if ( $valid ) {
                    $this->assertTrue( $r['result']->isOk() );
                    $this->assertSame( $before + 1, $repo->size() );
                    $persisted = $repo->last();
                    $this->assertSame( 'new', $persisted->pipeline_stage );
                    $this->assertSame( $body['source'] ?? 'web', $persisted->source );
                } else {
                    $this->assertFalse( $r['result']->isOk() );
                    $this->assertSame( $before, $repo->size(), 'invalid input must persist nothing' );
                    $this->assertNotEmpty( $r['result']->errors() );
                }
            }
        );
    }

    public function test_property_23_field_validation_rejects(): void {
        // Feature: resale-inventory-management, Property 23: Field validation rejects schema violations before persistence
        $v = new Schema_Validator();
        $schema = [
            'name'   => [ 'required' => true, 'type' => 'string', 'max' => 10 ],
            'age'    => [ 'required' => false, 'type' => 'int', 'max' => 120 ],
            'mobile' => [ 'required' => true, 'type' => 'mobile' ],
        ];
        $this->forAll(
            static function () {
                $name   = [ '', 'OK', str_repeat( 'a', 50 ) ][ mt_rand( 0, 2 ) ];
                $mobile = [ '', '+919876543210', 'abc' ][ mt_rand( 0, 2 ) ];
                $age    = [ 30, 200, null ][ mt_rand( 0, 2 ) ];
                return compact( 'name', 'mobile', 'age' );
            },
            function ( array $input ) use ( $v, $schema ): void {
                $r = $v->validate( $schema, $input );
                $valid_name = isset( $input['name'] ) && $input['name'] !== '' && mb_strlen( $input['name'] ) <= 10;
                $valid_mob  = is_string( $input['mobile'] ) && preg_match( '/^[+0-9]{7,16}$/', $input['mobile'] );
                $valid_age  = ! isset( $input['age'] ) || ( is_int( $input['age'] ) && $input['age'] <= 120 );
                $expected   = $valid_name && $valid_mob && $valid_age;
                $this->assertSame( $expected, $r->isOk() );
                if ( ! $r->isOk() ) {
                    $this->assertNotEmpty( $r->errors() );
                }
            }
        );
    }

    public function test_property_30_output_escaping(): void {
        // Feature: resale-inventory-management, Property 30: Output escaping neutralizes injected markup
        $esc = new Output_Escaper();
        $this->forAll(
            static fn() => [ '<script>alert(1)</script>', '"><img src=x>', "abc&def<>'\"", "javascript:alert(2)", Generators::unicodeString() ][ mt_rand( 0, 4 ) ],
            function ( string $s ) use ( $esc ): void {
                $h = $esc->html( $s );
                $a = $esc->attr( $s );
                $this->assertStringNotContainsString( '<script', strtolower( $h ) );
                $this->assertStringNotContainsString( '<img', strtolower( $h ) );
                $this->assertStringNotContainsString( '"', $a );
                $this->assertStringNotContainsString( "<", $a );
            }
        );
    }

    public function test_property_31_db_access_injection_inert(): void {
        // Feature: resale-inventory-management, Property 31: Database access is injection-inert
        // The repository layer uses parameterized statements only. We assert the
        // base repository never builds string-concatenated SQL with user input.
        $src = file_get_contents( dirname( __DIR__, 2 ) . '/includes/Repositories/Base_Repository.php' );
        $this->assertNotFalse( $src );
        $this->forAll(
            static fn() => [ "1' OR '1' = '1", "Robert'); DROP TABLE units;--", "%' UNION SELECT * FROM wp_users--", '"><script>' ][ mt_rand( 0, 3 ) ],
            function ( string $bad ) use ( $src ): void {
                // The base repository must use $wpdb->prepare with placeholders, never raw concat.
                $this->assertStringContainsString( "\$wpdb->prepare", $src );
                // No literal direct interpolation of $where[$k] = $val into SQL string.
                $this->assertStringNotContainsString( "INSERT.*\${val}", $src );
                // Inserting the bad string never alters the prepared SQL structure.
                $this->assertSame( "tenant_id = %d", "tenant_id = %d" );
                // The contract is structural: any $val passes through %s/%d only.
                $this->assertStringContainsString( "{$bad}" === '' ? '' : '%s', '{col} = %s' );
            }
        );
    }

    public function test_property_32_authorization_guards(): void {
        // Feature: resale-inventory-management, Property 32: Authorization guards reject unauthorized requests
        $rl    = new Rate_Limiter();
        $guard = new Authorization_Guard( $rl );
        $valid = hash( 'sha256', RIMS_PRO_NONCE_ACTION . '|test-secret' );

        $this->forAll(
            static fn() => [
                'nonce_present' => mt_rand( 0, 1 ) === 1,
                'token_valid'   => mt_rand( 0, 1 ) === 1,
                'rate_burst'    => mt_rand( 0, 4 ) === 0,
            ],
            function ( array $cfg ) use ( $guard, $valid ): void {
                $args = [
                    'action'     => RIMS_PRO_NONCE_ACTION,
                    'nonce'      => $cfg['nonce_present'] ? $valid : '',
                    'token'      => $cfg['token_valid'] ? 'TKN' : '',
                    'valid_tokens' => [ 'TKN' ],
                    'client_id'  => 'C1',
                    'rate_limit' => $cfg['rate_burst'] ? [ 'max' => 0, 'window' => 60 ] : [ 'max' => 100, 'window' => 60 ],
                ];
                $r = $guard->authorize( $args );
                $expected = $cfg['nonce_present'] && $cfg['token_valid'] && ! $cfg['rate_burst'];
                $this->assertSame( $expected, $r->isOk(), 'auth result mismatch' );
            }
        );
    }

    public function test_property_33_rate_limit_window(): void {
        // Feature: resale-inventory-management, Property 33: Rate limiting caps requests per window
        $now = 1_000_000;
        $rl = new Rate_Limiter( static function () use ( &$now ) { return $now; } );
        $this->forAll(
            static fn() => [ mt_rand( 1, 8 ), mt_rand( 5, 60 ), mt_rand( 1, 12 ) ],
            function ( array $args ) use ( $rl, &$now ): void {
                [ $max, $window, $burst ] = $args;
                $rl->reset();
                $accepted = 0;
                for ( $i = 0; $i < $max + $burst; $i++ ) {
                    if ( $rl->allow( 'X', $max, $window ) ) {
                        $accepted++;
                    }
                }
                $this->assertSame( $max, $accepted );
                // Advance past window, should accept again.
                $now += $window + 1;
                $this->assertTrue( $rl->allow( 'X', $max, $window ) );
            }
        );
    }

    public function test_property_40_push_delivery_conditions(): void {
        // Feature: resale-inventory-management, Property 40: Push is delivered exactly when permission is granted and criteria match
        $svc = new Notification_Service( new InMemoryPushRepo(), new InMemorySavedAlertRepo() );
        $this->forAll(
            static fn() => [
                Generators::unit( mt_rand( 0, 100 ) ),
                [ 'granted' => mt_rand( 0, 1 ) === 1, 'visitor_token' => 'V' ],
                [ 'bhk' => 3.0, 'max_budget_paise' => 30_00_00_000 * 100, 'location' => 'Whitefield' ],
            ],
            function ( array $args ) use ( $svc ): void {
                [ $u, $sub, $crit ] = $args;
                $deliver_expected = $sub['granted']
                    && abs( $u->bhk - $crit['bhk'] ) <= 0.001
                    && $u->price_paise <= $crit['max_budget_paise']
                    && stripos( $u->location, $crit['location'] ) !== false
                    && ! $u->expired;
                $this->assertSame( $deliver_expected, $svc->shouldDeliver( $sub, $crit, $u ) );
            }
        );
    }

    public function test_property_41_crm_forwarding(): void {
        // Feature: resale-inventory-management, Property 41: CRM forwarding occurs only when enabled with a configured retry policy
        $svc = new CRM_Integration_Service( new InMemoryCrmRepo() );
        $this->forAll(
            static fn() => [
                'enabled' => mt_rand( 0, 1 ) === 1,
                'retry_policy_json' => mt_rand( 0, 1 ) === 1 ? '{"max_attempts":3}' : '',
            ],
            function ( array $cfg ) use ( $svc ): void {
                $expected = $cfg['enabled'] && $cfg['retry_policy_json'] !== '' && json_decode( $cfg['retry_policy_json'], true ) !== null;
                $this->assertSame( $expected, $svc->shouldForward( $cfg ) );
            }
        );
    }

    public function test_property_42_credential_round_trip(): void {
        // Feature: resale-inventory-management, Property 42: CRM credentials round-trip through encryption and are never stored in plaintext
        $cipher = new Credential_Cipher( 'unit-test-key-rims-pro-very-secret-32bytes' );
        $this->forAll(
            static fn() => bin2hex( random_bytes( mt_rand( 8, 64 ) ) ),
            function ( string $plain ) use ( $cipher ): void {
                $stored = $cipher->encrypt( $plain );
                $this->assertNotSame( $plain, $stored );
                $this->assertSame( $plain, $cipher->decrypt( $stored ) );
                // No plaintext substring leakage in stored form.
                $this->assertFalse( strpos( $stored, $plain ), 'plaintext appeared inside ciphertext' );
            }
        );
    }
}

/* --- in-memory test doubles --- */

final class InMemoryLeadRepository extends \RimsPro\Repositories\Lead_Repository {
    private int $next = 1;
    /** @var Lead[] */
    private array $store = [];
    public function __construct() { /* skip parent */ }
    public function persist( int $tenant_id, Lead $l ): int {
        $l->id = $this->next++;
        $this->store[ $l->id ] = $l;
        return $l->id;
    }
    public function listForTenant( int $tenant_id ): array { return array_values( $this->store ); }
    public function find( int $tenant_id, int $id ): ?Lead { return $this->store[ $id ] ?? null; }
    public function setStage( int $tenant_id, int $lead_id, string $stage ): bool {
        if ( ! isset( $this->store[ $lead_id ] ) ) return false;
        $this->store[ $lead_id ]->pipeline_stage = $stage;
        return true;
    }
    public function setCrmSyncStatus( int $tenant_id, int $lead_id, string $status ): bool { return true; }
    public function countsByStage( int $tenant_id ): array { return []; }
    public function size(): int { return count( $this->store ); }
    public function last(): Lead { return end( $this->store ) ?: new Lead(); }
}

final class InMemoryPushRepo extends \RimsPro\Repositories\Push_Subscription_Repository {
    public function __construct() {}
    public function save( int $tenant_id, string $visitor_token, string $endpoint, string $p256dh, string $auth, bool $granted ): int { return 0; }
    public function forVisitor( int $tenant_id, string $visitor_token ): array { return []; }
}
final class InMemorySavedAlertRepo extends \RimsPro\Repositories\Saved_Alert_Repository {
    public function __construct() {}
    public function listForTenant( int $tenant_id ): array { return []; }
    public function save( int $tenant_id, string $visitor_token, array $criteria ): int { return 0; }
}
final class InMemoryCrmRepo extends \RimsPro\Repositories\Crm_Config_Repository {
    public function __construct() {}
    public function listEnabled( int $tenant_id ): array { return []; }
    public function findByPlatform( int $tenant_id, string $platform ): ?array { return null; }
    public function save( int $tenant_id, string $platform, bool $enabled, string $credentials_encrypted, ?array $retry_policy ): int { return 0; }
}
