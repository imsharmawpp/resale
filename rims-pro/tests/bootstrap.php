<?php
/**
 * RIMS Pro test bootstrap.
 * Runs without WordPress - we shim the few WP helpers we use in the testable core.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'RIMS_PRO_VERSION' ) ) {
    define( 'RIMS_PRO_VERSION', 'test' );
}
if ( ! defined( 'RIMS_PRO_DIR' ) ) {
    define( 'RIMS_PRO_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'RIMS_PRO_URL' ) ) {
    define( 'RIMS_PRO_URL', 'https://example.test/wp-content/plugins/rims-pro/' );
}
if ( ! defined( 'RIMS_PRO_REST_NAMESPACE' ) ) {
    define( 'RIMS_PRO_REST_NAMESPACE', 'rims/v1' );
}
if ( ! defined( 'RIMS_PRO_DB_PREFIX' ) ) {
    define( 'RIMS_PRO_DB_PREFIX', 'rims_' );
}
if ( ! defined( 'RIMS_PRO_NONCE_ACTION' ) ) {
    define( 'RIMS_PRO_NONCE_ACTION', 'rims_pro_nonce' );
}
if ( ! defined( 'RIMS_PRO_TEXT_DOMAIN' ) ) {
    define( 'RIMS_PRO_TEXT_DOMAIN', 'rims-pro' );
}

// Shims for WP helpers used by the testable core.
if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
        return json_encode( $data, $options, $depth );
    }
}
if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type = 'mysql', $gmt = 0 ) {
        return gmdate( 'Y-m-d H:i:s' );
    }
}
if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value, ...$args ) { return $value; }
}
if ( ! function_exists( 'wp_parse_url' ) ) {
    function wp_parse_url( $url, $component = -1 ) {
        return parse_url( $url, $component === -1 ? -1 : $component );
    }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $s ) {
        return is_string( $s ) ? trim( strip_tags( $s ) ) : '';
    }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $s ) {
        return htmlspecialchars( (string) $s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
    }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $s ) {
        return htmlspecialchars( (string) $s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
    }
}
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $s ) {
        return filter_var( (string) $s, FILTER_SANITIZE_URL ) ?: '';
    }
}
if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '/' ) {
        return 'https://example.test' . ( $path && $path[0] === '/' ? $path : '/' . $path );
    }
}

// PSR-4 autoload for plugin code + tests.
spl_autoload_register(
    static function ( string $class ): void {
        $maps = [
            'RimsPro\\Tests\\' => __DIR__ . '/',
            'RimsPro\\'         => dirname( __DIR__ ) . '/includes/',
        ];
        foreach ( $maps as $prefix => $base ) {
            $len = strlen( $prefix );
            if ( strncmp( $prefix, $class, $len ) === 0 ) {
                $rel  = substr( $class, $len );
                $file = $base . str_replace( '\\', DIRECTORY_SEPARATOR, $rel ) . '.php';
                if ( file_exists( $file ) ) {
                    require_once $file;
                    return;
                }
            }
        }
    }
);
