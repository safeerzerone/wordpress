<?php
/**
 * Every-10-minutes cron: WooCommerce + Stripe direct-debit members past due (10-minute grace).
 *
 * Looks up Stripe for a paid invoice/charge on the due date with the expected amount (and hints from
 * past payment_log rows). On match only: inserts ARMember success payment_log and updates plan meta so
 * ARMember's own failed-payment cron does not run. Never inserts failed rows or repairs failed logs.
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** WP-Cron hook name. */
define( 'ARSENAL_SETTINGS_WC_DD_OVERDUE_CRON_HOOK', 'arsenal_settings_wc_dd_overdue_reconcile' );

/** User-scan offset option (paginated batch through members). */
define( 'ARSENAL_SETTINGS_WC_DD_OVERDUE_CRON_OFFSET_OPTION', 'arsenal_settings_wc_dd_10min_cron_offset' );

/** Grace after arm_next_due_payment before this cron checks Stripe (seconds). */
define( 'ARSENAL_SETTINGS_WC_DD_OVERDUE_GRACE_SECONDS', 10 * MINUTE_IN_SECONDS );

/**
 * Register a 10-minute cron schedule.
 *
 * @param array $schedules WP cron schedules.
 * @return array
 */
function arsenal_settings_wc_dd_overdue_cron_schedules( $schedules ) {
	if ( ! isset( $schedules['every_ten_minutes'] ) ) {
		$schedules['every_ten_minutes'] = array(
			'interval' => 10 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 10 Minutes', 'arsenal-settings' ),
		);
	}

	return $schedules;
}
add_filter( 'cron_schedules', 'arsenal_settings_wc_dd_overdue_cron_schedules', 20 );

/**
 * Schedule the WooCommerce direct-debit due-payment reconcile cron.
 */
function arsenal_settings_schedule_wc_dd_overdue_cron() {
	$hook = ARSENAL_SETTINGS_WC_DD_OVERDUE_CRON_HOOK;

	if ( wp_get_schedule( $hook ) && 'every_ten_minutes' !== wp_get_schedule( $hook ) ) {
		wp_clear_scheduled_hook( $hook );
	}

	if ( ! wp_next_scheduled( $hook ) ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'every_ten_minutes', $hook );
	}
}
add_action( 'init', 'arsenal_settings_schedule_wc_dd_overdue_cron' );

/**
 * Append one NDJSON line to the WC DD overdue cron log.
 *
 * @param string $message Event name.
 * @param array  $extra   Context.
 */
function arsenal_settings_wc_dd_overdue_cron_log( $message, array $extra = array() ) {
	if ( ! function_exists( 'arsenal_settings_api_logging_enabled' ) || ! arsenal_settings_api_logging_enabled() ) {
		return;
	}

	if ( ! function_exists( 'arsenal_settings_api_log_ensure_dir' ) ) {
		return;
	}

	$dir = arsenal_settings_api_log_ensure_dir();
	if ( is_wp_error( $dir ) ) {
		return;
	}

	$entry = array(
		'timestamp' => current_time( 'mysql' ),
		'event'     => (string) $message,
		'extra'     => $extra && function_exists( 'arsenal_settings_api_redact_for_log' )
			? arsenal_settings_api_redact_for_log( $extra )
			: ( $extra ? $extra : array() ),
	);
	$file  = trailingslashit( $dir ) . 'wc-dd-overdue-cron-' . gmdate( 'Y-m-d' ) . '.log';
	$line  = wp_json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $line ) {
		return;
	}

	file_put_contents( $file, $line . "\n", FILE_APPEND | LOCK_EX );

	do_action( 'arsenal_settings_wc_dd_overdue_cron_logged', $message, $extra );
}

/**
 * Plan is ready to check Stripe: due timestamp + 10 minutes has passed (not ARMember's 24h rule).
 *
 * @param array $plan_data arm_user_plan_{id} meta.
 * @return bool
 */
function arsenal_settings_wc_dd_overdue_cron_due_ready( array $plan_data ) {
	$next_due = isset( $plan_data['arm_next_due_payment'] ) ? (int) $plan_data['arm_next_due_payment'] : 0;
	if ( $next_due < 1 ) {
		return false;
	}

	$grace = (int) apply_filters( 'arsenal_settings_wc_dd_overdue_grace_seconds', ARSENAL_SETTINGS_WC_DD_OVERDUE_GRACE_SECONDS );

	return ( $next_due + max( 0, $grace ) ) <= (int) current_time( 'timestamp' );
}

/**
 * WooCommerce gateway recurring plan (any payment mode).
 *
 * @param array $plan_data Plan meta.
 * @return bool
 */
function arsenal_settings_wc_dd_overdue_cron_is_wc_gateway_plan( array $plan_data ) {
	$gateway = isset( $plan_data['arm_user_gateway'] ) ? strtolower( trim( (string) $plan_data['arm_user_gateway'] ) ) : '';

	return 'woocommerce' === $gateway;
}

/**
 * Target plan: active, not suspended, recurring, WooCommerce gateway, Stripe direct debit.
 *
 * @param int   $user_id  WordPress user id.
 * @param int   $plan_id  ARMember plan id.
 * @param array $dd_cache Per-user DD cache.
 * @return bool
 */
