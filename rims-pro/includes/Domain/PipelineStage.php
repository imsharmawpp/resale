<?php
declare(strict_types=1);

namespace RimsPro\Domain;

enum PipelineStage: string {
    case New_              = 'new';
    case Contacted         = 'contacted';
    case Interested        = 'interested';
    case VisitScheduled    = 'visit_scheduled';
    case Negotiation       = 'negotiation';
    case Closed            = 'closed';
    case Lost              = 'lost';

    /** @return self[] (in canonical order) */
    public static function ordered(): array {
        return [
            self::New_,
            self::Contacted,
            self::Interested,
            self::VisitScheduled,
            self::Negotiation,
            self::Closed,
            self::Lost,
        ];
    }

    public function label(): string {
        return match ( $this ) {
            self::New_           => 'New',
            self::Contacted      => 'Contacted',
            self::Interested     => 'Interested',
            self::VisitScheduled => 'Visit Scheduled',
            self::Negotiation    => 'Negotiation',
            self::Closed         => 'Closed',
            self::Lost           => 'Lost',
        };
    }

    /** @return string[] */
    public static function values(): array {
        return array_map( static fn( self $s ) => $s->value, self::cases() );
    }
}
