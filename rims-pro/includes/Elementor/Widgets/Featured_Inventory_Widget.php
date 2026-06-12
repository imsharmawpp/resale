<?php
declare(strict_types=1);

namespace RimsPro\Elementor\Widgets;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
    return;
}

final class Featured_Inventory_Widget extends RIMS_Widget_Base {
    public function get_name() { return 'rims_featured_inventory'; }
    public function get_title() { return __( 'RIMS Featured Inventory', 'rims-pro' ); }
    public function get_icon() { return 'eicon-slideshow'; }
    protected function render() {
        echo do_shortcode( '[featured_inventory]' );
    }
}
