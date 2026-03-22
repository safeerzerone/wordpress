<?php
/**
 * WP Fusion - LearnDash Compatibility
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
 * LearnDash compatibility.
 *
 * Handles compatibility with other plugins.
 *
 * @since 3.46.0
 */
class WPF_LearnDash_Compatibility {

	/**
	 * Gets things started.
	 *
	 * @since 3.46.0
	 */
	public function __construct() {

		// ThriveCart.
		add_action( 'learndash_thrivecart_after_create_user', array( $this, 'thrivecart_after_create_user' ), 10, 3 );

		// Uncanny Toolkit Pro compatibility.
		add_action( 'wp_fusion_init', array( $this, 'uncanny_toolkit_pro_compatibility' ) );

		// HonorsWP Student - Parent Access compatibility.
		if ( class_exists( 'Learndash_Access_For_Parents' ) ) {
			add_filter( 'wpf_user_register', array( $this, 'sync_student_parent_access' ) );
			add_action( 'init', array( $this, 'sync_student_parent_access_linked' ) );
		}

		// Detect LearnDash for WooCommerce plugin.
		add_action( 'added_user_meta', array( $this, 'maybe_add_learndash_woocommerce_plugin_source' ), 10, 4 );
		add_action( 'updated_user_meta', array( $this, 'maybe_add_learndash_woocommerce_plugin_source' ), 10, 4 );
	}

	/**
	 * Runs after a new user is inserted by ThriveCart, syncs the generated
	 * password to the CRM.
	 *
	 * @since 3.36.8
	 *
	 * @param int         $user_id  The new user ID.
	 * @param array       $customer The ThriveCart customer data.
	 * @param string|bool $password The generated password.
	 */
	public function thrivecart_after_create_user( $user_id, $customer, $password = false ) {

		if ( false !== $password ) {

			$password_field = wpf_get_option( 'return_password_field' );

			if ( wpf_get_option( 'return_password' ) && ! empty( $password_field ) ) {

				wpf_log( 'info', $user_id, 'Syncing LearnDash-generated password <strong>' . $password . '</strong>' );

				$update_data = array(
					$password_field => $password,
				);

				$contact_id = wpf_get_contact_id( $user_id );
				$result     = wp_fusion()->crm->update_contact( $contact_id, $update_data, false );

			}
		}
	}


	/**
	 * Uncanny Toolkit Pro compatibility.
	 *
	 * The autocomplete lessons module in Uncanny Toolkit Pro runs at shutdown
	 * on priority 10, which means any tags to be applied aren't picked up by
	 * the WPF queue (which runs on priority 1).
	 *
	 * This adds a new shutdown handler at priority 15 to pick up any tags that
	 * were queued up by autocompleted lessons.
	 *
	 * @since 3.38.23
	 *
	 * @see   \uncanny_pro_toolkit\LessonTopicAutoComplete
	 * @see   WPF_CRM_Base::shutdown
	 */
	public function uncanny_toolkit_pro_compatibility() {

		if ( class_exists( '\uncanny_pro_toolkit\LessonTopicAutoComplete' ) && method_exists( wp_fusion()->crm, 'shutdown' ) ) {
			add_action( 'shutdown', array( wp_fusion()->crm, 'shutdown' ), 15 );
		}
	}

	/**
	 * Student Parent Access.
	 *
	 * Syncs the parent email to the child's account in the CRM when a child account is created.
	 *
	 * @since 3.41.36
	 *
	 * @param array $post_data The $_POST data.
	 * @return array The post data.
	 */
	public function sync_student_parent_access( $post_data ) {

		if ( isset( $_POST['ld-submit-no-email-account-button'] ) ) {

			// The parent email is the email of the current user who submitted the form.
			$post_data['ldap_parent_email'] = wpf_get_current_user_email();

		}

		return $post_data;
	}

	/**
	 * Student Parent Access.
	 *
	 * Syncs the parent email to the child's account in the CRM when a child account is linked.
	 *
	 * @since 3.41.36
	 */
	public function sync_student_parent_access_linked() {

		if ( isset( $_POST['ld-submit-links-button'] ) ) {

			$email = sanitize_email( $_POST['link_child_email'] );
			$user  = get_user_by( 'email', $email );

			$update_data = array( 'ldap_parent_email' => wpf_get_current_user_email() );

			wp_fusion()->user->push_user_meta( $user->ID, $update_data );

		}
	}

	/**
	 * If the LearnDash for WooCommerce plugin is triggering an enrollment, make
	 * sure it's recorded in the WPF logs.
	 *
	 * @since 3.38.17
	 *
	 * @param int    $meta_id     The meta ID.
	 * @param int    $user_id     The user ID.
	 * @param string $meta_key    The meta key.
	 * @param mixes  $_meta_value The meta value.
	 */
	public function maybe_add_learndash_woocommerce_plugin_source( $meta_id, $user_id, $meta_key, $_meta_value = false ) {

		if ( '_learndash_woocommerce_enrolled_courses_access_counter' === $meta_key ) {

			if ( function_exists( 'memberdash' ) ) {
				wp_fusion()->logger->add_source( 'memberdash' );
			} else {
				wp_fusion()->logger->add_source( 'learndash-woocommerce' );
			}
		}
	}
}
