<?php

/**
 * Fired when DazeStack Bulk Plugin Manager is uninstalled via WordPress Admin.
 * Cleans up options and transients.
 *
 * @package DazeStack_Bulk_Plugin_Manager
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Delete persistent settings and recipes
delete_option('dsbpm_recipes');
delete_option('dsbpm_plugin_meta');

// Delete temporary transient caches
delete_transient('dsbpm_partner_offers_cache');
delete_transient('dsbpm_metrics_cache');
delete_transient('dsbpm_install_lock');

// Cleanup for WordPress Multisite network installations
if (is_multisite()) {
	delete_site_option('dsbpm_recipes');
	delete_site_option('dsbpm_plugin_meta');
	delete_site_transient('dsbpm_partner_offers_cache');
	delete_site_transient('dsbpm_metrics_cache');
}
