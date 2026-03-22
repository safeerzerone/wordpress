<?php
/**
 * WP Fusion - LearnDash Access Control
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
 * LearnDash Access Control integration.
 *
 * Handles access control for LearnDash.
 *
 * @since 3.46.0
 */
class WPF_LearnDash_Access_Control {

	/**
	 * Contains any course sections modified by query filtering or Filter Course
	 * Steps.
	 *
	 * @since 3.38.39
	 * @var  array The sections.
	 */
	public $filter_course_sections = array();

	/**
	 * Helps track if we need to output extra CSS to disable clicks on locked lessons.
	 *
	 * @since 3.41.17
	 * @var  bool Whether or not we're locking lessons.
	 */
	public $locking_lessons = false;

	/**
	 * Gets things started.
	 *
	 * @since 3.46.0
	 */
	public function __construct() {

		if ( wpf_get_option( 'restrict_content', true ) ) {

			add_filter( 'learndash_access_redirect', array( $this, 'lesson_access_redirect' ), 10, 2 );

			// Content filtering.
			add_filter( 'learndash_content', array( $this, 'content_filter' ), 10, 2 );
			add_filter( 'learndash_lesson_row_class', array( $this, 'lesson_row_class' ), 10, 2 );
			add_filter( 'learndash-topic-row-class', array( $this, 'lesson_row_class' ), 10, 2 );
			add_filter( 'learndash-nav-widget-lesson-class', array( $this, 'lesson_row_class' ), 10, 2 );
			add_filter( 'learndash_lesson_attributes', array( $this, 'lesson_attributes' ), 10, 2 ); // Pre LD 4.2.0.
			add_filter( 'learndash_course_step_attributes', array( $this, 'course_step_attributes' ), 10, 4 ); // LD 4.2.0+.
			add_action( 'wp_print_footer_scripts', array( $this, 'add_inline_lesson_locked_styles' ) );

			// Filter Course Steps.
			add_filter( 'get_post_metadata', array( $this, 'filter_course_steps' ), 10, 4 );
			add_filter( 'update_post_metadata', array( $this, 'block_course_steps_save_for_non_admins' ), 10, 3 );
			add_filter( 'sfwd_lms_has_access', array( $this, 'has_access' ), 10, 3 );
			add_filter( 'learndash_can_user_read_step', array( $this, 'can_user_read_step' ), 10, 3 );

			add_filter( 'wpf_post_access_meta', array( $this, 'inherit_permissions_from_course' ), 10, 2 );
			add_filter( 'wpf_configure_settings', array( $this, 'configure_settings' ), 10, 2 );

		}
	}


	/**
	 * Run WPF's redirects on restricted LD lessons instead of letting LD take them to the course, so our login redirects work
	 *
	 * @since 3.46.0
	 *
	 * @param string $link      The redirect link.
	 * @param int    $lesson_id The lesson ID.
	 * @return string Redirect Link
	 */
	public function lesson_access_redirect( $link, $lesson_id ) {

		$course_id = learndash_get_course_id( $lesson_id );

		if ( ! wpf_user_can_access( $course_id ) ) {

			// Courses.

			$redirect = wp_fusion()->access->get_redirect( $course_id );

			if ( ! empty( $redirect ) ) {

				wp_fusion()->access->set_return_after_login( $lesson_id );

				wp_redirect( $redirect, 302, 'WP Fusion; Post ID ' . $lesson_id );
				exit();

			}
		} elseif ( ! wpf_user_can_access( $lesson_id ) ) {

			// Lessons.

			$redirect = wp_fusion()->access->get_redirect( $lesson_id );

			if ( ! empty( $redirect ) ) {

				wp_fusion()->access->set_return_after_login( $lesson_id );

				wp_redirect( $redirect, 302, 'WP Fusion; Post ID ' . $lesson_id );
				exit();

			}
		}

		return $link;
	}

	/**
	 * Class SFWD_CPT_Instance hooks into the_content filter to rebuild the
	 * course steps cache when a user views a course. If the cache is being
	 * rebuilt while WPF is filtering the content, this can cause course steps
	 * to be deleted. This bypasses the pre-processing while WPF is filtering
	 * the content.
	 *
	 * @since  3.38.11
	 *
	 * @param  int $post_id The post ID.
	 */
	public function filtering_page_content( $post_id ) {

		add_filter( 'learndash_template_preprocess_filter', '__return_false' );
	}

