<?php
/**
 * WP Fusion - FluentCommunity Integration
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
 * FluentCommunity integration.
 *
 * @since 3.44.20
 * @link https://wpfusion.com/documentation/membership/fluent-community/
 */
class WPF_FluentCommunity extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.44.20
	 * @var string $slug
	 */
	public $slug = 'fluent-community';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.44.20
	 * @var string $name
	 */
	public $name = 'FluentCommunity';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.44.20
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/membership/fluent-community/';

	/**
	 * Access control instance.
	 *
	 * @since 3.44.20
	 * @var   WPF_FluentCommunity_Access
	 */
	public $access;

	/**
	 * Spaces management instance.
	 *
	 * @since 3.44.20
	 * @var   WPF_FluentCommunity_Spaces
	 */
	public $spaces;

	/**
	 * Courses management instance.
	 *
	 * @since 3.44.20
	 * @var   WPF_FluentCommunity_Courses
	 */
	public $courses;

	/**
	 * Admin interface instance.
	 *
	 * @since 3.44.20
	 * @var   WPF_FluentCommunity_Admin
	 */
	public $admin;

	/**
	 * Meta API instance.
	 *
	 * @since 3.46.3
	 * @var   WPF_FluentCommunity_Meta
	 */
	public $meta;

	/**
	 * Initializes the integration from the base class.
	 *
	 * @since 3.44.20
	 */
	public function init() {

		// Include sub-component files.
		$this->include_files();

		// Initialize sub-components.
		$this->access  = new WPF_FluentCommunity_Access( $this );
		$this->spaces  = new WPF_FluentCommunity_Spaces( $this );
		$this->courses = new WPF_FluentCommunity_Courses( $this );
		$this->admin   = new WPF_FluentCommunity_Admin( $this );
		$this->meta    = new WPF_FluentCommunity_Meta( $this );

		// Main integration hooks.
		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );
		add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'add_meta_fields' ) );
	}

	/**
	 * Include required files.
	 *
	 * @since 3.44.20
	 */
	private function include_files() {
		require_once WPF_DIR_PATH . 'includes/integrations/fluent-community/class-fluent-community-access.php';
		require_once WPF_DIR_PATH . 'includes/integrations/fluent-community/class-fluent-community-spaces.php';
		require_once WPF_DIR_PATH . 'includes/integrations/fluent-community/class-fluent-community-courses.php';
		require_once WPF_DIR_PATH . 'includes/integrations/fluent-community/class-fluent-community-admin.php';
		require_once WPF_DIR_PATH . 'includes/integrations/fluent-community/class-fluent-community-meta.php';
	}

	/**
	 * Handles tag updates from the CRM and syncs course enrollments and space memberships.
	 *
	 * @since  3.44.20
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user's tags.
	 */
	public function tags_modified( $user_id, $user_tags ) {
		$this->courses->sync_course_tags( $user_id, $user_tags );
		$this->spaces->sync_space_tags( $user_id, $user_tags );
	}

	/**
	 * Handles registration data before it's sent to the CRM.
	 *
	 * @since  3.44.20
	 *
	 * @param  array $post_data The registration form data.
	 * @param  int   $user_id   The user ID.
	 * @return array The update data.
	 */
	public function user_register( $post_data, $user_id ) {

		if ( ! isset( $post_data['action'] ) || 'fcom_user_registration' !== $post_data['action'] ) {
			return $post_data;
		}

		if ( ! empty( $post_data['full_name'] ) ) {

			$parts = explode( ' ', $post_data['full_name'] );

			if ( count( $parts ) > 1 ) {
				$last_name  = array_pop( $parts );
				$first_name = implode( ' ', $parts );
			} else {
				$first_name = $post_data['full_name'];
				$last_name  = '';
			}

			$post_data['first_name'] = $first_name;
			$post_data['last_name']  = $last_name;
		}

		return $post_data;
	}

	/**
	 * Gets a setting.
	 *
	 * @since  3.44.20
	 *
	 * @param  string $setting The setting key.
	 * @param  mixed  $default The default value.
	 * @return mixed The setting value.
	 */
	public function get_setting( $setting, $default = false ) {
		$settings = get_option( 'wpf_fluent_community_options', array() );
		return isset( $settings[ $setting ] ) ? $settings[ $setting ] : $default;
	}

	/**
	 * Saves settings.
	 *
	 * @since  3.44.20
	 */
	public function save_settings() {

		if ( ! isset( $_POST['wpf_fluent_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpf_fluent_settings_nonce'] ) ), 'wpf_fluent_settings' ) ) {
			return;
		}

		$settings = array();

		if ( ! empty( $_POST['wpf-settings'] ) ) {
			$settings = wpf_clean( wp_unslash( $_POST['wpf-settings'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// Clean any tag fields.
			foreach ( $settings as $key => $value ) {
				if ( strpos( $key, '_tags' ) !== false || strpos( $key, '_tag_link' ) !== false ) {
					$settings[ $key ] = wpf_clean_tags( $value );
				}
			}
		}

		update_option( 'wpf_fluent_community_options', $settings, false );
	}

	/**
	 * Add meta field group.
	 *
	 * @since  3.44.20
	 *
	 * @param  array $field_groups The field groups.
	 * @return array The field groups.
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['fluent_community'] = array(
			'title' => __( 'FluentCommunity', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/membership/fluentcommunity/',
		);

		return $field_groups;
	}

	/**
	 * Prepare meta fields.
	 *
	 * @since  3.44.20
	 *
	 * @param  array $meta_fields The meta fields.
	 * @return array The meta fields.
	 */
	public function add_meta_fields( $meta_fields ) {

		$meta_fields['fc_last_space_joined'] = array(
			'label'  => 'Last Space Joined',
			'type'   => 'text',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_space_joined_date'] = array(
			'label'  => 'Last Space Joined Date',
			'type'   => 'date',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_course_enrolled'] = array(
			'label'  => 'Last Course Enrolled',
			'type'   => 'text',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_course_enrolled_date'] = array(
			'label'  => 'Last Course Enrolled Date',
			'type'   => 'date',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_course_completed'] = array(
			'label'  => 'Last Course Completed',
			'type'   => 'text',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_course_completed_date'] = array(
			'label'  => 'Last Course Completed Date',
			'type'   => 'date',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_lesson_completed'] = array(
			'label'  => 'Last Lesson Completed',
			'type'   => 'text',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_lesson_completed_date'] = array(
			'label'  => 'Last Lesson Completed Date',
			'type'   => 'date',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		return $meta_fields;
	}
}

new WPF_FluentCommunity();
