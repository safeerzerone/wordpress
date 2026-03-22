<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * LearnDash integration.
 *
 * @since 1.0.0
 *
 * @link https://wpfusion.com/documentation/learning-management/learndash/
 */
class WPF_LearnDash extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'learndash';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'LearnDash';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/learning-management/learndash/';

	/**
	 * Instance of WPF_LearnDash_Access_Control.
	 *
	 * @since 3.46.0
	 *
	 * @var WPF_LearnDash_Access_Control
	 */
	public $access_control;

	/**
	 * Instance of WPF_LearnDash_Courses.
	 *
	 * @since 3.46.0
	 *
	 * @var WPF_LearnDash_Courses
	 */
	public $courses;

	/**
	 * Instance of WPF_LearnDash_Topics.
	 *
	 * @since 3.46.0
	 *
	 * @var WPF_LearnDash_Groups
	 */
	public $groups;

	/**
	 * Instance of WPF_LearnDash_Lessons.
	 *
	 * @since 3.46.0
	 *
	 * @var WPF_LearnDash_Lessons
	 */
	public $lessons;

	/**
	 * Instance of WPF_LearnDash_Quizzes.
	 *
	 * @since 3.46.0
	 *
	 * @var WPF_LearnDash_Quizzes
	 */
	public $quizzes;

	/**
	 * Instance of WPF_LearnDash_Batch.
	 *
	 * @since 3.46.0
	 *
	 * @var WPF_LearnDash_Batch
	 */
	public $batch;

	/**
	 * Instance of WPF_LearnDash_Admin.
	 *
	 * @since 3.46.0
	 *
	 * @var WPF_LearnDash_Admin
	 */
	public $admin;

	/**
	 * Instance of WPF_LearnDash_Compatibility.
	 *
	 * @since 3.46.0
	 *
	 * @var WPF_LearnDash_Compatibility
	 */
	public $compatibility;

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */
	public function init() {

		// Load sub-modules.
		require_once __DIR__ . '/class-learndash-access-control.php';
		require_once __DIR__ . '/class-learndash-courses.php';
		require_once __DIR__ . '/class-learndash-groups.php';
		require_once __DIR__ . '/class-learndash-lessons.php';
		require_once __DIR__ . '/class-learndash-quizzes.php';
		require_once __DIR__ . '/class-learndash-batch.php';
		require_once __DIR__ . '/class-learndash-admin.php';
		require_once __DIR__ . '/class-learndash-compatibility.php';

		$this->access_control = new WPF_LearnDash_Access_Control();
		$this->courses        = new WPF_LearnDash_Courses();
		$this->groups         = new WPF_LearnDash_Groups();
		$this->lessons        = new WPF_LearnDash_Lessons();
		$this->quizzes        = new WPF_LearnDash_Quizzes();
		$this->batch          = new WPF_LearnDash_Batch();
		$this->admin          = new WPF_LearnDash_Admin();
		$this->compatibility  = new WPF_LearnDash_Compatibility();

		// Send auto-generated passwords on user registration.
		add_filter( 'random_password', array( $this, 'push_password' ) );
	}

	/**
	 * Magic method to handle calls to deprecated methods.
	 *
	 * @since  3.46.0
	 *
	 * @param  string $method     The method name.
	 * @param  array  $arguments  The method arguments.
	 * @return mixed  The result from the appropriate subclass method.
	 */
	public function __call( $method, $arguments ) {

		$method_map = array(
			// Access Control methods.
			'filter_content'              => array( 'access_control', 'filter_content' ),
			'lesson_attributes'           => array( 'access_control', 'lesson_attributes' ),
			'course_step_attributes'      => array( 'access_control', 'course_step_attributes' ),
			'nav_widget_lesson_class'     => array( 'access_control', 'nav_widget_lesson_class' ),

			// Course methods.
			'updated_course_access'       => array( 'courses', 'updated_course_access' ),
			'removed_course_access'       => array( 'courses', 'removed_course_access' ),
			'update_course_access'        => array( 'courses', 'update_course_access' ),
			'update_user_activity'        => array( 'courses', 'update_user_activity' ),

			// Group methods.
			'added_group_access'          => array( 'groups', 'added_group_access' ),
			'removed_group_access'        => array( 'groups', 'removed_group_access' ),
			'added_group_leader_access'   => array( 'groups', 'added_group_leader_access' ),
			'removed_group_leader_access' => array( 'groups', 'removed_group_leader_access' ),
			'update_group_access'         => array( 'groups', 'update_group_access' ),

			// Lesson methods.
			'lesson_completed'            => array( 'lessons', 'lesson_completed' ),
			'topic_completed'             => array( 'lessons', 'topic_completed' ),

			// Quiz methods.
			'quiz_completed'              => array( 'quizzes', 'quiz_completed' ),

			// Admin methods.
			'course_settings_fields'      => array( 'admin', 'course_settings_fields' ),
			'group_settings_fields'       => array( 'admin', 'group_settings_fields' ),
			'quiz_settings_fields'        => array( 'admin', 'quiz_settings_fields' ),
			'lesson_settings_fields'      => array( 'admin', 'lesson_settings_fields' ),
			'topic_settings_fields'       => array( 'admin', 'topic_settings_fields' ),
			'save_settings'               => array( 'admin', 'save_settings' ),
		);

		// Check if the method exists in our map.
		if ( isset( $method_map[ $method ] ) ) {
			$component  = $method_map[ $method ][0];
			$new_method = $method_map[ $method ][1];

			// Log a deprecation notice.
			$message = sprintf(
				/* translators: 1: The deprecated method name 2: The new method path */
				__( 'The method %1$s is deprecated. Use %2$s instead.', 'wp-fusion' ),
				'<strong>wp_fusion()->integrations->learndash->' . $method . '()</strong>',
				'<strong>wp_fusion()->integrations->learndash->' . $component . '->' . $new_method . '()</strong>'
			);

			// Only show deprecation notices to admins.
			if ( is_admin() && current_user_can( 'manage_options' ) ) {
				_deprecated_function( 'wp_fusion()->integrations->learndash->' . $method, '3.46.0', 'wp_fusion()->integrations->learndash->' . $component . '->' . $new_method );
			}

			// Log the deprecation.
			wpf_log( 'notice', 0, $message );

			// Call the method in the appropriate subclass.
			if ( isset( $this->$component ) && method_exists( $this->$component, $new_method ) ) {
				return call_user_func_array( array( $this->$component, $new_method ), $arguments );
			}
		}

		// Method not found in our map.
		$message = sprintf(
			/* translators: 1: The method name */
			__( 'The method %1$s does not exist in wp_fusion()->integrations->learndash or any of its subclasses.', 'wp-fusion' ),
			'<strong>' . $method . '</strong>'
		);

		wpf_log( 'error', 0, $message );

		return null;
	}

	/**
	 * Displays CRM field dropdowns on the custom settings panels.
	 *
	 * @since 3.38.16
	 *
	 * @param array $field_args The field arguments.
	 * @return mixed HTML Output.
	 */
	public function display_crm_field_dropdown( $field_args ) {

		wpf_render_crm_field_select(
			wpf_get_crm_field( $field_args['name'] ), // saved value.
			'wpf-settings-learndash', // option name.
			$field_args['name'] // field name.
		);

		echo '<p style="margin-top:5px;" class="description">' . esc_html( $field_args['desc'] ) . '</p>';
	}

	/**
	 * Display tags select input for on the custom settings panels.
	 *
	 * @access public
	 * @return mixed HTML output
	 */
	public function display_wpf_tags_select( $field_args ) {

		global $post;

		// pre 3.41.35, the quiz tags were stored in the generic wpf-settings, not wpf-settings-learndash.
		// pre 3.46.0, the course complete tags were stored in the generic wpf-settings, not wpf-settings-learndash.
		$old_settings = (array) get_post_meta( $post->ID, 'wpf-settings', true );
		$settings     = (array) get_post_meta( $post->ID, 'wpf-settings-learndash', true );
		$settings     = array_merge( $old_settings, $settings );

		if ( ! isset( $settings[ $field_args['name'] ] ) ) {
			$settings[ $field_args['name'] ] = array();
		}

		$args = array(
			'setting'   => $settings[ $field_args['name'] ],
			'meta_name' => 'wpf-settings-learndash',
			'field_id'  => $field_args['name'],
		);

		if ( isset( $field_args['limit'] ) ) {
			$args['limit'] = $field_args['limit'];
		}

		if ( 'apply_tags_enrolled' == $field_args['name'] ) {
			$args['no_dupes'] = array( 'wpf-settings-learndash-tag_link' );
		} elseif ( 'tag_link' == $field_args['name'] ) {
			$args['no_dupes'] = array( 'wpf-settings-learndash-apply_tags_enrolled' );
		}

		wpf_render_tag_multiselect( $args );

		echo '<p style="margin-top:5px;" class="description">' . $field_args['desc'] . '</p>';
	}


	/**
	 * Adds randomly generated passwords to POST data so it can be picked up by user_register().
	 *
	 * @access public
	 * @return string Password
	 */
	public function push_password( $password ) {

		if ( ! empty( $_POST ) ) {
			$_POST['user_pass'] = $password;
		}

		return $password;
	}
}

new WPF_LearnDash();
