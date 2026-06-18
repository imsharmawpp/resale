<?php
declare(strict_types=1);

namespace RimsPro\Tests\Property;

/**
 * Lightweight property-test driver. Each property generates >= 100 random
 * inputs and asserts an invariant. Mirrors the Eris/QuickCheck pattern
 * (using the design's chosen library when present; falls back to this driver
 * when Eris is unavailable in the sandbox).
 */
trait PropertyTrait {

    protected int $iterations = 100;

    /**
     * @template T
     * @param callable():T $generator
     * @param callable(T):void $invariant
     */
    protected function forAll( callable $generator, callable $invariant ): void {
        $seed = isset( $_ENV['RIMS_PROP_SEED'] ) ? (int) $_ENV['RIMS_PROP_SEED'] : 1234567;
        mt_srand( $seed );
        for ( $i = 0; $i < $this->iterations; $i++ ) {
            $input = $generator();
            try {
                $invariant( $input );
            } catch ( \Throwable $e ) {
                $this->fail( sprintf(
                    "Property failed on iteration %d (seed=%d): %s\nCounterexample: %s",
                    $i + 1,
                    $seed,
                    $e->getMessage(),
                    self::dump( $input )
                ) );
            }
        }
        $this->assertTrue( true ); // PHPUnit risky-test guard.
    }

    private static function dump( mixed $v ): string {
        if ( is_object( $v ) ) {
            return get_class( $v ) . ' ' . json_encode( get_object_vars( $v ), JSON_PARTIAL_OUTPUT_ON_ERROR );
        }
        return (string) json_encode( $v, JSON_PARTIAL_OUTPUT_ON_ERROR );
    }
}