function arsenal_settings_wc_dd_overdue_cron_plan_qualifies( $user_id, $plan_id, array &$dd_cache = array() ) {
	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 ) {
		return false;
	}

	if ( ! function_exists( 'arsenal_settings_user_has_active_armember_plan' )
		|| ! arsenal_settings_user_has_active_armember_plan( $user_id, $plan_id ) ) {
		return false;
	}

	$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
	if ( ! is_array( $plan_data ) || empty( $plan_data ) || ! arsenal_settings_wc_dd_overdue_cron_is_wc_gateway_plan( $plan_data ) ) {
		return false;
	}

	if ( class_exists( 'ARM_Plan' ) ) {
		$plan = new ARM_Plan( $plan_id );
		if ( is_object( $plan ) && method_exists( $plan, 'is_recurring' ) && ! $plan->is_recurring() ) {
			return false;
		}
	}

	if ( ! array_key_exists( $user_id, $dd_cache ) ) {
		$dd_cache[ $user_id ] = function_exists( 'arsenal_settings_dd_failure_user_uses_direct_debit' )
			&& arsenal_settings_dd_failure_user_uses_direct_debit( $user_id );
	}

	return ! empty( $dd_cache[ $user_id ] );
}

/**
 * Expected amount/currency for the due payment.
 *
 * @param int   $user_id   User id.
 * @param int   $plan_id   Plan id.
 * @param array $plan_data Plan meta.
 * @return array{amount_minor:int,currency:string,amount_float:float}
 */
function arsenal_settings_wc_dd_overdue_get_due_payment_expectation( $user_id, $plan_id, array $plan_data ) {
	global $wpdb;

	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;

	$currency = ! empty( $plan_data['arm_currency'] ) ? strtolower( (string) $plan_data['arm_currency'] ) : '';
	if ( '' === $currency && function_exists( 'get_woocommerce_currency' ) ) {
		$currency = strtolower( (string) get_woocommerce_currency() );
	}

	$amount_float = 0.0;
	if ( class_exists( 'ARM_Plan' ) ) {
		$plan = new ARM_Plan( $plan_id );
		if ( is_object( $plan ) && isset( $plan->amount ) ) {
			$amount_float = (float) $plan->amount;
		}
	}

	if ( $amount_float <= 0 && function_exists( 'arsenal_settings_get_armember_payment_log_table' ) ) {
		$table = arsenal_settings_get_armember_payment_log_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table from ARMember helper.
		$last = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT `arm_amount`, `arm_currency` FROM `{$table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s ORDER BY `arm_log_id` DESC LIMIT 1",
				$user_id,
				$plan_id,
				'woocommerce',
				'success'
			),
			ARRAY_A
		);
		if ( is_array( $last ) ) {
			if ( $amount_float <= 0 && isset( $last['arm_amount'] ) ) {
				$amount_float = (float) $last['arm_amount'];
			}
			if ( '' === $currency && ! empty( $last['arm_currency'] ) ) {
				$currency = strtolower( (string) $last['arm_currency'] );
			}
		}
	}

	$amount_minor = $amount_float > 0 ? (int) round( $amount_float * 100 ) : 0;

	return array(
		'amount_minor' => $amount_minor,
		'currency'     => $currency,
		'amount_float' => $amount_float,
	);
}

/**
 * Stripe subscription ids and related ids from recent successful payment_log rows.
 *
 * @param int $user_id User id.
 * @param int $plan_id Plan id.
 * @return array{subscription_ids:array<int,string>,invoice_ids:array<int,string>,customer_ids:array<int,string>}
 */
function arsenal_settings_wc_dd_overdue_stripe_hints_from_payment_logs( $user_id, $plan_id ) {
	global $wpdb;

	$hints = array(
		'subscription_ids' => array(),
		'invoice_ids'      => array(),
		'customer_ids'     => array(),
	);

	if ( ! function_exists( 'arsenal_settings_get_armember_payment_log_table' )
		|| ! function_exists( 'arsenal_settings_arm_payment_log_stripe_ids' ) ) {
		return $hints;
	}

	$table = arsenal_settings_get_armember_payment_log_table();
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table from ARMember helper.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM `{$table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s ORDER BY `arm_log_id` DESC LIMIT 10",
			(int) $user_id,
			(int) $plan_id,
			'woocommerce',
			'success'
		),
		ARRAY_A
	);

	if ( ! is_array( $rows ) ) {
		return $hints;
	}

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$ids = arsenal_settings_arm_payment_log_stripe_ids( $row );
		if ( ! empty( $ids['subscription_id'] ) ) {
			$hints['subscription_ids'][] = (string) $ids['subscription_id'];
		}
		if ( ! empty( $ids['invoice_id'] ) ) {
			$hints['invoice_ids'][] = (string) $ids['invoice_id'];
		}
		$extra = function_exists( 'arsenal_settings_arm_payment_log_extra_vars_array' )
			? arsenal_settings_arm_payment_log_extra_vars_array( isset( $row['arm_extra_vars'] ) ? $row['arm_extra_vars'] : '' )
			: array();
		if ( ! empty( $extra['stripe_customer_id'] ) && preg_match( '/^cus_/', (string) $extra['stripe_customer_id'] ) ) {
			$hints['customer_ids'][] = (string) $extra['stripe_customer_id'];
		}
	}

	$hints['subscription_ids'] = array_values( array_unique( array_filter( $hints['subscription_ids'] ) ) );
	$hints['invoice_ids']      = array_values( array_unique( array_filter( $hints['invoice_ids'] ) ) );
	$hints['customer_ids']     = array_values( array_unique( array_filter( $hints['customer_ids'] ) ) );

	return $hints;
}

/**
 * Forward window after due date when searching for a matching Stripe payment (direct debit settlement).
 *
 * @param int $due_ts Due timestamp.
 * @return int Unix timestamp (inclusive upper bound).
 */
function arsenal_settings_wc_dd_overdue_stripe_paid_window_end( $due_ts ) {
	$due_ts = (int) $due_ts;
	$days   = (int) apply_filters( 'arsenal_settings_wc_dd_overdue_stripe_forward_days', 14 );

	return $due_ts + max( 1, $days ) * DAY_IN_SECONDS;
}

