<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_commissions') ) {
    class affiliatepress_commissions Extends AffiliatePress_Core{
        
        var $affiliatepress_per_page_record;

        function __construct(){
            
            $this->affiliatepress_per_page_record = 10;

            /**Function for commission vue data */
            add_action( 'admin_init', array( $this, 'affiliatepress_commissions_vue_data_fields') );

            /* Dynamic Constant */
            add_filter('affiliatepress_commissions_dynamic_constant_define',array($this,'affiliatepress_commissions_dynamic_constant_define_func'),10,1);
            
            /* Dynamic Vue Fields */
            add_filter('affiliatepress_commissions_dynamic_data_fields',array($this,'affiliatepress_commissions_dynamic_data_fields_func'),10,1);

            /* Vue Load */
            add_action('affiliatepress_commissions_dynamic_view_load', array( $this, 'affiliatepress_commissions_dynamic_view_load_func' ), 10);

            /* Vue Method */
            add_filter('affiliatepress_commissions_dynamic_vue_methods',array($this,'affiliatepress_commissions_dynamic_vue_methods_func'),10,1);

            /* Dynamic On Load Method */
            add_filter('affiliatepress_commissions_dynamic_on_load_methods', array( $this, 'affiliatepress_commissions_dynamic_on_load_methods_func' ), 10);

            /* Get Commissions */
            add_action('wp_ajax_affiliatepress_get_commissions', array( $this, 'affiliatepress_get_commissions' ));

            /* Change Commission Status */
            add_action('wp_ajax_affiliatepress_change_commissions_status', array( $this, 'affiliatepress_change_commissions_status_func' ));

            /* Delete Commission */
            add_action('wp_ajax_affiliatepress_delete_commission', array( $this, 'affiliatepress_delete_commission' ));

            /* Bulk Action */
            add_action('wp_ajax_affiliatepress_commission_bulk_action', array( $this, 'affiliatepress_commission_bulk_action_func' ));  
            
            /* Get Affiliate User List */
            add_action('wp_ajax_affiliatepress_get_affiliate_users', array( $this, 'affiliatepress_get_affiliate_users' ));            

            /* Get Source Product List */
            add_action('wp_ajax_affiliatepress_get_source_products', array( $this, 'affiliatepress_get_source_products' ));

            /* Add commission */
            add_action('wp_ajax_affiliatepress_add_commission', array( $this, 'affiliatepress_add_commission_func' ));   
            
            /* Edit commission */
            add_action('wp_ajax_affiliatepress_edit_commission', array( $this, 'affiliatepress_edit_commission_func' ));  

            /*commission extra infromation show */
            add_action('wp_ajax_affiliatepress_show_commission_other_details', array( $this, 'affiliatepress_show_commission_other_details_func' )); 
            
            /* After Commission Created */
            add_action('affiliatepress_after_commission_created',array($this,'affiliatepress_after_commission_created_func'),10,2);

            /* Function for after change commission status */
            add_action('affiliatepress_after_commissions_status_change',array($this,'affiliatepress_after_commissions_status_change_func'),10,3);

            /* Bulk Action */
            add_action('wp_ajax_affiliatepress_commission_bulk_status_change', array( $this, 'affiliatepress_commission_bulk_status_change_func' ));  

            /**get user details */
            add_action('wp_ajax_affiliatepress_get_affiliate_user_details', array( $this, 'affiliatepress_get_affiliate_user_details_func' ));   

            /* before delete affiliate commisison product delete */
            add_action('affiliatepress_before_delete_affiliate',array($this,'affiliatepress_before_delete_affiliate_commission_delete_func'),10,1);

        }
               
         
        /**
         * Function for commission bulk action
         *
         * @return json
        */
        function affiliatepress_commission_bulk_status_change_func(){
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'change_commissions_status', true, 'ap_wp_nonce' ); // phpcs:ignore
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

            if(!current_user_can('affiliatepress_commissions')){
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
                $affiliatepress_commission_ids = (isset($_POST['ids']))?stripslashes_deep($_POST['ids']):'';// phpcs:ignore 
                if(!empty($affiliatepress_commission_ids)){
                    $affiliatepress_commission_ids = json_decode($affiliatepress_commission_ids, true);
                }
                $affiliatepress_bulk_action = sanitize_text_field($_POST['bulk_action']); // phpcs:ignore 
                $affiliatepress_new_status  = ($affiliatepress_bulk_action == 'approve')?1:(($affiliatepress_bulk_action == 'reject')?3:'');
                if($affiliatepress_bulk_action == "pending"){
                    $affiliatepress_new_status = 2;
                }
                if(is_array($affiliatepress_commission_ids) && !empty($affiliatepress_new_status)){
                    $affiliatepress_commission_ids = ! empty($affiliatepress_commission_ids) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_integer_field' ), $affiliatepress_commission_ids) : array(); // phpcs:ignore                    
                    if (!empty($affiliatepress_commission_ids)) {
                        foreach ( $affiliatepress_commission_ids as $affiliatepress_com_key => $affiliatepress_commission_id ) {
                            if (is_array($affiliatepress_commission_id) ) {
                                $affiliatepress_commission_id = intval($affiliatepress_commission_id['item_id']);
                            }else{
                                $affiliatepress_commission_id = intval($affiliatepress_commission_id);
                            }                            
                            $affiliatepress_commission_rec = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'ap_commission_id,ap_commission_status', 'WHERE ap_commission_id = %d AND 	ap_commission_status <> %d', array( $affiliatepress_commission_id, 4), '', '', '', false, true, ARRAY_A);                            
                            $affiliatepress_commission_id = (isset($affiliatepress_commission_rec['ap_commission_id']))?intval($affiliatepress_commission_rec['ap_commission_id']):0;
                            $affiliatepress_old_status = (isset($affiliatepress_commission_rec['ap_commission_status']))?intval($affiliatepress_commission_rec['ap_commission_status']):0;
                            if($affiliatepress_commission_id != 0 && $affiliatepress_commission_id){

                                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, array('ap_commission_status' => $affiliatepress_new_status), array( 'ap_commission_id' => $affiliatepress_commission_id ));
                                $response['id']         = $affiliatepress_commission_id;
                                $response['variant']    = 'success';
                                $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                                $response['msg']        = esc_html__('Commission status has been updated successfully.', 'affiliatepress-affiliate-marketing');                                
                                do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_new_status,$affiliatepress_old_status,'backend');
                                
                                //do_action('affiliatepress_backend_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_new_status,$affiliatepress_old_status);

                            }
                                                                           
                        }
                    }
                }
            }
            wp_send_json($response);            
        }

         /**
         * Function for affiliatepress_get_affiliate_user_details_func
         *
         * @return json
        */
        function affiliatepress_get_affiliate_user_details_func(){
            global $wpdb, $affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_affiliate_commissions,$AffiliatePress,$affiliatepress_tbl_ap_affiliate_visits,$affiliatepress_global_options;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_commissions', true, 'ap_wp_nonce' );
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

            if(!current_user_can('affiliatepress_commissions')){
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

            $affiliatepress_affiliate_id = isset($_POST['affiliat_id']) ? intval($_POST['affiliat_id']) : 0;
            $affiliatepress_affiliate_user_id = isset($_POST['affiliate_user_id']) ? intval($_POST['affiliate_user_id']) : 0;
            $affiliatepress_affiliate_data = array();
            $affiliatepress_wordpress_user_delete = "";
            if($affiliatepress_affiliate_id != 0 && $affiliatepress_affiliate_user_id != 0){
                $affiliatepress_user_data = get_userdata( $affiliatepress_affiliate_user_id);
                if(!empty($affiliatepress_user_data)){
                    $affiliatepress_affiliate_username = $affiliatepress_user_data->get('user_login');
                    $affiliatepress_affiliate_useremail = $affiliatepress_user_data->get('user_email');
                    $affiliatepress_affiliate_fullname = $affiliatepress_user_data->get('display_name');
                    $affiliatepress_affiliate_firstname = $affiliatepress_user_data->first_name;
                    $affiliatepress_affiliate_lastname = $affiliatepress_user_data->last_name;
    
                    if(empty($affiliatepress_affiliate_fullname)){
                        $affiliatepress_affiliate_fullname = $affiliatepress_affiliate_firstname." ".$affiliatepress_affiliate_lastname;
                    }
                    
                    $affiliatepress_site_url = get_site_url();
                    $affiliatepress_user_edit_link = $affiliatepress_site_url .'/wp-admin/user-edit.php?user_id=' . $affiliatepress_affiliate_user_id;
    
                    $affiliatepress_affiliate_data = array(
                        'affiliate_user_name' => $affiliatepress_affiliate_username,
                        'affiliate_user_email' => $affiliatepress_affiliate_useremail,
                        'affiliate_user_full_name'=> $affiliatepress_affiliate_fullname,
                        'affiliate_user_edit_link' => $affiliatepress_user_edit_link,
                    );
    
                    $affiliatepress_affiliate_data = apply_filters('affiliatepress_add_extra_affiliate_user_data',$affiliatepress_affiliate_data,$affiliatepress_affiliate_id);
    
                    $response['variant'] = 'success';
                    $response['affiliate_data'] = $affiliatepress_affiliate_data;
                    $response['affiliatepress_wordpress_user_delete'] = $affiliatepress_wordpress_user_delete;
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Commission Data.', 'affiliatepress-affiliate-marketing');
                }else{

                    $affiliatepress_wordpress_user_delete = esc_html__('User data not found', 'affiliatepress-affiliate-marketing');

                    $response['variant'] = 'success';
                    $response['affiliatepress_wordpress_user_delete'] = $affiliatepress_wordpress_user_delete;
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Commission Data.', 'affiliatepress-affiliate-marketing');
                    $response['affiliate_data'] = $affiliatepress_affiliate_data;
                }
                
            }

            wp_send_json($response);            
        }


        /**
         * Function for change commission
         *
         * @param  integer $affiliatepress_update_id
         * @param  integer $affiliatepress_new_status
         * @param  integer $affiliatepress_old_status
         * @return void
        */
        function affiliatepress_after_commissions_status_change_func($affiliatepress_update_id,$affiliatepress_new_status,$affiliatepress_old_status){
            global $affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_email_notifications;
            if(($affiliatepress_old_status == 2 || $affiliatepress_old_status == 3) && $affiliatepress_new_status == 1){
                $affiliatepress_commission_notification_type = 'commission_approved';                    
                $affiliatepress_affiliates_id = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'ap_affiliates_id', 'WHERE ap_commission_id = %d', array( $affiliatepress_update_id ), '', '', '', true, false, ARRAY_A));

                $affiliatepress_send_commission_approve_notification = true;
                $affiliatepress_send_commission_approve_notification = apply_filters('affiliatepress_send_commission_approve_notification', $affiliatepress_send_commission_approve_notification, $affiliatepress_update_id, $affiliatepress_affiliates_id);

                if(!empty($affiliatepress_commission_notification_type) && $affiliatepress_update_id && $affiliatepress_affiliates_id && $affiliatepress_send_commission_approve_notification){
                    $affiliatepress_email_notifications->affiliatepress_send_email_notification($affiliatepress_commission_notification_type,'commission',array('ap_commission_id'=>$affiliatepress_update_id,'ap_affiliates_id'=>$affiliatepress_affiliates_id));
                }                    
            }
        }

        /**
         * Function for after affiliate commission create
         *
         * @param  integer $affiliatepress_commission_id
         * @param  array $affiliatepress_inserted_data
         * @return void
        */
        function affiliatepress_after_commission_created_func($affiliatepress_commission_id,$affiliatepress_inserted_data){
            global $affiliatepress_email_notifications, $affiliatepress_tbl_ap_affiliate_visits, $affiliatepress_tbl_ap_commission_products, $affiliatepress_tbl_ap_affiliate_visits;
            $affiliatepress_visit_id = (isset($affiliatepress_inserted_data['ap_visit_id']))?intval($affiliatepress_inserted_data['ap_visit_id']):0;
            if($affiliatepress_commission_id && $affiliatepress_visit_id != 0){
                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_visits, array( 'ap_commission_id' => $affiliatepress_commission_id ), array( 'ap_visit_id' => $affiliatepress_visit_id ));

                if(!empty($affiliatepress_start_date)){
                    $affiliatepress_start_date = date('Y-m-d',strtotime($affiliatepress_start_date));//phpcs:ignore
                }

                $affiliatepress_visists_data = $this->affiliatepress_select_record( true, 'ap_affiliates_id, ap_visit_id, ap_visit_created_date', $affiliatepress_tbl_ap_affiliate_visits, '*', 'WHERE ap_visit_id = %d', array( $affiliatepress_visit_id ), '', '', '', false, true,ARRAY_A);

                $affiliatepress_affiliates_id = (isset($affiliatepress_visists_data['ap_affiliates_id']))?intval($affiliatepress_visists_data['ap_affiliates_id']):0;
                $affiliatepress_visit_id = (isset($affiliatepress_visists_data['ap_visit_id']))?intval($affiliatepress_visists_data['ap_visit_id']):0;
                $affiliatepress_visit_created_date = (isset($affiliatepress_visists_data['ap_visit_created_date'])) ? $affiliatepress_visists_data['ap_visit_created_date'] :"";

                if(!empty($affiliatepress_visit_created_date)){

                    global $affiliatepress_tracking;

                    $affiliatepress_visit_created_date = date('Y-m-d',strtotime($affiliatepress_visit_created_date));//phpcs:ignore
                    $affiliatepress_report_insert_type = "visits";

                    $affiliatepress_report_insert_data = array(
                        'ap_visit_id'      => $affiliatepress_visit_id,
                        'ap_affiliates_id' => $affiliatepress_affiliates_id,
                        'start_date'       => $affiliatepress_visit_created_date,                
                    );
                    
                    $affiliatepress_tracking->affiliatepress_affiliate_report_update_func($affiliatepress_report_insert_type,$affiliatepress_report_insert_data);                    

                }
                
            }
            if(isset($affiliatepress_inserted_data['products_commission']) && !empty($affiliatepress_inserted_data['products_commission']) && is_array($affiliatepress_inserted_data['products_commission'])){

                foreach($affiliatepress_inserted_data['products_commission'] as $affiliatepress_product_commission){
                    
                    $affiliatepress_args = array(
                        'ap_commission_id'                => $affiliatepress_commission_id,
                        'ap_commission_product_id'        => (isset($affiliatepress_product_commission['product_id']))?intval($affiliatepress_product_commission['product_id']):0,
                        'ap_commission_product_order_id'  => (isset($affiliatepress_product_commission['order_id']))?intval($affiliatepress_product_commission['order_id']):0,
                        'ap_commission_product_name'      => (isset($affiliatepress_product_commission['product_name']) && !empty($affiliatepress_product_commission['product_name']))?$affiliatepress_product_commission['product_name']:NULL,
                        'ap_commission_source'            => (isset($affiliatepress_inserted_data['ap_commission_source']) && !empty($affiliatepress_inserted_data['ap_commission_source']))?$affiliatepress_inserted_data['ap_commission_source']:NULL,  
                        'ap_commission_product_amount'    => (isset($affiliatepress_product_commission['commission_amount']))?floatval($affiliatepress_product_commission['commission_amount']):0,
                        'ap_commission_product_price'     => (isset($affiliatepress_product_commission['order_referal_amount']))?floatval($affiliatepress_product_commission['order_referal_amount']):0,
                        'ap_commission_product_rate'      => (isset($affiliatepress_product_commission['discount_val']))?floatval($affiliatepress_product_commission['discount_val']):0,
                        'ap_commission_product_type'      => (isset($affiliatepress_product_commission['discount_type']) && !empty($affiliatepress_product_commission['discount_type']))?$affiliatepress_product_commission['discount_type']:NULL,
                        'ap_commission_type'              => (isset($affiliatepress_product_commission['commission_basis']) && !empty($affiliatepress_product_commission['commission_basis']))?$affiliatepress_product_commission['commission_basis']:NULL,
                    );

                    $this->affiliatepress_insert_record($affiliatepress_tbl_ap_commission_products, $affiliatepress_args);

                }
                
            }
            $affiliatepress_commission_notification_type = 'commission_registered';
            $affiliatepress_affiliates_id = (isset($affiliatepress_inserted_data['ap_affiliates_id']))?$affiliatepress_inserted_data['ap_affiliates_id']:'';
            if(!empty($affiliatepress_commission_notification_type) && $affiliatepress_commission_id){
                $affiliatepress_email_notifications->affiliatepress_send_email_notification($affiliatepress_commission_notification_type,'commission',array('ap_commission_id'=>$affiliatepress_commission_id,'ap_affiliates_id'=>$affiliatepress_affiliates_id));
            }
        }

        /**
         * Function for get edit commission data
         *
         * @return json
        */
        function affiliatepress_edit_commission_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions,$AffiliatePress;
            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'edit_commission', true, 'ap_wp_nonce' );

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

            if(!current_user_can('affiliatepress_commissions')){
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

            $affiliatepress_commission_id  =  isset($_POST['edit_id']) ? intval($_POST['edit_id']) : ''; // phpcs:ignore

            $affiliatepress_commission_data = array();
            if(!empty($affiliatepress_commission_id)){
                
                $affiliatepress_commission = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, '*', 'WHERE ap_commission_id = %d', array( $affiliatepress_commission_id ), '', '', '', false, true,ARRAY_A);

                if(!empty($affiliatepress_commission)){

                    $affiliatepress_commission_data['ap_commission_id']                 = intval($affiliatepress_commission['ap_commission_id']);
                    $affiliatepress_commission_data['ap_affiliates_id']                 = intval($affiliatepress_commission['ap_affiliates_id']);
                    $affiliatepress_commission_data['affiliate_user_name']              = esc_html($AffiliatePress->affiliatepress_get_affiliate_user_name_by_id('',$affiliatepress_commission['ap_affiliates_id']));
                    $affiliatepress_commission_data['ap_commission_type']               = !empty($affiliatepress_commission['ap_commission_type']) ? stripslashes_deep($affiliatepress_commission['ap_commission_type']) : 'sale';
                    $affiliatepress_commission_data['ap_commission_status']             = esc_html($affiliatepress_commission['ap_commission_status']);
                    $affiliatepress_commission_data['ap_commission_reference_id']       = esc_html($affiliatepress_commission['ap_commission_reference_id']);
                    $affiliatepress_commission_data['ap_commission_product_ids']        = esc_html($affiliatepress_commission['ap_commission_product_ids']);
                    $affiliatepress_commission_data['ap_commission_reference_detail']   = stripslashes_deep($affiliatepress_commission['ap_commission_reference_detail']);
                    $affiliatepress_commission_data['ap_commission_reference_amount']   = esc_html($affiliatepress_commission['ap_commission_reference_amount']);
                    $affiliatepress_commission_data['ap_commission_source']             = esc_html($affiliatepress_commission['ap_commission_source']);
                    $affiliatepress_commission_data['ap_commission_amount']             = esc_html($affiliatepress_commission['ap_commission_amount']);
                    $affiliatepress_commission_data['ap_commission_order_amount']       = esc_html($affiliatepress_commission['ap_commission_order_amount']);
                    $affiliatepress_commission_data['ap_commission_note']               = esc_html($affiliatepress_commission['ap_commission_note']);
                    $affiliatepress_commission_data['ap_commission_created_date']       = esc_html($affiliatepress_commission['ap_commission_created_date']);

                    
                    $affiliatepress_commission_data = apply_filters('affiliatepress_modify_edit_commission_data',$affiliatepress_commission_data,$affiliatepress_commission,$affiliatepress_commission_id);
                    $response['variant'] = 'success';
                    $response['commissions'] = $affiliatepress_commission_data;
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Commission Data.', 'affiliatepress-affiliate-marketing');
                }

            }
            echo wp_json_encode($response);
            exit;                        

        }

        function affiliatepress_show_commission_other_details_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_affiliate_visits,$AffiliatePress,$affiliatepress_tbl_ap_affiliates;
            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'show_commisison_details', true, 'ap_wp_nonce' );

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

            if(!current_user_can('affiliatepress_commissions')){
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

            $affiliatepress_commission_id  =  isset($_POST['commission_id']) ? intval($_POST['commission_id']) : ''; // phpcs:ignore

            $affiliatepress_commission_data = array();
            if(!empty($affiliatepress_commission_id)){

                $affiliatepress_commission = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, '*', 'WHERE ap_commission_id = %d', array( $affiliatepress_commission_id ), '', '', '', false, true,ARRAY_A);

                if(!empty($affiliatepress_commission)){

                    $affiliatepress_affiliate_id = intval($affiliatepress_commission['ap_affiliates_id']);

                    $affiliatepress_affiliate_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_first_name, ap_affiliates_last_name, ap_affiliates_user_email', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliate_id ), '', '', '', false, true,ARRAY_A);
                    $affiliatepress_affiliates_first_name = isset($affiliatepress_affiliate_data['ap_affiliates_first_name']) ? sanitize_text_field($affiliatepress_affiliate_data['ap_affiliates_first_name']) : '';
                    $affiliatepress_affiliates_last_name  = isset($affiliatepress_affiliate_data['ap_affiliates_last_name']) ? sanitize_text_field($affiliatepress_affiliate_data['ap_affiliates_last_name']) : '';
                    $affiliatepress_affiliates_useremail  = isset($affiliatepress_affiliate_data['ap_affiliates_user_email']) ? sanitize_email($affiliatepress_affiliate_data['ap_affiliates_user_email']) : ''; 
                    $affiliatepress_full_name = $affiliatepress_affiliates_first_name.' '.$affiliatepress_affiliates_last_name;
                    if(empty($affiliatepress_full_name)){
                        $affiliatepress_full_name = $affiliatepress_affiliates_useremail;
                    }
                    $commission_created_date_formated = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_commission['ap_commission_created_date']);
                    $affiliatepress_source_plugin_name = $AffiliatePress->affiliatepress_get_supported_addon_name($affiliatepress_commission['ap_commission_source']);
                    $affiliatepress_formated_commission_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_commission['ap_commission_amount']);
                   
                    /** commission details */
                    $affiliatepress_commission_data['ap_commission_id']                 = intval($affiliatepress_commission['ap_commission_id']);
                    $affiliatepress_commission_data['ap_visit_id']                      = intval($affiliatepress_commission['ap_visit_id']);
                    $affiliatepress_commission_data['ap_affiliate_name']                = $affiliatepress_full_name;
                    $affiliatepress_commission_data['ap_commission_date']               = $commission_created_date_formated;
                    $affiliatepress_commission_data['ap_commission_source']             = $affiliatepress_source_plugin_name;
                    $affiliatepress_commission_data['ap_commission_amount']             = $affiliatepress_formated_commission_amount;
                    
                    /** visit details */
                    $affiliatepress_visit_id = intval($affiliatepress_commission['ap_visit_id']);
                    if(!empty($affiliatepress_visit_id)){
                        $affiliatepress_visit_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_visits, '*', 'WHERE ap_visit_id = %d', array( $affiliatepress_visit_id ), '', '', '', false, true,ARRAY_A);
                        $affiliatepress_image_url  = AFFILIATEPRESS_IMAGES_URL . '/country-flags/' . $affiliatepress_visit_data['ap_visit_iso_code'] . '.png';
                        $affiliatepress_server_root = isset($_SERVER['DOCUMENT_ROOT']) ? sanitize_text_field(wp_unslash($_SERVER['DOCUMENT_ROOT'])) : '';
                        $affiliatepress_image_path = $affiliatepress_server_root . wp_parse_url($affiliatepress_image_url, PHP_URL_PATH);
                        $affiliatepress_commission_data['ap_visit_country']            = sanitize_text_field($affiliatepress_visit_data['ap_visit_country']);
                        $affiliatepress_commission_data['ap_visit_ip_address']         = esc_html($affiliatepress_visit_data['ap_visit_ip_address']);
                        $affiliatepress_commission_data['ap_visit_landing_url']        = esc_url_raw($affiliatepress_visit_data['ap_visit_landing_url']);
                        $affiliatepress_commission_data['ap_visit_browser']            = esc_html($affiliatepress_visit_data['ap_visit_browser']);
                        $affiliatepress_commission_data['ap_referrer_url']             = esc_url_raw($affiliatepress_visit_data['ap_referrer_url']);
                        if (file_exists($affiliatepress_image_path)) {
                            $affiliatepress_commission_data['ap_visit_country_img_url'] = $affiliatepress_image_url;
                        }
                    }

                    $affiliatepress_commission_data = apply_filters('affiliatepress_add_commission_other_details',$affiliatepress_commission_data,$affiliatepress_commission);

                    $response['variant'] = 'success';
                    $response['commissions'] = $affiliatepress_commission_data;
                    $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Commission Data.', 'affiliatepress-affiliate-marketing');
                }

            }
            echo wp_json_encode($response);
            exit;                        

        }

        
        /**
         * Function for add commission
         *
         * @return json
        */
        function affiliatepress_add_commission_func(){
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions,$AffiliatePress,$affiliatepress_tracking,$affiliatepress_tbl_ap_affiliate_visits;
            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'add_commission', true, 'ap_wp_nonce' ); /* varify Nonce here */
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

            if(!current_user_can('affiliatepress_commissions')){
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

            $affiliatepress_commissions_data = '';
            if((isset($_POST['commissions'])) && !empty($_POST['commissions'])){ // phpcs:ignore 
                $affiliatepress_commissions_data = !empty($_POST['commissions']) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_field' ), json_decode(stripslashes_deep($_POST['commissions']),true)) : array(); // phpcs:ignore                
            }
            if(!empty($affiliatepress_commissions_data)){

                $affiliatepress_commission_id = (isset($affiliatepress_commissions_data['ap_commission_id']))?intval($affiliatepress_commissions_data['ap_commission_id']):0;
                $affiliatepress_affiliates_id = (isset($affiliatepress_commissions_data['ap_affiliates_id']))?intval($affiliatepress_commissions_data['ap_affiliates_id']):0;
                $affiliatepress_commission_type = (isset($affiliatepress_commissions_data['ap_commission_type']))?sanitize_text_field($affiliatepress_commissions_data['ap_commission_type']):'';
                $affiliatepress_commission_status = (isset($affiliatepress_commissions_data['ap_commission_status']))?intval($affiliatepress_commissions_data['ap_commission_status']):2;
                $affiliatepress_commission_product_ids = (isset($affiliatepress_commissions_data['ap_commission_product_ids']))?intval($affiliatepress_commissions_data['ap_commission_product_ids']):0;                
                $affiliatepress_commission_reference_id = (isset($affiliatepress_commissions_data['ap_commission_reference_id']))?intval($affiliatepress_commissions_data['ap_commission_reference_id']):0;
                $affiliatepress_commission_reference_detail = (isset($affiliatepress_commissions_data['ap_commission_reference_detail']))?sanitize_text_field($affiliatepress_commissions_data['ap_commission_reference_detail']):'';
                $affiliatepress_commission_reference_amount = (isset($affiliatepress_commissions_data['ap_commission_reference_amount']))?floatval($affiliatepress_commissions_data['ap_commission_reference_amount']):0;
                $affiliatepress_commission_source = (isset($affiliatepress_commissions_data['ap_commission_source']))?sanitize_text_field($affiliatepress_commissions_data['ap_commission_source']):'';
                $affiliatepress_commission_amount = (isset($affiliatepress_commissions_data['ap_commission_amount']))?floatval($affiliatepress_commissions_data['ap_commission_amount']):0;
                $affiliatepress_commission_currency = (isset($affiliatepress_commissions_data['ap_commission_currency']))?sanitize_text_field($affiliatepress_commissions_data['ap_commission_currency']):'';
                $affiliatepress_commission_note = (isset($affiliatepress_commissions_data['ap_commission_note']))?sanitize_text_field($affiliatepress_commissions_data['ap_commission_note']):'';
                $affiliatepress_commission_created_date = (isset($affiliatepress_commissions_data['ap_commission_created_date']))?sanitize_text_field($affiliatepress_commissions_data['ap_commission_created_date']):'';

                if($affiliatepress_affiliates_id == 0){
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Select affiliate user', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);                    
                }
                if($affiliatepress_commission_type == ""){
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Select commission type', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);                    
                }
                /*
                if($affiliatepress_commission_product_ids == 0){
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Select commission referance product.', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);                    
                }
                */            
                if($affiliatepress_commission_amount == 0){
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Enter commission amount.', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);                    
                }  
                /*                                              
                if($affiliatepress_commission_source == ''){
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Enter commission source detail.', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);                    
                }
                */    
                if($affiliatepress_commission_created_date == ''){
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Enter commission date', 'affiliatepress-affiliate-marketing');
                    wp_send_json($response);                    
                }                 
                if(empty($affiliatepress_commission_currency)){
                    $affiliatepress_currency_name = $AffiliatePress->affiliatepress_get_settings('payment_default_currency', 'affiliate_settings');
                    $affiliatepress_commission_currency = $affiliatepress_currency_name;
                }
                do_action('affiliatepress_add_commission_validation');                 
                $affiliatepress_args = array(                
                    'ap_affiliates_id'               => $affiliatepress_affiliates_id,
                    'ap_commission_type'             => $affiliatepress_commission_type,
                    'ap_commission_status'           => $affiliatepress_commission_status,                
                    'ap_commission_product_ids'      => $affiliatepress_commission_product_ids,
                    'ap_commission_reference_id'     => $affiliatepress_commission_reference_id,
                    'ap_commission_reference_detail' => $affiliatepress_commission_reference_detail,
                    'ap_commission_reference_amount' => $affiliatepress_commission_reference_amount,
                    'ap_commission_source'           => $affiliatepress_commission_source,
                    'ap_commission_amount'           => $affiliatepress_commission_amount,
                    'ap_commission_currency'         => $affiliatepress_commission_currency,
                    'ap_commission_note'             => $affiliatepress_commission_note,
                    'ap_commission_created_date'     => $affiliatepress_commission_created_date
                );                
                if($affiliatepress_commission_id == 0){ 

                    $affiliatepress_visit_args = array(
                        'ap_affiliates_id'      => $affiliatepress_affiliates_id,
                        'ap_visit_created_date' => $affiliatepress_commission_created_date,
                        'ap_visit_ip_address'   => '',
                        'ap_visit_country'      => '',
                        'ap_visit_iso_code'     => '',
                        'ap_visit_browser'      => '',
                        'ap_visit_landing_url'  => '',
                        'ap_referrer_url'       => '',   
                        'ap_affiliates_campaign_name' =>'',           
                        'ap_affiliates_sub_id' => '',          
                    );

                    $affiliatepress_visit_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_affiliate_visits, $affiliatepress_visit_args);

                    do_action('affiliatepress_after_visit_insert', $affiliatepress_visit_id, $affiliatepress_visit_args);
                    
                    $affiliatepress_args['ap_visit_id'] = $affiliatepress_visit_id;

                    $affiliatepress_commission_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_args);
                    $response['variant'] = 'success';
                    $response['title']   = esc_html__('Sucess', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Commissions successfully added.', 'affiliatepress-affiliate-marketing');
                    
                    do_action('affiliatepress_after_commission_created',$affiliatepress_commission_id,$affiliatepress_args,'backend');
                    
                }else{
                    $affiliatepress_args = array(                                        
                        'ap_commission_type'             => $affiliatepress_commission_type,
                        'ap_commission_status'           => $affiliatepress_commission_status,                
                        'ap_commission_reference_amount' => $affiliatepress_commission_reference_amount,
                        'ap_commission_amount'           => $affiliatepress_commission_amount,
                        'ap_commission_note'             => $affiliatepress_commission_note,
                        'ap_commission_reference_detail' => $affiliatepress_commission_reference_detail,
                    );

                    $affiliatepress_old_ap_commission_status = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'ap_commission_status', 'WHERE ap_commission_id = %d', array( $affiliatepress_commission_id ), '', '', '', true, false,ARRAY_A);

                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, $affiliatepress_args, array( 'ap_commission_id' => $affiliatepress_commission_id ));

                    do_action('affiliatepress_after_commission_record_update',$affiliatepress_commission_id,$affiliatepress_args);

                    if($affiliatepress_old_ap_commission_status != $affiliatepress_commission_status){

                        do_action('affiliatepress_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_commission_status,$affiliatepress_old_ap_commission_status,'backend');

                        //do_action('affiliatepress_backend_after_commissions_status_change',$affiliatepress_commission_id,$affiliatepress_commission_status,$affiliatepress_old_ap_commission_status);
                        
                    }

                    $response['variant'] = 'success';
                    $response['title']   = esc_html__('Sucess', 'affiliatepress-affiliate-marketing');
                    $response['msg']     = esc_html__('Commission successfully updated.', 'affiliatepress-affiliate-marketing');
                    
                }
            }

            wp_send_json($response);
            die();
        }

        /**
         * Function for get source products
         *
         * @return json
         */
        function affiliatepress_get_source_products(){            
            global $wpdb, $AffiliatePress,$affiliatepress_tbl_ap_affiliates;            
            $response              = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'search_source_product', true, 'ap_wp_nonce' ); // phpcs:ignore 
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
            $response['variant'] = 'error';
            $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing');

            if(!current_user_can('affiliatepress_commissions')){
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

            $affiliatepress_commission_source = !empty( $_REQUEST['ap_commission_source'] ) ? sanitize_text_field( $_REQUEST['ap_commission_source'] ) : ''; // phpcs:ignore 
            $affiliatepress_search_product_str = !empty( $_REQUEST['search_product_str'] ) ? sanitize_text_field( $_REQUEST['search_product_str'] ) : ''; // phpcs:ignore 

            $affiliatepress_existing_products_data = $affiliatepress_existing_product_data = array();
            if(!empty($affiliatepress_search_product_str) && !empty($affiliatepress_commission_source)) {  

                $affiliatepress_existing_source_product_data = array();
                $affiliatepress_existing_source_product_data = apply_filters( 'affiliatepress_get_source_product',$affiliatepress_existing_source_product_data, $affiliatepress_commission_source, $affiliatepress_search_product_str);

                $response['variant']               = 'success';
                $response['products']              = $affiliatepress_existing_source_product_data;
                $response['title']                 = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']                   = esc_html__('Affiliate Data.', 'affiliatepress-affiliate-marketing'); 

            }
            wp_send_json($response);
            exit;

        }

        /**
         * Function for get affiliate user
         *
         * @return json
        */
        function affiliatepress_get_affiliate_users(){
            global $wpdb, $AffiliatePress,$affiliatepress_tbl_ap_affiliates;            
            $response              = array();
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'search_affiliate_user', true, 'ap_wp_nonce' );
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
            $response['variant'] = 'error';
            $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing');
            $affiliatepress_search_user_str = ! empty( $_REQUEST['search_user_str'] ) ? sanitize_text_field( $_REQUEST['search_user_str'] ) : ''; // phpcs:ignore 
            $affiliatepress_affiliates_id = ! empty( $_REQUEST['ap_affiliates_id'] ) ? intval( $_REQUEST['ap_affiliates_id'] ) : ''; // phpcs:ignore 
            

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
            
            $affiliatepress_existing_user_data = $affiliatepress_existing_users_data = array();

            if(!empty($affiliatepress_search_user_str)) {  

                $affiliatepress_user_table = $this->affiliatepress_tablename_prepare($wpdb->users); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->users contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                $wp_usermeta_table = $this->affiliatepress_tablename_prepare($wpdb->usermeta); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->usermeta contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
                $affiliatepress_tbl_ap_affiliates_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

                $affiliatepress_where_clause = ' Where 1 = 1 ';
                

                $affiliatepress_search_name_last = $affiliatepress_search_user_str;
                if(!empty($affiliatepress_search_user_str)){
                    $name_parts = explode(' ', $affiliatepress_search_user_str);                                    
                    $affiliatepress_search_user_str = isset($name_parts[0]) ? $name_parts[0] : '';
                    if(isset($name_parts[1])){
                        $affiliatepress_search_name_last = isset($name_parts[1]) ? $name_parts[1] : '';
                    }                                                
                }


                $affiliatepress_where_clause .= $wpdb->prepare( " AND ( affiliate.ap_affiliates_first_name LIKE %s OR affiliate.ap_affiliates_last_name LIKE %s OR affiliate.ap_affiliates_user_name LIKE %s)", $affiliatepress_search_user_str.'%',$affiliatepress_search_name_last.'%',$affiliatepress_search_user_str.'%');

                if(isset($_POST['not_get_affiliate'])){// phpcs:ignore
                    $affiliatepress_not_get_affiliate = intval($_POST['not_get_affiliate']);// phpcs:ignore
                    if($affiliatepress_not_get_affiliate){
                        $affiliatepress_where_clause .= $wpdb->prepare( " AND ( affiliate.ap_affiliates_id <> %d ) ", $affiliatepress_not_get_affiliate);
                    }                    
                }

                $affiliatepress_affiliates_record    = $wpdb->get_results("SELECT affiliate.ap_affiliates_id,affiliate.ap_affiliates_user_email, affiliate.ap_affiliates_first_name, affiliate.ap_affiliates_last_name , affiliate.ap_affiliates_user_name   FROM {$affiliatepress_tbl_ap_affiliates_temp} as affiliate {$affiliatepress_where_clause} ", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates_temp is a table name. false alarm  

                if(!empty($affiliatepress_affiliates_record)){

                    if(isset($_POST['allow_remove'])){
                        $affiliatepress_user                  = array();
                        $affiliatepress_user['value']         = '';
                        $affiliatepress_user['label']         = esc_html__('Clear Value', 'affiliatepress-affiliate-marketing');
                        $affiliatepress_existing_users_data[] = $affiliatepress_user;    
                    }

                    foreach($affiliatepress_affiliates_record as $affiliatepress_single_affiliate){
 
                        $ap_affiliates_first_name = $affiliatepress_single_affiliate['ap_affiliates_first_name'];
                        $ap_affiliates_last_name  = $affiliatepress_single_affiliate['ap_affiliates_last_name'];
                        $affiliatepress_full_name = $ap_affiliates_first_name.' '.$ap_affiliates_last_name;

                        $affiliatepress_affiliates_username  = $affiliatepress_single_affiliate['ap_affiliates_user_name'];

                        if(empty($affiliatepress_full_name)){
                            $affiliatepress_full_name = $affiliatepress_single_affiliate['ap_affiliates_user_email'];
                        }
                        $affiliatepress_user                  = array();
                        $affiliatepress_user['value']         = esc_html($affiliatepress_single_affiliate['ap_affiliates_id']);
                        $affiliatepress_user['label']         = esc_html($affiliatepress_full_name).' / '.$affiliatepress_affiliates_username;
                        $affiliatepress_existing_users_data[] = $affiliatepress_user;
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
            }
            wp_send_json($response);
            exit;
        }

        
        /**
         * Function for commission bulk action
         *
         * @return json
        */
        function affiliatepress_commission_bulk_action_func(){
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'delete_commissions', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_commissions')){
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
                // phpcs:ignore because santize in below function affiliatepress_array_sanatize_integer_field 
                $affiliatepress_delete_ids = (isset($_POST['ids']))?stripslashes_deep($_POST['ids']):''; // phpcs:ignore             
                if(!empty($affiliatepress_delete_ids)){
                    $affiliatepress_delete_ids = json_decode($affiliatepress_delete_ids, true);
                }
                if(is_array($affiliatepress_delete_ids)){
                    $affiliatepress_delete_ids = ! empty($affiliatepress_delete_ids) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_integer_field' ), $affiliatepress_delete_ids) : array(); // phpcs:ignore
                    if (!empty($affiliatepress_delete_ids)) {
                        foreach ( $affiliatepress_delete_ids as $affiliatepress_delete_key => $affiliatepress_delete_val ) {
                            if (is_array($affiliatepress_delete_val) ) {
                                $affiliatepress_delete_val = intval($affiliatepress_delete_val['item_id']);
                            }
                            $return = $this->affiliatepress_delete_commission($affiliatepress_delete_val);
                            if ($return ) {
                                $response['variant'] = 'success';
                                $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                                $response['msg']     = esc_html__('Commissions have been deleted successfully.', 'affiliatepress-affiliate-marketing');
                            } else {
                                $response['variant'] = 'warning';
                                $response['title']   = esc_html__('Warning', 'affiliatepress-affiliate-marketing');
                                $response['msg']     = esc_html__('Could not delete commission. This commission not deleted.', 'affiliatepress-affiliate-marketing');
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
         * Function for delete commission
         *
         * @return json
        */
        function affiliatepress_delete_commission($affiliatepress_commission_id = ''){

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress, $affiliatepress_tbl_ap_commission_products;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'delete_commissions', true, 'ap_wp_nonce' );            
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Commission not deleted.', 'affiliatepress-affiliate-marketing');
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_commissions')){
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

            if(empty($affiliatepress_commission_id)){
                $affiliatepress_commission_id = (isset($_POST['ap_commission_id']))?intval($_POST['ap_commission_id']):0; // phpcs:ignore 
            }
            if($affiliatepress_commission_id){

                //do_action( 'affiliatepress_before_delete_commission', $affiliatepress_commission_id );

                $affiliatepress_deleted_commission_data = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'ap_affiliates_id, ap_visit_id, ap_commission_created_date', 'WHERE ap_commission_id = %d', array( $affiliatepress_commission_id ), '', '', '', false, true,ARRAY_A);


                $this->affiliatepress_delete_record($affiliatepress_tbl_ap_affiliate_commissions, array( 'ap_commission_id' => $affiliatepress_commission_id ), array( '%d' ));

                $this->affiliatepress_delete_record($affiliatepress_tbl_ap_commission_products, array( 'ap_commission_id' => $affiliatepress_commission_id ), array( '%d' ));

                do_action( 'affiliatepress_after_delete_commission', $affiliatepress_commission_id, $affiliatepress_deleted_commission_data );

                $response['variant'] = 'success';
                $response['title']   = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Commission has been deleted successfully.', 'affiliatepress-affiliate-marketing');
                $return              = true;
                if (isset($_POST['action']) && !empty($_POST['action']) && sanitize_text_field(wp_unslash($_POST['action'])) == 'affiliatepress_delete_commission' ) { // phpcs:ignore 
                    wp_send_json($response);
                }
                return $return;
            }
            $affiliatepress_error_msg = esc_html__( 'Commission not deleted.', 'affiliatepress-affiliate-marketing');
            $response['variant'] = 'warning';
            $response['title']   = esc_html__('warning', 'affiliatepress-affiliate-marketing');
            $response['msg']     = $affiliatepress_error_msg;
            $return              = false;
            if (isset($_POST['action']) && !empty($_POST['action']) && sanitize_text_field(wp_unslash($_POST['action'])) == 'affiliatepress_delete_commission' ) {  // phpcs:ignore 
                wp_send_json($response);
            }
            return $return;
        }    

        /**
         * Function for add commissions status
         *
         * @return json
        */
        function affiliatepress_change_commissions_status_func(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress, $affiliatepress_email_notifications;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'change_commissions_status', true, 'ap_wp_nonce' );            
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

            if(!current_user_can('affiliatepress_commissions')){
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
                $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, array('ap_commission_status'=>$affiliatepress_new_status), array( 'ap_commission_id' => $affiliatepress_update_id ));
                $response['id']         = $affiliatepress_update_id;
                $response['variant']    = 'success';
                $response['title']      = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']        = esc_html__('Affiliates status has been updated successfully.', 'affiliatepress-affiliate-marketing');

                do_action('affiliatepress_after_commissions_status_change', $affiliatepress_update_id, $affiliatepress_new_status, $affiliatepress_old_status,'backend');

                //do_action('affiliatepress_backend_after_commissions_status_change', $affiliatepress_update_id, $affiliatepress_new_status, $affiliatepress_old_status);

            }

            wp_send_json($response);
            exit;
        }    
          
        /**
         * commission module on load methods
         *
         * @param  string $affiliatepress_commissions_dynamic_on_load_methods
         * @return string
         */
        function affiliatepress_commissions_dynamic_on_load_methods_func($affiliatepress_commissions_dynamic_on_load_methods){
            $affiliatepress_commissions_dynamic_on_load_methods.='
                this.loadcommissions().catch(error => {
                    console.error(error)
                });            
            ';
            return $affiliatepress_commissions_dynamic_on_load_methods;
        }        
      

        /**
         * Function for get affiliate data
         *
         * @return void
         */
        function affiliatepress_get_commissions(){
            
            global $wpdb, $affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_affiliate_commissions,$AffiliatePress,$affiliatepress_tbl_ap_affiliate_visits,$affiliatepress_global_options;

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_commissions', true, 'ap_wp_nonce' );
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

            if(!current_user_can('affiliatepress_commissions')){
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
            $affiliatepress_order       = (isset($_POST['order']) && !empty($_POST['order'])) ? sanitize_text_field(wp_unslash($_POST['order'])) : ''; // phpcs:ignore 
            $affiliatepress_order_by    = (isset($_POST['order_by']) && !empty($_POST['order_by'])) ? sanitize_text_field(wp_unslash($_POST['order_by'])) : ''; // phpcs:ignore 
            
            $affiliatepress_search_query = '';

            $affiliatepress_where_clause = " WHERE 1 = 1 ";
            if (!empty($_REQUEST['search_data'])){// phpcs:ignore
               
                if(isset($_REQUEST['search_data']['ap_commission_search_date']) && !empty($_REQUEST['search_data']['ap_commission_search_date']) ){// phpcs:ignore

                    $affiliatepress_start_date = (isset($_REQUEST['search_data']['ap_commission_search_date'][0]))?sanitize_text_field($_REQUEST['search_data']['ap_commission_search_date'][0]):'';// phpcs:ignore
                    $affiliatepress_end_date   = (isset($_REQUEST['search_data']['ap_commission_search_date'][1]))?sanitize_text_field($_REQUEST['search_data']['ap_commission_search_date'][1]):'';// phpcs:ignore
                    if(!empty($affiliatepress_start_date) && !empty($affiliatepress_end_date)){                        
                        $affiliatepress_start_date = date('Y-m-d',strtotime($affiliatepress_start_date)); // phpcs:ignore
                        $affiliatepress_end_date = date('Y-m-d',strtotime($affiliatepress_end_date)); // phpcs:ignore
                        $affiliatepress_where_clause.= $wpdb->prepare( " AND (DATE(commissions.ap_commission_created_date) >= %s AND DATE(commissions.ap_commission_created_date) <= %s) ", $affiliatepress_start_date, $affiliatepress_end_date);
                    }                    
                    
                }                
                if (isset($_REQUEST['search_data']['ap_affiliates_user']) && !empty($_REQUEST['search_data']['ap_affiliates_user']) ) {// phpcs:ignore

                    $affiliatepress_search_id   = intval($_REQUEST['search_data']['ap_affiliates_user']);// phpcs:ignore

                    $affiliatepress_where_clause.= $wpdb->prepare( " AND (affiliate.ap_affiliates_id = %d) ", $affiliatepress_search_id);

                }
                if (isset($_REQUEST['search_data']['commission_status']) && !empty($_REQUEST['search_data']['commission_status']) ) {// phpcs:ignore
                    $affiliatepress_commission_status = intval($_REQUEST['search_data']['commission_status']);// phpcs:ignore
                    $affiliatepress_where_clause.= $wpdb->prepare( " AND (commissions.ap_commission_status = %d) ", $affiliatepress_commission_status);
                }
            }  

            $affiliatepress_tbl_ap_affiliate_commissions_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_commissions contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_user_table = $this->affiliatepress_tablename_prepare($wpdb->users); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->users contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $wp_usermeta_table = $this->affiliatepress_tablename_prepare($wpdb->usermeta); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->usermeta contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
            $affiliatepress_tbl_ap_affiliates_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliates); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliates contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function            

            $affiliatepress_get_total_commissions = intval($wpdb->get_var("SELECT count(commissions.ap_commission_id) FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} as commissions INNER JOIN {$affiliatepress_tbl_ap_affiliates_temp} as affiliate  ON commissions.ap_affiliates_id = affiliate.ap_affiliates_id {$affiliatepress_where_clause}")); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm

            $affiliatepress_pagination_count = ceil(intval($affiliatepress_get_total_commissions) / $affiliatepress_perpage);

            if($affiliatepress_currentpage > $affiliatepress_pagination_count && $affiliatepress_pagination_count > 0){
                $affiliatepress_currentpage = $affiliatepress_pagination_count;
                $affiliatepress_offset = ( ( $affiliatepress_currentpage - 1 ) * $affiliatepress_perpage );
            }

            if(empty($affiliatepress_order)){
                $affiliatepress_order = 'DESC';
            }
            if(empty($affiliatepress_order_by)){
                $affiliatepress_order_by = 'commissions.ap_commission_id';
            }

            if($affiliatepress_order_by == "first_name"){
                $affiliatepress_order_by = 'affiliate.ap_affiliates_first_name';
            }
            $affiliatepress_commissions_record = $wpdb->get_results("SELECT commissions.*, affiliate.ap_affiliates_user_id, affiliate.ap_affiliates_first_name, affiliate.ap_affiliates_last_name FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} as commissions INNER JOIN {$affiliatepress_tbl_ap_affiliates_temp} as affiliate  ON commissions.ap_affiliates_id = affiliate.ap_affiliates_id {$affiliatepress_where_clause}  order by {$affiliatepress_order_by} {$affiliatepress_order} LIMIT {$affiliatepress_offset} , {$affiliatepress_perpage}", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliates is a table name. false alarm

            $affiliatepress_commissions = array();
            if (! empty($affiliatepress_commissions_record) ) {
                $affiliatepress_counter = 1;
                foreach ( $affiliatepress_commissions_record as $affiliatepress_key=>$affiliatepress_single_commission ) {

                    $affiliatepress_commission = $affiliatepress_single_commission;
                    $affiliatepress_commission['ap_commission_id']    = intval($affiliatepress_single_commission['ap_commission_id']);
                    $affiliatepress_user_id = $affiliatepress_single_commission['ap_affiliates_user_id'];
                    $affiliatepress_affiliate_id = $affiliatepress_single_commission['ap_affiliates_id'];
                    
                    $affiliatepress_user_first_name =  esc_html($affiliatepress_single_commission['ap_affiliates_first_name']);
                    $affiliatepress_user_last_name  =  esc_html($affiliatepress_single_commission['ap_affiliates_last_name']);

                    $affiliatepress_full_name = $affiliatepress_user_first_name." ".$affiliatepress_user_last_name;

                    $affiliatepress_formated_commission_reference_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_single_commission['ap_commission_reference_amount']);
                    $affiliatepress_formated_commission_amount = $AffiliatePress->affiliatepress_price_formatter_with_currency_symbol($affiliatepress_single_commission['ap_commission_amount']);

                    $affiliatepress_source_plugin_name = $AffiliatePress->affiliatepress_get_supported_addon_name($affiliatepress_single_commission['ap_commission_source']);
                    $affiliatepress_commission['source_plugin_name'] = esc_html($affiliatepress_source_plugin_name);
                    $affiliatepress_commission['change_status_loader'] = '';

                    $affiliatepress_commission['ap_formated_commission_reference_amount'] = esc_html($affiliatepress_formated_commission_reference_amount);
                    $affiliatepress_commission['ap_formated_commission_amount'] = $affiliatepress_formated_commission_amount;
                    $affiliatepress_commission['ap_commission_status_org'] = esc_html($affiliatepress_single_commission['ap_commission_status']);

                    $affiliatepress_commission['commission_order_link'] = '';
                    if($affiliatepress_single_commission['ap_commission_reference_id'] != 0){
                        $affiliatepress_commission['commission_order_link'] = apply_filters('affiliatepress_modify_commission_link', $affiliatepress_single_commission['ap_commission_reference_id'], $affiliatepress_single_commission['ap_commission_source']); 
                    }
                                      
                    $affiliatepress_commission['commission_created_date_formated'] = $AffiliatePress->affiliatepress_formated_date_display($affiliatepress_single_commission['ap_commission_created_date']);
                    $affiliatepress_commission['full_name']             = esc_html($affiliatepress_full_name);
                    $affiliatepress_commission['change_status_loader']  = '';

                    $affiliatepress_commission_product = (isset($affiliatepress_single_commission['ap_commission_reference_detail']) && !empty($affiliatepress_single_commission['ap_commission_reference_detail']))?$affiliatepress_single_commission['ap_commission_reference_detail']:'-';
                    
                    $affiliatepress_commission['affiliatepress_commission_product'] = $affiliatepress_commission_product;             
                    $affiliatepress_commission['affiliatepress_affiliate_id'] = $affiliatepress_affiliate_id;           
                    $affiliatepress_commission['affiliatepress_affiliate_user_id'] = $affiliatepress_user_id;             
                    
                    $affiliatepress_commission['row_class']  = '';

                    $affiliatepress_commission = apply_filters('affiliatepress_backend_modify_commission_row', $affiliatepress_commission, $affiliatepress_single_commission); 

                    $affiliatepress_commissions[] = $affiliatepress_commission;
                }
            }

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'success', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');            
            $response['items'] = $affiliatepress_commissions;
            $response['total'] = $affiliatepress_get_total_commissions;
            $response['pagination_count'] = $affiliatepress_pagination_count;
            
            wp_send_json($response);
            exit;            
        }

        function affiliatepress_before_delete_affiliate_commission_delete_func($affiliatepress_affiliate_id){

            global $affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_commission_products;

            $affiliatepress_get_commisison_ids = $this->affiliatepress_select_record(true, '', $affiliatepress_tbl_ap_affiliate_commissions, 'ap_commission_id', 'WHERE ap_affiliates_id  = %d', array( $affiliatepress_affiliate_id), '', '', '', false, false,ARRAY_A);

            foreach ($affiliatepress_get_commisison_ids as $affiliatepress_commisison_data) {
                $affiliatepress_commission_id = isset($affiliatepress_commisison_data['ap_commission_id']) ? intval($affiliatepress_commisison_data['ap_commission_id']) : 0;

                $this->affiliatepress_delete_record($affiliatepress_tbl_ap_commission_products, array( 'ap_commission_id' => $affiliatepress_commission_id ), array('%d'));
            }
        }

                
        /**
         * Function for dynamic const for commission module add in vue
         *
         * @param  string $affiliatepress_commissions_dynamic_constant_define
         * @return string
         */
        function affiliatepress_commissions_dynamic_constant_define_func($affiliatepress_commissions_dynamic_constant_define){

            $affiliatepress_commissions_dynamic_constant_define.='
                const open_modal = ref(false);
                affiliatepress_return_data["open_modal"] = open_modal;            
            ';
            return $affiliatepress_commissions_dynamic_constant_define;

        }
                
        /**
         * Function for commission vue data
         *
         * @param  array $affiliatepress_commissions_vue_data_fields
         * @return json
         */
        function affiliatepress_commissions_dynamic_data_fields_func($affiliatepress_commissions_vue_data_fields){            
            
            global $AffiliatePress,$affiliatepress_commissions_vue_data_fields,$affiliatepress_global_options;
                        
            $affiliatepress_options = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_all_commissions_status = $affiliatepress_options['commissions_status'];
            $affiliatepress_commissions_vue_data_fields['all_filter_commissions_status'] = $affiliatepress_all_commissions_status;

            foreach ($affiliatepress_all_commissions_status as $affiliatepress_key => $afiliatepress_status) {
                if($afiliatepress_status['value'] == 4){
                    $affiliatepress_commissions_vue_data_fields['commission_status_paid'] = $afiliatepress_status['text'];
                    unset($affiliatepress_all_commissions_status[$affiliatepress_key]);
                }
            }

            $affiliatepress_commissions_vue_data_fields['AffiliateUsersList'] = array();
            $affiliatepress_commissions_vue_data_fields['SourceProductsList'] = array();

            $affiliatepress_commissions_vue_data_fields['commissions_org'] = $affiliatepress_commissions_vue_data_fields['commissions'];

            $affiliatepress_commissions_vue_data_fields['all_commissions_status'] = $affiliatepress_all_commissions_status;
            $affiliatepress_commissions_vue_data_fields['affiliates']['affiliate_user_name'] = '';

            $affiliatepress_commissions_vue_data_fields['show_setting'] = 0;

            $affiliatepress_commissions_vue_data_fields = apply_filters('affiliatepress_backend_modify_commissiom_data_fields', $affiliatepress_commissions_vue_data_fields);
            
            return wp_json_encode($affiliatepress_commissions_vue_data_fields);

        }
        
        /**
         * Function for commission dynamic vue method 
         *
         * @param  string $affiliatepress_commissions_dynamic_vue_methods
         * @return string
         */
        function affiliatepress_commissions_dynamic_vue_methods_func($affiliatepress_commissions_dynamic_vue_methods){
            global $affiliatepress_notification_duration;


            $affiliatepress_edit_commission_more_vue_data = "";
            $affiliatepress_edit_commission_more_vue_data = apply_filters('affiliatepress_edit_commission_more_vue_data', $affiliatepress_edit_commission_more_vue_data);     

            $affiliatepress_add_posted_data_for_save_commission = "";
            $affiliatepress_add_posted_data_for_save_commission = apply_filters('affiliatepress_add_posted_data_for_save_commission', $affiliatepress_add_posted_data_for_save_commission);       
            
            $affiliatepress_response_add_user_details = "";
            $affiliatepress_response_add_user_details = apply_filters('affiliatepress_response_add_user_details', $affiliatepress_response_add_user_details);   

            $affiliatepress_commissions_dynamic_vue_methods.='
            editCommission(ap_commission_id,index,row){
                const vm = this;
                vm.open_modal = true;
                var creatie_edit_data = { action: "affiliatepress_edit_commission",edit_id: ap_commission_id,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }
                axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(creatie_edit_data)).then(function(response){

                    if(response.data.commissions.ap_affiliates_id != undefined){
                        vm.commissions.ap_affiliates_id = response.data.commissions.ap_affiliates_id;
                    }
                    if(response.data.commissions.affiliate_user_name != undefined){
                        vm.commissions.affiliate_user_name = response.data.commissions.affiliate_user_name;
                    }
                    if(response.data.commissions.ap_commission_id != undefined){
                        vm.commissions.ap_commission_id = response.data.commissions.ap_commission_id;
                    }                   
                    if(response.data.commissions.ap_commission_type != undefined){
                        vm.commissions.ap_commission_type = response.data.commissions.ap_commission_type;
                    } 
                    if(response.data.commissions.ap_commission_status != undefined){
                        vm.commissions.ap_commission_status = response.data.commissions.ap_commission_status;
                    } 
                    if(response.data.commissions.ap_commission_reference_id != undefined){
                        vm.commissions.ap_commission_reference_id = response.data.commissions.ap_commission_reference_id;
                    } 
                    if(response.data.commissions.ap_commission_product_ids != undefined){
                        vm.commissions.ap_commission_product_ids = response.data.commissions.ap_commission_product_ids;
                    }
                    if(response.data.commissions.ap_commission_reference_detail != undefined){
                        vm.commissions.ap_commission_reference_detail = response.data.commissions.ap_commission_reference_detail;
                    }
                    if(response.data.commissions.ap_commission_reference_amount != undefined){
                        vm.commissions.ap_commission_reference_amount = response.data.commissions.ap_commission_reference_amount;
                    }
                    if(response.data.commissions.ap_commission_source != undefined){
                        vm.commissions.ap_commission_source = response.data.commissions.ap_commission_source;
                    } 
                    if(response.data.commissions.ap_commission_amount != undefined){
                        vm.commissions.ap_commission_amount = response.data.commissions.ap_commission_amount;
                    } 
                    if(response.data.commissions.ap_commission_order_amount != undefined){
                        vm.commissions.ap_commission_order_amount = response.data.commissions.ap_commission_order_amount;
                    }                    
                    if(response.data.commissions.ap_commission_created_date != undefined){
                        vm.commissions.ap_commission_created_date = response.data.commissions.ap_commission_created_date;
                    }
                    if(response.data.commissions.ap_commission_note != undefined){
                        vm.commissions.ap_commission_note = response.data.commissions.ap_commission_note;
                    }                     
                    '.$affiliatepress_edit_commission_more_vue_data.'
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
            commission_extra_details(ap_commission_id,index,row){
                const vm = this;
                vm.open_modal = true;
                vm.commission_details_show = true;
                vm.is_display_commisison_details_loader = "1";
                var creatie_edit_data = { action: "affiliatepress_show_commission_other_details",commission_id: ap_commission_id,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }
                axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(creatie_edit_data)).then(function(response){

                    if(response.data.variant == "success"){
                        vm.commission_details = response.data.commissions;
                    }
                    else
                    {
                        vm.$notify({
                            title: response.data.title,
                            message: response.data.msg,
                            type: response.data.variant,
                            customClass: response.data.variant+"_notification",
                            duration:'.intval($affiliatepress_notification_duration).',
                        });
                    }
                    vm.is_display_commisison_details_loader = "0";
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
            saveCommission(form_ref){                
                vm = this;
                this.$refs[form_ref].validate((valid) => {     
                    if (valid) {
                        var postdata = {"action":"affiliatepress_add_commission"};
                        postdata.commissions = JSON.stringify(vm.commissions);
                        '.$affiliatepress_add_posted_data_for_save_commission.'
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
                                if (response.data.variant == "success"){                                    
                                    vm.loadcommissions();
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
            affiliatepress_change_source(){
                const vm = this;
                vm.commissions.ap_commission_product_ids = "";
                vm.SourceProductsList = [];
            },       
            get_affiliate_source_product(query) {
                const vm = this;	
                if (query !== "") {
                    vm.affiliatepress_user_loading = true;        
                    if (vm.affiliateUsersAbortController) {
                        vm.affiliateUsersAbortController.abort();
                    }   
                    vm.affiliateUsersAbortController = new AbortController();
                    var customer_action = { action:"affiliatepress_get_source_products",search_product_str:query,ap_commission_source:vm.commissions.ap_commission_source,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" } 
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( customer_action ), {signal: vm.affiliateUsersAbortController.signal} )
                    .then(function(response){
                        vm.affiliatepress_user_loading = false;
                        if(typeof response.data.products != "undefined"){
                            vm.SourceProductsList = response.data.products;
                        }else{
                            vm.SourceProductsList = [];
                        }
                    }).catch(function(error){
                        if (error.name === "CanceledError") {
                            return;
                        }
                        console.log(error);
                    });
                } else {
                    vm.SourceProductsList = [];
                }	
            }, 
            affiliatepress_select_products(){
                const vm = this;
                vm.commissions.ap_commission_reference_detail = "";
                for (let group of vm.SourceProductsList) {
                    const found = group.product_data.find(item => item.value === vm.commissions.ap_commission_product_ids);
                    if (found) {
                        vm.commissions.ap_commission_reference_detail = found.label;
                        break;
                    }
                }
                if(vm.$refs["selectRef"] && vm.$refs["selectRef"].$el.querySelector("input")){
                    setTimeout(function(){
                        vm.$refs["selectRef"].$el.querySelector("input").blur();
                    },100);                
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
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( customer_action ),{signal: vm.affiliateUsersAbortController.signal} )
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
                            action:"affiliatepress_commission_bulk_action",
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
                            vm.loadcommissions(true);                  
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
                    }else if(this.multipleSelection.length > 0 && (this.bulk_action == "approve" || this.bulk_action == "reject" || this.bulk_action == "pending")){
                        var bulk_action_postdata = {
                            action:"affiliatepress_commission_bulk_status_change",
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
                            vm.is_multiple_checked = false;
                            vm.multipleSelection = []; 
                            vm.multipleSelectionVal = "";                                                   
                            vm.is_display_loader = "0";
                            vm.loadcommissions(true);   
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
            deleteCommission(ap_commission_id,index){
                const vm = this;
                var postData = { action:"affiliatepress_delete_commission", ap_commission_id: ap_commission_id, _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    if(response.data.variant == "success"){
                        vm.items.splice(index, 1);
                        vm.loadcommissions();
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
            affiliatepress_change_status(update_id, index, new_status, old_status){
                const vm = this;
                vm.items[index].change_status_loader = 1;
                var postData = { action:"affiliatepress_change_commissions_status", update_id: update_id, new_status: new_status, old_status: old_status, _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    if(response.data == "0" || response.data == 0){
                        vm.items[index].change_status_loader = 0;
                        vm.loadcommissions(false);
                        return false;
                    }else{
                        vm.items[index].change_status_loader = 0;
                        vm.$notify({
                            title: "'.esc_html__('Success', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Commission status changed successfully', 'affiliatepress-affiliate-marketing').'",
                            type: "success",
                            customClass: "success_notification",
                            duration:'.intval($affiliatepress_notification_duration).',  
                        });
                        vm.loadcommissions(false);
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
            handleSizeChange(val) {
                this.perPage = val;
                this.loadcommissions();
            },
            handleCurrentChange(val) {
                this.currentPage = val;
                this.loadcommissions();
            },            
            resetFilter(){
                const vm = this;                
                const formValues = Object.values(this.commissions_search);
                const hasValue = formValues.some(value => {
                    if (typeof value === "string") {
                        return value.trim() !== "";
                    }
                    if (Array.isArray(value)) {
                        return value.length > 0;
                    }
                    return false;
                });
                vm.commissions_search.ap_commission_search_date = [];
                vm.commissions_search.ap_affiliates_user = "";
                vm.commissions_search.commission_status = "";                
                if (hasValue) {
                    vm.currentPage = 1;
                    vm.loadcommissions();
                }                
                vm.is_multiple_checked = false;
                vm.multipleSelection = [];
            },     
            applyFilter(){
                const vm = this;
                vm.currentPage = 1;
                vm.loadcommissions();
            }, 
            async loadcommissions(flag=true) {
                const vm = this;
                if(flag){
                    vm.is_display_loader = "1";
                }            
                vm.is_apply_disabled = true;       
                vm.enabled = true;
                affiliatespress_search_data = vm.commissions_search;
                var postData = { action:"affiliatepress_get_commissions", perpage:this.perPage, order_by:this.order_by, order:this.order, currentpage:this.currentPage, search_data: affiliatespress_search_data,_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function(response){ 
                    vm.ap_first_page_loaded = "0";    
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
                    vm.is_display_loader = "0";
                }.bind(this) )
                .catch( function (error) {
                    vm.ap_first_page_loaded = "0";
                    vm.is_display_loader = "0"
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
                vm.commission_details_show = false;
                if(form_ref){
                    this.$refs[form_ref].resetFields();
                }                
                vm.commissions = JSON.parse(JSON.stringify(vm.commissions_org));
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
                vm.commissions = JSON.parse(JSON.stringify(vm.commissions_org));
                vm.commission_details_show = false;
            },
            closedetailsModal(form_ref){
                vm = this;
                vm.open_modal = false;
                vm.commission_details_show = false;
            },
            closeBulkAction(){
                this.$refs.multipleTable.clearSelection();
                this.bulk_action = "bulk_action";
            },            
            handleSelectionChange(val) {
                const items_obj = val;
                this.multipleSelection = [];
                var temp_data = [];
                Object.values(items_obj).forEach(val => {
                    temp_data.push({"item_id" : val.ap_commission_id});
                    this.bulk_action = "bulk_action";
                });
                this.multipleSelection = temp_data;
                if(temp_data.length > 0){
                    this.multipleSelectionVal = JSON.stringify(temp_data);
                }else{
                    this.multipleSelectionVal = "";
                }
            },            
            handleSortChange({ column, prop, order }){                
                var vm = this;
                if(prop == "full_name"){
                    vm.order_by = "first_name"; 
                }                
                if(prop == "full_name"){
                    vm.order_by = "first_name"; 
                }else if(prop == "ap_commission_created_date"){
                    vm.order_by = "ap_commission_created_date"; 
                }else if(prop == "ap_commission_source"){
                    vm.order_by = "ap_commission_source";                    
                }else if(prop == "ap_commission_id"){
                    vm.order_by = "ap_commission_id";
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
                this.loadcommissions(true);                 
            },
            affiliatepress_full_row_clickable(row){
                const vm = this
                if (event.target.closest(".ap-table-actions")) {
                    return;
                }
                if (event.target.closest(".ap-flag-icon-data .el-only-child__content.el-tooltip__trigger.el-tooltip__trigger")) {
                    return;
                }
                vm.$refs.multipleTable.toggleRowExpansion(row);
            },           
            isNumberValidate(evt) {
                const vm = this;
                const regex = /^(\d{1,3}(,\d{3})*|\d+)?(\.\d*)?$/;
                if (regex.test(evt)) {
                    vm.inputValue = evt; 
                } else {
                    vm.commissions.ap_commission_amount = vm.inputValue;
                }
            },                  
            isRefNumberValidate(evt) {
                const vm = this;
                const regex = /^(\d{1,3}(,\d{3})*|\d+)?(\.\d*)?$/;
                if (regex.test(evt)) {
                    vm.inputValue = evt; 
                } else {
                    vm.commissions.ap_commission_reference_amount = vm.inputValue;
                }    
            },       
            affiliatepress_get_affiliate_user_details(affiliate_id,user_id){
                const vm = this;
                vm.userPopoverVisible = "true";
                vm.is_get_user_data_loader = "1";
                var postData = [];
                postData.affiliat_id = affiliate_id;
                postData.affiliate_user_id = user_id;
                postData.action = "affiliatepress_get_affiliate_user_details";
                postData._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'"
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    if(response.data.variant == "success" && response.data.affiliate_data != ""){
                        vm.is_get_user_data_loader = "0";
                        vm.affiliate_user_details.affiliate_user_name = response.data.affiliate_data.affiliate_user_name;
                        vm.affiliate_user_details.affiliate_user_email = response.data.affiliate_data.affiliate_user_email;
                        vm.affiliate_user_details.affiliate_user_full_name = response.data.affiliate_data.affiliate_user_full_name;
                        vm.affiliate_user_details.affiliate_user_edit_link = response.data.affiliate_data.affiliate_user_edit_link;
                        vm.show_user_details = "1";
                        '.$affiliatepress_response_add_user_details.'
                    }else if(response.data.variant == "success" && response.data.affiliatepress_wordpress_user_delete != ""){
                        vm.is_get_user_data_loader = "0";
                        vm.affiliatepress_wordpress_user_delete = response.data.affiliatepress_wordpress_user_delete;
                        vm.show_user_details = "0";
                    }
                    else{
                        vm.is_get_user_data_loader = "0";     
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
                    vm.is_get_user_data_loader = "0";
                    vm.is_disabled = 0;                                     
                    vm.$notify({
                            title: "'.esc_html__('Error', 'affiliatepress-affiliate-marketing').'",
                            message: "'.esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing').'",
                            type: "error",
                            customClass: "error_notification",
                            duration:'.intval($affiliatepress_notification_duration).',  
                    });
                });
            },            
            editUserclosePopover(){
                const vm = this;
                vm.userPopoverVisible = false;
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
                vm.loadcommissions();
            },                                                                                               
            ';

            return $affiliatepress_commissions_dynamic_vue_methods;
        
        }
        
        /**
         * Function for dynamic View load
         *
         * @return html
        */
        function affiliatepress_commissions_dynamic_view_load_func(){

            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/commissions/manage_commissions.php';
            $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_commissions_view_file_path', $affiliatepress_load_file_name);
            include $affiliatepress_load_file_name;

        }

        
        /**
         * Function for affiliates default Vue Data
         *
         * @return void
        */
        function affiliatepress_commissions_vue_data_fields(){

            global $AffiliatePress,$affiliatepress_commissions_vue_data_fields,$affiliatepress_global_options;            
            $affiliatepress_pagination          = wp_json_encode(array( 10, 20, 50, 100, 200, 300, 400, 500 ));
            $affiliatepress_pagination_arr      = json_decode($affiliatepress_pagination, true);
            $affiliatepress_pagination_selected = $this->affiliatepress_per_page_record;

            $affiliatepress_current_currency_symbol = $AffiliatePress->affiliatepress_get_current_currency_symbol();
            $affiliatepress_dynamic_setting_data_fields['current_currency_symbol'] = $affiliatepress_current_currency_symbol;

            $affiliatepress_all_plugin_integration = $affiliatepress_global_options->affiliatepress_all_plugin_integration();

            $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_all_commissions_type = $affiliatepress_global_options_data['commissions_type'];

            $affiliatepress_pagination_value = (isset($affiliatepress_global_options_data['pagination_val']))?$affiliatepress_global_options_data['pagination_val']:array();

            $affiliatepress_commissions_vue_data_fields = array(
                'affiliatepress_user_loading'  => false,
                'affiliatepress_all_commissions_type'     => $affiliatepress_all_commissions_type,
                'all_plugin_integration'     => $affiliatepress_all_plugin_integration,
                'current_currency_symbol'    => $affiliatepress_current_currency_symbol,
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
                    array(
                        'value' => 'approve',
                        'label' => esc_html__('Approve', 'affiliatepress-affiliate-marketing'),
                    ), 
                    array(
                        'value' => 'reject',
                        'label' => esc_html__('Reject', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'pending',
                        'label' => esc_html__('Pending', 'affiliatepress-affiliate-marketing'),
                    ),    
                ),
                'loading'                       => false,
                'commissions_search'            => array(
                    "ap_affiliates_user"        => '',
                    "ap_commission_search_date" => array(),
                    "commission_status"         => '',
                ),
                'commissions'                 => array(
                    "ap_commission_id"                  => "",
                    "ap_affiliates_id"                  => "",
                    "affiliate_user_name"               => "", 
                    "ap_commission_type"                => "sale",
                    "ap_commission_status"              => "2",
                    "ap_commission_reference_id"        => "",
                    "ap_commission_product_ids"         => "",
                    "ap_commission_reference_detail"    => "",
                    "ap_commission_reference_amount"    => "",
                    "ap_commission_source"              => "",
                    "ap_commission_amount"              => "",
                    "ap_commission_order_amount"        => "",
                    "ap_commission_currency"            => "",
                    "ap_commission_note"                => "",
                    "ap_commission_created_date"        => "",
                ),
                'commission_details_show'               => false,
                'commission_details'                    => array(),
                'rules'                      => array(
                    'ap_commission_created_date' => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add date', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),                        
                    ),
                    'ap_commission_amount'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add commission amount', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ), 
                    'ap_commission_status'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please select status', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),        
                    /*            
                    'ap_commission_source'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please select affiliate source', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    ),
                    */                                        
                    'ap_affiliates_id'  => array(
                        array(
                            'required' => true,
                            'message'  => esc_html__('Please add affiliates user', 'affiliatepress-affiliate-marketing'),
                            'trigger'  => 'blur',
                        ),
                    )                   
                ),                              
                'order'                      => '',
                'order_by'                   => '',
                'items'                      => array(),
                'multipleSelection'          => array(),
                'multipleSelectionVal'       => '',
                'perPage'                    => $affiliatepress_pagination_selected,
                'totalItems'                 => 0,
                'currentPage'                => 1,
                'pagination_count'           => 1,
                'savebtnloading'             => false,
                'modal_loader'               => 1,
                'is_display_loader'          => '0',
                'is_disabled'                => false,
                'is_apply_disabled'          => false,
                'is_display_save_loader'     => '0',
                'is_display_commisison_details_loader'     => '0',
                'is_multiple_checked'        => false,              
                'pagination_length_val'      => '10',
                'ref_amount_placeholder'     => esc_html__('Enter Order Amount', 'affiliatepress-affiliate-marketing'),
                'amount_placeholder'         => esc_html__('Enter Amount', 'affiliatepress-affiliate-marketing'),
                'pagination_val'             => $affiliatepress_pagination_value,
                'is_get_user_data_loader'     => '0',
                'userPopoverVisible'          => false,
                'affiliate_user_details' => array(
                    'affiliate_user_name'  => '',
                    'affiliate_user_email' => '',
                    'affiliate_user_full_name'=> '',
                    'affiliate_user_edit_link' => '',
                ),
                'affiliatepress_wordpress_user_delete'=>'',
                'show_user_details' => '1',
                'affiliateUsersAbortController' => null,
            );
        }

        function affiliatepress_commission_customer_update(){

            global $affiliatepress_tbl_ap_affiliate_commissions,$AffiliatePress;

            $affiliatepress_commissions_data = $this->affiliatepress_select_record( true, '*', $affiliatepress_tbl_ap_affiliate_commissions, '*', 'WHERE ap_customer_id = %d OR ap_customer_id = %d', array( 0,1 ), '', '', '', false, false,ARRAY_A);

            foreach ($affiliatepress_commissions_data as $affiliatepress_commisison_data) {
                $affiliatepress_source = isset($affiliatepress_commisison_data['ap_commission_source']) ? sanitize_text_field($affiliatepress_commisison_data['ap_commission_source']) : '';
                $affiliatepress_commission_reference_id = isset($affiliatepress_commisison_data['ap_commission_reference_id']) ? sanitize_text_field($affiliatepress_commisison_data['ap_commission_reference_id']) : '';
                $affiliatepress_affiliate_id = isset($affiliatepress_commisison_data['ap_affiliates_id']) ? intval($affiliatepress_commisison_data['ap_affiliates_id']) : '';
                $affiliatepress_commission_id = isset($affiliatepress_commisison_data['ap_commission_id']) ? intval($affiliatepress_commisison_data['ap_commission_id']) : '';

                $affiliatepress_customer_details = $this->affiliatepress_customer_details_by_order_id($affiliatepress_commission_reference_id,$affiliatepress_source,$affiliatepress_affiliate_id);

                if(!empty($affiliatepress_customer_details)){
                    $affiliatepress_customer_id = $AffiliatePress->affiliatepress_add_commission_customer( $affiliatepress_customer_details );

                    if(!empty($affiliatepress_customer_id) || $affiliatepress_customer_id > 0){
                        $this->affiliatepress_update_record($affiliatepress_tbl_ap_affiliate_commissions, array('ap_customer_id'=>$affiliatepress_customer_id), array( 'ap_commission_id' => $affiliatepress_commission_id ));
                    }
                }
            }
        }

        function affiliatepress_customer_details_by_order_id($affiliatepress_commission_reference_id,$affiliatepress_source,$affiliatepress_affiliate_id){
            global $wpdb;
            $affiliatepress_customer_args = array();

            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            if (!empty($affiliatepress_source) && $affiliatepress_source === "easy_digital_downloads") {

                if (is_plugin_active('easy-digital-downloads/easy-digital-downloads.php') || is_plugin_active('easy-digital-downloads-pro/easy-digital-downloads.php')) {
                    if ( ! function_exists('edd_get_order') || ! function_exists('edd_get_customer') ) {
                        return $affiliatepress_customer_args;
                    }
            
                    $affiliatepress_order = edd_get_order($affiliatepress_commission_reference_id);
            
                    if ( empty($affiliatepress_order) || empty($affiliatepress_order->customer_id) ) {
                        return $affiliatepress_customer_args;
                    }
            
                    $affiliatepress_customer = edd_get_customer($affiliatepress_order->customer_id);
            
                    if ( empty($affiliatepress_customer) ) {
                        return $affiliatepress_customer_args;
                    }
            
                    $affiliatepress_user_id  = isset($affiliatepress_customer->user_id) ? intval($affiliatepress_customer->user_id)  : 0;
                    $affiliatepress_email_id = isset($affiliatepress_customer->email) ? sanitize_email($affiliatepress_customer->email): '';
                    $affiliatepress_first_name = '';
                    $affiliatepress_last_name  = '';
            
                    if ( !empty($affiliatepress_customer->name) ) {
                        $affiliatepress_name = explode(' ', trim($affiliatepress_customer->name), 2);
                        $affiliatepress_first_name = isset($affiliatepress_name[0])? $affiliatepress_name[0]: '';
                        $affiliatepress_last_name = isset($affiliatepress_name[1])? $affiliatepress_name[1]: '';
                    }
            
                    $affiliatepress_customer_args = array(
                        'email'        => $affiliatepress_email_id,
                        'user_id'      => $affiliatepress_user_id,
                        'first_name'   => $affiliatepress_first_name,
                        'last_name'    => $affiliatepress_last_name,
                        'affiliate_id' => $affiliatepress_affiliate_id,
                    );
                }
            }
            elseif (!empty($affiliatepress_source) && $affiliatepress_source === "woocommerce") {
                if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                    if ( ! function_exists('wc_get_order') ) {
                        return $affiliatepress_customer_args;
                    }
                    $affiliatepress_order = wc_get_order($affiliatepress_commission_reference_id);
                    if ( empty($affiliatepress_order) ) {
                        return $affiliatepress_customer_args;
                    }
    
                    $affiliatepress_user_id = intval($affiliatepress_order->get_user_id());
                    $affiliatepress_email_id  = sanitize_email($affiliatepress_order->get_billing_email());
                    $affiliatepress_first_name = sanitize_text_field($affiliatepress_order->get_billing_first_name());
                    $affiliatepress_last_name  = sanitize_text_field($affiliatepress_order->get_billing_last_name());
    
                    if ( empty($affiliatepress_first_name) && empty($affiliatepress_last_name) ) {
                        $affiliatepress_full_name = $affiliatepress_order->get_formatted_billing_full_name();
                
                        if ( !empty($affiliatepress_full_name) ) {
                            $affiliatepress_name = explode(' ', trim($affiliatepress_full_name), 2);
                            $affiliatepress_first_name = isset($affiliatepress_name[0]) ? $affiliatepress_name[0] : '';
                            $affiliatepress_last_name  = isset($affiliatepress_name[1]) ? $affiliatepress_name[1] : '';
                        }
                    }
                    
                    $affiliatepress_customer_args = array(
                        'email'        => $affiliatepress_email_id,
                        'user_id'      => $affiliatepress_user_id,
                        'first_name'   => $affiliatepress_first_name,
                        'last_name'    => $affiliatepress_last_name,
                        'affiliate_id' => $affiliatepress_affiliate_id,
                    );
                }
            }
            elseif (!empty($affiliatepress_source) && $affiliatepress_source === "armember") {

                if(is_plugin_active('armember-membership/armember-membership.php')){
                    $affiliatepress_log_id = $affiliatepress_commission_reference_id;

                    $affiliatepress_tbl_arm_payment_log = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'arm_payment_log' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'arm_payment_log' contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function
    
                    $affiliatepress_armaff_entry = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_arm_payment_log, 'arm_user_id', 'WHERE arm_log_id  = %d', array( $affiliatepress_log_id ), '', '', '', false, true,ARRAY_A);
    
                    $affiliatepress_user_id = isset($affiliatepress_armaff_entry['arm_user_id']) ? intval($affiliatepress_armaff_entry['arm_user_id']) : 0;
    
                    $affiliatepress_user_info = get_userdata($affiliatepress_user_id);
    
                    if(!empty($affiliatepress_user_info)){
    
                        $affiliatepress_first_name = isset( $affiliatepress_user_info->first_name) ? sanitize_text_field( $affiliatepress_user_info->first_name) : '';
                        $affiliatepress_last_name  = isset( $affiliatepress_user_info->last_name) ? sanitize_text_field( $affiliatepress_user_info->last_name) : '';
                        $affiliatepress_user_email = isset($affiliatepress_user_info->user_email) ? sanitize_email($affiliatepress_user_info->user_email) : '';
                        
                        $affiliatepress_customer_args = array(
                            'email'        => $affiliatepress_user_email,
                            'user_id'      => $affiliatepress_user_id,
                            'first_name'   => $affiliatepress_first_name,
                            'last_name'    => $affiliatepress_last_name,
                            'affiliate_id' => $affiliatepress_affiliate_id,
                        );
                    }
                }
            }
            elseif (!empty($affiliatepress_source) && $affiliatepress_source === "bookingpress") {

                if ( is_plugin_active( 'bookingpress-appointment-booking-pro/bookingpress-appointment-booking-pro.php' )) {
                    $affiliatepress_payment_id = $affiliatepress_commission_reference_id;

                    $affiliatepress_tbl_bookingpress_payment_transactions = $this->affiliatepress_tablename_prepare( $wpdb->prefix . 'bookingpress_payment_transactions' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $wpdb->prefix . 'bookingpress_appointment_bookings' contains table name and it's prepare properly using 'arm_payment_log' function

                    $affiliatepress_bookingpress_customer_details = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_bookingpress_payment_transactions, '*', 'WHERE bookingpress_payment_log_id = %d', array($affiliatepress_payment_id), '', '', '', false, true,ARRAY_A);

                    if(!empty($affiliatepress_bookingpress_customer_details)){
    
                        $affiliatepress_first_name = isset($affiliatepress_bookingpress_customer_details['bookingpress_customer_firstname']) ? sanitize_text_field($affiliatepress_bookingpress_customer_details['bookingpress_customer_firstname']) : '';
                        $affiliatepress_last_name  = isset($affiliatepress_bookingpress_customer_details['bookingpress_customer_lastname']) ? sanitize_text_field($affiliatepress_bookingpress_customer_details['bookingpress_customer_lastname']) : '';
                        $affiliatepress_user_email = isset($affiliatepress_bookingpress_customer_details['bookingpress_customer_email']) ? sanitize_email($affiliatepress_bookingpress_customer_details['bookingpress_customer_email']) : '';
                        
                        $affiliatepress_customer_args = array(
                            'email'        => $affiliatepress_user_email,
                            'user_id'      => '',
                            'first_name'   => $affiliatepress_first_name,
                            'last_name'    => $affiliatepress_last_name,
                            'affiliate_id' => $affiliatepress_affiliate_id,
                        );
                    }
                }
            }

            return $affiliatepress_customer_args;
        }
    }
}

global $affiliatepress_commissions;
$affiliatepress_commissions = new affiliatepress_commissions();