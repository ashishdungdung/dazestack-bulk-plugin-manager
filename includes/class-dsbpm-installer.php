<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Handles the actual plugin installation, activation, and filesystem checks.
 */
class DSBPM_Installer
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

	public function install_item($item, $force = false)
	{
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$slug     = isset($item['slug']) ? sanitize_key($item['slug']) : '';
		$source   = isset($item['source']) ? esc_url_raw($item['source']) : '';
		$version  = isset($item['version']) ? sanitize_text_field($item['version']) : '';
		$activate = ! empty($item['activate']);
		$network  = ! empty($item['network']);
		$force    = ! empty($item['force']) || $force;

		if (empty($slug) && empty($source)) {
			return new WP_Error('dsbpm_missing', __('Slug or source required.', 'dazestack-bulk-plugin-manager'));
		}

		$fs_ready = $this->maybe_require_filesystem();
		if (is_wp_error($fs_ready)) {
			return $fs_ready;
		}

		$installed_file = $this->find_plugin_file($slug);
		$current_ver    = '';
		if ($installed_file) {
			$all_plugins = get_plugins();
			if (isset($all_plugins[$installed_file]['Version'])) {
				$current_ver = $all_plugins[$installed_file]['Version'];
			}
		}

		if ($installed_file) {
			$metrics = DSBPM_Metrics::instance();
			$meta_map = $metrics->get_plugin_meta_map();
			$meta = isset($meta_map[$installed_file]) ? $meta_map[$installed_file] : array();

			if (! empty($meta['pinned']) && ! $force) {
				return new WP_Error('dsbpm_pinned', __('Plugin is pinned and cannot be updated.', 'dazestack-bulk-plugin-manager'));
			}
		}

		if ($this->is_vulnerable_blocked($slug) && ! $force) {
			return new WP_Error('dsbpm_vulnerable', __('Security alert: critical vulnerability detected.', 'dazestack-bulk-plugin-manager'));
		}

		if ($installed_file && empty($source) && empty($version) && ! $force) {
			return array(
				'status'       => 'skipped',
				'message'      => __('Already installed.', 'dazestack-bulk-plugin-manager'),
				'pluginFile'   => $installed_file,
				'currentVersion' => $current_ver,
			);
		}

		if ($installed_file && $version && $current_ver && version_compare($current_ver, $version, '>') && ! $force) {
			return array(
				'status'         => 'downgrade_blocked',
				'message'        => __('Installed version is newer. Enable force to downgrade.', 'dazestack-bulk-plugin-manager'),
				'pluginFile'     => $installed_file,
				'currentVersion' => $current_ver,
			);
		}

		$package = '';
		if ($source) {
			$package = $source;
		} elseif ($slug && $version) {
			$package = sprintf('https://downloads.wordpress.org/plugin/%s.%s.zip', $slug, $version);
		} elseif ($slug) {
			$info = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array('download_link' => true),
				)
			);
			if (is_wp_error($info) || empty($info->download_link)) {
				return new WP_Error('dsbpm_not_found', __('Plugin not found in repository.', 'dazestack-bulk-plugin-manager'));
			}
			$package = $info->download_link;
		}

		$skin     = new DSBPM_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader($skin);

		$result = false;
		if ($installed_file && empty($source) && empty($version)) {
			$result = $upgrader->upgrade($installed_file);
		} else {
			$args   = array(
				'overwrite_package' => true,
				'clear_destination' => (bool) $installed_file,
			);
			$result = $upgrader->install($package, $args);
		}

		if (is_wp_error($result)) {
			return $result;
		}

		if (! $result) {
			return new WP_Error('dsbpm_install_failed', __('Install failed.', 'dazestack-bulk-plugin-manager'));
		}

		$plugin_file = $upgrader->plugin_info();
		if (empty($plugin_file)) {
			$plugin_file = $installed_file;
		}

		$activation_error = '';
		if ($activate && $plugin_file) {
			try {
				$activate_result = activate_plugin($plugin_file, '', $network, true);
				if (is_wp_error($activate_result)) {
					$activation_error = $activate_result->get_error_message();
				}
			} catch (\Throwable $e) {
				deactivate_plugins($plugin_file, true, $network);
				/* translators: 1: Error message */
				$activation_error = sprintf(__('Activation rolled back due to fatal error: %s', 'dazestack-bulk-plugin-manager'), $e->getMessage());
			}
		}

		return array(
			'status'     => $activation_error ? 'activation_failed' : 'installed',
			'message'    => $activation_error ? $activation_error : __('Installed', 'dazestack-bulk-plugin-manager'),
			'pluginFile' => $plugin_file,
			'log'        => $skin->messages,
		);
	}

	public function find_plugin_file($slug)
	{
		if (empty($slug)) {
			return '';
		}
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = get_plugins();
		foreach ($plugins as $file => $data) {
			if (dirname($file) === $slug) {
				return $file;
			}
			if (basename($file, '.php') === $slug) {
				return $file;
			}
		}
		return '';
	}

	public function maybe_require_filesystem()
	{
		if (wp_is_writable(WP_PLUGIN_DIR)) {
			return true;
		}

		$creds_obj = DSBPM_Credentials::instance();
		$creds = $creds_obj->get();

		if ($creds) {
			if (WP_Filesystem($creds)) {
				return true;
			}
			$creds_obj->clear(); // Clear bad creds
		}

		$url = admin_url('admin.php?page=' . DSBPM_SLUG);
		ob_start();
		$form_creds = request_filesystem_credentials($url, '', false, WP_PLUGIN_DIR, null);
		$form = ob_get_clean();

		if (false === $form_creds) {
			return new WP_Error('dsbpm_fs_creds', __('Filesystem credentials required.', 'dazestack-bulk-plugin-manager'), array('form' => $form));
		}

		return true;
	}

	private function is_vulnerable_blocked($slug)
	{
		if (empty($slug)) {
			return false;
		}
		$cache = get_transient('dsbpm_vuln_' . $slug);
		if (empty($cache) || ! is_array($cache)) {
			return false;
		}
		return ! empty($cache['critical']);
	}
}
