<?php 
if (is_ssl()) {
    define('MEMBERSHIP_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . MEMBERSHIP_DIR_NAME));
    define('ARM_HOME_URL', home_url('','https'));
} else {
    define('MEMBERSHIP_URL', WP_PLUGIN_URL . '/' . MEMBERSHIP_DIR_NAME);  
    define('ARM_HOME_URL', home_url());
}

define('MEMBERSHIP_CORE_DIR', MEMBERSHIP_DIR . '/core');
define('MEMBERSHIP_CLASSES_DIR', MEMBERSHIP_DIR . '/core/classes');
define('MEMBERSHIP_CLASSES_URL', MEMBERSHIP_URL . '/core/classes');
define('MEMBERSHIP_WIDGET_DIR', MEMBERSHIP_DIR . '/core/widgets');
define('MEMBERSHIP_WIDGET_URL', MEMBERSHIP_URL . '/core/widgets');
define('MEMBERSHIP_IMAGES_DIR', MEMBERSHIP_DIR . '/images');
define('MEMBERSHIP_IMAGES_URL', MEMBERSHIP_URL . '/images');
define('MEMBERSHIP_LIBRARY_DIR', MEMBERSHIP_DIR . '/lib');
define('MEMBERSHIP_LIBRARY_URL', MEMBERSHIP_URL . '/lib');
define('MEMBERSHIP_INC_DIR', MEMBERSHIP_DIR . '/inc');
define('MEMBERSHIP_VIEWS_DIR', MEMBERSHIP_DIR . '/core/views');
define('MEMBERSHIP_VIEWS_URL', MEMBERSHIP_URL . '/core/views');
define('MEMBERSHIP_TXTDOMAIN', 'ARMember');
define('MEMBERSHIP_VIDEO_URL', 'https://www.youtube.com/embed/WhKgS2jv2xM');
define('MEMBERSHIP_DOCUMENTATION_URL', 'https://www.armemberplugin.com/documentation');
define('MEMBERSHIP_DOCUMENTATION_PAYMENT_GATEWAY_PAYPAL_URL', 'https://www.armemberplugin.com/documents/enable-interaction-with-paypal');
define('MEMBERSHIP_DOCUMENTATION_PAYMENT_GATEWAY_STRIPE_URL', 'https://www.armemberplugin.com/documents/enable-interaction-with-stripe');
define('MEMBERSHIP_DOCUMENTATION_PAYMENT_GATEWAY_AUTHORIZE_URL', 'https://www.armemberplugin.com/documents/enable-interaction-with-authorize-net');
define('MEMBERSHIP_DOCUMENTATION_PAYMENT_GATEWAY_2CHECKOUT_URL', 'https://www.armemberplugin.com/documents/enable-interaction-with-2checkout');

if(!defined('FS_METHOD')){
    define('FS_METHOD', 'direct');
}
if(!defined('ARMADDON_STORE_URL')){
    define( 'ARMADDON_STORE_URL', 'https://www.armemberplugin.com/');
}

/* Cornerstone */

global $armPrimaryStatus, $armSecondaryStatus;

/* DEBUG LOG CONSTANTS */
define("MEMBERSHIP_DEBUG_LOG", false); /* true - enable debug log (Default) & false - disable debug log */
define("MEMBERSHIP_DEBUG_LOG_TYPE", "ARM_ALL");
/* Possible Values
  ARM_ALL - Enable Debug Log for All types for restriction & redirection rules (Default).
  ARM_ADMIN_PANEL - Enable Debug Log for wordpress admin panel restriction & redirection rules.
  ARM_POSTS - Enable Debug Log for wordpress default posts for restriction & redirection rules.
  ARM_PAGES - Enable Debug Log for wordpress default pages for restriction & redirection rules.
  ARM_TAXONOMY - Enable Debug Log for all taxonomies for restriction & redirection rules.
  ARM_MENU - Enable Debug Log for wordpress Menu for restriction & redirection rules.
  ARM_CUSTOM - Enable Debug Log for all types of custom posts for restriction & redirection rules.
  ARM_SPECIAL_PAGE - Enable Debug Log for all types of special pages like Archive Page, Author Page, Category Page, etc.
  ARM_SHORTCODE - Enable Debug Log for all types of restriction & redirection rules applied using shortcodes
  ARM_MAIL - Enable Debug Log for all content before mail sent.
 */


global $arm_datepicker_loaded, $arm_avatar_loaded, $arm_file_upload_field, $bpopup_loaded, $arm_load_tipso, $arm_popup_modal_elements, $arm_is_access_rule_applied, $arm_load_icheck, $arm_font_awesome_loaded, $arm_inner_form_modal,$arm_forms_page_arr, $ARMemberAllowedHTMLTagsArray, $armember_check_plugin_copy, $arm_check_addon_copy_flag;

$armember_check_plugin_copy = 0;
$arm_is_access_rule_applied = 0;
$arm_check_addon_copy_flag = 0;
$arm_datepicker_loaded = $arm_avatar_loaded = $arm_file_upload_field = $bpopup_loaded = $arm_load_tipso = $arm_font_awesome_loaded = 0;
$arm_popup_modal_elements = array();
$arm_inner_form_modal = array();
$arm_forms_page_arr=array();
global $arm_case_types;
$arm_case_types = array(
    'admin_panel' => array(
        'protected' => false,
        'type' => 'redirect'
    ),
    'page' => array(
        'protected' => false,
        'type' => 'redirect'
    ),
    'post' => array(
        'protected' => false,
        'type' => 'redirect'
    ),
    'taxonomy' => array(
        'protected' => false,
        'type' => 'redirect'
    ),
    'menu' => array(
        'protected' => false,
        'type' => 'redirect'
    ),
    'custom' => array(
        'protected' => false,
        'type' => 'redirect'
    ),
    'special' => array(
        'protected' => false,
        'type' => 'redirect'
    ),
    'shortcode' => array(
        'protected' => false,
        'type' => 'redirect'
    ),
    'mail' => array(
        'protected' => false,
        'type' => 'redirect'
    )
);

$wpupload_dir = wp_upload_dir();
$upload_dir = $wpupload_dir['basedir'] . '/armember';
$upload_url = $wpupload_dir['baseurl'] . '/armember';
if (!is_dir($upload_dir)) {
    wp_mkdir_p($upload_dir);
}
define('MEMBERSHIP_UPLOAD_DIR', $upload_dir);
define('MEMBERSHIP_UPLOAD_URL', $upload_url);

/* Defining Membership Plugin Version */
global $arm_version, $arm_lite_compatibilty_version;
$arm_version = '7.1';
define('MEMBERSHIP_VERSION', $arm_version);
$arm_lite_compatibilty_version = '5.1';

global $arm_ajaxurl;
$arm_ajaxurl = admin_url('admin-ajax.php');

global $arm_errors;
$arm_errors = new WP_Error();

global $arm_widget_effects;

global $arm_default_user_details_text;

add_action('init','arm_check_for_wp_rename',1);
function arm_check_for_wp_rename(){
    $arm_rename_wp = new ARM_rename_wp();
}

/**
 * Plugin Main Class
 */
if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.armember.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.armember.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_setup_wizard.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_setup_wizard.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_members.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_members.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_manage_subscription.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_manage_subscription.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_restriction.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_restriction.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_modal_view_in_menu.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_modal_view_in_menu.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_payment_gateways.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_payment_gateways.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_shortcodes.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_shortcodes.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_paypal.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_paypal.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_authorize_net.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_authorize_net.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_global_settings.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_global_settings.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_membership_setup.php")){
   require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_membership_setup.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_member_forms.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_member_forms.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_buddypress_feature.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_buddypress_feature.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_members_directory.php")){
   require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_members_directory.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_subscription_plans.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_subscription_plans.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_wocommerce_feature.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_wocommerce_feature.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_transaction.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_transaction.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_crons.php")){
   require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_crons.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_manage_communication.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_manage_communication.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_drip_rules.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_drip_rules.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_members_badges.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_members_badges.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_members_activity.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_members_activity.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_stripe.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_stripe.php");
}

