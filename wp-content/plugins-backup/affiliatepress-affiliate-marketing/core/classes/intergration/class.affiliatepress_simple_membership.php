<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_simple_membership') ){
    
    class affiliatepress_simple_membership Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){            
            
            //add_action( 'wp', array( &$this, 'affiliatepress_set_ref_in_cookie' ), 10 );

            global $affiliatepress_is_simple_membership_active ;
            $affiliatepress_is_simple_membership_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'simple_membership';
            if($this->affiliatepress_simple_membership_commission_add() && $affiliatepress_is_simple_membership_active){

                /**Add Pending COmmission */
                add_action('swpm_txn_record_saved', array($this, 'affiliatepress_pending_refral_add_swpm') ,10,3);

                /**Add Approved Commission */
                add_action('swpm_payment_ipn_processed', array($this, 'affiliatepress_complete_payment_swpm') ,10,1);

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_swpm_func'),15,5);

                /** add disable option */
                add_filter('swpm_admin_add_membership_level_ui', array($this, 'affiliatepress_custom_edit_membership_level_ui'), 10, 2);
                add_filter('swpm_admin_edit_membership_level_ui', array($this, 'affiliatepress_custom_edit_membership_level_ui'), 10, 2);

                /** save disable option */
                add_filter('swpm_admin_edit_membership_level', array($this, 'affiliatepress_save_custom_fields'), 10, 2);
            }

            if($affiliatepress_is_simple_membership_active){
                /**Get Order Link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_simple_membership_link_order_func'),10,3); 

                /* Add armember Backend Product List */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_simple_membership_product_func'),10,3); 
            }
        }
        
       /**
         * Function for get source product for backend commission add/edit
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param  string $affiliatepress_ap_commission_source
         * @param  string $affiliatepress_search_product_str
         * @return array
        */
        function affiliatepress_get_simple_membership_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){

            global$wpdb;
        
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){
        
                $affiliatepress_existing_products_data = array();

                $affiliatepress_swpm_membership_tbl = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'swpm_membership_tbl' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'swpm_membership_tbl' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

                $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$affiliatepress_swpm_membership_tbl} WHERE alias LIKE %s",'%' . $wpdb->esc_like($affiliatepress_search_product_str) . '%'),ARRAY_A);// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_swpm_membership_tbl is a table name. false alarm   

                $affiliatepress_existing_product_data = array();
    
                $affiliatepress_membership_levels = array_column($affiliatepress_results, 'id');

                if($affiliatepress_membership_levels){
                    foreach ($affiliatepress_membership_levels as $affiliatepress_membership_level) {
                    
                        $affiliatepress_membership_level_name = $wpdb->get_var($wpdb->prepare("SELECT alias FROM {$affiliatepress_swpm_membership_tbl} WHERE id = %d",$affiliatepress_membership_level));// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_swpm_membership_tbl is a table name. false alarm   

                        if(!empty($affiliatepress_membership_level_name))
                        {
                            $affiliatepress_existing_product_data[] = array(
                                'value' => $affiliatepress_membership_level,
                                'label' => $affiliatepress_membership_level_name
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
         * Function For Simple Membership get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_simple_membership_link_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_url = admin_url("admin.php?page=simple_wp_membership_payments&tab=edit_txn&id=".$affiliatepress_ap_commission_reference_id);

                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href=" '.esc_url( $affiliatepress_url ). ' "> '. $affiliatepress_ap_commission_reference_id .' </a>';

                $affiliatepress_ap_commission_reference_id = $affiliatepress_ap_commission_order_link;
            }
            
            return $affiliatepress_ap_commission_reference_id;
        }

        /**
         * Function for affiliate validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  int $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_order_data
         * @return array
         */
        function affiliatepress_commission_validation_swpm_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_transection_id, $affiliatepress_payment_data){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = isset($affiliatepress_payment_data['email']) ? $affiliatepress_payment_data['email'] : '';              
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
         * Function For save custome data
         *
         * @param  array $affiliatepress_custom_meta
         * @param  int $affiliatepress_level_id
         * @return array
         */
        function affiliatepress_save_custom_fields($affiliatepress_custom_meta, $affiliatepress_level_id) {
            
            $affiliatepress_wpnonce = isset($_POST['affiliatepress_commission_nonce_simple_membership']) ? sanitize_text_field(wp_unslash($_POST['affiliatepress_commission_nonce_simple_membership'])) : '';// phpcs:ignore

            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_commission_nonce_simple_membership');
            if (! $affiliatepress_verify_nonce_flag ) {
                $affiliatepress_nonce_error = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                return $affiliatepress_custom_meta;
            }  

            // Check if the checkbox was submitted
            $affiliatepress_disable_commission = isset($_POST['disable_affiliatepress_simple_membership_commission']) ? 1 : 0;// phpcs:ignore
        
            // Save the checkbox state as an option
            update_option('disable_affiliatepress_simple_membership_commission_' . $affiliatepress_level_id, $affiliatepress_disable_commission, false);
            do_action('affiliatepress_simple_membership_save_product_settings' , $affiliatepress_custom_meta , $affiliatepress_level_id);
        
            // Add the checkbox state to the custom_meta array
            $affiliatepress_custom_meta[] = array(
                'meta_key'     => 'disable_affiliatepress_simple_membership_commission', // phpcs:ignore
                'level_id'     => $affiliatepress_level_id,
                'meta_label'   => 'Disable Commission',
                'meta_value'   => $affiliatepress_disable_commission, // phpcs:ignore
                'meta_type'    => 'boolean',
                'meta_context' => 'affiliate-settings',
            );
        
            // Return the updated custom_meta array
            return $affiliatepress_custom_meta;
        }
        
        /**
         * Function For edit membership settings
         *
         * @param  string $affiliatepress_content
         * @param  int $affiliatepress_id
         * @return string
         */
        function affiliatepress_custom_edit_membership_level_ui($affiliatepress_content, $affiliatepress_id=null) {
            
            $affiliatepress_is_edit = !is_null($affiliatepress_id);
            $affiliatepress_disable_commission = $affiliatepress_is_edit ? get_option('disable_affiliatepress_simple_membership_commission_' . $affiliatepress_id, 0) : 0;
            // $affiliatepress_disable_commission = get_option('disable_affiliatepress_simple_membership_commission_' . $affiliatepress_id, 0);
            
            $affiliatepress_content .= '<tr>';
            $affiliatepress_content .= '<th scope="row">';
            $affiliatepress_content .= '<label for="disable_affiliatepress_simple_membership_commission">' . esc_html__('Affiliatepress Settings', 'affiliatepress-affiliate-marketing') . '</label>';
            $affiliatepress_content .= '</th>';
            $affiliatepress_content .= '<td>';
            $affiliatepress_content .= '<input id="disable_affiliatepress_simple_membership_commission" name="disable_affiliatepress_simple_membership_commission" type="checkbox" value="1" ' . checked($affiliatepress_disable_commission, 1, false) . '>';
            $affiliatepress_content .= '<label for="disable_affiliatepress_simple_membership_commission">' . esc_html__('Disable Commission', 'affiliatepress-affiliate-marketing') . '</label>';
            $affiliatepress_content .= '<p class="description">' . esc_html__('Enable Option Not to Add Commission for This Membership Level', 'affiliatepress-affiliate-marketing') . '</p>';
            $affiliatepress_content .= '</td>';
            $affiliatepress_content .= '</tr>';

            $affiliatepress_commission_nonce_simple_membership = wp_create_nonce('affiliatepress_commission_nonce_simple_membership');
            $affiliatepress_content .= '<input name="affiliatepress_commission_nonce_simple_membership" id="affiliatepress_commission_nonce_simple_membership" type="hidden" value="' . esc_attr( $affiliatepress_commission_nonce_simple_membership ) . '" />';


            $affiliatepress_input_content = "";
            $affiliatepress_input_content = apply_filters('affiliatepress_simple_membership_add_product_settings', $affiliatepress_input_content, $affiliatepress_id);

            if (!empty($affiliatepress_input_content)) {
                $affiliatepress_content .= $affiliatepress_input_content;
            }
        
            return $affiliatepress_content;
        }
        /**
         * affiliatepress_pending_refral_add_swpm
         *
         * @param  array $affiliatepress_payment_data
         * @param  int $affiliatepress_swpm_transection_id
         * @param  int $affiliatepress_transection_id
         * @return void
         */
        function affiliatepress_pending_refral_add_swpm($affiliatepress_payment_data, $affiliatepress_swpm_transection_id, $affiliatepress_transection_id)
        {
            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;


            $affiliatepress_membership_level_id = !empty($affiliatepress_payment_data['membership_level']) ? $affiliatepress_payment_data['membership_level'] : '';

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();          
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;
            

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_transection_id) );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();
            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_transection_id, $affiliatepress_payment_data);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_customer_args = array(
                'email'   	   => !empty($affiliatepress_payment_data['email']) ? sanitize_email($affiliatepress_payment_data['email']) : '',
                'user_id' 	   => '',
                'first_name'   => !empty($affiliatepress_payment_data['first_name']) ? sanitize_text_field($affiliatepress_payment_data['first_name']) : '',
                'last_name'	   => '',
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

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_commission_type = 'sale';
            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_product_id =!empty( $affiliatepress_payment_data['membership_level']) ? intval( $affiliatepress_payment_data['membership_level']) : 0;
            $affiliatepress_membership_level_name = $this->affiliatepress_simple_membership_get_product_name($affiliatepress_product_id);

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
                
                $affiliatepress_total_price = isset($affiliatepress_payment_data['payment_amount']) ? floatval($affiliatepress_payment_data['payment_amount']):0;
                
                $affiliatepress_amount = $affiliatepress_total_price;

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => 'sale',
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => 0,
                    'order_id'     => $affiliatepress_transection_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );

                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, '', $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_membership_level_name,
                    'order_id'             => $affiliatepress_transection_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

            }else{

                $affiliatepress_simple_membership_product = array(
                    'product_id'=> $affiliatepress_membership_level_id,
                    'source'=>$this->affiliatepress_integration_slug
                );

                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_simple_membership_product );
    
                if($affiliatepress_product_disable){
                    return;
                }
               
                $affiliatepress_total_price = isset($affiliatepress_payment_data['payment_amount']) ? floatval($affiliatepress_payment_data['payment_amount']):0;
                
                $affiliatepress_amount = $affiliatepress_total_price;

                $affiliatepress_membership_level_name = $this->affiliatepress_simple_membership_get_product_name($affiliatepress_product_id);
                
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_product_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_transection_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, '', $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_membership_level_name,
                    'order_id'             => $affiliatepress_transection_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $affiliatepress_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );

                $affiliatepress_commission_products_ids[] = $affiliatepress_product_id;
                $affiliatepress_commission_products_name[] = $affiliatepress_membership_level_name;

            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_transection_id, $affiliatepress_payment_data);

            if(!empty($affiliatepress_commission_final_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_final_validation['variant']))?$affiliatepress_commission_final_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_final_validation['msg']))?$affiliatepress_commission_final_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Added', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);
                    return;
                }                
            }

            $affiliatepress_commission_products_ids_string = (is_array($affiliatepress_commission_products_ids) && !empty($affiliatepress_commission_products_ids))?implode(',',$affiliatepress_commission_products_ids):'';

            $affiliatepress_commission_products_name_string = (is_array($affiliatepress_commission_products_name) && !empty($affiliatepress_commission_products_name))?implode(',',$affiliatepress_commission_products_name):'';

            $affiliatepress_ip_address = $AffiliatePress->affiliatepress_get_ip_address();

            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => 2,
                'ap_commission_reference_id'     => $affiliatepress_transection_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_order_referal_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp'))// phpcs:ignore
            );

            /* Insert The Commission */
            $affiliatepress_ap_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
            if($affiliatepress_ap_commission_id == 0){
                $affiliatepress_debug_log_msg = 'Pending commission could not be inserted due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
            }else{
 
                $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;
                $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data);
                $affiliatepress_debug_log_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );
 
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission SuccessfullyInserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
            }
 
        }
        
        /**
         * Function For get Title name
         *
         * @param  int $affiliatepress_product_id
         * @return string
         */
        function affiliatepress_simple_membership_get_product_name($affiliatepress_product_id)
        {
            global $wpdb;

            $affiliatepress_tbl_swpm_membership_tbl = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'swpm_membership_tbl' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'swpm_membership_tbl' contains table name and it's prepare properly using 'arm_payment_log' function

            $affiliatepress_membership_level_name = $wpdb->get_var($wpdb->prepare("SELECT alias FROM $affiliatepress_tbl_swpm_membership_tbl WHERE id = %d",$affiliatepress_product_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_swpm_membership_tbl is a table name. false alarm

            return $affiliatepress_membership_level_name;
        }
        
        /**
         * Function For approved commission add
         *
         * @param  array $affiliatepress_payment_data
         * @return void
         */
        function affiliatepress_complete_payment_swpm( $affiliatepress_payment_data)
        {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking ,$AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_status = !empty($affiliatepress_payment_data['status']) ? $affiliatepress_payment_data['status'] : '';
            $affiliatepress_transection_id = 0;

            if($affiliatepress_status != "completed")
            {
                return;
            }

            $affiliatepress_txn_id = isset($affiliatepress_payment_data['txn_id']) ? $affiliatepress_payment_data['txn_id'] : '';

            $affiliatepress_args = array(
                'post_type' => 'swpm_transactions', // Use a specific post type if needed
                'meta_query' => array(//phpcs:ignore
                    array(
                        'key' => 'txn_id',
                        'value' => $affiliatepress_txn_id,
                        'compare' => '='
                    )
                )
            );

            $affiliatepress_posts = get_posts($affiliatepress_args);
            $affiliatepress_post_ids = wp_list_pluck($affiliatepress_posts, 'ID');

            if (!empty($affiliatepress_post_ids)) {
                foreach ($affiliatepress_post_ids as $affiliatepress_post_id) {
                    $affiliatepress_transection_id = $affiliatepress_post_id;
                }
            }

            if($affiliatepress_transection_id == 0 )
            {
                return;
            }

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_transection_id, $this->affiliatepress_integration_slug,' AND ap_commission_status = 2');

            if(!empty($affiliatepress_all_commission_data)){

                foreach($affiliatepress_all_commission_data as $affiliatepress_commission_data){

                    $affiliatepress_commission_id = (isset($affiliatepress_commission_data['ap_commission_id']))?intval($affiliatepress_commission_data['ap_commission_id']):0;

                    if($affiliatepress_commission_id != 0){      
                
                        $affiliatepress_updated_commission_status = 1;
                        if($affiliatepress_default_commission_status != "auto")
                        {
                            $affiliatepress_updated_commission_status = $affiliatepress_default_commission_status;
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => $affiliatepress_default_commission_status
                            );
        
                        }
                        else
                        {
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => 1
                            );
                        }
        
                        if($affiliatepress_updated_commission_status != 2)
                        {
                        
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_commission_id ));
                            $affiliatepress_debug_log_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
        
                            do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_updated_commission_status,2);
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                        }
        
                    }
                }
            }
        }
        
         /**
         * Function For Check Plugin Active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active()
        {
            $affiliatepress_flag = true;

            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            if ( !is_plugin_active( 'simple-membership/simple-wp-membership.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_simple_membership_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_simple_membership = $AffiliatePress->affiliatepress_get_settings('enable_simple_membership', 'integrations_settings');
            if($affiliatepress_enable_simple_membership != 'true'){
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }

    }
}
global $affiliatepress_simple_membership;
$affiliatepress_simple_membership = new affiliatepress_simple_membership();