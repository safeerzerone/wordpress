<?php
/**
 * Sync user meta for ARMember subscription plans:
 * - `current_arsenal_subscription` — reflects the member's active plan (cleared on cancel).
 * - `arsenal_active_plan` — last assigned/subscribed plan title (unchanged on cancel).
 * - `renewal_date` — next due or expiry from ARMember plan meta (`arm_next_due_payment`, else `arm_expire_plan`; Y-m-d).
 * - `selected_payment_method` — payment gateway chosen at signup/checkout (card, paypal, Bank Direct Debit, …).
 * - `selected_payment_type` — plan Setup Type: Recurring (subscription) or Single Year (paid finite).
 * - `custom_recent_payment_status` — status of the member's latest payment (default: pending).
 * - `user_membership_status` — membership lifecycle: pending (signup / awaiting payment), active (paid & not suspended), cancelled (suspended for payment pending or failure).
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User-facing label for ARMember's WooCommerce payment gateway (emails, dashboard, meta).
 *
 * @return string
 */
function arsenal_settings_woocommerce_gateway_display_label() {
	return 'Bank Direct Debit';
}

/**
 * Show "Bank Direct Debit" instead of "WooCommerce" wherever ARMember resolves gateway names
 * (transaction emails, member/admin dashboards, invoices, etc.).
 *
 * @param array<string,string> $gateway_names Gateway key => display name.
 * @return array<string,string>
 */
function arsenal_settings_arm_filter_gateway_names_woocommerce_label( $gateway_names ) {
	if ( ! is_array( $gateway_names ) ) {
		$gateway_names = array();
	}
	$gateway_names['woocommerce'] = __( 'Bank Direct Debit', 'arsenal-settings' );
	return $gateway_names;
}
add_filter( 'arm_filter_gateway_names', 'arsenal_settings_arm_filter_gateway_names_woocommerce_label', 20 );

/**
 * Fallback when a caller passes the raw key through `arm_gateway_name_by_key`.
 *
 * @param string $pg_name     Resolved display name.
 * @param string $gateway_key Gateway key.
 * @return string
 */
function arsenal_settings_arm_gateway_name_by_key_woocommerce_label( $pg_name, $gateway_key ) {
	if ( 'woocommerce' === strtolower( trim( (string) $gateway_key ) ) ) {
		return __( 'Bank Direct Debit', 'arsenal-settings' );
	}
	return $pg_name;
}
add_filter( 'arm_gateway_name_by_key', 'arsenal_settings_arm_gateway_name_by_key_woocommerce_label', 20, 2 );

/**
 * One-time: rewrite stored `selected_payment_method` from "woocommerce" to "Bank Direct Debit".
 */
function arsenal_settings_migrate_selected_payment_method_woocommerce_label() {
	if ( get_option( 'arsenal_settings_migrated_wc_payment_method_label', false ) ) {
		return;
	}

	global $wpdb;
	$meta_key = arsenal_settings_selected_payment_method_meta_key();
	$label    = arsenal_settings_woocommerce_gateway_display_label();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time usermeta label rewrite.
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->usermeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s",
			$label,
			$meta_key,
			'woocommerce'
		)
	);

	update_option( 'arsenal_settings_migrated_wc_payment_method_label', 1, false );
}
add_action( 'init', 'arsenal_settings_migrate_selected_payment_method_woocommerce_label', 5 );

/**
 * User meta key storing the current membership title (plain string).
 *
 * @return string
 */
function arsenal_settings_current_arsenal_subscription_meta_key() {
	return 'current_arsenal_subscription';
}

/**
 * User meta key storing the last assigned/subscribed plan title (plain string; not cleared on cancel).
 *
 * @return string
 */
function arsenal_settings_arsenal_active_plan_meta_key() {
	return 'arsenal_active_plan';
}

/**
 * User meta key storing the next membership due or expiry date (Y-m-d).
 *
 * @return string
 */
function arsenal_settings_renewal_date_meta_key() {
	return 'renewal_date';
}

/**
 * User meta key storing the member's selected payment method / gateway.
 *
 * @return string
 */
function arsenal_settings_selected_payment_method_meta_key() {
	return 'selected_payment_method';
}

/**
 * User meta key storing the member's selected subscription type.
 *
 * @return string
 */
function arsenal_settings_selected_payment_type_meta_key() {
	return 'selected_payment_type';
}

/**
 * Stored value for members billed automatically each term.
 *
 * @return string
 */
function arsenal_settings_selected_payment_type_recurring_label() {
	return 'Recurring';
}

/**
 * Stored value for members who paid once for a single term.
 *
 * @return string
 */
function arsenal_settings_selected_payment_type_single_year_label() {
	return 'Single Year';
}

/**
 * User meta key storing the status of the member's most recent payment.
 *
 * @return string
 */
function arsenal_settings_custom_recent_payment_status_meta_key() {
	return 'custom_recent_payment_status';
}

/**
 * Default value for `custom_recent_payment_status`.
 *
 * @return string
 */
function arsenal_settings_custom_recent_payment_status_default() {
	return 'pending';
}

/**
 * User meta key storing the member's overall membership status.
 *
 * @return string
 */
function arsenal_settings_user_membership_status_meta_key() {
	return 'user_membership_status';
}

/**
 * Default value for `user_membership_status`.
 *
 * @return string
 */
function arsenal_settings_user_membership_status_default() {
	return 'pending';
}

/**
 * Allowed stored values for `user_membership_status`.
 *
 * @return array<int,string>
 */
function arsenal_settings_user_membership_status_allowed_values() {
	return array( 'pending', 'active', 'cancelled' );
}

/**
 * Resolve `user_membership_status` from ARMember plan state and recent payment status.
 *
 * - cancelled — at least one plan is in `arm_user_suspended_plan_ids` (payment pending/failure suspension).
 * - active    — has an active plan (not suspended/cancelled/expired) and latest payment is successful.
 * - pending   — everything else (new signup, checkout in progress, awaiting first payment).
 *
 * @param int $user_id WordPress user ID.
 * @return string One of: pending, active, cancelled.
 */
