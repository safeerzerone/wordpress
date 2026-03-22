<?php
/**
 * WP Fusion - FluentCart Subscriptions Handler
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.47.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles FluentCart subscription meta field syncing.
 *
 * Tag application for subscriptions is handled by the integration feed system
 * in class-fluent-cart-integration.php. This class ONLY syncs custom fields
 * (subscription_status, recurring_amount, next_billing_date) to the CRM.
 *
 * @since 3.47.0
 */
class WPF_FluentCart_Subscriptions {

	/**
	 * Constructor.
	 *
	 * @since 3.47.0
	 */
	public function __construct() {

		// Subscription meta sync hooks (for custom field updates only).
		add_action( 'fluent_cart/subscription_activated', array( $this, 'sync_subscription_meta' ), 10, 1 );
		add_action( 'fluent_cart/subscription_renewed', array( $this, 'sync_subscription_meta' ), 10, 1 );
		add_action( 'fluent_cart/subscription_canceled', array( $this, 'sync_subscription_meta' ), 10, 1 );
		add_action( 'fluent_cart/payments/subscription_expired', array( $this, 'sync_subscription_meta' ), 10, 1 );
		add_action( 'fluent_cart/payments/subscription_completed', array( $this, 'sync_subscription_meta' ), 10, 1 );
	}

	/**
	 * Syncs subscription meta fields to CRM.
	 *
	 * This method is called on subscription events to update custom fields
	 * (status, recurring amount, next billing date) in the CRM.
	 *
	 * Tag application is handled by the integration feed system in
	 * class-fluent-cart-integration.php.
	 *
	 * @since 3.47.0
	 *
	 * @param array $data The event data containing subscription object.
	 */
	public function sync_subscription_meta( $data ) {

		if ( empty( $data['subscription'] ) ) {
			return;
		}

		$subscription = $data['subscription'];
		$user_id      = ! empty( $subscription->customer->user_id ) ? $subscription->customer->user_id : 0;

		// Only sync for registered users.
		if ( $user_id < 1 ) {
			return;
		}

		// Build subscription meta fields.
		$meta = array(
			'subscription_status' => $subscription->status,
			'recurring_amount'    => ! empty( $subscription->recurring_total ) ? $subscription->recurring_total : 0,
		);

		if ( ! empty( $subscription->next_billing_date ) ) {
			$meta['next_billing_date'] = $subscription->next_billing_date;
		}

		// Push to CRM.
		wp_fusion()->user->push_user_meta( $user_id, $meta );
	}
}
