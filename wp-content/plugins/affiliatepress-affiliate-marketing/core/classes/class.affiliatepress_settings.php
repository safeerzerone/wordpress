<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_settings') ) {
    class affiliatepress_settings Extends AffiliatePress_Core{
        
        var $affiliatepress_per_page_record;

        public $affiliatepress_sending_wp_mail;
        public $affiliatepress_ap_sent_test_wpmail_errors;
        public $affiliatepress_sending_wp_mail_upon_booking;

        function __construct(){
            
            $this->affiliatepress_per_page_record = 10;

            /**Function for get dynamic settings page data */
            add_action( 'admin_init', array( $this, 'affiliatepress_dynamic_setting_data_fields') );

            /* Dynamic Constant */
            add_filter('affiliatepress_settings_dynamic_constant_define',array($this,'affiliatepress_settings_dynamic_constant_define_func'),10,1);
            
            /* Dynamic Vue Fields */
            add_filter('affiliatepress_settings_dynamic_data_fields',array($this,'affiliatepress_settings_dynamic_data_fields_func'),10,1);

            /* Vue Load */
            add_action('affiliatepress_settings_dynamic_view_load', array( $this, 'affiliatepress_settings_dynamic_view_load_func' ), 10);

            /* Vue Method */
            add_filter('affiliatepress_settings_dynamic_vue_methods',array($this,'affiliatepress_settings_dynamic_vue_methods_func'),10,1);

            /* Get Affiliates */
            add_action('wp_ajax_affiliatepress_get_settings_details', array( $this, 'affiliatepress_get_settings_details' ));

            /* Save Affiliate Setting Data */
            add_action('wp_ajax_affiliatepress_save_settings_data', array( $this, 'affiliatepress_save_settings_data_func' ));


            /* Dynamic On Load Method */
            add_filter('affiliatepress_settings_dynamic_on_load_methods', array( $this, 'affiliatepress_settings_dynamic_on_load_methods_func' ), 10);

            /* WP_MAIL Test ajax request */
            add_action( 'wp_ajax_affiliatepress_send_test_wpmail_email', array( $this, 'affiliatepress_send_test_wpmail_email_func') );

            /* SMTP Test Mail Ajax Request */
            add_action('wp_ajax_affiliatepress_send_test_email', array( $this, 'affiliatepress_send_test_email_func' ));


            /* Function for add debug log */
            add_action('wp_ajax_affiliatepress_view_debug_log', array( $this, 'affiliatepress_view_debug_log_func' ), 10);

            /* Function for download debug log */
            add_action('wp_ajax_affiliatepress_download_log', array( $this, 'affiliatepess_download_log_func' ), 10);

            /* Function for clear debug log */
            add_action('wp_ajax_affiliatepress_clear_debug_log', array( $this, 'affiliatepress_clear_debug_log_func' ), 10);

            /* AffiliatePress debug log file */
            add_action('admin_init', array( $this, 'affiliatepress_debug_log_download_file' ));

            /** Reset Color & Font  */
            add_action('wp_ajax_ap_reset_appearance_color',array($this,'affiliatepress_ap_reset_appearance_color_func'));

            /**FUnction for add setup wizard section */
            add_action('affiliatepress_extra_affiliate_setting_section_html',array($this,'affiliatepress_extra_affiliate_setting_section_html_func'),14);

            /**Resetup Wizard */
            add_action('wp_ajax_affiliatepress_resetup_wizard',array($this,'affiliatepress_resetup_wizard_fun'));       
            
            /** Reset Color & Font  */
            add_action('wp_ajax_affiliatepress_get_page_url',array($this,'affiliatepress_get_page_url_func'));
        }

        /**
         * Function For Affiliate Wizard resetup
         *
         * @return void
         */
        function affiliatepress_resetup_wizard_fun(){
            global $wpdb ;

            $affiliatepress_check_authorization = $this->affiliatepress_ap_check_authentication('reset_wizard', true, 'ap_wp_nonce');
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error',  'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong',  'affiliatepress-affiliate-marketing');

            if( preg_match('/error/', $affiliatepress_check_authorization)){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request',  'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error',  'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

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

            update_option('affiliatepress_lite_wizard_complete', 0);
            update_option('affiliatepress_flush_rewrites',0);

            
            $response['variant'] = 'success';
            $response['title'] = esc_html__( 'Success', 'affiliatepress-affiliate-marketing');
            $response['msg'] =  esc_html__( 'AffiliatePress Wizard Reset', 'affiliatepress-affiliate-marketing');
            wp_send_json( $response );
            die;
        }

        /**
         * FUnction for add visit delete section
         *
         * @return void
        */
        function affiliatepress_extra_affiliate_setting_section_html_func(){
        ?>
            <div class="ap-gs__cb--item">
                <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row ap-wizard-redirect" :gutter="32">
                    <el-col :xs="22" :sm="22" :md="22" :lg="20" :xl="20" class="ap-gs__cb-item-left">
                        <el-row type="flex" class="ap-gs--tabs-fields-label">
                            <div class="ap-gs__cb--item-heading">
                                <?php esc_html_e('Setup Wizard', 'affiliatepress-affiliate-marketing'); ?>
                            </div>
                        </el-row>
                        <el-row type="flex" class="ap-gs--tabs-fields-description">
                            <div><?php esc_html_e('If you need to run the setup wizard again, click the Start button.', 'affiliatepress-affiliate-marketing'); ?></div>
                        </el-row>
                    </el-col>
                    <el-col :xs="2" :sm="2" :md="2" :lg="4" :xl="4" class="ap-gs__cb-item-right">				
                        <el-form-item>
                            <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Start the Setup Wizard', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                <span class="ap-redirect-btn"  @click="affiliatepress_resetup_wizard()"><?php do_action('affiliatepress_common_svg_code','redirect_icon'); ?></span>
                            </el-tooltip>
                        </el-form-item> 
                    </el-col>
                </el-row>
            </div>
            <div class="ap-settings-new-section"></div>    
        <?php 
        }


        
        /**
         * Set anonymous data cron
         *
         * @return void
         */
        function affiliatepress_set_anonymous_data_cron(){            
            wp_get_schedules();
            if ( ! wp_next_scheduled('affiliatepress_send_anonymous_data') ) {                
                wp_schedule_event( time(), 'weekly', 'affiliatepress_send_anonymous_data');
            }
        }

        /**
         * Clear debug logs
         *
         * @return json
        */
        function affiliatepress_clear_debug_log_func(){
            global $wpdb, $affiliatepress_tbl_ap_other_debug_logs, $affiliatepress_tbl_ap_commission_debug_logs ,$affiliatepress_tbl_ap_payout_debug_logs,$affiliatepress_tbl_ap_integrations_debug_logs;
            $response = array();
            $response['variant'] = 'error';
            $response['affiliates'] = '';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'clear_debug_payment_logs', true, 'ap_wp_nonce' );
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_settings')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }

            $affiliatepress_integration_debug_log_arr = $this->affiliatepress_get_integration_debug_log_arr();

            $affiliatepress_view_log_selector = ! empty($_REQUEST['affiliatepress_debug_log_selector']) ? sanitize_text_field($_REQUEST['affiliatepress_debug_log_selector']) : ''; // phpcs:ignore
            if (! empty($affiliatepress_view_log_selector) ) {

                if($affiliatepress_view_log_selector == 'commission_tracking_debug_logs'){
                    /* If data exists into debug log table then delete from that table. */
                    $wpdb->delete($affiliatepress_tbl_ap_commission_debug_logs, array( 'ap_commission_log_type' => $affiliatepress_view_log_selector ), array( '%s' ));// phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    $response['variant'] = 'success';
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Debug Logs Cleared Successfully.', 'affiliatepress-affiliate-marketing');
                }
                elseif($affiliatepress_view_log_selector == 'payout_tracking_debug_logs'){
                    /* If data exists into debug log table then delete from that table. */
                    $wpdb->delete($affiliatepress_tbl_ap_payout_debug_logs, array( 'ap_payout_log_type' => $affiliatepress_view_log_selector ), array( '%s' ));// phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    $response['variant'] = 'success';
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Debug Logs Cleared Successfully.', 'affiliatepress-affiliate-marketing');
                }
                elseif(!empty($affiliatepress_view_log_selector) && in_array($affiliatepress_view_log_selector,$affiliatepress_integration_debug_log_arr)){
                    /* If data exists into debug log table then delete from that table. */
                    $wpdb->delete($affiliatepress_tbl_ap_integrations_debug_logs, array( 'ap_integrations_log_type' => $affiliatepress_view_log_selector ), array( '%s' ));// phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    $response['variant'] = 'success';
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Debug Logs Cleared Successfully.', 'affiliatepress-affiliate-marketing');
                }
                else{
                    /* If data exists into debug log table then delete from that table. */
                    $wpdb->delete($affiliatepress_tbl_ap_other_debug_logs, array( 'ap_other_log_type' => $affiliatepress_view_log_selector ), array( '%s' ));// phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    $response['variant'] = 'success';
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Debug Logs Cleared Successfully.', 'affiliatepress-affiliate-marketing');
                }
            }

            $response = apply_filters('affiliatepress_modify_clear_debug_log_data', $response ,$affiliatepress_view_log_selector );

            echo wp_json_encode($response);
            exit();
        }

        /**
         * Download debug log files
         *
         * @return void
         */
        function affiliatepress_debug_log_download_file(){

            if (! empty($_REQUEST['affiliatepess_action']) && 'download_log' == sanitize_text_field($_REQUEST['affiliatepess_action']) ) { // phpcs:ignore

                $affiliatepress_filename = ! empty($_REQUEST['file']) ? basename(sanitize_file_name($_REQUEST['file'])) : ''; // phpcs:ignore 
                if (! empty($affiliatepress_filename) ) {
                    $affiliatepress_file_path = AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_filename);

                    $affiliatepress_allowexts = array( 'txt', 'zip' );

                    $affiliatepress_file_name_bpa = substr($affiliatepress_filename, 0, 3);

                    $affiliatepress_checkext = explode('.', $affiliatepress_filename);
                    $affiliatepress_ext      = strtolower($affiliatepress_checkext[ count($affiliatepress_checkext) - 1 ]);

                    if (! empty($affiliatepress_ext) && in_array($affiliatepress_ext, $affiliatepress_allowexts) && ! empty($affiliatepress_filename) && file_exists($affiliatepress_file_path) ) {
                        ignore_user_abort();
                        $affiliatepress_now = gmdate('D, d M Y H:i:s');
                        header('Expires: Tue, 03 Jul 2020 06:00:00 GMT');
                        header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
                        header("Last-Modified: {$affiliatepress_now} GMT");
                        header('Content-Type: application/force-download');
                        header('Content-Type: application/octet-stream');
                        header('Content-Type: application/download');
                        header("Content-Disposition: attachment;filename={$affiliatepress_filename}");
                        header('Content-Transfer-Encoding: binary');

                        readfile($affiliatepress_file_path);// phpcs:ignore

                        wp_delete_file($affiliatepress_file_path);// phpcs:ignore

                        $affiliatepress_txt_file_name = str_replace('.zip', '.txt', $affiliatepress_filename);
                        $affiliatepress_txt_file_path = AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_txt_file_name);
                        if (file_exists($affiliatepress_txt_file_path) ) {
                            wp_delete_file($affiliatepress_txt_file_path);// phpcs:ignore
                        }

                        die;
                    }
                }
            }
        }

        /**
         * Function for download log
         *
         * @return void
        */
        function affiliatepess_download_log_func(){

            global $wpdb, $affiliatepress_tbl_ap_other_debug_logs, $affiliatepress_tbl_ap_commission_debug_logs,$affiliatepress_tbl_ap_payout_debug_logs,$affiliatepress_tbl_ap_integrations_debug_logs;
            $response = array();
            $response['variant'] = 'error';
            $response['affiliates'] = '';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');
            

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'download_debug_payment_logs', true, 'ap_wp_nonce' );
            
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }
            
            if(!current_user_can('affiliatepress_settings')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }             

            $affiliatepress_perpage     = isset($_POST['perpage']) ? intval($_POST['perpage']) : 20; // phpcs:ignore WordPress.Security.NonceVerification
            $affiliatepress_currentpage = isset($_POST['currentpage']) ? intval($_POST['currentpage']) : 1; // phpcs:ignore WordPress.Security.NonceVerification
            $affiliatepress_offset      = ( ! empty($affiliatepress_currentpage) && $affiliatepress_currentpage > 1 ) ? ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage ) : 0;


            $affiliatepess_view_log_selector          = ! empty($_REQUEST['affiliatepess_debug_log_selector']) ? sanitize_text_field($_REQUEST['affiliatepess_debug_log_selector']) : ''; // phpcs:ignore
            $affiliatepess_selected_download_duration = ! empty($_REQUEST['affiliatepess_selected_download_duration']) ? sanitize_text_field($_REQUEST['affiliatepess_selected_download_duration']) : 'all'; // phpcs:ignore

            $affiliatepress_integration_debug_log_arr = $this->affiliatepress_get_integration_debug_log_arr();

            if($affiliatepess_view_log_selector == 'commission_tracking_debug_logs'){
                if(!empty($affiliatepess_view_log_selector) && !empty($affiliatepess_selected_download_duration) ) {

                    $affiliatepess_debug_payment_log_where_cond = '';
                    if (! empty($_REQUEST['affiliatepess_selected_download_custom_duration']) && $affiliatepess_selected_download_duration == 'custom' ) {// phpcs:ignore
                        $affiliatepess_start_date                   = !empty($_REQUEST['affiliatepess_selected_download_custom_duration'][0]) ? date('Y-m-d 00:00:00', strtotime(sanitize_text_field($_REQUEST['affiliatepess_selected_download_custom_duration'][0]))) : '';// phpcs:ignore
                        $affiliatepess_end_date                     = !empty($_REQUEST['affiliatepess_selected_download_custom_duration'][1]) ? date('Y-m-d 23:59:59', strtotime(sanitize_text_field($_REQUEST['affiliatepess_selected_download_custom_duration'][1]))) : '';// phpcs:ignore
                        $affiliatepess_debug_payment_log_where_cond = $wpdb->prepare(' AND (ap_commission_log_added_date >= %s AND ap_commission_log_added_date <= %s)', $affiliatepess_start_date, $affiliatepess_end_date);
                    } elseif (! empty($affiliatepess_selected_download_duration) && $affiliatepess_selected_download_duration != 'custom' && $affiliatepess_selected_download_duration != 'all' ) {
                        $affiliatepess_last_selected_days           = date('Y-m-d', strtotime('-' . $affiliatepess_selected_download_duration . ' days'));// phpcs:ignore
                        $affiliatepess_debug_payment_log_where_cond = $wpdb->prepare(' AND (ap_commission_log_added_date >= %s)', $affiliatepess_last_selected_days);
                    }
    

                    $affiliatepress_tbl_ap_commission_debug_logs_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_commission_debug_logs); 

                    $affiliatepess_debug_payment_log_query = "SELECT * FROM {$affiliatepress_tbl_ap_commission_debug_logs_temp} WHERE 1 = 1 " . $affiliatepess_debug_payment_log_where_cond . " ORDER BY ap_commission_log_id DESC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_commission_debug_logs_temp is table name defined globally. False Positive alarm                 

                    $affiliatepess_payment_debug_log_data = $wpdb->get_results($affiliatepess_debug_payment_log_query, ARRAY_A); //phpcs:ignore                    
    
                    $affiliatepess_download_data = wp_json_encode($affiliatepess_payment_debug_log_data);

                    if (! function_exists('WP_Filesystem') ) {
                        include_once ABSPATH . 'wp-admin/includes/file.php';
                    }
                    WP_Filesystem();
                    global $wp_filesystem;
    
                    $affiliatepesss_debug_log_file_name = 'affiliatepess_debug_logs_' . $affiliatepess_view_log_selector . '_' . $affiliatepess_selected_download_duration;
                    $result                            = $wp_filesystem->put_contents(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.txt', $affiliatepess_download_data, 0777);
    
                    $affiliatepress_debug_log_file_name = '';
    
                    if (class_exists('ZipArchive') ) {
                        $affiliatepress_zip = new ZipArchive();
                        $affiliatepress_zip->open(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.zip', ZipArchive::CREATE);
                        $affiliatepress_zip->addFile(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.txt', $affiliatepesss_debug_log_file_name . '.txt');
                        $affiliatepress_zip->close();
    
                        $affiliatepess_download_url = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepesss_debug_log_file_name . '.zip';
                        $affiliatepress_debug_log_file_name       = $affiliatepesss_debug_log_file_name . '.zip';
                    } else {
                        $affiliatepess_download_url = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepesss_debug_log_file_name . '.txt';
                        $affiliatepress_debug_log_file_name       = $affiliatepesss_debug_log_file_name . '.txt';
                    }
                    $response['variant']    = 'success';
                    $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']        = esc_html__('log download successfully', 'affiliatepress-affiliate-marketing');
                    $response['url'] = admin_url('admin.php?page=affiliatepress&module=settings&affiliatepess_action=download_log&file=' . $affiliatepress_debug_log_file_name);
                    echo wp_json_encode($response);
                    exit();

                }
            }
            elseif($affiliatepess_view_log_selector == 'payout_tracking_debug_logs'){
                if(!empty($affiliatepess_view_log_selector) && !empty($affiliatepess_selected_download_duration) ) {

                    $affiliatepess_debug_payment_log_where_cond = '';
                    if (! empty($_REQUEST['affiliatepess_selected_download_custom_duration']) && $affiliatepess_selected_download_duration == 'custom' ) {// phpcs:ignore
                        $affiliatepess_start_date                   = !empty($_REQUEST['affiliatepess_selected_download_custom_duration'][0]) ? date('Y-m-d 00:00:00', strtotime(sanitize_text_field($_REQUEST['affiliatepess_selected_download_custom_duration'][0]))) : '';// phpcs:ignore
                        $affiliatepess_end_date                     = !empty($_REQUEST['affiliatepess_selected_download_custom_duration'][1]) ? date('Y-m-d 23:59:59', strtotime(sanitize_text_field($_REQUEST['affiliatepess_selected_download_custom_duration'][1]))) : '';// phpcs:ignore
                        $affiliatepess_debug_payment_log_where_cond = $wpdb->prepare(' AND (ap_payout_log_added_date >= %s AND ap_payout_log_added_date <= %s)', $affiliatepess_start_date, $affiliatepess_end_date);
                    } elseif (! empty($affiliatepess_selected_download_duration) && $affiliatepess_selected_download_duration != 'custom' && $affiliatepess_selected_download_duration != 'all' ) {
                        $affiliatepess_last_selected_days           = date('Y-m-d', strtotime('-' . $affiliatepess_selected_download_duration . ' days'));// phpcs:ignore
                        $affiliatepess_debug_payment_log_where_cond = $wpdb->prepare(' AND (ap_payout_log_added_date >= %s)', $affiliatepess_last_selected_days);
                    }
                    
                    $affiliatepress_tbl_ap_payout_debug_logs_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_payout_debug_logs);

                    $affiliatepess_debug_payment_log_query = "SELECT * FROM {$affiliatepress_tbl_ap_payout_debug_logs_temp} WHERE 1 = 1  $affiliatepess_debug_payment_log_where_cond  ORDER BY ap_payout_log_id DESC"; // phpcs:ignore 
                        
                    $affiliatepess_payment_debug_log_data = $wpdb->get_results($affiliatepess_debug_payment_log_query, ARRAY_A); //phpcs:ignore                    
    
                    $affiliatepess_download_data = wp_json_encode($affiliatepess_payment_debug_log_data);

                    if (! function_exists('WP_Filesystem') ) {
                        include_once ABSPATH . 'wp-admin/includes/file.php';
                    }
                    WP_Filesystem();
                    global $wp_filesystem;
    
                    $affiliatepesss_debug_log_file_name = 'affiliatepess_debug_logs_' . $affiliatepess_view_log_selector . '_' . $affiliatepess_selected_download_duration;
                    $result                            = $wp_filesystem->put_contents(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.txt', $affiliatepess_download_data, 0777);
    
                    $affiliatepress_debug_log_file_name = '';
    
                    if (class_exists('ZipArchive') ) {
                        $affiliatepress_zip = new ZipArchive();
                        $affiliatepress_zip->open(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.zip', ZipArchive::CREATE);
                        $affiliatepress_zip->addFile(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.txt', $affiliatepesss_debug_log_file_name . '.txt');
                        $affiliatepress_zip->close();
    
                        $affiliatepess_download_url = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepesss_debug_log_file_name . '.zip';
                        $affiliatepress_debug_log_file_name       = $affiliatepesss_debug_log_file_name . '.zip';
                    } else {
                        $affiliatepess_download_url = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepesss_debug_log_file_name . '.txt';
                        $affiliatepress_debug_log_file_name       = $affiliatepesss_debug_log_file_name . '.txt';
                    }
                    $response['variant']    = 'success';
                    $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']        = esc_html__('log download successfully', 'affiliatepress-affiliate-marketing');
                    $response['url'] = admin_url('admin.php?page=affiliatepress&module=settings&affiliatepess_action=download_log&file=' . $affiliatepress_debug_log_file_name);
                    echo wp_json_encode($response);
                    exit();

                }
            }
            elseif(!empty($affiliatepess_view_log_selector) && in_array($affiliatepess_view_log_selector,$affiliatepress_integration_debug_log_arr)){
                if(!empty($affiliatepess_view_log_selector) && !empty($affiliatepess_selected_download_duration) ) {

                    $affiliatepess_debug_payment_log_where_cond = '';
                    if (! empty($_REQUEST['affiliatepess_selected_download_custom_duration']) && $affiliatepess_selected_download_duration == 'custom' ) {// phpcs:ignore
                        $affiliatepess_start_date                   = !empty($_REQUEST['affiliatepess_selected_download_custom_duration'][0]) ? date('Y-m-d 00:00:00', strtotime(sanitize_text_field($_REQUEST['affiliatepess_selected_download_custom_duration'][0]))) : '';// phpcs:ignore
                        $affiliatepess_end_date                     = !empty($_REQUEST['affiliatepess_selected_download_custom_duration'][1]) ? date('Y-m-d 23:59:59', strtotime(sanitize_text_field($_REQUEST['affiliatepess_selected_download_custom_duration'][1]))) : '';// phpcs:ignore
                        $affiliatepess_debug_payment_log_where_cond = $wpdb->prepare(' AND (ap_integrations_log_added_date >= %s AND ap_integrations_log_added_date <= %s)', $affiliatepess_start_date, $affiliatepess_end_date);
                    } elseif (! empty($affiliatepess_selected_download_duration) && $affiliatepess_selected_download_duration != 'custom' && $affiliatepess_selected_download_duration != 'all' ) {
                        $affiliatepess_last_selected_days           = date('Y-m-d', strtotime('-' . $affiliatepess_selected_download_duration . ' days'));// phpcs:ignore
                        $affiliatepess_debug_payment_log_where_cond = $wpdb->prepare(' AND (ap_integrations_log_added_date >= %s)', $affiliatepess_last_selected_days);
                    }
                    
                    $affiliatepress_tbl_ap_integrations_debug_logs_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_integrations_debug_logs);

                    $affiliatepess_debug_payment_log_query = $wpdb->prepare("SELECT * FROM {$affiliatepress_tbl_ap_integrations_debug_logs_temp} WHERE ap_integrations_log_type = %s $affiliatepess_debug_payment_log_where_cond  ORDER BY ap_integrations_log_id DESC",$affiliatepess_view_log_selector); // phpcs:ignore
                        
                    $affiliatepess_payment_debug_log_data = $wpdb->get_results($affiliatepess_debug_payment_log_query, ARRAY_A); //phpcs:ignore  
    
                    $affiliatepess_download_data = wp_json_encode($affiliatepess_payment_debug_log_data);

                    if (! function_exists('WP_Filesystem') ) {
                        include_once ABSPATH . 'wp-admin/includes/file.php';
                    }
                    WP_Filesystem();
                    global $wp_filesystem;
    
                    $affiliatepesss_debug_log_file_name = 'affiliatepess_debug_logs_' . $affiliatepess_view_log_selector . '_' . $affiliatepess_selected_download_duration;
                    $result                            = $wp_filesystem->put_contents(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.txt', $affiliatepess_download_data, 0777);
    
                    $affiliatepress_debug_log_file_name = '';
    
                    if (class_exists('ZipArchive') ) {
                        $affiliatepress_zip = new ZipArchive();
                        $affiliatepress_zip->open(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.zip', ZipArchive::CREATE);
                        $affiliatepress_zip->addFile(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.txt', $affiliatepesss_debug_log_file_name . '.txt');
                        $affiliatepress_zip->close();
    
                        $affiliatepess_download_url = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepesss_debug_log_file_name . '.zip';
                        $affiliatepress_debug_log_file_name       = $affiliatepesss_debug_log_file_name . '.zip';
                    } else {
                        $affiliatepess_download_url = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepesss_debug_log_file_name . '.txt';
                        $affiliatepress_debug_log_file_name       = $affiliatepesss_debug_log_file_name . '.txt';
                    }
                    $response['variant']    = 'success';
                    $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']        = esc_html__('log download successfully', 'affiliatepress-affiliate-marketing');
                    $response['url'] = admin_url('admin.php?page=affiliatepress&module=settings&affiliatepess_action=download_log&file=' . $affiliatepress_debug_log_file_name);
                    echo wp_json_encode($response);
                    exit();

                }
            }
            else{
                if (! empty($affiliatepess_view_log_selector) && ! empty($affiliatepess_selected_download_duration) ) {

                    $affiliatepess_debug_payment_log_where_cond = $wpdb->prepare(' WHERE ap_other_log_type = %s ', $affiliatepess_view_log_selector);
                    if (! empty($_REQUEST['affiliatepess_selected_download_custom_duration']) && $affiliatepess_selected_download_duration == 'custom' ) { // phpcs:ignore
                        $affiliatepess_start_date                   = !empty($_REQUEST['affiliatepess_selected_download_custom_duration'][0]) ? date('Y-m-d 00:00:00', strtotime(sanitize_text_field($_REQUEST['affiliatepess_selected_download_custom_duration'][0]))) : '';// phpcs:ignore
                        $affiliatepess_end_date                     = !empty($_REQUEST['affiliatepess_selected_download_custom_duration'][1]) ? date('Y-m-d 23:59:59', strtotime(sanitize_text_field($_REQUEST['affiliatepess_selected_download_custom_duration'][1]))) : '';// phpcs:ignore
                        $affiliatepess_debug_payment_log_where_cond = $wpdb->prepare(' AND (ap_other_log_added_date >= %s AND ap_other_log_added_date <= %s)', $affiliatepess_start_date, $affiliatepess_end_date);
                    } elseif (! empty($affiliatepess_selected_download_duration) && $affiliatepess_selected_download_duration != 'custom' && $affiliatepess_selected_download_duration != 'all' ) {
                        $affiliatepess_last_selected_days           = date('Y-m-d', strtotime('-' . $affiliatepess_selected_download_duration . ' days'));// phpcs:ignore
                        $affiliatepess_debug_payment_log_where_cond = $wpdb->prepare(' AND (ap_other_log_added_date >= %s)', $affiliatepess_last_selected_days);
                    }
    
                    $affiliatepress_tbl_ap_other_debug_logs_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_other_debug_logs);

                    $affiliatepess_debug_payment_log_query = "SELECT * FROM {$affiliatepress_tbl_ap_other_debug_logs_temp}   $affiliatepess_debug_payment_log_where_cond  ORDER BY ap_other_log_id DESC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_other_debug_logs_temp is table name defined globally. False Positive alarm
    
                    $affiliatepess_payment_debug_log_data = $wpdb->get_results($affiliatepess_debug_payment_log_query, ARRAY_A); //phpcs:ignore
    
                    $affiliatepess_download_data = wp_json_encode($affiliatepess_payment_debug_log_data);
    
                    if (! function_exists('WP_Filesystem') ) {
                        include_once ABSPATH . 'wp-admin/includes/file.php';
                    }
                    WP_Filesystem();
                    global $wp_filesystem;
    
                    $affiliatepesss_debug_log_file_name = 'affiliatepess_debug_logs_' . $affiliatepess_view_log_selector . '_' . $affiliatepess_selected_download_duration;
                    $result                            = $wp_filesystem->put_contents(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.txt', $affiliatepess_download_data, 0777);
    
                    $affiliatepress_debug_log_file_name = '';
    
                    if (class_exists('ZipArchive') ) {
                        $affiliatepress_zip = new ZipArchive();
                        $affiliatepress_zip->open(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.zip', ZipArchive::CREATE);
                        $affiliatepress_zip->addFile(AFFILIATEPRESS_UPLOAD_DIR . '/' . $affiliatepesss_debug_log_file_name . '.txt', $affiliatepesss_debug_log_file_name . '.txt');
                        $affiliatepress_zip->close();
    
                        $affiliatepess_download_url = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepesss_debug_log_file_name . '.zip';
                        $affiliatepress_debug_log_file_name       = $affiliatepesss_debug_log_file_name . '.zip';
                    } else {
                        $affiliatepess_download_url = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepesss_debug_log_file_name . '.txt';
                        $affiliatepress_debug_log_file_name       = $affiliatepesss_debug_log_file_name . '.txt';
                    }
                    $response['variant']    = 'success';
                    $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']        = esc_html__('log download successfully', 'affiliatepress-affiliate-marketing');
                    $response['url'] = admin_url('admin.php?page=affiliatepress&module=settings&affiliatepess_action=download_log&file=' . $affiliatepress_debug_log_file_name);
                }
    
            }

            $response = apply_filters('affiliatepress_modify_download_debug_log_data', $response ,$affiliatepess_view_log_selector ,$affiliatepess_selected_download_duration);

            echo wp_json_encode($response);
            exit();            


        }


        /**
         * View debug logs
         *
         * @return json
         */
        function affiliatepress_view_debug_log_func(){

            global $wpdb, $affiliatepress_tbl_ap_other_debug_logs, $affiliatepress_tbl_ap_commission_debug_logs,$affiliatepress_tbl_ap_payout_debug_logs,$affiliatepress_tbl_ap_integrations_debug_logs;
            $response = array();
            $response['variant'] = 'error';
            $response['affiliates'] = '';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');
            


            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'view_debug_payment_logs', true, 'ap_wp_nonce' );
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_settings')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            } 

            $affiliatepress_perpage     = isset($_POST['perpage']) ? intval($_POST['perpage']) : 20; // phpcs:ignore 
            $affiliatepress_currentpage = isset($_POST['currentpage']) ? intval($_POST['currentpage']) : 1; // phpcs:ignore
            $affiliatepress_offset      = ( ! empty($affiliatepress_currentpage) && $affiliatepress_currentpage > 1 ) ? ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage ) : 0;

            $affiliatepress_payment_debug_log_data = array();
            $affiliatepress_total_payment_debug_logs = 0;
            $affiliatepress_debug_log_selector = isset($_REQUEST['affiliatepress_debug_log_selector']) ? sanitize_text_field($_REQUEST['affiliatepress_debug_log_selector']) : ''; // phpcs:ignore 

            $affiliatepress_integration_debug_log_arr = $this->affiliatepress_get_integration_debug_log_arr();

            if (!empty($affiliatepress_debug_log_selector) && $affiliatepress_debug_log_selector == 'commission_tracking_debug_logs'){

                $affiliatepress_tbl_ap_commission_debug_logs_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_commission_debug_logs);

                $affiliatepress_total_payment_debug_logs   = $wpdb->get_var( "SELECT count(ap_commission_log_id) FROM {$affiliatepress_tbl_ap_commission_debug_logs_temp} ORDER BY ap_commission_log_id DESC"); //phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_commission_debug_logs is a table name. false alarm
                $affiliatepress_payment_debug_logs         = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$affiliatepress_tbl_ap_commission_debug_logs_temp} Where 1 = 1 ORDER BY ap_commission_log_id DESC LIMIT %d, %d", $affiliatepress_offset , $affiliatepress_perpage ), ARRAY_A); //phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_commission_debug_logs is a table name. false alarm                          

                $affiliatepress_payment_debug_log_data = array();                
                if (! empty($affiliatepress_payment_debug_logs) ) {
                    $affiliatepress_date_format = get_option('date_format');
                    foreach ( $affiliatepress_payment_debug_logs as $affiliatepress_payment_debug_log_key => $affiliatepress_payment_debug_log_val ) {

                        $affiliatepress_commission_log_id         = !empty($affiliatepress_payment_debug_log_val['ap_commission_log_id']) ? intval($affiliatepress_payment_debug_log_val['ap_commission_log_id']) : '';
                        $affiliatepress_commission_log_event      = !empty($affiliatepress_payment_debug_log_val['ap_commission_log_event']) ? esc_html($affiliatepress_payment_debug_log_val['ap_commission_log_event']) : '';
                        $affiliatepress_commission_log_raw_data   = !empty($affiliatepress_payment_debug_log_val['ap_commission_log_raw_data']) ? $affiliatepress_payment_debug_log_val['ap_commission_log_raw_data'] : '';
                        $affiliatepress_commission_log_added_date = !empty($affiliatepress_payment_debug_log_val['ap_commission_log_added_date']) ? esc_html($affiliatepress_payment_debug_log_val['ap_commission_log_added_date']) : '';

                        $affiliatepress_payment_debug_log_data[] = array(
                         'payment_debug_log_id'         => $affiliatepress_commission_log_id ,
                         'payment_debug_log_name'       => $affiliatepress_commission_log_event,
                         'payment_debug_log_data'       => stripslashes_deep($affiliatepress_commission_log_raw_data),
                         'payment_debug_log_added_date' => date($affiliatepress_date_format, strtotime($affiliatepress_commission_log_added_date)),// phpcs:ignore
                        );

                    }
                }                


            }
            elseif (!empty($affiliatepress_debug_log_selector) && $affiliatepress_debug_log_selector == 'payout_tracking_debug_logs'){

                $affiliatepress_tbl_ap_payout_debug_logs_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_payout_debug_logs);

                $affiliatepress_total_payment_debug_logs   = $wpdb->get_var("SELECT count(ap_payout_log_id) FROM {$affiliatepress_tbl_ap_payout_debug_logs_temp} ORDER BY ap_payout_log_id DESC"); //phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_payout_debug_logs_temp is already prepare & it's table name. false alarm
                $affiliatepress_payment_debug_logs         = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$affiliatepress_tbl_ap_payout_debug_logs_temp} Where 1 = 1 ORDER BY ap_payout_log_id DESC LIMIT %d, %d", $affiliatepress_offset, $affiliatepress_perpage ), ARRAY_A); //phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_payout_debug_logs_temp is already prepare & it's table name. false alarm
            
                $affiliatepress_payment_debug_log_data = array();                
                if (! empty($affiliatepress_payment_debug_logs) ) {
                    $affiliatepress_date_format = get_option('date_format');
                    foreach ( $affiliatepress_payment_debug_logs as $affiliatepress_payment_debug_log_key => $affiliatepress_payment_debug_log_val ) {

                        $affiliatepress_payout_log_id         = !empty($affiliatepress_payment_debug_log_val['ap_payout_log_id']) ? intval($affiliatepress_payment_debug_log_val['ap_payout_log_id']) : '';
                        $affiliatepress_payout_log_event      = !empty($affiliatepress_payment_debug_log_val['ap_payout_log_event']) ? esc_html($affiliatepress_payment_debug_log_val['ap_payout_log_event']) : '';
                        $affiliatepress_payout_log_raw_data   = !empty($affiliatepress_payment_debug_log_val['ap_payout_log_raw_data']) ? $affiliatepress_payment_debug_log_val['ap_payout_log_raw_data'] : '';
                        $affiliatepress_payout_log_added_date = !empty($affiliatepress_payment_debug_log_val['ap_payout_log_added_date']) ? esc_html($affiliatepress_payment_debug_log_val['ap_payout_log_added_date']) : '';

                        $affiliatepress_payment_debug_log_data[] = array(
                         'payment_debug_log_id'         => $affiliatepress_payout_log_id ,
                         'payment_debug_log_name'       => $affiliatepress_payout_log_event,
                         'payment_debug_log_data'       => stripslashes_deep($affiliatepress_payout_log_raw_data),
                         'payment_debug_log_added_date' => date($affiliatepress_date_format, strtotime($affiliatepress_payout_log_added_date)),// phpcs:ignore
                        );

                    }
                }                


            }
            elseif (!empty($affiliatepress_debug_log_selector) && in_array($affiliatepress_debug_log_selector,$affiliatepress_integration_debug_log_arr)){

                $affiliatepress_tbl_ap_integrations_debug_logs_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_integrations_debug_logs);

                $affiliatepress_total_payment_debug_logs   = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ap_integrations_log_id) FROM {$affiliatepress_tbl_ap_integrations_debug_logs_temp} WHERE ap_integrations_log_type = %s ORDER BY ap_integrations_log_id DESC",$affiliatepress_debug_log_selector)); //phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_integrations_debug_logs_temp is already prepare & it's table name. false alarm
                
                $affiliatepress_payment_debug_logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$affiliatepress_tbl_ap_integrations_debug_logs_temp} WHERE ap_integrations_log_type = %s ORDER BY ap_integrations_log_id DESC LIMIT %d, %d",$affiliatepress_debug_log_selector,$affiliatepress_offset,$affiliatepress_perpage),ARRAY_A); //phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_integrations_debug_logs_temp is already prepare & it's table name. false alarm
        
                $affiliatepress_payment_debug_log_data = array();                
                if (! empty($affiliatepress_payment_debug_logs) ) {
                    $affiliatepress_date_format = get_option('date_format');
                    foreach ( $affiliatepress_payment_debug_logs as $affiliatepress_payment_debug_log_key => $affiliatepress_payment_debug_log_val ) {

                        $affiliatepress_payout_log_id         = !empty($affiliatepress_payment_debug_log_val['ap_integrations_log_id']) ? intval($affiliatepress_payment_debug_log_val['ap_integrations_log_id']) : '';
                        $affiliatepress_payout_log_event      = !empty($affiliatepress_payment_debug_log_val['ap_integrations_log_event']) ? esc_html($affiliatepress_payment_debug_log_val['ap_integrations_log_event']) : '';
                        $affiliatepress_payout_log_raw_data   = !empty($affiliatepress_payment_debug_log_val['ap_integrations_log_raw_data']) ? $affiliatepress_payment_debug_log_val['ap_integrations_log_raw_data'] : '';
                        $affiliatepress_payout_log_added_date = !empty($affiliatepress_payment_debug_log_val['ap_integrations_log_added_date']) ? esc_html($affiliatepress_payment_debug_log_val['ap_integrations_log_added_date']) : '';

                        $affiliatepress_payment_debug_log_data[] = array(
                         'payment_debug_log_id'         => $affiliatepress_payout_log_id ,
                         'payment_debug_log_name'       => $affiliatepress_payout_log_event,
                         'payment_debug_log_data'       => stripslashes_deep($affiliatepress_payout_log_raw_data),
                         'payment_debug_log_added_date' => date($affiliatepress_date_format, strtotime($affiliatepress_payout_log_added_date)),// phpcs:ignore
                        );

                    }
                }                

            }
            else{
                if (! empty($affiliatepress_debug_log_selector) ) {
                    
                    $affiliatepress_tbl_ap_other_debug_logs_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_other_debug_logs); 

                    $affiliatepress_total_payment_debug_logs   = $wpdb->get_var( $wpdb->prepare( "SELECT count(ap_other_log_id) FROM {$affiliatepress_tbl_ap_other_debug_logs_temp} WHERE ap_other_log_type = %s ORDER BY ap_other_log_id DESC", $affiliatepress_debug_log_selector )); //phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason $affiliatepress_tbl_ap_other_debug_logs is table name & already prepare using affiliatepress_tablename_prepare function. false alarm

                    $affiliatepress_payment_debug_logs         = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$affiliatepress_tbl_ap_other_debug_logs_temp} WHERE ap_other_log_type = %s ORDER BY ap_other_log_id DESC LIMIT %d, %d", $affiliatepress_debug_log_selector, $affiliatepress_offset , $affiliatepress_perpage ), ARRAY_A); //phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason $affiliatepress_tbl_ap_other_debug_logs is table name & already prepare by affiliatepress_tablename_prepare function. false alarm
                                        
                    $affiliatepress_payment_debug_log_data = array();
                    if (! empty($affiliatepress_payment_debug_logs) ) {
                        $affiliatepress_date_format = get_option('date_format');
                        foreach ( $affiliatepress_payment_debug_logs as $affiliatepress_payment_debug_log_key => $affiliatepress_payment_debug_log_val ) {
    
                            $affiliatepress_other_log_id         = ! empty($affiliatepress_payment_debug_log_val['ap_other_log_id']) ? intval($affiliatepress_payment_debug_log_val['ap_other_log_id']) : '';
                            $affiliatepress_other_log_event      = ! empty($affiliatepress_payment_debug_log_val['ap_other_log_event']) ? esc_html($affiliatepress_payment_debug_log_val['ap_other_log_event']) : '';
                            $affiliatepress_other_log_raw_data   = ! empty($affiliatepress_payment_debug_log_val['ap_other_log_raw_data']) ? $affiliatepress_payment_debug_log_val['ap_other_log_raw_data'] : '';
                            $affiliatepress_other_log_added_date = ! empty($affiliatepress_payment_debug_log_val['ap_other_log_added_date']) ? esc_html($affiliatepress_payment_debug_log_val['ap_other_log_added_date']) : '';
    
                            $affiliatepress_payment_debug_log_data[] = array(
                             'payment_debug_log_id'         => $affiliatepress_other_log_id,
                             'payment_debug_log_name'       => $affiliatepress_other_log_event,
                             'payment_debug_log_data'       => stripslashes_deep($affiliatepress_other_log_raw_data),
                             'payment_debug_log_added_date' => date($affiliatepress_date_format, strtotime($affiliatepress_other_log_added_date)),// phpcs:ignore
                            );
                        }
                    }
                }
            }
            $affiliatepress_data['items'] = $affiliatepress_payment_debug_log_data;
            $affiliatepress_data['total'] = $affiliatepress_total_payment_debug_logs;

            // Modify debug logs data
            $affiliatepress_data = apply_filters('affiliatepress_modify_debug_log_data', $affiliatepress_data ,$affiliatepress_debug_log_selector ,$affiliatepress_perpage , $affiliatepress_offset);

            wp_send_json($affiliatepress_data);
            exit;
        }        
        
        /**
         * Function for integration debug log
         *
         * @return array
        */
        function affiliatepress_get_integration_debug_log_arr(){
            $affiliatepress_integration_log_arr = array();
			$affiliatepress_integration_log_arr = apply_filters('affiliatepress_integration_debug_log_arr_filter',$affiliatepress_integration_log_arr);
			return $affiliatepress_integration_log_arr;		
        }
        
        /**
         * Function for send test SMTP email
         *
         * @return json
        */
        function affiliatepress_send_test_email_func(){
             
            global $affiliatepress_email_notifications;

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'send_test_mail', true, 'ap_wp_nonce' );            
            $response = array();
            $response['variant'] = 'error';
            $response['affiliates'] = '';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_settings')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }

            if (! empty($_REQUEST['notification_formdata']) ) {// phpcs:ignore

                $affiliatepress_smtp_host                = ! empty($_REQUEST['notification_formdata']['smtp_host']) ? sanitize_text_field($_REQUEST['notification_formdata']['smtp_host']) : '';// phpcs:ignore
                $affiliatepress_smtp_port                = ! empty($_REQUEST['notification_formdata']['smtp_port']) ? sanitize_text_field($_REQUEST['notification_formdata']['smtp_port']) : '';// phpcs:ignore
                $affiliatepress_smtp_secure              = ! empty($_REQUEST['notification_formdata']['smtp_secure']) ? sanitize_text_field($_REQUEST['notification_formdata']['smtp_secure']) : 'Disabled';// phpcs:ignore
                $affiliatepress_smtp_username            = ! empty($_REQUEST['notification_formdata']['smtp_username']) ? sanitize_text_field($_REQUEST['notification_formdata']['smtp_username']) : '';// phpcs:ignore
                $affiliatepress_smtp_password            = ! empty($_REQUEST['notification_formdata']['smtp_password']) ? stripslashes_deep($_REQUEST['notification_formdata']['smtp_password']) : ''; // phpcs:ignore 
                $affiliatepress_smtp_sender_name         = ! empty($_REQUEST['notification_formdata']['sender_name']) ? sanitize_text_field( stripslashes_deep( $_REQUEST['notification_formdata']['sender_name'])) : '';// phpcs:ignore
                $affiliatepress_smtp_sender_email        = ! empty($_REQUEST['notification_formdata']['sender_email']) ? sanitize_email($_REQUEST['notification_formdata']['sender_email']) : '';// phpcs:ignore
                $affiliatepress_smtp_test_receiver_email = ! empty($_REQUEST['notification_test_mail_formdata']['smtp_test_receiver_email']) ? sanitize_email($_REQUEST['notification_test_mail_formdata']['smtp_test_receiver_email']) : '';// phpcs:ignore
                $affiliatepress_smtp_test_msg            = ! empty($_REQUEST['notification_test_mail_formdata']['smtp_test_msg']) ? sanitize_text_field($_REQUEST['notification_test_mail_formdata']['smtp_test_msg']) : '';// phpcs:ignore

                $affiliatepress_email_res = $affiliatepress_email_notifications->affiliatepress_send_test_email_notification($affiliatepress_smtp_host, $affiliatepress_smtp_port, $affiliatepress_smtp_secure, $affiliatepress_smtp_username, $affiliatepress_smtp_password, $affiliatepress_smtp_test_receiver_email, $affiliatepress_smtp_test_msg, $affiliatepress_smtp_sender_email, $affiliatepress_smtp_sender_name);
            }

            echo wp_json_encode($response);
            exit();            

        }


        /**
         * Send test email notification for WordPress default configuration
         *
         * @return json
         */
        function affiliatepress_send_test_wpmail_email_func(){
            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'send_test_mail', true, 'ap_wp_nonce' );            
            $response = array();
            $response['variant'] = 'error';
            $response['affiliates'] = '';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_settings')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }

            if (! empty($_POST['notification_formdata']) ) {// phpcs:ignore

                $wpmail_test_receiver_email      = ! empty($_POST['notification_test_mail_formdata']['wpmail_test_receiver_email']) ? sanitize_email($_POST['notification_test_mail_formdata']['wpmail_test_receiver_email']) : '';// phpcs:ignore
                $wpmail_test_msg                 = ! empty($_POST['notification_test_mail_formdata']['wpmail_test_msg']) ? sanitize_text_field($_POST['notification_test_mail_formdata']['wpmail_test_msg']) : '';// phpcs:ignore

                $affiliatepress_from_email = !empty( $_REQUEST['notification_formdata']['sender_email'] ) ? sanitize_email( $_REQUEST['notification_formdata']['sender_email'] ) : get_option( 'admin_email' );// phpcs:ignore
                $affiliatepress_from_name = !empty(  $_REQUEST['notification_formdata']['send_name'] ) ? sanitize_text_field( $_REQUEST['notification_formdata']['send_name'] ) : get_option( 'blogname' );// phpcs:ignore

                $affiliatepress_email_header_data  = 'From: ' . $affiliatepress_from_name . '<' . $affiliatepress_from_email . "> \r\n";
                $affiliatepress_email_header_data .= "Content-Type: text/html; charset=UTF-8\r\n";
               
                $this->affiliatepress_sending_wp_mail = true;

                $wpmail_test_msg_subject = esc_html__( ' Test AffiliatePress WordPress default mail', 'affiliatepress-affiliate-marketing');
                $return = wp_mail( $wpmail_test_receiver_email, $wpmail_test_msg_subject, $wpmail_test_msg, $affiliatepress_email_header_data );
                $this->affiliatepress_sending_wp_mail = false;
                if( !empty( $this->affiliatepress_ap_sent_test_wpmail_errors ) ){
                    $response = array(
                        'is_mail_sent' => 0,
                        'msg'          => $this->affiliatepress_ap_sent_test_wpmail_errors,
                        'variant'      => 'error',
                        'title'        => esc_html__('Error', 'affiliatepress-affiliate-marketing'),
                    );
                } else {
                    $response = array(
                        'is_mail_sent' => 1,
                        'msg'          => esc_html__('Test Email Sent Successfully', 'affiliatepress-affiliate-marketing'),
                        'variant'      => 'success',
                        'title'        => esc_html__('Success', 'affiliatepress-affiliate-marketing'),
                    );
                }                

            }

            echo wp_json_encode($response);
            exit();
        }

                
        /**
         * Function for get dynamic settings page data
         *
         * @return void
         */
        function affiliatepress_dynamic_setting_data_fields(){
            
            global $affiliatepress_global_options,$affiliatepress_dynamic_setting_data_fields, $affiliatepress_affiliates, $AffiliatePress,$affiliatepress_max_tracking_cookie_days;

            $affiliatepress_options                    = $affiliatepress_global_options->affiliatepress_global_options();
            
            $affiliatepress_countries_currency_details = json_decode($affiliatepress_options['countries_json_details'],true);                    

            $affiliatepress_all_currency_with_code = array();
            if(!empty($affiliatepress_countries_currency_details)){
                foreach($affiliatepress_countries_currency_details as $affiliatepress_currency_detail){
                    $affiliatepress_all_currency_with_code[$affiliatepress_currency_detail['code']] = $affiliatepress_currency_detail;
                }
            }

            $affiliatepress_inherit_fonts_list = array('Inherit Fonts',);
            $affiliatepress_default_fonts_list = $affiliatepress_global_options->affiliatepress_get_default_fonts();
            $affiliatepress_google_fonts_list  = $affiliatepress_global_options->affiliatepress_get_google_fonts();
            $affiliatepress_fonts_list         = array(
                array(
                    'label'   => esc_html__('Inherit Fonts', 'affiliatepress-affiliate-marketing'),
                    'options' => $affiliatepress_inherit_fonts_list,
                ),
                array(
                    'label'   => esc_html__('Default Fonts', 'affiliatepress-affiliate-marketing'),
                    'options' => $affiliatepress_default_fonts_list,
                ),
                array(
                    'label'   => esc_html__('Google Fonts', 'affiliatepress-affiliate-marketing'),
                    'options' => $affiliatepress_google_fonts_list,
                ),
            );
            
            
            $affiliatepress_all_affiliates_status = $affiliatepress_affiliates->affiliatepress_all_affiliates_status();

            $affiliatepress_affiliate_account_page_id = $AffiliatePress->affiliatepress_get_settings('affiliate_account_page_id', 'affiliate_settings');
            $affiliatepress_affiliate_register_page_id = $AffiliatePress->affiliatepress_get_settings('affiliate_registration_page_id', 'affiliate_settings');

            $affiliatepress_dynamic_setting_data_fields = array(
                'modal_loading'                    => 'false',
                'flags_img_url'                    => AFFILIATEPRESS_IMAGES_URL,
                'all_currency_with_code'           => $affiliatepress_all_currency_with_code,
                'currency_countries'               => $affiliatepress_countries_currency_details,
                'affiliate_account_page_url'         => get_permalink($affiliatepress_affiliate_account_page_id),
                'affiliate_register_page_url'         => get_permalink($affiliatepress_affiliate_register_page_id),
                'max_tracking_cookie_days'           => $affiliatepress_max_tracking_cookie_days,
                'modals'                           => array(
                    'general_setting_modal'      => false,
                    'company_setting_modal'      => false,
                    'notification_setting_modal' => false,
                    'workhours_setting_modal'    => false,
                    'appointment_setting_modal'  => false,
                    'label_setting_modal'        => false,
                    'payment_setting_modal'      => false,
                ),
                'debug_log_setting_form'            => array(
                    'email_notification_debug_logs' => false,
                    'email_notification_debug_logs' => false,                 
                ),                
                'notification_setting_form'      => array(
                    'selected_mail_service'      => 'wp_mail',
                ),
                'default_affiliate_staus'        => $affiliatepress_all_affiliates_status,
                'rules_affiliate'                => array(
                    'affiliate_default_status'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please select default status', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'tracking_cookie_days'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add tracking cookie days.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'affiliate_link_limit'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add custom affiliate link limit.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'affiliate_url_parameter'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add affiliate url parameter.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'affiliate_account_page_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please select affiliate account page.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'affiliate_registration_page_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please select affiliate registration page.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),  
                    'reset_password_page_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please select reset password page.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'terms_and_conditions_page_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please select terms and conditions page.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),                                                                                                                                             
                ),
                'rules_messages'                   => array(
                    'login_error_message'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'dashboard_menu'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),                                                                                                                                                   
                    'commission_menu'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'affiliate_links_menu'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'visits_menu'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'creative_menu'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'paymnets_menu'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),     

                    'dashboard_affiliate_dashboard'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'dashboard_total_earnings'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'dashboard_paid_earnings'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'dashboard_unpaid_earnings'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'dashboard_visits_count'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),  
                    'dashboard_commissions_count'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'dashboard_commission_rate'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'dashboard_chart_earnings'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'dashboard_chart_commisisons'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'dashboard_reports'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),  
                    'commission_affiliate_commission'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'commission_select_status'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'commission_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'commission_product'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'commission_date'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'commission_amount'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'commission_status'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_affiliate_links'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'link_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'link_parameter_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'link_cookie_duration'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'link_cookie_duration_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'link_custome_Affiliate_links'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_generate_affiliate_link'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_serial_number'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_campaign_name'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_affiliate_url'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'link_click_to_copy'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_your_affiliate_link'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_generate_custome_affiliate_links'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_generate_link_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_page_url'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_compaign_name'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_sub_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_generate_link'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'link_enter_page_url'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_empty_validation'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_pattern_validation'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_campaign_name_empty_validation'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_enter_compaign_name'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_enter_sub_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_enter_sub_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'link_enter_sub_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'visit_visits'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'visit_select_type'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'visit_serial_number'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'visit_date'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'visit_compaign'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'visit_ip_address'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'visit_converted'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'visit_unconverted_status'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'visit_all' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'visit_landing_url'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),   
                    'visit_referrer_url'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                     'creative_title'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'creative_enter_creative_name'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'creative_select_type'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'creative_download'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'creative_preview'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'creative_html_code'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'creative_copy_code'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'creative_image'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'creative_text_link'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 

                    'paymnet_title'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'paymnet_select_status'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'payment_minimum_amount_label'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'paymnet_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'paymnet_date'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'filters'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'paymnet_method'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'paymnet_amount'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'paymnet_status'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'edit_details'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'profile_details'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'profile_picture'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'change_button'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'remove_button'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'paymnet_detail'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'save_changes'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'chnage_password'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'new_password'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'current_password'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'confirm_new_password'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'save_password'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'delete_account'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'delete_account_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'log_out'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'create_an_account'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'create_account_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'create_account_button'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'do_you_have_account'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'signin'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_signin'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_login_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_username_empty_validation'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_password_empty_validation'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_user_name'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_password'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_remember_me'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_forgot_password'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_signin_button'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_create_account'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'login_user_name_placeholder'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'forget_password_label'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'forget_password_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'forget_password_email'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'forget_password_empty_validation'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'forget_password_placeholder'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'forget_password_button'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'forget_password_signin'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'delete_account_confirmation_msg'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'delete_account_cancel_button'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'delete_account_close_button'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'delete_account_confirmation_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'apply'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'reset'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),   
                    'showing'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'pagination'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'start_date'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'end_date'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),  
                    'no_data'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'no_data_description'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'pagination_change_label'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'custome_link_delete_confirm'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'delete_custome_link_label'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),     
                    'no_label'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),     
                    'yes_label'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),     
                ),                
                'messages_setting_form'             => array(

                    //affiliate side menu
                    'login_error_message'   => '',
                    'dashboard_menu'             => '',   
                    'commission_menu'            => '',                    
                    'affiliate_links_menu'       => '',       
                    'visits_menu'                => '',       
                    'creative_menu'              => '',    
                    'paymnets_menu'              => '',    

                    //dashboard panel
                    'dashboard_affiliate_dashboard'   => '',
                    'dashboard_total_earnings'        => '',
                    'dashboard_paid_earnings'         => '',
                    'dashboard_unpaid_earnings'       => '',
                    'dashboard_visits_count'          => '',
                    'dashboard_commissions_count'     => '',
                    'dashboard_commission_rate'       => '',
                    'dashboard_reports'               => '',
                    'dashboard_chart_earnings'        => '',
                    'dashboard_chart_commisisons'     => '',

                    //Commission panel
                    'commission_affiliate_commission'   => '',
                    'commission_select_status'           => '',
                    'commission_id'                      => '',
                    'commission_product'                 => '',
                    'commission_date'                    => '',
                    'commission_amount'                  => '',
                    'commission_status'                  => '',

                    //affiliate links
                    'link_affiliate_links'                  => '',
                    'link_description'                      => '',
                    'link_parameter_description'            => '',
                    'link_cookie_duration'                  => '',
                    'link_cookie_duration_description'      => '',
                    'link_custome_Affiliate_links'          => '',
                    'link_generate_affiliate_link'          => '',
                    'link_serial_number'                    => '',
                    'link_campaign_name'                    => '',
                    'link_affiliate_url'                    => '',
                    'link_click_to_copy'                    => '',
                    'link_your_affiliate_link'              => '', 
                    'link_generate_custome_affiliate_links' => '',
                    'link_generate_link_description'        => '',
                    'link_page_url'                         => '',
                    'link_enter_page_url'                   => '',
                    'link_empty_validation'                 => '',
                    'link_pattern_validation'               => '',
                    'link_campaign_name_empty_validation'   => '',
                    'link_compaign_name'                    => '',
                    'link_enter_compaign_name'              => '',
                    'link_sub_id'                           => '',
                    'link_enter_sub_id'                     => '',
                    'link_generate_link'                    => '', 
                    'custome_link_delete_confirm'     => '',
                    'delete_custome_link_label'       => '',

                    //visits
                    'visit_visits'                 => '',
                    'visit_select_type'            => '',
                    'visit_serial_number'          => '',
                    'visit_date'                   => '',
                    'visit_compaign'               => '',
                    'visit_ip_address'             => '',
                    'visit_converted'              => '',
                    'visit_all'                    => '',
                    'visit_landing_url'            => '',
                    'visit_referrer_url'           => '',
                    'visit_unconverted_status'     => '',

                    //Creative
                    'creative_title'                    => '',
                    'creative_enter_creative_name'      => '',
                    'creative_select_type'              => '',
                    'creative_download'                 => '',
                    'creative_preview'                  => '',
                    'creative_html_code'                => '',
                    'creative_copy_code'                => '',
                    'creative_image'                    => '',
                    'creative_text_link'                => '',
                    'creative_name'                     => '',
                    'creative_type'                     => '',

                    //paymnets
                    'paymnet_title' => '',
                    'paymnet_select_status' =>'',
                    'payment_minimum_amount_label' =>'',
                    'paymnet_id'=>'',
                    'paymnet_date'=>'',
                    'paymnet_method'=>'',
                    'paymnet_amount'=>'',
                    'paymnet_status' => '',

                    //edit profile
                    'edit_details' =>'',
                    'profile_details'=>'',
                    'profile_picture'=>'',
                    'change_button'=>'',
                    'remove_button'=>'',
                    'paymnet_detail'=>'',
                    'save_changes'=>'',
                    'chnage_password'=>'',
                    'current_password'=>'',
                    'new_password'=>'',
                    'confirm_new_password'=>'',
                    'save_password'=>'',
                    'delete_account'=>'',
                    'delete_account_description'=>'',
                    'log_out'=>'',
                    'delete_account_confirmation_msg'=>'',
                    'delete_account_confirmation_description'=>'',

                    //register account
                    'create_an_account'=>'',
                    'create_account_description'=>'',
                    'create_account_button'=>'',
                    'do_you_have_account'=>'',
                    'signin'=>'',

                    //login
                    'login_signin'=>'',
                    'login_login_description'=>'',
                    'login_user_name'=>'',
                    'login_user_name_placeholder'=>'',
                    'login_password'=>'',
                    'login_password_placeholder'=>'',
                    'login_remember_me'=>'',
                    'login_forgot_password'=>'',
                    'login_signin_button'=>'',
                    'login_dont_have_account'=>'',
                    'login_create_account'=>'',
                    'login_username_empty_validation' => '',
                    'login_password_empty_validation' =>'',

                    //forget_password
                    'forget_password_label'=>'',
                    'forget_password_description'=>'',
                    'forget_password_email'=>'',
                    'forget_password_placeholder'=>'',
                    'forget_password_button'=>'',
                    'forget_password_signin'=>'',
                    'forget_password_empty_validation' => '',

                    //common 
                    'apply'                           => '',
                    'reset'                           => '',
                    'showing'                         => '',
                    'pagination'                      => '',
                    'start_date'                      => '',
                    'end_date'                        => '',
                    'no_data'                         => '',
                    'no_data_description'             => '',
                    'pagination_change_label'         => '',
                    'filters'                         => '',
                    'no_label'                        => '',
                    'yes_label'                       => '',
                ),
                'affiliate_setting_form'             => array(
                    'allow_affiliate_registration'        => false,
                    'affiliate_default_status'            => '1',
                    'tracking_cookie_days'                => 5,
                    'affiliate_link_limit'                => 50,
                    'website_field_required'              => false,
                    'promote_us_field_required'           => false,
                    'affiliate_url_parameter'             => 'afref',
                    'enable_fancy_affiliate_url'          => false,
                    'affiliate_usage_stats'               => true,
                    'affiliate_account_page_id'           => '',
                    'affiliate_registration_page_id'      => '',
                    'reset_password_page_id'              => '',
                    'terms_and_conditions_page_id'        => '',
                    'payment_default_currency'           => 'USD',
                    'currency_symbol_position'           => 'before',
                    'number_of_decimals'                 => '2',
                    'currency_separator'                 => 'comma-dot',
                    'default_url_type'                   => 'affiliate_default_url',
                ), 
                'rules_commissions'                   => array(
                    'default_discount_val'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter commission rate', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'refund_grace_period'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter refund grace period', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'minimum_payment_amount'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter minimum payment amount', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'commission_cooling_period_days'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter commission cooling period days', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'day_of_billing_cycle'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter day of billing cycle', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                ),
                'show_currency_position'                 => false,
                'commissions_setting_form'               => array(
                    'default_discount_val'               => 10,
                    'default_discount_type'              => 'percentage',
                    'flat_rate_commission_basis'         => 'pre_product',
                    'exclude_shipping'                   => false,
                    'exclude_taxes'                      => false,
                    'earn_commissions_own_orders'        => false,
                    'reject_commission_on_refund'        => false,
                    'allow_zero_amount_commission'       => false,
                    'refund_grace_period'                => 2,
                    'minimum_payment_amount'             => 10,
                    'minimum_payment_order'              => 1,
                    'commission_billing_cycle'           => 'monthly',
                    'commission_cooling_period_days'     => 30,
                    'day_of_billing_cycle'               => 3,  
                    'default_commission_status'          => '2',
                    'auto_approve_commission_after_days' => 0,
                ),
                'default_commission_status_disable' => true,
                'minimum_payment_order_disable' => true,
                'default_commission_status_option' => array(
                    array(
                        'label'  => esc_html__('Pending', 'affiliatepress-affiliate-marketing'),
                        'value' => '2',
                    ),
                ),
                'billing_cycle'        => array(
                    array(
                        'label'  => 'Disabled',
                        'value' => 'disabled',
                    ),
                    array(
                        'label'  => 'Monthly',
                        'value' => 'monthly',
                    ),
                    array(
                        'label'  => 'Weekly',
                        'value' => 'weekly',
                    ),
                    array(
                        'label'  => 'Yearly',
                        'value' => 'yearly',
                    )
                ),
                'default_smtp_secure_options'      => array(
                    array(
                        'text'  => esc_html__('SSL', 'affiliatepress-affiliate-marketing'),
                        'value' => 'SSL',
                    ),
                    array(
                        'text'  => esc_html__('TLS', 'affiliatepress-affiliate-marketing'),
                        'value' => 'TLS',
                    ),
                    array(
                        'text'  => esc_html__('Disabled', 'affiliatepress-affiliate-marketing'),
                        'value' => 'Disabled',
                    ),
                ),
                'price_symbol_position_val'        => array(
                    array(
                        'text'        => esc_html__('Before value', 'affiliatepress-affiliate-marketing'),
                        'value'       => 'before',
                        'position_ex' => '$100',
                    ),
                    /*
                    array(
                        'text'        => esc_html__('Before value', 'affiliatepress-affiliate-marketing') . ', ' . esc_html__('separated with space', 'affiliatepress-affiliate-marketing'),
                        'value'       => 'before_with_space',
                        'position_ex' => '$ 100',
                    ),
                    */
                    array(
                        'text'        => esc_html__('After value', 'affiliatepress-affiliate-marketing'),
                        'value'       => 'after',
                        'position_ex' => '100$',
                    ),
                    /*
                    array(
                        'text'        => esc_html__('After value', 'affiliatepress-affiliate-marketing') . ', ' . esc_html__('separated with space', 'affiliatepress-affiliate-marketing'),
                        'value'       => 'after_with_space',
                        'position_ex' => '100 $',
                    ),
                    */
                ),
                'price_separator_vals'             => array(
                    array(
                        'text'         => esc_html__('Comma-Dot', 'affiliatepress-affiliate-marketing'),
                        'value'        => 'comma-dot',
                        'separator_ex' => '15,000.00',
                    ),
                    array(
                        'text'         => esc_html__('Dot-Comma', 'affiliatepress-affiliate-marketing'),
                        'value'        => 'dot-comma',
                        'separator_ex' => '15.000,00',
                    ),
                    array(
                        'text'         => esc_html__('Space-Dot', 'affiliatepress-affiliate-marketing'),
                        'value'        => 'space-dot',
                        'separator_ex' => '15 000.00',
                    ),
                    array(
                        'text'         => esc_html__('Space-Comma', 'affiliatepress-affiliate-marketing'),
                        'value'        => 'space-comma',
                        'separator_ex' => '15 000,00',
                    ),
                    array(
                        'text'  => esc_html__('Custom', 'affiliatepress-affiliate-marketing'),
                        'value' => 'Custom',
                    ),
                ),
                'fonts_list'                 => $affiliatepress_fonts_list,      
                'appearance_setting_form'   => array(
                    'primary_color'         => '#6858e0',
                    'background_color'      => '#ffffff',
                    'text_color'            => '#1A1E26',
                    'content_color'         => '#576582',
                    'font'                  => 'Poppins',
                    'panel_background_color'=> '#ffffff',
                    'border_color'          => '#C9CFDB',
                ),
                'rules_notification'               => array(
                    'company_name'   => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter company name', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'sender_name'   => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter from name', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'sender_email'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter from email', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'sender_url'    => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter sender url', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'admin_email'   => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter admin email', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'success_url'   => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter successfull redirection url', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'cancel_url'    => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter cancel redirection url', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'smtp_port'     => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter smtp port', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'smtp_host'     => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter smtp host', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'smtp_secure'   => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter smtp secure', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'gmail_client_ID' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter gmail client ID', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'gmail_client_secret' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter gmail client secret', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),

                ),                
                'notification_setting_form'        => array(
                    'selected_mail_service' => 'wp_mail',
                    'company_name'          => get_option('blogname'),
                    'sender_name'           => get_option('blogname'),
                    'sender_email'          => get_option('admin_email'),
                    'admin_email'           => get_option('admin_email'),
                    'success_url'           => '',
                    'cancel_url'            => '',
                    'smtp_host'             => '',
                    'smtp_port'             => '',
                    'smtp_secure'           => 'Disabled',
                    'smtp_username'         => '',
                    'smtp_password'         => '',
                    'gmail_client_ID'       => '',
                    'gmail_client_secret'   => '',
                    'gmail_redirect_url'    => '',
                    'gmail_auth_secret'     => '',
                    'affiliatepress_gmail_auth'            => '',
                    'affiliatepress_response_email'        => '',
                    'affiliatepress_gmail_auth_token' => '',
                ),
                'notification_smtp_test_mail_form' => array(
                    'smtp_test_receiver_email' => '',
                    'smtp_test_msg'            => '',
                ),
                'notification_gmail_test_mail_form' => array(
                    'gmail_test_receiver_email' => '',
                    'gmail_test_msg'            => '',
                ),
                'notification_wpmail_test_mail_form' => array(
                    'wpmail_test_receiver_email' => '',
                    'wpmail_test_msg'            => '',
                ),
                'customer_setting_form'            => array(
                    'allow_wp_user_create' => false,
                    'allow_autologin_user' => true,
                ),
                'is_edit_break' => 0,
                'succesfully_send_test_email'      => 0,
                'error_send_test_email'            => 0,
                'error_text_of_test_email'         => '',
                'is_disable_send_test_email_btn'   => false,
                'is_display_send_test_mail_loader' => '0',               
                'rules_notification'               => array(
                    'sender_name'   => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter sender name', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'sender_email'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter sender email', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'sender_url'    => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter sender url', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'admin_email'   => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter admin email', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'success_url'   => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter successfull redirection url', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'cancel_url'    => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter cancel redirection url', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'smtp_port'     => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter smtp port', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'smtp_host'     => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter smtp host', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'smtp_secure'   => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter smtp secure', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'gmail_client_ID' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter gmail client ID', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'gmail_client_secret' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter gmail client secret', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),

                ),
                'rules_smtp_test_mail'             => array(
                    'smtp_test_receiver_email' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter email address', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'smtp_test_msg'            => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter message', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                ),
                'rules_gmail_test_mail'             => array(
                    'gmail_test_receiver_email' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter email address', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'gmail_test_msg'            => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter message', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                ),
                'rules_wpmail_test_mail'             => array(
                    'wpmail_test_receiver_email' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter email address', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'wpmail_test_msg'            => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter message', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                ),         
                'isloading'                        => false,
                'is_display_loader'                => '0',
                'is_disabled'                      => false,
                'is_disabled_test'                 => false,
                'is_display_save_loader'           => '0',
                'is_mask_display'                  => false,
                //'open_display_log_modal'           => false,
                'items'                            => array(),
                'multipleSelection'                => array(),
                'perPage'                          => 10,
                'totalItems'                       => 0,
                'pagination_selected_length'       => 10,
                'pagination_length'                => 10,
                'currentPage'                      => 1,
                'pagination_length_val'            => '10',
                'open_view_model_gateway'          => '',
                'open_view_model_gateway_name'     => '', 
                'is_display_loader_view'           => '0',
                'select_download_log'              => '7',
                'ap_settings_content_loaded'       => 1,
                'log_download_default_option'      => array(
                    array(
                        'key'   => esc_html__('Last 1 Day', 'affiliatepress-affiliate-marketing'),
                        'value' => '1',
                    ),
                    array(
                        'key'   => esc_html__('Last 3 Days', 'affiliatepress-affiliate-marketing'),
                        'value' => '3',
                    ),
                    array(
                        'key'   => esc_html__('Last 1 Week', 'affiliatepress-affiliate-marketing'),
                        'value' => '7',
                    ),
                    array(
                        'key'   => esc_html__('Last 2 Weeks', 'affiliatepress-affiliate-marketing'),
                        'value' => '14',
                    ),
                    array(
                        'key'   => esc_html__('Last Month', 'affiliatepress-affiliate-marketing'),
                        'value' => '30',
                    ),
                    array(
                        'key'   => esc_html__('All', 'affiliatepress-affiliate-marketing'),
                        'value' => 'all',
                    ),
                    /*
                    array(
                        'key'   => esc_html__('Custom', 'affiliatepress-affiliate-marketing'),
                        'value' => 'custom',
                    ),
                    */
                ),
                'download_log_daterange'             => array( date('Y-m-d', strtotime('-3 Day')), date('Y-m-d', strtotime('+3 Day')) ),// phpcs:ignore
                'is_display_download_save_loader'    => '0',
                'proper_body_class'                  => false,
                'smtp_mail_error_text'               => '',
                'smtp_error_modal'                   => false,
                'succesfully_send_test_gmail_email'      => 0,
                'error_send_test_gmail_email'            => 0,
                'error_text_of_test_gmail_email'         => '',
                'is_disable_send_test_gmail_email_btn'   => false,
                'is_display_send_test_gmail_mail_loader' => '0',
                'is_disable_send_test_wpmail_email_btn'  => false,
                'is_display_send_test_wpmail_mail_loader' => '0',
                'succesfully_send_test_wpmail_email'      => 0,
                'error_send_test_wpmail_email'            => 0,
                'gmail_mail_error_text'                   => '',
                'is_display_reset_color_setting_loader' => '0',
                'is_disable_reset_color_setting_btn'  => false,

            );

        }
                
        /**
         * Settings module on load methods
         *
         * @param  string $affiliatepress_settings_dynamic_on_load_methods
         * @return string
        */
        function affiliatepress_settings_dynamic_on_load_methods_func($affiliatepress_settings_dynamic_on_load_methods){            
            global $affiliatepress_notification_duration;

            $affiliatepress_extra_settings_tab_dynamic_on_load_method = "";
            $affiliatepress_extra_settings_tab_dynamic_on_load_method = apply_filters('affiliatepress_extra_settings_tab_dynamic_on_load_method', $affiliatepress_extra_settings_tab_dynamic_on_load_method);     

            $affiliatepress_settings_dynamic_on_load_methods.='
                const vm = this;
                var selected_tab_name = sessionStorage.getItem("current_affiliatpress_tabname"); 
                if(selected_tab_name != null) {
                    vm.selected_tab_name = selected_tab_name;    
                } else if(selected_tab_name == null) {
                    selected_tab_name = vm.selected_tab_name;
                }
                if(selected_tab_name == "affiliate_settings"){
                    vm.getSettingsData("affiliate_settings", "affiliate_setting_form");                                        
                }else if(selected_tab_name == "commissions_settings"){
                    vm.getSettingsData("commissions_settings", "commissions_setting_form");
                }else if(selected_tab_name == "integrations_settings"){
                    vm.getSettingsData("integrations_settings", "integrations_setting_form");
                }else if(selected_tab_name == "email_notification_settings"){
                    vm.getSettingsData("email_notification_settings", "notification_setting_form");
                }else if(selected_tab_name == "debug_log_settings"){
                    vm.getSettingsData("debug_log_settings", "debug_log_setting_form");
                }else if(selected_tab_name == "message_settings"){
                    vm.getSettingsData("message_settings", "messages_setting_form");
                }else if(selected_tab_name == "appearance_settings"){
                    vm.getSettingsData("appearance_settings", "appearance_setting_form");
                }
                '.$affiliatepress_extra_settings_tab_dynamic_on_load_method.'
                           
            ';

            return $affiliatepress_settings_dynamic_on_load_methods;

        }        
      

        /**
         * Get settings of specific setting type
         *
         * @param  string $affiliatepress_setting_type
         * @return array
         */
        public function affiliatepress_get_settings_data_by_setting_type( $affiliatepress_setting_type ){
            global $wpdb, $affiliatepress_tbl_ap_settings;
            if (! empty($affiliatepress_setting_type) ) {

                $affiliatepress_tbl_ap_settings_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_settings); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_settings contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

                $affiliatepress_fetch_settings_details = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$affiliatepress_tbl_ap_settings_temp} WHERE ap_setting_type = %s", $affiliatepress_setting_type  ), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_settings is table name defined globally. False Positive alarm
                
                $affiliatepress_fetch_settings_details = apply_filters('affiliatepress_modify_get_settings_data_variable', $affiliatepress_fetch_settings_details,$affiliatepress_setting_type); // phpcs:ignore WordPress.Security.NonceVerification

                if (! empty($affiliatepress_fetch_settings_details) ) {
                    return $affiliatepress_fetch_settings_details;
                }
            }
            return array();
        }

        
        /**
         * Function for save affiliate setting data
         *
         * @return json
         */
        function affiliatepress_save_settings_data_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_settings,$AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_settings', true, 'ap_wp_nonce' );
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_settings')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }

            if (! empty($_POST) && ! empty($_POST['action']) && ( sanitize_text_field($_POST['action']) == 'affiliatepress_save_settings_data' ) && ! empty($_POST['settingType']) ) { // phpcs:ignore 
                
                $affiliatepress_setting_type       = sanitize_text_field($_POST['settingType']); // phpcs:ignore
                $affiliatepress_setting_action     = sanitize_text_field($_POST['action']); // phpcs:ignore

                update_option('affiliatepress_flush_rewrites',1);


                $affiliatepress_all_settings_data_with_key = array();
                $affiliatepress_all_settings_data = $AffiliatePress->affiliatepress_install_all_settings();
                foreach($affiliatepress_all_settings_data as $affiliatepress_setting_key_data){
                    $affiliatepress_all_settings_data_with_key[$affiliatepress_setting_key_data['ap_setting_name']] = (isset($affiliatepress_setting_key_data['type']))?$affiliatepress_setting_key_data['type']:'text'; 
                }
                $affiliatepress_response_arr = array();                

                if(!empty($_POST['all_settings']) && is_array($_POST['all_settings'])){// phpcs:ignore
                        $affiliatepress_unset_settings_data = array("none");
                        $affiliatepress_unset_settings_data = apply_filters('affiliatepress_modify_save_setting_data',$affiliatepress_unset_settings_data);

                    foreach ( $_POST['all_settings'] as $affiliatepress_setting_key => $affiliatepress_setting_val){ // phpcs:ignore
                        if (in_array($affiliatepress_setting_key, $affiliatepress_unset_settings_data)){
                           continue;
                        }
                        $affiliatepress_field_type = (isset($affiliatepress_all_settings_data_with_key[$affiliatepress_setting_key]))?$affiliatepress_all_settings_data_with_key[$affiliatepress_setting_key]:'';
                        if($affiliatepress_field_type == 'integer'){
                            $affiliatepress_setting_val = intval($affiliatepress_setting_val);
                        }else if($affiliatepress_field_type == 'text'){
                            $affiliatepress_setting_val = sanitize_text_field($affiliatepress_setting_val);
                        }else if($affiliatepress_field_type == 'email'){
                            $affiliatepress_setting_val = sanitize_email($affiliatepress_setting_val);
                        }else if($affiliatepress_field_type == 'float'){
                            $affiliatepress_setting_val = floatval($affiliatepress_setting_val);
                        }else{
                            $affiliatepress_setting_val = sanitize_text_field($affiliatepress_setting_val);
                        }

                        $affiliatepress_res = $AffiliatePress->affiliatepress_update_settings(sanitize_text_field($affiliatepress_setting_key), $affiliatepress_setting_type, $affiliatepress_setting_val);
                        array_push($affiliatepress_response_arr, $affiliatepress_res);                    
                    }
                }
                
                $AffiliatePress->affiliatepress_update_all_auto_load_settings();                
                do_action('affiliatepress_after_save_settings_data'); // phpcs:ignore

                if($affiliatepress_setting_type == 'affiliate_settings'){
                    if(isset($_POST['all_settings']['affiliate_usage_stats']) && $_POST['all_settings']['affiliate_usage_stats'] == true) { // phpcs:ignore WordPress.Security.NonceVerification
                        $this->affiliatepress_set_anonymous_data_cron();
                    }                    
                }

                if (! in_array('0', $affiliatepress_response_arr) ) {
                    $response['variant'] = 'success';
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Settings has been updated successfully.', 'affiliatepress-affiliate-marketing');
                }

            }
            echo wp_json_encode($response);
            exit();
        }

        /**
         * Function for get affiliate data
         *
         * @return json
         */
        function affiliatepress_get_settings_details(){
            
            global $wpdb, $affiliatepress_tbl_ap_settings,$AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_settings', true, 'ap_wp_nonce' );
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            $affiliatepress_setting_type  = '';
            if(!current_user_can('affiliatepress_settings')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }
            $affiliatepress_setting_return_data = array();

            if (! empty($_POST['setting_type']) ) { // phpcs:ignore 

                $affiliatepress_setting_type  = sanitize_text_field($_POST['setting_type']); // phpcs:ignore 
                $affiliatepress_settings_data = $this->affiliatepress_get_settings_data_by_setting_type($affiliatepress_setting_type);
                if (! empty($affiliatepress_settings_data) ) {
                    foreach ( $affiliatepress_settings_data as $affiliatepress_setting_key => $affiliatepress_setting_val ) {
                        $affiliatepress_tmp_setting_val = $affiliatepress_setting_val['ap_setting_value'];
                        if ($affiliatepress_tmp_setting_val == 'true' ) {
                            $affiliatepress_tmp_setting_val = true;
                        } elseif ($affiliatepress_tmp_setting_val == 'false' ) {
                            $affiliatepress_tmp_setting_val = false;
                        }
                        if (gettype($affiliatepress_tmp_setting_val) == 'boolean' ) {
                            $affiliatepress_setting_return_data[ $affiliatepress_setting_val['ap_setting_name'] ] = $affiliatepress_tmp_setting_val;
                        } else {
                            if ($affiliatepress_setting_val['ap_setting_name'] == 'smtp_password' ) {
                                $affiliatepress_setting_return_data[ $affiliatepress_setting_val['ap_setting_name'] ] = stripslashes_deep($affiliatepress_tmp_setting_val);
                            } else {
                                if (is_serialized($affiliatepress_tmp_setting_val) ) {
                                    $affiliatepress_setting_return_data[ $affiliatepress_setting_val['ap_setting_name'] ] = $affiliatepress_tmp_setting_val;
                                } else {
                                    $affiliatepress_setting_return_data[ $affiliatepress_setting_val['ap_setting_name'] ] = stripslashes_deep($affiliatepress_tmp_setting_val);
                                }
                            }
                        }
                    }

                }
            }

            $affiliatepress_setting_return_data = apply_filters('affiliatepress_modify_settings_listing_data', $affiliatepress_setting_return_data, $affiliatepress_setting_type); 
            /* Filter for modified data for pro & addon */

            $response['variant'] = 'success';
            $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__('Settings has been fetched successfully', 'affiliatepress-affiliate-marketing');
            $response['data']    = (!empty($affiliatepress_setting_return_data))?$affiliatepress_setting_return_data:'';   
            
            

            wp_send_json($response);
            exit;            
        }
        
        /**
         * Function for dynamic const add in vue
         *
         * @return void
         */
        function affiliatepress_settings_dynamic_constant_define_func($affiliatepress_settings_dynamic_constant_define){

            $affiliatepress_settings_dynamic_constant_define.='
                const open_display_log_modal = ref(false);
                affiliatepress_return_data["open_display_log_modal"] = open_display_log_modal;             
            ';

            return $affiliatepress_settings_dynamic_constant_define;

        ?>
       
        <?php 
        }

        /**
         * Function for affiliate vue data
         *
         * @return void
        */
        function affiliatepress_settings_dynamic_data_fields_func($affiliatepress_dynamic_setting_data_fields){            
            
            global $AffiliatePress,$affiliatepress_dynamic_setting_data_fields,$affiliatepress_affiliates,$affiliatepress_global_options,$affiliatepress_tracking;

            $affiliatepress_current_currency_symbol = $AffiliatePress->affiliatepress_get_current_currency_symbol();
            $affiliatepress_dynamic_setting_data_fields['current_currency_symbol'] = $affiliatepress_current_currency_symbol;
            
            $affiliatepress_all_plugin_integration = $affiliatepress_global_options->affiliatepress_all_plugin_integration();
            $affiliatepress_integrations_setting_form = array();
            if(!empty($affiliatepress_all_plugin_integration)){
                foreach($affiliatepress_all_plugin_integration as $affiliatepress_integration_plugin_detail){
                    $affiliatepress_integrations_setting_form[$affiliatepress_integration_plugin_detail['plugin_value']] = false;
                }
            }

            $affiliatepress_all_wordpress_pages = $AffiliatePress->affiliatepress_get_all_wp_pages();

            $affiliatepress_default_currency_code = $AffiliatePress->affiliatepress_get_settings('payment_default_currency', 'affiliate_settings');

            $affiliatepress_dynamic_setting_data_fields['show_currency_position'] = false; 
            if(isset($affiliatepress_dynamic_setting_data_fields['all_currency_with_code'][$affiliatepress_default_currency_code])){
                if(empty($affiliatepress_dynamic_setting_data_fields['all_currency_with_code'][$affiliatepress_default_currency_code]['currency_position'])){
                    $affiliatepress_dynamic_setting_data_fields['show_currency_position'] = true;
                }    
            }            

            $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_weekly_cycle_days = (isset($affiliatepress_global_options_data['weekly_cycle_days']))?$affiliatepress_global_options_data['weekly_cycle_days']:array();
            $affiliatepress_url_types = (isset($affiliatepress_global_options_data['url_types']))?$affiliatepress_global_options_data['url_types']:array();

            $affiliatepress_dynamic_setting_data_fields['weekly_cycle_days'] = $affiliatepress_weekly_cycle_days;

            $affiliatepress_yearly_cycle_months = (isset($affiliatepress_global_options_data['yearly_cycle_months']))?$affiliatepress_global_options_data['yearly_cycle_months']:array();
            $affiliatepress_dynamic_setting_data_fields['yearly_cycle_months'] = $affiliatepress_yearly_cycle_months;

            $affiliatepress_dynamic_setting_data_fields['affiliatepress_url_types'] = $affiliatepress_url_types;
            $affiliatepress_selected_tab_name  = !empty($_REQUEST['setting_page']) ? sanitize_text_field($_REQUEST['setting_page']) : 'affiliate_settings'; // phpcs:ignore         
            $affiliatepress_dynamic_setting_data_fields['selected_tab_name'] = $affiliatepress_selected_tab_name;     

            $affiliatepress_dynamic_setting_data_fields['is_display_reset_wizard_setting'] = 0;
            $affiliatepress_dynamic_setting_data_fields['is_display_reset_wizard_setting_btn'] = false;

            $affiliatepress_dynamic_setting_data_fields['integration_reject_commission_disable'] = true;
            $affiliatepress_dynamic_setting_data_fields['integration_upgrade_commission_disable'] = true;
            $affiliatepress_dynamic_setting_data_fields['affiliate_integration_refund_disabled_switch'] = true;

            $affiliatepress_dynamic_setting_data_fields['affiliate_integration_active_list'] = $affiliatepress_tracking->affiliatepress_integration_list('active');
            $affiliatepress_dynamic_setting_data_fields['affiliate_integration_inactive_list'] = $affiliatepress_tracking->affiliatepress_integration_list('inactive');

            $affiliatepress_dynamic_setting_data_fields['integrations_setting_form'] = array(
                'enable_woocommerce' => false,
                'woocommerce_exclude_shipping' =>true,
                'woocommerce_exclude_taxes' => true,
                'woocommerce_reject_commission_on_refund'=>true,

                'enable_accept_stripe_payments' => false,
                'accept_stripe_payments_exclude_shipping' =>true,
                'accept_stripe_payments_exclude_taxes' => true,
                'accept_stripe_payments_reject_commission_on_refund'=>true,

                'enable_armember' => false,
                'armember_exclude_taxes'=> true,
                'armember_reject_commission_on_refund' => true,

                'enable_gravity_forms' => false,
                'enable_wp_simple_pay' => false,

                'enable_member_mouse' => false,


                'enable_contact_form_7_paypal_stripe' => true,
                

                'enable_paid_memberships_pro' => false,
                'paid_memberships_pro_reject_commission_on_refund' => true,

                'easy_digital_downloads_exclude_shipping' => true,
                'easy_digital_downloads_exclude_taxes' =>true ,
                'easy_digital_downloads_reject_commission_on_refund' => true,
                'easy_digital_downloads_disable_commission_on_upgrade' => true,

                'enable_easy_digital_downloads' => false,
                'memberpress_exclude_taxes' =>true ,
                'memberpress_reject_commission_on_refund' => true,
                'memberpress_disable_commission_on_upgrade' => true,

                'enable_surecart' => false,
                'surecart_exclude_shipping' => true,
                'surecart_exclude_taxes' =>true ,
                'surecart_reject_commission_on_refund' => true,

                'enable_paid_memberships_subscriptions' => false,
                'paid_memberships_subscriptions_exclude_taxes' => true,
                'paid_memberships_subscriptions_reject_commission_on_refund' => true,

                'enable_ecwid_ecommerce' => false,

                'enable_wp_easycart' => false,
                'wp_easycart_exclude_shipping' => true,
                'wp_easycart_exclude_taxes' =>true ,
                'wp_easycart_reject_commission_on_refund' => true,

                'enable_gamipress' => false,
                'enable_give_wp' => false,
                'give_wp_reject_commission_on_refund' =>true,

                'enable_wp_forms' => false,
                'enable_memberpress' => false,
                'enable_ultimate_member' => false,
                'enable_simple_membership' => false,
                'enable_wp_members_membership' => false,
                'enable_learn_dash' => false,
                'learn_dash_exclude_taxes' =>true ,
                'learn_dash_reject_commission_on_refund' => true,

                'enable_lifter_lms' => false,
                'lifter_lms_reject_commission_on_refund' => true,

                'enable_learnpress' => false,
                'learnpress_reject_commission_on_refund'=>true,

                'enable_tutor_lms' => false,

                'enable_restrict_content' =>false,
                'restrict_content_reject_commission_on_refund' => true,

                'enable_formidablepro' =>false,
                'formidablepro_reject_commission_on_refund' => true,

                'enable_ninjaforms' =>false,
                'ninjaforms_reject_commission_on_delete_entry' => true,

                'enable_ultimate_membership_pro' =>false,

                'enable_masteriyo_lms' => false,
                'ultimate_membership_pro_exclude_taxes' =>true ,
                'ultimate_membership_pro_reject_commission_on_refund' => true,

                'enable_getpaid' => false,
                'getpaid_reject_commission_on_refund' => true,

                'enable_arforms' => false,

                'enable_download_manager' => false,
                'download_manager_reject_commission_on_refund'=>true,

                'enable_bookingpress' => false,
                'bookingpress_reject_commission_on_refund'=>true,

                'enable_learndash' => false,

            );

            $affiliatepress_dynamic_setting_data_fields['expand_settings'] = '';
            $affiliatepress_dynamic_setting_data_fields['rules_integrations'] = array();
            $affiliatepress_dynamic_setting_data_fields['all_wordpress_pages'] = $affiliatepress_all_wordpress_pages;

            $affiliatepress_dynamic_setting_data_fields = apply_filters('affiliatepress_backend_modify_settings_data_fields', $affiliatepress_dynamic_setting_data_fields);

            return wp_json_encode($affiliatepress_dynamic_setting_data_fields);

        }
        
        /**
         * Function for setting module dynamic vue method
         *
         * @param  string $affiliatepress_settings_dynamic_vue_methods
         * @return string
         */
        function affiliatepress_settings_dynamic_vue_methods_func($affiliatepress_settings_dynamic_vue_methods){
            global $affiliatepress_notification_duration;

            $affiliatepress_get_settings_details_response = "";
            $affiliatepress_get_settings_details_response = apply_filters('affiliatepress_get_settings_details_response', $affiliatepress_get_settings_details_response);

            $affiliatepress_add_settings_more_postdata = "";
            $affiliatepress_add_settings_more_postdata = apply_filters('affiliatepress_add_settings_more_postdata', $affiliatepress_add_settings_more_postdata); 
            
            $affiliatepress_settings_response = "";
            $affiliatepress_settings_response = apply_filters('affiliatepress_settings_response', $affiliatepress_settings_response); 
            
            $affiliatepress_extra_settings_tab_dynamic_load_method = "";
            $affiliatepress_extra_settings_tab_dynamic_load_method = apply_filters('affiliatepress_extra_settings_tab_dynamic_load_method', $affiliatepress_extra_settings_tab_dynamic_load_method);  

            $affiliatepress_extra_tab_save_settings = "";
            $affiliatepress_extra_tab_save_settings = apply_filters('affiliatepress_extra_tab_save_settings', $affiliatepress_extra_tab_save_settings);  

            $affiliatepress_settings_dynamic_vue_methods.='
            affiliatepress_resetup_wizard(){
                    const vm = this;
                    vm.is_display_reset_wizard_setting = "1";
                    vm.is_display_reset_wizard_setting_btn = true;
                    var postData = { action:"affiliatepress_resetup_wizard", _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) ).then(function(response){  
                        if(response.data.variant == "success"){
                            window.location.href = "'.esc_html(admin_url() . "admin.php?page=affiliatepress_lite_wizard").'";
                            vm.is_display_reset_wizard_setting = "0";
                            vm.is_display_reset_wizard_setting_btn = false;
                        }else{
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });                                
                        }
        
                    }.bind(this))
                        .catch( function (error) {
                            vm.import_loading = "0";
                            vm.$notify({
                                title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                                message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                                type: "error",
                                customClass: "error_notification",
                                duration:'.intval($affiliatepress_notification_duration).',                        
                            });
                        }
                    );      
                },            
                default_currency_change(event){
                    var vm = this;
                    var payment_default_currency = vm.affiliate_setting_form.payment_default_currency;
                    var all_currency_with_code   = vm.all_currency_with_code;
                    if(typeof all_currency_with_code[payment_default_currency].currency_position != "undefined"){
                        if(all_currency_with_code[payment_default_currency].currency_position == "fixed"){
                            vm.affiliate_setting_form.currency_symbol_position = all_currency_with_code[payment_default_currency].symbol_position;
                            vm.show_currency_position = false;
                        }else{
                            vm.show_currency_position = true;
                        }
                    }
                },	  
                isNumberValidate(evt) {
                    const vm = this;
                    const regex = /^(\d{1,3}(,\d{3})*|\d+)?(\.\d*)?$/;
                    if (regex.test(evt)) {
                        vm.inputValue = evt; 
                    } else {
                        vm.commissions_setting_form.default_discount_val = vm.inputValue;
                    }
                },
                change_auto_payout_cycle(val){
                    const vm = this;
                    if(val == "weekly"){
                        vm.commissions_setting_form.day_of_billing_cycle = "1";
                    }
                    if(val == "yearly"){
                        vm.commissions_setting_form.day_of_billing_cycle = "1";
                    }
                },
                handleSizeChange(val) {                
                    const vm = this;
                    var log_type = vm.open_view_model_gateway_name;
                    this.perPage = val
                    this.affiliatepess_view_log(log_type)
                },        
                handleCurrentChange(val) {
                    const vm = this
                    var log_type = vm.open_view_model_gateway_name;
                    this.currentPage = val;                
                    this.affiliatepess_view_log(log_type, "pagination");
                },
                affiliate_url_display(affiliate_url_parameter,enable_fancy_affiliate_url){
                    var affiliate_link = "";
                    if(enable_fancy_affiliate_url){
                        affiliate_link = "http://yourwebsite.com/"+affiliate_url_parameter+"/123/";
                    }else{
                        affiliate_link = "http://yourwebsite.com/?"+affiliate_url_parameter+"=123";
                    }
                    return affiliate_link;
                },            
                affiliatepress_clear_bebug_log(log_type){
                    const vm = this;
                    vm.is_display_loader = "1";
                    var postdata = [];
                    postdata.action = "affiliatepress_clear_debug_log";
                    postdata.affiliatepress_debug_log_selector = log_type;
                    postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify(postdata)).then(function(response){
                        vm.is_display_loader = "0";
                        vm.$notify({
                            title: response.data.title,
                            message: response.data.msg,
                            type: response.data.variant,
                            customClass: response.data.variant+"_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });
                    }).catch(function(error){
                        vm.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',  
                        });
                    });
                },            
                affiliatepess_download_log(log_type,selected_download_duration,download_log_daterange) {
                    const vm = this
                    vm.is_display_download_save_loader = "1";
                    vm.is_disabled= true;
                    var postdata = []
                    postdata.action = "affiliatepress_download_log";
                    postdata.affiliatepess_debug_log_selector = log_type;
                    postdata.affiliatepess_selected_download_duration = selected_download_duration;
                    if(selected_download_duration == "custom") {                    
                        postdata.affiliatepess_selected_download_custom_duration = download_log_daterange;
                    }
                    postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify(postdata)).then(function(response){                     
                        window.location.href = response.data.url;                    
                        vm.is_display_download_save_loader = "0";                       
                        vm.is_disabled= false;                    
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
                }, 
                affiliatepess_view_log(log_type, request_from="", log_name="",data_reset = "") {                                
                    const vm = this;
                    if(data_reset == "yes"){
                        vm.currentPage = 1;
                    }
                    vm.open_display_log_modal  = true;               
                    var postdata = []
                    
                    vm.is_display_loader_view = "1"
                    if( request_from != "pagination") {                        
                        vm.items = "";
                    }
                    vm.open_view_model_gateway_name = log_type;
                    if(log_name != ""){
                        vm.open_view_model_gateway = log_name;
                    }                
                    postdata.action = "affiliatepress_view_debug_log";
                    postdata.affiliatepress_debug_log_selector = log_type;
                    postdata.perpage=this.perPage,
                    postdata.currentpage=this.currentPage,
                    postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify(postdata))
                    .then(function(response){                    
                        vm.items = response.data.items;
                        vm.totalItems = parseInt(response.data.total);
                        vm.is_display_loader_view = "0";                                                            
                    }).catch(function(error){
                        console.log(error);
                        vm2.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',  
                        });
                    });
                },        
                settings_tab_select(selected_tab){ 
                    const vm = this;
                    
                    sessionStorage.setItem("selected_tab", selected_tab.index);
                    var current_tabname = selected_tab.props.name;
                    sessionStorage.setItem("current_affiliatpress_tabname", current_tabname);

                    if(current_tabname == "affiliate_settings"){
                        vm.getSettingsData("affiliate_settings", "affiliate_setting_form");                                        
                    }else if(current_tabname == "commissions_settings"){
                        vm.getSettingsData("commissions_settings", "commissions_setting_form");
                    }else if(current_tabname == "integrations_settings"){
                        vm.getSettingsData("integrations_settings", "integrations_setting_form");
                    }else if(current_tabname == "email_notification_settings"){
                        vm.getSettingsData("email_notification_settings", "notification_setting_form");
                    }else if(current_tabname == "debug_log_settings"){
                        vm.getSettingsData("debug_log_settings", "debug_log_setting_form");
                    }else if(current_tabname == "message_settings"){
                        vm.getSettingsData("message_settings", "messages_setting_form");
                    }else if(current_tabname == "appearance_settings"){
                        vm.getSettingsData("appearance_settings", "appearance_setting_form");
                    }
                    '.$affiliatepress_extra_settings_tab_dynamic_load_method.'


                },                
                getSettingsData(settingType, form_name){
                    const vm = this;
                    vm.is_disabled = true;
                    vm.ap_settings_content_loaded = "1";
                    let getSettingsDetails = {
                        "action": "affiliatepress_get_settings_details",
                        "setting_type": settingType,
                        "_wpnonce": "'.esc_html(wp_create_nonce("ap_wp_nonce")).'",
                    }
                    axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(getSettingsDetails))
                    .then(function(response){
                        vm.ap_settings_content_loaded = "0";
                        vm.ap_first_page_loaded = "0";
                        vm.is_disabled = false;
                        vm.is_display_loader = "0";
                        if(response.data.data != "" || response.data.data != []){
                            vm[form_name] = response.data.data;
                            '.$affiliatepress_get_settings_details_response.'
                        }
                    }).catch(function(error){
                        vm.ap_first_page_loaded = "0";
                        console.log(error)
                    });
                },
                saveIntegrationsSettingsData(){
                    const vm = this;              
                    var response_variant = vm.saveSettingsData("integrations_setting_form","integrations_settings", true);                
                },
                saveAffiliateSettingsData(){
                    const vm = this;              
                    var response_variant = vm.saveSettingsData("affiliate_setting_form","affiliate_settings", true);                
                },
                saveCommissionSettingsData(){
                    const vm = this;               
                    var response_variant = vm.saveSettingsData("commissions_setting_form","commissions_settings", true);                
                },
                saveEmailNotificationSettingsData(){
                    const vm = this;               
                    var response_variant = vm.saveSettingsData("notification_setting_form","email_notification_settings", true);                
                }, 
                '.$affiliatepress_extra_tab_save_settings.'
                saveMessageSettingsData(){
                    const vm = this;               
                    var response_variant = vm.saveSettingsData("messages_setting_form","message_settings", true);                
                }, 
                saveSettingsData(form_name,setting_type, display_save_msg = true){
                    const vm = this;
                    vm.$refs[form_name].validate((valid) => {
                        if(valid) {
                            vm.is_disabled = true;
                            vm.is_display_save_loader = "1";
                            let saveFormData = {};
                            saveFormData.all_settings = vm[form_name];
                            saveFormData.action = "affiliatepress_save_settings_data"; 
                            saveFormData.settingType = setting_type;
                            saveFormData._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'"
                            '.$affiliatepress_add_settings_more_postdata.'
                            axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(saveFormData))
                            .then(function(response){

                                if( true == display_save_msg || "error" == response.data.variant ){
                                    vm.$notify({                        
                                        title: response.data.title,
                                        message: response.data.msg,
                                        type: response.data.variant,
                                        customClass: response.data.variant+"_notification",
                                        duration:'.intval($affiliatepress_notification_duration).',
                                    });
                                }                    
                                vm.is_disabled = false;
                                vm.is_display_save_loader = "0";                                                 
                                vm.isloading = false;
                                '.$affiliatepress_settings_response.'                                                               
                            }).catch(function(error){
                                console.log(error)
                            });
                        }
                    });                
                },
                affiliatepress_send_test_email(){
                    const vm = this;
                    vm.$refs["notification_smtp_test_mail_form"].validate((valid) => {                        
                        if(valid) {
                            vm.is_disabled = true;
                            vm.is_display_send_test_mail_loader = "1";
                            vm.is_disable_send_test_email_btn = true;
                            var postdata = [];
                            postdata.action = "affiliatepress_send_test_email";
                            postdata.notification_formdata = vm.notification_setting_form;
                            postdata.notification_test_mail_formdata = vm.notification_smtp_test_mail_form;
                            postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";
                            axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify(postdata))
                            .then(function(response){
                                vm.is_disabled = false;
                                vm.is_display_send_test_mail_loader = "0";    
                                vm.is_disable_send_test_email_btn = false;
                                if(response.data.is_mail_sent == 1){
                                    vm.succesfully_send_test_email = 1;
                                    vm.error_send_test_email = 0;
                                    vm.smtp_mail_error_text = "";
                                    vm.error_text_of_test_email = "";
                                }else{
                                    vm.succesfully_send_test_email = 0;                                
                                    vm.error_send_test_email = 1;
                                    vm.error_text_of_test_email = response.data.error_msg;
                                    vm.smtp_mail_error_text = response.data.error_log_msg;
                                }
                                vm.$notify({
                                   title: response.data.title,
                                   message: response.data.error_msg,
                                   type: response.data.variant,
                                   customClass: response.data.variant+"_notification",
                                   duration:'.intval($affiliatepress_notification_duration).',
                                });
                            }).catch(function(error){
                                console.log(error);
                                vm2.$notify({
                                    title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                                    message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                                    type: "error",
                                    customClass: "error_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',  
                                });
                            });
                        }
                    })    
                },            
                affiliatepress_send_test_wpmail_email(){
                    const vm = this;
                    vm.$refs["notification_wpmail_test_mail_form"].validate( (valid) =>{
                        if( valid ){
                            vm.is_disabled_test = true;
                            vm.is_display_send_test_wpmail_mail_loader = "1";
                            vm.is_disable_send_test_wpmail_email_btn = true;
                            var postdata = []
                            postdata.action = "affiliatepress_send_test_wpmail_email"
                            postdata.notification_formdata = vm.notification_setting_form
                            postdata.notification_test_mail_formdata = vm.notification_wpmail_test_mail_form
                            postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";
                            axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify(postdata))
                            .then(function(response){
                                vm.is_disabled_test = false;
                                vm.is_display_send_test_wpmail_mail_loader = "0";   
                                vm.is_disable_send_test_wpmail_email_btn = false;
                                if(response.data.is_mail_sent == 1){
                                    vm.succesfully_send_test_wpmail_email = 1;
                                    vm.error_send_test_wpmail_email = 0;
                                    vm.wpmail_mail_error_text = "";
                                    vm.error_text_of_test_wpmail_email = "";
                                }else{
                                    vm.succesfully_send_test_wpmail_email = 0;                                
                                    vm.error_send_test_wpmail_email = 1;
                                    vm.error_text_of_test_wpmail_email = response.data.msg;
                                    vm.wpmail_mail_error_text = response.data.error_log_msg;
                                }
                                vm.$notify({
                                   title: response.data.title,
                                   message: response.data.msg,
                                   type: response.data.variant,
                                   customClass: response.data.variant+"_notification",
                                   duration:'.intval($affiliatepress_notification_duration).',
                                });
                            }).catch(function(error){
                                console.log(error);
                                vm2.$notify({
                                    title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                                    message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                                    type: "error",
                                    customClass: "error_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',  
                                });
                            });
                        }
                    });
                },  
                ap_reset_appearance_color(){
                    const vm = this;  
                    vm.is_display_reset_color_setting_loader = "1";
                    vm.is_disable_reset_color_setting_btn = true;
                    var postdata = [];       
                    postdata.action = "ap_reset_appearance_color";     
                    postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify(postdata)).then(function(response){   
                        vm.is_display_reset_color_setting_loader = "0";
                        vm.is_disable_reset_color_setting_btn = false;
                        vm.appearance_setting_form.primary_color = "#6858e0";
                        vm.appearance_setting_form.background_color = "#ffffff";
                        vm.appearance_setting_form.text_color = "#1A1E26";
                        vm.appearance_setting_form.content_color = "#576582";
                        vm.appearance_setting_form.font = "Poppins";
                        vm.appearance_setting_form.panel_background_color = "#ffffff";
                        vm.appearance_setting_form.border_color = "#C9CFDB";
                        vm.$notify({
                            title: response.data.title,
                            message: response.data.msg,
                            type: response.data.variant,
                            customClass: response.data.variant+"_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });                        
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                },
                ispaymentNumberValidate(evt) {
                    const vm = this;
                    const regex = /^(\d{1,3}(,\d{3})*|\d+)?(\.\d*)?$/;
                    if (regex.test(evt)) {
                        vm.inputValue = evt; 
                    } else {
                        vm.commissions_setting_form.minimum_payment_amount = vm.inputValue;
                    }
                },
                affiliatepress_beforeEnter(el) {
                    el.style.height = "0";
                    el.style.opacity = "0";
                },
                affiliatepress_enter(el, done) {
                    const height = el.scrollHeight;
                    el.style.transition = "all 0.4s ease";
                    el.style.height = (height + 26) + "px";
                    el.style.opacity = "1";
                    setTimeout(() => {
                    el.style.height = "auto";
                    done();
                    }, 400);
                },
                affiliatepress_leave(el, done) {
                    el.style.height = el.scrollHeight + "px";
                    el.offsetHeight; 
                    el.style.transition = "all 0.4s ease";
                    el.style.height = "0";
                    el.style.opacity = "0";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    setTimeout(() => done(), 400);
                },
                ap_active_integration(integration,integration_settings) {
                    const vm = this;  
                    this.$nextTick(() => {
                        const el = document.getElementById(`ap-enable-integration-${integration}`);
                        if (el) {
                            const rect = el.getBoundingClientRect();
                            const fullyVisible =
                            rect.top >= 120 &&
                            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight);
                            if (!fullyVisible) {
                                const defaultOffset = 120;
                                const elementPosition = el.getBoundingClientRect().top + window.pageYOffset;
                                const offsetPosition = elementPosition - defaultOffset;
                                window.scrollTo({
                                    top: offsetPosition,
                                    behavior: "smooth"
                                });
                            }
                        }
                    });
                    vm.affiliate_integration_active_list.push(integration_settings);

                    const index = vm.affiliate_integration_inactive_list.indexOf(integration_settings);
                    if (index > -1) {
                        vm.affiliate_integration_inactive_list.splice(index, 1);
                    }
                },
                ap_deactive_integration(integration, integration_settings) {
                    const vm = this;

                    const index = vm.affiliate_integration_active_list.indexOf(integration_settings);
                    if (index > -1) {
                        vm.affiliate_integration_active_list.splice(index, 1);
                    }

                    vm.affiliate_integration_inactive_list.push(integration_settings);
                },
                affiliatepress_get_page_url(affiliate_page_id,type){
                    const vm = this;  
                    var postdata = [];       
                    postdata.action = "affiliatepress_get_page_url";    
                    postdata.affiliate_type = type; 
                    postdata.affiliate_page_id = affiliate_page_id; 
                    postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify(postdata)).then(function(response){   
                        if(response.data.variant == "success"){
                            vm.affiliate_account_page_url = response.data.affiliate_account_page_url;
                            vm.affiliate_register_page_url = response.data.affiliate_register_page_url;
                        }
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                },
            ';

            $affiliatepress_settings_dynamic_vue_methods = apply_filters('affiliatepress_settings_add_dynamic_vue_methods', $affiliatepress_settings_dynamic_vue_methods); 

            return $affiliatepress_settings_dynamic_vue_methods;        
        }
        
        /**
         * Function for dynamic View load
         *
         * @return HTML
        */
        function affiliatepress_settings_dynamic_view_load_func(){

            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/settings/manage_settings.php';
            $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_settings_view_file_path', $affiliatepress_load_file_name);
            include $affiliatepress_load_file_name;

        }
        
        /**
         * Function for reset color option
         *
         * @return json
        */
        function affiliatepress_ap_reset_appearance_color_func(){
            global $AffiliatePress;

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'save_reset_appearance_color', true, 'ap_wp_nonce' ); 

            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');

            if( preg_match('/error/', $affiliatepress_ap_check_authorization)){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_settings')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }

            $AffiliatePress->affiliatepress_update_settings('primary_color', 'appearance_settings', '#6858e0');
            $AffiliatePress->affiliatepress_update_settings('background_color', 'appearance_settings','#ffffff');
            $AffiliatePress->affiliatepress_update_settings('panel_background_color', 'appearance_settings', '#ffffff');
            $AffiliatePress->affiliatepress_update_settings('text_color', 'appearance_settings','#1A1E26');
            $AffiliatePress->affiliatepress_update_settings('content_color', 'appearance_settings','#576582');          
            $AffiliatePress->affiliatepress_update_settings('border_color', 'appearance_settings','#C9CFDB'); 
            $AffiliatePress->affiliatepress_update_settings('font', 'appearance_settings','Poppins');
            
            $AffiliatePress->affiliatepress_update_all_auto_load_settings();

            $response['variant'] = 'success';
            $response['title'] = esc_html__('Success',  'affiliatepress-affiliate-marketing');
            $response['msg'] = esc_html__('Configuration reset successfully.','affiliatepress-affiliate-marketing');

            echo json_encode($response);
            exit;            
        }

         /**
         * Function for reset color option
         *
         * @return json
        */
        function affiliatepress_get_page_url_func(){
            global $AffiliatePress;

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'get_page_url', true, 'ap_wp_nonce' ); 

            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');

            if( preg_match('/error/', $affiliatepress_ap_check_authorization)){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_settings')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');            
            if (!$affiliatepress_verify_nonce_flag){
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                exit();
            }

            $affiliatepress_page_id = isset($_POST['affiliate_page_id']) ? intval($_POST['affiliate_page_id']) : 0;
            $affiliatepress_page_type = isset($_POST['affiliate_type']) ? sanitize_text_field(wp_unslash($_POST['affiliate_type'])) : '';

            if(!empty($affiliatepress_page_id)){

                if($affiliatepress_page_type == "account"){
                    $response['affiliate_account_page_url'] = get_permalink($affiliatepress_page_id);
                }else{
                    $response['affiliate_register_page_url'] =  get_permalink($affiliatepress_page_id);
                }
                
                $response['variant'] = 'success';
                $response['title'] = esc_html__( 'Success', 'affiliatepress-affiliate-marketing');
                $response['msg'] =  esc_html__( 'AffiliatePress page link get.', 'affiliatepress-affiliate-marketing');
                
            }else{
                $response['variant'] = 'error';
                $response['title'] = esc_html__('Error',  'affiliatepress-affiliate-marketing');
                $response['msg'] = esc_html__('Page Not Found','affiliatepress-affiliate-marketing');
            }

            echo json_encode($response);
            exit;            
        }
    }
}
global $affiliatepress_settings;
$affiliatepress_settings = new affiliatepress_settings();
