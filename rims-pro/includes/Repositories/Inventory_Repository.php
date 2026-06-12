<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

use RimsPro\Domain\Inventory_Unit;

class Inventory_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'inventory_units';
    }

    /** @return Inventory_Unit[] */
    public function listForTenant( int $tenant_id, bool $includeExpired = false ): array {
        $rows = $this->selectAll(
            $tenant_id,
            $includeExpired ? [] : [ 'expired' => 0 ],
            'ORDER BY id DESC'
        );
        return array_map( static fn( array $r ) => Inventory_Unit::fromArray( $r ), $rows );
    }

    public function find( int $tenant_id, int $id ): ?Inventory_Unit {
        $row = $this->selectOne( $tenant_id, [ 'id' => $id ] );
        return $row ? Inventory_Unit::fromArray( $row ) : null;
    }

    public function persist( int $tenant_id, Inventory_Unit $unit ): int {
        $data = [
            'project_id'        => $unit->project_id,
            'unit_number'       => $unit->unit_number,
            'tower'             => $unit->tower,
            'floor'             => $unit->floor,
            'facing'            => $unit->facing,
            'bhk'               => $unit->bhk,
            'is_penthouse'      => $unit->is_penthouse ? 1 : 0,
            'area_sqft'         => $unit->area_sqft,
            'price'             => $unit->price_paise / 100,
            'price_label'       => $unit->price_label,
            'owner_name'        => $unit->owner_name,
            'owner_phone'       => $unit->owner_phone,
            'broker_notes'      => $unit->broker_notes,
            'status'            => $unit->status,
            'status_changed_at' => $unit->status_changed_at,
            'is_featured'       => $unit->is_featured ? 1 : 0,
            'published_at'      => $unit->published_at ?? current_time( 'mysql', true ),
            'expired'           => $unit->expired ? 1 : 0,
            'slug'              => $unit->slug,
            'latitude'          => $unit->latitude,
            'longitude'         => $unit->longitude,
        ];
        if ( $unit->id > 0 ) {
            $this->update( $tenant_id, $unit->id, $data );
            return $unit->id;
        }
        return $this->insert( $tenant_id, $data );
    }

    public function delete_unit( int $tenant_id, int $id ): bool {
        return $this->delete( $tenant_id, $id );
    }

    public function changeStatus( int $tenant_id, int $id, string $status, ?string $changed_at = null ): bool {
        return $this->update( $tenant_id, $id, [
            'status'            => $status,
            'status_changed_at' => $changed_at ?? current_time( 'mysql', true ),
        ] );
    }

    public function markExpired( int $tenant_id, int $id ): bool {
        return $this->update( $tenant_id, $id, [ 'expired' => 1 ] );
    }

    public function renew( int $tenant_id, int $id, string $previous_status ): bool {
        return $this->update( $tenant_id, $id, [
            'expired'           => 0,
            'status'            => $previous_status,
            'published_at'      => current_time( 'mysql', true ),
            'status_changed_at' => current_time( 'mysql', true ),
        ] );
    }

    /** @return Inventory_Unit[] */
    public function listFeatured( int $tenant_id ): array {
        $rows = $this->selectAll( $tenant_id, [ 'is_featured' => 1, 'expired' => 0 ], 'ORDER BY id DESC' );
        return array_map( static fn( array $r ) => Inventory_Unit::fromArray( $r ), $rows );
    }
}
