<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_affiliate_panel') ) {
    class affiliatepress_affiliate_panel Extends AffiliatePress_Core{
                        
        function __construct(){
            
            global $affiliatepress_affiliate_panel_page_url, $affiliatepress_affiliate_user_allowed_to_acceess, $affiliatepress_affiliate_user_access_affiliate_panel;

            $affiliatepress_affiliate_user_allowed_to_acceess = "";
            $affiliatepress_affiliate_user_access_affiliate_panel = "";

            /**Function for affiliate registration page shortcode  */
            add_shortcode('affiliatepress_affiliate_panel', array($this,'affiliatepress_affiliate_panel_func'));

            /**Function for add register fields dynamic data */
            add_filter('affiliatepress_affiliate_panel_dynamic_data_fields',array($this,'affiliatepress_affiliate_panel_dynamic_data_fields_func'),10,1);

            /**Function for display vue method */
            add_filter('affiliatepress_affiliate_panel_dynamic_vue_methods',array($this,'affiliatepress_affiliate_panel_dynamic_vue_methods_func'),10,1);

            /* Function for add forgotpassword functionality */
            add_action('wp_ajax_nopriv_affiliatepress_forgot_password_account', array($this, 'affiliatepress_forgot_password_account_func'), 10);

            /* Login functionality */
            add_action('wp_ajax_nopriv_affiliatepress_affiliate_login_account', array($this, 'affiliatepress_affiliate_login_account_func'), 10); 
            add_action('wp_ajax_affiliatepress_affiliate_login_account', array($this, 'affiliatepress_affiliate_login_account_func'), 10);
            
            /**Function for set remember cookie */
            add_filter('auth_cookie_expiration', array($this,'affiliatepress_custom_remember_me_duration'), 10, 3);
            
            /**Function for add common svg code */
            add_action('affiliatepress_common_affiliate_panel_svg_code',array($this,'affiliatepress_common_affiliate_panel_svg_code_func'),10,1);

            /**Function for add dynamic helper variable */
            add_filter('affiliatepress_affiliate_panel_dynamic_constant_define',array($this,'affiliatepress_affiliate_panel_dynamic_constant_define_func'),10,1);

            /* Get Affiliates Commission */
            add_action('wp_ajax_affiliatepress_get_affiliate_commissions', array( $this, 'affiliatepress_get_affiliate_commissions_func' ));

            /* Get Affiliates Visits */
            add_action('wp_ajax_affiliatepress_get_affiliate_visits', array( $this, 'affiliatepress_get_affiliate_visits_func' ));      
            
            /* Get Affiliates Creative */
            add_action('wp_ajax_affiliatepress_get_affiliate_creatives', array( $this, 'affiliatepress_get_affiliate_creatives_func' )); 

            /* Get Affiliates Edit Profile Data */
            add_action('wp_ajax_affiliatepress_get_affiliate_edit_profile_data', array( $this, 'affiliatepress_get_affiliate_edit_profile_data_func' ));             

            /* Get Affiliates Edit Profile Data */
            add_action('wp_ajax_affiliatepress_save_edit_profile', array( $this, 'affiliatepress_save_edit_profile_data_func' )); 

            /* Change Password Functionality */
            add_action('wp_ajax_affiliatepress_affiliate_panel_change_password', array( $this, 'affiliatepress_affiliate_panel_change_password_func' )); 
            
            /* Affiltate Link Functionality */
            add_action('wp_ajax_affiliatepress_get_affiliates_links', array( $this, 'affiliatepress_get_affiliates_links_func' ));             

            /* Add Affiliate Custom Link */
            add_action('wp_ajax_affiliatepress_add_affiliate_custom_link',array($this,'affiliatepress_add_affiliate_custom_link_func'));

            /* Get Affiliate Dahboard Data */
            add_action('wp_ajax_affiliatepress_get_dashboard_data',array($this,'affiliatepress_get_dashboard_data_func')); 
            
            add_filter('affiliatepress_affiliate_panel_dynamic_on_load_methods',array($this,'affiliatepress_affiliate_panel_dynamic_on_load_methods_func'),10);

            /* Get Affiliates Payments */
            add_action('wp_ajax_affiliatepress_get_affiliate_payments', array( $this, 'affiliatepress_get_affiliate_payments_func' ));

            /* Send a Close Account Request */
            add_action('wp_ajax_affiliatepress_close_account_request', array( $this, 'affiliatepress_close_account_request_func' ));

            /* Upload Profile Image */
            add_action('wp_ajax_affiliatepress_upload_edit_profile_image', array( $this, 'affiliatepress_upload_edit_profile_image_func' ), 10);

            add_action('wp_ajax_affiliatepress_update_nonce_page_load', array( $this, 'affiliatepress_update_nonce_page_load_func' ), 10);
            add_action('wp_ajax_nopriv_affiliatepress_update_nonce_page_load', array( $this, 'affiliatepress_update_nonce_page_load_func' ), 10);

            /* Delete Affiliate */
            add_action('wp_ajax_affiliatepress_delete_affiliate_custome_link', array( $this, 'affiliatepress_delete_affiliate_custome_link_func' ));

        }

        function affiliatepress_update_nonce_page_load_func(){
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');
            
            $affiliatepress_updated_nonce = esc_html(wp_create_nonce('ap_wp_nonce'));

            if(!empty($affiliatepress_updated_nonce)){
                $response['variant'] = 'success';
                $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['affiliatepress_updated_nonce'] = $affiliatepress_updated_nonce;
            }

            echo wp_json_encode($response);
            exit;
        }
                
                
        /**
         * Function for return file size formated data
         *
         * @param  mixed $affiliatepress_bytes
         * @return void
        */
        function affiliatepress_format_file_size($affiliatepress_bytes) {
            if ($affiliatepress_bytes < 1024) {
                return $affiliatepress_bytes . ' Bytes';
            } elseif ($affiliatepress_bytes < 1048576) {
                return round($affiliatepress_bytes / 1024, 2) . ' KB';
            } elseif ($affiliatepress_bytes < 1073741824) {
                return round($affiliatepress_bytes / 1048576, 2) . ' MB';
            } else {
                return round($affiliatepress_bytes / 1073741824, 2) . ' GB';
            }
        }        

        /**
         * Function for display creative image data 
         *
         * @param  mixed $affiliatepress_imagePath
         * @return void
        */
        function affiliatepress_get_image_info($affiliatepress_imagePath){

            if(!file_exists($affiliatepress_imagePath)){
                return [
                    'width' => '',
                    'height' => '',
                    'mime' => '',
                    'type' => '',
                    'fileSize' => '',
                ];
            }
                    
            $affiliatepress_imageSize = getimagesize($affiliatepress_imagePath);
            if($affiliatepress_imageSize === false){
                return [
                    'width' => '',
                    'height' => '',
                    'mime' => '',
                    'type' => '',
                    'fileSize' => '',
                ];
            }
        
            $width = $affiliatepress_imageSize[0];
            $affiliatepress_height = $affiliatepress_imageSize[1];
            $affiliatepress_mime = $affiliatepress_imageSize['mime'];
            $affiliatepress_fileSize = intval(filesize($affiliatepress_imagePath));   
            $affiliatepress_fileSize = $this->affiliatepress_format_file_size($affiliatepress_fileSize);                 
            $affiliatepress_type = (!empty($affiliatepress_mime))?strtoupper(str_replace('image/','',$affiliatepress_mime)):'';
            return [
                'width' => $width,
                'height' => $affiliatepress_height,
                'mime' => $affiliatepress_mime,
                'type' => $affiliatepress_type,
                'fileSize' => $affiliatepress_fileSize,
            ];
        }

        /**
         * Function for close account request 
         *
         * @return void
        */
        function affiliatepress_close_account_request_func(){
            
            global $wpdb, $AffiliatePress, $affiliatepress_email_notifications,$affiliatepress_affiliates;
            
            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            $response['variant'] = 'error';
            $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__('Something wrong...', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }
            
            if($affiliatepress_affiliate_id){

                $affiliatepress_self_close_account =  $AffiliatePress->affiliatepress_get_settings('affiliate_user_self_closed_account', 'affiliate_settings');
                if($affiliatepress_self_close_account == "false"){
                    wp_send_json($response);
                    exit();                
                }

                $affiliatepress_affiliates->affiliatepress_affiliate_delete_data($affiliatepress_affiliate_id);
                $affiliatepress_affiliate_account_page_id = $AffiliatePress->affiliatepress_get_settings('affiliate_account_page_id', 'affiliate_settings');
                $affiliatepress_affiliate_login_page_url  = get_permalink($affiliatepress_affiliate_account_page_id);  
                $affiliatepress_affiliate_login_page_url = apply_filters('affiliatepress_modify_affiliate_panel_redirect_link', $affiliatepress_affiliate_login_page_url);
                $affiliatepress_affiliate_login_page_url = add_query_arg( 'ap-nocache',current_time('timestamp'),$affiliatepress_affiliate_login_page_url);
                wp_logout();

                $response['variant'] = 'success';
                $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('account_closure_request_success', 'message_settings'));
                $response['delete_account_url'] = $affiliatepress_affiliate_login_page_url;
                wp_send_json($response);
                exit(); 

            }



        }

         /**
         * Function for upload affiliate avatar
         *
         * @return json
        */
        function affiliatepress_upload_edit_profile_image_func(){

            global $AffiliatePress;

            $return_data = array(
                'error'            => 0,
                'msg'              => '',
                'upload_url'       => '',
                'upload_file_name' => '',
            );//phpcs:ignore

            $response              = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__('Something wrong...', 'affiliatepress-affiliate-marketing');
            
            $affiliatepress_wpnonce               = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_upload_edit_profile_image');

            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }

            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }

            $affiliatepress_fileupload_obj = new affiliatepress_fileupload_class( $_FILES['file'] ); //phpcs:ignore
            if (! $affiliatepress_fileupload_obj ) {
                $return_data['error'] = 1;
                $return_data['msg']   = $affiliatepress_fileupload_obj->error_message;
            }
            
            $affiliatepress_fileupload_obj->affiliatepress_check_nonce        = true;
            $affiliatepress_fileupload_obj->affiliatepress_nonce_data         = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''; // phpcs:ignore 
            $affiliatepress_fileupload_obj->affiliatepress_nonce_action       = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : ''; // phpcs:ignore 
            $affiliatepress_fileupload_obj->affiliatepress_check_only_image   = true;
            $affiliatepress_fileupload_obj->affiliatepress_check_specific_ext = false;
            $affiliatepress_fileupload_obj->affiliatepress_allowed_ext        = array();
            $affiliatepress_file_name                = isset($_FILES['file']['name']) ? current_time('timestamp') . '_' . sanitize_file_name($_FILES['file']['name']) : ''; // phpcs:ignore 
            $affiliatepress_upload_dir               = AFFILIATEPRESS_TMP_IMAGES_DIR . '/';
            $affiliatepress_upload_url               = AFFILIATEPRESS_TMP_IMAGES_URL . '/';
            $affiliatepress_destination = $affiliatepress_upload_dir . $affiliatepress_file_name;
            $affiliatepress_check_file = wp_check_filetype_and_ext( $affiliatepress_destination, $affiliatepress_file_name );
            if( empty( $affiliatepress_check_file['ext'] ) ){
                $return_data['error'] = 1;
                $return_data['upload_error'] = $affiliatepress_upload_file;
                $return_data['msg']   = esc_html__('Invalid file extension. Please select valid file', 'affiliatepress-affiliate-marketing');
            } else {
                $affiliatepress_upload_file = $affiliatepress_fileupload_obj->affiliatepress_process_upload($affiliatepress_destination);
                if ($affiliatepress_upload_file == false ) {
                    $return_data['error'] = 1;
                    $return_data['msg']   = ! empty($affiliatepress_upload_file->error_message) ? $affiliatepress_upload_file->error_message : esc_html__('Something went wrong while updating the file', 'affiliatepress-affiliate-marketing');
                } else {
                    $return_data['error']            = 0;
                    $return_data['msg']              = '';
                    $return_data['upload_url']       = $affiliatepress_upload_url . $affiliatepress_file_name;
                    $return_data['upload_file_name'] = $affiliatepress_file_name;
                }
            }
            
            echo wp_json_encode($return_data);
            exit();

        }  

        /**
         * Function for get affiliate commission data
         *
         * @return void
        */
        function affiliatepress_get_affiliate_payments_func(){

            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_affiliate_form_fields, $affiliatepress_tbl_ap_affiliates, $affiliatepress_affiliates, $affiliatepress_global_options, $affiliatepress_tbl_ap_payments;

            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            $response['variant'] = 'error';
            $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__('Something wrong...', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }

            $affiliatepress_perpage     = isset($_POST['perpage']) ? intval($_POST['perpage']) : 10; // phpcs:ignore 
            $affiliatepress_currentpage = isset($_POST['currentpage']) ? intval($_POST['currentpage']) : 1; // phpcs:ignore
            $affiliatepress_offset      = ( ! empty($affiliatepress_currentpage) && $affiliatepress_currentpage > 1 ) ? ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage ) : 0;
            $affiliatepress_order       = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : ''; // phpcs:ignore
            $affiliatepress_order_by    = isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : ''; // phpcs:ignore            
            $affiliatepress_search_query = '';
            $affiliatepress_where_clause = " WHERE 1 = 1 ";
            $affiliatepress_where_clause.= $wpdb->prepare( " AND (payments.ap_affiliates_id = %d) ", $affiliatepress_affiliate_id);
            if(!empty($_REQUEST['search_data'])){               
                if(isset($_REQUEST['search_data']['ap_payment_created_date'])){// phpcs:ignore
                    $affiliatepress_start_date = (isset($_REQUEST['search_data']['ap_payment_created_date'][0]))?sanitize_text_field($_REQUEST['search_data']['ap_payment_created_date'][0]):'';// phpcs:ignore
                    $affiliatepress_end_date   = (isset($_REQUEST['search_data']['ap_payment_created_date'][1]))?sanitize_text_field($_REQUEST['search_data']['ap_payment_created_date'][1]):'';// phpcs:ignore
                    if(!empty($affiliatepress_start_date) && !empty($affiliatepress_end_date)){                        
                        $affiliatepress_start_date = date('Y-m-d',strtotime($affiliatepress_start_date));// phpcs:ignore
                        $affiliatepress_end_date = date('Y-m-d',strtotime($affiliatepress_end_date));// phpcs:ignore
                        $affiliatepress_where_clause.= $wpdb->prepare( " AND (DATE(payments.ap_payment_created_date) >= %s AND DATE(payments.ap_payment_created_date) <= %s) ", $affiliatepress_start_date, $affiliatepress_end_date);
                    }                    
                }                                
                if (isset($_REQUEST['search_data']['payment_status']) && !empty($_REQUEST['search_data']['payment_status']) ) {// phpcs:ignore
                    $affiliatepress_payment_status = intval($_REQUEST['search_data']['payment_status']);// phpcs:ignore
                    $affiliatepress_where_clause.= $wpdb->prepare( " AND (payments.ap_payment_status = %d) ", $affiliatepress_payment_status);
                }
            }
            $affiliatepress_tbl_ap_payments_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_payments); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_payments_temp contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_get_total_payments = intval($wpdb->get_var("SELECT count(payments.ap_payment_id) FROM {$affiliatepress_tbl_ap_payments_temp} as payments  {$affiliatepress_where_clause}")); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_payments is a table name. false alarm
            if(empty($affiliatepress_order)){
                $affiliatepress_order = 'DESC';
            }
            if(empty($affiliatepress_order_by)){
                $affiliatepress_order_by = 'payments.ap_payment_id';
            }
            $affiliatepress_affiliate_payments_record = $wpdb->get_results("SELECT payments.ap_payment_id, payments.ap_payment_created_date, payments.ap_payment_method, payments.ap_payment_amount, payments.ap_payment_currency, payments.ap_payment_status FROM {$affiliatepress_tbl_ap_payments_temp} as payments {$affiliatepress_where_clause}  order by {$affiliatepress_order_by} {$affiliatepress_order} LIMIT {$affiliatepress_offset} , {$affiliatepress_perpage}", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_payments_temp is a table name. false alarm                         

            $affiliatepress_payments = array();
            if(!empty($affiliatepress_affiliate_payments_record)){

                $affiliatepress_options = $affiliatepress_global_options->affiliatepress_global_options();
                $affiliatepress_all_payment_status = $affiliatepress_options['payment_status'];
                $affiliatepress_all_payment_status_list = array();
                if(!empty($affiliatepress_all_payment_status)){
                    foreach($affiliatepress_all_payment_status as $affiliatepress_payment_status){
                        $affiliatepress_all_payment_status_list[$affiliatepress_payment_status['value']] = $affiliatepress_payment_status['text'];
                    }
                }

                $affiliatepress_counter = 1;
                foreach($affiliatepress_affiliate_payments_record as $affiliatepress_key=>$affiliatepress_single_payment){

                    $affiliatepress_payment = $affiliatepress_single_payment; 
                    $affiliatepress_payment_amount_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_single_payment['ap_payment_amount']);                    
                    $affiliatepress_payment['ap_formated_payment_amount'] = $affiliatepress_payment_amount_amount;
                    $affiliatepress_payment_status_name = (isset($affiliatepress_all_payment_status_list[$affiliatepress_single_payment['ap_payment_status']]))?$affiliatepress_all_payment_status_list[$affiliatepress_single_payment['ap_payment_status']]:'';
                    $affiliatepress_payment['payment_status_name'] = $affiliatepress_payment_status_name;
                    $affiliatepress_payment['ap_payment_created_date_formated'] = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_single_payment['ap_payment_created_date']);
                    $affiliatepress_payment['payment_method_name'] = ucfirst($affiliatepress_single_payment['ap_payment_method']);
                    $affiliatepress_payments[] = $affiliatepress_payment;

                }
            }
            $affiliatepress_payments_pagination_count = ceil(intval($affiliatepress_get_total_payments) / $affiliatepress_perpage);

            $affiliatepress_panel_labels = $this->affiliatepress_get_panel_lables();
            $affiliatepress_pagination_lable = $this->affiliatepress_get_pagination_lable();
            $affiliatepress_pagination_item_count = count($affiliatepress_payments);
            $affiliatepress_pagination_lable = str_replace(['[start]', '[total]'],[$affiliatepress_pagination_item_count, $affiliatepress_get_total_payments],$affiliatepress_pagination_lable);

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');            
            $response['items'] = $affiliatepress_payments;
            $response['total'] = intval($affiliatepress_get_total_payments);    
            $response['payments_pagination_count'] = intval($affiliatepress_payments_pagination_count);
            $response['payments_pagination_label'] = $affiliatepress_pagination_lable;  
            $response['affiliate_panel_labels']     = $affiliatepress_panel_labels;                 
            wp_send_json($response);
            exit; 
            
        }        

        /**
         * Function for add dynamic on load method
         *
         * @param  mixed $affiliatepress_affiliate_panel_dynamic_on_load_methods
         * @return void
         */
        function affiliatepress_affiliate_panel_dynamic_on_load_methods_func($affiliatepress_affiliate_panel_dynamic_on_load_methods){

            global $affiliatepress_notification_duration;

            $affiliatepress_affiliate_panel_dynamic_on_load_methods.='
                var vm = this;
                var container = document.getElementById("ap-vue-cont-id");
                var url = new URL(window.location.href);
                if(url.searchParams.has("ap-nocache")){
                    url.searchParams.delete("ap-nocache");
                    window.history.replaceState({}, document.title, url.toString());
                    if (!sessionStorage.getItem("ap_uid_cleaned")) {
                        sessionStorage.setItem("ap_uid_cleaned", "1");
                        window.location.reload();
                    }
                }
                function getScreenSize(width) {
                        if (width >= 1200) return "desktop";
                        else if (width < 1200 && width >= 768) return "tablet";
                        else if (width < 768) return "mobile";
                }
                function updateScreenSize() {
                        if (!container) return;
                        var width = container.offsetWidth;
                        vm.current_screen_size = getScreenSize(width);
                }
                function waitForStableContainerWidth(callback) {
                        var lastWidth = 0;
                        var stableCount = 0;
                        var maxTries = 20;
                        function check() {
                            if (!container) return;
                            var currentWidth = container.offsetWidth;
                            if (currentWidth === lastWidth) {
                                stableCount++;
                            } else {
                                stableCount = 0;
                                lastWidth = currentWidth;
                            }
                            if (stableCount >= 3 || maxTries <= 0) {
                                callback();
                            } else {
                                maxTries--;
                                requestAnimationFrame(check);
                            }
                        }
                        check();
                }
                waitForStableContainerWidth(function() {
                        updateScreenSize();
                });
                window.addEventListener("resize", function(event) {
                        updateScreenSize();
                });
                setTimeout(function(){
                    updateScreenSize();
                    const postData = new FormData();
                    postData.append("action", "affiliatepress_update_nonce_page_load");
                    postData.append("_wpnonce", "'.esc_html(wp_create_nonce('ap_wp_nonce')).'");
                    axios.post( affiliatepress_ajax_obj.ajax_url, postData )
                    .then( function (response) {
                        if(response.data.variant == "success"){
                            document.getElementById("_wpnonce").value = response.data.affiliatepress_updated_nonce;
                            if(vm.is_login == "true" && vm.allow_user_access == "true"){
                                vm.affiliatepress_change_tab("dashboard",true);
                                vm.is_display_tab_content_loader = "1";
                                vm.affiliatepress_onload_func();
                            }
                        }else{ 
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",                                
                                duration:'.intval($affiliatepress_notification_duration).',
                            });    
                        }
                    }.bind(this) )
                    .catch( function (error) {   
                        console.log(error);
                    });
                    vm.affiliatepress_load_panel_form(); 
                },1000);                
            ';

            return $affiliatepress_affiliate_panel_dynamic_on_load_methods;
        }
                
        /**
         * Function for get dashboard data
         *
         * @return void
        */
        function affiliatepress_get_dashboard_data_func(){
           
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_tbl_ap_affiliate_visits, $AffiliatePress, $affiliatepress_tbl_ap_affiliate_report, $affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_settings;

            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }            
            $affiliatepress_dashboard_start_date       = (isset($_POST['dashboard_date_range'][0]) && !empty($_POST['dashboard_date_range'][0])) ? sanitize_text_field($_POST['dashboard_date_range'][0]): ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $affiliatepress_dashboard_end_date         = (isset($_POST['dashboard_date_range'][1]) && !empty($_POST['dashboard_date_range'][1])) ? sanitize_text_field($_POST['dashboard_date_range'][1]): ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

            $affiliatepress_dashboard_total_earning    = "";
            $affiliatepress_dashboard_paid_earning     = "";
            $affiliatepress_dashboard_unpaid_earning   = "";
            $affiliatepress_dashboard_total_commission = "";

            if(!empty($affiliatepress_dashboard_start_date) && !empty($affiliatepress_dashboard_end_date)){

                $affiliatepress_dashboard_total_earning    = 0;
                $affiliatepress_dashboard_paid_earning     = 0;
                $affiliatepress_dashboard_unpaid_earning   = 0;
                $affiliatepress_dashboard_total_commission = 0;
                $affiliatepress_dashboard_total_visits     = 0;

                $affiliatepress_dashboard_report_data = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(ap_affiliate_report_total_commission) as total_commission_count,  sum(ap_affiliate_report_visits) as total_visits_count, SUM(ap_affiliate_report_total_commission_amount) as total_commission_amount, sum(ap_affiliate_report_paid_commission_amount) as paid_commission_amount, sum(ap_affiliate_report_unpaid_commission_amount) as unpaid_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report Inner Join {$affiliatepress_tbl_ap_affiliates} as affiliate ON report.ap_affiliates_id = affiliate.ap_affiliates_id  WHERE affiliate.ap_affiliates_id = %d AND DATE(ap_affiliate_report_date) >= %s AND DATE(ap_affiliate_report_date) <= %s",$affiliatepress_affiliate_id, $affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm
                
                
                if(!empty($affiliatepress_dashboard_report_data)){

                    $affiliatepress_dashboard_total_earning       = floatval($affiliatepress_dashboard_report_data['total_commission_amount']);
                    $affiliatepress_dashboard_paid_earning        = floatval($affiliatepress_dashboard_report_data['paid_commission_amount']);
                    $affiliatepress_dashboard_unpaid_earning      = floatval($affiliatepress_dashboard_report_data['unpaid_commission_amount']);
                    $affiliatepress_dashboard_total_commission    = intval($affiliatepress_dashboard_report_data['total_commission_count']);
                    $affiliatepress_dashboard_total_visits        = intval($affiliatepress_dashboard_report_data['total_visits_count']);

                }

                $affiliatepress_dashboard_total_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_total_earning,2));
                $affiliatepress_dashboard_paid_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_paid_earning,2));  
                $affiliatepress_dashboard_unpaid_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_unpaid_earning,2));

            }else{

                $affiliatepress_dashboard_total_earning    = 0;
                $affiliatepress_dashboard_paid_earning     = 0;
                $affiliatepress_dashboard_unpaid_earning   = 0;
                $affiliatepress_dashboard_total_commission = 0;
                $affiliatepress_dashboard_total_visits     = 0;

                $affiliatepress_dashboard_report_data = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(ap_affiliate_report_total_commission) as total_commission_count,  sum(ap_affiliate_report_visits) as total_visits_count, SUM(ap_affiliate_report_total_commission_amount) as total_commission_amount, sum(ap_affiliate_report_paid_commission_amount) as paid_commission_amount, sum(ap_affiliate_report_unpaid_commission_amount) as unpaid_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report Inner Join {$affiliatepress_tbl_ap_affiliates} as affiliate ON report.ap_affiliates_id = affiliate.ap_affiliates_id  WHERE affiliate.ap_affiliates_id = %d ",$affiliatepress_affiliate_id ), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm
                
                
                if(!empty($affiliatepress_dashboard_report_data)){

                    $affiliatepress_dashboard_total_earning       = floatval($affiliatepress_dashboard_report_data['total_commission_amount']);
                    $affiliatepress_dashboard_paid_earning        = floatval($affiliatepress_dashboard_report_data['paid_commission_amount']);
                    $affiliatepress_dashboard_unpaid_earning      = floatval($affiliatepress_dashboard_report_data['unpaid_commission_amount']);
                    $affiliatepress_dashboard_total_commission    = intval($affiliatepress_dashboard_report_data['total_commission_count']);
                    $affiliatepress_dashboard_total_visits        = intval($affiliatepress_dashboard_report_data['total_visits_count']);

                }

                $affiliatepress_dashboard_total_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_total_earning,2));
                $affiliatepress_dashboard_paid_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_paid_earning,2));  
                $affiliatepress_dashboard_unpaid_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_unpaid_earning,2));

            }

            $affiliatepress_revenue_chart_data = array(
                'labels'           => array(),
                'earning_values'   => array(),
                'commission_count' => array(),
            );

            $affiliatepress_default_primary_color          = '#6858e0';

            $affiliatepress_primary_color = $AffiliatePress->affiliatepress_get_settings('primary_color', 'appearance_settings');            
            if(empty($affiliatepress_primary_color)){
                $affiliatepress_primary_color = $affiliatepress_default_primary_color;
            }

            $affiliatepress_primary_opacity_color = $AffiliatePress->affiliatepress_hex_to_rgb_color($affiliatepress_primary_color,0.2);

            $affiliatepress_earnings_graph_label = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('dashboard_chart_earnings', 'message_settings'));
            $affiliatepress_commissions_graph_label = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('dashboard_chart_commisisons', 'message_settings'));
            $affiliatepress_revenue_chart_other_data = array(
                'earnings_graph_color' => $affiliatepress_primary_color,
                'earnings_graph_bgcolor' => $affiliatepress_primary_opacity_color,
                'earnings_graph_label' => $affiliatepress_earnings_graph_label,
                'commissions_graph_label' => $affiliatepress_commissions_graph_label,
            );

            if(empty($affiliatepress_dashboard_start_date) && empty($affiliatepress_dashboard_end_date)){                
                $affiliatepress_currentDate = new DateTime();
                $affiliatepress_dashboard_end_date = $affiliatepress_currentDate->format('Y-m-d');
                $affiliatepress_currentDate->modify('-3 years');
                $affiliatepress_dashboard_start_date = $affiliatepress_currentDate->format('Y-m-d');            
            }
            $affiliatepress_total_year_dates = $AffiliatePress->affiliatepress_get_date_between_year($affiliatepress_dashboard_start_date, $affiliatepress_dashboard_end_date);
            $affiliatepress_total_months_dates = $AffiliatePress->affiliatepress_get_months_between($affiliatepress_dashboard_start_date, $affiliatepress_dashboard_end_date);


            $affiliatepress_all_between_date = $AffiliatePress->affiliatepress_get_dates_between($affiliatepress_dashboard_start_date, $affiliatepress_dashboard_end_date);

            $affiliatepress_has_dates_only = false;
            if(!empty($affiliatepress_all_between_date) && is_array($affiliatepress_all_between_date)){
                $affiliatepress_total_dates = count($affiliatepress_all_between_date);
                if($affiliatepress_total_dates <= 31){
                    $affiliatepress_has_dates_only = true;
                }
            }   
            if(!empty($affiliatepress_total_year_dates) && count($affiliatepress_total_year_dates) > 1 && count($affiliatepress_total_months_dates) > 12 &&  $affiliatepress_has_dates_only == false){
                if(!empty($affiliatepress_total_year_dates)){
                    foreach($affiliatepress_total_year_dates as $affiliatepress_key=>$affiliatepress_value){

                        $affiliatepress_revenue_chart_data['labels'][]   = $affiliatepress_value;   

                        $affiliatepress_day_total_commission = 0;
                        $affiliatepress_total_commission   = 0;

                        $affiliatepress_dashboard_report_data = $wpdb->get_row( $wpdb->prepare( "SELECT sum(ap_affiliate_report_total_commission) as total_commission_count, SUM(ap_affiliate_report_total_commission_amount) as total_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report WHERE report.ap_affiliates_id = %d  AND DATE_FORMAT(DATE(ap_affiliate_report_date),'%Y') = %s", $affiliatepress_affiliate_id, $affiliatepress_key), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                        if(!empty($affiliatepress_dashboard_report_data)){
                            $affiliatepress_day_total_commission = floatval($affiliatepress_dashboard_report_data['total_commission_amount']);
                            $affiliatepress_total_commission = intval($affiliatepress_dashboard_report_data['total_commission_count']);
                        }

                        $affiliatepress_revenue_chart_data['earning_values'][] = round($affiliatepress_day_total_commission,2);
                        $affiliatepress_revenue_chart_data['commission_count'][] =  $affiliatepress_total_commission;       
                    }
                }  
            }else if(!empty($affiliatepress_total_months_dates) && count($affiliatepress_total_months_dates) > 1 && $affiliatepress_has_dates_only == false){
                if(!empty($affiliatepress_total_months_dates)){
                    foreach($affiliatepress_total_months_dates as $affiliatepress_key=>$affiliatepress_value){

                        $affiliatepress_revenue_chart_data['labels'][] = $affiliatepress_value;

                        $affiliatepress_day_total_commission = 0;
                        $affiliatepress_total_commission   = 0;

                        $affiliatepress_dashboard_report_data = $wpdb->get_row( $wpdb->prepare( "SELECT sum(ap_affiliate_report_total_commission) as total_commission_count, SUM(ap_affiliate_report_total_commission_amount) as total_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report WHERE report.ap_affiliates_id = %d  AND DATE_FORMAT(DATE(ap_affiliate_report_date),'%m-%Y') = %s ", $affiliatepress_affiliate_id, $affiliatepress_key), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                        if(!empty($affiliatepress_dashboard_report_data)){
                            $affiliatepress_day_total_commission = floatval($affiliatepress_dashboard_report_data['total_commission_amount']);
                            $affiliatepress_total_commission = intval($affiliatepress_dashboard_report_data['total_commission_count']);
                        }

                        $affiliatepress_revenue_chart_data['earning_values'][] = round($affiliatepress_day_total_commission,2);
                        $affiliatepress_revenue_chart_data['commission_count'][] =  $affiliatepress_total_commission; 
                    }
                }                
            }else{
                $affiliatepress_all_between_date = $AffiliatePress->affiliatepress_get_dates_between($affiliatepress_dashboard_start_date, $affiliatepress_dashboard_end_date);
                $affiliatepress_chart_value = array();
                if(!empty($affiliatepress_all_between_date)){
                    foreach($affiliatepress_all_between_date as $affiliatepress_key=>$affiliatepress_value){

                        $affiliatepress_revenue_chart_data['labels'][] = $affiliatepress_value;

                        $affiliatepress_day_total_commission = 0;
                        $affiliatepress_total_commission   = 0;

                        $affiliatepress_dashboard_report_data = $wpdb->get_row( $wpdb->prepare( "SELECT sum(ap_affiliate_report_total_commission) as total_commission_count, SUM(ap_affiliate_report_total_commission_amount) as total_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report WHERE report.ap_affiliates_id = %d AND  DATE(ap_affiliate_report_date) = %s ", $affiliatepress_affiliate_id, $affiliatepress_key), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                        if(!empty($affiliatepress_dashboard_report_data)){
                            $affiliatepress_day_total_commission = floatval($affiliatepress_dashboard_report_data['total_commission_amount']);
                            $affiliatepress_total_commission = intval($affiliatepress_dashboard_report_data['total_commission_count']);
                        }

                        $affiliatepress_revenue_chart_data['earning_values'][]   = round($affiliatepress_day_total_commission,2);
                        $affiliatepress_revenue_chart_data['commission_count'][] =  $affiliatepress_total_commission;

                    }
                }
            }

            $affiliatepress_panel_labels = $this->affiliatepress_get_panel_lables();

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['dashboard_total_earning']    = $affiliatepress_dashboard_total_earning;
            $response['dashboard_paid_earning']     = $affiliatepress_dashboard_paid_earning;
            $response['dashboard_unpaid_earning']   = $affiliatepress_dashboard_unpaid_earning;
            $response['dashboard_total_commission'] = intval($affiliatepress_dashboard_total_commission);
            $response['dashboard_total_visits']     = intval($affiliatepress_dashboard_total_visits); 
            $response['revenue_chart_data']         = $affiliatepress_revenue_chart_data;      
            $response['revenue_chart_other_data']   = $affiliatepress_revenue_chart_other_data;
            $response['affiliate_panel_labels']     = $affiliatepress_panel_labels; 
            
            wp_send_json($response);
            exit();

        }

        function affiliatepress_get_panel_lables(){

            global $affiliatepress_tbl_ap_settings;

            $affiliatepress_tbl_ap_settings_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_settings); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_settings contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_panel_message_settings_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_settings_temp, '*', 'WHERE ap_setting_type = %s', array( 'message_settings' ), '', '', '', false, false, ARRAY_A);

            $affiliatepress_panel_labels = array();

            foreach ($affiliatepress_panel_message_settings_data as $setting) {
                $affiliatepress_panel_labels[$setting['ap_setting_name']] = stripslashes($setting['ap_setting_value']);
            }

            return $affiliatepress_panel_labels;
        }

        function affiliatepress_get_pagination_lable(){

            global $affiliatepress_tbl_ap_settings;

            $affiliatepress_tbl_ap_settings_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_settings); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_settings contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_panel_message_settings_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_settings_temp, 'ap_setting_value', 'WHERE ap_setting_type = %s AND ap_setting_name = %s', array( 'message_settings' , 'pagination' ), '', '', '', false, true, ARRAY_A);
            
            $affiliatepress_panel_message_settings_data = isset($affiliatepress_panel_message_settings_data['ap_setting_value']) ? $affiliatepress_panel_message_settings_data['ap_setting_value'] : '';
            return $affiliatepress_panel_message_settings_data;
        }

        /**
         * Function for add affiliate custom link
         *
         * @return void
        */
        function affiliatepress_add_affiliate_custom_link_func(){

            global $wpdb, $affiliatepress_tbl_ap_affiliate_links, $AffiliatePress;

            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }
            
            $affiliatepress_page_link        = (isset($_POST['ap_page_link']) && !empty($_POST['ap_page_link'])) ? trim(sanitize_text_field($_POST['ap_page_link'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $affiliatepress_affiliates_campaign_name        = (isset($_POST['ap_affiliates_campaign_name']) && !empty($_POST['ap_affiliates_campaign_name'])) ? trim(sanitize_text_field($_POST['ap_affiliates_campaign_name'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $affiliatepress_affiliates_sub_id        = (isset($_POST['ap_affiliates_sub_id']) && !empty($_POST['ap_affiliates_sub_id'])) ? trim(sanitize_text_field($_POST['ap_affiliates_sub_id'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $affiliatepress_affiliate_link = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_links, '*', 'WHERE ap_affiliates_id = %d AND ap_affiliates_campaign_name = %s AND ap_affiliates_sub_id = %s', array($affiliatepress_affiliate_id,$affiliatepress_affiliates_campaign_name,$affiliatepress_affiliates_sub_id), '', 'order by ap_affiliate_link_id ASC', '', false, true,ARRAY_A);

            if(!empty($affiliatepress_affiliate_link)){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('campaign_name_already_added', 'message_settings'));
                wp_send_json($response);
                exit();                 
            }else{

                $affiliatepress_affiliate_links = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_links, '*', 'WHERE ap_affiliates_id = %d ', array($affiliatepress_affiliate_id), '', 'order by ap_affiliate_link_id ASC', '', false, false,ARRAY_A);

                $affiliatepress_total_link = count($affiliatepress_affiliate_links);
                $affiliatepress_link_limit = $AffiliatePress->affiliatepress_get_settings('affiliate_link_limit', 'affiliate_settings');
                if($affiliatepress_link_limit != 0 && $affiliatepress_total_link >=$affiliatepress_link_limit ){
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('link_limit_reached_error', 'message_settings'));
                }else{
                    $affiliatepress_args = array(
                        'ap_affiliates_id'            => $affiliatepress_affiliate_id,
                        'ap_page_link'                => $affiliatepress_page_link,
                        'ap_affiliates_sub_id'        => $affiliatepress_affiliates_sub_id,
                        'ap_affiliates_campaign_name' => $affiliatepress_affiliates_campaign_name,
                    );
                    $affiliatepress_affiliates_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_affiliate_links, $affiliatepress_args);

                    $response['variant'] = 'success';
                    $response['title']   = esc_html__( 'Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_custom_link_added', 'message_settings'));
                }
            }

            wp_send_json($response);
            exit;            
            
        }
        
        /**
         * Function for get affiliate links
         *
         * @return void
        */
        function affiliatepress_get_affiliates_links_func(){
            global $wpdb, $affiliatepress_tbl_ap_affiliate_links, $AffiliatePress;

            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }
            
            $affiliatepress_affiliate_custom_links = array();
            $affiliatepress_affiliate_links = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_links, '*', 'WHERE ap_affiliates_id = %d ', array($affiliatepress_affiliate_id), '', 'order by ap_affiliate_link_id ASC', '', false, false,ARRAY_A); 
            $affiliatepress_total_link = count($affiliatepress_affiliate_links);
            $affiliatepress_link_limit = $AffiliatePress->affiliatepress_get_settings('affiliate_link_limit', 'affiliate_settings');
            $response['is_add_affiliate_link']     = 1;  
            if($affiliatepress_link_limit != 0 && $affiliatepress_total_link >=$affiliatepress_link_limit ){
                $response['is_add_affiliate_link']     = 0;    
            }
            if(!empty($affiliatepress_affiliate_links)){
                $affiliatepress_sr_no = 0;
                foreach($affiliatepress_affiliate_links as $affiliatepress_affiliate_links){
                    $affiliatepress_sr_no++;
                    $affiliatepress_page_link = (isset($affiliatepress_affiliate_links['ap_page_link']))?$affiliatepress_affiliate_links['ap_page_link']:'';
                    $affiliatepress_affiliates_campaign_name = (isset($affiliatepress_affiliate_links['ap_affiliates_campaign_name']))?$affiliatepress_affiliate_links['ap_affiliates_campaign_name']:'';
                    $affiliatepress_affiliates_sub_id = (isset($affiliatepress_affiliate_links['ap_affiliates_sub_id']))?$affiliatepress_affiliate_links['ap_affiliates_sub_id']:'';
                    $affiliatepress_affiliate_link_id = (isset($affiliatepress_affiliate_links['ap_affiliate_link_id']))?$affiliatepress_affiliate_links['ap_affiliate_link_id']:'';

                    $affiliatepress_page_link = $AffiliatePress->affiliatepress_get_affiliate_custom_link($affiliatepress_affiliate_id,$affiliatepress_page_link);
                    $affiliatepress_page_link = add_query_arg( 'campaign',$affiliatepress_affiliates_campaign_name,$affiliatepress_page_link);

                    if(!empty($affiliatepress_affiliates_sub_id)){
                        $affiliatepress_page_link = add_query_arg( 'sub_id',$affiliatepress_affiliates_sub_id,$affiliatepress_page_link);
                    }
                    $affiliatepress_affiliate_custom_links[] = array(
                        'ap_affiliate_link_id' => $affiliatepress_affiliate_link_id,
                        'ap_affiliates_campaign_name' => $affiliatepress_affiliates_campaign_name,
                        'ap_affiliates_sub_id' => $affiliatepress_affiliates_sub_id,
                        'ap_page_link' => $affiliatepress_page_link,
                        'sr_no' => $affiliatepress_sr_no,
                    );
                }
            }

            $affiliatepress_panel_labels = $this->affiliatepress_get_panel_lables();

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'success', 'affiliatepress-affiliate-marketing');            
            $response['affiliate_custom_links'] = $affiliatepress_affiliate_custom_links;   
            $response['affiliate_panel_labels']     = $affiliatepress_panel_labels;    
            
            $response = apply_filters('affiliatepress_modify_affiliates_panel_listing_data', $response); 

            wp_send_json($response);
            exit;  

        }

        function affiliatepress_delete_affiliate_custome_link_func($affiliatepress_affiliates_id = ''){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliate_links, $AffiliatePress;

            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }

            $affiliatepress_custome_affiliate_link_id = (isset($_POST['ap_affiliate_link_id']))?intval($_POST['ap_affiliate_link_id']):0; // phpcs:ignore 

            if($affiliatepress_custome_affiliate_link_id && $affiliatepress_affiliate_id){

                $affiliatepress_link_deleted = $wpdb->delete( $affiliatepress_tbl_ap_affiliate_links,array(  'ap_affiliate_link_id' => $affiliatepress_custome_affiliate_link_id,'ap_affiliates_id'     => $affiliatepress_affiliate_id),array('%d','%d'));// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_links is a table name already prepare by affiliatepress_tablename_prepare function. false alarm
                
                if ( $affiliatepress_link_deleted ) {
                    $response['variant'] = 'success';
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_link_delete', 'message_settings'));
                
                } else {
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Something went wrong. Please try again.', 'affiliatepress-affiliate-marketing');
                }
            }

            wp_send_json($response);
            exit;  
        }

        
        /**
         * Function for change password functionality
         *
         * @return void
        */
        function affiliatepress_affiliate_panel_change_password_func(){
            global $wpdb, $affiliatepress_tbl_ap_affiliates, $AffiliatePress;

            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }

            $affiliatepress_old_password        = !empty($_POST['old_password']) ? trim(sanitize_text_field($_POST['old_password'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $affiliatepress_new_password        = !empty($_POST['new_password']) ? trim(sanitize_text_field($_POST['new_password'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $affiliatepress_confirm_password    = !empty($_POST['confirm_password']) ? trim(sanitize_text_field($_POST['confirm_password'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash              
            
            if(empty($affiliatepress_old_password) || empty($affiliatepress_new_password) || empty($affiliatepress_confirm_password)){
                $response['msg'] = esc_html__('Please enter all required fields.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                die();                
            }
            if($affiliatepress_new_password != $affiliatepress_confirm_password){
                $response['msg'] = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('new_and_current_password_not_match', 'message_settings'));
                wp_send_json($response);
                die();                
            }           
            $affiliatepress_current_user_id = get_current_user_id();
            $affiliatepress_user = get_user_by('id', $affiliatepress_current_user_id);
            if (!$affiliatepress_user) {
                $response['msg'] = esc_html__('User not found.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                die();                 
            }
            if (!wp_check_password($affiliatepress_old_password, $affiliatepress_user->user_pass, $affiliatepress_current_user_id)) {
                $response['msg'] = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('incorrect_current_password', 'message_settings'));
                wp_send_json($response);
                die();
            }             

            $affiliatepress_update = wp_set_password($affiliatepress_new_password, $affiliatepress_current_user_id);

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('password_successfully_updated', 'message_settings'));                        
            wp_send_json($response);
            exit;

        }
                
        /**
         * Function for edit profile
         *
         * @return void
        */
        function affiliatepress_save_edit_profile_data_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliates, $AffiliatePress,$affiliatepress_tbl_ap_affiliate_form_fields;

            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }

            $affiliatepress_firstname        = ! empty($_POST['firstname']) ? trim(sanitize_text_field($_POST['firstname'])) : ''; // phpcs:ignore
            $affiliatepress_lastname         = ! empty($_POST['lastname']) ? trim(sanitize_text_field($_POST['lastname'])) : ''; // phpcs:ignore 
            $affiliatepress_affiliates_payment_email = ! empty($_POST['ap_affiliates_payment_email']) ? trim(sanitize_text_field($_POST['ap_affiliates_payment_email'])) : ''; // phpcs:ignore
            $affiliatepress_affiliates_website = ! empty($_POST['ap_affiliates_website']) ? trim(sanitize_text_field($_POST['ap_affiliates_website'])) : ''; // phpcs:ignore
            $affiliatepress_affiliates_promote_us = ! empty($_POST['ap_affiliates_promote_us']) ? trim(sanitize_text_field($_POST['ap_affiliates_promote_us'])) : ''; // phpcs:ignore

            $affiliatepress_fields = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_form_fields, '*', 'WHERE ap_show_profile_field = %d ', array(1), '', 'order by ap_field_position ASC', '', false, false,ARRAY_A);          
            $affiliatepress_fields_error_message = array();
            if(!empty($affiliatepress_fields)){                
                foreach($affiliatepress_fields as $affiliatepress_key=>$affiliatepress_field){                   
                    $affiliatepress_field_error_message = (isset($affiliatepress_field['ap_field_error_message']))?$affiliatepress_field['ap_field_error_message']:'';
                    $affiliatepress_form_field_name = (isset($affiliatepress_field['ap_form_field_name']))?$affiliatepress_field['ap_form_field_name']:'';
                    $affiliatepress_field_required = (isset($affiliatepress_field['ap_field_required']))?$affiliatepress_field['ap_field_required']:0;
                    if($affiliatepress_field_required == 1){
                        $affiliatepress_fields_error_message[$affiliatepress_form_field_name] = $affiliatepress_field_error_message;
                    }
                }                
            }            
            if(empty(trim($affiliatepress_firstname))) {
                $response['msg'] = (isset($affiliatepress_fields_error_message['firstname']))?$affiliatepress_fields_error_message['firstname']:esc_html__('Please enter firstname', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                die();
            }
            if(empty(trim($affiliatepress_lastname))) {
                $response['msg'] = (isset($affiliatepress_fields_error_message['lastname']))?$affiliatepress_fields_error_message['lastname']:esc_html__('Please enter lastname', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                die();
            }  
            if(empty($affiliatepress_affiliates_payment_email) && isset($affiliatepress_fields_error_message['ap_affiliates_payment_email'])){
                $response['msg'] = $affiliatepress_fields_error_message['ap_affiliates_payment_email'];
                wp_send_json($response);
                die();                
            }
            if(empty($affiliatepress_affiliates_website) && isset($affiliatepress_fields_error_message['ap_affiliates_website'])){
                $response['msg'] = $affiliatepress_fields_error_message['ap_affiliates_website'];
                wp_send_json($response);
                die();                
            }  
            if(empty($affiliatepress_affiliates_promote_us) && isset($affiliatepress_fields_error_message['ap_affiliates_promote_us'])){
                $response['msg'] = $affiliatepress_fields_error_message['ap_affiliates_promote_us'];
                wp_send_json($response);
                die();                
            }            
            $affiliatepress_current_user_id = get_current_user_id();
            $affiliatepress_user_data = array(
                'ID'            => $affiliatepress_current_user_id,
                'first_name'    => $affiliatepress_firstname,
                'last_name'     => $affiliatepress_lastname,
                'display_name'  => $affiliatepress_firstname.' '.$affiliatepress_lastname
            );            
            $affiliatepress_user_id = wp_update_user($affiliatepress_user_data);            
            $affiliatepress_field_update = array();            


            $affiliatepress_field_update['ap_affiliates_first_name'] = $affiliatepress_firstname;
            $affiliatepress_field_update['ap_affiliates_last_name']  = $affiliatepress_lastname;

            if(!empty($affiliatepress_affiliates_payment_email)){
                $affiliatepress_field_update['ap_affiliates_payment_email'] = $affiliatepress_affiliates_payment_email;
            }
            if(!empty($affiliatepress_affiliates_website)){
                $affiliatepress_field_update['ap_affiliates_website'] = $affiliatepress_affiliates_website;
            }
            if(!empty($affiliatepress_affiliates_promote_us)){
                $affiliatepress_field_update['ap_affiliates_promote_us'] = $affiliatepress_affiliates_promote_us;
            }            

            $affiliatepress_avtar_name = isset($_POST['avatar_name']) ? sanitize_text_field(wp_unslash($_POST['avatar_name'])) : '';
            $affiliatepress_avatar_url = isset($_POST['avatar_url']) ? esc_url_raw(wp_unslash($_POST['avatar_url'])) : '';

            if(!empty($affiliatepress_avtar_name) && !empty($affiliatepress_avatar_url)){

                $affiliatepress_user_img_url  = esc_url_raw($affiliatepress_avatar_url); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $affiliatepress_user_img_name = sanitize_file_name($affiliatepress_avtar_name); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                                        
                $affiliatepress_edit_profile_image_url = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_avatar', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliate_id ), '', '', '', true, false,ARRAY_A);
                $affiliatepress_user_img_url_comapre = (!empty($affiliatepress_user_img_url))?basename($affiliatepress_user_img_url):'';

                if($affiliatepress_user_img_url_comapre != $affiliatepress_edit_profile_image_url ){

                    $affiliatepress_upload_dir                 = AFFILIATEPRESS_UPLOAD_DIR . '/';
                    $affiliatepress_new_file_name              = current_time('timestamp') . '_' . $affiliatepress_user_img_name;
                    $affiliatepress_upload_path                = $affiliatepress_upload_dir . $affiliatepress_new_file_name;

                    $affiliatepress_upload_res = new affiliatepress_fileupload_class( $affiliatepress_user_img_url, true );
                    $affiliatepress_upload_res->affiliatepress_check_nonce        = true;
                    $affiliatepress_upload_res->affiliatepress_nonce_data         = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';// phpcs:ignore 
                    $affiliatepress_upload_res->affiliatepress_nonce_action       = 'ap_wp_nonce';
                    $affiliatepress_upload_res->affiliatepress_check_only_image   = true;
                    $affiliatepress_upload_res->affiliatepress_check_specific_ext = false;
                    $affiliatepress_upload_res->affiliatepress_allowed_ext        = array();
                    $affiliatepress_upload_response = $affiliatepress_upload_res->affiliatepress_process_upload( $affiliatepress_upload_path );                            
                    if( true == $affiliatepress_upload_response ){

                        $affiliatepress_user_image_new_url   =  $affiliatepress_new_file_name;
                        $affiliatepress_field_update['ap_affiliates_user_avatar'] = $affiliatepress_user_image_new_url;

                        if( file_exists( AFFILIATEPRESS_TMP_IMAGES_DIR . '/' . basename($affiliatepress_user_img_url_comapre) ) ){
                            wp_delete_file(AFFILIATEPRESS_TMP_IMAGES_DIR . '/' . basename($affiliatepress_user_img_url_comapre));// phpcs:ignore
                        }
                        if (! empty($affiliatepress_edit_profile_image_url) ) {
                            // Remove old image and upload new image                                    
                            if( file_exists( AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_edit_profile_image_url) ) ){   
                                wp_delete_file(AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_edit_profile_image_url));// phpcs:ignore
                            }
                        }
                    }

                }                        

            }else{
                $affiliatepress_field_update['ap_affiliates_user_avatar'] = "";
            }

            if(!empty($affiliatepress_field_update)){
                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliates, $affiliatepress_field_update, array( 'ap_affiliates_id' => $affiliatepress_affiliate_id ));
            }
            
            do_action('affiliatepress_after_update_profile_field', $affiliatepress_affiliate_id); // phpcs:ignore WordPress.Security.NonceVerification

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('profile_fields_successfully_updated', 'message_settings'));
            
            wp_send_json($response);
            exit;  

            
        }
        
        /**
         * Function for get edit profile data
         *
         * @return void
        */
        function affiliatepress_get_affiliate_edit_profile_data_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliates, $AffiliatePress;            
            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }

            /* Affiliate Edit Profile Fields */
            $affiliatepress_affiliates_profile_fields = array(
                'username'                     => "",
                'firstname'                    => "",
                'lastname'                     => "",
                'email'                        => "",
                'password'                     => "",
                "ap_affiliates_user_id"        => "",
                "ap_affiliates_payment_email"  => "",
                "ap_affiliates_website"        => "",
                
            );  

            $affiliatepress_affiliate_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_payment_email,ap_affiliates_website,ap_affiliates_promote_us,	ap_affiliates_user_name,ap_affiliates_user_id,ap_affiliates_user_email,ap_affiliates_first_name,ap_affiliates_last_name', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliate_id ), '', '', '', false, true,ARRAY_A);
            if(!empty($affiliatepress_affiliate_data)){

                $affiliatepress_affiliates_profile_fields['ap_affiliates_payment_email'] =  (!empty($affiliatepress_affiliate_data['ap_affiliates_payment_email']))?stripslashes_deep($affiliatepress_affiliate_data['ap_affiliates_payment_email']):'';
                $affiliatepress_affiliates_profile_fields['ap_affiliates_website']       = (!empty($affiliatepress_affiliate_data['ap_affiliates_website']))?stripslashes_deep($affiliatepress_affiliate_data['ap_affiliates_website']):'';
                $affiliatepress_affiliates_profile_fields['ap_affiliates_promote_us']    = (!empty($affiliatepress_affiliate_data['ap_affiliates_promote_us']))?stripslashes_deep($affiliatepress_affiliate_data['ap_affiliates_promote_us']):'';

                $affiliatepress_current_user_id = get_current_user_id();
                $affiliatepress_user_info = get_userdata($affiliatepress_current_user_id);
                $affiliatepress_affiliates_profile_fields['firstname'] = (!empty($affiliatepress_affiliate_data['ap_affiliates_first_name']))?stripslashes_deep($affiliatepress_affiliate_data['ap_affiliates_first_name']):'';
                $affiliatepress_affiliates_profile_fields['lastname'] = (!empty($affiliatepress_affiliate_data['ap_affiliates_last_name']))?stripslashes_deep($affiliatepress_affiliate_data['ap_affiliates_last_name']):'';
                $affiliatepress_affiliates_profile_fields['username'] = (isset($affiliatepress_user_info->user_login))?esc_html($affiliatepress_user_info->user_login):'';
                $affiliatepress_affiliates_profile_fields['email'] = (isset($affiliatepress_user_info->user_email))?esc_html($affiliatepress_user_info->user_email):'';

                $affiliatepress_affiliates_profile_fields = apply_filters('affiliatepress_modify_edit_profile_affiliate_data',$affiliatepress_affiliates_profile_fields,$affiliatepress_affiliate_data,$affiliatepress_affiliate_id);

            }



            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');            
            $response['affiliates_profile_fields'] = $affiliatepress_affiliates_profile_fields;                  

            wp_send_json($response);
            exit;              
            


        }

        /**
         * Function for get affiliate creative
         *
         * @return void
        */
        function affiliatepress_get_affiliate_creatives_func(){

            global $wpdb, $affiliatepress_tbl_ap_creative, $AffiliatePress;            
            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }

            $affiliatepress_perpage     = isset($_POST['perpage']) ? intval($_POST['perpage']) : 10; // phpcs:ignore 
            $affiliatepress_currentpage = isset($_POST['currentpage']) ? intval($_POST['currentpage']) : 1; // phpcs:ignore
            $affiliatepress_offset      = ( ! empty($affiliatepress_currentpage) && $affiliatepress_currentpage > 1 ) ? ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage ) : 0;
            $affiliatepress_order       = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : ''; // phpcs:ignore
            $affiliatepress_order_by    = isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : ''; // phpcs:ignore
            
            $affiliatepress_search_query = '';

            $affiliatepress_where_clause = " WHERE 1 = 1 ";
            $affiliatepress_where_clause.= $wpdb->prepare( " AND ap_creative_status = %d ",1);

            if (isset($_REQUEST['search_data']) && !empty($_REQUEST['search_data']) ){// phpcs:ignore
                if (isset($_REQUEST['search_data']['ap_creative_name']) && !empty($_REQUEST['search_data']['ap_creative_name']) ) {// phpcs:ignore
                    $affiliatepress_search_name   = sanitize_text_field($_REQUEST['search_data']['ap_creative_name']);// phpcs:ignore
                    $affiliatepress_where_clause.= $wpdb->prepare( " AND ap_creative_name LIKE %s ", '%'.$affiliatepress_search_name.'%' );
                }
                if(isset($_REQUEST['search_data']['creative_type']) && !empty($_REQUEST['search_data']['creative_type'])){// phpcs:ignore
                    $affiliatepress_where_clause.= $wpdb->prepare( " AND ap_creative_type = %s ",sanitize_text_field( $_REQUEST['search_data']['creative_type']));// phpcs:ignore
                }
            }

            $affiliatepress_tbl_ap_creative_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_creative); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_creative contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_get_total_creatives = intval($wpdb->get_var("SELECT count(ap_creative_id) FROM {$affiliatepress_tbl_ap_creative_temp} {$affiliatepress_where_clause}")); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_creative_temp is a table name. false alarm
            if(empty($affiliatepress_order)){
                $affiliatepress_order = 'DESC';
            }
            if(empty($affiliatepress_order_by)){
                $affiliatepress_order_by = 'ap_creative_id';
            }

            $affiliatepress_creatives_record   = $wpdb->get_results("SELECT * FROM {$affiliatepress_tbl_ap_creative_temp} {$affiliatepress_where_clause}  order by {$affiliatepress_order_by} {$affiliatepress_order} LIMIT {$affiliatepress_offset} , {$affiliatepress_perpage}", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_creative_temp is a table name. false alarm

            $affiliatepress_creatives = array();
            if (! empty($affiliatepress_creatives_record) ) {
                $affiliatepress_counter = 1;
                $affiliatepress_affiliate_common_link = $AffiliatePress->affiliatepress_get_affiliate_common_link($affiliatepress_affiliate_id);
                foreach ( $affiliatepress_creatives_record as $affiliatepress_key=>$affiliatepress_single_record ) {

                    $affiliatepress_creative = $affiliatepress_single_record;                    
                    $affiliatepress_creative['change_status_loader']    = '';
                    $affiliatepress_creative['image_url']               = '';
                    $affiliatepress_creative['ap_creative_alt_text']    = stripslashes_deep($affiliatepress_creative['ap_creative_alt_text']);
                    $affiliatepress_creative['ap_creative_name']        = stripslashes_deep($affiliatepress_creative['ap_creative_name']);
                    $affiliatepress_creative['ap_creative_text']        = stripslashes_deep($affiliatepress_creative['ap_creative_text']);
                    $affiliatepress_creative['ap_creative_description'] = stripslashes_deep($affiliatepress_creative['ap_creative_description']);
                    $affiliatepress_creative['image_detail'] = array(

                    );

                    $affiliatepress_creative['image_data'] = array(
                        'width' => '',
                        'height' => '',
                        'type' => '',
                        'fileSize' => '',
                    );
                    if(!empty($affiliatepress_creative['ap_creative_image_url'])){                        
                        $affiliatepress_creative['image_url'] = AFFILIATEPRESS_UPLOAD_URL.'/'.$affiliatepress_creative['ap_creative_image_url'];                        
                        $affiliatepress_affiliate_upload_dir = AFFILIATEPRESS_UPLOAD_DIR.'/'.$affiliatepress_creative['ap_creative_image_url'];
                        $affiliatepress_creative_image_url = AFFILIATEPRESS_UPLOAD_URL.'/'.$affiliatepress_creative['ap_creative_image_url'];
                        $affiliatepress_creative['image_data'] = $this->affiliatepress_get_image_info($affiliatepress_affiliate_upload_dir);
                    }
                    $affiliatepress_creative_landing_url = $affiliatepress_creative['ap_creative_landing_url'];
                    $affiliatepress_creative_landing_url = $AffiliatePress->affiliatepress_get_affiliate_custom_link($affiliatepress_affiliate_id,$affiliatepress_creative_landing_url);
                    $affiliatepress_creative['ap_creative_created_at_formated'] = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_creative['ap_creative_created_at']);
                    $affiliatepress_creative['ap_creative_code'] = '';
                    $affiliatepress_creative['ap_creative_code_preview'] = '';
                    if($affiliatepress_creative['ap_creative_type'] == 'image'){                        
                        $affiliatepress_creative['ap_creative_code'] = '<a href="'.esc_url($affiliatepress_creative_landing_url).'"><img src="'.esc_url($affiliatepress_creative['image_url']).'" alt="'.esc_attr($affiliatepress_creative['ap_creative_alt_text']).'"/></a>';
                        $affiliatepress_creative['ap_creative_code_preview'] = htmlentities($affiliatepress_creative['ap_creative_code']);
                    }else{
                        $affiliatepress_creative['ap_creative_code'] = '<a href="'.$affiliatepress_creative_landing_url.'">'.$affiliatepress_creative['ap_creative_text'].'</a>';
                        $affiliatepress_creative['ap_creative_code_preview'] = htmlentities($affiliatepress_creative['ap_creative_code']);
                    }
                    $affiliatepress_creatives[] = $affiliatepress_creative;

                }
            }

            $affiliatepress_creative_pagination_count = ceil(intval($affiliatepress_get_total_creatives) / $affiliatepress_perpage);

            $affiliatepress_panel_labels = $this->affiliatepress_get_panel_lables();

            $affiliatepress_pagination_lable = $this->affiliatepress_get_pagination_lable();
            $affiliatepress_pagination_item_count = count($affiliatepress_creatives);
            $affiliatepress_pagination_lable = str_replace(['[start]', '[total]'],[$affiliatepress_pagination_item_count, $affiliatepress_get_total_creatives],$affiliatepress_pagination_lable);

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');            
            $response['items'] = $affiliatepress_creatives;
            $response['total'] = intval($affiliatepress_get_total_creatives);        
            $response['creative_pagination_count'] = $affiliatepress_creative_pagination_count;  
            $response['creative_pagination_label'] = $affiliatepress_pagination_lable;  
            $response['affiliate_panel_labels']     = $affiliatepress_panel_labels; 
            
            wp_send_json($response);
            exit;            

            
            
        }
        
        /**
         * Function for get affiliate visits
         *
         * @return void
        */
        function affiliatepress_get_affiliate_visits_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_affiliate_visits,$AffiliatePress;
            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }
            $affiliatepress_perpage     = isset($_POST['perpage']) ? intval($_POST['perpage']) : 10; // phpcs:ignore 
            $affiliatepress_currentpage = isset($_POST['currentpage']) ? intval($_POST['currentpage']) : 1; // phpcs:ignore
            $affiliatepress_offset      = (!empty($affiliatepress_currentpage) && $affiliatepress_currentpage > 1 ) ? ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage ) : 0;
            $affiliatepress_order       = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : ''; // phpcs:ignore
            $affiliatepress_order_by    = isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : ''; // phpcs:ignore
            
            $affiliatepress_search_query = '';

            $affiliatepress_where_clause = " WHERE 1 = 1 ";
            $affiliatepress_where_clause.= $wpdb->prepare( " AND (visits.ap_affiliates_id = %d) ", $affiliatepress_affiliate_id);
            if ( isset($_REQUEST['search_data']) && !empty($_REQUEST['search_data']) ) {
               
                if(isset($_REQUEST['search_data']['ap_visit_date']) && !empty($_REQUEST['search_data']['ap_visit_date']) && $_REQUEST['search_data']['ap_visit_date'] != '' ) {// phpcs:ignore

                    $affiliatepress_start_date = (isset($_REQUEST['search_data']['ap_visit_date'][0]))?sanitize_text_field($_REQUEST['search_data']['ap_visit_date'][0]):'';// phpcs:ignore
                    $affiliatepress_end_date   = (isset($_REQUEST['search_data']['ap_visit_date'][1]))?sanitize_text_field($_REQUEST['search_data']['ap_visit_date'][1]):'';// phpcs:ignore

                    if(!empty($affiliatepress_start_date) && !empty($affiliatepress_end_date)){

                        $affiliatepress_start_date = date('Y-m-d',strtotime($affiliatepress_start_date));// phpcs:ignore
                        $affiliatepress_end_date = date('Y-m-d',strtotime($affiliatepress_end_date));// phpcs:ignore

                        $affiliatepress_where_clause.= $wpdb->prepare( " AND (DATE(visits.ap_visit_created_date) >= %s AND DATE(visits.ap_visit_created_date) <= %s) ", $affiliatepress_start_date, $affiliatepress_end_date);

                    }
                }          
                $affiliatepress_visit_type = "all_visit";  
                if (isset($_REQUEST['search_data']['visit_type']) && !empty($_REQUEST['search_data']['visit_type']) ) {// phpcs:ignore

                    $affiliatepress_visit_type = sanitize_text_field( wp_unslash($_REQUEST['search_data']['visit_type']) );
                    if($affiliatepress_visit_type == 'converted'){// phpcs:ignore
                        $affiliatepress_where_clause.= $wpdb->prepare( " AND (visits.ap_commission_id <> %d) ", 0);
                    }elseif ($affiliatepress_visit_type == 'not_converted') {
                        $affiliatepress_where_clause.= $wpdb->prepare( " AND (visits.ap_commission_id = %d) ", 0);
                    }
                }
            }  

            $affiliatepress_tbl_ap_affiliate_visits_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_visits); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_visits contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function            
            
                        
            $affiliatepress_get_total_visits = intval($wpdb->get_var("SELECT count(visits.ap_visit_id) FROM {$affiliatepress_tbl_ap_affiliate_visits_temp} as visits {$affiliatepress_where_clause}")); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_visits_temp is a table name. false alarm

            if(empty($affiliatepress_order)){
                $affiliatepress_order = 'DESC';
            }
            if(empty($affiliatepress_order_by)){
                $affiliatepress_order_by = 'visits.ap_visit_id';
            }
            $affiliatepress_visits_record    = $wpdb->get_results("SELECT visits.ap_visit_id, visits.ap_commission_id, visits.ap_visit_created_date, visits.ap_visit_ip_address, visits.ap_visit_country, visits.ap_visit_landing_url, visits.ap_referrer_url, visits.ap_affiliates_campaign_name FROM {$affiliatepress_tbl_ap_affiliate_visits_temp} as visits {$affiliatepress_where_clause}  order by {$affiliatepress_order_by} {$affiliatepress_order} LIMIT {$affiliatepress_offset} , {$affiliatepress_perpage}", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_visits_temp is a table name. false alarm

            $affiliates = array();
            if (! empty($affiliatepress_visits_record) ) {
                foreach ( $affiliatepress_visits_record as $affiliatepress_key=>$affiliatepress_single_affiliate ) {
                    $affiliatepress_visit = $affiliatepress_single_affiliate;                    
                    $affiliatepress_visit['sr_no'] = intval($affiliatepress_single_affiliate['ap_visit_id']);
                    $affiliatepress_visit['visit_created_date_formated'] = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_single_affiliate['ap_visit_created_date']);
                    $affiliatepress_visit['ap_commission_id'] = apply_filters('affiliatepress_visit_commisison_update', $affiliatepress_single_affiliate['ap_commission_id']);
                    $affiliates[] = $affiliatepress_visit;
                }
            }

            $affiliatepress_visits_pagination_count = ceil(intval($affiliatepress_get_total_visits) / $affiliatepress_perpage);

            $affiliatepress_pagination_lable = $this->affiliatepress_get_pagination_lable();
            $affiliatepress_pagination_item_count = count($affiliates);
            $affiliatepress_pagination_lable = str_replace(['[start]', '[total]'],[$affiliatepress_pagination_item_count, $affiliatepress_get_total_visits],$affiliatepress_pagination_lable);

            $affiliatepress_panel_labels = $this->affiliatepress_get_panel_lables();

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');            
            $response['items'] = $affiliates;
            $response['total'] = intval($affiliatepress_get_total_visits);     
            $response['visits_pagination_count'] = intval($affiliatepress_visits_pagination_count);    
            $response['visits_pagination_label'] = $affiliatepress_pagination_lable;    
            $response['affiliate_panel_labels']     = $affiliatepress_panel_labels;       
            $response['affiliate_visit_type']     = $affiliatepress_visit_type;      

            wp_send_json($response);
            exit;             




        }

        /**
         * Function for allow affiliate user
         *
         * @return void
        */
        function affiliatepress_check_affiliate_user_allow_to_access_func(){
            global $affiliatepress_tbl_ap_affiliates,$affiliatepress_affiliates, $affiliatepress_affiliate_user_access_affiliate_panel;
            $affiliatepress_flag = false; 
            $affiliatepress_current_user_id = get_current_user_id();
            if($affiliatepress_current_user_id){

                if(empty($affiliatepress_affiliate_user_access_affiliate_panel)){

                    $affiliatepress_affiliates_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_id', 'WHERE ap_affiliates_user_id = %d AND ap_affiliates_status <> %d', array( $affiliatepress_current_user_id, 3), '', '', '', true, false,ARRAY_A);
                    if($affiliatepress_affiliates_id){
                        $affiliatepress_flag = $affiliatepress_affiliates->affiliatepress_is_valid_affiliate($affiliatepress_affiliates_id);
                        if($affiliatepress_flag){
                            $affiliatepress_affiliate_user_access_affiliate_panel = $affiliatepress_affiliates_id;
                            return $affiliatepress_affiliates_id;
                        }
                    }

                }else{
                    return $affiliatepress_affiliate_user_access_affiliate_panel;
                }

            }
            return $affiliatepress_flag;
        }

        /**
         * Function for get affiliate commission data
         *
         * @return void
        */
        function affiliatepress_get_affiliate_commissions_func(){

            global $wpdb, $AffiliatePress,$affiliatepress_tbl_ap_affiliate_form_fields,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_affiliates,$affiliatepress_affiliates,$affiliatepress_global_options;

            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            $response['variant'] = 'error';
            $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__('Something wrong...', 'affiliatepress-affiliate-marketing');
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_user_block_message', 'message_settings'));
                wp_send_json($response);
                exit();                
            }

            $affiliatepress_perpage     = isset($_POST['perpage']) ? intval($_POST['perpage']) : 10; // phpcs:ignore 
            $affiliatepress_currentpage = isset($_POST['currentpage']) ? intval($_POST['currentpage']) : 1; // phpcs:ignore 
            $affiliatepress_offset      = ( ! empty($affiliatepress_currentpage) && $affiliatepress_currentpage > 1 ) ? ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage ) : 0;
            $affiliatepress_order       = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : ''; // phpcs:ignore 
            $affiliatepress_order_by    = isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : ''; // phpcs:ignore 
            
            $affiliatepress_search_query = '';
            $affiliatepress_where_clause = " WHERE 1 = 1 ";
            $affiliatepress_where_clause.= $wpdb->prepare( " AND (commissions.ap_affiliates_id = %d) ", $affiliatepress_affiliate_id);

            if(!empty($_REQUEST['search_data'])){// phpcs:ignore         
                if(isset($_REQUEST['search_data']['ap_commission_search_date'])){// phpcs:ignore
                    $affiliatepress_start_date = (isset($_REQUEST['search_data']['ap_commission_search_date'][0]))?sanitize_text_field($_REQUEST['search_data']['ap_commission_search_date'][0]):'';// phpcs:ignore
                    $affiliatepress_end_date   = (isset($_REQUEST['search_data']['ap_commission_search_date'][1]))?sanitize_text_field($_REQUEST['search_data']['ap_commission_search_date'][1]):'';// phpcs:ignore
                    if(!empty($affiliatepress_start_date) && !empty($affiliatepress_end_date)){                        
                        $affiliatepress_start_date = date('Y-m-d',strtotime($affiliatepress_start_date));// phpcs:ignore
                        $affiliatepress_end_date = date('Y-m-d',strtotime($affiliatepress_end_date));// phpcs:ignore
                        $affiliatepress_where_clause.= $wpdb->prepare( " AND (DATE(commissions.ap_commission_created_date) >= %s AND DATE(commissions.ap_commission_created_date) <= %s) ", $affiliatepress_start_date, $affiliatepress_end_date);
                    }                    
                }                                
                if (isset($_REQUEST['search_data']['commission_status']) && !empty($_REQUEST['search_data']['commission_status']) ) {// phpcs:ignore
                    $affiliatepress_commission_status = intval($_REQUEST['search_data']['commission_status']);// phpcs:ignore
                    $affiliatepress_where_clause.= $wpdb->prepare( " AND (commissions.ap_commission_status = %d) ", $affiliatepress_commission_status);
                }
            }

            $affiliatepress_where_clause = apply_filters( 'affiliatepress_modify_commission_data', $affiliatepress_where_clause);

            $affiliatepress_tbl_ap_affiliate_commissions_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_commissions contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function 

            $affiliatepress_tbl_ap_affiliates_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function 

            $affiliatepress_get_total_commissions = intval($wpdb->get_var("SELECT count(commissions.ap_commission_id) FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} as commissions INNER JOIN {$affiliatepress_tbl_ap_affiliates_temp} as affiliate  ON commissions.ap_affiliates_id = affiliate.ap_affiliates_id {$affiliatepress_where_clause}")); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions_temp is a table name. false alarm

            if(empty($affiliatepress_order)){
                $affiliatepress_order = 'DESC';
            }
            if(empty($affiliatepress_order_by)){
                $affiliatepress_order_by = 'commissions.ap_commission_id';
            }
            $affiliatepress_commissions_record = $wpdb->get_results("SELECT commissions.ap_commission_id, commissions.ap_commission_created_date, commissions.ap_commission_type, commissions.ap_commission_amount, commissions.ap_commission_status, commissions.ap_commission_source, commissions.ap_commission_reference_detail FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} as commissions INNER JOIN {$affiliatepress_tbl_ap_affiliates_temp} as affiliate  ON commissions.ap_affiliates_id = affiliate.ap_affiliates_id {$affiliatepress_where_clause}  order by {$affiliatepress_order_by} {$affiliatepress_order} LIMIT {$affiliatepress_offset} , {$affiliatepress_perpage}", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions_temp is a table name. false alarm                            

            $affiliatepress_commissions = array();
            if (! empty($affiliatepress_commissions_record) ) {

                $affiliatepress_options = $affiliatepress_global_options->affiliatepress_global_options();
                $affiliatepress_all_commissions_status = $affiliatepress_options['commissions_status'];
                $affiliatepress_all_commissions_status_list = array();
                if(!empty($affiliatepress_all_commissions_status)){
                    foreach($affiliatepress_all_commissions_status as $affiliatepress_commission_status){
                        $affiliatepress_all_commissions_status_list[$affiliatepress_commission_status['value']] = $affiliatepress_commission_status['text'];
                    }
                }

                $affiliatepress_counter = 1;
                $affiliatepress_sr_no   = (($affiliatepress_currentpage - 1) * $affiliatepress_perpage);
                foreach ( $affiliatepress_commissions_record as $affiliatepress_key=>$affiliatepress_single_commission ) {
                    $affiliatepress_sr_no++;
                    $affiliatepress_commission   = $affiliatepress_single_commission;                                       
                    $affiliatepress_commission['sr_no'] = intval($affiliatepress_sr_no);
                    $affiliatepress_formated_commission_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_single_commission['ap_commission_amount']);                    
                    $affiliatepress_commission['ap_formated_commission_amount'] = $affiliatepress_formated_commission_amount;
                    $affiliatepress_commission_status_name = (isset($affiliatepress_all_commissions_status_list[$affiliatepress_single_commission['ap_commission_status']]))?$affiliatepress_all_commissions_status_list[$affiliatepress_single_commission['ap_commission_status']]:'';
                    $affiliatepress_commission['ap_commission_status_name'] = $affiliatepress_commission_status_name;
                    $affiliatepress_commission['commission_created_date_formated'] = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_single_commission['ap_commission_created_date']);

                    $affiliatepress_commission_product = (isset($affiliatepress_single_commission['ap_commission_reference_detail']) && !empty($affiliatepress_single_commission['ap_commission_reference_detail']))?$affiliatepress_single_commission['ap_commission_reference_detail']:'-';
                    
                    $affiliatepress_commission['affiliatepress_commission_product'] = $affiliatepress_commission_product; 

                    $affiliatepress_source_plugin_name = $AffiliatePress->affiliatepress_get_supported_addon_name($affiliatepress_single_commission['ap_commission_source']);
                    $affiliatepress_commission['source_plugin_name'] = $affiliatepress_source_plugin_name;
                    
                    $affiliatepress_commission_type = (isset($affiliatepress_commission['ap_commission_type']))?$affiliatepress_commission['ap_commission_type']:'';
                    if($affiliatepress_commission_type == 'sale'){
                        $affiliatepress_commission['ap_commission_type_text'] = esc_html__('Subscription', 'affiliatepress-affiliate-marketing');
                    }else{
                        $affiliatepress_commission['ap_commission_type_text'] = esc_html__('Sale', 'affiliatepress-affiliate-marketing');
                    }                    

                    $affiliatepress_commissions[] = $affiliatepress_commission;
                }
            }

            $affiliatepress_commission_pagination_count = ceil(intval($affiliatepress_get_total_commissions) / $affiliatepress_perpage);

            $affiliatepress_pagination_item_count = count($affiliatepress_commissions);

            $affiliatepress_pagination_lable = $this->affiliatepress_get_pagination_lable();
            $affiliatepress_pagination_lable = str_replace(['[start]', '[total]'],[$affiliatepress_pagination_item_count, $affiliatepress_get_total_commissions],$affiliatepress_pagination_lable);

            $affiliatepress_panel_labels = $this->affiliatepress_get_panel_lables();

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');            
            $response['items'] = $affiliatepress_commissions;
            $response['total'] = intval($affiliatepress_get_total_commissions);     
            $response['commission_pagination_counts'] = $affiliatepress_commission_pagination_count;
            $response['affiliate_panel_labels']     = $affiliatepress_panel_labels;    
            $response['commission_pagination_labels'] = $affiliatepress_pagination_lable;
            wp_send_json($response);
            exit; 
            
        }
                
        /**
         * Function for add dynamic helper variable
         *
         * @param  mixed $affiliatepress_affiliate_panel_dynamic_helper_vars
         * @return void
        */
        function affiliatepress_affiliate_panel_dynamic_constant_define_func($affiliatepress_affiliate_panel_dynamic_constant){
            $affiliatepress_affiliate_panel_dynamic_constant.='
                const open_modal = ref(false);
                affiliatepress_affiliate_panel_return_data["open_modal"] = open_modal;

                const open_creative_modal = ref(false);
                affiliatepress_affiliate_panel_return_data["open_creative_modal"] = open_creative_modal;

                const open_close_account_modal = ref(false);
                affiliatepress_affiliate_panel_return_data["open_close_account_modal"] = open_close_account_modal;

                const open_mobile_menu_modal = ref(false);
                affiliatepress_affiliate_panel_return_data["open_mobile_menu_modal"] = open_mobile_menu_modal;                

                const open_creative_filter_modal = ref(false);
                affiliatepress_affiliate_panel_return_data["open_creative_filter_modal"] = open_creative_filter_modal;

                const open_commission_filter_modal = ref(false);
                affiliatepress_affiliate_panel_return_data["open_commission_filter_modal"] = open_commission_filter_modal;

                const open_visit_filter_modal = ref(false);
                affiliatepress_affiliate_panel_return_data["open_visit_filter_modal"] = open_visit_filter_modal;

                const open_payment_filter_modal = ref(false);
                affiliatepress_affiliate_panel_return_data["open_payment_filter_modal"] = open_payment_filter_modal;                
            ';
            return $affiliatepress_affiliate_panel_dynamic_constant;
        }
        
        /**
         * Function for add common svg code
         *
        */
        function affiliatepress_common_affiliate_panel_svg_code_func($affiliatepress_type){
            if($affiliatepress_type == 'commission'){
            ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3.98797 14.6539L2.4657 13.1317C1.84477 12.5107 1.84477 11.4892 2.4657 10.8683L3.98797 9.34599C4.24836 9.0856 4.45867 8.57482 4.45867 8.21429V6.06105C4.45867 5.17973 5.17976 4.45867 6.06108 4.45867H8.2143C8.57484 4.45867 9.0856 4.24838 9.34599 3.98799L10.8682 2.4657C11.4892 1.84477 12.5108 1.84477 13.1317 2.4657L14.654 3.98799C14.9144 4.24838 15.425 4.45867 15.7856 4.45867H17.9389C18.8202 4.45867 19.5412 5.17973 19.5412 6.06105V8.21429C19.5412 8.57482 19.7515 9.0856 20.0119 9.34599L21.5343 10.8683C22.1552 11.4892 22.1552 12.5107 21.5343 13.1317L20.0119 14.6539C19.7515 14.9143 19.5412 15.4252 19.5412 15.7857V17.9388C19.5412 18.8202 18.8202 19.5413 17.9389 19.5413H15.7856C15.425 19.5413 14.9144 19.7516 14.654 20.0119L13.1317 21.5342C12.5108 22.1551 11.4892 22.1551 10.8682 21.5342L9.34599 20.0119C9.0856 19.7516 8.57484 19.5413 8.2143 19.5413H6.06108C5.17976 19.5413 4.45867 18.8202 4.45867 17.9388V15.7857C4.45867 15.4152 4.24836 14.9043 3.98797 14.6539Z" stroke="#4D5973" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.00488 14.9945L15.0139 8.98547" stroke="#4D5973" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14.5078 14.4938H14.5168" stroke="#4D5973" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.5 9.48621H9.50898" stroke="#4D5973" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

            <?php 
            }else if($affiliatepress_type == 'dashboard'){
            ?>
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4.75 1C1.88235 1 1 1.88235 1 4.75C1 7.61765 1.88235 8.5 4.75 8.5C7.61765 8.5 8.5 7.61765 8.5 4.75C8.5 1.88235 7.61765 1 4.75 1Z" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4.75 11.5C1.88235 11.5 1 12.3824 1 15.25C1 18.1176 1.88235 19 4.75 19C7.61765 19 8.5 18.1176 8.5 15.25C8.5 12.3824 7.61765 11.5 4.75 11.5Z" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15.25 11.5C12.3824 11.5 11.5 12.3824 11.5 15.25C11.5 18.1176 12.3824 19 15.25 19C18.1176 19 19 18.1176 19 15.25C19 12.3824 18.1176 11.5 15.25 11.5Z" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15.25 1C12.3824 1 11.5 1.88235 11.5 4.75C11.5 7.61765 12.3824 8.5 15.25 8.5C18.1176 8.5 19 7.61765 19 4.75C19 1.88235 18.1176 1 15.25 1Z" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php 
            }else if($affiliatepress_type == 'affiliates_links'){
            ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 12H15" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M9 18H8C4.68629 18 2 15.3137 2 12C2 8.68629 4.68629 6 8 6H9" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M15 6H16C19.3137 6 22 8.68629 22 12C22 15.3137 19.3137 18 16 18H15" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <?php 
            }else if($affiliatepress_type == 'visit'){
            ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3.27489 15.2957C2.42496 14.1915 2 13.6394 2 12C2 10.3606 2.42496 9.80853 3.27489 8.70433C4.97196 6.49956 7.81811 4 12 4C16.1819 4 19.028 6.49956 20.7251 8.70433C21.575 9.80853 22 10.3606 22 12C22 13.6394 21.575 14.1915 20.7251 15.2957C19.028 17.5004 16.1819 20 12 20C7.81811 20 4.97196 17.5004 3.27489 15.2957Z" stroke="#4D5973" stroke-width="1.5"/>
                <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="#4D5973" stroke-width="1.5"/>
            </svg>
            <?php 
            }else if($affiliatepress_type == 'creative'){
            ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 10C10.1046 10 11 9.10457 11 8C11 6.89543 10.1046 6 9 6C7.89543 6 7 6.89543 7 8C7 9.10457 7.89543 10 9 10Z" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2.66992 18.9501L7.59992 15.6401C8.38992 15.1101 9.52992 15.1701 10.2399 15.7801L10.5699 16.0701C11.3499 16.7401 12.6099 16.7401 13.3899 16.0701L17.5499 12.5001C18.3299 11.8301 19.5899 11.8301 20.3699 12.5001L21.9999 13.9001" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 22H15C20 22 22 20 22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22Z" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php 
            }else if($affiliatepress_type == 'payouts'){
            ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13 9H7" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M22.0002 10.9702V13.0302C22.0002 13.5802 21.5602 14.0302 21.0002 14.0502H19.0402C17.9602 14.0502 16.9702 13.2602 16.8802 12.1802C16.8202 11.5502 17.0602 10.9602 17.4802 10.5502C17.8502 10.1702 18.3602 9.9502 18.9202 9.9502H21.0002C21.5602 9.9702 22.0002 10.4202 22.0002 10.9702Z" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17.48 10.55C17.06 10.96 16.82 11.55 16.88 12.18C16.97 13.26 17.96 14.05 19.04 14.05H21V15.5C21 18.5 19 20.5 16 20.5H7C4 20.5 2 18.5 2 15.5V8.5C2 5.78 3.64 3.88 6.19 3.56C6.45 3.52 6.72 3.5 7 3.5H16C16.26 3.5 16.51 3.50999 16.75 3.54999C19.33 3.84999 21 5.76 21 8.5V9.95001H18.92C18.36 9.95001 17.85 10.17 17.48 10.55Z" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php 
            }else if($affiliatepress_type == 'copy_icon'){
            ?>
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="ap-copy-icon" d="M3.20728 12.2134V12.1134H3.10728H2.43764C2.12363 12.1134 1.868 11.8578 1.868 11.5438V2.43666C1.868 2.12265 2.12363 1.86702 2.43764 1.86702H11.5448C11.8588 1.86702 12.1144 2.12265 12.1144 2.43666V3.07282V3.17282H12.2144H13.5537H13.6537V3.07282V2.43666C13.6537 1.27358 12.7079 0.327734 11.5448 0.327734H2.43764C1.27455 0.327734 0.328711 1.27358 0.328711 2.43666V11.5438C0.328711 12.7069 1.27455 13.6527 2.43764 13.6527H3.10728H3.20728V13.5527V12.2134ZM6.4555 17.6706H15.5626C16.7257 17.6706 17.6716 16.7247 17.6716 15.5617V6.45452C17.6716 5.29143 16.7257 4.34559 15.5626 4.34559H6.4555C5.29241 4.34559 4.34657 5.29143 4.34657 6.45452V15.5617C4.34657 16.7247 5.29241 17.6706 6.4555 17.6706ZM5.88585 6.45452C5.88585 6.14051 6.14148 5.88488 6.4555 5.88488H15.5626C15.8767 5.88488 16.1323 6.14051 16.1323 6.45452V15.5617C16.1323 15.8757 15.8767 16.1313 15.5626 16.1313H6.4555C6.14148 16.1313 5.88585 15.8757 5.88585 15.5617V6.45452Z" fill="white" stroke="white" stroke-width="0.2"/>
            </svg>            
            <?php 
            }else if($affiliatepress_type == 'close_dialog'){
            ?>
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15.3498 0.661511C14.8948 0.206511 14.1598 0.206511 13.7048 0.661511L7.99984 6.35484L2.29484 0.649844C1.83984 0.194844 1.10484 0.194844 0.649844 0.649844C0.194844 1.10484 0.194844 1.83984 0.649844 2.29484L6.35484 7.99984L0.649844 13.7048C0.194844 14.1598 0.194844 14.8948 0.649844 15.3498C1.10484 15.8048 1.83984 15.8048 2.29484 15.3498L7.99984 9.64484L13.7048 15.3498C14.1598 15.8048 14.8948 15.8048 15.3498 15.3498C15.8048 14.8948 15.8048 14.1598 15.3498 13.7048L9.64484 7.99984L15.3498 2.29484C15.7932 1.85151 15.7932 1.10484 15.3498 0.661511Z" fill="#2E3A59"/>
            </svg>    
            <?php 
            }else if($affiliatepress_type == 'download_icon'){
            ?>
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_587_144246465)">
                <path class="ap-download-icon-fill" d="M10.0001 12.4286L9.39906 13.0296L10.0001 13.6307L10.6011 13.0296L10.0001 12.4286ZM10.8501 1.5C10.8501 1.03056 10.4695 0.65 10.0001 0.65C9.53066 0.65 9.1501 1.03056 9.1501 1.5H10.8501ZM3.92857 18.5L3.92857 17.65H3.92857V18.5ZM16.0714 18.5L16.0714 19.35H16.0714V18.5ZM3.32763 6.95818L9.39906 13.0296L10.6011 11.8275L4.52971 5.7561L3.32763 6.95818ZM10.6011 13.0296L16.6726 6.95818L15.4705 5.7561L9.39906 11.8275L10.6011 13.0296ZM10.8501 12.4286V1.5H9.1501V12.4286H10.8501ZM3.92857 19.35L16.0714 19.35L16.0714 17.65L3.92857 17.65L3.92857 19.35ZM2.35 16.0714V14.8571H0.65V16.0714H2.35ZM19.35 16.0714V14.8571H17.65V16.0714H19.35ZM16.0714 19.35C17.8821 19.35 19.35 17.8821 19.35 16.0714H17.65C17.65 16.9432 16.9432 17.65 16.0714 17.65V19.35ZM3.92857 17.65C3.05675 17.65 2.35 16.9432 2.35 16.0714H0.65C0.65 17.8821 2.11787 19.35 3.92857 19.35V17.65Z" fill="#656E81"/>
                </g>
                <defs>
                <clipPath id="clip0_587_144246465">
                <rect class="ap-download-icon-fill" width="20" height="20" fill="white"/>
                </clipPath>
                </defs>
            </svg>           
            <?php 
            }else if($affiliatepress_type == 'text_link_icon'){
            ?>
            <svg width="87" height="92" viewBox="0 0 87 92" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M42.1941 67.5105H50.6329C51.752 67.5105 52.8252 67.9551 53.6165 68.7464C54.4078 69.5377 54.8523 70.6109 54.8523 71.73C54.8523 72.849 54.4078 73.9222 53.6165 74.7135C52.8252 75.5048 51.752 75.9494 50.6329 75.9494H25.3165C24.1974 75.9494 23.1242 75.5048 22.3329 74.7135C21.5416 73.9222 21.097 72.849 21.097 71.73C21.097 70.6109 21.5416 69.5377 22.3329 68.7464C23.1242 67.9551 24.1974 67.5105 25.3165 67.5105H33.7553V8.43882H8.43882V16.8776C8.43882 17.9967 7.99427 19.0699 7.20298 19.8612C6.41169 20.6525 5.33847 21.097 4.21941 21.097C3.10035 21.097 2.02713 20.6525 1.23584 19.8612C0.444543 19.0699 0 17.9967 0 16.8776V4.21941C0 3.10035 0.444543 2.02713 1.23584 1.23584C2.02713 0.444543 3.10035 0 4.21941 0H71.73C72.849 0 73.9222 0.444543 74.7135 1.23584C75.5048 2.02713 75.9494 3.10035 75.9494 4.21941V16.8776C75.9494 17.9967 75.5048 19.0699 74.7135 19.8612C73.9222 20.6525 72.849 21.097 71.73 21.097C70.6109 21.097 69.5377 20.6525 68.7464 19.8612C67.9551 19.0699 67.5105 17.9967 67.5105 16.8776V8.43882H42.1941V67.5105Z" fill="#C5BDFE"/>
                <circle cx="64.7218" cy="68.8229" r="22.2785" fill="#F6F4FF"/>
                <path d="M51.921 76.1102L51.9211 76.1102C53.0893 77.2792 54.9828 77.2794 56.1523 76.1099L59.2556 73.0066C59.8013 72.4609 60.6861 72.4608 61.2318 73.0065C61.7775 73.5522 61.7775 74.437 61.2318 74.9827L58.1285 78.086C55.8677 80.3468 52.2042 80.347 49.9446 78.086L51.921 76.1102ZM51.921 76.1102C50.7524 74.9416 50.7524 73.0478 51.9207 71.8795L58.1284 65.6718L57.9163 65.4596L58.1285 65.6718C59.2968 64.5034 61.1905 64.5034 62.3589 65.6718L62.571 65.4596L62.3589 65.6718C62.9046 66.2174 63.7893 66.2174 64.335 65.6718C64.8807 65.1261 64.8807 64.2413 64.335 63.6956C62.0753 61.4358 58.4121 61.4358 56.1523 63.6956L49.9446 69.9033C47.6849 72.163 47.6849 75.8262 49.9446 78.0859L51.921 76.1102Z" fill="#C5BDFE" stroke="#C5BDFE" stroke-width="0.6"/>
                <path d="M60.4565 67.5747L60.2458 67.7854L60.4565 67.5747C59.9108 67.029 59.026 67.029 58.4804 67.5747C57.9347 68.1204 57.9347 69.0051 58.4804 69.5508C60.7401 71.8106 64.4033 71.8106 66.6631 69.5508L73.4908 62.7231C75.7506 60.4634 75.7506 56.8001 73.4908 54.5403C71.231 52.2806 67.5667 52.2806 65.3069 54.5403L61.5836 58.2636C61.0379 58.8093 61.0379 59.6941 61.5836 60.2398C62.1293 60.7855 63.0141 60.7855 63.5598 60.2398L67.2831 56.5165L67.0709 56.3044L67.2831 56.5165C68.4514 55.3481 70.3463 55.3481 71.5146 56.5165C72.683 57.6848 72.683 59.5786 71.5146 60.7469L64.6869 67.5747C63.5186 68.7431 61.6248 68.743 60.4565 67.5747Z" fill="#C5BDFE" stroke="#C5BDFE" stroke-width="0.6"/>
            </svg>            
            <?php 
            }else if($affiliatepress_type == 'edit_profile_icon'){
            ?>
            <svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect class="ap-svg-border-color" x="0.5" y="0.5" width="37" height="37" rx="7.5" stroke="#C9CFDB"/>
                <path class="ap-svg-content-color" d="M20.2594 10.6002L12.0494 19.2902C11.7394 19.6202 11.4394 20.2702 11.3794 20.7202L11.0094 23.9602C10.8794 25.1302 11.7194 25.9302 12.8794 25.7302L16.0994 25.1802C16.5494 25.1002 17.1794 24.7702 17.4894 24.4302L25.6994 15.7402C27.1194 14.2402 27.7594 12.5302 25.5494 10.4402C23.3494 8.37022 21.6794 9.10022 20.2594 10.6002Z" stroke="#4D5973" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-svg-content-color" d="M18.8906 12.0498C19.3206 14.8098 21.5606 16.9198 24.3406 17.1998" stroke="#4D5973" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-svg-content-color" d="M10 29H28" stroke="#4D5973" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>

            <?php 
            }else if($affiliatepress_type == 'logout_account_icon'){
            ?>
            <svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect class="ap-svg-border-color" x="0.5" y="0.5" width="37" height="37" rx="7.5" stroke="#C9CFDB"/>
                <path class="ap-svg-content-color" d="M22.0996 14.5602C21.7896 10.9602 19.9396 9.49023 15.8896 9.49023H15.7596C11.2896 9.49023 9.4996 11.2802 9.4996 15.7502V22.2702C9.4996 26.7402 11.2896 28.5302 15.7596 28.5302H15.8896C19.9096 28.5302 21.7596 27.0802 22.0896 23.5402" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-svg-content-color" d="M16.0009 19H27.3809" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-svg-content-color" d="M25.15 15.6504L28.5 19.0004L25.15 22.3504" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

            <?php 
            }else if($affiliatepress_type == 'close_account_image'){
            ?>
            <svg width="90" height="90" viewBox="0 0 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="5" y="5" width="80" height="80" rx="40" fill="#FFDDDB"/>
                <rect x="5" y="5" width="80" height="80" rx="40" stroke="#FEF3F2" stroke-width="9.6"/>
                 <path d="M51.6667 34.9997V33.6663C51.6667 31.7995 51.6667 30.8661 51.3034 30.153C50.9838 29.5258 50.4738 29.0159 49.8466 28.6963C49.1336 28.333 48.2002 28.333 46.3333 28.333H43.6667C41.7998 28.333 40.8664 28.333 40.1534 28.6963C39.5262 29.0159 39.0162 29.5258 38.6966 30.153C38.3333 30.8661 38.3333 31.7995 38.3333 33.6663V34.9997M41.6667 44.1663V52.4997M48.3333 44.1663V52.4997M30 34.9997H60M56.6667 34.9997V53.6663C56.6667 56.4666 56.6667 57.8667 56.1217 58.9363C55.6423 59.8771 54.8774 60.642 53.9366 61.1214C52.8671 61.6663 51.4669 61.6663 48.6667 61.6663H41.3333C38.5331 61.6663 37.1329 61.6663 36.0634 61.1214C35.1226 60.642 34.3577 59.8771 33.8783 58.9363C33.3333 57.8667 33.3333 56.4666 33.3333 53.6663V34.9997" stroke="#EB5757" stroke-width="3.33333" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>                
            <?php 
            }else if($affiliatepress_type == 'ap_menu_icon'){
            ?>
            <svg width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19.4696 8C19.4696 7.44051 19.016 6.98696 18.4565 6.98696H1.41304C0.853554 6.98696 0.4 7.44051 0.4 8C0.4 8.55949 0.853554 9.01304 1.41304 9.01304H18.4565C19.016 9.01304 19.4696 8.55949 19.4696 8ZM19.4696 1.91304C19.4696 1.35355 19.016 0.9 18.4565 0.9H1.41304C0.853554 0.9 0.4 1.35356 0.4 1.91304C0.4 2.47253 0.853554 2.92609 1.41304 2.92609H18.4565C19.016 2.92609 19.4696 2.47253 19.4696 1.91304ZM19.4696 14.087C19.4696 13.5275 19.016 13.0739 18.4565 13.0739H1.41304C0.853554 13.0739 0.4 13.5275 0.4 14.087C0.4 14.6464 0.853554 15.1 1.41304 15.1H18.4565C19.016 15.1 19.4696 14.6464 19.4696 14.087Z" fill="#2E3A59" stroke="#2E3A59" stroke-width="0.2" stroke-linecap="round"/>
            </svg>            
            <?php 
            }else if($affiliatepress_type == 'profile_menu_edit_profile_icon'){
            ?>
            <svg width="16" height="16" viewBox="0 0 19 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="ap-front-menu-fill" d="M13.4316 5.82353C13.4316 6.32238 13.3334 6.81635 13.1425 7.27724C12.9516 7.73812 12.6718 8.15689 12.319 8.50964C11.9663 8.86238 11.5475 9.1422 11.0866 9.3331C10.6257 9.524 10.1318 9.62226 9.63291 9.62226C9.13406 9.62226 8.64008 9.524 8.1792 9.3331C7.71832 9.1422 7.29955 8.86238 6.9468 8.50964C6.59406 8.15689 6.31424 7.73812 6.12334 7.27724C5.93243 6.81635 5.83418 6.32238 5.83418 5.82353C5.83418 4.81604 6.2344 3.84982 6.9468 3.13742C7.6592 2.42501 8.62543 2.02479 9.63291 2.02479C10.6404 2.02479 11.6066 2.42502 12.319 3.13742C13.0314 3.84982 13.4316 4.81604 13.4316 5.82353ZM13.6419 9.83255C14.7052 8.76929 15.3025 7.3272 15.3025 5.82353C15.3025 4.31985 14.7052 2.87776 13.6419 1.8145C12.5787 0.751239 11.1366 0.153906 9.63291 0.153906C8.12924 0.153906 6.68714 0.751239 5.62388 1.8145C4.56062 2.87776 3.96329 4.31985 3.96329 5.82353C3.96329 7.3272 4.56062 8.76929 5.62388 9.83255C6.68714 10.8958 8.12924 11.4931 9.63291 11.4931C11.1366 11.4931 12.5787 10.8958 13.6419 9.83255ZM9.63291 12.6856C7.3168 12.6856 5.09555 13.6056 3.45781 15.2434C1.82007 16.8811 0.9 19.1024 0.9 21.4185C0.9 21.6666 0.998555 21.9045 1.17398 22.0799C1.34941 22.2554 1.58735 22.3539 1.83544 22.3539C2.08354 22.3539 2.32147 22.2554 2.4969 22.0799C2.67233 21.9045 2.77089 21.6666 2.77089 21.4185C2.77089 19.5985 3.49385 17.8532 4.78073 16.5663C6.06761 15.2794 7.81299 14.5564 9.63291 14.5564C11.4528 14.5564 13.1982 15.2794 14.4851 16.5663C15.772 17.8532 16.4949 19.5985 16.4949 21.4185C16.4949 21.6666 16.5935 21.9045 16.7689 22.0799C16.9444 22.2554 17.1823 22.3539 17.4304 22.3539C17.6785 22.3539 17.9164 22.2554 18.0918 22.0799C18.2673 21.9045 18.3658 21.6666 18.3658 21.4185C18.3658 19.1024 17.4458 16.8811 15.808 15.2434C14.1703 13.6056 11.949 12.6856 9.63291 12.6856Z" fill="#656E81" stroke="#656E81" stroke-width="0.2"/>
            </svg>            
            <?php 
            }else if($affiliatepress_type == 'profile_menu_logout_icon'){
            ?>
            <svg width="19" height="19" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="ap-front-menu-fill" d="M15.121 16.754C14.8598 16.754 14.6092 16.8578 14.4245 17.0425C14.2398 17.2273 14.136 17.4778 14.136 17.739V20.1387C14.1357 20.5075 13.9891 20.8612 13.7283 21.122C13.4675 21.3828 13.114 21.5296 12.7451 21.53H5.86087C5.49205 21.5296 5.13846 21.3828 4.8777 21.122C4.61694 20.8612 4.47031 20.5075 4.46998 20.1387V5.86128C4.47031 5.49246 4.61694 5.13883 4.8777 4.87799C5.13846 4.61715 5.49205 4.47042 5.86087 4.46998H12.7451C13.114 4.47042 13.4675 4.61715 13.7283 4.87799C13.9891 5.13883 14.1357 5.49246 14.136 5.86128V7.93551C14.136 8.19674 14.2398 8.44728 14.4245 8.632C14.6092 8.81672 14.8598 8.9205 15.121 8.9205C15.3822 8.9205 15.6328 8.81672 15.8175 8.632C16.0022 8.44728 16.106 8.19674 16.106 7.93551V5.86128C16.1049 4.97022 15.7505 4.11596 15.1205 3.48584C14.4904 2.85573 13.6362 2.50119 12.7451 2.5H5.86087C4.96981 2.50119 4.11559 2.85573 3.48555 3.48584C2.85551 4.11596 2.50109 4.97022 2.5 5.86128V20.1387C2.50109 21.0298 2.85551 21.884 3.48555 22.5142C4.11559 23.1443 4.96981 23.4988 5.86087 23.5H12.7451C13.6362 23.4988 14.4904 23.1443 15.1205 22.5142C15.7505 21.884 16.1049 21.0298 16.106 20.1387V17.739C16.106 17.4778 16.0022 17.2273 15.8175 17.0425C15.6328 16.8578 15.3822 16.754 15.121 16.754Z" fill="#656E81"/>
                <path class="ap-front-menu-fill" d="M23.2096 12.2835L19.7043 8.78059C19.5219 8.5983 19.2754 8.49477 19.0175 8.49224C18.7597 8.4897 18.5112 8.58837 18.3253 8.76705C17.9272 9.14996 17.9366 9.79842 18.3273 10.1891L20.1524 12.0155H11.6158C11.3546 12.0155 11.1041 12.1192 10.9194 12.304C10.7346 12.4887 10.6309 12.7392 10.6309 13.0005C10.6309 13.2617 10.7346 13.5122 10.9194 13.6969C11.1041 13.8817 11.3546 13.9854 11.6158 13.9854H20.1524L18.3097 15.8282C18.1249 16.0129 18.0211 16.2635 18.021 16.5247C18.021 16.786 18.1248 17.0366 18.3095 17.2213C18.4942 17.4061 18.7447 17.5099 19.006 17.51C19.2673 17.51 19.5179 17.4063 19.7026 17.2216L23.2096 13.7162C23.3038 13.6222 23.3785 13.5105 23.4295 13.3876C23.4805 13.2647 23.5068 13.1329 23.5068 12.9998C23.5068 12.8668 23.4805 12.735 23.4295 12.6121C23.3785 12.4891 23.3038 12.3775 23.2096 12.2835Z" fill="#656E81"/>
            </svg>            
                       
            <?php 
            }else if($affiliatepress_type == 'front_menu_close_icon'){
            ?>
            <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7.02071 20.6552L7.02073 20.6552L13.0004 14.6734L18.9812 20.6541L18.9812 20.6541L18.9825 20.6554C19.2056 20.8709 19.5045 20.9902 19.8148 20.9875C20.1251 20.9848 20.4219 20.8603 20.6413 20.6409C20.8607 20.4215 20.9851 20.1247 20.9878 19.8145C20.9905 19.5042 20.8712 19.2053 20.6557 18.9821L20.6557 18.9821L20.6545 18.9809L14.6737 13.0001L20.6545 7.0193L20.6545 7.01928C20.8764 6.79724 21.001 6.49615 21.0008 6.18226C21.0007 5.86836 20.8759 5.56736 20.6539 5.34548C20.4319 5.1236 20.1308 4.99901 19.8169 4.99912C19.503 4.99923 19.202 5.12403 18.9801 5.34607L13.0004 11.3268L7.01963 5.34605L7.01964 5.34604L7.01839 5.34483C6.79521 5.12927 6.4963 5.01 6.18603 5.0127C5.87577 5.01539 5.57897 5.13984 5.35957 5.35924C5.14017 5.57864 5.01572 5.87544 5.01303 6.1857C5.01033 6.49597 5.1296 6.79488 5.34516 7.01806L5.34515 7.01807L5.34638 7.0193L11.3272 13.0001L5.34638 18.9809C5.12434 19.2029 4.99961 19.5041 4.99961 19.8181C4.99961 20.132 5.12435 20.4332 5.34638 20.6552C5.56841 20.8772 5.86955 21.002 6.18355 21.002C6.49754 21.002 6.79868 20.8772 7.02071 20.6552Z" fill="#656E81" stroke="#656E81" stroke-width="0.2"/>
            </svg>                           
            <?php 
            }else if($affiliatepress_type == 'filter_icon'){
            ?>
            <svg width="23" height="24" viewBox="0 0 23 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21.5387 1H1.9244C1.75473 0.999698 1.58826 1.04625 1.44337 1.13454C1.29847 1.22282 1.18076 1.3494 1.10323 1.50032C1.02461 1.65338 0.989784 1.82517 1.00259 1.99676C1.0154 2.16835 1.07535 2.33307 1.17582 2.47276L8.36166 12.5954C8.36406 12.5989 8.36669 12.6021 8.36909 12.6056C8.63015 12.9582 8.77142 13.3851 8.77219 13.8238V22.0767C8.77141 22.1977 8.79457 22.3176 8.84034 22.4296C8.88611 22.5416 8.95358 22.6435 9.03887 22.7293C9.12415 22.8151 9.22557 22.8832 9.33729 22.9297C9.44901 22.9761 9.56881 23 9.6898 23C9.81415 23 9.93694 22.9752 10.0516 22.9276L14.0895 21.3879C14.4514 21.2775 14.6915 20.9361 14.6915 20.525V13.8238C14.6923 13.3852 14.8335 12.9582 15.0944 12.6056C15.0968 12.6021 15.0995 12.5989 15.1019 12.5954L22.2875 2.47232C22.3879 2.33272 22.4479 2.16808 22.4607 1.99657C22.4735 1.82506 22.4387 1.65335 22.3601 1.50038C22.2825 1.34942 22.1648 1.22281 22.0198 1.13452C21.8749 1.04622 21.7084 0.999675 21.5387 1ZM14.194 11.944C13.7926 12.4887 13.5756 13.1473 13.5743 13.8238V20.3893L9.88892 21.7944V13.8238C9.88767 13.1472 9.67049 12.4887 9.26899 11.944L2.29283 2.11679H21.1704L14.194 11.944Z" fill="#656E81" stroke="#656E81" stroke-width="0.6"/>
            </svg>                
            <?php 
            }else if($affiliatepress_type == 'date_calendar_icon'){
            ?>
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.1429 1.12H12.2857V0.571429C12.2857 0.419876 12.2255 0.274531 12.1183 0.167368C12.0112 0.0602039 11.8658 0 11.7143 0C11.5627 0 11.4174 0.0602039 11.3102 0.167368C11.2031 0.274531 11.1429 0.419876 11.1429 0.571429V1.12H4.85714V0.571429C4.85714 0.419876 4.79694 0.274531 4.68978 0.167368C4.58261 0.0602039 4.43727 0 4.28571 0C4.13416 0 3.98882 0.0602039 3.88165 0.167368C3.77449 0.274531 3.71429 0.419876 3.71429 0.571429V1.12H2.85714C1.28171 1.12 0 2.40171 0 3.97714V13.1429C0 14.7183 1.28171 16 2.85714 16H13.1429C14.7183 16 16 14.7183 16 13.1429V3.97714C16 2.40171 14.7183 1.12 13.1429 1.12ZM1.14286 3.97714C1.14286 3.032 1.912 2.26286 2.85714 2.26286H3.71429V2.81143C3.71429 2.96298 3.77449 3.10833 3.88165 3.21549C3.98882 3.32265 4.13416 3.38286 4.28571 3.38286C4.43727 3.38286 4.58261 3.32265 4.68978 3.21549C4.79694 3.10833 4.85714 2.96298 4.85714 2.81143V2.26286H11.1429V2.81143C11.1429 2.96298 11.2031 3.10833 11.3102 3.21549C11.4174 3.32265 11.5627 3.38286 11.7143 3.38286C11.8658 3.38286 12.0112 3.32265 12.1183 3.21549C12.2255 3.10833 12.2857 2.96298 12.2857 2.81143V2.26286H13.1429C14.088 2.26286 14.8571 3.032 14.8571 3.97714V4.55429H1.14286V3.97714ZM13.1429 14.8571H2.85714C1.912 14.8571 1.14286 14.088 1.14286 13.1429V5.69714H14.8571V13.1429C14.8571 14.088 14.088 14.8571 13.1429 14.8571Z" fill="#656E81"/>
            </svg>            
            <?php 
            }else if($affiliatepress_type == 'lock_screen_icon'){
            ?>
            <svg width="244" height="240" viewBox="0 0 244 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M54.4651 67.3838V135.395H189.24V67.3838C189.24 30.2305 159.01 0 121.849 0C84.688 0 54.4651 30.2305 54.4651 67.3838ZM65.8015 67.3838C65.8015 36.4278 90.8928 11.3289 121.849 11.3289C152.805 11.3289 177.896 36.4278 177.896 67.3838V124.058H65.8015V67.3838Z" fill="#C5BDFE"/>
                <path d="M65.8015 67.3838V124.058H177.904V67.3838C177.904 36.4277 152.812 11.3289 121.856 11.3289C90.9004 11.3289 65.8015 36.4277 65.8015 67.3838ZM77.138 67.3838C77.138 42.7308 97.1959 22.6729 121.856 22.6729C146.517 22.6729 166.575 42.7308 166.575 67.3838V112.722H77.138V67.3838Z" fill="#DEDAFF"/>
                <path d="M46.6431 240H197.078C205.421 240 212.193 233.236 212.193 224.885V115.639C212.193 107.296 205.421 100.524 197.078 100.524H46.6431C38.2919 100.524 31.5278 107.296 31.5278 115.639V224.885C31.5278 233.236 38.2919 240 46.6431 240Z" fill="#DEDAFF"/>
                <path d="M148.172 179.932H153.893C158.745 179.932 162.675 175.979 162.645 171.127V170.394C162.675 165.285 158.519 161.106 153.417 161.106H146.018C140.365 161.106 135.294 156.874 134.999 151.228C134.682 145.205 139.473 140.224 145.429 140.224H172.122C177.171 140.224 180.935 135.984 180.874 130.936V130.679C180.935 125.63 177.171 121.398 172.122 121.398H163.492C157.838 121.398 152.767 117.166 152.473 111.52C152.155 105.497 156.947 100.516 162.902 100.516H197.078C205.421 100.516 212.193 107.28 212.193 115.632V224.877C212.193 233.228 205.421 239.992 197.078 239.992H152.813C147.046 239.992 142.368 235.314 142.368 229.548C142.368 223.781 147.046 219.103 152.813 219.103H166.779C171.608 219.103 175.538 215.15 175.516 210.321V210.208C175.516 209.996 175.523 209.785 175.538 209.573C175.826 204.812 172.047 200.799 167.278 200.799H148.762C143.109 200.799 138.037 196.566 137.743 190.921C137.433 184.912 142.224 179.932 148.172 179.932Z" fill="#D3CDFF"/>
                <path d="M99.146 157.388C99.146 144.842 109.319 134.677 121.857 134.677C134.402 134.677 144.567 144.849 144.567 157.388C144.567 165.761 139.98 173.002 133.231 176.939L136.345 205.847H107.361L110.475 176.939C103.726 173.002 99.146 165.769 99.146 157.388Z" fill="#C5BDFE"/>
                <path d="M239 195C236.239 195 234 192.761 234 190C234 187.239 236.239 185 239 185C241.761 185 244 187.239 244 190C244 192.761 241.761 195 239 195Z" fill="#E8E7F6"/>
                <path d="M234 51C235.657 51 237 49.6569 237 48C237 46.3431 235.657 45 234 45C232.343 45 231 46.3431 231 48C231 49.6569 232.343 51 234 51Z" fill="#DEDAFF"/>
                <path d="M33.8043 37.6085C37.0099 37.6085 39.6085 35.0099 39.6085 31.8043C39.6085 28.5987 37.0099 26 33.8043 26C30.5987 26 28 28.5987 28 31.8043C28 35.0099 30.5987 37.6085 33.8043 37.6085Z" fill="#E8E7F6"/>
                <path d="M6 190C9.31371 190 12 187.314 12 184C12 180.686 9.31371 178 6 178C2.68629 178 0 180.686 0 184C0 187.314 2.68629 190 6 190Z" fill="#E8E7F6"/>
            </svg>                
            <?php 
            }else if($affiliatepress_type == 'total_earnings'){
                ?>
                <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect class="ap-primary-color-fill" width="50" height="50" rx="25" fill="#6858E0" fill-opacity="0.06"/>
                    <path class="ap-primary-color-stroke" d="M13.334 27.3333C13.334 22.9336 13.334 20.7337 14.7008 19.3668C16.0677 18 18.2675 18 22.6673 18H27.334C31.7337 18 33.9337 18 35.3005 19.3668C36.6673 20.7337 36.6673 22.9336 36.6673 27.3333C36.6673 31.7331 36.6673 33.933 35.3005 35.2998C33.9337 36.6667 31.7337 36.6667 27.334 36.6667H22.6673C18.2675 36.6667 16.0677 36.6667 14.7008 35.2998C13.334 33.933 13.334 31.7331 13.334 27.3333Z" stroke="#6858E0" stroke-width="1.5"/>
                    <path class="ap-primary-color-stroke" d="M29.6673 17.9999C29.6673 15.8 29.6673 14.7001 28.9839 14.0167C28.3005 13.3333 27.2005 13.3333 25.0007 13.3333C22.8008 13.3333 21.7008 13.3333 21.0174 14.0167C20.334 14.7001 20.334 15.8 20.334 17.9999" stroke="#6858E0" stroke-width="1.5"/>
                    <path class="ap-primary-color-stroke" d="M25.0013 31.2223C26.29 31.2223 27.3346 30.3517 27.3346 29.2779C27.3346 28.204 26.29 27.3334 25.0013 27.3334C23.7126 27.3334 22.668 26.4628 22.668 25.3889C22.668 24.3151 23.7126 23.4446 25.0013 23.4446M25.0013 31.2223C23.7126 31.2223 22.668 30.3517 22.668 29.2779M25.0013 31.2223V32.0001M25.0013 23.4446V22.6667M25.0013 23.4446C26.29 23.4446 27.3346 24.3151 27.3346 25.3889" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round"/>
                </svg>

            <?php 
            }
            else if($affiliatepress_type == 'paid_earnings'){
                ?>
                <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect class="ap-primary-color-fill" width="50" height="50" rx="25" fill="#6858E0" fill-opacity="0.06"/>
                    <path class="ap-primary-color-stroke" d="M16.5996 34.9855H19.3115C20.5246 34.9855 21.7507 35.1172 22.9312 35.3705C25.0195 35.8187 27.2182 35.8729 29.3279 35.5173C30.3682 35.3418 31.3908 35.0737 32.3166 34.6082C33.1523 34.188 34.1759 33.5958 34.8635 32.9324C35.5502 32.27 36.2652 31.1859 36.7727 30.3384C37.208 29.6118 36.9975 28.7203 36.309 28.1788C35.5444 27.5774 34.4097 27.5775 33.6451 28.1792L31.4765 29.8855C30.636 30.5469 29.718 31.1557 28.6243 31.3374C28.4928 31.3593 28.3551 31.3792 28.2113 31.3965M28.2113 31.3965C28.168 31.4018 28.1242 31.4068 28.0798 31.4115M28.2113 31.3965C28.3863 31.3575 28.5599 31.245 28.723 31.0969C29.4948 30.3952 29.5435 29.2125 28.8738 28.4289C28.7185 28.2472 28.5366 28.0955 28.3345 27.9699C24.9777 25.8843 19.7549 27.4728 16.5996 29.8037M28.2113 31.3965C28.1675 31.4063 28.1236 31.4115 28.0798 31.4115M28.0798 31.4115C27.4517 31.4787 26.7171 31.496 25.9018 31.4158" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round"/>
                    <path class="ap-primary-color-stroke" d="M16.6 28.875C16.6 27.8395 15.7941 27 14.8 27C13.8059 27 13 27.8395 13 28.875V35.125C13 36.1605 13.8059 37 14.8 37C15.7941 37 16.6 36.1605 16.6 35.125V28.875Z" stroke="#6858E0" stroke-width="1.5"/>
                    <path class="ap-primary-color-stroke" d="M25.5 21.1666C26.8807 21.1666 28 20.2339 28 19.0834C28 17.9328 26.8807 17 25.5 17C24.1193 17 23 16.0672 23 14.9166C23 13.7661 24.1193 12.8334 25.5 12.8334M25.5 21.1666C24.1193 21.1666 23 20.2339 23 19.0834M25.5 21.1666V22M25.5 12.8334V12M25.5 12.8334C26.8807 12.8334 28 13.7661 28 14.9166" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            <?php 
            }else if($affiliatepress_type == 'unpaid_earnings'){
                ?>
                <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect class="ap-primary-color-fill" width="50" height="50" rx="25" fill="#6858E0" fill-opacity="0.06"/>
                    <path class="ap-primary-color-stroke" d="M25 37C31.6274 37 37 31.6274 37 25C37 18.3726 31.6274 13 25 13C18.3726 13 13 18.3726 13 25C13 31.6274 18.3726 37 25 37Z" stroke="#6858E0" stroke-width="1.5"/>
                    <path class="ap-primary-color-stroke" d="M25 30.8335V31.4168V32.0002" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round"/>
                    <path class="ap-primary-color-stroke" d="M25 18V18.5833V19.1667" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round"/>
                    <path class="ap-primary-color-stroke" d="M28.5 22.0832C28.5 20.4723 26.933 19.1665 25 19.1665C23.0669 19.1665 21.5 20.4723 21.5 22.0832C21.5 23.694 23.0669 24.9998 25 24.9998C26.933 24.9998 28.5 26.3057 28.5 27.9165C28.5 29.5273 26.933 30.8332 25 30.8332C23.0669 30.8332 21.5 29.5273 21.5 27.9165" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round"/>
                    <path class="ap-primary-color-stroke" d="M17 17L33 33" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round"/>
                </svg>

            <?php 
            }else if($affiliatepress_type == 'visits'){
                ?>
                <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect class="ap-primary-color-fill" width="50" height="50" rx="25" fill="#6858E0" fill-opacity="0.06"/>
                    <path class="ap-primary-color-stroke" d="M14.8214 28.8451C13.8298 27.5568 13.334 26.9127 13.334 25.0001C13.334 23.0874 13.8298 22.4434 14.8214 21.1551C16.8013 18.5829 20.1218 15.6667 25.0007 15.6667C29.8795 15.6667 33.2 18.5829 35.1799 21.1551C36.1715 22.4434 36.6673 23.0874 36.6673 25.0001C36.6673 26.9127 36.1715 27.5568 35.1799 28.8451C33.2 31.4172 29.8795 34.3334 25.0007 34.3334C20.1218 34.3334 16.8013 31.4172 14.8214 28.8451Z" stroke="#6858E0" stroke-width="1.5"/>
                    <path class="ap-primary-color-stroke" d="M28.5 25C28.5 26.933 26.933 28.5 25 28.5C23.0669 28.5 21.5 26.933 21.5 25C21.5 23.0669 23.0669 21.5 25 21.5C26.933 21.5 28.5 23.0669 28.5 25Z" stroke="#6858E0" stroke-width="1.5"/>
                </svg>

            <?php 
            }else if($affiliatepress_type == 'total_commission'){
                ?>
                <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect class="ap-primary-color-fill" width="50" height="50" rx="25" fill="#6858E0" fill-opacity="0.06"/>
                    <path class="ap-primary-color-stroke" d="M15.6513 28.0962L13.8753 26.3202C13.1509 25.5958 13.1509 24.404 13.8753 23.6796L15.6513 21.9036C15.9551 21.5998 16.2005 21.0039 16.2005 20.5833V18.0711C16.2005 17.0429 17.0418 16.2017 18.07 16.2017H20.582C21.0027 16.2017 21.5986 15.9564 21.9024 15.6526L23.6783 13.8766C24.4027 13.1521 25.5946 13.1521 26.319 13.8766L28.095 15.6526C28.3988 15.9564 28.9946 16.2017 29.4152 16.2017H31.9274C32.9556 16.2017 33.7968 17.0429 33.7968 18.0711V20.5833C33.7968 21.0039 34.0422 21.5998 34.3459 21.9036L36.1221 23.6796C36.8465 24.404 36.8465 25.5958 36.1221 26.3202L34.3459 28.0962C34.0422 28.4 33.7968 28.996 33.7968 29.4166V31.9286C33.7968 32.9568 32.9556 33.7981 31.9274 33.7981H29.4152C28.9946 33.7981 28.3988 34.0434 28.095 34.3472L26.319 36.1232C25.5946 36.8476 24.4027 36.8476 23.6783 36.1232L21.9024 34.3472C21.5986 34.0434 21.0027 33.7981 20.582 33.7981H18.07C17.0418 33.7981 16.2005 32.9568 16.2005 31.9286V29.4166C16.2005 28.9843 15.9551 28.3883 15.6513 28.0962Z" stroke="#6858E0" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <path class="ap-primary-color-stroke" d="M21.5039 28.4936L28.5144 21.4832" stroke="#6858E0" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <path class="ap-primary-color-stroke" d="M27.9238 27.9094H27.9343" stroke="#6858E0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path class="ap-primary-color-stroke" d="M22.082 22.0674H22.0925" stroke="#6858E0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                
            <?php 
            }else if($affiliatepress_type == 'commission_rate'){
                ?>
                <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect class="ap-primary-color-fill" width="50" height="50" rx="25" fill="#6858E0" fill-opacity="0.06"/>
                    <path class="ap-primary-color-stroke" d="M22.2784 19.5555C22.2784 21.0589 21.0596 22.2777 19.5562 22.2777C18.0528 22.2777 16.834 21.0589 16.834 19.5555C16.834 18.052 18.0528 16.8333 19.5562 16.8333C21.0596 16.8333 22.2784 18.052 22.2784 19.5555Z" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path class="ap-primary-color-stroke" d="M33.1671 30.4444C33.1671 31.9479 31.9484 33.1666 30.4449 33.1666C28.9414 33.1666 27.7227 31.9479 27.7227 30.4444C27.7227 28.9409 28.9414 27.7222 30.4449 27.7222C31.9484 27.7222 33.1671 28.9409 33.1671 30.4444Z" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path class="ap-primary-color-stroke" d="M17.7422 32.2592L32.2607 17.7407" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            <?php 
            }else if($affiliatepress_type == 'cookie_duration'){
                ?>
               <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="36" height="36" rx="18" fill="#FFF2D6"/>
                <path d="M15 22H15.01M18 17H18.01M13 16H13.01M21 22H21.01M27 18C27 22.9706 22.9706 27 18 27C13.0294 27 9 22.9706 9 18C9 13.0294 13.0294 9 18 9C18 11.7614 19.7909 14 22 14C22 16.2091 24.2386 18 27 18Z" stroke="#FFC757" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            <?php 
            }else if($affiliatepress_type == 'tooltip_info'){
                ?>
                    <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path class="ap-svg-content-color-fill" d="M8.25 0C3.69023 0 0 3.68981 0 8.25C0 12.8097 3.68981 16.5 8.25 16.5C12.8098 16.5 16.5 12.8102 16.5 8.25C16.5 3.6903 12.8102 0 8.25 0ZM8.25 15.3488C4.3357 15.3488 1.15117 12.1643 1.15117 8.25C1.15117 4.33567 4.3357 1.15117 8.25 1.15117C12.1643 1.15117 15.3488 4.33567 15.3488 8.25C15.3488 12.1643 12.1643 15.3488 8.25 15.3488Z" fill="#576582"/>
                        <path class="ap-svg-content-color-fill" d="M8.25025 6.87695C7.76156 6.87695 7.41406 7.08333 7.41406 7.38739V11.5248C7.41406 11.7855 7.76156 12.0461 8.25025 12.0461C8.71721 12.0461 9.09726 11.7855 9.09726 11.5248V7.38732C9.09726 7.0833 8.71721 6.87695 8.25025 6.87695Z" fill="#576582"/>
                        <path class="ap-svg-content-color-fill" d="M8.24983 4.3252C7.75028 4.3252 7.35938 4.68355 7.35938 5.09622C7.35938 5.50891 7.75032 5.87813 8.24983 5.87813C8.73851 5.87813 9.12948 5.50891 9.12948 5.09622C9.12948 4.68355 8.73848 4.3252 8.24983 4.3252Z" fill="#576582"/>
                    </svg>
                <?php 
            }
        }

        /**
         * Function for set remember cookie
         *
         * @param  mixed $affiliatepress_expiration
         * @param  mixed $affiliatepress_user_id
         * @param  mixed $remember
         * @return void
        */
        function affiliatepress_custom_remember_me_duration($affiliatepress_expiration, $affiliatepress_user_id, $remember) {            
            if ($remember) {
                $affiliatepress_expiration = 30 * DAY_IN_SECONDS;
            }        
            return $affiliatepress_expiration;
        }        
        
        /**
         * Function for affiliate panel access user
         *
         * @param  mixed $affiliatepress_user_id
         * @return void
        */
        function affilatepress_allow_affiliate_user_to_access_affiliate_panel($affiliatepress_user_id){
            global $affiliatepress_tbl_ap_affiliates, $affiliatepress_affiliate_user_allowed_to_acceess;
            $affiliatepress_allow_affiliate_user = false;
            if(empty($affiliatepress_affiliate_user_allowed_to_acceess)){
                $affiliatepress_affiliates_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_id', 'WHERE ap_affiliates_user_id  = %d AND ap_affiliates_status = %d', array( $affiliatepress_user_id, 1 ), '', '', '', true, false,ARRAY_A);
                if($affiliatepress_affiliates_id){
                    $affiliatepress_allow_affiliate_user = $affiliatepress_affiliates_id;
                    $affiliatepress_affiliate_user_allowed_to_acceess = $affiliatepress_affiliates_id;
                }                    
            }
            else {
                $affiliatepress_allow_affiliate_user = $affiliatepress_affiliate_user_allowed_to_acceess;
            }
            return $affiliatepress_allow_affiliate_user;
        }
        
        
        /**
         * validate user login
         *
         * @param  mixed $affiliatepress_username_or_email
         * @param  mixed $affiliatepress_password
         * @return void
         */
        function affiliatepress_validate_user_login($affiliatepress_username_or_email, $affiliatepress_password) {
            // Check if it's an email
            if (is_email($affiliatepress_username_or_email)) {
                // Get the user by email
                $affiliatepress_user = get_user_by('email', $affiliatepress_username_or_email);
            } else {
                // Get the user by username
                $affiliatepress_user = get_user_by('login', $affiliatepress_username_or_email);
            }
        
            // If user exists and password matches
            if ($affiliatepress_user && wp_check_password($affiliatepress_password, $affiliatepress_user->user_pass, $affiliatepress_user->ID)) {
                return $affiliatepress_user->ID; // Successful login
            }
        
            return ''; // Invalid login
        }

                
        /**
         * Function for set login cookie
         *
         * @param  mixed $affiliatepress_username
         * @param  mixed $affiliatepress_password
         * @return void
        */
        function affiliatepress_set_login_cookies($affiliatepress_username, $affiliatepress_password) {            
            
        }
        
        /**
         * Function for get login cookie
         *
         * @return void
        */
        function affiliatepress_get_login_cookies() {
            if (isset($_COOKIE['login_username']) && isset($_COOKIE['login_password'])) {
                $affiliatepress_username = sanitize_text_field($_COOKIE['login_username']);// phpcs:ignore 
                $affiliatepress_password = '';// phpcs:ignore         
                /* You can now use these for authentication or other purposes */
                return array('username' => $affiliatepress_username, 'password' => $affiliatepress_password);
            }        
            return array();
        }
        

        /**
         * Function for affiliate login account
         *
         * @return void
        */
        function affiliatepress_affiliate_login_account_func(){

            global $AffiliatePress;            
            $affiliatepress_login_err_msg = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('login_error_message', 'message_settings'));

			$response              = array();
			$response['variant']   = 'error';
			$response['title']     = esc_html__('Error','affiliatepress-affiliate-marketing');
			$response['msg']       = stripslashes_deep($affiliatepress_login_err_msg);  
			$response['after_register_redirect'] = "";         
			$affiliatepress_wpnonce               = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash($_POST['_wpnonce']) ) : '';// phpcs:ignore 
			$affiliatepress_verify_nonce_flag = wp_verify_nonce( $affiliatepress_wpnonce, 'ap_wp_nonce' );
			if ( ! $affiliatepress_verify_nonce_flag ) {
				$response['msg']     = esc_html__( 'Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
				if($return_data){
					return $response;
				}
				echo wp_json_encode( $response );
				die();
			}            

			$affiliatepress_login_email = !empty($_POST['login_email_address']) ? sanitize_text_field($_POST['login_email_address']) : '';// phpcs:ignore 
			$affiliatepress_login_pass = !empty($_POST['login_password']) ? sanitize_text_field($_POST['login_password']) : ''; // phpcs:ignore 
			$affiliatepress_remember_me = !empty($_POST['is_remember']) ? true : false;
            
			if(!empty($affiliatepress_login_email) && !empty($affiliatepress_login_pass)){

                $affiliatepress_panel_response = array();
                $affiliatepress_panel_response = apply_filters('affiliatepress_modify_affiliate_panel_response', $affiliatepress_panel_response);
                if(!empty($affiliatepress_panel_response) && $affiliatepress_panel_response['variant'] == 'error'){
                    echo wp_json_encode($affiliatepress_panel_response);
                    exit;
                }
                
                $affiliatepress_user_id = $this->affiliatepress_validate_user_login($affiliatepress_login_email,$affiliatepress_login_pass);

                if(!$affiliatepress_user_id){
                    
                    $response              = array();
                    $response['variant']   = 'error';
                    $response['title']     = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']       = stripslashes_deep($affiliatepress_login_err_msg);
                    echo wp_json_encode($response);
                    exit;
                }                

				$affiliatepress_login_arr = array(
					'user_login'    => $affiliatepress_login_email,
					'user_password' => $affiliatepress_login_pass,
					'remember'      => $affiliatepress_remember_me
				);
                
                $affiliatepress_user = get_userdata($affiliatepress_user_id);

				$affiliatepress_user_signin = wp_signon($affiliatepress_login_arr);								
				if(!is_wp_error($affiliatepress_user_signin)){                    
                    if($affiliatepress_remember_me){
                        $this->affiliatepress_set_login_cookies($affiliatepress_login_email,$affiliatepress_login_pass);
                    }                        
                    wp_set_current_user( $affiliatepress_user_signin->ID );
                    $response['variant'] = 'success';
                    $response['title'] = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg'] = esc_html__('Login Successfully', 'affiliatepress-affiliate-marketing');
                    $response['current_logged_id'] =  wp_get_current_user();
                    $response['new_nonce'] = wp_create_nonce('ap_wp_nonce');    
                    $affiliatepress_affiliate_account_page_id = $AffiliatePress->affiliatepress_get_settings('affiliate_account_page_id', 'affiliate_settings');
                    $affiliatepress_affiliate_login_page_url  = get_permalink($affiliatepress_affiliate_account_page_id);  
                    $affiliatepress_affiliate_login_page_url = apply_filters('affiliatepress_modify_affiliate_panel_redirect_link', $affiliatepress_affiliate_login_page_url);
                    $affiliatepress_affiliate_login_page_url = add_query_arg( 'ap-nocache',current_time('timestamp'),$affiliatepress_affiliate_login_page_url);
                    $response['after_register_redirect'] = $affiliatepress_affiliate_login_page_url;                
				}
			}
			echo wp_json_encode($response);
			exit;
        }

        /**
         * Function for add forget password functionality
         *
         * @return void
        */
        function affiliatepress_forgot_password_account_func(){
            
            global $AffiliatePress;

			$affiliatepress_forgot_password_err_msg     = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('affiliate_wrong_email', 'message_settings'));
			$affiliatepress_forgot_password_success_msg = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('send_password_reset_link', 'message_settings'));           

			$response              = array();
			$response['variant']   = 'error';
			$response['title']     = esc_html__('Error', 'affiliatepress-affiliate-marketing');
			$response['msg']       = stripslashes_deep($affiliatepress_forgot_password_err_msg);

			$affiliatepress_wpnonce               = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash($_POST['_wpnonce']) ) : '';// phpcs:ignore 
			$affiliatepress_ap_verify_nonce_flag = wp_verify_nonce( $affiliatepress_wpnonce, 'ap_wp_nonce' );
			if ( ! $affiliatepress_ap_verify_nonce_flag ) {
				$response['msg']     = esc_html__( 'Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
				echo wp_json_encode( $response );
				die();
			}
			$affiliatepress_forgot_pass_email = !empty($_POST['forgot_pass_email_address']) ? sanitize_email($_POST['forgot_pass_email_address']) : '';// phpcs:ignore 
			if(!empty($affiliatepress_forgot_pass_email)){
				$return  = $this->affiliatepress_send_forgotpassword_email($affiliatepress_forgot_pass_email);
				if($return){
					$response['variant'] = 'success';
					$response['title'] = esc_html__('Success', 'affiliatepress-affiliate-marketing');
					$response['msg'] = stripslashes_deep($affiliatepress_forgot_password_success_msg);
				}
			}

			echo wp_json_encode($response);
			exit;
        }

		/**
		 * Function for send forgot password email notification
		 *
		 * @param  mixed $affiliatepress_email
		 * @return void
		 */
		function affiliatepress_send_forgotpassword_email($affiliatepress_email){
			
			global $affiliatePress,$wpdb,$affiliatepress_other_debug_log_id;	
			$affiliatepress_user_data = "";	
			if ( empty( $affiliatepress_email ) ) {
				return false;
			} else if ( strpos( $affiliatepress_email, '@' ) ) {
				$affiliatepress_user_data = get_user_by( 'email', trim( $affiliatepress_email ) );
				if ( empty( $affiliatepress_user_data ) )
					return false;
			} else {
				$affiliatepress_login = trim($affiliatepress_email);
				$affiliatepress_user_data = get_user_by('login', $affiliatepress_login);				
				if ( !$affiliatepress_user_data ) 			
					return false;
			}	

			do_action('affiliatepress_lostpassword_post');
			
			// redefining user_login ensures we return the right case in the email
			$affiliatepress_user_login = $affiliatepress_user_data->user_login;
			$affiliatepress_user_email = $affiliatepress_user_data->user_email;
			
			do_action('affiliatepress_retrieve_password', $affiliatepress_user_login);
		
			$affiliatepress_allow = true;
			$affiliatepress_allow = apply_filters('affiliatepress_allow_password_reset', $affiliatepress_allow, $affiliatepress_user_data->ID);

			if ( ! $affiliatepress_allow )
				return false;
			else if ( is_wp_error($affiliatepress_allow) )
				return false;
			
			$affiliatepress_key = get_password_reset_key($affiliatepress_user_data);			
			$affiliatepress_message = esc_html__('Someone requested that the password be reset for the following account:', 'affiliatepress-affiliate-marketing') . "\r\n\r\n";
			$affiliatepress_message .= network_home_url( '/' ) . "\r\n\r\n ";
			/* translators: 1. Username */
			$affiliatepress_message .= sprintf(esc_html__('Username: %s', 'affiliatepress-affiliate-marketing'), $affiliatepress_user_login) . "\r\n\r\n";
			$affiliatepress_message .= esc_html__('If this was a mistake, just ignore this email and nothing will happen.', 'affiliatepress-affiliate-marketing') . "\r\n\r\n";
			$affiliatepress_message .= esc_html__('To reset your password, visit the following address:', 'affiliatepress-affiliate-marketing') . "\r\n\r\n ";
			$affiliatepress_password_reset_link = network_site_url("wp-login.php?action=rp&key=$affiliatepress_key&login=" . rawurlencode($affiliatepress_user_login), 'login');
			$affiliatepress_message .= $affiliatepress_password_reset_link." \r\n";

			$affiliatepress_blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

			$affiliatepress_title = sprintf( esc_html__('[%s] Password Reset', 'affiliatepress-affiliate-marketing'), $affiliatepress_blogname );	// phpcs:ignore
			$affiliatepress_title = apply_filters('retrieve_password_title', $affiliatepress_title);// phpcs:ignore
			$affiliatepress_message = apply_filters('retrieve_password_message', $affiliatepress_message, $affiliatepress_key, $affiliatepress_user_login, $affiliatepress_user_data);	// phpcs:ignore

            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Forget Password Action', 'affiliatepress_email_notiifcation', $affiliatepress_message, $affiliatepress_other_debug_log_id);
            wp_mail($affiliatepress_user_email, $affiliatepress_title, $affiliatepress_message);

			return true;
		}

        /**
         * Function for display vue method
         *
         * @param  mixed $affiliatepress_affiliate_panel_dynamic_vue_method
         * @return void
        */
        function affiliatepress_affiliate_panel_dynamic_vue_methods_func($affiliatepress_affiliate_panel_dynamic_vue_method){

            global $affiliatepress_notification_duration;
            $affiliatepress_nonce = esc_html(wp_create_nonce('ap_wp_nonce'));

            $affiliatepress_edit_affiliate_profile_more_vue_data = '';
            $affiliatepress_edit_affiliate_profile_more_vue_data = apply_filters( 'affiliatepress_edit_affiliate_profile_more_vue_data', $affiliatepress_edit_affiliate_profile_more_vue_data);

            $affiliatepress_affiliate_panel_after_tab_change = '';
            $affiliatepress_affiliate_panel_after_tab_change = apply_filters( 'affiliatepress_affiliate_panel_after_tab_change', $affiliatepress_affiliate_panel_after_tab_change);

            $affiliatepress_after_affiliate_signup_more_vue_data = '';
            $affiliatepress_after_affiliate_signup_more_vue_data = apply_filters( 'affiliatepress_after_affiliate_signup_more_vue_data', $affiliatepress_after_affiliate_signup_more_vue_data);

            $affiliatepress_affiliate_panel_affiliate_links_modify_response = '';
            $affiliatepress_affiliate_panel_affiliate_links_modify_response = apply_filters( 'affiliatepress_affiliate_panel_affiliate_links_modify_response', $affiliatepress_affiliate_panel_affiliate_links_modify_response);

            $affiliatepress_get_link_data = '';
            $affiliatepress_get_link_data = apply_filters( 'affiliatepress_get_link_data', $affiliatepress_get_link_data);

            $affiliatepress_login_before_validation = '';
            $affiliatepress_login_before_validation = apply_filters( 'affiliatepress_login_before_validation', $affiliatepress_login_before_validation);

            $affiliatepress_add_login_posted_data = '';
            $affiliatepress_add_login_posted_data = apply_filters( 'affiliatepress_add_login_posted_data', $affiliatepress_add_login_posted_data);

            $affiliatepress_modify_panel_register_postdata = '';
            $affiliatepress_modify_panel_register_postdata = apply_filters( 'affiliatepress_modify_panel_register_postdata', $affiliatepress_modify_panel_register_postdata);

            $affiliatepress_affiliate_panel_dynamic_vue_method.='
                register_terms_and_condition(field_value){
                    var vm = this;
                    if(vm.affiliates[field_value] == false){
                        vm.affiliates[field_value] = "";
                    }
                },            
                affiliatepress_get_all_parent_node_with_overflow_hidden( elem ){
                    if (!Element.prototype.matches) {
                        Element.prototype.matches = Element.prototype.matchesSelector ||
                            Element.prototype.mozMatchesSelector ||
                            Element.prototype.msMatchesSelector ||
                            Element.prototype.oMatchesSelector ||
                            Element.prototype.webkitMatchesSelector ||
                            function(s) {
                                var matches = (this.document || this.ownerDocument).querySelectorAll(s),
                                    i = matches.length;
                                while (--i >= 0 && matches.item(i) !== this) {}
                                return i > -1;
                            };
                    }
                
                    var parents = [];
                
                    for (; elem && elem !== document; elem = elem.parentNode) {
                        let computed_style = getComputedStyle( elem );
                        
                        if( computed_style.overflow == "hidden" || computed_style.overflowX == "hidden" || computed_style.overflowY == "hidden" ){
                            parents.push(elem);
                        }
                    }
                    return parents;
                },            
                affiliatepress_onload_func(){
                    var vm = this;
                    if(window.innerWidth <= 576){
                        vm.affiliatepress_container_dynamic_class = "" ;
                        vm.affiliatepress_footer_dynamic_class = ""; 
                        let affiliatepress_container = vm.$refs["affiliatepresspanel"];                        
                        if(affiliatepress_container){

                            let parents_with_hidden_overflow = vm.affiliatepress_get_all_parent_node_with_overflow_hidden( affiliatepress_container );
                            let apply_overflow = ( parents_with_hidden_overflow.length > 0 ) ? true : false;

                            window.addEventListener("scroll", function(e){

                                let affiliatepress_scrollTop = affiliatepress_container.getBoundingClientRect().top;
                                let affiliatepress_scrollBottom = affiliatepress_container.getBoundingClientRect().bottom;
                                let ap_current_scroll = window.scrollY;                                
                                let targetBottom = affiliatepress_container.getBoundingClientRect().bottom;
                                let viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                                let ap_fixed_pagination_id = document.getElementById("ap_fixed_pagination_id");                                
                                if(ap_fixed_pagination_id){
                                    const height = ap_fixed_pagination_id.offsetHeight;
                                    viewportHeight = viewportHeight+height;
                                    if( affiliatepress_scrollTop < 50 && ap_current_scroll >= affiliatepress_scrollTop && targetBottom >= viewportHeight ){
                                        vm.affiliatepress_container_dynamic_class = "ap-front__mc--is-sticky" ;
                                        vm.affiliatepress_footer_dynamic_class = "__ap-is-sticky"; 
                                        if( apply_overflow ){
                                            for( let i = 0; i < parents_with_hidden_overflow.length; i++ ){
                                                let parent = parents_with_hidden_overflow[i];
                                                parent.classList.add("--ap-is-overflow-visible");
                                            }
                                        }
                                    } else {
                                        vm.affiliatepress_container_dynamic_class = ""; 
                                        vm.affiliatepress_footer_dynamic_class = ""; 
                                        if( apply_overflow ){
                                            for( let i = 0; i < parents_with_hidden_overflow.length; i++ ){
                                                let parent = parents_with_hidden_overflow[i];
                                                parent.classList.remove("--ap-is-overflow-visible");
                                            }
                                        }
                                    } 
                                }

                            });                        

                        }                        
                    }
                    window.addEventListener("resize", function(e){
                        var container = document.getElementById("ap-vue-cont-id");
                        var width = container.offsetWidth;
                        if(width >= 1200){
                            vm.current_screen_size = "desktop";
                        }else if(width < 1200 && width >= 768){
                            vm.current_screen_size = "tablet";
                        }else if(width < 768){
                            vm.current_screen_size = "mobile";
                        }
                        if(window.innerWidth <= 576){
                            vm.affiliatepress_container_dynamic_class = "" ;
                            vm.affiliatepress_footer_dynamic_class = ""; 
                            let affiliatepress_container = vm.$refs["affiliatepresspanel"];                        
                            if(affiliatepress_container){

                                let parents_with_hidden_overflow = vm.affiliatepress_get_all_parent_node_with_overflow_hidden( affiliatepress_container );
                                let apply_overflow = ( parents_with_hidden_overflow.length > 0 ) ? true : false;

                                window.addEventListener("scroll", function(e){
                                    
                                    let affiliatepress_scrollTop = affiliatepress_container.getBoundingClientRect().top;
                                    let affiliatepress_scrollBottom = affiliatepress_container.getBoundingClientRect().bottom;
                                    let ap_current_scroll = window.scrollY;                                
                                    let targetBottom = affiliatepress_container.getBoundingClientRect().bottom;
                                    let viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                                    let ap_fixed_pagination_id = document.getElementById("ap_fixed_pagination_id");                                
                                    if(ap_fixed_pagination_id){
                                        const height = ap_fixed_pagination_id.offsetHeight;
                                        viewportHeight = viewportHeight+height;
                                        if( affiliatepress_scrollTop < 50 && ap_current_scroll >= affiliatepress_scrollTop && targetBottom >= viewportHeight ){
                                           
                                            vm.affiliatepress_container_dynamic_class = "ap-front__mc--is-sticky" ;
                                            vm.affiliatepress_footer_dynamic_class = "__ap-is-sticky"; 
                                            if( apply_overflow ){
                                                for( let i = 0; i < parents_with_hidden_overflow.length; i++ ){
                                                    let parent = parents_with_hidden_overflow[i];
                                                    parent.classList.add("--ap-is-overflow-visible");
                                                }
                                            }
                                        } else {
                                            vm.affiliatepress_container_dynamic_class = ""; 
                                            vm.affiliatepress_footer_dynamic_class = ""; 
                                            if( apply_overflow ){
                                                for( let i = 0; i < parents_with_hidden_overflow.length; i++ ){
                                                    let parent = parents_with_hidden_overflow[i];
                                                    parent.classList.remove("--ap-is-overflow-visible");
                                                }
                                            }
                                        } 
                                    }
                                });                        

                            }                        
                        }else{
                            vm.affiliatepress_container_dynamic_class = "";
                            vm.affiliatepress_footer_dynamic_class = "";
                        }

                    });

                },
                closeDrawerModal(){
                    var vm = this;
                    vm.open_mobile_menu_modal = false;
                },
                affiliatepanel_logout(logout_url){
                    window.location.href = logout_url;
                },
                resetvisit(data = "",flag = false){
                    const vm = this;
                    vm.visits_perpage = 25;
                    vm.visits_order_by = "";
                    vm.visits_order = "";
                    vm.visits_currentPage = 1;
                    const formValues = Object.values(this.visits_search);
                    const hasValue = formValues.some(value => {
                        if (typeof value === "string") {
                            return value.trim() !== "";
                        }
                        if (Array.isArray(value)) {
                            return value.length > 0;
                        }
                        return false;
                    });                    
                    vm.visits_search.visit_type = "";
                    vm.visits_search.ap_visit_date = [];
                    if(hasValue || flag){
                        vm.loadvisits();
                    }                    
                },
                handleVisitPage(val) {
                    this.visits_currentPage = val;
                    this.loadvisits();
                },
                applyVisitFilter(){
                    const vm = this;
                    vm.visits_currentPage = 1;
                    const formValues = Object.values(this.visits_search);
                    const hasValue = formValues.some(value => {
                        if (typeof value === "string") {
                            return value.trim() !== "";
                        }
                        if (Array.isArray(value)) {
                            return value.length > 0;
                        }
                        return false;
                    });   
                    if(hasValue){
                        this.loadvisits();
                    }                                          
                },
                handleVisitSortChange({ column, prop, order }){
                    var vm = this;
                    if(prop == "sr_no"){
                        vm.visits_order_by = "ap_visit_id"; 
                    }else if(prop == "visit_created_date_formated"){
                        vm.visits_order_by = "ap_visit_created_date"; 
                    }
                    if(vm.visits_order_by){
                        if(order == "descending"){
                            vm.visits_order = "DESC";
                        }else if(order == "ascending"){
                            vm.visits_order = "ASC";
                        }else{
                            vm.commission_order    = "";
                            vm.visits_order_by = "";
                        }
                    }                
                    vm.loadvisits();
                    return false;   
                },
                sortIntegers(a, b) {
                    console.log(a);
                    console.log(b);
                    return a - b;
                },              
                loadvisits(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                         var wpdata = parentDiv.querySelector("#_wpnonce");
                         if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                         }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }                    
                    vm.affiliate_visit_loader = "1";
                    vm.is_apply_disabled = true;
                    vm.enabled = true;
                    affiliatespress_search_data = vm.visits_search;
                    var postData = { action:"affiliatepress_get_affiliate_visits",currentpage:vm.visits_currentPage,  perpage:vm.visits_perpage, order_by:vm.visits_order_by, order:vm.visits_order, search_data: affiliatespress_search_data, _wpnonce:ap_wpnonce_pre_fetch };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function(response){                    
                        vm.affiliate_visit_loader   = "0";
                        vm.is_apply_disabled = false;
                        vm.is_display_tab_content_loader = "0";                                     
                        if(response.data.variant == "success"){                            
                            vm.visits_items      = response.data.items;                      
                            vm.visits_totalItems = response.data.total;
                            var defaultPerPage = vm.visits_defaultPerPage;
                            if(vm.visits_perpage > defaultPerPage && response.data.visits_pagination_count == 1){
                                response.data.visits_pagination_count = 2;
                            }
                            vm.visits_pagination_count = response.data.visits_pagination_count;
                            vm.affiliate_panel_labels       = response.data.affiliate_panel_labels;    
                            vm.visits_pagination_label       = response.data.visits_pagination_label;    
                            vm.visits_height = true; 
                            vm.visits_search.visit_type = response.data.affiliate_visit_type;                            
                            setTimeout(function(){
                                vm.visits_height = true;
                                var visits_tbl_div = document.querySelector(".ap-visits-table-data");
                                if(visits_tbl_div){
                                    var visits_tbl_height = visits_tbl_div.offsetHeight;
                                    if(visits_tbl_height > 700){
                                        vm.visits_height = true;
                                    }else{
                                        vm.visits_height = true;
                                    }
                                }
                            },400);

                        }else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                                                       
                        }
                    }.bind(this) )
                    .catch( function (error) {
                        vm.affiliate_commission_loader   = "0";
                        vm.is_display_tab_content_loader = "0";
                        console.log(error);                        
                    }); 
                },
                affiliatepress_copy_data(copy_data){
                    const vm = this;	
                    var affiliatepress_dummy_elem = document.createElement("textarea");
                    document.body.appendChild(affiliatepress_dummy_elem);
                    affiliatepress_dummy_elem.value = copy_data;
                    affiliatepress_dummy_elem.select();
                    document.execCommand("copy");
                    document.body.removeChild(affiliatepress_dummy_elem);
                    vm.$notify({ 
                        title: "'.esc_html__('Success', 'affiliatepress-affiliate-marketing').'",
                        message: vm.affiliate_link_copy_message,
                        type: "success",
                        customClass: "success_notification",
                        duration:'.intval($affiliatepress_notification_duration).',
                    });
                },                
                applyCreativeFilter(){
                    const vm = this;
                    vm.creative_currentPage = 1;
                    const formValues = Object.values(vm.creative_search);
                    const hasValue = formValues.some(value => {
                        if (typeof value === "string") {
                            return value.trim() !== "";
                        }
                        if (Array.isArray(value)) {
                            return value.length > 0;
                        }
                        return false;
                    }); 
                    if(hasValue){
                        vm.loadcreative();
                    }                                          
                },                
                loadcreative(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                         var wpdata = parentDiv.querySelector("#_wpnonce");
                         if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                         }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }                    
                    vm.affiliate_creative_loader = "1";
                    vm.is_apply_disabled = true;
                    vm.enabled = true;
                    affiliatespress_search_data = vm.creative_search;
                    var postData = { action:"affiliatepress_get_affiliate_creatives",currentpage:vm.creative_currentPage,  perpage:vm.creative_perpage, order_by:vm.creative_order_by, order:vm.creative_order, search_data: affiliatespress_search_data, _wpnonce:ap_wpnonce_pre_fetch };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function(response){                    
                        vm.affiliate_creative_loader     = "0";
                        vm.is_apply_disabled = false;
                        vm.is_display_tab_content_loader = "0";                                     
                        if(response.data.variant == "success"){                            
                            vm.creative_items      = response.data.items;                      
                            vm.creative_totalItems = parseInt(response.data.total);
                            vm.creative_pagination_count = parseInt(response.data.creative_pagination_count);
                            vm.creative_pagination_label = response.data.creative_pagination_label;
                            vm.affiliate_panel_labels       = response.data.affiliate_panel_labels;    
                        }else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                                                       
                        }
                    }.bind(this) )
                    .catch( function (error) {
                        vm.affiliate_commission_loader   = "0";
                        vm.is_display_tab_content_loader = "0";
                        console.log(error);                        
                    });
                },
                handlecreativePage(val) {
                    this.creative_currentPage = val;
                    this.loadcreative();
                },                 
                resetcreative(data = "",flag = false){
                    const vm = this;
                    vm.creative_totalItems = 0;
                    vm.creative_perpage = 6;
                    vm.creative_order_by = "";
                    vm.creative_order = "";
                    vm.creative_currentPage = 1;   
                    const formValues = Object.values(this.creative_search);
                    const hasValue = formValues.some(value => {
                        if (typeof value === "string") {
                            return value.trim() !== "";
                        }
                        if (Array.isArray(value)) {
                            return value.length > 0;
                        }
                        return false;
                    }); 
                    vm.creative_search.ap_creative_name = "";
                    vm.creative_search.creative_type = "";  
                    if(hasValue || flag){
                        vm.loadcreative();
                    }                    
                },                
                resetcommissions(data = "",flag = false){
                    const vm = this;
                    vm.commission_perpage = 10;
                    vm.commission_order_by = "";
                    vm.commission_order = "";
                    vm.commission_currentPage = 1;
                    const formValues = Object.values(vm.commissions_search);
                    const hasValue = formValues.some(value => {
                        if (typeof value === "string") {
                            return value.trim() !== "";
                        }
                        if (Array.isArray(value)) {
                            return value.length > 0;
                        }
                        return false;
                    });
                    vm.commissions_search.commission_status = "";
                    vm.commissions_search.ap_commission_search_date = [];
                    if(hasValue || flag){
                        vm.loadcommissions();
                    }                    
                },
                applyCommissionsFilter(){
                    const vm = this;
                    vm.commission_currentPage = 1;
                    const formValues = Object.values(vm.commissions_search);
                    const hasValue = formValues.some(value => {
                        if (typeof value === "string") {
                            return value.trim() !== "";
                        }
                        if (Array.isArray(value)) {
                            return value.length > 0;
                        }
                        return false;
                    });  
                    if(hasValue){                  
                        this.loadcommissions();
                    }
                },                
                handleCommissionPage(val) {
                    this.commission_currentPage = val;
                    this.loadcommissions();
                }, 
                handleCommissionSortChange({ column, prop, order }){
                    var vm = this;
                    if(prop == "ap_commission_id"){
                        vm.commission_order_by = "ap_commission_id"; 
                    }else if(prop == "commission_created_date_formated"){
                        vm.commission_order_by = "ap_commission_created_date"; 
                    }else if(prop == "ap_commission_amount"){
                        vm.commission_order_by = "ap_commission_amount";                    
                    }
                    if(vm.commission_order_by){
                        if(order == "descending"){
                            vm.commission_order = "DESC";
                        }else if(order == "ascending"){
                            vm.commission_order = "ASC";
                        }else{
                            vm.commission_order    = "";
                            vm.commission_order_by = "";
                        }
                    }                
                    vm.loadcommissions();
                    return false;                        
                },
                get_edit_profile_data(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                         var wpdata = parentDiv.querySelector("#_wpnonce");
                         if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                         }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }
                    var postData = { action:"affiliatepress_get_affiliate_edit_profile_data", _wpnonce:ap_wpnonce_pre_fetch };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function(response){                         
                        vm.is_display_tab_content_loader = "0";                                     
                        if(response.data.variant == "success"){                                                   
                            vm.affiliates_profile_fields  = response.data.affiliates_profile_fields;                             
                            if(typeof vm.affiliates_profile_fields != "undefined" && typeof vm.affiliates_profile_fields.firstname != "" && typeof vm.affiliates_profile_fields.lastname != ""){
                                vm.userName = vm.affiliates_profile_fields.firstname+" "+vm.affiliates_profile_fields.lastname;
                            }
                            '.$affiliatepress_edit_affiliate_profile_more_vue_data.'
                        }else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                                                       
                        }
                    }.bind(this) )
                    .catch( function (error) {                        
                        vm.is_display_tab_content_loader = "0";
                        console.log(error);                        
                    });                    

                },
                change_password_request(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                         var wpdata = parentDiv.querySelector("#_wpnonce");
                         if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                         }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }  
                    vm.$refs["affiliates_change_pass_form_data"].validate((valid) => {     
                        if(valid){
                            var postdata    = vm.affiliate_change_password;
                            postdata.action = "affiliatepress_affiliate_panel_change_password";
                            vm.affiliate_change_password_loader = "1";
                            postdata._wpnonce = ap_wpnonce_pre_fetch;                            
                            axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                            .then(function(response){                                 
                                vm.affiliate_change_password_loader = "0";                                                                                         
                                vm.$notify({
                                    title: response.data.title,
                                    message: response.data.msg,
                                    type: response.data.variant,
                                    customClass: response.data.variant+"_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });
                                if(response.data.variant == "success"){
                                    window.location.href = window.location.href;
                                }
                            }).catch(function(error){
                                console.log(error);
                                vm.$notify({
                                    title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                                    message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                                    type: "error",
                                    customClass: "error_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });
                            });                            
                        }else{
                            return false;
                        }
                    });                    
                },
                save_edit_profile_data(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                         var wpdata = parentDiv.querySelector("#_wpnonce");
                         if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                         }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }                    
                    vm.$refs["affiliates_profile_form_data"].validate((valid) => {     
                        if(valid){
                            var postdata = vm.affiliates_profile_fields;
                            postdata.action = "affiliatepress_save_edit_profile";
                            vm.affiliate_edit_profile_loader = "1";
                            postdata._wpnonce = ap_wpnonce_pre_fetch;                            
                            axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                            .then(function(response){                                 
                                vm.affiliate_edit_profile_loader = "0";
                                vm.get_edit_profile_data();                                                           
                                vm.$notify({
                                    title: response.data.title,
                                    message: response.data.msg,
                                    type: response.data.variant,
                                    customClass: response.data.variant+"_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });
                            }).catch(function(error){
                                console.log(error);
                                vm.$notify({
                                    title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                                    message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                                    type: "error",
                                    customClass: "error_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });
                            });                            
                        }else{
                            return false;
                        }
                    });
                },
                loadcommissions(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                         var wpdata = parentDiv.querySelector("#_wpnonce");
                         if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                         }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }                    
                    vm.is_apply_disabled = true;
                    vm.affiliate_commission_loader = "1";
                    vm.enabled = true;
                    affiliatespress_search_data = vm.commissions_search;
                    var postData = { action:"affiliatepress_get_affiliate_commissions",currentpage:vm.commission_currentPage,  perpage:vm.commission_perpage, order_by:vm.commission_order_by, order:vm.commission_order, search_data: affiliatespress_search_data, _wpnonce:ap_wpnonce_pre_fetch };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function(response){                    
                        vm.affiliate_commission_loader = "0"; 
                        vm.is_display_tab_content_loader = "0";    
                        vm.is_apply_disabled = false;                                 
                        if(response.data.variant == "success"){                                                   
                            vm.commission_items      = response.data.items;                                                  
                            vm.commission_totalItems = response.data.total; 
                            vm.commission_pagination_counts = response.data.commission_pagination_counts; 
                            vm.commission_pagination_labels = response.data.commission_pagination_labels; 
                            vm.affiliate_panel_labels       = response.data.affiliate_panel_labels;    
                        }else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                                                       
                        }
                    }.bind(this) )
                    .catch( function (error) {
                        vm.affiliate_commission_loader   = "0";
                        vm.is_display_tab_content_loader = "0";
                        console.log(error);                        
                    });                    
                },
                affiliatepress_change_tab(new_tab,first_load=false){
                    const vm = this;
                    if(vm.affiliate_current_tab != new_tab || first_load){
                        vm.is_display_tab_content_loader = "1";
                        if(new_tab == "commission"){
                            vm.resetcommissions("",true);                         
                        }else if(new_tab == "visit"){
                            vm.resetvisit("",true);
                        }else if(new_tab == "creative"){
                            vm.resetcreative("",true);
                        }else if(new_tab == "edit_profile"){
                            vm.get_edit_profile_data();
                        }else if(new_tab == "affiliates_links"){
                            vm.get_affiliates_links_data();
                        }else if(new_tab == "dashboard"){                            
                            vm.get_dashboard_data();
                        }else if(new_tab == "payments"){  
                            vm.resetpayments("",true);
                        }
                        '.$affiliatepress_affiliate_panel_after_tab_change.'    
                        vm.affiliate_current_tab = new_tab;                        
                        vm.open_mobile_menu_modal = false;
                    }
                },
                resetpayments(data = "",flag = false){
                    const vm = this;
                    vm.payments_perpage = 10;
                    vm.payments_order_by = "";
                    vm.payments_order = "";
                    vm.payments_currentPage = 1;
                    const formValues = Object.values(this.payments_search);
                    const hasValue = formValues.some(value => {
                        if (typeof value === "string") {
                            return value.trim() !== "";
                        }
                        if (Array.isArray(value)) {
                            return value.length > 0;
                        }
                        return false;
                    });                    
                    vm.payments_search.payment_status = "";
                    vm.payments_search.ap_payment_created_date = [];
                    if(hasValue || flag){
                        vm.loadpayments();
                    }                    
                },
                handlePaymentSortChange({ column, prop, order }){
                    var vm = this;
                    if(prop == "ap_payment_id"){
                        vm.commission_order_by = "ap_payment_id"; 
                    }else if(prop == "ap_payment_created_date_formated"){
                        vm.commission_order_by = "ap_payment_created_date"; 
                    }
                    if(vm.commission_order_by){
                        if(order == "descending"){
                            vm.commission_order = "DESC";
                        }else if(order == "ascending"){
                            vm.commission_order = "ASC";
                        }else{
                            vm.commission_order    = "";
                            vm.commission_order_by = "";
                        }
                    }                
                    vm.loadpayments();
                    return false;                        
                },                
                handlePaymentPage(val) {
                    this.payments_currentPage = val;
                    this.loadpayments();
                },    
                resetpopuppayment(){
                    var vm = this;
                    vm.resetpayments("",true);
                },                
                applypopupPaymentFilter(){
                    var vm = this;
                    vm.open_payment_filter_modal = false;
                    vm.applyPaymentFilter();
                },                                
                applyPaymentFilter(){
                    const vm = this;
                    vm.payments_currentPage = 1;
                    const formValues = Object.values(this.payments_search);
                    const hasValue = formValues.some(value => {
                        if (typeof value === "string") {
                            return value.trim() !== "";
                        }
                        if (Array.isArray(value)) {
                            return value.length > 0;
                        }
                        return false;
                    });
                    if(hasValue){                   
                        vm.loadpayments();
                    }
                },                 
                loadpayments(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                         var wpdata = parentDiv.querySelector("#_wpnonce");
                         if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                         }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }                    
                    vm.affiliate_payments_loader = "1";
                    vm.is_apply_disabled = true;
                    vm.enabled = true;
                    affiliatespress_search_data = vm.payments_search;
                    var postData = { action:"affiliatepress_get_affiliate_payments",currentpage:vm.payments_currentPage,  perpage:vm.payments_perpage, order_by:vm.payments_order_by, order:vm.payments_order, search_data: affiliatespress_search_data, _wpnonce:ap_wpnonce_pre_fetch };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function(response){                    
                        vm.affiliate_payments_loader = "0"; 
                        vm.is_display_tab_content_loader = "0"; 
                        vm.is_apply_disabled = false;                                    
                        if(response.data.variant == "success"){                                                   
                            vm.payments_items      = response.data.items;                                                  
                            vm.payments_totalItems = response.data.total; 
                            vm.payments_pagination_count = response.data.payments_pagination_count; 
                            vm.affiliate_panel_labels       = response.data.affiliate_panel_labels;   
                             vm.payments_pagination_label = response.data.payments_pagination_label; 
                        }else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                                                       
                        }
                    }.bind(this) )
                    .catch( function (error) {
                        vm.affiliate_commission_loader   = "0";
                        vm.is_display_tab_content_loader = "0";
                        console.log(error);                        
                    });                    
                },                
                change_dashboard_date(){
                    const vm = this;
                    vm.get_dashboard_data();
                },
                close_acount_action(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                        var wpdata = parentDiv.querySelector("#_wpnonce");
                        if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                        }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }
                    vm.affiliate_close_account_loader = "1";    
                    var postData = { action:"affiliatepress_close_account_request", _wpnonce:ap_wpnonce_pre_fetch };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function(response){                                            
                        vm.affiliate_close_account_loader = "0";                                     
                        if(response.data.variant == "success"){                            
                            vm.open_close_account_modal = false;
                            window.location.href = response.data.delete_account_url;
                        }else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                                                       
                        }
                    }.bind(this) )
                    .catch( function (error) {
                        vm.affiliate_commission_loader   = "0";
                        vm.is_display_tab_content_loader = "0";
                        console.log(error);                        
                    });                        
                    
                },
                get_dashboard_data(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                        var wpdata = parentDiv.querySelector("#_wpnonce");
                        if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                        }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }
                    vm.affiliate_dashboard_loader   = "1";
                    vm.is_apply_disabled = true;
                    var postData = { action:"affiliatepress_get_dashboard_data", _wpnonce:ap_wpnonce_pre_fetch,"dashboard_date_range":vm.dashboard_date_range };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function(response){                                            
                        vm.is_display_tab_content_loader = "0";                                     
                        if(response.data.variant == "success"){      
                            vm.affiliate_dashboard_loader   = "0"; 
                            vm.is_apply_disabled = false;                                            
                            vm.dashboard_total_earning      = response.data.dashboard_total_earning;
                            vm.dashboard_paid_earning       = response.data.dashboard_paid_earning;
                            vm.dashboard_unpaid_earning     = response.data.dashboard_unpaid_earning;
                            vm.dashboard_total_commission   = response.data.dashboard_total_commission;
                            vm.dashboard_total_visits       = response.data.dashboard_total_visits;    
                            vm.affiliate_panel_labels       = response.data.affiliate_panel_labels;    
                            vm.revenue_chart_data           = response.data.revenue_chart_data;
                            vm.revenue_chart_other_data     = response.data.revenue_chart_other_data;

                                setTimeout(function(){

                                const revenue_chart_var = document.getElementById("revenue_chart");                            
                                if(typeof revenue_chart_var != "undefined" && revenue_chart_var != null){                                
                                    const revenue_chart = document.getElementById("revenue_chart").getContext("2d");
                                    if (typeof window.revenue_chart_data != "undefined" && window.revenue_chart_data) {
                                        window.revenue_chart_data.destroy();
                                    }                             
                                    window.revenue_chart_data = new Chart(revenue_chart, {
                                        type: "line",
                                        data: {
                                            labels: vm.revenue_chart_data.labels,
                                            datasets: [
                                                {
                                                    label: response.data.revenue_chart_other_data.earnings_graph_label,
                                                    data: vm.revenue_chart_data.earning_values,
                                                    borderColor: vm.revenue_chart_other_data.earnings_graph_color,
                                                    backgroundColor: vm.revenue_chart_other_data.earnings_graph_bgcolor,
                                                    fill: true,
                                                    pointRadius: function(context) {
                                                        return context.raw === 0 ? 0 : 3;
                                                    },
                                                    pointHoverRadius: function(context) {
                                                        return context.raw === 0 ? 0 : 3;
                                                    },
                                                    pointBackgroundColor: vm.revenue_chart_other_data.earnings_graph_color,
                                                },
                                                {
                                                    label: response.data.revenue_chart_other_data.commissions_graph_label,
                                                    data: vm.revenue_chart_data.commission_count,
                                                    borderColor: "rgba(255, 99, 132, 1)",
                                                    backgroundColor: "rgba(255, 99, 132, 0.2)",
                                                    fill: true,
                                                    pointRadius: 3,
                                                    pointRadius: function(context) {
                                                        return context.raw === 0 ? 0 : 3;
                                                    },
                                                    pointHoverRadius: function(context) {
                                                        return context.raw === 0 ? 0 : 3;
                                                    },
                                                }
                                            ]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false, 
                                            interaction: {
                                                mode: "nearest",
                                                intersect: false
                                            },
                                            plugins: {
                                                legend: {
                                                    display: true,
                                                    position: "top"
                                                },
                                                tooltip: {
                                                    enabled: true,
                                                    mode: "nearest",
                                                    intersect: false,
                                                    callbacks: {
                                                        label: function(context) {
                                                            let label = context.dataset.label || "";
                                                            if (label) {
                                                                label += ": ";
                                                            }
                                                            if (vm.currency_symbol && context.dataset.label === "Earnings") {
                                                                label += vm.currency_symbol;
                                                            }
                                                            if (context.parsed.y !== null) {
                                                                label += context.parsed.y;
                                                            }
                                                            return label;
                                                        }
                                                    }
                                                }
                                            },
                                            scales: {
                                                x: {
                                                    beginAtZero: true
                                                },
                                                y: {
                                                    beginAtZero: true
                                                }
                                            }
                                        }
                                    });                            
                                }
                                },1200);
                            
                        }else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                                                       
                        }
                    }.bind(this) )
                    .catch( function (error) {
                        vm.affiliate_commission_loader   = "0";
                        vm.is_display_tab_content_loader = "0";
                        console.log(error);                        
                    });

                },
                open_affiliate_link_model(){
                    const vm = this;                    
                    vm.open_modal = true;
                    var affiliate_links_frm_data = vm.$refs["affiliate_links_frm"];
                    if(affiliate_links_frm_data){
                        vm.$refs["affiliate_links_frm"].resetFields();
                    }

                    setTimeout(() => {
                        vm.$nextTick(() => {
                            const inputRef = vm.$refs["generate_link_pageUrlInput"];
                            if (inputRef && inputRef.$el) {
                                const nativeInput = inputRef.$el.querySelector("input");
                                if (nativeInput) {
                                    nativeInput.focus();
                                }
                            }
                        });
                    }, 100); // Delay helps when the dialog has transition effects
                },
                add_affliate_custom_link(){
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                         var wpdata = parentDiv.querySelector("#_wpnonce");
                         if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                         }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }                    
                    vm.$refs["affiliate_links_frm"].validate((valid) => {     
                        if(valid){
                            vm.affiliate_custom_link_loader = "1";
                            var postdata = vm.affiliate_links_data;
                            postdata.action = "affiliatepress_add_affiliate_custom_link";
                            vm.affiliate_custom_link_loader = "1";
                            postdata._wpnonce = ap_wpnonce_pre_fetch;                            
                            axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                            .then(function(response){   

                                vm.affiliate_links_data.ap_page_link = "";
                                vm.affiliate_links_data.ap_affiliates_campaign_name = "";
                                vm.affiliate_links_data.ap_affiliates_sub_id = "";
                                vm.affiliate_custom_link_loader = "0";
                                vm.get_affiliates_links_data();                                                           
                                vm.$notify({
                                    title: response.data.title,
                                    message: response.data.msg,
                                    type: response.data.variant,
                                    customClass: response.data.variant+"_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });
                                vm.open_modal = false;
                            }).catch(function(error){
                                console.log(error);
                                vm.$notify({
                                    title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                                    message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                                    type: "error",
                                    customClass: "error_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });
                            });                            
                        }else{
                            return false;
                        }
                    });
                },                
                get_affiliates_links_data(){
                    const vm = this;                                       
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                        var wpdata = parentDiv.querySelector("#_wpnonce");
                        if(wpdata){
                            ap_wpnonce_pre_fetch = wpdata;                            
                        }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }                                 
                    var postData = { action:"affiliatepress_get_affiliates_links", _wpnonce:ap_wpnonce_pre_fetch };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function(response){                                            
                        vm.is_display_tab_content_loader = "0";                                     
                        if(response.data.variant == "success"){                                                   
                            vm.affiliate_custom_links      = response.data.affiliate_custom_links;
                            vm.affiliate_panel_labels       = response.data.affiliate_panel_labels;  
                            if (typeof response.data.is_add_affiliate_link !== "undefined") {
                                vm.is_add_affiliate_link = response.data.is_add_affiliate_link;
                            }
                            '.$affiliatepress_affiliate_panel_affiliate_links_modify_response.'                            
                        }else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                                                       
                        }
                        '.$affiliatepress_get_link_data.'
                    }.bind(this) )
                    .catch( function (error) {
                        vm.affiliate_commission_loader   = "0";
                        vm.is_display_tab_content_loader = "0";
                        console.log(error);                        
                    });

                },
                affiliatepress_set_error_msg(error_msg,allow_final_scroll = true){
                    const vm = this;
                    let pos = 0;
                    const element = document.querySelector(".ap-front-reg-container");
                    if( null != element ){
                        const rect = element.getBoundingClientRect();
                        pos = rect.top + window.scrollY;
                    }
                    vm.is_display_error = "1";
                    vm.is_error_msg = error_msg;
                    const myVar = Error().stack;                    
                    let allow_scroll = true;
                    if( /mounted/.test( myVar ) ){
                        allow_scroll = false;
                    }
                    if( allow_scroll && allow_final_scroll ){
                        window.scrollTo({
                            top: pos,
                            behavior: "smooth",
                        });
                    }                    
                    setTimeout(function(){
                        vm.affiliatepress_remove_success_error_msg();
                    },6000);
                }, 
                affiliatepress_set_success_msg(success_msg,allow_final_scroll = true){
                    const vm = this;
                    let pos = 0;
                    const element = document.querySelector(".ap-front-reg-container");
                    if( null != element ){
                        const rect = element.getBoundingClientRect();
                        pos = rect.top + window.scrollY;
                    }



                    //vm.affiliatepress_remove_success_error_msg();
                    vm.is_display_success = "1";
                    vm.is_success_msg = success_msg;
                    const myVar = Error().stack;  
                    let allow_scroll = true;
                    if( /mounted/.test( myVar ) ){
                        allow_scroll = false;
                    }                 

                    
                    if( allow_scroll && allow_final_scroll ){
                        window.scrollTo({
                            top: pos,
                            behavior: "smooth",
                        });
                    }
                    setTimeout(function(){
                        vm.affiliatepress_remove_success_error_msg();
                    },3000);
                },
                affiliatepress_remove_success_error_msg(){
                    const vm = this;
                    vm.is_display_success = "0";
                    vm.is_success_msg = "";
                    vm.is_display_error = "0";
                    vm.is_error_msg = "";                    
                },    
                showForgotpassword(){
                    var vm = this;
                    vm.show_forgot_password_form = "1";

                    vm.$nextTick(() => {
                        if (vm.$refs.forgetInput) {
                            vm.$refs.forgetInput.focus();
                        }
                    });
                },
                showLoginForm(){
                    var vm = this;
                    vm.show_forgot_password_form = "0";
                    if(typeof vm.$refs["affiliates_login_form_data"] != "undefined"){
                        vm.$refs["affiliates_login_form_data"].resetFields();
                        vm.affiliatepress_login_form.affiliatepress_username = "";
                        vm.affiliatepress_login_form.affiliatepress_password = "";
                        vm.affiliatepress_login_form.affiliatepress_is_remember = false;
                    }       
                        
                    vm.$nextTick(() => {
                        if (vm.$refs.loginInput) {
                            vm.$refs.loginInput.focus();
                        }
                    });
                },
                open_creative_popup(creative_item){
                    var vm = this;
                    vm.creative_popup_data = creative_item;
                    vm.open_creative_modal = true;
                },
                download_preview_image(image_url){                    
                    window.open(image_url, "_blank");
                },
                async affiliatepress_affiliate_login(){
                    const vm = this;
                    '.$affiliatepress_login_before_validation.'                    
					vm.$refs["affiliates_login_form_data"].validate((valid) => {
						if(valid){
							vm.login_form_loader = "1";
                            var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                            var parentDiv = document.getElementById("ap-none-field");
                            var ap_wpnonce_pre_fetch = "";
                            if(parentDiv){
                                var wpdata = parentDiv.querySelector("#_wpnonce");
                                if(wpdata){
                                    ap_wpnonce_pre_fetch = wpdata;                            
                                }                         
                            }                
                            if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                                ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                            }else{
                                ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                            }
							var loginFormData = { action:"affiliatepress_affiliate_login_account", login_email_address: vm.affiliatepress_login_form.affiliatepress_username, login_password: vm.affiliatepress_login_form.affiliatepress_password, is_remember: vm.affiliatepress_login_form.affiliatepress_is_remember, _wpnonce:ap_wpnonce_pre_fetch };
                            '.$affiliatepress_add_login_posted_data.'
							axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( loginFormData ) )
							.then( function (response) {								
								vm.login_form_loader = "0";
								if(response.data.variant == "error"){
									vm.affiliatepress_set_error_msg(response.data.msg);
								}else{
                                    if(typeof response.data.after_register_redirect != "undefined" && response.data.after_register_redirect != ""){
                                        window.location.href = response.data.after_register_redirect;
                                    }else{
                                        window.location.href = window.location.href; 
                                    }
								}
							}.bind(this) )
							.catch( function (error) {                    
								console.log(error);
							});			
						}else{
                            vm.$nextTick(() => {
                                const inputRef = vm.$refs["loginInput"];
                                if (inputRef && inputRef.$el) {
                                    const nativeInput = inputRef.$el.querySelector("input");
                                    if (nativeInput) {
                                        nativeInput.focus();
                                    }
                                }
                            });
                        }
					});
					return false;                    
                },
                affiliatepress_forgot_password(){
					const vm = this;
					vm.$refs["affiliatepress_forgot_password_form"].validate((valid) => {
						if(valid){
							vm.forgot_form_loader = "1";
                            var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                            var parentDiv = document.getElementById("ap-none-field");
                            var ap_wpnonce_pre_fetch = "";
                            if(parentDiv){
                                var wpdata = parentDiv.querySelector("#_wpnonce");
                                if(wpdata){
                                    ap_wpnonce_pre_fetch = wpdata;                            
                                }                         
                            }                
                            if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                                ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                            }else{
                                ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                            }
							var forgotPassFormData = { action:"affiliatepress_forgot_password_account", forgot_pass_email_address: vm.affiliatepress_forgot_password_form.affiliatepress_email, _wpnonce:ap_wpnonce_pre_fetch };
							axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( forgotPassFormData ) )
							.then( function (response) {
								vm.forgot_form_loader = "0";
								if(response.data.variant == "error"){
									vm.affiliatepress_set_error_msg(response.data.msg);
								}else{
									vm.affiliatepress_set_success_msg(response.data.msg);
								}
							}.bind(this) )
							.catch( function (error) {  
                                vm.forgot_form_loader = "0";                  
								console.log(error);
							});
						}else{
                            vm.$nextTick(() => {
                                const inputRef = vm.$refs["forgetInput"];
                                if (inputRef && inputRef.$el) {
                                    const nativeInput = inputRef.$el.querySelector("input");
                                    if (nativeInput) {
                                        nativeInput.focus();
                                    }
                                }
                            });
                        }
					});
				}, 
                close_drawer_menu(){
                    vm = this;
                    vm.open_mobile_menu_modal = false;  
                },
                resetpopupcreative(){
                    var vm = this;
                    vm.resetcreative();
                },
                applypopupCreativeFilter(){
                    var vm = this;
                    vm.open_creative_filter_modal = false;                    
                    vm.applyCreativeFilter();                    
                },
                resetpopupcommission(){
                    var vm = this;
                    vm.resetcommissions("",true);
                },                
                applypopupCommissionFilter(){
                    var vm = this;
                    vm.open_commission_filter_modal = false;
                    vm.applyCommissionsFilter();
                },
                resetpopupvisit(){
                    var vm = this;
                    vm.resetvisit();
                },
                applypopupVisitFilter(){
                    var vm = this;
                    vm.open_visit_filter_modal = false;
                    vm.applyVisitFilter();
                }, 
                affiliatepress_go_to_register(){
                    var vm = this; 
                    vm.show_register_form = "1";
                    setTimeout(function(){    
                        const element = document.querySelector(".ap-front-reg-container");                
                        if( null != element ){                            
                            const rect = element.getBoundingClientRect();
                            pos = rect.top + window.scrollY;
                            window.scrollTo({
                                top: pos,
                                behavior: "smooth",
                            });  
                            
                            if(typeof vm.$refs["affiliates_reg_form_data"] != "undefined"){
                                vm.$refs["affiliates_reg_form_data"].resetFields();
                                vm.affiliates.firstname = "";
                                vm.affiliates.lastname = "";
                                vm.affiliates.username = "";
                                vm.affiliates.email = "";
                                vm.affiliates.password = "";                             
                            }

                            const formFields = vm.$refs["affiliates_reg_form_data"].fields;
                            for (let key in formFields) {
                                if (formFields[key].$el) {
                                    const input = formFields[key].$el.querySelector("input, textarea");
                                    if (input && !input.disabled) {
                                        input.focus();
                                        break;
                                    }
                                }
                            }
                        }                    
                    },200);         
                },
                go_to_login_page(){
                    var vm = this;
                    vm.show_register_form = "0";  
                    if(typeof vm.$refs["affiliates_login_form_data"] != "undefined"){
                        vm.$refs["affiliates_login_form_data"].resetFields();
                        vm.affiliatepress_login_form.affiliatepress_username = "";
                        vm.affiliatepress_login_form.affiliatepress_password = "";
                        vm.affiliatepress_login_form.affiliatepress_is_remember = false;
                    }                                     
                    setTimeout(function(){    
                        const element = document.querySelector(".ap-front-reg-container");                
                        if( null != element ){                            
                            const rect = element.getBoundingClientRect();
                            pos = rect.top + window.scrollY;
                            window.scrollTo({
                                top: pos,
                                behavior: "smooth",
                            });    
                        }                    
                    },200);  
                    
                    vm.$nextTick(() => {
                        if (vm.$refs.loginInput) {
                            vm.$refs.loginInput.focus();
                        }
                    });
                     
                },
                async registerAffiliate(){  
                    const vm = this;
                    var ap_wpnonce_pre = "' . $affiliatepress_nonce . '";
                    var parentDiv = document.getElementById("ap-none-field");
                    var ap_wpnonce_pre_fetch = "";
                    if(parentDiv){
                        var wpdata = parentDiv.querySelector("#_wpnonce");
                        if(wpdata){
                           ap_wpnonce_pre_fetch = wpdata;                            
                        }                         
                    }                
                    if(typeof ap_wpnonce_pre_fetch=="undefined" || ap_wpnonce_pre_fetch==null){
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre;
                    }else{
                        ap_wpnonce_pre_fetch = ap_wpnonce_pre_fetch.value;
                    }
                    var postdata = vm.affiliates;
                    postdata.action = "affiliatepress_register_affiliate";                    
                    postdata._wpnonce = ap_wpnonce_pre_fetch;
                    postdata.affiliatepanelrequest = "yes";
                    '.$affiliatepress_modify_panel_register_postdata.'
                    this.$refs["affiliates_reg_form_data"].validate((valid) => {   
                        if(valid){
                            vm.reg_is_disabled = true;
                            vm.is_display_reg_save_loader = "1";                       
                            axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                            .then(function(response){                                
                                vm.reg_is_disabled = false;                           
                                vm.is_display_reg_save_loader = "0";                                
                                if (response.data.variant == "success") {   

                                    if(typeof response.data.after_register_redirect != "undefined" && response.data.after_register_redirect != "" && typeof response.data.affiliatepress_affiliates_status != "undefined" && response.data.affiliatepress_affiliates_status == 1){
                                        window.location.href = response.data.after_register_redirect;
                                    }
                                    if(vm.is_login == "true"){
                                        if(typeof response.data.affiliatepress_affiliates_status == "undefined" || response.data.affiliatepress_affiliates_status != 1){
                                            vm.is_show_register_form = "false";
                                            vm.register_form_msg = response.data.msg;
                                        }
                                    }else{
                                        vm.$refs["affiliates_reg_form_data"].resetFields();
                                        vm.affiliates.firstname = "";
                                        vm.affiliates.lastname = "";
                                        vm.affiliates.username = "";
                                        vm.affiliates.email = "";
                                        vm.affiliates.password = "";
                                        vm.reg_is_disabled = true;
                                        vm.affiliatepress_set_success_msg(response.data.msg);                                       
                                    }
                                    
                                    '.$affiliatepress_after_affiliate_signup_more_vue_data.'
                                }else{                                    
                                    vm.affiliatepress_set_error_msg(response.data.msg);
                                }
                            }).catch(function(error){
                                vm.reg_is_disabled = false;                           
                                vm.is_display_reg_save_loader = "0";                                  
                            });
                        }else{
                            const formFields = this.$refs.affiliates_reg_form_data.fields;
                            if(typeof formFields != "undefined"){
                                for (let field in formFields) {
                                    if (formFields[field].$el && formFields[field].validateState == "error") {
                                        const errorElement = formFields[field].$el;
                                        if (errorElement){
                                            const inputEl = errorElement.querySelector("input, textarea, .el-input__inner");
                                            if(inputEl){
                                                inputEl.focus();
                                            }
                                            errorElement.scrollIntoView({ behavior: "smooth", block: "center" });
                                            break;
                                        }
                                    }
                                }
                            }
                            return false;
                        }
                    });
                },    
                affiliatepress_panel_full_row_clickable(row){
                    const vm = this
                    vm.$refs.multipleTable.toggleRowExpansion(row);
                },   
                affiliatepress_upload_edit_profile_image_func(response, file, fileList){
                    const vm = this;
                    vm.$refs.avatarRef.clearFiles();
                    if(response != ""){
                        vm.affiliates_profile_fields.avatar_url = response.upload_url;
                        vm.affiliates_profile_fields.avatar_name = response.upload_file_name;
                        vm.affiliates_profile_fields.ap_edit_profile_image_url = response.upload_file_name;
                        vm.userAvatar = response.upload_url;
                        vm.default_userAvatar_show = "false";
                    }
                },   
                affiliatepress_image_upload_limit(files, fileList){
                    const vm = this;
                        if(vm.affiliates_profile_fields.avatar_url != ""){
                        vm.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Multiple files not allowed', 'affiliatepress-affiliate-marketing').'",
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });
                    }
                },         
                affiliatepress_remove_affiliate_avatar() {
                    const vm = this;
                    vm.affiliates_profile_fields.avatar_url ="";
                    vm.affiliates_profile_fields.avatar_name = "";
                    vm.affiliates_profile_fields.ap_edit_profile_image_url = "";
                    vm.userAvatar = vm.default_userAvatar;
                    vm.default_userAvatar_show = "true";
                    vm.$refs.avatarRef.clearFiles();
                },      
                checkUploadedFile(file){
                    const vm = this;
                    if(file.type != "image/jpeg"  && file.type != "image/jpg" && file.type != "image/png" && file.type != "image/webp"){
                        vm.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: vm.file_upload_type_validation_message,
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });
                        return false;
                    }else{
                        var ap_image_size = parseFloat(file.size / 1000000);
                        if(ap_image_size >= 1){
                            vm.$notify({
                                title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                                message: vm.file_upload_limit_validation_message,
                                type: "error",
                                customClass: "error_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                    
                            return false;
                        }
                    }
                },
                visitchangeCurrentPage(perPage) {
                    const vm = this;
                    var total_item = vm.visits_totalItems;
                    var recored_perpage = perPage;
                    var select_page =  vm.visits_currentPage;                
                    var current_page = Math.ceil(total_item/recored_perpage);
                    if(total_item <= recored_perpage ) {
                        current_page = 1;
                    } else if(select_page >= current_page ) {
                        
                    } else {
                        current_page = select_page;
                    }
                    return current_page;
                },
                visitchangePaginationSize(selectedPage) {
                    const vm = this;
                    selectedPage = parseInt( selectedPage );
                    vm.visits_perpage = selectedPage;
                    var current_page = vm.visitchangeCurrentPage(selectedPage);                                        
                    vm.visits_currentPage = current_page;    
                    vm.loadvisits();
                },
                async validatePassword(rule, value) {
                    if (value && this.affiliates.confirm_password) {
                        if (this.$refs.affiliates_reg_form_data) {
                            try {
                                await this.$refs.affiliates_reg_form_data.validateField("confirm_password");
                            } catch (e) {
                                // ignore confirm password errors
                            }
                        }
                    }
                    return true;
                },
                validateConfirmPassword(rule, value, callback) {
                    if (value !== this.affiliates.password) {
                        callback(new Error(this.confirm_password_field.confirm_password_validation_msg));
                    } else {
                        callback();
                    }
                },
                checklandingOverflow(row) {
                    return (el) => {
                    if (!el) return;

                    requestAnimationFrame(() => {
                        const hasOverflow = el.scrollHeight > el.offsetHeight;
                        row._isOverflow = hasOverflow;
                    });
                    };
                },
                checkrefgOverflow(row) {
                    return (el) => {
                    if (!el) return;

                    requestAnimationFrame(() => {
                        const hasOverflow = el.scrollHeight > el.offsetHeight;
                        row._refOverflow = hasOverflow;
                    });
                    };
                },
                deleteAffiliatelink(ap_affiliate_link_id,index){
                    const vm = this;
                    vm.affiliate_delete_link_loader = index;
                    var postData = { action:"affiliatepress_delete_affiliate_custome_link", ap_affiliate_link_id: ap_affiliate_link_id, _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(response.data.variant == "success"){
                            vm.get_affiliates_links_data();                                                           
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });     
                            vm.affiliate_delete_link_loader = null;         
                        }
                        else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            vm.affiliate_delete_link_loader = null;
                        }
                    }.bind(this) )
                    .catch( function (error) {
                        vm.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',                        
                        });
                        vm.affiliate_delete_link_loader = null;
                    });
                },
                ';

            return $affiliatepress_affiliate_panel_dynamic_vue_method;
        }
                
        /**
         * Function for add register fields dynamic data
         *
         * @return void
        */
        function affiliatepress_affiliate_panel_dynamic_data_fields_func($affiliatepress_dynamic_data_fields){
            
            global $AffiliatePress,$wpdb,$affiliatepress_tbl_ap_affiliate_form_fields,$affiliatepress_global_options,$affiliatepress_tbl_ap_affiliate_form_fields,$affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_affiliates_group, $affiliatepress_payout,$affiliatepress_affiliates,$affiliatepress_max_tracking_cookie_days;

            $affiliatepress_options = $affiliatepress_global_options->affiliatepress_global_options();

            $affiliatepress_pagination_value = (isset($affiliatepress_options['fontend_pagination_val']))?$affiliatepress_options['fontend_pagination_val']:array();

            $affiliatepress_dynamic_data_fields['is_display_tab_content_loader'] = '1';
            
            $affiliatepress_dynamic_data_fields['is_login'] = 'false';
            $affiliatepress_dynamic_data_fields['allow_user_access'] = 'false'; 
            $affiliatepress_dynamic_data_fields['is_show_register_form'] = 'true';
            $affiliatepress_dynamic_data_fields['register_form_msg'] = '';

            $affiliatepress_affiliate_default_status = $AffiliatePress->affiliatepress_get_settings('affiliate_default_status', 'affiliate_settings');
            $affiliatepress_dynamic_data_fields['affiliate_default_status'] = $affiliatepress_affiliate_default_status;

            $affiliatepress_current_user_id = get_current_user_id();
            if($affiliatepress_current_user_id && $affiliatepress_current_user_id != 0){
                $affiliatepress_allow_user = $this->affilatepress_allow_affiliate_user_to_access_affiliate_panel($affiliatepress_current_user_id);
                $affiliatepress_dynamic_data_fields['is_login'] = 'true';
                if($affiliatepress_allow_user){
                    $affiliatepress_dynamic_data_fields['allow_user_access'] = 'true'; 
                }
            }
            $affiliatepress_affiliate_allow_register = false;

            $affiliatepress_dynamic_data_fields['signup_url']    = '';
            $affiliatepress_dynamic_data_fields['allow_signup']    = '';

            $affiliatepress_allow_affiliate_registration = $AffiliatePress->affiliatepress_get_settings('allow_affiliate_registration', 'affiliate_settings');
            $affiliatepress_dynamic_data_fields['allow_affiliate_registration'] = $affiliatepress_allow_affiliate_registration;

            if($affiliatepress_dynamic_data_fields['is_login'] == 'true' && $affiliatepress_dynamic_data_fields['allow_user_access'] == 'false'){                
                $affiliatepress_dynamic_data_fields['allow_signup'] = $affiliatepress_allow_affiliate_registration;
                $affiliatepress_affiliate_registration_page_id = $AffiliatePress->affiliatepress_get_settings('affiliate_registration_page_id', 'affiliate_settings');
                $affiliatepress_dynamic_data_fields['signup_url'] = get_permalink($affiliatepress_affiliate_registration_page_id);
                $affiliatepress_affiliate_allow_register = true;
            }
            if($affiliatepress_dynamic_data_fields['is_login'] == 'false'){
                $affiliatepress_affiliate_registration_page_id = $AffiliatePress->affiliatepress_get_settings('affiliate_registration_page_id', 'affiliate_settings');
                $affiliatepress_dynamic_data_fields['signup_url'] = get_permalink($affiliatepress_affiliate_registration_page_id);
                $affiliatepress_affiliate_allow_register = true;
            }
            

            $affiliatepress_dynamic_data_fields['affiliate_allow_register']    = ($affiliatepress_affiliate_allow_register)?'true':'false';

            $affiliatepress_current_currency_symbol = $AffiliatePress->affiliatepress_get_current_currency_symbol();
            $affiliatepress_dynamic_data_fields['currency_symbol'] = $affiliatepress_current_currency_symbol;

            $affiliatepress_minimum_payment_amount = floatval($AffiliatePress->affiliatepress_get_settings('minimum_payment_amount', 'commissions_settings'));
            $affiliatepress_minimum_payment_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_minimum_payment_amount);
            $affiliatepress_dynamic_data_fields['minimum_payment_amount_data'] = $affiliatepress_minimum_payment_amount;

            $affiliatepress_endOfLastMonth = date('Y-m-d'); //phpcs:ignore
            $affiliatepress_startOfLastMonth = date('Y-m-d', strtotime($affiliatepress_endOfLastMonth.' -30 days')); //phpcs:ignore      

            $affiliatepress_dynamic_data_fields['dashboard_date_range'] = array($affiliatepress_startOfLastMonth,$affiliatepress_endOfLastMonth);

            
            
            $affiliatepress_dynamic_data_fields['dashboard_total_earning']    = '';
            $affiliatepress_dynamic_data_fields['dashboard_paid_earning']     = '';
            $affiliatepress_dynamic_data_fields['dashboard_unpaid_earning']   = '';
            $affiliatepress_dynamic_data_fields['dashboard_total_visits']     = '';
            $affiliatepress_dynamic_data_fields['dashboard_total_commission'] = '';
            $affiliatepress_dynamic_data_fields['affiliate_dashboard_loader'] = '0';
            $affiliatepress_dynamic_data_fields['is_apply_disabled'] = false;
            $affiliatepress_dynamic_data_fields['revenue_chart_data']         = array();

            
            $affiliatepress_tracking_cookie_days = $AffiliatePress->affiliatepress_get_settings('tracking_cookie_days', 'affiliate_settings');
            if($affiliatepress_tracking_cookie_days == 0){
                $affiliatepress_tracking_cookie_days = $affiliatepress_max_tracking_cookie_days;
            }
            $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
            $affiliatepress_affiliate_user_self_closed_account = $AffiliatePress->affiliatepress_get_settings('affiliate_user_self_closed_account', 'affiliate_settings');
            $affiliatepress_dynamic_data_fields['affiliate_user_self_closed_account'] = $affiliatepress_affiliate_user_self_closed_account;
            $affiliatepress_dynamic_data_fields['affiliatepress_affiliate_id_login'] = $affiliatepress_affiliate_id;

            $affiliatepress_default_commission_rate = $affiliatepress_affiliates->affiliatepress_get_current_affiliate_rate($affiliatepress_affiliate_id);   
            $affiliatepress_default_discount_label = esc_html__('Commission Rates :', 'affiliatepress-affiliate-marketing');             

            $affiliatepress_dynamic_data_fields['default_commission_rate'] = $affiliatepress_default_commission_rate;
            $affiliatepress_dynamic_data_fields['default_discount_label'] = $affiliatepress_default_discount_label;
            $affiliatepress_dynamic_data_fields['tracking_cookie_days'] = $affiliatepress_tracking_cookie_days.' '.esc_html__( 'Days', 'affiliatepress-affiliate-marketing');
            $affiliatepress_dynamic_data_fields['is_add_affiliate_link'] = 1;
            $affiliatepress_dynamic_data_fields['affiliate_delete_link_loader'] = "0";

            $affiliatepress_dynamic_data_fields['affiliate_common_link'] = "";
            $affiliatepress_dynamic_data_fields['affiliate_link_copy_message'] = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('link_copied', 'message_settings'));
            $affiliatepress_dynamic_data_fields['file_upload_type_validation_message'] = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('file_upload_type_validation', 'message_settings'));
            $affiliatepress_dynamic_data_fields['file_upload_limit_validation_message'] = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('file_upload_limit_validation', 'message_settings'));
            $affiliatepress_affiliates_avatar =  AFFILIATEPRESS_IMAGES_URL . '/default-avatar.jpg';
            $affiliatepress_affiliates_avatar =  apply_filters('affiliatepress_modify_affiliate_default_avatra' , $affiliatepress_affiliates_avatar);
            
            $affiliatepress_dynamic_data_fields['default_userAvatar'] = $affiliatepress_affiliates_avatar;
            $affiliatepress_dynamic_data_fields['default_userAvatar_show'] = 'true';
            $affiliatepress_dynamic_data_fields['userName']   = '';
            $affiliatepress_dynamic_data_fields['userFirstName']   = '';
            $affiliatepress_dynamic_data_fields['userEmail']   = '';
            $affiliatepress_dynamic_data_fields['userAvatar'] = $affiliatepress_affiliates_avatar;
            $affiliatepress_dynamic_data_fields['affiliate_link_slug'] = "";
            if($affiliatepress_dynamic_data_fields['allow_user_access'] == 'true' && $affiliatepress_dynamic_data_fields['is_login'] == 'true'){

                $affiliatepress_dynamic_data_fields['userName'] = $AffiliatePress->affiliatepress_get_affiliate_user_name_by_id($affiliatepress_current_user_id);
                $affiliatepress_dynamic_data_fields['userFirstName'] = $AffiliatePress->affiliatepress_get_affiliate_user_first_name_by_id($affiliatepress_current_user_id);
                $affiliatepress_dynamic_data_fields['userEmail'] = $AffiliatePress->affiliatepress_get_affiliate_user_email_by_affiliate_id($affiliatepress_affiliate_id);
                $affiliatepress_affiliate_user_avatar = $AffiliatePress->affiliatepress_get_affiliate_user_avatar_image_by_user_id($affiliatepress_current_user_id); 

                if(!empty($affiliatepress_affiliate_user_avatar)){
                    $affiliatepress_dynamic_data_fields['userAvatar'] = AFFILIATEPRESS_UPLOAD_URL.'/'.basename($affiliatepress_affiliate_user_avatar);
                    $affiliatepress_dynamic_data_fields['default_userAvatar_show'] = 'false';
                }
                $affiliatepress_affiliate_common_link = $AffiliatePress->affiliatepress_get_affiliate_common_link($affiliatepress_allow_user);
                $affiliatepress_affiliate_common_link =  apply_filters('affiliatepress_modify_affiliate_link' , $affiliatepress_affiliate_common_link , $affiliatepress_allow_user);
                $affiliatepress_dynamic_data_fields['affiliate_common_link'] = $affiliatepress_affiliate_common_link;

                $affiliatepress_affiliate_link_slug = $AffiliatePress->affiliatepress_get_affiliate_custom_link($affiliatepress_allow_user,'http://new_link');
                $affiliatepress_affiliate_link_slug = str_replace('http://new_link','',$affiliatepress_affiliate_link_slug);
                $affiliatepress_dynamic_data_fields['affiliate_link_slug'] = $affiliatepress_affiliate_link_slug;
            }
            
            $affiliatepress_dynamic_data_fields['affiliate_current_tab'] = "dashboard";   
            $affiliatepress_dynamic_data_fields['not_allow_user_affiliate_panel'] = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('not_allow_affiliate_register', 'message_settings'));
            $affiliatepress_dynamic_data_fields['show_forgot_password_form'] = '0'; 
            $affiliatepress_dynamic_data_fields['is_error_msg'] = "";
            $affiliatepress_dynamic_data_fields['is_display_error'] = "0";
            $affiliatepress_dynamic_data_fields['is_success_msg'] = "";
            $affiliatepress_dynamic_data_fields['is_display_success'] = "0";

            /* Commission Page Data */
            $affiliatepress_all_commissions_status = $affiliatepress_options['commissions_status'];
            $affiliatepress_dynamic_data_fields['all_commissions_status'] = $affiliatepress_all_commissions_status;
            $affiliatepress_dynamic_data_fields['commissions_search']['ap_commission_search_date'] = '';
            $affiliatepress_dynamic_data_fields['commissions_search']['commission_status'] = '';
            $affiliatepress_dynamic_data_fields['commission_items'] = [];
            $affiliatepress_dynamic_data_fields['commission_perpage'] = 10;
            $affiliatepress_dynamic_data_fields['commission_currentPage'] = 1;
            $affiliatepress_dynamic_data_fields['commission_order_by'] = '';
            $affiliatepress_dynamic_data_fields['commission_order'] = '';
            $affiliatepress_dynamic_data_fields['commission_totalItems'] = 120;
            $affiliatepress_dynamic_data_fields['commission_pagination_counts'] = 1;
            $affiliatepress_dynamic_data_fields['commission_pagination_labels'] = 1;
            $affiliatepress_dynamic_data_fields['affiliate_commission_loader'] = '0';

            /* Payments Page Data */
            $affiliatepress_all_payments_status = $affiliatepress_options['payment_status'];
            if(!empty($affiliatepress_all_payments_status)){
                $affiliatepress_all_payments_status_temp = array();
                foreach($affiliatepress_all_payments_status as $affiliatepress_ap_payment_status){
                    if($affiliatepress_ap_payment_status['value'] == '1' || $affiliatepress_ap_payment_status['value'] == '4'){
                        $affiliatepress_all_payments_status_temp[] = $affiliatepress_ap_payment_status;
                    }                    
                }
                $affiliatepress_all_payments_status = $affiliatepress_all_payments_status_temp;
            }
            $affiliatepress_dynamic_data_fields['all_payments_status'] = $affiliatepress_all_payments_status;
            $affiliatepress_dynamic_data_fields['payments_search']['ap_payment_created_date'] = '';
            $affiliatepress_dynamic_data_fields['payments_search']['payment_status'] = '';
            $affiliatepress_dynamic_data_fields['payments_items'] = [];
            $affiliatepress_dynamic_data_fields['payments_perpage'] = 10;
            $affiliatepress_dynamic_data_fields['payments_currentPage'] = 1;
            $affiliatepress_dynamic_data_fields['payments_order_by'] = '';
            $affiliatepress_dynamic_data_fields['payments_order'] = '';
            $affiliatepress_dynamic_data_fields['payments_totalItems'] = 120;
            $affiliatepress_dynamic_data_fields['payments_pagination_count'] = 1;
            $affiliatepress_dynamic_data_fields['payments_pagination_label'] = 1;
            $affiliatepress_dynamic_data_fields['affiliate_payments_loader'] = '0';

            /* Visit Page Data */
            $affiliatepress_dynamic_data_fields['visits_search']['ap_visit_date'] = array();
            $affiliatepress_dynamic_data_fields['visits_search']['visit_type']    = 'all_visit';
            $affiliatepress_dynamic_data_fields['visits_totalItems'] = 0;
            $affiliatepress_dynamic_data_fields['visits_pagination_count'] = 1;
            $affiliatepress_dynamic_data_fields['visits_pagination_label'] = 1;
            $affiliatepress_dynamic_data_fields['visits_perpage'] = 25;
            $affiliatepress_dynamic_data_fields['visits_defaultPerPage'] = 25;
            $affiliatepress_dynamic_data_fields['visits_currentPage'] = 1;
            $affiliatepress_dynamic_data_fields['visits_height'] = false;
            $affiliatepress_dynamic_data_fields['visits_items'] = [];
            $affiliatepress_dynamic_data_fields['visits_order_by'] = '';
            $affiliatepress_dynamic_data_fields['visits_order'] = '';            
            $affiliatepress_dynamic_data_fields['affiliate_visit_loader'] = '0';
            $affiliatepress_dynamic_data_fields['visit_pagination_val'] = $affiliatepress_pagination_value;
            $affiliatepress_dynamic_data_fields['visit_pagination_length_val'] = 25;

            /* Affiliate Link Page Data */
            $affiliatepress_dynamic_data_fields['affiliate_link'] = 'http://www.xyz.com';
            $affiliatepress_dynamic_data_fields['affiliate_custom_links'] = [];
            $affiliatepress_dynamic_data_fields['affiliate_custom_link_loader'] = '0';

            $affiliatepress_dynamic_data_fields['affiliate_links_data'] = array(
                'ap_page_link' => '',
                'ap_affiliates_campaign_name' => '',
                'ap_affiliates_sub_id' => '',
            );

            $affiliatepress_dynamic_data_fields['affiliate_links_data_rules'] = array(
                'ap_page_link'  => array(
                    array(
                        'required' => true,
                        'message'  => stripslashes_deep($AffiliatePress->affiliatepress_get_settings('link_empty_validation', 'message_settings')),
                        'trigger'  => 'blur',
                    ),
                    array(
                        'pattern' => '^(https?:\/\/)(localhost|\d{1,3}(\.\d{1,3}){3}|([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|([a-zA-Z0-9-]+\.)+local)(:\d+)?(\/[^\s]*)?$',
                        'message' => stripslashes_deep($AffiliatePress->affiliatepress_get_settings('link_pattern_validation', 'message_settings')),
                        'trigger' => 'blur',
                    ),
                ), 
                'ap_affiliates_campaign_name'  => array(
                    array(
                        'required' => true,
                        'message'  => stripslashes_deep($AffiliatePress->affiliatepress_get_settings('link_campaign_name_empty_validation', 'message_settings')),
                        'trigger'  => 'blur',
                    ),
                )
            );            

            /* Affiliate Creative */
            $affiliatepress_dynamic_data_fields['login_form_loader'] = '0';
            $affiliatepress_creative_temp_image =  AFFILIATEPRESS_IMAGES_URL . '/temp-creative.png';
            $affiliatepress_dynamic_data_fields['creative_temp_image'] = $affiliatepress_creative_temp_image;

            $affiliatepress_dynamic_data_fields['creative_search']['ap_creative_name'] = array();
            $affiliatepress_dynamic_data_fields['creative_search']['creative_type']    = '';
            $affiliatepress_dynamic_data_fields['creative_totalItems'] = 0;
            $affiliatepress_dynamic_data_fields['creative_pagination_count'] = 1;
            $affiliatepress_dynamic_data_fields['creative_pagination_label'] = 1;
            $affiliatepress_dynamic_data_fields['creative_perpage'] = 6;
            $affiliatepress_dynamic_data_fields['creative_currentPage'] = 1;
            $affiliatepress_dynamic_data_fields['creative_items'] = [];
            $affiliatepress_dynamic_data_fields['creative_order_by'] = '';
            $affiliatepress_dynamic_data_fields['creative_order'] = '';            
            $affiliatepress_dynamic_data_fields['affiliate_creative_loader'] = '0';            
            $affiliatepress_dynamic_data_fields['creative_popup_data'] = '';
            $affiliatepress_dynamic_data_fields['affiliate_close_account_loader'] = '0';

            /* Affiliate Edit Profile Fields */
            $affiliatepress_dynamic_data_fields['affiliates_profile_fields'] = array(
                'username'                     => "",
                'firstname'                    => "",
                'lastname'                     => "",
                'email'                        => "",
                'password'                     => "",
                "ap_affiliates_user_id"        => "",
                "ap_affiliates_payment_email"  => "",
                "ap_affiliates_website"        => "",
                "image_list"                   => [],
                "avatar_url"                   => "",
                "avatar_name"                  => "",
                "ap_edit_profile_image_url"    => "",
            );

            $affiliatepress_dynamic_data_fields['affiliates'] = array(
                'username'                     => "",
                'firstname'                    => "",
                'lastname'                     => "",
                'email'                        => "",
                'password'                     => "",
                'confirm_password'             => "",
                "ap_affiliates_user_id"        => "",
                "ap_affiliates_payment_email"  => "",
                "ap_affiliates_website"        => "",
            );

            $affiliatepress_confirm_password_field_settings = $AffiliatePress->affiliatepress_get_settings('confirm_password_field', 'field_settings');
            $affiliatepress_confirm_password_field_settings = !empty($affiliatepress_confirm_password_field_settings) ? maybe_unserialize($affiliatepress_confirm_password_field_settings) : array();

            if(!empty($affiliatepress_confirm_password_field_settings) && is_array($affiliatepress_confirm_password_field_settings)){

                $affiliatepress_is_display_confirm_password = isset($affiliatepress_confirm_password_field_settings['enable_confirm_password']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['enable_confirm_password']) : '';
                $affiliatepress_confirm_password_label = isset($affiliatepress_confirm_password_field_settings['confirm_password_label']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_label']) : '';
                $affiliatepress_confirm_password_placeholder =isset($affiliatepress_confirm_password_field_settings['confirm_password_placeholder']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_placeholder']) : '';
                $affiliatepress_confirm_password_error_msg = isset($affiliatepress_confirm_password_field_settings['confirm_password_error_msg']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_error_msg']) : '';
                $affiliatepress_confirm_password_validation_msg = isset($affiliatepress_confirm_password_field_settings['confirm_password_validation_msg']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_validation_msg']) : '';

                $affiliatepress_dynamic_data_fields['confirm_password_field'] = array(
                    'is_display_confirm_password' => $affiliatepress_is_display_confirm_password,
                    'confirm_password_label'  => $affiliatepress_confirm_password_label,
                    'confirm_password_placeholder'  => $affiliatepress_confirm_password_placeholder,
                    'confirm_password_error_msg'  => $affiliatepress_confirm_password_error_msg,
                    'confirm_password_validation_msg'  => $affiliatepress_confirm_password_validation_msg,
                );
            }

            $affiliatepress_dynamic_data_fields['rules'] = array();

            $affiliatepress_fields = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_form_fields, '*', 'WHERE ap_show_profile_field = %d ', array(1), '', 'order by ap_field_position ASC', '', false, false,ARRAY_A);           
            $affiliatepress_dynamic_data_fields['affiliate_fields'] = array();            
            if(!empty($affiliatepress_fields)){
                foreach($affiliatepress_fields as $affiliatepress_key=>$affiliatepress_field){

                    $affiliatepress_fields[$affiliatepress_key]['ap_field_label'] = stripslashes_deep($affiliatepress_fields[$affiliatepress_key]['ap_field_label']);
                    $affiliatepress_fields[$affiliatepress_key]['ap_field_placeholder'] = stripslashes_deep($affiliatepress_fields[$affiliatepress_key]['ap_field_placeholder']);
                    $affiliatepress_fields[$affiliatepress_key]['ap_field_error_message'] = stripslashes_deep($affiliatepress_fields[$affiliatepress_key]['ap_field_error_message']);

                    $affiliatepress_form_field_name = (isset($affiliatepress_field['ap_form_field_name']))?$affiliatepress_field['ap_form_field_name']:'';
                    $affiliatepress_field_required = (isset($affiliatepress_field['ap_field_required']))?$affiliatepress_field['ap_field_required']:'';
                    $affiliatepress_field_error_message = (isset($affiliatepress_field['ap_field_error_message']))?$affiliatepress_field['ap_field_error_message']:'';
                    $affiliatepress_dynamic_data_fields['affiliates_profile_fields'][$affiliatepress_form_field_name] = '';

                    if($affiliatepress_fields[$affiliatepress_key]['ap_form_field_name'] == 'ap_affiliates_payment_email'){
                        $affiliatepress_dynamic_data_fields['affiliate_fields_payout_label'] = stripslashes_deep($affiliatepress_fields[$affiliatepress_key]['ap_field_label']);
                        $affiliatepress_dynamic_data_fields['affiliate_fields_payout_placeholder'] = stripslashes_deep($affiliatepress_fields[$affiliatepress_key]['ap_field_placeholder']);         
                    }

                    if($affiliatepress_field_required == 1){
                        if(isset($affiliatepress_dynamic_data_fields['rules'][$affiliatepress_form_field_name][0])){
                            $affiliatepress_dynamic_data_fields['rules'][$affiliatepress_form_field_name][0]['message'] = (!empty($affiliatepress_field_error_message))?stripslashes_deep($affiliatepress_field_error_message):'';
                            if(isset($affiliatepress_dynamic_data_fields['rules'][$affiliatepress_form_field_name][1]['message'])){
                                $affiliatepress_dynamic_data_fields['rules'][$affiliatepress_form_field_name][1]['message'] = (!empty($affiliatepress_field_error_message))?stripslashes_deep($affiliatepress_field_error_message):'';
                            }
                        }else{
                            $affiliatepress_dynamic_data_fields['rules'][$affiliatepress_form_field_name][] = array(
                                'required' => true,
                                'message'  => (!empty($affiliatepress_field_error_message))?stripslashes_deep($affiliatepress_field_error_message):'',
                                'trigger'  => 'blur',                                    
                            );                            
                        }

                        if ($affiliatepress_field['ap_form_field_name'] === 'ap_affiliates_payment_email' || strpos($affiliatepress_field['ap_form_field_name'], 'email') !== false) {
                            $affiliatepress_dynamic_data_fields['rules'][$affiliatepress_form_field_name][] = array(
                                'type'    => 'email',
                                'message' => (!empty($affiliatepress_field_error_message)) ? stripslashes_deep($affiliatepress_field_error_message) : 'Please enter valid email address',
                                'trigger' => 'blur',
                            );
                        }
                    }

                }
            }
            $affiliatepress_dynamic_data_fields['affiliate_edit_profile_loader'] = "0";
            $affiliatepress_dynamic_data_fields['affiliate_fields'] = $affiliatepress_fields;
            $affiliatepress_paymnet_email_show_panel  = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_form_fields, 'ap_show_profile_field', 'WHERE ap_form_field_name = %s', array('ap_affiliates_payment_email'), '', '', '', false, true,ARRAY_A);  
            $affiliatepress_show_paymnet_email_panel = isset($affiliatepress_paymnet_email_show_panel['ap_show_profile_field']) ? intval($affiliatepress_paymnet_email_show_panel['ap_show_profile_field']) : 1;
            $affiliatepress_dynamic_data_fields['affiliatepress_paymnet_email_show_panel'] = $affiliatepress_show_paymnet_email_panel; 

            $affiliatepress_dynamic_data_fields['affiliate_change_password_loader'] = "0"; 
            $affiliatepress_dynamic_data_fields['affiliate_change_password'] = array(
                'old_password'     => '',
                'new_password'     => '',
                'confirm_password' => '',
            ); 

            $required_field_validation = $AffiliatePress->affiliatepress_get_settings('required_field_validation', 'message_settings');
            $required_field_validation = (!empty($required_field_validation))?stripslashes_deep($required_field_validation):'';

            $affiliatepress_dynamic_data_fields['affiliate_change_password_rules'] = array(
                'old_password'  => array(
                    array(
                        'required' => true,
                        'message'  => $required_field_validation,
                        'trigger'  => 'blur',
                    ),
                ), 
                'new_password'  => array(
                    array(
                        'required' => true,
                        'message'  => $required_field_validation,
                        'trigger'  => 'blur',
                    ),
                ),
                'confirm_password'  => array(
                    array(
                        'required' => true,
                        'message'  => $required_field_validation,
                        'trigger'  => 'blur',
                    ),
                ),                                                                    
            );            
            
            



            /* login form */
            $affiliatepress_dynamic_data_fields['login_form_loader'] = '0';
            $affiliatepress_dynamic_data_fields['affiliatepress_login_form'] = array(
                'affiliatepress_username'    => '',
                'affiliatepress_password'    => '',
                'affiliatepress_is_remember' => ''
            );

            $affiliatepress_cookie_data = $this->affiliatepress_get_login_cookies();
            if(isset($affiliatepress_cookie_data['username']) && $affiliatepress_cookie_data['password']){
                $affiliatepress_dynamic_data_fields['affiliatepress_login_form']['affiliatepress_username'] = $affiliatepress_cookie_data['username'];
                $affiliatepress_dynamic_data_fields['affiliatepress_login_form']['affiliatepress_password'] = $affiliatepress_cookie_data['password'];
            }

            $affiliatepress_dynamic_data_fields['affiliatepress_login_form_rules'] = array(
                'affiliatepress_username' => array(
                    array(
                        'required' => true,
                        'message'  => stripslashes_deep($AffiliatePress->affiliatepress_get_settings('login_username_empty_validation', 'message_settings')),
                        'trigger'  => 'change',
                    ),
                ),
                'affiliatepress_password' => array(
                    array(
                        'required' => true,
                        'message'  => stripslashes_deep($AffiliatePress->affiliatepress_get_settings('login_password_empty_validation', 'message_settings')),
                        'trigger'  => 'change',
                    ),
                ),				
            );
            /* Forgot password form */
            $affiliatepress_dynamic_data_fields['forgot_form_loader'] = '0';
            $affiliatepress_dynamic_data_fields['affiliatepress_forgot_password_form'] = array(
                'affiliatepress_email' => '',
            );
            $affiliatepress_dynamic_data_fields['affiliatepress_forgot_password_form_rules'] = array(
                'affiliatepress_email' => array(
                    array(
                        'required' => true,
                        'message'  => stripslashes_deep($AffiliatePress->affiliatepress_get_settings('forget_password_empty_validation', 'message_settings')),
                        'trigger'  => 'change',
                    ),
                ),
            );
           
            $affiliatepress_dynamic_data_fields['is_affiliate_form_loader'] = 0;


            if($affiliatepress_affiliate_allow_register){
                global $affiliatepress_affiliate_register;
                $affiliatepress_dynamic_data_fields = $affiliatepress_affiliate_register->affiliatepress_affiliate_registration_dynamic_data_fields_func($affiliatepress_dynamic_data_fields, true);

                $affiliatepress_dynamic_data_fields['show_register_form'] = '0';
            }

            $affiliatepress_commission_billing_cycle = $AffiliatePress->affiliatepress_get_settings('commission_billing_cycle', 'commissions_settings');

            $affiliatepress_auto_payout_date = "";
            if($affiliatepress_commission_billing_cycle != 'disabled' && !empty($affiliatepress_commission_billing_cycle)){
                $affiliatepress_auto_payout_date = $affiliatepress_payout->affiliatepress_get_next_auto_payout_date();
                $affiliatepress_auto_payout_date = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_auto_payout_date);
            }

            $affiliatepress_dynamic_data_fields['auto_payout_date'] = $affiliatepress_auto_payout_date;

            //$affiliatepress_dynamic_data_fields['affiliate_close_account_loader'] = '0';
           
            $affiliatepress_dynamic_data_fields = apply_filters('affiliatepress_affiliate_panel_data_fields', $affiliatepress_dynamic_data_fields);

            return wp_json_encode($affiliatepress_dynamic_data_fields);
        }

        /**
         * Function for set front CSS
         *
         * @return void
        */
        function affiliatepress_affiliatepress_set_front_css($affiliatepress_force_enqueue = 0 ){
            
            global $AffiliatePress;
            /* AffiliatePress Front CSS */
            wp_register_style('affiliatepress_front_variables_css', AFFILIATEPRESS_URL . 'css/affiliatepress_front_variables.css', array(), AFFILIATEPRESS_VERSION);

            $affiliatepress_custom_css = $AffiliatePress->affiliatepress_front_dynamic_variable_add();
            wp_add_inline_style('affiliatepress_front_variables_css', $affiliatepress_custom_css,'after');

            wp_register_style('affiliatepress_elements_css', AFFILIATEPRESS_URL . 'css/affiliatepress_elements_front.css', array(), AFFILIATEPRESS_VERSION);
            wp_register_style('affiliatepress_component_css', AFFILIATEPRESS_URL . 'css/affiliatepress_component.css', array(), AFFILIATEPRESS_VERSION);            
            wp_register_style('affiliatepress_front_css', AFFILIATEPRESS_URL . 'css/affiliatepress_front.css', array(), AFFILIATEPRESS_VERSION);            
            wp_register_style('affiliatepress_front_rtl_css', AFFILIATEPRESS_URL . 'css/affiliatepress_front_rtl.css', array(), AFFILIATEPRESS_VERSION);

            if($affiliatepress_force_enqueue == 1){
                wp_enqueue_style('affiliatepress_front_variables_css');
                wp_enqueue_style('affiliatepress_elements_css');
                wp_enqueue_style('affiliatepress_component_css');
                wp_enqueue_style('affiliatepress_front_css');
                if(is_rtl()){
                    wp_enqueue_style('affiliatepress_front_rtl_css');
                }
            }
            do_action('affiliatepress_affiliate_panel_front_style',$affiliatepress_force_enqueue);
        }
        
        /**
         * Function for set front js
         *
         * @param  mixed $affiliatepress_force_enqueue
         * @return void
        */
        function affiliatepress_set_front_js($affiliatepress_force_enqueue = 0 ){
            global $AffiliatePress;
            /* Plugin JS File */
            wp_register_script('affiliatepress_front_js', AFFILIATEPRESS_URL . 'js/affiliatepress_vue.min.js', array(), AFFILIATEPRESS_VERSION,false);
            wp_register_script('affiliatepress_axios_js', AFFILIATEPRESS_URL . 'js/affiliatepress_axios.min.js', array(), AFFILIATEPRESS_VERSION,false);
            wp_register_script('affiliatepress_wordpress_vue_qs_js', AFFILIATEPRESS_URL . 'js/affiliatepress_wordpress_vue_qs_helper.js', array(), AFFILIATEPRESS_VERSION,false);
            wp_register_script('affiliatepress_element_js', AFFILIATEPRESS_URL . 'js/affiliatepress_element.min.js', array(), AFFILIATEPRESS_VERSION,true);
            wp_register_script('affiliatepress_charts_js', AFFILIATEPRESS_URL . 'js/affiliatepress_chart.umd.min.js', array(), AFFILIATEPRESS_VERSION,false); 

            if($affiliatepress_force_enqueue == 1){

                $affiliatepress_data = 'var affiliatepress_ajax_obj = '.wp_json_encode( array('ajax_url' => admin_url( 'admin-ajax.php'))).';';
                wp_add_inline_script('affiliatepress_front_js', $affiliatepress_data, 'before');

                wp_enqueue_script('affiliatepress_front_js');
                wp_enqueue_script('affiliatepress_axios_js');                
                wp_enqueue_script('affiliatepress_wordpress_vue_qs_js');
                wp_enqueue_script('affiliatepress_element_js');

                $affiliatepress_affiliate_id = $this->affiliatepress_check_affiliate_user_allow_to_access_func();
                if(!empty($affiliatepress_affiliate_id)){
                    wp_enqueue_script( 'moment' );            
                }
                wp_enqueue_script('affiliatepress_charts_js');

                do_action('affiliatepress_affiliate_panel_front_script', $affiliatepress_force_enqueue);

            }            

        }

                
        /**
         * Function for affiliate registration page shortcode 
         *
         * @return void
        */
        function affiliatepress_affiliate_panel_func(){
            
            global $affiliatepress_common_date_format;

            $affiliatepress_site_current_language = get_locale();

            $affiliatepress_uniq_id = uniqid();
            $this->affiliatepress_affiliatepress_set_front_css(1);
            $this->affiliatepress_set_front_js(1);
            
            $affiliatepress_front_booking_dynamic_helper_vars = '';
            $affiliatepress_front_booking_dynamic_helper_vars = apply_filters('affiliatepress_affiliate_panel_dynamic_helper_vars', $affiliatepress_front_booking_dynamic_helper_vars);

            $affiliatepress_dynamic_directive_data = '';
            $affiliatepress_dynamic_directive_data = apply_filters('affiliatepress_affiliate_panel_dynamic_directives', $affiliatepress_dynamic_directive_data);

            $affiliatepress_dynamic_data_fields = array();            
            $affiliatepress_dynamic_data_fields = apply_filters('affiliatepress_affiliate_panel_dynamic_data_fields', $affiliatepress_dynamic_data_fields);
            
            $affiliatepress_dynamic_on_load_methods_data = '';
            $affiliatepress_dynamic_on_load_methods_data = apply_filters('affiliatepress_affiliate_panel_dynamic_on_load_methods', $affiliatepress_dynamic_on_load_methods_data);          

            $affiliatepress_vue_methods_data = '';
            $affiliatepress_vue_methods_data = apply_filters('affiliatepress_affiliate_panel_dynamic_vue_methods', $affiliatepress_vue_methods_data);
            
            $affiliatepress_script_return_data = '';
            if (! empty($affiliatepress_front_booking_dynamic_helper_vars) ) {
                $affiliatepress_script_return_data .= $affiliatepress_front_booking_dynamic_helper_vars;
            }
            
            $affiliatepress_script_return_data .= "var affiliatepress_uniq_id_js_var = '" . $affiliatepress_uniq_id . "';";
            $affiliatepress_nonce = esc_html(wp_create_nonce('ap_wp_nonce'));

            $affiliatepress_script_return_data .= "var affiliatepress_uniq_id_js_var = '" . $affiliatepress_uniq_id . "';";
            $affiliatepress_nonce = esc_html(wp_create_nonce('ap_wp_nonce'));

            $affiliatepress_site_date = date('Y-m-d H:i:s', current_time( 'timestamp') );// phpcs:ignore
            $affiliatepress_vue_root_element_id = '#affiliatepress_panel_' . $affiliatepress_uniq_id;
            $affiliatepress_vue_root_element_id_without_hash = 'affiliatepress_panel__' . $affiliatepress_uniq_id;
            $affiliatepress_vue_root_element_id_el = 'method_' . $affiliatepress_uniq_id;
            
            ob_start();
            $affiliatepress_shortcode_file_url = AFFILIATEPRESS_VIEWS_DIR.'/front/affiliate_panel_form.php';
            $affiliatepress_shortcode_file_url = apply_filters('affiliatepress_affiliate_panel_view_file', $affiliatepress_shortcode_file_url);
            include $affiliatepress_shortcode_file_url;            
            $affiliatepress_content = ob_get_clean();   

            ob_start();
            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/manage_language.php';                            
            include $affiliatepress_load_file_name;            
            $affiliatepress_localization_data_content = ob_get_clean();            
            
            $affiliatepress_affiliate_panel_dynamic_constant = '';
            $affiliatepress_affiliate_panel_dynamic_constant = apply_filters('affiliatepress_affiliate_panel_dynamic_constant_define', $affiliatepress_affiliate_panel_dynamic_constant);

            $affiliatepress_affiliate_panel_dynamic_component = '';
            $affiliatepress_affiliate_panel_dynamic_component = apply_filters('affiliatepress_affiliate_panel_dynamic_component', $affiliatepress_affiliate_panel_dynamic_component);

            $affiliatepress_load_panel_shortcode_data = '';
            $affiliatepress_load_panel_shortcode_data = apply_filters('affiliatepress_load_panel_shortcode_data', $affiliatepress_load_panel_shortcode_data);

            $affiliatepress_lastWeekStart = date('Y-m-d', strtotime('monday last week'));// phpcs:ignore
            $affiliatepress_lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));// phpcs:ignore

            $affiliatepress_drawer_direction = (is_rtl())?"rtl":"ltr";

            $affiliatepress_panel_labels = $this->affiliatepress_get_panel_lables();

            $affiliatepress_script_return_data .= $affiliatepress_localization_data_content;
            $affiliatepress_script_return_data .= '
            var app = "";
            const { ref, createApp, reactive} = Vue;  
            '.$affiliatepress_front_booking_dynamic_helper_vars.'  
            app = createApp({ 
				el: "' . $affiliatepress_vue_root_element_id . '",
				components:{  
                     '.$affiliatepress_affiliate_panel_dynamic_component.'
                },				
				data(){
                    var affiliatepress_affiliate_panel_return_data = '.$affiliatepress_dynamic_data_fields.';

                    if (affiliatepress_affiliate_panel_return_data.rules) {
                        affiliatepress_affiliate_panel_return_data.rules.confirm_password = [
                            { required: true, message: affiliatepress_affiliate_panel_return_data.confirm_password_field.confirm_password_error_msg, trigger: "blur" },
                            { validator: this.validateConfirmPassword, trigger: "blur" }
                        ];
                    }

                    if (affiliatepress_affiliate_panel_return_data.rules && affiliatepress_affiliate_panel_return_data.rules.password) {
                        affiliatepress_affiliate_panel_return_data.rules.password.push({
                            validator: this.validatePassword,
                            trigger: ["blur", "change"]
                        });
                    }

                    affiliatepress_affiliate_panel_return_data["current_screen_size"] = "";

                    affiliatepress_affiliate_panel_return_data["affiliatepress_container_dynamic_class"] = "";
                    affiliatepress_affiliate_panel_return_data["affiliatepress_footer_dynamic_class"] = "";                    
                    
                    affiliatepress_affiliate_panel_return_data["ap_common_date_format"] = "'.esc_html($affiliatepress_common_date_format).'";   

                    affiliatepress_affiliate_panel_return_data["affiliate_panel_labels"] = '.json_encode($affiliatepress_panel_labels).';

                    var today = new Date();                         
                    const dayOfWeek = today.getDay(); 

                    const Last30DaysendDate = new Date();
                    const Last30DaysstartDate = new Date();
                    Last30DaysstartDate.setDate(Last30DaysendDate.getDate() - 30);

                    const yesterday = new Date(today);
                    yesterday.setDate(today.getDate() - 1);

                    const startDateWeek = new Date(today);
                    startDateWeek.setDate(today.getDate() - dayOfWeek); 
                    const endDateWeek = new Date(startDateWeek);
                    endDateWeek.setDate(startDateWeek.getDate() + 6);

                    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                    const lastWeekStart = new Date(today.setDate(today.getDate() - dayOfWeek - 1));                    
                    const lastWeekEnd = new Date(today.setDate(lastWeekStart.getDate() - 6));                                
                    today = new Date();
                    const startOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0); // Last day of the last month
                    const startOfThreeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 3, 1);
                    const endOfLastThreeeMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                    const currentDate = new Date();
                    const startDate = new Date(today);
                    startDate.setMonth(currentDate.getMonth() - 6);
                    const startOfLastsixMonth = startDate.setDate(1);
                    const endDate = new Date();
                    const endOfLastsixMonth = endDate.setDate(0);                                
                    const startDateNew = new Date(currentDate);
                    startDateNew.setFullYear(currentDate.getFullYear() - 1);
                    startDateNew.setMonth(1 - 1); 
                    const startOfLastyear = startDateNew.setDate(1);
                    const endDatenew = new Date(startDateNew);
                    const endOfLastyear = endDatenew.setFullYear(startDateNew.getFullYear(), startDateNew.getMonth() + 12, 0); 


                    const shortcuts = [
                        { text: "'.esc_html__("Today", "affiliatepress-affiliate-marketing").'",
                            value: () => {
                                return [today, today]
                            },
                        },
                        { text: "'.esc_html__("Yesterday", "affiliatepress-affiliate-marketing").'",
                            value: () => {
                                return [yesterday, yesterday]
                            },
                        },                        
                        { text: "'.esc_html__("This Week", "affiliatepress-affiliate-marketing").'",
                            value: () => {
                                return [startDateWeek, endDateWeek]
                            },
                        },
                        { text: "'.esc_html__("Last Week", "affiliatepress-affiliate-marketing").'",
                        value: () => {
                            return [lastWeekEnd, lastWeekStart]
                        },
                        },
                        {
                            text: "'.esc_html__('Last Month', "affiliatepress-affiliate-marketing").'",
                            value: () => {
                                return [startOfLastMonth, endOfLastMonth]
                            },
                        },
                        {
                            text: "'.esc_html__('This Month', "affiliatepress-affiliate-marketing").'",
                            value: () => {
                                return [startOfMonth, endOfMonth]
                            },
                        }, 
                        {
                            text: "'.esc_html__('30 Days', "affiliatepress-affiliate-marketing").'",
                            value: () => {
                                return [Last30DaysstartDate, Last30DaysendDate]
                            },
                        },                                               
                        {
                        text: "'.esc_html__('3 Months', 'affiliatepress-affiliate-marketing').'",
                        value: () => {
                            return [startOfThreeMonthsAgo, endOfLastThreeeMonth]
                        },
                        
                        },
                        {
                            text: "'.esc_html__('6 Months', 'affiliatepress-affiliate-marketing').'",
                            value: () => {
                                return [startOfLastsixMonth, endOfLastsixMonth]
                            },                                    
                        },
                        {
                            text: "'.esc_html__('Last Year', 'affiliatepress-affiliate-marketing').'",
                            value: () => {
                                return [startOfLastyear, endOfLastyear]
                            },                                    
                        },                                                                        
                    ];
                    affiliatepress_affiliate_panel_return_data["shortcuts"] = shortcuts;
                    affiliatepress_affiliate_panel_return_data["drawer_direction"] = "'.$affiliatepress_drawer_direction.'";
                    '.$affiliatepress_affiliate_panel_dynamic_constant.'

					return affiliatepress_affiliate_panel_return_data;
				},
				filters: {
					
				},
                beforeCreate(){                       
					this.is_affiliate_form_loader = "0";
				},
				created(){
                   
				},
				mounted(){
					'.$affiliatepress_dynamic_on_load_methods_data.'
				},
                computed: {                    
                                       
                },
                methods:{
                    affiliatepress_load_panel_form(){
                        const vm = this;
                        setTimeout(function(){
                            vm.is_affiliate_form_loader = "1";                            
                        }, 400);
                    },                 
					'.$affiliatepress_vue_methods_data.'
				},
			});               
            app.use(ElementPlus, {
                locale: ElementPlusLocaleData,
            });

            app.mount("'.$affiliatepress_vue_root_element_id.'");            
            ';            

            $affiliatepress_script_data = " var app;  
			var is_script_loaded_$affiliatepress_vue_root_element_id_el = false;
            affiliatepress_beforeload_data = '';
            if( null != document.getElementById('$affiliatepress_vue_root_element_id_without_hash') ){
                affiliatepress_beforeload_data = document.getElementById('$affiliatepress_vue_root_element_id_without_hash').innerHTML;
            }
            window.addEventListener('DOMContentLoaded', function() {
                if( is_script_loaded_$affiliatepress_vue_root_element_id_el == false) {
                    is_script_loaded_$affiliatepress_vue_root_element_id_el = true;
                    ap_load_vue_shortcode_$affiliatepress_vue_root_element_id_el();
                }
            });
            window.addEventListener( 'elementor/popup/show', (event) => {
                let element = event.detail.instance.\$element[0].querySelector('.ap-review-container');
                if( 'undefined' != typeof element ){
                    document.getElementById('$affiliatepress_vue_root_element_id_without_hash').innerHTML = affiliatepress_beforeload_data;
                    ap_load_vue_shortcode_$affiliatepress_vue_root_element_id_el();
                }
            });
            function ap_load_vue_shortcode_$affiliatepress_vue_root_element_id_el(){
                {$affiliatepress_script_return_data}           
            }         
            ".$affiliatepress_load_panel_shortcode_data;            
            
            wp_add_inline_script('affiliatepress_element_js', $affiliatepress_script_data, 'after');

            return do_shortcode( $affiliatepress_content );                

        }


    }
}
global $affiliatepress_affiliate_panel;
$affiliatepress_affiliate_panel = new affiliatepress_affiliate_panel();
