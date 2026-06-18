<?php
declare(strict_types=1);

namespace RimsPro\Security;

/**
 * Sliding-window rate limiter. Backed by an in-memory store by default;
 * may be swapped via setStore() (e.g. WP transients, Redis) without changing
 * the algorithm.
 */
final class Rate_Limiter {

    /** @var array<string, array{count:int, window_start:int}> */
    private array $store = [];

    private $clock;

    public function __construct( ?callable $clock = null ) {
        $this->clock = $clock ?? static fn(): int => time();
    }

    public function allow( string $client_id, int $max, int $window_seconds ): bool {
        if ( $max <= 0 || $window_seconds <= 0 ) {
            return false;
        }
        $now    = (int) ( $this->clock )();
        $bucket = $this->store[ $client_id ] ?? null;
        if ( $bucket === null || ( $now - $bucket['window_start'] ) >= $window_seconds ) {
            $this->store[ $client_id ] = [ 'count' => 1, 'window_start' => $now ];
            return true;
        }
        if ( $bucket['count'] >= $max ) {
            return false;
        }
        $this->store[ $client_id ]['count'] = $bucket['count'] + 1;
        return true;
    }

    public function reset(): void {
        $this->store = [];
    }
}
