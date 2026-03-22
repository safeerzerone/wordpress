<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_accept_stripe_payments') ){
    
    class affiliatepress_accept_stripe_payments Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){

            global $affiliatepress_is_accept_stripe_payments_active;
            $affiliatepress_is_accept_stripe_payments_active = $this->affiliatepress_check_plugin_active();
            $this->affiliatepress_integration_slug = 'accept_stripe_payments';

            if($this->affiliatepress_accept_stripe_payments_add() && $affiliatepress_is_accept_stripe_payments_active){
                
                /**Function For Add accept Stripe Paymnet Commission add */
                add_action('asp_stripe_payment_completed',array( $this, 'affiliatepress_accept_stripe_payments_add_commission' ) ,10,2);

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);
                
                /**Add Approved commission */
                add_action('save_post',array($this, 'affiliatepress_accept_stripe_payment'), 19,1);

                // Add the commission settings in download page
                add_action( 'add_meta_boxes', array($this,'affiliatepress_add_commission_settings_metabox_asp'), 10, 1 );

                // Save the affiliate id in the product meta
                add_action( 'save_post', array($this,'affiliatepress_save_product_commission_settings_asp'), 10, 1 );

            }

            if($affiliatepress_is_accept_stripe_payments_active){
                /** Function For get order link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_asp_link_order_func'),10,3); 

                /* Add edd Backend Product List */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_asp_product_func'),10,3); 
            }
        }
        
        /**
         * Setting Meta box add
         *
         * @param  string $affiliatepress_post_type
         * @return void
         */
        function affiliatepress_add_commission_settings_metabox_asp( $affiliatepress_post_type ) {

            // Check that post type is 'asp-products'
            if ( $affiliatepress_post_type != 'asp-products' ) {
                return;
            }

            add_meta_box( 'affiliatepress_metabox_commission_settings', esc_html__( 'AffiliatePress Commission Settings', 'affiliatepress-affiliate-marketing'), array($this, 'affiliatepress_add_product_commission_settings_asp'), $affiliatepress_post_type, 'advanced', 'high' );
        }
        
        /**
         * Function For Accept Stripe paymnet in affiliate settings add
         *
         * @return void
         */
        function affiliatepress_add_product_commission_settings_asp() {
            global $post;

            // Get the disable commissions value
            $affiliatepress_disable_commission_accept_stripe_payments = get_post_meta( $post->ID, 'affiliatepress_disable_commission_accept_stripe_payments', true );
            wp_nonce_field( 'affiliatepress_commission_nonce_accept_stripe_payments', 'affiliatepress_commission_nonce_accept_stripe_payments' );
            ?>
                <div id="affiliatepress_product_settings" class="affiliatepress-options-groups-wrapper" style="margin-top: 20px; margin-bottom:20px;">
                    <div class="affiliatepress-options-group">
                        <div class="affiliatepress-option-field-wrapper">
                            <label for="affiliatepress-disable-commissions"><?php esc_html_e( 'Disable Commissions', 'affiliatepress-affiliate-marketing'); ?></label>
                            <label for="affiliatepress-disable-commissions" style="margin-left: 80px;">
                                <input type="checkbox" class="affiliatepress_disable_commission_accept_stripe_payments" name="affiliatepress_disable_commission_accept_stripe_payments" id="affiliatepress_disable_commission_accept_stripe_payments" value="1"<?php checked( esc_html($affiliatepress_disable_commission_accept_stripe_payments), true ); ?> />
                                <?php esc_html_e( 'Disable commissions for this Product.', 'affiliatepress-affiliate-marketing'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            <?php
            do_action('affiliatepress_accept_stripe_payments_add_product_settings');
        }
        
        /**
         * Function For Accept Stripe paymnet in affiliate settings Save
         *
         * @param  integer $post_id
         * @return void
         */
        function affiliatepress_save_product_commission_settings_asp($post_id) {

            if (!$post_id || empty($_POST['affiliatepress_commission_nonce_accept_stripe_payments'])) {
                return;
            }
            if (wp_verify_nonce( $_POST['affiliatepress_commission_nonce_accept_stripe_payments'], 'affiliatepress_commission_nonce_accept_stripe_payments' )){   // phpcs:ignore
                if ( ! empty( $_POST['affiliatepress_disable_commission_accept_stripe_payments'] ) ) {
                    update_post_meta( $post_id, 'affiliatepress_disable_commission_accept_stripe_payments', 1 );
                } else {
                    delete_post_meta( $post_id, 'affiliatepress_disable_commission_accept_stripe_payments' );
                }
                do_action('affiliatepress_accept_stripe_payments_settings_save' , $post_id);
            }
        }
        
        /**
         * Function For Accept Stripe paymnet get order link
         *
         * @param  integer $affiliatepress_commission_reference_id
         * @param  string $affiliatepress_commission_source
         * @return string
         */
        function affiliatepress_get_asp_link_order_func($affiliatepress_commission_reference_id, $affiliatepress_commission_source){
            
            if($affiliatepress_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_url = admin_url("post.php?post=".$affiliatepress_commission_reference_id."&action=edit");

                $affiliatepress_commission_order_link = '<a target="_blank" class="ap-refrance-link" href=" '.esc_url( $affiliatepress_url ). ' "> '. $affiliatepress_commission_reference_id .' </a>';

                $affiliatepress_commission_reference_id = $affiliatepress_commission_order_link;
            }
            
            return $affiliatepress_commission_reference_id;
        }
        
        /**
         * Function for get source product for backend commission add/edit
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param  string $affiliatepress_commission_source
         * @param  string $affiliatepress_search_product_str
         * @return array
         */
        function affiliatepress_get_asp_product_func($affiliatepress_existing_source_product_data, $affiliatepress_commission_source, $affiliatepress_search_product_str) {
            global $wpdb;
            
            if ($affiliatepress_commission_source == $this->affiliatepress_integration_slug) {
                
                $affiliatepress_existing_products_data = array();

                $affiliatepress_args = array(
                    'post_type'   => 'asp-products',  // Your custom post type
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
         * Function For Check Accept Stripe Payments plugin
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if(!function_exists('is_plugin_active')){
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if(is_plugin_active('stripe-payments/accept-stripe-payments.php')){
                $affiliatepress_flag = true;
            }else{
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }     
                
        /**
         * Function For Check Accept Stripe Paymnets settings enable
         *
         * @return bool
         */
        function affiliatepress_accept_stripe_payments_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_accept_stripe_payments = $AffiliatePress->affiliatepress_get_settings('enable_accept_stripe_payments', 'integrations_settings');
            if($affiliatepress_enable_accept_stripe_payments != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
    
        /**
         * Function for affiliate validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  integer $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  integer $affiliatepress_order_id
         * @param  array $affiliatepress_order_data
         * @return array
         */
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order_data){

            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = isset($affiliatepress_order_data['stripeEmail']) ? sanitize_email($affiliatepress_order_data['stripeEmail']) : '';
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
         * Function For Add accept Stripe Paymnet Commission add
         *
         * @param  array $affiliatepress_data
         * @param  object $affiliatepress_charge
         * @return void
         */
        function affiliatepress_accept_stripe_payments_add_commission($affiliatepress_order_data, $affiliatepress_charge )
        {
            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_order_id = isset($affiliatepress_order_data['order_post_id']) ? intval($affiliatepress_order_data['order_post_id']) : 0;

            $affiliatepress_product_id = isset($affiliatepress_order_data['product_id']) ? intval($affiliatepress_order_data['product_id']) :0;

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();  
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id):0;

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id' => $affiliatepress_order_id) );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();
            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_order_data);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_email = isset($affiliatepress_order_data['stripeEmail']) ? sanitize_email($affiliatepress_order_data['stripeEmail']) : '';

            $affiliatepress_customer_args = array(
                'email'   	   => $affiliatepress_email,
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

            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('accept_stripe_payments_exclude_taxes', 'integrations_settings');            
            $affiliatepress_exclude_shipping = $AffiliatePress->affiliatepress_get_settings('accept_stripe_payments_exclude_shipping', 'integrations_settings');

            $affiliatepress_commission_type = 'sale';
            $affiliatepress_commission_type = !empty($affiliatepress_commission_type) ? sanitize_text_field($affiliatepress_commission_type) : '';
            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_product_name = isset($affiliatepress_order_data['item_name']) ? sanitize_text_field($affiliatepress_order_data['item_name']) :'';
            $affiliatepress_currency = isset($affiliatepress_order_data['currency_code']) ? sanitize_text_field($affiliatepress_order_data['currency_code']) : '';

            $affiliatepress_total_amount = isset($affiliatepress_order_data['paid_amount']) ? floatval($affiliatepress_order_data['paid_amount']) : 0;

            $affiliatepress_commission_products_ids[] = $affiliatepress_product_id;
            $affiliatepress_commission_products_name[] = $affiliatepress_product_name;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_amount = $affiliatepress_total_amount;

                $affiliatepress_tax = isset($affiliatepress_order_data['tax']) ? floatval($affiliatepress_order_data['tax']) : 0;

                $affiliatepress_shipping = isset($affiliatepress_order_data['shipping']) ? floatval($affiliatepress_order_data['shipping']) : 0;

                if($affiliatepress_exclude_taxes == "true")
                {
                    $affiliatepress_amount = $affiliatepress_amount-$affiliatepress_tax;
                }

                if($affiliatepress_exclude_shipping == "true")
                {
                    $affiliatepress_amount = $affiliatepress_amount-$affiliatepress_shipping;
                }
                
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => 'sale',
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_product_id,
                    'order_id'         => $affiliatepress_order_id,
                    'customer_id'      => $affiliatepress_customer_id,
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
                $affiliatepress_accept_stripe_paymnet_product = array(
                    'product_id'=> $affiliatepress_product_id,
                    'source'=>$this->affiliatepress_integration_slug
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_accept_stripe_paymnet_product );
                if($affiliatepress_product_disable){
                    return;
                }
                $affiliatepress_amount = isset($affiliatepress_order_data['item_price'] ) ? $affiliatepress_order_data['item_price'] : '';

                $affiliatepress_quantity = isset($affiliatepress_order_data['item_quantity']) ? intval($affiliatepress_order_data['item_quantity']) : 0;

                $affiliatepress_amount = $affiliatepress_quantity * $affiliatepress_amount;

                $affiliatepress_tax = isset($affiliatepress_order_data['tax']) ? $affiliatepress_order_data['tax'] : 0;

                $affiliatepress_shipping = isset($affiliatepress_order_data['shipping']) ? $affiliatepress_order_data['shipping'] : 0;
 
                 /* Include Tax */
                if($affiliatepress_exclude_taxes == 'false'){
                    $affiliatepress_amount = $affiliatepress_amount + $affiliatepress_tax;
                }

                /* Include Shipping */
                if($affiliatepress_exclude_shipping == 'false'){
                    $affiliatepress_amount = $affiliatepress_amount + $affiliatepress_shipping;
                }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_product_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_order_id,
                    'quntity'          => $affiliatepress_quantity, 
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

            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_order_id, $affiliatepress_order_data);

            if(!empty($affiliatepress_commission_final_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_final_validation['variant']))?sanitize_text_field($affiliatepress_commission_final_validation['variant']):'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_final_validation['msg']))?sanitize_text_field($affiliatepress_commission_final_validation['msg']):'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Added', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);
                    return;
                }                
            }


            $affiliatepress_commission_products_ids_string = (is_array($affiliatepress_commission_products_ids) && !empty($affiliatepress_commission_products_ids))?implode(',',$affiliatepress_commission_products_ids):'';

            $affiliatepress_commission_products_name_string = (is_array($affiliatepress_commission_products_name) && !empty($affiliatepress_commission_products_name))?implode(',',$affiliatepress_commission_products_name):'';

            $affiliatepress_ip_address = $AffiliatePress->affiliatepress_get_ip_address();

            $affiliatepress_commission_status = 2;
            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();            
            $affiliatepress_status = isset($affiliatepress_charge->status) ? $affiliatepress_charge->status : '';
            if($affiliatepress_default_commission_status == "auto"){
                if('succeeded' === $affiliatepress_status){
                    $affiliatepress_commission_status = 1;
                }
            }

            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => $affiliatepress_commission_status,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_total_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp'))// phpcs:ignore
            );

            /* Insert The Commission */
            $affiliatepress_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
            if($affiliatepress_commission_id == 0){
                
                if($affiliatepress_commission_status == 2)
                {
                    $affiliatepress_msg = 'Pending commission could not be inserted due to an unexpected error.';
                }
                else
                {
                    $affiliatepress_msg = 'commission could not be inserted due to an unexpected error.';
                }
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
            }else{

                $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;

                $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                do_action('affiliatepress_after_commission_created', $affiliatepress_commission_id, $affiliatepress_commission_data);

                if($affiliatepress_commission_status == 1){
                    $affiliatepress_msg = sprintf( 'commission #%s has been successfully inserted.', $affiliatepress_commission_id);
                    do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,1,2);
                }else{
                    $affiliatepress_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_commission_id );
                }
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);            
            }            

        }
        
        /**
         * Function For Accept stripe paymnet Approved commission add
         *
         * @param  integer $affiliatepress_order_id
         * @return void
         */
        function affiliatepress_accept_stripe_payment($affiliatepress_order_id){

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking ,$AffiliatePress;

            $affiliatepress_post_type = (isset($_POST['post_type']))?sanitize_text_field(wp_unslash($_POST['post_type'])):'';//phpcs:ignore
            if($affiliatepress_post_type != "stripe_order"){
                return;
            }
            $affiliatepress_order_status = get_post_meta($affiliatepress_order_id, 'asp_order_status', true);
            if($affiliatepress_order_status != "paid"){
                return;
            }

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();
            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);

            if(!empty($affiliatepress_all_commission_data)){

                foreach($affiliatepress_all_commission_data as $affiliatepress_commissition_data){
                    if(!empty($affiliatepress_commissition_data)){
                        $affiliatepress_commission_status = (isset($affiliatepress_commissition_data['ap_commission_status']))?intval($affiliatepress_commissition_data['ap_commission_status']):0;
                        $affiliatepress_commission_id     = (isset($affiliatepress_commissition_data['ap_commission_id']))?$affiliatepress_commissition_data['ap_commission_id']:'';
                        if($affiliatepress_commission_status == 4){
                            $affiliatepress_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_commission_id );
                            continue;
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

global $affiliatepress_accept_stripe_payments;
$affiliatepress_accept_stripe_payments = new affiliatepress_accept_stripe_payments();