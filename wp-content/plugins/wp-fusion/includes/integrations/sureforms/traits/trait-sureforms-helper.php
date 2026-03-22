<?php
/**
 * SureForms Helper trait.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.47.3
 */

namespace WP_Fusion\Integrations\SureForms\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

trait SureForms_Helper {
	/**
	 * Check if WP Fusion is active.
	 *
	 * @since 3.47.3
	 * @return bool
	 */
	protected function is_plugin_active() {
		return function_exists( 'wp_fusion' );
	}

	/**
	 * Check if the connected CRM supports lists.
	 *
	 * @since 3.47.3
	 * @return bool
	 */
	protected function crm_supports_lists() {
		if ( ! $this->is_plugin_active() ) {
			return false;
		}

		$crm = wp_fusion()->crm;

		return is_object( $crm ) && $crm->supports( 'lists' );
	}

	/**
	 * Normalize different value structures.
	 *
	 * @since 3.47.3
	 *
	 * @param mixed $value Value to normalize.
	 * @return array
	 */
	protected function normalize_multi_value( $value ) {
		if ( is_array( $value ) ) {
			$normalized = array();

			foreach ( $value as $item ) {
				if ( is_array( $item ) ) {
					if ( isset( $item['value'] ) ) {
						$normalized[] = $item['value'];
					} elseif ( isset( $item['label'] ) ) {
						$normalized[] = $item['label'];
					}
				} else {
					$normalized[] = $item;
				}
			}

			return $normalized;
		}

		if ( is_string( $value ) ) {
			$parts = array_map( 'trim', explode( ',', $value ) );
			return array_filter( $parts, array( $this, 'is_not_empty_string' ) );
		}

		return array();
	}

	/**
	 * Determine if a string is not empty after trimming.
	 *
	 * @since 3.47.3
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	protected function is_not_empty_string( $value ) {
		return is_string( $value ) && '' !== trim( $value );
	}

	/**
	 * Map CRM field type to SureForms field type.
	 *
	 * @since 3.47.3
	 *
	 * @param string|array $field_config CRM field configuration.
	 * @return string
	 */
	protected function map_field_type( $field_config ) {
		$type = '';

		if ( is_array( $field_config ) && isset( $field_config['type'] ) ) {
			$type = $field_config['type'];
		} elseif ( is_string( $field_config ) ) {
			$type = $field_config;
		}

		switch ( strtolower( (string) $type ) ) {
			case 'date':
			case 'birthday':
				return 'date';
			case 'datetime':
				return 'datetime';
			case 'number':
			case 'integer':
			case 'float':
				return 'number';
			case 'checkbox':
			case 'checkboxes':
				return 'checkbox';
			case 'radio':
			case 'dropdown':
			case 'select':
				return 'select';
			case 'tel':
			case 'phone':
				return 'tel';
			case 'url':
				return 'url';
			default:
				return 'text';
		}
	}
}
