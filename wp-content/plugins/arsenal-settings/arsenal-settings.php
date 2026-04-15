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
 * Add to wp-config.php: define( 'ARSENAL_STRIPE_SECRET_KEY', 'sk_test_...' );
 * Or use the filter: arsenal_stripe_secret_key
 *
 * @return string
 */
function arsenal_settings_get_stripe_secret_key() {
	if ( defined( 'ARSENAL_STRIPE_SECRET_KEY' ) && constant( 'ARSENAL_STRIPE_SECRET_KEY' ) ) {
		return (string) constant( 'ARSENAL_STRIPE_SECRET_KEY' );
	}

	return (string) apply_filters( 'arsenal_stripe_secret_key', '' );
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
			__( 'Stripe secret key is not configured.', 'arsenal-settings' ),
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
			__( 'Stripe secret key is not configured.', 'arsenal-settings' ),
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
 *                                          When no default_payment_method is sent, default_incomplete is applied so Stripe does not require a saved card up front.
 * }
 * @return array|WP_Error Decoded subscription object or error.
 */
function arsenal_settings_stripe_create_subscription( $customer_id, $price_id, $quantity = 1, $extra = array() ) {
	$secret = arsenal_settings_get_stripe_secret_key();
	if ( '' === $secret ) {
		return new WP_Error(
			'stripe_config',
			__( 'Stripe secret key is not configured. Define ARSENAL_STRIPE_SECRET_KEY in wp-config.php or use the arsenal_stripe_secret_key filter.', 'arsenal-settings' ),
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

	$allowed_behaviors = array( 'allow_incomplete', 'default_incomplete', 'error_if_incomplete', 'pending_if_incomplete' );

	$pm = isset( $extra['default_payment_method'] ) ? trim( (string) $extra['default_payment_method'] ) : '';
	if ( $pm !== '' && preg_match( '/^pm_[a-zA-Z0-9]+$/', $pm ) ) {
		$body['default_payment_method'] = $pm;
	}

	if ( isset( $extra['payment_behavior'] ) && in_array( (string) $extra['payment_behavior'], $allowed_behaviors, true ) ) {
		$body['payment_behavior'] = (string) $extra['payment_behavior'];
	} elseif ( ! isset( $body['default_payment_method'] ) ) {
		// No saved card on customer: create as incomplete and return PaymentIntent for client confirmation.
		// @see https://stripe.com/docs/billing/subscriptions/build-subscriptions?ui=elements#handle-incomplete
		$body['payment_behavior']                              = 'default_incomplete';
		$body['payment_settings[save_default_payment_method]'] = 'on_subscription';
	}

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
			),
		)
	);
}
add_action( 'rest_api_init', 'arsenal_settings_register_rest_routes' );

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
 * - payment_behavior (optional): override Stripe behavior (usually omit; without pm_… we use default_incomplete).
 *   When both are omitted, if the Stripe customer has a default PaymentMethod (Dashboard / invoice settings), it is used automatically so the subscription can activate without a separate client confirmation step.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function arsenal_settings_rest_create_subscription( WP_REST_Request $request ) {
	$raw_customer = trim( (string) $request->get_param( 'customer' ) );
	$email        = trim( (string) $request->get_param( 'customer_email' ) );
	$price        = trim( (string) $request->get_param( 'price' ) );
	$quantity     = $request->get_param( 'quantity' );
	$stripe_pm = trim( (string) $request->get_param( 'default_payment_method' ) );
	$stripe_pb = trim( (string) $request->get_param( 'payment_behavior' ) );
	$stripe_extra = array();
	if ( $stripe_pm !== '' ) {
		if ( ! preg_match( '/^pm_[a-zA-Z0-9]+$/', $stripe_pm ) ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'Optional "default_payment_method" must be a Stripe PaymentMethod id (pm_…).', 'arsenal-settings' ),
					'status'  => false,
					'code'    => 'invalid_payment_method_format',
				),
				400
			);
		}
		$stripe_extra['default_payment_method'] = $stripe_pm;
	}
	if ( $stripe_pb !== '' ) {
		$stripe_extra['payment_behavior'] = $stripe_pb;
	}

	$customer_id = '';

	if ( $raw_customer !== '' && preg_match( '/^cus_[a-zA-Z0-9]+$/', $raw_customer ) ) {
		$customer_id = $raw_customer;
	} elseif ( $raw_customer !== '' ) {
		return new WP_REST_Response(
			array(
				'message' => __( 'The "customer" value must be a Stripe Customer ID (for example cus_AbCd123), not a name or username. In Stripe Dashboard open Customers and copy the ID, or send "customer_email" with the billing email instead.', 'arsenal-settings' ),
				'status'  => false,
				'code'    => 'invalid_customer_format',
			),
			400
		);
	} elseif ( $email !== '' && is_email( $email ) ) {
		$looked = arsenal_settings_stripe_find_customer_id_by_email( $email );
		if ( is_wp_error( $looked ) ) {
			$d      = $looked->get_error_data();
			$status = isset( $d['status'] ) ? (int) $d['status'] : 500;
			return new WP_REST_Response(
				array(
					'message' => $looked->get_error_message(),
					'status'  => false,
					'code'    => $looked->get_error_code(),
				),
				$status
			);
		}
		if ( $looked === '' ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'No Stripe customer exists for that email. Create a customer in Stripe (Dashboard or API) first, then retry or pass "customer" as cus_….', 'arsenal-settings' ),
					'status'  => false,
					'code'    => 'stripe_customer_not_found',
				),
				404
			);
		}
		$customer_id = $looked;
	} else {
		return new WP_REST_Response(
			array(
				'message' => __( 'Provide either "customer" (Stripe id cus_…) or a valid "customer_email" to identify the Stripe customer.', 'arsenal-settings' ),
				'status'  => false,
				'code'    => 'missing_customer',
			),
			400
		);
	}

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

	// If the client did not pass a pm or payment_behavior, reuse the customer's default PaymentMethod from Stripe
	// (Dashboard "default for invoices" / API invoice_settings.default_payment_method). Otherwise we force
	// default_incomplete and the subscription stays incomplete even though the customer already has a card on file.
	if ( ! isset( $stripe_extra['default_payment_method'] ) && $stripe_pb === '' ) {
		$customer_default_pm = arsenal_settings_stripe_get_customer_default_payment_method_id( $customer_id );
		if ( $customer_default_pm !== '' ) {
			$stripe_extra['default_payment_method'] = $customer_default_pm;
		}
	}

	$result = arsenal_settings_stripe_create_subscription( $customer_id, $price, $quantity, $stripe_extra );

	if ( is_wp_error( $result ) ) {
		$data   = $result->get_error_data();
		$status = isset( $data['status'] ) ? (int) $data['status'] : 500;

		$body = array(
			'message' => $result->get_error_message(),
			'status'  => false,
			'code'    => $result->get_error_code(),
		);

		if ( ! empty( $data['stripe_error'] ) ) {
			$body['stripe_error'] = $data['stripe_error'];
		}

		return new WP_REST_Response( $body, $status );
	}

	list( $pi_client_secret, $pi_status ) = arsenal_settings_stripe_resolve_subscription_payment_intent( $result );

	// If expand on create did not return a PI (encoding quirks or eventual consistency), refetch subscription once.
	if ( ( null === $pi_client_secret || '' === $pi_client_secret ) && ! empty( $result['id'] ) && preg_match( '/^sub_[a-zA-Z0-9]+$/', (string) $result['id'] ) ) {
		$refetched = arsenal_settings_stripe_get_subscription(
			(string) $result['id'],
			array( 'latest_invoice.payment_intent' )
		);
		if ( ! is_wp_error( $refetched ) && isset( $refetched['object'] ) && 'subscription' === $refetched['object'] ) {
			list( $pi2, $st2 ) = arsenal_settings_stripe_resolve_subscription_payment_intent( $refetched );
			if ( null !== $pi2 && '' !== $pi2 ) {
				$pi_client_secret = $pi2;
				$pi_status         = $st2;
				$result            = $refetched;
			}
		}
	}

	$sub_status = isset( $result['status'] ) ? (string) $result['status'] : '';
	$message    = __( 'The subscription has been created.', 'arsenal-settings' );
	if ( 'incomplete' === $sub_status && $pi_client_secret ) {
		$message = __( 'The subscription has been created. Complete payment with Stripe (use the payment_intent client_secret on the client).', 'arsenal-settings' );
	}

	$latest_invoice_id = null;
	if ( isset( $result['latest_invoice'] ) ) {
		$latest_invoice_id = is_array( $result['latest_invoice'] ) && isset( $result['latest_invoice']['id'] )
			? (string) $result['latest_invoice']['id']
			: (string) $result['latest_invoice'];
	}

	$body = array(
		'message'                      => $message,
		'status'                       => true,
		'code'                         => 'created',
		'payment_intent_client_secret' => $pi_client_secret,
		'payment_intent_status'        => $pi_status,
		'subscription'                 => array(
			'id'                     => isset( $result['id'] ) ? $result['id'] : '',
			'object'                 => isset( $result['object'] ) ? $result['object'] : 'subscription',
			'customer'               => isset( $result['customer'] ) ? $result['customer'] : '',
			'status'                 => $sub_status,
			'currency'               => isset( $result['currency'] ) ? $result['currency'] : '',
			'livemode'               => isset( $result['livemode'] ) ? (bool) $result['livemode'] : false,
			'created'                => isset( $result['created'] ) ? (int) $result['created'] : 0,
			'collection_method'      => isset( $result['collection_method'] ) ? $result['collection_method'] : '',
			'latest_invoice_id'      => $latest_invoice_id,
			'default_payment_method' => isset( $result['default_payment_method'] ) ? $result['default_payment_method'] : null,
		),
	);

	return new WP_REST_Response( $body, 201 );
}
