<?php
/**
 * Defer ARMember user registration until WooCommerce checkout is submitted (WooCommerce gateway signup).
 *
 * Creates the WordPress user at checkout submission so guest checkout AJAX keeps working.
 * Bank details are saved to Stripe via force-save + post-payment sync. ARMember plan assignment
 * runs when the order reaches a paid status.
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** WC session key for pending ARMember signup payload. */
function arsenal_settings_arm_wc_defer_signup_session_key() {
	return 'arsenal_arm_pending_signup';
}

/** Order meta: ARMember entry id for deferred signup. */
function arsenal_settings_arm_wc_defer_signup_entry_meta_key() {
	return 'arsenal_arm_entry_id';
}

/** Order meta: flag set after deferred user account is created. */
function arsenal_settings_arm_wc_defer_signup_created_meta_key() {
	return 'arsenal_arm_signup_user_created';
}

/** Order meta: flag set after ARMember membership is assigned for the order. */
function arsenal_settings_arm_wc_defer_signup_membership_assigned_meta_key() {
	return 'arsenal_arm_signup_membership_assigned';
}

/** Cart / order line item meta: ARMember entry id for deferred signup. */
function arsenal_settings_arm_wc_defer_signup_cart_meta_key() {
	return 'arsenal_arm_entry_id';
}

/** Order meta: marks checkout as deferred ARMember signup. */
function arsenal_settings_arm_wc_defer_signup_order_flag_meta_key() {
	return '_arsenal_deferred_wc_signup';
}

/**
 * Paid-like WooCommerce order statuses that should trigger deferred signup user creation.
 *
 * @return string[]
 */
function arsenal_settings_arm_wc_defer_signup_paid_statuses() {
	return apply_filters(
		'arsenal_settings_arm_wc_defer_signup_paid_statuses',
		array( 'processing', 'completed', 'wps_renewal' )
	);
}

/**
 * Log deferred signup diagnostics (api-YYYY-MM-DD.log).
 *
 * @param string               $message Short event name.
 * @param array<string, mixed> $extra   Context.
 */
function arsenal_settings_arm_wc_defer_signup_log( $message, array $extra = array() ) {
	if ( ! function_exists( 'arsenal_settings_api_process_log' ) ) {
		return;
	}

	arsenal_settings_api_process_log(
		'wc_defer_signup_' . (string) $message,
		$extra
	);
}

/**
 * Replace ARMember's WooCommerce gateway handler with the deferred-registration variant.
 */
function arsenal_settings_arm_wc_defer_signup_replace_arm_handler() {
	global $is_woocommerce_feature;

	if ( ! is_object( $is_woocommerce_feature ) || ! method_exists( $is_woocommerce_feature, 'arm2_payment_gateway_form_submit_action' ) ) {
		return;
	}

	remove_action( 'arm_payment_gateway_validation_from_setup', array( $is_woocommerce_feature, 'arm2_payment_gateway_form_submit_action' ), 10 );
	add_action( 'arm_payment_gateway_validation_from_setup', 'arsenal_settings_arm_wc_defer_signup_gateway_action', 10, 4 );
}
add_action( 'init', 'arsenal_settings_arm_wc_defer_signup_replace_arm_handler', 99 );

/**
 * Whether a pending deferred signup exists in the WooCommerce session.
 *
 * @return bool
 */
function arsenal_settings_arm_wc_defer_signup_has_pending_session() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return false;
	}

	$pending = WC()->session->get( arsenal_settings_arm_wc_defer_signup_session_key() );

	return is_array( $pending ) && ! empty( $pending['entry_id'] );
}

/**
 * Read pending deferred signup data from the WooCommerce session.
 *
 * @return array<string, mixed>
 */
function arsenal_settings_arm_wc_defer_signup_get_pending_session() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return array();
	}

	$pending = WC()->session->get( arsenal_settings_arm_wc_defer_signup_session_key() );

	return is_array( $pending ) ? $pending : array();
}

/**
 * Persist pending signup data for guest checkout (billing, tax, coupon, entry id).
 *
 * @param int                  $entry_id     ARMember entry id.
 * @param array<string, mixed> $entry_values Unserialized entry values.
 * @param array<string, mixed> $posted_data  Setup form submission.
 * @param int                  $product_id   Optional WooCommerce product id for cart restore.
 */
function arsenal_settings_arm_wc_defer_signup_store_pending_session( $entry_id, array $entry_values, array $posted_data, $product_id = 0 ) {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	WC()->session->set(
		arsenal_settings_arm_wc_defer_signup_session_key(),
		array(
			'entry_id'    => (int) $entry_id,
			'product_id'  => (int) $product_id,
			'billing'     => arsenal_settings_arm_wc_defer_signup_billing_from_entry_values( $entry_values ),
			'tax_amount'  => isset( $posted_data['arm_common_tax_amount'] ) ? $posted_data['arm_common_tax_amount'] : '',
			'coupon_code' => ! empty( $posted_data['arm_coupon_code'] ) ? (string) $posted_data['arm_coupon_code'] : '',
		)
	);
}

/**
 * Clear pending deferred signup session data.
 */
function arsenal_settings_arm_wc_defer_signup_clear_pending_session() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	WC()->session->set( arsenal_settings_arm_wc_defer_signup_session_key(), null );
}

/**
 * Map ARMember entry values to WooCommerce billing field keys.
 *
 * @param array<string, mixed> $values Entry values.
 * @return array<string, string>
 */
function arsenal_settings_arm_wc_defer_signup_billing_from_entry_values( array $values ) {
	$billing = array();

	$map = array(
		'billing_first_name' => 'first_name',
		'billing_last_name'  => 'last_name',
		'billing_email'      => 'user_email',
		'billing_phone'      => 'text_21wqp',
	);

	foreach ( $map as $billing_key => $entry_key ) {
		if ( ! empty( $values[ $entry_key ] ) ) {
			$billing[ $billing_key ] = sanitize_text_field( (string) $values[ $entry_key ] );
		}
	}

	return $billing;
}

