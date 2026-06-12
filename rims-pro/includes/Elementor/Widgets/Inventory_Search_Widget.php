<?php
declare(strict_types=1);

namespace RimsPro\Elementor\Widgets;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
    return;
}

final class Inventory_Search_Widget extends RIMS_Widget_Base {
    public function get_name() { return 'rims_inventory_search'; }
    public function get_title() { return __( 'RIMS Inventory Search', 'rims-pro' ); }
    public function get_icon() { return 'eicon-search'; }
    protected function render() {
        echo do_shortcode( '[inventory_search]' );
    }
}
