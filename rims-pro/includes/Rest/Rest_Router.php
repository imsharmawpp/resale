<?php
declare(strict_types=1);

namespace RimsPro\Rest;

use RimsPro\Core\Container;

/**
 * Registers all REST routes under rims/v1.
 */
final class Rest_Router {

    public function __construct( private Container $c ) {}

    public function register(): void {
        ( new Inventory_Controller( $this->c ) )->register_routes();
        ( new Project_Controller( $this->c ) )->register_routes();
        ( new Lead_Controller( $this->c ) )->register_routes();
    }
}
