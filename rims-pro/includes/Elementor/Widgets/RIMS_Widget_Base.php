<?php
declare(strict_types=1);

namespace RimsPro\Elementor\Widgets;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
    return;
}

abstract class RIMS_Widget_Base extends \Elementor\Widget_Base {

    public function get_categories() {
        return [ 'general' ];
    }

    public function get_keywords() {
        return [ 'rims', 'real estate', 'inventory' ];
    }
}