function arsenal_settings_resolve_user_membership_status( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return arsenal_settings_user_membership_status_default();
	}

	$suspended = get_user_meta( $user_id, 'arm_user_suspended_plan_ids', true );
	$suspended = is_array( $suspended ) ? array_filter( array_map( 'intval', $suspended ) ) : array();
	if ( ! empty( $suspended ) ) {
		return 'cancelled';
	}

	$active_plan_ids = arsenal_settings_get_user_active_armember_plan_ids( $user_id );
	if ( empty( $active_plan_ids ) ) {
		return arsenal_settings_user_membership_status_default();
	}

	$payment_status = get_user_meta( $user_id, arsenal_settings_custom_recent_payment_status_meta_key(), true );
	$payment_status = is_string( $payment_status ) ? strtolower( trim( $payment_status ) ) : '';
	if ( '' === $payment_status ) {
		$payment_status = arsenal_settings_custom_recent_payment_status_default();
	}

	if ( 'success' === $payment_status ) {
		return 'active';
	}

	return arsenal_settings_user_membership_status_default();
}

/**
 * Update `user_membership_status` for a user.
 *
 * @param int $user_id WordPress user ID.
 */
function arsenal_settings_update_user_membership_status_meta( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$status = arsenal_settings_resolve_user_membership_status( $user_id );
	if ( ! in_array( $status, arsenal_settings_user_membership_status_allowed_values(), true ) ) {
		$status = arsenal_settings_user_membership_status_default();
	}

	update_user_meta( $user_id, arsenal_settings_user_membership_status_meta_key(), $status );
}

/**
 * Label stored when the member has no active ARMember plan.
 *
 * @return string
 */
function arsenal_settings_no_active_plan_label() {
	return 'No plan';
}

/**
 * Active ARMember plan IDs for a user (not suspended, cancelled, or expired).
 *
 * @param int $user_id WordPress user ID.
 * @return array<int,int> Plan IDs.
 */
function arsenal_settings_get_user_active_armember_plan_ids( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return array();
	}

	$plan_ids = get_user_meta( $user_id, 'arm_user_plan_ids', true );
	$plan_ids = is_array( $plan_ids ) ? array_map( 'intval', $plan_ids ) : array();
	if ( empty( $plan_ids ) ) {
		return array();
	}

	$suspended = get_user_meta( $user_id, 'arm_user_suspended_plan_ids', true );
	$suspended = is_array( $suspended ) ? array_map( 'intval', $suspended ) : array();

	$active = array();
	foreach ( $plan_ids as $plan_id ) {
		if ( $plan_id < 1 ) {
			continue;
		}
		if ( function_exists( 'arsenal_settings_user_has_active_armember_plan' ) ) {
			if ( arsenal_settings_user_has_active_armember_plan( $user_id, $plan_id ) ) {
				$active[] = $plan_id;
			}
			continue;
		}
		if ( in_array( $plan_id, $suspended, true ) ) {
			continue;
		}
		$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
		if ( ! is_array( $plan_data ) || empty( $plan_data ) ) {
			continue;
		}
		if ( ! empty( $plan_data['arm_cencelled_plan'] ) && 'yes' === (string) $plan_data['arm_cencelled_plan'] ) {
			continue;
		}
		$expire = isset( $plan_data['arm_expire_plan'] ) ? $plan_data['arm_expire_plan'] : '';
		if ( $expire !== '' && $expire !== null && (int) $expire > 0 && (int) $expire < (int) current_time( 'timestamp' ) ) {
			continue;
		}
		$active[] = $plan_id;
	}

	return array_values( array_unique( $active ) );
}

/**
 * Resolve the membership title to store for the user's current subscription.
 *
 * @param int $user_id            WordPress user ID.
 * @param int $preferred_plan_id  Plan ID just assigned/changed (optional).
 * @return string Membership title or empty string when none.
 */
function arsenal_settings_resolve_current_arsenal_subscription_title( $user_id, $preferred_plan_id = 0 ) {
	$user_id           = (int) $user_id;
	$preferred_plan_id = (int) $preferred_plan_id;

	$active_plan_ids = arsenal_settings_get_user_active_armember_plan_ids( $user_id );
	if ( empty( $active_plan_ids ) ) {
		return arsenal_settings_no_active_plan_label();
	}

	$plan_id = 0;
	if ( $preferred_plan_id > 0 && in_array( $preferred_plan_id, $active_plan_ids, true ) ) {
		$plan_id = $preferred_plan_id;
	} else {
		$plan_id = (int) $active_plan_ids[0];
	}

	global $arm_subscription_plans;
	if ( ! is_object( $arm_subscription_plans ) || ! method_exists( $arm_subscription_plans, 'arm_get_plan_name_by_id' ) ) {
		return '';
	}

	$name = $arm_subscription_plans->arm_get_plan_name_by_id( $plan_id );
	$name = is_string( $name ) ? trim( $name ) : '';
	return $name !== '' ? $name : arsenal_settings_no_active_plan_label();
}

/**
 * Update `current_arsenal_subscription` from active ARMember plans.
 *
 * @param int $user_id            WordPress user ID.
 * @param int $preferred_plan_id  Plan ID just assigned/changed (optional).
 */
function arsenal_settings_update_current_arsenal_subscription_meta( $user_id, $preferred_plan_id = 0 ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$title = arsenal_settings_resolve_current_arsenal_subscription_title( $user_id, $preferred_plan_id );
	update_user_meta( $user_id, arsenal_settings_current_arsenal_subscription_meta_key(), $title );
}

/**
 * Resolve an ARMember plan title by plan ID.
 *
 * @param int $plan_id ARMember plan ID.
 * @return string Plan title or empty string when unavailable.
 */
function arsenal_settings_resolve_armember_plan_title_by_id( $plan_id ) {
	$plan_id = (int) $plan_id;
	if ( $plan_id < 1 ) {
		return '';
	}

	global $arm_subscription_plans;
	if ( ! is_object( $arm_subscription_plans ) || ! method_exists( $arm_subscription_plans, 'arm_get_plan_name_by_id' ) ) {
		return '';
	}

	$name = $arm_subscription_plans->arm_get_plan_name_by_id( $plan_id );
	$name = is_string( $name ) ? trim( $name ) : '';
	return $name;
}

