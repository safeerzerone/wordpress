<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_notifications') ) {
    class affiliatepress_notifications Extends AffiliatePress_Core{
        
        var $affiliatepress_per_page_record;

        function __construct(){
            
            $this->affiliatepress_per_page_record = 10;

            /**Function for notification vue data */
            add_action( 'admin_init', array( $this, 'affiliatepress_notifications_vue_data_fields') );

            /* Dynamic Constant */
            add_filter('affiliatepress_notifications_dynamic_constant_define',array($this,'affiliatepress_notifications_dynamic_constant_define_func'),10,1);
            
            /* Dynamic Vue Fields */
            add_filter('affiliatepress_notifications_dynamic_data_fields',array($this,'affiliatepress_notifications_dynamic_data_fields_func'),10,1);

            /* Vue Load */
            add_action('affiliatepress_notifications_dynamic_view_load', array( $this, 'affiliatepress_notifications_dynamic_view_load_func' ), 10);

            /* Vue Method */
            add_filter('affiliatepress_notifications_dynamic_vue_methods',array($this,'affiliatepress_notifications_dynamic_vue_methods_func'),10,1);

            /* Dynamic On Load Method */
            add_filter('affiliatepress_notifications_dynamic_on_load_methods', array( $this, 'affiliatepress_notifications_dynamic_on_load_methods_func' ), 10, 1);

            /* Get Notifications */
            add_action('wp_ajax_affiliatepress_get_email_notification_data', array( $this, 'affiliatepress_get_email_notification_data' ), 10);

            add_action('wp_ajax_affiliatepress_email_notification_status', array( $this, 'affiliatepress_get_all_email_notification_status' ), 10);

            /* Save Email Notifications */
            add_action('wp_ajax_affiliatepress_save_email_notification_data', array( $this, 'affiliatepress_save_email_notification_data' ), 10);

        }
                
        /**
         * Function for save email notification
         *
         * @return void
        */
        function affiliatepress_save_email_notification_data(){
            
            global $wpdb, $affiliatepress_tbl_ap_notifications, $AffiliatePress,$affiliatepress_global_options;
                        

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'save_email_notification', true, 'ap_wp_nonce' );
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            $response['return_data']            = array();

            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_notifications')){
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

            $affiliatepress_return_data['variant']                = 'error';
            $affiliatepress_return_data['title']                  = esc_html__('Error', 'affiliatepress-affiliate-marketing');
            $affiliatepress_return_data['msg']                    = esc_html__('Something went wrong..', 'affiliatepress-affiliate-marketing');
            $affiliatepress_return_data['return_data']            = array();
            $affiliatepress_return_data['is_custom_notification'] = 0;

            if(!empty($_POST)){// phpcs:ignore

                $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
                $affiliatepress_allow_tag = json_decode($affiliatepress_global_options_data['allowed_html'], true);

                $affiliatepress_ap_notification_receiver_type = ! empty($_POST['ap_notification_receiver_type']) ? sanitize_text_field($_POST['ap_notification_receiver_type']) : '';// phpcs:ignore
                $affiliatepress_ap_notification_slug     = ! empty($_POST['ap_notification_slug']) ? sanitize_text_field($_POST['ap_notification_slug']) : '';// phpcs:ignore
                $affiliatepress_ap_notification_subject  = ! empty($_POST['ap_notification_subject']) ? wp_kses($_POST['ap_notification_subject'],$affiliatepress_allow_tag) : '';// phpcs:ignore
                $affiliatepress_ap_notification_message  = ! empty($_POST['ap_notification_message']) ? wp_kses($_POST['ap_notification_message'], $affiliatepress_allow_tag) : '';// phpcs:ignore
                
                $affiliatepress_ap_notification_message  = htmlspecialchars_decode(stripslashes_deep($affiliatepress_ap_notification_message));

                $affiliatepress_email_notification_status = ! empty($_REQUEST['ap_notification_status'][ $affiliatepress_ap_notification_receiver_type ][ $affiliatepress_ap_notification_slug ]) ? sanitize_text_field(wp_unslash($_REQUEST['ap_notification_status'][ $affiliatepress_ap_notification_receiver_type ][ $affiliatepress_ap_notification_slug ])) : '';

                $affiliatepress_notification_status = ($affiliatepress_email_notification_status == "true") ? 1 : 0;
                
                $affiliatepress_database_modify_data = array(
                    'ap_notification_receiver_type'           => $affiliatepress_ap_notification_receiver_type,                    
                    'ap_notification_status'                  => $affiliatepress_notification_status,
                    'ap_notification_subject'                 => $affiliatepress_ap_notification_subject,
                    'ap_notification_message'                 => $affiliatepress_ap_notification_message,
                    'ap_notification_updated_at'              => current_time('mysql'),
                );

                $affiliatepress_database_modify_data = apply_filters('affiliatepress_save_email_notification_data_filter', $affiliatepress_database_modify_data);// phpcs:ignore
                $affiliatepress_if_notification_exists = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_notifications, 'COUNT(ap_notification_id)', ' WHERE ap_notification_slug = %s AND ap_notification_receiver_type = %s', array( $affiliatepress_ap_notification_slug, $affiliatepress_ap_notification_receiver_type), '', '', '', true, false,ARRAY_A));

                if($affiliatepress_if_notification_exists > 0){
                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_notifications, $affiliatepress_database_modify_data, array( 'ap_notification_receiver_type' => $affiliatepress_ap_notification_receiver_type, 'ap_notification_slug' => $affiliatepress_ap_notification_slug));
                }

                $affiliatepress_return_data['variant']                = 'success';
                $affiliatepress_return_data['title']                  = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $affiliatepress_return_data['msg']                    = esc_html__('Email notifications details updated successfully.', 'affiliatepress-affiliate-marketing');
                $affiliatepress_return_data['return_data']            = array();
                $affiliatepress_return_data['is_custom_notification'] = 0;
    
                do_action('affiliatepress_after_save_email_notification_data');// phpcs:ignore
                
            }

            echo wp_json_encode($affiliatepress_return_data);
            exit();

        }

        /**
         * Service module on load methods
         *
         * @return void
         */
        function affiliatepress_notifications_dynamic_on_load_methods_func($affiliatepress_notifications_dynamic_on_load_methods){
            $affiliatepress_notifications_dynamic_on_load_methods.='
                this.affiliatepress_select_email_notification("affiliate_account_pending", "affiliate");  
                this.affiliatepress_get_all_email_notification_status()          
            ';
            return $affiliatepress_notifications_dynamic_on_load_methods;
        }     
        
        function affiliatepress_get_all_email_notification_status(){
            global $wpdb, $affiliatepress_tbl_ap_notifications, $AffiliatePress,$affiliatepress_global_options;

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_email_notification', true, 'ap_wp_nonce' );
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            $response['return_data']            = array();

            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_notifications')){
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
            
            $affiliatepress_email_notification_status['admin'] = array(
                'affiliate_account_pending' => true,
                'affiliate_account_approved'  => true,
                'affiliate_account_rejected' => true,
                'commission_registered' => true,
                'commission_approved' => true,
                'affiliate_payment_paid' => true,
                'affiliate_payment_failed'=> true,
            );
            $affiliatepress_email_notification_status['affiliate'] = array(
                'affiliate_account_pending' => true,
                'affiliate_account_approved'  => true,
                'affiliate_account_rejected' => true,
                'commission_registered' => true,
                'commission_approved' => true,
                'affiliate_payment_paid' => true,
                'affiliate_payment_failed'=> true,
            );

            $affiliatepress_default_notification_data = $this->affiliatepress_get_default_notifications();

            foreach ( $affiliatepress_default_notification_data as $affiliatepress_default_notification_key => $affiliatepress_default_notification_val ) {
                $affiliatepress_notification_value         = ( $affiliatepress_default_notification_val['ap_notification_status'] == 1 ) ? true : false;
                $affiliatepress_notification_receiver_type = $affiliatepress_default_notification_val['ap_notification_receiver_type'];

                switch ( $affiliatepress_default_notification_val['ap_notification_slug'] ) {
                    case 'affiliate_account_pending':
                        $affiliatepress_email_notification_status[ $affiliatepress_notification_receiver_type ]['affiliate_account_pending'] = $affiliatepress_notification_value;
                        break;
                    case 'affiliate_account_approved':
                        $affiliatepress_email_notification_status[ $affiliatepress_notification_receiver_type ]['affiliate_account_approved'] = $affiliatepress_notification_value;
                        break;
                    case 'affiliate_account_rejected':
                        $affiliatepress_email_notification_status[ $affiliatepress_notification_receiver_type ]['affiliate_account_rejected'] = $affiliatepress_notification_value;
                        break;
                    case 'commission_registered':
                        $affiliatepress_email_notification_status[ $affiliatepress_notification_receiver_type ]['commission_registered'] = $affiliatepress_notification_value;
                        break;
                    case 'commission_approved':
                        $affiliatepress_email_notification_status[ $affiliatepress_notification_receiver_type ]['commission_approved'] = $affiliatepress_notification_value;
                        break;
                    case 'affiliate_payment_paid':
                        $affiliatepress_email_notification_status[ $affiliatepress_notification_receiver_type ]['affiliate_payment_paid'] = $affiliatepress_notification_value;
                        break;
                    case 'affiliate_payment_failed':
                        $affiliatepress_email_notification_status[ $affiliatepress_notification_receiver_type ]['affiliate_payment_failed'] = $affiliatepress_notification_value;
                        break;
                }
            }

            $affiliatepress_email_notification_status = apply_filters('affiliatepress_add_affiliatepress_email_notification_status', $affiliatepress_email_notification_status, $affiliatepress_default_notification_data);
            echo wp_json_encode($affiliatepress_email_notification_status);
            exit();
        }

        /**
     * Get all default notifications
     *
     * @return void
     */
    function affiliatepress_get_default_notifications()
    {
        global $wpdb, $affiliatepress_tbl_ap_notifications;
        $affiliatepress_default_notifications_data = $wpdb->get_results("SELECT * FROM {$affiliatepress_tbl_ap_notifications} WHERE ap_notification_is_custom = 0", ARRAY_A);// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_notifications is table name already prepare in "affiliatepress_tablename_prepare". False Positive alarm
        return $affiliatepress_default_notifications_data;
    }
      
        
        /**
         * Function for get notification data
         *
         * @return void
        */
        function affiliatepress_get_email_notification_data(){
            
            global $wpdb, $affiliatepress_tbl_ap_notifications, $AffiliatePress;
            
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_email_notification', true, 'ap_wp_nonce' );
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something Wrong', 'affiliatepress-affiliate-marketing');
            $response['return_data']            = array();
            
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }
            
            if(!current_user_can('affiliatepress_notifications')){
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

            $affiliatepress_notification_slug = (isset($_REQUEST['ap_notification_slug']) && !empty($_REQUEST['ap_notification_slug'])) ? sanitize_text_field($_REQUEST['ap_notification_slug']) : '';// phpcs:ignore
            $affiliatepress_notification_receiver_type = (isset($_REQUEST['ap_notification_receiver_type']) && !empty($_REQUEST['ap_notification_receiver_type'])) ? sanitize_text_field($_REQUEST['ap_notification_receiver_type']) : '';// phpcs:ignore
            
            if(!empty($affiliatepress_notification_slug) && !empty($affiliatepress_notification_receiver_type)){

                $affiliatepress_tbl_ap_notifications_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_notifications);

                $affiliatepress_record_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$affiliatepress_tbl_ap_notifications_temp} WHERE ap_notification_slug = %s AND ap_notification_receiver_type = %s", $affiliatepress_notification_slug, $affiliatepress_notification_receiver_type), ARRAY_A); //phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason $affiliatepress_tbl_ap_notifications is a table name               
                if(!empty($affiliatepress_record_data)){

                    $affiliatepress_record_data['ap_notification_subject'] = stripslashes_deep($affiliatepress_record_data['ap_notification_subject']);
                    $affiliatepress_record_data['ap_notification_message'] = stripslashes_deep($affiliatepress_record_data['ap_notification_message']);                    
                    $response['variant']                 = 'success';
                    $response['return_data']             = $affiliatepress_record_data;

                }
            }

            echo wp_json_encode($response);
            exit();
        }

        
        /**
         * Function for dynamic const add in vue
         *
         * @return void
         */
        function affiliatepress_notifications_dynamic_constant_define_func($affiliatepress_notifications_dynamic_constant_define){
            return $affiliatepress_notifications_dynamic_constant_define;
        }

        /**
         * Function for notification vue data
         *
         * @return void
        */
        function affiliatepress_notifications_dynamic_data_fields_func($affiliatepress_notifications_vue_data_fields){            
            
            global $AffiliatePress,$affiliatepress_notifications_vue_data_fields;
                        
            $affiliatepress_notifications_vue_data_fields['all_status'] = array();
            $affiliatepress_notifications_vue_data_fields['affiliates']['affiliate_user_name'] = '';

            $affiliatepress_notifications_vue_data_fields = apply_filters('affiliatepress_backend_modify_notifications_data_fields', $affiliatepress_notifications_vue_data_fields);
            
            $affiliatepress_notifications_vue_data_fields =  wp_json_encode($affiliatepress_notifications_vue_data_fields);

            return $affiliatepress_notifications_vue_data_fields;

        }

        function affiliatepress_notifications_dynamic_vue_methods_func($affiliatepress_notifications_dynamic_vue_methods){
            global $affiliatepress_notification_duration;            
            $affiliatepress_account_pending_label = esc_html__('Account Pending', 'affiliatepress-affiliate-marketing');

            $affiliatepress_notifications_dynamic_vue_methods.='
            
            affiliatepress_insert_placeholder(selected_tag){
                const vm = this;
                var affiliatepress_textarea_element = document.getElementById("affiliatepress_email_notification_subject_message");
                var affiliatepress_current_val = document.getElementById("affiliatepress_email_notification_subject_message").value;
                var affiliatepress_start_pos = affiliatepress_textarea_element.selectionStart;
                var affiliatepress_end_pos = affiliatepress_textarea_element.selectionEnd;

                var affiliatepress_before_string = affiliatepress_current_val.substring(0, affiliatepress_start_pos);
                var affiliatepress_after_string = affiliatepress_current_val.substring(affiliatepress_end_pos, affiliatepress_current_val.length);

                var affiliatepress_new_appended_string = affiliatepress_before_string + selected_tag + affiliatepress_after_string;
                document.getElementById("affiliatepress_email_notification_subject_message").value = affiliatepress_new_appended_string;
            },
            affiliatepress_select_email_notification(email_notification_key, ap_notification_receiver_type ="", is_custom_notification = 0){                
                const vm = this;
                let email_notification_label = "'.esc_html($affiliatepress_account_pending_label).'";
                let notification_label = document.querySelector( `.ap-en-left_item-body--list__item[data-key="${email_notification_key}"]` );
                if( null != notification_label ){
                    email_notification_label = notification_label.getAttribute( "data-label" );
                }
                vm.affiliatepress_selected_default_notification_db_name = email_notification_key;
                vm.affiliatepress_active_email_notification = email_notification_key;            
                vm.affiliatepress_email_notification_edit_text = email_notification_label;
                vm.affiliatepress_get_notification_data(email_notification_key, ap_notification_receiver_type);
            }, 
            affiliatepress_change_tab(eventData){
                const vm = this;
                vm.activeTabName = eventData.props.name;
                vm.affiliatepress_get_notification_data(vm.affiliatepress_active_email_notification, eventData.props.name);
            },  
            affiliatepress_save_email_notification_data(){

                const vm = this;
                vm.is_disabled = true;
                vm.is_display_save_loader = "1";
                tinyMCE.triggerSave();
                const formData = new FormData(vm.$refs.email_notification_form.$el);
                const data = {};
                for (let [key, val] of formData.entries()) {
                    Object.assign(data, { [key]: val })
                }
                var affiliatepress_email_notification_msg_data = data.affiliatepress_email_notification_subject_message;
                let affiliatepress_save_notification_data = [];
                affiliatepress_save_notification_data.ap_notification_receiver_type = vm.activeTabName;
                affiliatepress_save_notification_data.email_notification_key = vm.affiliatepress_active_email_notification;

                affiliatepress_save_notification_data.ap_notification_subject = vm.affiliatepress_email_notification_subject;
                affiliatepress_save_notification_data.ap_notification_message = affiliatepress_email_notification_msg_data;
                affiliatepress_save_notification_data.ap_notification_status  = vm.affiliatepress_email_notification_status;
                affiliatepress_save_notification_data.ap_notification_slug    = vm.affiliatepress_active_email_notification;
                affiliatepress_save_notification_data.action = "affiliatepress_save_email_notification_data";
                affiliatepress_save_notification_data._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";
                affiliatepress_save_notification_data.additional_data = [];
 
                axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(affiliatepress_save_notification_data)).then(function(response){
                    vm.is_disabled = false;                    
                    vm.is_display_save_loader = "0";
                    vm.$notify({
                        title: response.data.title,
                        message: response.data.msg,
                        type: response.data.variant,
                        customClass: response.data.variant+"_notification",
                        duration:'.intval($affiliatepress_notification_duration).',
                    });
                }.bind(this)).catch(function(error){                    
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
            affiliatepress_get_notification_data(email_notification_key, ap_notification_receiver_type){
                const vm = this;
                if(ap_notification_receiver_type == ""){
                    ap_notification_receiver_type = vm.activeTabName;
                }  
                vm.ap_notifications_content_loaded = "1";
                var affiliatepress_get_notification_post_data = [];
                affiliatepress_get_notification_post_data.ap_notification_slug = email_notification_key;
                affiliatepress_get_notification_post_data.ap_notification_receiver_type = ap_notification_receiver_type;
                affiliatepress_get_notification_post_data.action = "affiliatepress_get_email_notification_data";
                affiliatepress_get_notification_post_data._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'";
                
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( affiliatepress_get_notification_post_data )).then( function (response) {
                    const affiliatepress_return_notification_data = response.data.return_data;                        
                    if(response.data.variant == "success" && affiliatepress_return_notification_data.length != 0){
                        var oldvalue = vm.ap_first_page_loaded;
                        vm.ap_first_page_loaded = "0";  
                        vm.affiliatepress_email_notification_subject = affiliatepress_return_notification_data.ap_notification_subject;
                        var affiliatepress_email_notification_msg = affiliatepress_return_notification_data.ap_notification_message;  
                        vm.ap_notifications_content_loaded = "0";                         
                        vm.$nextTick(() => {
                            document.getElementById("affiliatepress_email_notification_subject_message").value = affiliatepress_email_notification_msg;
                            if( null != tinyMCE.activeEditor ){
                                tinyMCE.activeEditor.setContent(affiliatepress_email_notification_msg);
                            }
                        });
                    }
                }.bind(this))
                .catch( function (error) {                    
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
            affiliatepress_get_all_email_notification_status(){
                const vm = this;  
                var postData = { action:"affiliatepress_email_notification_status", _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then(function(response){                                               
                    vm.affiliatepress_email_notification_status = response.data
                }.bind(this))
                .catch( function (error) {
                    console.log(error);
                });                      
            },
        ';

            return $affiliatepress_notifications_dynamic_vue_methods;
 
        }
        
        /**
         * Function for dynamic View load
         *
         * @return void
        */
        function affiliatepress_notifications_dynamic_view_load_func(){

            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/notifications/manage_notifications.php';
            $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_notification_view_file_path', $affiliatepress_load_file_name);
            include $affiliatepress_load_file_name;

        }

        
        /**
         * Function for affiliates default Vue Data
         *
         * @return void
        */
        function affiliatepress_notifications_vue_data_fields(){

            global $affiliatepress_notifications_vue_data_fields,$affiliatepress_global_options,$AffiliatePress;            
            $affiliatepress_pagination          = wp_json_encode(array( 10, 20, 50, 100, 200, 300, 400, 500 ));
            $affiliatepress_pagination_arr      = json_decode($affiliatepress_pagination, true);
            $affiliatepress_pagination_selected = $this->affiliatepress_per_page_record;

            $affiliatepress_options             = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_placeholders             = (isset($affiliatepress_options['affiliate_placeholders']))?json_decode($affiliatepress_options['affiliate_placeholders'],true):array();
            $affiliatepress_commission_placeholders            = (isset($affiliatepress_options['commission_placeholders']))?json_decode($affiliatepress_options['commission_placeholders'],true):array();
            $affiliatepress_payment_placeholders               = (isset($affiliatepress_options['payment_placeholders']))?json_decode($affiliatepress_options['payment_placeholders'],true):array();

            $affiliatepress_notifications_vue_data_fields = array(

                'checked'                                         => true,
                'affiliatepress_selected_default_notification'    => 'affiliate_account_pending',
                'affiliatepress_selected_default_notification_db_name' => '',
                'activeTabName'                                   => 'affiliate',
                'affiliatepress_email_notification_edit_text'     => '',
                'affiliatepress_email_notification_subject'       => '',
                'affiliatepress_email_notification_status'        => array(
                    'admin' => array(
                        'affiliate_account_pending' => true,
                        'affiliate_account_approved'  => true,
                        'affiliate_account_rejected' => true,
                        'commission_registered' => true,
                        'commission_approved' => true,
                        'affiliate_payment_paid' => true,
                        'affiliate_payment_failed'=> true,
                    ),
                    'affiliate' => array(
                        'affiliate_account_pending' => true,
                        'affiliate_account_approved'  => true,
                        'affiliate_account_rejected' => true,
                        'commission_registered' => true,
                        'commission_approved' => true,
                        'affiliate_payment_paid' => true,
                        'affiliate_payment_failed'=> true,
                    ),
                ),                       
                'affiliatepress_affiliate_placeholders'           => $affiliatepress_placeholders,                
                'affiliatepress_commission_placeholders'          => $affiliatepress_commission_placeholders,
                'affiliatepress_payment_placeholders'             => $affiliatepress_payment_placeholders,
                'affiliatepress_active_email_notification'        => 'affiliate_account_pending',
                'is_display_loader'                               => '0',
                'is_disabled'                                     => false,
                'is_display_save_loader'                          => '0',
                'is_affiliate_pro_active'                         => $AffiliatePress->affiliatepress_pro_install(), 
                'ap_notifications_content_loaded'                 => '0',
            );

        }
    }
}
global $affiliatepress_notifications;
$affiliatepress_notifications = new affiliatepress_notifications();
