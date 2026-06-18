<?php
declare(strict_types=1);

namespace RimsPro\Domain;

/**
 * Money value object - stores amount in paise (integer) so arithmetic is exact.
 * Parses Indian-convention input (`3 Cr` = 3,00,00,000 rupees = 30,00,00,000 paise; `75 L` = 75,00,000 rupees).
 */
final readonly class Money {

    public function __construct(
        public int $amount, // in paise
    ) {
    }

    public static function zero(): self {
        return new self( 0 );
    }

    public static function fromRupees( int|float|string $rupees ): self {
        if ( is_string( $rupees ) ) {
            $rupees = (float) $rupees;
        }
        return new self( (int) round( ((float) $rupees) * 100 ) );
    }

    /**
     * Parse Indian shorthand: "3 Cr", "1.5 cr", "75 L", "75 lakh", "9000000".
     * Returns null on unparseable input.
     */
    public static function parse( string $input ): ?self {
        $s = trim( strtolower( $input ) );
        if ( $s === '' ) {
            return null;
        }
        // Strip currency symbols, commas, spaces around the number.
        $s = preg_replace( '/[,\₹\$]/', '', $s ) ?? $s;
        $s = trim( (string) $s );

        // Pattern: number optional unit (cr|crore|l|lac|lakh|k|thousand)
        if ( ! preg_match( '/^([0-9]+(?:\.[0-9]+)?)\s*(cr|crore|crores|l|lac|lakh|lakhs|k|thousand)?$/i', $s, $m ) ) {
            return null;
        }
        $value = (float) $m[1];
        $unit  = strtolower( $m[2] ?? '' );

        $rupees = match ( $unit ) {
            'cr', 'crore', 'crores' => $value * 1_00_00_000,
            'l', 'lac', 'lakh', 'lakhs' => $value * 1_00_000,
            'k', 'thousand' => $value * 1_000,
            default => $value,
        };

        return self::fromRupees( $rupees );
    }

    public function rupees(): float {
        return $this->amount / 100;
    }

    public function format(): string {
        $r = (int) round( $this->rupees() );
        if ( $r >= 1_00_00_000 ) {
            $cr = $r / 1_00_00_000;
            return rtrim( rtrim( number_format( $cr, 2 ), '0' ), '.' ) . ' Cr';
        }
        if ( $r >= 1_00_000 ) {
            $l = $r / 1_00_000;
            return rtrim( rtrim( number_format( $l, 2 ), '0' ), '.' ) . ' L';
        }
        return number_format( $r );
    }

    public function lessThanOrEqual( self $other ): bool {
        return $this->amount <= $other->amount;
    }

    public function equals( self $other ): bool {
        return $this->amount === $other->amount;
    }
}
