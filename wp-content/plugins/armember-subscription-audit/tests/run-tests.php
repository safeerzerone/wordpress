<?php
/**
 * WordPress-free unit tests. Run: php tests/run-tests.php   (exit code 1 on failure)
 *
 * Covers the pure logic only (adapters, assessor, redaction, read-only HTTP guarantee). Queue, writer and
 * admin handlers need WordPress + a database – see tests/INTEGRATION.md for those scenarios.
 */

require __DIR__ . '/bootstrap.php';

use ARMSA\Status;

$GLOBALS['t_pass'] = 0;
$GLOBALS['t_fail'] = array();
function t( $name, $cond, $detail = '' ) {
	if ( $cond ) {
		++$GLOBALS['t_pass'];
		echo "  ok   $name\n";
	} else {
		$GLOBALS['t_fail'][] = $name;
		echo "  FAIL $name $detail\n";
	}
}
function section( $s ) {
	echo "\n== $s\n";
}

// ---------------------------------------------------------------------------------------------
section( 'Stripe native' );
$r = run_assess( facts(), stripe_ok_http() );
t( 'valid subscription+customer: gateway verified', Status::GW_VERIFIED === $r['gateway']['status'], $r['gateway']['status'] . ' ' . $r['gateway']['explain'] );
t( 'valid: ARMember renewal verified, no proposals', Status::ARM_VERIFIED === $r['arm']['status'] && ! $r['proposals'], $r['arm']['status'] );
t( 'valid: overall = renewal readiness verified', Status::OV_RENEWAL_OK === $r['overall']['status'], $r['overall']['status'] );

$f = facts( array( 'meta' => array( 'raw' => array( 'arm_subscr_id' => '', 'arm_stripe' => array( 'customer_id' => 'cus_1AAA', 'transaction_id' => '' ) ), 'subscr_id' => '' ), 'pay' => array( 'log_refs' => array( array( 'log_id' => 7, 'field' => 'arm_token', 'value' => 'sub_1AAA', 'gateway' => 'stripe' ) ) ) ) );
$r = run_assess( $f, stripe_ok_http() );
t( 'sub id only in payment log: ready for sync', Status::OV_READY === $r['overall']['status'], $r['overall']['status'] . ' ' . $r['overall']['action'] );
t( 'ready: proposals target arm_subscr_id and arm_stripe.transaction_id', 2 === count( $r['proposals'] ), count( $r['proposals'] ) );
t( 'ready: gateway verified but ARMember mapping required', Status::ARM_MAPPING === $r['arm']['status'], $r['arm']['status'] );

$r = run_assess( facts( array( 'meta' => array( 'raw' => array( 'arm_subscr_id' => '', 'arm_stripe' => array( 'customer_id' => '', 'transaction_id' => '' ) ), 'subscr_id' => '' ) ) ), null, false );
t( 'nothing stored, no lookup: gateway lookup required', Status::GW_LOOKUP === $r['gateway']['status'], $r['gateway']['status'] );

$h = new Fake_Http();
$h->route( '/v1/customers', array( 'ok' => true, 'body' => array( 'data' => array( array( 'id' => 'cus_A1' ), array( 'id' => 'cus_B2' ) ) ) ) );
$h->route( '/v1/webhook_endpoints', array( 'ok' => true, 'body' => array( 'data' => array() ) ) );
$r = run_assess( facts( array( 'meta' => array( 'raw' => array( 'arm_subscr_id' => '', 'arm_stripe' => array( 'customer_id' => '', 'transaction_id' => '' ) ), 'subscr_id' => '' ) ) ), $h );
t( 'two customers share the e-mail: Multiple Matching Records, none auto-selected', Status::GW_MULTIPLE === $r['gateway']['status'] && '' === $r['fields']['stripe_customer_id']['current'], $r['gateway']['status'] );
t( 'multiple matches: no proposals, needs review', ! $r['proposals'] && Status::OV_REVIEW === $r['overall']['status'] );

$h = new Fake_Http();
$h->route( '/v1/customers', array( 'ok' => true, 'body' => array( 'data' => array( array( 'id' => 'cus_A1' ) ) ) ) );
$r = run_assess( facts( array( 'meta' => array( 'raw' => array( 'arm_subscr_id' => '', 'arm_stripe' => array( 'customer_id' => '', 'transaction_id' => '' ) ), 'subscr_id' => '' ) ) ), $h );
t( 'single e-mail match is NOT proof: ownership verification required', Status::GW_OWNERSHIP === $r['gateway']['status'] && ! $r['proposals'], $r['gateway']['status'] );

