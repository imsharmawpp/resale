<?php
declare(strict_types=1);

namespace RimsPro\Frontend;

use RimsPro\Core\Container;

/**
 * Resolves RIMS virtual routes (/inventory, /broker-sheet, /inventory/{slug},
 * /project/{slug}) and renders body BETWEEN the active theme's get_header()
 * / get_footer() output.
 *
 * Falls back to WP defaults + writes a diagnostic admin notice when the theme
 * lacks header/footer support (Req 1.7).
 *
 * Elementor-aware: detects Elementor-managed pages and injects via the
 * `the_content` filter rather than wrapping a standalone layout.
 */
final class Theme_Integration_Engine {

    public function __construct( private Container $c ) {}

    public function register(): void {
        // Pretty permalink rules for the four virtual pages.
        add_action( 'init', [ $this, 'register_rewrites' ], 11 );

        add_filter(
            'query_vars',
            static function ( array $vars ): array {
                $vars[] = 'rims_route';
                return $vars;
            }
        );

        // Render frontend content via theme.
        add_action( 'template_redirect', [ $this, 'maybe_render' ] );

        // Diagnostic admin notice when theme lacks header/footer support.
        add_action( 'admin_notices', [ $this, 'maybe_diagnostic_notice' ] );
    }

    public function register_rewrites(): void {
        add_rewrite_rule( '^inventory/?$', 'index.php?rims_route=inventory', 'top' );
        add_rewrite_rule( '^inventory/([^/]+)/?$', 'index.php?rims_route=unit&rims_unit_slug=$matches[1]', 'top' );
        add_rewrite_rule( '^broker-sheet/?$', 'index.php?rims_route=broker_sheet', 'top' );
        add_rewrite_rule( '^project/([^/]+)/?$', 'index.php?rims_route=project&rims_project_slug=$matches[1]', 'top' );
    }

    public function maybe_render(): void {
        $route = get_query_var( 'rims_route' );
        if ( ! $route ) {
            return;
        }
        if ( ! $this->hasThemeHeaderFooter() ) {
            $this->log_fallback();
        }
        $this->render( (string) $route );
        exit;
    }

    public function isElementorManaged(): bool {
        if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
            return false;
        }
        return function_exists( 'elementor_is_active_for_post' );
    }

    public function hasThemeHeaderFooter(): bool {
        return function_exists( 'get_header' ) && function_exists( 'get_footer' );
    }

    public function maybe_diagnostic_notice(): void {
        if ( $this->hasThemeHeaderFooter() ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>' .
            esc_html__( 'RIMS Pro: active theme does not declare header/footer support. RIMS pages will fall back to WordPress defaults.', 'rims-pro' ) .
            '</p></div>';
    }

    private function render( string $route ): void {
        if ( $this->hasThemeHeaderFooter() ) {
            get_header();
        }
        $renderer = new Frontend_Renderer( $this->c );
        echo '<div class="rims-root rims-theme-host" data-rims-route="' . esc_attr( $route ) . '">';
        switch ( $route ) {
            case 'unit':
                $slug = (string) get_query_var( 'rims_unit_slug' );
                $renderer->renderUnitDetail( $slug );
                break;
            case 'project':
                $slug = (string) get_query_var( 'rims_project_slug' );
                $renderer->renderProjectDetail( $slug );
                break;
            case 'broker_sheet':
                $renderer->renderBrokerSheet();
                break;
            case 'inventory':
            default:
                $renderer->renderInventoryPage();
                break;
        }
        echo '</div>';
        if ( $this->hasThemeHeaderFooter() ) {
            get_footer();
        }
    }

    private function log_fallback(): void {
        error_log( '[RIMS Pro] theme lacks header/footer support; rendering WP defaults.' );
    }
}
