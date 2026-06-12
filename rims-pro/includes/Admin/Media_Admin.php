<?php
declare(strict_types=1);

namespace RimsPro\Admin;

use RimsPro\Core\Container;

final class Media_Admin {

    public function __construct( private Container $c ) {}

    public function register(): void {
        add_submenu_page(
            'rims-pro',
            __( 'Media', 'rims-pro' ),
            __( 'Media', 'rims-pro' ),
            'manage_options',
            'rims-pro-media',
            [ $this, 'render' ]
        );
    }

    public function render(): void {
        include RIMS_PRO_DIR . 'templates/admin/media.php';
    }
}
