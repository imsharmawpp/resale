<?php
declare(strict_types=1);

namespace RimsPro\Services;

/**
 * Generates share-channel URLs that embed the record's public URL (Property 39 / Req 27).
 */
final class Share_Manager {

    public function whatsapp( string $public_url, string $title = '' ): string {
        $msg = trim( $title . ' ' . $public_url );
        return 'https://wa.me/?text=' . rawurlencode( $msg );
    }
    public function facebook( string $public_url ): string {
        return 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $public_url );
    }
    public function linkedin( string $public_url ): string {
        return 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $public_url );
    }
    public function telegram( string $public_url, string $title = '' ): string {
        return 'https://t.me/share/url?url=' . rawurlencode( $public_url ) . '&text=' . rawurlencode( $title );
    }
    public function email( string $public_url, string $subject = '' ): string {
        return 'mailto:?subject=' . rawurlencode( $subject ) . '&body=' . rawurlencode( $public_url );
    }

    /** @return array<string, string> */
    public function all( string $public_url, string $title = '' ): array {
        return [
            'whatsapp' => $this->whatsapp( $public_url, $title ),
            'facebook' => $this->facebook( $public_url ),
            'linkedin' => $this->linkedin( $public_url ),
            'telegram' => $this->telegram( $public_url, $title ),
            'email'    => $this->email( $public_url, $title ),
        ];
    }
}
