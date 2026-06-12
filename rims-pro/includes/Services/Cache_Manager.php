<?php
declare(strict_types=1);

namespace RimsPro\Services;

/**
 * Read-through cache wrapping WP Object Cache (Redis-routed when a drop-in is
 * present). Keys are deterministic hashes of (tenant_id, query-shape, normalized-filters, page).
 * Writes invalidate affected key groups within the configured TTL.
 *
 * In-memory fallback when wp_cache_* is unavailable (tests).
 */
final class Cache_Manager {

    public const DEFAULT_TTL = 300; // 5 minutes
    private const GROUP      = 'rims_pro';

    /** @var array<string, array{value:mixed, exp:int}> */
    private array $local = [];

    /** @var array<string, array<string, true>> indexed group -> keys */
    private array $groups = [];

    private $clock;

    public function __construct( ?callable $clock = null ) {
        $this->clock = $clock ?? static fn(): int => time();
    }

    public function key( int $tenant_id, string $shape, array $filters, int $page = 1 ): string {
        ksort( $filters );
        $payload = wp_json_encode( [ $tenant_id, $shape, $filters, $page ] );
        return 'rims_' . hash( 'sha256', (string) $payload );
    }

    /**
     * @template T
     * @param callable():T $producer
     * @return T
     */
    public function remember( string $key, callable $producer, ?string $group = null, int $ttl = self::DEFAULT_TTL ) {
        $value = $this->get( $key );
        if ( $value !== null ) {
            return $value;
        }
        $value = $producer();
        $this->set( $key, $value, $group, $ttl );
        return $value;
    }

    public function get( string $key ): mixed {
        if ( function_exists( 'wp_cache_get' ) ) {
            $v = wp_cache_get( $key, self::GROUP );
            return $v === false ? null : $v;
        }
        $entry = $this->local[ $key ] ?? null;
        if ( $entry === null ) {
            return null;
        }
        if ( $entry['exp'] < (int) ( $this->clock )() ) {
            unset( $this->local[ $key ] );
            return null;
        }
        return $entry['value'];
    }

    public function set( string $key, mixed $value, ?string $group = null, int $ttl = self::DEFAULT_TTL ): void {
        if ( function_exists( 'wp_cache_set' ) ) {
            wp_cache_set( $key, $value, self::GROUP, $ttl );
        } else {
            $this->local[ $key ] = [
                'value' => $value,
                'exp'   => (int) ( $this->clock )() + $ttl,
            ];
        }
        if ( $group !== null ) {
            $this->groups[ $group ][ $key ] = true;
        }
    }

    public function delete( string $key ): void {
        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( $key, self::GROUP );
        }
        unset( $this->local[ $key ] );
    }

    public function purgeGroup( string $group ): void {
        $keys = array_keys( $this->groups[ $group ] ?? [] );
        foreach ( $keys as $k ) {
            $this->delete( $k );
        }
        unset( $this->groups[ $group ] );
    }
}
