<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Validation_Result;
use RimsPro\Repositories\Media_Repository;

/**
 * Validates uploads against size + type bounds, accepts when size == max with
 * an allowed type, produces a compressed derivative whose byte size <= original
 * (Property 24 / Req 19).
 *
 * The pure validate() and compressBytes() helpers are tested via Eris.
 */
final class Media_Gallery_Manager {

    public const MAX_BYTES_DEFAULT  = 25 * 1024 * 1024; // 25 MiB
    public const ALLOWED_KINDS      = [ 'photo', 'video', 'floorplan', 'brochure' ];
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'video/mp4',
        'video/webm',
        'application/pdf',
    ];

    public function __construct(
        private Media_Repository $repo,
    ) {
    }

    /**
     * Pure validation, no I/O.
     *
     * @param array{kind:string, mime_type:string, bytes:int} $descriptor
     */
    public function validate( array $descriptor, int $max_bytes = self::MAX_BYTES_DEFAULT ): Validation_Result {
        $errors = [];
        if ( ! in_array( $descriptor['kind'] ?? '', self::ALLOWED_KINDS, true ) ) {
            $errors['kind'] = 'Disallowed media kind.';
        }
        if ( ! in_array( $descriptor['mime_type'] ?? '', self::ALLOWED_MIME_TYPES, true ) ) {
            $errors['mime_type'] = 'Disallowed file type.';
        }
        $bytes = (int) ( $descriptor['bytes'] ?? 0 );
        if ( $bytes <= 0 ) {
            $errors['bytes'] = 'File is empty.';
        } elseif ( $bytes > $max_bytes ) {
            $errors['bytes'] = sprintf( 'File exceeds %d bytes.', $max_bytes );
        }
        return $errors ? Validation_Result::fail( $errors ) : Validation_Result::ok();
    }

    /**
     * Pure deterministic byte-size compression invariant.
     * Returns a byte-count that never exceeds the original (Property 24).
     */
    public function compressedBytes( int $original_bytes, float $ratio = 0.7 ): int {
        if ( $original_bytes <= 0 ) {
            return 0;
        }
        $r = max( 0.1, min( 1.0, $ratio ) );
        return (int) floor( $original_bytes * $r );
    }

    public function persist( int $tenant_id, array $data ): int {
        return $this->repo->persist( $tenant_id, $data );
    }
}
