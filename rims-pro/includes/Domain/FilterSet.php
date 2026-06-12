<?php
declare(strict_types=1);

namespace RimsPro\Domain;

final readonly class FilterSet {

    public function __construct(
        public ?string $project = null,
        public ?string $builder = null,
        public ?string $location = null,
        public ?string $sector = null,
        public ?float $bhk = null,
        public ?int $areaMin = null,
        public ?int $areaMax = null,
        public ?int $budgetMinPaise = null,
        public ?int $budgetMaxPaise = null,
        public ?string $facing = null,
        public ?string $tower = null,
        public ?int $floor = null,
        public ?string $status = null,
    ) {
    }

    public static function empty(): self {
        return new self();
    }

    public function isEmpty(): bool {
        return $this->project === null
            && $this->builder === null
            && $this->location === null
            && $this->sector === null
            && $this->bhk === null
            && $this->areaMin === null
            && $this->areaMax === null
            && $this->budgetMinPaise === null
            && $this->budgetMaxPaise === null
            && $this->facing === null
            && $this->tower === null
            && $this->floor === null
            && $this->status === null;
    }

    public static function fromArray( array $data ): self {
        $g = static fn( string $k ) => array_key_exists( $k, $data ) && $data[ $k ] !== '' ? $data[ $k ] : null;
        return new self(
            project: $g( 'project' ) !== null ? (string) $g( 'project' ) : null,
            builder: $g( 'builder' ) !== null ? (string) $g( 'builder' ) : null,
            location: $g( 'location' ) !== null ? (string) $g( 'location' ) : null,
            sector: $g( 'sector' ) !== null ? (string) $g( 'sector' ) : null,
            bhk: $g( 'bhk' ) !== null ? (float) $g( 'bhk' ) : null,
            areaMin: $g( 'area_min' ) !== null ? (int) $g( 'area_min' ) : null,
            areaMax: $g( 'area_max' ) !== null ? (int) $g( 'area_max' ) : null,
            budgetMinPaise: $g( 'budget_min' ) !== null ? (int) $g( 'budget_min' ) : null,
            budgetMaxPaise: $g( 'budget_max' ) !== null ? (int) $g( 'budget_max' ) : null,
            facing: $g( 'facing' ) !== null ? (string) $g( 'facing' ) : null,
            tower: $g( 'tower' ) !== null ? (string) $g( 'tower' ) : null,
            floor: $g( 'floor' ) !== null ? (int) $g( 'floor' ) : null,
            status: $g( 'status' ) !== null ? (string) $g( 'status' ) : null,
        );
    }
}
