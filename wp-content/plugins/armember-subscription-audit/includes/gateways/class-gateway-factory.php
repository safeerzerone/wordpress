<?php
/**
 * Picks the adapter for a member/plan. Order matters; Unsupported is always last.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Gateway_Factory {

	/** @return Gateway_Adapter[] */
	public static function all() {
		return array(
			new Adapter_Stripe_Native(),
			new Adapter_Paypal_Native(),
			new Adapter_Woo_Stripe(),
			new Adapter_Unsupported(),
		);
	}

	/**
	 * @param array $facts Facts (needs audit.gateway_key).
	 * @return Gateway_Adapter
	 */
	public static function for_facts( array $facts ) {
		foreach ( self::all() as $adapter ) {
			if ( $adapter->matches( $facts ) ) {
				return $adapter;
			}
		}
		return new Adapter_Unsupported();
	}
}