if( file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_stripe_sca.php") ){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_stripe_sca.php" );
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_2checkout.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_gateways_2checkout.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_manage_coupons.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_manage_coupons.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_social_feature.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_social_feature.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_pro_ration.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_pro_ration.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_access_rules.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_access_rules.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_multiple_membership_feature.php")){
   require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_multiple_membership_feature.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_email_settings.php")){
   require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_email_settings.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_spam_filter.php")){
  require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_spam_filter.php");
}

if(defined('MEMBERSHIPLITE_WIDGET_DIR') && file_exists(MEMBERSHIPLITE_WIDGET_DIR . "/class.arm_dashboard_widgets.php")){
	require_once( MEMBERSHIPLITE_WIDGET_DIR . "/class.arm_dashboard_widgets.php");
}

if(file_exists(MEMBERSHIP_WIDGET_DIR . "/class.arm_dashboard_widgets.php")){
	require_once( MEMBERSHIP_WIDGET_DIR . "/class.arm_dashboard_widgets.php");
}

if(defined('MEMBERSHIPLITE_WIDGET_DIR') && file_exists(MEMBERSHIPLITE_WIDGET_DIR . "/class.arm_widgetForm.php")){
	require_once( MEMBERSHIPLITE_WIDGET_DIR . "/class.arm_widgetForm.php");
}