/**
 * Copy ARMember WooCommerce gateway user meta that is normally set before checkout.
 *
 * @param int                  $user_id     WordPress user id.
 * @param int                  $entry_id    ARMember entry id.
 * @param array<string, mixed> $pending     Pending session payload.
 */
function arsenal_settings_arm_wc_defer_signup_apply_gateway_user_meta( $user_id, $entry_id, array $pending ) {
	$user_id  = (int) $user_id;
	$entry_id = (int) $entry_id;

	if ( $user_id < 1 || $entry_id < 1 ) {
		return;
	}

	update_user_meta( $user_id, 'arm_wooc_gateway_entry_id', $entry_id );
	update_user_meta( $user_id, 'arm_entry_id', $entry_id );

	if ( isset( $pending['tax_amount'] ) && $pending['tax_amount'] !== '' && $pending['tax_amount'] !== null ) {
		update_user_meta( $user_id, 'arm_wooc_gateway_tax_' . $entry_id, $pending['tax_amount'] );
	}

	if ( ! empty( $pending['coupon_code'] ) ) {
		update_user_meta( $user_id, 'arm_wooc_gateway_coupon_' . $entry_id, (string) $pending['coupon_code'] );
	}

	$billing = isset( $pending['billing'] ) && is_array( $pending['billing'] ) ? $pending['billing'] : array();

	if ( ! empty( $billing['billing_first_name'] ) ) {
		update_user_meta( $user_id, 'billing_first_name', $billing['billing_first_name'] );
	}
	if ( ! empty( $billing['billing_last_name'] ) ) {
		update_user_meta( $user_id, 'billing_last_name', $billing['billing_last_name'] );
	}
	if ( ! empty( $billing['billing_email'] ) ) {
		update_user_meta( $user_id, 'billing_email', sanitize_email( $billing['billing_email'] ) );
	}
	if ( ! empty( $billing['billing_phone'] ) ) {
		update_user_meta( $user_id, 'billing_phone', $billing['billing_phone'] );
	}
}

/**
 * WooCommerce gateway submit: add plan to cart and redirect without creating the user first.
 *
 * @param string               $payment_gateway         Gateway slug.
 * @param array<string, mixed> $payment_gateway_options Gateway options.
 * @param array<string, mixed> $posted_data             Posted setup data.
 * @param int                  $entry_id                ARMember entry id.
 */
