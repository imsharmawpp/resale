<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;
use RimsPro\Domain\UnitStatus;
use RimsPro\Domain\Validation_Result;
use RimsPro\Repositories\Inventory_Repository;
use RimsPro\Security\Schema_Validator;

/**
 * CRUD + status transitions for Inventory_Unit.
 *
 * - New units default to status `available` (Req 18.4).
 * - Status changes record `status_changed_at` (Req 18.6).
 * - Missing required fields are rejected with field-level messages (Req 18.7) - persists nothing.
 */
final class Inventory_Manager {

    public const SCHEMA = [
        'project_id'  => [ 'required' => true, 'type' => 'int' ],
        'unit_number' => [ 'required' => true, 'type' => 'string', 'max' => 50 ],
        'bhk'         => [ 'required' => true, 'type' => 'float' ],
        'area_sqft'   => [ 'required' => true, 'type' => 'int', 'min' => 1 ],
        'price'       => [ 'required' => true, 'type' => 'float' ],
        'tower'       => [ 'required' => false, 'type' => 'string', 'max' => 50 ],
        'floor'       => [ 'required' => false, 'type' => 'int' ],
        'facing'      => [ 'required' => false, 'type' => 'enum', 'in' => [ 'N','S','E','W','NE','NW','SE','SW' ] ],
        'owner_name'  => [ 'required' => false, 'type' => 'string', 'max' => 191 ],
        'owner_phone' => [ 'required' => false, 'type' => 'mobile' ],
        'broker_notes'=> [ 'required' => false, 'type' => 'string', 'max' => 5000 ],
        'status'      => [ 'required' => false, 'type' => 'enum', 'in' => [ 'available','blocked','token_received','under_negotiation','sold' ] ],
    ];

    public function __construct(
        private Inventory_Repository $repo,
        private Schema_Validator $validator,
        private Cache_Manager $cache,
    ) {
    }

    /**
     * @return array{result:Validation_Result, unit_id?:int}
     */
    public function create( int $tenant_id, array $input ): array {
        $check = $this->validator->validate( self::SCHEMA, $input );
        if ( ! $check->isOk() ) {
            return [ 'result' => $check ];
        }
        $unit                    = $this->hydrate( $input );
        $unit->status            = $unit->status === '' ? UnitStatus::Available->value : $unit->status;
        $unit->status_changed_at = current_time( 'mysql', true );
        $unit->published_at      = current_time( 'mysql', true );
        $unit->slug              = $this->slugify( $unit->project_name . '-' . $unit->unit_number . '-' . substr( md5( (string) microtime( true ) ), 0, 6 ) );

        $id = $this->repo->persist( $tenant_id, $unit );
        $this->cache->purgeGroup( "tenant:{$tenant_id}:inventory" );
        return [ 'result' => Validation_Result::ok(), 'unit_id' => $id ];
    }

    public function update_unit( int $tenant_id, int $id, array $patch ): Validation_Result {
        $existing = $this->repo->find( $tenant_id, $id );
        if ( ! $existing ) {
            return Validation_Result::fail( [ '_root' => 'Unit not found.' ] );
        }
        $merged = array_merge( $existing->toArray(), $patch );
        $check  = $this->validator->validate( self::SCHEMA, $merged );
        if ( ! $check->isOk() ) {
            return $check;
        }
        $unit = $this->hydrate( $merged );
        $unit->id = $id;
        $this->repo->persist( $tenant_id, $unit );
        $this->cache->purgeGroup( "tenant:{$tenant_id}:inventory" );
        return Validation_Result::ok();
    }

    public function delete_unit( int $tenant_id, int $id ): bool {
        $ok = $this->repo->delete_unit( $tenant_id, $id );
        if ( $ok ) {
            $this->cache->purgeGroup( "tenant:{$tenant_id}:inventory" );
        }
        return $ok;
    }

    public function changeStatus( int $tenant_id, int $id, string $status, ?string $changed_at = null ): Validation_Result {
        if ( ! in_array( $status, UnitStatus::values(), true ) ) {
            return Validation_Result::fail( [ 'status' => 'Invalid status.' ] );
        }
        $ok = $this->repo->changeStatus( $tenant_id, $id, $status, $changed_at );
        if ( $ok ) {
            $this->cache->purgeGroup( "tenant:{$tenant_id}:inventory" );
            return Validation_Result::ok();
        }
        return Validation_Result::fail( [ '_root' => 'Status update failed.' ] );
    }

    private function hydrate( array $row ): Inventory_Unit {
        $u                = new Inventory_Unit();
        $u->project_id    = (int) ( $row['project_id'] ?? 0 );
        $u->project_name  = (string) ( $row['project_name'] ?? '' );
        $u->builder       = (string) ( $row['builder'] ?? '' );
        $u->location      = (string) ( $row['location'] ?? '' );
        $u->sector        = (string) ( $row['sector'] ?? '' );
        $u->unit_number   = (string) ( $row['unit_number'] ?? '' );
        $u->tower         = (string) ( $row['tower'] ?? '' );
        $u->floor         = (int) ( $row['floor'] ?? 0 );
        $u->facing        = (string) ( $row['facing'] ?? 'N' );
        $u->bhk           = (float) ( $row['bhk'] ?? 2.0 );
        $u->is_penthouse  = ! empty( $row['is_penthouse'] );
        $u->area_sqft     = (int) ( $row['area_sqft'] ?? 0 );
        $u->price_paise   = isset( $row['price'] ) ? (int) round( ( (float) $row['price'] ) * 100 ) : (int) ( $row['price_paise'] ?? 0 );
        $u->price_label   = (string) ( $row['price_label'] ?? '' );
        $u->owner_name    = (string) ( $row['owner_name'] ?? '' );
        $u->owner_phone   = (string) ( $row['owner_phone'] ?? '' );
        $u->broker_notes  = (string) ( $row['broker_notes'] ?? '' );
        $u->status        = (string) ( $row['status'] ?? UnitStatus::Available->value );
        $u->is_featured   = ! empty( $row['is_featured'] );
        $u->slug          = (string) ( $row['slug'] ?? '' );
        $u->published_at  = (string) ( $row['published_at'] ?? '' );
        $u->expired       = ! empty( $row['expired'] );
        $u->latitude      = isset( $row['latitude'] ) ? (float) $row['latitude'] : null;
        $u->longitude     = isset( $row['longitude'] ) ? (float) $row['longitude'] : null;
        return $u;
    }

    private function slugify( string $s ): string {
        $s = strtolower( trim( $s ) );
        $s = preg_replace( '/[^a-z0-9]+/u', '-', $s ) ?? $s;
        $s = trim( (string) $s, '-' );
        return $s !== '' ? $s : (string) random_int( 100000, 999999 );
    }
}
