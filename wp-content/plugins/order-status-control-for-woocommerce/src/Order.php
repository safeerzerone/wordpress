<?php

namespace BP_Order_Control;

class Order {

	/**
	 * @var string
	 */
	public $orderStaus;

	public function __construct() {
		$this->orderStaus = get_option( 'wc_order_status_control', 'default' );
		add_action( 'woocommerce_payment_complete_order_status', array( $this, 'isVirtualMarkasComplete' ), 10, 3 );
		add_action( 'woocommerce_thankyou', array( $this, 'autoCompleteOrder' ), 10, 1 );
		// add_action( 'admin_init', [$this, 'dfwc_param_cehck'], 90 );
	}
	/**
	 * @link https: //docs.woocommerce.com/document/automatically-complete-orders/
	 *
	 * @param    $order_id
	 * @return
	 */
	function autoCompleteOrder( $order_id ) {

		if ( ! $order_id || 'all' != $this->orderStaus ) {
			return;
		}
		$order = wc_get_order( $order_id );
		$order->update_status( 'completed' );
	}
	/**
	 * check if the order is have virtual products then mark is as completed
	 *
	 * @param $orderId
	 */
	public function isVirtualMarkasComplete( $status, $orderId, $order ) {

		switch ( $this->orderStaus ) {

			case 'all':
				/**
				 * status control "all" is trigger on thank you page
				 * method "autoCompleteOrder()"
				 */
				return $status;

			break;

			case 'only_paid':
				return 'wc-completed';

			break;

			case 'only_virtual':
				$virtualProducts     = false;
				$onlyVirtualProducts = true;
				// check each products
				foreach ( $order->get_items() as $item_id => $item ) {

					$bvProduct = $item->get_product();
					if ( $bvProduct->get_virtual() ) {
						$virtualProducts = true;
					}
					if ( ! $bvProduct->get_virtual() ) {
						$onlyVirtualProducts = false;
					}
				}

				if ( $virtualProducts && $onlyVirtualProducts ) {
					return 'wc-completed';
				}

				break;

			default: // default
				return $status;
			break;
		}

		return $status;
	}

	/**
	 * Initializes a singleton instance
	 *
	 * @return $instance
	 */
	public static function init() {
		/**
		 * @var mixed
		 */
		static $instance = false;
		if ( ! $instance ) {
			$instance = new self();
		}
		return $instance;
	}
}