function arsenal_settings_arm_wc_defer_signup_gateway_action( $payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0 ) {
	global $wpdb, $ARMember, $woocommerce, $arm_member_forms, $arm_payment_gateways, $is_woocommerce_feature;

	unset( $payment_gateway_options );

	if ( 'woocommerce' !== (string) $payment_gateway ) {
		return;
	}

	if ( ! is_object( $is_woocommerce_feature ) || empty( $is_woocommerce_feature->isWocommerceFeature ) || ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		return;
	}

	$arm_entry_id = isset( $posted_data['arm_entry_id'] ) ? (int) $posted_data['arm_entry_id'] : (int) $entry_id;
	$arm_product_id = (int) $is_woocommerce_feature->arm2_woo_product_find_product();

	if ( $arm_product_id < 1 ) {
		$err_msg  = esc_html__( 'Sorry, No Woocommerce product found for selected plan', 'ARMember' );
		$err_html = '<div class="arm_error_msg arm-df__fc--validation__wrap"><ul><li>' . $err_msg . '</li></ul></div>';
		wp_send_json(
			array(
				'status'  => 'error',
				'type'    => 'message',
				'message' => $err_html,
			)
		);
	}

	if ( 'publish' !== get_post_status( $arm_product_id ) ) {
		$arm_product_id = 0;
	}

	if ( $arm_product_id < 1 ) {
		$err_msg  = esc_html__( 'Sorry, No Woocommerce product found for selected plan', 'ARMember' );
		$err_html = '<div class="arm_error_msg arm-df__fc--validation__wrap"><ul><li>' . $err_msg . '</li></ul></div>';
		wp_send_json(
			array(
				'status'  => 'error',
				'type'    => 'message',
				'message' => $err_html,
			)
		);
	}

	$arm_user_id      = 0;
	$arm_user_login   = ! empty( $posted_data['user_login'] ) ? sanitize_user( (string) $posted_data['user_login'] ) : '';
	if ( $arm_user_login === '' && ! empty( $posted_data['user_email'] ) ) {
		$arm_user_login = sanitize_user( (string) $posted_data['user_email'] );
	}
	$defer_signup     = false;
	$entry_values     = array();
	$pending_session  = array();

	$entry_data = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT `arm_entry_id`, `arm_entry_email`, `arm_entry_value`, `arm_form_id`, `arm_user_id`, `arm_plan_id` FROM `' . $ARMember->tbl_arm_entries . '` WHERE `arm_entry_id`=%d',
			$arm_entry_id
		),
		ARRAY_A
	);

	if ( ! empty( $entry_data ) ) {
		$entry_values = maybe_unserialize( $entry_data['arm_entry_value'] );
		$entry_values = is_array( $entry_values ) ? $entry_values : array();

		if ( $arm_user_login === '' && ! empty( $entry_values['user_login'] ) ) {
			$arm_user_login = sanitize_user( (string) $entry_values['user_login'] );
		}
		if ( $arm_user_login === '' && ! empty( $entry_values['user_email'] ) ) {
			$arm_user_login = sanitize_user( (string) $entry_values['user_email'] );
		}
	}

	if ( is_user_logged_in() ) {
		$arm_user_id = get_current_user_id();
	} elseif ( ! empty( $arm_user_login ) && username_exists( $arm_user_login ) ) {
		$arm_user_obj = get_user_by( 'login', $arm_user_login );
		$arm_user_id  = ( $arm_user_obj && ! empty( $arm_user_obj->ID ) ) ? (int) $arm_user_obj->ID : 0;
	} elseif ( ! empty( $entry_data ) ) {
		if ( ! empty( $entry_values['subscription_plan'] ) ) {
			unset( $entry_values['subscription_plan'] );
		}
		if ( ! empty( $entry_values['_subscription_plan'] ) ) {
			unset( $entry_values['_subscription_plan'] );
		}

		$form_id = (int) $entry_data['arm_form_id'];
		$armform = new ARM_Form( 'id', $form_id );

		if ( in_array( $armform->type, array( 'registration' ), true ) ) {
			$defer_signup = true;
			arsenal_settings_arm_wc_defer_signup_store_pending_session( $arm_entry_id, $entry_values, $posted_data );
			$pending_session = arsenal_settings_arm_wc_defer_signup_get_pending_session();
		}
	}

	if ( $arm_user_id > 0 ) {
		update_user_meta( $arm_user_id, 'arm_wooc_gateway_entry_id', $arm_entry_id );

		if ( ! empty( $posted_data['arm_common_tax_amount'] ) ) {
			update_user_meta( $arm_user_id, 'arm_wooc_gateway_tax_' . $arm_entry_id, $posted_data['arm_common_tax_amount'] );
		}

		$arm_applied_coupon_code = ! empty( $posted_data['arm_coupon_code'] ) ? (string) $posted_data['arm_coupon_code'] : '';
		if ( $arm_applied_coupon_code !== '' ) {
			update_user_meta( $arm_user_id, 'arm_wooc_gateway_coupon_' . $arm_entry_id, $arm_applied_coupon_code );
		}
	}

	$wpdb->update(
		$ARMember->tbl_arm_entries,
		array( 'arm_user_id' => $arm_user_id ),
		array( 'arm_entry_id' => $arm_entry_id )
	);

	$woocommerce->cart->empty_cart();
	$cart_item_data = array();
	if ( $defer_signup ) {
		$cart_item_data[ arsenal_settings_arm_wc_defer_signup_cart_meta_key() ] = $arm_entry_id;
	}
	$woocommerce->cart->add_to_cart( $arm_product_id, 1, 0, array(), $cart_item_data );

	if ( $defer_signup ) {
		arsenal_settings_arm_wc_defer_signup_store_pending_session( $arm_entry_id, $entry_values, $posted_data, $arm_product_id );
		$pending_session = arsenal_settings_arm_wc_defer_signup_get_pending_session();
	}

	$arm_woocommerce_cart_key = '';
	foreach ( $woocommerce->cart->get_cart() as $arm_woo_val ) {
		$arm_woocommerce_cart_key = isset( $arm_woo_val['key'] ) ? (string) $arm_woo_val['key'] : '';
	}

	$arm_get_entry_data = $arm_payment_gateways->arm_get_entry_data_by_id( $arm_entry_id );
	$arm_entry_value    = ! empty( $arm_get_entry_data['arm_entry_value'] ) ? maybe_unserialize( $arm_get_entry_data['arm_entry_value'] ) : array();
	$arm_entry_value    = is_array( $arm_entry_value ) ? $arm_entry_value : array();
	$arm_entry_value['arm_woocommerce_gateway_cart_key'] = $arm_woocommerce_cart_key;

	if ( $defer_signup ) {
		$arm_entry_value['arsenal_deferred_wc_signup'] = 1;
	}

	$wpdb->update(
		$ARMember->tbl_arm_entries,
		array( 'arm_entry_value' => maybe_serialize( $arm_entry_value ) ),
		array( 'arm_entry_id' => $arm_entry_id )
	);

	if ( $defer_signup && function_exists( 'WC' ) && WC()->customer ) {
		$billing = isset( $pending_session['billing'] ) && is_array( $pending_session['billing'] ) ? $pending_session['billing'] : array();
		foreach ( $billing as $key => $value ) {
			$value = is_string( $value ) ? $value : '';
			if ( $value === '' ) {
				continue;
			}
			$setter = 'set_' . $key;
			if ( is_callable( array( WC()->customer, $setter ) ) ) {
				call_user_func( array( WC()->customer, $setter ), $value );
			}
		}
		WC()->customer->save();
	}

	$arm_woo_checkout_url      = wc_get_checkout_url();
	$arm_woo_redirect_checkout = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . esc_url( $arm_woo_checkout_url ) . '";</script>';

	wp_send_json(
		array(
			'status'  => 'success',
			'type'    => 'redirect',
			'message' => $arm_woo_redirect_checkout,
		)
	);
}

/**
 * Disable WooCommerce account creation during deferred-signup checkout.
 *
 * @param bool $enabled Whether registration is enabled at checkout.
 * @return bool
 */
function arsenal_settings_arm_wc_defer_signup_disable_wc_registration( $enabled ) {
	if ( arsenal_settings_arm_wc_defer_signup_has_pending_session() ) {
		return false;
	}

	return $enabled;
}
add_filter( 'woocommerce_checkout_registration_enabled', 'arsenal_settings_arm_wc_defer_signup_disable_wc_registration', 9999 );
add_filter( 'woocommerce_enable_signup_and_login_from_checkout', 'arsenal_settings_arm_wc_defer_signup_disable_wc_registration', 9999 );

/**
 * Copy deferred signup entry id from cart line item to the order (survives lost WC sessions).
 *
 * @param WC_Order_Item_Product $item          Order line item.
 * @param string                $cart_item_key Cart item key.
 * @param array<string, mixed>  $values        Cart item values.
 * @param WC_Order              $order         Order.
 */
