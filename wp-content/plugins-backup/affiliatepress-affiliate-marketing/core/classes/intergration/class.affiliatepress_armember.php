<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_armember') ){

    class affiliatepress_armember Extends AffiliatePress_Core{
    
        private $affiliatepress_integration_slug;

         /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){  
            
            global $affiliatepress_is_armember_active ;
            $affiliatepress_is_armember_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'armember';

            if($this->affiliatepress_armember_commission_add() && $affiliatepress_is_armember_active){

                /**Add Pending or Approved Commission */
                add_action( 'arm_after_add_transaction', array( $this, 'affiliatepress_add_referral_transaction' ), 11, 1 );

                /**Commission data validation */
                add_filter( 'affiliatepress_commission_validation',array($this,'affiliatepress_armember_commission_validation_func'),15,5);

                /**Add Approved Commission */
                add_action( 'arm_after_accept_bank_transfer_payment', array($this,'affiliatepress_approve_commission'), 15, 3 );
                
                /**Add disable option Settings */
                add_action( 'arm_display_field_add_membership_plan', array( $this, 'affiliatepress_display_field_add_membership_plan_page_func' ) );

                /**Save Disable option settings */
                add_filter( 'arm_befor_save_field_membership_plan', array( $this, 'affiliatepress_before_save_field_membership_plan' ), 10, 2 );

                add_filter( 'arm_add_arm_entries_value', array( $this, 'affiliatepress_add_affiliate_armember_data' ), 10, 1 );

                add_filter( 'affiliatepress_get_affiliate_cookie_armember', array( $this, 'affiliatepress_modify_affiliate_cookie_data' ), 10, 3 );

                add_action('admin_enqueue_scripts', array( $this, 'affiliatepress_armember_enqueue_js' ), 11); 
            }

            if($affiliatepress_is_armember_active){
                /* Add armember Backend Product List */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_armember_product_func'),10,3); 

                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_link_order_func'),10,3); 
                
                
            }
        }

         /**
         * Function For armember Js add
         *
         * @return void
         */
        function affiliatepress_armember_enqueue_js()
        {
            global $arm_lite_version; 
            if (!empty($arm_lite_version) && version_compare($arm_lite_version, '5.0', '>=') ) {
                wp_register_script('affiliatepress_armember', AFFILIATEPRESS_URL . 'js/affiliatepress_armember.js', array('jquery'), AFFILIATEPRESS_VERSION);// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
                wp_enqueue_script('affiliatepress_armember');
            }
        }

        function affiliatepress_modify_affiliate_cookie_data($affiliatepress_get_cookie,$affiliatepress_plan_data,$affiliatepress_cookie_type){

            global $wpdb,$ARMember,$affiliatepress_commission_debug_log_id;

            $user_id = isset($affiliatepress_plan_data['arm_user_id']) ? $affiliatepress_plan_data['arm_user_id'] : 0;
            if($user_id == 0){ return; }

            $entry_id = get_user_meta($user_id, 'arm_entry_id');

            $arm_tbl_entry = $ARMember->tbl_arm_entries;
            $entry_data_value = $wpdb->get_row($wpdb->prepare("SELECT `arm_entry_value` FROM `{$arm_tbl_entry}` WHERE `arm_user_id` = %d AND `arm_entry_id` = %d ", $user_id, $entry_id[0]), ARRAY_A); //phpcs:ignore

            if(!empty($entry_data_value) && isset($entry_data_value['arm_entry_value'])){
                $entry_data = maybe_unserialize($entry_data_value['arm_entry_value']);

                if($affiliatepress_cookie_type == "affiliate"){
                    $affiliatepress_get_store_cookie = isset($_COOKIE['affiliatepress_ref_cookie']) ? intval($_COOKIE['affiliatepress_ref_cookie']) :$affiliatepress_get_cookie;                
                    if(($affiliatepress_get_store_cookie <= 0 ) && isset($entry_data['affiliatepress_ref_affiliate_id'])){
                        $affiliatepress_get_store_cookie = isset($entry_data['affiliatepress_ref_affiliate_id']) ? $entry_data['affiliatepress_ref_affiliate_id'] : $affiliatepress_get_cookie;
                    }
                    $affiliatepress_get_cookie = $affiliatepress_get_store_cookie;
                }elseif ($affiliatepress_cookie_type == "visit") {
                    $affiliatepress_get_store_visit_cookie = isset($_COOKIE['affiliatepress_visitor_id']) ? intval($_COOKIE['affiliatepress_visitor_id']) : $affiliatepress_get_cookie;                
                    if(($affiliatepress_get_store_visit_cookie <= 0 ) && isset($entry_data['affiliatepress_ref_affiliate_visitor_id'])){
                        $affiliatepress_get_store_visit_cookie = isset($entry_data['affiliatepress_ref_affiliate_visitor_id']) ? $entry_data['affiliatepress_ref_affiliate_visitor_id'] : $affiliatepress_get_cookie ;
                    }
                    $affiliatepress_get_cookie = $affiliatepress_get_store_visit_cookie;   
                }
            }

            return $affiliatepress_get_cookie;
        }

        function affiliatepress_add_affiliate_armember_data( $entry_post_data ) {
            global $affiliatepress_tracking,$affiliatepress_commission_debug_log_id;
            $entry_post_data['affiliatepress_ref_affiliate_id'] = isset($_COOKIE['affiliatepress_ref_cookie']) ? absint( $_COOKIE['affiliatepress_ref_cookie'] ) : 0;
            $entry_post_data['affiliatepress_ref_affiliate_visitor_id'] = isset($_COOKIE['affiliatepress_visitor_id']) ? intval($_COOKIE['affiliatepress_visitor_id']) : 0;

            $affiliatepress_log_msg = "Add Cookie data in entry value & Entry affiliate id=".$entry_post_data['affiliatepress_ref_affiliate_id'] ."& visistor Entry data = ".$entry_post_data['affiliatepress_ref_affiliate_visitor_id'];
            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Set Entry Cookie data', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
	        return $entry_post_data;
        }

        /**
         * Function for get order page link for commission referance order
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_link_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){
                $affiliatepress_ap_commission_order_link   = '<a target="_blank" class="ap-refrance-link" href="'.admin_url("admin.php?page=arm_transactions&arm_log_id=".$affiliatepress_ap_commission_reference_id).'");"> '. $affiliatepress_ap_commission_reference_id .' </a>';
                $affiliatepress_ap_commission_reference_id = $affiliatepress_ap_commission_order_link;
            }            
            return $affiliatepress_ap_commission_reference_id;
        }

        /**
         * Function for get source product for backend commission add/edit
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param  string $affiliatepress_ap_commission_source
         * @param  string $affiliatepress_search_product_str
         * @return array
        */
        function affiliatepress_get_armember_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){

            global $wpdb;
        
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){
        
                $affiliatepress_existing_products_data = array();
                $affiliatepress_tbl_arm_subscription_plans = $this->affiliatepress_tablename_prepare($wpdb->prefix . 'arm_subscription_plans'); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arm_subscription_plans' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

                $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT arm_subscription_plan_id FROM {$affiliatepress_tbl_arm_subscription_plans} WHERE arm_subscription_plan_name LIKE %s",'%' . $wpdb->esc_like($affiliatepress_search_product_str) . '%'),ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_arm_subscription_plans is a table name. false alarm 
    
                $affiliatepress_plan_ids = array_column($affiliatepress_results, 'arm_subscription_plan_id');

                if($affiliatepress_plan_ids){
                    foreach ($affiliatepress_plan_ids as $affiliatepress_plan_id) {

                        $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT arm_subscription_plan_name FROM {$affiliatepress_tbl_arm_subscription_plans} WHERE arm_subscription_plan_id =  %d",$affiliatepress_plan_id),ARRAY_A );// phpcs:ignore  WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_arm_subscription_plans is a table name. false alarm 

                        $affiliatepress_plan_name = !empty($affiliatepress_results) ? $affiliatepress_results[0]['arm_subscription_plan_name'] : '';

                        if(!empty($affiliatepress_plan_name))
                        {
                            $affiliatepress_existing_product_data[] = array(
                                'value' => $affiliatepress_plan_id,
                                'label' => $affiliatepress_plan_name
                            );
                        }
                    }    

                    $affiliatepress_existing_products_data[] = array(
                        'category'     => esc_html__('Select Source Product', 'affiliatepress-affiliate-marketing'),
                        'product_data' => $affiliatepress_existing_product_data,
                    );  
                    
                }
                
                $affiliatepress_existing_source_product_data = $affiliatepress_existing_products_data;
        
            }
        
            return $affiliatepress_existing_source_product_data;
        }
        
        
        /**
         * ARMember plan display affiliatepress option
         *
         * @param  array $affiliatepress_plan_options
         * @return array
         */
        function affiliatepress_display_field_add_membership_plan_page_func($affiliatepress_plan_options){

            global $arm_lite_version;
            if (!empty($arm_lite_version) && version_compare($arm_lite_version, '5.0', '>=') ) {
                $affiliatepress_commission_nonce_armember = wp_create_nonce('affiliatepress_commission_nonce_armember');
                ?>
                <div class="arm_spacing_div"></div>
                    <div class="arm_plan_price_section arm_form_main_content">
                        <div class="arm_form_header_label"><?php esc_html_e('AffiliatePress Commission Settings', 'affiliatepress-affiliate-marketing'); ?></div>
                        <div id="arm_plan_price_box_content" class="arm_plan_price_box">
                            <div class="page_sub_content">
                                <table class="form-table">
                                    <tr class="form-field form-required arm_plan_price_type">
                                        <td class="arm_padding_top_0">
                                            <div class="arm_upgrade_downgrade_section_switch">
												<div class="armswitch arm_global_setting_switch arm_vertical_align_middle" >
													<input type="checkbox" id="affiliatepress_commission_disable_armember" value="1" class="armswitch_input" name="arm_subscription_plan_options[affiliatepress_commission_disable_armember]"/>
													<label for="affiliatepress_commission_disable_armember" class="armswitch_label arm_min_width_40" ></label>
												</div>
												<label for="affiliatepress_commission_disable_armember" class="arm_padding_left_10 arm_font_size_14 arm_field_hint"><?php esc_html_e('Disable Commission For This Plan', 'affiliatepress-affiliate-marketing'); ?><i class="arm_helptip_icon armfa armfa-question-circle" title="<?php esc_html_e('Turn on AffiliatePress Commission to disable commission calculations for this plan and prevent affiliates from earning on it.', 'affiliatepress-affiliate-marketing'); ?>"></i></label>
												
												<span class ="arm_font_size_14 arm_margin_top_10 " style="float:left;width:50%;position:relative;top:5px;font-weight:400px;color: #6E7E9E;"><?php esc_html_e( 'Enable AffiliatePress Commission to prevent adding commission for this plan.', 'affiliatepress-affiliate-marketing'); ?></span>
											</div>
                                        </td>
                                    </tr>	
                                    <?php
                                        $affiliatepress_extra_Settings = "";
                                        $affiliatepress_extra_Settings .= apply_filters( 'affiliatepress_armember_add_product_settings', $affiliatepress_extra_Settings ,$affiliatepress_plan_options);

                                        echo $affiliatepress_extra_Settings;//phpcs:ignore  
                                    ?>
                                </table>
                            </div>
                        </div>
                    </div> 
                <?php
            }else{
                $affiliatepress_arm_commission_settings = $this->affiliatepress_set_plan_settings_content($affiliatepress_plan_options, 'armember_action');
                echo $affiliatepress_arm_commission_settings; //phpcs:ignore       
            }
        }

        /**
         * Function For Armember Disable Settings add
         *
         * @param  array $affiliatepress_plan_options
         * @param  bool $affiliatepress_armaff_flag
         * @return string
         */
        function affiliatepress_set_plan_settings_content( $affiliatepress_plan_options, $affiliatepress_armaff_flag = 'armember_action' ) {
        
            $affiliatepress_armaff_plan_settings = '';

            if($affiliatepress_armaff_flag == 'armember_action'){
                $affiliatepress_armaff_plan_settings .= '<div class="arm_solid_divider"></div>';
            }

            $affiliatepress_commission_nonce_armember = wp_create_nonce('affiliatepress_commission_nonce_armember');
            $affiliatepress_armaff_plan_settings .= '<input name="arm_subscription_plan_options[affiliatepress_commission_nonce_armember]" id="affiliatepress_commission_nonce_armember" type="hidden" value="' . esc_attr( $affiliatepress_commission_nonce_armember ) . '" />';

            $affiliatepress_commission_disable_armember = (!empty($affiliatepress_plan_options["affiliatepress_commission_disable_armember"])) ? $affiliatepress_plan_options["affiliatepress_commission_disable_armember"] : 0;

            $affiliatepress_armaff_plan_settings .= '<div id="arm_plan_price_box_content" class="arm_plan_price_box arm_affiliatepress_commission_meta_box">';
            $affiliatepress_armaff_plan_settings .= '<div class="page_sub_content">';
                if($affiliatepress_armaff_flag == 'armember_action'){
                    $affiliatepress_armaff_plan_settings .= '<div class="page_sub_title">'. esc_html__('AffiliatePress Commission Settings','affiliatepress-affiliate-marketing') . '</div>';
                    $affiliatepress_arm_page = isset($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : ''; //phpcs:ignore
                    $affiliatepress_armaff_plan_settings .= '<input type="hidden" name="page" id="page" value="'. $affiliatepress_arm_page .'" />';
                }
                $affiliatepress_armaff_plan_settings .= '<table class="form-table">';
                    $affiliatepress_ap_disable_commission_isChecked = checked($affiliatepress_commission_disable_armember, 1, false);

                    $affiliatepress_plan_settings = "";
                    $affiliatepress_plan_settings .= '<tr class="form-field form-required ">';
                        $affiliatepress_plan_settings .= '<th><label>'. esc_html__('Disable Commission For This Plan' ,'affiliatepress-affiliate-marketing') . '</label></th>';
                        $affiliatepress_plan_settings .= '<td>';
                            $affiliatepress_plan_settings .= '<div class="armclear"></div>';
                            $affiliatepress_plan_settings .= '<div class="armswitch arm_global_setting_switch" style="vertical-align: middle;">';
                                $affiliatepress_plan_settings .= '<input type="checkbox" id="affiliatepress_commission_disable_armember" '.esc_html($affiliatepress_ap_disable_commission_isChecked).' value="1" class="armswitch_input" name="arm_subscription_plan_options[affiliatepress_commission_disable_armember]"/>';
                                $affiliatepress_plan_settings .= '<label for="affiliatepress_commission_disable_armember" class="armswitch_label" style="min-width:40px;"></label>';
                            $affiliatepress_plan_settings .= '</div>&nbsp;';

                            $affiliatepress_plan_settings .= '<span style="float:left;width:100%;position:relative;top:5px;left:5px;">'. esc_html__('Enable AffiliatePress Commission to prevent adding commission for this plan.','affiliatepress-affiliate-marketing').'</span>';
                            $affiliatepress_plan_settings .= '<div class="armclear"></div>';
                        $affiliatepress_plan_settings .= '</td>';
                    $affiliatepress_plan_settings .= '</tr>';

                    $affiliatepress_armaff_plan_settings .=$affiliatepress_plan_settings;

                    $affiliatepress_extra_Settings = "";
                    $affiliatepress_extra_Settings .= apply_filters( 'affiliatepress_armember_add_product_settings', $affiliatepress_extra_Settings ,$affiliatepress_plan_options);

                    if(!empty($affiliatepress_extra_Settings))
                    {
                        $affiliatepress_armaff_plan_settings .=$affiliatepress_extra_Settings;
                    }

                $affiliatepress_armaff_plan_settings .= '</table>';

            $affiliatepress_armaff_plan_settings .= '</div>';
            $affiliatepress_armaff_plan_settings .= '</div>';

            return $affiliatepress_armaff_plan_settings;
        
        }
        
        /**
         * Function For Armember Disable Settings Save
         *
         * @param  array $affiliatepress_plan_options
         * @param  array $affiliatepress_posted_data
         * @return array
         */
        function affiliatepress_before_save_field_membership_plan($affiliatepress_plan_options, $affiliatepress_posted_data){

            if (!empty($arm_lite_version) && version_compare($arm_lite_version, '5.0', '<') ) {

                if (empty($affiliatepress_posted_data['arm_subscription_plan_options']['affiliatepress_commission_nonce_armember'])) {
                    return;
                }
    
                if (wp_verify_nonce( $affiliatepress_posted_data['arm_subscription_plan_options']['affiliatepress_commission_nonce_armember'], 'affiliatepress_commission_nonce_armember' )){  // phpcs:ignore
    
                    $affiliatepress_plan_options['affiliatepress_commission_disable_armember'] = isset($affiliatepress_posted_data['arm_subscription_plan_options']['affiliatepress_commission_disable_armember']) ? $affiliatepress_posted_data['arm_subscription_plan_options']['affiliatepress_commission_disable_armember'] : 0;
    
                    $affiliatepress_plan_options = apply_filters( 'affiliatepress_armember_settings_save', $affiliatepress_plan_options ,$affiliatepress_posted_data );
                }
            }

            return $affiliatepress_plan_options;
        }
        
        /**
         * Function For Armember integration settings check 
         *
         * @return void
         */
        function affiliatepress_armember_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_armember = $AffiliatePress->affiliatepress_get_settings('enable_armember', 'integrations_settings');
            if($affiliatepress_enable_armember != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For check Armember plugin
         *
         * @return void
         */
        function affiliatepress_check_plugin_active()
        {
            $affiliatepress_flag = true;
            
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            if ( is_plugin_active( 'armember-membership/armember-membership.php' ) || is_plugin_active( 'armember/armember.php' ) ) {
                $affiliatepress_flag = true;
            }else{
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }
        
                        
        /**
         * ARMember validation 
         *
         * @param  array $affiliatepress_commission_validation
         * @param  integer $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  integer $affiliatepress_order_id
         * @param  array $affiliatepress_plan
         * @return array
         */
        function affiliatepress_armember_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_plan){
            
            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){

                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 

                    if($affiliatepress_earn_commissions_own_orders == 'false')
                    {
                        $affiliatepress_billing_email = isset($affiliatepress_plan['arm_payer_email']) ? sanitize_email($affiliatepress_plan['arm_payer_email'])  : '';   
                        if($AffiliatePress->affiliatepress_affiliate_has_email( $affiliatepress_affiliate_id, $affiliatepress_billing_email ) ) {                   
                            $affiliatepress_commission_validation['variant']   = 'error';
                            $affiliatepress_commission_validation['title']     = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                            $affiliatepress_commission_validation['msg']       = esc_html__( 'Pending commission was not created because the customer is also the affiliate.', 'affiliatepress-affiliate-marketing');                                            
                        }
                    }
                }

                return $affiliatepress_commission_validation;
            }

            return $affiliatepress_commission_validation;
        }        
        
        /**
         * Function For Armember commission add
         *
         * @param  array $affiliatepress_plan_data
         * @return void
         */
        function affiliatepress_add_referral_transaction($affiliatepress_plan_data) {

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id,$wpdb,$arm_subscription_plans ;

            $affiliatepress_order_id = isset($affiliatepress_plan_data['arm_log_id']) ? intval( $affiliatepress_plan_data['arm_log_id'] ) : '';

            $affiliatepress_plan_id = !empty($affiliatepress_plan_data['arm_plan_id']) ? intval($affiliatepress_plan_data['arm_plan_id']) : '';

            $affiliatepress_log_msg = "ARMember add transaction commission Log";
            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug . $affiliatepress_log_msg, 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_plan_data, $affiliatepress_commission_debug_log_id);

            if(!isset($affiliatepress_plan_data['arm_transaction_status']) || ( $affiliatepress_plan_data['arm_transaction_status'] != "success" && $affiliatepress_plan_data['arm_payment_gateway'] != 'bank_transfer') )
            {
                return;
            }

            $affiliatepress_user_id = !empty($affiliatepress_plan_data['arm_user_id']) ? intval($affiliatepress_plan_data['arm_user_id']) : 0;
            $affiliatepress_plan_id = !empty($affiliatepress_plan_data['arm_plan_id']) ? intval($affiliatepress_plan_data['arm_plan_id']) : 0;
            $affiliatepress_payment_gateway = !empty($affiliatepress_plan_data['arm_payment_gateway']) ? intval($affiliatepress_plan_data['arm_payment_gateway']) : 0;

            $affiliatepress_armember_is_recurring_payment = $this->affiliatepress_armember_is_plan_recurring($affiliatepress_user_id, $affiliatepress_plan_id, $affiliatepress_payment_gateway);

            if( $affiliatepress_armember_is_recurring_payment ){

                $affiliatepress_log_msg = "commission was not created because the payment is alredy add.";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' commission was not created ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }else{
                $affiliatepress_log_msg = "commission created start";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' commission was not created ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
            }

            $affiliatepress_order_id = isset($affiliatepress_plan_data['arm_log_id']) ? intval( $affiliatepress_plan_data['arm_log_id'] ) : '';

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate($this->affiliatepress_integration_slug,$affiliatepress_plan_data);
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit($this->affiliatepress_integration_slug,$affiliatepress_plan_data);   

            $affiliatepress_log_msg = "Affiliate ID : ".$affiliatepress_affiliate_id;
            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;

            $affiliatepress_affiliate_id_data = array('order_id'=>$affiliatepress_order_id);
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_affiliate_id_data );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_plan_data);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant'])) ? sanitize_text_field($affiliatepress_commission_validation['variant']) : '';

                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_armember_withtaxvalue = isset($affiliatepress_plan_data['arm_amount']) ? floatval($affiliatepress_plan_data['arm_amount']) : 0;

            $affiliatepress_armember_extra_details = isset($affiliatepress_plan_data['arm_extra_vars']) ? sanitize_text_field($affiliatepress_plan_data['arm_extra_vars']) : '';

            $affiliatepress_armember_extra_details = unserialize($affiliatepress_armember_extra_details);

            $affiliatepress_armember_planamount = isset($affiliatepress_armember_extra_details['plan_amount']) ? floatval($affiliatepress_armember_extra_details['plan_amount']) : 0;

            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('armember_exclude_taxes', 'integrations_settings');       

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_commission_type = 'subscription';
            $affiliatepress_commission_type = !empty($affiliatepress_commission_type) ? sanitize_text_field($affiliatepress_commission_type):'';

            $affiliatepress_order_referal_amount = 0;

            /* Add Commission Customer Here */
            $affiliatepress_customer_args = array(
                'email'   	   => !empty($affiliatepress_plan_data['arm_payer_email']) ? sanitize_email($affiliatepress_plan_data['arm_payer_email']) : '',
                'user_id' 	   => !empty($affiliatepress_plan_data['arm_user_id']) ? intval($affiliatepress_plan_data['arm_user_id']) : '',
                'first_name'   => !empty($affiliatepress_plan_data['arm_first_name']) ? sanitize_email($affiliatepress_plan_data['arm_first_name']) : '',
                'last_name'	   => !empty($affiliatepress_plan_data['arm_last_name']) ? sanitize_email($affiliatepress_plan_data['arm_last_name']) : '',
                'affiliate_id' => $affiliatepress_affiliate_id
            );

            $affiliatepress_customer_commisison_add = true;
            $affiliatepress_customer_commisison_add = apply_filters('affiliatepress_validate_customer_for_commission', $affiliatepress_customer_commisison_add, $affiliatepress_customer_args,$this->affiliatepress_integration_slug);

            if(!$affiliatepress_customer_commisison_add){
                return;
            }

            $affiliatepress_customer_id = $AffiliatePress->affiliatepress_add_commission_customer( $affiliatepress_customer_args );
            $affiliatepress_customer_id = !empty($affiliatepress_customer_id) ? intval($affiliatepress_customer_id) : 0;

            if ( $affiliatepress_customer_id ) {
                $affiliatepress_debug_log_msg = sprintf( 'Customer #%s has been successfully processed.', $affiliatepress_customer_id );    
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Customer Created', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);                     
            } else {
                $affiliatepress_debug_log_msg = 'Customer could not be processed due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', 'Customer Not Created', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
            }

            $affiliatepress_plan_id = !empty($affiliatepress_plan_data['arm_plan_id']) ? intval($affiliatepress_plan_data['arm_plan_id']) : '';
            $affiliatepress_plan_name = $this->affiliatepress_armember_get_subscription_plan_name($affiliatepress_plan_id);

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_order_referal_amount = !empty($affiliatepress_armember_withtaxvalue) ? floatval($affiliatepress_armember_withtaxvalue) : 0;
                $affiliatepress_plan_tax_amount  =isset($affiliatepress_armember_extra_details['tax_amount']) ? floatval($affiliatepress_armember_extra_details['tax_amount']) : 0;

                if ( $affiliatepress_exclude_taxes == 'true' ) {
                    $affiliatepress_order_referal_amount = $affiliatepress_order_referal_amount - $affiliatepress_plan_tax_amount ;
                }

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => $affiliatepress_commission_type,
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => 0,
                    'order_id'     => $affiliatepress_order_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_order_referal_amount, $affiliatepress_plan_data['arm_currency'], $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_plan_id,
                    'product_name'         => $affiliatepress_plan_name,
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );
            }
            else
            {
                $affiliatepress_plan_amount = !empty($affiliatepress_armember_planamount) ? floatval($affiliatepress_armember_planamount) : 0;

                if ( $affiliatepress_exclude_taxes == 'false' ) {
                    $affiliatepress_plan_amount = !empty($affiliatepress_armember_withtaxvalue) ? floatval($affiliatepress_armember_withtaxvalue) : 0;
                }

                $affiliatepress_plan_id = !empty($affiliatepress_plan_data['arm_plan_id']) ? intval($affiliatepress_plan_data['arm_plan_id']) : '';
                $affiliatepress_plan_name = $this->affiliatepress_armember_get_subscription_plan_name($affiliatepress_plan_id);
                $affiliatepress_plan_name = !empty($affiliatepress_plan_name) ? sanitize_text_field($affiliatepress_plan_name):'';

                $affiliatepress_armember_product = array(
                    'product_id'=>$affiliatepress_plan_id,
                    'source'=>$this->affiliatepress_integration_slug
                );
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_armember_product );
    
                if($affiliatepress_product_disable){
    
                    return;
                }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_plan_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_order_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_plan_amount, $affiliatepress_plan_data['arm_currency'], $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_plan_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_plan_id,
                    'product_name'         => $affiliatepress_plan_name,
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $affiliatepress_plan_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );

                $affiliatepress_commission_products_ids[] = $affiliatepress_plan_id;
                $affiliatepress_commission_products_name[] = $affiliatepress_plan_name;
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();

            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_order_id, $affiliatepress_plan_data);

            if(!empty($affiliatepress_commission_final_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_final_validation['variant']))?sanitize_text_field($affiliatepress_commission_final_validation['variant']):'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_final_validation['msg']))?$affiliatepress_commission_final_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Added', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);
                    return;
                }                
            }

            $affiliatepress_ip_address = $AffiliatePress->affiliatepress_get_ip_address();

            $affiliatepress_commission_products_ids_string = (is_array($affiliatepress_commission_products_ids) && !empty($affiliatepress_commission_products_ids))?implode(',',$affiliatepress_commission_products_ids):'';

            $affiliatepress_commission_products_name_string = (is_array($affiliatepress_commission_products_name) && !empty($affiliatepress_commission_products_name))?implode(',',$affiliatepress_commission_products_name):'';

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_armember_withtaxvalue,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp'))// phpcs:ignore
            );

            $affiliatepress_commission_data['ap_commission_status'] = 2;

            $affiliatepress_tbl_arm_payment_log = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'arm_payment_log' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arm_payment_log' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_armaff_entry = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_arm_payment_log, 'arm_transaction_status', 'WHERE arm_log_id  = %d', array( $affiliatepress_order_id ), '', '', '', false, true,ARRAY_A);

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_ap_transaction_status = isset($affiliatepress_armaff_entry['arm_transaction_status']) ? sanitize_text_field($affiliatepress_armaff_entry['arm_transaction_status']) : '';
            if($affiliatepress_ap_transaction_status == "canceled"){
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Created', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', 'Commission was not created because the plan status is canceled.', $affiliatepress_commission_debug_log_id);
                return ;
            }

            if(!empty($affiliatepress_armaff_entry))
            {
                $affiliatepress_updated_commission_status = 1;
                if($affiliatepress_default_commission_status != "auto")
                {
                    $affiliatepress_updated_commission_status = $affiliatepress_default_commission_status;
                    if( $affiliatepress_ap_transaction_status == 'success' ){
                        $affiliatepress_commission_data['ap_commission_status'] = $affiliatepress_default_commission_status;
                    }
                }
                else
                {
                    if( $affiliatepress_ap_transaction_status == 'success' ){
                        $affiliatepress_commission_data['ap_commission_status'] = 1;
                    }
                }
            }

            /* Insert The Commission */
            $affiliatepress_ap_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
            if($affiliatepress_ap_commission_id == 0){
                $affiliatepress_debug_log_msg = 'commission could not be inserted due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
            }else{

                $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;

                if( $affiliatepress_ap_transaction_status == 'success' ){
                    if($affiliatepress_updated_commission_status == 1){
                        do_action('affiliatepress_after_commissions_status_change',$affiliatepress_ap_commission_id,$affiliatepress_updated_commission_status,2);
                    }
                    
                }
                $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data);
                $affiliatepress_debug_log_msg = sprintf( 'commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
            }
        }
        
        /**
         * Function For Check Armember plan recurring
         *
         * @param  integer $affiliatepress_user_id
         * @param  integer $affiliatepress_user_plan
         * @param  string $affiliatepress_payment_gateway
         * @return bool
         */
        function affiliatepress_armember_is_plan_recurring($affiliatepress_user_id, $affiliatepress_user_plan, $affiliatepress_payment_gateway = ''){

            global $wpdb,$affiliatepress_commission_debug_log_id; 

            $affiliatepress_is_recurring = false;
            $arm_user_plan_ids = get_user_meta($affiliatepress_user_id,'arm_user_plan_ids',true);

            if(!empty($arm_user_plan_ids) && in_array($affiliatepress_user_plan,$arm_user_plan_ids)){
                $arm_user_plan = get_user_meta($affiliatepress_user_id,'arm_user_plan_'.$affiliatepress_user_plan,true);
                
                $arm_completed_recurring = isset($arm_user_plan['arm_completed_recurring']) ? $arm_user_plan['arm_completed_recurring'] : '';

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Recurring Completed Paymnets', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $arm_completed_recurring, $affiliatepress_commission_debug_log_id);          

                if($arm_completed_recurring > 1){
                    $affiliatepress_is_recurring =  true;
                }else{
                    $affiliatepress_tbl_activity = $this->affiliatepress_tablename_prepare($wpdb->prefix . 'arm_activity'); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arm_subscriptarm_activityion_plans' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                    $affiliatepress_get_first_payment_date = $wpdb->get_var($wpdb->prepare("SELECT arm_date_recorded FROM {$affiliatepress_tbl_activity} WHERE arm_user_id = %d && arm_item_id = %d ORDER BY arm_date_recorded DESC",$affiliatepress_user_id,$affiliatepress_user_plan));// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_arm_subscription_plan is a table name. false alarm 

                    $affiliatepress_tbl_arm_payment_log = $this->affiliatepress_tablename_prepare($wpdb->prefix . 'arm_payment_log'); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arm_payment_log' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                    $affiliatepress_total_recurring_paymnet = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_arm_payment_log, 'COUNT(arm_log_id)', 'WHERE arm_user_id  = %d  && arm_plan_id = %d && arm_created_date >=%s', array( $affiliatepress_user_id,$affiliatepress_user_plan,$affiliatepress_get_first_payment_date), '', '', '', true, false,ARRAY_A));

                    if($affiliatepress_total_recurring_paymnet > 1){
                        $affiliatepress_is_recurring =  true;
                    }
                }
            }
            
            return $affiliatepress_is_recurring;
        }
        
        /**
         * Function For cehck Armember Plan Name
         *
         * @param  integer $affiliatepress_plan_id
         * @return string
         */
        function affiliatepress_armember_get_subscription_plan_name($affiliatepress_plan_id)
        {

            global $wpdb; 

            $affiliatepress_plan_id  = !empty($affiliatepress_plan_id) ? intval($affiliatepress_plan_id) : '';

            $affiliatepress_tbl_arm_subscription_plan = $this->affiliatepress_tablename_prepare($wpdb->prefix . 'arm_subscription_plans'); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arm_subscription_plans' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_arm_subscription_plan = $wpdb->get_var($wpdb->prepare("SELECT arm_subscription_plan_name FROM {$affiliatepress_tbl_arm_subscription_plan} WHERE arm_subscription_plan_id = %d",$affiliatepress_plan_id));// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_arm_subscription_plan is a table name. false alarm 

            return $affiliatepress_arm_subscription_plan;
        }
        
        /**
         * Function For Armember Approved Commission add 
         *
         * @param  integer $affiliatepress_user_id
         * @param  integer $affiliatepress_plan_id
         * @param  integer $affiliatepress_order_id
         * @return void
         */
        function affiliatepress_approve_commission( $affiliatepress_user_id, $affiliatepress_plan_id , $affiliatepress_order_id )
        {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id,$AffiliatePress,$affiliatepress_tracking;

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : pending commision data order id'.$affiliatepress_order_id, 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_all_commission_data, $affiliatepress_commission_debug_log_id);
            if(!empty($affiliatepress_all_commission_data)){

                foreach($affiliatepress_all_commission_data as $affiliatepress_commission_data){

                    if(!empty($affiliatepress_commission_data)){
            
                        $affiliatepress_ap_commission_status = (isset($affiliatepress_commission_data['ap_commission_status']))?intval($affiliatepress_commission_data['ap_commission_status']):0;
                        $affiliatepress_ap_commission_id     = (isset($affiliatepress_commission_data['ap_commission_id']))?intval($affiliatepress_commission_data['ap_commission_id']):0;
        
                        if($affiliatepress_ap_commission_status == 4){
                            $affiliatepress_debug_log_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_ap_commission_id );
                            continue;
                        }
                        
        
                        $affiliatepress_tbl_arm_payment_log = $this->affiliatepress_tablename_prepare($wpdb->prefix . 'arm_payment_log'); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arm_payment_log' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
        
                        $affiliatepress_armaff_entry = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_arm_payment_log, 'arm_transaction_status', 'WHERE arm_log_id  = %d', array( $affiliatepress_order_id ), '', '', '', false, true,ARRAY_A);

                        do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Payment Transaction data', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_armaff_entry, $affiliatepress_commission_debug_log_id);
        
                        if(!empty($affiliatepress_armaff_entry)){

                            $affiliatepress_ap_transaction_status = isset($affiliatepress_armaff_entry['arm_transaction_status']) ? sanitize_text_field($affiliatepress_armaff_entry['arm_transaction_status']) : '';
        
                            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();
        
                            if( $affiliatepress_ap_transaction_status == 'success' ||  $affiliatepress_ap_transaction_status == 1){
                                $affiliatepress_updated_commission_status = 1;
        
                               if($affiliatepress_default_commission_status != "auto"){
                                
                                    $affiliatepress_updated_commission_status = $affiliatepress_default_commission_status;
                                    $affiliatepress_commission_data = array(
                                        'ap_commission_updated_date' => current_time( 'mysql', true ),
                                        'ap_commission_status'       => $affiliatepress_default_commission_status
                                    );
                                }else{                        
                                    $affiliatepress_commission_data = array(
                                        'ap_commission_updated_date' => current_time( 'mysql', true ),
                                        'ap_commission_status'       => 1
                                    );
                                }    
            
                                if($affiliatepress_updated_commission_status != 2){

                                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
        
                                    do_action('affiliatepress_after_commissions_status_change',$affiliatepress_ap_commission_id,$affiliatepress_updated_commission_status,2);
                
                                    $affiliatepress_debug_log_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
                
                                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);

                                }
                             
                            }
                        }
                    }

                }

            }
        }
    }
}
global $affiliatepress_armember;
$affiliatepress_armember = new affiliatepress_armember();