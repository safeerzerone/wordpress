<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_gravity_forms') ){
    
    class affiliatepress_gravity_forms Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){

            global $affiliatepress_is_gravity_forms_active;
            $affiliatepress_is_gravity_forms_active = $this->affiliatepress_check_plugin_active();
            $this->affiliatepress_integration_slug = 'gravity_forms';
            if($this->affiliatepress_gravity_forms_add() && $affiliatepress_is_gravity_forms_active){

                /* Add Settings Fields */
                add_filter('gform_form_settings', array($this, 'affiliatepress_add_settings'), 10, 2);

                /**Save Disable Commission Settings */
                add_filter('gform_pre_form_settings_save', array($this, 'affiliatepress_save_settings'));
                
                /**Add Pending Commission */
                add_filter( 'gform_entry_created', array( $this, 'affiliatepress_add_commission' ), 10, 2 );

                /**Filter validation */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

                /**Add Approved Commission */
                add_action( 'gform_post_payment_completed', array( $this, 'affiliatepress_commission_approve' ), 10, 2 );
              
            }

            if($affiliatepress_is_gravity_forms_active){
                /**Get Order Link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_gravity_forms_link_order_func'),10,3); 
            }
        }

        /**
         * Function for approve commission
         *
         * @param  int $affiliatepress_entry
         * @param  string $affiliatepress_action
         * @return void
        */
        function affiliatepress_commission_approve( $affiliatepress_entry, $affiliatepress_action ) {

            $affiliatepress_entry_id = (isset($affiliatepress_entry['id']))?$affiliatepress_entry['id']:'';
            if($affiliatepress_entry_id){

                global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;
                $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();
                if($affiliatepress_default_commission_status != "auto"){
                    
                    $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_entry_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');

                    if(!empty($affiliatepress_all_commission_data)){

                        foreach($affiliatepress_all_commission_data as $affiliatepress_single_commission_data){
        
                            $affiliatepress_commission_id = (isset($affiliatepress_single_commission_data['ap_commission_id']))?intval($affiliatepress_single_commission_data['ap_commission_id']):0;
                            
                            if($affiliatepress_commission_id != 0){

                                $affiliatepress_updated_commission_status = 1;
                                $affiliatepress_commission_data = array(
                                    'ap_commission_updated_date' => current_time( 'mysql', true ),
                                    'ap_commission_status' 		 => $affiliatepress_default_commission_status
                                );                         
                                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_commission_id ));
                                $affiliatepress_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
        
                                do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_updated_commission_status,2);
            
                                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_paid_membership_pro_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);                        
        
                            }

                        }
                    }
                    
             
                }

            }



        }

        /**
         * Function For Gravity Forms get order link
         *
         * @param  integer $affiliatepress_commission_reference_id
         * @param  string $affiliatepress_commission_source
         * @return string
         */
        function affiliatepress_get_gravity_forms_link_order_func($affiliatepress_commission_reference_id, $affiliatepress_commission_source){            
            if($affiliatepress_commission_source == $this->affiliatepress_integration_slug){
                $affiliatepress_entry_data = GFFormsModel::get_lead( $affiliatepress_commission_reference_id );                                
                if(isset($affiliatepress_entry_data['form_id'])){
                    $affiliatepress_commission_order_link = '<a target="_blank" class="ap-refrance-link" href="'.admin_url("admin.php?page=gf_entries&view=entry&id=" . $affiliatepress_entry_data['form_id'] . "&lid=".$affiliatepress_commission_reference_id).'");"> '. $affiliatepress_commission_reference_id .' </a>';                
                    $affiliatepress_commission_reference_id = $affiliatepress_commission_order_link;    
                } 
            }            
            return $affiliatepress_commission_reference_id;
        }        

        /**
         * Function for add commission
         *
         * @param  int $affiliatepress_entry
         * @param  array $affiliatepress_form_data
         * @return void
        */
        function affiliatepress_add_commission($affiliatepress_entry, $affiliatepress_form_data){

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id; 

            $affiliatepress_form_id = (isset($affiliatepress_form_data['id']))?intval($affiliatepress_form_data['id']):0;

            $affiliatepress_form_title = $this->affiliatepress_gravity_forms_get_form_title($affiliatepress_form_id);

            $affiliatepress_entry_id    = (isset($affiliatepress_entry['id']))?$affiliatepress_entry['id']:'';
            if(empty($affiliatepress_entry_id)){
                $affiliatepress_log_msg = "Entry ID Not Found";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Entry ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;                
            }
			
	        $affiliatepress_email = rgar( $affiliatepress_form_data, 'affiliatepress_email_field' );
            if(!empty($affiliatepress_email)){
                $affiliatepress_email = (isset($affiliatepress_entry[$affiliatepress_email]))?sanitize_email($affiliatepress_entry[$affiliatepress_email]):'';
            }

            $affiliatepress_entry['affiliatepress_customer_email'] = $affiliatepress_email;
			
            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id):0;

            $affiliatepress_order_data   = array('entry_id'=>$affiliatepress_entry_id);
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_order_data );

            if(empty($affiliatepress_affiliate_id)){
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }
            
            $affiliatepress_commission_validation = array();
            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_entry_id, $affiliatepress_entry);
            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }            
            
            $affiliatepress_commission_type = rgar( $affiliatepress_form_data, 'affiliatepress_commission_type' );
            if(empty($affiliatepress_commission_type)){
                $affiliatepress_commission_type = 'sale';
            }
            $affiliatepress_form_data_title = isset($affiliatepress_form_data['title'])?sanitize_text_field($affiliatepress_form_data['title']):'';
            $affiliatepress_customer_email = rgar( $affiliatepress_form_data, 'affiliatepress_email_field' );
            $affiliatepress_customer_name  = rgar( $affiliatepress_form_data, 'affiliatepress_name_field' );
            if(!empty($affiliatepress_customer_email)){
                $affiliatepress_customer_email = (isset($affiliatepress_entry[$affiliatepress_customer_email]))?sanitize_email($affiliatepress_entry[$affiliatepress_customer_email]):'';
            }
            $affiliatepress_first_name = '';
            $affiliatepress_last_name = '';
            $affiliatepress_mapping_field_value = '';

            if(!empty($affiliatepress_customer_name)){
                foreach ($affiliatepress_form_data['fields'] as $field) {
                    if ($field->id == $affiliatepress_customer_name) {
                        $field_type = $field->type;
                        if($field_type == "name"){
                            $affiliatepress_first_name = rgar($affiliatepress_entry, $affiliatepress_customer_name . '.3' );
                            $affiliatepress_last_name = rgar($affiliatepress_entry, $affiliatepress_customer_name . '.6' );
                        }
                        if($field_type == "checkbox"){
                            $values = array();
                            foreach ($affiliatepress_entry as $key => $value) {
                                if (strpos((string)$key, $affiliatepress_customer_name) === 0) {
                                    $values[] = $value;
                                }
                            }
                            $affiliatepress_mapping_field_value = implode(", ", $values);
                        }else{
                            $affiliatepress_mapping_field_value = (isset($affiliatepress_entry[$affiliatepress_customer_name]))?($affiliatepress_entry[$affiliatepress_customer_name]):'';
                        }
                    }
                }
            }  
            
            if(empty($affiliatepress_first_name)){
                $affiliatepress_first_name = $affiliatepress_mapping_field_value;
            }
            
            $affiliatepress_customer_id = 0;
            if(!empty($affiliatepress_customer_email)){
                /* Add Commission Customer Here */
                $affiliatepress_customer_args = array(
                    'email'   	   => $affiliatepress_customer_email,
                    'user_id' 	   => 0,
                    'first_name'   => $affiliatepress_first_name,
                    'last_name'	   => $affiliatepress_last_name,
                    'affiliate_id' => $affiliatepress_affiliate_id
                );

                $affiliatepress_customer_commisison_add = true;
                $affiliatepress_customer_commisison_add = apply_filters('affiliatepress_validate_customer_for_commission', $affiliatepress_customer_commisison_add, $affiliatepress_customer_args,$this->affiliatepress_integration_slug);
    
                if(!$affiliatepress_customer_commisison_add){
                    return;
                }
                
                $affiliatepress_customer_id = $AffiliatePress->affiliatepress_add_commission_customer($affiliatepress_customer_args);
                if ( $affiliatepress_customer_id ) {
                    $affiliatepress_msg = sprintf( 'Customer #%s has been successfully processed.', $affiliatepress_customer_id );    
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Customer Created', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);                     
                } else {
                    $affiliatepress_msg = 'Customer could not be processed due to an unexpected error.';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', 'Customer Not Created', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
                }                
            }

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();
            $affiliatepress_order_referal_amount = 0;


            
            $affiliatepress_all_product_name     = isset( $affiliatepress_form['title'] ) ? $affiliatepress_form['title'] : '';
            $affiliatepress_entry    = GFFormsModel::get_lead( $affiliatepress_entry['id'] );
            $affiliatepress_products = GFCommon::get_product_fields( $affiliatepress_form_data, $affiliatepress_entry );
            $affiliatepress_total    = 0;
    
            foreach ( $affiliatepress_products['products'] as $affiliatepress_key => $affiliatepress_product ) {
                $affiliatepress_price = GFCommon::to_number( $affiliatepress_product['price'] );
                if ( is_array( rgar( $affiliatepress_product,'options' ) ) ) {
                    $affiliatepress_count = count( $affiliatepress_product['options'] );
                    $index = 1;
                    foreach ( $affiliatepress_product['options'] as $affiliatepress_option ) {
                        $affiliatepress_price += GFCommon::to_number( $affiliatepress_option['price'] );
                    }
                }
                $affiliatepress_subtotal = floatval( $affiliatepress_product['quantity'] ) * $affiliatepress_price;
                $affiliatepress_total += $affiliatepress_subtotal;
            }
            if(!empty($affiliatepress_products['products'])){
                $affiliatepress_product_names = wp_list_pluck( $affiliatepress_products['products'], 'name' );
                $affiliatepress_all_product_name          = implode( ', ', $affiliatepress_product_names );
            }
            $affiliatepress_shipping_price = (isset($affiliatepress_products['shipping']['price']))?floatval($affiliatepress_products['shipping']['price']):0;    
            $affiliatepress_total += floatval( $affiliatepress_shipping_price );  

            if($affiliatepress_total == 0){
                $affiliatepress_error_msg = ' Total Amount Is 0. ';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                return;
            }

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_amount = $affiliatepress_total;
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_form_id,
                    'order_id'         => $affiliatepress_entry_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );                
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount,'',$affiliatepress_args);
                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_form_id,
                    'product_name'         => $affiliatepress_form_title,
                    'order_id'             => $affiliatepress_entry_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );

                $affiliatepress_order_referal_amount = $affiliatepress_amount;
            }else{

                $affiliatepress_disable_option = rgar($affiliatepress_form_data,'affiliatepress_allow_commission');

                $affiliatepress_gravity_forms_product = array(
                    'product_id'=> $affiliatepress_form_id,
                    'source'=>$this->affiliatepress_integration_slug,
                    'affiliatepress_gravity_forms_settings' =>$affiliatepress_disable_option
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_gravity_forms_product );
    
                if($affiliatepress_product_disable){
                    return;
                }
                /* Calculate Commission Amount */
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_form_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_entry_id,
                );
                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_total, '', $affiliatepress_args );

                $affiliatepress_single_product_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_commission_amount += $affiliatepress_single_product_commission_amount;

                $affiliatepress_order_referal_amount += $affiliatepress_total;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_form_id,
                    'product_name'         => $affiliatepress_form_title,
                    'order_id'             => $affiliatepress_entry_id,
                    'commission_amount'    => $affiliatepress_single_product_commission_amount,
                    'order_referal_amount' => $affiliatepress_total,
                    'commission_basis'     => 'per_product',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );


                $affiliatepress_commission_products_ids[] = $affiliatepress_form_id;
                $affiliatepress_commission_products_name[] = $affiliatepress_form_title;
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_form_id, $affiliatepress_entry);

            if(!empty($affiliatepress_commission_final_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_final_validation['variant']))?$affiliatepress_commission_final_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_final_validation['msg']))?sanitize_text_field($affiliatepress_commission_final_validation['msg']):'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Added', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);
                    return;
                }                
            }

            $affiliatepress_commission_products_ids_string = (is_array($affiliatepress_commission_products_ids) && !empty($affiliatepress_commission_products_ids))?implode(',',$affiliatepress_commission_products_ids):'';

            $affiliatepress_commission_products_name_string = (is_array($affiliatepress_commission_products_name) && !empty($affiliatepress_commission_products_name))?implode(',',$affiliatepress_commission_products_name):'';

            $affiliatepress_ip_address = $AffiliatePress->affiliatepress_get_ip_address();

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => 2,
                'ap_commission_reference_id'     => $affiliatepress_entry_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_total,
                'ap_commission_order_amount'     => $affiliatepress_total,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp'))// phpcs:ignore
            );

            /* Insert The Commission */
            $affiliatepress_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
            if($affiliatepress_commission_id == 0){
                $affiliatepress_msg = 'Pending commission could not be inserted due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
            }else{

                $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;
                $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                do_action('affiliatepress_after_commission_created', $affiliatepress_commission_id, $affiliatepress_commission_data);
                $affiliatepress_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_commission_id );

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);         
            }            


        }
  
        /**
         * Function For Validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  int $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  int $affiliatepress_order_id
         * @param  int $affiliatepress_entry
         * @return array
         */
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_entry){

            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = $affiliatepress_entry['affiliatepress_customer_email'];              
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
         * Function For Get Title
         *
         * @param  int $affiliatepress_form_id
         * @return string
         */
        function affiliatepress_gravity_forms_get_form_title($affiliatepress_form_id)
        {
            global $wpdb;
		    $affiliatepress_form_table_name = $wpdb->prefix . 'gf_form';

            $affiliatepress_tbl_gf_form = $this->affiliatepress_tablename_prepare( $affiliatepress_form_table_name ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_form_table_name contains table name and it's prepare properly using 'arm_payment_log' function
                
            $affiliatepress_form_title = $wpdb->get_var( $wpdb->prepare( "SELECT title FROM $affiliatepress_tbl_gf_form WHERE id = %d",$affiliatepress_form_id));// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_gf_form is a table name. false alarm

           return $affiliatepress_form_title;
        }

                
        /**
         * Function for save settings
         *
         * @param  array $affiliatepress_form_data
         * @return array
        */
        public function affiliatepress_save_settings($affiliatepress_form_data){

            $affiliatepress_form_data['affiliatepress_allow_commission'] = rgpost('affiliatepress_allow_commission');
            $affiliatepress_form_data['affiliatepress_commission_type']  = rgpost('affiliatepress_commission_type');
            $affiliatepress_form_data['affiliatepress_email_field']      = rgpost('affiliatepress_email_field');
            $affiliatepress_form_data['affiliatepress_name_field']       = rgpost('affiliatepress_name_field');

            $affiliatepress_form_data = apply_filters('affiliatepress_gravity_forms_save_product_settings', $affiliatepress_form_data);

            return $affiliatepress_form_data;

        }

        /**
         * Function for add gravity form settings
         *
         * @param  array $affiliatepress_settings
         * @param  array $affiliatepress_form
         * @return array
        */
        public function affiliatepress_add_settings($affiliatepress_settings, $affiliatepress_form){

            global $affiliatepress_global_options;

            $affiliatepress_allow_commission  = rgar( $affiliatepress_form, 'affiliatepress_allow_commission' );
            $affiliatepress_commission_type = rgar( $affiliatepress_form, 'affiliatepress_commission_type' );
            
            $affiliatepress_email_field = rgar( $affiliatepress_form, 'affiliatepress_email_field' );
            $affiliatepress_name_field = rgar( $affiliatepress_form, 'affiliatepress_name_field' );

            $affiliatepress_commission_all_options = array();

            $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_all_commissions_type = $affiliatepress_global_options_data['commissions_type'];

            foreach( $affiliatepress_all_commissions_type as $affiliatepress_commissions_type ) {                
                if($affiliatepress_commissions_type['value'] != 'subscription'){
                    $affiliatepress_commission_all_options[] = array(
                        'label' => $affiliatepress_commissions_type['text'],
                        'value' => $affiliatepress_commissions_type['value']
                    );    
                }
            }

            $affiliatepress_allow_commission = ($affiliatepress_allow_commission == "yes")?"checked":"";
            $affiliatepress_all_fields = (isset($affiliatepress_form['fields']))?$affiliatepress_form['fields']:array();


            $affiliatepress_final_html = '<tr><th><label>' . esc_html__( 'Allow commission', 'affiliatepress-affiliate-marketing') . '</label></th><td><input type="checkbox" value="yes" id="affiliatepress_allow_commission" '.$affiliatepress_allow_commission.' name="affiliatepress_allow_commission" /></td></tr>
                <tr><th><label>' . esc_html__( 'Commission Type', 'affiliatepress-affiliate-marketing') . '</label></th><td><select name="affiliatepress_commission_type" id="affiliatepress_commission_type">';
                        foreach( $affiliatepress_commission_all_options as $affiliatepress_option ) {
                            $affiliatepress_selected = ($affiliatepress_commission_type == $affiliatepress_option['value'])?"selected":"";
                            $affiliatepress_final_html.= '<option '.$affiliatepress_selected.' value="'  . esc_attr( $affiliatepress_option['value'] ) . '">' . esc_html( $affiliatepress_option['label'] ) . '</option>';           
                        }                
            $affiliatepress_final_html.= '</select></td></tr>';

            $affiliatepress_extra_settings_gravity_forms="";

            $affiliatepress_final_html .='<tr><th><label>' . esc_html__( 'Select Email Field', 'affiliatepress-affiliate-marketing') . '</label></th>
                <td><select name="affiliatepress_email_field" id="affiliatepress_email_field">';
                $affiliatepress_final_html.= '<option  value="">' .esc_html__( 'Select Field', 'affiliatepress-affiliate-marketing') . '</option>';     
                if(!empty($affiliatepress_all_fields)){
                    foreach( $affiliatepress_all_fields as $affiliatepress_field ) {
                        $affiliatepress_field_type = (isset($affiliatepress_field->type))?$affiliatepress_field->type:'';
                        if($affiliatepress_field_type != 'section' && $affiliatepress_field_type != 'product' && $affiliatepress_field_type != 'total' && $affiliatepress_field_type != 'page' && $affiliatepress_field_type != 'address' && $affiliatepress_field_type != 'shipping'  && $affiliatepress_field_type != 'hidden' && $affiliatepress_field_type != 'consent' && $affiliatepress_field_type != 'fileupload' && $affiliatepress_field_type != 'html'){
                            $affiliatepress_selected = ($affiliatepress_email_field == $affiliatepress_field->id)?"selected":"";
                            $affiliatepress_final_html.= '<option '.$affiliatepress_selected.' value="'  . esc_attr( $affiliatepress_field->id ) . '">' . esc_html( $affiliatepress_field->label ) . '</option>';               
                        }
                    }    
                }
            $affiliatepress_final_html.= '</select></td>
                </tr>
                <tr><th><label>' . esc_html__( 'Select Name Field', 'affiliatepress-affiliate-marketing') . '</label></th>
                <td><select name="affiliatepress_name_field" id="affiliatepress_name_field">';
                $affiliatepress_final_html.= '<option  value="">' .esc_html__( 'Select Field', 'affiliatepress-affiliate-marketing') . '</option>';     
                if(!empty($affiliatepress_all_fields)){
                    foreach( $affiliatepress_all_fields as $affiliatepress_field ) {
                        $affiliatepress_field_type = (isset($affiliatepress_field->type))?$affiliatepress_field->type:'';
                        if($affiliatepress_field_type != 'section' && $affiliatepress_field_type != 'product' && $affiliatepress_field_type != 'total' &&       $affiliatepress_field_type != 'page' && $affiliatepress_field_type != 'address' && $affiliatepress_field_type != 'shipping' && $affiliatepress_field_type != 'hidden' && $affiliatepress_field_type != 'consent' && $affiliatepress_field_type != 'fileupload'  && $affiliatepress_field_type != 'html'){
                            $affiliatepress_selected = ($affiliatepress_name_field == $affiliatepress_field->id)?"selected":"";
                            $affiliatepress_final_html.= '<option '.$affiliatepress_selected.' value="'  . esc_attr( $affiliatepress_field->id ) . '">' . esc_html( $affiliatepress_field->label ) . '</option>';               
                        }       
                    }    
                }                
            $affiliatepress_final_html.= '</select></td>';

            $affiliatepress_settings['AffiliatePress']['affiliatepress_allow_commission'] = $affiliatepress_final_html;

            $affiliatepress_settings = apply_filters('affiliatepress_gravity_forms_add_product_settings', $affiliatepress_settings, $affiliatepress_form);
            return $affiliatepress_settings;

        }


        /**
         * Enable Commission for Gravity Form
         *
         * @return bool
        */
        function affiliatepress_gravity_forms_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_gravity_forms = $AffiliatePress->affiliatepress_get_settings('enable_gravity_forms', 'integrations_settings');
            if($affiliatepress_enable_gravity_forms != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }

        /**
         * check plugin active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if(!function_exists('is_plugin_active')){
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if(is_plugin_active('gravityforms/gravityforms.php')){
                $affiliatepress_flag = true;
            }else{
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }        

    }
}

global $affiliatepress_gravity_forms;
$affiliatepress_gravity_forms = new affiliatepress_gravity_forms();