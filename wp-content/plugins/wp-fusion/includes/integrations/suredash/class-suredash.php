<?php
/**
 * WP Fusion - SureDash Integration
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.37.14
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles the integration with SureDash.
 *
 * @since 3.47.4
 */
class WPF_SureDash extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.47.4
	 *
	 * @var string $slug
	 */
	public $slug = 'suredash';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.47.4
	 *
	 * @var string $name
	 */
	public $name = 'SureDash';

	/**
	 * Gets things started.
	 *
	 * @since 3.47.4
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'suredashboard_post_meta_dataset', array( $this, 'add_additional_fields' ) );
		add_filter( 'suredashboard_post_meta_value', array( $this, 'add_redirect_label' ), 10, 3 );

		// Lesson metabox content.
		add_action( 'wpf_meta_box_content', array( $this, 'lesson_meta_box_content' ), 10, 2 );

		// Lesson completion.
		add_action( 'suredash_lesson_completed', array( $this, 'lesson_completed' ), 10, 3 );

		// Course completion.
		add_action( 'suredash_course_completed', array( $this, 'course_completed' ), 10, 2 );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 3.47.4
	 *
	 * @param string $hook The current admin page.
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on SureDash admin pages.
		if ( false === strpos( $hook, 'portal' ) ) {
			return;
		}

		$asset_meta = wpf_get_asset_meta( WPF_DIR_PATH . 'build/suredash-integration.asset.php' );

		// Add select2 to dependencies for the redirect page selector.
		$dependencies   = $asset_meta['dependencies'];
		$dependencies[] = 'select2';

		wp_enqueue_script(
			'wpf-suredash-integration',
			WPF_DIR_URL . 'build/suredash-integration.js',
			$dependencies,
			$asset_meta['version'],
			true
		);
	}

	/**
	 * Add additional fields to spaces.
	 *
	 * @since 3.47.4
	 *
	 * @param array $meta_fields The meta fields.
	 *
	 * @return array The meta fields.
	 */
	public function add_additional_fields( $meta_fields ) {
		$meta_fields['wpf_settings_suredash'] = array(
			'default' => array(
				'required_tags'       => array(),
				'redirect'            => '',
				'apply_tags_complete' => array(),
			),
			'type'    => 'array',
		);

		return $meta_fields;
	}

	/**
	 * Add a redirect label to SureDash settings for display.
	 *
	 * @since 3.47.4
	 *
	 * @param mixed  $meta_value The meta value.
	 * @param int    $post_id The post ID.
	 * @param string $meta_key The meta key.
	 *
	 * @return mixed The filtered meta value.
	 */
	public function add_redirect_label( $meta_value, $post_id, $meta_key ) {
		if ( 'wpf_settings_suredash' !== $meta_key || ! is_array( $meta_value ) ) {
			return $meta_value;
		}

		$redirect = $meta_value['redirect'] ?? '';

		if ( empty( $redirect ) ) {
			$meta_value['redirect_label'] = '';
			return $meta_value;
		}

		if ( 0 === strpos( $redirect, 'http' ) ) {
			$meta_value['redirect_label'] = $redirect;
			return $meta_value;
		}

		if ( is_numeric( $redirect ) ) {
			$redirect_id = absint( $redirect );
			if ( $redirect_id ) {
				$title = get_the_title( $redirect_id );
				if ( ! empty( $title ) ) {
					$meta_value['redirect_label'] = $title;
					return $meta_value;
				}
			}
		}

		$meta_value['redirect_label'] = $redirect;

		return $meta_value;
	}

	/**
	 * Add lesson metabox content.
	 *
	 * @since 3.47.4
	 *
	 * @param WP_Post $post     The post object.
	 * @param array   $settings The settings.
	 *
	 * @return void
	 */
	public function lesson_meta_box_content( $post, $settings ) {

		// Only show for SureDash lessons.
		if ( 'community-content' !== $post->post_type || 'lesson' !== get_post_meta( $post->ID, 'content_type', true ) ) {
			return;
		}

		echo '<p class="wpf-apply-tags-select"><label for="wpf-apply-tags-suredash-complete"><small>';
		esc_html_e( 'Apply these tags when marked complete:', 'wp-fusion' );
		echo '</small></label>';

		$args = array(
			'setting'   => $settings['apply_tags_suredash_complete'],
			'meta_name' => 'wpf-settings',
			'field_id'  => 'apply_tags_suredash_complete',
		);

		wpf_render_tag_multiselect( $args );

		echo '</p>';
	}

	/**
	 * Apply tags when a lesson is completed.
	 *
	 * @since 3.47.4
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $course_id The course ID.
	 * @param int $user_id   The user ID.
	 *
	 * @return void
	 */
	public function lesson_completed( $lesson_id, $course_id, $user_id ) {

		$settings = get_post_meta( $lesson_id, 'wpf-settings', true );

		if ( ! empty( $settings['apply_tags_suredash_complete'] ) ) {

			wpf_log( 'info', $user_id, 'User completed SureDash lesson <a href="' . admin_url( 'post.php?post=' . $lesson_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $lesson_id ) . '</a>. Applying tags.' );

			wp_fusion()->user->apply_tags( $settings['apply_tags_suredash_complete'], $user_id );
		}
	}

	/**
	 * Apply tags when a course is completed.
	 *
	 * @since 3.47.4
	 *
	 * @param int $course_id The course ID (space ID).
	 * @param int $user_id   The user ID.
	 *
	 * @return void
	 */
	public function course_completed( $course_id, $user_id ) {

		$settings = get_post_meta( $course_id, 'wpf_settings_suredash', true );

		if ( ! empty( $settings['apply_tags_complete'] ) ) {

			// Extract tag IDs from the tag objects.
			$tags = $this->extract_tag_ids( $settings['apply_tags_complete'] );

			if ( ! empty( $tags ) ) {
				wpf_log( 'info', $user_id, 'User completed SureDash course <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $course_id ) . '</a>. Applying tags.' );

				wp_fusion()->user->apply_tags( $tags, $user_id );
			}
		}
	}

	/**
	 * Extract tag IDs from tag data (handles both objects and strings).
	 *
	 * @since 3.47.4
	 *
	 * @param array $tags The tags (can be objects with 'value' key or strings).
	 *
	 * @return array The tag IDs.
	 */
	private function extract_tag_ids( $tags ) {
		if ( empty( $tags ) ) {
			return array();
		}

		$tag_ids = array();

		foreach ( $tags as $tag ) {
			if ( is_array( $tag ) && isset( $tag['value'] ) ) {
				// Tag is an object with value key.
				$tag_ids[] = $tag['value'];
			} elseif ( is_string( $tag ) ) {
				// Tag is already a string ID.
				$tag_ids[] = $tag;
			}
		}

		return $tag_ids;
	}
}

new WPF_SureDash();
