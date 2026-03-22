<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_email_notifications') ) {
    class affiliatepress_email_notifications Extends affiliatepress_Core
    {
        var $affiliatepress_email_notification_type = '';
        var $affiliatepress_email_sender_name       = '';
        var $affiliatepress_email_sender_email      = '';
        var $affiliatepress_admin_email             = '';
        var $affiliatepress_smtp_username           = '';
        var $affiliatepress_smtp_password           = '';
        var $affiliatepress_smtp_host               = '';
        var $affiliatepress_smtp_port               = '';
        var $affiliatepress_smtp_secure             = '';
        var $affiliatepress_gmail_client_id         = '';
        var $affiliatepress_gmail_client_secret     = '';
        var $affiliatepress_gmail_auth_token        = '';
        var $affiliatepress_response_email          = '';
        var $affiliatepress_template_type           = '';

        function __construct(){
            
            /*  Get Dynamic Variable  */
            add_filter('affiliatepress_notification_dynamic_variable', array( $this, 'affiliatepress_notification_dynamic_variable_func' ), 10, 5);
            
            /*  For replace dynamic variable  */
            add_filter('affiliatepress_notification_dynamic_variable_replace', array( $this, 'affiliatepress_notification_dynamic_variable_replace_func' ), 10, 2);
            
        }
        
        /**
         * Initialize email configurations
         *
         * @return void
         */
        function affiliatepress_init_email_config(){

            global $AffiliatePress, $affiliatepress_other_debug_log_id;

            $this->affiliatepress_email_notification_type = esc_html($AffiliatePress->affiliatepress_get_settings('selected_mail_service', 'email_notification_settings'));
            $this->affiliatepress_email_sender_name       = stripslashes_deep($AffiliatePress->affiliatepress_get_settings('sender_name', 'email_notification_settings'));
            $this->affiliatepress_email_sender_email      = esc_html($AffiliatePress->affiliatepress_get_settings('sender_email', 'email_notification_settings'));

            if ($this->affiliatepress_email_notification_type == 'smtp' ) {
                $this->affiliatepress_smtp_username = esc_html($AffiliatePress->affiliatepress_get_settings('smtp_username', 'email_notification_settings'));
                $this->affiliatepress_smtp_password = $AffiliatePress->affiliatepress_get_settings('smtp_password', 'email_notification_settings');
                $this->affiliatepress_smtp_host     = $AffiliatePress->affiliatepress_get_settings('smtp_host', 'email_notification_settings');
                $this->affiliatepress_smtp_port     = esc_html($AffiliatePress->affiliatepress_get_settings('smtp_port', 'email_notification_settings'));
                $this->affiliatepress_smtp_secure   = esc_html($AffiliatePress->affiliatepress_get_settings('smtp_secure', 'email_notification_settings'));
            }

            

            $affiliatepress_debug_log_data = "Notification type => {$this->affiliatepress_email_notification_type} | Sender name => {$this->affiliatepress_email_sender_name} | Sender Email => {$this->affiliatepress_email_sender_email} | SMTP Username => {$this->affiliatepress_smtp_username} | SMTP Password => {$this->affiliatepress_smtp_password} | SMTP Host => {$this->affiliatepress_smtp_host} | SMTP Port => {$this->affiliatepress_smtp_port} | SMTP Secure => {$this->affiliatepress_smtp_secure}";

            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Init Email Configuration', 'affiliatepress_email_notiifcation', $affiliatepress_debug_log_data, $affiliatepress_other_debug_log_id);
        }
        
        /**
         * Function for send test email notification
         *
         * @param  string $affiliatepress_smtp_host
         * @param  string $affiliatepress_smtp_port
         * @param  string $affiliatepress_smtp_secure
         * @param  string $affiliatepress_smtp_username
         * @param  string $affiliatepress_smtp_password
         * @param  string $affiliatepress_smtp_test_receiver_email
         * @param  string $affiliatepress_smtp_test_msg
         * @param  string $affiliatepress_smtp_sender_email
         * @param  string $affiliatepress_smtp_sender_name
         * @return json
         */
        function affiliatepress_send_test_email_notification( $affiliatepress_smtp_host, $affiliatepress_smtp_port, $affiliatepress_smtp_secure, $affiliatepress_smtp_username, $affiliatepress_smtp_password, $affiliatepress_smtp_test_receiver_email, $affiliatepress_smtp_test_msg, $affiliatepress_smtp_sender_email, $affiliatepress_smtp_sender_name )
        {
            global $wpdb, $wp_version, $affiliatepress_other_debug_log_id;

            $affiliatepress_debug_log_args_data = func_get_args();
            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Test email notification arguments data', 'affiliatepress_email_notiifcation', $affiliatepress_debug_log_args_data, $affiliatepress_other_debug_log_id);

            $affiliatepress_is_mail_sent     = 0;
            $return_error_msg = esc_html__('SMTP Test Email cannot sent successfully', 'affiliatepress-affiliate-marketing');
            $return_error_log = '';

            if (! empty($affiliatepress_smtp_host) && ! empty($affiliatepress_smtp_port) && ! empty($affiliatepress_smtp_secure) && ! empty($affiliatepress_smtp_username) && ! empty($affiliatepress_smtp_password) && ! empty($affiliatepress_smtp_test_receiver_email) && ! empty($affiliatepress_smtp_test_msg) && ! empty($affiliatepress_smtp_sender_email) && ! empty($affiliatepress_smtp_sender_name) ) {
                if (version_compare($wp_version, '5.5', '<') ) {
                    include_once ABSPATH . WPINC . '/class-phpmailer.php';
                    include_once ABSPATH . WPINC . '/class-smtp.php';
                    $affiliatepressMailer = new PHPMailer();
                } else {
                    include_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                    include_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                    include_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                    $affiliatepressMailer = new PHPMailer\PHPMailer\PHPMailer();
                }

                $affiliatepressMailer->CharSet   = 'UTF-8';
                $affiliatepressMailer->SMTPDebug = 1; // change this value to 1 for debug
                ob_start();
                echo '<span class="ap-smtp-notification-error-msg">';
             // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                echo addslashes(esc_html__('The SMTP debugging output is shown below:', 'affiliatepress-affiliate-marketing'));
                echo '</span><pre>';
                $affiliatepressMailer->isSMTP();
                $affiliatepressMailer->Host     = $affiliatepress_smtp_host;
                $affiliatepressMailer->SMTPAuth = true;
                $affiliatepressMailer->Username = $affiliatepress_smtp_username;
                $affiliatepressMailer->Password = $affiliatepress_smtp_password;
                if (! empty($affiliatepress_smtp_secure) && $affiliatepress_smtp_secure != 'Disabled' ) {
                    $affiliatepressMailer->SMTPSecure = strtolower($affiliatepress_smtp_secure);
                }
                if ($affiliatepress_smtp_secure == 'Disabled' ) {
                    $affiliatepressMailer->SMTPAutoTLS = false;
                }
                $affiliatepressMailer->Port = $affiliatepress_smtp_port;
                $affiliatepressMailer->setFrom($affiliatepress_smtp_sender_email, $affiliatepress_smtp_sender_name);
                $affiliatepressMailer->addReplyTo($affiliatepress_smtp_sender_email, $affiliatepress_smtp_sender_name);
                $affiliatepressMailer->addAddress($affiliatepress_smtp_test_receiver_email);
                $affiliatepressMailer->isHTML(true);
                $affiliatepress_email_subject  = esc_html__('AffiliatePress SMTP Test Email Notification', 'affiliatepress-affiliate-marketing');
                $affiliatepressMailer->Subject = $affiliatepress_email_subject;
                $affiliatepressMailer->Body    = $affiliatepress_smtp_test_msg;

                if (! $affiliatepressMailer->send() ) {
                    echo '</pre><span class="ap-dialog--sns__body--error-title">';
                 // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                    echo addslashes(esc_html__('The full debugging output is shown below:', 'affiliatepress-affiliate-marketing'));
                    echo '</span>';                   
                    $affiliatepress_smtp_debug_log    = ob_get_clean();
                    $return_error_log .= '<pre>';
                    $return_error_log .= $affiliatepress_smtp_debug_log;
                    $return_error_log .= '</pre>';
                    $return_error_msg  = $affiliatepressMailer->ErrorInfo;
                } else {
                    $affiliatepress_smtp_debug_log   = ob_get_clean();
                    $affiliatepress_is_mail_sent     = 1;
                    $return_error_msg = '';
                }
            }

            $return_msg = array(
                'is_mail_sent'  => $affiliatepress_is_mail_sent,
                'error_msg'     => $return_error_msg,
                'error_log_msg' => $return_error_log,
            );

            if($affiliatepress_is_mail_sent){
                $return_msg['variant']        = 'success';
                $return_msg['title']          = esc_html__('Success', 'affiliatepress-affiliate-marketing');
            }else{
                $return_msg['variant']        = 'error';
                $return_msg['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            }

            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Test email notification send response', 'affiliatepress_email_notiifcation', $return_msg, $affiliatepress_other_debug_log_id);

            echo wp_json_encode($return_msg);
            exit;
        }

        
        /**
         * Function for send test gmail-email notification
         *
         * @param  integer $affiliatepress_gmail_client_id
         * @param  string $affiliatepress_gmail_client_secret
         * @param  string $affiliatepress_gmail_auth_secret
         * @param  string $affiliatepress_gmail_test_receiver_email
         * @param  string $affiliatepress_gmail_test_msg
         * @param  string $affiliatepress_gmail_connect
         * @param  string $affiliatepress_gmail_auth
         * @param  string $affiliatepress_gmail_sender_email
         * @param  string $affiliatepress_gmail_sender_name
         * @return json
         */
        function affiliatepress_send_test_gmail_notification( $affiliatepress_gmail_client_id, $affiliatepress_gmail_client_secret, $affiliatepress_gmail_auth_secret, $affiliatepress_gmail_test_receiver_email, $affiliatepress_gmail_test_msg, $affiliatepress_gmail_connect, $affiliatepress_gmail_auth, $affiliatepress_gmail_sender_email, $affiliatepress_gmail_sender_name )
        {
            global $wpdb, $AffiliatePress, $wp_version, $affiliatepress_other_debug_log_id;

            $affiliatepress_debug_log_args_data = func_get_args();
            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Test G-mail notification arguments data', 'affiliatepress_email_notiifcation', $affiliatepress_debug_log_args_data, $affiliatepress_other_debug_log_id);

            $affiliatepress_is_mail_sent     = 0;
            $return_error_msg = esc_html__('Gmail Test Email cannot sent successfully', 'affiliatepress-affiliate-marketing');
            $return_error_log = '';

            require_once AFFILIATEPRESS_LIBRARY_DIR . "/gmail/vendor/autoload.php";

            $affiliatepress_redirect_url = get_home_url() .'?page=affiliatepress_gmailapi';

            $affiliatepress_gmail_auth = stripslashes_deep( $affiliatepress_gmail_auth );
            $affiliatepress_gmail_oauth_data = json_decode( $affiliatepress_gmail_auth, true);

            $affiliatepress_client = new Google_Client();
            $affiliatepress_client->setClientId($affiliatepress_gmail_client_id);
            $affiliatepress_client->setClientSecret( $affiliatepress_gmail_client_secret );
            $affiliatepress_client->setRedirectUri( $affiliatepress_redirect_url);
            $affiliatepress_client->setAccessToken( $affiliatepress_gmail_oauth_data );
            
            /** Refresh Google API Token */
            if( $affiliatepress_client->isAccessTokenExpired() ){
                $affiliatepress_is_refreshed = $affiliatepress_client->refreshToken( $affiliatepress_gmail_oauth_data['refresh_token'] );

                if( !empty( $affiliatepress_is_refreshed['error'] ) ){

                    global $affiliatepress_other_debug_log_id;
                    $refreshed_token_err = $affiliatepress_is_refreshed['error'];
                    do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'failed to refresh token', 'affiliatepress_email_notiifcation', $refreshed_token_err, $affiliatepress_other_debug_log_id);
                    $AffiliatePress->affiliatepress_update_settings('affiliatepress_gmail_invalid_auth_token', 'notification_setting', 1);
                    return false;
                }
                $refresh_token = $affiliatepress_gmail_oauth_data['refresh_token'];

                if( empty( $affiliatepress_gmail_oauth_data['refresh_token'] ) ){
                    echo "inside this refresh token empty condition";
                    $affiliatepress_gmail_oauth_data['refresh_token'] = $refresh_token;
                }
                
                $AffiliatePress->affiliatepress_update_settings('affiliatepress_gmail_auth', 'notification_setting',wp_json_encode($affiliatepress_gmail_oauth_data));
                $AffiliatePress->affiliatepress_update_settings('affiliatepress_gmail_invalid_auth_token', 'notification_setting', 0);
                $affiliatepress_client->setAccessToken( $affiliatepress_gmail_oauth_data );
            } else {
                
                
                
            }

            $affiliatepress_service = new Google\Service\Gmail( $affiliatepress_client );

            $affiliatepress_user = 'me';
            $affiliatepress_subjectCharset = $affiliatepress_charset = 'utf-8';
            $affiliatepress_strSubject = esc_html__('affiliatepress Gmail Test Email Notification', 'affiliatepress-affiliate-marketing');

            $affiliatepress_gmail_sent_data = 'From: =?' . $affiliatepress_subjectCharset . '?B?' . base64_encode($affiliatepress_gmail_sender_name)."?= <".$affiliatepress_gmail_sender_email.">\r\n";
            $affiliatepress_gmail_sent_data .= "To: ".$affiliatepress_gmail_test_receiver_email."\r\n";
            $affiliatepress_gmail_sent_data .= 'Subject: =?' . $affiliatepress_subjectCharset . '?B?' . base64_encode($affiliatepress_strSubject) . "?=\r\n";
            $affiliatepress_gmail_sent_data .= "MIME-Version: 1.0\r\n";
            $affiliatepress_gmail_sent_data .= "Content-Type: text/html; charset=utf-8\r\n";
            $affiliatepress_gmail_sent_data .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
            $affiliatepress_gmail_test_msg = chunk_split( base64_encode( $affiliatepress_gmail_test_msg ) );
            $affiliatepress_gmail_sent_data .= "".$affiliatepress_gmail_test_msg."\r\n";

            $affiliatepress_mime = rtrim(strtr(base64_encode($affiliatepress_gmail_sent_data), '+/', '-_'), '=');
            $affiliatepress_msg = new Google_Service_Gmail_Message();
            $affiliatepress_msg->setRaw($affiliatepress_mime);

            $return_error_msg = esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing');
            try {
                $affiliatepress_message = $affiliatepress_service->users_messages->send('me', $affiliatepress_msg);
                $affiliatepress_is_mail_sent = 1;
                $return_error_msg = '';
                do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Send Test Email notification GMail success response', 'affiliatepress_email_notiifcation', $affiliatepress_is_mail_sent, $affiliatepress_other_debug_log_id);
            } catch (Exception $e) {
                $return_error_msg = 'An error occurred: ' . $e->getMessage();
                do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Send Test Email notification GMail error response', 'affiliatepress_email_notiifcation', wp_json_encode( $e ), $affiliatepress_other_debug_log_id);
            }

            $return_msg = array(
                'is_mail_sent'  => $affiliatepress_is_mail_sent,
                'error_msg'     => $return_error_msg,
                'error_log_msg' => $return_error_log,
            );

           do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Test G-mail notification send response', 'affiliatepress_email_notiifcation', $return_msg, $affiliatepress_other_debug_log_id);

           echo wp_json_encode($return_msg);
           exit;
        }
        
                
        /**
         * Function for replace notification data
         *
         * @param  mixed $affiliatepress_notification_data
         * @param  mixed $affiliatepress_notification_dynamic_variable
         * @return string
        */
        function affiliatepress_notification_dynamic_variable_replace_func($affiliatepress_notification_data,$affiliatepress_notification_dynamic_variable){
            if(!empty($affiliatepress_notification_dynamic_variable)){
                foreach($affiliatepress_notification_dynamic_variable as $affiliatepress_key=>$affiliatepress_dynamic_variable){
                    if(!empty($affiliatepress_key)){
                        $affiliatepress_notification_data = str_replace('%'.$affiliatepress_key.'%',$affiliatepress_dynamic_variable,$affiliatepress_notification_data);
                    }                    
                }
            }            
            return $affiliatepress_notification_data;
        }
        
        
        /**
         * Function for get dynamic notification variable
         *
         * @param  array $affiliatepress_notification_dynamic_variable
         * @param  string $affiliatepress_notification_slug
         * @param  string $affiliatepress_notification_type
         * @param  integer $affiliatepress_notification_data_id
         * @param  string $affiliatepress_get_all_template_data
         * @return array
        */
        function affiliatepress_notification_dynamic_variable_func($affiliatepress_notification_dynamic_variable, $affiliatepress_notification_slug,$affiliatepress_notification_type,$affiliatepress_notification_data_id,$affiliatepress_get_all_template_data){

            global $affiliatepress_tbl_ap_affiliates, $wpdb, $AffiliatePress, $affiliatepress_affiliates, $affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_payments,$affiliatepress_tbl_ap_payouts;

            $affiliatepress_company_name = esc_html($AffiliatePress->affiliatepress_get_settings('company_name', 'email_notification_settings'));
            $affiliatepress_notification_dynamic_variable['company_name'] = $affiliatepress_company_name;

            if($affiliatepress_notification_type == 'affiliate' || $affiliatepress_notification_type == 'commission' || $affiliatepress_notification_type == 'payment'){

                $affiliatepress_affiliates_id = (isset($affiliatepress_notification_data_id['ap_affiliates_id']))?$affiliatepress_notification_data_id['ap_affiliates_id']:'';
                if(!empty($affiliatepress_affiliates_id)){

                    $affiliatepress_user_table = $this->affiliatepress_tablename_prepare($wpdb->users); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->users contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                    $wp_usermeta_table = $this->affiliatepress_tablename_prepare($wpdb->usermeta); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->usermeta contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                    $affiliatepress_tbl_ap_affiliates_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

                    $affiliatepress_where_clause = " Where 1 = 1 ";
                    $affiliatepress_where_clause.= $wpdb->prepare( " AND affiliate.ap_affiliates_id = %d", intval($affiliatepress_affiliates_id) );
                    
                    $affiliatepress_affiliates_record    = $wpdb->get_row("SELECT affiliate.*,affiliate.ap_affiliates_user_id as ID, affiliate.ap_affiliates_user_email as user_email, affiliate.ap_affiliates_first_name, affiliate.ap_affiliates_last_name  FROM {$affiliatepress_tbl_ap_affiliates_temp} as affiliate  {$affiliatepress_where_clause} ", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates_temp is a table name. false alarm

                    if(!empty($affiliatepress_affiliates_record)){

                        $affiliatepress_notification_dynamic_variable['affiliate_id'] = (isset($affiliatepress_affiliates_record['ap_affiliates_id']))?$affiliatepress_affiliates_record['ap_affiliates_id']:'';
                        $affiliatepress_notification_dynamic_variable['affiliate_referral_url'] = $AffiliatePress->affiliatepress_get_affiliate_common_link($affiliatepress_notification_dynamic_variable['affiliate_id']);


                        $user_id = (!empty($affiliatepress_affiliates_record['ap_affiliates_user_id']))?stripslashes_deep($affiliatepress_affiliates_record['ap_affiliates_user_id']):0; 

                        $affiliatepress_first_name =  (!empty($affiliatepress_affiliates_record['ap_affiliates_first_name']))?stripslashes_deep($affiliatepress_affiliates_record['ap_affiliates_first_name']):""; 
                        $affiliatepress_last_name  =  (!empty($affiliatepress_affiliates_record['ap_affiliates_last_name']))?stripslashes_deep($affiliatepress_affiliates_record['ap_affiliates_last_name']):"";


                        
                        $affiliatepress_full_name = $affiliatepress_first_name.' '.$affiliatepress_last_name;
                        
                        

                        $affiliatepress_notification_dynamic_variable['affiliate_username'] = (isset($affiliatepress_affiliates_record['ap_affiliates_user_name']))?$affiliatepress_affiliates_record['ap_affiliates_user_name']:'';
                        $affiliatepress_notification_dynamic_variable['affiliate_email'] = (isset($affiliatepress_affiliates_record['ap_affiliates_user_email']))?$affiliatepress_affiliates_record['ap_affiliates_user_email']:'';
                        $affiliatepress_notification_dynamic_variable['affiliate_first_name'] = $affiliatepress_first_name;
                        $affiliatepress_notification_dynamic_variable['affiliate_last_name'] = $affiliatepress_last_name;
                        $affiliatepress_notification_dynamic_variable['affiliate_website'] = (isset($affiliatepress_affiliates_record['ap_affiliates_website']))?$affiliatepress_affiliates_record['ap_affiliates_website']:'';
                        $affiliatepress_affiliate_status = (isset($affiliatepress_affiliates_record['ap_affiliates_status']))?$affiliatepress_affiliates_record['ap_affiliates_status']:'';
                        $affiliatepress_all_affiliate_status = $affiliatepress_affiliates->affiliatepress_all_affiliates_status();
                        foreach($affiliatepress_all_affiliate_status as $affiliatepress_all_status){
                            if($affiliatepress_all_status['value'] == $affiliatepress_affiliate_status){
                                $affiliatepress_affiliate_status = $affiliatepress_all_status['label']; 
                                break;
                            }
                        }
                        $affiliatepress_notification_dynamic_variable['affiliate_status'] = $affiliatepress_affiliate_status;
                        $affiliatepress_notification_dynamic_variable['promote_us'] = (isset($affiliatepress_affiliates_record['ap_affiliates_promote_us']))?$affiliatepress_affiliates_record['ap_affiliates_promote_us']:'';
                        
                    }



                }

            }
            if($affiliatepress_notification_type == 'commission'){
                
                $affiliatepress_commission_id = (isset($affiliatepress_notification_data_id['ap_commission_id']))?intval($affiliatepress_notification_data_id['ap_commission_id']):0;
                $affiliatepress_commission_record = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'ap_commission_source, ap_commission_amount', 'WHERE ap_commission_id = %d', array( $affiliatepress_commission_id ), '', '', '', false, true,ARRAY_A);

                $affiliatepress_commission_source = (isset($affiliatepress_commission_record['ap_commission_amount']))?floatval($affiliatepress_commission_record['ap_commission_amount']):0; 
                $affiliatepress_formated_commission_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_commission_source);

                $affiliatepress_commission_source = (isset($affiliatepress_commission_record['ap_commission_source']))?$affiliatepress_commission_record['ap_commission_source']:'';
                $affiliatepress_source_plugin_name = $AffiliatePress->affiliatepress_get_supported_addon_name($affiliatepress_commission_source);

                $affiliatepress_notification_dynamic_variable['commission_amount'] = $affiliatepress_formated_commission_amount;
                $affiliatepress_notification_dynamic_variable['commission_reference'] = $affiliatepress_source_plugin_name;
                $affiliatepress_notification_dynamic_variable['commission_id'] = $affiliatepress_commission_id;

            }
            if($affiliatepress_notification_type == 'payment'){

                $affiliatepress_payment_id = (isset($affiliatepress_notification_data_id['ap_payment_id']))?intval($affiliatepress_notification_data_id['ap_payment_id']):0;
                $affiliatepress_payment_record = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'ap_payment_id, ap_payment_amount, ap_payment_method ,ap_payout_id', 'WHERE ap_payment_id = %d', array( $affiliatepress_payment_id ), '', '', '', false, true,ARRAY_A);

                $affiliatepress_payout_id = (isset($affiliatepress_payment_record['ap_payout_id']))?intval($affiliatepress_payment_record['ap_payout_id']):0;

                $affiliatepress_payout_up_to_date_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payouts, 'ap_payout_upto_date', 'WHERE ap_payout_id = %d', array( $affiliatepress_payout_id ), '', '', '', false, true,ARRAY_A);

                $affiliatepress_payout_up_to_date = isset($affiliatepress_payout_up_to_date_data['ap_payout_upto_date']) ? $affiliatepress_payout_up_to_date_data['ap_payout_upto_date'] : '';

                $affiliatepress_payment_id = (isset($affiliatepress_payment_record['ap_payment_id']))?intval($affiliatepress_payment_record['ap_payment_id']):0;
                $affiliatepress_payment_amount = (isset($affiliatepress_payment_record['ap_payment_amount']))?floatval($affiliatepress_payment_record['ap_payment_amount']):0;
                $affiliatepress_payment_method = (isset($affiliatepress_payment_record['ap_payment_method']))?$affiliatepress_payment_record['ap_payment_method']:'';
                
                $affiliatepress_ap_formated_payment_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_payment_amount);

                $affiliatepress_notification_dynamic_variable['payment_id'] = $affiliatepress_payment_id;
                $affiliatepress_notification_dynamic_variable['payment_amount'] = $affiliatepress_ap_formated_payment_amount;
                $affiliatepress_notification_dynamic_variable['payment_payout_method'] = $affiliatepress_payment_method;         
                $affiliatepress_notification_dynamic_variable['payment_upto_date'] =  $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_payout_up_to_date);   

            }


            return $affiliatepress_notification_dynamic_variable;
        }


        
        /**
         * Function for send email notification 
         *
         * @param  html $affiliatepress_email_subject
         * @param  html $affiliatepress_email_content
         * @param  array $receiver_data_arr
         * @param  array $reply_to_data_arr
         * @param  array $affiliatepress_cc_emails
         * @param  boolean $affiliatepress_force
         * @param  array $affiliatepress_notification_dynamic_variable
         * @param  array $affiliatepress_get_template_data
         * @return boolean
       */
        function affiliatepress_send_email($affiliatepress_email_subject,$affiliatepress_email_content,$receiver_data_arr = array(),$reply_to_data_arr = array(), $affiliatepress_cc_emails = array(), $affiliatepress_force = false,$affiliatepress_notification_dynamic_variable = array(),$affiliatepress_get_template_data = array()){

            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_notifications, $wp_version, $affiliatepress_other_debug_log_id, $affiliatepress_settings;

            $affiliatepress_attachments = array();
            $affiliatepress_email_reply_to_name  = $reply_to_data_arr['affiliatepress_email_reply_to_name'];
            $affiliatepress_email_reply_to_email = $reply_to_data_arr['affiliatepress_email_reply_to_email'];            
            if(empty($affiliatepress_email_reply_to_name)) {
                $affiliatepress_email_reply_to_name=$this->affiliatepress_email_sender_name;
            }
            if(empty($affiliatepress_email_reply_to_email)) {
                $affiliatepress_email_reply_to_email=$this->affiliatepress_email_sender_email;
            }

            $receiver_email_id = (isset($receiver_data_arr['receiver_email']))?$receiver_data_arr['receiver_email']:'';
            $affiliatepress_email_send_res = array(
                'is_mail_sent'   => 0,
                'configurations' => array(
                'notification_type' => $this->affiliatepress_email_notification_type,
                'sender_name'       => $this->affiliatepress_email_sender_name,
                'sender_email'      => $this->affiliatepress_email_sender_email,
                'smtp_username'     => base64_encode($this->affiliatepress_smtp_password),
                'smtp_host'         => $this->affiliatepress_smtp_host,
                'smtp_port'         => $this->affiliatepress_smtp_port,
                'smpt_secure'       => $this->affiliatepress_smtp_secure,
                'gmail_client_id'   => $this->affiliatepress_gmail_client_id,
                'gmail_client_secret' => $this->affiliatepress_gmail_client_secret,
                'gmail_auth_token'   => $this->affiliatepress_gmail_auth_token,
                'gmail_connected_email' => $this->affiliatepress_response_email,
                ),
                'error_response' => 'Something went wrong while sending email notification',
                'posted_data'    => array(),
            );            
            
            switch ( $this->affiliatepress_email_notification_type ) {
                case 'php_mail':

                    include_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';

                    $affiliatepress_phpmailer_version = PHPMailer\PHPMailer\PHPMailer::VERSION;

                    $affiliatepress_email_header_data = 'Date: '.date('D, j M Y H:i:s O', current_time('timestamp') )."\r\n"; // phpcs:ignore
                    $affiliatepress_email_header_data .= 'From: ' . $this->affiliatepress_email_sender_name . '<' . $this->affiliatepress_email_sender_email . "> \r\n";
                    $affiliatepress_email_header_data .= 'Reply-To: ' . $affiliatepress_email_reply_to_name . '<' . $affiliatepress_email_reply_to_email . "> \r\n";
                    $affiliatepress_email_header_data .= 'Message-ID: '. sprintf('<%s@%s>', $this->affiliatepress_generate_random_msgid(), $this->affiliatepress_get_host_name())."\r\n";
                    $affiliatepress_email_header_data .= 'X-Mailer: PHPMailer ' . $affiliatepress_phpmailer_version . " (https://github.com/PHPMailer/PHPMailer)\r\n";
                    $affiliatepress_email_header_data .= "MIME-Version: 1.0\r\n";
                    $affiliatepress_email_header_data .= "Content-Type: text/html; charset=UTF-8\r\n";
                    
                    if(!empty($affiliatepress_cc_emails) && is_array($affiliatepress_cc_emails)){
                        $affiliatepress_email_header_data .= "Cc: ".implode(',', $affiliatepress_cc_emails)."\r\n";
                    }

                    $affiliatepress_attachments = array();

                    if( !empty( $affiliatepress_attachments ) ){
                        $affiliatepress_attachment_id = wp_rand(100,999);
                        
                        $affiliatepress_boundary = md5( $affiliatepress_attachment_id.'_'.current_time('timestamp') );

                        $affiliatepress_email_header_data = 'Date: '.date('D, j M Y H:i:s O', current_time('timestamp') )."\r\n";// phpcs:ignore
                        $affiliatepress_email_header_data .= 'From: ' . $this->affiliatepress_email_sender_name . '<' . $this->affiliatepress_email_sender_email . "> \r\n";
                        $affiliatepress_email_header_data .= 'Reply-To: ' . $affiliatepress_email_reply_to_name . '<' . $affiliatepress_email_reply_to_email . "> \r\n";
                        $affiliatepress_email_header_data .= 'Message-ID: '. sprintf('<%s@%s>', $this->affiliatepress_generate_random_msgid(), $this->affiliatepress_get_host_name())."\r\n";
                        $affiliatepress_email_header_data .= 'X-Mailer: PHPMailer ' . $affiliatepress_phpmailer_version . " (https://github.com/PHPMailer/PHPMailer)\r\n";
                        if(!empty($affiliatepress_cc_emails) && is_array($affiliatepress_cc_emails)){
                            $affiliatepress_email_header_data .= "Cc: ".implode(',', $affiliatepress_cc_emails)."\r\n";
                        }
                        
                        $affiliatepress_email_header_data .= "MIME-Version: 1.0\r\n";
                        $affiliatepress_email_header_data .= "Content-Transfer-Encoding: 7bit\r\n";
                        $affiliatepress_email_header_data .= "Content-Type: multipart/mixed; boundary = \"{$affiliatepress_boundary}\"\r\n";

                        $affiliatepress_temp_email_content = "--$affiliatepress_boundary\r\n";
                        $affiliatepress_temp_email_content .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
                        $affiliatepress_temp_email_content .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
                        $affiliatepress_email_content = $affiliatepress_temp_email_content . $affiliatepress_email_content . "\r\n";
                        foreach( $affiliatepress_attachments as $affiliatepress_attachment_file ){
                            $affiliatepress_attachment_name = basename( $affiliatepress_attachment_file );
                            $affiliatepress_attachment_type = mime_content_type( $affiliatepress_attachment_file );

                            if (! function_exists('WP_Filesystem') ) {
                                include_once ABSPATH . 'wp-admin/includes/file.php';
                            }

                            WP_Filesystem();
                            global $wp_filesystem;

                            $affiliatepress_file_content  = $wp_filesystem->get_contents($affiliatepress_attachment_file);
                            $affiliatepress_file_content = chunk_split( base64_encode( $affiliatepress_file_content ) );

                            $affiliatepress_email_content .= "--$affiliatepress_boundary\r\n";
                            $affiliatepress_email_content .= "Content-Type: {$affiliatepress_attachment_type}; name={$affiliatepress_attachment_name}\r\n";
                            $affiliatepress_email_content .= "Content-Disposition: attachment; filename={$affiliatepress_attachment_name}\r\n";
                            $affiliatepress_email_content .= "Content-Transfer-Encoding: base64\r\n";
                            $affiliatepress_email_content .= "X-Attachment-Id: {$affiliatepress_attachment_id}\r\n\r\n";
                            $affiliatepress_email_content .= $affiliatepress_file_content;
                        }
                        $affiliatepress_email_content .= "\r\n--{$affiliatepress_boundary}--\r\n";
                    }
                    
                    if (@mail($receiver_email_id, $affiliatepress_email_subject, $affiliatepress_email_content, $affiliatepress_email_header_data) ) {
                         $affiliatepress_email_send_res['is_mail_sent'] = 1;
                         $affiliatepress_is_mail_sent                                = 1;
                    }
                    break;
                case 'wp_mail':
                    $affiliatepress_email_header_data = 'From: ' . $this->affiliatepress_email_sender_name . '<' . $this->affiliatepress_email_sender_email . "> \r\n";
                    $affiliatepress_email_header_data .= 'Reply-To: ' . $affiliatepress_email_reply_to_name . '<' . $affiliatepress_email_reply_to_email . "> \r\n";
                    $affiliatepress_email_header_data .= "Content-Type: text/html; charset=UTF-8\r\n";
                    if(!empty($affiliatepress_cc_emails) && is_array($affiliatepress_cc_emails)){
                        $affiliatepress_email_header_data .= "Cc: ".implode(',', $affiliatepress_cc_emails)."\r\n";
                    }
                    $affiliatepress_settings->affiliatepress_sending_wp_mail_upon_booking = true;
                    if (wp_mail($receiver_email_id, $affiliatepress_email_subject, $affiliatepress_email_content, $affiliatepress_email_header_data, $affiliatepress_attachments) ) {
                        $affiliatepress_email_send_res['is_mail_sent'] = 1;
                        $affiliatepress_is_mail_sent                                = 1;
                    }
                    $affiliatepress_settings->affiliatepress_sending_wp_mail_upon_booking = false;
                    break;
                case 'smtp':
                    if ( ! empty($this->affiliatepress_smtp_host) && ! empty($this->affiliatepress_smtp_port) && ! empty($this->affiliatepress_smtp_secure) ) {
                        if (version_compare($wp_version, '5.5', '<') ) {
                            include_once ABSPATH . WPINC . '/class-phpmailer.php';
                            include_once ABSPATH . WPINC . '/class-smtp.php';
                            $affiliatepressMailer = new PHPMailer();
                        } else {
                            include_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                            include_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                            include_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                            $affiliatepressMailer = new PHPMailer\PHPMailer\PHPMailer();
                        }

                        $affiliatepressMailer->CharSet   = 'UTF-8';
                        $affiliatepressMailer->SMTPDebug = 0; // change this value to 1 for debug
                        $affiliatepressMailer->isSMTP();
                        $affiliatepressMailer->Host     = $this->affiliatepress_smtp_host;
                        $affiliatepressMailer->SMTPAuth = true;
                        if( ! empty($this->affiliatepress_smtp_username)  ){
                            $affiliatepressMailer->Username = $this->affiliatepress_smtp_username;
                        }
                        if( ! empty($this->affiliatepress_smtp_password) ){
                            $affiliatepressMailer->Password = $this->affiliatepress_smtp_password;
                        }
                        if (! empty($this->affiliatepress_smtp_secure) && $this->affiliatepress_smtp_secure != 'Disabled' ) {
                            $affiliatepressMailer->SMTPSecure = strtolower($this->affiliatepress_smtp_secure);
                        }
                        if ($this->affiliatepress_smtp_secure == 'Disabled' ) {
                            $affiliatepressMailer->SMTPAutoTLS = false;
                        }
                        $affiliatepressMailer->Port = $this->affiliatepress_smtp_port;
                        $affiliatepressMailer->setFrom($this->affiliatepress_email_sender_email, $this->affiliatepress_email_sender_name);
                        $affiliatepressMailer->addReplyTo($affiliatepress_email_reply_to_email, $affiliatepress_email_reply_to_name);
                        $affiliatepressMailer->addAddress($receiver_email_id);

                        if(!empty($affiliatepress_cc_emails) && is_array($affiliatepress_cc_emails)){
                            foreach($affiliatepress_cc_emails as $affiliatepress_ccemail ){
                                $affiliatepressMailer->addCC($affiliatepress_ccemail);
                            }
                        }

                        if (! empty($affiliatepress_attachments) ) {
                            foreach ( $affiliatepress_attachments as $affiliatepress_attachment ) {
                                $affiliatepressMailer->addAttachment($affiliatepress_attachment);
                            }
                        }
                            $affiliatepressMailer->isHTML(true);
                            $affiliatepressMailer->Subject = $affiliatepress_email_subject;
                            $affiliatepressMailer->Body    = $affiliatepress_email_content;

                        if ($affiliatepressMailer->send() ) {
                            $affiliatepress_is_mail_sent                                = 1;
                            $affiliatepress_email_send_res['is_mail_sent'] = 1;
                            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Send Email notification SMTP success response', 'affiliatepress_email_notiifcation', $affiliatepress_is_mail_sent, $affiliatepress_other_debug_log_id);
                        } else {
                            $affiliatepressmailer_errorinfo                  = ! empty($affiliatepressMailer->ErrorInfo) ? $affiliatepressMailer->ErrorInfo : '';
                            $affiliatepress_email_send_res['error_response'] = $affiliatepressmailer_errorinfo;
                            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Send Email notification SMTP error response', 'affiliatepress_email_notiifcation', $affiliatepressmailer_errorinfo, $affiliatepress_other_debug_log_id);
                        }
                    }
                    break;                
                }
                if (! empty($affiliatepress_attachments) ) {
                    foreach ( $affiliatepress_attachments as $affiliatepress_attachment ) {
                        if( file_exists( $affiliatepress_attachment ) ){
                            wp_delete_file($affiliatepress_attachment); // phpcs:ignore
                        }
                    }
                }


                return $affiliatepress_email_send_res;
        }

               
        /**
         * Function for send both type affiliate & admin notification
         *
         * @param  string $affiliatepress_notification_slug
         * @param  string $affiliatepress_notification_type
         * @param  integer $affiliatepress_notification_data_id
         * @param  string $receiver_email_id
         * @param  array $affiliatepress_cc_emails
         * @param  boolean $affiliatepress_force
         * @return array
         */
        function affiliatepress_send_email_notification( $affiliatepress_notification_slug, $affiliatepress_notification_type, $affiliatepress_notification_data_id, $receiver_email_id = "", $affiliatepress_cc_emails = array(), $affiliatepress_force = false )
        {
            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_notifications, $wp_version, $affiliatepress_other_debug_log_id, $affiliatepress_settings;

            $affiliatepress_send_email_notification_debug_log_data = func_get_args();
            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Email notification argument - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', $affiliatepress_send_email_notification_debug_log_data, $affiliatepress_other_debug_log_id);

            $affiliatepress_admin_emails       = esc_html($AffiliatePress->affiliatepress_get_settings('admin_email', 'email_notification_settings'));
            $affiliatepress_admin_sender_name  = esc_html($AffiliatePress->affiliatepress_get_settings('sender_name', 'email_notification_settings'));
            $affiliatepress_admin_sender_email = esc_html($AffiliatePress->affiliatepress_get_settings('sender_email', 'email_notification_settings'));

            $return_data = array(
                'affiliate_send_email' => 0,
                'admin_send_email'     => 0,
            );

            if(!empty($affiliatepress_notification_slug) && !empty($affiliatepress_notification_type) && !empty($affiliatepress_notification_data_id)){

                $affiliatepress_email_reply_to_name = $affiliatepress_email_reply_to_email = '';
                $this->affiliatepress_init_email_config();

                $affiliatepress_email_send_res = array(
                    'is_mail_sent'   => 0,
                    'configurations' => array(
                        'notification_type' => $this->affiliatepress_email_notification_type,
                        'sender_name'       => $this->affiliatepress_email_sender_name,
                        'sender_email'      => $this->affiliatepress_email_sender_email,
                        'smtp_username'     => base64_encode($this->affiliatepress_smtp_password),
                        'smtp_host'         => $this->affiliatepress_smtp_host,
                        'smtp_port'         => $this->affiliatepress_smtp_port,
                        'smpt_secure'       => $this->affiliatepress_smtp_secure,
                        'gmail_client_id'   => $this->affiliatepress_gmail_client_id,
                        'gmail_client_secret' => $this->affiliatepress_gmail_client_secret,
                        'gmail_auth_token'   => $this->affiliatepress_gmail_auth_token,
                        'gmail_connected_email' => $this->affiliatepress_response_email,
                    ),
                    'error_response' => 'Something went wrong while sending email notification',
                    'posted_data'    => array(),
                );

                $affiliatepress_affiliates_id = (isset($affiliatepress_notification_data_id['ap_affiliates_id']))?$affiliatepress_notification_data_id['ap_affiliates_id']:'';
                $affiliatepress_notification_dynamic_variable = array();

                /* Get Affiliate Template Data Here */
                $affiliatepress_get_template_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_notifications, '*', 'WHERE ap_notification_slug = %s AND ap_notification_receiver_type = %s', array( $affiliatepress_notification_slug, 'affiliate' ), '', '', '', false, true,ARRAY_A);

                if($affiliatepress_get_template_data['ap_notification_status'] == 0){

                    $affiliatepress_is_notification_enabled = $affiliatepress_get_template_data['ap_notification_status'];
                    do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Affiliate Email notification status - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', "Is notification enabled ==> ".$affiliatepress_is_notification_enabled, $affiliatepress_other_debug_log_id);                                      

                }else{
                    
                    $affiliatepress_notification_slug    = (isset($affiliatepress_get_template_data['ap_notification_slug']))?$affiliatepress_get_template_data['ap_notification_slug']:'';
                    $affiliatepress_notification_type    = (isset($affiliatepress_get_template_data['ap_notification_type']))?$affiliatepress_get_template_data['ap_notification_type']:'';
                    $affiliatepress_notification_subject = (isset($affiliatepress_get_template_data['ap_notification_subject']))?$affiliatepress_get_template_data['ap_notification_subject']:'';
                    $affiliatepress_notification_message = (isset($affiliatepress_get_template_data['ap_notification_message']))?$affiliatepress_get_template_data['ap_notification_message']:'';

                    $affiliatepress_send_email = true;
                    $affiliatepress_send_email = apply_filters('affiliatepress_validate_affiliate_email_notification', $affiliatepress_send_email, $affiliatepress_get_template_data);

                    if ($affiliatepress_send_email) {

                        $affiliatepress_notification_dynamic_variable = apply_filters( 'affiliatepress_notification_dynamic_variable',$affiliatepress_notification_dynamic_variable, $affiliatepress_notification_slug,$affiliatepress_notification_type,$affiliatepress_notification_data_id,$affiliatepress_get_template_data);

                        $receiver_email = (isset($affiliatepress_notification_dynamic_variable['affiliate_email']))?$affiliatepress_notification_dynamic_variable['affiliate_email']:'';
                        $receiver_name = (isset($affiliatepress_notification_dynamic_variable['affiliate_first_name']))?$affiliatepress_notification_dynamic_variable['affiliate_first_name'].' '.$affiliatepress_notification_dynamic_variable['affiliate_last_name']:'';
                        
                        $receiver_data_arr = array(
                            'receiver_name' => $receiver_name, 
                            'receiver_email'=> $receiver_email
                        );

                        $this->affiliatepress_email_sender_name  = $affiliatepress_admin_sender_name;
                        $this->affiliatepress_email_sender_email = $affiliatepress_admin_sender_email;
                        $reply_to_data_arr = array(
                            'affiliatepress_email_reply_to_name' => $this->affiliatepress_email_sender_name,
                            'affiliatepress_email_reply_to_email'=> $affiliatepress_admin_emails
                        );                    
                        

                        $affiliatepress_email_subject = apply_filters( 'affiliatepress_notification_dynamic_variable_replace', $affiliatepress_notification_subject,$affiliatepress_notification_dynamic_variable);

                        do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Affiliate Notification Message - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', $affiliatepress_notification_message, $affiliatepress_other_debug_log_id);

                        $affiliatepress_email_content = apply_filters( 'affiliatepress_notification_dynamic_variable_replace', $affiliatepress_notification_message,$affiliatepress_notification_dynamic_variable);

                        do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Affiliate Dynamic Variable - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', $affiliatepress_notification_dynamic_variable, $affiliatepress_other_debug_log_id);

                        do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Affiliate Notification Message - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', $affiliatepress_email_content, $affiliatepress_other_debug_log_id);                        

                        $affiliatepress_send_email_data = $this->affiliatepress_send_email($affiliatepress_email_subject,$affiliatepress_email_content,$receiver_data_arr,$reply_to_data_arr,array(), false,$affiliatepress_notification_dynamic_variable,$affiliatepress_get_template_data);

                        if($affiliatepress_send_email_data['is_mail_sent'] == 1){
                            $return_data['affiliate_send_email'] = 1;

                            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Affiliate Email Successfully send - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', 'Email successfully send for '.$receiver_email, $affiliatepress_other_debug_log_id);
                        }

                        $affiliatepress_attachments = array();
                    }else{
                        do_action('affiliatepress_other_debug_log_entry','email_notification_debug_logs','Affiliate Email Blocked - '.$affiliatepress_notification_slug, 'affiliatepress_email_notification','Email Blocked its reason to not send affiliate mail',$affiliatepress_other_debug_log_id );
                    }

                }
                /* Get Affiliate Template Data Over */

                /* Get Admin Template Data Here */                
                $affiliatepress_get_template_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_notifications, '*', 'WHERE ap_notification_slug = %s AND ap_notification_receiver_type = %s', array( $affiliatepress_notification_slug, 'admin' ), '', '', '', false, true,ARRAY_A);
                if($affiliatepress_get_template_data['ap_notification_status'] == 0){

                    $affiliatepress_is_notification_enabled = $affiliatepress_get_template_data['ap_notification_status'];
                    do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Send Email notification status data', 'affiliatepress_email_notiifcation', "Is notification enabled ==> ".$affiliatepress_is_notification_enabled, $affiliatepress_other_debug_log_id);

                }else{

                    $affiliatepress_notification_slug    = (isset($affiliatepress_get_template_data['ap_notification_slug']))?$affiliatepress_get_template_data['ap_notification_slug']:'';
                    $affiliatepress_notification_type    = (isset($affiliatepress_get_template_data['ap_notification_type']))?$affiliatepress_get_template_data['ap_notification_type']:'';
                    $affiliatepress_notification_subject = (isset($affiliatepress_get_template_data['ap_notification_subject']))?$affiliatepress_get_template_data['ap_notification_subject']:'';
                    $affiliatepress_notification_message = (isset($affiliatepress_get_template_data['ap_notification_message']))?$affiliatepress_get_template_data['ap_notification_message']:'';
                    
                    if(empty($affiliatepress_notification_dynamic_variable)){
                        $affiliatepress_notification_dynamic_variable = apply_filters( 'affiliatepress_notification_dynamic_variable',$affiliatepress_notification_dynamic_variable, $affiliatepress_notification_slug,$affiliatepress_notification_type,$affiliatepress_notification_data_id,$affiliatepress_get_template_data);
                    }

                    $replay_email = (isset($affiliatepress_notification_dynamic_variable['affiliate_email']))?$affiliatepress_notification_dynamic_variable['affiliate_email']:'';                    
                    $replay_name = (isset($affiliatepress_notification_dynamic_variable['affiliate_first_name']))?$affiliatepress_notification_dynamic_variable['affiliate_first_name'].' '.$affiliatepress_notification_dynamic_variable['affiliate_last_name']:'';                    
                    $receiver_email = $affiliatepress_admin_emails;

                    $receiver_data_arr = array(
                        'receiver_name' => $affiliatepress_admin_sender_name, 
                        'receiver_email'=> $receiver_email
                    );                    

                    $reply_to_data_arr = array(
                        'affiliatepress_email_reply_to_name' => $replay_name, 
                        'affiliatepress_email_reply_to_email'=> $replay_email
                    );                    
                   
                    
                    $affiliatepress_email_subject = apply_filters( 'affiliatepress_notification_dynamic_variable_replace', $affiliatepress_notification_subject,$affiliatepress_notification_dynamic_variable);

                    $affiliatepress_email_content = apply_filters( 'affiliatepress_notification_dynamic_variable_replace', $affiliatepress_notification_message,$affiliatepress_notification_dynamic_variable);

                    $affiliatepress_send_email_data = $this->affiliatepress_send_email($affiliatepress_email_subject,$affiliatepress_email_content,$receiver_data_arr,$reply_to_data_arr,array(), false,$affiliatepress_notification_dynamic_variable,$affiliatepress_get_template_data);

                    do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Admin Dynamic Variable - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', $affiliatepress_notification_dynamic_variable, $affiliatepress_other_debug_log_id);                    

                    do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Admin Notification Message - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', $affiliatepress_email_content, $affiliatepress_other_debug_log_id);

                    do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Admin Notification Message - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', $affiliatepress_notification_message, $affiliatepress_other_debug_log_id);
                    


                    if($affiliatepress_send_email_data['is_mail_sent'] == 1){
                        $return_data['admin_send_email'] = 1;

                        do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Admin Email Successfully send - '.$affiliatepress_notification_slug, 'affiliatepress_email_notiifcation', 'Email send succesfully for '.$affiliatepress_admin_sender_name, $affiliatepress_other_debug_log_id);
                    }
                  
                }
                /* Get Admin Template Data Here */

            }



            return $return_data;
        }
        
        /**
         * Function for get host name
         *
         * @return string
        */
        function affiliatepress_get_host_name(){
            $result = '';
            if (isset($_SERVER) && array_key_exists('SERVER_NAME', $_SERVER)) {// phpcs:ignore
                $result = sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']));// phpcs:ignore
            } elseif (function_exists('gethostname') && gethostname() !== false) {// phpcs:ignore
                $result = gethostname();
            } elseif (php_uname('n') !== false) {
                $result = php_uname('n');
            }
            if (!static::affiliatepress_is_valid_host($result)) {
                return 'localhost.localdomain';
            }

            return $result;
        }
        
        /**
         * Function for check valid host or not 
         *
         * @param  string $affiliatepress_host
         * @return string
        */
        public static function affiliatepress_is_valid_host($affiliatepress_host){
            //Simple syntax limits
            if (
                empty($affiliatepress_host)
                || !is_string($affiliatepress_host)
                || strlen($affiliatepress_host) > 256
                || !preg_match('/^([a-zA-Z\d.-]*|\[[a-fA-F\d:]+\])$/', $affiliatepress_host)
            ) {
                return false;
            }
            //Looks like a bracketed IPv6 address
            if (strlen($affiliatepress_host) > 2 && substr($affiliatepress_host, 0, 1) === '[' && substr($affiliatepress_host, -1, 1) === ']') {
                return filter_var(substr($affiliatepress_host, 1, -1), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
            }
            //If removing all the dots results in a numeric string, it must be an IPv4 address.
            //Need to check this first because otherwise things like `999.0.0.0` are considered valid host names
            if (is_numeric(str_replace('.', '', $affiliatepress_host))) {
                //Is it a valid IPv4 address?
                return filter_var($affiliatepress_host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
            }
            //Is it a syntactically valid hostname (when embeded in a URL)?
            return filter_var('http://' . $affiliatepress_host, FILTER_VALIDATE_URL) !== false;
        }
        
        /**
         * Function for generate random message id
         *
         * @return void
        */
        function affiliatepress_generate_random_msgid(){
            $affiliatepress_len = 32; //32 bytes = 256 bits
            $affiliatepress_bytes = '';
            if (function_exists('random_bytes')) {
                try {
                    $affiliatepress_bytes = random_bytes($affiliatepress_len);
                } catch (\Exception $e) {
                    //Do nothing
                }
            } elseif (function_exists('openssl_random_pseudo_bytes')) {
                /** @noinspection CryptographicallySecureRandomnessInspection */
                $affiliatepress_bytes = openssl_random_pseudo_bytes($affiliatepress_len);
            }
            if ($affiliatepress_bytes === '') {
                //We failed to produce a proper random string, so make do.
                //Use a hash to force the length to the same as the other methods
                $affiliatepress_bytes = hash('sha256', uniqid((string) mt_rand(), true), true); // phpcs:ignore
            }

            //We don't care about messing up base64 format here, just want a random string
            return str_replace(['=', '+', '/'], '', base64_encode(hash('sha256', $affiliatepress_bytes, true)));
        }
        
        
        /**
         * Function to send email notifications directly to send emails with passed data
         *
         * @param  string $affiliatepress_email_to
         * @param  string $affiliatepress_email_subject
         * @param  string $affiliatepress_email_content
         * @param  string $affiliatepress_from_name
         * @param  string $affiliatepress_from_email
         * @param  string $reply_to
         * @param  string $reply_to_name
         * @return void
         */
        function affiliatepress_send_custom_email_notifications( $affiliatepress_email_to = '', $affiliatepress_email_subject = '', $affiliatepress_email_content = '', $affiliatepress_from_name = '', $affiliatepress_from_email = '', $reply_to = '', $reply_to_name = '' ){
            global $wpdb, $affiliatepress,  $wp_version, $affiliatepress_other_debug_log_id;

            $this->affiliatepress_init_email_config();

            switch ( $this->affiliatepress_email_notification_type ) {
                case 'php_mail':
                    $affiliatepress_email_header_data = 'From: ' . $affiliatepress_from_name . '<' . $affiliatepress_from_email . "> \r\n";
                    $affiliatepress_email_header_data .= 'Reply-To: ' . $reply_to_name . '<' . $reply_to . "> \r\n";
                    $affiliatepress_email_header_data .= "Content-Type: text/html; charset=UTF-8\r\n";

                    if (@mail($affiliatepress_email_to, $affiliatepress_email_subject, $affiliatepress_email_content, $affiliatepress_email_header_data) ) {
                         $affiliatepress_is_mail_sent                                = 1;

                         do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Send Custom Email notification SMTP success response', 'affiliatepress_email_notiifcation',$affiliatepress_email_content.' --->>>'. $affiliatepress_is_mail_sent, $affiliatepress_other_debug_log_id);
                    }
                    break;
                case 'wp_mail':
                    $affiliatepress_email_header_data = 'From: ' . $affiliatepress_from_name . '<' . $affiliatepress_from_email . "> \r\n";
                    $affiliatepress_email_header_data .= 'Reply-To: ' . $reply_to_name . '<' . $reply_to . "> \r\n";
                    $affiliatepress_email_header_data .= "Content-Type: text/html; charset=UTF-8\r\n";
                    
                    if (wp_mail($affiliatepress_email_to, $affiliatepress_email_subject, $affiliatepress_email_content, $affiliatepress_email_header_data) ) {
                        $affiliatepress_is_mail_sent                                = 1;

                        do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Send Custom Email notification SMTP success response', 'affiliatepress_email_notiifcation',$affiliatepress_email_content.' --->>>'. $affiliatepress_is_mail_sent, $affiliatepress_other_debug_log_id);

                    }
                    break;
                case 'smtp':
                    if ( ! empty($this->affiliatepress_smtp_host) && ! empty($this->affiliatepress_smtp_port) && ! empty($this->affiliatepress_smtp_secure) ) {
                        if (version_compare($wp_version, '5.5', '<') ) {
                            include_once ABSPATH . WPINC . '/class-phpmailer.php';
                            include_once ABSPATH . WPINC . '/class-smtp.php';
                            $affiliatepressMailer = new PHPMailer();
                        } else {
                            include_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                            include_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                            include_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                            $affiliatepressMailer = new PHPMailer\PHPMailer\PHPMailer();
                        }

                        $affiliatepressMailer->CharSet   = 'UTF-8';
                        $affiliatepressMailer->SMTPDebug = 0; // change this value to 1 for debug
                        $affiliatepressMailer->isSMTP();
                        $affiliatepressMailer->Host     = $this->affiliatepress_smtp_host;
                        $affiliatepressMailer->SMTPAuth = true;
                        if( ! empty($this->affiliatepress_smtp_username) ){
                            $affiliatepressMailer->Username = $this->affiliatepress_smtp_username;
                        }
                        if( ! empty($this->affiliatepress_smtp_password)  ){
                            $affiliatepressMailer->Password = $this->affiliatepress_smtp_password;
                        }
                        if (! empty($this->affiliatepress_smtp_secure) && $this->affiliatepress_smtp_secure != 'Disabled' ) {
                            $affiliatepressMailer->SMTPSecure = strtolower($this->affiliatepress_smtp_secure);
                        }
                        if ($this->affiliatepress_smtp_secure == 'Disabled' ) {
                            $affiliatepressMailer->SMTPAutoTLS = false;
                        }
                        $affiliatepressMailer->Port = $this->affiliatepress_smtp_port;
                        $affiliatepressMailer->setFrom($affiliatepress_from_email, $affiliatepress_from_name);
                        $affiliatepressMailer->addReplyTo($reply_to, $reply_to_name);
                        $affiliatepressMailer->addAddress($affiliatepress_email_to);

                        $affiliatepressMailer->isHTML(true);
                        $affiliatepressMailer->Subject = $affiliatepress_email_subject;
                        $affiliatepressMailer->Body    = $affiliatepress_email_content;

                        if ($affiliatepressMailer->send() ) {
                            $affiliatepress_is_mail_sent                                = 1;
                            
                            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Send Custom Email notification SMTP success response', 'affiliatepress_email_notiifcation',$affiliatepress_email_content.' --->>>'. $affiliatepress_is_mail_sent, $affiliatepress_other_debug_log_id);
                        } else {
                            $affiliatepressmailer_errorinfo                  = ! empty($affiliatepressMailer->ErrorInfo) ? $affiliatepressMailer->ErrorInfo : '';
                            do_action('affiliatepress_other_debug_log_entry', 'email_notification_debug_logs', 'Send Custom notification SMTP error response', 'affiliatepress_email_notiifcation', $affiliatepressmailer_errorinfo, $affiliatepress_other_debug_log_id);
                        }
                    }
                    break;                
                }
        }
        

    }

    global $affiliatepress_email_notifications;
    $affiliatepress_email_notifications = new affiliatepress_email_notifications();
}
