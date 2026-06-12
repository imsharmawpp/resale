<?php
declare(strict_types=1);

namespace RimsPro\Tests\Property;

use RimsPro\Domain\FilterSet;
use RimsPro\Domain\Inventory_Unit;
use RimsPro\Domain\Lead;
use RimsPro\Domain\Project;

/**
 * Smart input generators used by every property test. Bias towards edge cases
 * (empty datasets, unicode, max-size files, missing coords, zero leads).
 */
final class Generators {

    public static function pickStatus(): string {
        $opts = [ 'available', 'blocked', 'token_received', 'under_negotiation', 'sold' ];
        return $opts[ mt_rand( 0, count( $opts ) - 1 ) ];
    }

    public static function pickFacing(): string {
        $opts = [ 'N', 'S', 'E', 'W', 'NE', 'NW', 'SE', 'SW' ];
        return $opts[ mt_rand( 0, count( $opts ) - 1 ) ];
    }

    public static function pickProject(): string {
        $opts = [ 'Skylark Heights', 'Marigold Estates', 'Palm Cove', 'Greens at Nineveh', 'Royal Pavilion', 'M3M Gallery', 'BPTP Astaire' ];
        return $opts[ mt_rand( 0, count( $opts ) - 1 ) ];
    }

    public static function pickBuilder(): string {
        $opts = [ 'Skylark', 'Marigold Group', 'Palm Realty', 'Nineveh Living', 'Royal Builders', 'M3M', 'BPTP' ];
        return $opts[ mt_rand( 0, count( $opts ) - 1 ) ];
    }

    public static function pickLocation(): string {
        $opts = [ 'Whitefield', 'Gachibowli', 'Sector 65', 'Hebbal', 'Chembur', 'Sushant Lok', 'Golf Course Road', 'Park Avenue' ];
        return $opts[ mt_rand( 0, count( $opts ) - 1 ) ];
    }

    public static function unit( int $i ): Inventory_Unit {
        $u                = new Inventory_Unit();
        $u->id            = $i + 1;
        $u->tenant_id     = 1;
        $u->project_id    = 1 + ( $i % 5 );
        $u->project_name  = self::pickProject();
        $u->builder       = self::pickBuilder();
        $u->location      = self::pickLocation();
        $u->sector        = 'Sector ' . ( 50 + ( $i % 50 ) );
        $u->unit_number   = 'UNIT-' . ( $i + 1 );
        $u->tower         = 'T' . ( 1 + ( $i % 6 ) );
        $u->floor         = mt_rand( 1, 40 );
        $u->facing        = self::pickFacing();
        $u->bhk           = (float) [ 2.0, 2.5, 3.0, 3.5, 4.0, 5.0 ][ mt_rand( 0, 5 ) ];
        $u->is_penthouse  = mt_rand( 0, 19 ) === 0;
        $u->area_sqft     = mt_rand( 600, 6500 );
        $u->price_paise   = mt_rand( 1, 200 ) * 1_00_00_000;
        $u->price_label   = [ '@ MP', '25:75 P/p', 'U/C', 'CR WITHOUT OC', '' ][ mt_rand( 0, 4 ) ];
        $u->owner_name    = 'Owner ' . ( $i + 1 );
        $u->owner_phone   = '+9198' . str_pad( (string) mt_rand( 0, 99999999 ), 8, '0', STR_PAD_LEFT );
        $u->broker_notes  = mt_rand( 0, 3 ) === 0 ? 'Confidential broker note ' . $i : '';
        $u->status        = self::pickStatus();
        $u->is_featured   = mt_rand( 0, 4 ) === 0;
        $u->slug          = 'unit-' . ( $i + 1 );
        $u->latitude      = mt_rand( 0, 1 ) ? mt_rand( -90000, 90000 ) / 1000 : null;
        $u->longitude     = $u->latitude !== null ? mt_rand( -180000, 180000 ) / 1000 : null;
        $u->published_at  = gmdate( 'Y-m-d H:i:s', time() - mt_rand( 0, 86400 * 200 ) );
        $u->created_at    = $u->published_at;
        $u->status_changed_at = $u->published_at;
        $u->expired       = false;
        return $u;
    }

    /** @return Inventory_Unit[] */
    public static function dataset( int $size ): array {
        $out = [];
        for ( $i = 0; $i < $size; $i++ ) {
            $out[] = self::unit( $i );
        }
        return $out;
    }

    public static function filterFor( Inventory_Unit $u ): FilterSet {
        return new FilterSet(
            project: mt_rand( 0, 1 ) ? $u->project_name : null,
            builder: mt_rand( 0, 1 ) ? $u->builder : null,
            location: mt_rand( 0, 1 ) ? $u->location : null,
            bhk: mt_rand( 0, 1 ) ? $u->bhk : null,
            facing: mt_rand( 0, 1 ) ? $u->facing : null,
            tower: mt_rand( 0, 1 ) ? $u->tower : null,
            floor: mt_rand( 0, 1 ) ? $u->floor : null,
            status: mt_rand( 0, 1 ) ? $u->status : null,
        );
    }

    public static function lead( int $i ): Lead {
        $l                 = new Lead();
        $l->id             = $i + 1;
        $l->tenant_id      = 1;
        $l->name           = 'Lead ' . ( $i + 1 );
        $l->mobile         = '+919876543210';
        $l->email          = mt_rand( 0, 1 ) ? "lead{$i}@example.com" : null;
        $l->source         = [ 'web', 'whatsapp', 'phone', 'referral' ][ mt_rand( 0, 3 ) ];
        $l->pipeline_stage = [ 'new','contacted','interested','visit_scheduled','negotiation','closed','lost' ][ mt_rand( 0, 6 ) ];
        $l->created_at     = gmdate( 'Y-m-d H:i:s', time() - mt_rand( 0, 86400 * 30 ) );
        return $l;
    }

    public static function project( int $i ): Project {
        return new Project(
            id: $i + 1,
            tenant_id: 1,
            name: self::pickProject(),
            slug: 'project-' . ( $i + 1 ),
            builder: self::pickBuilder(),
            location: self::pickLocation(),
            tower_count: mt_rand( 1, 6 ),
            possession_status: mt_rand( 0, 1 ) ? 'Ready' : 'U/C'
        );
    }

    public static function unitWithStatus( int $i, string $status ): Inventory_Unit {
        $u         = self::unit( $i );
        $u->status = $status;
        return $u;
    }

    public static function unicodeString(): string {
        $opts = [ 'राज भवन', 'Palmé Cove', '🌟 Featured', 'سلام', '上海大厦', 'Zürich' ];
        return $opts[ mt_rand( 0, count( $opts ) - 1 ) ];
    }
}
