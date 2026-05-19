<?php
/**
 * WooCommerce / Stripe direct debit payment failures — admin list and logging.
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Log file prefix: wc-dd-payment-failures-YYYY-MM-DD.log */
define( 'ARSENAL_SETTINGS_DD_FAILURE_LOG_PREFIX', 'wc-dd-payment-failures' );

/**
 * REST routes that can produce payment failures.
 *
 * @return string[]
 */
function arsenal_settings_dd_failure_payment_route_suffixes() {
	return array(
		'/create-payment',
		'/create-subscription',
		'/create-subscription-custom',
		'/create-recurring-subscription',
		'/create-recurring-subscription-by-email',
		'/create-recurring-subscription-by-armember-plan',
		'/create-recurring-subscription-by-armember-plan-deferred',
	);
}

/**
 * Cron log events treated as payment failures.
 *
 * @return string[]
 */
function arsenal_settings_dd_failure_cron_events() {
	return array(
		'failed_log_repair_skip',
		'failed_log_repair_error',
		'failed_log_repair_duplicate_remove_error',
		'payment_log_insert_error',
		'cron_error',
	);
}

/**
 * Stripe / bank debit payment method types for filtering.
 *
 * @return string[]
 */
function arsenal_settings_dd_failure_debit_types() {
	return array( 'us_bank_account', 'sepa_debit', 'bacs_debit', 'au_becs_debit', 'acss_debit' );
}

/**
 * Whether text references a Stripe bank-debit / direct-debit payment method.
 *
 * @param string $text Haystack.
 * @return bool
 */
function arsenal_settings_dd_failure_text_suggests_debit( $text ) {
	$text = strtolower( (string) $text );
	foreach ( arsenal_settings_dd_failure_debit_types() as $type ) {
		if ( false !== strpos( $text, $type ) ) {
			return true;
		}
	}
	return ( false !== strpos( $text, 'direct debit' ) || false !== strpos( $text, 'bank_debit' ) || false !== strpos( $text, 'ach_debit' ) );
}

/**
 * Recursively scan array values for direct-debit payment method hints.
 *
 * @param mixed $data Data.
 * @return bool
 */
