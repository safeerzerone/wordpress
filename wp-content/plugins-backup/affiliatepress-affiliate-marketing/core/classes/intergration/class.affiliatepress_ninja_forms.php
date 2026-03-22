<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }



if( !class_exists('affiliatepress_ninja_forms') ){
    
    class affiliatepress_ninja_forms Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;

                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){
            global $affiliatepress_is_ninjaforms_active;
            $affiliatepress_is_ninjaforms_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'ninjaforms';
            if($this->affiliatepress_ninjaforms_commission_add() && $affiliatepress_is_ninjaforms_active){
                
                /* Ninja Form Affiliate Press Settings Add */
                $affiliatepress_ninja_forms_version = get_option( 'ninja_forms_version', '0.0.0' );
                if( version_compare($affiliatepress_ninja_forms_version, '3.0', '>=' ) && !get_option( 'ninja_forms_load_deprecated', FALSE ) ) {
                    
                    add_action('affiliatepress_add_ninja_form_commission',  array( $this, 'affiliatepress_add_ninja_form_commission_func'));
                    add_filter('ninja_forms_register_actions', array( $this, 'affiliatepress_register_ninja_form_actions'));

                    /* Affiliate Own Commission Filter Add Here  */
                    add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

                }

            }

            if($affiliatepress_is_ninjaforms_active){
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_link_order_func'),10,3);
            }
              
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
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_args){
            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;
                $affiliatepress_customer_email  = isset( $affiliatepress_args['customer_email'] ) ? sanitize_email( $affiliatepress_args['customer_email']) : '';
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){                        
                        if($AffiliatePress->affiliatepress_affiliate_has_email( $affiliatepress_affiliate_id, $affiliatepress_customer_email ) ) {                   
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
         * Function For Add Pending commission
         *
         * @param  array $affiliatepress_args_new
         * @return void
         */
        function affiliatepress_add_ninja_form_commission_func($affiliatepress_args_new){
            
            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_customer_email  = isset( $affiliatepress_args_new['customer_email'] ) ? $affiliatepress_args_new['customer_email'] : '';
            $affiliatepress_total_amount    = isset( $affiliatepress_args_new['total_amount'] ) ? floatval($affiliatepress_args_new['total_amount']) : 0;
            $affiliatepress_sub_id          = isset( $affiliatepress_args_new['sub_id'] ) ? $affiliatepress_args_new['sub_id'] : '';
            $affiliatepress_name            = isset( $affiliatepress_args_new['name'] ) ? $affiliatepress_args_new['name'] : '';
            $affiliatepress_commission_type = isset( $affiliatepress_args_new['commission_type'] ) ? $affiliatepress_args_new['commission_type'] : 'sale';
            $affiliatepress_form_id         = isset( $affiliatepress_args_new['form_id'] ) ? intval($affiliatepress_args_new['form_id']) : 0;

            /* Get and check to see if referrer exists */
            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();       
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_sub_id) );

            if(empty($affiliatepress_affiliate_id)){
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            } 
            
            if(empty($affiliatepress_total_amount) || $affiliatepress_total_amount == 0){
                $affiliatepress_log_msg = "Commission Amount 0";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Amount 0', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }            


            $affiliatepress_commission_validation = array();
            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_sub_id, $affiliatepress_args_new);
            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;
                }                
            }

            $affiliatepress_customer_id            = 0;
            if(!empty($affiliatepress_customer_email)){
                /* Add Commission Customer Here */
                $affiliatepress_customer_args = array(
                    'email'   	   => $affiliatepress_customer_email,
                    'user_id' 	   => 0,
                    'first_name'   => '',
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
            }
            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();            
            $affiliatepress_order_referal_amount = 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
                $affiliatepress_product_id   = $affiliatepress_form_id;
                $affiliatepress_product_name = $affiliatepress_name;

                $affiliatepress_amount = $affiliatepress_total_amount;

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => 'sale',
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => $affiliatepress_form_id,
                    'order_id'     => $affiliatepress_sub_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, '', $affiliatepress_args );
                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;
               
                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_product_name,
                    'order_id'             => $affiliatepress_sub_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );

                $affiliatepress_order_referal_amount = $affiliatepress_amount;            

            }else{

                $affiliatepress_amount       = $affiliatepress_total_amount;
                $affiliatepress_product_id   = $affiliatepress_form_id;
                $affiliatepress_product_name = $affiliatepress_name;
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_form_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_sub_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(floatval($affiliatepress_total_amount),'USD',$affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;
                $affiliatepress_order_referal_amount = $affiliatepress_total_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_product_name,
                    'order_id'             => $affiliatepress_sub_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $affiliatepress_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );

                $affiliatepress_commission_products_ids[] = $affiliatepress_product_id;
                $affiliatepress_commission_products_name[] = $affiliatepress_product_name;             

            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_sub_id, $affiliatepress_args_new);

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


            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();
            if($affiliatepress_default_commission_status == "auto"){
                $affiliatepress_ap_commission_status = 1;
            }else{
                $affiliatepress_ap_commission_status = $affiliatepress_default_commission_status;
            }

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => $affiliatepress_ap_commission_status,
                'ap_commission_reference_id'     => $affiliatepress_sub_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_total_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')) // phpcs:ignore
            );

            $affiliatepress_ap_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
            if($affiliatepress_ap_commission_id == 0){
                $affiliatepress_debug_log_msg = 'Pending commission could not be inserted due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
            }else{

                $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;
                $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data);
                $affiliatepress_debug_log_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
            }


        }

        
        /**
         * Function For Addd Affiliate settings in ninja forms
         *
         * @param  array $affiliatepress_actions
         * @return array
         */
        public function affiliatepress_register_ninja_form_actions( $affiliatepress_actions ) {
            if (file_exists(AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_ninja_form_action.php') ) {
                include_once AFFILIATEPRESS_CLASSES_DIR . '/intergration/class.affiliatepress_ninja_form_action.php';
                $affiliatepress_actions[ 'affiliatepress_add_commission' ] = new affiliatepress_ninja_form_action();
            }            
            return $affiliatepress_actions;
        }

        /**
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_ninjaforms_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_ninjaforms = $AffiliatePress->affiliatepress_get_settings('enable_ninjaforms', 'integrations_settings');
            if($affiliatepress_enable_ninjaforms != 'true'){
                $affiliatepress_flag = false;
            }            
            return $affiliatepress_flag;
        }        

        /**
         * Function for link order & commission
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
        */
        function affiliatepress_get_link_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){      
                /*
                $post = get_post($affiliatepress_ap_commission_reference_id);
                if(!$post){
                    return $affiliatepress_ap_commission_reference_id;
                }
                */
                $affiliatepress_url = admin_url( "post.php?action=edit&post=".$affiliatepress_ap_commission_reference_id);                         
                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href=" '.esc_url( $affiliatepress_url ). ' "> '. $affiliatepress_ap_commission_reference_id .' </a>';                                
                return $affiliatepress_ap_commission_order_link;
            }            
            return $affiliatepress_ap_commission_reference_id;
        }        
        
        /**
         * Function For Check Plugin Active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active(){              
            $affiliatepress_flag = true;
            if(!function_exists('is_plugin_active')){
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if(!is_plugin_active('ninja-forms/ninja-forms.php')){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }


    }
}

global $affiliatepress_ninja_forms;
$affiliatepress_ninja_forms = new affiliatepress_ninja_forms();