/**
 * Paid-at timestamp for a Stripe invoice.
 *
 * @param array $invoice Stripe invoice.
 * @return int
 */
function arsenal_settings_wc_dd_overdue_invoice_paid_at( array $invoice ) {
	$pi = isset( $invoice['payment_intent'] ) && is_array( $invoice['payment_intent'] ) ? $invoice['payment_intent'] : null;

	return function_exists( 'arsenal_settings_stripe_extract_paid_at_timestamp' )
		? (int) arsenal_settings_stripe_extract_paid_at_timestamp( $invoice, $pi )
		: 0;
}

/**
 * Whether a paid Stripe invoice qualifies for the current overdue cycle (amount + paid on/after due).
 *
 * @param array  $invoice       Stripe invoice.
 * @param int    $due_ts        arm_next_due_payment timestamp.
 * @param int    $amount_minor  Expected minor units.
 * @param string $currency      Currency code.
 * @param int    $plan_id       ARMember plan id.
 * @return bool
 */
function arsenal_settings_wc_dd_overdue_invoice_qualifies_for_due_cycle( array $invoice, $due_ts, $amount_minor, $currency, $plan_id ) {
	$status = isset( $invoice['status'] ) ? (string) $invoice['status'] : '';
	$paid   = isset( $invoice['amount_paid'] ) ? (int) $invoice['amount_paid'] : 0;
	if ( 'paid' !== $status && $paid <= 0 ) {
		return false;
	}

	if ( $amount_minor > 0 && $paid > 0 && abs( $paid - $amount_minor ) > 1 ) {
		return false;
	}

	$inv_currency = isset( $invoice['currency'] ) ? strtolower( (string) $invoice['currency'] ) : '';
	if ( '' !== $currency && '' !== $inv_currency && $currency !== $inv_currency ) {
		return false;
	}

	$paid_at = arsenal_settings_wc_dd_overdue_invoice_paid_at( $invoice );
	if ( $paid_at < 1 ) {
		return false;
	}

	$due_ts = (int) $due_ts;
	if ( $paid_at < $due_ts ) {
		return false;
	}

	if ( $paid_at > arsenal_settings_wc_dd_overdue_stripe_paid_window_end( $due_ts ) ) {
		return false;
	}

	$plan_id = (int) $plan_id;
	if ( $plan_id > 0 && ! empty( $invoice['subscription_details']['metadata'] ) && is_array( $invoice['subscription_details']['metadata'] ) ) {
		$meta = $invoice['subscription_details']['metadata'];
		foreach ( array( 'armember_plan_id', 'arm_plan_id', 'plan_id' ) as $key ) {
			if ( isset( $meta[ $key ] ) && (int) $meta[ $key ] !== $plan_id ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Whether this exact Stripe charge / invoice / PI is already recorded in ARMember payment_log.
 *
 * Does not treat subscription id alone as a duplicate (avoids reusing an older cycle's row).
 *
 * @param int   $user_id      User id.
 * @param int   $plan_id      Plan id.
 * @param array $stripe_match Match from Stripe.
 * @return int Existing arm_log_id or 0.
 */
function arsenal_settings_wc_dd_overdue_stripe_transaction_already_logged( $user_id, $plan_id, array $stripe_match ) {
	global $wpdb;

	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 || empty( $stripe_match ) || ! function_exists( 'arsenal_settings_get_armember_payment_log_table' ) ) {
		return 0;
	}

	$ids = array();
	foreach ( array( 'invoice_id', 'latest_charge_id', 'payment_intent_id' ) as $key ) {
		if ( ! empty( $stripe_match[ $key ] ) && is_string( $stripe_match[ $key ] ) ) {
			$ids[] = (string) $stripe_match[ $key ];
		}
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );
	if ( empty( $ids ) ) {
		return 0;
	}

	$table    = arsenal_settings_get_armember_payment_log_table();
	$or_parts = array();
	$params   = array( $user_id, $plan_id, 'woocommerce', 'success' );

	foreach ( $ids as $stripe_id ) {
		$or_parts[] = '`arm_transaction_id` = %s';
		$params[]   = $stripe_id;
		$or_parts[] = '`arm_extra_vars` LIKE %s';
		$params[]   = '%' . $wpdb->esc_like( $stripe_id ) . '%';
	}

	$where_or = implode( ' OR ', $or_parts );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table from ARMember helper.
	$sql = "SELECT `arm_log_id` FROM `{$table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s AND ( {$where_or} ) ORDER BY `arm_log_id` DESC LIMIT 1";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
}

/**
 * Find the newest Stripe paid invoice for this due cycle that is not yet in payment_log.
 *
 * Prefers paid_at on or after arm_next_due_payment (handles stale due dates when a newer charge exists).
 *
 * @param WP_User $user    User.
 * @param int     $plan_id Plan id.
 * @param int     $due_ts  Due timestamp.
 * @param array   $expect  amount_minor, currency.
 * @return array|WP_Error Stripe match array or empty.
 */
function arsenal_settings_wc_dd_overdue_find_stripe_match_for_due_payment( $user, $plan_id, $due_ts, array $expect ) {
	if ( ! $user || empty( $user->user_email ) || ! is_email( $user->user_email ) ) {
		return array();
	}

	$plan_id      = (int) $plan_id;
	$due_ts       = (int) $due_ts;
	$amount_minor = isset( $expect['amount_minor'] ) ? (int) $expect['amount_minor'] : 0;
	$currency     = isset( $expect['currency'] ) ? strtolower( (string) $expect['currency'] ) : '';

	$hints = arsenal_settings_wc_dd_overdue_stripe_hints_from_payment_logs( (int) $user->ID, $plan_id );

	$customer_id = '';
	if ( ! empty( $hints['customer_ids'][0] ) ) {
		$customer_id = (string) $hints['customer_ids'][0];
	}
	if ( '' === $customer_id && function_exists( 'arsenal_settings_stripe_find_customer_id_by_email' ) ) {
		$customer_id = arsenal_settings_stripe_find_customer_id_by_email( (string) $user->user_email );
		if ( is_wp_error( $customer_id ) ) {
			return $customer_id;
		}
		$customer_id = (string) $customer_id;
	}
	if ( '' === $customer_id ) {
		return array();
	}

	$subscription_ids = $hints['subscription_ids'];
	if ( function_exists( 'arsenal_settings_stripe_list_subscriptions_for_customer' ) ) {
		$subs = arsenal_settings_stripe_list_subscriptions_for_customer( $customer_id, true );
		if ( ! is_wp_error( $subs ) && is_array( $subs ) ) {
			foreach ( $subs as $sub_row ) {
				if ( empty( $sub_row['id'] ) ) {
					continue;
				}
				$sid = (string) $sub_row['id'];
				if ( ! in_array( $sid, $subscription_ids, true ) ) {
					$subscription_ids[] = $sid;
				}
			}
		}
	}

	$subscription_ids = array_values( array_unique( array_filter( $subscription_ids ) ) );
	$candidates       = array();

	foreach ( $subscription_ids as $sub_id ) {
		if ( ! preg_match( '/^sub_[a-zA-Z0-9]+$/', $sub_id ) ) {
			continue;
		}

		$full_sub = function_exists( 'arsenal_settings_stripe_get_subscription' )
			? arsenal_settings_stripe_get_subscription( $sub_id, array( 'latest_invoice.payment_intent', 'items.data.price' ) )
			: array();
		if ( is_wp_error( $full_sub ) || ! is_array( $full_sub ) ) {
			continue;
		}

		$metadata = isset( $full_sub['metadata'] ) && is_array( $full_sub['metadata'] ) ? $full_sub['metadata'] : array();
		foreach ( array( 'armember_plan_id', 'arm_plan_id', 'plan_id' ) as $key ) {
			if ( isset( $metadata[ $key ] ) && (int) $metadata[ $key ] !== $plan_id ) {
				continue 2;
			}
		}

		$invoices = function_exists( 'arsenal_settings_stripe_list_invoices_for_subscription' )
			? arsenal_settings_stripe_list_invoices_for_subscription( $sub_id, 36 )
			: array();
		if ( is_wp_error( $invoices ) ) {
			continue;
		}

		foreach ( $invoices as $invoice ) {
			if ( ! is_array( $invoice ) ) {
				continue;
			}
			if ( ! arsenal_settings_wc_dd_overdue_invoice_qualifies_for_due_cycle( $invoice, $due_ts, $amount_minor, $currency, $plan_id ) ) {
				continue;
			}

			$match = function_exists( 'arsenal_settings_stripe_build_match_from_invoice' )
				? arsenal_settings_stripe_build_match_from_invoice( $invoice, $full_sub, $customer_id )
				: array();
			if ( empty( $match ) ) {
				continue;
			}

			$paid_at = isset( $match['paid_at'] ) ? (int) $match['paid_at'] : arsenal_settings_wc_dd_overdue_invoice_paid_at( $invoice );
			$candidates[] = array(
				'paid_at' => $paid_at,
				'match'   => $match,
			);
		}
	}

	if ( empty( $candidates ) ) {
		return array();
	}

	usort(
		$candidates,
		static function ( $a, $b ) {
			return (int) $b['paid_at'] <=> (int) $a['paid_at'];
		}
	);

	foreach ( $candidates as $candidate ) {
		$match = $candidate['match'];
		if ( arsenal_settings_wc_dd_overdue_stripe_transaction_already_logged( (int) $user->ID, $plan_id, $match ) > 0 ) {
			continue;
		}
		return $match;
	}

	return array();
}

/**
 * Insert ARMember payment_log for a specific Stripe transaction (exact-id dedupe only).
 *
 * @param int     $user_id      User id.
 * @param int     $plan_id      Plan id.
 * @param array   $stripe_match Stripe match.
 * @param WP_User $user         User.
 * @return array{arm_log_id:int,inserted:bool,status:string}
 */
function arsenal_settings_wc_dd_overdue_insert_payment_log_from_stripe_match( $user_id, $plan_id, array $stripe_match, $user ) {
	global $wpdb, $arm_payment_gateways;

	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 || empty( $stripe_match ) || ! $user ) {
		return array(
			'arm_log_id' => 0,
			'inserted'   => false,
			'status'     => 'invalid_args',
		);
	}

	$existing = arsenal_settings_wc_dd_overdue_stripe_transaction_already_logged( $user_id, $plan_id, $stripe_match );
	if ( $existing > 0 ) {
		return array(
			'arm_log_id' => $existing,
			'inserted'   => false,
			'status'     => 'already_logged_for_transaction',
		);
	}

	if ( ! function_exists( 'arsenal_settings_get_arm_plan_payment_type' ) ) {
		return array(
			'arm_log_id' => 0,
			'inserted'   => false,
			'status'     => 'helper_missing',
		);
	}

	$plan_type = arsenal_settings_get_arm_plan_payment_type( $plan_id );
	$currency  = ! empty( $stripe_match['currency'] ) ? strtoupper( (string) $stripe_match['currency'] ) : '';
	if ( '' === $currency && function_exists( 'get_woocommerce_currency' ) ) {
		$currency = (string) get_woocommerce_currency();
	}

	$amount = 0.0;
	if ( ! empty( $stripe_match['amount_paid'] ) ) {
		$amount = (float) $stripe_match['amount_paid'] / 100;
	}
	if ( $amount <= 0 && class_exists( 'ARM_Plan' ) ) {
		$plan = new ARM_Plan( $plan_id );
		if ( is_object( $plan ) && isset( $plan->amount ) ) {
			$amount = (float) $plan->amount;
		}
	}

	$payment_datetime = '';
	if ( ! empty( $stripe_match['paid_at'] ) && function_exists( 'arsenal_settings_unix_to_arm_payment_datetime' ) ) {
		$payment_datetime = arsenal_settings_unix_to_arm_payment_datetime( (int) $stripe_match['paid_at'] );
	}
	if ( '' === $payment_datetime ) {
		$payment_datetime = current_time( 'mysql' );
	}

	$transaction_id = '';
	foreach ( array( 'latest_charge_id', 'payment_intent_id', 'invoice_id' ) as $key ) {
		if ( ! empty( $stripe_match[ $key ] ) ) {
			$transaction_id = (string) $stripe_match[ $key ];
			break;
		}
	}

	$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
	$plan_data = is_array( $plan_data ) ? $plan_data : array();
	$pay_mode  = isset( $plan_data['arm_payment_mode'] ) ? (string) $plan_data['arm_payment_mode'] : '';

	$extra_vars = array(
		'arsenal_source'             => 'wc_dd_10min_due_stripe_match',
		'arsenal_synced_at'          => current_time( 'mysql' ),
		'stripe_customer_id'         => isset( $stripe_match['customer_id'] ) ? (string) $stripe_match['customer_id'] : '',
		'stripe_subscription_id'     => isset( $stripe_match['subscription_id'] ) ? (string) $stripe_match['subscription_id'] : '',
		'stripe_subscription_status' => isset( $stripe_match['subscription_status'] ) ? (string) $stripe_match['subscription_status'] : '',
		'stripe_invoice_id'          => isset( $stripe_match['invoice_id'] ) ? (string) $stripe_match['invoice_id'] : '',
		'stripe_invoice_status'      => isset( $stripe_match['invoice_status'] ) ? (string) $stripe_match['invoice_status'] : '',
		'stripe_payment_intent_id'   => isset( $stripe_match['payment_intent_id'] ) ? (string) $stripe_match['payment_intent_id'] : '',
		'stripe_charge_id'           => isset( $stripe_match['latest_charge_id'] ) ? (string) $stripe_match['latest_charge_id'] : '',
	);

	$payment_data = array(
		'arm_user_id'                  => $user_id,
		'arm_first_name'               => isset( $user->first_name ) ? (string) $user->first_name : '',
		'arm_last_name'                => isset( $user->last_name ) ? (string) $user->last_name : '',
		'arm_plan_id'                  => $plan_id,
		'arm_payment_gateway'          => 'woocommerce',
		'arm_payment_type'             => $plan_type,
		'arm_token'                    => isset( $stripe_match['subscription_id'] ) ? (string) $stripe_match['subscription_id'] : '',
		'arm_payer_email'              => isset( $user->user_email ) ? (string) $user->user_email : '',
		'arm_receiver_email'           => '',
		'arm_transaction_id'           => $transaction_id,
		'arm_transaction_payment_type' => $plan_type,
		'arm_transaction_status'       => 'success',
		'arm_payment_mode'             => $pay_mode,
		'arm_payment_date'             => $payment_datetime,
		'arm_amount'                   => $amount,
		'arm_currency'                 => $currency,
		'arm_extra_vars'               => maybe_serialize( $extra_vars ),
		'arm_created_date'             => $payment_datetime,
	);

	$payment_log_id = 0;
	if ( is_object( $arm_payment_gateways ) && method_exists( $arm_payment_gateways, 'arm_save_payment_log' ) ) {
		$payment_log_id = (int) $arm_payment_gateways->arm_save_payment_log( $payment_data );
	}

	if ( $payment_log_id < 1 ) {
		arsenal_settings_wc_dd_overdue_cron_log(
			'payment_log_insert_error',
			array(
				'user_id'  => $user_id,
				'plan_id'  => $plan_id,
				'db_error' => $wpdb->last_error,
				'invoice'  => isset( $stripe_match['invoice_id'] ) ? (string) $stripe_match['invoice_id'] : '',
			)
		);
		return array(
			'arm_log_id' => 0,
			'inserted'   => false,
			'status'     => 'insert_failed',
		);
	}

	return array(
		'arm_log_id' => $payment_log_id,
		'inserted'   => true,
		'status'     => 'inserted',
	);
}

/**
 * Try syncing a paid WC Stripe DD order on or after the due date (newest unlogged order first).
 *
 * @param int $user_id User id.
 * @param int $plan_id Plan id.
 * @param int $due_ts  Due timestamp.
 * @param int $amount_minor Expected amount.
 * @return array{status:string,order_id?:int}
 */
function arsenal_settings_wc_dd_overdue_try_wc_order_sync( $user_id, $plan_id, $due_ts, $amount_minor ) {
	if ( ! function_exists( 'arsenal_settings_find_paid_wc_stripe_dd_orders_for_user_since' )
		|| ! function_exists( 'arsenal_settings_sync_wc_stripe_order_to_arm_payment_log' ) ) {
		return array( 'status' => 'skipped_wc_unavailable' );
	}

	$since_ts   = max( 0, (int) $due_ts );
	$window_end = arsenal_settings_wc_dd_overdue_stripe_paid_window_end( $due_ts );
	$candidates = array();

	foreach ( arsenal_settings_find_paid_wc_stripe_dd_orders_for_user_since( $user_id, $since_ts - DAY_IN_SECONDS ) as $order_id ) {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
		if ( ! $order ) {
			continue;
		}

		$order_plan_ids = function_exists( 'arsenal_settings_get_arm_plan_ids_for_wc_order' )
			? arsenal_settings_get_arm_plan_ids_for_wc_order( $order )
			: array();
		if ( ! in_array( (int) $plan_id, $order_plan_ids, true ) ) {
			continue;
		}

		$paid_ts = $order->get_date_paid();
		$paid_ts = $paid_ts ? $paid_ts->getTimestamp() : ( $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0 );
		if ( $paid_ts < $since_ts || $paid_ts > $window_end ) {
			continue;
		}

		if ( $amount_minor > 0 && method_exists( $order, 'get_total' ) ) {
			$order_minor = (int) round( (float) $order->get_total() * 100 );
			if ( abs( $order_minor - $amount_minor ) > 1 ) {
				continue;
			}
		}

		$candidates[] = array(
			'order_id' => (int) $order_id,
			'paid_ts'  => (int) $paid_ts,
		);
	}

	if ( empty( $candidates ) ) {
		return array( 'status' => 'no_wc_order_match' );
	}

	usort(
		$candidates,
		static function ( $a, $b ) {
			return (int) $b['paid_ts'] <=> (int) $a['paid_ts'];
		}
	);

	foreach ( $candidates as $candidate ) {
		$order_id = (int) $candidate['order_id'];
		$inserted = (int) arsenal_settings_sync_wc_stripe_order_to_arm_payment_log( $order_id, false );
		if ( $inserted > 0 ) {
			return array(
				'status'   => 'reconciled_wc',
				'order_id' => $order_id,
			);
		}
	}

	return array(
		'status'   => 'wc_order_already_logged',
		'order_id' => (int) $candidates[0]['order_id'],
	);
}

/**
 * Apply ARMember plan updates after a confirmed Stripe/WC payment for this due cycle.
 *
 * @param int    $user_id WordPress user id.
 * @param int    $plan_id Plan id.
 * @param string $source  reconciled_wc|reconciled_stripe.
 */
function arsenal_settings_wc_dd_overdue_cron_apply_arm_success( $user_id, $plan_id, $source ) {
	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 ) {
		return;
	}

	if ( 'reconciled_wc' === (string) $source ) {
		return;
	}

	global $arm_subscription_plans;

	$default_plan_data = is_object( $arm_subscription_plans ) && method_exists( $arm_subscription_plans, 'arm_default_plan_array' )
		? $arm_subscription_plans->arm_default_plan_array()
		: array();
	$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
	$plan_data = is_array( $plan_data ) ? $plan_data : array();
	if ( ! empty( $default_plan_data ) ) {
		$plan_data = shortcode_atts( $default_plan_data, $plan_data );
	}
	if ( empty( $plan_data ) ) {
		return;
	}

	$gateway = isset( $plan_data['arm_user_gateway'] ) ? (string) $plan_data['arm_user_gateway'] : 'woocommerce';
	$mode    = isset( $plan_data['arm_payment_mode'] ) ? (string) $plan_data['arm_payment_mode'] : '';

	$plan = class_exists( 'ARM_Plan' ) ? new ARM_Plan( $plan_id ) : null;
	if ( is_object( $plan ) && method_exists( $plan, 'is_recurring' ) && $plan->is_recurring() ) {
		$completed = isset( $plan_data['arm_completed_recurring'] ) ? (int) $plan_data['arm_completed_recurring'] : 0;
		$plan_data['arm_completed_recurring'] = $completed + 1;
	}

	if ( function_exists( 'arsenal_settings_resolve_arm_next_due_payment_timestamp' ) ) {
		$previous_due = isset( $plan_data['arm_next_due_payment'] ) ? (int) $plan_data['arm_next_due_payment'] : 0;
		$next_due     = arsenal_settings_resolve_arm_next_due_payment_timestamp( $user_id, $plan_id, null );
		if ( $next_due > 0 && function_exists( 'arsenal_settings_apply_arm_next_due_payment_to_plan_data' ) ) {
			$plan_data = arsenal_settings_apply_arm_next_due_payment_to_plan_data( $plan_data, $next_due );
			if ( $previous_due !== (int) $plan_data['arm_next_due_payment'] ) {
				arsenal_settings_wc_dd_overdue_cron_log(
					'arm_next_due_payment_updated',
					array(
						'user_id'      => $user_id,
						'plan_id'      => $plan_id,
						'previous_due' => $previous_due,
						'next_due'     => (int) $plan_data['arm_next_due_payment'],
					)
				);
			}
		}
	}

	$plan_data['arm_is_user_in_grace']      = '0';
	$plan_data['arm_grace_period_end']     = '';
	$plan_data['arm_grace_period_action']  = '';

	update_user_meta( $user_id, 'arm_user_plan_' . $plan_id, $plan_data );

	do_action( 'arm_after_recurring_payment_success_outside', $user_id, $plan_id, $gateway, $mode, $plan_data );
}

/**
 * Reconcile one due plan: Stripe match by due date + amount only; never insert failed rows.
 *
 * @param int $user_id User id.
 * @param int $plan_id Plan id.
 * @return array{status:string,arm_log_id?:int,order_id?:int}
 */
function arsenal_settings_wc_dd_overdue_cron_reconcile_plan( $user_id, $plan_id ) {
	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;

	$dd_cache = array();
	if ( ! arsenal_settings_wc_dd_overdue_cron_plan_qualifies( $user_id, $plan_id, $dd_cache ) ) {
		return array( 'status' => 'skipped_not_target_plan' );
	}

	$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
	$plan_data = is_array( $plan_data ) ? $plan_data : array();

	if ( ! arsenal_settings_wc_dd_overdue_cron_due_ready( $plan_data ) ) {
		return array( 'status' => 'skipped_not_due_yet' );
	}

	$due_ts = isset( $plan_data['arm_next_due_payment'] ) ? (int) $plan_data['arm_next_due_payment'] : 0;
	$expect = arsenal_settings_wc_dd_overdue_get_due_payment_expectation( $user_id, $plan_id, $plan_data );

	$wc_result = arsenal_settings_wc_dd_overdue_try_wc_order_sync( $user_id, $plan_id, $due_ts, $expect['amount_minor'] );
	if ( isset( $wc_result['status'] ) && in_array( (string) $wc_result['status'], array( 'reconciled_wc', 'wc_order_already_logged' ), true ) ) {
		$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
		if ( is_array( $plan_data ) && arsenal_settings_wc_dd_overdue_cron_due_ready( $plan_data ) ) {
			// WC sync did not advance due yet — still try Stripe below.
		} else {
			return $wc_result;
		}
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return array( 'status' => 'skipped_no_user' );
	}

	$stripe_match = arsenal_settings_wc_dd_overdue_find_stripe_match_for_due_payment( $user, $plan_id, $due_ts, $expect );
	if ( is_wp_error( $stripe_match ) ) {
		arsenal_settings_wc_dd_overdue_cron_log(
			'stripe_error',
			array(
				'user_id' => $user_id,
				'plan_id' => $plan_id,
				'error'   => $stripe_match->get_error_message(),
			)
		);
		return array( 'status' => 'stripe_error' );
	}

	if ( empty( $stripe_match ) ) {
		arsenal_settings_wc_dd_overdue_cron_log(
			'no_stripe_match_for_due',
			array(
				'user_id'      => $user_id,
				'plan_id'      => $plan_id,
				'due_ts'       => $due_ts,
				'due_date'     => wp_date( 'Y-m-d', $due_ts ),
				'amount_minor' => $expect['amount_minor'],
				'currency'     => $expect['currency'],
			)
		);
		return array( 'status' => 'no_stripe_match' );
	}

	$insert_result = arsenal_settings_wc_dd_overdue_insert_payment_log_from_stripe_match( $user_id, $plan_id, $stripe_match, $user );
	$log_id        = isset( $insert_result['arm_log_id'] ) ? (int) $insert_result['arm_log_id'] : 0;
	$insert_status = isset( $insert_result['status'] ) ? (string) $insert_result['status'] : '';

	if ( 'already_logged_for_transaction' === $insert_status ) {
		if ( function_exists( 'arsenal_settings_restore_arm_plan_from_stripe_match' ) ) {
			arsenal_settings_restore_arm_plan_from_stripe_match( $user_id, $plan_id, $stripe_match );
		}
		if ( function_exists( 'arsenal_settings_resolve_arm_next_due_payment_timestamp' ) ) {
			$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
			$plan_data = is_array( $plan_data ) ? $plan_data : array();
			$next_due  = arsenal_settings_resolve_arm_next_due_payment_timestamp( $user_id, $plan_id, null );
			if ( $next_due > $due_ts && function_exists( 'arsenal_settings_apply_arm_next_due_payment_to_plan_data' ) ) {
				$plan_data = arsenal_settings_apply_arm_next_due_payment_to_plan_data( $plan_data, $next_due );
				update_user_meta( $user_id, 'arm_user_plan_' . $plan_id, $plan_data );
			}
		}
		return array(
			'status'       => 'already_logged_for_transaction',
			'arm_log_id'   => $log_id,
			'stripe_invoice' => isset( $stripe_match['invoice_id'] ) ? (string) $stripe_match['invoice_id'] : '',
		);
	}

	if ( 'inserted' !== $insert_status || $log_id < 1 ) {
		arsenal_settings_wc_dd_overdue_cron_log(
			'payment_log_insert_skipped',
			array(
				'user_id'  => $user_id,
				'plan_id'  => $plan_id,
				'status'   => $insert_status,
				'invoice'  => isset( $stripe_match['invoice_id'] ) ? (string) $stripe_match['invoice_id'] : '',
			)
		);
		return array( 'status' => 'insert_failed' );
	}

	if ( function_exists( 'arsenal_settings_restore_arm_plan_from_stripe_match' ) ) {
		arsenal_settings_restore_arm_plan_from_stripe_match( $user_id, $plan_id, $stripe_match );
	}

	arsenal_settings_wc_dd_overdue_cron_apply_arm_success( $user_id, $plan_id, 'reconciled_stripe' );

	$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
	$still_due = is_array( $plan_data ) && arsenal_settings_wc_dd_overdue_cron_due_ready( $plan_data );

	arsenal_settings_wc_dd_overdue_cron_log(
		'payment_log_inserted',
		array(
			'user_id'        => $user_id,
			'plan_id'        => $plan_id,
			'arm_log_id'     => $log_id,
			'stripe_invoice' => isset( $stripe_match['invoice_id'] ) ? (string) $stripe_match['invoice_id'] : '',
			'stripe_charge'  => isset( $stripe_match['latest_charge_id'] ) ? (string) $stripe_match['latest_charge_id'] : '',
			'paid_at'        => isset( $stripe_match['paid_at'] ) ? (int) $stripe_match['paid_at'] : 0,
		)
	);

	return array(
		'status'     => $still_due ? 'reconciled_stripe_due_stale' : 'reconciled_stripe',
		'arm_log_id' => $log_id,
	);
}

/**
 * Cron callback: paginated scan of due WooCommerce DD members.
 */
function arsenal_settings_wc_dd_overdue_cron_run() {
	$started = microtime( true );

	$batch_size = (int) apply_filters( 'arsenal_settings_wc_dd_overdue_cron_batch_size', 15 );
	$batch_size = max( 5, min( 50, $batch_size ) );
	$offset     = (int) get_option( ARSENAL_SETTINGS_WC_DD_OVERDUE_CRON_OFFSET_OPTION, 0 );

	$summary = array(
		'users_scanned'       => 0,
		'plans_checked'       => 0,
		'reconciled_wc'       => 0,
		'reconciled_stripe'   => 0,
		'already_logged'      => 0,
		'insert_failed'       => 0,
		'no_stripe_match'     => 0,
		'skipped'             => 0,
		'errors'              => 0,
	);

	if ( ! function_exists( 'get_users' ) ) {
		arsenal_settings_wc_dd_overdue_cron_log( 'cron_skip', array( 'reason' => 'wordpress_users_unavailable' ) );
		return;
	}

	$users = get_users(
		array(
			'meta_key'     => 'arm_user_plan_ids',
			'meta_compare' => 'EXISTS',
			'number'       => $batch_size,
			'offset'       => $offset,
			'orderby'      => 'ID',
			'order'        => 'ASC',
			'fields'       => array( 'ID' ),
		)
	);

	if ( empty( $users ) ) {
		update_option( ARSENAL_SETTINGS_WC_DD_OVERDUE_CRON_OFFSET_OPTION, 0, false );
		arsenal_settings_wc_dd_overdue_cron_log(
			'cron_complete',
			array_merge(
				$summary,
				array(
					'offset_reset' => true,
					'duration_ms'  => (int) round( 1000 * ( microtime( true ) - $started ) ),
				)
			)
		);
		return;
	}

	arsenal_settings_wc_dd_overdue_cron_log(
		'cron_start',
		array(
			'hook'       => ARSENAL_SETTINGS_WC_DD_OVERDUE_CRON_HOOK,
			'offset'     => $offset,
			'batch_size' => $batch_size,
			'grace_sec'  => (int) apply_filters( 'arsenal_settings_wc_dd_overdue_grace_seconds', ARSENAL_SETTINGS_WC_DD_OVERDUE_GRACE_SECONDS ),
		)
	);

	$dd_cache = array();

	foreach ( $users as $user ) {
		$user_id = isset( $user->ID ) ? (int) $user->ID : 0;
		if ( $user_id < 1 ) {
			continue;
		}

		$summary['users_scanned']++;
		$plan_ids = get_user_meta( $user_id, 'arm_user_plan_ids', true );
		$plan_ids = is_array( $plan_ids ) ? array_map( 'intval', $plan_ids ) : array();

		foreach ( $plan_ids as $plan_id ) {
			if ( $plan_id < 1 ) {
				continue;
			}

			if ( ! arsenal_settings_wc_dd_overdue_cron_plan_qualifies( $user_id, $plan_id, $dd_cache ) ) {
				continue;
			}

			$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
			$plan_data = is_array( $plan_data ) ? $plan_data : array();
			if ( ! arsenal_settings_wc_dd_overdue_cron_due_ready( $plan_data ) ) {
				continue;
			}

			$summary['plans_checked']++;
			$result = arsenal_settings_wc_dd_overdue_cron_reconcile_plan( $user_id, $plan_id );
			$status = isset( $result['status'] ) ? (string) $result['status'] : '';

			switch ( $status ) {
				case 'reconciled_wc':
					$summary['reconciled_wc']++;
					break;
				case 'wc_order_already_logged':
					$summary['skipped']++;
					break;
				case 'reconciled_stripe':
				case 'reconciled_stripe_due_stale':
					$summary['reconciled_stripe']++;
					break;
				case 'already_logged_for_transaction':
					$summary['already_logged']++;
					break;
				case 'insert_failed':
					$summary['insert_failed']++;
					break;
				case 'no_stripe_match':
				case 'no_wc_order_match':
					$summary['no_stripe_match']++;
					break;
				case 'stripe_error':
					$summary['errors']++;
					break;
				default:
					$summary['skipped']++;
			}

			$log_extra = array(
				'user_id' => $user_id,
				'plan_id' => $plan_id,
				'status'  => $status,
			);
			if ( isset( $result['arm_log_id'] ) ) {
				$log_extra['arm_log_id'] = (int) $result['arm_log_id'];
			}
			if ( isset( $result['order_id'] ) ) {
				$log_extra['order_id'] = (int) $result['order_id'];
			}
			arsenal_settings_wc_dd_overdue_cron_log( 'member_plan_checked', $log_extra );
		}
	}

	update_option( ARSENAL_SETTINGS_WC_DD_OVERDUE_CRON_OFFSET_OPTION, $offset + count( $users ), false );

	$summary['duration_ms'] = (int) round( 1000 * ( microtime( true ) - $started ) );
	$summary['next_offset']   = $offset + count( $users );

	arsenal_settings_wc_dd_overdue_cron_log( 'cron_complete', $summary );
}
add_action( ARSENAL_SETTINGS_WC_DD_OVERDUE_CRON_HOOK, 'arsenal_settings_wc_dd_overdue_cron_run' );

/**
 * Clear scheduled event on plugin deactivation.
 */
function arsenal_settings_wc_dd_overdue_cron_deactivate() {
	wp_clear_scheduled_hook( ARSENAL_SETTINGS_WC_DD_OVERDUE_CRON_HOOK );
}
register_deactivation_hook( __DIR__ . '/arsenal-settings.php', 'arsenal_settings_wc_dd_overdue_cron_deactivate' );
