<?php
declare(strict_types=1);

namespace RimsPro\Domain;

enum ViewMode: string {
    case Card        = 'card';
    case Table       = 'table';
    case BrokerSheet = 'broker_sheet';
    case Map         = 'map';

    public static function default(): self {
        return self::Card;
    }

    /** @return string[] */
    public static function values(): array {
        return array_map( static fn( self $m ) => $m->value, self::cases() );
    }
}
