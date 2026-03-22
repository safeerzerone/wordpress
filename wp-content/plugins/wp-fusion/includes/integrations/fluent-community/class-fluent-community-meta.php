<?php
/**
 * WP Fusion - FluentCommunity Meta Settings API
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.46.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * FluentCommunity Meta Settings API Integration.
 *
 * @since 3.46.3
 */
class WPF_FluentCommunity_Meta {

	/**
	 * The main integration instance.
	 *
	 * @since 3.46.3
	 * @var   WPF_FluentCommunity
	 */
	private $integration;

	/**
	 * Constructor.
	 *
	 * @since 3.46.3
	 *
	 * @param WPF_FluentCommunity $integration The main integration instance.
	 */
	public function __construct( $integration ) {
		$this->integration = $integration;

		// Add hooks directly in constructor.
		add_action( 'fluent_community/on_wp_init', array( $this, 'register_meta_boxes' ) );
	}

	/**
	 * Register meta boxes for courses and spaces.
	 *
	 * @since 3.46.3
	 *
	 * @param object $app The FluentCommunity app instance.
	 */
	public function register_meta_boxes( $app ) {

		$api = \FluentCommunity\App\Functions\Utility::extender();

		$api->addMetaBox(
			'wp_fusion',
			array(
				'section_title'   => sprintf(
					// translators: %s is the CRM name.
					__( '%s Integration', 'wp-fusion' ),
					wp_fusion()->crm->name
				),
				'fields_callback' => array( $this, 'get_meta_fields' ),
				'data_callback'   => array( $this, 'get_meta_data' ),
				'save_callback'   => array( $this, 'save_meta_data' ),
			),
			array( 'course', 'space' )
		);
	}

	/**
	 * Get meta fields for the settings form.
	 *
	 * @since 3.46.3
	 *
	 * @param object $model The course or space model.
	 * @return array The form fields.
	 */
	public function get_meta_fields( $model ) {

		$object_type = $this->get_object_type( $model );

		$fields = array(
			'tags'     => array(
				'label'       => sprintf(
					// translators: %s is "enrolled" for courses or "joined" for spaces.
					__( 'Apply Tags - %s', 'wp-fusion' ),
					'course' === $object_type ? __( 'Enrolled', 'wp-fusion' ) : __( 'Joined', 'wp-fusion' )
				),
				'type'        => 'select',
				'is_multiple' => true,
				'options'     => $this->get_tag_options(),
				'help_text'   => sprintf(
					// translators: %1$s is the CRM name, %2$s is "enroll" for courses or "join" for spaces.
					__( 'These tags will be applied in %1$s when someone %2$ss this %3$s.', 'wp-fusion' ),
					wp_fusion()->crm->name,
					'course' === $object_type ? __( 'enroll', 'wp-fusion' ) : __( 'join', 'wp-fusion' ),
					$object_type
				),
			),
			'remove'   => array(
				'type'           => 'inline_checkbox',
				'checkbox_label' => __( 'Remove tags when user leaves', 'wp-fusion' ),
				'true_value'     => '1',
				'false_value'    => '0',
				'help_text'      => sprintf(
					// translators: %s is "unenrolls" for courses or "leaves" for spaces.
					__( 'Remove the tags when the user %s.', 'wp-fusion' ),
					'course' === $object_type ? __( 'unenrolls', 'wp-fusion' ) : __( 'leaves', 'wp-fusion' )
				),
			),
			'tag_link' => array(
				'label'       => __( 'Link with Tags', 'wp-fusion' ),
				'type'        => 'select',
				'is_multiple' => true,
				'options'     => $this->get_tag_options(),
				'help_text'   => sprintf(
					// translators: %1$s and %2$s are both the CRM name, %3$s is the object type, %4$s is "enrolled/joined", %5$s is "unenrolled/left".
					__( 'These tags will be applied in %1$s when a user %4$s, and will be removed when a user %5$s. Likewise, if any of these tags are applied to a user from within %2$s, they will be automatically added to this %3$s. If none of the tags remain, the user will be removed from the %3$s.', 'wp-fusion' ),
					wp_fusion()->crm->name,
					wp_fusion()->crm->name,
					$object_type,
					'course' === $object_type ? __( 'enrolls', 'wp-fusion' ) : __( 'joins', 'wp-fusion' ),
					'course' === $object_type ? __( 'unenrolls', 'wp-fusion' ) : __( 'leaves', 'wp-fusion' )
				),
			),
		);

		// Add course completion tags for courses only.
		if ( 'course' === $object_type ) {
			$fields['complete_tags'] = array(
				'label'       => __( 'Apply Tags - Complete', 'wp-fusion' ),
				'type'        => 'select',
				'is_multiple' => true,
				'options'     => $this->get_tag_options(),
				'help_text'   => sprintf(
					// translators: %s is the CRM name.
					__( 'These tags will be applied in %s when someone completes this course.', 'wp-fusion' ),
					wp_fusion()->crm->name
				),
			);
		}

		return $fields;
	}

