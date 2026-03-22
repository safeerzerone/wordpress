<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action('init','affiliatepress_add_integration_on_init');

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_woocommerce.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_woocommerce.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_armember.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_armember.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_edd.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_edd.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_memberpress.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_memberpress.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_surecart.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_surecart.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_easycart.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_easycart.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_restrict_content.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_restrict_content.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_lifterlms.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_lifterlms.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_give_wp.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_give_wp.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_ninja_forms.php')) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_ninja_forms.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_wp_forms.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_wp_forms.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_simple_membership.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_simple_membership.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_paid_memberships_pro.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_paid_memberships_pro.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_paid_memberships_subscriptions.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_paid_memberships_subscriptions.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_ultimate_membership_pro.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_ultimate_membership_pro.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_learndash.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_learndash.php';
}


function affiliatepress_add_integration_on_init(){
    if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_wp_simple_pay.php') ) {
        include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_wp_simple_pay.php';
    } 
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_masteriyo_lms.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_masteriyo_lms.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_learnpress.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_learnpress.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_accept_stripe_payment.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_accept_stripe_payment.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_getpaid.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_getpaid.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_gravity_forms.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_gravity_forms.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_arforms.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_arforms.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_download_manager.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_download_manager.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_bookingpress.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_bookingpress.php';
}
