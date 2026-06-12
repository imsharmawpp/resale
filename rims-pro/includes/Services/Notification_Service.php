<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Inventory_Unit;
use RimsPro\Repositories\Push_Subscription_Repository;
use RimsPro\Repositories\Saved_Alert_Repository;

/**
 * Push delivery: deliver IFF permission granted AND unit matches saved
 * criteria (Property 40 / Req 28.3, 28.4).
 */
final class Notification_Service {

    public function __construct(
        private Push_Subscription_Repository $subs,
        private Saved_Alert_Repository $alerts,
    ) {
    }

    /**
     * @param array{visitor_token:string, granted:bool} $subscription
     * @param array<string,mixed>                       $criteria
     */
    public function shouldDeliver( array $subscription, array $criteria, Inventory_Unit $u ): bool {
        if ( empty( $subscription['granted'] ) ) {
            return false;
        }
        if ( $u->expired ) {
            return false;
        }
        if ( isset( $criteria['bhk'] ) && abs( $u->bhk - (float) $criteria['bhk'] ) > 0.001 ) {
            return false;
        }
        if ( isset( $criteria['max_budget_paise'] ) && $u->price_paise > (int) $criteria['max_budget_paise'] ) {
            return false;
        }
        if ( isset( $criteria['location'] ) && stripos( $u->location, (string) $criteria['location'] ) === false ) {
            return false;
        }
        if ( isset( $criteria['builder'] ) && stripos( $u->builder, (string) $criteria['builder'] ) === false ) {
            return false;
        }
        return true;
    }

    /**
     * Dispatch a push for each visitor whose alert matches and has granted permission.
     *
     * @return int number of pushes attempted
     */
    public function dispatch( int $tenant_id, Inventory_Unit $u ): int {
        $alerts = $this->alerts->listForTenant( $tenant_id );
        $count  = 0;
        foreach ( $alerts as $row ) {
            $subs = $this->subs->forVisitor( $tenant_id, $row['visitor_token'] );
            foreach ( $subs as $sub ) {
                if ( $this->shouldDeliver( [ 'visitor_token' => $row['visitor_token'], 'granted' => (bool) $sub['granted'] ], $row['criteria'], $u ) ) {
                    $this->sendPush( $sub, $u );
                    $count++;
                }
            }
        }
        return $count;
    }

    private function sendPush( array $sub, Inventory_Unit $u ): void {
        try {
            if ( class_exists( '\Minishlink\WebPush\WebPush' ) && defined( 'RIMS_PRO_VAPID_PRIVATE' ) ) {
                $auth = [
                    'VAPID' => [
                        'subject'    => home_url(),
                        'publicKey'  => defined( 'RIMS_PRO_VAPID_PUBLIC' ) ? constant( 'RIMS_PRO_VAPID_PUBLIC' ) : '',
                        'privateKey' => constant( 'RIMS_PRO_VAPID_PRIVATE' ),
                    ],
                ];
                $webPush = new \Minishlink\WebPush\WebPush( $auth );
                $webPush->queueNotification(
                    \Minishlink\WebPush\Subscription::create( [
                        'endpoint'        => $sub['endpoint'],
                        'publicKey'       => $sub['p256dh'],
                        'authToken'       => $sub['auth'],
                    ] ),
                    wp_json_encode( [
                        'title' => 'New unit: ' . $u->project_name,
                        'body'  => $u->bhk . ' BHK in ' . $u->location,
                        'url'   => $u->publicUrl(),
                    ] )
                );
                foreach ( $webPush->flush() as $report ) {
                    if ( ! $report->isSuccess() ) {
                        error_log( '[RIMS Pro] push failed: ' . $report->getReason() );
                    }
                }
            }
        } catch ( \Throwable $e ) {
            error_log( '[RIMS Pro] push exception: ' . $e->getMessage() );
        }
    }
}
