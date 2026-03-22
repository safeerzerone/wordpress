<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (is_ssl() ) {
    define('AFFILIATEPRESS_URL', str_replace('http://', 'https://', plugin_dir_url( __FILE__ )));
    define('AFFILIATEPRESS_HOME_URL', home_url('', 'https'));
} else {
    define('AFFILIATEPRESS_URL', plugin_dir_url( __FILE__ ));
    define('AFFILIATEPRESS_HOME_URL', home_url());
}

define('AFFILIATEPRESS_MENU_URL', admin_url() . 'admin.php?page=affiliatepress');

define('AFFILIATEPRESS_CORE_DIR', AFFILIATEPRESS_DIR . '/core');
define('AFFILIATEPRESS_MAIN_FILE', plugin_basename(__FILE__));
define('AFFILIATEPRESS_CLASSES_DIR', AFFILIATEPRESS_DIR . '/core/classes');
define('AFFILIATEPRESS_CLASSES_URL', AFFILIATEPRESS_URL . '/core/classes');

define('AFFILIATEPRESS_WIDGET_DIR', AFFILIATEPRESS_DIR . '/core/widgets');
define('AFFILIATEPRESS_WIDGET_URL', AFFILIATEPRESS_URL . '/core/widgets');

define('AFFILIATEPRESS_IMAGES_DIR', AFFILIATEPRESS_DIR . '/images');
define('AFFILIATEPRESS_IMAGES_URL', AFFILIATEPRESS_URL . 'images');

define('AFFILIATEPRESS_LIBRARY_DIR', AFFILIATEPRESS_DIR . '/lib');
define('AFFILIATEPRESS_LIBRARY_URL', AFFILIATEPRESS_URL . '/lib');

define('AFFILIATEPRESS_INC_DIR', AFFILIATEPRESS_DIR . '/inc');

define('AFFILIATEPRESS_VIEWS_DIR', AFFILIATEPRESS_DIR . '/core/views');
define('AFFILIATEPRESS_VIEWS_URL', AFFILIATEPRESS_URL . '/core/views');


if (! defined('AFFILIATEPRESS_FS_METHOD') ) {
    @define('AFFILIATEPRESS_FS_METHOD', 'direct');
}

global $affiliatepress_version, $affiliatepress_website_url;
$affiliatepress_version = "2.3";

$affiliatepress_website_url = "https://www.affiliatepressplugin.com/";

$affiliatepress_wpupload_dir = wp_upload_dir();
$affiliatepress_upload_dir   = $affiliatepress_wpupload_dir['basedir'] . '/affiliatepress-affiliate-marketing';
$affiliatepress_upload_url   = $affiliatepress_wpupload_dir['baseurl'] . '/affiliatepress-affiliate-marketing';

if (is_ssl() ) {
    $affiliatepress_upload_url = str_replace('http://', 'https://', $affiliatepress_upload_url);
}

global $affiliatepress_settings_table_exists, $affiliatepress_customize_settings_table_exists;

$affiliatepress_tmp_images_dir = $affiliatepress_upload_dir . '/temp_images';
$affiliatepress_tmp_images_url = $affiliatepress_upload_url . '/temp_images';
if (! is_dir($affiliatepress_upload_dir) ) {
    wp_mkdir_p($affiliatepress_upload_dir);
}

if (! is_dir($affiliatepress_tmp_images_dir) ) {
    wp_mkdir_p($affiliatepress_tmp_images_dir);
}

$affiliatepress_import_dir = $affiliatepress_upload_dir . '/import';
$affiliatepress_import_url = $affiliatepress_upload_url . '/import';

if (! is_dir($affiliatepress_import_dir) ) {
    wp_mkdir_p($affiliatepress_import_dir);
}

define('AFFILIATEPRESS_UPLOAD_DIR', $affiliatepress_upload_dir);
define('AFFILIATEPRESS_UPLOAD_URL', $affiliatepress_upload_url);
define('AFFILIATEPRESS_TMP_IMAGES_DIR', $affiliatepress_tmp_images_dir);
define('AFFILIATEPRESS_TMP_IMAGES_URL', $affiliatepress_tmp_images_url);

define('AFFILIATEPRESS_IMPORT_DIR', $affiliatepress_import_dir);
define('AFFILIATEPRESS_IMPORT_URL', $affiliatepress_import_url);