function arsenal_settings_dd_failure_array_mentions_debit( $data ) {
	if ( is_string( $data ) ) {
		return arsenal_settings_dd_failure_text_suggests_debit( $data );
	}
	if ( ! is_array( $data ) ) {
		return false;
	}
	foreach ( $data as $key => $value ) {
		if ( is_string( $key ) && arsenal_settings_dd_failure_text_suggests_debit( $key ) ) {
			return true;
		}
		if ( arsenal_settings_dd_failure_array_mentions_debit( $value ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Read payment_method_type from REST params when present.
 *
 * @param array $params Request params.
 * @return string Normalized type or empty.
 */
function arsenal_settings_dd_failure_detect_type_from_params( array $params ) {
	foreach ( array( 'payment_method_type', 'payment_type', 'type' ) as $key ) {
		if ( empty( $params[ $key ] ) ) {
			continue;
		}
		$val = strtolower( sanitize_text_field( (string) $params[ $key ] ) );
		if ( in_array( $val, arsenal_settings_dd_failure_debit_types(), true ) ) {
			return $val;
		}
	}
	return '';
}

/**
 * Whether a WooCommerce order used Stripe direct debit / bank debit.
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function arsenal_settings_dd_failure_order_is_direct_debit( $order ) {
	if ( ! is_object( $order ) || ! method_exists( $order, 'get_payment_method' ) ) {
		return false;
	}
	$pm = strtolower( (string) $order->get_payment_method() );
	foreach ( arsenal_settings_dd_failure_debit_types() as $type ) {
		if ( $pm === 'stripe_' . $type || false !== strpos( $pm, $type ) ) {
			return true;
		}
	}
	foreach ( array( '_stripe_payment_method_type', '_payment_method_type' ) as $meta_key ) {
		$val = strtolower( (string) $order->get_meta( $meta_key ) );
		if ( in_array( $val, arsenal_settings_dd_failure_debit_types(), true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether a normalized failure row is a Stripe direct-debit / bank-debit failure.
 *
 * @param array $row Failure row.
 * @return bool
 */
function arsenal_settings_dd_failure_row_is_direct_debit( array $row ) {
	if ( ! empty( $row['payment_method_type'] ) ) {
		return in_array( strtolower( (string) $row['payment_method_type'] ), arsenal_settings_dd_failure_debit_types(), true );
	}

	$pm = isset( $row['payment_method'] ) ? strtolower( (string) $row['payment_method'] ) : '';
	foreach ( arsenal_settings_dd_failure_debit_types() as $type ) {
		if ( $pm === 'stripe_' . $type || ( $pm !== '' && false !== strpos( $pm, $type ) ) ) {
			return true;
		}
	}

	$details = isset( $row['details'] ) && is_array( $row['details'] ) ? $row['details'] : array();
	if ( ! empty( $details['params'] ) && is_array( $details['params'] ) ) {
		if ( arsenal_settings_dd_failure_detect_type_from_params( $details['params'] ) !== '' ) {
			return true;
		}
		if ( arsenal_settings_dd_failure_array_mentions_debit( $details['params'] ) ) {
			return true;
		}
	}
	if ( ! empty( $details['response'] ) && arsenal_settings_dd_failure_array_mentions_debit( $details['response'] ) ) {
		return true;
	}
	if ( ! empty( $details['extra'] ) && arsenal_settings_dd_failure_array_mentions_debit( $details['extra'] ) ) {
		return true;
	}
	if ( ! empty( $details['process'] ) && arsenal_settings_dd_failure_array_mentions_debit( $details['process'] ) ) {
		return true;
	}
	if ( arsenal_settings_dd_failure_array_mentions_debit( $details ) ) {
		return true;
	}

	$order_id = isset( $row['order_id'] ) ? (int) $row['order_id'] : 0;
	if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
		if ( $order && arsenal_settings_dd_failure_order_is_direct_debit( $order ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Append one structured failure row to the dedicated NDJSON log.
 *
 * @param array $entry Failure record.
 */
function arsenal_settings_dd_failure_log( array $entry ) {
	if ( ! function_exists( 'arsenal_settings_api_log_ensure_dir' ) ) {
		return;
	}
	$dir = arsenal_settings_api_log_ensure_dir();
	if ( is_wp_error( $dir ) ) {
		return;
	}

	$row = array_merge(
		array(
			'id'        => function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'ddf_', true ),
			'timestamp' => current_time( 'mysql' ),
			'source'    => 'unknown',
		),
		$entry
	);
	if ( isset( $row['details'] ) && is_array( $row['details'] ) && function_exists( 'arsenal_settings_api_redact_for_log' ) ) {
		$row['details'] = arsenal_settings_api_redact_for_log( $row['details'] );
	}

	$file = trailingslashit( $dir ) . ARSENAL_SETTINGS_DD_FAILURE_LOG_PREFIX . '-' . gmdate( 'Y-m-d' ) . '.log';
	$line = wp_json_encode( $row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $line ) {
		return;
	}
	file_put_contents( $file, $line . "\n", FILE_APPEND | LOCK_EX );
}

/**
 * Resolve customer email from log context.
 *
 * @param array $params Request params or extra array.
 * @param array $extra  Additional context.
 * @return string
 */
function arsenal_settings_dd_failure_resolve_email( array $params, array $extra = array() ) {
	foreach ( array( 'customer_email', 'email', 'arm_payer_email', 'payer_email', 'receipt_email' ) as $key ) {
		if ( ! empty( $params[ $key ] ) && is_email( $params[ $key ] ) ) {
			return sanitize_email( (string) $params[ $key ] );
		}
		if ( ! empty( $extra[ $key ] ) && is_email( $extra[ $key ] ) ) {
			return sanitize_email( (string) $extra[ $key ] );
		}
	}
	$user_id = 0;
	if ( ! empty( $extra['user_id'] ) ) {
		$user_id = (int) $extra['user_id'];
	} elseif ( ! empty( $params['user_id'] ) ) {
		$user_id = (int) $params['user_id'];
	}
	if ( $user_id > 0 ) {
		$user = get_userdata( $user_id );
		if ( $user && is_email( $user->user_email ) ) {
			return $user->user_email;
		}
	}
	$order_id = 0;
	if ( ! empty( $extra['order_id'] ) ) {
		$order_id = (int) $extra['order_id'];
	} elseif ( ! empty( $extra['woocommerce_order_id'] ) ) {
		$order_id = (int) $extra['woocommerce_order_id'];
	}
	if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			return (string) $order->get_billing_email();
		}
	}
	return '';
}

/**
 * Whether a route is a payment-related Arsenal REST route.
 *
 * @param string $route Full route.
 * @return bool
 */
function arsenal_settings_dd_failure_is_payment_route( $route ) {
	$route = (string) $route;
	$ns    = '/' . ARSENAL_SETTINGS_REST_NAMESPACE;
	if ( strpos( $route, $ns ) !== 0 ) {
		return false;
	}
	$suffix = substr( $route, strlen( $ns ) );
	foreach ( arsenal_settings_dd_failure_payment_route_suffixes() as $allowed ) {
		if ( $suffix === $allowed || strpos( $suffix, $allowed . '/' ) === 0 ) {
			return true;
		}
	}
	return false;
}

/**
 * Build failure reason string from API response data.
 *
 * @param int   $http_status HTTP status.
 * @param mixed $data        Response body.
 * @return string
 */
function arsenal_settings_dd_failure_api_reason( $http_status, $data ) {
	if ( is_array( $data ) ) {
		if ( ! empty( $data['code'] ) ) {
			$code = (string) $data['code'];
			$msg  = ! empty( $data['message'] ) ? (string) $data['message'] : '';
			return $msg !== '' ? $code . ': ' . $msg : $code;
		}
		if ( ! empty( $data['message'] ) ) {
			return (string) $data['message'];
		}
		if ( ! empty( $data['latest_invoice']['pay_error'] ) ) {
			$err = (string) $data['latest_invoice']['pay_error'];
			$ec  = ! empty( $data['latest_invoice']['pay_error_code'] ) ? (string) $data['latest_invoice']['pay_error_code'] : '';
			return $ec !== '' ? $ec . ': ' . $err : $err;
		}
	}
	if ( $http_status >= 400 ) {
		return sprintf( 'HTTP %d', (int) $http_status );
	}
	return __( 'Payment failure', 'arsenal-settings' );
}

/**
 * Detect API payment failure from logged REST entry + response.
 *
 * @param array                $entry    Log entry.
 * @param WP_HTTP_Response|mixed $response Response.
 * @return array|null Normalized row or null.
 */
function arsenal_settings_dd_failure_from_api_log( array $entry, $response ) {
	$route = isset( $entry['route'] ) ? (string) $entry['route'] : '';
	if ( ! arsenal_settings_dd_failure_is_payment_route( $route ) ) {
		return null;
	}

	$http_status = 0;
	$data        = null;
	if ( is_object( $response ) && method_exists( $response, 'get_status' ) ) {
		$http_status = (int) $response->get_status();
	}
	if ( is_object( $response ) && method_exists( $response, 'get_data' ) ) {
		$data = $response->get_data();
	} elseif ( isset( $entry['response'] ) ) {
		$data = $entry['response'];
		if ( isset( $entry['response_status'] ) ) {
			$http_status = (int) $entry['response_status'];
		}
	}

	$is_failure = $http_status >= 400;
	if ( is_array( $data ) ) {
		if ( array_key_exists( 'status', $data ) && false === $data['status'] ) {
			$is_failure = true;
		}
		if ( ! empty( $data['code'] ) && 'payment_not_succeeded' === (string) $data['code'] ) {
			$is_failure = true;
		}
		if ( ! empty( $data['latest_invoice']['pay_error'] ) ) {
			$is_failure = true;
		}
	}
	if ( ! $is_failure ) {
		return null;
	}

	$params = isset( $entry['params'] ) && is_array( $entry['params'] ) ? $entry['params'] : array();
	$email  = arsenal_settings_dd_failure_resolve_email( $params );
	$ts     = isset( $entry['at'] ) ? (string) $entry['at'] : gmdate( 'c' );
	$pm_type = arsenal_settings_dd_failure_detect_type_from_params( $params );

	$row = array(
		'id'                  => isset( $entry['request_id'] ) ? (string) $entry['request_id'] : uniqid( 'api_', true ),
		'timestamp'           => $ts,
		'source'              => 'api',
		'customer_email'      => $email,
		'route'               => $route,
		'failure_reason'      => arsenal_settings_dd_failure_api_reason( $http_status, $data ),
		'http_status'         => $http_status,
		'order_id'            => isset( $params['order_id'] ) ? (int) $params['order_id'] : 0,
		'payment_method'      => isset( $params['default_payment_method'] ) ? (string) $params['default_payment_method'] : '',
		'payment_method_type' => $pm_type,
		'details'             => array(
			'method'      => isset( $entry['method'] ) ? $entry['method'] : '',
			'params'      => $params,
			'response'    => $data,
			'process'     => isset( $entry['process'] ) ? $entry['process'] : array(),
			'duration_ms' => isset( $entry['duration_ms'] ) ? $entry['duration_ms'] : null,
		),
	);

	return arsenal_settings_dd_failure_row_is_direct_debit( $row ) ? $row : null;
}

/**
 * Record API failure after REST log is written.
 *
 * @param array                $entry    Log entry.
 * @param WP_HTTP_Response|mixed $response Response.
 */
function arsenal_settings_dd_failures_on_rest_api_logged( array $entry, $response ) {
	$row = arsenal_settings_dd_failure_from_api_log( $entry, $response );
	if ( null === $row ) {
		return;
	}
	arsenal_settings_dd_failure_log( $row );
}
add_action( 'arsenal_settings_rest_api_logged', 'arsenal_settings_dd_failures_on_rest_api_logged', 10, 2 );

/**
 * Whether a WordPress user pays via Stripe bank debit / direct debit.
 *
 * @param int $user_id WordPress user id.
 * @return bool
 */
function arsenal_settings_dd_failure_user_uses_direct_debit( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return false;
	}

	$user = get_userdata( $user_id );
	if ( ! $user || empty( $user->user_email ) || ! function_exists( 'arsenal_settings_stripe_find_customer_id_by_email' ) ) {
		return false;
	}

	$customer_id = arsenal_settings_stripe_find_customer_id_by_email( (string) $user->user_email );
	if ( is_wp_error( $customer_id ) || '' === $customer_id ) {
		return false;
	}

	foreach ( arsenal_settings_dd_failure_debit_types() as $type ) {
		if ( function_exists( 'arsenal_settings_stripe_get_first_customer_payment_method_id' ) ) {
			$pm_id = arsenal_settings_stripe_get_first_customer_payment_method_id( $customer_id, $type );
			if ( '' !== $pm_id ) {
				return true;
			}
		}
	}

	$default_pm = function_exists( 'arsenal_settings_stripe_get_customer_default_payment_method_id' )
		? arsenal_settings_stripe_get_customer_default_payment_method_id( $customer_id )
		: '';
	if ( '' !== $default_pm && function_exists( 'arsenal_settings_stripe_api_get' ) ) {
		$pm = arsenal_settings_stripe_api_get( 'payment_methods/' . rawurlencode( $default_pm ) );
		if ( ! is_wp_error( $pm ) && ! empty( $pm['type'] ) ) {
			return in_array( strtolower( (string) $pm['type'] ), arsenal_settings_dd_failure_debit_types(), true );
		}
	}

	return false;
}

/**
 * Record cron payment failure events.
 *
 * @param string $message Event name.
 * @param array  $extra   Context.
 */
function arsenal_settings_dd_failures_on_cron_log( $message, array $extra = array() ) {
	$message = (string) $message;
	$events  = arsenal_settings_dd_failure_cron_events();
	$include = in_array( $message, $events, true );
	if ( ! $include && 'order_skip' === $message && ! empty( $extra['reason'] ) && 'not_paid_or_stripe_not_verified' === (string) $extra['reason'] ) {
		$include = true;
	}
	if ( ! $include ) {
		return;
	}

	$order_id = isset( $extra['order_id'] ) ? (int) $extra['order_id'] : ( isset( $extra['woocommerce_order_id'] ) ? (int) $extra['woocommerce_order_id'] : 0 );
	$probe    = array(
		'order_id'            => $order_id,
		'payment_method_type' => '',
		'details'             => array( 'extra' => $extra ),
	);
	if ( ! arsenal_settings_dd_failure_row_is_direct_debit( $probe ) && 'failed_log_repair_skip' === $message ) {
		$user_id = isset( $extra['user_id'] ) ? (int) $extra['user_id'] : 0;
		if ( $user_id > 0 && arsenal_settings_dd_failure_user_uses_direct_debit( $user_id ) ) {
			$probe['payment_method_type'] = 'us_bank_account';
		}
	}
	if ( ! arsenal_settings_dd_failure_row_is_direct_debit( $probe ) ) {
		return;
	}

	$reason = $message;
	if ( ! empty( $extra['reason'] ) ) {
		$reason = $message . ': ' . (string) $extra['reason'];
	} elseif ( ! empty( $extra['db_error'] ) ) {
		$reason = $message . ': ' . (string) $extra['db_error'];
	}

	arsenal_settings_dd_failure_log(
		array(
			'source'         => 'cron',
			'customer_email' => arsenal_settings_dd_failure_resolve_email( array(), $extra ),
			'event'          => $message,
			'failure_reason' => $reason,
			'order_id'       => isset( $extra['order_id'] ) ? (int) $extra['order_id'] : ( isset( $extra['woocommerce_order_id'] ) ? (int) $extra['woocommerce_order_id'] : 0 ),
			'details'        => array(
				'event' => $message,
				'extra' => $extra,
			),
		)
	);
}
add_action( 'arsenal_settings_wc_stripe_arm_cron_logged', 'arsenal_settings_dd_failures_on_cron_log', 10, 2 );

/**
 * Log WooCommerce order failures (Stripe / direct debit).
 *
 * @param int $order_id Order ID.
 */
function arsenal_settings_dd_failures_on_wc_order_failed( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	if ( ! arsenal_settings_dd_failure_order_is_direct_debit( $order ) ) {
		return;
	}

	$pm = (string) $order->get_payment_method();
	$pm_type = '';
	foreach ( array( '_stripe_payment_method_type', '_payment_method_type' ) as $meta_key ) {
		$val = (string) $order->get_meta( $meta_key );
		if ( $val !== '' ) {
			$pm_type = $val;
			break;
		}
	}
	if ( $pm_type === '' && strpos( $pm, 'stripe_' ) === 0 ) {
		$pm_type = substr( $pm, strlen( 'stripe_' ) );
	}

	arsenal_settings_dd_failure_log(
		array(
			'source'           => 'woocommerce',
			'customer_email'   => (string) $order->get_billing_email(),
			'failure_reason'   => sprintf(
				/* translators: 1: order status */
				__( 'WooCommerce order failed (status: %1$s)', 'arsenal-settings' ),
				$order->get_status()
			),
			'order_id'         => (int) $order_id,
			'payment_method'   => $pm,
			'payment_method_type' => $pm_type,
			'details'          => array(
				'order_id'       => (int) $order_id,
				'status'         => $order->get_status(),
				'total'          => $order->get_total(),
				'currency'       => $order->get_currency(),
				'payment_method' => $pm,
				'payment_method_type' => $pm_type,
				'customer_note'  => $order->get_customer_note(),
			),
		)
	);
}
add_action( 'woocommerce_order_status_failed', 'arsenal_settings_dd_failures_on_wc_order_failed', 20, 1 );

/**
 * Parse timestamp from log row for sorting/display.
 *
 * @param array $row Row.
 * @return int Unix timestamp.
 */
function arsenal_settings_dd_failure_row_timestamp( array $row ) {
	if ( ! empty( $row['timestamp'] ) ) {
		$t = strtotime( (string) $row['timestamp'] );
		if ( $t ) {
			return $t;
		}
	}
	if ( ! empty( $row['at'] ) ) {
		$t = strtotime( (string) $row['at'] );
		if ( $t ) {
			return $t;
		}
	}
	return 0;
}

/**
 * Parse one NDJSON log file into failure rows.
 *
 * @param string $path     File path.
 * @param string $kind     dedicated|api|cron.
 * @return array[]
 */
function arsenal_settings_dd_failures_parse_log_file( $path, $kind ) {
	$rows = array();
	if ( ! is_readable( $path ) ) {
		return $rows;
	}
	$handle = fopen( $path, 'rb' );
	if ( ! $handle ) {
		return $rows;
	}
	while ( ( $line = fgets( $handle ) ) !== false ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}
		$decoded = json_decode( $line, true );
		if ( ! is_array( $decoded ) ) {
			continue;
		}
		if ( 'dedicated' === $kind ) {
			$rows[] = $decoded;
			continue;
		}
		if ( 'api' === $kind ) {
			$row = arsenal_settings_dd_failure_from_api_log( $decoded, null );
			if ( null !== $row ) {
				$rows[] = $row;
			}
			continue;
		}
		if ( 'cron' === $kind ) {
			$event = isset( $decoded['event'] ) ? (string) $decoded['event'] : '';
			$extra = isset( $decoded['extra'] ) && is_array( $decoded['extra'] ) ? $decoded['extra'] : array();
			$events = arsenal_settings_dd_failure_cron_events();
			$include = in_array( $event, $events, true );
			if ( ! $include && 'order_skip' === $event && ! empty( $extra['reason'] ) && 'not_paid_or_stripe_not_verified' === (string) $extra['reason'] ) {
				$include = true;
			}
			if ( ! $include ) {
				continue;
			}
			$reason = $event;
			if ( ! empty( $extra['reason'] ) ) {
				$reason = $event . ': ' . (string) $extra['reason'];
			} elseif ( ! empty( $extra['db_error'] ) ) {
				$reason = $event . ': ' . (string) $extra['db_error'];
			}
			$cron_row = array(
				'id'             => isset( $decoded['timestamp'], $event ) ? md5( $decoded['timestamp'] . $event . wp_json_encode( $extra ) ) : uniqid( 'cron_', true ),
				'timestamp'      => isset( $decoded['timestamp'] ) ? (string) $decoded['timestamp'] : '',
				'source'         => 'cron',
				'customer_email' => arsenal_settings_dd_failure_resolve_email( array(), $extra ),
				'event'          => $event,
				'failure_reason' => $reason,
				'order_id'       => isset( $extra['order_id'] ) ? (int) $extra['order_id'] : 0,
				'details'        => $decoded,
			);
			if ( arsenal_settings_dd_failure_row_is_direct_debit( $cron_row ) ) {
				$rows[] = $cron_row;
			}
		}
	}
	fclose( $handle );
	return $rows;
}

/**
 * Collect failures for admin table (deduped, sorted newest first).
 *
 * @param int    $days          Days of logs to scan.
 * @param string $source_filter api|cron|woocommerce|'' for all.
 * @param string $email_search  Partial email filter.
 * @return array[]
 */
function arsenal_settings_dd_failures_collect( $days = 30, $source_filter = '', $email_search = '' ) {
	$days = max( 1, min( 90, (int) $days ) );
	$dir  = function_exists( 'arsenal_settings_api_log_ensure_dir' ) ? arsenal_settings_api_log_ensure_dir() : '';
	if ( is_wp_error( $dir ) || $dir === '' ) {
		return array();
	}

	$all    = array();
	$seen   = array();
	$prefix = ARSENAL_SETTINGS_DD_FAILURE_LOG_PREFIX;

	for ( $i = 0; $i < $days; $i++ ) {
		$date = gmdate( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
		$files = array(
			array( trailingslashit( $dir ) . $prefix . '-' . $date . '.log', 'dedicated' ),
			array( trailingslashit( $dir ) . 'api-' . $date . '.log', 'api' ),
			array( trailingslashit( $dir ) . 'wc-stripe-arm-cron-' . $date . '.log', 'cron' ),
		);
		foreach ( $files as $file_meta ) {
			list( $path, $kind ) = $file_meta;
			if ( ! file_exists( $path ) ) {
				continue;
			}
			foreach ( arsenal_settings_dd_failures_parse_log_file( $path, $kind ) as $row ) {
				$key = isset( $row['id'] ) ? (string) $row['id'] : md5( wp_json_encode( $row ) );
				if ( isset( $seen[ $key ] ) ) {
					continue;
				}
				if ( ! arsenal_settings_dd_failure_row_is_direct_debit( $row ) ) {
					continue;
				}
				$seen[ $key ] = true;
				$all[]        = $row;
			}
		}
	}

	usort(
		$all,
		static function ( $a, $b ) {
			return arsenal_settings_dd_failure_row_timestamp( $b ) <=> arsenal_settings_dd_failure_row_timestamp( $a );
		}
	);

	if ( $source_filter !== '' ) {
		$all = array_values(
			array_filter(
				$all,
				static function ( $row ) use ( $source_filter ) {
					return isset( $row['source'] ) && (string) $row['source'] === $source_filter;
				}
			)
		);
	}

	if ( $email_search !== '' ) {
		$needle = strtolower( $email_search );
		$all    = array_values(
			array_filter(
				$all,
				static function ( $row ) use ( $needle ) {
					$email = isset( $row['customer_email'] ) ? strtolower( (string) $row['customer_email'] ) : '';
					return $email !== '' && false !== strpos( $email, $needle );
				}
			)
		);
	}

	return $all;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Admin list table for payment failures.
 */
class Arsenal_Settings_DD_Failures_List_Table extends WP_List_Table {

	/** @var array[] */
	private $items_data = array();

	/**
	 * @param array[] $items All rows.
	 */
	public function __construct( array $items ) {
		parent::__construct(
			array(
				'plural'   => 'dd-failures',
				'singular' => 'dd-failure',
				'ajax'     => false,
				'screen'   => function_exists( 'get_current_screen' ) && get_current_screen() ? get_current_screen() : null,
			)
		);
		$this->items_data = $items;
	}

	/**
	 * @return string
	 */
	public function get_primary_column_name() {
		return 'datetime';
	}

	/**
	 * Message when the current page has no rows.
	 */
	public function no_items() {
		esc_html_e( 'No direct debit payment failures on this page.', 'arsenal-settings' );
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return array(
			'datetime'       => __( 'Date & time', 'arsenal-settings' ),
			'source'         => __( 'Source', 'arsenal-settings' ),
			'customer'       => __( 'Customer', 'arsenal-settings' ),
			'failure_reason' => __( 'Failure reason', 'arsenal-settings' ),
			'order'          => __( 'Order', 'arsenal-settings' ),
			'details'        => __( 'Details', 'arsenal-settings' ),
		);
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 * Prepare items with pagination.
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable, $this->get_primary_column_name() );

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total        = count( $this->items_data );
		$offset       = ( $current_page - 1 ) * $per_page;

		$this->items = array_slice( $this->items_data, $offset, $per_page );
		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total / $per_page ) ),
			)
		);
	}

	/**
	 * @param array  $item Row.
	 * @param string $column_name Column.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'datetime':
				$ts = arsenal_settings_dd_failure_row_timestamp( $item );
				return $ts ? esc_html( wp_date( 'Y-m-d H:i:s', $ts ) ) : esc_html( isset( $item['timestamp'] ) ? (string) $item['timestamp'] : '—' );
			case 'source':
				$src = isset( $item['source'] ) ? (string) $item['source'] : '';
				$labels = array(
					'api'          => __( 'API', 'arsenal-settings' ),
					'cron'         => __( 'Cron', 'arsenal-settings' ),
					'woocommerce'  => __( 'WooCommerce', 'arsenal-settings' ),
				);
				return esc_html( isset( $labels[ $src ] ) ? $labels[ $src ] : ucfirst( $src ) );
			case 'customer':
				$email = isset( $item['customer_email'] ) ? (string) $item['customer_email'] : '';
				if ( $email === '' ) {
					return '<span class="description">—</span>';
				}
				$user = get_user_by( 'email', $email );
				if ( $user ) {
					return sprintf(
						'<a href="%1$s">%2$s</a><br><span class="description">%3$s</span>',
						esc_url( get_edit_user_link( $user->ID ) ),
						esc_html( $email ),
						esc_html( sprintf( 'User #%d', (int) $user->ID ) )
					);
				}
				return esc_html( $email );
			case 'failure_reason':
				$reason = isset( $item['failure_reason'] ) ? (string) $item['failure_reason'] : '';
				if ( isset( $item['route'] ) && $item['route'] !== '' ) {
					$reason .= ' <span class="description">(' . esc_html( $item['route'] ) . ')</span>';
				} elseif ( isset( $item['event'] ) && $item['event'] !== '' ) {
					$reason .= ' <span class="description">(' . esc_html( $item['event'] ) . ')</span>';
				}
				return $reason !== '' ? $reason : '<span class="description">—</span>';
			case 'order':
				$oid = isset( $item['order_id'] ) ? (int) $item['order_id'] : 0;
				if ( $oid < 1 ) {
					return '<span class="description">—</span>';
				}
				if ( function_exists( 'wc_get_order' ) ) {
					return sprintf(
						'<a href="%1$s">#%2$d</a>',
						esc_url( admin_url( 'post.php?post=' . $oid . '&action=edit' ) ),
						$oid
					);
				}
				return esc_html( '#' . $oid );
			case 'details':
				$json = wp_json_encode( isset( $item['details'] ) ? $item['details'] : $item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				$id   = 'arsenal-dd-detail-' . md5( ( isset( $item['id'] ) ? (string) $item['id'] : '' ) . wp_rand() );
				return sprintf(
					'<button type="button" class="button button-small arsenal-dd-toggle-details" data-target="%1$s" aria-expanded="false">%2$s</button>
					<div id="%1$s" class="arsenal-dd-details-panel" hidden><pre>%3$s</pre></div>',
					esc_attr( $id ),
					esc_html__( 'View', 'arsenal-settings' ),
					esc_html( is_string( $json ) ? $json : '{}' )
				);
			default:
				return '';
		}
	}
}

/**
 * Enqueue admin script for details toggle.
 *
 * @param string $hook Page hook.
 */
