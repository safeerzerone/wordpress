<?php
/**
 * Models B/C: WooCommerce gateway (arm_user_gateway=woocommerce, arm_payment_mode=manual_subscription),
 * optionally with Stripe tokens (cards / BACS / SEPA direct debit) or a direct Stripe subscription (Model C).
 *
 * ARMember core has no renewal scheduler for these models: renewals depend on WooCommerce orders and on
 * site-specific reconciliation code that this plugin intentionally does not call. Therefore this adapter is
 * report-only (supports_sync() === false) and never returns "verified" for renewal processing.
 *
 * Stripe key: woocommerce_stripe_settings (WooCommerce Stripe Gateway). WC_Subscriptions is not required and
 * the WPSwings subscription plugin is not inspected (documented limitation).
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Adapter_Woo_Stripe extends Gateway_Adapter {

	const RE_CUS = '/^cus_[A-Za-z0-9]+$/';
	const RE_PM  = '/^(?:pm|card|src)_[A-Za-z0-9]+$/';
	const RE_SUB = '/^sub_[A-Za-z0-9]+$/';

	public function id() {
		return 'woo_stripe';
	}

	public function label() {
		return 'Models B/C – WooCommerce gateway with Stripe tokens / direct Stripe subscription';
	}

	public function matches( array $facts ) {
		return 'woocommerce' === ( isset( $facts['audit']['gateway_key'] ) ? $facts['audit']['gateway_key'] : '' );
	}

	public function supports_sync() {
		return false;
	}

	public function fields( array $facts ) {
		$rows = array(
			self::field( array( 'key' => 'wc_stripe_customer_id', 'label' => 'WooCommerce Stripe customer ID', 'required' => true, 'format' => self::RE_CUS, 'format_hint' => 'cus_…', 'store' => 'woocommerce', 'source' => 'user option _stripe_customer_id / WC order meta', 'retrievable' => true, 'editable' => false, 'verify' => true, 'validation' => 'Owned by WooCommerce Stripe; never edited by this plugin.' ) ),
			self::field( array( 'key' => 'wc_payment_token', 'label' => 'WooCommerce payment token (Stripe)', 'required' => true, 'type' => 'int', 'format' => '/^[1-9][0-9]*$/', 'store' => 'woocommerce', 'source' => 'woocommerce_payment_tokens', 'retrievable' => false, 'editable' => false, 'verify' => false, 'validation' => 'Created only by WooCommerce checkout / My Account.' ) ),
			self::field( array( 'key' => 'stripe_payment_method', 'label' => 'Stripe PaymentMethod of the token', 'required' => true, 'format' => self::RE_PM, 'format_hint' => 'pm_…', 'store' => 'woocommerce', 'source' => 'payment token value', 'retrievable' => true, 'editable' => false, 'verify' => true ) ),
			self::field( array( 'key' => 'stripe_mandate', 'label' => 'Stripe mandate (direct debit, optional)', 'required' => false, 'format' => '/^mandate_[A-Za-z0-9]+$/', 'format_hint' => 'mandate_…', 'store' => 'woocommerce', 'source' => 'WC order meta _stripe_mandate_id', 'retrievable' => false, 'editable' => false, 'verify' => false, 'future' => false ) ),
			self::field( array( 'key' => 'stripe_subscription_id', 'label' => 'Stripe Subscription ID (Model C only)', 'required' => false, 'format' => self::RE_SUB, 'format_hint' => 'sub_…', 'store' => 'paylog', 'source' => 'payment log token', 'retrievable' => false, 'editable' => false, 'verify' => true, 'future' => false ) ),
		);
		return array_merge( self::arm_common_fields( 'woocommerce', 'manual_subscription', 'woocommerce', 'manual_subscription' ), $rows );
	}

	public function discover( array $facts ) {
		$found = array();
		self::arm_common_discover( $facts, $found );
		$wc = isset( $facts['wc'] ) ? $facts['wc'] : array();
		self::add( $found, 'wc_stripe_customer_id', isset( $wc['stripe_customer_id'] ) ? $wc['stripe_customer_id'] : '', 'user option _stripe_customer_id' );
		foreach ( isset( $wc['order_refs']['customers'] ) ? $wc['order_refs']['customers'] : array() as $c ) {
			self::add( $found, 'wc_stripe_customer_id', $c, 'WooCommerce order meta _stripe_customer_id' );
		}
		foreach ( isset( $wc['tokens'] ) ? $wc['tokens'] : array() as $t ) {
			if ( 0 === strpos( (string) $t['gateway_id'], 'stripe' ) ) {
				self::add( $found, 'wc_payment_token', $t['id'], 'WC token #' . $t['id'] . ' (' . $t['gateway_id'] . ')' );
				self::add( $found, 'stripe_payment_method', $t['token'], 'WC token #' . $t['id'] );
			}
		}
		foreach ( isset( $wc['order_refs']['mandates'] ) ? $wc['order_refs']['mandates'] : array() as $m ) {
			self::add( $found, 'stripe_mandate', $m, 'WooCommerce order meta _stripe_mandate_id' );
		}
		foreach ( self::paylog_values( $facts, '/^sub_/' ) as $r ) {
			self::add( $found, 'stripe_subscription_id', $r['value'], 'Payment log #' . $r['log_id'] . ' (' . $r['field'] . ')' );
		}
		return $found;
	}

	public function lookup( array $facts, array $found, Http $http, Secrets $secrets ) {
		$b = self::empty_lookup();
		$cus = array();
		foreach ( isset( $found['wc_stripe_customer_id'] ) ? $found['wc_stripe_customer_id'] : array() as $e ) {
			$cus[ $e['value'] ] = true;
		}
		$cus = array_keys( $cus );
		if ( ! $cus ) {
			self::step( $b, 'Stripe customer lookup', Status::STEP_SKIPPED, 'No WooCommerce Stripe customer id stored for this member; WooCommerce data must be created by checkout, not by this plugin.' );
			return $b;
		}
		$key = $secrets->stripe_key( 'woo' );
		$b['attempted'] = true;
		if ( '' === (string) $key ) {
			$b['error'] = 'WooCommerce Stripe credentials are not configured.';
			self::step( $b, 'Stripe credentials', Status::STEP_FAILED, $b['error'], '', $b['error'] );
			return $b;
		}
		if ( count( $cus ) > 1 ) {
			foreach ( $cus as $c ) {
				$b['candidates'][] = array( 'key' => 'wc_stripe_customer_id', 'value' => $c, 'note' => 'Several Stripe customers recorded for this member.' );
			}
			self::step( $b, 'Stripe customer lookup', Status::STEP_WARNING, 'Several Stripe customer ids are recorded; none selected automatically.' );
			return $b;
		}
		$api = new Stripe_Client( $http, $key );
		$r   = $api->get( 'customers/' . $cus[0] );
		if ( ! $r['ok'] ) {
			if ( 404 === $r['code'] ) {
				$b['invalid']['wc_stripe_customer_id'] = 'Stripe reports this customer does not exist.';
			} else {
				$b['error'] = $r['error'];
			}
			self::step( $b, 'Stripe customer lookup', Status::STEP_FAILED, 'Stripe request failed.', $cus[0], $r['error'] );
			return $b;
		}
		if ( ! empty( $r['body']['deleted'] ) ) {
			$b['invalid']['wc_stripe_customer_id'] = 'Stripe customer has been deleted.';
			self::step( $b, 'Stripe customer lookup', Status::STEP_FAILED, 'Customer deleted in Stripe.', $cus[0] );
			return $b;
		}
		$b['objects']['wc_stripe_customer_id'] = $cus[0];
		// Customer id comes from this user's own WooCommerce records ⇒ locally linked.
		$linked = ! empty( $facts['wc']['stripe_customer_id'] ) && $facts['wc']['stripe_customer_id'] === $cus[0];
		$b['ownership']      = $linked ? 'verified' : 'unverified';
		$b['ownership_note'] = $linked ? 'Customer id is the member\'s WooCommerce Stripe customer (user option).' : 'Customer id only seen on orders, not on the user record.';
		self::step( $b, 'Stripe customer lookup', Status::STEP_COMPLETED, 'Customer exists in Stripe.', $cus[0] );

		$pm_ids = array();
		foreach ( isset( $found['stripe_payment_method'] ) ? $found['stripe_payment_method'] : array() as $e ) {
			$pm_ids[ $e['value'] ] = true;
		}
		foreach ( array_keys( $pm_ids ) as $pm ) {
			if ( ! preg_match( self::RE_PM, $pm ) ) {
				continue;
			}
			$p = $api->get( 'payment_methods/' . $pm );
			if ( $p['ok'] && Stripe_Client::id_of( isset( $p['body']['customer'] ) ? $p['body']['customer'] : '' ) === $cus[0] ) {
				$b['objects']['stripe_payment_method'] = $pm;
				self::step( $b, 'PaymentMethod lookup', Status::STEP_COMPLETED, 'PaymentMethod (' . ( isset( $p['body']['type'] ) ? $p['body']['type'] : '?' ) . ') is attached to the customer.', $pm );
				break;
			}
			if ( $p['ok'] ) {
				$b['ownership']      = 'mismatch';
				$b['ownership_note'] = 'WooCommerce token PaymentMethod is attached to a different Stripe customer.';
				self::step( $b, 'PaymentMethod lookup', Status::STEP_FAILED, 'PaymentMethod belongs to another customer.', $pm );
			} else {
				self::step( $b, 'PaymentMethod lookup', Status::STEP_WARNING, 'PaymentMethod could not be retrieved.', $pm, $p['error'] );
			}
		}
		foreach ( isset( $found['stripe_subscription_id'] ) ? $found['stripe_subscription_id'] : array() as $e ) {
			$s = $api->get( 'subscriptions/' . $e['value'] );
			if ( $s['ok'] ) {
				$same = Stripe_Client::id_of( isset( $s['body']['customer'] ) ? $s['body']['customer'] : '' ) === $cus[0];
				$st   = isset( $s['body']['status'] ) ? $s['body']['status'] : '';
				self::step( $b, 'Direct subscription lookup (Model C)', $same ? Status::STEP_COMPLETED : Status::STEP_FAILED, 'Subscription status ' . $st . ( $same ? '' : ' – belongs to another customer' ), $e['value'] );
				if ( $same ) {
					$b['objects']['stripe_subscription_id'] = $e['value'];
					$b['sub_status'] = in_array( $st, array( 'active', 'trialing' ), true ) ? 'active' : 'inactive';
					$b['sub_detail'] = 'Stripe status: ' . $st;
				} else {
					$b['ownership'] = 'mismatch';
					$b['ownership_note'] = 'Stored direct subscription belongs to a different customer.';
				}
			}
		}
		$b['webhook'] = array( 'checked' => false, 'ok' => false, 'detail' => 'Renewal is reconciled by WooCommerce / site-specific code that this plugin does not inspect or call.' );
		return $b;
	}

	public function arm_expectations( array $facts ) {
		return array(
			'gateway_keys'   => array( 'woocommerce' ),
			'mode'           => 'manual_subscription',
			'label'          => 'arm_user_gateway=woocommerce, arm_payment_mode=manual_subscription',
			'renewal_driver' => 'WooCommerce renewal order / site-specific reconciliation',
		);
	}

	public function renewal_check( array $facts, array $gateway, array $fields ) {
		$tokens = 0;
		foreach ( isset( $facts['wc']['tokens'] ) ? $facts['wc']['tokens'] : array() as $t ) {
			if ( 0 === strpos( (string) $t['gateway_id'], 'stripe' ) ) {
				++$tokens;
			}
		}
		return array(
			'status' => 'not_verified',
			'detail' => 'ARMember has no renewal processor for the WooCommerce model; renewal depends on WooCommerce/site-specific code that cannot be verified here.',
			'checks' => array(
				array( 'label' => 'WooCommerce Stripe token(s) for member', 'status' => $tokens ? 'ok' : 'fail', 'detail' => $tokens . ' token(s).' ),
				array( 'label' => 'Renewal processor', 'status' => 'unknown', 'detail' => 'Not verifiable by this plugin (WC_Subscriptions absent; site-specific reconciliation not inspected).' ),
			),
		);
	}
}
