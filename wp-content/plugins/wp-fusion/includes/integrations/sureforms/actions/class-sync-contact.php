<?php
/**
 * Sync Contact action for SureForms.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.47.3
 */

namespace WP_Fusion\Integrations\SureForms\Actions;

use WP_Fusion\Integrations\SureForms\Traits\SureForms_Helper;
use SRFM_Pro\Inc\Pro\Native_Integrations\WordPress_Action;
use SRFM_Pro\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles syncing contacts via WP Fusion.
 *
 * @since 3.47.3
 */
class Sync_Contact extends WordPress_Action {
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
	protected $action = 'sync_contact';

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

			unset( $data['_test_mode'] );
		}

		$validation = $this->validate_required_fields( $data, array( 'email_address' ) );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$email = isset( $data['email_address'] ) ? sanitize_email( $data['email_address'] ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'A valid email address is required.', 'wp-fusion' ) );
		}

		$update_data = $this->prepare_update_data( $data );
		$form_id     = isset( $data['form_id'] ) ? absint( $data['form_id'] ) : 0;

		$args = array(
			'email_address'    => $email,
			'update_data'      => $update_data,
			'auto_login'       => ! empty( $data['auto_login'] ),
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

		$message = __( 'Contact synced successfully.', 'wp-fusion' );

		if ( $is_test_mode ) {
			$message = __( 'Test submission synced successfully.', 'wp-fusion' );
		}

		return array(
			'success'    => true,
			'message'    => $message,
			'contact_id' => $contact_id,
			'email'      => $email,
		);
	}

	/**
	 * Prepare CRM update payload.
	 *
	 * @since 3.47.3
	 *
	 * @param array $data Form submission data.
	 * @return array
	 */
	private function prepare_update_data( $data ) {
		$update_data = array();
		$field_types = $this->get_crm_field_types();

		foreach ( $field_types as $field_key => $field_type ) {
			if ( ! array_key_exists( $field_key, $data ) ) {
				continue;
			}

			$value = $data[ $field_key ];

			if ( $this->is_empty( $value ) ) {
				continue;
			}

			$sanitized_value = $this->sanitize_value( $value );
			$formatted_value = apply_filters( 'wpf_format_field_value', $sanitized_value, $field_type, $field_key, $update_data );

			if ( null === $formatted_value ) {
				continue;
			}

			$update_data[ $field_key ] = $formatted_value;
		}

		return $update_data;
	}

	/**
	 * Get CRM field type map.
	 *
	 * @since 3.47.3
	 *
	 * @return array<string, string>
	 */
	private function get_crm_field_types() {
		static $field_types = null;

		if ( null !== $field_types ) {
			return $field_types;
		}

		$field_types = array();
		$crm_fields  = wp_fusion()->settings->get( 'crm_fields', array() );

		if ( empty( $crm_fields ) ) {
			return $field_types;
		}

		$field_groups = is_array( reset( $crm_fields ) ) ? $crm_fields : array( 'general' => $crm_fields );

		foreach ( $field_groups as $group_fields ) {
			if ( ! is_array( $group_fields ) ) {
				continue;
			}

			foreach ( $group_fields as $field_key => $field_config ) {
				$field_types[ $field_key ] = $this->map_field_type( $field_config );
			}
		}

		return $field_types;
	}

	/**
	 * Determine if a value is effectively empty.
	 *
	 * @since 3.47.3
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	private function is_empty( $value ) {
		if ( is_array( $value ) ) {
			return empty( array_filter( $value, array( $this, 'is_not_empty_scalar' ) ) );
		}

		return '' === $value || null === $value;
	}

	/**
	 * Helper for filtering arrays.
	 *
	 * @since 3.47.3
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	private function is_not_empty_scalar( $value ) {
		return '' !== $value && null !== $value;
	}

	/**
	 * Sanitize a value from the form submission.
	 *
	 * @since 3.47.3
	 *
	 * @param mixed $value Value to sanitize.
	 * @return mixed
	 */
	private function sanitize_value( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'sanitize_value' ), $value );
		}

		if ( is_string( $value ) ) {
			return wp_unslash( $value );
		}

		return $value;
	}
}

Sync_Contact::get_instance();
