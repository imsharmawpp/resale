<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Repositories\Analytics_Repository;

/**
 * Records exactly one event per action; aggregates Most-Viewed-Projects /
 * Most-Viewed-Units rankings (Property 25 / Req 20.1-20.6).
 */
final class Analytics_Engine {

    public const EVENT_PAGE_VIEW      = 'page_view';
    public const EVENT_INVENTORY_VIEW = 'inventory_view';
    public const EVENT_WHATSAPP_CLICK = 'whatsapp_click';
    public const EVENT_PHONE_CLICK    = 'phone_click';
    public const EVENT_LEAD_GENERATED = 'lead_generated';

    public const EVENTS = [
        self::EVENT_PAGE_VIEW,
        self::EVENT_INVENTORY_VIEW,
        self::EVENT_WHATSAPP_CLICK,
        self::EVENT_PHONE_CLICK,
        self::EVENT_LEAD_GENERATED,
    ];

    public function __construct( private Analytics_Repository $repo ) {}

    public function record( int $tenant_id, string $event_type, ?int $unit_id, ?int $project_id, string $session_hash = '' ): bool {
        if ( ! in_array( $event_type, self::EVENTS, true ) ) {
            return false;
        }
        $this->repo->record( $tenant_id, $event_type, $unit_id, $project_id, $session_hash );
        return true;
    }

    /**
     * Rank entities by view counts in descending order. When no events
     * exist for the requested type, returns [] (rankings deferred per Req 20.8).
     *
     * @return array<int, array{entity_id:int, count:int}>
     */
    public function topProjects( int $tenant_id, int $limit = 10 ): array {
        return $this->repo->topByEvent( $tenant_id, self::EVENT_INVENTORY_VIEW, 'project_id', $limit );
    }

    /** @return array<int, array{entity_id:int, count:int}> */
    public function topUnits( int $tenant_id, int $limit = 10 ): array {
        return $this->repo->topByEvent( $tenant_id, self::EVENT_INVENTORY_VIEW, 'unit_id', $limit );
    }

    /**
     * Pure ranking helper for tests: counts events and returns descending order.
     *
     * @param array<int, array{event_type:string, entity_id:int}> $events
     * @return array<int, array{entity_id:int, count:int}>
     */
    public function rank( array $events, string $event_type ): array {
        $counts = [];
        foreach ( $events as $e ) {
            if ( ($e['event_type'] ?? '') !== $event_type ) {
                continue;
            }
            $id = (int) ( $e['entity_id'] ?? 0 );
            $counts[ $id ] = ( $counts[ $id ] ?? 0 ) + 1;
        }
        if ( empty( $counts ) ) {
            return [];
        }
        arsort( $counts );
        $out = [];
        foreach ( $counts as $id => $c ) {
            $out[] = [ 'entity_id' => (int) $id, 'count' => (int) $c ];
        }
        return $out;
    }
}
