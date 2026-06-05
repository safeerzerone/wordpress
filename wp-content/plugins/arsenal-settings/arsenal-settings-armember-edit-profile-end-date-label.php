<?php
/**
 * ARMember edit profile: show "No expiry date" instead of "Never Expires" in Current Membership End Date.
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current front-end request is the ARMember edit profile page.
 *
 * @return bool
 */
function arsenal_settings_is_armember_edit_profile_page() {
	if ( ! is_page() ) {
		return false;
	}

	$page = get_queried_object();
	if ( ! $page instanceof WP_Post ) {
		return false;
	}

	if ( 'edit_profile' === $page->post_name ) {
		return true;
	}

	$arm_global_settings = get_option( 'arm_global_settings', array() );
	if ( is_array( $arm_global_settings ) && isset( $arm_global_settings['page_settings']['edit_profile_page_id'] ) ) {
		return (int) $arm_global_settings['page_settings']['edit_profile_page_id'] === (int) $page->ID;
	}

	return false;
}

/**
 * Replace recurring membership End Date label on the edit profile page.
 *
 * @return void
 */
function arsenal_settings_armember_edit_profile_end_date_label_script() {
	if ( ! arsenal_settings_is_armember_edit_profile_page() ) {
		return;
	}
	?>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			document.querySelectorAll('.arm_current_membership_list_item_plan_end').forEach(function (endDateCell) {
				if (endDateCell.textContent.trim() === 'Never Expires') {
					endDateCell.textContent = 'No expiry date';
				}
			});
		});
	</script>
	<?php
}
add_action( 'wp_footer', 'arsenal_settings_armember_edit_profile_end_date_label_script', 20 );
