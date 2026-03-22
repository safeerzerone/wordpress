<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_give') ){

    class affiliatepress_give Extends AffiliatePress_Core{

        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){   
        
            global $affiliatepress_is_give_active ;
            $affiliatepress_is_give_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'give_wp';
            if($this->affiliatepress_give_commission_add() && $affiliatepress_is_give_active){
                
                /**Add Pending Commission */
                add_action( 'give_insert_payment', array( $this, 'affiliatepress_add_pending_referral_givewp' ), 10, 2 );

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

                /**Add disable Settings */
                add_filter( 'give_metabox_form_data_settings', array( $this, 'affiliatepress_add_donation_settings_givewp' ), 99 );

                /**Add Approved Commission */
                add_action( 'give_complete_form_donation', array( $this, 'affiliatepress_accept_pending_commission_givewp' ), 10, 3 );

                /**Add Approved Commission After status chnage */
                add_action( 'give_update_payment_status', array( $this, 'affiliatepress_change_completed_status_givewp' ), 10, 3 );

                /**Add Pending Commission After status chnage */
                add_action( 'give_update_payment_status', array( $this, 'affiliatepress_change_pending_status_givewp' ), 10, 3 );

                /**Add Reject Commission After status chnage */
                add_action( 'give_update_payment_status', array( $this, 'affiliatepress_rejected_commission_givewp' ), 10, 3 );

                /**Add Reject Commission After Delete */
                add_action( 'give_payment_delete', array( $this, 'affiliatepress_revoke_referral_on_delete_givewp' ), 10 );

                if($this->affiliatepress_check_givewp_version()){

                    add_action( 'givewp_form_builder_enqueue_scripts', array( $this, 'affiliatepress_disable_settings_add_givewp' ) );

                    add_action( 'givewp_form_builder_updated', array( $this, 'affiliatepress_save_give_settings_data' ), 10, 2 );
                }

            }

            if($affiliatepress_is_give_active)
            {
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_give_link_order_func'),10,3); 

                /* Add Give Backend Product List */
               add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_give_wp_product_func'),10,3); 
            }
        }
        
        /**
         * Function For Give WP get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_give_link_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_url = admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-payment-details&id=' . $affiliatepress_ap_commission_reference_id );

                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href=" '.esc_url( $affiliatepress_url ). ' "> '. $affiliatepress_ap_commission_reference_id .' </a>';

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
        function affiliatepress_get_give_wp_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str) {
            global $wpdb;
            
            if ($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug) {
                
                $affiliatepress_existing_products_data = array();
                $affiliatepress_args = array(
                    'post_type'   => 'give_forms',  // Your custom post type
                    'post_status' => 'publish',   // Only published posts
                    's'           => $affiliatepress_search_product_str, // Search term
                    'fields'      => 'ids',       // Only return post IDs
                );

                $affiliatepress_query = new WP_Query($affiliatepress_args);

                if ($affiliatepress_query->have_posts()) {
                    $affiliatepress_post_ids = $affiliatepress_query->posts;
                    foreach ($affiliatepress_post_ids as $affiliatepress_post_id) {

                        $affiliatepress_post_name = get_the_title($affiliatepress_post_id);

                        // echo "<>".$affiliatepress_post_name."<br>";
                        
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
         * Function For Add Disable Commission
         *
         * @param  array $affiliatepress_settings
         * @return array
         */
        public function affiliatepress_add_donation_settings_givewp( $affiliatepress_settings ) {
            $affiliatepress_settings_fields = array(
                array(
                    'name' => esc_html__( 'Enable commission', 'affiliatepress-affiliate-marketing'),
                    'desc' => esc_html__( 'Enable AffiliatePress Commission to add commission for this Form', 'affiliatepress-affiliate-marketing'),
                    'id'   => 'affiliatepress_give_disable_commission',
                    'type' => 'checkbox'
                ),
            );

            $affiliatepress_settings_fields = apply_filters( 'affiliatepress_give_wp_add_product_settings', $affiliatepress_settings_fields );

            $affiliatepress_settings[ 'affiliatepress' ] = array(
                'id'     => "affiliatepress",
                'title'  => esc_html__( 'AffiliatePress', 'affiliatepress-affiliate-marketing'),
                'fields' => $affiliatepress_settings_fields
            );

            return $affiliatepress_settings;
        }

         /**
         * Function For Add Disable Commission in give wp form builder
         *
         * @param  
         * @return array
         */
        function affiliatepress_disable_settings_add_givewp() {
            if ( ! is_admin() ) return;

            $affiliatepress_is_give_wp_forms = ( isset($_GET['post_type'], $_GET['page']) && $_GET['post_type'] === 'give_forms' &&  $_GET['page'] === 'givewp-form-builder'); // phpcs:ignore
            if ( ! $affiliatepress_is_give_wp_forms ) {
                return;
            }

            wp_register_script('affiliatepress_give_settings', AFFILIATEPRESS_URL . 'js/affiliatepress_give.js', array('wp-hooks', 'wp-i18n', 'wp-element', 'wp-components', 'react', 'react-dom'), AFFILIATEPRESS_VERSION,  false);
            wp_enqueue_script('affiliatepress_give_settings');

            $affiliatepress_form_id = 0;
            $affiliatepress_doantion_form_id = isset($_GET['donationFormID']) ? intval($_GET['donationFormID']) : 0;// phpcs:ignore
            $affiliatepress_post_form_id = isset($_GET['post']) ? intval($_GET['post']) : 0;// phpcs:ignore
            $affiliatepress_get_form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;// phpcs:ignore

            if ( $affiliatepress_doantion_form_id > 0 ) {
                $affiliatepress_form_id = $affiliatepress_doantion_form_id;
            } elseif ( $affiliatepress_post_form_id > 0 ) {
                $affiliatepress_form_id = $affiliatepress_post_form_id;
            } elseif ( $affiliatepress_get_form_id > 0 ) {
                $affiliatepress_form_id = $affiliatepress_get_form_id;
            } else {
                $affiliatepress_form_id = 0;
            }

            $affiliatepress_allow_commission = $affiliatepress_form_id ? give_get_meta($affiliatepress_form_id, 'affiliatepress_give_disable_commission', true) : false;

            $affiliatepress_give_settings = array(
                'affiliatepress_allow_give_commision' => (bool) $affiliatepress_allow_commission,
                'formId'                              => $affiliatepress_form_id,
                'nonce'                               => wp_create_nonce('affiliatepress_give_save_settings'),
                'strings'                             => [
                    'sectionTitle'           => esc_html__('AffiliatePress', 'affiliatepress-affiliate-marketing'),
                    'allow_commission_label' => esc_html__('Enable Commission', 'affiliatepress-affiliate-marketing'),
                    'allow_commission_desc'  => esc_html__('Enable affiliate commission creation for this donation form', 'affiliatepress-affiliate-marketing'),
                ],
            );

            wp_localize_script(
                'affiliatepress_give_settings',
                'affiliate_give_settings',
                $affiliatepress_give_settings
            );
        }

        
        function affiliatepress_save_give_settings_data($form, $request){
            $settings = $request->get_param('settings');
        
            if ( is_string( $settings ) ) {
                $settings = json_decode( $settings, true );
            }
        
            // Enable/Disable commission
            if ( isset( $settings['affiliatepress_allow_give_commision'] ) ) {
                $value = $settings['affiliatepress_allow_give_commision'];
                if ( true === $value || 'true' === $value || '1' === $value || 1 === $value ) {
                    give_update_meta( $form->id, 'affiliatepress_give_disable_commission', '1', '', 'form' );
                } else {
                    give_delete_meta( $form->id, 'affiliatepress_give_disable_commission', '', 'form' );
                }
            }
        }

        /**
         * Function for affiliate validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  int $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  string $affiliatepress_payment_id
         * @param  array $affiliatepress_user
         * @return void
         */
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_payment_id, $affiliatepress_user){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = !empty($affiliatepress_user['email']) ? sanitize_email($affiliatepress_user['email']) : '';
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
         * Function For Add Pending Commission
         *
         * @param  int $affiliatepress_payment_id
         * @param  array $affiliatepress_payment_data
         * @return void
         */
        function affiliatepress_add_pending_referral_givewp( $affiliatepress_payment_id , $affiliatepress_payment_data )
        {            
            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_payment_data = !empty($affiliatepress_payment_data) ? $affiliatepress_payment_data : array();

            $affiliatepress_payment_id = !empty($affiliatepress_payment_id) ? $affiliatepress_payment_id : 0;

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit(); 

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;
            $affiliatepress_form_id =$affiliatepress_payment_data['give_form_id'];

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_payment_id) );

            $affiliatepress_givewp_product = array(
                'product_id'=> $affiliatepress_form_id,
                'source'=>$this->affiliatepress_integration_slug
            );

            $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_givewp_product );
            if($affiliatepress_product_disable){
                return;
            }

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_user_info = $affiliatepress_payment_data['user_info'];

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_payment_id, $affiliatepress_user_info);

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
                'email'   	   => !empty($affiliatepress_user_info['email']) ? sanitize_email($affiliatepress_user_info['email']) : '',
                'user_id' 	   => !empty($affiliatepress_user_info['id']) ? intval($affiliatepress_user_info['id']) : 0,
                'first_name'   => !empty($affiliatepress_user_info['first_name']) ? sanitize_text_field($affiliatepress_user_info['first_name']) : '',
                'last_name'	   => !empty($affiliatepress_user_info['last_name']) ? sanitize_text_field($affiliatepress_user_info['last_name']) : '',
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

            $affiliatepress_product_id = !empty($affiliatepress_payment_data['give_form_id']) ? intval($affiliatepress_payment_data['give_form_id']) : 0;
            $affiliatepress_product_name = !empty($affiliatepress_payment_data['give_form_title']) ? sanitize_text_field($affiliatepress_payment_data['give_form_title']) :'';

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_total_price = isset($affiliatepress_payment_data['price']) ? floatval($affiliatepress_payment_data['price']) : 0;

                $affiliatepress_amount = $affiliatepress_total_price;

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => 'sale',
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => $affiliatepress_product_id,
                    'order_id'     => $affiliatepress_payment_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_payment_data['currency'], $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_product_name,
                    'order_id'             => $affiliatepress_payment_id,
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
                $affiliatepress_amount = !empty($affiliatepress_payment_data['price']) ? floatval($affiliatepress_payment_data['price']):0;

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_product_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_payment_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_payment_data['currency'], $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_product_name,
                    'order_id'             => $affiliatepress_payment_id,
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
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_payment_id, $affiliatepress_payment_data);

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
                'ap_commission_reference_id'     => $affiliatepress_payment_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_payment_data['price'],
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp'))// phpcs:ignore
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
         * Function For Add Approved Commission
         *
         * @param  int $affiliatepress_form_id
         * @param  int $affiliatepress_payment_id
         * @param  array $affiliatepress_payment_meta
         * @return array
         */
        public function affiliatepress_accept_pending_commission_givewp( $affiliatepress_form_id, $affiliatepress_payment_id, $affiliatepress_payment_meta ) {
            
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_payment_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');

            if(!empty($affiliatepress_all_commission_data)){

                foreach($affiliatepress_all_commission_data as $affiliatepress_single_commission_data){


                    $affiliatepress_commission_id = (isset($affiliatepress_single_commission_data['ap_commission_id']))?intval($affiliatepress_single_commission_data['ap_commission_id']):0;

                    if($affiliatepress_commission_id != 0){
                
                        $affiliatepress_updated_commission_status = 1;
                        if($affiliatepress_default_commission_status != "auto"){
        
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

        
        /**
         * Function For Add Approved Commission after status chnage
         *
         * @param  int $affiliatepress_payment_id
         * @param  string $affiliatepress_new_status
         * @param  string $affiliatepress_old_status
         * @return void
         */
        function affiliatepress_change_completed_status_givewp($affiliatepress_payment_id, $affiliatepress_new_status, $affiliatepress_old_status)
        {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;

            if ( 'publish' != $affiliatepress_new_status ) {
                return;
            }

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_payment_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');

            if(!empty($affiliatepress_all_commission_data)){

                foreach($affiliatepress_all_commission_data as $affiliatepress_single_commission_data){


                    $affiliatepress_commission_id = (isset($affiliatepress_single_commission_data['ap_commission_id']))?intval($affiliatepress_single_commission_data['ap_commission_id']):0;

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
        

        /**
         * Function For Add Reject Commission after status chnage
         *
         * @param  int $affiliatepress_payment_id
         * @param  string $affiliatepress_new_status
         * @param  string $affiliatepress_old_status
         * @return void
         */
        function affiliatepress_revoke_referral_on_delete_givewp($affiliatepress_payment_id)
        {
            global $AffiliatePress,$affiliatepress_commission_debug_log_id ,$affiliatepress_tbl_ap_affiliate_commissions;

            $affiliatepress_payment_id = !empty($affiliatepress_payment_id) ? $affiliatepress_payment_id : '';

            $affiliatepress_all_commissition_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_payment_id, $this->affiliatepress_integration_slug);    

            if(!empty($affiliatepress_all_commissition_data)){

                foreach($affiliatepress_all_commissition_data as $affiliatepress_commissition_data){

                    if(!empty($affiliatepress_commissition_data)){

                        $affiliatepress_ap_commission_status = (isset($affiliatepress_commissition_data['ap_commission_status']))? intval($affiliatepress_commissition_data['ap_commission_status']):0;
                        $affiliatepress_ap_commission_id     = (isset($affiliatepress_commissition_data['ap_commission_id']))?intval($affiliatepress_commissition_data['ap_commission_id']):0;
                        if($affiliatepress_ap_commission_status == 4){
                            $affiliatepress_debug_log_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_ap_commission_id );
                            return;
                        }
                        if($affiliatepress_ap_commission_id != 0){
        
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => 3
                            );
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
        
                            $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as rejected, after payment #%s Deleted.', $affiliatepress_ap_commission_id, $affiliatepress_payment_id );
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Reject ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                        }                                
                    }

                }

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

            if ( !is_plugin_active( 'give/give.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }

        /**
         * Function For Check Plugin version
         *
         * @return void
         */
        function affiliatepress_check_givewp_version()
        {
            $affiliatepress_flag = false;

            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            
            if ( is_plugin_active( 'give/give.php' ) ) {
                $affiliatepress_give_data = get_plugin_data( WP_PLUGIN_DIR .'/give/give.php');
                $affiliatepress_give_version = isset($affiliatepress_give_data['Version']) ? $affiliatepress_give_data['Version'] : '';
                if(version_compare( $affiliatepress_give_version, '3.0', '>=' )){
                    $affiliatepress_flag = true;
                }
            }

            return $affiliatepress_flag;
        }
        
        
        /**
         * Function For Check Enable iuntegration settings
         *
         * @return void
         */
        function affiliatepress_give_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_give_wp = $AffiliatePress->affiliatepress_get_settings('enable_give_wp', 'integrations_settings');
            if($affiliatepress_enable_give_wp != 'true'){
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }

         /**
         * Function For Add Pending commission after status change
         *
         * @param  int $affiliatepress_payment_id
         * @param  string $affiliatepress_new_status
         * @param  string $affiliatepress_old_status
         * @return void
         */
        function affiliatepress_change_pending_status_givewp( $affiliatepress_payment_id, $affiliatepress_new_status, $affiliatepress_old_status)
        {
            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;
        
            if ( $affiliatepress_new_status == "pending"  || $affiliatepress_new_status == "processing") {
                
                $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_payment_id, $this->affiliatepress_integration_slug);
                if(!empty($affiliatepress_all_commission_data)){

                    foreach($affiliatepress_all_commission_data as $affiliatepress_commission_data){

                        if(!empty($affiliatepress_commission_data)){
                            $affiliatepress_ap_commission_status = (isset($affiliatepress_commission_data['ap_commission_status']))?intval($affiliatepress_commission_data['ap_commission_status']):0;
                            $affiliatepress_ap_commission_id     = (isset($affiliatepress_commission_data['ap_commission_id']))?intval($affiliatepress_commission_data['ap_commission_id']):0;
                            if($affiliatepress_ap_commission_status == 4){
                                $affiliatepress_debug_log_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_ap_commission_id );
                                return;
                            }
                            if($affiliatepress_ap_commission_id != 0){
        
                                $affiliatepress_commission_data = array(
                                    'ap_commission_updated_date' => current_time( 'mysql', true ),
                                    'ap_commission_status' 		 => 2
                                );
                                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
                                $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as pending, after order #%s status change pending.', $affiliatepress_ap_commission_id, $affiliatepress_payment_id );
        
                                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Pending ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                            }
                        }

                    }

                }


            }

        }
        
        /**
        * Function For Add Reject commission after status change
        *
        * @param  int $affiliatepress_payment_id
        * @param  string $affiliatepress_new_status
        * @param  string $affiliatepress_old_status
        * @return void
        */
        function affiliatepress_rejected_commission_givewp($affiliatepress_payment_id, $affiliatepress_new_status, $affiliatepress_old_status)
        {
            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;
        
            if ( $affiliatepress_new_status == "failed"  || $affiliatepress_new_status == "cancelled" || $affiliatepress_new_status =="abandoned" || $affiliatepress_new_status == "revoked" ) {

                $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_payment_id, $this->affiliatepress_integration_slug);

                if(!empty($affiliatepress_all_commission_data)){

                    foreach($affiliatepress_all_commission_data as $affiliatepress_commission_data){

                        if(!empty($affiliatepress_commission_data)){
                            $affiliatepress_ap_commission_status = (isset($affiliatepress_commission_data['ap_commission_status']))?intval($affiliatepress_commission_data['ap_commission_status']):0;
                            $affiliatepress_ap_commission_id     = (isset($affiliatepress_commission_data['ap_commission_id']))?intval($affiliatepress_commission_data['ap_commission_id']):0;
                            if($affiliatepress_ap_commission_status == 4){
                                $affiliatepress_debug_log_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_ap_commission_id );
                                return;
                            }
                            if($affiliatepress_ap_commission_id != 0){
        
                                $affiliatepress_commission_data = array(
                                    'ap_commission_updated_date' => current_time( 'mysql', true ),
                                    'ap_commission_status' 		 => 3
                                );
                                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
                                $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as rejected, after order #%s  status change.', $affiliatepress_ap_commission_id, $affiliatepress_payment_id );
        
                                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission rejected ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                            }
                        }

                    }

                }



            }
              
        }
    }

}
global $affiliatepress_give;
$affiliatepress_give = new affiliatepress_give();