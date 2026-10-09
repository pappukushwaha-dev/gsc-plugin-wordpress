<?php
/**
 * Runs when the plugin is deleted from WP admin (not on deactivate).
 * Removes only this plugin's own data. The registration on the panel
 * stays - a reinstall on the same site must find its history.
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('gscwp_instance');
delete_transient('gscwp_register_cooldown');
delete_transient('gscwp_admin_notice');
