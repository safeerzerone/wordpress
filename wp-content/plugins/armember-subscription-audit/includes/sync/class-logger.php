<?php
/**
 * Append-only processing log + administrator change audit.
 *
 * Immutability: this class exposes insert/read methods only. There is deliberately no update or delete
 * path for armsa_logs / armsa_changes anywhere in the plugin.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Logger {

	/** Operation run id shared by every entry of one operation. */
	private $run_id;
	private $operation;
	private $user_id;
	private $plan_id;
	private $gateway;
	private $initiator;

	/**
	 * @param string $operation assess | update | sync | queue | retrieve.
	 * @param int    $user_id   Member.
	 * @param int    $plan_id   Plan.
	 * @param string $gateway   Adapter id.
	 * @param int    $initiator Admin user id; 0 = background worker.
	 */
	public function __construct( $operation, $user_id, $plan_id, $gateway = '', $initiator = 0 ) {
		$this->run_id    = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'run', true );
		$this->operation = $operation;
		$this->user_id   = (int) $user_id;
		$this->plan_id   = (int) $plan_id;
		$this->gateway   = (string) $gateway;
		$this->initiator = (int) $initiator;
	}

	public function run_id() {
		return $this->run_id;
	}

	public function set_gateway( $gateway ) {
		$this->gateway = (string) $gateway;
	}

	/** Callable compatible with Assessor's $log. */
	public function callback() {
		return function ( $step, $status, $desc, $ref = '', $error = '' ) {
			$this->write( $step, $status, $desc, $ref, $error );
		};
	}

	public function write( $step, $status, $desc, $ref = '', $error = '' ) {
		global $wpdb;
		$row = array(
			'run_id'       => $this->run_id,
			'created_at'   => gmdate( 'Y-m-d H:i:s' ),
			'user_id'      => $this->user_id,
			'plan_id'      => $this->plan_id,
			'operation'    => $this->operation,
			'step'         => mb_substr( self::redact( (string) $step ), 0, 190 ),
			'gateway'      => $this->gateway,
			'status'       => $status,
			'description'  => self::redact( (string) $desc ),
			'ref'          => mb_substr( self::redact( (string) $ref ), 0, 190 ),
			'error_detail' => self::redact( (string) $error ),
			'initiated_by' => $this->initiator,
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( Installer::table( 'logs' ), $row );
	}

	/**
	 * Record an administrator change with before/after values (immutable).
	 */
	public function change( $field, $old, $new, $action ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			Installer::table( 'changes' ),
			array(
				'run_id'     => $this->run_id,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
				'user_id'    => $this->user_id,
				'plan_id'    => $this->plan_id,
				'field_key'  => (string) $field,
				'old_value'  => self::redact( (string) $old ),
				'new_value'  => self::redact( (string) $new ),
				'action'     => (string) $action,
				'admin_id'   => $this->initiator,
			)
		);
		$this->write( 'Change recorded', Status::STEP_COMPLETED, $action . ' ' . $field . ': "' . $old . '" → "' . $new . '".', (string) $field );
	}

	/**
	 * Timeline for a member (newest first).
	 *
	 * @return array[]
	 */
	public static function timeline( $user_id, $plan_id = 0, $limit = 300 ) {
		global $wpdb;
		$t = Installer::table( 'logs' );
		if ( $plan_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE user_id = %d AND plan_id = %d ORDER BY id DESC LIMIT %d", $user_id, $plan_id, $limit ), ARRAY_A );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE user_id = %d ORDER BY id DESC LIMIT %d", $user_id, $limit ), ARRAY_A );
	}

	/** Administrator change audit for a member (newest first). @return array[] */
	public static function changes( $user_id, $limit = 100 ) {
		global $wpdb;
		$t = Installer::table( 'changes' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE user_id = %d ORDER BY id DESC LIMIT %d", $user_id, $limit ), ARRAY_A );
	}

	/**
	 * Strip anything that looks like a credential (Stripe keys, bearer tokens, PayPal NVP secrets, card numbers).
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function redact( $text ) {
		$text = (string) $text;
		$text = preg_replace( '/\b(?:sk|rk|pk)_(?:live|test)_[A-Za-z0-9]+/', '[redacted-key]', $text );
		$text = preg_replace( '/Bearer\s+[A-Za-z0-9._\-]+/i', 'Bearer [redacted]', $text );
		$text = preg_replace( '/\b(PWD|SIGNATURE|USER|password|secret|token)=([^&\s]+)/i', '$1=[redacted]', $text );
		$text = preg_replace( '/\b(?:\d[ -]?){13,19}\b/', '[redacted-number]', $text );
		return $text;
	}
}
