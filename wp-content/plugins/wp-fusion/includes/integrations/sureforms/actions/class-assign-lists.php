<?php
/**
 * Assign Lists action for SureForms.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.47.3
 */

namespace WP_Fusion\Integrations\SureForms\Actions;

use WP_Fusion\Integrations\SureForms\Traits\SureForms_Helper;
use SRFM_Pro\Inc\Traits\Get_Instance;
use SRFM_Pro\Inc\Pro\Native_Integrations\WordPress_Action;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Adds contacts to CRM lists through WP Fusion.
 *
 * @since 3.47.3
 */
class Assign_Lists extends WordPress_Action {
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
	protected $action = 'assign_lists';

	/**
	 * Execute the action.
	 *
	 * @since 3.47.3
	 *
	 * @param array $data Form submission data.
	 * @return array|\WP_Error
	 */
	protected function execute( $data ) {
		if ( ! $this->crm_supports_lists() ) {
			return new \WP_Error( 'lists_not_supported', __( 'The connected CRM does not support lists.', 'wp-fusion' ) );
		}

		$is_test_mode = isset( $data['_test_mode'] ) && true === $data['_test_mode'];

		if ( $is_test_mode ) {
			$data['email_address'] = 'testing@example.com';
			$data['list_ids']      = array( '1' );

			unset( $data['_test_mode'] );
		}

		$validation = $this->validate_required_fields( $data, array( 'email_address' ) );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$email   = isset( $data['email_address'] ) ? sanitize_email( $data['email_address'] ) : '';
		$lists   = $this->collect_lists( $data );
		$form_id = isset( $data['form_id'] ) ? absint( $data['form_id'] ) : 0;

		if ( empty( $lists ) ) {
			return new \WP_Error( 'missing_lists', __( 'Select at least one list to assign.', 'wp-fusion' ) );
		}

		$args = array(
			'email_address'    => $email,
			'update_data'      => array(
				'lists' => $lists,
			),
			'apply_lists'      => $lists,
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

		$message = __( 'Lists assigned successfully.', 'wp-fusion' );

		if ( $is_test_mode ) {
			$message = __( 'Test submission processed successfully.', 'wp-fusion' );
		}

		return array(
			'success'    => true,
			'message'    => $message,
			'contact_id' => $contact_id,
			'lists'      => $lists,
		);
	}

	/**
	 * Build a normalized list array from action data.
	 *
	 * @since 3.47.3
	 *
	 * @param array $data Form submission data.
	 * @return array
	 */
	private function collect_lists( $data ) {
		$raw_lists = array();

		if ( isset( $data['list_ids'] ) ) {
			$raw_lists = array_merge( $raw_lists, $this->normalize_multi_value( $data['list_ids'] ) );
		}

		$raw_lists = array_filter( $raw_lists, array( $this, 'is_not_empty_string' ) );

		if ( empty( $raw_lists ) ) {
			return array();
		}

		return $this->convert_lists_to_ids( $raw_lists );
	}

	/**
	 * Convert list labels to IDs when possible.
	 *
	 * @since 3.47.3
	 *
	 * @param array $lists List inputs.
	 * @return array
	 */
	private function convert_lists_to_ids( $lists ) {
		$available_lists = wpf_get_option( 'available_lists', array() );
		$label_map       = array();

		foreach ( $available_lists as $list_id => $label ) {
			$label_map[ strtolower( $label ) ] = (string) $list_id;
		}

		$normalized = array();

		foreach ( $lists as $list ) {
			if ( is_numeric( $list ) ) {
				$normalized[] = (string) absint( $list );
				continue;
			}

			$lookup = strtolower( (string) $list );

			if ( isset( $label_map[ $lookup ] ) ) {
				$normalized[] = $label_map[ $lookup ];
			} else {
				$normalized[] = (string) $list;
			}
		}

		return array_values( array_unique( $normalized ) );
	}
}

Assign_Lists::get_instance();
