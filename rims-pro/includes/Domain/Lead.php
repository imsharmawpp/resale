<?php
declare(strict_types=1);

namespace RimsPro\Domain;

final class Lead {

    public function __construct(
        public int $id = 0,
        public int $tenant_id = 0,
        public string $name = '',
        public string $mobile = '',
        public ?string $email = null,
        public ?string $requirements = null,
        public string $source = 'web',
        public ?int $unit_id = null,
        public string $pipeline_stage = 'new',
        public string $crm_sync_status = 'pending',
        public string $created_at = '',
        public string $updated_at = '',
    ) {
    }

    public static function fromArray( array $row ): self {
        $l = new self();
        foreach ( get_object_vars( $l ) as $k => $_ ) {
            if ( array_key_exists( $k, $row ) ) {
                $l->{$k} = $row[ $k ];
            }
        }
        return $l;
    }

    public function toArray(): array {
        return get_object_vars( $this );
    }
}