$r = run_assess( facts(), stripe_ok_http( 'canceled' ) );
t( 'inactive Stripe subscription: Gateway Subscription Inactive', Status::GW_INACTIVE === $r['gateway']['status'], $r['gateway']['status'] );
t( 'inactive: overall = gateway verification failed, no proposals', Status::OV_GW_FAILED === $r['overall']['status'] && ! $r['proposals'] );

$h = new Fake_Http();
$h->route( '/v1/subscriptions/sub_1AAA', array( 'ok' => false, 'code' => 500, 'error' => 'HTTP 500' ) );
$r = run_assess( facts(), $h );
t( 'failed gateway API request: Gateway API Error', Status::GW_API_ERROR === $r['gateway']['status'] && Status::OV_GW_FAILED === $r['overall']['status'], $r['gateway']['status'] );

$h = new Fake_Http();
$h->route( '/v1/subscriptions/sub_1AAA', array( 'ok' => false, 'code' => 404, 'error' => 'HTTP 404' ) );
$r = run_assess( facts(), $h );
t( 'Stripe 404: Invalid Gateway Information (not API error)', Status::GW_INVALID === $r['gateway']['status'], $r['gateway']['status'] );

$r = run_assess( facts(), stripe_ok_http( 'active', false ) );
t( 'webhook endpoint missing: Gateway Linked – ARMember Renewal Not Verified', Status::OV_LINKED_UNVER === $r['overall']['status'] && Status::ARM_NOT_VERIFIED === $r['arm']['status'], $r['overall']['status'] );

$r = run_assess( facts( array( 'meta' => array( 'mode' => 'manual_subscription', 'raw' => array( 'arm_payment_mode' => 'manual_subscription' ) ) ) ), stripe_ok_http() );
t( 'incorrect ARMember payment mode: Incorrect Payment Configuration, never auto-fixed', Status::ARM_INCORRECT === $r['arm']['status'] && ! $r['proposals'] && Status::OV_REVIEW === $r['overall']['status'], $r['arm']['status'] );

$r = run_assess( facts( array( 'shared_refs' => array( 'sub_1AAA' => array( 99 ) ) ) ), stripe_ok_http() );
t( 'duplicate gateway reference on another member: conflict, review', Status::OV_REVIEW === $r['overall']['status'] && ! $r['proposals'], $r['overall']['status'] );

$r = run_assess( facts( array( 'meta' => array( 'raw' => array( 'arm_stripe' => array( 'customer_id' => 'cus_OTHER' ) ) ) ) ), stripe_ok_http() );
t( 'conflicting customer ids (plan meta vs stripe sub): not ready', ! in_array( $r['overall']['status'], array( Status::OV_READY, Status::OV_RENEWAL_OK ), true ), $r['overall']['status'] );

$r = run_assess( facts( array( 'has_plan_meta' => false, 'meta' => array( 'next_due' => '', 'raw' => array() ) ) ), stripe_ok_http() );
t( 'ARMember metadata missing: not ready, never synchronised', ! in_array( $r['overall']['status'], array( Status::OV_READY, Status::OV_RENEWAL_OK ), true ) && ! $r['proposals'], $r['overall']['status'] );

$r = run_assess( facts( array( 'staged' => array( 'stripe_subscription_id' => array( 'value' => 'sub_1AAA', 'confirmed' => false ) ), 'meta' => array( 'raw' => array( 'arm_subscr_id' => '', 'arm_stripe' => array( 'customer_id' => '', 'transaction_id' => '' ) ), 'subscr_id' => '' ) ) ), stripe_ok_http() );
t( 'staged value with no local customer link & not confirmed: ownership required', Status::GW_OWNERSHIP === $r['gateway']['status'] && ! $r['proposals'], $r['gateway']['status'] );
$r = run_assess( facts( array( 'staged' => array( 'stripe_subscription_id' => array( 'value' => 'sub_1AAA', 'confirmed' => true ) ), 'meta' => array( 'raw' => array( 'arm_subscr_id' => '', 'arm_stripe' => array( 'customer_id' => '', 'transaction_id' => '' ) ), 'subscr_id' => '' ) ) ), stripe_ok_http() );
t( 'staged value confirmed by admin and verified in Stripe: ready for sync', Status::OV_READY === $r['overall']['status'], $r['overall']['status'] . ' ' . $r['gateway']['explain'] );

