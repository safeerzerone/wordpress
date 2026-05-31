<?php
/**
 * WooCommerce checkout: Stripe direct debit — hide email in Payment Element; use logged-in customer email.
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce gateway IDs for Stripe bank direct-debit methods at checkout.
 *
 * @return string[]
 */
function arsenal_settings_wc_stripe_dd_checkout_payment_method_ids() {
	return apply_filters(
		'arsenal_settings_wc_stripe_dd_checkout_payment_method_ids',
		array(
			'stripe_bacs_debit',
			'stripe_sepa_debit',
			'stripe_us_bank_account',
			'stripe_au_becs_debit',
			'stripe_acss_debit',
		)
	);
}

/**
 * Whether the post-registration checkout email hide logic should run.
 *
 * @return bool
 */
function arsenal_settings_wc_stripe_dd_checkout_should_apply() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	return arsenal_settings_wc_stripe_dd_checkout_customer_email() !== '';
}

/**
 * Billing email for the current checkout customer (WooCommerce session / logged-in user).
 *
 * @return string Sanitized email or empty when unavailable.
 */
function arsenal_settings_wc_stripe_dd_checkout_customer_email() {
	$email = '';

	if ( function_exists( 'WC' ) && WC()->customer ) {
		$email = sanitize_email( (string) WC()->customer->get_billing_email() );
	}

	if ( $email === '' && is_user_logged_in() ) {
		$user_id = get_current_user_id();
		$email   = sanitize_email( (string) get_user_meta( $user_id, 'billing_email', true ) );
		if ( $email === '' ) {
			$user  = wp_get_current_user();
			$email = sanitize_email( (string) $user->user_email );
		}
	}

	return (string) apply_filters( 'arsenal_settings_wc_stripe_dd_checkout_customer_email', $email );
}

/**
 * Billing name for the current checkout customer.
 *
 * @return string
 */
function arsenal_settings_wc_stripe_dd_checkout_customer_name() {
	$name = '';

	if ( function_exists( 'WC' ) && WC()->customer ) {
		$name = trim( (string) WC()->customer->get_billing_first_name() . ' ' . (string) WC()->customer->get_billing_last_name() );
	}

	if ( $name === '' && is_user_logged_in() ) {
		$user_id = get_current_user_id();
		$name    = trim(
			(string) get_user_meta( $user_id, 'billing_first_name', true ) . ' ' .
			(string) get_user_meta( $user_id, 'billing_last_name', true )
		);
	}

	return (string) apply_filters( 'arsenal_settings_wc_stripe_dd_checkout_customer_name', $name );
}

/**
 * After ARMember registration, copy the logged-in user's email into the WooCommerce customer session.
 */
function arsenal_settings_wc_stripe_dd_checkout_sync_customer_session() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->customer ) {
		return;
	}

	$email = arsenal_settings_wc_stripe_dd_checkout_customer_email();
	if ( $email === '' ) {
		return;
	}

	if ( WC()->customer->get_billing_email() !== $email ) {
		WC()->customer->set_billing_email( $email );
	}

	$name = arsenal_settings_wc_stripe_dd_checkout_customer_name();
	if ( $name !== '' ) {
		$parts = preg_split( '/\s+/', $name, 2 );
		if ( WC()->customer->get_billing_first_name() === '' && ! empty( $parts[0] ) ) {
			WC()->customer->set_billing_first_name( $parts[0] );
		}
		if ( WC()->customer->get_billing_last_name() === '' && ! empty( $parts[1] ) ) {
			WC()->customer->set_billing_last_name( $parts[1] );
		}
	}

	WC()->customer->save();
}
add_action( 'woocommerce_checkout_init', 'arsenal_settings_wc_stripe_dd_checkout_sync_customer_session', 5 );
add_action( 'template_redirect', 'arsenal_settings_wc_stripe_dd_checkout_sync_customer_session', 5 );

/**
 * Keep billing_email in the checkout DOM (hidden) so Stripe UPE treats it as collected on the WC form.
 *
 * @param array<string, array<string, mixed>> $fields Checkout fields.
 * @return array<string, array<string, mixed>>
 */
function arsenal_settings_wc_stripe_dd_checkout_billing_email_field( $fields ) {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || ! arsenal_settings_wc_stripe_dd_checkout_should_apply() ) {
		return $fields;
	}

	$email = arsenal_settings_wc_stripe_dd_checkout_customer_email();

	if ( ! isset( $fields['billing'] ) || ! is_array( $fields['billing'] ) ) {
		$fields['billing'] = array();
	}

	$fields['billing']['billing_email'] = array(
		'type'              => 'hidden',
		'label'             => __( 'Email address', 'woocommerce' ),
		'required'          => true,
		'class'             => array(),
		'validate'          => array( 'email' ),
		'autocomplete'      => 'email',
		'priority'          => 110,
		'default'           => $email,
		'custom_attributes' => array(
			'readonly' => 'readonly',
		),
	);

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'arsenal_settings_wc_stripe_dd_checkout_billing_email_field', 9999 );

/**
 * Stripe UPE: ensure billing_email is the only WC billing field marked "enabled" for Stripe when
 * checkout-2 has no other billing inputs. Other address/name fields stay on "auto" inside Stripe.
 *
 * @param array $params wc_stripe_upe_params.
 * @return array
 */
function arsenal_settings_wc_stripe_upe_params_use_session_email( $params ) {
	if ( empty( $params['isCheckout'] ) || ! arsenal_settings_wc_stripe_dd_checkout_should_apply() ) {
		return $params;
	}

	$email = arsenal_settings_wc_stripe_dd_checkout_customer_email();

	// Do not replace WooCommerce's full billing field list — that marks every field "never" and can
	// leave the direct debit Payment Element empty when checkout-2 has no visible billing fields.
	$params['enabledBillingFields'] = array( 'billing_email' );

	$name = arsenal_settings_wc_stripe_dd_checkout_customer_name();
	if ( $name !== '' ) {
		$params['customerBillingData'] = array(
			'name'  => $name,
			'email' => $email,
		);
	} else {
		$params['customerBillingData'] = array(
			'email' => $email,
		);
	}

	return $params;
}
add_filter( 'wc_stripe_upe_params', 'arsenal_settings_wc_stripe_upe_params_use_session_email', 20 );

/**
 * Enqueue checkout helper script for direct-debit payment methods.
 */
function arsenal_settings_wc_stripe_dd_checkout_enqueue_scripts() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	$script_path = __DIR__ . '/assets/arsenal-settings-wc-stripe-dd-checkout.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'arsenal-settings-wc-stripe-dd-checkout',
		plugins_url( 'assets/arsenal-settings-wc-stripe-dd-checkout.js', __FILE__ ),
		array( 'jquery', 'wc-checkout' ),
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'arsenal-settings-wc-stripe-dd-checkout',
		'arsenalSettingsWcStripeDdCheckout',
		array(
			'paymentMethodIds'     => arsenal_settings_wc_stripe_dd_checkout_payment_method_ids(),
			'customerEmail'        => arsenal_settings_wc_stripe_dd_checkout_customer_email(),
			'customerName'         => arsenal_settings_wc_stripe_dd_checkout_customer_name(),
			'billingEmailSelector' => '#billing_email',
			'emailRequiredMsg'     => __( 'Your account email is required to pay by direct debit. Please log in or contact support.', 'arsenal-settings' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'arsenal_settings_wc_stripe_dd_checkout_enqueue_scripts', 20 );
