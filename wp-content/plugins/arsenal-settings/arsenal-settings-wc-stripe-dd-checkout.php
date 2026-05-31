<?php
/**
 * WooCommerce checkout: Stripe direct debit — hide email/address in Payment Element; use session data.
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
 * Billing field keys kept in the checkout DOM (hidden) for Stripe UPE.
 *
 * @return string[]
 */
function arsenal_settings_wc_stripe_dd_checkout_hidden_field_keys() {
	return apply_filters(
		'arsenal_settings_wc_stripe_dd_checkout_hidden_field_keys',
		array(
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_country',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'billing_phone',
			'billing_email',
		)
	);
}

/**
 * Whether the post-registration checkout hide logic should run.
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
 * Fallback values when registration did not store billing fields Stripe still requires.
 *
 * @return array<string, string>
 */
function arsenal_settings_wc_stripe_dd_checkout_field_defaults() {
	return (array) apply_filters(
		'arsenal_settings_wc_stripe_dd_checkout_field_defaults',
		array(
			'billing_first_name' => 'Customer',
			'billing_last_name'  => 'Member',
			'billing_address_1'  => 'N/A',
			'billing_address_2'  => 'N/A',
			'billing_city'       => 'N/A',
			'billing_state'      => 'N/A',
			'billing_postcode'   => '000000',
			'billing_phone'      => '0000000000',
		)
	);
}

/**
 * Fallback postcode when registration did not store billing_postcode (Stripe requires a value).
 *
 * @return string
 */
function arsenal_settings_wc_stripe_dd_checkout_default_postcode() {
	$defaults = arsenal_settings_wc_stripe_dd_checkout_field_defaults();

	return (string) apply_filters(
		'arsenal_settings_wc_stripe_dd_checkout_default_postcode',
		isset( $defaults['billing_postcode'] ) ? (string) $defaults['billing_postcode'] : '000000'
	);
}

/**
 * Apply Stripe-safe placeholder values for empty hidden billing fields.
 *
 * @param array<string, string> $data Billing field values.
 * @return array<string, string>
 */
function arsenal_settings_wc_stripe_dd_checkout_apply_field_defaults( array $data ) {
	$defaults = arsenal_settings_wc_stripe_dd_checkout_field_defaults();

	if (
		is_user_logged_in()
		&& trim( (string) ( $data['billing_first_name'] ?? '' ) ) === ''
		&& trim( (string) ( $data['billing_last_name'] ?? '' ) ) === ''
	) {
		$user  = wp_get_current_user();
		$parts = preg_split( '/\s+/', trim( (string) $user->display_name ), 2 );
		if ( ! empty( $parts[0] ) ) {
			$data['billing_first_name'] = (string) $parts[0];
		}
		if ( ! empty( $parts[1] ) ) {
			$data['billing_last_name'] = (string) $parts[1];
		}
	}

	foreach ( $defaults as $key => $default ) {
		$default = trim( (string) $default );
		if ( $default === '' ) {
			continue;
		}
		if ( trim( (string) ( $data[ $key ] ?? '' ) ) === '' ) {
			$data[ $key ] = $default;
		}
	}

	return $data;
}

/**
 * Read one billing value from WC customer session or user meta.
 *
 * @param string $meta_key WooCommerce billing meta key (e.g. billing_country).
 * @return string
 */
function arsenal_settings_wc_stripe_dd_checkout_get_billing_value( $meta_key ) {
	$meta_key = (string) $meta_key;
	$value    = '';

	if ( function_exists( 'WC' ) && WC()->customer ) {
		$getter = 'get_' . $meta_key;
		if ( is_callable( array( WC()->customer, $getter ) ) ) {
			$value = (string) call_user_func( array( WC()->customer, $getter ) );
		}
	}

	if ( $value === '' && is_user_logged_in() ) {
		$value = (string) get_user_meta( get_current_user_id(), $meta_key, true );
	}

	if ( $value === '' && 'billing_email' === $meta_key && is_user_logged_in() ) {
		$user  = wp_get_current_user();
		$value = sanitize_email( (string) $user->user_email );
	}

	return (string) apply_filters( 'arsenal_settings_wc_stripe_dd_checkout_billing_value', $value, $meta_key );
}

/**
 * Billing details collected earlier (registration / ARMember) for the logged-in customer.
 *
 * @return array<string, string>
 */