	/**
	 * Hide LD content if user doesn't have access
	 *
	 * @since 3.46.0
	 *
	 * @param string  $content The post content.
	 * @param WP_Post $post    The post object.
	 * @return mixed Content
	 */
	public function content_filter( $content, $post ) {

		if ( ! wp_fusion()->access->user_can_access( $post->ID ) ) {
			$content = wp_fusion()->access->get_restricted_content_message();
		}

		return $content;
	}

	/**
	 * If query filtering is enabled, hide course steps from the course
	 * navigation.
	 *
	 * @since  3.36.16
	 *
	 * @param  bool $can_read  Can the user read the step?.
	 * @param  int  $step_id   The step ID.
	 * @param  int  $course_id The course ID.
	 * @return bool  True if user able to read step, False otherwise.
	 */
	public function can_user_read_step( $can_read, $step_id, $course_id ) {

		if ( ! $can_read ) {
			return $can_read;
		}

		if ( learndash_is_course_shared_steps_enabled() ) {
			return $can_read; // This is redundant with shared course steps and lessons since it's already filtered out the disabled steps on get_post_metadata.
		}

		if ( is_admin() || wpf_admin_override() || wp_doing_cron() ) {
			return $can_read; // This can mess with the Builder if it's allowed to run in the admin.
		}

		if ( wpf_get_option( 'hide_archives' ) ) {

			// If query filtering is on.

			$post_types = wpf_get_option( 'query_filter_post_types', array() );

			if ( empty( $post_types ) || in_array( get_post_type( $step_id ), $post_types, true ) ) {

				if ( ! wpf_user_can_access( $step_id ) ) {
					$can_read = false;
				}
			}
		} else {

			// If Filter Course Steps is on.

			$settings = get_post_meta( $course_id, 'wpf-settings-learndash', true );

			if ( ! empty( $settings ) ) {

				if ( ! empty( $settings['step_display'] ) && 'filter_steps' === $settings['step_display'] && ! wpf_user_can_access( $step_id ) ) {
					$can_read = false;
				}
			}
		}

		if ( false === $can_read ) {

			// Reduce the course step count.

			add_filter(
				'learndash-course-progress-stats',
				function ( $progress ) {

					if ( is_array( $progress ) && ! empty( $progress['total'] ) ) {
						$progress['total'] -= 1;
					}

					return $progress;
				}
			);

		}

		return $can_read;
	}


