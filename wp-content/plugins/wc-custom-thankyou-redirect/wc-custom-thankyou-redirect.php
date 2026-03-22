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