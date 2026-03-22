<?php
/**
 * WP Fusion - FluentCommunity Courses Management
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.44.20
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * FluentCommunity Courses Management.
 *
 * @since 3.44.20
 */
class WPF_FluentCommunity_Courses {

	/**
	 * The main integration instance.
	 *
	 * @since 3.44.20
	 * @var   WPF_FluentCommunity
	 */
	private $integration;

	/**
	 * Constructor.
	 *
	 * @since 3.44.20
	 *
	 * @param WPF_FluentCommunity $integration The main integration instance.
	 */
	public function __construct( $integration ) {
		$this->integration = $integration;

		// Add hooks directly in constructor.
		add_action( 'fluent_community/course/enrolled', array( $this, 'course_enrolled' ), 10, 2 );
		add_action( 'fluent_community/course/unenrolled', array( $this, 'course_unenrolled' ), 10, 2 );
		add_action( 'fluent_community/course/completed', array( $this, 'course_completed' ), 10, 2 );
		add_action( 'fluent_community/course/lesson_completed', array( $this, 'lesson_completed' ), 10, 2 );
	}

	/**
	 * Gets all courses from FluentCommunity.
	 *
	 * @since  3.44.20
	 *
	 * @return array Courses.
	 */
	public function get_courses() {
		$courses = \FluentCommunity\App\Functions\Utility::getCourses();

		// Convert to id => title format.
		$formatted = array();
		foreach ( $courses as $course ) {
			$formatted[ $course->id ] = $course->title;
		}

		return $formatted;
	}

	/**
	 * Handle course enrollment.
	 *
	 * @since  3.44.20
	 *
	 * @param object $course  The course object.
	 * @param int    $user_id The user ID.
	 */
	public function course_enrolled( $course, $user_id ) {

		// Update meta.
		wp_fusion()->user->push_user_meta(
			$user_id,
			array(
				'fc_last_course_enrolled'      => $course->title,
				'fc_last_course_enrolled_date' => current_time( 'Y-m-d H:i:s' ),
			)
		);

		$apply_tags = array();

		$settings = $course->getCustomMeta( '_wpf_settings', array() );

		if ( ! empty( $settings['tags'] ) ) {
			$apply_tags = array_merge( $apply_tags, $settings['tags'] );
		}

		if ( ! empty( $settings['tag_link'] ) && ! doing_action( 'wpf_tags_modified' ) ) {
			$tag_link   = is_array( $settings['tag_link'] ) ? $settings['tag_link'] : array( $settings['tag_link'] );
			$apply_tags = array_merge( $apply_tags, $tag_link );
		}

		if ( ! empty( $apply_tags ) ) {
			wpf_log( 'info', $user_id, 'User enrolled in FluentCommunity course <strong>' . $course->title . '</strong>. Applying tags:' );

			remove_action( 'wpf_tags_modified', array( $this->integration, 'tags_modified' ) ); // Prevent looping.
			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
			add_action( 'wpf_tags_modified', array( $this->integration, 'tags_modified' ), 10, 2 ); // Prevent looping.
		}
	}

	/**
	 * Handle course unenrollment.
	 *
	 * @since  3.44.20
	 *
	 * @param object $course  The course object.
	 * @param int    $user_id The user ID.
	 */
	public function course_unenrolled( $course, $user_id ) {

		$remove_tags = array();

		// Get settings from course meta (new API) or fall back to global settings.
		$settings = $course->getCustomMeta( '_wpf_settings', array() );

		if ( ! empty( $settings['remove'] ) && '1' === $settings['remove'] && ! empty( $settings['tags'] ) ) {
			$remove_tags = array_merge( $remove_tags, $settings['tags'] );
		}

		if ( ! empty( $settings['tag_link'] ) ) {
			$tag_link    = is_array( $settings['tag_link'] ) ? $settings['tag_link'] : array( $settings['tag_link'] );
			$remove_tags = array_merge( $remove_tags, $tag_link );
		}

		if ( ! empty( $remove_tags ) ) {
			wpf_log( 'info', $user_id, 'User un-enrolled from FluentCommunity course <strong>' . $course->title . '</strong>. Removing tags:' );

			remove_action( 'wpf_tags_modified', array( $this->integration, 'tags_modified' ) ); // Prevent looping.
			wp_fusion()->user->remove_tags( $remove_tags, $user_id );
			add_action( 'wpf_tags_modified', array( $this->integration, 'tags_modified' ), 10, 2 ); // Prevent looping.
		}
	}

