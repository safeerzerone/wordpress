<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_wp_simple_pay') ){
    
    class affiliatepress_wp_simple_pay Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){

            global $affiliatepress_is_wp_simple_pay_active;
            $affiliatepress_is_wp_simple_pay_active = ($this->affiliatepress_check_plugin_active() || $this->affiliatepress_check_pro_plugin_active())?true:false;
            $this->affiliatepress_integration_slug = 'wp_simple_pay';

            if($this->affiliatepress_wp_simple_pay_add() && $affiliatepress_is_wp_simple_pay_active){

                if($this->affiliatepress_check_pro_plugin_active()){

                    add_filter('simpay_get_subscription_args_from_payment_form_request',array($this,'affiliatepress_track_commission_wpsimplepay_pro'));
                    add_filter('simpay_get_paymentintent_args_from_payment_form_request',array($this,'affiliatepress_track_commission_wpsimplepay_pro'));
                    add_filter( 'simpay_get_session_args_from_payment_form_request', array($this, 'affiliatepress_track_commission_wpsimplepay_pro') );

                    add_action('simpay_webhook_checkout_session_completed', array($this, 'affiliatepress_commission_stripe_checkout'), 10);
                    add_action('simpay_webhook_subscription_created', array($this, 'affiliatepress_add_pending_commission'), 10, 2);        
                    add_action('simpay_webhook_payment_intent_succeeded', array($this, 'affiliatepress_add_pending_commission'), 10, 2);                

                }else{

                    /* Add For Light Version */
                    add_filter('simpay_get_session_args_from_payment_form_request',array( $this,'affiliatepress_track_commission_wpsimplepay_lite'),10,5);			    
                    // add_action('simpay_payment_receipt_viewed',array( $this, 'affiliatepress_commission_wpsimplepay_lite_add' ));
                    add_action('_simpay_payment_confirmation',array( $this, 'affiliatepress_commission_wpsimplepay_lite_add' ) ,10 ,3);

                }

                add_filter( 'simpay_form_settings_meta_tabs_li', array($this , 'affiliatepress_add_settings_tab_wpsimplepay_lite'), 10, 2 );

                add_action('simpay_form_settings_meta_options_panel' , array($this , 'affiliatepress_add_settings_tab_content_wpsimplepay_lite') ,10,1);

                add_action('save_post',array($this, 'affiliatepress_save_simple_pay_metabox_data'), 19,1);

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),20,5);
                

            }

            if($affiliatepress_is_wp_simple_pay_active){
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_wp_simple_pay_link_order_func'),10,2); 
            }
        }
        
        /**
         * Function For Affiliate settings tab add
         *
         * @param  array $affiliatepress_tabs
         * @param  int $post_id
         * @return array
         */
        function affiliatepress_add_settings_tab_wpsimplepay_lite( $affiliatepress_tabs, $post_id ) {
            // Add the custom tab
            $affiliatepress_tabs['affiliatepress'] = array(
                'label'  => esc_html__( 'AffiliatePress Setting', 'affiliatepress-affiliate-marketing' ),
                'target' => 'affiliatepress-settings-panel',
                'icon'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12,16c2.206,0,4-1.794,4-4s-1.794-4-4-4s-4,1.794-4,4S9.794,16,12,16z M12,10c1.084,0,2,0.916,2,2s-0.916,2-2,2 s-2-0.916-2-2S10.916,10,12,10z"></path><path d="M2.845,16.136l1,1.73c0.531,0.917,1.809,1.261,2.73,0.73l0.529-0.306C7.686,18.747,8.325,19.122,9,19.402V20 c0,1.103,0.897,2,2,2h2c1.103,0,2-0.897,2-2v-0.598c0.675-0.28,1.314-0.655,1.896-1.111l0.529,0.306 c0.923,0.53,2.198,0.188,2.731-0.731l0.999-1.729c0.552-0.955,0.224-2.181-0.731-2.732l-0.505-0.292C19.973,12.742,20,12.371,20,12 s-0.027-0.743-0.081-1.111l0.505-0.292c0.955-0.552,1.283-1.777,0.731-2.732l-0.999-1.729c-0.531-0.92-1.808-1.265-2.731-0.732 l-0.529,0.306C16.314,5.253,15.675,4.878,15,4.598V4c0-1.103-0.897-2-2-2h-2C9.897,2,9,2.897,9,4v0.598 c-0.675,0.28-1.314,0.655-1.896,1.111L6.575,5.403c-0.924-0.531-2.2-0.187-2.731,0.732L2.845,7.864 c-0.552,0.955-0.224,2.181,0.731,2.732l0.505,0.292C4.027,11.257,4,11.629,4,12s0.027,0.742,0.081,1.111l-0.505,0.292 C2.621,13.955,2.293,15.181,2.845,16.136z M6.171,13.378C6.058,12.925,6,12.461,6,12c0-0.462,0.058-0.926,0.17-1.378 c0.108-0.433-0.083-0.885-0.47-1.108L4.577,8.864l0.998-1.729L6.72,7.797c0.384,0.221,0.867,0.165,1.188-0.142 c0.683-0.647,1.507-1.131,2.384-1.399C10.713,6.128,11,5.739,11,5.3V4h2v1.3c0,0.439,0.287,0.828,0.708,0.956 c0.877,0.269,1.701,0.752,2.384,1.399c0.321,0.307,0.806,0.362,1.188,0.142l1.144-0.661l1,1.729L18.3,9.514 c-0.387,0.224-0.578,0.676-0.47,1.108C17.942,11.074,18,11.538,18,12c0,0.461-0.058,0.925-0.171,1.378 c-0.107,0.433,0.084,0.885,0.471,1.108l1.123,0.649l-0.998,1.729l-1.145-0.661c-0.383-0.221-0.867-0.166-1.188,0.142 c-0.683,0.647-1.507,1.131-2.384,1.399C13.287,17.872,13,18.261,13,18.7l0.002,1.3H11v-1.3c0-0.439-0.287-0.828-0.708-0.956 c-0.877-0.269-1.701-0.752-2.384-1.399c-0.19-0.182-0.438-0.275-0.688-0.275c-0.172,0-0.344,0.044-0.5,0.134l-1.144,0.662l-1-1.729 L5.7,14.486C6.087,14.263,6.278,13.811,6.171,13.378z"></path></svg>',
            );
        
            return $affiliatepress_tabs;
        }

                
        /**
         * Function For Affiliate settings add
         *
         * @param  int $post_id
         * @return void
         */
        function affiliatepress_add_settings_tab_content_wpsimplepay_lite( $post_id ) {

            $panel_classes = array(
                'simpay-panel',
                'simpay-panel-hidden',
            );

            wp_nonce_field( 'affiliatepress_commission_nonce_wp_simple_pay', 'affiliatepress_commission_nonce_wp_simple_pay' );
            ?>
                <div id="affiliatepress-settings-panel" class="simpay-panel-hidden <?php echo esc_attr( implode( ' ', $panel_classes ) ); ?>">
                    <table>
                        <tbody class="simpay-panel-section">
                            <tr class="simpay-panel-field">
                                <th>
                                    <strong>
                                        <?php esc_html_e( 'AffiliatePress Enable Settings', 'affiliatepress-affiliate-marketing' ); ?>
                                    </strong>
                                </th>
                                <td style="border-bottom: 0;">
                                    <?php
                                    $affiliatepress_commission_disable = get_post_meta( $post_id, 'affiliatepress_commission_disable_simple_pay', true );
                                    $affiliatepress_commission_disable = $affiliatepress_commission_disable === 'yes' ? 'yes' : 'no';
                                    ?>

                                    <label for="affiliatepress_commission_disable_simple_pay" class="simpay-field-bool">
                                        <input name="affiliatepress_commission_disable_simple_pay"type="checkbox" id="affiliatepress_commission_disable_simple_pay" class="simpay-field simpay-field-checkbox simpay-field-checkboxes" value="yes"<?php checked( 'yes', $affiliatepress_commission_disable ); ?>/>
                                        <b><?php esc_html_e( 'Enable AffiliatePress Commission', 'affiliatepress-affiliate-marketing' );?> </b>
                                        <?php esc_html_e('(Allow this form to generate Commission.)','affiliatepress-affiliate-marketing'); ?>
                                    </label>
                                </td>
                            </tr>
                            <?php do_action('affiliatepress_wp_simple_pay_add_product_settings' , $post_id); ?>
                        </tbody>
                    </table>
                </div>
            <?php
        }
        
        /**
         * Function For Affiliate Settings save
         *
         * @param  int $post_id
         * @return void
         */
        function affiliatepress_save_simple_pay_metabox_data($post_id) {

            if (!$post_id || empty($_POST['affiliatepress_commission_nonce_wp_simple_pay'])) {
                return;
            }
            if (wp_verify_nonce( $_POST['affiliatepress_commission_nonce_wp_simple_pay'], 'affiliatepress_commission_nonce_wp_simple_pay' )){   // phpcs:ignore
                $affiliatepress_commission_disable_simple_pay = !empty($_POST['affiliatepress_commission_disable_simple_pay']) ? sanitize_text_field( $_POST['affiliatepress_commission_disable_simple_pay'] ) : 'no';  //phpcs:ignore

                update_post_meta( $post_id, 'affiliatepress_commission_disable_simple_pay', $affiliatepress_commission_disable_simple_pay );
    
                do_action('affiliatepress_wp_simple_pay_settings_save' , $post_id);
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
        function affiliatepress_commission_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_payment_info){
            if($affiliatepress_source == $this->affiliatepress_integration_slug){
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = (isset($affiliatepress_payment_info->customer->email))?sanitize_email($affiliatepress_payment_info->customer->email):'';     
                        if(empty($affiliatepress_billing_email)){
                            $affiliatepress_billing_email = (isset($affiliatepress_payment_info->receipt_email))?$affiliatepress_payment_info->receipt_email:'';     
                        }                    
                        

                        if(!empty($affiliatepress_billing_email) && $AffiliatePress->affiliatepress_affiliate_has_email( $affiliatepress_affiliate_id, $affiliatepress_billing_email ) ) {                   
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
         * Function for add stripe checkout record 
         *
         * @param  array $affiliatepress_event_data
         * @return void
        */
        public function affiliatepress_commission_stripe_checkout( $affiliatepress_event_data ) {
            return $this->affiliatepress_add_pending_commission( $affiliatepress_event_data, $affiliatepress_event_data->data->object );
        }        
                        
        /**
         * Function for track commission in pro
         *
         * @param  array $affiliatepress_object_args
         * @return void
        */
        public function affiliatepress_track_commission_wpsimplepay_pro( $affiliatepress_object_args ) {
    
            global $AffiliatePress,$affiliatepress_tracking;
            
            $affiliatepress_affiliate_id = intval($affiliatepress_tracking->affiliatepress_get_referral_affiliate());
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit(); 

            if($affiliatepress_affiliate_id == 0){
                return $affiliatepress_object_args;
            }
            $affiliatepress_object_args['metadata']['affiliatepress_visit_id'] = $affiliatepress_visit_id;            
            $affiliatepress_object_args['metadata']['affiliatepress_affiliate_id'] = $affiliatepress_affiliate_id;

            return $affiliatepress_object_args;
        }

        /**
         * Function for check light version active
         *
         * @param  array $affiliatepress_payment_data
         * @return void
        */
        public function affiliatepress_commission_wpsimplepay_lite_add( $affiliatepress_payment_data ,$affiliatepress_paymnet_form ,$afiliatepress_simple_pay_get_data){
            global $AffiliatePress;
            $affiliatepress_payment_info = current( $affiliatepress_payment_data['paymentintents'] );
            $this->affiliatepress_add_pending_commission( null, $affiliatepress_payment_info );
        }

        
        /**
         * Function for add pending commission
         *
         * @param  array $affiliatepress_event
         * @param  array $affiliatepress_payment_info
         * @return void
        */
        public function affiliatepress_add_pending_commission( $affiliatepress_event, $affiliatepress_payment_info ) {

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_affiliate_id = isset($affiliatepress_payment_info->metadata->affiliatepress_affiliate_id)?$affiliatepress_payment_info->metadata->affiliatepress_affiliate_id:"";
            $affiliatepress_visit_id = isset($affiliatepress_payment_info->metadata->affiliatepress_visit_id)?intval($affiliatepress_payment_info->metadata->affiliatepress_visit_id):0;
            $affiliatepress_customer_email = (isset($affiliatepress_payment_info->customer->email))?$affiliatepress_payment_info->customer->email:'';
            $affiliatepress_payment_id = (isset($affiliatepress_payment_info->id))?$affiliatepress_payment_info->id:'';
            $affiliatepress_form_id     = (isset($affiliatepress_payment_info->metadata->simpay_form_id))?$affiliatepress_payment_info->metadata->simpay_form_id:'';
            $affiliatepress_status      = (isset($affiliatepress_payment_info->status))?$affiliatepress_payment_info->status:'';
        
            if(empty($affiliatepress_customer_email)){
                $affiliatepress_customer_email = (isset($affiliatepress_payment_info->receipt_email))?$affiliatepress_payment_info->receipt_email:'';     
            }            

            if(empty($affiliatepress_affiliate_id)){
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_amount = 0;
            $affiliatepress_product_detail  = '';
            switch($affiliatepress_payment_info->object){
                case 'subscription':
                    $affiliatepress_invoice = $affiliatepress_event->data->object;
                    $affiliatepress_amount           = $affiliatepress_invoice->amount_paid;
                    $affiliatepress_product_detail   = $affiliatepress_payment_info->plan->nickname;
                    $affiliatepress_currency         = $affiliatepress_invoice->currency;
                    $affiliatepress_mode             = $affiliatepress_invoice->livemode;                    
                    break;
                case 'payment_intent':    
                    $affiliatepress_amount           = $affiliatepress_payment_info->amount_received;
                    $affiliatepress_product_detail   = $affiliatepress_payment_info->description;
                    $affiliatepress_currency         = $affiliatepress_payment_info->currency;
                    $affiliatepress_mode             = $affiliatepress_payment_info->livemode;
                    break;
                case 'checkout.session':                    
                    $affiliatepress_amount           = $affiliatepress_payment_info->amount_total;
                    $affiliatepress_product_detail   = $affiliatepress_payment_info->metadata->item_description;
                    $affiliatepress_currency         = $affiliatepress_payment_info->currency;
                    $affiliatepress_mode             = $affiliatepress_payment_info->livemode;
                    break;
            }

            $affiliatepress_amount = round($affiliatepress_amount/100, 2);

            if(empty($affiliatepress_product_detail)){                
                if($affiliatepress_form_id){
                    $affiliatepress_product_detail = simpay_get_filtered( 'item_description', simpay_get_saved_meta( $affiliatepress_form_id, '_item_description' ), $affiliatepress_form_id );
                    if ( empty( $affiliatepress_product_detail ) ) {
                        $affiliatepress_product_detail = get_the_title( $affiliatepress_form_id );
                    }
                }
            }            
            
            $affiliatepress_commission_validation = array();
            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_payment_id, $affiliatepress_payment_info);

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
            $affiliatepress_commission_type = 'sale';
            $affiliatepress_order_referal_amount = $affiliatepress_amount;


            if(!empty($affiliatepress_customer_email)){                
                $affiliatepress_user_id = 0;
                /* Add Commission Customer Here */
                $affiliatepress_customer_args = array(
                    'email'   	   => $affiliatepress_customer_email,
                    'user_id' 	   => $affiliatepress_user_id,
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
            }

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){

                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_form_id,
                    'order_id'         => $affiliatepress_payment_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );

                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount, '', $affiliatepress_args);
                $affiliatepress_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0; 
                
                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_form_id,
                    'product_name'         => $affiliatepress_product_detail,
                    'order_id'             => $affiliatepress_payment_id,
                    'commission_amount'    => $affiliatepress_commission_amount,
                    'order_referal_amount' => 0,
                    'commission_basis'     => 'per_order',
                    'discount_val'         => ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        => ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),                    
                );

                $affiliatepress_order_referal_amount = $affiliatepress_amount;                

            }else{

                $affiliatepress_wp_simple_pay_product = array(
                    'product_id'=> $affiliatepress_form_id,
                    'source'=>$this->affiliatepress_integration_slug,
                );
    
                $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_wp_simple_pay_product );
    
                if($affiliatepress_product_disable){
                    return;
                }

                /* Calculate Commission Amount */
                $affiliatepress_args = array(
                    'origin'	       => $this->affiliatepress_integration_slug,
                    'type' 		       => $affiliatepress_commission_type,
                    'affiliate_id'     => $affiliatepress_affiliate_id,
                    'product_id'       => $affiliatepress_form_id,
                    'customer_id'      => $affiliatepress_customer_id,
                    'commission_basis' => 'per_product',
                    'order_id'         => $affiliatepress_payment_id,
                );
                $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, '', $affiliatepress_args );

                $affiliatepress_single_product_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                $affiliatepress_commission_amount = $affiliatepress_single_product_commission_amount;

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

                $affiliatepress_allow_products_commission[] = array(
                    'product_id'           => $affiliatepress_form_id,
                    'product_name'         => $affiliatepress_product_detail,
                    'order_id'             => $affiliatepress_payment_id,
                    'commission_amount'    => $affiliatepress_single_product_commission_amount,
                    'order_referal_amount' => $affiliatepress_amount,
                    'commission_basis'     => 'per_product',
                    'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                    'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                );
                $affiliatepress_commission_products_ids[] = $affiliatepress_form_id;
                $affiliatepress_commission_products_name[] = $affiliatepress_product_detail;

            }    
            
            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();
            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_payment_id, $affiliatepress_payment_info);

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
            
            $affiliatepress_commission_status = 2;
            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();            
            $affiliatepress_status = (!empty($affiliatepress_status))?strtolower( $affiliatepress_status ):'';
            if($affiliatepress_default_commission_status == "auto"){
                if('succeeded' === $affiliatepress_status){
                    $affiliatepress_commission_status = 1;
                }
            }

            /* Prepare commission data */
            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_type,
                'ap_commission_status'	         => $affiliatepress_commission_status,
                'ap_commission_reference_id'     => $affiliatepress_payment_id,
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
         * Function for track commission in WP Simple Pay Lite
         *
         * @param  array $affiliatepress_object_args
         * @return void
        */
        public function affiliatepress_track_commission_wpsimplepay_lite( $affiliatepress_object_args, $affiliatepress_form, $affiliatepress_from_data, $affiliatepress_form_values, $affiliatepress_customer_id ){

            global $AffiliatePress,$affiliatepress_tracking;            
            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();

            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id):0;
            if($affiliatepress_affiliate_id == 0){
                return $affiliatepress_object_args;
            }            
            $affiliatepress_object_args['payment_intent_data']['metadata']['affiliatepress_visit_id'] = $affiliatepress_visit_id;            
            $affiliatepress_object_args['payment_intent_data']['metadata']['affiliatepress_affiliate_id'] = $affiliatepress_affiliate_id;

            return $affiliatepress_object_args;

        }


        /**
         * Set Simple Pay Link
         *
         * @param  int $affiliatepress_commission_reference_id
         * @param  string $affiliatepress_commission_source
         * @return string
        */
        function affiliatepress_get_wp_simple_pay_link_order_func($affiliatepress_commission_reference_id, $affiliatepress_commission_source){            
            if($affiliatepress_commission_source == $this->affiliatepress_integration_slug){
                $affiliatepress_stripe_endpoint = false !== strpos($affiliatepress_commission_reference_id,'sub_')?'subscriptions':'payments';
                $affiliatepress_url = 'https://dashboard.stripe.com/' . $affiliatepress_stripe_endpoint  . '/' . $affiliatepress_commission_reference_id;                
                $affiliatepress_commission_order_link = '<a target="_blank" class="ap-refrance-link" href="'. esc_url( $affiliatepress_url ).'");"> '. $affiliatepress_commission_reference_id .' </a>';                
                $affiliatepress_commission_reference_id = $affiliatepress_commission_order_link;
            }            
            return $affiliatepress_commission_reference_id;
        }        

        


       /**
         * Function For Integration Settings Check
         *
         * @return bool
         */
        function affiliatepress_wp_simple_pay_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_wp_simple_pay = $AffiliatePress->affiliatepress_get_settings('enable_wp_simple_pay', 'integrations_settings');
            if($affiliatepress_enable_wp_simple_pay != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }

        /**
         * Function For Check Plugin Active
         *
         * @return bool
         */
        function affiliatepress_check_pro_plugin_active(){
            $affiliatepress_flag = true;           
            if(class_exists('SimplePay\Pro\SimplePayPro')){
                $affiliatepress_flag = true;                
            }else{
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        } 

        /**
         * check_plugin_active
         *
         * @return void
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if(!function_exists('is_plugin_active')){
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if(is_plugin_active('stripe/stripe-checkout.php')){
                $affiliatepress_flag = true;
            }else{
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }        

    }
}

global $affiliatepress_wp_simple_pay;
$affiliatepress_wp_simple_pay = new affiliatepress_wp_simple_pay();