<?php
/**
 * Plugin Name: WooCommerce Custom Thank You Redirect
 * Description: Redirect WooCommerce checkout success page to a custom thank you page.
 * Version: 1.5
 * Author: Zerone
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/** ARMember plan ID for recurring annual (see wc_wpf_armember_plan_maps). */
if ( ! defined( 'WC_CUSTOM_THANKYOU_RECURRING_PLAN_ID' ) ) {
    define( 'WC_CUSTOM_THANKYOU_RECURRING_PLAN_ID', 1 );
}

/** User meta `payment_method_for_cms` must match this to set `remove_recurring_subscription` true. */
if ( ! defined( 'WC_CUSTOM_THANKYOU_CMS_PAYMENT_BACS_DIRECT_DEBIT' ) ) {
    define( 'WC_CUSTOM_THANKYOU_CMS_PAYMENT_BACS_DIRECT_DEBIT', 'Bacs Direct Debit' );
}

/**
 * Only users with payment_method_for_cms = Bacs Direct Debit get remove_recurring_subscription set true.
 *
 * @param int $user_id User ID.
 */
function wc_custom_thankyou_user_qualifies_for_remove_recurring_flag( $user_id ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) {
        return false;
    }

    $cms_method = get_user_meta( $user_id, 'payment_method_for_cms', true );
    $cms_method = is_string( $cms_method ) ? trim( $cms_method ) : '';

    return $cms_method === WC_CUSTOM_THANKYOU_CMS_PAYMENT_BACS_DIRECT_DEBIT;
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

        $arm_meta_crm = $crm->get_crm_field( 'arm_user_plan_2' );
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

        if ( array_key_exists( 'arm_user_plan_2', $user_meta ) ) {
            $user_meta['arm_user_plan_2'] = wc_wpf_armember_crm_value_to_plan_ids(
                $user_meta['arm_user_plan_2'],
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

/**
 * Default user meta: subscription_plan for new registrations (including WooCommerce customers).
 */
add_action( 'user_register', 'wc_custom_thankyou_set_subscription_plan_on_register', 10, 1 );

function wc_custom_thankyou_set_subscription_plan_on_register( $user_id ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) {
        return;
    }

    update_user_meta( $user_id, 'subscription_plan', 'membership approved' );
    update_user_meta( $user_id, 'remove_recurring_subscription', false );
}

/**
 * Normalize ARMember plan IDs from user meta to a unique list of integers.
 *
 * @param mixed $value Stored meta value.
 * @return int[]
 */
function wc_custom_thankyou_normalize_arm_plan_ids( $value ) {
    if ( null === $value || '' === $value ) {
        return array();
    }
    if ( ! is_array( $value ) ) {
        return array( (int) $value );
    }
    $ids = array();
    foreach ( $value as $v ) {
        $ids[] = (int) $v;
    }

    return array_values( array_unique( $ids ) );
}

/**
 * When admin (or ARMember) drops recurring plan from arm_user_plan_ids, flag remove_recurring_subscription.
 * Fires before DB write so get_user_meta still reflects the previous value.
 */
add_action( 'update_user_meta', 'wc_custom_thankyou_sync_remove_recurring_on_plan_ids_update', 10, 4 );

function wc_custom_thankyou_sync_remove_recurring_on_plan_ids_update( $meta_id, $user_id, $meta_key, $new_value ) {
    if ( 'arm_user_plan_ids' !== $meta_key ) {
        return;
    }
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) {
        return;
    }

    $old_ids = wc_custom_thankyou_normalize_arm_plan_ids( get_user_meta( $user_id, 'arm_user_plan_ids', true ) );
    $new_ids = wc_custom_thankyou_normalize_arm_plan_ids( $new_value );

    $rid           = (int) WC_CUSTOM_THANKYOU_RECURRING_PLAN_ID;
    $had_recurring = in_array( $rid, $old_ids, true );
    $has_recurring = in_array( $rid, $new_ids, true );

    if ( $had_recurring && ! $has_recurring && wc_custom_thankyou_user_qualifies_for_remove_recurring_flag( $user_id ) ) {
        update_user_meta( $user_id, 'remove_recurring_subscription', true );
    } elseif ( $has_recurring ) {
        update_user_meta( $user_id, 'remove_recurring_subscription', false );
    }
}

/**
 * Before arm_user_plan_ids is removed (e.g. last plan in ARMember Manage Plans). WordPress often calls
 * delete_user_meta( $id, 'arm_user_plan_ids' ) without a meta value, so deleted_user_meta's value is empty.
 */
add_action( 'delete_user_meta', 'wc_custom_thankyou_sync_remove_recurring_on_plan_ids_pre_delete', 10, 4 );

function wc_custom_thankyou_sync_remove_recurring_on_plan_ids_pre_delete( $meta_ids, $user_id, $meta_key, $meta_value ) {
    if ( 'arm_user_plan_ids' !== $meta_key ) {
        return;
    }
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) {
        return;
    }

    $ids = wc_custom_thankyou_normalize_arm_plan_ids( get_user_meta( $user_id, 'arm_user_plan_ids', true ) );
    if ( in_array( (int) WC_CUSTOM_THANKYOU_RECURRING_PLAN_ID, $ids, true ) && wc_custom_thankyou_user_qualifies_for_remove_recurring_flag( $user_id ) ) {
        update_user_meta( $user_id, 'remove_recurring_subscription', true );
    }
}

/**
 * ARMember admin: Members → Manage Plans popup removes a plan via arm_user_plan_action (arm_action=delete).
 * That path calls arm_clear_user_plan_detail then fires arm_after_cancel_subscription on success — not always
 * a single update_user_meta edge case, so we tie the flag to the cancelled plan ID and current meta.
 */
add_action( 'arm_after_cancel_subscription', 'wc_custom_thankyou_on_arm_after_cancel_subscription', 20, 4 );

function wc_custom_thankyou_on_arm_after_cancel_subscription( $user_id, $plan_obj, $cancel_plan_action, $plan_data ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 || ! is_object( $plan_obj ) ) {
        return;
    }

    $plan_id = isset( $plan_obj->ID ) ? (int) $plan_obj->ID : 0;
    if ( $plan_id !== (int) WC_CUSTOM_THANKYOU_RECURRING_PLAN_ID ) {
        return;
    }

    $remaining = wc_custom_thankyou_normalize_arm_plan_ids( get_user_meta( $user_id, 'arm_user_plan_ids', true ) );
    if ( ! in_array( (int) WC_CUSTOM_THANKYOU_RECURRING_PLAN_ID, $remaining, true ) && wc_custom_thankyou_user_qualifies_for_remove_recurring_flag( $user_id ) ) {
        update_user_meta( $user_id, 'remove_recurring_subscription', true );
    }
}