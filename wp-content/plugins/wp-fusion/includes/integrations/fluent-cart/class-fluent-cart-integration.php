<?php
/**
 * WP Fusion - FluentCart Integration Manager
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
 * Registers WP Fusion as a FluentCart integration.
 *
 * @since 3.47.0
 */
class WPF_FluentCart_Integration {

	/**
	 * Constructor.
	 *
	 * @since 3.47.0
	 */
	public function __construct() {

		// Register with FluentCart's integration system.
		add_filter( 'fluent_cart/integration/order_integrations', array( $this, 'register_integration' ), 10, 1 );

		// Only load integration handlers if WP Fusion is configured.
		if ( wp_fusion()->settings->get( 'connection_configured' ) ) {
			add_filter( 'fluent_cart/integration/get_integration_defaults_wpfusion', array( $this, 'get_integration_defaults' ), 10, 2 );
			add_filter( 'fluent_cart/integration/get_integration_settings_fields_wpfusion', array( $this, 'get_settings_fields' ), 10, 2 );
			add_action( 'fluent_cart/integration/run/wpfusion', array( $this, 'process_action' ), 10, 1 );
		}
	}

	/**
	 * Register WP Fusion with FluentCart integrations.
	 *
	 * @since 3.47.0
	 *
	 * @param array $integrations The existing integrations.
	 * @return array Modified integrations.
	 */
	public function register_integration( $integrations ) {

		$integrations['wpfusion'] = array(
			'priority'                => 15,
			'title'                   => 'WP Fusion',
			'description'             => __( 'Automatically sync customers and apply tags in your CRM when orders are placed in FluentCart.', 'wp-fusion' ),
			'category'                => 'crm',
			'disable_global_settings' => true, // We handle settings through WP Fusion's own settings page.
			'config_url'              => admin_url( 'options-general.php?page=wpf-settings&tab=integrations' ),
			'logo'                    => WPF_DIR_URL . 'assets/img/logo-sm-trans.png',
			'enabled'                 => wp_fusion()->settings->get( 'connection_configured' ),
			'scopes'                  => array( 'product' ), // Only product-level integrations.
			'installable'             => '',
			'delay_on_product_action' => false, // Run immediately.
			'delay_on_global_action'  => false,
		);

		return $integrations;
	}

	/**
	 * Get integration default values.
	 *
	 * @since 3.47.0
	 *
	 * @param array $settings The existing settings (not used).
	 * @return array Default settings.
	 */
	public function get_integration_defaults( $settings ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		return array(
			'enabled'                => 'yes',
			'name'                   => '',
			'apply_tags'             => array(),
			'remove_tags'            => array(),
			'sync_custom_fields'     => 'yes', // Enabled by default.
			'remove_tags_on_refund'  => 'yes', // Remove applied tags when refunded.
			'watch_on_access_revoke' => 'yes', // Always enable refund/cancellation handling.
			'event_trigger'          => array( 'order_paid_done' ), // Default to order paid.
		);
	}