function arsenal_settings_dd_failures_admin_assets( $hook ) {
	if ( strpos( $hook, 'arsenal-settings-wc-dd-failures' ) === false ) {
		return;
	}
	wp_register_script( 'arsenal-dd-failures-admin', false, array(), ARSENAL_SETTINGS_VERSION, true );
	wp_enqueue_script( 'arsenal-dd-failures-admin' );
	wp_add_inline_script(
		'arsenal-dd-failures-admin',
		"(function(){document.addEventListener('click',function(e){var btn=e.target.closest('.arsenal-dd-toggle-details');if(!btn)return;e.preventDefault();var id=btn.getAttribute('data-target');var panel=document.getElementById(id);if(!panel)return;var open=!panel.hasAttribute('hidden');if(open){panel.setAttribute('hidden','');btn.setAttribute('aria-expanded','false');btn.textContent=" . wp_json_encode( __( 'View', 'arsenal-settings' ) ) . ";}else{panel.removeAttribute('hidden');btn.setAttribute('aria-expanded','true');btn.textContent=" . wp_json_encode( __( 'Hide', 'arsenal-settings' ) ) . ";}});})();"
	);
	wp_register_style( 'arsenal-dd-failures-admin', false, array(), ARSENAL_SETTINGS_VERSION );
	wp_enqueue_style( 'arsenal-dd-failures-admin' );
	wp_add_inline_style(
		'arsenal-dd-failures-admin',
		'.arsenal-dd-details-panel{margin-top:8px;max-width:720px}.arsenal-dd-details-panel pre{background:#f6f7f7;border:1px solid #c3c4c7;padding:10px;overflow:auto;max-height:320px;font-size:12px}'
	);
}
add_action( 'admin_enqueue_scripts', 'arsenal_settings_dd_failures_admin_assets' );