/**
 * Update `arsenal_active_plan` when a user subscribes or is assigned a plan.
 *
 * Unlike `current_arsenal_subscription`, this meta is not updated on cancellation.
 *
 * @param int $user_id WordPress user ID.
 * @param int $plan_id ARMember plan ID just assigned/subscribed.
 */
function arsenal_settings_update_arsenal_active_plan_meta( $user_id, $plan_id ) {
	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 ) {
		return;
	}

	$title = arsenal_settings_resolve_armember_plan_title_by_id( $plan_id );
	if ( $title === '' ) {
		return;
	}

	update_user_meta( $user_id, arsenal_settings_arsenal_active_plan_meta_key(), $title );
}

/**
 * Resolve the renewal timestamp for a member plan row.
 *
 * Priority:
 * 1. `arm_next_due_payment` — next recurring billing date.
 * 2. Computed next due (ARMember / Stripe / WooCommerce) when meta is not written yet.
 * 3. `arm_expire_plan` — membership expiry for finite / non-recurring plans.
 *
 * @param int $user_id WordPress user ID.
 * @param int $plan_id ARMember plan ID.
 * @return int Unix timestamp, or 0 when unavailable.
 */
function arsenal_settings_get_armember_plan_renewal_timestamp( $user_id, $plan_id ) {
	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 ) {
		return 0;
	}

	$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
	if ( is_array( $plan_data ) && ! empty( $plan_data['arm_next_due_payment'] ) ) {
		return (int) $plan_data['arm_next_due_payment'];
	}

	// Admin assignment and some gateway flows update plan meta in multiple passes; the assignment
	// hook can run before `arm_next_due_payment` is persisted. Fall back to ARMember/Stripe resolution.
	if ( function_exists( 'arsenal_settings_resolve_arm_next_due_payment_timestamp' ) ) {
		$resolved = (int) arsenal_settings_resolve_arm_next_due_payment_timestamp( $user_id, $plan_id, null );
		if ( $resolved > 0 ) {
			return $resolved;
		}
	}

	if ( is_array( $plan_data ) && ! empty( $plan_data['arm_expire_plan'] ) ) {
		$expire = (int) $plan_data['arm_expire_plan'];
		if ( $expire > 0 ) {
			return $expire;
		}
	}

	return 0;
}

/**
 * Resolve the renewal date string (`Y-m-d`) from ARMember plan meta.
 *
 * Uses `arm_next_due_payment` when present, otherwise `arm_expire_plan` for finite plans.
 *
 * @param int $user_id            WordPress user ID.
 * @param int $preferred_plan_id  Plan ID just assigned/changed (optional).
 * @return string Date string or empty when no due/expiry date is available.
 */
function arsenal_settings_resolve_renewal_date_for_user( $user_id, $preferred_plan_id = 0 ) {
	$user_id           = (int) $user_id;
	$preferred_plan_id = (int) $preferred_plan_id;

	$plan_id = 0;
	if ( $preferred_plan_id > 0 ) {
		$assigned_plan_ids = get_user_meta( $user_id, 'arm_user_plan_ids', true );
		$assigned_plan_ids = is_array( $assigned_plan_ids ) ? array_map( 'intval', $assigned_plan_ids ) : array();
		if ( in_array( $preferred_plan_id, $assigned_plan_ids, true ) ) {
			$plan_id = $preferred_plan_id;
		}
	}

	if ( $plan_id < 1 ) {
		$active_plan_ids = arsenal_settings_get_user_active_armember_plan_ids( $user_id );
		if ( empty( $active_plan_ids ) ) {
			return '';
		}

		$plan_id = (int) $active_plan_ids[0];
	}

	$renewal_ts = arsenal_settings_get_armember_plan_renewal_timestamp( $user_id, $plan_id );
	if ( $renewal_ts < 1 ) {
		return '';
	}

	return wp_date( 'Y-m-d', $renewal_ts );
}

/**
 * Update `renewal_date` from the member's ARMember next due or expiry date.
 *
 * @param int $user_id            WordPress user ID.
 * @param int $preferred_plan_id  Plan ID just assigned/changed (optional).
 */
function arsenal_settings_update_renewal_date_meta( $user_id, $preferred_plan_id = 0 ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$date = arsenal_settings_resolve_renewal_date_for_user( $user_id, $preferred_plan_id );
	if ( $date === '' ) {
		return;
	}

	update_user_meta( $user_id, arsenal_settings_renewal_date_meta_key(), $date );
}

/**
 * Normalize an ARMember / WooCommerce payment gateway slug for `selected_payment_method`.
 *
 * Stripe card gateways are stored as `card` (user-facing label); WooCommerce gateway as Bank Direct Debit.
 *
 * @param string $gateway Raw gateway slug (e.g. stripe, paypal, woocommerce).
 * @return string Normalized value, or empty when unavailable.
 */
function arsenal_settings_normalize_selected_payment_method( $gateway ) {
	$gateway = strtolower( trim( (string) $gateway ) );
	if ( $gateway === '' ) {
		return '';
	}

	$map = array(
		'stripe'         => 'card',
		'stripe_sca'     => 'card',
		'stripe_connect' => 'card',
		'paypal'         => 'paypal',
		'woocommerce'    => arsenal_settings_woocommerce_gateway_display_label(),
	);

	/**
	 * Filter the gateway → selected_payment_method map.
	 *
	 * @param array<string,string> $map Gateway slug => stored meta value.
	 */
	$map = apply_filters( 'arsenal_settings_selected_payment_method_map', $map );

	if ( isset( $map[ $gateway ] ) ) {
		return (string) $map[ $gateway ];
	}

	return $gateway;
}

/**
 * Resolve `selected_payment_method` from an ARMember plan row's `arm_user_gateway`.
 *
 * @param int $user_id            WordPress user ID.
 * @param int $preferred_plan_id  Plan ID just assigned/changed (optional).
 * @return string Normalized payment method, or empty when unavailable.
 */
