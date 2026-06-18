<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Project;

/**
 * AI content generator. The default implementation produces deterministic
 * template-based content that requires no external service (so the plugin is
 * usable offline). Site owners can swap the provider by hooking
 * 'rims_pro_ai_provider'.
 *
 * On exception, returns null and leaves saved content unchanged (Req 21.3).
 */
final class AI_Content_Generator {

    /** @return array{seo_description:string, project_overview:string, whatsapp_message:string}|null */
    public function generateForProject( Project $p ): ?array {
        try {
            $provider = function_exists( 'apply_filters' ) ? apply_filters( 'rims_pro_ai_provider', null, $p ) : null;
            if ( is_callable( $provider ) ) {
                $r = $provider( $p );
                if ( is_array( $r ) ) {
                    return $r;
                }
            }
            // Deterministic fallback.
            $seo = sprintf(
                'Discover %s by %s in %s. %d-tower premium development. %s.',
                $p->name,
                $p->builder ?: 'Builder',
                $p->location ?: 'India',
                $p->tower_count,
                $p->possession_status ?: 'Possession-ready'
            );
            $overview = sprintf(
                "%s is a %s development by %s situated in %s%s. The project comprises %d tower(s) and offers a curated selection of resale-ready apartments.",
                $p->name,
                strtolower( $p->possession_status ?: 'premium' ),
                $p->builder ?: 'a leading developer',
                $p->location ?: 'a prime location',
                $p->sector ? ', ' . $p->sector : '',
                max( 1, $p->tower_count )
            );
            $wa = sprintf(
                'Hi! I would like more information about resale apartments in %s, %s.',
                $p->name,
                $p->location ?: 'your project'
            );
            return [
                'seo_description'  => $seo,
                'project_overview' => $overview,
                'whatsapp_message' => $wa,
            ];
        } catch ( \Throwable $e ) {
            error_log( '[RIMS Pro] AI generation failed: ' . $e->getMessage() );
            return null;
        }
    }
}
