<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

class DSBPM_Upgrader_Skin extends WP_Upgrader_Skin {
	public $messages = array();

	public function header() {}
	public function footer() {}

	public function feedback( $data, ...$args ) {
		if ( is_wp_error( $data ) ) {
			$this->messages[] = $data->get_error_message();
			return;
		}

		if ( is_array( $data ) ) {
			$data = implode( ' ', $data );
		}

		if ( ! empty( $args ) ) {
			$data = vsprintf( $data, $args );
		}

		$this->messages[] = wp_strip_all_tags( $data );
	}
}
