<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Handles fetching, parsing, and caching of the remote partner offers feed.
 */
class DSBPM_Partners
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

	public function get_cache_key()
	{
		return 'dsbpm_partner_offers_cache';
	}

	public function fetch_remote_csv()
	{
		$url = defined('DSBPM_PARTNER_OFFERS_URL') ? DSBPM_PARTNER_OFFERS_URL : '';
		if (empty($url)) {
			return new WP_Error('dsbpm_partner_offers_url', __('Partner offers URL is not configured.', 'dazestack-bulk-plugin-manager'));
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 12,
			)
		);

		if (is_wp_error($response)) {
			return $response;
		}

		$body = trim(wp_remote_retrieve_body($response));
		if ('' === $body) {
			return new WP_Error('dsbpm_partner_offers_empty', __('Partner offers feed returned empty content.', 'dazestack-bulk-plugin-manager'));
		}

		return $body;
	}

	public function load_local_csv()
	{
		$path = DSBPM_DIR . 'assets/data/partner-offers.csv';
		if (! file_exists($path)) {
			return new WP_Error('dsbpm_partner_offers_missing', __('Local partner offers backup not found.', 'dazestack-bulk-plugin-manager'));
		}

		$body = file_get_contents($path);
		if (false === $body || '' === trim($body)) {
			return new WP_Error('dsbpm_partner_offers_unreadable', __('Local partner offers backup is empty.', 'dazestack-bulk-plugin-manager'));
		}

		return $body;
	}

	public function parse_csv($csv)
	{
		$csv = trim((string) $csv);
		if ('' === $csv) {
			return array();
		}

		$lines = preg_split('/\r\n|\r|\n/', $csv);
		if (empty($lines)) {
			return array();
		}

		$headers = str_getcsv(array_shift($lines));
		if (empty($headers)) {
			return array();
		}

		$offers = array();
		foreach ($lines as $line) {
			$data = str_getcsv($line);
			if (count($data) !== count($headers)) {
				continue;
			}
			$row = array_combine($headers, $data);
			$normalized = $this->normalize_row($row);
			if ($normalized) {
				$offers[] = $normalized;
			}
		}

		usort($offers, function ($a, $b) {
			return $a['priority'] <=> $b['priority'];
		});

		return $offers;
	}

	private function normalize_row($row)
	{
		if (! is_array($row)) {
			return null;
		}

		$map = array(
			'type'         => 'type',
			'id'           => 'id',
			'title'        => 'title',
			'description'  => 'description',
			'badge'        => 'badge',
			'href'         => 'href',
			'url'          => 'href',
			'link'         => 'href',
			'image'        => 'image',
			'cta'          => 'cta',
			'placement'    => 'placement',
			'width'        => 'width',
			'height'       => 'height',
			'maxwidth'     => 'maxWidth',
			'max_width'    => 'maxWidth',
			'minheight'    => 'minHeight',
			'min_height'   => 'minHeight',
			'aspectratio'  => 'aspectRatio',
			'aspect_ratio' => 'aspectRatio',
			'scalemode'    => 'scaleMode',
			'scale_mode'   => 'scaleMode',
			'active'       => 'active',
			'priority'     => 'priority',
		);

		$normalized = array();
		foreach ($row as $key => $value) {
			$key = strtolower(trim((string) $key));
			if (isset($map[$key])) {
				$normalized[$map[$key]] = is_string($value) ? trim($value) : $value;
			}
		}

		$active = $this->normalize_bool($normalized['active'] ?? '1');
		if (! $active) {
			return null;
		}

		$priority = isset($normalized['priority']) && is_numeric($normalized['priority'])
			? (int) $normalized['priority']
			: 999;

		return array(
			'type'        => sanitize_key($normalized['type'] ?? ''),
			'id'          => sanitize_text_field($normalized['id'] ?? ''),
			'title'       => sanitize_text_field($normalized['title'] ?? ''),
			'description' => sanitize_textarea_field($normalized['description'] ?? ''),
			'badge'       => sanitize_text_field($normalized['badge'] ?? ''),
			'href'        => esc_url_raw($normalized['href'] ?? ''),
			'image'       => esc_url_raw($normalized['image'] ?? ''),
			'cta'         => sanitize_text_field($normalized['cta'] ?? ''),
			'placement'   => sanitize_key($normalized['placement'] ?? ''),
			'width'       => $this->sanitize_dimension($normalized['width'] ?? ''),
			'height'      => $this->sanitize_dimension($normalized['height'] ?? ''),
			'maxWidth'    => $this->sanitize_dimension($normalized['maxWidth'] ?? ''),
			'minHeight'   => $this->sanitize_dimension($normalized['minHeight'] ?? ''),
			'aspectRatio' => $this->sanitize_aspect_ratio($normalized['aspectRatio'] ?? ''),
			'scaleMode'   => $this->sanitize_scale_mode($normalized['scaleMode'] ?? ''),
			'active'      => $active,
			'priority'    => $priority,
		);
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

	private function sanitize_dimension($value)
	{
		$value = trim((string) $value);
		if ('' === $value) {
			return '';
		}
		if (preg_match('/^\d+(\.\d+)?(px|%|rem|em|vh|vw)?$/', $value)) {
			return $value;
		}
		return '';
	}

	private function sanitize_aspect_ratio($value)
	{
		$value = trim((string) $value);
		if ('' === $value) {
			return '';
		}
		if (preg_match('/^\d+(\.\d+)?\s*(\/\s*\d+(\.\d+)?)?$/', $value)) {
			return str_replace(' ', '', $value);
		}
		return '';
	}

	private function sanitize_scale_mode($value)
	{
		$value = strtolower(trim((string) $value));
		if (in_array($value, array('cover', 'contain'), true)) {
			return $value;
		}
		return '';
	}
}
