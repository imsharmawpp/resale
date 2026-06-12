<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;

/**
 * Builds WhatsApp + tel: links targeting the configured number with a
 * unit-identifying message (Property 10 / Req 7.4, 7.5).
 */
final class Contact_Link_Builder {

    public function whatsapp( string $configured_number, Inventory_Unit $u ): string {
        $digits  = preg_replace( '/[^0-9]/', '', $configured_number ) ?? '';
        $message = sprintf(
            'Hi, I am interested in the %s in %s (Unit #%s, %g BHK, %d sqft) - listing ID %d.',
            $u->project_name ?: 'inventory',
            $u->location ?: 'your project',
            $u->unit_number,
            $u->bhk,
            $u->area_sqft,
            $u->id
        );
        return 'https://wa.me/' . $digits . '?text=' . rawurlencode( $message );
    }

    public function tel( string $configured_number ): string {
        $digits = preg_replace( '/[^0-9+]/', '', $configured_number ) ?? '';
        return 'tel:' . $digits;
    }
}
