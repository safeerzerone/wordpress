<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_creative') ) {
    class affiliatepress_creative Extends AffiliatePress_Core{
        
        var $affiliatepress_per_page_record;

        function __construct(){
            
            $this->affiliatepress_per_page_record = 10;

            /**Function for creative vue data */
            add_action( 'admin_init', array( $this, 'affiliatepress_creative_vue_data_fields') );

            /* Dynamic Constant */
            add_filter('affiliatepress_creative_dynamic_constant_define',array($this,'affiliatepress_creative_dynamic_constant_define_func'),10,1);
            
            /* Dynamic Vue Fields */
            add_filter('affiliatepress_creative_dynamic_data_fields',array($this,'affiliatepress_creative_dynamic_data_fields_func'),10,1);

            /* Vue Load */
            add_action('affiliatepress_creative_dynamic_view_load', array( $this, 'affiliatepress_creative_dynamic_view_load_func' ), 10);

            /* Vue Method */
            add_filter('affiliatepress_creative_dynamic_vue_methods',array($this,'affiliatepress_creative_dynamic_vue_methods_func'),5,1);

            /* Dynamic On Load Method */
            add_filter('affiliatepress_creative_dynamic_on_load_methods', array( $this, 'affiliatepress_creative_dynamic_on_load_methods_func'), 10, 1);

            /* Get Creatives */
            add_action('wp_ajax_affiliatepress_get_creatives', array( $this, 'affiliatepress_get_creatives' ));

            /* Change Creative Status */
            add_action('wp_ajax_affiliatepress_change_creative_status', array( $this, 'affiliatepress_change_creative_status_func' ));

             /* Bulk Action */
             add_action('wp_ajax_affiliatepress_creative_bulk_status_change', array( $this, 'affiliatepress_creativen_bulk_status_change_func' ));  

            /* Add Creative */
            add_action('wp_ajax_affiliatepress_add_creative', array( $this, 'affiliatepress_add_creative_func' ));            

            /* Edit Creative */
            add_action('wp_ajax_affiliatepress_edit_creative', array( $this, 'affiliatepress_edit_creative_func' ));

            /* Upload Creative Image */
            add_action('wp_ajax_affiliatepress_upload_creative_image', array( $this, 'affiliatepress_upload_creative_image_func' ), 10);

            /* Remove Creative Image */
            add_action( 'wp_ajax_affiliatepress_remove_creative_avatar', array( $this, 'affiliatepress_remove_creative_avatar_func'));
            
        }
        
        /**
         * Function for remove creative image
         *
         * @return json
        */
        function affiliatepress_remove_creative_avatar_func(){
            global $wpdb;
            $response = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'remove_creative_image', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_creative')){
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
                    wp_delete_file(AFFILIATEPRESS_TMP_IMAGES_DIR . '/' . basename($affiliatepress_file_name));// phpcs:ignore
                }
            }
            die;
        }

        /**
         * Function for upload affiliate avatar
         *
         * @return json
        */
        function affiliatepress_upload_creative_image_func(){

            $return_data = array(
                'error'            => 0,
                'msg'              => '',
                'upload_url'       => '',
                'upload_file_name' => '',
            );//phpcs:ignore

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'affiliate_creative_image_upload', true, 'affiliatepress_upload_creative_image' );            
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

            if(!current_user_can('affiliatepress_creative')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }
            
            $affiliatepress_wpnonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : ((isset($_REQUEST['_wpnonce']))?sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])):'');// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'affiliatepress_upload_creative_image');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            } 

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
         * Function for edit creative data
         *
         * @return json
        */
        function affiliatepress_edit_creative_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_creative,$AffiliatePress;
            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'edit_creative', true, 'ap_wp_nonce' );

            $response = array();
            $response['variant'] = 'error';
            $response['creatives'] = '';
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

            if(!current_user_can('affiliatepress_creative')){
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

            $affiliatepress_creative_id  =  isset($_POST['edit_id']) ? intval($_POST['edit_id']) : ''; // phpcs:ignore 
            $affiliatepress_creatives_data = array();
            if(!empty($affiliatepress_creative_id)){
                
                $affiliatepress_creatives = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_creative, '*', 'WHERE ap_creative_id = %d', array( $affiliatepress_creative_id ), '', '', '', false, true,ARRAY_A);
                if(!empty($affiliatepress_creatives)){

                    $affiliatepress_creatives_data['ap_creative_id']              = intval($affiliatepress_creatives['ap_creative_id']);
                    $affiliatepress_creatives_data['ap_creative_name']            = stripslashes_deep($affiliatepress_creatives['ap_creative_name']);
                    $affiliatepress_creatives_data['ap_creative_description']     = stripslashes_deep($affiliatepress_creatives['ap_creative_description']);
                    $affiliatepress_creatives_data['ap_creative_type']            = stripslashes_deep($affiliatepress_creatives['ap_creative_type']);
                    $affiliatepress_creatives_data['ap_creative_image_url']       = esc_html($affiliatepress_creatives['ap_creative_image_url']);
                    $affiliatepress_creatives_data['ap_creative_alt_text']        = esc_attr($affiliatepress_creatives['ap_creative_alt_text']);
                    $affiliatepress_creatives_data['ap_creative_text']            = stripslashes_deep($affiliatepress_creatives['ap_creative_text']);
                    $affiliatepress_creatives_data['ap_creative_landing_url']     = esc_url($affiliatepress_creatives['ap_creative_landing_url']);
                    $affiliatepress_creatives_data['ap_creative_status']          = esc_html($affiliatepress_creatives['ap_creative_status']);
                    $affiliatepress_creatives_data['ap_creative_created_at']      = esc_html($affiliatepress_creatives['ap_creative_created_at']);                    
                    $affiliatepress_creatives_data['creative_shortcode']          = '[affiliatepress_creative id="'.esc_html($affiliatepress_creatives['ap_creative_id']).'"]';

                    $affiliatepress_creatives_data['image_list']                  = array();
                    $affiliatepress_creatives_data['image_url']                   = '';
                    if(!empty($affiliatepress_creatives_data['ap_creative_image_url'])){
                        $affiliatepress_creatives_data['image_url'] = esc_url(AFFILIATEPRESS_UPLOAD_URL.'/'.$affiliatepress_creatives_data['ap_creative_image_url']); 
                        $affiliatepress_image_name = basename($affiliatepress_creatives_data['ap_creative_image_url']);
                        $affiliatepress_creatives_data['image_list'] = array(
                            array(
                                'name' => $affiliatepress_image_name,
                                'url'  => AFFILIATEPRESS_UPLOAD_URL . '/' .$affiliatepress_creatives_data['ap_creative_image_url'],
                            )
                        );
                    }
                    
                    $response['variant'] = 'success';
                    $response['creatives'] = $affiliatepress_creatives_data;
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Creative Data.', 'affiliatepress-affiliate-marketing');
                }

            }
            echo wp_json_encode($response);
            exit;            
            

            

        }

        /**
         * Function for add creative
         *
         * @return json
         */
        function affiliatepress_add_creative_func(){
            
            global $AffiliatePress, $wpdb, $affiliatepress_tbl_ap_creative;
            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'add_creative', true, 'ap_wp_nonce' );
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

            if(!current_user_can('affiliatepress_creative')){
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
            
            $affiliatepress_creatives_data = '';
            if((isset($_POST['creatives'])) && !empty($_POST['creatives'])){ // phpcs:ignore            
                $affiliatepress_creatives_data = !empty($_POST['creatives']) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_field' ), json_decode(stripslashes_deep($_POST['creatives']),true)) : array(); // phpcs:ignore                
            }
            if(!empty($affiliatepress_creatives_data)){

                $affiliatepress_creative_id = (isset($affiliatepress_creatives_data['ap_creative_id']))?intval($affiliatepress_creatives_data['ap_creative_id']):'';
                $affiliatepress_creative_status = (isset($affiliatepress_creatives_data['ap_creative_status']))?intval($affiliatepress_creatives_data['ap_creative_status']):'';                
                $affiliatepress_creative_name = (isset($affiliatepress_creatives_data['ap_creative_name']))?sanitize_text_field($affiliatepress_creatives_data['ap_creative_name']):'';
                $affiliatepress_creative_description = (isset($affiliatepress_creatives_data['ap_creative_description']))?sanitize_text_field($affiliatepress_creatives_data['ap_creative_description']):'';
                $affiliatepress_creative_type = (isset($affiliatepress_creatives_data['ap_creative_type']))?sanitize_text_field($affiliatepress_creatives_data['ap_creative_type']):'';
                $affiliatepress_creative_image_url = (isset($affiliatepress_creatives_data['ap_creative_image_url']))?sanitize_text_field($affiliatepress_creatives_data['ap_creative_image_url']):'';
                $affiliatepress_creative_alt_text = (isset($affiliatepress_creatives_data['ap_creative_alt_text']))?sanitize_text_field($affiliatepress_creatives_data['ap_creative_alt_text']):'';
                $affiliatepress_creative_text = (isset($affiliatepress_creatives_data['ap_creative_text']))?sanitize_text_field($affiliatepress_creatives_data['ap_creative_text']):'';
                $affiliatepress_creative_landing_url = (isset($affiliatepress_creatives_data['ap_creative_landing_url']))?sanitize_text_field($affiliatepress_creatives_data['ap_creative_landing_url']):'';
                if(empty(trim($affiliatepress_creative_name))) {
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Enter creative name', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);
                    die();
                }else if(empty(trim($affiliatepress_creative_type))){
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Enter creative type', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);
                    die();                    
                }
                    
                $affiliatepress_args = array(                
                    'ap_creative_name'              => $affiliatepress_creative_name,
                    'ap_creative_description'       => $affiliatepress_creative_description,
                    'ap_creative_type'              => $affiliatepress_creative_type,                
                    'ap_creative_alt_text'          => $affiliatepress_creative_alt_text,
                    'ap_creative_status'            => $affiliatepress_creative_status,
                    'ap_creative_text'              => $affiliatepress_creative_text,
                    'ap_creative_landing_url'       => $affiliatepress_creative_landing_url,
                );
                if($affiliatepress_creative_id && $affiliatepress_creative_id != 0){                    
                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_creative, $affiliatepress_args, array( 'ap_creative_id' => $affiliatepress_creative_id ));
                    $response['variant'] = 'success';
                    $response['title']   = esc_html__('Sucess', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Creative successfully updated.', 'affiliatepress-affiliate-marketing');                    
                }else{           
                    $affiliatepress_creative_id = apply_filters('affiliatepress_add_creative',$affiliatepress_creative_id ,$affiliatepress_args);

                    if(!empty($affiliatepress_creative_id)){
                        $response['variant'] = 'success';
                        $response['title']   = esc_html__('Sucess', 'affiliatepress-affiliate-marketing');
                        $response['msg']     = esc_html__('Creative successfully added.', 'affiliatepress-affiliate-marketing'); 
                    }
                }

                if($affiliatepress_creative_type == 'image'){

                    if(!empty($affiliatepress_creatives_data['avatar_name']) && !empty($affiliatepress_creatives_data['avatar_url'])){

                        $affiliatepress_user_img_url  = esc_url_raw($affiliatepress_creatives_data['avatar_url']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                        $affiliatepress_user_img_name = sanitize_file_name($affiliatepress_creatives_data['avatar_name']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                                                
                        $affiliatepress_creative_image_url = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_creative, 'ap_creative_image_url', 'WHERE ap_creative_id = %d', array( $affiliatepress_creative_id ), '', '', '', true, false,ARRAY_A);
                        $affiliatepress_user_img_url_comapre = (!empty($affiliatepress_user_img_url))?basename($affiliatepress_user_img_url):'';

                        if($affiliatepress_user_img_url_comapre != $affiliatepress_creative_image_url ){

                            $affiliatepress_upload_dir                 = AFFILIATEPRESS_UPLOAD_DIR . '/';
                            $affiliatepress_new_file_name = current_time('timestamp') . '_' . $affiliatepress_user_img_name;
                            $affiliatepress_upload_path                = $affiliatepress_upload_dir . $affiliatepress_new_file_name;
                            $affiliatepress_upload_res = new affiliatepress_fileupload_class( $affiliatepress_user_img_url, true );
                            $affiliatepress_upload_res->affiliatepress_check_cap          = true;
                            $affiliatepress_upload_res->affiliatepress_check_nonce        = true;
                            $affiliatepress_upload_res->affiliatepress_nonce_data         = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';// phpcs:ignore 
                            $affiliatepress_upload_res->affiliatepress_nonce_action       = 'ap_wp_nonce';
                            $affiliatepress_upload_res->affiliatepress_check_only_image   = true;
                            $affiliatepress_upload_res->affiliatepress_check_specific_ext = false;
                            $affiliatepress_upload_res->affiliatepress_allowed_ext        = array();
                            $affiliatepress_upload_response = $affiliatepress_upload_res->affiliatepress_process_upload( $affiliatepress_upload_path );                            
                            if( true == $affiliatepress_upload_response ){
                                //$affiliatepress_user_image_new_url   = AFFILIATEPRESS_UPLOAD_URL . '/' . $affiliatepress_new_file_name;
                                $affiliatepress_user_image_new_url   =  $affiliatepress_new_file_name;
                                $this->affiliatepress_update_record($affiliatepress_tbl_ap_creative, array('ap_creative_image_url'=>$affiliatepress_user_image_new_url), array( 'ap_creative_id' => $affiliatepress_creative_id ));
                                if( file_exists( AFFILIATEPRESS_TMP_IMAGES_DIR . '/' . basename($affiliatepress_user_img_url_comapre) ) ){
                                    wp_delete_file(AFFILIATEPRESS_TMP_IMAGES_DIR . '/' . basename($affiliatepress_user_img_url_comapre));// phpcs:ignore
                                }
                                if (! empty($affiliatepress_creative_image_url) ) {
                                    // Remove old image and upload new image                                    
                                    if( file_exists( AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_creative_image_url) ) ){   
                                        wp_delete_file(AFFILIATEPRESS_UPLOAD_DIR . '/' . basename($affiliatepress_creative_image_url));// phpcs:ignore
                                    }
                                }
                            }

                        }                        

                    }

                }

            }

            wp_send_json($response);
            die();            
        }

        function affiliatepress_creativen_bulk_status_change_func(){
            global $wpdb, $affiliatepress_tbl_ap_creative, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'change_creative_status', true, 'ap_wp_nonce' ); // phpcs:ignore
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

            if(!current_user_can('affiliatepress_creative')){
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

            if (!empty($_POST['bulk_action'])) { // phpcs:ignore
                // phpcs:ignore because already sanitize using below function affiliatepress_array_sanatize_integer_field
                $affiliatepress_creative_ids = (isset($_POST['ids']))?stripslashes_deep($_POST['ids']):'';// phpcs:ignore 
                if(!empty($affiliatepress_creative_ids)){
                    $affiliatepress_creative_ids = json_decode($affiliatepress_creative_ids, true);
                }
                $affiliatepress_bulk_action = sanitize_text_field($_POST['bulk_action']); // phpcs:ignore 
                $affiliatepress_new_status  = ($affiliatepress_bulk_action == 'active')?1:(($affiliatepress_bulk_action == 'inactive')?0:'');

                if(is_array($affiliatepress_creative_ids) && ($affiliatepress_new_status == 0 || $affiliatepress_new_status == 1)){
                    $affiliatepress_creative_ids = ! empty($affiliatepress_creative_ids) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_integer_field' ), $affiliatepress_creative_ids) : array(); // phpcs:ignore                    
                    if (!empty($affiliatepress_creative_ids)) {
                        foreach ( $affiliatepress_creative_ids as $affiliatepress_creative_key => $affiliatepress_creative_id ) {
                            if (is_array($affiliatepress_creative_id) ) {
                                $affiliatepress_creative_id = intval($affiliatepress_creative_id['item_id']);
                            }else{
                                $affiliatepress_creative_id = intval($affiliatepress_creative_id);
                            }                            
                            $affiliatepress_creative_rec = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_creative, 'ap_creative_id,ap_creative_status', 'WHERE ap_creative_id = %d', array( $affiliatepress_creative_id), '', '', '', false, true, ARRAY_A);                            
                            $affiliatepress_creative_id = (isset($affiliatepress_creative_rec['ap_creative_id']))?intval($affiliatepress_creative_rec['ap_creative_id']):0;
                            $affiliatepress_old_status = (isset($affiliatepress_creative_rec['ap_creative_status']))?intval($affiliatepress_creative_rec['ap_creative_status']):0;
                            if($affiliatepress_creative_id != 0 && $affiliatepress_creative_id){

                                $this->affiliatepress_update_record($affiliatepress_tbl_ap_creative, array('ap_creative_status' => $affiliatepress_new_status), array( 'ap_creative_id' => $affiliatepress_creative_id ));
                                $response['id']         = $affiliatepress_creative_id;
                                $response['variant']    = 'success';
                                $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                                $response['msg']        = esc_html__('Creative status has been updated successfully.', 'affiliatepress-affiliate-marketing');                                
                                do_action('affiliatepress_after_commissions_status_change',$affiliatepress_creative_id,$affiliatepress_new_status,$affiliatepress_old_status,'backend');
                                
                                //do_action('affiliatepress_backend_after_commissions_status_change',$affiliatepress_creative_id,$affiliatepress_new_status,$affiliatepress_old_status);

                            }
                                                                           
                        }
                    }
                }
            }
            wp_send_json($response);  
        }

        /**
         * Function for creative status change
         *
         * @return json
         */
        function affiliatepress_change_creative_status_func(){

            global $wpdb, $affiliatepress_tbl_ap_creative,$AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'change_affiliate_status', true, 'ap_wp_nonce' );            
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Creative status has not been updated successfully', 'affiliatepress-affiliate-marketing');
            
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_creative')){
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
            $affiliatepress_new_status   = isset($_POST['new_status']) ? intval($_POST['new_status']) : ''; // phpcs:ignore
            $affiliatepress_old_status   = isset($_POST['old_status']) ? intval($_POST['old_status']) : ''; // phpcs:ignore
            if($affiliatepress_update_id){

                $this->affiliatepress_update_record($affiliatepress_tbl_ap_creative, array('ap_creative_status'=>$affiliatepress_new_status), array( 'ap_creative_id' => $affiliatepress_update_id ));

                $response['id']         = $affiliatepress_update_id;
                $response['variant']    = 'success';
                $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']        = esc_html__('Creative status has been updated successfully.', 'affiliatepress-affiliate-marketing');

            }

            wp_send_json($response);
            exit;            

        }    

        
        /**
         * Creative module on load methods
         *
         * @return string
         */
        function affiliatepress_creative_dynamic_on_load_methods_func($affiliatepress_creative_dynamic_on_load_methods){

            $affiliatepress_creative_dynamic_on_load_methods.='
            this.loadCreatives().catch(error => {
                console.error(error)
            });            
            ';
            return $affiliatepress_creative_dynamic_on_load_methods;
        }        
      

        /**
         * Function for get affiliate data
         *
         * @return json
         */
        function affiliatepress_get_creatives(){
            
            global $wpdb, $affiliatepress_tbl_ap_creative, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_creative', true, 'ap_wp_nonce' );
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

            if(!current_user_can('affiliatepress_creative')){
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
            if (! empty($_REQUEST['search_data']) ){// phpcs:ignore
                if (isset($_REQUEST['search_data']['ap_creative_name']) && !empty($_REQUEST['search_data']['ap_creative_name']) ) {// phpcs:ignore
                    $affiliatepress_search_name   = sanitize_text_field($_REQUEST['search_data']['ap_creative_name']);// phpcs:ignore
                    $affiliatepress_where_clause.= $wpdb->prepare( " AND ap_creative_name LIKE %s ", '%'.$affiliatepress_search_name.'%' );
                }
            }

            $affiliatepress_tbl_ap_creative_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_creative); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_creative contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_get_total_creatives = intval($wpdb->get_var("SELECT count(ap_creative_id) FROM {$affiliatepress_tbl_ap_creative_temp} {$affiliatepress_where_clause}")); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_creative_temp is a table name. false alarm

            $affiliatepress_pagination_count = ceil(intval($affiliatepress_get_total_creatives) / $affiliatepress_perpage);
            
            if($affiliatepress_currentpage > $affiliatepress_pagination_count && $affiliatepress_pagination_count > 0){
                $affiliatepress_currentpage = $affiliatepress_pagination_count;
                $affiliatepress_offset = ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage );
            }
            if(empty($affiliatepress_order)){
                $affiliatepress_order = 'DESC';
            }
            if(empty($affiliatepress_order_by)){
                $affiliatepress_order_by = 'ap_creative_id';
            }
            
            $affiliatepress_creatives_record   = $wpdb->get_results("SELECT * FROM {$affiliatepress_tbl_ap_creative_temp} {$affiliatepress_where_clause}  order by {$affiliatepress_order_by} {$affiliatepress_order} LIMIT {$affiliatepress_offset} , {$affiliatepress_perpage}", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_creative_temp is a table name & already prepare by affiliatepress_tablename_prepare function. false alarm

            $affiliatepress_creatives = array();
            if (! empty($affiliatepress_creatives_record) ) {
                $affiliatepress_counter = 1;
                foreach ( $affiliatepress_creatives_record as $affiliatepress_key=>$affiliatepress_single_record ) {

                    $affiliatepress_creative = $affiliatepress_single_record;
                    
                    $affiliatepress_creative['ap_creative_id']    = intval($affiliatepress_single_record['ap_creative_id']);
                    $affiliatepress_creative['ap_creative_name']  = esc_html($affiliatepress_single_record['ap_creative_name']);
                    $affiliatepress_creative['ap_creative_description']  = esc_html($affiliatepress_single_record['ap_creative_description']);
                    $affiliatepress_creative['ap_creative_type']  = esc_html($affiliatepress_single_record['ap_creative_type']);                    
                    $affiliatepress_creative['ap_creative_alt_text']  = esc_html($affiliatepress_single_record['ap_creative_alt_text']);
                    $affiliatepress_creative['ap_creative_text']  = esc_html($affiliatepress_single_record['ap_creative_text']);
                    $affiliatepress_creative['ap_creative_landing_url']  = (!empty($affiliatepress_single_record['ap_creative_landing_url']))?esc_url($affiliatepress_single_record['ap_creative_landing_url']):'';
                    $affiliatepress_creative['ap_creative_status']  = esc_html($affiliatepress_single_record['ap_creative_status']);

                    $affiliatepress_creative['change_status_loader']  = '';
                    $affiliatepress_creative['image_url']             = '';
                    if(!empty($affiliatepress_creative['ap_creative_image_url'])){
                        $affiliatepress_creative['image_url'] = esc_url(AFFILIATEPRESS_UPLOAD_URL.'/'.$affiliatepress_creative['ap_creative_image_url']);                        
                    }
                    
                    $affiliatepress_creatives[] = $affiliatepress_creative;

                }
            }

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');            
            $response['items'] = $affiliatepress_creatives;
            $response['total'] = $affiliatepress_get_total_creatives;
            $response['pagination_count'] = $affiliatepress_pagination_count;
            

            wp_send_json($response);
            exit;            
        }



        
        /**
         * Function for creative dynamic const add in vue
         *
         * @return string
        */
        function affiliatepress_creative_dynamic_constant_define_func($affiliatepress_creative_dynamic_constant_define){
            $affiliatepress_creative_dynamic_constant_define.='
                const open_modal = ref(false);
                affiliatepress_return_data["open_modal"] = open_modal;            
            ';
            return $affiliatepress_creative_dynamic_constant_define;
        }

        /**
         * Function for creative vue data
         *
         * @return array
        */
        function affiliatepress_creative_dynamic_data_fields_func($affiliatepress_creative_vue_data_fields){            
            
            global $AffiliatePress,$affiliatepress_creative_vue_data_fields,$affiliatepress_global_options;

            $affiliatepress_options = $affiliatepress_global_options->affiliatepress_global_options();

            $affiliatepress_all_creatives_status = $affiliatepress_options['creative_status'];

            $affiliatepress_creative_vue_data_fields['creatives_org'] = $affiliatepress_creative_vue_data_fields['creatives'];
            $affiliatepress_creative_vue_data_fields['all_creatives_status'] = $affiliatepress_all_creatives_status;
            $affiliatepress_creative_vue_data_fields['affiliates']['affiliate_user_name'] = '';

            $affiliatepress_creative_vue_data_fields = apply_filters('affiliatepress_backend_modify_creative_data_fields', $affiliatepress_creative_vue_data_fields);

            $affiliatepress_creative_vue_data_fields = wp_json_encode($affiliatepress_creative_vue_data_fields);

            return $affiliatepress_creative_vue_data_fields;

        }
        
        /**
         * Creative vue methof
         *
         * @param  string $affiliatepress_creative_dynamic_vue_methods
         * @return string
         */
        function affiliatepress_creative_dynamic_vue_methods_func($affiliatepress_creative_dynamic_vue_methods){
            global $affiliatepress_notification_duration;

            $affiliatepress_add_other_bulk_action = "";
            $affiliatepress_add_other_bulk_action .= apply_filters('affiliatepress_add_other_bulk_action', $affiliatepress_add_other_bulk_action);

            $affiliatepress_creative_dynamic_vue_methods.='
            handleSizeChange(val) {
                this.perPage = val;
                this.loadCreatives();
            },
            handleCurrentChange(val) {
                this.currentPage = val;
                this.loadCreatives();
            },
            affiliatepress_upload_creative_image_func(response, file, fileList){
                const vm = this;
                if(response != ""){
                    vm.creatives.avatar_url = response.upload_url;
                    vm.creatives.avatar_name = response.upload_file_name;
                    vm.creatives.ap_creative_image_url = response.upload_file_name;
                }
            },
            affiliatepress_replace_image(files, fileList) {
                const vm = this;
                vm.$refs.avatarRef.clearFiles();
                vm.creatives.avatar_url = "";
                vm.creatives.avatar_name = "";
                vm.creatives.ap_creative_image_url = "";
                const file = files[0];
                vm.$refs.avatarRef.handleStart(file);
                vm.$refs.avatarRef.submit();
            },
            affiliatepress_remove_affiliate_avatar() {
                const vm = this
                var upload_url = vm.affiliates.avatar_url;
                var upload_filename = vm.affiliates.avatar_name;
                var postData = { action:"affiliatepress_remove_creative_avatar", upload_file_url: upload_url,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    vm.creatives.avatar_url = "";
                    vm.creatives.avatar_name = "";
                    vm.$refs.avatarRef.clearFiles();
                }.bind(vm) )
                .catch( function (error) {
                    console.log(error);
                });
                                
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
                    return false;
                }else{
                    var ap_image_size = parseFloat(file.size / 1000000);
                    if(ap_image_size >= vm.creative_upload_file_size){
                        vm.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Please upload maximum 5 MB file only', 'affiliatepress-affiliate-marketing').'",
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });                    
                        return false;
                    }
                }
            },
            copy_affiliate_data(copy_data){
				const vm = this;				
				var affiliatepress_dummy_elem = document.createElement("textarea");
				document.body.appendChild(affiliatepress_dummy_elem);
				affiliatepress_dummy_elem.value = copy_data;
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
            editCreative(ap_creative_id,index,row){
                const vm = this;
                vm.open_modal = true;
                var creatie_edit_data = { action: "affiliatepress_edit_creative",edit_id: ap_creative_id,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }
                axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(creatie_edit_data)).then(function(response){
                    if(response.data.creatives.ap_creative_id != undefined){
                        vm.creatives.ap_creative_id = response.data.creatives.ap_creative_id;
                    } 
                    if(response.data.creatives.image_list != undefined){
                        vm.creatives.image_list = response.data.creatives.image_list;
                    }                    
                    if(response.data.creatives.creative_shortcode != undefined){
                        vm.creatives.creative_shortcode = response.data.creatives.creative_shortcode;
                    } 
                    if(response.data.creatives.ap_creative_name != undefined){
                        vm.creatives.ap_creative_name = response.data.creatives.ap_creative_name;
                    } 
                    if(response.data.creatives.ap_creative_description != undefined){
                        vm.creatives.ap_creative_description = response.data.creatives.ap_creative_description;
                    } 
                    if(response.data.creatives.ap_creative_type != undefined){
                        vm.creatives.ap_creative_type = response.data.creatives.ap_creative_type;
                    } 
                    if(response.data.creatives.ap_creative_image_url != undefined){
                        vm.creatives.ap_creative_image_url = response.data.creatives.ap_creative_image_url;
                    }                     
                    if(response.data.creatives.ap_creative_alt_text != undefined){
                        vm.creatives.ap_creative_alt_text = response.data.creatives.ap_creative_alt_text;
                        vm.creatives.avatar_url = response.data.creatives.ap_creative_image_url;
                    }  
                    if(response.data.creatives.ap_creative_alt_text != undefined){
                        vm.creatives.ap_creative_alt_text = response.data.creatives.ap_creative_alt_text;
                    } 
                    if(response.data.creatives.ap_creative_text != undefined){
                        vm.creatives.ap_creative_text = response.data.creatives.ap_creative_text;
                    }
                    if(response.data.creatives.ap_creative_landing_url != undefined){
                        vm.creatives.ap_creative_landing_url = response.data.creatives.ap_creative_landing_url;
                    }
                    if(response.data.creatives.ap_creative_status != undefined){
                        vm.creatives.ap_creative_status = response.data.creatives.ap_creative_status;
                    }                                
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
            saveCreative(form_ref){                
                vm = this;
                this.$refs[form_ref].validate((valid) => {     
                    if (valid) {
                        var postdata = {"action":"affiliatepress_add_creative"};
                        postdata.creatives = JSON.stringify(vm.creatives);
                        //postdata.action = "affiliatepress_add_creative";
                        
                        vm.is_disabled = true;
                        vm.is_display_save_loader = "1";
                        vm.savebtnloading = true;
                        postdata._wpnonce = "'.esc_html(wp_create_nonce('ap_wp_nonce')).'";                        
                        setTimeout(function(){
                            axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postdata ) )
                            .then(function(response){
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
                                    vm.loadCreatives();
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
                        },1000);
                    }else{
                        return false;
                    }
                });
            },            
            applyFilter(){
                const vm = this;
                vm.currentPage = 1;
                vm.loadCreatives();
            },                                
            resetFilter(){
                const vm = this;     
                const formValues = Object.values(this.creatives_search);
                const hasValue = formValues.some(value => {
                    if (typeof value === "string") {
                        return value.trim() !== "";
                    }
                    if (Array.isArray(value)) {
                        return value.length > 0;
                    }
                    return false;
                });                           
                vm.creatives_search.ap_creative_name = "";
                if(hasValue){
                    vm.loadCreatives();
                }                
                vm.is_multiple_checked = false;
                vm.multipleSelection = [];
            },
            affiliatepress_change_status(update_id, index, new_status, old_status){
                const vm = this;                
                vm.items[index].change_status_loader = 1;                
                var postData = { action:"affiliatepress_change_creative_status", update_id: update_id, new_status: new_status, old_status: old_status, _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    if(response.data.variant == "success"){                    
                        vm.$notify({
                            title: "'.esc_html__('Success', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Creative status changed successfully', 'affiliatepress-affiliate-marketing').'",
                            type: "success",
                            customClass: "success_notification",
                            duration:'.intval($affiliatepress_notification_duration).', 
                        });
                        vm.loadCreatives(false);
                    }else{
                        vm.$notify({
                        title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                        message: response.data.msg,
                        type: "error",
                        customClass: "error_notification",
                        duration:'.intval($affiliatepress_notification_duration).',                         
                    });
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
            async loadCreatives(flag = true){
                const vm = this;
                if(flag){
                    vm.is_display_loader = "1";
                }                
                vm.enabled = true;
                vm.is_apply_disabled = true;      
                affiliatespress_search_data = vm.creatives_search;
                var postData = { action:"affiliatepress_get_creatives", perpage:this.perPage, order_by:this.order_by, order:this.order, currentpage:this.currentPage, search_data: affiliatespress_search_data,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
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
                .catch( function(error){
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
                if(form_ref){
                    this.$refs[form_ref].resetFields();
                }                
                vm.creatives = JSON.parse(JSON.stringify(vm.creatives_org));
                var div = document.getElementById("ap-drawer-body");
                if(div){
                    div.scrollTop = 0;
                }                
            },
            closeModal(form_ref){
                vm = this;
                var div = document.getElementById("ap-drawer-body");
                if(div){
                    div.scrollTop = 0;
                }                
                vm.open_modal = false;
                if(form_ref){
                    this.$refs[form_ref].resetFields();
                }                
                vm.creatives = JSON.parse(JSON.stringify(vm.creatives_org));
            },
            handleSortChange({ column, prop, order }){                
                var vm = this;
                if(prop == "ap_creative_name"){
                    vm.order_by = "ap_creative_name"; 
                } 
                if(prop == "ap_creative_id"){
                    vm.order_by = "ap_creative_id"; 
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
                this.loadCreatives(true);                 
            },
            handleSelectionChange(val) {
                const items_obj = val;
                this.multipleSelection = [];
                var temp_data = [];
                Object.values(items_obj).forEach(val => {
                    temp_data.push({"item_id" : val.ap_creative_id});
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
                    if(this.multipleSelection.length > 0 && (this.bulk_action == "active" || this.bulk_action == "inactive")){
                        var bulk_action_postdata = {
                            action:"affiliatepress_creative_bulk_status_change",
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
                            vm.loadCreatives(true);                     
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
                    '.$affiliatepress_add_other_bulk_action.'
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
                vm.loadCreatives();
            },                                                     
            ';

            return $affiliatepress_creative_dynamic_vue_methods;

        }
        
        /**
         * Function for dynamic View load
         *
         * @return html
        */
        function affiliatepress_creative_dynamic_view_load_func(){

            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/creative/manage_creative.php';
            $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_creative_view_file_path', $affiliatepress_load_file_name);
            include $affiliatepress_load_file_name;

        }

        
        /**
         * Function for Creatie default Vue Data
         *
         * @return void
        */
        function affiliatepress_creative_vue_data_fields(){

            global $affiliatepress_creative_vue_data_fields,$affiliatepress_global_options;          
            $affiliatepress_pagination          = wp_json_encode(array( 10, 20, 50, 100, 200, 300, 400, 500 ));
            $affiliatepress_pagination_arr      = json_decode($affiliatepress_pagination, true);
            $affiliatepress_pagination_selected = $this->affiliatepress_per_page_record;

            $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_pagination_value = (isset($affiliatepress_global_options_data['pagination_val']))?$affiliatepress_global_options_data['pagination_val']:array();

            $affiliatepress_creative_vue_data_fields = array(
                'bulk_action'                => 'bulk_action',
                'bulk_options'               => array(
                    array(
                        'value' => 'bulk_action',
                        'label' => esc_html__('Bulk Action', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'active',
                        'label' => esc_html__('Active', 'affiliatepress-affiliate-marketing'),
                    ), 
                    array(
                        'value' => 'inactive',
                        'label' => esc_html__('Inactive', 'affiliatepress-affiliate-marketing'),
                    ),                     
                ),
                'loading'                    => false,
                'creatives_search'           => array(
                    "ap_creative_name"       => '',
                ),
                'creatives'                 => array(
                    "avatar_url"                   => "",
                    "avatar_name"                  => "",
                    "image_list"                   => [],                    
                    "ap_creative_id"               => "",
                    "ap_creative_name"             => "",
                    "ap_creative_description"      => "",
                    "ap_creative_type"             => "image",
                    "ap_creative_image_url"        => "",
                    "ap_creative_alt_text"         => "",
                    "ap_creative_text"             => "",
                    "ap_creative_landing_url"      => "",
                    "ap_creative_status"           => "1", 
                    "creative_shortcode"           => "",                    
                ), 
                'rules'                      => array(
                    'ap_creative_name'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter creative name', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),                                              
                    ),
                    'ap_creative_type'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please select creative type', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),                                              
                    ),
                    'ap_creative_image_url'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add creative image', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),                                              
                    ), 
                    'ap_creative_text'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please enter creative text', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),                                              
                    ),                                        
                    'ap_creative_status'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please select creative status', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),                                              
                    ),

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
                'is_disabled'                => false,
                'is_apply_disabled'          => false,
                'is_display_save_loader'     => '0',
                'is_multiple_checked'        => false,              
                'pagination_length_val'      => '10',
                'pagination_val'             => $affiliatepress_pagination_value,
                'affiliate_add_disable' => true,
                'creative_upload_file_size' => 5,
            );
        }



    }
}
global $affiliatepress_creative;
$affiliatepress_creative = new affiliatepress_creative();
