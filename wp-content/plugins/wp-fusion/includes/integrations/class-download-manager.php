<?php

/**
 * WP Fusion - Download Manager integration.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.41.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Download Manager integration.
 *
 * @since 3.41.13
 */
class WPF_Download_Manager extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.41.13
	 * @var string $slug
	 */

	public $slug = 'download-manager';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.41.13
	 * @var string $name
	 */
	public $name = 'Download Manager';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.41.13
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/other/download-manager/';

	/**
	 * Gets things started.
	 *
	 * @since 3.41.13
	 */
	public function init() {

		add_filter( 'wpdm_before_download', array( $this, 'check_downloads' ) );
		add_filter( 'wpdm_before_render_pdf_viewer', array( $this, 'check_downloads' ) );

		// Filter shortcode output when hide_archives is enabled.
		add_filter( 'do_shortcode_tag', array( $this, 'filter_shortcode_output' ), 10, 4 );

		// Access metabox.
		add_filter( 'wpf_meta_box_post_types', array( $this, 'add_post_type' ) );
		add_filter( 'wpf_restrict_content_checkbox_label', array( $this, 'checkbox_label' ), 10, 2 );
	}

	/**
	 * Filter shortcode output when hide_archives is enabled.
	 *
	 * @since 3.46.5
	 *
	 * @param string $output  The shortcode output.
	 * @param string $tag     The shortcode tag.
	 * @param array  $attr    The shortcode attributes.
	 * @param array  $m       The entire shortcode match.
	 * @return string The filtered output.
	 */
	public function filter_shortcode_output( $output, $tag, $attr, $m ) {

		// Only filter specific download manager shortcodes.
		if ( ! in_array( $tag, array( 'wpdm_package', 'wpdm_direct_link' ), true ) ) {
			return $output;
		}

		// Only filter if hide_archives is enabled.
		if ( ! wpf_get_option( 'hide_archives' ) ) {
			return $output;
		}

		// Don't filter for admins.
		if ( wpf_admin_override() ) {
			return $output;
		}

		// Check if the post type is eligible for query filtering.
		if ( ! wp_fusion()->access->is_post_type_eligible_for_query_filtering( 'wpdmpro' ) ) {
			return $output;
		}

		// Get the download ID from shortcode attributes.
		$download_id = 0;
		if ( isset( $attr['id'] ) ) {
			$download_id = absint( $attr['id'] );
		} elseif ( is_singular( 'wpdmpro' ) ) {
			$download_id = get_the_ID();
		}

		// If no valid ID, return original output.
		if ( ! $download_id || 'wpdmpro' !== get_post_type( $download_id ) ) {
			return $output;
		}

		// Check if user has access to this download.
		if ( ! wpf_user_can_access( $download_id ) ) {
			return '';
		}

		return $output;
	}


	/**
	 * Check downloads based on CRM Tags.
	 *
	 * @since  3.41.13
	 *
	 * @param  array|int $download The requested download.
	 * @return bool   Can access.
	 */
	public function check_downloads( $download ) {

		if ( is_array( $download ) ) {
			$download_id = $download['ID'];
		} else {
			$download_id = $download;
		}

		if ( ! wpf_user_can_access( $download_id ) ) {
			$redirect = wp_fusion()->access->get_redirect( $download_id );
			if ( ! empty( $redirect ) ) {
				wp_fusion()->access->template_redirect( $download_id );
			} else {
				wp_die( wp_kses_post( wp_fusion()->access->get_restricted_content_message( $download_id ) ) );
			}
		}

		return $download;
	}


	/**
	 * Register the WPF access control meta box on the download post type.
	 *
	 * @since  3.41.13
	 *
	 * @param  array $post_types The post types to show the metabox on.
	 * @return array The post types.
	 */
	public function add_post_type( $post_types ) {

		$post_types[] = 'wpdmpro';

		return $post_types;
	}

	/**
	 * Filters the checkbox label in the WPF meta box when editing a download.
	 *
	 * @since  3.41.13
	 *
	 * @param  string  $message The message.
	 * @param  WP_Post $post    The post being edited in the admin.
	 * @return string  The message.
	 */
	public function checkbox_label( $message, $post ) {

		if ( 'wpdmpro' === $post->post_type ) {
			$message = __( 'Users must be logged in to download this file', 'wp-fusion' );
		}

		return $message;
	}
}
new WPF_Download_Manager();
