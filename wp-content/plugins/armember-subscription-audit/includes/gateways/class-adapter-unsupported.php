<?php
/**
 * Fallback for every payment model without a verified integration (GoCardless, 2Checkout, Authorize.net,
 * bank transfer, PayPal REST / vault, unknown, empty). Report-only: no lookup, no sync.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Adapter_Unsupported extends Gateway_Adapter {

	public function id() {
		return 'unsupported';
	}

	public function label() {
		return 'Unsupported payment model';
	}

	public function matches( array $facts ) {
		return true;
	}

	public function supports_sync() {
		return false;
	}

	public function fields( array $facts ) {
		return array_merge(
			self::arm_common_fields( '.*', '.*', 'any', 'any' ),
			array()
		);
	}

	public function discover( array $facts ) {
		$found = array();
		self::arm_common_discover( $facts, $found );
		return $found;
	}

	public function lookup( array $facts, array $found, Http $http, Secrets $secrets ) {
		$b = self::empty_lookup();
		self::step( $b, 'Gateway lookup', Status::STEP_SKIPPED, 'No supported integration for this payment model; nothing was requested from any gateway.' );
		return $b;
	}

	public function arm_expectations( array $facts ) {
		return array( 'gateway_keys' => array(), 'mode' => '', 'label' => 'n/a', 'renewal_driver' => 'unknown' );
	}

	public function renewal_check( array $facts, array $gateway, array $fields ) {
		return array( 'status' => 'manual', 'detail' => 'Unsupported payment model – manual review.', 'checks' => array() );
	}
}
