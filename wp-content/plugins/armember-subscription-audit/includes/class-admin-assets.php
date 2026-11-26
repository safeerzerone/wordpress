<?php
/**
 * Admin CSS loader (only on this plugin's screens).
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin_Assets {

	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( false === strpos( (string) $hook, Admin_Menu::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'armsa-admin', ARMSA_URL . 'admin/css/admin.css', array(), ARMSA_VERSION );
	}
}