function arsenal_settings_resolve_selected_payment_method_for_user( $user_id, $preferred_plan_id = 0 ) {
	$user_id           = (int) $user_id;
	$preferred_plan_id = (int) $preferred_plan_id;
	if ( $user_id < 1 ) {
		return '';
	}

	$plan_id = 0;
	if ( $preferred_plan_id > 0 ) {
		$plan_id = $preferred_plan_id;
	} else {
		$active_plan_ids = arsenal_settings_get_user_active_armember_plan_ids( $user_id );
		if ( ! empty( $active_plan_ids ) ) {
			$plan_id = (int) $active_plan_ids[0];
		}
	}

	if ( $plan_id < 1 ) {
		return '';
	}

	$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
	if ( ! is_array( $plan_data ) || empty( $plan_data['arm_user_gateway'] ) ) {
		return '';
	}

	return arsenal_settings_normalize_selected_payment_method( $plan_data['arm_user_gateway'] );
}

/**
 * Update `selected_payment_method` from the member's ARMember gateway (or an explicit value).
 *
 * @param int         $user_id            WordPress user ID.
 * @param int         $preferred_plan_id  Plan ID just assigned/changed (optional).
 * @param string|null $explicit_gateway   When set, store this gateway instead of reading plan meta.
 */
function arsenal_settings_update_selected_payment_method_meta( $user_id, $preferred_plan_id = 0, $explicit_gateway = null ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	if ( null !== $explicit_gateway ) {
		$method = arsenal_settings_normalize_selected_payment_method( $explicit_gateway );
	} else {
		$method = arsenal_settings_resolve_selected_payment_method_for_user( $user_id, $preferred_plan_id );
	}

	if ( $method === '' ) {
		return;
	}

	update_user_meta( $user_id, arsenal_settings_selected_payment_method_meta_key(), $method );
}

/**
 * Map an ARMember plan Setup Type to a `selected_payment_type` value.
 *
 * Setup Type "Subscription / Recurring Payment" is stored as `recurring`; "Paid Plan (finite)"
 * is stored as `paid_finite`. Free and lifetime (`paid_infinite`) plans are neither, so they
 * yield an empty string and leave the meta untouched.
 *
 * @param string $plan_type ARMember `arm_subscription_plan_type`.
 * @return string Meta value, or empty string when the type is not billable per term.
 */
function arsenal_settings_map_armember_plan_type_to_selected_payment_type( $plan_type ) {
	$plan_type = strtolower( trim( (string) $plan_type ) );

	switch ( $plan_type ) {
		case 'recurring':
			return arsenal_settings_selected_payment_type_recurring_label();
		case 'paid_finite':
			return arsenal_settings_selected_payment_type_single_year_label();
		default:
			return '';
	}
}

/**
 * Read an ARMember plan's Setup Type.
 *
 * Prefers the live plan record; falls back to the Setup Type snapshotted onto the member's
 * plan row, which is what remains available if the plan is later edited or deleted.
 *
 * @param int $plan_id ARMember plan ID.
 * @param int $user_id WordPress user ID, used for the plan-row fallback (optional).
 * @return string Setup Type, or empty string when it cannot be read.
 */
function arsenal_settings_get_armember_plan_type( $plan_id, $user_id = 0 ) {
	$plan_id = (int) $plan_id;
	$user_id = (int) $user_id;
	if ( $plan_id < 1 ) {
		return '';
	}

	if ( class_exists( 'ARM_Plan' ) ) {
		$plan = new ARM_Plan( $plan_id );
		if ( ! empty( $plan->ID ) && ! empty( $plan->type ) ) {
			return (string) $plan->type;
		}
	}

	if ( $user_id > 0 ) {
		$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
		if ( is_array( $plan_data ) && ! empty( $plan_data['arm_current_plan_detail']['arm_subscription_plan_type'] ) ) {
			return (string) $plan_data['arm_current_plan_detail']['arm_subscription_plan_type'];
		}
	}

	return '';
}

/**
 * Resolve `selected_payment_type` for a member from the plan's Setup Type.
 *
 * @param int $user_id           WordPress user ID.
 * @param int $preferred_plan_id Plan ID just assigned/changed (optional).
 * @return string Meta value, or empty string when it cannot be determined.
 */
function arsenal_settings_resolve_selected_payment_type_for_user( $user_id, $preferred_plan_id = 0 ) {
	$user_id           = (int) $user_id;
	$preferred_plan_id = (int) $preferred_plan_id;
	if ( $user_id < 1 ) {
		return '';
	}

	$plan_id = $preferred_plan_id;
	if ( $plan_id < 1 ) {
		$active_plan_ids = arsenal_settings_get_user_active_armember_plan_ids( $user_id );
		if ( ! empty( $active_plan_ids ) ) {
			$plan_id = (int) $active_plan_ids[0];
		}
	}

	if ( $plan_id < 1 ) {
		return '';
	}

	return arsenal_settings_map_armember_plan_type_to_selected_payment_type(
		arsenal_settings_get_armember_plan_type( $plan_id, $user_id )
	);
}

/**
 * Update `selected_payment_type` from the Setup Type of the member's plan.
 *
 * @param int $user_id           WordPress user ID.
 * @param int $preferred_plan_id Plan ID just assigned/changed (optional).
 */
function arsenal_settings_update_selected_payment_type_meta( $user_id, $preferred_plan_id = 0 ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$type = arsenal_settings_resolve_selected_payment_type_for_user( $user_id, $preferred_plan_id );
	if ( '' === $type ) {
		return;
	}

	update_user_meta( $user_id, arsenal_settings_selected_payment_type_meta_key(), $type );
}

/**
 * Normalize a payment/transaction status for `custom_recent_payment_status`.
 *
 * Aligns with ARMember's arm_add_transaction status mapping (plus WooCommerce order statuses).
 *
 * @param mixed $status Raw status (string or bank-transfer numeric code).
 * @return string One of: pending, success, failed, canceled, expired.
 */
