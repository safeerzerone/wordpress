<?php
/**
 * WP Fusion - BuddyBoss App integration.
 *
 * @package   WP_Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.46.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * BuddyBoss App integration.
 *
 * @since 3.46.12
 *
 * @link https://wpfusion.com/documentation/membership/buddyboss/
 */
class WPF_BuddyBoss_App extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.46.12
	 * @var string $slug
	 */
	public $slug = 'buddyboss-app';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.46.12
	 * @var string $name
	 */
	public $name = 'BuddyBoss App';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.46.12
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/membership/buddyboss/';

	/**
	 * Instance of WPF_BuddyBoss_IAP.
	 *
	 * @since 3.46.12
	 *
	 * @var WPF_BuddyBoss_IAP
	 */
	public $iap;

	/**
	 * Instance of WPF_BuddyBoss_App_Segment.
	 *
	 * @since 3.46.12
	 *
	 * @var WPF_BuddyBoss_App_Segment
	 */
	public $app_segment;

	/**
	 * Instance of WPF_BuddyBoss_App_Access_Group.
	 *
	 * @since 3.46.12
	 *
	 * @var WPF_BuddyBoss_App_Access_Group
	 */
	public $app_access_group;

	/**
	 * Gets things started.
	 *
	 * @since 3.46.12
	 */
	public function init() {

		// Load the integration classes.
		$this->load_dependencies();

		// Initialize the integrations.
		$this->init_integrations();

		// Profile updates.
		add_action( 'bp_rest_xprofile_update_items', array( $this, 'rest_xprofile_updated' ), 10, 3 );

		// LearnDash lesson access control for BuddyBoss app.
		if ( wpf_get_option( 'restrict_content', true ) ) {
			add_filter( 'bbapp_ld_rest_course_detail_lessons_list', array( $this, 'course_detail_lessons_list' ), 15, 2 );
		}
	}

	/**
	 * Load the integration dependencies.
	 *
	 * @since 3.46.12
	 */
	private function load_dependencies() {

		// In-App Purchases - only load if the module is active.
		if ( bbapp_is_active( 'iap' ) && function_exists( 'bbapp_iap' ) ) {
			require_once __DIR__ . '/class-buddyboss-iap.php';
		}

		// App Segments - only load if the class exists (no specific module check needed).
		if ( class_exists( 'BuddyBossApp\UserSegment\SegmentsAbstract' ) ) {
			require_once __DIR__ . '/class-buddyboss-app-segment.php';
		}

		// App Access Controls - only load if the module is active.
		if ( bbapp_is_active( 'access_controls' ) && class_exists( 'BuddyBossApp\AccessControls\Integration_Abstract' ) ) {
			require_once __DIR__ . '/class-buddyboss-app-access-group.php';
		}
	}

	/**
	 * Initialize the integrations.
	 *
	 * @since 3.46.12
	 */
	private function init_integrations() {

		// Initialize In-App Purchases - only if the module is fully active.
		if ( class_exists( 'WPF_BuddyBoss_IAP' ) && bbapp_is_active( 'iap' ) && function_exists( 'bbapp_iap' ) ) {
			$this->iap = WPF_BuddyBoss_IAP::instance();
			$this->iap->set_up( 'wp-fusion', 'WP Fusion' );

			// Register with BuddyBoss App.
			bbapp_iap()->integrations['wp_fusion'] = array(
				'type'    => 'wp-fusion',
				// translators: %s is the CRM name (e.g. "Infusionsoft").
				'label'   => sprintf( __( '%s Tag', 'wp-fusion' ), wp_fusion()->crm->name ) . ' (' . __( 'WP Fusion', 'wp-fusion' ) . ')',
				'enabled' => true,
				'class'   => WPF_BuddyBoss_IAP::class,
			);
		}

		// Initialize App Segments - only if the class is available.
		if ( class_exists( 'WPF_BuddyBoss_App_Segment' ) ) {
			$this->app_segment = new WPF_BuddyBoss_App_Segment();
		}

		// Initialize App Access Controls - only if the module is active.
		if ( class_exists( 'WPF_BuddyBoss_App_Access_Group' ) && bbapp_is_active( 'access_controls' ) ) {
			$this->app_access_group = new WPF_BuddyBoss_App_Access_Group();
			$this->app_access_group->setup();
		}
	}

	/**
	 * REST XProfile Updated.
	 *
	 * Runs when a profile is updated in the BuddyBoss app.
	 *
	 * @since 3.37.26
	 *
	 * @param array            $field_groups The field groups that were updated.
	 * @param WP_REST_Response $response     The response data.
	 * @param WP_REST_Request  $request      The request sent to the API.
	 */
	public function rest_xprofile_updated( $field_groups, $response, $request ) {

		$fields = $request->get_param( 'fields' );

		$update_data = array();

		foreach ( $fields as $k => $field_post ) {

			$field_id = ( isset( $field_post['field_id'] ) && ! empty( $field_post['field_id'] ) ) ? $field_post['field_id'] : '';
			$value    = ( isset( $field_post['value'] ) && ! empty( $field_post['value'] ) ) ? $field_post['value'] : '';

			if ( empty( $field_id ) ) {
				continue;
			}

			$field = xprofile_get_field( $field_id );

			if ( 'checkbox' === $field->type || 'multiselectbox' === $field->type ) {
				if ( is_serialized( $value ) ) {
					$value = maybe_unserialize( $value );
				}

				$value = json_decode( wp_json_encode( $value ), true );

				if ( ! is_array( $value ) ) {
					$value = (array) $value;
				}
			}

			// Format social network value.
			if ( 'socialnetworks' === $field->type ) {
				if ( is_serialized( $value ) ) {
					$value = maybe_unserialize( $value );
				}
			}

			$update_data[ "bbp_field_{$field_id}" ] = $value;
		}

		wp_fusion()->user->push_user_meta( bp_loggedin_user_id(), $update_data );
	}


	/**
	 * Filter the course detail lesson data to properly handle WP Fusion access control.
	 *
	 * This method ensures that lessons locked by WP Fusion show proper error messages
	 * in the course lesson list, similar to how LearnDash's native lesson locking works.
	 *
	 * @since 3.46.12
	 *
	 * @param array           $lesson_data The individual lesson data array.
	 * @param WP_REST_Request $request     The REST request object.
	 * @return array The modified lesson data array.
	 */
	public function course_detail_lessons_list( $lesson_data, $request ) {

		if ( ! is_array( $lesson_data ) || ! isset( $lesson_data['id'] ) ) {
			return $lesson_data;
		}

		$settings = false;

		$lesson_id = $lesson_data['id'];

		// Check if user has access to this lesson via WP Fusion.
		if ( ! wpf_user_can_access( $lesson_id ) ) {

			if ( false === $settings ) {
				$course_id = learndash_get_course_id( $lesson_id );
				$settings  = get_post_meta( $course_id, 'wpf-settings-learndash', true );
			}

			// Get the locked lesson text.
			$locked_lesson_text = ! empty( $settings['lesson_locked_text'] ) ? $settings['lesson_locked_text'] : wpf_get_option( 'ld_default_lesson_locked_text' );

			// Set the lesson as locked with proper error message.
			$lesson_data['has_content_access'] = false;
			$lesson_data['has_course_access']  = false;

			$lesson_data['error_message'] = array(
				'code'    => 'wp_fusion_lesson_access_denied',
				'message' => ! empty( $locked_lesson_text ) ? $locked_lesson_text : __( 'You do not have access to this lesson.', 'wp-fusion' ),
				'data'    => array( 'status' => 400 ),
			);
		}

		// Also check topics within this lesson for WP Fusion access control.
		if ( isset( $lesson_data['topics'] ) && is_array( $lesson_data['topics'] ) ) {

			foreach ( $lesson_data['topics'] as $topic_index => $topic_data ) {

				if ( ! isset( $topic_data['id'] ) ) {
					continue;
				}

				$topic_id = $topic_data['id'];

				// Check if user has access to this topic via WP Fusion.
				if ( ! wpf_user_can_access( $topic_id ) ) {

					if ( false === $settings ) {
						$course_id = learndash_get_course_id( $topic_id );
						$settings  = get_post_meta( $course_id, 'wpf-settings-learndash', true );
					}

					// Get the locked lesson text (topics use same setting).
					$locked_lesson_text = ! empty( $settings['lesson_locked_text'] ) ? $settings['lesson_locked_text'] : wpf_get_option( 'ld_default_lesson_locked_text' );

					// Set the topic as locked with proper error message.
					$lesson_data['topics'][ $topic_index ]['has_content_access'] = false;
					$lesson_data['topics'][ $topic_index ]['has_course_access']  = false;

					$lesson_data['topics'][ $topic_index ]['error_message'] = array(
						'code'    => 'wp_fusion_topic_access_denied',
						'message' => ! empty( $locked_lesson_text ) ? $locked_lesson_text : __( 'You do not have access to this topic.', 'wp-fusion' ),
						'data'    => array( 'status' => 400 ),
					);
				}
			}
		}

		return $lesson_data;
	}
}

new WPF_BuddyBoss_App();
