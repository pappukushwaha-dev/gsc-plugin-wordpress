<?php
/**
 * REST endpoint the panel calls to trigger an instant sync.
 *
 * After the setup wizard issues a verification token it needs the site
 * to pick it up immediately - not on the next hourly pull. The panel
 * POSTs here with the instance_id and the sync_secret that the plugin
 * stored when it last pulled its meta; a matching pair runs the sync
 * on the spot.
 *
 * The endpoint is also what makes "push to site" work for future
 * panel-side actions without waiting for the hourly window.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSCWP_Rest
{
    public static function init(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('gscwp/v1', '/sync', [
                'methods'             => 'POST',
                'permission_callback' => '__return_true', // auth inside the handler
                'callback'            => [self::class, 'handleSync'],
            ]);
        });
    }

    /**
     * Auth: instance_id + sync_secret must match the stored pair
     * (hash_equals on both - timing-safe).
     */
    public static function handleSync($request)
    {
        $state      = GSCWP_Register::getState();
        $instanceId = sanitize_text_field((string) $request->get_param('instance_id'));
        $syncSecret = (string) $request->get_param('sync_secret');

        $storedId     = (string) ($state['instance_id'] ?? '');
        $storedSecret = (string) ($state['sync_secret'] ?? '');

        $ok = $storedId !== '' && $storedSecret !== ''
            && hash_equals($storedId, $instanceId)
            && hash_equals($storedSecret, $syncSecret);

        if (!$ok) {
            return new WP_REST_Response(['success' => false, 'error' => 'unauthorised'], 401);
        }

        $result = GSCWP_Sync::sync();

        return new WP_REST_Response([
            'success' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
        ], 200);
    }
}
