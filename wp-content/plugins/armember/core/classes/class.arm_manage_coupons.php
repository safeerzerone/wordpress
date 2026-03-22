<?php
if (!class_exists('ARM_manage_coupons'))
{
    class ARM_manage_coupons
    {
        var $isCouponFeature;
        function __construct()
        {
            global $wpdb, $ARMember, $arm_slugs;
            $is_coupon_feature = get_option('arm_is_coupon_feature', 0);
            $this->isCouponFeature = ($is_coupon_feature == '1') ? true : false;

            add_action('wp_ajax_arm_generate_code', array($this, 'arm_generate_code'));
            add_action('wp_ajax_arm_admin_save_coupon_details', array($this, 'arm_admin_save_coupon_details'));
            add_action('wp_ajax_arm_apply_coupon_code', array($this, 'arm_apply_coupon_code'));
            add_action('wp_ajax_nopriv_arm_apply_coupon_code', array($this, 'arm_apply_coupon_code'));
            add_action('wp_ajax_arm_delete_single_coupon', array($this, 'arm_delete_single_coupon'));
            add_action('wp_ajax_arm_delete_bulk_coupons',array($this,'arm_delete_bulk_coupons'));
            add_action('wp_ajax_arm_update_coupons_status', array($this, 'arm_update_coupons_status'));
            add_action('wp_ajax_arm_get_coupon_members_data', array($this, 'arm_get_coupon_members_data_func'));

            //Load coupon data with ajax
            add_action('wp_ajax_arm_get_coupon_data', array($this, 'arm_load_coupon_data'));

            add_action( 'wp_ajax_arm_get_paid_post_item_coupon_options', array( $this, 'arm_get_paid_post_item_coupon_options' ) );
            add_action('wp_ajax_arm_edit_coupon_detail',array($this,'arm_edit_coupon_detail_func'));
        }

        function arm_edit_coupon_detail_func()
        {
            global $wpdb, $ARMember, $arm_global_settings, $arm_slugs, $arm_payment_gateways, $arm_subscription_plans,$arm_capabilities_global;

            $date_format = $arm_global_settings->arm_get_wp_date_format();

            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], 1,1); //phpcs:ignore --Reason:Verifying nonce

            $arm_common_date_format = $arm_global_settings->arm_check_common_date_format(get_option('date_format'));

            $cid = intval($_REQUEST['id']);
            $result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_id`=%d",$cid) ); //phpcs:ignore --Reason $ARMember->tbl_arm_coupons is a table name
            $c_data=$result;
            $response['c_page_title'] = esc_html__('Edit Coupon','ARMember'); 
            $response['c_id'] = $result->arm_coupon_id;
            $response['c_code'] = $result->arm_coupon_code;
            $response['c_discount'] = $result->arm_coupon_discount;
            $response['c_type'] = $result->arm_coupon_discount_type;
            $response['c_coupon_on_each_subscriptions'] = isset($result->arm_coupon_on_each_subscriptions) ? $result->arm_coupon_on_each_subscriptions : 0;
            $response['c_sdate'] = !empty($result->arm_coupon_start_date) ? date($arm_common_date_format, strtotime($result->arm_coupon_start_date)) : '';
            $response['c_edate'] = !empty($result->arm_coupon_expire_date) ? date($arm_common_date_format, strtotime($result->arm_coupon_expire_date)) : '';
            $c_subs = $result->arm_coupon_subscription;
            $response['c_subs'] = @explode(',', $c_subs);
            $c_paid_posts = !empty($result->arm_coupon_paid_posts) ? $result->arm_coupon_paid_posts : array();
            $c_paid_posts = !empty($c_paid_posts) ? @explode(',', $c_paid_posts) : array();
            if( !empty( $c_paid_posts) ) {
                $arm_plan_name ='';
                $arm_coupon_form_html = '';
                foreach ($c_paid_posts as $arm_paid_post_id_val) {       
                    $arm_subscription_plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($arm_paid_post_id_val);

                    $arm_coupon_form_html .= '<div class="arm_paid_post_itembox arm_paid_post_itembox_'.esc_attr($arm_paid_post_id_val).'">';
                    $arm_coupon_form_html .='<input type="hidden" name="arm_paid_post_item_id['.esc_attr($arm_paid_post_id_val).']" value="'.esc_attr($arm_paid_post_id_val).'" />';
                    $arm_coupon_form_html .='<label style="color:#FFF">'.$arm_subscription_plan_name.'<span class="arm_remove_selected_itembox">x</span></label>';
                    $arm_coupon_form_html .='</div>';
                }
                $response['c_paid_posts'] = $arm_coupon_form_html;
            }
            $arm_coupon_label = !empty($result->arm_coupon_label) ? stripslashes($result->arm_coupon_label) : '';
            $response['c_allowed_uses'] = $result->arm_coupon_allowed_uses;
            $response['c_label']= $arm_coupon_label;
            $response['coupon_status'] = $result->arm_coupon_status;
            $response['c_allow_trial'] = $result->arm_coupon_allow_trial;
            $response['form_id'] = 'arm_edit_coupon_wrapper_frm';
            $readonly = 'readonly = readonly';
            $response['period_type'] = (!empty($result->arm_coupon_period_type)) ? $result->arm_coupon_period_type : 'daterange';
            $response['arm_coupon_type'] = isset($result->arm_coupon_type) ? $result->arm_coupon_type : 1;
            $response['edit_mode'] = true;
            $response['today'] = date('Y-m-d H:i:s');
            $response['action'] = 'edit_coupon';
            if ($response['today']  > $response['c_sdate']) {
                $response['sdate_status'] = $readonly;
            } else {
                $response['sdate_status'] = '';
            }

            echo arm_pattern_json_encode($response);
            die();
        }


        function arm_load_coupon_data()
        {
            global $wpdb, $ARMember, $arm_global_settings, $arm_slugs, $arm_payment_gateways, $arm_subscription_plans,$arm_capabilities_global;

            $date_format = $arm_global_settings->arm_get_wp_date_format();

            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], 1,1); //phpcs:ignore --Reason:Verifying nonce

            $offset = isset( $_POST['iDisplayStart'] ) ? intval($_POST['iDisplayStart']) : 0;//phpcs:ignore
            $limit = isset( $_POST['iDisplayLength'] ) ? intval($_POST['iDisplayLength']) : 10;//phpcs:ignore

            $search_term = ( isset( $_POST['sSearch'] ) && '' != $_POST['sSearch'] ) ? true : false;//phpcs:ignore

            $search_query = 'WHERE 1=1';
            if( $search_term ){
                $search_query .= $wpdb->prepare(" AND (arm_coupon_code LIKE %s )",'%'.$_POST['sSearch'].'%');//phpcs:ignore
                $search_query .= $wpdb->prepare(" OR (arm_coupon_label LIKE %s )",'%'.$_POST['sSearch'].'%');//phpcs:ignore
            }


            $sortOrder = isset( $_POST['sSortDir_0'] ) ? sanitize_text_field($_POST['sSortDir_0']) : 'DESC';//phpcs:ignore
            $sortOrder = strtolower($sortOrder);
            if ( 'asc'!=$sortOrder && 'desc'!=$sortOrder ) {
                $sortOrder = 'desc';
            }

            $orderBy = 'ORDER BY  arm_coupon_id ' . $sortOrder;
            if( !empty( $_POST['iSortCol_0'] ) ){//phpcs:ignore
                if( $_POST['iSortCol_0'] == 0 ){//phpcs:ignore
                    $orderBy = 'ORDER BY arm_coupon_id ' . $sortOrder;
                }else if($_POST['iSortCol_0'] == 1){//phpcs:ignore
                    $orderBy = 'ORDER BY arm_coupon_code ' . $sortOrder;
                }
            }
            $get_coupons = $wpdb->get_results("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` {$search_query} {$orderBy}  LIMIT {$offset}, {$limit}"); //phpcs:ignore --Reason $ARMember->tbl_arm_coupons is a table name

            $total_coupons_query = "SELECT COUNT(arm_coupon_id) AS total FROM {$ARMember->tbl_arm_coupons} {$orderBy}";
            $total_coupons_result = $wpdb->get_results( $total_coupons_query ); //phpcs:ignore --Reason $total_coupons_query is a query
            $total_coupons = $total_coupons_result[0]->total;

            $grid_data = array();
            $ai = 0;


            if( !empty( $get_coupons ))
            {
                $current_timestamp = current_time('timestamp');
                foreach ($get_coupons as $key => $coupon_val) 
                {
                    if( !isset($grid_data[$ai]) || !is_array( $grid_data[$ai] ) ){
                        $grid_data[$ai] = array();
                    }

                    $couponID = $coupon_val->arm_coupon_id;
                    $edit_link = admin_url('admin.php?page='.$arm_slugs->coupon_management.'&action=edit_coupon&coupon_eid='.$couponID);

                    $global_currency = $arm_payment_gateways->arm_get_global_currency();
                    $grid_data[$ai][] = '<tr class="arm_coupons_tr_'.esc_attr($couponID).' row_'.esc_attr($couponID).'">
                    <td class="dt-center"><input class="chkstanard arm_bulk_select_single" type="checkbox" value="'.esc_attr($couponID).'" name="item-action[]"></td>';

                    $switchChecked = ($coupon_val->arm_coupon_status == '1') ? 'checked="checked"' : '';
                    $grid_data[$ai][] = '<td class="center"><div class="armswitch"><input type="checkbox" class="armswitch_input arm_coupon_status_action" id="arm_coupon_status_input_'.esc_attr($couponID).'" value="1" data-item_id="'.esc_attr($couponID).'" '.$switchChecked.'><label class="armswitch_label" for="arm_coupon_status_input_'.esc_attr($couponID).'"></label><span class="arm_status_loader_img"></span></div></td>';
                    $arm_coupon_label = !empty($coupon_val->arm_coupon_label) ? stripslashes($coupon_val->arm_coupon_label) : '';

                    $grid_data[$ai][] = '<td>'.$arm_coupon_label.'</td>';

                    $grid_data[$ai][] = '<td><a href="javascript:void(0)" class="arm_edit_coupon_data_btn" data-coupon_id="'.$couponID.'">'.stripslashes($coupon_val->arm_coupon_code).'</a></td>';

                    $grid_data[$ai][] = '<td class="center">'.$arm_payment_gateways->arm_amount_set_separator($global_currency, $coupon_val->arm_coupon_discount) . (($coupon_val->arm_coupon_discount_type != 'percentage') ? " " .$global_currency : "%").'</td>';
		    
                    $filter_data = "";
                    $filter_data = apply_filters('arm_add_new_coupon_field_body', $filter_data, $coupon_val);
                    if(!empty($filter_data))
                    {
                        $grid_data[$ai][] = $filter_data;
                    }

                    $arm_coupon_expire_date_class = "";
                    if($coupon_val->arm_coupon_period_type == 'daterange') {
                        if(strtotime($coupon_val->arm_coupon_expire_date) < $current_timestamp) {
                            $arm_coupon_expire_date_class = "arm_coupon_date_expire";
                        }
                        $arm_coupon_expire_date_val = date_i18n($date_format,strtotime($coupon_val->arm_coupon_expire_date));
                    }else {
                        $arm_coupon_expire_date_val = esc_html__('Unlimited', 'ARMember');
                    }

                    $grid_data[$ai][] = '<td>'.date_i18n($date_format,strtotime($coupon_val->arm_coupon_start_date)).'</td>';
                    $grid_data[$ai][] = '<td><span class="'.$arm_coupon_expire_date_class.'">'.$arm_coupon_expire_date_val.'</span></td>';

                    $subs_plan_title = '';
                    $arm_coupon_type = isset($coupon_val->arm_coupon_type) ? $coupon_val->arm_coupon_type : 1;
                    $arm_coupon_subscription_plans = !empty($coupon_val->arm_coupon_subscription) ? @explode(',', $coupon_val->arm_coupon_subscription) : array();
                    $arm_coupon_paid_posts = !empty($coupon_val->arm_coupon_paid_posts) ? @explode(',', $coupon_val->arm_coupon_paid_posts) : array();

                    if($arm_coupon_type == 1)
                    {
                        if(!empty($arm_coupon_subscription_plans))
                        {
                            $exclude_paid_posts = 1;
                            $subs_plan_title = $arm_subscription_plans->arm_get_comma_plan_names_by_ids($arm_coupon_subscription_plans, $exclude_paid_posts);
                            $subs_plan_title = (!empty($subs_plan_title)) ? stripslashes($subs_plan_title) : '--';
                        }
                        else{
                            $subs_plan_title = esc_html__('All Membership Plans', 'ARMember');
                        }
                    }
                    else if($arm_coupon_type == 2)
                    {
                        if(!empty($arm_coupon_paid_posts))
                        {
                            $exclude_paid_posts = 0;
                            $subs_plan_title_data = $arm_subscription_plans->arm_get_comma_plan_names_by_ids($arm_coupon_paid_posts, $exclude_paid_posts);
                            $subs_plan_title = (!empty($subs_plan_title_data)) ? stripslashes($subs_plan_title_data) : '';
                        }
                        else
                        {
                            $subs_plan_title = esc_html__('All Paid Posts', 'ARMember');
                        }
                    }
                    else
                    {
                        if(empty($arm_coupon_subscription_plans) && empty($arm_coupon_paid_posts))
                        {
                            $subs_plan_title .= esc_html__('All Membership Plans and paid posts', 'ARMember');
                        }
                        else if(!empty($arm_coupon_subscription_plans) && empty($arm_coupon_paid_posts))
                        {
                            $exclude_paid_posts = 1;
                            $subs_plan_title_data = $arm_subscription_plans->arm_get_comma_plan_names_by_ids($arm_coupon_subscription_plans, $exclude_paid_posts);
                            $subs_plan_title .= (!empty($subs_plan_title_data)) ? stripslashes($subs_plan_title_data) : '';
                            
                            $subs_plan_title .= "<br>";
                            $subs_plan_title .= esc_html__('All Paid Posts', 'ARMember');
                        }
                        else if(empty($arm_coupon_subscription_plans) && !empty($arm_coupon_paid_posts))
                        {
                            $subs_plan_title .= esc_html__('All Membership Plans', 'ARMember');
                            $subs_plan_title .= "<br>";
                            $exclude_paid_posts = 0;
                            $subs_plan_title_data = $arm_subscription_plans->arm_get_comma_plan_names_by_ids($arm_coupon_paid_posts, $exclude_paid_posts);
                            $subs_plan_title .= (!empty($subs_plan_title_data)) ? stripslashes($subs_plan_title_data) : '--';
                        }
			else if(!empty($arm_coupon_subscription_plans) && !empty($arm_coupon_paid_posts))
                        {
                            $exclude_paid_posts = !empty($exclude_paid_posts) ? $exclude_paid_posts : 0;
                            $subs_plan_title_data = $arm_subscription_plans->arm_get_comma_plan_names_by_ids($arm_coupon_subscription_plans, $exclude_paid_posts);
                            $subs_plan_title .= (!empty($subs_plan_title_data)) ? stripslashes($subs_plan_title_data) : '';
                            $subs_plan_title .= "<br>";
                            $exclude_paid_posts = 0;
                            $subs_plan_title_data = $arm_subscription_plans->arm_get_comma_plan_names_by_ids($arm_coupon_paid_posts, $exclude_paid_posts);
                            $subs_plan_title .= (!empty($subs_plan_title_data)) ? stripslashes($subs_plan_title_data) : '';
                        }
                    }

                    

                    $used_coupon_cnt = $coupon_val->arm_coupon_used;
                    if($coupon_val->arm_coupon_used > 0) 
                    {
                        $used_coupon_cnt = '<a class="arm_coupon_members_list_detail" href="javascript:void(0);" data-list_id="'.esc_attr($couponID).'">'.$coupon_val->arm_coupon_used.'</a>';
                        $nonce = wp_create_nonce('arm_wp_nonce');
                        $used_coupon_cnt .='<input type="hidden" name="arm_wp_nonce" value="'.esc_attr( $nonce ).'">';

                    }
                    $grid_data[$ai][] = '<td>'.$used_coupon_cnt.'</td>';

                    $grid_data[$ai][] = '<td class="form_entries">'.(($coupon_val->arm_coupon_allowed_uses == 0) ? esc_html__('Unlimited', 'ARMember') : $coupon_val->arm_coupon_allowed_uses).'</td>';

                    $grid_data[$ai][] = $subs_plan_title;

                    $gridActionData = '<td class="armGridActionTD">';
                    $gridActionData .= '<div class="arm_grid_action_btn_container">';
                    $gridActionData .= '<a href="javascript:void(0)" class="arm_edit_coupon_data_btn armhelptip" title="'.esc_attr__('Edit Coupon','ARMember').'" data-coupon_id="'.$couponID.'"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.2594 3.60022L5.04936 12.2902C4.73936 12.6202 4.43936 13.2702 4.37936 13.7202L4.00936 16.9602C3.87936 18.1302 4.71936 18.9302 5.87936 18.7302L9.09936 18.1802C9.54936 18.1002 10.1794 17.7702 10.4894 17.4302L18.6994 8.74022C20.1194 7.24022 20.7594 5.53022 18.5494 3.44022C16.3494 1.37022 14.6794 2.10022 13.2594 3.60022Z" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.8906 5.0498C12.3206 7.8098 14.5606 9.9198 17.3406 10.1998" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 22H21" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/></svg></a>';
                    $gridActionData .= '<a href="javascript:void(0)" onclick="showConfirmBoxCallback('.$couponID.');" class="arm_grid_delete_action armhelptip" title="'.esc_attr__('Delete','ARMember').'"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 5.33333H21M16.5 5.33333L16.1956 4.43119C15.9005 3.55694 15.7529 3.11982 15.4793 2.79664C15.2376 2.51126 14.9274 2.29036 14.5768 2.1542C14.1798 2 13.7134 2 12.7803 2H11.2197C10.2866 2 9.8202 2 9.4232 2.1542C9.07266 2.29036 8.76234 2.51126 8.5207 2.79664C8.24706 3.11982 8.09954 3.55694 7.80447 4.43119L7.5 5.33333M18.75 5.33333V16.6667C18.75 18.5336 18.75 19.4669 18.3821 20.18C18.0586 20.8072 17.5423 21.3171 16.9072 21.6367C16.1852 22 15.2402 22 13.35 22H10.65C8.75982 22 7.81473 22 7.09278 21.6367C6.45773 21.3171 5.94143 20.8072 5.61785 20.18C5.25 19.4669 5.25 18.5336 5.25 16.6667V5.33333M14.25 9.77778V17.5556M9.75 9.77778V17.5556" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>';
                    $gridActionData .= $arm_global_settings->arm_get_confirm_box($couponID, esc_html__("Are you sure you want to delete this coupon?", 'ARMember'), 'arm_coupon_delete_btn','',esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));
                    $gridActionData .= '</div>';

                    $grid_data[$ai][] = '<div class="arm_grid_action_wrapper">'.$gridActionData.'</div></tr>';


                    $ai++;
                }
            }

            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $after_filter = $total_coupons;
            if( $search_term ){
                $after_filter = $ai;
            }

            $response = array(
                'sColumns' => implode(',',array('Active','Coupon Label','Coupon Code','Discount','Start Date', 'Expire Date', 'Used', 'Allowed Uses','Subscription')),
                'sEcho' => $sEcho,
                'iTotalRecords' => $total_coupons,
                'iTotalDisplayRecords' => $after_filter,
                'aaData' => $grid_data
            );

            echo json_encode( $response );
            die;
        }

        function arm_apply_coupon_code($coupon_code = '', $plan_id = null, $setup_id = 0, $payment_cycle = 0 , $arm_user_old_plan = array())
        {
            global $wpdb, $ARMember, $arm_subscription_plans, $arm_global_settings, $arm_payment_gateways, $arm_membership_setup;

            $common_message = $arm_global_settings->common_message;
            $common_message = apply_filters('arm_modify_common_message_settings_externally', $common_message);
            $return = array(
                'status' => 'error',
                'message' => esc_html__('You can not redeem this coupon code right now.', 'ARMember'),
                'validity' => 'invalid_coupon',
                'coupon_amt' => 0,
                'total_amt' => 0,
                'discount' => 0,
                'discount_type' => '',
            );
            $err_empty_coupon = !empty($common_message['arm_empty_coupon']) ? $common_message['arm_empty_coupon'] : esc_html__('Please enter the coupon code', 'ARMember');

            $err_invalid_coupon = !empty($common_message['arm_invalid_coupon']) ? $common_message['arm_invalid_coupon'] : esc_html__('Coupon code is not valid', 'ARMember');

            $err_invalid_coupon_plan = !empty($common_message['arm_invalid_coupon_plan']) ? $common_message['arm_invalid_coupon_plan'] : esc_html__('Coupon code is not valid for the selected plan', 'ARMember');

            $err_coupon_expire = !empty($common_message['arm_coupon_expire']) ? $common_message['arm_coupon_expire'] : esc_html__('Coupon code has expired', 'ARMember');

            $success_coupon = !empty($common_message['arm_success_coupon']) ? $common_message['arm_success_coupon'] : esc_html__('Coupon has been successfully applied', 'ARMember');
     
            $gateway = (isset($_REQUEST['gateway']) && !empty($_REQUEST['gateway'])) ? sanitize_text_field($_REQUEST['gateway']) : '';
            $payment_mode = (isset($_REQUEST['payment_mode']) && !empty($_REQUEST['payment_mode'])) ? sanitize_text_field($_REQUEST['payment_mode']) : '';
            if ($this->isCouponFeature) {
                $reqCoupon = (isset($_REQUEST['coupon_code']) && !empty($_REQUEST['coupon_code'])) ? sanitize_text_field($_REQUEST['coupon_code']) : '';
                $reqPlanID = (isset($_REQUEST['plan_id']) && !empty($_REQUEST['plan_id'])) ? intval($_REQUEST['plan_id']) : 0;
                $reqSetupID = (isset($_REQUEST['setup_id']) && !empty($_REQUEST['setup_id'])) ? intval($_REQUEST['setup_id']) : 0;
                $reqUserOldPlan = (isset($_REQUEST['user_old_plan']) && !empty($_REQUEST['user_old_plan'])) ? explode(",",sanitize_text_field($_REQUEST['user_old_plan'])) : 0;
                $paymentCycle = (isset($_REQUEST['payment_cycle']) && !empty($_REQUEST['payment_cycle']))? intval($_REQUEST['payment_cycle']) : 0;
                $coupon_code = (!empty($coupon_code)) ? $coupon_code : $reqCoupon;
                $couponData = $this->arm_get_coupon($coupon_code);
                $setupid = (!empty($setup_id)) ? $setup_id : $reqSetupID;
                $arm_user_old_plan =  !empty($arm_user_old_plan) ? $arm_user_old_plan : $reqUserOldPlan; 
                $payment_cycle = ($payment_cycle!= 0 ) ? $payment_cycle : $paymentCycle;
                $is_used_as_invitation_code = false;
                $planAmt = 0;
                if($setupid != 0)
                {
                    $setup_data = $arm_membership_setup->arm_get_membership_setup($setupid);
                    
                     if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                         $setup_modules = $setup_data['setup_modules'];
                         $is_used_as_invitation_code= (isset($setup_modules['modules']['coupon_as_invitation']) && $setup_modules['modules']['coupon_as_invitation'] == 1) ? true : false;
                     }
                }
                if (!empty($couponData)) {
                    
                    $plan_id = ( null === $plan_id ) ? $reqPlanID : $plan_id;
                    if (is_object($plan_id)) {
                        $planObj = $plan_id;
                    } else {
                        $planObj = new ARM_Plan($plan_id);
                    }
                    if ($planObj->exists()) {
                        $plans = $couponData['arm_coupon_subscription'];
                        $allow_plan_ids = explode(',', $plans);
                        $paid_posts = $couponData['arm_coupon_paid_posts'];
                        $allow_post_ids = explode(',', $paid_posts);
                        $allowOnTrial = $couponData['arm_coupon_allow_trial'];
                        $user_count = $couponData['arm_coupon_used'];
                        $allowed_uses = $couponData['arm_coupon_allowed_uses'];
                        $arm_coupon_type = $couponData['arm_coupon_type'];
                        $arm_isPaidPost = (isset($planObj->isPaidPost) && $planObj->isPaidPost != 0 ) ? 1 : 0 ;
                        
                        if ($couponData['arm_coupon_status'] != '1') {
                            $return['message'] = $err_invalid_coupon;
                            $return['validity'] = 'invalid_coupon';
                        } elseif ($allowed_uses != 0 && $allowed_uses <= $user_count) {
                            $return['message'] = $err_coupon_expire;
                            $return['validity'] = 'expired';
                        } elseif ($couponData['arm_coupon_period_type'] == 'daterange' && time() < strtotime($couponData['arm_coupon_start_date'])) {
                            $return['message'] = $err_invalid_coupon;
                            $return['validity'] = 'invalid_coupon';
                        } elseif ($couponData['arm_coupon_period_type'] == 'daterange' && time() > strtotime($couponData['arm_coupon_expire_date'])) {
                            $return['message'] = $err_coupon_expire;
                            $return['validity'] = 'expired';
                        }elseif ($arm_coupon_type == 1 && (!empty($plans) && !in_array($planObj->ID, $allow_plan_ids) || $arm_isPaidPost == 1)) {
                            $return['message'] = $err_invalid_coupon_plan;
                            $return['validity'] = 'invalid_plan';
                        }elseif ($arm_coupon_type == 2 && (!empty($paid_posts) && !in_array($planObj->ID, $allow_post_ids) || $arm_isPaidPost == 0)) {
                            $return['message'] = $err_invalid_coupon_plan;
                            $return['validity'] = 'invalid_plan';
                        }elseif ($arm_coupon_type == 0 && $arm_isPaidPost == 0 && (!empty($plans) && !in_array($planObj->ID, $allow_plan_ids))){
                            $return['message'] = $err_invalid_coupon_plan;
                            $return['validity'] = 'invalid_plan';
                        }elseif ($arm_coupon_type == 0 && $arm_isPaidPost == 1 && (!empty($paid_posts) && !in_array($planObj->ID, $allow_post_ids))){
                            $return['message'] = $err_invalid_coupon;
                            $return['validity'] = 'invalid_coupon';
                        }else{
                            $arm_coupon_not_allowed_on_trial = 0;
                            
                            if($planObj->is_recurring()) {
                                if(isset($planObj->options['payment_cycles']) && !empty($planObj->options['payment_cycles'])) {
                                    $planAmt = str_replace(',','',$planObj->options['payment_cycles'][$payment_cycle]['cycle_amount']);
                                }
                                else {
                                    $planAmt = str_replace(',','',$planObj->amount);
                                }
                                $planAmt = str_replace(',','',$planAmt);
                            }
                            else {
                                $planAmt = str_replace(',','',$planObj->amount);
                            }
                            $planAmt = apply_filters('arm_modify_plan_amount_for_coupon', $planAmt, $planObj, $paymentCycle,$gateway,$payment_mode );
                            
                            if ($planObj->has_trial_period() && (empty($arm_user_old_plan) || $arm_user_old_plan == 0)) {
                                if ($allowOnTrial == '1') {
                                    $planAmt = !empty($planObj->options['trial']['amount']) ? $planObj->options['trial']['amount'] : 0;
                                }
                                else {
                                    $planAmt = 0;
                                    $arm_coupon_not_allowed_on_trial = 1;
                                }
                            }

                            if ((!empty($planAmt) && $planAmt != 0 && $arm_coupon_not_allowed_on_trial == 0) || (!empty($couponData['arm_coupon_on_each_subscriptions']) && $arm_coupon_not_allowed_on_trial == 0)) {
                                do_action('arm_before_apply_coupon_code', $coupon_code, $planObj->ID);
                                $couponAmt = $couponData['arm_coupon_discount'];
                                if ($couponData['arm_coupon_discount_type'] == 'percentage') {
                                    $couponAmt = ($planAmt * $couponAmt) / 100;
                                }
                                $discount_amount = floatval(str_replace(',','',$planAmt));
                                if (!empty($couponAmt) && $couponAmt > 0) {
                                    if($couponAmt > $discount_amount){
                                        $couponAmt = $planAmt;
                                        $discount_amount = '0';
                                    } else {
                                        $discount_amount = $discount_amount - $couponAmt;
                                    }
                                }
                                
				                //Group Membership addon Discount calculate if the selected child user not empty.
                                if(!empty($_REQUEST['arm_selected_child_users'])){
                                    $_REQUEST['armgm'] = sanitize_text_field($_REQUEST['arm_selected_child_users']);
                                }

                                $discount_amount = apply_filters('arm_modify_coupon_pricing', $discount_amount, $planObj, $planAmt, $couponAmt);

                                
                                $global_currency = $arm_payment_gateways->arm_get_global_currency();
                                $couponAmt = $arm_payment_gateways->arm_amount_set_separator($global_currency, $couponAmt, true);
                                $couponAmt = str_replace(',','',$couponAmt);
                                $discount_amount = $arm_payment_gateways->arm_amount_set_separator($global_currency, $discount_amount);
                                $final_amount = $discount_amount . ' ' . $global_currency;
                                $return = array (
                                    'status' => 'success', 'message' => $success_coupon,
                                    'coupon_amt' => $couponAmt, 'total_amt' => $discount_amount,
                                    'discount_type' => $couponData['arm_coupon_discount_type'],
                                    'discount' => $couponData['arm_coupon_discount'],
                                    'arm_coupon_on_each_subscriptions' => $couponData['arm_coupon_on_each_subscriptions'],
                                );
                                do_action('arm_after_apply_coupon_code', $coupon_code, $planObj->ID);
                            } else {
                                if(($planAmt == 0 && $is_used_as_invitation_code == true) || (!empty($couponData['arm_coupon_on_each_subscriptions']) && $arm_coupon_not_allowed_on_trial == 0) )
                                {
                                    $couponAmt = $couponData['arm_coupon_discount'];
                                    $discount_amount = $planAmt;
                                    $return = array (
                                    'status' => 'success', 'message' => $success_coupon,
                                    'coupon_amt' => $couponAmt, 'total_amt' => $discount_amount,
                                    'discount_type' => $couponData['arm_coupon_discount_type'],
                                    'discount' => $couponData['arm_coupon_discount'],
                                    'arm_coupon_on_each_subscriptions' => $couponData['arm_coupon_on_each_subscriptions'],
                                    );
                                }
                                else {
                                    $return['message'] = $err_invalid_coupon_plan;
                                    $return['validity'] = 'invalid_plan';
                                }
                            }
                        }
                    } else {
                        $return['message'] = $err_invalid_coupon;
                        $return['validity'] = 'invalid_coupon';
                    }
                } else {
                    $return['message'] = $err_invalid_coupon;
                    $return['validity'] = 'invalid_coupon';
                }

                $planObj = isset($planObj) ? $planObj : '';
                if(isset($planObj->type) && 'recurring' != $planObj->type){
                    $payment_mode = 0;
                }

                /* Modify Coupon Code outside from plugin */
                $return = apply_filters('arm_change_coupon_code_outside_from_'.$gateway,$return,$payment_mode,$couponData,$planAmt,$planObj);
            }

            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'arm_apply_coupon_code') {
                $ARMember->arm_check_user_cap('',0,1);
                do_action('arm_restrict_specific_coupon_code', $coupon_code);
                echo json_encode($return);
                exit;
            }

            return $return;
        }
        function arm_redeem_coupon_html($content = '', $labels = array(), $plan_data = array(), $btn_style_class = '', $is_used_as_invitation_code = false , $setupRandomID = '',$formPosition = 'left', $form_settings=array())
        {
            global $wpdb, $ARMember, $arm_subscription_plans, $arm_global_settings;
            if ($this->isCouponFeature) {
                $coupon_code = (!empty($_REQUEST['arm_coupon_code'])) ? sanitize_text_field($_REQUEST['arm_coupon_code']) : '';
                $plan_id = (isset($plan_data['arm_subscription_plan_id'])) ? $plan_data['arm_subscription_plan_id'] : 0;
                $check_coupon = $this->arm_apply_coupon_code($coupon_code, $plan_id);
                $common_message = $arm_global_settings->common_message;
                $common_message = apply_filters('arm_modify_common_message_settings_externally', $common_message);
                
                $err_empty_coupon = !empty($common_message['arm_empty_coupon']) ? $common_message['arm_empty_coupon'] : esc_html__('Please enter the coupon code', 'ARMember');

                $err_invalid_coupon = !empty($common_message['arm_invalid_coupon']) ? $common_message['arm_invalid_coupon'] : esc_html__('Coupon code is not valid', 'ARMember');

                $err_invalid_coupon_plan = !empty($common_message['arm_invalid_coupon_plan']) ? $common_message['arm_invalid_coupon_plan'] : esc_html__('Coupon code is not valid for the selected plan', 'ARMember');

                $err_coupon_expire = !empty($common_message['arm_coupon_expire']) ? $common_message['arm_coupon_expire'] : esc_html__('Coupon code has expired', 'ARMember');

                $success_coupon = !empty($common_message['arm_success_coupon']) ? $common_message['arm_success_coupon'] : esc_html__('Coupon has been successfully applied', 'ARMember');
                
                $coupon_code_message = '';
                $check_coupon_plan_type = isset($check_coupon['plan_type']) ? $check_coupon['plan_type'] : '';
                if ($check_coupon['status'] == 'success' && $check_coupon_plan_type != 'free') {
                    $coupon_code_message = '<span class="success notify_msg">' . $check_coupon['message'] . '</span>';
                } else {
                    $coupon_code = '';
                }
                $title_text = (!empty($labels['title'])) ? stripslashes_deep($labels['title']) : esc_html__('Have a coupon code?', 'ARMember');
                $button_text = (!empty($labels['button'])) ? $labels['button'] : esc_html__('Apply', 'ARMember');
                $content = apply_filters('arm_before_redeem_coupon_section', $content);
                $couponBoxID = arm_generate_random_code(20);
                
                switch($formPosition){
                    case 'left':
                        $coupon_style = $coupon_submit_style = "float:left;";
                        break;
                    case 'center':
                        $coupon_style = "float:none;margin:0 auto -6px !important;";
                        $coupon_submit_style = "float:none;";
                        break;
                    case 'right':
                        $coupon_style = $coupon_submit_style = "float:right;";
                        break;
                }
                /*Check for form style*/
                $formStyles = (isset($form_settings['style']) && !empty($form_settings['style'])) ? $form_settings['style'] : array();
                $arm_allow_notched_outline = 0;
                if($formStyles['form_layout'] == 'writer_border')
                {
                    $arm_allow_notched_outline = 1;
                }
                
                $arm_field_wrap_active_class = $ffield_label_html = $ffield_label = '';
                if(!empty($arm_allow_notched_outline))
                {
                    $arm_field_wrap_active_class = (!empty($field_val)) ? ' arm-df__form-material-field-wrap' : '';
                    $ffield_label_html = '<div class="arm-notched-outline">';
                    $ffield_label_html .= '<div class="arm-notched-outline__leading"></div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__notch">';
		            $ffield_label_html .= '<label class="arm-df__label-text arm_material_label" for="arm_coupon_code_'.esc_attr($setupRandomID).'">' . $title_text . '</label>';
		    
                    $ffield_label_html .= '</div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__trailing"></div>';
                    $ffield_label_html .= '</div>';

                    $ffield_label = $ffield_label_html;
                }
                else if($formStyles['form_layout'] == 'writer') {
                    $ffield_label = '<label class="arm-df__label-text" for="arm_coupon_code_'.esc_attr($setupRandomID).'">' . $title_text . '</label>';
		        }
                /**/

                if(file_exists(ABSPATH . 'wp-admin/includes/file.php')){
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                WP_Filesystem();
                global $wp_filesystem;
                $arm_loader_url = MEMBERSHIPLITE_IMAGES_DIR . "/loader.svg";
                $arm_loader_img = $wp_filesystem->get_contents($arm_loader_url);

                $content .= '<div class="arm_apply_coupon_container arm_position_'.esc_attr($formPosition).'" id="'.esc_attr($couponBoxID).'">';
                    $coupon_style = "";
                    $coupon_submit_style = "";
                        $content .= '<div class="arm-control-group arm_coupon_field_wrapper arm-df__form-group arm-df__form-group_text" style="'.$coupon_style.'">';
                            $content .= '<div class="arm-df__form-field">';
                                $content .= '<div class="arm-df__form-field-wrap arm-controls arm-df__form-field-wrap_coupon_code">';
                                        $arm_error_couponMessages='';
                                        if($is_used_as_invitation_code == true){
                                            $couponInputAttr = ' required data-validation-required-message="'.$err_empty_coupon.'"  data-isRequiredCoupon="true" ';
                                        } else {
                                            $couponInputAttr = ' data-isRequiredCoupon="false" ';
                                        }
                                        
                                        $content .= '<input type="text" id="arm_coupon_code_'.esc_attr($setupRandomID).'" name="arm_coupon_code" value="'.esc_attr($coupon_code).'" class="arm-df__form-control field_coupon_code arm_coupon_code" data-checkcouponcode-message="' .  stripcslashes($err_empty_coupon) . '" '.$couponInputAttr.' >';                                        
                                        $content .= $ffield_label;
                                        $nonce = wp_create_nonce('arm_wp_nonce');
                                        $content .= '<input type="hidden" name="arm_wp_nonce" value="'. esc_attr( $nonce ).'">';
                                $content .= '</div>';
                            $content .= '</div>';
			    $content .= $arm_error_couponMessages;                                       
                                    $content .= $coupon_code_message;
                        $content .= '</div>';
                        $content .= '<div class="arm_coupon_submit_wrapper arm-df__form-group arm-df__form-group_submit" style="'.$coupon_submit_style.'">';
                            $content .= '<div class="arm-df__form-field">';
                                $content .= '<div class="arm-df__form-field-wrap_submit arm-df__form-field-wrap arm-controls" id="arm_setup_coupon_button_container">';
                                $content .= '<button type="button" class="arm_apply_coupon_btn arm-df__form-control-submit-btn arm-df__form-group_button arm_material_input '.$btn_style_class.'"><span class="arm_spinner">'.$arm_loader_img.'</span>' . esc_html(stripslashes($button_text)) . '</button>';
                                $content .= '</div>';
                            $content .= '</div>';
                        $content .= '</div>';
                    $content .= '</div>';
                $content = apply_filters('arm_after_redeem_coupon_section', $content);
            }
            return $content;
        }
        function arm_generate_coupon_code()
        {
            $couponCode = '';
            if (function_exists('arm_generate_random_code')) {
                $couponCode = arm_generate_random_code(8);
            } else {
                $coupon_char = array();
                $coupon_char[] = array('count' => 6, 'char' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
                $coupon_char[] = array('count' => 2, 'char' => '0123456789');
                $temp_array = array();
                foreach ($coupon_char as $char_set) {
                    for ($i = 0; $i < $char_set['count']; $i++) {
                        $temp_array[] = $char_set['char'][rand(0, strlen($char_set['char']) - 1)];
                    }
                }
                shuffle($temp_array);
                $couponCode = implode('', $temp_array);
            }
            return $couponCode;
        }
        function arm_generate_code()
        {
            global $wpdb, $ARMember, $arm_slugs, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $arm_code = $this->arm_generate_coupon_code();

            $old_coupon =  $this->arm_get_coupon($arm_code);
            if (!empty($old_coupon) && is_array($old_coupon)) {
                $this->arm_generate_code();
            } else {
                $response = array('arm_coupon_code' => $arm_code);
                echo arm_pattern_json_encode($response);
            }
            die();
        }
        function arm_admin_save_coupon_details() {
            global $wpdb, $ARMember, $arm_slugs, $arm_global_settings,$arm_capabilities_global;
            

            if(isset($_REQUEST['arm_action']))
            {
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], '1',1); //phpcs:ignore --Reason:Verifying nonce
                $coupon_data = $_POST;
            }

            $op_type = $coupon_data['op_type'];
            
            $coupon_code = (isset($coupon_data['arm_coupon_code']) && !empty($coupon_data['arm_coupon_code'])) ? sanitize_text_field($coupon_data['arm_coupon_code']) : '';
            $coupon_discount = (isset($coupon_data['arm_coupon_discount'])) ? $coupon_data['arm_coupon_discount'] : '';
            $arm_coupon_on_each_subscriptions = (isset($coupon_data['arm_coupon_on_each_subscriptions'])) ? intval($coupon_data['arm_coupon_on_each_subscriptions']) : 0;
            $coupon_discount_type = (isset($coupon_data['arm_discount_type']) && !empty($coupon_data['arm_discount_type'])) ? sanitize_text_field($coupon_data['arm_discount_type']) : '';
            $coupon_label = (isset($coupon_data['arm_coupon_label']) && !empty($coupon_data['arm_coupon_label'])) ? sanitize_text_field($coupon_data['arm_coupon_label']) : '';
            $coupon_period_type = (isset($coupon_data['arm_coupon_period_type']) && !empty($coupon_data['arm_coupon_period_type'])) ? sanitize_text_field($coupon_data['arm_coupon_period_type']) : 'daterange';
            $coupon_start = (isset($coupon_data['arm_coupon_start_date']) && !empty($coupon_data['arm_coupon_start_date'])) ? $coupon_data['arm_coupon_start_date'] : date('Y-m-d');
            $coupon_expire = (isset($coupon_data['arm_coupon_expire_date']) && !empty($coupon_data['arm_coupon_expire_date'])) ? $coupon_data['arm_coupon_expire_date'] : date('Y-m-d');
            $coupon_status = (isset($coupon_data['arm_coupon_status']) && !empty($coupon_data['arm_coupon_status'])) ? intval($coupon_data['arm_coupon_status']) : 1;
            $coupon_allow_trial = (isset($coupon_data['arm_coupon_allow_trial']) && !empty($coupon_data['arm_coupon_allow_trial'])) ? intval($coupon_data['arm_coupon_allow_trial']) : 0;
            $coupon_subscription = (isset($coupon_data['arm_subscription_coupons']) && !empty($coupon_data['arm_subscription_coupons'])) ? $coupon_data['arm_subscription_coupons'] : array();                  

            $paid_post_coupon_subscription = (isset( $coupon_data['arm_paid_post_item_id'] ) && !empty($coupon_data['arm_paid_post_item_id']) )  ? $coupon_data['arm_paid_post_item_id'] : array();

            $arm_coupon_type = isset($coupon_data['arm_coupon_type']) ? $coupon_data['arm_coupon_type'] : 0;
            
            $coupon_subscription = (!empty($coupon_subscription)) ? @implode(',', $coupon_subscription) : '';
            $paid_post_coupon_subscription = (!empty($paid_post_coupon_subscription)) ? @implode(',', $paid_post_coupon_subscription) : '';
            $coupon_allowed_uses = (!empty($coupon_data['arm_allowed_uses']) && is_numeric($coupon_data['arm_allowed_uses'])) ? $coupon_data['arm_allowed_uses'] : 0;
            $coupon_apply_to = (isset($coupon_data['arm_coupon_apply_to']) && !empty($coupon_data['arm_coupon_apply_to'])) ? $coupon_data['arm_coupon_apply_to'] : '';
            $coupon_start_date = date('Y-m-d H:i:s', strtotime($coupon_start));
            $coupon_expire_date = date('Y-m-d 23:59:59', strtotime($coupon_expire));
            if ($coupon_period_type == 'unlimited') {
                $coupon_start_date = date('Y-m-d H:i:s');
                $coupon_expire_date = date('Y-m-d 23:59:59');
            }

            $c_where = '';
            if ($op_type == 'edit' && !empty($coupon_data['arm_edit_coupon_id']) && $coupon_data['arm_edit_coupon_id'] != 0) {
                $c_where = " AND `arm_coupon_id` != '" . $coupon_data['arm_edit_coupon_id'] . "' ";
            }
            $old_coupon =  $this->arm_get_coupon($coupon_code, $c_where);
            $check_status = 0;
            if (!empty($old_coupon) && is_array($old_coupon)) {
                $check_status = 1;
            }

            $coupons_values = array(
                'arm_coupon_code' => $coupon_code,
                'arm_coupon_label' => $coupon_label,
                'arm_coupon_discount' => $coupon_discount,
                'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions,
                'arm_coupon_discount_type' => $coupon_discount_type,
                'arm_coupon_period_type' => $coupon_period_type,
                'arm_coupon_start_date' => $coupon_start_date,
                'arm_coupon_expire_date' => $coupon_expire_date,
                'arm_coupon_subscription' => $coupon_subscription,
                'arm_coupon_paid_posts' => $paid_post_coupon_subscription,
                'arm_coupon_allow_trial' => $coupon_allow_trial,
                'arm_coupon_allowed_uses' => $coupon_allowed_uses,
                'arm_coupon_status' => $coupon_status,
                'arm_coupon_type' => $arm_coupon_type,
                'arm_coupon_added_date' => date('Y-m-d H:i:s')
            );
            $coupons_values = apply_filters( 'arm_before_admin_save_coupon', $coupons_values, $coupon_data );
            if($op_type == 'bulk_add' && isset($coupon_data['arm_coupon_code_type']) && !empty($coupon_data['arm_coupon_code_type']) && isset($coupon_data['arm_coupon_quantity']) && !empty($coupon_data['arm_coupon_quantity']) && isset($coupon_data['arm_coupon_code_length']) && !empty($coupon_data['arm_coupon_code_length'])){
                for($c=0;$c<$coupon_data['arm_coupon_quantity'];$c++) {
                     $arm_coupon_code=$this->arm_bulk_generate_code($coupon_data['arm_coupon_code_length'],$coupon_data['arm_coupon_code_type']); 
                     $coupons_values['arm_coupon_code']=$arm_coupon_code;
                     $ins = $wpdb->insert($ARMember->tbl_arm_coupons, $coupons_values);
                }
                
            }
            if ($op_type == 'add') {
                if ($check_status != 1) {
                    $ins = $wpdb->insert($ARMember->tbl_arm_coupons, $coupons_values);
                    if ($ins) {
                        $message = esc_html__('Coupon Added Successfully.', 'ARMember');
                        $status = 'success';
                        $coupon_id = $wpdb->insert_id;                       
                    } else {
                        $message = esc_html__('Error Adding Coupons, Please Again Try Again.', 'ARMember');
                        $status = 'failed';                       
                    }
                } else {
                    $message = esc_html__('Could Not Perform The Operation, Because Coupon Code Already Exists.', 'ARMember');
                    $status = 'failed';
                }
            }else if ($op_type == 'bulk_add') {
                if ($check_status != 1) {
                    $message = esc_html__('Coupons Added Successfully.', 'ARMember');
                    $status = 'success';
                    // $edit_coupon_link = admin_url('admin.php?page='.$arm_slugs->coupon_management);
                }    
            } else {

                $c_id = $coupon_data['arm_edit_coupon_id'];
                if ($check_status != 1) {
                    unset($coupons_values['arm_coupon_code']);
                    $where = array('arm_coupon_id' => $c_id);
                    $up = $wpdb->update($ARMember->tbl_arm_coupons, $coupons_values, $where);
                    if ($up) {
                        $message = esc_html__('Coupon Updated Successfully.', 'ARMember');
                        $status = 'success';
                        // $edit_coupon_link = admin_url('admin.php?page=' . $arm_slugs->coupon_management . '&action=edit_coupon&coupon_eid=' . $c_id);
                    } else {
                        $message = esc_html__('Error Updating Coupons, Please Again Try Again.', 'ARMember');
                        $status = 'failed';
                        // $edit_coupon_link = admin_url('admin.php?page=' . $arm_slugs->coupon_management . '&action=edit_coupon&coupon_eid=' . $c_id);
                    }
                } else {
                    $message = esc_html__('Could Not Perform The Operation, Because Coupon Code Already Exists.', 'ARMember');
                    $status = 'failed';
                    $edit_coupon_link = admin_url('admin.php?page=' . $arm_slugs->coupon_management . '&action=edit_coupon&coupon_eid=' . $c_id);
                }
            }

            echo arm_pattern_json_encode( array('status'=>$status,'type'=>$status,'msg'=> $message));
            die();
        }
        function arm_op_coupons()
        {
            global $wpdb, $ARMember, $arm_slugs, $arm_global_settings;
            $op_type = !empty($_REQUEST['op_type']) ? sanitize_text_field($_REQUEST['op_type']): ''; //phpcs:ignore
            //$posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
	    $posted_data = $_POST; //phpcs:ignore
            $coupon_code = (isset($_POposted_dataST['arm_coupon_code']) && !empty($posted_data['arm_coupon_code'])) ? $posted_data['arm_coupon_code'] : '';
            $coupon_discount = (isset($posted_data['arm_coupon_discount']) && !empty($posted_data['arm_coupon_discount'])) ? $posted_data['arm_coupon_discount'] : '';                
            $coupon_discount_type = (isset($posted_data['arm_discount_type']) && !empty($posted_data['arm_discount_type'])) ? $posted_data['arm_discount_type'] : '';
            $coupon_period_type = (isset($posted_data['arm_coupon_period_type']) && !empty($posted_data['arm_coupon_period_type'])) ? $posted_data['arm_coupon_period_type'] : 'daterange';                               
            $coupon_start = (isset($posted_data['arm_coupon_start_date']) && !empty($posted_data['arm_coupon_start_date'])) ? $posted_data['arm_coupon_start_date'] : date('Y-m-d');
            $coupon_expire = (isset($posted_data['arm_coupon_expire_date']) && !empty($posted_data['arm_coupon_expire_date'])) ? $posted_data['arm_coupon_expire_date'] : date('Y-m-d');          
            $coupon_status = (isset($posted_data['arm_coupon_status']) && !empty($posted_data['arm_coupon_status'])) ? $posted_data['arm_coupon_status'] : 0;
            $coupon_allow_trial = (isset($posted_data['arm_coupon_allow_trial']) && !empty($posted_data['arm_coupon_allow_trial'])) ? $posted_data['arm_coupon_allow_trial'] : 0;
            $coupon_subscription = (isset($posted_data['arm_subscription_coupons']) && !empty($posted_data['arm_subscription_coupons'])) ? $posted_data['arm_subscription_coupons'] : '';
            $coupon_subscription = (!empty($coupon_subscription)) ? @implode(',', $coupon_subscription) : '';
            $coupon_allowed_uses = (!empty($posted_data['arm_allowed_uses']) && is_numeric($posted_data['arm_allowed_uses'])) ? $posted_data['arm_allowed_uses'] : 0;
            $coupon_apply_to = (isset($posted_data['arm_coupon_apply_to']) && !empty($posted_data['arm_coupon_apply_to'])) ? $posted_data['arm_coupon_apply_to'] : '';
            $coupon_start_date = date('Y-m-d H:i:s', strtotime($coupon_start));
            $coupon_expire_date = date('Y-m-d 23:59:59', strtotime($coupon_expire));
            if ($coupon_period_type == 'unlimited') {
                $coupon_start_date = date('Y-m-d H:i:s');
                $coupon_expire_date = date('Y-m-d 23:59:59');
            }
            
            $c_where = '';
            if ($op_type == 'edit' && !empty($_REQUEST['arm_edit_coupon_id']) && $_REQUEST['arm_edit_coupon_id'] != 0) {
                $c_where = " AND `arm_coupon_id` != '" . intval($_REQUEST['arm_edit_coupon_id']) . "'";
            }
            $old_coupon =  $this->arm_get_coupon($coupon_code, $c_where);
            $check_status = 0;
            if (!empty($old_coupon) && is_array($old_coupon)) {
                $check_status = 1;
            }
            
            $coupons_values = array(
                'arm_coupon_code' => $coupon_code,
                'arm_coupon_discount' => $coupon_discount,
                'arm_coupon_discount_type' => $coupon_discount_type,
                'arm_coupon_period_type' => $coupon_period_type,
                'arm_coupon_start_date' => $coupon_start_date,
                'arm_coupon_expire_date' => $coupon_expire_date,
                'arm_coupon_subscription' => $coupon_subscription,
                'arm_coupon_allow_trial' => $coupon_allow_trial,
                'arm_coupon_allowed_uses' => $coupon_allowed_uses,
                'arm_coupon_status' => $coupon_status,
                'arm_coupon_added_date' => date('Y-m-d H:i:s')
            );
            
            if ($op_type == 'add')
            {
                if ($check_status != 1) {
                    $ins = $wpdb->insert($ARMember->tbl_arm_coupons, $coupons_values);
                    if ($ins) {
                        $message = esc_html__('Coupon Added Successfully.', 'ARMember');
                        $status = 'success';
                        $coupon_id = $wpdb->insert_id;
                        $edit_coupon_link = admin_url('admin.php?page=' . $arm_slugs->coupon_management . '&action=edit_coupon&coupon_eid=' . $coupon_id);                                       
                    } else {
                        $message = esc_html__('Error Adding Coupons, Please Again Try Again.', 'ARMember');
                        $status = 'failed';
                        $edit_coupon_link = '';
                    }               
                } else {
                    $message = esc_html__('Could Not Perform The Operation, Because Coupon Code Already Exists.', 'ARMember');
                    $status = 'failed';
                    $edit_coupon_link = '';
                }
            } else {
                $c_id = !empty($_REQUEST['arm_edit_coupon_id']) ? intval($_REQUEST['arm_edit_coupon_id']) : 0;
                if ($check_status != 1) {                   
                    $where = array('arm_coupon_id' => $c_id);
                    $up = $wpdb->update($ARMember->tbl_arm_coupons, $coupons_values, $where);
                    if ($up) {
                        $message = esc_html__('Coupon Updated Successfully.', 'ARMember');
                        $status = 'success';
                        $edit_coupon_link = admin_url('admin.php?page=' . $arm_slugs->coupon_management . '&action=edit_coupon&coupon_eid=' . $c_id);
                    } else {
                        $message = esc_html__('Error Updating Coupons, Please Again Try Again.', 'ARMember');
                        $status = 'failed';
                        $edit_coupon_link = admin_url('admin.php?page=' . $arm_slugs->coupon_management . '&action=edit_coupon&coupon_eid=' . $c_id);
                    }
                } else {
                    $message = esc_html__('Could Not Perform The Operation, Because Coupon Code Already Exists.', 'ARMember');
                    $status = 'failed';
                    $edit_coupon_link = admin_url('admin.php?page=' . $arm_slugs->coupon_management . '&action=edit_coupon&coupon_eid=' . $c_id);
                }   
            }
            $response = array('status' => $status, 'message' => $message, 'url' => $edit_coupon_link);
            echo json_encode($response);
            die();
        }
        function arm_update_coupons_status()
        {
            global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $response = array('type'=>'error', 'msg'=>esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
            if (!empty($_POST['arm_coupon_id']) && $_POST['arm_coupon_id'] != 0)//phpcs:ignore
            {
                $coupon_id = intval($_POST['arm_coupon_id']);//phpcs:ignore
                $arm_coupon_status = (!empty($_POST['arm_coupon_status'])) ? intval($_POST['arm_coupon_status']) : 0;//phpcs:ignore
                $coupons_values = array(
                    'arm_coupon_status' => $arm_coupon_status,
                );
                $update_temp = $wpdb->update($ARMember->tbl_arm_coupons, $coupons_values, array('arm_coupon_id' => $coupon_id));
                $response = array('type'=>'success', 'msg'=>esc_html__('Coupon Updated Successfully.', 'ARMember'));
            }
            echo arm_pattern_json_encode($response);
            die();
        }
        function arm_get_coupon($coupon_code = '', $where_condition='')
        {
            global $wpdb, $ARMember, $arm_slugs;
            $coupon_detail = FALSE;
            if (!empty($coupon_code)) {
                //$coupon_detail = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_code` LIKE '$coupon_code'", ARRAY_A);
                $coupon_details = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_code`=%s {$where_condition}",$coupon_code), ARRAY_A);//phpcs:ignore --Reason $ARMember->tbl_arm_coupons is a table name
                if (empty($coupon_details)) {
                    $coupon_detail = FALSE;
                } else {
                    $ismatchedCoupon = FALSE;
                    foreach($coupon_details as $coupon_detail) {
                        $couponCodeDB = $coupon_detail['arm_coupon_code'];
                        if( $couponCodeDB == $coupon_code  ){
                            $ismatchedCoupon = TRUE;
                            break;
                        }
                    }
                    if($ismatchedCoupon==FALSE)
                    {
                        $coupon_detail = FALSE;
                    }
                }
            }
            return $coupon_detail;
        }

        function arm_get_coupon_by_id($coupon_id = '')
        {
            global $wpdb, $ARMember;
            $coupon_detail = FALSE;
            if (!empty($coupon_id)) {
                $coupon_detail = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_id` = %d",$coupon_id), ARRAY_A); //phpcs:ignore --Reason ARMember->tbl_arm_coupons is a table name
                if (!empty($coupon_detail)) {
                    $couponIdDB = $coupon_detail['arm_coupon_id'];
                    if( $couponIdDB != $coupon_id  ){
                        $coupon_detail = FALSE;
                    }
                }
            }
            return $coupon_detail;
        }
        function arm_update_coupon_used_count($coupon_code = '')
        {
            global $wpdb, $ARMember, $arm_slugs;
            if (!empty($coupon_code)) {
                $arm_check_coupon_details = $this->arm_get_coupon($coupon_code);
                if(!empty($arm_check_coupon_details) && is_array($arm_check_coupon_details))
                {
                    $coupon_id = $arm_check_coupon_details['arm_coupon_id'];
                    $used_coupons = $wpdb->get_results( $wpdb->prepare("UPDATE `" . $ARMember->tbl_arm_coupons . "` SET `arm_coupon_used` = `arm_coupon_used`+1 WHERE `arm_coupon_id` = %d ",$coupon_id) ); //phpcs:ignore --Reason $ARMember->tbl_arm_coupons is a table name
                    return $used_coupons;
                }

            }
            return FALSE;
            
        }
        function arm_get_coupon_amount($coupon_code, $payment_amount = 0, $plan_id = 0)
        {
            global $wpdb, $ARMember, $arm_slugs,$arm_global_settings;

            $general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
	    $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;

            $coupon_amount = 0;
            if ($this->isCouponFeature) {
                $coupon_detail = $this->arm_get_coupon($coupon_code);
                if ($coupon_detail !== FALSE) {
                    if ($coupon_detail['arm_coupon_discount'] != 0) {
                        $plans = $coupon_detail['arm_coupon_subscription'];
                        $allow_plan_ids = explode(',', $plans);
                        //$user_count = $this->arm_get_used_coupon_count($coupon_code);
                        $user_count = $coupon_detail['arm_coupon_used'];
                        $allowed_uses = $coupon_detail['arm_coupon_allowed_uses'];
                        if ($coupon_detail['arm_coupon_status'] != '1') {
                            $coupon_amount = 0;
                        } elseif ($allowed_uses != 0 && $allowed_uses <= $user_count) {
                            $coupon_amount = 0;
                        } elseif ($coupon_detail['arm_coupon_period_type'] == 'daterange' && time() < strtotime($coupon_detail['arm_coupon_start_date'])) {
                            $coupon_amount = 0;
                        } elseif ($coupon_detail['arm_coupon_period_type'] == 'daterange' && time() > strtotime($coupon_detail['arm_coupon_expire_date'])) {
                            $coupon_amount = 0;
                        } elseif (!empty($plans) && !in_array($plan_id, $allow_plan_ids)) {
                            $coupon_amount = 0;
                        } else {
                            $coupon_amount = $coupon_detail['arm_coupon_discount'];
                            if ($coupon_detail['arm_coupon_discount_type'] == 'percentage') {
                                $coupon_amount = ($payment_amount * $coupon_amount) / 100;
                            }
                            $coupon_amount = number_format((float) $coupon_amount, $arm_currency_decimal);
                        }
                    }
                }
            }
            return $coupon_amount;
        }
        function arm_get_used_coupon_count($coupon_code = '')
        {
            global $wpdb, $ARMember, $arm_slugs;
            $used_count = 0;
            $coupon_detail = $this->arm_get_coupon($coupon_code);
            if(!empty($coupon_detail) && is_array($coupon_detail))
            {
                $used_count = $coupon_detail['arm_coupon_used'];
            }
            return $used_count;
        }
        function arm_get_all_coupons()
        {
            global $wpdb, $ARMember, $arm_slugs;
            return $row = $wpdb->get_results("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` ORDER BY `arm_coupon_id` DESC"); //phpcs:ignore --Reason Query without any where clause
        }
        function arm_total_coupons()
        {
            global $wpdb, $ARMember;
            $coupon_count = $wpdb->get_var("SELECT COUNT(`arm_coupon_id`) FROM `" . $ARMember->tbl_arm_coupons . "`"); //phpcs:ignore --Reason Query without where clause
            return $coupon_count;
        }
        function arm_delete_single_coupon()
        {
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $action = sanitize_text_field($_POST['act']); //phpcs:ignore
            $id = intval($_POST['id']);//phpcs:ignore
            if( $action == 'delete' )
            {
                if (empty($id)) {
                    $errors[] = esc_html__('Invalid action.', 'ARMember');
                } else {
                    if (!current_user_can('arm_manage_coupons')) {
                        $errors[] = esc_html__('Sorry, You do not have permission to perform this action.', 'ARMember');
                    } else {
                        $res_var = $wpdb->delete($ARMember->tbl_arm_coupons, array('arm_coupon_id' => $id));
                        if ($res_var) {
                            $message = esc_html__('Coupon has been deleted successfully.', 'ARMember');
                        }
                    }
                }
            }
            $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
            echo arm_pattern_json_encode($return_array);
            exit;
        }
        function arm_delete_bulk_coupons()
        {
            if (!isset($_POST)) {//phpcs:ignore
                return;
            }
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $bulkaction = $arm_global_settings->get_param('action1');
            if ($bulkaction == -1) {
                $bulkaction = $arm_global_settings->get_param('action2');
            }
            $ids = $arm_global_settings->get_param('item-action', '');
            if (empty($ids))
            {
                $errors[] = esc_html__('Please select one or more records.', 'ARMember');
            } else {
                if (!current_user_can('arm_manage_coupons')) {
                    $errors[] = esc_html__('Sorry, You do not have permission to perform this action.', 'ARMember');
                } else {
                    if (!is_array($ids)) {
                        $ids = explode(',', $ids);
                    }
                    if (is_array($ids)) {
                        if ($bulkaction == 'delete_coupon') {
                            foreach ($ids as $coupon_id) {
                                $res_var = $wpdb->delete($ARMember->tbl_arm_coupons, array('arm_coupon_id' => $coupon_id));
                            }
                            if ($res_var) {
                                $message = esc_html__('Coupon(s) has been deleted successfully.', 'ARMember');
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

        function arm_coupon_apply_to_subscription($user_ID, $log_detail,$pgateway,$userPlanData)
        {
            global $wp, $wpdb, $ARMember, $arm_manage_coupons,$arm_global_settings;

            $general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
	    $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;

            $log_id = isset($log_detail->arm_log_id) ? $log_detail->arm_log_id : $log_detail;
                
            if(!empty($log_id))
            {
                
                $armLogTable = $ARMember->tbl_arm_payment_log;
                
                
                $arm_current_plan_detail = !empty($userPlanData['arm_current_plan_detail']) ? $userPlanData['arm_current_plan_detail'] : '';
                if(!empty($arm_current_plan_detail))
                {
                    if(MEMBERSHIP_DEBUG_LOG == true) {
                        $ARMember->arm_write_response("ARMember COUPON LOG 1 : arm_coupon_apply_to_subscription plan detail : ".maybe_serialize($arm_current_plan_detail));
                    }
                    $arm_subscription_plan_type = isset($arm_current_plan_detail['arm_subscription_plan_type']) ? $arm_current_plan_detail['arm_subscription_plan_type'] : '';
                    $arm_subscription_plan_id = isset($arm_current_plan_detail['arm_subscription_plan_id']) ? $arm_current_plan_detail['arm_subscription_plan_id'] : '';
                    $arm_subscription_plan_options = isset($arm_current_plan_detail['arm_subscription_plan_options']) ? maybe_unserialize($arm_current_plan_detail['arm_subscription_plan_options']) : '';
                    if($arm_subscription_plan_type=='recurring')
                    {
                        if(MEMBERSHIP_DEBUG_LOG == true) {
                            $ARMember->arm_write_response("ARMember COUPON LOG 2 : arm_coupon_apply_to_subscription inside recurring plan");
                        }
                        $user_subscription_payment_cycle = get_user_meta($user_ID, 'payment_cycle_'.$arm_subscription_plan_id, true);
                        $user_subscription_payment_cycle = isset($arm_current_plan_detail['arm_user_selected_payment_cycle']) ? $arm_current_plan_detail['arm_user_selected_payment_cycle'] : $user_subscription_payment_cycle;
                        $userPlanData = get_user_meta($user_ID, 'arm_user_plan_'.$arm_subscription_plan_id, true);
                        
                        if($user_subscription_payment_cycle=='') {
                            $user_subscription_payment_cycle = 0;
                        }
                        $arm_subscription_plan_amount = $arm_current_plan_detail['arm_subscription_plan_amount'];
                        if(isset($arm_current_plan_detail['arm_subscription_plan_amount_original']) && !empty($arm_current_plan_detail['arm_subscription_plan_amount_original']) )
                        {
                            $arm_subscription_plan_amount = $arm_current_plan_detail['arm_subscription_plan_amount_original'];
                        }

                        $log_details = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$armLogTable}` WHERE `arm_log_id`=%d",$log_id)); //phpcs:ignore --Reason $armLogTable is a table name
                        if(!empty($log_details))
                        {
                            if(MEMBERSHIP_DEBUG_LOG == true) {
                                $ARMember->arm_write_response("ARMember COUPON LOG 3 : arm_coupon_apply_to_subscription log details : ". maybe_serialize($log_details));
                            }
                            $arm_coupon_discount = $log_details->arm_coupon_discount;
                            $arm_coupon_discount_type = $log_details->arm_coupon_discount_type;
                            $arm_coupon_code = $log_details->arm_coupon_code;
                            $arm_coupon_on_each_subscriptions = $log_details->arm_coupon_on_each_subscriptions;
                            if(!empty($arm_coupon_on_each_subscriptions))
                            {
                                if(MEMBERSHIP_DEBUG_LOG == true) {
                                    $ARMember->arm_write_response("ARMember COUPON LOG 4 : arm_coupon_apply_to_subscription each recurring true");
                                }
                                if(!empty($arm_coupon_code))
                                {
                                    $arm_user_payment_cycles = $arm_subscription_plan_options['payment_cycles'];
                                    if(MEMBERSHIP_DEBUG_LOG == true) {
                                        $ARMember->arm_write_response("ARMember COUPON LOG 5 : arm_coupon_apply_to_subscription arm_user_payment_cycles".maybe_serialize($arm_user_payment_cycles));
                                    }
                                    if(count($arm_user_payment_cycles)>0)
                                    {
                                        foreach ($arm_user_payment_cycles as $arm_user_payment_cycle_key => $arm_user_payment_cycle_value) {

                                            if(!isset($arm_user_payment_cycle_value['cycle_amount_original']))
                                            {
                                                $arm_user_payment_cycle_amount = $arm_user_payment_cycle_value['cycle_amount'];
                                            }
                                            else {
                                                $arm_user_payment_cycle_amount = $arm_user_payment_cycle_value['cycle_amount_original'];
                                            }

                                            $arm_couponApply_plan = $arm_manage_coupons->arm_apply_coupon_code($arm_coupon_code, $arm_subscription_plan_id, 0, $arm_user_payment_cycle_key);
                                            $arm_coupon_discount_type = isset($arm_couponApply_plan['discount_type']) ? $arm_couponApply_plan['discount_type'] : '';
                                            $arm_coupon_discount = isset($arm_couponApply_plan['discount']) ? $arm_couponApply_plan['discount'] : 0;
                                            if($arm_coupon_discount_type=='percentage')
                                            {
                                                $arm_subscription_plan_amount_couponed = ($arm_user_payment_cycle_amount * $arm_coupon_discount) / 100;
                                                $arm_subscription_plan_amount_couponed = $arm_user_payment_cycle_amount - $arm_subscription_plan_amount_couponed;
                                            }
                                            else {
                                                $arm_subscription_plan_amount_couponed = $arm_user_payment_cycle_amount-$arm_coupon_discount;
                                            }

                                            if($arm_subscription_plan_amount_couponed<0)
                                            {
                                                $arm_subscription_plan_amount_couponed = 0;
                                            }

                                            if(MEMBERSHIP_DEBUG_LOG == true) {
                                                $ARMember->arm_write_response("ARMember COUPON LOG 5.1 : arm_subscription_plan_amount_couponed=".maybe_serialize($arm_subscription_plan_amount_couponed));
                                            }

                                            if($user_subscription_payment_cycle==$arm_user_payment_cycle_key)
                                            {
                                                if(!isset($userPlanData['arm_current_plan_detail']['arm_subscription_plan_amount_original']))
                                                {
                                                    $userPlanData['arm_current_plan_detail']['arm_subscription_plan_amount_original'] = $arm_user_payment_cycle_amount;
                                                    $user_activity = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_activity . "` WHERE `arm_type`=%s AND `arm_user_id`=%d AND `arm_action` = %s AND `arm_item_id`=%d ORDER BY `arm_activity_id` DESC LIMIT 1",'membership',$user_ID,'new_subscription',$arm_subscription_plan_id), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_activity is a table name

                                                    if(!empty($user_activity)) {

                                                    $user_activity_content = maybe_unserialize( $user_activity['arm_content'] );

                                                    if(isset($user_activity['arm_activity_plan_amount'])) {
                                                        $user_activity['arm_activity_plan_amount'] = $arm_subscription_plan_amount_couponed;
                                                    }

                                                    if (isset($user_activity_content['plan_detail']['arm_subscription_plan_amount'])) {
                                                        $user_activity_content['plan_detail']['arm_subscription_plan_amount'] = $arm_subscription_plan_amount_couponed;
                                                    }

                                                    if (isset($user_activity_content['plan_detail']['arm_subscription_plan_options'])) {

                                                        $arm_subscription_plan_options = maybe_unserialize($user_activity_content['plan_detail']['arm_subscription_plan_options']);

                                                        if(isset($arm_subscription_plan_options['payment_cycles'][0]['cycle_amount'])) {

                                                            $arm_subscription_plan_options['payment_cycles'][0]['cycle_amount'] = $arm_subscription_plan_amount_couponed;

                                                            $user_activity_content['plan_detail']['arm_subscription_plan_options'] = maybe_serialize($arm_subscription_plan_options);
                                                        }
                                                    }

                                                    if(!empty($user_activity_content['plan_text'])) {
                                                        $plan_text = $user_activity_content['plan_text'];

                                                        $first_part_ind = strpos($plan_text, 'arm_plan_amount_span');

                                                        $second_part_ind = strrpos($plan_text, '</span>');

                                                        if($first_part_ind !== false && $second_part_ind !== false) {

                                                            $first_part = substr($plan_text, 0, ($first_part_ind + 22));

                                                            $second_part = substr($plan_text, $second_part_ind);

                                                            $user_activity_content['plan_text'] = $first_part . number_format((float)$arm_subscription_plan_amount_couponed, $arm_currency_decimal) . $second_part;
                                                        }
                                                    }

                                                    $user_activity_content = maybe_serialize($user_activity_content);

                                                    $user_activity_id = $user_activity['arm_activity_id'];

                                                    if(!empty($user_activity_id)) {
                                                        $user_activity_update = $wpdb->update($ARMember->tbl_arm_activity, array('arm_content' => $user_activity_content,'arm_activity_plan_amount'=>$arm_subscription_plan_amount_couponed), array('arm_activity_id' => $user_activity_id));
                                                    }
                                                }
                                                }
                                                $userPlanData['arm_current_plan_detail']['arm_subscription_plan_amount'] = $arm_subscription_plan_amount_couponed;
                                            }
                                            
                                            if(!isset($arm_subscription_plan_options['payment_cycles'][$arm_user_payment_cycle_key]['cycle_amount_original']))
                                            {
                                                $arm_subscription_plan_options['payment_cycles'][$arm_user_payment_cycle_key]['cycle_amount_original'] =$arm_subscription_plan_options['payment_cycles'][$arm_user_payment_cycle_key]['cycle_amount'];
                                            }
                                            $arm_subscription_plan_options['payment_cycles'][$arm_user_payment_cycle_key]['cycle_amount'] = $arm_subscription_plan_amount_couponed;
                                        }

                                        $userPlanData['arm_current_plan_detail']['arm_subscription_plan_options'] = maybe_serialize($arm_subscription_plan_options);

                                        if(MEMBERSHIP_DEBUG_LOG == true) {
                                            $ARMember->arm_write_response("ARMember COUPON LOG 6 : arm_coupon_apply_to_subscription userPlanData".maybe_serialize($userPlanData));
                                        }

                                        update_user_meta($user_ID, 'arm_user_plan_'.$arm_subscription_plan_id, $userPlanData);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        function arm_coupon_plan_list_options( $selectedPlans = array(), $allPlans = array() ) {
			global $wp, $wpdb, $ARMemberLite, $arm_subscription_plans;
			$planList = '';

			$planList     .= '                        
					<input type="hidden" id="arm_coupon_modules_plans_selection" name="arm_subscription_coupons[]" class="arm_setup_modules_plans_selection_inp" value="'.implode(',',$selectedPlans).'" required data-msg-required="' . esc_html__( 'Please select atleast one plan.', 'ARMember' ) . '">
					<dl class="arm_multiple_selectbox arm_width_32_pct">
						<dt><span style=""></span><input type="text" style="display: none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
						<dd>
							<ul data-id="arm_coupon_modules_plans_selection" data-placeholder="Select Plans">';

			if ( ! empty( $allPlans ) ) {
				foreach ( $allPlans as $plan ) {
					$planObj = new ARM_Plan( 0 );
					$planObj->init( (object) $plan );
					$plan_id                      = $planObj->ID;
					$plan_options                 = $planObj->options;
					$arm_show_plan_payment_cycles = ( isset( $plan_options['show_payment_cycle'] ) && $plan_options['show_payment_cycle'] == '1' ) ? 1 : 0;

					$planInputAttr = ' data-plan_name="' . esc_attr($planObj->name) . '" data-plan_type="' . esc_attr($planObj->type) . '" data-payment_type="' . esc_attr($planObj->payment_type) . '" data-show_payment_cycle="' . esc_attr($arm_show_plan_payment_cycles) . '" ';

					$planList     .= '
						<li data-label="'.esc_attr($planObj->name).'" data-value="'.esc_attr($plan_id) .'"><input type="checkbox" class="arm_icheckbox plans_chk_inputs plans_chk_inputs_' . esc_attr($planObj->type) . '" id="plan_chk_'.esc_attr($plan_id) .'" value="'.esc_attr($plan_id) .'" '.$planInputAttr.'>'.esc_attr($planObj->name).'</li>';
				}
				$planList     .= '</ul>
					</dd>
				</dl>';
			}
			return $planList;
		}
        function arm_coupon_form_html($c_discount,$c_type,$period_type,$sdate_status,$edit_mode,$c_sdate,$c_edate,$c_allow_trial,$c_allowed_uses,$c_label,$c_coupon_on_each_subscriptions,$coupon_status,$c_subs,$c_data, $arm_coupon_type = 1, $arm_paid_posts = array(),$is_bulk_create=0){

            global $arm_payment_gateways, $arm_subscription_plans, $arm_global_settings,$arm_pay_per_post_feature,$ARMember,$wpdb,$arm_manage_coupons;

            $arm_common_date_format = $arm_global_settings->arm_check_common_date_format(get_option('date_format'));

            $global_currency = $arm_payment_gateways->arm_get_global_currency();
            $arm_coupon_form_html='';
            $c_discount=(isset($c_discount)) ? $c_discount : '';
            $c_post_subs = !empty($arm_paid_posts) ? array_filter($arm_paid_posts) : array();
            $c_subs = (!empty($c_subs)) ? $c_subs : array();
            $paid_post_item_list_container_cls = 'arm_paid_post_items_list_container';
            $paid_post_item_cls = 'arm_paid_post_items';
            $arm_coupon_paid_post_items_input = "arm_coupon_paid_post_items_input";
            $arm_coupon_start_date = "arm_coupon_start_date";
            $arm_coupon_expire_date = "arm_coupon_expire_date";
            if(!empty($is_bulk_create))
            {
                $paid_post_item_list_container_cls = 'arm_blk_paid_post_items_list_container';
                $paid_post_item_cls = 'arm_blk_paid_post_items';
                $arm_coupon_paid_post_items_input = "arm_blk_coupon_paid_post_items_input";
                $arm_coupon_start_date = "arm_blk_coupon_start_date";
                $arm_coupon_expire_date = "arm_blk_coupon_expire_date";
            }

            $period_type_section=($period_type == 'daterange') ? '' : 'hidden_section';
            $arm_rtl_style=(is_rtl()) ? 'margin-left: 10px;' : 'margin-right: 10px;';
            $arm_coupon_form_html .='<div class="'.$paid_post_item_list_container_cls.'" id="'.$paid_post_item_list_container_cls.'"></div>';
            $arm_coupon_form_html .='<tr class="form-field form-required"><td class="arm_height_auto arm_padding_bottom_0 arm_padding_0 arm_discount_form_row">
                <table class="arm_width_100_pct">';
            $arm_coupon_form_html .='<tbody class="arm_display_grid arm_grid_col_3">';
            $arm_coupon_form_html .='<tr class="form-field form-required arm_margin_top_24">
                                        <th class="arm_padding_0 arm_padding_bottom_12"><label class="arm-black-350 arm_font_size_16">'.esc_html__('Discount', 'ARMember').'</label></th>
                                        <td>
                                            <div class="arm_setup_forms_container">
                                                <input type="text" id="arm_coupon_discount" value="'.esc_attr($c_discount).'" onkeypress="return ArmNumberValidation(event, this)" name="arm_coupon_discount" class="arm_coupon_input_fields arm_coupon_discount_input arm_no_paste arm_max_width_60_pct" data-msg-required="'. esc_attr__('Please add discount amount.', 'ARMember').'" required/>
                                                <input type="hidden" id="arm_discount_type" name="arm_discount_type" value="'.esc_attr($c_type).'"/>
                                                <dl class="arm_selectbox arm_coupon_discount_select column_level_dd">
                                                    <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                    <dd>
                                                        <ul data-id="arm_discount_type">
                                                            <li data-label="'.esc_attr__('Fixed', 'ARMember').' ('.$global_currency.')" data-value="fixed">'.esc_html__('Fixed', 'ARMember').' ('.$global_currency.')</li>
                                                            <li data-label="'.esc_attr__('Percentage', 'ARMember').' (%)" data-value="percentage">'.esc_html__('Percentage', 'ARMember').' (%)</li>
                                                        </ul>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </td>
                                    </tr>';
                                    $arm_coupon_form_html .='<tr class="form-field form-required arm_margin_top_24">
                                        <th class="arm_padding_0 arm_padding_bottom_12"><label class="arm-black-350 arm_font_size_16">'.esc_html__('Coupon Label', 'ARMember').'</label></th>
                                        <td valign="middle">
                                            <div class="arm_setup_forms_container">
                                                <input type="text"  id="arm_coupon_label" value="'.(isset($c_label) ? stripslashes_deep($c_label) : '').'" name="arm_coupon_label" class="arm_coupon_input_fields"/>
                                            </div>
                                           
                                        </td>
                                    </tr>';
                                    $arm_coupon_form_html .='<tr class="form-field form-required arm_margin_top_24">
                                        <th  class="arm_padding_0 arm_padding_bottom_12"><label class="arm-black-350 arm_font_size_16">'.esc_html__('No. of time uses allowed', 'ARMember').'</label><i class="arm_helptip_icon armfa armfa-question-circle" title="'.esc_attr__("Leave blank or '0' for unlimited uses.", 'ARMember').'"></i></th>
                                        <td valign="middle  class="arm_padding_right_0"">
                                            <div class="arm_setup_forms_container">
                                                <input type="text" onkeypress="javascript:return isNumber(event)" id="arm_allowed_uses" value="'.(!empty($c_allowed_uses) ? $c_allowed_uses : 0).'" name="arm_allowed_uses" class="arm_coupon_input_fields"/>
                                            </div>
                                        </td>
                                    </tr>';
                                    $arm_coupon_form_html .='</tbody"></table></td></tr>';
                                    if(!empty($arm_pay_per_post_feature->isPayPerPostFeature)){

                                        $arm_membership_plan_chk = ($arm_coupon_type == 1) ? "checked='checked'" : "";
                                        $arm_paid_post_chk = ($arm_coupon_type == 2) ? "checked='checked'" : "";
                                        $arm_both_chk = ($arm_coupon_type == 0) ? "checked='checked'" : "";
                                        $coupon_type_membership_plan_id = "coupon_type_membership_plan";
                                        $coupon_type_paid_post_id = 'coupon_type_paid_post';
                                        $coupon_type_both_id = "coupon_type_both";
                                        if($is_bulk_create)
                                        {
                                            $coupon_type_membership_plan_id = "blk_coupon_type_membership_plan";
                                            $coupon_type_paid_post_id = 'blk_coupon_type_paid_post';
                                            $coupon_type_both_id = "blk_coupon_type_both";
                                        }
                            
                                        $arm_coupon_form_html.= '<tr class="form-field form-required arm_width_100_pct">
                                                                    <th class="arm_padding_top_30"><label class="arm_font_size_16">' . esc_html__('Coupon Type', 'ARMember').'<label></th>
                                                                    <td class="arm_padding_bottom_0 arm_padding_top_12">
                                                                        <div class="arm_coupon_period_box">
                                                                            <span class="arm_sel_coupon_types_container" id="arm_sel_coupon_types_container">
                                                                                <input type="radio" class="arm_iradio" '.$arm_membership_plan_chk.' value="1" name="arm_coupon_type" id="'.$coupon_type_membership_plan_id.'" ><label for="'.$coupon_type_membership_plan_id.'" class="arm_padding_right_46 arm_margin_top_0">'.esc_html__('Membership Plan', 'ARMember').'</label>
                            
                                                                                <input type="radio" class="arm_iradio" '.$arm_paid_post_chk.' value="2" name="arm_coupon_type" id="'.$coupon_type_paid_post_id.'"><label for="'.$coupon_type_paid_post_id.'" class="arm_padding_right_46 arm_margin_top_0">'.esc_html__('Paid Post', 'ARMember').'</label>
                            
                                                                                <input type="radio" class="arm_iradio " '.$arm_both_chk.' value="0" name="arm_coupon_type" id="'.$coupon_type_both_id.'" ><label for="'.$coupon_type_both_id.'">'.esc_html__('Both', 'ARMember').'</label>
                                                                            </span>
                                                                            <div class="armclear"></div>
                                                                        </div> 
                                                                    </td>
                                                                </tr>';
                                        }
                            
                                        $arm_display_membership_plan_class = "";
                                        if(!empty($arm_pay_per_post_feature->isPayPerPostFeature)){   
                                            $arm_display_membership_plan_class = ($arm_coupon_type == 2) ? " hidden_section" : '';
                                        }
                            
                                        $arm_coupon_form_html .='<tr class="form-field form-required arm_width_100_pct coupon_type_membership_plan arm_d_flex '.$arm_display_membership_plan_class.' arm_margin_top_24">
                                                                    <th class="arm_padding_top_0"><label class="arm-black-350 arm_font_size_16">'.esc_html__('Select Membership Plan', 'ARMember').'</label></th>
                                                                    <td class="arm_height_auto arm_padding_bottom_0 arm_padding_top_12">
                                                                       ';
                                                                                
                                                                       $allPlans = $arm_subscription_plans->arm_get_all_active_subscription_plans();

                                                                       $arm_coupon_form_html .='<div class="arm_setup_module_box">
                                                                            <div class="arm_setup_plan_options_list arm_margin_auto">'.$arm_manage_coupons->arm_coupon_plan_list_options( array(), $allPlans )
                                                                            .'
                                                                            <span class="arm_coupon_blank_field_warning  arm_margin_top_12">Leave blank for apply coupon to all plan(s)</span>
                                                                            </div>
                                                                            
                                                                            <span class="arm_setup_error_msg"></span>
                                                                        </div>
                                                                        
                                                                    </td>
                                                                </tr>';
                        if(!empty($arm_pay_per_post_feature->isPayPerPostFeature)){   
                        $arm_display_paid_post_class = ($arm_coupon_type == 1) ? " hidden_section" : '';
            
                        $arm_coupon_form_html.= '<tr class="form-field form-required arm_width_100_pct coupon_type_paid_post arm_d_flex'.$arm_display_paid_post_class.' arm_margin_top_24">
                                                    <th class="arm_padding_top_0"><label>' . esc_html__('Paid Posts', 'ARMember').'<label></th>
                                                    <td class="arm_height_auto arm_padding_top_12 arm_padding_bottom_0">
                                                        <div class="arm_setup_forms_container">
                                                            <div class="arm_text_align_center arm_width_100_pct" ><img src="'.MEMBERSHIP_IMAGES_URL.'/arm_loader.gif" id="arm_loader_img_paid_post_items" class="arm_loader_img_paid_post_items" style="display: none;" width="20" height="20" /></div>
            
                                                            <input id="'.$arm_coupon_paid_post_items_input.'" type="text" value="" placeholder="'. esc_attr__( 'Search by paid post title...', 'ARMember').'" />
                                                            <span class="arm_coupon_blank_field_warning arm_margin_top_10">'.esc_html__('Leave blank for apply coupon to all paid post(s)', 'ARMember').'</span>
                                                            <div class="'.$paid_post_item_cls.'" id="'.$paid_post_item_cls.'" style="'.(empty($c_post_subs) ? 'display:none' : '').'">';
            
                                                        if( !empty( $c_post_subs) ) {
                                                            $arm_plan_name ='';
                                                            foreach ($c_post_subs as $key => $arm_paid_post_id_val) {       
                                        $arm_subscription_plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($arm_paid_post_id_val);
            
                                                                $arm_coupon_form_html .= '<div class="arm_paid_post_itembox arm_paid_post_itembox_'.esc_attr($arm_paid_post_id_val).'">';
                                                                $arm_coupon_form_html .='<input type="hidden" name="arm_paid_post_item_id['.esc_attr($arm_paid_post_id_val).']" value="'.esc_attr($arm_paid_post_id_val).'" />';
                                                                $arm_coupon_form_html .='<label style="color:#FFF">'.$arm_subscription_plan_name.'<span class="arm_remove_selected_itembox">x</span></label>';
                                                                $arm_coupon_form_html .='</div>';
                                                            }
                                                        }
                            $arm_coupon_form_html .=  '</div>
                                                    </div>
                                                    </td>
                                                </tr>';
                        }
            
            
                        $c_allow_trial_chk=($c_allow_trial=='1')?"checked='checked'":"";          
                        $arm_coupon_allow_trial = 'arm_coupon_allow_trial';
                        if($is_bulk_create){
                            $arm_coupon_allow_trial = 'arm_blk_coupon_allow_trial';
                        }
                        $arm_coupon_form_html .='<tr class="form-field form-required">
                                                    <td valign="middle" class="arm_padding_bottom_0 arm_width_100_pct arm_padding_top_24">
                                                        <div class="armswitch armswitchbig arm_display_flex arm_margin_left_0" bis_skin_checked="1">
                                                            <input type="checkbox" value="1" class="armswitch_input" name="arm_coupon_allow_trial" id="'.$arm_coupon_allow_trial.'"><label for="'.$arm_coupon_allow_trial.'" class="armswitch_label arm_margin_left_0" '.$c_allow_trial_chk.'></label>
                                                            <label class="arm_margin_left_12 arm_font_size_16 arm-black-600" for="'.$arm_coupon_allow_trial.'">'.esc_html__('Allow this coupon with trial period amount', 'ARMember').'</label>
                                                        </div>
                                                    </td>
                                                </tr>';
                                                
                                            $arm_coupon_form_html .='<tr class="form-field form-required arm_width_100_pct">
                                                <td class="arm_padding_0 arm_margin_top_24">
                                                <div class="arm_form_header_label arm_padding_left_0 arm_padding_0" bis_skin_checked="1">'.esc_html__('Validity','ARMember').'</div>                                              
                                                </td>
                                            </tr>';
            $daterange_chk=($period_type=='daterange')? "checked='checked'" :"";
            $unlimited_chk=($period_type=='unlimited')? "checked='checked'" :"";
            $period_type_dt = "period_type_daterange";
            $period_type_un = "period_type_unlimited";
            if($is_bulk_create == 1)
            {
                $period_type_dt = "blk_period_type_daterange";
                $period_type_un = "blk_period_type_unlimited";
            }
            $arm_coupon_form_html .='<tr class="form-field form-required arm_width_100_pct">
                                        <th class="arm_padding_top_16"><label class="arm_font_size_16">'.esc_html__('Period Type', 'ARMember').'</label></th>
                                        <td class="arm_padding_bottom_0 arm_margin_bottom_0 arm_padding_top_20 arm_height_auto">
                                            <div class="arm_coupon_period_box">
                                                <span class="arm_period_types_container" id="arm_period_types_container">
                                                    <input type="radio" class="arm_iradio" '.$daterange_chk.' value="daterange" name="arm_coupon_period_type" id="'.$period_type_dt.'" ><label for="'.$period_type_dt.'" class="arm_padding_right_46 arm_margin_top_0">'.esc_html__('Date Range', 'ARMember').'</label>
                                                    <input type="radio" class="arm_iradio arm_margin_top_0" '.$unlimited_chk.' value="unlimited" name="arm_coupon_period_type" id="'.$period_type_un.'" ><label for="'.$period_type_un.'">'.esc_html__('Unlimited', 'ARMember').'</label>
                                                </span>
                                                <div class="armclear"></div>
                                            </div> 
                                        </td>
                                    </tr>';
            
                                    $arm_coupon_form_html .='<tr class="form-field form-required"><td class="arm_height_auto arm_padding_0">
                <div class="arm_width_100_pct">';
            $arm_coupon_form_html .='<div class="arm_display_grid arm_grid_col_3">';

                $arm_coupon_form_html .='<div class="arm_form_field_block coupon_period_options'.$period_type_section.' arm_margin_top_32">
                                        <label class="arm_font_size_16 arm_padding_bottom_12">'.esc_html__('Start Date', 'ARMember').'</label>
                                        <div class="arm_position_relative">
                                            <div class="arm_setup_forms_container">
                                                <input type="text" id="'.$arm_coupon_start_date.'" '.esc_attr($sdate_status).' value="'.(!empty($c_sdate) ? esc_attr(date($arm_common_date_format, strtotime($c_sdate))) : '').'" name="arm_coupon_start_date" data-date_format="'.esc_attr($arm_common_date_format).'" class="arm_coupon_input_fields '.(!empty($sdate_status) ? '' : 'arm_datepicker_coupon' ).'" data-msg-required="'.esc_attr__('Please select start date.', 'ARMember').'" required />';
                                                if ($edit_mode == TRUE && $sdate_status != '') {
                                                    $arm_coupon_form_html .='<i class="arm_helptip_icon armfa armfa-question-circle" title="'.esc_attr__("Date Can't Be Changed, Because coupon usage has been started.", 'ARMember').'"></i>';
                                                }
                $arm_coupon_form_html .='   </div>
                                        </div>
                                    </div>';

            $edit_mode=($edit_mode) ? '1' : '0';
            $arm_coupon_form_html .='<div class="arm_form_field_block coupon_period_options'.esc_attr($period_type_section).' arm_margin_top_32">
                                        <label class="arm_font_size_16 arm_padding_bottom_12">'.esc_html__('Expire Date', 'ARMember').'</label>
                                        <div class="arm_position_relative">
                                            <div class="arm_setup_forms_container">
                                                <input type="text" id="'.$arm_coupon_expire_date.'" value="'.(!empty($c_edate) ? date($arm_common_date_format, strtotime($c_edate)) : '').'" name="'.$arm_coupon_expire_date.'" data-date_format="'.esc_attr($arm_common_date_format).'" class="arm_coupon_input_fields arm_datepicker_coupon" data-editmode="'.esc_attr($edit_mode).'" data-msg-required="'.esc_attr__('Please select expire date.', 'ARMember').'" data-armgreaterthan-msg="'.esc_attr__('Expire date can not be earlier than start date', 'ARMember').'" required />
                                            </div>
                                        </div>
                                    </div>';
            $arm_coupon_form_html .='</div></div></div></div>';

            
            
            
            $c_coupon_on_each_subscriptions_chk=($c_coupon_on_each_subscriptions=='1')?"checked='checked'":"";
            $arm_blk_coupon_on_each_subscriptions = 'arm_coupon_on_each_subscriptions';
            if($is_bulk_create){
                $arm_blk_coupon_on_each_subscriptions = 'arm_blk_coupon_on_each_subscriptions';
            }
            $arm_coupon_form_html .='<tr class="form-field arm_width_100_pct">
                                        <td valign="middle" class="arm_padding_0 arm_margin_top_22 arm_margin_bottom_0">
                                            <div class="armswitch armswitchbig arm_display_flex arm_margin_left_0" bis_skin_checked="1">
                                                <input type="checkbox" value="1" class="armswitch_input" name="arm_coupon_on_each_subscriptions" id="'.$arm_blk_coupon_on_each_subscriptions.'" wfd-id="id448"><label for="'.$arm_blk_coupon_on_each_subscriptions.'" class="armswitch_label arm_margin_left_0" '.$c_coupon_on_each_subscriptions_chk.'></label>
                                                <label class="arm_margin_left_12 arm_font_size_16 arm-black-600" for="'.$arm_blk_coupon_on_each_subscriptions.'">'.esc_html__('For Recurring Plan Apply to Entire Duration', 'ARMember').'</label>
                                            </div>
                                        </td>
                                    </tr>
                                    <input type="hidden" name="arm_coupon_status" value="'.esc_attr($coupon_status).'"/>';
            
            $arm_coupon_form_html = apply_filters('arm_add_field_after_coupon_form',$arm_coupon_form_html,$c_data);

            return $arm_coupon_form_html;
        }
        function arm_bulk_generate_code($length,$type)
        {   $return_code='';
            global $wpdb, $ARMember, $arm_slugs, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], '1',1);
            $arm_code = $this->arm_bulk_generate_coupon_code($length,$type);
            $old_coupon =  $this->arm_get_coupon($arm_code);
            if (!empty($old_coupon) && is_array($old_coupon)) {
                $this->arm_bulk_generate_code();
            } else {
                $return_code=$arm_code;
            }
            return $return_code;
        }
        function arm_bulk_generate_coupon_code($length,$type)
        {   
            
            $couponCode = '';
            $coupon_char = array();
            if($type=='alphanumeric'){
                $length_second=$length*40/100;
                $length_first=$length-$length_second;
                $coupon_char[] = array('count' => $length_first, 'char' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
                $coupon_char[] = array('count' => $length_second, 'char' => '0123456789');
            }else if($type=='alphabetical'){
                $coupon_char[] = array('count' => $length, 'char' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
            }else if($type=='numeric'){
                $coupon_char[] = array('count' => $length, 'char' => '0123456789');
            }    
            
            $temp_array = array();
            foreach ($coupon_char as $char_set) {
                for ($i = 0; $i < $char_set['count']; $i++) {
                    $temp_array[] = $char_set['char'][rand(0, strlen($char_set['char']) - 1)];
                }
            }
            shuffle($temp_array);
            $couponCode = implode('', $temp_array);
            
            return $couponCode;
        }
    
       function arm_save_coupon_in_usermeta($user_id=0, $coupon='', $plan_id=0) {
            if( 0 != $user_id && '' != $coupon ) {
                update_user_meta($user_id, 'arm_used_invite_coupon_'.$plan_id , $coupon);
            }
        }

        function arm_get_coupon_members($coupon_id = 0) {
            global $wp, $wpdb, $arm_slugs, $ARMember, $arm_global_settings, $arm_payment_gateways, $arm_transaction;
            $couponMembers = array();
            
            $couponData = $this->arm_get_coupon_by_id($coupon_id);
            if (!empty($couponData)) {
                $nowTime = strtotime(current_time('mysql'));
                $coupon_id = $couponData['arm_coupon_id'];
                $coupon_code = $couponData['arm_coupon_code'];
                $discount_type = $couponData['arm_coupon_discount_type'];
                $rule_type = $couponData['arm_coupon_period_type'];
                

                $rule_post_data = array();
                if (!empty($post_id)) {
                    $rule_post_data = get_post($post_id);
                }

                $rule_post_date = '';

                if (!empty($rule_post_data)) {

                    $rule_post_date = isset($rule_post_data->post_date) ? $rule_post_data->post_date : '';
                    $rule_post_modify_date = isset($rule_post_data->post_modified) ? $rule_post_data->post_modified : '';
                }

                if (!empty($coupon_code)) {

                    $log_data = $wpdb->get_results($wpdb->prepare("SELECT arm_user_id,arm_plan_id,arm_coupon_code FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_coupon_code`=%s", $coupon_code), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name

                    $Members_arr = array();
                    $arm_used_invite_coupon_already_added = "";
                    if(!empty($log_data)) {
                        foreach ($log_data as $log_data_val) {
                            if($coupon_code==$log_data_val['arm_coupon_code'])
                            {
                                $Member = array();
                                $arm_used_invite_coupon_already_added .= $wpdb->prepare(" AND `meta_key` NOT LIKE %s","arm_used_invite_coupon_".$log_data_val['arm_plan_id']);

                                $user_info = get_userdata($log_data_val['arm_user_id']);
                                if(!empty($user_info)) {
                                    $view_link = admin_url('admin.php?page=' . $arm_slugs->manage_members . '&action=view_member&id=' . $user_info->data->ID);
                                    $Member['user_id'] = $user_info->data->ID;
                                    $Member['user_login'] = $user_info->data->user_login;
                                    $Member['user_email'] = $user_info->data->user_email;
                                    $Member['coupon_id'] = $coupon_id;
                                    $Member['coupon_code'] = $coupon_code;
                                    $Member['view_detail'] = htmlentities("<center><a class='arm_openpreview arm_openpreview_popup' href='javascript:void(0)' data-id=".$user_info->data->ID." data-arm_hide_edit='1' data-arm_popup_opened='1'>" . esc_html__('View Detail', 'ARMember') . "</a></center>");
                                    array_push($Members_arr, $Member);
                                }
                            }
                        }
                    }

                    $log_data2 = $wpdb->get_results($wpdb->prepare("SELECT user_id FROM `" . $wpdb->usermeta . "` WHERE `meta_key` LIKE %s AND `meta_value` = %s ".$arm_used_invite_coupon_already_added,'arm_used_invite_coupon_%', $coupon_code), ARRAY_A); //phpcs:ignore --Reason $wpdb->usermeta is a table name

                    if(!empty($log_data2)) {
                        foreach ($log_data2 as $meta_value) {
                            if($coupon_code==$meta_value['meta_value'])
                            {
                                $Member = array();
                                $user_info = get_userdata($meta_value['user_id']);
                                if(!empty($user_info)) {
                                    $view_link = admin_url('admin.php?page=' . $arm_slugs->manage_members . '&action=view_member&id=' . $user_info->data->ID);
                                    $Member['user_id'] = $user_info->data->ID;
                                    $Member['user_login'] = $user_info->data->user_login;
                                    $Member['user_email'] = $user_info->data->user_email;
                                    $Member['coupon_id'] = $coupon_id;
                                    $Member['coupon_code'] = $coupon_code;
                                    $Member['view_detail'] = htmlentities("<center><a class='arm_openpreview' href='{$view_link}' data-arm_hide_edit='1'>" . esc_html__('View Detail', 'ARMember') . "</a></center>");
                                    array_push($Members_arr, $Member);
                                }
                            }
                        }
                    }

                    $couponMembers = $Members_arr;

                }
            }


            return $couponMembers;
        }

        function arm_get_coupon_members_data_func() {
            global $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $couponID = isset($_REQUEST['coupon_id']) ? intval($_REQUEST['coupon_id']) : 0;
            $response = array('status' => 'error', 'data' => array());
            if(0 != $couponID) {
                $membersDatasDefault = array();
                $response['status'] = "success";
                $response['data'] = $membersDatasDefault;

                global $arm_manage_coupons;
                $couponMembers = array();
                $couponAllowMembers = $arm_manage_coupons->arm_get_coupon_members($couponID);
                $couponRulesMembers[$couponID] = $couponAllowMembers;
                if(!empty($couponRulesMembers)) {
                    foreach($couponRulesMembers as $couponID => $members) {
                        if (!empty($members)) {
                            $membersData = array();
                            foreach($members as $mData){
                              
                                $membersDatas = array();
                                
                                $membersDatas['username'] = $mData['user_login'];
                                $membersDatas['user_email'] = $mData['user_email'];
                                $membersDatas['coupon_code'] = $mData['coupon_code'];
                                $membersDatas['view_detail'] = html_entity_decode($mData['view_detail']);
                                $membersData[] = array_values($membersDatas); 
                            }
                            $response['status'] = "success";
                            $response['data'] = $membersData;
                        }
                    }
                }


            }
            echo arm_pattern_json_encode($response);
            die;
        }
        function arm_get_paid_post_item_coupon_options() {

            global $wpdb, $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_coupons'], '1',1); //phpcs:ignore --Reason:Verifying nonce

            $search_key = isset( $_POST['search_key'] ) ? sanitize_text_field($_POST['search_key']) : '';//phpcs:ignore

            if( $search_key != '' ){
                $postQuery = $wpdb->get_results( $wpdb->prepare( "SELECT p.arm_subscription_plan_id, p.arm_subscription_plan_name,p.arm_subscription_plan_post_id FROM {$ARMember->tbl_arm_subscription_plans} p  WHERE p.arm_subscription_plan_post_id != %d AND p.arm_subscription_plan_is_delete = %d AND p.arm_subscription_plan_name LIKE %s LIMIT 0,10",0,0,'%' . $wpdb->esc_like( $search_key ) . '%') ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
            } else {
                $postQuery = $wpdb->get_results( $wpdb->prepare( "SELECT p.arm_subscription_plan_id, p.arm_subscription_plan_name,p.arm_subscription_plan_post_id FROM {$ARMember->tbl_arm_subscription_plans} p  WHERE p.arm_subscription_plan_post_id != %d AND p.arm_subscription_plan_is_delete = %d LIMIT 0,10",0,0) );//phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
            }

            $ppData = array();
            if( isset( $postQuery ) && !empty( $postQuery ) ){
                foreach( $postQuery as $k => $postData ){
                    $isEnablePaidPost = get_post_meta( $postData->arm_subscription_plan_post_id, 'arm_is_paid_post', true );
                    if( !empty($isEnablePaidPost) ){
                        $ppData[] = array(
                            'id' => $postData->arm_subscription_plan_id,
                            'value' => $postData->arm_subscription_plan_name,
                            'label' => $postData->arm_subscription_plan_name
                        );
                    }
                }
            }

            $response = array('status' => 'success', 'data' => $ppData);
            echo arm_pattern_json_encode($response);
            die;

        }
	
    }

    
}
global $arm_manage_coupons;
$arm_manage_coupons = new ARM_manage_coupons();