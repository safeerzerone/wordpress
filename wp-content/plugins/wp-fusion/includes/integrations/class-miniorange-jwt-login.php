<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * miniOrange JWT Login integration.
 *
 * @since 3.46.0
 */
class WPF_miniOrange_JWT_Login extends WPF_Integrations_Base {

	/**
	 * This identifies the integration internally and makes it available.
	 *
	 * @var  string
	 * @since 3.46.0
	 */

	public $slug = 'miniorange-jwt-login';

	/**
	 * The human-readable name of the integration.
	 *
	 * @var  string
	 * @since 3.46.0
	 */

	public $name = 'miniOrange JWT Login';

	/**
	 * Get things started.
	 *
	 * @since 3.46.0
	 */
	public function init() {

		add_filter( 'wpf_get_contact_id_email', array( $this, 'get_contact_id_email' ), 10, 2 );
		add_filter( 'wpf_user_register', array( $this, 'user_register' ) );
	}

	/**
	 * Get contact ID email.
	 *
	 * @since 3.46.0
	 *
	 * @param string $email     The email address.
	 * @param int    $user_id   The user ID.
	 *
	 * @return string The email address.
	 */
	public function get_contact_id_email( $email, $user_id ) {
		if ( empty( $email ) || ! is_email( $email ) ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user ) {
				$email = $user->user_login;
			}
		}
		return $email;
	}

	/**
	 * User register.
	 *
	 * @since 3.46.0
	 *
	 * @param array $user_data The user data.
	 *
	 * @return array The user data.
	 */
	public function user_register( $user_data ) {

		if ( empty( $user_data['user_email'] ) && is_email( $user_data['user_login'] ) ) {
			$user_data['user_email'] = $user_data['user_login'];
		} elseif ( ! empty( $_REQUEST['email'] ) && is_email( wp_unslash( $_REQUEST['email'] ) ) ) {
			$user_data['user_email'] = sanitize_email( wp_unslash( $_REQUEST['email'] ) );
		}

		if ( isset( $_REQUEST['firstName'] ) ) {
			$user_data['first_name'] = sanitize_text_field( wp_unslash( $_REQUEST['firstName'] ) );
		}

		if ( isset( $_REQUEST['lastName'] ) ) {
			$user_data['last_name'] = sanitize_text_field( wp_unslash( $_REQUEST['lastName'] ) );
		}

		return $user_data;
	}
}

new WPF_miniOrange_JWT_Login();
