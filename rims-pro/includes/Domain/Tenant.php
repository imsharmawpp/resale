<?php
declare(strict_types=1);

namespace RimsPro\Domain;

final class Tenant {

    public function __construct(
        public int $id = 0,
        public string $name = '',
        public string $domain = '',
        public string $mode = 'single',
    ) {
    }
}
