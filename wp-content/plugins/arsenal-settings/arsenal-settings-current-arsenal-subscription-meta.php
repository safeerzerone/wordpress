<?php
/**
 * Sync user meta for ARMember subscription plans:
 * - `current_arsenal_subscription` — reflects the member's active plan (cleared on cancel).
 * - `arsenal_active_plan` — last assigned/subscribed plan title (unchanged on cancel).
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User meta key storing the current membership title (plain string).
 *
 * @return string
 */
function arsenal_settings_current_arsenal_subscription_meta_key() {
	return 'current_arsenal_subscription';
}

/**
 * User meta key storing the last assigned/subscribed plan title (plain string; not cleared on cancel).
 *
 * @return string
 */
function arsenal_settings_arsenal_active_plan_meta_key() {
	return 'arsenal_active_plan';
}

/**
 * Label stored when the member has no active ARMember plan.
 *
 * @return string
 */
function arsenal_settings_no_active_plan_label() {
	return 'No active plan';
}

/**
 * Active ARMember plan IDs for a user (not suspended, cancelled, or expired).
 *
 * @param int $user_id WordPress user ID.
 * @return array<int,int> Plan IDs.
 */
function arsenal_settings_get_user_active_armember_plan_ids( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return array();
	}

	$plan_ids = get_user_meta( $user_id, 'arm_user_plan_ids', true );
	$plan_ids = is_array( $plan_ids ) ? array_map( 'intval', $plan_ids ) : array();
	if ( empty( $plan_ids ) ) {
		return array();
	}

	$suspended = get_user_meta( $user_id, 'arm_user_suspended_plan_ids', true );
	$suspended = is_array( $suspended ) ? array_map( 'intval', $suspended ) : array();

	$active = array();
	foreach ( $plan_ids as $plan_id ) {
		if ( $plan_id < 1 ) {
			continue;
		}
		if ( function_exists( 'arsenal_settings_user_has_active_armember_plan' ) ) {
			if ( arsenal_settings_user_has_active_armember_plan( $user_id, $plan_id ) ) {
				$active[] = $plan_id;
			}
			continue;
		}
		if ( in_array( $plan_id, $suspended, true ) ) {
			continue;
		}
		$plan_data = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
		if ( ! is_array( $plan_data ) || empty( $plan_data ) ) {
			continue;
		}
		if ( ! empty( $plan_data['arm_cencelled_plan'] ) && 'yes' === (string) $plan_data['arm_cencelled_plan'] ) {
			continue;
		}
		$expire = isset( $plan_data['arm_expire_plan'] ) ? $plan_data['arm_expire_plan'] : '';
		if ( $expire !== '' && $expire !== null && (int) $expire > 0 && (int) $expire < (int) current_time( 'timestamp' ) ) {
			continue;
		}
		$active[] = $plan_id;
	}

	return array_values( array_unique( $active ) );
}

/**
 * Resolve the membership title to store for the user's current subscription.
 *
 * @param int $user_id            WordPress user ID.
 * @param int $preferred_plan_id  Plan ID just assigned/changed (optional).
 * @return string Membership title or empty string when none.
 */
function arsenal_settings_resolve_current_arsenal_subscription_title( $user_id, $preferred_plan_id = 0 ) {
	$user_id           = (int) $user_id;
	$preferred_plan_id = (int) $preferred_plan_id;

	$active_plan_ids = arsenal_settings_get_user_active_armember_plan_ids( $user_id );
	if ( empty( $active_plan_ids ) ) {
		return arsenal_settings_no_active_plan_label();
	}

	$plan_id = 0;
	if ( $preferred_plan_id > 0 && in_array( $preferred_plan_id, $active_plan_ids, true ) ) {
		$plan_id = $preferred_plan_id;
	} else {
		$plan_id = (int) $active_plan_ids[0];
	}

	global $arm_subscription_plans;
	if ( ! is_object( $arm_subscription_plans ) || ! method_exists( $arm_subscription_plans, 'arm_get_plan_name_by_id' ) ) {
		return '';
	}

	$name = $arm_subscription_plans->arm_get_plan_name_by_id( $plan_id );
	$name = is_string( $name ) ? trim( $name ) : '';
	return $name !== '' ? $name : arsenal_settings_no_active_plan_label();
}

