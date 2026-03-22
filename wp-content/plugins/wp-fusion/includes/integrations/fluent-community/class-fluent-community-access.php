<?php
/**
 * WP Fusion - FluentCommunity Access Control
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
 * FluentCommunity Access Control.
 *
 * @since 3.44.20
 */
class WPF_FluentCommunity_Access {

	/**
	 * The main integration instance.
	 *
	 * @since 3.44.20
	 * @var   WPF_FluentCommunity
	 */
	private $integration;

	/**
	 * Constructor.
	 *
	 * @since 3.44.20
	 *
	 * @param WPF_FluentCommunity $integration The main integration instance.
	 */
	public function __construct( $integration ) {
		$this->integration = $integration;

		// Add hooks directly in constructor.
		add_filter( 'fluent_community/can_access_portal', array( $this, 'can_access_portal' ) );
	}

	/**
	 * Controls access to the community portal based on tags.
	 *
	 * @since  3.44.20
	 *
	 * @param  bool $can_access_portal Whether the user can access the portal.
	 * @return bool Access status.
	 */
	public function can_access_portal( $can_access_portal ) {

		if ( ! wpf_is_user_logged_in() || wpf_admin_override() ) {
			return $can_access_portal;
		}

		// Make sure the request is actually a request to access the portal.
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return $can_access_portal;
		}

		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$request_uri = trim( $request_uri, '/' );
		$portal_path = \FluentCommunity\App\Services\Helper::getPortalRequestPath( $request_uri );

		if ( ! $portal_path ) {
			return $can_access_portal;
		}

		$required_tags = $this->integration->get_setting( 'fc_access_tags' );

		if ( ! empty( $required_tags ) ) {
			$can_access = wp_fusion()->user->has_tag( $required_tags );

			if ( ! $can_access ) {
				$redirect = $this->integration->get_setting( 'redirect' );

				if ( ! empty( $redirect ) ) {
					if ( is_numeric( $redirect ) ) {
						$redirect = get_permalink( $redirect );
					}

					wp_safe_redirect( $redirect );
					exit;
				}
			}

			return $can_access;
		}

		return $can_access_portal;
	}
}
