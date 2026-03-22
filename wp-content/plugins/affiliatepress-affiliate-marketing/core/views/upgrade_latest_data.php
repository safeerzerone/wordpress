<?php 

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $AffiliatePress, $wpdb, $affiliatepress_version;

$affiliatepress_old_version = get_option('bookingpress_version', true);

if (version_compare($affiliatepress_old_version, '1.0.2', '<') ) {
    $AffiliatePress->affiliatepress_install_default_creative_data();
    if(!$AffiliatePress->affiliatepress_pro_install()){
        $AffiliatePress->affiliatepress_update_settings('default_commission_status','commissions_settings',2);
        $AffiliatePress->affiliatepress_update_all_auto_load_settings();
    }
}

if (version_compare($affiliatepress_old_version, '1.2', '<') ) 
{
    global $affiliatepress_tbl_ap_affiliate_form_fields,$AffiliatePress;
    $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_form_fields, array('ap_field_edit'=>1), array( 'ap_form_field_name' => 'ap_affiliates_payment_email' ));
    $AffiliatePress->affiliatepress_update_settings('default_url_type','affiliate_settings','affiliate_default_url');
}

if (version_compare($affiliatepress_old_version, '1.3', '<') ) 
{
    global $AffiliatePress;
    $AffiliatePress->affiliatepress_update_settings('visit_all','message_settings',esc_html__('All Visits', 'affiliatepress-affiliate-marketing'));
}

if (version_compare($affiliatepress_old_version, '1.5', '<') ) 
{
    global $AffiliatePress;
    $AffiliatePress->affiliatepress_update_settings('pagination_change_label','message_settings',esc_html__('Per Page', 'affiliatepress-affiliate-marketing'));

    $affiliatepress_confirm_password_settings = array(
        'enable_confirm_password' => 'true',
        'confirm_password_label' => esc_html__('Confirm Password', 'affiliatepress-affiliate-marketing'),
        'confirm_password_placeholder' => esc_html__('Enter your Confirm password', 'affiliatepress-affiliate-marketing'),
        'confirm_password_error_msg' => esc_html__('Please enter your confirm password', 'affiliatepress-affiliate-marketing'),
        'confirm_password_validation_msg' => esc_html__('Confirm password do not match', 'affiliatepress-affiliate-marketing'),
    );
    $AffiliatePress->affiliatepress_update_settings('confirm_password_field', 'field_settings' , maybe_serialize($affiliatepress_confirm_password_settings));
}

