<?php
/**
 * Plugin Name: Arsenal settings
 * Description: Provides REST endpoints for Arsenal-related configuration and checks.
 * Version: 1.0.0
 * Author: Arsenal
 * Text Domain: arsenal-settings
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARSENAL_SETTINGS_VERSION', '1.0.0' );
define( 'ARSENAL_SETTINGS_REST_NAMESPACE', 'arsenal-settings/v1' );

/**
 * Stripe secret key (test or live sk_…).
 *
 * Priority: (1) value saved under Settings → Arsenal Stripe, (2) wp-config.php constant ARSENAL_STRIPE_SECRET_KEY if set,
 * (3) filter arsenal_stripe_secret_key (receives the value from previous steps).
 *
 * @return string
 */
function arsenal_settings_get_stripe_secret_key() {
	$saved = get_option( 'arsenal_settings_stripe_secret_key', '' );
	$key   = is_string( $saved ) ? trim( $saved ) : '';

	if ( $key === '' && defined( 'ARSENAL_STRIPE_SECRET_KEY' ) && constant( 'ARSENAL_STRIPE_SECRET_KEY' ) ) {
		$key = (string) constant( 'ARSENAL_STRIPE_SECRET_KEY' );
	}

	return (string) apply_filters( 'arsenal_stripe_secret_key', $key );
}

/**
 * Whether the configured Stripe secret key is a test (sk_test_…) key.
 *
 * @return bool
 */
function arsenal_settings_stripe_uses_test_mode() {
	$sk = arsenal_settings_get_stripe_secret_key();
	return (bool) preg_match( '/^sk_test_/', $sk );
}

/**
 * Map a full test card number (digits only) to a Stripe test token (tok_…).
 * Use with PaymentMethods API as card[token] so raw PAN is not sent to Stripe when raw card APIs are disabled.
 *
 * @param string $digits Card number, digits only.
 * @return string tok_… or empty when unknown / not in test mode.
 */
function arsenal_settings_stripe_test_token_for_card_digits( $digits ) {
	if ( ! arsenal_settings_stripe_uses_test_mode() ) {
		return '';
	}
	$digits = (string) $digits;
	$map     = array(
		'4242424242424242'       => 'tok_visa',
		'4000056655665556'       => 'tok_visa_debit',
		'5555555555554444'       => 'tok_mastercard',
		'2223003122003222'       => 'tok_visa',
		'5200828282828210'       => 'tok_mastercard',
		'5105105105105100'       => 'tok_mastercard_prepaid',
		'378282246310005'        => 'tok_amex',
		'371449635398431'        => 'tok_amex',
		'6011111111111117'       => 'tok_discover',
		'3056930009020004'       => 'tok_diners',
		'36227206271667'         => 'tok_diners',
		'3566111111111118'       => 'tok_jcb',
		'6200000000000005'       => 'tok_unionpay',
	);
	/**
	 * Add or override digit string => tok_* mappings for create-payment test flows.
	 *
	 * @param array<string,string> $map Card digits => Stripe test token id.
	 */
	$map = apply_filters( 'arsenal_settings_stripe_test_card_token_map', $map );
	return isset( $map[ $digits ] ) ? (string) $map[ $digits ] : '';
}

/**
 * Stripe GET v1/{path}.
 *
 * @param string $path Path after v1/ (no leading slash), may include query string.
 * @return array|WP_Error Decoded JSON object or error.
 */
function arsenal_settings_stripe_api_get( $path ) {
	$secret = arsenal_settings_get_stripe_secret_key();
	if ( '' === $secret ) {
		return new WP_Error(
			'stripe_config',
			__( 'Stripe secret key is not configured. Save it under Settings → Arsenal Stripe in the admin, or define ARSENAL_STRIPE_SECRET_KEY in wp-config.php, or use the arsenal_stripe_secret_key filter.', 'arsenal-settings' ),
			array( 'status' => 500 )
		);
	}

	$url      = 'https://api.stripe.com/v1/' . ltrim( $path, '/' );
	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $secret,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) ) {
		return new WP_Error(
			'stripe_invalid_response',
			__( 'Invalid response from Stripe.', 'arsenal-settings' ),
			array( 'status' => 502 )
		);
	}

	if ( $code >= 400 ) {
		$message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Stripe request failed.', 'arsenal-settings' );
		return new WP_Error(
			'stripe_api_error',
			$message,
			array( 'status' => $code )
		);
	}

	return $data;
}

/**
 * Stripe POST v1/{path} (application/x-www-form-urlencoded body).
 *
 * @param string $path Path after v1/ (no leading slash).
 * @param array  $body Request body (nested arrays supported by http_build_query).
 * @return array|WP_Error Decoded JSON object or error.
 */
function arsenal_settings_stripe_api_post( $path, array $body ) {
	$secret = arsenal_settings_get_stripe_secret_key();
	if ( '' === $secret ) {
		return new WP_Error(
			'stripe_config',
			__( 'Stripe secret key is not configured. Save it under Settings → Arsenal Stripe in the admin, or define ARSENAL_STRIPE_SECRET_KEY in wp-config.php, or use the arsenal_stripe_secret_key filter.', 'arsenal-settings' ),
			array( 'status' => 500 )
		);
	}

	$url      = 'https://api.stripe.com/v1/' . ltrim( (string) $path, '/' );
	$response = wp_remote_post(
		$url,
		array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $secret,
			),
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) ) {
		return new WP_Error(
			'stripe_invalid_response',
			__( 'Invalid response from Stripe.', 'arsenal-settings' ),
			array( 'status' => 502 )
		);
	}

	if ( $code >= 400 ) {
		$message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Stripe request failed.', 'arsenal-settings' );
		return new WP_Error(
			isset( $data['error']['type'] ) ? 'stripe_' . sanitize_key( $data['error']['type'] ) : 'stripe_error',
			$message,
			array(
				'status'       => $code,
				'stripe_error' => isset( $data['error'] ) ? $data['error'] : null,
			)
		);
	}

	return $data;
}

/**
 * Build a Stripe v1 path with query string (RFC3986) so expand[] survives encoding correctly.
 *
 * @param string $path  Path after v1/ (no leading slash), without query string.
 * @param array  $query Query parameters (use nested arrays for expand, e.g. array( 'expand' => array( 'payment_intent' ) )).
 * @return string
 */
function arsenal_settings_stripe_path_with_query( $path, array $query ) {
	$path = ltrim( (string) $path, '/' );
	if ( empty( $query ) ) {
		return $path;
	}
	$qs = http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
	return $path . '?' . $qs;
}

/**
 * GET a Subscription by id with optional expand paths.
 *
 * @param string $subscription_id sub_….
 * @param array  $expand_paths     e.g. array( 'latest_invoice.payment_intent' ).
 * @return array|WP_Error
 */
