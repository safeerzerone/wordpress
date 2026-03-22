<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_getpaid') ){
    
    class affiliatepress_getpaid Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){            

            global $affiliatepress_is_getpaid_active ;
            $affiliatepress_is_getpaid_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'getpaid';

            if($this->affiliatepress_getpaid_commission_add() && $affiliatepress_is_getpaid_active){

                global $wpdb, $affiliatepress_tracking, $affiliatepress_affiliates, $AffiliatePress, $affiliatepress_commission_debug_log_id;

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

                /**Add Pending Commission */
                add_action( 'getpaid_new_invoice', array($this,'affiliatepress_getpaid_add_new_commission'), 10);

                /**Add Approved Commission */
                add_action( 'getpaid_invoice_status_publish', array($this,'affiliatepress_getpaid_accept_pending_commission'), 10, 2);
                
                /* Update Status to Pending */
                add_action( 'getpaid_invoice_status_wpi-pending', array($this,'affiliatepress_getpaid_approve_rejected_commission'), 10, 3 );
                add_action( 'getpaid_invoice_status_wpi-processing', array($this,'affiliatepress_getpaid_approve_rejected_commission'), 10, 3 );
                add_action( 'getpaid_invoice_status_wpi-onhold', array($this,'affiliatepress_getpaid_approve_rejected_commission'), 10, 3 );   
                
                /* Enable/Disable Settings Add */
                add_filter( 'add_meta_boxes', array($this,'affiliatepress_add_commission_settings_metabox'), 10, 2 );
                add_action( 'getpaid_item_metabox_save', array($this,'affiliatepress_item_commission_settings'), 10, 2 );
            }

            if($affiliatepress_is_getpaid_active){
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_getpaid_link_order_func'),10,3); 
            }
        }
                
        /**
         * Function for save item commission settings
         *
         * @param  int $post_id
         * @param  array $affiliatepress_item
         * @return void
        */
        function affiliatepress_item_commission_settings($post_id, $affiliatepress_item){
            if (empty($_POST['getpaid_commission_token']) || !wp_verify_nonce( $_POST['getpaid_commission_token'], 'affiliatepress_getpaid_commission') || wp_is_post_autosave( $post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)){  // phpcs:ignore
                return $post_id;
            }
            if(isset($_POST['affiliatepress_commission_disable_getpaid']) && !empty($_POST['affiliatepress_commission_disable_getpaid'])){
                update_post_meta($post_id,'affiliatepress_commission_disable_getpaid',1);
            }else{
                delete_post_meta($post_id,'affiliatepress_commission_disable_getpaid');
            }

            do_action('affiliatepress_getpaid_save_product_settings' , $post_id);
        }

        /**
         * Function for add metabox settings
         *
         * @param  string $affiliatepress_post_type
         * @param  array $post
         * @return void
        */
        function affiliatepress_add_commission_settings_metabox( $affiliatepress_post_type, $post ) {            
            if($affiliatepress_post_type == 'wpi_item'){
                add_meta_box( 'affiliatepress_add_commission_settings_metabox', esc_html__( 'AffiliatePress Commission settings', 'affiliatepress-affiliate-marketing'),  array($this,'affiliatepress_add_commission_settings_metabox_fnl'), $affiliatepress_post_type, 'advanced', 'default' );
            }else{
                return;
            }                        
        }
             
        
        /**
         * Function For Check Disable Settings Add
         *
         * @return void
         */
        function affiliatepress_add_commission_settings_metabox_fnl(){
            global $post;
            $affiliatepress_disable_commissions = get_post_meta( $post->ID, 'affiliatepress_commission_disable_getpaid', true );
            wp_nonce_field( 'affiliatepress_getpaid_commission', 'getpaid_commission_token', false );
        ?>
            <div id="affiliatepress_settings" class="ap-affiliatepress-disable-settings-wrapper">
                <table cellpadding="5" cellspacing="0" style="margin-top: 15px;" border="0">
                    <tr>
                        <th style="text-align: left;"><b><?php echo esc_html__( 'Disable Commissions :', 'affiliatepress-affiliate-marketing');?></b></th>
                        <td>
                            <input type="checkbox" <?php echo esc_html(!empty($affiliatepress_disable_commissions)?'checked':'') ?> name="affiliatepress_commission_disable_getpaid" id="affiliatepress-disable-commissions" value="1" class="affiliatepress-disable-commissions-field">  
                            <?php esc_html_e( 'Disable commissions for this Item.', 'affiliatepress-affiliate-marketing'); ?>                        
                        </td>
                    </tr>
                    <?php do_action('affiliatepress_getpaid_add_product_settings'); ?>
                </table>
            </div>
        <?php 
        }


        /**
         * FUnction for approve reject commission
         *
         * @param  array $affiliatepress_invoice
         * @param  array $affiliatepress_status_transition
         * @return void
        */
        function affiliatepress_getpaid_approve_rejected_commission($affiliatepress_invoice, $affiliatepress_status_transition){
            global $AffiliatePress, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id;

            $affiliatepress_status_to_data = (isset($affiliatepress_status_transition['to']))?sanitize_text_field($affiliatepress_status_transition['to']):'';
            if ( in_array( $affiliatepress_status_to_data, array( 'wpi-failed', 'wpi-cancelled', 'wpi-refunded', 'publish' ) ) ) {
                return;
            }
            $affiliatepress_invoice_id = isset($affiliatepress_invoice) ? intval($affiliatepress_invoice->get_id()) : 0;
            $affiliatepress_all_commissition_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_invoice_id, $this->affiliatepress_integration_slug);            

            if(!empty($affiliatepress_all_commissition_data)){

                foreach($affiliatepress_all_commissition_data as $affiliatepress_commissition_data){

                    if(!empty($affiliatepress_commissition_data)){
                        $affiliatepress_commission_status = (isset($affiliatepress_commissition_data['ap_commission_status']))?intval($affiliatepress_commissition_data['ap_commission_status']):0;
                        $affiliatepress_commission_id     = (isset($affiliatepress_commissition_data['ap_commission_id']))?intval($affiliatepress_commissition_data['ap_commission_id']):0;
                        if($affiliatepress_commission_status == 4){
                            $affiliatepress_msg = sprintf( 'Commission #%s could not be pending because it was already paid.', $affiliatepress_commission_id );
                            continue;
                        }
                        if($affiliatepress_commission_id != 0){
        
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time('mysql', true),
                                'ap_commission_status' 		 => 2
                            );
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_commission_id ));
                            $affiliatepress_msg = sprintf( 'WOO: Commission #%s successfully marked as rejected, after order #%s failed or was cancelled.', $affiliatepress_commission_id, $affiliatepress_order_id );
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Reject ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
        
                        }
                    }
                    
                }

            }



        }

        /**
         * Function for accept pending commission
         *
         * @param  array $affiliatepress_invoice
         * @param  array $affiliatepress_status_transition
         * @return void
        */
        function affiliatepress_getpaid_accept_pending_commission( $affiliatepress_invoice, $affiliatepress_status_transition ) {

            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_tbl_ap_affiliate_commissions = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_commissions contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_invoice_id = isset($affiliatepress_invoice) ? intval($affiliatepress_invoice->get_id()) : 0;

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_invoice_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');

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
        
                        }else{
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => 1
                            );
                        }

                        if($affiliatepress_updated_commission_status != 2){
                        
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_commission_id ));
                            $affiliatepress_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
        
                            do_action('affiliatepress_after_commissions_status_change', $affiliatepress_commission_id, $affiliatepress_updated_commission_status, 2);
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
        
                        }
        
                    }

                }

            }



        }
  
        /**
         * Function for affiliate validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  int $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  int $affiliatepress_invoice_id
         * @param  array $affiliatepress_invoice
         * @return array
         */
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_invoice_id, $affiliatepress_invoice){

            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){

                        $affiliatepress_billing_email = isset($affiliatepress_invoice) ? sanitize_email($affiliatepress_invoice->get_email()) : '';         
                        if($AffiliatePress->affiliatepress_affiliate_has_email( $affiliatepress_affiliate_id, $affiliatepress_billing_email ) ) {                   
                            $affiliatepress_commission_validation['variant']   = 'error';
                            $affiliatepress_commission_validation['title']     = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                            $affiliatepress_commission_validation['msg']       = esc_html__( 'Pending commission was not created because the customer is also the affiliate.', 'affiliatepress-affiliate-marketing');                                            
                        }
                        if(is_user_logged_in() && get_current_user_id() == $AffiliatePress->affiliatepress_get_affiliate_user_id($affiliatepress_affiliate_id)){
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
         * Function for add commission
         *
         * @param  array $affiliatepress_invoice
         * @return void
        */
        function affiliatepress_getpaid_add_new_commission($affiliatepress_invoice){

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            /* Get and check to see if referrer exists */
            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();           
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id):0;
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_invoice->get_id()) );

            if(empty($affiliatepress_affiliate_id)){
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            /* Check to see if the invoice is a renewal or not */
            if ( $affiliatepress_invoice->is_renewal() ) {
                $affiliatepress_log_msg = "Pending commission was not created because the invoice is a renewal.";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Pending commission was not created ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }            
            
            $affiliatepress_invoice_id = isset($affiliatepress_invoice) ? intval($affiliatepress_invoice->get_id()) : 0;
            if( !$affiliatepress_invoice_id ) {                
                $affiliatepress_log_msg = "Empty Invoice ID ";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Invoice ID ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;                
            }
            
            $affiliatepress_commission_validation = array();
            $affiliatepress_commission_validation = apply_filters('affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_invoice_id, $affiliatepress_invoice);
            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;
                }
            }

            $affiliatepress_invoice_items = $affiliatepress_invoice->get_items();
            if(!is_array($affiliatepress_invoice_items)){        
                $affiliatepress_log_msg = "commission was not created because the invoice details were not valid. ";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.'Invalid Invoice Detail ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;         
            }

            /* Add Commission Customer Here */
            $affiliatepress_customer_args = array(
                'email'   	   => isset($affiliatepress_invoice) ? sanitize_email($affiliatepress_invoice->get_email()) : '',
                'user_id' 	   => isset($affiliatepress_invoice) ? intval($affiliatepress_invoice->get_user_id()) : 0,
                'first_name'   => isset($affiliatepress_invoice) ? sanitize_text_field($affiliatepress_invoice->get_first_name()) : '',
                'last_name'	   => isset($affiliatepress_invoice) ? sanitize_text_field($affiliatepress_invoice->get_last_name()) : '',
                'affiliate_id' => $affiliatepress_affiliate_id
            );

            $affiliatepress_customer_commisison_add = true;
            $affiliatepress_customer_commisison_add = apply_filters('affiliatepress_validate_customer_for_commission', $affiliatepress_customer_commisison_add, $affiliatepress_customer_args,$this->affiliatepress_integration_slug);

            if(!$affiliatepress_customer_commisison_add){
                return;
            }
            
            $affiliatepress_customer_id = $AffiliatePress->affiliatepress_add_commission_customer( $affiliatepress_customer_args );

            $affiliatepress_customer_id = !empty($affiliatepress_customer_id) ? intval($affiliatepress_customer_id) : 0;

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();
            $affiliatepress_commission_type = "sale";

            $affiliatepress_commission_type = !empty($affiliatepress_commission_type) ? sanitize_text_field($affiliatepress_commission_type) : '';

            $affiliatepress_invoice_price = isset($affiliatepress_invoice) ? floatval($affiliatepress_invoice->get_total()) : 0;

            $affiliatepress_order_referal_amount = 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_total_price = $affiliatepress_invoice_price;

                $affiliatepress_amount = $affiliatepress_total_price;
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => 'sale',
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => 0,
                    'order_id'         => $affiliatepress_invoice_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount, '', $affiliatepress_args);

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => 0,
                    'product_name'         => '',
                    'order_id'             => $affiliatepress_invoice_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );

                $affiliatepress_order_referal_amount = $affiliatepress_amount;
            }else{
                
                foreach( $affiliatepress_invoice_items as $affiliatepress_invoice_item ){

                    $affiliatepress_item_name = isset($affiliatepress_invoice) ? sanitize_text_field($affiliatepress_invoice_item->get_name()) : '';
                    $affiliatepress_amount = $this->affiliteprtess_item_amount_find($affiliatepress_invoice_item, $affiliatepress_invoice);      
                    
                    $affiliatepress_getpaid_product = array(
                        'product_id'=> $affiliatepress_invoice_item->get_id(),
                        'source'=>$this->affiliatepress_integration_slug,
                    );
        
                    $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_getpaid_product );
        
                    if($affiliatepress_product_disable){
                        continue;
                    }

                    $affiliatepress_commission_type = $affiliatepress_invoice_item->is_recurring() ? 'subscription' : 'sale';
                    $affiliatepress_quantity = intval($affiliatepress_invoice_item->get_quantity());                   
                    if($affiliatepress_quantity == 0){
                        $affiliatepress_quantity = 1;
                    }
                    $affiliatepress_args = array(
                        'origin'	       => $this->affiliatepress_integration_slug,
                        'type' 		       => $affiliatepress_commission_type,
                        'affiliate_id'     => $affiliatepress_affiliate_id,
                        'product_id'       => $affiliatepress_invoice_item->get_id(),
                        'customer_id'      => $affiliatepress_customer_id,
                        'commission_basis' => 'per_product',
                        'order_id'         => $affiliatepress_invoice_id,
                        'quntity'          => $affiliatepress_quantity, 
                    );
                    $affiliatepress_amount = $affiliatepress_amount*$affiliatepress_quantity;
                    $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount( $affiliatepress_amount, '', $affiliatepress_args );

                    $affiliatepress_single_product_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                    $affiliatepress_commission_amount += $affiliatepress_single_product_commission_amount;
                    $affiliatepress_order_referal_amount += $affiliatepress_amount;
                    
                    $affiliatepress_allow_products_commission[] = array(
                        'product_id'           => $affiliatepress_invoice_item->get_id(),
                        'product_name'         => $affiliatepress_item_name,
                        'order_id'             => $affiliatepress_invoice_id,
                        'commission_amount'    => $affiliatepress_single_product_commission_amount,
                        'order_referal_amount' => $affiliatepress_amount,
                        'commission_basis'     => 'per_product',
                        'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                        'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                    );

                    $affiliatepress_commission_products_ids[]  = isset($affiliatepress_invoice_item) ? intval($affiliatepress_invoice_item->get_id()) : 0;
                    $affiliatepress_commission_products_name[] = $affiliatepress_item_name;

                }
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_invoice_id, $affiliatepress_invoice);
            
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
                'ap_commission_reference_id'     => $affiliatepress_invoice_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_invoice_price,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp'))// phpcs:ignore
            );            
            
            /* Insert The Commission */
            $affiliatepress_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission($affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
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
         * Function For Get Item Amount
         *
         * @param array $affiliatepress_invoice_item
         * @param array $affiliatepress_invoice
         * @return void
         */
        function affiliteprtess_item_amount_find( $affiliatepress_invoice_item, $affiliatepress_invoice ){

            $affiliatepress_amount = isset($affiliatepress_invoice) ? floatval($affiliatepress_invoice_item->get_price()) : 0;
            
            if ( $affiliatepress_invoice->get_total_discount() > 0 ) {
                $affiliatepress_discount = wpinv_get_discount( $affiliatepress_invoice->get_discount_code() );                        
                if ( $affiliatepress_invoice->is_renewal() && ! $affiliatepress_discount->is_recurring() ){
                    return $affiliatepress_amount;
                }                
                $affiliatepress_discount_amount = $affiliatepress_discount->get_amount();
                if ( ! empty( $affiliatepress_discount_amount ) ) {
                    $affiliatepress_discount_amount = floatval( wpinv_sanitize_amount( $affiliatepress_discount_amount ) );
                    if ( $affiliatepress_discount->is_type( 'percent' ) ) {        
                        $affiliatepress_discount_amount = $affiliatepress_amount * ( $affiliatepress_discount_amount / 100 );
                    }
                    if ( $affiliatepress_discount_amount < 0 ) {
                        $affiliatepress_discount_amount = 0;
                    }
                    if ( $affiliatepress_discount_amount > $affiliatepress_amount ) {
                        $affiliatepress_discount_amount = $affiliatepress_amount;
                    }
                }
                $affiliatepress_amount -= $affiliatepress_discount_amount;
            }

            return $affiliatepress_amount;
        
        }


        /**
         * Function For Getpaid get order link
         *
         * @param  int $affiliatepress_commission_reference_id
         * @param  string $affiliatepress_commission_source
         * @return string
         */
        function affiliatepress_get_getpaid_link_order_func($affiliatepress_commission_reference_id, $affiliatepress_commission_source){            
            if($affiliatepress_commission_source == $this->affiliatepress_integration_slug){
                $affiliatepress_invoice = wpinv_get_invoice( $affiliatepress_commission_reference_id );
                if(empty($affiliatepress_invoice)){
                    return $affiliatepress_commission_reference_id;
                }                
                if(!empty($affiliatepress_invoice->get_id())){
                    $affiliatepress_url = add_query_arg( array( 'post' => $affiliatepress_commission_reference_id, 'action' => 'edit' ), admin_url( 'post.php' ) );
                    $affiliatepress_commission_order_link   = '<a target="_blank" class="ap-refrance-link" href="'.esc_url($affiliatepress_url).'"> '. $affiliatepress_commission_reference_id .' </a>';
                    $affiliatepress_commission_reference_id = $affiliatepress_commission_order_link;    
                }
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
            if ( !is_plugin_active( 'invoicing/invoicing.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Check Enable Integration Settings 
         *
         * @return bool
         */
        function affiliatepress_getpaid_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;            
            $affiliatepress_enable_getpaid = $AffiliatePress->affiliatepress_get_settings('enable_getpaid', 'integrations_settings');
            if($affiliatepress_enable_getpaid != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }



        
    }
}
global $affiliatepress_getpaid;
$affiliatepress_getpaid = new affiliatepress_getpaid();