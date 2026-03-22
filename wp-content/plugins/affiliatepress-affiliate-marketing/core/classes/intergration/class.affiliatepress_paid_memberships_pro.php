<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_paid_memberships_pro') ){
    
    class affiliatepress_paid_memberships_pro Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){     

            global $affiliatepress_is_paid_memberships_pro_active ;
            $affiliatepress_is_paid_memberships_pro_active = $this->affiliatepress_check_plugin_active();
            $this->affiliatepress_integration_slug = 'paid_memberships_pro';
            if($this->affiliatepress_paid_memberships_pro_add() && $affiliatepress_is_paid_memberships_pro_active){

                /**Add Pending Commission */
                add_action( 'pmpro_added_order', array( $this, 'affiliatepress_add_pending_commission' ), 10 );

                /**Add Approved Commission */
                add_action( 'pmpro_updated_order', array( $this, 'affiliatepress_commission_status_approve' ), 10 );

                /* Disable Commission Settings ADD */
                add_action( 'pmpro_membership_level_after_other_settings', array($this,'affiliatepress_membership_setting'));
                add_action( 'pmpro_save_membership_level', array($this, 'affiliatepress_save_membership_setting'));

                /**Validation */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

            }


            if($affiliatepress_is_paid_memberships_pro_active){
                /**Get Order Link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_paid_membrship_pro_link_order_func'),10,3); 

                /* Function for get source product  */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_source_product_func'),10,3);
            }
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
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order){

            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){                    

                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = sanitize_email($affiliatepress_order->Email);

                        if(empty($affiliatepress_billing_email)){
                            $affiliatepress_current_user = wp_get_current_user();
                            $affiliatepress_billing_email = $affiliatepress_current_user->user_email;
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
         *  Function for get level by name
         *
         * @param  string $affiliatepress_search_name
         * @return array
        */
        function affiliatepress_get_levels_by_name($affiliatepress_search_name){            
            $affiliatepress_all_levels = pmpro_getAllLevels();
            $affiliatepress_matched_levels = [];
            if(empty($affiliatepress_search_name)){
                return $affiliatepress_all_levels;
            }           
            foreach($affiliatepress_all_levels as $affiliatepress_level){
                if(stripos($affiliatepress_level->name, $affiliatepress_search_name) !== false){
                    $affiliatepress_matched_levels[] = $affiliatepress_level;
                }
            }        
            return $affiliatepress_matched_levels;
        }

        /**
         * Function for get source product for backend commission add/edit
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param  string $affiliatepress_ap_commission_source
         * @param  string $affiliatepress_search_product_str
         * @return array
        */
        function affiliatepress_get_source_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){

            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_existing_products_data = array();
                $affiliatepress_existing_product_data = array();
                $affiliatepress_all_level_list = $this->affiliatepress_get_levels_by_name($affiliatepress_search_product_str);                
                if(!empty($affiliatepress_all_level_list)){
                    foreach($affiliatepress_all_level_list as $affiliatepress_affiliate_level){
                        $affiliatepress_existing_product_data[] = array(
                            'value' => $affiliatepress_affiliate_level->id,
                            'label' => $affiliatepress_affiliate_level->name,
                        );    
                    }
                    $affiliatepress_existing_products_data[] = array(
                        'category'     => esc_html__('Select Source Plan', 'affiliatepress-affiliate-marketing'),
                        'product_data' => $affiliatepress_existing_product_data,
                    );                      
                }
                
                return $affiliatepress_existing_products_data;

            }

            return $affiliatepress_existing_source_product_data;

        }

        
        /**
         * Function For Paid Membership Pro get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_paid_membrship_pro_link_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){
                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href="'.admin_url("admin.php?page=pmpro-orders&order=".$affiliatepress_ap_commission_reference_id).'");"> '. $affiliatepress_ap_commission_reference_id .' </a>';                
                $affiliatepress_ap_commission_reference_id = $affiliatepress_ap_commission_order_link;
            }
            
            return $affiliatepress_ap_commission_reference_id;
        }

        
        /**
         * Function for update paid membership pro plugin settings
         *
         * @param  int $affiliatepress_edit_id
         * @return void
       */
        public function affiliatepress_save_membership_setting( $affiliatepress_edit_id ) {
            $affiliatepress_wpnonce = isset($_POST['affiliatepress_commission_nonce']) ? sanitize_text_field(wp_unslash($_POST['affiliatepress_commission_nonce'])) : '';// phpcs:ignore

            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_commission_nonce');
            if (! $affiliatepress_verify_nonce_flag ) {
                $affiliatepress_nonce_error = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                return $affiliatepress_nonce_error;
            }  

            $affiliatepress_disable_commission = (bool)isset($_POST['affiliatepress_disable_commission']);
            $affiliatepress_setting_data = array(                   
                'is_disabled' => $affiliatepress_disable_commission,
            );
            update_option("affiliatepress_pmp_settings_{$affiliatepress_edit_id}", $affiliatepress_setting_data);      
            
            do_action('affiliatepress_paid_memberships_pro_save_product_settings' , $affiliatepress_edit_id);
        }
        

        /**
         * Function for add paid membership disable settings
         *
         * @return void
        */
        public function affiliatepress_membership_setting() {
            
            $affiliatepress_edit_id = isset( $_REQUEST['edit'] ) ? intval( $_REQUEST['edit'] ) : 0;//phpcs:ignore
            if (!$affiliatepress_edit_id){
                return;
            }
            $affiliatepress_settings = get_option( "affiliatepress_pmp_settings_{$affiliatepress_edit_id}", array() );
            $affiliatepress_is_disabled = empty( $affiliatepress_settings['is_disabled'] ) ? false : true;
            ?>
                <div cla="affiliatepress-settings">
                    <?php 
                        wp_nonce_field( 'affiliatepress_commission_nonce', 'affiliatepress_commission_nonce' );
                    ?>
                    <h3 class="topborder"><?php echo  esc_html__( 'AffiliatePress Settings', 'affiliatepress-affiliate-marketing');?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row" valign="top">
                                <label for="affiliatepress_disable_commission"><?php echo esc_html__( 'Disable Commission', 'affiliatepress-affiliate-marketing');?>:</label>
                            </th>
                            <td><input id="affiliatepress_disable_commission" name="affiliatepress_disable_commission" type="checkbox" value="1" <?php echo ($affiliatepress_is_disabled)?'checked':''; ?> /> <label for="affiliatepress_disable_commission"><?php esc_html_e( 'Check to disable commission.', 'affiliatepress-affiliate-marketing');?></label></td>
                        </tr>    
                        <?php  do_action('affiliatepress_paid_memberships_pro_add_product_settings' , $affiliatepress_edit_id); ?>                  
                    </table>
                </div>
            <?php             
        }

        /**
         * Function for change commission status to approve
         *
         * @param  array $affiliatepress_order
         * @return void
        */
        function affiliatepress_commission_status_approve($affiliatepress_order){
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking, $AffiliatePress;
            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();
            $affiliatepress_order_id     = (isset($affiliatepress_order->id))?absint( $affiliatepress_order->id ):'';
            $affiliatepress_order_status = (isset($affiliatepress_order->status))?strtolower( $affiliatepress_order->status ):'';

            if ( 'success' === $affiliatepress_order_status ) {

                $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug,' AND ap_commission_status = 2');

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
                                $affiliatepress_debug_log_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
            
                                do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_updated_commission_status,2);
            
                                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                            }                                     
                        }
                    }
                }

            }

           if(in_array($affiliatepress_order_status,array( 'token', 'error', 'review', 'pending' ), true )){
                
                $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);

                if(!empty($affiliatepress_all_commission_data)){
                    foreach($affiliatepress_all_commission_data as $affiliatepress_commissition_data){

                        if(!empty($affiliatepress_commissition_data)){
                            $affiliatepress_ap_commission_status = (isset($affiliatepress_commissition_data['ap_commission_status']))? intval($affiliatepress_commissition_data['ap_commission_status']):0;
                            $affiliatepress_ap_commission_id     = (isset($affiliatepress_commissition_data['ap_commission_id']))?intval($affiliatepress_commissition_data['ap_commission_id']):0;  
        
                            if($affiliatepress_ap_commission_status == 4){
                                $affiliatepress_debug_log_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_ap_commission_id );
                                return;                        
                            }else{
                                if($affiliatepress_ap_commission_status != 2 && in_array($affiliatepress_order_status,array('token','error','review','pending'))){
        
                                    $affiliatepress_commission_data = array(
                                        'ap_commission_updated_date' => current_time( 'mysql', true ),
                                        'ap_commission_status' 		 => 2
                                    ); 
        
                                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
                                    $affiliatepress_debug_log_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_ap_commission_id );                               
        
                                    do_action('affiliatepress_after_commissions_status_change',$affiliatepress_ap_commission_id,$affiliatepress_ap_commission_status,2);
                    
                                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                    
        
                                }
                            }
        
                        }
                    }
                }
           }
            


        }

        
        /**
         * Function for add commission
         *
         * @param  array $affiliatepress_order
         * @return void
        */
        function affiliatepress_add_pending_commission($affiliatepress_order){

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id ;
            $affiliatepress_order_id = (isset($affiliatepress_order->id))?$affiliatepress_order->id:''; 

            if(!$affiliatepress_order_id){
                $affiliatepress_log_msg = "Empty Order ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Order ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;                
            }

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;
            
            if(empty($affiliatepress_affiliate_id)){
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            /* Get description */
            if ( isset( $affiliatepress_order->membership_name ) ) {                
                $affiliatepress_membership_name = $affiliatepress_order->membership_name;
            } else {      
                
                $affiliatepress_tbl_pmpro_membership_levels = $wpdb->pmpro_membership_levels;
                $affiliatepress_pmpro_membership_levels = $this->affiliatepress_tablename_prepare( $affiliatepress_tbl_pmpro_membership_levels ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_pmpro_membership_levels contains table name and it's prepare properly using 'arm_payment_log' function

                $affiliatepress_membership_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $wpdb->pmpro_membership_levels WHERE id = %d LIMIT 1", $affiliatepress_order->membership_id ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions is a table name. false alarm
            }
            
            $affiliatepress_membership_level  = isset( $affiliatepress_order->membership_id ) ? (int) $affiliatepress_order->membership_id : 0;
            $affiliatepress_amount = (isset($affiliatepress_order->subtotal))?floatval($affiliatepress_order->subtotal):0;

            $affiliatepress_commission_validation = array();
            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_order);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?$affiliatepress_commission_validation['variant']:'';
                if($affiliatepress_filter_variant == 'error'){

                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?$affiliatepress_commission_validation['msg']:'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }            

            $affiliatepress_customer_id = 0;
            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_commission_type = 'subscription';
            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_FirstName = (isset($affiliatepress_order->FirstName))?sanitize_text_field($affiliatepress_order->FirstName):'';
            $affiliatepress_LastName  = (isset($affiliatepress_order->LastName))?sanitize_text_field($affiliatepress_order->LastName):'';
            $affiliatepress_Email     = (isset($affiliatepress_order->Email))?sanitize_email($affiliatepress_order->Email):'';

            if(!empty($affiliatepress_Email)){                
                $affiliatepress_user_id = (isset($affiliatepress_order->user_id))?$affiliatepress_order->user_id:0;
                /* Add Commission Customer Here */
                $affiliatepress_customer_args = array(
                    'email'   	   => $affiliatepress_Email,
                    'user_id' 	   => $affiliatepress_user_id,
                    'first_name'   => $affiliatepress_FirstName,
                    'last_name'	   => $affiliatepress_LastName,
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
            }

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_membership_level,
                    'order_id'         => $affiliatepress_order_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount, '', $affiliatepress_args);
                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;
               
                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_membership_level,
                    'product_name'         => $affiliatepress_membership_name,
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );
                $affiliatepress_order_referal_amount = $affiliatepress_amount;                

            }else{

                $affiliatepress_paid_membership_pro_product = array(
                    'product_id'=> $affiliatepress_membership_level,
                    'source'=>$this->affiliatepress_integration_slug
                );

                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_paid_membership_pro_product );
    
                if($affiliatepress_product_disable){
                    return;
                }

                /* Calculate Commission Amount */
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_membership_level,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_order_id,
                );
                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, '', $affiliatepress_args );

                $affiliatepress_single_product_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_commission_amount += $affiliatepress_single_product_commission_amount;

                $affiliatepress_order_referal_amount += $affiliatepress_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_membership_level,
                    'product_name'         => $affiliatepress_membership_name,
                    'order_id'             => $affiliatepress_order_id,
                    'commission_amount'    => $affiliatepress_single_product_commission_amount,
                    'order_referal_amount' => $affiliatepress_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );


                $affiliatepress_commission_products_ids[] = $affiliatepress_membership_level;
                $affiliatepress_commission_products_name[] = $affiliatepress_membership_name;

            }


            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_order_id, $affiliatepress_order);

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
            
            $affiliatepress_ap_commission_status = 2;
            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();            
            $affiliatepress_order_status = (isset($affiliatepress_order->status))?strtolower( $affiliatepress_order->status ):'';
            if($affiliatepress_default_commission_status == "auto"){
                if('success' === $affiliatepress_order_status){
                    $affiliatepress_ap_commission_status = 1;
                }
            }

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => $affiliatepress_ap_commission_status,
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_order_referal_amount,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s',current_time('timestamp'))// phpcs:ignore
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
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_paid_memberships_pro_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_paid_memberships_pro = $AffiliatePress->affiliatepress_get_settings('enable_paid_memberships_pro', 'integrations_settings');
            if($affiliatepress_enable_paid_memberships_pro != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }

         /**
         * Function For Check Plugin Active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if(!function_exists('is_plugin_active')){
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if(is_plugin_active('paid-memberships-pro/paid-memberships-pro.php')){
                $affiliatepress_flag = true;
            }else{
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }        

    }
}

global $affiliatepress_paid_memberships_pro;
$affiliatepress_paid_memberships_pro = new affiliatepress_paid_memberships_pro();