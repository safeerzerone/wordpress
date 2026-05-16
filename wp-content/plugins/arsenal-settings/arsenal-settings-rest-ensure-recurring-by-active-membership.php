<?php
/**
 * REST helpers and callback for ensure-recurring (route is registered in arsenal-settings.php).
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the WordPress user currently has an active (non-cancelled, non-expired) ARMember subscription for a plan.
 *
 * @param int $user_id WordPress user ID.
 * @param int $plan_id ARMember plan ID (arm_subscription_plan_id).
 * @return bool
 */
function arsenal_settings_user_has_active_armember_plan( $user_id, $plan_id ) {
	$user_id = (int) $user_id;
	$plan_id  = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 ) {
		return false;
	}

	$plan_ids = get_user_meta( $user_id, 'arm_user_plan_ids', true );
	$plan_ids = is_array( $plan_ids ) ? array_map( 'intval', $plan_ids ) : array();
	if ( ! in_array( $plan_id, $plan_ids, true ) ) {
		return false;
	}

	$suspended = get_user_meta( $user_id, 'arm_user_suspended_plan_ids', true );
	$suspended = is_array( $suspended ) ? array_map( 'intval', $suspended ) : array();
	if ( in_array( $plan_id, $suspended, true ) ) {
		return false;
	}

	$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
	if ( ! is_array( $plan_data ) || empty( $plan_data ) ) {
		return false;
	}

	if ( ! empty( $plan_data['arm_cencelled_plan'] ) && 'yes' === (string) $plan_data['arm_cencelled_plan'] ) {
		return false;
	}

	$expire = isset( $plan_data['arm_expire_plan'] ) ? $plan_data['arm_expire_plan'] : '';
	if ( $expire !== '' && $expire !== null && (int) $expire > 0 && (int) $expire < (int) current_time( 'timestamp' ) ) {
		return false;
	}

	return true;
}

/**
 * Stripe PaymentMethod `type` values treated as bank direct debit for ensure-recurring gating.
 *
 * @return array<int,string>
 */
function arsenal_settings_rest_direct_debit_stripe_payment_method_types() {
	$types = array( 'us_bank_account', 'sepa_debit', 'bacs_debit', 'au_becs_debit', 'acss_debit' );
	/**
	 * Filter which Stripe PaymentMethod types count as “direct debit” for ensure-recurring endpoints.
	 *
	 * @param array<int,string> $types Stripe `type` strings.
	 */
	return apply_filters( 'arsenal_settings_stripe_direct_debit_payment_method_types', $types );
}

/**
 * Whether a Stripe PaymentMethod type string is a direct-debit (bank) method.
 *
 * @param string $type Stripe PaymentMethod.type.
 * @return bool
 */
function arsenal_settings_stripe_payment_method_type_is_direct_debit( $type ) {
	$t = strtolower( trim( (string) $type ) );
	if ( $t === '' ) {
		return false;
	}
	return in_array( $t, arsenal_settings_rest_direct_debit_stripe_payment_method_types(), true );
}

/**
 * ARMember user-plan meta: paid via Stripe with auto-debit (off-session / mandate) mode.
 *
 * @param int $user_id WordPress user ID.
 * @param int $plan_id ARMember plan ID.
 * @return bool
 */
function arsenal_settings_user_armember_plan_is_stripe_auto_debit( $user_id, $plan_id ) {
	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 ) {
		return false;
	}
	$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
	if ( ! is_array( $plan_data ) ) {
		return false;
	}
	$gateway = isset( $plan_data['arm_user_gateway'] ) ? strtolower( trim( (string) $plan_data['arm_user_gateway'] ) ) : '';
	$mode    = isset( $plan_data['arm_payment_mode'] ) ? (string) $plan_data['arm_payment_mode'] : '';
	return 'stripe' === $gateway && 'auto_debit_subscription' === $mode;
}

/**
 * Load Stripe PaymentMethod by id and return its `type` (or empty on failure).
 *
 * @param string $pm_id pm_….
 * @return string|WP_Error Type string or empty string; WP_Error on transport/API failure.
 */
