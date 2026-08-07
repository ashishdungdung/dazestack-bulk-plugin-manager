<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Controller class for all AJAX actions in the DazeStack Bulk Plugin Manager.
 * Orchestrates calls between the UI and specialized service classes.
 */
class DSBPM_AJAX
{
	private static $instance;

	public static function instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Nonce check wrapper.
	 */
	private function nonce_check()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
	}

	/**
	 * Capability check wrapper.
	 */
	private function ensure_caps($cap)
	{
		if (! current_user_can($cap)) {
			wp_send_json_error(array('message' => __('Insufficient permissions.', 'dazestack-bulk-plugin-manager')), 403);
		}
	}

	/**
	 * Reads and sanitizes JSON body from a POST key based on a schema.
	 */
	private function read_json_body($key, $schema = array())
	{
		if (empty($_POST[$key])) {
			return array();
		}
		$raw = wp_unslash($_POST[$key]);
		$data = json_decode($raw, true);
		if (! is_array($data)) {
			return array();
		}

		if (! empty($schema)) {
			foreach ($data as &$item) {
				if (! is_array($item)) {
					continue;
				}
				foreach ($schema as $field => $sanitizer) {
					if (isset($item[$field])) {
						if ('bool' === $sanitizer) {
							$item[$field] = $this->normalize_bool($item[$field]);
						} elseif (is_callable($sanitizer)) {
							$item[$field] = call_user_func($sanitizer, $item[$field]);
						}
					}
				}
			}
		}

		return $data;
	}

	private function normalize_bool($value)
	{
		if (is_bool($value)) {
			return $value;
		}
		if (is_numeric($value)) {
			return (int) $value === 1;
		}
		$value = strtolower(trim((string) $value));
		return in_array($value, array('1', 'true', 'yes', 'y', 'on'), true);
	}

	// --- HANDLERS ---

	public function ajax_preflight()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$slugs = isset($_POST['slugs']) ? $this->read_json_body('slugs') : array();
		if (! is_array($slugs)) {
			$slugs = array();
		}
		$slugs = array_map('sanitize_key', $slugs);
		$health = array();

		foreach ($slugs as $slug) {
			$slug = sanitize_key($slug);
			if (empty($slug)) {
				continue;
			}

			$info = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array(
						'last_updated'   => true,
						'active_installs' => true,
						'tested'         => true,
						'download_link'  => true,
					),
				)
			);

			if (is_wp_error($info)) {
				$health[$slug] = array(
					'error'    => $info->get_error_message(),
					'score'    => 0,
					'warnings' => array(__('Plugin not found in WordPress.org.', 'dazestack-bulk-plugin-manager')),
				);
				continue;
			}

			$warnings = array();
			$score    = 100;

			if (! empty($info->last_updated)) {
				$last_updated = strtotime($info->last_updated);
				if ($last_updated && (time() - $last_updated) > YEAR_IN_SECONDS * 2) {
					$warnings[] = __('Not updated in 2+ years.', 'dazestack-bulk-plugin-manager');
					$score     -= 35;
				}
			}

			if (isset($info->active_installs) && $info->active_installs < 1000) {
				$warnings[] = __('Low adoption (< 1k installs).', 'dazestack-bulk-plugin-manager');
				$score     -= 25;
			}

			if (! empty($info->tested) && version_compare($info->tested, get_bloginfo('version'), '<')) {
				$warnings[] = __('Not tested with current WP version.', 'dazestack-bulk-plugin-manager');
				$score     -= 20;
			}

			if ($score < 0) {
				$score = 0;
			}

			$health[$slug] = array(
				'score'       => $score,
				'warnings'    => $warnings,
				'lastUpdated' => $info->last_updated,
				'installs'    => $info->active_installs,
				'tested'      => $info->tested,
			);
		}

		wp_send_json_success(array('health' => $health));
	}

	public function ajax_payload()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$slugs = isset($_POST['slugs']) ? $this->read_json_body('slugs') : array();
		if (! is_array($slugs)) {
			$slugs = array();
		}
		$slugs = array_map('sanitize_key', $slugs);

		$sizes = array();
		$total = 0;

		foreach ($slugs as $slug) {
			$slug = sanitize_key($slug);
			if (empty($slug)) {
				continue;
			}

			$info = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array('download_link' => true),
				)
			);

			if (is_wp_error($info) || empty($info->download_link)) {
				continue;
			}

			$head = wp_remote_head($info->download_link, array('timeout' => 12));
			if (is_wp_error($head)) {
				continue;
			}
			$len = wp_remote_retrieve_header($head, 'content-length');
			$len = $len ? (int) $len : 0;
			$sizes[$slug] = $len;
			$total         += $len;
		}

		wp_send_json_success(
			array(
				'sizes' => $sizes,
				'total' => $total,
			)
		);
	}

	public function ajax_vuln_check()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$key   = isset($_POST['apiKey']) ? sanitize_text_field(wp_unslash($_POST['apiKey'])) : '';
		$slugs = isset($_POST['slugs']) ? $this->read_json_body('slugs') : array();

		if (! is_array($slugs)) {
			$slugs = array();
		}
		$slugs = array_map('sanitize_key', $slugs);

		if (empty($key)) {
			wp_send_json_success(
				array(
					'results' => array(),
					'message' => __('WPScan API key missing. Scan skipped.', 'dazestack-bulk-plugin-manager'),
				)
			);
		}

		$metrics = DSBPM_Metrics::instance();
		$results = $metrics->run_vulnerability_scan($slugs, $key);

		wp_send_json_success(array('results' => $results));
	}

	public function ajax_metrics()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$force_refresh = isset($_GET['force_refresh']);
		$metrics = DSBPM_Metrics::instance();
		$data = $metrics->get_site_metrics($force_refresh);

		wp_send_json_success($data);
	}

	public function ajax_partner_offers()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$partners = DSBPM_Partners::instance();
		$force_refresh = isset($_GET['force_refresh']);
		$cache_key = $partners->get_cache_key();

		if (! $force_refresh) {
			$cached = get_transient($cache_key);
			if ($cached) {
				wp_send_json_success(array('offers' => $cached, 'source' => 'cache'));
			}
		}

		$csv = $partners->fetch_remote_csv();
		$source = 'remote';

		if (is_wp_error($csv)) {
			$csv = $partners->load_local_csv();
			$source = 'local';
		}

		if (is_wp_error($csv)) {
			wp_send_json_error(array('message' => $csv->get_error_message()));
		}

		$offers = $partners->parse_csv($csv);
		set_transient($cache_key, $offers, 12 * HOUR_IN_SECONDS);

		wp_send_json_success(array('offers' => $offers, 'source' => $source));
	}
	public function ajax_simulate()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$schema = array(
			'slug'     => 'sanitize_key',
			'source'   => 'esc_url_raw',
			'activate' => 'bool',
			'network'  => 'bool',
			'force'    => 'bool',
		);
		$items  = $this->read_json_body('items', $schema);
		$report = array();
		$writable = wp_is_writable(WP_PLUGIN_DIR);

		foreach ($items as $item) {
			$slug = isset($item['slug']) ? sanitize_key($item['slug']) : '';
			$issues = array();
			$requires_php = '';

			if (! $writable) {
				$issues[] = __('Plugins directory not writable.', 'dazestack-bulk-plugin-manager');
			}

			if ($slug) {
				$info = plugins_api(
					'plugin_information',
					array(
						'slug'   => $slug,
						'fields' => array('sections' => false),
					)
				);
				if (is_wp_error($info)) {
					$issues[] = __('Plugin not found in WP.org repository.', 'dazestack-bulk-plugin-manager');
				} else {
					if (! empty($info->requires_php)) {
						$requires_php = $info->requires_php;
						if (version_compare(PHP_VERSION, $info->requires_php, '<')) {
							/* translators: 1: Required PHP version, 2: Current PHP version */
							$issues[] = sprintf(__('Requires PHP %1$s (current: %2$s).', 'dazestack-bulk-plugin-manager'), $info->requires_php, PHP_VERSION);
						}
					}
				}
			}

			$report[$slug] = array(
				'issues' => $issues,
				'php'    => $requires_php,
			);
		}

		wp_send_json_success(array('report' => $report));
	}

	public function ajax_plugins_list()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = get_plugins();
		$active  = get_option('active_plugins', array());
		$metrics = DSBPM_Metrics::instance();
		$meta_map = $metrics->get_plugin_meta_map();

		$list = array();
		foreach ($plugins as $file => $data) {
			$slug = dirname($file);
			if ('.' === $slug) {
				$slug = basename($file, '.php');
			}
			$meta = isset($meta_map[$file]) ? $meta_map[$file] : array();
			$list[] = array(
				'file'    => $file,
				'slug'    => $slug,
				'name'    => isset($data['Name']) ? $data['Name'] : $slug,
				'version' => isset($data['Version']) ? $data['Version'] : '',
				'active'  => in_array($file, $active, true),
				'pinned'  => ! empty($meta['pinned']),
				'note'    => isset($meta['note']) ? $meta['note'] : '',
			);
		}

		wp_send_json_success(array('plugins' => $list));
	}

	public function ajax_save_note()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$file = isset($_POST['file']) ? sanitize_text_field(wp_unslash($_POST['file'])) : '';
		$note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';

		if (empty($file)) {
			wp_send_json_error(array('message' => __('Plugin file required.', 'dazestack-bulk-plugin-manager')), 400);
		}

		$metrics = DSBPM_Metrics::instance();
		$meta = $metrics->get_plugin_meta_map();
		if (! isset($meta[$file])) {
			$meta[$file] = array();
		}
		$meta[$file]['note'] = $note;
		$metrics->save_plugin_meta_map($meta);

		wp_send_json_success();
	}

	public function ajax_toggle_pin()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$file = isset($_POST['file']) ? sanitize_text_field(wp_unslash($_POST['file'])) : '';

		if (empty($file)) {
			wp_send_json_error(array('message' => __('Plugin file required.', 'dazestack-bulk-plugin-manager')), 400);
		}

		$metrics = DSBPM_Metrics::instance();
		$meta = $metrics->get_plugin_meta_map();
		if (! isset($meta[$file])) {
			$meta[$file] = array();
		}
		$meta[$file]['pinned'] = ! empty($meta[$file]['pinned']) ? false : true;
		$metrics->save_plugin_meta_map($meta);

		wp_send_json_success(array('pinned' => $meta[$file]['pinned']));
	}

	public function ajax_enqueue()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		if (! function_exists('as_enqueue_async_action')) {
			wp_send_json_error(array('message' => __('Action Scheduler not available.', 'dazestack-bulk-plugin-manager')), 400);
		}

		$items = $this->read_json_body('items');
		foreach ($items as $item) {
			as_enqueue_async_action('dsbpm_run_install', array('item' => $item), 'dsbpm');
		}
		wp_send_json_success(array('message' => __('Queued in background.', 'dazestack-bulk-plugin-manager')));
	}

	public function ajax_resolve_slug()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		if (empty($name)) {
			wp_send_json_error(array('message' => __('Name required.', 'dazestack-bulk-plugin-manager')), 400);
		}

		$result = plugins_api(
			'query_plugins',
			array(
				'search'  => $name,
				'per_page' => 5,
				'fields'  => array('name' => true),
			)
		);

		if (is_wp_error($result) || empty($result->plugins)) {
			wp_send_json_success(array('matches' => array()));
		}

		$matches = array();
		foreach ($result->plugins as $plugin) {
			$matches[] = array(
				'slug' => $plugin->slug,
				'name' => $plugin->name,
			);
		}

		wp_send_json_success(array('matches' => $matches));
	}

	public function ajax_sort_queue()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$items = $this->read_json_body('items');
		if (empty($items)) {
			wp_send_json_success(array('items' => array()));
		}

		$deps = array();
		foreach ($items as $item) {
			$slug = isset($item['slug']) ? sanitize_key($item['slug']) : '';
			if (empty($slug)) {
				continue;
			}
			$info = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array('requires_plugins' => true),
				)
			);
			if (is_wp_error($info)) {
				continue;
			}
			if (! empty($info->requires_plugins) && is_array($info->requires_plugins)) {
				$deps[$slug] = $info->requires_plugins;
			}
		}

		$sorted = $this->topo_sort($items, $deps);
		wp_send_json_success(
			array(
				'items' => $sorted,
				'deps'  => $deps,
			)
		);
	}

	private function topo_sort($items, $deps)
	{
		$map = array();
		foreach ($items as $item) {
			if (empty($item['slug'])) {
				continue;
			}
			$map[$item['slug']] = $item;
		}

		$visited = array();
		$stack   = array();

		$visit = function ($slug) use (&$visit, &$visited, &$stack, $deps, $map) {
			if (isset($visited[$slug])) {
				return;
			}
			$visited[$slug] = true;
			if (isset($deps[$slug])) {
				foreach ($deps[$slug] as $dep) {
					if (isset($map[$dep])) {
						$visit($dep);
					}
				}
			}
			if (isset($map[$slug])) {
				$stack[] = $map[$slug];
			}
		};

		foreach ($map as $slug => $item) {
			$visit($slug);
		}

		return $stack;
	}

	public function ajax_deactivate()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('activate_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = $this->read_json_body('plugins');
		if (empty($plugins)) {
			wp_send_json_success(array('message' => __('Nothing to deactivate.', 'dazestack-bulk-plugin-manager')));
		}
		deactivate_plugins($plugins, false, false);
		wp_send_json_success(array('message' => __('Deactivated.', 'dazestack-bulk-plugin-manager')));
	}

	public function ajax_delete()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('delete_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$plugins = $this->read_json_body('plugins');
		if (empty($plugins)) {
			wp_send_json_success(array('message' => __('Nothing to delete.', 'dazestack-bulk-plugin-manager')));
		}

		deactivate_plugins($plugins, false, false);
		$result = delete_plugins($plugins);

		if (is_wp_error($result)) {
			wp_send_json_error(array('message' => $result->get_error_message()), 500);
		}

		wp_send_json_success(array('message' => __('Deleted.', 'dazestack-bulk-plugin-manager')));
	}

	public function ajax_deactivate_all()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('activate_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = array_keys(get_plugins());
		$keep    = plugin_basename(DSBPM_FILE);
		$targets = array();

		foreach ($plugins as $plugin) {
			if ($plugin === $keep) {
				continue;
			}
			$targets[] = $plugin;
		}

		deactivate_plugins($targets, false, false);
		wp_send_json_success(array('message' => __('Deactivated all plugins except DazeStack.', 'dazestack-bulk-plugin-manager')));
	}

	public function ajax_delete_all()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('delete_plugins');

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$plugins = array_keys(get_plugins());
		$keep    = plugin_basename(DSBPM_FILE);
		$targets = array();

		foreach ($plugins as $plugin) {
			if ($plugin === $keep) {
				continue;
			}
			$targets[] = $plugin;
		}

		deactivate_plugins($targets, false, false);
		$result = delete_plugins($targets);

		if (is_wp_error($result)) {
			wp_send_json_error(array('message' => $result->get_error_message()), 500);
		}

		wp_send_json_success(array('message' => __('Deleted all plugins except DazeStack.', 'dazestack-bulk-plugin-manager')));
	}

	public function ajax_remote_list()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
		if (empty($url)) {
			wp_send_json_error(array('message' => __('URL required.', 'dazestack-bulk-plugin-manager')), 400);
		}

		if (! wp_http_validate_url($url)) {
			wp_send_json_error(array('message' => __('Invalid URL.', 'dazestack-bulk-plugin-manager')), 400);
		}

		$parsed = wp_parse_url($url);
		$host   = isset($parsed['host']) ? $parsed['host'] : '';
		if ($host) {
			$ip = gethostbyname($host);
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && '127.0.0.1' !== $ip) {
				wp_send_json_error(array('message' => __('Remote fetches from private or local IP addresses are blocked.', 'dazestack-bulk-plugin-manager')), 400);
			}
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 12,
				'limit_response_size' => 5242880, // 5MB limit
			)
		);

		if (is_wp_error($response)) {
			wp_send_json_error(array('message' => $response->get_error_message()), 500);
		}

		$body = wp_remote_retrieve_body($response);
		$body = trim($body);
		if (empty($body)) {
			wp_send_json_error(array('message' => __('Empty response.', 'dazestack-bulk-plugin-manager')), 400);
		}

		$items = array();
		if (0 === strpos($body, '[') || 0 === strpos($body, '{')) {
			$data = json_decode($body, true);
			if (is_array($data)) {
				$items = $data;
			}
		} else {
			$lines   = preg_split('/\r\n|\r|\n/', $body);
			$headers = array();
			foreach ($lines as $index => $line) {
				if ('' === trim($line)) {
					continue;
				}
				$cols = str_getcsv($line);
				if (0 === $index) {
					$headers = $cols;
					continue;
				}
				$row = array();
				foreach ($cols as $i => $value) {
					$key        = isset($headers[$i]) ? $headers[$i] : 'col' . $i;
					$row[$key] = $value;
				}
				$items[] = $row;
			}
		}

		wp_send_json_success(array('items' => $items));
	}

	public function ajax_store_credentials()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$creds = array(
			'hostname'        => isset($_POST['hostname']) ? sanitize_text_field(wp_unslash($_POST['hostname'])) : '',
			'username'        => isset($_POST['username']) ? sanitize_text_field(wp_unslash($_POST['username'])) : '',
			'password'        => isset($_POST['password']) ? wp_unslash($_POST['password']) : '',
			'public_key'      => isset($_POST['public_key']) ? sanitize_text_field(wp_unslash($_POST['public_key'])) : '',
			'private_key'     => isset($_POST['private_key']) ? sanitize_text_field(wp_unslash($_POST['private_key'])) : '',
			'connection_type' => isset($_POST['connection_type']) ? sanitize_text_field(wp_unslash($_POST['connection_type'])) : '',
		);

		if (empty($creds['hostname']) || empty($creds['username'])) {
			wp_send_json_error(array('message' => __('Hostname and username are required.', 'dazestack-bulk-plugin-manager')), 400);
		}

		if (! WP_Filesystem($creds)) {
			wp_send_json_error(array('message' => __('Filesystem credentials rejected.', 'dazestack-bulk-plugin-manager')), 403);
		}

		DSBPM_Credentials::instance()->store($creds);
		wp_send_json_success(array('message' => __('Credentials stored.', 'dazestack-bulk-plugin-manager')));
	}

	public function ajax_install()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$schema = array(
			'slug'     => 'sanitize_key',
			'source'   => 'esc_url_raw',
			'version'  => 'sanitize_text_field',
			'activate' => 'bool',
			'network'  => 'bool',
			'force'    => 'bool',
		);
		$data = $this->read_json_body('item', $schema);
		if (empty($data)) {
			wp_send_json_error(array('message' => __('Invalid plugin data.', 'dazestack-bulk-plugin-manager')), 400);
		}

		$item = reset($data);
		$installer = DSBPM_Installer::instance();
		$result = $installer->install_item($item);

		if (is_wp_error($result)) {
			$error_data = $result->get_error_data();
			$form = '';
			if (is_array($error_data) && isset($error_data['form'])) {
				$form = $error_data['form'];
			}
			wp_send_json_error(
				array(
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
					'form'    => $form,
				),
				409
			);
		}

		wp_send_json_success($result);
	}

	public function ajax_get_recipes()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$recipes = DSBPM_Recipes::instance()->get_all_recipes();
		wp_send_json_success(array('recipes' => $recipes));
	}

	public function ajax_save_recipe()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$title       = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
		$description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
		$items       = $this->read_json_body('items');

		$recipe = DSBPM_Recipes::instance()->save_recipe($title, $description, $items);
		if (is_wp_error($recipe)) {
			wp_send_json_error(array('message' => $recipe->get_error_message()), 400);
		}

		wp_send_json_success(array('recipe' => $recipe, 'message' => __('Recipe saved.', 'dazestack-bulk-plugin-manager')));
	}

	public function ajax_delete_recipe()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$id = isset($_POST['id']) ? sanitize_key(wp_unslash($_POST['id'])) : '';
		if (empty($id)) {
			wp_send_json_error(array('message' => __('Recipe ID required.', 'dazestack-bulk-plugin-manager')), 400);
		}

		DSBPM_Recipes::instance()->delete_recipe($id);
		wp_send_json_success(array('message' => __('Recipe deleted.', 'dazestack-bulk-plugin-manager')));
	}

	public function ajax_export_wp_cli()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$items = $this->read_json_body('items');
		$cmd   = DSBPM_Recipes::instance()->generate_wp_cli($items);

		wp_send_json_success(array('command' => $cmd));
	}

	public function ajax_toggle_auto_update()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('install_plugins');

		$file = isset($_POST['file']) ? sanitize_text_field(wp_unslash($_POST['file'])) : '';
		if (empty($file)) {
			wp_send_json_error(array('message' => __('Plugin file required.', 'dazestack-bulk-plugin-manager')), 400);
		}

		$auto_updates = get_option('auto_update_plugins', array());
		if (! is_array($auto_updates)) {
			$auto_updates = array();
		}

		if (in_array($file, $auto_updates, true)) {
			$auto_updates = array_diff($auto_updates, array($file));
			$enabled = false;
		} else {
			$auto_updates[] = $file;
			$enabled = true;
		}

		update_option('auto_update_plugins', array_values($auto_updates));
		wp_send_json_success(array('enabled' => $enabled));
	}

	public function ajax_bulk_update_plugins()
	{
		check_ajax_referer('dsbpm_nonce', 'nonce');
		$this->ensure_caps('update_plugins');

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$plugins = $this->read_json_body('plugins');
		if (empty($plugins) || ! is_array($plugins)) {
			wp_send_json_error(array('message' => __('No plugins specified for update.', 'dazestack-bulk-plugin-manager')), 400);
		}

		$skin     = new DSBPM_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader($skin);
		$results  = $upgrader->bulk_upgrade($plugins);

		wp_send_json_success(
			array(
				'results' => $results,
				'log'     => $skin->messages,
				'message' => __('Bulk update completed.', 'dazestack-bulk-plugin-manager'),
			)
		);
	}
}
