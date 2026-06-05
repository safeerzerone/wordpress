<?php
/**
 * ARMember: replace "Never Expires" with "No expiry date" on profile and in emails.
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replacement label for recurring memberships without a fixed expiry.
 *
 * @return string
 */
function arsenal_settings_armember_no_expiry_date_label() {
	return 'No expiry date';
}

/**
 * ARMember text domains that use the "Never Expires" string.
 *
 * @return string[]
 */
function arsenal_settings_armember_never_expires_text_domains() {
	return array( 'armember-membership', 'ARMember' );
}

/**
 * Replace ARMember "Never Expires" string at translation time.
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $domain      Text domain.
 * @return string
 */
function arsenal_settings_armember_filter_never_expires_gettext( $translation, $text, $domain ) {
	if ( 'Never Expires' === $text && in_array( $domain, arsenal_settings_armember_never_expires_text_domains(), true ) ) {
		return arsenal_settings_armember_no_expiry_date_label();
	}

	return $translation;
}
add_filter( 'gettext', 'arsenal_settings_armember_filter_never_expires_gettext', 20, 3 );

/**
 * Fallback for email bodies that may already contain the literal string.
 *
 * @param string $content Email content.
 * @return string
 */
function arsenal_settings_armember_filter_never_expires_email_content( $content ) {
	if ( ! is_string( $content ) || $content === '' || strpos( $content, 'Never Expires' ) === false ) {
		return $content;
	}

	return str_replace( 'Never Expires', arsenal_settings_armember_no_expiry_date_label(), $content );
}
add_filter( 'arm_change_email_content', 'arsenal_settings_armember_filter_never_expires_email_content', 20 );
add_filter( 'arm_change_email_content_with_user_detail', 'arsenal_settings_armember_filter_never_expires_email_content', 20 );
add_filter( 'arm_change_advanced_email_communication_email_notification', 'arsenal_settings_armember_filter_never_expires_email_content', 20 );

add_action('plugins_loaded', function () {

    // Change checkout text
    add_filter('gettext', function ($translated, $text, $domain) {

        if (!function_exists('is_checkout') || !is_checkout()) {
            return $translated;
        }

        $replacements = array(
            'Billing details' => 'Member Details',
            'Your order'      => 'Membership Summary',
            'Product'         => 'Membership Plan',
            'Subtotal'        => 'Membership Fee',
            'Total'           => 'Amount Due Today',
            'Place order'     => 'Submit',
        );

        if (isset($replacements[$translated])) {
            return $replacements[$translated];
        }

        return $translated;

    }, 20, 3);

    // Change Place Order button text
    add_filter('woocommerce_order_button_text', function () {
        return 'Submit';
    });

    // Remove quantity (× 1) from checkout order review
    add_filter('woocommerce_checkout_cart_item_quantity', '__return_empty_string');

});