function arsenal_settings_stripe_get_payment_method_type( $pm_id ) {
	$pm_id = trim( (string) $pm_id );
	if ( ! preg_match( '/^pm_[a-zA-Z0-9]+$/', $pm_id ) ) {
		return '';
	}
	$row = arsenal_settings_stripe_api_get( 'payment_methods/' . rawurlencode( $pm_id ) );
	if ( is_wp_error( $row ) ) {
		return $row;
	}
	if ( ! is_array( $row ) || empty( $row['type'] ) ) {
		return '';
	}
	return (string) $row['type'];
}

/**
 * Default PaymentMethod on the Stripe customer must be a direct-debit type for ensure-recurring to proceed.
 *
 * @param string $customer_id cus_….
 * @return array{ payment_method_id: string, type: string }|WP_Error Empty strings when no default pm; WP_Error on Stripe failure.
 */
function arsenal_settings_stripe_get_customer_default_direct_debit_context( $customer_id ) {
	if ( ! preg_match( '/^cus_[a-zA-Z0-9]+$/', (string) $customer_id ) ) {
		return array(
			'payment_method_id' => '',
			'type'                => '',
		);
	}
	$pm_id = arsenal_settings_stripe_get_customer_default_payment_method_id( (string) $customer_id );
	if ( $pm_id === '' ) {
		return array(
			'payment_method_id' => '',
			'type'                => '',
		);
	}
	$ptype = arsenal_settings_stripe_get_payment_method_type( $pm_id );
	if ( is_wp_error( $ptype ) ) {
		return $ptype;
	}
	return array(
		'payment_method_id' => $pm_id,
		'type'              => $ptype,
	);
}

/**
 * Compare Stripe subscription line items to ARMember-resolved inline price (currency, amount, interval).
 *
 * @param array $subscription Full Stripe subscription object (items.data.price expanded).
 * @param array $inline       Resolved inline from arsenal_settings_rest_resolve_armember_plan_for_stripe_inline().
 * @return bool
 */
function arsenal_settings_stripe_subscription_matches_armember_inline( array $subscription, array $inline ) {
	$want_currency = isset( $inline['currency'] ) ? strtolower( (string) $inline['currency'] ) : '';
	$want_amount   = isset( $inline['unit_amount'] ) ? (int) $inline['unit_amount'] : 0;
	$want_interval = isset( $inline['interval'] ) ? strtolower( (string) $inline['interval'] ) : '';
	$want_ic       = isset( $inline['interval_count'] ) ? max( 1, (int) $inline['interval_count'] ) : 1;

	if ( $want_currency === '' || $want_amount < 1 || $want_interval === '' ) {
		return false;
	}

	if ( empty( $subscription['items']['data'] ) || ! is_array( $subscription['items']['data'] ) ) {
		return false;
	}

	foreach ( $subscription['items']['data'] as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$price = isset( $item['price'] ) && is_array( $item['price'] ) ? $item['price'] : array();
		if ( empty( $price ) ) {
			continue;
		}
		$cur = isset( $price['currency'] ) ? strtolower( (string) $price['currency'] ) : '';
		$ua  = isset( $price['unit_amount'] ) ? (int) $price['unit_amount'] : 0;
		if ( $cur !== $want_currency || $ua < 1 || abs( $ua - $want_amount ) > 1 ) {
			continue;
		}
		$rec = isset( $price['recurring'] ) && is_array( $price['recurring'] ) ? $price['recurring'] : array();
		if ( empty( $rec ) ) {
			continue;
		}
		$iv = isset( $rec['interval'] ) ? strtolower( (string) $rec['interval'] ) : '';
		$ic = isset( $rec['interval_count'] ) ? max( 1, (int) $rec['interval_count'] ) : 1;
		if ( $iv === $want_interval && $ic === $want_ic ) {
			return true;
		}
	}

	return false;
}

