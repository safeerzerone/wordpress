<?php

if (!class_exists('ARM_pro_ration_feature')) {

    class ARM_pro_ration_feature {
    	
        var $pro_ration_settings;
        
        var $isMultipleMembershipFeature;
        var $isProRationFeature;

        function __construct() {
            $is_multiple_membership_feature = get_option('arm_is_multiple_membership_feature', 0);
        	$this->isMultipleMembershipFeature = ($is_multiple_membership_feature == '1') ? true : false;
            if ($this->isMultipleMembershipFeature != true) {
                $is_pro_ration_feature = get_option('arm_is_pro_ration_feature', 0);
                $this->isProRationFeature = ($is_pro_ration_feature == '1') ? true : false;
                if( $this->isProRationFeature == true ){
                    add_filter('arm_calculate_payment_gateway_submit_data', array($this, 'arm_calculate_payment_gateway_submit_data_for_pro_ration'), 10, 5);

                    add_filter('arm_filter_email_message_type',array($this,'arm_update_member_subscription_date_func'),10,4);

                    add_filter( 'arm_before_setup_form_content', array($this,'arm_prorata_before_setup_form_content'), 10, 3 );

                    add_filter('arm_modify_plan_amount_for_coupon',array($this,'arm_modify_plan_amount_for_coupon'),10,5);

                    add_filter( 'arm_get_recurring_plan_start_date', array($this,'arm_trial_date_for_reccurring_func'),10,3);

                    add_action('arm_after_global_settings_html',array($this,'arm_prorata_setting_html'),1);

                    add_filter( 'arm_update_upgrade_downgrade_action_external', array($this,'arm_update_upgrade_downgrade_action_func'),10,1);                    
                }
            }
        }

        function arm_get_protation_data($user_id,$plan_id,$arm_plan_cycle_id=0)
        {
            global $arm_subscription_plans, $arm_global_settings,$arm_payment_gateways,$ARMember,$wpdb,$wp;

            $return_data = array('pro_rata_enabled'=>0,'pro_rata'=>0,'pro_rata_amount'=>0);
            $current_user_plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true);
            $current_plan_id = !empty($current_user_plan_ids) ? $current_user_plan_ids[0] : 0;
            $suspended_plan_ids = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
            
            if(!empty($current_plan_id) && !empty($plan_id) && 
            (empty($suspended_plan_ids) || !in_array($current_plan_id,$suspended_plan_ids)))
            {
                
                $current_plan = $arm_subscription_plans->arm_get_subscription_plan($current_plan_id);
                $new_plan = $arm_subscription_plans->arm_get_subscription_plan($plan_id);

                $general_settings = $arm_global_settings->global_settings;

                $current_plan_options = $current_plan['arm_subscription_plan_options'];
                $is_enable_upgrade_downgrade_action = !empty($current_plan_options['enable_upgrade_downgrade_action']) ? $current_plan_options['enable_upgrade_downgrade_action'] : 0 ;
                
                $pro_ration_method = !empty($general_settings['arm_pro_ration_method']) ? $general_settings['arm_pro_ration_method'] : 'cost_base';

                if ($is_enable_upgrade_downgrade_action) {
                        
                    $is_plan_in_upgrade_downgrade_action_check = '';
                    if (!empty($current_plan_options['upgrade_plans']) && in_array($plan_id, $current_plan_options['upgrade_plans'])) {
                        $is_plan_in_upgrade_downgrade_action_check = 'upgrade_action';
                    }

                    if( empty($is_plan_in_upgrade_downgrade_action_check) && !empty($current_plan_options['downgrade_plans']) && in_array($plan_id, $current_plan_options['downgrade_plans']))
                    {
                        $is_plan_in_upgrade_downgrade_action_check = 'downgrade_action';
                    }

                    
                    if (!empty($is_plan_in_upgrade_downgrade_action_check) && $current_plan_options[$is_plan_in_upgrade_downgrade_action_check] == 'immediate' || (!empty($is_plan_in_upgrade_downgrade_action_check) && ($general_settings['arm_enable_reset_billing'] == 1 && $current_plan_options[$is_plan_in_upgrade_downgrade_action_check] == 'on_expire'))) {
                        if ( $pro_ration_method == 'cost_base' ) {
                            $return_data = $this->arm_get_pro_ration_cal_cost_base($user_id, $current_plan_id, $plan_id,$arm_plan_cycle_id);
                        } else if ($pro_ration_method == 'time_base') {
                            $return_data = $this->arm_get_pro_ration_cal_time_base($user_id, $current_plan_id, $plan_id,$arm_plan_cycle_id);
                        }  

                    }
                }

            }
            return $return_data;
        }

        function arm_get_pro_ration_cal_cost_base($user_id, $current_plan_id, $new_plan_id,$arm_plan_cycle_id=0,$entry_id=0){
            global $wpdb, $ARMember, $arm_subscription_plans, $arm_global_settings;

            $current_plan = $arm_subscription_plans->arm_get_subscription_plan($current_plan_id);
            $current_planData = get_user_meta($user_id, 'arm_user_plan_' . $current_plan_id, true);
            $new_plan = $arm_subscription_plans->arm_get_subscription_plan($new_plan_id);
            $general_settings = $arm_global_settings->global_settings;
            $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;

            $return_data = array('pro_rata_enabled'=>0,'pro_rata'=>0,'pro_rata_amount'=>0,'membership_length'=>0);

            $current_membership_type = $current_planData['arm_current_plan_detail']['arm_subscription_plan_type'];
            $new_membership_type = $new_plan['arm_subscription_plan_type'];
            
            $current_membership_expires_on = "";
            if($current_membership_type == 'paid_finite') {
                $current_membership_expires_on = !empty($current_planData['arm_expire_plan']) ? $current_planData['arm_expire_plan'] : 0;
            } else if ($current_membership_type == 'recurring') {
                $is_current_trial_active = $current_planData['arm_is_trial_plan'];
                if ($is_current_trial_active) { 
                    $current_membership_expires_on = !empty($current_planData['arm_trial_end']) ? $current_planData['arm_trial_end'] : 0;
                } else {
                    $current_membership_expires_on = !empty($current_planData['arm_next_due_payment']) ? $current_planData['arm_next_due_payment'] : 0;
                }
            }

            if ($new_membership_type == 'free') {
                $return_data = array('pro_rata_enabled'=>0,'pro_rata'=>0,'pro_rata_amount'=>0);
                return $return_data;
            } else if ($new_membership_type == 'paid_infinite') {
                $new_membership_length = 'lifetime';
                $new_membership_price = $new_plan['arm_subscription_plan_amount'];

            } else if($new_membership_type == 'paid_finite') {
                $new_membership_plan_options = $new_plan['arm_subscription_plan_options'];
                $new_membership_length_type = $new_membership_plan_options['eopa']['type'];

                switch ($new_membership_length_type) {
                    case 'D':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['days'];
                        $new_membership_length_period_type = 'day';
                        break;
                    case 'W':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['weeks'];
                        $new_membership_length_period_type = 'week';
                        break;
                    case 'M':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['months'];
                        $new_membership_length_period_type = 'month';
                        break;
                    case 'Y':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['years'];
                        $new_membership_length_period_type = 'year';
                        break;                    
                    default:
                        $new_membership_length_period = 0;
                        break;
                }
                if($new_membership_length_period > 1)
                {
                    $new_membership_length_period_type = $new_membership_length_period_type.'s';
                }
                $new_membership_length = "+" . $new_membership_length_period .' '. $new_membership_length_period_type;
                $new_membership_price = $new_plan['arm_subscription_plan_amount'];

            } else if ($new_membership_type == 'recurring') {
                $new_membership_cycle_id = !empty($arm_plan_cycle_id) ? $arm_plan_cycle_id : 0;
                $new_membership_length_period_type = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['billing_type'];
                $new_membership_length_period = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['billing_cycle'];
                $new_membership_recurring_cycle_count = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['recurring_time'];
                
                switch ($new_membership_length_period_type) {
                    case 'D':
                        $new_membership_length_period_type = 'day';
                        break;
                    case 'W':
                        $new_membership_length_period_type = 'week';
                        break;
                    case 'M':
                        $new_membership_length_period_type = 'month';
                        break;
                    case 'Y':
                        $new_membership_length_period_type = 'year';
                        break;                    
                    default:
                        $new_membership_length_period_type = '-';
                        break;
                }
                if($new_membership_length_period > 1)
                {
                    $new_membership_length_period_type = $new_membership_length_period_type.'s';
                }
                $new_membership_length = "+" . $new_membership_length_period .' '. $new_membership_length_period_type;
                $new_membership_price = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['cycle_amount'];

            }

            if(!empty($entry_id))
            {
                $arm_hook_return_type = "array";
                $new_membership_update_data = apply_filters('arm_modify_membership_plan_amount_external', $new_membership_price, $arm_hook_return_type, $user_id,$new_plan_id, $arm_plan_cycle_id, $new_plan, $entry_id);

                $new_membership_price = !empty($new_membership_update_data['plan_amt']) ? $new_membership_update_data['plan_amt'] : $new_membership_price;

                $new_plan = !empty($new_membership_update_data['plan_data']) ? $new_membership_update_data['plan_data'] : $new_plan;
            }

            $current_plan_data          = $wpdb->get_row( $wpdb->prepare("SELECT `arm_amount` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE arm_plan_id = %d AND arm_user_id=%d AND arm_transaction_status IN ('success',1) ORDER BY arm_log_id DESC LIMIT 1", $current_plan_id, $user_id) );

            $arm_current_plan_amount    = ($current_plan_data->arm_amount != 0) ? $current_plan_data->arm_amount : 0;

            if (!empty($general_settings['enable_tax']) && $new_membership_price > 0) {
                $tax_percentage = 0;
                $tax_display_type= !empty($general_settings['arm_tax_include_exclude_flag']) ? $general_settings['arm_tax_include_exclude_flag'] : 0;
                if ($general_settings['tax_type'] == 'country_tax') {

                    $country_tax_field = !empty($general_settings['country_tax_field']) ? $general_settings['country_tax_field'] : 'country';

                    $member_data_country = $wpdb->get_var( $wpdb->prepare("SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s",$user_id, $country_tax_field));
                    $country_tax_arr = (!empty($general_settings['arm_tax_country_name'])) ? maybe_unserialize($general_settings['arm_tax_country_name']) : array();
                    $country_tax_val_arr = (!empty($general_settings['arm_country_tax_val'])) ? maybe_unserialize($general_settings['arm_country_tax_val']) : array();
                    $country_default_tax = (!empty($general_settings['arm_country_tax_default_val'])) ? $general_settings['arm_country_tax_default_val'] : 0;                   
                    if(!empty($member_data_country) && in_array($member_data_country, $country_tax_arr)) {
                        $opt_index = array_search($member_data_country, $country_tax_arr);
                        $tax_percentage = $country_tax_val_arr[$opt_index];
                    } else {
                        $tax_percentage = $country_default_tax;
                    }
                } else {
                    $tax_percentage = $general_settings['tax_amount'];
                }
                if( empty( $tax_display_type ) )
                {
                    $new_membership_price_tax_amt = ($new_membership_price * $tax_percentage)/100;
                    $new_membership_price = $new_membership_price + $new_membership_price_tax_amt;
                }
            }

            $arm_prorated_amount = $new_membership_price - $arm_current_plan_amount;
            if($arm_prorated_amount<0)
            {
                $arm_prorated_amount = 0;
            }

            $return_data = array('pro_rata_enabled'=>1,'pro_rata'=>$arm_current_plan_amount,'pro_rata_amount'=>$arm_prorated_amount,'new_membership_length'=>$new_membership_length,'arm_trial_end_date'=>$current_membership_expires_on,'new_plan'=>$new_plan);

            return $return_data;
            
        }


        function arm_get_pro_ration_cal_time_base($user_id, $current_plan_id, $new_plan_id,$arm_plan_cycle_id=0,$entry_id=0)
        {            
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways;            

            $general_settings = $arm_global_settings->global_settings;
            $current_plan = $arm_subscription_plans->arm_get_subscription_plan($current_plan_id);
            $new_plan = $arm_subscription_plans->arm_get_subscription_plan($new_plan_id);

            $current_planData = get_user_meta($user_id, 'arm_user_plan_' . $current_plan_id, true);
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $starts_date = !empty($current_planData['arm_start_plan']) ? $current_planData['arm_start_plan'] : '';
            $started_date = !empty($current_planData['arm_started_plan_date']) ? $current_planData['arm_started_plan_date'] : '';
            $starts_on = !empty($starts_date) ? date_i18n($date_format, $starts_date) : '-';            
            if($started_date != '' && $started_date <= $starts_date) {
                $starts_on = date_i18n($date_format, $started_date);
            }

            $arm_return_data = array('pro_rata_enabled'=>0,'pro_rata'=>0,'pro_rata_amount'=>0);

            $current_membership_type = $current_planData['arm_current_plan_detail']['arm_subscription_plan_type'];

            if ($current_membership_type == 'free') {
                $arm_return_data = array('pro_rata_enabled'=>0,'pro_rata'=>0,'pro_rata_amount'=>0);
                return $arm_return_data;
            } else if ($current_membership_type == 'paid_infinite') {
                return $this->arm_get_pro_ration_cal_cost_base($user_id, $current_plan_id, $new_plan_id);
            } else if($current_membership_type == 'paid_finite') {
                $current_membership_expires_on = !empty($current_planData['arm_expire_plan']) ? $current_planData['arm_expire_plan'] : 0;
                if($current_plan['arm_subscription_plan_options']['expiry_type'] != 'fixed_date_expiry')
                {
                    $current_membership_length_type = $current_plan['arm_subscription_plan_options']['eopa']['type'];
                    switch ($current_membership_length_type) {
                        case 'D':
                            $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['days'];
                            $current_membership_length_type = 'day';
                            break;
                        case 'W':
                            $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['weeks'];
                            $current_membership_length_type = 'week';
                            break;
                        case 'M':
                            $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['months'];
                            $current_membership_length_type = 'month';
                            break;
                        case 'Y':
                            $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['years'];
                            $current_membership_length_type = 'year';
                            break;                    
                        default:
                            $current_membership_length_period = 0;
                            break;
                    }
                    if($current_membership_length_period > 1)
                    {
                        $current_membership_length_type = $current_membership_length_type.'s';
                    }
                    $current_membership_length = "+" . $current_membership_length_period .' '. $current_membership_length_type;
                }
                else
                {
                    $current_membership_length = '';
                    $current_membership_expires_on = strtotime($current_plan['arm_subscription_plan_options']['expiry_date']);
                }

            } else if ($current_membership_type == 'recurring') {
                $is_current_trial_active = $current_planData['arm_is_trial_plan'];
                if ($is_current_trial_active) {
                    $current_membership_length_type = $current_plan['arm_subscription_plan_options']['trial']['type'];

                } else {
                    $current_membership_cycle_id = !empty($current_planData['arm_current_plan_detail']['arm_user_selected_payment_cycle']) ? $current_planData['arm_current_plan_detail']['arm_user_selected_payment_cycle'] : 0;
                    $current_membership_length_type = $current_plan['arm_subscription_plan_options']['payment_cycles'][$current_membership_cycle_id]['billing_type'];
                    $current_membership_length_period = $current_plan['arm_subscription_plan_options']['payment_cycles'][$current_membership_cycle_id]['billing_cycle'];
                }
                switch ($current_membership_length_type) {
                    case 'D':
                        $current_membership_length_type = 'day';
                        break;
                    case 'W':
                        $current_membership_length_type = 'week';
                        break;
                    case 'M':
                        $current_membership_length_type = 'month';
                        break;
                    case 'Y':
                        $current_membership_length_type = 'year';
                        break;                    
                    default:
                        $current_membership_length_type = '-';
                        break;
                }

                if ($is_current_trial_active) {
                    $current_membership_length_type =  $current_plan['arm_subscription_plan_options']['trial']['type'];
                    switch ($current_membership_length_type) {
                        case 'D':
                            $current_membership_length_type = 'day';
                            break;
                        case 'M':
                            $current_membership_length_type = 'month';
                            break;
                        case 'Y':
                            $current_membership_length_type = 'year';
                            break;                    
                        default:
                            $current_membership_length_type = 'day';
                            break;
                    }
                    $current_membership_length_period =  $current_plan['arm_subscription_plan_options']['trial'][$current_membership_length_type.'s'];
                }
                if($current_membership_length_period > 1)
                {
                    $current_membership_length_type = $current_membership_length_type.'s';
                }
                $current_membership_length = "+" . $current_membership_length_period .' '. $current_membership_length_type;

                if ($is_current_trial_active) { 
                    $current_membership_expires_on = !empty($current_planData['arm_trial_end']) ? $current_planData['arm_trial_end'] : 0;
                } else {
                    $current_membership_expires_on = !empty($current_planData['arm_next_due_payment']) ? $current_planData['arm_next_due_payment'] : 0;
                }

            }

            $new_membership_type = $new_plan['arm_subscription_plan_type'];

            if ($new_membership_type == 'free') {
                $new_membership_length = 'lifetime';
                return $arm_return_data;
            } else if ($new_membership_type == 'paid_infinite') {
                $new_membership_length = 'lifetime';
                $new_membership_price = $new_plan['arm_subscription_plan_amount'];

            } else if($new_membership_type == 'paid_finite') {
                
                if(!isset($current_plan['arm_subscription_plan_options']['expiry_type']) || $current_plan['arm_subscription_plan_options']['expiry_type'] != 'fixed_date_expiry')
                {
                    $new_membership_plan_options = $new_plan['arm_subscription_plan_options'];
                    $new_membership_length_type = $new_membership_plan_options['eopa']['type'];

                    switch ($new_membership_length_type) {
                        case 'D':
                            $new_membership_length_period = $new_membership_plan_options['eopa']['days'];
                            $new_membership_length_period_type = 'day';
                            break;
                        case 'W':
                            $new_membership_length_period = $new_membership_plan_options['eopa']['weeks'];
                            $new_membership_length_period_type = 'week';
                            break;
                        case 'M':
                            $new_membership_length_period = $new_membership_plan_options['eopa']['months'];
                            $new_membership_length_period_type = 'month';
                            break;
                        case 'Y':
                            $new_membership_length_period = $new_membership_plan_options['eopa']['years'];
                            $new_membership_length_period_type = 'year';
                            break;                    
                        default:
                            $new_membership_length_period = 0;
                            break;
                    }
                    if($new_membership_length_period > 1)
                    {
                        $new_membership_length_period_type = $new_membership_length_period_type.'s';
                    }
                    $new_membership_length = "+" . $new_membership_length_period .' '. $new_membership_length_period_type;
                }
                else
                {
                    $new_membership_length = 'lifetime';
                }

                $new_membership_price = $new_plan['arm_subscription_plan_amount'];

            } else if ($new_membership_type == 'recurring') {
                $new_membership_cycle_id = $arm_plan_cycle_id;
                $new_membership_length_period_type = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['billing_type'];
                $new_membership_length_period = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['billing_cycle'];
                
                switch ($new_membership_length_period_type) {
                    case 'D':
                        $new_membership_length_period_type = 'day';
                        break;
                    case 'W':
                        $new_membership_length_period_type = 'week';
                        break;
                    case 'M':
                        $new_membership_length_period_type = 'month';
                        break;
                    case 'Y':
                        $new_membership_length_period_type = 'year';
                        break;                    
                    default:
                        $new_membership_length_period_type = '-';
                        break;
                }
                if($new_membership_length_period > 1)
                {
                    $new_membership_length_period_type = $new_membership_length_period_type.'s';
                }
                $new_membership_length = "+" . $new_membership_length_period .' '. $new_membership_length_period_type;
                $new_membership_price = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['cycle_amount'];

            }

            if(!empty($entry_id))
            {
                $arm_hook_return_type = "array";
                $new_membership_update_data = apply_filters('arm_modify_membership_plan_amount_external', $new_membership_price, $arm_hook_return_type, $user_id, $new_plan_id, $arm_plan_cycle_id, $new_plan, $entry_id );

                $new_membership_price = !empty($new_membership_update_data['plan_amt']) ? $new_membership_update_data['plan_amt'] : $new_membership_price;

                $new_plan = !empty($new_membership_update_data['plan_data']) ? $new_membership_update_data['plan_data'] : $new_plan;

            }

            if (!empty($general_settings['enable_tax']) && $new_membership_price > 0) {
                $tax_percentage = 0;
                $tax_display_type= !empty($general_settings['arm_tax_include_exclude_flag']) ? $general_settings['arm_tax_include_exclude_flag'] : 0;
                if ($general_settings['tax_type'] == 'country_tax') {

                    $country_tax_field = !empty($general_settings['country_tax_field']) ? $general_settings['country_tax_field'] : 'country';

                    $member_data_country = $wpdb->get_var( $wpdb->prepare("SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s",$user_id, $country_tax_field));
                    $country_tax_arr = (!empty($general_settings['arm_tax_country_name'])) ? maybe_unserialize($general_settings['arm_tax_country_name']) : array();
                    $country_tax_val_arr = (!empty($general_settings['arm_country_tax_val'])) ? maybe_unserialize($general_settings['arm_country_tax_val']) : array();
                    $country_default_tax = (!empty($general_settings['arm_country_tax_default_val'])) ? $general_settings['arm_country_tax_default_val'] : 0;                   
                    if(!empty($member_data_country) && in_array($member_data_country, $country_tax_arr)) {
                        $opt_index = array_search($member_data_country, $country_tax_arr);
                        $tax_percentage = $country_tax_val_arr[$opt_index];
                    } else {
                        $tax_percentage = $country_default_tax;
                    }
                } else {
                    $tax_percentage = $general_settings['tax_amount'];
                }
                if( empty( $tax_display_type ) )
                {
                    $new_membership_price_tax_amt = ($new_membership_price * $tax_percentage)/100;
                    $new_membership_price = $new_membership_price + $new_membership_price_tax_amt;
                }
            }

            $current_time           = strtotime(current_time( 'mysql', true ));

            $midnight_today         = current_time('timestamp');
            if( !empty( $current_membership_length ) )
            {
                $current_membership_length_seconds = strtotime( $current_membership_length, $midnight_today ) - $midnight_today;
            }
            else
            {
                $current_membership_length_seconds = $current_membership_expires_on - $midnight_today;
            }

            $seconds_until_expires_second  = absint( $current_membership_expires_on - $current_time );
            $seconds_until_expires  = (int)($seconds_until_expires_second / 86400)  * 86400  ;
            $seconds_used           = $current_membership_length_seconds - $seconds_until_expires;

            $percent_used_decimal = $seconds_used / $current_membership_length_seconds;
            
            $current_plan_data          = $wpdb->get_row( $wpdb->prepare("SELECT `arm_amount` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE arm_plan_id = %d AND arm_user_id=%d AND arm_transaction_status = 'success' ORDER BY arm_log_id DESC LIMIT 1", $current_plan_id, $user_id) );

            $arm_current_plan_amount    = $current_plan_data->arm_amount;
            $credit                 = $arm_current_plan_amount * abs( 1 - $percent_used_decimal );

            $current_membership_remaining_day = $seconds_until_expires / DAY_IN_SECONDS;

            $arm_prorated_amount = 0;
            if ( (!empty($general_settings['arm_enable_reset_billing']) && $general_settings['arm_enable_reset_billing'] == 1 && $new_membership_type == 'recurring' ) || $new_membership_type != 'recurring') {
                $arm_prorated_amount = $new_membership_price - $credit;
            } else {
                    //if check with each day amount by plan duration and multiply by remaining day if  user's old plan is in trial and deduct with $credit
                if ($current_membership_type == 'recurring' && $current_planData['arm_is_trial_plan'] == 1) {
                    if ( 'lifetime' ==  $new_membership_length) {
                        $current_membership_remaining_day = 1;
                        $new_plan_prorated_amount = $new_membership_price;
                    } else {
                        $new_plan_start_date = $current_planData['arm_trial_start'];
                        $new_plan_expire_date = strtotime($new_membership_length, $new_plan_start_date);
                        $new_plan_total_day = ($new_plan_expire_date - $new_plan_start_date) / DAY_IN_SECONDS;
    
                        $new_plan_prorated_amount = ($new_membership_price / $new_plan_total_day);
                        
                    }

                    $arm_prorated_amount = ($new_plan_prorated_amount * $current_membership_remaining_day) - $credit;

                } else {
                    if ( 'lifetime' !==  $new_membership_length) {
                        $new_pkg_length_seconds = strtotime( $new_membership_length, $midnight_today ) - $midnight_today;
                        $percent_used_decimal   = $seconds_used / $new_pkg_length_seconds;
                    }

                    if ( 'paid_infinite' ===  $new_plan['arm_subscription_plan_type']) {
                        $arm_prorated_amount = $new_membership_price - $credit;
                    } else {
                        // What percentage of the new mebership plan time is left (in decimal form).
                        $new_plan_start_date = $current_planData['arm_start_plan'];
                        $new_plan_expire_date = strtotime($new_membership_length, $new_plan_start_date);
                        $new_plan_total_day = ($new_plan_expire_date - $new_plan_start_date) / DAY_IN_SECONDS;
                        
                        $new_plan_prorated_amount = ($new_membership_price / $new_plan_total_day);

                        $current_membership_remaining_day = $seconds_until_expires / DAY_IN_SECONDS;

                        $arm_prorated_amount = ($new_plan_prorated_amount * $current_membership_remaining_day) - $credit;
                    }
                }
            }

            if($arm_prorated_amount<0)
            {
                $arm_prorated_amount = 0;
            }

            $arm_return_data = array('pro_rata_enabled'=>1,'pro_rata'=>$credit,'pro_rata_amount'=>$arm_prorated_amount,'seconds_until_expires_second'=>$seconds_until_expires_second,'new_membership_length'=>$new_membership_length,'arm_trial_end_date'=>$current_membership_expires_on,'current_membership_remaining_day'=>$current_membership_remaining_day,'new_plan'=>$new_plan);

            return $arm_return_data;
        }
        function arm_prorata_setting_html()
        {
            if (file_exists(MEMBERSHIP_VIEWS_DIR . '/arm_pro_rata_general_setting.php')) {
                require_once( MEMBERSHIP_VIEWS_DIR . '/arm_pro_rata_general_setting.php' );
            }
        }

        function arm_trial_date_for_reccurring_func($user_id,$old_plan_id,$new_plan_id)
        {
            global $arm_subscription_plans, $arm_global_settings;
            $general_settings = $arm_global_settings->global_settings;
            $new_plan = $arm_subscription_plans->arm_get_subscription_plan($new_plan_id);
            $new_membership_type = $new_plan['arm_subscription_plan_type'];
            $old_plan_id = $old_plan_id[0];

            $arm_enable_reset_billing = isset($general_settings['arm_enable_reset_billing']) ? $general_settings['arm_enable_reset_billing'] : 0;
            if($new_membership_type == 'recurring' && !empty($new_plan['arm_subscription_plan_options']['trial']) && !empty($new_plan['arm_subscription_plan_options']['trial']['is_trial_period'])){

                $current_plan = $arm_subscription_plans->arm_get_subscription_plan($old_plan_id);
                $planData = get_user_meta($user_id, 'arm_user_plan_' . $old_plan_id, true);

                $starts_date = !empty($planData['arm_start_plan']) ? $planData['arm_start_plan'] : '';
                $started_date = !empty($planData['arm_started_plan_date']) ? $planData['arm_started_plan_date'] : '';

                $membership_type = $current_plan['arm_subscription_plan_type'];

                if($membership_type == 'paid_finite') {
                    $expires_on = !empty($planData['arm_expire_plan']) ? $planData['arm_expire_plan'] : '';
                }
                else if($membership_type == 'recurring')
                {
                    $expires_on = !empty($planData['arm_next_due_payment']) ? $planData['arm_next_due_payment'] : '';
                }
                else
                {
                   $start_date = strtotime(current_time( 'mysql' ));
                }
                $plan_trial_type = $new_plan['arm_subscription_plan_options']['trial']['type'];
                switch($plan_trial_type)
                {
                    case 'D':
                        $trial_period = $new_plan['arm_subscription_plan_options']['trial']['days'];
                        // $daysin_second = $trial_period * 86400;
                        $start_date = strtotime('-'.$trial_period.' days',$expires_on);
                        break;
                    case 'M':
                        $trial_period = $new_plan['arm_subscription_plan_options']['trial']['months'];
                        $start_date = strtotime('-'.$trial_period.' Months',$expires_on);
                        break;
                    case 'Y':
                        $trial_period = $new_plan['arm_subscription_plan_options']['trial']['years'];
                        $start_date = strtotime('-'.$trial_period.' Years',$expires_on);
                        break;
                    default:
                        $trial_period = $new_plan['arm_subscription_plan_options']['trial']['days'];
                        $start_date = strtotime('-'.$trial_period.' days',$expires_on);
                        break;
                }
                return $start_date;
            }
        }

        function arm_modify_plan_amount_for_coupon($plan_amt,$plan_obj,$payment_cycle,$payment_gateway='',$payment_mode='')
        {
            global $arm_subscription_plans, $arm_global_settings;
            $arm_pro_ration_supported_payment_gateways = $this->arm_check_payment_gateway_allow_pro_ration();
            $arm_pro_rata_supported_gateways = $arm_pro_ration_supported_payment_gateways['allow_auto_debit_trial'];

            $is_payment_gateway_supported  = ( in_array($payment_gateway,$arm_pro_rata_supported_gateways) ) ? 1 : 0;

            if($this->isProRationFeature && is_user_logged_in() && ( $plan_obj->is_recurring() && ( ( !empty( $is_payment_gateway_supported ) && $payment_mode == 'auto_debit_subscription' ) || $payment_mode != 'auto_debit_subscription') || !$plan_obj->is_recurring() ) )
            {
                $payment_cycle = !empty($payment_cycle) ? $payment_cycle : 0;
                $user_id = get_current_user_id();
                $selected_plan_id = get_user_meta( get_current_user_id(), 'arm_user_last_plan', true );
                $plan = new ARM_Plan($selected_plan_id);
                $is_recurring = $plan->is_recurring();
                $current_plan = $arm_subscription_plans->arm_get_subscription_plan($selected_plan_id);                
                $general_settings = $arm_global_settings->global_settings;
                $arm_enable_reset_billing = !empty($general_settings['arm_enable_reset_billing']) ? 1 : 0;
                $protation_amount = $this->arm_get_protation_data($user_id,$plan_obj->ID,$payment_cycle);
                
                if($protation_amount['pro_rata_enabled'])
                {
                    $plan_amt = $protation_amount['pro_rata_amount'];
                }
            }
            return $plan_amt;

        }

        function arm_prorata_before_setup_form_content($content, $setupID, $setup_data)
        {
            $content = '';
            global $arm_subscription_plans, $arm_global_settings,$ARMember,$wpdb;
            if ($this->isProRationFeature && is_user_logged_in()) {
                $user_id = get_current_user_id();
                $selected_plan_id = get_user_meta( $user_id, 'arm_user_last_plan', true );
                $general_settings = $arm_global_settings->global_settings;               
                
                if(!empty($selected_plan_id))
                {    
                    $plan = new ARM_Plan($selected_plan_id);
                    $is_recurring = $plan->is_recurring();
                    $current_plan = $arm_subscription_plans->arm_get_subscription_plan($selected_plan_id);

                    $is_enable_upgrade_downgrade_action = !empty($current_plan['arm_subscription_plan_options']['enable_upgrade_downgrade_action']) ? $current_plan['arm_subscription_plan_options']['enable_upgrade_downgrade_action'] : '';

                    if ( $is_enable_upgrade_downgrade_action ) 
                    {
                        $upgrade_plans_ids = !empty($current_plan['arm_subscription_plan_options']['upgrade_plans']) ? $current_plan['arm_subscription_plan_options']['upgrade_plans'] : array();

                        $updgrade_plan_action = !empty($current_plan['arm_subscription_plan_options']['upgrade_action']) ? $current_plan['arm_subscription_plan_options']['upgrade_action'] : '';

                        $downgrade_plan_action = !empty($current_plan['arm_subscription_plan_options']['downgrade_action']) ? $current_plan['arm_subscription_plan_options']['downgrade_action'] : '';

                        $downgrade_plans_ids = !empty($current_plan['arm_subscription_plan_options']['downgrade_plans']) ? $current_plan['arm_subscription_plan_options']['downgrade_plans'] : array();

                        $upgrade_plans = !empty($current_plan['arm_subscription_plan_options']['upgrade_plans']) ? implode(',',$current_plan['arm_subscription_plan_options']['upgrade_plans']) : '';

                        $downgrade_plans = !empty($current_plan['arm_subscription_plan_options']['downgrade_plans']) ? implode(',',$current_plan['arm_subscription_plan_options']['downgrade_plans']) : '';

                        $pro_ration_method = !empty($general_settings['arm_pro_ration_method']) ? $general_settings['arm_pro_ration_method'] : 'cost_base';

                        $arm_enable_reset_billing = !empty($general_settings['arm_enable_reset_billing']) ? 1 : 0;

                        $setup_plan_ids = $setup_data['arm_setup_modules']['modules']['plans'];
                        foreach($setup_plan_ids as $plan_id)
                        {
                            $arm_pro_rata_feature_flag = 0;
                            $data_arm_is_proratation_amount = 0;
                            if( ( !empty($upgrade_plans_ids) && in_array($plan_id,$upgrade_plans_ids) && ( !empty($updgrade_plan_action) && ( $updgrade_plan_action != 'on_expire' || $arm_enable_reset_billing == 1 ) ) ) || ( !empty($downgrade_plans_ids) && in_array($plan_id,$downgrade_plans_ids)  && ( !empty($downgrade_plan_action) && ( $downgrade_plan_action != 'on_expire' || $arm_enable_reset_billing == 1 ) ) ) )
                            {
                                $arm_pro_rata_feature_flag = 1;
                                $data_arm_is_proratation = $this->arm_get_protation_data($user_id,$plan_id);
                                $data_arm_is_proratation_amount = $data_arm_is_proratation['pro_rata_amount'];
                            }
                            $content .= "<input type='hidden' class='arm_pro_rata_feature_".$plan_id."' value='" . $arm_pro_rata_feature_flag . "'/>";
                            $content .= "<input type='hidden' class='arm_pro_rata_amt_".$plan_id."' value='" . $data_arm_is_proratation_amount . "'/>";
                        }
                    }
                    $allowed_pro_rata_gateways = $this->arm_check_payment_gateway_allow_pro_ration();
                    $content .= '<input type="hidden" class="arm_prorata_supported_gateway" value="'.implode(',',$allowed_pro_rata_gateways['allow_auto_debit_trial']).'"/>';
                }
            }
            return $content;
        }

        function arm_prorata_calulate_for_trial($user_id,$plan_id,$current_plan_id,$credit)
        {
            global $arm_subscription_plans; 
            $current_plan = $arm_subscription_plans->arm_get_subscription_plan($current_plan_id);

            $current_membership_length_type = $current_plan['arm_subscription_plan_options']['trial']['type'];

            $current_planData = get_user_meta( $user_id, 'arm_user_plan_'.$current_plan_id, true );
            
            $current_membership_type = $current_planData['arm_current_plan_detail']['arm_subscription_plan_type'];

            $current_membership_arm_start_plan = !empty($current_planData['arm_start_plan']) ? $current_planData['arm_start_plan'] : 0;

            $current_membership_expires_on = !empty($current_planData['arm_trial_end']) ? $current_planData['arm_trial_end'] : 0;
            
            switch ($current_membership_length_type) {
                case 'D':
                    $current_membership_length_type = 'day';
                    break;
                case 'M':
                    $current_membership_length_type = 'month';
                    break;
                case 'Y':
                    $current_membership_length_type = 'year';
                    break;                    
                default:
                    $current_membership_length_type = 'day';
                    break;
            }
            
            $current_membership_length_period =  $current_plan['arm_subscription_plan_options']['trial'][$current_membership_length_type.'s'];

            $new_plan = $arm_subscription_plans->arm_get_subscription_plan($plan_id);
            $new_membership_type = $new_plan['arm_subscription_plan_type'];
            $new_membership_length_period = 'lifetime';
            $new_membership_price = $new_plan['arm_subscription_plan_amount'];
            if ($new_membership_type == 'paid_infinite') {
                $new_membership_length_period = 'lifetime';
                $new_membership_price = $new_plan['arm_subscription_plan_amount'];
            } else if($new_membership_type == 'paid_finite') {
                $new_membership_plan_options = $new_plan['arm_subscription_plan_options'];
                $new_membership_length_type = $new_membership_plan_options['eopa']['type'];

                switch ($new_membership_length_type) {
                    case 'D':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['days'];
                        $new_membership_length_period_type = 'day';
                    case 'W':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['weeks'];
                        $new_membership_length_period_type = 'week';
                        break;
                    case 'M':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['months'];
                        $new_membership_length_period_type = 'month';
                        break;
                    case 'Y':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['years'];
                        $new_membership_length_period_type = 'year';
                        break;                    
                    default:
                        $new_membership_length_period = 0;
                        break;
                }
                if($new_membership_length_period > 1)
                {
                    $new_membership_length_period_type = $new_membership_length_period_type.'s';
                }
                $new_membership_length = "+" . $new_membership_length_period .' '. $new_membership_length_period_type;
                $new_membership_price = $new_plan['arm_subscription_plan_amount'];

            } else if ($new_membership_type == 'recurring') {
                $new_membership_cycle_id = 0;
                $new_membership_length_period_type = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['billing_type'];
                $new_membership_length_period = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['billing_cycle'];
                
                switch ($new_membership_length_period_type) {
                    case 'D':
                        $new_membership_length_period_type = 'day';
                    case 'W':
                        $new_membership_length_period_type = 'week';
                        break;
                    case 'M':
                        $new_membership_length_period_type = 'month';
                        break;
                    case 'Y':
                        $new_membership_length_period_type = 'year';
                        break;                    
                    default:
                        $new_membership_length_period_type = '-';
                        break;
                }
                if($new_membership_length_period > 1)
                {
                    $new_membership_length_period_type = $new_membership_length_period_type.'s';
                }
                $new_membership_length = "+" . $new_membership_length_period .' '. $new_membership_length_period_type;
                $new_membership_price = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['cycle_amount']; 

            }

            $current_time           = strtotime(current_time( 'mysql', true ));

            $midnight_today         = strtotime( 'today midnight' );
            
            $seconds_until_expires_second  = absint( $current_membership_expires_on - $current_time + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
            $seconds_until_expires  = (int)($seconds_until_expires_second / 86400) * 86400  ;

            $arm_trial_remaining_days = 1;
            $arm_new_plan_prorated_amount = $new_membership_price;

            if ($current_membership_type == 'recurring' && $current_planData['arm_is_trial_plan'] == 1) {
                $new_plan_start_date = $current_planData['arm_trial_start'];
                $new_plan_expire_date = strtotime($new_membership_length, $new_plan_start_date);
                $new_plan_total_day = ($new_plan_expire_date - $new_plan_start_date) / DAY_IN_SECONDS;
                $new_plan_prorated_amount = ($new_membership_price / $new_plan_total_day);
                $current_membership_remaining_day = $seconds_until_expires / DAY_IN_SECONDS;
                if ( 'lifetime' ==  $new_membership_length) {
                    $current_membership_remaining_day = 1;
                }                    
                $arm_prorated_amount = ($new_plan_prorated_amount * $current_membership_remaining_day) - $credit;
            }
            else
            {

                if($new_membership_length_period != 'lifetime')
                {
                    $new_membership_end_date = strtotime($new_membership_length,$current_membership_arm_start_plan);
    
                    $arm_time_calculated = $new_membership_end_date - $current_membership_arm_start_plan;
    
                    $arm_days_remaining = $arm_time_calculated / DAY_IN_SECONDS;
    
                    $arm_new_plan_prorated_amount = $new_membership_price / $arm_days_remaining;
    
                    $arm_trial_remaining_days = $seconds_until_expires / 86400;
                }
                $arm_prorated_amount = ($arm_new_plan_prorated_amount * $arm_trial_remaining_days) - $credit;
            }
            

            return $arm_prorated_amount;
        }
 
        function arm_prorata_calulate_percent($plan_id,$percent_used_decimal,$seconds_used)
        {
            global $arm_subscription_plans; 
            $new_plan = $arm_subscription_plans->arm_get_subscription_plan($plan_id);
            $new_membership_type = $new_plan['arm_subscription_plan_type'];
            $new_membership_length = 'lifetime';
            if ($new_membership_type == 'paid_infinite') {
                $new_membership_length = 'lifetime';
            } else if($new_membership_type == 'paid_finite') {
                $new_membership_plan_options = $new_plan['arm_subscription_plan_options'];
                $new_membership_length_type = $new_membership_plan_options['eopa']['type'];

                switch ($new_membership_length_type) {
                    case 'D':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['days'];
                        $new_membership_length_period_type = 'day';
                    case 'W':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['weeks'];
                        $new_membership_length_period_type = 'week';
                        break;
                    case 'M':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['months'];
                        $new_membership_length_period_type = 'month';
                        break;
                    case 'Y':
                        $new_membership_length_period = $new_membership_plan_options['eopa']['years'];
                        $new_membership_length_period_type = 'year';
                        break;                    
                    default:
                        $new_membership_length_period = 0;
                        break;
                }
                if($new_membership_length_period > 1)
                {
                    $new_membership_length_period_type = $new_membership_length_period_type.'s';
                }
                $new_membership_length = "+" . $new_membership_length_period .' '. $new_membership_length_period_type;

            } else if ($new_membership_type == 'recurring') {
                $new_membership_cycle_id = 0;
                $new_membership_length_period_type = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['billing_type'];
                $new_membership_length_period = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['billing_cycle'];
                
                switch ($new_membership_length_period_type) {
                    case 'D':
                        $new_membership_length_period_type = 'day';
                    case 'W':
                        $new_membership_length_period_type = 'week';
                        break;
                    case 'M':
                        $new_membership_length_period_type = 'month';
                        break;
                    case 'Y':
                        $new_membership_length_period_type = 'year';
                        break;                    
                    default:
                        $new_membership_length_period_type = '-';
                        break;
                }
                if($new_membership_length_period > 1)
                {
                    $new_membership_length_period_type = $new_membership_length_period_type.'s';
                }
                $new_membership_length = "+" . $new_membership_length_period .' '. $new_membership_length_period_type;

            }
            
            $midnight_today = strtotime( 'today midnight' );
            if ( 'lifetime' !==  $new_membership_length) {
                $new_pkg_length_seconds = strtotime( $new_membership_length, $midnight_today ) - $midnight_today;
                $percent_used_decimal   = $seconds_used / $new_pkg_length_seconds;
            }

            $percent_remaining_decimal = !empty($percent_used_decimal) ? abs( 1 - $percent_used_decimal ) : 1;
            return $percent_remaining_decimal;
        }

        function arm_get_pro_ration_amount($user_id,$plan_id)
        {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways;
            
            $current_plan_id = $plan_id;
            $credit = 0;
            if(empty($current_plan_id)) {
                return $credit;
            }

            $current_plan = $arm_subscription_plans->arm_get_subscription_plan($current_plan_id);
            
            $planData = get_user_meta($user_id, 'arm_user_plan_' . $current_plan_id, true);

            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $starts_date = !empty($planData['arm_start_plan']) ? $planData['arm_start_plan'] : '';
            $started_date = !empty($planData['arm_started_plan_date']) ? $planData['arm_started_plan_date'] : '';
            $starts_on = !empty($starts_date) ? date_i18n($date_format, $starts_date) : '-';            
            if($started_date != '' && $started_date <= $starts_date) {
                $starts_on = date_i18n($date_format, $started_date);
            }

            $membership_type = $current_plan['arm_subscription_plan_type'];

            if ($membership_type == 'paid_infinite') {
                $credit = $current_plan['arm_subscription_plan_amount'];
                $arm_pro_ration_time_cal = array("credit"=>$credit,"percent_remaining"=>'');
                return $arm_pro_ration_time_cal;
            } else if($membership_type == 'paid_finite') {
                $current_membership_length_type = $current_plan['arm_subscription_plan_options']['eopa']['type'];
                switch ($current_membership_length_type) {
                    case 'D':
                        $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['days'];
                        $current_membership_length_period_type = 'day';
                        break;
                    case 'W':
                        $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['weeks'];
                        $current_membership_length_period_type = 'week';
                        break;
                    case 'M':
                        $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['months'];
                        $current_membership_length_period_type = 'month';
                        break;
                    case 'Y':
                        $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['years'];
                        $current_membership_length_period_type = 'year';
                        break;                    
                    default:
                        $current_membership_length_period = 0;
                        break;
                }
                if($current_membership_length_period > 1)
                {
                    $current_membership_length_period_type = $current_membership_length_period_type.'s';
                }
                $current_membership_length = "+" . $current_membership_length_period .' '. $current_membership_length_period_type;

                $current_membership_expires_on = !empty($planData['arm_expire_plan']) ? $planData['arm_expire_plan'] : 0;

            } elseif ($membership_type == 'recurring') {
                $is_current_trial_active = $planData['arm_is_trial_plan'];
                $current_membership_length_type = $current_plan['arm_subscription_plan_options']['payment_cycles'][0]['billing_type'];
                $current_membership_length = $current_plan['arm_subscription_plan_options']['payment_cycles'][0]['billing_cycle'];
                
                switch ($current_membership_length_type) {
                    case 'D':
                        $current_membership_length_type = 'day';
                    case 'W':
                        $current_membership_length_type = 'week';
                        break;
                    case 'M':
                        $current_membership_length_type = 'month';
                        break;
                    case 'Y':
                        $current_membership_length_type = 'year';
                        break;                    
                    default:
                        $current_membership_length_type = 'day';
                        break;
                }
                if ($is_current_trial_active) {
                    $current_membership_length_type =  $current_plan['arm_subscription_plan_options']['trial']['type'];
                    switch ($current_membership_length_type) {
                        case 'D':
                            $current_membership_length_type = 'day';
                            break;
                        case 'M':
                            $current_membership_length_type = 'month';
                            break;
                        case 'Y':
                            $current_membership_length_type = 'year';
                            break;                    
                        default:
                            $current_membership_length_type = 'day';
                            break;
                    }
                    $current_membership_length =  $current_plan['arm_subscription_plan_options']['trial'][$current_membership_length_type.'s'];
                }
                if($current_membership_length > 1)
                {
                    $current_membership_length_type = $current_membership_length_type.'s';
                }
                $current_membership_length = "+" . $current_membership_length .' '. $current_membership_length_type;
                $current_membership_expires_on = !empty($planData['arm_next_due_payment']) ? $planData['arm_next_due_payment'] : 0;

            }

            $current_time           = strtotime(current_time( 'mysql', true ));

            $midnight_today         = strtotime( 'today midnight' );

            $current_membership_length_seconds = strtotime( $current_membership_length, $midnight_today ) - $midnight_today;

            
            
            $seconds_until_expires_second  = absint( $current_membership_expires_on - $current_time + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
            $seconds_until_expires  = (int)($seconds_until_expires_second / 86400) * 86400  ;

            $seconds_used           = $current_membership_length_seconds - $seconds_until_expires;

            $percent_used_decimal   = $seconds_used / $current_membership_length_seconds;

            $current_plan_data          = $wpdb->get_row( $wpdb->prepare("SELECT `arm_amount` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE arm_plan_id = %d AND arm_user_id=%d ORDER BY arm_log_id DESC LIMIT 1", $current_plan_id, $user_id) );

            $arm_old_plan_amount    = $current_plan_data->arm_amount;
            $credit                 = $arm_old_plan_amount * abs( 1 - $percent_used_decimal );
            
            $percent_remaining_decimal = abs( 1 - $percent_used_decimal );

            $arm_pro_ration_time_cal = array("credit"=>$credit,"percent_remaining"=>$percent_remaining_decimal,"seconds_used"=>$seconds_used);

            return $arm_pro_ration_time_cal;
        }
        
        public function arm_calculate_payment_gateway_submit_data_for_pro_ration( $arm_return_data, $payment_gateway, $payment_gateway_options, $posted_data, $entry_id )
        {
            global $arm_subscription_plans, $arm_global_settings,$arm_payment_gateways,$ARMember,$wpdb,$wp;
            if ($this->isProRationFeature) {
                $general_settings = $arm_global_settings->global_settings;
                $user_id = $arm_return_data['arm_entry_data']['arm_user_id'];
                $new_plan_id = !empty($arm_return_data['arm_plan_obj']->ID) ? $arm_return_data['arm_plan_obj']->ID :0 ;

                $plan = !empty($arm_return_data['arm_plan_obj']) ? $arm_return_data['arm_plan_obj'] : new ARM_Plan($new_plan_id);
                $is_recurring = $plan->is_recurring();
                $old_plan_id = $arm_return_data['arm_entry_data']['arm_entry_value']['arm_user_old_plan'];
                $current_plan = $arm_subscription_plans->arm_get_subscription_plan($arm_return_data['arm_entry_data']['arm_entry_value']['arm_user_old_plan']);
                $new_plan = $arm_subscription_plans->arm_get_subscription_plan($arm_return_data['arm_plan_obj']->ID);
                if(!empty($current_plan) && !empty($new_plan_id) && $new_plan_id != $old_plan_id) {
                    $current_plan_options = $current_plan['arm_subscription_plan_options'];
                    $is_enable_upgrade_downgrade_action = !empty($current_plan_options['enable_upgrade_downgrade_action']) ? $current_plan_options['enable_upgrade_downgrade_action'] : 0 ;
                    $pro_ration_method = !empty($general_settings['arm_pro_ration_method']) ? $general_settings['arm_pro_ration_method'] : 'cost_base';
                    if ($is_enable_upgrade_downgrade_action) {
                        $is_plan_in_upgrade_downgrade_action_check = '';
                        if (!empty($current_plan_options['upgrade_plans']) && in_array($new_plan_id, $current_plan_options['upgrade_plans'])) {
                            $is_plan_in_upgrade_downgrade_action_check = 'upgrade_action';
                        }

                        if( empty($is_plan_in_upgrade_downgrade_action_check) && !empty($current_plan_options['downgrade_plans']) && in_array($new_plan_id, $current_plan_options['downgrade_plans']))
                        {
                            $is_plan_in_upgrade_downgrade_action_check = 'downgrade_action';
                        }

                        if (!empty($is_plan_in_upgrade_downgrade_action_check) && $current_plan_options[$is_plan_in_upgrade_downgrade_action_check] == 'immediate' || (!empty($is_plan_in_upgrade_downgrade_action_check) && ($general_settings['arm_enable_reset_billing'] == 1 && $current_plan_options[$is_plan_in_upgrade_downgrade_action_check] == 'on_expire'))) {
                            if ( $pro_ration_method == 'cost_base' ) {
                                $arm_return_data = $this->arm_pro_ration_cal_cost_base($arm_return_data, $payment_gateway, $payment_gateway_options, $posted_data, $entry_id);
                            } else if ($pro_ration_method == 'time_base') {
                                $arm_return_data = $this->arm_pro_ration_cal_time_base($arm_return_data, $payment_gateway, $payment_gateway_options, $posted_data, $entry_id);
                            }  
                            
                            //update to entries table if plan is upgrade/downgrade
                            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
                            $entry_values = !empty($entry_data['arm_entry_value']) ? maybe_unserialize($entry_data['arm_entry_value']) : array();
                            $entry_values['arm_return_data'] = $arm_return_data;
                            $arm_updated_entry_values = maybe_serialize($entry_values);
                            $wpdb->update($ARMember->tbl_arm_entries, array('arm_entry_value' => $arm_updated_entry_values), array('arm_entry_id' => $entry_id));
                            $arm_return_data['arm_entry_data']['arm_return_data'] = maybe_serialize($arm_return_data);
                        }
                    }
                    
                }
            }
            return $arm_return_data;
        }

        public function arm_check_payment_gateway_allow_pro_ration( $payment_gateway='', $trial_days='' )
        {
            $arm_pro_ration_supported_payment_gateways['arm_is_trial_allowed'] = 0;

            $arm_pro_ration_supported_payment_gateways['allow_auto_debit_trial'] = array('paypal', 'stripe', 'paypal_pro', 'paddle');
            
            $arm_pro_ration_supported_payment_gateways['allow_auto_debit_trial_interval'] = array(
                'paypal' => array( 'type' => 'D', 'interval' => 90, ),
                'stripe' =>array( 'type' => 'D', 'interval' => 365, ),
                //'authorize_net' => array( 'type' => 'D', 'interval' => 90, ),
                //'2checkout' => array( 'type' => 'D', 'interval' => 0, ),
                'paypal_pro' => array( 'type' => 'D', 'interval' => 0, ),
                'paddle' => array( 'type' => 'D', 'interval' => 0, ),
                //'paystack' => array( 'type' => 'D', 'interval' => 0, ),
            );

            $arm_pro_ration_supported_payment_gateways = apply_filters('arm_allow_pro_ration_supported_payment_gateway', $arm_pro_ration_supported_payment_gateways);
            if(!empty($payment_gateway))
            {
                $allowed_trial_days = !empty($arm_pro_ration_supported_payment_gateways['allow_auto_debit_trial_interval'][$payment_gateway]['interval']) ? $arm_pro_ration_supported_payment_gateways['allow_auto_debit_trial_interval'][$payment_gateway]['interval'] : 0;
                if(in_array($payment_gateway, $arm_pro_ration_supported_payment_gateways['allow_auto_debit_trial'])) {
    
                    if ( ($allowed_trial_days != 0 && $trial_days <= $allowed_trial_days) || $allowed_trial_days == 0 ) {     
                        $arm_pro_ration_supported_payment_gateways['arm_is_trial_allowed'] = 1;
                    }
                }
            }

            return $arm_pro_ration_supported_payment_gateways;
        }

        public function arm_pro_ration_cal_cost_base( $arm_return_data, $payment_gateway, $payment_gateway_options, $posted_data, $entry_id )
        {
            global $wpdb, $ARMember, $arm_subscription_plans, $arm_global_settings;

            $user_id = $arm_return_data['arm_entry_data']['arm_user_id'];
            $current_plan_id = $arm_return_data['arm_entry_data']['arm_entry_value']['arm_user_old_plan'];
            $new_plan_id = $arm_return_data['arm_plan_obj']->ID;

            $current_plan = $arm_subscription_plans->arm_get_subscription_plan($current_plan_id);
            $current_planData = get_user_meta($user_id, 'arm_user_plan_' . $current_plan_id, true);
            $new_plan = $arm_subscription_plans->arm_get_subscription_plan($new_plan_id);
            $general_settings = $arm_global_settings->global_settings;
            $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;

            $new_membership_cycle_id = !empty($arm_return_data['arm_entry_data']['arm_entry_value']['arm_payment_cycle_plan_'.$new_plan_id]) ? $arm_return_data['arm_entry_data']['arm_entry_value']['arm_payment_cycle_plan_'.$new_plan_id] :0;

            $current_membership_type = $current_planData['arm_current_plan_detail']['arm_subscription_plan_type'];
            
            
            $arm_cost_base_prorated_amount = $this->arm_get_pro_ration_cal_cost_base($user_id,$current_plan_id,$new_plan_id,$new_membership_cycle_id,$entry_id);

            $new_plan = !empty($arm_cost_base_prorated_amount['new_plan']) ? $arm_cost_base_prorated_amount['new_plan'] : $new_plan;

            $new_membership_type = $new_plan['arm_subscription_plan_type'];

            $arm_prorated_amount = $arm_cost_base_prorated_amount['pro_rata_amount'];

            $new_membership_length = $arm_cost_base_prorated_amount['new_membership_length'];

            $arm_trial_end_date = $arm_cost_base_prorated_amount['arm_trial_end_date'];
            
            $payment_mode = $arm_return_data['arm_payment_mode'];
            $is_payment_gateway_support = 1;
            if ($new_membership_type == 'recurring') {
                $date_format = $arm_global_settings->arm_get_wp_date_format();
                $startDate = current_datetime()->format($date_format);
                $endDate = $startDate;
                $endDate = strtotime($new_membership_length,strtotime($endDate));
                $interval = $endDate - strtotime($startDate);
                $trial_days = $interval/86400;
                $is_payment_gateway_support_allowed = $this->arm_check_payment_gateway_allow_pro_ration( $payment_gateway, $trial_days );
                $is_payment_gateway_support = !empty($is_payment_gateway_support_allowed['arm_is_trial_allowed']) ? $is_payment_gateway_support_allowed['arm_is_trial_allowed'] : 0;
                if(!empty($general_settings['arm_enable_reset_billing']))
                {
                    $arm_trial_end_date = $endDate;
                }
            }

            // $arm_return_data['arm_plan_obj']->amount = $arm_prorated_amount;
            /*if( $new_membership_type == 'recurring' && ( ( !empty($arm_return_data['arm_coupon_data'] ) && empty( $arm_return_data['arm_coupon_data']['arm_coupon_on_each_subscriptions'] ) ) ) ){
                $arm_return_data['arm_recurring_data']['amount'] = $arm_prorated_amount;
            }*/
            
            if ($is_payment_gateway_support) {

                $arm_prorated_amount = number_format((float)$arm_prorated_amount, $arm_currency_decimal, '.','');
                if (!empty($arm_return_data['arm_coupon_data']) && $arm_prorated_amount > 0 ) {
                    $arm_coupon_type = $arm_return_data['arm_coupon_data']['discount_type'];
                    if ($arm_coupon_type == 'percentage') {
                        $arm_coupon_amount = ($arm_prorated_amount * $arm_return_data['arm_coupon_data']['discount']) / 100;
                        $arm_prorated_amount = $arm_prorated_amount - number_format((float)$arm_coupon_amount, $arm_currency_decimal, '.','');
                    } else {
                        $arm_coupon_amount = $arm_return_data['arm_coupon_data']['discount'];
                        $arm_prorated_amount = $arm_prorated_amount - number_format((float)$arm_coupon_amount, $arm_currency_decimal, '.','');
                    }

                    $arm_return_data['arm_coupon_type']['coupon_amt'] = $arm_coupon_amount;
                    $arm_return_data['arm_coupon_type']['total_amt'] = $arm_prorated_amount;

                    $arm_return_data['arm_coupon_data']['coupon_amt'] = $arm_coupon_amount;
                    $arm_return_data['arm_coupon_data']['total_amt'] = $arm_prorated_amount;
                }
    
                if ($arm_prorated_amount <= 0) {
                    $arm_prorated_amount = 0;
                }
                if ($new_membership_type != 'recurring') {
                    $arm_return_data['arm_plan_obj']->amount = $arm_prorated_amount;
                }
                else
                {
                    $arm_plan_amt = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['cycle_amount'];
                    if(!empty($arm_return_data['arm_coupon_data']) && !empty($arm_return_data['arm_coupon_data']['arm_coupon_on_each_subscriptions']) )
                    {
                        $arm_coupon_type = $arm_return_data['arm_coupon_data']['discount_type'];
                        if ($arm_coupon_type == 'percentage') {
                            $arm_plan_coupon_amount = ($arm_plan_amt * $arm_return_data['arm_coupon_data']['discount']) / 100;
                            $arm_plan_amt = $arm_plan_amt - number_format((float)$arm_plan_coupon_amount, $arm_currency_decimal, '.','');
                        } else {
                            $arm_plan_coupon_amount = $arm_return_data['arm_coupon_data']['discount'];
                            $arm_plan_amt = $arm_plan_amt - number_format((float)$arm_plan_coupon_amount, $arm_currency_decimal, '.','');
                        }
                    }
                    $arm_return_data['arm_plan_obj']->amount = $arm_plan_amt;
                    $arm_return_data['arm_recurring_data']['amount'] = $arm_plan_amt;
                    $arm_return_data['arm_plan_obj']->amount = $arm_plan_amt;
                }
                $arm_return_data['arm_payable_amount'] = $arm_prorated_amount;
                $arm_return_data['arm_total_payable_amount'] = $arm_prorated_amount;
                $arm_return_data['arm_entry_data']['arm_entry_value']['arm_total_payable_amount'] = $arm_prorated_amount;
                $arm_return_data['arm_is_prorated'] = 1;
                $arm_return_data['arm_last_plan'] = $current_plan_id;
                if(!empty($general_settings['enable_tax']))
                {
                    $arm_return_data['arm_tax_data']['tax_percentage'] = 0;
                }

                if ($new_membership_type == 'recurring' && !empty($new_plan['arm_subscription_plan_options']['trial']) && in_array($current_membership_type,array('recurring','paid_finite') )) {
                    $arm_return_data['allow_trial'] = 1;
                    $arm_return_data['arm_trial_amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_recurring_data']['trial']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_recurring_data']['trial']['period'] = 'D';
                    $arm_return_data['arm_recurring_data']['trial']['interval'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_recurring_data']['trial']['type'] = 'Day';
                    $arm_return_data['arm_trial_data']['is_trial_period'] = 1;
                    $arm_return_data['arm_trial_data']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_trial_data']['days'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_trial_data']['type'] = 'D';
                    $arm_return_data['arm_plan_obj']->options['trial']['is_trial_period'] = 1;
                    $arm_return_data['arm_plan_obj']->options['trial']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_plan_obj']->options['trial']['days'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_plan_obj']->options['trial']['type'] = 'D';
                    $arm_return_data['arm_plan_obj']->arm_subscription_plan_options['trial']['is_trial_period'] = 1;
                    $arm_return_data['arm_plan_obj']->arm_subscription_plan_options['trial']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_plan_obj']->arm_subscription_plan_options['trial']['days'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_plan_obj']->arm_subscription_plan_options['trial']['type'] = 'D';
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['period'] = 'D';
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['interval'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['type'] = 'Day';
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['is_trial_period'] = 1;
                    
                    $arm_return_data['arm_trial_amount_for_not_in_plan_trial'] = $arm_prorated_amount;
                    $arm_return_data['arm_trial_amount_for_not_in_plan_trial_flag'] = 1;
                    $arm_return_data['arm_remained_days'] = ($trial_days > 0) ? $trial_days : 0;
                    $arm_return_data['arm_pro_rated_trial'] = 1;
                    $arm_return_data['arm_trial_end_date'] = $arm_trial_end_date;
                }
            }
            return $arm_return_data;
        }

        public function arm_pro_ration_cal_time_base( $arm_return_data, $payment_gateway, $payment_gateway_options, $posted_data, $entry_id )
        {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways;
            
            $user_id = $arm_return_data['arm_entry_data']['arm_user_id'];
            $current_plan_id = $arm_return_data['arm_entry_data']['arm_entry_value']['arm_user_old_plan'];
            $new_plan_id = $arm_return_data['arm_plan_id'];
            $general_settings = $arm_global_settings->global_settings;
            if (empty($current_plan_id) || $current_plan_id == 0) {
                return $arm_return_data;
            }

            $current_plan = $arm_subscription_plans->arm_get_subscription_plan($current_plan_id);
            $new_plan = $arm_subscription_plans->arm_get_subscription_plan($new_plan_id);

            $current_planData = get_user_meta($user_id, 'arm_user_plan_' . $current_plan_id, true);
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $starts_date = !empty($current_planData['arm_start_plan']) ? $current_planData['arm_start_plan'] : '';
            $started_date = !empty($current_planData['arm_started_plan_date']) ? $current_planData['arm_started_plan_date'] : '';
            $starts_on = !empty($starts_date) ? date_i18n($date_format, $starts_date) : '-';            
            if($started_date != '' && $started_date <= $starts_date) {
                $starts_on = date_i18n($date_format, $started_date);
            }

            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
            $current_membership_type = $current_planData['arm_current_plan_detail']['arm_subscription_plan_type'];

            if ($current_membership_type == 'free') {
                return $arm_return_data;
            } else if ($current_membership_type == 'paid_infinite') {
                return $this->arm_pro_ration_cal_cost_base($arm_return_data, $payment_gateway, $payment_gateway_options, $posted_data, $entry_id);
            } else if($current_membership_type == 'paid_finite') {
                $current_membership_length_type = $current_plan['arm_subscription_plan_options']['eopa']['type'];

                switch ($current_membership_length_type) {
                    case 'D':
                        $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['days'];
                        $current_membership_length_type = 'day';
                        break;
                    case 'W':
                        $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['weeks'];
                        $current_membership_length_type = 'week';
                        break;
                    case 'M':
                        $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['months'];
                        $current_membership_length_type = 'month';
                        break;
                    case 'Y':
                        $current_membership_length_period = $current_plan['arm_subscription_plan_options']['eopa']['years'];
                        $current_membership_length_type = 'year';
                        break;                    
                    default:
                        $current_membership_length_period = 0;
                        break;
                }
                if($current_membership_length_period > 1)
                {
                    $current_membership_length_type = $current_membership_length_type.'s';
                }
                $current_membership_length = "+" . $current_membership_length_period .' '. $current_membership_length_type;

                $current_membership_expires_on = !empty($current_planData['arm_expire_plan']) ? $current_planData['arm_expire_plan'] : 0;

            } else if ($current_membership_type == 'recurring') {
                $is_current_trial_active = $current_planData['arm_is_trial_plan'];
                if ($is_current_trial_active) {
                    $current_membership_length_type = $current_plan['arm_subscription_plan_options']['trial']['type'];

                } else {
                    $current_membership_cycle_id = $arm_return_data['arm_entry_data']['arm_entry_value']['arm_selected_payment_cycle'];

                    $current_membership_length_type = $current_plan['arm_subscription_plan_options']['payment_cycles'][$current_membership_cycle_id]['billing_type'];
                    $current_membership_length_period = $current_plan['arm_subscription_plan_options']['payment_cycles'][$current_membership_cycle_id]['billing_cycle'];
                }
                switch ($current_membership_length_type) {
                    case 'D':
                        $current_membership_length_type = 'day';
                        break;
                    case 'W':
                        $current_membership_length_type = 'week';
                        break;
                    case 'M':
                        $current_membership_length_type = 'month';
                        break;
                    case 'Y':
                        $current_membership_length_type = 'year';
                        break;                    
                    default:
                        $current_membership_length_type = '-';
                        break;
                }

                if ($is_current_trial_active) {
                    $current_membership_length_type =  $current_plan['arm_subscription_plan_options']['trial']['type'];
                    switch ($current_membership_length_type) {
                        case 'D':
                            $current_membership_length_type = 'day';
                            break;
                        case 'M':
                            $current_membership_length_type = 'month';
                            break;
                        case 'Y':
                            $current_membership_length_type = 'year';
                            break;                    
                        default:
                            $current_membership_length_type = 'day';
                            break;
                    }
                    $current_membership_length_period =  $current_plan['arm_subscription_plan_options']['trial'][$current_membership_length_type.'s'];
                }
                if($current_membership_length_period > 1)
                {
                    $current_membership_length_type = $current_membership_length_type.'s';
                }
                $current_membership_length = "+" . $current_membership_length_period .' '. $current_membership_length_type;

                if ($is_current_trial_active) { 
                    $current_membership_expires_on = !empty($current_planData['arm_trial_end']) ? $current_planData['arm_trial_end'] : 0;
                } else {
                    $current_membership_expires_on = !empty($current_planData['arm_next_due_payment']) ? $current_planData['arm_next_due_payment'] : 0;
                }

            } else {
                return $arm_return_data;
            }

            $new_membership_type = $new_plan['arm_subscription_plan_type'];

            $new_membership_cycle_id = !empty($arm_return_data['arm_entry_data']['arm_entry_value']['arm_payment_cycle_plan_'.$new_plan_id]) ? $arm_return_data['arm_entry_data']['arm_entry_value']['arm_payment_cycle_plan_'.$new_plan_id] : 0;

            $arm_time_base_prorated_amount = $this->arm_get_pro_ration_cal_time_base($user_id,$current_plan_id,$new_plan_id,$new_membership_cycle_id,$entry_id);

            $new_plan = !empty($arm_time_base_prorated_amount['new_plan']) ? $arm_time_base_prorated_amount['new_plan'] : $new_plan;

            $arm_prorated_amount = $arm_time_base_prorated_amount['pro_rata_amount'];

            $new_membership_length = $arm_time_base_prorated_amount['new_membership_length'];

            $seconds_until_expires_second = $arm_time_base_prorated_amount['seconds_until_expires_second'];

            $arm_trial_end_date = $arm_time_base_prorated_amount['arm_trial_end_date'];

            $trial_days = (int)($seconds_until_expires_second / 86400);

            $payment_mode = $arm_return_data['arm_payment_mode'];

            $is_payment_gateway_support = 1;
            if (!empty($general_settings['arm_enable_reset_billing']) && ($new_membership_type == 'recurring' && $payment_mode == 'auto_debit_subscription')) {
                $date_format = $arm_global_settings->arm_get_wp_date_format();
                $startDate = current_datetime()->format($date_format);
                $endDate = $startDate;
                $endDate = strtotime($new_membership_length,strtotime($endDate));
                $interval = $endDate - strtotime($startDate);
                $trial_days = $interval/86400;
                $arm_trial_end_date = $endDate;
            } else {
                $trial_days = (int)($seconds_until_expires_second / 86400);
                $arm_trial_end_date = strtotime('+'.$trial_days.' days',current_time('timestamp'));
            }
            if (!empty($trial_days) && $payment_mode == 'auto_debit_subscription' ) {
                $is_payment_gateway_support_allowed = $this->arm_check_payment_gateway_allow_pro_ration( $payment_gateway, $trial_days );
                $is_payment_gateway_support = !empty($is_payment_gateway_support_allowed['arm_is_trial_allowed']) ? $is_payment_gateway_support_allowed['arm_is_trial_allowed'] : 0;
            }

            if ($is_payment_gateway_support) {
                $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;
                $arm_prorated_amount = number_format((float)$arm_prorated_amount, $arm_currency_decimal, '.','');
                if (!empty($arm_return_data['arm_coupon_data']) && $arm_prorated_amount > 0 ) {
                    $arm_coupon_type = $arm_return_data['arm_coupon_data']['discount_type'];
                    if ($arm_coupon_type == 'percentage') {
                        $arm_coupon_amount = ($arm_prorated_amount * $arm_return_data['arm_coupon_data']['discount']) / 100;
                        $arm_prorated_amount = $arm_prorated_amount - number_format((float)$arm_coupon_amount, $arm_currency_decimal, '.','');
                    } else {
                        $arm_coupon_amount = $arm_return_data['arm_coupon_data']['discount'];
                        $arm_prorated_amount = $arm_prorated_amount - number_format((float)$arm_coupon_amount, $arm_currency_decimal, '.','');
                    }

                    $arm_return_data['arm_coupon_type']['coupon_amt'] = $arm_coupon_amount;
                    $arm_return_data['arm_coupon_type']['total_amt'] = $arm_prorated_amount;
		    
		    $arm_return_data['arm_coupon_data']['coupon_amt'] = $arm_coupon_amount;
                    $arm_return_data['arm_coupon_data']['total_amt'] = $arm_prorated_amount;
                }

                if($new_membership_type != 'recurring')
                {
                    $arm_return_data['arm_plan_obj']->amount = $arm_prorated_amount;
                }
                else
                {
                    $arm_plan_amt = $new_plan['arm_subscription_plan_options']['payment_cycles'][$new_membership_cycle_id]['cycle_amount'];
                    if(!empty($arm_return_data['arm_coupon_data']) && !empty($arm_return_data['arm_coupon_data']['arm_coupon_on_each_subscriptions']) )
                    {
                        $arm_coupon_type = $arm_return_data['arm_coupon_data']['discount_type'];
                        if ($arm_coupon_type == 'percentage') {
                            $arm_plan_coupon_amount = ($arm_plan_amt * $arm_return_data['arm_coupon_data']['discount']) / 100;
                            $arm_plan_amt = $arm_plan_amt - number_format((float)$arm_plan_coupon_amount, $arm_currency_decimal, '.','');
                        } else {
                            $arm_plan_coupon_amount = $arm_return_data['arm_coupon_data']['discount'];
                            $arm_plan_amt = $arm_plan_amt - number_format((float)$arm_plan_coupon_amount, $arm_currency_decimal, '.','');
                        }
                    }
                    $arm_return_data['arm_plan_obj']->amount = $arm_plan_amt;
                    $arm_return_data['arm_recurring_data']['amount'] = $arm_plan_amt;
                    $arm_return_data['arm_plan_obj']->amount = $arm_plan_amt;
                }
                //calculate with tax if tax module is active
                
                
                $arm_prorated_amount = number_format((float)$arm_prorated_amount, $arm_currency_decimal, '.','');
                
                if ($arm_prorated_amount <= 0) {
                    $arm_prorated_amount = 0;
                }

                $arm_return_data['arm_payable_amount'] = $arm_prorated_amount;
                $arm_return_data['arm_total_payable_amount'] = $arm_prorated_amount;
                $arm_return_data['arm_entry_data']['arm_entry_value']['arm_total_payable_amount'] = $arm_prorated_amount;
                $arm_return_data['arm_is_prorated'] = 1;
                $arm_return_data['arm_last_plan'] = $current_plan_id;
                if(!empty($general_settings['enable_tax']))
                {
                    if(isset($arm_return_data['arm_tax_data']['tax_percentage']))
                    {
                        $arm_return_data['arm_tax_data']['tax_percentage'] = 0;
                    }
                    else
                    {
                        $arm_return_data['arm_tax_data'] = array('tax_percentage' => 0);
                    }
                }
                if ($new_membership_type == 'recurring' && !empty($new_plan['arm_subscription_plan_options']['trial']) && in_array($current_membership_type,array('recurring','paid_finite') )) {

                    $arm_return_data['allow_trial'] = 1;
                    $arm_return_data['arm_trial_amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_trial_amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_recurring_data']['trial']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_recurring_data']['trial']['period'] = 'D';
                    $arm_return_data['arm_recurring_data']['trial']['interval'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_recurring_data']['trial']['type'] = 'Day';
                    $arm_return_data['arm_recurring_data']['trial']['is_trial_period'] = 1;
                    $arm_return_data['arm_trial_data']['is_trial_period'] = 1;
                    $arm_return_data['arm_trial_data']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_trial_data']['days'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_trial_data']['type'] = 'D';
                    $arm_return_data['arm_plan_obj']->options['trial']['is_trial_period'] = 1;
                    $arm_return_data['arm_plan_obj']->options['trial']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_plan_obj']->options['trial']['days'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_plan_obj']->options['trial']['type'] = 'D';
                    $arm_return_data['arm_plan_obj']->arm_subscription_plan_options['trial']['is_trial_period'] = 1;
                    $arm_return_data['arm_plan_obj']->arm_subscription_plan_options['trial']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_plan_obj']->arm_subscription_plan_options['trial']['days'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_plan_obj']->arm_subscription_plan_options['trial']['type'] = 'D';
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['amount'] = $arm_prorated_amount;
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['period'] = 'D';
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['interval'] = ($trial_days > 0) ? $trial_days : 1;
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['type'] = 'Day';
                    $arm_return_data['arm_plan_obj']->recurring_data['trial']['is_trial_period'] = 1;

                    $arm_return_data['arm_trial_amount_for_not_in_plan_trial'] = $arm_prorated_amount;
                    $arm_return_data['arm_trial_amount_for_not_in_plan_trial_flag'] = 1;
                    $arm_return_data['arm_remained_days'] = ($trial_days > 0) ? $trial_days : 0;
                    $arm_return_data['arm_pro_rated_trial'] = 1;
                    $arm_return_data['arm_trial_end_date'] = $arm_trial_end_date;
                }
            }
            return $arm_return_data;
        }

        function arm_update_member_subscription_date_func($message_type, $plan_id, $is_post_plan, $user_id )
        {
            global $wp,$wpdb,$ARMember,$arm_manage_communication,$arm_global_settings,$arm_subscription_plans,$arm_payment_gateways,$arm_members_class;

            if( !empty( $user_id ) )
            {
                $entry_data = $wpdb->get_row( $wpdb->prepare( "SELECT arm_entry_id, arm_plan_id, arm_entry_value FROM " . $ARMember->tbl_arm_entries ." WHERE arm_user_id=%d AND arm_plan_id=%d ORDER BY arm_entry_id DESC",$user_id,$plan_id ), ARRAY_A );
                //execute query $entry_data = 

                if(empty($entry_data))
                {
                    $arm_get_entry_id = get_user_meta($user_id,'arm_entry_id',true);
                    $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($arm_get_entry_id);

                }
                else {
                    $arm_get_entry_id = $entry_data['arm_entry_id'];
                }

                if( !empty($entry_data) )
                {

                    $entry_values = !empty($entry_data['arm_entry_value']) ? maybe_unserialize($entry_data['arm_entry_value']) : array();

                    $arm_return_data = !empty($entry_values['arm_return_data']) ? maybe_unserialize($entry_values['arm_return_data']) : array();
                    if(empty($arm_return_data))
                    {
                        $arm_return_data = !empty($entry_values['arm_return_data_used']) ? maybe_unserialize($entry_values['arm_return_data_used']) : array();
                    }
                    $last_plan_id = !empty($last_plan_ids[0]) ? $last_plan_ids[0] : 0;

                    if(!empty($arm_return_data) && !empty( $arm_return_data['arm_is_prorated'] ) )
                    {

                        $last_plan_id = !empty($arm_return_data['arm_last_plan']) ? $arm_return_data['arm_last_plan'] :0;
                        if(!empty($last_plan_id))
                        {
                            $general_settings = $arm_global_settings->global_settings;
                            $current_plan = get_user_meta( $user_id, 'arm_user_plan_'.$last_plan_id, true );
                            
                            $current_plan = maybe_unserialize( $current_plan );
                
                            $arm_enable_reset_billing = isset($general_settings['arm_enable_reset_billing']) ? $general_settings['arm_enable_reset_billing'] : 0;
    
                            $arm_pro_ration_method = isset($general_settings['arm_pro_ration_method']) ? $general_settings['arm_pro_ration_method'] : 'cost_base';
                
                            $old_plan = new ARM_Plan($last_plan_id);
                            $arm_plan_start_date = !empty($arm_return_data['arm_plan_start_date']) ? strtotime($arm_return_data['arm_plan_start_date']) : '';
                            
                            $new_plan = new ARM_Plan($plan_id);
                            if(!empty($current_plan) && $new_plan->is_recurring() && empty($arm_enable_reset_billing))
                            {
                                $payment_cycle = !empty($current_plan['arm_payment_cycle']) ? $current_plan['arm_payment_cycle'] : 0;
                                
                                $last_plan = $arm_subscription_plans->arm_get_subscription_plan($last_plan_id);
                                $is_upgraded_downgraded_plan = 0;
                    
                                if (!empty($last_plan['arm_subscription_plan_options']['upgrade_plans']) && in_array($plan_id, $last_plan['arm_subscription_plan_options']['upgrade_plans'])) {
                                    $is_upgraded_downgraded_plan = 1;
                                }
                    
                                if( empty($is_upgraded_downgraded_plan) && !empty($last_plan['arm_subscription_plan_options']['downgrade_plans']) && in_array($plan_id, $last_plan['arm_subscription_plan_options']['downgrade_plans']))
                                {
                                    $is_upgraded_downgraded_plan = 1;
                                }
                    
                                if($is_upgraded_downgraded_plan)
                                {            
                                    $arm_start_date = (!empty($current_plan['arm_start_plan'])) ? $current_plan['arm_start_plan'] : current_time('timestamp');
                                    $arm_end_date ='';
                                    $arm_rec_end_date= '';
                                    $new_plan = new ARM_Plan($plan_id);
                                    
                                    $planData = get_user_meta( $user_id, 'arm_user_plan_'.$plan_id, true);
                                    if($old_plan->is_recurring())
                                    {
                                        $old_plan_cycle = !empty($planData['arm_payment_cycle']) ? $planData['arm_payment_cycle'] : 0;
                                        $old_recurring_plan_options = $old_plan->prepare_recurring_data($old_plan_cycle);
                                        $recurring_time = !empty($old_recurring_plan_options['rec_time']) ? $old_recurring_plan_options['rec_time'] : '';
                                        $arm_rec_end_date = $current_plan['arm_next_due_payment'];
                                        if($recurring_time != 'infinite')
                                        {
                                            $arm_end_date = $current_plan['arm_expire_plan'];    
                                        }
                                    }
                                    else
                                    {
                                        $arm_rec_end_date = $current_plan['arm_expire_plan'];
                                        $arm_end_date = $current_plan['arm_expire_plan'];
                                    }
                
                                    //update subscription for user start date and end  date
                                    if( (!empty($arm_end_date) || !empty($arm_rec_end_date) ) && !empty($user_id) && !empty($plan_id) )
                                    {
                                        
                                        $planData = !empty($planData) ? $planData : array();
                                        $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                                        $planData = shortcode_atts($defaultPlanData, $planData);
                                        if(!empty($planData))
                                        {
                                            $planData = maybe_unserialize( $planData );                       
                                            $new_recurring_plan_options = $new_plan->prepare_recurring_data($payment_cycle);
                                            $recurring_time = !empty($new_recurring_plan_options['rec_time']) ? $new_recurring_plan_options['rec_time'] : '';
                                            $new_expire_plan_date = $new_plan->arm_plan_expire_time($arm_rec_end_date, $planData['arm_payment_mode'], $payment_cycle);
                                            if($new_plan->is_recurring())
                                            {
                                                $arm_activity_plan_end_date = !empty($arm_rec_end_date) ? date('Y-m-d H:i:s',$arm_rec_end_date) : '';
                                                if($recurring_time != 'infinite')
                                                {
                                                    $arm_activity_plan_end_date = date('Y-m-d H:i:s', $new_expire_plan_date);
                                                }
                                                $planData['arm_completed_recurring'] = 0;
                                                $planData['arm_next_due_payment'] = !empty($arm_rec_end_date) ? $arm_rec_end_date : '';
                                            }
                                            else
                                            {
                                                $arm_activity_plan_end_date = !empty($new_expire_plan_date) ? date('Y-m-d H:i:s', $new_expire_plan_date) : ''; 
                                            }
                                            $planData['arm_is_trial_plan'] = 1;
                                            $planData['arm_trial_start']=$arm_start_date;
                                            $planData['arm_trial_end']=$arm_rec_end_date;
                                            $arm_plan_start_date = $arm_rec_end_date;
                                            $planData['arm_start_plan'] = $arm_plan_start_date;
                                            $planData['arm_next_due_payment'] = !empty($arm_rec_end_date) ? $arm_rec_end_date : '';
                                            $planData['arm_expire_plan'] = $new_expire_plan_date;
                                            $planData['arm_pro_rated'] = 1;
                        
                                            $get_activity =$wpdb->get_row($wpdb->prepare("SELECT * FROM ".$ARMember->tbl_arm_activity .' WHERE arm_user_id=%d AND arm_item_id=%d AND arm_action="new_subscription"',$user_id,$plan_id),ARRAY_A);
                                            
                                            $arm_activity_plan_due_date = !empty($arm_rec_end_date) ? date('Y-m-d H:i:s', $arm_rec_end_date) : '';
                        
                                            $arm_content = maybe_unserialize( $get_activity['arm_content']);
                                            $arm_content['start']=$arm_rec_end_date;
                                            $arm_content['expire']= !empty($new_expire_plan_date) ? $new_expire_plan_date : '';
                                            
                                            $wpdb->update($ARMember->tbl_arm_activity,array('arm_content'=>maybe_serialize( $arm_content ),'arm_activity_plan_start_date'=>date('Y-m-d H:i:s', $arm_start_date),'arm_activity_plan_end_date'=>$arm_activity_plan_end_date,'arm_activity_plan_next_cycle_date'=>$arm_activity_plan_due_date),array('arm_user_id'=>$user_id,'arm_item_id'=>$plan_id));
                                            
                                            update_user_meta( $user_id, 'arm_user_plan_'.$plan_id, $planData);
                        
                                        }
                                    }
                                }
                            } else {

                                $payment_cycle = !empty($current_plan['arm_payment_cycle']) ? $current_plan['arm_payment_cycle'] : 0;

                                $planData = get_user_meta( $user_id, 'arm_user_plan_'.$plan_id, true);
                                
                                $planData['arm_start_plan'] = $arm_plan_start_date;

                                $payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : 'manual_subscription';

                                $arm_plan_due_date = $arm_plan_end_date = '';
                                if($new_plan->is_recurring())
                                {
                                    $arm_plan_due_date = $arm_members_class->arm_get_next_due_date($user_id, $plan_id, false, $payment_cycle,$arm_plan_start_date);
                                    $planData['arm_next_due_payment'] = $arm_plan_due_date;

                                    $expire_time = $new_plan->arm_plan_expire_time($arm_plan_start_date, $payment_mode, $payment_cycle);

                                    $planData['arm_expire_plan'] = $expire_time;

                                }
                                else
                                {
                                    if(!$new_plan->is_recurring() && !$new_plan->is_lifetime() && $new_plan->is_paid())
                                    {
                                        $expire_time = $new_plan->arm_plan_expire_time($arm_plan_start_date, $payment_mode, $payment_cycle);
    
                                        $planData['arm_expire_plan'] = $expire_time;
                                    }
                                }

                                $planData['arm_completed_recurring'] = 0;
                                update_user_meta( $user_id, 'arm_user_plan_'.$plan_id, $planData);
                            }
    
                            $arm_return_data['arm_is_prorated'] = 0; // update pro rated to 0 now.
    
                            $entry_values['arm_return_data']=maybe_serialize($arm_return_data);
                            $arm_updated_entry_values = maybe_serialize($entry_values);
                            
                            $wpdb->update($ARMember->tbl_arm_entries, array('arm_entry_value' => $arm_updated_entry_values), array('arm_entry_id' => $arm_get_entry_id));
                        }
                    }
                }
            }

            return $message_type;
        }

        function arm_update_upgrade_downgrade_action_func( $change_act )
        {
            global $arm_global_settings;
            if ($this->isProRationFeature && $arm_global_settings->global_settings['arm_enable_reset_billing'] == 1) {
                $change_act = 'immediate';
            }
            return $change_act;
        }
    }
}
global $arm_pro_ration_feature;
$arm_pro_ration_feature = new ARM_pro_ration_feature();