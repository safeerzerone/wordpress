<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_lifter_lms') ){
    
    class affiliatepress_lifter_lms Extends AffiliatePress_Core{

        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){ 
        
            $this->affiliatepress_integration_slug = 'lifter_lms';

            global $affiliatepress_is_lifter_lms_active ;
            $affiliatepress_is_lifter_lms_active = $this->affiliatepress_check_plugin_active();

            if($this->affiliatepress_lifter_lms_commission_add() && $affiliatepress_is_lifter_lms_active){
                
                /**Add pending COmmission */
                add_action( 'lifterlms_new_pending_order', array( $this, 'affiliatepress_create_pending_referral_lifterlms' ), 10, 1 );

                // Complete the pending referral on successes.
				add_action( 'lifterlms_order_status_completed', array( $this, 'affiliatepress_complete_pending_referral_lifterlms' ), 10, 1 );
				add_action( 'lifterlms_order_status_active', array( $this, 'affiliatepress_complete_pending_referral_lifterlms' ), 10, 1 );
                
                add_action( 'lifterlms_order_status_pending', array( $this, 'affiliatepress_add_pending_commission_on_status_change_lifterlms' ), 10, 1 );

                // Add affiliate product fields to LifterLMS courses and memberships.
				add_filter( 'llms_metabox_fields_lifterlms_course_options', array( $this, 'affiliatepress_product_meta_output' ), 77, 1 );
				add_filter( 'llms_metabox_fields_lifterlms_membership', array( $this, 'affiliatepress_product_meta_output' ), 77, 1 );

                // save affiliate product fields to post meta.
                add_action( 'lifterlms_process_course_meta', array( $this, 'affiliatepress_product_meta_save' ), 10, 2 );
                add_action( 'lifterlms_process_llms_membership_meta', array( $this, 'affiliatepress_product_meta_save' ), 10, 2 );

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func_lifterlms'),15,5);
            }

            if($affiliatepress_is_lifter_lms_active)
            {
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_link_lms_order_func'),10,3); 

                /* Add lifterlms Backend Product List */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_lifterlms_product_func'),10,3); 
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
        function affiliatepress_get_lifterlms_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str) {
            global $wpdb;
            
            if ($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug) {
                
                $affiliatepress_existing_products_data = array();

                $affiliatepress_args = array(
                    'post_type'   => 'course',  // Your custom post type
                    'post_status' => 'publish',   // Only published posts
                    's'           => $affiliatepress_search_product_str, // Search term
                    'fields'      => 'ids',       // Only return post IDs
                );

                $affiliatepress_query = new WP_Query($affiliatepress_args);

                if ($affiliatepress_query->have_posts()) {
                    $affiliatepress_post_ids = $affiliatepress_query->posts;
                    foreach ($affiliatepress_post_ids as $affiliatepress_post_id) {

                        $affiliatepress_post_name = get_the_title($affiliatepress_post_id);
                        
                        $affiliatepress_existing_product_data[] = array(
                            'value' => $affiliatepress_post_id,
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
         * Function For LifterLMS get order link
         *
         * @param  int $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_link_lms_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href=" '.esc_url( get_edit_post_link( $affiliatepress_ap_commission_reference_id ) ).' ");"> '. $affiliatepress_ap_commission_reference_id .' </a>';

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
         * @param  array $affiliatepress_order
         * @return array
         */
        function affiliatepress_commission_validation_func_lifterlms($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = !empty($affiliatepress_order->get( 'billing_email' )) ? sanitize_email($affiliatepress_order->get( 'billing_email' )) : '';                
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
         * Function For Add Pending Commisison
         *
         * @param  array $affiliatepress_order
         * @return void
         */
        public function affiliatepress_create_pending_referral_lifterlms( $affiliatepress_order ) {

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            if ( ! $affiliatepress_order instanceof LLMS_Order ) {
                return;
            }
    
            $affiliatepress_order_id  = $affiliatepress_order->get( 'id' );
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

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_order);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_customer_args = array(
                'email'   	   => isset($affiliatepress_order) ? sanitize_email($affiliatepress_order->get( 'billing_email' )) : '',
                'user_id' 	   => isset($affiliatepress_order) ? intval($affiliatepress_order->get( 'user_id' )) : 0,
                'first_name'   => isset($affiliatepress_order) ? sanitize_text_field($affiliatepress_order->get( 'billing_first_name' )) : '',
                'last_name'	   => isset($affiliatepress_order) ? sanitize_text_field($affiliatepress_order->get( 'billing_last_name' )) : '',
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

            $affiliatepress_commission_type = 'sale';

            $affiliatepress_commission_products_ids =array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_total_price = isset($affiliatepress_order) ? floatval($affiliatepress_order->get('total')) : 0;

            $affiliatepress_currency = isset($affiliatepress_order) ? sanitize_text_field($affiliatepress_order->get('currency')) : '';

            $affiliatepress_product_id = isset($affiliatepress_order) ? intval($affiliatepress_order->get('product_id')) : 0;
            $affiliatepress_product_name = get_the_title($affiliatepress_product_id);
 
            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_total_price = $affiliatepress_total_price;

                $affiliatepress_amount = $affiliatepress_total_price;

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => 'sale',
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => 0,
                    'order_id'     => $affiliatepress_order_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

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
            else
            {
                $affiliatepress_liftrelms_product = array(
                    'product_id'=>$affiliatepress_product_id,
                    'source'=>$this->affiliatepress_integration_slug
                );

                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_liftrelms_product );
                if($affiliatepress_product_disable){
                    return;
                }
                
                $affiliatepress_amount = $affiliatepress_total_price;

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
                $affiliatepress_commission_products_name[] =$affiliatepress_product_name;
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
                'ap_commission_order_amount'     => $affiliatepress_order->get('total'),
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
 
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
             }
           
        }
        
        /**
         * Function For add Approved Commission
         *
         * @param  array $affiliatepress_order
         * @return void
         */
        public function affiliatepress_complete_pending_referral_lifterlms( $affiliatepress_order ) {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            if ( ! $affiliatepress_order instanceof LLMS_Order ) {
                return;
            }

            $affiliatepress_order_id =$affiliatepress_order->get( 'id' );

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');
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
        
                        if($affiliatepress_updated_commission_status !=2)
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
         * Function For Add Affiliate disable settings
         *
         * @param  array $affiliatepress_fields
         * @return array
         */
        public function affiliatepress_product_meta_output( $affiliatepress_fields ) {

            global $post;

            $affiliatepress_product_type = str_replace( 'llms_', '', $post->post_type );

            $affiliatepress_settings_fields = array(
                array(
                    'type'		 => 'checkbox',
                    'label'		 => __( 'Disable Commission', 'affiliatepress-affiliate-marketing'),
                    'desc' 		 => sprintf( __( 'Activate this setting to stop commissions for affiliates on purchases of this %s.', 'affiliatepress-affiliate-marketing'),$affiliatepress_product_type), // phpcs:ignore
                    'desc_class' => 'd-3of4 t-3of4 m-1of2',
                    'id' 		 => 'affiliatepress_commission_disable_lifterlms',
                    'value' 	 => '1',
                ),
                array(
                    'type'  => 'hidden',
                    'id'    => 'affiliatepress_llms_course_nonce_action_field',
                    'value' => wp_create_nonce('affiliatepress_llms_course_nonce_action_field'),
                ),
            );

            $affiliatepress_fields[] = array(
                'title' => 'AffiliatePress',
                'fields' => apply_filters( 'affiliatepress_lifterlms_add_product_settings', $affiliatepress_settings_fields, $affiliatepress_product_type )
            );

            return $affiliatepress_fields;
        }
        
        /**
         * Function For Disable option settings  save 
         *
         * @param  int $affiliatepress_post_id
         * @param  string $affiliatepress_product_name
         * @return void
         */
        public function affiliatepress_product_meta_save( $affiliatepress_post_id, $affiliatepress_product_name ) {

            if (isset($_POST['affiliatepress_llms_course_nonce_action_field']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['affiliatepress_llms_course_nonce_action_field'])), 'affiliatepress_llms_course_nonce_action_field')){ //phpcs:ignore

                $affiliatepress_disable_commission_lifterlms = !empty($_POST['affiliatepress_commission_disable_lifterlms']) ? sanitize_text_field(wp_unslash($_POST['affiliatepress_commission_disable_lifterlms'])) : '';
                if ( ! empty( $_POST['affiliatepress_commission_disable_lifterlms'] ) ) {
                    update_post_meta( $affiliatepress_post_id, 'affiliatepress_commission_disable_lifterlms', esc_attr($affiliatepress_disable_commission_lifterlms) );
                } else {
                    delete_post_meta( $affiliatepress_post_id, 'affiliatepress_commission_disable_lifterlms' );
                }

                do_action('affiliatepress_lifterlms_settings_save' , $affiliatepress_post_id , $affiliatepress_product_name);
            }
        }

        
        /**
         * Function For Check plugin Active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active()
        {
            $affiliatepress_flag = true;

            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            if ( !is_plugin_active( 'lifterlms/lifterlms.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Check integration Settings 
         *
         * @return bool
         */
        function affiliatepress_lifter_lms_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_lifter_lms = $AffiliatePress->affiliatepress_get_settings('enable_lifter_lms', 'integrations_settings');
            if($affiliatepress_enable_lifter_lms != 'true'){
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }
        
        /**
         * Function For add pending commission after status change
         *
         * @param  array $affiliatepress_order
         * @return void
         */
        function affiliatepress_add_pending_commission_on_status_change_lifterlms($affiliatepress_order){

            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;

            if ( ! $affiliatepress_order instanceof LLMS_Order ) {
                return;
            }
            $affiliatepress_order_id = !empty($affiliatepress_order->get( 'id' )) ? $affiliatepress_order->get( 'id' ) : 0;

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);

            if(!empty($affiliatepress_all_commission_data)){
                foreach($affiliatepress_all_commission_data as $affiliatepress_commission_data){

                    if(!empty($affiliatepress_commission_data)){
                        $affiliatepress_ap_commission_status = (isset($affiliatepress_commission_data['ap_commission_status']))?intval($affiliatepress_commission_data['ap_commission_status']):0;
                        $affiliatepress_ap_commission_id     = (isset($affiliatepress_commission_data['ap_commission_id']))?intval($affiliatepress_commission_data['ap_commission_id']):0;
                        if($affiliatepress_ap_commission_status == 4){
                            $affiliatepress_debug_log_msg = sprintf( 'Commission #%s could not be pending because it was already paid.', $affiliatepress_ap_commission_id );
                            return;
                        }
                        if($affiliatepress_ap_commission_id != 0){
        
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => 2
                            );
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
                            $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as pending, after order #%s status change.', $affiliatepress_ap_commission_id, $affiliatepress_order_id );
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Reject ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                        }
                    }

                }
            }


        }
    }
}

global $affiliatepress_lifter_lms;
$affiliatepress_lifter_lms = new affiliatepress_lifter_lms();