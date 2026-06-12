<?php
declare(strict_types=1);

namespace RimsPro\Core;

use RimsPro\Admin\Admin_Dashboard;
use RimsPro\Admin\Inventory_Admin;
use RimsPro\Admin\Lead_Admin;
use RimsPro\Admin\Media_Admin;
use RimsPro\Admin\Settings_Admin;
use RimsPro\Ajax\Ajax_Router;
use RimsPro\Elementor\Elementor_Widget_Provider;
use RimsPro\Frontend\Frontend_Renderer;
use RimsPro\Frontend\Theme_Integration_Engine;
use RimsPro\Rest\Rest_Router;
use RimsPro\Services\Cron_Scheduler;
use RimsPro\Shortcodes\Shortcode_Registrar;

/**
 * Core plugin entry point - wires every subsystem on plugins_loaded.
 */
final class Plugin {

    private static ?self $instance = null;

    private Container $container;

    private bool $booted = false;

    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->container = new Container();
    }

    public function container(): Container {
        return $this->container;
    }

    public function boot(): void {
        if ( $this->booted ) {
            return;
        }
        $this->booted = true;

        // i18n.
        add_action(
            'init',
            static function (): void {
                if ( function_exists( 'load_plugin_textdomain' ) ) {
                    load_plugin_textdomain( 'rims-pro', false, dirname( RIMS_PRO_BASENAME ) . '/languages' );
                }
            }
        );

        // REST routes.
        add_action(
            'rest_api_init',
            function (): void {
                ( new Rest_Router( $this->container ) )->register();
            }
        );

        // AJAX handlers (logged-in + nopriv).
        ( new Ajax_Router( $this->container ) )->register();

        // Shortcodes.
        ( new Shortcode_Registrar( $this->container ) )->register();

        // Elementor widgets (safe no-op when Elementor inactive).
        add_action(
            'elementor/widgets/register',
            function ( $widgets_manager ): void {
                ( new Elementor_Widget_Provider( $this->container ) )->register( $widgets_manager );
            }
        );

        // Frontend routing + theme integration (front of the site).
        add_action(
            'init',
            function (): void {
                ( new Theme_Integration_Engine( $this->container ) )->register();
            }
        );

        // Frontend asset enqueue.
        add_action(
            'wp_enqueue_scripts',
            function (): void {
                $this->enqueue_frontend_assets();
            }
        );

        // Admin screens.
        if ( is_admin() ) {
            add_action(
                'admin_menu',
                function (): void {
                    ( new Admin_Dashboard( $this->container ) )->register();
                    ( new Inventory_Admin( $this->container ) )->register();
                    ( new Lead_Admin( $this->container ) )->register();
                    ( new Media_Admin( $this->container ) )->register();
                    ( new Settings_Admin( $this->container ) )->register();
                }
            );
            add_action(
                'admin_enqueue_scripts',
                function ( string $hook ): void {
                    $this->enqueue_admin_assets( $hook );
                }
            );
        }

        // Cron schedules.
        ( new Cron_Scheduler( $this->container ) )->register();

        // PWA + sitemap output.
        add_action( 'init', [ $this, 'register_public_endpoints' ] );

        // HTTPS enforcement for REST/AJAX writes (logs only when not HTTPS).
        add_action( 'rest_api_init', [ $this, 'enforce_https_notice' ] );
    }

    private function enqueue_frontend_assets(): void {
        $version = RIMS_PRO_VERSION;

        wp_register_style(
            'rims-pro-frontend',
            RIMS_PRO_URL . 'assets/dist/css/rims-frontend.css',
            [],
            $version
        );
        wp_enqueue_style( 'rims-pro-frontend' );

        // Alpine.js (lightweight, deferred).
        wp_register_script(
            'rims-pro-alpine',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js',
            [],
            '3.14.1',
            true
        );
        wp_enqueue_script( 'rims-pro-alpine' );

        wp_register_script(
            'rims-pro-frontend',
            RIMS_PRO_URL . 'assets/dist/js/rims-frontend.js',
            [],
            $version,
            true
        );

        $tenant   = $this->container->tenant_resolver()->resolve();
        $branding = $this->container->tenant_resolver()->branding( $tenant->id );

        wp_localize_script(
            'rims-pro-frontend',
            'RimsProConfig',
            [
                'restUrl'        => esc_url_raw( rest_url( RIMS_PRO_REST_NAMESPACE . '/' ) ),
                'ajaxUrl'        => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
                'nonce'          => wp_create_nonce( RIMS_PRO_NONCE_ACTION ),
                'restNonce'      => wp_create_nonce( 'wp_rest' ),
                'tenantId'       => (int) $tenant->id,
                'branding'       => $branding,
                'statusColors'   => $branding['status_color_map'] ?? [],
                'infiniteScroll' => (bool) ( $branding['infinite_scroll_enabled'] ?? true ),
            ]
        );
        wp_enqueue_script( 'rims-pro-frontend' );
    }

    private function enqueue_admin_assets( string $hook ): void {
        if ( strpos( $hook, 'rims-pro' ) === false && strpos( $hook, 'rims_pro' ) === false ) {
            return;
        }
        wp_enqueue_style(
            'rims-pro-admin',
            RIMS_PRO_URL . 'assets/dist/css/rims-admin.css',
            [],
            RIMS_PRO_VERSION
        );
        wp_enqueue_script(
            'rims-pro-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.js',
            [],
            '4.4.4',
            true
        );
        wp_enqueue_script(
            'rims-pro-admin',
            RIMS_PRO_URL . 'assets/dist/js/rims-admin.js',
            [ 'rims-pro-chartjs', 'jquery' ],
            RIMS_PRO_VERSION,
            true
        );
        wp_localize_script(
            'rims-pro-admin',
            'RimsProAdminConfig',
            [
                'ajaxUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
                'restUrl' => esc_url_raw( rest_url( RIMS_PRO_REST_NAMESPACE . '/' ) ),
                'nonce'   => wp_create_nonce( RIMS_PRO_NONCE_ACTION ),
            ]
        );
    }

    public function register_public_endpoints(): void {
        // PWA manifest endpoint.
        add_rewrite_rule( '^rims-manifest\.webmanifest$', 'index.php?rims_endpoint=manifest', 'top' );
        // Service worker endpoint.
        add_rewrite_rule( '^rims-sw\.js$', 'index.php?rims_endpoint=sw', 'top' );
        // Sitemap.
        add_rewrite_rule( '^rims-sitemap\.xml$', 'index.php?rims_endpoint=sitemap', 'top' );

        add_filter(
            'query_vars',
            static function ( array $vars ): array {
                $vars[] = 'rims_endpoint';
                $vars[] = 'rims_unit_slug';
                $vars[] = 'rims_project_slug';
                return $vars;
            }
        );

        add_action(
            'template_redirect',
            function (): void {
                $endpoint = get_query_var( 'rims_endpoint' );
                if ( ! $endpoint ) {
                    return;
                }
                ( new \RimsPro\Frontend\Public_Endpoints( $this->container ) )->dispatch( (string) $endpoint );
            }
        );
    }

    public function enforce_https_notice(): void {
        if ( ! is_ssl() && ! defined( 'WP_DEBUG' ) ) {
            // Log diagnostic notice; we do not block to avoid breaking dev environments.
            error_log( '[RIMS Pro] REST endpoints should be served over HTTPS in production.' );
        }
    }
}
