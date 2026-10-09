<?php
/**
 * Keeps the plugin's view of the panel in sync: pulls the verification
 * token and reports the sitemap URL, once an hour on admin loads.
 *
 * One round trip does both - the panel's instance-meta endpoint stores
 * a reported sitemap (same host only) and returns the current meta.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSCWP_Sync
{
    const REFRESH_EVERY = 3600; // seconds

    public static function maybe_sync(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $state = GSCWP_Register::getState();
        if (($state['instance_id'] ?? '') === '' || ($state['secret'] ?? '') === '') {
            return;
        }

        $last = (int) get_transient('gscwp_sync_cooldown');
        if ($last && (time() - $last) < self::REFRESH_EVERY) {
            return;
        }
        set_transient('gscwp_sync_cooldown', time(), self::REFRESH_EVERY);

        self::sync();
    }

    /**
     * Pulls the panel meta and reports the site's sitemap.
     *
     * @return array{ok:bool, message:string}
     */
    public static function sync(): array
    {
        $state = GSCWP_Register::getState();

        $response = GSCWP_ApiClient::post('api/instance-meta.php', [
            'instance_id' => $state['instance_id'] ?? '',
            'secret'      => $state['secret'] ?? '',
            'sitemap_url' => self::detectSitemap(),
        ]);

        if (empty($response['ok']) || empty($response['body']['success'])) {
            $reason = $response['body']['error'] ?? ($response['error'] ?? 'HTTP ' . $response['status']);
            return ['ok' => false, 'message' => (string) $reason];
        }

        update_option('gscwp_panel_meta', [
            'verify_token' => (string) ($response['body']['verify_token'] ?? ''),
            'sitemap_url'  => (string) ($response['body']['sitemap_url'] ?? ''),
            'fetched_at'   => gmdate('c'),
        ], false);

        return ['ok' => true, 'message' => 'synced'];
    }

    /**
     * WordPress has shipped a native sitemap since 5.5.
     */
    public static function detectSitemap(): string
    {
        $candidate = home_url('/wp-sitemap.xml');

        $check = wp_remote_head($candidate, ['timeout' => 5, 'redirection' => 0]);

        if (!is_wp_error($check)) {
            $code = (int) wp_remote_retrieve_response_code($check);
            if ($code >= 200 && $code < 400) {
                return $candidate;
            }
        }

        return '';
    }

    /** Cached panel meta for the settings page and the verify hook. */
    public static function meta(): array
    {
        $meta = get_option('gscwp_panel_meta', []);
        return is_array($meta) ? $meta : [];
    }
}
