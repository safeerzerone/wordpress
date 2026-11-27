<?php
/**
 * Read-only member data retrieval.
 *
 * Members = WordPress users that have a non-empty `arm_user_plan_ids` user meta (the ARMember
 * membership marker). Each row of the audit is one member + one plan.
 *
 * Performance model:
 *  - No derived filter: SQL pagination over users, then ONE batched meta-cache load and ONE
 *    batched payment query for just that page.
 *  - Derived filter (gateway / payment mode / audit status): these values are computed, not
 *    stored, so users are scanned in batches of 200 (bounded by MAX_SCAN) until the page is filled.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Member_Repository {

	const BATCH    = 200;
	const MAX_SCAN = 5000;

	/** @var Payment_History */
	private $payments;
	/** @var Subscription_Audit */
	private $audit;
	/** @var array|null */
	private $plans = null;
	/** @var bool Whether the last query hit MAX_SCAN. */
	public $truncated = false;

	public function __construct( Payment_History $payments, Subscription_Audit $audit ) {
		$this->payments = $payments;
		$this->audit    = $audit;
	}

	public function payments() {
		return $this->payments;
	}

	/**
	 * List audit rows for the admin listing.
	 *
	 * @param array $args search, gateway, mode, status, orderby (id|last_payment), order, page, per_page.
	 * @return array rows[], total (int), total_pages (int)
	 */
	public function list_rows( array $args ) {
		$per_page = max( 1, min( 100, (int) $args['per_page'] ) );
		$page     = max( 1, (int) $args['page'] );
		$offset   = ( $page - 1 ) * $per_page;
		// Phase 2 filters (optional keys): gw_ready, arm_ready, sync (overall/effective sync status).
		$args    += array( 'gw_ready' => '', 'arm_ready' => '', 'sync' => '' );
		$derived  = '' !== $args['gateway'] || '' !== $args['mode'] || '' !== $args['status'] || '' !== $args['gw_ready'] || '' !== $args['arm_ready'] || '' !== $args['sync'];

		$this->truncated = false;

		if ( ! $derived ) {
			$total = $this->count_users( $args['search'] );
			$users = $this->query_users( $args, $offset, $per_page );
			return array(
				'rows'        => $this->build_rows( $users ),
				'total'       => $total,
				'total_pages' => (int) ceil( $total / $per_page ),
			);
		}

		$matches = array();
		$scanned = 0;
		$cursor  = 0;
		while ( $scanned < self::MAX_SCAN ) {
			$users = $this->query_users( $args, $cursor, self::BATCH );
			if ( ! $users ) {
				break;
			}
			$cursor  += self::BATCH;
			$scanned += count( $users );
			foreach ( $this->build_rows( $users ) as $row ) {
				if ( '' !== $args['gateway'] && $this->gateway_bucket( $row['audit']['gateway'] ) !== $args['gateway'] ) {
					continue;
				}
				if ( '' !== $args['mode'] && $this->mode_bucket( $row['audit']['mode'] ) !== $args['mode'] ) {
					continue;
				}
				if ( '' !== $args['status'] && $row['audit']['status'] !== $args['status'] ) {
					continue;
				}
				if ( '' !== $args['gw_ready'] && $row['p2']['gateway_status'] !== $args['gw_ready'] ) {
					continue;
				}
				if ( '' !== $args['arm_ready'] && $row['p2']['arm_status'] !== $args['arm_ready'] ) {
					continue;
				}
				if ( '' !== $args['sync'] && $row['p2']['effective'] !== $args['sync'] ) {
					continue;
				}
				$matches[] = $row;
			}
			if ( count( $users ) < self::BATCH ) {
				break;
			}
		}
		if ( $scanned >= self::MAX_SCAN ) {
			$this->truncated = true;
		}
		$total = count( $matches );
		return array(
			'rows'        => array_slice( $matches, $offset, $per_page ),
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
		);
	}

	/** Shared derived-filter test (Phase 1 + Phase 2 filters). */
	private function row_matches( array $row, array $args ) {
		if ( '' !== $args['gateway'] && $this->gateway_bucket( $row['audit']['gateway'] ) !== $args['gateway'] ) {
			return false;
		}
		if ( '' !== $args['mode'] && $this->mode_bucket( $row['audit']['mode'] ) !== $args['mode'] ) {
			return false;
		}
		if ( '' !== $args['status'] && $row['audit']['status'] !== $args['status'] ) {
			return false;
		}
		if ( '' !== $args['gw_ready'] && $row['p2']['gateway_status'] !== $args['gw_ready'] ) {
			return false;
		}
		if ( '' !== $args['arm_ready'] && $row['p2']['arm_status'] !== $args['arm_ready'] ) {
			return false;
		}
		if ( '' !== $args['sync'] && $row['p2']['effective'] !== $args['sync'] ) {
			return false;
		}
		return true;
	}

	/**
	 * Every (user, plan) matching the filters, for "all filtered members" bulk actions / exports.
	 * Bounded by MAX_SCAN users and $cap rows.
	 *
	 * @return array[] rows (full audit rows)
	 */
	public function matching_rows( array $args, $cap = 2000 ) {
		$args    += array( 'gw_ready' => '', 'arm_ready' => '', 'sync' => '', 'gateway' => '', 'mode' => '', 'status' => '', 'search' => '', 'orderby' => 'id', 'order' => 'asc' );
		$out     = array();
		$scanned = 0;
		$cursor  = 0;
		while ( $scanned < self::MAX_SCAN && count( $out ) < $cap ) {
			$users = $this->query_users( $args, $cursor, self::BATCH );
			if ( ! $users ) {
				break;
			}
			$cursor  += self::BATCH;
			$scanned += count( $users );
			foreach ( $this->build_rows( $users ) as $row ) {
				if ( $this->row_matches( $row, $args ) && count( $out ) < $cap ) {
					$out[] = $row;
				}
			}
			if ( count( $users ) < self::BATCH ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Rows (one per plan) for a single member, for the details page.
	 *
	 * @param int $user_id User ID.
	 * @return array rows[] (empty when the user is not an ARMember member)
	 */
	public function rows_for_user( $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT ID, display_name, user_email, user_registered, user_login FROM {$wpdb->users} WHERE ID = %d", $user_id ) );
		if ( ! $user ) {
			return array();
		}
		return $this->build_rows( array( $user ) );
	}

	/** Profile (custom field) usermeta keys that hold the member's payment method, in priority order. */
	const PROFILE_GATEWAY_KEYS = array( 'selected_payment_method', 'payment_method_for_cms', 'order_payment_method' );

	/** Recognised gateway key (stripe|woocommerce|paypal|gocardless) from profile fields, or ''. */
	public function profile_gateway( $user_id ) {
		foreach ( self::PROFILE_GATEWAY_KEYS as $k ) {
			$v = get_user_meta( $user_id, $k, true );
			if ( ! is_scalar( $v ) ) {
				continue;
			}
			$key = Subscription_Audit::gateway_key( $v );
			if ( '' !== $key && 'other' !== $key ) {
				return $key;
			}
		}
		return '';
	}

	/** Gateway filter bucket: stripe | woocommerce | paypal | gocardless | other | none. */
	private function gateway_bucket( $gateway ) {
		$key = \ARMSA\Subscription_Audit::gateway_key( $gateway );
		return '' === $key ? 'none' : $key;
	}

	/** Mode filter bucket: auto_debit | manual | other | none. */
	private function mode_bucket( $mode ) {
		if ( '' === $mode ) {
			return 'none';
		}
		if ( 'auto_debit_subscription' === $mode ) {
			return 'auto_debit';
		}
		return 'manual_subscription' === $mode ? 'manual' : 'other';
	}

	// ---------------------------------------------------------------------------------------
	// SQL
	// ---------------------------------------------------------------------------------------

	private function base_where( $search, array &$params ) {
		global $wpdb;
		$where    = " WHERE pm.meta_key = %s AND pm.meta_value <> '' AND pm.meta_value <> 'a:0:{}'";
		$params[] = 'arm_user_plan_ids';
		$search   = trim( (string) $search );
		if ( '' !== $search ) {
			$like  = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= ' AND (u.display_name LIKE %s OR u.user_email LIKE %s OR u.user_login LIKE %s';
			array_push( $params, $like, $like, $like );
			if ( ctype_digit( $search ) ) {
				$where   .= ' OR u.ID = %d';
				$params[] = (int) $search;
			}
			$where .= ')';
		}
		return $where;
	}

	private function count_users( $search ) {
		global $wpdb;
		$params = array();
		$where  = $this->base_where( $search, $params );
		$sql    = "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} pm ON pm.user_id = u.ID" . $where;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
	}

	private function query_users( array $args, $offset, $limit ) {
		global $wpdb;
		$params = array();
		$where  = $this->base_where( $args['search'], $params );
		$order  = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$join   = '';
		$sort   = "u.ID {$order}";

		if ( 'last_payment' === $args['orderby'] && $this->payments->is_available() && $this->payments->has_column( 'arm_payment_date' ) ) {
			$log  = $this->payments->table();
			$join = " LEFT JOIN (SELECT arm_user_id, MAX(arm_payment_date) AS last_pay FROM `{$log}` GROUP BY arm_user_id) lp ON lp.arm_user_id = u.ID";
			$sort = "lp.last_pay {$order}, u.ID DESC";
		}

		$sql      = "SELECT u.ID, u.display_name, u.user_email, u.user_registered, u.user_login FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} pm ON pm.user_id = u.ID{$join}{$where} ORDER BY {$sort} LIMIT %d OFFSET %d";
		$params[] = (int) $limit;
		$params[] = (int) $offset;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$users = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		if ( ! is_array( $users ) ) {
			Plugin::log( 'Member query failed: ' . $wpdb->last_error );
			return array();
		}
		$seen = array();
		foreach ( $users as $i => $u ) {
			if ( isset( $seen[ $u->ID ] ) ) {
				unset( $users[ $i ] );
			}
			$seen[ $u->ID ] = true;
		}
		return array_values( $users );
	}

	/** Phase 2 columns derived from the stored assessment row (never from live gateway calls). */
	private function phase2_state( $row ) {
		$missing = array();
		if ( $row && ! empty( $row['missing'] ) ) {
			$m       = json_decode( $row['missing'], true );
			$missing = is_array( $m ) ? $m : array();
		}
		$overall = $row ? $row['overall_status'] : Status::OV_NOT_ASSESSED;
		$sync    = $row ? $row['sync_status'] : Status::SYNC_NONE;
		return array(
			'has'            => (bool) $row,
			'gateway_status' => $row ? $row['gateway_status'] : Status::GW_NA,
			'arm_status'     => $row ? $row['arm_status'] : Status::ARM_NA,
			'overall'        => $overall,
			'sync'           => $sync,
			'effective'      => Status::effective_overall( $overall, $sync ),
			'missing'        => $missing,
			'assessed_at'    => $row ? $row['assessed_at'] : '',
			'synced_at'      => $row ? $row['synced_at'] : '',
		);
	}

	/** Plan definitions (small table), loaded once per request. Own columns only. */
	private function plans() {
		global $wpdb;
		if ( null !== $this->plans ) {
			return $this->plans;
		}
		$this->plans = array();
		$table       = $wpdb->prefix . 'arm_subscription_plans';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			Plugin::log( 'Plans table not found: ' . $table );
			return $this->plans;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT arm_subscription_plan_id, arm_subscription_plan_name, arm_subscription_plan_type, arm_subscription_plan_options FROM `{$table}`", ARRAY_A );
		foreach ( (array) $rows as $r ) {
			$opts = maybe_unserialize( $r['arm_subscription_plan_options'] );
			$opts = is_array( $opts ) ? $opts : array();
			$type = strtolower( (string) $r['arm_subscription_plan_type'] );
			$kind = 'unknown';
			if ( 'recurring' === $type || ( isset( $opts['payment_type'] ) && 'subscription' === $opts['payment_type'] ) ) {
				$kind = 'recurring';
			} elseif ( 'free' === $type ) {
				$kind = 'free';
			} elseif ( 'paid_infinite' === $type || ( isset( $opts['access_type'] ) && 'lifetime' === $opts['access_type'] ) ) {
				$kind = 'lifetime';
			} elseif ( 'paid_finite' === $type ) {
				$kind = 'one_time';
			}
			$this->plans[ (int) $r['arm_subscription_plan_id'] ] = array(
				'name' => (string) $r['arm_subscription_plan_name'],
				'kind' => $kind,
			);
		}
		return $this->plans;
	}

	// ---------------------------------------------------------------------------------------
	// Row assembly
	// ---------------------------------------------------------------------------------------

	/**
	 * @param object[] $users Rows with ID, display_name, user_email, user_registered, user_login.
	 * @return array[]
	 */
	private function build_rows( array $users ) {
		if ( ! $users ) {
			return array();
		}
		$ids = array_map( 'intval', wp_list_pluck( $users, 'ID' ) );
		update_meta_cache( 'user', $ids ); // One query for every meta row of these users.
		$plans    = $this->plans();
		$payments = $this->payments->summaries_for_users( $ids );
		$rows     = array();
		$stored   = array();
		if ( class_exists( __NAMESPACE__ . '\\Sync_Service' ) ) {
			$pairs = array();
			foreach ( $ids as $i ) {
				$pairs[] = array( $i, 0 );
			}
			$stored = Sync_Service::stored_many( $pairs ); // One query for the whole page (no gateway calls).
		}

		foreach ( $users as $u ) {
			$uid      = (int) $u->ID;
			$plan_ids = get_user_meta( $uid, 'arm_user_plan_ids', true );
			$plan_ids = is_array( $plan_ids ) ? $plan_ids : array_filter( explode( ',', (string) $plan_ids ) );
			$plan_ids = array_values( array_unique( array_filter( array_map( 'absint', $plan_ids ) ) ) );
			$profile_gateway = $this->profile_gateway( $uid );

			if ( ! $plan_ids ) {
				$plan_ids = array( 0 );
			}
			foreach ( $plan_ids as $pid ) {
				$raw      = $pid ? get_user_meta( $uid, 'arm_user_plan_' . $pid, true ) : '';
				$has_meta = is_array( $raw ) && ! empty( $raw );
				$raw      = $has_meta ? $raw : array();
				$stripe   = isset( $raw['arm_stripe'] ) && is_array( $raw['arm_stripe'] ) ? $raw['arm_stripe'] : array();

				$meta = array(
					'gateway'   => isset( $raw['arm_user_gateway'] ) ? (string) $raw['arm_user_gateway'] : '',
					'profile_gateway' => $profile_gateway,
					'selected_payment_method' => is_scalar( $spm = get_user_meta( $uid, 'selected_payment_method', true ) ) ? trim( (string) $spm ) : '',
					'mode'      => isset( $raw['arm_payment_mode'] ) ? (string) $raw['arm_payment_mode'] : '',
					'cycle'     => isset( $raw['arm_payment_cycle'] ) ? $raw['arm_payment_cycle'] : '',
					'completed' => isset( $raw['arm_completed_recurring'] ) ? $raw['arm_completed_recurring'] : '',
					'next_due'  => isset( $raw['arm_next_due_payment'] ) ? $raw['arm_next_due_payment'] : '',
					'subscr_id' => isset( $raw['arm_subscr_id'] ) && is_scalar( $raw['arm_subscr_id'] ) ? (string) $raw['arm_subscr_id'] : '',
					'stripe'    => $stripe,
					'cancelled' => isset( $raw['arm_cencelled_plan'] ) ? $raw['arm_cencelled_plan'] : '',
				);
				$pay  = isset( $payments[ $uid ][ $pid ] ) ? $payments[ $uid ][ $pid ] : array( 'count' => 0, 'latest' => null, 'sub_refs' => array(), 'order_refs' => array(), 'gateways' => array(), 'modes' => array() );
				$plan = isset( $plans[ $pid ] ) ? $plans[ $pid ] : null;
				$kind = $plan ? $plan['kind'] : 'unknown';

				$audit = $this->audit->analyze(
					array(
						'plan_id'       => $pid,
						'plan_kind'     => $kind,
						'has_plan_meta' => $has_meta,
						'meta'          => $meta,
						'pay'           => $pay,
					)
				);

				$rows[] = array(
					'user'      => array(
						'id'         => $uid,
						'name'       => (string) $u->display_name,
						'email'      => (string) $u->user_email,
						'login'      => isset( $u->user_login ) ? (string) $u->user_login : '',
						'registered' => (string) $u->user_registered,
					),
					'plan_id'   => $pid,
					'plan_name' => $plan ? $plan['name'] : ( $pid ? sprintf( 'Unknown plan #%d', $pid ) : 'No plan' ),
					'plan_kind' => $kind,
					'meta'      => $meta,
					'pay'       => $pay,
					'audit'     => $audit,
					'p2'        => $this->phase2_state( isset( $stored[ $uid . ':' . $pid ] ) ? $stored[ $uid . ':' . $pid ] : null ),
				);
			}
		}
		return $rows;
	}
}
