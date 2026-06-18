<?php
declare(strict_types=1);

namespace RimsPro\Repositories;

class Tag_Repository extends Base_Repository {

    public const VOCABULARY = [
        'Luxury',
        'Golf View',
        'Corner Unit',
        'Park Facing',
        'Urgent Sale',
        'Investor Deal',
    ];

    protected function table_suffix(): string {
        return 'tags';
    }

    public function ensureVocabulary( int $tenant_id ): void {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return;
        }
        foreach ( self::VOCABULARY as $name ) {
            $exists = $this->selectOne( $tenant_id, [ 'name' => $name ] );
            if ( ! $exists ) {
                $this->insert( $tenant_id, [ 'name' => $name ] );
            }
        }
    }

    public function persistAccepted( int $tenant_id, int $unit_id, array $accepted_tags ): void {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return;
        }
        $bridge = $wpdb->prefix . RIMS_PRO_DB_PREFIX . 'inventory_tags';
        foreach ( $accepted_tags as $tag_name ) {
            if ( ! in_array( $tag_name, self::VOCABULARY, true ) ) {
                continue;
            }
            $row = $this->selectOne( $tenant_id, [ 'name' => $tag_name ] );
            if ( ! $row ) {
                $tag_id = $this->insert( $tenant_id, [ 'name' => $tag_name ] );
            } else {
                $tag_id = (int) $row['id'];
            }
            $wpdb->replace( $bridge, [ 'unit_id' => $unit_id, 'tag_id' => $tag_id, 'accepted' => 1 ] );
        }
    }
}