function arsenal_settings_stripe_get_subscription( $subscription_id, array $expand_paths = array() ) {
	if ( ! preg_match( '/^sub_[a-zA-Z0-9]+$/', (string) $subscription_id ) ) {
		return new WP_Error(
			'invalid_subscription_id',
			__( 'Invalid Stripe subscription id.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}
	$query = array();
	if ( ! empty( $expand_paths ) ) {
		$query['expand'] = array_values( $expand_paths );
	}
	$path = arsenal_settings_stripe_path_with_query( 'subscriptions/' . rawurlencode( (string) $subscription_id ), $query );
	return arsenal_settings_stripe_api_get( $path );
}

/**
 * Resolve PaymentIntent client_secret from a subscription API object (handles unexpanded invoice / PI).
 *
 * @param array $subscription Decoded subscription object from Stripe.
 * @return array{0:?string,1:?string} Tuple: client_secret, payment_intent_status.
 */
function arsenal_settings_stripe_resolve_subscription_payment_intent( array $subscription ) {
	$latest = isset( $subscription['latest_invoice'] ) ? $subscription['latest_invoice'] : null;

	$invoice = null;
	if ( is_string( $latest ) && preg_match( '/^in_[a-zA-Z0-9]+$/', $latest ) ) {
		$inv_path = arsenal_settings_stripe_path_with_query(
			'invoices/' . rawurlencode( $latest ),
			array( 'expand' => array( 'payment_intent' ) )
		);
		$fetched = arsenal_settings_stripe_api_get( $inv_path );
		if ( ! is_wp_error( $fetched ) && isset( $fetched['object'] ) && 'invoice' === $fetched['object'] ) {
			$invoice = $fetched;
		}
	} elseif ( is_array( $latest ) ) {
		$invoice    = $latest;
		$pi_nested  = isset( $invoice['payment_intent'] ) ? $invoice['payment_intent'] : null;
		$has_secret = is_array( $pi_nested ) && ! empty( $pi_nested['client_secret'] );
		if (
			! $has_secret
			&& ! empty( $invoice['id'] )
			&& preg_match( '/^in_[a-zA-Z0-9]+$/', (string) $invoice['id'] )
		) {
			$inv_path = arsenal_settings_stripe_path_with_query(
				'invoices/' . rawurlencode( (string) $invoice['id'] ),
				array( 'expand' => array( 'payment_intent' ) )
			);
			$fetched = arsenal_settings_stripe_api_get( $inv_path );
			if ( ! is_wp_error( $fetched ) && isset( $fetched['object'] ) && 'invoice' === $fetched['object'] ) {
				$invoice = $fetched;
			}
		}
	}

	if ( ! is_array( $invoice ) ) {
		return array( null, null );
	}

	$pit = isset( $invoice['payment_intent'] ) ? $invoice['payment_intent'] : null;
	$pi  = null;

	if ( is_string( $pit ) && preg_match( '/^pi_[a-zA-Z0-9]+$/', $pit ) ) {
		$fetched = arsenal_settings_stripe_api_get( 'payment_intents/' . rawurlencode( $pit ) );
		if ( ! is_wp_error( $fetched ) && isset( $fetched['object'] ) && 'payment_intent' === $fetched['object'] ) {
			$pi = $fetched;
		}
	} elseif ( is_array( $pit ) ) {
		$pi = $pit;
	}

	if ( ! is_array( $pi ) ) {
		return array( null, null );
	}

	$secret = isset( $pi['client_secret'] ) ? (string) $pi['client_secret'] : null;
	$status = isset( $pi['status'] ) ? (string) $pi['status'] : null;

	return array( $secret, $status );
}

/**
 * Find first Stripe Customer ID by email.
 *
 * @param string $email Email address.
 * @return string|WP_Error Customer id (cus_…) or empty string if none, or WP_Error on API failure.
 */
function arsenal_settings_stripe_find_customer_id_by_email( $email ) {
	$secret = arsenal_settings_get_stripe_secret_key();
	if ( '' === $secret ) {
		return new WP_Error(
			'stripe_config',
			__( 'Stripe secret key is not configured. Save it under Settings → Arsenal Stripe in the admin, or define ARSENAL_STRIPE_SECRET_KEY in wp-config.php, or use the arsenal_stripe_secret_key filter.', 'arsenal-settings' ),
			array( 'status' => 500 )
		);
	}

	$url = add_query_arg(
		array(
			'email' => $email,
			'limit' => 1,
		),
		'https://api.stripe.com/v1/customers'
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $secret,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) || $code >= 400 ) {
		$message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Could not look up Stripe customer.', 'arsenal-settings' );
		return new WP_Error(
			'stripe_lookup_failed',
			$message,
			array( 'status' => $code >= 400 ? $code : 502 )
		);
	}

	if ( ! empty( $data['data'][0]['id'] ) ) {
		return (string) $data['data'][0]['id'];
	}

	return '';
}

/**
 * Create a Stripe Customer (minimal: email, optional name).
 *
 * @param string $email Valid email (billing / login).
 * @param string $name  Optional display name (max 256 chars).
 * @return array|WP_Error Decoded Customer object or error.
 */
function arsenal_settings_stripe_create_customer( $email, $name = '' ) {
	$email = sanitize_email( trim( (string) $email ) );
	if ( ! is_email( $email ) ) {
		return new WP_Error(
			'invalid_email',
			__( 'A valid email is required to create a Stripe customer.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$body = array(
		'email' => $email,
	);
	$name = trim( (string) $name );
	if ( $name !== '' ) {
		$body['name'] = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 256 ) : substr( $name, 0, 256 );
	}

	return arsenal_settings_stripe_api_post( 'customers', $body );
}

/**
 * Return the customer's default PaymentMethod id (pm_…) if set in Stripe.
 *
 * Reads invoice_settings.default_payment_method and default_payment_method on the Customer object.
 * Legacy card/bank default_source (card_… / ba_…) is not returned — Subscription create expects pm_….
 *
 * @param string $customer_id Stripe Customer id cus_….
 * @return string pm_… or empty string if none / lookup failed.
 */
function arsenal_settings_stripe_get_customer_default_payment_method_id( $customer_id ) {
	if ( ! preg_match( '/^cus_[a-zA-Z0-9]+$/', (string) $customer_id ) ) {
		return '';
	}

	$data = arsenal_settings_stripe_api_get( 'customers/' . rawurlencode( (string) $customer_id ) );
	if ( is_wp_error( $data ) || ! is_array( $data ) || empty( $data['id'] ) ) {
		return '';
	}

	$candidates = array();
	if ( isset( $data['invoice_settings'] ) && is_array( $data['invoice_settings'] ) && isset( $data['invoice_settings']['default_payment_method'] ) ) {
		$candidates[] = $data['invoice_settings']['default_payment_method'];
	}
	if ( isset( $data['default_payment_method'] ) ) {
		$candidates[] = $data['default_payment_method'];
	}

	foreach ( $candidates as $pm ) {
		if ( is_string( $pm ) && preg_match( '/^pm_[a-zA-Z0-9]+$/', $pm ) ) {
			return $pm;
		}
		if ( is_array( $pm ) && ! empty( $pm['id'] ) && preg_match( '/^pm_[a-zA-Z0-9]+$/', (string) $pm['id'] ) ) {
			return (string) $pm['id'];
		}
	}

	return '';
}

/**
 * First PaymentMethod attached to the customer (when no invoice default is set).
 *
 * @param string $customer_id cus_….
 * @param string $type        Stripe payment method type (card, us_bank_account, sepa_debit).
 * @return string pm_… or empty.
 */
function arsenal_settings_stripe_get_first_customer_payment_method_id( $customer_id, $type = 'card' ) {
	if ( ! preg_match( '/^cus_[a-zA-Z0-9]+$/', (string) $customer_id ) ) {
		return '';
	}
	$allowed = array( 'card', 'us_bank_account', 'sepa_debit' );
	if ( ! in_array( (string) $type, $allowed, true ) ) {
		$type = 'card';
	}
	$path = arsenal_settings_stripe_path_with_query(
		'payment_methods',
		array(
			'customer' => (string) $customer_id,
			'type'     => (string) $type,
			'limit'    => 1,
		)
	);
	$data = arsenal_settings_stripe_api_get( $path );
	if ( is_wp_error( $data ) || empty( $data['data'][0]['id'] ) ) {
		return '';
	}
	$id = (string) $data['data'][0]['id'];
	return preg_match( '/^pm_[a-zA-Z0-9]+$/', $id ) ? $id : '';
}

/**
 * Pick a PaymentMethod to charge: subscription default, then customer invoice default, then first attached PM.
 *
 * @param string $customer_id     cus_….
 * @param array  $subscription_row Subscription object from Stripe.
 * @return string pm_… or empty.
 */
function arsenal_settings_stripe_resolve_payment_method_for_charge( $customer_id, array $subscription_row ) {
	$sub_pm = isset( $subscription_row['default_payment_method'] ) ? $subscription_row['default_payment_method'] : null;
	if ( is_string( $sub_pm ) && preg_match( '/^pm_[a-zA-Z0-9]+$/', $sub_pm ) ) {
		return $sub_pm;
	}
	if ( is_array( $sub_pm ) && ! empty( $sub_pm['id'] ) && preg_match( '/^pm_[a-zA-Z0-9]+$/', (string) $sub_pm['id'] ) ) {
		return (string) $sub_pm['id'];
	}
	$from_customer = arsenal_settings_stripe_get_customer_default_payment_method_id( $customer_id );
	if ( $from_customer !== '' ) {
		return $from_customer;
	}
	$first = arsenal_settings_stripe_get_first_customer_payment_method_id( $customer_id, 'card' );
	if ( $first !== '' ) {
		return $first;
	}
	return arsenal_settings_stripe_get_first_customer_payment_method_id( $customer_id, 'us_bank_account' );
}

/**
 * Parse card expiry (MM/YY, MM/YYYY, with / or -).
 *
 * @param string $raw Raw user input.
 * @return array{ exp_month: int, exp_year: int }|null
 */
function arsenal_settings_parse_card_expiry( $raw ) {
	$raw = preg_replace( '/\s+/', '', (string) $raw );
	if ( '' === $raw ) {
		return null;
	}
	if ( ! preg_match( '/^(0?[1-9]|1[0-2])[\/\-](\d{2}|\d{4})$/', $raw, $m ) ) {
		return null;
	}
	$month = (int) $m[1];
	$yp    = $m[2];
	$year  = strlen( $yp ) === 2 ? (int) ( 2000 + (int) $yp ) : (int) $yp;
	if ( $year < 2000 || $year > 2100 || $month < 1 || $month > 12 ) {
		return null;
	}
	return array(
		'exp_month' => $month,
		'exp_year'  => $year,
	);
}

/**
 * Create a Stripe card PaymentMethod from raw card fields and attach it to a Customer.
 *
 * In test mode (sk_test_…), common Stripe test card numbers are converted to official test
 * tokens (tok_visa, etc.) and sent as card[token], so raw PAN is not sent to Stripe when
 * raw card data APIs are disabled on the account.
 *
 * PCI: handling PAN/CVC on your server has strict requirements in live mode. Disable with the
 * filter `arsenal_settings_allow_create_payment_with_card_fields` when not allowed.
 *
 * @param string $customer_id Stripe Customer id cus_….
 * @param array  $card {
 *     @type string $number          Card number (digits only).
 *     @type int    $exp_month       1–12.
 *     @type int    $exp_year        Four-digit year.
 *     @type string $cvc             CVC digits.
 *     @type string $cardholder_name Optional.
 *     @type string $postal_code     Optional billing postal / ZIP.
 * }
 * @return string|WP_Error pm_… on success.
 */
function arsenal_settings_stripe_create_and_attach_card_payment_method( $customer_id, array $card ) {
	if ( ! preg_match( '/^cus_[a-zA-Z0-9]+$/', (string) $customer_id ) ) {
		return new WP_Error(
			'invalid_customer',
			__( 'Invalid customer for PaymentMethod attach.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$number = (string) $card['number'];
	$month  = (int) $card['exp_month'];
	$year   = (int) $card['exp_year'];
	$cvc    = (string) $card['cvc'];
	$name   = isset( $card['cardholder_name'] ) ? trim( (string) $card['cardholder_name'] ) : '';
	$postal = isset( $card['postal_code'] ) ? trim( (string) $card['postal_code'] ) : '';

	$test_tok = arsenal_settings_stripe_test_token_for_card_digits( $number );
	if ( $test_tok !== '' && preg_match( '/^tok_[a-zA-Z0-9]+$/', $test_tok ) ) {
		$body = array(
			'type' => 'card',
			'card' => array(
				'token' => $test_tok,
			),
		);
	} else {
		$body = array(
			'type' => 'card',
			'card' => array(
				'number'    => $number,
				'exp_month' => $month,
				'exp_year'  => $year,
				'cvc'       => $cvc,
			),
		);
	}

	if ( $name !== '' || $postal !== '' ) {
		$bd = array();
		if ( $name !== '' ) {
			$bd['name'] = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 256 ) : substr( $name, 0, 256 );
		}
		if ( $postal !== '' ) {
			$bd['address'] = array(
				'postal_code' => function_exists( 'mb_substr' ) ? mb_substr( $postal, 0, 20 ) : substr( $postal, 0, 20 ),
			);
		}
		$body['billing_details'] = $bd;
	}

	$created = arsenal_settings_stripe_api_post( 'payment_methods', $body );
	if ( is_wp_error( $created ) ) {
		return $created;
	}

	$pm_id = isset( $created['id'] ) ? (string) $created['id'] : '';
	if ( $pm_id === '' || ! preg_match( '/^pm_[a-zA-Z0-9]+$/', $pm_id ) ) {
		return new WP_Error(
			'stripe_invalid_response',
			__( 'Stripe did not return a PaymentMethod id.', 'arsenal-settings' ),
			array( 'status' => 502 )
		);
	}

	$attached = arsenal_settings_stripe_api_post(
		'payment_methods/' . rawurlencode( $pm_id ) . '/attach',
		array( 'customer' => $customer_id )
	);
	if ( is_wp_error( $attached ) ) {
		return $attached;
	}

	return $pm_id;
}

/**
 * Pay an open Stripe invoice (server-side), e.g. first subscription invoice.
 *
 * @param string $invoice_id Invoice id in_….
 * @param string $payment_method_id pm_… (recommended for reliable collection).
 * @return array|WP_Error Paid invoice object or error.
 */
function arsenal_settings_stripe_invoice_pay( $invoice_id, $payment_method_id ) {
	if ( ! preg_match( '/^in_[a-zA-Z0-9]+$/', (string) $invoice_id ) ) {
		return new WP_Error(
			'invalid_invoice_id',
			__( 'Invalid Stripe invoice id.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}
	$body = array(
		'off_session' => 'true',
	);
	if ( $payment_method_id !== '' && preg_match( '/^pm_[a-zA-Z0-9]+$/', (string) $payment_method_id ) ) {
		$body['payment_method'] = (string) $payment_method_id;
	}
	return arsenal_settings_stripe_api_post( 'invoices/' . rawurlencode( (string) $invoice_id ) . '/pay', $body );
}

/**
 * Create a Stripe PaymentIntent (one-off payment).
 *
 * Always sets capture_method to automatic (authorize and capture in one step; no separate capture call).
 *
 * @see https://docs.stripe.com/api/payment_intents/create
 *
 * @param array $args {
 *     @type string $customer         Stripe Customer id cus_… (required).
 *     @type int    $amount           Amount in smallest currency unit (positive integer).
 *     @type string $currency         Three-letter ISO code (e.g. gbp).
 *     @type string $payment_method   pm_… When empty, automatic_payment_methods is used (client-side collection).
 *     @type string $description      Optional statement description.
 *     @type string $receipt_email     Optional receipt email.
 *     @type bool   $confirm          When payment_method is set, whether to confirm immediately (default true).
 *     @type bool   $off_session      When confirming without setup_future_usage, merchant-initiated flag (default true).
 *                                      When setup_future_usage is set, off_session is forced false (Stripe API requirement).
 *     @type string $setup_future_usage When payment_method is set: off_session (default) or on_session to save the
 *                                      PaymentMethod on the Customer for reuse; empty string to omit (no save hint).
 *     @type string $return_url         When confirming with payment_method: URL for redirect-based auth (default home_url).
 *     @type array  $metadata         Optional string key => scalar value (max 40 keys).
 * }
 * @return array|WP_Error
 */
function arsenal_settings_stripe_create_payment_intent( array $args ) {
	$defaults = array(
		'customer'             => '',
		'amount'               => 0,
		'currency'             => '',
		'payment_method'       => '',
		'description'          => '',
		'receipt_email'        => '',
		'confirm'              => true,
		'off_session'          => true,
		'setup_future_usage'   => 'off_session',
		'return_url'           => '',
		'metadata'             => array(),
	);
	$a = array_merge( $defaults, $args );

	$customer = trim( (string) $a['customer'] );
	if ( $customer === '' || ! preg_match( '/^cus_[a-zA-Z0-9]+$/', $customer ) ) {
		return new WP_Error(
			'invalid_customer',
			__( 'A valid Stripe Customer id (cus_…) is required for create-payment.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$currency = strtolower( trim( (string) $a['currency'] ) );
	if ( $currency === '' || ! preg_match( '/^[a-z]{3}$/', $currency ) ) {
		return new WP_Error(
			'invalid_currency',
			__( 'Provide a valid three-letter currency code (for example gbp or usd).', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$amount = (int) $a['amount'];
	if ( $amount < 1 ) {
		return new WP_Error(
			'invalid_amount',
			__( 'amount must be a positive integer in the smallest currency unit (e.g. pence for GBP).', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$pm = trim( (string) $a['payment_method'] );
	if ( $pm !== '' && ! preg_match( '/^pm_[a-zA-Z0-9]+$/', $pm ) ) {
		return new WP_Error(
			'invalid_payment_method',
			__( 'payment_method must be a Stripe PaymentMethod id (pm_…).', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$body = array(
		'amount'           => $amount,
		'currency'         => $currency,
		'customer'         => $customer,
		'capture_method'   => 'automatic',
		'expand'           => array( 'latest_charge' ),
	);

	$desc = trim( (string) $a['description'] );
	if ( $desc !== '' ) {
		$body['description'] = function_exists( 'mb_substr' ) ? mb_substr( $desc, 0, 1000 ) : substr( $desc, 0, 1000 );
	}

	$receipt = trim( (string) $a['receipt_email'] );
	if ( $receipt !== '' && is_email( $receipt ) ) {
		$body['receipt_email'] = $receipt;
	}

	if ( is_array( $a['metadata'] ) && ! empty( $a['metadata'] ) ) {
		$n = 0;
		foreach ( $a['metadata'] as $mk => $mv ) {
			if ( $n >= 40 ) {
				break;
			}
			$k = sanitize_key( (string) $mk );
			if ( $k === '' ) {
				continue;
			}
			$body[ 'metadata[' . $k . ']' ] = is_scalar( $mv ) ? substr( (string) $mv, 0, 500 ) : '';
			++$n;
		}
	}

	if ( $pm !== '' ) {
		$body['payment_method'] = $pm;
		$sfu = isset( $a['setup_future_usage'] ) ? trim( (string) $a['setup_future_usage'] ) : 'off_session';
		if ( $sfu !== '' && in_array( $sfu, array( 'off_session', 'on_session' ), true ) ) {
			$body['setup_future_usage'] = $sfu;
		}
		$do_confirm = (bool) $a['confirm'];
		if ( $do_confirm ) {
			$body['confirm'] = 'true';
			// Stripe rejects confirm + off_session=true together with setup_future_usage (save PM for later).
			if ( isset( $body['setup_future_usage'] ) ) {
				$body['off_session'] = 'false';
			} else {
				$body['off_session'] = ! empty( $a['off_session'] ) ? 'true' : 'false';
			}
			// Redirect-based dashboard PMs may require return_url when confirming; default to site home.
			$ru = isset( $a['return_url'] ) ? trim( (string) $a['return_url'] ) : '';
			if ( $ru === '' && function_exists( 'home_url' ) ) {
				$ru = (string) apply_filters( 'arsenal_settings_stripe_payment_intent_return_url', home_url( '/' ) );
			}
			if ( $ru !== '' ) {
				$body['return_url'] = $ru;
			}
		}
	} else {
		$body['automatic_payment_methods'] = array(
			'enabled'          => 'true',
			'allow_redirects'  => 'never',
		);
	}

	return arsenal_settings_stripe_api_post( 'payment_intents', $body );
}

/**
 * Apply default_payment_method and payment_behavior to a Stripe Subscriptions POST body.
 *
 * @param array $body  Subscription create body (passed by reference).
 * @param array $extra Same shape as arsenal_settings_stripe_create_subscription $extra.
 */
function arsenal_settings_stripe_subscription_body_apply_payment_options( array &$body, array $extra ) {
	$allowed_behaviors = array( 'allow_incomplete', 'default_incomplete', 'error_if_incomplete', 'pending_if_incomplete' );

	$pm = isset( $extra['default_payment_method'] ) ? trim( (string) $extra['default_payment_method'] ) : '';
	if ( $pm !== '' && preg_match( '/^pm_[a-zA-Z0-9]+$/', $pm ) ) {
		$body['default_payment_method'] = $pm;
	}

	if ( isset( $extra['payment_behavior'] ) && in_array( (string) $extra['payment_behavior'], $allowed_behaviors, true ) ) {
		$body['payment_behavior'] = (string) $extra['payment_behavior'];
		if ( 'allow_incomplete' === $body['payment_behavior'] ) {
			// Merchant/server-initiated: Stripe attempts automatic payment without a hosted client confirmation step.
			$body['off_session'] = 'true';
		}
	} else {
		// Default: allow_incomplete (not default_incomplete). Stripe tries to charge saved PMs off-session;
		// subscription may become active immediately when payment succeeds without frontend Elements/SCA UI.
		// @see https://docs.stripe.com/api/subscriptions/create#create_subscription-payment_behavior
		$body['payment_behavior']                              = 'allow_incomplete';
		$body['payment_settings[save_default_payment_method]'] = 'on_subscription';
		$body['off_session']                                   = 'true';
	}
}

/**
 * Apply subscription timing: billing_cycle_anchor, trial_period_days (Stripe Subscriptions API).
 *
 * @param array $body  Subscription create body (passed by reference).
 * @param array $extra May include billing_cycle_anchor (unix), trial_period_days (0–730).
 */
function arsenal_settings_stripe_subscription_body_apply_schedule_options( array &$body, array $extra ) {
	if ( isset( $extra['billing_cycle_anchor'] ) ) {
		$anchor = (int) $extra['billing_cycle_anchor'];
		if ( $anchor > 0 ) {
			$body['billing_cycle_anchor'] = $anchor;
		}
	}
	if ( isset( $extra['trial_period_days'] ) ) {
		$days = (int) $extra['trial_period_days'];
		if ( $days > 0 && $days <= 730 ) {
			$body['trial_period_days'] = $days;
		}
	}
}

/**
 * Create a Stripe Subscription via the REST API.
 *
 * @see https://docs.stripe.com/api/subscriptions/create
 * @see https://docs.stripe.com/api/subscriptions/object
 *
 * @param string $customer_id Stripe Customer ID (cus_…).
 * @param string $price_id    Stripe Price ID (price_…).
 * @param int    $quantity    Line item quantity (min 1).
 * @param array  $extra {
 *     Optional. Extra Stripe create parameters.
 *
 *     @type string $default_payment_method Payment method id pm_… already attached to the customer.
 *     @type string $payment_behavior       One of allow_incomplete, default_incomplete, error_if_incomplete, pending_if_incomplete.
 *                                          When omitted, allow_incomplete and off_session are applied for server-side charging without frontend confirmation.
 *     @type int    $billing_cycle_anchor   Optional. Unix timestamp aligning subscription billing cycles (@see Stripe subscription create).
 *     @type int    $trial_period_days      Optional. Free trial length in days before the first charge (0–730).
 * }
 * @return array|WP_Error Decoded subscription object or error.
 */
function arsenal_settings_stripe_create_subscription( $customer_id, $price_id, $quantity = 1, $extra = array() ) {
	$secret = arsenal_settings_get_stripe_secret_key();
	if ( '' === $secret ) {
		return new WP_Error(
			'stripe_config',
			__( 'Stripe secret key is not configured. Save it under Settings → Arsenal Stripe in the admin, or define ARSENAL_STRIPE_SECRET_KEY in wp-config.php, or use the arsenal_stripe_secret_key filter.', 'arsenal-settings' ),
			array( 'status' => 500 )
		);
	}

	$quantity = max( 1, (int) $quantity );

	$body = array(
		'customer'           => $customer_id,
		'items[0][price]'    => $price_id,
		'items[0][quantity]' => $quantity,
		'expand'             => array( 'latest_invoice.payment_intent' ),
	);

	arsenal_settings_stripe_subscription_body_apply_payment_options( $body, $extra );
	arsenal_settings_stripe_subscription_body_apply_schedule_options( $body, $extra );

	$response = wp_remote_post(
		'https://api.stripe.com/v1/subscriptions',
		array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $secret,
			),
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) ) {
		return new WP_Error(
			'stripe_invalid_response',
			__( 'Invalid response from Stripe.', 'arsenal-settings' ),
			array( 'status' => 502 )
		);
	}

	if ( $code >= 400 ) {
		$message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Stripe request failed.', 'arsenal-settings' );
		return new WP_Error(
			isset( $data['error']['type'] ) ? 'stripe_' . sanitize_key( $data['error']['type'] ) : 'stripe_error',
			$message,
			array(
				'status'       => $code,
				'stripe_error' => isset( $data['error'] ) ? $data['error'] : null,
			)
		);
	}

	return $data;
}

/**
 * Create a Stripe Product (minimal fields).
 *
 * @param string $name Product display name.
 * @return array|WP_Error Decoded product object.
 */
function arsenal_settings_stripe_create_product( $name ) {
	$secret = arsenal_settings_get_stripe_secret_key();
	if ( '' === $secret ) {
		return new WP_Error(
			'stripe_config',
			__( 'Stripe secret key is not configured. Save it under Settings → Arsenal Stripe in the admin, or define ARSENAL_STRIPE_SECRET_KEY in wp-config.php, or use the arsenal_stripe_secret_key filter.', 'arsenal-settings' ),
			array( 'status' => 500 )
		);
	}

	$response = wp_remote_post(
		'https://api.stripe.com/v1/products',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $secret,
			),
			'body'    => array(
				'name' => $name,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) ) {
		return new WP_Error(
			'stripe_invalid_response',
			__( 'Invalid response from Stripe.', 'arsenal-settings' ),
			array( 'status' => 502 )
		);
	}

	if ( $code >= 400 ) {
		$message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Stripe request failed.', 'arsenal-settings' );
		return new WP_Error(
			isset( $data['error']['type'] ) ? 'stripe_' . sanitize_key( $data['error']['type'] ) : 'stripe_error',
			$message,
			array(
				'status'       => $code,
				'stripe_error' => isset( $data['error'] ) ? $data['error'] : null,
			)
		);
	}

	return $data;
}

/**
 * Create a Stripe Subscription with inline price_data (new recurring Price; Product must exist or be created first).
 *
 * Current Stripe API requires `items[].price_data.product` (a Product id). Inline `product_data` on subscriptions is not accepted.
 * When `product_name` is supplied without `product`, a Product is created via the Products API, then the subscription references it.
 *
 * @see https://docs.stripe.com/api/subscriptions/create#create_subscription-items-price_data
 *
 * @param string $customer_id Stripe Customer ID (cus_…).
 * @param int    $quantity    Line item quantity (min 1).
 * @param array  $inline {
 *     @type string $currency          Three-letter ISO currency (e.g. gbp, usd).
 *     @type int    $unit_amount       Amount in the smallest currency unit (e.g. pence).
 *     @type string $interval          One of day, week, month, year.
 *     @type int    $interval_count    Optional. Default 1. Billing period multiplier.
 *     @type string $product           Optional. Existing Stripe Product id prod_…. If set, used as price_data.product.
 *     @type string $product_name      If product is omitted, a new Product is created with this name (max 500 chars).
 * }
 * @param array  $extra       Optional default_payment_method, payment_behavior (same as create_subscription).
 * @return array|WP_Error
 */
function arsenal_settings_stripe_create_subscription_inline_price( $customer_id, $quantity, array $inline, array $extra = array() ) {
	$secret = arsenal_settings_get_stripe_secret_key();
	if ( '' === $secret ) {
		return new WP_Error(
			'stripe_config',
			__( 'Stripe secret key is not configured. Save it under Settings → Arsenal Stripe in the admin, or define ARSENAL_STRIPE_SECRET_KEY in wp-config.php, or use the arsenal_stripe_secret_key filter.', 'arsenal-settings' ),
			array( 'status' => 500 )
		);
	}

	$quantity = max( 1, (int) $quantity );

	$currency = isset( $inline['currency'] ) ? strtolower( trim( (string) $inline['currency'] ) ) : '';
	if ( $currency === '' || ! preg_match( '/^[a-z]{3}$/', $currency ) ) {
		return new WP_Error(
			'invalid_currency',
			__( 'Provide a valid three-letter currency code (for example gbp or usd).', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$unit_amount = isset( $inline['unit_amount'] ) ? (int) $inline['unit_amount'] : 0;
	if ( $unit_amount < 1 ) {
		return new WP_Error(
			'invalid_unit_amount',
			__( 'unit_amount must be a positive integer in the smallest currency unit (e.g. pence for GBP).', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$interval = isset( $inline['interval'] ) ? strtolower( trim( (string) $inline['interval'] ) ) : '';
	$allowed  = array( 'day', 'week', 'month', 'year' );
	if ( ! in_array( $interval, $allowed, true ) ) {
		return new WP_Error(
			'invalid_interval',
			__( 'interval must be one of day, week, month, or year.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$interval_count = isset( $inline['interval_count'] ) ? (int) $inline['interval_count'] : 1;
	$interval_count = max( 1, min( 365, $interval_count ) );

	$existing_product = isset( $inline['product'] ) ? trim( (string) $inline['product'] ) : '';
	$product_name     = isset( $inline['product_name'] ) ? trim( (string) $inline['product_name'] ) : '';

	$product_id = '';
	if ( $existing_product !== '' ) {
		if ( ! preg_match( '/^prod_[a-zA-Z0-9]+$/', $existing_product ) ) {
			return new WP_Error(
				'invalid_product_format',
				__( 'When provided, "product" must be a Stripe Product id (prod_…).', 'arsenal-settings' ),
				array( 'status' => 400 )
			);
		}
		$product_id = $existing_product;
	} else {
		if ( $product_name === '' ) {
			return new WP_Error(
				'missing_product',
				__( 'Provide either "product" (Stripe Product id prod_…) or "product_name" to create a Product before the recurring Price.', 'arsenal-settings' ),
				array( 'status' => 400 )
			);
		}
		if ( function_exists( 'mb_substr' ) ) {
			$product_name = mb_substr( $product_name, 0, 500 );
		} else {
			$product_name = substr( $product_name, 0, 500 );
		}
		$created_product = arsenal_settings_stripe_create_product( $product_name );
		if ( is_wp_error( $created_product ) ) {
			return $created_product;
		}
		if ( empty( $created_product['id'] ) || ! is_string( $created_product['id'] ) || ! preg_match( '/^prod_[a-zA-Z0-9]+$/', $created_product['id'] ) ) {
			return new WP_Error(
				'stripe_invalid_response',
				__( 'Stripe did not return a valid Product id after product creation.', 'arsenal-settings' ),
				array( 'status' => 502 )
			);
		}
		$product_id = (string) $created_product['id'];
	}

	$recurring = array(
		'interval'       => $interval,
		'interval_count' => $interval_count,
	);

	$body = array(
		'customer' => $customer_id,
		'items'    => array(
			array(
				'quantity'   => $quantity,
				'price_data' => array(
					'currency'    => $currency,
					'product'     => $product_id,
					'unit_amount' => $unit_amount,
					'recurring'   => $recurring,
				),
			),
		),
		'expand'   => array( 'latest_invoice.payment_intent' ),
	);

	arsenal_settings_stripe_subscription_body_apply_payment_options( $body, $extra );
	arsenal_settings_stripe_subscription_body_apply_schedule_options( $body, $extra );

	$response = wp_remote_post(
		'https://api.stripe.com/v1/subscriptions',
		array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $secret,
			),
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) ) {
		return new WP_Error(
			'stripe_invalid_response',
			__( 'Invalid response from Stripe.', 'arsenal-settings' ),
			array( 'status' => 502 )
		);
	}

	if ( $code >= 400 ) {
		$message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Stripe request failed.', 'arsenal-settings' );
		return new WP_Error(
			isset( $data['error']['type'] ) ? 'stripe_' . sanitize_key( $data['error']['type'] ) : 'stripe_error',
			$message,
			array(
				'status'       => $code,
				'stripe_error' => isset( $data['error'] ) ? $data['error'] : null,
			)
		);
	}

	return $data;
}

/**
 * Register REST API routes.
 */
function arsenal_settings_register_rest_routes() {
	register_rest_route(
		ARSENAL_SETTINGS_REST_NAMESPACE,
		'/check-user-subscription',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'arsenal_settings_rest_check_user_subscription',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		ARSENAL_SETTINGS_REST_NAMESPACE,
		'/create-subscription',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'arsenal_settings_rest_create_subscription',
			'permission_callback' => '__return_true',
			'args'                => array(
				'customer'        => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'customer_email'  => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				),
				'price'           => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'quantity'                 => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				),
				'default_payment_method'   => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'payment_behavior'         => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'billing_cycle_anchor'     => array(
					'required' => false,
					'type'     => 'integer',
				),
				'trial_period_days'        => array(
					'required' => false,
					'type'     => 'integer',
				),
			),
		)
	);

	register_rest_route(
		ARSENAL_SETTINGS_REST_NAMESPACE,
		'/create-subscription-custom',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'arsenal_settings_rest_create_subscription_custom',
			'permission_callback' => '__return_true',
			'args'                => array(
				'customer'               => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'customer_email'         => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				),
				'currency'               => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'unit_amount'            => array(
					'required' => true,
					'type'     => 'integer',
				),
				'interval'               => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'interval_count'         => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				),
				'product'                => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'product_name'           => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'quantity'               => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				),
				'default_payment_method' => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'payment_behavior'       => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'billing_cycle_anchor'   => array(
					'required' => false,
					'type'     => 'integer',
				),
				'trial_period_days'      => array(
					'required' => false,
					'type'     => 'integer',
				),
			),
		)
	);

	register_rest_route(
		ARSENAL_SETTINGS_REST_NAMESPACE,
		'/create-recurring-subscription',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'arsenal_settings_rest_create_recurring_subscription',
			'permission_callback' => '__return_true',
			'args'                => array(
				'customer'               => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'customer_email'         => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				),
				'price'                  => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'currency'               => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'unit_amount'            => array(
					'required' => false,
					'type'     => 'integer',
				),
				'interval'               => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'interval_count'         => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				),
				'product'                => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'product_name'           => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'quantity'               => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				),
				'default_payment_method' => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'payment_behavior'       => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'billing_cycle_anchor'   => array(
					'required' => false,
					'type'     => 'integer',
				),
				'trial_period_days'      => array(
					'required' => false,
					'type'     => 'integer',
				),
			),
		)
	);

	register_rest_route(
		ARSENAL_SETTINGS_REST_NAMESPACE,
		'/create-recurring-subscription-by-email',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'arsenal_settings_rest_create_recurring_subscription_by_email',
			'permission_callback' => '__return_true',
			'args'                => array(
				'customer_email'         => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				),
				'price'                  => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'currency'               => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'unit_amount'            => array(
					'required' => false,
					'type'     => 'integer',
				),
				'interval'               => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'interval_count'         => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				),
				'product'                => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'product_name'           => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'quantity'               => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				),
				'default_payment_method' => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'payment_behavior'       => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'billing_cycle_anchor'   => array(
					'required' => false,
					'type'     => 'integer',
				),
				'trial_period_days'      => array(
					'required' => false,
					'type'     => 'integer',
				),
			),
		)
	);

	register_rest_route(
		ARSENAL_SETTINGS_REST_NAMESPACE,
		'/create-recurring-subscription-by-armember-plan',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'arsenal_settings_rest_create_recurring_subscription_by_armember_plan',
			'permission_callback' => '__return_true',
			'args'                => array(
				'customer_email'         => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				),
				'armember_plan_id'       => array(
					'required' => true,
					'type'     => 'integer',
				),
				'payment_cycle'          => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 0,
				),
				'quantity'               => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				),
				'default_payment_method' => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'payment_behavior'       => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'billing_cycle_anchor'   => array(
					'required' => false,
					'type'     => 'integer',
				),
				'trial_period_days'      => array(
					'required' => false,
					'type'     => 'integer',
				),
			),
		)
	);

	register_rest_route(
		ARSENAL_SETTINGS_REST_NAMESPACE,
		'/create-recurring-subscription-by-armember-plan-deferred',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'arsenal_settings_rest_create_recurring_subscription_by_armember_plan_deferred',
			'permission_callback' => '__return_true',
			'args'                => array(
				'customer_email'           => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				),
				'armember_plan_id'         => array(
					'required' => true,
					'type'     => 'integer',
				),
				'payment_cycle'            => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 0,
				),
				'quantity'                 => array(
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				),
				'default_payment_method'   => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'payment_behavior'         => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'billing_cycle_anchor'     => array(
					'required' => false,
					'type'     => 'integer',
				),
				'trial_period_days'        => array(
					'required' => false,
					'type'     => 'integer',
				),
				'defer_first_billing_period' => array(
					'required' => false,
					'type'     => 'boolean',
					'default'  => true,
				),
			),
		)
	);

	register_rest_route(
		ARSENAL_SETTINGS_REST_NAMESPACE,
		'/create-payment',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'arsenal_settings_rest_create_payment',
			'permission_callback' => '__return_true',
			'args'                => array(
				'customer'               => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'customer_email'         => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				),
				'customer_name'          => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'amount'                 => array(
					'required' => true,
					'type'     => 'integer',
				),
				'currency'               => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'payment_method'         => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'payment_method_id'      => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'card_number'            => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'card_expiry'            => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'card_cvc'               => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'cardholder_name'        => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'billing_postal_code'    => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'description'            => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'receipt_email'          => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				),
				'setup_future_usage'     => array(
					'required'          => false,
					'type'              => 'string',
					'enum'              => array( 'off_session', 'on_session' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'return_url'             => array(
					'required'          => false,
					'type'              => 'string',
					'format'            => 'uri',
					'sanitize_callback' => 'esc_url_raw',
				),
				'metadata'               => array(
					'required' => false,
					'type'     => 'object',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'arsenal_settings_register_rest_routes' );

/**
 * Turn a WP_Error into a REST error response (honours data.status when set).
 *
 * @param WP_Error $error Error.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_from_wp_error( WP_Error $error ) {
	$data   = $error->get_error_data();
	$status = ( is_array( $data ) && isset( $data['status'] ) ) ? (int) $data['status'] : 500;
	$body   = array(
		'message' => $error->get_error_message(),
		'status'  => false,
		'code'    => $error->get_error_code(),
	);
	if ( is_array( $data ) && ! empty( $data['stripe_error'] ) ) {
		$body['stripe_error'] = $data['stripe_error'];
	}
	return new WP_REST_Response( $body, $status );
}

/**
 * Resolve Stripe customer id from REST params (same rules as create-subscription).
 *
 * @param string $raw_customer              Trimmed "customer" param.
 * @param string $email                     Trimmed "customer_email" param.
 * @param bool   $create_if_missing_email   When true and email is valid but no Customer exists, creates one via Stripe API.
 * @param string $create_display_name       Optional name for new Customer (used only when creating).
 * @return string|WP_Error cus_… or WP_Error (data.status set for HTTP mapping).
 */
function arsenal_settings_rest_get_stripe_customer_id( $raw_customer, $email, $create_if_missing_email = false, $create_display_name = '' ) {
	if ( $raw_customer !== '' && preg_match( '/^cus_[a-zA-Z0-9]+$/', $raw_customer ) ) {
		return $raw_customer;
	}
	if ( $raw_customer !== '' ) {
		return new WP_Error(
			'invalid_customer_format',
			__( 'The "customer" value must be a Stripe Customer ID (for example cus_AbCd123), not a name or username. In Stripe Dashboard open Customers and copy the ID, or send "customer_email" with the billing email instead.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}
	if ( $email !== '' && is_email( $email ) ) {
		$looked = arsenal_settings_stripe_find_customer_id_by_email( $email );
		if ( is_wp_error( $looked ) ) {
			return $looked;
		}
		if ( $looked === '' ) {
			if ( $create_if_missing_email ) {
				$created = arsenal_settings_stripe_create_customer( $email, $create_display_name );
				if ( is_wp_error( $created ) ) {
					return $created;
				}
				if ( ! empty( $created['id'] ) && is_string( $created['id'] ) && preg_match( '/^cus_[a-zA-Z0-9]+$/', $created['id'] ) ) {
					return (string) $created['id'];
				}
				return new WP_Error(
					'stripe_invalid_response',
					__( 'Stripe did not return a valid Customer id after customer creation.', 'arsenal-settings' ),
					array( 'status' => 502 )
				);
			}
			return new WP_Error(
				'stripe_customer_not_found',
				__( 'No Stripe customer exists for that email. Create a customer in Stripe (Dashboard or API) first, then retry or pass "customer" as cus_….', 'arsenal-settings' ),
				array( 'status' => 404 )
			);
		}
		return $looked;
	}

	return new WP_Error(
		'missing_customer',
		__( 'Provide either "customer" (Stripe id cus_…) or a valid "customer_email" to identify the Stripe customer.', 'arsenal-settings' ),
		array( 'status' => 400 )
	);
}

/**
 * Convert ARMember plan amount (major currency units) to Stripe unit_amount (smallest currency unit).
 *
 * @param float|string $major    Plan amount as stored by ARMember.
 * @param string       $currency Three-letter ISO currency (upper or lower case).
 * @return int Positive integer or 0 if invalid.
 */
function arsenal_settings_armember_major_amount_to_stripe_unit_amount( $major, $currency ) {
	$currency = strtoupper( trim( (string) $currency ) );
	$major_f   = (float) $major;
	if ( $currency === '' || $major_f <= 0 ) {
		return 0;
	}
	$zero_decimal = apply_filters(
		'arsenal_settings_stripe_zero_decimal_currencies',
		array( 'BIF', 'DJF', 'JPY', 'KRW', 'PYG', 'VND', 'XAF', 'XPF', 'CLP', 'GNF', 'KMF', 'MGA', 'RWF', 'VUV', 'XOF', 'UGX' )
	);
	if ( in_array( $currency, $zero_decimal, true ) ) {
		return max( 1, (int) round( $major_f ) );
	}
	$decimals = 2;
	if ( isset( $GLOBALS['arm_global_settings']->global_settings['arm_currency_decimal_digit'] ) ) {
		$d = (int) $GLOBALS['arm_global_settings']->global_settings['arm_currency_decimal_digit'];
		if ( $d >= 0 && $d <= 6 ) {
			$decimals = $d;
		}
	}
	$mult = (int) pow( 10, $decimals );
	return max( 1, (int) round( $major_f * $mult ) );
}

/**
 * Map ARMember trial block (from ARM_Plan::prepare_recurring_data) to Stripe trial_period_days (approximate for month/year).
 *
 * @param array $trial Trial subset from recurring data.
 * @return int|null Days 1–730, or null when not applicable.
 */
function arsenal_settings_armember_trial_to_trial_period_days( array $trial ) {
	if ( empty( $trial['interval'] ) ) {
		return null;
	}
	$interval = (int) $trial['interval'];
	$period   = isset( $trial['period'] ) ? strtoupper( (string) $trial['period'] ) : 'M';
	$days     = 0;
	switch ( $period ) {
		case 'D':
			$days = $interval;
			break;
		case 'W':
			$days = $interval * 7;
			break;
		case 'M':
			$days = $interval * 30;
			break;
		case 'Y':
			$days = $interval * 365;
			break;
		default:
			return null;
	}
	if ( $days < 1 ) {
		return null;
	}
	return min( 730, $days );
}

/**
 * Approximate Stripe trial_period_days for one full billing period from inline price_data (day/week/month/year).
 *
 * @param array $inline Same shape as for arsenal_settings_stripe_create_subscription_inline_price (interval, interval_count).
 * @return int 1–730.
 */
function arsenal_settings_stripe_inline_price_to_deferral_trial_days( array $inline ) {
	$interval        = isset( $inline['interval'] ) ? strtolower( trim( (string) $inline['interval'] ) ) : 'month';
	$interval_count  = isset( $inline['interval_count'] ) ? max( 1, (int) $inline['interval_count'] ) : 1;
	switch ( $interval ) {
		case 'day':
			return min( 730, max( 1, $interval_count ) );
		case 'week':
			return min( 730, max( 1, $interval_count * 7 ) );
		case 'month':
			return min( 730, max( 1, $interval_count * 30 ) );
		case 'year':
			return min( 730, max( 1, $interval_count * 365 ) );
		default:
			return min( 730, 30 );
	}
}

/**
 * Load an ARMember subscription plan and build Stripe inline price fields (currency, unit_amount, interval, etc.).
 *
 * @param int $plan_id        ARMember arm_subscription_plan_id.
 * @param int $payment_cycle  Index into payment_cycles when the plan uses multiple cycles; default 0.
 * @return array|WP_Error {
 *     @type array $inline               Arguments for arsenal_settings_stripe_create_subscription_inline_price.
 *     @type int|null $trial_period_days Suggested Stripe trial length when the plan has a trial (approximate for M/Y).
 *     @type string $plan_name           Plan display name (for debugging / filters).
 * }
 */
function arsenal_settings_rest_resolve_armember_plan_for_stripe_inline( $plan_id, $payment_cycle = 0 ) {
	if ( ! class_exists( 'ARM_Plan' ) ) {
		return new WP_Error(
			'armember_inactive',
			__( 'ARMember is not active or its plan classes are not loaded.', 'arsenal-settings' ),
			array( 'status' => 503 )
		);
	}
	$plan_id = (int) $plan_id;
	if ( $plan_id < 1 ) {
		return new WP_Error(
			'invalid_armember_plan_id',
			__( 'armember_plan_id must be a positive integer.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}
	$plan = new ARM_Plan( $plan_id );
	if ( ! $plan->exists() ) {
		return new WP_Error(
			'armember_plan_not_found',
			__( 'No ARMember subscription plan exists for that armember_plan_id.', 'arsenal-settings' ),
			array( 'status' => 404 )
		);
	}
	if ( method_exists( $plan, 'is_deleted' ) && $plan->is_deleted() ) {
		return new WP_Error(
			'armember_plan_deleted',
			__( 'That ARMember plan is deleted.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}
	if ( method_exists( $plan, 'is_active' ) && ! $plan->is_active() ) {
		return new WP_Error(
			'armember_plan_inactive',
			__( 'That ARMember plan is not active.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}
	if ( ! empty( $plan->isGiftPlan ) ) {
		return new WP_Error(
			'armember_plan_not_supported',
			__( 'Gift ARMember plans cannot be used with this endpoint.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}
	if ( ! $plan->is_recurring() ) {
		return new WP_Error(
			'armember_plan_not_recurring',
			__( 'That ARMember plan is not a recurring (subscription) plan. Use a plan with recurring billing only.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	global $arm_payment_gateways;
	$currency = '';
	if ( is_object( $arm_payment_gateways ) && method_exists( $arm_payment_gateways, 'arm_get_global_currency' ) ) {
		$currency = strtolower( trim( (string) $arm_payment_gateways->arm_get_global_currency() ) );
	}
	if ( $currency === '' || ! preg_match( '/^[a-z]{3}$/', $currency ) ) {
		return new WP_Error(
			'armember_currency_missing',
			__( 'Could not read ARMember global currency. Set it in ARMember payment settings.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$payment_cycle = (int) $payment_cycle;
	$rd            = $plan->prepare_recurring_data( $payment_cycle );
	if ( empty( $rd['amount'] ) || (float) $rd['amount'] <= 0 ) {
		return new WP_Error(
			'armember_plan_invalid_amount',
			__( 'That ARMember plan has no positive recurring amount for the selected payment cycle.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$period = isset( $rd['period'] ) ? strtoupper( (string) $rd['period'] ) : 'M';
	$map    = array(
		'D' => 'day',
		'W' => 'week',
		'M' => 'month',
		'Y' => 'year',
	);
	if ( ! isset( $map[ $period ] ) ) {
		return new WP_Error(
			'armember_plan_invalid_interval',
			__( 'That ARMember plan uses a billing period Stripe cannot map (expected D, W, M, or Y).', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}
	$stripe_interval = $map[ $period ];
	$interval_count  = isset( $rd['interval'] ) ? max( 1, (int) $rd['interval'] ) : 1;

	$unit_amount = arsenal_settings_armember_major_amount_to_stripe_unit_amount( $rd['amount'], $currency );
	if ( $unit_amount < 1 ) {
		return new WP_Error(
			'armember_plan_invalid_unit_amount',
			__( 'Could not convert the plan amount to Stripe smallest-currency units.', 'arsenal-settings' ),
			array( 'status' => 400 )
		);
	}

	$name = $plan->name;
	if ( function_exists( 'mb_substr' ) ) {
		$name = mb_substr( $name, 0, 500 );
	} else {
		$name = substr( $name, 0, 500 );
	}

	$inline = array(
		'currency'        => $currency,
		'unit_amount'     => $unit_amount,
		'interval'        => $stripe_interval,
		'interval_count'  => $interval_count,
		'product'         => '',
		'product_name'    => $name,
	);

	$trial_days = null;
	if ( $plan->has_trial_period() && ! empty( $rd['trial'] ) && is_array( $rd['trial'] ) ) {
		$trial_days = arsenal_settings_armember_trial_to_trial_period_days( $rd['trial'] );
	}

	$inline = apply_filters( 'arsenal_settings_armember_plan_stripe_inline', $inline, $plan, $rd, $plan_id, $payment_cycle );

	return array(
		'inline'              => $inline,
		'trial_period_days'   => $trial_days,
		'plan_name'           => $plan->name,
	);
}

/**
 * When no explicit default_payment_method, attach the customer's PaymentMethod from Stripe if present.
 *
 * Skips when the client asks for default_incomplete (intended client-side confirmation only).
 *
 * @param array  $stripe_extra Passed by reference.
 * @param string $customer_id  cus_….
 * @param string $stripe_pb    Trimmed payment_behavior from request (empty means not set).
 */
function arsenal_settings_rest_maybe_apply_customer_default_pm( array &$stripe_extra, $customer_id, $stripe_pb ) {
	if ( isset( $stripe_extra['default_payment_method'] ) ) {
		return;
	}
	if ( 'default_incomplete' === trim( (string) $stripe_pb ) ) {
		return;
	}
	$customer_default_pm = arsenal_settings_stripe_get_customer_default_payment_method_id( $customer_id );
	if ( $customer_default_pm === '' ) {
		$customer_default_pm = arsenal_settings_stripe_get_first_customer_payment_method_id( $customer_id, 'card' );
	}
	if ( $customer_default_pm === '' ) {
		$customer_default_pm = arsenal_settings_stripe_get_first_customer_payment_method_id( $customer_id, 'us_bank_account' );
	}
	if ( $customer_default_pm !== '' ) {
		$stripe_extra['default_payment_method'] = $customer_default_pm;
	}
}

/**
 * Parse default_payment_method and payment_behavior for subscription endpoints.
 *
 * @param WP_REST_Request $request Request.
 * @return array|WP_Error Array with keys extra (array), stripe_pb (string), or WP_Error on invalid pm.
 */
function arsenal_settings_rest_parse_subscription_payment_extras( WP_REST_Request $request ) {
	$stripe_pm = trim( (string) $request->get_param( 'default_payment_method' ) );
	$stripe_pb = trim( (string) $request->get_param( 'payment_behavior' ) );
	$stripe_extra = array();
	if ( $stripe_pm !== '' ) {
		if ( ! preg_match( '/^pm_[a-zA-Z0-9]+$/', $stripe_pm ) ) {
			return new WP_Error(
				'invalid_payment_method_format',
				__( 'Optional "default_payment_method" must be a Stripe PaymentMethod id (pm_…).', 'arsenal-settings' ),
				array( 'status' => 400 )
			);
		}
		$stripe_extra['default_payment_method'] = $stripe_pm;
	}
	if ( $stripe_pb !== '' ) {
		$stripe_extra['payment_behavior'] = $stripe_pb;
	}
	return array(
		'extra'     => $stripe_extra,
		'stripe_pb' => $stripe_pb,
	);
}

/**
 * Merge billing_cycle_anchor / trial_period_days from the request into Stripe subscription $extra.
 *
 * @param WP_REST_Request $request Request.
 * @param array           $stripe_extra Passed by reference.
 * @return null|WP_Error Null on success.
 */
function arsenal_settings_rest_merge_subscription_schedule_to_extra( WP_REST_Request $request, array &$stripe_extra ) {
	if ( $request->has_param( 'billing_cycle_anchor' ) ) {
		$a = (int) $request->get_param( 'billing_cycle_anchor' );
		if ( $a < 1 ) {
			return new WP_Error(
				'invalid_billing_cycle_anchor',
				__( 'billing_cycle_anchor must be a positive Unix timestamp.', 'arsenal-settings' ),
				array( 'status' => 400 )
			);
		}
		$stripe_extra['billing_cycle_anchor'] = $a;
	}
	if ( $request->has_param( 'trial_period_days' ) ) {
		$d = (int) $request->get_param( 'trial_period_days' );
		if ( $d < 0 || $d > 730 ) {
			return new WP_Error(
				'invalid_trial_period_days',
				__( 'trial_period_days must be between 0 and 730.', 'arsenal-settings' ),
				array( 'status' => 400 )
			);
		}
		$stripe_extra['trial_period_days'] = $d;
	}
	return null;
}

/**
 * Read an integer unix field from a Stripe subscription array for JSON output.
 *
 * @param array  $row Subscription hash from Stripe.
 * @param string $key Field name.
 * @return int|null
 */
function arsenal_settings_rest_subscription_int_field( array $row, $key ) {
	return ( array_key_exists( $key, $row ) && null !== $row[ $key ] && '' !== $row[ $key ] ) ? (int) $row[ $key ] : null;
}

/**
 * Normalize Stripe Customer id from a subscription payload field.
 *
 * @param mixed $customer_field customer string or expanded object.
 * @return string cus_… or empty.
 */
function arsenal_settings_rest_normalize_stripe_customer_id( $customer_field ) {
	if ( is_string( $customer_field ) && preg_match( '/^cus_[a-zA-Z0-9]+$/', $customer_field ) ) {
		return $customer_field;
	}
	if ( is_array( $customer_field ) && ! empty( $customer_field['id'] ) && preg_match( '/^cus_[a-zA-Z0-9]+$/', (string) $customer_field['id'] ) ) {
		return (string) $customer_field['id'];
	}
	return '';
}

/**
 * Build success or error REST response after Stripe subscription create (shared shape).
 *
 * @param array|WP_Error $result           Decoded subscription from Stripe or WP_Error.
 * @param array          $response_options Optional. skip_invoice_automation (bool): do not finalize/pay invoices on this response (deferred first charge).
 * @return WP_REST_Response
 */
function arsenal_settings_rest_subscription_created_response( $result, array $response_options = array() ) {
	if ( is_wp_error( $result ) ) {
		return arsenal_settings_rest_from_wp_error( $result );
	}

	list( $pi_client_secret, $pi_status ) = arsenal_settings_stripe_resolve_subscription_payment_intent( $result );

	// Re-load the subscription from Stripe so the payload includes full billing fields (create responses are
	// sometimes sparse; we previously only merged this refetch when a PaymentIntent secret existed, which hid
	// current_period_end / items for active subscriptions).
	if ( ! empty( $result['id'] ) && preg_match( '/^sub_[a-zA-Z0-9]+$/', (string) $result['id'] ) ) {
		$refetched = arsenal_settings_stripe_get_subscription(
			(string) $result['id'],
			array(
				'latest_invoice.payment_intent',
				'items.data.price',
			)
		);
		if ( ! is_wp_error( $refetched ) && isset( $refetched['object'] ) && 'subscription' === $refetched['object'] ) {
			list( $pi2, $st2 ) = arsenal_settings_stripe_resolve_subscription_payment_intent( $refetched );
			if ( ( null === $pi_client_secret || '' === $pi_client_secret ) && null !== $pi2 && '' !== $pi2 ) {
				$pi_client_secret = $pi2;
				$pi_status         = $st2;
			}
			$result = $refetched;
		}
	}

	$latest_invoice_id = null;
	if ( isset( $result['latest_invoice'] ) ) {
		$latest_invoice_id = is_array( $result['latest_invoice'] ) && isset( $result['latest_invoice']['id'] )
			? (string) $result['latest_invoice']['id']
			: (string) $result['latest_invoice'];
	}

	$invoice_summary = array(
		'id'            => $latest_invoice_id,
		'status'        => null,
		'amount_due'    => null,
		'amount_paid'   => null,
		'pay_attempted' => false,
		'pay_error'     => null,
		'pay_error_code' => null,
	);

	$customer_str = arsenal_settings_rest_normalize_stripe_customer_id( isset( $result['customer'] ) ? $result['customer'] : null );

	$skip_invoice_automation = ! empty( $response_options['skip_invoice_automation'] );

	if (
		$latest_invoice_id
		&& preg_match( '/^in_[a-zA-Z0-9]+$/', $latest_invoice_id )
		&& $customer_str !== ''
	) {
		$inv_path = arsenal_settings_stripe_path_with_query(
			'invoices/' . rawurlencode( $latest_invoice_id ),
			array( 'expand' => array( 'payment_intent' ) )
		);
		$inv = arsenal_settings_stripe_api_get( $inv_path );
		if ( ! is_wp_error( $inv ) && isset( $inv['object'] ) && 'invoice' === $inv['object'] ) {
			$invoice_summary['status']      = isset( $inv['status'] ) ? (string) $inv['status'] : null;
			$invoice_summary['amount_due']  = isset( $inv['amount_due'] ) ? (int) $inv['amount_due'] : null;
			$invoice_summary['amount_paid'] = isset( $inv['amount_paid'] ) ? (int) $inv['amount_paid'] : null;

			if ( ! $skip_invoice_automation ) {
				if ( isset( $inv['status'] ) && 'draft' === $inv['status'] ) {
					$finalized = arsenal_settings_stripe_api_post( 'invoices/' . rawurlencode( $latest_invoice_id ) . '/finalize', array() );
					if ( ! is_wp_error( $finalized ) && isset( $finalized['object'] ) && 'invoice' === $finalized['object'] ) {
						$inv = $finalized;
						$invoice_summary['status']      = isset( $inv['status'] ) ? (string) $inv['status'] : null;
						$invoice_summary['amount_due']  = isset( $inv['amount_due'] ) ? (int) $inv['amount_due'] : null;
						$invoice_summary['amount_paid'] = isset( $inv['amount_paid'] ) ? (int) $inv['amount_paid'] : null;
					}
				}

				$due = isset( $inv['amount_due'] ) ? (int) $inv['amount_due'] : 0;
				if ( isset( $inv['status'] ) && 'open' === $inv['status'] && $due > 0 ) {
					$pm_pay = arsenal_settings_stripe_resolve_payment_method_for_charge( $customer_str, $result );
					if ( $pm_pay !== '' ) {
						$invoice_summary['pay_attempted'] = true;
						$paid                             = arsenal_settings_stripe_invoice_pay( $latest_invoice_id, $pm_pay );
						if ( is_wp_error( $paid ) ) {
							$invoice_summary['pay_error'] = $paid->get_error_message();
							$edata                        = $paid->get_error_data();
							if ( is_array( $edata ) && ! empty( $edata['stripe_error']['code'] ) ) {
								$invoice_summary['pay_error_code'] = (string) $edata['stripe_error']['code'];
							}
							list( $pi_client_secret, $pi_status ) = arsenal_settings_stripe_resolve_subscription_payment_intent( $result );
						} elseif ( is_array( $paid ) ) {
							$invoice_summary['status']      = isset( $paid['status'] ) ? (string) $paid['status'] : $invoice_summary['status'];
							$invoice_summary['amount_paid'] = isset( $paid['amount_paid'] ) ? (int) $paid['amount_paid'] : $invoice_summary['amount_paid'];
							$invoice_summary['amount_due']  = isset( $paid['amount_due'] ) ? (int) $paid['amount_due'] : $invoice_summary['amount_due'];
							if ( ! empty( $paid['payment_intent'] ) && is_array( $paid['payment_intent'] ) && isset( $paid['payment_intent']['status'] ) ) {
								$pi_status = (string) $paid['payment_intent']['status'];
							}
							if ( ! empty( $result['id'] ) && preg_match( '/^sub_[a-zA-Z0-9]+$/', (string) $result['id'] ) ) {
								$refetched2 = arsenal_settings_stripe_get_subscription(
									(string) $result['id'],
									array(
										'latest_invoice.payment_intent',
										'items.data.price',
									)
								);
								if ( ! is_wp_error( $refetched2 ) && isset( $refetched2['object'] ) && 'subscription' === $refetched2['object'] ) {
									$result = $refetched2;
									list( $pi2, $st2 ) = arsenal_settings_stripe_resolve_subscription_payment_intent( $result );
									if ( ( null === $pi_client_secret || '' === $pi_client_secret ) && null !== $pi2 && '' !== $pi2 ) {
										$pi_client_secret = $pi2;
										$pi_status         = $st2;
									} elseif ( $pi_status === null || '' === (string) $pi_status ) {
										$pi_status = $st2;
									}
								}
							}
						}
					}
				}
			}
		}
	}

	if (
		! $skip_invoice_automation
		&& $latest_invoice_id
		&& preg_match( '/^in_[a-zA-Z0-9]+$/', $latest_invoice_id )
		&& isset( $invoice_summary['status'] )
		&& 'open' === $invoice_summary['status']
		&& isset( $invoice_summary['amount_due'] )
		&& (int) $invoice_summary['amount_due'] > 0
		&& ! $invoice_summary['pay_attempted']
	) {
		$invoice_summary['hint'] = __( 'No usable PaymentMethod was found: set the customer default in Stripe or pass default_payment_method (pm_…) so the open invoice can be paid automatically.', 'arsenal-settings' );
	} elseif (
		$skip_invoice_automation
		&& $latest_invoice_id
		&& preg_match( '/^in_[a-zA-Z0-9]+$/', $latest_invoice_id )
		&& isset( $invoice_summary['status'] )
		&& 'open' === $invoice_summary['status']
		&& isset( $invoice_summary['amount_due'] )
		&& (int) $invoice_summary['amount_due'] > 0
	) {
		$invoice_summary['hint'] = __( 'Initial collection was skipped on purpose: pay or confirm this invoice when ready; recurring charges follow the Stripe billing schedule.', 'arsenal-settings' );
	}

	$sub_status = isset( $result['status'] ) ? (string) $result['status'] : '';
	$message    = __( 'The subscription has been created.', 'arsenal-settings' );
	if ( 'paid' === (string) ( $invoice_summary['status'] ?? '' ) ) {
		$message = __( 'The subscription invoice was paid successfully.', 'arsenal-settings' );
	} elseif ( 'succeeded' === (string) $pi_status ) {
		$message = __( 'Payment succeeded for the subscription invoice.', 'arsenal-settings' );
	} elseif ( 'incomplete' === $sub_status && $pi_client_secret ) {
		$message = __( 'The subscription has been created. Complete payment with Stripe (use the payment_intent client_secret on the client).', 'arsenal-settings' );
	} elseif ( in_array( $sub_status, array( 'active', 'trialing' ), true ) ) {
		$message = __( 'The subscription is active: recurring billing is attached to this customer in Stripe.', 'arsenal-settings' );
	}

	$item_price_id = null;
	if ( ! empty( $result['items']['data'] ) && is_array( $result['items']['data'] ) ) {
		$first = $result['items']['data'][0];
		if ( isset( $first['price'] ) ) {
			$pr = $first['price'];
			if ( is_string( $pr ) && preg_match( '/^price_[a-zA-Z0-9]+$/', $pr ) ) {
				$item_price_id = $pr;
			} elseif ( is_array( $pr ) && ! empty( $pr['id'] ) && preg_match( '/^price_[a-zA-Z0-9]+$/', (string) $pr['id'] ) ) {
				$item_price_id = (string) $pr['id'];
			}
		}
	}

	$body = array(
		'message'                      => $message,
		'status'                       => true,
		'code'                         => 'created',
		'payment_intent_client_secret' => $pi_client_secret,
		'payment_intent_status'        => $pi_status,
		'latest_invoice'               => $invoice_summary,
		'subscription'                 => array(
			'id'                     => isset( $result['id'] ) ? $result['id'] : '',
			'object'                 => isset( $result['object'] ) ? $result['object'] : 'subscription',
			'customer'               => $customer_str !== '' ? $customer_str : ( isset( $result['customer'] ) ? $result['customer'] : '' ),
			'status'                 => $sub_status,
			'currency'               => isset( $result['currency'] ) ? $result['currency'] : '',
			'livemode'               => isset( $result['livemode'] ) ? (bool) $result['livemode'] : false,
			'created'                => isset( $result['created'] ) ? (int) $result['created'] : 0,
			'collection_method'      => isset( $result['collection_method'] ) ? $result['collection_method'] : '',
			'latest_invoice_id'      => $latest_invoice_id,
			'default_payment_method' => isset( $result['default_payment_method'] ) ? $result['default_payment_method'] : null,
			'billing_cycle_anchor'   => arsenal_settings_rest_subscription_int_field( $result, 'billing_cycle_anchor' ),
			'current_period_start'   => arsenal_settings_rest_subscription_int_field( $result, 'current_period_start' ),
			'current_period_end'     => arsenal_settings_rest_subscription_int_field( $result, 'current_period_end' ),
			'trial_end'              => arsenal_settings_rest_subscription_int_field( $result, 'trial_end' ),
			'item_price_id'          => $item_price_id,
		),
	);

	return new WP_REST_Response( $body, 201 );
}

/**
 * REST callback: check-user-subscription.
 *
 * @return WP_REST_Response
 */
function arsenal_settings_rest_check_user_subscription() {
	$body = array(
		'message' => 'Hello world',
		'status'  => true,
		'code'    => 'ok',

	);

	return new WP_REST_Response( $body, 200 );
}

/**
 * REST callback: create-subscription — creates a Stripe Subscription.
 *
 * POST JSON or form body:
 * - price (required): Stripe Price id price_…
 * - customer (optional): Stripe Customer id cus_… (not a WordPress username)
 * - customer_email (optional): if set, looks up cus_… in Stripe when customer is omitted
 * - quantity (optional)
 * - default_payment_method (optional): pm_… on the customer to charge immediately
 * - payment_behavior (optional): override Stripe behavior (usually omit; defaults to allow_incomplete + off_session for automatic payment).
 *   When both payment_behavior and default_payment_method are omitted, the customer’s saved PaymentMethod (default or first attached) is applied when found.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_create_subscription( WP_REST_Request $request ) {
	$raw_customer = trim( (string) $request->get_param( 'customer' ) );
	$email        = trim( (string) $request->get_param( 'customer_email' ) );
	$price        = trim( (string) $request->get_param( 'price' ) );
	$quantity     = $request->get_param( 'quantity' );

	$parsed = arsenal_settings_rest_parse_subscription_payment_extras( $request );
	if ( is_wp_error( $parsed ) ) {
		return arsenal_settings_rest_from_wp_error( $parsed );
	}
	$stripe_extra = $parsed['extra'];
	$stripe_pb    = $parsed['stripe_pb'];

	$sched_err = arsenal_settings_rest_merge_subscription_schedule_to_extra( $request, $stripe_extra );
	if ( is_wp_error( $sched_err ) ) {
		return arsenal_settings_rest_from_wp_error( $sched_err );
	}

	$customer_res = arsenal_settings_rest_get_stripe_customer_id( $raw_customer, $email );
	if ( is_wp_error( $customer_res ) ) {
		return arsenal_settings_rest_from_wp_error( $customer_res );
	}
	$customer_id = $customer_res;

	if ( $price === '' || ! preg_match( '/^price_[a-zA-Z0-9]+$/', $price ) ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'The "price" value must be a Stripe recurring Price ID (starts with price_).', 'arsenal-settings' ),
				'status'  => false,
				'code'    => 'invalid_price_format',
			),
			400
		);
	}

	arsenal_settings_rest_maybe_apply_customer_default_pm( $stripe_extra, $customer_id, $stripe_pb );

	$result = arsenal_settings_stripe_create_subscription( $customer_id, $price, $quantity, $stripe_extra );

	return arsenal_settings_rest_subscription_created_response( $result );
}

/**
 * REST callback: create-subscription-custom — recurring subscription without a pre-existing Stripe Price.
 *
 * POST JSON or form body:
 * - customer or customer_email (same as create-subscription)
 * - currency (required): e.g. gbp, usd
 * - unit_amount (required): integer, smallest currency unit (e.g. pence)
 * - interval (required): day | week | month | year
 * - interval_count (optional): default 1
 * - product (optional): existing Stripe Product id prod_… (used as price_data.product; skips Product creation)
 * - product_name (optional unless product omitted): if no prod_…, a Product is created with this name, then Price + Subscription
 * - quantity (optional)
 * - default_payment_method, payment_behavior (optional, same as create-subscription)
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_create_subscription_custom( WP_REST_Request $request ) {
	$raw_customer = trim( (string) $request->get_param( 'customer' ) );
	$email        = trim( (string) $request->get_param( 'customer_email' ) );
	$quantity     = $request->get_param( 'quantity' );

	$parsed = arsenal_settings_rest_parse_subscription_payment_extras( $request );
	if ( is_wp_error( $parsed ) ) {
		return arsenal_settings_rest_from_wp_error( $parsed );
	}
	$stripe_extra = $parsed['extra'];
	$stripe_pb    = $parsed['stripe_pb'];

	$sched_err = arsenal_settings_rest_merge_subscription_schedule_to_extra( $request, $stripe_extra );
	if ( is_wp_error( $sched_err ) ) {
		return arsenal_settings_rest_from_wp_error( $sched_err );
	}

	$customer_res = arsenal_settings_rest_get_stripe_customer_id( $raw_customer, $email );
	if ( is_wp_error( $customer_res ) ) {
		return arsenal_settings_rest_from_wp_error( $customer_res );
	}
	$customer_id = $customer_res;

	$inline = array(
		'currency'       => (string) $request->get_param( 'currency' ),
		'unit_amount'    => (int) $request->get_param( 'unit_amount' ),
		'interval'       => (string) $request->get_param( 'interval' ),
		'interval_count' => (int) $request->get_param( 'interval_count' ),
		'product'        => trim( (string) $request->get_param( 'product' ) ),
		'product_name'   => (string) $request->get_param( 'product_name' ),
	);

	arsenal_settings_rest_maybe_apply_customer_default_pm( $stripe_extra, $customer_id, $stripe_pb );

	$result = arsenal_settings_stripe_create_subscription_inline_price( $customer_id, $quantity, $inline, $stripe_extra );

	return arsenal_settings_rest_subscription_created_response( $result );
}

/**
 * REST callback: create-recurring-subscription — puts a Stripe Customer on a recurring billing schedule (Subscription).
 *
 * For email-only clients, use POST …/create-recurring-subscription-by-email (requires customer_email; same plan and payment fields).
 * For ARMember-driven pricing, use POST …/create-recurring-subscription-by-armember-plan (customer_email + armember_plan_id).
 * For the same without collecting the first payment on the API call, use …/create-recurring-subscription-by-armember-plan-deferred.
 *
 * Stripe models ongoing charges as a **Subscription** (recurring invoices / PaymentIntents). This endpoint is the
 * single entry point: use a catalog **price** (price_…), *or* define **currency**, **unit_amount**, **interval** plus
 * **product** or **product_name** for a custom recurring amount (Product is created in Stripe when needed).
 *
 * Optional timing (see Stripe subscription create):
 * - billing_cycle_anchor: Unix timestamp to align billing cycles
 * - trial_period_days: free trial before the first full charge (0–730)
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_create_recurring_subscription( WP_REST_Request $request ) {
	$raw_customer = trim( (string) $request->get_param( 'customer' ) );
	$email        = trim( (string) $request->get_param( 'customer_email' ) );
	$price        = trim( (string) $request->get_param( 'price' ) );
	$quantity     = $request->get_param( 'quantity' );

	$parsed = arsenal_settings_rest_parse_subscription_payment_extras( $request );
	if ( is_wp_error( $parsed ) ) {
		return arsenal_settings_rest_from_wp_error( $parsed );
	}
	$stripe_extra = $parsed['extra'];
	$stripe_pb    = $parsed['stripe_pb'];

	$sched_err = arsenal_settings_rest_merge_subscription_schedule_to_extra( $request, $stripe_extra );
	if ( is_wp_error( $sched_err ) ) {
		return arsenal_settings_rest_from_wp_error( $sched_err );
	}

	$customer_res = arsenal_settings_rest_get_stripe_customer_id( $raw_customer, $email );
	if ( is_wp_error( $customer_res ) ) {
		return arsenal_settings_rest_from_wp_error( $customer_res );
	}
	$customer_id = $customer_res;

	arsenal_settings_rest_maybe_apply_customer_default_pm( $stripe_extra, $customer_id, $stripe_pb );

	$use_catalog_price = ( $price !== '' && preg_match( '/^price_[a-zA-Z0-9]+$/', $price ) );

	$currency    = strtolower( trim( (string) $request->get_param( 'currency' ) ) );
	$interval    = strtolower( trim( (string) $request->get_param( 'interval' ) ) );
	$unit_amount = $request->get_param( 'unit_amount' );
	$has_unit    = null !== $unit_amount && '' !== (string) $unit_amount;
	$unit_int    = $has_unit ? (int) $unit_amount : 0;

	$use_custom_amount = ( $currency !== '' && preg_match( '/^[a-z]{3}$/', $currency ) && $has_unit && $unit_int >= 1 && $interval !== '' );

	if ( $use_catalog_price && $use_custom_amount ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'Send either "price" (price_…) for a catalog plan, or currency/unit_amount/interval for a custom recurring amount—not both.', 'arsenal-settings' ),
				'status'  => false,
				'code'    => 'ambiguous_plan',
			),
			400
		);
	}

	if ( $use_catalog_price ) {
		$result = arsenal_settings_stripe_create_subscription( $customer_id, $price, $quantity, $stripe_extra );
		return arsenal_settings_rest_subscription_created_response( $result );
	}

	if ( $use_custom_amount ) {
		$inline = array(
			'currency'       => $currency,
			'unit_amount'    => $unit_int,
			'interval'       => $interval,
			'interval_count' => (int) $request->get_param( 'interval_count' ),
			'product'        => trim( (string) $request->get_param( 'product' ) ),
			'product_name'   => (string) $request->get_param( 'product_name' ),
		);
		$result = arsenal_settings_stripe_create_subscription_inline_price( $customer_id, $quantity, $inline, $stripe_extra );
		return arsenal_settings_rest_subscription_created_response( $result );
	}

	return new WP_REST_Response(
		array(
			'message' => __( 'Provide a recurring plan: either "price" (Stripe recurring price_…), or "currency", "unit_amount", "interval", and "product" or "product_name".', 'arsenal-settings' ),
			'status'  => false,
			'code'    => 'missing_plan',
		),
		400
	);
}

/**
 * REST callback: create-recurring-subscription-by-email — same as create-recurring-subscription but customer is resolved only from customer_email.
 *
 * Looks up the Stripe Customer by email (no auto-create; create the Customer in Stripe first if missing). Ignores any "customer" body field.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_create_recurring_subscription_by_email( WP_REST_Request $request ) {
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
	$request->set_param( 'customer', '' );
	$request->set_param( 'customer_email', $email );

	return arsenal_settings_rest_create_recurring_subscription( $request );
}

/**
 * REST callback: create-recurring-subscription-by-armember-plan — recurring Stripe subscription from ARMember plan + customer email.
 *
 * Resolves currency, amount, and billing interval from the ARMember plan (recurring subscription plans only). Uses the
 * ARMember global currency. Optional payment_cycle selects a multi-cycle plan row. Trial on the plan maps to
 * trial_period_days when the request does not send trial_period_days (month/year trials are approximate days).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_create_recurring_subscription_by_armember_plan( WP_REST_Request $request ) {
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

	$resolved = arsenal_settings_rest_resolve_armember_plan_for_stripe_inline( $plan_id, $payment_cycle );
	if ( is_wp_error( $resolved ) ) {
		return arsenal_settings_rest_from_wp_error( $resolved );
	}

	$parsed = arsenal_settings_rest_parse_subscription_payment_extras( $request );
	if ( is_wp_error( $parsed ) ) {
		return arsenal_settings_rest_from_wp_error( $parsed );
	}
	$stripe_extra = $parsed['extra'];
	$stripe_pb    = $parsed['stripe_pb'];

	$sched_err = arsenal_settings_rest_merge_subscription_schedule_to_extra( $request, $stripe_extra );
	if ( is_wp_error( $sched_err ) ) {
		return arsenal_settings_rest_from_wp_error( $sched_err );
	}

	if ( ! $request->has_param( 'trial_period_days' )
		&& isset( $resolved['trial_period_days'] )
		&& null !== $resolved['trial_period_days']
		&& (int) $resolved['trial_period_days'] > 0 ) {
		$stripe_extra['trial_period_days'] = (int) $resolved['trial_period_days'];
	}

	$customer_res = arsenal_settings_rest_get_stripe_customer_id( '', $email );
	if ( is_wp_error( $customer_res ) ) {
		return arsenal_settings_rest_from_wp_error( $customer_res );
	}
	$customer_id = $customer_res;

	$quantity = $request->get_param( 'quantity' );
	if ( null === $quantity || '' === $quantity ) {
		$quantity = 1;
	}
	$quantity = max( 1, (int) $quantity );

	arsenal_settings_rest_maybe_apply_customer_default_pm( $stripe_extra, $customer_id, $stripe_pb );

	$result = arsenal_settings_stripe_create_subscription_inline_price(
		$customer_id,
		$quantity,
		$resolved['inline'],
		$stripe_extra
	);

	return arsenal_settings_rest_subscription_created_response( $result );
}

/**
 * REST callback: create-recurring-subscription-by-armember-plan-deferred — same plan resolution as by-armember-plan, but no initial charge on this request.
 *
 * Uses payment_behavior default_incomplete, omits default_payment_method on create (saved PMs are not charged here), and by
 * default sets trial_period_days to one approximate billing period so the first paid charge aligns with the first renewal
 * (set defer_first_billing_period false and pass trial_period_days yourself to customize). Invoice finalize/pay automation
 * in the success response is skipped so this call does not collect the first payment.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_create_recurring_subscription_by_armember_plan_deferred( WP_REST_Request $request ) {
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

	$resolved = arsenal_settings_rest_resolve_armember_plan_for_stripe_inline( $plan_id, $payment_cycle );
	if ( is_wp_error( $resolved ) ) {
		return arsenal_settings_rest_from_wp_error( $resolved );
	}

	$parsed = arsenal_settings_rest_parse_subscription_payment_extras( $request );
	if ( is_wp_error( $parsed ) ) {
		return arsenal_settings_rest_from_wp_error( $parsed );
	}
	$stripe_extra = $parsed['extra'];

	$sched_err = arsenal_settings_rest_merge_subscription_schedule_to_extra( $request, $stripe_extra );
	if ( is_wp_error( $sched_err ) ) {
		return arsenal_settings_rest_from_wp_error( $sched_err );
	}

	$defer_first = true;
	if ( $request->has_param( 'defer_first_billing_period' ) ) {
		$defer_first = function_exists( 'rest_sanitize_boolean' )
			? (bool) rest_sanitize_boolean( $request->get_param( 'defer_first_billing_period' ) )
			: (bool) $request->get_param( 'defer_first_billing_period' );
	}

	if ( ! $request->has_param( 'trial_period_days' ) ) {
		$arm_trial = isset( $resolved['trial_period_days'] ) && null !== $resolved['trial_period_days']
			? (int) $resolved['trial_period_days']
			: 0;
		$defer_days = $defer_first ? arsenal_settings_stripe_inline_price_to_deferral_trial_days( $resolved['inline'] ) : 0;
		$combined   = min( 730, max( $defer_days, $arm_trial ) );
		if ( $combined > 0 ) {
			$stripe_extra['trial_period_days'] = $combined;
		}
	}

	$stripe_extra['payment_behavior'] = 'default_incomplete';
	unset( $stripe_extra['default_payment_method'] );

	$customer_res = arsenal_settings_rest_get_stripe_customer_id( '', $email );
	if ( is_wp_error( $customer_res ) ) {
		return arsenal_settings_rest_from_wp_error( $customer_res );
	}
	$customer_id = $customer_res;

	$quantity = $request->get_param( 'quantity' );
	if ( null === $quantity || '' === $quantity ) {
		$quantity = 1;
	}
	$quantity = max( 1, (int) $quantity );

	$result = arsenal_settings_stripe_create_subscription_inline_price(
		$customer_id,
		$quantity,
		$resolved['inline'],
		$stripe_extra
	);

	return arsenal_settings_rest_subscription_created_response(
		$result,
		array( 'skip_invoice_automation' => true )
	);
}

/**
 * REST callback: create-payment — creates a Stripe PaymentIntent (one-off charge) for a customer.
 *
 * POST JSON or form body:
 * - customer (optional): Stripe Customer id cus_…
 * - customer_email (optional): looks up cus_… when customer is omitted; if none exists, a new Stripe Customer is created for that email
 * - customer_name (optional): display name when auto-creating a Customer from customer_email
 * - amount (required): integer, smallest currency unit (e.g. pence)
 * - currency (required): e.g. gbp, usd
 * - payment_method_id (optional if customer already has a saved pm_… or sends card fields): Stripe PaymentMethod id pm_…
 *   Alias: payment_method (same value).
 * - card_number, card_expiry, card_cvc (optional together): when no pm_… and no saved card, these create a Stripe PaymentMethod
 *   server-side and attach it to the customer, then charge. Optional: cardholder_name, billing_postal_code.
 *   With sk_test_…, standard test numbers (e.g. 4242424242424242) use Stripe test tokens (tok_visa) so raw card APIs need not be enabled.
 *   Filter `arsenal_settings_allow_create_payment_with_card_fields` (default true) can be set false to forbid raw card fields.
 *   On payment_method_required, the JSON lists fields_to_enter if nothing usable was sent.
 *   HTTP 201 is returned only when the PaymentIntent status is succeeded; otherwise HTTP 402 with payment_intent details.
 * - setup_future_usage (optional): off_session (default) or on_session; sent on the PaymentIntent when a PaymentMethod is used
 *   so Stripe persists it on the Customer for future charges. Stripe requires on-session confirmation with this flag, so the
 *   plugin sends off_session=false on confirm when setup_future_usage is present (even for server-side confirm).
 * - return_url (optional): HTTPS URL Stripe may use after redirect-based authentication; defaults to the site home URL.
 * - description (optional)
 * - receipt_email (optional)
 * - metadata (optional): object of string key => string value (max 40 keys)
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_create_payment( WP_REST_Request $request ) {
	$raw_customer = trim( (string) $request->get_param( 'customer' ) );
	$email        = trim( (string) $request->get_param( 'customer_email' ) );
	$customer_name = trim( (string) $request->get_param( 'customer_name' ) );

	$customer_res = arsenal_settings_rest_get_stripe_customer_id( $raw_customer, $email, true, $customer_name );
	if ( is_wp_error( $customer_res ) ) {
		return arsenal_settings_rest_from_wp_error( $customer_res );
	}
	$customer_id = $customer_res;

	$amount   = (int) $request->get_param( 'amount' );
	$currency = (string) $request->get_param( 'currency' );

	$pm_request = trim( (string) $request->get_param( 'payment_method_id' ) );
	if ( $pm_request === '' ) {
		$pm_request = trim( (string) $request->get_param( 'payment_method' ) );
	}
	if ( $pm_request !== '' && ! preg_match( '/^pm_[a-zA-Z0-9]+$/', $pm_request ) ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'payment_method_id (or payment_method) must be a Stripe PaymentMethod id (pm_…).', 'arsenal-settings' ),
				'status'  => false,
				'code'    => 'invalid_payment_method_format',
			),
			400
		);
	}

	$card_number = trim( (string) $request->get_param( 'card_number' ) );
	$card_expiry = trim( (string) $request->get_param( 'card_expiry' ) );
	$card_cvc    = trim( (string) $request->get_param( 'card_cvc' ) );
	$any_card    = ( $card_number !== '' || $card_expiry !== '' || $card_cvc !== '' );

	if ( $pm_request === '' && $any_card ) {
		$allow_raw = (bool) apply_filters( 'arsenal_settings_allow_create_payment_with_card_fields', true );
		if ( ! $allow_raw ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'Raw card fields on create-payment are disabled. Use payment_method_id from Stripe.js, or allow this path with the arsenal_settings_allow_create_payment_with_card_fields filter.', 'arsenal-settings' ),
					'status'  => false,
					'code'    => 'raw_card_disabled',
				),
				400
			);
		}

		$missing = array();
		if ( $card_number === '' ) {
			$missing[] = 'card_number';
		}
		if ( $card_expiry === '' ) {
			$missing[] = 'card_expiry';
		}
		if ( $card_cvc === '' ) {
			$missing[] = 'card_cvc';
		}
		if ( ! empty( $missing ) ) {
			return new WP_REST_Response(
				array(
					'message'             => __( 'Incomplete card details: send card_number, card_expiry, and card_cvc together.', 'arsenal-settings' ),
					'status'              => false,
					'code'                => 'incomplete_card_details',
					'missing_field_names' => $missing,
				),
				400
			);
		}

		$digits = preg_replace( '/\D+/', '', $card_number );
		if ( strlen( $digits ) < 12 || strlen( $digits ) > 19 ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'card_number must contain 12–19 digits.', 'arsenal-settings' ),
					'status'  => false,
					'code'    => 'invalid_card_number',
				),
				400
			);
		}

		$parsed = arsenal_settings_parse_card_expiry( $card_expiry );
		if ( null === $parsed ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'card_expiry could not be parsed. Use MM/YY or MM/YYYY (for example 12/2029).', 'arsenal-settings' ),
					'status'  => false,
					'code'    => 'invalid_card_expiry',
				),
				400
			);
		}

		$cvc_digits = preg_replace( '/\D+/', '', $card_cvc );
		if ( strlen( $cvc_digits ) < 3 || strlen( $cvc_digits ) > 4 ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'card_cvc must be 3 or 4 digits.', 'arsenal-settings' ),
					'status'  => false,
					'code'    => 'invalid_card_cvc',
				),
				400
			);
		}

		$cardholder = trim( (string) $request->get_param( 'cardholder_name' ) );
		$postal     = trim( (string) $request->get_param( 'billing_postal_code' ) );

		$pm_from_card = arsenal_settings_stripe_create_and_attach_card_payment_method(
			$customer_id,
			array(
				'number'          => $digits,
				'exp_month'       => $parsed['exp_month'],
				'exp_year'        => $parsed['exp_year'],
				'cvc'             => $cvc_digits,
				'cardholder_name' => $cardholder,
				'postal_code'     => $postal,
			)
		);
		if ( is_wp_error( $pm_from_card ) ) {
			return arsenal_settings_rest_from_wp_error( $pm_from_card );
		}
		$pm_request = $pm_from_card;
	}

	$pm_saved = '';
	if ( $pm_request === '' ) {
		$pm_saved = arsenal_settings_stripe_resolve_payment_method_for_charge( $customer_id, array() );
	}

	if ( $amount >= 1 && $pm_request === '' && $pm_saved === '' ) {
		$fields_to_enter = array(
			array(
				'name'     => 'card_number',
				'label'    => __( 'Card number', 'arsenal-settings' ),
				'required' => true,
			),
			array(
				'name'     => 'card_expiry',
				'label'    => __( 'Expiry date (MM / YY)', 'arsenal-settings' ),
				'required' => true,
			),
			array(
				'name'     => 'card_cvc',
				'label'    => __( 'Security code (CVC)', 'arsenal-settings' ),
				'required' => true,
			),
			array(
				'name'     => 'cardholder_name',
				'label'    => __( 'Name on card', 'arsenal-settings' ),
				'required' => false,
			),
			array(
				'name'     => 'billing_postal_code',
				'label'    => __( 'Billing postal or ZIP code', 'arsenal-settings' ),
				'required' => false,
			),
		);

		return new WP_REST_Response(
			array(
				'message' => __( 'No saved payment method: send card_number, card_expiry, and card_cvc together, or payment_method_id (pm_…).', 'arsenal-settings' ),
				'status'  => false,
				'code'    => 'payment_method_required',
				'fields_to_enter'     => $fields_to_enter,
				'field_names_to_enter' => array_map(
					static function ( $row ) {
						return $row['name'];
					},
					$fields_to_enter
				),
				'request_field_after_token' => array(
					'primary' => 'payment_method_id',
					'aliases' => array( 'payment_method' ),
					'format'  => 'pm_*',
				),
			),
			400
		);
	}

	$pm = $pm_request !== '' ? $pm_request : $pm_saved;

	$metadata = $request->get_param( 'metadata' );
	if ( ! is_array( $metadata ) ) {
		$metadata = array();
	}

	// One-off charge: confirm with a pm when present; off_session on the PI follows Stripe rules inside create_payment_intent.
	$confirm     = ( $pm !== '' );
	$off_session = ( $pm !== '' );

	$intent_args = array(
		'customer'       => $customer_id,
		'amount'         => $amount,
		'currency'       => $currency,
		'payment_method' => $pm,
		'description'    => (string) $request->get_param( 'description' ),
		'receipt_email'  => (string) $request->get_param( 'receipt_email' ),
		'confirm'        => $confirm,
		'off_session'    => $off_session,
		'metadata'       => $metadata,
	);
	$setup_fu = $request->get_param( 'setup_future_usage' );
	if ( null !== $setup_fu && $setup_fu !== '' ) {
		$intent_args['setup_future_usage'] = trim( (string) $setup_fu );
	}
	$return_u = $request->get_param( 'return_url' );
	if ( null !== $return_u && trim( (string) $return_u ) !== '' ) {
		$intent_args['return_url'] = trim( (string) $return_u );
	}

	$result = arsenal_settings_stripe_create_payment_intent( $intent_args );

	if ( is_wp_error( $result ) ) {
		return arsenal_settings_rest_from_wp_error( $result );
	}

	$pi_status     = isset( $result['status'] ) ? (string) $result['status'] : '';
	$client_secret = isset( $result['client_secret'] ) ? (string) $result['client_secret'] : null;

	$pi_payload = array(
		'id'            => isset( $result['id'] ) ? (string) $result['id'] : '',
		'status'        => $pi_status,
		'amount'        => isset( $result['amount'] ) ? (int) $result['amount'] : $amount,
		'currency'      => isset( $result['currency'] ) ? (string) $result['currency'] : strtolower( trim( $currency ) ),
		'customer'      => isset( $result['customer'] ) ? $result['customer'] : $customer_id,
		'client_secret' => $client_secret,
	);

	if ( ! empty( $result['latest_charge'] ) ) {
		if ( is_string( $result['latest_charge'] ) && preg_match( '/^ch_/', $result['latest_charge'] ) ) {
			$pi_payload['latest_charge_id'] = $result['latest_charge'];
		} elseif ( is_array( $result['latest_charge'] ) && ! empty( $result['latest_charge']['id'] ) ) {
			$pi_payload['latest_charge_id'] = (string) $result['latest_charge']['id'];
		}
	}

	if ( 'succeeded' !== $pi_status ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'Payment did not complete with status succeeded. Use a valid pm_…, ensure the card supports off-session charges, or complete any required authentication using client_secret.', 'arsenal-settings' ),
				'status'         => false,
				'code'           => 'payment_not_succeeded',
				'payment_intent' => $pi_payload,
			),
			402
		);
	}

	$body = array(
		'message'        => __( 'Payment succeeded.', 'arsenal-settings' ),
		'status'         => true,
		'code'           => 'payment_succeeded',
		'payment_intent' => $pi_payload,
	);

	return new WP_REST_Response( $body, 201 );
}

/**
 * Option name for the Stripe secret key stored in wp_options.
 */
function arsenal_settings_stripe_secret_key_option_name() {
	return 'arsenal_settings_stripe_secret_key';
}

/**
 * Sanitize and persist Stripe secret key from Settings → Arsenal Stripe.
 *
 * @param mixed $value Submitted value.
 * @return string Stored key or empty string.
 */
function arsenal_settings_sanitize_stripe_secret_key_option( $value ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return (string) get_option( arsenal_settings_stripe_secret_key_option_name(), '' );
	}
	if ( isset( $_POST['arsenal_settings_stripe_secret_key_clear'] ) && '1' === (string) wp_unslash( $_POST['arsenal_settings_stripe_secret_key_clear'] ) ) {
		return '';
	}
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( $value === '' ) {
		return (string) get_option( arsenal_settings_stripe_secret_key_option_name(), '' );
	}
	if ( ! preg_match( '/^sk_(test|live)_[A-Za-z0-9]+$/', $value ) ) {
		add_settings_error(
			arsenal_settings_stripe_secret_key_option_name(),
			'arsenal_stripe_key_invalid',
			__( 'Stripe secret key must look like sk_test_… or sk_live_… . The previous value was kept.', 'arsenal-settings' ),
			'error'
		);
		return (string) get_option( arsenal_settings_stripe_secret_key_option_name(), '' );
	}
	return $value;
}

/**
 * Register the Stripe key setting for the Settings API.
 */
function arsenal_settings_register_stripe_admin_settings() {
	register_setting(
		'arsenal_settings',
		arsenal_settings_stripe_secret_key_option_name(),
		array(
			'type'              => 'string',
			'sanitize_callback' => 'arsenal_settings_sanitize_stripe_secret_key_option',
			'default'           => '',
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'arsenal_settings_register_stripe_admin_settings' );

/**
 * Add Settings → Arsenal Stripe.
 */
function arsenal_settings_register_stripe_admin_menu() {
	add_options_page(
		__( 'Arsenal Stripe', 'arsenal-settings' ),
		__( 'Arsenal Stripe', 'arsenal-settings' ),
		'manage_options',
		'arsenal-settings-stripe',
		'arsenal_settings_render_stripe_settings_page'
	);
}
add_action( 'admin_menu', 'arsenal_settings_register_stripe_admin_menu' );

/**
 * Render Settings → Arsenal Stripe.
 */
function arsenal_settings_render_stripe_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$opt_name = arsenal_settings_stripe_secret_key_option_name();
	settings_errors( $opt_name );
	$has_key = (string) get_option( $opt_name, '' ) !== '';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Store your Stripe secret API key here for Arsenal REST endpoints. When a key is saved, it is used before any wp-config.php constant.', 'arsenal-settings' ); ?>
		</p>
		<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
			<?php settings_fields( 'arsenal_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( $opt_name ); ?>"><?php esc_html_e( 'Secret key', 'arsenal-settings' ); ?></label>
					</th>
					<td>
						<input
							name="<?php echo esc_attr( $opt_name ); ?>"
							type="password"
							id="<?php echo esc_attr( $opt_name ); ?>"
							class="regular-text code"
							value=""
							autocomplete="new-password"
							spellcheck="false"
						/>
						<p class="description">
							<?php
							if ( $has_key ) {
								esc_html_e( 'A key is already saved. Enter a new key to replace it. Leave the field empty to keep the current key.', 'arsenal-settings' );
							} else {
								esc_html_e( 'Enter sk_test_… or sk_live_… from the Stripe Dashboard (Developers → API keys).', 'arsenal-settings' );
							}
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Remove key', 'arsenal-settings' ); ?></th>
					<td>
						<label>
							<input name="arsenal_settings_stripe_secret_key_clear" type="checkbox" value="1" />
							<?php esc_html_e( 'Remove stored Stripe secret key from the database', 'arsenal-settings' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save changes', 'arsenal-settings' ) ); ?>
		</form>
	</div>
	<?php
}
