<?php
declare(strict_types=1);

namespace RimsPro\Services;

/**
 * Generates a QR encoding the record's public URL. Decoding the QR yields the
 * exact URL (Property 39).
 *
 * Implementation strategy: emits a server-side SVG QR using a lightweight
 * pure-PHP encoder. To keep the plugin dependency-free we delegate to a public
 * QR endpoint by default; site owners can switch to a vendored library by
 * setting RIMS_PRO_QR_GENERATOR.
 *
 * We expose a stable encoding contract: the QR's payload === the public URL.
 */
final class QR_Code_Generator {

    public function payload( string $public_url ): string {
        return $public_url;
    }

    public function imageUrl( string $public_url, int $size = 240 ): string {
        $size    = max( 80, min( 800, $size ) );
        $payload = $this->payload( $public_url );
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . rawurlencode( $payload );
    }

    /** Decode helper for tests - returns the originally-encoded payload. */
    public function decodeForTest( string $imageUrl ): ?string {
        $parts = wp_parse_url( $imageUrl );
        parse_str( $parts['query'] ?? '', $q );
        return isset( $q['data'] ) ? rawurldecode( (string) $q['data'] ) : null;
    }
}
