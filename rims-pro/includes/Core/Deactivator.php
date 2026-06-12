<?php
declare(strict_types=1);

namespace RimsPro\Core;

final class Deactivator {

    public function deactivate(): void {
        // Unschedule cron jobs while preserving inventory/project/lead data.
        $timestamp = wp_next_scheduled( 'rims_pro_expire_units' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'rims_pro_expire_units' );
        }
        flush_rewrite_rules();
    }
}
