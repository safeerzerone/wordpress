<?php
/**
 * Sync user meta for ARMember subscription plans:
 * - `current_arsenal_subscription` — reflects the member's active plan (cleared on cancel).
 * - `arsenal_active_plan` — last assigned/subscribed plan title (unchanged on cancel).
 * - `renewal_date` — next due or expiry from ARMember plan meta (`arm_next_due_payment`, else `arm_expire_plan`; Y-m-d).
 * - `selected_payment_method` — payment gateway chosen at signup/checkout (card, paypal, woocommerce, …).
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
 * Stripe card gateways are stored as `card` (user-facing label); other gateways keep their slug.
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
		'woocommerce'    => 'woocommerce',
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
	arsenal_settings_defer_renewal_date_meta_sync( $user_id, $normalized_plan_id );
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
add_action( 'arm_after_recurring_payment_success_outside', 'arsenal_settings_on_arm_after_recurring_payment_success_sync_renewal_date', 25, 5 );

/**
 * Save `selected_payment_method` as soon as the member chooses a gateway on the ARMember setup form.
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
}
add_action( 'arm_payment_gateway_validation_from_setup', 'arsenal_settings_save_selected_payment_method_on_gateway_setup', 15, 4 );

/**
 * Save `selected_payment_method` from a WooCommerce order's customer (membership / checkout flows).
 *
 * When the order came through ARMember's WooCommerce gateway, store `woocommerce`.
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
}
add_action( 'woocommerce_checkout_order_processed', 'arsenal_settings_save_selected_payment_method_from_wc_order', 25, 1 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'arsenal_settings_save_selected_payment_method_from_wc_order', 25, 1 );
add_action( 'woocommerce_payment_complete', 'arsenal_settings_save_selected_payment_method_from_wc_order', 25, 1 );

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
