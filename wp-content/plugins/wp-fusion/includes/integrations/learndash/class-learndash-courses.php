<?php
/**
 * WP Fusion - LearnDash Courses
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
 * LearnDash Courses integration.
 *
 * Handles course enrollment, completion tracking, and auto-enrollment via tags.
 *
 * @since 3.46.0
 */
class WPF_LearnDash_Courses {

	/**
	 * Gets things started.
	 *
	 * @since 3.46.0
	 */
	public function __construct() {

		// Course completion.
		add_action( 'learndash_course_completed', array( $this, 'course_completed' ), 5 );

		// Course enrollment tracking.
		add_action( 'learndash_update_course_access', array( $this, 'updated_course_access' ), 10, 4 );

		// Auto enrollments.
		add_action( 'wpf_tags_modified', array( $this, 'update_course_access' ), 10, 2 );

		// Course progress tracking.
		add_action( 'learndash_update_user_activity', array( $this, 'update_user_activity' ) );

		// Settings fields.
		add_filter( 'learndash_course_settings_fields_wpf', array( $this, 'course_settings_fields' ), 10, 2 );
	}

	/**
	 * Applies tags when a LearnDash course is completed.
	 *
	 * @since 3.46.0
	 *
	 * @param array $data The course completion data.
	 */
	public function course_completed( $data ) {

		// get_post_field() to get around ASCII character encoding on get_the_title().

		$update_data = array(
			'ld_last_course_completed'      => get_post_field( 'post_title', $data['course']->ID, 'raw' ),
			'ld_last_course_completed_date' => wpf_get_iso8601_date(),
		);

		wp_fusion()->user->push_user_meta( $data['user']->ID, $update_data );

		$old_settings = (array) get_post_meta( $data['course']->ID, 'wpf-settings', true );
		$new_settings = (array) get_post_meta( $data['course']->ID, 'wpf-settings-learndash', true );

		$settings = array_merge( $old_settings, $new_settings );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_ld'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_ld'], $data['user']->ID );
		}
	}

	/**
	 * Applies / removes linked tags when user added to / removed from course.
	 *
	 * @since 3.46.0
	 *
	 * @param int   $user_id     The user ID.
	 * @param int   $course_id   The course ID.
	 * @param array $access_list The access list.
	 * @param bool  $remove      Whether to remove access.
	 */
	public function updated_course_access( $user_id, $course_id, $access_list = array(), $remove = false ) {

		// Apply the tags.

		$defaults = array(
			'tag_link'            => array(),
			'apply_tags_enrolled' => array(),
			'remove_tags'         => false,
		);

		$settings = wp_parse_args( get_post_meta( $course_id, 'wpf-settings-learndash', true ), $defaults );

		remove_action( 'wpf_tags_modified', array( $this, 'update_course_access' ), 10, 2 );

		if ( false === $remove ) {

			// Sync the fields.

			// Stop special chars in title name getting HTML encoded.
			remove_filter( 'the_title', 'wptexturize' );

			$updated_fields = array(
				'ld_last_course_enrolled'                => get_post_field( 'post_title', $course_id, 'raw' ),
				'course_enrollment_' . $course_id        => gmdate( wpf_get_datetime_format(), ld_course_access_from( $course_id, $user_id ) ),
				'course_enrollment_expiry_' . $course_id => gmdate( wpf_get_datetime_format(), ld_course_access_expires_on( $course_id, $user_id ) ),
			);

			wp_fusion()->user->push_user_meta( $user_id, array_filter( $updated_fields ) );

			$apply_tags = array();

			if ( ! empty( $settings['tag_link'] ) && ! doing_action( 'wpf_tags_modified' ) ) {
				$apply_tags = array_merge( $apply_tags, $settings['tag_link'] );
			}

			if ( ! empty( $settings['apply_tags_enrolled'] ) ) {
				$apply_tags = array_merge( $apply_tags, $settings['apply_tags_enrolled'] );
			}

			if ( ! empty( $apply_tags ) ) {

				$message = 'User was enrolled in LearnDash course <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> ';

				// Safety check to see if this was triggered by the LD + Woo plugin.

				$ld_woo_courses = get_user_meta( $user_id, '_learndash_woocommerce_enrolled_courses_access_counter', true );

				if ( ! empty( $ld_woo_courses ) && isset( $ld_woo_courses[ $course_id ] ) ) {
					$message .= ' by the <strong>LearnDash - WooCommerce plugin</strong>';
				}

				wpf_log( 'info', $user_id, $message . '. Applying tags.' );

				wp_fusion()->user->apply_tags( $apply_tags, $user_id );

			}
		} elseif ( ! doing_action( 'wpf_tags_modified' ) ) {

			$remove_tags = ! empty( $settings['tag_link'] ) ? $settings['tag_link'] : array();

			if ( ! empty( $settings['remove_tags'] ) && ! empty( $settings['apply_tags_enrolled'] ) ) {
				$remove_tags = array_merge( $remove_tags, $settings['apply_tags_enrolled'] );
			}

			if ( $remove_tags ) {

				wpf_log( 'info', $user_id, 'User was unenrolled from LearnDash course <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> . Removing tag(s).' );

				wp_fusion()->user->remove_tags( $remove_tags, $user_id );

			}
		}

		add_action( 'wpf_tags_modified', array( $this, 'update_course_access' ), 10, 2 );
	}

	/**
	 * Update user course enrollment when tags are modified.
	 *
	 * @since 3.46.0
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user tags.
	 */
	public function update_course_access( $user_id, $user_tags ) {

		if ( learndash_can_user_autoenroll_courses( $user_id ) ) {
			return; // user is an admin or group leader, don't bother with the query.
		}

		$linked_courses = get_posts(
			array(
				'post_type'  => 'sfwd-courses',
				'nopaging'   => true,
				'meta_query' => array(
					array(
						'key'     => 'wpf-settings-learndash',
						'compare' => 'EXISTS',
					),
				),
				'fields'     => 'ids',
			)
		);

		// Update course access based on user tags.
		if ( ! empty( $linked_courses ) ) {

			$user_tags = wp_fusion()->user->get_tags( $user_id ); // Get them here for cases where the tags might have changed since wpf_tags_modified was triggered.

			// See if user is enrolled.
			$enrolled_courses = learndash_user_get_enrolled_courses( $user_id, array() );

			// We won't look at courses a user is in because of a group.
			$groups_courses = learndash_get_user_groups_courses_ids( $user_id );

			// Don't bother with open courses since users are enrolled in them by default.
			$open_courses = learndash_get_open_courses();

			$enrolled_courses = array_diff( $enrolled_courses, $open_courses );

			foreach ( $linked_courses as $course_id ) {

				$settings = get_post_meta( $course_id, 'wpf-settings-learndash', true );

				if ( empty( $settings ) || empty( $settings['tag_link'] ) ) {
					continue;
				}

				$tag_id = $settings['tag_link'][0];

				if ( in_array( $course_id, $enrolled_courses ) ) {
					$is_enrolled = true;
				} else {
					$is_enrolled = false;
				}

				if ( in_array( $tag_id, $user_tags ) && ! $is_enrolled ) {

					if ( in_array( $course_id, $groups_courses ) ) {

						// We can't add someone to a course that they already have access to as part of a group.

						wpf_log( 'notice', $user_id, 'User could not be auto-enrolled in LearnDash course <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong> because they already have access to that course as part of a LearnDash group.' );
						continue;

					}

					// Logger.
					wpf_log( 'info', $user_id, 'User auto-enrolled in LearnDash course <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

					ld_update_course_access( $user_id, $course_id, $remove = false );

				} elseif ( ! in_array( $tag_id, $user_tags ) && $is_enrolled ) {

					// Check if unenroll is disabled.
					if ( isset( $settings['tag_link_unenroll'] ) && boolval( $settings['tag_link_unenroll'] ) === false ) {
						continue;
					}

					if ( in_array( $course_id, $groups_courses ) ) {

						// We can't add someone to a course that they already have access to as part of a group.

						wpf_log( 'notice', $user_id, 'User could not be un-enrolled from LearnDash course <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong> because they have access to that course as part a LearnDash group.' );
						continue;

					}

					// Logger.
					wpf_log( 'info', $user_id, 'User un-enrolled from LearnDash course <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

					ld_update_course_access( $user_id, $course_id, $remove = true );

				}
			}
		}
	}

	/**
	 * Sync the last course progressed.
	 *
	 * @since 3.46.0
	 *
	 * @param array $args The user activity args.
	 */
	public function update_user_activity( $args ) {

		$update_data = array();

		// Save the user activity.

		if ( ! empty( $args['course_id'] ) && ( wpf_is_field_active( 'course_progress_' . $args['course_id'] ) || wpf_is_field_active( 'ld_last_course_progressed' ) ) ) {

			remove_action( 'learndash_update_user_activity', array( $this, 'update_user_activity' ) );

			$update_data = array();

			// For performance, we're only going to update this if it's changed.

			$previous_last_course_progressed = get_user_meta( $args['user_id'], 'ld_last_course_progressed', true );

			// Stop special chars in title name getting HTML encoded.
			remove_filter( 'the_title', 'wptexturize' );

			$last_course_progressed = get_post_field( 'post_title', $args['course_id'], 'raw' );

			if ( $last_course_progressed !== $previous_last_course_progressed ) {
				$update_data['ld_last_course_progressed'] = $last_course_progressed;
				update_user_meta( $args['user_id'], 'ld_last_course_progressed', $last_course_progressed );
			}

			$progress = learndash_course_progress(
				array(
					'user_id'   => $args['user_id'],
					'course_id' => $args['course_id'],
					'array'     => true,
				)
			);

			if ( isset( $progress['percentage'] ) ) {
				$update_data[ 'course_progress_' . $args['course_id'] ] = absint( $progress['percentage'] );
			}

			wp_fusion()->user->push_user_meta( $args['user_id'], $update_data );

		}
	}

	/**
	 * Registers LD course fields.
	 *
	 * @since 3.46.0
	 *
	 * @param array  $fields      The fields.
	 * @param string $metabox_key The metabox key.
	 * @return array The fields.
	 */
	public function course_settings_fields( $fields, $metabox_key ) {

		if ( wpf_get_option( 'admin_permissions' ) && ! current_user_can( 'manage_options' ) ) {
			return $fields;
		}

		$settings = array(
			'apply_tags_ld'       => array(),
			'apply_tags_enrolled' => array(),
			'remove_tags'         => false,
			'tag_link'            => array(),
			'tag_link_unenroll'   => true,
			'lesson_locked_text'  => '',
			'step_display'        => false,
		);

		global $post;

		if ( is_object( $post ) ) {
			$settings = wp_parse_args( get_post_meta( $post->ID, 'wpf-settings-learndash', true ), $settings );
		}

		// Migrate settings.
		if ( empty( $settings['step_display'] ) ) {

			if ( ! empty( $settings['lock_lessons'] ) ) {
				$settings['step_display'] = 'lock_lessons';
			}

			if ( ! empty( $settings['filter_steps'] ) ) {
				$settings['step_display'] = 'filter_steps';
			}
		}

		$filter_steps_subfields = array(
			'lesson_locked_text' => array(
				'name' => 'lesson_locked_text',
				'id'   => 'learndash-course-access-settings_course_step_display_lesson_locked_text',
				'args' => array(
					'id'               => 'learndash-course-access-settings_course_step_display_lesson_locked_text',
					'label_for'        => 'lesson_locked_text',
					'name'             => 'learndash-course-wpf[lesson_locked_text]',
					'label'            => sprintf( __( 'Locked %s Text', 'wp-fusion' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
					'type'             => 'text',
					'class'            => 'full-text',
					'placeholder'      => wpf_get_option( 'ld_default_lesson_locked_text', __( 'Not Available', 'wp-fusion' ) ),
					'value'            => $settings['lesson_locked_text'],
					'help_text'        => sprintf( __( 'Enter a message to be displayed on locked %s.', 'wp-fusion' ), learndash_get_custom_label_lower( 'lessons' ) ),
					'input_show'       => true,
					'display_callback' => LearnDash_Settings_Fields::get_field_instance( 'text' )->get_creation_function_ref(),
				),
			),
		);

		$new_options = array(
			'apply_tags_ld'           => array(
				'name'             => 'apply_tags_ld',
				'label'            => __( 'Apply Tags - Marked Complete', 'wp-fusion' ),
				'type'             => 'multiselect',
				'multiple'         => 'true',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
				'desc'             => sprintf( __( 'These tags will be applied in %s when someone marks the course as complete.', 'wp-fusion' ), wp_fusion()->crm->name ),
			),
			'apply_tags_enrolled'     => array(
				'name'             => 'apply_tags_enrolled',
				'label'            => __( 'Apply Tags - Enrolled', 'wp-fusion' ),
				'type'             => 'multiselect',
				'multiple'         => 'true',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
				'desc'             => sprintf( __( 'These tags will be applied in %s when someone is enrolled in this course.', 'wp-fusion' ), wp_fusion()->crm->name ),
			),
			'remove_tags'             => array(
				'name'      => 'remove_tags',
				'value'     => $settings['remove_tags'],
				'label'     => __( 'Remove Tags', 'wp-fusion' ),
				'type'      => 'checkbox-switch',
				'default'   => '',
				'options'   => array(
					'on' => '',
				),
				'help_text' => sprintf( __( 'Remove the enrolled tags from the user when they leave the course.', 'wp-fusion' ), wp_fusion()->crm->name ),
			),
			'tag_link'                => array(
				'name'             => 'tag_link',
				'label'            => __( 'Link with Tag', 'wp-fusion' ),
				'type'             => 'multiselect',
				'multiple'         => 'true',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
				'desc'             => sprintf( __( 'This tag will be applied in %1$s when a user is enrolled, and will be removed when a user is unenrolled. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this course. If this tag is removed, the user will be removed from the course.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ),
				'limit'            => 1,
				'help_text'        => sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/learning-management/learndash/#course-specific-settings" target="_blank">', '</a>' ),
			),
			'tag_link_unenroll'       => array(
				'name'      => 'tag_link_unenroll',
				'value'     => $settings['tag_link_unenroll'],
				'label'     => __( 'Unenroll from Course when Linked Tag is Removed', 'wp-fusion' ),
				'type'      => 'checkbox-switch',
				'default'   => 'on',
				'options'   => array(
					'on' => '',
				),
				'help_text' => sprintf( __( 'When the linked tag is removed from the user, they will be unenrolled from the course (this is the default behavior).', 'wp-fusion' ), wp_fusion()->crm->name ),
			),
			'step_display'            => array(
				'name'    => 'step_display',
				'label'   => sprintf( __( 'WP Fusion - %s Navigation', 'wp-fusion' ), LearnDash_Custom_Label::get_label( 'course' ) ),
				'type'    => 'radio',
				'value'   => $settings['step_display'],
				'default' => 'default',
				'options' => array(
					'default'      => array(
						'label'       => esc_html__( 'Default', 'wp-fusion' ),
						'description' => sprintf(
							// translators: placeholder: course, crm name.
							esc_html_x( 'The %1$s navigation will show all content, regardless of of the user\'s %2$s tags.', 'placeholder: course, crm name ', 'wp-fusion' ),
							learndash_get_custom_label_lower( 'course' ),
							wp_fusion()->crm->name
						),
					),
					'lock_lessons' => array(
						'label'               => sprintf( __( 'Lock %s', 'wp-fusion' ), learndash_get_custom_label_lower( 'lessons' ) ),
						'description'         => sprintf(
							// translators: placeholder: course.
							esc_html_x( 'Content that a user cannot access will show as disabled in the %s navigation.', 'placeholder: course', 'wp-fusion' ),
							learndash_get_custom_label_lower( 'course' )
						),
						'inline_fields'       => array(
							'step_display_lock_lessons' => $filter_steps_subfields,
						),
						'inner_section_state' => ( 'lock_lessons' === $settings['step_display'] ) ? 'open' : 'closed',
					),
					'filter_steps' => array(
						'label'       => sprintf( __( 'Filter %s steps', 'wp-fusion' ), learndash_get_custom_label_lower( 'course' ) ),
						'description' => sprintf(
							// translators: placeholder: lessons, course.
							esc_html_x( '%1$s, topics, and quizzes that a user doesn\'t have access to will be removed from the %2$s navigation, and won\'t be required for course completion.', 'placeholder: lessons, course', 'wp-fusion' ),
							LearnDash_Custom_Label::get_label( 'lessons' ),
							learndash_get_custom_label_lower( 'course' )
						),
					),
				),
			),
			'progress_field'          => array(
				'name'             => "course_progress_{$post->ID}",
				'label'            => __( 'Field - Course Progress', 'wp-fusion' ),
				'type'             => 'select',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_crm_field_dropdown' ),
				'desc'             => sprintf( __( 'As the user progresses through the course, their course completion percentage will be synced to the selected custom field in %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
			),
			'enrollment_field'        => array(
				'name'             => "course_enrollment_{$post->ID}",
				'label'            => __( 'Field - Course Enrollment Date', 'wp-fusion' ),
				'type'             => 'select',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_crm_field_dropdown' ),
				'desc'             => sprintf( __( 'When the user enrolls in the course, the date will be synced to the selected custom field in %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
			),
			'enrollment_expiry_field' => array(
				'name'             => "course_enrollment_expiry_{$post->ID}",
				'label'            => __( 'Field - Course Enrollment Expiration Date', 'wp-fusion' ),
				'type'             => 'select',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_crm_field_dropdown' ),
				'desc'             => sprintf( __( 'When Course Access Expiration is enabled and the user enrolls, the date that their access expires will be synced to the selected custom field in %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
			),
		);

		// Warning if course is open and a linked tag is set.

		if ( is_object( $post ) ) {

			if ( ! empty( $settings['tag_link'] ) ) {

				$course_settings = get_post_meta( $post->ID, '_sfwd-courses', true );

				if ( ! empty( $course_settings ) && isset( $course_settings['sfwd-courses_course_price_type'] ) ) {

					if ( 'free' == $course_settings['sfwd-courses_course_price_type'] || 'open' == $course_settings['sfwd-courses_course_price_type'] ) {

						$new_options['tag_link']['desc'] .= '<br /><br/><div class="ld-settings-info-banner ld-settings-info-banner-alert"><p>' . sprintf( __( '<strong>Note:</strong> Your course Access Mode is currently set to <strong>%s</strong>, for auto-enrollments to work correctly your course Access Mode should be set to "closed".', 'wp-fusion' ), $course_settings['sfwd-courses_course_price_type'] ) . '</p></div>';

					}
				}
			}

			// Warning about "Single Page Courses" module in Uncanny LearnDash Toolkit Pro.

			if ( 'filter_steps' === $settings['step_display'] && class_exists( 'uncanny_learndash_toolkit\Config' ) ) {

				if ( uncanny_learndash_toolkit\Config::is_toolkit_module_active( 'uncanny_pro_toolkit\OnePageCourseStep' ) ) {

					$new_options['step_display']['desc'] = '<div class="ld-settings-info-banner ld-settings-info-banner-alert"><p>' . sprintf( __( '<strong>Note:</strong> You have the "Single Page Courses" module enabled in the <a href="%1$s">Uncanny LearnDash Toolkit Pro</a> plugin. This will cause issues with the "Filter %2$s steps" option, so you should disable it.', 'wp-fusion' ), admin_url( 'admin.php?page=uncanny-toolkit' ), learndash_get_custom_label_lower( 'course' ) ) . '</p></div>';

				}
			}
		}

		if ( class_exists( 'Learndash_WooCommerce' ) ) {

			$new_options['tag_link']['desc'] .= '<br /><br/><div class="ld-settings-info-banner ld-settings-info-banner-alert"><p>';
			$new_options['tag_link']['desc'] .= __( '<strong>Warning:</strong> The <strong>LearnDash - WooCommerce</strong> plugin is active. If access to this course is managed by that plugin, you should <em>not</em> use the Link With Tag setting, as it will cause your students to become unenrolled from the course when their renewal payments are processed.', 'wp-fusion' );
			$new_options['tag_link']['desc'] .= '</p></div>';

		} elseif ( function_exists( 'memberdash' ) ) {

			$new_options['tag_link']['desc'] .= '<br /><br/><div class="ld-settings-info-banner ld-settings-info-banner-alert"><p>';
			$new_options['tag_link']['desc'] .= __( '<strong>Warning:</strong> The <strong>MemberDash</strong> plugin is active. If access to this course is managed by that plugin, you should <em>not</em> use the Link With Tag setting, as it will cause your students to become unenrolled from the course when their renewal payments are processed.', 'wp-fusion' );
			$new_options['tag_link']['desc'] .= '</p></div>';

		}

		$fields = wp_fusion()->settings->insert_setting_after( 'course_access_list', $fields, $new_options );

		return $fields;
	}
}
