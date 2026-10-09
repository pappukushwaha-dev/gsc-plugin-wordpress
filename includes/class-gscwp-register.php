<?php
/**
 * Registers this WordPress site with the hosted panel.
 *
 * The registration is the WordPress answer to a marketplace install: the
 * panel creates (or returns) the instance for this site_url, and the
 * plugin stores it. Idempotent by design - reinstalling the plugin on
 * the same site returns the same instance_id from the panel, so history
 * is never orphaned.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSCWP_Register
{
    /** How long a failed attempt waits before the next retry (seconds).
     *  Without this a downed panel would be hammered on every admin
     *  page load. */
    const RETRY_WAIT = 300;

    /**
     * Runs on admin_init. Registers only when: user may manage options,
     * the site is not registered yet, and the retry cooldown has passed.
     */
    public static function maybe_register(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $state = self::getState();
        if (($state['instance_id'] ?? '') !== '') {
            return;
        }

        $lastAttempt = (int) get_transient('gscwp_register_cooldown');
        if ($lastAttempt && (time() - $lastAttempt) < self::RETRY_WAIT) {
            return;
        }

        set_transient('gscwp_register_cooldown', time(), self::RETRY_WAIT);

        self::register();
    }

    /**
     * Calls the panel and stores the result.
     *
     * @return array{ok:bool, message:string}
     */
    public static function register(): array
    {
        $siteUrl = home_url();
        if (!preg_match('#^https?://#i', $siteUrl)) {
            return ['ok' => false, 'message' => 'site URL is not absolute'];
        }

        $response = GSCWP_ApiClient::post('api/register-site.php', [
            'site_url'       => $siteUrl,
            'wp_version'     => get_bloginfo('version'),
            'plugin_version' => GSCWP_VERSION,
        ]);

        if (empty($response['ok']) || empty($response['body']['success'])
            || empty($response['body']['instance_id'])) {
            $reason = $response['body']['error'] ?? ($response['error'] ?? 'HTTP ' . $response['status']);
            GSCWP_AdminPage::setNotice(sprintf('Registration failed: %s', $reason), 'error');
            return ['ok' => false, 'message' => (string) $reason];
        }

        $instanceId = substr(trim((string) $response['body']['instance_id']), 0, 64);

        update_option(GSCWP_OPTION_KEY, [
            'instance_id'     => $instanceId,
            'site_url'        => $siteUrl,
            'registered_at'   => gmdate('c'),
            'plugin_version'  => GSCWP_VERSION,
        ], false);

        delete_transient('gscwp_register_cooldown');
        GSCWP_AdminPage::setNotice('Your site is connected to the GSC dashboard.', 'success');

        return ['ok' => true, 'message' => 'registered', 'instance_id' => $instanceId];
    }

    /** @return array{instance_id?:string, site_url?:string, registered_at?:string, plugin_version?:string} */
    public static function getState(): array
    {
        $state = get_option(GSCWP_OPTION_KEY, []);
        return is_array($state) ? $state : [];
    }
}
