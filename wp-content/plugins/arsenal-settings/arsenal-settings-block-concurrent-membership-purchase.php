<?php
/**
 * Block a user from starting a second membership purchase while a first direct-debit
 * membership order is still being processed.
 *
 * Direct-debit (BACS, SEPA, US bank account, etc.) settlement is delayed: the WooCommerce
 * order sits in pending / on-hold and the ARMember plan is only assigned once payment is
 * confirmed. If the customer immediately submits another membership purchase, the two
 * assignment flows race and conflict. This guard rejects the new purchase while an
 * in-progress direct-debit membership order exists for the same user / email.
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce order statuses treated as an "in-progress" (not yet settled) membership purchase.
 *
 * @return string[]
 */
function arsenal_settings_block_concurrent_membership_in_progress_statuses() {
	return (array) apply_filters(
		'arsenal_settings_block_concurrent_membership_in_progress_statuses',
		array( 'pending', 'on-hold' )
	);
}

/**
 * Only consider in-progress orders created within this many days (bounds the query and acts as
 * a safety valve so an abandoned never-settled order does not block the customer forever).
 *
 * @return int
 */
function arsenal_settings_block_concurrent_membership_lookback_days() {
	return (int) apply_filters( 'arsenal_settings_block_concurrent_membership_lookback_days', 30 );
}

/**
 * Whether a blocking in-progress order must be paid via direct debit.
 *
 * Defaults to true: only delayed bank-debit orders create the settlement race. Card / instant
 * methods either complete immediately or fail, so an abandoned pending card order is ignored.
 *
 * @return bool
 */
function arsenal_settings_block_concurrent_membership_require_direct_debit() {
	return (bool) apply_filters( 'arsenal_settings_block_concurrent_membership_require_direct_debit', true );
}

/**
 * Log a block-concurrent-membership diagnostic event (api-YYYY-MM-DD.log).
 *
 * @param string               $message Short event name.
 * @param array<string, mixed> $extra   Context.
 */
function arsenal_settings_block_concurrent_membership_log( $message, array $extra = array() ) {
	if ( ! function_exists( 'arsenal_settings_api_process_log' ) ) {
		return;
	}

	arsenal_settings_api_process_log( 'block_concurrent_membership_' . (string) $message, $extra );
}

/**
 * Whether a WooCommerce order was paid (or is being paid) via a Stripe bank direct-debit method.
 *
 * Covers split gateway IDs (e.g. stripe_bacs_debit), legacy payment-method-type meta, and the
 * UPE `_stripe_upe_payment_type` meta that the active Stripe gateway stores.
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function arsenal_settings_block_concurrent_membership_order_is_direct_debit( $order ) {
	if ( function_exists( 'arsenal_settings_dd_failure_order_is_direct_debit' )
		&& arsenal_settings_dd_failure_order_is_direct_debit( $order ) ) {
		return true;
	}

	if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
		return false;
	}

	$dd_types = function_exists( 'arsenal_settings_dd_failure_debit_types' )
		? arsenal_settings_dd_failure_debit_types()
		: array( 'us_bank_account', 'sepa_debit', 'bacs_debit', 'au_becs_debit', 'acss_debit' );

	$upe_type = strtolower( trim( (string) $order->get_meta( '_stripe_upe_payment_type' ) ) );

	return $upe_type !== '' && in_array( $upe_type, $dd_types, true );
}

/**
 * Collect candidate in-progress order ids for a user id and/or billing email.
 *
 * @param int    $user_id WordPress user id (0 when unknown).
 * @param string $email   Billing email (empty when unknown).
 * @return int[]
 */
function arsenal_settings_block_concurrent_membership_collect_order_ids( $user_id, $email ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}

	$user_id = (int) $user_id;
	$email   = is_string( $email ) ? sanitize_email( $email ) : '';

	$base = array(
		'limit'   => 20,
		'orderby' => 'date',
		'order'   => 'DESC',
		'status'  => arsenal_settings_block_concurrent_membership_in_progress_statuses(),
		'return'  => 'ids',
	);

	$lookback_days = arsenal_settings_block_concurrent_membership_lookback_days();
	if ( $lookback_days > 0 ) {
		$base['date_created'] = '>' . gmdate( 'Y-m-d\TH:i:s', time() - $lookback_days * DAY_IN_SECONDS );
	}

	$ids = array();

	if ( $user_id > 0 ) {
		$query              = $base;
		$query['customer_id'] = $user_id;
		$result             = wc_get_orders( $query );
		if ( is_array( $result ) ) {
			$ids = array_merge( $ids, $result );
		}
	}

	if ( $email !== '' && is_email( $email ) ) {
		$query                 = $base;
		$query['billing_email'] = $email;
		$result                = wc_get_orders( $query );
		if ( is_array( $result ) ) {
			$ids = array_merge( $ids, $result );
		}
	}

	return array_values( array_unique( array_map( 'intval', $ids ) ) );
}