function arsenal_settings_normalize_custom_recent_payment_status( $status ) {
	$status = strtolower( trim( (string) $status ) );

	switch ( $status ) {
		case '1':
		case 'completed':
		case 'complete':
		case 'paid':
		case 'active':
		case 'trialing':
		case 'succeeded':
		case 'success':
		case 'processing': // WooCommerce paid/processing.
		case 'wps_renewal':
			return 'success';
		case '0':
		case 'pending':
		case 'past_due':
		case 'on-hold':
		case 'on_hold':
		case 'requires_action': // Stripe PaymentIntent awaiting SCA / 3DS.
		case 'requires_confirmation':
		case 'requires_payment_method':
		case 'incomplete':
			return 'pending';
		case '2':
		case 'canceled':
		case 'cancelled':
		case 'unpaid':
		case 'refunded':
			return 'canceled';
		case 'failed':
			return 'failed';
		case 'expired':
			return 'expired';
		default:
			return $status !== '' ? $status : arsenal_settings_custom_recent_payment_status_default();
	}
}

/**
 * Update `custom_recent_payment_status` for a user.
 *
 * @param int    $user_id WordPress user ID.
 * @param mixed  $status  Raw payment/transaction status.
 */
function arsenal_settings_update_custom_recent_payment_status_meta( $user_id, $status ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$normalized = arsenal_settings_normalize_custom_recent_payment_status( $status );
	update_user_meta( $user_id, arsenal_settings_custom_recent_payment_status_meta_key(), $normalized );
	arsenal_settings_update_user_membership_status_meta( $user_id );
}

/**
 * Ensure new users start with `custom_recent_payment_status` = pending.
 *
 * @param int $user_id WordPress user ID.
 */
function arsenal_settings_init_custom_recent_payment_status_on_register( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$existing = get_user_meta( $user_id, arsenal_settings_custom_recent_payment_status_meta_key(), true );
	if ( '' === $existing || false === $existing || null === $existing ) {
		arsenal_settings_update_custom_recent_payment_status_meta(
			$user_id,
			arsenal_settings_custom_recent_payment_status_default()
		);
	}

	$membership_status = get_user_meta( $user_id, arsenal_settings_user_membership_status_meta_key(), true );
	if ( '' === $membership_status || false === $membership_status || null === $membership_status ) {
		update_user_meta( $user_id, arsenal_settings_user_membership_status_meta_key(), arsenal_settings_user_membership_status_default() );
	}
}
add_action( 'user_register', 'arsenal_settings_init_custom_recent_payment_status_on_register', 20 );

/**
 * Read the latest visible ARMember payment-log status for a user.
 *
 * @param int $user_id WordPress user ID.
 * @return string Empty string when no log is found.
 */
function arsenal_settings_get_latest_arm_payment_log_status( $user_id ) {
	global $wpdb;

	$user_id = (int) $user_id;
	if ( $user_id < 1 || ! function_exists( 'arsenal_settings_get_armember_payment_log_table' ) ) {
		return '';
	}

	$table = arsenal_settings_get_armember_payment_log_table();
	if ( '' === $table ) {
		return '';
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from ARMember helper.
	$status = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT `arm_transaction_status` FROM `{$table}` WHERE `arm_user_id` = %d AND `arm_display_log` = %d ORDER BY `arm_log_id` DESC LIMIT 1",
			$user_id,
			1
		)
	);

	return is_string( $status ) ? $status : '';
}

/**
 * Sync `custom_recent_payment_status` from the user's latest ARMember payment log.
 *
 * Needed for Stripe SCA card flows: ARMember often creates a pending log, then later
 * flips it to success with a raw `$wpdb->update` that does not fire `arm_after_add_transaction`.
 *
 * @param int $user_id WordPress user ID.
 * @return bool True when a status was applied from a payment log.
 */
function arsenal_settings_sync_custom_recent_payment_status_from_latest_log( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return false;
	}

	$status = arsenal_settings_get_latest_arm_payment_log_status( $user_id );
	if ( '' === $status ) {
		return false;
	}

	arsenal_settings_update_custom_recent_payment_status_meta( $user_id, $status );
	return true;
}

/**
 * Resolve a WordPress user ID from an ARMember entry ID (Stripe SCA AJAX payloads).
 *
 * @param int $entry_id ARMember entry ID.
 * @return int
 */
function arsenal_settings_resolve_user_id_from_arm_entry( $entry_id ) {
	global $wpdb, $ARMember, $ARMemberLite;

	$entry_id = (int) $entry_id;
	if ( $entry_id < 1 ) {
		return 0;
	}

	$entries_table = '';
	if ( is_object( $ARMember ) && ! empty( $ARMember->tbl_arm_entries ) ) {
		$entries_table = $ARMember->tbl_arm_entries;
	} elseif ( is_object( $ARMemberLite ) && ! empty( $ARMemberLite->tbl_arm_entries ) ) {
		$entries_table = $ARMemberLite->tbl_arm_entries;
	} else {
		$entries_table = $wpdb->prefix . 'arm_entries';
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from ARMember global/prefix helper.
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT `arm_user_id`, `arm_entry_email` FROM `{$entries_table}` WHERE `arm_entry_id` = %d LIMIT 1",
			$entry_id
		),
		ARRAY_A
	);
	if ( ! is_array( $row ) ) {
		return 0;
	}

	$user_id = isset( $row['arm_user_id'] ) ? (int) $row['arm_user_id'] : 0;
	if ( $user_id > 0 ) {
		return $user_id;
	}

	$email = isset( $row['arm_entry_email'] ) ? sanitize_email( (string) $row['arm_entry_email'] ) : '';
	if ( '' === $email ) {
		return 0;
	}

	$user = get_user_by( 'email', $email );
	return ( $user && ! empty( $user->ID ) ) ? (int) $user->ID : 0;
}

/**
 * Sync `custom_recent_payment_status` from a newly added ARMember payment log.
 *
 * @param array<string,mixed> $log_data Payment log row data from arm_add_transaction.
 */
function arsenal_settings_on_arm_after_add_transaction_payment_status( $log_data ) {
	if ( ! is_array( $log_data ) ) {
		return;
	}

	$user_id = isset( $log_data['arm_user_id'] ) ? (int) $log_data['arm_user_id'] : 0;
	if ( $user_id < 1 ) {
		return;
	}

	$status = isset( $log_data['arm_transaction_status'] ) ? $log_data['arm_transaction_status'] : '';
	arsenal_settings_update_custom_recent_payment_status_meta( $user_id, $status );
}
add_action( 'arm_after_add_transaction', 'arsenal_settings_on_arm_after_add_transaction_payment_status', 20 );