/**
 * Find a non-ended Stripe subscription whose recurring price matches the ARMember plan inline spec.
 *
 * @param string $customer_id Stripe customer id cus_….
 * @param array  $inline      Inline price spec from ARMember resolution.
 * @return string|null Subscription id sub_… or null.
 */
function arsenal_settings_stripe_find_matching_subscription_for_inline( $customer_id, array $inline ) {
	$list = arsenal_settings_stripe_list_subscriptions_for_customer( (string) $customer_id, true );
	if ( is_wp_error( $list ) || ! is_array( $list ) ) {
		return null;
	}

	$active_like = array( 'active', 'trialing', 'past_due', 'unpaid', 'incomplete' );

	foreach ( $list as $row ) {
		if ( ! is_array( $row ) || empty( $row['id'] ) ) {
			continue;
		}
		$st = isset( $row['status'] ) ? (string) $row['status'] : '';
		if ( ! in_array( $st, $active_like, true ) ) {
			continue;
		}
		$full = arsenal_settings_stripe_get_subscription(
			(string) $row['id'],
			array( 'items.data.price' )
		);
		if ( is_wp_error( $full ) || ! is_array( $full ) ) {
			continue;
		}
		if ( arsenal_settings_stripe_subscription_matches_armember_inline( $full, $inline ) ) {
			return (string) $full['id'];
		}
	}

	return null;
}

