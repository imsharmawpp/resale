<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;

/**
 * Serializes Inventory_Unit picking the field set by audience.
 *
 * - Visitor / non-capability: public fields only
 * - Capability holder (inventory-management): public + internal (owner_name, owner_phone, broker_notes)
 *
 * Validates: Req 18.8, 22.4, 10.10  (Property 9).
 */
final class Field_Visibility_Serializer {

    public const PUBLIC_FIELDS = [
        'id',
        'tenant_id',
        'project_id',
        'project_name',
        'builder',
        'location',
        'sector',
        'unit_number',
        'tower',
        'floor',
        'facing',
        'bhk',
        'is_penthouse',
        'area_sqft',
        'price_paise',
        'price_label',
        'status',
        'status_changed_at',
        'is_featured',
        'published_at',
        'expired',
        'slug',
        'latitude',
        'longitude',
        'image_url',
        'created_at',
        'updated_at',
        'variants',
    ];

    public const INTERNAL_FIELDS = [ 'owner_name', 'owner_phone', 'broker_notes' ];

    /**
     * @return array<string, mixed>
     */
    public function serialize( Inventory_Unit $u, bool $include_internal ): array {
        $arr = $u->toArray();
        $out = [];
        foreach ( self::PUBLIC_FIELDS as $f ) {
            if ( array_key_exists( $f, $arr ) ) {
                $out[ $f ] = $arr[ $f ];
            }
        }
        if ( $include_internal ) {
            foreach ( self::INTERNAL_FIELDS as $f ) {
                $out[ $f ] = $arr[ $f ] ?? '';
            }
        }
        return $out;
    }

    /**
     * @param Inventory_Unit[] $units
     * @return array<int, array<string, mixed>>
     */
    public function serializeMany( array $units, bool $include_internal ): array {
        return array_map( fn( Inventory_Unit $u ) => $this->serialize( $u, $include_internal ), $units );
    }
}
