<?php
declare(strict_types=1);

namespace RimsPro\Domain;

/**
 * Inventory_Unit DTO. Holds all canonical fields including internal-only ones
 * (owner_name/owner_phone/broker_notes). Use Field_Visibility_Serializer to
 * select an audience-appropriate field set.
 */
final class Inventory_Unit {

    public function __construct(
        public int $id = 0,
        public int $tenant_id = 0,
        public int $project_id = 0,
        public string $project_name = '',
        public string $builder = '',
        public string $location = '',
        public string $sector = '',
        public string $unit_number = '',
        public string $tower = '',
        public int $floor = 0,
        public string $facing = 'N',
        public float $bhk = 2.0,
        public bool $is_penthouse = false,
        public int $area_sqft = 0,
        public int $price_paise = 0,
        public string $price_label = '',
        public string $owner_name = '',
        public string $owner_phone = '',
        public string $broker_notes = '',
        public string $status = 'available',
        public ?string $status_changed_at = null,
        public bool $is_featured = false,
        public ?string $published_at = null,
        public bool $expired = false,
        public string $slug = '',
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $image_url = null,
        public string $created_at = '',
        public string $updated_at = '',
        /** @var array<int, array{area_sqft:int,label:string}> */
        public array $variants = [],
    ) {
    }

    public function toArray(): array {
        return get_object_vars( $this );
    }

    public static function fromArray( array $row ): self {
        $u = new self();
        foreach ( get_object_vars( $u ) as $k => $_ ) {
            if ( array_key_exists( $k, $row ) ) {
                $u->{$k} = $row[ $k ];
            }
        }
        // Common $wpdb-row mapping for price (DECIMAL string -> paise int).
        if ( isset( $row['price'] ) && ! isset( $row['price_paise'] ) ) {
            $u->price_paise = (int) round( ((float) $row['price'] ) * 100 );
        }
        return $u;
    }

    public function publicUrl( string $base = '/' ): string {
        $base = rtrim( $base, '/' );
        return $base . '/inventory/' . rawurlencode( $this->slug ?: (string) $this->id );
    }
}
