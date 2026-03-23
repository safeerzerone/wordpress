<?php
/**
 * Plugin Name: WooCommerce Custom Thank You Redirect
 * Description: Redirect WooCommerce checkout success page to a custom thank you page.
 * Version: 1.0
 * Author: Zerone
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Shared ARMember plan ID ↔ label map for WP Fusion sync with HighLevel.
 *
 * @return array{ id_to_label: array<int, string>, label_to_id: array<string, int> } label_to_id keys are lowercase.
 */
function wc_wpf_armember_plan_maps() {
    static $cached = null;
    if ( null !== $cached ) {
        return $cached;
    }

    $id_to_label = array(
        1 => 'Annual Membership - Recurring',
        2 => 'Annual Membership - Single Year',
        3 => 'Lifetime Membership',
    );

    $label_to_id = array();
    foreach ( $id_to_label as $id => $label ) {
        $label_to_id[ strtolower( $label ) ] = (int) $id;
    }

    $cached = array(
        'id_to_label' => $id_to_label,
        'label_to_id' => $label_to_id,
    );

    return $cached;
}

/**
 * Turn CRM value (labels, comma-separated labels, numeric ID, or list) into plan ID(s).
 *
 * @param mixed $value       Raw value from HighLevel.
 * @param bool  $force_array True for arm_user_plan_ids (array of ints); false for arm_user_plan (single int).
 * @return mixed Unchanged $value if no known plan could be resolved.
 */
function wc_wpf_armember_crm_value_to_plan_ids( $value, $force_array ) {
    if ( null === $value || '' === $value ) {
        return $value;
    }

    $label_to_id = wc_wpf_armember_plan_maps()['label_to_id'];

    $to_id = function ( $v ) use ( $label_to_id ) {
        if ( '' === (string) $v ) {
            return null;
        }
        if ( is_numeric( $v ) ) {
            return (int) $v;
        }
        $k = strtolower( trim( (string) $v ) );

        return isset( $label_to_id[ $k ] ) ? $label_to_id[ $k ] : null;
    };

    $ids = array();

    if ( is_array( $value ) ) {
        foreach ( $value as $v ) {
            $id = $to_id( $v );
            if ( null !== $id ) {
                $ids[] = $id;
            }
        }
    } else {
        $parts = preg_split( '/\s*,\s*/', trim( (string) $value ), -1, PREG_SPLIT_NO_EMPTY );
        foreach ( $parts as $p ) {
            $id = $to_id( $p );
            if ( null !== $id ) {
                $ids[] = $id;
            }
        }
    }

    $ids = array_values( array_unique( $ids ) );

    if ( empty( $ids ) ) {
        return $value;
    }

    if ( $force_array ) {
        return $ids;
    }

    return $ids[0];
}

// Redirect after successful checkout
add_filter('woocommerce_get_return_url', 'wc_custom_redirect_after_checkout', 10, 2);

function wc_custom_redirect_after_checkout($return_url, $order) {

    if (!$order) {
        return $return_url;
    }

    // Optional: Redirect only if order is paid
    if ($order->get_payment_method() === 'stripe_bacs_debit') {
        return home_url('/thank_you_stripe/');
    }

    return $return_url;
}

/**
 * Map ARMember plan IDs to labels for Go High Level (and other CRMs) via WP Fusion.
 *
 * wpf_format_field_value passes (value, field_type, crm_field_id, update_data) — not the
 * WordPress meta key. Match the CRM field that is mapped to arm_user_plan in WP Fusion.
 */
add_filter(
    'wpf_format_field_value',
    function ( $value, $type, $crm_field, $update_data = array() ) {
        if ( ! function_exists( 'wp_fusion' ) || ! is_object( wp_fusion()->crm ) ) {
            return $value;
        }

        $crm          = wp_fusion()->crm;
        $plan_map     = wc_wpf_armember_plan_maps()['id_to_label'];
        $crm_field_id = (string) $crm_field;

        $arm_meta_crm = $crm->get_crm_field( 'arm_user_plan' );
        if ( $arm_meta_crm && (string) $arm_meta_crm === $crm_field_id ) {
            if ( is_array( $value ) ) {
                $value = reset( $value );
            }
            $key = is_numeric( $value ) ? (int) $value : $value;

            return isset( $plan_map[ $key ] ) ? $plan_map[ $key ] : $value;
        }

        $ids_meta_crm = $crm->get_crm_field( 'arm_user_plan_ids' );
        if ( $ids_meta_crm && (string) $ids_meta_crm === $crm_field_id && is_array( $value ) ) {
            $labels = array();
            foreach ( $value as $id ) {
                $key = is_numeric( $id ) ? (int) $id : $id;
                if ( isset( $plan_map[ $key ] ) ) {
                    $labels[] = $plan_map[ $key ];
                }
            }

            return ! empty( $labels ) ? implode( ', ', $labels ) : $value;
        }

        return $value;
    },
    10,
    4
);

/**
 * Map HighLevel labels back to ARMember plan IDs when WP Fusion pulls contact meta.
 */
add_filter(
    'wpf_pulled_user_meta',
    function ( $user_meta, $user_id ) {
        if ( ! is_array( $user_meta ) ) {
            return $user_meta;
        }

        if ( array_key_exists( 'arm_user_plan', $user_meta ) ) {
            $user_meta['arm_user_plan'] = wc_wpf_armember_crm_value_to_plan_ids(
                $user_meta['arm_user_plan'],
                false
            );
        }

        if ( array_key_exists( 'arm_user_plan_ids', $user_meta ) ) {
            $user_meta['arm_user_plan_ids'] = wc_wpf_armember_crm_value_to_plan_ids(
                $user_meta['arm_user_plan_ids'],
                true
            );
        }

        return $user_meta;
    },
    10,
    2
);
