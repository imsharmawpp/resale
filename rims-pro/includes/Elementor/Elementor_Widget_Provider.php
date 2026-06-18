<?php
declare(strict_types=1);

namespace RimsPro\Elementor;

use RimsPro\Core\Container;

/**
 * Registers Inventory Grid, Inventory Search, Featured Inventory, and Lead Form
 * widgets. Safe no-op when Elementor is inactive (Req 3.3-3.4).
 *
 * Implementation strategy: each widget delegates rendering to the corresponding
 * shortcode so editor preview and frontend output are identical.
 */
final class Elementor_Widget_Provider {

    public function __construct( private Container $c ) {}

    public function register( $widgets_manager ): void {
        if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
            return; // Elementor not loaded - silent no-op.
        }
        $widgets = $this->buildWidgets();
        foreach ( $widgets as $w ) {
            if ( method_exists( $widgets_manager, 'register' ) ) {
                $widgets_manager->register( $w );
            } elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
                $widgets_manager->register_widget_type( $w );
            }
        }
    }

    /** @return array<int, object> */
    private function buildWidgets(): array {
        if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
            return [];
        }
        require_once __DIR__ . '/Widgets/RIMS_Widget_Base.php';
        require_once __DIR__ . '/Widgets/Inventory_Grid_Widget.php';
        require_once __DIR__ . '/Widgets/Inventory_Search_Widget.php';
        require_once __DIR__ . '/Widgets/Featured_Inventory_Widget.php';
        require_once __DIR__ . '/Widgets/Lead_Form_Widget.php';

        return [
            new \RimsPro\Elementor\Widgets\Inventory_Grid_Widget(),
            new \RimsPro\Elementor\Widgets\Inventory_Search_Widget(),
            new \RimsPro\Elementor\Widgets\Featured_Inventory_Widget(),
            new \RimsPro\Elementor\Widgets\Lead_Form_Widget(),
        ];
    }
}