	/**
	 * Get settings fields for the integration.
	 *
	 * @since 3.47.0
	 *
	 * @param array $settings The current settings (not used).
	 * @param array $args     Additional arguments (not used).
	 * @return array Settings fields.
	 */
	public function get_settings_fields( $settings, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Get available tags from WP Fusion.
		$available_tags = wp_fusion()->settings->get_available_tags_flat();

		$fields = array(
			array(
				'key'         => 'name',
				'label'       => __( 'Feed Title', 'wp-fusion' ),
				'required'    => true,
				'placeholder' => __( 'Name', 'wp-fusion' ),
				'component'   => 'text',
				'inline_tip'  => __( 'Name of this feed for your reference. Example: "Apply Customer Tag on Purchase" or "Cancel Membership on Subscription End"', 'wp-fusion' ),
			),
			array(
				'key'         => 'apply_tags',
				'label'       => __( 'Apply Tags', 'wp-fusion' ),
				'placeholder' => __( 'Select Tags', 'wp-fusion' ),
				'inline_tip'  => __( 'These tags will be applied in your CRM when the selected event(s) occur.', 'wp-fusion' ),
				'component'   => 'select',
				'is_multiple' => true,
				'required'    => false,
				'options'     => $available_tags,
			),
			array(
				'key'         => 'remove_tags',
				'label'       => __( 'Remove Tags', 'wp-fusion' ),
				'placeholder' => __( 'Select Tags', 'wp-fusion' ),
				'inline_tip'  => __( 'These tags will be removed from the contact in your CRM when the selected event(s) occur.', 'wp-fusion' ),
				'component'   => 'select',
				'is_multiple' => true,
				'required'    => false,
				'options'     => $available_tags,
			),
			array(
				'key'            => 'sync_custom_fields',
				'component'      => 'yes-no-checkbox',
				'checkbox_label' => __( 'Sync custom fields', 'wp-fusion' ),
				'inline_tip'     => __( 'When enabled, customer and order data (billing address, order date, etc.) will be synced to your CRM.', 'wp-fusion' ),
			),
			array(
				'key'            => 'remove_tags_on_refund',
				'component'      => 'yes-no-checkbox',
				'checkbox_label' => __( 'Remove applied tags on refund', 'wp-fusion' ),
				'inline_tip'     => __( 'When enabled and this order is refunded, any tags applied by this feed will be automatically removed.', 'wp-fusion' ),
			),
		);

		// Add the event trigger field (standard FluentCart field).
		$fields[] = $this->action_fields();

		// Add hidden field to ensure feed is triggered on revoke events (refunds/cancellations).
		// This must be included as a field so it gets saved with the feed config.
		$fields[] = array(
			'key'       => 'watch_on_access_revoke',
			'component' => 'hidden',
			'value'     => 'yes',
		);

		return array(
			'fields'              => $fields,
			'button_require_list' => false,
			'integration_title'   => __( 'WP Fusion', 'wp-fusion' ),
		);
	}

	/**
	 * Get action/event trigger fields.
	 *
	 * @since 3.47.0
	 *
	 * @return array Action fields.
	 */
	private function action_fields() {

			if ( ! class_exists( '\\FluentCart\App\Helpers\Status' ) ) {
				return array();
			}

		return \FluentCart\App\Helpers\Status::eventTriggers();
	}

