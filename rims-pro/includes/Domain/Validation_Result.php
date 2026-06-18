<?php
declare(strict_types=1);

namespace RimsPro\Domain;

final class Validation_Result {

    /** @param array<string, string[]> $errors */
    public function __construct(
        private bool $ok,
        private array $errors = [],
    ) {
    }

    public static function ok(): self {
        return new self( true, [] );
    }

    /** @param array<string, string|string[]> $errors */
    public static function fail( array $errors ): self {
        $normalized = [];
        foreach ( $errors as $field => $msgs ) {
            $normalized[ $field ] = is_array( $msgs ) ? array_values( $msgs ) : [ (string) $msgs ];
        }
        return new self( false, $normalized );
    }

    public function isOk(): bool {
        return $this->ok;
    }

    /** @return array<string, string[]> */
    public function errors(): array {
        return $this->errors;
    }

    public function firstError(): ?string {
        foreach ( $this->errors as $msgs ) {
            if ( ! empty( $msgs ) ) {
                return (string) reset( $msgs );
            }
        }
        return null;
    }
}
