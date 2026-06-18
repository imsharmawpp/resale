<?php
declare(strict_types=1);

namespace RimsPro\Domain;

final class Project {

    public function __construct(
        public int $id = 0,
        public int $tenant_id = 0,
        public string $name = '',
        public string $slug = '',
        public string $builder = '',
        public string $location = '',
        public string $sector = '',
        public ?float $latitude = null,
        public ?float $longitude = null,
        public string $possession_status = '',
        public int $tower_count = 0,
        public string $seo_title = '',
        public string $seo_description = '',
        public string $overview = '',
        public string $created_at = '',
        public string $updated_at = '',
    ) {
    }

    public static function fromArray( array $row ): self {
        $p = new self();
        foreach ( get_object_vars( $p ) as $k => $_ ) {
            if ( array_key_exists( $k, $row ) ) {
                $p->{$k} = $row[ $k ];
            }
        }
        return $p;
    }

    public function publicUrl( string $base = '/' ): string {
        $base = rtrim( $base, '/' );
        return $base . '/project/' . rawurlencode( $this->slug ?: (string) $this->id );
    }
}