	/**
	 * If query filtering is enabled, hide course steps from the course
	 * navigation.
	 *
	 * @since  3.38.11
	 *
	 * @param  null|array $value    The postmeta value.
	 * @param  int        $post_id  The post ID.
	 * @param  string     $meta_key The meta key.
	 * @param  bool       $single   Whether to return a single value or array.
	 * @return null|array The modified course steps array.
	 */
	public function filter_course_steps( $value, $post_id, $meta_key, $single ) {

		if ( 'ld_course_steps' === $meta_key ) {
			// Prevent infinite loop.
			remove_filter( 'get_post_metadata', array( $this, 'filter_course_steps' ), 10, 4 );
		}

		if ( 'ld_course_steps' === $meta_key && learndash_is_course_shared_steps_enabled() ) { // only works with shared course steps.

			if ( ( is_admin() && ! wp_doing_ajax() ) || wpf_admin_override() || wp_doing_cron() ) {
				return $value; // Don't need to do anything.
			}

			$should_filter = false;

			// If Filter Course Steps is on.

			$settings = get_post_meta( $post_id, 'wpf-settings-learndash', true );

			if ( ! empty( $settings ) && isset( $settings['step_display'] ) && 'filter_steps' === $settings['step_display'] ) {
				$should_filter = true;
			}

			// If query filtering is on.

			if ( false === $should_filter && wpf_get_option( 'hide_archives' ) ) {

				$post_types = wpf_get_option( 'query_filter_post_types', array() );

				if ( empty( $post_types ) || in_array( get_post_type( $post_id ), $post_types, true ) ) {
					$should_filter = true;
				}
			}

			if ( true === $should_filter ) {

				// Get the current value (up until this point $value is null).
				$value = get_post_meta( $post_id, 'ld_course_steps', true );

				if ( ! empty( $value ) ) {

					$sections = get_post_meta( $post_id, 'course_sections', true );

					if ( ! empty( $sections ) ) {
						$sections = json_decode( $sections, true );
					}

					// Remove any content the user can't access.

					foreach ( $value['steps']['h'] as $post_type => $posts ) {

						// Track the current step in the course navigation so we can adjust
						// the "order" on any sections if needed.
						$step_id = 0;

						foreach ( $posts as $post_id => $sub_posts ) {

							if ( ! empty( $sections ) ) {

								foreach ( $sections as $i => $section ) {
									if ( $section['order'] === $step_id ) {
										++$step_id; // Increment the step ID each time we pass a section heading.
									}
								}
							}

							if ( ! wpf_user_can_access( $post_id ) ) {

								unset( $value['steps']['h'][ $post_type ][ $post_id ] ); // remove the restricted lesson.

								--$value['steps_count']; // This makes the progress bar calculate correctly.

								// If it's a lesson, removing it could potentially affect the
								// section positions, so we'll account for that here.

								if ( ! empty( $sections ) ) {

									foreach ( $sections as $i => $section ) {

										if ( $section['order'] >= $step_id ) {

											// Sections are stored with an order relative to their position in the course.
											--$sections[ $i ]['order'];

										}
									}
								}

								continue;
							}

							// Maybe deal with topics and quizzes inside of lessons.

							foreach ( $sub_posts as $sub_post_type => $sub_posts_of_type ) {

								foreach ( $sub_posts_of_type as $sub_post_id => $sub_post_of_type ) {

									if ( ! wpf_user_can_access( $sub_post_id ) ) {
										unset( $value['steps']['h'][ $post_type ][ $post_id ][ $sub_post_type ][ $sub_post_id ] );
										--$value['steps_count'];
									}
								}
							}

							++$step_id;

						}
					}

					if ( $single && is_array( $value ) ) {
						$value = array( $value ); // get_metadata_raw will return $value[0] if $single is true and the response from the filter is non-null.
					}
				}

				if ( ! empty( $sections ) ) {

					$this->filter_course_sections = $sections;

				}
			}
		} elseif ( 'course_sections' === $meta_key && ! empty( $this->filter_course_sections ) ) {

			$value = wp_json_encode( $this->filter_course_sections );

			if ( $single ) {
				$value = array( $value ); // get_metadata_raw will return $value[0] if $single is true and the response from the filter is non-null.
			}
		}

		if ( 'ld_course_steps' === $meta_key ) {
			// Add it back.
			add_filter( 'get_post_metadata', array( $this, 'filter_course_steps' ), 10, 4 );
		}

		return $value;
	}


	/**
	 * This prevents a course steps rebuild triggered on the frontend from messing up the
	 * data saved in the database.
	 *
	 * @since 3.41.8
	 *
	 * @param bool   $check     Check.
	 * @param int    $object_id The post ID.
	 * @param string $meta_key  The meta key.
	 */
	public function block_course_steps_save_for_non_admins( $check, $object_id, $meta_key ) {

		if ( 'ld_course_steps' === $meta_key && ( ! current_user_can( 'edit_courses' ) || ! is_admin() ) ) {
			return false;
		}

		return $check;
	}

	/**
	 * This works to hide the lessons in focus mode with the BuddyBoss theme
	 * when Filter Course Steps is on.
	 *
	 * @since  3.38.22
	 *
	 * @param  bool $has_access Indicates if the user has access.
	 * @param  int  $post_id    The course ID.
	 * @param  int  $user_id    The user ID.
	 * @return bool  Whether or not the user can access the lesson.
	 */
	public function has_access( $has_access, $post_id, $user_id ) {

		if ( ! $has_access ) {
			return $has_access; // Already denied.
		}

		if ( doing_filter( 'wpf_user_can_access' ) ) {
			return $has_access; // Prevent looping.
		}

		$post_type = get_post_type( $post_id );
		if ( 'sfwd-courses' === $post_type ) {
			return $has_access; // Only filter lessons and topics.
		}

		$course_id = learndash_get_course_id( $post_id );
		$settings  = get_post_meta( $course_id, 'wpf-settings-learndash', true );

		// Also check course-specific settings if they exist.
		if ( ! empty( $settings ) && isset( $settings['step_display'] ) ) {

			if ( 'filter_steps' === $settings['step_display'] ) {

				if ( ! wpf_user_can_access( $post_id, $user_id ) ) {
					$has_access = false;
				}
			} elseif ( 'lock_lessons' === $settings['step_display'] && ! defined( 'REST_REQUEST' ) ) {

				// BuddyBoss theme, focus mode.

				if ( ! wpf_user_can_access( $post_id, $user_id ) ) {
					$has_access = false;
				}
			}
		}

		return $has_access;
	}


