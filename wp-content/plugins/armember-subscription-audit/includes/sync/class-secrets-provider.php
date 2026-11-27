<?php
/**
 * Reads gateway credentials in-process from the plugins that already own them (ARMember gateway settings,
 * WooCommerce Stripe settings). Values are returned to adapters and never rendered, logged or stored.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Secrets_Provider implements Secrets {

	private function arm_gateway( $name ) {
		global $arm_payment_gateways;
		if ( ! is_object( $arm_payment_gateways ) || ! method_exists( $arm_payment_gateways, 'arm_get_active_payment_gateways' ) ) {
			return array();
		}
		$all = $arm_payment_gateways->arm_get_active_payment_gateways();
		return isset( $all[ $name ] ) && is_array( $all[ $name ] ) ? $all[ $name ] : array();
	}

	public function stripe_key( $scope ) {
		if ( 'native' === $scope ) {
			$o = $this->arm_gateway( 'stripe' );
			if ( ! $o ) {
				return '';
			}
			$live = isset( $o['stripe_payment_mode'] ) && 'live' === $o['stripe_payment_mode'];
			$k    = $live ? ( isset( $o['stripe_secret_key'] ) ? $o['stripe_secret_key'] : '' ) : ( isset( $o['stripe_test_secret_key'] ) ? $o['stripe_test_secret_key'] : '' );
			return is_string( $k ) ? trim( $k ) : '';
		}
		$s = get_option( 'woocommerce_stripe_settings', array() );
		if ( ! is_array( $s ) ) {
			return '';
		}
		$test = isset( $s['testmode'] ) && 'yes' === $s['testmode'];
		$k    = $test ? ( isset( $s['test_secret_key'] ) ? $s['test_secret_key'] : '' ) : ( isset( $s['secret_key'] ) ? $s['secret_key'] : '' );
		return is_string( $k ) ? trim( $k ) : '';
	}

	public function paypal_nvp() {
		$o = $this->arm_gateway( 'paypal' );
		if ( ! $o ) {
			return null;
		}
		$sandbox = isset( $o['paypal_payment_mode'] ) && 'sandbox' === $o['paypal_payment_mode'];
		$p       = $sandbox ? 'sandbox_' : 'live_';
		return array(
			'user'      => isset( $o[ $p . 'api_username' ] ) ? (string) $o[ $p . 'api_username' ] : '',
			'pwd'       => isset( $o[ $p . 'api_password' ] ) ? (string) $o[ $p . 'api_password' ] : '',
			'signature' => isset( $o[ $p . 'api_signature' ] ) ? (string) $o[ $p . 'api_signature' ] : '',
			'sandbox'   => $sandbox,
		);
	}
}
