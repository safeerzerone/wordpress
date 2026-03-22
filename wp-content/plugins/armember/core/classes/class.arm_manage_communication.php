<?php

if (!class_exists('ARM_manage_communication')) {

    class ARM_manage_communication {

        function __construct() {
            global $wpdb, $ARMember, $arm_slugs;
            add_action('wp_ajax_arm_message_operation', array($this, 'arm_message_operation'));
            add_action('wp_ajax_arm_delete_single_communication', array($this, 'arm_delete_single_communication'));
            add_action('wp_ajax_arm_delete_bulk_communication', array($this, 'arm_delete_bulk_communication'));
            add_action('arm_user_plan_status_action_failed_payment', array($this, 'arm_user_plan_status_action_mail'), 10, 2);
            add_action('arm_user_plan_status_action_cancel_payment', array($this, 'arm_user_plan_status_action_mail'), 10, 2);
            add_action('arm_user_plan_status_action_eot', array($this, 'arm_user_plan_status_action_mail'), 10, 2);
            add_action('wp_ajax_arm_update_message_communication_status', array($this, 'arm_update_message_communication_status'));
            add_action('wp_ajax_arm_edit_message_data', array($this, 'arm_edit_message_data'));
            add_action('arm_after_recurring_payment_success_outside', array($this, 'arm_recurring_payment_success_email_notification'), 10, 5);
            add_filter('arm_get_modify_admin_mail_template_object_for_send_mail', array($this, 'arm_get_modify_admin_mail_template_object_for_send_mail'),10,6);            
            add_filter('arm_automated_message_email_content_filter', array($this, 'arm_automated_message_email_content_filter'),10,6);            
        }

        function arm_message_operation() {
            global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_capabilities_global,$arm_subscription_plans;
            $op_type = sanitize_text_field($_REQUEST['op_type']);//phpcs:ignore
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce

            //$posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
            $posted_data = $_POST; //phpcs:ignore
            $msg_type = isset($posted_data['arm_message_type']) ? sanitize_text_field($posted_data['arm_message_type']) : '';
            $msg_per_unit = isset($posted_data['arm_message_period_unit']) ? intval($posted_data['arm_message_period_unit']) : 1;
            $msg_per_type = isset($posted_data['arm_message_period_type']) ? sanitize_text_field($posted_data['arm_message_period_type']) : 'day';
            if ($msg_type == 'manual_subscription_reminder' || $msg_type == 'automatic_subscription_reminder') {

                $msg_per_unit = isset($posted_data['arm_message_period_unit_manual_subscription']) ? intval($posted_data['arm_message_period_unit_manual_subscription']) : 1;
                $msg_per_type = isset($posted_data['arm_message_period_type_manual_subscription']) ? sanitize_text_field($posted_data['arm_message_period_type_manual_subscription']) : 'day';
            }
            if ($msg_type == 'before_dripped_content_available') {

                $msg_per_unit = isset($posted_data['arm_message_period_unit_dripped_content']) ? intval($posted_data['arm_message_period_unit_dripped_content']) : 1;
                $msg_per_type = isset($posted_data['arm_message_period_type_dripped_content']) ? sanitize_text_field($posted_data['arm_message_period_type_dripped_content']) : 'day';
            }
            $msg_subsc = isset($posted_data['arm_message_subscription']) ? $posted_data['arm_message_subscription'] : '';
            $msg_subject = isset($posted_data['arm_message_subject']) ? sanitize_text_field($posted_data['arm_message_subject']) : '';
            $msg_status = isset($posted_data['arm_message_status']) ? intval($posted_data['arm_message_status']) : 1;
            $msg_content = isset($posted_data['arm_message_content']) ? $posted_data['arm_message_content'] : '';
            $msg_send_copy_to_admin = (isset($posted_data['arm_email_send_to_admin']) && $posted_data['arm_email_send_to_admin'] == 'on' ) ? 1 : 0;
            $msg_send_diff_copy_to_admin = (isset($posted_data['arm_email_different_content_for_admin']) && $posted_data['arm_email_different_content_for_admin'] == 'on' ) ? 1 : 0;
            $msg_admin_message = isset($posted_data['arm_admin_message_content']) ? $posted_data['arm_admin_message_content'] : '';
           // if ($msg_type != 'before_expire') {
                $where = '';
                if ($op_type == 'edit' && !empty($_REQUEST['edit_id']) && $_REQUEST['edit_id'] != 0) {
                    $where = $wpdb->prepare(" AND `arm_message_id` != %d",intval($_REQUEST['edit_id']));
                }
                $where .= $wpdb->prepare(" AND `arm_message_period_unit` = %d AND `arm_message_period_type` = %s",$msg_per_unit,$msg_per_type);
                $check_res = $wpdb->get_results( $wpdb->prepare("SELECT `arm_message_subscription` FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_type`=%s AND `arm_message_status`=%d " . $where . " ",$msg_type,1) ); //phpcs:ignore --Reason $ARMember->tbl_arm_auto_message is a table name
                $check_status = array(-1);
                if (!empty($msg_subsc)) {
                    
                  
                    foreach ($check_res as $cr) {
                        if ($cr->arm_message_subscription != '') {
                            $check_subs = @explode(',', $cr->arm_message_subscription);
                            foreach ($msg_subsc as $ms) {
                                if (in_array($ms, $check_subs)) {
                                    $check_status[] = 1;
                                } else {
                                    $check_status[] = 0;
                                }
                            }
                        } else {
                            $check_status[] = 1;
                        }
                    }
                } else {
                   
                    if (count($check_res) > 0) {
                        $check_status[] = 1;
                    } else {
                        $check_status[] = 0;
                    }
                }
           // }
       
            if (!empty($msg_subsc)) {
                $msg_subsc = trim(@implode(',', $msg_subsc), ',');
            } else {
                $msg_subsc = '';
            }
            $message_values = array(
                'arm_message_type' => $msg_type,
                'arm_message_period_unit' => $msg_per_unit,
                'arm_message_period_type' => $msg_per_type,
                'arm_message_subscription' => $msg_subsc,
                'arm_message_subject' => $msg_subject,
                'arm_message_content' => $msg_content,
                'arm_message_status' => $msg_status,
                'arm_message_send_copy_to_admin' => $msg_send_copy_to_admin,
                'arm_message_send_diff_msg_to_admin' => $msg_send_diff_copy_to_admin,
                'arm_message_admin_message' => $msg_admin_message
            );

            $message_values = apply_filters('arm_automated_email_template_save_external',$message_values,$_POST);//phpcs:ignore

            $mid = !empty($_REQUEST['edit_id']) ? intval($_REQUEST['edit_id']) : 0;
            if ($op_type == 'add' && empty($mid)) {
                if (!in_array(1, $check_status)) {
                    $email_added = $wpdb->insert($ARMember->tbl_arm_auto_message, $message_values);
                    $mid = $wpdb->insert_id;
                    $message_values['id'] = $mid;
                    if ($email_added) {
                        $message = esc_html__('Message Added Successfully.', 'ARMember');
                        $status = 'success';
                    } else {
                        $message = esc_html__('Error Adding Message, Please Try Again.', 'ARMember');
                        $status = 'failed';
                    }
                } else {
                    $message = esc_html__('Could Not Perform The Operation, Because Message With The Same Type And Subscription Plan Already Exists.', 'ARMember');
                    $status = 'failed';
                }
               
            } else {
                if (!in_array(1, $check_status)) {
                    $where = array('arm_message_id' => $mid);
                    $up_message = $wpdb->update($ARMember->tbl_arm_auto_message, $message_values, $where); 
                    $message = esc_html__('Message Updated Successfully', 'ARMember');
                    $status = 'success';
                    $message_values['id'] = $mid;
                } else {
                    $message = esc_html__('Could Not Perform The Operation, Because Message With The Same Type And Subscription Plan Already Exists.', 'ARMember');
                    $status = 'failed';
                }
            }

            $message_values = apply_filters('arm_automated_email_template_save_before',$message_values,$_POST);//phpcs:ignore
            $msge_type = '';
            switch ($msg_type) {
                case 'on_new_subscription':
                    $msge_type = esc_html__('On New Subscription', 'ARMember');
                    break;
                case 'on_cancel_subscription':
                    $msge_type = esc_html__('On Cancel Membership', 'ARMember');
                    break;
                case 'on_menual_activation':
                    $msge_type = esc_html__('On Manual User Activation', 'ARMember');
                    break;
                case 'on_change_subscription':
                    $msge_type = esc_html__('On Change Subscription', 'ARMember');
                    break;
                case 'on_renew_subscription':
                    $msge_type = esc_html__('On Renew Subscription', 'ARMember');
                    break;
                case 'on_failed':
                    $msge_type = esc_html__('On Failed Payment', 'ARMember');
                    break;
                case 'on_next_payment_failed':
                    $msge_type = esc_html__('On Semi Automatic Subscription Failed Payment', 'ARMember');
                    break;
                case 'trial_finished':
                    $msge_type = esc_html__('Trial Finished', 'ARMember');
                    break;
                case 'on_expire':
                    $msge_type = esc_html__('On Membership Expired', 'ARMember');
                    break;
                case 'before_expire':
                    $msge_type = esc_html__('Before Membership Expired', 'ARMember');
                    break;
                case 'manual_subscription_reminder':
                    $msge_type = esc_html__('Before Semi Automatic Subscription Payment due', 'ARMember');
                    break;
                case 'automatic_subscription_reminder':
                    $msge_type = esc_html__('Before Automatic Subscription Payment due','ARMember');
                    break;
                case 'on_change_subscription_by_admin':
                    $msge_type = esc_html__('On Change Subscription By Admin', 'ARMember');
                    break;
                case 'before_dripped_content_available':
                    $msge_type = esc_html__('Before Dripped Content Available', 'ARMember');
                    break;
                case 'on_recurring_subscription':
                    $msge_type = esc_html__('On Recurring Subscription', 'ARMember');
                    break;
                case 'on_close_account':
                    $msge_type = esc_html__('On Close User Account', 'ARMember');
                    break;
                case 'on_login_account':
                    $msge_type = esc_html__('On User Login', 'ARMember');
                    break;
                case 'on_new_subscription_post':
                    $msge_type = esc_html__('On new paid post purchase', 'ARMember');
                    break;
                case 'on_recurring_subscription_post':
                    $msge_type = esc_html__('On recurring paid post purchase', 'ARMember');
                    break;
                case 'on_renew_subscription_post':
                    $msge_type = esc_html__('On renew paid post purchase', 'ARMember');
                    break;
                case 'on_cancel_subscription_post':
                    $msge_type = esc_html__('On cancel paid post', 'ARMember');
                    break;
                case 'before_expire_post':
                    $msge_type = esc_html__('Before paid post expire', 'ARMember');
                    break;
                case 'on_expire_post':
                    $msge_type = esc_html__('On Expire paid post', 'ARMember');
                    break;
                case 'on_purchase_subscription_bank_transfer':
                    $msge_type = esc_html__('On Purchase membership plan using Bank Transfer', 'ARMember');
                    break;
                default:
                    break;
            }

            $msge_type = apply_filters('arm_filter_edit_email_notification_type', $msge_type, $msg_type);
            
            $msg_sub_lbl = '';
            if(!empty($posted_data['arm_message_subscription']))
            {
                if(!is_array($posted_data['arm_message_subscription'])){

                    $msg_subsc_arr = explode(',',$posted_data['arm_message_subscription']);
                }
                else{
                    $msg_subsc_arr = $posted_data['arm_message_subscription'];
                }
                foreach($msg_subsc_arr as $plan_id){
                    $msg_sub_lbl .= $arm_subscription_plans->arm_get_plan_name_by_id($plan_id);
                    $msg_sub_lbl .= ', ';
                }
            }
            else{
                $msg_sub_lbl = esc_html__('All Membership plans','ARMember');
            }
            $arm_message_response_html = '';
            if(empty($_REQUEST['edit_id'])){
                $arm_message_response_html = '<div class="divTableRow arm_message_tr_'.$mid.' member_row_'.$mid.' arm_email_template_table" onmouseover="arm_datatable_row_hover(\'member_row_'.$mid.'\',\'hovered\')" onmouseleave="arm_datatable_row_hover(\'member_row_'.$mid.'\');" bis_skin_checked="1">';    
            }
            $is_email_checked = '';
            if(!empty($msg_status)){
                $is_email_checked = 'checked="checked"';
            }

            $gridActionConfirmbox = $arm_global_settings->arm_get_confirm_box($mid, esc_html__("Are you sure you want to delete this message?", 'ARMember'), 'arm_communication_delete_btn','',esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));

            $arm_message_response_html .= '
                <div class="divTableCell" bis_skin_checked="1"><div class="armswitch" bis_skin_checked="1">
                    <input type="checkbox" class="armswitch_input arm_communication_status_action" id="arm_communication_status_input_'.$mid.'" value="1" data-item_id="'.$mid.'" '.$is_email_checked.'> 
                    <label class="armswitch_label" for="arm_communication_status_input_'.$mid.'"></label>
                    <span class="arm_status_loader_img arm_right_30"></span>
                </div></div>
                <div class="divTableCell" bis_skin_checked="1">'.$msg_subject.'</div>
                <div class="divTableCell" bis_skin_checked="1">'.$msg_sub_lbl.'</div>
                <div class="divTableCell" bis_skin_checked="1">'.$msge_type.'</div>
                <div class="divTableCell arm_grid_action_wrapper hidden_section" bis_skin_checked="1" style="display: none;">
                    <div class="arm_grid_action_wrapper" bis_skin_checked="1">
                        <div class="arm_grid_action_btn_container" bis_skin_checked="1">
                            <a class="arm_edit_message_btn pro arm_margin_right_5 armhelptip tipso_style" title="'.esc_html__('Edit Message','ARMember').'" href="javascript:void(0);" data-message_id="'.$mid.'"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.2594 3.60022L5.04936 12.2902C4.73936 12.6202 4.43936 13.2702 4.37936 13.7202L4.00936 16.9602C3.87936 18.1302 4.71936 18.9302 5.87936 18.7302L9.09936 18.1802C9.54936 18.1002 10.1794 17.7702 10.4894 17.4302L18.6994 8.74022C20.1194 7.24022 20.7594 5.53022 18.5494 3.44022C16.3494 1.37022 14.6794 2.10022 13.2594 3.60022Z" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.8906 5.0498C12.3206 7.8098 14.5606 9.9198 17.3406 10.1998" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 22H21" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                            <a class="arm_test_mail_btn_pro arm_margin_right_5 armhelptip tipso_style" title="'.esc_html__('Resend Email','ARMember').'" href="javascript:void(0);" data-message_id="'.$mid.'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M7 19C4 19 2 17.5 2 14V7C2 3.5 4 2 7 2H17C20 2 22 3.5 22 7V11" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 6L12.9032 8.7338C12.3712 9.08873 11.6288 9.08873 11.0968 8.7338L7 6" stroke="#617191" stroke-width="1.5" stroke-linecap="round"/><path d="M19.8942 18.0232C20.1376 16.8612 19.9704 15.6089 19.3301 14.4998C17.9494 12.1083 14.8915 11.289 12.5 12.6697C10.1085 14.0504 9.28916 17.1083 10.6699 19.4998C11.8597 21.5606 14.2948 22.454 16.4758 21.7782" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.988 20.1047C19.7581 20.4134 19.2802 20.3574 19.1279 20.0039L17.7572 16.8233C17.6049 16.4699 17.8923 16.084 18.2746 16.1288L21.7144 16.5321C22.0967 16.5769 22.2871 17.0187 22.0571 17.3274L19.988 20.1047Z" fill="#617191"/></svg></a>
                            <a href="javascript:void(0)" onclick="showConfirmBoxCallback('.$mid.');" class="arm_grid_delete_action armhelptip tipso_style" title="'.esc_html__('Delete','ARMember').'"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 5.33333H21M16.5 5.33333L16.1956 4.43119C15.9005 3.55694 15.7529 3.11982 15.4793 2.79664C15.2376 2.51126 14.9274 2.29036 14.5768 2.1542C14.1798 2 13.7134 2 12.7803 2H11.2197C10.2866 2 9.8202 2 9.4232 2.1542C9.07266 2.29036 8.76234 2.51126 8.5207 2.79664C8.24706 3.11982 8.09954 3.55694 7.80447 4.43119L7.5 5.33333M18.75 5.33333V16.6667C18.75 18.5336 18.75 19.4669 18.3821 20.18C18.0586 20.8072 17.5423 21.3171 16.9072 21.6367C16.1852 22 15.2402 22 13.35 22H10.65C8.75982 22 7.81473 22 7.09278 21.6367C6.45773 21.3171 5.94143 20.8072 5.61785 20.18C5.25 19.4669 5.25 18.5336 5.25 16.6667V5.33333M14.25 9.77778V17.5556M9.75 9.77778V17.5556" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                            '.$gridActionConfirmbox.'
                        </div>
                    </div>
                </div>';
            if(empty($_REQUEST['edit_id'])){
                $arm_message_response_html .= '</div>';    
            }

            $response = array('status' => $status, 'message' => $message,'response_html' => $arm_message_response_html);
            
            echo arm_pattern_json_encode($response);
            die();
        }

        function arm_update_message_communication_status($posted_data = array()) {
            global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $response = array('type' => 'error', 'msg' => esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
            if (!empty($_POST['arm_message_id']) && $_POST['arm_message_id'] != 0) {//phpcs:ignore
                $message_id = intval($_POST['arm_message_id']);//phpcs:ignore
                $msg_status = (!empty($_POST['arm_message_status'])) ? intval($_POST['arm_message_status']) : 0;//phpcs:ignore
                $message_values = array('arm_message_status' => $msg_status);
                $update_temp = $wpdb->update($ARMember->tbl_arm_auto_message, $message_values, array('arm_message_id' => $message_id));
                $response = array('type' => 'success', 'msg' => esc_html__('Message Updated Successfully.', 'ARMember'));
            }
            echo arm_pattern_json_encode($response);
            die();
        }

        function arm_delete_single_communication() {
            global $wpdb, $ARMember, $arm_slugs, $arm_subscription_plans, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $action = sanitize_text_field($_POST['act']);//phpcs:ignore
            $id = intval($_POST['id']);//phpcs:ignore
            if ($action == 'delete') {
                if (empty($id)) {
                    $errors[] = esc_html__('Invalid action.', 'ARMember');
                } else {
                    if (!current_user_can('arm_manage_communication')) {
                        $errors[] = esc_html__('Sorry, You do not have permission to perform this action.', 'ARMember');
                    } else {
                        $res_var = $wpdb->delete($ARMember->tbl_arm_auto_message, array('arm_message_id' => $id));
                        if ($res_var) {
                            $message = esc_html__('Message has been deleted successfully.', 'ARMember');
                        }
                    }
                }
            }
            $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
            echo arm_pattern_json_encode($return_array);
            exit;
        }

        function arm_delete_bulk_communication() {
            if (!isset($_POST)) {//phpcs:ignore
                return;
            }
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $bulkaction = $arm_global_settings->get_param('action1');
            if ($bulkaction == -1) {
                $bulkaction = $arm_global_settings->get_param('action2');
            }
            $ids = $arm_global_settings->get_param('item-action', '');
            if (empty($ids)) {
                $errors[] = esc_html__('Please select one or more records.', 'ARMember');
            } else {
                if (!current_user_can('arm_manage_communication')) {
                    $errors[] = esc_html__('Sorry, You do not have permission to perform this action.', 'ARMember');
                } else {
                    if (!is_array($ids)) {
                        $ids = explode(',', $ids);
                    }
                    if (is_array($ids)) {
                        if ($bulkaction == 'delete_communication') {
                            foreach ($ids as $msg_id) {
                                $res_var = $wpdb->delete($ARMember->tbl_arm_auto_message, array('arm_message_id' => $msg_id));
                            }
                            if ($res_var) {
                                $message = esc_html__('Message(s) has been deleted successfully.', 'ARMember');
                            }
                        } else {
                            $errors[] = esc_html__('Please select valid action.', 'ARMember');
                        }
                    }
                }
            }
            $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
            echo arm_pattern_json_encode($return_array);
            exit;
        }

        function arm_user_plan_status_action_mail($args = array(), $plan_obj = array()) {
            global $wpdb, $ARMember, $arm_slugs, $arm_subscription_plans, $arm_global_settings,$arm_pro_ration_feature;
            if (!empty($args['action'])) {
                $now = current_time('timestamp');
                $user_id = $args['user_id'];
                $plan_id = $args['plan_id'];
                $alreadysentmsgs = array();
                
                
                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                $planData = shortcode_atts($defaultPlanData, $userPlanDatameta);
               
                if(!empty($planData)){
                   if(isset($planData['arm_sent_msgs']) && !empty($planData['arm_sent_msgs'])){
                      $alreadysentmsgs = $planData['arm_sent_msgs'];
                   } 
                }
         
                $notification_type = '';
                switch ($args['action']) {
                    case 'on_failed':
                    case 'failed_payment':
                        $notification_type = 'on_failed';
                        break;
                    case 'on_next_payment_failed':
                        $notification_type = 'on_next_payment_failed';
                        break;
                    case 'on_cancel_subscription':
                    case 'on_cancel':
                    case 'cancel_payment':
                    case 'cancel_subscription':
                        $notification_type = 'on_cancel_subscription';
                        break;
                    case 'on_expire':
                    case 'eot':
                        $notification_type = 'on_expire';
                        break;
                    case 'on_new_subscription':
                    case 'new_subscription':
                        $notification_type = 'on_new_subscription';
                        break;
                    case 'on_change_subscription':
                    case 'change_subscription':
                        $notification_type = 'on_change_subscription';
                        break;
                    case 'on_renew_subscription':
                    case 'renew_subscription':
                        $notification_type = 'on_renew_subscription';
                        break;
                    case 'on_success_payment':
                    case 'success_payment':
                        $notification_type = 'on_success_payment';
                        break;
                    case 'on_change_subscription_by_admin':
                        $notification_type = 'on_change_subscription_by_admin';
                        break;
                    case 'before_dripped_content_available':
                        $notification_type = 'before_dripped_content_available';
                        break;
                    case 'on_recurring_subscription':
                        $notification_type = 'on_recurring_subscription';
                        break;
                    case 'on_close_account':
                        $notification_type = 'on_close_account';
                        break;
                    case 'on_login_account':
                        $notification_type = 'on_login_account';
                        break;
                    case 'on_purchase_subscription_bank_transfer':
                        $notification_type = 'on_purchase_subscription_bank_transfer';
                        break;
                    
                    default:
                        break;
                }
                $notification = $this->membership_communication_mail($notification_type, $user_id, $plan_id);
                if ($notification) {
                    $alreadysentmsgs[$now] = $notification_type;
                    if($arm_pro_ration_feature->isProRationFeature)
                    {
                        $planData = get_user_meta($user_id, 'arm_user_plan_' . $plan_id, true);
                    }
                    $planData['arm_sent_msgs'] = $alreadysentmsgs;
                    update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $planData);
                }
                return $notification;
            }
        }

        function membership_communication_mail($message_type = "", $user_id = 0, $user_plan = 0) {
            global $wpdb, $ARMember, $arm_slugs, $arm_subscription_plans, $arm_global_settings, $wp_hasher, $arm_email_settings,$arm_pay_per_post_feature;
            $send_mail = false;
            if (!empty($user_id) && $user_id != 0) {
                $user_plan = (!empty($user_plan)) ? $user_plan : 0;
                $user_info = get_userdata($user_id);
                $user_email = $user_info->user_email;
                $user_login = $user_info->user_login;
                if(isset($user_info->data) && empty($user_email) && empty($user_login)) {
                    $user_email = $user_info->data->user_email;
                    $user_login = $user_info->data->user_login;
                }

                $key = '';
                if ($message_type == 'on_new_subscription' || $message_type == 'on_menual_activation') {

                    

                    if (function_exists('get_password_reset_key')) {
                        remove_all_filters('allow_password_reset');
                        
                        $key = get_password_reset_key($user_info);
                       
                        
                    } else {
                        do_action('retreive_password', $user_login);  /* Misspelled and deprecated */
                        do_action('retrieve_password', $user_login);

                        $allow = apply_filters('allow_password_reset', true, $user_id);

                        if (!$allow) {
                            $key = "";
                        } else if (is_wp_error($allow)) {
                            $key = "";
                        }
                        /* Generate something random for a key... */
                        $key = wp_generate_password(20, false);
                        do_action('retrieve_password_key', $user_login, $key);
                        /* Now insert the new md5 key into the db */
                        if (empty($wp_hasher)) {
                            require_once ABSPATH . WPINC . '/class-phpass.php';
                            $wp_hasher = new PasswordHash(8, true);
                        }
                        $hashed = $wp_hasher->HashPassword($key);
                        $key_saved = $wpdb->update($wpdb->users, array('user_activation_key' => $hashed), array('user_login' => $user_login));
                        if (false === $key_saved) {
                            $key = '';
                        }
                      
                    }
                }

                if (!empty($message_type) && $message_type != 'before_expire') {
                    $is_post_plan = 0;
                    if( $arm_pay_per_post_feature->isPayPerPostFeature ){
                        $planResp = $arm_pay_per_post_feature->arm_get_post_from_plan_id( $user_plan );

                        if( !empty( $planResp[0] ) && !empty( $planResp[0]['arm_subscription_plan_post_id']) ){
                            if( in_array( $message_type , array( 'on_new_subscription', 'on_renew_subscription', 'on_recurring_subscription', 'on_cancel_subscription', 'on_expire' ) ) ){
                                $message_type = $message_type .'_post';
                                $is_post_plan = 1;
                            }
                        }
                    }            
                    
                    $message_type = apply_filters('arm_filter_email_message_type', $message_type, $user_plan, $is_post_plan, $user_id);

                    if(is_array($user_plan)){

                        $additional_data = array("ARM_MESSAGE_RESET_PASSWORD_LINK" => $key);
                        foreach($user_plan as $plan){
                            $messages = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_status`=%d AND `arm_message_type`=%s AND (FIND_IN_SET(%s, `arm_message_subscription`) OR (`arm_message_subscription`=%s))",1,$message_type,$plan,'') ); //phpcs:ignore --Reason $ARMember->tbl_arm_auto_message is a table name
                            $messages = apply_filters('arm_modify_communication_mail_message_external', $messages);
                            if (!empty($messages)) {
                                foreach ($messages as $msg) {
                                    $send_mail = $this->arm_common_send_email($msg,$user_email,$user_id,$plan,'',array(),$additional_data);
                                }
                            }
                        }
                    }else{
                        $messages = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_status`=%d AND `arm_message_type`=%s AND (FIND_IN_SET(" . $user_plan . ", `arm_message_subscription`) OR (`arm_message_subscription`=%s))",1,$message_type,'')); //phpcs:ignore --Reason $ARMember->tbl_arm_auto_message is a table name
                        $messages = apply_filters('arm_modify_communication_mail_message_external', $messages);
                        if (!empty($messages)) {

                            $additional_data = array("ARM_MESSAGE_RESET_PASSWORD_LINK" => $key);

                            foreach ($messages as $msg) {
                                $send_mail = $this->arm_common_send_email($msg,$user_email,$user_id,$user_plan,'',array(),$additional_data);
                            }
                        }
                    }
                    
                }
            }
            return $send_mail;
        }

        function arm_filter_communication_content($content = '', $user_id = 0, $user_plan = 0, $key = '') {
            global $wpdb, $ARMember, $arm_slugs, $arm_subscription_plans, $arm_global_settings, $arm_payment_gateways, $wp_hasher, $arm_email_settings,$arm_subscription_class;
            if (!empty($content) && !empty($user_id)) {
                $date_format = $arm_global_settings->arm_get_wp_date_format();
                $currency = $arm_payment_gateways->arm_get_global_currency();
                $user_plan = (!empty($user_plan) && $user_plan != 0) ? $user_plan : 0;
                $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($user_plan);
       
                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_'.$user_plan, true); 
                $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                $planData = shortcode_atts($defaultPlanData, $userPlanDatameta);

                $arm_plan_detail = $planData['arm_current_plan_detail'];
                $using_gateway = $planData['arm_user_gateway'];
                $arm_plan_description = !empty($arm_plan_detail['arm_subscription_plan_description']) ? $arm_plan_detail['arm_subscription_plan_description'] : '';
                if( isset( $arm_plan_detail['arm_subscription_plan_type'] ) && $arm_plan_detail['arm_subscription_plan_type'] == 'recurring' )
                {
                    $arm_user_plan_info = new ARM_Plan(0);
                    $arm_user_plan_info->init((object) $arm_plan_detail);
                    $arm_user_payment_cycle = isset($arm_plan_detail['arm_user_selected_payment_cycle']) ? $arm_plan_detail['arm_user_selected_payment_cycle'] : '';
                    $arm_user_plan_data = $arm_user_plan_info->prepare_recurring_data($arm_user_payment_cycle);
                    $plan_amount = isset( $arm_user_plan_data['amount'] ) ? $arm_user_plan_data['amount'] : 0;
                } else {
                    $plan_amount = isset( $arm_plan_detail['arm_subscription_plan_amount'] ) ? $arm_plan_detail['arm_subscription_plan_amount'] : 0;
                }

                $u_payable_amount = 0;
                $u_tax_percentage = '-';
                $u_tax_amount = '-';
                $u_transaction_id = '-';
                $u_payment_date = '-';
                $plan_expire = esc_html__('Never Expires', 'ARMember');
                $expire_time = $planData['arm_expire_plan'];
                if (!empty($expire_time)) {
                    $plan_expire = date_i18n($date_format, $expire_time);
                }
                
                $start_plan_date = $planData['arm_start_plan'];
                $data_status = '-';
                if(false !== strpos($content,'{ARM_MESSAGE_SUBSCRIPTION_STATUS}')){
                    $plan_status = $arm_subscription_class->get_return_status_data($user_id,$user_plan,$planData,$start_plan_date);
    
                    if(!empty($plan_status['status']) && $plan_status['status'] == 'suspended'){
                        $data_status = esc_html__('Suspended','ARMember');
                    }else if(!empty($plan_status['status']) &&  $plan_status['status'] == 'canceled'){
                        $data_status = esc_html__('Canceled','ARMember');
                    }else if( !empty($plan_status['status']) && $plan_status['status'] == 'expired'){
                        $data_status = esc_html__('Expired','ARMember');
                    }else if( !empty($plan_status['status']) && $plan_status['status'] == 'active'){
                        $data_status = esc_html__('Active','ARMember');
                    }else{
                        $data_status = esc_html__('Canceled','ARMember');
                    }
                    $data_status = apply_filters('arm_modify_status_lable_at_communication_content_filter', $data_status, $plan_status);
                }
                
                $plan_next_due_date = '-';
                $next_due_date = $planData['arm_next_due_payment'];
                if (!empty($next_due_date)) {
                    $plan_next_due_date = date_i18n($date_format, $next_due_date);
                }

                $user_info = get_userdata($user_id);
                $blog_name = get_bloginfo('name');
                $blog_url = ARM_HOME_URL;
                $u_email = $user_info->user_email;
                $u_displayname = $user_info->display_name;
                $u_userurl = $user_info->user_url;
                $u_username = $user_info->user_login;
                $u_fname = $user_info->first_name;
                $u_lname = $user_info->last_name;
                $u_nicename = $user_info->user_nicename;
                $networ_name = get_site_option('site_name');
                $networ_url = get_site_option('siteurl');

               
                if ($key != '' && !empty($key)) {

                    $change_password_page_id = isset($arm_global_settings->global_settings['change_password_page_id']) ? $arm_global_settings->global_settings['change_password_page_id'] : 0;
                    if ($change_password_page_id == 0) {
                        $arm_reset_password_link = network_site_url("wp-login.php?action=armrp&key=" . rawurlencode($key) . "&login=" . rawurlencode($u_username), 'login');
                    } else {
                        $arm_change_password_page_url = $arm_global_settings->arm_get_permalink('', $change_password_page_id);
                        $arm_change_password_page_url = $arm_global_settings->add_query_arg('action', 'armrp', $arm_change_password_page_url);
                        $arm_change_password_page_url = $arm_global_settings->add_query_arg('key', rawurlencode($key), $arm_change_password_page_url);
                        $arm_change_password_page_url = $arm_global_settings->add_query_arg('login', rawurlencode($u_username), $arm_change_password_page_url);
                        $arm_reset_password_link = $arm_change_password_page_url;

                        $arm_reset_password_link = apply_filters('arm_modify_redirection_page_external', $arm_reset_password_link,$user_id,$change_password_page_id);
                    }
                    $content = str_replace('{ARM_MESSAGE_RESET_PASSWORD_LINK}', $arm_reset_password_link, $content);
                } else {

                    $content = str_replace('{ARM_MESSAGE_RESET_PASSWORD_LINK}', '', $content);
                }


                 $selectColumns = '`arm_log_id`, `arm_user_id`, `arm_transaction_id`, `arm_amount`, `arm_is_trial`, `arm_extra_vars`, `arm_coupon_discount`,`arm_coupon_discount_type`,`arm_payment_date`,`arm_coupon_code`';
                $where_bt=''; 
                if ($using_gateway == 'bank_transfer') {
                    /* Change Log Table For Bank Transfer Method */
                    $armLogTable = $ARMember->tbl_arm_payment_log;
                    $where_bt= $wpdb->prepare(" AND arm_payment_gateway=%s",'bank_transfer');
                } else {
                    $armLogTable = $ARMember->tbl_arm_payment_log;
                    $selectColumns .= ', `arm_token`';

                }

              
                $log_detail = $wpdb->get_row( $wpdb->prepare("SELECT {$selectColumns} FROM `{$armLogTable}` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d {$where_bt} ORDER BY `arm_log_id` DESC",$user_id,$user_plan) ); //phpcs:ignore --Reason $armLogTable is a table name
                $u_plan_discount = 0;
                $u_trial_amount = 0;
                $u_coupon_code = "";
                if (!empty($log_detail)) {
                    $u_transaction_id = $log_detail->arm_transaction_id;
                    $u_payable_amount = $log_detail->arm_amount;

                    $extravars = maybe_unserialize($log_detail->arm_extra_vars);
                    if (!empty($log_detail->arm_coupon_discount) && $log_detail->arm_coupon_discount > 0) {


                        if ($using_gateway == 'bank_transfer') {
                            if(isset($extravars['coupon'])){
                                $u_plan_discount = isset($extravars['coupon']['amount']) ? $extravars['coupon']['amount'] : 0;
                                $u_coupon_code = isset($extravars['coupon']['coupon_code']) ? $extravars['coupon']['coupon_code'] : "";
                            }
                            else{
                                $u_plan_discount = $log_detail->arm_coupon_discount.$log_detail->arm_coupon_discount_type;
                                $u_coupon_code = $log_detail->arm_coupon_code;
                            }
                        }
                        else{
                            $u_plan_discount = isset($extravars['coupon']['amount']) ? $extravars['coupon']['amount'] : 0;   
                            $u_coupon_code = isset($extravars['coupon']['coupon_code']) ? $extravars['coupon']['coupon_code'] : "";
                        }
                    }

                    if(isset($extravars['tax_percentage'])){
                        $u_tax_percentage = ($extravars['tax_percentage'] != '') ? $extravars['tax_percentage'].'%': '-';
                    }

                    if(isset($extravars['tax_amount'])){
                        $u_tax_amount = ($extravars['tax_amount'] != '') ? $arm_payment_gateways->arm_amount_set_separator($currency, $extravars['tax_amount']): '-';
                    }


                    if (!empty($log_detail->arm_is_trial) && $log_detail->arm_is_trial == 1) {
                        $u_trial_amount= isset($extravars['trial']['amount']) ? $extravars['trial']['amount'] : 0;

                    }

                    if (!empty($log_detail->arm_payment_date)) {
                        $date_format = $arm_global_settings->arm_get_wp_date_format();
                        $u_payment_date = date_i18n($date_format, $log_detail->arm_payment_date);
                    }
                }

                
                $all_email_settings = $arm_email_settings->arm_get_all_email_settings();
                $admin_email = (!empty($all_email_settings['arm_email_admin_email'])) ? $all_email_settings['arm_email_admin_email'] : get_option('admin_email');
                

                $activation_key = get_user_meta($user_id, 'arm_user_activation_key', true);
                $login_page_id = isset($arm_global_settings->global_settings['login_page_id']) ? $arm_global_settings->global_settings['login_page_id'] : 0;
                
                $login_url = $arm_global_settings->arm_get_permalink('', $login_page_id);
                $login_url = apply_filters('arm_modify_redirection_page_external', $login_url,$user_id,$login_page_id);

                $u_payment_type = '-';
                $u_payment_gateway = '-';
                $planObj = "";
                if (!empty($arm_plan_detail)) {
                    $plan_detail = maybe_unserialize($arm_plan_detail);
                    if (!empty($plan_detail)) {
                        $planObj = new ARM_Plan(0);
                        $planObj->init((object) $plan_detail);
                    } else {
                        $planObj = new ARM_Plan($user_plan);
                    }
                }
                
                if(!empty($planObj)) {
                    if ($planObj->is_paid()) {
                        if ($planObj->is_lifetime()) {
                            $u_payment_type = esc_html__('Life Time', 'ARMember');
                        } else {
                            if ($planObj->is_recurring()) {
                                $u_payment_type = esc_html__('Subscription', 'ARMember');
                            } else {
                                $u_payment_type = esc_html__('One Time', 'ARMember');
                            }
                        }
                    } else {
                        $u_payment_type = esc_html__('Free', 'ARMember');
                    }
                }

                
                if (!empty($using_gateway)) {
                    $u_payment_gateway = $arm_payment_gateways->arm_gateway_name_by_key($using_gateway);
                }


                $profile_link = $arm_global_settings->arm_get_user_profile_url($user_id);
                $content = str_replace('{ARM_MESSAGE_BLOGNAME}', $blog_name, $content);
                $content = str_replace('{ARM_MESSAGE_BLOGURL}', $blog_url, $content);
                $content = str_replace('{ARM_MESSAGE_NETWORKNAME}', $networ_name, $content);
                $content = str_replace('{ARM_MESSAGE_NETWORKURL}', $networ_url, $content);
                $content = str_replace('{ARM_MESSAGE_USERNAME}', $u_username, $content);
                $content = str_replace('{ARM_MESSAGE_USER_ID}', $user_id, $content);
                $content = str_replace('{ARM_MESSAGE_EMAIL}', $u_email, $content);
                $content = str_replace('{ARM_MESSAGE_USERNICENAME}', $u_nicename, $content);
                $content = str_replace('{ARM_MESSAGE_USERDISPLAYNAME}', $u_displayname, $content);
                $content = str_replace('{ARM_MESSAGE_USERFIRSTNAME}', $u_fname, $content);
                $content = str_replace('{ARM_MESSAGE_USERLASTNAME}', $u_lname, $content);
                $content = str_replace('{ARM_MESSAGE_SUBSCRIPTIONNAME}', $plan_name, $content);
                $content = str_replace('{ARM_MESSAGE_SUBSCRIPTIONDESCRIPTION}', $arm_plan_description, $content);
                $content = str_replace('{ARM_MESSAGE_SUBSCRIPTION_AMOUNT}', $plan_amount, $content);
                $content = str_replace('{ARM_MESSAGE_COUPON_DISCOUNT}', $u_plan_discount, $content);
                $content = str_replace('{ARM_MESSAGE_TRIAL_AMOUNT}', $u_trial_amount, $content);
                $content = str_replace('{ARM_MESSAGE_PAYABLE_AMOUNT}', $u_payable_amount, $content);
                $content = str_replace('{ARM_MESSAGE_CURRENCY}', $currency, $content);
                $content = str_replace('{ARM_MESSAGE_SUBSCRIPTION_EXPIRE}', $plan_expire, $content);
                $content = str_replace('{ARM_MESSAGE_SUBSCRIPTION_NEXT_DUE}', $plan_next_due_date, $content);
                $content = str_replace('{ARM_MESSAGE_SUBSCRIPTION_TAX_PERCENTAGE}', $u_tax_percentage, $content);
                $content = str_replace('{ARM_MESSAGE_SUBSCRIPTION_TAX_AMOUNT}', $u_tax_amount, $content);
                $content = str_replace('{ARM_PROFILE_LINK}', $profile_link, $content);
                $content = str_replace('{ARM_USERMETA_user_url}', $u_userurl, $content);
                $content = str_replace('{ARM_MESSAGE_ADMIN_EMAIL}', $admin_email, $content);
                $content = str_replace('{ARM_MESSAGE_LOGIN_URL}', $login_url, $content);
                $content = str_replace('{ARM_MESSAGE_PAYMENT_TYPE}', $u_payment_type, $content);
                $content = str_replace('{ARM_MESSAGE_PAYMENT_GATEWAY}', $u_payment_gateway, $content);
                $content = str_replace('{ARM_MESSAGE_TRANSACTION_ID}', $u_transaction_id, $content);
                $content = str_replace('{ARM_MESSAGE_PAYMENT_DATE}', $u_payment_date, $content);
                $content = str_replace('{ARM_MESSAGE_COUPON_CODE}', $u_coupon_code, $content);
                $content = str_replace('{ARM_MESSAGE_SUBSCRIPTION_STATUS}', $data_status, $content);

                /* Content replace for user meta */
                $matches = array();
                preg_match_all("/\b(\w*ARM_USERMETA_\w*)\b/", $content, $matches, PREG_PATTERN_ORDER);
                $matches = $matches[0];
                if (!empty($matches)) {
                    foreach ($matches as $mat_var) {
                        $key = str_replace('ARM_USERMETA_', '', $mat_var);
                        $meta_val = "";
                        if (!empty($key)) {
                            $meta_val = do_shortcode('[arm_usermeta id='.$user_id.' meta="'.$key.'"]',true);
                            /*if(is_array($meta_val))
                            {
                                $replace_val = "";
                                foreach ($meta_val as $key => $value) {
                                    $replace_val .= ($value != '') ? $value."," : "";   
                                }
                                $meta_val = rtrim($replace_val, ",");
                            }*/
                        }
                        $content = str_replace('{' . $mat_var . '}', $meta_val, $content);
                    }
                }
               
            }

            $arm_is_html = $arm_global_settings->arm_is_html( $content );
            if( !$arm_is_html )
            {
                $content = nl2br( $content );
            }
            
            $content = apply_filters('arm_change_advanced_email_communication_email_notification', $content, $user_id, $user_plan);
            return $content;
        }

        function arm_get_communication_messages_by($field = '', $value = '') {
            global $wpdb, $ARMember, $arm_slugs, $arm_subscription_plans, $arm_global_settings;
            $messages = array();
            if (!empty($field) && !empty($value)) {
                $field_key = $field;
                switch ($field) {
                    case 'id':
                    case 'message_id':
                    case 'arm_message_id':
                        $field_key = 'arm_message_id';
                        break;
                    case 'type':
                    case 'message_type':
                    case 'arm_message_type':
                        $field_key = 'arm_message_type';
                        break;
                    case 'status':
                    case 'message_status':
                    case 'arm_message_status':
                        $field_key = 'arm_message_status';
                        break;
                    default:
                        break;
                }
                $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_status`=%d AND `$field_key`=%s",1,$value) ); //phpcs:ignore --Reason $ARMember->tbl_arm_auto_message is a table name
                if (!empty($results)) {
                    $messages = $results;
                }
            }
            return $messages;
        }
        function arm_get_communication_messages_sorted($notifications) {
            $sort_arr = array();
            $new_notif_result = array();
            $now = current_time('timestamp');
            if(!empty($notifications)) {

                foreach ($notifications as $key => $notification) {
                    $period_unit = $notification->arm_message_period_unit;
                    $period_type = $notification->arm_message_period_type;
                    $endtime = strtotime("+$period_unit Days", $now);
                    switch (strtolower($period_type)) {
                        case 'd':
                        case 'day':
                        case 'days':
                            //$endtime = strtotime("+$period_unit Days", $now);
                            $notifications[$key]->arm_message_period_unit = $period_unit;
                            $notifications[$key]->arm_message_period_type = "day";
                            break;
                        case 'w':
                        case 'week':
                        case 'weeks':
                            //$endtime = strtotime("+$period_unit Weeks", $now);
                            $notifications[$key]->arm_message_period_unit = $period_unit * 7;
                            $notifications[$key]->arm_message_period_type = "day";
                            break;
                        case 'm':
                        case 'month':
                        case 'months':
                            //$endtime = strtotime("+$period_unit Months", $now);
                            $unit = 0;
                            for($i=1; $i<=$period_unit; $i++) {
                                $new_date = strtotime("+$i Months", $now);
                                $date = date_create(date("Y-m-d",$new_date));
                                $unit += date_format($date,"t");
                            }
                            $notifications[$key]->arm_message_period_unit = $unit;
                            $notifications[$key]->arm_message_period_type = "day";
                            break;
                        case 'y':
                        case 'year':
                        case 'years':
                            //$endtime = strtotime("+$period_unit Years", $now);
                            $unit = 0;
                            $new_date = strtotime("+$period_unit Years", $now);
                            $date1 = date_create(date("Y-m-d",$now));
                            $date2 = date_create(date("Y-m-d",$new_date));
                            $unit = (abs(date_diff($date1,$date2)->days)) ? abs(date_diff($date1,$date2)->days): 0;
                            $notifications[$key]->arm_message_period_unit = $unit;
                            $notifications[$key]->arm_message_period_type = "day";
                            break;
                        default:
                            break;
                    }
                    if($key > 0) {
                        array_push($new_notif_result, $notification);
                        $cnt = count($new_notif_result) - 1;
                        for($j=$cnt; $j>=0; $j--) {
                            if($new_notif_result[$j]->arm_message_period_unit > $notification->arm_message_period_unit) {
                                $obj = $new_notif_result[$j];
                                $new_notif_result[$j] = $notification;
                                $new_notif_result[$j + 1] = $obj;
                            }
                        }
                            
                    } else {
                        array_push($new_notif_result, $notification);
                    }
                }
            }
            return $new_notif_result;
            
        }

        function arm_edit_message_data() {
            global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_email_settings, $arm_manage_coupons, $arm_manage_communication, $arm_capabilities_global;
            $return = array('status' => 'error');
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            if (isset($_REQUEST['action']) && isset($_REQUEST['message_id']) && $_REQUEST['message_id'] != '') {
                $form_id = 'arm_edit_message_wrapper_frm';
                $mid = intval($_REQUEST['message_id']);
                $result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_id`= %d ",$mid) ); //phpcs:ignore --Reason $ARMember->tbl_arm_auto_message is a table 
                $msg_per_subscription = $result->arm_message_subscription;
                $c_subs = @explode(',', $msg_per_subscription);
                $msge_type = '';
                switch ($result->arm_message_type) {
                    case 'on_new_subscription':
                        $msge_type = esc_html__('On New Subscription', 'ARMember');
                        break;
                    case 'on_cancel_subscription':
                        $msge_type = esc_html__('On Cancel Membership', 'ARMember');
                        break;
                    case 'on_menual_activation':
                        $msge_type = esc_html__('On Manual User Activation', 'ARMember');
                        break;
                    case 'on_change_subscription':
                        $msge_type = esc_html__('On Change Subscription', 'ARMember');
                        break;
                    case 'on_renew_subscription':
                        $msge_type = esc_html__('On Renew Subscription', 'ARMember');
                        break;
                    case 'on_failed':
                        $msge_type = esc_html__('On Failed Payment', 'ARMember');
                        break;
                    case 'on_next_payment_failed':
                        $msge_type = esc_html__('On Semi Automatic Subscription Failed Payment', 'ARMember');
                        break;
                    case 'trial_finished':
                        $msge_type = esc_html__('Trial Finished', 'ARMember');
                        break;
                    case 'on_expire':
                        $msge_type = esc_html__('On Membership Expired', 'ARMember');
                        break;
                    case 'before_expire':
                        $msge_type = esc_html__('Before Membership Expired', 'ARMember');
                        break;
                    case 'manual_subscription_reminder':
                        $msge_type = esc_html__('Before Semi Automatic Subscription Payment due', 'ARMember');
                        break;
                    case 'automatic_subscription_reminder':
                        $msge_type = esc_html__('Before Automatic Subscription Payment due','ARMember');
                        break;
                    case 'on_change_subscription_by_admin':
                        $msge_type = esc_html__('On Change Subscription By Admin', 'ARMember');
                        break;
                    case 'before_dripped_content_available':
                        $msge_type = esc_html__('Before Dripped Content Available', 'ARMember');
                        break;
                    case 'on_recurring_subscription':
                        $msge_type = esc_html__('On Recurring Subscription', 'ARMember');
                        break;
                    case 'on_close_account':
                        $msge_type = esc_html__('On Close User Account', 'ARMember');
                        break;
                    case 'on_login_account':
                        $msge_type = esc_html__('On User Login', 'ARMember');
                        break;
                    case 'on_new_subscription_post':
                        $msge_type = esc_html__('On new paid post purchase', 'ARMember');
                        break;
                    case 'on_recurring_subscription_post':
                        $msge_type = esc_html__('On recurring paid post purchase', 'ARMember');
                        break;
                    case 'on_renew_subscription_post':
                        $msge_type = esc_html__('On renew paid post purchase', 'ARMember');
                        break;
                    case 'on_cancel_subscription_post':
                        $msge_type = esc_html__('On cancel paid post', 'ARMember');
                        break;
                    case 'before_expire_post':
                        $msge_type = esc_html__('Before paid post expire', 'ARMember');
                        break;
                    case 'on_expire_post':
                        $msge_type = esc_html__('On Expire paid post', 'ARMember');
                        break;
                    case 'on_purchase_subscription_bank_transfer':
                        $msge_type = esc_html__('On Purchase membership plan using Bank Transfer', 'ARMember');
                        break;
                    default:
                        break;
                }

                $msge_type = apply_filters('arm_filter_edit_email_notification_type', $msge_type, $result->arm_message_type);

                $return = array(
                    'status' => 'success',
                    'id' => intval($_REQUEST['message_id']),
                    'popup_heading' => $msge_type,
                    'arm_message_type' => $result->arm_message_type,
                    'arm_message_period_unit' => $result->arm_message_period_unit,
                    'arm_message_period_type' => $result->arm_message_period_type,
                    'arm_message_subscription' => $c_subs,
                    'arm_message_subject' => stripslashes_deep($result->arm_message_subject),
                    'arm_message_content' => stripslashes_deep($result->arm_message_content),
                    'arm_message_status' => $result->arm_message_status,
                    'arm_message_send_copy_to_admin' => stripslashes_deep($result->arm_message_send_copy_to_admin),
                    'arm_message_send_diff_copy_to_admin' => $result->arm_message_send_diff_msg_to_admin,
                    'arm_message_admin_message' => stripslashes_deep($result->arm_message_admin_message),
                );
                $return = apply_filters('arm_automated_email_attachment_file_outside',$return);
            }
            echo arm_pattern_json_encode($return);
            exit;
        }

        function arm_recurring_payment_success_email_notification($user_id, $plan_id, $payment_gateway = '', $payment_mode = '', $user_subsdata = '')
        {
            global $wpdb, $ARMember, $arm_manage_communication;
            $args = array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_recurring_subscription');

            $mail_sent = $arm_manage_communication->arm_user_plan_status_action_mail($args);
        }


        /**
         * Sends an email with the provided data to the specified user. 
         * @param object $mail_data_obj The mail data object containing the email subject and content.
         * @param string $user_email The email address of the user to send the email to. 
         * @param int $user_id The ID of the user.
         * @param int $plan_id The ID of the plan.
         * @param int $follwer_id The ID of the follower.
         * @param array $attachment_arr An array of attachments to include in the email. 
         * @param array $mail_addition_data_for_filter An array of additional data for content filter.
         * @return boolean True on success, false on failure.
         * 
         * */
        function arm_common_send_email($mail_data_obj,$user_email,$user_id,$plan_id=0,$follwer_id='',$attachment_arr=array(),$mail_addition_data_for_filter=array()) {

            global $arm_global_settings;
            // change mail content (i.e. Multi Language)
            
            $mail_data_obj_admin = $mail_data_obj;
            
            // $mail_data_obj = apply_filters('arm_get_modify_mail_template_object_for_send_mail', $mail_data_obj,$user_id);
            
            $subject = isset( $mail_data_obj->arm_message_subject ) ? $mail_data_obj->arm_message_subject : '';
            $subject = ( isset($mail_data_obj->arm_template_subject) && !isset($mail_data_obj->arm_message_subject) ) ? $mail_data_obj->arm_template_subject : $subject;

            $message = isset( $mail_data_obj->arm_message_content ) ? $mail_data_obj->arm_message_content : '';
            $message = ( isset( $mail_data_obj->arm_template_content ) && !isset( $mail_data_obj->arm_message_content ) ) ? $mail_data_obj->arm_template_content : $message;
            
            // Content filters for replace variables

            $arm_template_id = isset( $mail_data_obj->arm_template_id ) ? $mail_data_obj->arm_template_id : 0;
            if(empty($arm_template_id) && !empty($mail_data_obj->arm_message_id))
            {
                $arm_template_id = isset( $mail_data_obj->arm_message_id ) ? $mail_data_obj->arm_message_id : 0;
            }
            $arm_message_type = isset( $mail_data_obj->arm_message_type ) ? $mail_data_obj->arm_message_type : '';

            $subject = apply_filters('arm_automated_message_email_content_filter_for_user', $subject,$user_id,$arm_template_id,$arm_message_type,0,$plan_id);
            $message = apply_filters('arm_automated_message_email_content_filter_for_user', $message,$user_id,$arm_template_id,$arm_message_type,1,$plan_id);

            $subject = apply_filters('arm_automated_message_email_content_filter', $subject,$user_id,$plan_id,$follwer_id,$mail_addition_data_for_filter);
            $message = apply_filters('arm_automated_message_email_content_filter', $message,$user_id,$plan_id,$follwer_id,$mail_addition_data_for_filter);
            
            // additional attachment if any 
            $attachments=apply_filters('arm_automated_message_email_attachment', $attachment_arr,$user_id,$mail_data_obj,$plan_id);
            
            $send_mail = $arm_global_settings->arm_wp_mail('', $user_email, $subject, $message, $attachments); 
            
            $send_one_copy_to_admin = empty($mail_data_obj->arm_message_send_copy_to_admin)? 0 : $mail_data_obj->arm_message_send_copy_to_admin;
            if( 1 == $send_one_copy_to_admin ) {

                //change mail content (i.e. Multi Language) for admin specific language
                //$mail_data_obj = apply_filters( 'arm_modify_admin_mail_template_object_for_send_mail', $mail_data_obj_admin, $user_id, $plan_id);
                
                $subject = isset( $mail_data_obj->arm_message_subject ) ? $mail_data_obj->arm_message_subject : '';
                $subject = ( isset($mail_data_obj->arm_template_subject) && !isset($mail_data_obj->arm_message_subject) ) ? $mail_data_obj->arm_template_subject : $subject;

                $message = isset( $mail_data_obj->arm_message_content ) ? $mail_data_obj->arm_message_content : '';
                $message = ( isset( $mail_data_obj->arm_template_content ) && !isset( $mail_data_obj->arm_message_content ) ) ? $mail_data_obj->arm_template_content : $message;

                //Content filters for replace variables

                $subject = apply_filters('arm_automated_message_email_content_filter_for_admin', $subject,$arm_template_id,$arm_message_type,0);
                $subject = apply_filters('arm_automated_message_email_content_filter', $subject,$user_id,$plan_id,$follwer_id,$mail_addition_data_for_filter);

                $message = apply_filters('arm_automated_message_email_content_filter_for_admin', $message,$arm_template_id,$arm_message_type,1);
                $message = apply_filters('arm_automated_message_email_content_filter', $message,$user_id,$plan_id,$follwer_id,$mail_addition_data_for_filter);
                
                $send_diff_copy_to_admin = empty($mail_data_obj->arm_message_send_diff_msg_to_admin)? 0 : $mail_data_obj->arm_message_send_diff_msg_to_admin;
                if( 1 == $send_diff_copy_to_admin )
                {
                    $arm_message_admin_message = empty($mail_data_obj->arm_message_admin_message)? '' : $mail_data_obj->arm_message_admin_message;
                    if( !empty( $arm_message_admin_message ) ) {
                        $arm_message_admin_message = apply_filters('arm_automated_message_email_content_filter', $arm_message_admin_message,$user_id,$plan_id,$follwer_id,$mail_addition_data_for_filter);
                    }
                    $send_mail = $arm_global_settings->arm_send_message_to_armember_admin_users('', $subject, $arm_message_admin_message,$attachments);
                }
                else
                {
				    //$message = !empty($mail_data_obj->arm_message_content)? $mail_data_obj->arm_message_content: '';
                    $send_mail = $arm_global_settings->arm_send_message_to_armember_admin_users('', $subject, $message,$attachments); 
                }
            }
            
            return $send_mail;
        }

        function arm_automated_message_email_content_filter($content,$user_id,$plan_id,$follwer_id,$mail_addtional_data){
            global $arm_manage_communication, $arm_global_settings;
            
            if(isset($mail_addtional_data['ARM_MESSAGE_RESET_PASSWORD_LINK']) && !empty($mail_addtional_data['ARM_MESSAGE_RESET_PASSWORD_LINK'])){
                $content = $arm_manage_communication->arm_filter_communication_content($content, $user_id, $plan_id,$mail_addtional_data['ARM_MESSAGE_RESET_PASSWORD_LINK']);
            }else{
                $content = $arm_manage_communication->arm_filter_communication_content($content, $user_id, $plan_id,$follwer_id);
            }
            $content = $arm_global_settings->arm_filter_email_with_user_detail($content, $user_id, $plan_id,$follwer_id);

            
            if(isset($mail_addtional_data['ARM_MESSAGE_DRIP_CONTENT_URL'])){
                $content = str_replace('{ARM_MESSAGE_DRIP_CONTENT_URL}', $mail_addtional_data['ARM_MESSAGE_DRIP_CONTENT_URL'], $content);
            }
            
            return $content;
        }

        function arm_get_modify_admin_mail_template_object_for_send_mail($mail_obj, $user_id, $plan_id=0, $follower_id=0){

            global $arm_global_settings, $arm_manage_communication;
           
            if(isset($mail_obj->arm_template_subject)){
                $arm_template_subject = $arm_global_settings->arm_filter_email_with_user_detail($mail_obj->arm_template_subject, $user_id, $plan_id, $follower_id);
                $mail_obj->arm_template_subject = $arm_manage_communication->arm_filter_communication_content($arm_template_subject, $user_id, $plan_id, $follower_id);
            }
            if(isset($mail_obj->arm_template_content)){
                $arm_template_content = $arm_global_settings->arm_filter_email_with_user_detail($mail_obj->arm_template_content, $user_id, $plan_id, $follower_id);
                $mail_obj->arm_template_content = $arm_manage_communication->arm_filter_communication_content($arm_template_content, $user_id, $plan_id, $follower_id);
            }
            if(isset($mail_obj->arm_message_subject)){
                $arm_message_subject = $arm_global_settings->arm_filter_email_with_user_detail($mail_obj->arm_message_subject, $user_id, $plan_id, $follower_id);
                $mail_obj->arm_message_subject = $arm_manage_communication->arm_filter_communication_content($arm_message_subject, $user_id, $plan_id, $follower_id);
            }
            if(isset($mail_obj->arm_message_content)){
                $arm_message_content = $arm_global_settings->arm_filter_email_with_user_detail($mail_obj->arm_message_content, $user_id, $plan_id, $follower_id);
                $mail_obj->arm_message_content = $arm_manage_communication->arm_filter_communication_content($arm_message_content, $user_id, $plan_id, $follower_id);
            }
            return $mail_obj;

        }

    }

}
global $arm_manage_communication;
$arm_manage_communication = new ARM_manage_communication();
