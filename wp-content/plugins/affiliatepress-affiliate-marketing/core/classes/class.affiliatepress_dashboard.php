<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_dashboard') ) {
    class affiliatepress_dashboard Extends AffiliatePress_Core{
        
        var $affiliatepress_per_page_record;

        function __construct(){
            
            $this->affiliatepress_per_page_record = 10;

            /**Function for dashboard vue data */
            add_action( 'admin_init', array( $this, 'affiliatepress_dashboard_vue_data_fields') );

            /* Dynamic Constant */
            add_filter('affiliatepress_dashboard_dynamic_constant_define',array($this,'affiliatepress_dashboard_dynamic_constant_define_func'),10,1);
            
            /* Dynamic Vue Fields */
            add_filter('affiliatepress_dashboard_dynamic_data_fields',array($this,'affiliatepress_dashboard_dynamic_data_fields_func'),10,1);

            /* Vue Load */
            add_action('affiliatepress_dashboard_dynamic_view_load', array( $this, 'affiliatepress_dashboard_dynamic_view_load_func' ), 10);

            /* Vue Method */
            add_filter('affiliatepress_dashboard_dynamic_vue_methods',array($this,'affiliatepress_dashboard_dynamic_vue_methods_func'),10,1);

            /* Dynamic On Load Method */
            add_filter('affiliatepress_dashboard_dynamic_on_load_methods', array( $this, 'affiliatepress_dashboard_dynamic_on_load_methods_func' ), 10);

            /* Get Dashboard Data */
            add_action('wp_ajax_affiliatepress_backend_dashboard_data', array( $this, 'affiliatepress_backend_dashboard_data_func' ));

            /* Vue Component Method */
            add_filter('affiliatepress_dashboard_dynamic_components',array($this,'affiliatepress_dashboard_dynamic_components_func'),10,1);            

        }
        
        /**
         * Function for dashboard module vue component data
         *
         * @param  string $affiliatepress_dashboard_dynamic_components
         * @return string
         */
        function affiliatepress_dashboard_dynamic_components_func($affiliatepress_dashboard_dynamic_components){
            return $affiliatepress_dashboard_dynamic_components;
        }              
        
      
        /**
         * dashboard module on load methods
         *
         * @param  string $affiliatepress_dashboard_dynamic_on_load_methods
         * @return void
        */
        function affiliatepress_dashboard_dynamic_on_load_methods_func($affiliatepress_dashboard_dynamic_on_load_methods){

            global $AffiliatePress;

            $affiliatepress_model_show = "";
            $affiliatepress_dashboard_dynamic_on_load_methods.='
                var vm = this;                
            ';
            if(!empty($_GET["upgrade_action"]) && ($_GET["upgrade_action"] == "upgrade_to_pro")){ //phpcs:ignore
                $affiliatepress_current_date_for_bf_popup = current_time('timestamp',true); //GMT/ UTC+00 timeszone
                $affiliatepress_sale_popup_details = $AffiliatePress->affiliatepress_get_sales_data();
                $current_year = gmdate('Y', current_time('timestamp', true ) );
                if( !empty( $affiliatepress_sale_popup_details[ $current_year ] ) ){
                    $sale_details = $affiliatepress_sale_popup_details[ $current_year ];
                    
                    $affiliatepress_bf_popup_start_time = $sale_details['start_time'];
                    $affiliatepress_bf_popup_end_time = $sale_details['end_time'];

                    if( $affiliatepress_current_date_for_bf_popup >= $affiliatepress_bf_popup_start_time && $affiliatepress_current_date_for_bf_popup <= $affiliatepress_bf_popup_end_time ){
                        $affiliatepress_model_show = "true";
                        $affiliatepress_dashboard_dynamic_on_load_methods.='
                            setTimeout(function(){
                                vm.affiliatepress_premium_modal = false;   
                                vm.affiliatepress_sale_premium_modal = true;                     
                            },2000);    
                        ';
                    }else{
                        $affiliatepress_model_show = "true";
                        $affiliatepress_dashboard_dynamic_on_load_methods.='
                            setTimeout(function(){
                                vm.affiliatepress_premium_modal = true;   
                                vm.affiliatepress_sale_premium_modal = false;                      
                            },2000);    
                        ';
                    }
                }else{

                    $affiliatepress_model_show = "true";
                    $affiliatepress_dashboard_dynamic_on_load_methods.='
                        setTimeout(function(){
                            vm.affiliatepress_premium_modal = true;  
                            vm.affiliatepress_sale_premium_modal = false;                      
                        },2000);    
                    ';
                }
            }
            $affiliatepress_dashboard_dynamic_on_load_methods.='                
                setTimeout(function(){
                    vm.affiliatepress_get_dashboard_detail();
                },1000);        
            ';

            return $affiliatepress_dashboard_dynamic_on_load_methods;

        }
                               

        /**
         * Function for get dashboard data
         *
         * @return json
        */
        function affiliatepress_backend_dashboard_data_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_affiliate_visits,$affiliatepress_tbl_ap_affiliate_commissions,$AffiliatePress, $affiliatepress_tbl_ap_affiliate_report;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_dashboard_data', true, 'ap_wp_nonce' );

            if(!current_user_can('affiliatepress')){
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

            $affiliatepress_dashboard_start_date = (isset($_POST['dashboard_date_range'][0]) && !empty($_POST['dashboard_date_range'][0])) ? sanitize_text_field($_POST['dashboard_date_range'][0]): ''; // phpcs:ignore
            $affiliatepress_dashboard_end_date   = (isset($_POST['dashboard_date_range'][1]) && !empty($_POST['dashboard_date_range'][1])) ? sanitize_text_field($_POST['dashboard_date_range'][1]): ''; // phpcs:ignore 

            $affiliatepress_dashboard_total_commission = '';
            $affiliatepress_dashboard_total_revenue_commission = '';
            $affiliatepress_dashboard_unpaid_commission = '';
            $affiliatepress_dashboard_total_visits_count = '';
            $affiliatepress_dashboard_total_commission_count = '';
            $affiliatepress_dashboard_total_affiliate_count = '';  
            
            $affiliatepress_tbl_ap_affiliate_commissions = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_commissions contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_tbl_ap_affiliate_visits = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_visits); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_visits contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_tbl_ap_affiliates = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            if(!empty($affiliatepress_dashboard_start_date) && !empty($affiliatepress_dashboard_end_date)){


                $affiliatepress_dashboard_total_commission   = 0;
                $affiliatepress_dashboard_total_revenue_commission    = 0;
                $affiliatepress_dashboard_unpaid_commission  = 0;
                $affiliatepress_dashboard_total_commission_count = 0;
                $affiliatepress_dashboard_total_visits_count = 0;

                $affiliatepress_dashboard_report_data = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(ap_affiliate_report_total_commission) as total_commission_count,  sum(ap_affiliate_report_visits) as total_visits_count, SUM(ap_affiliate_report_total_commission_amount) as total_commission_amount, sum(ap_affiliate_report_total_commission_revenue) as total_revenue_amount, sum(ap_affiliate_report_unpaid_commission_amount) as unpaid_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report Inner Join {$affiliatepress_tbl_ap_affiliates} as affiliate ON report.ap_affiliates_id = affiliate.ap_affiliates_id  WHERE affiliate.ap_affiliates_status = %d AND DATE(ap_affiliate_report_date) >= %s AND DATE(ap_affiliate_report_date) <= %s", 1, $affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                if(!empty($affiliatepress_dashboard_report_data)){

                        $affiliatepress_dashboard_total_commission       = floatval($affiliatepress_dashboard_report_data['total_commission_amount']);
                        $affiliatepress_dashboard_total_revenue_commission        = floatval($affiliatepress_dashboard_report_data['total_revenue_amount']);
                        $affiliatepress_dashboard_unpaid_commission      = floatval($affiliatepress_dashboard_report_data['unpaid_commission_amount']);
                        $affiliatepress_dashboard_total_commission_count = intval($affiliatepress_dashboard_report_data['total_commission_count']);
                        $affiliatepress_dashboard_total_visits_count     = intval($affiliatepress_dashboard_report_data['total_visits_count']);

                }
               
                $affiliatepress_dashboard_total_commission = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_total_commission,2));
                $affiliatepress_dashboard_total_revenue_commission = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_total_revenue_commission,2));  
                $affiliatepress_dashboard_unpaid_commission = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_unpaid_commission,2)); 


                /*
                $affiliatepress_dashboard_total_commission = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount)', 'WHERE ap_commission_status IN (1,4) AND DATE(ap_commission_created_date) >= %s AND DATE(ap_commission_created_date) <= %s ', array( $affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date), '', '', '', true, false,ARRAY_A));
                

                $affiliatepress_dashboard_paid_commission = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount)', 'WHERE ap_commission_status IN (4) AND DATE(ap_commission_created_date) >= %s AND DATE(ap_commission_created_date) <= %s ', array( $affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date), '', '', '', true, false,ARRAY_A));
                
                
                $affiliatepress_dashboard_unpaid_commission = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount)', 'WHERE ap_commission_status IN (1) AND DATE(ap_commission_created_date) >= %s AND DATE(ap_commission_created_date) <= %s ', array( $affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date), '', '', '', true, false,ARRAY_A));
                 
                
                $affiliatepress_dashboard_total_commission_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'COUNT(ap_commission_id)', 'WHERE ap_commission_status IN (1,4) AND DATE(ap_commission_created_date) >= %s AND DATE(ap_commission_created_date) <= %s ', array( $affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date), '', '', '', true, false,ARRAY_A));

                $affiliatepress_dashboard_total_visits_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_visits, 'COUNT(ap_visit_id)', 'WHERE  DATE(ap_visit_created_date) >= %s AND DATE(ap_visit_created_date) <= %s ', array( $affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date), '', '', '', true, false,ARRAY_A));

                */



                $affiliatepress_dashboard_total_affiliate_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'COUNT(ap_affiliates_id)', 'WHERE DATE(ap_affiliates_created_at) >= %s AND DATE(ap_affiliates_created_at) <= %s AND ap_affiliates_status = %d', array( $affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date,1), '', '', '', true, false,ARRAY_A));


            }else{



                $affiliatepress_dashboard_total_commission   = 0;
                $affiliatepress_dashboard_total_revenue_commission    = 0;
                $affiliatepress_dashboard_unpaid_commission  = 0;
                $affiliatepress_dashboard_total_commission_count = 0;
                $affiliatepress_dashboard_total_visits_count = 0;

                /*
                $affiliatepress_dashboard_report_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_report, 'SUM(ap_affiliate_report_total_commission) as total_commission_count,  sum(ap_affiliate_report_visits) as total_visits_count, SUM(ap_affiliate_report_total_commission_amount) as total_commission_amount, sum(ap_affiliate_report_paid_commission_amount) as paid_commission_amount, sum(ap_affiliate_report_unpaid_commission_amount) as unpaid_commission_amount', 'WHERE  1 = %d ', array(1), '', '', '', false, true,ARRAY_A);
                */

                $affiliatepress_dashboard_report_data = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(ap_affiliate_report_total_commission) as total_commission_count,  sum(ap_affiliate_report_visits) as total_visits_count, SUM(ap_affiliate_report_total_commission_amount) as total_commission_amount, sum(ap_affiliate_report_total_commission_revenue) as total_revenue_amount, sum(ap_affiliate_report_unpaid_commission_amount) as unpaid_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report Inner Join {$affiliatepress_tbl_ap_affiliates} as affiliate ON report.ap_affiliates_id = affiliate.ap_affiliates_id  WHERE affiliate.ap_affiliates_status = %d ", 1), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                if(!empty($affiliatepress_dashboard_report_data)){

                        $affiliatepress_dashboard_total_commission       = floatval($affiliatepress_dashboard_report_data['total_commission_amount']);
                        $affiliatepress_dashboard_total_revenue_commission        = floatval($affiliatepress_dashboard_report_data['total_revenue_amount']);
                        $affiliatepress_dashboard_unpaid_commission      = floatval($affiliatepress_dashboard_report_data['unpaid_commission_amount']);
                        $affiliatepress_dashboard_total_commission_count = intval($affiliatepress_dashboard_report_data['total_commission_count']);
                        $affiliatepress_dashboard_total_visits_count = intval($affiliatepress_dashboard_report_data['total_visits_count']);

                }
               
                $affiliatepress_dashboard_total_commission  = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_total_commission,2));
                $affiliatepress_dashboard_total_revenue_commission   = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_total_revenue_commission,2));  
                $affiliatepress_dashboard_unpaid_commission = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_unpaid_commission,2)); 


                /*
                $affiliatepress_dashboard_total_commission = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount)', 'WHERE  ap_commission_status IN (1,4)', '', '', '', '', true, false,ARRAY_A));
                $affiliatepress_dashboard_total_commission = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_total_commission,2));

                $affiliatepress_dashboard_paid_commission = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount)', 'WHERE  ap_commission_status IN (4)', '', '', '', '', true, false,ARRAY_A));
                $affiliatepress_dashboard_paid_commission = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_paid_commission,2));

                $affiliatepress_dashboard_unpaid_commission = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount)', 'WHERE  ap_commission_status IN (1)', '', '', '', '', true, false,ARRAY_A));
                $affiliatepress_dashboard_unpaid_commission = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_dashboard_unpaid_commission,2));

                $affiliatepress_dashboard_total_commission_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'COUNT(ap_commission_id)', 'WHERE  ap_commission_status IN (1,4)', '', '', '', '', true, false,ARRAY_A));

                $affiliatepress_dashboard_total_visits_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_visits, 'COUNT(ap_visit_id)', '', '', '', '', '', true, false,ARRAY_A));
                */
                $affiliatepress_dashboard_total_affiliate_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'COUNT(ap_affiliates_id)', 'WHERE ap_affiliates_status = %d', array(1), '', '', '', true, false,ARRAY_A));

            }

            $affiliatepress_revenue_chart_data = array(
                'labels' => array(),
                'values' => array(),
            );
            $affiliatepress_visit_chart_data = array(
                'labels' => array(),
                'values' => array(),
            ); 
            $affiliatepress_affiliate_chart_data = array(
                'labels' => array(),
                'values' => array(),
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


            if(!empty($affiliatepress_total_year_dates) && count($affiliatepress_total_year_dates) > 1 && count($affiliatepress_total_months_dates) > 12 && $affiliatepress_has_dates_only == false){
                if(!empty($affiliatepress_total_year_dates)){
                    foreach($affiliatepress_total_year_dates as $affiliatepress_key=>$affiliatepress_value){
                        $affiliatepress_revenue_chart_data['labels'][]   = $affiliatepress_value;
                        $affiliatepress_visit_chart_data['labels'][]     = $affiliatepress_value;
                        $affiliatepress_affiliate_chart_data['labels'][] = $affiliatepress_value;


                        $affiliatepress_day_total_commission = 0;
                        $affiliatepress_total_visits_count   = 0;

                        $affiliatepress_dashboard_report_data = $wpdb->get_row( $wpdb->prepare( "SELECT sum(ap_affiliate_report_visits) as total_visits_count, SUM(ap_affiliate_report_total_commission_revenue) as total_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report Inner Join {$affiliatepress_tbl_ap_affiliates} as affiliate ON report.ap_affiliates_id = affiliate.ap_affiliates_id  WHERE affiliate.ap_affiliates_status = %d  AND DATE_FORMAT(DATE(ap_affiliate_report_date),'%Y') = %s", 1, $affiliatepress_key), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                        if(!empty($affiliatepress_dashboard_report_data)){
                            $affiliatepress_day_total_commission = floatval($affiliatepress_dashboard_report_data['total_commission_amount']);
                            $affiliatepress_total_visits_count = intval($affiliatepress_dashboard_report_data['total_visits_count']);
                        }

                        $affiliatepress_revenue_chart_data['values'][] = round($affiliatepress_day_total_commission,2);
                        $affiliatepress_visit_chart_data['values'][] = $affiliatepress_total_visits_count;

                        /*
                        $affiliatepress_day_total_commission = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount)', 'WHERE ap_commission_status IN (1,4) AND DATE_FORMAT(DATE(ap_commission_created_date),"%Y") = %s ', array($affiliatepress_key), '', '', '', true, false,ARRAY_A));
                        
                        $affiliatepress_total_visits_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_visits, 'COUNT(ap_visit_id)', 'WHERE  DATE_FORMAT(DATE(ap_visit_created_date),"%Y") = %s ', array( $affiliatepress_key), '', '', '', true, false,ARRAY_A));
                        
                        */

                        $affiliatepress_total_affiliate_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'COUNT(ap_affiliates_id)', 'WHERE DATE_FORMAT(DATE(ap_affiliates_created_at),"%Y") = %s AND ap_affiliates_status = %d', array( $affiliatepress_key,1), '', '', '', true, false,ARRAY_A));




                        $affiliatepress_affiliate_chart_data['values'][] = $affiliatepress_total_affiliate_count;

                    }
                }  
            }else if(!empty($affiliatepress_total_months_dates) && count($affiliatepress_total_months_dates) > 1 && $affiliatepress_has_dates_only == false){
                if(!empty($affiliatepress_total_months_dates)){
                    foreach($affiliatepress_total_months_dates as $affiliatepress_key=>$affiliatepress_value){
                        $affiliatepress_revenue_chart_data['labels'][] = $affiliatepress_value;
                        $affiliatepress_visit_chart_data['labels'][] = $affiliatepress_value;
                        $affiliatepress_affiliate_chart_data['labels'][] = $affiliatepress_value;


                        $affiliatepress_day_total_commission = 0;
                        $affiliatepress_total_visits_count   = 0;



                        $affiliatepress_dashboard_report_data = $wpdb->get_row( $wpdb->prepare( "SELECT sum(ap_affiliate_report_visits) as total_visits_count, SUM(ap_affiliate_report_total_commission_revenue) as total_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report Inner Join {$affiliatepress_tbl_ap_affiliates} as affiliate ON report.ap_affiliates_id = affiliate.ap_affiliates_id  WHERE affiliate.ap_affiliates_status = %d  AND  DATE_FORMAT(DATE(ap_affiliate_report_date),'%m-%Y') = %s", 1, $affiliatepress_key), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                        if(!empty($affiliatepress_dashboard_report_data)){
                            $affiliatepress_day_total_commission = floatval($affiliatepress_dashboard_report_data['total_commission_amount']);
                            $affiliatepress_total_visits_count = intval($affiliatepress_dashboard_report_data['total_visits_count']);
                        }
                        
                        $affiliatepress_revenue_chart_data['values'][] = round($affiliatepress_day_total_commission,2);
                        $affiliatepress_visit_chart_data['values'][] = $affiliatepress_total_visits_count;



                        $affiliatepress_total_affiliate_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'COUNT(ap_affiliates_id)', 'WHERE DATE_FORMAT(DATE(ap_affiliates_created_at),"%m-%Y") = %s AND ap_affiliates_status = %d', array( $affiliatepress_key,1), '', '', '', true, false,ARRAY_A));
                        $affiliatepress_affiliate_chart_data['values'][] = $affiliatepress_total_affiliate_count;

                    }
                }                
            }else{
                $affiliatepress_all_between_date = $AffiliatePress->affiliatepress_get_dates_between($affiliatepress_dashboard_start_date, $affiliatepress_dashboard_end_date);
                $affiliatepress_all_earning_arr = array();
                $affiliatepress_all_visits_arr = array();
                $affiliatepress_all_affiliate_arr = array();

                $affiliatepress_dashboard_report_data = $wpdb->get_results( $wpdb->prepare( "SELECT ap_affiliate_report_date, sum(ap_affiliate_report_visits) as total_visits_count, sum(ap_affiliate_report_total_commission_revenue) as total_commission_amount FROM {$affiliatepress_tbl_ap_affiliate_report} as report Inner Join {$affiliatepress_tbl_ap_affiliates} as affiliate ON report.ap_affiliates_id = affiliate.ap_affiliates_id  WHERE affiliate.ap_affiliates_status = %d  AND (DATE(ap_affiliate_report_date) >= %s AND DATE(ap_affiliate_report_date) <= %s) GROUP BY ap_affiliate_report_date ", 1, $affiliatepress_dashboard_start_date, $affiliatepress_dashboard_end_date), ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_report is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                if(!empty($affiliatepress_dashboard_report_data)){
                    foreach($affiliatepress_dashboard_report_data as $affiliatepress_data){

                        $affiliatepress_date_key_data      = $affiliatepress_data['ap_affiliate_report_date'];
                        $affiliatepress_total_visits_count = $affiliatepress_data['total_visits_count'];
                        $affiliatepress_all_earning_arr[$affiliatepress_date_key_data] = round($affiliatepress_data['total_commission_amount'],2);
                        $affiliatepress_all_visits_arr[$affiliatepress_date_key_data]  = $affiliatepress_total_visits_count;

                    }
                }


                $affiliatepress_affiliate_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'COUNT(ap_affiliates_id) as total_affiliate, DATE(ap_affiliates_created_at) as affiliate_date', 'WHERE (DATE(ap_affiliates_created_at) >= %s AND DATE(ap_affiliates_created_at) <= %s) AND ap_affiliates_status = %d', array($affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date,1), 'GROUP BY affiliate_date', '', '', false, false,ARRAY_A);
                
                if(!empty($affiliatepress_affiliate_data)){
                    foreach($affiliatepress_affiliate_data as $affiliatepress_affiliate_d){
                        $affiliatepress_all_affiliate_arr[$affiliatepress_affiliate_d['affiliate_date']] = intval($affiliatepress_affiliate_d['total_affiliate']);
                    }
                }
                $affiliatepress_chart_value = array();
                if(!empty($affiliatepress_all_between_date)){
                    foreach($affiliatepress_all_between_date as $affiliatepress_key=>$affiliatepress_value){

                        $affiliatepress_revenue_chart_data['labels'][] = $affiliatepress_value;
                        $affiliatepress_visit_chart_data['labels'][] = $affiliatepress_value;
                        $affiliatepress_affiliate_chart_data['labels'][] = $affiliatepress_value;

                        $affiliatepress_day_total_commission = (isset($affiliatepress_all_earning_arr[$affiliatepress_key]))?$affiliatepress_all_earning_arr[$affiliatepress_key]:0;
                        $affiliatepress_revenue_chart_data['values'][] = $affiliatepress_day_total_commission;

                        $affiliatepress_total_visits_count = (isset($affiliatepress_all_visits_arr[$affiliatepress_key]))?$affiliatepress_all_visits_arr[$affiliatepress_key]:0;
                        $affiliatepress_visit_chart_data['values'][] = $affiliatepress_total_visits_count;

                        $affiliatepress_total_affiliate_count = (isset($affiliatepress_all_affiliate_arr[$affiliatepress_key]))?$affiliatepress_all_affiliate_arr[$affiliatepress_key]:0;
                        $affiliatepress_affiliate_chart_data['values'][] = $affiliatepress_total_affiliate_count;
                        
                    }
                }
            }
            
            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');                        
            $response['data']['dashboard_total_commission'] = $affiliatepress_dashboard_total_commission;
            $response['data']['dashboard_paid_commission'] = $affiliatepress_dashboard_total_revenue_commission;
            $response['data']['dashboard_unpaid_commission'] = $affiliatepress_dashboard_unpaid_commission;
            $response['data']['dashboard_total_commission_count'] = $affiliatepress_dashboard_total_commission_count;
            $response['data']['dashboard_total_visits_count'] = $affiliatepress_dashboard_total_visits_count;
            $response['data']['dashboard_total_affiliate_count'] = $affiliatepress_dashboard_total_affiliate_count;
            $response['data']['revenue_chart_data'] = $affiliatepress_revenue_chart_data;
            $response['data']['visit_chart_data']   = $affiliatepress_visit_chart_data;
            $response['data']['affiliate_chart_data']   = $affiliatepress_affiliate_chart_data;

            wp_send_json($response);
            exit;            
        }



        
        /**
         * Function for dynamic const for dashboard add in vue
         *
         * @return string
        */
        function affiliatepress_dashboard_dynamic_constant_define_func($affiliatepress_dashboard_dynamic_constant_define){
            return $affiliatepress_dashboard_dynamic_constant_define;
        }

        /**
         * Function for dashboard vue data
         *
         * @return json
        */
        function affiliatepress_dashboard_dynamic_data_fields_func($affiliatepress_dashboard_vue_data_fields){            
            
            global $AffiliatePress,$affiliatepress_dashboard_vue_data_fields, $wpdb, $affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_tbl_ap_affiliate_visits,$affiliatepress_global_options,$affiliatepress_tracking;
                                    
            $affiliatepress_dashboard_vue_data_fields['all_status'] = array();
            $affiliatepress_dashboard_vue_data_fields['affiliates']['affiliate_user_name'] = '';

            $affiliatepress_dashboard_end_date = date('Y-m-d');  //phpcs:ignore
            $affiliatepress_dashboard_start_date = date('Y-m-d', strtotime($affiliatepress_dashboard_end_date.' -30 days'));//phpcs:ignore


            $affiliatepress_dashboard_vue_data_fields['dashboard_date_range'] = array($affiliatepress_dashboard_start_date,$affiliatepress_dashboard_end_date);

            $affiliatepress_dashboard_vue_data_fields['dashboard_change_date_loader']       = '0';
            $affiliatepress_dashboard_vue_data_fields['dashboard_total_commission']       = '';
            $affiliatepress_dashboard_vue_data_fields['dashboard_paid_commission']        = '';
            $affiliatepress_dashboard_vue_data_fields['dashboard_unpaid_commission']      = '';
            $affiliatepress_dashboard_vue_data_fields['dashboard_total_visits_count']     = '';
            $affiliatepress_dashboard_vue_data_fields['dashboard_total_commission_count'] = '';
            $affiliatepress_dashboard_vue_data_fields['dashboard_total_affiliate_count']  = '';
            $affiliatepress_dashboard_vue_data_fields['revenue_chart_data'] = array();
            $affiliatepress_dashboard_vue_data_fields['visit_chart_data'] = array();
            $affiliatepress_dashboard_vue_data_fields['affiliate_chart_data'] = array();

            /* Get Commission Records */
            $affiliatepress_tbl_ap_affiliate_commissions_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_commissions contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_user_table = $this->affiliatepress_tablename_prepare($wpdb->users); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->users contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $wp_usermeta_table = $this->affiliatepress_tablename_prepare($wpdb->usermeta); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->usermeta contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_tbl_ap_affiliates_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function            

            $affiliatepress_commissions_record = $wpdb->get_results("SELECT commissions.*,affiliate.ap_affiliates_user_id, affiliate.ap_affiliates_first_name, affiliate.ap_affiliates_last_name  FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} as commissions INNER JOIN {$affiliatepress_tbl_ap_affiliates_temp} as affiliate  ON commissions.ap_affiliates_id = affiliate.ap_affiliates_id  order by ap_commission_id DESC LIMIT 8", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm

            $affiliatepress_all_commissions = array();
            if (! empty($affiliatepress_commissions_record) ) {
                $affiliatepress_counter = 1;
                foreach ( $affiliatepress_commissions_record as $affiliatepress_key=>$affiliatepress_single_commission ) {

                    $affiliatepress_commission = $affiliatepress_single_commission;

                    $affiliatepress_user_id = intval($affiliatepress_single_commission['ap_affiliates_user_id']);                       
                    $user = get_user_by('id', $affiliatepress_user_id);

                    $affiliatepress_user_first_name =  esc_html($affiliatepress_single_commission['ap_affiliates_first_name']);
                    $affiliatepress_user_last_name  =  esc_html($affiliatepress_single_commission['ap_affiliates_last_name']);
                    $affiliatepress_full_name = $affiliatepress_user_first_name.' '.$affiliatepress_user_last_name;
                  
                    $affiliatepress_formated_commission_reference_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_single_commission['ap_commission_reference_amount']);
                    $affiliatepress_formated_commission_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_single_commission['ap_commission_amount']);

                    $affiliatepress_source_plugin_name = $AffiliatePress->affiliatepress_get_supported_addon_name($affiliatepress_single_commission['ap_commission_source']);
                    $affiliatepress_commission['source_plugin_name'] = esc_html($affiliatepress_source_plugin_name);
                    $affiliatepress_commission['change_status_loader'] = '';
                    $affiliatepress_commission['ap_formated_commission_reference_amount'] = $affiliatepress_formated_commission_reference_amount;
                    $affiliatepress_commission['ap_formated_commission_amount'] = $affiliatepress_formated_commission_amount;
                    $affiliatepress_commission['ap_commission_status_org'] = esc_html($affiliatepress_single_commission['ap_commission_status']);
                    if($affiliatepress_single_commission['ap_commission_reference_id'] != 0){
                        $affiliatepress_commission['commission_order_link'] = apply_filters('affiliatepress_modify_commission_link', $affiliatepress_single_commission['ap_commission_reference_id'], $affiliatepress_single_commission['ap_commission_source']);                     
                    }
                    $affiliatepress_commission['commission_created_date_formated'] = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_single_commission['ap_commission_created_date']);
                    $affiliatepress_commission['full_name']             = esc_html($affiliatepress_full_name);
                    $affiliatepress_commission['change_status_loader']  = ''; 


                    $affiliatepress_commission_product = (isset($affiliatepress_single_commission['ap_commission_reference_detail']) && !empty($affiliatepress_single_commission['ap_commission_reference_detail']))?$affiliatepress_single_commission['ap_commission_reference_detail']:'-';
                    
                    $affiliatepress_commission['affiliatepress_commission_product'] = $affiliatepress_commission_product;                    

                    $affiliatepress_commission['row_class']  = '';

                    $affiliatepress_commission = apply_filters('affiliatepress_backend_modify_dashboard_commission_row', $affiliatepress_commission, $affiliatepress_single_commission); 
                    $affiliatepress_all_commissions[] = $affiliatepress_commission;
                }
            }    
            
            $affiliatepress_options = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_all_commissions_status = $affiliatepress_options['commissions_status'];  

            foreach ($affiliatepress_all_commissions_status as $affiliatepress_key => $afiliatepress_status) {
                if($afiliatepress_status['value'] == 4){
                    $affiliatepress_dashboard_vue_data_fields['commission_status_paid'] = $afiliatepress_status['text'];
                    unset($affiliatepress_all_commissions_status[$affiliatepress_key]);
                }
            }

            $affiliatepress_current_currency_symbol = $AffiliatePress->affiliatepress_get_current_currency_symbol();
            $affiliatepress_dashboard_vue_data_fields['currency_symbol'] = $affiliatepress_current_currency_symbol;

            
            $affiliatepress_dashboard_vue_data_fields['all_commissions_status'] = $affiliatepress_all_commissions_status;
            $affiliatepress_dashboard_vue_data_fields['commissions'] = $affiliatepress_all_commissions; 

            $affiliatepress_any_interation_active = false;
            $affiliatepress_active_integration_list = $affiliatepress_tracking->affiliatepress_integration_list('active');
            if(!empty($affiliatepress_active_integration_list)){
                $affiliatepress_any_interation_active = true;
            }

            $affiliatepress_dashboard_vue_data_fields['affiliatepress_any_interation_active'] = $affiliatepress_any_interation_active;

            $affiliatepress_dashboard_vue_data_fields = apply_filters('affiliatepress_backend_modify_dashboard_data', $affiliatepress_dashboard_vue_data_fields); 
            
            return wp_json_encode($affiliatepress_dashboard_vue_data_fields);

        }
        
        /**
         * Function for dashboard module vue metod
         *
         * @param  string $affiliatepress_dashboard_dynamic_vue_methods
         * @return string
         */
        function affiliatepress_dashboard_dynamic_vue_methods_func($affiliatepress_dashboard_dynamic_vue_methods){
            global $affiliatepress_notification_duration;

            $affiliatepress_dashboard_dynamic_add_vue_methods = "";
            $affiliatepress_dashboard_dynamic_add_vue_methods = apply_filters('affiliatepress_dashboard_dynamic_add_vue_methods', $affiliatepress_dashboard_dynamic_add_vue_methods); 

            $affiliatepress_dashboard_dynamic_vue_methods.='
            
                affiliatepress_change_status(update_id, index, new_status, old_status){
                    const vm = this;
                    vm.commissions[index].change_status_loader = 1;
                    var postData = { action:"affiliatepress_change_commissions_status", update_id: update_id, new_status: new_status, old_status: old_status, _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(response.data == "0" || response.data == 0){      
                            vm.commissions[index].change_status_loader = 0;                  
                            return false;
                        }else{
                            vm.$notify({
                                title: "'.esc_html__('Success', 'affiliatepress-affiliate-marketing').'",
                                message: "'.esc_html__('Commission status changed successfully', 'affiliatepress-affiliate-marketing').'",
                                type: "success",
                                customClass: "success_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });  
                            vm.commissions[index].change_status_loader = 0;                      
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
                affiliatepress_get_dashboard_detail(){
                    const vm = this;
                    vm.dashboard_change_date_loader = "1";
                    var postData = { action:"affiliatepress_backend_dashboard_data", dashboard_date_range:vm.dashboard_date_range, _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        vm.ap_first_page_loaded = "0";
                        if(response.data.variant == "success"){      
                            vm.dashboard_change_date_loader = "0";
                            vm.dashboard_total_commission = response.data.data.dashboard_total_commission;
                            vm.dashboard_total_affiliate_count = response.data.data.dashboard_total_affiliate_count;
                            vm.dashboard_paid_commission = response.data.data.dashboard_paid_commission;
                            vm.dashboard_unpaid_commission = response.data.data.dashboard_unpaid_commission;
                            vm.dashboard_total_visits_count = response.data.data.dashboard_total_visits_count;
                            vm.dashboard_total_commission_count = response.data.data.dashboard_total_commission_count;                        
                            vm.revenue_chart_data = response.data.data.revenue_chart_data;
                            vm.visit_chart_data = response.data.data.visit_chart_data;
                            vm.affiliate_chart_data = response.data.data.affiliate_chart_data;
                            setTimeout(function(){
                                const revenue_chart = document.getElementById("revenue_chart").getContext("2d"); 
                                const visit_chart = document.getElementById("visit_chart").getContext("2d"); 
                                if (typeof window.revenue_chart_data != "undefined" && window.revenue_chart_data) {
                                    window.revenue_chart_data.destroy();
                                }                          
                                window.revenue_chart_data = new Chart(revenue_chart, {
                                    type: "line",
                                    data: {
                                        labels: vm.revenue_chart_data.labels,
                                        datasets: [{
                                            label: "Comission Revenue",
                                            data: vm.revenue_chart_data.values,
                                            borderColor: "#3170F2",
                                            backgroundColor: "rgba(195, 215, 251, 0.6)",
                                            fill: true,
                                            pointRadius: function(context) {
                                                return context.raw === 0 ? 0 : 3;
                                            },
                                            pointHoverRadius: function(context) {
                                                return context.raw === 0 ? 0 : 3;
                                            },
                                            pointBackgroundColor: "#3170F2"
                                        }]
                                    },
                                    options: {
                                        interaction: {
                                            mode: "nearest",
                                            intersect: false
                                        },
                                        plugins: {
                                            legend: {
                                                display: false,
                                                onClick: null
                                            },
                                            tooltip: {
                                                enabled: true,
                                                mode: "nearest",
                                                intersect: false,
                                                callbacks: {
                                                    label: function(context) {
                                                        let label = "'.esc_html__('Revenue', 'affiliatepress-affiliate-marketing').': ";
                                                        if (vm.currency_symbol) {
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
                                if (typeof window.visit_chart_data != "undefined" && window.visit_chart_data) {
                                    window.visit_chart_data.destroy();
                                } 
                                window.visit_chart_data = new Chart(visit_chart, {
                                    type: "line",
                                    data: {
                                        labels: vm.visit_chart_data.labels,
                                        datasets: [{
                                            label: "'.esc_html__('Visits', 'affiliatepress-affiliate-marketing').': ",
                                            data: vm.visit_chart_data.values,
                                            borderColor: "#D75394",
                                            backgroundColor: "rgba(215, 83, 148, 0.2)",
                                            fill: true,
                                            pointRadius: function(context) {
                                                return context.raw === 0 ? 0 : 3;
                                            },
                                            pointHoverRadius: function(context) {
                                                return context.raw === 0 ? 0 : 3;
                                            },
                                            pointBackgroundColor: "#D75394"
                                        }]
                                    },
                                    options: {
                                        interaction: {
                                            mode: "nearest",
                                            intersect: false
                                        },
                                        plugins: {
                                            legend: {
                                                display: false
                                            },
                                            tooltip: {
                                                enabled: true,
                                                mode: "nearest",
                                                intersect: false
                                            }
                                        },
                                        scales: {
                                            x: {
                                                beginAtZero: true,
                                            },
                                            y: {
                                                beginAtZero: true
                                            }
                                        }
                                    }
                                });
                                const affiliate_chart = document.getElementById("affiliate_chart").getContext("2d");
                                if (typeof window.affiliate_chart_data != "undefined" && window.affiliate_chart_data) {
                                    window.affiliate_chart_data.destroy();
                                }                                                
                                window.affiliate_chart_data = new Chart(affiliate_chart, {
                                    type: "bar",
                                    data: {
                                        labels: vm.affiliate_chart_data.labels, 
                                        datasets: [{
                                            label: "'.esc_html__("Affiliates", "affiliatepress-affiliate-marketing").'",
                                            data: vm.affiliate_chart_data.values,
                                            borderColor: "#82DED0",
                                            backgroundColor: "rgba(130, 222, 208, 1)",
                                            fill: true,
                                        }]
                                    },
                                    options: {
                                        interaction: {
                                            mode: "nearest",
                                            intersect: false
                                        },
                                        plugins: {
                                            legend: {
                                                display: false
                                            },
                                            tooltip: {
                                                enabled: true,
                                                mode: "nearest",
                                                intersect: false
                                            }
                                        },
                                        scales: {
                                            x: {
                                                beginAtZero: true,
                                            },
                                            y: {
                                                beginAtZero: true
                                            }
                                        }
                                    }
                                });   
                            },500);
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
                        vm.ap_first_page_loaded = "0";
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
                affiliatepress_full_row_clickable(row){
                    const vm = this
                    if (event.target.closest(".ap-flag-icon-data .el-only-child__content.el-tooltip__trigger.el-tooltip__trigger")) {
                        return;
                    }
                    vm.$refs.multipleTable.toggleRowExpansion(row);
                },   
                affiliatepress_redirect_integration_settings(){
                    const vm = this;      
                    var redirect_url = "?page=affiliatepress_settings&setting_page=integrations_settings";          	
                    if(redirect_url != "") {						
                        window.location.href = redirect_url;
                    }		
                },
                '.$affiliatepress_dashboard_dynamic_add_vue_methods.'  
            ';
            return $affiliatepress_dashboard_dynamic_vue_methods;
        ?>                        
         
        <?php 
        }
        
        /**
         * Function for dynamic View load
         *
         * @return html
        */
        function affiliatepress_dashboard_dynamic_view_load_func(){

            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/dashboard/manage_dashboard.php';
            $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_dashboard_view_file_path', $affiliatepress_load_file_name);
            include $affiliatepress_load_file_name;

        }

        
        /**
         * Function for affiliates default Vue Data
         *
         * @return void
        */
        function affiliatepress_dashboard_vue_data_fields(){

            global $affiliatepress_dashboard_vue_data_fields,$affiliatepress_global_options;            
            $affiliatepress_pagination          = wp_json_encode(array( 10, 20, 50, 100, 200, 300, 400, 500 ));
            $affiliatepress_pagination_arr      = json_decode($affiliatepress_pagination, true);
            $affiliatepress_pagination_selected = $this->affiliatepress_per_page_record;

            $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_pagination_value = (isset($affiliatepress_global_options_data['pagination_val']))?$affiliatepress_global_options_data['pagination_val']:array();

            $affiliatepress_dashboard_vue_data_fields = array(
                'bulk_action'                => 'bulk_action',
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
                'dashboard_date_range'       => array(),
                'order'                      => '',
                'order_by'                   => '',
                'items'                      => array(),
                'multipleSelection'          => array(),
                'multipleSelectionVal'       => '',
                'perPage'                    => $affiliatepress_pagination_selected,
                'totalItems'                 => 0,
                'currentPage'                => 1,
                'savebtnloading'             => false,
                'modal_loader'               => 1,
                'is_display_loader'          => '0',
                'is_disabled'                => false,
                'is_display_save_loader'     => '0',
                'is_multiple_checked'        => false,              
                'pagination_length_val'      => '10',
                'pagination_val'             => $affiliatepress_pagination_value,

            );
        }



    }
}
global $affiliatepress_dashboard;
$affiliatepress_dashboard = new affiliatepress_dashboard();
