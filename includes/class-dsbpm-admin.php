<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Main orchestrator class for the DazeStack Bulk Plugin Manager.
 * Responsibilities:
 * - Initialization of specialized modules
 * - Internationalization (i18n)
 * - Asset enqueuing
 * - Generic infrastructure hooks (REST, Action Scheduler hooks)
 */
class DSBPM_Admin
{
	private static $instance;

	public static function instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		// Load translations
		add_action('plugins_loaded', array($this, 'init_i18n'));

		// Shared UI & Menu
		$menu = DSBPM_Admin_Menu::instance();
		add_action('admin_menu', array($menu, 'register_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue'));
		add_action('admin_post_dsbpm_export', array($menu, 'export_csv'));

		// AJAX Handlers (Delegated)
		$ajax = DSBPM_AJAX::instance();
		add_action('wp_ajax_dsbpm_preflight',          array($ajax, 'ajax_preflight'));
		add_action('wp_ajax_dsbpm_payload',            array($ajax, 'ajax_payload'));
		add_action('wp_ajax_dsbpm_simulate',           array($ajax, 'ajax_simulate'));
		add_action('wp_ajax_dsbpm_vuln_check',         array($ajax, 'ajax_vuln_check'));
		add_action('wp_ajax_dsbpm_plugins_list',       array($ajax, 'ajax_plugins_list'));
		add_action('wp_ajax_dsbpm_save_note',          array($ajax, 'ajax_save_note'));
		add_action('wp_ajax_dsbpm_toggle_pin',         array($ajax, 'ajax_toggle_pin'));
		add_action('wp_ajax_dsbpm_enqueue',            array($ajax, 'ajax_enqueue'));
		add_action('wp_ajax_dsbpm_install',            array($ajax, 'ajax_install'));
		add_action('wp_ajax_dsbpm_metrics',            array($ajax, 'ajax_metrics'));
		add_action('wp_ajax_dsbpm_resolve_slug',       array($ajax, 'ajax_resolve_slug'));
		add_action('wp_ajax_dsbpm_sort_queue',         array($ajax, 'ajax_sort_queue'));
		add_action('wp_ajax_dsbpm_deactivate',         array($ajax, 'ajax_deactivate'));
		add_action('wp_ajax_dsbpm_delete',             array($ajax, 'ajax_delete'));
		add_action('wp_ajax_dsbpm_deactivate_all',     array($ajax, 'ajax_deactivate_all'));
		add_action('wp_ajax_dsbpm_delete_all',         array($ajax, 'ajax_delete_all'));
		add_action('wp_ajax_dsbpm_remote_list',        array($ajax, 'ajax_remote_list'));
		add_action('wp_ajax_dsbpm_partner_offers',     array($ajax, 'ajax_partner_offers'));
		add_action('wp_ajax_dsbpm_store_credentials',  array($ajax, 'ajax_store_credentials'));
		add_action('wp_ajax_dsbpm_get_recipes',        array($ajax, 'ajax_get_recipes'));
		add_action('wp_ajax_dsbpm_save_recipe',        array($ajax, 'ajax_save_recipe'));
		add_action('wp_ajax_dsbpm_delete_recipe',      array($ajax, 'ajax_delete_recipe'));
		add_action('wp_ajax_dsbpm_export_wp_cli',      array($ajax, 'ajax_export_wp_cli'));
		add_action('wp_ajax_dsbpm_toggle_auto_update', array($ajax, 'ajax_toggle_auto_update'));
		add_action('wp_ajax_dsbpm_bulk_update',        array($ajax, 'ajax_bulk_update_plugins'));

		// Background / Cron hooks
		add_action('dsbpm_run_install', array($this, 'action_run_install'), 10, 1);

		// REST API
		add_action('rest_api_init', array($this, 'register_rest'));
	}

	public function init_i18n()
	{
		// Removed load_plugin_textdomain as it is discouraged for repo-hosted plugins.
	}

	public function enqueue($hook)
	{
		$screen = get_current_screen();
		$allowed_ids = array(
			'toplevel_page_' . DSBPM_BASE_SLUG,
			DSBPM_BASE_SLUG . '_page_' . DSBPM_BASE_SLUG,
			DSBPM_BASE_SLUG . '_page_' . DSBPM_SLUG,
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
		$allowed_pages = array(DSBPM_BASE_SLUG, DSBPM_SLUG, 'dsbpm-about-legal', 'dsbpm-review');
		$is_allowed_page = in_array($page, $allowed_pages, true);


		if ((! $screen || ! in_array($screen->id, $allowed_ids, true)) && ! $is_allowed_page) {
			return;
		}

		wp_enqueue_style(
			'dsbpm-admin',
			DSBPM_URL . 'assets/admin.css',
			array(),
			DSBPM_VERSION
		);

		wp_enqueue_script(
			'dsbpm-read-excel',
			DSBPM_URL . 'assets/vendor/read-excel-file.min.js',
			array(),
			DSBPM_VERSION,
			true
		);

		wp_enqueue_script(
			'dsbpm-admin',
			DSBPM_URL . 'assets/admin.js',
			array('dsbpm-read-excel', 'wp-i18n'),
			DSBPM_VERSION,
			true
		);

		if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('dsbpm-admin', 'dazestack-bulk-plugin-manager', DSBPM_DIR . 'languages');
		}

		wp_localize_script(
			'dsbpm-admin',
			'DSBPM_DATA',
			array(
				'ajaxUrl'       => admin_url('admin-ajax.php'),
				'nonce'         => wp_create_nonce('dsbpm_nonce'),
				'restHealthUrl' => esc_url_raw(rest_url('dazestack/v1/health')),
				'exportUrl'     => esc_url_raw(admin_url('admin-post.php?action=dsbpm_export')),
				'wpVersion'     => get_bloginfo('version'),
				'pluginSlug'    => DSBPM_SLUG,
				'partnerOffersBackupUrl' => esc_url_raw(DSBPM_URL . 'assets/data/partner-offers.xlsx'),
				'partnerOffersCsvBackupUrl' => esc_url_raw(DSBPM_URL . 'assets/data/partner-offers.csv'),
				'hasActionScheduler'     => function_exists('as_enqueue_async_action'),
			)
		);
	}

	public function action_run_install($args)
	{
		if (! is_array($args) || empty($args['item'])) {
			return;
		}
		DSBPM_Installer::instance()->install_item($args['item'], true);
	}

	public function register_rest()
	{
		register_rest_route(
			'dazestack/v1',
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'rest_health'),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'dazestack/v1',
			'/abilities',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'rest_abilities'),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function rest_health()
	{
		return rest_ensure_response(
			array(
				'ok'   => true,
				'time' => current_time('mysql'),
			)
		);
	}

	public function rest_abilities()
	{
		return rest_ensure_response(
			array(
				'plugin'    => 'DazeStack Bulk Plugin Manager',
				'version'   => DSBPM_VERSION,
				'abilities' => array(
					'bulk_install',
					'bulk_deactivate',
					'bulk_delete',
					'stack_export',
				),
			)
		);
	}
}
