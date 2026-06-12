<?php
declare(strict_types=1);

namespace RimsPro\Services;

/**
 * Stable, ordered pagination. Returns page slice + true total count.
 * Property 35: concatenation of pages == ordered dataset, no duplicates/gaps.
 */
final class Pagination_Service {

    public const DEFAULT_PER_PAGE = 12;
    public const MAX_PER_PAGE     = 100;

    /**
     * @param array<int, mixed> $dataset
     * @return array{items: array<int, mixed>, total:int, page:int, per_page:int, total_pages:int}
     */
    public function page( array $dataset, int $page = 1, int $per_page = self::DEFAULT_PER_PAGE ): array {
        $per_page = max( 1, min( self::MAX_PER_PAGE, $per_page ) );
        $page     = max( 1, $page );
        $total    = count( $dataset );
        $offset   = ( $page - 1 ) * $per_page;
        $items    = array_slice( array_values( $dataset ), $offset, $per_page );
        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => $per_page > 0 ? (int) max( 1, ceil( $total / $per_page ) ) : 1,
        ];
    }
}
