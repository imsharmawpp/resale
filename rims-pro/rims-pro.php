<?php
declare(strict_types=1);
/**
 * Plugin Name:       RIMS Pro - Resale Inventory Management System
 * Plugin URI:        https://goldlineestate.com/rims-pro
 * Description:       Enterprise-grade real estate resale inventory platform with native theme integration, four view modes, lead management, AI content, exports, and multi-tenant SaaS readiness.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            GoldLine Estate
 * Author URI:        https://goldlineestate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rims-pro
 * Domain Path:       /languages
 *
 * @package RimsPro
 */

// PHP version gate — bail early with a friendly admin notice on PHP < 8.1.
if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>RIMS Pro</strong> requires PHP 8.1 or later. Your server is running PHP ' . PHP_VERSION . '. Please upgrade PHP to activate this plugin.</p></div>';
    } );
    return; // Stop loading entirely — prevents parse errors from enum syntax.
}

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RIMS_PRO_VERSION', '1.0.0' );
define( 'RIMS_PRO_FILE', __FILE__ );
define( 'RIMS_PRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'RIMS_PRO_URL', plugin_dir_url( __FILE__ ) );
define( 'RIMS_PRO_BASENAME', plugin_basename( __FILE__ ) );
define( 'RIMS_PRO_REST_NAMESPACE', 'rims/v1' );
define( 'RIMS_PRO_TEXT_DOMAIN', 'rims-pro' );
define( 'RIMS_PRO_DB_PREFIX', 'rims_' );
define( 'RIMS_PRO_NONCE_ACTION', 'rims_pro_nonce' );

// Composer autoloader (vendored). In production zips vendor/ is removed entirely;
// wrap in try-catch so dev-only packages never cause a fatal in production.
$rims_pro_autoload = RIMS_PRO_DIR . 'vendor/autoload.php';
if ( file_exists( $rims_pro_autoload ) ) {
    try {
        require_once $rims_pro_autoload;
    } catch ( \Throwable $e ) {
        // Dev environment only; production zip has no vendor/.
    }
}

// PSR-4 fallback autoloader for the plugin's own classes (works without composer install).
spl_autoload_register(
    static function ( string $class ): void {
        $prefix   = 'RimsPro\\';
        $base_dir = RIMS_PRO_DIR . 'includes/';
        $len      = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }
        $relative = substr( $class, $len );
        $path     = $base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
);

// Activation / Deactivation hooks.
register_activation_hook(
    __FILE__,
    static function (): void {
        ( new \RimsPro\Core\Activator() )->activate();
    }
);
register_deactivation_hook(
    __FILE__,
    static function (): void {
        ( new \RimsPro\Core\Deactivator() )->deactivate();
    }
);

// Boot the plugin on plugins_loaded so all of WordPress is available.
add_action(
    'plugins_loaded',
    static function (): void {
        \RimsPro\Core\Plugin::instance()->boot();
    },
    5
);
