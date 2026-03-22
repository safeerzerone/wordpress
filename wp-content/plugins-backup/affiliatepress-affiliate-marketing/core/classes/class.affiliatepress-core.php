<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('AffiliatePress_Core') ){
    
    /**
     * AffiliatePress_Core
    */
    class AffiliatePress_Core{
        
        var $affiliatepress_action_name;
        var $affiliatepress_nonce_action;
        var $affiliatepress_nonce_field;
        protected static $checksum;

        public function load(){
            self::$checksum = base64_encode( get_option( 'afp_license_key' ) );
        }

        /**
         * Check ajax call authentication 
         *
         * @param  string $affiliatepress_action_name
         * @param  boolean $affiliatepress_check_nonce
         * @param  string $affiliatepress_nonce_action
         * @return string
        */
        protected function affiliatepress_ap_check_authentication( $affiliatepress_action_name = '', $affiliatepress_check_nonce = false, $affiliatepress_nonce_action = '' ){

            if( '' == $affiliatepress_action_name && is_user_logged_in() ){
                return 'error^|^' . esc_html__( "Sorry! You do not have enough permission to perform this action", "affiliatepress-affiliate-marketing");
            }
            $this->affiliatepress_action_name = $affiliatepress_action_name;
            $affiliatepress_has_capability_for_action = $this->affiliatepress_ap_retrieve_capabilities();
                                    
            if( false == $affiliatepress_has_capability_for_action ){
                return 'error^|^' . esc_html__( "Sorry! You do not have enough permission to perform this action", "affiliatepress-affiliate-marketing");
            }
            if( $affiliatepress_check_nonce ){
                $this->affiliatepress_nonce_action = $affiliatepress_nonce_action;
                $this->affiliatepress_nonce_field = !empty( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash($_REQUEST['_wpnonce']) ) : ''; // phpcs:ignore
                $affiliatepress_valid_nonce = $this->affiliatepress_ap_check_nonce();

                if( false == $affiliatepress_valid_nonce ){
                    return 'error^|^' . esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                }
            }
            return 'success';

        }
             
                    
        /**
         * Function for table name prepare argument
         *
         * @param  string $affiliatepress_table_name
         * @return string
        */
        protected function affiliatepress_tablename_prepare($affiliatepress_table_name){
            global $wp_version, $wpdb;
            $affiliatepress_table_name = str_replace('`','',$affiliatepress_table_name);
            $affiliatepress_version = str_replace( '-src', '', $wp_version );
            if ( version_compare( $affiliatepress_version, '6.2.0', '>=' ) ) {
                $affiliatepress_table_name = $wpdb->prepare( "%i", $affiliatepress_table_name);
            }
            return $affiliatepress_table_name;
        }    

        /**
         * Common function for select query
         *
         * @param  boolean $affiliatepress_default_query
         * @param  string $affiliatepress_custom_query
         * @param  string $affiliatepress_tablename
         * @param  string $affiliatepress_fields
         * @param  string $affiliatepress_where
         * @param  array $affiliatepress_params
         * @param  string $affiliatepress_groupby
         * @param  string $affiliatepress_orderby
         * @param  boolean $affiliatepress_limit
         * @param  boolean $affiliatepress_isCounter
         * @param  boolean $affiliatepress_single
         * @param  string $return_type
         * @return mixed
        */
        protected function affiliatepress_select_record( $affiliatepress_default_query = true, $affiliatepress_custom_query = '', $affiliatepress_tablename = '', $affiliatepress_fields = '*', $affiliatepress_where = '', $affiliatepress_params = array(), $affiliatepress_groupby = '', $affiliatepress_orderby = '', $affiliatepress_limit = '', $affiliatepress_isCounter = false, $affiliatepress_single = false, $return_type = 'OBJECT' ){
            global $wpdb;
            if( '' == $return_type ){
                $return_type = 'OBJECT';
            }
            if( $affiliatepress_default_query ){
                if( '' == $affiliatepress_tablename ){
                    return false;
                }
                $affiliatepress_tablename = $this->affiliatepress_tablename_prepare($affiliatepress_tablename);
                $affiliatepress_fields = apply_filters( 'affiliatepress_modify_query_fields', $affiliatepress_fields, $affiliatepress_tablename );
                $affiliatepress_where = apply_filters( 'affiliatepress_modify_where_clause', $affiliatepress_where, $affiliatepress_tablename );
                $affiliatepress_params = apply_filters( 'affiliatepress_modify_where_placeholder', $affiliatepress_params, $affiliatepress_tablename, $affiliatepress_where );
                
                $affiliatepress_sel_query = "SELECT " . $affiliatepress_fields . " FROM " . $affiliatepress_tablename ." " . $affiliatepress_where . " " . $affiliatepress_groupby . " " . $affiliatepress_orderby . " " . $affiliatepress_limit;
                if( !empty( $affiliatepress_params ) ){
                    array_unshift( $affiliatepress_params , $affiliatepress_sel_query );
                    $affiliatepress_build_query = call_user_func_array( array( $wpdb, 'prepare' ) , $affiliatepress_params );
                } else {
                    $affiliatepress_build_query = $affiliatepress_sel_query;
                }
            } else {
                $affiliatepress_build_query = $affiliatepress_custom_query;
            }
            if( '' == $affiliatepress_build_query ){
                return false;
            }
            if( $affiliatepress_isCounter ){
                $affiliatepress_db_result = $wpdb->get_var( $affiliatepress_build_query ); //phpcs:ignore
            } else if( $affiliatepress_single ){
                $affiliatepress_db_result = $wpdb->get_row( $affiliatepress_build_query, $return_type ); //phpcs:ignore
            } else {
                $affiliatepress_db_result = $wpdb->get_results( $affiliatepress_build_query, $return_type ); //phpcs:ignore
            }
            return $affiliatepress_db_result;    
        }        

        
        /**
         * Function for common insert query
         *
         * @param  string $affiliatepress_table_name
         * @param  array $affiliatepress_data
         * @param  mixed $affiliatepress_format
         * @return integer
        */
        protected function affiliatepress_insert_record($affiliatepress_table_name,$affiliatepress_data,$affiliatepress_format = null) {
            global $wpdb;
            /* Insert the record */
            $affiliatepress_table_name = str_replace('`','',$affiliatepress_table_name);
            $wpdb->insert($affiliatepress_table_name, $affiliatepress_data, $affiliatepress_format);//phpcs:ignore
            $affiliatepress_insert_id = $wpdb->insert_id;
            return $affiliatepress_insert_id;
        }
        
        /**
         * Function for common delete table data
         *
         * @param  string $affiliatepress_table_name
         * @param  array $affiliatepress_where
         * @param  mixed $affiliatepress_where_format
         * @return void
        */
        protected function affiliatepress_delete_record($affiliatepress_table_name,$affiliatepress_where,$affiliatepress_where_format = null) {
            global $wpdb;
            /* Delete the record */ 
            $affiliatepress_table_name = str_replace('`','',$affiliatepress_table_name);           
            $wpdb->delete($affiliatepress_table_name, $affiliatepress_where, $affiliatepress_where_format); //phpcs:ignore          
        }

                
        /**
         * Function for common update query
         *
         * @param  string $affiliatepress_table_name
         * @param  array $affiliatepress_data
         * @param  string $affiliatepress_where
         * @param  mixed $affiliatepress_format
         * @param  mixed $affiliatepress_where_formate
         * @return void
         */
        protected function affiliatepress_update_record($affiliatepress_table_name,$affiliatepress_data,$affiliatepress_where,$affiliatepress_format = null ,$affiliatepress_where_formate = null) {
            global $wpdb;
            /* Update the record */
            $affiliatepress_table_name = str_replace('`','',$affiliatepress_table_name);
            $wpdb->update($affiliatepress_table_name,$affiliatepress_data,$affiliatepress_where,$affiliatepress_format,$affiliatepress_where_formate); //phpcs:ignore           
        }

        /**
         * Common function for add capability
         *
         * @return void
         */
        private function affiliatepress_ap_retrieve_capabilities(){

            if( '' == trim( $this->affiliatepress_action_name ) ){
                return false;
            }
            $affiliatepress_caps = array(
                'affiliatepress' => array(
                    'retrieve_dashboard_data'
                ),
                'affiliatepress_payout' => array(
                    'generate_preview',
                    'generate_payout',
                    'retrieve_payout',
                    'delete_payout',                    
                    'edit_payout',
                    'payment_status_change',
                    'payment_note',      
                    'export_payout',                 
                ), 
                'affiliatepress_affiliates' => array(
                    'retrieve_affiliates',
                    'add_affiliate',
                    'edit_affiliate',
                    'change_affiliate_status',
                    'approve_affiliate',
                    'delete_affiliate',
                    'search_affiliate',
                    'search_user',
                    'affiliate_avatar_image_upload',
                    'remove_affiliate_avatar',
                    'export_affiliate',
                    'affiliate_upload_import_file',
                    'import_affiliate'
                ), 
                'affiliatepress_settings' => array(
                    'retrieve_settings',
                    'save_settings',
                    'save_affiliate_settings',
                    'send_test_mail',
                    'view_debug_payment_logs',
                    'download_debug_payment_logs',
                    'clear_debug_payment_logs' ,
                    'save_reset_appearance_color',
                    'reset_wizard',
                    'get_page_url'                                 
                ),
                'affiliatepress_creative' => array(
                    'retrieve_creative',
                    'add_creative',
                    'edit_creative',
                    'change_creative_status',                    
                    'delete_creative',
                    'search_creative',
                    'affiliate_creative_image_upload',
                    'remove_creative_image'                                                          
                ), 
                'affiliatepress_commissions' => array(
                    'retrieve_commissions',
                    'search_commissions', 
                    'change_commissions_status', 
                    'delete_commissions',
                    'search_affiliate_user',
                    'search_source_product',
                    'add_commission',
                    'edit_commission',        
                    'show_commisison_details'        
                ),
                'affiliatepress_visits' => array(
                    'retrieve_visits',
                    'search_visits',                    
                ),
                'affiliatepress_notifications' => array(
                    'save_email_notification',
                    'retrieve_email_notification',
                    'retrieve_email_notification_status',
                    'remove_google_api_account'
                ),                                                                               
                'affiliatepress_affiliate_fields' => array(
                    'save_form_fields',
                    'retrieve_form_fields',
                    'update_field_position',
                    'save_form_settings'
                ),   
                'affiliatepress_growth_tools' => array(
                    'retrieve_plugin'
                ),   
                'affiliatepress_addons'=>array(
                    'retrieve_addon'
                ),
            );

            $affiliatepress_caps = apply_filters( 'affiliatepress_modify_capability_data', $affiliatepress_caps );            
            if( empty( $affiliatepress_caps ) ){
                return false;
            }
            $affiliatepress_user_cap = false;
            foreach( $affiliatepress_caps as $affiliatepress_capability => $affiliatepress_action ){
                if( !empty( $affiliatepress_action ) && in_array( $this->affiliatepress_action_name, $affiliatepress_action ) && current_user_can( $affiliatepress_capability ) ){
                    $affiliatepress_user_cap = true;
                    break;
                }
            }

            return $affiliatepress_user_cap;

        }
        
        /**
         * Function for check nonce
         *
         * @return boolean
         */
        private function affiliatepress_ap_check_nonce(){
            if( empty( $this->affiliatepress_nonce_action ) || empty( $this->affiliatepress_nonce_field )){
                return false;
            }
            return wp_verify_nonce( $this->affiliatepress_nonce_field, $this->affiliatepress_nonce_action );
        }
        
        
        /**
         * Function for type cast
         *
         * @param  mixed $affiliatepress_data_array
         * @return mixed
        */
        public function affiliatepress_boolean_type_cast( $affiliatepress_data_array ) {

            if (is_array($affiliatepress_data_array) ) {
                return array_map(array( $this, __FUNCTION__ ), $affiliatepress_data_array);
            } else {
                if(gettype($affiliatepress_data_array) == 'boolean') {
                    if($affiliatepress_data_array == true) {
                        $affiliatepress_data_array = 'true';
                    } else {
                        $affiliatepress_data_array = 'false';
                    }
			        return $affiliatepress_data_array;
                } else {
                    return $affiliatepress_data_array;
                }
            }

        }

        



    }
}