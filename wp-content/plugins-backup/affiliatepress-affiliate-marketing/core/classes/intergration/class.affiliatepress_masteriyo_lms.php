<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_masteriyo_lms') ){
    
    class affiliatepress_masteriyo_lms Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){  

            global $affiliatepress_is_masteriyo_lms_active ;
            $affiliatepress_is_masteriyo_lms_active = $this->affiliatepress_check_plugin_active();
            $this->affiliatepress_integration_slug = 'masteriyo_lms';

            if($this->affiliatepress_masteriyo_lms_commission_add() && $affiliatepress_is_masteriyo_lms_active){

                /**Add pending Commission */
                add_action( 'masteriyo_checkout_order_processed', array($this,'affiliatepress_insert_pending_commission_from_masteriyo_lms'), 10, 3 );

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func_masteriyo_lms'),15,5);

                /**Add Approved Commission After Status chnage */
                add_action( 'masteriyo_order_status_changed', array( $this, 'affiliatepress_accept_pending_commission_masteriyo_lms' ), 10, 4 );

                /**Add Pedning Commission After Status chnage */
                add_action( 'masteriyo_order_status_changed', array( $this, 'affiliatepress_add_pending_commission_on_status_update_masteriyo_lms' ), 10, 4 );

                /**Add for get product */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_masteriyo_lms_product_func'),10,3); 

                /**Add Proper URL */
                add_action('init', array( $this, 'affiliatepress_masteriyo_lms_courses_base_rewrites'));

            }

            if($affiliatepress_is_masteriyo_lms_active){
                /**Get Order Link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_masteriyo_lms_link_order_func'),10,3); 
            }
        }
        
        /**
         * Function For set cookie in page
         *
         * @return void
         */
        function affiliatepress_masteriyo_lms_courses_base_rewrites() {
            global $AffiliatePress;
            
            $affiliatepress_url_parameter = $AffiliatePress->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');

            add_rewrite_rule( 
                'courses' . '/' . $affiliatepress_url_parameter . '(/(.*))?/?$',
                'index.php?post_type=mto-course&'.$affiliatepress_url_parameter.'=$matches[2]',
                'top'
            );
        }
        
        /**
         * Function For get product details
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param  string $affiliatepress_commission_source
         * @param  string $affiliatepress_search_product_str
         * @return array
         */
        function affiliatepress_get_masteriyo_lms_product_func($affiliatepress_existing_source_product_data, $affiliatepress_commission_source, $affiliatepress_search_product_str) {
            global $wpdb;
            
            if ($affiliatepress_commission_source == $this->affiliatepress_integration_slug) {
                
                $affiliatepress_existing_products_data = array();

                $affiliatepress_args = array(
                    'post_type'   => 'mto-course',  // Your custom post type
                    'post_status' => 'publish',   // Only published posts
                    's'           => $affiliatepress_search_product_str, // Search term
                    'fields'      => 'ids',       // Only return post IDs
                );

                $affiliatepress_query = new WP_Query($affiliatepress_args);

                if ($affiliatepress_query->have_posts()) {
                    $post_ids = $affiliatepress_query->posts;
                    foreach ($post_ids as $post_id) {

                        $affiliatepress_post_name = get_the_title($post_id);
                        
                        $affiliatepress_existing_product_data[] = array(
                            'value' => $post_id,
                            'label' => $affiliatepress_post_name
                        );

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
         * Function For Masteriyo LMS get order link
         *
         * @param  integer $affiliatepress_commission_reference_id
         * @param  string $affiliatepress_commission_source
         * @return string
         */
        function affiliatepress_get_masteriyo_lms_link_order_func($affiliatepress_commission_reference_id, $affiliatepress_commission_source){
            
            if($affiliatepress_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_url = admin_url("admin.php?page=masteriyo#/orders/".$affiliatepress_commission_reference_id);

                $affiliatepress_commission_order_link = '<a target="_blank" class="ap-refrance-link" href=" '.esc_url( $affiliatepress_url ). ' "> '. $affiliatepress_commission_reference_id .' </a>';

                $affiliatepress_commission_reference_id = $affiliatepress_commission_order_link;
            }
            
            return $affiliatepress_commission_reference_id;
        }
                        
        /**
         * Function For Check Plugin Active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if ( is_plugin_active( 'learning-management-system/lms.php' )  || is_plugin_active( 'learning-management-system-pro/lms.php' ) ) {
                $affiliatepress_flag = true;
            }
            else
            {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_masteriyo_lms_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;            
            $affiliatepress_enable_masteriyo_lms = $AffiliatePress->affiliatepress_get_settings('enable_masteriyo_lms', 'integrations_settings');
            if($affiliatepress_enable_masteriyo_lms != 'true'){
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
        function affiliatepress_commission_validation_func_masteriyo_lms($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order_data){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = isset($affiliatepress_order_data['billing_email']) ? sanitize_email($affiliatepress_order_data['billing_email']) : '';
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
         * Function For Add pending Commission 
         *
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_posted_data
         * @param  array $affiliatepress_order
         * @return void
         */
        function affiliatepress_insert_pending_commission_from_masteriyo_lms($affiliatepress_order_id, $affiliatepress_posted_data, $affiliatepress_order)
        {
            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_order_id = isset( $affiliatepress_order_id ) ? intval($affiliatepress_order_id) : 0;

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit(); 

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id):0;

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_order_id) );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_posted_data);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_customer_id = $affiliatepress_order->get_data()['customer_id'];

            $affiliatepress_customer_args = array(
                'email'   	   => isset($affiliatepress_posted_data['billing_email']) ? sanitize_email($affiliatepress_posted_data['billing_email']) : '',
                'user_id' 	   => $affiliatepress_customer_id,
                'first_name'   => isset($affiliatepress_posted_data['billing_first_name']) ? sanitize_text_field($affiliatepress_posted_data['billing_first_name']) : '',
                'last_name'	   => isset($affiliatepress_posted_data['billing_last_name']) ? sanitize_text_field($affiliatepress_posted_data['billing_last_name']) : '',
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
                $affiliatepress_msg = sprintf( 'Customer #%s has been successfully processed.', $affiliatepress_customer_id );    
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Customer Created', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);                     
            } else {
                $affiliatepress_msg = 'Customer could not be processed due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', 'Customer Not Created', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
            }

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_commission_type = 'sale';
            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_total_price = isset($affiliatepress_order) ? floatval($affiliatepress_order->get_data()['total']) : 0;
            $affiliatepress_currency = isset($affiliatepress_order) ? sanitize_text_field($affiliatepress_order->get_data()['currency']) : '';

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_amount = $affiliatepress_total_price;
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => 'sale',
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => 0,
                    'order_id'         => $affiliatepress_order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

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
                $affiliatepress_amount = $affiliatepress_total_price;

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => '',
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_order_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => '',
                    'product_name'         => '',
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $affiliatepress_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );

            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_order_id, $affiliatepress_order);

            if(!empty($affiliatepress_commission_final_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_final_validation['variant']))?$affiliatepress_commission_final_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_final_validation['msg']))?sanitize_text_field($affiliatepress_commission_final_validation['msg']):'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Added', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);
                    return;
                }                
            }

            $affiliatepress_ip_address = $AffiliatePress->affiliatepress_get_ip_address();

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => 2,
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_product_ids'      => '',
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_total_price,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp'))// phpcs:ignore
            );

            $affiliatepress_flat_rate_commission_basis = $AffiliatePress->affiliatepress_get_settings('flat_rate_commission_basis', 'commissions_settings');
            if($affiliatepress_flat_rate_commission_basis == 'pre_product'){
                $affiliatepress_commission_data['ap_commission_reference_detail'] = 'Order '.$affiliatepress_order_id;
            }

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
         * Function For Add Approved Commission
         *
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_from
         * @param  string $to
         * @param  array $affiliatepress_order
         * @return void
         */
        public function affiliatepress_accept_pending_commission_masteriyo_lms( $affiliatepress_order_id, $affiliatepress_from, $to, $affiliatepress_order ) {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;

            if($to != "completed"){
                return;
            }

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_all_commissition_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);

            if(!empty($affiliatepress_all_commissition_data)){

                foreach($affiliatepress_all_commissition_data as $affiliatepress_commission){

                    $affiliatepress_commission_id = $affiliatepress_commission['ap_commission_id'];
                    $affiliatepress_commission_status = $affiliatepress_commission['ap_commission_status'];
        
                    if($affiliatepress_commission_status == 4){
                        $affiliatepress_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_commission_id );
        
                        do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Already paid ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
                        return;
                    }
        
                    if($affiliatepress_commission_id != 0){      
                        
                        $affiliatepress_updated_commission_status = 1;
                        if($affiliatepress_default_commission_status != "auto"){
                            $affiliatepress_updated_commission_status = $affiliatepress_default_commission_status;
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => $affiliatepress_default_commission_status
                            );
        
                        }else{
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => 1
                            );
                        }
        
                        if($affiliatepress_updated_commission_status != 2)
                        {
                        
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_commission_id ));
                            $affiliatepress_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
        
                            do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_updated_commission_status,2);
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
        
                        }
        
                    }

                }

            }


        }
        
        /**
         * Function For Add Reject Commission
         *
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_from
         * @param  string $to
         * @param  array $affiliatepress_order
         * @return void
         */
        function affiliatepress_add_pending_commission_on_status_update_masteriyo_lms($affiliatepress_order_id, $affiliatepress_from, $to, $affiliatepress_order)
        {
            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;

            if($to == "pending" || $to == "on-hold"){
                $affiliatepress_commission_all_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);
                if(!empty($affiliatepress_commission_all_data)){

                    foreach($affiliatepress_commission_all_data as $affiliatepress_commission_data){

                        if(!empty($affiliatepress_commission_data)){
                            $affiliatepress_commission_status = (isset($affiliatepress_commission_data['ap_commission_status']))?$affiliatepress_commission_data['ap_commission_status']:'';
                            $affiliatepress_commission_id     = (isset($affiliatepress_commission_data['ap_commission_id']))?$affiliatepress_commission_data['ap_commission_id']:'';
                            if($affiliatepress_commission_status == 4){
                                $affiliatepress_msg = sprintf( 'Commission #%s could not be pending because it was already paid.', $affiliatepress_commission_id );
                                return;
                            }
                            if($affiliatepress_commission_id != 0){
        
                                $affiliatepress_commission_data = array(
                                    'ap_commission_updated_date' => current_time( 'mysql', true ),
                                    'ap_commission_status' 		 => 2
                                );
                                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_commission_id ));
                                $affiliatepress_msg = sprintf( ' Commission #%s successfully marked as pending, after order #%s status change', $affiliatepress_commission_id, $affiliatepress_order_id );
        
                                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Reject ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
        
                            }
                        }

                    }

                }

            }
        }

    }
    
}

global $affiliatepress_masteriyo_lms;
$affiliatepress_masteriyo_lms = new affiliatepress_masteriyo_lms();