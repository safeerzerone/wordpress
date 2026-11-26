<?php
/**
 * Plugin bootstrap. Registers admin-only hooks. No cron, no webhooks, no writes.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot() {
		if ( ! is_admin() ) {
			return; // Nothing is ever exposed on the front end.
		}
		$repository = new Member_Repository( new Payment_History(), new Subscription_Audit() );
		( new Admin_Assets() )->register();
		( new Admin_Menu( $repository ) )->register();
	}

	/**
	 * Debug logging, only when WP_DEBUG_LOG is on.
	 *
	 * @param string $message Message.
	 */
	public static function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[ARMember Subscription Audit] ' . $message );
		}
	}
}