$affiliatepress_upload_css_dir = $affiliatepress_wpupload_dir['basedir'] . '/affiliatepress-affiliate-marketing/css';
$affiliatepress_upload_css_url = $affiliatepress_wpupload_dir['baseurl'] . '/affiliatepress-affiliate-marketing/css';
if (! is_dir($affiliatepress_upload_css_dir) ) {
    wp_mkdir_p($affiliatepress_upload_css_dir);
}
define('AFFILIATEPRESS_UPLOAD_CSS_DIR', $affiliatepress_upload_css_dir);
define('AFFILIATEPRESS_UPLOAD_CSS_URL', $affiliatepress_upload_css_url);

global $affiliatepress_user_status, $affiliatepress_user_type;


define('AFFILIATEPRESS_VERSION', $affiliatepress_version);

global $affiliatepress_ajaxurl;
$affiliatepress_ajaxurl = admin_url('admin-ajax.php');



/**
 * Plugin Main Class
*/
if( file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress-core.php') ){
    require_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress-core.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_fileupload_class.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_fileupload_class.php';
}

/* Email Notification Settings Added Here... */
if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_email_notifications.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_email_notifications.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_global_options.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_global_options.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_tracking.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_tracking.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_affiliates.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_affiliates.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_visits.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_visits.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_creative.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_creative.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_settings.php') ) {
   include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_settings.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_notifications.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_notifications.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_commissions.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_commissions.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_affiliate_fields.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_affiliate_fields.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_dashboard.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_dashboard.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_payout.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_payout.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_addons.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_addons.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_growth_tools.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_growth_tools.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.manage_intergration.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.manage_intergration.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/frontend/class.affiliatepress_affiliate_register.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/frontend/class.affiliatepress_affiliate_register.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/frontend/class.affiliatepress_affiliate_panel.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/frontend/class.affiliatepress_affiliate_panel.php';
}

if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/frontend/class.affiliatepress_creative_shortcode.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/frontend/class.affiliatepress_creative_shortcode.php';
}

/* widget Files */
if (file_exists(AFFILIATEPRESS_WIDGET_DIR . '/class.affiliatepress_frontwidget.php') ) {
    include_once AFFILIATEPRESS_WIDGET_DIR . '/class.affiliatepress_frontwidget.php';
}

/* Elementor File  */
if( file_exists( AFFILIATEPRESS_WIDGET_DIR . '/affiliatepress-elementor-widget/affiliatepress-elementor-widget.php' ) ){
    include_once AFFILIATEPRESS_WIDGET_DIR . '/affiliatepress-elementor-widget/affiliatepress-elementor-widget.php';
} 

/** wizard hooks */
if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_wizard.php') ) {
    include_once AFFILIATEPRESS_CLASSES_DIR . '/class.affiliatepress_wizard.php';
}

add_action('plugins_loaded', 'affiliatepress_load_textdomain');

