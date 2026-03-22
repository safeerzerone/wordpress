<?php
/**
 * SureForms Integration Helper.
 *
 * Registers dynamic field providers and loads action classes for the
 * SureForms native integration.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.47.3
 */

namespace WP_Fusion\Integrations\SureForms;

use WP_Fusion\Integrations\SureForms\Traits\SureForms_Helper;
use SRFM_Pro\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registration code for SureForms API.
 *
 * @since 3.47.3
 */
class SureForms_Integration {
	use Get_Instance;
	use SureForms_Helper;

	/**
	 * Constructor.
	 *
	 * @since 3.47.3
	 */
	public function __construct() {
		$this->register_filters();
	}

	/**
	 * Register filters and load action files.
	 *
	 * @since 3.47.3
	 * @return void
	 */
	private function register_filters() {
		add_filter( 'srfm_wp_fusion_get_crm_fields', array( $this, 'get_crm_fields' ) );
		add_filter( 'srfm_wp_fusion_get_tags', array( $this, 'get_tag_choices' ) );

		if ( $this->crm_supports_lists() ) {
			add_filter( 'srfm_wp_fusion_get_lists', array( $this, 'get_list_choices' ) );
		}

		$this->load_action_files();
	}

	/**
	 * Provide CRM field definitions to SureForms.
	 *
	 * @since 3.47.3
	 *
	 * @param array $fields Existing fields.
	 * @return array
	 */
	public function get_crm_fields( $fields = array() ) {
		$crm_fields = wp_fusion()->settings->get( 'crm_fields', array() );

		if ( empty( $crm_fields ) ) {
			return $fields;
		}

		$dynamic_fields = array();
		$field_groups   = is_array( reset( $crm_fields ) ) ? $crm_fields : array( 'general' => $crm_fields );

		foreach ( $field_groups as $group_fields ) {
			if ( ! is_array( $group_fields ) ) {
				continue;
			}

			foreach ( $group_fields as $field_key => $field_config ) {
				$label = $this->get_field_label( $field_key, $field_config );

				// Skip duplicate email field since we expose email separately.
				if ( 'email' === strtolower( $field_key ) || wp_fusion()->settings->get( 'lookup_field', 'email' ) === $field_key ) {
					continue;
				}

				$type = $this->map_field_type( $field_config );

				$field_definition = array(
					'key'         => $field_key,
					'label'       => $label,
					'type'        => $type,
					'required'    => false,
					'description' => sprintf(
						/* translators: %s: CRM field label. */
						__( 'Map a SureForms field to the %s CRM field.', 'wp-fusion' ),
						$label
					),
				);

				$options = $this->get_field_options( $field_config );

				if ( ! empty( $options ) ) {
					$field_definition['options'] = $options;
				}

				$dynamic_fields[] = $field_definition;
			}
		}

		// Add dynamic tagging option if supported by CRM.
		if ( in_array( 'add_tags', wp_fusion()->crm->supports, true ) ) {
			$dynamic_fields[] = array(
				'key'         => 'add_tag_value',
				'label'       => __( '+ Create tag(s) from value', 'wp-fusion' ),
				'type'        => 'text',
				'required'    => false,
				'description' => __( 'Create new tags in the CRM from the submitted field value.', 'wp-fusion' ),
			);
		}

		return array_merge( $fields, $dynamic_fields );
	}

	/**
	 * Provide tag options.
	 *
	 * @since 3.47.3
	 *
	 * @param array $options Existing tag options.
	 * @return array
	 */
	public function get_tag_choices( $options = array() ) {
		$tags = wp_fusion()->settings->get_available_tags_flat();

		if ( empty( $tags ) ) {
			return $options;
		}

		foreach ( $tags as $tag_id => $tag_label ) {
			$options[] = array(
				'value' => (string) $tag_id,
				'label' => $tag_label,
			);
		}

		return $options;
	}

	/**
	 * Provide list options.
	 *
	 * @since 3.47.3
	 *
	 * @param array $options Existing list options.
	 * @return array
	 */
	public function get_list_choices( $options = array() ) {
		if ( ! $this->crm_supports_lists() ) {
			return $options;
		}

		$lists = wpf_get_option( 'available_lists', array() );

		if ( empty( $lists ) ) {
			return $options;
		}

		foreach ( $lists as $list_id => $label ) {
			$options[] = array(
				'value' => (string) $list_id,
				'label' => $label,
			);
		}

		return $options;
	}

	/**
	 * Determine a human readable label for a CRM field.
	 *
	 * @since 3.47.3
	 *
	 * @param string       $field_key    CRM field key.
	 * @param string|array $field_config CRM field configuration.
	 * @return string
	 */
	private function get_field_label( $field_key, $field_config ) {
		if ( is_array( $field_config ) ) {
			if ( isset( $field_config['crm_label'] ) ) {
				return $field_config['crm_label'];
			}

			if ( isset( $field_config['label'] ) ) {
				return $field_config['label'];
			}

			if ( isset( $field_config['remote_label'] ) ) {
				return $field_config['remote_label'];
			}
		}

		if ( is_string( $field_config ) && '' !== $field_config ) {
			return $field_config;
		}

		return ucwords( str_replace( '_', ' ', $field_key ) );
	}

	/**
	 * Extract selectable options from the CRM configuration when available.
	 *
	 * @since 3.47.3
	 *
	 * @param array|string $field_config CRM field configuration.
	 * @return array
	 */
	private function get_field_options( $field_config ) {
		if ( ! is_array( $field_config ) ) {
			return array();
		}

		$option_keys = array( 'options', 'choices' );

		foreach ( $option_keys as $option_key ) {
			if ( empty( $field_config[ $option_key ] ) || ! is_array( $field_config[ $option_key ] ) ) {
				continue;
			}

			$options = array();

			foreach ( $field_config[ $option_key ] as $value => $label ) {
				if ( is_array( $label ) && isset( $label['label'], $label['value'] ) ) {
					$options[] = array(
						'value' => (string) $label['value'],
						'label' => $label['label'],
					);
				} else {
					$options[] = array(
						'value' => (string) $value,
						'label' => is_string( $label ) ? $label : (string) $value,
					);
				}
			}

			return $options;
		}

		return array();
	}

	/**
	 * Load WordPress action classes.
	 *
	 * @since 3.47.3
	 * @return void
	 */
	private function load_action_files() {
		$action_files = array(
			WPF_DIR_PATH . 'includes/integrations/sureforms/actions/class-sync-contact.php',
			WPF_DIR_PATH . 'includes/integrations/sureforms/actions/class-apply-tags.php',
			WPF_DIR_PATH . 'includes/integrations/sureforms/actions/class-assign-lists.php',
		);

		foreach ( $action_files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
}

SureForms_Integration::get_instance();
