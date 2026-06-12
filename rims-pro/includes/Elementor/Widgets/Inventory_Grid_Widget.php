<?php
declare(strict_types=1);

namespace RimsPro\Elementor\Widgets;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
    return;
}

final class Inventory_Grid_Widget extends RIMS_Widget_Base {
    public function get_name() { return 'rims_inventory_grid'; }
    public function get_title() { return __( 'RIMS Inventory Grid', 'rims-pro' ); }
    public function get_icon() { return 'eicon-posts-grid'; }
    protected function register_controls() {
        $this->start_controls_section( 'rims_section', [ 'label' => __( 'RIMS Filters', 'rims-pro' ) ] );
        $this->add_control( 'project', [ 'label' => 'Project', 'type' => \Elementor\Controls_Manager::TEXT ] );
        $this->add_control( 'builder', [ 'label' => 'Builder', 'type' => \Elementor\Controls_Manager::TEXT ] );
        $this->add_control( 'bhk', [ 'label' => 'BHK', 'type' => \Elementor\Controls_Manager::TEXT ] );
        $this->add_control( 'status', [ 'label' => 'Status', 'type' => \Elementor\Controls_Manager::TEXT ] );
        $this->end_controls_section();
    }
    protected function render() {
        $s = $this->get_settings_for_display();
        echo do_shortcode( sprintf(
            '[resale_inventory project="%s" builder="%s" bhk="%s" status="%s"]',
            esc_attr( (string) ( $s['project'] ?? '' ) ),
            esc_attr( (string) ( $s['builder'] ?? '' ) ),
            esc_attr( (string) ( $s['bhk'] ?? '' ) ),
            esc_attr( (string) ( $s['status'] ?? '' ) )
        ) );
    }
}