function arsenal_settings_wc_stripe_dd_checkout_billing_data() {
	$data = array();

	foreach ( arsenal_settings_wc_stripe_dd_checkout_hidden_field_keys() as $key ) {
		$data[ $key ] = arsenal_settings_wc_stripe_dd_checkout_get_billing_value( $key );
	}

	$data = arsenal_settings_wc_stripe_dd_checkout_apply_field_defaults( $data );

	return (array) apply_filters( 'arsenal_settings_wc_stripe_dd_checkout_billing_data', $data );
}

/**
 * Billing email for the current checkout customer.
 *
 * @return string
 */
function arsenal_settings_wc_stripe_dd_checkout_customer_email() {
	return arsenal_settings_wc_stripe_dd_checkout_get_billing_value( 'billing_email' );
}

/**
 * Billing name for the current checkout customer.
 *
 * @return string
 */
function arsenal_settings_wc_stripe_dd_checkout_customer_name() {
	$first = arsenal_settings_wc_stripe_dd_checkout_get_billing_value( 'billing_first_name' );
	$last  = arsenal_settings_wc_stripe_dd_checkout_get_billing_value( 'billing_last_name' );

	return trim( $first . ' ' . $last );
}

/**
 * Stripe customerBillingData shape for wc_stripe_upe_params.
 *
 * @return array<string, mixed>
 */
function arsenal_settings_wc_stripe_dd_checkout_customer_billing_data() {
	$data  = arsenal_settings_wc_stripe_dd_checkout_billing_data();
	$email = isset( $data['billing_email'] ) ? sanitize_email( $data['billing_email'] ) : '';

	$payload = array(
		'name'  => trim( (string) ( $data['billing_first_name'] ?? '' ) . ' ' . (string) ( $data['billing_last_name'] ?? '' ) ),
		'email' => $email,
		'phone' => isset( $data['billing_phone'] ) ? (string) $data['billing_phone'] : '',
		'address' => array(
			'country'     => isset( $data['billing_country'] ) ? (string) $data['billing_country'] : '',
			'line1'       => isset( $data['billing_address_1'] ) ? (string) $data['billing_address_1'] : '',
			'line2'       => isset( $data['billing_address_2'] ) ? (string) $data['billing_address_2'] : '',
			'city'        => isset( $data['billing_city'] ) ? (string) $data['billing_city'] : '',
			'state'       => isset( $data['billing_state'] ) ? (string) $data['billing_state'] : '',
			'postal_code' => isset( $data['billing_postcode'] ) ? (string) $data['billing_postcode'] : '',
		),
	);

	return (array) apply_filters( 'arsenal_settings_wc_stripe_dd_checkout_customer_billing_data', $payload, $data );
}

/**
 * Copy known billing values into the WooCommerce customer session on checkout.
 */
function arsenal_settings_wc_stripe_dd_checkout_sync_customer_session() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->customer ) {
		return;
	}

	$billing = arsenal_settings_wc_stripe_dd_checkout_billing_data();
	$changed = false;

	foreach ( $billing as $key => $value ) {
		$value = is_string( $value ) ? $value : '';
		if ( $value === '' ) {
			continue;
		}

		$setter = 'set_' . $key;
		$getter = 'get_' . $key;
		if ( ! is_callable( array( WC()->customer, $setter ) ) || ! is_callable( array( WC()->customer, $getter ) ) ) {
			continue;
		}

		if ( (string) call_user_func( array( WC()->customer, $getter ) ) !== $value ) {
			call_user_func( array( WC()->customer, $setter ), $value );
			$changed = true;
		}
	}

	if ( $changed ) {
		WC()->customer->save();
	}
}
add_action( 'woocommerce_checkout_init', 'arsenal_settings_wc_stripe_dd_checkout_sync_customer_session', 5 );
add_action( 'template_redirect', 'arsenal_settings_wc_stripe_dd_checkout_sync_customer_session', 5 );

/**
 * Hidden billing fields on checkout so Stripe UPE treats them as collected on the WC form.
 *
 * @param array<string, array<string, mixed>> $fields Checkout fields.
 * @return array<string, array<string, mixed>>
 */
