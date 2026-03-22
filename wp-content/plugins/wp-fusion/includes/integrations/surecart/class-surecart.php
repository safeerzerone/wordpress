<?php
/**
 * WP Fusion - SureCart Integration
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.40.48
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureCart integration class.
 *
 * @since 3.40.48
 */

class WPF_SureCart extends WPF_Integrations_Base {

	/**
	 * This identifies the integration internally and makes it available at
	 * wp_fusion()->integrations->{'my-plugin-slug'}
	 *
	 * @var  string
	 * @since 3.40.48
	 */
	public $slug = 'surecart';

	/**
	 * The human-readable name of the integration.
	 *
	 * @var  string
	 * @since 3.40.48
	 */
	public $name = 'SureCart';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.40.48
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/ecommerce/surecart/';

	/**
	 * Apply tags integration.
	 *
	 * @var \WPFusion\Integrations\Apply_Tags
	 * @since 3.40.48
	 */
	public $apply_tags;

	/**
	 * Remove tags integration.
	 *
	 * @var \WPFusion\Integrations\Remove_Tags
	 * @since 3.40.48
	 */
	public $remove_tags;

	/**
	 * Get things started.
	 *
	 * @since 3.40.48
	 */
	public function init() {

		$this->includes();

		$this->apply_tags  = ( new \WPFusion\Integrations\Apply_Tags() )->bootstrap();
		$this->remove_tags = ( new \WPFusion\Integrations\Remove_Tags() )->bootstrap();

		// Custom Fields.
		add_action( 'surecart/checkout_confirmed', array( $this, 'sync_custom_fields' ) );

		// Batch processing.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_surecart_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_surecart', array( $this, 'batch_step' ) );
	}

	/**
	 * Includes.
	 *
	 * @since 3.41.46
	 */
	public function includes() {

		require_once __DIR__ . '/class-apply-tags.php';
		require_once __DIR__ . '/class-remove-tags.php';
	}

	/**
	 * Loads SureCart custom fields by parsing block content from forms.
	 *
	 * @since 3.46.0
	 *
	 * @return array Custom fields.
	 */
	public function get_surecart_custom_fields() {
		$custom_fields = array();

		// Get all posts that might contain SureCart forms.
		$args = array(
			'post_type'      => 'sc_form',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		);

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			// Check if this post contains a SureCart form.
			if ( function_exists( 'has_block' ) && has_block( 'surecart/form', $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );
				$this->extract_custom_fields_from_blocks( $blocks, $custom_fields );
			}
		}

