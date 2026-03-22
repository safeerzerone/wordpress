<?php
/**
 * WP Fusion - FluentCart Integration
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
 * Handles integration with FluentCart.
 *
 * @since 3.47.0
 */
class WPF_FluentCart extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.47.0
	 * @var string $slug
	 */
	public $slug = 'fluent-cart';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.47.0
	 * @var string $name
	 */
	public $name = 'FluentCart';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.47.0
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/ecommerce/fluent-cart/';

	/**
	 * Admin class instance.
	 *
	 * @since 3.47.0
	 * @var WPF_FluentCart_Admin
	 */
	public $admin;

	/**
	 * Subscriptions class instance.
	 *
	 * @since 3.47.0
	 * @var WPF_FluentCart_Subscriptions
	 */
	public $subscriptions;

	/**
	 * Batch class instance.
	 *
	 * @since 3.47.0
	 * @var WPF_FluentCart_Batch
	 */
	public $batch;

	/**
	 * Gets things started.
	 *
	 * @since 3.47.0
	 */
	public function init() {

		// Register settings.
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		// Load integration manager (extends FluentCart's BaseIntegrationManager).
		// This handles ALL order, refund, and subscription events through FluentCart's feed system.
		require_once __DIR__ . '/class-fluent-cart-integration.php';

		// Load admin functionality (order widgets, reprocessing).
		require_once __DIR__ . '/class-fluent-cart-admin.php';
		$this->admin = new WPF_FluentCart_Admin();

		// Load batch export functionality.
		require_once __DIR__ . '/class-fluent-cart-batch.php';
		$this->batch = new WPF_FluentCart_Batch();

		// Load subscriptions handler (only for meta field syncing).
		// Tag application for subscriptions is handled by the integration manager.
		if ( $this->has_subscription_support() ) {
			require_once __DIR__ . '/class-fluent-cart-subscriptions.php';
			$this->subscriptions = new WPF_FluentCart_Subscriptions();
		}
	}

	/**
	 * Checks if FluentCart has subscription support.
	 *
	 * @since 3.47.0
	 *
	 * @return bool True if subscriptions are supported.
	 */
	public function has_subscription_support() {
		return class_exists( '\FluentCart\App\Models\Subscription' );
	}

	/**
	 * Registers FluentCart settings in the Integrations tab.
	 *
	 * @since 3.47.0
	 *
	 * @param array $settings The registered settings.
	 * @param array $options  The options in the database.
	 * @return array Settings.
	 */
	public function register_settings( $settings, $options ) {

		$settings['fluent_cart_header'] = array(
			'title'   => __( 'FluentCart Integration', 'wp-fusion' ),
			'url'     => $this->docs_url,
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['fluent_cart_apply_tags_customers'] = array(
			'title'   => __( 'Apply Tags to Customers', 'wp-fusion' ),
			'desc'    => __( 'These tags will be applied to all FluentCart customers.', 'wp-fusion' ),
			'std'     => array(),
			'type'    => 'assign_tags',
			'section' => 'integrations',
		);

		return $settings;
	}

	/**
	 * Adds FluentCart field group to meta fields list.
	 *
	 * @since 3.47.0
	 *
	 * @param array $field_groups The field groups.
	 * @return array Field groups.
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['fluent-cart'] = array(
			'title' => __( 'FluentCart', 'wp-fusion' ),
			'url'   => $this->docs_url,
		);

		return $field_groups;
	}

	/**
	 * Adds FluentCart meta fields to WP Fusion contact fields.
	 *
	 * @since 3.47.0
	 *
	 * @param array $meta_fields The meta fields.
	 * @return array Meta fields.
	 */
	public function add_meta_fields( $meta_fields ) {

		// Billing fields.
		$meta_fields['billing_address_1'] = array(
			'label' => __( 'Billing Address 1', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		$meta_fields['billing_address_2'] = array(
			'label' => __( 'Billing Address 2', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		$meta_fields['billing_city'] = array(
			'label' => __( 'Billing City', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		$meta_fields['billing_state'] = array(
			'label' => __( 'Billing State', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		$meta_fields['billing_country'] = array(
			'label' => __( 'Billing Country', 'wp-fusion' ),
			'type'  => 'country',
			'group' => 'fluent-cart',
		);

		$meta_fields['billing_postcode'] = array(
			'label' => __( 'Billing Postcode', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		$meta_fields['phone'] = array(
			'label' => __( 'Phone', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		// Shipping fields.
		$meta_fields['shipping_address_1'] = array(
			'label' => __( 'Shipping Address 1', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		$meta_fields['shipping_address_2'] = array(
			'label' => __( 'Shipping Address 2', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		$meta_fields['shipping_city'] = array(
			'label' => __( 'Shipping City', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		$meta_fields['shipping_state'] = array(
			'label' => __( 'Shipping State', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		$meta_fields['shipping_country'] = array(
			'label' => __( 'Shipping Country', 'wp-fusion' ),
			'type'  => 'country',
			'group' => 'fluent-cart',
		);

		$meta_fields['shipping_postcode'] = array(
			'label' => __( 'Shipping Postcode', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'fluent-cart',
		);

		// Pseudo fields.
		$meta_fields['customer_id'] = array(
			'label'  => __( 'FluentCart Customer ID', 'wp-fusion' ),
			'type'   => 'int',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['order_date'] = array(
			'label'  => __( 'Last Order Date', 'wp-fusion' ),
			'type'   => 'date',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		// Payment and order totals / status.
		$meta_fields['payment_method'] = array(
			'label'  => __( 'Payment Method', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['payment_method_title'] = array(
			'label'  => __( 'Payment Method Title', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['payment_status'] = array(
			'label'  => __( 'Payment Status', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['order_status'] = array(
			'label'  => __( 'Order Status', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['order_type'] = array(
			'label'  => __( 'Order Type', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['currency'] = array(
			'label'  => __( 'Currency', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['subtotal'] = array(
			'label'  => __( 'Subtotal', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['tax_total'] = array(
			'label'  => __( 'Tax Total', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['shipping_total'] = array(
			'label'  => __( 'Shipping Total', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['shipping_tax'] = array(
			'label'  => __( 'Shipping Tax', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['discount_tax'] = array(
			'label'  => __( 'Discount Tax', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['manual_discount_total'] = array(
			'label'  => __( 'Manual Discount Total', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['coupon_discount_total'] = array(
			'label'  => __( 'Coupon Discount Total', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['total_amount'] = array(
			'label'  => __( 'Order Total Amount', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['total_paid'] = array(
			'label'  => __( 'Total Paid', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['total_refund'] = array(
			'label'  => __( 'Total Refunded', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['receipt_number'] = array(
			'label'  => __( 'Receipt Number', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['invoice_no'] = array(
			'label'  => __( 'Invoice Number', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		// Shipping details.
		$meta_fields['fulfillment_type'] = array(
			'label'  => __( 'Fulfillment Type', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['shipping_status'] = array(
			'label'  => __( 'Shipping Status', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['shipping_required'] = array(
			'label'  => __( 'Shipping Required', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		$meta_fields['shipping_method'] = array(
			'label'  => __( 'Shipping Method', 'wp-fusion' ),
			'type'   => 'text',
			'group'  => 'fluent-cart',
			'pseudo' => true,
		);

		// Subscription fields (if available).
		if ( $this->has_subscription_support() ) {

			$meta_fields['subscription_status'] = array(
				'label'  => __( 'Subscription Status', 'wp-fusion' ),
				'type'   => 'text',
				'group'  => 'fluent-cart',
				'pseudo' => true,
			);

			$meta_fields['next_billing_date'] = array(
				'label'  => __( 'Next Billing Date', 'wp-fusion' ),
				'type'   => 'date',
				'group'  => 'fluent-cart',
				'pseudo' => true,
			);

			$meta_fields['recurring_amount'] = array(
				'label'  => __( 'Recurring Amount', 'wp-fusion' ),
				'type'   => 'text',
				'group'  => 'fluent-cart',
				'pseudo' => true,
			);
		}

		return $meta_fields;
	}
}

new WPF_FluentCart();