/**
 * Find an in-progress (unsettled) direct-debit membership order for the given user / email.
 *
 * @param int    $user_id          WordPress user id (0 when unknown).
 * @param string $email            Billing email (empty when unknown).
 * @param int    $exclude_order_id Order id to ignore (e.g. the order currently being placed).
 * @return int Blocking order id, or 0 when none.
 */
function arsenal_settings_find_in_progress_membership_order( $user_id, $email, $exclude_order_id = 0 ) {
	$exclude_order_id = (int) $exclude_order_id;
	$require_dd       = arsenal_settings_block_concurrent_membership_require_direct_debit();

	foreach ( arsenal_settings_block_concurrent_membership_collect_order_ids( $user_id, $email ) as $order_id ) {
		$order_id = (int) $order_id;
		if ( $order_id < 1 || $order_id === $exclude_order_id ) {
			continue;
		}

		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
		if ( ! $order ) {
			continue;
		}

		// Must be a membership order (maps to at least one ARMember plan).
		if ( ! function_exists( 'arsenal_settings_get_arm_plan_ids_for_wc_order' ) ) {
			continue;
		}
		$plan_ids = arsenal_settings_get_arm_plan_ids_for_wc_order( $order );
		if ( empty( $plan_ids ) ) {
			continue;
		}

		// The ARMember membership has already been assigned for this order: not in progress.
		if ( function_exists( 'arsenal_settings_arm_wc_defer_signup_membership_assigned_meta_key' )
			&& '1' === (string) $order->get_meta( arsenal_settings_arm_wc_defer_signup_membership_assigned_meta_key() ) ) {
			continue;
		}

		if ( $require_dd && ! arsenal_settings_block_concurrent_membership_order_is_direct_debit( $order ) ) {
			continue;
		}

		return $order_id;
	}

	return 0;
}

/**
 * Customer-facing message shown when a concurrent membership purchase is blocked.
 *
 * @param int $blocking_order_id Order id that is still processing.
 * @return string
 */
function arsenal_settings_block_concurrent_membership_message( $blocking_order_id = 0 ) {
	$message = __( 'You already have a membership purchase being processed. Bank direct-debit payments can take a few days to confirm. Please wait until that payment completes before purchasing another membership, or contact support if you need help.', 'arsenal-settings' );

	return (string) apply_filters( 'arsenal_settings_block_concurrent_membership_message', $message, (int) $blocking_order_id );
}

/**
 * Resolve the user id and email from the ARMember setup-form submission context.
 *
 * @param array<string, mixed> $posted_data Posted setup data.
 * @param int                  $entry_id    ARMember entry id.
 * @return array{0:int,1:string} [user_id, email]
 */
function arsenal_settings_block_concurrent_membership_identity_from_setup( array $posted_data, $entry_id ) {
	$user_id = 0;
	$email   = '';

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
		$user    = wp_get_current_user();
		if ( $user && is_email( $user->user_email ) ) {
			$email = sanitize_email( (string) $user->user_email );
		}
	}

	if ( $email === '' && ! empty( $posted_data['user_email'] ) && is_email( (string) $posted_data['user_email'] ) ) {
		$email = sanitize_email( (string) $posted_data['user_email'] );
	}

	if ( $email === '' ) {
		global $wpdb, $ARMember;
		$entry_id = (int) $entry_id;
		if ( $entry_id > 0 && is_object( $ARMember ) && ! empty( $ARMember->tbl_arm_entries ) ) {
			$entry = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT `arm_entry_email`, `arm_entry_value` FROM `' . $ARMember->tbl_arm_entries . '` WHERE `arm_entry_id` = %d',
					$entry_id
				),
				ARRAY_A
			);
			if ( ! empty( $entry['arm_entry_email'] ) && is_email( (string) $entry['arm_entry_email'] ) ) {
				$email = sanitize_email( (string) $entry['arm_entry_email'] );
			}
			if ( $email === '' && ! empty( $entry['arm_entry_value'] ) ) {
				$values = maybe_unserialize( $entry['arm_entry_value'] );
				if ( is_array( $values ) && ! empty( $values['user_email'] ) && is_email( (string) $values['user_email'] ) ) {
					$email = sanitize_email( (string) $values['user_email'] );
				}
			}
		}
	}

	return array( (int) $user_id, $email );
}