function arsenal_settings_arm_wc_defer_signup_copy_entry_to_order_line_item( $item, $cart_item_key, $values, $order ) {
	unset( $cart_item_key );

	$meta_key = arsenal_settings_arm_wc_defer_signup_cart_meta_key();
	if ( empty( $values[ $meta_key ] ) || ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
		return;
	}

	$entry_id = (int) $values[ $meta_key ];
	if ( $entry_id < 1 ) {
		return;
	}

	if ( method_exists( $item, 'add_meta_data' ) ) {
		$item->add_meta_data( $meta_key, $entry_id, true );
	}

	$order->update_meta_data( arsenal_settings_arm_wc_defer_signup_entry_meta_key(), $entry_id );
	$order->update_meta_data( arsenal_settings_arm_wc_defer_signup_order_flag_meta_key(), '1' );
}
add_action( 'woocommerce_checkout_create_order_line_item', 'arsenal_settings_arm_wc_defer_signup_copy_entry_to_order_line_item', 10, 4 );

/**
 * Persist entry id on the order for post-payment user creation (session fallback).
 *
 * @param int $order_id Order id.
 */
function arsenal_settings_arm_wc_defer_signup_attach_entry_to_order( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! method_exists( $order, 'update_meta_data' ) ) {
		return;
	}

	$entry_id = (int) $order->get_meta( arsenal_settings_arm_wc_defer_signup_entry_meta_key() );
	if ( $entry_id < 1 ) {
		$pending = arsenal_settings_arm_wc_defer_signup_get_pending_session();
		if ( ! empty( $pending['entry_id'] ) ) {
			$entry_id = (int) $pending['entry_id'];
		}
	}

	if ( $entry_id < 1 ) {
		$entry_id = arsenal_settings_arm_wc_defer_signup_resolve_entry_id( $order );
	}

	if ( $entry_id < 1 ) {
		return;
	}

	$order->update_meta_data( arsenal_settings_arm_wc_defer_signup_entry_meta_key(), $entry_id );
	$order->update_meta_data( arsenal_settings_arm_wc_defer_signup_order_flag_meta_key(), '1' );
	$order->save();
}
add_action( 'woocommerce_checkout_order_processed', 'arsenal_settings_arm_wc_defer_signup_attach_entry_to_order', 5, 1 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'arsenal_settings_arm_wc_defer_signup_attach_entry_to_order', 5, 1 );

/**
 * Resolve ARMember entry id for an order with deferred signup.
 *
 * @param WC_Order $order Order.
 * @return int
 */
function arsenal_settings_arm_wc_defer_signup_resolve_entry_id( $order ) {
	if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
		return 0;
	}

	$entry_id = (int) $order->get_meta( arsenal_settings_arm_wc_defer_signup_entry_meta_key() );
	if ( $entry_id > 0 ) {
		return $entry_id;
	}

	if ( method_exists( $order, 'get_items' ) ) {
		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item ) || ! method_exists( $item, 'get_meta' ) ) {
				continue;
			}
			$item_entry_id = (int) $item->get_meta( arsenal_settings_arm_wc_defer_signup_cart_meta_key(), true );
			if ( $item_entry_id > 0 ) {
				return $item_entry_id;
			}
		}
	}

	global $wpdb, $ARMember;

	$email = method_exists( $order, 'get_billing_email' ) ? sanitize_email( (string) $order->get_billing_email() ) : '';
	$plan_ids = array();
	$mapped   = $order->get_meta( 'arm_mapped_order_product_plans' );
	if ( empty( $mapped ) ) {
		$mapped = get_post_meta( $order->get_id(), 'arm_mapped_order_product_plans', true );
	}
	$mapped = maybe_unserialize( $mapped );
	if ( is_array( $mapped ) ) {
		$plan_ids = array_filter( array_map( 'intval', $mapped ) );
	}

	if ( $email !== '' ) {
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT arm_entry_id, arm_entry_value, arm_plan_id FROM `' . $ARMember->tbl_arm_entries . '` WHERE arm_entry_email = %s AND arm_user_id = 0 ORDER BY arm_entry_id DESC LIMIT 1',
				$email
			),
			ARRAY_A
		);

		if ( ! empty( $row['arm_entry_id'] ) ) {
			$values = maybe_unserialize( $row['arm_entry_value'] );
			if ( is_array( $values ) && ( ! empty( $values['arm_woocommerce_gateway_cart_key'] ) || ! empty( $values['arsenal_deferred_wc_signup'] ) ) ) {
				if ( empty( $plan_ids ) || in_array( (int) $row['arm_plan_id'], $plan_ids, true ) ) {
					return (int) $row['arm_entry_id'];
				}
			}
		}

		// Billing email may differ from arm_entry_email; match on serialized entry payload.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT arm_entry_id, arm_entry_value, arm_plan_id FROM `' . $ARMember->tbl_arm_entries . '` WHERE arm_user_id = 0 AND arm_entry_value LIKE %s ORDER BY arm_entry_id DESC LIMIT 1',
				'%' . $wpdb->esc_like( $email ) . '%'
			),
			ARRAY_A
		);

		if ( ! empty( $row['arm_entry_id'] ) ) {
			$values = maybe_unserialize( $row['arm_entry_value'] );
			if ( is_array( $values ) && ( ! empty( $values['arm_woocommerce_gateway_cart_key'] ) || ! empty( $values['arsenal_deferred_wc_signup'] ) ) ) {
				if ( empty( $plan_ids ) || in_array( (int) $row['arm_plan_id'], $plan_ids, true ) ) {
					return (int) $row['arm_entry_id'];
				}
			}
		}
	}

	if ( ! empty( $plan_ids ) ) {
		foreach ( $plan_ids as $plan_id ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT arm_entry_id, arm_entry_value FROM `' . $ARMember->tbl_arm_entries . '` WHERE arm_plan_id = %d AND arm_user_id = 0 ORDER BY arm_entry_id DESC LIMIT 1',
					$plan_id
				),
				ARRAY_A
			);
			if ( empty( $row['arm_entry_id'] ) ) {
				continue;
			}
			$values = maybe_unserialize( $row['arm_entry_value'] );
			if ( is_array( $values ) && ( ! empty( $values['arm_woocommerce_gateway_cart_key'] ) || ! empty( $values['arsenal_deferred_wc_signup'] ) ) ) {
				return (int) $row['arm_entry_id'];
			}
		}
	}

	return 0;
}

