<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_payout') ) {
    class affiliatepress_payout Extends AffiliatePress_Core{
        
        var $affiliatepress_per_page_record;

        var $affiliatepress_payout_affiliate_limit;

        function __construct(){
            
            $this->affiliatepress_per_page_record = 10;
            $this->affiliatepress_payout_affiliate_limit = 500;

            /**Function for payout vue data */
            add_action( 'admin_init', array( $this, 'affiliatepress_payout_vue_data_fields') );

            /* Dynamic Constant */
            add_filter('affiliatepress_payout_dynamic_constant_define',array($this,'affiliatepress_payout_dynamic_constant_define_func'),10,1);
            
            /* Dynamic Vue Fields */
            add_filter('affiliatepress_payout_dynamic_data_fields',array($this,'affiliatepress_payout_dynamic_data_fields_func'),10,1);

            /* Vue Load */
            add_action('affiliatepress_payout_dynamic_view_load', array( $this, 'affiliatepress_payout_dynamic_view_load_func' ), 10);

            /* Vue Method */
            add_filter('affiliatepress_payout_dynamic_vue_methods',array($this,'affiliatepress_payout_dynamic_vue_methods_func'),10,1);

            /* Get Affiliates */
            add_action('wp_ajax_affiliatepress_get_payouts', array( $this, 'affiliatepress_get_payouts' ));

            /* Dynamic On Load Method */
            add_filter('affiliatepress_payout_dynamic_on_load_methods', array( $this, 'affiliatepress_payout_dynamic_on_load_methods_func' ), 10);

            /* Edit Payout */
            add_action('wp_ajax_affiliatepress_edit_payout', array( $this, 'affiliatepress_edit_payout_func' ));

            /*generate payout preview */
            add_action('wp_ajax_affiliatepress_generate_payout_preview', array( $this, 'affiliatepress_generate_payout_preview_func' ));

            /* generate payout  */
            add_action('wp_ajax_affiliatepress_generate_payout_request', array( $this, 'affiliatepress_generate_payout_request_func' ));            

            /* Affiliate Payout Process */
            add_action('wp_ajax_affiliatepress_generate_payout_process', array( $this, 'affiliatepress_generate_payout_process_request_func' ));  

            /* Affiliate Payout Process */
            add_action('wp_ajax_affiliatepress_check_payout_process', array( $this, 'affiliatepress_check_payout_process_func' ));     
            
            /* Delete Payout */
            add_action('wp_ajax_affiliatepress_delete_payout', array( $this, 'affiliatepress_delete_payout' ));  
            
            /* Payment Status Change */
            add_action('wp_ajax_affiliatepress_payment_status_change', array( $this, 'affiliatepress_payment_status_change' ));  
            
            /* Send Paid Payment Email Notification */
            add_action('affiliatepress_after_change_payment_status',array($this,'affiliatepress_after_change_payment_status_func'),10,2);            

            /**Function for add custom hourly cron */
            add_filter( 'cron_schedules', array($this,'affiliatepress_custom_cron_schedule'));

            /**Function for schedule cron event */
            add_action( 'wp', array($this,'affiliatepress_schedule_custom_cron_event') );  
            
            /**Generate auto payout */
            add_action( 'affiliatepress_payout_hourly_event', array($this,'affiliatepress_generate_auto_payout') );

            /* Function for add payout payment note */
            add_action('wp_ajax_affiliatepress_add_payment_note', array( $this, 'affiliatepress_add_payment_note_func')); 
            
            /* Function For After Payment Status Update Migrate Report Table Data Update */
            add_action('affiliatepress_update_report_data_based_on_payment_status',array($this,'affiliatepress_update_report_data_based_on_payment_status_func'),10,2);

            add_action('wp_ajax_affiliatepress_generate_export_payout', array($this, 'affiliatepress_generate_export_payout_func'),10);

            add_filter('affiliatepress_payout_payment_method', array($this,'affiliatepress_payout_method_add_func'),10,1);

        }
          
        
        /**
         * Function for get auto payout date
         *
         * @return date
        */
        function affiliatepress_get_next_auto_payout_date(){
            global $AffiliatePress;
            $affiliatepress_commission_billing_cycle = $AffiliatePress->affiliatepress_get_settings('commission_billing_cycle', 'commissions_settings');
            $affiliatepress_commission_cooling_period_days = $AffiliatePress->affiliatepress_get_settings('commission_cooling_period_days', 'commissions_settings');
            $affiliatepress_day_of_billing_cycle = $AffiliatePress->affiliatepress_get_settings('day_of_billing_cycle', 'commissions_settings');
            $affiliatepress_givenDate = date('Y-m-d',current_time('timestamp') );// phpcs:ignore
            if($affiliatepress_commission_billing_cycle == 'weekly'){
                $affiliatepress_daysname = "Monday";
                switch($affiliatepress_day_of_billing_cycle){
                    case 1:
                        $affiliatepress_daysname= 'Monday';
                        break;
                    case 2:
                        $affiliatepress_daysname= 'Tuesday';
                        break;
                    case 3:
                        $affiliatepress_daysname= 'Wednesday';
                        break;
                    case 4:
                        $affiliatepress_daysname= 'Thursday';
                        break;
                    case 5:
                        $affiliatepress_daysname= 'Friday';
                        break;
                    case 6:
                        $affiliatepress_daysname= 'Saturday';
                        break;
                    default:
                        $affiliatepress_daysname= 'Sunday';                
                }                
                $affiliatepress_possible_date = date('Y-m-d', strtotime(''.$affiliatepress_daysname.' ', strtotime($affiliatepress_givenDate)));   // phpcs:ignore            
            }else{
                if($affiliatepress_commission_billing_cycle == 'monthly'){

                    $affiliatepress_date = new DateTime('last day of this month');
                    $affiliatepress_last_day_of_previous_month = $affiliatepress_date->format('Y-m-d');
                    $affiliatepress_date = new DateTime('first day of this month');
                    $affiliatepress_day_of_billing_cycle = $affiliatepress_day_of_billing_cycle - 1;
                    if($affiliatepress_day_of_billing_cycle > 0){
                        $affiliatepress_date->modify('+'.$affiliatepress_day_of_billing_cycle.' days');  
                    }                    
                    $affiliatepress_possible_date = $affiliatepress_date->format('Y-m-d');                    
                    if($affiliatepress_possible_date > $affiliatepress_last_day_of_previous_month){
                        $affiliatepress_possible_date = $affiliatepress_last_day_of_previous_month;
                    }
                    $today = new DateTime('now');
                    if($affiliatepress_possible_date < $today){

                        $affiliatepress_date = new DateTime('last day of next month');
                        $affiliatepress_last_day_of_previous_month = $affiliatepress_date->format('Y-m-d');
                        $affiliatepress_date = new DateTime('first day of next month');                        
                        if($affiliatepress_day_of_billing_cycle > 0){
                            $affiliatepress_date->modify('+'.$affiliatepress_day_of_billing_cycle.' days');  
                        }                    
                        $affiliatepress_possible_date = $affiliatepress_date->format('Y-m-d');                    
                        if($affiliatepress_possible_date > $affiliatepress_last_day_of_previous_month){
                            $affiliatepress_possible_date = $affiliatepress_last_day_of_previous_month;
                        }
 
                    }

                }
                else if ($affiliatepress_commission_billing_cycle == 'yearly') {

                    $affiliatepress_selected_month = intval($affiliatepress_day_of_billing_cycle);
                
                    if ($affiliatepress_selected_month == 1) {
                        $affiliatepress_selected_month = 12;
                    } else {
                        $affiliatepress_selected_month -= 1;
                    }
                    $today = new DateTime();
                    $today->setTime(0, 0, 0);
                    $affiliatepress_date = new DateTime();
                    $affiliatepress_date->setDate(date('Y'), $affiliatepress_selected_month, 1); //phpcs:ignore
                    $affiliatepress_date->modify('last day of this month');
                    $affiliatepress_date->setTime(0, 0, 0);
                    if ($affiliatepress_date < $today) {
                        $affiliatepress_date->modify('+1 year');
                    }
                    $affiliatepress_possible_date = $affiliatepress_date->format('Y-m-d');
                }
            }

            return $affiliatepress_possible_date;
        }

         /**
         * Function for get auto payout date
         *
         * @return date
        */
        function affiliatepress_get_auto_payout_date(){
            global $AffiliatePress;
            $affiliatepress_commission_billing_cycle = $AffiliatePress->affiliatepress_get_settings('commission_billing_cycle', 'commissions_settings');
            $affiliatepress_commission_cooling_period_days = $AffiliatePress->affiliatepress_get_settings('commission_cooling_period_days', 'commissions_settings');
            $affiliatepress_day_of_billing_cycle = $AffiliatePress->affiliatepress_get_settings('day_of_billing_cycle', 'commissions_settings');
            $affiliatepress_givenDate = date('Y-m-d',current_time('timestamp') );// phpcs:ignore
            if($affiliatepress_commission_billing_cycle == 'weekly'){
                $affiliatepress_daysname = "Monday";
                switch($affiliatepress_day_of_billing_cycle){
                    case 1:
                        $affiliatepress_daysname= 'Monday';
                        break;
                    case 2:
                        $affiliatepress_daysname= 'Tuesday';
                        break;
                    case 3:
                        $affiliatepress_daysname= 'Wednesday';
                        break;
                    case 4:
                        $affiliatepress_daysname= 'Thursday';
                        break;
                    case 5:
                        $affiliatepress_daysname= 'Friday';
                        break;
                    case 6:
                        $affiliatepress_daysname= 'Saturday';
                        break;
                    default:
                        $affiliatepress_daysname= 'Sunday';                
                }                
                $affiliatepress_possible_date = date('Y-m-d', strtotime(''.$affiliatepress_daysname.' this week  - 7 days', strtotime($affiliatepress_givenDate)));   // phpcs:ignore            
            }else{
                if($affiliatepress_commission_billing_cycle == 'monthly'){

                    $affiliatepress_date = new DateTime('last day of previous month');
                    $affiliatepress_last_day_of_previous_month = $affiliatepress_date->format('Y-m-d');
                    $affiliatepress_date = new DateTime('first day of previous month');

                    $affiliatepress_day_of_billing_cycle = $affiliatepress_day_of_billing_cycle - 1;
                    if($affiliatepress_day_of_billing_cycle > 0){
                        $affiliatepress_date->modify('+'.$affiliatepress_day_of_billing_cycle.' days');  
                    }                    
                    $affiliatepress_possible_date = $affiliatepress_date->format('Y-m-d');                    
                    if($affiliatepress_possible_date > $affiliatepress_last_day_of_previous_month){
                        $affiliatepress_possible_date = $affiliatepress_last_day_of_previous_month;
                    }

                }
                else if($affiliatepress_commission_billing_cycle == 'yearly'){

                    $affiliatepress_selected_month = intval($affiliatepress_day_of_billing_cycle);

                    if ($affiliatepress_selected_month == 1) {
                        $affiliatepress_selected_month = 12;
                        $year = date('Y') - 1;//phpcs:ignore
                    } else {
                        $affiliatepress_selected_month -= 1;
                        $year = date('Y');//phpcs:ignore
                    }
                
                    $affiliatepress_date = new DateTime();
                    $affiliatepress_date->setDate($year, $affiliatepress_selected_month, 1);
                    $affiliatepress_date->modify('last day of this month');
                    $affiliatepress_date->setTime(0, 0, 0);
                
                    $today = new DateTime();
                    $today->setTime(0, 0, 0);
                    
                    if ($affiliatepress_date >= $today) {
                        $affiliatepress_date->modify('-1 year');
                    }
                
                    $affiliatepress_possible_date = $affiliatepress_date->format('Y-m-d');
                }
            }

            return $affiliatepress_possible_date;
        }


        /**
         * Function for update report data when change payment status
         *
         * @param  mixed $affiliatepress_payment_commission
         * @param  mixed $affiliatepress_payment_id
         * @return void
        */
        function affiliatepress_update_report_data_based_on_payment_status_func($affiliatepress_payment_commission, $affiliatepress_payment_id){

            if(!empty($affiliatepress_payment_commission)){
                global $affiliatepress_tracking;
                $affiliatepress_payment_commission_arr = explode(",",$affiliatepress_payment_commission);
                if(!empty($affiliatepress_payment_commission_arr) && is_array($affiliatepress_payment_commission_arr)){
                    foreach($affiliatepress_payment_commission_arr as $affiliatepress_commission_id){
                        $affiliatepress_tracking->affiliatepress_commissions_report_data_change_func($affiliatepress_commission_id, 0, 0); 
                    }
                }
            }
            
        }

        /**
         * Function for add payout note data
         *
         * @return json
        */
        function affiliatepress_add_payment_note_func(){
            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_payments;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'payment_note', true, 'ap_wp_nonce' );            
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
            
            if(!current_user_can('affiliatepress_payout')){
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

            $affiliatepress_payout_id = isset($_POST['payout_id']) ? intval($_POST['payout_id']) : '';
            $response['msg'] = esc_html__('Payment note has not been added.', 'affiliatepress-affiliate-marketing');
            $affiliatepress_payout_payment_message_id = (isset($_POST['payout_payment_message_id']))?intval($_POST['payout_payment_message_id']):0;// phpcs:ignore
            $affiliatepress_payout_payment_message = (isset($_POST['payout_payment_message']))?sanitize_text_field($_POST['payout_payment_message']):'';// phpcs:ignore
            if($affiliatepress_payout_payment_message_id && !empty($affiliatepress_payout_payment_message)){
                $affiliatepress_args = array(
                    'ap_payment_note' => $affiliatepress_payout_payment_message
                );
                $this->affiliatepress_update_record($affiliatepress_tbl_ap_payments, $affiliatepress_args, array( 'ap_payment_id' => $affiliatepress_payout_payment_message_id ));
                $response['variant'] = 'success';
                $response['ap_payout_id'] = $affiliatepress_payout_id;
                $response['title']   = esc_html__( 'Success', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__( 'Payment note added successfully.', 'affiliatepress-affiliate-marketing');
                wp_send_json( $response );
                die;
            }
            wp_send_json( $response );
            die;
        }

                        
        /**
         * Function for add custom hourly cron
         *
         * @param  mixed $affiliatepress_schedules
         * @return array
        */
        function affiliatepress_custom_cron_schedule( $affiliatepress_schedules ) {
            $affiliatepress_schedules['affiliatepress_every_hour'] = array(
                'interval' => 3600,
                'display'  => esc_html__( 'Every 1 Hour' , 'affiliatepress-affiliate-marketing')
            );
            return $affiliatepress_schedules;
        }
        
        /**
         * Function for schedule cron event
         *
         * @return void
        */
        function affiliatepress_schedule_custom_cron_event() {
            if(!wp_next_scheduled('affiliatepress_payout_hourly_event')){
                wp_schedule_event(time(),'affiliatepress_every_hour','affiliatepress_payout_hourly_event');
            }            
        }

        /**
         * Generate auto payout
         *
         * @return void
        */
        function affiliatepress_generate_auto_payout(){

            global $wpdb,$AffiliatePress,$affiliatepress_tbl_ap_payouts,$affiliatepress_payout_debug_log_id,$affiliatepress_tracking;
            
            $affiliatepress_commission_billing_cycle = $AffiliatePress->affiliatepress_get_settings('commission_billing_cycle', 'commissions_settings');

            if($affiliatepress_commission_billing_cycle == 'disabled'){

                do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'Automatic Payout Call ', 'affiliatepress_auto_payout_tracking', 'Automatic Payout Option Is Disable ', $affiliatepress_payout_debug_log_id);

                return false;
                exit;
            }

            $affiliatepress_auto_payout_date = $this->affiliatepress_get_auto_payout_date();            

            do_action('affiliatepress_before_auto_payout_generate');

            $affiliatepress_payout_added_affiliate = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payouts, 'count(ap_payout_id)', 'WHERE DATE(ap_payout_upto_date) >= %s ', array( $affiliatepress_auto_payout_date ), '', '', '', true, false,ARRAY_A));

            do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'Automatic Payout Call ', 'affiliatepress_auto_payout_tracking', 'Automatic Payout Date : '.$affiliatepress_auto_payout_date, $affiliatepress_payout_debug_log_id);

            if($affiliatepress_payout_added_affiliate == 0 && $affiliatepress_auto_payout_date < date('Y-m-d',current_time('timestamp'))){ // phpcs:ignore                
                
                update_option('affiliatepress_auto_payout_process',$affiliatepress_auto_payout_date);
                update_option('affiliatepress_auto_payout_process_time',time());

                do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'Automatic Payout Date Get ', 'affiliatepress_auto_payout_tracking', $affiliatepress_auto_payout_date, $affiliatepress_payout_debug_log_id);

                $affiliatepress_payment_method = 'manual';
                $affiliatepress_payment_method = apply_filters('affiliatepress_auto_payout_payment_method',$affiliatepress_payment_method);

                $affiliatepress_payout_data = $this->affiliatepress_generate_payout_data($affiliatepress_auto_payout_date,array());
                if(isset($affiliatepress_payout_data['payout_affiliates']) && !empty($affiliatepress_payout_data['payout_affiliates'])){                      
                    
                    $affiliatepress_total_affiliate = (isset($affiliatepress_payout_data['total_affiliate']))?intval($affiliatepress_payout_data['total_affiliate']):0;                    
                    $affiliatepress_minimum_payment_amount = $AffiliatePress->affiliatepress_get_settings('minimum_payment_amount', 'commissions_settings'); 
                    $affiliatepress_minimum_payment_order = $affiliatepress_tracking->affiliatepress_get_payout_minimum_payment_order();                   
                    $affiliatepress_args = array(                
                        'ap_payout_created_by'          => 0,
                        'ap_payout_upto_date'           => $affiliatepress_auto_payout_date,
                        'ap_payout_total_affiliate'     => $affiliatepress_total_affiliate,
                        'ap_payout_selected_affiliate'  => '',   
                        'ap_payment_method'             => '',
                        'ap_payment_min_amount'         => $affiliatepress_minimum_payment_amount,             
                        'ap_payout_process'             => 0,
                        'ap_payment_min_order'          => $affiliatepress_minimum_payment_order,
                    );

                    $affiliatepress_payout_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_payouts, $affiliatepress_args);
                    
                    do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'AUTO Payout ID #'.$affiliatepress_payout_id.' Data : ', 'affiliatepress_auto_payout_tracking', wp_json_encode(array('total_affiliate' => $affiliatepress_total_affiliate,'minimum_payment_amount'=>$affiliatepress_minimum_payment_amount,'auto_payout_date'=>$affiliatepress_auto_payout_date)), $affiliatepress_payout_id);

                    $affiliatepress_auto_payout_generated = $this->affiliatepress_generate_payout_process(true);

                    if($affiliatepress_auto_payout_generated){
                        do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'AUTO Payout ID #'.$affiliatepress_payout_id.'', 'affiliatepress_auto_payout_tracking', 'Automatic Payout succesfully generated.', $affiliatepress_payout_id);
                    }

                }

            }

        }

        /**
         * Function for change payment status
         *
         * @param  mixed $affiliatepress_payment_id
         * @param  mixed $affiliatepress_new_status
         * @return void
        */
        function affiliatepress_after_change_payment_status_func($affiliatepress_payment_id,$affiliatepress_new_status){

            global $wpdb,$affiliatepress_email_notifications,$affiliatepress_tbl_ap_payments, $affiliatepress_tbl_ap_payment_commission;

            $affiliatepress_all_payment_commissions = $wpdb->get_row($wpdb->prepare("SELECT GROUP_CONCAT(ap_commission_id) payment_commission FROM {$affiliatepress_tbl_ap_payment_commission} WHERE ap_payment_id = %d", $affiliatepress_payment_id), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_payment_commission_temp is table name defined globally.            

            if(!empty($affiliatepress_all_payment_commissions) && !empty($affiliatepress_all_payment_commissions['payment_commission'])){  
                do_action('affiliatepress_update_report_data_based_on_payment_status',$affiliatepress_all_payment_commissions['payment_commission'], $affiliatepress_payment_id);
            }

            if($affiliatepress_new_status == 4){
                $affiliatepress_notification_type = 'affiliate_payment_paid';                
                $affiliatepress_affiliates_id = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'ap_affiliates_id', 'WHERE ap_payment_id = %d ', array( $affiliatepress_payment_id ), '', '', '', true, false,ARRAY_A));
                if(!empty($affiliatepress_notification_type) && $affiliatepress_payment_id){
                   $affiliatepress_email_notifications->affiliatepress_send_email_notification($affiliatepress_notification_type,'payment',array('ap_affiliates_id'=>$affiliatepress_affiliates_id,'ap_payment_id'=>$affiliatepress_payment_id));
                }
            }
        }    

        /**
         * Function for update payment status
         *
         * @return json
        */
        function affiliatepress_payment_status_change(){

            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_payouts, $affiliatepress_tbl_ap_payments, $affiliatepress_tbl_ap_payment_commission,$affiliatepress_tbl_ap_affiliate_commissions;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'payment_status_change', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_payout')){
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

            $affiliatepress_payment_id = (isset($_POST['ap_payment_id']))?intval($_POST['ap_payment_id']):'';// phpcs:ignore
            $affiliatepress_new_status = (isset($_POST['new_status']))?intval($_POST['new_status']):'';// phpcs:ignore

            $affiliatepress_args = array(
                'ap_payment_status'=>$affiliatepress_new_status,
                'ap_payment_method'=>'manual',
            );
            $this->affiliatepress_update_record($affiliatepress_tbl_ap_payments, $affiliatepress_args, array( 'ap_payment_id' => $affiliatepress_payment_id ));

            $affiliatepress_tbl_ap_payment_commission_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_payment_commission); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_payment_commission contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_all_payment_commissions = $wpdb->get_row($wpdb->prepare("SELECT GROUP_CONCAT(ap_commission_id) payment_commission FROM {$affiliatepress_tbl_ap_payment_commission_temp} WHERE ap_payment_id = %d", $affiliatepress_payment_id), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_payment_commission_temp is table name defined globally.

            if(!empty($affiliatepress_all_payment_commissions) && !empty($affiliatepress_all_payment_commissions['payment_commission'])){          
                
                $affiliatepress_tbl_ap_affiliate_commissions_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions); 

                if($affiliatepress_new_status == 4){ 
                    $affiliatepress_commission_status = 4;                   
                    $affiliatepress_final_commission_ids = $affiliatepress_all_payment_commissions['payment_commission'];

                    $wpdb->query($wpdb->prepare("UPDATE $affiliatepress_tbl_ap_affiliate_commissions SET ap_commission_status = %d WHERE ap_commission_id IN ($affiliatepress_final_commission_ids)",$affiliatepress_commission_status)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions_temp is a table name. false alarm    

                }else{
                    $affiliatepress_commission_status = 1;
                    $affiliatepress_final_commission_ids = $affiliatepress_all_payment_commissions['payment_commission'];
                    $wpdb->query($wpdb->prepare("UPDATE $affiliatepress_tbl_ap_affiliate_commissions SET ap_commission_status = %d WHERE ap_commission_id IN ($affiliatepress_final_commission_ids)",$affiliatepress_commission_status));// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions_temp is a table name. false alarm   

                }                
            }


            do_action('affiliatepress_after_change_payment_status',$affiliatepress_payment_id,$affiliatepress_new_status);

            $affiliatepress_tbl_ap_payments_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_payments);

            $affiliatepress_payout_id = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments_temp, 'ap_payout_id', 'WHERE ap_payment_id = %d ', array( $affiliatepress_payment_id ), '', '', '', true, false,ARRAY_A));

          
            $response['variant']       = 'success';
            $response['ap_payout_id']  = $affiliatepress_payout_id;
            $response['title']         = esc_html__( 'Completed', 'affiliatepress-affiliate-marketing');
            $response['msg']           = esc_html__( 'Payout Status Successfully Change.', 'affiliatepress-affiliate-marketing');

            wp_send_json( $response );
            die;
        }     
                   
        /**
         * Function for check payout process percentage
         *
         * @return json
        */
        function affiliatepress_check_payout_process_func(){
            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_payouts, $affiliatepress_tbl_ap_payments, $affiliatepress_tbl_ap_payment_commission;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'generate_payout', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_payout')){
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

            $affiliatepress_complete_percentage = 0;         
            $affiliatepress_last_payout_record = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payouts, '*', '', '', '', ' order by ap_payout_id DESC ', '', false, true,ARRAY_A);            
            $affiliatepress_ap_payout_process = (isset($affiliatepress_last_payout_record['ap_payout_process']))?$affiliatepress_last_payout_record['ap_payout_process']:'';
            if(!empty($affiliatepress_last_payout_record)){
                if($affiliatepress_ap_payout_process == 0){    
                    $affiliatepress_payout_id = (isset($affiliatepress_last_payout_record['ap_payout_id']))?$affiliatepress_last_payout_record['ap_payout_id']:0;
                    $affiliatepress_payout_total_affiliate = (isset($affiliatepress_last_payout_record['ap_payout_total_affiliate']))?$affiliatepress_last_payout_record['ap_payout_total_affiliate']:0;
    
                    if ($affiliatepress_payout_total_affiliate > 0) {
                        $affiliatepress_payout_added_affiliate = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'count(ap_payment_id)', 'WHERE ap_payout_id = %d ', array( $affiliatepress_payout_id ), '', '', '', true, false,ARRAY_A)); 
                        $affiliatepress_complete_percentage = round((($affiliatepress_payout_added_affiliate / $affiliatepress_payout_total_affiliate) * 100),2);
                    }
                }else{
                    $affiliatepress_complete_percentage = 100;
                }    
            }
            $response['variant'] = 'success';
            $response['complete_percentage'] = $affiliatepress_complete_percentage;
            $response['title']   = esc_html__( 'Completed', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Payout Succesfully Created.', 'affiliatepress-affiliate-marketing');
            wp_send_json( $response );
            die;            
        }    

        /**
         * Function for generate payout cron data
         *
         * @param  mixed $affiliatepress_payout_upto_date
         * @param  mixed $affiliatepress_ap_payout_selected_affiliate
         * @return mixed
        */
        function affiliatepress_generate_payout_process($affiliatepress_is_return = false){
            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_payouts, $affiliatepress_tbl_ap_payments, $affiliatepress_tbl_ap_payment_commission,$affiliatepress_tbl_ap_affiliate_commissions;
            $affiliatepress_last_payout_record = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payouts, '*', '', '', '', ' order by ap_payout_id DESC ', '', false, true,ARRAY_A);   
            $affiliatepress_ap_payout_process = (isset($affiliatepress_last_payout_record['ap_payout_process']))?$affiliatepress_last_payout_record['ap_payout_process']:'';    
            
            $affiliatepress_payout_process = get_option('affiliatepress_payout_process');
            $affiliatepress_payout_process = '';

            $affiliatepress_tbl_ap_payments_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_payments);
	    
            $affiliatepress_payment_method = (isset($affiliatepress_last_payout_record['ap_payment_method']))?$affiliatepress_last_payout_record['ap_payment_method']:'';
            if(empty($affiliatepress_payment_method)){
                $affiliatepress_payment_method = 'manual';
            }    

            if($affiliatepress_ap_payout_process == 0 && !empty($affiliatepress_last_payout_record) && empty($affiliatepress_payout_process)){                
                $affiliatepress_ap_payout_created_by = (isset($affiliatepress_last_payout_record['ap_payout_created_by']))?$affiliatepress_last_payout_record['ap_payout_created_by']:'';
                $affiliatepress_payout_upto_date = (isset($affiliatepress_last_payout_record['ap_payout_upto_date']))?$affiliatepress_last_payout_record['ap_payout_upto_date']:'';
                $affiliatepress_ap_payout_selected_affiliate = (isset($affiliatepress_last_payout_record['ap_payout_selected_affiliate']))?$affiliatepress_last_payout_record['ap_payout_selected_affiliate']:array();                
                $affiliatepress_payout_id = (isset($affiliatepress_last_payout_record['ap_payout_id']))?$affiliatepress_last_payout_record['ap_payout_id']:0;
                if($affiliatepress_ap_payout_created_by != 0){
                    if(!empty($affiliatepress_ap_payout_selected_affiliate)){
                        $affiliatepress_ap_payout_selected_affiliate = explode(",",$affiliatepress_ap_payout_selected_affiliate);
                    }else{
                        $affiliatepress_ap_payout_selected_affiliate = array('none');
                    }                                            
                }
                $affiliatepress_payout_data = $this->affiliatepress_generate_payout_data($affiliatepress_payout_upto_date,$affiliatepress_ap_payout_selected_affiliate);

                do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'Payout ID #'.$affiliatepress_payout_id.' Data : ', 'affiliatepress_manual_payout_tracking', wp_json_encode(array('affiliatepress_last_payout_record' => $affiliatepress_last_payout_record)), $affiliatepress_payout_id);

                $affiliatepress_default_currency = $AffiliatePress->affiliatepress_get_default_currency_code();
                $affiliatepress_total_affiliate = 0;
                $affiliatepress_total_payout_amount = 0;

                update_option('affiliatepress_payout_process',$affiliatepress_payout_id);

                if(isset($affiliatepress_payout_data['payout_affiliates']) && !empty($affiliatepress_payout_data['payout_affiliates'])){                      
                    foreach($affiliatepress_payout_data['payout_affiliates'] as $affiliatepress_payout_payment_detail){
                        $affiliatepress_payment_id = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'ap_payment_id', 'WHERE ap_affiliates_id = %d AND ap_payout_id = %d ', array( $affiliatepress_payout_payment_detail['ap_affiliates_id'], $affiliatepress_payout_id ), '', '', '', true, false,ARRAY_A));
                        if($affiliatepress_payment_id == 0){
                            
                            $wpdb->query($wpdb->prepare("UPDATE {$affiliatepress_tbl_ap_payments_temp} SET ap_payment_status = 5 WHERE ap_affiliates_id = %d AND ap_payment_status != 4", $affiliatepress_payout_payment_detail['ap_affiliates_id'])); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_payments_temp is a table name. false alarm  

                            $affiliatepress_payment_method = $this->affiliatepress_get_affiliate_payment_method($affiliatepress_payout_payment_detail['ap_affiliates_id']);

                            $affiliatepress_ap_payment_status = 1;
                            $affiliatepress_auto_payout = "false";
                            if($affiliatepress_payment_method != 'manual'){
                                $affiliatepress_ap_payment_status = 2;
                            }else{
                                $affiliatepress_auto_payout = isset($_POST['auto_approved_payouts']) ? sanitize_text_field(wp_unslash($_POST['auto_approved_payouts'])) : "false";//phpcs:ignore

                                if($affiliatepress_auto_payout == "true"){
                                    $affiliatepress_ap_payment_status = 4;
                                }
                            }
                            
                            $affiliatepress_args = array(                
                                'ap_payout_id'                  => $affiliatepress_payout_id,
                                'ap_affiliates_id'              => $affiliatepress_payout_payment_detail['ap_affiliates_id'],
                                'ap_affiliates_name'            => $affiliatepress_payout_payment_detail['affiiate_name'],
                                'ap_payment_amount'             => $affiliatepress_payout_payment_detail['total_amount'],                                                
                                'ap_payment_currency'           => $affiliatepress_default_currency,
                                'ap_payment_method'             => $affiliatepress_payment_method,
                                'ap_payment_status'             => $affiliatepress_ap_payment_status,
                                'ap_payment_visit'              => $affiliatepress_payout_payment_detail['ap_payout_visit_count'],
                                'ap_payment_created_date'       => date('Y-m-d H:i:s', current_time('timestamp'))//phpcs:ignore
                            );
                            $affiliatepress_payment_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_payments, $affiliatepress_args);
                            if($affiliatepress_payment_id && !empty($affiliatepress_payout_payment_detail)){

                                do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'Payment ID #'.$affiliatepress_payment_id.' Data : ', 'affiliatepress_manual_payout_tracking', wp_json_encode($affiliatepress_args), $affiliatepress_payout_id);

                                foreach($affiliatepress_payout_payment_detail['commission_record'] as $affiliatepress_payment_commission){
                                    $affiliatepress_args = array(                
                                        'ap_payment_id'                 => $affiliatepress_payment_id,
                                        'ap_payout_id'                  => $affiliatepress_payout_id,
                                        'ap_affiliates_id'              => $affiliatepress_payout_payment_detail['ap_affiliates_id'],
                                        'ap_commission_id'              => $affiliatepress_payment_commission['ap_commission_id'],                
                                        'ap_commission_amount'          => $affiliatepress_payment_commission['ap_commission_amount'],
                                        'ap_payment_commission_created_date'  => date('Y-m-d H:i:s', current_time('timestamp'))//phpcs:ignore
                                    );
                                    $this->affiliatepress_insert_record($affiliatepress_tbl_ap_payment_commission, $affiliatepress_args);      
                                    
                                    if($affiliatepress_auto_payout == "true" && $affiliatepress_payment_method =="manual"){
                                        $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, array('ap_commission_status'=>$affiliatepress_ap_payment_status), array( 'ap_commission_id' => $affiliatepress_payment_commission['ap_commission_id'] ));
                                    }

                                }     
                                
                                do_action('affiliatepress_after_change_payment_status',$affiliatepress_payment_id,$affiliatepress_ap_payment_status);
                            }
    
                        }
                    }

                }

                $affiliatepress_payout_total_affiliate = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'count(ap_payment_id)', 'WHERE ap_payout_id = %d ', array( $affiliatepress_payout_id ), '', '', '', true, false,ARRAY_A));
                $affiliatepress_payout_amount = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'SUM(ap_payment_amount)', 'WHERE ap_payout_id = %d ', array( $affiliatepress_payout_id ), '', '', '', true, false,ARRAY_A));
                
                $affiliatepress_args = array(
                    'ap_payout_total_affiliate' => $affiliatepress_payout_total_affiliate,
                    'ap_payout_amount'          => $affiliatepress_payout_amount,
                    'ap_payout_process'         => 1,
                );                

                do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'Payout Successfully Generated ', 'affiliatepress_manual_payout_tracking', wp_json_encode($affiliatepress_args), $affiliatepress_payout_id);

                $this->affiliatepress_update_record($affiliatepress_tbl_ap_payouts, $affiliatepress_args, array( 'ap_payout_id' => $affiliatepress_payout_id ));

                do_action('affiliatepress_after_payout_generate', $affiliatepress_payout_id);

                update_option('affiliatepress_payout_process','');

                if($affiliatepress_is_return){
                    return true;
                }
            }
            if($affiliatepress_is_return){
                return false;
            }            
        }

        /**
         * Function for generate payout process
         *
         * @return json
        */
        function affiliatepress_generate_payout_process_request_func(){
            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_payouts, $affiliatepress_tbl_ap_payments, $affiliatepress_tbl_ap_payment_commission;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'generate_payout', true, 'ap_wp_nonce' );            
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
            
            if(!current_user_can('affiliatepress_payout')){
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

            $this->affiliatepress_generate_payout_process(true);
            $response['variant'] = 'success';
            $response['affiliates'] = '';
            $response['title']   = esc_html__( 'Completed', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Payout Succesfully Created.', 'affiliatepress-affiliate-marketing');
            wp_send_json( $response );
            die;            
        }    

        /**
         * Function for generate payout request
         *
         * @return void
        */
        function affiliatepress_generate_payout_request_func(){
            global $wpdb,$AffiliatePress,$affiliatepress_tbl_ap_payouts,$affiliatepress_payout_debug_log_id,$affiliatepress_tracking;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'generate_payout', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_payout')){
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

            $affiliatepress_payout_upto = (isset($_POST['payout_upto']))?sanitize_text_field($_POST['payout_upto']):'';// phpcs:ignore 
            $affiliatepress_allow_affiliates = (isset($_POST['allow_affiliates']))?$_POST['allow_affiliates']:''; // phpcs:ignore 
            if(empty($affiliatepress_payout_upto)){
                $response['msg']     = esc_html__( 'Please add payout upto date.', 'affiliatepress-affiliate-marketing');
                wp_send_json( $response );
                die;                
            }else if(empty($affiliatepress_allow_affiliates) || !is_array($affiliatepress_allow_affiliates)){
                $response['msg']     = esc_html__( 'Please select one affiliate to generate payout.', 'affiliatepress-affiliate-marketing');
                wp_send_json( $response );
                die;                                
            }else{

                    $affiliatepress_payment_method = (isset($_POST['payment_method']))?sanitize_text_field($_POST['payment_method']):'';// phpcs:ignore 
                    if(empty($affiliatepress_payment_method)){
                        $affiliatepress_payment_method = 'manual';
                    }

                    $affiliatepress_preview_total_affiliate = (isset($_POST['preview_total_affiliate']))?intval($_POST['preview_total_affiliate']):0;// phpcs:ignore 
                    $affiliatepress_minimum_payment_amount = $AffiliatePress->affiliatepress_get_settings('minimum_payment_amount', 'commissions_settings');
                    $affiliatepress_minimum_payment_order = $affiliatepress_tracking->affiliatepress_get_payout_minimum_payment_order();
                    $affiliatepress_allow_affiliates = implode(",",$affiliatepress_allow_affiliates);
                    $affiliatepress_args = array(                
                        'ap_payout_created_by'          => get_current_user_ID(),
                        'ap_payout_upto_date'           => $affiliatepress_payout_upto,
                        'ap_payout_total_affiliate'     => $affiliatepress_preview_total_affiliate,
                        'ap_payout_selected_affiliate'  => $affiliatepress_allow_affiliates,   
                        'ap_payment_method'             => $affiliatepress_payment_method,
                        'ap_payment_min_amount'         => $affiliatepress_minimum_payment_amount,          
                        'ap_payout_process'             => 0,
                        'ap_payout_created_date'        => date('Y-m-d H:i:s', current_time('timestamp')),//phpcs:ignore
                        'ap_payment_min_order'          => $affiliatepress_minimum_payment_order,
                    );  

                    $affiliatepress_payout_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_payouts, $affiliatepress_args);
                    if($affiliatepress_payout_id){
                        $response['variant'] = 'success';                        
                        $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                        $response['msg']     = esc_html__('Affiliate Data.', 'affiliatepress-affiliate-marketing');
                        $response['payout_id']     = $affiliatepress_payout_id;
                        
                        
                        do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'Manual Payout Created : '.$affiliatepress_payout_id, 'affiliatepress_manual_payout_tracking', 'Manual Payout Created : '.$affiliatepress_payout_id, $affiliatepress_payout_id);

                    }


            }
            wp_send_json( $response );
            die;            
        }


        /**
         * Payout Data Generate For All Selected Affiliates  
         *
         * @param  mixed $affiliatepress_payout_upto_date
         * @return void
        */
        function affiliatepress_generate_payout_data($affiliatepress_payout_upto_date = '',$affiliatepress_include_affiliate = array(), $affiliatepress_payment_method = 'manual'){

            global $wpdb,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_affiliates,$AffiliatePress,$affiliatepress_tracking;

            $this->affiliatepress_payout_affiliate_limit = 500;
            
            $affiliatepress_tbl_ap_affiliate_commissions_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions);// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_commissions contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_user_table = $this->affiliatepress_tablename_prepare($wpdb->users); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->users contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $wp_usermeta_table = $this->affiliatepress_tablename_prepare($wpdb->usermeta); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->usermeta contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_tbl_ap_affiliates_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function  
            $affiliatepress_payout_data = array();
            $affiliatepress_where_clause = " WHERE 1 = 1 ";
            $affiliatepress_where_clause.= $wpdb->prepare( " AND (commissions.ap_commission_status = %d) ", 1);
            $affiliatepress_where_clause.= $wpdb->prepare( " AND DATE(commissions.ap_commission_created_date) <= %s ", $affiliatepress_payout_upto_date);

            $refund_grace_period = intval($AffiliatePress->affiliatepress_get_settings('refund_grace_period', 'commissions_settings'));
            if(!empty($affiliatepress_include_affiliate) && is_array($affiliatepress_include_affiliate)){
                $affiliatepress_all_affiliatestring = implode(",",$affiliatepress_include_affiliate);
                $affiliatepress_where_clause.= " AND commissions.ap_affiliates_id IN (".$affiliatepress_all_affiliatestring.") ";
            }
            $affiliatepress_commission_compare_date = '';
            if($refund_grace_period > 0){
                $affiliatepress_currentDate = new DateTime();
                $affiliatepress_currentDate->modify("-{$refund_grace_period} days"); 
                $affiliatepress_commission_compare_date = $affiliatepress_currentDate->format('Y-m-d');
                $affiliatepress_where_clause.= $wpdb->prepare( " AND DATE(commissions.ap_commission_created_date) < %s ", $affiliatepress_commission_compare_date);
            }

            $affiliatepress_where_clause.= $wpdb->prepare( " AND ap_commission_payment_id = %d ", 0);

            $affiliatepress_commissions_record = $wpdb->get_results("SELECT commissions.ap_commission_id,commissions.ap_affiliates_id,commissions.ap_commission_amount,commissions.ap_commission_currency,commissions.ap_commission_created_date, affiliate.ap_affiliates_user_id, affiliate.ap_affiliates_user_email as user_email, affiliate.ap_affiliates_first_name, affiliate.ap_affiliates_last_name, affiliate.ap_affiliates_user_name, affiliate.ap_affiliates_payment_email  FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} as commissions INNER JOIN {$affiliatepress_tbl_ap_affiliates_temp} as affiliate  ON (commissions.ap_affiliates_id = affiliate.ap_affiliates_id AND affiliate.ap_affiliates_status = 1)  {$affiliatepress_where_clause}  order by commissions.ap_commission_id ASC", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates_temp is a table name. false alarm

            if(empty($affiliatepress_payment_method)){
                $affiliatepress_payment_method = 'manualy';
            }
            $affiliatepress_payment_method = $AffiliatePress->affiliatepress_get_payment_method_name_by_slug($affiliatepress_payment_method);

            $affiliatepress_payout_affiliates = array();            
            if(!empty($affiliatepress_commissions_record)){
                
                foreach($affiliatepress_commissions_record as $affiliatepress_commission_rec){
                    
                    $affiliatepress_total_added_affiliate_in_payout = count($affiliatepress_payout_affiliates);
                    if($affiliatepress_total_added_affiliate_in_payout >= $this->affiliatepress_payout_affiliate_limit){
                        break;
                    }
                    
                    if(array_key_exists($affiliatepress_commission_rec['ap_affiliates_id'],$affiliatepress_payout_affiliates)){
                        $affiliatepress_payout_affiliates[$affiliatepress_commission_rec['ap_affiliates_id']]['total_amount'] = $affiliatepress_payout_affiliates[$affiliatepress_commission_rec['ap_affiliates_id']]['total_amount']+$affiliatepress_commission_rec['ap_commission_amount'];
                        $affiliatepress_payout_affiliates[$affiliatepress_commission_rec['ap_affiliates_id']]['commission_record'][] = array('ap_commission_id'=>$affiliatepress_commission_rec['ap_commission_id'],'ap_commission_amount'=>$affiliatepress_commission_rec['ap_commission_amount']);
                    }else{                                      
                        
                        $ap_affiliates_first_name = esc_html($affiliatepress_commission_rec['ap_affiliates_first_name']);
                        $ap_affiliates_last_name  = esc_html($affiliatepress_commission_rec['ap_affiliates_last_name']);
                        $affiliatepress_full_name = $ap_affiliates_first_name.' '.$ap_affiliates_last_name;
                        $affiliatepress_affiliates_user_name  = esc_html($affiliatepress_commission_rec['ap_affiliates_user_name']);
                        $affiliatepress_affiliates_payment_email  = esc_html($affiliatepress_commission_rec['ap_affiliates_payment_email']);

                        $affiliatepress_visit_count = $this->affiliatepress_preview_payout_visit_count($affiliatepress_commission_rec['ap_affiliates_id'],$affiliatepress_payout_upto_date,$affiliatepress_commissions_record);

                        $affiliatepress_payout_affiliates[$affiliatepress_commission_rec['ap_affiliates_id']] = array('payment_method'=>$affiliatepress_payment_method,'ap_affiliates_id'=>$affiliatepress_commission_rec['ap_affiliates_id'],'ap_payout_visit_count'=>$affiliatepress_visit_count,'affiiate_name'=>$affiliatepress_full_name,'affiiate_user_name'=>$affiliatepress_affiliates_user_name,'affiiate_payment_email'=>$affiliatepress_affiliates_payment_email,'total_amount'=>$affiliatepress_commission_rec['ap_commission_amount'],'commission_record'=>array());
                        $affiliatepress_payout_affiliates[$affiliatepress_commission_rec['ap_affiliates_id']]['commission_record'][] = array('ap_commission_id'=>$affiliatepress_commission_rec['ap_commission_id'],'ap_commission_amount'=>$affiliatepress_commission_rec['ap_commission_amount']);
                    }
                }
            }
            $affiliatepress_total_payment_amount = 0;
            $affiliatepress_total_affiliate = 0;
            $affiliatepress_all_payout_affiliate = array();
            $affiliatepress_minimum_payment_amount = floatval($AffiliatePress->affiliatepress_get_settings('minimum_payment_amount', 'commissions_settings'));
            $affiliatepress_minimum_payment_order = $affiliatepress_tracking->affiliatepress_get_payout_minimum_payment_order();
            $affiliatepress_final_payout_affiliate = array();
            if(!empty($affiliatepress_payout_affiliates)){
                $affiliatepress_i = 0;
                foreach($affiliatepress_payout_affiliates as $affiliatepress_key=>$affiliatepress_value){ 
                    $affiliatepress_total_commission_count = count($affiliatepress_payout_affiliates[$affiliatepress_key]['commission_record']);  
                    if($affiliatepress_payout_affiliates[$affiliatepress_key]['total_amount'] >= $affiliatepress_minimum_payment_amount &&
                    $affiliatepress_total_commission_count >= $affiliatepress_minimum_payment_order){
                        $affiliatepress_final_payout_affiliate[$affiliatepress_i] = $affiliatepress_value;
                        $affiliatepress_payout_affiliates[$affiliatepress_key]['total_amount'] = round($affiliatepress_payout_affiliates[$affiliatepress_key]['total_amount'],2);
                        $affiliatepress_total_payment_amount = $affiliatepress_total_payment_amount + $affiliatepress_payout_affiliates[$affiliatepress_key]['total_amount'];
                        $affiliatepress_total_affiliate = $affiliatepress_total_affiliate + 1;
                        $affiliatepress_final_payout_affiliate[$affiliatepress_i]['total_commission'] = count($affiliatepress_payout_affiliates[$affiliatepress_key]['commission_record']);

                        $affiliatepress_total_visit = $affiliatepress_final_payout_affiliate[$affiliatepress_i]['ap_payout_visit_count'];
                        $affiliatepress_total_commisison = $affiliatepress_final_payout_affiliate[$affiliatepress_i]['total_commission'];

                        if($affiliatepress_total_visit > 0){
                            $affiliatepress_conversion_rate = round(($affiliatepress_total_commisison / $affiliatepress_total_visit) * 100, 2);
                        }else{
                            $affiliatepress_conversion_rate = 0;
                        }

                        $affiliatepress_final_payout_affiliate[$affiliatepress_i]['ap_payout_visit_conversion_rate'] = $affiliatepress_conversion_rate;
                        $affiliatepress_total_amount_formted = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_payout_affiliates[$affiliatepress_key]['total_amount']);
                        $affiliatepress_final_payout_affiliate[$affiliatepress_i]['total_amount_formted'] = $affiliatepress_total_amount_formted;
                        $affiliatepress_all_payout_affiliate[] = $affiliatepress_key;
                        $affiliatepress_i++;
                    }                    
                }
            }
            $affiliatepress_total_payment_amount = round($affiliatepress_total_payment_amount,2);
            $affiliatepress_total_payment_amount_formated = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_total_payment_amount);
            return array(
                'total_payment_amount' => $affiliatepress_total_payment_amount,
                'total_payment_amount_formated' => $affiliatepress_total_payment_amount_formated,
                'total_affiliate'      => $affiliatepress_total_affiliate,
                'payout_affiliates'    => $affiliatepress_final_payout_affiliate,
                'all_payout_affiliate' => $affiliatepress_all_payout_affiliate,
                'commission_compare_date' => $affiliatepress_commission_compare_date
            );

        }

        function affiliatepress_preview_payout_visit_count($affiliatepress_affiliate_id,$affiliatepress_payout_upto_date,$affiliatepress_affiliate_all_commisison){

            global $affiliatepress_payout_debug_log_id,$affiliatepress_tbl_ap_payouts,$wpdb,$affiliatepress_tbl_ap_affiliate_visits,$affiliatepress_tbl_ap_payments;

            $affiliatepress_visit_count = 0;

            if(!empty($affiliatepress_affiliate_id) && $affiliatepress_affiliate_id != 0)
            {
                $affiliatepress_old_commission_date = min(array_column($affiliatepress_affiliate_all_commisison,'ap_commission_created_date' ));
                $affiliatepress_old_commission_date = date('Y-m-d 00:00:00',strtotime($affiliatepress_old_commission_date));//phpcs:ignore

                $affiliatepress_payout_upto_date = date('Y-m-d 23:59:59',strtotime($affiliatepress_payout_upto_date));//phpcs:ignore

                $affiliatepress_last_payout_record = $wpdb->get_row($wpdb->prepare("SELECT payout.* FROM {$affiliatepress_tbl_ap_payouts} AS payout INNER JOIN {$affiliatepress_tbl_ap_payments} AS payment   ON payout.ap_payout_id = payment.ap_payout_id WHERE payment.ap_affiliates_id = %d AND payment.ap_payment_status = %d ORDER BY payout.ap_payout_created_date DESC LIMIT 1", $affiliatepress_affiliate_id, 4),ARRAY_A);// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm

                do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'Payout visit count - last payout record data', 'affiliatepress_auto_payout_tracking', wp_json_encode($affiliatepress_last_payout_record), $affiliatepress_payout_debug_log_id);

                $affiliatepress_last_payout_upto_date = isset($affiliatepress_last_payout_record['ap_payout_upto_date']) ? date('Y-m-d 23:59:59',strtotime($affiliatepress_last_payout_record['ap_payout_upto_date'])) : '';//phpcs:ignore
                $affiliatepress_visit_count_last_date = $affiliatepress_last_payout_upto_date;
                $affiliatepress_current_payout_date = date('Y-m-d 23:59:59',strtotime($affiliatepress_payout_upto_date));//phpcs:ignore

                do_action('affiliatepress_payout_debug_log_entry', 'payout_tracking_debug_logs', 'Payout visit count - Date', 'affiliatepress_auto_payout_tracking', 'last payout date = '.$affiliatepress_visit_count_last_date .' and current payout date ='.$affiliatepress_current_payout_date, $affiliatepress_payout_debug_log_id);

                if(empty($affiliatepress_visit_count_last_date)){
                    $affiliatepress_visit_where_clause = 'WHERE ap_affiliates_id = %d AND ap_visit_created_date <= %s';
                    $affiliatepress_visit_where_clause_array = array($affiliatepress_affiliate_id,$affiliatepress_current_payout_date);
                }else{

                    $affiliatepress_visit_count_last_date = min($affiliatepress_old_commission_date, $affiliatepress_last_payout_upto_date );

                    $affiliatepress_visit_where_clause  = 'WHERE ap_affiliates_id = %d AND ap_visit_created_date BETWEEN %s AND %s';
                    $affiliatepress_visit_where_clause_array = array($affiliatepress_affiliate_id,$affiliatepress_visit_count_last_date,$affiliatepress_current_payout_date);
                }

                $affiliatepress_visit_count = intval( $this->affiliatepress_select_record(true,'',$affiliatepress_tbl_ap_affiliate_visits,'COUNT(*)',$affiliatepress_visit_where_clause,$affiliatepress_visit_where_clause_array, '','','',true,false,ARRAY_A));
            }

            do_action('affiliatepress_payout_debug_log_entry','payout_tracking_debug_logs','Payout visit count - RESULT','affiliatepress_auto_payout_tracking', 'Visit count = ' . $affiliatepress_visit_count,$affiliatepress_payout_debug_log_id);

            return $affiliatepress_visit_count;
            
        }

        /**
         * Function for generate payout preview
         *
         * @return void
        */
        function affiliatepress_generate_payout_preview_func(){
            global $wpdb,$AffiliatePress;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'generate_preview', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_payout')){
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

            $affiliatepress_payout_upto = (isset($_POST['payout_upto']))?sanitize_text_field($_POST['payout_upto']):'';// phpcs:ignore 
            if(empty($affiliatepress_payout_upto)){
                $response['msg']     = esc_html__( 'Please add payout upto date.', 'affiliatepress-affiliate-marketing');
                wp_send_json( $response );
                die;                
            }else{
                $affiliatepress_payment_method = (isset($_POST['payment_method']))?sanitize_text_field($_POST['payment_method']):'';// phpcs:ignore 
                if(empty($affiliatepress_payment_method)){
                    $affiliatepress_payment_method = 'manual';
                }
                $affiliatepress_selected_affiliate = array();
                $affiliatepress_payout_data = $this->affiliatepress_generate_payout_data($affiliatepress_payout_upto, $affiliatepress_selected_affiliate, $affiliatepress_payment_method); 

                $affiliatepress_payout_data = apply_filters('affiliatepress_modify_payout_data',$affiliatepress_payout_data);
              
                foreach ($affiliatepress_payout_data['payout_affiliates'] as $key => $affiliate) {
                    if (isset($affiliate['payment_method'])) {
                        $affiliatepress_payout_data['payout_affiliates'][$key]['payment_method_label'] = ucfirst($affiliate['payment_method']);
                    }
                }

                if(!isset($affiliatepress_payout_data['payout_affiliates']) || empty($affiliatepress_payout_data['payout_affiliates'])){ // phpcs:ignore                     
                    $response['msg'] = esc_html__( 'No payout commission found for the selected date.', 'affiliatepress-affiliate-marketing');
                    wp_send_json( $response );
                    die;                     
                }else{
                    $affiliatepress_total_payment_amount = (isset($affiliatepress_payout_data['total_payment_amount']))?floatval($affiliatepress_payout_data['total_payment_amount']):0;
                    $affiliatepress_total_payment_amount_display = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_total_payment_amount); 
                    $affiliatepress_payout_data['total_payment_amount_display'] = $affiliatepress_total_payment_amount_display;
                    $response['variant'] = 'success';
                    $response['payout_data'] = $affiliatepress_payout_data;
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Affiliate Data.', 'affiliatepress-affiliate-marketing');
                }
            }
            wp_send_json( $response );
            die;                


        }

        /**
         * Function For generate payout export data
         *
         * @return void
         */
        function affiliatepress_generate_export_payout_func(){
            global $wpdb, $affiliatepress_tbl_ap_affiliates, $affiliatepress_affiliates, $AffiliatePress,$affiliatepress_tbl_ap_payouts;

            // Authentication Check
            $affiliatepress_check_authorization = $this->affiliatepress_ap_check_authentication('export_payout', true, 'ap_wp_nonce');
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

            $affiliatepress_wpnonce               = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';// phpcs:ignore
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

            $affiliatepress_export_payout_id = isset($_GET['export_payout_id']) ? sanitize_text_field(wp_unslash($_GET['export_payout_id'])) : '';
            $affiliatepress_export_data = $_GET;

            $affiliatepress_exports_data = $this->affiliatepress_get_affiliate_export_data($affiliatepress_export_payout_id);
            $affiliatepress_export_file_name = 'payout';

            $affiliatepress_columns = $this->affiliatepress_get_export_payouts_columns();

            $affiliatepress_filename = 'affiliatepress-export-'.$affiliatepress_export_file_name.'-'.date('Y-m-d') . '.csv'; //phpcs:ignore

            ob_clean();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $affiliatepress_filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            $affiliatepress_output = fopen('php://output', 'w');
            fputcsv($affiliatepress_output, array_values($affiliatepress_columns));
            foreach ($affiliatepress_exports_data as $affiliatepress_export_data) {
                fputcsv($affiliatepress_output, $affiliatepress_export_data);
            }
            fclose($affiliatepress_output);//phpcs:ignore
            exit;
        }

        function affiliatepress_payout_method_add_func($affiliatepress_payment_method){

            global $AffiliatePress;
            $affiliatepress_payment_method['manual'] = esc_html__( 'Manual', 'affiliatepress-affiliate-marketing' );// phpcs:ignore;

           return $affiliatepress_payment_method;
       }

        /**
         * Function For generate payout data for column
         *
         * @return array
         */
        function affiliatepress_get_export_payouts_columns(){

            $affiliatepress_payout_paymnet_method = array();
            $affiliatepress_payout_paymnet_method = apply_filters('affiliatepress_payout_payment_method',$affiliatepress_payout_paymnet_method);
            
            $affiliatepress_export_col = array(
                'ap_payment_id'           => __( 'Payment ID', 'affiliatepress-affiliate-marketing' ),
                'ap_affiliates_id'        => __( 'Affiliate ID', 'affiliatepress-affiliate-marketing' ),
                'ap_affiliates_user_name' => __( 'Affiliate Username', 'affiliatepress-affiliate-marketing' ),
                'ap_affiliates_email'     => __( 'Affiliate Email', 'affiliatepress-affiliate-marketing' ),
                'ap_affiliates_payment_email'   => __( 'Affiliate Payment Email', 'affiliatepress-affiliate-marketing' ),
                'ap_affiliates_name'      => __( 'Affiliate Name', 'affiliatepress-affiliate-marketing' ),
                'ap_payment_amount'       => __( 'Amount', 'affiliatepress-affiliate-marketing' ),
                'ap_payment_method'       => __( 'Payment Method', 'affiliatepress-affiliate-marketing' ),
                'ap_payment_status'       => __( 'Payment Status', 'affiliatepress-affiliate-marketing' ),
                'ap_total_commission'       => __( 'Total Commission', 'affiliatepress-affiliate-marketing' ),
                'ap_total_visit'       => __( 'Total Visit', 'affiliatepress-affiliate-marketing' ),
                'ap_conversion_rate'       => __( 'Conversion Rate', 'affiliatepress-affiliate-marketing' ),
            );

            $affiliatepress_export_col = apply_filters('affiliatepress_export_payout_extra_details',$affiliatepress_export_col,$affiliatepress_payout_paymnet_method);

            return $affiliatepress_export_col;
        }

         /**
         * Function For generate payout data
         *
         * @return array
         */
        function affiliatepress_get_affiliate_export_data($affiliatepress_export_payout_id){
            global  $wpdb,$affiliatepress_tbl_ap_payments,$affiliatepress_global_options,$affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_payment_commission;

            if(empty($affiliatepress_export_payout_id)){
                return;
            }

            $affiliatepress_where_clause = " WHERE 1 = 1 ";
            $affiliatepress_where_clause.= $wpdb->prepare( " AND (payment.ap_payout_id = %d) ", $affiliatepress_export_payout_id);

            $affiliatepress_export_payouts_data = $wpdb->get_results("SELECT payment.*, affiliate.ap_affiliates_user_email, affiliate.ap_affiliates_payment_email, affiliate.ap_affiliates_user_name FROM {$affiliatepress_tbl_ap_payments} as payment INNER JOIN {$affiliatepress_tbl_ap_affiliates} as affiliate  ON payment.ap_affiliates_id = affiliate.ap_affiliates_id {$affiliatepress_where_clause}", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm

            $affiliatepress_options = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_all_payment_status_list = $affiliatepress_options['payment_status'];
            $affiliatepress_all_payment_status = array();
            if(!empty($affiliatepress_all_payment_status_list)){
                foreach($affiliatepress_all_payment_status_list as $affiliatepress_commission_status){
                    $affiliatepress_all_payment_status[$affiliatepress_commission_status['value']] = $affiliatepress_commission_status['text'];
                }
            }

            $affiliatepress_payouts = array();   
            if(!empty($affiliatepress_export_payouts_data)){

                foreach($affiliatepress_export_payouts_data as $affiliatepress_export_payouts){
                    $affiliatepress_payment_affiliate_id = (isset($affiliatepress_export_payouts['ap_affiliates_id']))?stripslashes_deep($affiliatepress_export_payouts['ap_affiliates_id']):0;
                    $affiliatepress_payment_amount =(isset($affiliatepress_export_payouts['ap_payment_amount']))?stripslashes_deep($affiliatepress_export_payouts['ap_payment_amount']):0;
                    $affiliatepress_payment_currency =(isset($affiliatepress_export_payouts['ap_payment_currency']))?stripslashes_deep($affiliatepress_export_payouts['ap_payment_currency']):0;
                    $affiliatepress_payment_amount_with_currency = $affiliatepress_payment_amount .' '. $affiliatepress_payment_currency;
                    $affiliatepress_payment_method = (isset($affiliatepress_export_payouts['ap_payment_method']))?stripslashes_deep($affiliatepress_export_payouts['ap_payment_method']):'-';

                    $affiliatepress_payout['ap_payment_id'] = (isset($affiliatepress_export_payouts['ap_payment_id']))?stripslashes_deep($affiliatepress_export_payouts['ap_payment_id']):0;
                    $affiliatepress_payout['ap_affiliates_id'] = $affiliatepress_payment_affiliate_id;
                    $affiliatepress_payout['ap_affiliates_user_name'] = (isset($affiliatepress_export_payouts['ap_affiliates_user_name']))?stripslashes_deep($affiliatepress_export_payouts['ap_affiliates_user_name']):'-';
                    $affiliatepress_payout['ap_affiliates_email'] = (isset($affiliatepress_export_payouts['ap_affiliates_user_email']))?stripslashes_deep($affiliatepress_export_payouts['ap_affiliates_user_email']):'-';
                    $affiliatepress_payout['ap_affiliates_payment_email'] = (isset($affiliatepress_export_payouts['ap_affiliates_payment_email']))?stripslashes_deep($affiliatepress_export_payouts['ap_affiliates_payment_email']):'-';
                    $affiliatepress_payout['ap_affiliates_name'] = (isset($affiliatepress_export_payouts['ap_affiliates_name']))?stripslashes_deep($affiliatepress_export_payouts['ap_affiliates_name']):'-';
                    $affiliatepress_payout['ap_payment_amount'] = $affiliatepress_payment_amount_with_currency;
                    $affiliatepress_payout['ap_payment_method'] = $affiliatepress_payment_method;
                    $affiliatepress_payout['ap_payment_status'] = (isset($affiliatepress_all_payment_status[$affiliatepress_export_payouts['ap_payment_status']])) ? stripslashes_deep($affiliatepress_all_payment_status[$affiliatepress_export_payouts['ap_payment_status']]) : '';

                    $affiliatepress_payout['ap_total_commission'] = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payment_commission, 'count(ap_payment_commission_id)', 'WHERE ap_payment_id  = %d', array($affiliatepress_payout['ap_payment_id']), '', '', '', true, false,ARRAY_A));

                    $affiliatepress_payout['ap_total_visit'] = (isset($affiliatepress_export_payouts['ap_payment_visit']))?intval($affiliatepress_export_payouts['ap_payment_visit']):'-'; 

                    if($affiliatepress_payout['ap_total_visit'] > 0){
                        $affiliatepress_conversion_rate = round(($affiliatepress_payout['ap_total_commission'] /  $affiliatepress_payout['ap_total_visit']) * 100, 2)."%";
                    }else{
                        $affiliatepress_conversion_rate = 0;
                    }
                    $affiliatepress_payout['ap_conversion_rate'] = $affiliatepress_conversion_rate;
                    $affiliatepress_payout = apply_filters('affiliatepress_export_payout_other_data_add' ,$affiliatepress_payout , $affiliatepress_export_payouts,$affiliatepress_payment_method);
                    
                    $affiliatepress_payouts[] = $affiliatepress_payout;
                }
            }

            return $affiliatepress_payouts;
            
        }        
        /**
         * Function for check affiliate user valid for payout
         *
         * @param  integer $affiliatepress_payout_id
         * @return boolean
         */
        function affiliatepress_is_valid_affiliate($affiliatepress_payout_id = 0){            
            global $affiliatepress_tbl_ap_affiliates,$wpdb;
            $affiliatepress_flag = false;
            if($affiliatepress_payout_id){                
                $affiliatepress_rec = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, '*', 'WHERE ap_payout_id  = %d', array( $affiliatepress_payout_id ), '', '', '', false, true,ARRAY_A);
                if(!empty($affiliatepress_rec)){                    
                    $affiliatepress_payout_status = (isset($affiliatepress_rec['ap_payout_status']))?$affiliatepress_rec['ap_payout_status']:'';
                    if($affiliatepress_payout_status == 1){
                        $affiliatepress_flag = true;
                    }
                }
                
            }
            return $affiliatepress_flag;
        }       

        function affiliatepress_get_affiliate_payment_method($affiliate_id){

            $affiliatepress_affiliate_payment_method = "manual";

            $affiliatepress_affiliate_payment_method = apply_filters('affiliatepress_payout_change_paymnet_method',$affiliatepress_affiliate_payment_method,$affiliate_id);

            return $affiliatepress_affiliate_payment_method;
        }

        /**
         * Function for get edit affiliate info 
         *
         * @return void
        */
        function affiliatepress_edit_payout_func(){

            global $wpdb, $affiliatepress_tbl_ap_payouts, $AffiliatePress, $affiliatepress_tbl_ap_payments,$affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_payment_commission,$affiliatepress_global_options,$affiliatepress_tbl_ap_affiliate_visits;
            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'edit_payout', true, 'ap_wp_nonce' );
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

            if(!current_user_can('affiliatepress_payout')){
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

            $affiliatepress_payout_id  =  isset($_POST['edit_id']) ? intval($_POST['edit_id']) : ''; // phpcs:ignore
            $affiliatepress_affiliates_data = array();
            if(!empty($affiliatepress_payout_id)){
                
                $affiliatepress_payout_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payouts, '*', 'WHERE ap_payout_id = %d', array( $affiliatepress_payout_id ), '', '', '', false, true,ARRAY_A);

                if(!empty($affiliatepress_payout_data)){
                    
                    $affiliatepress_payout_data['ap_payout_upto_date_formated'] = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_payout_data['ap_payout_upto_date']);
                    $affiliatepress_payout_data['ap_formated_payout_amount']    = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_payout_data['ap_payout_amount']);
                    
                    $affiliatepress_payout_data['unpaid_affiliate_count'] = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'count(ap_payment_id)', 'WHERE ap_payout_id  = %d AND ap_payment_status <> %d', array( $affiliatepress_payout_data['ap_payout_id'],4), '', '', '', true, false,ARRAY_A)); 
                                      
                    $affiliatepress_payout_data['paid_affiliate_count'] = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'count(ap_payment_id)', 'WHERE ap_payout_id  = %d AND ap_payment_status = %d', array($affiliatepress_payout_data['ap_payout_id'],4), '', '', '', true, false,ARRAY_A));
                    $affiliatepress_payout_data['ap_payment_method']           = ucfirst(esc_html($affiliatepress_payout_data['ap_payment_method']));                                        
                    $affiliatepress_payout_data['ap_payment_min_amount_formated']    = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_payout_data['ap_payment_min_amount']);

                    $affiliatepress_user_table = $this->affiliatepress_tablename_prepare($wpdb->users); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->users contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                    $wp_usermeta_table = $this->affiliatepress_tablename_prepare($wpdb->usermeta); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->usermeta contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                    $affiliatepress_tbl_ap_affiliates_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

                    $affiliatepress_tbl_ap_payments_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_payments); 

                    $affiliatepress_where_clause = $wpdb->prepare( " WHERE payments.ap_payout_id = %d", intval($affiliatepress_payout_id));

                    $affiliatepress_payout_payment_record  = $wpdb->get_results("SELECT payments.ap_payment_id,payments.ap_payment_status,payments.ap_payment_method,payments.ap_payment_visit,payments.ap_payment_amount,payments.ap_payment_note,affiliate.ap_affiliates_user_email, affiliate.ap_affiliates_id, affiliate.ap_affiliates_user_id as ID, affiliate.ap_affiliates_user_email as user_email, affiliate.ap_affiliates_first_name, affiliate.ap_affiliates_last_name, affiliate.ap_affiliates_user_name, affiliate.ap_affiliates_payment_email FROM {$affiliatepress_tbl_ap_payments_temp} as payments LEFT JOIN {$affiliatepress_tbl_ap_affiliates_temp} as affiliate ON payments.ap_affiliates_id = affiliate.ap_affiliates_id  {$affiliatepress_where_clause}", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates_temp is a table name. false alarm

                    $affiliatepress_options = $affiliatepress_global_options->affiliatepress_global_options();
                    $affiliatepress_all_payment_status_data = $affiliatepress_options['payment_status'];
                    

                    $affiliatepress_all_payment_status = array();
                    if(!empty($affiliatepress_all_payment_status_data)){
                        foreach($affiliatepress_all_payment_status_data as $affiliatepress_commission_status){
                            $affiliatepress_all_payment_status[$affiliatepress_commission_status['value']] = $affiliatepress_commission_status['text'];
                        }
                    }

                    $affiliatepress_payout_payments = array();
                    $affiliatepress_common_payment_method = '';
                    $affiliatepress_all_payout_same_payment_method = true;
                    if(!empty($affiliatepress_payout_payment_record)){
                        foreach($affiliatepress_payout_payment_record as $affiliatepress_payout_payment){

                            $affiliatepress_payout_payment_single = $affiliatepress_payout_payment;

                            $affiliatepress_payment_id = intval($affiliatepress_payout_payment['ap_payment_id']);
                            $affiliatepress_affiliate_id = intval($affiliatepress_payout_payment['ap_affiliates_id']);
                            $affiliatepress_user_first_name =  esc_html($affiliatepress_payout_payment['ap_affiliates_first_name']);
                            $affiliatepress_user_last_name  =  esc_html($affiliatepress_payout_payment['ap_affiliates_last_name']);

                            $affiliatepress_full_name = $affiliatepress_user_first_name.' '.$affiliatepress_user_last_name;

                            if(empty(trim($affiliatepress_full_name))){
                                $affiliatepress_full_name = esc_html($affiliatepress_payout_payment['ap_affiliates_user_email']);                                
                            }
                                                       
                            $affiliatepress_payout_payment_single['affiliate_name'] = esc_html($affiliatepress_full_name);
                            $affiliatepress_payout_payment_single['payment_status_change_loader'] = '0';

                            $affiliatepress_payout_payment_single['ap_payment_method_key'] =esc_html($affiliatepress_payout_payment['ap_payment_method']);
                            $affiliatepress_payout_payment_single['ap_payment_method'] = ucfirst(esc_html($affiliatepress_payout_payment['ap_payment_method']));
                            $affiliatepress_payout_payment_single['ap_payment_amount_formated'] = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_payout_payment_single['ap_payment_amount']);
                            $affiliatepress_payout_payment_single['payment_status_name'] = (isset($affiliatepress_all_payment_status[$affiliatepress_payout_payment['ap_payment_status']]))?$affiliatepress_all_payment_status[$affiliatepress_payout_payment['ap_payment_status']]:'';       
                            
                            $affiliatepress_payout_payment_single['ap_affiliates_id'] = intval($affiliatepress_affiliate_id);
                            $affiliatepress_payout_payment_single['ap_affiliates_user_name'] = esc_html($affiliatepress_payout_payment['ap_affiliates_user_name']);
                            $affiliatepress_payout_payment_single['ap_affiliates_payment_email'] = esc_html($affiliatepress_payout_payment['ap_affiliates_payment_email']);
                            $affiliatepress_payout_payment_single['ap_affiliate_visit_count'] = intval($affiliatepress_payout_payment['ap_payment_visit']);
                            $affiliatepress_payout_payment_single['ap_payment_note'] = esc_html($affiliatepress_payout_payment['ap_payment_note']);
                            $affiliatepress_payout_payment_single['ap_affiliate_commission_count'] = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payment_commission, 'count(ap_payment_commission_id)', 'WHERE ap_payment_id  = %d', array($affiliatepress_payment_id), '', '', '', true, false,ARRAY_A));
                            if($affiliatepress_payout_payment_single['ap_affiliate_visit_count'] > 0){
                                $affiliatepress_conversion_rate = round(($affiliatepress_payout_payment_single['ap_affiliate_commission_count'] /  $affiliatepress_payout_payment_single['ap_affiliate_visit_count']) * 100, 2);
                            }else{
                                $affiliatepress_conversion_rate = 0;
                            }
                            
                            $affiliatepress_payout_payment_single['ap_payout_visit_conversion_rate'] = floatval($affiliatepress_conversion_rate);
                            if ($affiliatepress_common_payment_method === '') {
                                $affiliatepress_common_payment_method = $affiliatepress_payout_payment_single['ap_payment_method_key'];
                            } else {
                                if ($affiliatepress_common_payment_method !== $affiliatepress_payout_payment_single['ap_payment_method_key']) {
                                    $affiliatepress_all_payout_same_payment_method = false;
                                }
                            }
                            
                            $affiliatepress_payout_payments[] = $affiliatepress_payout_payment_single;
                        }
                    }

                    if ($affiliatepress_all_payout_same_payment_method) {
                        $affiliatepress_payout_data['affiliatepress_common_payment_method'] = ucfirst(esc_html($affiliatepress_common_payment_method));
                    }else{
                        $affiliatepress_payout_data['affiliatepress_common_payment_method'] = '';
                    }

                    $affiliatepress_payout_final_data = array('edit_payout_data' => $affiliatepress_payout_data, 'edit_payout_payments' => $affiliatepress_payout_payments);
                    $affiliatepress_payout_final_data = apply_filters('affiliatepress_modify_edit_payout_data',$affiliatepress_payout_final_data,$affiliatepress_payout_id);

                    $response['variant'] = 'success';
                    $response['payout_data'] = $affiliatepress_payout_final_data;
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Affiliate Data.', 'affiliatepress-affiliate-marketing');                    
                }

            }
            echo wp_json_encode($response);
            exit;

        }
        
        /**
         * Function for delete single affiliate
         *
        */
        function affiliatepress_delete_payout($affiliatepress_payout_id = ''){
            global $wpdb, $affiliatepress_tbl_ap_payouts,$AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'delete_payout', true, 'ap_wp_nonce' );            
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Affiliates not deleted.', 'affiliatepress-affiliate-marketing');
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_payout')){
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

            if(empty($affiliatepress_payout_id)){
                $affiliatepress_payout_id = (isset($_POST['ap_payout_id']))?intval($_POST['ap_payout_id']):0;// phpcs:ignore 
            }
            if($affiliatepress_payout_id){                
                $this->affiliatepress_delete_record($affiliatepress_tbl_ap_payouts, array( 'ap_payout_id' => $affiliatepress_payout_id ), array( '%d' ));
                do_action( 'affiliatepress_after_delete_payout', $affiliatepress_payout_id );
                $response['variant'] = 'success';
                $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Payout has been deleted successfully.', 'affiliatepress-affiliate-marketing');
                $return              = true;
                if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'affiliatepress_delete_payout' ) { // phpcs:ignore
                    wp_send_json($response);
                }
                return $return;
            }
            $affiliatepress_error_msg = esc_html__( 'Payout not deleted.', 'affiliatepress-affiliate-marketing');
            $response['variant'] = 'warning';
            $response['title']   = esc_html__('warning', 'affiliatepress-affiliate-marketing');
            $response['msg']     = $affiliatepress_error_msg;
            $return              = false;
            if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'affiliatepress_delete_payout' ) { // phpcs:ignore
                wp_send_json($response);
            }
            return $return;

        }

        /**
         * Service module on load methods
         *
         * @return void
         */
        function affiliatepress_payout_dynamic_on_load_methods_func($affiliatepress_payout_dynamic_on_load_methods){

            $affiliatepress_payout_dynamic_on_load_methods.='
                this.loadPayouts().catch(error => {
                    console.error(error)
                });            
            ';

            return $affiliatepress_payout_dynamic_on_load_methods;

        }        
      

        /**
         * Function for get payout data
         *
         * @return json
         */
        function affiliatepress_get_payouts(){
            
            global $wpdb, $affiliatepress_tbl_ap_payouts,$AffiliatePress,$affiliatepress_tbl_ap_payments;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_affiliates', true, 'ap_wp_nonce' );
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

            if(!current_user_can('affiliatepress_payout')){
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

            $affiliatepress_perpage     = isset($_POST['perpage']) ? intval($_POST['perpage']) : 10; // phpcs:ignore 
            $affiliatepress_currentpage = isset($_POST['currentpage']) ? intval($_POST['currentpage']) : 1; // phpcs:ignore 
            $affiliatepress_offset      = ( ! empty($affiliatepress_currentpage) && $affiliatepress_currentpage > 1 ) ? ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage ) : 0;
            $affiliatepress_order       = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : ''; // phpcs:ignore 
            $affiliatepress_order_by    = isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : ''; // phpcs:ignore 
            
            $affiliatepress_where_clause = " WHERE 1 = 1 ";                        
            $affiliatepress_search_query = '';            
            if(isset($_REQUEST['search_data']) && !empty($_REQUEST['search_data'])){// phpcs:ignore

                if(!empty($_REQUEST['search_data']['ap_payout_type'])){// phpcs:ignore
                    if($_REQUEST['search_data']['ap_payout_type'] == 'auto'){// phpcs:ignore
                        $affiliatepress_where_clause.= $wpdb->prepare( " AND ap_payout_created_by = %d", 0);
                    }else{
                        $affiliatepress_where_clause.= $wpdb->prepare( " AND ap_payout_created_by <> %d", 0);
                    }                                        
                }                
                if(isset($_REQUEST['search_data']['ap_payout_date']) && !empty($_REQUEST['search_data']['ap_payout_date']) && $_REQUEST['search_data']['ap_payout_date'] != '' ){// phpcs:ignore
                    
                    $affiliatepress_start_date = (isset($_REQUEST['search_data']['ap_payout_date'][0]))?sanitize_text_field($_REQUEST['search_data']['ap_payout_date'][0]):'';// phpcs:ignore
                    $affiliatepress_end_date   = (isset($_REQUEST['search_data']['ap_payout_date'][1]))?sanitize_text_field($_REQUEST['search_data']['ap_payout_date'][1]):'';// phpcs:ignore
                    if(!empty($affiliatepress_start_date) && !empty($affiliatepress_end_date)){                        
                        $affiliatepress_start_date = date('Y-m-d',strtotime($affiliatepress_start_date));// phpcs:ignore
                        $affiliatepress_end_date = date('Y-m-d',strtotime($affiliatepress_end_date));// phpcs:ignore
                        $affiliatepress_where_clause.= $wpdb->prepare( " AND (DATE(ap_payout_upto_date) >= %s AND DATE(ap_payout_upto_date) <= %s) ", $affiliatepress_start_date, $affiliatepress_end_date);
                    }

                }                              
            } 
             

           
            $affiliatepress_tbl_ap_payouts_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_payouts); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_payouts contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_get_total_payouts = intval($wpdb->get_var("SELECT count(ap_payout_id) FROM {$affiliatepress_tbl_ap_payouts_temp} {$affiliatepress_where_clause}")); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_creative is a table name. false alarm

            $affiliatepress_pagination_count = ceil(intval($affiliatepress_get_total_payouts) / $affiliatepress_perpage);
            
            if($affiliatepress_currentpage > $affiliatepress_pagination_count && $affiliatepress_pagination_count > 0){
                $affiliatepress_currentpage = $affiliatepress_pagination_count;
                $affiliatepress_offset = ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage );
            }
            
            if(empty($affiliatepress_order)){
                $affiliatepress_order = 'DESC';
            }
            if(empty($affiliatepress_order_by)){
                $affiliatepress_order_by = 'ap_payout_id';
            }     

            if($affiliatepress_order_by == "date_fromate"){
                $affiliatepress_order_by = 'ap_payout_created_date';
            }

            if($affiliatepress_order_by == "payout_amount"){
                $affiliatepress_order_by = 'ap_payout_amount';
            }

            $affiliatepress_payouts_record   = $wpdb->get_results("SELECT * FROM {$affiliatepress_tbl_ap_payouts_temp} {$affiliatepress_where_clause}  order by {$affiliatepress_order_by} {$affiliatepress_order} LIMIT {$affiliatepress_offset} , {$affiliatepress_perpage}", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm
            $affiliatepress_payouts = array();
            if (! empty($affiliatepress_payouts_record) ) {
                $affiliatepress_counter = 1;
                foreach ( $affiliatepress_payouts_record as $affiliatepress_key=>$affiliatepress_single_payout ) {

                    $affiliatepress_payout = $affiliatepress_single_payout;
                    $affiliatepress_payout['ap_payout_id']    = intval($affiliatepress_single_payout['ap_payout_id']);
                    $affiliatepress_payout['ap_payout_upto_date_formated'] = $AffiliatePress->affiliatepress_formated_date_display(esc_html($affiliatepress_single_payout['ap_payout_upto_date']));
                    $affiliatepress_payout['ap_payout_amount'] = floatval($affiliatepress_single_payout['ap_payout_amount']);       
                    $affiliatepress_payout['ap_formated_payout_amount'] = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_payout['ap_payout_amount']);
                    $affiliatepress_payout['unpaid_affiliate_count'] = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'count(ap_payment_id)', 'WHERE ap_payout_id  = %d AND ap_payment_status <> %d', array( $affiliatepress_single_payout['ap_payout_id'],4), '', '', '', true, false,ARRAY_A));
                    
                    $affiliatepress_payout['paid_affiliate_count'] = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payments, 'count(ap_payment_id)', 'WHERE ap_payout_id  = %d AND ap_payment_status = %d', array($affiliatepress_single_payout['ap_payout_id'],4), '', '', '', true, false,ARRAY_A));
                    $affiliatepress_payout['ap_payment_method']           = ucfirst(esc_html($affiliatepress_single_payout['ap_payment_method']));                    
                    $affiliatepress_payout_total_affiliate = intval($affiliatepress_single_payout['ap_payout_total_affiliate']);
                    if($affiliatepress_payout_total_affiliate > 0){
                        $affiliatepress_paid_affiliate_count = intval($affiliatepress_payout['paid_affiliate_count']);
                        $affiliatepress_payout['complete_percentage'] = round((($affiliatepress_paid_affiliate_count/$affiliatepress_payout_total_affiliate) * 100),2);
                    }else{
                        $affiliatepress_payout['complete_percentage'] = 100;
                    }
                    $affiliatepress_payout['payout_type'] = ($affiliatepress_single_payout['ap_payout_created_by'] == 0)?esc_html__( 'Automatic', 'affiliatepress-affiliate-marketing'):esc_html__( 'Manual', 'affiliatepress-affiliate-marketing');

                    $affiliatepress_payouts[]                    = $affiliatepress_payout;

                }
            }
            $affiliatepress_last_payout_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_payouts, 'ap_payout_id', '', '', '', ' order by ap_payout_id DESC ', '', true, false,ARRAY_A);

            $response['variant']        = 'success';
            $response['title']          = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']            = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');            
            $response['items']          = $affiliatepress_payouts;
            $response['last_payout_id'] = $affiliatepress_last_payout_id;
            $response['total']          = $affiliatepress_get_total_payouts;
            $response['pagination_count']          = $affiliatepress_pagination_count;

            wp_send_json($response);
            exit;            
        }


        /**
         * Function For Remove Affiliate User Role & Relation
         *
         * @param  mixed $affiliatepress_user_id
         * @return void
        */
        function affiliatepress_remove_affiliate_user_role($affiliatepress_user_id){            
            $affiliatepress_user = new WP_User($affiliatepress_user_id);
            if($affiliatepress_user->ID){
              delete_user_meta($affiliatepress_user_id,'affiliatepress_affiliate_user');
              $affiliatepress_user->remove_role('affiliatepress-affiliate-user');
          }
        }        

        /**
         * Function For Add Affiliate User Role
         *
         * @param  mixed $affiliatepress_user_id
         * @return void
        */
        function affiliatepress_add_affiliate_user_role($affiliatepress_user_id){            
            $affiliatepress_user = new WP_User($affiliatepress_user_id);
            if($affiliatepress_user->ID){
              update_user_meta($affiliatepress_user_id,'affiliatepress_affiliate_user','yes');
              $affiliatepress_user->add_role('affiliatepress-affiliate-user');
          }
        }

        
        /**
         * Function for add affiliate status 
         *
         * @return void
         */
        function affiliatepress_all_payout_status(){
            $affiliatepress_all_payout_status = array(
                array(
                    'label'=>'Approved',
                    'value'=>'1',
                ),
                array(
                    'label'=>'Pending',
                    'value'=>'2',
                ),
                array(
                    'label'=>'Rejected',
                    'value'=>'3',
                )                                
            );
            return $affiliatepress_all_payout_status;
        }

        
        /**
         * Function for dynamic const add in vue
         *
         * @return void
         */
        function affiliatepress_payout_dynamic_constant_define_func($affiliatepress_payout_dynamic_constant_define){

            $affiliatepress_payout_dynamic_constant_define.='
                const open_modal = ref(false);
                const payout_preview_table = ref(false);
                const open_edit_modal = ref(false);
                const open_payment_message_modal = ref(false);

                affiliatepress_return_data["open_edit_modal"] = open_edit_modal;
                affiliatepress_return_data["open_modal"] = open_modal;
                affiliatepress_return_data["payout_preview_table"] = payout_preview_table;
                affiliatepress_return_data["open_payment_message_modal"] = open_payment_message_modal;            
            ';

            return $affiliatepress_payout_dynamic_constant_define;
        ?>

        <?php 
        }

        /**
         * Function for affiliate vue data
         *
         * @return void
        */
        function affiliatepress_payout_dynamic_data_fields_func($affiliatepress_payout_vue_data_fields){            
            
            global $AffiliatePress,$affiliatepress_payout_vue_data_fields,$wpdb,$affiliatepress_global_options;

            $affiliatepress_global_data = $affiliatepress_global_options->affiliatepress_global_options();

            $affiliatepress_payout_types = (isset($affiliatepress_global_data['payout_types']))?$affiliatepress_global_data['payout_types']:array();

            $affiliatepress_all_payment_method = (isset($affiliatepress_global_data['payment_method']))?$affiliatepress_global_data['payment_method']:array();

            $affiliatepress_payout_vue_data_fields['all_payment_method'] = $affiliatepress_all_payment_method;
            $affiliatepress_payout_vue_data_fields['payout_types'] = $affiliatepress_payout_types;

            $affiliatepress_minimum_payment_amount = $AffiliatePress->affiliatepress_get_settings('minimum_payment_amount', 'commissions_settings');
            $affiliatepress_minimum_payment_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_minimum_payment_amount);   

            $affiliatepress_payout_vue_data_fields['minimum_payment_amount'] = $affiliatepress_minimum_payment_amount;
            $affiliatepress_all_payout_status = $this->affiliatepress_all_payout_status();
            $affiliatepress_payout_vue_data_fields['all_status'] = $affiliatepress_all_payout_status;
            $affiliatepress_payout_vue_data_fields['affiliates']['affiliate_user_name'] = '';

            $affiliatepress_payout_vue_data_fields['payment_status_change_loader'] = '0';

            $affiliatepress_payout_vue_data_fields['edit_payout_data'] = '';
            $affiliatepress_payout_vue_data_fields['edit_payout_payments'] = [];

            $affiliatepress_payout_vue_data_fields['payout_message']['payout_payment_message_id'] = '';
            $affiliatepress_payout_vue_data_fields['payout_message']['payout_payment_message'] = '';
            $affiliatepress_payout_vue_data_fields['payout_message']['payment_index'] = '';

            $affiliatepress_payout_vue_data_fields['payout_msg_rules'] = array(
                'payout_payment_message'  => array(
                    array(
                        'required' => true,
                        'message'  => esc_html__('Please add payment note.', 'affiliatepress-affiliate-marketing'),
                        'trigger'  => 'blur',
                    ),
                )  
            );

            $affiliatepress_payout_vue_data_fields['payout_payment_note_loader'] = '0';
            $affiliatepress_payout_vue_data_fields['payout_payment_note_get_loader'] = '0';
            $affiliatepress_payout_vue_data_fields['last_payout_id'] = '';            
            
            $affiliatepress_payout_vue_data_fields['payout']['payment_method'] = 'manual';

            $affiliatepress_payout_vue_data_fields['payout_org'] = $affiliatepress_payout_vue_data_fields['payout'];

            $affiliatepress_payout_vue_data_fields = apply_filters('affiliatepress_backend_modify_payout_data_fields', $affiliatepress_payout_vue_data_fields);

            return wp_json_encode($affiliatepress_payout_vue_data_fields);

        }

        function affiliatepress_payout_dynamic_vue_methods_func($affiliatepress_payout_dynamic_vue_methods){
            global $affiliatepress_notification_duration;

            $affiliatepress_edit_payout_more_vue_data = "";
            $affiliatepress_edit_payout_more_vue_data = apply_filters('affiliatepress_edit_payout_more_vue_data', $affiliatepress_edit_payout_more_vue_data);     


            $affiliatepress_payout_dynamic_vue_methods.='
                resetModal(form_ref){
                    const vm = this;
                    if(form_ref && vm.open_edit_modal == false && this.$refs[form_ref]){
                        this.$refs[form_ref].resetFields();
                    }                
                    vm.payout = JSON.parse(JSON.stringify(vm.payout_org));
                    vm.preview_affiliates = [];
                    vm.auto_approved_payouts = false;
                    vm.preview_total_affiliate = 0;
                    vm.preview_total_amount = "";
                    vm.selected_paymnet_method = "";
                    vm.complete_percentage = 0;
                    vm.payout_generate_loading = "0";
                    vm.payout_preview_loading = "0";
                    vm.payout_generate_loading_btn = "0";
                    var div = document.getElementById("ap-drawer-body");
                    if(div){
                        div.scrollTop = 0;
                    }  
                    vm.open_edit_modal = false;               
                },
                paymentPayoutmessage_add(ap_payment_id,payment_index,row_data){
                    const vm = this;
                    vm.payout_message.payment_index = payment_index;
                    vm.payout_message.payout_payment_message_id = ap_payment_id;
                    vm.payout_message.payout_payment_message = row_data.ap_payment_note;                
                    vm.open_payment_message_modal = true;                                              
                },            
            savePaymentMessage(form_ref , payout_id){
                const vm = this;                
                this.$refs[form_ref].validate((valid) => { 
                    if (valid) {
                        var postdata = vm.payout_message;
                        postdata.action = "affiliatepress_add_payment_note";  
                        postdata.payout_id = payout_id;                                               
                        vm.payout_payment_note_loader = "1";                        
                        postdata._wpnonce = "'.esc_html(wp_create_nonce('ap_wp_nonce')).'";                                                
                        axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                        .then(function(response){                                                     
                            vm.payout_payment_note_loader = "0";                                                       
                            if (response.data.variant == "success") { 
                                vm.edit_payout_payments[vm.payout_message.payment_index]["ap_payment_note"] = vm.payout_message.payout_payment_message;  
                                vm.open_payment_message_modal = false;                                                                                                
                                vm.$notify({
                                    title: response.data.title,
                                    message: response.data.msg,
                                    type: response.data.variant,
                                    customClass: response.data.variant+"_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });                              
                            }
                            if(response.data.variant == "error"){
                                vm.$notify({
                                    title: response.data.title,
                                    message: response.data.msg,
                                    type: response.data.variant,
                                    customClass: response.data.variant+"_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });
                            }
                        }).catch(function(error){   
                            vm.payout_payment_note_loader = "0";                                                      
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
            closePaymentModal(form_ref){
                const vm = this;
                vm.open_payment_message_modal = false;
                if(form_ref && this.$refs[form_ref]){
                    this.$refs[form_ref].resetFields();
                }                                
            },
            closeModal(form_ref){
                const vm = this;
                var div = document.getElementById("ap-drawer-body");
                if(div){
                    div.scrollTop = 0;
                }                
                vm.open_modal = false;
                if(form_ref && vm.open_edit_modal == false && this.$refs[form_ref]){
                    this.$refs[form_ref].resetFields();
                }                
                vm.payout = JSON.parse(JSON.stringify(vm.payout_org));
                vm.preview_total_affiliate = 0;
                vm.preview_total_amount = "";
                vm.selected_paymnet_method = "";
                vm.complete_percentage = 0;
                vm.preview_affiliates = [];
                vm.auto_approved_payouts = false;
                vm.payout_generate_loading = "0";
                vm.payout_preview_loading = "0";
                vm.payout_generate_loading_btn = "0";
                vm.open_edit_modal = false; 
            },        
            handleSelectionPreview(val){
                const vm = this;
                const items_obj = val;
                this.multipleSelection = [];
                var temp_data = [];
                Object.values(items_obj).forEach(val => {
                    temp_data.push(val.ap_affiliates_id);                    
                });                
                vm.payout.allow_affiliates = temp_data;                             
            },              
            check_payout_process(){
                const vm = this;
                var postdata = vm.payout;
                postdata.action = "affiliatepress_check_payout_process";                                                
                vm.payout_generate_loading_btn = "1";        
                vm.payout_generate_loading = "1";                 
                postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";                                                
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                .then(function(response){                                                                           
                    if(response.data.variant == "success"){                                                                   
                        vm.complete_percentage = parseFloat(response.data.complete_percentage);
                        if(vm.complete_percentage < 100){
                            vm.check_payout_process();
                        }else{
                            vm.loadPayouts();
                        }
                    }
                    if(response.data.variant == "error"){                        

                    }
                }).catch(function(error){
                    
                });
            },      
            generate_payout_record(){
                const vm = this;
                var postdata = vm.payout;
                postdata.action = "affiliatepress_generate_payout_process";                                                
                vm.payout_generate_loading_btn = "1";        
                vm.payout_generate_loading = "1";                 
                postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";                                                
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                .then(function(response){                                                                           
                    if(response.data.variant == "success"){                                                                   
                        vm.loadPayouts();
                    }
                    if(response.data.variant == "error"){
                        
                    }
                }).catch(function(error){
                    
                });
            },   
            generate_payout_request(form_ref){
                const vm = this;
                this.$refs[form_ref].validate((valid) => { 
                    if (valid) {
                        var postdata = vm.payout;
                        postdata.preview_total_affiliate = vm.preview_total_affiliate;
                        postdata.auto_approved_payouts = vm.auto_approved_payouts;
                        postdata.action = "affiliatepress_generate_payout_request";                                                
                        vm.payout_generate_loading_btn = "1";        
                        vm.payout_generate_loading = "1";                 
                        postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";                                                
                        axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                        .then(function(response){                                                     
                            vm.payout_generate_loading_btn = "0";                                                       
                            if (response.data.variant == "success") {                                                                   
                                vm.generate_payout_record();
                                vm.check_payout_process();      
                                vm.payout_generate_id = response.data.payout_id;                          
                            }
                            if(response.data.variant == "error"){
                                vm.payout_generate_loading_btn = "0";        
                                vm.payout_generate_loading = "0";                                 
                                vm.$notify({
                                    title: response.data.title,
                                    message: response.data.msg,
                                    type: response.data.variant,
                                    customClass: response.data.variant+"_notification",
                                    duration:'.intval($affiliatepress_notification_duration).', 
                                });
                            }
                        }).catch(function(error){
                            vm.payout_generate_loading_btn = "0";        
                            vm.payout_generate_loading = "0";                              
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
            async loadPayouts(show_loader = true){
                const vm = this;
                if(show_loader){
                    vm.is_display_loader = "1";
                }                
                vm.enabled = true;
                vm.is_apply_disabled = true; 
                affiliatespress_search_data = vm.payout_search;
                var postData = { action:"affiliatepress_get_payouts", perpage:this.perPage, order_by:this.order_by, order:this.order, currentpage:this.currentPage, search_data: affiliatespress_search_data,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function(response){    
                    vm.ap_first_page_loaded = "0"; 
                    vm.is_apply_disabled = false;                                                      
                    if(response.data.variant == "success"){
                        vm.items = response.data.items;
                        vm.last_payout_id = response.data.last_payout_id;
                        vm.totalItems = response.data.total;   
                        var defaultPerPage = '.$this->affiliatepress_per_page_record.';
                        if(vm.perPage > defaultPerPage && response.data.pagination_count == 1){
                            response.data.pagination_count = 2;
                        }
                        vm.pagination_count = response.data.pagination_count;   
                    }else{
                        vm.$notify({
                            title: response.data.title,
                            message: response.data.msg,
                            type: response.data.variant,
                            customClass: response.data.variant+"_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });                        
                    }                 
                    vm.is_display_loader = "0";
                }.bind(this) )
                .catch( function (error) {
                    vm.ap_first_page_loaded = "0";
                    vm.is_display_loader = "0";
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
            previewPayout(form_ref){
                const vm = this;
                this.$refs[form_ref].validate((valid) => { 
                    if (valid) {
                        var postdata = vm.payout;
                        postdata.action = "affiliatepress_generate_payout_preview";                                                
                        vm.payout_preview_loading = "1";     
                        var selected_paymnet_method = vm.payout.payment_method;                   
                        postdata._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";                                                
                        axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                        .then(function(response){                                                     
                            vm.payout_preview_loading = "0";                                                       
                            if (response.data.variant == "success") {  
                                vm.selected_paymnet_method = selected_paymnet_method;                         
                                vm.preview_total_amount = response.data.payout_data.total_payment_amount_display;
                                vm.preview_total_affiliate = response.data.payout_data.total_affiliate;
                                vm.preview_affiliates = response.data.payout_data.payout_affiliates; 
                                if (vm.preview_affiliates.length !== 0) {
                                    vm.$nextTick(() => {
                                        var selected_paymnet_affiliates = vm.preview_affiliates.filter(row => row.payment_method == selected_paymnet_method);
                                        vm.$refs["payout_preview_table"].clearSelection();
                                        selected_paymnet_affiliates.forEach(row => {
                                            vm.$refs["payout_preview_table"].toggleRowSelection(row, true);
                                        });
                                        vm.payout.allow_affiliates = selected_paymnet_affiliates.map(row => row.ap_affiliates_id);
                                    });
                                }
                            }
                            if(response.data.variant == "error"){
                                vm.$notify({
                                    title: response.data.title,
                                    message: response.data.msg,
                                    type: response.data.variant,
                                    customClass: response.data.variant+"_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });
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
            selected_paymnet_affiliate_row(row, index) {
                const vm = this;
                return row.payment_method === vm.selected_paymnet_method;
            },
            deletePayout(ap_payout_id,index){
                const vm = this;
                var postData = { action:"affiliatepress_delete_payout", ap_payout_id: ap_payout_id, _wpnonce:"'.esc_html(wp_create_nonce("ap_wp_nonce")).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    if(response.data.variant == "success"){
                        vm.items.splice(index, 1);                                                                        
                        vm.loadPayouts();                                  
                    }
                    vm.$notify({
                        title: response.data.title,
                        message: response.data.msg,
                        type: response.data.variant,
                        customClass: response.data.variant+"_notification",
                        duration:'.intval($affiliatepress_notification_duration).',
                    });
                }.bind(this) )
                .catch( function (error) {
                    vm.$notify({
                        title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                        message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                        type: "error",
                        customClass: "error_notification",
                        duration:'.intval($affiliatepress_notification_duration).',                    
                    });
                });
            },
            editPayoutCall(ap_payout_id,index,row){
                const vm = this;
                vm.edit_payout_payments = [];
                vm.edit_payout_data = [];
                vm.editPayout(ap_payout_id,index,row);
            },

            editPayout(ap_payout_id,index,row){

            const vm = this;                                    
            vm.open_edit_modal = true;
            vm.open_modal = true;
        
        
            var affiliate_edit_data = { action: "affiliatepress_edit_payout",edit_id: ap_payout_id,_wpnonce:"'.esc_html(wp_create_nonce("ap_wp_nonce")).'" }
            axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(affiliate_edit_data)).then(function(response){
                
                vm.payment_status_change_loader = "0";
                if(response.data.payout_data.edit_payout_data != undefined){
                    vm.edit_payout_data = response.data.payout_data.edit_payout_data;
                } 
                if(response.data.payout_data.edit_payout_payments != undefined){
                    vm.edit_payout_payments = response.data.payout_data.edit_payout_payments;
                }                       
                '.$affiliatepress_edit_payout_more_vue_data.'
        
            }.bind(this) )
            .catch(function(error){
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
        paymentPayoutStatusChange(ap_payment_id,new_status,index){
            const vm = this;   
            vm.payment_status_change_loader = "1";   
            vm.edit_payout_payments[index]["payment_status_change_loader"] = "1";
            var affiliate_edit_data = { action: "affiliatepress_payment_status_change",ap_payment_id: ap_payment_id,new_status:new_status,_wpnonce:"'.esc_html(wp_create_nonce("ap_wp_nonce")).'" }
            axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(affiliate_edit_data)).then(function(response){
                if (response.data.variant == "success") {   
                    vm.editPayout(response.data.ap_payout_id,"","");
                    vm.edit_payout_payments[index]["payment_status_change_loader"] = "0";
                    vm.loadPayouts(false);
                }                    
            }.bind(this) )
            .catch(function(error){
                console.log(error);
                vm.payment_status_change_loader = "0";
                vm.edit_payout_payments[index]["payment_status_change_loader"] = "0";
                vm.$notify({
                    title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                    message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                    type: "error",
                    customClass: "error_notification",
                    duration:'.intval($affiliatepress_notification_duration).', 
                });
            });                   
        },
        resetEditModal(){
            var vm = this;
            vm.open_edit_modal = false;
        },                               
        handleSizeChange(val) {
            this.perPage = val;
            this.loadPayouts();
        },
        handleCurrentChange(val) {
            this.currentPage = val;
            this.loadPayouts();
        }, 
        affiliatepress_change_status(update_id, index, new_status, old_status){
            const vm = this;
            vm.items[index].change_status_loader = 1;
            
                var postData = { action:"affiliatepress_change_affiliate_status", update_id: update_id, new_status: new_status, old_status: old_status, _wpnonce:"'.esc_html(wp_create_nonce("ap_wp_nonce")).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    if(response.data == "0" || response.data == 0){
                        vm.loadPayouts();
                        return false;
                    }else{
                        vm.$notify({
                            title: "'.esc_html__("Success", "affiliatepress-affiliate-marketing").'",
                            message: "'.esc_html__("Affiliate status changed successfully", "affiliatepress-affiliate-marketing").'",
                            type: "success",
                            customClass: "success_notification",
                            duration:'.intval($affiliatepress_notification_duration).',  
                        });
                        vm.loadPayouts();
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
                });
            
        },
        resetFilter(){
            const vm = this;
            const formValues = Object.values(this.payout_search);
            const hasValue = formValues.some(value => {
                if (typeof value === "string") {
                    return value.trim() !== "";
                }
                if (Array.isArray(value)) {
                    return value.length > 0;
                }
                return false;
            });                
            vm.payout_search.ap_payout_date = "";
            vm.payout_search.ap_payout_type = "";
            if(hasValue){
                vm.loadPayouts();
            }                
            vm.is_multiple_checked = false;
            vm.multipleSelection = [];
        },
        affiliatepress_get_existing_user_details(affiliatepress_selected_user_id){
            const vm = this
            if(affiliatepress_selected_user_id != "add_new") {
                var postData = { action:"affiliatepress_get_existing_users_details", existing_user_id: affiliatepress_selected_user_id, _wpnonce:"'.esc_html(wp_create_nonce("ap_wp_nonce")).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    if(response.data.user_details != "" || response.data.user_details != undefined){
                        vm.customer.username  = response.data.user_details.username
                        vm.customer.firstname = response.data.user_details.user_firstname
                        vm.customer.lastname = response.data.user_details.user_lastname
                        vm.customer.email = response.data.user_details.user_email
                    }
                }.bind(vm) )
                .catch( function (error) {
                    console.log(error);
                });
            }
        },                        
        handleSortChange({ column, prop, order }){                
            var vm = this;
            if(prop == "ap_payout_upto_date_formated"){
                vm.order_by = "date_fromate"; 
            }
            if(prop == "ap_formated_payout_amount"){
                vm.order_by = "payout_amount"; 
            }
            if(prop == "ap_payout_id"){
                vm.order_by = "ap_payout_id"; 
            }
            if(vm.order_by){
                if(order == "descending"){
                    vm.order = "DESC";
                }else if(order == "ascending"){
                    vm.order = "ASC";
                }else{
                    vm.order = "";
                    vm.order_by = "";
                }
            }    
            this.loadPayouts(true);     
        }, 
        handleSelectionChange(val) {
            const items_obj = val;
            this.multipleSelection = [];
            var temp_data = [];
            Object.values(items_obj).forEach(val => {
                temp_data.push({"item_id" : val.ap_payout_id});
                this.bulk_action = "bulk_action";
            });
            this.multipleSelection = temp_data;
            if(temp_data.length > 0){
                this.multipleSelectionVal = JSON.stringify(temp_data);
            }else{
                this.multipleSelectionVal = "";
            }
        },
        closeBulkAction(){
            this.$refs.multipleTable.clearSelection();
            this.bulk_action = "bulk_action";
        }, 
        bulk_action_perform(){
            const vm = this;
            if(this.bulk_action == "bulk_action"){
                vm.$notify({
                    title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                    message: "'.esc_html__("Please select any action.", "affiliatepress-affiliate-marketing").'",
                    type: "error",
                    customClass: "error_notification",
                    duration:'.intval($affiliatepress_notification_duration).',
                });
            }else{
                if(this.multipleSelection.length > 0 && this.bulk_action == "delete"){
                    var bulk_action_postdata = {
                        action:"affiliatepress_affiliate_bulk_action",
                        ids: vm.multipleSelectionVal,
                        bulk_action: this.bulk_action,
                        _wpnonce:"'.esc_html(wp_create_nonce("ap_wp_nonce")).'",
                    };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( bulk_action_postdata )).then(function(response){
                        vm.$notify({
                            title: response.data.title,
                            message: response.data.msg,
                            type: response.data.variant,
                            customClass: response.data.variant+"_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });
                        vm.loadPayouts();                     
                        vm.is_multiple_checked = false;
                        vm.multipleSelection = []; 
                        vm.multipleSelectionVal = "";                                                   
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


                }
            }
        },   
        affiliatepress_full_row_clickable(row){
            const vm = this
            if (event.target.closest(".ap-table-actions")) {
                return;
            }
            vm.$refs.multipleTable.toggleRowExpansion(row);
        },
        affiliatepress_payout_full_row_clickable(row){
            const vm = this
            vm.$refs.payout_payment_table.toggleRowExpansion(row);
        },   
        export_payout(payout_id){
            const vm = this;
            var nonce = "'.esc_html(wp_create_nonce('ap_wp_nonce')).'";
            vm.payout_export_loading = "1";
            var downloadUrl = affiliatepress_ajax_obj.ajax_url + "?action=affiliatepress_generate_export_payout&_wpnonce=" + nonce + "&export_payout_id=" +payout_id;
            window.location.href = downloadUrl;
            vm.payout_export_loading = "0";
        },    
        changeCurrentPage(perPage) {
            const vm = this;
            var total_item = vm.totalItems;
            var recored_perpage = perPage;
            var select_page =  vm.currentPage;                
            var current_page = Math.ceil(total_item/recored_perpage);
            if(total_item <= recored_perpage ) {
                current_page = 1;
            } else if(select_page >= current_page ) {
                
            } else {
                current_page = select_page;
            }
            return current_page;
        },
        changePaginationSize(selectedPage) {
            const vm = this;
            selectedPage = parseInt( selectedPage );
            vm.perPage = selectedPage;
            var current_page = vm.changeCurrentPage(selectedPage);                                        
            vm.currentPage = current_page;    
            vm.loadPayouts();
        },    
        disabled_after_grace_period_date(date) {
            const vm = this;
            const today_date = new Date()
            const graceLimit = new Date()
            graceLimit.setDate(today_date.getDate() - vm.refund_grace_period)
            return date > graceLimit
        },
        ';
        return $affiliatepress_payout_dynamic_vue_methods;
        }
        
        /**
         * Function for dynamic View load
         *
         * @return void
        */
        function affiliatepress_payout_dynamic_view_load_func(){
            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/payout/manage_payout.php';
            $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_payout_view_file_path', $affiliatepress_load_file_name);
            include $affiliatepress_load_file_name;
        }

        
        /**
         * Function for affiliates default Vue Data
         *
         * @return void
        */
        function affiliatepress_payout_vue_data_fields(){

            global $affiliatepress_payout_vue_data_fields,$affiliatepress_global_options,$AffiliatePress;            
            $affiliatepress_pagination          = wp_json_encode(array( 10, 20, 50, 100, 200, 300, 400, 500 ));
            $affiliatepress_pagination_arr      = json_decode($affiliatepress_pagination, true);
            $affiliatepress_pagination_selected = $this->affiliatepress_per_page_record;

            $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_pagination_value = (isset($affiliatepress_global_options_data['pagination_val']))?$affiliatepress_global_options_data['pagination_val']:array();

            $affiliatepress_payout_vue_data_fields = array(
                'bulk_action'                => 'bulk_action',                
                'preview_total_amount'       => '',
                'selected_paymnet_method'    => 'manual',
                'preview_total_affiliate'    => 0,
                'complete_percentage'        => 0,
                'preview_affiliates'         => array(),
                'auto_approved_payouts'      => false,
                'bulk_options'               => array(
                    array(
                        'value' => 'bulk_action',
                        'label' => esc_html__('Bulk Action', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'delete',
                        'label' => esc_html__('Delete', 'affiliatepress-affiliate-marketing'),
                    ),
                ),
                'loading'                    => false,
                'payout_search'          => array(
                    "ap_payout_date"     => array(),
                    "ap_payout_type"     => '',
                ),
                'order'                      => '',
                'order_by'                   => '',
                'items'                      => array(),
                'multipleSelection'          => array(),
                'multipleSelectionVal'       => '',
                'perPage'                    => $affiliatepress_pagination_selected,
                'totalItems'                 => 0,
                'pagination_count'           => 1,
                'currentPage'                => 1,
                'savebtnloading'             => false,
                'modal_loader'               => 1,
                'is_display_loader'          => '0',
                'is_apply_disabled'          => false,
                'is_disabled'                => false,
                'is_display_save_loader'     => '0',
                'is_multiple_checked'        => false,
                'wpUsersList'                => array(),
                'affiliatepress_user_loading'=> false,
                'payout_preview_loading'     => '0',
                'payout_generate_loading'    => '0',
                'payout_generate_loading_btn'=> '0',
                'payout_generate_id'         =>  0,
                'payout_export_loading'      => '0',
                'payout'                => array(
                    'payout_upto'           => "",  
                    'allow_affiliates'  => array(),                  
                ),                
                'rules'                      => array(
                    'payout_upto'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add payout upto date.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    )                                       
                ),                
                'pagination_length_val'      => '10',
                'pagination_val'             => $affiliatepress_pagination_value,
                'refund_grace_period'        => intval($AffiliatePress->affiliatepress_get_settings('refund_grace_period', 'commissions_settings')),
            );
        }



    }
}
global $affiliatepress_payout;
$affiliatepress_payout = new affiliatepress_payout();