/**
 * Stripe SCA / card completion path that stores a log without always re-firing add_transaction.
 *
 * @param int $payment_log_id ARMember payment log ID.
 */
function arsenal_settings_on_arm_after_completing_transaction_payment_status( $payment_log_id ) {
	global $wpdb;

	$payment_log_id = (int) $payment_log_id;
	if ( $payment_log_id < 1 || ! function_exists( 'arsenal_settings_get_armember_payment_log_table' ) ) {
		return;
	}

	$table = arsenal_settings_get_armember_payment_log_table();
	if ( '' === $table ) {
		return;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from ARMember helper.
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT `arm_user_id`, `arm_transaction_status` FROM `{$table}` WHERE `arm_log_id` = %d LIMIT 1",
			$payment_log_id
		),
		ARRAY_A
	);
	if ( ! is_array( $row ) ) {
		return;
	}

	$user_id = isset( $row['arm_user_id'] ) ? (int) $row['arm_user_id'] : 0;
	if ( $user_id < 1 ) {
		return;
	}

	$status = isset( $row['arm_transaction_status'] ) ? $row['arm_transaction_status'] : '';
	arsenal_settings_update_custom_recent_payment_status_meta( $user_id, $status );
}
add_action( 'arm_after_completing_transaction', 'arsenal_settings_on_arm_after_completing_transaction_payment_status', 20 );

/**
 * After Stripe SCA AJAX charge handlers exit, re-read payment log status.
 *
 * ARMember's SCA success path often updates pending → success via `$wpdb->update`
 * (no `arm_after_add_transaction`), then redirects to the payment success page.
 */
function arsenal_settings_register_stripe_sca_payment_status_shutdown_sync() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only action name gate for shutdown sync.
	$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
	$sca_actions = array(
		'arm_stripe_made_charge',
		'arm_stripe_made_charge_onetime',
		'arm_stripe_made_update_card',
	);
	if ( ! in_array( $action, $sca_actions, true ) ) {
		return;
	}

	add_action(
		'shutdown',
		static function () {
			$user_id = 0;
			if ( is_user_logged_in() ) {
				$user_id = (int) get_current_user_id();
			}
			if ( $user_id < 1 ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Entry id only used to resolve user for meta sync.
				$entry_id = isset( $_REQUEST['entry_id'] ) ? absint( wp_unslash( $_REQUEST['entry_id'] ) ) : 0;
				$user_id  = arsenal_settings_resolve_user_id_from_arm_entry( $entry_id );
			}
			if ( $user_id > 0 ) {
				arsenal_settings_sync_custom_recent_payment_status_from_latest_log( $user_id );
			}
		},
		5
	);
}
add_action( 'init', 'arsenal_settings_register_stripe_sca_payment_status_shutdown_sync', 1 );

/**
 * Mark recent payment success after a recurring payment succeeds.
 *
 * @param int   $user_id       WordPress user ID.
 * @param int   $plan_id       ARMember plan ID (unused).
 * @param mixed $gateway       Payment gateway (unused).
 * @param mixed $payment_mode  Payment mode (unused).
 * @param mixed $user_subsdata Subscription data (unused).
 */
function arsenal_settings_on_arm_recurring_payment_success_status( $user_id, $plan_id = 0, $gateway = '', $payment_mode = '', $user_subsdata = null ) {
	unset( $plan_id, $gateway, $payment_mode, $user_subsdata );
	arsenal_settings_update_custom_recent_payment_status_meta( (int) $user_id, 'success' );
}
add_action( 'arm_after_recurring_payment_success_outside', 'arsenal_settings_on_arm_recurring_payment_success_status', 30, 5 );

/**
 * Bank transfer accepted → success.
 *
 * @param int $user_id WordPress user ID.
 * @param int $plan_id ARMember plan ID (unused).
 * @param int $log_id  Payment log ID (unused).
 */
function arsenal_settings_on_arm_bank_transfer_accepted_status( $user_id, $plan_id = 0, $log_id = 0 ) {
	unset( $plan_id, $log_id );
	arsenal_settings_update_custom_recent_payment_status_meta( (int) $user_id, 'success' );
}
add_action( 'arm_after_accept_bank_transfer_payment', 'arsenal_settings_on_arm_bank_transfer_accepted_status', 20, 3 );

/**
 * Bank transfer declined → failed.
 *
 * @param int $user_id WordPress user ID.
 * @param int $plan_id ARMember plan ID (unused).
 */
function arsenal_settings_on_arm_bank_transfer_declined_status( $user_id, $plan_id = 0 ) {
	unset( $plan_id );
	arsenal_settings_update_custom_recent_payment_status_meta( (int) $user_id, 'failed' );
}
add_action( 'arm_after_decline_bank_transfer_payment', 'arsenal_settings_on_arm_bank_transfer_declined_status', 20, 2 );

/**
 * Normalize ARMember hook plan argument to a single plan ID.
 *
 * @param mixed $plan_id Plan ID or array of plan IDs.
 * @return int
 */
function arsenal_settings_normalize_armember_plan_id( $plan_id ) {
	if ( is_array( $plan_id ) ) {
		$plan_id = reset( $plan_id );
	}
	return (int) $plan_id;
}

/**
 * Queue a renewal_date sync for end-of-request (after ARMember finishes all plan meta writes).
 *
 * @param int $user_id WordPress user ID.
 * @param int $plan_id ARMember plan ID.
 */
function arsenal_settings_defer_renewal_date_meta_sync( $user_id, $plan_id ) {
	static $queued = array();

	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 ) {
		return;
	}

	$key = $user_id . ':' . $plan_id;
	if ( isset( $queued[ $key ] ) ) {
		return;
	}

	$queued[ $key ] = true;
	add_action(
		'shutdown',
		static function () use ( $user_id, $plan_id ) {
			arsenal_settings_update_renewal_date_meta( $user_id, $plan_id );
		},
		999
	);
}

