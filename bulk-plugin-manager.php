<?php

/**
 * Plugin Name: DazeStack Bulk Plugin Manager
 * Description: High-efficiency bulk provisioning for WordPress plugin stacks.
 * Version: 0.0.2
 * Author: Ashish Dungdung
 * Author URI: https://ashishdungdung.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dazestack-bulk-plugin-manager
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) {
	exit;
}

define('DSBPM_VERSION', '0.0.2');
define('DSBPM_FILE', __FILE__);
define('DSBPM_DIR', plugin_dir_path(__FILE__));
define('DSBPM_URL', plugin_dir_url(__FILE__));
define('DSBPM_SLUG', 'bulk-plugin-manager');
define('DSBPM_BASE_SLUG', 'dazestack-suite');
define('DSBPM_PARTNER_OFFERS_URL', 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSm9cAwZecAbTfoDFRDzYpFj5qCVb9jV82K95l7YcX3c6LSdHJy11NRGtyBTYdHS7frfWF6bCdVYRhs/pub?output=csv');

require_once DSBPM_DIR . 'includes/class-dsbpm-skin.php';
require_once DSBPM_DIR . 'includes/class-dsbpm-credentials.php';
require_once DSBPM_DIR . 'includes/class-dsbpm-partners.php';
require_once DSBPM_DIR . 'includes/class-dsbpm-metrics.php';
require_once DSBPM_DIR . 'includes/class-dsbpm-recipes.php';
require_once DSBPM_DIR . 'includes/class-dsbpm-installer.php';
require_once DSBPM_DIR . 'includes/class-dsbpm-ajax.php';
require_once DSBPM_DIR . 'includes/class-dsbpm-admin-menu.php';
require_once DSBPM_DIR . 'includes/class-dsbpm-admin.php';

function dsbpm_boot()
{
	DSBPM_Admin::instance();
}
add_action('plugins_loaded', 'dsbpm_boot');
