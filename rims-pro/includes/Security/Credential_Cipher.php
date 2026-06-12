<?php
declare(strict_types=1);

namespace RimsPro\Security;

/**
 * AES-256-GCM credential cipher. Round-trip safe; never stores plaintext.
 * Key derives from the configured RIMS_PRO_CRYPTO_KEY (or AUTH_KEY fallback).
 */
final class Credential_Cipher {

    private string $key;

    public function __construct( ?string $key = null ) {
        if ( $key !== null ) {
            $this->key = hash( 'sha256', $key, true );
            return;
        }
        $secret = defined( 'RIMS_PRO_CRYPTO_KEY' ) && constant( 'RIMS_PRO_CRYPTO_KEY' )
            ? (string) constant( 'RIMS_PRO_CRYPTO_KEY' )
            : ( defined( 'AUTH_KEY' ) ? (string) constant( 'AUTH_KEY' ) : 'rims-pro-default-key' );
        $this->key = hash( 'sha256', $secret, true );
    }

    public function encrypt( string $plaintext ): string {
        $iv     = random_bytes( 12 );
        $tag    = '';
        $cipher = openssl_encrypt( $plaintext, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag );
        if ( $cipher === false ) {
            throw new \RuntimeException( 'Encryption failed.' );
        }
        return base64_encode( $iv . $tag . $cipher );
    }

    public function decrypt( string $stored ): string {
        $raw = base64_decode( $stored, true );
        if ( $raw === false || strlen( $raw ) < 28 ) {
            throw new \RuntimeException( 'Decryption failed: malformed input.' );
        }
        $iv     = substr( $raw, 0, 12 );
        $tag    = substr( $raw, 12, 16 );
        $cipher = substr( $raw, 28 );
        $plain  = openssl_decrypt( $cipher, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag );
        if ( $plain === false ) {
            throw new \RuntimeException( 'Decryption failed.' );
        }
        return $plain;
    }
}
