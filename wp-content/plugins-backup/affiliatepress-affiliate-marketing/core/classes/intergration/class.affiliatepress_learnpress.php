<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_learnpress') ){
    
    class affiliatepress_learnpress Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){   

            global $affiliatepress_is_learnpress_active ;
            $affiliatepress_is_learnpress_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'learnpress';
            if($this->affiliatepress_learnpress_commission_add() && $affiliatepress_is_learnpress_active){

                /**Add Pending Commission */
                add_action( 'learn-press/checkout-order-processed', array($this,'affiliatepress_insert_pending_commission_from_learnpress'), 10, 2 );

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

                /* Update the status of the commission to "unpaid" */
                add_action( 'learn-press/payment-complete', array($this,'affiliatepress_accept_pending_commission_learnpress'), 10, 1 );

                /**Add Approved Commission After Status chnage */
                add_action( 'learn-press/order/status-changed', array($this,'affiliatepress_status_change_completed_learnpress'), 10, 3 );

                /**add affiliate settings tab */
                add_filter( 'learnpress/course/metabox/tabs', array($this , 'affiliatepress_add_Settings_tab'), 10, 2 );

                /**Add Proper URL */
                add_action('init', array( $this, 'affiliatepress_learnpress_courses_base_rewrites'));
            }

            if($affiliatepress_is_learnpress_active){
                /**Get Order Link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_learnpress_link_order_func'),10,3); 

                /**Get All product  */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_learnpress_product_func'),10,3); 
            }
        }
        
        /**
         * Function For Cookie set in page
         *
         * @return void
         */
        public function affiliatepress_learnpress_courses_base_rewrites() {
            $affiliatepress_store_page_id = get_option( 'learn_press_courses_page_id' );            
            if ( $affiliatepress_store_page_id ) {                
                global $AffiliatePress;
                $affiliatepress_url_parameter = $AffiliatePress->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');
                $affiliatepress_uri = get_page_uri( $affiliatepress_store_page_id );                
                add_rewrite_rule( $affiliatepress_uri . '/' . $affiliatepress_url_parameter . '(/(.*))?/?$', 'index.php?page_id='.$affiliatepress_store_page_id.'&' . $affiliatepress_url_parameter . '=$matches[2]', 'top' );
            }
        }
        
        /**
         * affiliatepress_get_learnpress_product_func
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param string $affiliatepress_commission_source
         * @param string $affiliatepress_search_product_str
         * @return array
         */
        function affiliatepress_get_learnpress_product_func($affiliatepress_existing_source_product_data, $affiliatepress_commission_source, $affiliatepress_search_product_str) {
            global $wpdb;
            
            if ($affiliatepress_commission_source == $this->affiliatepress_integration_slug) {
                
                $affiliatepress_existing_products_data = array();

                $affiliatepress_args = array(
                    'post_type'   => 'lp_course',  // Your custom post type
                    'post_status' => 'publish',   // Only published posts
                    's'           => $affiliatepress_search_product_str, // Search term
                    'fields'      => 'ids',       // Only return post IDs
                );

                $affiliatepress_query = new WP_Query($affiliatepress_args);

                if ($affiliatepress_query->have_posts()) {
                    $post_ids = $affiliatepress_query->posts;
                    foreach ($post_ids as $post_id) {

                        $affiliatepress_post_name = get_the_title($post_id);

                        // echo "<>".$affiliatepress_post_name."<br>";
                        
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
         * Function For LearnPress get order link
         *
         * @param  integer $affiliatepress_commission_reference_id
         * @param  string $affiliatepress_commission_source
         * @return string
         */
        function affiliatepress_get_learnpress_link_order_func($affiliatepress_commission_reference_id, $affiliatepress_commission_source){
            
            if($affiliatepress_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_url = admin_url("post.php?post=".$affiliatepress_commission_reference_id."&action=edit");

                $affiliatepress_commission_order_link = '<a target="_blank" class="ap-refrance-link" href=" '.esc_url( $affiliatepress_url ). ' "> '. $affiliatepress_commission_reference_id .' </a>';

                $affiliatepress_commission_reference_id = $affiliatepress_commission_order_link;
            }
            
            return $affiliatepress_commission_reference_id;
        }
        
        /**
         * Function For Check Plugin
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if ( !is_plugin_active( 'learnpress/learnpress.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Integration Settings check
         *
         * @return bool
         */
        function affiliatepress_learnpress_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;            
            $affiliatepress_enable_learnpress = $AffiliatePress->affiliatepress_get_settings('enable_learnpress', 'integrations_settings');
            if($affiliatepress_enable_learnpress != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Add tab Affiliate settings
         *
         * @param  array $affiliatepress_tabs
         * @param  int $post_id
         * @return array
         */
        function affiliatepress_add_Settings_tab($affiliatepress_tabs, $post_id)
        {
            $affiliatepress_tabs['affiliatepress_disable_commission_learnpress']  = array(
                'label'    => esc_html__( 'AffiliatePress', 'affiliatepress-affiliate-marketing'),
                'target'   => 'affiliatepress_disable_commission_learnpress',
                'icon'     => 'dashicons-admin-tools',
                'priority' => 60,
                'content'  => $this->disable_option( $post_id ),
            );

            return $affiliatepress_tabs;
        }
        
        /**
         * function For Add Disable settings
         *
         * @param  int $post_id
         * @return array
         */
        function disable_option( $post_id)
        {
            $affiliatepress_fields = array(
                'affiliatepress_disable_commission_learnpress' => new LP_Meta_Box_Checkbox_Field(
                    esc_html__( 'AffiliatePress Disable', 'affiliatepress-affiliate-marketing' ),
                    esc_html__( 'Check this option to disable commission in AffiliatePress.', 'affiliatepress-affiliate-marketing' ),
                    'no'
                ),
            );
        
            return apply_filters('affiliatepress_learnpress_add_product_settings',$affiliatepress_fields,$post_id);
        }
   
        /**
         * Function For validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  int $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_order
         * @return void
         */
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order){

            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;

                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_user_id = isset($affiliatepress_order) ? intval($affiliatepress_order->get_user_id()) : 0;

                        $affiliatepress_user_info = get_userdata($affiliatepress_user_id);
                        $affiliatepress_user_email = isset($affiliatepress_user_info) ? sanitize_email($affiliatepress_user_info->user_email) : '';

                        if($AffiliatePress->affiliatepress_affiliate_has_email( $affiliatepress_affiliate_id, $affiliatepress_user_email ) ) {                   
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
         * Function For Add Pending Comission
         *
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_checkout_instance
         * @return void
         */
        function affiliatepress_insert_pending_commission_from_learnpress($affiliatepress_order_id , $affiliatepress_checkout_instance )
        {
            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_order_id = !empty($affiliatepress_order_id) ? intval($affiliatepress_order_id) : 0;

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();    

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id):0;

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id' => $affiliatepress_order_id) );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_order = learn_press_get_order( $affiliatepress_order_id );

            $affiliatepress_items = $affiliatepress_order->get_items();

            $affiliatepress_product_id = "";
            $affiliatepress_product_name = "";

            foreach ($affiliatepress_items as $affiliatepress_key => $affiliatepress_course) {

                $affiliatepress_product_id = !empty($affiliatepress_course['course_id']) ? intval($affiliatepress_course['course_id']) : 0;
                $affiliatepress_product_name = !empty($affiliatepress_course['name']) ? sanitize_text_field($affiliatepress_course['name']) : '';

            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_order);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_user_id = isset($affiliatepress_order) ? intval($affiliatepress_order->get_user_id()):0;
            $affiliatepress_user_info = get_userdata($affiliatepress_user_id);

            $affiliatepress_user_email = isset($affiliatepress_user_info) ? sanitize_email($affiliatepress_user_info->user_email) : '';
            $affiliatepress_first_name = isset($affiliatepress_user_info) ? sanitize_text_field($affiliatepress_user_info->display_name) : '';

            $affiliatepress_customer_args = array(
                'email'   	   => $affiliatepress_user_email,
                'user_id' 	   => $affiliatepress_user_id,
                'first_name'   => $affiliatepress_first_name,
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

            $affiliatepress_total_amount = !empty($affiliatepress_order) ? floatval($affiliatepress_order->get_total()) : 0;
            $affiliatepress_currency = !empty($affiliatepress_order) ? sanitize_text_field($affiliatepress_order->get_currency()) : '';

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
                
                $affiliatepress_amount =  $affiliatepress_total_amount;

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => 'sale',
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_product_id,
                    'order_id'         => $affiliatepress_order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount,$affiliatepress_currency, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_product_name,
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );
                
                $affiliatepress_order_referal_amount = $affiliatepress_amount;

            }
            else{

                $affiliatepress_larnpress_product = array(
                    'product_id'=> $affiliatepress_product_id,
                    'source'=>$this->affiliatepress_integration_slug
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_larnpress_product );
                if($affiliatepress_product_disable){
                    return;
                }

                $affiliatepress_amount = $affiliatepress_total_amount;

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_product_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_order_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;
    
                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_product_name,
                    'order_id'             => $affiliatepress_order_id,
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

            $affiliatepress_commission_products_ids_string = (is_array($affiliatepress_commission_products_ids) && !empty($affiliatepress_commission_products_ids))?implode(',',$affiliatepress_commission_products_ids):'';

            $affiliatepress_commission_products_name_string = (is_array($affiliatepress_commission_products_name) && !empty($affiliatepress_commission_products_name))?implode(',',$affiliatepress_commission_products_name):'';

            $affiliatepress_ip_address = $AffiliatePress->affiliatepress_get_ip_address();

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => 2,
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_total_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp'))// phpcs:ignore
            );

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
         * Function For add Approved Commission
         *
         * @param  int $affiliatepress_order_id
         * @return void
         */
        function affiliatepress_accept_pending_commission_learnpress($affiliatepress_order_id)
        {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');
            
            if(!empty($affiliatepress_all_commission_data)){

                foreach($affiliatepress_all_commission_data as $affiliatepress_commission_data){

                    $affiliatepress_commission_id = (isset($affiliatepress_commission_data['ap_commission_id']))?intval($affiliatepress_commission_data['ap_commission_id']):0;

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
        
                        if($affiliatepress_updated_commission_status != 2){
                        
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
         * Function For Add Approved Commission After status chnage
         *
         * @param  int $affiliatepress_order_id
         * @param  string $affiliatepress_old_status
         * @param  string $affiliatepress_new_status
         * @return void
         */
        function affiliatepress_status_change_completed_learnpress($affiliatepress_order_id , $affiliatepress_old_status , $affiliatepress_new_status)
        {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking ,$AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            if( $affiliatepress_new_status !=  "completed" )
            {
                return;
            }

            $affiliatepress_all_commissition_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);

            if(!empty($affiliatepress_all_commissition_data)){

                foreach($affiliatepress_all_commissition_data as $affiliatepress_commissition_data){
                    
                    if(!empty($affiliatepress_commissition_data)){
                        $affiliatepress_commission_status = (isset($affiliatepress_commissition_data['ap_commission_status']))?intval($affiliatepress_commissition_data['ap_commission_status']):0;
                        $affiliatepress_commission_id     = (isset($affiliatepress_commissition_data['ap_commission_id']))?$affiliatepress_commissition_data['ap_commission_id']:'';
                        if($affiliatepress_commission_status == 4){
                            $affiliatepress_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_commission_id );
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
            
                            if($affiliatepress_updated_commission_status != 2){
                            
                                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_commission_id ));
                                $affiliatepress_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
            
                                do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_updated_commission_status,2);
            
                                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
            
                            }
            
                        }
                    }


                }

            }


        }
        
    }
}
global $affiliatepress_learnpress;
$affiliatepress_learnpress = new affiliatepress_learnpress();