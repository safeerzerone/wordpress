<?php
/**
 * WP Fusion - LearnDash Lessons
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.46.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * LearnDash Lessons integration.
 *
 * Handles lesson, topic completion tracking and assignment uploads.
 *
 * @since 3.46.0
 */
class WPF_LearnDash_Lessons {

	/**
	 * Gets things started.
	 *
	 * @since 3.46.0
	 */
	public function __construct() {

		// Lesson completion.
		add_action( 'learndash_lesson_completed', array( $this, 'lesson_completed' ), 5 );

		// Topic completion.
		add_action( 'learndash_topic_completed', array( $this, 'topic_completed' ), 5 );

		// Assignment uploads.
		add_action( 'learndash_assignment_uploaded', array( $this, 'assignment_uploaded' ), 10, 2 );

		// Don't apply tags when an LD-restricted lesson is viewed.
		add_filter( 'wpf_apply_tags_on_view', array( $this, 'maybe_stop_apply_tags_on_view' ), 10, 2 );

		// Assignment settings.
		add_filter( 'learndash_settings_fields', array( $this, 'lesson_settings_fields' ), 10, 2 );
	}

	/**
	 * Applies tags when a LearnDash lesson is completed.
	 *
	 * @since 3.46.0
	 *
	 * @param array $data The lesson completion data.
	 */
	public function lesson_completed( $data ) {

		$update_data = array(
			'ld_last_lesson_completed'      => get_post_field( 'post_title', $data['lesson']->ID, 'raw' ),
			'ld_last_lesson_completed_date' => wpf_get_iso8601_date(),
		);

		wp_fusion()->user->push_user_meta( $data['user']->ID, $update_data );

		$settings = get_post_meta( $data['lesson']->ID, 'wpf-settings', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_ld'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_ld'], $data['user']->ID );
		}
	}

	/**
	 * Applies tags when a LearnDash topic is completed.
	 *
	 * @since 3.46.0
	 *
	 * @param array $data The topic completion data.
	 */
	public function topic_completed( $data ) {

		$update_data = array(
			'ld_last_topic_completed' => get_post_field( 'post_title', $data['topic']->ID, 'raw' ),
		);

		wp_fusion()->user->push_user_meta( $data['user']->ID, $update_data );

		$settings = get_post_meta( $data['topic']->ID, 'wpf-settings', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_ld'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_ld'], $data['user']->ID );
		}
	}

	/**
	 * Apply tags when an assignment has been uploaded.
	 *
	 * @since 3.46.0
	 *
	 * @param int   $assignment_post_id The assignment post ID.
	 * @param array $assignment_meta    The assignment meta data.
	 */
	public function assignment_uploaded( $assignment_post_id, $assignment_meta ) {

		$settings = get_post_meta( $assignment_meta['lesson_id'], 'wpf-settings-learndash', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_assignment_upload'] ) ) {

			wp_fusion()->user->apply_tags( $settings['apply_tags_assignment_upload'], $assignment_meta['user_id'] );

		}
	}

	/**
	 * Don't apply tags on view when a LD-restricted lesson is viewed
	 *
	 * @access public
	 * @return bool Proceed
	 */
	public function maybe_stop_apply_tags_on_view( $proceed, $post_id ) {

		if ( get_post_type( $post_id ) == 'sfwd-lessons' ) {

			$access_from = ld_lesson_access_from( $post_id, wpf_get_current_user_id() );

			if ( $access_from > time() ) {
				$proceed = false;
			}
		}

		return $proceed;
	}

	/**
	 * Adds WPF settings to assignment upload section in lesson settings.
	 *
	 * @since 3.46.0
	 *
	 * @param array  $options_fields The options fields.
	 * @param string $metabox_key    The metabox key.
	 * @return array The options fields.
	 */
	public function lesson_settings_fields( $options_fields, $metabox_key ) {

		if ( 'learndash-lesson-display-content-settings' == $metabox_key || 'learndash-topic-display-content-settings' == $metabox_key ) {

			$new_options = array(
				'apply_tags_assignment_upload' => array(
					'name'             => 'apply_tags_assignment_upload',
					'label'            => esc_html__( 'Apply Tags', 'wp-fusion' ),
					'type'             => 'multiselect',
					'multiple'         => 'true',
					'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
					'parent_setting'   => 'lesson_assignment_upload',
					'desc'             => sprintf( __( 'Select tags to be applied to the student in %s when an assigment is uploaded.', 'wp-fusion' ), wp_fusion()->crm->name ),
				),
			);

			$options_fields = wp_fusion()->settings->insert_setting_after( 'assignment_upload_limit_size', $options_fields, $new_options );

		}

		return $options_fields;
	}
}