/**
 * REST callback: when the user has the given ARMember plan active, paid via Stripe auto-debit, and the Stripe customer’s
 * default payment method is a bank direct-debit type, ensure a matching Stripe recurring subscription exists (deferred first
 * charge); otherwise no-op.
 *
 * Same optional timing fields as …/create-recurring-subscription-by-armember-plan-deferred. Accepts GET, POST, or JSON body
 * (merged the same way as the deferred endpoint).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_ensure_recurring_subscription_by_active_armember_plan( WP_REST_Request $request ) {
	arsenal_settings_api_process_log(
		'callback_enter',
		array( 'callback' => 'arsenal_settings_rest_ensure_recurring_subscription_by_active_armember_plan' )
	);

	arsenal_settings_rest_merge_deferred_armember_plan_request_params( $request );

	$email = trim( (string) $request->get_param( 'customer_email' ) );
	if ( ! is_email( $email ) ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'Provide a valid customer_email.', 'arsenal-settings' ),
				'status'  => false,
				'code'    => 'invalid_customer_email',
			),
			400
		);
	}

	$plan_id = (int) $request->get_param( 'armember_plan_id' );
	if ( $plan_id < 1 ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'Provide a valid armember_plan_id (ARMember subscription plan id).', 'arsenal-settings' ),
				'status'  => false,
				'code'    => 'invalid_armember_plan_id',
			),
			400
		);
	}

	$payment_cycle = $request->get_param( 'payment_cycle' );
	if ( null === $payment_cycle || '' === $payment_cycle ) {
		$payment_cycle = 0;
	}
	$payment_cycle = max( 0, (int) $payment_cycle );

	$user = get_user_by( 'email', $email );
	if ( ! $user || empty( $user->ID ) ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'No WordPress user exists for that email; nothing to do.', 'arsenal-settings' ),
				'status'  => true,
				'code'    => 'skipped_no_wp_user',
				'action'  => 'skipped',
			),
			200
		);
	}

	if ( ! arsenal_settings_user_has_active_armember_plan( (int) $user->ID, $plan_id ) ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'The user does not have an active membership for that plan; nothing to do.', 'arsenal-settings' ),
				'status'  => true,
				'code'    => 'skipped_no_active_membership',
				'action'  => 'skipped',
			),
			200
		);
	}

	if ( ! arsenal_settings_user_armember_plan_is_stripe_auto_debit( (int) $user->ID, $plan_id ) ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'The active plan is not recorded as Stripe auto-debit (direct debit) in ARMember; nothing to do.', 'arsenal-settings' ),
				'status'  => true,
				'code'    => 'skipped_not_stripe_auto_debit_plan',
				'action'  => 'skipped',
			),
			200
		);
	}

	$customer_res = arsenal_settings_rest_get_stripe_customer_id( '', $email );
	if ( is_wp_error( $customer_res ) ) {
		return arsenal_settings_rest_from_wp_error( $customer_res );
	}
	$customer_id = $customer_res;

	$dd_ctx = arsenal_settings_stripe_get_customer_default_direct_debit_context( $customer_id );
	if ( is_wp_error( $dd_ctx ) ) {
		return arsenal_settings_rest_from_wp_error( $dd_ctx );
	}
	$pm_type = isset( $dd_ctx['type'] ) ? (string) $dd_ctx['type'] : '';
	if ( empty( $dd_ctx['payment_method_id'] ) || ! arsenal_settings_stripe_payment_method_type_is_direct_debit( $pm_type ) ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'The Stripe customer has no default payment method, or it is not a bank direct-debit type (e.g. BACS, SEPA, US bank account); nothing to do.', 'arsenal-settings' ),
				'status'  => true,
				'code'    => 'skipped_stripe_default_not_direct_debit',
				'action'  => 'skipped',
				'customer_id' => $customer_id,
				'default_payment_method_id' => isset( $dd_ctx['payment_method_id'] ) ? (string) $dd_ctx['payment_method_id'] : '',
				'default_payment_method_type' => $pm_type,
				'direct_debit_types' => arsenal_settings_rest_direct_debit_stripe_payment_method_types(),
			),
			200
		);
	}

	$resolved = arsenal_settings_rest_resolve_armember_plan_for_stripe_inline( $plan_id, $payment_cycle );
	if ( is_wp_error( $resolved ) ) {
		return arsenal_settings_rest_from_wp_error( $resolved );
	}
	$inline = isset( $resolved['inline'] ) && is_array( $resolved['inline'] ) ? $resolved['inline'] : array();

	$matched = arsenal_settings_stripe_find_matching_subscription_for_inline( $customer_id, $inline );
	if ( is_string( $matched ) && $matched !== '' ) {
		return new WP_REST_Response(
			array(
				'message'          => __( 'A Stripe recurring subscription already matches this membership amount and billing interval.', 'arsenal-settings' ),
				'status'           => true,
				'code'             => 'already_matched',
				'action'           => 'none',
				'subscription_id'  => $matched,
				'customer_id'      => $customer_id,
				'expected_inline'  => $inline,
				'stripe_direct_debit_payment_method_id'   => isset( $dd_ctx['payment_method_id'] ) ? (string) $dd_ctx['payment_method_id'] : '',
				'stripe_direct_debit_payment_method_type' => $pm_type,
			),
			200
		);
	}

	$inner = new WP_REST_Request( $request->get_method(), $request->get_route() );
	foreach ( array(
		'customer_email',
		'armember_plan_id',
		'payment_cycle',
		'quantity',
		'defer_first_billing_period',
		'default_payment_method',
		'payment_behavior',
		'billing_cycle_anchor',
		'trial_period_days',
	) as $key ) {
		if ( $request->has_param( $key ) ) {
			$inner->set_param( $key, $request->get_param( $key ) );
		}
	}
	$inner->set_param( 'customer_email', $email );
	$inner->set_param( 'armember_plan_id', $plan_id );
	$inner->set_param( 'payment_cycle', $payment_cycle );

	$created = arsenal_settings_rest_create_recurring_subscription_by_armember_plan_deferred( $inner );
	if ( $created instanceof WP_REST_Response ) {
		$data = $created->get_data();
		if ( is_array( $data ) ) {
			$data['ensure_source'] = 'ensure-recurring-subscription-by-active-armember-plan';
			$data['action']         = ! empty( $data['status'] ) ? 'created' : 'create_failed';
			$data['stripe_direct_debit_payment_method_id']   = isset( $dd_ctx['payment_method_id'] ) ? (string) $dd_ctx['payment_method_id'] : '';
			$data['stripe_direct_debit_payment_method_type'] = $pm_type;
			$created->set_data( $data );
		}
	}
	return $created;
}
