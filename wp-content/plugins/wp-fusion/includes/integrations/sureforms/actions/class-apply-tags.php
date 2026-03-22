<?php
/**
 * Apply Tags action for SureForms.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.47.3
 */

namespace WP_Fusion\Integrations\SureForms\Actions;

use SRFM_Pro\Inc\Pro\Native_Integrations\WordPress_Action;
use SRFM_Pro\Inc\Traits\Get_Instance;
use WP_Fusion\Integrations\SureForms\Traits\SureForms_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Applies WP Fusion tags to a contact.
 *
 * @since 3.47.3
 */
class Apply_Tags extends WordPress_Action {
	use Get_Instance;
	use SureForms_Helper;

	/**
	 * Integration slug.
	 *
	 * @since 3.47.3
	 * @var string
	 */
	protected $integration = 'wp_fusion';

	/**
	 * Action name.
	 *
	 * @since 3.47.3
	 * @var string
	 */
	protected $action = 'apply_tags';

	/**
	 * Execute the action.
	 *
	 * @since 3.47.3
	 *
	 * @param array $data Form submission data.
	 * @return array|\WP_Error
	 */
	protected function execute( $data ) {
		$is_test_mode = isset( $data['_test_mode'] ) && true === $data['_test_mode'];

		if ( $is_test_mode ) {
			$data['email_address'] = 'testing@example.com';
			$data['tag_ids']       = array( '1' );

			unset( $data['_test_mode'] );
		}

		$validation = $this->validate_required_fields( $data, array( 'email_address' ) );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$email   = isset( $data['email_address'] ) ? sanitize_email( $data['email_address'] ) : '';
		$tags    = $this->collect_tags( $data );
		$form_id = isset( $data['form_id'] ) ? absint( $data['form_id'] ) : 0;

		if ( empty( $tags ) ) {
			return new \WP_Error( 'missing_tags', __( 'Select at least one tag to apply.', 'wp-fusion' ) );
		}

		$args = array(
			'email_address'    => $email,
			'update_data'      => array(),
			'apply_tags'       => $tags,
			'integration_slug' => 'sureforms',
			'integration_name' => 'SureForms',
			'form_id'          => $form_id,
			'form_title'       => isset( $data['form_title'] ) ? sanitize_text_field( $data['form_title'] ) : '',
			'form_edit_link'   => $form_id ? admin_url( 'admin.php?page=sureforms_menu&route=form-settings/' . $form_id ) : '',
			'entry_id'         => isset( $data['entry_id'] ) ? absint( $data['entry_id'] ) : false,
		);

		$contact_id = \WPF_Forms_Helper::process_form_data( $args );

		if ( is_wp_error( $contact_id ) ) {
			return $contact_id;
		}

		$message = __( 'Tags applied successfully.', 'wp-fusion' );

		if ( $is_test_mode ) {
			$message = __( 'Test submission processed successfully.', 'wp-fusion' );
		}

		return array(
			'success'    => true,
			'message'    => $message,
			'contact_id' => $contact_id,
			'tags'       => $tags,
		);
	}

	/**
	 * Build a normalized list of tags from the action data.
	 *
	 * @since 3.47.3
	 *
	 * @param array $data Form submission data.
	 * @return array
	 */
	private function collect_tags( $data ) {
		$raw_tags = array();

		if ( isset( $data['tag_ids'] ) ) {
			$raw_tags = array_merge( $raw_tags, $this->normalize_multi_value( $data['tag_ids'] ) );
		}

		$raw_tags = array_filter( $raw_tags, array( $this, 'is_not_empty_string' ) );

		if ( empty( $raw_tags ) ) {
			return array();
		}

		return $this->convert_tags_to_ids( $raw_tags );
	}

	/**
	 * Convert tag labels to IDs when possible.
	 *
	 * @since 3.47.3
	 *
	 * @param array $tags Tag inputs.
	 * @return array
	 */
	private function convert_tags_to_ids( $tags ) {
		if ( ! function_exists( 'wp_fusion' ) ) {
			return array_values( array_unique( $tags ) );
		}

		$available_tags = wp_fusion()->settings->get_available_tags_flat();
		$label_map      = array();

		foreach ( $available_tags as $tag_id => $label ) {
			$label_map[ strtolower( $label ) ] = (string) $tag_id;
		}

		$normalized = array();

		foreach ( $tags as $tag ) {
			if ( is_numeric( $tag ) ) {
				$normalized[] = (string) absint( $tag );
				continue;
			}

			$lookup = strtolower( (string) $tag );

			if ( isset( $label_map[ $lookup ] ) ) {
				$normalized[] = $label_map[ $lookup ];
			} else {
				$normalized[] = (string) $tag;
			}
		}

		return array_values( array_unique( $normalized ) );
	}
}

Apply_Tags::get_instance();