/**
 * @param int   $user_id WordPress user ID.
 * @param mixed $plan_id ARMember plan ID.
 */
function arsenal_settings_on_armember_plan_assigned( $user_id, $plan_id ) {
	$normalized_plan_id = arsenal_settings_normalize_armember_plan_id( $plan_id );

	arsenal_settings_update_current_arsenal_subscription_meta( $user_id, $normalized_plan_id );
	arsenal_settings_update_arsenal_active_plan_meta( $user_id, $normalized_plan_id );
	arsenal_settings_update_renewal_date_meta( $user_id, $normalized_plan_id );
	arsenal_settings_update_selected_payment_method_meta( $user_id, $normalized_plan_id );
	arsenal_settings_update_selected_payment_type_meta( $user_id, $normalized_plan_id );
	arsenal_settings_defer_renewal_date_meta_sync( $user_id, $normalized_plan_id );
	arsenal_settings_update_user_membership_status_meta( $user_id );
}

/**
 * Sync plan-derived user meta when an ARMember plan row (`arm_user_plan_<id>`) is written.
 *
 * Covers subscription, renewal, next-due, and cancellation writes regardless of how the plan
 * was assigned. This is required for payment-gateway self-signups (Stripe SCA card, PayPal),
 * which assign the plan by writing `arm_user_plan_<id>` / `arm_user_plan_ids` directly and do
 * not reliably fire `arm_after_user_plan_change`. Syncs:
 * - `current_arsenal_subscription` — recomputed from the user's active plans.
 * - `arsenal_active_plan` — last assigned/subscribed plan title.
 * - `renewal_date` — next membership due date.
 * - `selected_payment_method` — payment gateway from `arm_user_gateway`.
 *
 * @param int    $meta_id    Meta row ID (unused).
 * @param int    $user_id    WordPress user ID.
 * @param string $meta_key   User meta key.
 * @param mixed  $meta_value Meta value (unused; re-reads plan row).
 */
function arsenal_settings_on_arm_user_plan_meta_changed( $meta_id, $user_id, $meta_key, $meta_value ) {
	unset( $meta_id, $meta_value );

	if ( ! preg_match( '/^arm_user_plan_(\d+)$/', (string) $meta_key, $matches ) ) {
		return;
	}

	$user_id = (int) $user_id;
	$plan_id = (int) $matches[1];

	arsenal_settings_update_current_arsenal_subscription_meta( $user_id, $plan_id );
	arsenal_settings_update_arsenal_active_plan_meta( $user_id, $plan_id );
	arsenal_settings_update_renewal_date_meta( $user_id, $plan_id );
	arsenal_settings_update_selected_payment_method_meta( $user_id, $plan_id );
	arsenal_settings_update_selected_payment_type_meta( $user_id, $plan_id );
	arsenal_settings_update_user_membership_status_meta( $user_id );
}

/**
 * Sync `user_membership_status` when ARMember suspension list changes.
 *
 * @param int    $meta_id    Meta row ID (unused).
 * @param int    $user_id    WordPress user ID.
 * @param string $meta_key   User meta key.
 * @param mixed  $meta_value Meta value (unused).
 */
function arsenal_settings_on_arm_user_suspended_plan_ids_changed( $meta_id, $user_id, $meta_key, $meta_value ) {
	unset( $meta_id, $meta_value );

	if ( 'arm_user_suspended_plan_ids' !== $meta_key ) {
		return;
	}

	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	arsenal_settings_update_user_membership_status_meta( $user_id );
}

/**
 * @param int   $user_id     WordPress user ID.
 * @param int   $plan_id     ARMember plan ID.
 * @param mixed $gateway     Payment gateway (unused).
 * @param mixed $payment_mode Payment mode (unused).
 * @param mixed $user_subsdata Subscription data (unused).
 */
function arsenal_settings_on_arm_after_recurring_payment_success_sync_renewal_date( $user_id, $plan_id, $gateway, $payment_mode, $user_subsdata ) {
	unset( $gateway, $payment_mode, $user_subsdata );
	arsenal_settings_update_renewal_date_meta( (int) $user_id, (int) $plan_id );
}

/**
 * Re-sync after a plan is removed or cancelled.
 *
 * @param int $user_id WordPress user ID.
 */
function arsenal_settings_on_armember_plan_removed( $user_id ) {
	arsenal_settings_update_current_arsenal_subscription_meta( $user_id, 0 );
	arsenal_settings_update_user_membership_status_meta( $user_id );
}

/**
 * @param int   $user_id WordPress user ID.
 * @param mixed $plan    ARM_Plan object or plan ID (unused; re-syncs from user meta).
 */
function arsenal_settings_on_arm_after_cancel_subscription( $user_id, $plan ) {
	arsenal_settings_on_armember_plan_removed( $user_id );
}

/**
 * @param int   $user_id     WordPress user ID.
 * @param array $member_data Member form data (unused; re-syncs from user meta).
 */
function arsenal_settings_on_arm_after_update_user_profile( $user_id, $member_data ) {
	unset( $member_data );
	arsenal_settings_on_armember_plan_removed( $user_id );
	arsenal_settings_update_renewal_date_meta( $user_id, 0 );
}

add_action( 'arm_after_user_plan_change', 'arsenal_settings_on_armember_plan_assigned', 20, 2 );
add_action( 'arm_after_user_plan_change_by_admin', 'arsenal_settings_on_armember_plan_assigned', 20, 2 );
add_action( 'arm_after_cancel_subscription', 'arsenal_settings_on_arm_after_cancel_subscription', 99, 2 );
add_action( 'arm_after_update_user_profile', 'arsenal_settings_on_arm_after_update_user_profile', 99, 2 );
add_action( 'added_user_meta', 'arsenal_settings_on_arm_user_plan_meta_changed', 10, 4 );
add_action( 'updated_user_meta', 'arsenal_settings_on_arm_user_plan_meta_changed', 10, 4 );
add_action( 'added_user_meta', 'arsenal_settings_on_arm_user_suspended_plan_ids_changed', 10, 4 );
add_action( 'updated_user_meta', 'arsenal_settings_on_arm_user_suspended_plan_ids_changed', 10, 4 );
add_action( 'arm_after_recurring_payment_success_outside', 'arsenal_settings_on_arm_after_recurring_payment_success_sync_renewal_date', 25, 5 );

