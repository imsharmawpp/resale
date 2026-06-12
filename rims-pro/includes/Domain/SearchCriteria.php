<?php
declare(strict_types=1);

namespace RimsPro\Domain;

final readonly class SearchCriteria {

    /**
     * @param string[] $rawTerms
     */
    public function __construct(
        public ?float $bhk = null,
        public ?int $maxBudgetPaise = null,
        public ?string $builderOrProject = null,
        public ?string $location = null,
        public array $rawTerms = [],
    ) {
    }

    public function isEmpty(): bool {
        return $this->bhk === null
            && $this->maxBudgetPaise === null
            && $this->builderOrProject === null
            && $this->location === null
            && empty( $this->rawTerms );
    }
}
