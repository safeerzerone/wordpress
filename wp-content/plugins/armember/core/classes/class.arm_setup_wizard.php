<?php
if (!class_exists('ARM_setup_Wizard')) {
    class ARM_setup_Wizard {
        function __construct() {
            add_action('wp_ajax_arm_verify_stripe_webhook_setup', array($this, 'arm_verify_stripe_webhook_setup'), 10, 2);
            add_action('wp_ajax_arm_complete_setup_data', array($this, 'arm_complete_setup_data'), 10, 2);
            add_action('wp_ajax_skip_setup_action',array($this,'skip_setup_action'),10,2);

            add_filter('arm_setup_wizard_license_section',array($this,'arm_setup_wizard_license_section_func'),10,1);

            add_filter('arm_pro_lincense_menu_item',array($this,'arm_pro_lincense_menu_item_func'),10,1);

            add_filter('arm_available_currencies',array($this,'arm_available_currencies_func'),10,1);

            add_filter('arm_setup_wizard_settings_section',array($this,'arm_setup_wizard_settings_section_func'),10,1);

            add_filter('arm_setup_wizard_payment_gateway_section',array($this,'arm_setup_wizard_payment_gateway_section_func'),10,1);
        }
        function skip_setup_action()
        {
            global $wp,$wpdb,$ARMember,$arm_slugs, $arm_capabilities_global;
	    
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_members'], '1',1);//phpcs:ignore --Reason:Verifying nonce

            update_option('arm_is_wizard_complete',1);
            $redirect_url =  admin_url('admin.php?page=' . $arm_slugs->manage_subscriptions); 
            $response = array('type'=>'success','msg'=>esc_html__('ARMember setup wizard completed','ARMember'),'redirect_url'=>$redirect_url);
            echo json_encode($response);
            die;
        }
        /** WIZARD SETUPS STARTS*/

        function arm_verify_stripe_webhook_setup()
        {
            global $arm_global_settings, $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_members'], '1',1); //phpcs:ignore --Reason:Verifying nonce

            $statusRes = array('type' => 'success', 'message' => esc_html__('Stripe Webhook has been verified successfully.', 'ARMember'));
            $secrate_key = (isset($_POST['secrate_key'])) ? sanitize_text_field($_POST['secrate_key']) : $secrate_key;//phpcs:ignore
            
            $url = "https://api.stripe.com/v1/webhook_endpoints";
            $arm_webhook_headers = array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $secrate_key
            );
            $arm_webhook_args = array(
                'headers' => $arm_webhook_headers,
            );
            $response = wp_remote_get($url, $arm_webhook_args);
            $resp = json_decode($response['body'],true);            
            
            $is_stripe_webhoook_added = $is_stripe_all_events_added = 0;
            $stripe_not_added_events = array();
            $stripe_default_events = array(
                'invoice.payment_succeeded', 'customer.subscription.updated', 'customer.subscription.deleted', 'invoice.payment_failed', 'customer.subscription.created', 'subscription_schedule.canceled'
            );
            $arm_stripe_webhook_url = $arm_global_settings->add_query_arg("arm-listener", "arm_stripe_api", ARM_HOME_URL . "/");
            if(!empty($resp['data'])){
                foreach($resp['data'] as $stripe_webhook_key => $stripe_webhook_val){
                    if($stripe_webhook_val['url'] == $arm_stripe_webhook_url){
                        $is_stripe_webhoook_added = 1;
                        $stripe_added_events = $stripe_webhook_val['enabled_events'];
                        $stripe_webhook_status = $stripe_webhook_val['status'];

                        if(is_array($stripe_added_events)) {

                            if(!empty($stripe_added_events[0]) && $stripe_added_events[0]=="*") {
                                $stripe_not_added_events = array();
                            }
                            else if($stripe_webhook_status == 'enabled'){
                                foreach($stripe_default_events as $stripe_events_key => $stripe_event_val){
                                    if(!in_array($stripe_event_val, $stripe_added_events)){
                                        //List of events which are not configued
                                        array_push($stripe_not_added_events,$stripe_event_val);
                                    }else{
                                        $is_stripe_all_events_added = 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if(!empty($resp['error']) && !empty($resp['error']['message'])) {
                $message = $resp['error']['message'];
                $statusRes = array('type' => 'error', 'message' => $message);
            }
            else 
            {
                if($is_stripe_webhoook_added != 1) {
                    //Webhook is not configued OR provided keys are invalid -- Error level 1
                    $message = '<ol><li>'.esc_html__("Webhook URL", 'ARMember') . " <code>" . $arm_stripe_webhook_url . "</code> " . esc_html__("not configured in stripe account. Please add Webhook at", "ARMember").' <a href="https://dashboard.stripe.com/" target="_blank">'.esc_html__('Stripe.com', 'ARMember').'</a> '. esc_html__("account -> Developers -> Webhooks page with specified events in ARMember", 'ARMember') . ' <a href="https://www.armemberplugin.com/documents/enable-interaction-with-stripe/#ARMStripeWebhooksDetails" target="_blank">'.esc_html__('Stripe Documentation', 'ARMember').'</a></li><li>'.esc_html__('Webhook is configued successfully then please verify provided Key details and Payment mode at your stripe', 'ARMember').' <a href="https://dashboard.stripe.com/apikeys" target="_blank">'.esc_html__('API Keys', 'ARMember').' </a>'.esc_html__("Page.", 'ARMember').'</li></ol>';

                    $statusRes = array('type' => 'error', 'message' => $message);
                }
                else if($stripe_webhook_status != 'enabled')
                {
                    //Webhook is not enabled - Error level 2 
                    $message = esc_html__("Webhook URL", 'ARMember') . " <code>" . $arm_stripe_webhook_url . "</code> " . esc_html__("is configured but disabled. Please enable it from", 'ARMember').' <a href="https://dashboard.stripe.com/" target="_blank">'.esc_html__('Stripe.com', 'ARMember').'</a> '.esc_html__("account -> Developers -> Webhooks -> Edit Webhook page.", 'ARMember');

                    $statusRes = array('type' => 'error', 'message' => $message);
                }
                else if($is_stripe_webhoook_added == 1 && !empty($stripe_not_added_events))
                {
                    //Webhook is configued but some event are not. - Error level 3
                    $arm_not_configued_events = implode(", ",$stripe_not_added_events);
                    $message = esc_html__("Webhook URL", 'ARMember') . " <code>" . $arm_stripe_webhook_url . "</code> " . esc_html__("is configured but below specified event(s) not enabled at your", 'ARMember').' <a href="https://dashboard.stripe.com/" target="_blank">'.esc_html__('Stripe.com', 'ARMember').'</a> '.esc_html__("account -> Developers -> Webhooks -> Edit Webhook page.", 'ARMember'). " <br><br>" . $arm_not_configued_events;

                    $statusRes = array('type' => 'error', 'message' => $message);
                }
            }
            echo json_encode($statusRes);
            die();
        }

        function arm_complete_setup_data(){
            global $wp,$wpdb,$arm_global_settings,$arm_payment_gateways,$arm_subscription_plans,$ARMember,$arm_access_rules,$arm_capabilities_global;
            $response = array('type'=>'error','msg'=>esc_html__('Something went wrong! please try again later','ARMember'));
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_members'], '1',1);//phpcs:ignore --Reason:Verifying nonce

            $posted_data = $_POST;//phpcs:ignore
            $all_global_settings = $arm_global_settings->arm_get_all_global_settings();
            $general_settings = $arm_global_settings->global_settings;
            // $all_general_settings = $all_global_settings['general_settings'];
            $default_rules = $arm_access_rules->arm_get_default_access_rules();
            if(empty($default_rules))
            {
                $default_rules = array();
            }
            $payment_gateways = get_option('arm_payment_gateway_settings');
            $general_settings['restrict_site_access'] = !empty($posted_data['arm_restrict_entire_website']) ? intval($posted_data['arm_restrict_entire_website']) : '';
            $default_rules['arm_allow_content_listing'] = !empty($posted_data['arm_post_page_listing']) ? $posted_data['arm_post_page_listing'] : '';
            $general_settings['user_register_verification'] = $posted_data['user_register_verification'];
            $general_settings['arm_new_signup_status'] = ($posted_data['user_register_verification'] != 'auto') ? 3 : 1;
            $general_settings['arm_anonymous_data'] = !empty($posted_data['arm_anonymous_data'])? intval($posted_data['arm_anonymous_data']) : 0;
            $general_settings['paymentcurrency'] = sanitize_text_field($posted_data['paymentcurrency']);
            $general_settings['arm_currency_decimal_digit'] = !empty($posted_data['arm_currency_decimal_digit']) ? intval($posted_data['arm_currency_decimal_digit']) : 2;
            
            $all_global_settings['general_settings'] = $general_settings;

            update_option('arm_global_settings', $all_global_settings);
            update_option('arm_default_rules', $default_rules);

            //payment gateways
            
            $payment_gateway = $posted_data['arm_selected_payment_gateway'];
            $payment_gateway_type = !empty($posted_data['payment_method_type']) ? $posted_data['payment_method_type']: '';
            $stripe_secret_key = !empty($posted_data['arm_stripe_secret_key']) ? $posted_data['arm_stripe_secret_key'] : '';
            $stripe_publishable_key = !empty($posted_data['arm_stripe_secret_key']) ? $posted_data['arm_stripe_secret_key'] : '';
            $is_webhook_verified = !empty($posted_data['stripe_webhook_verified']) ? $posted_data['stripe_webhook_verified'] : '';
            $paypal_merchant_email = !empty($posted_data['arm_paypal_merchant_email']) ? $posted_data['arm_paypal_merchant_email'] : '';
            $paypal_api_username = !empty($posted_data['arm_paypal_merchant_api_username']) ? $posted_data['arm_paypal_merchant_api_username'] : ''; 
            $paypal_api_password = !empty($posted_data['arm_paypal_merchant_api_password']) ? $posted_data['arm_paypal_merchant_api_password'] : ''; 
            $paypal_api_signature = !empty($posted_data['arm_paypal_merchant_api_signature']) ? $posted_data['arm_paypal_merchant_api_signature'] : ''; 
            $pay_gate_settings = $all_gateways = $payment_mode_arr =  array();
            $payment_gateway_data = $posted_data['arm_selected_payment_gateway'];
            foreach($payment_gateway_data as $payment_gateway => $payment_data)
            {
                $pg_setting = array();
                if($payment_gateway == 'stripe')
                {
                    $pg_setting['status'] = !empty($payment_data['status']) ? intval($payment_data['status']) : 0;
                    $stripe_secret_key = $payment_data['secret_key'];
                    $stripe_publishable_key = $payment_data['publish_key'];
                    $payment_gateway_type = ($payment_data['payment_method'] == 'sandbox') ? 'test' : 'live';
                    $pg_setting['stripe_payment_mode'] = $payment_gateway_type;
                    if($payment_gateway_type == 'test')
                    {
                        $pg_setting['stripe_test_secret_key'] = $stripe_secret_key;
                        $pg_setting['stripe_test_pub_key'] = $stripe_publishable_key;
                    }
                    else
                    {                       
                        $pg_setting['stripe_secret_key'] = $stripe_secret_key;
                        $pg_setting['stripe_pub_key'] = $stripe_publishable_key;
                    }
                    $pg_setting['stripe_webhook_verified'] = $is_webhook_verified;
                    
                    $pg_setting['stripe_payment_method'] = 'popup';
                }
                if($payment_gateway == 'paypal')
                {                   
                    $pg_setting['status']  = !empty($payment_data['status'])? intval($payment_data['status']) : 0;
                    $pg_setting['paypal_payment_mode'] = $payment_data['payment_method'];
                    $pg_setting['paypal_merchant_email'] = $payment_data['merchant_email'];
                    $paypal_api_username = $payment_data['api_username'];
                    $paypal_merchant_email = $payment_data['api_password'];
                    $paypal_merchant_signature = $payment_data['api_signature'];
                    if($payment_data['payment_method'] == 'sandbox')
                    {                        
                        $pg_setting['sandbox_api_username'] = $paypal_api_username;
                        $pg_setting['sandbox_api_password'] = $paypal_merchant_email;
                        $pg_setting['sandbox_api_signature'] = $paypal_merchant_signature;
                    }
                    else
                    {
                        $pg_setting['paypal_payment_mode'] = 'live';
                        $pg_setting['live_api_username'] = $paypal_api_username;
                        $pg_setting['live_api_password'] = $paypal_merchant_email;
                        $pg_setting['live_api_signature'] = $paypal_merchant_signature;
                    }
                }
                if($payment_gateway == 'bank_transfer' && !empty($payment_data['status']))
                {
                    $pg_setting['status'] = !empty($payment_data['status']) ? intval($payment_data['status']) : 0;
                    $transaction_id = !empty($payment_data['transaction_id']) ? 1 : 0 ;
                    $bank_name = !empty($payment_data['bank_name']) ? 1 : 0 ;
                    $account_name = !empty($payment_data['account_name']) ? 1 : 0 ;
                    $additional_info = !empty($payment_data['additional_info']) ? 1 : 0 ;
                    $transfer_mode = !empty($payment_data['transaction_id']) ? 1 : 0 ;
                    $digital_transfer_label =!empty($payment_data['digital_transfer_label']) ? $payment_data['digital_transfer_label'] : '';
                    $cheque_label =!empty($payment_data['cheque_label']) ? $payment_data['cheque_label'] : '';
                    $cash_label =!empty($payment_data['cash_label']) ? $payment_data['cash_label'] : '';
                    $pg_setting['fields']= array(
                        'transaction_id' => $transaction_id,
                        'bank_name' => $bank_name,
                        'account_name' => $account_name,
                        'additional_info' => $additional_info,
                        'transfer_mode' => $transfer_mode,
                        'transfer_mode_option'=> $payment_data['transfer_mode_option'],
                        'transfer_mode_option_label' => array(
                            'bank_transfer'=>$digital_transfer_label,
                            'cheque'=>$cheque_label,
                            'cash'=>$cash_label
                        ),
                    );
                    
                }

                $pay_gate_settings[$payment_gateway] = $pg_setting;
                if(!empty($payment_data['status']) && $payment_data['status'] == 1)
                {
                    array_push($all_gateways,$payment_gateway);
                    $payment_mode = 'manual_subscription';
                    if($payment_gateway=='paypal')
                    {
                        $payment_mode = 'both';
                    }
                    else if($payment_gateway=='stripe')
                    {
                        $payment_mode = 'both';
                    }
                    $payment_mode_arr[$payment_gateway] = $payment_mode;
                }
                
                
            }
            
            update_option('arm_payment_gateway_settings',$pay_gate_settings);
            //membership plans
            $arm_membership_plan_name = $posted_data['arm_membership_plan_name'];
            $subscription_type = $posted_data['arm_subscription_plan_type'];
            $subscription_amount = !empty($posted_data['arm_membership_plan_amount'])? $posted_data['arm_membership_plan_amount'] : 0;
            $data = array('action'=>'add','plan_name'=>$arm_membership_plan_name,'plan_status'=>1,'arm_subscription_plan_type'=>$subscription_type,'arm_subscription_plan_amount'=>$subscription_amount);
            
            $plan_id = $this->create_subscription_plans($data);

            //setups
            $setup_name = $posted_data['arm_membership_setup_name'];
            $setup_modules = array(
                'modules'=>array(
                    'plans' => array($plan_id),
                    'forms' => 101, 
                    'gateways' => $all_gateways,
                    'payment_mode' => $payment_mode_arr,
                    'coupon'=>0,
                    'plans_order' => array($plan_id => 1),
                    'gateways_order' => array('paypal' => 1,'stripe' => 2,'authorize_net' => 3,'2checkout' => 4,'bank_transfer' => 5),
                ),
                'style'=>array(
                    'plan_skin' => 'skin3',
                    'plan_area_position' => 'before',
                    'gateway_skin' => 'radio',
                    'content_width' => 800,
                    'form_position' => 'center',
                    'font_family' => 'Poppins',
                    'title_font_size' => 20,
                    'title_font_bold' => 1,
                    'title_font_italic' => '',
                    'title_font_decoration' => '',
                    'description_font_size' => 15,
                    'description_font_bold' => 0,
                    'description_font_italic' =>'' ,
                    'description_font_decoration' => '',
                    'price_font_size' => 28,
                    'price_font_bold' => 0,
                    'price_font_italic' => '',
                    'price_font_decoration' => '',
                    'summary_font_size' => 16,
                    'summary_font_bold' => 0,
                    'summary_font_italic' => '',
                    'summary_font_decoration' => '',
                    'plan_title_font_color' => '#2C2D42',
                    'plan_desc_font_color' => '#555F70',
                    'price_font_color' => '#2C2D42',
                    'summary_font_color' => '#555F70',
                    'selected_plan_title_font_color' => '#005AEE',
                    'selected_plan_desc_font_color' => '#2C2D42',
                    'selected_price_font_color' => '#FFFFFF',
                    'bg_active_color' => '#005AEE',
                ),
                'plans_columns'=>3,
                'selected_plan'=>$plan_id,
                'cycle_columns'=>1,
                'gateways_columns'=>1,
                'custom_css'=>'',
            );
            $setup_labels = array('button_labels' => array('submit' => 'Submit','coupon_title' => 'Enter Coupon Code','coupon_button' => 'Apply','next' => 'Next','previous' => 'Previous'),
            'member_plan_field_title' => 'Select Membership Plan',
            'payment_cycle_section_title' => 'Select Your Payment Cycle',
            'payment_cycle_field_title' => 'Select Your Payment Cycle',
            'payment_section_title' => 'Select Your Payment Gateway',
            'payment_gateway_field_title' => 'Select Your Payment Gateway',
            'payment_gateway_labels' => array(
                    'paypal' => 'Paypal',
                    'stripe' => 'Stripe',
                    'authorize_net' => 'Authorize.net',
                    '2checkout' => '2Checkout',
                    'bank_transfer' => 'Bank Transfer',
            ),
        
            'payment_mode_selection' => 'How you want to pay?',
            'automatic_subscription' => 'Auto Debit Payment',
            'semi_automatic_subscription' => 'Manual Payment',
            'credit_card_logos' => '',
            'summary_text' => '<div>Payment Summary</div><br/><div>Your currently selected plan : <strong>[PLAN_NAME]</strong>,  Plan Amount : <strong>[PLAN_AMOUNT]</strong> </div><div>Coupon Discount Amount : <strong>[DISCOUNT_AMOUNT]</strong>, Final Payable Amount: <strong>[PAYABLE_AMOUNT]</strong> </div>'
        );
            $db_data = array(
                'arm_setup_name' => $setup_name,
                'arm_setup_modules' => maybe_serialize($setup_modules),
                'arm_setup_labels' => maybe_serialize($setup_labels),
                'arm_setup_type' => 0
            );
            $db_data['arm_status'] = 1;
            $db_data['arm_created_date'] = date('Y-m-d H:i:s');
            /* Insert Form Fields. */
            $wpdb->insert($ARMember->tbl_arm_membership_setup, $db_data);
            $setup_id = $wpdb->insert_id;
            /* Action After Adding Setup Details */
            do_action('arm_saved_membership_setup', $setup_id, $db_data);

            $create_setup_page = array(
                    'post_title' => 'Setup',
                    'post_name' => 'setup',
                    'post_content' => '[arm_setup id="' . $setup_id . '" hide_title="1"]',
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    'post_author' => 1,
                    'post_type' => 'page',
            );
            $page_id = wp_insert_post($create_setup_page);
            $setup_page_url = get_permalink($page_id);


            
            //access_rules IDS
            $arm_allowed_access_pages= !empty($posted_data['arm_access_rules_pages_ids']) ? $posted_data['arm_access_rules_pages_ids'] : '';
            if(!empty($arm_allowed_access_pages))
            {
                foreach($arm_allowed_access_pages as $page_id)
                {
                    update_post_meta($page_id,'arm_access_plan',$plan_id);
                }
            }
            update_option('arm_is_wizard_complete',1);
            $response = array('type'=>'success','msg'=>esc_html__('ARMember setup wizard completed','ARMember'),'setup_url'=>$setup_page_url);
            echo json_encode($response);
            die;
        }

        function create_subscription_plans($posted_data=array())
        {
            global $wp,$wpdb,$ARMember,$arm_global_settings,$arm_access_rules;
            if (isset($posted_data) && !empty($posted_data) && in_array($posted_data['action'], array('add', 'update'))) {
                
                $plan_name = (!empty($posted_data['plan_name'])) ? sanitize_text_field($posted_data['plan_name']) : esc_html__('Untitled Plan', 'ARMember');
                $plan_description = (!empty($posted_data['plan_description'])) ? $posted_data['plan_description'] : '';
                $plan_status = (!empty($posted_data['plan_status']) && $posted_data['plan_status'] != 0) ? 1 : 0;
                $plan_role = (!empty($posted_data['plan_role'])) ? sanitize_text_field($posted_data['plan_role']) : get_option('default_role');
                $plan_type = (!empty($posted_data['arm_subscription_plan_type'])) ? sanitize_text_field($posted_data['arm_subscription_plan_type']) : 'free';
                $payment_type = $plan_amount = $stripe_plan = '';
                $plan_options = $plan_payment_gateways = array();
                if ($plan_type != 'free') {
                    $plan_options = (!empty($posted_data['arm_subscription_plan_options'])) ? $posted_data['arm_subscription_plan_options'] : array();

                    $plan_options['access_type'] = (!empty($plan_options['access_type'])) ? $plan_options['access_type'] : 'lifetime';
                    $plan_options['payment_type'] = (!empty($plan_options['payment_type'])) ? $plan_options['payment_type'] : 'one_time';

                    if ($plan_type == 'paid_finite') {
                        $plan_options['access_type']='finite';
                        $plan_options['expiry_type'] = 'joined_date_expiry';
                        $plan_options['eopa'] =array(
                            'days' => 1,
                            'weeks' => 1,
                            'months' => 1,
                            'years' => 1,
                            'type' => 'M'
                        );
                    } else {
                        unset($plan_options['expiry_type']);
                        unset($plan_options["expiry_date"]);
                        unset($plan_options["eopa"]);
                    }

                    if ($plan_type == 'paid_infinite') {
                        //unset($plan_options['upgrade_action']);
                        //unset($plan_options['downgrade_action']);
                        //unset($plan_options['enable_upgrade_downgrade_action']);
                        unset($plan_options['grace_period']);
                        unset($plan_options['eot']);
                        //unset($plan_options['upgrade_plans']);
                        //unset($plan_options['downgrade_plans']);
                    }

                    if ($plan_options['payment_type'] == "one_time") {
                        $plan_options['trial'] = array();
                    }

                    $plan_amount = (!empty($posted_data['arm_subscription_plan_amount'])) ? $posted_data['arm_subscription_plan_amount'] : 0;
                    
                    if ($plan_type == 'recurring') {
                        $plan_options['access_type']='finite';
                        $plan_options['payment_type'] = 'subscription';
                        
                        $manual_billing_start = (!empty($plan_options['recurring'])) ? $plan_options['recurring']['manual_billing_start'] : 'transaction_day';
                        $plan_options['payment_cycles'][0] = array(
                            'cycle_key'=>'arm0',
                            'cycle_label' => '',
                            'cycle_amount'=>$plan_amount,
                            'billing_cycle'=>1,
                            'billing_type'=>'M',
                            'recurring_time'=>'infinite',
                            'payment_cycle_order'=>1
                        );
                        $plan_options['recurring'] = array(
                            'days' => 1,
                            'months' => 1,
                            'years' => 1,
                            'type' => 'M',
                            'time' => 'infinite',
                            'manual_billing_start' => 'transaction_day',
                        );
                        $plan_options['cancel_action'] = 'block';
                        $plan_options['cancel_plan_action'] = 'on_expire';
                        $plan_options['eot'] = 'block';
                        $plan_options['grace_period'] = array(
                                'end_of_term' => 0,
                                'failed_payment' => 2
                        );
                    } else {
                        unset($plan_options['payment_cycles']);
                        unset($plan_options['recurring']);
                        unset($plan_options['trial']);
                        unset($plan_options['cancel_action']);
                        unset($plan_options['cancel_plan_action']);
                        unset($plan_options['payment_failed_action']);
                    }
                }
                $plan_options['pricetext'] = isset($posted_data['arm_subscription_plan_options']['pricetext']) ? $posted_data['arm_subscription_plan_options']['pricetext'] : esc_html__('Free Membership', 'ARMember');
                $plan_options = apply_filters('arm_befor_save_field_membership_plan', $plan_options, $posted_data);
                $subscription_plans_data = array(
                    'arm_subscription_plan_name' => $plan_name,
                    'arm_subscription_plan_description' => $plan_description,
                    'arm_subscription_plan_status' => $plan_status,
                    'arm_subscription_plan_type' => $plan_type,
                    'arm_subscription_plan_options' => maybe_serialize($plan_options),
                    'arm_subscription_plan_amount' => $plan_amount,
                    'arm_subscription_plan_role' => $plan_role,
                );
                if ($posted_data['action'] == 'add') {
                    $subscription_plans_data['arm_subscription_plan_created_date'] = date('Y-m-d H:i:s');
                    //Insert Form Fields.

                    $wpdb->insert($ARMember->tbl_arm_subscription_plans, $subscription_plans_data);
                    $plan_id = $wpdb->insert_id;
                    //Action After Adding Plan
                    do_action('arm_saved_subscription_plan', $plan_id, $subscription_plans_data);
                    $inherit_plan_id = isset($posted_data['arm_inherit_plan_rules']) ? intval($posted_data['arm_inherit_plan_rules']) : 0;
                    if (!empty($plan_id) && $plan_id != 0 && !empty($inherit_plan_id) && $inherit_plan_id != 0) {
                        $arm_access_rules->arm_inherit_plan_rules($plan_id, $inherit_plan_id);
                    }
                    return $plan_id;
                }
            }
        }

        function arm_setup_wizard_license_section_func($arm_setup_wizard_license_section){
            global $arm_members_activity,$check_sorting;
            $arm_lic_domain = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : ''; //phpcs:ignore
            $setact = 0;
            $setact = $arm_members_activity->$check_sorting();
            $is_debug_enable = 0;
            $pcodeinfo = "";
            $pcodedate = "";
            $pcodedateexp = "";
            $pcodelastverified = "";
            $pcodecustemail = "";
            if($setact != 1 && $is_debug_enable==0){
            $arm_setup_wizard_license_section = '<div class="arm-wizard-setup-container arm-ws-is-lic-page arm-lic-activatation-wapper arm_setup_wizard_page_1 arm_license_activation " id="arm_license_activation">
                <div class="arm-ws-account-setup">
                    <div class="arm-ws-acco-logo">
                        <a href="https://www.armemberplugin.com/" target="_blank">
                            <img class="arm-ws-acc-img" src="'. MEMBERSHIPLITE_IMAGES_URL.'/arm-logo-icon.webp" alt="ARMember">
                        </a>
                    </div>
                    <div class="arm-ws-acc-content">
                        <h2 class="arm-ws-acc-heding">'. esc_html__('Account Setup','ARMember').'</h2>
                        <p class="arm-ws-acc-disc">'. esc_html__('Complete simple steps to get started.','ARMember').'</p>
                    </div>
                </div>
                
                <div class="arm-ws-steps-belt">
                
                    <div class="arm-ws-step-box arm-ws-step-activate">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                            <svg class="arm-ws-step-activate-svg" width="18" style="display:block; margin: 0 auto;" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10.015 2.12646C12.0388 4.01646 14.6963 4.92646 17.4488 5.58396C17.3175 11.5602 14.7025 16.5452 9.99502 18.1865C7.54377 17.3927 5.69252 15.724 4.44127 13.4527C3.22127 11.2402 2.61627 8.47521 2.55127 5.60021C5.44502 5.16146 8.02877 4.12521 10.0138 2.12646H10.015ZM13.3375 7.47772L8.77877 11.4677L6.69127 9.38272L5.80752 10.2665L8.72002 13.179L14.1625 8.41772L13.3375 7.47772Z" fill="#637799"/>
                            </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('License','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                                <svg class="arm-ws-step-activate-svg" width="18" style="display:block; margin: 0 auto;" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M17.5 9.9627V7.9627L15.075 7.1627L14.5125 5.8002L15.6125 3.5002L14.2 2.0877L11.9375 3.2252L10.575 2.6627L9.7125 0.262695H7.7125L6.925 2.6877L5.5375 3.2502L3.2375 2.1502L1.825 3.5627L2.9625 5.8252L2.4 7.1877L0 8.0377V10.0252L2.425 10.8252L2.9875 12.1877L1.8875 14.4877L3.3 15.9002L5.5625 14.7627L6.925 15.3252L7.7875 17.7252H9.775L10.5625 15.3002L11.95 14.7377L14.25 15.8377L15.6625 14.4252L14.5125 12.1627L15.1 10.8002L17.5 9.93769V9.9627ZM8.75 12.7502C6.675 12.7502 5 11.0752 5 9.00019C5 6.9252 6.675 5.2502 8.75 5.2502C10.825 5.2502 12.5 6.9252 12.5 9.00019C12.5 11.0752 10.825 12.7502 8.75 12.7502Z" fill="#637799"/>
                                </svg>
                            </span>
                            
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('General Options','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                                <svg class="arm-ws-step-activate-svg" style="display:block; margin: 0 auto;" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13.6154 0H4.38462C3.22174 0 2.1065 0.461949 1.28422 1.28422C0.461949 2.1065 0 3.22174 0 4.38462V13.6154C0 14.7783 0.461949 15.8935 1.28422 16.7158C2.1065 17.5381 3.22174 18 4.38462 18H13.6154C14.7783 18 15.8935 17.5381 16.7158 16.7158C17.5381 15.8935 18 14.7783 18 13.6154V4.38462C18 3.22174 17.5381 2.1065 16.7158 1.28422C15.8935 0.461949 14.7783 0 13.6154 0ZM7.64308 12.2585L5.79692 14.1046C5.66711 14.2343 5.49115 14.3071 5.30769 14.3071C5.12423 14.3071 4.94827 14.2343 4.81846 14.1046L3.89538 13.1815C3.82737 13.1182 3.77281 13.0417 3.73497 12.9568C3.69713 12.8719 3.67679 12.7802 3.67515 12.6873C3.67351 12.5943 3.69061 12.502 3.72543 12.4158C3.76024 12.3296 3.81207 12.2512 3.87781 12.1855C3.94355 12.1198 4.02186 12.0679 4.10806 12.0331C4.19427 11.9983 4.2866 11.9812 4.37956 11.9828C4.47252 11.9845 4.56419 12.0048 4.64911 12.0427C4.73403 12.0805 4.81047 12.1351 4.87385 12.2031L5.30769 12.6369L6.66462 11.28C6.79585 11.1577 6.96943 11.0911 7.14879 11.0943C7.32814 11.0975 7.49927 11.1701 7.62611 11.297C7.75295 11.4238 7.82561 11.5949 7.82878 11.7743C7.83194 11.9536 7.76537 12.1272 7.64308 12.2585ZM7.64308 4.87385L5.79692 6.72C5.66711 6.84965 5.49115 6.92247 5.30769 6.92247C5.12423 6.92247 4.94827 6.84965 4.81846 6.72L3.89538 5.79692C3.7731 5.66568 3.70652 5.4921 3.70968 5.31275C3.71285 5.13339 3.78551 4.96227 3.91235 4.83543C4.03919 4.70858 4.21032 4.63593 4.38967 4.63276C4.56903 4.6296 4.74261 4.69617 4.87385 4.81846L5.30769 5.25231L6.66462 3.89538C6.79585 3.7731 6.96943 3.70652 7.14879 3.70968C7.32814 3.71285 7.49927 3.78551 7.62611 3.91235C7.75295 4.03919 7.82561 4.21032 7.82878 4.38967C7.83194 4.56903 7.76537 4.74261 7.64308 4.87385ZM13.6154 13.3846H10.8462C10.6625 13.3846 10.4865 13.3117 10.3566 13.1818C10.2268 13.052 10.1538 12.8759 10.1538 12.6923C10.1538 12.5087 10.2268 12.3326 10.3566 12.2028C10.4865 12.0729 10.6625 12 10.8462 12H13.6154C13.799 12 13.9751 12.0729 14.1049 12.2028C14.2348 12.3326 14.3077 12.5087 14.3077 12.6923C14.3077 12.8759 14.2348 13.052 14.1049 13.1818C13.9751 13.3117 13.799 13.3846 13.6154 13.3846ZM13.6154 6H10.8462C10.6625 6 10.4865 5.92706 10.3566 5.79723C10.2268 5.66739 10.1538 5.4913 10.1538 5.30769C10.1538 5.12408 10.2268 4.94799 10.3566 4.81816C10.4865 4.68832 10.6625 4.61538 10.8462 4.61538H13.6154C13.799 4.61538 13.9751 4.68832 14.1049 4.81816C14.2348 4.94799 14.3077 5.12408 14.3077 5.30769C14.3077 5.4913 14.2348 5.66739 14.1049 5.79723C13.9751 5.92706 13.799 6 13.6154 6Z" fill="#637799"/>
                                </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('Membership Plan','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                                <svg class="arm-ws-step-activate-svg" width="18" style="display:block; margin: 0 auto;" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15.2009 13.2254C15.2009 14.3638 14.8307 15.3441 14.0904 16.1663C13.3501 16.9885 12.3884 17.4963 11.2054 17.6897V19.6429C11.2054 19.747 11.1719 19.8326 11.1049 19.8996C11.0379 19.9665 10.9524 20 10.8482 20H9.34152C9.24479 20 9.16109 19.9647 9.0904 19.894C9.01972 19.8233 8.98437 19.7396 8.98437 19.6429V17.6897C8.4933 17.6228 8.01897 17.5074 7.56138 17.3438C7.10379 17.1801 6.72619 17.0145 6.42857 16.8471C6.13095 16.6797 5.85565 16.5011 5.60268 16.3114C5.3497 16.1217 5.17671 15.9821 5.08371 15.8929C4.9907 15.8036 4.9256 15.7366 4.88839 15.692C4.7619 15.5357 4.75446 15.3832 4.86607 15.2344L6.01562 13.7277C6.06771 13.6533 6.15327 13.6086 6.27232 13.5938C6.38393 13.5789 6.47321 13.6124 6.54018 13.6942L6.5625 13.7165C7.40327 14.4531 8.30729 14.9182 9.27455 15.1116C9.54985 15.1711 9.82515 15.2009 10.1004 15.2009C10.7031 15.2009 11.2333 15.0409 11.6908 14.721C12.1484 14.401 12.3772 13.9472 12.3772 13.3594C12.3772 13.151 12.3214 12.9539 12.2098 12.7679C12.0982 12.5818 11.9736 12.4256 11.8359 12.2991C11.6983 12.1726 11.4807 12.0331 11.183 11.8806C10.8854 11.7281 10.6399 11.609 10.4464 11.5234C10.253 11.4379 9.95536 11.317 9.55357 11.1607C9.26339 11.0417 9.0346 10.9487 8.86719 10.8817C8.69978 10.8147 8.47098 10.7161 8.1808 10.5859C7.89062 10.4557 7.65811 10.3404 7.48326 10.24C7.30841 10.1395 7.09821 10.0074 6.85268 9.84375C6.60714 9.68006 6.40811 9.52195 6.25558 9.36942C6.10305 9.21689 5.94122 9.0346 5.77009 8.82255C5.59896 8.61049 5.46689 8.39472 5.37388 8.17522C5.28088 7.95573 5.20275 7.70833 5.13951 7.43304C5.07626 7.15774 5.04464 6.86756 5.04464 6.5625C5.04464 5.53571 5.40923 4.63542 6.13839 3.86161C6.86756 3.0878 7.81622 2.58929 8.98437 2.36607V0.357143C8.98437 0.260417 9.01972 0.176711 9.0904 0.106027C9.16109 0.0353423 9.24479 0 9.34152 0H10.8482C10.9524 0 11.0379 0.0334821 11.1049 0.100446C11.1719 0.167411 11.2054 0.252976 11.2054 0.357143V2.32143C11.6295 2.36607 12.0406 2.45164 12.4386 2.57812C12.8367 2.70461 13.1603 2.82924 13.4096 2.95201C13.6589 3.07478 13.8951 3.21429 14.1183 3.37054C14.3415 3.52679 14.4866 3.63467 14.5536 3.6942C14.6205 3.75372 14.6763 3.8058 14.721 3.85045C14.8475 3.98438 14.8661 4.12574 14.7768 4.27455L13.8728 5.90402C13.8132 6.01562 13.7277 6.07515 13.6161 6.08259C13.5119 6.10491 13.4115 6.07887 13.3147 6.00446C13.2924 5.98214 13.2385 5.9375 13.1529 5.87054C13.0673 5.80357 12.9222 5.70499 12.7176 5.57478C12.513 5.44457 12.2954 5.32552 12.0647 5.21763C11.8341 5.10975 11.5569 5.01302 11.2333 4.92746C10.9096 4.84189 10.5915 4.79911 10.279 4.79911C9.57217 4.79911 8.99554 4.95908 8.54911 5.27902C8.10268 5.59896 7.87946 6.0119 7.87946 6.51786C7.87946 6.71131 7.91109 6.88988 7.97433 7.05357C8.03757 7.21726 8.14732 7.37165 8.30357 7.51674C8.45982 7.66183 8.60677 7.7846 8.74442 7.88505C8.88207 7.98549 9.0904 8.10082 9.36942 8.23103C9.64844 8.36124 9.87351 8.46168 10.0446 8.53237C10.2158 8.60305 10.4762 8.70536 10.8259 8.83929C11.2202 8.9881 11.5216 9.10528 11.7299 9.19085C11.9382 9.27641 12.221 9.40662 12.5781 9.58147C12.9353 9.75632 13.2161 9.91443 13.4208 10.0558C13.6254 10.1972 13.856 10.3832 14.1127 10.6138C14.3694 10.8445 14.5666 11.0807 14.7042 11.3225C14.8419 11.5644 14.9591 11.849 15.0558 12.1763C15.1525 12.5037 15.2009 12.8534 15.2009 13.2254Z" fill="#637799"/>
                                </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('Payment Options','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                                <svg class="arm-ws-step-activate-svg" width="18" style="display:block; margin: 0 auto;" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2972_7739)">
                                        <path d="M10.5416 8.33333C9.85408 6.39167 8.00825 5 5.83325 5C3.07075 5 0.833252 7.2375 0.833252 10C0.833252 12.7625 3.07075 15 5.83325 15C8.00825 15 9.85408 13.6083 10.5416 11.6667H14.1666V15H17.4999V11.6667H19.1666V8.33333H10.5416ZM5.83325 11.6667C4.91242 11.6667 4.16659 10.9208 4.16659 10C4.16659 9.07917 4.91242 8.33333 5.83325 8.33333C6.75409 8.33333 7.49992 9.07917 7.49992 10C7.49992 10.9208 6.75409 11.6667 5.83325 11.6667Z" fill="#637799"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2972_7739">
                                            <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('Content Access','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                                <svg class="arm-ws-step-activate-svg" width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.00007 9.89051C2.04546 9.87493 2.03478 9.82954 2.04342 9.7985C2.35381 8.68316 3.66188 8.32595 4.48772 9.13906C5.26014 9.89957 6.022 10.6708 6.7885 11.4373C6.88833 11.5371 6.9896 11.6356 7.08678 11.7378C7.12965 11.783 7.16348 11.7963 7.20492 11.7392C7.22078 11.7174 7.24296 11.7001 7.26229 11.6808C9.32991 9.61319 11.3983 7.54638 13.4643 5.47712C13.7718 5.16918 14.1313 4.98564 14.5699 5.00088C15.1689 5.02171 15.614 5.30529 15.8609 5.85265C16.1076 6.39967 16.0201 6.91668 15.6468 7.38489C15.6044 7.43811 15.5553 7.48629 15.5071 7.53461C13.0821 9.95993 10.6572 12.3852 8.23172 14.81C7.73003 15.3115 7.08705 15.4322 6.49784 15.1393C6.34975 15.0656 6.21977 14.9677 6.10305 14.8509C4.88904 13.6366 3.67509 12.4222 2.46039 11.2086C2.2565 11.0048 2.1093 10.7699 2.0407 10.4876C2.03416 10.4609 2.0424 10.4202 2 10.4098C2.00007 10.2366 2.00007 10.0636 2.00007 9.89051Z" fill="#637799"/>
                                </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('Complete','ARMember').'</div>
                    </div>
                </div>
                <div class="arm-ws-license-part-content arm-ws-account-setup">
                    <div class="arm-ws-lic-con-heding-area">
                        <div class="arm-lic-con-page-count">01</div>
                        <div class="arm-lic-con-page-info">
                            <h2 class="arm-lic-page-heding">'. esc_html__('License Activation','ARMember').'</h2>
                            <p class="arm-lic-page-disc">'. esc_html__('After activating the plugin, activate your license for support and enable the automatic upgrades. Also, without license activation, you won\'t be able to use any inbuilt addons of ARMember','ARMember').'</p>
                        </div>
                    </div>
                    <div class="arm-lic-page-content-wrapper arm-gen-otp-content-wapper">
                        <div class="arm-lic-page-content">
                            <label class="arm-form-table-label">'. esc_html__('Customer Name','ARMember').' <span>*</span></label>
                                <div class="arm-form-table-content arm-df__form-field-wrap_text arm-df__form-field-wrap arm-controls ">
                                    <input id="arm_lic_activation_name" class="arm-lic-sectext-field" type="text" name="arm_lic_activation_name" placeholder="Enter your full name" value="" aria-required="true" data-msg-required="'. esc_html__('Please Enter Customer Name','ARMember').'" required>
                                    <span id="arm_lic_activation_name-error" class="error arm_invalid"></span>        
                                </div>
                        </div>
                        <div class="arm-lic-page-content">
                            <label class="arm-form-table-label">'. esc_html__('Customer Email','ARMember').' <span>*</span></label>
                                <div class="arm-form-table-content">
                                    <input id="arm_lic_activation_email" class="arm-lic-sectext-field" type="text" name="arm_lic_activation_email" placeholder="Enter your Email Address" value="" aria-required="true" data-msg-required="'. esc_html__('Please Enter Customer Email','ARMember').'" data-validation-regex-regex="^.+@.+\..+$" data-validation-regex-message="Please enter valid email address." required>
                                    <span id="arm_lic_activation_email-error" class="error arm_invalid"></span>
                                </div>
                        </div>
                        <div class="arm-lic-page-content">
                            <label class="arm-form-table-label">'. esc_html__('Purchase Code','ARMember').' <span>*</span></label>
                            <div class="arm-form-table-content">
                                <input id="arm_lic_activation_key" class="arm-lic-sectext-field" type="text" name="arm_lic_activation_key" placeholder="Enter Purchase Code" value="" aria-required="true" data-msg-required="'. esc_html__('Please Enter Purchase Code','ARMember').'" required>';
                                $purchase_help = esc_html__("To get more information about how to get purchase code, please", 'ARMember') . " <a href='https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code' rel='noopener' target='_blank'>" . esc_html__('click here', 'ARMember') . "</a> ";
                                $arm_setup_wizard_license_section .= '<i class="arm_helptip_icon armfa armfa-question-circle" title="'. esc_attr($purchase_help).'"></i>
                                <span id="arm_lic_activation_key-error" class="error arm_invalid"></span>
                            </div>';
                            $wpnonce = wp_create_nonce( 'arm_wp_nonce' );
                            $arm_setup_wizard_license_section .= '<input type="hidden" name="arm_wp_nonce" value="'. esc_attr($wpnonce).'"/>
                        </div>
                        <div class="arm-lic-page-content" style="display:contents;">
                            <input id="arm_domain_name" class="arm-lic-sectext-field" type="hidden" name="arm_domain_name" placeholder="Enter Purchase Code" value="'. $_SERVER["SERVER_NAME"].'">
                            <button class="arm-wsc-btn arm-wsc-btn--primary arm-ws-next-btn arm_submit_validate_license" type="button">
                                '. esc_html__('Activate License','ARMember').'
                            </button>
                            <span id="license_loader" style="display:none;padding-left:10px"><img src="'. MEMBERSHIP_IMAGES_URL . '/loading_activation.gif" height="15" /></span>
                            <span id="arm_lic_activation-error" class="error arm_invalid" style="width:auto;margin-left:10px"></span>
                        </div>
                    </div>
                </div>

                <div class="arm-ws-footer-wrapper">
                    <div class="arm-ws-footer-left">
                    <a href="https://youtu.be/WhKgS2jv2xM" target="_blank" class="arm-wsc-btn arm-wsc-btn--primary arm-youtube-btn">
                            <img src="'. MEMBERSHIPLITE_IMAGES_URL.'/arm-youtube-icon.webp" alt="ARMember">
                            '. esc_html__('Watch Tutorial','ARMember').'
                        </a>
                    </div>
                    <div class="arm-ws-footer-right">
                        <button type="button" class="arm-wsc-btn arm-wsc-btn--primary arm-ws-back-btn arm_skip_license">
                            '.esc_html__('Skip','ARMember').'
                        </button>
                        <button type="button" class="arm-wsc-btn arm-wsc-btn--primary arm-ws-next-btn ">
                            '. esc_html__('Continue','ARMember').'
                            <img src="'. MEMBERSHIPLITE_IMAGES_URL.'/arm-lp-long-right-arrow-icon.png" alt="ARMember">
                        </button>
                    </div>
                </div>
            </div>';
            }

            $arm_setup_wizard_license_section .= '<input type="hidden" value="'. esc_attr($setact).'" name="arm_package_actvted"/>';
            if($setact == 1){
                $get_purchased_info = get_option('armSortInfo');
                $get_purchased_val = get_option('armSortOrder');

                $sortorderval = base64_decode($get_purchased_info);
                $ordering = array();
                if (is_array($ordering)) {
                    $ordering = explode("^", $sortorderval);
                    if (is_array($ordering)) {
                        if (isset($ordering[0]) && $ordering[0] != "") {
                            $pcodeinfo = $ordering[0];
                        } else {
                            $pcodeinfo = "";
                        }
                        if (isset($ordering[1]) && $ordering[1] != "") {
                            $pcodedate = $ordering[1];
                        } else {
                            $pcodedate = "";
                        }
                        if (isset($ordering[2]) && $ordering[2] != "") {
                            $pcodedateexp = $ordering[2];
                        } else {
                            $pcodedateexp = "";
                        }
                        if (isset($ordering[3]) && $ordering[3] != "") {
                            $pcodelastverified = $ordering[3];
                        } else {
                            $pcodelastverified = "";
                        }
                        if (isset($ordering[4]) && $ordering[4] != "") {
                            $pcodecustemail = $ordering[4];
                        } else {
                            $pcodecustemail = "";
                        }
                    }
                }
            }
            $arm_setup_wizard_license_section_css = ($setact == 0) ? "display:none;" : '';
            $arm_setup_wizard_license_section .= '<div class="arm-wizard-setup-container arm-ws-is-lic-page arm_setup_wizard_page_1 arm_package_actvted " id="arm_setup_wizard_page_1" style="'.$arm_setup_wizard_license_section_css.'">
                <div class="arm-ws-account-setup">
                    <div class="arm-ws-acco-logo">
                        <a href="https://www.armemberplugin.com/" target="_blank">
                            <img class="arm-ws-acc-img" src="'. MEMBERSHIPLITE_IMAGES_URL.'/arm-logo-icon.webp" alt="ARMember">
                        </a>
                    </div>
                    <div class="arm-ws-acc-content">
                        <h2 class="arm-ws-acc-heding">'. esc_html__('Account Setup','ARMember').'</h2>
                        <p class="arm-ws-acc-disc">'. esc_html__('Complete simple steps to get started.','ARMember').'</p>
                    </div>
                </div>
                
                <div class="arm-ws-steps-belt">
                
                    <div class="arm-ws-step-box arm-ws-step-activate">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                            <svg class="arm-ws-step-activate-svg" width="18" style="display:block; margin: 0 auto;" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10.015 2.12646C12.0388 4.01646 14.6963 4.92646 17.4488 5.58396C17.3175 11.5602 14.7025 16.5452 9.99502 18.1865C7.54377 17.3927 5.69252 15.724 4.44127 13.4527C3.22127 11.2402 2.61627 8.47521 2.55127 5.60021C5.44502 5.16146 8.02877 4.12521 10.0138 2.12646H10.015ZM13.3375 7.47772L8.77877 11.4677L6.69127 9.38272L5.80752 10.2665L8.72002 13.179L14.1625 8.41772L13.3375 7.47772Z" fill="#637799"/>
                            </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('License','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                                <svg class="arm-ws-step-activate-svg" width="18" style="display:block; margin: 0 auto;" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M17.5 9.9627V7.9627L15.075 7.1627L14.5125 5.8002L15.6125 3.5002L14.2 2.0877L11.9375 3.2252L10.575 2.6627L9.7125 0.262695H7.7125L6.925 2.6877L5.5375 3.2502L3.2375 2.1502L1.825 3.5627L2.9625 5.8252L2.4 7.1877L0 8.0377V10.0252L2.425 10.8252L2.9875 12.1877L1.8875 14.4877L3.3 15.9002L5.5625 14.7627L6.925 15.3252L7.7875 17.7252H9.775L10.5625 15.3002L11.95 14.7377L14.25 15.8377L15.6625 14.4252L14.5125 12.1627L15.1 10.8002L17.5 9.93769V9.9627ZM8.75 12.7502C6.675 12.7502 5 11.0752 5 9.00019C5 6.9252 6.675 5.2502 8.75 5.2502C10.825 5.2502 12.5 6.9252 12.5 9.00019C12.5 11.0752 10.825 12.7502 8.75 12.7502Z" fill="#637799"/>
                                </svg>
                            </span>
                            
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('General Options','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                                <svg class="arm-ws-step-activate-svg" style="display:block; margin: 0 auto;" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13.6154 0H4.38462C3.22174 0 2.1065 0.461949 1.28422 1.28422C0.461949 2.1065 0 3.22174 0 4.38462V13.6154C0 14.7783 0.461949 15.8935 1.28422 16.7158C2.1065 17.5381 3.22174 18 4.38462 18H13.6154C14.7783 18 15.8935 17.5381 16.7158 16.7158C17.5381 15.8935 18 14.7783 18 13.6154V4.38462C18 3.22174 17.5381 2.1065 16.7158 1.28422C15.8935 0.461949 14.7783 0 13.6154 0ZM7.64308 12.2585L5.79692 14.1046C5.66711 14.2343 5.49115 14.3071 5.30769 14.3071C5.12423 14.3071 4.94827 14.2343 4.81846 14.1046L3.89538 13.1815C3.82737 13.1182 3.77281 13.0417 3.73497 12.9568C3.69713 12.8719 3.67679 12.7802 3.67515 12.6873C3.67351 12.5943 3.69061 12.502 3.72543 12.4158C3.76024 12.3296 3.81207 12.2512 3.87781 12.1855C3.94355 12.1198 4.02186 12.0679 4.10806 12.0331C4.19427 11.9983 4.2866 11.9812 4.37956 11.9828C4.47252 11.9845 4.56419 12.0048 4.64911 12.0427C4.73403 12.0805 4.81047 12.1351 4.87385 12.2031L5.30769 12.6369L6.66462 11.28C6.79585 11.1577 6.96943 11.0911 7.14879 11.0943C7.32814 11.0975 7.49927 11.1701 7.62611 11.297C7.75295 11.4238 7.82561 11.5949 7.82878 11.7743C7.83194 11.9536 7.76537 12.1272 7.64308 12.2585ZM7.64308 4.87385L5.79692 6.72C5.66711 6.84965 5.49115 6.92247 5.30769 6.92247C5.12423 6.92247 4.94827 6.84965 4.81846 6.72L3.89538 5.79692C3.7731 5.66568 3.70652 5.4921 3.70968 5.31275C3.71285 5.13339 3.78551 4.96227 3.91235 4.83543C4.03919 4.70858 4.21032 4.63593 4.38967 4.63276C4.56903 4.6296 4.74261 4.69617 4.87385 4.81846L5.30769 5.25231L6.66462 3.89538C6.79585 3.7731 6.96943 3.70652 7.14879 3.70968C7.32814 3.71285 7.49927 3.78551 7.62611 3.91235C7.75295 4.03919 7.82561 4.21032 7.82878 4.38967C7.83194 4.56903 7.76537 4.74261 7.64308 4.87385ZM13.6154 13.3846H10.8462C10.6625 13.3846 10.4865 13.3117 10.3566 13.1818C10.2268 13.052 10.1538 12.8759 10.1538 12.6923C10.1538 12.5087 10.2268 12.3326 10.3566 12.2028C10.4865 12.0729 10.6625 12 10.8462 12H13.6154C13.799 12 13.9751 12.0729 14.1049 12.2028C14.2348 12.3326 14.3077 12.5087 14.3077 12.6923C14.3077 12.8759 14.2348 13.052 14.1049 13.1818C13.9751 13.3117 13.799 13.3846 13.6154 13.3846ZM13.6154 6H10.8462C10.6625 6 10.4865 5.92706 10.3566 5.79723C10.2268 5.66739 10.1538 5.4913 10.1538 5.30769C10.1538 5.12408 10.2268 4.94799 10.3566 4.81816C10.4865 4.68832 10.6625 4.61538 10.8462 4.61538H13.6154C13.799 4.61538 13.9751 4.68832 14.1049 4.81816C14.2348 4.94799 14.3077 5.12408 14.3077 5.30769C14.3077 5.4913 14.2348 5.66739 14.1049 5.79723C13.9751 5.92706 13.799 6 13.6154 6Z" fill="#637799"/>
                                </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('Membership Plan','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                                <svg class="arm-ws-step-activate-svg" width="18" style="display:block; margin: 0 auto;" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15.2009 13.2254C15.2009 14.3638 14.8307 15.3441 14.0904 16.1663C13.3501 16.9885 12.3884 17.4963 11.2054 17.6897V19.6429C11.2054 19.747 11.1719 19.8326 11.1049 19.8996C11.0379 19.9665 10.9524 20 10.8482 20H9.34152C9.24479 20 9.16109 19.9647 9.0904 19.894C9.01972 19.8233 8.98437 19.7396 8.98437 19.6429V17.6897C8.4933 17.6228 8.01897 17.5074 7.56138 17.3438C7.10379 17.1801 6.72619 17.0145 6.42857 16.8471C6.13095 16.6797 5.85565 16.5011 5.60268 16.3114C5.3497 16.1217 5.17671 15.9821 5.08371 15.8929C4.9907 15.8036 4.9256 15.7366 4.88839 15.692C4.7619 15.5357 4.75446 15.3832 4.86607 15.2344L6.01562 13.7277C6.06771 13.6533 6.15327 13.6086 6.27232 13.5938C6.38393 13.5789 6.47321 13.6124 6.54018 13.6942L6.5625 13.7165C7.40327 14.4531 8.30729 14.9182 9.27455 15.1116C9.54985 15.1711 9.82515 15.2009 10.1004 15.2009C10.7031 15.2009 11.2333 15.0409 11.6908 14.721C12.1484 14.401 12.3772 13.9472 12.3772 13.3594C12.3772 13.151 12.3214 12.9539 12.2098 12.7679C12.0982 12.5818 11.9736 12.4256 11.8359 12.2991C11.6983 12.1726 11.4807 12.0331 11.183 11.8806C10.8854 11.7281 10.6399 11.609 10.4464 11.5234C10.253 11.4379 9.95536 11.317 9.55357 11.1607C9.26339 11.0417 9.0346 10.9487 8.86719 10.8817C8.69978 10.8147 8.47098 10.7161 8.1808 10.5859C7.89062 10.4557 7.65811 10.3404 7.48326 10.24C7.30841 10.1395 7.09821 10.0074 6.85268 9.84375C6.60714 9.68006 6.40811 9.52195 6.25558 9.36942C6.10305 9.21689 5.94122 9.0346 5.77009 8.82255C5.59896 8.61049 5.46689 8.39472 5.37388 8.17522C5.28088 7.95573 5.20275 7.70833 5.13951 7.43304C5.07626 7.15774 5.04464 6.86756 5.04464 6.5625C5.04464 5.53571 5.40923 4.63542 6.13839 3.86161C6.86756 3.0878 7.81622 2.58929 8.98437 2.36607V0.357143C8.98437 0.260417 9.01972 0.176711 9.0904 0.106027C9.16109 0.0353423 9.24479 0 9.34152 0H10.8482C10.9524 0 11.0379 0.0334821 11.1049 0.100446C11.1719 0.167411 11.2054 0.252976 11.2054 0.357143V2.32143C11.6295 2.36607 12.0406 2.45164 12.4386 2.57812C12.8367 2.70461 13.1603 2.82924 13.4096 2.95201C13.6589 3.07478 13.8951 3.21429 14.1183 3.37054C14.3415 3.52679 14.4866 3.63467 14.5536 3.6942C14.6205 3.75372 14.6763 3.8058 14.721 3.85045C14.8475 3.98438 14.8661 4.12574 14.7768 4.27455L13.8728 5.90402C13.8132 6.01562 13.7277 6.07515 13.6161 6.08259C13.5119 6.10491 13.4115 6.07887 13.3147 6.00446C13.2924 5.98214 13.2385 5.9375 13.1529 5.87054C13.0673 5.80357 12.9222 5.70499 12.7176 5.57478C12.513 5.44457 12.2954 5.32552 12.0647 5.21763C11.8341 5.10975 11.5569 5.01302 11.2333 4.92746C10.9096 4.84189 10.5915 4.79911 10.279 4.79911C9.57217 4.79911 8.99554 4.95908 8.54911 5.27902C8.10268 5.59896 7.87946 6.0119 7.87946 6.51786C7.87946 6.71131 7.91109 6.88988 7.97433 7.05357C8.03757 7.21726 8.14732 7.37165 8.30357 7.51674C8.45982 7.66183 8.60677 7.7846 8.74442 7.88505C8.88207 7.98549 9.0904 8.10082 9.36942 8.23103C9.64844 8.36124 9.87351 8.46168 10.0446 8.53237C10.2158 8.60305 10.4762 8.70536 10.8259 8.83929C11.2202 8.9881 11.5216 9.10528 11.7299 9.19085C11.9382 9.27641 12.221 9.40662 12.5781 9.58147C12.9353 9.75632 13.2161 9.91443 13.4208 10.0558C13.6254 10.1972 13.856 10.3832 14.1127 10.6138C14.3694 10.8445 14.5666 11.0807 14.7042 11.3225C14.8419 11.5644 14.9591 11.849 15.0558 12.1763C15.1525 12.5037 15.2009 12.8534 15.2009 13.2254Z" fill="#637799"/>
                                </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('Payment Options','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                                <svg class="arm-ws-step-activate-svg" width="18" style="display:block; margin: 0 auto;" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2972_7739)">
                                        <path d="M10.5416 8.33333C9.85408 6.39167 8.00825 5 5.83325 5C3.07075 5 0.833252 7.2375 0.833252 10C0.833252 12.7625 3.07075 15 5.83325 15C8.00825 15 9.85408 13.6083 10.5416 11.6667H14.1666V15H17.4999V11.6667H19.1666V8.33333H10.5416ZM5.83325 11.6667C4.91242 11.6667 4.16659 10.9208 4.16659 10C4.16659 9.07917 4.91242 8.33333 5.83325 8.33333C6.75409 8.33333 7.49992 9.07917 7.49992 10C7.49992 10.9208 6.75409 11.6667 5.83325 11.6667Z" fill="#637799"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2972_7739">
                                            <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('Content Access','ARMember').'</div>
                    </div>
                
                    <div class="arm-ws-step-box">
                        <div class="arm-ws-steps-icon-wrapper">
                            <span>
                            <svg class="arm-ws-step-activate-svg" width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.00007 9.89051C2.04546 9.87493 2.03478 9.82954 2.04342 9.7985C2.35381 8.68316 3.66188 8.32595 4.48772 9.13906C5.26014 9.89957 6.022 10.6708 6.7885 11.4373C6.88833 11.5371 6.9896 11.6356 7.08678 11.7378C7.12965 11.783 7.16348 11.7963 7.20492 11.7392C7.22078 11.7174 7.24296 11.7001 7.26229 11.6808C9.32991 9.61319 11.3983 7.54638 13.4643 5.47712C13.7718 5.16918 14.1313 4.98564 14.5699 5.00088C15.1689 5.02171 15.614 5.30529 15.8609 5.85265C16.1076 6.39967 16.0201 6.91668 15.6468 7.38489C15.6044 7.43811 15.5553 7.48629 15.5071 7.53461C13.0821 9.95993 10.6572 12.3852 8.23172 14.81C7.73003 15.3115 7.08705 15.4322 6.49784 15.1393C6.34975 15.0656 6.21977 14.9677 6.10305 14.8509C4.88904 13.6366 3.67509 12.4222 2.46039 11.2086C2.2565 11.0048 2.1093 10.7699 2.0407 10.4876C2.03416 10.4609 2.0424 10.4202 2 10.4098C2.00007 10.2366 2.00007 10.0636 2.00007 9.89051Z" fill="#637799"/>
                                </svg>
                            </span>
                        </div>
                        <div class="arm-ws-steps-text">'. esc_html__('Complete','ARMember').'</div>
                    </div>
                </div>
                <div class="arm-ws-license-part-content arm-ws-account-setup">
                    <div class="arm-ws-lic-con-heding-area">
                        <div class="arm-lic-con-page-count">01</div>
                        <div class="arm-lic-con-page-info">
                            <h2 class="arm-lic-page-heding">'. esc_html__('License Activation','ARMember').'</h2>
                            <p class="arm-lic-page-disc">'. esc_html__('After activating the plugin, activate your license for support and enable the automatic upgrades. Also, without license activation, you won\'t be able to use any inbuilt addons of ARMember','ARMember').'</p>
                        </div>
                    </div>
                    <div class="arm-lic-page-content-wrapper arm-gen-otp-content-wapper arm-lic-opt-com-wapper">
                        <div class="arm-lic-comp-summary">
                            <div>
                            <img class="arm-ws-acc-img" src="'. MEMBERSHIP_IMAGES_URL.'/arm-lic-sucess-line-left.webp" alt="ARMember">
                            <img class="arm-ws-acc-img" src="'. MEMBERSHIP_IMAGES_URL.'/arm-lic-sucess-icon.webp" alt="ARMember">
                            <img class="arm-ws-acc-img" src="'. MEMBERSHIP_IMAGES_URL.'/arm-lic-sucess-line-right.webp" alt="ARMember">
                            </div>
                        </div>
                        <div class="arm-ws-lic-comp-text">
                            <h2><div class="arm_license_date">'. esc_html__('License activated on','ARMember').' '. esc_html($arm_lic_domain).'</div></h1>
                        </div>
                        <div class="arm-lic-detail-part">
                            <div class="arm-li-customer-name">
                                <p>'. esc_html__('Customer Name','ARMember').'</p>
                                <h3 class="arm_customer_name">-</h3>
                            </div>
                            <div class="arm-li-customer-name">
                                <p>'. esc_html__('Customer Email','ARMember').'</p>
                                <h3 class="arm_customer_email">'. esc_html($pcodecustemail).'</h3>
                            </div>
                            <div class="arm-li-customer-name">
                                <p>'. esc_html__('Purchase Code','ARMember').'</p>
                                <h3 class="arm_license_key">'. esc_html($pcodeinfo).'</h3>
                            </div>';
                            $wpnonce = wp_create_nonce( 'arm_wp_nonce' );
                            $arm_setup_wizard_license_section .= '<input type="hidden" name="arm_wp_nonce" value="'. esc_attr($wpnonce).'"/>
                        </div>
                    </div>
                </div>

                <div class="arm-ws-footer-wrapper">
                    <div class="arm-ws-footer-left">
                    <a href="https://youtu.be/WhKgS2jv2xM" target="_blank" class="arm-wsc-btn arm-wsc-btn--primary arm-youtube-btn">
                            <img src="'. MEMBERSHIPLITE_IMAGES_URL.'/arm-youtube-icon.webp" alt="ARMember">
                            '. esc_html__('Watch Tutorial','ARMember').'
                    </a>
                    </div>
                    <div class="arm-ws-footer-right">
                        <button type="button" class="arm-wsc-btn arm-wsc-btn--primary arm-ws-next-btn">
                            '. esc_html__('Continue','ARMember').'
                            <img src="'. MEMBERSHIPLITE_IMAGES_URL.'/arm-lp-long-right-arrow-icon.png" alt="ARMember">
                        </button>
                    </div>
                </div>
            </div>';
            return $arm_setup_wizard_license_section;
        }

        function arm_pro_lincense_menu_item_func($arm_pro_lincense_menu_item){
            $arm_pro_lincense_menu_item = '<div class="arm-ws-step-box arm-ws-step-activate">
                <div class="arm-ws-steps-icon-wrapper">
                    <span>
                    <svg class="arm-ws-step-activate-svg" width="18" style="display:block; margin: 0 auto;" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.015 2.12646C12.0388 4.01646 14.6963 4.92646 17.4488 5.58396C17.3175 11.5602 14.7025 16.5452 9.99502 18.1865C7.54377 17.3927 5.69252 15.724 4.44127 13.4527C3.22127 11.2402 2.61627 8.47521 2.55127 5.60021C5.44502 5.16146 8.02877 4.12521 10.0138 2.12646H10.015ZM13.3375 7.47772L8.77877 11.4677L6.69127 9.38272L5.80752 10.2665L8.72002 13.179L14.1625 8.41772L13.3375 7.47772Z" fill="#637799"/>
                    </svg>
                    </span>
                </div>
                <div class="arm-ws-steps-text">'. esc_html__('License','ARMember').'</div>
            </div>';
            return $arm_pro_lincense_menu_item;
        }

        function arm_available_currencies_func($currencies){
            global $arm_global_settings,$arm_payment_gateways;
            
            $currencies = array_merge( $arm_payment_gateways->currency['stripe'], $arm_payment_gateways->currency['authorize_net'], $arm_payment_gateways->currency['2checkout']);
            return $currencies;
        }

        function arm_setup_wizard_settings_section_func($arm_setup_wizard_settings_section){
            global $arm_global_settings;
            $general_settings = $arm_global_settings->global_settings;
            $arm_setup_wizard_settings_section = '<div class="arm-lic-page-content">
				<label for="country" class="arm-lic-new-user-approv-dd">'. esc_html__('Number of Decimals','ARMember').' </label>
					<div class="arm_form_fields_wrapper">
						<div class="arm-df__form-field-wrap_select arm-df__form-field-wrap arm-controls " id="arm-df__form-field-wrap_countryoAbEb4GP0H">';
                            $general_settings['arm_currency_decimal_digit'] = (isset($general_settings['arm_currency_decimal_digit']) && $general_settings['arm_currency_decimal_digit']!= '') ? $general_settings['arm_currency_decimal_digit'] : 2;
							$arm_setup_wizard_settings_section .= '<input type="hidden" id="arm_currency_decimal_digit" name="arm_currency_decimal_digit" value="'. esc_attr($general_settings['arm_currency_decimal_digit']).'" />
							<dl class="arm_selectbox column_level_dd" id="arm_currency_decimal">
								<dt><span class="arm_no_auto_complete">'. esc_attr($general_settings['arm_currency_decimal_digit']).'</span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
								<dd>
									<ul data-id="arm_currency_decimal_digit">
										<li data-label="0" data-value="0">0</li>
										<li data-label="1" data-value="1">1</li>
										<li data-label="2" data-value="2">2</li>
										<li data-label="3" data-value="3">3</li>
									</ul>
								</dd>
							</dl>
						</div>
					</div>
			</div>
			<div class="arm-lic-page-content">
				<label class="arm_primary_status" for="arm_restrict_entire_website">'. esc_html__('Restrict Entire Website Without Login','ARMember').'</label>
				<div class="arm_position_relative">
					<div class="armswitch arm_member_status_div">
						<input type="checkbox" id="arm_restrict_entire_website" value="1" class="armswitch_input" name="arm_restrict_entire_website">
						<label for="arm_restrict_entire_website" class="armswitch_label arm_primary_status_check_label"></label>
					</div>
				</div>
			</div>
			<div class="arm-lic-page-content">
				<label class="arm_primary_status" for="arm_post_page_listing">'. esc_html__('Allow Restricted Pages/Posts in Listing','ARMember').'</label>
				<div class="arm_position_relative">
					<div class="armswitch arm_member_status_div">
						<input type="checkbox" id="arm_post_page_listing" value="1" class="armswitch_input" name="arm_post_page_listing">
						<label for="arm_post_page_listing" class="armswitch_label arm_primary_status_check_label"></label>
					</div>
				</div>
			</div>';
            return $arm_setup_wizard_settings_section;
        }

        function arm_setup_wizard_payment_gateway_section_func($arm_setup_wizard_payment_gateway_section){
            $arm_setup_wizard_payment_gateway_section ='<div class=arm_payment_gateway_section>
				<div class="armswitch arm_payment_setting_switch">
					<input type="checkbox" id="arm_setup_stripe_status" value="1" class="armswitch_input armswitch_payment_input" name="arm_selected_payment_gateway[stripe][status]" checked="checked">
					<label for="arm_setup_stripe_status" class="armswitch_label"></label>
				</div>
				<label class="arm-form-table-label arm-paym-meth-lable" for="arm_setup_stripe_status" style="display:contents">&nbsp;'. esc_html__('Enable Stripe Payment','ARMember').'</label>
				<div class ="arm_stripe_payment_section">
					<div class="arm-lic-page-content arm-payment-mrthod-checkbox">
						<label class="arm-form-table-label arm-paym-meth-lable">'. esc_html__('Payment Mode','ARMember').'</label>
						<span class="arm_subscription_types_container" id="arm_subscription_types_container"><input type="radio" class="arm_iradio" checked="checked" value="sandbox" name="arm_selected_payment_gateway[stripe][payment_method]" id="payment_method_type_test_stripe"><label for="payment_method_type_test_stripe">'. esc_html__('Test','ARMember').'</label>
						</span>
						<span class="arm_subscription_types_container" id="arm_subscription_types_container"><input type="radio" class="arm_iradio" value="live" name="arm_selected_payment_gateway[stripe][payment_method]" id="payment_method_type_live"><label for="payment_method_type_live">'. esc_html__('Live','ARMember').'</label>
						</span>
					</div>
					<div>
						<div class="arm-lic-page-content arm_payment_setup arm_stripe_payment_setup">
							<label class="arm-form-table-label arm_stripe_sandbox_api">'. esc_html__('Stripe Secret Key (sandbox)','ARMember').'</label>
							<label class="arm-form-table-label arm_stripe_live_api" style="display:none">'. esc_html__('Stripe Secret Key','ARMember').'</label>
								<div class="arm-form-table-content">
									<input id="arm_stripe_secret_key" class="arm-lic-sectext-field" type="text" name="arm_selected_payment_gateway[stripe][secret_key]" value="" placeholder="'. esc_html__('Enter Stripe Secret Key','ARMember').'" required_msg="'. esc_html__('Please Enter Valid Secret Key','ARMember').'">
									<span id="arm_stripe_secret_key-error" class="error arm_invalid"></span>
								</div>
						</div>
						<div class="arm-lic-page-content arm_payment_setup arm_stripe_payment_setup">
							<label class="arm-form-table-label arm_stripe_sandbox_api">'. esc_html__('Stripe Publishable Key (Sandbox)','ARMember').'</label>
							<label class="arm-form-table-label arm_stripe_live_api" style="display:none">'. esc_html__('Stripe Publishable Key','ARMember').'</label>
							<div class="arm-form-table-content">
								<input id="arm_stripe_publishable_key" class="arm-lic-sectext-field" type="text" name="arm_selected_payment_gateway[stripe][publish_key]" value="" placeholder="'. esc_html__('Enter Stripe Publishable Key','ARMember').'" required_msg="'. esc_html__('Please Enter Valid Publishable Key','ARMember').'"> 
								<span id="arm_stripe_publishable_key-error" class="error arm_invalid"></span>
							</div>
						</div>
					</div>
					<button type="button" class="arm-wsc-btn arm-wsc-btn--primary arm-ws-back-btn arm_payment_setup arm_stripe_verify_webhook">
						'. esc_html__('Verify Webhook','ARMember');
						$wpnonce = wp_create_nonce( 'arm_wp_nonce' );
						$arm_setup_wizard_payment_gateway_section .= '<input type="hidden" name="arm_wp_nonce" value="'. esc_attr($wpnonce).'"/>
					</button>
					<span class="arm_stripe_webhook_error hidden_section" id="arm_stripe_webhook_error_setup">'.esc_html__('Please verify Stripe Webhook by clicking on \'Verify Webhook\' button', 'ARMember').'</span>
					<span class="arm_stripe_webhook_verify hidden_section" id="arm_stripe_webhook_verify_setup">'. esc_html__('Verified', 'ARMember').'</span>
					<input type="hidden" name="stripe_webhook_verified" id="arm_stripe_webhook_verified" value="" data-msg-required="'. esc_html__('Please verify Stripe Webhook by clicking on \'Verify Webhook\' button', 'ARMember').'">
				</div>
			</div>';
            return $arm_setup_wizard_payment_gateway_section;
        }
        /** WIZARD SETUP ENDS */
    }
}
global $arm_wizard_class;
$arm_wizard_class = new ARM_setup_Wizard();