if(defined('MEMBERSHIPLITE_WIDGET_DIR') && file_exists(MEMBERSHIPLITE_WIDGET_DIR . "/class.arm_widgetlatestMembers.php")){
   	require_once( MEMBERSHIPLITE_WIDGET_DIR . "/class.arm_widgetlatestMembers.php");
}

if(defined('MEMBERSHIPLITE_WIDGET_DIR') && file_exists(MEMBERSHIPLITE_WIDGET_DIR . "/class.arm_widgetloginwidget.php")){
	   require_once( MEMBERSHIPLITE_WIDGET_DIR . "/class.arm_widgetloginwidget.php");
}

if(file_exists(MEMBERSHIP_WIDGET_DIR . "/class.arm_allwidget.php")){
   require_once( MEMBERSHIP_WIDGET_DIR . "/class.arm_allwidget.php");
}
if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_report_analytics.php")){
  require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_report_analytics.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_mycred_feature_admin.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_mycred_feature_admin.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_user_private_content.php") ){
    require_once MEMBERSHIP_CLASSES_DIR . "/class.arm_user_private_content.php";
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_pay_per_post.php") ){
    require_once MEMBERSHIP_CLASSES_DIR . "/class.arm_pay_per_post.php";
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_common_hooks.php") ){
    require_once MEMBERSHIP_CLASSES_DIR . "/class.arm_common_hooks.php";
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_api_service_feature.php") ){
    require_once MEMBERSHIP_CLASSES_DIR . "/class.arm_api_service_feature.php";
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_updates_cron.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_updates_cron.php");
}

if(file_exists(MEMBERSHIP_CLASSES_DIR . "/class.arm_membership_optins.php")){
    require_once( MEMBERSHIP_CLASSES_DIR . "/class.arm_membership_optins.php");
}

