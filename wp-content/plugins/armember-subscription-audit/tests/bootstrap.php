<?php
/**
 * Minimal bootstrap for the WordPress-free unit tests (pure logic: adapters, assessor, redaction).
 * Run:  php tests/run-tests.php
 */

define( 'ABSPATH', __DIR__ . '/' );
$root = dirname( __DIR__ ) . '/includes/';
require_once $root . 'sync/class-status.php';
require_once $root . 'sync/class-http.php';
require_once $root . 'sync/class-logger.php';
require_once $root . 'gateways/abstract-gateway-adapter.php';
require_once $root . 'gateways/class-stripe-client.php';
require_once $root . 'gateways/class-adapter-stripe-native.php';
require_once $root . 'gateways/class-adapter-paypal-native.php';
require_once $root . 'gateways/class-adapter-woo-stripe.php';
require_once $root . 'gateways/class-adapter-unsupported.php';
require_once $root . 'gateways/class-gateway-factory.php';
require_once $root . 'sync/class-assessor.php';

/** Fake HTTP: routes by URL substring; records every request verb (proves read-only behaviour). */
final class Fake_Http implements \ARMSA\Http {
	public $calls  = array();
	public $routes = array();
	public function route( $needle, array $resp ) {
		$this->routes[ $needle ] = $resp;
	}
	private function resolve( $verb, $url ) {
		$this->calls[] = array( $verb, $url );
		// Longest needle wins so '/subscriptions/sub_1' beats '/subscriptions'.
		$best = null;
		foreach ( $this->routes as $needle => $resp ) {
			if ( false !== strpos( $url, $needle ) && ( null === $best || strlen( $needle ) > strlen( $best ) ) ) {
				$best = $needle;
			}
		}
		if ( null === $best ) {
			return array( 'ok' => false, 'code' => 404, 'body' => null, 'error' => 'HTTP 404' );
		}
		$r = $this->routes[ $best ];
		return array( 'ok' => ! empty( $r['ok'] ), 'code' => isset( $r['code'] ) ? $r['code'] : ( ! empty( $r['ok'] ) ? 200 : 500 ), 'body' => isset( $r['body'] ) ? $r['body'] : null, 'error' => isset( $r['error'] ) ? $r['error'] : '' );
	}
	public function get( $url, array $headers = array() ) {
		return $this->resolve( 'GET', $url );
	}
	public function post_form( $url, array $form ) {
		$r = $this->resolve( 'POST:' . ( isset( $form['METHOD'] ) ? $form['METHOD'] : '?' ), $url );
		return $r;
	}
}

final class Fake_Secrets implements \ARMSA\Secrets {
	public $stripe = 'sk_test_FAKE';
	public $paypal = array( 'user' => 'u', 'pwd' => 'p', 'signature' => 's', 'sandbox' => true );
	public function stripe_key( $scope ) {
		return $this->stripe;
	}
	public function paypal_nvp() {
		return $this->paypal;
	}
}

/** Facts builder with sane "healthy" defaults. */
function facts( array $o = array() ) {
	$base = array(
		'user'          => array( 'id' => 10, 'email' => 'm@example.test', 'login' => 'm', 'name' => 'Member' ),
		'plan_id'       => 5,
		'plan_name'     => 'Gold',
		'plan_kind'     => 'recurring',
		'has_plan_meta' => true,
		'audit'         => array( 'gateway' => 'stripe', 'gateway_key' => 'stripe', 'is_recurring' => true, 'from_profile' => false, 'mode' => 'auto_debit_subscription' ),
		'meta'          => array(
			'gateway' => 'stripe', 'mode' => 'auto_debit_subscription', 'subscr_id' => 'sub_1AAA', 'next_due' => 1900000000, 'completed' => 2, 'cancelled' => '', 'cycle' => 0,
			'profile_gateway' => '', 'selected_payment_method' => '',
			'raw' => array( 'arm_user_gateway' => 'stripe', 'arm_payment_mode' => 'auto_debit_subscription', 'arm_subscr_id' => 'sub_1AAA', 'arm_stripe' => array( 'customer_id' => 'cus_1AAA', 'transaction_id' => 'sub_1AAA' ), 'arm_paypal' => array() ),
		),
		'pay'           => array( 'count' => 3, 'latest' => array(), 'log_refs' => array(), 'gateways' => array( 'stripe' ), 'modes' => array( 'auto_debit_subscription' ), 'payer_emails' => array() ),
		'wc'            => array( 'available' => true, 'stripe_customer_id' => '', 'tokens' => array(), 'orders' => 0, 'order_refs' => array( 'customers' => array(), 'sources' => array(), 'mandates' => array() ), 'subscriptions_ext' => false ),
		'staged'        => array(),
		'shared_refs'   => array(),
	);
	return array_replace_recursive( $base, $o );
}

function stripe_ok_http( $status = 'active', $webhook = true ) {
	$h = new Fake_Http();
	$h->route( '/v1/subscriptions/sub_1AAA', array( 'ok' => true, 'body' => array( 'object' => 'subscription', 'id' => 'sub_1AAA', 'status' => $status, 'customer' => 'cus_1AAA', 'default_payment_method' => 'pm_1AAA' ) ) );
	$h->route( '/v1/webhook_endpoints', array( 'ok' => true, 'body' => array( 'data' => $webhook ? array( array( 'url' => 'https://x.test/?arm-listener=arm_stripe_notify', 'status' => 'enabled', 'enabled_events' => array( 'invoice.payment_succeeded' ) ) ) : array() ) ) );
	return $h;
}

function run_assess( array $facts, $http = null, $lookup = true, $secrets = null ) {
	$adapter = \ARMSA\Gateway_Factory::for_facts( $facts );
	$a       = new \ARMSA\Assessor();
	$log     = array();
	$res     = $a->assess( $facts, $adapter, array( 'lookup' => $lookup, 'http' => $http ? $http : new Fake_Http(), 'secrets' => $secrets ? $secrets : new Fake_Secrets(), 'log' => function ( $s, $st, $d ) use ( &$log ) { $log[] = array( $s, $st, $d ); } ) );
	$res['_log'] = $log;
	return $res;
}
