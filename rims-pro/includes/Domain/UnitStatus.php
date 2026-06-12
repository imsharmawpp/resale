<?php
declare(strict_types=1);

namespace RimsPro\Domain;

enum UnitStatus: string {
    case Available        = 'available';
    case Blocked          = 'blocked';
    case TokenReceived    = 'token_received';
    case UnderNegotiation = 'under_negotiation';
    case Sold             = 'sold';

    /**
     * Default status-color map per design Req 18.5 / Property 11.
     * Each status maps to a distinct color. Tenants may override at runtime.
     *
     * @return array<string, string>
     */
    public static function default_color_map(): array {
        return [
            self::Available->value        => '#10b981', // green
            self::Blocked->value          => '#f97316', // orange
            self::TokenReceived->value    => '#3b82f6', // blue
            self::UnderNegotiation->value => '#a855f7', // distinct purple
            self::Sold->value             => '#6b7280', // gray
        ];
    }

    public function label(): string {
        return match ( $this ) {
            self::Available        => 'Available',
            self::Blocked          => 'Blocked',
            self::TokenReceived    => 'Token Received',
            self::UnderNegotiation => 'Under Negotiation',
            self::Sold             => 'Sold',
        };
    }

    /** @return string[] */
    public static function values(): array {
        return array_map( static fn( self $s ) => $s->value, self::cases() );
    }
}
