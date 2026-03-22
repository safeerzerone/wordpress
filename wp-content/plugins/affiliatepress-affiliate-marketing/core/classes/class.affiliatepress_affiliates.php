<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_affiliates') ) {
    class affiliatepress_affiliates Extends AffiliatePress_Core{
        
        var $affiliatepress_per_page_record;

        function __construct(){

            $this->affiliatepress_per_page_record = 10;

            add_action('wp_ajax_affiliatepress_export_affiliate',array($this,'affiliatepress_export_affiliate_func'),10);

            /**Function for affiliates default Vue Data*/
            add_action( 'admin_init', array( $this, 'affiliatepress_affiliates_vue_data_fields') );

            /* Dynamic Constant */
            add_filter('affiliatepress_affiliates_dynamic_constant_define',array($this,'affiliatepress_affiliates_dynamic_constant_define_func'),10,1);
            
            /* Dynamic Vue Fields */
            add_filter('affiliatepress_affiliates_dynamic_data_fields',array($this,'affiliatepress_affiliates_dynamic_data_fields_func'),10,1);

            /* Vue Load */
            add_action('affiliatepress_affiliates_dynamic_view_load', array( $this, 'affiliatepress_affiliates_dynamic_view_load_func' ), 10);

            /* Vue Method */
            add_filter('affiliatepress_affiliates_dynamic_vue_methods',array($this,'affiliatepress_affiliates_dynamic_vue_methods_func'),10,1);

            /* Add Affiliates */
            add_action('wp_ajax_affiliatepress_add_affiliate', array( $this, 'affiliatepress_add_affiliate_func' ));

            /* Get Affiliates */
            add_action('wp_ajax_affiliatepress_get_affiliates', array( $this, 'affiliatepress_get_affiliates' ));

            /* Dynamic On Load Method */
            add_filter('affiliatepress_affiliates_dynamic_on_load_methods', array( $this, 'affiliatepress_affiliates_dynamic_on_load_methods_func' ), 10,1);

            /* Change Affiliate Status */
            add_action('wp_ajax_affiliatepress_change_affiliate_status', array( $this, 'affiliatepress_change_affiliate_status_func' ));

            /* Delete Affiliate */
            add_action('wp_ajax_affiliatepress_delete_affiliate', array( $this, 'affiliatepress_delete_affiliate' ));

            /* Bulk Action */
            add_action('wp_ajax_affiliatepress_affiliate_bulk_action', array( $this, 'affiliatepress_affiliate_bulk_action_func' ));

            /* Get User List */
            add_action('wp_ajax_affiliatepress_get_wpuser', array( $this, 'affiliatepress_get_wpuser' ));

            /* Upload Avatar */
            add_action('wp_ajax_affiliatepress_upload_affiliate_avatar', array( $this, 'affiliatepress_upload_affiliate_avatar_func' ), 10);

            /* Edit Affiliate */
            add_action('wp_ajax_affiliatepress_edit_affiliate', array( $this, 'affiliatepress_edit_affiliate_func' ));

            /* Remove Affiliate Avatar Image */
            add_action( 'wp_ajax_affiliatepress_remove_affiliate_avatar', array( $this, 'affiliatepress_remove_affiliate_avatar_func'));

            /* Function for send affiliate chnage status email */            
            add_action('affiliatepress_after_affiliate_status_change',array($this,'affiliatepress_after_affiliate_status_change_func'),10,3);            

            /* Upload Import File */
            add_action('wp_ajax_affiliatepress_upload_affiliate_import_file', array( $this, 'affiliatepress_upload_affiliate_import_file_func' ), 10);

            /* Import Affiliate */
            add_action('wp_ajax_affiliatepress_import_affiliates',array($this,'affiliatepress_import_affiliates_func'));

            /* Get exiting user detail  */
            add_action('wp_ajax_affiliatepress_get_existing_users_details', array( $this, 'affiliatepress_get_existing_users_details' ), 10);

            /* AffiliatePress User Profile Update */
            add_action('profile_update', array($this,'affiliatepress_profile_update_action'), 10, 2);

        }
                
        /**
         * Function for update affiliate user name after update user profile
         *
         * @param  mixed $user_id
         * @param  mixed $old_user_data
         * @return void
        */
        function affiliatepress_profile_update_action($user_id, $old_user_data){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliates, $AffiliatePress;

            $ap_affiliates_user_id = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_id', 'WHERE ap_affiliates_user_id  = %d', array( intval($user_id) ), '', '', '', true, true,ARRAY_A));

            if($ap_affiliates_user_id){
                
                $user_info = get_userdata($ap_affiliates_user_id);

                if(!empty($user_info)){

                    $first_name = $user_info->first_name;
                    $last_name  = $user_info->last_name;
                    $user_login = $user_info->user_login;
                    $user_email = $user_info->user_email;
                    
                    $affiliatepress_args = array(
                        'ap_affiliates_first_name' => $first_name,
                        'ap_affiliates_last_name'  => $last_name,
                        'ap_affiliates_user_name'  => $user_login,
                        'ap_affiliates_user_email' => $user_email,
                    );

                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliates, $affiliatepress_args, array( 'ap_affiliates_user_id' => $ap_affiliates_user_id ));
    
                }
        
            }

        }

        /**
         * Get existing wordpress user details
         *
         * @return json
         */
        function affiliatepress_get_existing_users_details(){
            global $wpdb;
            $response              = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'search_user', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_affiliates')){
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

            $response['variant']      = 'error';
            $response['title']        = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $response['msg']          = esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing');
            $response['user_details'] = '';

            $affiliatepress_existing_user_id = ! empty($_REQUEST['existing_user_id']) ? intval($_REQUEST['existing_user_id']) : 0; // phpcs:ignore
            if (! empty($affiliatepress_existing_user_id) ) {
                $affiliatepress_user_details = get_user_by('id', $affiliatepress_existing_user_id);
                $affiliatepress_user_email   = $affiliatepress_user_details->data->user_email;
                $affiliatepress_user_name    = $affiliatepress_user_details->data->user_login;
                
                $affiliatepress_user_firstname = get_user_meta($affiliatepress_existing_user_id, 'first_name', true);
                $affiliatepress_user_lastname  = get_user_meta($affiliatepress_existing_user_id, 'last_name', true);

                $affiliatepress_user_data = array(
                    'username'       => esc_html($affiliatepress_user_name),
                    'user_email'     => esc_html($affiliatepress_user_email),
                    'user_firstname' => esc_html($affiliatepress_user_firstname),
                    'user_lastname'  => esc_html($affiliatepress_user_lastname),
                );

                $response['user_details'] = $affiliatepress_user_data;
                $response['variant']      = 'success';
                $response['title']        = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']          = esc_html__('Users details fetched successfully.', 'affiliatepress-affiliate-marketing');
            }

            echo wp_json_encode($response);
            exit();
        }

        /**
         * Function for import affiliate 
         *
         * @return json
        */
        function affiliatepress_import_affiliates_func(){
            
            global $wpdb,$affiliatepress_tbl_ap_affiliates,$AffiliatePress;       
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'import_affiliate', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_affiliates')){
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

            $affiliatepress_import_file_name =  isset($_POST['import_file_name']) ? sanitize_text_field($_POST['import_file_name']) : ''; // phpcs:ignore 
            $affiliatepress_affiliate_field_data = '';
            if((isset($_POST['import_file_fields'])) && !empty($_POST['import_file_fields']) && is_array($_POST['import_file_fields'])){  // phpcs:ignore                               
                $affiliatepress_affiliate_field_data = !empty($_POST['import_file_fields']) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_integer_field' ), stripslashes_deep($_POST['import_file_fields'])) : array(); // phpcs:ignore
            }
            $affiliatepress_affiliates_status = (isset($affiliatepress_affiliate_field_data['ap_affiliates_status']))?intval($affiliatepress_affiliate_field_data['ap_affiliates_status']):1;
            if($affiliatepress_affiliates_status == 0){
                $affiliatepress_affiliates_status = 1;
            }
            if(isset($affiliatepress_affiliate_field_data['ap_affiliates_status'])){
                unset($affiliatepress_affiliate_field_data['ap_affiliates_status']);
            }
            if(!empty($affiliatepress_affiliate_field_data) && !empty($affiliatepress_import_file_name)){
                $affiliatepress_upload_dir               = AFFILIATEPRESS_IMPORT_DIR . '/';                
                $affiliatepress_destination = $affiliatepress_upload_dir . basename($affiliatepress_import_file_name);            
                $affiliatepress_total_count = 0;
                $affiliatepress_import_count = 0;
                $affiliatepress_duplicate_count = 0;                
                if(file_exists($affiliatepress_destination)){
                    $affiliatepress_data_array = array();
                    if (($affiliatepress_handle = fopen($affiliatepress_destination, "r")) !== FALSE) { // phpcs:ignore

                        $affiliatepress_final_first_row_data = array();
                        $affiliatepress_first_row = (array)fgetcsv($affiliatepress_handle);  
                        if(!isset($affiliatepress_first_row[0]) || empty($affiliatepress_first_row) || (isset($affiliatepress_first_row[0]) && empty($affiliatepress_first_row[0]))){
                            $affiliatepress_first_row = (array) fgetcsv($affiliatepress_handle);
                        }                         
                        if(!empty($affiliatepress_first_row) && is_array($affiliatepress_first_row)){
                            foreach($affiliatepress_first_row as $affiliatepress_key=>$affiliatepress_val){
                                $affiliatepress_final_first_row_data[$affiliatepress_key] = $affiliatepress_val;
                            }
                        } 

                        $affiliatepress_created_date_key = "";
                        $affiliatepress_first_name_key = "";
                        $affiliatepress_last_name_key = "";
                        foreach ($affiliatepress_final_first_row_data as $key => $value) {
                            if($value == "Date Registered"){
                                $affiliatepress_created_date_key = $key;
                            }
                            if($value == "First Name"){
                                $affiliatepress_first_name_key = $key;
                            }
                            if($value == "Last Name"){
                                $affiliatepress_last_name_key = $key;
                            }
                        }

                        $affiliatepress_i = 0;
                        while (($affiliatepress_row = fgetcsv($affiliatepress_handle, 2000, ",")) !== FALSE) {
                            $affiliatepress_i++;
                            if(empty($affiliatepress_row)){
                                $affiliatepress_row = fgetcsv($affiliatepress_handle, 2000, ",");
                            }
                            $affiliatepress_total_count++;
                            $affiliatepress_has_import_affiliate = false;
                            if(!empty($affiliatepress_row) && is_array($affiliatepress_row)){
                                if(!empty($affiliatepress_affiliate_field_data) && is_array($affiliatepress_affiliate_field_data)){
                                    $affiliatepress_final_single_import_data = array();
                                    foreach($affiliatepress_affiliate_field_data as $affiliatepress_fkey=>$affiliatepress_fval){
                                        $affiliatepress_final_single_import_data[$affiliatepress_fkey] = (isset($affiliatepress_row[$affiliatepress_fval]))?$affiliatepress_row[$affiliatepress_fval]:'';
                                    }
                                    $affiliatepress_firstname = (isset($affiliatepress_final_single_import_data['firstname']))?sanitize_text_field($affiliatepress_final_single_import_data['firstname']):'';
                                    if(empty($affiliatepress_firstname)){
                                        $affiliatepress_firstname = isset($affiliatepress_row[$affiliatepress_first_name_key]) ? sanitize_text_field($affiliatepress_row[$affiliatepress_first_name_key]) : $affiliatepress_firstname; 
                                    }
                                    $affiliatepress_lastname  = (isset($affiliatepress_final_single_import_data['lastname']))?sanitize_text_field($affiliatepress_final_single_import_data['lastname']):'';
                                    if(empty($affiliatepress_lastname)){
                                        $affiliatepress_lastname = isset($affiliatepress_row[$affiliatepress_last_name_key]) ? sanitize_text_field($affiliatepress_row[$affiliatepress_last_name_key]) : $affiliatepress_lastname; 
                                    }
                                    $affiliatepress_username  = (isset($affiliatepress_final_single_import_data['username']))?sanitize_text_field($affiliatepress_final_single_import_data['username']):'';
                                    $affiliatepress_email     = (isset($affiliatepress_final_single_import_data['email']))?sanitize_email($affiliatepress_final_single_import_data['email']):'';
                                    $affiliatepress_affiliates_payment_email = (isset($affiliatepress_final_single_import_data['ap_affiliates_payment_email']))?sanitize_email($affiliatepress_final_single_import_data['ap_affiliates_payment_email']):'';
                                    $affiliatepress_affiliates_website = (isset($affiliatepress_final_single_import_data['ap_affiliates_website']) && !empty($affiliatepress_final_single_import_data['ap_affiliates_website']))?sanitize_url($affiliatepress_final_single_import_data['ap_affiliates_website']):'';
                                    $affiliatepress_affiliates_promote_us = (isset($affiliatepress_final_single_import_data['ap_affiliates_promote_us']))?sanitize_text_field($affiliatepress_final_single_import_data['ap_affiliates_promote_us']):'';                                                                          
                                    if(!empty($affiliatepress_email) && is_email($affiliatepress_email)){
                                        if(empty($affiliatepress_username) || $affiliatepress_username == "-"){
                                            $affiliatepress_username = $affiliatepress_email;
                                        }
                                        if(!email_exists($affiliatepress_email) && !username_exists($affiliatepress_username)){
                                            $affiliatepress_user_id = wp_insert_user( array(
                                                'user_login' => sanitize_user( $affiliatepress_username, true ),
                                                'user_email' => sanitize_text_field($affiliatepress_email),
                                                'user_pass'  => wp_generate_password( 20, false ),
                                                'first_name' => !empty( $affiliatepress_firstname ) ? sanitize_text_field( $affiliatepress_firstname ) : '',
                                                'last_name'  => !empty( $affiliatepress_lastname ) ? sanitize_text_field( $affiliatepress_lastname ) : '',
                                            ));
                                            if($affiliatepress_user_id){                                                
                                                $affiliatepress_args = array(   
                                                    'ap_affiliates_first_name'      => !empty( $affiliatepress_firstname ) ? sanitize_text_field( $affiliatepress_firstname ) : '',
                                                    'ap_affiliates_last_name'       => !empty( $affiliatepress_lastname ) ? sanitize_text_field( $affiliatepress_lastname ) : '',
                                                    'ap_affiliates_user_name'       => sanitize_user( $affiliatepress_username, true ),
                                                    'ap_affiliates_user_email'      => sanitize_text_field($affiliatepress_email),
                                                    'ap_affiliates_user_id'         => $affiliatepress_user_id,  
                                                    'ap_affiliates_status'          => $affiliatepress_affiliates_status                                                               
                                                );  
                                                if(!empty($affiliatepress_affiliates_payment_email)){
                                                    $affiliatepress_args['ap_affiliates_payment_email'] = $affiliatepress_affiliates_payment_email;
                                                }
                                                if(!empty($affiliatepress_affiliates_website)){
                                                    $affiliatepress_args['ap_affiliates_website'] = $affiliatepress_affiliates_website;
                                                }
                                                if(!empty($affiliatepress_affiliates_promote_us)){
                                                    $affiliatepress_args['ap_affiliates_promote_us'] = $affiliatepress_affiliates_promote_us;
                                                }    
                                                if(!empty($affiliatepress_created_date_key)){
                                                    $date = date_create($affiliatepress_row[$affiliatepress_created_date_key]); 
                                                    $affiliatepress_created_date = $date ? date_format($date, 'Y-m-d H:i:s') : '';
                                                    $affiliatepress_args['ap_affiliates_created_at'] = $affiliatepress_created_date;
                                                } 
                                                
                                                $affiliatepress_affiliates_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_affiliates, $affiliatepress_args);
                                                if($affiliatepress_affiliates_id){
                                                    $affiliatepress_has_import_affiliate = true;
                                                    $this->affiliatepress_add_affiliate_user_role($affiliatepress_user_id);                                                    
                                                }else{

                                                    $affiliatepress_error_msg = sprintf( esc_html__('Affiliate User not created for :  %1$s Username :  %2$s','affiliatepress-affiliate-marketing'), $affiliatepress_email, $affiliatepress_username);// phpcs:ignore
                                                }                                                
                                            }else{

                                                $affiliatepress_error_msg = sprintf( esc_html__('WordPress User not created for :  %1$s Username :  %2$s','affiliatepress-affiliate-marketing'), $affiliatepress_email, $affiliatepress_username);// phpcs:ignore
                                            }
                                        }else{

                                            $affiliatepress_error_msg = sprintf( esc_html__('Username OR Email already exists email :  %1$s Username :  %2$s','affiliatepress-affiliate-marketing'), $affiliatepress_email, $affiliatepress_username);// phpcs:ignore
                                        }
                                    }else{

                                        $affiliatepress_error_msg = sprintf( esc_html__('Not Valid Email Address  %1$s','affiliatepress-affiliate-marketing'), $affiliatepress_email);// phpcs:ignore
                                    }
                                }
                            }                    
                            if($affiliatepress_has_import_affiliate){
                                $affiliatepress_import_count++;
                            }else{
                                $affiliatepress_duplicate_count++;
                            }              
                        }                        
                        fclose($affiliatepress_handle); // phpcs:ignore
                        $response['variant'] = 'success';
                        $response['title'] = esc_html__( 'Success', 'affiliatepress-affiliate-marketing');
                        $response['msg'] =  esc_html__( 'Affiliate Succesfully Imported', 'affiliatepress-affiliate-marketing');
                        $response['total_count'] = $affiliatepress_total_count;
                        $response['import_count'] = $affiliatepress_import_count;
                        $response['duplicate_count'] = $affiliatepress_duplicate_count;

                        if(file_exists($affiliatepress_destination)){
                            wp_delete_file($affiliatepress_destination); // phpcs:ignore
                        }
                        wp_send_json( $response );
                        die;                        

                    }else{

                        if(file_exists($affiliatepress_destination)){
                            wp_delete_file($affiliatepress_destination); // phpcs:ignore
                        }
                        $response['variant'] = 'error';
                        $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                        $response['msg'] =  esc_html__( 'CSV File read premission issue.', 'affiliatepress-affiliate-marketing');
                        wp_send_json( $response );
                        die;
                    }   
                    
                  

                }else{
                    $response['variant'] = 'error';
                    $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                    $response['msg'] =  esc_html__( 'CSV File not exists.', 'affiliatepress-affiliate-marketing');
                    wp_send_json( $response );
                    die;
                }
            }
            $response['variant'] = 'error';
            $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg'] =  esc_html__( 'Something Wrong.', 'affiliatepress-affiliate-marketing');

           
            wp_send_json( $response );
            die;

        }

        /**
         * Function for upload import file
         *
         * @return json
        */
        function affiliatepress_upload_affiliate_import_file_func(){

            $return_data = array(
                'error'            => 0,
                'msg'              => '',
                'upload_url'       => '',
                'upload_file_name' => '',
            );//phpcs:ignore

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'affiliate_upload_import_file', true, 'affiliatepress_upload_affiliate_import_file' );            
            $response = array();
            $response['variant'] = 'error';
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

            if(!current_user_can('affiliatepress_affiliates')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_upload_affiliate_import_file');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }  

            $affiliatepress_fileupload_obj = new affiliatepress_fileupload_class( $_FILES['file'] ); // phpcs:ignore
            if (! $affiliatepress_fileupload_obj ) {
                $return_data['error'] = 1;
                $return_data['msg']   = $affiliatepress_fileupload_obj->error_message;
            }

            $affiliatepress_fileupload_obj->affiliatepress_check_cap          = true;
            $affiliatepress_fileupload_obj->affiliatepress_check_nonce        = true;
            $affiliatepress_fileupload_obj->affiliatepress_nonce_data         = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''; // phpcs:ignore 
            $affiliatepress_fileupload_obj->affiliatepress_nonce_action       = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : ''; // phpcs:ignore 
            $affiliatepress_fileupload_obj->affiliatepress_check_only_image   = false;
            $affiliatepress_fileupload_obj->affiliatepress_check_specific_ext = true;
            $affiliatepress_fileupload_obj->affiliatepress_allowed_ext        = array('csv');
            $affiliatepress_file_name                = isset($_FILES['file']['name']) ? current_time('timestamp') . '_' . sanitize_file_name($_FILES['file']['name']) : ''; // phpcs:ignore
            $affiliatepress_file_name                = 'affiliate-import.csv';
            $affiliatepress_upload_dir               = AFFILIATEPRESS_IMPORT_DIR . '/';
            $affiliatepress_upload_url               = AFFILIATEPRESS_IMPORT_URL . '/';
            $affiliatepress_destination = $affiliatepress_upload_dir . $affiliatepress_file_name;
            $affiliatepress_check_file = wp_check_filetype_and_ext( $affiliatepress_destination, $affiliatepress_file_name );            
            if( empty( $affiliatepress_check_file['ext'] ) ){
                $return_data['error'] = 1;
                $return_data['upload_error'] = $affiliatepress_upload_file;
                $return_data['msg']   = esc_html__('Invalid file extension. Please select valid file.', 'affiliatepress-affiliate-marketing');
            } else {
                $affiliatepress_upload_file = $affiliatepress_fileupload_obj->affiliatepress_process_upload($affiliatepress_destination);
                if ($affiliatepress_upload_file == false ) {
                    $return_data['error'] = 1;
                    $return_data['msg']   = ! empty($affiliatepress_fileupload_obj->error_message) ? $affiliatepress_fileupload_obj->error_message : esc_html__('Something went wrong while updating the file', 'affiliatepress-affiliate-marketing');
                } else {
                    if (($affiliatepress_handle = fopen($affiliatepress_destination, 'r')) !== false) { // phpcs:ignore

                        $affiliatepress_final_first_row_data = array();
                        $affiliatepress_first_row = (array)fgetcsv($affiliatepress_handle);
                        if(!isset($affiliatepress_first_row[0]) || empty($affiliatepress_first_row) || (isset($affiliatepress_first_row[0]) && empty($affiliatepress_first_row[0]))){
                            $affiliatepress_first_row = (array) fgetcsv($affiliatepress_handle);
                        }
                        if(!empty($affiliatepress_first_row)){
                            foreach($affiliatepress_first_row as $affiliatepress_key=>$affiliatepress_val){
                                $affiliatepress_final_first_row_data[] = array('key'=>$affiliatepress_key,'value'=>$affiliatepress_val);
                            }
                        }
                        $return_data['error']              = 0;
                        $return_data['msg']                = '';
                        $return_data['import_file_fields'] = $affiliatepress_final_first_row_data;
                        $return_data['import_file_name'] = $affiliatepress_file_name;
                    }else{
                        $return_data['error'] = 1;
                        $return_data['upload_error'] = $affiliatepress_upload_file;
                        $return_data['msg']   = esc_html__('File read permission not allowed.', 'affiliatepress-affiliate-marketing');                        
                    }
                }
            }            
            echo wp_json_encode($return_data);
            exit();

        }  
        
        /**
         * Function for export affiliate
         *
         * @return json
        */
        function affiliatepress_export_affiliate_func(){            

            global $wpdb, $affiliatepress_tbl_ap_affiliates, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress, $affiliatepress_tbl_ap_affiliate_visits;            
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'export_affiliate', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_affiliates')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }

            $affiliatepress_user_table = $this->affiliatepress_tablename_prepare($wpdb->users); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->users contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $wp_usermeta_table = $this->affiliatepress_tablename_prepare($wpdb->usermeta); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->usermeta contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_tbl_ap_affiliates_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_affiliates_record  = $wpdb->get_results("SELECT affiliate.* FROM {$affiliatepress_tbl_ap_affiliates_temp} as affiliate order by ap_affiliates_id ASC", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery ,PluginCheck.Security.DirectDB.UnescapedDBParameter , WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm
            
            $affiliates = array();         
            if(!empty($affiliatepress_affiliates_record)){
                
                $affiliatepress_all_affiliates_data = array();
                $affiliatepress_all_affiliates_status = $this->affiliatepress_all_affiliates_status();
                foreach($affiliatepress_all_affiliates_status as $affiliatepress_val){
                    $affiliatepress_all_affiliates_data[$affiliatepress_val['value']] = $affiliatepress_val['label'];
                }
                foreach($affiliatepress_affiliates_record as $affiliatepress_single_affiliate){
                    $affiliate = array();

                    $user_id = (!empty($affiliatepress_single_affiliate['ap_affiliates_user_id']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_user_id']):0; 

                    $affiliatepress_first_name =  (!empty($affiliatepress_single_affiliate['ap_affiliates_first_name']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_first_name']):""; 
                    $affiliatepress_last_name  =  (!empty($affiliatepress_single_affiliate['ap_affiliates_last_name']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_last_name']):""; 

                    $affiliatepress_user_first_name =  $affiliatepress_first_name;
                    $affiliatepress_user_last_name  =  $affiliatepress_last_name;


                    $affiliatepress_affiliate_id = (isset($affiliatepress_single_affiliate['ap_affiliates_id']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_id']):0;
                    $affiliate['ap_affiliates_id'] = (isset($affiliatepress_single_affiliate['ap_affiliates_id']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_id']):'-';
                    $affiliate['ap_affiliates_user_name'] = (isset($affiliatepress_single_affiliate['user_login']))?stripslashes_deep($affiliatepress_single_affiliate['user_login']):stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_user_email']);

                    $affiliate['email'] = (isset($affiliatepress_single_affiliate['ap_affiliates_user_email']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_user_email']):'-';

                    $affiliate['first_name'] = (!empty($affiliatepress_user_first_name))?stripslashes_deep($affiliatepress_user_first_name):'-';
                    $affiliate['last_name'] = (isset($affiliatepress_user_last_name))?stripslashes_deep($affiliatepress_user_last_name):'-';
                    $affiliate['affiliate_payment_email'] = (isset($affiliatepress_single_affiliate['ap_affiliates_payment_email']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_payment_email']):'-';                                                      
                    $affiliatepress_paid_earning = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount)', 'WHERE ap_affiliates_id  = %d AND ap_commission_status IN (4)', array( $affiliatepress_affiliate_id ), '', '', '', true, false,ARRAY_A));
                    $affiliatepress_paid_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_paid_earning,2));
                    $affiliatepress_unpaid_earning = floatval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'SUM(ap_commission_amount)', 'WHERE ap_affiliates_id  = %d AND ap_commission_status IN (1)', array( $affiliatepress_affiliate_id ), '', '', '', true, false,ARRAY_A));
                    $affiliatepress_unpaid_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_unpaid_earning,2));                      
                    $affiliate['unpaid_earning'] = $affiliatepress_unpaid_earning;
                    $affiliate['paid_earning'] = $affiliatepress_paid_earning;
                    $affiliate['website'] = (isset($affiliatepress_single_affiliate['ap_affiliates_website']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_website']):'-';                    
                    $affiliate['status'] = (isset($affiliatepress_all_affiliates_data[$affiliatepress_single_affiliate['ap_affiliates_status']]))?$affiliatepress_all_affiliates_data[$affiliatepress_single_affiliate['ap_affiliates_status']]:'';
                    $affiliatepress_total_commission = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'COUNT(ap_commission_id)', 'WHERE ap_affiliates_id  = %d AND ap_commission_status IN (1,4)', array( $affiliatepress_affiliate_id), '', '', '', true, false,ARRAY_A));
                    $affiliatepress_total_visits = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_visits, 'COUNT(ap_visit_id)', 'WHERE ap_affiliates_id  = %d  ', array( $affiliatepress_affiliate_id), '', '', '', true, false,ARRAY_A));
                    $affiliate['total_visit'] = $affiliatepress_total_visits;
                    $affiliate['convert_user'] = $affiliatepress_total_commission;
                    $affiliate['how_will_promote'] = (isset($affiliatepress_single_affiliate['ap_affiliates_promote_us']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_promote_us']):'';
                    $affiliate['created_date'] = (!empty($affiliatepress_single_affiliate['ap_affiliates_created_at']))?stripslashes_deep($affiliatepress_single_affiliate['ap_affiliates_created_at']):""; ;
                    $affiliates[] = $affiliate;

                    
                }
            }

            $affiliatepress_exports_data = $affiliates;

            $affiliatepress_columns = array(
                'ap_affiliates_id'              => 'Affiliate ID', // No need to add Text domain
                'ap_affiliates_user_name'       => 'Username',
                'email'                         => 'User Email',
                'first_name'                    => 'First Name',
                'last_name'                     => 'Last Name',
                'affiliate_payment_email'       => 'Payout Email',
                'unpaid_earning'                => 'Unpaid Earnings',
                'paid_earning'                  => 'Paid Earnings',
                'website'                       => 'Website',
                'status'                        => 'Status',
                'total_visit'                   => 'Total Visit',
                'convert_user'                  => 'Converted',
                'how_will_promote'               => 'How Will You Promote Us?',
                'created_date'                  => 'Date Registered',
            );

            $affiliatepress_filename = 'AffiliatePress-export-affiliates.csv'; //phpcs:ignore

                    

            ob_start();
            if (ob_get_length()) {
                ob_end_clean();
            }
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

        
        /**
         * Function for check user exists or not
         *
         * @param  integer $affiliatepress_user_id
         * @return boolean
        */
        function affiliatepress_check_user_exists_by_id($affiliatepress_user_id){
            $affiliatepress_user = get_userdata( $affiliatepress_user_id );
            return ( $affiliatepress_user !== false );
        }
        
        /**
         * Function for check valid affiliate user
         *
         * @param  integer $affiliatepress_affiliates_id
         * @return boolean
        */
        function affiliatepress_is_valid_affiliate($affiliatepress_affiliates_id = 0){            
            global $affiliatepress_tbl_ap_affiliates,$wpdb;
            $affiliatepress_flag = false;
            if($affiliatepress_affiliates_id){                
                $affiliatepress_rec = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, '*', 'WHERE ap_affiliates_id  = %d', array( $affiliatepress_affiliates_id ), '', '', '', false, true,ARRAY_A);
                if(!empty($affiliatepress_rec)){                    
                    $affiliatepress_affiliates_status = (isset($affiliatepress_rec['ap_affiliates_status']))?$affiliatepress_rec['ap_affiliates_status']:'';
                    if($affiliatepress_affiliates_status == 1){
                        $affiliatepress_affiliates_user_id = (isset($affiliatepress_rec['ap_affiliates_user_id']))?$affiliatepress_rec['ap_affiliates_user_id']:0;
                        $affiliatepress_has_user_exists = $this->affiliatepress_check_user_exists_by_id($affiliatepress_affiliates_user_id);
                        if($affiliatepress_has_user_exists){
                            $affiliatepress_flag = true;
                        }                        
                    }
                }
                
            }
            return $affiliatepress_flag;
        }
                
        /**
         * Function for send affiliate email when change affiliate status
         *
         * @param  integer $affiliatepress_affiliates_id
         * @param  integer $affiliatepress_affiliates_status
         * @param  integer $affiliatepress_old_ap_affiliates_status
         * @return void
        */
        function affiliatepress_after_affiliate_status_change_func($affiliatepress_affiliates_id,$affiliatepress_affiliates_status,$affiliatepress_old_ap_affiliates_status){

            global $affiliatepress_email_notifications;
            $affiliatepress_send_affiliate_email = true;
            

            if(defined('DOING_AJAX') && DOING_AJAX && isset($_POST['ap_send_email']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'ap_wp_nonce')){// phpcs:ignore

                if(!current_user_can('affiliatepress_affiliates')){
                    $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                    $response['variant'] = 'error';
                    $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                    $response['msg'] = $affiliatepress_error_msg; 
                    wp_send_json( $response );
                    die;                
                }

                $affiliatepress_send_email = (isset($_POST['ap_send_email']))?sanitize_text_field($_POST['ap_send_email']):'';// phpcs:ignore 
                $affiliatepress_action = (isset($_POST['action']))?sanitize_text_field($_POST['action']):'';// phpcs:ignore 
                $affiliatepress_action = (isset($_POST['action']))?sanitize_text_field($_POST['action']):'';// phpcs:ignore             
                if(isset($_POST['ap_send_email']) && $affiliatepress_send_email == "false" && $affiliatepress_action == "affiliatepress_add_affiliate"){ // phpcs:ignore 
                    $affiliatepress_affiliates_id = (isset($_POST['ap_affiliates_id']))?intval($_POST['ap_affiliates_id']):0; // phpcs:ignore 
                    if($affiliatepress_affiliates_id == 0){
                        $affiliatepress_send_affiliate_email = false;
                    }                
                }

            }

            if($affiliatepress_send_affiliate_email){
                $affiliatepress_notification_type = '';
                if($affiliatepress_affiliates_status == 1){
                    $affiliatepress_notification_type = 'affiliate_account_approved';
                }else if($affiliatepress_affiliates_status == 2){
                    $affiliatepress_notification_type = 'affiliate_account_pending';
                }else if($affiliatepress_affiliates_status == 3){
                    $affiliatepress_notification_type = 'affiliate_account_rejected';
                }
                if(!empty($affiliatepress_notification_type) && $affiliatepress_affiliates_id){
                    $affiliatepress_email_notifications->affiliatepress_send_email_notification($affiliatepress_notification_type,'affiliate',array('ap_affiliates_id'=>$affiliatepress_affiliates_id));
                }    
            }
            
        }

        /**
         * Function for remove affiliate avatar
         *
         * @return json
        */
        function affiliatepress_remove_affiliate_avatar_func(){
            global $wpdb;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'remove_affiliate_avatar', true, 'ap_wp_nonce' );            
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
            if(!current_user_can('affiliatepress_affiliates')){
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

            if (! empty($_POST) && ! empty($_POST['upload_file_url']) ) { // phpcs:ignore 
                $affiliatepress_uploaded_avatar_url = esc_url_raw($_POST['upload_file_url']); // phpcs:ignore
                $affiliatepress_file_name_arr       = explode('/', $affiliatepress_uploaded_avatar_url);
                $affiliatepress_file_name           = $affiliatepress_file_name_arr[ count($affiliatepress_file_name_arr) - 1 ];
                if( file_exists( AFFILIATEPRESS_TMP_IMAGES_DIR . '/' . basename($affiliatepress_file_name) ) ){
                    wp_delete_file(AFFILIATEPRESS_TMP_IMAGES_DIR . '/' . basename($affiliatepress_file_name)); // phpcs:ignore
                }
            }
            die;             
        }

        /**
         * Function for get edit affiliate info 
         *
         * @return json
        */
        function affiliatepress_edit_affiliate_func(){
            global $wpdb, $affiliatepress_tbl_ap_affiliates,$AffiliatePress;
            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'edit_affiliate', true, 'ap_wp_nonce' );
            
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

            if(!current_user_can('affiliatepress_affiliates')){
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
            
            $affiliatepress_affiliates_id  =  isset($_POST['edit_id']) ? intval($_POST['edit_id']) : ''; // phpcs:ignore 

            $affiliatepress_affiliates_data = array();
            if(!empty($affiliatepress_affiliates_id)){
                
                $affiliates = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, '*', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliates_id ), '', '', '', false, true,ARRAY_A);

                if(!empty($affiliates)){
                    $affiliatepress_affiliates_data = (array) $affiliates;
                    $affiliatepress_affiliates_data['ap_affiliates_id']            = intval($affiliates['ap_affiliates_id']);
                    $affiliatepress_affiliates_data['ap_affiliates_user_id']       = intval($affiliates['ap_affiliates_user_id']);
                    $affiliatepress_affiliates_data['ap_affiliates_payment_email'] = esc_html($affiliates['ap_affiliates_payment_email']);
                    $affiliatepress_affiliates_data['ap_affiliates_website']       = stripslashes_deep($affiliates['ap_affiliates_website']);
                    $affiliatepress_affiliates_data['ap_affiliates_status']        = esc_html($affiliates['ap_affiliates_status']);

                    $affiliatepress_affiliates_user_avatar = !empty( $affiliates['ap_affiliates_user_avatar'] ) ? esc_url( $affiliates['ap_affiliates_user_avatar'] ) : '';
                    $affiliatepress_image_url = esc_url(AFFILIATEPRESS_IMAGES_URL . '/default-avatar.jpg');
                    if(!empty($affiliatepress_affiliates_user_avatar)){
                        $affiliatepress_affiliates_user_avatar = esc_url(AFFILIATEPRESS_UPLOAD_URL.'/'.basename($affiliatepress_affiliates_user_avatar));
                    }
                    $affiliatepress_affiliates_data['ap_affiliates_user_avatar']   = (!empty($affiliatepress_affiliates_user_avatar))?esc_url($affiliatepress_affiliates_user_avatar):'';
                    $affiliatepress_affiliates_data['ap_affiliates_promote_us']    = stripslashes_deep($affiliates['ap_affiliates_promote_us']);
                    $affiliatepress_affiliates_data['affiliate_user_name']         = $AffiliatePress->affiliatepress_get_affiliate_user_name_by_id($affiliates['ap_affiliates_user_id']);
                    $affiliatepress_note = html_entity_decode( $affiliatepress_affiliates_data['ap_affiliates_note'], ENT_QUOTES, 'UTF-8' );
                    $affiliatepress_affiliates_data['ap_affiliates_note']          = $affiliatepress_note;

                    if(empty($affiliatepress_affiliates_data['affiliate_user_name'])){
                        $affiliatepress_affiliates_data['ap_affiliates_user_id'] = "";
                    }
                    
                    $affiliatepress_affiliates_data = apply_filters('affiliatepress_modify_edit_affiliate_data',$affiliatepress_affiliates_data,$affiliates,$affiliatepress_affiliates_id);
                    /* Filter for modified edit affiliate data for pro  */

                    $response['variant'] = 'success';
                    $response['affiliates'] = $affiliatepress_affiliates_data;
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Affiliate Data.', 'affiliatepress-affiliate-marketing');                    
                }

            }
            echo wp_json_encode($response);
            exit;

        }

        /**
         * Function for upload affiliate avatar
         *
         * @return json
        */
        function affiliatepress_upload_affiliate_avatar_func(){


            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'affiliate_avatar_image_upload', true, 'affiliatepress_upload_affiliate_avatar' );            
            $response = array();
            $response['variant'] = 'error';
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

            if(!current_user_can('affiliatepress_affiliates')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_upload_affiliate_avatar');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            } 

            $return_data = array(
                'error'            => 0,
                'msg'              => '',
                'upload_url'       => '',
                'upload_file_name' => '',
            );//phpcs:ignore
            $affiliatepress_fileupload_obj = new affiliatepress_fileupload_class( $_FILES['file'] ); //phpcs:ignore
            if (! $affiliatepress_fileupload_obj ) {
                $return_data['error'] = 1;
                $return_data['msg']   = $affiliatepress_fileupload_obj->error_message;
            }


            $affiliatepress_fileupload_obj->affiliatepress_check_cap          = true;
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
         * Ajax request for get wordpress user except user who has role of affiliatepress-affiliate-user
         *
         * @return json
         */
        function affiliatepress_get_wpuser(){
            global $wpdb, $AffiliatePress,$affiliatepress_tbl_ap_affiliates;            
            $response              = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'search_user', true, 'ap_wp_nonce' );
            $response = array();
            $response['variant'] = 'error';
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

            if(!current_user_can('affiliatepress_affiliates')){
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
            $affiliatepress_search_user_str = ! empty( $_REQUEST['search_user_str'] ) ? sanitize_text_field( $_REQUEST['search_user_str'] ) : ''; // phpcs:ignore 
            $wordpress_user_id = ! empty( $_REQUEST['wordpress_user_id'] ) ? intval( $_REQUEST['wordpress_user_id'] ) : ''; // phpcs:ignore 

			if(!empty($affiliatepress_search_user_str)) {                    
                $affiliatepress_args  = array(
                    'search' => '*'.$affiliatepress_search_user_str.'*',
					'fields' => array( 'user_login','id'),
                    'role__not_in' => array( 'administrator','affiliatepress-affiliate-user'),
                );
                $wpusers             = get_users($affiliatepress_args);
                $affiliatepress_existing_user_data = $affiliatepress_existing_users_data = array();                
                if (!empty($wpusers) ) {
                    foreach ( $wpusers as $wpuser ) {

                        $affiliatepress_user_id = $wpuser->id;                        
                        $affiliatepress_is_user_exist = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, '*', 'WHERE ap_affiliates_user_id = %d', array( $affiliatepress_user_id ), '', '', '', true, false,ARRAY_A);

                        if(empty($affiliatepress_is_user_exist)){
                            $affiliatepress_user                  = array();
                            $affiliatepress_user['value']         = intval($wpuser->id);
                            $affiliatepress_user['label']         = esc_html($wpuser->user_login);
                            $affiliatepress_existing_users_data[] = $affiliatepress_user;
                        }

                    }
                }         
                $affiliatepress_existing_user_data[] = array(
                    'category'     => esc_html__('Select Existing User', 'affiliatepress-affiliate-marketing'),
                    'wp_user_data' => $affiliatepress_existing_users_data,
                );
                $response['variant']               = 'success';
                $response['users']                 = $affiliatepress_existing_user_data;
                $response['title']                 = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']                   = esc_html__('Affiliate Data.', 'affiliatepress-affiliate-marketing');
            }     
            wp_send_json($response);
        }
                
        /**
         * Function for affiliate bulk action perform
         *
         * @return json
        */
        function affiliatepress_affiliate_bulk_action_func(){            
            global $wpdb, $affiliatepress_tbl_ap_affiliates,$AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'delete_affiliate', true, 'ap_wp_nonce' );            
            $response = array();
            $response['variant'] = 'error';
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

            if(!current_user_can('affiliatepress_affiliates')){
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

            if (! empty($_POST['bulk_action']) && sanitize_text_field($_POST['bulk_action']) == 'delete' ) { // phpcs:ignore 

                // phpcs:ignore santize in below function affiliatepress_array_sanatize_integer_field
                $affiliatepress_delete_ids = (isset($_POST['ids']))?stripslashes_deep($_POST['ids']):'';// phpcs:ignore                 
                if(!empty($affiliatepress_delete_ids)){
                    $affiliatepress_delete_ids = json_decode($affiliatepress_delete_ids, true);
                }
                if(is_array($affiliatepress_delete_ids)){

                    $affiliatepress_delete_ids = ! empty($affiliatepress_delete_ids) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_integer_field' ), $affiliatepress_delete_ids) : array(); // phpcs:ignore
                    if (!empty($affiliatepress_delete_ids)) {
                        foreach ( $affiliatepress_delete_ids as $affiliatepress_delete_key => $affiliatepress_delete_val ) {
                            if (is_array($affiliatepress_delete_val) ) {
                                $affiliatepress_delete_val = intval($affiliatepress_delete_val['item_id']);
                            }else{  
                                $affiliatepress_delete_val = intval($affiliatepress_delete_val);
                            }
                            $return = $this->affiliatepress_delete_affiliate($affiliatepress_delete_val);                            
                            if ($return ) {                                
                                $response['variant'] = 'success';
                                $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                                $response['msg']     = esc_html__('Affiliates have been deleted successfully.', 'affiliatepress-affiliate-marketing');
                            } else {
                                $response['variant'] = 'warning';
                                $response['title']   = esc_html__('Warning', 'affiliatepress-affiliate-marketing');
                                $response['msg']     = esc_html__('Could not delete affiliate. This affiliate not deleted.', 'affiliatepress-affiliate-marketing');
                                wp_send_json($response);
                                exit;
                            }                                                
                        }
                    }

                }

            }
            wp_send_json($response);
        }

             
        /**
         * Function for delete single affiliate
         *
         * @param  integer $affiliatepress_affiliates_id
         * @return json
         */
        function affiliatepress_delete_affiliate($affiliatepress_affiliates_id = ''){
            global $wpdb, $affiliatepress_tbl_ap_affiliates,$AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_affiliate_visits;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'delete_affiliate', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_affiliates')){
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
            
            if(empty($affiliatepress_affiliates_id)){
                $affiliatepress_affiliates_id = (isset($_POST['ap_affiliates_id']))?intval($_POST['ap_affiliates_id']):0; // phpcs:ignore 
            }
            if($affiliatepress_affiliates_id){
                
                $this->affiliatepress_affiliate_delete_data($affiliatepress_affiliates_id);

                $response['variant'] = 'success';
                $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Affiliate has been deleted successfully.', 'affiliatepress-affiliate-marketing');
                $return              = true;
                if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'affiliatepress_delete_affiliate' ) { // phpcs:ignore
                    wp_send_json($response);
                }
                return $return;
            }
            $affiliatepress_error_msg = esc_html__( 'Affiliates not deleted.', 'affiliatepress-affiliate-marketing');
            $response['variant'] = 'warning';
            $response['title']   = esc_html__('warning', 'affiliatepress-affiliate-marketing');
            $response['msg']     = $affiliatepress_error_msg;
            $return              = false;
            if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'affiliatepress_delete_affiliate' ) { // phpcs:ignore
                wp_send_json($response);
            }
            return $return;

        }

        function affiliatepress_affiliate_delete_data($affiliatepress_affiliates_id){

            global $affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_affiliate_visits,$affiliatepress_tbl_ap_affiliate_links,$affiliatepress_tbl_ap_customer;

            $affiliatepress_affiliates_user_id = $this->affiliatepress_select_record(true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_id', 'WHERE ap_affiliates_id  = %d', array( $affiliatepress_affiliates_id), '', '', '', true, false,ARRAY_A);

            do_action('affiliatepress_before_delete_affiliate', $affiliatepress_affiliates_id);

            $this->affiliatepress_delete_record($affiliatepress_tbl_ap_affiliates, array( 'ap_affiliates_id' => $affiliatepress_affiliates_id ), array('%d'));
            $this->affiliatepress_delete_record($affiliatepress_tbl_ap_affiliate_commissions, array( 'ap_affiliates_id' => $affiliatepress_affiliates_id ), array('%d'));
            $this->affiliatepress_delete_record($affiliatepress_tbl_ap_affiliate_visits, array( 'ap_affiliates_id' => $affiliatepress_affiliates_id ), array('%d'));
            $this->affiliatepress_delete_record($affiliatepress_tbl_ap_affiliate_links, array( 'ap_affiliates_id' => $affiliatepress_affiliates_id ), array('%d'));
            $this->affiliatepress_delete_record($affiliatepress_tbl_ap_customer, array( 'ap_affiliates_id' => $affiliatepress_affiliates_id ), array('%d'));

            if($affiliatepress_affiliates_user_id){
                $this->affiliatepress_remove_affiliate_user_role($affiliatepress_affiliates_user_id);
            }                
                                
            do_action('affiliatepress_after_delete_affiliate',$affiliatepress_affiliates_id);
        }

        /**
         * Function for change affiliate status 
         *
         * @return json
        */
        function affiliatepress_change_affiliate_status_func(){

            global $wpdb, $affiliatepress_tbl_ap_affiliates,$AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'change_affiliate_status', true, 'ap_wp_nonce' );
            
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Affiliates status has not been updated successfully', 'affiliatepress-affiliate-marketing');
            
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_affiliates')){
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

            $affiliatepress_update_id   = isset($_POST['update_id']) ? intval($_POST['update_id']) : ''; // phpcs:ignore 
            $affiliatepress_new_status   = isset($_POST['new_status']) ? intval($_POST['new_status']) : 0; // phpcs:ignore 
            $affiliatepress_old_status   = isset($_POST['old_status']) ? intval($_POST['old_status']) : 0; // phpcs:ignore 
            if($affiliatepress_update_id && $affiliatepress_new_status){

                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliates, array('ap_affiliates_status'=>$affiliatepress_new_status), array( 'ap_affiliates_id' => $affiliatepress_update_id ));

                if($affiliatepress_new_status == 3){
                    $this->affiliatepress_update_commisison_status($affiliatepress_update_id , $affiliatepress_new_status,$affiliatepress_old_status);
                }
                $response['id']         = $affiliatepress_update_id;
                $response['variant']    = 'success';
                $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']        = esc_html__('Affiliates status has been updated successfully.', 'affiliatepress-affiliate-marketing');

                do_action('affiliatepress_after_affiliate_status_change',$affiliatepress_update_id,$affiliatepress_new_status,'');

            }

            wp_send_json($response);
            exit;

        }

        function affiliatepress_update_commisison_status($affiliatepress_affiliate_id,$affiliatepress_new_status,$affiliatepress_old_status){

            global $wpdb,$affiliatepress_tbl_ap_affiliate_commissions;
        
            $affiliatepress_commission_ids = $wpdb->get_col($wpdb->prepare( "SELECT ap_commission_id  FROM {$affiliatepress_tbl_ap_affiliate_commissions} WHERE ap_affiliates_id = %d  AND ap_commission_status IN (1,2)", $affiliatepress_affiliate_id ));// phpcs:ignore WordPress.DB.DirectDatabaseQuery ,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_commissions is table name already prepare in "affiliatepress_tablename_prepare". False Positive alarm
        
            $wpdb->query($wpdb->prepare("UPDATE {$affiliatepress_tbl_ap_affiliate_commissions} SET ap_commission_status = %d WHERE ap_affiliates_id = %d  AND ap_commission_status IN (1,2)", $affiliatepress_new_status, $affiliatepress_affiliate_id ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery ,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_commissions is table name already prepare in "affiliatepress_tablename_prepare". False Positive alarm
        
            if(!empty($affiliatepress_commission_ids)){
                foreach($affiliatepress_commission_ids as $affiliatepress_commission_id){
                    do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id, $affiliatepress_new_status,$affiliatepress_old_status, 'backend');
                }
            }
        }

        /**
         * affiliate module on load methods
         *
         * @param  string $affiliatepress_affiliates_dynamic_on_load_methods
         * @return string
         */
        function affiliatepress_affiliates_dynamic_on_load_methods_func($affiliatepress_affiliates_dynamic_on_load_methods){
            $affiliatepress_affiliates_dynamic_on_load_methods.='
                this.loadAffiliate().catch(error => {
                    console.error(error)
                });            
            ';
            return $affiliatepress_affiliates_dynamic_on_load_methods;
        }        
      

        /**
         * Function for get affiliate data
         *
         * @return json
         */
        function affiliatepress_get_affiliates(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliates,$AffiliatePress,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_affiliate_visits, $affiliatepress_tbl_ap_affiliate_report;
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

            if(!current_user_can('affiliatepress_affiliates')){
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
            $affiliatepress_offset      = (!empty($affiliatepress_currentpage) && $affiliatepress_currentpage > 1 ) ? ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage ) : 0;
            $affiliatepress_order       = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : ''; // phpcs:ignore
            $affiliatepress_order_by    = isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : ''; // phpcs:ignore
            

            $affiliatepress_search_query = '';

            $affiliatepress_where_clause = " WHERE 1 = 1 ";
            if (!empty($_REQUEST['search_data']) ) {// phpcs:ignore 
               
                if (!empty($_REQUEST['search_data']['ap_affiliates_status']) && sanitize_text_field($_REQUEST['search_data']['ap_affiliates_status']) != 'all' ) { // phpcs:ignore                    
                    $affiliatepress_where_clause.= $wpdb->prepare( " AND affiliate.ap_affiliates_status = %d", intval($_REQUEST['search_data']['ap_affiliates_status']) );// phpcs:ignore 
                }                
                if (!empty($_REQUEST['search_data']['ap_affiliates_user']) ) {// phpcs:ignore 
                    
                    $affiliatepress_search_id   = intval($_REQUEST['search_data']['ap_affiliates_user']);// phpcs:ignore

                    $affiliatepress_where_clause.= $wpdb->prepare( " AND (affiliate.ap_affiliates_id = %d) ", $affiliatepress_search_id);
                } 

            }  

            $affiliatepress_user_table = $this->affiliatepress_tablename_prepare($wpdb->users); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->users contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $wp_usermeta_table = $this->affiliatepress_tablename_prepare($wpdb->usermeta); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->usermeta contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_tbl_ap_affiliates_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            
            $affiliatepress_get_total_affiliates = intval($wpdb->get_var("SELECT count(affiliate.ap_affiliates_id) FROM {$affiliatepress_tbl_ap_affiliates_temp} as affiliate {$affiliatepress_search_query}  {$affiliatepress_where_clause}")); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates_temp is a table name already prepare by affiliatepress_tablename_prepare function. false alarm

            $affiliatepress_pagination_count = ceil(intval($affiliatepress_get_total_affiliates) / $affiliatepress_perpage);
            
            if($affiliatepress_currentpage > $affiliatepress_pagination_count && $affiliatepress_pagination_count > 0){
                $affiliatepress_currentpage = $affiliatepress_pagination_count;
                $affiliatepress_offset = ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage );
            }
            if(empty($affiliatepress_order)){
                $affiliatepress_order = 'DESC';
            }
            if(empty($affiliatepress_order_by)){
                $affiliatepress_order_by = 'affiliate.ap_affiliates_id';
            }      
            
            if($affiliatepress_order_by == "first_name"){
                $affiliatepress_order_by = 'affiliate.ap_affiliates_first_name';
            }

            if($affiliatepress_order_by == "total_visit"){
                $affiliatepress_order_by = 'total_visit';
            }

            if($affiliatepress_order_by == "converted_user"){
                $affiliatepress_order_by = 'converted_user';
            }

            if($affiliatepress_order_by == "ap_affiliates_created_at"){
                $affiliatepress_order_by = 'ap_affiliates_created_at';
            }

            $affiliatepress_sql = "  SELECT affiliate.*, COALESCE(SUM(report.ap_affiliate_report_visits), 0) AS total_visit,  COALESCE(SUM(report.ap_affiliate_report_total_commission), 0) AS converted_user, COALESCE(SUM(report.ap_affiliate_report_paid_commission_amount), 0) AS affiliatepress_paid_earning,  COALESCE(SUM(report.ap_affiliate_report_unpaid_commission_amount), 0) AS affiliatepress_unpaid_earning FROM {$affiliatepress_tbl_ap_affiliates_temp} AS affiliate  LEFT JOIN {$affiliatepress_tbl_ap_affiliate_report} AS report ON report.ap_affiliates_id = affiliate.ap_affiliates_id  {$affiliatepress_where_clause} GROUP BY affiliate.ap_affiliates_id ORDER BY {$affiliatepress_order_by} {$affiliatepress_order} LIMIT {$affiliatepress_offset}, {$affiliatepress_perpage}";
            $affiliatepress_affiliates_record = $wpdb->get_results($affiliatepress_sql, ARRAY_A);// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_form_fields_temp is table name already prepare in "affiliatepress_tablename_prepare". False Positive alarm


            $affiliates = array();
            if (! empty($affiliatepress_affiliates_record) ) {
                $affiliatepress_counter = 1;
                foreach ( $affiliatepress_affiliates_record as $affiliatepress_key=>$affiliatepress_single_affiliate ) {

                    $affiliate = $affiliatepress_single_affiliate;
                    $affiliate['ap_affiliates_id']    = intval($affiliatepress_single_affiliate['ap_affiliates_id']);
                    $affiliatepress_user_id = $affiliatepress_single_affiliate['ap_affiliates_user_id'];
                    $affiliate['avatar_url']  = '';
                    $affiliates_avatar =  $affiliatepress_single_affiliate['ap_affiliates_user_avatar'];
                    if(empty($affiliates_avatar)){
                        $affiliates_avatar =  AFFILIATEPRESS_IMAGES_URL . '/default-avatar.jpg';
                    }else{
                        $affiliate['avatar_url']  = AFFILIATEPRESS_UPLOAD_URL.'/'.basename($affiliatepress_single_affiliate['ap_affiliates_user_avatar']);
                        $affiliates_avatar = AFFILIATEPRESS_UPLOAD_URL.'/'.basename($affiliatepress_single_affiliate['ap_affiliates_user_avatar']);
                    }   
                    
                    $affiliatepress_user_first_name =  esc_html($affiliatepress_single_affiliate['ap_affiliates_first_name']);
                    $affiliatepress_user_last_name  =  esc_html($affiliatepress_single_affiliate['ap_affiliates_last_name']);

                    $ap_affiliates_user_email = esc_html($affiliatepress_single_affiliate['ap_affiliates_user_email']);

                    $affiliatepress_full_name = $affiliatepress_user_first_name." ".$affiliatepress_user_last_name;
                    
                    $affiliatepress_affiliate_id = $affiliatepress_single_affiliate['ap_affiliates_id'];

                    $affiliatepress_paid_earning = (isset($affiliatepress_single_affiliate['affiliatepress_paid_earning']))?floatval($affiliatepress_single_affiliate['affiliatepress_paid_earning']):0;
                    $affiliatepress_unpaid_earning = (isset($affiliatepress_single_affiliate['affiliatepress_unpaid_earning']))?floatval($affiliatepress_single_affiliate['affiliatepress_unpaid_earning']):0;
                    $affiliatepress_total_commission = (isset($affiliatepress_single_affiliate['converted_user']))?intval($affiliatepress_single_affiliate['converted_user']):0;
                    $affiliatepress_total_visits = (isset($affiliatepress_single_affiliate['total_visit']))?intval($affiliatepress_single_affiliate['total_visit']):0;
                    
                    
                    $affiliatepress_paid_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_paid_earning,2));

                    $affiliatepress_unpaid_earning = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol(round($affiliatepress_unpaid_earning,2));

                    $affiliatepress_affiliate_link = $AffiliatePress->affiliatepress_get_affiliate_common_link($affiliatepress_single_affiliate['ap_affiliates_id']);

                    $affiliatepress_affiliate_link = apply_filters('affiliatepress_modify_affiliate_link' , $affiliatepress_affiliate_link , $affiliatepress_single_affiliate['ap_affiliates_id']);
                    $affiliatepress_default_commission_rate = $this->affiliatepress_get_current_affiliate_rate($affiliatepress_affiliate_id);
                    $affiliatepress_affiliate_create_date = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_single_affiliate['ap_affiliates_created_at']);

                    $affiliate['affiliates_link']    = $affiliatepress_affiliate_link;
                    $affiliate['affiliates_avatar']  = esc_url($affiliates_avatar);
                    $affiliate['full_name']             = esc_html($affiliatepress_full_name);
                    $affiliate['user_email']            = esc_html($ap_affiliates_user_email);
                    $affiliate['change_status_loader']  = ''; 
                    $affiliate['paid_earning']          = $affiliatepress_paid_earning;
                    $affiliate['unpaid_earning']        = $affiliatepress_unpaid_earning;
                    $affiliate['total_visit']           = $affiliatepress_total_visits;
                    $affiliate['converted_user']        = $affiliatepress_total_commission;
                    $affiliate['current_commission_rate']        = $affiliatepress_default_commission_rate;
                    $affiliate['affiliate_created_date_formated']        = $affiliatepress_affiliate_create_date;

                    $affiliate['row_class']  = '';
                    $affiliate = apply_filters('affiliatepress_modify_affiliate_manage_row', $affiliate, $affiliatepress_single_affiliate); 

                    $affiliates[] = $affiliate;
                }
            }
            
            $affiliates = apply_filters('affiliatepress_modify_affiliates_listing_data', $affiliates); // phpcs:ignore WordPress.Security.NonceVerification

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');            
            $response['items'] = $affiliates;
            $response['total'] = $affiliatepress_get_total_affiliates;
            $response['pagination_count'] = $affiliatepress_pagination_count;
            wp_send_json($response);
            exit;            
        }

        /**
         * Function For Remove Affiliate User Role & Relation
         *
         * @param  integer $affiliatepress_user_id
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
         * @param  integer $affiliatepress_user_id
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
         * Function for add affiliate
         *
         * @return json
        */
        function affiliatepress_add_affiliate_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_affiliate_form_fields,$AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'add_affiliate', true, 'ap_wp_nonce' );
            $response = array();
            $response['variant'] = 'error';
            $response['id']      = '';
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

            
            if(!current_user_can('affiliatepress_affiliates')){
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


            $affiliatepress_update_id   = isset($_POST['ap_affiliates_id']) ? intval($_POST['ap_affiliates_id']) : ''; // phpcs:ignore
            $affiliatepress_affiliates_user_id  = (isset($_POST['ap_affiliates_user_id']) && $_POST['ap_affiliates_user_id'] != 'add_new') ? intval($_POST['ap_affiliates_user_id']) : sanitize_text_field($_POST['ap_affiliates_user_id']); // phpcs:ignore 
            $affiliatepress_affiliates_status   = isset($_POST['ap_affiliates_status']) ? intval($_POST['ap_affiliates_status']) : 2; // phpcs:ignore 
            $affiliatepress_affiliates_note = isset($_POST['ap_affiliates_note'])? sanitize_textarea_field( wp_unslash($_POST['ap_affiliates_note']) ): '';
            if(empty($affiliatepress_update_id) && $affiliatepress_update_id == 0){
                $affiliatepress_affiliates_payment_email = ! empty($_POST['ap_affiliates_payment_email_add']) ? trim(sanitize_text_field($_POST['ap_affiliates_payment_email_add'])) : ''; // phpcs:ignore
            }else{
                $affiliatepress_affiliates_payment_email = ! empty($_POST['ap_affiliates_payment_email_edit']) ? trim(sanitize_text_field($_POST['ap_affiliates_payment_email_edit'])) : ''; // phpcs:ignore
            }
            $affiliatepress_affiliates_website = ! empty($_POST['ap_affiliates_website']) ? trim(sanitize_text_field($_POST['ap_affiliates_website'])) : ''; // phpcs:ignore 
            $affiliatepress_affiliates_promote_us = ! empty($_POST['ap_affiliates_promote_us']) ? trim(sanitize_text_field($_POST['ap_affiliates_promote_us'])) : ''; // phpcs:ignore
            $affiliatepress_avatar_url = (isset($_POST['avatar_url'])) ? trim(sanitize_text_field($_POST['avatar_url'])) : ''; // phpcs:ignore 
            
            $affiliatepress_affiliates_user_avatar = (isset($_POST['ap_affiliates_user_avatar'])) ? trim(sanitize_text_field($_POST['ap_affiliates_user_avatar'])) : ''; // phpcs:ignore
            if($affiliatepress_affiliates_user_id == 'add_new') {

                $affiliatepress_username         = ! empty($_POST['username']) ? sanitize_text_field($_POST['username']) : ''; // phpcs:ignore
                $affiliatepress_firstname        = ! empty($_POST['firstname']) ? trim(sanitize_text_field($_POST['firstname'])) : ''; // phpcs:ignore
                $affiliatepress_lastname         = ! empty($_POST['lastname']) ? trim(sanitize_text_field($_POST['lastname'])) : ''; // phpcs:ignore 
                $affiliatepress_email            = ! empty($_POST['email']) ? sanitize_email($_POST['email']) : ''; // phpcs:ignore
                $affiliatepress_user_pass        = wp_generate_password(12, false);
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $_POST['search_data'] contains password and will be hashed using wp_create_user function. 
                $affiliatepress_password = ! empty($_POST['password']) ? sanitize_text_field($_POST['password']) : '';// phpcs:ignore

                $affiliatepress_confirm_password_field_settings = $AffiliatePress->affiliatepress_get_settings('confirm_password_field', 'field_settings');
                $affiliatepress_confirm_password_field_settings = !empty($affiliatepress_confirm_password_field_settings) ? maybe_unserialize($affiliatepress_confirm_password_field_settings) : array();
    
                if(!empty($affiliatepress_confirm_password_field_settings) && is_array($affiliatepress_confirm_password_field_settings)){
    
                    $affiliatepress_is_display_confirm_password = isset($affiliatepress_confirm_password_field_settings['enable_confirm_password']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['enable_confirm_password']) : '';
                    $affiliatepress_confirm_password_validation_msg = isset($affiliatepress_confirm_password_field_settings['confirm_password_validation_msg']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_validation_msg']) : '';

                    if($affiliatepress_is_display_confirm_password == "true"){
                        $affiliatepress_confirm_password = ! empty($_POST['confirm_password']) ? sanitize_text_field(wp_unslash($_POST['confirm_password'])) : ''; 
                        if ($affiliatepress_password !== $affiliatepress_confirm_password) {
                            $response['msg'] = $affiliatepress_confirm_password_validation_msg.'.';
                            wp_send_json($response);
                            die();
                        }
                    }

                }

                if (strlen($affiliatepress_firstname) > 255 ) {
                    $response['msg'] = esc_html__('Firstname is too long...', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);
                    die();
                }
                if (strlen($affiliatepress_lastname) > 255 ) {
                    $response['msg'] = esc_html__('Lastname is too long...', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);
                    die();
                }
                if (strlen($affiliatepress_email) > 255 ) {
                    $response['msg'] = esc_html__('Email address is too long...', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);
                    die();
                }
                if (email_exists($affiliatepress_email) ) {
                    $response['msg'] = esc_html__('Email address is already exists', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);
                    die();
                }
                if (username_exists($affiliatepress_username) ) {
                    $response['msg'] = esc_html__('Username is already exists', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);
                    die();
                }


            }
            if ($affiliatepress_affiliates_user_id == 0 && $affiliatepress_affiliates_user_id != 'add_new') {
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Affiliate WordPress User is required', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                die();                
            }
            if (strlen($affiliatepress_affiliates_payment_email) > 255 ) {
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Payment email is too long...', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                die();
            }
            $affiliatepress_fields = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_form_fields, '*', 'WHERE  ap_form_field_name <> %s', array( 'terms_and_conditions'), '', 'order by ap_field_position ASC', '', false, false,ARRAY_A);      
            
            $affiliatepress_paymnet_show_register = 0;
            $affiliatepress_paymnet_show_panel = 0;
            foreach ($affiliatepress_fields as $key => $affiliatepress_field) {
                if(isset($affiliatepress_field['ap_form_field_name']) && $affiliatepress_field['ap_form_field_name'] == "ap_affiliates_payment_email"){
                    $affiliatepress_paymnet_show_register = isset($affiliatepress_field['ap_show_signup_field']) ? intval($affiliatepress_field['ap_show_signup_field']) : 0;
                    $affiliatepress_paymnet_show_panel = isset( $affiliatepress_field['ap_show_profile_field']) ? intval( $affiliatepress_field['ap_show_profile_field']) : 0;
                }
                
            }
            if(empty($affiliatepress_update_id) && $affiliatepress_update_id == 0){
                if($affiliatepress_paymnet_show_register == "1"){
                    if(empty($affiliatepress_affiliates_payment_email) || !is_email($affiliatepress_affiliates_payment_email)){
                        $response['variant'] = 'error';
                        $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                        $response['msg']     = esc_html__('Please Enter valid payment email.', 'affiliatepress-affiliate-marketing');
                        wp_send_json($response);
                        die();                
                    }
                }
                
            }else{
                if($affiliatepress_paymnet_show_panel == "1"){
                    if(empty($affiliatepress_affiliates_payment_email) || !is_email($affiliatepress_affiliates_payment_email)){
                        $response['variant'] = 'error';
                        $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                        $response['msg']     = esc_html__('Please Enter valid payment email.', 'affiliatepress-affiliate-marketing');
                        wp_send_json($response);
                        die();                
                    }
                }
            }
            if (strlen($affiliatepress_affiliates_website) > 255 ) {
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Website is too long...', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                die();
            } 
            if (strlen($affiliatepress_affiliates_promote_us) > 800 ) {
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Promote us is too long...', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                die();
            }
            do_action('affiliatepress_add_affiliate_validation');
            
            if($affiliatepress_affiliates_user_id == 'add_new') {
                $affiliatepress_affiliates_user_id = wp_create_user($affiliatepress_username, $affiliatepress_password, $affiliatepress_email);
                if (!is_wp_error($affiliatepress_affiliates_user_id)) {                   
                    $affiliatepress_display_name = $affiliatepress_firstname.' '.$affiliatepress_lastname;
                    wp_update_user(array(
                        'ID' => $affiliatepress_affiliates_user_id,
                        'display_name' => $affiliatepress_display_name,
                        'first_name' => $affiliatepress_firstname,
                        'last_name' => $affiliatepress_lastname,
                    ));
                } else {
                    $response['msg'] = $affiliatepress_affiliates_user_id->get_error_message();
                    wp_send_json($response);
                    die();                    
                }
            }else{               
                $affiliatepress_firstname = get_user_meta($affiliatepress_affiliates_user_id, 'first_name', true);
                $affiliatepress_lastname = get_user_meta($affiliatepress_affiliates_user_id, 'last_name', true); 
            }
            $affiliatepress_args = array(                
                'ap_affiliates_first_name'      => $affiliatepress_firstname,
                'ap_affiliates_last_name'       => $affiliatepress_lastname,
                'ap_affiliates_payment_email'   => $affiliatepress_affiliates_payment_email,
                'ap_affiliates_website'         => $affiliatepress_affiliates_website,
                'ap_affiliates_promote_us'      => $affiliatepress_affiliates_promote_us,                
                'ap_affiliates_status'          => $affiliatepress_affiliates_status,
                'ap_affiliates_note'            => $affiliatepress_affiliates_note,
            );           
            if($affiliatepress_affiliates_user_id != 'add_new' && $affiliatepress_affiliates_user_id) {
                $affiliatepress_user_info = get_userdata($affiliatepress_affiliates_user_id);
                if($affiliatepress_user_info){
                    $affiliatepress_username = $affiliatepress_user_info->user_login;
                    $affiliatepress_args['ap_affiliates_user_email'] = $affiliatepress_user_info->user_email; 
                    $affiliatepress_args['ap_affiliates_user_name']  = $affiliatepress_username;                    
                }
            }
                
            $affiliatepress_args['ap_affiliates_user_id'] = $affiliatepress_affiliates_user_id;                
            $affiliatepress_user_info = get_userdata($affiliatepress_affiliates_user_id);
            if($affiliatepress_user_info){
                $affiliatepress_username = $affiliatepress_user_info->user_login;
                $affiliatepress_email    = $affiliatepress_user_info->user_email;
                $affiliatepress_args['ap_affiliates_user_name']  = $affiliatepress_username; 
                $affiliatepress_args['ap_affiliates_user_email'] = $affiliatepress_email;
            }    

            $affiliatepress_affiliates_id = '';
            if($affiliatepress_update_id == 0){
                
                $affiliatepress_affiliates_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_affiliates, $affiliatepress_args);

                do_action('affiliatepress_after_add_affiliate',$affiliatepress_affiliates_id);
                
                if($affiliatepress_affiliates_id){                    
                    $this->affiliatepress_add_affiliate_user_role($affiliatepress_affiliates_user_id);                    
                    $response['id']         = $affiliatepress_affiliates_id;
                    $response['variant']    = 'success';
                    $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']        = esc_html__('Affiliates has been added successfully.', 'affiliatepress-affiliate-marketing');                    
                }
                $affiliatepress_old_ap_affiliates_status = '';


                do_action('affiliatepress_after_affiliate_status_change',$affiliatepress_affiliates_id,$affiliatepress_affiliates_status,$affiliatepress_old_ap_affiliates_status);

            }else{

                $affiliatepress_old_ap_affiliates_status = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_status', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_update_id ), '', '', '', true, false,ARRAY_A);

                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliates, $affiliatepress_args, array( 'ap_affiliates_id' => $affiliatepress_update_id ));
                $affiliatepress_affiliates_id       = $affiliatepress_update_id;
                $response['id']         = $affiliatepress_affiliates_id;
                $response['variant']    = 'success';
                $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']        = esc_html__('Affiliates has been updated successfully.', 'affiliatepress-affiliate-marketing');

                $affiliatepress_affiliates_user_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_id', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_update_id ), '', '', '', true, false,ARRAY_A);
                if($affiliatepress_affiliates_user_id){
                    $this->affiliatepress_add_affiliate_user_role($affiliatepress_affiliates_user_id);
                }

                do_action('affiliatepress_after_update_affiliate', $affiliatepress_affiliates_id); // phpcs:ignore WordPress.Security.NonceVerification

                if($affiliatepress_old_ap_affiliates_status != $affiliatepress_affiliates_status){
                    do_action('affiliatepress_after_affiliate_status_change',$affiliatepress_affiliates_id,$affiliatepress_affiliates_status,$affiliatepress_old_ap_affiliates_status);

                    if($affiliatepress_affiliates_status == 3){
                        $this->affiliatepress_update_commisison_status($affiliatepress_update_id , $affiliatepress_affiliates_status,$affiliatepress_old_ap_affiliates_status);
                    }
                }

                if(empty($affiliatepress_avatar_url) && !empty($affiliatepress_affiliates_user_avatar)){                    
                    if( file_exists( AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_affiliates_user_avatar) ) ){   
                        wp_delete_file(AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_affiliates_user_avatar)); // phpcs:ignore
                    }
                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliates, array('ap_affiliates_user_avatar'=>''), array( 'ap_affiliates_id' => $affiliatepress_affiliates_id ));
                }
            }
            
            if(!empty($_REQUEST['avatar_name']) && !empty($_REQUEST['avatar_url'])){// phpcs:ignore 
                
                $affiliatepress_user_img_url  = esc_url_raw($_REQUEST['avatar_url']); // phpcs:ignore 
                $affiliatepress_user_img_name = sanitize_file_name($_REQUEST['avatar_name']); // phpcs:ignore

               

                $affiliatepress_creative_image_url = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_avatar', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliates_id ), '', '', '', true, false,ARRAY_A);

                if ($affiliatepress_user_img_url != $affiliatepress_affiliates_user_avatar ) {

                    global $AffiliatePress;
                    $affiliatepress_upload_dir                 = AFFILIATEPRESS_UPLOAD_DIR . '/';
                    $affiliatepress_new_file_name = current_time('timestamp') . '_' . $affiliatepress_user_img_name;
                    $affiliatepress_upload_path                = $affiliatepress_upload_dir . $affiliatepress_new_file_name;
                    $affiliatepress_upload_res = new affiliatepress_fileupload_class( $affiliatepress_user_img_url, true );
                    $affiliatepress_upload_res->affiliatepress_check_cap          = true;
                    $affiliatepress_upload_res->affiliatepress_check_nonce        = true;
                    $affiliatepress_upload_res->affiliatepress_nonce_data         = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''; // phpcs:ignore 
                    $affiliatepress_upload_res->affiliatepress_nonce_action       = 'ap_wp_nonce';
                    $affiliatepress_upload_res->affiliatepress_check_only_image   = true;
                    $affiliatepress_upload_res->affiliatepress_check_specific_ext = false;
                    $affiliatepress_upload_res->affiliatepress_allowed_ext        = array();
                    $affiliatepress_upload_response = $affiliatepress_upload_res->affiliatepress_process_upload( $affiliatepress_upload_path );

                    $affiliatepress_user_img_url_old = basename($affiliatepress_user_img_url);
                    if( true == $affiliatepress_upload_response ){

                        $affiliatepress_user_image_new_url   = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepress_new_file_name;
                        $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliates, array('ap_affiliates_user_avatar' => basename($affiliatepress_user_image_new_url)), array( 'ap_affiliates_id' => $affiliatepress_affiliates_id ));
                        if( file_exists( AFFILIATEPRESS_TMP_IMAGES_DIR . '/' . $affiliatepress_user_img_url_old ) ){
                            wp_delete_file(AFFILIATEPRESS_TMP_IMAGES_DIR . '/' . $affiliatepress_user_img_url_old);// phpcs:ignore
                        }
                        if (! empty($affiliatepress_affiliates_user_avatar) ) {
                            // Remove old image and upload new image
                            if( file_exists( AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_affiliates_user_avatar) ) ){   
                                wp_delete_file(AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_affiliates_user_avatar));// phpcs:ignore
                            }
                        }
                    }

                }
            }            
            wp_send_json($response);
            die();
        }

        
        /**
         * Function for get affiliate status 
         *
         * @return array
         */
        function affiliatepress_all_affiliates_status(){
            $affiliatepress_all_affiliates_status = array(
                array(
                    'label'=> esc_html__('Approved', 'affiliatepress-affiliate-marketing'),
                    'value'=>'1',
                ),
                array(
                    'label'=> esc_html__('Pending', 'affiliatepress-affiliate-marketing'),
                    'value'=>'2',
                ),
                array(
                    'label'=> esc_html__('Rejected', 'affiliatepress-affiliate-marketing'),
                    'value'=>'3',
                )                                
            );
            return $affiliatepress_all_affiliates_status;
        }

              
        /**
         * Function for affiliate module dynamic const add in vue
         *
         * @param  string $affiliatepress_affiliates_dynamic_constant_define
         * @return string
         */
        function affiliatepress_affiliates_dynamic_constant_define_func($affiliatepress_affiliates_dynamic_constant_define){

            $affiliatepress_affiliates_dynamic_constant_define.='
                const open_modal = ref(false);
                affiliatepress_return_data["open_modal"] = open_modal;
                const open_import_modal = ref(false);
                affiliatepress_return_data["open_import_modal"] = open_import_modal;         
            ';

            return $affiliatepress_affiliates_dynamic_constant_define;

        }

        
        /**
         * Function for affiliate vue data
         *
         * @param  array $affiliatepress_affiliate_vue_data_fields
         * @return json
         */
        function affiliatepress_affiliates_dynamic_data_fields_func($affiliatepress_affiliate_vue_data_fields){            
            
            global $AffiliatePress,$affiliatepress_affiliate_vue_data_fields,$wpdb,$affiliatepress_tbl_ap_affiliate_form_fields;
            $affiliatepress_fields = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_form_fields, '*', 'WHERE  ap_form_field_name <> %s', array( 'terms_and_conditions'), '', 'order by ap_field_position ASC', '', false, false,ARRAY_A);           
            $affiliatepress_affiliate_vue_data_fields['affiliate_fields'] = array();

            $affiliatepress_import_fields = array();
            $affiliatepress_import_field_data = array();
            $affiliatepress_affiliate_import_rules = array();

            if(!empty($affiliatepress_fields)){
                foreach($affiliatepress_fields as $affiliatepress_key=>$affiliatepress_field){

                    $affiliatepress_fields[$affiliatepress_key]['ap_field_label'] = stripslashes_deep($affiliatepress_fields[$affiliatepress_key]['ap_field_label']);
                    $affiliatepress_fields[$affiliatepress_key]['ap_field_placeholder'] = stripslashes_deep($affiliatepress_fields[$affiliatepress_key]['ap_field_placeholder']);
                    $affiliatepress_fields[$affiliatepress_key]['ap_field_error_message'] = stripslashes_deep($affiliatepress_fields[$affiliatepress_key]['ap_field_error_message']);
                    $affiliatepress_form_field_name = (isset($affiliatepress_field['ap_form_field_name']))?$affiliatepress_field['ap_form_field_name']:'';
                    $affiliatepress_field_required = (isset($affiliatepress_field['ap_field_required']))?$affiliatepress_field['ap_field_required']:'';
                    $affiliatepress_field_error_message = (isset($affiliatepress_field['ap_field_error_message']))?$affiliatepress_field['ap_field_error_message']:'';

                    $affiliatepress_field_is_default = (isset($affiliatepress_field['ap_field_is_default']))?$affiliatepress_field['ap_field_is_default']:'';

                    if($affiliatepress_field_required == 1){

                        if ($affiliatepress_form_field_name == 'ap_affiliates_payment_email') {
                            unset($affiliatepress_affiliate_vue_data_fields['rules']['ap_affiliates_payment_email']);
                            if (isset($affiliatepress_field['ap_show_signup_field']) && $affiliatepress_field['ap_show_signup_field'] == 1) {
                                $affiliatepress_affiliate_vue_data_fields['rules']['ap_affiliates_payment_email_add'] = array(
                                    array(
                                        'required' => true,
                                        'message'  => $affiliatepress_field_error_message,
                                        'trigger'  => 'blur',
                                    ),
                                    array(
                                        'type' => 'email',
                                        'message' => $affiliatepress_field_error_message,
                                        'trigger' => 'blur',
                                    ),
                                );
                            }
                            if (isset($affiliatepress_field['ap_show_profile_field']) && $affiliatepress_field['ap_show_profile_field'] == 1) {
                                $affiliatepress_affiliate_vue_data_fields['rules']['ap_affiliates_payment_email_edit'] = array(
                                    array(
                                        'required' => true,
                                        'message'  => $affiliatepress_field_error_message,
                                        'trigger'  => 'blur',
                                    ),
                                    array(
                                        'type' => 'email',
                                        'message' => $affiliatepress_field_error_message,
                                        'trigger' => 'blur',
                                    ),
                                );
                            }
                        }
                        if($affiliatepress_form_field_name == 'email'){                                
                            $affiliatepress_affiliate_import_rules['email'] = array(
                                'required' => true,
                                'message' => (!empty($affiliatepress_field_error_message))?stripslashes_deep($affiliatepress_field_error_message):'',
                                'trigger' => 'blur',
                            ); 
                        }
                        if(isset($affiliatepress_affiliate_vue_data_fields['rules'][$affiliatepress_form_field_name][0])){
                            $affiliatepress_affiliate_vue_data_fields['rules'][$affiliatepress_form_field_name][0]['message'] = (!empty($affiliatepress_field_error_message))?stripslashes_deep($affiliatepress_field_error_message):'';
                        }else{
                            $affiliatepress_affiliate_vue_data_fields['rules'][$affiliatepress_form_field_name] = array(
                                'required' => true,
                                'message'  => (!empty($affiliatepress_field_error_message))?stripslashes_deep($affiliatepress_field_error_message):'',
                                'trigger'  => 'blur',                                   
                            );                            
                        }
                    }

                    $affiliatepress_form_field_name = (isset($affiliatepress_field['ap_form_field_name']))?$affiliatepress_field['ap_form_field_name']:'';
                    $affiliatepress_field_label = (isset($affiliatepress_field['ap_field_label']))?stripslashes_deep($affiliatepress_field['ap_field_label']):'';
                    if($affiliatepress_form_field_name != 'password'){
                        $affiliatepress_is_required = 0;
                        if($affiliatepress_form_field_name == 'email'){
                            $affiliatepress_is_required = 1;
                        }

                        if($affiliatepress_field_is_default == 1){
                            $affiliatepress_import_field_data[] = array(
                                'field_key'   => esc_html($affiliatepress_form_field_name),
                                'field_label' => esc_html($affiliatepress_field_label),
                                'is_required' => esc_html($affiliatepress_is_required),
                            );
                            $affiliatepress_import_fields[$affiliatepress_form_field_name] = '';    
                        }

                    }                    

                }
            }

            $affiliatepress_import_fields['ap_affiliates_status'] = '2';
            $affiliatepress_affiliate_vue_data_fields['affiliatepress_affiliate_import_rules'] = $affiliatepress_affiliate_import_rules;
            $affiliatepress_affiliate_vue_data_fields['affiliatepress_import_fields'] = $affiliatepress_import_fields;
            $affiliatepress_affiliate_vue_data_fields['affiliatepress_import_field_data'] = $affiliatepress_import_field_data;
            $affiliatepress_affiliate_vue_data_fields['affiliatepress_import_field_data_org'] = $affiliatepress_import_field_data;

            $affiliatepress_affiliate_vue_data_fields['affiliate_fields'] = $affiliatepress_fields;

            $affiliatepress_all_affiliates_status = $this->affiliatepress_all_affiliates_status();
            $affiliatepress_affiliate_vue_data_fields['all_status'] = $affiliatepress_all_affiliates_status;
            $affiliatepress_affiliate_vue_data_fields['affiliates']['affiliate_user_name'] = '';
            $affiliatepress_affiliate_vue_data_fields['affiliates_org']   = $affiliatepress_affiliate_vue_data_fields['affiliates'];
            $affiliatepress_affiliate_vue_data_fields['import_file_list'] = array();

            $affiliatepress_affiliate_vue_data_fields['import_file_fields']  = array();
            $affiliatepress_affiliate_vue_data_fields['import_file_name']    = '';
            $affiliatepress_affiliate_vue_data_fields['import_loading']      = '0';
            $affiliatepress_affiliate_vue_data_fields['complete_percentage'] = '0';
            $affiliatepress_affiliate_vue_data_fields['total_count']         = '0';
            $affiliatepress_affiliate_vue_data_fields['import_count']        = '0';
            $affiliatepress_affiliate_vue_data_fields['duplicate_count']     = '0';

            $affiliatepress_confirm_password_field_settings = $AffiliatePress->affiliatepress_get_settings('confirm_password_field', 'field_settings');
            $affiliatepress_confirm_password_field_settings = !empty($affiliatepress_confirm_password_field_settings) ? maybe_unserialize($affiliatepress_confirm_password_field_settings) : array();

            if(!empty($affiliatepress_confirm_password_field_settings) && is_array($affiliatepress_confirm_password_field_settings)){

                $affiliatepress_is_display_confirm_password = isset($affiliatepress_confirm_password_field_settings['enable_confirm_password']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['enable_confirm_password']) : '';
                $affiliatepress_confirm_password_label = isset($affiliatepress_confirm_password_field_settings['confirm_password_label']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_label']) : '';
                $affiliatepress_confirm_password_placeholder =isset($affiliatepress_confirm_password_field_settings['confirm_password_placeholder']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_placeholder']) : '';
                $affiliatepress_confirm_password_error_msg = isset($affiliatepress_confirm_password_field_settings['confirm_password_error_msg']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_error_msg']) : '';
                $affiliatepress_confirm_password_validation_msg = isset($affiliatepress_confirm_password_field_settings['confirm_password_validation_msg']) ? sanitize_text_field($affiliatepress_confirm_password_field_settings['confirm_password_validation_msg']) : '';

                $affiliatepress_affiliate_vue_data_fields['confirm_password_field'] = array(
                    'is_display_confirm_password' => $affiliatepress_is_display_confirm_password,
                    'confirm_password_label'  => $affiliatepress_confirm_password_label,
                    'confirm_password_placeholder'  => $affiliatepress_confirm_password_placeholder,
                    'confirm_password_error_msg'  => $affiliatepress_confirm_password_error_msg,
                    'confirm_password_validation_msg'  => $affiliatepress_confirm_password_validation_msg,
                );
            }

            $affiliatepress_affiliate_vue_data_fields = apply_filters('affiliatepress_backend_modify_affiliate_data_fields', $affiliatepress_affiliate_vue_data_fields);

            return wp_json_encode($affiliatepress_affiliate_vue_data_fields);

        }

        function affiliatepress_get_affiliate_name_by_id($affiliate_id) {

            $affiliate_name = "";
            global $affiliatepress_tbl_ap_affiliates;

            if (!$affiliate_id) {
               return $affiliate_id;
            }

            $affiliatepress_affiliate_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_first_name,ap_affiliates_last_name,ap_affiliates_user_name', 'WHERE ap_affiliates_id  = %d', array( $affiliate_id ), '', '', '', false, true,ARRAY_A);

            $affiliatepress_first_name = isset($affiliatepress_affiliate_data['ap_affiliates_first_name']) ? sanitize_text_field($affiliatepress_affiliate_data['ap_affiliates_first_name']) : '';
            $affiliatepress_last_name = isset($affiliatepress_affiliate_data['ap_affiliates_last_name']) ? sanitize_text_field($affiliatepress_affiliate_data['ap_affiliates_last_name']) : '';
            $affiliatepress_user_name = isset($affiliatepress_affiliate_data['ap_affiliates_user_name']) ? sanitize_text_field($affiliatepress_affiliate_data['ap_affiliates_user_name']) : '';

            if(!empty($affiliatepress_first_name) || !empty($affiliatepress_last_name)){
                $affiliate_name = $affiliatepress_first_name.' '.$affiliatepress_last_name;
            }else{
                $affiliate_name = $affiliatepress_user_name;
            }

            return $affiliate_name;
        }
        
        /**
         * Function for affiliate module vue method 
         *
         * @param  string $affiliatepress_affiliates_dynamic_vue_methods
         * @return void
         */
        function affiliatepress_affiliates_dynamic_vue_methods_func($affiliatepress_affiliates_dynamic_vue_methods){
            global $affiliatepress_notification_duration;            

            $affiliatepress_edit_affiliate_more_vue_data = "";
            $affiliatepress_edit_affiliate_more_vue_data = apply_filters('affiliatepress_edit_affiliate_more_vue_data', $affiliatepress_edit_affiliate_more_vue_data);     
            
            $affiliatepress_add_posted_data_for_save_affiliate = "";
            $affiliatepress_add_posted_data_for_save_affiliate = apply_filters('affiliatepress_add_posted_data_for_save_affiliate', $affiliatepress_add_posted_data_for_save_affiliate);     

            $affiliatepress_affiliates_dynamic_vue_methods.='
            importAffiliate(form_ref){
                const vm = this;
                vm.$refs[form_ref].validate((valid) => {  
                    if(valid){                              
                        var postData = { action:"affiliatepress_import_affiliates", _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                        postData.import_file_fields = vm.affiliatepress_import_fields;
                        postData.import_file_name   = vm.import_file_name;
                        vm.import_loading = "1";
                        axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                        .then(function(response){                                               
                            if(response.data.variant == "success"){
                                vm.complete_percentage = 50;
                                setTimeout(function(){
                                    vm.complete_percentage = 100;
                                    vm.total_count = response.data.total_count;
                                    vm.import_count = response.data.import_count;
                                    vm.duplicate_count = response.data.duplicate_count;
                                    vm.import_loading = "1";
                                },500);
                                vm.loadAffiliate(false);
                            }else{
                                vm.import_loading = "0";
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
                        });                                              
                    }else{
                        return false;
                    }
                });
            },
            affiliatepress_remove_import_file(){
                const vm = this;
                vm.import_file_fields = [];
                vm.import_file_name   = "";
            },             
            affiliatepress_upload_import_file_func(response, file, fileList){
                const vm = this;                
                if(response != "" && response.error == 0){                                        
                    vm.import_file_fields = response.import_file_fields;
                    vm.import_file_name = response.import_file_name;
                    vm.autoMapAffiliateManageImportFields();
                }else{
                    vm.import_file_list = [];
                    if(response != ""){                        
                        vm.$notify({
                            title: "Error",
                            message: response.msg,
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });                    
                    }else{
                        vm.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Something wrong file not uploaded.', 'affiliatepress-affiliate-marketing').'",
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });                        
                    }
                }
            },  
            autoMapAffiliateManageImportFields(){
                const vm = this;
                vm.affiliatepress_import_field_data.forEach(field => {
                    let match = vm.import_file_fields.find(csv =>
                        csv.value.toLowerCase().replace(/\s/g,"") ==
                        field.field_label.toLowerCase().replace(/\s/g,"")
                    );
                    if(match){
                        vm.affiliatepress_import_fields[field.field_key] = match.key;
                    }
                });
            },
            checkImportUploadedFile(file){
                const vm = this;                
                if(file.type != "text/csv"){
                    vm.$notify({
                        title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                        message: "'.esc_html__('Please upload csv file only.', 'affiliatepress-affiliate-marketing').'",
                        type: "error",
                        customClass: "error_notification",
                        duration:'.intval($affiliatepress_notification_duration).',
                    });
                    return false;
                }
            }, 
            importAffiliateOpenDrawer(){
                const vm = this;
                vm.open_import_modal = true;
                vm.open_modal = true;
            },
            exportAffiliate(){                
                const vm = this;
                var nonce = "'.esc_html(wp_create_nonce('ap_wp_nonce')).'";
                var downloadUrl = affiliatepress_ajax_obj.ajax_url + "?action=affiliatepress_export_affiliate&_wpnonce=" + nonce;
                window.location.href = downloadUrl;
            }, 
            copy_affiliate_link(affiliates_link){
				const vm = this;				
				var affiliatepress_dummy_elem = document.createElement("textarea");
				document.body.appendChild(affiliatepress_dummy_elem);
				affiliatepress_dummy_elem.value = affiliates_link;
				affiliatepress_dummy_elem.select();
				document.execCommand("copy");
				document.body.removeChild(affiliatepress_dummy_elem);
				vm.$notify({ 
					title: "'.esc_html__('Success', 'affiliatepress-affiliate-marketing').'",
					message: "'.esc_html__('Link copied successfully.', 'affiliatepress-affiliate-marketing').'",
					type: "success",
					customClass: "success_notification",
					duration:'.intval($affiliatepress_notification_duration).',
				});
            }, 
            affiliatepress_upload_affiliate_avatar_func(response, file, fileList){
                const vm = this;
                if(response != ""){
                    vm.affiliates.avatar_url = response.upload_url;
                    vm.affiliates.avatar_name = response.upload_file_name;
                }
            },
            checkUploadedFile(file){
                const vm = this;
                if(file.type != "image/jpeg" && file.type != "image/jpg" && file.type != "image/png" && file.type != "image/webp"){
                    vm.$notify({
                        title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                        message: "'.esc_html__('Please upload jpg,jpeg,png or webp file only', 'affiliatepress-affiliate-marketing').'",
                        type: "error",
                        customClass: "error_notification",
                        duration:'.intval($affiliatepress_notification_duration).',
                    });
                    return false
                }else{
                    var ap_image_size = parseFloat(file.size / 1000000);
                    if(ap_image_size >= vm.avatar_upload_file_size){
                        vm.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Please upload maximum 1 MB file only', 'affiliatepress-affiliate-marketing').'",
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });                    
                        return false
                    }
                }
            },            
            affiliatepress_remove_affiliate_avatar() {
                const vm = this
                var upload_url = vm.affiliates.avatar_url;
                var upload_filename = vm.affiliates.avatar_name;                
                var postData = { action:"affiliatepress_remove_affiliate_avatar", upload_file_url: upload_url,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    vm.affiliates.avatar_url = "";
                    vm.affiliates.avatar_name = "";
                    vm.$refs.avatarRef.clearFiles();
                }.bind(vm) )
                .catch( function (error) {
                    console.log(error);
                });                
            },     
            affiliatepress_image_upload_limit(files, fileList){
                const vm = this;
                    if(vm.affiliates.avatar_url != ""){
                    vm.$notify({
                        title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                        message: "'.esc_html__('Multiple files not allowed', 'affiliatepress-affiliate-marketing').'",
                        type: "error",
                        customClass: "error_notification",
                        duration:'.intval($affiliatepress_notification_duration).',
                    });
                }
            },                 
            editAffiliate(ap_affiliates_id,index,row){
                const vm = this;
                vm.open_import_modal = false;
                vm.open_modal = true;
                vm.affiliate_image_list = [];
                var affiliate_edit_data = { action: "affiliatepress_edit_affiliate",edit_id: ap_affiliates_id,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }
                axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(affiliate_edit_data)).then(function(response){

                    if(response.data.affiliates.ap_affiliates_id != undefined){
                        vm.affiliates.ap_affiliates_id = response.data.affiliates.ap_affiliates_id;
                    } 
                    if(response.data.affiliates.ap_affiliates_user_id != undefined){
                        vm.affiliates.ap_affiliates_user_id = response.data.affiliates.ap_affiliates_user_id;
                    }
                    if(response.data.affiliates.ap_affiliates_payment_email != undefined){
                        vm.affiliates.ap_affiliates_payment_email_edit = response.data.affiliates.ap_affiliates_payment_email;
                    } 
                    if(response.data.affiliates.ap_affiliates_website != undefined){
                        vm.affiliates.ap_affiliates_website = response.data.affiliates.ap_affiliates_website;
                    } 
                    if(response.data.affiliates.ap_affiliates_status != undefined){
                        vm.affiliates.ap_affiliates_status = response.data.affiliates.ap_affiliates_status;
                    } 
                    if(response.data.affiliates.ap_affiliates_user_avatar != undefined){
                        vm.affiliates.ap_affiliates_user_avatar = response.data.affiliates.ap_affiliates_user_avatar;
                        vm.affiliates.avatar_url = response.data.affiliates.ap_affiliates_user_avatar;
                    }  
                    if(response.data.affiliates.ap_affiliates_promote_us != undefined){
                        vm.affiliates.ap_affiliates_promote_us = response.data.affiliates.ap_affiliates_promote_us;
                    } 
                    if(response.data.affiliates.ap_affiliates_note != undefined){
                        vm.affiliates.ap_affiliates_note = response.data.affiliates.ap_affiliates_note;
                    } 
                    if(response.data.affiliates.affiliate_user_name != undefined){
                        vm.affiliates.affiliate_user_name = response.data.affiliates.affiliate_user_name;
                    }                                                                                                                                           
                    '.$affiliatepress_edit_affiliate_more_vue_data.'
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
            deleteAffiliate(ap_affiliates_id,index){
                const vm = this;
                var postData = { action:"affiliatepress_delete_affiliate", ap_affiliates_id: ap_affiliates_id, _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    if(response.data.variant == "success"){
                        vm.items.splice(index,1);
                        vm.loadAffiliate();                        
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
            handleSizeChange(val) {
                this.perPage = val;
                this.loadAffiliate();
            },
            handleCurrentChange(val) {
                this.currentPage = val;
                this.loadAffiliate();
            },            
            affiliatepress_change_status(update_id, index, new_status, old_status){
                    const vm = this;
                    vm.items[index].change_status_loader = 1;                
                    var postData = { action:"affiliatepress_change_affiliate_status", update_id: update_id, new_status: new_status, old_status: old_status, _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(response.data == "0" || response.data == 0){
                            vm.items[index].change_status_loader = 0;
                            vm.loadAffiliate(false);
                            return false;
                        }else{
                            vm.items[index].change_status_loader = 0;
                            vm.$notify({
                                title: "'.esc_html__('Success', 'affiliatepress-affiliate-marketing').'",
                                message: "'.esc_html__('Affiliate status changed successfully', 'affiliatepress-affiliate-marketing').'",
                                type: "success",
                                customClass: "success_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            vm.loadAffiliate(false);
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
            applyFilter(){
                const vm = this;
                vm.currentPage = 1;
                vm.loadAffiliate();
            },            
            resetFilter(){
                const vm = this;               
                const hasValue = Object.values(this.affiliates_search).some(value => (value !== "" || value != []));
                vm.affiliates_search.ap_affiliates_status = "";
                vm.affiliates_search.ap_affiliates_user = "";                 
                if (hasValue) {
                    vm.currentPage = 1;
                    vm.loadAffiliate();
                }                                
                vm.is_multiple_checked = false;
                vm.multipleSelection = [];
            },
            affiliatepress_get_existing_user_details(affiliatepress_selected_user_id){
                const vm = this;
                if(vm.$refs["selectRef"] && vm.$refs["selectRef"].$el.querySelector("input")){
                    setTimeout(function(){
                        vm.$refs["selectRef"].$el.querySelector("input").blur();
                    },100);                
                }
                if(affiliatepress_selected_user_id != "add_new") {                                                                                
                    var postData = { action:"affiliatepress_get_existing_users_details", existing_user_id: affiliatepress_selected_user_id, _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {                        
                        if(response.data.user_details != "" || response.data.user_details != undefined){
                            
                        }
                    }.bind(vm) )
                    .catch( function (error) {
                        console.log(error);
                    });
                }
            },            
            get_wordpress_users(query) {
                const vm = this;	
                if (query !== "") {
                    vm.affiliatepress_user_loading = true;
                    if (vm.affiliateUsersAbortController) {
                        vm.affiliateUsersAbortController.abort();
                    }   
                    vm.affiliateUsersAbortController = new AbortController();                      
                    var customer_action = { action:"affiliatepress_get_wpuser",search_user_str:query,wordpress_user_id:vm.wordpress_user_id,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }                    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( customer_action ), {signal: vm.affiliateUsersAbortController.signal} )
                    .then(function(response){
                        vm.affiliatepress_user_loading = false;
                        vm.wpUsersList = response.data.users
                    }).catch(function(error){
                        if (error.name === "CanceledError") {
                            return;
                        }
                        console.log(error)
                    });
                } else {
                    vm.wpUsersList = [];
                }	
            },              
            async loadAffiliate(flag = true) {
                const vm = this;
                if(flag){
                    vm.is_display_loader = "1";
                }                
                vm.enabled = true;
                vm.is_apply_disabled = true;
                affiliatespress_search_data = vm.affiliates_search;
                var postData = { action:"affiliatepress_get_affiliates", perpage:this.perPage, order_by:this.order_by, order:this.order, currentpage:this.currentPage, search_data: affiliatespress_search_data,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function(response){
                    vm.ap_first_page_loaded = "0";
                    vm.is_display_loader = "0"; 
                    vm.is_apply_disabled = false;
                    if(response.data.variant == "success"){
                        vm.items = response.data.items;
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
            resetModal(form_ref){
                vm = this;                
                if(form_ref && this.$refs[form_ref]){
                    this.$refs[form_ref].resetFields();
                }                
                vm.affiliates = JSON.parse(JSON.stringify(vm.affiliates_org));
                var div = document.getElementById("ap-drawer-body");
                if(div){
                    div.scrollTop = 0;
                }   
                vm.import_file_fields = [];
                vm.import_file_name   = "";                 
                vm.import_file_list = [];
                vm.import_file_fields = [];
                vm.import_file_name = "";
                vm.import_loading = "0";
                vm.complete_percentage = "0";
                vm.total_count = "0";
                vm.import_count = "0";
                vm.duplicate_count = "0";
                vm.affiliatepress_import_field_data = vm.affiliatepress_import_field_data_org;                

            },
            openAddAffiliate(){
                vm = this; 
                vm.open_import_modal = false;
                vm.open_modal = true;        
                vm.affiliate_image_list = [];        
            },
            closeModal(form_ref){
                vm = this;                
                var div = document.getElementById("ap-drawer-body");
                if(div){
                    div.scrollTop = 0;
                }                
                vm.open_modal = false;
                if(form_ref && this.$refs[form_ref]){
                    this.$refs[form_ref].resetFields();
                }                
                vm.affiliates = JSON.parse(JSON.stringify(vm.affiliates_org));
                vm.open_import_modal = false;
                vm.import_file_fields = [];
                vm.import_file_name   = "";                                  
                vm.import_file_list = [];                
                vm.import_file_name = "";
                vm.import_loading = "0";
                vm.complete_percentage = "0";
                vm.total_count = "0";
                vm.import_count = "0";
                vm.duplicate_count = "0";
                vm.affiliatepress_import_field_data = vm.affiliatepress_import_field_data_org;                 
                vm.affiliate_image_list = [];
            },  
            saveAffiliate(form_ref){                
                vm = this;
                this.$refs[form_ref].validate((valid) => {     
                    if (valid) {
                        var postdata = vm.affiliates;
                        postdata.action = "affiliatepress_add_affiliate";
                        '.$affiliatepress_add_posted_data_for_save_affiliate.'
                        vm.is_disabled = true;
                        vm.is_display_save_loader = "1";
                        vm.savebtnloading = true;
                        postdata._wpnonce = "'.esc_html(wp_create_nonce('ap_wp_nonce')).'";                        
                        
                        axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                        .then(function(response){
                            vm.affiliate_image_list = [];
                            vm.is_disabled = false;                           
                            vm.is_display_save_loader = "0";                           
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            vm.savebtnloading = false;
                            if (response.data.variant == "success") {                                    
                                vm.loadAffiliate();
                            }
                            if(response.data.variant != "error"){
                                vm.closeModal();
                            }
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
                        
                    }else{
                        return false;
                    }
                });
            }, 
            handleSortChange({ column, prop, order }){                
                var vm = this;
                if(prop == "full_name"){
                    vm.order_by = "first_name"; 
                }else if(prop == "ap_affiliates_id"){
                    vm.order_by = "ap_affiliates_id"; 
                }else if(prop == "total_visit"){
                    vm.order_by = "total_visit"; 
                }else if(prop == "converted_user"){
                    vm.order_by = "converted_user"; 
                }else if(prop == "ap_affiliates_created_at"){
                    vm.order_by = "ap_affiliates_created_at"; 
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
                this.loadAffiliate(true);                 
            }, 
            handleSelectionChange(val) {
                const items_obj = val;
                this.multipleSelection = [];
                var temp_data = [];
                Object.values(items_obj).forEach(val => {
                    temp_data.push({"item_id" : val.ap_affiliates_id});
                    this.bulk_action = "delete";
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
                        message: "'.esc_html__('Please select any action.', 'affiliatepress-affiliate-marketing').'",
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
                            _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'",
                        };
                        vm.is_display_loader = "1";
                        axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( bulk_action_postdata )).then(function(response){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                customClass: response.data.variant+"_notification",
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            vm.loadAffiliate(true);                     
                            vm.is_multiple_checked = false;
                            vm.multipleSelection = []; 
                            vm.multipleSelectionVal = "";                                         
                        }).catch(function(error){
                            console.log(error);
                            vm.is_display_loader = "0";
                            vm2.$notify({
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
                vm.loadAffiliate();
            },
            async validatePassword(rule, value) {
                if (value && this.affiliates.confirm_password) {
                    if (this.$refs.affiliates_form_data) {
                        try {
                            await this.$refs.affiliates_form_data.validateField("confirm_password");
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
            affiliatepress_get_existing_affiliate_details(affiliatepress_selected_user_id){
                const vm = this;  
                if(vm.$refs["selectAffUserRef"] && vm.$refs["selectAffUserRef"].$el.querySelector("input")){
                    setTimeout(function(){
                        vm.$refs["selectAffUserRef"].$el.querySelector("input").blur();
                    },100);                
                }        
                if (typeof vm.$refs["commission_form_data"] != "undefined") {
                    vm.$refs["commission_form_data"].validateField("ap_affiliates_id");
                  }                      
            },
            get_affiliate_users(query) {
                const vm = this;	
                if (query !== "") {
                    vm.affiliatepress_user_loading = true;  
                    if (vm.affiliateUsersAbortController) {
                        vm.affiliateUsersAbortController.abort();
                    }   
                    vm.affiliateUsersAbortController = new AbortController();      
                    var customer_action = { action:"affiliatepress_get_affiliate_users",search_user_str:query,ap_affiliates_user_id:vm.ap_affiliates_user_id,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }                    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( customer_action ), {signal: vm.affiliateUsersAbortController.signal} )
                    .then(function(response){
                        vm.affiliatepress_user_loading = false;
                        vm.AffiliateUsersList = response.data.users
                    }).catch(function(error){
                        if (error.name === "CanceledError") {
                            return;
                        }
                        console.log(error)
                    });
                } else {
                    vm.AffiliateUsersList = [];
                }	
            },       
            affiliatepress_affiliate_to_all_visit_show(affiliate_id){
                const vm = this;
                window.open("'.admin_url("admin.php?page=affiliatepress_visits&affiliate=").'" + affiliate_id, "_blank");
            }, 
            ';

            $affiliatepress_affiliates_dynamic_vue_methods = apply_filters('affiliatepress_affiliate_add_dynamic_vue_methods', $affiliatepress_affiliates_dynamic_vue_methods);

            return $affiliatepress_affiliates_dynamic_vue_methods;
        }
        
        /**
         * Function for dynamic View load
         *
         * @return html
        */
        function affiliatepress_affiliates_dynamic_view_load_func(){

            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliates/manage_affiliates.php';
            $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_affiliates_view_file_path', $affiliatepress_load_file_name);
            include $affiliatepress_load_file_name;

        }

        
        /**
         * Function for affiliates default Vue Data
         *
         * @return void
        */
        function affiliatepress_affiliates_vue_data_fields(){

            global $affiliatepress_affiliate_vue_data_fields,$affiliatepress_global_options;            
            $affiliatepress_pagination          = wp_json_encode(array( 10, 20, 50, 100, 200, 300, 400, 500 ));
            $affiliatepress_pagination_arr      = json_decode($affiliatepress_pagination, true);
            $affiliatepress_pagination_selected = $this->affiliatepress_per_page_record;

            $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_pagination_value = (isset($affiliatepress_global_options_data['pagination_val']))?$affiliatepress_global_options_data['pagination_val']:array();

            $affiliatepress_affiliate_vue_data_fields = array(
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
                'affiliates_search'          => array(
                    "ap_affiliates_user"     => '',
                    "ap_affiliates_status"   => '',
                ),
                'order'                      => '',
                'order_by'                   => '',

                'items'                      => array(),
                'avatar_upload_file_size'    => 1,
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
                'affiliateUsersAbortController' => null,
                'affiliates'                 => array(
                    'username'                     => "",
                    'firstname'                    => "",
                    'lastname'                     => "",
                    'email'                        => "",
                    'password'                     => "",
                    'confirm_password'             => "",
                    "ap_affiliates_id"             => "",
                    "ap_affiliates_user_id"        => "",
                    "ap_affiliates_payment_email"  => "",
                    "ap_affiliates_payment_email_add"=>"",
                    "ap_affiliates_payment_email_edit"=> "",
                    "ap_affiliates_website"        => "",
                    "ap_affiliates_user_avatar"    => "",
                    "avatar_url"                   => "",
                    "affiliate_image_list"         => [],
                    "avatar_name"                  => "",
                    "ap_affiliates_status"         => "1",
                    "ap_affiliates_promote_us"     => "",
                    "ap_send_email"                => false,
                    "ap_affiliates_note"            => "",
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
		        'pagination_length_val'      => '10',
                'pagination_val'             => $affiliatepress_pagination_value,
            );
        }

        /** Function For Get affiliate CUrrent rate */
        function affiliatepress_get_current_affiliate_rate($affiliatepress_affiliate_id){

            global $AffiliatePress;
            
            $affiliatepress_commission_type_priorities = $AffiliatePress->affiliatepress_commission_type_priorities();
            
            $affiliatepress_current_rule = array();
            $affiliatepress_default_commission_rate = "";
            if( !empty($affiliatepress_commission_type_priorities)){
                asort($affiliatepress_commission_type_priorities);
                unset($affiliatepress_commission_type_priorities['product']);

                foreach ($affiliatepress_commission_type_priorities as $type => $priorities) {
                    $affiliatepress_current_rule = apply_filters( 'affiliatepress_get_current_affiliate_rate_'.$type, $affiliatepress_current_rule,$affiliatepress_affiliate_id); 
                }

                $affiliatepress_discount_value = isset($affiliatepress_current_rule['discount_value']) ? $affiliatepress_current_rule['discount_value'] : 0;
                $affiliatepress_discount_type = isset($affiliatepress_current_rule['discount_type']) ? $affiliatepress_current_rule['discount_type'] : 'percentage';
                $affiliatepress_discount_label = isset($affiliatepress_current_rule['discount_label']) ? $affiliatepress_current_rule['discount_label'] : '';

                if($affiliatepress_discount_type == 'percentage'){
                    $affiliatepress_default_commission_rate = $affiliatepress_discount_value.'%';
                }else{
                    $affiliatepress_default_commission_rate = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_discount_value);                
                }
            }

            return $affiliatepress_default_commission_rate;
        }

    }
}
global $affiliatepress_affiliates;
$affiliatepress_affiliates = new affiliatepress_affiliates();