	/**
	 * Handle course completion.
	 *
	 * @since  3.44.20
	 *
	 * @param object $course  The course object.
	 * @param int    $user_id The user ID.
	 */
	public function course_completed( $course, $user_id ) {

		// Update meta.
		wp_fusion()->user->push_user_meta(
			$user_id,
			array(
				'fc_last_course_completed'      => $course->title,
				'fc_last_course_completed_date' => current_time( 'Y-m-d H:i:s' ),
			)
		);

		// Get settings from course meta (new API) or fall back to global settings.
		$settings = $course->getCustomMeta( '_wpf_settings', array() );

		if ( ! empty( $settings['complete_tags'] ) ) {
			wpf_log( 'info', $user_id, 'User completed FluentCommunity course <strong>' . $course->title . '</strong>. Applying tags:' );
			wp_fusion()->user->apply_tags( $settings['complete_tags'], $user_id );
		}
	}

	/**
	 * Handle lesson completed.
	 *
	 * @since  3.44.20
	 *
	 * @param object $lesson  The lesson object.
	 * @param int    $user_id The user ID.
	 */
	public function lesson_completed( $lesson, $user_id ) {

		// Update meta.
		wp_fusion()->user->push_user_meta(
			$user_id,
			array(
				'fc_last_lesson_completed'      => $lesson->title,
				'fc_last_lesson_completed_date' => current_time( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Handle course tag synchronization.
	 *
	 * @since  3.44.20
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user's tags.
	 */
	public function sync_course_tags( $user_id, $user_tags ) {

		$courses = $this->get_courses();

		foreach ( $courses as $course_id => $title ) {

			// Get the course object to access settings.
			$course = \FluentCommunity\Modules\Course\Model\Course::find( $course_id );

			if ( ! $course ) {
				continue;
			}

			$settings = $course->getCustomMeta( '_wpf_settings', array() );

			if ( empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_link = $settings['tag_link'];

			// Backward compatibility: convert single value to array.
			if ( ! is_array( $tag_link ) ) {
				$tag_link = array( $tag_link );
			}

			$is_enrolled = \FluentCommunity\Modules\Course\Services\CourseHelper::isEnrolled( $course_id, $user_id );

			$matched_tags = array_intersect( $tag_link, $user_tags );

			if ( $matched_tags && ! $is_enrolled ) {

				// Enroll in course.
				\FluentCommunity\Modules\Course\Services\CourseHelper::enrollCourse( $course_id, $user_id );

				$tag_labels = array_map( 'wpf_get_tag_label', $matched_tags );
				wpf_log( 'info', $user_id, 'User enrolled in FluentCommunity course <strong>' . $title . '</strong> by linked tag(s) <strong>' . implode( ', ', $tag_labels ) . '</strong>' );

			} elseif ( ! $matched_tags && $is_enrolled ) {

				// Un-enroll from course.
				\FluentCommunity\Modules\Course\Services\CourseHelper::leaveCourse( $course_id, $user_id );

				$tag_labels = array_map( 'wpf_get_tag_label', $tag_link );
				wpf_log( 'info', $user_id, 'User un-enrolled from FluentCommunity course <strong>' . $title . '</strong>. No linked tags remaining: <strong>' . implode( ', ', $tag_labels ) . '</strong>' );
			}
		}
	}
}
