<?php
declare(strict_types=1);

namespace RimsPro\Shortcodes;

use RimsPro\Core\Container;
use RimsPro\Frontend\Frontend_Renderer;

/**
 * Registers [resale_inventory], [featured_inventory], [inventory_sheet], [inventory_search].
 * Renders within the theme's content area; attribute -> filter mapping (Req 2.3-2.8).
 */
final class Shortcode_Registrar {

    public function __construct( private Container $c ) {}

    public function register(): void {
        add_shortcode( 'resale_inventory', [ $this, 'inventory' ] );
        add_shortcode( 'featured_inventory', [ $this, 'featured' ] );
        add_shortcode( 'inventory_sheet', [ $this, 'sheet' ] );
        add_shortcode( 'inventory_search', [ $this, 'search' ] );
    }

    public function inventory( array $atts = [] ): string {
        $atts = shortcode_atts( [
            'project' => '',
            'builder' => '',
            'bhk'     => '',
            'status'  => '',
        ], $atts ?: [] );
        $renderer = new Frontend_Renderer( $this->c );
        ob_start();
        $renderer->renderInventoryPage( $atts );
        return (string) ob_get_clean();
    }

    public function featured( array $atts = [] ): string {
        $renderer = new Frontend_Renderer( $this->c );
        ob_start();
        $renderer->renderFeaturedCarousel( (array) $atts );
        return (string) ob_get_clean();
    }

    public function sheet( array $atts = [] ): string {
        $renderer = new Frontend_Renderer( $this->c );
        ob_start();
        $renderer->renderBrokerSheet( (array) $atts );
        return (string) ob_get_clean();
    }

    public function search( array $atts = [] ): string {
        $renderer = new Frontend_Renderer( $this->c );
        ob_start();
        $renderer->renderInventorySearch( (array) $atts );
        return (string) ob_get_clean();
    }
}
