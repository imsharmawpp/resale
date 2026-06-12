<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Core\Container;

final class Cron_Scheduler {

    public function __construct( private Container $c ) {}

    public function register(): void {
        add_action(
            'rims_pro_expire_units',
            function (): void {
                $this->c->expiry_manager()->expire_all_due();
            }
        );
    }
}
