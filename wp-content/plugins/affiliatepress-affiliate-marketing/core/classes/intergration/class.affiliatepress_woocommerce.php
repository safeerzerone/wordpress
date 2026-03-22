<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_woocommerce') ){
    
    class affiliatepress_woocommerce Extends AffiliatePress_Core{
        
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){            

            global $affiliatepress_is_woocommerce_active ;
            $affiliatepress_is_woocommerce_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'woocommerce';
            if($this->affiliatepress_woocommerce_commission_add() && $affiliatepress_is_woocommerce_active){

                /* Insert a new pending commission */
                add_action( 'woocommerce_checkout_update_order_meta', array($this,'affiliatepress_insert_pending_commission_from_woocommerce'), 10, 1 );

                if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '6.4.0', '>=' ) ) {
                    add_action( 'woocommerce_store_api_checkout_order_processed', array($this,'affiliatepress_insert_pending_commission_from_woocommerce') );
                } else {
                    add_action( 'woocommerce_blocks_checkout_order_processed', array($this,'affiliatepress_insert_pending_commission_from_woocommerce') );
                }            

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func'),15,5);

                /* Update the status of the commission to "unpaid" */
                add_action( 'woocommerce_order_status_completed', array($this,'affiliatepress_accept_pending_commission'), 10, 1 );
                add_action( 'woocommerce_order_status_processing', array($this,'affiliatepress_accept_pending_commission'), 10, 1 );

                /* Update the status of the commission to "pending" when the originating order is moved from failed to any other status */
                add_action( 'woocommerce_order_status_changed', array($this,'affiliatepress_approve_rejected_commission'), 15, 3 );

                /* WooCommerce Product Commission Disable Setting Added Here */
                add_filter( 'woocommerce_product_data_tabs', array( $this, 'affiliatepress_add_product_tabs' ) );
                add_action( 'woocommerce_product_data_panels', array( $this, 'affiliatepress_add_product_panels' ), 101 );
                add_action( 'woocommerce_process_product_meta', array(&$this, 'affiliatepress_save_product_meta'));


                /* Add WooCommerce Backend Product List */                              
                add_action('init', array( $this, 'affiliatepress_woocommerce_shop_base_rewrites'));

                add_action('woocommerce_product_options_general_product_data', array($this,'affiliatepress_add_nonce_field'));
                add_action( 'woocommerce_thankyou', array($this,'affiliatepress_insert_commission_express_checkout_woocommerce'), 10,1);

            }

            if($affiliatepress_is_woocommerce_active){
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_source_product_func'),10,3);
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_link_order_func'),10,3); 
            }
        }
        function affiliatepress_insert_commission_express_checkout_woocommerce( $order_id ) {
            global $affiliatepress_commission_debug_log_id;
        
            $order = wc_get_order( $order_id );
        
            if ( $order ) {
                do_action('affiliatepress_commission_debug_log_entry','commission_tracking_debug_logs',$this->affiliatepress_integration_slug.' : Express Chekout Order id ','affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking',$order_id, $affiliatepress_commission_debug_log_id);

                $this->affiliatepress_insert_pending_commission_from_woocommerce( $order );
            }else{
                $msg = "Order Not Found";
                do_action('affiliatepress_commission_debug_log_entry','commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Not Found Order ','affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $msg,$affiliatepress_commission_debug_log_id);
            }
        }
        
        /**
         * WooCommerce Shop Page Redirect Rewrite rules
         *
         * @return void
        */
        public function affiliatepress_woocommerce_shop_base_rewrites() {
            if ( $affiliatepress_shop_page_id = wc_get_page_id( 'shop' ) ) {
                global $AffiliatePress;
                $affiliatepress_affiliate_url_parameter = $AffiliatePress->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');
                $affiliatepress_uri = get_page_uri($affiliatepress_shop_page_id);
                add_rewrite_rule( $affiliatepress_uri . '/' . $affiliatepress_affiliate_url_parameter . '(/(.*))?/?$', 'index.php?post_type=product&'.$affiliatepress_affiliate_url_parameter.'=$matches[2]','top');
            }
        }
        
        /**
         * Function for get order page link for commission referance order
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_link_order_func($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){
                $affiliatepress_ap_commission_order_link   = '<a target="_blank" class="ap-refrance-link" href="'.admin_url("admin.php?page=wc-orders&action=edit&id=".$affiliatepress_ap_commission_reference_id).'");"> '. $affiliatepress_ap_commission_reference_id .' </a>';
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
        function affiliatepress_get_source_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_existing_products_data = array();
                $affiliatepress_args = array(
                    'limit' => -1, 
                    'status' => 'publish',
                    's' => $affiliatepress_search_product_str, 
                    'return' => 'ids', 
                );            
                $affiliatepress_product_query = new WC_Product_Query($affiliatepress_args);
                $affiliatepress_product_ids = $affiliatepress_product_query->get_products();
                if($affiliatepress_product_ids){
                    foreach ($affiliatepress_product_ids as $affiliatepress_product_id) {
                        $affiliatepress_product = wc_get_product($affiliatepress_product_id);
                        if ($affiliatepress_product) {
                            $affiliatepress_existing_product_data[] = array(
                                'value' => $affiliatepress_product->get_id(),
                                'label' => $affiliatepress_product->get_name(),
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
         * Function For Check Woocommecre integrations settings
         *
         * @return bool
         */
        function affiliatepress_woocommerce_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;            
            $affiliatepress_enable_woocommerce = $AffiliatePress->affiliatepress_get_settings('enable_woocommerce', 'integrations_settings');
            if($affiliatepress_enable_woocommerce != 'true'){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Check Woocommecre Plugin Active
         *
         * @return bool
         */
        function affiliatepress_check_plugin_active(){
            $affiliatepress_flag = true;
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Add Woocommerce Nonce Field
         *
         * @return void
         */
        function affiliatepress_add_nonce_field(){
            wp_nonce_field('affiliatepress_save_product_nonce_action', 'affiliatepress_save_product_nonce');
        }
        
        /**
         * Function For Save Woocommerce Nonce Field
         *
         * @param  integer $affiliatepress_product_id
         * @return void
         */
        function affiliatepress_save_product_meta($affiliatepress_product_id){       
            
            if (isset($_POST['affiliatepress_save_product_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['affiliatepress_save_product_nonce'])), 'affiliatepress_save_product_nonce_action')){ //phpcs:ignore

                if(isset($_POST['affiliatepress_disable_commissions_woocommerce'])){ //phpcs:ignore
                    update_post_meta($affiliatepress_product_id, 'affiliatepress_disable_commissions_woocommerce', 1); //phpcs:ignore
                } else {
                    delete_post_meta($affiliatepress_product_id, 'affiliatepress_disable_commissions_woocommerce');
                }

                do_action('affiliatepress_woocommerce_save_product_settings' , $affiliatepress_product_id);
    
            }
        }  
        
        /**
         * Function for add product panel setting
         *
         * @return void
        */
        function affiliatepress_add_product_panels(){
            global $post;
        ?>
            <div id="affiliatepress_product_panel" class="panel woocommerce_options_panel">
                <div class="options_group affiliatepress_wc_product_options">
                    <?php
                        woocommerce_wp_checkbox( array(
                            'id'          => 'affiliatepress_disable_commissions_woocommerce',
                            'label'       => esc_html__( 'Disable commissions', 'affiliatepress-affiliate-marketing' ),
                            'description' => esc_html__( 'Disable AffiliatePress commission on sale of this product.', 'affiliatepress-affiliate-marketing'),
                            'cbvalue'     => 1,
                            'class'       => 'affiliatepress_wc_products_checkbox'
                        ) );

                         /**
                        * Hook to add settings after the core ones
                        * 
                        */
                        do_action( 'affiliatepress_woocommerce_add_product_settings' );
                    ?>
                </div>
            </div>

        <?php
        }        

                
        /**
         * Function for add WooCommerce Product Tab
         *
         * @param  array $affiliatepress_woo_tabs
         * @return array
        */
        function affiliatepress_add_product_tabs( $affiliatepress_woo_tabs ){

            $affiliatepress_woo_tabs['affiliatepress_affiliate'] = array(
                'label'  => esc_html__( 'AffiliatePress Settings', 'affiliatepress-affiliate-marketing'),
                'target' => 'affiliatepress_product_panel',
                'class'  => array('affiliatepress_mapping_tab'),
            );

            return $affiliatepress_woo_tabs;
        }

        /**
         * Function for approve commisssion reject 
         *
         * @param  integer $affiliatepress_order_id
         * @param  string $affiliatepress_status_from
         * @param  string $affiliatepress_status_to
         * @return void
        */
        function affiliatepress_approve_rejected_commission($affiliatepress_order_id, $affiliatepress_status_from, $affiliatepress_status_to){
            global $AffiliatePress, $affiliatepress_commission_debug_log_id,$affiliatepress_tbl_ap_affiliate_commissions;
            if ( ! in_array( $affiliatepress_status_from, array( 'failed', 'cancelled', 'refunded' ) ) ){
                return;
            }            
            if ( in_array( $affiliatepress_status_to, array( 'failed', 'cancelled', 'refunded', 'processing', 'completed' ) ) ){
                return;
            }            
            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);  

            if(!empty($affiliatepress_all_commission_data)){

                foreach($affiliatepress_all_commission_data as $affiliatepress_commission_data){

                    if(!empty($affiliatepress_commission_data)){
                        $affiliatepress_ap_commission_status = (isset($affiliatepress_commission_data['ap_commission_status']))?intval($affiliatepress_commission_data['ap_commission_status']):0;
                        $affiliatepress_ap_commission_id     = (isset($affiliatepress_commission_data['ap_commission_id']))?intval($affiliatepress_commission_data['ap_commission_id']):0;
                        if($affiliatepress_ap_commission_status == 4){
                            $affiliatepress_debug_log_msg = sprintf( 'Commission #%s could not be rejected because it was already paid.', $affiliatepress_ap_commission_id );
                            continue;
                        }
                        if($affiliatepress_ap_commission_id != 0 && $affiliatepress_ap_commission_status != 3){
        
                            $affiliatepress_commission_data = array(
                                'ap_commission_updated_date' => current_time( 'mysql', true ),
                                'ap_commission_status' 		 => 2
                            );
                            $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_reference_id' => $affiliatepress_ap_commission_id ));
        
        
                            $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as pending, after order #%s was updated from %s to %s.', $affiliatepress_ap_commission_id, $affiliatepress_order_id, $affiliatepress_status_from, $affiliatepress_status_to );
        
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Pending ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                        }                                
                    } 

                }

            }
           

        }

        /**
         * Function for accept pending commission
         *
         * @param  int $affiliatepress_order_id
         * @return void
        */
        function affiliatepress_accept_pending_commission( $affiliatepress_order_id ) {
            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_order = wc_get_order( $affiliatepress_order_id );
            if($affiliatepress_order->get_status() == 'processing' && $affiliatepress_order->get_payment_method() == 'cod'){
                return;
            }

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
                        $affiliatepress_billing_email = ( true === version_compare( WC()->version, '3.0.0', '>=' ) ? $affiliatepress_order->get_billing_email() : $affiliatepress_order->billing_email );                
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
         * Function For Add Pending Commission
         *
         * @param  array $affiliatepress_order
         * @return void
         */
        function affiliatepress_insert_pending_commission_from_woocommerce($affiliatepress_order){

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_order_id = ( is_a( $affiliatepress_order, 'WC_Order' ) ? $affiliatepress_order->get_id() : $affiliatepress_order );
            
            /* Get and check to see if referrer exists */
            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();        
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;
            
            $affiliatepress_order_data   = array('order_id' => $affiliatepress_order_id);
            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_order_data ,$affiliatepress_order );

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_get_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_order_data ,$affiliatepress_order );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            /*
            if(!$affiliatepress_affiliates->affiliatepress_is_valid_affiliate($affiliatepress_affiliate_id)){
                echo "Affiliate ID Not Valid";
                return;
            }            
            $affiliatepress_commission_exists = $affiliatepress_tracking->affiliatepress_check_commission_exists( array( 'reference' => $affiliatepress_order_id, 'origin' => $this->affiliatepress_integration_slug ));           
            if($affiliatepress_commission_exists){                
                echo "Commission already added";
                return;                
            }
            */

            $affiliatepress_order = ( is_a( $affiliatepress_order, 'WC_Order' ) ? $affiliatepress_order : wc_get_order( $affiliatepress_order_id ) );
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

            /* Get All Order Cart Items */
            $affiliatepress_cart_order_items = $affiliatepress_order->get_items();

            $affiliatepress_cart_shipping = $affiliatepress_order->get_shipping_total( 'edit' );

            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('woocommerce_exclude_taxes', 'integrations_settings');            
            $affiliatepress_exclude_shipping = $AffiliatePress->affiliatepress_get_settings('woocommerce_exclude_shipping', 'integrations_settings');

            if ( $affiliatepress_exclude_taxes == 'false' ) {
                $affiliatepress_cart_shipping = $affiliatepress_cart_shipping + $affiliatepress_order->get_shipping_tax( 'edit' );
            }
            
            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_commission_type = 'sale';
            $affiliatepress_order_referal_amount = 0;

            /* Add Commission Customer Here */
            $affiliatepress_customer_args = array(
                'email'   	   => isset($affiliatepress_order) ? sanitize_email( $affiliatepress_order->get_billing_email() ) : '',
                'user_id' 	   => isset($affiliatepress_order) ? intval( $affiliatepress_order->get_user_id() ) : 0,
                'first_name'   => isset($affiliatepress_order) ? sanitize_text_field( $affiliatepress_order->get_billing_first_name()) : '',
                'last_name'	   => isset($affiliatepress_order) ? sanitize_text_field( $affiliatepress_order->get_billing_last_name() ) :'',
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

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
                
                $affiliatepress_order_referal_amount = $affiliatepress_order->get_total();
                if($affiliatepress_exclude_taxes == 'true'){
                    $affiliatepress_order_total_tax = $affiliatepress_order->get_total_tax();
                    $affiliatepress_order_referal_amount = $affiliatepress_order_referal_amount - $affiliatepress_order_total_tax;
                }
                if($affiliatepress_exclude_shipping == 'true'){                    
                    $affiliatepress_order_referal_amount = $affiliatepress_order_referal_amount - $affiliatepress_cart_shipping;
                }                

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
                $affiliatepress_commission_rules  = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_order_referal_amount, $affiliatepress_order->get_currency('edit'), $affiliatepress_args);                
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
                if(!empty($affiliatepress_cart_order_items)){
                    foreach($affiliatepress_cart_order_items as $affiliatepress_cart_item){

                        $affiliatepress_woocommerce_product = array(
                            'product_id'=>$affiliatepress_cart_item->get_product_id(),
                            'source'=>$this->affiliatepress_integration_slug
                        );
                        
                        $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_woocommerce_product );

                        /* Verify if commissions are disabled for this product */
                        if($affiliatepress_product_disable){

                            continue;
                        }
             
    
                        $affiliatepress_amount = $affiliatepress_cart_item->get_total( 'edit' );
                        $affiliatepress_amount = apply_filters('affiliatepress_woocommerce_chnage_product_amount',$affiliatepress_amount,$affiliatepress_cart_item);
    
                        /* Include Tax */
                        if($affiliatepress_exclude_taxes == 'false'){
                            $affiliatepress_amount = $affiliatepress_amount + $affiliatepress_cart_item->get_total_tax( 'edit' );
                        }
    
                        /* Include Shipping */
                        if($affiliatepress_exclude_shipping == 'false'){
                            $affiliatepress_amount = $affiliatepress_amount + $affiliatepress_cart_shipping / count( $affiliatepress_cart_order_items );
                        }
    
                        /* Set Product ID */
                        $affiliatepress_variation_id = $affiliatepress_cart_item->get_variation_id( 'edit' );
                        $affiliatepress_product_id   = ( ! empty( $affiliatepress_variation_id ) ? intval($affiliatepress_variation_id) : $affiliatepress_cart_item->get_product_id( 'edit' ) );
                        $affiliatepress_product_name   = !empty($affiliatepress_cart_item->get_name()) ? $affiliatepress_cart_item->get_name() : '';
                        $affiliatepress_product = wc_get_product( $affiliatepress_product_id );
                        $affiliatepress_commission_type = $affiliatepress_product->is_type( array( 'subscription', 'variable-subscription', 'subscription_variation' ) ) ? 'subscription' : 'sale';
                        $affiliatepress_quntity = $affiliatepress_cart_item->get_quantity();

                        /* Calculate Commission Amount */
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
                        $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount($affiliatepress_amount, $affiliatepress_order->get_currency( 'edit' ), $affiliatepress_args );

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

            $affiliatepress_visit_id = apply_filters( 'affiliatepress_get_visit_id', $affiliatepress_visit_id,$affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, $affiliatepress_order_id ,$affiliatepress_order );

            $affiliatepress_commisison_other_details = array();
            $affiliatepress_commisison_other_details  = apply_filters( 'affiliatepress_get_commisison_other_details',$affiliatepress_commisison_other_details,$affiliatepress_affiliate_id, $affiliatepress_visit_id ,$this->affiliatepress_integration_slug, $affiliatepress_order_id ,$affiliatepress_order );

            /* Prepare commission data */
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
                'ap_commission_order_amount'     => $affiliatepress_order->get_total(),
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
                $affiliatepress_commission_data['commission_other_details'] = $affiliatepress_commisison_other_details;

                do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data );
                $affiliatepress_debug_log_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
            }

        }

    }
}
global $affiliatepress_woocommerce;
$affiliatepress_woocommerce = new affiliatepress_woocommerce();