<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_paid_memberships_subscriptions') ){
    
    class affiliatepress_paid_memberships_subscriptions Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){            
            
            //add_action( 'wp', array( &$this, 'affiliatepress_set_ref_in_cookie' ), 10 );

            global $affiliatepress_is_paid_memberships_subscriptions_active ;
            $affiliatepress_is_paid_memberships_subscriptions_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'paid_memberships_subscriptions';
            if($this->affiliatepress_paid_memberships_subscriptions_commission_add() && $affiliatepress_is_paid_memberships_subscriptions_active){

                /**Add Pending Commission */
                add_action( 'pms_register_payment', array($this , 'affiliatepress_insert_pending_commission_pms'), 10, 1 );

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_pms_validation_func'),15,5);

                // Update the status of the commission to "unpaid", thus marking it as complete
                add_action( 'pms_payment_update', array($this,'affiliatepress_accept_pending_commission_pms'), 10, 3 );

                // Update the status of the commission to "rejected" when the originating payment is failed
		// Update the status of the commission to "pending" when the originating paymnet status change
                add_action( 'pms_payment_update', array($this,'affiliatepress_pending_commission_change_status_pms'), 10, 3 );

               // Hook the function to the 'add_meta_boxes' action
                add_action( 'add_meta_boxes', array($this , 'affiliatepress_add_commission_settings_metabox_pms'), 10, 2 );

                // Saves the commissions settings in download meta
                add_action( 'pms_save_meta_box_pms-subscription', array($this , 'affiliatepress_save_product_commission_settings_pms'), 10, 2 );
            }

            if($affiliatepress_is_paid_memberships_subscriptions_active){
                /**Get Order Link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_pms_link_order_func'),10,3); 

                /**Get All Product */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_pms_product_func'),10,3); 
            }
        }

        /**
         * Function For Paid Membership Subscriptions get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_pms_link_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_url = admin_url("admin.php?page=pms-payments-page&pms-action=edit_payment&payment_id=".$affiliatepress_ap_commission_reference_id);

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
        function affiliatepress_get_pms_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str) {
            global $wpdb;
            
            if ($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug) {
                
                $affiliatepress_existing_products_data = array();

                $affiliatepress_args = array(
                    'post_type'   => 'pms-subscription',  // Your custom post type
                    'post_status' => 'active',   // Only published posts
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
                            'label' => html_entity_decode( $affiliatepress_post_name )
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

            if ( !is_plugin_active( 'paid-member-subscriptions/index.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_paid_memberships_subscriptions_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;            
            $affiliatepress_enable_paid_memberships_subscriptions = $AffiliatePress->affiliatepress_get_settings('enable_paid_memberships_subscriptions', 'integrations_settings');
            if($affiliatepress_enable_paid_memberships_subscriptions != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }

                
        /**
         * Fnction for add settings meta box in pms
         *
         * @param  string $affiliatepress_post_type
         * @param  array $affiliatepress_post
         * @return void
         */
        function affiliatepress_add_commission_settings_metabox_pms( $affiliatepress_post_type, $affiliatepress_post ) {
            if ( $affiliatepress_post_type != 'pms-subscription' ) {
                return;
            }

            add_meta_box( 
                'affiliatepress_metabox_commission_settings_pms', 
                esc_html__( 'AffiliatePress Commission Settings', 'affiliatepress-affiliate-marketing'), 
                array( $this, 'affiliatepress_add_product_commission_settings_pms' ), 
                $affiliatepress_post_type, 
                'normal', 
                'default' 
            );
        }
        
        /**
         * Function For Product Commission settings add
         *
         * @return void
         */
       function affiliatepress_add_product_commission_settings_pms() {
            global $post;

            $affiliatepress_disable_commissions = get_post_meta( $post->ID, 'affiliatepress_pms_disable_commissions', true );
            wp_nonce_field( 'affiliatepress_commission_nonce_paid_membership_subscriptions', 'affiliatepress_commission_nonce_paid_membership_subscriptions' );
            ?>
            <div id="affiliatepress_product_settings" class="affiliatepress-options-groups-wrapper">
                <div class="affiliatepress-options-group">
                    <p class="affiliatepress-option-field-wrapper pms-meta-box-field-wrapper">
                        <label for="affiliatepress-disable-commissions" class="pms-meta-box-field-label"><?php esc_html_e( 'Disable Commissions', 'affiliatepress-affiliate-marketing'); ?></label>
                        <label for="affiliatepress-disable-commissions">
                            <input type="checkbox" class="affiliatepress-option-field-disable-commissions" name="affiliatepress_pms_disable_commissions" id="affiliatepress-disable-commissions" value="1" <?php checked( $affiliatepress_disable_commissions, true );  ?> style="margin-left: 80px;"/>
                            <?php esc_html_e( 'Disable commissions for this subscription plan.', 'affiliatepress-affiliate-marketing'); ?>
                        </label>
                    </p>
                </div>
            </div>
            <?php
            do_action('affiliatepress_paid_memberships_subscriptions_add_product_settings');
        }

        /**
         * Saves the product commission settings into the product meta
         *
         * @return void
         * 
         */
        function affiliatepress_save_product_commission_settings_pms( $affiliatepress_post_id, $affiliatepress_post ) {

            $affiliatepress_wpnonce = isset($_POST['affiliatepress_commission_nonce_paid_membership_subscriptions']) ? sanitize_text_field(wp_unslash($_POST['affiliatepress_commission_nonce_paid_membership_subscriptions'])) : '';// phpcs:ignore

            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_commission_nonce_paid_membership_subscriptions');
            if (! $affiliatepress_verify_nonce_flag ) {
                $affiliatepress_nonce_error = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                return $affiliatepress_nonce_error;
            } 
          
            if ( ! empty( $_POST['affiliatepress_pms_disable_commissions'] ) ) {
                update_post_meta( $affiliatepress_post_id, 'affiliatepress_pms_disable_commissions', 1 );
            } else {
                delete_post_meta( $affiliatepress_post_id, 'affiliatepress_pms_disable_commissions' );
            }
    
            do_action('affiliatepress_paid_memberships_subscriptions_settings_save' , $affiliatepress_post_id);
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
        function affiliatepress_commission_pms_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_payment_id, $affiliatepress_payment_data){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = !empty($affiliatepress_payment_data['user_data']['user_email']) ? $affiliatepress_payment_data['user_data']['user_email'] : '';               
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
         * Function For pending commission add
         *
         * @param  array $affiliatepress_payment_data
         * @return void
         */
        function affiliatepress_insert_pending_commission_pms( $affiliatepress_payment_data ) {

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_plan_id = !empty($affiliatepress_payment_data['subscription_plan_id']) ? $affiliatepress_payment_data['subscription_plan_id'] : 0;

            $affiliatepress_payment_type = !empty($affiliatepress_payment_data['type']) ? sanitize_text_field($affiliatepress_payment_data['type']) :'';

            if($affiliatepress_payment_type != "subscription_initial_payment"){

                $affiliatepress_log_msg = "This Payment is recurring paymnet, so no Commission could be created.";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' This Recurring payment', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);

                return;
            }

            $affiliatepress_payment_id = !empty($affiliatepress_payment_data['payment_id']) ? $affiliatepress_payment_data['payment_id'] : 0;

            /* Get and check to see if referrer exists */
            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();       

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;
         
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_payment_id) );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_payment_id, $affiliatepress_payment_data);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_customer_args = array(
                'email'   	   => !empty($affiliatepress_payment_data['user_data']['user_email']) ? sanitize_email($affiliatepress_payment_data['user_data']['user_email']) : '',
                'user_id' 	   => !empty($affiliatepress_payment_data['user_data']['user_id']) ? intval($affiliatepress_payment_data['user_data']['user_id']) : 0,
                'first_name'   => !empty($affiliatepress_payment_data['user_data']['first_name']) ? sanitize_text_field($affiliatepress_payment_data['user_data']['first_name']) : '',
                'last_name'	   => !empty($affiliatepress_payment_data['user_data']['last_name']) ? sanitize_text_field($affiliatepress_payment_data['user_data']['last_name']) : '',
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

            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('paid_memberships_subscriptions_exclude_taxes', 'integrations_settings');  

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_commission_type = 'subscription';
            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_plan_name = !empty($affiliatepress_payment_data['user_data']['subscription']->name) ? sanitize_text_field($affiliatepress_payment_data['user_data']['subscription']->name) : '';

            $affiliatepress_currency = !empty($affiliatepress_payment_data['currency']) ? sanitize_text_field($affiliatepress_payment_data['currency']) : '';

            $affiliatepress_total_amount = !empty($affiliatepress_payment_data['amount']) ? floatval($affiliatepress_payment_data['amount']) : 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
                
                $affiliatepress_total_price = $affiliatepress_total_amount;

                $affiliatepress_tax_with_total = 0;
                if( defined( 'PMS_TAX_VERSION' ) ){
                    $affiliatepress_tax 	= pms_tax_determine_tax_breakdown( $affiliatepress_payment_data['payment_id'] );	
                    $affiliatepress_tax_with_total = isset($affiliatepress_tax['subtotal']) ? floatval($affiliatepress_tax['subtotal']) : 0;
                } 

                $affiliatepress_tax_amount = $affiliatepress_tax_with_total - $affiliatepress_total_price;

                $affiliatepress_total_price = $affiliatepress_total_price + $affiliatepress_tax_amount;

                $affiliatepress_amount = $affiliatepress_total_price;

                if($affiliatepress_exclude_taxes == "true")
                {
                    $affiliatepress_amount = $affiliatepress_amount - $affiliatepress_tax_amount;
                }
                
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_plan_id,
                    'order_id'         => $affiliatepress_payment_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );

                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_plan_id,
                    'product_name'         => $affiliatepress_plan_name,
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
                $affiliatepress_pms_product = array(
                    'product_id'=> $affiliatepress_plan_id,
                    'source'=>$this->affiliatepress_integration_slug
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_pms_product );
    
                if($affiliatepress_product_disable){
    
                    return;
                }

                $affiliatepress_amount = $affiliatepress_total_amount;

                if( defined( 'PMS_TAX_VERSION' ) && $affiliatepress_exclude_taxes == 'false'){
                    $affiliatepress_tax 	= pms_tax_determine_tax_breakdown( $affiliatepress_payment_data['payment_id'] );	
                    $affiliatepress_amount = empty( $affiliatepress_tax ) ? $affiliatepress_amount : floatval($affiliatepress_tax['subtotal']);
                }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_plan_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_payment_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_plan_id,
                    'product_name'         => $affiliatepress_plan_name,
                    'order_id'             => $affiliatepress_payment_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $affiliatepress_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );

                $affiliatepress_commission_products_ids[] = $affiliatepress_plan_id;
                $affiliatepress_commission_products_name[] = $affiliatepress_plan_name;
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
                'ap_commission_order_amount'     => $affiliatepress_total_amount,
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
                do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data);
                $affiliatepress_debug_log_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
            }
        }
        
        /**
         * Function For Approved Commission add
         *
         * @param  int $affiliatepress_payment_id
         * @param  string $affiliatepress_new_data
         * @param  string $affiliatepress_old_data
         * @return void
         */
        function affiliatepress_accept_pending_commission_pms( $affiliatepress_payment_id, $affiliatepress_new_data, $affiliatepress_old_data ) {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;


            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : complete paymnet data', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', "payment id------".$affiliatepress_payment_id."new dataaa===".serialize($affiliatepress_new_data)."old data ============".serialize($affiliatepress_old_data), $affiliatepress_commission_debug_log_id);

            if ( ! isset( $affiliatepress_new_data['status'] ) || $affiliatepress_new_data['status'] != 'completed' ){
                return;
            }

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_payment_id = !empty($affiliatepress_payment_id) ? $affiliatepress_payment_id : 0;

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_payment_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');

            if(!empty($affiliatepress_all_commission_data)){

                foreach($affiliatepress_all_commission_data as $affiliatepress_single_commission_data){

                    $affiliatepress_commission_id = (isset($affiliatepress_single_commission_data['ap_commission_id']))?intval($affiliatepress_single_commission_data['ap_commission_id']):0;
                    $affiliatepress_commission_status = (isset($affiliatepress_single_commission_data['ap_commission_status']))?intval($affiliatepress_single_commission_data['ap_commission_status']):0;


                    if($affiliatepress_commission_status == 4){
                        $affiliatepress_debug_log_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_commission_id );
        
                        do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Already paid ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
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
                            $affiliatepress_debug_log_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
        
                            do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_updated_commission_status,2);
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                        }
        
                    }

                }

            }


        }
        
        /**
         * Function for add pending commission after status chnage
         *
         * @param  int $affiliatepress_payment_id
         * @param  string $affiliatepress_new_data
         * @param  string $affiliatepress_old_data
         * @return void
         */
        function affiliatepress_pending_commission_change_status_pms($affiliatepress_payment_id, $affiliatepress_new_data, $affiliatepress_old_data)
        {
            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;

            $affiliatepress_payment_id = !empty($affiliatepress_payment_id) ? $affiliatepress_payment_id : 0;

            if ( isset($affiliatepress_new_data['status']) && $affiliatepress_new_data['status'] != 'pending' ){
               return;
            }

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_payment_id, $this->affiliatepress_integration_slug);

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
                            $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as pending, after order #%s status change pending.', $affiliatepress_ap_commission_id, $affiliatepress_payment_id );
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Reject ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                        }
                    }
                }
            }
        }

    }
}
global $affiliatepress_paid_memberships_subscriptions;
$affiliatepress_paid_memberships_subscriptions = new affiliatepress_paid_memberships_subscriptions();