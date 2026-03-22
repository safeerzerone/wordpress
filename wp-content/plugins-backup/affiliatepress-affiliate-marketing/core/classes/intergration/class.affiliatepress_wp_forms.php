<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_wp_forms') ){
    
    class affiliatepress_wp_forms Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){     

            global $affiliatepress_is_wp_forms_active ;
            $affiliatepress_is_wp_forms_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'wp_forms';
            if($this->affiliatepress_wp_forms_commission_add() && $affiliatepress_is_wp_forms_active){

                /**Add pending Commission */
                add_action ( 'wpforms_process_complete' ,array($this , 'affiliatepress_add_pending_referral_wpforms') , 10, 4);

                /**Add Approevd commission */
                add_action( 'wpforms_stripe_process_complete', array($this , 'affiliatepress_accept_pending_commission_wpforms'), 11,6 );
                add_action( 'wpforms_paypal_standard_process_complete', array( $this, 'affiliatepress_accept_pending_commission_wpforms' ), 10, 6 );

                /**Add for Validation */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func_wp_forms'),15,5);

                /**Add Script Load */
                add_action( 'wpforms_builder_enqueues', array( $this, 'affiliatepress_admin_enqueues' ) );

                /**Add Affiliate Settings add */
                add_filter( 'wpforms_builder_settings_sections', array( $this, 'affiliatepres_settings_sections_wpforms' ), 10, 2 );

                /**Add for content */
                add_action( 'wpforms_form_settings_panel_content', array( $this, 'affiliatepress_panel_content_wpforms' ), 10, 2 );

                /**Save affiliate settings */
                add_action('wpforms_builder_save_form' , array($this , 'affiliatepress_forms_data_save_wpforms') ,10,2);

            }

            if($affiliatepress_is_wp_forms_active)
            {
                /**get order link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_wpforms_link_order_func'),10,3); 

                /* Add Wpforms Backend Product List */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_wpforms_source_product_func'),10,3); 
            }
        }

        
        /**
         * Function For add Script
         *
         * @return void
         */
        public function affiliatepress_admin_enqueues() {
            $affiliatepress_min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

            wp_enqueue_script(
                'affiliatepress-wpforms-admin-manager',
                AFFILIATEPRESS_URL . "/js/wpforms-admin-manager{$affiliatepress_min}.js",
                [ 'jquery' ],
                AFFILIATEPRESS_VERSION,
                false
            );
        }

        
        /**
         * Function For add settings tab
         *
         * @param  array $affiliatepress_sections
         * @param  array $affiliatepress_form_data
         * @return array
         */
        public function affiliatepres_settings_sections_wpforms( $affiliatepress_sections, $affiliatepress_form_data ) {
            $affiliatepress_sections['affiliatepress'] = esc_html__( 'AffiliatePress', 'affiliatepress-affiliate-marketing');

            return $affiliatepress_sections;
        }
        
        /**
         * Function For add affiliate settings
         *
         * @param  string $affiliatepress_instance
         * @return string
         */
        public function affiliatepress_panel_content_wpforms( $affiliatepress_instance ) {

            $affiliatepress_form_data = $affiliatepress_instance->form_data;
    
            echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-affiliatepress">';
            echo '<div class="wpforms-panel-content-section-title">';
            echo esc_html__( 'AffiliatePress', 'affiliatepress-affiliate-marketing');
            echo '</div>';
    
            $affiliatepress_hide = 'display:none;';

            wpforms_panel_field(
                'toggle',
                'settings',
                'affiliatepress_allow_referrals',
                $affiliatepress_form_data,
                esc_html__( 'Enable Commission', 'affiliatepress-affiliate-marketing'),
                [
                    'tooltip' => esc_html__( 'Allow this form to generate Commission.', 'affiliatepress-affiliate-marketing'),
                ]
            );

            echo '<div id="wpforms-affiliatepress-referrals-content-block" style="' . esc_attr( $affiliatepress_hide ) . '">';

            wpforms_panel_fields_group(
                $this->referral_fields( $affiliatepress_instance ),
                [
                    'title'       => esc_html__( 'Commission creation', 'affiliatepress-affiliate-marketing'),
                    'description' => esc_html__( 'Allow this form to generate Commission.', 'affiliatepress-affiliate-marketing'),
                ]
            );

            echo '</div>';

            echo '</div>';
        }

        
        /**
         * Function For add commission type settings
         *
         * @param  array $affiliatepress_instance
         * @return array
         */
        private function referral_fields( $affiliatepress_instance ) {

            $affiliatepress_output = '';

            $affiliatepress_options = array();

            $wp_forms_settings_extra_fields = "";
            $wp_forms_settings_extra_fields .= apply_filters( 'affiliatepress_wp_forms_add_product_settings', $wp_forms_settings_extra_fields, $affiliatepress_instance );

            if(!empty($wp_forms_settings_extra_fields))
            {
                $affiliatepress_output .= $wp_forms_settings_extra_fields;
            }

            $affiliatepress_options = array(
                'sale' => esc_html__('Sale', 'affiliatepress-affiliate-marketing'),
                'opt-in' => esc_html__('Opt-In', 'affiliatepress-affiliate-marketing'),
                'lead' => esc_html__('Lead', 'affiliatepress-affiliate-marketing'),
            );

            $affiliatepress_output .= wpforms_panel_field(
                'select',
                'settings',
                'affiliatepress_referral_type_wp_forms',
                $affiliatepress_instance->form_data,
                esc_html__( 'Commission type', 'affiliatepress-affiliate-marketing'),
                [
                    'options' => $affiliatepress_options,
                    'tooltip' => esc_html__( 'Select the type of Commission this should be.', 'affiliatepress-affiliate-marketing'),
                ],
                false
            );

            return $affiliatepress_output;
        }
        
        /**
         * Function For Save Affiliate settings
         *
         * @param  int $affiliatepress_form_id
         * @param  array $affiliatepress_form_data
         * @return void
         */
        function affiliatepress_forms_data_save_wpforms($affiliatepress_form_id,$affiliatepress_form_data)
        {
            do_action('affiliatepress_wp_forms_save_product_settings' ,$affiliatepress_form_id ,$affiliatepress_form_data);
        }
        
        /**
         * Function For WPForms get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_wpforms_link_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href="'.admin_url("admin.php?page=wpforms-payments&view=payment&payment_id=".$affiliatepress_ap_commission_reference_id).'");"> '. $affiliatepress_ap_commission_reference_id .' </a>';
                
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
        function affiliatepress_get_wpforms_source_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){
            
            global $wpdb;
            
            if ($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug) {
                
                $affiliatepress_existing_products_data = array();

                $affiliatepress_args = array(
                    'post_type'   => 'wpforms',  // Your custom post type
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
         * Function for affiliate validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  int $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_order_data
         * @return array
         */
        function affiliatepress_commission_validation_func_wp_forms($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_payment_id, $affiliatepress_fields){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                       
                        $affiliatepress_billing_email = "";
                        foreach ( $affiliatepress_fields as $affiliatepress_field ) {
                            if ( 'email' === $affiliatepress_field['type'] ) {
                                $affiliatepress_billing_email = !empty($affiliatepress_field['value']) ? sanitize_email($affiliatepress_field['value']) : '';
                                break;
                            }
                        }
                        
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
         * Function For add Pending Commision
         *
         * @param  array $affiliatepress_fields
         * @param  array $affiliatepress_entry
         * @param  array $affiliatepress_form_data
         * @param  int $affiliatepress_entry_id
         * @return void
         */
        public function affiliatepress_add_pending_referral_wpforms( $affiliatepress_fields, $affiliatepress_entry, $affiliatepress_form_data, $affiliatepress_entry_id ) {

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id ;

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();  

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;

            $affiliatepress_tbl_wpforms_payments = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'wpforms_payments' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'wpforms_payments' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_payment = $wpdb->get_row($wpdb->prepare("SELECT id, status FROM {$affiliatepress_tbl_wpforms_payments} WHERE entry_id = %d",$affiliatepress_entry_id),ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_wpforms_payments is a table name. false alarm 

            $affiliatepress_payment_id = $affiliatepress_payment['id'];
            $affiliatepress_payment_status = $affiliatepress_payment['status'];

            if(!$affiliatepress_payment_id)
            {
                return;
            }

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_payment_id) );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_payment_id, $affiliatepress_fields);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_email      = "";
            $affiliatepress_first_name = "";
            $affiliatepress_last_name  = "";

            foreach ( $affiliatepress_fields as $affiliatepress_field ) {

                if ( 'email' === $affiliatepress_field['type'] ) {
                    $affiliatepress_email = isset( $affiliatepress_field['value'] )? sanitize_email( $affiliatepress_field['value'] ) : '';
                }

                if ( 'name' === $affiliatepress_field['type'] ) {
                    $affiliatepress_first_name = isset( $affiliatepress_field['first'] )? sanitize_text_field( $affiliatepress_field['first'] ): '';
                    $affiliatepress_last_name = isset( $affiliatepress_field['last'] ) ? sanitize_text_field( $affiliatepress_field['last'] ): '';
                }
            }

            $affiliatepress_customer_args = array(
                'email'   	   => $affiliatepress_email,
                'user_id' 	   => '',
                'first_name'   => $affiliatepress_first_name,
                'last_name'	   => $affiliatepress_last_name,
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
            $affiliatepress_form_id = !empty($affiliatepress_form_data['id']) ? intval($affiliatepress_form_data['id']):0;
            $affiliatepress_form_name = !empty($affiliatepress_form_data['settings']['form_title']) ? sanitize_text_field($affiliatepress_form_data['settings']['form_title']) : '';

            // Get the referral type.
		    $affiliatepress_commission_type = isset( $affiliatepress_form_data['settings']['affiliatepress_referral_type_wp_forms'] ) ? strval( $affiliatepress_form_data['settings']['affiliatepress_referral_type_wp_forms'] ) : 'sale';
            $affiliatepress_order_referal_amount = 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_amount = 0;
                if ( function_exists( 'wpforms_get_total_payment' ) ) {
                    $affiliatepress_amount = wpforms_get_total_payment( $affiliatepress_fields );
                }

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => $affiliatepress_commission_type,
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => $affiliatepress_form_id,
                    'order_id'     => $affiliatepress_payment_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, '', $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_form_id,
                    'product_name'         => $affiliatepress_form_name,
                    'order_id'             => $affiliatepress_payment_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );

                $affiliatepress_amount = 0;
                if ( function_exists( 'wpforms_get_total_payment' ) ) {
                    $affiliatepress_amount = wpforms_get_total_payment( $affiliatepress_fields );
                }

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

            }
            else
            {
                $affiliatepress_amount = 0;
                if ( function_exists( 'wpforms_get_total_payment' ) ) {
                    $affiliatepress_amount = wpforms_get_total_payment( $affiliatepress_fields );
                }

                $affiliatepress_disable_option = isset( $affiliatepress_form_data['settings']['affiliatepress_allow_referrals']) ? intval($affiliatepress_form_data['settings']['affiliatepress_allow_referrals']) : 0;

                $affiliatepress_wp_forms_product = array(
                    'product_id'=> $affiliatepress_form_id,
                    'source'=>$this->affiliatepress_integration_slug,
                    'affiliatepress_wp_forms_settings' =>$affiliatepress_disable_option
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_wp_forms_product );
    
                if($affiliatepress_product_disable){
                    return;
                }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_form_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_payment_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, '', $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_form_id,
                    'product_name'         => $affiliatepress_form_name,
                    'order_id'             => $affiliatepress_payment_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $affiliatepress_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );

                $affiliatepress_commission_products_ids[] = $affiliatepress_form_id;
                $affiliatepress_commission_products_name[] = $affiliatepress_form_name;
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_payment_id, $affiliatepress_fields);

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
                'ap_commission_reference_id'     => $affiliatepress_payment_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_order_referal_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')) // phpcs:ignore
            );

            if($affiliatepress_payment_status == "completed")
            {
                $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

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

                    if($affiliatepress_updated_commission_status == 1)
                    {
                        do_action('affiliatepress_after_commissions_status_change',$affiliatepress_ap_commission_id,1,2);
                    }
                    $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                    do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data);
                    $affiliatepress_debug_log_msg = sprintf( 'commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );
    
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
                }
                
            }else
            {
                $affiliatepress_commission_data['ap_commission_status'] = 2;
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
        }
       
       /**
        * Function For add Approved Commission
        *
        * @param  array $affiliatepress_fields
        * @param  array $affiliatepress_form_data
        * @param  int $affiliatepress_payment_id
        * @param  array $affiliatepress_payment
        * @param  array $affiliatepress_subscription
        * @param  array $affiliatepress_get_customer
        * @return void
        */
        function affiliatepress_accept_pending_commission_wpforms( $affiliatepress_fields, $affiliatepress_form_data, $affiliatepress_payment_id, $affiliatepress_payment, $affiliatepress_subscription, $affiliatepress_get_customer )
        {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking ,$AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_payment_id, $this->affiliatepress_integration_slug,' AND ap_commission_status = 2');

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

            if ( is_plugin_active( 'wpforms/wpforms.php' )) {
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
        function affiliatepress_wp_forms_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_wp_forms = $AffiliatePress->affiliatepress_get_settings('enable_wp_forms', 'integrations_settings');
            if($affiliatepress_enable_wp_forms != 'true'){
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }
    }
}

global $affiliatepress_wp_forms;
$affiliatepress_wp_forms = new affiliatepress_wp_forms();