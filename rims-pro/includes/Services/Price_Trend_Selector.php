<?php
declare(strict_types=1);

namespace RimsPro\Services;

/**
 * Selects price-history points whose `recorded_at` is within the chosen
 * window (6 or 12 months) relative to now (Property 17 / Req 12.5).
 */
final class Price_Trend_Selector {

    public const WINDOW_6M  = 6;
    public const WINDOW_12M = 12;

    /**
     * @param array<int, array{recorded_at:string, price:float}> $points
     * @return array<int, array{recorded_at:string, price:float}>
     */
    public function select( array $points, int $months, ?int $now_ts = null ): array {
        $now_ts = $now_ts ?? time();
        if ( ! in_array( $months, [ self::WINDOW_6M, self::WINDOW_12M ], true ) ) {
            $months = self::WINDOW_6M;
        }
        $cutoff = strtotime( "-{$months} months", $now_ts );
        if ( $cutoff === false ) {
            return $points;
        }
        $out = [];
        foreach ( $points as $p ) {
            $ts = strtotime( $p['recorded_at'] );
            if ( $ts !== false && $ts >= $cutoff && $ts <= $now_ts ) {
                $out[] = $p;
            }
        }
        return $out;
    }
}
