<?php
/**
 * WP Fusion - LearnDash Groups
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
 * LearnDash Groups integration.
 *
 * Handles group enrollment, group leader management, and auto-enrollment via tags.
 *
 * @since 3.46.0
 */
class WPF_LearnDash_Groups {

	/**
	 * Gets things started.
	 *
	 * @since 3.46.0
	 */
	public function __construct() {

		// Group linking.
		add_filter( 'learndash_settings_fields', array( $this, 'group_settings_fields' ), 10, 2 );
		add_action( 'ld_added_group_access', array( $this, 'added_group_access' ), 10, 2 );
		add_action( 'ld_removed_group_access', array( $this, 'removed_group_access' ), 10, 2 );

		// Group Leader linking.
		add_action( 'ld_added_leader_group_access', array( $this, 'added_group_leader_access' ), 10, 2 );
		add_action( 'ld_removed_leader_group_access', array( $this, 'removed_group_leader_access' ), 10, 2 );

		// Auto enrollments.
		add_action( 'wpf_tags_modified', array( $this, 'update_group_access' ), 9, 2 ); // This is 9 so that the user is in the correct groups by the time we go to update their courses.
	}

	/**
	 * Registers LD group fields.
	 *
	 * @since 3.46.0
	 *
	 * @param array  $fields      The fields.
	 * @param string $metabox_key The metabox key.
	 * @return array The fields.
	 */
	public function group_settings_fields( $fields, $metabox_key ) {

		if ( 'learndash-group-access-settings' == $metabox_key ) {

			if ( wpf_get_option( 'admin_permissions' ) && ! current_user_can( 'manage_options' ) ) {
				return $fields;
			}

			$settings = array(
				'remove_tags' => false,
			);

			global $post;

			if ( is_object( $post ) ) {
				$settings = wp_parse_args( get_post_meta( $post->ID, 'wpf-settings-learndash', true ), $settings );
			}

			$new_options = array(
				'apply_tags_enrolled' => array(
					'name'             => 'apply_tags_enrolled',
					'label'            => __( 'Apply Tags - Enrolled', 'wp-fusion' ),
					'type'             => 'multiselect',
					'multiple'         => 'true',
					'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
					'desc'             => sprintf( __( 'These tags will be applied in %s when someone is enrolled in this group.', 'wp-fusion' ), wp_fusion()->crm->name ),
					'help_text'        => sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/learning-management/learndash/#groups" target="_blank">', '</a>' ),
				),
				'remove_tags'         => array(
					'name'      => 'remove_tags',
					'value'     => $settings['remove_tags'],
					'label'     => __( 'Remove Tags', 'wp-fusion' ),
					'type'      => 'checkbox-switch',
					'default'   => '',
					'options'   => array(
						'on' => '',
					),
					'help_text' => sprintf( __( 'Remove the enrolled tags from the user when they leave the group.', 'wp-fusion' ), wp_fusion()->crm->name ),
				),
				'tag_link'            => array(
					'name'             => 'tag_link',
					'label'            => __( 'Link with Tag', 'wp-fusion' ),
					'type'             => 'multiselect',
					'multiple'         => 'true',
					'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
					'desc'             => sprintf( __( 'This tag will be applied in %1$s when a user is enrolled, and will be removed when a user is unenrolled. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this group. If this tag is removed, the user will be removed from the group.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ),
					'limit'            => 1,
					'help_text'        => sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/learning-management/learndash/#groups" target="_blank">', '</a>' ),
				),
				'leader_tag'          => array(
					'name'             => 'leader_tag',
					'label'            => __( 'Link with Tag - Group Leader', 'wp-fusion' ),
					'type'             => 'multiselect',
					'multiple'         => 'true',
					'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
					'desc'             => sprintf( __( 'This tag will be applied in %1$s when a group leader is assigned, and will be removed when a group leader is removed. Likewise, if this tag is applied to a user from within %2$s, they will be automatically assigned as the leader of this group. If this tag is removed, the user will be removed from leadership of the group.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ),
					'limit'            => 1,
					'help_text'        => sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/learning-management/learndash/#groups" target="_blank">', '</a>' ),
				),
			);

			// Warning if LD - Woo plugin is active.

			if ( class_exists( 'Learndash_WooCommerce' ) ) {

				$new_options['tag_link']['desc'] .= '<br /><br/><div class="ld-settings-info-banner ld-settings-info-banner-alert"><p>';
				$new_options['tag_link']['desc'] .= __( '<strong>Warning:</strong> The <strong>LearnDash - WooCommerce</strong> plugin is active. If access to this group is managed by that plugin, you should <em>not</em> use the Link With Tag setting, as it will cause your students to become unenrolled from the course when their renewal payments are processed.', 'wp-fusion' );
				$new_options['tag_link']['desc'] .= '</p></div>';

			}

			$fields = $fields + $new_options;

		}

		return $fields;
	}

	/**
	 * Applies group link tag when user added to group.
	 *
	 * @since 3.46.0
	 *
	 * @param int $user_id  The user ID.
	 * @param int $group_id The group ID.
	 */
	public function added_group_access( $user_id, $group_id ) {

		$defaults = array(
			'tag_link'            => array(),
			'apply_tags_enrolled' => array(),
		);

		$settings = wp_parse_args( get_post_meta( $group_id, 'wpf-settings-learndash', true ), $defaults );

		$apply_tags = array_merge( $settings['tag_link'], $settings['apply_tags_enrolled'] );

		if ( empty( $apply_tags ) && ! wpf_is_field_active( 'ld_last_group_enrolled' ) ) {
			return;
		}

		if ( doing_action( 'user_register' ) && ! wpf_get_contact_id( $user_id ) ) {

			// The Uncanny Toolkit Pro plugin has an option to register a user and add them to a group in one step.
			// @link https://www.uncannyowl.com/knowledge-base/group-sign-up/, either via a native form or a Gravity
			// Form embedded in the course.

			// It runs on user_register priority 10, so tags configured for the group can't be applied at this stage
			// since the user doesn't yet have a CRM contact record. We'll force WPF's user_register action to run
			// early to make sure the user can be tagged.

			remove_filter( 'wpf_user_register', array( wp_fusion()->integrations->{'gravity-forms'}, 'maybe_bypass_user_register' ) );
			remove_action( 'gform_user_registered', array( wp_fusion()->integrations->{'gravity-forms'}, 'user_registered' ), 20, 4 );

			add_action(
				'gform_user_registered',
				function ( $user_id ) {
					wp_fusion()->user->push_user_meta( $user_id );
				},
				20
			);

			// ^ this is a mess and I hate it but at the moment it's just for Cesar at
			// https://secure.helpscout.net/conversation/1947596250/22299?folderId=726355 so we'll put up with it.

			wp_fusion()->user->user_register( $user_id );

			// This already happened so don't need to do it again.
			remove_action( 'user_register', array( wp_fusion()->user, 'user_register' ), 20 );

		}

		// Sync the last course enrolled name.

		$update_data = array(
			'ld_last_group_enrolled' => get_post_field( 'post_title', $group_id, 'raw' ),
		);

		wp_fusion()->user->push_user_meta( $user_id, $update_data );

		if ( ! empty( $apply_tags ) ) {

			// Prevent looping.
			remove_action( 'wpf_tags_modified', array( $this, 'update_group_access' ), 10, 2 );

			wpf_log( 'info', $user_id, 'User was enrolled in LearnDash group <a href="' . admin_url( 'post.php?post=' . $group_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $group_id ) . '</a>. Applying tags.' );

			wp_fusion()->user->apply_tags( $apply_tags, $user_id );

			add_action( 'wpf_tags_modified', array( $this, 'update_group_access' ), 10, 2 );
		}
	}

	/**
	 * Removes group link tag when user removed from group.
	 *
	 * @since 3.46.0
	 *
	 * @param int $user_id  The user ID.
	 * @param int $group_id The group ID.
	 */
	public function removed_group_access( $user_id, $group_id ) {

		$settings = get_post_meta( $group_id, 'wpf-settings-learndash', true );

		if ( empty( $settings ) ) {
			return;
		}

		$remove_tags = ! empty( $settings['tag_link'] ) ? $settings['tag_link'] : array();

		if ( ! empty( $settings['remove_tags'] ) && ! empty( $settings['apply_tags_enrolled'] ) ) {
			$remove_tags = array_merge( $remove_tags, $settings['apply_tags_enrolled'] );
		}

		if ( $remove_tags ) {

			// Prevent looping.
			remove_action( 'wpf_tags_modified', array( $this, 'update_group_access' ), 10, 2 );

			wpf_log( 'info', $user_id, 'User was un-enrolled from LearnDash group <a href="' . admin_url( 'post.php?post=' . $group_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $group_id ) . '</a>. Removing tag(s).' );

			wp_fusion()->user->remove_tags( $remove_tags, $user_id );

			add_action( 'wpf_tags_modified', array( $this, 'update_group_access' ), 10, 2 );

		}
	}

	/**
	 * Applies the linked tags when a user is added as a group leader.
	 *
	 * @since 3.46.0
	 *
	 * @param int $user_id  The user ID.
	 * @param int $group_id The group ID.
	 */
	public function added_group_leader_access( $user_id, $group_id ) {

		$settings = get_post_meta( $group_id, 'wpf-settings-learndash', true );

		if ( empty( $settings ) || empty( $settings['leader_tag'] ) ) {
			return;
		}

		// Prevent looping.
		remove_action( 'wpf_tags_modified', array( $this, 'update_group_access' ), 10, 2 );

		wpf_log( 'info', $user_id, 'User was enrolled as group leader in LearnDash group <a href="' . admin_url( 'post.php?post=' . $group_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $group_id ) . '</a>. Applying linked tag.' );

		wp_fusion()->user->apply_tags( $settings['leader_tag'], $user_id );

		add_action( 'wpf_tags_modified', array( $this, 'update_group_access' ), 10, 2 );
	}

	/**
	 * Removes the linked tags when a user is removed as a group leader.
	 *
	 * @since 3.46.0
	 *
	 * @param int $user_id  The user ID.
	 * @param int $group_id The group ID.
	 */
	public function removed_group_leader_access( $user_id, $group_id ) {

		$settings = get_post_meta( $group_id, 'wpf-settings-learndash', true );

		if ( empty( $settings ) || empty( $settings['leader_tag'] ) ) {
			return;
		}

		// Prevent looping.
		remove_action( 'wpf_tags_modified', array( $this, 'update_group_access' ), 10, 2 );

		wpf_log( 'info', $user_id, 'User was removed as Leader from LearnDash group <a href="' . admin_url( 'post.php?post=' . $group_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $group_id ) . '</a>. Removing linked tag.' );

		wp_fusion()->user->remove_tags( $settings['leader_tag'], $user_id );

		add_action( 'wpf_tags_modified', array( $this, 'update_group_access' ), 10, 2 );
	}

	/**
	 * Update user group enrollment when tags are modified.
	 *
	 * @since 3.46.0
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user tags.
	 */
	public function update_group_access( $user_id, $user_tags ) {

		// Possibly update groups.
		$linked_groups = get_posts(
			array(
				'post_type'  => 'groups',
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

		$updated = false;

		if ( ! empty( $linked_groups ) ) {

			$user_tags = wp_fusion()->user->get_tags( $user_id ); // Get them here for cases where the tags might have changed since wpf_tags_modified was triggered.

			foreach ( $linked_groups as $group_id ) {

				$settings = get_post_meta( $group_id, 'wpf-settings-learndash', true );

				if ( empty( $settings ) ) {
					continue;
				}

				if ( ! empty( $settings['tag_link'] ) ) {

					// Group member auto-enrollment.

					$tag_id = $settings['tag_link'][0];

					if ( in_array( $tag_id, $user_tags ) && learndash_is_user_in_group( $user_id, $group_id ) == false ) {

						wpf_log( 'info', $user_id, 'User added to LearnDash group <a href="' . admin_url( 'post.php?post=' . $group_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $group_id ) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

						// Prevent looping.
						remove_action( 'ld_added_group_access', array( $this, 'added_group_access' ), 10, 2 );

						ld_update_group_access( $user_id, $group_id, $remove = false );

						add_action( 'ld_added_group_access', array( $this, 'added_group_access' ), 10, 2 );

						$updated = true;

					} elseif ( ! in_array( $tag_id, $user_tags ) && learndash_is_user_in_group( $user_id, $group_id ) != false ) {

						wpf_log( 'info', $user_id, 'User removed from LearnDash group <a href="' . admin_url( 'post.php?post=' . $group_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $group_id ) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

						// Prevent looping.
						remove_action( 'ld_removed_group_access', array( $this, 'removed_group_access' ), 10, 2 );

						ld_update_group_access( $user_id, $group_id, $remove = true );

						add_action( 'ld_removed_group_access', array( $this, 'removed_group_access' ), 10, 2 );

						$updated = true;

					}
				}

				if ( ! empty( $settings['leader_tag'] ) ) {

					// Group leader auto-enrollment.

					$tag_id = $settings['leader_tag'][0];

					// Get list of group leader IDs - so we can check later if the user is a leader in the group
					// and we need to remove the user from the leader of that group accordingly.

					$group_leader_ids = learndash_get_groups_administrator_ids( $group_id, $bypass_transient = true );

					if ( in_array( $tag_id, $user_tags ) && ! in_array( $user_id, $group_leader_ids ) ) {

						wpf_log( 'info', $user_id, 'User added as leader to LearnDash group <a href="' . admin_url( 'post.php?post=' . $group_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $group_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

						// Prevent looping.
						remove_action( 'ld_added_leader_group_access', array( $this, 'added_group_leader_access' ), 10, 2 );

						ld_update_leader_group_access( $user_id, $group_id, $remove = false );

						add_action( 'ld_added_leader_group_access', array( $this, 'added_group_leader_access' ), 10, 2 );

					} elseif ( ! in_array( $tag_id, $user_tags ) && in_array( $user_id, $group_leader_ids ) ) {

						wpf_log( 'info', $user_id, 'User removed as leader from LearnDash group <a href="' . admin_url( 'post.php?post=' . $group_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $group_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

						remove_action( 'ld_removed_leader_group_access', array( $this, 'removed_group_leader_access' ), 10, 2 );

						ld_update_leader_group_access( $user_id, $group_id, $remove = true );

						add_action( 'ld_removed_leader_group_access', array( $this, 'removed_group_leader_access' ), 10, 2 );

					}
				}
			}
		}

		// Clear the courses / groups transients.

		if ( $updated ) {

			delete_transient( 'learndash_user_courses_' . $user_id );
			delete_transient( 'learndash_user_groups_' . $user_id );

		}
	}
}
