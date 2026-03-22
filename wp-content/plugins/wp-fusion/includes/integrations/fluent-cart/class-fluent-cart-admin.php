<?php
/**
 * WP Fusion - FluentCart Admin Handler
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
 * Handles FluentCart admin functionality.
 *
 * FluentCart uses Vue.js for its admin interface. Order status is displayed
 * via the Activity log (order->addLog()) which we use in the integration manager.
 * Sidebar widgets are hardcoded in Vue.js and cannot be extended via PHP.
 *
 * @since 3.47.0
 */
class WPF_FluentCart_Admin {

	/**
	 * Constructor.
	 *
	 * @since 3.47.0
	 */
	public function __construct() {

		// Add process action handler.
		add_action( 'wp_ajax_wpf_process_fluent_cart_order', array( $this, 'process_order_again' ) );
	}

	/**
	 * Re-processes a FluentCart order.
	 *
	 * @since 3.47.0
	 */
	public function process_order_again() {

		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpf_process_fluent_cart_order' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'wp-fusion' ) ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-fusion' ) ) );
		}

		// Get order ID.
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'wp-fusion' ) ) );
		}

		// Load FluentCart order.
		if ( ! class_exists( '\FluentCart\App\Models\Order' ) ) {
			wp_send_json_error( array( 'message' => __( 'FluentCart is not active.', 'wp-fusion' ) ) );
		}

		try {
			$order = \FluentCart\App\Models\Order::with( array( 'customer', 'order_items', 'billing_address', 'shipping_address' ) )
				->find( $order_id );

			if ( ! $order ) {
				wp_send_json_error( array( 'message' => __( 'Order not found.', 'wp-fusion' ) ) );
			}

			// Clear processing flags (use FluentCart's meta system).
			$order->deleteMeta( 'wpf_complete' );

			// Trigger the order_paid_done event which will fire integration feeds.
			$admin_url  = admin_url( 'admin.php?page=fluent-cart#/orders/' . $order->id . '/view' );
			$order_link = '<a href="' . esc_url( $admin_url ) . '" target="_blank">#' . esc_html( $order->id ) . '</a>';
			wpf_log( 'info', 0, 'Manually reprocessing FluentCart order ' . $order_link . ' for WP Fusion.' );

			// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action(
				'fluent_cart/order_paid_done',
				array(
					'order' => $order,
				)
			);
			// phpcs:enable WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			// Add log to order.
			$order->addLog(
				__( 'WP Fusion Manual Reprocess', 'wp-fusion' ),
				__( 'Order was manually reprocessed for WP Fusion integration.', 'wp-fusion' ),
				'info',
				'WP Fusion'
			);

			wp_send_json_success( array( 'message' => __( 'Order successfully reprocessed for WP Fusion.', 'wp-fusion' ) ) );

		} catch ( Exception $e ) {
			wpf_log( 'error', 0, 'Error reprocessing FluentCart order #' . $order_id . ': ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}
}
