<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Handles encrypted storage and retrieval of filesystem credentials.
 */
class DSBPM_Credentials
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

	private function get_credentials_key()
	{
		return 'dsbpm_fs_creds_' . get_current_user_id();
	}

	private function get_secret_key()
	{
		$salt = function_exists('wp_salt') ? wp_salt('auth') : 'dsbpm_default_fallback_salt';
		$auth_key  = defined('AUTH_KEY') ? AUTH_KEY : '';
		$auth_salt = defined('AUTH_SALT') ? AUTH_SALT : '';
		return hash('sha256', $auth_key . $auth_salt . $salt);
	}

	public function encrypt($creds)
	{
		$json = wp_json_encode($creds);
		if (function_exists('openssl_encrypt')) {
			$key    = $this->get_secret_key();
			$iv     = openssl_random_pseudo_bytes(16);
			$cipher = openssl_encrypt($json, 'AES-256-CBC', $key, 0, $iv);
			if ($cipher) {
				return base64_encode($iv) . ':' . $cipher;
			}
		}
		return base64_encode($json);
	}

	public function decrypt($payload)
	{
		if (empty($payload)) {
			return array();
		}
		$payload = (string) $payload;
		if (false !== strpos($payload, ':') && function_exists('openssl_decrypt')) {
			list($iv_b64, $cipher) = explode(':', $payload, 2);
			$iv = base64_decode($iv_b64, true);
			if ($iv) {
				$key  = $this->get_secret_key();
				$json = openssl_decrypt($cipher, 'AES-256-CBC', $key, 0, $iv);
				$data = json_decode($json, true);
				return is_array($data) ? $data : array();
			}
		}

		$json = base64_decode($payload, true);
		$data = json_decode($json, true);
		return is_array($data) ? $data : array();
	}

	public function store($creds)
	{
		$payload = $this->encrypt($creds);
		set_transient($this->get_credentials_key(), $payload, 30 * MINUTE_IN_SECONDS);
	}

	public function get()
	{
		$payload = get_transient($this->get_credentials_key());
		if (! $payload) {
			return array();
		}
		return $this->decrypt($payload);
	}

	public function clear()
	{
		delete_transient($this->get_credentials_key());
	}
}
