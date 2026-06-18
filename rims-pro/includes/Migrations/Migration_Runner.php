<?php
declare(strict_types=1);

namespace RimsPro\Migrations;

/**
 * Versioned schema migration runner. Tracks applied versions in wp_options
 * (`rims_pro_db_version`) and applies pending migrations idempotently.
 */
final class Migration_Runner {

    public const OPTION_KEY = 'rims_pro_db_version';

    /** @var array<int, callable(\wpdb):void> */
    private array $migrations;

    public function __construct() {
        $this->migrations = $this->build_migrations();
    }

    public function run(): void {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return;
        }
        $applied = (int) get_option( self::OPTION_KEY, 0 );
        foreach ( $this->migrations as $version => $migration ) {
            if ( $version <= $applied ) {
                continue;
            }
            $migration( $wpdb );
            update_option( self::OPTION_KEY, $version );
        }
    }

    /** @return array<int, callable(\wpdb):void> */
    private function build_migrations(): array {
        $charset = function ( \wpdb $wpdb ): string {
            return $wpdb->get_charset_collate();
        };
        $p = static fn( \wpdb $wpdb, string $name ): string => $wpdb->prefix . RIMS_PRO_DB_PREFIX . $name;

        return [
            1 => function ( \wpdb $wpdb ) use ( $p, $charset ): void {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                $cs = $charset( $wpdb );

                // Batch all 17 CREATE TABLE statements into a single dbDelta() call.
                // This is dramatically faster than 17 separate calls because dbDelta()
                // only needs to load and parse the schema comparison logic once.
                $sql = "CREATE TABLE {$p( $wpdb, 'tenants' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        name VARCHAR(191) NOT NULL,
                        domain VARCHAR(191) NOT NULL,
                        mode VARCHAR(16) NOT NULL DEFAULT 'single',
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY domain_idx (domain)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'tenant_settings' )} (
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        company_name VARCHAR(191) NOT NULL DEFAULT '',
                        logo_url VARCHAR(255) NOT NULL DEFAULT '',
                        primary_color VARCHAR(16) NOT NULL DEFAULT '#1e3a8a',
                        contact_phone VARCHAR(40) NOT NULL DEFAULT '',
                        contact_whatsapp VARCHAR(40) NOT NULL DEFAULT '',
                        watermark_enabled TINYINT(1) NOT NULL DEFAULT 0,
                        watermark_path VARCHAR(255) NOT NULL DEFAULT '',
                        expiry_days INT NOT NULL DEFAULT 90,
                        infinite_scroll_enabled TINYINT(1) NOT NULL DEFAULT 1,
                        rate_limit_window INT NOT NULL DEFAULT 60,
                        rate_limit_max INT NOT NULL DEFAULT 60,
                        status_color_map LONGTEXT NULL,
                        PRIMARY KEY (tenant_id)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'projects' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        name VARCHAR(191) NOT NULL,
                        slug VARCHAR(191) NOT NULL,
                        builder VARCHAR(191) NOT NULL DEFAULT '',
                        location VARCHAR(191) NOT NULL DEFAULT '',
                        sector VARCHAR(100) NOT NULL DEFAULT '',
                        latitude DECIMAL(10,7) NULL,
                        longitude DECIMAL(10,7) NULL,
                        possession_status VARCHAR(50) NOT NULL DEFAULT '',
                        tower_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                        seo_title VARCHAR(255) NOT NULL DEFAULT '',
                        seo_description TEXT NULL,
                        overview LONGTEXT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY tenant_idx (tenant_id),
                        KEY tenant_slug_idx (tenant_id, slug),
                        KEY builder_idx (tenant_id, builder),
                        KEY location_idx (tenant_id, location)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'inventory_units' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        project_id BIGINT UNSIGNED NOT NULL,
                        unit_number VARCHAR(50) NOT NULL DEFAULT '',
                        tower VARCHAR(50) NOT NULL DEFAULT '',
                        floor SMALLINT NOT NULL DEFAULT 0,
                        facing VARCHAR(4) NOT NULL DEFAULT 'N',
                        bhk DECIMAL(3,1) NOT NULL DEFAULT 2.0,
                        is_penthouse TINYINT(1) NOT NULL DEFAULT 0,
                        area_sqft INT UNSIGNED NOT NULL DEFAULT 0,
                        price DECIMAL(15,2) NOT NULL DEFAULT 0,
                        price_label VARCHAR(60) NOT NULL DEFAULT '',
                        owner_name VARCHAR(191) NOT NULL DEFAULT '',
                        owner_phone VARCHAR(30) NOT NULL DEFAULT '',
                        broker_notes TEXT NULL,
                        status VARCHAR(32) NOT NULL DEFAULT 'available',
                        status_changed_at DATETIME NULL,
                        is_featured TINYINT(1) NOT NULL DEFAULT 0,
                        published_at DATETIME NULL,
                        expired TINYINT(1) NOT NULL DEFAULT 0,
                        slug VARCHAR(191) NOT NULL,
                        latitude DECIMAL(10,7) NULL,
                        longitude DECIMAL(10,7) NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY tenant_idx (tenant_id),
                        KEY tenant_project_idx (tenant_id, project_id),
                        KEY tenant_status_idx (tenant_id, status),
                        KEY tenant_slug_idx (tenant_id, slug)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'unit_variants' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        unit_id BIGINT UNSIGNED NOT NULL,
                        area_sqft INT UNSIGNED NOT NULL,
                        label VARCHAR(60) NOT NULL DEFAULT '',
                        PRIMARY KEY (id),
                        KEY unit_idx (unit_id)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'leads' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        name VARCHAR(191) NOT NULL,
                        mobile VARCHAR(30) NOT NULL,
                        email VARCHAR(191) NULL,
                        requirements TEXT NULL,
                        source VARCHAR(60) NOT NULL DEFAULT 'web',
                        unit_id BIGINT UNSIGNED NULL,
                        pipeline_stage VARCHAR(32) NOT NULL DEFAULT 'new',
                        crm_sync_status VARCHAR(16) NOT NULL DEFAULT 'pending',
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY tenant_idx (tenant_id),
                        KEY tenant_stage_idx (tenant_id, pipeline_stage)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'lead_history' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        lead_id BIGINT UNSIGNED NOT NULL,
                        actor_id BIGINT UNSIGNED NULL,
                        from_stage VARCHAR(32) NULL,
                        to_stage VARCHAR(32) NOT NULL,
                        note TEXT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY lead_idx (lead_id)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'media' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        owner_type VARCHAR(16) NOT NULL,
                        owner_id BIGINT UNSIGNED NOT NULL,
                        kind VARCHAR(16) NOT NULL,
                        original_path VARCHAR(255) NOT NULL,
                        compressed_path VARCHAR(255) NOT NULL DEFAULT '',
                        watermarked TINYINT(1) NOT NULL DEFAULT 0,
                        width INT UNSIGNED NOT NULL DEFAULT 0,
                        height INT UNSIGNED NOT NULL DEFAULT 0,
                        bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
                        sort_order INT NOT NULL DEFAULT 0,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY tenant_owner_idx (tenant_id, owner_type, owner_id)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'analytics_events' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        event_type VARCHAR(32) NOT NULL,
                        unit_id BIGINT UNSIGNED NULL,
                        project_id BIGINT UNSIGNED NULL,
                        session_hash VARCHAR(64) NOT NULL DEFAULT '',
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY tenant_event_idx (tenant_id, event_type, created_at)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'tags' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        name VARCHAR(60) NOT NULL,
                        PRIMARY KEY (id),
                        KEY tenant_idx (tenant_id)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'inventory_tags' )} (
                        unit_id BIGINT UNSIGNED NOT NULL,
                        tag_id BIGINT UNSIGNED NOT NULL,
                        accepted TINYINT(1) NOT NULL DEFAULT 0,
                        PRIMARY KEY (unit_id, tag_id)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'price_history' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        project_id BIGINT UNSIGNED NOT NULL,
                        price DECIMAL(15,2) NOT NULL,
                        recorded_at DATETIME NOT NULL,
                        PRIMARY KEY (id),
                        KEY tenant_project_idx (tenant_id, project_id, recorded_at)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'nearby_places' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        project_id BIGINT UNSIGNED NOT NULL,
                        category VARCHAR(16) NOT NULL,
                        name VARCHAR(191) NOT NULL,
                        distance_km DECIMAL(5,2) NOT NULL,
                        PRIMARY KEY (id),
                        KEY project_cat_idx (project_id, category)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'bookmarks' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        visitor_token VARCHAR(64) NOT NULL,
                        unit_id BIGINT UNSIGNED NOT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY tenant_visitor_idx (tenant_id, visitor_token)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'saved_alerts' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        visitor_token VARCHAR(64) NOT NULL,
                        criteria_json LONGTEXT NOT NULL,
                        PRIMARY KEY (id),
                        KEY tenant_visitor_idx (tenant_id, visitor_token)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'push_subscriptions' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        visitor_token VARCHAR(64) NOT NULL,
                        endpoint VARCHAR(500) NOT NULL,
                        p256dh VARCHAR(255) NOT NULL,
                        auth VARCHAR(255) NOT NULL,
                        granted TINYINT(1) NOT NULL DEFAULT 0,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY tenant_visitor_idx (tenant_id, visitor_token)
                    ) {$cs};

                    CREATE TABLE {$p( $wpdb, 'crm_config' )} (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tenant_id BIGINT UNSIGNED NOT NULL,
                        platform VARCHAR(32) NOT NULL,
                        enabled TINYINT(1) NOT NULL DEFAULT 0,
                        credentials_encrypted LONGBLOB NULL,
                        retry_policy_json LONGTEXT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY tenant_platform_idx (tenant_id, platform)
                    ) {$cs};";

                dbDelta( $sql );
            },
        ];
    }
}
