<?php
/**
 * WP Fusion - SureForms Integration
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.47.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms integration class.
 *
 * @since 3.47.3
 */
class WPF_SureForms extends WPF_Integrations_Base {

	/**
	 * Internal integration slug.
	 *
	 * @since 3.47.3
	 * @var string
	 */
	public $slug = 'sureforms';

	/**
	 * Human readable integration name.
	 *
	 * @since 3.47.3
	 * @var string
	 */
	public $name = 'SureForms';

	/**
	 * Documentation URL.
	 *
	 * @since 3.47.3
	 * @var string
	 */
	public $docs_url = 'https://wpfusion.com/documentation/lead-generation/sureforms/';

	/**
	 * Get things running.
	 *
	 * @since 3.47.3
	 * @return void
	 */
	public function init() {
		if ( ! class_exists( 'SureForms_Helper' ) ) {
			require_once WPF_DIR_PATH . 'includes/integrations/sureforms/traits/trait-sureforms-helper.php';
		}

		add_filter( 'srfm_pro_native_integrations_json_configs', array( $this, 'register_json_config' ) );
		add_filter( 'srfm_pro_wordpress_plugin_integrations', array( $this, 'register_plugin_handler' ) );
	}

	/**
	 * Register the SureForms integration JSON config.
	 *
	 * @since 3.47.3
	 *
	 * @param array $configs Existing integration configs.
	 * @return array
	 */
	public function register_json_config( $configs ) {
		$config_data = $this->get_config_array();

		if ( empty( $config_data ) ) {
			return $configs;
		}

		// Resolve the icon path to an absolute URL.
		$config_data = $this->resolve_icon_path( $config_data );

		$integrations = array( $config_data );

		if ( isset( $config_data['integrations'] ) && is_array( $config_data['integrations'] ) ) {
			$integrations = $config_data['integrations'];
		} elseif ( is_array( $config_data ) ) {
			$integrations = array( $config_data );
		}

		$integrations = array_values(
			array_map(
				array( $this, 'maybe_filter_lists_action' ),
				array_filter( $integrations, array( __CLASS__, 'is_valid_integration_config' ) )
			)
		);

		if ( empty( $integrations ) ) {
			return $configs;
		}

		$payload = array( 'integrations' => $integrations );

		$encoded = wp_json_encode( $payload );

		if ( false === $encoded ) {
			return $configs;
		}

		$configs[] = $encoded;

		return $configs;
	}

	/**
	 * Resolve the icon path to an absolute URL.
	 *
	 * SureForms expects icons to be in its own plugin directory by default.
	 * This method converts relative icon paths to absolute URLs pointing to
	 * the WP Fusion plugin directory.
	 *
	 * @since 3.47.3
	 *
	 * @param array $config The integration config.
	 * @return array The config with resolved icon path.
	 */
	private function resolve_icon_path( $config ) {
		if ( isset( $config['integration']['icon'] ) && ! filter_var( $config['integration']['icon'], FILTER_VALIDATE_URL ) ) {
			$config['integration']['icon'] = WPF_DIR_URL . 'includes/integrations/sureforms/' . $config['integration']['icon'];
		}

		return $config;
	}

	/**
	 * Register the SureForms plugin handler file.
	 *
	 * @since 3.47.3
	 *
	 * @param array $handlers Existing handlers.
	 * @return array
	 */
	public function register_plugin_handler( $handlers ) {
		$handler_file = WPF_DIR_PATH . 'includes/integrations/sureforms/actions.php';

		if ( ! file_exists( $handler_file ) ) {
			return $handlers;
		}

		$handlers['wp-fusion'] = array(
			'file'      => $handler_file,
			'detection' => array( 'class' => 'WP_Fusion' ),
		);

		return $handlers;
	}

	/**
	 * Load and decode the integration config.
	 *
	 * @since 3.47.3
	 * @return array
	 */
	private function get_config_array() {
		$config_path = WPF_DIR_PATH . 'includes/integrations/sureforms/config.json';

		if ( ! file_exists( $config_path ) ) {
			return array();
		}

		$config = $this->get_config_array_from_file( $config_path );

		return $config;
	}

	/**
	 * Get the config array from a file.
	 *
	 * @since 3.47.3
	 *
	 * @param string $config_path The path to the config file.
	 * @return array
	 */
	private function get_config_array_from_file( $config_path ) {
		$config = array();

		// Compatibility with WordPress < 5.9.
		if ( ! function_exists( 'wp_json_file_decode' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$file_contents = file_get_contents( $config_path );

			if ( false === $file_contents ) {
				return array();
			}

			$config = json_decode( $file_contents, true );

			return $config;
		}

		$config = wp_json_file_decode(
			$config_path,
			array(
				'associative' => true,
			)
		);

		if ( is_wp_error( $config ) ) {
			return array();
		}

		return $config;
	}

	/**
	 * Remove list-specific actions when the active CRM does not support lists.
	 *
	 * @since 3.47.3
	 *
	 * @param array $config Integration config array.
	 * @return array
	 */
	private function maybe_filter_lists_action( $config ) {
		if ( empty( $config['actions'] ) ) {
			return $config;
		}

		$crm = wp_fusion()->crm;

		if ( ! is_object( $crm ) || $crm->supports( 'lists' ) ) {
			return $config;
		}

		$config['actions'] = array_values(
			array_filter(
				$config['actions'],
				function ( $action ) {
					return isset( $action['handler']['action'] ) && 'wp_fusion_assign_lists' !== $action['handler']['action'];
				}
			)
		);

		return $config;
	}

	/**
	 * Determine whether an integration config is valid.
	 *
	 * @since 3.47.3
	 *
	 * @param mixed $config Potential integration config.
	 * @return bool
	 */
	private static function is_valid_integration_config( $config ) {
		return is_array( $config ) && isset( $config['id'] );
	}
}

new WPF_SureForms();
