<?php
if(!defined('ABSPATH')){ exit; }

if (! class_exists('affiliatepress_affiliate_fields') ) {
    class affiliatepress_affiliate_fields Extends AffiliatePress_Core{
        
        var $affiliatepress_per_page_record;

        function __construct(){
            
            $this->affiliatepress_per_page_record = 10;

            //Function for affiliate fields vue data
            add_action( 'admin_init', array( $this, 'affiliatepress_affiliate_fields_vue_data_fields') );

            /* Dynamic Constant */
            add_filter('affiliatepress_affiliate_fields_dynamic_constant_define',array($this,'affiliatepress_affiliate_fields_dynamic_constant_define_func'),10,1);
            
            /* Dynamic Vue Fields */
            add_filter('affiliatepress_affiliate_fields_dynamic_data_fields',array($this,'affiliatepress_affiliate_fields_dynamic_data_fields_func'),10,1);

            /* Vue Load */
            add_action('affiliatepress_affiliate_fields_dynamic_view_load', array( $this, 'affiliatepress_affiliate_fields_dynamic_view_load_func' ), 10);

            /* Vue Method */
            add_filter('affiliatepress_affiliate_fields_dynamic_vue_methods',array($this,'affiliatepress_affiliate_fields_dynamic_vue_methods_func'),10,1);

            /* Vue Component Method */
            add_filter('affiliatepress_affiliate_fields_dynamic_components',array($this,'affiliatepress_affiliate_fields_dynamic_components_func'),10,1);

            /* Dynamic On Load Method */
            add_filter('affiliatepress_affiliate_fields_dynamic_on_load_methods', array( $this, 'affiliatepress_affiliate_fields_dynamic_on_load_methods_func' ), 10);

            /* Function for get affiliate form fields */
            add_action('wp_ajax_affiliatepress_load_affiliate_fields', array( $this, 'affiliatepress_load_affiliate_fields_func' ));

            /* Function for store affiliate form fields */
            add_action('wp_ajax_affiliatepress_save_field_settings', array( $this, 'affiliatepress_save_field_settings_data_func' ),20);

            /* Function for update field position */
            add_action('wp_ajax_affiliatepress_update_field_position', array( $this, 'affiliatepress_update_field_position_func' ),10);

        }
                
        /**
         * Function for add dynamic component add
         *
         * @param  string $affiliatepress_affiliate_fields_dynamic_components
         * @return string
         */
        function affiliatepress_affiliate_fields_dynamic_components_func($affiliatepress_affiliate_fields_dynamic_components){
            $affiliatepress_affiliate_fields_dynamic_components.='
                draggable: window.vuedraggable 
            '; 
            return $affiliatepress_affiliate_fields_dynamic_components;
        }
                       
        /**
         * Function for update field position
         *
         * @return json
        */
        function affiliatepress_update_field_position_func(){
            global $wpdb,$affiliatepress_tbl_ap_affiliate_form_fields,$AffiliatePress;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'update_field_position', true, 'ap_wp_nonce' );            
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
        
            if(!current_user_can('affiliatepress_affiliate_fields')){
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

            $response['variant'] = 'error';
            $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing');
        
            $affiliatepress_old_index = isset($_POST['old_index']) ? ( intval($_POST['old_index']) + 1 ) : 0; // phpcs:ignore 
            $affiliatepress_new_index = isset($_POST['new_index']) ? ( intval($_POST['new_index']) + 1 ) : 0; // phpcs:ignore 
            $affiliatepress_update_id = ! empty($_POST['update_id']) ? intval($_POST['update_id']) : 0; // phpcs:ignore 
        
            $affiliatepress_tbl_ap_affiliate_form_fields_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_form_fields); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_fields    = $wpdb->get_results("SELECT * FROM {$affiliatepress_tbl_ap_affiliate_form_fields_temp} order by ap_field_position ASC", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery ,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_form_fields_temp is table name already prepare in "affiliatepress_tablename_prepare". False Positive alarm
            $affiliatepress_i = 1;
            foreach ( $affiliatepress_fields as $affiliatepress_field ) {
                $affiliatepress_args = array('ap_field_position' => $affiliatepress_i);
                $wpdb->update($affiliatepress_tbl_ap_affiliate_form_fields, $affiliatepress_args, array( 'ap_form_field_id' => $affiliatepress_field['ap_form_field_id'] ));// phpcs:ignore WordPress.DB.DirectDatabaseQuery
               $affiliatepress_i++;     
            }
            if (isset($_POST['old_index']) && isset($_POST['new_index']) ) { // phpcs:ignore 
                if ($affiliatepress_new_index > $affiliatepress_old_index ) {
                    $affiliatepress_condition = 'BETWEEN ' . $affiliatepress_old_index . ' AND ' . $affiliatepress_new_index;
                    $affiliatepress_fields    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$affiliatepress_tbl_ap_affiliate_form_fields_temp} WHERE ap_field_position BETWEEN %d AND %d order by ap_field_position ASC", $affiliatepress_old_index, $affiliatepress_new_index ), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery ,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_form_fields_temp is table name defined globally & already prepare by affiliatepress_tablename_prepare. False Positive alarm
                    foreach ( $affiliatepress_fields as $affiliatepress_field ) {
                        $affiliatepress_position = $affiliatepress_field['ap_field_position'] - 1;
                        $affiliatepress_position = ( $affiliatepress_field['ap_field_position'] == $affiliatepress_old_index ) ? $affiliatepress_new_index : $affiliatepress_position;
                        $affiliatepress_args     = array(
                            'ap_field_position' => $affiliatepress_position,
                        );
                        $wpdb->update($affiliatepress_tbl_ap_affiliate_form_fields, $affiliatepress_args, array( 'ap_form_field_id' => $affiliatepress_field['ap_form_field_id'] )); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    }
                } else {
                    $affiliatepress_fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$affiliatepress_tbl_ap_affiliate_form_fields_temp} WHERE ap_field_position BETWEEN %d AND %d order by ap_field_position ASC", $affiliatepress_new_index, $affiliatepress_old_index ), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery ,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_form_fields_temp is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm
                    foreach ( $affiliatepress_fields as $affiliatepress_field ) {
                        $affiliatepress_position = $affiliatepress_field['ap_field_position'] + 1;
                        $affiliatepress_position = ( $affiliatepress_field['ap_field_position'] == $affiliatepress_old_index ) ? $affiliatepress_new_index : $affiliatepress_position;
                        $affiliatepress_args     = array(
                             'ap_field_position' => $affiliatepress_position,
                        );
                        $wpdb->update($affiliatepress_tbl_ap_affiliate_form_fields, $affiliatepress_args, array( 'ap_form_field_id' => $affiliatepress_field['ap_form_field_id'] ));// phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    }
                }
                $response['variant'] = 'success';
                $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Field position has been changed successfully.', 'affiliatepress-affiliate-marketing');
            }
        
            echo wp_json_encode($response);
            exit();            
                        
        }            

        /**
         * Function for save fields data 
         *
         * @return json
        */
        function affiliatepress_save_field_settings_data_func(){
            
            global $wpdb,$affiliatepress_tbl_ap_affiliate_form_fields,$AffiliatePress;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'save_form_fields', true, 'ap_wp_nonce' ); // phpcs:ignore 
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

            if(!current_user_can('affiliatepress_affiliate_fields')){
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

            $response['variant'] = 'error';
            $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing');

            if( !empty( $_POST['field_settings'] ) && !is_array( $_POST['field_settings'] ) ){ //phpcs:ignore
                $_POST['field_settings'] = json_decode( stripslashes_deep( $_POST['field_settings'] ), true ); //phpcs:ignore
				$_POST['field_settings'] = $this->affiliatepress_boolean_type_cast( $_POST['field_settings'] ); //phpcs:ignore
            }
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $_REQUEST['field_settings'] contains mixed array and it's been sanitized properly using 'affiliatepress_sanitize_affiliatepress_fields' function
            $affiliatepress_field_settings_data = ! empty($_POST['field_settings']) ? array_map(array( $this, 'affiliatepress_sanitize_affiliatepress_fields' ), $_POST['field_settings']) : array(); // phpcs:ignore

            $affiliatepress_field_message_settings_data = ! empty($_POST['field_message_settings']) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_field' ), $_POST['field_message_settings']) : array(); // phpcs:ignore

            foreach ($affiliatepress_field_message_settings_data as $affiliatepress_messsage_settings_name => $affiliatepress_messsage_settings_value) {
                $affiliatepress_messsage_settings_value = stripslashes_deep($affiliatepress_messsage_settings_value);
                $AffiliatePress->affiliatepress_update_settings($affiliatepress_messsage_settings_name, 'message_settings' , $affiliatepress_messsage_settings_value);
            }
            
            $affiliatepress_confirm_password_fields = ! empty($_POST['confirm_password_field']) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_field' ), wp_unslash($_POST['confirm_password_field'])) : array(); // phpcs:ignore

            $affiliatepress_enable_confirm_password = isset($affiliatepress_confirm_password_fields['enable_confirm_password']) ? sanitize_text_field($affiliatepress_confirm_password_fields['enable_confirm_password']) : "true";
            $affiliatepress_confirm_password_label = isset($affiliatepress_confirm_password_fields['confirm_password_label']) ? sanitize_text_field($affiliatepress_confirm_password_fields['confirm_password_label']) :esc_html__('Confirm Password', 'affiliatepress-affiliate-marketing');
            $affiliatepress_confirm_password_placeholder = isset($affiliatepress_confirm_password_fields['confirm_password_placeholder']) ? sanitize_text_field($affiliatepress_confirm_password_fields['confirm_password_placeholder']) :esc_html__('Enter your Confirm password', 'affiliatepress-affiliate-marketing');
            $affiliatepress_confirm_password_error_msg = isset($affiliatepress_confirm_password_fields['confirm_password_error_msg']) ? sanitize_text_field($affiliatepress_confirm_password_fields['confirm_password_error_msg']) :esc_html__('Please enter your confirm password', 'affiliatepress-affiliate-marketing');
            $affiliatepress_confirm_password_validation_msg = isset($affiliatepress_confirm_password_fields['confirm_password_validation_msg']) ? sanitize_text_field($affiliatepress_confirm_password_fields['confirm_password_validation_msg']) :esc_html__('Confirm password do not match', 'affiliatepress-affiliate-marketing');

            $affiliatepress_confirm_password_settings = array(
                'enable_confirm_password' => $affiliatepress_enable_confirm_password,
                'confirm_password_label' => $affiliatepress_confirm_password_label,
                'confirm_password_placeholder' => $affiliatepress_confirm_password_placeholder,
                'confirm_password_error_msg' => $affiliatepress_confirm_password_error_msg,
                'confirm_password_validation_msg' => $affiliatepress_confirm_password_validation_msg,
            );
            $AffiliatePress->affiliatepress_update_settings('confirm_password_field', 'field_settings' , maybe_serialize($affiliatepress_confirm_password_settings));
            $AffiliatePress->affiliatepress_update_all_auto_load_settings();

            if (! empty($affiliatepress_field_settings_data) ) {

                $affiliatepress_allow_tags = array(
                    'a' => array_merge(
                            array(
                                'class' => array(),
                                'id' => array(),
                                'title' => array(),
                                'style' => array(),
                            ),
                            array(
                                'href' => array(),
                                'rel' => array(),
                                'target' => array(),
                            )
                        )
                    );

                foreach ( $affiliatepress_field_settings_data as $affiliatepress_field_setting_key => $affiliatepress_field_setting_val ) {


                    if($affiliatepress_field_setting_val['field_name'] == 'terms_and_conditions' && isset($_POST['field_settings'][$affiliatepress_field_setting_key]['label'])){
                        $affiliatepress_field_label = stripslashes_deep(wp_kses(wp_unslash($_POST['field_settings'][$affiliatepress_field_setting_key]['label']),$affiliatepress_allow_tags));
                    }else{
                        $affiliatepress_field_label = sanitize_text_field($affiliatepress_field_setting_val['label']);
                    }

                    $affiliatepress_field_is_required = isset($affiliatepress_field_setting_val['is_required']) ? (( sanitize_text_field($affiliatepress_field_setting_val['is_required']) == 'false' ) ? 0 : 1) : 1;                    
                    $affiliatepress_db_fields = array(
                        'ap_field_required'                => intval($affiliatepress_field_is_required),
                        'ap_field_label'                   => $affiliatepress_field_label, 
                        'ap_field_placeholder'             => sanitize_text_field($affiliatepress_field_setting_val['placeholder']),
                        'ap_field_error_message'           => sanitize_text_field($affiliatepress_field_setting_val['error_message']),
                        'ap_show_signup_field'             => (sanitize_text_field($affiliatepress_field_setting_val['show_signup_field']) == 'false' ) ? 0 : 1,
                        'ap_show_profile_field'            => (sanitize_text_field($affiliatepress_field_setting_val['show_profile_field']) == 'false' ) ? 0 : 1,
                        'ap_field_position'                => intval($affiliatepress_field_setting_val['field_position']),
                        'ap_field_class'                   => sanitize_text_field($affiliatepress_field_setting_val['field_class']),
                    );
                    $affiliatepress_existing_field_id = intval($affiliatepress_field_setting_val['id']);
                    $wpdb->update($affiliatepress_tbl_ap_affiliate_form_fields, $affiliatepress_db_fields, array( 'ap_form_field_id' => $affiliatepress_existing_field_id ));// phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    
                }

                do_action('affiliatepress_after_save_custom_form_fields');

                $response['variant'] = 'success';
                $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Field Settings Data Saved Successfully', 'affiliatepress-affiliate-marketing');
            }

            echo wp_json_encode($response);
            exit();
        }

        
        /**
         * Function for sanitize all fields
         *
         * @param  array $affiliatepress_field_settings
         * @return array
        */
        function affiliatepress_sanitize_affiliatepress_fields( $affiliatepress_field_settings ){

            if(!empty($affiliatepress_field_settings)){
                foreach($affiliatepress_field_settings as $affiliatepress_key=>$affiliatepress_field){

                    if($affiliatepress_key == 'id' || $affiliatepress_key == 'is_edit' || $affiliatepress_key == 'show_setting' || $affiliatepress_key == 'field_position' || $affiliatepress_key == 'keyName'){
                        $affiliatepress_field_settings[$affiliatepress_key] = intval($affiliatepress_field_settings[$affiliatepress_key]);
                    }
                    if($affiliatepress_key == 'is_required' || $affiliatepress_key == 'field_name' || $affiliatepress_key == 'field_type' || $affiliatepress_key == 'is_required' || $affiliatepress_key == 'label'  || $affiliatepress_key == 'placeholder'  || $affiliatepress_key == 'error_message'  || $affiliatepress_key == 'field_class'  || $affiliatepress_key == 'show_signup_field' || $affiliatepress_key == 'show_profile_field'){
                        $affiliatepress_field_settings[$affiliatepress_key] = sanitize_text_field($affiliatepress_field_settings[$affiliatepress_key]);
                    }                                                                                                                                                               
                    
                }    
            }
            return $affiliatepress_field_settings;
        }

        /**
         * Function for get affiliate fields 
         *
         * @return json
        */
        function affiliatepress_load_affiliate_fields_func(){

            global $wpdb,$affiliatepress_tbl_ap_affiliate_form_fields,$AffiliatePress,$affiliatepress_tbl_ap_settings;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_form_fields', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_affiliate_fields')){
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
            
            $affiliatepress_tbl_ap_affiliate_form_fields_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_form_fields); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_field_settings_data = $wpdb->get_results("SELECT * FROM {$affiliatepress_tbl_ap_affiliate_form_fields_temp} ORDER BY ap_field_position ASC", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery ,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_form_fields is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm            

            $affiliatepress_pro_active = $AffiliatePress->affiliatepress_pro_install();

            $affiliatepress_tbl_ap_settings_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_settings); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_settings contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_fields_message_settings_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_settings_temp, '*', 'WHERE ap_setting_type = %s', array( 'message_settings' ), '', '', '', false, false, ARRAY_A);

            $affiliatepress_field_messages_settings_form = array();

            foreach ($affiliatepress_fields_message_settings_data as $setting) {
                $affiliatepress_field_messages_settings_form[$setting['ap_setting_name']] = $setting['ap_setting_value'];
            }
            
            $affiliatepress_field_settings_return_data = array();
            foreach ( $affiliatepress_field_settings_data as $affiliatepress_field_setting_key => $affiliatepress_field_setting_val ) {
                $affiliatepress_field_type = '';
                
                if(!$affiliatepress_pro_active && $affiliatepress_field_setting_val['ap_field_is_default'] == 0){
                    continue;
                }

                $affiliatepress_draggable_field_setting_fields_tmp                      = array();
                $affiliatepress_draggable_field_setting_fields_tmp['id']                = intval($affiliatepress_field_setting_val['ap_form_field_id']);
                $affiliatepress_draggable_field_setting_fields_tmp['field_name']        = esc_html($affiliatepress_field_setting_val['ap_form_field_name']);
                $affiliatepress_draggable_field_setting_fields_tmp['field_type']        = esc_html($affiliatepress_field_setting_val['ap_form_field_type']);
                $affiliatepress_draggable_field_setting_fields_tmp['is_edit']           = esc_html($affiliatepress_field_setting_val['ap_field_edit']);
                $affiliatepress_draggable_field_setting_fields_tmp['show_setting']      = "false";
                $affiliatepress_draggable_field_setting_fields_tmp['is_required']       = ( $affiliatepress_field_setting_val['ap_field_required'] == 0 ) ? false : true;
                $affiliatepress_draggable_field_setting_fields_tmp['label']             = stripslashes_deep($affiliatepress_field_setting_val['ap_field_label']);
                $affiliatepress_draggable_field_setting_fields_tmp['placeholder']       = stripslashes_deep($affiliatepress_field_setting_val['ap_field_placeholder']);
                $affiliatepress_draggable_field_setting_fields_tmp['error_message']     = stripslashes_deep($affiliatepress_field_setting_val['ap_field_error_message']);
                $affiliatepress_draggable_field_setting_fields_tmp['field_class']     = stripslashes_deep($affiliatepress_field_setting_val['ap_field_class']);
                $affiliatepress_draggable_field_setting_fields_tmp['show_signup_field'] = ( $affiliatepress_field_setting_val['ap_show_signup_field'] == 0 ) ? false : true;
                $affiliatepress_draggable_field_setting_fields_tmp['show_profile_field'] = ( $affiliatepress_field_setting_val['ap_show_profile_field'] == 0 ) ? false : true;
                $affiliatepress_draggable_field_setting_fields_tmp['field_position'] = floatval($affiliatepress_field_setting_val['ap_field_position']);

                $affiliatepress_draggable_field_setting_fields_tmp = apply_filters('affiliatepress_modify_field_data_before_load', $affiliatepress_draggable_field_setting_fields_tmp, $affiliatepress_field_setting_val);      
                /* Filter for modified fields data */          

                array_push($affiliatepress_field_settings_return_data, $affiliatepress_draggable_field_setting_fields_tmp);
            }

            $affiliatepress_get_confirm_pasoword_field = array();
            $affiliatepress_confirm_password_field_settings = $AffiliatePress->affiliatepress_get_settings('confirm_password_field', 'field_settings');
            $affiliatepress_confirm_password_field_settings = !empty($affiliatepress_confirm_password_field_settings) ? maybe_unserialize($affiliatepress_confirm_password_field_settings) : array();

            if(!empty($affiliatepress_confirm_password_field_settings) && is_array($affiliatepress_confirm_password_field_settings)){

                $affiliatepress_enable_confirm_password = isset($affiliatepress_confirm_password_field_settings['enable_confirm_password']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['enable_confirm_password']) : '';
                $affiliatepress_confirm_password_label = isset($affiliatepress_confirm_password_field_settings['confirm_password_label']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_label']) : '';
                $affiliatepress_confirm_password_placeholder =isset($affiliatepress_confirm_password_field_settings['confirm_password_placeholder']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_placeholder']) : '';
                $affiliatepress_confirm_password_error_msg = isset($affiliatepress_confirm_password_field_settings['confirm_password_error_msg']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_error_msg']) : '';
                $affiliatepress_confirm_password_validation_msg = isset($affiliatepress_confirm_password_field_settings['confirm_password_validation_msg']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_validation_msg']) : '';

                if($affiliatepress_enable_confirm_password == "true"){
                    $affiliatepress_enable_confirm_password = true;
                }else{
                    $affiliatepress_enable_confirm_password = false;
                }

                $affiliatepress_get_confirm_pasoword_field = array(
                    'enable_confirm_password' => $affiliatepress_enable_confirm_password,
                    'confirm_password_label'  => $affiliatepress_confirm_password_label,
                    'confirm_password_placeholder'  => $affiliatepress_confirm_password_placeholder,
                    'confirm_password_error_msg'  => $affiliatepress_confirm_password_error_msg,
                    'confirm_password_validation_msg'  => $affiliatepress_confirm_password_validation_msg,
                );
            }
            
            $response['variant']        = 'success';
            $response['title']          = esc_html__('Success', 'affiliatepress-affiliate-marketing');
            $response['msg']            = esc_html__('Field Settings Data Retrieved Successfully', 'affiliatepress-affiliate-marketing');
            $response['field_settings'] = $affiliatepress_field_settings_return_data;
            $response['messages_setting_form'] = $affiliatepress_field_messages_settings_form;
            $response['confirm_password_field'] = $affiliatepress_get_confirm_pasoword_field;

            echo wp_json_encode($response);
            exit();


        }

      
        /**
         * affiliate fields module on load methods
         *
         * @param  string $affiliatepress_affiliate_fields_dynamic_on_load_methods
         * @return string
         */
        function affiliatepress_affiliate_fields_dynamic_on_load_methods_func($affiliatepress_affiliate_fields_dynamic_on_load_methods){

            $affiliatepress_affiliate_fields_dynamic_on_load_methods.='
                this.loadAffiliateFields().catch(error => {
                    console.error(error)
                });            
            ';
            return $affiliatepress_affiliate_fields_dynamic_on_load_methods;
        }        

       
        /**
         * Function for dynamic const add in vue
         *
         * @param  string $affiliatepress_affiliate_fields_dynamic_constant_define
         * @return string
         */
        function affiliatepress_affiliate_fields_dynamic_constant_define_func($affiliatepress_affiliate_fields_dynamic_constant_define){
            
            return $affiliatepress_affiliate_fields_dynamic_constant_define;
        }
                
        /**
         * Function for affiliate fields vue data
         *
         * @param  array $affiliatepress_affiliate_fields_vue_data_fields
         * @return json
         */
        function affiliatepress_affiliate_fields_dynamic_data_fields_func($affiliatepress_affiliate_fields_vue_data_fields){            
            
            global $AffiliatePress,$affiliatepress_affiliate_fields_vue_data_fields;
                        
            $affiliatepress_affiliate_fields_vue_data_fields = apply_filters('affiliatepress_backend_modify_affiliate_fields_data_fields', $affiliatepress_affiliate_fields_vue_data_fields);

            return wp_json_encode($affiliatepress_affiliate_fields_vue_data_fields);

        }
        
        /**
         * Function for affiliate fields dynamic vue method
         *
         * @param  string $affiliatepress_affiliate_fields_dynamic_vue_methods
         * @return string
        */
        function affiliatepress_affiliate_fields_dynamic_vue_methods_func($affiliatepress_affiliate_fields_dynamic_vue_methods){            
            global $affiliatepress_notification_duration;

            $affiliatepress_after_save_field_settings_method = "";
            $affiliatepress_after_save_field_settings_method = apply_filters('affiliatepress_after_save_field_settings_method', $affiliatepress_after_save_field_settings_method);

            $affiliatepress_after_load_field_settings = "";
            $affiliatepress_after_load_field_settings = apply_filters('affiliatepress_after_load_field_settings', $affiliatepress_after_load_field_settings);  

            $affiliatepress_before_save_field_settings_method = "";
            $affiliatepress_before_save_field_settings_method = apply_filters('affiliatepress_before_save_field_settings_method', $affiliatepress_before_save_field_settings_method); 
            
            $affiliatepress_affiliate_field_before_change_position = "";
            $affiliatepress_affiliate_field_before_change_position = apply_filters('affiliatepress_affiliate_field_before_change_position', $affiliatepress_affiliate_field_before_change_position);             
            
            $affiliatepress_affiliate_fields_dynamic_vue_methods.='
            
                ap_save_field_settings_data(){
                    const vm = this;
                    vm.$refs["field_messages_settings_form"].validate((valid) => {
                        if(valid) {
                            vm.is_display_save_loader = "1";
                            vm.is_disabled = 1;                
                            var postData = [];
                            postData.action = "affiliatepress_save_field_settings";
                            '.$affiliatepress_before_save_field_settings_method.'
                            postData.field_settings = JSON.stringify(vm.field_settings_fields);
                            postData.field_message_settings = vm.messages_setting_form;
                            postData._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'"
                            postData.confirm_password_field = vm.confirm_password_field;
                            axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                            .then( function (response) {
                                if(response.data.variant == "error"){
                                    vm.is_display_save_loader = "0";
                                    vm.is_disabled = 0;                        
                                    vm.$notify({
                                        title: response.data.title,
                                        message: response.data.msg,
                                        type: response.data.variant,
                                        customClass: response.data.variant+"_notification",                                
                                        duration:'.intval($affiliatepress_notification_duration).',
                                    });    
                                }else{
                                    vm.is_display_save_loader = "0";
                                    vm.is_disabled = 0;
                                    vm.$notify({
                                        title: "'.esc_html__("Success", "affiliatepress-affiliate-marketing").'",
                                        message: "'.esc_html__("Form Fields saved successfully.", "affiliatepress-affiliate-marketing").'",
                                        type: "success",
                                        customClass: "success_notification",
                                        duration:'.intval($affiliatepress_notification_duration).',
                                    });
                                }
                                '.$affiliatepress_after_save_field_settings_method.'
                            }.bind(this) )
                            .catch( function (error) {   
                                vm.is_display_save_loader = "0";
                                vm.is_disabled = 0;                                     
                                vm.$notify({
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
                closeFieldSettings(field_name){
                    const vm = this;
                    vm.$refs.fields_settings_popover.hide();
                },            
                async loadAffiliateFields(){
                    const vm = this;
                    var postData = []
                    postData.action = "affiliatepress_load_affiliate_fields"
                    postData._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'"
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        vm.ap_first_page_loaded = "0";                       
                        if(response.data.variant == "error"){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                        }else{                        
                            var dataObject = response.data.field_settings;
                            const arrayFromObject = Object.entries(dataObject).map(([key, value]) => {
                            return {
                                ...value,  
                                keyName: key 
                            };
                            });
                            vm.confirm_password_field = response.data.confirm_password_field;                  
                            vm.field_settings_fields = arrayFromObject;
                            vm.messages_setting_form = response.data.messages_setting_form;
                            '.$affiliatepress_after_load_field_settings.'
                        }
                    }.bind(this) )
                    .catch( function (error) {   
                        vm.ap_first_page_loaded = "0";                 
                        vm.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',  
                        });
                    });
                },
                updateFieldPos(e){
                    const vm2 = this;
                    var field_pos_update_id = e.draggedContext.element.id;
                    var old_index = e.draggedContext.index;
                    var new_index = e.draggedContext.futureIndex;
                    vm2.drag_data = {"field_pos_update_id":field_pos_update_id,"old_index":old_index,"new_index":new_index};
                },
                endDragposistion(){
                    const vm2 = this;
                    if(typeof vm2.drag_data.field_pos_update_id != "undefined" && typeof vm2.drag_data.old_index != "undefined" && typeof vm2.drag_data.new_index != "undefined"){
                        '.$affiliatepress_affiliate_field_before_change_position.'
                        var field_pos_update_id = vm2.drag_data.field_pos_update_id;
                        var old_index = vm2.drag_data.old_index;
                        var new_index = vm2.drag_data.new_index;
                        var postData = [];
                        postData.action = "affiliatepress_update_field_position";
                        postData.old_index = old_index;
                        postData.new_index = new_index;
                        postData.update_id = field_pos_update_id;
                        postData._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'"
                        axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                        .then( function (response) {
                            if(response.data.variant == "error"){
                                vm2.$notify({
                                    title: response.data.title,
                                    message: response.data.msg,
                                    type: response.data.variant,
                                    customClass: response.data.variant+"_notification",
                                    duration:'.intval($affiliatepress_notification_duration).',
                                });
                            }
                            vm2.loadAffiliateFields();
                        }.bind(this) )
                        .catch( function (error) {                    
                            vm2.$notify({
                                title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                                message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                                type: "error",
                                customClass: "error_notification",
                                duration:'.intval($affiliatepress_notification_duration).',  
                            });
                        });
                    }
                },
            ';

            return $affiliatepress_affiliate_fields_dynamic_vue_methods;


        }
        
        /**
         * Function for dynamic View load
         *
         * @return html
        */        
        function affiliatepress_affiliate_fields_dynamic_view_load_func(){

            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliate_fields/manage_affiliate_fields.php';
            $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_affiliates_fields_view_file_path', $affiliatepress_load_file_name);
            include $affiliatepress_load_file_name;

        }

        
        /**
         * Function for affiliates default Vue Data
         *
         * @return void
        */
        function affiliatepress_affiliate_fields_vue_data_fields(){

            global $affiliatepress_affiliate_fields_vue_data_fields;            
            $affiliatepress_pagination          = wp_json_encode(array( 10, 20, 50, 100, 200, 300, 400, 500 ));
            $affiliatepress_pagination_arr      = json_decode($affiliatepress_pagination, true);
            $affiliatepress_pagination_selected = $this->affiliatepress_per_page_record;

            $affiliatepress_affiliate_fields_vue_data_fields = array(
                'bulk_action'                => 'bulk_action',
                'drag_data'                  => '',
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
                'field_settings_fields'                  => array(
                    array(
                        'field_name'     => 'fullname',
                        'field_type'     => 'Text',
                        'is_edit'        => 0,
                        'is_required'    => 0,
                        'label'          => esc_html__('Fullname', 'affiliatepress-affiliate-marketing'),
                        'placeholder'    => esc_html__('Enter your full name', 'affiliatepress-affiliate-marketing'),
                        'error_message'  => esc_html__('Please enter your full name', 'affiliatepress-affiliate-marketing'),
                        'is_hide'        => 0,
                        'is_default'     => 1,
                        'field_position' => 1,
                    ),
                    array(
                        'field_name'     => 'firstname',
                        'field_type'     => 'Text',
                        'is_edit'        => 0,
                        'is_required'    => 0,
                        'label'          => esc_html__('Firstname', 'affiliatepress-affiliate-marketing'),
                        'placeholder'    => esc_html__('Enter your firstname', 'affiliatepress-affiliate-marketing'),
                        'error_message'  => esc_html__('Please enter your firstname', 'affiliatepress-affiliate-marketing'),
                        'is_hide'        => 0,
                        'is_default'     => 1,
                        'field_position' => 2,
                    ),
                    array(
                        'field_name'     => 'lastname',
                        'field_type'     => 'Text',
                        'is_edit'        => 0,
                        'is_required'    => 0,
                        'label'          => esc_html__('Lastname', 'affiliatepress-affiliate-marketing'),
                        'placeholder'    => esc_html__('Enter your lastname', 'affiliatepress-affiliate-marketing'),
                        'error_message'  => esc_html__('Please enter your lastname', 'affiliatepress-affiliate-marketing'),
                        'is_hide'        => 0,
                        'is_default'     => 1,
                        'field_position' => 3,
                    ),
                    array(
                        'field_name'     => 'email_address',
                        'field_type'     => 'Email',
                        'is_edit'        => 0,
                        'is_required'    => true,
                        'label'          => esc_html__('Email Address', 'affiliatepress-affiliate-marketing'),
                        'placeholder'    => esc_html__('Enter your email address', 'affiliatepress-affiliate-marketing'),
                        'error_message'  => esc_html__('Please enter your email address', 'affiliatepress-affiliate-marketing'),
                        'is_hide'        => 0,
                        'is_default'     => 1,
                        'field_position' => 4,
                    ),
                    array(
                        'field_name'     => 'phone_number',
                        'field_type'     => 'Dropdown',
                        'is_edit'        => 0,
                        'is_required'    => 0,
                        'label'          => esc_html__('Phone Number', 'affiliatepress-affiliate-marketing'),
                        'placeholder'    => esc_html__('Enter your phone number', 'affiliatepress-affiliate-marketing'),
                        'error_message'  => esc_html__('Please enter your phone number', 'affiliatepress-affiliate-marketing'),
                        'is_hide'        => 0,
                        'is_default'     => 1,
                        'field_position' => 5,
                        'set_custom_placeholder' => 0,
                    ),
                    array(
                        'field_name'     => 'note',
                        'field_type'     => 'Textarea',
                        'is_edit'        => 0,
                        'is_required'    => 0,
                        'label'          => esc_html__('Note', 'affiliatepress-affiliate-marketing'),
                        'placeholder'    => esc_html__('Enter note details', 'affiliatepress-affiliate-marketing'),
                        'error_message'  => esc_html__('Please enter appointment note', 'affiliatepress-affiliate-marketing'),
                        'is_hide'        => 0,
                        'is_default'     => 1,
                        'field_position' => 6,
                    ),
                    array(
                        'field_name'     => 'username',
                        'field_type'     => 'text',
                        'is_edit'        => 0,
                        'is_required'    => true,
                        'placeholder'    => esc_html__('Enter your username', 'affiliatepress-affiliate-marketing'),
                        'label'          => esc_html__('Username', 'affiliatepress-affiliate-marketing'),
                        'error_message'  => esc_html__('Please enter your username', 'affiliatepress-affiliate-marketing'),
                        'is_hide'        => 1,
                        'field_position' => 8,
                    ),
                ),
                'confirm_password_field' => array(
                    'enable_confirm_password' => true,
                    'confirm_password_label'  => esc_html__('Confirm Password', 'affiliatepress-affiliate-marketing'),
                    'confirm_password_placeholder'  => esc_html__('Enter your Confirm password', 'affiliatepress-affiliate-marketing'),
                    'confirm_password_error_msg'  => esc_html__('Please enter your confirm password', 'affiliatepress-affiliate-marketing'),
                    'confirm_password_validation_msg'  => esc_html__('Confirm password do not match', 'affiliatepress-affiliate-marketing'),
                ),
                'messages_setting_form'=>array(
                    'login_error_message'                   => '',
                    'affiliate_register_with_auto_approved' => '', 
                    'affiliate_register_with_pending'       => '',
                    'username_already_exists'               => '',
                    'email_already_exists'                  => '',
                    'affiliate_registration_disabled'       => '',
                    'login_is_not_allowed'                  => '', 
                    'affiliate_wrong_email'                 => '',
                    'send_password_reset_link'              => '', 
                    'account_closure_request_success'       => '',
                    'affiliate_custom_link_added'           => '',
                    'incorrect_current_password'            => '',
                    'new_and_current_password_not_match'    => '',
                    'password_successfully_updated'         => '',
                    'profile_fields_successfully_updated'   => '',
                    'affiliate_pending_register_message'    => '',
                    'affiliate_user_block_message'          => '',
                    'link_limit_reached_error'              => '',
                    'affiliate_link_delete'                 => '',
                    'link_copied'                           => '',
                    'file_upload_type_validation'           => '',
                    'file_upload_limit_validation'          => '',
                    'not_allow_affiliate_register'          => '',
                    'affiliate_already_registered_message'  => '',
                    'required_field_validation'             => '',
                ),
                'rules_messages'=> array(
                    'login_error_message'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'affiliate_register_with_auto_approved'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'affiliate_register_with_pending'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'username_already_exists'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'email_already_exists'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'affiliate_registration_disabled'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    'login_is_not_allowed'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),         
                    'required_field_validation'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),         
                    'affiliate_wrong_email'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),             
                    'send_password_reset_link'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),               
                    'account_closure_request_success'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),               
                    'affiliate_custom_link_added'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),                                                                                                                                     
                    'campaign_name_already_added'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),         
                    'incorrect_current_password'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),         
                    'new_and_current_password_not_match'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),         
                    'password_successfully_updated'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),         
                    'profile_fields_successfully_updated'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'affiliate_pending_register_message'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'affiliate_already_registered_message'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'affiliate_user_block_message'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),      
                    'link_limit_reached_error'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),     
                    'affiliate_link_delete'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),     
                    'link_copied'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),     
                    'file_upload_type_validation'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),     
                    'file_upload_limit_validation'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),    
                    'not_allow_affiliate_register'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                ),  
                'loading'                    => false,
                'affiliates_search'          => array(
                    "ap_affiliates_user"     => '',
                    "ap_affiliates_status"   => '',
                ),
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
                'wpUsersList'                => array(),
                'affiliatepress_user_loading'=> false,
                'affiliates'                 => array(
                    'username'                     => "",
                    'firstname'                    => "",
                    'lastname'                     => "",
                    'email'                        => "",
                    'password'                     => "",
                    "ap_affiliates_id"             => "",
                    "ap_affiliates_user_id"        => "",
                    "ap_affiliates_payment_email"  => "",
                    "ap_affiliates_website"        => "",
                    "ap_affiliates_user_avatar"    => "",
                    "avatar_url"                   => "",
                    "avatar_name"                  => "",
                    "ap_affiliates_status"         => "1",
                    "ap_affiliates_promote_us"     => "",
                    "ap_send_email"                => false,
                ),                
                'rules'                      => array(
                    'password'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add password', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'lastname'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add lastname', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),                    
                    'firstname'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add firstname', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),                     
                    'email'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter user email', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                        array(
                            'type'    => 'email',
                            'message' => esc_html__( 'Please enter valid user email address', 'affiliatepress-affiliate-marketing'),
                            'trigger' => 'blur',
                        ), 
                    ),                     
                    'username'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add username', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),                    
                    'ap_affiliates_user_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add affiliates user', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),                    
                    'ap_affiliates_payment_email' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter payment email', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                        array(
                            'type'    => 'email',
                            'message' => esc_html__( 'Please enter valid email address', 'affiliatepress-affiliate-marketing'),
                            'trigger' => 'blur',
                        ),                        
                    ),                    
                ),

            );
        }



    }
}
global $affiliatepress_affiliate_fields;
$affiliatepress_affiliate_fields = new affiliatepress_affiliate_fields();