	/**
	 * Process the integration action.
	 *
	 * @since 3.47.0
	 *
	 * @param array $event_data The event data containing order and feed config.
	 * @return void
	 */
	public function process_action( $event_data ) {

		if ( ! isset( $event_data['order'] ) || ! isset( $event_data['feed'] ) ) {
			return;
		}

		$order       = $event_data['order'];
		$feed_config = $event_data['feed'];
		$is_revoke   = isset( $event_data['is_revoke_hook'] ) && 'yes' === $event_data['is_revoke_hook'];
		$event       = isset( $event_data['trigger'] ) ? $event_data['trigger'] : 'unknown';

        // Get user ID or handle guest checkout.
        // Note: user_id is on the customer object, not the order.
        $user_id = ( isset( $order->customer ) && ! empty( $order->customer->user_id ) ) ? $order->customer->user_id : 0;

        // Build FluentCart admin order link for WPF logs.
        $order_admin_url = admin_url( 'admin.php?page=fluent-cart#/orders/' . $order->id . '/view' );
        $order_admin_link = '<a href="' . esc_url( $order_admin_url ) . '" target="_blank">#' . esc_html( $order->id ) . '</a>';

        wpf_log(
            'info',
            $user_id,
            'FluentCart integration feed triggered. Event: ' . $event . ', Is Revoke: ' . ( $is_revoke ? 'yes' : 'no' ) . ', Order: ' . $order_admin_link,
            array(
                'feed_name'    => isset( $feed_config['name'] ) ? $feed_config['name'] : 'N/A',
                'has_order'    => ! empty( $order ),
                'has_customer' => ! empty( $order->customer ),
            )
        );

		// Get contact ID and track if it's new or existing.
		$contact_id         = false;
		$contact_is_new     = false;
		$contact_created_id = false;

		if ( $user_id ) {
			$contact_id = wp_fusion()->user->get_contact_id( $user_id );
		} elseif ( ! empty( $order->customer->email ) ) {
			$contact_id = wp_fusion()->crm->get_contact_id( $order->customer->email );
		}

	// If this is a revoke hook (refund/cancellation), handle removal.
	if ( $is_revoke && $contact_id ) {
		$this->handle_revoke( $feed_config, $user_id, $contact_id, $event );
		return;
	}

		// Create or update contact if needed.
		if ( ! $contact_id ) {
			$contact_created_id = $this->create_contact( $order );
			if ( is_wp_error( $contact_created_id ) ) {
				wpf_log( 'error', $user_id, 'Error creating contact for FluentCart order #' . $order->id . ': ' . $contact_created_id->get_error_message() );
				return;
			}
			$contact_id     = $contact_created_id;
			$contact_is_new = true;
		}


		// Prepare custom fields from the order for syncing and logging.
		$custom_fields = array();

		// Pseudo fields.
		if ( ! empty( $order->customer ) && ! empty( $order->customer->id ) ) {
			$custom_fields['customer_id'] = $order->customer->id;
		}

		if ( ! empty( $event_data['order']->created_at ) ) {
			$custom_fields['order_date'] = gmdate( 'Y-m-d', strtotime( $event_data['order']->created_at ) );
		}

		// Billing address.
		if ( ! empty( $order->billing_address ) ) {
			$billing                           = $order->billing_address;
			$custom_fields['billing_address_1'] = isset( $billing->address_1 ) ? $billing->address_1 : '';
			$custom_fields['billing_address_2'] = isset( $billing->address_2 ) ? $billing->address_2 : '';
			$custom_fields['billing_city']      = isset( $billing->city ) ? $billing->city : '';
			$custom_fields['billing_state']     = isset( $billing->state ) ? $billing->state : '';
			$custom_fields['billing_postcode']  = isset( $billing->postcode ) ? $billing->postcode : '';
			$custom_fields['billing_country']   = isset( $billing->country ) ? $billing->country : '';
		}

		// Payment and order totals / status.
		$custom_fields['payment_method']       = isset( $order->payment_method ) ? $order->payment_method : '';
		$custom_fields['payment_method_title'] = isset( $order->payment_method_title ) ? $order->payment_method_title : '';
		$custom_fields['payment_status']       = isset( $order->payment_status ) ? $order->payment_status : '';
		$custom_fields['order_status']         = isset( $order->status ) ? $order->status : '';
		$custom_fields['order_type']           = isset( $order->type ) ? $order->type : '';
		$custom_fields['currency']             = isset( $order->currency ) ? $order->currency : '';
		$custom_fields['subtotal']             = isset( $order->subtotal ) ? $order->subtotal : '';
		$custom_fields['tax_total']            = isset( $order->tax_total ) ? $order->tax_total : '';
		$custom_fields['shipping_total']       = isset( $order->shipping_total ) ? $order->shipping_total : '';
		$custom_fields['shipping_tax']         = isset( $order->shipping_tax ) ? $order->shipping_tax : '';
		$custom_fields['discount_tax']         = isset( $order->discount_tax ) ? $order->discount_tax : '';
		$custom_fields['manual_discount_total'] = isset( $order->manual_discount_total ) ? $order->manual_discount_total : '';
		$custom_fields['coupon_discount_total'] = isset( $order->coupon_discount_total ) ? $order->coupon_discount_total : '';
		$custom_fields['total_amount']         = isset( $order->total_amount ) ? $order->total_amount : '';
		$custom_fields['total_paid']           = isset( $order->total_paid ) ? $order->total_paid : '';
		$custom_fields['total_refund']         = isset( $order->total_refund ) ? $order->total_refund : '';
		$custom_fields['receipt_number']       = isset( $order->receipt_number ) ? $order->receipt_number : '';
		$custom_fields['invoice_no']           = isset( $order->invoice_no ) ? $order->invoice_no : '';
		$custom_fields['mode']                 = isset( $order->mode ) ? $order->mode : '';

		// Shipping details.
		$custom_fields['fulfillment_type'] = isset( $order->fulfillment_type ) ? $order->fulfillment_type : '';
		$custom_fields['shipping_status']  = isset( $order->shipping_status ) ? $order->shipping_status : '';
		$custom_fields['shipping_required'] = ( isset( $order->fulfillment_type ) && 'physical' === $order->fulfillment_type ) || ! empty( $order->shipping_address ) ? 'yes' : 'no';

		// Shipping method (if recorded in order meta).
		if ( method_exists( $order, 'getMeta' ) ) {
			$shipping_method = $order->getMeta( 'shipping_method', '' );
			if ( ! empty( $shipping_method ) ) {
				$custom_fields['shipping_method'] = is_array( $shipping_method ) ? wp_json_encode( $shipping_method ) : $shipping_method;
			}
		}

		// Shipping address.
		if ( ! empty( $order->shipping_address ) ) {
			$shipping                           = $order->shipping_address;
			$custom_fields['shipping_address_1'] = isset( $shipping->address_1 ) ? $shipping->address_1 : '';
			$custom_fields['shipping_address_2'] = isset( $shipping->address_2 ) ? $shipping->address_2 : '';
			$custom_fields['shipping_city']      = isset( $shipping->city ) ? $shipping->city : '';
			$custom_fields['shipping_state']     = isset( $shipping->state ) ? $shipping->state : '';
			$custom_fields['shipping_postcode']  = isset( $shipping->postcode ) ? $shipping->postcode : '';
			$custom_fields['shipping_country']   = isset( $shipping->country ) ? $shipping->country : '';
		}

	// Sync custom fields if enabled (registered users or guests with contact ID).
	if ( isset( $feed_config['sync_custom_fields'] ) && 'yes' === $feed_config['sync_custom_fields'] ) {
		// Filter out null values but keep 0 and '0'.
		$custom_fields_to_push = $this->filter_empty_fields( $custom_fields );

		if ( ! empty( $custom_fields_to_push ) ) {
			if ( $user_id ) {
				wp_fusion()->user->push_user_meta( $user_id, $custom_fields_to_push );
			} elseif ( $contact_id ) {
				wp_fusion()->crm->update_contact( $contact_id, $custom_fields_to_push );
			}
		}
	}

		// Get tags to apply and remove.
		$apply_tags  = isset( $feed_config['apply_tags'] ) ? (array) $feed_config['apply_tags'] : array();
		$remove_tags = isset( $feed_config['remove_tags'] ) ? (array) $feed_config['remove_tags'] : array();

		// Add global customer tags (only for order_paid_done events).
		if ( 'order_paid_done' === $event ) {
			$global_customer_tags = wpf_get_option( 'fluent_cart_apply_tags_customers', array() );
			if ( ! empty( $global_customer_tags ) ) {
				$apply_tags = array_merge( $apply_tags, $global_customer_tags );
			}
		}

		// Remove tags first.
		if ( ! empty( $remove_tags ) && $contact_id ) {

			if ( $user_id ) {
				wp_fusion()->user->remove_tags( $remove_tags, $user_id );
			} else {
				wp_fusion()->crm->remove_tags( $remove_tags, $contact_id );
			}
		}

		// Apply tags.
		if ( ! empty( $apply_tags ) && $contact_id ) {

            wpf_log(
                'info',
                $user_id,
                'Applying tags for FluentCart event "' . $event . '". Order: ' . $order_admin_link,
                array( 'tag_array' => $apply_tags )
            );

			if ( $user_id ) {
				wp_fusion()->user->apply_tags( $apply_tags, $user_id );
			} else {
				wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );
			}
		}

