<?php 

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_memberpress') ){

    class affiliatepress_memberpress Extends AffiliatePress_Core{

        private $affiliatepress_integration_slug;

        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){         

            global $affiliatepress_is_memberpress_active ;
            $affiliatepress_is_memberpress_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'memberpress';

            if($this->affiliatepress_memberpress_commission_add() && $affiliatepress_is_memberpress_active){

                /**Insert a new pending commission*/
                add_action( 'mepr-txn-status-pending', array($this,'affiliatepress_insert_pending_commission_memberpress'), 10, 1 );

                /**Add Validation */
                add_filter( 'affiliatepress_commission_validation',array($this,'affiliatepress_memberpress_commission_validation_func'),15,5);

                // Update the status of the commission to "unpaid", thus marking it as complete
                add_action( 'mepr-txn-status-complete',array($this,'affiliatepress_accept_pending_commission_memberpress'),10,1);
                add_action( 'mepr-txn-status-confirmed',array($this,'affiliatepress_accept_pending_commission_memberpress'),10,1);
                
                //status pending change
                add_action( 'mepr-txn-status-pending',array($this,'affiliatepress_change_pending_status_memberpress'),10,1);

                // Add the commission settings in download page
                add_action( 'add_meta_boxes', array($this,'affiliatepress_add_commission_settings_metabox_memberpress'), 10, 1 );

                // Save the affiliate id in the product meta
                add_action( 'save_post_memberpressproduct', array($this,'affiliatepress_save_product_commission_settings_memberpress'), 10, 1 );
        
            }   

            if($affiliatepress_is_memberpress_active)
            {
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_link_order_func_memberpress'),10,3); 

                /* Add Memberpress Backend Product List */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_memberpress_product_func'),10,3); 
            }
        }
        
        /**
         * Function For MemberPress get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_link_order_func_memberpress($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug)
            {
                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href="'.admin_url("admin.php?page=memberpress-trans&action=edit&id=".$affiliatepress_ap_commission_reference_id).'");"> '. $affiliatepress_ap_commission_reference_id .' </a>';

                $affiliatepress_ap_commission_reference_id = $affiliatepress_ap_commission_order_link;

            }

            return $affiliatepress_ap_commission_reference_id;
        }

        /**
         * Function For get product details
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param  string $affiliatepress_commission_source
         * @param  string $affiliatepress_search_product_str
         * @return array
         */
        function affiliatepress_get_memberpress_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str) {
            global $wpdb;
            
            if ($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug) {
                
                $affiliatepress_existing_products_data = array();

                $affiliatepress_args = array(
                    'post_type'   => 'memberpressproduct',  // Your custom post type
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
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_memberpress_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_memberpress = $AffiliatePress->affiliatepress_get_settings('enable_memberpress', 'integrations_settings');

            if($affiliatepress_enable_memberpress != 'true'){
                $affiliatepress_flag = false;
            }
            
            return $affiliatepress_flag;
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

            if( !is_plugin_active( 'memberpress/memberpress.php' ))
            {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }

        /**
         * Adds the commissions settings metabox
         *
         * @param  string $affiliatepress_post_type
         * @return void
         */
        function affiliatepress_add_commission_settings_metabox_memberpress( $affiliatepress_post_type ) {

            // Check that post type is 'memberpressproduct'
            if ( $affiliatepress_post_type != 'memberpressproduct' ) {
                return;
            }

            add_meta_box( 'affiliatepress_metabox_commission_settings', esc_html__( 'AffiliatePress Commission Settings', 'affiliatepress-affiliate-marketing'), array($this, 'affiliatepress_add_product_commission_settings_memberpress'), $affiliatepress_post_type, 'advanced', 'high' );
        }
        
        /**
         * Adds the product commission settings fields in MemberPress add/edit subscription page
         *
         * @return void
         */
        function affiliatepress_add_product_commission_settings_memberpress() {
            global $post;

            // Get the disable commissions value
            $affiliatepress_commission_disable_memberpress = get_post_meta( $post->ID, 'affiliatepress_commission_disable_memberpress', true );
            $affiliatepress_commission_nonce_memberpress = wp_create_nonce('affiliatepress_commission_nonce_memberpress');
            ?>
            
                <div id="affiliatepress_product_settings" class="affiliatepress-options-groups-wrapper" style="margin-top: 20px; margin-bottom:20px;">
                    <div class="affiliatepress-options-group">
                        <div class="affiliatepress-option-field-wrapper">
                            <label for="affiliatepress-disable-commissions"><?php esc_html_e( 'Disable Commissions', 'affiliatepress-affiliate-marketing'); ?></label>
                            <label for="affiliatepress-disable-commissions" style="margin-left: 80px;">
                                <input type="checkbox" class="affiliatepress_commission_disable_memberpress" name="affiliatepress_commission_disable_memberpress" id="affiliatepress_commission_disable_memberpress" value="1"<?php checked( $affiliatepress_commission_disable_memberpress, true ); ?> />
                                <?php esc_html_e( 'Disable commissions for this membership.', 'affiliatepress-affiliate-marketing'); ?>
                            </label>
                            <input name="affiliatepress_commission_nonce_memberpress" id="affiliatepress_commission_nonce_memberpress" type="hidden" value="<?php echo esc_attr( $affiliatepress_commission_nonce_memberpress ) ?> " />
                        </div>
                    </div>
                </div>  

            <?php

            do_action('affiliatepress_memberpress_add_product_settings');
        }

        /**
         * Saves the product commission settings into the product meta
         * 
         * @param int $affiliatepress_post_id
         * @param WP_Post $affiliatepress_post
         * 
         */
        function affiliatepress_save_product_commission_settings_memberpress( $affiliatepress_post_id) {

            $affiliatepress_wpnonce = isset($_POST['affiliatepress_commission_nonce_memberpress']) ? sanitize_text_field(wp_unslash($_POST['affiliatepress_commission_nonce_memberpress'])) : '';// phpcs:ignore

            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_commission_nonce_memberpress');
            if (! $affiliatepress_verify_nonce_flag ) {
                $affiliatepress_nonce_error = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                return $affiliatepress_nonce_error;
            }  

            if ( ! empty( $_POST['affiliatepress_commission_disable_memberpress'] ) ) {
                update_post_meta( $affiliatepress_post_id, 'affiliatepress_commission_disable_memberpress', 1 );
            } else {
                delete_post_meta( $affiliatepress_post_id, 'affiliatepress_commission_disable_memberpress' );
            }
    
            do_action('affiliatepress_memberpress_settings_save' , $affiliatepress_post_id);

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
        function affiliatepress_memberpress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_transaction_id, $affiliatepress_transaction){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){

                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 

                    if($affiliatepress_earn_commissions_own_orders != 'true'){

                        $affiliatepress_user = get_userdata( $affiliatepress_transaction->user_id );

                        $affiliatepress_user_email = $affiliatepress_user->get( 'user_email' );

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
         * Inserts a new pending commission when a new transaction is registered
         *
         * @param  array $affiliatepress_transaction
         * @return void
         */
        function affiliatepress_insert_pending_commission_memberpress( $affiliatepress_transaction ) {

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_transaction_id = $affiliatepress_transaction->id;

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();    
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;

            $affiliatepress_order_data = array('order_id'=>$affiliatepress_transaction_id);
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_order_data );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();
            $affiliatepress_commission_exists = $affiliatepress_tracking->affiliatepress_check_commission_exists( array( 'reference' => $affiliatepress_transaction_id, 'origin' => $this->affiliatepress_integration_slug ));           
            if($affiliatepress_commission_exists){        
                
                $affiliatepress_error_msg = 'Commission already added';

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);   

                return;                         
            }                


            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_transaction_id, $affiliatepress_transaction);

            if(!empty($affiliatepress_commission_validation)){
                
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';

                if($affiliatepress_filter_variant == 'error'){

                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';

                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);          

                    return;

                }     
            }

            $affiliatepress_user = get_userdata( $affiliatepress_transaction->user_id );
            $affiliatepress_user_email = $affiliatepress_user->get( 'user_email' );

            $affiliatepress_customer_args = array(
                'email'   	   => !empty($affiliatepress_user_email) ? sanitize_email($affiliatepress_user_email) : '',
                'user_id' 	   => isset($affiliatepress_transaction) ? intval($affiliatepress_transaction->user_id) : 0,
                'first_name'   => isset($affiliatepress_user) ? sanitize_text_field($affiliatepress_user->display_name) : '',
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

            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('memberpress_exclude_taxes', 'integrations_settings');       

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();
            $affiliatepress_commission_type = "subscription";

            $affiliatepress_memberpres_options = MeprOptions::fetch();

            $affiliatepress_product_id = !empty($affiliatepress_transaction->product_id) ? intval($affiliatepress_transaction->product_id) : 0;
            $affiliatepress_product_name = get_the_title($affiliatepress_product_id);

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_total_amount = isset($affiliatepress_transaction->total)  ? floatval($affiliatepress_transaction->total ): 0;

                if ( $affiliatepress_transaction->subscription() && $affiliatepress_transaction->subscription()->trial ) {

                    $affiliatepress_total_amount =  isset($affiliatepress_transaction->subscription()->trial_total) ? floatval($affiliatepress_transaction->subscription()->trial_total) : 0;
                }

                $affiliatepress_tax_amount = isset($affiliatepress_transaction->tax_amount) ? intval($affiliatepress_transaction->tax_amount) : 0;

                if($affiliatepress_exclude_taxes == 'true'){

                    $affiliatepress_amount = $affiliatepress_total_amount - $affiliatepress_tax_amount;
                }

                $affiliatepress_amount = $affiliatepress_total_amount;

                

                $affiliatepress_currency = !empty($affiliatepress_memberpres_options->currency_code) ? sanitize_text_field($affiliatepress_memberpres_options->currency_code) : '';

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => $affiliatepress_commission_type,
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => 0,
                    'order_id'     => $affiliatepress_transaction_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );

                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_product_name,
                    'order_id'             => $affiliatepress_transaction_id,
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
                $affiliatepress_memberpress_product = array(
                    'product_id'=> $affiliatepress_transaction->product_id,
                    'source'=>$this->affiliatepress_integration_slug
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_memberpress_product );
    
                if($affiliatepress_product_disable){
    
                    return;
                }

                $affiliatepress_amount = $affiliatepress_transaction->amount;

                if ( $affiliatepress_exclude_taxes == 'false' ) {
                    $affiliatepress_amount = $affiliatepress_transaction->total;
                }
    
                if ( $affiliatepress_transaction->subscription() && $affiliatepress_transaction->subscription()->trial ) {
    
                    $affiliatepress_amount = $affiliatepress_transaction->subscription()->trial_amount;
    
                    if ( $affiliatepress_exclude_taxes == 'false' ) {
                        $affiliatepress_amount = $affiliatepress_transaction->subscription()->trial_total;
                    }
            
                }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_product_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_transaction_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_memberpres_options->currency_code, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_product_id,
                    'product_name'         => $affiliatepress_product_name,
                    'order_id'             => $affiliatepress_transaction_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $affiliatepress_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );

                $affiliatepress_commission_products_ids[] = !empty($affiliatepress_product_id) ? intval($affiliatepress_product_id) : 0;
                $affiliatepress_commission_products_name[] = html_entity_decode($affiliatepress_product_name);
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();

            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_transaction_id, $affiliatepress_transaction);

            if(!empty($affiliatepress_commission_final_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_final_validation['variant']))?$affiliatepress_commission_final_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_final_validation['msg']))?$affiliatepress_commission_final_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Added', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);
                    return;
                }                
            }

            $affiliatepress_ip_address = $AffiliatePress->affiliatepress_get_ip_address();

            $affiliatepress_commission_products_ids_string = (is_array($affiliatepress_commission_products_ids) && !empty($affiliatepress_commission_products_ids))?implode(',',$affiliatepress_commission_products_ids):'';

            $affiliatepress_commission_products_name_string = (is_array($affiliatepress_commission_products_name) && !empty($affiliatepress_commission_products_name))?implode(',',$affiliatepress_commission_products_name):'';

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_reference_id'     => $affiliatepress_transaction_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_transaction->total,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')), // phpcs:ignore
                'ap_commission_status'           => 2 
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
         * Updates the status of the commission attached to a transaction to "unpaid", thus marking it as complete.
         *
         * @param  array $affiliatepress_transaction
         * @return void
         */
        function affiliatepress_accept_pending_commission_memberpress( $affiliatepress_transaction ) {
                    
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking ,$AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_easy_digital_downloads_commission_tracking', $affiliatepress_transaction->id, $affiliatepress_commission_debug_log_id);

            $affiliatepress_transaction_id = $affiliatepress_transaction->id;

            $affiliatepress_transection_status = $affiliatepress_transaction->status;

            if($affiliatepress_transection_status != "complete"){
                return ;
            }

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_transaction_id, $this->affiliatepress_integration_slug,' AND ap_commission_status = 2');

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
            
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_easy_digital_downloads_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                        }
        
                    }
                }
            }
           
        }
        
        /**
         * Function For add pending commisisom
         *
         * @param  array $affiliatepress_transaction
         * @return void
         */
        function affiliatepress_change_pending_status_memberpress( $affiliatepress_transaction)
        {
            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;

            if ( empty( $affiliatepress_transaction->id ) ) {
                return;
            }

            $affiliatepress_transaction_id = $affiliatepress_transaction->id;

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_transaction_id, $this->affiliatepress_integration_slug);

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
        
                            $affiliatepress_update_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => 2
                            );
        
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_update_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
        
                            $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as pending, after payment #%s status change pending.', $affiliatepress_ap_commission_id, $affiliatepress_transaction_id );
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Pending', 'affiliatepress_easy_digital_downloads_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                        
                        }
        
                    }
                    
                }
            }
        }

    }

}

global $affiliatepress_memberpress;
$affiliatepress_memberpress = new affiliatepress_memberpress();