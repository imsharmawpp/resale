<?php
declare(strict_types=1);

namespace RimsPro\Services;

/**
 * Comparison set with cap of 4 distinct units (Property 37 / Req 26.2, 26.3).
 * Mutating; returns updated set + optional message.
 */
final class Comparison_Engine {

    public const MAX = 4;

    /**
     * @param int[] $current
     * @return array{set:int[], message?:string}
     */
    public function add( array $current, int $unit_id ): array {
        $current = array_values( array_unique( $current ) );
        if ( in_array( $unit_id, $current, true ) ) {
            return [ 'set' => $current ];
        }
        if ( count( $current ) >= self::MAX ) {
            return [
                'set'     => $current,
                'message' => sprintf( 'You can compare up to %d units at once.', self::MAX ),
            ];
        }
        $current[] = $unit_id;
        return [ 'set' => $current ];
    }

    /**
     * @param int[] $current
     * @return int[]
     */
    public function remove( array $current, int $unit_id ): array {
        return array_values( array_filter( $current, static fn( $id ) => (int) $id !== $unit_id ) );
    }
}