			// Add comprehensive activity log to FluentCart order (single message).
			if ( method_exists( $order, 'addLog' ) && $contact_id ) {

			// Build contact status message.
			$contact_status = $contact_is_new ? __( 'Contact created', 'wp-fusion' ) : __( 'Contact updated', 'wp-fusion' );

			// Get CRM link if available.
			$contact_url = wp_fusion()->crm->get_contact_edit_url( $contact_id );
			if ( $contact_url ) {
				/* translators: 1: Contact status (created/updated), 2: URL to contact in CRM, 3: Contact ID */
				$format = __( '%1$s: <a href="%2$s" target="_blank">#%3$s</a>', 'wp-fusion' );
				$contact_message = sprintf(
					$format,
					$contact_status,
					esc_url( $contact_url ),
					esc_html( $contact_id )
				);
			} else {
				/* translators: 1: Contact status (created/updated), 2: Contact ID */
				$format = __( '%1$s: #%2$s', 'wp-fusion' );
				$contact_message = sprintf(
					$format,
					$contact_status,
					esc_html( $contact_id )
				);
			}

			// Add tags info.
			$details = array();
			if ( ! empty( $apply_tags ) ) {
				$tag_labels = implode( ', ', array_map( 'wpf_get_tag_label', $apply_tags ) );
				/* translators: %s: comma-separated tag labels */
				$details[] = sprintf( __( 'Tags applied: %s', 'wp-fusion' ), $tag_labels );
			}

			if ( ! empty( $remove_tags ) ) {
				$tag_labels = implode( ', ', array_map( 'wpf_get_tag_label', $remove_tags ) );
				/* translators: %s: comma-separated tag labels */
				$details[] = sprintf( __( 'Tags removed: %s', 'wp-fusion' ), $tag_labels );
			}

				if ( isset( $feed_config['sync_custom_fields'] ) && 'yes' === $feed_config['sync_custom_fields'] ) {
					$details[] = __( 'Custom fields synced', 'wp-fusion' );
				}

				// Combine message.
				$log_message = $contact_message;
				if ( ! empty( $details ) ) {
					$log_message .= '<br>' . implode( '<br>', $details );
				}

			// Add code block with the actual synced fields / values.
			if ( isset( $feed_config['sync_custom_fields'] ) && 'yes' === $feed_config['sync_custom_fields'] ) {
				$display_fields = $this->filter_empty_fields( $custom_fields );

				if ( ! empty( $display_fields ) ) {
					$json = wp_json_encode( $display_fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
					$log_message .= '<br><pre><code>' . esc_html( $json ) . '</code></pre>';
				}
			}

			$order->addLog(
				__( 'WP Fusion', 'wp-fusion' ),
				$log_message,
				'info',
				'WP Fusion'
			);
		}

		// Mark order as processed and store contact ID.
		if ( in_array( $event, array( 'order_paid_done', 'order_fully_refunded', 'order_status_changed_to_canceled' ), true ) ) {
			$order->updateMeta( 'wpf_complete', current_time( 'mysql' ) );
		}

		if ( $contact_id ) {
			$order->updateMeta( 'wpf_contact_id', $contact_id );
		}

			// Single detailed message added above; remove duplicate "Customer Synced" message.
	}

