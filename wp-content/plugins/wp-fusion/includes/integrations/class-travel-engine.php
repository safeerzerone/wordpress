<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Travel_Engine extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.45.11
	 * @var string $slug
	 */

	public $slug = 'travel-engine';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.45.11
	 * @var string $name
	 */
	public $name = 'Travel Engine';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.45.11
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/events/wp-travel-engine/';

	/**
	 * Gets things started.
	 *
	 * @since 3.45.11
	 */
	public function init() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_trip', array( $this, 'save_meta_box_data' ) );

		add_action( 'wp_travel_engine_after_booking_process_completed', array( $this, 'after_booking' ) );
		add_action( 'save_post_booking', array( $this, 'after_booking' ) );
	}

	/**
	 * Process trip and attendee after booking.
	 *
	 * @since 3.45.11
	 *
	 * @param int $booking_id The booking ID.
	 */
	public function after_booking( $booking_id ) {

		$status = get_post_meta( $booking_id, 'wp_travel_engine_booking_status', true );

		if ( 'booked' !== $status ) {
			return;
		}

		$trips = get_post_meta( $booking_id, 'order_trips', true );

		if ( empty( $trips ) ) {
			return;
		}

		foreach ( $trips as $trip ) {
			$trip_id = $trip['ID'];

			$settings = get_post_meta( $trip_id, 'wpf_settings_travel_engine', true );

			if ( empty( $settings ) ) {
				continue;
			}

			$billing_info = get_post_meta( $booking_id, 'billing_info', true );
			$email        = $billing_info['email'];
			$trip_data    = get_post_meta( $trip_id, 'wp_travel_engine_setting', true );

			$update_data = array(
				'user_email'            => $email,
				'first_name'            => $billing_info['fname'] ?? '',
				'last_name'             => $billing_info['lname'] ?? '',
				'te_attendee_address'   => $billing_info['address'] ?? '',
				'te_attendee_city'      => $billing_info['city'] ?? '',
				'te_attendee_country'   => $billing_info['country'] ?? '',
				'te_trip_name'          => $trip['title'] ?? '',
				'te_trip_code'          => $trip_data['trip_code'] ?? '',
				'te_trip_duration'      => $trip_data['trip_duration'] ?? '',
				'te_trip_duration_unit' => $trip_data['trip_duration_unit'] ?? '',
				'te_trip_date'          => $trip['_cart_item_object']['trip_date'] ?? '',
				'te_package_name'       => $trip['package_name'] ?? '',
				'te_trip_price'         => $trip['_cart_item_object']['trip_price'] ?? '',
				'te_trip_tax'           => $trip['_cart_item_object']['tax_amount'] ?? '',
			);

			$this->register_attendee( $update_data, $settings['apply_tags'] );

			do_action( 'wpf_travel_engine_after_booking', $booking_id, $contact_id );

		}
	}


	/**
	 * Create/update attendee into the CRM and apply tags to it when he books a trip.
	 *
	 * @since 3.45.11
	 *
	 * @param array $update_data The field data.
	 * @param array $apply_tags  The tags to apply.
	 */
	public function register_attendee( $update_data, $apply_tags ) {

		// Send update data.
		$user = get_user_by( 'email', $update_data['user_email'] );

		if ( is_object( $user ) ) {

			wp_fusion()->user->push_user_meta( $user->ID, $update_data );

			if ( ! empty( $apply_tags ) ) {

				wpf_log(
					'info',
					0,
					'Applying tag(s) for travel engine booking for user #' . $user->ID,
					array(
						'tag_array' => $apply_tags,
					)
				);

				wp_fusion()->user->apply_tags( $apply_tags, $user->ID );
			}
		} else {

			// Guest checkouts.
			$contact_id = $this->guest_registration( $update_data['user_email'], $update_data );

			if ( ! empty( $apply_tags ) ) {

				wpf_log(
					'info',
					0,
					'Applying tag(s) for travel engine booking for contact #' . $contact_id,
					array(
						'tag_array' => $apply_tags,
					)
				);

				wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

			}
		}
	}

	/**
	 * Adds Travel Engine field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['travel-engine'] = array(
			'title' => __( 'Travel Engine', 'wp-fusion' ),
			'url'   => false,
		);

		return $field_groups;
	}

	/**
	 * Loads Travel Engine fields for inclusion in Contact Fields table
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */
	public function add_meta_fields( $meta_fields ) {
		$meta_fields['te_trip_name'] = array(
			'label'  => 'Trip Name',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_trip_code'] = array(
			'label'  => 'Trip Code',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_trip_duration'] = array(
			'label'  => 'Trip Duration',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_trip_duration_unit'] = array(
			'label'  => 'Trip Duration Unit',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_trip_date'] = array(
			'label'  => 'Trip Date',
			'type'   => 'date',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_package_name'] = array(
			'label'  => 'Package Name',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_trip_price'] = array(
			'label'  => 'Trip Price',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_trip_tax'] = array(
			'label'  => 'Trip Tax',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_attendee_address'] = array(
			'label'  => 'Attendee Address',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_attendee_city'] = array(
			'label'  => 'Attendee City',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		$meta_fields['te_attendee_country'] = array(
			'label'  => 'Attendee Country',
			'type'   => 'text',
			'group'  => 'travel-engine',
			'pseudo' => true,
		);

		return $meta_fields;
	}



	/**
	 * Adds meta box on the Trip post type.
	 *
	 * @since 3.45.11
	 */
	public function add_meta_box() {

		add_meta_box( 'travel-engine-wp-fusion', 'WP Fusion - Trip Settings', array( $this, 'meta_box_callback' ), 'trip', 'normal', 'default' );
	}


	/**
	 * Displays meta box content.
	 *
	 * @since 3.45.11
	 *
	 * @param WP_Post $post   The post.
	 */
	public function meta_box_callback( $post ) {
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpf_meta_box_travel_engine', 'wpf_meta_box_travel_engine_nonce' );

		$settings = array(
			'apply_tags' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf_settings_travel_engine', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf_settings_travel_engine', true ) );
		}

		// Apply tags

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags">' . __( 'Apply Tags', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags'],
			'meta_name' => 'wpf_settings_travel_engine',
			'field_id'  => 'apply_tags',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">' . sprintf( __( 'These tags will be applied in %s when a customer books this trip.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';
		echo '</td>';

		echo '</tr>';

		do_action( 'wpf_edd_meta_box_inner', $post, $settings );

		echo '</tbody></table>';
	}

	/**
	 * Saves trip metabox data.
	 *
	 * @since 3.45.11
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_travel_engine_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_travel_engine_nonce'], 'wpf_meta_box_travel_engine' ) ) {
			return;
		}

		if ( ! empty( $_POST['wpf_settings_travel_engine'] ) ) {
			update_post_meta( $post_id, 'wpf_settings_travel_engine', $_POST['wpf_settings_travel_engine'] );
		} else {
			delete_post_meta( $post_id, 'wpf_settings_travel_engine' );
		}
	}
}

new WPF_Travel_Engine();
