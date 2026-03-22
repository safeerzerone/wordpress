<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_download_manager') ){
    
    class affiliatepress_download_manager Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){  

            global $affiliatepress_is_download_manager_active ;
            $affiliatepress_is_download_manager_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'download_manager';
            if($this->affiliatepress_download_manager_commission_add() && $affiliatepress_is_download_manager_active){

                /**Add Disable Settings */
                add_filter( 'wpdm_package_settings_tabs', array($this , 'affiliatepress_downlod_manager_add_disable_option_tab') ,20);

                /** Add pending Commision */
                add_action('wpdm_before_placing_order' ,array($this,'affiliatepress_add_pending_commission_downlods_manager'));
                
                /**Add approved Commission */
                add_action('wpdmpp_payment_completed', array($this, 'affiliatepress_add_completed_commission_downloads_manager'), 10);
                add_action('wpdmpp_payment_completed', array($this, 'affiliatepress_accept_pending_commission_downloads_manager'), 20);

                /**Add Approved Commission on status change */
                add_action('wpdmpp_admin_payment_status_updated',array($this , 'affiliatepress_accept_pending_commission_after_status_change_downloads_manager'),10,2);

                /**Add pending Commission on status change */
                add_action('wpdmpp_admin_payment_status_updated',array($this , 'affiliatepress_add_pending_commission_after_status_change_downlods_manager'),10,2);

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func_downloads_manager'),15,5);
            }

            if($affiliatepress_is_download_manager_active)
            {
                /**Get Order link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_downloads_manager_link_order_func'),10,3); 
            }
              
        }
        
        /**
         * Function For Download manager get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_downloads_manager_link_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_url = admin_url( 'edit.php?post_type=wpdmpro&page=orders&task=vieworder&id=' . $affiliatepress_ap_commission_reference_id );

                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href=" '.esc_url( $affiliatepress_url ). ' "> '. $affiliatepress_ap_commission_reference_id .' </a>';

                $affiliatepress_ap_commission_reference_id = $affiliatepress_ap_commission_order_link;
            }
            
            return $affiliatepress_ap_commission_reference_id;
        }
        
        /**
         * Function For Affiliate settings tab add in Download manager
         *
         * @param  array $affiliatepress_tabs
         * @return array
         */
        function affiliatepress_downlod_manager_add_disable_option_tab($affiliatepress_tabs){
            if ( is_admin() ) {
				$affiliatepress_tabs['affiliatepress'] = array(
					'name'     => __( 'Affiliatepress', 'affiliatepress-affiliate-marketing' ),
					'callback' => array( $this, 'affiliatepress_downlod_manager_add_disable_option_settings' )
				);
			}

			return $affiliatepress_tabs;
        }
        
        /**
         * Function For Disable Option Settings add
         *
         * @return void
         */
        function affiliatepress_downlod_manager_add_disable_option_settings(){
            global $post;
            ?>
                <div class="w3eden" id="wpdm-pp-settings">
                    <div class="row">
                        <div class="col-md-12 wpdm-full-front">
                            <div class="card panel panel-default p-0 mb-3">
                                <div class="card-header panel-heading"><?php esc_html_e('Affiliatepress Settings','affiliatepress-affiliate-marketing'); ?></div>
                                <div class="card-body panel-body">
                                    <input type="hidden" name="file[affiliatepress_downloads_manager_disable_commission]" value="0">
                                    <label><input type="checkbox" name="file[affiliatepress_downloads_manager_disable_commission]" <?php checked(1, get_post_meta($post->ID, '__wpdm_affiliatepress_downloads_manager_disable_commission', true)); ?> value="1"> <?php esc_html_e('Disable commissions ( Disable commissions For This Downloads)','affiliatepress-affiliate-marketing'); ?> </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="clear: both;"></div>

                <?php do_action('affiliatepress_downloads_manager_add_product_settings'); ?>
            <?php
        }

        
        /**
         * Function For Validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  int $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_order_data
         * @return array
         */
        function affiliatepress_commission_validation_func_downloads_manager($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order_data){
            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){

                        $affiliatepress_billing_data = $affiliatepress_order_data->billing_info;

                        $affiliatepress_billing_data = maybe_unserialize($affiliatepress_billing_data);

                        $affiliatepress_user_email = !empty($affiliatepress_billing_data['order_email']) ? sanitize_email($affiliatepress_billing_data['order_email']) : '';                
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
         * Function For pending Commission add
         *
         * @param  int $affiliatepress_order_id
         * @return void
         */
        function affiliatepress_add_pending_commission_downlods_manager($affiliatepress_order_id){

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_order = new \WPDMPP\Libs\Order(); // Use full namespace

            $affiliatepress_order_data = $affiliatepress_order->getOrder($affiliatepress_order_id);

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

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_order_data);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_user_data = $affiliatepress_order_data->billing_info;

            $affiliatepress_user_data = maybe_unserialize($affiliatepress_user_data);

            /* Add Commission Customer Here */
            $affiliatepress_customer_args = array(
                'email'   	   => !empty($affiliatepress_user_data['order_email']) ? sanitize_email($affiliatepress_user_data['order_email']) : '',
                'user_id' 	   => !empty($affiliatepress_order_data->uid) ?intval( $affiliatepress_order_data->uid) : 0,
                'first_name'   => !empty($affiliatepress_user_data['first_name']) ? sanitize_text_field($affiliatepress_user_data['first_name']) : '',
                'last_name'	   => !empty($affiliatepress_user_data['last_name']) ? sanitize_text_field($affiliatepress_user_data['last_name']) : '', 
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
            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('download_manager_exclude_taxes', 'integrations_settings');  
            $affiliatepress_currency_data = $affiliatepress_order_data->currency;

            $affiliatepress_currency_data = maybe_unserialize($affiliatepress_currency_data);
            $affiliatepress_currency = !empty($affiliatepress_currency_data['code']) ? sanitize_text_field($affiliatepress_currency_data['code']) : ''; 
            $affiliatepress_cart_data = $affiliatepress_order_data->cart_data;
            $affiliatepress_cart_data = maybe_unserialize($affiliatepress_cart_data);
            $affiliatepress_total_amount = !empty($affiliatepress_order_data->total) ?floatval($affiliatepress_order_data->total) : 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_order_referal_amount = !empty($affiliatepress_order_data->total) ?floatval($affiliatepress_order_data->total) : 0;

                // if($affiliatepress_exclude_taxes == 'true'){
                //     $affiliatepress_order_total_tax = !empty($affiliatepress_order_data->tax) ? floatval($affiliatepress_order_data->tax) : 0;
                //     $affiliatepress_order_referal_amount = $affiliatepress_order_referal_amount - $affiliatepress_order_total_tax;
                // }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => 'sale',
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => 0,
                    'order_id'         => $affiliatepress_order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                    'quntity'          => 1,
                );

                $affiliatepress_commission_rules  = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_order_referal_amount, $affiliatepress_currency, $affiliatepress_args);

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
            }else{
                if(!empty($affiliatepress_cart_data)){
                    foreach($affiliatepress_cart_data as $affiliatepress_cart_item){

                        $affiliatepress_downlods_manager_product = array(
                            'product_id'=>$affiliatepress_cart_item['pid'],
                            'source'=>$this->affiliatepress_integration_slug
                        );
                        
                        $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_downlods_manager_product );

                        /* Verify if commissions are disabled for this product */
                        if($affiliatepress_product_disable){

                            continue;
                        }

                        $affiliatepress_amount = !empty($affiliatepress_cart_item['price']) ? floatval($affiliatepress_cart_item['price']) : 0;

                        if($affiliatepress_cart_item['discount_amount'] > 0 ){
                            $affiliatepress_amount = $$affiliatepress_amount - $affiliatepress_cart_item['discount_amount'];
                        }

                        $affiliatepress_product_id   = ( ! empty( $affiliatepress_cart_item['pid'] ) ? intval($affiliatepress_cart_item['pid']) : 0);
                        $affiliatepress_product_name   = !empty( $affiliatepress_cart_item['product_name']) ? sanitize_text_field($affiliatepress_cart_item['product_name']) : '';
                        $affiliatepress_quntity = !empty($affiliatepress_cart_item['quantity']) ? intval($affiliatepress_cart_item['quantity']) : 1;

                        $affiliatepress_args = array(
                            'origin'	       => $this->affiliatepress_integration_slug,
                            'type' 		       => $affiliatepress_commission_type,
                            'affiliate_id'     => $affiliatepress_affiliate_id,
                            'product_id'       => $affiliatepress_product_id,
                            'customer_id'      => $affiliatepress_customer_id,
                            'commission_basis' => 'per_product',
                            'order_id'         => $affiliatepress_order_id,
                            'quntity'          => $affiliatepress_quntity, 
                        );

                        $affiliatepress_amount = $affiliatepress_quntity * $affiliatepress_amount;
                        $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

                        $affiliatepress_single_product_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                        $affiliatepress_commission_amount += $affiliatepress_single_product_commission_amount;
                        $affiliatepress_order_referal_amount += $affiliatepress_amount;

                        $affiliatepress_allow_products_commission[] = array(
                            'product_id'           => $affiliatepress_product_id,
                            'product_name'         => $affiliatepress_product_name,
                            'order_id'             => $affiliatepress_order_id,
                            'commission_amount'    => $affiliatepress_single_product_commission_amount,
                            'order_referal_amount' => $affiliatepress_amount,
                            'commission_basis'     => 'per_product',
                            'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                            'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                        );
    
                        $affiliatepress_commission_products_ids[] = $affiliatepress_product_id;
                        $affiliatepress_commission_products_name[] = $affiliatepress_product_name;
                    }
                }
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }
            
            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_order_id, $affiliatepress_order_data);

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
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
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
                do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data );
                
                $affiliatepress_debug_log_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );
 
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
            }
        }

         /**
         * Function For Direct Completed paymnet commisison add
         *
         * @param  int $affiliatepress_order_id
         * @return void
         */
        function affiliatepress_add_completed_commission_downloads_manager($affiliatepress_order_id){

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id;

            $affiliatepress_all_commission_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, '*', 'WHERE ap_commission_reference_id = %s', array( $affiliatepress_order_id ), '', '', '', false, true,ARRAY_A);

            if(empty($affiliatepress_all_commission_data)){
                $affiliatepress_debug_log_msg  = "Downlod manager Completed Paymnet Commission Add.";

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Completed Add', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                $this->affiliatepress_add_pending_commission_downlods_manager($affiliatepress_order_id);
            }
        }
        
        /**
         * Function For Approved Commission add
         *
         * @param  int $affiliatepress_order_id
         * @return void
         */
        function affiliatepress_accept_pending_commission_downloads_manager($affiliatepress_order_id)
        {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');

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
         * Function For Approved Commission add After status chnage
         *
         * @param  int $affiliatepress_order_id
         * @param  string $affiliatepress_payment_status
         * @return void
         */
        function affiliatepress_accept_pending_commission_after_status_change_downloads_manager($affiliatepress_order_id , $affiliatepress_payment_status){

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;

            if($affiliatepress_payment_status != "Completed"){
                return;
            }

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');

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
         * Function For Pending Commission add After status chnage
         *
         * @param  int $affiliatepress_order_id
         * @param  string $affiliatepress_payment_status
         * @return void
         */
        function affiliatepress_add_pending_commission_after_status_change_downlods_manager($affiliatepress_order_id , $affiliatepress_payment_status){
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;

            if($affiliatepress_payment_status == "Pending" || $affiliatepress_payment_status == "Processing"){
                
                $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);

                if(!empty($affiliatepress_all_commission_data)){
                    foreach($affiliatepress_all_commission_data as $affiliatepress_commission_data){
                        if(!empty($affiliatepress_commission_data)){

                            $affiliatepress_ap_commission_status = (isset($affiliatepress_commission_data['ap_commission_status']))?intval($affiliatepress_commission_data['ap_commission_status']):0;
                            $affiliatepress_ap_commission_id     = (isset($affiliatepress_commission_data['ap_commission_id']))?intval($affiliatepress_commission_data['ap_commission_id']):0;

                            if($affiliatepress_ap_commission_status == 4){
                                $affiliatepress_debug_log_msg = sprintf( 'Commission #%s could not be pendingd because it was already paid.', $affiliatepress_ap_commission_id );
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
         * affiliatepress_check_plugin_active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active()
        {
            $affiliatepress_flag = true;

            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            if ( is_plugin_active( 'wpdm-premium-packages/wpdm-premium-packages.php' ) ) {
                $affiliatepress_flag = true;
            }
            else
            {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }   
        
        /**
         * Function For Check Integration settings Enable
         *
         * @return bool
         */
        function affiliatepress_download_manager_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_download_manager = $AffiliatePress->affiliatepress_get_settings('enable_download_manager', 'integrations_settings');
            if($affiliatepress_enable_download_manager != 'true'){
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }
    }
}
global $affiliatepress_download_manager;
$affiliatepress_download_manager = new affiliatepress_download_manager();