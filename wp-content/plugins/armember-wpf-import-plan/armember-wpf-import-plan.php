<?php
/**
 * Plugin Name:       ARMember Plan Assign on WP Fusion Import
 * Description:       When WP Fusion imports a NEW user from the CRM (Import Users), assigns the ARMember membership plan named in the contact field "arsenal_active_plan". Existing users are never touched.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Zerone Consulting
 * License:           GPL-2.0-or-later
 * Text Domain:       armember-wpf-import-plan
 *
 * Standalone: depends only on ARMember and WP Fusion. No dependency on arsenal-settings.
 *
 * @package ARMWPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARMWPI_FIELD', 'arsenal_active_plan' );

/**
 * Write to the WP Fusion activity log (falls back to error_log when debugging).
 *
 * @param string $level   info|notice|error.
 * @param int    $user_id User ID.
 * @param string $message Message.
 */
function armwpi_log( $level, $user_id, $message ) {
	if ( function_exists( 'wpf_log' ) ) {
		wpf_log( $level, (int) $user_id, $message, array( 'source' => 'armember-plan-assign' ) );
	} elseif ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[ARMWPI] user ' . (int) $user_id . ': ' . $message );
	}
}

/**
 * Resolve a plan name from the CRM to an ARMember plan ID.
 *
 * 1. Filter `armwpi_plan_map` (array of lower-case name => plan ID) wins.
 * 2. Otherwise exact, case-insensitive, trimmed match on plan name among active, non-deleted plans.
 * Zero or multiple matches => null (never guess).
 *
 * @param string $name Plan name from the CRM field.
 * @return int|null
 */
function armwpi_resolve_plan_id( $name ) {
	global $wpdb;

	$key = strtolower( trim( $name ) );
	if ( '' === $key ) {
		return null;
	}

	$map = apply_filters( 'armwpi_plan_map', array() );
	if ( is_array( $map ) ) {
		$map = array_change_key_case( $map, CASE_LOWER );
		if ( isset( $map[ $key ] ) && (int) $map[ $key ] > 0 ) {
			return (int) $map[ $key ];
		}
	}

	$table = $wpdb->prefix . 'arm_subscription_plans';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT arm_subscription_plan_id FROM `{$table}` WHERE LOWER(TRIM(arm_subscription_plan_name)) = %s AND arm_subscription_plan_is_delete = 0 AND arm_subscription_plan_status = 1",
			$key
		)
	);

	return ( is_array( $ids ) && 1 === count( $ids ) ) ? (int) $ids[0] : null;
}

/**
 * Map a free-text payment method from the CRM ("PayPal", "paypal", "GoCardless", "Bank Direct Debit", ...)
 * to an ARMember-style gateway slug. Case, spaces and punctuation are ignored.
 *
 * @param string $label Raw value.
 * @return string paypal|gocardless|stripe|woocommerce, or '' when not recognised.
 */
function armwpi_gateway_from_label( $label ) {
	$flat = preg_replace( '/[^a-z0-9]/', '', strtolower( trim( (string) $label ) ) );
	if ( '' === $flat ) {
		return '';
	}
	if ( false !== strpos( $flat, 'paypal' ) || 0 === strpos( $flat, 'ppcp' ) || 0 === strpos( $flat, 'ppec' ) ) {
		return 'paypal';
	}
	if ( false !== strpos( $flat, 'gocardless' ) ) {
		return 'gocardless';
	}
	if ( 'card' === $flat || false !== strpos( $flat, 'stripe' ) ) {
		return 'stripe';
	}
	if ( 'woocommerce' === $flat || 'bankdirectdebit' === $flat ) {
		return 'woocommerce';
	}
	return '';
}

/**
 * Resolve the gateway for an imported member from the CRM fields (first recognised value wins).
 *
 * Filter `armwpi_payment_fields` changes the field list; filter `armwpi_payment_gateway` can override the result.
 *
 * @param int   $user_id   User ID.
 * @param array $user_meta Fields loaded from the CRM.
 * @return string Gateway slug or ''.
 */
function armwpi_resolve_gateway( $user_id, $user_meta ) {
	$fields  = apply_filters( 'armwpi_payment_fields', array( 'selected_payment_method', 'payment_method_for_cms', 'order_payment_method' ) );
	$gateway = '';
	foreach ( (array) $fields as $field ) {
		$value = isset( $user_meta[ $field ] ) ? $user_meta[ $field ] : get_user_meta( $user_id, $field, true );
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}
		$gateway = armwpi_gateway_from_label( is_scalar( $value ) ? (string) $value : '' );
		if ( '' !== $gateway ) {
			break;
		}
	}
	return (string) apply_filters( 'armwpi_payment_gateway', $gateway, $user_id, $user_meta );
}

/**
 * Hook: runs once for each user WP Fusion creates during an import.
 * (WP Fusion fires wpf_user_imported only for newly created users, after fields and tags are saved.)
 *
 * @param int   $user_id   New user ID.
 * @param array $user_meta Fields loaded from the CRM.
 */
