<?php

if (!class_exists('ARM_crons')) {

    class ARM_crons {

        function __construct() {
            global $wpdb, $ARMember, $arm_slugs;
            add_filter('cron_schedules', array($this, 'arm_add_cron_schedules'));
            //add_action('init', array($this, 'arm_add_crons'), 10);

            add_action('arm_handle_change_user_plan', array($this, 'arm_handle_change_user_plan_func'));
            add_action('arm_handle_expire_subscription', array($this, 'arm_handle_expire_subscription_func'));
            add_action('arm_handle_failed_payment_for_subscription_plan', array($this, 'arm_handle_failed_payment_for_subscription_plan_func'));
            
            /* For checking if recurring payment response is not arrived in the system OR 
             * For checking grace period is completed for failed payment
             */
            add_action('arm_handle_expire_infinite_subscription', array($this, 'arm_handle_expire_infinite_subscription_func'));
            add_action('arm_handle_before_expire_subscription', array($this, 'arm_handle_before_expire_subscription_func'));
            add_action('arm_handle_before_dripped_content_available', array($this, 'arm_handle_before_dripped_content_available_func'));
            add_action('arm_handle_trial_finished', array($this, 'arm_handle_trial_finished_func'));
            add_action('arm_update_user_achievements', array($this, 'arm_update_user_achievements_func'));
            add_action('arm_handle_renewal_reminder_of_subscription', array($this, 'arm_handle_renewal_reminder_of_subscription_func'));
            add_action('arm_handle_renewal_reminder_of_subscription', array($this, 'arm_handle_renewal_reminder_of_automatic_subscription_func'));
            
            add_action('arm_handle_failed_login_log_data_delete', array($this, 'arm_handle_failed_login_log_data_delete_func'));
            
            add_action('armember_send_anonymous_data_cron', array($this, 'armember_send_anonymous_data_cron_func'));
        }

        function arm_handle_failed_login_log_data_delete_func()
        {
            global $wpdb, $ARMember, $arm_global_settings;
            if(!empty($arm_global_settings->block_settings['failed_login_lockdown']))
            {
                $arm_tbl_arm_failed_login_logs = $ARMember->tbl_arm_fail_attempts;
                $arm_delete_start_date = date('Y-m-d', strtotime('-30 days'));
                $arm_delete_faild_login_log_data = $wpdb->query($wpdb->prepare("DELETE FROM `{$arm_tbl_arm_failed_login_logs}` WHERE `arm_fail_attempts_datetime` <= %s", $arm_delete_start_date)); //phpcs:ignore --Reason $arm_tbl_arm_failed_login_logs is a table 
            }

            if(!empty($arm_global_settings->block_settings['track_login_history']))
            {
                $arm_tbl_arm_login_history = $ARMember->tbl_arm_login_history;
                $arm_delete_start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));

                $arm_delete_login_history_data = $wpdb->query($wpdb->prepare("DELETE FROM `{$arm_tbl_arm_login_history}` WHERE `arm_logout_date` <= %s AND arm_history_id NOT IN ( SELECT arm_history_id_max_id FROM ( SELECT max(arm_history_id) as arm_history_id_max_id FROM `{$arm_tbl_arm_login_history}` Group BY arm_user_id ORDER BY arm_history_id DESC ) arm_history_ids )", $arm_delete_start_date)); //phpcs:ignore --Reason $arm_tbl_arm_login_history is a table name
            }
        }

        function arm_add_cron_schedules($schedules) {
            if (!is_array($schedules)) {
                $schedules = array();
            }
            for ($i = 2; $i < 24; $i++) {
                if ($i == 12) {
                    continue;
                }
                $display_label = esc_html__('Every', 'ARMember') . ' ' . $i . ' ' . esc_html__('Hour', 'ARMember');
                $schedules['every' . $i . 'hour'] = array('interval' => HOUR_IN_SECONDS * $i, 'display' => $display_label);
            }
            return apply_filters('arm_add_cron_schedules', $schedules);
        }

        function arm_add_crons() {
            global $wpdb, $ARMember, $arm_slugs, $arm_cron_hooks_interval, $arm_global_settings;
            wp_get_schedules();
            $all_global_settings = $arm_global_settings->arm_get_all_global_settings();
            $general_settings = $all_global_settings['general_settings'];
            $cron_schedules_time = isset($general_settings['arm_email_schedular_time']) ? $general_settings['arm_email_schedular_time'] : 12;
            $interval = 'twicedaily';
            if ($cron_schedules_time == 24) {
                $interval = 'daily';
            } else if ($cron_schedules_time == 12) {
                $interval = 'twicedaily';
            } else if ($cron_schedules_time == 1) {
                $interval = 'hourly';
            } else {
                $interval = 'every' . $cron_schedules_time . 'hour';
            }
            $cron_hooks = $this->arm_get_cron_hook_names();
            
            
            
            foreach ($cron_hooks as $hook) {
                if (!wp_next_scheduled($hook)) {
                    wp_schedule_event(time(), $interval, $hook);
                }
            }
            do_action('arm_membership_addon_crons', $interval);
        }

        function arm_handle_expire_subscription_func() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_manage_communication, $arm_members_class;
            set_time_limit(0); /* Preventing timeout issue. */
            $now = current_time('timestamp');
            $start_time = strtotime("-12 Hours", $now);
            $end_time = strtotime("+30 Minutes", $now);
            $cron_msgs = array();
            /**
             * For Expire Subscription on Today Process
             */
            $args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => '',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    ),
                )
            );
            $expireUsers = get_users($args);


            if (!empty($expireUsers)) {
                foreach ($expireUsers as $usr) {
                    $user_id = $usr->ID;
                    $plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true);
                    $plan_ids = !empty($plan_ids) ? $plan_ids : array();
                    if (!empty($plan_ids) && is_array($plan_ids)) {
                        foreach ($plan_ids as $plan_id) {
                            $planData = get_user_meta($user_id, 'arm_user_plan_' . $plan_id, true);
                            if (!empty($planData)) {
                                $expireTime = isset($planData['arm_expire_plan']) ? $planData['arm_expire_plan'] : '';
                                $is_plan_cancelled = isset($planData['arm_cencelled_plan']) ? $planData['arm_cencelled_plan'] : '';
                                $planDetail = isset($planData['arm_current_plan_detail']) ? $planData['arm_current_plan_detail'] : '';

                                if (!empty($planDetail)) {
                                    $plan = new ARM_Plan(0);
                                    $plan->init((object) $planDetail);
                                } else {
                                    $plan = new ARM_Plan($plan_id);
                                }

                                if (!empty($expireTime)) {
                                    if ($expireTime <= $end_time) {
                                        $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($plan_id);

                                        /* Cancel Subscription on expiration */
                                        if (isset($is_plan_cancelled) && $is_plan_cancelled == 'yes') {
                                            if ($plan->exists()) {
                                                $cancel_plan_action = isset($plan->options['cancel_plan_action']) ? $plan->options['cancel_plan_action'] : 'immediate';
                                                if ($cancel_plan_action == 'on_expire') {
                                                    if ($plan->is_paid() && !$plan->is_lifetime() && $plan->is_recurring()) {

                                                        do_action('arm_cancel_subscription_gateway_action', $user_id, $plan_id);
                                                        $arm_subscription_plans->arm_add_membership_history($usr->ID, $plan_id, 'cancel_subscription');
                                                        do_action('arm_cancel_subscription', $usr->ID, $plan_id);
                                                        $arm_subscription_plans->arm_clear_user_plan_detail($usr->ID, $plan_id);

                                                        $cancel_plan_act = isset($plan->options['cancel_action']) ? $plan->options['cancel_action'] : 'block';
                                                        if ($arm_subscription_plans->isPlanExist($cancel_plan_act)) {

                                                            do_action('arm_general_log_entry', 'cron', 'subscription user plan assigned by system', 'armember', 'userid='.$usr->ID.', planid='.$plan_id.', plan='.$plan_name.', cancel action='.$cancel_plan_act);

                                                            $arm_members_class->arm_new_plan_assigned_by_system($cancel_plan_act, $plan_id, $usr->ID);
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        /* Send Notification Mail */
                                        $alreadysentmsgs = $planData['arm_sent_msgs'];
                                        $alreadysentmsgs = (!empty($alreadysentmsgs)) ? $alreadysentmsgs : array();
                                        $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $usr->ID, 'action' => 'eot'));

                                        do_action('arm_general_log_entry', 'cron', 'subscription user plan expired by system', 'armember', 'userid='.$usr->ID.', planid='.$plan_id.', plan='.$plan_name.', cancel action=eot');
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($cron_msgs)) {
                do_action('arm_cron_expire_subscription', $cron_msgs);
            }
        }

        function arm_handle_expire_infinite_subscription_func() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_manage_communication, $arm_members_class,$arm_subscription_cancel_msg;           
            set_time_limit(0); /* Preventing timeout issue. */
            $now = current_time('timestamp');
            $start_time = strtotime("-12 Hours", $now);
            $end_time = strtotime("+30 Minutes", $now);
            $cron_msgs = array();
            /**
             * For Expire infinite Subscription on Today Process
             */
            $args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => '',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    ),
                )
            );
            $expireUsers = get_users($args);

            if (!empty($expireUsers)) {
                foreach ($expireUsers as $usr) {
                    $user_id = $usr->ID;
                    $plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true);
                    $plan_ids = !empty($plan_ids) ? $plan_ids : array();
                    if (!empty($plan_ids) && is_array($plan_ids)) {
                        foreach ($plan_ids as $plan_id) {
                            $planData = get_user_meta($user_id, 'arm_user_plan_' . $plan_id, true);
                            if (!empty($planData)) {
                                $expireTime = isset($planData['arm_next_due_payment']) ? $planData['arm_next_due_payment'] : '';
                                $is_plan_cancelled = isset($planData['arm_cencelled_plan']) ? $planData['arm_cencelled_plan'] : '';
                                $planDetail = isset($planData['arm_current_plan_detail']) ? $planData['arm_current_plan_detail'] : '';
                                if (!empty($planDetail)) { 
                                    $plan = new ARM_Plan(0);
                                    $plan->init((object) $planDetail);
                                } else {
                                    $plan = new ARM_Plan($plan_id);
                                }
                                
                                
                                if (!empty($expireTime) && isset($is_plan_cancelled) && $is_plan_cancelled == 'yes') {
                                    if ($expireTime <= $now) {
                                        $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($plan_id);
                                        /* Cancel Subscription on expiration for infinite  */
                                        $plan_cycle = isset($planData['arm_payment_cycle']) ? $planData['arm_payment_cycle'] : '';
                                        $paly_cycle_data = $plan->prepare_recurring_data($plan_cycle);

                                        if($plan->is_recurring() && $paly_cycle_data['rec_time'] == 'infinite') {
                                            if ($plan->exists()) {
                                                $cancel_plan_action = isset($plan->options['cancel_plan_action']) ? $plan->options['cancel_plan_action'] : 'immediate';
                                                if ($cancel_plan_action == 'on_expire') {
                                                    if ($plan->is_paid() && !$plan->is_lifetime() && $plan->is_recurring()) {
                                                        //Update Last Subscriptions Log Detail
                                                        do_action('arm_cancel_subscription_gateway_action', $user_id, $plan_id);

                                                        if (!empty($arm_subscription_cancel_msg)) {
                                                            // Gateway cancel subscription API failed - log error and DON'T remove plan
                                                            do_action('arm_general_log_entry', 'cron', 'Subscription canceled failed from API', 'armember', "Subscription cancellation failed for user $user_id, plan $plan_id: " . $arm_subscription_cancel_msg);
                                                            continue;
                                                        }

                                                        $arm_subscription_plans->arm_add_membership_history($usr->ID, $plan_id, 'cancel_subscription');
                                                        do_action('arm_cancel_subscription', $usr->ID, $plan_id);
                                                        $arm_subscription_plans->arm_clear_user_plan_detail($usr->ID, $plan_id);

                                                        do_action('arm_general_log_entry', 'cron', 'expired infinite subscription user plan', 'armember', 
                                                            'userid='.$user_id.', planid='.$plan_id.', plan='.$plan_name.', expireTime='.$expireTime.', cancelled='.$is_plan_cancelled.', cancel action='.$cancel_plan_action.', recurringtime='.$paly_cycle_data['rec_time']);

                                                        $cancel_plan_act = isset($plan->options['cancel_action']) ? $plan->options['cancel_action'] : 'block';
                                                        if ($arm_subscription_plans->isPlanExist($cancel_plan_act)) {
                                                            $arm_members_class->arm_new_plan_assigned_by_system($cancel_plan_act, $plan_id, $usr->ID);
                                                        } else {
                                                        }
                                                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                                    }
                                                }
                                            }
                                            $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'eot'));

                                            do_action('arm_scheduler_user_plan_eot_outside', $user_id, $plan_id );

                                            do_action('arm_general_log_entry', 'cron', 'recurring subscription user plan expired by system', 'armember', 'userid='.$usr->ID.', planid='.$plan_id.', plan='.$plan_name.', action=eot');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        function arm_handle_failed_payment_for_subscription_plan_func() 
        {
            /* Checked */
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_manage_communication, $arm_members_class;
            set_time_limit(0); /* Preventing timeout issue. */
            $now = current_time('timestamp');

            $cron_msgs = array();
            /**
             * For Expire Subscription on Today Process
             */
            $args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => '',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    ),
                )
            );
            $expireUsers = get_users($args);

            if (!empty($expireUsers)) {
                foreach ($expireUsers as $usr) {

                    $user_id = $usr->ID;
                    $plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true);
                    $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                    if(!empty($plan_ids) && is_array($plan_ids)) {
                        foreach($plan_ids as $plan_id) {
                            $planData = get_user_meta($user_id, 'arm_user_plan_'  .$plan_id, true);
                            $planData = !empty($planData) ? $planData : array();
                            $planData = shortcode_atts($defaultPlanData, $planData);
                            $is_plan_cancelled = isset( $planData['arm_cencelled_plan'] ) ? $planData['arm_cencelled_plan'] : 0;
                            if(!empty($planData) && empty($is_plan_cancelled))
                            {
                                $planDetail = $planData['arm_current_plan_detail'];
                                if (!empty($planDetail)) {
                                    $plan = new ARM_Plan(0);
                                    $plan->init((object) $planDetail);
                                } else {
                                    $plan = new ARM_Plan($plan_id);
                                }   

                                $payment_mode = $planData['arm_payment_mode'];
                                if ($plan->is_recurring() && $payment_mode == 'manual_subscription') 
                                {
                                    $expireTime = $planData['arm_next_due_payment'];
                                    $arm_payment_cycle = $planData['arm_payment_cycle'];
                                    $recurring_data = $plan->prepare_recurring_data($arm_payment_cycle);
                                    $recurring_time = $recurring_data['rec_time']; 
                                    $completed = $planData['arm_completed_recurring'];   

                                    if($recurring_time != $completed || 'infinite'==$recurring_time)
                                    {
                                        if (!empty($expireTime)) 
                                        {
                                            $arm_next_due_date = strtotime("+24 Hours", $expireTime);
                                            if ($arm_next_due_date <= $now) 
                                            {
                                                $suspended_plan_ids = get_user_meta($usr->ID, 'arm_user_suspended_plan_ids', true);
                                                $suspended_plan_id = (!empty($suspended_plan_ids) && is_array($suspended_plan_ids)) ? $suspended_plan_ids :  array();                                                    
                                                if(!in_array($plan_id, $suspended_plan_id))
                                                {
                                                    /* Send Notification Mail */
                                                    $alreadysentmsgs = $planData['arm_sent_msgs'];
                                                    $alreadysentmsgs = (!empty($alreadysentmsgs)) ? $alreadysentmsgs : array();

                                                    $arm_user_complete_recurring_meta = $planData['arm_completed_recurring'];
                                                    $arm_user_complete_recurring = !empty($arm_user_complete_recurring_meta) ? $arm_user_complete_recurring_meta : 0;

                                                    $arm_is_user_in_grace = !empty($planData['arm_is_user_in_grace']) ? $planData['arm_is_user_in_grace'] : 0;
                                                    $arm_grace_period_end = $planData['arm_grace_period_end'];
                                                    
                                                    do_action('arm_general_log_entry', 'cron', 'check user plan next due date', 'armember', 'user_id='.$user_id.', plan_id='.$plan_id.', next due date='.$arm_next_due_date.', end_time='.$now.', in grace='.$arm_is_user_in_grace.', grace end='.$arm_grace_period_end);

                                                    if (!in_array('failed_payment_' . $plan_id . '_' . $arm_user_complete_recurring, $alreadysentmsgs)) 
                                                    {
                                        

                                                        $alreadysentmsgs['failed_payment_'.$now] = 'failed_payment_' . $plan_id . '_' . $arm_user_complete_recurring;
                                                        $planData['arm_sent_msgs'] = $alreadysentmsgs;
                                                        update_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, $planData);
                                                        do_action('arm_general_log_entry', 'cron', 'manual failed payment action', 'armember', 'user_id='.$user_id.', plan_id='.$plan_id );

                                                        $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $usr->ID, 'action' => 'failed_payment'), true); 
                                                        do_action('arm_scheduler_user_plan_failed_payment_outside', $user_id, $plan_id );
                                                    }
                                                    else if( !in_array('failed_payment_after_grace_' . $plan_id . '_' . $arm_user_complete_recurring, $alreadysentmsgs) && !empty($arm_is_user_in_grace) && !empty($arm_grace_period_end) && $arm_grace_period_end < $now) 
                                                    {

                                                        $alreadysentmsgs['failed_payment_after_grace_'.$now] = 'failed_payment_after_grace_' . $plan_id . '_' . $arm_user_complete_recurring;
                                                        $planData['arm_sent_msgs'] = $alreadysentmsgs;
                                                        update_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, $planData);
                                                        do_action('arm_general_log_entry', 'cron', 'manual subscription after grace', 'armember', 'user_id='.$user_id.', plan='. $plan_id );
                                                        
                                                        $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $usr->ID, 'action' => 'failed_payment'), true); 
                                                        do_action('arm_scheduler_user_plan_failed_payment_after_grace_outside', $user_id, $plan_id );
                                                    }

                                                    //suspended plan id check because in above two if condition, if plan suspended then only we need to send next payment failed notification.
                                                    $suspended_plan_ids_check_for_next_payment_failed = get_user_meta($usr->ID, 'arm_user_suspended_plan_ids', true);
                                                    $suspended_plan_ids_check_for_next_payment_failed = (!empty($suspended_plan_ids_check_for_next_payment_failed) && is_array($suspended_plan_ids_check_for_next_payment_failed)) ? $suspended_plan_ids_check_for_next_payment_failed :  array();

                                                    if (in_array($plan_id, $suspended_plan_ids_check_for_next_payment_failed) && !in_array('on_next_payment_failed_' . $plan_id . '_' . $arm_user_complete_recurring, $alreadysentmsgs)) 
                                                    {
                                                        $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($plan_id);
                                                        $notify = $arm_manage_communication->membership_communication_mail('on_next_payment_failed', $usr->ID, $plan_id);
                                                        if($notify) {

                                                                /* Update User meta for notification type */
                                                            $alreadysentmsgs['on_next_payment_failed_'.$now] = 'on_next_payment_failed_' . $plan_id . '_' . $arm_user_complete_recurring;
                                                            $planData['arm_sent_msgs'] = $alreadysentmsgs;
                                                            update_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, $planData);
                                                            $cron_msgs[$usr->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for failed payment.", 'ARMember') . "({$plan_name})";

                                                            do_action('arm_general_log_entry', 'cron', 'manual next payment failed', 'armember', 'user_id='.$user_id.', plan='. $plan_id );

                                                        } else {
                                                            $cron_msgs[$usr->ID] = esc_html__("There is an error in sending mail to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for failed payment.", 'ARMember') . "({$plan_name})";
                                                        }
                                                    }
                                                    else {
                                                        $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($plan_id);
                                                        $cron_msgs[$usr->ID] = esc_html__("Mail already sent to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for failed payment.", 'ARMember') . "({$plan_name})";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                else if ($payment_mode == 'auto_debit_subscription') 
                                {
                                    /* check for failed payment after 1 day of last next due payment date if failed payment was not occured and recurring response was not arrived, then in that case we need to call failed payment action */

                                    $arm_payment_cycle = $planData['arm_payment_cycle'];
                                    $recurring_data = $plan->prepare_recurring_data($arm_payment_cycle);

                                    //$amount = !empty($recurring_data['amount']) ? $recurring_data['amount'] : 0;
                                    $recurring_time = !empty($recurring_data['rec_time']) ? $recurring_data['rec_time'] : '';
                                    $completed = $planData['arm_completed_recurring'];

                                    if($recurring_time != $completed || 'infinite'==$recurring_time) 
                                    {
                                        $actual_arm_next_due_date = $planData['arm_next_due_payment'];
                                        if(!empty($actual_arm_next_due_date)) 
                                        {
                                            $arm_next_due_date = strtotime("+24 Hours", $actual_arm_next_due_date); 
                                            if($now > $arm_next_due_date) 
                                            {
                                                $suspended_plan_ids = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
                                                $suspended_plan_id = (!empty($suspended_plan_ids) && is_array($suspended_plan_ids)) ? $suspended_plan_ids :  array();

                                                if(!in_array($plan_id, $suspended_plan_id)) 
                                                {
                                                    /* control will come here only if recurring payment response was not arrived. */
                                                    $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'), true);

                                                    $arm_user_payment_gateway = $planData['arm_user_gateway'];
                                                    do_action('arm_general_log_entry', 'cron', 'auto subscription check user suspend plan', 'armember', 'user_id='.$user_id.', plan='.$plan_id.', gateway='.$arm_user_payment_gateway.', time='.$now.'>'.$arm_next_due_date );

                                                    do_action('arm_scheduler_user_plan_failed_payment_outside', $user_id, $plan_id );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } /* End Foreach Loop `($expireUsers as $usr)` */
            } /* End `(!empty($expireUsers))` */
            if (!empty($cron_msgs)) {
                do_action('arm_cron_failed_payment_subscription', $cron_msgs);
            }
        }

        function arm_handle_change_user_plan_func() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_manage_communication;
            set_time_limit(0); /* Prevanting timeout issue. */
            $now = current_time('timestamp');
            $start_time = strtotime(date('Y-m-d 00:00:00'));
            $end_time = strtotime(date('Y-m-d 23:59:59'));
            $cron_msgs = array();

            $args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => '',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    ),
                )
            );
            $users = get_users($args);

            if (!empty($users)) {
                foreach ($users as $usr) {
                    $user_id = $usr->ID;
                    $plan_ids = get_user_meta($usr->ID, 'arm_user_plan_ids', true);
                    if (!empty($plan_ids) && is_array($plan_ids)) {
                        foreach ($plan_ids as $plan_id) {
                            $planData = get_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, true);
                            if (!empty($planData) && is_array($plan_ids)) {
                                $arm_subscription_effective = isset($planData['arm_subscr_effective']) ? $planData['arm_subscr_effective']: '';
                                $new_plan = isset($planData['arm_change_plan_to']) ? $planData['arm_change_plan_to'] : '';
                                if (!empty($arm_subscription_effective)) {
                                    if ($arm_subscription_effective <= $end_time) {
                                        if (!empty($new_plan)) {
                                            $arm_subscription_plans->arm_update_user_subscription($user_id, $new_plan, 'system', false);
                                            do_action('arm_general_log_entry', 'cron', 'user membership plan changed', 'armember', 'user_id='.$user_id.', plan='.$plan_id.', subscription effective='.$arm_subscription_effective.', change plan to='.$new_plan );
                                            /* We can send mail to user for change subscription plan */
                                            $cron_msgs[$usr->ID] = $usr->user_email . "'s " . esc_html__("membership has been changed to", 'ARMember') . " {$new_plan}.";
                                            do_action('arm_scheduler_change_user_plan_outside', $user_id, $plan_id, $new_plan);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            
            
            $args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'arm_user_future_plan_ids',
                        'value' => '',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'arm_user_future_plan_ids',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    ),
                )
            );
            $users = get_users($args);

            if (!empty($users)){
                foreach ($users as $usr) {
                    $user_id = $usr->ID;
                    $plan_ids = get_user_meta($usr->ID, 'arm_user_future_plan_ids', true);
                    $current_plan_ids = get_user_meta($usr->ID, 'arm_user_plan_ids', true);
                    $current_plan_ids = !empty($current_plan_ids) ? $current_plan_ids : array(); 
                    if (!empty($plan_ids) && is_array($plan_ids)) {
                        foreach ($plan_ids as $plan_id) {
                            $planData = get_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, true);
                            if (!empty($planData) && is_array($plan_ids)) {
                                $arm_subscription_effective = $planData['arm_start_plan'];
                                if($now >= $arm_subscription_effective){
                                    if(!in_array($plan_id, $current_plan_ids)){
                                        $arm_plan_role = $planData['arm_current_plan_detail']['arm_subscription_plan_role'];
                                        
                                        if(count($current_plan_ids) > 0){
                                            $usr->add_role($arm_plan_role);
                                        }
                                        else{
                                            $usr->set_role($arm_plan_role);
                                        }
                                        unset($plan_ids[array_search($plan_id, $plan_ids)]);
                                        
                                        $current_plan_ids[] = $plan_id;
                                        update_user_meta($usr->ID, 'arm_user_last_plan', $plan_id);
                                        do_action('arm_general_log_entry', 'cron', 'user membership future plan changed', 'armember', 
                                            'user_id='.$user_id.', plan='.$plan_id.', subscription effective='.$arm_subscription_effective);
                                    }
                                }
                            }
                        }
                        update_user_meta($usr->ID, 'arm_user_future_plan_ids', array_values($plan_ids));

                        update_user_meta($usr->ID, 'arm_user_plan_ids', array_values($current_plan_ids));
                    }
                }
            }

            if (!empty($cron_msgs)) {
                do_action('arm_cron_change_user_plan', $cron_msgs);
            }
        }

        function arm_handle_before_expire_subscription_func() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_manage_communication,$arm_pay_per_post_feature;
            set_time_limit(0); /* Preventing timeout issue. */
            $now = current_time('timestamp');
            $cron_msgs = array();
            $notifications = $arm_manage_communication->arm_get_communication_messages_by('message_type', 'before_expire');
            if (!empty($notifications)) {
                foreach ($notifications as $message) {
                    $period_unit = $message->arm_message_period_unit;
                    $period_type = $message->arm_message_period_type;
                    $endtime = strtotime("+$period_unit Days", $now);
                    switch (strtolower($period_type)) {
                        case 'd':
                        case 'day':
                        case 'days':
                            $endtime = strtotime("+$period_unit Days", $now);
                            break;
                        case 'w':
                        case 'week':
                        case 'weeks':
                            $endtime = strtotime("+$period_unit Weeks", $now);
                            break;
                        case 'm':
                        case 'month':
                        case 'months':
                            $endtime = strtotime("+$period_unit Months", $now);
                            break;
                        case 'y':
                        case 'year':
                        case 'years':
                            $endtime = strtotime("+$period_unit Years", $now);
                            break;
                        default:
                            break;
                    }
                    $endtime_start = strtotime(date('Y-m-d 00:00:00', $endtime));
                    $endtime_end = strtotime(date('Y-m-d 23:59:59', $endtime));
                    $message_plans = (!empty($message->arm_message_subscription)) ? explode(',', $message->arm_message_subscription) : array();
                    $planArray = array();
                    if (empty($message_plans)) {
                        $table = $ARMember->tbl_arm_subscription_plans;
                        $all_plans = $wpdb->get_results($wpdb->prepare("SELECT `arm_subscription_plan_id` FROM `{$table}` WHERE `arm_subscription_plan_type` != %s AND `arm_subscription_plan_type` != %s AND `arm_subscription_plan_post_id` = %d", 'free', 'paid_infinite', 0)); //phpcs:ignore --Reason $table is a table name

                        if (!empty($all_plans)) {
                            foreach ($all_plans as $plan) {
                                $planId = $plan->arm_subscription_plan_id;
                                $planArray[] = $planId;
                            }
                        }
                    } else {
                        $planArray = $message_plans;
                    }

                    if (!empty($planArray)) {
                        foreach ($planArray as $plan_id) {
                            $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($plan_id);
                            $args = array(
                                'meta_query' => array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => 'arm_user_plan_ids',
                                        'value' => '',
                                        'compare' => '!='
                                    ),
                                    array(
                                        'key' => 'arm_user_plan_ids',
                                        'value' => 'a:0:{}',
                                        'compare' => '!='
                                    ),
                                )
                            );
                            $users = get_users($args);
                            if (empty($users)) {
                                continue;
                            }
                            foreach ($users as $usr) {
                                $user_plan_ids = get_user_meta($usr->ID, 'arm_user_plan_ids', true);
                                $user_post_ids = get_user_meta($usr->ID, 'arm_user_post_ids', true);
                                if( empty( $user_post_ids ) ){
                                    $user_post_ids = array();
                                }
                                if (!empty($user_plan_ids) && is_array($user_plan_ids)) {
                                    if (in_array($plan_id, $user_plan_ids) && !array_key_exists($plan_id, $user_post_ids) ) {
                                        $planData = get_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, true);
                                        if (!empty($planData)) {
                                            $expireTime = $planData['arm_expire_plan'];
                                            if (!empty($expireTime)) {
                                                if ($expireTime > $now && $expireTime <= $endtime_end) {

                                                    $memberStatus = arm_get_member_status($usr->ID);
                                                    $payment_mode = $planData['arm_payment_mode'];
                                                    $alreadysentmsgs = $planData['arm_sent_msgs'];
                                                    $alreadysentmsgs = (!empty($alreadysentmsgs)) ? $alreadysentmsgs : array();

                                                    if (!in_array('before_expire_' . $message->arm_message_id, $alreadysentmsgs)) {
                                                        
                                                        $notify = $arm_manage_communication->arm_common_send_email($message,$usr->data->user_email,$usr->ID,$plan_id);
                                                        
                                                        if ($notify) {
                                                            /* Update User meta for notification type */
							    $arm_now = $now . '_'.rand();
                                                            $alreadysentmsgs[$arm_now] = 'before_expire_' . $message->arm_message_id;
                                                            $planData['arm_sent_msgs'] = $alreadysentmsgs;
                                                            update_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, $planData);
                                                            $cron_msgs[$usr->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire membership.", 'ARMember') . "({$plan_name})";
                                                        } else {
                                                            $cron_msgs[$usr->ID] = esc_html__("There is an error in sending mail to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire membership.", 'ARMember') . "({$plan_name})";
                                                        }

                                                        if ($notify) {
                                                            $cron_msgs['admin_mail_for_' . $usr->ID] = esc_html__("Mail successfully sent to admin for", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire membership.", 'ARMember') . "({$plan_name})";
                                                        } else {
                                                            $cron_msgs['admin_mail_for_' . $usr->ID] = esc_html__("There is an error in sending mail to admin for", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire membership.", 'ARMember') . "({$plan_name})";
                                                        }

                                                        do_action('arm_general_log_entry', 'cron', 'expire subscription check plan', 'armember', 'user_id='.$usr->ID.', plan='.$plan_id.', expireTime='.$expireTime.', notify='.$notify.', message_id='.$message->arm_message_id);

                                                    } else {
                                                        $cron_msgs[$usr->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire membership.", 'ARMember') . "({$plan_name})";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } /* End Foreach Loop `($notifications as $message)` */
            } /* End `(!empty($notifications))` */

            if( $arm_pay_per_post_feature->isPayPerPostFeature ) {
                $now = current_time('timestamp');
                $pp_notifications = $arm_manage_communication->arm_get_communication_messages_by( 'message_type', 'before_expire_post' );
                if (!empty($pp_notifications)) {
                    foreach ($pp_notifications as $message) {
                        $period_unit = $message->arm_message_period_unit;
                        $period_type = $message->arm_message_period_type;
                        $endtime = strtotime("+$period_unit Days", $now);
                        switch (strtolower($period_type)) {
                            case 'd':
                            case 'day':
                            case 'days':
                                $endtime = strtotime("+$period_unit Days", $now);
                                break;
                            case 'w':
                            case 'week':
                            case 'weeks':
                                $endtime = strtotime("+$period_unit Weeks", $now);
                                break;
                            case 'm':
                            case 'month':
                            case 'months':
                                $endtime = strtotime("+$period_unit Months", $now);
                                break;
                            case 'y':
                            case 'year':
                            case 'years':
                                $endtime = strtotime("+$period_unit Years", $now);
                                break;
                            default:
                                break;
                        }
                        $endtime_start = strtotime(date('Y-m-d 00:00:00', $endtime));
                        $endtime_end = strtotime(date('Y-m-d 23:59:59', $endtime));
                        $message_plans = (!empty($message->arm_message_subscription)) ? explode(',', $message->arm_message_subscription) : array();
                        $planArray = array();
                        if (empty($message_plans)) {
                            $table = $ARMember->tbl_arm_subscription_plans;
                            $all_plans = $wpdb->get_results($wpdb->prepare("SELECT `arm_subscription_plan_id` FROM `{$table}` WHERE `arm_subscription_plan_type` != %s AND `arm_subscription_plan_type` != %s AND arm_subscription_plan_post_id > %d", 'free', 'paid_infinite', 0)); //phpcs:ignore --Reason $table is a table name

                            if (!empty($all_plans)) {
                                foreach ($all_plans as $plan) {
                                    $planId = $plan->arm_subscription_plan_id;
                                    $planArray[] = $planId;
                                }
                            }
                        } else {
                            $planArray = $message_plans;
                        }

                        if (!empty($planArray)) {
                            foreach ($planArray as $plan_id) {
                                $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($plan_id);
                                $args = array(
                                    'meta_query' => array(
                                        'relation' => 'AND',
                                        array(
                                            'key' => 'arm_user_plan_ids',
                                            'value' => '',
                                            'compare' => '!='
                                        ),
                                        array(
                                            'key' => 'arm_user_plan_ids',
                                            'value' => 'a:0:{}',
                                            'compare' => '!='
                                        ),
                                    )
                                );
                                $users = get_users($args);
                                if (empty($users)) {
                                    continue;
                                }
                                foreach ($users as $usr) {
                                    $user_plan_ids = get_user_meta($usr->ID, 'arm_user_post_ids', true);
                                    if (!empty($user_plan_ids) && is_array($user_plan_ids)) {
                                        if (array_key_exists($plan_id, $user_plan_ids)) {
                                            $planData = get_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, true);
                                            if (!empty($planData)) {
                                                $expireTime = $planData['arm_expire_plan'];
                                                
                                                if (!empty($expireTime)) {
                                                    if ($expireTime > $now && $expireTime <= $endtime_end) {
                                                        $memberStatus = arm_get_member_status($usr->ID);
                                                        $payment_mode = $planData['arm_payment_mode'];
                                                        $alreadysentmsgs = $planData['arm_sent_msgs'];
                                                        $alreadysentmsgs = (!empty($alreadysentmsgs)) ? $alreadysentmsgs : array();

                                                        if (!in_array('before_expire_post_' . $message->arm_message_id, $alreadysentmsgs)) {
                                                            
                                                            $notify = $arm_manage_communication->arm_common_send_email($message,$usr->data->user_email,$usr->ID,$plan_id);
							                                
                                                            if ($notify) {
                                                                /* Update User meta for notification type */
								$arm_now = $now . '_'.rand();
                                                                $alreadysentmsgs[$arm_now] = 'before_expire_post_' . $message->arm_message_id;
                                                                $planData['arm_sent_msgs'] = $alreadysentmsgs;
                                                                update_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, $planData);
                                                                $cron_msgs[$usr->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire paid post purchase.", 'ARMember') . "({$plan_name})";
                                                            } else {
                                                                $cron_msgs[$usr->ID] = esc_html__("There is an error in sending mail to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire paid post purchase.", 'ARMember') . "({$plan_name})";
                                                            }

                                                            if ($notify) {
                                                                $cron_msgs['admin_mail_for_' . $usr->ID] = esc_html__("Mail successfully sent to admin for", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire membership.", 'ARMember') . "({$plan_name})";
                                                            } else {
                                                                $cron_msgs['admin_mail_for_' . $usr->ID] = esc_html__("There is an error in sending mail to admin for", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire paid post purchase.", 'ARMember') . "({$plan_name})";
                                                            }

                                                            do_action('arm_general_log_entry', 'cron', 'expire subscription check post', 'armember', 'user_id='.$usr->ID.', plan='.$plan_id.', expireTime='.$expireTime.', notify='.$notify );

                                                        } else {
                                                            $cron_msgs[$usr->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for before expire paid post purchase.", 'ARMember') . "({$plan_name})";
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                do_action('arm_general_log_entry', 'cron', 'subscription user notify before subscription expire', 'armember', 'userid='.$usr->ID.', planid='.$plan_id.', action= before plan expire');
                            }
                        }
                    } /* End Foreach Loop `($notifications as $message)` */
                }
            }
            if (!empty($cron_msgs)) {
                do_action('arm_cron_before_expire_subscription', $cron_msgs);
            }
        }

        function arm_handle_before_dripped_content_available_func() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_manage_communication, $arm_drip_rules;
            if ($arm_drip_rules->isDripFeature) {
                set_time_limit(0); /* Preventing timeout issue. */
                $now = current_time('timestamp');
                $cron_msgs = array();
                $notifications = $arm_manage_communication->arm_get_communication_messages_by('message_type', 'before_dripped_content_available');
                $all_drip_rules = $wpdb->get_results("SELECT * FROM `" . $ARMember->tbl_arm_drip_rules . "` ORDER BY `arm_rule_id` DESC", ARRAY_A); //phpcs:ignore  --Reason $ARMember->tbl_arm_drip_rules is a a table

                $dripRulesMembers = array();



                if (!empty($notifications) && !empty($all_drip_rules)) {
                    foreach ($notifications as $message) {
                        $period_unit = $message->arm_message_period_unit;
                        $period_type = $message->arm_message_period_type;
                        $message_plans = (!empty($message->arm_message_subscription)) ? explode(',', $message->arm_message_subscription) : array();
                        if (!empty($all_drip_rules)) {
                            foreach ($all_drip_rules as $dr) {
                                $ruleID = $dr['arm_rule_id'];
                                $dripAllowMembers = $arm_drip_rules->arm_get_members_for_before_dripped_reminder($ruleID, $period_type, $period_unit);
                                $dripRulesMembers[$ruleID] = $dripAllowMembers;
                            }
                        }

                        if (empty($dripRulesMembers)) {
                            continue;
                        }
                        $planArray = array();




                        if (empty($message_plans)) {
                            $table = $ARMember->tbl_arm_subscription_plans;
                            $all_plans = $wpdb->get_results("SELECT `arm_subscription_plan_id` FROM `{$table}` "); //phpcs:ignore --Reason Query without where clause so need to skip

                            if (!empty($all_plans)) {
                                foreach ($all_plans as $plan) {
                                    $plan_id = $plan->arm_subscription_plan_id;
                                    $planArray[] = $plan_id;
                                }
                            }
                        } else {
                            $planArray = $message_plans;
                        }

                        if (!empty($planArray)) {

                            foreach ($planArray as $plan_id) {
                                $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($plan_id);

                                foreach ($dripRulesMembers as $rule_id => $members) {

                                    foreach ($members as $uID => $member) {
                                        $user_id = $member['user_id'];
                                        $user_email = $member['user_email'];
                                        $arm_item_id = $member['arm_item_id'];
                                        
                                        if (!empty($member['plan_array'])) {
                                            foreach ($member['plan_array'] as $member_plan_array) {
                                                if ($member_plan_array['plan_id'] == $plan_id) {
                                                    $memberStatus = arm_get_member_status($user_id);
                                                    $planData = get_user_meta($user_id, 'arm_user_plan_' . $plan_id, true);
                                                    $alreadysentmsgs = $planData['arm_sent_msgs'];
                                                    $alreadysentmsgs = (!empty($alreadysentmsgs)) ? $alreadysentmsgs : array();
                                                    if (!in_array('before_dripped_content_' . $message->arm_message_id . '_' . $rule_id, $alreadysentmsgs)) {
                                                        
                                                        $ARM_MESSAGE_DRIP_CONTENT_URL = "";
                                                        if($arm_item_id > 0) {
                                                            $ARM_MESSAGE_DRIP_CONTENT_URL = get_permalink($member['arm_item_id']);
                                                        }
                                                        $mail_additional_details = array(
                                                            "ARM_MESSAGE_DRIP_CONTENT_URL" => $ARM_MESSAGE_DRIP_CONTENT_URL
                                                        );
                                                        
                                                        $notify = $arm_manage_communication->arm_common_send_email($message,$user_email,$user_id,$plan_id,'',array(),$mail_additional_details);
                                                        $arm_now = $now . '_'.rand();
                                                        $alreadysentmsgs[$arm_now] = 'before_dripped_content_' . $message->arm_message_id . '_' . $rule_id;
                                                        $planData['arm_sent_msgs'] = $alreadysentmsgs;

                                                        update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $planData);
                                                        if ($notify) {
                                                            /* Update User meta for notification type */
                                                            $cron_msgs[$user_id] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $user_id . " " . esc_html__("for before dripped content available.", 'ARMember') . "({$plan_name})";
                                                        } else {
                                                            $cron_msgs[$user_id] = esc_html__("There is an error in sending mail to", 'ARMember') . " " . $user_id . " " . esc_html__("for before dripped content available.", 'ARMember') . "({$plan_name})";
                                                        }

                                                        if ($notify) {
                                                            $cron_msgs['admin_mail_for_' . $user_id] = esc_html__("Mail successfully sent to admin", 'ARMember') . " for " . $user_id . " " . esc_html__("for before dripped content available.", 'ARMember') . "({$plan_name})";
                                                        } else {
                                                            $cron_msgs['admin_mail_for_' . $user_id] = esc_html__("There is an error in sending mail to admin", 'ARMember') . " for " . $user_id . " " . esc_html__("for before dripped content available.", 'ARMember') . "({$plan_name})";
                                                        }


                                                        do_action('arm_general_log_entry', 'cron', 'dripped content available and email', 'armember', 'user_id='.$user_id.', plan='.$plan_id.', rule='.$rule_id.', msg='.$cron_msgs[$user_id].', notify='.$notify);
                                                        
                                                    } else {
                                                        $cron_msgs[$user_id] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $user_id . " " . esc_html__("for before dripped content available.", 'ARMember') . "({$plan_name})";
                                                    }
                                                    
                                                }
                                            }
                                        }
                                    } /* End Foreach Loop `($users as $usr)` */
                                }
                            }
                        }
                    } /* End Foreach Loop `($notifications as $message)` */
                } /* End `(!empty($notifications))` */
                if (!empty($cron_msgs)) {
                    do_action('arm_cron_before_dripped_content_available', $cron_msgs);
                }
            }
        }

        /**
         * For Trial Period Finished on Today Process
         */
        function arm_handle_trial_finished_func() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_manage_communication;
            set_time_limit(0); /* Preventing timeout issue. */
            $now = current_time('timestamp');
            $eod_time = strtotime(date('Y-m-d 23:59:59', $now));
            $cron_msgs = array();
            $args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => '',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    ),
                )
            );
            $trialUsers = get_users($args);
            if (!empty($trialUsers)) {
                foreach ($trialUsers as $usr) {
                    $memberStatus = arm_get_member_status($usr->ID);
                    $plan_ids = get_user_meta($usr->ID, 'arm_user_plan_ids', true);
                    if (!empty($plan_ids) && is_array($plan_ids)) {
                        foreach ($plan_ids as $plan_id) {
                            $planData = get_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, true);
                            if (!empty($planData) && is_array($planData)) {
                                $is_plan_trial = isset($planData['arm_is_trial_plan']) ? $planData['arm_is_trial_plan'] : 0;
                                $expireTime = isset($planData['arm_trial_end']) ? $planData['arm_trial_end'] : '';
                                if ($expireTime <= $eod_time && $is_plan_trial == '1') {

                                    $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($plan_id);
                                    /* Send Notification Mail */
                                    $alreadysentmsgs = $planData['arm_sent_msgs'];
                                    $alreadysentmsgs = (!empty($alreadysentmsgs)) ? $alreadysentmsgs : array();
                                    if (!in_array('trial_finished', $alreadysentmsgs)) {
                                        $notify = $arm_manage_communication->membership_communication_mail('trial_finished', $usr->ID, $plan_id);

                                        $planData['arm_is_trial_plan'] = 0;
                                        update_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, $planData);
                                        if ($notify) {
                                            /* Update User meta for notification type */
					    $arm_now = $now . '_'.rand();
                                            $alreadysentmsgs[$arm_now] = 'trial_finished';
                                            $planData['arm_sent_msgs'] = $alreadysentmsgs;

                                            update_user_meta($usr->ID, 'arm_user_plan_' . $plan_id, $planData);
                                            $cron_msgs[$usr->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for trial period finished.", 'ARMember') . "({$plan_name})";
                                        } else {
                                            $cron_msgs[$usr->ID] = esc_html__("There is an error in sending mail to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for trial period finished.", 'ARMember') . "({$plan_name})";
                                        }

                                        do_action('arm_general_log_entry', 'cron', 'trial finished email check email', 'armember', 'user_id='.$usr->ID.', plan='.$plan_id.', expireTime='.$expireTime.', msg='.$cron_msgs[$usr->ID].', notify='.$notify);

                                    } else {
                                        $cron_msgs[$usr->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $usr->ID . " " . esc_html__("for trial period finished.", 'ARMember') . "({$plan_name})";
                                    }
                                    
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($cron_msgs)) {
                do_action('arm_cron_trial_finished', $cron_msgs);
            }
        }

        function arm_handle_renewal_reminder_of_subscription_func() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_manage_communication;
            set_time_limit(0);
            $now = current_time('timestamp');
            $cron_msgs = array();
            $notifications = $arm_manage_communication->arm_get_communication_messages_by('message_type', 'manual_subscription_reminder');
            $notifications = $arm_manage_communication->arm_get_communication_messages_sorted($notifications);
                
            if (!empty($notifications)) {
                foreach ($notifications as $message_key => $message) {
                    $period_unit = $message->arm_message_period_unit;
                    $period_type = $message->arm_message_period_type;
                    $endtime = strtotime("+$period_unit Days", $now);
                    switch (strtolower($period_type)) {
                        case 'd':
                        case 'day':
                        case 'days':
                            $endtime = strtotime("+$period_unit Days", $now);
                            break;
                        case 'w':
                        case 'week':
                        case 'weeks':
                            $endtime = strtotime("+$period_unit Weeks", $now);
                            break;
                        case 'm':
                        case 'month':
                        case 'months':
                            $endtime = strtotime("+$period_unit Months", $now);
                            break;
                        case 'y':
                        case 'year':
                        case 'years':
                            $endtime = strtotime("+$period_unit Years", $now);
                            break;
                        default:
                            break;
                    }
                    $endtime_start = strtotime(date('Y-m-d 00:00:00', $endtime));
                    $endtime_end = strtotime(date('Y-m-d 23:59:59', $endtime));
                    $message_plans = (!empty($message->arm_message_subscription)) ? explode(',', $message->arm_message_subscription) : array();
                    $planArray = array();

                    if (empty($message_plans)) {
                        $table = $ARMember->tbl_arm_subscription_plans;
                        $all_plans = $wpdb->get_results($wpdb->prepare("SELECT `arm_subscription_plan_id` FROM `{$table}` WHERE `arm_subscription_plan_type` != %s AND `arm_subscription_plan_type` != %s", 'free', 'paid_infinite')); //phpcs:ignore --Reason $table is a table name
                        if (!empty($all_plans)) {
                            foreach ($all_plans as $plan) {
                                $plan_id = $plan->arm_subscription_plan_id;
                                $planArray[] = $plan_id;
                            }
                        }
                    } else {
                        $planArray = $message_plans;
                    }

                    if (!empty($planArray)) {
                        foreach ($planArray as $plan_id) {
                            $planObj = new ARM_Plan($plan_id);
                            if (!$planObj->is_recurring()) {
                                continue;
                            }
                            $this->arm_send_mail_for_subsciption_expire_reminder($message, $plan_id, $endtime_start, $endtime_end, $now, $notifications, $message_key);
                        }
                    }
                }
            }
            if (!empty($cron_msgs)) {
                do_action('arm_cron_before_send_renew_subscption', $cron_msgs);
            }
        }

        function arm_send_mail_for_subsciption_expire_reminder($message, $arm_plan_id, $endtime_start, $endtime_end, $now, $notifications=array(), $message_key=0) {

            global $wp, $wpdb, $ARMember, $arm_manage_communication, $arm_global_settings, $arm_subscription_plans;
            $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($arm_plan_id);
            $args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => '',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    ),
                )
            );
            $users = get_users($args);

            if (!empty($users)) {

                foreach ($users as $user) {
                    $memberStatus = arm_get_member_status($user->ID);
                    $plan_ids = get_user_meta($user->ID, 'arm_user_plan_ids', true);
                    if (!empty($plan_ids) && is_array($plan_ids)) {
                        foreach ($plan_ids as $plan_id) {
                            if($arm_plan_id==$plan_id)
                            {
                                $planData = get_user_meta($user->ID, 'arm_user_plan_' . $plan_id, true);
                                if (!empty($planData)) {
                                    $arm_next_due_payment = $planData['arm_next_due_payment'];
                                    $payment_mode = $planData['arm_payment_mode'];
                                    if ($payment_mode == 'auto_debit_subscription') {
                                        continue;
                                    }
                                    if (!empty($arm_next_due_payment)) {
                                        if ($arm_next_due_payment > $now && $arm_next_due_payment <= $endtime_end) {
                                            $alreadysentmsgs = $planData['arm_sent_msgs'];
                                            $alreadysentmsgs = (!empty($alreadysentmsgs)) ? $alreadysentmsgs : array();

                                            $arm_user_complete_recurring_meta = $planData['arm_completed_recurring'];
                                            $arm_user_complete_recurring = isset($arm_user_complete_recurring_meta) ? $arm_user_complete_recurring_meta : 0;

                                            if (!in_array('manual_subscription_reminder_' . $message->arm_message_id . '_' . $arm_user_complete_recurring, $alreadysentmsgs)) {
					    
                                                $send_mail = $arm_manage_communication->arm_common_send_email($message,$user->data->user_email,$user->ID,$plan_id);

                                                if ($send_mail) {
                                                    /* Update User meta for notification type */
                                                    $alreadysentmsgs[] = 'manual_subscription_reminder_' . $message->arm_message_id . '_' . $arm_user_complete_recurring;
                                                    for ($i=0; $i<count($notifications); $i++) {
                                                        $arm_message_period_unit = $notifications[$i]->arm_message_period_unit;
                                                        if(!empty($arm_message_period_unit))
                                                        {
                                                            $endtime_chk = strtotime("+$arm_message_period_unit Days", $now);

                                                            if ($arm_next_due_payment <= $endtime_chk) {
                                                                if(!in_array('manual_subscription_reminder_' . $notifications[$i]->arm_message_id . '_' . $arm_user_complete_recurring, $alreadysentmsgs))
                                                                {
                                                                        $alreadysentmsgs[] = 'manual_subscription_reminder_' . $notifications[$i]->arm_message_id . '_' . $arm_user_complete_recurring;    
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    $planData['arm_sent_msgs'] = $alreadysentmsgs;
                                                    update_user_meta($user->ID, 'arm_user_plan_' . $plan_id, $planData);
                                                    $cron_msgs[$user->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $user->ID . " " . esc_html__("for semi automatic subscription reminder.", 'ARMember') . "({$plan_name})";
                                                } else {
                                                    $cron_msgs[$user->ID] = esc_html__("There is an error in sending mail to", 'ARMember') . " " . $user->ID . " " . esc_html__("for semi automatic subscription reminder.", 'ARMember') . "({$plan_name})";
                                                }

                                                if ($send_mail) {
                                                    $cron_msgs['admin_mail_for_' . $user->ID] = esc_html__("Mail successfully sent to admin", 'ARMember') . " for " . $user->ID . " " . esc_html__("for semi automatic subscription reminder.", 'ARMember') . "({$plan_name})";
                                                } else {
                                                    $cron_msgs['admin_mail_for_' . $user->ID] = esc_html__("There is an error in sending mail to admin", 'ARMember') . " for " . $user->ID . " " . esc_html__("for semi automatic subscription reminder.", 'ARMember') . "({$plan_name})";
                                                }

                                                do_action('arm_general_log_entry', 'cron', 'before subscription expire reminder email sent', 'armember', 'user_id='.$user->ID.', plan='.$plan_id.', next due payment='.$arm_next_due_payment.', sentmsg='.maybe_serialize($alreadysentmsgs).', msg='.$cron_msgs[$user->ID].', notify='.$send_mail );

                                            } else {
                                                $cron_msgs[$user->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $user->ID . " " . esc_html__("for semi automatic subscription reminder.", 'ARMember') . "({$plan_name})";
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //Repute Log Before Automatic Subscription Payment Due Code Start
        function arm_handle_renewal_reminder_of_automatic_subscription_func() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_manage_communication;
            set_time_limit(0);
            $now = current_time('timestamp');
            $cron_msgs = array();
            $notifications = $arm_manage_communication->arm_get_communication_messages_by('message_type', 'automatic_subscription_reminder');
            $notifications = $arm_manage_communication->arm_get_communication_messages_sorted($notifications);
            if (!empty($notifications)) {
                foreach ($notifications as $message_key => $message) {
                    $period_unit = $message->arm_message_period_unit;
                    $period_type = $message->arm_message_period_type;
                    $endtime = strtotime("+$period_unit Days", $now);
                    switch (strtolower($period_type)) {
                        case 'd':
                        case 'day':
                        case 'days':
                            $endtime = strtotime("+$period_unit Days", $now);
                            break;
                        case 'w':
                        case 'week':
                        case 'weeks':
                            $endtime = strtotime("+$period_unit Weeks", $now);
                            break;
                        case 'm':
                        case 'month':
                        case 'months':
                            $endtime = strtotime("+$period_unit Months", $now);
                            break;
                        case 'y':
                        case 'year':
                        case 'years':
                            $endtime = strtotime("+$period_unit Years", $now);
                            break;
                        default:
                            break;
                    }
                    $endtime_start = strtotime(date('Y-m-d 00:00:00', $endtime));
                    $endtime_end = strtotime(date('Y-m-d 23:59:59', $endtime));
                    $message_plans = (!empty($message->arm_message_subscription)) ? explode(',', $message->arm_message_subscription) : array();
                    $planArray = array();

                    if (empty($message_plans)) {
                        $table = $ARMember->tbl_arm_subscription_plans;
                        $all_plans = $wpdb->get_results($wpdb->prepare("SELECT `arm_subscription_plan_id` FROM `{$table}` WHERE `arm_subscription_plan_type` != %s AND `arm_subscription_plan_type` != %s", 'free', 'paid_infinite')); //phpcs:ignore --Reason $table is a table name
                        if (!empty($all_plans)) {
                            foreach ($all_plans as $plan) {
                                $plan_id = $plan->arm_subscription_plan_id;
                                $planArray[] = $plan_id;
                            }
                        }
                    } else {
                        $planArray = $message_plans;
                    }

                    if (!empty($planArray)) {
                        foreach ($planArray as $plan_id) {
                            $planObj = new ARM_Plan($plan_id);
                            if (!$planObj->is_recurring()) {
                                continue;
                            }
                            $this->arm_send_mail_for_automatic_subsciption_expire_reminder($message, $plan_id, $endtime_start, $endtime_end, $now, $notifications, $message_key);
                        }
                    }
                }
            }
            if (!empty($cron_msgs)) {
                do_action('arm_cron_before_send_renew_subscption', $cron_msgs);
            }
        }

        function arm_send_mail_for_automatic_subsciption_expire_reminder($message, $arm_plan_id, $endtime_start, $endtime_end, $now, $notifications=array(), $message_key=0) {
            global $wp, $wpdb, $ARMember, $arm_manage_communication, $arm_global_settings, $arm_subscription_plans;
            $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($arm_plan_id);
            $args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => '',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    ),
                )
            );
            $users = get_users($args);

            if (!empty($users)) {
                foreach ($users as $user) {
                    $memberStatus = arm_get_member_status($user->ID);
                    $plan_ids = get_user_meta($user->ID, 'arm_user_plan_ids', true);
                    if (!empty($plan_ids) && is_array($plan_ids)) {
                        foreach ($plan_ids as $plan_id) {
                            if($arm_plan_id==$plan_id)
                            {
                                $planData = get_user_meta($user->ID, 'arm_user_plan_' . $plan_id, true);
                                if (!empty($planData)) {
                                    $arm_next_due_payment = $planData['arm_next_due_payment'];
                                    $payment_mode = $planData['arm_payment_mode'];
                                    if ($payment_mode != 'auto_debit_subscription') {
                                        continue;
                                    }
                                    if (!empty($arm_next_due_payment)) {
                                        if ($arm_next_due_payment > $now && $arm_next_due_payment <= $endtime_end) {
                                            $alreadysentmsgs = $planData['arm_sent_msgs'];
                                            $alreadysentmsgs = (!empty($alreadysentmsgs)) ? $alreadysentmsgs : array();

                                            $arm_user_complete_recurring_meta = $planData['arm_completed_recurring'];
                                            $arm_user_complete_recurring = isset($arm_user_complete_recurring_meta) ? $arm_user_complete_recurring_meta : 0;
                                            if (!in_array('automatic_subscription_reminder_' . $message->arm_message_id . '_' . $arm_user_complete_recurring, $alreadysentmsgs)) {
					    
                                                $notify = $arm_manage_communication->arm_common_send_email($message,$user->data->user_email,$user->ID,$plan_id);

                                                if ($notify) {
                                                    /* Update User meta for notification type */
                                                    $alreadysentmsgs[] = 'automatic_subscription_reminder_' . $message->arm_message_id . '_' . $arm_user_complete_recurring;
                                                    for ($i=0; $i<count($notifications); $i++) {
                                                        $arm_message_period_unit = $notifications[$i]->arm_message_period_unit;
                                                        if(!empty($arm_message_period_unit))
                                                        {
                                                            $endtime_chk = strtotime("+$arm_message_period_unit Days", $now);

                                                            if ($arm_next_due_payment <= $endtime_chk) {
                                                                if(!in_array('automatic_subscription_reminder_' . $notifications[$i]->arm_message_id . '_' . $arm_user_complete_recurring, $alreadysentmsgs))
                                                                {
                                                                        $alreadysentmsgs[] = 'automatic_subscription_reminder_' . $notifications[$i]->arm_message_id . '_' . $arm_user_complete_recurring;    
                                                                }
                                                            }
                                                        }
                                                    }

                                                    $planData['arm_sent_msgs'] = $alreadysentmsgs;
                                                    update_user_meta($user->ID, 'arm_user_plan_' . $plan_id, $planData);
                                                    $cron_msgs[$user->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $user->ID . " " . esc_html__("for before automatic subscription due.", 'ARMember') . "({$plan_name})";
                                                } else {
                                                    $cron_msgs[$user->ID] = esc_html__("There is an error in sending mail to", 'ARMember') . " " . $user->ID . " " . esc_html__("for before automatic subscription due.", 'ARMember') . "({$plan_name})";
                                                }

                                                if ($notify) {
                                                    $cron_msgs['admin_mail_for_' . $user->ID] = esc_html__("Mail successfully sent to admin", 'ARMember') . " for " . $user->ID . " " . esc_html__("for before automatic subscription due.", 'ARMember') . "({$plan_name})";
                                                } else {
                                                    $cron_msgs['admin_mail_for_' . $user->ID] = esc_html__("There is an error in sending mail to admin", 'ARMember') . " for " . $user->ID . " " . esc_html__("for before automatic subscription due.", 'ARMember') . "({$plan_name})";
                                                }

                                                do_action('arm_general_log_entry', 'cron', 'automatic subscription expire and email sent', 'armember', 'user_id='.$user->ID.', plan='.$plan_id.', next due payment='.$arm_next_due_payment.', sentmsg='.maybe_serialize($alreadysentmsgs).', msg='.$cron_msgs[$user->ID].', notify='.$notify);

                                            } else {
                                                $cron_msgs[$user->ID] = esc_html__("Mail successfully sent to", 'ARMember') . " " . $user->ID . " " . esc_html__("for before automatic subscription due.", 'ARMember') . "({$plan_name})";
                                            }
                                            
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        function arm_update_user_achievements_func() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_members_badges;
            set_time_limit(0); /* Preventing timeout issue. */
            $now = current_time('timestamp');
            $cron_msgs = array();
            $arm_members_badges->arm_add_user_achieve_by_cron();
        }

        /**
         * Send anonymous data through cron
         *
         * @return void
         */
        function armember_send_anonymous_data_cron_func() {

            global $ARMember, $wpdb, $arm_global_settings, $wp_version,$arm_payment_gateways, $arm_social_feature;

            $general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
            $armember_send_anonymous_data = isset($general_settings['arm_anonymous_data']) ? $general_settings['arm_anonymous_data'] : 0;
            if(!empty($armember_send_anonymous_data))
            {
                $armember_total_plans = $armember_total_members = $armember_total_payment_transactions = $armember_assign_plan_from_backend = $armember_total_register_custom_fields = 0;

                $activated_modules = $inactivated_modules = $active_plugins_arr = $armember_gateway_wise_transactions = $inactive_plugin_arr = array();

                $armember_lite_version = $armember_lite_installation_date = $armember_pro_version = $armember_pro_installation_date = $home_url = $admin_url = $site_timezone = $site_locale = '';

                $armember_lite_version = get_option('armlite_version');
                $armember_lite_version = !empty($armember_lite_version) ? $armember_lite_version : '';
                $armember_lite_installation_date = get_option('armember_lite_install_date');
                $armember_lite_installation_date = !empty($armember_lite_installation_date) ? $armember_lite_installation_date : '';

                $armember_pro_version = get_option('arm_version');
                $armember_pro_version = !empty($armember_pro_version) ? $armember_pro_version : '';
                $armember_pro_installation_date = get_option('armember_pro_install_date');
                $armember_pro_installation_date = !empty($armember_pro_installation_date) ? $armember_pro_installation_date : '';

                $home_url = home_url();
                $admin_url = admin_url();
                $site_locale = get_locale();
                $site_locale = !empty($site_locale) ? $site_locale : '';
                $site_timezone = wp_timezone_string();

                $tbl_armember_members = $ARMember->tbl_arm_members;
                $tbl_armember_payment_log =  $ARMember->tbl_arm_payment_log;

                $server_information = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field($_SERVER['SERVER_SOFTWARE']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

                $my_theme   = wp_get_theme();
                $theme_data = $my_theme->get('Name').'  ('.$my_theme->get('Version').' )';
                $is_multisite = is_multisite() ? 'Yes' : 'NO';

                $plugin_list    = get_plugins();                               
                $active_plugins = get_option('active_plugins');

                if(!empty($active_plugins))
                {
                    foreach ( $plugin_list as $key => $plugin_detail ) {
                        $is_active = in_array($key, $active_plugins);
                        if ($is_active == 1 ) {
                            $name      = substr($key, 0, strpos($key, '/'));
                            $active_plugins_arr[] = array(            
                                $plugin_detail['Name'] => $plugin_detail['Version']
                            );
                        } else {
                            $inactive_plugin_arr[]  = array(            
                                $plugin_detail['Name'] => $plugin_detail['Version']
                            );
                        }
                    }
                }

                $armember_currency = $arm_payment_gateways->arm_get_global_currency();
                
                $armember_module = array(
                    'arm_is_social_feature'=>'Social Feature',
                    'arm_is_opt_ins_feature'=>'Opts Ins',
                    'arm_is_drip_content_feature' => 'Drip Content',
                    'arm_is_social_login_feature' => 'Social Connect',
                    'arm_is_pay_per_post_feature' => 'Pay Per Post',
                    'arm_is_coupon_feature'=>'Coupon',
                    'arm_is_invoice_tax_feature' => 'Invoice and Tax',
                    'arm_is_user_private_content_feature'=>'User Private Content',
                    'arm_is_multiple_membership_feature' => 'Multiple Membership',
                    'arm_is_gutenberg_block_restriction_feature' => 'Gutenberg Block Restriction',
                    'arm_is_api_service_feature'=>'API Services',
                    'arm_is_buddypress_feature' => 'Buddypress/Buddyboss Integration',
                    'arm_is_woocommerce_feature'=> 'Woocommerce Integration',
                    'arm_is_mycred_feature' => 'myCRED Integration',
		    'arm_is_plan_limit_feature' => 'Membership Limit',
                    'arm_is_beaver_builder_restriction_feature' => 'Beaver Builder Restriction',
                    'arm_is_divi_builder_restriction_feature' => 'DIVI Builder Restriction',
                    'arm_is_wpbakery_page_builder_restriction_feature' => 'WPBakry Page Builder Restriction',
                    'arm_is_fusion_builder_restriction_feature' => 'Fusion Builder Restriction',
                    'arm_is_oxygen_builder_restriction_feature' => 'Oxygen Builder Restriction',
                    'arm_is_siteorigin_builder_restriction_feature' => 'SiteOrigin Builder Restriction',
                    'arm_is_bricks_builder_restriction_feature' => 'Bricks Builder Restriction',
                    'arm_is_pro_ration_feature' => 'Pro-Rata ',
                );
                foreach($armember_module as $key => $value) {                    
                    $is_module_active = get_option($key);
                    if(!empty($is_module_active)) {
                        $activated_modules[] = $value;
                    } else {
                        $inactivated_modules[] = $value;
                    }                    
                }
                
                $armember_addons = $arm_social_feature->addons_page();
                if ($armember_addons != "") {
                    $resp = explode("|^^|", $armember_addons);
                    if ($resp[0] == 1) {
                        $myplugarr = array();
                        $myplugarr = unserialize(base64_decode($resp[1]));
                        $is_active = 0;
                    }
                }
                $active_addon_list = $inactive_addon_list =  array();      
                foreach($myplugarr as $arm_armember_addon_key => $armember_addon_val)
                {
                    if(!empty($armember_addon_val['plugin_installer'])) {
                        if(file_exists( WP_PLUGIN_DIR . '/'.$armember_addon_val['plugin_installer'])) {        
                            $is_addon_active = is_plugin_active($armember_addon_val['plugin_installer']);
                            if($is_addon_active) {
                                $active_addon_list[$armember_addon_val['short_name']] = $armember_addon_val['plugin_version'];
                            } else {
                                $inactive_addon_list[$armember_addon_val['short_name']] = $armember_addon_val['plugin_version'];
                            } 
                        }
                    }
                }

                $armember_total_members = $wpdb->get_var( "SELECT count(arm_member_id) FROM {$ARMember->tbl_arm_members}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_arm_members is a table name. false alarm 

                $armember_total_payment_transactions = $wpdb->get_var( "SELECT count(arm_log_id) FROM {$ARMember->tbl_arm_payment_log}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $ARMember->tbl_arm_payment_log is a table name. false alarm 

                $armember_total_transactions = $wpdb->get_results( "SELECT count(arm_log_id) as total, arm_payment_gateway FROM {$ARMember->tbl_arm_payment_log} GROUP BY arm_payment_gateway",ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_arm_payment_log is a table name. false alarm 

                $armember_total_plans = $wpdb->get_var( "SELECT count(arm_subscription_plan_id) FROM {$ARMember->tbl_arm_subscription_plans}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_arm_subscription_plans is a table name. false alarm 


                if(!empty($armember_total_transactions)) {
                    foreach($armember_total_transactions as $key => $value) {
                       $armember_gateway_wise_transactions[$value['arm_payment_gateway']] =  $value['total'];
                    }
                }

                $presetFormFields = get_option('arm_preset_form_fields', '');
                $dbFormFields = maybe_unserialize($presetFormFields);
                $armember_total_register_custom_fields = isset($dbFormFields['other']) ? count($dbFormFields['other']) : 0;
                
                $total_wp_users =  count_users();
                $total_wp_users = $total_wp_users['total_users'];                              
              
                $armember_anonymous_data = array(
                    'php_version'                             => phpversion(),
                    'armember_lite_version'                   => $armember_lite_version,
                    'armember_lite_installation_date'         => $armember_lite_installation_date,
                    'armember_pro_version'                    => $armember_pro_version,
                    'armember_pro_installation_date'          => $armember_pro_installation_date,
                    'wp_version'                              => $wp_version,
                    'server_information'                      => $server_information,
                    'is_multisite'                            => $is_multisite,
                    'theme_data'                              => $theme_data,
                    'home_url'                                => $home_url,
                    'admin_url'                               => $admin_url,
                    'active_plugin_list'                      => wp_json_encode($active_plugins_arr),
                    'inactivate_plugin_list'                  => wp_json_encode($inactive_plugin_arr),
                    'site_locale'                             => $site_locale,
                    'site_timezone'                           => $site_timezone,
                    'armember_currency'                       => $armember_currency,
                    'activated_modules'                       => wp_json_encode($activated_modules),
                    'inactive_modules'                        => wp_json_encode($inactivated_modules),
                    'activated_addons'                        => wp_json_encode($active_addon_list),
                    'inactive_addons'                         => wp_json_encode($inactive_addon_list),
                    'armember_total_members'                  => $armember_total_members,
                    'armember_total_plans'                    => $armember_total_plans,
                    'total_wp_users'                          => $total_wp_users,
                    'armember_total_register_custom_fields'   => $armember_total_register_custom_fields,
                    'total_payment_transactions'              => $armember_total_payment_transactions,
                    'payment_gateway_wise_transaction'        => wp_json_encode($armember_gateway_wise_transactions),
                );

                $headers = array('user-agent' => $_SERVER['HTTP_USER_AGENT']); //phpcs:ignore --Reason: $_SERVER is a superglobal variable.

                $url = 'https://www.armemberplugin.com/armember_lite_version/arm_tracking_usage.php';
                $response = wp_remote_post(
                    $url,
                    array(
                    'timeout' => 500,
                    'body'    => array( 'arm_anonymous_data' =>  wp_json_encode($armember_anonymous_data), 'headers' => $headers ),
                    )
                );
            }
            
        }

        function arm_clear_cron($name = '') {
            global $ARMember;
            if (!empty($name)) {
                wp_clear_scheduled_hook($name);
            }
        }

        function arm_get_cron_hook_names() {
            $cron_array = array(
                'arm_handle_change_user_plan',
                'arm_handle_expire_subscription',
                'arm_handle_expire_infinite_subscription',
                'arm_handle_before_expire_subscription',
                'arm_handle_before_dripped_content_available',
                'arm_handle_renewal_reminder_of_subscription',
                'arm_handle_trial_finished',
                'arm_update_user_achievements',
                'arm_handle_failed_login_log_data_delete',
                'armember_send_anonymous_data_cron'
            );

            $cron_array = apply_filters('arm_filter_cron_hook_name_outside', $cron_array);

            $cron_array[] = 'arm_handle_failed_payment_for_subscription_plan';
            $cron_array[] = 'arm_handle_failed_payment_for_auto_subscription';

            $cron_array = apply_filters('arm_filter_cron_hook_name_after_outside', $cron_array);

            return $cron_array;
        }

    }

}

global $arm_crons;
$arm_crons = new ARM_crons();