/**
 * Whether an order belongs to a deferred ARMember WooCommerce signup checkout.
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function arsenal_settings_arm_wc_defer_signup_order_is_deferred( $order ) {
	if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
		return false;
	}

	if ( '1' === (string) $order->get_meta( arsenal_settings_arm_wc_defer_signup_order_flag_meta_key() ) ) {
		return true;
	}

	$mapped = $order->get_meta( 'arm_mapped_order_product_plans' );
	if ( empty( $mapped ) ) {
		$mapped = get_post_meta( $order->get_id(), 'arm_mapped_order_product_plans', true );
	}

	return ! empty( $mapped );
}

/**
 * Resolve ARMember entry id during checkout (session or cart), before an order exists.
 *
 * @return int
 */
function arsenal_settings_arm_wc_defer_signup_resolve_checkout_entry_id() {
	if ( arsenal_settings_arm_wc_defer_signup_has_pending_session() ) {
		$pending = arsenal_settings_arm_wc_defer_signup_get_pending_session();
		if ( ! empty( $pending['entry_id'] ) ) {
			return (int) $pending['entry_id'];
		}
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}

	$meta_key = arsenal_settings_arm_wc_defer_signup_cart_meta_key();
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( ! empty( $cart_item[ $meta_key ] ) ) {
			return (int) $cart_item[ $meta_key ];
		}
	}

	return 0;
}

/**
 * Whether checkout submission succeeded enough to create the deferred signup user.
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function arsenal_settings_arm_wc_defer_signup_checkout_submission_succeeded( $order ) {
	if ( ! is_object( $order ) || ! method_exists( $order, 'get_status' ) ) {
		return false;
	}

	return ! in_array(
		(string) $order->get_status(),
		array( 'failed', 'cancelled', 'trash', 'checkout-draft' ),
		true
	);
}

/**
 * Log the new member in immediately after deferred signup user creation.
 *
 * @param int $user_id WordPress user id.
 * @return bool
 */
function arsenal_settings_arm_wc_defer_signup_login_user( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 || is_user_logged_in() ) {
		return false;
	}

	$user = get_user_by( 'id', $user_id );
	if ( ! $user || empty( $user->user_login ) ) {
		return false;
	}

	wp_clear_auth_cookie();
	wp_set_current_user( $user_id, $user->user_login );
	wp_set_auth_cookie( $user_id, true );

	if ( function_exists( 'WC' ) ) {
		if ( WC()->session && is_callable( array( WC()->session, 'init_session_cookie' ) ) ) {
			WC()->session->init_session_cookie();
		}
		do_action( 'woocommerce_set_cart_cookies', true );
	}

	update_user_meta( $user_id, 'arm_last_login_date', current_time( 'mysql' ) );

	global $ARMember, $arm_login_from_registration;
	if ( is_object( $ARMember ) && method_exists( $ARMember, 'arm_get_ip_address' ) ) {
		update_user_meta( $user_id, 'arm_last_login_ip', $ARMember->arm_get_ip_address() );
	}

	$arm_login_from_registration = 1;
	do_action( 'wp_login', $user->user_login, $user );

	arsenal_settings_arm_wc_defer_signup_log(
		'user_logged_in',
		array(
			'user_id' => $user_id,
		)
	);

	return true;
}

/**
 * Register the deferred signup member from an ARMember entry (no order required).
 *
 * @param int                  $entry_id ARMember entry id.
 * @param array<string, mixed> $args     Optional: send_notification, context.
 * @return int User id or 0.
 */
function arsenal_settings_arm_wc_defer_signup_create_user_from_entry( $entry_id, array $args = array() ) {
	global $wpdb, $ARMember, $arm_member_forms;

	$args = wp_parse_args(
		$args,
		array(
			'send_notification' => true,
			'context'           => '',
		)
	);

	$entry_id = (int) $entry_id;
	if ( $entry_id < 1 || ! is_object( $ARMember ) ) {
		return 0;
	}

	$entry_data = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM `' . $ARMember->tbl_arm_entries . '` WHERE arm_entry_id = %d',
			$entry_id
		),
		ARRAY_A
	);

	if ( empty( $entry_data ) ) {
		return 0;
	}

	$entry_values = maybe_unserialize( $entry_data['arm_entry_value'] );
	$entry_values = is_array( $entry_values ) ? $entry_values : array();

	if ( empty( $entry_values['arsenal_deferred_wc_signup'] ) && empty( $entry_values['arm_woocommerce_gateway_cart_key'] ) ) {
		return 0;
	}

	$user_id     = (int) $entry_data['arm_user_id'];
	$created_now = false;

	if ( $user_id < 1 ) {
		$form_id = (int) $entry_data['arm_form_id'];
		$armform = new ARM_Form( 'id', $form_id );

		if ( ! in_array( $armform->type, array( 'registration' ), true ) ) {
			return 0;
		}

		if ( ! empty( $entry_values['subscription_plan'] ) ) {
			unset( $entry_values['subscription_plan'] );
		}
		if ( ! empty( $entry_values['_subscription_plan'] ) ) {
			unset( $entry_values['_subscription_plan'] );
		}

		$entry_values['arm_update_user_from_profile'] = 0;
		$entry_values['payment_done']                 = '1';
		$entry_values['arm_entry_id']                 = $entry_id;

		$user_id = $arm_member_forms->arm_register_new_member( $entry_values, $armform, '', 0 );
		if ( is_wp_error( $user_id ) ) {
			arsenal_settings_arm_wc_defer_signup_log(
				'register_failed',
				array(
					'entry_id' => $entry_id,
					'error'    => $user_id->get_error_message(),
					'context'  => (string) $args['context'],
				)
			);
			return 0;
		}
		if ( ! is_numeric( $user_id ) ) {
			return 0;
		}

		$user_id     = (int) $user_id;
		$created_now = true;

		$wpdb->update(
			$ARMember->tbl_arm_entries,
			array( 'arm_user_id' => $user_id ),
			array( 'arm_entry_id' => $entry_id )
		);

		$pending = arsenal_settings_arm_wc_defer_signup_billing_from_entry_values( $entry_values );
		$pending = array(
			'entry_id'    => $entry_id,
			'billing'     => $pending,
			'tax_amount'  => isset( $entry_values['arm_common_tax_amount'] ) ? $entry_values['arm_common_tax_amount'] : '',
			'coupon_code' => ! empty( $entry_values['arm_coupon_code'] ) ? (string) $entry_values['arm_coupon_code'] : '',
		);
		arsenal_settings_arm_wc_defer_signup_apply_gateway_user_meta( $user_id, $entry_id, $pending );

		if ( ! empty( $args['send_notification'] ) && function_exists( 'arm_new_user_notification' ) ) {
			arm_new_user_notification( $user_id );
			update_user_meta( $user_id, 'arsenal_defer_signup_welcome_sent', '1' );
		}
	}

	if ( $user_id > 0 && $created_now ) {
		arsenal_settings_arm_wc_defer_signup_log(
			'user_created',
			array(
				'entry_id'    => $entry_id,
				'user_id'     => $user_id,
				'created_now' => true,
				'context'     => (string) $args['context'],
			)
		);
	}

	return $user_id;
}