// ---------------------------------------------------------------------------------------------
section( 'PayPal (ARMember native, IPN)' );
$pp = function ( array $o = array() ) {
	return facts( array_replace_recursive( array(
		'audit' => array( 'gateway' => 'paypal', 'gateway_key' => 'paypal' ),
		'meta'  => array( 'gateway' => 'paypal', 'raw' => array( 'arm_user_gateway' => 'paypal', 'arm_subscr_id' => 'I-ABCDEF123456', 'arm_paypal' => array( 'arm_subscr_id' => 'I-ABCDEF123456' ), 'arm_stripe' => array() ) ),
	), $o ) );
};
$nvp = function ( $status = 'Active' ) {
	$h = new Fake_Http();
	$h->route( 'nvp', array( 'ok' => true, 'body' => array( 'ACK' => 'Success', 'STATUS' => $status, 'NEXTBILLINGDATE' => '2030-01-01T00:00:00Z' ) ) );
	return $h;
};
$r = run_assess( $pp(), $nvp() );
t( 'PayPal all details: gateway verified', Status::GW_VERIFIED === $r['gateway']['status'], $r['gateway']['status'] . $r['gateway']['explain'] );
t( 'PayPal: renewal never verified (IPN unverifiable) → linked-not-verified', Status::OV_LINKED_UNVER === $r['overall']['status'], $r['overall']['status'] );

$r = run_assess( $pp( array( 'meta' => array( 'subscr_id' => '', 'raw' => array( 'arm_subscr_id' => '', 'arm_paypal' => array( 'arm_subscr_id' => '' ) ) ) ) ), $nvp() );
t( 'PayPal missing subscription reference: Manual Input Required (no e-mail search API)', Status::GW_MANUAL === $r['gateway']['status'] && Status::OV_MANUAL === $r['overall']['status'], $r['gateway']['status'] );

$r = run_assess( $pp(), $nvp( 'Cancelled' ) );
t( 'PayPal inactive profile: Gateway Subscription Inactive', Status::GW_INACTIVE === $r['gateway']['status'], $r['gateway']['status'] );

$r = run_assess( $pp( array( 'meta' => array( 'raw' => array( 'arm_subscr_id' => 'I-ABCDEF123456', 'arm_paypal' => array( 'arm_subscr_id' => 'I-ZZZZZZ999999' ) ) ) ) ), $nvp() );
t( 'PayPal conflicting profile ids: Multiple Matching Records', Status::GW_MULTIPLE === $r['gateway']['status'], $r['gateway']['status'] );

$r = run_assess( $pp( array( 'meta' => array( 'subscr_id' => '', 'raw' => array( 'arm_subscr_id' => '', 'arm_paypal' => array( 'arm_subscr_id' => '' ) ) ), 'staged' => array( 'paypal_profile_id' => array( 'value' => 'I-ABCDEF123456', 'confirmed' => false ) ) ) ), $nvp() );
t( 'PayPal admin-supplied id without ownership proof: Ownership Verification Required', Status::GW_OWNERSHIP === $r['gateway']['status'] && ! $r['proposals'], $r['gateway']['status'] );

$r = run_assess( $pp( array( 'meta' => array( 'raw' => array( 'arm_subscr_id' => '', 'arm_paypal' => array( 'arm_subscr_id' => 'I-ABCDEF123456' ) ), 'subscr_id' => '' ) ) ), $nvp() );
t( 'PayPal id only in arm_paypal: sync would add arm_subscr_id (not overwrite)', 1 === count( $r['proposals'] ) && array( 'arm_subscr_id' ) === $r['proposals'][0]['path'], json_encode( $r['proposals'] ) );

$h = new Fake_Http();
$h->route( 'nvp', array( 'ok' => true, 'body' => array( 'ACK' => 'Failure', 'L_ERRORCODE0' => '11592', 'L_LONGMESSAGE0' => 'Invalid profile id' ) ) );
$r = run_assess( $pp(), $h );
t( 'PayPal unknown profile (11592): Invalid Gateway Information', Status::GW_INVALID === $r['gateway']['status'], $r['gateway']['status'] );

