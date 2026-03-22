<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_surecart') ){

    class affiliatepress_surecart Extends AffiliatePress_Core{
    
        private $affiliatepress_integration_slug;

         /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){  
            global $affiliatepress_is_surecart_active ;
            $affiliatepress_is_surecart_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'surecart';

            if($this->affiliatepress_surecart_commission_add()  && $affiliatepress_is_surecart_active){
            
                /**Add Pending Commission */
                add_action( 'surecart/checkout_confirmed', array( $this, 'affiliatepress_insert_pending_commission_surecart' ), 11, 1 );

                /**Add for validation */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_surecart_func'),15,5);

                // Update the status of the commission to "unpaid", thus marking it as complete.
                add_action( 'surecart/order_paid', array($this,'affiliatepress_accept_pending_commission_surecart'),10,1);

                /* Add surecart Backend Product List */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_source_product_func_surecart'),10,3);
                
                /**Add proper URl */
                add_action('init', array( $this, 'surecart_shop_base_rewrites'));
            }

            if($affiliatepress_is_surecart_active){
                /**Get Order Link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_link_order_func_surecart'),10,3); 
            }
        }


        /**
         * Surecart Shop Page Redirect
         *
         * @return void
        */
        public function surecart_shop_base_rewrites() {
            
            global $AffiliatePress;
            $affiliatepress_affiliate_url_parameter = $AffiliatePress->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');
            add_rewrite_rule(
                '^products/([^/]+)/'.$affiliatepress_affiliate_url_parameter.'/([^/]+)/?$',
                'index.php?sc_product_page_id=$matches[1]&'.$affiliatepress_affiliate_url_parameter.'=$matches[2]',
                'top'
            );

        }

        /**
         * Function for get source product for backend commission add/edit
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param  string $affiliatepress_ap_commission_source
         * @param  string $affiliatepress_search_product_str
         * @return array
        */
        function affiliatepress_get_source_product_func_surecart($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

            }

            return $affiliatepress_existing_source_product_data;
        }
        
        /**
         * Function For get order ref link
         *
         * @param  int $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_link_order_func_surecart($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            

            $affiliatepress_order = \SureCart\Models\Order::find( $affiliatepress_ap_commission_reference_id );

            if ( ! empty( $affiliatepress_order->id ) ) {

                $affiliatepress_url = esc_url( \SureCart::getUrl()->edit( 'order', $affiliatepress_order->id ) );
        
                if ( ! empty( $affiliatepress_url ) ) {
                    $affiliatepress_ap_commission_reference_id = '<a target="_blank" class="ap-refrance-link" href="' . esc_url( $affiliatepress_url ) . '">#' . $affiliatepress_order->number . '</a>';
                }
        
            }
            // if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){
            //     $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href="'.admin_url("admin.php?page=sc-orders&action=edit&id=".$affiliatepress_ap_commission_reference_id).'");"> '. $affiliatepress_ap_commission_reference_id .' </a>';
            //     $affiliatepress_ap_commission_reference_id = $affiliatepress_ap_commission_order_link;
            // }
            
            return $affiliatepress_ap_commission_reference_id;
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

            if ( !is_plugin_active( 'surecart/surecart.php' )  ) {
                $affiliatepress_flag = false;
            }
           
            return $affiliatepress_flag;
        }

        /**
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_surecart_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_surecart = $AffiliatePress->affiliatepress_get_settings('enable_surecart', 'integrations_settings');
            if($affiliatepress_enable_surecart != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
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
        function affiliatepress_commission_validation_surecart_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_checkout){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;

                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = !empty($affiliatepress_checkout->customer->email) ? $affiliatepress_checkout->customer->email : '';
                        
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
         * Function For Add pending commission
         *
         * @param  array $affiliatepress_checkout
         * @return void
         */
        function affiliatepress_insert_pending_commission_surecart( $affiliatepress_checkout ) {

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id ,$affiliatepress_tracking;

            $affiliatepress_checkout = \SureCart\Models\Checkout::with( [ 'initial_order', 'order', 'product', 'customer' ] )->find( $affiliatepress_checkout->id );

            if ( empty( $affiliatepress_checkout->order->id ) ) {
                return;
            }

            $affiliatepress_order_id = isset($affiliatepress_checkout) ? sanitize_text_field($affiliatepress_checkout->order->id) : 0;

            $affiliatepress_order = $affiliatepress_checkout->order;

            /* Get and check to see if referrer exists */
            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();   
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_order_id) );

            
            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_checkout);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            /* Add Commission Customer Here */
            $affiliatepress_customer_args = array(
                'email'   	   => $affiliatepress_checkout->customer->email,
                'user_id' 	   => ( ! empty( $affiliatepress_checkout->metadata->wp_created_by ) ? absint( $affiliatepress_checkout->metadata->wp_created_by ) : get_current_user_id() ),
                'first_name'   => isset($affiliatepress_checkout) ? sanitize_text_field($affiliatepress_checkout->customer->first_name) : '',
                'last_name'	   => isset($affiliatepress_checkout) ? sanitize_text_field($affiliatepress_checkout->customer->last_name) : '',
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

            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('surecart_exclude_taxes', 'integrations_settings');            
            $affiliatepress_exclude_shipping = $AffiliatePress->affiliatepress_get_settings('surecart_exclude_shipping', 'integrations_settings');

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();

            $affiliatepress_commission_type = 'sale';
            $affiliatepress_order_referal_amount = 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
            
                if ( \SureCart\Support\Currency::isZeroDecimal( $affiliatepress_checkout->currency ) ) {
                    $affiliatepress_total_price = $affiliatepress_checkout->amount_due;
                } else {
                    $affiliatepress_total_price = round( $affiliatepress_checkout->amount_due / 100, 2 );
                }
                $affiliatepress_tax_amount  = round( $affiliatepress_checkout->tax_amount / 100, 2 );
                $affiliatepress_shipping_amount = round( $affiliatepress_checkout->shipping_amount / 100, 2 );

                $affiliatepress_amount = $affiliatepress_total_price;

                if($affiliatepress_exclude_taxes == "true")
                {
                    $affiliatepress_amount = $affiliatepress_amount-$affiliatepress_tax_amount;
                }

                if($affiliatepress_exclude_shipping == "true")
                {
                    $affiliatepress_amount =$affiliatepress_amount-$affiliatepress_shipping_amount;
                }

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => 'sale',
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => 0,
                    'order_id'     => $affiliatepress_order_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, strtoupper( $affiliatepress_checkout->currency ), $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => 0,
                    'product_name'         => '',
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

            }
            else
            {
                $affiliatepress_order_amount = round( $affiliatepress_checkout->subtotal_amount / 100, 2 );

                if($affiliatepress_exclude_taxes == 'false'){
                    $affiliatepress_order_amount = $affiliatepress_order_amount + round( $affiliatepress_checkout->tax_amount / 100, 2 ) ;
                }

                /* Include Shipping */
                if($affiliatepress_exclude_shipping == 'false'){
                    $affiliatepress_order_amount = $affiliatepress_order_amount + round( $affiliatepress_checkout->shipping_amount / 100, 2 ) ;
                }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_order_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_order_amount, strtoupper( $affiliatepress_checkout->currency ), $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_order_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => '',
                    'product_name'         => '',
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $affiliatepress_order_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );

                $affiliatepress_commission_products_ids[] = $affiliatepress_order_id;
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_order_id, $affiliatepress_order);

            if(!empty($affiliatepress_commission_final_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_final_validation['variant']))?$affiliatepress_commission_final_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_final_validation['msg']))?$affiliatepress_commission_final_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Added', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);
                    return;
                }                
            }

            $affiliatepress_commission_products_ids_string = (is_array($affiliatepress_commission_products_ids) && !empty($affiliatepress_commission_products_ids))?implode(',',$affiliatepress_commission_products_ids):'';

            $affiliatepress_ip_address = $AffiliatePress->affiliatepress_get_ip_address();

            if ( \SureCart\Support\Currency::isZeroDecimal( $affiliatepress_checkout->currency ) ) {
                $affiliatepress_total_amount = $affiliatepress_checkout->amount_due;
            } else {
                $affiliatepress_total_amount = round( $affiliatepress_checkout->amount_due / 100, 2 );
            }

            // 3b4fe064-154c-42b2-932c-035a0ca2b3ce
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => '',
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_total_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')) // phpcs:ignore
            );

            $affiliatepress_checkout_payment_status = $affiliatepress_order->status;
            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            if($affiliatepress_checkout_payment_status == "paid")
            {
                $affiliatepress_commission_data['ap_commission_status'] = 1;

                $affiliatepress_updated_commission_status = 1;

                if($affiliatepress_default_commission_status != "auto")
                {

                    $affiliatepress_updated_commission_status = $affiliatepress_default_commission_status;
                    $affiliatepress_commission_data['ap_commission_status'] = $affiliatepress_default_commission_status;

                }
                else
                {
                    $affiliatepress_commission_data['ap_commission_status'] = 1;
                }

                $affiliatepress_ap_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
                if($affiliatepress_ap_commission_id == 0){
                    $affiliatepress_debug_log_msg = 'commission could not be inserted due to an unexpected error.';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                }else{

                    $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;
                    $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                    do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data);

                    if($affiliatepress_updated_commission_status != 2){
                        do_action('affiliatepress_after_commissions_status_change',$affiliatepress_ap_commission_id,$affiliatepress_updated_commission_status,2);
                    }
                    else
                    {
                        do_action('affiliatepress_after_commissions_status_change',$affiliatepress_ap_commission_id,1,2);
                    }

                    $affiliatepress_debug_log_msg = sprintf( 'commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );
    
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
                }
            }
            else
            {
                $affiliatepress_commission_data['ap_commission_status'] = 2;

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

        }

        
        /**
         * Function For add Approved commission
         *
         * @param  array $affiliatepress_order
         * @return void
         */
        function affiliatepress_accept_pending_commission_surecart( $affiliatepress_order ) {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id,$affiliatepress_tracking ,$AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_order = $affiliatepress_order->id;

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order, $this->affiliatepress_integration_slug,' AND ap_commission_status = 2');

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
        
                        if($affiliatepress_updated_commission_status != 2){
                            
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_commission_id ));
                            $affiliatepress_debug_log_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
        
                            do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_updated_commission_status,2);
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                        }
        
                    }
                }
            }

        }

    }
}

global $affiliatepress_surecart;
$affiliatepress_surecart = new affiliatepress_surecart();