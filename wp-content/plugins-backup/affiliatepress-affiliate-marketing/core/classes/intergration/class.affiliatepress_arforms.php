<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_arforms') ){
    
    class affiliatepress_arforms Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){            

            global $affiliatepress_is_arforms_active;
            $affiliatepress_is_arforms_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'arforms';
            if($this->affiliatepress_arforms_commission_add() && $affiliatepress_is_arforms_active){

                /** Add pending commission */
                add_action( 'arf_transaction_data_arr', array($this,'affiliatepress_add_pending_commission_arforms'), 10, 3 );

                /** Add Approved Commission */
                add_action ( 'affiliatepress_after_successful_paymnet' , array($this , 'affiliatepress_after_success_paymnet_arforms') ,1,1);

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

                /**Add Disable Option Settings */
                add_action( 'arf_add_form_other_option_outside' , array($this,'affiliatepress_disable_option_arforms') ,10,2);

                /**Save Disable Option settings */
                add_filter('arf_save_form_options_outside' , array($this , 'affiliatepress_disable_option_save_arforms') ,10 ,3);

            }
            if($affiliatepress_is_arforms_active)
            {
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_arforms_product_func'),10,3); 
            }
        }

        function affiliatepress_get_arforms_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){

            global $wpdb;
        
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){
        
                $affiliatepress_existing_products_data = array();
                $affiliatepress_tbl_arf_forms = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'arf_forms' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arf_forms' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                
                $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$affiliatepress_tbl_arf_forms} WHERE name LIKE %s",'%' . $wpdb->esc_like($affiliatepress_search_product_str) . '%'),ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_arf_forms is a table name. false alarm 
    
                $affiliatepress_plan_ids = array_column($affiliatepress_results, 'id');

                if($affiliatepress_plan_ids){
                    foreach ($affiliatepress_plan_ids as $affiliatepress_plan_id) {
                        
                        $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT name FROM {$affiliatepress_tbl_arf_forms} WHERE id = %d",$affiliatepress_plan_id),ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_arf_forms is a table name. false alarm 

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
         * Function For disable settings add in ARForms
         *
         * @param  array $affiliatepress_values
         * @return array
         */
        function affiliatepress_disable_option_arforms( $affiliatepress_values )
        {
            global $maincontroller;
            ?>
                <div class="arf_other_option_separator"></div>
                <span class="arf_hidden_field_title" style="margin-bottom: 10px;"><?php echo esc_html__('AffiliatePress Settings', 'affiliatepress-affiliate-marketing'); ?></span>
                <div class="arf_popup_checkbox_wrapper">
                    <div class="arf_custom_checkbox_div">
                        <div class="arf_custom_checkbox_wrapper">
                            <input type="checkbox" name="options[arf_affiliatepress_enable_commission]" id="arf_affiliatepress_enable_commission" value="1" <?php isset($affiliatepress_values['arf_affiliatepress_enable_commission']) ? checked(esc_html($affiliatepress_values['arf_affiliatepress_enable_commission'])) : ''; ?> />
                            <svg width="18px" height="18px">
                                <path id="arfcheckbox_unchecked" d="M15.643,17.617H3.499c-1.34,0-2.427-1.087-2.427-2.429V3.045  c0-1.341,1.087-2.428,2.427-2.428h12.144c1.342,0,2.429,1.087,2.429,2.428v12.143C18.072,16.53,16.984,17.617,15.643,17.617z   M16.182,2.477H2.961v13.221h13.221V2.477z" />
                                <path id="arfcheckbox_checked" d="M15.645,17.62H3.501c-1.34,0-2.427-1.087-2.427-2.429V3.048  c0-1.341,1.087-2.428,2.427-2.428h12.144c1.342,0,2.429,1.087,2.429,2.428v12.143C18.074,16.533,16.986,17.62,15.645,17.62z   M16.184,2.48H2.963v13.221h13.221V2.48z M5.851,7.15l2.716,2.717l5.145-5.145l1.718,1.717l-5.146,5.145l0.007,0.007l-1.717,1.717  l-0.007-0.008l-0.006,0.008l-1.718-1.717l0.007-0.007L4.134,8.868L5.851,7.15z" />
                            </svg>
                        </div>
                        <span>
                            <label for="arf_affiliatepress_enable_commission"><?php echo esc_html__('Enable Commission ','affiliatepress-affiliate-marketing'); ?></label>
                        </span>
                    </div>
                    <div style="clear:both; margin-left: 40px; font-size: 14px; font-style: italic;"><?php esc_html_e('(Allow this form to generate Commission in AffiliatePress.)', 'affiliatepress-affiliate-marketing'); ?></div>
                </div>

                <div class="arftablerow">
					<div class="arfcolmnleft arf_affiliatepress_commission_type ">
						<div class="arfcolumnleft"><label for="arf_affiliatepress_commission_type"><?php esc_html_e( 'AffiliatePress Rate Type', 'affiliatepress-affiliate-marketing' ); ?></label></div>
						<div class="arfcolumnright">
							<?php
                               $affiliatepress_commission_type =array(
                                'sale' => esc_html__('Sale', 'affiliatepress-affiliate-marketing'),
                                'opt-in' => esc_html__('Opt-In', 'affiliatepress-affiliate-marketing'),
                                'lead' => esc_html__('Lead', 'affiliatepress-affiliate-marketing'),
                            );
    
                            echo $maincontroller->arf_selectpicker_dom( 'options[arf_affiliatepress_commission_type]', 'arf_affiliatepress_commission_type', '', 'width:400px;', isset($affiliatepress_values['arf_affiliatepress_commission_type']) ? ($affiliatepress_values['arf_affiliatepress_commission_type']) : 'sale', '', $affiliatepress_commission_type );//phpcs:ignore
                            ?>
						</div>
					</div>
				</div>

                <?php do_action('affiliatepress_arforms_add_product_settings' , $affiliatepress_values); ?>

            <?php
        }
        
        /**
         * Function For disable settings Save in ARForms
         *
         * @param  array $affiliatepress_options
         * @param  array $affiliatepress_values
         * @param  integer $affiliatepress_form_id
         * @return array
         */
        function affiliatepress_disable_option_save_arforms( $affiliatepress_options,$affiliatepress_values,$affiliatepress_form_id )
        {
            $affiliatepress_options['arf_affiliatepress_enable_commission'] = isset($affiliatepress_values['options']['arf_affiliatepress_enable_commission']) ? intval($affiliatepress_values['options']['arf_affiliatepress_enable_commission']) : 0;

            $affiliatepress_options['arf_affiliatepress_commission_type'] = isset($affiliatepress_values['options']['arf_affiliatepress_commission_type']) ? sanitize_text_field($affiliatepress_values['options']['arf_affiliatepress_commission_type']) : 'sale';

            $affiliatepress_options = apply_filters( 'affiliatepress_arforms_save_product_settings', $affiliatepress_options, $affiliatepress_values );

            return $affiliatepress_options;
        }
        
        /**
         * Function For check ARForms Settings Enable
         *
         * @return void
         */
        function affiliatepress_arforms_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;            
            $affiliatepress_enable_arforms = $AffiliatePress->affiliatepress_get_settings('enable_arforms', 'integrations_settings');
            if($affiliatepress_enable_arforms != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For check ARForms plugin Active
         *
         * @return void
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if ( !is_plugin_active( 'arforms/arforms.php' ) ) {
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
         * @param  integer $affiliatepress_entry_id
         * @param  array $affiliatepress_transaction_data
         * @return array
         */
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_entry_id, $affiliatepress_transaction_data){

            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        
                        $affiliatepress_billing_email = isset($affiliatepress_transaction_data) ? sanitize_email($affiliatepress_transaction_data['user_data']['email']) : '';    
                        
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
         * Function For ARforms in pending Commission add
         *
         * @param  integer $affiliatepress_form_id
         * @param  integer $affiliatepress_entry_id
         * @param  array $affiliatepress_transaction_data
         * @return void
         */
        function affiliatepress_add_pending_commission_arforms( $affiliatepress_form_id ,$affiliatepress_entry_id , $affiliatepress_transaction_data )
        {
            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_entry_id = !empty($affiliatepress_entry_id) ? intval($affiliatepress_entry_id) : 0;
            $affiliatepress_payment_status = !empty($affiliatepress_transaction_data['payment_status']) ? sanitize_text_field($affiliatepress_transaction_data['payment_status']) : '';

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();  

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id):0;

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id' => $affiliatepress_entry_id) );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_entry_id, $affiliatepress_transaction_data);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_email = !empty($affiliatepress_transaction_data['user_data']['email']) ? sanitize_email($affiliatepress_transaction_data['user_data']['email']) : '';

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

            $affiliatepress_commission_type = isset($affiliatepress_form_options['arf_affiliatepress_commission_type']) ? sanitize_text_field($affiliatepress_form_options['arf_affiliatepress_commission_type']) : 'sale';
            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_form_title = $this->affiliatepress_get_arforms_form_title($affiliatepress_form_id);

            $affiliatepress_form_title = !empty($affiliatepress_form_title) ? sanitize_text_field($affiliatepress_form_title):'';

            $affiliatepress_currency = !empty($affiliatepress_transaction_data['currency'])  ? sanitize_text_field($affiliatepress_transaction_data['currency']) : '';

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
                
                $affiliatepress_amount = !empty($affiliatepress_transaction_data['amount']) ? floatval($affiliatepress_transaction_data['amount']) : 0;

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => 0,
                    'order_id'         => $affiliatepress_entry_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules  = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args);                
                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => 0,
                    'product_name'         => '',
                    'order_id'             => $affiliatepress_entry_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );
                $affiliatepress_order_referal_amount = $affiliatepress_amount;

            }else{

                $affiliatepress_ar_forms_product = array(
                    'product_id'=> $affiliatepress_form_id,
                    'source'=>$this->affiliatepress_integration_slug,
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_ar_forms_product );
    
                if($affiliatepress_product_disable){
                    return;
                }

                $affiliatepress_amount = !empty($affiliatepress_transaction_data['amount']) ? floatval($affiliatepress_transaction_data['amount']) : 0;

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_form_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_entry_id,
                );
                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;
                
                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_form_id,
                    'product_name'         => $affiliatepress_form_title,
                    'order_id'             => $affiliatepress_entry_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => $affiliatepress_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );

                $affiliatepress_commission_products_ids[] = $affiliatepress_form_id;
                $affiliatepress_commission_products_name[] = $affiliatepress_form_title;
            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_entry_id, $affiliatepress_transaction_data);

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

            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => 2,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_reference_id'     => $affiliatepress_entry_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')) // phpcs:ignore
            );

            $affiliatepress_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);

            if($affiliatepress_commission_id == 0){
                $affiliatepress_msg = 'Pending commission could not be inserted due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
            }else{

                $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;
                $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                
                do_action('affiliatepress_after_commission_created', $affiliatepress_commission_id, $affiliatepress_commission_data );
                $affiliatepress_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_commission_id );

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);    
                
                if($affiliatepress_payment_status == "completed"){
                    do_action('affiliatepress_after_successful_paymnet' , $affiliatepress_entry_id);
                }
            }
        }
        
        /**
         * Function For ARforms in Complete Commission add
         *
         * @param  integer $affiliatepress_entry_id
         * @return void
         */
        function affiliatepress_after_success_paymnet_arforms($affiliatepress_entry_id)
        {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking,$AffiliatePress;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_entry_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3)');

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
         * Function For get ARForms form Title
         *
         * @param  integer $affiliatepress_form_id
         * @return string
         */
        function affiliatepress_get_arforms_form_title($affiliatepress_form_id)
        {
            global $wpdb;
            $affiliatepress_tbl_arf_forms = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'arf_forms' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arf_forms' contains table name and it's prepare properly using 'arm_payment_log' function

            $affiliatepress_form_name = $wpdb->get_var( $wpdb->prepare("SELECT name FROM $affiliatepress_tbl_arf_forms WHERE id = %d", $affiliatepress_form_id) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_arf_forms is a table name. false alarm
			
            $affiliatepress_form_name = !empty($affiliatepress_form_name) ? sanitize_text_field($affiliatepress_form_name):'';
            return $affiliatepress_form_name;
        }
        
        /**
         * Function For get ARForms FOrm Options(Settings)
         *
         * @param  integer $affiliatepress_form_id
         * @return void
         */
        function affiliatepress_get_arforms_form_options($affiliatepress_form_id)
        {
            global $wpdb;

            $affiliatepress_tbl_arf_forms = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'arf_forms' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arf_forms' contains table name and it's prepare properly using 'arm_payment_log' function

            $affiliatepress_form_options = $wpdb->get_var( $wpdb->prepare("SELECT options FROM $affiliatepress_tbl_arf_forms WHERE id = %d", $affiliatepress_form_id) );	// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_arf_forms is a table name. false alarm

            $affiliatepress_form_options = unserialize($affiliatepress_form_options);
			
            return $affiliatepress_form_options;
        }
    }
}
global $affiliatepress_arforms;
$affiliatepress_arforms = new affiliatepress_arforms();