	/**
	 * Create a contact from FluentCart order.
	 *
	 * @since 3.47.0
	 *
	 * @param object $order The FluentCart order object.
	 * @return int|WP_Error Contact ID or error.
	 */
	private function create_contact( $order ) {

		$customer = $order->customer;

		$contact_data = array(
			'user_email' => $customer->email,
			'first_name' => $customer->first_name,
			'last_name'  => $customer->last_name,
		);

		// Pseudo fields.
		if ( ! empty( $customer->id ) ) {
			$contact_data['customer_id'] = $customer->id;
		}

		if ( ! empty( $order->created_at ) ) {
			$contact_data['order_date'] = gmdate( 'Y-m-d', strtotime( $order->created_at ) );
		}

		// Add billing address if available.
		if ( ! empty( $order->billing_address ) ) {
			$billing                           = $order->billing_address;
			$contact_data['billing_address_1'] = isset( $billing->address_1 ) ? $billing->address_1 : '';
			$contact_data['billing_address_2'] = isset( $billing->address_2 ) ? $billing->address_2 : '';
			$contact_data['billing_city']      = isset( $billing->city ) ? $billing->city : '';
			$contact_data['billing_state']     = isset( $billing->state ) ? $billing->state : '';
			$contact_data['billing_postcode']  = isset( $billing->postcode ) ? $billing->postcode : '';
			$contact_data['billing_country']   = isset( $billing->country ) ? $billing->country : '';
		}

		// Add shipping address if available.
		if ( ! empty( $order->shipping_address ) ) {
			$shipping                           = $order->shipping_address;
			$contact_data['shipping_address_1'] = isset( $shipping->address_1 ) ? $shipping->address_1 : '';
			$contact_data['shipping_address_2'] = isset( $shipping->address_2 ) ? $shipping->address_2 : '';
			$contact_data['shipping_city']      = isset( $shipping->city ) ? $shipping->city : '';
			$contact_data['shipping_state']     = isset( $shipping->state ) ? $shipping->state : '';
			$contact_data['shipping_postcode']  = isset( $shipping->postcode ) ? $shipping->postcode : '';
			$contact_data['shipping_country']   = isset( $shipping->country ) ? $shipping->country : '';
		}

		return wp_fusion()->crm->add_contact( $contact_data );
	}

