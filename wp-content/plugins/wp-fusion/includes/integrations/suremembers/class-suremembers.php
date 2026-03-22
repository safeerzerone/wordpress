<?php
/**
 * Holds the Integration for SureMembers.
 *
 * @package WP_Fusion
 */

use WP_Fusion\Includes\Admin\WPF_Tags_Select_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles the integration with SureMembers.
 *
 * @since 3.41.23
 */
class WPF_SureMembers extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.41.23
	 *
	 * @var string $slug
	 */

	public $slug = 'suremembers';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.41.23
	 *
	 * @var string $name
	 */
	public $name = 'SureMembers';

	/**
	 * SureMembers third party section key.
	 *
	 * @since 3.47.8.1
	 *
	 * @var string
	 */
	private $section_id = 'wpf_suremembers_settings';

	/**
	 * SureMembers third party component name.
	 *
	 * @since 3.47.8.1
	 *
	 * @var string
	 */
	private $component_name = 'WPFusionSureMembersSettings';

	/**
	 * WP Fusion SureMembers settings meta key.
	 *
	 * @since 3.47.8.1
	 *
	 * @var string
	 */
	private $settings_meta_key = 'wpf_suremembers_settings';

	/**
	 * Gets things started.
	 *
	 * @since 3.41.23
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );
		add_action( 'suremembers_after_access_grant', array( $this, 'add_user_tags' ), 10, 2 );
		add_action( 'suremembers_after_access_revoke', array( $this, 'remove_user_tags' ), 10, 2 );
		add_action( 'suremembers_after_submit_form', array( $this, 'save_meta_box_data' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'suremembers_third_party_sections', array( $this, 'register_third_party_section' ) );
		add_filter( 'suremembers_get_membership_data', array( $this, 'inject_membership_data' ), 10, 2 );
	}

	/**
	 * Enqueue Assets.
	 *
	 * @since 3.41.23
	 * @since 3.41.44 Fixed PHP errors, PHPCS errors, and array to string conversion.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->is_suremembers_admin_page() ) {
			return;
		}

		$dependencies = array( 'wp-element', 'wp-hooks', 'wp-i18n', 'wpf-admin' );

		if ( wp_script_is( 'suremembers_posts', 'registered' ) || wp_script_is( 'suremembers_posts', 'enqueued' ) ) {
			$dependencies[] = 'suremembers_posts';
		}

		wp_enqueue_script(
			'wpf-suremembers-integration',
			WPF_DIR_URL . 'build/suremembers-integration.js',
			$dependencies,
			WP_FUSION_VERSION,
			true
		);

		$post_id  = $this->get_current_membership_id();
		$settings = $post_id ? $this->get_access_group_settings( $post_id ) : array();

		$args = array(
			'nonce'             => wp_create_nonce( 'wpf_meta_box_suremembers' ),
			'apply_tags'        => WPF_Tags_Select_API::format_tags_to_props( $settings['apply_tags'] ?? array() ),
			'tag_link'          => WPF_Tags_Select_API::format_tags_to_props( $settings['tag_link'] ?? array() ),
			'apply_tags_string' => sprintf(
				// translators: %s is the name of the CRM.
				__( 'Apply the selected tags in %s when a user is added to this access group.', 'wp-fusion' ),
				wp_fusion()->crm->name,
			),
			'tag_link_string'   => sprintf(
				// translators: %s is the name of the CRM.
				__( 'Select a tag to link with this access group. When the tag is applied in %s, the user will be enrolled. When the tag is removed, the user will be unenrolled.', 'wp-fusion' ),
				wp_fusion()->crm->name
			),
			'section_id'        => $this->section_id,
			'component_name'    => $this->component_name,
			'post_id'           => absint( $post_id ),
		);

		wp_localize_script( 'wpf-suremembers-integration', 'wpf_suremembers', $args );
	}

	/**
	 * Register SureMembers third-party settings section.
	 *
	 * @since 3.47.8.1
	 *
	 * @param array<string, mixed> $sections Existing sections.
	 * @return array<string, mixed> Updated sections.
	 */
	public function register_third_party_section( $sections ) {
		$sections[ $this->section_id ] = array(
			'title'     => __( 'WP Fusion', 'wp-fusion' ),
			'icon'      => 'Zap',
			'priority'  => 50,
			'component' => $this->component_name,
		);

		return $sections;
	}

	/**
	 * Inject saved data into SureMembers membership data payload.
	 *
	 * SureMembers calls this filter when loading membership data for
	 * the editor, allowing us to populate our component's sectionData.
	 *
	 * @since 3.47.8.1
	 *
	 * @param array<string, mixed> $data Membership data.
	 * @param int                  $post_id Membership post ID.
	 * @return array<string, mixed> Updated membership data.
	 */
	public function inject_membership_data( $data, $post_id ) {
		$settings = $this->get_access_group_settings( $post_id );

		if ( ! isset( $data['third_party_data'] ) || ! is_array( $data['third_party_data'] ) ) {
			$data['third_party_data'] = array();
		}

		$data['third_party_data'][ $this->section_id ] = array(
			'apply_tags' => WPF_Tags_Select_API::format_tags_to_props( $settings['apply_tags'] ),
			'tag_link'   => WPF_Tags_Select_API::format_tags_to_props( $settings['tag_link'] ),
		);

		return $data;
	}

	/**
	 * Adds tags to the user when they are added to a group.
	 *
	 * @since 3.41.23
	 * @since 3.41.44 Fixed issue where tags were not being added to the user.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $access_group_ids The ID of the access group(s) that is being granted.
	 *
	 * @return void
	 */
	public function add_user_tags( $user_id, array $access_group_ids = array() ) {
		// @phpstan-ignore-next-line - We are removing the action using priority and arguments for safety.
		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		$user_tags = wpf_get_tags( $user_id );

		foreach ( $access_group_ids as $group_id ) {
			$settings = $this->get_access_group_settings( $group_id );

			if ( empty( $settings ) ) {
				continue;
			}

			$group_url   = $this->get_access_group_admin_url( $group_id );
			$group_title = get_the_title( $group_id );

			if ( ! empty( $settings['tag_link'] ) ) {
				$tag_link        = $settings['tag_link'];
				$user_can_access = get_user_meta( $user_id, 'suremembers_user_access_group_' . $group_id, true );
				$user_status     = is_array( $user_can_access ) ? ( $user_can_access['status'] ?? '' ) : '';
				$tag_label       = wpf_get_tag_label( $tag_link[0] );

				if (
					! array_intersect( $tag_link, $user_tags ) &&
					'active' === $user_status &&
					! user_can( $user_id, 'manage_options' )
				) {
					wpf_log(
						'info',
						$user_id,
						"User added to access group <a href=\"$group_url\">$group_title</a>. Applying linked tag <strong>$tag_label</strong>."
					);

					wp_fusion()->user->apply_tags( $tag_link, $user_id );
				}
			}

			if ( ! empty( $settings['apply_tags'] ) ) {
				wpf_log(
					'info',
					$user_id,
					"User added to access group <a href=\"$group_url\">$group_title</a>. Applying tags."
				);

				wp_fusion()->user->apply_tags( $settings['apply_tags'], $user_id );
			}
		}

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );
	}

	/**
	 * Removes the link tag when a user is removed from a group.
	 *
	 * @since 3.41.23
	 * @since 3.41.44 Cleaned up code, removed unnecessary checks & fixed tags not being applied.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $access_group_ids The ID of the access group(s) that is being revoked.
	 *
	 * @return void
	 */
	public function remove_user_tags( $user_id, array $access_group_ids = array() ) {
		// @phpstan-ignore-next-line - We are removing the action using priority and arguments for safety.
		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		$user_tags = wpf_get_tags( $user_id );

		foreach ( $access_group_ids as $group_id ) {
			$settings = $this->get_access_group_settings( $group_id );

			if ( empty( $settings ) || empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_link        = $settings['tag_link'];
			$user_can_access = get_user_meta( $user_id, 'suremembers_user_access_group_' . $group_id, true );
			$user_status     = is_array( $user_can_access ) ? ( $user_can_access['status'] ?? '' ) : '';

			if (
				array_intersect( $tag_link, $user_tags ) &&
				'revoked' === $user_status &&
				! user_can( $user_id, 'manage_options' )
			) {
				$group_url   = $this->get_access_group_admin_url( $group_id );
				$group_title = get_the_title( $group_id );
				$tag_label   = wpf_get_tag_label( $tag_link[0] );

				wpf_log(
					'info',
					$user_id,
					"User removed from access group <a href=\"$group_url\">$group_title</a>. Removing link tag <strong>$tag_label</strong>."
				);

				wp_fusion()->user->remove_tags( $tag_link, $user_id );
			}
		}

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );
	}

	/**
	 * Updates user's access groups if a tag linked to a SureMembers access group is changed.
	 *
	 * @since 3.41.23
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user tags.
	 *
	 * @return void
	 */
	public function tags_modified( $user_id, array $user_tags = array() ) {
		$access_groups      = SureMembers\Inc\Access_Groups::get_active();
		$user_access_groups = (array) get_user_meta( $user_id, 'suremembers_user_access_group', true );

		foreach ( $access_groups as $group_id => $group ) {
			$settings = $this->get_access_group_settings( $group_id );

			if ( empty( $settings ) || empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_id          = $settings['tag_link'][0];
			$user_can_access = get_user_meta( $user_id, 'suremembers_user_access_group_' . $group_id, true );
			$user_status     = is_array( $user_can_access ) ? ( $user_can_access['status'] ?? '' ) : '';
			$tag_label       = wpf_get_tag_label( $tag_id );
			$group_url       = $this->get_access_group_admin_url( $group_id );

			// The type of $tag_id is unknown so we can't use strict.
			if (
				// phpcs:ignore
				( in_array( $tag_id, $user_tags ) && empty( $user_access_groups ) ) ||
				// phpcs:ignore
				( in_array( $tag_id, $user_tags ) && ! in_array( $group_id, $user_access_groups ) ) ||
				// phpcs:ignore
				( in_array( $tag_id, $user_tags ) && 'revoked' === $user_status )
			) {
				wpf_log(
					'info',
					$user_id,
					"Linked tag <strong>$tag_label</strong> applied to user. Adding user to access group <a href=\"$group_url\">$group</a>."
				);
				SureMembers\Inc\Access::grant( $user_id, $group_id, 'wp-fusion' );
			} elseif (
				// phpcs:ignore
				! in_array( $tag_id, $user_tags ) &&
				// phpcs:ignore
				in_array( $group_id, $user_access_groups ) &&
				'active' === $user_status
			) {
				wpf_log(
					'info',
					$user_id,
					"Linked tag <strong>$tag_label</strong> removed from user. Removing user from access group <a href=\"$group_url\">$group</a>."
				);
				SureMembers\Inc\Access::revoke( $user_id, $group_id );
			}
		}
	}

	/**
	 * Saves WP Fusion settings when SureMembers saves an access group.
	 *
	 * @since 3.41.23
	 * @since 3.41.44 Cleaned up code.
	 * @since 3.47.8.1 Refactored for SureMembers v2 third-party API.
	 *
	 * @param int                  $access_group The access group ID.
	 * @param array<string, mixed> $post_data Raw POST data from SureMembers.
	 *
	 * @return void
	 */
	public function save_meta_box_data( $access_group, $post_data = array() ) {
		if ( empty( $access_group ) ) {
			return;
		}

		$wpf_data = null;

		// SureMembers v2.0.6+ third-party API: data comes via post_data.
		if (
			isset( $post_data['third_party_data'][ $this->section_id ] ) &&
			is_array( $post_data['third_party_data'][ $this->section_id ] )
		) {
			$wpf_data = $post_data['third_party_data'][ $this->section_id ];
		}

		// Legacy fallback: data comes via $_POST from old form-based UI.
		if ( null === $wpf_data && $this->is_valid_save_request() ) {
			$wpf_data = array();

			if ( isset( $_POST['wp_fusion']['apply_tags'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$wpf_data['apply_tags'] = sanitize_text_field( wp_unslash( $_POST['wp_fusion']['apply_tags'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['wp_fusion']['tag_link'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$wpf_data['tag_link'] = sanitize_text_field( wp_unslash( $_POST['wp_fusion']['tag_link'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}

		if ( empty( $wpf_data ) ) {
			return;
		}

		$settings = array(
			'apply_tags' => $this->sanitize_tag_ids( $wpf_data['apply_tags'] ?? array() ),
			'tag_link'   => $this->sanitize_tag_ids( $wpf_data['tag_link'] ?? array(), true ),
		);

		$this->save_access_group_settings( $access_group, $settings );
	}

	/**
	 * Determine whether current request is any SureMembers admin page.
	 *
	 * We enqueue on all SM pages because SureMembers uses client-side
	 * routing; the script must be available before the user navigates
	 * to the editor without a full page reload.
	 *
	 * @since 3.47.8.1
	 *
	 * @return bool True if this is a SureMembers admin page.
	 */
	private function is_suremembers_admin_page() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 'suremembers' === $page || 'suremembers_rules' === $page;
	}

	/**
	 * Get currently edited membership ID from request.
	 *
	 * @since 3.47.8.1
	 *
	 * @return int Membership ID.
	 */
	private function get_current_membership_id() {
		if ( isset( $_GET['membership-id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return absint( wp_unslash( $_GET['membership-id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		if ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return absint( wp_unslash( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		if ( isset( $_GET['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return absint( wp_unslash( $_GET['post_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		return 0;
	}

	/**
	 * Validate legacy save request from posted form values.
	 *
	 * @since 3.47.8.1
	 *
	 * @return bool True if request is valid.
	 */
	private function is_valid_save_request() {
		if ( ! isset( $_POST['wpf_meta_box_suremembers_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['wpf_meta_box_suremembers_nonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! wp_verify_nonce( $nonce, 'wpf_meta_box_suremembers' ) ) {
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		return true;
	}

	/**
	 * Get normalized settings for an access group.
	 *
	 * @since 3.47.8.1
	 *
	 * @param int $group_id Access group ID.
	 * @return array<string, array<int, string>> Normalized settings.
	 */
	private function get_access_group_settings( $group_id ) {
		$settings = array(
			'apply_tags' => array(),
			'tag_link'   => array(),
		);

		$stored = get_post_meta( $group_id, $this->settings_meta_key, true );

		if ( is_array( $stored ) ) {
			$settings['apply_tags'] = $this->sanitize_tag_ids( $stored['apply_tags'] ?? array() );
			$settings['tag_link']   = $this->sanitize_tag_ids( $stored['tag_link'] ?? array(), true );
		}

		$legacy = get_post_meta( $group_id, 'suremembers_plan_rules', true );

		if ( is_array( $legacy ) ) {
			if ( empty( $settings['apply_tags'] ) && ! empty( $legacy['apply_tags'] ) ) {
				$settings['apply_tags'] = $this->sanitize_tag_ids( $legacy['apply_tags'] );
			}

			if ( empty( $settings['tag_link'] ) && ! empty( $legacy['tag_link'] ) ) {
				$settings['tag_link'] = $this->sanitize_tag_ids( $legacy['tag_link'], true );
			}
		}

		return $settings;
	}

	/**
	 * Save settings to new and legacy storage keys.
	 *
	 * @since 3.47.8.1
	 *
	 * @param int                               $group_id Access group ID.
	 * @param array<string, array<int, string>> $settings Settings payload.
	 * @return void
	 */
	private function save_access_group_settings( $group_id, array $settings ) {
		$settings['apply_tags'] = $this->sanitize_tag_ids( $settings['apply_tags'] ?? array() );
		$settings['tag_link']   = $this->sanitize_tag_ids( $settings['tag_link'] ?? array(), true );

		update_post_meta( $group_id, $this->settings_meta_key, $settings );

		$legacy = get_post_meta( $group_id, 'suremembers_plan_rules', true );
		$legacy = is_array( $legacy ) ? $legacy : array();

		$legacy['apply_tags'] = $settings['apply_tags'];
		$legacy['tag_link']   = $settings['tag_link'];

		update_post_meta( $group_id, 'suremembers_plan_rules', $legacy );
	}

	/**
	 * Sanitize tags from arrays or comma-separated strings.
	 *
	 * @since 3.47.8.1
	 *
	 * @param mixed $value Raw tag value.
	 * @param bool  $single Whether only one tag should remain.
	 * @return array<int, string> Sanitized tag IDs.
	 */
	private function sanitize_tag_ids( $value, $single = false ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', sanitize_text_field( $value ) );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $value as $item ) {
			if ( is_array( $item ) && isset( $item['value'] ) ) {
				$item = $item['value'];
			}

			$tag = sanitize_text_field( (string) $item );

			if ( '' === $tag ) {
				continue;
			}

			$sanitized[] = $tag;
		}

		$sanitized = array_values( array_unique( $sanitized ) );

		if ( $single ) {
			return array_slice( $sanitized, 0, 1 );
		}

		return $sanitized;
	}

	/**
	 * Get access group admin URL.
	 *
	 * @since 3.47.8.1
	 *
	 * @param int $group_id Access group ID.
	 * @return string Access group URL.
	 */
	private function get_access_group_admin_url( $group_id ) {
		if ( defined( 'SUREMEMBERS_VER' ) && version_compare( (string) constant( 'SUREMEMBERS_VER' ), '2.0.0', '>=' ) ) {
			return admin_url( 'admin.php?page=suremembers&membership-id=' . absint( $group_id ) );
		}

		return admin_url( 'edit.php?post_type=wsm_access_group&page=suremembers_rules&post_id=' . absint( $group_id ) );
	}
}

new WPF_SureMembers();