function arsenal_settings_wc_stripe_dd_checkout_hidden_billing_fields( $fields ) {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || ! arsenal_settings_wc_stripe_dd_checkout_should_apply() ) {
		return $fields;
	}

	$billing = arsenal_settings_wc_stripe_dd_checkout_billing_data();

	if ( ! isset( $fields['billing'] ) || ! is_array( $fields['billing'] ) ) {
		$fields['billing'] = array();
	}

	$hidden_defaults = array(
		'billing_first_name' => array(
			'label'        => __( 'First name', 'woocommerce' ),
			'autocomplete' => 'given-name',
			'priority'     => 10,
		),
		'billing_last_name'  => array(
			'label'        => __( 'Last name', 'woocommerce' ),
			'autocomplete' => 'family-name',
			'priority'     => 20,
		),
		'billing_company'    => array(
			'label'    => __( 'Company name', 'woocommerce' ),
			'priority' => 30,
		),
		'billing_country'    => array(
			'label'    => __( 'Country / Region', 'woocommerce' ),
			'type'     => 'country',
			'priority' => 40,
		),
		'billing_address_1'  => array(
			'label'        => __( 'Street address', 'woocommerce' ),
			'autocomplete' => 'address-line1',
			'priority'     => 50,
		),
		'billing_address_2'  => array(
			'label'        => __( 'Apartment, suite, unit, etc.', 'woocommerce' ),
			'autocomplete' => 'address-line2',
			'priority'     => 60,
		),
		'billing_city'       => array(
			'label'        => __( 'Town / City', 'woocommerce' ),
			'autocomplete' => 'address-level2',
			'priority'     => 70,
		),
		'billing_state'      => array(
			'label'        => __( 'State / County', 'woocommerce' ),
			'autocomplete' => 'address-level1',
			'priority'     => 80,
		),
		'billing_postcode'   => array(
			'label'        => __( 'Postcode / ZIP', 'woocommerce' ),
			'autocomplete' => 'postal-code',
			'priority'     => 90,
		),
		'billing_phone'      => array(
			'label'        => __( 'Phone', 'woocommerce' ),
			'type'         => 'tel',
			'autocomplete' => 'tel',
			'priority'     => 100,
		),
		'billing_email'      => array(
			'label'        => __( 'Email address', 'woocommerce' ),
			'validate'     => array( 'email' ),
			'autocomplete' => 'email',
			'priority'     => 110,
		),
	);

	foreach ( $hidden_defaults as $key => $config ) {
		$value = isset( $billing[ $key ] ) ? (string) $billing[ $key ] : '';

		$fields['billing'][ $key ] = array_merge(
			array(
				'type'              => 'hidden',
				'required'          => false,
				'class'             => array(),
				'default'           => $value,
				'custom_attributes' => array(
					'readonly' => 'readonly',
				),
			),
			$config
		);

		if ( 'billing_email' === $key ) {
			$fields['billing'][ $key ]['required'] = true;
		}

		if ( 'billing_country' === $key && $config['type'] === 'country' ) {
			// Country must remain type country so WooCommerce outputs the correct field name/id.
			$fields['billing'][ $key ]['type'] = 'hidden';
		}
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'arsenal_settings_wc_stripe_dd_checkout_hidden_billing_fields', 9999 );

/**
 * Stripe UPE: mark email + address as collected on the WC checkout form (hidden fields).
 *
 * @param array $params wc_stripe_upe_params.
 * @return array
 */
function arsenal_settings_wc_stripe_upe_params_use_session_billing( $params ) {
	if ( empty( $params['isCheckout'] ) || ! arsenal_settings_wc_stripe_dd_checkout_should_apply() ) {
		return $params;
	}

	$params['enabledBillingFields'] = arsenal_settings_wc_stripe_dd_checkout_hidden_field_keys();
	$params['customerBillingData']  = arsenal_settings_wc_stripe_dd_checkout_customer_billing_data();

	return $params;
}
add_filter( 'wc_stripe_upe_params', 'arsenal_settings_wc_stripe_upe_params_use_session_billing', 20 );

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
			'paymentMethodIds'   => arsenal_settings_wc_stripe_dd_checkout_payment_method_ids(),
			'billingData'        => arsenal_settings_wc_stripe_dd_checkout_billing_data(),
			'fieldDefaults'      => arsenal_settings_wc_stripe_dd_checkout_field_defaults(),
			'defaultPostcode'    => arsenal_settings_wc_stripe_dd_checkout_default_postcode(),
			'emailRequiredMsg'   => __( 'Your account email is required to pay by direct debit. Please log in or contact support.', 'arsenal-settings' ),
			'addressRequiredMsg' => __( 'Your billing address is required to pay by direct debit. Please update your profile or contact support.', 'arsenal-settings' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'arsenal_settings_wc_stripe_dd_checkout_enqueue_scripts', 20 );
