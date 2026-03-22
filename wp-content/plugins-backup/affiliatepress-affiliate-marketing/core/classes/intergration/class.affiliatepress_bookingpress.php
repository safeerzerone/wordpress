<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_bookingpress') ){
    
    class affiliatepress_bookingpress Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){    

            global $affiliatepress_is_bookingpress_active ;
            $affiliatepress_is_bookingpress_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'bookingpress';
            if($this->affiliatepress_bookingpress_commission_add() && $affiliatepress_is_bookingpress_active){

                /**Add Pending Commission */
                add_action('bookingpress_after_book_appointment' , array($this , 'affiliatepress_pending_commission_add_after_book_appointment_bookingpress') ,20,3);

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

                /**Add Approved Commission */
                add_action('bookingpress_update_payment_details_externally_after_update_status' , array($this , 'affiliatepress_accept_pending_commission_bookingpress') , 10,1);

                /**Add pending Commission After Status Change */
                add_action('bookingpress_update_payment_details_externally_after_update_status' , array($this , 'affiliatepress_pending_commission_add_after_status_chnage_bookingpress') , 10,1);

                

                /**Add Disable Option settings */
                add_action('bookingpress_add_content_after_basic_details' , array($this , 'affiliatepress_disable_commission_Section_add_bookingpress'), 20);
                add_filter( 'bookingpress_modify_service_data_fields', array( $this, 'affiliatepress_modify_service_data_fields_add_bookingpress' ), 10 );
                add_filter('bookingpress_after_add_update_service' , array($this , 'affiliatepress_save_enable_affiliate_data') ,10,3);
                add_action( 'bookingpress_edit_service_more_vue_data', array( $this, 'affiliatepress_bookingpress_edit_service_more_vue_data_func' ), 10 );
                add_filter('bookingpress_modify_edit_service_data', array($this, 'affiliatepress_bookingpress_modify_edit_service_data_func'), 10, 2);
            }

            if($affiliatepress_is_bookingpress_active)
            {
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_bookingpress_product_func'),10,3); 
            }
              
        }

        function affiliatepress_get_bookingpress_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){

            global $wpdb;
        
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){
        
                $affiliatepress_existing_products_data = array();
                $affiliatepress_tbl_bookingpress_services = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'bookingpress_services' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'bookingpress_services' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                
                $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT bookingpress_service_id FROM {$affiliatepress_tbl_bookingpress_services} WHERE bookingpress_service_name LIKE %s",'%' . $wpdb->esc_like($affiliatepress_search_product_str) . '%'),ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_bookingpress_services is a table name. false alarm 
    
                $affiliatepress_plan_ids = array_column($affiliatepress_results, 'bookingpress_service_id');

                if($affiliatepress_plan_ids){
                    foreach ($affiliatepress_plan_ids as $affiliatepress_plan_id) {
                        
                        $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT bookingpress_service_name FROM {$affiliatepress_tbl_bookingpress_services} WHERE bookingpress_service_id = %d",$affiliatepress_plan_id),ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_bookingpress_services is a table name. false alarm 

                        $affiliatepress_plan_name = !empty($affiliatepress_results) ? $affiliatepress_results[0]['bookingpress_service_name'] : '';

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
         * Function For disable settings add dynemic vue metod
         *
         * @param  array $affiliatepress_bookingpress_services_vue_data_fields
         * @return void
         */
        function affiliatepress_modify_service_data_fields_add_bookingpress($affiliatepress_bookingpress_services_vue_data_fields){
            $affiliatepress_bookingpress_services_vue_data_fields['service']['affiliatepress_enable_commission']      = false;
            $affiliatepress_bookingpress_services_vue_data_fields = apply_filters('affiliatepress_modify_service_data_fields_bookingpress' , $affiliatepress_bookingpress_services_vue_data_fields);

            return $affiliatepress_bookingpress_services_vue_data_fields;
        }
        
        /**
         * Function For disable settings Add
         *
         * @return void
         */
        function affiliatepress_disable_commission_Section_add_bookingpress(){
            ?>
                <div class="bpa-form-row">
                    <el-row>
                        <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                            <div class="bpa-db-sec-heading">
                                <el-row type="flex" align="middle">
                                    <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                        <div class="bpa-db-sec-left">
                                            <h2 class="bpa-page-heading"><?php esc_html_e( 'Affiliatepress', 'affiliatepress-affiliate-marketing' ); ?></h2>
                                        </div>
                                    </el-col>
                                </el-row>
                            </div>    
                            <div class="bpa-default-card bpa-db-card bpa-grid-list-container bpa-dc__staff--assigned-service">						
                                <el-tabs class="bpa-tabs bpa-tabs--service-integration" v-model="bookingpress_advance_option_active_tab"> 							
                                    <el-tab-pane name="service_settings">
                                        <template #label>
                                            <span><?php esc_html_e( 'AffiliatePress Settings', 'affiliatepress-affiliate-marketing' ); ?></span>
                                        </template>
                                        <el-row :gutter="20">
                                            <el-col :xs="24" :sm="24" :md="24" :lg="08" :xl="08">
                                                <label class="bpa-form-label"><?php esc_html_e('Enable Affiliate Commission:', 'affiliatepress-affiliate-marketing'); ?></label>
                                                <el-switch class="bpa-swtich-control" v-model="service.affiliatepress_enable_commission" ></el-switch>
                                            </el-col>
                                            <?php do_action('affiliatepress_bookingpress_add_product_settings'); ?>
                                        </el-row>
                                    </el-tab-pane>
                                </el-tabs>
                            </div>
                        </el-col>
                    </el-row>
                </div>
            <?php
        }
        
        /**
         * Function For disable settings Save
         *
         * @param  array $response
         * @param  int $affiliatepress_service_id
         * @param  array $affiliatepress_service_data
         * @return array
         */
        function affiliatepress_save_enable_affiliate_data($response, $affiliatepress_service_id, $affiliatepress_service_data){

            global $wpdb , $bookingpress_services;

            $affiliatepress_enable_option = isset($affiliatepress_service_data['affiliatepress_enable_commission']) ? sanitize_text_field($affiliatepress_service_data['affiliatepress_enable_commission']) : false ;
            $bookingpress_services->bookingpress_add_service_meta( $affiliatepress_service_id, 'affiliatepress_enable_commission', $affiliatepress_enable_option );

            $response = apply_filters('affiliatepress_bookingpress_save_product_settings' , $response , $affiliatepress_service_id , $affiliatepress_service_data);

            return $response;
        }
        
        /**
         * Function For Dynemic vue data add 
         *
         * @return void
         */
        function affiliatepress_bookingpress_edit_service_more_vue_data_func(){
            ?>
                vm2.service.affiliatepress_enable_commission = (response.data.affiliatepress_enable_commission !== undefined) ? response.data.affiliatepress_enable_commission : false;
            <?php
            do_action('affiliatepress_edit_Service_data_bookingpress');
        }
        
        /**
         * Function For Edit Disable commission settings
         *
         * @param  array $response
         * @param  int $affiliatepress_service_id
         * @return void
         */
        function affiliatepress_bookingpress_modify_edit_service_data_func($response,$affiliatepress_service_id){

            $affiliatepress_enable_commission = isset($response['affiliatepress_enable_commission']) ? sanitize_text_field($response['affiliatepress_enable_commission']) : 'false';

            $response['affiliatepress_enable_commission'] = !empty($affiliatepress_enable_commission) &&  $affiliatepress_enable_commission == 'true' ? true : false;

            return $response;
        }
   
        /**
         * Function for affiliate validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  int $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  int $affiliatepress_payment_id
         * @param  array $affiliatepress_bookingpress_appointment_data
         * @return array
         */
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_payment_id, $affiliatepress_bookingpress_appointment_data){

            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = isset($affiliatepress_bookingpress_appointment_data['bookingpress_customer_email']) ? $affiliatepress_bookingpress_appointment_data['bookingpress_customer_email'] : '';             
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
         * Function For Add pending Commission
         *
         * @param  int $inserted_booking_id
         * @param  int $affiliatepress_entry_id
         * @param  array $affiliatepress_payment_gateway_data
         * @return void
         */
        function affiliatepress_pending_commission_add_after_book_appointment_bookingpress($inserted_booking_id, $affiliatepress_entry_id, $affiliatepress_payment_gateway_data){
            
            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_tbl_bookingpress_appointment_bookings = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'bookingpress_appointment_bookings' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'bookingpress_appointment_bookings' contains table name and it's prepare properly using 'arm_payment_log' function

            $affiliatepress_bookingpress_appointment_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_bookingpress_appointment_bookings, '*', 'WHERE bookingpress_appointment_booking_id = %d', array($inserted_booking_id), '', '', '', false, true,ARRAY_A);

            $affiliatepress_payment_id = isset($affiliatepress_bookingpress_appointment_data['bookingpress_payment_id']) ? intval($affiliatepress_bookingpress_appointment_data['bookingpress_payment_id']) : 0;

            if($affiliatepress_payment_id == 0){
                return;
            }
            
            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();    

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;

            $affiliatepress_order_data   = array('order_id' => $affiliatepress_payment_id);
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_order_data );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_payment_id, $affiliatepress_bookingpress_appointment_data);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            /* Add Commission Customer Here */
            $affiliatepress_customer_args = array(
                'email'   	   => isset($affiliatepress_bookingpress_appointment_data['bookingpress_customer_email']) ? sanitize_email( $affiliatepress_bookingpress_appointment_data['bookingpress_customer_email']) : '',
                'user_id' 	   => '',
                'first_name'   => isset($affiliatepress_bookingpress_appointment_data['bookingpress_customer_firstname']) ? sanitize_text_field( $affiliatepress_bookingpress_appointment_data['bookingpress_customer_firstname']) : '',
                'last_name'	   => isset($affiliatepress_bookingpress_appointment_data['bookingpress_customer_lastname']) ? sanitize_text_field( $affiliatepress_bookingpress_appointment_data['bookingpress_customer_lastname']) : '',
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
            $affiliatepress_currency = isset($affiliatepress_bookingpress_appointment_data['bookingpress_service_currency']) ? sanitize_text_field($affiliatepress_bookingpress_appointment_data['bookingpress_service_currency']) :'';
            $affiliatepress_order_total_amount = isset($affiliatepress_bookingpress_appointment_data['bookingpress_total_amount']) ? floatval($affiliatepress_bookingpress_appointment_data['bookingpress_total_amount']) : 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_order_referal_amount = $affiliatepress_order_total_amount;

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => 'sale',
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => 0,
                    'order_id'         => $affiliatepress_payment_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                    'quntity'          => 1,
                );

                $affiliatepress_commission_rules  = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_order_referal_amount, $affiliatepress_currency, $affiliatepress_args);        
                
                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => 0,
                    'product_name'         => '',
                    'order_id'             => $affiliatepress_payment_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',                     
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );
            }else{
                $affiliatepress_bookingpress_is_cart = isset($affiliatepress_bookingpress_appointment_data['bookingpress_is_cart']) ? intval($affiliatepress_bookingpress_appointment_data['bookingpress_is_cart']) : 0;
        
                if($affiliatepress_bookingpress_is_cart == 0){

                    $affiliatepress_product_id   = isset($affiliatepress_bookingpress_appointment_data['bookingpress_service_id']) ? intval($affiliatepress_bookingpress_appointment_data['bookingpress_service_id']) :0;
                    $affiliatepress_product_name   = isset($affiliatepress_bookingpress_appointment_data['bookingpress_service_name']) ? sanitize_text_field($affiliatepress_bookingpress_appointment_data['bookingpress_service_name']) : '';

                    $affiliatepress_bookingpress_product = array(
                        'product_id'=>$affiliatepress_product_id,
                        'source'=>$this->affiliatepress_integration_slug
                    );
                    
                    $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_bookingpress_product );

                    /* Verify if commissions are disabled for this product */
                    if($affiliatepress_product_disable){

                        return;
                    }
        
                    $affiliatepress_amount = isset($affiliatepress_bookingpress_appointment_data['bookingpress_service_price']) ? floatval($affiliatepress_bookingpress_appointment_data['bookingpress_service_price']) : 0;
        
                    $affiliatepress_staffmember_price = isset($affiliatepress_bookingpress_appointment_data['bookingpress_staff_member_price']) ? floatval($affiliatepress_bookingpress_appointment_data['bookingpress_staff_member_price']) : 0;
                    if($affiliatepress_staffmember_price > 0){
                        $affiliatepress_amount = $affiliatepress_staffmember_price;
                    }
        
                    $affiliatepress_quntity = 1;
        
                    /* Calculate Commission Amount */
                    $affiliatepress_args = array(
                       'origin'	          => $this->affiliatepress_integration_slug,
                       'type' 		      => $affiliatepress_commission_type,
                       'affiliate_id'     => $affiliatepress_affiliate_id,
                       'product_id'       => $affiliatepress_product_id,
                       'customer_id'      => $affiliatepress_customer_id,
                       'commission_basis' => 'per_product',
                       'order_id'         => $affiliatepress_payment_id,
                       'quntity'          => $affiliatepress_quntity, 
                    );
        
                    $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

                    $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;
        
                    $affiliatepress_order_referal_amount += $affiliatepress_amount;
        
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
                elseif ($affiliatepress_bookingpress_is_cart == 1) {
        
                    $affiliatepress_cart_order_items = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_bookingpress_appointment_bookings, '*', 'WHERE bookingpress_payment_id = %d', array($affiliatepress_payment_id), '', '', '', false, false,ARRAY_A);

                    foreach($affiliatepress_cart_order_items as $affiliatepress_cart_item){

                        $affiliatepress_product_id   = isset($affiliatepress_cart_item['bookingpress_service_id']) ? intval($affiliatepress_cart_item['bookingpress_service_id']) :0;
                        $affiliatepress_product_name   = isset($affiliatepress_cart_item['bookingpress_service_name']) ? sanitize_text_field($affiliatepress_cart_item['bookingpress_service_name']) : '';
                        
                        $affiliatepress_bookingpress_product = array(
                            'product_id'=>$affiliatepress_product_id,
                            'source'=>$this->affiliatepress_integration_slug
                        );
                        
                        $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_bookingpress_product );
    
                        /* Verify if commissions are disabled for this product */
                        if($affiliatepress_product_disable){
    
                            continue;
                        }

                        $affiliatepress_amount = isset($affiliatepress_cart_item['bookingpress_service_price']) ? floatval($affiliatepress_cart_item['bookingpress_service_price']) : 0;

                        $affiliatepress_staffmember_price = isset($affiliatepress_cart_item['bookingpress_staff_member_price']) ? floatval($affiliatepress_cart_item['bookingpress_staff_member_price']) : 0;

                        if($affiliatepress_staffmember_price > 0){
                            $affiliatepress_amount = $affiliatepress_staffmember_price;
                        }

                        $affiliatepress_quntity = 1;

                        /* Calculate Commission Amount */
                        $affiliatepress_args = array(
                            'origin'	       => $this->affiliatepress_integration_slug,
                            'type' 		       => $affiliatepress_commission_type,
                            'affiliate_id'     => $affiliatepress_affiliate_id,
                            'product_id'       => $affiliatepress_product_id,
                            'customer_id'      => $affiliatepress_customer_id,
                            'commission_basis' => 'per_product',
                            'order_id'         => $affiliatepress_payment_id,
                            'quntity'          => $affiliatepress_quntity, 
                        );

                        $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount, $affiliatepress_currency, $affiliatepress_args );

                        $affiliatepress_single_product_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                        $affiliatepress_commission_amount += $affiliatepress_single_product_commission_amount;

                        $affiliatepress_order_referal_amount += $affiliatepress_amount;

                        $affiliatepress_allow_products_commission[] = array(
                            'product_id'           => $affiliatepress_product_id,
                            'product_name'         => $affiliatepress_product_name,
                            'order_id'             => $affiliatepress_payment_id,
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
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_payment_id, $affiliatepress_bookingpress_appointment_data);

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
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_order_total_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')) // phpcs:ignore
            );

            $affiliatepress_tbl_bookingpress_payment_transactions = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'bookingpress_payment_transactions' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'bookingpress_appointment_bookings' contains table name and it's prepare properly using 'arm_payment_log' function

            $affiliatepress_bookingpress_paymnet_status = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_bookingpress_payment_transactions, 'bookingpress_payment_status', 'WHERE bookingpress_payment_log_id = %d', array($affiliatepress_payment_id), '', '', '', false, true,ARRAY_A);

            $affiliatepress_payment_status = isset($affiliatepress_bookingpress_paymnet_status['bookingpress_payment_status']) ? intval($affiliatepress_bookingpress_paymnet_status['bookingpress_payment_status']) : 0;

            if($affiliatepress_payment_status == 2){
                $affiliatepress_commission_data['ap_commission_status'] = 2;

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
            }elseif ($affiliatepress_payment_status == 1) {
                $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

                $affiliatepress_updated_commission_status = 1;

                if($affiliatepress_default_commission_status != "auto"){

                    $affiliatepress_updated_commission_status = $affiliatepress_default_commission_status;
                    $affiliatepress_commission_data['ap_commission_status'] = $affiliatepress_default_commission_status;
                }else{
                    $affiliatepress_commission_data['ap_commission_status'] = 1;
                }

                $affiliatepress_ap_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);

                if($affiliatepress_ap_commission_id == 0){

                    $affiliatepress_debug_log_msg = 'commission could not be inserted due to an unexpected error.';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                }
                else{

                    $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;

                    $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                    do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data );
                    $affiliatepress_debug_log_msg = sprintf( 'commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );

                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
                }
            }

        }
        
        /**
         * Function For add Approved Commission
         *
         * @param  array $affiliatepress_paymnet_updated_data
         * @return void
         */
        function affiliatepress_accept_pending_commission_bookingpress($affiliatepress_paymnet_updated_data){
            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking;

            $affiliatepress_payment_status = isset($affiliatepress_paymnet_updated_data['payment_status']) ? intval($affiliatepress_paymnet_updated_data['payment_status']) : 0;

            if($affiliatepress_payment_status == 1 || $affiliatepress_payment_status == 4){
                
                $affiliatepress_paymnet_id = isset($affiliatepress_paymnet_updated_data['payment_log_id']) ? intval($affiliatepress_paymnet_updated_data['payment_log_id']) : 0;

                $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();
    
                $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_paymnet_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');
    
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
                                $affiliatepress_debug_log_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
            
                                do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_updated_commission_status,2);
            
                                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
            
                            }
            
                        }
                    }
                }
            }
        }
        
        /**
         * Function For pending Commission add after status change
         *
         * @param  array $affiliatepress_paymnet_updated_data
         * @return void
         */
        function affiliatepress_pending_commission_add_after_status_chnage_bookingpress($affiliatepress_paymnet_updated_data){
            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;

            $affiliatepress_payment_status = isset($affiliatepress_paymnet_updated_data['payment_status']) ? intval($affiliatepress_paymnet_updated_data['payment_status']) : 0;

            if($affiliatepress_payment_status != 2){
                return;
            }

            $affiliatepress_paymnet_id = isset($affiliatepress_paymnet_updated_data['payment_log_id']) ? intval($affiliatepress_paymnet_updated_data['payment_log_id']) : 0;

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_paymnet_id, $this->affiliatepress_integration_slug);

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
        
        /**
         * Function For Check Plugin active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if ( is_plugin_active( 'bookingpress-appointment-booking-pro/bookingpress-appointment-booking-pro.php' )) {
                $affiliatepress_flag = true;
            }
            else{
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }

        /**
         * Function For Check integration settings enable active
         *
         * @return bool
         */
        function affiliatepress_bookingpress_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_bookingpress = $AffiliatePress->affiliatepress_get_settings('enable_bookingpress', 'integrations_settings');
            if($affiliatepress_enable_bookingpress != 'true'){
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }
    }
}
global $affiliatepress_bookingpress;
$affiliatepress_bookingpress = new affiliatepress_bookingpress();