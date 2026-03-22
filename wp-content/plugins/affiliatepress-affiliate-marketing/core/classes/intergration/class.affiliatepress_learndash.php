<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_learndash') ){

    class affiliatepress_learndash Extends AffiliatePress_Core{
    
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){  
            global $affiliatepress_is_learndash_active ;
            $affiliatepress_is_learndash_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'learndash';

            if($this->affiliatepress_learndash_commission_add() && $affiliatepress_is_learndash_active){
                
                add_action( 'learndash_transaction_created', array($this , 'affiliatepress_process_commission_learndash'), 10 );

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

                add_action( 'added_post_meta', array( $this, 'affiliatepress_insert_commission_learndash' ), 10, 4 );
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
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order){

            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = (!empty($affiliatepress_order->customer_email)) ?   $affiliatepress_order->customer_email : '';    
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
        
        
        function affiliatepress_insert_commission_learndash($meta_id, $post_id, $meta_key, $meta_value ){
            if ( 'user_id' !== $meta_key ) {
                return;
            }
    
            // Bail if not a LearnDash Transaction.
            if ( 'sfwd-transactions' !== get_post_type( $post_id ) ) {
                return;
            }
    
            // Get order details.
            $order = $this->get_order( $post_id );

            $order_id = $order->id;

            if ( empty( $order ) ) {

                $affiliatepress_log_msg = "Process Commission not completed because no order found.";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();        
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;

            $affiliatepress_order_data   = array('order_id' => $order_id);
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_order_data  );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $payment_processor = array('stripe', 'stripe_connect');

            if ( empty( $order->payment_processor ) || ! in_array( $order->payment_processor, $payment_processor, true ) ) {
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $order_id, $order);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            if ( ! empty( $order->stripe_price_type ) && 'paynow' === $order->stripe_price_type ) {
                
                $payment_intent = $order->stripe_payment_intent;

                $affiliatepress_log_msg = "Processing referral for Stripe one-time charge.";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Stripe ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
            }
        
            // Subscription charge.
            if ( ! empty( $order->stripe_price_type ) && 'subscribe' === $order->stripe_price_type ) {

                $payment_intent = $order->subscription;

                $affiliatepress_log_msg = "Processing referral for Stripe subscription.";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Stripe ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
            }

            if ( empty( $order->description ) ) {
                $order_description = get_the_title( $order->id );
            }

            $affiliatepress_customer_args = array(
                'email'   	   => $order->customer_email,
                'user_id' 	   => '',
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

            $order_total_amount =$this->get_order_total( $order->id );

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_commission_type = 'sale';
            $affiliatepress_order_referal_amount = 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
                
                $affiliatepress_order_referal_amount = $order_total_amount;
               
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => 'sale',
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => 0,
                    'order_id'         => $order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                    'quntity'          => 1,
                );
                $affiliatepress_commission_rules  = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_order_referal_amount, '', $affiliatepress_args);     

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => 0,
                    'product_name'         => '',
                    'order_id'             => $order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',                     
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );
                

            }else{

                // $affiliatepress_plan_amount = !empty($affiliatepress_armember_planamount) ? floatval($affiliatepress_armember_planamount) : 0;

                // if ( $affiliatepress_exclude_taxes == 'false' ) {
                //     $affiliatepress_plan_amount = !empty($affiliatepress_armember_withtaxvalue) ? floatval($affiliatepress_armember_withtaxvalue) : 0;
                // }

                // $affiliatepress_armember_product = array(
                //     'product_id'=>$affiliatepress_plan_id,
                //     'source'=>$this->affiliatepress_integration_slug
                // );
                // $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_armember_product );
    
                // if($affiliatepress_product_disable){
    
                //     return;
                // }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $order_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $order_total_amount, '', $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_plan_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $order_id,
                    'product_name'         => $order_description,
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $order_total_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );
    
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $order_id, $order);

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

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => 1,
                'ap_commission_reference_id'     => $order_id,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $order_total_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')) // phpcs:ignore
            );

            /* Insert The Commission */
            $affiliatepress_ap_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
            if($affiliatepress_ap_commission_id == 0){
                $affiliatepress_debug_log_msg = 'Pending commission could not be inserted due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
            }else{

                $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;
                $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data );
                $affiliatepress_debug_log_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
            }
        }

        function affiliatepress_process_commission_learndash($transaction_id){

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $order = $this->get_order( $transaction_id );

            $order_id = $order->id;

            if ( empty( $order ) ) {

                $affiliatepress_log_msg = "Process Commission not completed because no order found.";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();        
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;

            $affiliatepress_order_data   = array('order_id' => $order_id);
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_order_data  );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $payment_processor = array('stripe', 'stripe_connect');

            if ( empty( $order->payment_processor ) || ! in_array( $order->payment_processor, $payment_processor, true ) ) {
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $order_id, $order);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            if ( ! empty( $order->stripe_price_type ) && 'paynow' === $order->stripe_price_type ) {
                
                $payment_intent = $order->stripe_payment_intent;

                $affiliatepress_log_msg = "Processing referral for Stripe one-time charge.";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Stripe ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
            }
        
            // Subscription charge.
            if ( ! empty( $order->stripe_price_type ) && 'subscribe' === $order->stripe_price_type ) {

                $payment_intent = $order->subscription;

                $affiliatepress_log_msg = "Processing referral for Stripe subscription.";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Stripe ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
            }

            if ( empty( $order->description ) ) {
                $order_description = get_the_title( $order->id );
            }

            $affiliatepress_customer_args = array(
                'email'   	   => $order->customer_email,
                'user_id' 	   => '',
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

            $order_total_amount =$this->get_order_total( $order->id );

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_commission_type = 'sale';
            $affiliatepress_order_referal_amount = 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
                
                $affiliatepress_order_referal_amount = $order_total_amount;
               
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => 'sale',
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => 0,
                    'order_id'         => $order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                    'quntity'          => 1,
                );
                $affiliatepress_commission_rules  = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_order_referal_amount, '', $affiliatepress_args);     

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => 0,
                    'product_name'         => '',
                    'order_id'             => $order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',                     
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );
                

            }else{

                // $affiliatepress_plan_amount = !empty($affiliatepress_armember_planamount) ? floatval($affiliatepress_armember_planamount) : 0;

                // if ( $affiliatepress_exclude_taxes == 'false' ) {
                //     $affiliatepress_plan_amount = !empty($affiliatepress_armember_withtaxvalue) ? floatval($affiliatepress_armember_withtaxvalue) : 0;
                // }

                // $affiliatepress_armember_product = array(
                //     'product_id'=>$affiliatepress_plan_id,
                //     'source'=>$this->affiliatepress_integration_slug
                // );
                // $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_armember_product );
    
                // if($affiliatepress_product_disable){
    
                //     return;
                // }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $order_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $order_total_amount, '', $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_plan_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $order_id,
                    'product_name'         => $order_description,
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $order_total_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );
    
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $order_id, $order);

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

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => 1,
                'ap_commission_reference_id'     => $order_id,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $order_total_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')) // phpcs:ignore
            );

            /* Insert The Commission */
            $affiliatepress_ap_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
            if($affiliatepress_ap_commission_id == 0){
                $affiliatepress_debug_log_msg = 'Pending commission could not be inserted due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
            }else{

                $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;
                $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data );
                $affiliatepress_debug_log_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
            }
        }

        /**
         * Function For Check Plugin Active
         *
         * @return void
         */
        function affiliatepress_check_plugin_active()
        {
            $affiliatepress_flag = true;

            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            if ( !is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }

        /**
         * Function For Check Integration Settings add 
         *
         * @return void
         */
        function affiliatepress_learndash_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_wp_easycart = $AffiliatePress->affiliatepress_get_settings('enable_learndash', 'integrations_settings');
            if($affiliatepress_enable_wp_easycart != 'true'){
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }

        public function get_order_total( $order = 0 ) {

            if ( ! function_exists( 'learndash_transaction_get_final_price' ) ) {
                return 0;
            }
    
            return learndash_transaction_get_final_price( $order );
        }

        function get_order( $transaction_id ) {
    
            $post = get_post( $transaction_id );
    
            if ( ! $post ) {
                return false;
            }
    
            // Create a plain new standard object to map order props from Stripe metadata to it.
            $order = new stdClass();
    
            // Set some reference data.
            $order->id   = absint( $transaction_id );
            $order->post = $post; // WP Post Object.
    
            // Get all metadata related to the transaction. Observe that multiple values for each meta will be returned.
            $payment_data = get_post_meta( $transaction_id );
    
            // In some LearnDash versions (4.10 or higher) most metadata is concentrated in the gateway_transaction meta.
            $gateway_transaction = maybe_unserialize( $payment_data['gateway_transaction'][0] )['event'] ?? null;
    
            // We are expecting this to return affwp_affiliate_id and affwp_visit_id object props.
            $order->stripe_metadata = maybe_unserialize( $gateway_transaction->metadata ?? $payment_data['stripe_metadata'][0] ?? '' );
    
            // Newer LD versions use price_type instead of stripe_price_type.
            $order->stripe_price_type =  $payment_data['price_type'][0] ?? $payment_data['stripe_price_type'][0] ?? null;
    
            // Get other payment related metadata. Newer LD versions get values from the gateway_transaction event.
            $order->stripe_payment_intent = $gateway_transaction->payment_intent ?? $payment_data['stripe_payment_intent'][0] ?? null;
            $order->subscription          = $gateway_transaction->subscription ?? $payment_data['subscription'][0] ?? null;
    
            // New price info is within a JSON string in newer versions of LD.
            $price_info = json_decode( $gateway_transaction->metadata->pricing_info ?? '', true );
    
            // Set pricing data.
            $order->stripe_price    = $price_info['price'] ?? $payment_data['stripe_price'][0] ?? null;
            $order->stripe_currency = $price_info['currency'] ?? $payment_data['stripe_currency'][0] ?? null;
    
            /*
             * Set the payment processor.
             * Note: cannot rely on ld_payment_processor because its added later in the addon.
             */
            $payment_processor        = $gateway_transaction->metadata->ld_payment_processor ?? $payment_data['ld_payment_processor'][0] ?? null;
            $order->payment_processor = empty( $payment_processor ) && ! empty( $order->stripe_price_type )
                ? 'stripe'
                : $payment_processor;
    
            // Set the user-related data.
            $order->stripe_email   = $gateway_transaction->customer_details->email ??  $payment_data['stripe_email'][0] ?? null;
            $order->customer_email = $gateway_transaction->customer_details->email ??  $payment_data['customer_email'][0] ?? null;
    
            // Set the product-related data.
            $order->stripe_name = $payment_data['stripe_name'][0] ?? null;
            $order->description = $order->stripe_name;

            return  $order;
        }


    }
}
global $affiliatepress_learndash;
$affiliatepress_learndash = new affiliatepress_learndash();