/**
 * Render admin page.
 */
function arsenal_settings_render_dd_payment_failures_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$days   = isset( $_GET['dd_days'] ) ? (int) $_GET['dd_days'] : 30;
	$source = isset( $_GET['dd_source'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['dd_source'] ) ) : '';
	$search = isset( $_GET['dd_email'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['dd_email'] ) ) : '';

	$items = arsenal_settings_dd_failures_collect( $days, $source, $search );
	$table = new Arsenal_Settings_DD_Failures_List_Table( $items );
	$table->prepare_items();

	$base_url = arsenal_settings_admin_page_url( 'arsenal-settings-wc-dd-failures' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p>
			<a href="<?php echo esc_url( arsenal_settings_admin_page_url( 'arsenal-settings' ) ); ?>"><?php esc_html_e( 'REST API key', 'arsenal-settings' ); ?></a>
			&middot;
			<a href="<?php echo esc_url( arsenal_settings_admin_page_url( 'arsenal-settings-stripe' ) ); ?>"><?php esc_html_e( 'Stripe', 'arsenal-settings' ); ?></a>
			&middot;
			<a href="<?php echo esc_url( arsenal_settings_admin_page_url( 'arsenal-settings-api-logs' ) ); ?>"><?php esc_html_e( 'API logs', 'arsenal-settings' ); ?></a>
		</p>
		<p class="description">
			<?php esc_html_e( 'Stripe direct debit and bank-debit payment failures only (US bank account, SEPA, BECS, ACSS, etc.). Card or generic API errors are excluded. Sources: dedicated failure log, matching REST API log lines, cron events tied to a debit order, and failed WooCommerce debit orders.', 'arsenal-settings' ); ?>
		</p>

		<form method="get" class="arsenal-dd-failures-filters" style="margin:16px 0;">
			<input type="hidden" name="page" value="arsenal-settings-wc-dd-failures" />
			<label>
				<?php esc_html_e( 'Days', 'arsenal-settings' ); ?>
				<select name="dd_days">
					<?php foreach ( array( 7, 14, 30, 60, 90 ) as $d ) : ?>
						<option value="<?php echo (int) $d; ?>" <?php selected( $days, $d ); ?>><?php echo (int) $d; ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label style="margin-left:12px;">
				<?php esc_html_e( 'Source', 'arsenal-settings' ); ?>
				<select name="dd_source">
					<option value=""><?php esc_html_e( 'All', 'arsenal-settings' ); ?></option>
					<option value="api" <?php selected( $source, 'api' ); ?>><?php esc_html_e( 'API', 'arsenal-settings' ); ?></option>
					<option value="cron" <?php selected( $source, 'cron' ); ?>><?php esc_html_e( 'Cron', 'arsenal-settings' ); ?></option>
					<option value="woocommerce" <?php selected( $source, 'woocommerce' ); ?>><?php esc_html_e( 'WooCommerce', 'arsenal-settings' ); ?></option>
				</select>
			</label>
			<label style="margin-left:12px;">
				<?php esc_html_e( 'Customer email', 'arsenal-settings' ); ?>
				<input type="search" name="dd_email" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search email…', 'arsenal-settings' ); ?>" />
			</label>
			<?php submit_button( __( 'Filter', 'arsenal-settings' ), 'secondary', '', false ); ?>
			<a class="button" href="<?php echo esc_url( $base_url ); ?>"><?php esc_html_e( 'Reset', 'arsenal-settings' ); ?></a>
		</form>

		<?php if ( array() === $items ) : ?>
			<p><?php esc_html_e( 'No Stripe direct debit payment failures found for the selected filters.', 'arsenal-settings' ); ?></p>
		<?php else : ?>
			<p><?php printf( esc_html__( 'Showing %d failure(s).', 'arsenal-settings' ), (int) count( $items ) ); ?></p>
			<?php $table->display(); ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Register submenu under Arsenal.
 */
function arsenal_settings_register_dd_payment_failures_menu() {
	add_submenu_page(
		'arsenal-settings',
		__( 'Direct debit payment failures', 'arsenal-settings' ),
		__( 'DD payment failures', 'arsenal-settings' ),
		'manage_options',
		'arsenal-settings-wc-dd-failures',
		'arsenal_settings_render_dd_payment_failures_page'
	);
}
add_action( 'admin_menu', 'arsenal_settings_register_dd_payment_failures_menu', 11 );