	/**
	 * If lock lessons is enabled, disable clicking on the lesson or topic.
	 *
	 * Works with BuddyBoss theme in regular course overveiew, but not focus mode.
	 *
	 * Works with built in LD theme in focus mode and regular mode.
	 *
	 * @since  3.37.4
	 *
	 * @param  string       $class  The row classes.
	 * @param  array|object $item   The item, either the lesson or topic.
	 * @return string       The row classes.
	 */
	public function lesson_row_class( $class, $item = false ) {

		if ( false === $item ) {
			return $class;  // At the moment learndash-nav-widget-lesson-class is hooked to this
							// function but we're waiting for an update from LD to pass the
							// second parameter.
		}

		if ( is_a( $item, 'WP_Post' ) ) {
			$id = $item->ID; // Topics
		} else {
			$id = ( isset( $item['id'] ) ? $item['id'] : 0 ); // Lessons.
		}

		$course_id = learndash_get_course_id( $id );

		$settings = get_post_meta( $course_id, 'wpf-settings-learndash', true );

		if ( empty( $settings ) ) {
			return $class;
		}

		if ( ! empty( $settings['step_display'] ) && 'lock_lessons' === $settings['step_display'] ) {
			if ( ! wpf_user_can_access( $id ) && empty( wp_fusion()->access->get_post_access_meta( $id )['redirect'] ) ) {
				$class                .= ' wp-fusion-locked';
				$this->locking_lessons = true;
			}
		}

		return $class;
	}


	/**
	 * Add restricted content attributes to restricted lessons.
	 *
	 * Does not run with standard LD theme (or standard focus mode).
	 *
	 * Only for LD pre 4.2.0.
	 *
	 * @since      3.37.4
	 * @deprecated 3.40.23
	 *
	 * @param array $attributes The attributes.
	 * @param array $lesson     The lesson.
	 * @return array The attributes.
	 */
	public function lesson_attributes( $attributes, $lesson ) {

		if ( is_a( $lesson, 'WP_Post' ) ) {
			$lesson_id = $lesson->ID;
		} else {
			$lesson_id = ( isset( $lesson['id'] ) ? $lesson['id'] : 0 );
		}

		$course_id = learndash_get_course_id( $lesson_id );

		$attributes = $this->course_step_attributes( $attributes, $lesson_id, $course_id, wpf_get_current_user_id() );

		return $attributes;
	}

	/**
	 * Add restricted content attributes to restricted lessons and topics.
	 *
	 * Works with standard LD theme in regular and focus mode. Works with BuddyBoss only in regular.
	 *
	 * @since  3.40.23
	 *
	 * @param  array $attributes The attributes.
	 * @param  int   $step_id    The lesson or topic ID.
	 * @param  int   $course_id  The course ID.
	 * @param  int   $user_id    The user ID.
	 * @return array The attributes.
	 */
	public function course_step_attributes( $attributes, $step_id, $course_id, $user_id ) {

		$settings = get_post_meta( $course_id, 'wpf-settings-learndash', true );

		if ( empty( $settings ) ) {
			return $attributes;
		}

		if ( ! empty( $settings['step_display'] ) && 'lock_lessons' === $settings['step_display'] ) {

			if ( ! wpf_user_can_access( $step_id, $user_id ) ) {

				$attribute = array(
					'label' => ! empty( $settings['lesson_locked_text'] ) ? $settings['lesson_locked_text'] : wpf_get_option( 'ld_default_lesson_locked_text' ),
					'icon'  => 'ld-icon-unlocked',
					'class' => 'ld-status-locked ld-primary-color wp-fusion-locked',
				);

				if ( function_exists( 'buddyboss_theme_get_option' ) ) {
					$attribute['icon'] = 'ld-icon-calendar';
				}

				// Classes can be ld-status-complete, ld-status-waiting, ld-status-unlocked, ld-status-incomplete
				// Icons can be any of the ld-icon-* classes, for example ld-icon-calendar.

				$attributes[]          = apply_filters( 'wpf_learndash_lesson_locked_attributes', $attribute, $step_id );
				$this->locking_lessons = true;

			}
		}

		return $attributes;
	}

