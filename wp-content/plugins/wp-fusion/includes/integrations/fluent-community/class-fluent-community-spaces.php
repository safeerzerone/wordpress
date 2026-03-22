<?php
/**
 * WP Fusion - FluentCommunity Spaces Management
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
 * FluentCommunity Spaces Management.
 *
 * @since 3.44.20
 */
class WPF_FluentCommunity_Spaces {

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
		add_action( 'fluent_community/space/joined', array( $this, 'space_joined' ), 10, 3 );
		add_action( 'fluent_community/space/user_left', array( $this, 'space_left' ), 10, 3 );
	}

	/**
	 * Gets all spaces from FluentCommunity.
	 *
	 * @since  3.44.20
	 *
	 * @return array Spaces.
	 */
	public function get_spaces() {
		$spaces = \FluentCommunity\App\Functions\Utility::getSpaces();

		// Convert to id => title format.
		$formatted = array();
		foreach ( $spaces as $space ) {
			$formatted[ $space->id ] = $space->title;
		}

		return $formatted;
	}

	/**
	 * Handle space joined.
	 *
	 * @since  3.44.20
	 *
	 * @param object $space   The space object.
	 * @param int    $user_id The user ID.
	 * @param array  $data    Additional data.
	 */
	public function space_joined( $space, $user_id, $data ) {

		// Update meta.
		wp_fusion()->user->push_user_meta(
			$user_id,
			array(
				'fc_last_space_joined'      => $space->title,
				'fc_last_space_joined_date' => current_time( 'Y-m-d H:i:s' ),
			)
		);

		$apply_tags = array();

		// Get settings from space meta (new API) or fall back to global settings.
		$settings = $space->getCustomMeta( '_wpf_settings', array() );

		if ( ! empty( $settings['tags'] ) ) {
			$apply_tags = array_merge( $apply_tags, $settings['tags'] );
		}

		if ( ! empty( $settings['tag_link'] ) && ! doing_action( 'wpf_tags_modified' ) ) {
			$tag_link   = is_array( $settings['tag_link'] ) ? $settings['tag_link'] : array( $settings['tag_link'] );
			$apply_tags = array_merge( $apply_tags, $tag_link );
		}

		if ( ! empty( $apply_tags ) ) {
			wpf_log( 'info', $user_id, 'User joined FluentCommunity space <strong>' . $space->title . '</strong>. Applying tags:' );

			remove_action( 'wpf_tags_modified', array( $this->integration, 'tags_modified' ) ); // Prevent looping.
			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
			add_action( 'wpf_tags_modified', array( $this->integration, 'tags_modified' ), 10, 2 ); // Prevent looping.
		}
	}

	/**
	 * Handle space left.
	 *
	 * @since  3.44.20
	 *
	 * @param object $space   The space object.
	 * @param int    $user_id The user ID.
	 * @param array  $data    Additional data.
	 */
	public function space_left( $space, $user_id, $data ) {

		$remove_tags = array();

		// Get settings from space meta (new API) or fall back to global settings.
		$settings = $space->getCustomMeta( '_wpf_settings', array() );

		if ( ! empty( $settings['remove'] ) && '1' === $settings['remove'] && ! empty( $settings['tags'] ) ) {
			$remove_tags = array_merge( $remove_tags, $settings['tags'] );
		}

		if ( ! empty( $settings['tag_link'] ) ) {
			$tag_link    = is_array( $settings['tag_link'] ) ? $settings['tag_link'] : array( $settings['tag_link'] );
			$remove_tags = array_merge( $remove_tags, $tag_link );
		}

		if ( ! empty( $remove_tags ) ) {
			wpf_log( 'info', $user_id, 'User left FluentCommunity space <strong>' . $space->title . '</strong>. Removing tags:' );

			remove_action( 'wpf_tags_modified', array( $this->integration, 'tags_modified' ) ); // Prevent looping.
			wp_fusion()->user->remove_tags( $remove_tags, $user_id );
			add_action( 'wpf_tags_modified', array( $this->integration, 'tags_modified' ), 10, 2 ); // Prevent looping.
		}
	}

	/**
	 * Handle space tag synchronization.
	 *
	 * @since  3.44.20
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user's tags.
	 */
	public function sync_space_tags( $user_id, $user_tags ) {

		$spaces = $this->get_spaces();

		foreach ( $spaces as $space_id => $title ) {

			// Get the space object to access settings.
			$space = \FluentCommunity\App\Models\Space::find( $space_id );

			if ( ! $space ) {
				continue;
			}

			$settings = $space->getCustomMeta( '_wpf_settings', array() );

			if ( empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_link = $settings['tag_link'];

			// Backward compatibility: convert single value to array.
			if ( ! is_array( $tag_link ) ) {
				$tag_link = array( $tag_link );
			}

			$is_member = \FluentCommunity\App\Services\Helper::isUserInSpace( $user_id, $space_id );

			$matched_tags = array_intersect( $tag_link, $user_tags );

			if ( $matched_tags && ! $is_member ) {

				// Join space.
				\FluentCommunity\App\Services\Helper::addToSpace( $space_id, $user_id, 'member', 'by_admin' );

				$tag_labels = array_map( 'wpf_get_tag_label', $matched_tags );
				wpf_log( 'info', $user_id, 'User joined FluentCommunity space <strong>' . $title . '</strong> by linked tag(s) <strong>' . implode( ', ', $tag_labels ) . '</strong>' );

			} elseif ( ! $matched_tags && $is_member ) {

				// Leave space.
				\FluentCommunity\App\Services\Helper::removeFromSpace( $space_id, $user_id, 'by_admin' );

				$tag_labels = array_map( 'wpf_get_tag_label', $tag_link );
				wpf_log( 'info', $user_id, 'User left FluentCommunity space <strong>' . $title . '</strong>. No linked tags remaining: <strong>' . implode( ', ', $tag_labels ) . '</strong>' );
			}
		}
	}
}
