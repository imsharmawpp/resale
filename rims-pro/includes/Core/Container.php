<?php
declare(strict_types=1);

namespace RimsPro\Core;

use RimsPro\Repositories\Analytics_Repository;
use RimsPro\Repositories\Bookmark_Repository;
use RimsPro\Repositories\Crm_Config_Repository;
use RimsPro\Repositories\Inventory_Repository;
use RimsPro\Repositories\Lead_History_Repository;
use RimsPro\Repositories\Lead_Repository;
use RimsPro\Repositories\Media_Repository;
use RimsPro\Repositories\Nearby_Place_Repository;
use RimsPro\Repositories\Price_History_Repository;
use RimsPro\Repositories\Project_Repository;
use RimsPro\Repositories\Push_Subscription_Repository;
use RimsPro\Repositories\Saved_Alert_Repository;
use RimsPro\Repositories\Tag_Repository;
use RimsPro\Repositories\Tenant_Repository;
use RimsPro\Security\Authorization_Guard;
use RimsPro\Security\Output_Escaper;
use RimsPro\Security\Rate_Limiter;
use RimsPro\Security\Schema_Validator;
use RimsPro\Services\AI_Content_Generator;
use RimsPro\Services\AI_Tag_Generator;
use RimsPro\Services\Analytics_Engine;
use RimsPro\Services\Bookmark_Manager;
use RimsPro\Services\Broker_Sheet_Formatter;
use RimsPro\Services\Cache_Manager;
use RimsPro\Services\Comparison_Engine;
use RimsPro\Services\Contact_Link_Builder;
use RimsPro\Services\CRM_Integration_Service;
use RimsPro\Services\Dashboard_Metrics_Service;
use RimsPro\Services\Expiry_Manager;
use RimsPro\Services\Export_Engine;
use RimsPro\Services\Featured_Carousel_Service;
use RimsPro\Services\Field_Visibility_Serializer;
use RimsPro\Services\Filter_Engine;
use RimsPro\Services\Inventory_Manager;
use RimsPro\Services\Lead_Capture;
use RimsPro\Services\Lead_Management;
use RimsPro\Services\Map_Partition_Service;
use RimsPro\Services\Media_Gallery_Manager;
use RimsPro\Services\Notification_Service;
use RimsPro\Services\Pagination_Service;
use RimsPro\Services\Price_Trend_Selector;
use RimsPro\Services\QR_Code_Generator;
use RimsPro\Services\Recently_Viewed_Tracker;
use RimsPro\Services\SEO_Engine;
use RimsPro\Services\Share_Manager;
use RimsPro\Services\Smart_Search_Engine;
use RimsPro\Services\Statistics_Aggregator;
use RimsPro\Services\Table_Sort_Service;
use RimsPro\Tenancy\Tenant_Resolver;

/**
 * Lazy DI container - constructs and shares services, repositories, and tenant resolver.
 */
final class Container {

    /** @var array<string, object> */
    private array $shared = [];

    /**
     * @template T of object
     * @param string $key
     * @param callable():T $factory
     * @return T
     */
    private function share( string $key, callable $factory ): object {
        if ( ! isset( $this->shared[ $key ] ) ) {
            $this->shared[ $key ] = $factory();
        }
        return $this->shared[ $key ];
    }

    public function tenant_resolver(): Tenant_Resolver {
        return $this->share(
            'tenant_resolver',
            fn() => new Tenant_Resolver( $this->tenant_repository() )
        );
    }

    public function tenant_repository(): Tenant_Repository {
        return $this->share( 'tenant_repository', fn() => new Tenant_Repository() );
    }

    public function inventory_repository(): Inventory_Repository {
        return $this->share( 'inventory_repository', fn() => new Inventory_Repository() );
    }

    public function project_repository(): Project_Repository {
        return $this->share( 'project_repository', fn() => new Project_Repository() );
    }

    public function lead_repository(): Lead_Repository {
        return $this->share( 'lead_repository', fn() => new Lead_Repository() );
    }

    public function lead_history_repository(): Lead_History_Repository {
        return $this->share( 'lead_history_repository', fn() => new Lead_History_Repository() );
    }

    public function media_repository(): Media_Repository {
        return $this->share( 'media_repository', fn() => new Media_Repository() );
    }

    public function analytics_repository(): Analytics_Repository {
        return $this->share( 'analytics_repository', fn() => new Analytics_Repository() );
    }

    public function tag_repository(): Tag_Repository {
        return $this->share( 'tag_repository', fn() => new Tag_Repository() );
    }

    public function price_history_repository(): Price_History_Repository {
        return $this->share( 'price_history_repository', fn() => new Price_History_Repository() );
    }

    public function nearby_place_repository(): Nearby_Place_Repository {
        return $this->share( 'nearby_place_repository', fn() => new Nearby_Place_Repository() );
    }

    public function bookmark_repository(): Bookmark_Repository {
        return $this->share( 'bookmark_repository', fn() => new Bookmark_Repository() );
    }

    public function saved_alert_repository(): Saved_Alert_Repository {
        return $this->share( 'saved_alert_repository', fn() => new Saved_Alert_Repository() );
    }

    public function push_subscription_repository(): Push_Subscription_Repository {
        return $this->share( 'push_subscription_repository', fn() => new Push_Subscription_Repository() );
    }

    public function crm_config_repository(): Crm_Config_Repository {
        return $this->share( 'crm_config_repository', fn() => new Crm_Config_Repository() );
    }

    public function cache_manager(): Cache_Manager {
        return $this->share( 'cache_manager', fn() => new Cache_Manager() );
    }

