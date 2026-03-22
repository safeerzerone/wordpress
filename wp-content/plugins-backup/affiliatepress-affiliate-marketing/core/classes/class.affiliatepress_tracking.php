<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('affiliatepress_tracking') ){
    
    /**
     * AffiliatePress_Tracking
    */
    class affiliatepress_tracking Extends AffiliatePress_Core{
        
        private $referral_var;
        private $affiliatepress_expiration_time;
        public $referral;
                
        /**
         * Function for construct
         *
         * @return void
        */
        function __construct(){            
            
            /**Common Function for set affiliatepress cookie  */
            add_action( 'wp', array( &$this, 'affiliatepress_set_ref_in_cookie' ),10);

            /**Function for common validation for all integration */
            add_filter('affiliatepress_commission_validation',array($this,'affiliatepress_commission_common_validation_func'),10,5);

            /**Common allow zero amount Filter validation for all integration commission */
            add_filter('affiliatepress_commission_final_validation',array($this,'affiliatepress_commission_final_validation_func'),10,6);

            /* Report data insert function added */
            add_action('affiliatepress_after_visit_insert',array($this,'affiliatepress_after_visit_insert_func'),10,2);

            /* Report data insert function added */
            add_action('affiliatepress_after_commission_created',array($this,'affiliatepress_after_commission_created_func'),20,2);

            /*  Report data update after commission status update record */
            add_action('affiliatepress_after_commissions_status_change',array($this,'affiliatepress_commissions_report_data_change_func'),10,3);

            /* backend commission record update after */
            add_action('affiliatepress_after_commission_record_update',array($this,'affiliatepress_after_commission_record_update_func'),10,2);
            /* backend commission record update after */
            add_action('affiliatepress_after_delete_commission',array($this,'affiliatepress_after_delete_commission_func'),10,2);
            /* Update Report Data After MLM Commission Update */
            add_action('affiliatepress_after_mlm_commission_add',array($this,'affiliatepress_after_mlm_commission_add_func'),10,2);

            /* Removed Affiliate User Commission Data When Delete Affiliate User */
            add_action('affiliatepress_after_delete_affiliate',array($this,'affiliatepress_after_delete_affiliate_func'),10,1);

            /* Function For After Migrate Report Table Data Update */
            add_action('affiliatepress_after_migrate_commission_record',array($this,'affiliatepress_after_migrate_commission_record_func'),10,1);

            add_filter('affiliatepress_calculate_commission_amount_default',array($this,'affiliatepress_calculate_commission_amount_func'),10,4);

            add_filter('affiliatepress_get_current_affiliate_rate_default',array($this,'affiliatepress_get_default_commission_rate_func'),10,2);
        }                

        /**
         * Function for after migrate data update report table
         *
         * @param  mixed $affiliatepress_commission_id
         * @return void
        */
        function affiliatepress_after_migrate_commission_record_func($affiliatepress_commission_id){
            if(empty($affiliatepress_commission_id)){
                return;
            }
            $this->affiliatepress_commissions_report_data_change_func($affiliatepress_commission_id, 0, 0);  
        }

        /**
         * Function for after delete affiliate data
         *
         * @param  mixed $affiliatepress_affiliates_id
         * @return void
        */
        function affiliatepress_after_delete_affiliate_func($affiliatepress_affiliates_id){
            global $wpdb, $affiliatepress_tbl_ap_affiliate_report;
            if(empty($affiliatepress_affiliates_id)){
                return;
            }
            $this->affiliatepress_delete_record($affiliatepress_tbl_ap_affiliate_report, array( 'ap_affiliates_id' => $affiliatepress_affiliates_id ), array('%d'));
        }
                
        /**
         * Function for update commission MLM record
         *
         * @param  integer $affiliatepress_commission_id
         * @param  array $affiliatepress_commission_data
         * @return void
        */
        function affiliatepress_after_mlm_commission_add_func($affiliatepress_commission_id,$affiliatepress_commission_data){
            if(empty($affiliatepress_commission_id)){
                return;
            }
            $this->affiliatepress_commissions_report_data_change_func($affiliatepress_commission_id, 0, 0);       
        }

        /**
         * Function for update commission report data 
         *
         * @param  integer $affiliatepress_commission_id
         * @return void
        */
        function affiliatepress_after_delete_commission_func($affiliatepress_commission_id,$affiliatepress_commission_data){
            if(empty($affiliatepress_commission_id)){
                return;
            }
            $affiliatepress_affiliates_id           = (isset($affiliatepress_commission_data['ap_affiliates_id']))?intval($affiliatepress_commission_data['ap_affiliates_id']):'';
            $affiliatepress_visit_id                = (isset($affiliatepress_commission_data['ap_visit_id']))?intval($affiliatepress_commission_data['ap_visit_id']):'';
            $affiliatepress_commission_created_date = (isset($affiliatepress_commission_data['ap_commission_created_date']))?$affiliatepress_commission_data['ap_commission_created_date']:'';

            if(!empty($affiliatepress_commission_created_date) && !empty($affiliatepress_affiliates_id)){

                $affiliatepress_commission_created_date = date('Y-m-d',strtotime($affiliatepress_commission_created_date));//phpcs:ignore
                $affiliatepress_report_insert_type = "commission";

                $affiliatepress_report_insert_data = array(
                    'ap_commission_id' => $affiliatepress_commission_id,
                    'ap_visit_id'      => $affiliatepress_visit_id,
                    'ap_affiliates_id' => $affiliatepress_affiliates_id,
                    'start_date'       => $affiliatepress_commission_created_date,                
                );
    
                $this->affiliatepress_affiliate_report_update_func($affiliatepress_report_insert_type,$affiliatepress_report_insert_data);
    
            } 

        }
        
        /**
         * Function for update commission report
         *
         * @param  integer $affiliatepress_commission_id
         * @param  array $affilitepress_commission_args
         * @return void
        */
        function affiliatepress_after_commission_record_update_func($affiliatepress_commission_id, $affilitepress_commission_args){

            if(empty($affiliatepress_commission_id)){
                return;
            }
            
            $this->affiliatepress_commissions_report_data_change_func($affiliatepress_commission_id, 0, 0);

        }
        
        /**
         * affiliatepress_commissions_report_data_change_func
         *
         * @param  integer $affiliatepress_commission_id
         * @param  integer $affiliatepress_new_status
         * @param  integer $affiliatepress_old_status
         * @return void
        */
        function affiliatepress_commissions_report_data_change_func($affiliatepress_commission_id,$affiliatepress_new_status, $affiliatepress_old_status){

            if(empty($affiliatepress_commission_id)){
                return;
            }

            global $affiliatepress_tbl_ap_affiliate_commissions;

            $affiliatepress_commission_data = $this->affiliatepress_select_record( true, 'ap_affiliates_id, ap_visit_id, ap_commission_created_date', $affiliatepress_tbl_ap_affiliate_commissions, '*', 'WHERE ap_commission_id = %d', array( $affiliatepress_commission_id ), '', '', '', false, true,ARRAY_A);

            $affiliatepress_affiliates_id           = (isset($affiliatepress_commission_data['ap_affiliates_id']))?intval($affiliatepress_commission_data['ap_affiliates_id']):'';
            $affiliatepress_visit_id                = (isset($affiliatepress_commission_data['ap_visit_id']))?intval($affiliatepress_commission_data['ap_visit_id']):'';
            $affiliatepress_commission_created_date = (isset($affiliatepress_commission_data['ap_commission_created_date']))?$affiliatepress_commission_data['ap_commission_created_date']:'';

            if(!empty($affiliatepress_commission_created_date) && !empty($affiliatepress_affiliates_id)){

                $affiliatepress_commission_created_date = date('Y-m-d',strtotime($affiliatepress_commission_created_date));//phpcs:ignore
                $affiliatepress_report_insert_type = "commission";

                $affiliatepress_report_insert_data = array(
                    'ap_commission_id' => $affiliatepress_commission_id,
                    'ap_visit_id'      => $affiliatepress_visit_id,
                    'ap_affiliates_id' => $affiliatepress_affiliates_id,
                    'start_date'       => $affiliatepress_commission_created_date,                
                );
    
                $this->affiliatepress_affiliate_report_update_func($affiliatepress_report_insert_type,$affiliatepress_report_insert_data);
    
            }            


        }

        /**
         * Function for update report commission data
         *
         * @param  mixed $affiliatepress_commission_id
         * @param  mixed $affiliatepress_commission_data
         * @return void
        */
        function affiliatepress_after_commission_created_func($affiliatepress_commission_id, $affiliatepress_commission_data){

            if(empty($affiliatepress_commission_id)){
                return;
            }

            global $affiliatepress_tbl_ap_affiliate_commissions;

            $affiliatepress_affiliates_id           = (isset($affiliatepress_commission_data['ap_affiliates_id']))?intval($affiliatepress_commission_data['ap_affiliates_id']):'';
            $affiliatepress_visit_id                = (isset($affiliatepress_commission_data['ap_visit_id']))?intval($affiliatepress_commission_data['ap_visit_id']):'';
            $affiliatepress_commission_created_date = (isset($affiliatepress_commission_data['ap_commission_created_date']))?$affiliatepress_commission_data['ap_commission_created_date']:'';

            if(empty($affiliatepress_affiliates_id) || empty($affiliatepress_visit_id) || empty($affiliatepress_commission_created_date)){

                $affiliatepress_commission_data = $this->affiliatepress_select_record( true, 'ap_affiliates_id,ap_visit_id,ap_commission_created_date', $affiliatepress_tbl_ap_affiliate_commissions, '*', 'WHERE ap_commission_id = %d', array( $affiliatepress_commission_id ), '', '', '', false, true,ARRAY_A);

                $affiliatepress_affiliates_id           = (isset($affiliatepress_commission_data['ap_affiliates_id']))?intval($affiliatepress_commission_data['ap_affiliates_id']):'';
                $affiliatepress_visit_id                = (isset($affiliatepress_commission_data['ap_visit_id']))?intval($affiliatepress_commission_data['ap_visit_id']):'';
                $affiliatepress_commission_created_date = (isset($affiliatepress_commission_data['ap_commission_created_date']))?$affiliatepress_commission_data['ap_commission_created_date']:'';

            }            

            if(!empty($affiliatepress_commission_created_date)){

                $affiliatepress_commission_created_date = date('Y-m-d',strtotime($affiliatepress_commission_created_date));//phpcs:ignore
                $affiliatepress_report_insert_type = "commission";

                $affiliatepress_report_insert_data = array(
                    'ap_commission_id' => $affiliatepress_commission_id,
                    'ap_visit_id'      => $affiliatepress_visit_id,
                    'ap_affiliates_id' => $affiliatepress_affiliates_id,
                    'start_date'       => $affiliatepress_commission_created_date,                
                );
    
                $this->affiliatepress_affiliate_report_update_func($affiliatepress_report_insert_type,$affiliatepress_report_insert_data);
    
            }


        }

        /**
         * Function for after visit data insert report data
         *
         * @param  integer $affiliatepress_inserted_visit_id
         * @param  array $affiliatepress_args
         * @return void
        */
        function affiliatepress_after_visit_insert_func($affiliatepress_inserted_visit_id, $affiliatepress_args){
            
            if(!$affiliatepress_inserted_visit_id){
                return;
            }

            $affiliatepress_report_insert_type = "visits";
            $affiliatepress_start_date = (isset($affiliatepress_args['ap_visit_created_date'])) ? $affiliatepress_args['ap_visit_created_date']:'';
            if(!empty($affiliatepress_start_date)){
                $affiliatepress_start_date = date('Y-m-d',strtotime($affiliatepress_start_date));//phpcs:ignore
            }
            $affiliatepress_report_insert_data = array(
                'ap_visit_id'      => $affiliatepress_inserted_visit_id,
                'ap_affiliates_id' => (isset($affiliatepress_args['ap_affiliates_id']))?intval($affiliatepress_args['ap_affiliates_id']):0,
                'start_date'       => $affiliatepress_start_date,                
            );

            $this->affiliatepress_affiliate_report_update_func($affiliatepress_report_insert_type,$affiliatepress_report_insert_data);

        }
        
        /**
         * Function for insert affiliate report data
         *
         * @param  string $affiliatepress_report_insert_type
         * @param  array $affiliatepress_report_insert_data
         * @return void
        */
        function affiliatepress_affiliate_report_update_func($affiliatepress_report_insert_type, $affiliatepress_report_insert_data){

            global $wpdb, $affiliatepress_tbl_ap_affiliate_report;

            $affiliatepress_insert_date = (isset($affiliatepress_report_insert_data['start_date']))?$affiliatepress_report_insert_data['start_date']:'';
            $affiliatepress_affiliates_id = (isset($affiliatepress_report_insert_data['ap_affiliates_id']))?$affiliatepress_report_insert_data['ap_affiliates_id']:'';

            $affiliatepress_report_insert_data = array();
            if($affiliatepress_report_insert_type == "commission" && !empty($affiliatepress_insert_date) && !empty($affiliatepress_affiliates_id)){
                
                global $affiliatepress_tbl_ap_affiliate_commissions;

                $ap_commission_id = (isset($affiliatepress_report_insert_data['ap_commission_id']))?$affiliatepress_report_insert_data['ap_commission_id']:'';

                $affiliatepress_total_affiliate_commission_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount) as total_amount, SUM(ap_commission_reference_amount) as total_revenue, COUNT(ap_commission_id) as total_count', 'WHERE ap_commission_status IN (1,4) AND DATE(ap_commission_created_date) = %s AND ap_affiliates_id = %d ', array( $affiliatepress_insert_date,$affiliatepress_affiliates_id), '', '', '', false, true,ARRAY_A);


                $affiliatepress_total_affiliate_revenue_amount = (isset($affiliatepress_total_affiliate_commission_data['total_revenue']))?round(floatval($affiliatepress_total_affiliate_commission_data['total_revenue']),2):0;
                     

                $affiliatepress_total_affiliate_commission_amount = (isset($affiliatepress_total_affiliate_commission_data['total_amount']))?round(floatval($affiliatepress_total_affiliate_commission_data['total_amount']),2):0;
                $affiliatepress_total_commission_count = (isset($affiliatepress_total_affiliate_commission_data['total_count']))?intval($affiliatepress_total_affiliate_commission_data['total_count']):0;

                $affiliatepress_paid_affiliate_commission_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount) as total_amount, COUNT(ap_commission_id) as total_count', 'WHERE ap_commission_status IN (4) AND DATE(ap_commission_created_date) = %s AND ap_affiliates_id = %d ', array( $affiliatepress_insert_date,$affiliatepress_affiliates_id), '', '', '', false, true,ARRAY_A);

                $affiliatepress_paid_affiliate_commission_amount = (isset($affiliatepress_paid_affiliate_commission_data['total_amount']))?round(floatval($affiliatepress_paid_affiliate_commission_data['total_amount']),2):0;
                $affiliatepress_paid_commission_count = (isset($affiliatepress_paid_affiliate_commission_data['total_count']))?intval($affiliatepress_paid_affiliate_commission_data['total_count']):0;

                $affiliatepress_unpaid_affiliate_commission_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount) as total_amount, COUNT(ap_commission_id) as total_count', 'WHERE ap_commission_status IN (1) AND DATE(ap_commission_created_date) = %s AND ap_affiliates_id = %d ', array( $affiliatepress_insert_date,$affiliatepress_affiliates_id), '', '', '', false, true,ARRAY_A);

                $affiliatepress_unpaid_affiliate_commission_amount = (isset($affiliatepress_unpaid_affiliate_commission_data['total_amount']))?round(floatval($affiliatepress_unpaid_affiliate_commission_data['total_amount']),2):0;

                $affiliatepress_unpaid_commission_count = (isset($affiliatepress_unpaid_affiliate_commission_data['total_count']))?intval($affiliatepress_unpaid_affiliate_commission_data['total_count']):0;

                $affiliatepress_report_insert_data['ap_affiliate_report_total_commission_amount']  = round($affiliatepress_total_affiliate_commission_amount,2);
                $affiliatepress_report_insert_data['ap_affiliate_report_paid_commission_amount']   = round($affiliatepress_paid_affiliate_commission_amount,2);
                $affiliatepress_report_insert_data['ap_affiliate_report_unpaid_commission_amount'] = round($affiliatepress_unpaid_affiliate_commission_amount,2);

                $affiliatepress_report_insert_data['ap_affiliate_report_total_commission_revenue'] = round($affiliatepress_total_affiliate_revenue_amount,2);

                $affiliatepress_report_insert_data['ap_affiliate_report_total_commission'] = $affiliatepress_total_commission_count;
                $affiliatepress_report_insert_data['ap_affiliate_report_paid_commission'] = $affiliatepress_paid_commission_count;
                $affiliatepress_report_insert_data['ap_affiliate_report_unpaid_commission'] = $affiliatepress_unpaid_commission_count;

            }

            if($affiliatepress_report_insert_type == "visits" && !empty($affiliatepress_insert_date) && !empty($affiliatepress_affiliates_id)){   
                
                global $affiliatepress_tbl_ap_affiliate_visits;

                $affiliatepress_total_visits_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_visits, 'COUNT(ap_visit_id)', 'WHERE DATE(ap_visit_created_date) = %s AND ap_affiliates_id = %d ', array( $affiliatepress_insert_date,$affiliatepress_affiliates_id), '', '', '', true, false,ARRAY_A));

                $affiliatepress_unconverted_visits_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_visits, 'COUNT(ap_visit_id)', 'WHERE DATE(ap_visit_created_date) = %s  AND ap_affiliates_id = %d AND 	ap_commission_id = 0 ', array( $affiliatepress_insert_date,$affiliatepress_affiliates_id), '', '', '', true, false,ARRAY_A));

                $affiliatepress_converted_visits_count = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_visits, 'COUNT(ap_visit_id)', 'WHERE DATE(ap_visit_created_date) = %s  AND ap_affiliates_id = %d AND ap_commission_id <> 0 ', array( $affiliatepress_insert_date,$affiliatepress_affiliates_id), '', '', '', true, false,ARRAY_A));

                $affiliatepress_report_insert_data['ap_affiliate_report_visits'] = $affiliatepress_total_visits_count;
                $affiliatepress_report_insert_data['ap_affiliate_report_converted_visits'] =  $affiliatepress_converted_visits_count;
                $affiliatepress_report_insert_data['ap_affiliate_report_unconverted_visits'] =  $affiliatepress_unconverted_visits_count;

            }

            if(!empty($affiliatepress_affiliates_id) && !empty($affiliatepress_insert_date) && !empty($affiliatepress_report_insert_data)){
                
                $ap_affiliate_report_id = intval($wpdb->get_var($wpdb->prepare("SELECT ap_affiliate_report_id FROM {$affiliatepress_tbl_ap_affiliate_report} WHERE ap_affiliate_report_date = %s AND ap_affiliates_id = %d", $affiliatepress_insert_date, $affiliatepress_affiliates_id))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_settings_temp is prepare above using affiliatepress_tablename_prepare function. False Positive alarm

                if($ap_affiliate_report_id == 0){
                    $affiliatepress_report_insert_data['ap_affiliates_id'] = $affiliatepress_affiliates_id;
                    $affiliatepress_report_insert_data['ap_affiliate_report_date'] = $affiliatepress_insert_date;
                    $affiliatepress_affiliates_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_affiliate_report, $affiliatepress_report_insert_data);
                }else{
                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_report, $affiliatepress_report_insert_data, array( 'ap_affiliate_report_id' => $ap_affiliate_report_id ));
                }


            }


        }
                        
        /**
         * Common function for all integration check commission basis per order or not
         *
         * @return void
        */
        function affiliatepress_is_commission_basis_per_order(){
            global $AffiliatePress;
            $affiliatepress_commission_basis_per_order = false;
            $affiliatepress_default_discount_type = $AffiliatePress->affiliatepress_get_settings('default_discount_type', 'commissions_settings');
            /* Check Commission Basis Per Order Or Not  */
            $affiliatepress_flat_rate_commission_basis = $AffiliatePress->affiliatepress_get_settings('flat_rate_commission_basis', 'commissions_settings');
            if($affiliatepress_flat_rate_commission_basis == 'pre_order'){
                $affiliatepress_commission_basis_per_order = true;
            }
            
            return $affiliatepress_commission_basis_per_order;
        }

        /**
         * Common Function for insert commission For All Integration
         *
         * @param  mixed $affiliatepress_commission_data
         * @param  mixed $affiliatepress_affiliate_id
         * @param  mixed $affiliatepress_visit_id
         * @return integer
         */
        function affiliatepress_insert_commission($affiliatepress_commission_data, $affiliatepress_affiliate_id, $affiliatepress_visit_id){
            global $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_commission_rate = $AffiliatePress->affiliatepress_get_default_commission_rate();
            if(!empty($affiliatepress_commission_rate) && is_array($affiliatepress_commission_rate)){
                $affiliatepress_commission_rate = wp_json_encode($affiliatepress_commission_rate,true);
                $affiliatepress_commission_data['ap_commission_rate'] = $affiliatepress_commission_rate;
            }
            $affiliatepress_commission_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_commission_data);            
            return intval($affiliatepress_commission_id); /* return insert commission id */
        }
        
        /**
         * Common allow zero amount Filter validation for all integration commission
         *
         * @param  mixed $affiliatepress_commission_final_validation
         * @param  mixed $affiliatepress_affiliate_id
         * @param  mixed $affiliatepress_source
         * @param  mixed $affiliatepress_commission_amount
         * @param  mixed $affiliatepress_order_id
         * @param  mixed $affiliatepress_order_data
         * @return boolean
        */
        function affiliatepress_commission_final_validation_func($affiliatepress_commission_final_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_commission_amount, $affiliatepress_order_id, $affiliatepress_order_data){
            global $AffiliatePress;
            if($affiliatepress_commission_amount == 0){
                $affiliatepress_allow_zero_amount_commission = $AffiliatePress->affiliatepress_get_settings('allow_zero_amount_commission', 'commissions_settings');
                if($affiliatepress_allow_zero_amount_commission == "false"){
                    $affiliatepress_commission_final_validation['variant']   = 'error';
                    $affiliatepress_commission_final_validation['title']     = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                    $affiliatepress_commission_final_validation['msg']       = esc_html__( 'Commission was not inserted because the commission amount is zero.', 'affiliatepress-affiliate-marketing');                    
                }
            }
            return $affiliatepress_commission_final_validation;
        }

        /**
         * Common commission calculation function for all integration
         *
         * @param  faloat $affiliatepress_amount
         * @param  string $affiliatepress_payment_currency
         * @param  array $affiliatepress_args
         * @return array
        */
        function affiliatepress_calculate_commission_amount($affiliatepress_amount,$affiliatepress_payment_currency = '',$affiliatepress_args = array()){
            global $AffiliatePress,$affiliatepress_commission_debug_log_id;

            // $affiliatepress_commission_rules = apply_filters( 'affiliatepress_calculate_commission_amount', $affiliatepress_commission_rules, $affiliatepress_amount, $affiliatepress_payment_currency, $affiliatepress_args);
            
            $affiliatepress_commission_rules = array();
            $affiliatepress_commission_type_priorities = $AffiliatePress->affiliatepress_commission_type_priorities();
            if( !empty($affiliatepress_commission_type_priorities)){
                asort($affiliatepress_commission_type_priorities);
                
                foreach ($affiliatepress_commission_type_priorities as $type => $priorities) {
                    $affiliatepress_commission_rules = apply_filters( 'affiliatepress_calculate_commission_amount_'.$type, $affiliatepress_commission_rules, $affiliatepress_amount, $affiliatepress_payment_currency, $affiliatepress_args); 
                }
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $affiliatepress_args['origin'].' Commission Recurring Default Rule', 'affiliatepress_'.$affiliatepress_args['origin'].'_commission_tracking', wp_json_encode($affiliatepress_commission_rules), $affiliatepress_commission_debug_log_id);
            return $affiliatepress_commission_rules;
        }

        function affiliatepress_calculate_commission_amount_func($affiliatepress_commission_rules, $affiliatepress_amount, $affiliatepress_payment_currency, $affiliatepress_args){
            
            global $AffiliatePress,$affiliatepress_commission_debug_log_id;

            if ( empty( $affiliatepress_args['origin'] ) ) {
                return 0;
            }        
            if ( empty( $affiliatepress_args['type'] ) ) {
                return 0;
            }
            if ( empty( $affiliatepress_args['commission_basis'] ) ) {
                return 0;
            }            
            $affiliatepress_commission_amount = 0;

            $affiliatepress_quntity = (isset($affiliatepress_args['quntity']))?intval($affiliatepress_args['quntity']):1;
            if($affiliatepress_quntity == 0){
                $affiliatepress_quntity = 1;
            }
            $affiliatepress_commission_rules = array();
            if($affiliatepress_args['commission_basis'] == 'per_order'){ /* Calculation for per order commission */

                $affiliatepress_default_discount_val = $AffiliatePress->affiliatepress_get_settings('default_discount_val', 'commissions_settings');
                $affiliatepress_default_discount_type = $AffiliatePress->affiliatepress_get_settings('default_discount_type', 'commissions_settings');
                $affiliatepress_commission_amount = ( $affiliatepress_default_discount_type == 'percentage' ? round( ( $affiliatepress_amount * $affiliatepress_default_discount_val / 100 ), 2 ) : $affiliatepress_default_discount_val );
                $affiliatepress_commission_amount = round($affiliatepress_commission_amount,2);

                $affiliatepress_commission_rules['commission_amount'] = $affiliatepress_commission_amount;
                $affiliatepress_commission_rules['discount_val']      = $affiliatepress_commission_amount;
                $affiliatepress_commission_rules['discount_type']     = $affiliatepress_default_discount_type;
                $affiliatepress_commission_rules['commission_basis']  = $affiliatepress_args['commission_basis'];
                $affiliatepress_commission_rules['rule_source']       = 'default';

            }else{
                /* Calculation for per product commission */
                if($affiliatepress_amount > 0){
                    
                    $affiliatepress_default_discount_val = $AffiliatePress->affiliatepress_get_settings('default_discount_val', 'commissions_settings');
                    $affiliatepress_default_discount_type = $AffiliatePress->affiliatepress_get_settings('default_discount_type', 'commissions_settings');
                    $affiliatepress_commission_amount = ( $affiliatepress_default_discount_type == 'percentage' ? round( ( $affiliatepress_amount * $affiliatepress_default_discount_val / 100 ), 2 ) : $affiliatepress_default_discount_val );

                    if($affiliatepress_default_discount_type != 'percentage'){
                        $affiliatepress_commission_amount = $affiliatepress_commission_amount * $affiliatepress_quntity;
                    }

                    $affiliatepress_commission_rules['commission_amount'] = $affiliatepress_commission_amount;
                    $affiliatepress_commission_rules['discount_val']      = $affiliatepress_default_discount_val;
                    $affiliatepress_commission_rules['discount_type']     = $affiliatepress_default_discount_type;
                    $affiliatepress_commission_rules['commission_basis']  = $affiliatepress_args['commission_basis'];
                    $affiliatepress_commission_rules['rule_source']       = 'default';

                }    
            }

            do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $affiliatepress_args['origin'].' Commission Default Rule', 'affiliatepress_'.$affiliatepress_args['origin'].'_commission_tracking', wp_json_encode($affiliatepress_commission_rules), $affiliatepress_commission_debug_log_id);
	    
            return $affiliatepress_commission_rules;
        }
        
        
        /**
         * Function for common validation for all integration
         *
         * @param  array $affiliatepress_commission_validation
         * @param  integer $affiliatepress_affiliate_id
         * @param  string $affiliatepress_source
         * @param  string $affiliatepress_order_id
         * @param  array $affiliatepress_order_data
         * @return array
         */
        function affiliatepress_commission_common_validation_func($affiliatepress_commission_validation, $affiliatepress_affiliate_id, $affiliatepress_source, $affiliatepress_order_id, $affiliatepress_order_data){
            
            global $AffiliatePress,$affiliatepress_affiliates;
            if(empty($affiliatepress_commission_validation) && $affiliatepress_affiliate_id){

                if(!$affiliatepress_affiliates->affiliatepress_is_valid_affiliate($affiliatepress_affiliate_id)){                    
                    $affiliatepress_commission_validation['variant']   = 'error';
                    $affiliatepress_commission_validation['title']     = 'Error';
                    $affiliatepress_commission_validation['msg']       = 'Affiliate ID Not Valid.';  
                    return $affiliatepress_commission_validation;
                }

                $affiliatepress_commission_exists = $this->affiliatepress_check_commission_exists( array( 'reference' => $affiliatepress_order_id, 'origin' => $affiliatepress_source ));           
                if($affiliatepress_commission_exists){                  
                    $affiliatepress_commission_validation['variant']   = 'error';
                    $affiliatepress_commission_validation['title']     = 'Error';
                    $affiliatepress_commission_validation['msg']       = 'Commission already added';  
                    return $affiliatepress_commission_validation;                                  
                }                

                $affiliatepress_earn_commissions_own_orders = $AffiliatePress->affiliatepress_get_settings('earn_commissions_own_orders', 'commissions_settings'); 
                if($affiliatepress_earn_commissions_own_orders != 'true'){
                    $affiliatepress_affiliate_user_id = $AffiliatePress->affiliatepress_get_affiliate_user_id();
                    if(is_user_logged_in() && get_current_user_id() == $affiliatepress_affiliate_user_id && $affiliatepress_affiliate_user_id != 0 ) {                        
                        $affiliatepress_commission_validation['variant']   = 'error';
                        $affiliatepress_commission_validation['title']     = 'Error';
                        $affiliatepress_commission_validation['msg']       = 'Pending commission was not created because the customer is also the affiliate.';
                    }                    
                }
            }
            return $affiliatepress_commission_validation;
        }
                
        /**
         * Function for check commission already exists or not
         *
         * @param  array $affiliatepress_args
         * @return boolean
         */
        function affiliatepress_check_commission_exists($affiliatepress_args = array()){
            global $affiliatepress_tbl_ap_affiliate_commissions;            
            $affiliatepress_commission_exists = false;
            $reference = (isset($affiliatepress_args['reference']))?$affiliatepress_args['reference']:'';
            $affiliatepress_origin    = (isset($affiliatepress_args['origin']))?$affiliatepress_args['origin']:'';
            $affiliatepress_commission_id = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'ap_commission_id', 'WHERE ap_commission_source = %s AND ap_commission_reference_id = %s', array( $affiliatepress_origin, $reference ), '', '', '', true, false,ARRAY_A));
            if($affiliatepress_commission_id != 0){
                $affiliatepress_commission_exists = true;
            }
            return $affiliatepress_commission_exists;

        }

        /**
         * Function for get referal affiliate ID
         *
         * @return string
        */
        function affiliatepress_get_referral_affiliate($source = '', $order_data = '' ){            
            $affiliatepress_get_cookie = '';
            if(isset($_COOKIE['affiliatepress_ref_cookie']) && !empty($_COOKIE['affiliatepress_ref_cookie'])) 
            {
                $affiliatepress_get_cookie = absint( $_COOKIE['affiliatepress_ref_cookie'] );
            }
            else if( !empty( $source ) )
            {
                $affiliatepress_get_cookie = apply_filters( 'affiliatepress_get_affiliate_cookie_'.$source, $affiliatepress_get_cookie,$order_data,'affiliate');
            }
            
            return $affiliatepress_get_cookie;
        } 

        /**
         * Function for get referal affiliate visit id
         *
         * @return void
        */
        function affiliatepress_get_referral_visit( $source = '', $order_data = '' ){
            $affiliatepress_get_visit_id = '';
            if(isset($_COOKIE['affiliatepress_visitor_id']) && !empty($_COOKIE['affiliatepress_visitor_id']))
            {
                $affiliatepress_get_visit_id = absint( $_COOKIE['affiliatepress_visitor_id'] );
            }
            else if( !empty( $source ) )
            {
                $affiliatepress_get_visit_id = apply_filters( 'affiliatepress_get_affiliate_cookie_'.$source, $affiliatepress_get_visit_id,$order_data,'visit');
            }
            
            return $affiliatepress_get_visit_id;
        }         

        /**
         * Function for set init data for tracking cookie day
         *
         * @return void
        */
        function affiliatepress_set_init_data(){
            global $AffiliatePress,$affiliatepress_max_tracking_cookie_days;
            $affiliatepress_tracking_cookie_days  = intval($AffiliatePress->affiliatepress_get_settings('tracking_cookie_days', 'affiliate_settings'));
            if($affiliatepress_tracking_cookie_days == 0){
                $affiliatepress_tracking_cookie_days = $affiliatepress_max_tracking_cookie_days;
            }
            $this->affiliatepress_expiration_time = time()+(60 * (60 * (24 * $affiliatepress_tracking_cookie_days)));
            $this->referral_var    = $AffiliatePress->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');
        }
        
        /**
         * Function Current URL 
         *
         * @return string
        */
        function affiliatepress_get_current_url() {
            $affiliatepress_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $affiliatepress_host = sanitize_text_field($_SERVER['HTTP_HOST']); // phpcs:ignore
            $request_uri = $_SERVER['REQUEST_URI']; // phpcs:ignore
            return sanitize_url($affiliatepress_protocol . '://' . $affiliatepress_host . $request_uri);
        }        
        

        /**
         * Common Function for set affiliatepress cookie 
         *
         * @return void
        */
        function affiliatepress_set_ref_in_cookie(){
            global $wp,$wpdb,$wp_query,$affiliatepress_affiliates,$AffiliatePress,$affiliatepress_tbl_ap_affiliate_visits;
            $this->affiliatepress_set_init_data(); /* Set tracking cookied days & default data  */      
            if(!empty($this->referral_var)){                
                $referral_var = $this->referral_var;
                $affiliatepress_ref_affiliate = isset($_REQUEST[$referral_var]) ? sanitize_text_field($_REQUEST[$referral_var]) : ''; //phpcs:ignore
                if($affiliatepress_ref_affiliate == ''){
                    $affiliatepress_ref_affiliate = $wp_query->get($referral_var);
                }                
                if(empty($affiliatepress_ref_affiliate)){  
                    $affiliatepress_enable_fancy_affiliate_url = $AffiliatePress->affiliatepress_get_settings('enable_fancy_affiliate_url' , 'affiliate_settings');                
                    $affiliatepress_final_variable = $wp->request;
                    if(!empty($affiliatepress_final_variable) && $affiliatepress_enable_fancy_affiliate_url == "true"){
                        $affiliatepress_final_variable = 'abc/'.$affiliatepress_final_variable;                        
                        if(strpos($affiliatepress_final_variable,$referral_var.'/')){
                            $affiliatepress_track_arr = explode($referral_var.'/',$affiliatepress_final_variable);
                            if(isset($affiliatepress_track_arr[1]) && !empty($affiliatepress_track_arr[1])){
                                $affiliatepress_ref_affiliate = $affiliatepress_track_arr[1];                                
                            }
                        }
                    }
                } /* Function for get affiliate id encode value from URL using variable */

                $affiliatepress_ref_affiliate = $AffiliatePress->affiliatepress_decode_affiliate_id($affiliatepress_ref_affiliate); /* Function for decode affiliate ID */

                $affiliatepress_ref_affiliate = apply_filters('affiliatepess_affiliate_cookie_set', $affiliatepress_ref_affiliate);//phpcs:ignore

                $affiliatepress_allow_second_referal = false;
                /* Filter for allow last referal */
                $affiliatepress_allow_second_referal = apply_filters('affiliatepess_allow_second_referal', $affiliatepress_allow_second_referal, $affiliatepress_ref_affiliate); //phpcs:ignore 

                $affiliatepress_last_referal_allowed = (isset($_COOKIE['affiliatepress_ref_cookie']) && !empty($_COOKIE['affiliatepress_ref_cookie']) && $affiliatepress_allow_second_referal == true)?true:false;

                if(!empty($affiliatepress_ref_affiliate) && (!isset($_COOKIE['affiliatepress_ref_cookie']) || $affiliatepress_last_referal_allowed) && $affiliatepress_affiliates->affiliatepress_is_valid_affiliate($affiliatepress_ref_affiliate)){

                    $affiliatepress_cookie_value    = $affiliatepress_ref_affiliate;
                    $affiliatepress_cookie_name     = 'affiliatepress_ref_cookie';
                    $affiliatepress_cookie_exp_time = $this->affiliatepress_expiration_time;
                    
                    setcookie($affiliatepress_cookie_name, $affiliatepress_cookie_value, $affiliatepress_cookie_exp_time, '/'); /* Set Affiliate Cookie */

                    $affiliatepress_referralUrl = (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']))?sanitize_url($_SERVER['HTTP_REFERER']):''; // phpcs:ignore

                    $affiliatepress_campaign = isset($_REQUEST['campaign']) ? sanitize_text_field($_REQUEST['campaign']) : '';// phpcs:ignore
                    $affiliatepress_sub_id = isset($_REQUEST['sub_id']) ? sanitize_text_field($_REQUEST['sub_id']) : '';// phpcs:ignore
                                                            
                    /* Visit Data Insert */
                    $affiliatepress_inserted_visit_id = $this->affiliatepress_insert_visit(
                        $affiliatepress_cookie_value,
                        array(
                            'referrer' => $affiliatepress_referralUrl,
                            'campaign' => $affiliatepress_campaign,
                            'sub_id'   => $affiliatepress_sub_id
                        )
                    );

                    setcookie('affiliatepress_visitor_id', $affiliatepress_inserted_visit_id, $affiliatepress_cookie_exp_time, '/');


                }

            }
        }

        function affiliatepress_insert_visit( $aff_id, $data = array() ) {
            global $wpdb, $affiliatepress_tbl_ap_affiliate_visits, $AffiliatePress;
        
            $ip_address  = !empty($data['ip_address']) ? sanitize_text_field($data['ip_address']) : $AffiliatePress->affiliatepress_get_ip_address();
            $user_agent  = !empty($data['browser_user_agent']) ? sanitize_text_field($data['browser_user_agent']) : sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
        
            $browser_data = $AffiliatePress->affiliatepress_get_browser($user_agent);
            $browser_name = isset($browser_data['name']) ? $browser_data['name'] : '';
            $browser_ver  = isset($browser_data['version']) ? $browser_data['version'] : '';
            $browser      = $browser_name . ' (' . $browser_ver . ')';
        
            $country_details = $AffiliatePress->affiliatepress_get_country_from_ip($ip_address);
            $country         = isset($country_details['country_name']) ? sanitize_text_field($country_details['country_name']) : '';
            $iso_code        = isset($country_details['iso_code']) ? sanitize_text_field($country_details['iso_code']) : '';
        
            $args = array(
                'ap_affiliates_id'      => intval($aff_id),
                'ap_visit_created_date' => date('Y-m-d H:i:s', current_time('timestamp')),
                'ap_visit_ip_address'   => $ip_address,
                'ap_visit_country'      => $country,
                'ap_visit_iso_code'     => $iso_code,
                'ap_visit_browser'      => $browser,
                'ap_visit_landing_url'  => !empty($data['url']) ? sanitize_url($data['url']) : $this->affiliatepress_get_current_url(),
                'ap_referrer_url'       => !empty($data['referrer']) ? sanitize_url($data['referrer']) : '',
            );
        
            if(!empty($data['campaign'])){
                $args['ap_affiliates_campaign_name'] = sanitize_text_field($data['campaign']);
        
                if(!empty($data['sub_id'])){
                    $args['ap_affiliates_sub_id'] = sanitize_text_field($data['sub_id']);
                }
            }
        
            $wpdb->insert($affiliatepress_tbl_ap_affiliate_visits, $args);
        
            $visit_id = $wpdb->insert_id;
        
            do_action('affiliatepress_after_visit_insert', $visit_id, $args);
        
            return $visit_id;
        }
        
        /**
         * Common function for get default commission status
         *
         * @return integer
        */
        function affiliatepress_get_default_commission_status(){
            global $AffiliatePress;

            if(!$AffiliatePress->affiliatepress_pro_install()){
                $affiliatepress_commission_status = "2";
            }
	    else {
	    	$affiliatepress_commission_status = $AffiliatePress->affiliatepress_get_settings('default_commission_status', 'commissions_settings');
	    }

            return $affiliatepress_commission_status;
        }

        /**
         * Common function for get minimum payout order
         *
         * @return integer
        */
        function affiliatepress_get_payout_minimum_payment_order(){
            global $AffiliatePress;

            if(!$AffiliatePress->affiliatepress_pro_install()){
                $affiliatepress_minimum_payment_order = 1;
            }
            else {
                $affiliatepress_minimum_payment_order = $AffiliatePress->affiliatepress_get_settings('minimum_payment_order', 'commissions_settings');
            }

            return $affiliatepress_minimum_payment_order;
        }

         /**
         * affiliatepress get default commission rate
         *
         * @return integer
        */
        function affiliatepress_get_default_commission_rate_func($affiliatepress_current_rule , $affiliatepress_affiliate_id){
            
            global $AffiliatePress;
            
            $affiliatepress_default_discount_val = $AffiliatePress->affiliatepress_get_settings('default_discount_val', 'commissions_settings');
            $affiliatepress_default_discount_type = $AffiliatePress->affiliatepress_get_settings('default_discount_type', 'commissions_settings');

            $affiliatepress_current_rule = array(
                'discount_value' => $affiliatepress_default_discount_val,
                'discount_type' => $affiliatepress_default_discount_type,
                'discount_label' => esc_html__('Default Commission Rate', 'affiliatepress-affiliate-marketing'),
            );   
            
            return $affiliatepress_current_rule;
        }

        function affiliatepress_integration_list($affiliatepress_integration_type){

            global $AffiliatePress;

            $affiliatepress_active_integration = array();
            $affiliatepress_inactive_integration = array();

            $affiliatepress_integrations = array('enable_woocommerce','enable_armember','enable_easy_digital_downloads', 'enable_bookingpress', 'enable_memberpress','enable_surecart', 'enable_restrict_content','enable_wp_easycart','enable_lifter_lms', 'enable_arforms', 'enable_give_wp', 'enable_simple_membership', 'enable_paid_memberships_pro','enable_paid_memberships_subscriptions','enable_ultimate_membership_pro','enable_ninjaforms','enable_wp_forms', 'enable_gravity_forms','enable_wp_simple_pay','enable_masteriyo_lms','enable_getpaid','enable_learnpress', 'enable_accept_stripe_payments','enable_download_manager','enable_learndash' );

            $affiliatepress_integrations = apply_filters( 'affiliatepress_add_integration_list', $affiliatepress_integrations );

            foreach ($affiliatepress_integrations as $affiliatepress_integration) {
                if ($AffiliatePress->affiliatepress_get_settings($affiliatepress_integration, 'integrations_settings') === 'true') {
                   $affiliatepress_active_integration[] = $affiliatepress_integration;
                }else{
                    $affiliatepress_inactive_integration[] = $affiliatepress_integration;
                }
            }

            if($affiliatepress_integration_type == "active"){
                return $affiliatepress_active_integration;
            }

            if($affiliatepress_integration_type == "inactive"){
                return $affiliatepress_inactive_integration;
            }
        }

                
        /**
         * Common function for check product wise commission disable or not (This function use in all integration)
         *
         * @param  array $affiliatepress_product
         * @return boolean
        */
        function affiliatepress_check_product_disabled( $affiliatepress_product){

            global $affiliatepress_commission_debug_log_id,$wpdb,$affiliatepress_give;
            $affiliatepress_prodcut_id = isset($affiliatepress_product['product_id']) ? intval($affiliatepress_product['product_id']) : 0;
            $affiliatepress_source = isset($affiliatepress_product['source']) ? sanitize_text_field($affiliatepress_product['source']) : '';

            $affiliatepress_product_disable = false;
            if($affiliatepress_source == "woocommerce"){/* Check Disable Product For WooCommerce same for all other integration condition below */
                $affiliatepress_woocommerce_disable= get_post_meta( $affiliatepress_prodcut_id,'affiliatepress_disable_commissions_woocommerce',true);

                if($affiliatepress_woocommerce_disable){
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_woocommerce' , $affiliatepress_product_disable , $affiliatepress_product);
                /* Filter for allow product addon same for all other integration condition below */

            }elseif($affiliatepress_source == "armember"){

                $affiliatepress_arm_subscription_plans = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'arm_subscription_plans' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arm_subscription_plans' contains table name and it's prepare properly using 'arm_payment_log' function

                $affiliatepress_plan_options = $wpdb->get_var($wpdb->prepare("SELECT arm_subscription_plan_options FROM {$affiliatepress_arm_subscription_plans} WHERE arm_subscription_plan_id= %d",$affiliatepress_prodcut_id));// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_arm_subscription_plans is a table name. false alarm

                $affiliatepress_plan_options = unserialize($affiliatepress_plan_options);

                $affiliatepress_disable_option = !empty($affiliatepress_plan_options['affiliatepress_commission_disable_armember']) ? intval($affiliatepress_plan_options['affiliatepress_commission_disable_armember']) : 0;

                if($affiliatepress_disable_option == "1")
                {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_armember' , $affiliatepress_product_disable , $affiliatepress_product);

            }elseif($affiliatepress_source == "easy_digital_downloads"){

                $affiliatepress_commission_disable_edd = get_post_meta($affiliatepress_prodcut_id, 'affiliatepress_commission_disable_edd', true );

                if($affiliatepress_commission_disable_edd == 1)
                {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_easy_digital_downloads' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "memberpress") {

                $affiliatepress_disable_option_memberpress = get_post_meta( $affiliatepress_prodcut_id, 'affiliatepress_commission_disable_memberpress', true );

                if($affiliatepress_disable_option_memberpress){
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_memberpress' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "restrict_content") {

                $affiliatepress_disable_option_restrict_content = rcp_get_membership_level_meta( $affiliatepress_prodcut_id, 'affiliatepress_commission_disable_restrict_content', true );

                if($affiliatepress_disable_option_restrict_content){
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_restrict_content' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "wp_easycart") {
                $affiliatepress_disable_commission_easycart = get_option( 'affiliatepress_disable_commission_easycart_'.$affiliatepress_prodcut_id );

                if ($affiliatepress_disable_commission_easycart) {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_wp_easycart' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "lifter_lms") {
                $affiliatepress_disable_commission_lifterlms = get_post_meta( $affiliatepress_prodcut_id, 'affiliatepress_commission_disable_lifterlms', true );

                if ($affiliatepress_disable_commission_lifterlms) {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_liftre_lms' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "give_wp") {
                
                $affiliatepress_tbl_give_formmeta_meta = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'give_formmeta' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'give_formmeta' contains table name and it's prepare properly using 'arm_payment_log' function

                $affiliatepress_disable_commission = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_give_formmeta_meta, 'meta_value', 'WHERE form_id = %d AND meta_key = %s ', array($affiliatepress_prodcut_id,'affiliatepress_give_disable_commission'), '', '', '', false, true,'');

                $affiliatepress_disable_commission = !empty($affiliatepress_disable_commission) ? $affiliatepress_disable_commission->meta_value : '';
                
                if($affiliatepress_disable_commission !== "on") 
                {
                    $affiliatepress_product_disable = true;
                }

                if($affiliatepress_give->affiliatepress_check_givewp_version()){
                    if(!$affiliatepress_disable_commission){
                        $affiliatepress_product_disable = true;
                    }else{
                        $affiliatepress_product_disable = false;
                    }
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_give_wp' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "simple_membership") {
                $affiliatepress_disable_commission = get_option('disable_affiliatepress_simple_membership_commission_' . $affiliatepress_prodcut_id, 0);

                if($affiliatepress_disable_commission == 1)
                {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_simple_membership' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "paid_memberships_pro") {
                $affiliatepress_level_settings = get_option( "affiliatepress_pmp_settings_{$affiliatepress_prodcut_id}", array() );
                if ( isset($affiliatepress_level_settings['is_disabled']) && !empty( $affiliatepress_level_settings['is_disabled'] ) ) {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_paid_memberships_pro' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "paid_memberships_subscriptions") {
                $affiliatepress_pms_disable_option = get_post_meta( $affiliatepress_prodcut_id, 'affiliatepress_pms_disable_commissions', true );

                if($affiliatepress_pms_disable_option){
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_paid_memberships_subscriptions' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "ultimate_membership_pro") {

                $affiliatepress_tbl_wp_ihc_memberships_meta_pro = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'ihc_memberships_meta' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'ihc_memberships_meta' contains table name and it's prepare properly using 'arm_payment_log' function

                $affiliatepress_ump_meta_key = 'affiliatepress_ump_disable_commission';

                $affiliatepress_disable_option = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_wp_ihc_memberships_meta_pro, 'meta_value', 'WHERE membership_id = %d AND meta_key = %s ', array($affiliatepress_prodcut_id,'affiliatepress_ump_disable_commission'), '', '', '', false, true,'');

                $affiliatepress_disable_option = $affiliatepress_disable_option->meta_value;

                if($affiliatepress_disable_option == 1)
                {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_ultimate_membership_pro' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "wp_forms") {

                $affiliatepress_disable_option = isset($affiliatepress_product['affiliatepress_wp_forms_settings'] ) ? intval($affiliatepress_product['affiliatepress_wp_forms_settings'] ) : 0;
                
                if ( !$affiliatepress_disable_option) {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_wp_forms' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "gravity_forms") {
                $affiliatepress_disable_option = isset($affiliatepress_product['affiliatepress_gravity_forms_settings']) ? sanitize_text_field($affiliatepress_product['affiliatepress_gravity_forms_settings']) : '';

                if($affiliatepress_disable_option != "yes"){
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_gravity_forms' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "wp_simple_pay") {
                $affiliatepres_commission_disable = get_post_meta($affiliatepress_prodcut_id, 'affiliatepress_commission_disable_simple_pay', true );

                if($affiliatepres_commission_disable == "no")
                {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_wp_simple_pay' , $affiliatepress_product_disable , $affiliatepress_product);

            }
            elseif ($affiliatepress_source == "getpaid") {
                $affiliatepress_commission_disable_getpaid = get_post_meta( $affiliatepress_prodcut_id,'affiliatepress_commission_disable_getpaid',true);
                if($affiliatepress_commission_disable_getpaid){
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_getpaid' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "accept_stripe_payments") {
               $affiliatepres_commission_disable = get_post_meta($affiliatepress_prodcut_id, 'affiliatepress_disable_commission_accept_stripe_payments', true );

                if($affiliatepres_commission_disable == 1)
                {
                    $affiliatepress_product_disable = true;
                }
                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_accept_stripe_payments' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "arforms") {
                $affiliatepress_tbl_arf_forms = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'arf_forms' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arf_forms' contains table name and it's prepare properly using 'arm_payment_log' function

                $affiliatepress_form_options = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_arf_forms, 'options', 'WHERE id = %d ', array($affiliatepress_prodcut_id), '', '', '', false, true,'');
                $affiliatepress_form_options = $affiliatepress_form_options->options;
                $affiliatepress_form_options = unserialize($affiliatepress_form_options);

                $affiliatepress_enable_commission = isset($affiliatepress_form_options['arf_affiliatepress_enable_commission']) ? intval($affiliatepress_form_options['arf_affiliatepress_enable_commission']) : 0;

                if(!$affiliatepress_enable_commission)
                {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_arforms' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "learnpress") {
                $affiliatepres_commission_disable = get_post_meta($affiliatepress_prodcut_id, 'affiliatepress_disable_commission_learnpress', true );

                if($affiliatepres_commission_disable == "yes")
                {
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_learnpress' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif($affiliatepress_source == "ninjaforms"){
                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_ninja_forms' , $affiliatepress_product_disable , $affiliatepress_product);
            }elseif ($affiliatepress_source == "download_manager") {
                $affiliatepres_commission_disable = get_post_meta($affiliatepress_prodcut_id, '__wpdm_affiliatepress_downloads_manager_disable_commission', true );

                if($affiliatepres_commission_disable == "1"){
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_download_manager' , $affiliatepress_product_disable , $affiliatepress_product);
            }
            elseif ($affiliatepress_source == "bookingpress") {
                $affiliatepress_tbl_bookingpress_servicesmeta = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'bookingpress_servicesmeta' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'bookingpress_servicesmeta' contains table name and it's prepare properly using 'arm_payment_log' function

                $affiliatepress_meta_key = 'affiliatepress_enable_commission';
                $affiliatepress_disable_option = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_bookingpress_servicesmeta, 'bookingpress_servicemeta_value', 'WHERE bookingpress_service_id = %d AND bookingpress_servicemeta_name = %s', array($affiliatepress_prodcut_id , $affiliatepress_meta_key), '', '', '', false, true,'');

                if($affiliatepress_disable_option->bookingpress_servicemeta_value == "false"){
                    $affiliatepress_product_disable = true;
                }

                $affiliatepress_product_disable = apply_filters('affiliatepress_allowed_product_bookingpress' , $affiliatepress_product_disable , $affiliatepress_product);
            }

            if($affiliatepress_product_disable){ /* Insert Log For Disable Product */
                do_action('affiliatepress_commission_debug_log_entry', 'commission_tracking_debug_logs', $affiliatepress_source.' : Not Allowed Product', 'affiliatepress_'.$affiliatepress_source.'_commission_tracking', $affiliatepress_prodcut_id . ' this product id for not add commission', $affiliatepress_commission_debug_log_id);
            }

            return $affiliatepress_product_disable;
        }

    }
}
global $affiliatepress_tracking;
$affiliatepress_tracking = new affiliatepress_tracking();