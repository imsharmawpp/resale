<?php
declare(strict_types=1);

namespace RimsPro\Frontend;

use RimsPro\Core\Container;
use RimsPro\Domain\Inventory_Unit;

/**
 * Renders RIMS frontend output (hero, stats, carousel, cards/table/sheet/map,
 * detail pages, lead capture). All output uses the rims- CSS prefix and is
 * wrapped in .rims-root so it never bleeds into the host theme.
 */
final class Frontend_Renderer {

    public function __construct( private Container $c ) {}

    public function renderInventoryPage( array $atts = [] ): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;
        $branding  = $this->c->tenant_resolver()->branding( $tenant_id );
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );
        $featured  = $this->c->featured_carousel_service()->select( $units );
        $stats     = $this->c->statistics_aggregator()->compute( $units );
        $page      = $this->c->pagination_service()->page( $units, 1, 12 );
        $serial    = $this->c->field_visibility_serializer();
        $items_arr = $serial->serializeMany( $page['items'], current_user_can( 'manage_options' ) );

        include RIMS_PRO_DIR . 'templates/frontend/inventory-page.php';
    }

    public function renderBrokerSheet( array $atts = [] ): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;
        $branding  = $this->c->tenant_resolver()->branding( $tenant_id );
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );
        $rows      = $this->c->broker_sheet_formatter()->format( $units );
        $can_export = current_user_can( 'manage_options' );

        include RIMS_PRO_DIR . 'templates/frontend/broker-sheet.php';
    }

    public function renderUnitDetail( string $slug ): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );
        $unit      = null;
        foreach ( $units as $u ) {
            if ( $u->slug === $slug || (string) $u->id === $slug ) {
                $unit = $u;
                break;
            }
        }
        if ( ! $unit || $unit->expired ) {
            status_header( 404 );
            echo '<section class="rims-404"><h1>' . esc_html__( 'Listing not available', 'rims-pro' ) . '</h1></section>';
            return;
        }
        $branding         = $this->c->tenant_resolver()->branding( $tenant_id );
        $serializer       = $this->c->field_visibility_serializer();
        $include_internal = current_user_can( 'manage_options' );
        $payload          = $serializer->serialize( $unit, $include_internal );
        $contact_links    = [
            'whatsapp' => $this->c->contact_link_builder()->whatsapp( (string) ( $branding['contact_whatsapp'] ?? '' ), $unit ),
            'tel'      => $this->c->contact_link_builder()->tel( (string) ( $branding['contact_phone'] ?? '' ) ),
        ];
        include RIMS_PRO_DIR . 'templates/frontend/unit-detail.php';
    }

    public function renderProjectDetail( string $slug ): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;
        $project   = $this->c->project_repository()->findBySlug( $tenant_id, $slug );
        if ( ! $project ) {
            status_header( 404 );
            echo '<section class="rims-404"><h1>' . esc_html__( 'Project not found', 'rims-pro' ) . '</h1></section>';
            return;
        }
        $units    = array_filter(
            $this->c->inventory_repository()->listForTenant( $tenant_id ),
            static fn( Inventory_Unit $u ) => $u->project_id === $project->id
        );
        $nearby   = $this->c->nearby_place_repository()->forProject( $project->id );
        $branding = $this->c->tenant_resolver()->branding( $tenant_id );
        include RIMS_PRO_DIR . 'templates/frontend/project-detail.php';
    }

    public function renderHeroAndStats( array $atts = [] ): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );
        $stats     = $this->c->statistics_aggregator()->compute( $units );
        include RIMS_PRO_DIR . 'templates/frontend/hero-stats.php';
    }

    public function renderFeaturedCarousel( array $atts = [] ): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );
        $featured  = $this->c->featured_carousel_service()->select( $units );
        if ( empty( $featured ) ) {
            return; // omit when none flagged (Req 6.5)
        }
        include RIMS_PRO_DIR . 'templates/frontend/featured-carousel.php';
    }

    public function renderInventorySearch( array $atts = [] ): void {
        include RIMS_PRO_DIR . 'templates/frontend/inventory-search.php';
    }

    public function renderInventoryShortcode( array $atts = [] ): string {
        ob_start();
        $this->renderInventoryPage( $atts );
        return (string) ob_get_clean();
    }
}