		return $custom_fields;
	}

	/**
	 * Extract custom fields from blocks recursively.
	 *
	 * @since 3.46.0
	 *
	 * @param array $blocks        The blocks to parse.
	 * @param array &$custom_fields The custom fields array to populate.
	 * @return array The custom fields array.
	 */
	private function extract_custom_fields_from_blocks( $blocks, &$custom_fields ) {
		foreach ( $blocks as $block ) {
			// Check for input blocks with custom field names.
			if ( 'surecart/input' === $block['blockName'] && isset( $block['attrs']['name'] ) ) {
				// Skip standard fields.
				if ( ! in_array( $block['attrs']['name'], array( 'email', 'first_name', 'last_name' ), true ) ) {
					$field_name  = $block['attrs']['name'];
					$field_label = isset( $block['attrs']['label'] ) ? $block['attrs']['label'] : ucwords( str_replace( '_', ' ', $field_name ) );

					// Add to custom fields with empty value as placeholder.
					$custom_fields[ $field_name ] = $field_label;
				}
			}

			// Check for checkbox fields.
			if ( 'surecart/checkbox' === $block['blockName'] ) {
				// For checkboxes, the name and content are in the innerHTML.
				if ( isset( $block['innerHTML'] ) ) {
					// Extract name from the innerHTML using regex.
					if ( preg_match( '/name="([^"]*)"/', $block['innerHTML'], $name_matches ) ) {
						$field_name = $name_matches[1];

						// Extract the label (content between opening and closing tags).
						$field_label = '';
						if ( preg_match( '/>([^<]*)<\/sc-checkbox>/', $block['innerHTML'], $label_matches ) ) {
							$field_label = trim( $label_matches[1] );
						}

						// Fallback to name if no label found.
						if ( empty( $field_label ) ) {
							$field_label = ucwords( str_replace( '_', ' ', $field_name ) );
						}

						$custom_fields[ $field_name ] = $field_label;
					}
				}
			}

			// Check for other custom field blocks that might use HTML structure.
			if ( in_array( $block['blockName'], array( 'surecart/textarea', 'surecart/radio', 'surecart/select' ), true ) ) {
				// First try to get from attrs if available.
				if ( isset( $block['attrs']['name'] ) ) {
					$field_name                   = $block['attrs']['name'];
					$field_label                  = isset( $block['attrs']['label'] ) ? $block['attrs']['label'] : ucwords( str_replace( '_', ' ', $field_name ) );
					$custom_fields[ $field_name ] = $field_label;
				}

				// Otherwise try to extract from innerHTML like with checkboxes.
				if ( ! isset( $block['attrs']['name'] ) && isset( $block['innerHTML'] ) ) {
					$tag_name = str_replace( 'surecart/', 'sc-', $block['blockName'] );

					// Extract name from the innerHTML using regex.
					if ( preg_match( '/name="([^"]*)"/', $block['innerHTML'], $name_matches ) ) {
						$field_name = $name_matches[1];

						// Extract the label (content between opening and closing tags).
						$field_label = '';
						if ( preg_match( '/>([^<]*)<\/' . $tag_name . '>/', $block['innerHTML'], $label_matches ) ) {
							$field_label = trim( $label_matches[1] );
						}

						// Fallback to name if no label found.
						if ( empty( $field_label ) ) {
							$field_label = ucwords( str_replace( '_', ' ', $field_name ) );
						}

						$custom_fields[ $field_name ] = $field_label;
					}
				}
			}

			// Recursively check inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->extract_custom_fields_from_blocks( $block['innerBlocks'], $custom_fields );
			}
		}

		return $custom_fields;
	}

	/**
	 * Add Meta Field Group
	 * Adds the field group for SureCart checkout.
	 *
	 * @since 3.42.15
	 *
	 * @param array $field_groups Field groups.
	 * @return  array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['surecart'] = array(
			'title' => __( 'SureCart', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/ecommerce/surecart/',
		);

		return $field_groups;
	}

	/**
	 * Prepare Meta Fields
	 * Sets field labels and types for SureCart custom fields.
	 *
	 * @since 3.42.15
	 *
	 * @param array $meta_fields Meta fields.
	 * @return  array Meta fields
	 */
	public function add_meta_fields( $meta_fields ) {

		$meta_fields['line_1'] = array(
			'label' => 'Billing Address 1',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['line_2'] = array(
			'label' => 'Billing Address 2',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['city'] = array(
			'label' => 'City',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['state'] = array(
			'label' => 'State',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['country'] = array(
			'label' => 'Country',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['postal_code'] = array(
			'label' => 'Postcode',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['phone'] = array(
			'label' => 'Phone',
			'type'  => 'text',
			'group' => 'surecart',
		);

		// Custom Fields.
		$custom_fields = $this->get_surecart_custom_fields();

		// Map custom fields to $meta_fields.
		if ( ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $field_key => $field_value ) {

				// Skip the wp_created_by field.
				if ( 'wp_created_by' === $field_key ) {
					continue;
				}

				$meta_fields[ $field_key ] = array(
					'label' => is_string( $field_value ) && ! empty( $field_value ) ? $field_value : ucwords( str_replace( '_', ' ', $field_key ) ),
					'type'  => is_numeric( $field_key ) ? 'integer' : 'text',
					'group' => 'surecart',
				);
			}
		}

		return $meta_fields;
	}

	/**
	 * Sync Custom Fields
	 * Syncs custom fields to the CRM when a purchase is created.
	 *
	 * @since 3.42.15
	 *
	 * @param \SureCart\Models\Checkout $checkout The checkout data.
	 */
	public function sync_custom_fields( $checkout ) {

		$checkout_meta = $checkout->getAttributes();

		$user_meta = array(
			'first_name'  => $checkout_meta['customer']['first_name'],
			'last_name'   => $checkout_meta['customer']['last_name'],
			'line_1'      => isset( $checkout_meta['shipping_address']['line_1'] ) ? $checkout_meta['shipping_address']['line_1'] : '',
			'line_2'      => isset( $checkout_meta['shipping_address']['line_2'] ) ? $checkout_meta['shipping_address']['line_2'] : '',
			'city'        => isset( $checkout_meta['shipping_address']['city'] ) ? $checkout_meta['shipping_address']['city'] : '',
			'state'       => isset( $checkout_meta['shipping_address']['state'] ) ? $checkout_meta['shipping_address']['state'] : '',
			'country'     => isset( $checkout_meta['shipping_address']['country'] ) ? $checkout_meta['shipping_address']['country'] : '',
			'postal_code' => isset( $checkout_meta['shipping_address']['postal_code'] ) ? $checkout_meta['shipping_address']['postal_code'] : '',
			'phone'       => isset( $checkout_meta['customer']['phone'] ) ? $checkout_meta['customer']['phone'] : '',
		);

		// Custom Fields.
		foreach ( $checkout_meta['metadata'] as $key => $value ) {
			$user_meta[ $key ] = $value;
		}

		$user = get_user_by( 'email', $checkout_meta['email'] );

		wp_fusion()->user->push_user_meta( $user->ID, $user_meta );
	}

	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds SureCart orders checkbox to available export options
	 *
	 * @since 3.44.27
	 *
	 * @param array $options The export options.
	 * @return array Options
	 */
	public function export_options( $options ) {

		$options['surecart'] = array(
			'label'   => 'SureCart orders',
			'title'   => 'Orders',
			'tooltip' => sprintf(
				// translators: The CRM name.
				__( 'For each SureCart order, applies any tags in %1$s configured on the order products.', 'wp-fusion' ),
				wp_fusion()->crm->name
			),
		);

		return $options;
	}

	/**
	 * Gets total list of orders to be processed
	 *
	 * @since 3.44.27
	 *
	 * @return array Product IDs
	 */
	public function batch_init() {
		$product_ids = array();
		$per_page    = 100;

		// Get first page to get total count.
		$purchases = \SureCart\Models\Purchase::where( array( 'revoked' => false ) )
			->paginate(
				array(
					'page'     => 1,
					'per_page' => $per_page,
				)
			);

		$total_count = $purchases->pagination->count;

		// Process first page results.
		foreach ( $purchases->data as $purchase ) {
			$wp_user = $purchase->getWPUser();
			if ( $wp_user ) {
				$product_ids[] = $wp_user->ID . '__' . $purchase->product_id;
			}
		}

		// Only continue if we haven't received all items in first request.
		if ( count( $purchases->data ) < $total_count ) {
			$total_pages = ceil( $total_count / $per_page );
			// Get remaining pages.
			for ( $page = 2; $page <= $total_pages; $page++ ) {
				$purchases = \SureCart\Models\Purchase::where( array( 'revoked' => false ) )
					->paginate(
						array(
							'page'     => $page,
							'per_page' => $per_page,
						)
					);

				foreach ( $purchases->data as $purchase ) {
					$wp_user = $purchase->getWPUser();
					if ( $wp_user ) {
						$product_ids[] = $wp_user->ID . '__' . $purchase->product_id;
					}
				}
			}
		}

		return $product_ids;
	}

	/**
	 * Processes purchase actions in batches.
	 *
	 * @since 3.44.27
	 *
	 * @param string $product_id The product ID.
	 */
	public function batch_step( $product_id ) {
		$product_id = explode( '__', $product_id );
		$user_id    = $product_id[0];
		$product_id = $product_id[1];

		$integrations = \SureCart\Models\Integration::where(
			array(
				'model_id' => $product_id,
				'provider' => 'wp-fusion/apply-tags',
			)
		)->get();

		foreach ( $integrations as $integration ) {

			$atts = $integration->getAttributes();

			$apply_tags = $integration->integration_id;
			if ( ! is_array( $apply_tags ) ) {
				$apply_tags = array( $apply_tags );
			}

			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
		}
	}
}

new WPF_SureCart();
