<?php
/**
 * WP Fusion - FluentCart Batch Handler
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
 * Handles FluentCart batch processing.
 *
 * @since 3.47.0
 */
class WPF_FluentCart_Batch {

	/**
	 * Constructor.
	 *
	 * @since 3.47.0
	 */
	public function __construct() {

		// Export functions.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_fluent_cart_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_fluent_cart', array( $this, 'batch_step' ) );
	}

	/**
	 * Adds FluentCart checkbox to available export options.
	 *
	 * @since 3.47.0
	 *
	 * @param array $options The export options.
	 * @return array Options.
	 */
	public function export_options( $options ) {

		$options['fluent_cart'] = array(
			'label'         => __( 'FluentCart orders', 'wp-fusion' ),
			'title'         => __( 'Orders', 'wp-fusion' ),
			'process_again' => true,
			'tooltip'       => __( 'Finds FluentCart orders that have not been processed by WP Fusion, and triggers the integration feeds configured for each product.', 'wp-fusion' ),
		);

		return $options;
	}

	/**
	 * Gets total number of orders to be processed.
	 *
	 * @since 3.47.0
	 *
	 * @param array $args The batch arguments.
	 * @return array Order IDs.
	 */
	public function batch_init( $args ) {

		if ( ! class_exists( '\FluentCart\App\Models\Order' ) ) {
			return array();
		}

		$order_ids = array();

		try {
			// Query paid orders.
			$orders_query = \FluentCart\App\Models\Order::where( 'payment_status', 'paid' )
				->orderBy( 'id', 'ASC' );

			// Skip processed if requested.
				if ( ! empty( $args['skip_processed'] ) ) {
					// Exclude orders already processed by checking FluentCart order meta.
						if ( class_exists( '\FluentCart\App\Models\OrderMeta' ) ) {
						$processed_rows = \FluentCart\App\Models\OrderMeta::query()
							->where( 'meta_key', 'wpf_complete' )
							->get();

						$processed_ids = array();
						foreach ( $processed_rows as $row ) {
							if ( isset( $row->order_id ) ) {
								$processed_ids[] = (int) $row->order_id;
							}
						}

						if ( ! empty( $processed_ids ) ) {
							$orders_query->whereNotIn( 'id', $processed_ids );
						}
					}
				}

			$orders = $orders_query->get();

			foreach ( $orders as $order ) {
				$order_ids[] = $order->id;
			}
		} catch ( Exception $e ) {
			wpf_log( 'error', 0, 'Error querying FluentCart orders for batch processing: ' . $e->getMessage() );
		}

		return $order_ids;
	}

	/**
	 * Processes FluentCart order actions in batches.
	 *
	 * Manually triggers FluentCart's order_paid event which will fire
	 * any configured integration feeds for the products in the order.
	 *
	 * @since 3.47.0
	 *
	 * @param int $order_id The order ID.
	 */
	public function batch_step( $order_id ) {

		if ( ! class_exists( '\FluentCart\App\Models\Order' ) ) {
			return;
		}

		try {
			// Load the order.
			$order = \FluentCart\App\Models\Order::with( array( 'customer', 'order_items', 'billing_address', 'shipping_address' ) )
				->find( $order_id );

			if ( ! $order ) {
				wpf_log( 'notice', 0, 'FluentCart order #' . $order_id . ' not found during batch processing.' );
				return;
			}

				// Trigger the order_paid_done event which will fire integration feeds.
				// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action(
					'fluent_cart/order_paid_done',
					array(
						'order' => $order,
					)
				);
			// phpcs:enable WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		} catch ( Exception $e ) {
			wpf_log( 'error', 0, 'Error processing FluentCart order #' . $order_id . ' in batch: ' . $e->getMessage() );
		}
	}
}