/**
 * Save `selected_payment_method` and `selected_payment_type` as soon as the member chooses a
 * gateway and billing mode on the ARMember setup form.
 *
 * @param string               $payment_gateway         Gateway slug (stripe, paypal, woocommerce, …).
 * @param array<string, mixed> $payment_gateway_options Gateway options (unused).
 * @param array<string, mixed> $posted_data             Posted signup data.
 * @param int                  $entry_id                ARMember entry ID (unused).
 */
function arsenal_settings_save_selected_payment_method_on_gateway_setup( $payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0 ) {
	unset( $payment_gateway_options, $entry_id );

	$user_id = 0;
	if ( is_array( $posted_data ) ) {
		if ( ! empty( $posted_data['user_id'] ) ) {
			$user_id = (int) $posted_data['user_id'];
		} elseif ( ! empty( $posted_data['arm_user_id'] ) ) {
			$user_id = (int) $posted_data['arm_user_id'];
		}
	}
	if ( $user_id < 1 && is_user_logged_in() ) {
		$user_id = (int) get_current_user_id();
	}
	if ( $user_id < 1 ) {
		return;
	}

	arsenal_settings_update_selected_payment_method_meta( $user_id, 0, $payment_gateway );

	$selected_plan_id = 0;
	if ( is_array( $posted_data ) ) {
		if ( ! empty( $posted_data['subscription_plan'] ) ) {
			$selected_plan_id = (int) $posted_data['subscription_plan'];
		} elseif ( ! empty( $posted_data['_subscription_plan'] ) ) {
			$selected_plan_id = (int) $posted_data['_subscription_plan'];
		}
	}
	arsenal_settings_update_selected_payment_type_meta( $user_id, $selected_plan_id );

	// Payment just started — keep recent status as pending until a transaction settles.
	arsenal_settings_update_custom_recent_payment_status_meta(
		$user_id,
		arsenal_settings_custom_recent_payment_status_default()
	);
	arsenal_settings_update_user_membership_status_meta( $user_id );
}
add_action( 'arm_payment_gateway_validation_from_setup', 'arsenal_settings_save_selected_payment_method_on_gateway_setup', 15, 4 );

/**
 * Save `selected_payment_method` from a WooCommerce order's customer (membership / checkout flows).
 *
 * When the order came through ARMember's WooCommerce gateway, store Bank Direct Debit.
 * Otherwise store the order payment method id (normalized).
 *
 * @param int $order_id WooCommerce order ID.
 */
function arsenal_settings_save_selected_payment_method_from_wc_order( $order_id ) {
	$order_id = (int) $order_id;
	if ( $order_id < 1 || ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return;
	}

	$user_id = (int) $order->get_user_id();
	if ( $user_id < 1 ) {
		return;
	}

	$gateway = 'woocommerce';
	$arm_entry = $order->get_meta( 'arm_entry_id' );
	if ( empty( $arm_entry ) ) {
		$arm_entry = $order->get_meta( '_arm_entry_id' );
	}
	// Non-ARMember checkout: still record the chosen WC payment method.
	if ( empty( $arm_entry ) ) {
		$wc_method = $order->get_payment_method();
		if ( is_string( $wc_method ) && $wc_method !== '' ) {
			$gateway = $wc_method;
		}
	}

	arsenal_settings_update_selected_payment_method_meta( $user_id, 0, $gateway );
	arsenal_settings_update_custom_recent_payment_status_meta( $user_id, $order->get_status() );
}
add_action( 'woocommerce_checkout_order_processed', 'arsenal_settings_save_selected_payment_method_from_wc_order', 25, 1 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'arsenal_settings_save_selected_payment_method_from_wc_order', 25, 1 );
add_action( 'woocommerce_payment_complete', 'arsenal_settings_save_selected_payment_method_from_wc_order', 25, 1 );

/**
 * Keep `custom_recent_payment_status` in sync when a WooCommerce order status changes.
 *
 * @param int      $order_id WooCommerce order ID.
 * @param string   $from     Previous status (unused).
 * @param string   $to       New status.
 * @param WC_Order $order    Order object (optional).
 */
function arsenal_settings_save_custom_recent_payment_status_on_wc_status_change( $order_id, $from, $to, $order = null ) {
	unset( $from );

	$user_id = 0;
	if ( $order && is_a( $order, 'WC_Order' ) ) {
		$user_id = (int) $order->get_user_id();
	} elseif ( function_exists( 'wc_get_order' ) ) {
		$fetched = wc_get_order( (int) $order_id );
		if ( $fetched && is_a( $fetched, 'WC_Order' ) ) {
			$user_id = (int) $fetched->get_user_id();
		}
	}

	if ( $user_id < 1 ) {
		return;
	}

	arsenal_settings_update_custom_recent_payment_status_meta( $user_id, $to );
}
add_action( 'woocommerce_order_status_changed', 'arsenal_settings_save_custom_recent_payment_status_on_wc_status_change', 25, 4 );

/**
 * Mirror the ARMember phone meta to a dedicated formatted_phone_number key.
 *
 * @param int    $meta_id    Meta row ID (unused).
 * @param int    $user_id    WordPress user ID.
 * @param string $meta_key   User meta key.
 * @param mixed  $meta_value Meta value (unused; re-reads saved meta).
 */
function arsenal_settings_sync_formatted_phone_number_usermeta( $meta_id, $user_id, $meta_key, $meta_value ) {
	unset( $meta_id, $meta_value );

	if ( 'text_21wqp' !== $meta_key ) {
		return;
	}

	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$value = get_user_meta( $user_id, 'text_21wqp', true );
	update_user_meta( $user_id, 'formatted_phone_number', $value );
}

add_action( 'added_user_meta', 'arsenal_settings_sync_formatted_phone_number_usermeta', 10, 4 );
add_action( 'updated_user_meta', 'arsenal_settings_sync_formatted_phone_number_usermeta', 10, 4 );