function armwpi_assign_plan_on_import( $user_id, $user_meta = array() ) {
	$user_id = (int) $user_id;

	if ( ! class_exists( 'ARM_Plan' ) || ! has_action( 'arm_member_update_meta' ) ) {
		armwpi_log( 'error', $user_id, 'ARMember is not active; plan not assigned.' );
		return;
	}

	// New members only: never alter a user who already holds any ARMember plan.
	$existing = get_user_meta( $user_id, 'arm_user_plan_ids', true );
	if ( ! empty( $existing ) ) {
		armwpi_log( 'notice', $user_id, 'User already has an ARMember plan; skipped.' );
		return;
	}

	$field = apply_filters( 'armwpi_plan_field', ARMWPI_FIELD );
	$value = isset( $user_meta[ $field ] ) ? $user_meta[ $field ] : get_user_meta( $user_id, $field, true );
	if ( is_array( $value ) ) {
		$value = reset( $value );
	}
	$value = trim( wp_strip_all_tags( (string) $value ) );
	if ( '' === $value ) {
		armwpi_log( 'notice', $user_id, 'Field "' . $field . '" is empty; no plan assigned.' );
		return;
	}

	$plan_id = armwpi_resolve_plan_id( $value );
	if ( ! $plan_id ) {
		armwpi_log( 'error', $user_id, 'No unique active ARMember plan matches "' . $value . '"; no plan assigned.' );
		return;
	}

	$plan = new ARM_Plan( $plan_id );
	if ( ! $plan->exists() || ! $plan->is_active() ) {
		armwpi_log( 'error', $user_id, 'ARMember plan #' . $plan_id . ' is missing or inactive; no plan assigned.' );
		return;
	}

	/**
	 * Veto assignment, e.g. when user_membership_status is not "Active".
	 *
	 * @param bool  $assign    Default true.
	 * @param int   $user_id   User ID.
	 * @param array $user_meta CRM fields.
	 * @param int   $plan_id   Resolved plan ID.
	 */
	if ( ! apply_filters( 'armwpi_should_assign', true, $user_id, $user_meta, $plan_id ) ) {
		armwpi_log( 'notice', $user_id, 'Assignment vetoed by armwpi_should_assign filter.' );
		return;
	}

	// Optional original start date (Y-m-d), as supported by ARMember's own importer.
	$start = '';
	if ( ! empty( $user_meta['arm_subscription_start_date'] ) ) {
		$dt = DateTime::createFromFormat( 'Y-m-d', substr( (string) $user_meta['arm_subscription_start_date'], 0, 10 ) );
		if ( $dt && $dt->format( 'Y-m-d' ) === substr( (string) $user_meta['arm_subscription_start_date'], 0, 10 ) ) {
			$start = $dt->format( 'Y-m-d' );
		}
	}

	// Gateway from the CRM payment-method fields (PayPal, GoCardless, ...). Empty => ARMember default ("manual").
	$gateway = armwpi_resolve_gateway( $user_id, $user_meta );

	// Same payload ARMember's own CSV importer sends (class.arm_members.php, arm_add_import_user).
	$posted = array(
		'arm_user_plan'               => $plan_id,
		'payment_gateway'             => '' !== $gateway ? $gateway : 'manual',
		'arm_selected_payment_mode'   => 'manual_subscription',
		'arm_primary_status'          => 1,
		'arm_secondary_status'        => 0,
		'arm_subscription_start_date' => $start,
		'arm_user_import'             => true,
	);
	do_action( 'arm_member_update_meta', $user_id, $posted, 1 );

	/**
	 * Create ARMember's "Paid By admin" manual payment log(s) (arm_manual_update_user_data).
	 * Default false: it would record a full-price payment for historical members, and for recurring
	 * plans one log per elapsed billing cycle since the start date.
	 *
	 * @param bool     $write   Default false.
	 * @param int      $plan_id Plan ID.
	 * @param ARM_Plan $plan    Plan object.
	 * @param int      $user_id User ID.
	 */
	if ( apply_filters( 'armwpi_write_manual_payment_log', false, $plan_id, $plan, $user_id ) && ! $plan->is_free() ) {
		global $arm_members_class;
		if ( is_object( $arm_members_class ) && method_exists( $arm_members_class, 'arm_manual_update_user_data' ) ) {
			$arm_members_class->arm_manual_update_user_data( $user_id, $plan_id, $posted );
		}
	}

	/**
	 * ARMember writes arm_user_plan_<id> BEFORE it writes arm_user_plan_ids during this call, and does not fire
	 * arm_after_user_plan_change for it. Meta listeners that ran on the first write (arsenal-settings:
	 * current_arsenal_subscription, renewal_date, ...) therefore saw "no active plan". Re-run them now that the
	 * plan is fully assigned. Guarded so this plugin stays standalone.
	 */
	if ( function_exists( 'arsenal_settings_on_armember_plan_assigned' ) ) {
		arsenal_settings_on_armember_plan_assigned( $user_id, $plan_id );
	}
	do_action( 'armwpi_after_plan_assigned', $user_id, $plan_id );

	// Verify the result instead of assuming success.
	$after = get_user_meta( $user_id, 'arm_user_plan_ids', true );
	if ( is_array( $after ) && in_array( $plan_id, array_map( 'intval', $after ), true ) ) {
		$note = $plan->is_recurring() ? ' NOTE: recurring plan - gateway "' . ( '' !== $gateway ? $gateway : 'manual' ) . '", mode manual_subscription; next-due/gateway subscription linkage not set up by this plugin.' : '';
		armwpi_log( 'info', $user_id, 'Assigned ARMember plan #' . $plan_id . ' ("' . $value . '").' . $note );
	} else {
		armwpi_log( 'error', $user_id, 'ARMember did not record plan #' . $plan_id . ' ("' . $value . '") after assignment.' );
	}
}
add_action( 'wpf_user_imported', 'armwpi_assign_plan_on_import', 20, 2 );