$h = new Fake_Http();
$h->route( 'nvp', array( 'ok' => false, 'code' => 503, 'error' => 'HTTP 503' ) );
$r = run_assess( $pp(), $h );
t( 'PayPal outage: Gateway API Error', Status::GW_API_ERROR === $r['gateway']['status'], $r['gateway']['status'] );

$r = run_assess( $pp( array( 'audit' => array( 'gateway' => '', 'from_profile' => true ), 'meta' => array( 'gateway' => '', 'raw' => array( 'arm_user_gateway' => '' ) ) ) ), $nvp() );
t( 'imported member (ARMember gateway empty, profile says PayPal): Incorrect Payment Configuration, not auto-fixed', Status::ARM_INCORRECT === $r['arm']['status'], $r['arm']['status'] );

$r = run_assess( facts( array( 'audit' => array( 'gateway' => 'ppcp-gateway', 'gateway_key' => 'paypal', 'from_profile' => false ) ) ) );
t( 'WooCommerce PayPal Payments gateway id (ppcp-gateway): Unsupported Payment Model', Status::GW_UNSUPPORTED === $r['gateway']['status'], $r['gateway']['status'] );

// ---------------------------------------------------------------------------------------------
section( 'WooCommerce (Stripe tokens / direct debit)' );
$wc = function ( array $o = array() ) {
	return facts( array_replace_recursive( array(
		'audit' => array( 'gateway' => 'woocommerce', 'gateway_key' => 'woocommerce', 'mode' => 'manual_subscription' ),
		'meta'  => array( 'gateway' => 'woocommerce', 'mode' => 'manual_subscription', 'subscr_id' => '', 'raw' => array( 'arm_user_gateway' => 'woocommerce', 'arm_payment_mode' => 'manual_subscription', 'arm_subscr_id' => '', 'arm_stripe' => array() ) ),
		'wc'    => array( 'stripe_customer_id' => 'cus_W1', 'tokens' => array( array( 'id' => 31, 'gateway_id' => 'stripe_bacs_debit', 'token' => 'pm_W1', 'type' => 'stripe_bacs' ) ) ),
	), $o ) );
};
$whttp = function ( $pm_customer = 'cus_W1' ) {
	$h = new Fake_Http();
	$h->route( '/v1/customers/cus_W1', array( 'ok' => true, 'body' => array( 'id' => 'cus_W1', 'email' => 'm@example.test' ) ) );
	$h->route( '/v1/payment_methods/pm_W1', array( 'ok' => true, 'body' => array( 'id' => 'pm_W1', 'customer' => $pm_customer, 'type' => 'bacs_debit' ) ) );
	return $h;
};
$r = run_assess( $wc(), $whttp() );
t( 'WooCommerce valid token: gateway verified', Status::GW_VERIFIED === $r['gateway']['status'], $r['gateway']['status'] . $r['gateway']['explain'] );
t( 'WooCommerce: never "Renewal Verified"; shows Gateway Linked – Renewal Not Verified; no proposals', Status::OV_LINKED_UNVER === $r['overall']['status'] && ! $r['proposals'], $r['overall']['status'] );

$nt = $wc(); $nt['wc']['tokens'] = array();
$r = run_assess( $nt, $whttp() );
t( 'WooCommerce without token: Missing Gateway Information (cannot be typed in)', Status::GW_MISSING === $r['gateway']['status'] && Status::OV_MISSING === $r['overall']['status'], $r['gateway']['status'] );

$r = run_assess( $wc(), $whttp( 'cus_SOMEONE_ELSE' ) );
t( 'WooCommerce token PaymentMethod attached to another Stripe customer: ownership mismatch → invalid', Status::GW_INVALID === $r['gateway']['status'], $r['gateway']['status'] );

$r = run_assess( $wc( array( 'meta' => array( 'mode' => 'auto_debit_subscription', 'raw' => array( 'arm_payment_mode' => 'auto_debit_subscription' ) ) ) ), $whttp() );
t( 'WooCommerce with auto_debit mode: Incorrect Payment Configuration', Status::ARM_INCORRECT === $r['arm']['status'], $r['arm']['status'] );

