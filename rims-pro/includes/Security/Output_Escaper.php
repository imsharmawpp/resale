<?php
declare(strict_types=1);

namespace RimsPro\Security;

/**
 * Context-aware escaping. Falls back to plain-text encoding if WP helpers are unavailable.
 */
final class Output_Escaper {

    public function html( string $value ): string {
        return function_exists( 'esc_html' )
            ? esc_html( $value )
            : htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
    }

    public function attr( string $value ): string {
        return function_exists( 'esc_attr' )
            ? esc_attr( $value )
            : htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
    }

    public function url( string $value ): string {
        if ( function_exists( 'esc_url' ) ) {
            return esc_url( $value );
        }
        $sanitized = filter_var( $value, FILTER_SANITIZE_URL );
        return $sanitized !== false ? (string) $sanitized : '';
    }

    public function js( string $value ): string {
        return function_exists( 'esc_js' )
            ? esc_js( $value )
            : addslashes( $value );
    }
}
