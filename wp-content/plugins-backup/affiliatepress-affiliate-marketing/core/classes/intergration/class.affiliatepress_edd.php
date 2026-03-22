<?php 

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_edd') ){

    class affiliatepress_edd Extends AffiliatePress_Core{

        private $affiliatepress_integration_slug;

        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){ 
            
            global $affiliatepress_is_edd_active ;
            $affiliatepress_is_edd_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'easy_digital_downloads';

            if($this->affiliatepress_edd_commission_add() && $affiliatepress_is_edd_active){
                
                /** Add Pending Commission */
                add_action('edd_insert_payment',array($this, 'affiliatepress_edd_insert_payment'),10,2);

                /** Add validation */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func_edd'),15,5);

                /** Add Approved Commission */
                add_action( 'edd_complete_purchase', array($this,'affiliatepress_accept_pending_commission_edd'), 10, 1 );

                /** Add Pending Commission on status change*/
                add_action( 'edd_update_payment_status', array($this,'affiliatepress_change_pending_status_edd'), 10, 3 );

                /** Add Approved Commission  on status change*/
                add_action( 'edd_update_payment_status', array($this,'affiliatepress_change_completed_status_edd'), 10, 3 );

                /** Add Affiliate Disable settings */
                add_action('add_meta_boxes',array($this, 'affiliatepress_add_affilate_edd_metabox'), 10, 1);
 
                /** Save Affiliate Disable settings */
                add_action('save_post',array($this, 'affiliatepress_save_edd_metabox_data'), 19,1);

                /** Add Page In proper URL */
                add_action( 'init', array( $this, 'affiliatepress_downloads_page_rewrites' ) );
            
            }

            if($affiliatepress_is_edd_active)
            {
                /**Get Order Link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_link_order_func_edd'),10,3); 

                /* Get Product data */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_edd_product_func'),10,3); 
            }
        }
        
        /**
         * AffiliatePress Rewrite Rules
         *
         * @return void
        */
        public function affiliatepress_downloads_page_rewrites() {
            global $AffiliatePress;
            $affiliatepress_download_main_page = get_post_type_object( 'download' );
            if ( null === $affiliatepress_download_main_page ) {
                return;
            }
            if ( ! empty( $affiliatepress_download_main_page->rewrite['slug'] ) ) {
                $affiliatepress_slug = $affiliatepress_download_main_page->rewrite['slug'];
            } else {
                $affiliatepress_slug = 'downloads';
            }
            $affiliatepress_url_parameter = $AffiliatePress->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');           
            add_rewrite_rule( $affiliatepress_slug . '/' . $affiliatepress_url_parameter . '(/(.*))?/?$', 'index.php?post_type=download&' . $affiliatepress_url_parameter . '=$matches[2]', 'top' );
        }

        
        /**
         * Function For Edd get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string 
         */
        function affiliatepress_get_link_order_func_edd($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){

            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug)
            {
                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href="'.admin_url('edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id='.$affiliatepress_ap_commission_reference_id).'");"> '. $affiliatepress_ap_commission_reference_id .' </a>';

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
        function affiliatepress_get_edd_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str) {
            global $wpdb;
            
            if ($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug) {
                
                $affiliatepress_existing_products_data = array();

                $affiliatepress_args = array(
                    'post_type'   => 'download',  // Your custom post type
                    'post_status' => 'publish',   // Only published posts
                    's'           => $affiliatepress_search_product_str, // Search term
                    'fields'      => 'ids',       // Only return post IDs
                );

                $affiliatepress_query = new WP_Query($affiliatepress_args);

                if ($affiliatepress_query->have_posts()) {
                    $affiliatepress_post_ids = $affiliatepress_query->posts;
                    foreach ($affiliatepress_post_ids as $affiliatepress_post_id) {

                        $affiliatepress_post_name = get_the_title($affiliatepress_post_id);

                        // echo "<>".$affiliatepress_post_name."<br>";
                        
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
         * Affiliatepress Downloads in Affiliate Settings add
         *
         * @return void
         */
        function affiliatepress_add_affilate_edd_metabox() {
            add_meta_box(
                'affilatepress_edd_post_metabox_wrapper',
                esc_html__( 'AffiliatePress Commission Settings', 'affiliatepress-affiliate-marketing'),
                array( $this,'affiliatepress_edd_meta_box_callback'),
                'download',
                'normal',
                'high',
                array(
                    '__block_editor_compatible_meta_box' => true,
                )
            );
        }
        
        /**
         * affiliatepress_edd_meta_box_callback
         *
         * @param  array $affiliatepress_post
         * @return void
         */
        function affiliatepress_edd_meta_box_callback($affiliatepress_post){

            global $post;
            // Get the disable commissions value
            $affiliatepress_commission_disable_edd = get_post_meta( $post->ID, 'affiliatepress_commission_disable_edd', true );
            $affiliatepress_commission_nonce_edd = wp_create_nonce('affiliatepress_commission_nonce_edd');
            ?>
            
                <div id="affiliatepress_product_settings" class="affiliatepress-options-groups-wrapper" style="margin-top: 20px; margin-bottom:20px;">
                    <div class="affiliatepress-options-group">
                        <div class="affiliatepress-option-field-wrapper">
                            <label for="affiliatepress-disable-commissions"><?php echo  esc_html__( 'Disable Commissions', 'affiliatepress-affiliate-marketing'); ?></label>
                            <label for="affiliatepress-disable-commissions" style="margin-left: 80px;">
                                <input type="checkbox" class="affiliatepress_commission_disable_edd" name="affiliatepress_commission_disable_edd" id="affiliatepress_commission_disable_edd" value="1"<?php checked( esc_html($affiliatepress_commission_disable_edd), true ); ?> />
                                <?php echo esc_html__( 'Disable commissions for this Download.', 'affiliatepress-affiliate-marketing'); ?>
                            </label>
                        </div>
                    </div>
                </div>  
                <input name="affiliatepress_commission_nonce_edd" id="affiliatepress_commission_nonce_edd" type="hidden" value=" <?php echo esc_attr( $affiliatepress_commission_nonce_edd ) ?>" />
            <?php

            do_action('affiliatepress_edd_add_product_settings');
        
        }
        
        /**
         * affiliatepress_save_edd_metabox_data : Save meta option 
         *
         * @param  int $affiliatepress_post_id
         * @return void
         */
        function affiliatepress_save_edd_metabox_data($affiliatepress_post_id) {

            $affiliatepress_wpnonce = isset($_POST['affiliatepress_commission_nonce_edd']) ? sanitize_text_field(wp_unslash($_POST['affiliatepress_commission_nonce_edd'])) : '';// phpcs:ignore

            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_commission_nonce_edd');
            if (! $affiliatepress_verify_nonce_flag ) {
                $affiliatepress_nonce_error = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                return $affiliatepress_nonce_error;
            }  
            
            $affiliatepress_commission_disable_edd = !empty($_POST['affiliatepress_commission_disable_edd']) ? sanitize_text_field( $_POST['affiliatepress_commission_disable_edd'] ) : '';  //phpcs:ignore

            if($affiliatepress_commission_disable_edd != ""){
                update_post_meta( $affiliatepress_post_id, 'affiliatepress_commission_disable_edd', $affiliatepress_commission_disable_edd );
            }
            else{
                delete_post_meta( $affiliatepress_post_id, 'affiliatepress_commission_disable_edd' );
            }

            do_action('affiliatepress_edd_save_product_settings' , $affiliatepress_post_id);

        }
        
        /**
         * affiliatepress_edd_commission_add
         *
         * @return void
         */
        function affiliatepress_edd_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;

            $affiliatepress_enable_edd = $AffiliatePress->affiliatepress_get_settings('enable_easy_digital_downloads', 'integrations_settings');

            if($affiliatepress_enable_edd != 'true'){
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }

        function affiliatepress_check_plugin_active() {
            $affiliatepress_flag = false;
        
            if (!function_exists('is_plugin_active')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }

            if (is_plugin_active('easy-digital-downloads/easy-digital-downloads.php') || is_plugin_active('easy-digital-downloads-pro/easy-digital-downloads.php')) {
                $affiliatepress_flag = true; 
            }
            else
            {
                $affiliatepress_flag = false;
            }
        
            return $affiliatepress_flag;
        }
        /**
         * affiliatepress_edd_insert_payment : Download edd product
         *
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_order_data
         * @return void
         */
        function affiliatepress_edd_insert_payment($affiliatepress_order_id, $affiliatepress_order_data)
        {

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' order details ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', json_encode($affiliatepress_order_data), $affiliatepress_commission_debug_log_id);

            $affiliatepress_order_id = isset($affiliatepress_order_id) ? intval($affiliatepress_order_id) : 0;

            $affiliatepress_affiliate_id = $affiliatepress_tracking->affiliatepress_get_referral_affiliate();
            $affiliatepress_visit_id	  = $affiliatepress_tracking->affiliatepress_get_referral_visit();    
            
            $affiliatepress_affiliate_id = !empty($affiliatepress_affiliate_id) ? intval($affiliatepress_affiliate_id) : 0;

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_referrer_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_order_id) , $affiliatepress_order_data );

            $affiliatepress_affiliate_id = apply_filters( 'affiliatepress_get_affiliate_id', $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_order_id) ,$affiliatepress_order_data );

            if ( empty( $affiliatepress_affiliate_id ) ) {
                $affiliatepress_log_msg = "Empty Affiliate ID";
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Empty Affiliate ID', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            $affiliatepress_commission_validation = array();
            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_order_data);

            if(!empty($affiliatepress_commission_validation)){

                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))? sanitize_text_field($affiliatepress_commission_validation['variant']):'';

                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))? sanitize_text_field($affiliatepress_commission_validation['msg']):'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }     

            }

            $affiliatepress_customer_args = array(
                'email'   	   => !empty($affiliatepress_order_data['user_email']) ? sanitize_email($affiliatepress_order_data['user_email']) : '',
                'user_id' 	   => !empty($affiliatepress_order_data['user_info']['id']) ? intval($affiliatepress_order_data['user_info']['id']) : 0,
                'first_name'   => !empty($affiliatepress_order_data['user_info']['first_name']) ? sanitize_text_field($affiliatepress_order_data['user_info']['first_name']) : '',
                'last_name'    => !empty($affiliatepress_order_data['user_info']['last_name']) ? sanitize_text_field($affiliatepress_order_data['user_info']['last_name']) : '',
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

            $affiliatepress_cart_order_items = edd_get_payment_meta_cart_details( $affiliatepress_order_id );

            $affiliatepress_order_details = ( function_exists( 'edd_get_order' ) ? edd_get_order( $affiliatepress_order_id ) : edd_get_payment( $affiliatepress_order_id ) );

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('easy_digital_downloads_exclude_taxes', 'integrations_settings');            
            $affiliatepress_exclude_shipping = $AffiliatePress->affiliatepress_get_settings('easy_digital_downloads_exclude_shipping', 'integrations_settings');

            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
            
                $affiliatepress_total_price = isset($affiliatepress_order_data['price']) ? floatval($affiliatepress_order_data['price']) : 0;

                $affiliatepress_amount = 0;
                $affiliatepress_amount = $affiliatepress_total_price;

                $affiliatepress_total_tax_amount = 0;
                foreach($affiliatepress_cart_order_items as $affiliatepress_cart_item){
                    $affiliatepress_cart_item_item_tax = !empty($affiliatepress_cart_item['tax']) ? floatval($affiliatepress_cart_item['tax']) : 0;
                    $affiliatepress_total_tax_amount = $affiliatepress_total_tax_amount + $affiliatepress_cart_item_item_tax;
                }

                $affiliatepress_total_shipping_amount = 0;
                foreach($affiliatepress_cart_order_items as $affiliatepress_cart_item){

                    foreach ( $affiliatepress_cart_item['fees'] as $affiliatepress_key => $affiliatepress_fee ) {
    
                        if ( empty( $affiliatepress_fee['amount'] ) ) {
                            continue;
                        }
    
                        if ( false === strpos( $affiliatepress_key, 'shipping' ) ) {
                            continue;
                        }

                        $affiliatepress_shipping_amount = !empty($affiliatepress_fee['amount']) ? floatval($affiliatepress_fee['amount']) : 0;
                                 
                        $affiliatepress_total_shipping_amount = $affiliatepress_total_shipping_amount + $affiliatepress_shipping_amount;
    
                    }
                }

                if($affiliatepress_exclude_taxes == 'true')
                {
                    $affiliatepress_amount = $affiliatepress_amount-$affiliatepress_total_tax_amount;
                }

                if($affiliatepress_exclude_shipping == 'true')
                {
                    $affiliatepress_amount = $affiliatepress_amount-$affiliatepress_total_shipping_amount;
                }

                $affiliatepress_commission_types = "sale";

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => 'sale',
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => 0,
                    'order_id'     => $affiliatepress_order_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );

                $affiliatepress_currency = isset($affiliatepress_order_data['currency']) ? sanitize_text_field($affiliatepress_order_data['currency']) : '';
                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount,$affiliatepress_currency , $affiliatepress_args );
                
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

                $affiliatepress_order_referal_amount = $affiliatepress_amount;

            }
            else
            {
                if(!empty($affiliatepress_cart_order_items)){
                    foreach($affiliatepress_cart_order_items as $affiliatepress_cart_item){

                        $affiliatepress_product_id = isset($affiliatepress_cart_item['id']) ? intval($affiliatepress_cart_item['id']) : '';

                        $affiliatepress_product_name = isset($affiliatepress_cart_item['name']) ? sanitize_text_field($affiliatepress_cart_item['name']) : '';

                        $affiliatepress_edd_product = array(
                            'product_id'=>$affiliatepress_product_id,
                            'source'=>$this->affiliatepress_integration_slug
                        );

                        $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_edd_product );

                        if($affiliatepress_product_disable){

                            continue;
                        }
                        
                        $affiliatepress_amount = $affiliatepress_cart_item['item_price'];
                        $affiliatepress_amount = apply_filters('affiliatepress_edd_chnage_product_amount',$affiliatepress_amount,$affiliatepress_cart_item);

                        if($affiliatepress_exclude_taxes == 'false'){
                            $affiliatepress_amount = $affiliatepress_cart_item['price'];
                        }

                        if($affiliatepress_exclude_shipping == 'false'){
                            
                            foreach ( $affiliatepress_cart_item['fees'] as $affiliatepress_key => $affiliatepress_fee ) {

                                if ( empty( $affiliatepress_fee['amount'] ) ) {
                                    continue;
                                }
        
                                if ( false === strpos( $affiliatepress_key, 'shipping' ) ) {
                                    continue;
                                }
                                    
                                $affiliatepress_amount = $affiliatepress_amount + $affiliatepress_fee['amount'];
        
                            }

                        }

                        $affiliatepress_args = array(
                            'origin'	       => $this->affiliatepress_integration_slug,
                            'type' 		       => ! empty( $affiliatepress_cart_item['item_number']['options']['recurring'] ) ? 'subscription' : 'sale',
                            'affiliate_id'     => $affiliatepress_affiliate_id,
                            'product_id'       => $affiliatepress_product_id,
                            'customer_id'      => $affiliatepress_customer_id,
                            'commission_basis' => 'per_product',
                            'order_id'         => $affiliatepress_order_id,
                        );

                        $affiliatepress_commission_types = $affiliatepress_args['type'];

                        $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, $affiliatepress_cart_item['currency'], $affiliatepress_args );

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
            
            $affiliatepress_visit_id = apply_filters( 'affiliatepress_get_visit_id', $affiliatepress_visit_id, $affiliatepress_affiliate_id, $this->affiliatepress_integration_slug, array('order_id'=>$affiliatepress_order_id) ,$affiliatepress_order_data ); 

            $affiliatepress_commisison_other_details  = apply_filters( 'affiliatepress_get_commisison_other_details',$affiliatepress_commisison_other_details,$affiliatepress_affiliate_id, $affiliatepress_visit_id ,$this->affiliatepress_integration_slug, $affiliatepress_order_id ,$affiliatepress_order_data );

            $affiliatepress_commission_data = array(
                'ap_affiliates_id'		         => $affiliatepress_affiliate_id,
                'ap_visit_id'			         => (!is_null($affiliatepress_visit_id)?$affiliatepress_visit_id:0),
                'ap_commission_type'	         => $affiliatepress_commission_types,
                'ap_commission_status'	         => 2,
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_order_data['price'],
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s', current_time('timestamp'))// phpcs:ignore
            );

            $affiliatepress_ap_commission_id = $affiliatepress_tracking->affiliatepress_insert_commission( $affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id);
            if($affiliatepress_ap_commission_id == 0){
                $affiliatepress_debug_log_msg = 'Pending commission could not be inserted due to an unexpected error.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Not Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
            }else{

                $affiliatepress_commission_data['products_commission'] = $affiliatepress_allow_products_commission;
                $affiliatepress_commission_data['commission_rules'] = $affiliatepress_commission_rules;
                $affiliatepress_commission_data['commission_other_details'] = $affiliatepress_commisison_other_details;
                
                do_action('affiliatepress_after_commission_created', $affiliatepress_ap_commission_id, $affiliatepress_commission_data);
                $affiliatepress_debug_log_msg = sprintf( 'Pending commission #%s has been successfully inserted.', $affiliatepress_ap_commission_id );

                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Successfully Inserted', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);            
            }

        }
        
        /**
         * affiliatepress_accept_pending_commission_edd 
         *
         * @param  int $affiliatepress_order_id
         * @return void
         */
        function affiliatepress_accept_pending_commission_edd( $affiliatepress_order_id ) {

            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id,$affiliatepress_tracking;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve order id ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_order_id, $affiliatepress_commission_debug_log_id);

            $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug,' AND (ap_commission_status = 2 OR ap_commission_status = 3) ');

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
        
                            do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_updated_commission_status,2);
                            $affiliatepress_debug_log_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_commission_id );
            
                            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
            
                        }
                       
                    }

                }

            }
        }

        function affiliatepress_commission_validation_func_edd($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;
                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){

                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 

                    $affiliatepress_billing_email = isset($affiliatepress_order['user_email'])? sanitize_email($affiliatepress_order['user_email']) : '';      
                    
                    if($affiliatepress_earn_commissions_own_orders == 'false')
                    {
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
         * affiliatepress_change_pending_status_edd
         *
         * @param  int $affiliatepress_order_id
         * @param  string $affiliatepress_new_status
         * @param  string $affiliatepress_old_status
         * @return void
         */
        function affiliatepress_change_pending_status_edd( $affiliatepress_order_id, $affiliatepress_new_status, $affiliatepress_old_status  ) {

            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;
    
            if ( $affiliatepress_new_status == 'pending' || $affiliatepress_new_status == "on_hold"  || $affiliatepress_new_status == "processing" )
            {
                $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);
    
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
                
                                $affiliatepress_update_commission_data = array(
                                    'ap_commission_updated_date' => current_time( 'mysql', true ),
                                    'ap_commission_status' 		 => 2
                                );
                
                                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_update_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
                
                                $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as Pending, after order #%s status change.', $affiliatepress_ap_commission_id, $affiliatepress_order_id );
                
                                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Pending', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                            
                            }
                
                        }

                    }

                }


            }
            
        }
                       
       /**
        * affiliatepress_change_completed_status_edd
        * edd in on hold to chnage completed that time this function use
        *
        * @param  int $affiliatepress_order_id
        * @param  string $affiliatepress_new_status
        * @param  string $affiliatepress_old_status
        * @return void
        */
        function affiliatepress_change_completed_status_edd($affiliatepress_order_id, $affiliatepress_new_status, $affiliatepress_old_status)
        {
            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id,$affiliatepress_tracking;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            if ( $affiliatepress_new_status == 'complete' )
            {
                $affiliatepress_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);
    
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
                    
                                $affiliatepress_updated_commission_status = 1;
            
                                if($affiliatepress_default_commission_status != "auto")
                                {
                                    $affiliatepress_updated_commission_status = $affiliatepress_default_commission_status;
            
                                    $affiliatepress_update_commission_data = array(
                                        'ap_commission_updated_date' => current_time( 'mysql', true ),
                                        'ap_commission_status' 		 => $affiliatepress_default_commission_status
                                    );
            
                                }
                                else
                                {
                                    $affiliatepress_update_commission_data = array(
                                        'ap_commission_updated_date' => current_time( 'mysql', true ),
                                        'ap_commission_status' 		 => 1
                                    );
                                }
            
                                if($affiliatepress_updated_commission_status == 1){
                                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_update_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
        
                                    do_action('affiliatepress_after_commissions_status_change',$affiliatepress_ap_commission_id,1,2);
        
                                    $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as completed, after order #%s status change.', $affiliatepress_ap_commission_id, $affiliatepress_order_id );
                    
                                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approved', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                                }
                                
                            }
                    
                        }

                    }

                }


                
            }
        }  

    }

}

global $affiliatepress_edd;
$affiliatepress_edd = new affiliatepress_edd();