/**
 * Loading plugin text domain
*/
function affiliatepress_load_textdomain(){
    load_plugin_textdomain('affiliatepress-affiliate-marketing', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

/* AffiliatePress Lite Update Code Add */

global $affiliatepress_api_url, $AffiliatePress, $affiliatepress_plugin_slug;

$affiliatepress_api_url = $AffiliatePress->affiliatepress_get_apiurl();
$affiliatepress_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'affiliatepress_check_for_plugin_update');

function affiliatepress_check_for_plugin_update( $checked_data ){

    $affiliatepress_lite_force_update_check = get_transient('affiliatepress_lite_force_update_check');
    if( !empty( $affiliatepress_lite_force_update_check ) ){
        global $affiliatepress_api_url, $affiliatepress_plugin_slug, $wp_version, $AffiliatePress, $affiliatepress_version;

        //Comment out these two lines during testing.
		if (empty($checked_data->checked)){
            return $checked_data;
        }

        $final_data = $AffiliatePress->affiliatepress_get_remote_post_params();

        $affiliatepress_args = array(
            'slug' => $affiliatepress_plugin_slug,
            'version' => $affiliatepress_version,
            'other_variables' => $AffiliatePress->affiliatepress_get_remote_post_params()
        );

        $affiliatepress_request_string = array(
            'body' => array(
                'action'  => 'basic_check',
                'request' => serialize( $affiliatepress_args ),
                'api-key' => md5( AFFILIATEPRESS_HOME_URL )
            ),
            'sslverify' => false,
            'user-agent' => 'APLITE-WordPress/'.$wp_version.';'.AFFILIATEPRESS_HOME_URL
        );
        
        //Start checking for an update
        $affiliatepress_raw_response = wp_remote_post( $affiliatepress_api_url, $affiliatepress_request_string );

        if( !is_wp_error( $affiliatepress_raw_response ) && ( $affiliatepress_raw_response['response']['code'] == 200 ) ){
            $response = @unserialize( $affiliatepress_raw_response['body'] );
        }
        
        if( isset( $response['access_request'] ) && !empty( $response['access_request'] ) && 'success' == $response['access_request'] ){
            if( isset( $response['access_package'] ) && !empty( $response['access_package'] ) ){
                $update_package = @unserialize( $response['access_package'] );                
                if( isset( $update_package ) && is_object( $update_package ) && is_object( $checked_data ) && !empty( $update_package ) ){
                    $checked_data->response[$affiliatepress_plugin_slug .'/' . $affiliatepress_plugin_slug .'.php'] = $update_package;
                }

            }
        }
    }

    return $checked_data;

}

add_filter( 'plugins_api', 'affiliatepress_plugin_api_call', 11, 3 );

function affiliatepress_plugin_api_call( $res, $action, $args ){

    $affiliatepress_lite_force_update_check = get_transient( 'affiliatepress_lite_force_update_check' );
    if( !empty( $affiliatepress_lite_force_update_check ) ){

        global $affiliatepress_plugin_slug, $affiliatepress_api_url, $wp_version, $affiliatepress_version;

		if ( !isset( $args->slug ) || ( $args->slug != $affiliatepress_plugin_slug ) ){
            return $res;
        }

        $args->version = $affiliatepress_version;

		$affiliatepress_request_string = array(
			'body' => array(
				'action' => $action,
				'request' => serialize($args),
				'api-key' => md5(AFFILIATEPRESS_HOME_URL)
			),
			'sslverify' => false,
			'user-agent' => 'APLITE-WordPress/' . $wp_version . '; ' . AFFILIATEPRESS_HOME_URL
		);

		$request = wp_remote_post($affiliatepress_api_url, $affiliatepress_request_string);

		if (is_wp_error($request)) {
			$res = new WP_Error('plugins_api_failed', '<p>'.esc_html__('An Unexpected HTTP Error occurred during the API request.', 'affiliatepress-affiliate-marketing') . "</p> <p><a href='?' onclick='document.location.reload(); return false;>".esc_html__('Try again','affiliatepress-affiliate-marketing')."</a></p>", $request->get_error_message());
		} else {
			
            if(!is_string($request['body'])){
                $res = unserialize($request['body']);
            }
            
			if ($res === false){
                $res = new WP_Error('plugins_api_failed', esc_html__('An unknown error occurred', 'affiliatepress-affiliate-marketing'), $request['body']);
            }
				
		}
    }

    return $res;
}

add_action( 'init', 'affiliatepress_lite_validate_plugin_update' );

function affiliatepress_lite_validate_plugin_update(){

    $affiliatepress_lite_force_update_check = get_transient( 'affiliatepress_lite_force_update_check' );

    if( empty( $affiliatepress_lite_force_update_check ) ){
        if( !function_exists( 'plugins_api' ) ){
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }

        $affiliatepress_validate_active_plugin = plugins_api(
            'plugin_information',
            array(
                'slug' => 'affiliatepress-affiliate-marketing',
                'fields' => array(
                    'sections' => false,
                    'versions' => true
                )
            )
        );

        if( is_wp_error( $affiliatepress_validate_active_plugin ) ){
            $expiration_sec = 60 * 60 * 24;
            set_transient( 'affiliatepress_lite_force_update_check', 1, $expiration_sec );
        }

    }

}

add_filter('plugin_action_links', 'affiliatepress_plugin_links', 10, 2);
function affiliatepress_plugin_links($links, $file){
    global $wp, $wpdb, $AffiliatePress,$affiliatepress_website_url;
    $main_file = plugin_basename(AFFILIATEPRESS_DIR . 'affiliatepress-affiliate-marketing.php');
    if ($file == $main_file && !is_plugin_active( 'affiliatepress-affiliate-marketing-pro/affiliatepress-affiliate-marketing-pro.php' ) ) {
        $link = '<a class="ap-plugin-upgrade-to-pro" title="' . esc_html__('Upgrade To Premium', 'affiliatepress-affiliate-marketing') . '" href='.$affiliatepress_website_url.'pricing/?utm_source=liteversion&utm_medium=plugin&utm_campaign=Upgrade+to+Premium&utm_id=affiliatepress_2" style="font-weight:bold;">' . esc_html__('Upgrade To Premium', 'affiliatepress-affiliate-marketing') . '</a>';
        array_unshift($links, $link); /* Add Link To First Position */
    }
    return $links;
}