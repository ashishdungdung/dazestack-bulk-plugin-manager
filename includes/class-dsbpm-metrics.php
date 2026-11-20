<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Handles site metrics, directory sizing, and vulnerability scanning logic.
 */
class DSBPM_Metrics
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

	public function get_site_metrics($force_refresh = false)
	{
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugins      = get_plugins();
		$active       = get_option('active_plugins', array());
		$active_count = count($active);
		$total_count  = count($plugins);

		$total_size = 0;
		$largest = array('name' => '', 'size' => 0);
		$cache_metrics = get_transient('dsbpm_metrics_cache');

		if (is_array($cache_metrics) && ! $force_refresh) {
			$total_size = $cache_metrics['total_size'];
			$largest = $cache_metrics['largest'];
		} else {
			foreach ($plugins as $file => $data) {
				$path = WP_PLUGIN_DIR . '/' . dirname($file);
				if ('.' === dirname($file)) {
					$path = WP_PLUGIN_DIR . '/' . $file;
				}
				$size = $this->path_size($path);
				$total_size += $size;
				if ($size > $largest['size']) {
					$largest['size'] = $size;
					$largest['name'] = isset($data['Name']) ? $data['Name'] : $file;
				}
			}
			set_transient('dsbpm_metrics_cache', array('total_size' => $total_size, 'largest' => $largest), 1 * HOUR_IN_SECONDS);
		}

		$pinned = 0;
		$meta = $this->get_plugin_meta_map();
		foreach ($meta as $info) {
			if (! empty($info['pinned'])) {
				$pinned++;
			}
		}

		$auto_updates = get_option('auto_update_plugins', array());
		if (! is_array($auto_updates)) {
			$auto_updates = array();
		}
		$auto_updates_count = count(array_intersect($auto_updates, array_keys($plugins)));

		$average_size = $total_count > 0 ? (int) ($total_size / $total_count) : 0;

		return array(
			'installed'          => $total_count,
			'active'             => $active_count,
			'inactive'           => $total_count - $active_count,
			'pinned'             => $pinned,
			'autoUpdatesEnabled' => $auto_updates_count,
			'totalSize'          => $total_size,
			'averageSize'        => $average_size,
			'largest'            => $largest,
			'wpVersion'          => get_bloginfo('version'),
			'phpVersion'         => PHP_VERSION,
		);
	}

	public function path_size($path, $depth = 0, $max_depth = 8)
	{
		if ($depth > $max_depth) {
			return 0;
		}
		$size = 0;
		if (is_file($path)) {
			return (int) filesize($path);
		}
		$path = rtrim($path, '/');
		if (! is_dir($path) || is_link($path)) {
			return 0;
		}

		$files = scandir($path);
		if (false === $files) {
			return 0;
		}

		foreach ($files as $file) {
			if ('.' === $file || '..' === $file) {
				continue;
			}
			$full = $path . '/' . $file;
			if (is_link($full)) {
				continue;
			}
			if (is_dir($full)) {
				$size += $this->path_size($full, $depth + 1, $max_depth);
			} else {
				$size += (int) filesize($full);
			}
		}
		return $size;
	}

	public function get_plugin_meta_map()
	{
		$meta = get_option('dsbpm_plugin_meta', array());
		return is_array($meta) ? $meta : array();
	}

	public function save_plugin_meta_map($map)
	{
		update_option('dsbpm_plugin_meta', $map);
	}

	public function run_vulnerability_scan($slugs, $api_key)
	{
		$results = array();
		foreach ($slugs as $slug) {
			$slug = sanitize_key($slug);
			if (empty($slug)) {
				continue;
			}
			$cache_key = 'dsbpm_vuln_' . $slug;
			$cached = get_transient($cache_key);
			if ($cached) {
				$results[$slug] = $cached;
				continue;
			}

			$response = wp_remote_get(
				'https://wpscan.com/api/v3/plugins/' . $slug,
				array(
					'timeout' => 12,
					'headers' => array(
						'Authorization' => 'Token token=' . $api_key,
					),
				)
			);

			if (is_wp_error($response)) {
				$results[$slug] = array(
					'error' => $response->get_error_message(),
				);
				continue;
			}

			$data = json_decode(wp_remote_retrieve_body($response), true);
			$entry = array(
				'critical' => false,
				'issues'   => array(),
			);
			if (is_array($data) && isset($data[$slug]['vulnerabilities'])) {
				foreach ($data[$slug]['vulnerabilities'] as $vuln) {
					$severity = isset($vuln['severity']) ? strtolower($vuln['severity']) : '';
					$cvss = isset($vuln['cvss']) ? floatval($vuln['cvss']) : 0;
					$fixed = isset($vuln['fixed_in']) ? $vuln['fixed_in'] : '';
					$is_critical = ('critical' === $severity || $cvss >= 9.0) && empty($fixed);
					$entry['issues'][] = array(
						'title'   => isset($vuln['title']) ? $vuln['title'] : 'Vulnerability',
						'severity' => $severity,
						'cvss'    => $cvss,
						'fixedIn' => $fixed,
					);
					if ($is_critical) {
						$entry['critical'] = true;
					}
				}
			}
			set_transient($cache_key, $entry, 30 * MINUTE_IN_SECONDS);
			$results[$slug] = $entry;
		}
		return $results;
	}
}