/**
 * Update `current_arsenal_subscription` from active ARMember plans.
 *
 * @param int $user_id            WordPress user ID.
 * @param int $preferred_plan_id  Plan ID just assigned/changed (optional).
 */
function arsenal_settings_update_current_arsenal_subscription_meta( $user_id, $preferred_plan_id = 0 ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$title = arsenal_settings_resolve_current_arsenal_subscription_title( $user_id, $preferred_plan_id );
	update_user_meta( $user_id, arsenal_settings_current_arsenal_subscription_meta_key(), $title );
}

/**
 * Resolve an ARMember plan title by plan ID.
 *
 * @param int $plan_id ARMember plan ID.
 * @return string Plan title or empty string when unavailable.
 */
function arsenal_settings_resolve_armember_plan_title_by_id( $plan_id ) {
	$plan_id = (int) $plan_id;
	if ( $plan_id < 1 ) {
		return '';
	}

	global $arm_subscription_plans;
	if ( ! is_object( $arm_subscription_plans ) || ! method_exists( $arm_subscription_plans, 'arm_get_plan_name_by_id' ) ) {
		return '';
	}

	$name = $arm_subscription_plans->arm_get_plan_name_by_id( $plan_id );
	$name = is_string( $name ) ? trim( $name ) : '';
	return $name;
}

/**
 * Update `arsenal_active_plan` when a user subscribes or is assigned a plan.
 *
 * Unlike `current_arsenal_subscription`, this meta is not updated on cancellation.
 *
 * @param int $user_id WordPress user ID.
 * @param int $plan_id ARMember plan ID just assigned/subscribed.
 */
function arsenal_settings_update_arsenal_active_plan_meta( $user_id, $plan_id ) {
	$user_id = (int) $user_id;
	$plan_id = (int) $plan_id;
	if ( $user_id < 1 || $plan_id < 1 ) {
		return;
	}

	$title = arsenal_settings_resolve_armember_plan_title_by_id( $plan_id );
	if ( $title === '' ) {
		return;
	}

	update_user_meta( $user_id, arsenal_settings_arsenal_active_plan_meta_key(), $title );
}

/**
 * Normalize ARMember hook plan argument to a single plan ID.
 *
 * @param mixed $plan_id Plan ID or array of plan IDs.
 * @return int
 */
function arsenal_settings_normalize_armember_plan_id( $plan_id ) {
	if ( is_array( $plan_id ) ) {
		$plan_id = reset( $plan_id );
	}
	return (int) $plan_id;
}

/**
 * @param int   $user_id WordPress user ID.
 * @param mixed $plan_id ARMember plan ID.
 */
function arsenal_settings_on_armember_plan_assigned( $user_id, $plan_id ) {
	$normalized_plan_id = arsenal_settings_normalize_armember_plan_id( $plan_id );

	arsenal_settings_update_current_arsenal_subscription_meta( $user_id, $normalized_plan_id );
	arsenal_settings_update_arsenal_active_plan_meta( $user_id, $normalized_plan_id );
}

/**
 * Re-sync after a plan is removed or cancelled.
 *
 * @param int $user_id WordPress user ID.
 */
function arsenal_settings_on_armember_plan_removed( $user_id ) {
	arsenal_settings_update_current_arsenal_subscription_meta( $user_id, 0 );
}

/**
 * @param int   $user_id WordPress user ID.
 * @param mixed $plan    ARM_Plan object or plan ID (unused; re-syncs from user meta).
 */
function arsenal_settings_on_arm_after_cancel_subscription( $user_id, $plan ) {
	arsenal_settings_on_armember_plan_removed( $user_id );
}

/**
 * @param int   $user_id     WordPress user ID.
 * @param array $member_data Member form data (unused; re-syncs from user meta).
 */
function arsenal_settings_on_arm_after_update_user_profile( $user_id, $member_data ) {
	arsenal_settings_on_armember_plan_removed( $user_id );
}

add_action( 'arm_after_user_plan_change', 'arsenal_settings_on_armember_plan_assigned', 20, 2 );
add_action( 'arm_after_user_plan_change_by_admin', 'arsenal_settings_on_armember_plan_assigned', 20, 2 );
add_action( 'arm_after_cancel_subscription', 'arsenal_settings_on_arm_after_cancel_subscription', 99, 2 );
add_action( 'arm_after_update_user_profile', 'arsenal_settings_on_arm_after_update_user_profile', 99, 2 );
