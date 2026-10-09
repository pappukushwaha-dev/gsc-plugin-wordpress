<?php
/**
 * Plugin Name: GSC Plugin
 * Description: Connects your WordPress site to the GSC dashboard - Google verification, sitemap and search reports in one place.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: MakkPress Apps
 * Text Domain: gscwp
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('GSCWP_VERSION', '1.0.0');

/**
 * The hosted panel this plugin connects to. Override in wp-config.php
 * (define before require of wp-settings) when pointing at a staging
 * panel; production default below.
 */
if (!defined('GSCWP_PANEL_URL')) {
    define('GSCWP_PANEL_URL', 'https://makkpressapps.com/wordpress/googlesearchconsole/');
}

define('GSCWP_OPTION_KEY', 'gscwp_instance');
define('GSCWP_MIN_PHP', '7.4');

require_once __DIR__ . '/includes/class-gscwp-apiclient.php';
require_once __DIR__ . '/includes/class-gscwp-register.php';
require_once __DIR__ . '/includes/class-gscwp-adminpage.php';

/**
 * Guard: the plugin refuses to run on PHP below the declared minimum.
 * A version notice beats a fatal on an old host.
 */
if (version_compare(PHP_VERSION, GSCWP_MIN_PHP, '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>'
            . esc_html__('GSC Plugin requires PHP ', 'gscwp')
            . esc_html(GSCWP_MIN_PHP)
            . esc_html__(' or newer. The plugin is inactive.', 'gscwp')
            . '</p></div>';
    });
    return;
}

GSCWP_AdminPage::init();

/**
 * On activation: clear any stale state so a clean registration runs.
 * The registration itself happens on the next admin page load (see
 * GSCWP_Register::maybe_register) - doing outbound HTTP inside the
 * activation hook risks the double-request redirect and a confusing
 * first impression.
 */
function gscwp_activate(): void
{
    delete_option(GSCWP_OPTION_KEY);
}
register_activation_hook(__FILE__, 'gscwp_activate');

/**
 * Attempt registration once per admin page load when the site is not
 * yet registered. Admin-only, capability-checked inside.
 */
add_action('admin_init', function () {
    GSCWP_Register::maybe_register();
});
