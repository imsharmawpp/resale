<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;

/**
 * Returns exactly the units flagged is_featured (Property 7 / Req 6.1, 6.5).
 */
final class Featured_Carousel_Service {

    /**
     * @param Inventory_Unit[] $dataset
     * @return Inventory_Unit[]
     */
    public function select( array $dataset ): array {
        $out = [];
        foreach ( $dataset as $u ) {
            if ( $u->is_featured && ! $u->expired ) {
                $out[] = $u;
            }
        }
        return $out;
    }
}