	/**
	 * Get meta data for the settings form.
	 *
	 * @since 3.46.3
	 *
	 * @param object $model The course or space model.
	 * @return array The settings data.
	 */
	public function get_meta_data( $model ) {

		$settings = $model->getCustomMeta( '_wpf_settings', array() );

		if ( isset( $settings['tag_link'] ) && ! is_array( $settings['tag_link'] ) ) {
			// Migration from old single-tag storage to multi-tag array.
			$settings['tag_link'] = ! empty( $settings['tag_link'] ) ? array( $settings['tag_link'] ) : array();
		}

		$defaults = array(
			'tags'     => array(),
			'remove'   => '0',
			'tag_link' => array(),
		);

		// Add course completion defaults for courses.
		if ( 'course' === $this->get_object_type( $model ) ) {
			$defaults['complete_tags'] = array();
		}

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Save meta data from the settings form.
	 *
	 * @since 3.46.3
	 *
	 * @param array  $settings The settings data.
	 * @param object $model    The course or space model.
	 */
	public function save_meta_data( $settings, $model ) {

		// Sanitize and validate settings.
		$clean_settings = array();

		if ( isset( $settings['tags'] ) ) {
			$clean_settings['tags'] = wpf_clean_tags( $settings['tags'] );
		}

		if ( isset( $settings['remove'] ) ) {
			$clean_settings['remove'] = '1' === $settings['remove'] ? '1' : '0';
		}

		if ( isset( $settings['tag_link'] ) ) {
			$clean_settings['tag_link'] = wpf_clean_tags( $settings['tag_link'] );
		}

		if ( isset( $settings['complete_tags'] ) ) {
			$clean_settings['complete_tags'] = wpf_clean_tags( $settings['complete_tags'] );
		}

		$model->updateCustomMeta( '_wpf_settings', $clean_settings );
	}

	/**
	 * Get available tags as options for select fields.
	 *
	 * @since 3.46.3
	 *
	 * @param bool $include_read_only Whether to include read-only tags.
	 * @return array The tag options.
	 */
	private function get_tag_options( $include_read_only = false ) {

		$available_tags = wp_fusion()->settings->get_available_tags_flat( $include_read_only );
		$options        = array();

		foreach ( $available_tags as $tag_id => $tag_label ) {
			$options[] = array(
				'label' => $tag_label,
				'value' => $tag_id,
			);
		}

		return $options;
	}

	/**
	 * Get the object type from the model.
	 *
	 * @since 3.46.3
	 *
	 * @param object $model The course or space model.
	 * @return string The object type ('course' or 'space').
	 */
	private function get_object_type( $model ) {

		if ( $model instanceof \FluentCommunity\Modules\Course\Model\Course ) {
			return 'course';
		} elseif ( $model instanceof \FluentCommunity\App\Models\Space ) {
			return 'space';
		}

		return 'unknown';
	}
}