/**
 * Resolve the WooCommerce membership product id for a deferred signup entry.
 *
 * @param int $entry_id ARMember entry id.
 * @return int
 */
function arsenal_settings_arm_wc_defer_signup_product_id_for_entry( $entry_id ) {
	$entry_id = (int) $entry_id;
	if ( $entry_id < 1 ) {
		return 0;
	}

	$pending = arsenal_settings_arm_wc_defer_signup_get_pending_session();
	if ( ! empty( $pending['entry_id'] ) && (int) $pending['entry_id'] === $entry_id && ! empty( $pending['product_id'] ) ) {
		$product_id = (int) $pending['product_id'];
		if ( $product_id > 0 && 'publish' === get_post_status( $product_id ) ) {
			return $product_id;
		}
	}

	global $is_woocommerce_feature;
	if ( is_object( $is_woocommerce_feature ) && method_exists( $is_woocommerce_feature, 'arm2_woo_product_find_product' ) ) {
		$product_id = (int) $is_woocommerce_feature->arm2_woo_product_find_product();
		if ( $product_id > 0 && 'publish' === get_post_status( $product_id ) ) {
			return $product_id;
		}
	}

	return 0;
}

/**
 * Rebuild the checkout cart when session data was lost (e.g. after a failed login redirect).
 */
function arsenal_settings_arm_wc_defer_signup_maybe_restore_checkout_cart() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->is_empty() ) {
		return;
	}

	$entry_id = arsenal_settings_arm_wc_defer_signup_resolve_checkout_entry_id();
	if ( $entry_id < 1 ) {
		return;
	}

	$product_id = arsenal_settings_arm_wc_defer_signup_product_id_for_entry( $entry_id );
	if ( $product_id < 1 ) {
		return;
	}

	$cart_item_data = array(
		arsenal_settings_arm_wc_defer_signup_cart_meta_key() => $entry_id,
	);

	$added = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );
	if ( ! $added ) {
		return;
	}

	arsenal_settings_arm_wc_defer_signup_log(
		'cart_restored',
		array(
			'entry_id'   => $entry_id,
			'product_id' => $product_id,
		)
	);
}
add_action( 'woocommerce_cart_loaded_from_session', 'arsenal_settings_arm_wc_defer_signup_maybe_restore_checkout_cart', 99 );

/**
 * Create and log in the deferred signup user immediately before checkout is processed.
 *
 * Guest checkout must stay intact while Stripe UPE loads; login runs only on place order.
 *
 * @return int User id or 0.
 */
function arsenal_settings_arm_wc_defer_signup_ensure_checkout_user() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return 0;
	}

	arsenal_settings_arm_wc_defer_signup_maybe_restore_checkout_cart();

	if ( is_user_logged_in() ) {
		return get_current_user_id();
	}

	$entry_id = arsenal_settings_arm_wc_defer_signup_resolve_checkout_entry_id();
	if ( $entry_id < 1 ) {
		return 0;
	}

	if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() && is_callable( array( WC()->cart, 'set_session' ) ) ) {
		WC()->cart->set_session();
	}

	$user_id = arsenal_settings_arm_wc_defer_signup_create_user_from_entry(
		$entry_id,
		array(
			'send_notification' => false,
			'context'           => 'checkout_submit',
		)
	);

	if ( $user_id < 1 ) {
		return 0;
	}

	arsenal_settings_arm_wc_defer_signup_login_user( $user_id );

	return $user_id;
}
add_action( 'woocommerce_before_checkout_process', 'arsenal_settings_arm_wc_defer_signup_ensure_checkout_user', 1 );

/**
 * Assign ARMember membership for a deferred signup order once payment is confirmed.
 *
 * @param int $order_id Order id.
 */