/**
 * Guard the ARMember setup-form submit: reject the new membership purchase (JSON error, matching
 * ARMember's gateway-action response shape) when an in-progress direct-debit membership exists.
 *
 * Runs before the deferred-signup handler so no cart/redirect is created for the blocked purchase.
 *
 * @param string               $payment_gateway         Gateway slug.
 * @param array<string, mixed> $payment_gateway_options Gateway options (unused).
 * @param array<string, mixed> $posted_data             Posted setup data.
 * @param int                  $entry_id                ARMember entry id.
 */
function arsenal_settings_block_concurrent_membership_guard_setup( $payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0 ) {
	unset( $payment_gateway_options );

	$applies_to = (array) apply_filters(
		'arsenal_settings_block_concurrent_membership_setup_gateways',
		array( 'woocommerce' )
	);
	if ( ! empty( $applies_to ) && ! in_array( (string) $payment_gateway, $applies_to, true ) ) {
		return;
	}

	$posted_data = is_array( $posted_data ) ? $posted_data : array();
	list( $user_id, $email ) = arsenal_settings_block_concurrent_membership_identity_from_setup( $posted_data, (int) $entry_id );

	if ( $user_id < 1 && $email === '' ) {
		return;
	}

	$blocking_order_id = arsenal_settings_find_in_progress_membership_order( $user_id, $email );
	if ( $blocking_order_id < 1 ) {
		return;
	}

	arsenal_settings_block_concurrent_membership_log(
		'blocked_setup',
		array(
			'user_id'           => $user_id,
			'email'             => $email,
			'entry_id'          => (int) $entry_id,
			'blocking_order_id' => $blocking_order_id,
			'gateway'           => (string) $payment_gateway,
		)
	);

	$err_msg  = esc_html( arsenal_settings_block_concurrent_membership_message( $blocking_order_id ) );
	$err_html = '<div class="arm_error_msg arm-df__fc--validation__wrap"><ul><li>' . $err_msg . '</li></ul></div>';

	wp_send_json(
		array(
			'status'  => 'error',
			'type'    => 'message',
			'message' => $err_html,
		)
	);
}
add_action( 'arm_payment_gateway_validation_from_setup', 'arsenal_settings_block_concurrent_membership_guard_setup', 5, 4 );

/**
 * Whether the current WooCommerce cart contains an ARMember membership product.
 *
 * @return bool
 */
function arsenal_settings_block_concurrent_membership_cart_has_membership() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( ! empty( $cart_item[ function_exists( 'arsenal_settings_arm_wc_defer_signup_cart_meta_key' ) ? arsenal_settings_arm_wc_defer_signup_cart_meta_key() : 'arsenal_arm_entry_id' ] ) ) {
			return true;
		}

		$product_id = 0;
		if ( ! empty( $cart_item['variation_id'] ) ) {
			$product_id = (int) $cart_item['variation_id'];
		} elseif ( ! empty( $cart_item['product_id'] ) ) {
			$product_id = (int) $cart_item['product_id'];
		}

		if ( $product_id > 0 && (int) get_post_meta( $product_id, '_arm_woocommerce_membership_plan', true ) > 0 ) {
			return true;
		}
	}

	return false;
}

/**
 * Safety-net guard at checkout: block placing a new membership order while an in-progress
 * direct-debit membership order exists (covers paths that bypass the ARMember setup form).
 */
function arsenal_settings_block_concurrent_membership_guard_checkout() {
	if ( ! function_exists( 'wc_add_notice' ) || ! arsenal_settings_block_concurrent_membership_cart_has_membership() ) {
		return;
	}

	$user_id = is_user_logged_in() ? get_current_user_id() : 0;
	$email   = '';

	if ( $user_id > 0 ) {
		$user = wp_get_current_user();
		if ( $user && is_email( $user->user_email ) ) {
			$email = sanitize_email( (string) $user->user_email );
		}
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Reading posted billing email only; WooCommerce verifies its own checkout nonce.
	if ( $email === '' && ! empty( $_POST['billing_email'] ) ) {
		$email = sanitize_email( wp_unslash( (string) $_POST['billing_email'] ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( $user_id < 1 && $email === '' ) {
		return;
	}

	$blocking_order_id = arsenal_settings_find_in_progress_membership_order( $user_id, $email );
	if ( $blocking_order_id < 1 ) {
		return;
	}

	arsenal_settings_block_concurrent_membership_log(
		'blocked_checkout',
		array(
			'user_id'           => $user_id,
			'email'             => $email,
			'blocking_order_id' => $blocking_order_id,
		)
	);

	wc_add_notice( arsenal_settings_block_concurrent_membership_message( $blocking_order_id ), 'error' );
}
add_action( 'woocommerce_checkout_process', 'arsenal_settings_block_concurrent_membership_guard_checkout', 5 );
