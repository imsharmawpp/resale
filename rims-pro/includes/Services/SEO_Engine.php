<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;
use RimsPro\Domain\Project;

/**
 * Slugs (URL-safe + tenant-unique), title/meta derivation, project JSON-LD,
 * sitemap URL set (Properties 43-46 / Req 32).
 */
final class SEO_Engine {

    public function slugForUnit( Inventory_Unit $u, array $existing_slugs = [] ): string {
        $base = $this->slugify( $u->project_name . ' ' . $u->bhk . ' BHK ' . $u->unit_number );
        if ( $base === '' ) {
            $base = 'unit-' . $u->id;
        }
        return $this->disambiguate( $base, $existing_slugs );
    }

    public function slugForProject( Project $p, array $existing_slugs = [] ): string {
        $base = $this->slugify( $p->name . ' ' . $p->location );
        if ( $base === '' ) {
            $base = 'project-' . $p->id;
        }
        return $this->disambiguate( $base, $existing_slugs );
    }

    private function slugify( string $s ): string {
        $s = strtolower( trim( $s ) );
        // Transliterate to ASCII when possible.
        if ( function_exists( 'iconv' ) ) {
            $t = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $s );
            if ( is_string( $t ) ) {
                $s = $t;
            }
        }
        $s = preg_replace( '/[^a-z0-9]+/i', '-', $s ) ?? $s;
        $s = trim( strtolower( (string) $s ), '-' );
        return $s;
    }

    /** @param string[] $existing */
    private function disambiguate( string $base, array $existing ): string {
        $set = array_flip( $existing );
        if ( ! isset( $set[ $base ] ) ) {
            return $base;
        }
        $n = 2;
        while ( isset( $set[ $base . '-' . $n ] ) ) {
            $n++;
        }
        return $base . '-' . $n;
    }

    /** @return array{title:string, description:string} */
    public function metadataForUnit( Inventory_Unit $u ): array {
        $title = sprintf(
            '%s | %g BHK %s, %d sqft for sale in %s',
            $u->project_name ?: 'Unit ' . $u->id,
            $u->bhk,
            $u->is_penthouse ? 'Penthouse' : 'Unit',
            $u->area_sqft,
            $u->location ?: 'Unknown'
        );
        $desc  = sprintf(
            '%g BHK in %s by %s, %d sqft, floor %d, %s.',
            $u->bhk,
            $u->project_name,
            $u->builder ?: 'Builder',
            $u->area_sqft,
            $u->floor,
            $u->price_label !== '' ? $u->price_label : 'Price on request'
        );
        return [ 'title' => $title, 'description' => $desc ];
    }

    /** @return array{title:string, description:string} */
    public function metadataForProject( Project $p ): array {
        $title = sprintf( '%s by %s in %s', $p->name, $p->builder ?: 'Builder', $p->location ?: 'India' );
        $desc  = $p->seo_description !== '' ? $p->seo_description : sprintf(
            'Explore %s in %s by %s. %s. %d towers.',
            $p->name,
            $p->location,
            $p->builder,
            $p->possession_status ?: 'Available now',
            $p->tower_count
        );
        return [ 'title' => $title, 'description' => $desc ];
    }

    /** @return array<string, mixed> */
    public function jsonLdForProject( Project $p, string $base_url ): array {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Residence',
            'name'     => $p->name,
            'url'      => rtrim( $base_url, '/' ) . '/project/' . rawurlencode( $p->slug ?: (string) $p->id ),
            'address'  => [
                '@type'           => 'PostalAddress',
                'addressLocality' => $p->location,
                'addressRegion'   => $p->sector,
                'addressCountry'  => 'IN',
            ],
            'geo'         => $p->latitude !== null && $p->longitude !== null ? [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $p->latitude,
                'longitude' => $p->longitude,
            ] : null,
            'description' => $p->overview,
            'numberOfRooms' => null,
        ];
    }

    /**
     * @param Inventory_Unit[] $units
     * @param Project[]        $projects
     * @return string[]
     */
    public function sitemapUrls( array $units, array $projects, string $base_url ): array {
        $urls = [];
        foreach ( $projects as $p ) {
            $urls[] = rtrim( $base_url, '/' ) . '/project/' . rawurlencode( $p->slug ?: (string) $p->id );
        }
        foreach ( $units as $u ) {
            if ( $u->expired ) {
                continue;
            }
            $urls[] = rtrim( $base_url, '/' ) . '/inventory/' . rawurlencode( $u->slug ?: (string) $u->id );
        }
        return array_values( array_unique( $urls ) );
    }
}