// ---------------------------------------------------------------------------------------------
section( 'Unsupported / safety' );
$r = run_assess( facts( array( 'audit' => array( 'gateway' => 'gocardless', 'gateway_key' => 'gocardless' ) ) ) );
t( 'GoCardless: Unsupported Payment Model, Needs Review', Status::GW_UNSUPPORTED === $r['gateway']['status'] && Status::OV_REVIEW === $r['overall']['status'] );

$all = array();
foreach ( array( stripe_ok_http(), $nvp(), $whttp() ) as $h ) {
	$all = array_merge( $all, $h->calls );
}
run_assess( facts(), $hh = stripe_ok_http() );
run_assess( $pp(), $hp = $nvp() );
run_assess( $wc(), $hw = $whttp() );
$verbs = array();
foreach ( array_merge( $hh->calls, $hp->calls, $hw->calls ) as $c ) {
	$verbs[ $c[0] ] = true;
}
t( 'only GET and the read-only NVP detail call are ever issued (no charge/agreement creation)', array() === array_diff( array_keys( $verbs ), array( 'GET', 'POST:GetRecurringPaymentsProfileDetails' ) ), implode( ',', array_keys( $verbs ) ) );

$src = file_get_contents( dirname( __DIR__ ) . '/includes/gateways/class-stripe-client.php' );
t( 'Stripe client source contains no POST/DELETE capability', false === stripos( $src, 'post(' ) && false === stripos( $src, 'delete' ) );

t( 'redaction removes Stripe keys, bearer tokens and card-like numbers', false === strpos( \ARMSA\Logger::redact( 'key sk_live_ABC123 Bearer abc.def PWD=secret 4242 4242 4242 4242' ), 'sk_live' ) && false === strpos( \ARMSA\Logger::redact( 'PWD=secret' ), 'secret' ) && false === strpos( \ARMSA\Logger::redact( '4242 4242 4242 4242' ), '4242' ) );

$r = run_assess( facts(), stripe_ok_http() );
$steps = array_map( function ( $l ) { return $l[0]; }, $r['_log'] );
t( 'every assessment step is reported to the log (member, payment meta, history, gateway, lookup, readiness, completed)', in_array( 'Member and membership information loaded', $steps, true ) && in_array( 'Assessment completed', $steps, true ) && in_array( 'Existing payment history inspected', $steps, true ) && in_array( 'Subscription lookup', $steps, true ) );

$log_text = json_encode( $r['_log'] );
t( 'no secrets in the log', false === strpos( $log_text, 'sk_test_FAKE' ) );

// ---------------------------------------------------------------------------------------------
section( 'Payment type: only recurring plans sync' );
foreach ( array( 'one_time', 'lifetime', 'free' ) as $k ) {
	$h = stripe_ok_http();
	$f = facts( array( 'plan_kind' => $k, 'meta' => array( 'raw' => array( 'arm_subscr_id' => '', 'arm_stripe' => array( 'customer_id' => '', 'transaction_id' => '' ) ), 'subscr_id' => '' ), 'pay' => array( 'log_refs' => array( array( 'log_id' => 1, 'field' => 'arm_token', 'value' => 'sub_1AAA', 'gateway' => 'stripe' ) ) ) ) );
	$r = run_assess( $f, $h );
	t( "$k plan: Not Applicable, no gateway call, no proposals, nothing missing", Status::OV_NOT_APPLICABLE === $r['overall']['status'] && ! $h->calls && ! $r['proposals'] && ! $r['missing'] && 'one_time' === $r['payment_type']['type'], $r['overall']['status'] . ' calls=' . count( $h->calls ) );
}
$r = run_assess( facts( array( 'plan_kind' => 'unknown' ) ), $h = stripe_ok_http() );
t( 'unknown plan definition: Needs Review, never synced, no gateway call', Status::OV_REVIEW === $r['overall']['status'] && ! $h->calls && 'undetermined' === $r['payment_type']['type'] );
$r = run_assess( facts(), stripe_ok_http() );
t( 'recurring plan still assessed normally', 'recurring' === $r['payment_type']['type'] && Status::OV_RENEWAL_OK === $r['overall']['status'] );

require __DIR__ . '/test-writer-and-load.php';

echo "\n" . $GLOBALS['t_pass'] . ' passed, ' . count( $GLOBALS['t_fail'] ) . " failed\n";
if ( $GLOBALS['t_fail'] ) {
	echo 'Failed: ' . implode( '; ', $GLOBALS['t_fail'] ) . "\n";
	exit( 1 );
}
