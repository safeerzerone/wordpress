<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_restrict_content') ){
    
    class affiliatepress_restrict_content Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){            
            
            //add_action( 'wp', array( &$this, 'affiliatepress_set_ref_in_cookie' ), 10 );

            global $affiliatepress_is_restrict_content_active ;
            $affiliatepress_is_restrict_content_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'restrict_content';
            if($this->affiliatepress_restrict_content_commission_add() && $affiliatepress_is_restrict_content_active){

                /* Insert a new pending commission */
                add_action( 'rcp_form_processing', array($this,'affiliatepress_add_pending_referral_restrict_content'), 10, 8 );

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func_rcp'),15,5);

                // Update the status of the commission to "unpaid", thus marking it as complete
                add_action( 'rcp_update_payment_status_complete', array($this,'affiliatepress_accept_pending_commission_rcp'),10,1);

                // Add the commission settings in add/edit membership level pages
                add_action( 'rcp_add_subscription_form', array($this,'affiliatepress_add_product_commission_settings_membership_rcp'),20);
                add_action( 'rcp_edit_subscription_form', array($this,'affiliatepress_add_product_commission_settings_membership_rcp'),20);

                // Save the commission settings for membership levels
                add_action( 'rcp_add_subscription', array($this,'affiliatepress_save_product_commission_settings_rcp'), 10, 2 );
                add_action( 'rcp_edit_subscription_level', array($this,'affiliatepress_save_product_commission_settings_rcp'), 10, 2 );

                add_action( 'rcp_update_payment_status_pending', array($this , 'affiliatepress_pending_status_on_change_status_rcp'), 10, 1 );

            }

            if($affiliatepress_is_restrict_content_active)
            {
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_link_rcp_order_func'),10,3); 

                /* Add restrict content Backend Product List */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_rcp_product_func'),10,3); 
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
        function affiliatepress_get_rcp_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){

            global $wpdb;
        
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){
        
                $affiliatepress_existing_products_data = array();
                $affiliatepress_tbl_restrict_content_pro = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'restrict_content_pro' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'restrict_content_pro' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                
                $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$affiliatepress_tbl_restrict_content_pro} WHERE name LIKE %s",'%' . $wpdb->esc_like($affiliatepress_search_product_str) . '%'),ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_restrict_content_pro is a table name. false alarm 
    
                $affiliatepress_plan_ids = array_column($affiliatepress_results, 'id');

                if($affiliatepress_plan_ids){
                    foreach ($affiliatepress_plan_ids as $affiliatepress_plan_id) {
                        
                        $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT name FROM {$affiliatepress_tbl_restrict_content_pro} WHERE id = %d",$affiliatepress_plan_id),ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_restrict_content_pro is a table name. false alarm 

                        $affiliatepress_plan_name = !empty($affiliatepress_results) ? $affiliatepress_results[0]['name'] : '';

                        if(!empty($affiliatepress_plan_name))
                        {
                            $affiliatepress_existing_product_data[] = array(
                                'value' => $affiliatepress_plan_id,
                                'label' => $affiliatepress_plan_name
                            );
                        }
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
         * Function For Restrict content pro get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_link_rcp_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug)
            {
                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href="'.admin_url("admin.php?page=rcp-payments&payment_id=".$affiliatepress_ap_commission_reference_id."&view=edit-payment").'"> '. $affiliatepress_ap_commission_reference_id .' </a>';

                $affiliatepress_ap_commission_reference_id = $affiliatepress_ap_commission_order_link;

            }

            return $affiliatepress_ap_commission_reference_id;
        }

              
        /**
         * Function For add affiliate settings
         *
         * @return void
         */
        function affiliatepress_add_product_commission_settings_membership_rcp() {

            $affiliatepress_level_id 			 = ( ! empty( $_GET['edit_subscription'] ) ? absint( $_GET['edit_subscription'] ) : 0 );//phpcs:ignore
            $affiliatepress_disable_commissions = rcp_get_membership_level_meta( $affiliatepress_level_id, 'affiliatepress_commission_disable_restrict_content', true );
            wp_nonce_field( 'affiliatepress_commission_nonce_restrict_content', 'affiliatepress_commission_nonce_restrict_content' );
            ?>
        
            <tr id="affiliatepress_product_settings" class="affiliatepress-options-groups-wrapper" >
                <td colspan="2" style="padding: 0px;">
                    <h2><?php esc_html_e( 'AffiliatePress Commission Settings', 'affiliatepress-affiliate-marketing') ?></h2>
                    <table class="affiliatepress-options-group form-table">
                        <tbody>
                            <tr class="affiliatepress-option-field-wrapper form-field">
                                <th scope="row" valign="top">
                                    <label for="affiliatepress-disable-commissions"><?php esc_html_e( 'Disable commissions', 'affiliatepress-affiliate-marketing'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" class="affiliatepress-option-field-disable-commissions" name="affiliatepress_commission_disable_restrict_content" id="affiliatepress-disable-commissions" value="1" <?php checked( $affiliatepress_disable_commissions, true ); ?> /><?php esc_html_e( 'Disable commissions for this membership level.', 'affiliatepress-affiliate-marketing'); ?>
                                </td>
                            </tr>

                            <?php  do_action('affiliatepress_restrict_content_add_product_settings'); ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        
            <?php
        }

        /**
         * Function For save affiliate settings
         * 
         * @param int	$affiliatepress_level_id
         * @param array $affiliatepress_args
         * 
         */
        function affiliatepress_save_product_commission_settings_rcp( $affiliatepress_level_id, $affiliatepress_args ) {

            $affiliatepress_wpnonce = isset($_POST['affiliatepress_commission_nonce_restrict_content']) ? sanitize_text_field(wp_unslash($_POST['affiliatepress_commission_nonce_restrict_content'])) : '';// phpcs:ignore

            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_commission_nonce_restrict_content');
            if (! $affiliatepress_verify_nonce_flag ) {
                $affiliatepress_nonce_error = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                return $affiliatepress_nonce_error;
            }  
           
            if ( ! empty( $_POST['affiliatepress_commission_disable_restrict_content'] ) ) {
                rcp_update_membership_level_meta( $affiliatepress_level_id, 'affiliatepress_commission_disable_restrict_content', 1 );
            } else {
                rcp_delete_membership_level_meta( $affiliatepress_level_id, 'affiliatepress_commission_disable_restrict_content' );
            }
            do_action('affiliatepress_restrict_content_settings_save' ,$affiliatepress_level_id);
 
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
        function affiliatepress_commission_validation_func_rcp($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_payment_id, $affiliatepress_user_data){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_user_email = !empty($affiliatepress_user_data['rcp_user_email']) ? $affiliatepress_user_data['rcp_user_email'] : '';      
                        
                        if(empty($affiliatepress_user_email)){
                            $affiliatepress_current_user = wp_get_current_user();
                            $affiliatepress_user_email = $affiliatepress_current_user->user_email;
                        }
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
         * Function for add pending commission settings
         *
         * @param  array $affiliatepress_post_data
         * @param  int $affiliatepress_user_id
         * @param  float $affiliatepress_price
         * @param  int $affiliatepress_payment_id
         * @param  array $affiliatepress_customer
         * @param  int $affiliatepress_membership_id
         * @param  array $affiliatepress_previous_membership
         * @param  string $affiliatepress_registration_type
         * @return void
         */
        function affiliatepress_add_pending_referral_restrict_content( $affiliatepress_post_data, $affiliatepress_user_id, $affiliatepress_price, $affiliatepress_payment_id, $affiliatepress_customer, $affiliatepress_membership_id, $affiliatepress_previous_membership, $affiliatepress_registration_type ) {

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id,$rcp_payments_db;

            // Check if the transaction is for a new subscription
            if ( $affiliatepress_registration_type != 'new' ) {
                return;
            }

            $affiliatepress_payment_id = !empty($affiliatepress_payment_id) ?  $affiliatepress_payment_id : '';

            // Get the payment
	        $affiliatepress_payment = $rcp_payments_db->get_payment( $affiliatepress_payment_id );

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

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_payment_id, $affiliatepress_post_data);

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
                'email'   	   => !empty($affiliatepress_post_data['rcp_user_email']) ? sanitize_email($affiliatepress_post_data['rcp_user_email']) : '',
                'user_id' 	   => !empty($affiliatepress_user_id) ?intval( $affiliatepress_user_id) : 0,
                'first_name'   => !empty($affiliatepress_post_data['rcp_user_first']) ? sanitize_text_field($affiliatepress_post_data['rcp_user_first']) : '',
                'last_name'	   => !empty($affiliatepress_post_data['rcp_user_last']) ? sanitize_text_field($affiliatepress_post_data['rcp_user_last']) : '', 
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

            $affiliatepress_commission_type = 'subscription';
            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_args = array(
                'origin'	           => $this->affiliatepress_integration_slug,
                'type' 		           => $affiliatepress_commission_type,
                'affiliate_id'         => $affiliatepress_affiliate_id,
                'product_id'           => 0,
                'customer_id'          => $affiliatepress_customer_id,
                'order_id'             => $affiliatepress_payment_id,
            );
            
            $affiliatepress_currency = rcp_get_currency();

            $affiliatepress_currency = !empty($affiliatepress_currency) ? sanitize_text_field($affiliatepress_currency) : '';

            $affiliatepress_plan_id = !empty($affiliatepress_payment->object_id) ? intval($affiliatepress_payment->object_id) : 0;
            $affiliatepress_plan_name = !empty($affiliatepress_payment->subscription) ? sanitize_text_field($affiliatepress_payment->subscription) : '';

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_total_amount = !empty($affiliatepress_payment->subtotal) ?  floatval($affiliatepress_payment->subtotal) : 0;

                $affiliatepress_amount =$affiliatepress_total_amount;

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => 'subscription',
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => 0,
                    'order_id'     => $affiliatepress_payment_id,
                    'customer_id'  => $affiliatepress_customer_id,
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

                $affiliatepress_restrict_content_product = array(
                    'product_id'=> $affiliatepress_payment->object_id,
                    'source'=>$this->affiliatepress_integration_slug
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_restrict_content_product );
                if($affiliatepress_product_disable){
                    return;
                }

                /* Calculate Commission Amount */
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_plan_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_payment_id,
                );

                $affiliatepress_amount = !empty($affiliatepress_payment->amount) ? floatval($affiliatepress_payment->amount) : 0;

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

            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_payment_id, $affiliatepress_payment);

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
                'ap_commission_order_amount'     => $affiliatepress_payment->subtotal,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')) // phpcs:ignore
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
         * Function for add Approved commission settings
         *
         * @param  int $affiliatepress_payment_id
         * @return void
         */
        function affiliatepress_accept_pending_commission_rcp( $affiliatepress_payment_id ) {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id,$affiliatepress_tracking ,$AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();
            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_payment_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2  or ap_commission_status = 3)');

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

        /**
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_restrict_content_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_restrict_content = $AffiliatePress->affiliatepress_get_settings('enable_restrict_content', 'integrations_settings');
            if($affiliatepress_enable_restrict_content != 'true'){
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

            if ( is_plugin_active( 'restrict-content/restrictcontent.php' ) || is_plugin_active( 'restrict-content-pro/restrict-content-pro.php' ) ) {
                $affiliatepress_flag = true;
            }
            else
            {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }   
        
        /**
         * Function for add pending commission settings after status chnage
         *
         * @param  int $affiliatepress_payment_id
         * @return void
         */
        function affiliatepress_pending_status_on_change_status_rcp( $affiliatepress_payment_id)
        {
            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;
           
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
}
global $affiliatepress_restrict_content;
$affiliatepress_restrict_content = new affiliatepress_restrict_content();