<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_easycart') ){

    class affiliatepress_easycart Extends AffiliatePress_Core{
    
        private $affiliatepress_integration_slug;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){  
            global $affiliatepress_is_easycart_active ;
            $affiliatepress_is_easycart_active = $this->affiliatepress_check_plugin_active();

            $this->affiliatepress_integration_slug = 'wp_easycart';

            if($this->affiliatepress_easycart_commission_add() && $affiliatepress_is_easycart_active){
                
                /**Add Pending Commisison */
                add_action( 'wpeasycart_order_inserted', array( $this, 'affiliatepress_add_pending_referral_easycart' ), 10, 5 );

                /* Affiliate Own Commission Filter Add Here  */
                add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_validation_func_easycart'),15,5);

                /**Add Approved Commisison */
                add_action( 'wpeasycart_order_paid', array( $this, 'affiliatepress_mark_referral_complete_easycart' ), 10 );

                /**Add Pending Commisison after status chnage */
                add_action( 'wpeasycart_order_status_update', array( $this, 'affiliatepress_pending_commission_on_status_change_easycart' ), 10 ,2 );

                /**Add Approved Commisison after status chnage */
                add_action( 'wpeasycart_order_status_update', array( $this, 'affiliatepress_completed_commission_on_status_change_easycart' ), 10 ,2 );

                /**Add page in proper url*/
                add_action('init', array( $this, 'affiliatepress_shop_base_rewrites'));

                /**add disable option section in easy cart  */
                add_action('wp_easycart_admin_product_details_after_images', array($this,'affiliatepress_add_disable_setting_field'),20);
                
                /**enque script for easycart disable option save  */
                add_action('admin_enqueue_scripts', array( $this, 'affiliatepress_easycart_js' ), 11);  
                
                /**save setting ajax call back function */
                add_action( 'wp_ajax_affiliatepress_easycart_save_settings', array($this , 'affiliatepress_easycart_save_settings_callback') );

            }

            if($affiliatepress_is_easycart_active)
            {
                /** Get Order Link */
                add_filter('affiliatepress_modify_commission_link',array($this,'affiliatepress_get_link_order_func_easycart'),10,3); 

                /* Add easycart Backend Product List */
                add_filter('affiliatepress_get_source_product',array($this,'affiliatepress_get_wp_easycart_product_func'),10,3); 
            }
        }
        
        /**
         * Function For EasyCart Js add
         *
         * @return void
         */
        function affiliatepress_easycart_js()
        {
            global $affiliatepress_ajaxurl;

            wp_register_script('affiliatepress_easycart', AFFILIATEPRESS_URL . 'js/affiliatepress_easycart.js', array('jquery'), AFFILIATEPRESS_VERSION);// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
            wp_enqueue_script('affiliatepress_easycart');

            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            if( is_plugin_active( 'affiliatepress-product-commission-rates/affiliatepress-product-commission-rates.php' ))
            {
                $affiliatepress_product_commission_active = true;
            }else{
                $affiliatepress_product_commission_active = false;
            }

            wp_localize_script( 'affiliatepress_easycart', 'affiliatepress_wpeasycart_ajax_object', array(
                'ajax_url' => $affiliatepress_ajaxurl,
                'product_rate_plugin_active' => $affiliatepress_product_commission_active
            ) );
        }
        
        /**
         * Function For Add Disable settings
         *
         * @return void
         */
        function affiliatepress_add_disable_setting_field() {

            $affiliatepress_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;//phpcs:ignore

            $affiliatepress_disable_commission_easycart = get_option( 'affiliatepress_disable_commission_easycart_'.$affiliatepress_product_id );

            ?>
                <div class="ec_admin_flex_row">
                    <div class="ec_admin_list_line_item ec_admin_col_12 ec_admin_col_first ec_admin_collapsable">
                    <div class="ec_admin_loader" id="affiliatepress_easycart_loader">
                        <div class="ec_admin_loader_animation"><?php esc_attr_e( 'Loading...', 'affiliatepress-affiliate-marketing' ); ?></div>
                        <div class="ec_admin_loader_loaded">
                            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 161.2 161.2" enable-background="new 0 0 161.2 161.2" xml:space="preserve">
                                <path class="ec_admin_loader_loaded_path" fill="none" stroke="#FFF" stroke-miterlimit="10" d="M425.9,52.1L425.9,52.1c-2.2-2.6-6-2.6-8.3-0.1l-42.7,46.2l-14.3-16.4c-2.3-2.7-6.2-2.7-8.6-0.1c-1.9,2.1-2,5.6-0.1,7.7l17.6,20.3c0.2,0.3,0.4,0.6,0.6,0.9c1.8,2,4.4,2.5,6.6,1.4c0.7-0.3,1.4-0.8,2-1.5c0.3-0.3,0.5-0.6,0.7-0.9l46.3-50.1C427.7,57.5,427.7,54.2,425.9,52.1z"/>
                                <circle class="ec_admin_loader_loaded_path" fill="none" stroke="#FFF" stroke-width="4" stroke-miterlimit="10" cx="80.6" cy="80.6" r="62.1"/>
                                <polyline class="ec_admin_loader_loaded_path" fill="none" stroke="#FFF" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="113,52.8 74.1,108.4 48.2,86.4 "/>
                            </svg>
                        </div>
                    </div>
                    <div class="ec_admin_settings_label ec_admin_expand_section_header">
                        <div class="dashicons-before dashicons-chart-area"></div>
                        <span><?php esc_attr_e( 'AffiliatePress Commission Setting', 'affiliatepress-affiliate-marketing' ); ?></span>
                        <a href="#tax" class="ec_admin_expand_section" data-section="ec_affiliatepress_settings_section"><div class="dashicons-before dashicons-arrow-down-alt2"></div></a>
                        <div class="ec_page_product_title_button_wrap ec_page_title_button_wrap">
                        </div>
                    </div>
                    <div class="ec_admin_settings_input ec_admin_collapsed_section" id="ec_affiliatepress_settings_section">
                        <div id="affiliatepress_product_settings" class="affiliatepress-options-groups-wrapper" style="margin-top: 10px; margin-bottom:10px;">
                            <div class="affiliatepress-options-group">
                                <div class="affiliatepress-option-field-wrapper">
                                <table style="border: none;">
                                    <tr>
                                        <td style="padding-left: 10px;">
                                            <label for="affiliatepress-disable-commissions" style="margin-left: 10px;"><?php esc_html_e( 'Disable Commissions', 'affiliatepress-affiliate-marketing'); ?></label>
                                        </td>
                                        <td style="padding-left: 20px;">
                                            <input type="checkbox" class="affiliatepress_disable_commission_easycart" name="affiliatepress_disable_commission_easycart" id="affiliatepress_disable_commission_easycart" value="1"<?php checked( esc_html($affiliatepress_disable_commission_easycart), true ); ?> />
                                            <span style="vertical-align: middle; margin-left: 5px;" class="description"><?php esc_html_e( 'Disable commissions for this Product', 'affiliatepress-affiliate-marketing'); ?></span>
                                        </td>
                                    </tr>
                                    <?php do_action('affiliatepress_wp_easycart_add_product_settings' ,$affiliatepress_product_id); ?>
                                </table>
                                </div>
                            </div>
                        </div>
                        <?php $affiliatepress_nonce = wp_create_nonce( 'affiliatepress_save_settings_nonce' ); ?>
                        <div class="ec_admin_products_submit">
                            <input type="submit" class="ec_admin_products_simple_button" onclick="return affiliatepress_easycart_save_settings( <?php echo esc_js( $affiliatepress_product_id ); ?>, '<?php echo esc_js( $affiliatepress_nonce ); ?>');" value="<?php esc_attr_e( 'Update AffiliatePress Settings', 'affiliatepress-affiliate-marketing' ); ?>" />
                        </div>
                    </div>
                    </div>
                </div>
            <?php
        }

        /**
         * AffiliatePress Shop Page Redirect
         *
         * @return void
        */
        public function affiliatepress_shop_base_rewrites() {
            $affiliatepress_store_page_id = get_option( 'ec_option_storepage' );            
            if ( $affiliatepress_store_page_id ) {                
                global $AffiliatePress;
                $affiliatepress_url_parameter = $AffiliatePress->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');
                $affiliatepress_uri = get_page_uri( $affiliatepress_store_page_id );                
                add_rewrite_rule( $affiliatepress_uri . '/' . $affiliatepress_url_parameter . '(/(.*))?/?$', 'index.php?page_id='.$affiliatepress_store_page_id.'&' . $affiliatepress_url_parameter . '=$matches[2]', 'top' );
            }
        }
        
        /**
         * Function For Save EasyCart Disable settings
         *
         * @return void
         */
        function affiliatepress_easycart_save_settings_callback(){
            $response_data = array();
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field($_POST['_wpnonce']), 'affiliatepress_save_settings_nonce' ) ) {//phpcs:ignore
                $response_data['status']  = 'error';
                $response_data['message'] = esc_html__( 'Sorry, your request cannot be processed due to security reasons.', 'affiliatepress-affiliate-marketing' );
                wp_send_json( $response_data );
            }

            $affiliatepress_product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

            if ( $affiliatepress_product_id <= 0 ) {
                $response_data['status']  = 'error';
                $response_data['message'] = esc_html__( 'Invalid product ID.', 'affiliatepress-affiliate-marketing' );
                wp_send_json( $response_data );
            }

            $affiliatepress_disable_commission = isset( $_POST['disable_commission'] ) ? intval($_POST['disable_commission'])  : 0;//phpcs:ignore

            $affiliatepress_option_key['affiliatepress_disable_commission_easycart_'.$affiliatepress_product_id] = $affiliatepress_disable_commission;

            $affiliatepress_product_rate_type = !empty($_POST['rate_type']) ? sanitize_text_field(wp_unslash($_POST['rate_type'])) : '';

            $affiliatepress_product_rate_val = !empty($_POST['rate_value']) ? floatval($_POST['rate_value']) : 0;

            $affiliatepress_product_commission_data = array(
                'rate_type' =>$affiliatepress_product_rate_type,
                'rate_value'=>$affiliatepress_product_rate_val
            );

            $affiliatepress_option_key = apply_filters( 'affiliatepress_wp_easycart_save_product_settings', $affiliatepress_option_key, $affiliatepress_product_id, $affiliatepress_product_commission_data );

            if(!empty($affiliatepress_option_key))
            {
                foreach ( $affiliatepress_option_key as $affiliatepress_key => $affiliatepress_value ) {
                    update_option( $affiliatepress_key, $affiliatepress_value );
                }

                $response_data['status']  = 'success';
                $response_data['message'] = esc_html__( 'Settings saved successfully.', 'affiliatepress-affiliate-marketing' );
            }
            else
            {
                $response_data['status']  = 'error';
                $response_data['message'] = esc_html__( 'Failed to save settings. Please try again.', 'affiliatepress-affiliate-marketing' );
            }

            wp_send_json( $response_data );
        }

        /**
         * Function for get source product for backend commission add/edit
         *
         * @param  array $affiliatepress_existing_source_product_data
         * @param  string $affiliatepress_ap_commission_source
         * @param  string $affiliatepress_search_product_str
         * @return array
        */
        function affiliatepress_get_wp_easycart_product_func($affiliatepress_existing_source_product_data, $affiliatepress_ap_commission_source, $affiliatepress_search_product_str){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_existing_products_data = array();

                $affiliatepress_args = array(
                    'post_type'   => 'ec_store',  // Your custom post type
                    'post_status' => 'publish',   // Only published posts
                    's'           => $affiliatepress_search_product_str, // Search term
                    'fields'      => 'ids',       // Only return post IDs
                    'ping_status'  => 'open', 
                );

                $affiliatepress_query = new WP_Query($affiliatepress_args);

                if ($affiliatepress_query->have_posts()) {
                    $affiliatepress_post_ids = $affiliatepress_query->posts;
                    foreach ($affiliatepress_post_ids as $affiliatepress_post_id) {

                        $affiliatepress_post_name = get_the_title($affiliatepress_post_id);
                        
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
         * Function For Easy Cart get order link
         *
         * @param  integer $affiliatepress_ap_commission_reference_id
         * @param  string $affiliatepress_ap_commission_source
         * @return string
         */
        function affiliatepress_get_link_order_func_easycart($affiliatepress_ap_commission_reference_id, $affiliatepress_ap_commission_source){
            
            if($affiliatepress_ap_commission_source == $this->affiliatepress_integration_slug){

                $affiliatepress_ap_commission_order_link = '<a target="_blank" class="ap-refrance-link" href="'.admin_url("admin.php?page=wp-easycart-orders&order_id=".$affiliatepress_ap_commission_reference_id."&ec_admin_form_action=edit").'");"> '. $affiliatepress_ap_commission_reference_id .' </a>';

                $affiliatepress_ap_commission_reference_id = $affiliatepress_ap_commission_order_link;
            }
            
            return $affiliatepress_ap_commission_reference_id;
        }
   
        /**
         * Function for affiliate validation
         *
         * @param  array $affiliatepress_commission_validation
         * @param  int $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_user
         * @return array
         */
        function affiliatepress_commission_validation_func_easycart($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_user){

            if($affiliatepress_source == $this->affiliatepress_integration_slug)
            {
                global $AffiliatePress;

                if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){
                    $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                    if($affiliatepress_earn_commissions_own_orders != 'true'){
                        $affiliatepress_billing_email = !empty($affiliatepress_user->email) ? $affiliatepress_user->email : '';            
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
         * Store a pending referral when a new order is created
         *
         * @param  int $affiliatepress_order_id
         * @param  array $affiliatepress_cart
         * @param  array $affiliatepress_order_totals
         * @param  array $affiliatepress_user
         * @param  string $affiliatepress_payment_type
         * @return void
         */
        public function affiliatepress_add_pending_referral_easycart( $affiliatepress_order_id, $affiliatepress_cart, $affiliatepress_order_totals, $affiliatepress_user, $affiliatepress_payment_type ){

            global $wpdb,$affiliatepress_tracking, $affiliatepress_affiliates,$AffiliatePress,$affiliatepress_commission_debug_log_id;

            $affiliatepress_order_id = !empty($affiliatepress_order_id) ? intval($affiliatepress_order_id) : 0;

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

            $affiliatepress_commission_validation = apply_filters( 'affiliatepress_commission_validation', $affiliatepress_commission_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_order_id, $affiliatepress_user);

            if(!empty($affiliatepress_commission_validation)){
                $affiliatepress_filter_variant = (isset($affiliatepress_commission_validation['variant']))?sanitize_text_field($affiliatepress_commission_validation['variant']):'';
                if($affiliatepress_filter_variant == 'error'){
                    $affiliatepress_error_msg = (isset($affiliatepress_commission_validation['msg']))?sanitize_text_field($affiliatepress_commission_validation['msg']):'';
                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Not Generate', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_error_msg, $affiliatepress_commission_debug_log_id);                    
                    return;

                }                
            }

            $affiliatepress_customer_args = array(
                'email'   	   => !empty($affiliatepress_user->email) ? sanitize_email($affiliatepress_user->email):'',
                'user_id' 	   => !empty($affiliatepress_user->user_id) ? intval($affiliatepress_user->user_id):'',
                'first_name'   => !empty($affiliatepress_user->first_name) ? sanitize_text_field($affiliatepress_user->first_name):'',
                'last_name'	   => !empty($affiliatepress_user->last_name) ? sanitize_text_field($affiliatepress_user->last_name):'',
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

            $affiliatepress_exclude_taxes = $AffiliatePress->affiliatepress_get_settings('wp_easycart_exclude_taxes', 'integrations_settings');            
            $affiliatepress_exclude_shipping = $AffiliatePress->affiliatepress_get_settings('wp_easycart_exclude_shipping', 'integrations_settings');

            $affiliatepress_commission_amount = 0;
            $affiliatepress_allow_products_commission = array();
            $affiliatepress_commission_products_ids = array();
            $affiliatepress_commission_products_name = array();

            $affiliatepress_commission_type = 'sale';
            $affiliatepress_commission_type = !empty($affiliatepress_commission_type) ? sanitize_text_field($affiliatepress_commission_type) : '';
            $affiliatepress_order_referal_amount = 0;

            $affiliatepress_items  = $affiliatepress_cart->cart;
            if($affiliatepress_tracking->affiliatepress_is_commission_basis_per_order()){
                
                $affiliatepress_total_price = isset($affiliatepress_order_totals->grand_total) ? floatval($affiliatepress_order_totals->grand_total):0;
                $affiliatepress_total_shipping_amount = !empty($affiliatepress_order_totals->shipping_total) ? floatval($affiliatepress_order_totals->shipping_total) : 0;
                $affiliatepress_total_tax_amount = !empty($affiliatepress_order_totals->tax_total) ? floatval($affiliatepress_order_totals->tax_total) : 0;

                $affiliatepress_amount = 0;
                $affiliatepress_amount = $affiliatepress_total_price;

                if($affiliatepress_exclude_taxes == "true")
                {
                    $affiliatepress_amount = $affiliatepress_amount - $affiliatepress_total_tax_amount;
                }

                if($affiliatepress_exclude_shipping == "true")
                {
                    $affiliatepress_amount = $affiliatepress_amount - $affiliatepress_total_shipping_amount;
                }

                $affiliatepress_args = array(
                    'origin'	   => $this->affiliatepress_integration_slug,
                    'type' 		   => 'sale',
                    'affiliate_id' => $affiliatepress_affiliate_id,
                    'product_id'   => 0,
                    'order_id'     => $affiliatepress_order_id,
                    'customer_id'  => $affiliatepress_customer_id,
                    'commission_basis' => 'per_order',
                );

                $affiliatepress_commission_rules    = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_amount, '', $affiliatepress_args );

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
                foreach ( $affiliatepress_items as $affiliatepress_cart_item ) {

                    $affiliatepress_product_id = !empty($affiliatepress_cart_item->product_id ) ? intval($affiliatepress_cart_item->product_id) : 0;

                    $affiliatepress_easycart_product = array(
                        'product_id'=>$affiliatepress_product_id,
                        'source'=>$this->affiliatepress_integration_slug
                    );

                    $affiliatepress_product_disable = $affiliatepress_tracking->affiliatepress_check_product_disabled( $affiliatepress_easycart_product );
                    if($affiliatepress_product_disable){
                        continue;
                    }
                    
                    $affiliatepress_product_name = !empty($affiliatepress_cart_item->title) ? sanitize_text_field($affiliatepress_cart_item->title): '';

                    $affiliatepress_product_total = !empty($affiliatepress_cart_item->total_price) ? floatval($affiliatepress_cart_item->total_price):0;
                    $affiliatepress_cart_shipping = !empty($affiliatepress_order_totals->shipping_total) ? floatval($affiliatepress_order_totals->shipping_total):0;
                    $affiliatepress_cart_tax      = !empty($affiliatepress_order_totals->tax_total) ? floatval($affiliatepress_order_totals->tax_total):0;
                    $affiliatepress_shipping = 0;

                    $affiliatepress_quntity = isset($affiliatepress_cart_item->quantity) ? intval($affiliatepress_cart_item->quantity) : '';

                    if($affiliatepress_exclude_taxes == 'false'){
                        $affiliatepress_tax            = $affiliatepress_cart_tax / count( $affiliatepress_items );
					    $affiliatepress_product_total += $affiliatepress_tax;
                    }

                    /* Include Shipping */
                    if($affiliatepress_exclude_shipping == 'false'){
                        $affiliatepress_shipping       = $affiliatepress_cart_shipping / count( $affiliatepress_items );
					    $affiliatepress_product_total += $affiliatepress_shipping;
                    }

                    if ( $affiliatepress_product_total <= 0 ) {
                        continue;
                    }

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

                    $affiliatepress_commission_rules = $affiliatepress_tracking->affiliatepress_calculate_commission_amount(  $affiliatepress_product_total, '', $affiliatepress_args );

                    $affiliatepress_single_product_commission_amount = (isset($affiliatepress_commission_rules['commission_amount']))?floatval($affiliatepress_commission_rules['commission_amount']):0;

                    $affiliatepress_commission_amount += $affiliatepress_single_product_commission_amount;

                    $affiliatepress_order_referal_amount += $affiliatepress_product_total;

                    $affiliatepress_allow_products_commission[] = array(
                        'product_id'           => $affiliatepress_product_id,
                        'product_name'         => $affiliatepress_product_name,
                        'order_id'             => $affiliatepress_order_id,
                        'commission_amount'    => $affiliatepress_single_product_commission_amount,
                        'order_referal_amount' => $affiliatepress_product_total,
                        'commission_basis'     => 'per_product',
                        'discount_val'         =>  ((isset($affiliatepress_commission_rules['discount_val']))?$affiliatepress_commission_rules['discount_val']:0),
                        'discount_type'        =>  ((isset($affiliatepress_commission_rules['discount_type']))?$affiliatepress_commission_rules['discount_type']:NULL),
                    );

                    $affiliatepress_commission_products_ids[] = $affiliatepress_product_id;
                    $affiliatepress_commission_products_name[] = $affiliatepress_product_name;
                }

            }

            if(empty($affiliatepress_allow_products_commission)){
                $affiliatepress_debug_log_msg = 'Commission Product Not Found.';
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Product Not Found', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                return;
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' Commission Product', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', wp_json_encode($affiliatepress_allow_products_commission), $affiliatepress_commission_debug_log_id);

            $affiliatepress_commission_final_validation = array();

            $affiliatepress_commission_final_validation = apply_filters( 'affiliatepress_commission_final_validation', $affiliatepress_commission_final_validation, $affiliatepress_affiliate_id,$this->affiliatepress_integration_slug, $affiliatepress_commission_amount, $affiliatepress_order_id, $affiliatepress_user);

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
                'ap_commission_status'	         => 2,
                'ap_commission_reference_id'     => $affiliatepress_order_id,
                'ap_commission_product_ids'      => $affiliatepress_commission_products_ids_string,
                'ap_commission_reference_detail' => html_entity_decode($affiliatepress_commission_products_name_string),
                'ap_commission_source'           => $this->affiliatepress_integration_slug,
                'ap_commission_amount'           => $affiliatepress_commission_amount,
                'ap_commission_reference_amount' => $affiliatepress_order_referal_amount,
                'ap_commission_order_amount'     => $affiliatepress_order_totals->grand_total,
                'ap_commission_currency'         => $AffiliatePress->affiliatepress_get_default_currency_code(),
                'ap_commission_ip_address'       => $affiliatepress_ip_address,
                'ap_customer_id'                 => $affiliatepress_customer_id,
                'ap_commission_created_date'     => date('Y-m-d H:i:s',current_time('timestamp') )// phpcs:ignore
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
         * Mark referral as complete when payment is completed
         *
         * @param  int $affiliatepress_order_id
         * @return void
         */
        public function affiliatepress_mark_referral_complete_easycart( $affiliatepress_order_id ) {

            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_debug_log_id ,$affiliatepress_tracking;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            $affiliatepress_order_id = !empty($affiliatepress_order_id) ? intval($affiliatepress_order_id) : 0;

            $affiliatepress_tbl_ap_affiliate_commissions_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_commissions contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_commission_id = intval($wpdb->get_var($wpdb->prepare("SELECT ap_commission_id FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} Where ap_commission_source = %s  AND ap_commission_reference_id = %d AND ap_commission_status = 2",$this->affiliatepress_integration_slug,$affiliatepress_order_id))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions_temp is a table name. false alarm

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
         * Function For Check Plugin Active
         *
         * @return void
         */
        function affiliatepress_check_plugin_active()
        {
            $affiliatepress_flag = true;

            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            if ( !is_plugin_active( 'wp-easycart/wpeasycart.php' ) ) {
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;
        }
        
        /**
         * Function For Check Integration Settings add 
         *
         * @return void
         */
        function affiliatepress_easycart_commission_add(){
            global $AffiliatePress;
            $affiliatepress_flag = true;
            $affiliatepress_enable_wp_easycart = $AffiliatePress->affiliatepress_get_settings('enable_wp_easycart', 'integrations_settings');
            if($affiliatepress_enable_wp_easycart != 'true'){
                $affiliatepress_flag = false;
            }

            return $affiliatepress_flag;
        }
        
        /**
         * Function For Add Pending Commission
         *
         * @param  int $affiliatepress_order_id
         * @param  int $affiliatepress_orderstatus_id
         * @return void
         */
        function affiliatepress_pending_commission_on_status_change_easycart($affiliatepress_order_id, $affiliatepress_orderstatus_id)
        {
            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id;

            $affiliatepress_order_id = !empty($affiliatepress_order_id) ? $affiliatepress_order_id : 0;

            if ($affiliatepress_orderstatus_id == '4' ){

                $affiliatepres_all_commission_data = $AffiliatePress->affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id, $this->affiliatepress_integration_slug);
                if(!empty($affiliatepres_all_commission_data)){

                    foreach($affiliatepres_all_commission_data as $affiliatepress_commission_data){

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
                                $affiliatepress_debug_log_msg = sprintf( 'Commission #%s successfully marked as pending, after order #%s pending status change.', $affiliatepress_ap_commission_id, $affiliatepress_order_id );
        
                                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Penidng ', 'affiliatepress_easycart_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
        
                            }
                        }

                    }

                }

            }
        }
        
        /**
         * Function For Add Approved commission
         *
         * @param  int $affiliatepress_order_id
         * @param  int $affiliatepress_orderstatus_id
         * @return void
         */
        function affiliatepress_completed_commission_on_status_change_easycart($affiliatepress_order_id, $affiliatepress_orderstatus_id){

            global $AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_commission_debug_log_id,$affiliatepress_tracking;

            $affiliatepress_order_id = !empty($affiliatepress_order_id) ? $affiliatepress_order_id : 0;

            $affiliatepress_default_commission_status = $affiliatepress_tracking->affiliatepress_get_default_commission_status();

            if ($affiliatepress_orderstatus_id == '3' ){

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
                            if($affiliatepress_ap_commission_id != 0){
        
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
        
                                if($affiliatepress_updated_commission_status == 1){
                                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data, array( 'ap_commission_id' => $affiliatepress_ap_commission_id ));
                                    $affiliatepress_debug_log_msg = sprintf('Pending commission #%s successfully marked as completed.', $affiliatepress_ap_commission_id );
                    
                                    do_action('affiliatepress_after_commissions_status_change',$affiliatepress_ap_commission_id,1,2);
                    
                                    do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $this->affiliatepress_integration_slug.' : Commission Approve ', 'affiliatepress_'.$this->affiliatepress_integration_slug.'_commission_tracking', $affiliatepress_debug_log_msg, $affiliatepress_commission_debug_log_id);
                                }
        
                            }
                        }

                    }

                }


            }
        }
        
       

    }
}
global $affiliatepress_easycart;
$affiliatepress_easycart = new affiliatepress_easycart();