function arsenal_settings_arm_wc_defer_signup_assign_membership_for_order( $order_id ) {
	global $is_woocommerce_feature;

	$order_id = (int) $order_id;
	if ( $order_id < 1 ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! arsenal_settings_arm_wc_defer_signup_order_is_deferred( $order ) ) {
		return;
	}

	if ( '1' === (string) $order->get_meta( arsenal_settings_arm_wc_defer_signup_membership_assigned_meta_key() ) ) {
		return;
	}

	if ( ! in_array( (string) $order->get_status(), arsenal_settings_arm_wc_defer_signup_paid_statuses(), true ) ) {
		return;
	}

	$user_id = (int) $order->get_customer_id();
	if ( $user_id < 1 ) {
		$user_id = arsenal_settings_arm_wc_defer_signup_create_user_for_order(
			$order_id,
			array(
				'auto_login'        => ! is_user_logged_in(),
				'assign_membership' => false,
				'context'           => 'membership_assign',
			)
		);
	}

	if ( $user_id < 1 ) {
		return;
	}

	if ( is_object( $is_woocommerce_feature ) && method_exists( $is_woocommerce_feature, 'arm_woocommerce_add_member' ) ) {
		$is_woocommerce_feature->arm_woocommerce_add_member( $order_id );
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->update_meta_data( arsenal_settings_arm_wc_defer_signup_membership_assigned_meta_key(), '1' );
			$order->save();
		}

		arsenal_settings_arm_wc_defer_signup_log(
			'membership_assigned',
			array(
				'order_id' => $order_id,
				'user_id'  => $user_id,
				'status'   => $order ? $order->get_status() : '',
			)
		);
	}
}

/**
 * Create the ARMember user for a deferred signup order and optionally log them in.
 *
 * @param int                  $order_id Order id.
 * @param array<string, mixed> $args     Optional: auto_login, assign_membership, context.
 * @return int User id or 0.
 */
function arsenal_settings_arm_wc_defer_signup_create_user_for_order( $order_id, array $args = array() ) {
	global $wpdb, $ARMember, $arm_member_forms;

	$args = wp_parse_args(
		$args,
		array(
			'auto_login'        => false,
			'assign_membership' => false,
			'context'           => '',
		)
	);

	$order_id = (int) $order_id;
	if ( $order_id < 1 ) {
		return 0;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return 0;
	}

	if ( ! arsenal_settings_arm_wc_defer_signup_order_is_deferred( $order ) ) {
		return (int) $order->get_customer_id();
	}

	if ( '1' === (string) $order->get_meta( arsenal_settings_arm_wc_defer_signup_created_meta_key() ) ) {
		$existing_user_id = (int) $order->get_customer_id();
		if ( $existing_user_id > 0 && ! empty( $args['auto_login'] ) ) {
			arsenal_settings_arm_wc_defer_signup_login_user( $existing_user_id );
		}
		if ( ! empty( $args['assign_membership'] ) ) {
			arsenal_settings_arm_wc_defer_signup_assign_membership_for_order( $order_id );
		}
		return $existing_user_id;
	}

	$entry_id = arsenal_settings_arm_wc_defer_signup_resolve_entry_id( $order );
	if ( $entry_id < 1 ) {
		arsenal_settings_arm_wc_defer_signup_log(
			'entry_not_found',
			array(
				'order_id' => $order_id,
				'status'   => $order->get_status(),
				'email'    => method_exists( $order, 'get_billing_email' ) ? (string) $order->get_billing_email() : '',
			)
		);
		return (int) $order->get_customer_id();
	}

	$entry_data = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM `' . $ARMember->tbl_arm_entries . '` WHERE arm_entry_id = %d',
			$entry_id
		),
		ARRAY_A
	);

	if ( empty( $entry_data ) ) {
		return (int) $order->get_customer_id();
	}

	$entry_values = maybe_unserialize( $entry_data['arm_entry_value'] );
	$entry_values = is_array( $entry_values ) ? $entry_values : array();

	if ( empty( $entry_values['arsenal_deferred_wc_signup'] ) && empty( $entry_values['arm_woocommerce_gateway_cart_key'] ) ) {
		return (int) $order->get_customer_id();
	}

	$existing_user_id = (int) $order->get_customer_id();
	if ( $existing_user_id > 0 && (int) $entry_data['arm_user_id'] === $existing_user_id ) {
		$order->update_meta_data( arsenal_settings_arm_wc_defer_signup_created_meta_key(), '1' );
		$order->save();
		return $existing_user_id;
	}

	$user_id       = (int) $entry_data['arm_user_id'];
	$created_now   = false;
	$had_user      = $user_id > 0;

	if ( $user_id < 1 ) {
		$user_id = arsenal_settings_arm_wc_defer_signup_create_user_from_entry(
			$entry_id,
			array(
				'send_notification' => true,
				'context'           => (string) $args['context'],
			)
		);
		$created_now = $user_id > 0;
	} elseif ( $had_user && ! get_user_meta( $user_id, 'arsenal_defer_signup_welcome_sent', true ) && function_exists( 'arm_new_user_notification' ) ) {
		arm_new_user_notification( $user_id );
		update_user_meta( $user_id, 'arsenal_defer_signup_welcome_sent', '1' );
	}

	if ( $user_id < 1 ) {
		return 0;
	}

	if ( (int) $order->get_customer_id() !== $user_id && method_exists( $order, 'set_customer_id' ) ) {
		$order->set_customer_id( $user_id );
	}

	$order->update_meta_data( arsenal_settings_arm_wc_defer_signup_entry_meta_key(), $entry_id );
	$order->update_meta_data( arsenal_settings_arm_wc_defer_signup_created_meta_key(), '1' );
	$order->save();

	arsenal_settings_arm_wc_defer_signup_clear_pending_session();

	if ( $created_now || ! $had_user ) {
		arsenal_settings_arm_wc_defer_signup_log(
			'user_created',
			array(
				'order_id'    => $order_id,
				'entry_id'    => $entry_id,
				'user_id'     => $user_id,
				'created_now' => $created_now,
				'context'     => (string) $args['context'],
			)
		);
	}

	if ( ! empty( $args['auto_login'] ) ) {
		arsenal_settings_arm_wc_defer_signup_login_user( $user_id );
	}

	if ( ! empty( $args['assign_membership'] ) ) {
		arsenal_settings_arm_wc_defer_signup_assign_membership_for_order( $order_id );
	}

	return $user_id;
}

