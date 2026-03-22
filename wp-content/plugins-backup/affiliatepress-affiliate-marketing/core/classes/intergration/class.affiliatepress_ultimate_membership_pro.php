<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_ultimate_membership_pro') ){
    
    class affiliatepress_ultimate_membership_pro Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){

            global $affiliatepress_is_ultimate_membership_pro_active ;
            $affiliatepress_is_ultimate_membership_pro_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'ultimate_membership_pro';
            if($this->affiliatepress_ultimate_membership_pro_commission_add() && $affiliatepress_is_ultimate_membership_pro_active){

                /**Add pending COmmission */
                add_action( 'ihc_action_before_after_order', array( $this, 'affiliatepress_pending_refral_add_ump' ), 1, 1 );

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_ump_validation_func'),15,5);

                /**Add Approved Commission */
                add_action('ump_payment_check', array($this, 'affiliatepress_accept_pending_commission_ump'), 1, 2);

                /**Add Pending Commission On status change */
                add_action('ump_payment_check', array($this, 'affiliatepress_add_pending_commission_on_status_change_ump'), 1, 2);

                /**add settings  */
                add_action( 'ihc_filter_admin_section_edit_membership_after_membership_plan', array($this,'affiliatepress_custom_membership_add_section'), 10, 1 );
            }

            if($affiliatepress_is_ultimate_membership_pro_active){
                /**Get Order Links */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_ump_link_order_func'),10,3); 

                /**Add for get all products */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_ump_product_func'),10,3); 
            }
        }
        
        /**
         * Function For affiliate settings add
         *
         * @param  array $affiliatepress_level_data
         * @return void
         */
        function affiliatepress_custom_membership_add_section( $affiliatepress_level_data)
        {
            $affiliatepress_disable_commission = isset($affiliatepress_level_data['affiliatepress_ump_disable_commission']) ? $affiliatepress_level_data['affiliatepress_ump_disable_commission'] : '';
            ?>
            <div class="form-field inside ihc-plan-details-wrapper">
                <div class="iump-form-line iump-no-border">
					<h2><?php esc_html_e('AffiliatePress Settings', 'affiliatepress-affiliate-marketing');?></h2>
                </div>
				<div class="iump-form-line iump-no-border">
				    <h4><?php esc_html_e('AffiliatePress Disable Commission', 'affiliatepress-affiliate-marketing');?></h4>
                    <p><?php esc_html_e('Enable this option to remove this membership from the commission.', 'affiliatepress-affiliate-marketing');?></p>
				    <label class="iump_label_shiwtch ihc-switch-button-margin">
						<?php $affiliatepress_checked = ($affiliatepress_disable_commission == 1) ? 'checked' : '';?>
						<input type="checkbox" class="iump-switch" onClick="iumpCheckAndH(this, '#affiliatepress_ump_disable_commission');" <?php echo esc_attr($affiliatepress_checked);?> />
						<div class="switch ihc-display-inline"></div>
					</label>
					<input type="hidden" value="<?php echo esc_attr($affiliatepress_disable_commission);?>" name="affiliatepress_ump_disable_commission" id="affiliatepress_ump_disable_commission" />
				</div>
                <?php do_action('affiliatepress_ultimate_memberships_pro_add_product_settings' , $affiliatepress_level_data); ?>
            </div>
            <?php
        }

        /**
         * Function For Ultimate Membership pro get order link
         *
         * @param  integer $affiliatepress_commission_reference_id
         * @param  string $affiliatepress_commission_source
         * @return string
         */
        function affiliatepress_get_ump_link_order_func($affiliatepress_commission_reference_id, $affiliatepress_commission_source){
            
            if($affiliatepress_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_url = admin_url("admin.php?page=ihc_manage&tab=order-edit&order_id=".$affiliatepress_commission_reference_id);

                $affiliatepress_commission_order_link = '<a target="_blank" class="ap-refrance-link" href=" '.esc_url( $affiliatepress_url ). ' "> '. $affiliatepress_commission_reference_id .' </a>';

                $affiliatepress_commission_reference_id = $affiliatepress_commission_order_link;
            }
            
            return $affiliatepress_commission_reference_id;
        }
        
        /**
         * Function for get source product for backend commission add/edit
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param  string $affiliatepress_ap_commission_source
         * @param  string $affiliatepress_search_product_str
         * @return array
        */
        function affiliatepress_get_ump_product_func($affiliatepress_existing_source_product_data, $affiliatepress_commission_source, $affiliatepress_search_product_str){

            global $wpdb;
        
            if($affiliatepress_commission_source == $this->affiliatepress_integration_slug){
        
                $affiliatepress_existing_products_data = array();
                $affiliatepress_tbl_ihc_memberships_pro = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'ihc_memberships' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'ihc_memberships' contains table name and it's prepare properly using 'arm_payment_log' function
                
                $affiliatepress_results = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM $affiliatepress_tbl_ihc_memberships_pro WHERE label LIKE %s",'%' . $wpdb->esc_like($affiliatepress_search_product_str) . '%'),ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ihc_memberships_pro is a table name. false alarm
    
                $affiliatepress_plan_ids = array_column($affiliatepress_results, 'id');

                if($affiliatepress_plan_ids){
                    foreach ($affiliatepress_plan_ids as $affiliatepress_plan_id) {
                        
                        $affiliatepress_results = $wpdb->get_results($wpdb->prepare("SELECT label FROM $affiliatepress_tbl_ihc_memberships_pro WHERE id = %d",$affiliatepress_plan_id),ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ihc_memberships_pro is a table name. false alarm

                        $affiliatepress_plan_name = !empty($affiliatepress_results) ? $affiliatepress_results[0]['label'] : '';

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
         * Function For Check Plugin Active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if ( !is_plugin_active( 'indeed-membership-pro/indeed-membership-pro.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
       /**
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_ultimate_membership_pro_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;            
            $affiliatepress_enable_ultimate_membership_pro = $AffiliatePress->affiliatepress_get_settings('enable_ultimate_membership_pro', 'integrations_settings');
            if($affiliatepress_enable_ultimate_membership_pro != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
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
        function affiliatepress_commission_ump_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = !empty($affiliatepress_order['customer_email']) ? $affiliatepress_order['customer_email'] : '';              
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
         * Function For Add pending commission
         *
         * @param  array $affiliatepress_order_data
         * @return void
         */
        function affiliatepress_pending_refral_add_ump($affiliatepress_order_data)
        {
            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_order_id = !empty($affiliatepress_order_data['order_id']) ? intval($affiliatepress_order_data['order_id']) : 0;

            $affiliatepress_plan_id = !empty($affiliatepress_order_data['lid']) ? intval($affiliatepress_order_data['lid']) : 0;

            /* Get and check to see if referrer exists */
            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit(); 

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id):0;

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

            $affiliatepress_customer_args = array(
                'email'   	   => !empty($affiliatepress_order_data['customer_email']) ? sanitize_email($affiliatepress_order_data['customer_email']) : '',
                'user_id' 	   => !empty($affiliatepress_order_data['uid']) ? intval($affiliatepress_order_data['uid']) : 0,
                'first_name'   => !empty($affiliatepress_order_data['customer_name']) ? sanitize_text_field($affiliatepress_order_data['customer_name']) : '',
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

            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('ultimate_membership_pro_exclude_taxes', 'integrations_settings'); 

            $affiliatepress_commission_type = 'subscription';
            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_currecy = !empty($affiliatepress_order_data['currency']) ? sanitize_text_field($affiliatepress_order_data['currency']) : '';
            $affiliatepress_plan_name = !empty($affiliatepress_order_data['level_label']) ? sanitize_text_field($affiliatepress_order_data['level_label']) : '';
            $affiliatepress_order_amount =!empty($affiliatepress_order_data['amount']) ? floatval($affiliatepress_order_data['amount']) : 0;

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_total_price = $affiliatepress_order_amount;
                $plan_price = !empty($affiliatepress_order_data['base_price']) ? floatval($affiliatepress_order_data['base_price']) : 0;

                $affiliatepress_tax_amount = $affiliatepress_total_price - $plan_price;

                $affiliatepress_amount = 0;

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
                    'order_id'         => $affiliatepress_order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currecy, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_plan_id,
                    'product_name'         => $affiliatepress_plan_name,
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
                $affiliatepress_ump_product = array(
                    'product_id'=> $affiliatepress_plan_id,
                    'source'=>$this->affiliatepress_integration_slug
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_ump_product );
    
                if($affiliatepress_product_disable){
                    return;
                }

                $affiliatepress_amount = !empty($affiliatepress_order_data['base_price']) ? floatval($affiliatepress_order_data['base_price']) : 0;

                /* Include Tax */
                if($affiliatepress_exclude_taxes == 'false'){
                    $affiliatepress_amount = !empty($affiliatepress_order_data['amount']) ? $affiliatepress_order_data['amount'] : '';
                }

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_plan_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_order_id,
                );

                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_currecy, $affiliatepress_args );

                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_plan_id,
                    'product_name'         => $affiliatepress_plan_name,
                    'order_id'             => $affiliatepress_order_id,
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
                $affiliatepress_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();

            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_order_id, $affiliatepress_order_data);

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
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_order_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp')) // phpcs:ignore
            );

            /* Insert The Commission */
            $affiliatepress_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
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
         * Function For add approved commission
         *
         * @return void
         */
        function affiliatepress_accept_pending_commission_ump( $affiliatepress_order_id , $affiliatepress_type){

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;

            if($affiliatepress_order_id == 0){
                return;
            }

            require_once IHC_PATH . 'classes/Orders.class.php';

			$affiliatepress_object = new Ump\Orders();

			$affiliatepress_data = $affiliatepress_object->get_data($affiliatepress_order_id);

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            if ($affiliatepress_data['status'] !='Completed'){
                return ;
            }

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');

            if(!empty($affiliatepress_all_commission_data)){

                foreach($affiliatepress_all_commission_data as $affiliatepress_single_commission_data){

                    $affiliatepress_commission_id = (isset($affiliatepress_single_commission_data['ap_commission_id']))?intval($affiliatepress_single_commission_data['ap_commission_id']):0;

                    $affiliatepress_commission_status = (isset($affiliatepress_single_commission_data['ap_commission_status']))?intval($affiliatepress_single_commission_data['ap_commission_status']):0;

                    if($affiliatepress_commission_status == 4){
                        $affiliatepress_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_commission_id );
        
                        do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Already paid ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
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
        
         /**
         * Function For Add pending commission after status change
         *
         * @param  int $affiliatepress_order_id
         * @param  string $affiliatepress_type
         * @return void
         */
        function affiliatepress_add_pending_commission_on_status_change_ump($affiliatepress_order_id , $affiliatepress_type)
        {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking ,$AffiliatePress;

            if($affiliatepress_order_id == 0)
            {
                return;
            }

            require_once IHC_PATH . 'classes/Orders.class.php';

			$affiliatepress_object = new Ump\Orders();

			$affiliatepress_data = $affiliatepress_object->get_data($affiliatepress_order_id);

            if ($affiliatepress_data['status'] !='pending'){
                return ;
            }

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);
            if(!empty($affiliatepress_all_commission_data)){
                foreach($affiliatepress_all_commission_data as $affiliatepress_commissition_data){

                    if(!empty($affiliatepress_commissition_data)){
                        $affiliatepress_commission_status = (isset($affiliatepress_commissition_data['ap_commission_status']))?intval($affiliatepress_commissition_data['ap_commission_status']):0;
                        $affiliatepress_commission_id     = (isset($affiliatepress_commissition_data['ap_commission_id']))?intval($affiliatepress_commissition_data['ap_commission_id']):0;
                        if($affiliatepress_commission_status == 4){
                            $affiliatepress_msg = sprintf( 'Commission #%s could not be pending because it was already paid.', $affiliatepress_commission_id );
                            return;
                        }
                        if($affiliatepress_commission_id != 0){
        
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => 2
                            );
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_commission_id ));
                            $affiliatepress_msg = sprintf( 'Commission #%s successfully marked as pending, after order #%s status change', $affiliatepress_commission_id, $affiliatepress_order_id );
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Reject ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
        
                        }
                    }
                }
            }
          
        }
    }
}

global $affiliatepress_ultimate_membership_pro;
$affiliatepress_ultimate_membership_pro = new affiliatepress_ultimate_membership_pro();