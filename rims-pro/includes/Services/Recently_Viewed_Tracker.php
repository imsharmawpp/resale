<?php
declare(strict_types=1);

namespace RimsPro\Services;

/**
 * Pure list mutation. Most-recent-first; deduplicated to each unit's latest view.
 * (Property 38 / Req 26.5, 26.6).
 */
final class Recently_Viewed_Tracker {

    public const MAX = 20;

    /**
     * @param int[] $current ordered most-recent-first
     * @return int[]
     */
    public function track( array $current, int $unit_id ): array {
        $current = array_values( array_filter( $current, static fn( $id ) => (int) $id !== $unit_id ) );
        array_unshift( $current, $unit_id );
        if ( count( $current ) > self::MAX ) {
            $current = array_slice( $current, 0, self::MAX );
        }
        return array_values( array_map( 'intval', $current ) );
    }
}