    public function authorization_guard(): Authorization_Guard {
        return $this->share( 'authorization_guard', fn() => new Authorization_Guard( $this->rate_limiter() ) );
    }

    public function output_escaper(): Output_Escaper {
        return $this->share( 'output_escaper', fn() => new Output_Escaper() );
    }

    public function rate_limiter(): Rate_Limiter {
        return $this->share( 'rate_limiter', fn() => new Rate_Limiter() );
    }

    public function schema_validator(): Schema_Validator {
        return $this->share( 'schema_validator', fn() => new Schema_Validator() );
    }

    public function field_visibility_serializer(): Field_Visibility_Serializer {
        return $this->share( 'field_visibility_serializer', fn() => new Field_Visibility_Serializer() );
    }

    public function filter_engine(): Filter_Engine {
        return $this->share( 'filter_engine', fn() => new Filter_Engine() );
    }

    public function smart_search_engine(): Smart_Search_Engine {
        return $this->share( 'smart_search_engine', fn() => new Smart_Search_Engine( $this->filter_engine() ) );
    }

    public function statistics_aggregator(): Statistics_Aggregator {
        return $this->share( 'statistics_aggregator', fn() => new Statistics_Aggregator() );
    }

    public function pagination_service(): Pagination_Service {
        return $this->share( 'pagination_service', fn() => new Pagination_Service() );
    }

    public function expiry_manager(): Expiry_Manager {
        return $this->share(
            'expiry_manager',
            fn() => new Expiry_Manager( $this->inventory_repository(), $this->tenant_resolver() )
        );
    }

    public function inventory_manager(): Inventory_Manager {
        return $this->share(
            'inventory_manager',
            fn() => new Inventory_Manager( $this->inventory_repository(), $this->schema_validator(), $this->cache_manager() )
        );
    }

    public function featured_carousel_service(): Featured_Carousel_Service {
        return $this->share( 'featured_carousel_service', fn() => new Featured_Carousel_Service() );
    }

    public function table_sort_service(): Table_Sort_Service {
        return $this->share( 'table_sort_service', fn() => new Table_Sort_Service() );
    }

    public function broker_sheet_formatter(): Broker_Sheet_Formatter {
        return $this->share( 'broker_sheet_formatter', fn() => new Broker_Sheet_Formatter() );
    }

    public function map_partition_service(): Map_Partition_Service {
        return $this->share( 'map_partition_service', fn() => new Map_Partition_Service() );
    }

    public function price_trend_selector(): Price_Trend_Selector {
        return $this->share( 'price_trend_selector', fn() => new Price_Trend_Selector() );
    }

    public function contact_link_builder(): Contact_Link_Builder {
        return $this->share( 'contact_link_builder', fn() => new Contact_Link_Builder() );
    }

    public function lead_capture(): Lead_Capture {
        return $this->share(
            'lead_capture',
            fn() => new Lead_Capture( $this->lead_repository(), $this->schema_validator() )
        );
    }

    public function lead_management(): Lead_Management {
        return $this->share(
            'lead_management',
            fn() => new Lead_Management( $this->lead_repository(), $this->lead_history_repository() )
        );
    }

    public function analytics_engine(): Analytics_Engine {
        return $this->share(
            'analytics_engine',
            fn() => new Analytics_Engine( $this->analytics_repository() )
        );
    }

    public function dashboard_metrics_service(): Dashboard_Metrics_Service {
        return $this->share( 'dashboard_metrics_service', fn() => new Dashboard_Metrics_Service() );
    }

    public function media_gallery_manager(): Media_Gallery_Manager {
        return $this->share(
            'media_gallery_manager',
            fn() => new Media_Gallery_Manager( $this->media_repository() )
        );
    }

    public function ai_content_generator(): AI_Content_Generator {
        return $this->share( 'ai_content_generator', fn() => new AI_Content_Generator() );
    }

    public function ai_tag_generator(): AI_Tag_Generator {
        return $this->share(
            'ai_tag_generator',
            fn() => new AI_Tag_Generator( $this->tag_repository() )
        );
    }

    public function export_engine(): Export_Engine {
        return $this->share(
            'export_engine',
            fn() => new Export_Engine( $this->field_visibility_serializer() )
        );
    }

    public function comparison_engine(): Comparison_Engine {
        return $this->share( 'comparison_engine', fn() => new Comparison_Engine() );
    }

    public function bookmark_manager(): Bookmark_Manager {
        return $this->share(
            'bookmark_manager',
            fn() => new Bookmark_Manager( $this->bookmark_repository() )
        );
    }

    public function recently_viewed_tracker(): Recently_Viewed_Tracker {
        return $this->share( 'recently_viewed_tracker', fn() => new Recently_Viewed_Tracker() );
    }

    public function share_manager(): Share_Manager {
        return $this->share( 'share_manager', fn() => new Share_Manager() );
    }

    public function qr_code_generator(): QR_Code_Generator {
        return $this->share( 'qr_code_generator', fn() => new QR_Code_Generator() );
    }

    public function seo_engine(): SEO_Engine {
        return $this->share( 'seo_engine', fn() => new SEO_Engine() );
    }

    public function notification_service(): Notification_Service {
        return $this->share(
            'notification_service',
            fn() => new Notification_Service( $this->push_subscription_repository(), $this->saved_alert_repository() )
        );
    }

    public function crm_integration_service(): CRM_Integration_Service {
        return $this->share(
            'crm_integration_service',
            fn() => new CRM_Integration_Service( $this->crm_config_repository() )
        );
    }
}