/**
 * Create and log in the member as soon as checkout / payment form submission succeeds.
 *
 * @param int $order_id Order id.
 */
function arsenal_settings_arm_wc_defer_signup_on_checkout_processed( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! arsenal_settings_arm_wc_defer_signup_checkout_submission_succeeded( $order ) ) {
		return;
	}

	arsenal_settings_arm_wc_defer_signup_create_user_for_order(
		(int) $order_id,
		array(
			'auto_login'        => true,
			'assign_membership' => false,
			'context'           => 'checkout_submitted',
		)
	);
}
add_action( 'woocommerce_checkout_order_processed', 'arsenal_settings_arm_wc_defer_signup_on_checkout_processed', 20, 1 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'arsenal_settings_arm_wc_defer_signup_on_checkout_processed', 20, 1 );

/**
 * Thank-you page fallback: ensure the member is logged in after redirect.
 *
 * @param int $order_id Order id.
 */
function arsenal_settings_arm_wc_defer_signup_on_thankyou( $order_id ) {
	if ( is_user_logged_in() || (int) $order_id < 1 ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! arsenal_settings_arm_wc_defer_signup_order_is_deferred( $order ) ) {
		return;
	}

	arsenal_settings_arm_wc_defer_signup_create_user_for_order(
		(int) $order_id,
		array(
			'auto_login'        => true,
			'assign_membership' => false,
			'context'           => 'thankyou',
		)
	);
}
add_action( 'woocommerce_thankyou', 'arsenal_settings_arm_wc_defer_signup_on_thankyou', 5, 1 );

/**
 * Assign membership once payment is confirmed (user may already exist from checkout).
 *
 * @param int $order_id Order id.
 */
function arsenal_settings_arm_wc_defer_signup_on_payment_complete( $order_id ) {
	$user_id = arsenal_settings_arm_wc_defer_signup_create_user_for_order(
		(int) $order_id,
		array(
			'auto_login'        => ! is_user_logged_in(),
			'assign_membership' => false,
			'context'           => 'payment_confirmed',
		)
	);

	if ( $user_id > 0 && ! is_user_logged_in() ) {
		arsenal_settings_arm_wc_defer_signup_login_user( $user_id );
	}

	arsenal_settings_arm_wc_defer_signup_assign_membership_for_order( (int) $order_id );
}
add_action( 'woocommerce_payment_complete', 'arsenal_settings_arm_wc_defer_signup_on_payment_complete', 5, 1 );
add_action( 'woocommerce_order_status_completed', 'arsenal_settings_arm_wc_defer_signup_on_payment_complete', 5, 1 );
add_action( 'woocommerce_order_status_processing', 'arsenal_settings_arm_wc_defer_signup_on_payment_complete', 5, 1 );
add_action( 'woocommerce_order_status_wps_renewal', 'arsenal_settings_arm_wc_defer_signup_on_payment_complete', 5, 1 );

/**
 * Catch paid status transitions (e.g. Subscriptions For WooCommerce `wps_renewal`).
 *
 * @param int    $order_id Order id.
 * @param string $from     Previous status.
 * @param string $to       New status.
 * @param object $order    Order object.
 */
function arsenal_settings_arm_wc_defer_signup_on_status_changed( $order_id, $from, $to, $order ) {
	unset( $from, $order );

	if ( in_array( (string) $to, arsenal_settings_arm_wc_defer_signup_paid_statuses(), true ) ) {
		arsenal_settings_arm_wc_defer_signup_on_payment_complete( (int) $order_id );
	}
}
add_action( 'woocommerce_order_status_changed', 'arsenal_settings_arm_wc_defer_signup_on_status_changed', 5, 4 );

/**
 * Repair deferred signups for paid orders that completed before user creation ran.
 *
 * Intended for one-off admin / CLI use on affected orders.
 *
 * @param int $order_id Order id.
 * @return int User id or 0.
 */
function arsenal_settings_arm_wc_defer_signup_repair_order( $order_id ) {
	$user_id = arsenal_settings_arm_wc_defer_signup_create_user_for_order( (int) $order_id );

	if ( $user_id > 0 && function_exists( 'arsenal_settings_wc_stripe_dd_sync_order_payment_method_to_customer' ) ) {
		$order = wc_get_order( (int) $order_id );
		if ( $order ) {
			$order->delete_meta_data( '_arsenal_stripe_dd_default_pm_synced' );
			$order->save();
		}
		arsenal_settings_wc_stripe_dd_sync_order_payment_method_to_customer( (int) $order_id );
	}

	return $user_id;
}

/**
 * Expose pending signup billing to Stripe DD checkout helpers.
 *
 * @param string $value    Current billing value.
 * @param string $meta_key WooCommerce billing meta key.
 * @return string
 */
function arsenal_settings_arm_wc_defer_signup_checkout_billing_value( $value, $meta_key ) {
	if ( $value !== '' || ! arsenal_settings_arm_wc_defer_signup_has_pending_session() ) {
		return $value;
	}

	$pending = arsenal_settings_arm_wc_defer_signup_get_pending_session();
	$billing = isset( $pending['billing'] ) && is_array( $pending['billing'] ) ? $pending['billing'] : array();

	if ( isset( $billing[ $meta_key ] ) && $billing[ $meta_key ] !== '' ) {
		return (string) $billing[ $meta_key ];
	}

	return $value;
}
add_filter( 'arsenal_settings_wc_stripe_dd_checkout_billing_value', 'arsenal_settings_arm_wc_defer_signup_checkout_billing_value', 10, 2 );