if(defined('MEMBERSHIPLITE_CLASSES_DIR') && file_exists(MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_gutenberg_restriction.php") ){
    require_once( MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_gutenberg_restriction.php");
}

if(defined('MEMBERSHIPLITE_CLASSES_DIR') && file_exists(MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_beaver_builder_restriction.php")){
    require_once( MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_beaver_builder_restriction.php");
}

if( defined('MEMBERSHIPLITE_CLASSES_DIR') && file_exists(MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_divi_builder_restriction.php") ){
    require_once( MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_divi_builder_restriction.php");
}

if( defined('MEMBERSHIPLITE_CLASSES_DIR') && file_exists(MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_wpbakery_builder_restriction.php") ){
    require_once( MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_wpbakery_builder_restriction.php");
}

if( defined('MEMBERSHIPLITE_CLASSES_DIR') && file_exists(MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_fusion_builder_restriction.php") ){
    require_once( MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_fusion_builder_restriction.php");
}

if(defined('MEMBERSHIPLITE_CLASSES_DIR') && file_exists(MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_oxygen_builder_restriction.php") ){
    require_once( MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_oxygen_builder_restriction.php");
}

if(defined('MEMBERSHIPLITE_CLASSES_DIR') && file_exists(MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_siteorigin_builder_restriction.php")){
    require_once( MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_siteorigin_builder_restriction.php");
}

if(defined('MEMBERSHIPLITE_CLASSES_DIR') && file_exists(MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_bricks_builder_restriction.php")){
    require_once( MEMBERSHIPLITE_CLASSES_DIR . "/arm_builder/class.arm_bricks_builder_restriction.php");
}

global $arm_api_url, $arm_plugin_slug, $wp_version;


//Query monitor
register_uninstall_hook(MEMBERSHIP_DIR.'/armember.php', array('ARMember', 'uninstall'));


if( empty( $armember_check_plugin_copy ) )
{
    global $arm_members_activity;

    $arm_api_url = $arm_members_activity->armgetapiurl();
    $arm_plugin_slug = basename(dirname(__FILE__));

    add_filter('pre_set_site_transient_update_plugins', 'arm_check_for_plugin_update');

    function arm_check_for_plugin_update($checked_data) {
        global $arm_api_url, $arm_plugin_slug, $wp_version, $arm_members_activity, $arm_version, $ARMember;

        //Comment out these two lines during testing.
        if (empty($checked_data->checked))
            return $checked_data;

        $args = array(
            'slug' => $arm_plugin_slug,
            'version' => $arm_version,
            'other_variables' => $arm_members_activity->arm_get_remote_post_params(),
        );

        $request_string = array(
            'body' => array(
                'action' => 'basic_check',
                'request' => serialize($args),
                'api-key' => md5(ARM_HOME_URL)
            ),
            'sslverify' => false,
            'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL
        );

        // Start checking for an update
        $raw_response = wp_remote_post($arm_api_url, $request_string);

        if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
            $response = @unserialize($raw_response['body']);

        if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
            update_option('arm_update_token', $response->token);
            
        if(isset($response['access_request']) && !empty($response['access_request']) && $response['access_request'] == "success")
        {
            if(isset($response['access_package']) && !empty($response['access_package']))
            {
                $update_package = @unserialize($response['access_package']);
                if (isset($update_package) && is_object($update_package) && is_object($checked_data) && !empty($update_package))
                {
                    $checked_data->response[$arm_plugin_slug . '/' . $arm_plugin_slug . '.php'] = $update_package;
                    delete_option('arm_badgeupdaterequired');
                }
            }
        }else if(isset($response['access_request']) && !empty($response['access_request']) && $response['access_request'] == "error2"){
            $option_val = 1;
            $option_val = $option_val + get_option('arm_badgeupdaterequired');
            update_option('arm_badgeupdaterequired', $option_val);
            
            $updated_with_badges = $ARMember->arm_update_badges($option_val);
        }
        
        return $checked_data;
    }

    add_filter('plugins_api', 'arm_plugin_api_call', 11, 3);

    function arm_plugin_api_call($res, $action, $args) {
        global $arm_plugin_slug, $arm_api_url, $wp_version;

        if (!isset($args->slug) || ($args->slug != $arm_plugin_slug))
            return $res;

        // Get the current version
        $plugin_info = get_site_transient('update_plugins');
        $current_version = $plugin_info->checked[$arm_plugin_slug . '/' . $arm_plugin_slug . '.php'];
        $args->version = $current_version;

        $request_string = array(
            'body' => array(
                'action' => $action,
                'update_token' => get_site_option('arm_update_token'),
                'request' => serialize($args),
                'api-key' => md5(ARM_HOME_URL)
            ),
            'sslverify' => false,
            'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL
        );

        $request = wp_remote_post($arm_api_url, $request_string);

        if (is_wp_error($request)) {
            $res = new WP_Error('plugins_api_failed', '<p>'.esc_html__('An Unexpected HTTP Error occurred during the API request.', 'ARMember') . "</p> <p><a href='?' onclick='document.location.reload(); return false;>".esc_html__('Try again','ARMember')."</a></p>", $request->get_error_message());
        } else {
            $res = unserialize($request['body']);

            if ($res === false)
                $res = new WP_Error('plugins_api_failed', esc_html__('An unknown error occurred', 'ARMember'), $request['body']);
        }

        return $res;
    }
}
else 
{
    if( ! class_exists( 'armember_pro_updater' ) ) {
        require_once MEMBERSHIP_CLASSES_DIR . '/class.armember_pro_plugin_updater.php';
    }

    function armember_armember_plugin_updater() {
        global $arm_version;

        $plugin_slug_for_update = 'armember/armember.php';

        // To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
        $doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
        if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
            return;
        }

        $pkg_key = trim( get_option( 'arm_pkg_key' ) );
        $package = trim( get_option( 'arm_pkg' ) );

        // setup the updater
        $edd_updater = new armember_pro_updater(
            ARMADDON_STORE_URL,
            $plugin_slug_for_update,
            array(
                'version' => $arm_version,
                'license' => $pkg_key,
                'item_id' => $package,
                'author'  => 'Repute Infosystems',
                'beta'    => false,
            )
        );

    }
    add_action( 'init', 'armember_armember_plugin_updater' );
}