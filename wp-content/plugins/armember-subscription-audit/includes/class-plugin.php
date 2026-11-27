<?php
/**
 * Plugin bootstrap.
 *
 * Phase 1 screens stay admin-only and read-only. Phase 2 adds: plugin tables, a background queue worker
 * (must also register outside wp-admin because WP-Cron / Action Scheduler run on non-admin requests) and
 * nonce/capability-gated admin handlers. Nothing is ever output on the front end.
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
		// Worker hook + schedule: needed on cron/async requests too.
		Queue::register_hooks( new Queue() );

		if ( ! is_admin() ) {
			return; // Nothing is ever exposed on the front end.
		}
		add_action( 'admin_init', array( Installer::class, 'maybe_install' ) );
		$repository = new Member_Repository( new Payment_History(), new Subscription_Audit() );
		$sync_admin = new Sync_Admin( $repository );
		$sync_admin->register();
		( new Admin_Assets() )->register();
		( new Admin_Menu( $repository, $sync_admin ) )->register();
	}

	public static function activate() {
		Installer::install();
		Queue::ensure_scheduled();
	}

	public static function deactivate() {
		Queue::unschedule(); // Tables and logs are kept (audit trail).
	}

	/**
	 * Debug logging, only when WP_DEBUG_LOG is on.
	 *
	 * @param string $message Message.
	 */
	public static function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[ARMember Subscription Audit] ' . Logger::redact( $message ) );
		}
	}
}