if (version_compare($affiliatepress_old_version, '1.7', '<') ) 
{
    global $affiliatepress_tbl_ap_affiliate_visits;
    $affiliatepress_affiliates_col_added = $wpdb->get_results( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'ap_visit_iso_code'", DB_NAME, $affiliatepress_tbl_ap_affiliate_visits ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm
	if ( empty( $affiliatepress_affiliates_col_added ) ) {
		$wpdb->query( "ALTER TABLE `{$affiliatepress_tbl_ap_affiliate_visits}` ADD `ap_visit_iso_code` varchar(10) default NULL AFTER `ap_visit_country`" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm
	}		
}

if (version_compare($affiliatepress_old_version, '1.8', '<') ) 
{
    global $AffiliatePress;
    $AffiliatePress->affiliatepress_update_settings('dashboard_chart_earnings','message_settings',esc_html__('Earnings', 'affiliatepress-affiliate-marketing'));
    $AffiliatePress->affiliatepress_update_settings('dashboard_chart_commisisons','message_settings',esc_html__('Commissions', 'affiliatepress-affiliate-marketing'));
}

if (version_compare($affiliatepress_old_version, '2.1', '<') ) {
    global $affiliatepress_tbl_ap_payments;
    $affiliatepress_payment_col_added = $wpdb->get_results( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'ap_payment_visit'", DB_NAME, $affiliatepress_tbl_ap_payments ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_payments is a table name. false alarm
	if ( empty( $affiliatepress_payment_col_added ) ) {
		$wpdb->query( "ALTER TABLE `{$affiliatepress_tbl_ap_payments}` ADD `ap_payment_visit` INT(11) default 0 AFTER `ap_payment_status`" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_payments is a table name. false alarm
	}		
}

if ( version_compare( $affiliatepress_old_version, '2.2', '<' ) ) {
    global $AffiliatePress,$affiliatepress_commissions,$affiliatepress_tbl_ap_payouts,$affiliatepress_tbl_ap_affiliates;
    $AffiliatePress->affiliatepress_update_settings('easy_digital_downloads_disable_commission_on_upgrade','integrations_settings',"true");
    $AffiliatePress->affiliatepress_update_settings('minimum_payment_order','commissions_settings',1);

    $affiliatepress_affiliates_col_added = $wpdb->get_results( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'ap_payment_min_order'", DB_NAME, $affiliatepress_tbl_ap_payouts ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm
	if ( empty( $affiliatepress_affiliates_col_added ) ) {
		$wpdb->query( "ALTER TABLE `{$affiliatepress_tbl_ap_payouts}` ADD `ap_payment_min_order` varchar(255) default NULL AFTER `ap_payment_min_amount`" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm
	}		

    $affiliatepress_affiliates_col_added = $wpdb->get_results( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'ap_affiliates_note'", DB_NAME, $affiliatepress_tbl_ap_affiliates ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm
	if ( empty( $affiliatepress_affiliates_col_added ) ) {
		$wpdb->query( "ALTER TABLE `{$affiliatepress_tbl_ap_affiliates}` ADD `ap_affiliates_note` TEXT DEFAULT NULL AFTER `ap_affiliates_promote_us`" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm
	}	

    $affiliatepress_commissions->affiliatepress_commission_customer_update();
}

if ( version_compare( $affiliatepress_old_version, '2.3', '<' ) ) {

    global $AffiliatePress;

    $affiliatepress_update_settings = array();
    $affiliatepress_minimum_amount_label = $AffiliatePress->affiliatepress_get_settings('payment_minimum_amount_label', 'message_settings');

    if(empty($affiliatepress_minimum_amount_label)){
        $affiliatepress_update_settings[] = array(
            'settings_key' => 'payment_minimum_amount_label',
            'settings_type' => 'message_settings',
            'settings_value' => esc_html__('Minimum Amount', 'affiliatepress-affiliate-marketing'),
        );
    }

    $affiliatepress_update_settings[] = array('settings_key' => 'custome_link_delete_confirm','settings_type' => 'message_settings', 'settings_value' => esc_html__('Are you sure you want to delete this Link?', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'delete_custome_link_label', 'settings_type' => 'message_settings','settings_value' => esc_html__('Delete', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'no_label','settings_type' => 'message_settings','settings_value' => esc_html__('No', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array( 'settings_key' => 'yes_label', 'settings_type' => 'message_settings','settings_value' => esc_html__('Yes', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array( 'settings_key' => 'affiliate_link_limit','settings_type' => 'affiliate_settings','settings_value' => 50);
    $affiliatepress_update_settings[] = array('settings_key' => 'affiliate_pending_register_message','settings_type' => 'message_settings','settings_value' => esc_html__('Your affiliate account is currently under review. You will be notified by email once it has been approved.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'affiliate_already_registered_message','settings_type' => 'message_settings','settings_value' => esc_html__('Your account is already registered as an affiliate.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'affiliate_user_block_message','settings_type' => 'message_settings','settings_value' => esc_html__('Sorry, Affiliate user temporarily blocked by admin.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'link_limit_reached_error','settings_type' => 'message_settings','settings_value' => esc_html__('You cannot add a custom affiliate link because the maximum limit has been reached.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'affiliate_link_delete','settings_type' => 'message_settings','settings_value' => esc_html__('Affiliate Link has been deleted successfully.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'link_copied','settings_type' => 'message_settings','settings_value' => esc_html__('Link copied successfully.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'file_upload_type_validation','settings_type' => 'message_settings','settings_value' => esc_html__('Please upload jpg,jpeg,png or webp file only.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'file_upload_limit_validation','settings_type' => 'message_settings','settings_value' => esc_html__('Please upload maximum 1 MB file only.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'not_allow_affiliate_register','settings_type' => 'message_settings','settings_value' => esc_html__('Sorry! you are not allowed to access the affiliate panel.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'link_empty_validation','settings_type' => 'message_settings','settings_value' => esc_html__('Please add page link.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'link_pattern_validation','settings_type' => 'message_settings','settings_value' => esc_html__('Please enter a valid URL.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'link_campaign_name_empty_validation','settings_type' => 'message_settings','settings_value' => esc_html__('Please add campaign name.', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'login_username_empty_validation','settings_type' => 'message_settings','settings_value' => esc_html__('Please enter username or email address', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'login_password_empty_validation','settings_type' => 'message_settings','settings_value' => esc_html__('Please enter password', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'forget_password_empty_validation','settings_type' => 'message_settings','settings_value' => esc_html__('Please enter email address', 'affiliatepress-affiliate-marketing'));
    $affiliatepress_update_settings[] = array('settings_key' => 'memberpress_disable_commission_on_upgrade','settings_type' => 'integrations_settings','settings_value' => "true");

    if(!empty($affiliatepress_update_settings)){
        foreach ($affiliatepress_update_settings as $affiliatepress_settings) {
            $affiliatepress_affiliate_settings_key = $affiliatepress_settings['settings_key'];
            $affiliatepress_affiliate_settings_type = $affiliatepress_settings['settings_type'];
            $affiliatepress_affiliate_settings_value = $affiliatepress_settings['settings_value'];

            $AffiliatePress->affiliatepress_update_settings($affiliatepress_affiliate_settings_key,$affiliatepress_affiliate_settings_type,$affiliatepress_affiliate_settings_value);
        }
    }
}

$affiliatepress_new_version = '2.3';
update_option('affiliatepress_new_version_installed', 1);
update_option('affiliatepress_version', $affiliatepress_new_version);
update_option('affiliatepress_updated_date_' . $affiliatepress_new_version, current_time('mysql'));