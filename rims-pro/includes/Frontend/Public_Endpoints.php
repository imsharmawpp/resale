<?php
declare(strict_types=1);

namespace RimsPro\Frontend;

use RimsPro\Core\Container;

/**
 * PWA manifest, service worker, and sitemap endpoints (Req 28.1, 28.2, 32.4).
 */
final class Public_Endpoints {

    public function __construct( private Container $c ) {}

    public function dispatch( string $endpoint ): void {
        switch ( $endpoint ) {
            case 'manifest':
                $this->manifest();
                break;
            case 'sw':
                $this->serviceWorker();
                break;
            case 'sitemap':
                $this->sitemap();
                break;
            default:
                status_header( 404 );
                echo 'Unknown endpoint';
        }
        exit;
    }

    private function manifest(): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;
        $branding  = $this->c->tenant_resolver()->branding( $tenant_id );
        header( 'Content-Type: application/manifest+json; charset=utf-8' );
        echo wp_json_encode( [
            'name'             => $branding['company_name'] ?? 'RIMS Pro',
            'short_name'       => $branding['company_name'] ?? 'RIMS',
            'description'      => 'Premium resale inventory.',
            'start_url'        => '/inventory',
            'display'          => 'standalone',
            'background_color' => '#ffffff',
            'theme_color'      => $branding['primary_color'] ?? '#1e3a8a',
            'icons'            => [
                [ 'src' => RIMS_PRO_URL . 'assets/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png' ],
                [ 'src' => RIMS_PRO_URL . 'assets/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png' ],
            ],
        ] );
    }

    private function serviceWorker(): void {
        header( 'Content-Type: application/javascript; charset=utf-8' );
        readfile( RIMS_PRO_DIR . 'assets/pwa/service-worker.js' );
    }

    private function sitemap(): void {
        $tenant_id = $this->c->tenant_resolver()->resolve()->id;
        $units     = $this->c->inventory_repository()->listForTenant( $tenant_id );
        $projects  = $this->c->project_repository()->listForTenant( $tenant_id );
        $urls      = $this->c->seo_engine()->sitemapUrls( $units, $projects, home_url() );
        header( 'Content-Type: application/xml; charset=utf-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ( $urls as $u ) {
            echo '  <url><loc>' . htmlspecialchars( $u, ENT_XML1, 'UTF-8' ) . '</loc></url>' . "\n";
        }
        echo '</urlset>' . "\n";
    }
}