	/**
	 * Outputs inline styles to prevent clicking on locked lessons.
	 *
	 * @since 3.41.17
	 */
	public function add_inline_lesson_locked_styles() {

		if ( $this->locking_lessons ) {

			echo '<style> /* Added by WP Fusion, to prevent clicking on locked lessons. */';
			echo '.learndash-wrapper .ld-table-list .ld-table-list-items .ld-table-list-item a.wp-fusion-locked { pointer-events: none; cursor: default; }';
			echo '.learndash-wrapper .ld-table-list .ld-table-list-items .ld-table-list-item.wp-fusion-locked a { pointer-events: none; cursor: default; }';
			echo '.bb-learndash-content-wrap .ld-table-list .ld-table-list-items .ld-table-list-item a.wp-fusion-locked .ld-topic-title::before { content: "\eecd"; }';
			echo '.bb-learndash-content-wrap .ld-status-locked .ld-icon-unlocked::before { content: "\eecd"; font-family: "bb-icons"; } ';
			echo '.learndash-wrapper .ld-item-list-items .ld-item-list-item.wp-fusion-locked a { pointer-events: none; cursor: default; }';
			echo '.learndash-wrapper .ld-item-list .ld-item-list-item.wp-fusion-locked a.ld-item-name:hover, .learndash-wrapper .ld-item-list .ld-item-list-item.wp-fusion-locked .ld-item-list-item-preview:hover a.ld-item-name .ld-item-title { color: inherit; }';
			// BuddyBoss theme focus mode:
			echo '.lms-lesson-content a.bb-title.bb-lms-title-wrap:has( i.bb-icon-lock ) { pointer-events: none; cursor: default; opacity: 0.6; }';
			echo '</style>';

		}
	}


	/**
	 * LearnDash lessons and topics should inherit permissions from the parent course
	 *
	 * @since 3.46.0
	 *
	 * @param array $access_meta The access meta settings.
	 * @param int   $post_id     The post ID.
	 * @return array Access Meta
	 */
	public function inherit_permissions_from_course( $access_meta, $post_id ) {

		if ( empty( $access_meta ) || ( empty( $access_meta['lock_content'] ) && empty( $access_meta['allow_tags_not'] ) ) ) {

			$post_type = get_post_type( $post_id );

			if ( 'sfwd-lessons' === $post_type || 'sfwd-topic' === $post_type || 'sfwd-quiz' === $post_type ) {

				$parent_settings = false;

				// Inherit the settings from the parent lesson, for quizzes and topics.

				if ( 'sfwd-lessons' !== $post_type ) {

					$lesson_id = learndash_get_lesson_id( $post_id );

					if ( ! empty( $lesson_id ) ) {
						$parent_settings = get_post_meta( $lesson_id, 'wpf-settings', true );
					}
				}

				if ( empty( $parent_settings ) || ( empty( $parent_settings['lock_content'] ) && empty( $parent_settings['allow_tags_not'] ) ) ) {

					// Maybe try the course.

					$course_id       = learndash_get_course_id( $post_id );
					$parent_settings = get_post_meta( $course_id, 'wpf-settings', true );

				}

				if ( ! empty( $parent_settings ) ) {
					$access_meta = $parent_settings;
				}
			}
		}

		return $access_meta;
	}

	/**
	 * Register the LearnDash global settings.
	 *
	 * @since 3.40.46
	 *
	 * @param array $settings Settings.
	 * @return array Settings.
	 */
	public function configure_settings( $settings ) {

		$settings['learndash_header'] = array(
			'title'   => __( 'LearnDash Integration', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['ld_default_lesson_locked_text'] = array(
			'title'   => __( 'Default Lesson Locked Text', 'wp-fusion' ),
			'desc'    => __( 'The default message to show for unavailable content when using the Lock Lessons course setting.', 'wp-fusion' ),
			'std'     => __( 'Not Available', 'wp-fusion' ),
			'type'    => 'text',
			'section' => 'integrations',
		);

		return $settings;
	}
}
