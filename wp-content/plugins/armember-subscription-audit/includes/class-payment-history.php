<?php
/**
 * Read-only access to the ARMember payment log (<prefix>arm_payment_log).
 *
 * Column existence is verified against the live schema; missing columns are skipped.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Payment_History {

	const STATUS_SUCCESS   = 'success';
	const STATUS_FAILED    = 'failed';
	const STATUS_PENDING   = 'pending';
	const STATUS_CANCELLED = 'cancelled';
	const STATUS_UNKNOWN   = 'unknown';
	const STATUS_NONE      = 'none';

	/** @var string[]|null Existing columns of the log table (null = not loaded, empty = table missing). */
	private $columns = null;

	public function table() {
		global $wpdb;
		return $wpdb->prefix . 'arm_payment_log';
	}

	/**
	 * Columns that really exist in the live table.
	 *
	 * @return string[]
	 */
	public function columns() {
		global $wpdb;
		if ( null === $this->columns ) {
			$this->columns = array();
			$table         = $this->table();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
			if ( $exists === $table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`", 0 );
				if ( is_array( $cols ) ) {
					$this->columns = $cols;
				}
			} else {
				Plugin::log( 'Payment log table not found: ' . $table );
			}
		}
		return $this->columns;
	}

	public function is_available() {
		$cols = $this->columns();
		return in_array( 'arm_user_id', $cols, true ) && in_array( 'arm_plan_id', $cols, true );
	}

	public function has_column( $column ) {
		return in_array( $column, $this->columns(), true );
	}

	/**
	 * Build a backtick-quoted SELECT list from wanted columns that exist.
	 *
	 * @param string[] $wanted Column names (code constants, never user input).
	 * @return string
	 */
	private function select_list( array $wanted ) {
		$have = array_values( array_intersect( $wanted, $this->columns() ) );
		return $have ? '`' . implode( '`, `', $have ) . '`' : '`arm_user_id`';
	}

	/**
	 * Per user + plan summary for a batch of users (one query, compact columns).
	 *
	 * @param int[] $user_ids User IDs.
	 * @return array<int,array<int,array>> [user_id][plan_id] => summary
	 */
	public function summaries_for_users( array $user_ids ) {
		global $wpdb;
		$user_ids = array_values( array_filter( array_map( 'absint', $user_ids ) ) );
		if ( ! $user_ids || ! $this->is_available() ) {
			return array();
		}
		$cols         = $this->select_list(
			array( 'arm_log_id', 'arm_user_id', 'arm_plan_id', 'arm_payment_gateway', 'arm_payment_type', 'arm_payment_mode', 'arm_token', 'arm_transaction_id', 'arm_transaction_status', 'arm_amount', 'arm_currency', 'arm_payment_date', 'arm_created_date' )
		);
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$order        = $this->has_column( 'arm_log_id' ) ? 'ORDER BY `arm_log_id` DESC' : '';
		$table        = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$cols} FROM `{$table}` WHERE `arm_user_id` IN ({$placeholders}) {$order}", $user_ids ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			Plugin::log( 'Payment summary query failed: ' . $wpdb->last_error );
			return array();
		}

		// Newest first by effective payment date, then log id.
		usort(
			$rows,
			function ( $a, $b ) {
				$da = self::effective_date( $a );
				$db = self::effective_date( $b );
				if ( $da === $db ) {
					return (int) ( isset( $b['arm_log_id'] ) ? $b['arm_log_id'] : 0 ) - (int) ( isset( $a['arm_log_id'] ) ? $a['arm_log_id'] : 0 );
				}
				return strcmp( $db, $da );
			}
		);

		$out = array();
		foreach ( $rows as $row ) {
			$uid = (int) $row['arm_user_id'];
			$pid = isset( $row['arm_plan_id'] ) ? (int) $row['arm_plan_id'] : 0;
			if ( ! isset( $out[ $uid ][ $pid ] ) ) {
				$out[ $uid ][ $pid ] = array(
					'count'      => 0,
					'latest'     => $row, // First seen = newest.
					'sub_refs'   => array(),
					'order_refs' => array(),
					'gateways'   => array(),
					'modes'      => array(),
				);
			}
			$s = &$out[ $uid ][ $pid ];
			++$s['count'];
			foreach ( array( 'arm_token', 'arm_transaction_id' ) as $field ) {
				$val = isset( $row[ $field ] ) ? trim( (string) $row[ $field ] ) : '';
				if ( '' === $val ) {
					continue;
				}
				if ( self::is_subscription_reference( $val ) ) {
					$s['sub_refs'][ $val ] = true;
				} elseif ( 'arm_transaction_id' === $field ) {
					$s['order_refs'][ $val ] = true;
				}
			}
			foreach ( array( 'arm_payment_gateway' => 'gateways', 'arm_payment_mode' => 'modes' ) as $field => $bucket ) {
				$val = isset( $row[ $field ] ) ? strtolower( trim( (string) $row[ $field ] ) ) : '';
				if ( '' !== $val ) {
					$s[ $bucket ][ $val ] = true;
				}
			}
			unset( $s );
		}
		foreach ( $out as $uid => $plans ) {
			foreach ( $plans as $pid => $s ) {
				$out[ $uid ][ $pid ]['sub_refs']   = array_keys( $s['sub_refs'] );
				$out[ $uid ][ $pid ]['order_refs'] = array_keys( $s['order_refs'] );
				$out[ $uid ][ $pid ]['gateways']   = array_keys( $s['gateways'] );
				$out[ $uid ][ $pid ]['modes']      = array_keys( $s['modes'] );
			}
		}
		return $out;
	}

	/**
	 * Full payment history of one member, newest first (for the details page).
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   Max rows.
	 * @return array[]
	 */
	public function history_for_user( $user_id, $limit = 500 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		if ( ! $user_id || ! $this->is_available() ) {
			return array();
		}
		$cols  = $this->select_list(
			array( 'arm_log_id', 'arm_user_id', 'arm_plan_id', 'arm_payment_gateway', 'arm_payment_type', 'arm_payment_mode', 'arm_transaction_id', 'arm_token', 'arm_transaction_status', 'arm_amount', 'arm_currency', 'arm_payment_date', 'arm_created_date' )
		);
		$order = $this->has_column( 'arm_log_id' ) ? 'ORDER BY `arm_log_id` DESC' : '';
		$table = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$cols} FROM `{$table}` WHERE `arm_user_id` = %d {$order} LIMIT %d", $user_id, max( 1, (int) $limit ) ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			Plugin::log( 'History query failed: ' . $wpdb->last_error );
			return array();
		}
		usort(
			$rows,
			function ( $a, $b ) {
				$cmp = strcmp( self::effective_date( $b ), self::effective_date( $a ) );
				return 0 !== $cmp ? $cmp : (int) ( isset( $b['arm_log_id'] ) ? $b['arm_log_id'] : 0 ) - (int) ( isset( $a['arm_log_id'] ) ? $a['arm_log_id'] : 0 );
			}
		);
		return $rows;
	}

	/**
	 * Phase 2: identifier-bearing columns of one member's log rows (read-only, for discovery).
	 *
	 * @param int $user_id User.
	 * @param int $plan_id Plan (0 = all).
	 * @return array[] rows with arm_log_id, arm_plan_id, arm_token, arm_transaction_id, arm_payment_gateway, arm_payer_email
	 */
	public function refs_for_user( $user_id, $plan_id = 0 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		if ( ! $user_id || ! $this->is_available() ) {
			return array();
		}
		$cols  = $this->select_list( array( 'arm_log_id', 'arm_plan_id', 'arm_token', 'arm_transaction_id', 'arm_payment_gateway', 'arm_payer_email' ) );
		$table = $this->table();
		$sql   = "SELECT {$cols} FROM `{$table}` WHERE `arm_user_id` = %d";
		$args  = array( $user_id );
		if ( $plan_id ) {
			$sql   .= ' AND `arm_plan_id` = %d';
			$args[] = absint( $plan_id );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql . ' ORDER BY `arm_log_id` DESC LIMIT 200', $args ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Other members whose payment log carries the same identifier (duplicate-reference detection).
	 *
	 * @param string[] $values  Identifiers.
	 * @param int      $user_id Member to exclude.
	 * @return array<string,int[]> value => other user ids
	 */
	public function other_users_with_refs( array $values, $user_id ) {
		global $wpdb;
		$values = array_values( array_unique( array_filter( array_map( 'strval', $values ) ) ) );
		if ( ! $values || ! $this->is_available() || ! $this->has_column( 'arm_token' ) ) {
			return array();
		}
		$table = $this->table();
		$ph    = implode( ',', array_fill( 0, count( $values ), '%s' ) );
		$tcol  = $this->has_column( 'arm_transaction_id' ) ? "OR `arm_transaction_id` IN ({$ph})" : '';
		$args  = array_merge( $values, $this->has_column( 'arm_transaction_id' ) ? $values : array(), array( absint( $user_id ) ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT arm_user_id, arm_token" . ( $tcol ? ', arm_transaction_id' : '' ) . " FROM `{$table}` WHERE ( `arm_token` IN ({$ph}) {$tcol} ) AND `arm_user_id` <> %d AND `arm_user_id` > 0 LIMIT 200", $args ), ARRAY_A );
		$out  = array();
		foreach ( (array) $rows as $r ) {
			foreach ( array( 'arm_token', 'arm_transaction_id' ) as $c ) {
				if ( isset( $r[ $c ] ) && in_array( $r[ $c ], $values, true ) ) {
					$out[ $r[ $c ] ][] = (int) $r['arm_user_id'];
				}
			}
		}
		return array_map( 'array_unique', $out );
	}

	/**
	 * Payment date, falling back to created date when the date is empty / epoch default.
	 *
	 * @param array $row Log row.
	 * @return string MySQL datetime or ''.
	 */
	public static function effective_date( array $row ) {
		foreach ( array( 'arm_payment_date', 'arm_created_date' ) as $f ) {
			if ( ! empty( $row[ $f ] ) && 0 !== strpos( $row[ $f ], '1970-01-01' ) && 0 !== strpos( $row[ $f ], '0000-00-00' ) ) {
				return (string) $row[ $f ];
			}
		}
		return '';
	}

	/**
	 * Map a raw ARMember/gateway status to a normalised bucket.
	 *
	 * Observed in ARMember source: 'success', 'completed', 'pending', 'canceled', 'failed',
	 * numeric 0/1/2 (bank transfer: 0 pending, 1 approved, 2 rejected) and raw Stripe/PayPal/
	 * Authorize.net strings passed through from gateway responses.
	 *
	 * @param mixed $raw Raw value.
	 * @return string One of the STATUS_* constants.
	 */
	public static function normalize_status( $raw ) {
		$s = strtolower( trim( (string) $raw ) );
		if ( '' === $s ) {
			return self::STATUS_UNKNOWN;
		}
		if ( in_array( $s, array( '1', 'success', 'succeeded', 'completed', 'complete', 'paid', 'active', 'approved', 'processed', 'captured' ), true ) ) {
			return self::STATUS_SUCCESS;
		}
		if ( in_array( $s, array( '2', 'failed', 'failure', 'fail', 'declined', 'denied', 'rejected', 'error', 'unpaid', 'past_due', 'uncollectible', 'incomplete_expired', 'requires_payment_method' ), true ) ) {
			return self::STATUS_FAILED;
		}
		if ( in_array( $s, array( '0', 'pending', 'processing', 'open', 'incomplete', 'on-hold', 'on_hold', 'requires_action' ), true ) ) {
			return self::STATUS_PENDING;
		}
		if ( in_array( $s, array( 'canceled', 'cancelled', 'void', 'voided', 'refunded', 'expired' ), true ) ) {
			return self::STATUS_CANCELLED;
		}
		return self::STATUS_UNKNOWN;
	}

	/**
	 * Does the value look like an external subscription id (Stripe sub_..., PayPal I-...)?
	 *
	 * @param string $value Candidate.
	 * @return bool
	 */
	public static function is_subscription_reference( $value ) {
		return (bool) preg_match( '/^(sub_|I-[A-Z0-9]+$)/', (string) $value );
	}
}
