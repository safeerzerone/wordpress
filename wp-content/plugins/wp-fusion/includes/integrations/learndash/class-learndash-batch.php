<?php
/**
 * WP Fusion - LearnDash Batch
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
 * LearnDash Batch integration.
 *
 * Handles batch operations for LearnDash.
 *
 * @since 3.46.0
 */
class WPF_LearnDash_Batch {

	/**
	 * Gets things started.
	 *
	 * @since 3.46.0
	 */
	public function __construct() {

		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );

		add_filter( 'wpf_batch_learndash_courses_init', array( $this, 'batch_init' ) );
		add_filter( 'wpf_batch_learndash_progress_init', array( $this, 'batch_init' ) );
		add_filter( 'wpf_batch_learndash_groups_init', array( $this, 'batch_init' ) );
		add_filter( 'wpf_batch_learndash_progress_meta_init', array( $this, 'batch_init' ) );
		add_filter( 'wpf_batch_learndash_quiz_results_init', array( $this, 'batch_init' ) );

		add_action( 'wpf_batch_learndash_courses', array( $this, 'batch_step_courses' ) );
		add_action( 'wpf_batch_learndash_progress', array( $this, 'batch_step_progress' ) );
		add_action( 'wpf_batch_learndash_groups', array( $this, 'batch_step_groups' ) );
		add_action( 'wpf_batch_learndash_progress_meta', array( $this, 'batch_step_progress_meta' ) );
		add_action( 'wpf_batch_learndash_quiz_results', array( $this, 'batch_step_quiz_results' ) );
	}


	/**
	 * Adds LearnDash courses to available export options
	 *
	 * @access public
	 * @return array Options
	 */
	public function export_options( $options ) {

		$options['learndash_courses'] = array(
			'label'   => __( 'LearnDash course enrollment statuses', 'wp-fusion' ),
			'title'   => __( 'Users', 'wp-fusion' ),
			'tooltip' => sprintf( __( 'For each user on your site, applies tags in %s based on their current LearnDash course enrollments, using the settings configured on each course. <br /><br />Note that this does not apply to course enrollments that have been granted via Groups. <br /><br />Note that this does not enroll or unenroll any users from courses, it just applies tags based on existing course enrollments.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		$options['learndash_progress'] = array(
			'label'   => __( 'LearnDash course progress', 'wp-fusion' ),
			'title'   => __( 'Users', 'wp-fusion' ),
			'tooltip' => sprintf( __( 'For each user on your site, applies tags in %s based on their current course progress, based on the <em>Apply tags when marked complete</em> settings on every course, lesson, topic, and quiz.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		$options['learndash_groups'] = array(
			'label'   => __( 'LearnDash group enrollment statuses', 'wp-fusion' ),
			'title'   => __( 'Users', 'wp-fusion' ),
			'tooltip' => sprintf( __( 'For each user on your site, applies tags in %s based on their current LearnDash group enrollments, using the settings configured on each group.<br /><br />Note that this does not enroll or unenroll any users from groups, it just applies tags based on existing group enrollments.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		$options['learndash_progress_meta'] = array(
			'label'   => __( 'LearnDash course progress meta', 'wp-fusion' ),
			'title'   => __( 'Users', 'wp-fusion' ),
			'tooltip' => sprintf( __( 'For each user on your site, syncs any enabled progress fields (Last Course, Topic, and Lesson Completed, Last Course Enrolled, course completion percentages, etc.) to %s.<br /><br />Does not apply any tags or affect enrollments.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		$options['learndash_quiz_results'] = array(
			'label'   => __( 'LearnDash quiz results', 'wp-fusion' ),
			'title'   => __( 'Users', 'wp-fusion' ),
			'tooltip' => sprintf( __( 'For each user on your site, syncs any enabled quiz result fields (quiz scores, points, category scores) to %s based on their completed quizzes.<br /><br />Does not apply any tags or affect enrollments.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		return $options;
	}

	/**
	 * Gets users to be processed
	 *
	 * @since 3.46.11 Updated to only query active LearnDash users.
	 * @return array User IDs.
	 */
	public function batch_init() {

		global $wpdb;

		// Only get users who actually have quiz activity data.
		$user_ids = $wpdb->get_col(
			'SELECT DISTINCT user_id 
			 FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' 
			 WHERE activity_completed IS NOT NULL
			 ORDER BY user_id ASC'
		);

		return array_map( 'intval', $user_ids );
	}

	/**
	 * Process user enrollments one at a time
	 *
	 * @access public
	 * @return void
	 */
	public function batch_step_courses( $user_id ) {

		// Get courses
		$enrolled_courses = learndash_user_get_enrolled_courses( $user_id, array() );

		// We won't look at courses a user is in because of a group
		$groups_courses = learndash_get_user_groups_courses_ids( $user_id );

		$enrolled_courses = array_diff( $enrolled_courses, $groups_courses );

		if ( ! empty( $enrolled_courses ) ) {

			foreach ( $enrolled_courses as $course_id ) {

				wpf_log( 'info', $user_id, 'Processing LearnDash course enrollment status for <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '">' . get_the_title( $course_id ) . '</a>' );

				wp_fusion()->integrations->learndash->courses->updated_course_access( $user_id, $course_id );

			}
		}
	}


	/**
	 * Apply tags for a single user's course progress.
	 *
	 * @since 3.37.12
	 *
	 * @param int $user_id The user identifier.
	 */
	public function batch_step_progress( $user_id ) {

		$enrolled_courses = learndash_user_get_enrolled_courses( $user_id, array() );

		$apply_tags = array();

		foreach ( $enrolled_courses as $course_id ) {

			$progress_all = learndash_user_get_course_progress( $user_id, $course_id, 'co' );
			$progress     = array_filter( $progress_all );

			// If the number of completed = the number of steps, we'll consider the course complete

			if ( $progress_all == $progress ) {

				$old_settings = (array) get_post_meta( $course_id, 'wpf-settings', true );
				$settings     = (array) get_post_meta( $course_id, 'wpf-settings-learndash', true );
				$settings     = array_merge( $old_settings, $settings );

				if ( ! empty( $settings ) && ! empty( $settings['apply_tags_ld'] ) ) {
					$apply_tags = array_merge( $apply_tags, $settings['apply_tags_ld'] );
				}
			}

			// Now get the settings from the individual lessons / topics / etc

			foreach ( $progress as $step => $completed ) {

				$step     = explode( ':', $step );
				$step_id  = $step[1];
				$settings = get_post_meta( $step_id, 'wpf-settings', true );

				if ( ! empty( $settings ) && ! empty( $settings['apply_tags_ld'] ) ) {
					$apply_tags = array_merge( $apply_tags, $settings['apply_tags_ld'] );
				}
			}
		}

		if ( ! empty( $apply_tags ) ) {

			wp_fusion()->user->apply_tags( $apply_tags, $user_id );

		}
	}

	/**
	 * Apply tags for a single user's group enrollments.
	 *
	 * @since 3.37.12
	 *
	 * @param int $user_id The user identifier.
	 */
	public function batch_step_groups( $user_id ) {

		$users_group_ids = learndash_get_users_group_ids( $user_id );

		foreach ( $users_group_ids as $group_id ) {

			wpf_log( 'info', $user_id, 'Processing LearnDash group enrollment status for <a href="' . admin_url( 'post.php?post=' . $group_id . '&action=edit' ) . '">' . get_the_title( $group_id ) . '</a>' );

			wp_fusion()->integrations->learndash->groups->added_group_access( $user_id, $group_id );

		}
	}

	/**
	 * Sync progress meta for users.
	 *
	 * @since 3.40.24
	 *
	 * @param int $user_id The user identifier.
	 */
	public function batch_step_progress_meta( $user_id ) {

		global $wpdb;

		// Last course enrolled.
		$ld_last_course_enrolled = $wpdb->get_var( $wpdb->prepare( 'SELECT course_id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND activity_type=%s ORDER BY activity_started DESC LIMIT 1', $user_id, 'access' ) );

		// Last topic completed.
		$ld_last_topic_completed = $wpdb->get_var( $wpdb->prepare( 'SELECT post_id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND activity_type=%s ORDER BY activity_completed DESC LIMIT 1', $user_id, 'topic' ) );

		// Last course progressed.
		$ld_last_course_progressed = $wpdb->get_var( $wpdb->prepare( 'SELECT course_id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND activity_type=%s ORDER BY activity_updated DESC LIMIT 1', $user_id, 'course' ) );

		// Last lesson completed/Date.
		$ld_last_lesson_completed = $wpdb->get_row( $wpdb->prepare( 'SELECT post_id,activity_completed FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND activity_type=%s ORDER BY activity_completed DESC LIMIT 1', $user_id, 'lesson' ), ARRAY_A );

		// Last course completed/Date.
		$ld_last_course_completed = $wpdb->get_row( $wpdb->prepare( 'SELECT course_id,activity_completed FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND activity_type=%s ORDER BY activity_completed DESC LIMIT 1', $user_id, 'course' ), ARRAY_A );

		// Enrollment Date.
		// $ld_course_enrollment_date = $wpdb->get_var( $wpdb->prepare( 'SELECT activity_started FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND activity_type=%s ORDER BY activity_started ASC LIMIT 1', $user_id, 'access' ) );

		$update_data = array(
			'ld_last_course_completed'      => ( $ld_last_course_completed && $ld_last_course_completed['activity_completed'] ? get_post_field( 'post_title', $ld_last_course_completed['course_id'], 'raw' ) : '' ),
			'ld_last_course_completed_date' => ( $ld_last_course_completed && $ld_last_course_completed['activity_completed'] ? $ld_last_course_completed['activity_completed'] : '' ),

			'ld_last_lesson_completed'      => ( $ld_last_lesson_completed && $ld_last_lesson_completed['post_id'] ? get_post_field( 'post_title', $ld_last_lesson_completed['post_id'], 'raw' ) : '' ),
			'ld_last_lesson_completed_date' => ( $ld_last_lesson_completed && $ld_last_lesson_completed['activity_completed'] ? $ld_last_lesson_completed['activity_completed'] : '' ),

			'ld_last_course_progressed'     => ( $ld_last_course_progressed ? get_post_field( 'post_title', $ld_last_course_progressed, 'raw' ) : '' ),
			'ld_last_topic_completed'       => ( $ld_last_topic_completed ? get_post_field( 'post_title', $ld_last_topic_completed, 'raw' ) : '' ),
			'ld_last_course_enrolled'       => ( $ld_last_course_enrolled ? get_post_field( 'post_title', $ld_last_course_enrolled, 'raw' ) : '' ),
		);

		// Remove empty dates.
		$update_data = array_filter( $update_data );

		// Course progress fields.

		$user_course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

		if ( ! empty( $user_course_progress ) ) {

			foreach ( $user_course_progress as $course_id => $course_progress ) {

				if ( ! empty( ld_course_access_from( $course_id, $user_id ) ) ) {
					$update_data[ "course_enrollment_{$course_id}" ] = gmdate( wpf_get_datetime_format(), ld_course_access_from( $course_id, $user_id ) );
				}

				if ( ! empty( ld_course_access_expires_on( $course_id, $user_id ) ) ) {
					$update_data[ "course_enrollment_expiry_{$course_id}" ] = gmdate( wpf_get_datetime_format(), ld_course_access_expires_on( $course_id, $user_id ) );
				}

				if ( isset( $course_progress['completed'] ) ) {
					$completed = absint( $course_progress['completed'] );
				}

				if ( isset( $course_progress['total'] ) ) {
					$total = absint( $course_progress['total'] );
				}

				if ( ( isset( $course_progress['status'] ) ) && ( 'completed' === $course_progress['status'] ) ) {
					$completed = $total;
				}

				if ( $total > 0 ) {
					$percentage = intval( $completed * 100 / $total );
					$percentage = ( $percentage > 100 ) ? 100 : $percentage;
				} else {
					$percentage = 0;
				}

				$update_data[ "course_progress_{$course_id}" ] = $percentage;

			}
		}

		wp_fusion()->user->push_user_meta( $user_id, $update_data );
	}

	/**
	 * Sync quiz results for a single user.
	 *
	 * @since 3.46.11
	 *
	 * @param int $user_id The user identifier.
	 */
	public function batch_step_quiz_results( $user_id ) {

		$update_data = array();

		global $wpdb;

		// Get all quiz activities for this user with their metadata.
		$quiz_activities = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT ua.post_id, ua.activity_id, uam.activity_meta_key, uam.activity_meta_value 
				 FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' ua
				 LEFT JOIN ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity_meta' ) ) . ' uam 
				 ON ua.activity_id = uam.activity_id 
				 WHERE ua.user_id=%d AND ua.activity_type=%s AND ua.activity_completed IS NOT NULL
				 AND uam.activity_meta_key IN (%s, %s, %s)
				 ORDER BY ua.activity_id DESC',
				$user_id,
				'quiz',
				'percentage',
				'points',
				'total_points'
			),
			ARRAY_A
		);

		if ( empty( $quiz_activities ) ) {
			return;
		}

		// Group results by quiz ID and activity ID.
		$quiz_data = array();
		foreach ( $quiz_activities as $row ) {
			$quiz_id     = $row['post_id'];
			$activity_id = $row['activity_id'];

			if ( ! isset( $quiz_data[ $quiz_id ] ) ) {
				$quiz_data[ $quiz_id ] = array();
			}
			if ( ! isset( $quiz_data[ $quiz_id ][ $activity_id ] ) ) {
				$quiz_data[ $quiz_id ][ $activity_id ] = array();
			}

			// Store the meta value based on what was queried.
			$quiz_data[ $quiz_id ][ $activity_id ][ $row['activity_meta_key'] ] = $row['activity_meta_value'];
		}

		foreach ( $quiz_data as $quiz_id => $activities ) {

			// Get the most recent activity for this quiz.
			$latest_activity = reset( $activities );

			// Extract quiz results data.
			$percentage = isset( $latest_activity['percentage'] ) ? floatval( $latest_activity['percentage'] ) : 0;
			$points     = isset( $latest_activity['total_points'] ) ? intval( $latest_activity['total_points'] ) : ( isset( $latest_activity['points'] ) ? intval( $latest_activity['points'] ) : 0 );

			// Final score.
			if ( wpf_is_field_active( "quiz_final_score_{$quiz_id}" ) ) {
				$update_data[ "quiz_final_score_{$quiz_id}" ] = $percentage;
			}

			// Final points.
			if ( wpf_is_field_active( "quiz_final_points_{$quiz_id}" ) ) {
				$update_data[ "quiz_final_points_{$quiz_id}" ] = $points;
			}

			// Note: Category scores would require additional meta_key queries
			// and are not included in this batch operation for performance reasons.

			// Legacy support for pre-3.41.35 field mapping.
			$settings = (array) get_post_meta( $quiz_id, 'wpf-settings', true );

			if ( ! empty( $settings['final_score_field'] ) && ! empty( $settings['final_score_field']['crm_field'] ) ) {
				$update_data[ $settings['final_score_field']['crm_field'] ] = $percentage;
			}

			if ( ! empty( $settings['final_points_field'] ) && ! empty( $settings['final_points_field']['crm_field'] ) ) {
				$update_data[ $settings['final_points_field']['crm_field'] ] = $points;
			}
		}

		// Only push data if we have quiz results to sync.
		if ( ! empty( $update_data ) ) {
			wpf_log( 'info', $user_id, 'Syncing LearnDash quiz results to ' . wp_fusion()->crm->name . ':', array( 'meta_array' => $update_data ) );
			wp_fusion()->user->push_user_meta( $user_id, $update_data );
		}
	}
}
