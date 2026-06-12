<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;
use RimsPro\Repositories\Tag_Repository;

/**
 * Suggests tags from the configured vocabulary; persists ONLY explicitly
 * accepted tags (Property 28 / Req 21.4, 21.5).
 */
final class AI_Tag_Generator {

    public function __construct(
        private Tag_Repository $tags,
    ) {
    }

    /** @return string[] subset of Tag_Repository::VOCABULARY */
    public function suggest( Inventory_Unit $u ): array {
        $out = [];
        if ( $u->price_paise > 5_00_00_000 * 100 ) {
            $out[] = 'Luxury';
        }
        if ( strpos( strtolower( $u->location . ' ' . $u->project_name ), 'golf' ) !== false ) {
            $out[] = 'Golf View';
        }
        if ( in_array( strtoupper( $u->facing ), [ 'NE', 'NW', 'SE', 'SW' ], true ) ) {
            $out[] = 'Corner Unit';
        }
        if ( strpos( strtolower( $u->location . ' ' . $u->project_name ), 'park' ) !== false ) {
            $out[] = 'Park Facing';
        }
        if ( $u->status === 'available' && $u->is_featured ) {
            $out[] = 'Urgent Sale';
        }
        if ( $u->price_paise < 1_00_00_000 * 100 && $u->bhk <= 2.0 ) {
            $out[] = 'Investor Deal';
        }
        // Defense in depth: every suggestion MUST be in the vocabulary.
        return array_values( array_intersect( $out, Tag_Repository::VOCABULARY ) );
    }

    /**
     * Persist only the accepted subset; rejected/unknown tags persist nothing.
     *
     * @param string[] $accepted
     */
    public function persistAccepted( int $tenant_id, int $unit_id, array $accepted ): void {
        $clean = array_values( array_intersect( $accepted, Tag_Repository::VOCABULARY ) );
        $this->tags->persistAccepted( $tenant_id, $unit_id, $clean );
    }
}
