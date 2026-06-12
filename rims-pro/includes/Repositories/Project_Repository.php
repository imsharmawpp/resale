<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

use RimsPro\Domain\Project;

class Project_Repository extends Base_Repository {

    protected function table_suffix(): string {
        return 'projects';
    }

    /** @return Project[] */
    public function listForTenant( int $tenant_id ): array {
        return array_map(
            static fn( array $r ) => Project::fromArray( $r ),
            $this->selectAll( $tenant_id, [], 'ORDER BY name ASC' )
        );
    }

    public function find( int $tenant_id, int $id ): ?Project {
        $row = $this->selectOne( $tenant_id, [ 'id' => $id ] );
        return $row ? Project::fromArray( $row ) : null;
    }

    public function findBySlug( int $tenant_id, string $slug ): ?Project {
        $row = $this->selectOne( $tenant_id, [ 'slug' => $slug ] );
        return $row ? Project::fromArray( $row ) : null;
    }

    public function persist( int $tenant_id, Project $p ): int {
        $data = [
            'name'              => $p->name,
            'slug'              => $p->slug,
            'builder'           => $p->builder,
            'location'          => $p->location,
            'sector'            => $p->sector,
            'latitude'          => $p->latitude,
            'longitude'         => $p->longitude,
            'possession_status' => $p->possession_status,
            'tower_count'       => $p->tower_count,
            'seo_title'         => $p->seo_title,
            'seo_description'   => $p->seo_description,
            'overview'          => $p->overview,
        ];
        if ( $p->id > 0 ) {
            $this->update( $tenant_id, $p->id, $data );
            return $p->id;
        }
        return $this->insert( $tenant_id, $data );
    }
}