	/**
	 * Handle revoke (refund/cancellation) events.
	 *
	 * @since 3.47.0
	 *
	 * @param array  $feed_config The feed configuration.
	 * @param int    $user_id     The user ID (0 for guest).
	 * @param string $contact_id  The contact ID.
	 * @param string $event       The event name.
	 * @return void
	 */
	private function handle_revoke( $feed_config, $user_id, $contact_id, $event ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// Remove applied tags if "Remove applied tags on refund" is enabled.
		if ( isset( $feed_config['remove_tags_on_refund'] ) && 'yes' === $feed_config['remove_tags_on_refund'] ) {
			$tags_to_remove = isset( $feed_config['apply_tags'] ) ? (array) $feed_config['apply_tags'] : array();
			if ( ! empty( $tags_to_remove ) ) {
				wpf_log(
					'info',
					$user_id,
					'Removing applied tags due to FluentCart refund.',
					array( 'tag_array' => $tags_to_remove )
				);

				if ( $user_id ) {
					wp_fusion()->user->remove_tags( $tags_to_remove, $user_id );
				} else {
					wp_fusion()->crm->remove_tags( $tags_to_remove, $contact_id );
				}
			}
		}


			// Apply any "Remove Tags" on revoke.
			$remove_tags = isset( $feed_config['remove_tags'] ) ? (array) $feed_config['remove_tags'] : array();
			if ( ! empty( $remove_tags ) ) {

				if ( $user_id ) {
					wp_fusion()->user->apply_tags( $remove_tags, $user_id );
				} else {
					wp_fusion()->crm->apply_tags( $remove_tags, $contact_id );
				}
			}
	}

	/**
	 * Filter out empty fields from an array.
	 *
	 * Removes entries where the value is null or an empty string.
	 * Preserves entries with falsy values like 0, '0', and false.
	 *
	 * @since 3.47.0
	 *
	 * @param array $fields The fields to filter.
	 * @return array Filtered fields with non-empty values.
	 */
	private function filter_empty_fields( $fields ) {

		$filtered = array();

		foreach ( $fields as $key => $value ) {
			if ( null !== $value && ! ( is_string( $value ) && '' === $value ) ) {
				$filtered[ $key ] = $value;
			}
		}

		return $filtered;
	}
}

new WPF_FluentCart_Integration();
