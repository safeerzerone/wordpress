<?php
/**
 * WP Fusion - LearnDash Admin
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
 * LearnDash Admin integration.
 *
 * Handles meta boxes, admin settings, and other admin-related functionality.
 *
 * @since 3.46.0
 */
class WPF_LearnDash_Admin {

	/**
	 * Gets things started.
	 *
	 * @since 3.46.0
	 */
	public function __construct() {

		// WPF settings fields.
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'add_meta_fields' ) );

		// Settings.
		add_action( 'add_meta_boxes', array( $this, 'configure_meta_box' ) );
		add_action( 'wpf_meta_box_content', array( $this, 'meta_box_content' ), 40, 2 );

		// Meta box notice about inherited access rules.
		add_action( 'wpf_meta_box_content', array( $this, 'meta_box_notice' ), 5, 2 );

		// Meta boxes.
		add_action( 'save_post', array( $this, 'save_meta_box_data' ), 20, 2 );

		add_action( 'load-post.php', array( $this, 'register_metabox' ), 1 );
		add_action( 'load-post-new.php', array( $this, 'register_metabox' ), 1 );
		add_filter( 'learndash_header_tab_menu', array( $this, 'add_metabox_tab' ) );

		// Admin course table.
		add_filter( 'display_post_states', array( $this, 'admin_table_post_states' ), 10, 2 );
	}


	/**
	 * Adds LearnDash field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['learndash_progress'] = array(
			'title' => __( 'LearnDash', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/learning-management/learndash/#syncing-course-progress',
		);

		return $field_groups;
	}


	/**
	 * Prepare Meta Fields.
	 *
	 * Adds LearnDash meta fields to WPF contact fields list.
	 *
	 * @param array $meta_fields Meta Fields.
	 *
	 * @return  array Meta Fields
	 */
	public function add_meta_fields( $meta_fields ) {

		$meta_fields['ld_last_group_enrolled'] = array(
			'label'  => 'Last Group Enrolled',
			'type'   => 'text',
			'group'  => 'learndash_progress',
			'pseudo' => true,
		);

		$meta_fields['ld_last_course_enrolled'] = array(
			'label'  => 'Last Course Enrolled',
			'type'   => 'text',
			'group'  => 'learndash_progress',
			'pseudo' => true,
		);

		$meta_fields['ld_last_lesson_completed'] = array(
			'label'  => 'Last Lesson Completed',
			'type'   => 'text',
			'group'  => 'learndash_progress',
			'pseudo' => true,
		);

		$meta_fields['ld_last_lesson_completed_date'] = array(
			'label'  => 'Last Lesson Completed Date',
			'type'   => 'date',
			'group'  => 'learndash_progress',
			'pseudo' => true,
		);

		$meta_fields['ld_last_topic_completed'] = array(
			'label'  => 'Last Topic Completed',
			'type'   => 'text',
			'group'  => 'learndash_progress',
			'pseudo' => true,
		);

		$meta_fields['ld_last_course_completed'] = array(
			'label'  => 'Last Course Completed',
			'type'   => 'text',
			'group'  => 'learndash_progress',
			'pseudo' => true,
		);

		$meta_fields['ld_last_course_completed_date'] = array(
			'label'  => 'Last Course Completed Date',
			'type'   => 'date',
			'group'  => 'learndash_progress',
			'pseudo' => true,
		);

		$meta_fields['ld_last_course_progressed'] = array(
			'label'  => 'Last Course Progressed',
			'type'   => 'text',
			'group'  => 'learndash_progress',
			'pseudo' => true,
		);

		// HonorsWP Student - Parent Access compatibility.

		if ( class_exists( 'Learndash_Access_For_Parents' ) ) {
			$meta_fields['ldap_parent_email'] = array(
				'label'  => 'Parent Email',
				'type'   => 'text',
				'group'  => 'learndash_progress',
				'pseudo' => true,
			);
		}

		// Course progress fields.

		$fields = array(
			'course_progress'          => array(
				'name' => __( 'Progress', 'wp-fusion' ),
				'type' => 'int',
			),
			'course_enrollment'        => array(
				'name' => __( 'Enrollment Date', 'wp-fusion' ),
				'type' => 'date',
			),
			'course_enrollment_expiry' => array(
				'name' => __( 'Enrollment Expiry Date', 'wp-fusion' ),
				'type' => 'date',
			),
			'quiz_final_score'         => array(
				'name' => __( 'Quiz Final Score', 'wp-fusion' ),
				'type' => 'int',
			),
			'quiz_final_points'        => array(
				'name' => __( 'Quiz Final Points', 'wp-fusion' ),
				'type' => 'int',
			),
			'quiz_category_score'      => array(
				'name' => __( 'Quiz Category Score', 'wp-fusion' ),
				'type' => 'int',
			),
		);

		$contact_fields = wpf_get_option( 'contact_fields', array() );

		foreach ( $contact_fields as $key => $value ) {

			foreach ( $fields as $crm_key => $crm_value ) {

				if ( false !== strpos( $key, $crm_key . '_' ) ) {

					$post_id             = str_replace( $crm_key . '_', '', $key );
					$meta_fields[ $key ] = array(
						'label'  => get_the_title( $post_id ) . ' - ' . $crm_value['name'],
						'type'   => $crm_value['type'],
						'pseudo' => true,
						'group'  => 'learndash_progress',
					);
				}
			}
		}

		return $meta_fields;
	}

	/**
	 * Remove standard "Apply to children" field from meta box
	 *
	 * @access public
	 * @return void
	 */
	public function configure_meta_box() {

		global $post;

		if ( empty( $post ) ) {
			return;
		}

		if ( $post->post_type == 'sfwd-lessons' || $post->post_type == 'sfwd-topic' ) {
			remove_action( 'wpf_meta_box_content', 'apply_to_children', 35 );
		}
	}


	/**
	 * Adds LearnDash fields to WPF meta box
	 *
	 * @access public
	 * @return void
	 */
	public function meta_box_content( $post, $settings ) {

		if ( 'sfwd-lessons' === $post->post_type || 'sfwd-topic' === $post->post_type ) {

			$defaults = array(
				'apply_tags_ld' => array(),
			);

			$settings = array_merge( $defaults, $settings );

			echo '<p><label for="wpf-apply-tags-ld"><small>';

			esc_html_e( 'Apply these tags when marked complete:', 'wp-fusion' );

			echo '</small></label>';

			wpf_render_tag_multiselect(
				array(
					'setting'   => $settings['apply_tags_ld'],
					'meta_name' => 'wpf-settings',
					'field_id'  => 'apply_tags_ld',
				)
			);

			echo '</p>';
		}
	}

	/**
	 * Adds notice about inherited rules
	 *
	 * @access public
	 * @return void
	 */
	public function meta_box_notice( $post, $settings ) {

		if ( 'sfwd-lessons' != $post->post_type && 'sfwd-topic' != $post->post_type ) {
			return;
		}

		$parent_settings = false;

		$lesson_id = learndash_get_lesson_id( $post->ID );

		if ( ! empty( $lesson_id ) && $lesson_id !== $post->ID ) {
			$parent_settings = get_post_meta( $lesson_id, 'wpf-settings', true );
		}

		if ( empty( $parent_settings ) || empty( $parent_settings['lock_content'] ) ) {

			// Maybe try the course

			$course_id       = learndash_get_course_id( $post->ID );
			$parent_settings = get_post_meta( $course_id, 'wpf-settings', true );

		}

		if ( ! empty( $parent_settings ) && ! empty( $parent_settings['lock_content'] ) ) {

			$post_type_object = get_post_type_object( $post->post_type );

			echo '<div class="wpf-metabox-notice">';

			if ( isset( $course_id ) ) {
				printf( __( 'If no access rules are specified here, this %1$s will inherit permissions from the course %2$s.', 'wp-fusion' ), strtolower( $post_type_object->labels->singular_name ), '<a href="' . get_edit_post_link( $course_id ) . '">' . get_the_title( $course_id ) . '</a>' );
			} elseif ( $lesson_id !== $post->ID ) {
				printf( __( 'If no access rules are specified here, this %1$s will inherit permissions from the lesson %2$s.', 'wp-fusion' ), strtolower( $post_type_object->labels->singular_name ), '<a href="' . get_edit_post_link( $lesson_id ) . '">' . get_the_title( $lesson_id ) . '</a>' );
			}

			$required_tags = array();

			if ( ! empty( $parent_settings['allow_tags'] ) ) {
				$required_tags = array_merge( $required_tags, $parent_settings['allow_tags'] );
			}

			if ( ! empty( $parent_settings['allow_tags_all'] ) ) {
				$required_tags = array_merge( $required_tags, $parent_settings['allow_tags_all'] );
			}

			if ( ! empty( $required_tags ) ) {

				$required_tags = array_map( array( wp_fusion()->user, 'get_tag_label' ), $required_tags );

				echo '<span class="notice-required-tags">' . sprintf( __( '(Required tag(s): %s)', 'wp-fusion' ), implode( ', ', $required_tags ) ) . '</span>';
			}

			echo '</div>';

		}
	}


	/**
	 * Save Meta Box Data
	 * Runs when WPF meta box is saved on a course, quiz, lesson, or question.
	 *
	 * @since 3.41.35
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post Post object.
	 */
	public function save_meta_box_data( $post_id, $post ) {

		if ( ! in_array( $post->post_type, array( 'sfwd-courses', 'sfwd-quiz', 'groups', 'sfwd-question', 'sfwd-topic', 'sfwd-lessons' ) ) ) {
			return;
		}

		// As of LD 3.2.2 this runs on every lesson in the builder when the course is saved, so we'll check for that here to avoid having the lesson settings overwritten by the course.
		if ( isset( $_POST['post_ID'] ) && $_POST['post_ID'] != $post_id ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$data = array();

		if ( ! empty( $_POST['wpf-settings-learndash'] ) ) {
			$data = $_POST['wpf-settings-learndash'];
		}

		// Some special fields.
		if ( ! empty( $_POST['learndash-course-wpf'] ) ) {

			if ( isset( $_POST['learndash-course-wpf']['step_display'] ) ) {
				$data['step_display'] = $_POST['learndash-course-wpf']['step_display'];
				unset( $_POST['learndash-course-wpf']['step_display'] );
			}

			if ( isset( $_POST['learndash-course-wpf']['lesson_locked_text'] ) ) {
				$data['lesson_locked_text'] = sanitize_text_field( $_POST['learndash-course-wpf']['lesson_locked_text'] );
				unset( $_POST['learndash-course-wpf']['lesson_locked_text'] );
			}

			if ( ! empty( $_POST['learndash-course-wpf']['remove_tags'] ) ) {
				$data['remove_tags'] = true;
			}

			if ( ! empty( $_POST['learndash-course-wpf']['tag_link_unenroll'] ) ) {
				$data['tag_link_unenroll'] = true;
			} else {
				$data['tag_link_unenroll'] = false;
			}
		}

		// Groups.
		if ( ! empty( $_POST['learndash-group-access-settings'] ) && ! empty( $_POST['learndash-group-access-settings']['remove_tags'] ) ) {
			$data['remove_tags'] = true;
		}

		if ( array_key_exists( 'wpf-settings', $_POST ) ) {
			if ( ! empty( $_POST['wpf-settings'] && ! empty( $_POST['wpf-settings']['apply_delay'] ) ) ) {
				$data['apply_delay'] = $_POST['wpf-settings']['apply_delay'];
				unset( $_POST['wpf-settings']['apply_delay'] );
			}
		}

		if ( ! empty( $data ) ) {

			$data = WPF_Admin_Interfaces::sanitize_tags_settings( $data );
			update_post_meta( $post_id, 'wpf-settings-learndash', $data );

			// Copy custom fields to main contact fields.

			$contact_fields = wpf_get_option( 'contact_fields', array() );

			// Course and quiz progress fields.

			$fields = array( "quiz_final_score_{$post_id}", "quiz_final_points_{$post_id}", "course_progress_{$post_id}", "course_enrollment_{$post_id}", "course_enrollment_expiry_{$post_id}" );

			foreach ( $fields as $field ) {

				if ( isset( $data[ $field ] ) && ! empty( $data[ $field ]['crm_field'] ) ) {

					// Also copy to the main settings.
					$contact_fields[ $field ]['crm_field'] = $data[ $field ]['crm_field'];
					$contact_fields[ $field ]['active']    = true;

					if ( false !== strpos( $field, 'enrollment' ) ) {
						$contact_fields[ $field ]['type'] = 'date';
					} else {
						$contact_fields[ $field ]['type'] = 'int';
					}
				} elseif ( isset( $contact_fields[ $field ] ) ) {

					unset( $contact_fields[ $field ] );

				}
			}

			// Quiz category scores. We need to use the Learndash Category Mapper here to get
			// the categories.

			$category_mapper = new WpProQuiz_Model_CategoryMapper();
			$quiz_id         = (int) learndash_get_setting( $post_id, 'quiz_pro' );
			$categories      = $category_mapper->fetchByQuiz( $quiz_id );

			$field = 'category_score_field_';

			foreach ( $categories as $category ) {

				$category_id = $category->getCategoryId();

				// i.e. quiz_category_score_123_1.
				$key = "quiz_category_score_{$post_id}_{$category_id}";

				if ( ! empty( $data[ $key ]['crm_field'] ) ) {

					// Also copy to the main settings.
					$contact_fields[ $key ]['crm_field'] = $data[ $key ]['crm_field'];
					$contact_fields[ $key ]['active']    = true;

				} elseif ( isset( $contact_fields[ $key ] ) ) {

					unset( $contact_fields[ $key ] );

				}
			}

			wp_fusion()->settings->set( 'contact_fields', $contact_fields );

		} elseif ( empty( $data ) && isset( $_POST['action'] ) && 'editpost' === $_POST['action'] ) {
			delete_post_meta( $post_id, 'wpf-settings-learndash' );
		}
	}

	/**
	 * Require LearnDash custom settings classes.
	 *
	 * @since 3.38.28
	 */
	public function register_metabox() {
		require_once __DIR__ . '/class-learndash-metabox-course-settings.php';
		require_once __DIR__ . '/class-learndash-metabox-quiz-settings.php';
	}

	/**
	 * Add metabox tab to LD course and quiz admin views.
	 *
	 * @since  3.38.28
	 *
	 * @param  array $tabs   The tabs.
	 * @return array Tabs
	 */
	public function add_metabox_tab( $tabs ) {
		$screen = get_current_screen();

		if ( 'sfwd-courses' !== $screen->id && 'sfwd-quiz' !== $screen->id ) {
			return $tabs;
		}
		if ( 'sfwd-courses' === $screen->id ) {
			if ( ( isset( $_GET['post'] ) && isset( $_GET['action'] ) ) || isset( $_GET['post_type'] ) && $_GET['post_type'] === 'sfwd-courses' ) {
				$tabs[] = array(
					'id'                  => 'wp-fusion-settings',
					'name'                => __( 'WP Fusion', 'wp-fusion' ),
					'metaboxes'           => array( 'learndash-course-wpf' ),
					'showDocumentSidebar' => 'false',
				);
			}
		}
		if ( 'sfwd-quiz' === $screen->id ) {
			if ( ( isset( $_GET['post'] ) && isset( $_GET['action'] ) ) || isset( $_GET['post_type'] ) && $_GET['post_type'] === 'sfwd-quiz' ) {
				$tabs[] = array(
					'id'                  => 'wp-fusion-settings',
					'name'                => __( 'WP Fusion', 'wp-fusion' ),
					'metaboxes'           => array( 'learndash-quiz-wpf' ),
					'showDocumentSidebar' => 'false',
				);
			}
		}
		return $tabs;
	}


	/**
	 * Show post access controls in the posts table
	 *
	 * @access public
	 * @return array Post States
	 */
	public function admin_table_post_states( $post_states, $post ) {

		if ( ! is_object( $post ) ) {
			return $post_states;
		}

		if ( 'sfwd-courses' != $post->post_type && 'groups' != $post->post_type ) {
			return $post_states;
		}

		$wpf_settings = get_post_meta( $post->ID, 'wpf-settings-learndash', true );

		if ( ! empty( $wpf_settings ) && ! empty( $wpf_settings['tag_link'] ) ) {

			$post_type_object = get_post_type_object( $post->post_type );

			$content = sprintf( __( 'This %1$s is linked for auto-enrollment with %2$s tag: ', 'wp-fusion' ), strtolower( $post_type_object->labels->singular_name ), wp_fusion()->crm->name );

			$content .= '<strong>' . wpf_get_tag_label( $wpf_settings['tag_link'][0] ) . '</strong>';

			$classes = 'dashicons dashicons-admin-links wpf-tip wpf-tip-bottom';

			if ( ! empty( array_diff( $wpf_settings['tag_link'], array_keys( wpf_get_option( 'available_tags', array() ) ) ) ) ) {
				$classes .= ' error';
			}

			$post_states['wpf_learndash'] = '<span class="' . $classes . '" data-tip="' . $content . '"></span>';

		}

		return $post_states;
	}
}
