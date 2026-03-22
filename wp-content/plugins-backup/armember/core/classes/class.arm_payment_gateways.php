<?php

if (!class_exists('ARM_payment_gateways')) {

    class ARM_payment_gateways {

        var $currency;

        function __construct() {
            global $wpdb, $ARMember, $arm_slugs;
            add_action('wp_ajax_arm_update_pay_gate_settings', array($this, 'arm_update_pay_gate_settings'));
            add_filter('arm_change_user_meta_before_save', array($this, 'arm_filter_form_posted_plan_data'), 10, 3);
            add_action('wp_ajax_arm_check_currency_status', array($this, 'arm_check_currency_status'));
            $this->currency = array(
                'paypal' => $this->arm_paypal_currency_symbol(),
                'stripe' => $this->arm_stripe_currency_symbol(),
                'authorize_net' => $this->arm_authorize_net_currency_symbol(),
                '2checkout' => $this->arm_2checkout_currency_symbol(),
                'bank_transfer' => $this->arm_bank_transfer_currency_symbol(),
            );

            add_action('wp_ajax_arm_view_payment_debug_log', array($this, 'arm_view_payment_debug_log'));
            add_action('wp_ajax_arm_view_general_debug_log', array($this, 'arm_view_general_debug_log'));

            add_action('wp_ajax_arm_download_payment_debug_log', array($this, 'arm_download_payment_debug_log'));
            add_action('wp_ajax_arm_download_general_debug_log', array($this, 'arm_download_general_debug_log'));

            add_action('wp_ajax_arm_verify_stripe_webhook', array($this, 'arm_verify_stripe_webhook_func'));

            add_filter('arm_change_payment_gateway_tooltip',array($this,'arm_change_payment_gateway_tooltip_func'),10,3);

            add_filter('arm_gateway_callback_info',array($this,'arm_gateway_callback_info_func'),10,3);

            add_filter('armember_pro_gateways_fields_section',array($this,'armember_pro_gateways_fields_section_func'),10,3);

            add_filter('arm_allow_ccfields_settings',array($this,'arm_allow_ccfields_settings_function'),10,3);

            add_filter('arm_allow_ccfields_class_names',array($this,'arm_allow_ccfields_class_names_func'),10,3);

            add_filter('arm_payment_currency_section',array($this,'arm_payment_currency_section_func'),10,1);
            
        }

        function arm_payment_currency_section_func($arm_gateway_fields)
		{

            global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_payment_gateways,$arm_members_activity;

            $all_currency               = $arm_payment_gateways->arm_get_all_currencies();
            $global_currency            = $arm_payment_gateways->arm_get_global_currency();
            $global_currency_symbol     = $all_currency[ strtoupper( $global_currency ) ];

			$arm_gateway_fields ='
			<div class="arm_form_field_block arm_payment_currency">
					<label class="arm-form-table-label arm_width_100_pct">'. esc_html__( 'Currency', 'ARMember' ).'</label>
					<div class="arm_margin_top_12">
						
						<span class="arm_payment_gateway_currency_label ">'. esc_html($global_currency).' ( ' . esc_html($global_currency_symbol) . ' ) </span>
						<a class="arm_payment_gateway_currency_link arm_ref_info_links" href="'. esc_url( admin_url( 'admin.php?page=' . $arm_slugs->general_settings . '#changeCurrency' ) ) .'">'. esc_html__( 'Change currency', 'ARMember' ).'</a>			
					</div>
				</div>
			';
			return $arm_gateway_fields;
		}

        function arm_allow_ccfields_settings_function($allowed, $gateway_name, $gateway_options){
            if(in_array($gateway_name, array('stripe','authorize_net')))
            {
                $allowed = true;
            }
            return $allowed;
        }

        function arm_allow_ccfields_class_names_func($arm_gateway_class_name, $gateway_name, $gateway_options){
            
            if($gateway_name == 'stripe')
            {
                $arm_stripe_popup_section = (!empty($gateway_options['stripe_payment_method']) && $gateway_options['stripe_payment_method']=='fields') ? '' : 'hidden_section';
                $arm_gateway_class_name = 'arm_payment_gateway_stripe_cc_field_wrapper '.$arm_stripe_popup_section;
            }
            return $arm_gateway_class_name;
        }
        function armember_pro_gateways_fields_section_func($arm_gateway_fields,$gateway_name,$gateway_options){
            global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_payment_gateways,$arm_members_activity;
            

            $arm_stripe_currency = $arm_payment_gateways->currency['stripe'];
            $arm_authorize_net_currency = $arm_payment_gateways->currency['authorize_net'];
            $arm_2checkout_currency = $arm_payment_gateways->currency['2checkout'];
            $arm_bank_transfer_currency = $arm_payment_gateways->currency['bank_transfer'];

            $all_currency               = $arm_payment_gateways->arm_get_all_currencies();
            $global_currency            = $arm_payment_gateways->arm_get_global_currency();
            $global_currency_symbol     = $all_currency[ strtoupper( $global_currency ) ];
            

            if(!empty($gateway_options['stripe_popup_icon'])){
                
                $file_name = !empty($gateway_options['stripe_popup_icon']) ? esc_attr($ARMember->arm_get_basename($gateway_options['stripe_popup_icon'])) : "";
                $file_meta_key = "stripe_popup";
                $arm_members_activity->session_for_file_handle($file_meta_key,$file_name);
            }     

            switch($gateway_name){
                case 'stripe':
                    
                    $arm_status_switchChecked = ($gateway_options['status'] == '1') ? 'checked="checked"' : '';
                    $disabled_field_attr = ($gateway_options['status']=='1') ? '' : 'disabled="disabled"';
                    $readonly_field_attr = ($gateway_options['status']=='1') ? '' : 'readonly="readonly"';
                    $gateway_options['stripe_payment_mode'] = (!empty($gateway_options['stripe_payment_mode'])) ? $gateway_options['stripe_payment_mode'] : 'test';
                    $is_test_mode_checked = ($gateway_options['stripe_payment_mode'] == 'test') ? 'checked="checked"' :'' ;
                    $is_live_mode_checked = ($gateway_options['stripe_payment_mode'] == 'live') ? 'checked="checked"' : '';
                    $arm_gateway_fields .= '<div class="arm_payment_gateway_section arm_stripe_main_container' . ( $gateway_options['status'] == '1' ? '' : ' hidden_section' ) . '">';
                    $arm_gateway_fields .= '<div class="arm_content_border arm_margin_top_24"></div><div class="form-field">
                        <div class="arm_form_field_block  arm_margin_top_24">
                        <label class="arm-form-table-label"><label>'. esc_html__('Payment Mode', 'ARMember').'</label></label>
                        <div class="arm-form-table-content ">
                            <div id="arm_stripe_mode_container" class="arm_stripe_mode_container arm_paypal_mode_container arm_margin_top_20 arm_margin_bottom_32">
                                <input id="arm_stripe_mode_test" class="arm_general_input arm_stripe_mode_radio arm_iradio arm_active_payment_'. strtolower( esc_attr($gateway_name) ).'" type="radio" value="test" name="payment_gateway_settings[stripe][stripe_payment_mode]" '. $is_test_mode_checked.' '.  $disabled_field_attr.'><label for="arm_stripe_mode_test">'. esc_html__('Sandbox', 'ARMember').'</label>
                                <input id="arm_stripe_mode_live" class="arm_general_input arm_stripe_mode_radio arm_iradio arm_active_payment_'. strtolower( esc_attr($gateway_name) ).'" type="radio" value="live" name="payment_gateway_settings[stripe][stripe_payment_mode]" '. $is_live_mode_checked.' '.  $disabled_field_attr.'><label for="arm_stripe_mode_live">'. esc_html__('Live', 'ARMember').'</label>
                            </div>
                        </div>
                    </div>';
                    $stripe_live_hidden_section = ($gateway_options['stripe_payment_mode']=='live') ? '' : 'hidden_section';
                    $stripe_live_secret_key = (!empty($gateway_options['stripe_secret_key']) ? esc_attr($gateway_options['stripe_secret_key']) : "" );
                    $stripe_live_pub_key = (!empty($gateway_options['stripe_pub_key']) ? esc_attr($gateway_options['stripe_pub_key']) : "" );
                    $arm_gateway_fields .= '<div class="arm_email_setting_flex_group arm_payment_getway_page"> <div class="arm_form_field_block arm_pay_gate_stripe_live_mode '. $stripe_live_hidden_section.' arm_width_50_pct">
                        <label class="arm-form-table-label">'. esc_html__('Secret Key', 'ARMember').' *</label>
                        <div class="arm-form-table-content ">
                            <input class="arm_active_payment_'. strtolower(esc_attr($gateway_name)).' arm_width_100_pct arm_margin_top_12" id="arm_stripe_live_secrate_key" type="text" name="payment_gateway_settings[stripe][stripe_secret_key]" value="'.$stripe_live_secret_key.'" data-msg-required="'. esc_html__('Live Secret Key can not be left blank.', 'ARMember').'" '. $readonly_field_attr .'>
                        </div>
                    </div>
                    <div class="arm_form_field_block arm_pay_gate_stripe_live_mode '.$stripe_live_hidden_section.' arm_width_50_pct">
                        <label class="arm-form-table-label">'. esc_html__('Publishable Key', 'ARMember').' *</label>
                        <div class="arm-form-table-content">
                            <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_width_100_pct arm_margin_top_12" id="arm_stripe_live_pub_key" type="text" name="payment_gateway_settings[stripe][stripe_pub_key]" value="'.$stripe_live_pub_key.'" data-msg-required="'. esc_html__('Live Publishable Key can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                        </div>
                    </div></div>';
                    $stripe_test_hidden_section = ($gateway_options['stripe_payment_mode']=='test') ? '' : 'hidden_section';
                    $stripe_test_secret_key = (!empty($gateway_options['stripe_test_secret_key']) ? esc_attr($gateway_options['stripe_test_secret_key']) : "" );
                    $stripe_test_pub_key = (!empty($gateway_options['stripe_test_pub_key']) ? esc_attr($gateway_options['stripe_test_pub_key']) : "" );
                    $arm_gateway_fields .= '<div class="arm_email_setting_flex_group arm_payment_getway_page"><div class="arm_form_field_block  arm_pay_gate_stripe_test_mode '.$stripe_test_hidden_section.' arm_width_50_pct">
                        <label class="arm-form-table-label">'. esc_html__('Secret Key', 'ARMember').' *</label>
                        <div class="arm-form-table-content">
                            <input class="arm_active_payment_'. strtolower(esc_attr($gateway_name)).' arm_width_100_pct arm_margin_top_12" id="arm_stripe_test_secrate_key" type="text" name="payment_gateway_settings[stripe][stripe_test_secret_key]" value="'.$stripe_test_secret_key.'" data-msg-required="'. esc_html__('Test Secret Key can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                        </div>
                    </div>
                    <div class="arm_form_field_block  arm_pay_gate_stripe_test_mode '.$stripe_test_hidden_section.' arm_width_50_pct">
                        <label class="arm-form-table-label">'. esc_html__('Publishable Key', 'ARMember').' *</label>
                        <div class="arm-form-table-content">
                            <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_width_100_pct arm_margin_top_12" id="arm_stripe_test_pub_key" type="text" name="payment_gateway_settings[stripe][stripe_test_pub_key]" value="'.$stripe_test_pub_key.'" data-msg-required="'. esc_html__('Test Publishable Key can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                        </div>
                    </div></div>';
                    $stripe_webhook_verified_hidden = (!empty($gateway_options['stripe_webhook_verified'])) ? '' : 'hidden_section';
                    $stripe_webhook_verified = (!empty($gateway_options['stripe_webhook_verified']) ? esc_attr($gateway_options['stripe_webhook_verified']) : "" );
                    $arm_gateway_fields .= '<div class="form-field arm_margin_top_20">
                
                        <div class="arm-form-table-content">
                            <a href="javascript:void(0)" id="arm_verify_stripe_webhook" onclick="arm_verify_pg_webhook(\'stripe\')" class="arm_verify_stripe_webhook_conf armemailaddbtn">'. esc_html__('Verify Webhook', 'ARMember').'</a>
                            <span id="arm_stripe_webhook_verify" class="arm_success_msg arm_width_70_pct arm_margin_left_10 '.$stripe_webhook_verified_hidden.'">'. esc_html__('Verified', 'ARMember').'</span> 
                            <span id="arm_stripe_webhook_error" class="payment-errors arm_margin_top_20" style="display:none;">'. esc_html__('Not Verified', 'ARMember').'</span>
                            <input type="hidden" name="payment_gateway_settings[stripe][stripe_webhook_verified]" id="arm_stripe_webhook_verified" value="'.$stripe_webhook_verified.'" data-msg-required="'. esc_html__('Please verify Stripe Webhook by clicking on \'Verify Webhook\' button', 'ARMember').'">
                        </div>
                    </div>
                    
                    <div class="arm_form_field_block arm_margin_top_20">
                    
                        <div class="arm-form-table-label">
                            <label>'. esc_html__('Select Payment Method', 'ARMember').'</label>
                        </div>
                        <div class="arm-form-table-content arm_margin_top_20">
                            ';
                                if( empty( $gateway_options['stripe_payment_method'] ) ){
                                    $gateway_options['stripe_payment_method'] = 'popup';
                                }
                                $is_stripe_field_checked = ($gateway_options['stripe_payment_method'] == 'fields') ? 'checked="checked"' : '';
                                $is_stripe_sca_checked = ($gateway_options['stripe_payment_method'] == 'popup') ? 'checked="checked"' : '';
                            $arm_gateway_fields .= '
                            <div class="arm_stripe_mode_container arm_paypal_mode_container arm_margin_bottom_32" id="arm_stripe_payment_method_container">
                                <label>
                                    <input id="arm_stripe_payment_method_fields" class="arm_general_input arm_stripe_payment_method_radio arm_iradio arm_active_payment_'. strtolower(esc_attr($gateway_name)).'" type="radio" value="fields" name="payment_gateway_settings[stripe][stripe_payment_method]" '. $is_stripe_field_checked.' '. $disabled_field_attr.' /><label for="arm_stripe_payment_method_fields">'. esc_html__('Built-In Form Fields','ARMember').'</label>
                                </label>
                                <label style="margin-bottom:0 !important">
                                    <input id="arm_stripe_payment_method_popup" class="arm_general_input arm_stripe_payment_method_radio arm_iradio arm_active_payment_'. strtolower(esc_attr($gateway_name)) .'" type="radio" value="popup" name="payment_gateway_settings[stripe][stripe_payment_method]" '. $is_stripe_sca_checked .' '. $disabled_field_attr.' /><label for="arm_stripe_payment_method_popup">'. esc_html__('SCA Compliant (popup)','ARMember').'</label>
                                </label>
                            </div>
                        </div>
                    </div>';
                    $arm_stripe_popup_section = ($gateway_options['stripe_payment_method']=='popup') ? '' : 'hidden_section';
                    $arm_stripe_popup_title = !empty($gateway_options['stripe_popup_title']) ? esc_attr($gateway_options['stripe_popup_title']) : "";
                    $arm_stripe_popup_button_lbl = (!empty($gateway_options['stripe_popup_button_lbl']) ? esc_attr($gateway_options['stripe_popup_button_lbl']) : "" );
                    $arm_stripe_popup_icon_hidden = (!empty($gateway_options['stripe_popup_icon']) ? "hidden_section" : "");
                    $arm_is_file_display =(!empty($gateway_options['stripe_popup_icon']) ? "arm_file_display" : ""); 
                    $arm_stripe_popup_icon_hidden_show = (!empty($gateway_options['stripe_popup_icon']) ? "" : "hidden_section");
                    $arm_stripe_popup_icon = (!empty($gateway_options['stripe_popup_icon']) ? esc_attr($gateway_options['stripe_popup_icon']) : "");
                    $arm_stripe_popup_icon_name = (!empty($gateway_options['stripe_popup_icon']) ? basename($gateway_options['stripe_popup_icon']) : "");
                    $arm_gateway_fields .= '<div class="arm_email_setting_flex_group arm_payment_getway_page"> 
                    <div class="arm_form_field_block  arm_stripe_payment_method_popup_fields '.$arm_stripe_popup_section.' arm_width_100_pct ">
                        <label class="arm-form-table-label">'. esc_html__('Popup Title', 'ARMember').' *</label>
                        <i class="arm_helptip_icon armfa armfa-question-circle" title="{arm_selected_plan_title} : '. esc_html__("This shortcode will be replaced with the user selected plan name.", 'ARMember').'"></i>
                        <div class="arm-form-table-content">
                            <input class="arm_active_payment_'. strtolower(esc_attr($gateway_name)).' arm_width_100_pct arm_margin_top_12" id="arm_stripe_popup_title_lbl" type="text" name="payment_gateway_settings[stripe][stripe_popup_title]" value="'.$arm_stripe_popup_title.'" data-msg-required="'. esc_html__('Popup Title can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                        </div>
                    </div>
                    <div class="arm_form_field_block  arm_stripe_payment_method_popup_fields '.$arm_stripe_popup_section.' arm_width_100_pct ">
                        <label class="arm-form-table-label">'. esc_html__('Payment Button Label', 'ARMember').' *</label>
                        <div class="arm-form-table-content ">
                            <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_margin_top_12" id="arm_stripe_popup_button_lbl" type="text" name="payment_gateway_settings[stripe][stripe_popup_button_lbl]" value="'.$arm_stripe_popup_button_lbl.'" data-msg-required="'. esc_html__('Payment Button Label can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                        </div>
                    </div>
                    <div class="arm_form_field_block arm_stripe_payment_method_popup_fields '.$arm_stripe_popup_section.' arm_width_100_pct ">
                        <label class="arm-form-table-label">'. esc_html__('Popup Logo', 'ARMember').' <i class="arm_helptip_icon armfa armfa-question-circle" title="'. esc_attr__("Recommended logo size 70X70 px", 'ARMember').'"></i></label>
                        <div class="arm-form-table-content">
                            <div class="arm_stripe_popup_icon_containe arm_min_width_0 arm_margin_top_12 '.$arm_is_file_display.'">
                                <div class="arm_stripe_popup_icon_wrapper '.$arm_stripe_popup_icon_hidden.'">
                                    <span>'. esc_html__('Drop file here or click to select.', 'ARMember').'</span>
                                    <input type="file" class="arm_stripe_popup_icon arm_active_payment_'. strtolower(esc_attr($gateway_name)).'" id="arm_stripe_popup_icon_input" data-arm_clicked="not" data-avatar-type="stripe_popup" data-arm_stripe_popup_icon="arm_stripe_popup_icon" />
                                </div>
                                
                                <div class="arm_status_loader_img" id="arm_stripe_popup_icon_upload"></div>
                                <script type="text/javascript"> 
                                var ARM_MCARD_LOGO_ERROR_MSG = "'. esc_html__('Invalid File', 'ARMember').'";
                                </script>
                                <input type="hidden" class="arm_stripe_popup_icon_file_url" name="payment_gateway_settings[stripe][stripe_popup_icon]" value="'.$arm_stripe_popup_icon.'" />
                                <div class="arm_remove_default_cover_photo_wrapper arm_stripe_popup_icon_remove '.$arm_stripe_popup_icon_hidden_show.'">
                                    <img src="'.MEMBERSHIPLITE_IMAGES_URL.'/delete.svg" onmouseover="this.src=\''.MEMBERSHIPLITE_IMAGES_URL.'/delete_hover.svg\'" onmouseout="this.src=\''.MEMBERSHIPLITE_IMAGES_URL.'/delete.svg\'">                                   
                                </div>
                                <div class="arm_stripe_popup_icon_selected_img '.$arm_stripe_popup_icon_hidden_show.'">
                                    <img src="'.$arm_stripe_popup_icon.'" class="'.$arm_stripe_popup_icon_hidden_show.'" />
                                    <span class="arm_stripe_file_name">'.$arm_stripe_popup_icon_name.'</span>
                                </div>
                            </div>
                            <div class="arm_stripe_icon_error" id="arm_stripe_icon_error"></div>
                        </div>
                    </div>';

                    $arm_sca_cc_label = (!empty($gateway_options['sca_cc_label']) ? esc_html(stripslashes($gateway_options['sca_cc_label'])) : esc_html__('Credit Card Number', 'ARMember'));

                    $arm_sca_em_label = (!empty($gateway_options['sca_em_label']) ? esc_html(stripslashes($gateway_options['sca_em_label'])) : esc_html__('Expiration', 'ARMember'));

                    $arm_sca_cvv_label = (!empty($gateway_options['sca_cvv_label']) ? esc_html(stripslashes($gateway_options['sca_cvv_label'])) : esc_html__('CVV Code', 'ARMember'));

                    $arm_gateway_fields .= '<div class="arm_form_field_block arm_stripe_payment_method_popup_fields '.$arm_stripe_popup_section.'arm_width_100_pct">
                        <label class="arm-form-table-label">'. esc_html__('Credit Card Label', 'ARMember').'</label>
                        <i class="arm_helptip_icon armfa armfa-question-circle" title="'. esc_html__("This label will be displayed at frontend membership setup wizard page while payment.", 'ARMember').'"></i>
                        <div class="arm-form-table-content">
                            <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_margin_top_12" id="arm_payment_gateway_'. esc_attr($gateway_name).'_cc_label_creadit_card" data-id="arm_payment_gateway_'. esc_attr($gateway_name).'_cc_label" type="text" name="payment_gateway_settings['. esc_attr($gateway_name).'][sca_cc_label]" value="'.$arm_sca_cc_label.'" '. $readonly_field_attr.'>
                        </div>
                    </div>
                    <div class="arm_form_field_block arm_stripe_payment_method_popup_fields '.$arm_stripe_popup_section.' arm_width_100_pct">
                        <label class="arm-form-table-label">'. esc_html__('Expiration Label', 'ARMember').'</label>
                        <i class="arm_helptip_icon armfa armfa-question-circle" title="'. esc_html__("This label will be displayed at frontend membership setup wizard page while payment.", 'ARMember').'"></i>
                        <div class="arm-form-table-content">
                            <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_margin_top_12" id="arm_payment_gateway_'. esc_attr($gateway_name).'_em_label" type="text" name="payment_gateway_settings['. esc_attr($gateway_name).'][sca_em_label]" value="'.$arm_sca_em_label.'" '. $readonly_field_attr.'>
                        </div>
                    </div>
                    <div class="arm_form_field_block arm_stripe_payment_method_popup_fields '.$arm_stripe_popup_section.' arm_width_100_pct">
                        <label class="arm-form-table-label">'. esc_html__('CVV Label', 'ARMember').'</label>
                        <i class="arm_helptip_icon armfa armfa-question-circle" title="'. esc_html__("This label will be displayed at frontend membership setup wizard page while payment.", 'ARMember').'"></i>
                        <div class="arm-form-table-content">
                            <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_margin_top_12" id="arm_payment_gateway_'. esc_attr($gateway_name).'_cvv_label" type="text" name="payment_gateway_settings['. esc_attr($gateway_name).'][sca_cvv_label]" value="'.$arm_sca_cvv_label.'" '. $readonly_field_attr.'>
                        </div>
                    </div>
                   ';
                    
                    $arm_stripe_payment_gateway_section = '';
                    $arm_stripe_payment_gateway_section = apply_filters('arm_stripe_add_payment_method_section',$arm_stripe_payment_gateway_section,$gateway_name,$gateway_options); //phpcs:ignore

                    $arm_gateway_fields .= !empty($arm_stripe_payment_gateway_section) ? $arm_stripe_payment_gateway_section : '';
                    
                    break;
                case 'authorize_net':
                    $arm_status_switchChecked = ($gateway_options['status'] == '1') ? 'checked="checked"' : '';
                    $disabled_field_attr = ($gateway_options['status']=='1') ? '' : 'disabled="disabled"';
                    $readonly_field_attr = ($gateway_options['status']=='1') ? '' : 'readonly="readonly"';

                    $gateway_options['autho_mode'] = (!empty($gateway_options['autho_mode'])) ? $gateway_options['autho_mode'] : 'sandbox';

                    $arm_autho_api_login_id = (!empty($gateway_options['autho_api_login_id']) ? esc_attr($gateway_options['autho_api_login_id']) : "" );

                    $arm_autho_transaction_key = (!empty($gateway_options['autho_transaction_key']) ? esc_attr($gateway_options['autho_transaction_key']) : "" );

                    $is_test_mode_checked = ($gateway_options['autho_mode'] == 'sandbox') ? 'checked="checked"' :'' ;
                    $is_live_mode_checked = ($gateway_options['autho_mode'] == 'live') ? 'checked="checked"' : '';
                    $arm_gateway_fields .= '<div class="arm_payment_gateway_section arm_authorize_main_container' . ( $gateway_options['status'] == '1' ? '' : ' hidden_section' ) . '">';
                    
                    $arm_gateway_fields .= '<div class="arm_content_border arm_margin_top_24"> <div class="form-field">
                        <label class="arm-form-table-label arm_margin_top_24">'. esc_html__('Payment Mode', 'ARMember').'</label>
                        <div class="arm-form-table-content">
                            <div class="arm_authorize_net_mode_container arm_margin_top_20" id="arm_authorize_net_mode_container">
                                <input id="arm_autho_mode_sand" class="arm_general_input arm_iradio arm_active_payment_'. strtolower(esc_attr($gateway_name)).'" type="radio" value="sandbox" name="payment_gateway_settings[authorize_net][autho_mode]" '. $is_test_mode_checked.' '. $disabled_field_attr.'><label for="arm_autho_mode_sand">'. esc_html__('Sandbox', 'ARMember').'</label>
                                <input id="arm_autho_mode_live" class="arm_general_input arm_iradio arm_active_payment_'. strtolower(esc_attr($gateway_name) ).'" type="radio" value="live" name="payment_gateway_settings[authorize_net][autho_mode]" '. $is_live_mode_checked.' '. $disabled_field_attr.'><label for="arm_autho_mode_live">'. esc_html__('Live', 'ARMember').'</label>
                            </div>
                        </div>
                    </div>
                    <div class="arm_email_setting_flex_group arm_payment_getway_page arm_margin_top_32">
                        <div class="arm_form_field_block arm_width_100_pct">
                            <label class="arm-form-table-label">'. esc_html__('API Login ID', 'ARMember').' *</label>
                            <div class="arm-form-table-content">
                                <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_margin_top_12" id="arm_autho_api_key" type="text" name="payment_gateway_settings[authorize_net][autho_api_login_id]" value="'.$arm_autho_api_login_id.'" data-msg-required="'. esc_html__('API Login ID can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                            </div>
                        </div>
                    <div class="arm_form_field_block arm_width_100_pct ">
                        <label class="arm-form-table-label">'. esc_html__('Transaction Key', 'ARMember').' *</label>
                        <div class="arm-form-table-content">
                            <input class="arm_active_payment_'. strtolower(esc_attr($gateway_name)).' arm_margin_top_12" id="arm_autho_transaction_key" type="text" name="payment_gateway_settings[authorize_net][autho_transaction_key]" value="'.$arm_autho_transaction_key.'" data-msg-required="'. esc_html__('Transaction Key can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                        </div>
                    </div>';
                    
                    break;
                case '2checkout':
                    $arm_status_switchChecked = ($gateway_options['status'] == '1') ? 'checked="checked"' : '';
                    $disabled_field_attr = ($gateway_options['status']=='1') ? '' : 'disabled="disabled"';
                    $readonly_field_attr = ($gateway_options['status']=='1') ? '' : 'readonly="readonly"';

                    $gateway_options['payment_mode'] = (!empty($gateway_options['payment_mode'])) ? $gateway_options['payment_mode'] : 'sandbox';

                    $arm_checkout_username = (!empty($gateway_options['username']) ? esc_attr($gateway_options['username']) : "" );

                    $arm_checkout_password =(!empty($gateway_options['password']) ? esc_attr($gateway_options['password']) : "" );

                    $arm_checkout_seller_id = (!empty($gateway_options['sellerid']) ? esc_attr($gateway_options['sellerid']) : "" );

                    $arm_checkout_private_key = (!empty($gateway_options['private_key']) ? esc_attr($gateway_options['private_key']) : "" );

                    $arm_checkout_api_secret_key = (!empty($gateway_options['api_secret_key']) ? esc_attr($gateway_options['api_secret_key']) : "" );

                    $arm_checkout_secret_word =  (!empty($gateway_options['secret_word']) ? esc_attr($gateway_options['secret_word']) : "" );

                    $is_test_mode_checked = ($gateway_options['payment_mode'] == 'sandbox') ? 'checked="checked"' :'' ;
                    $is_live_mode_checked = ($gateway_options['payment_mode'] == 'live') ? 'checked="checked"' : '';
                    $arm_gateway_fields .= '<div class="arm_payment_gateway_section arm_2checkout_main_container' . ( $gateway_options['status'] == '1' ? '' : ' hidden_section' ) . '">';
                    
                    $arm_gateway_fields .= '<div class="arm_content_border arm_margin_top_24"></div><div class="form-field">
                        <label class="arm-form-table-label arm_margin_top_24">'. esc_html__('Payment Mode', 'ARMember').'</label>
                        <div class="arm-form-table-content">
                            <div class="arm_2checkout_mode_container arm_margin_top_20 arm_margin_bottom_32" id="arm_2checkout_mode_container">
                                <input class="arm_general_input arm_2checkout_mode_radio arm_iradio arm_active_payment_'. strtolower( esc_attr($gateway_name) ).'" type="radio" value="sandbox" id="arm_2checkout_mode_sandbox" name="payment_gateway_settings[2checkout][payment_mode]" '. $is_test_mode_checked.' '. $disabled_field_attr.'><label for="arm_2checkout_mode_sandbox">'. esc_html__('Sandbox', 'ARMember').'</label>
                                <input class="arm_general_input arm_2checkout_mode_radio arm_iradio arm_active_payment_'. strtolower(esc_attr($gateway_name) ).'" type="radio" value="live" id="arm_2checkout_mode_live" name="payment_gateway_settings[2checkout][payment_mode]" '. $is_live_mode_checked.' '. $disabled_field_attr.'><label for="arm_2checkout_mode_live">'. esc_html__('Live', 'ARMember').'</label>
                            </div>
                        </div>
                   </div>
                   <div class="arm_email_setting_flex_group arm_payment_getway_page">
                        <div class="arm_form_field_block">
                                <label class="arm-form-table-label">'. esc_html__('API Username', 'ARMember').' *</label>
                                <div class="arm-form-table-content">
                                    <input class="arm_active_payment_'. strtolower(esc_attr($gateway_name)).' arm_margin_top_12" id="arm_2checkout_username" type="text" name="payment_gateway_settings[2checkout][username]" value="'.$arm_checkout_username.'" data-msg-required="'. esc_html__('API Username can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                                </div>
                        </div>
                        <div class="arm_form_field_block">
                                <label class="arm-form-table-label">'. esc_html__('API Password', 'ARMember').' *</label>
                                <div class="arm-form-table-content">
                                    <input class="arm_active_payment_'. strtolower(esc_attr($gateway_name)).' arm_margin_top_12" id="arm_2checkout_password" type="text" name="payment_gateway_settings[2checkout][password]" value="'.$arm_checkout_password.'" data-msg-required="'. esc_html__('API Password can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                                </div>
                        </div>
                        <div class="arm_form_field_block">
                                <label class="arm-form-table-label">'. esc_html__('Seller Id (account number)', 'ARMember').' *</label>
                                <div class="arm-form-table-content">
                                    <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_margin_top_12" id="arm_2checkout_sellerid" type="text" name="payment_gateway_settings[2checkout][sellerid]" value="'.$arm_checkout_seller_id.'" data-msg-required="'. esc_html__('SellerId can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                                </div>
                        </div>
                  
                 
                        <div class="arm_form_field_block">
                                <label class="arm-form-table-label">'. esc_html__('Private Key', 'ARMember').' *</label>
                                <div class="arm-form-table-content">
                                    <input class="arm_active_payment_'. strtolower(esc_attr($gateway_name)).' arm_margin_top_12" id="arm_2checkout_private_key" type="text" name="payment_gateway_settings[2checkout][private_key]" value="'.$arm_checkout_private_key.'" data-msg-required="'. esc_html__('Private Key can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                                </div>
                        </div>
                        <div class="arm_form_field_block">
                                <label class="arm-form-table-label">'. esc_html__('API Secret Key', 'ARMember').' *</label>
                                <div class="arm-form-table-content">
                                    <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_margin_top_12" id="arm_2checkout_api_secret_key" type="text" name="payment_gateway_settings[2checkout][api_secret_key]" value="'.$arm_checkout_api_secret_key.'" data-msg-required="'. esc_html__('API Secret Key can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                                </div>
                        </div>
                        <div class="arm_form_field_block">
                                <label class="arm-form-table-label">'. esc_html__('Secret Word', 'ARMember').' *</label>
                                <div class="arm-form-table-content">
                                    <input class="arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_margin_top_12" id="arm_2checkout_secret_word" type="text" name="payment_gateway_settings[2checkout][secret_word]" value="'.$arm_checkout_secret_word.'" data-msg-required="'. esc_html__('Secret Word can not be left blank.', 'ARMember').'" '. $readonly_field_attr.'>
                                </div>
                        </div>
                        <div class="arm_form_field_block">
                            <label class="arm-form-table-label">'. esc_html__('Language', 'ARMember').'</label>
                            <div class="arm-form-table-content">';
                                $arm_2checkout_language = $arm_payment_gateways->arm_2checkout_language();
                                $arm_2checkout_lang = (!empty($gateway_options['language'])) ? esc_attr($gateway_options['language']) : 'en';
                                $arm_2checkout_lan_style = ($gateway_options['status']=='1') ? '' : 'style="border:1px solid #DBE1E8"';
                                $arm_gateway_fields .= '<input type="hidden" id="arm_2checkout_language" name="payment_gateway_settings[2checkout][language]" value="'.$arm_2checkout_lang.'"/>
                                <dl class="arm_selectbox arm_active_payment_'. strtolower( esc_attr($gateway_name) ).' arm_margin_top_12 arm_width_100_pct" '. $disabled_field_attr.'>
                                    <dt '.$arm_2checkout_lan_style.'>
                                        <span></span>
                                        <input type="text" style="display:none;" value="'. esc_html__('English ( en )', 'ARMember').'" class="arm_autocomplete"/>
                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_2checkout_language">';
                                            foreach ($arm_2checkout_language as $key => $value):
                                                $arm_gateway_fields .= '<li data-label="'. esc_attr($value) . '('. esc_attr($key).')" data-value="'. esc_attr($key).'">'. esc_html($value) . ' ( '. esc_html($key).' )</li>';
                                            endforeach;
                                        $arm_gateway_fields .= '</ul>
                                    </dd>
                                </dl>
                            </div>
                       </div>';
                       $arm_gateway_fields .= apply_filters('arm_payment_currency_section' , "");
                       $arm_gateway_fields .= '</div>';
                    break;
                  
            }
            return $arm_gateway_fields;
        }

        function arm_gateway_callback_info_func($apiCallbackUrlInfo, $gateway_name, $gateway_options){
            global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_payment_gateways;
            switch ($gateway_name) {
                case 'stripe':
                    
                    $apiCallbackUrl = $arm_global_settings->add_query_arg("arm-listener", "arm_stripe_api", ARM_HOME_URL . "/");
                    $apiCallbackUrlInfo = '<div class="arm_margin_top_24 arm_info_text arm-note-message --alert">';                        
                    $apiCallbackUrlInfo .= esc_html__('Please make sure you have set following callback URL in your Stripe account.', 'ARMember');
                    $callbackTooltip = esc_html__('To get more information about how to set Web Hook URL from your Stripe account, please refer this', 'ARMember').' <a href="'.MEMBERSHIP_DOCUMENTATION_PAYMENT_GATEWAY_STRIPE_URL.'" target="_blank">'.esc_html__('document', 'ARMember').'</a>';
                    $apiCallbackUrlInfo .= '<i class="arm_helptip_icon armfa armfa-question-circle" title="'.htmlentities($callbackTooltip).'"></i>';
                    $apiCallbackUrlInfo .= '<br/><b>' . $apiCallbackUrl . '</b>';
                    $apiCallbackUrlInfo .= '</div>';
                    break;
                case 'authorize_net':
                    $apiCallbackUrl = $arm_global_settings->add_query_arg("arm-listener", "arm_authorizenet_api", ARM_HOME_URL . "/");
                    $apiCallbackUrlInfo = '<div class="arm_margin_top_24 arm_info_text arm-note-message --alert">';
                    $apiCallbackUrlInfo .= esc_html__('Please make sure you have set following callback URL in your Authorize.net account.', 'ARMember');
                    $callbackTooltip = esc_html__('To get more information about how to set Silent Post URL from your Authorize.net account, please refer this', 'ARMember').' <a href="'.MEMBERSHIP_DOCUMENTATION_PAYMENT_GATEWAY_AUTHORIZE_URL.'" target="_blank">'.esc_html__('document', 'ARMember').'</a>';
                    $apiCallbackUrlInfo .= '<i class="arm_helptip_icon armfa armfa-question-circle" title="'.htmlentities($callbackTooltip).'"></i>';
                    $apiCallbackUrlInfo .= '<br/><b>' . $apiCallbackUrl . '</b>';
                    break;
                case '2checkout':
                    $apiCallbackUrl = $arm_global_settings->add_query_arg("action", "arm_2checkout_api", ARM_HOME_URL . "/");
                    $apiListenerUrl = $arm_global_settings->add_query_arg("arm-listener", "arm_2checkout_api", ARM_HOME_URL ."/");
                    $apiCallbackUrlInfo = '<div class="arm_margin_top_24 arm_info_text arm-note-message --alert">';
                    $apiCallbackUrlInfo .= esc_html__('Please make sure you have set following Approved URL in your 2Checkout account.', 'ARMember');
                    $callbackTooltip = esc_html__('To get more information about how to set INS(Web Hook) URL AND Approved URL from your 2Checkout account, please refer this', 'ARMember').' <a href="'.MEMBERSHIP_DOCUMENTATION_PAYMENT_GATEWAY_2CHECKOUT_URL.'" target="_blank">'.esc_html__('document', 'ARMember').'</a>';
                    $apiCallbackUrlInfo .= '<i class="arm_helptip_icon armfa armfa-question-circle" title="'.htmlentities($callbackTooltip).'"></i>';
                    $apiCallbackUrlInfo .= '<br/><b>' . $apiCallbackUrl . '</b>';
                    $apiCallbackUrlInfo .= '</div>';
                    
                    $apiCallbackUrlInfo .= '<div class="arm_margin_top_24 arm_info_text arm-note-message --alert">'.esc_html__('In case of Recurring Billing, Please make sure you have set following URL in the Global URL field in your 2Checkout account.', 'ARMember');
                    $globalfieldTooltip = esc_html__('To get more information about how to set Global URL Field from your 2Checkout account, please refer this', 'ARMember').' <a href="'.MEMBERSHIP_DOCUMENTATION_PAYMENT_GATEWAY_2CHECKOUT_URL.'" target="_blank">'.esc_html__('document', 'ARMember').'</a>';
                    $apiCallbackUrlInfo .= '<i class="arm_helptip_icon armfa armfa-question-circle" title="'.htmlentities($globalfieldTooltip).'"></i>';
                    $apiCallbackUrlInfo .= '<br/><b>'.$apiListenerUrl.'</b>';
                    $apiCallbackUrlInfo .= '</div>';


                    break;
                default:
                    break;
            }
            return $apiCallbackUrlInfo;
        }

        function arm_change_payment_gateway_tooltip_func($titleTooltip, $gateway_name, $gateway_options)
        {
            global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_payment_gateways;
            switch ($gateway_name) {
                case 'stripe':
                    $titleTooltip = esc_html__('Your API keys are located in your', 'ARMember').' <a href="https://dashboard.stripe.com/account/apikeys" target="_blank">'.esc_html__('account settings', 'ARMember').'</a>. '.esc_html__('To get more details, please refer this', 'ARMember').' <a href="https://support.stripe.com/questions/where-do-i-find-my-api-keys" target="_blank">'.esc_html__('document', 'ARMember').'</a>';
                    break;
                case 'authorize_net':
                    $titleTooltip = esc_html__('You can find your Login ID & Transaction Key from your authorize.net account. To get more details, Please refer this', 'ARMember').' <a href="https://support.authorize.net/knowledgebase/Knowledgearticle/?code=000001271" target="_blank">'.esc_html__('document', 'ARMember').'.</a>';
                    
                    break;
                case '2checkout':
                    $titleTooltip = esc_html__('You can find Username, Password, Secret Key etc details', 'ARMember').' <a href="http://help.2checkout.com/articles/FAQ/Where-do-I-set-up-the-Secret-Word/" target="_blank">'.esc_html__('here', 'ARMember').'.</a>';

                    break;
                default:
                    break;
            }
            return $titleTooltip;
        }

        function arm_debug_log_download_file(){
            if( !empty( $_REQUEST['arm_action'] ) && 'download_log' == $_REQUEST['arm_action'] ){
                global $wpdb, $ARMember, $arm_global_settings, $arm_capabilities_global;
                
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1);
                
                $filename = !empty($_REQUEST['file']) ? basename( sanitize_file_name( $_REQUEST['file']) ) : '';
                if(!empty($filename))
                {
                    $file_path = MEMBERSHIP_UPLOAD_DIR . '/' . $filename;

                    $allowexts = array( "txt", "zip" );

                    $file_name_arm = substr($filename, 0,3);

                    $checkext = explode(".", $filename);
                    $ext = strtolower( $checkext[count($checkext) - 1] );

                    if(!empty($ext) && in_array($ext, $allowexts) && !empty($filename) && file_exists($file_path))
                    {
                        ignore_user_abort();
                        $now = gmdate("D, d M Y H:i:s");
                        header("Expires: Tue, 03 Jul 2020 06:00:00 GMT");
                        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
                        header("Last-Modified: {$now} GMT");
                        header("Content-Type: application/force-download");
                        header("Content-Type: application/octet-stream");
                        header("Content-Type: application/download");
                        header("Content-Disposition: attachment;filename={$filename}");
                        header("Content-Transfer-Encoding: binary");

                        readfile($file_path);

                        unlink( $file_path );

                        $arm_txt_file_name = str_replace('.zip', '.txt', $filename);
                        $arm_txt_file_path = MEMBERSHIP_UPLOAD_DIR . '/' . $arm_txt_file_name;
                        if(file_exists($arm_txt_file_path))
                        {
                            unlink($arm_txt_file_path);
                        }

                        die;
                    }
                }
            }
        }

        function arm_download_general_debug_log(){
            global $wpdb, $ARMember, $arm_global_settings, $arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $arm_download_url = '';
            if(!empty($_POST['arm_download_key']))//phpcs:ignore
            {
                $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
                $tbl_arm_debug_general_log = $ARMember->tbl_arm_debug_general_log;
                $arm_download_key = $posted_data['arm_download_key'];
                $arm_wp_nonce = !empty($posted_data['arm_wp_nonce'])?sanitize_text_field($posted_data['arm_wp_nonce']):(!empty($posted_data['_wpnonce'])?sanitize_text_field($posted_data['_wpnonce']):'');
                
                $arm_selected_download_duration = !empty($posted_data['selected_download_duration']) ? $posted_data['selected_download_duration'] : 'all';

                $arm_custom_duration_start_date = !empty($posted_data['arm_filter_pstart_date']) ? $posted_data['arm_filter_pstart_date'] : '';
                $arm_custom_duration_end_date = !empty($posted_data['arm_filter_pend_date']) ? $posted_data['arm_filter_pend_date'] : '';

                $arm_debug_general_log_where_cond = "";
                if(!empty($arm_custom_duration_start_date) && !empty($arm_custom_duration_end_date) && $arm_selected_download_duration == "custom")
                {
                    $arm_custom_duration_start_date = date('Y-m-d 00:00:00', strtotime($arm_custom_duration_start_date));

                    $arm_custom_duration_end_date = date('Y-m-d 23:59:59', strtotime($arm_custom_duration_end_date));

                    $arm_debug_general_log_where_cond = $wpdb->prepare(" AND (arm_general_log_added_date >= %s AND arm_general_log_added_date <= %s)",$arm_custom_duration_start_date,$arm_custom_duration_end_date);
                }
                else if(!empty($arm_selected_download_duration) && $arm_selected_download_duration != "custom")
                {
                    $arm_last_selected_days = date('Y-m-d', strtotime('-'.$arm_selected_download_duration.' days'));

                    $arm_debug_general_log_where_cond = $wpdb->prepare(" AND (arm_general_log_added_date >= %s)",$arm_last_selected_days);
                }


                $arm_general_debug_log_selector_search = "";
                $arm_general_debug_log_selector_arr = array('cron','email');
                $arm_general_debug_log_selector_arr = apply_filters('arm_debug_general_log_selector_external', $arm_general_debug_log_selector_arr);
                if( !in_array($arm_download_key, $arm_general_debug_log_selector_arr) ){
                    foreach($arm_general_debug_log_selector_arr as $arm_general_debug_log_selector_val)
                    {
                        if( empty( $arm_general_debug_log_selector_search ) )
                        {
                            $arm_general_debug_log_selector_search = $wpdb->prepare(" `arm_general_log_event`!=%s ",$arm_general_debug_log_selector_val);
                        }
                        else
                        {
                            $arm_general_debug_log_selector_search .= $wpdb->prepare(" AND `arm_general_log_event`!=%s ",$arm_general_debug_log_selector_val);
                        }
                    }
                }
                else 
                {
                    $arm_general_debug_log_selector_search = $wpdb->prepare(" `arm_general_log_event` =%s",$arm_download_key);
                }

                $total_log_data = $wpdb->get_var($wpdb->prepare("SELECT count(arm_general_log_id) as total_records FROM `" . $tbl_arm_debug_general_log . "` WHERE {$arm_general_debug_log_selector_search} ".$arm_debug_general_log_where_cond." ORDER BY arm_general_log_id DESC") ); //phpcs:ignore --Reason $tbl_arm_debug_general_log is a table name
                
                /*if($total_log_data>10000)
                {
                   $total_log_data = 10000; 
                }*/

                $arm_debug_log_data = $arm_debug_log_arr = array();
                $chunk_data_var = 1000;
                if(!empty($total_log_data))
                {
                    $totaloccurance = ceil($total_log_data/$chunk_data_var);
                    $startfrom = 0-$chunk_data_var;
                    $endto = 0;
                    for($cntr=0;$cntr<$totaloccurance;$cntr++)
                    {
                        $arm_debug_log_arr = array();
                        $startfrom += $chunk_data_var;
                        $endto += $chunk_data_var;

                        //echo '<br>==>'.$startfrom.', '.$endto.'|';

                        //echo "<pre><br>SELECT * FROM `" . $tbl_arm_debug_general_log . "` WHERE {$arm_general_debug_log_selector_search} ORDER BY arm_general_log_id DESC LIMIT ".$startfrom.", ".$endto;

                        $arm_debug_log_arr = $wpdb->get_results($wpdb->prepare("SELECT * FROM `" . $tbl_arm_debug_general_log . "` WHERE {$arm_general_debug_log_selector_search} ORDER BY arm_general_log_id DESC LIMIT %d,%d", $startfrom, $endto ), ARRAY_A); //phpcs:ignore --Reason $tbl_arm_debug_general_log is a table name

                        $arm_debug_log_data = array_merge($arm_debug_log_data,$arm_debug_log_arr);
                    }
                }

                $arm_download_data = json_encode($arm_debug_log_data);

                if( !function_exists('WP_Filesystem' ) )
                {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                WP_Filesystem();
                global $wp_filesystem;

                $arm_debug_log_file_name = "arm_debug_logs_" . $arm_download_key."_".$arm_selected_download_duration ;
                $result = $wp_filesystem->put_contents( MEMBERSHIP_UPLOAD_DIR."/".$arm_debug_log_file_name.".txt", $arm_download_data, 0777 );

                $debug_log_file_name  = '';

                if(class_exists('ZipArchive'))
                {
                    $zip = new ZipArchive();
                    $zip->open(MEMBERSHIP_UPLOAD_DIR."/".$arm_debug_log_file_name.".zip", ZipArchive::CREATE);
                    $zip->addFile(MEMBERSHIP_UPLOAD_DIR."/".$arm_debug_log_file_name.".txt", $arm_debug_log_file_name.".txt");
                    $zip->close();

                    $arm_download_url = MEMBERSHIP_UPLOAD_URL."/".$arm_debug_log_file_name.".zip";

                    $debug_log_file_name = $arm_debug_log_file_name . ".zip";
                } else {
                    $arm_download_url = MEMBERSHIP_UPLOAD_URL."/".$arm_debug_log_file_name.".txt";
                    $debug_log_file_name = $arm_debug_log_file_name . ".txt";
                }
            }
            echo $arm_ajax_pattern_start.''.admin_url( 'admin.php?page=arm_general_settings&action=debug_logs&arm_action=download_log&file=' . $debug_log_file_name . '&arm_wp_nonce=' . $arm_wp_nonce ).''.$arm_ajax_pattern_end; //phpcs:ignore
            exit();
        }

        function arm_download_payment_debug_log()
        {
            global $wpdb, $ARMember, $arm_global_settings, $arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $arm_download_url = '';
            if(!empty($_POST['arm_download_key']))//phpcs:ignore
            {
                $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
                $tbl_arm_debug_payment_log = $ARMember->tbl_arm_debug_payment_log;
                $arm_download_key = $posted_data['arm_download_key'];
                $arm_wp_nonce = !empty($posted_data['arm_wp_nonce'])?sanitize_text_field($posted_data['arm_wp_nonce']):(!empty($posted_data['_wpnonce'])?sanitize_text_field($posted_data['_wpnonce']):'');
                $arm_debug_payment_log_where_cond = "";

                $arm_selected_download_duration = !empty($posted_data['selected_download_duration']) ? $posted_data['selected_download_duration'] : 'all';

                $arm_custom_duration_start_date = !empty($posted_data['arm_filter_pstart_date']) ? $posted_data['arm_filter_pstart_date'] : '';
                $arm_custom_duration_end_date = !empty($posted_data['arm_filter_pend_date']) ? $posted_data['arm_filter_pend_date'] : '';
                if(!empty($arm_custom_duration_start_date) && !empty($arm_custom_duration_end_date) && $arm_selected_download_duration == "custom")
                {
                    $arm_custom_duration_start_date = date('Y-m-d 00:00:00', strtotime($arm_custom_duration_start_date));

                    $arm_custom_duration_end_date = date('Y-m-d 23:59:59', strtotime($arm_custom_duration_end_date));

                    $arm_debug_payment_log_where_cond = $wpdb->prepare(" AND (arm_payment_log_added_date >= %s AND arm_payment_log_added_date <= %s)",$arm_custom_duration_start_date,$arm_custom_duration_end_date);
                }
                else if(!empty($arm_selected_download_duration) && $arm_selected_download_duration != "custom")
                {
                    $arm_last_selected_days = date('Y-m-d', strtotime('-'.$arm_selected_download_duration.' days'));
                    $arm_debug_payment_log_where_cond = $wpdb->prepare(" AND (arm_payment_log_added_date >= %s)",$arm_last_selected_days);
                }

                $arm_debug_payment_log_query = $wpdb->prepare("SELECT * FROM `" . $tbl_arm_debug_payment_log . "` WHERE `arm_payment_log_gateway` = %s AND `arm_payment_log_status` = %d ".$arm_debug_payment_log_where_cond." ORDER BY arm_payment_log_id DESC",$arm_download_key,1); //phpcs:ignore --Reason $tbl_arm_debug_payment_log is a table name


                $arm_payment_debug_log_data = $wpdb->get_results($arm_debug_payment_log_query, ARRAY_A);//phpcs:ignore --Reason $arm_debug_payment_log_query is a prepared query

                $arm_download_data = json_encode($arm_payment_debug_log_data);

                if( !function_exists('WP_Filesystem' ) )
                {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                WP_Filesystem();
                global $wp_filesystem;

                $arm_debug_log_file_name = "arm_debug_logs_".$arm_download_key."_".$arm_selected_download_duration;
                $result = $wp_filesystem->put_contents( MEMBERSHIP_UPLOAD_DIR."/".$arm_debug_log_file_name.".txt", $arm_download_data, 0777 );

                $debug_log_file_name  = '';

                if(class_exists('ZipArchive'))
                {
                    $zip = new ZipArchive();
                    $zip->open(MEMBERSHIP_UPLOAD_DIR."/".$arm_debug_log_file_name.".zip", ZipArchive::CREATE);
                    $zip->addFile(MEMBERSHIP_UPLOAD_DIR."/".$arm_debug_log_file_name.".txt", $arm_debug_log_file_name.".txt");
                    $zip->close();

                    $arm_download_url = MEMBERSHIP_UPLOAD_URL."/".$arm_debug_log_file_name.".zip";

                    $debug_log_file_name = $arm_debug_log_file_name . ".zip";
                } else {
                    $arm_download_url = MEMBERSHIP_UPLOAD_URL."/".$arm_debug_log_file_name.".txt";
                    $debug_log_file_name = $arm_debug_log_file_name . ".txt";
                }
            }

            echo $arm_ajax_pattern_start.''. admin_url( 'admin.php?page=arm_general_settings&action=debug_logs&arm_action=download_log&file=' . $debug_log_file_name . '&arm_wp_nonce=' . $arm_wp_nonce ) .''.$arm_ajax_pattern_end; //phpcs:ignore
            exit();
        }

        function arm_view_payment_debug_log()
        {
            global $wpdb, $ARMember, $arm_global_settings, $arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $arm_payment_debug_log_html = '';
            if(!empty($_POST) && !empty($_POST['arm_debug_log_selector']))//phpcs:ignore
            {
                $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
                $tbl_arm_debug_payment_log = $ARMember->tbl_arm_debug_payment_log;

                $arm_payment_debug_log_selector = $posted_data['arm_debug_log_selector'];
                $date_time_format = $arm_global_settings->arm_get_wp_date_time_format();

                $current_page = !empty($posted_data['page']) ? $posted_data['page'] : 1;
                $perPage = !empty($posted_data['per_page']) ? intval($posted_data['per_page']) : 25;

                $offset = 0;
                if (!empty($current_page) && $current_page > 1) {
                    $offset = ($current_page - 1) * $perPage;
                }

                $totalRecord = $wpdb->get_var( $wpdb->prepare("SELECT COUNT('arm_payment_log_id') FROM `" . $tbl_arm_debug_payment_log . "` WHERE `arm_payment_log_gateway` = %s AND `arm_payment_log_status`= %d ORDER BY arm_payment_log_id DESC",$arm_payment_debug_log_selector,1) ); //phpcs:ignore --Reason $tbl_arm_debug_payment_log is a table name
                $where = $wpdb->prepare('WHERE `arm_payment_log_gateway` = %s AND `arm_payment_log_status`=%d',$arm_payment_debug_log_selector, 1);
                if(!empty($_REQUEST['arm_search']))
				{
					$like_string = '%'.$_REQUEST['arm_search'].'%';
                    $where = $wpdb->prepare('WHERE `arm_payment_log_gateway` = %s AND `arm_payment_log_status`= %d AND (`arm_payment_log_event` LIKE %s OR `arm_payment_log_raw_data` LIKE %s)',$arm_payment_debug_log_selector,1,$like_string,$like_string);
					$totalRecord = $wpdb->get_var("SELECT COUNT('arm_payment_log_id') FROM `" . $tbl_arm_debug_payment_log . "` ".$where." ORDER BY arm_payment_log_id DESC" ); //phpcs:ignore --Reason $tbl_arm_debug_payment_log is a table name
				}

                $armPaymentDebugLimit = (!empty($perPage)) ? " LIMIT $offset, $perPage " : "";

                $arm_payment_debug_log_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM `" . $tbl_arm_debug_payment_log . "` ".$where." ORDER BY arm_payment_log_id DESC {$armPaymentDebugLimit}"), ARRAY_A); //phpcs:ignore --Reason $tbl_arm_debug_payment_log is a table name
                $arm_payment_debug_log_html .= '<div class="arm_payment_debug_log_container dataTables_scroll">';
                $arm_payment_debug_log_html .= '<table class="form-table arm_member_last_subscriptions_table" style="width: 100%; border:none;">';
                $arm_payment_debug_log_html .= '<tr>';
                $arm_payment_debug_log_html .= '<td><b>'.esc_html__('Log ID', 'ARMember').'</b></td>';
                $arm_payment_debug_log_html .= '<td><b>'.esc_html__('Log Name', 'ARMember').'</b></td>';
                $arm_payment_debug_log_html .= '<td class="arm_debug_log_raw_data"><b>'.esc_html__('Log Data', 'ARMember').'</b></td>';
                $arm_payment_debug_log_html .= '<td><b>'.esc_html__('Log Added Date', 'ARMember').'</b></td>';
                $arm_payment_debug_log_html .= '</tr>';
                
                foreach ($arm_payment_debug_log_data as $arm_payment_debug_log_key => $arm_payment_debug_log_val) {
                    $arm_payment_debug_log_html .= '<tr>';
                
                    $arm_payment_debug_log_html .= '<td>'.$arm_payment_debug_log_val['arm_payment_log_id'].'</td>';
                    $arm_payment_debug_log_html .= '<td>'.$arm_payment_debug_log_val['arm_payment_log_event'].'</td>';
                    //$arm_payment_debug_log_html .= '<td>'.$arm_payment_debug_log_val['arm_payment_log_gateway'].'</td>';
                    $arm_payment_debug_log_html .= '<td class="arm_debug_log_raw_data">'.htmlspecialchars(utf8_encode($arm_payment_debug_log_val['arm_payment_log_raw_data'])).'</td>';
                
                    $arm_created_date = date_i18n($date_time_format, strtotime($arm_payment_debug_log_val['arm_payment_log_added_date']));
                    $arm_payment_debug_log_html .= '<td>'.$arm_created_date.'</td>';
                
                    // Apply bottom border to the last row only
                    if ($arm_payment_debug_log_key == count($arm_payment_debug_log_data) - 1) {
                        $arm_payment_debug_log_html .= '<td colspan="4"></td>';
                    }
                
                    $arm_payment_debug_log_html .= '</tr>';
                }
                
                if ($totalRecord <= 0) {
                    $total_column = 4;
                    $arm_payment_debug_log_html .= '<tr>';
                    $arm_payment_debug_log_html .= '<td colspan="'.esc_attr($total_column).'" class="arm_text_align_center" >' . esc_html__('No debug logs found.', 'ARMember') . '</td>';
                    $arm_payment_debug_log_html .= '</tr>';
                }
                
                $arm_payment_debug_log_html .= '</table>';
                $arm_payment_debug_log_html .= '</div>';
                
                $arm_payment_debug_log_html .= '</div>';

                $arm_payment_debug_log_html .= '<div class="arm_membership_history_pagination_block">';
                $historyPaging = $arm_global_settings->arm_get_paging_links($current_page, $totalRecord, $perPage);
                $arm_payment_debug_log_html .= '<div class="arm_membership_history_paging_container">' . $historyPaging . '</div>';
                $arm_payment_debug_log_html .= '</div>';

            }
            echo $arm_ajax_pattern_start.''.$arm_payment_debug_log_html.''.$arm_ajax_pattern_end; //phpcs:ignore;
            exit();
        }

        function arm_view_general_debug_log()
        {
            global $wpdb, $ARMember, $arm_global_settings, $arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $arm_general_debug_log_html = '';
            if(!empty($_POST) && !empty($_POST['arm_debug_log_selector']))//phpcs:ignore
            {
                $tbl_arm_debug_general_log = $ARMember->tbl_arm_debug_general_log;
                $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
                $arm_general_debug_log_selector = $posted_data['arm_debug_log_selector'];

                $arm_general_debug_log_selector_search = "";

                $arm_general_debug_log_selector_arr = array('cron','email');
                $arm_general_debug_log_selector_arr = apply_filters('arm_debug_general_log_selector_external', $arm_general_debug_log_selector_arr);

                if( !in_array($arm_general_debug_log_selector, $arm_general_debug_log_selector_arr) ){
                    foreach($arm_general_debug_log_selector_arr as $arm_general_debug_log_selector_val)
                    {
                        if( empty( $arm_general_debug_log_selector_search ) )
                        {
                            $arm_general_debug_log_selector_search = $wpdb->prepare(" `arm_general_log_event`!=%s ",$arm_general_debug_log_selector_val);
                        }
                        else
                        {
                            $arm_general_debug_log_selector_search .= $wpdb->prepare(" AND `arm_general_log_event`!=%s ",$arm_general_debug_log_selector_val);
                        }
                    }
                }
                else {
                    $arm_general_debug_log_selector_search = $wpdb->prepare(" `arm_general_log_event` =%s ",$arm_general_debug_log_selector);
                }
                
                $date_time_format = $arm_global_settings->arm_get_wp_date_time_format();

                $current_page = !empty($posted_data['page']) ? $posted_data['page'] : 1;
                $perPage = !empty($posted_data['per_page']) ? intval($posted_data['per_page']) : 25;

                $offset = 0;
                if (!empty($current_page) && $current_page > 1) {
                    $offset = ($current_page - 1) * $perPage;
                }

                $arm_search_where = '';
                if(!empty($posted_data['arm_search'])){
                    $like_string = '%'.$_REQUEST['arm_search'].'%';
                    $arm_general_debug_log_selector_search .= $wpdb->prepare(' AND (`arm_general_log_event_name` LIKE %s OR `arm_general_log_raw_data` LIKE %s)',$like_string,$like_string); //phpcs:ignore
                }
                
                $totalRecord = $wpdb->get_var("SELECT COUNT('arm_general_log_id') FROM `" . $tbl_arm_debug_general_log . "` WHERE {$arm_general_debug_log_selector_search} ORDER BY arm_general_log_id DESC"); //phpcs:ignore --Reason $tbl_arm_debug_general_log is a table name
                

                $arm_general_debug_log_html .= '<div class="arm_general_debug_log_container dataTables_scroll">';
                $arm_general_debug_log_html .= '<table class="form-table arm_member_last_subscriptions_table" style="width: 100%; border:none;">';
                $arm_general_debug_log_html .= '<tr>';
                    $arm_general_debug_log_html .= '<td><b>'.esc_html__('Log ID', 'ARMember').'</b></td>';
                    $arm_general_debug_log_html .= '<td><b>'.esc_html__('Log Name', 'ARMember').'</b></td>';
                    $arm_general_debug_log_html .= '<td class="arm_debug_log_raw_data"><b>'.esc_html__('Log Data', 'ARMember').'</b></td>';
                    $arm_general_debug_log_html .= '<td><b>'.esc_html__('Log Added Date', 'ARMember').'</b></td>';
                $arm_general_debug_log_html .= '</tr>';
                
                if($totalRecord>0)
                {
                    $armgeneralDebugLimit = (!empty($perPage)) ? " LIMIT $offset, $perPage " : "";

                    $arm_general_debug_log_data = $wpdb->get_results("SELECT * FROM `" . $tbl_arm_debug_general_log . "` WHERE {$arm_general_debug_log_selector_search} ORDER BY arm_general_log_id DESC {$armgeneralDebugLimit}", ARRAY_A); //phpcs:ignore --Reason $tbl_arm_debug_general_log is a table name
                    
                    foreach($arm_general_debug_log_data as $arm_general_debug_log_key => $arm_general_debug_log_val)
                    {
                        $arm_general_debug_log_val_raw_data = $arm_general_debug_log_val['arm_general_log_raw_data'];
                        $arm_general_debug_log_val_raw_data = htmlspecialchars(utf8_encode($arm_general_debug_log_val_raw_data));
                        if($arm_general_debug_log_val['arm_general_log_event']=='email')
                        {
                            $arm_general_debug_log_val_raw_data = str_replace(array("{ARMNL}"), "<br />", $arm_general_debug_log_val_raw_data);
                        }

                        $arm_general_debug_log_html .= '<tr>';

                            $arm_general_debug_log_html .= '<td>'.$arm_general_debug_log_val['arm_general_log_id'].'</td>';
                            $arm_general_debug_log_html .= '<td>'.$arm_general_debug_log_val['arm_general_log_event_name'].'</td>';
                            $arm_general_debug_log_html .= '<td class="arm_debug_log_raw_data">'.$arm_general_debug_log_val_raw_data.'</td>';

                            $arm_created_date = date_i18n($date_time_format, strtotime($arm_general_debug_log_val['arm_general_log_added_date']));
                            $arm_general_debug_log_html .= '<td>'.$arm_created_date.'</td>';

                        $arm_general_debug_log_html .= '</tr>';
                    }

                }
                else 
                {
                    $total_column = 4;
                    $arm_general_debug_log_html .= '<tr>';
                        $arm_general_debug_log_html .= '<td colspan="'.esc_attr($total_column).'" class="arm_text_align_center" >' . esc_html__('No debug logs found.', 'ARMember') . '</td>';
                    $arm_general_debug_log_html .= '</tr>';
                }

                $arm_general_debug_log_html .= '</table>';
                $arm_general_debug_log_html .= '</div>';

                $arm_general_debug_log_html .= '<div class="arm_membership_history_pagination_block">';
                    $historyPaging = $arm_global_settings->arm_get_paging_links($current_page, $totalRecord, $perPage);
                    $arm_general_debug_log_html .= '<div class="arm_membership_history_paging_container">' . $historyPaging . '</div>';
                $arm_general_debug_log_html .= '</div>';

            }
            echo $arm_ajax_pattern_start.''.$arm_general_debug_log_html.''.$arm_ajax_pattern_end; //phpcs:ignore
            die();
        }
        
        function arm_need_to_cancel_old_subscription_gateways(){
            return apply_filters('arm_need_to_cancel_old_subscription_gateways', array('2checkout'));
        }

        function arm_get_all_payment_gateways() {
            global $wpdb, $ARMember;
            $pay_get_settings_user = get_option('arm_payment_gateway_settings', array());
            $pay_get_settings = maybe_unserialize($pay_get_settings_user);
            /* General Settings */
            $default_payment_gateway = array(
                'paypal' => array('gateway_name' => $this->arm_gateway_name_by_key('paypal')),
                'stripe' => array('gateway_name' => $this->arm_gateway_name_by_key('stripe')),
                'authorize_net' => array('gateway_name' => $this->arm_gateway_name_by_key('authorize_net')),
                '2checkout' => array('gateway_name' => $this->arm_gateway_name_by_key('2checkout')),
                'bank_transfer' => array('gateway_name' => $this->arm_gateway_name_by_key('bank_transfer')),
            );
            $payment_gateways = apply_filters('arm_get_payment_gateways_in_filters', $default_payment_gateway);
            foreach ($payment_gateways as $pgKey => $pgVal) {
                if (isset($pay_get_settings[$pgKey])) {
                    $payment_gateways[$pgKey] = array_merge($pgVal, $pay_get_settings[$pgKey]);
                }
            }
	    
            $payment_gateways = apply_filters('arm_modify_payment_gateways_details_externally', $payment_gateways);
	    
            return $payment_gateways;
        }

        function arm_get_all_payment_gateways_for_setup() {
            global $wpdb, $ARMember;
            $pay_get_settings_unser = get_option('arm_payment_gateway_settings', array());
            $pay_get_settings = maybe_unserialize($pay_get_settings_unser);
            /* General Settings */
            $default_payment_gateway = array(
                'paypal' => array('gateway_name' => $this->arm_gateway_name_by_key('paypal')),
                'stripe' => array('gateway_name' => $this->arm_gateway_name_by_key('stripe')),
                'authorize_net' => array('gateway_name' => $this->arm_gateway_name_by_key('authorize_net')),
                '2checkout' => array('gateway_name' => $this->arm_gateway_name_by_key('2checkout')),
                'bank_transfer' => array('gateway_name' => $this->arm_gateway_name_by_key('bank_transfer')),
            );
            $payment_gateways = apply_filters('arm_get_payment_gateways', $default_payment_gateway);
            foreach ($payment_gateways as $pgKey => $pgVal) {
                if (isset($pay_get_settings[$pgKey])) {
                    $payment_gateways[$pgKey] = array_merge($pgVal, $pay_get_settings[$pgKey]);
                }
            }
	    
            $payment_gateways = apply_filters('arm_modify_payment_gateways_details_externally', $payment_gateways);
	    
            return $payment_gateways;
        }

        function arm_gateway_name_by_key($gateway_key = '') {
            $gatewayNames = array(
                'paypal' => esc_html__('Paypal', 'ARMember'),
                'stripe' => esc_html__('Stripe', 'ARMember'),
                'authorize_net' => esc_html__('Authorize.net', 'ARMember'),
                '2checkout' => esc_html__('2Checkout', 'ARMember'),
                'bank_transfer' => esc_html__('Bank Transfer', 'ARMember'),
                'manual' => esc_html__('Manual', 'ARMember'),
            );
            $gatewayNames = apply_filters('arm_filter_gateway_names', $gatewayNames);
            $pgName = (isset($gatewayNames[$gateway_key])) ? $gatewayNames[$gateway_key] : $gateway_key;
            return apply_filters('arm_gateway_name_by_key', $pgName, $gateway_key);
        }

        function arm_get_active_payment_gateways() {
            global $wpdb, $ARMember;
            $payment_gateways = array();
            $pay_get_settings_unser = $this->arm_get_all_payment_gateways();
            $pay_get_settings = maybe_unserialize($pay_get_settings_unser);
            if (!empty($pay_get_settings)) {
                foreach ($pay_get_settings as $key => $pg) {
                    if (isset($pg['status']) && $pg['status'] == 1) {
                        $payment_gateways[$key] = $pg;
                    }
                }
            }
            return $payment_gateways;
        }

        function arm_get_all_currencies() {
            global $wpdb, $ARMember, $arm_global_settings;
            $paypal_cur = $this->arm_paypal_currency_symbol();
            $stripe_cur = $this->arm_stripe_currency_symbol();
            $authorize_net_cur = $this->arm_authorize_net_currency_symbol();
            $twocheckout_cur = $this->arm_2checkout_currency_symbol();
            $bank_transfer_cur = $this->arm_bank_transfer_currency_symbol();
            $all_currencies = array_merge($paypal_cur, $stripe_cur, $authorize_net_cur, $twocheckout_cur, $bank_transfer_cur);
            /* Add Custom Currency */
            $global_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : $arm_global_settings->arm_get_all_global_settings(true);
            $custom_currency = isset($global_settings['custom_currency']) ? $global_settings['custom_currency'] : array();
            if (isset($custom_currency['status']) && $custom_currency['status'] == 1) {
                $all_currencies[strtoupper($custom_currency['shortname'])] = $custom_currency['symbol'];
            }
            return apply_filters('arm_add_currency_in_default_list', $all_currencies);
        }

        function arm_get_global_currency() {
            global $wpdb, $ARMember, $arm_global_settings;
            $global_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : $arm_global_settings->arm_get_all_global_settings(true);
            $global_currency = !empty($global_settings['paymentcurrency']) ?$global_settings['paymentcurrency'] : '';
            $custom_currency = isset($global_settings['custom_currency']) ? $global_settings['custom_currency'] : array();
            if (isset($custom_currency['status']) && $custom_currency['status'] == 1) {
                $global_currency = $custom_currency['shortname'];
            }
            $global_currency = apply_filters('arm_set_global_currency_outside', $global_currency);
            return $global_currency;
        }

        /**
         * Get Currency Symbol Position From Currency Code
         */
        function arm_currency_symbol_position($currency = '') {
            global $wpdb, $ARMember, $arm_global_settings;
            $symbol_position = array(
                'prefix' => array('USD', 'AUD', 'BRL', 'CAD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'NZD', 'PHP', 'GBP', 'RUB', 'SGD', 'CHF', 'TWD', 'THB', 'TRY', 'INR', 'GHS', 'NGN', 'ZAR', 'HKD', 'BTC', 'BTN', 'CUC', 'CUP', 'GGP', 'IMP', 'JEP', 'KPW', 'RMB', 'SDG', 'SSP', 'VEF', 'VES'),

                'suffix' => array('CZK', 'SEK', 'DKK', 'NOK', 'PLN', 'BHD', 'BYR', 'BYN', 'ERN', 'IQD', 'IRR', 'IRT', 'JOD', 'KWD', 'LYD', 'MRU', 'OMR', 'PRB', 'STN', 'TMT', 'TND'),
            );

            $current_symbol_pos = 'suffix';
            //$global_settings = $arm_global_settings->arm_get_all_global_settings(true);
            $global_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : $arm_global_settings->arm_get_all_global_settings(true);
            
            $custom_currency = isset($global_settings['custom_currency']) ? $global_settings['custom_currency'] : array();
            if (isset($custom_currency['status']) && $custom_currency['status'] == 1 && $custom_currency['place']) {
                $current_symbol_pos = $custom_currency['place'];
            }
            else if (in_array(strtoupper($currency), $symbol_position['prefix'])) {
                $current_symbol_pos = 'prefix';
            } elseif (in_array(strtoupper($currency), $symbol_position['suffix'])) {
                $current_symbol_pos = 'suffix';
            } elseif ($currency == 'EUR'){
                $current_symbol_pos = isset($global_settings['arm_specific_currency_position']) ? $global_settings['arm_specific_currency_position'] : 'suffix';
            }
            return $current_symbol_pos;
        }

        /**
         * Get Currency Symbol Position With Amount From Currency Code And Amount
         */
        function arm_prepare_amount($currency = '', $amount = 0) {
            $new_amount = $amount;
            if (!empty($currency) && !empty($amount) && $amount > 0) {
                $all_currencies = $this->arm_get_all_currencies();
                $symbol = isset($all_currencies[strtoupper($currency)]) ? $all_currencies[strtoupper($currency)] : " ";
                if ($this->arm_currency_symbol_position($currency) == 'prefix') {
                    $new_amount = $symbol . '' . $this->arm_amount_set_separator($currency, $amount);
                } else {
                    $new_amount = $this->arm_amount_set_separator($currency, $amount) . '' . $symbol;
                }
            }
            else if(!empty($currency)){
                $all_currencies = $this->arm_get_all_currencies();
                $symbol = isset($all_currencies[strtoupper($currency)]) ? $all_currencies[strtoupper($currency)] : " ";
                if ($this->arm_currency_symbol_position($currency) == 'prefix') {
                    $new_amount = $symbol . '' . $this->arm_amount_set_separator($currency, $amount);
                } else {
                    $new_amount = $this->arm_amount_set_separator($currency, $amount) . '' . $symbol;
                }
            }
            return $new_amount;
        }
        
        function arm_update_pay_gate_settings() {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_email_settings, $arm_global_settings, $arm_capabilities_global, $arm_subscription_plans, $arm_stripe,$arm_ajax_pattern_start,$arm_ajax_pattern_end;

            $old_pay_gate_settings = get_option('arm_payment_gateway_settings');
            
            $pay_gate_settings = array();
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_payment_gateways'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $ARMember->arm_session_start();
            $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data_extend'), $_POST ); //phpcs:ignore
            //$posted_data = $_POST; //phpcs:ignore
            // sesion handling
            if(!empty($_SESSION['arm_file_upload_arr'])){
                foreach ($_SESSION['arm_file_upload_arr'] as $upload_key => $upload_arr) {
                    if($upload_key=='stripe_popup')
                    {
                        $upload_key = "stripe_popup_icon";
                    }
                    if(isset($posted_data['payment_gateway_settings']['stripe'][$upload_key])){
                        $base_name = $ARMember->arm_get_basename($posted_data['payment_gateway_settings']['stripe'][$upload_key]);
                        if((is_string($upload_arr) && $upload_arr!=$base_name) || (is_array($upload_arr) && !in_array($base_name,$upload_arr))){
                            unset($posted_data['payment_gateway_settings']['stripe'][$upload_key]);
                        }
                    }
                }
            }
            if (is_array($posted_data['payment_gateway_settings']) && !empty($posted_data['payment_gateway_settings'])) {
                foreach ($posted_data['payment_gateway_settings'] as $key => $pg_setting) {
                    $pay_gate_settings[$key] = isset($posted_data['payment_gateway_settings'][$key]) ? $pg_setting : "";
                    $pay_gate_settings[$key]['payment_debug_logs'] = !empty($old_pay_gate_settings[$key]['payment_debug_logs']) ? $old_pay_gate_settings[$key]['payment_debug_logs'] : 0;
                }
            }
            $pay_gate_settings = apply_filters('arm_save_payment_gateway_settings', $pay_gate_settings, $posted_data);
            $pay_gate_settings = arm_array_map($pay_gate_settings);
            update_option('arm_payment_gateway_settings', $pay_gate_settings);
            $this->arm_update_payment_gate_status();

            if( (!empty($old_pay_gate_settings['stripe']) && is_array($old_pay_gate_settings['stripe']) ) && !empty($old_pay_gate_settings['stripe']['status']) && ( ($old_pay_gate_settings['stripe']['status'] != $posted_data['payment_gateway_settings']['stripe']['status'] && $posted_data['payment_gateway_settings']['stripe']['status']==1 ) 
                || ($old_pay_gate_settings['stripe']['stripe_payment_mode'] != $posted_data['payment_gateway_settings']['stripe']['stripe_payment_mode']) 
                || ($old_pay_gate_settings['stripe']['stripe_test_secret_key'] != $posted_data['payment_gateway_settings']['stripe']['stripe_test_secret_key']) 
                || ($old_pay_gate_settings['stripe']['stripe_test_pub_key'] != $posted_data['payment_gateway_settings']['stripe']['stripe_test_pub_key']) 
                || ($old_pay_gate_settings['stripe']['stripe_secret_key'] != $posted_data['payment_gateway_settings']['stripe']['stripe_secret_key']) 
                || ($old_pay_gate_settings['stripe']['stripe_pub_key'] != $posted_data['payment_gateway_settings']['stripe']['stripe_pub_key'])
                )
              )
            {
                $arm_all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id');
                if ( !empty($arm_all_plans) )
                {
                    foreach($arm_all_plans as $arm_plan_key => $arm_plan_val)
                    {
                        $arm_plan_id = $arm_plan_val['arm_subscription_plan_id'];
                        $arm_plan_recurring = new ARM_Plan($arm_plan_id);
                        if($arm_plan_recurring->is_recurring())
                        {
                            $arm_stripe_plan_keys = array();
                            $plan_options = $arm_plan_recurring->options;
                            $arm_plan_keys = $plan_options['payment_cycles'];
                            foreach($arm_plan_keys as $arm_keys => $arm_values)
                            {
                                array_push($arm_stripe_plan_keys, $arm_values['cycle_key']);
                            }
    
    
                            //Table name variable
                            $arm_membership_setup_tbl = $ARMember->tbl_arm_membership_setup;
    
                            //Fetch New Stripe Plan Array from Plan Id
                            $arm_fetch_new_stripe_plan_arr = $arm_stripe->arm_stripe_get_stripe_plan($arm_plan_id);
    
                            $arm_plan_setup_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$arm_membership_setup_tbl} WHERE arm_status = %d", 1)); //phpcs:ignore --Reason $arm_membership_setup_tbl is a table name
    
                            foreach($arm_plan_setup_data as $arm_setup_data_key => $arm_setup_data_val)
                            {
                                $arm_setup_modules_data = maybe_unserialize($arm_setup_data_val->arm_setup_modules);
                                if((!empty($arm_setup_modules_data['modules']['plans']) && !empty($arm_setup_modules_data['modules']['gateways']) && !empty($arm_setup_modules_data['modules']['payment_mode']['stripe'])) && (in_array($arm_plan_id, $arm_setup_modules_data['modules']['plans']) && in_array('stripe', $arm_setup_modules_data['modules']['gateways']) && $arm_setup_modules_data['modules']['payment_mode']['stripe'] != "manual_subscription"))
                                {
                                    if(!empty($arm_setup_modules_data['modules']['stripe_plans'][$arm_plan_id]))
                                    {
                                        $arm_new_setup_data = array();
                                        for($arm_new_plans = 0;$arm_new_plans < count($arm_plan_keys);$arm_new_plans++)
                                        {
                                            //Modify plan cycle data with stripe plan id.
                                            $arm_new_setup_data[$arm_stripe_plan_keys[$arm_new_plans]] = $arm_fetch_new_stripe_plan_arr[$arm_new_plans];
                                        }
    
                                        $arm_setup_modules_data['modules']['stripe_plans'][$arm_plan_id] = $arm_new_setup_data;
    
                                        $arm_setup_new_modules_data = maybe_serialize($arm_setup_modules_data);
                                        $arm_setup_modules_data_update = $wpdb->query($wpdb->prepare("UPDATE {$arm_membership_setup_tbl} SET `arm_setup_modules` = '{$arm_setup_new_modules_data}' WHERE arm_setup_id = %d", $arm_setup_data_val->arm_setup_id)); //phpcs:ignore --Reason $arm_membership_setup_tbl is a table name
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $response = array('message' => 'success');
            echo arm_pattern_json_encode($response);
            die();
        }

        function arm_update_payment_gate_status($currency='') {
            global $wpdb, $ARMember, $arm_global_settings;
            $global_currency = !empty($currency) ? $currency : $this->arm_get_global_currency() ;
            $not_allow_payment = $this->arm_check_currency_status_for_gateways($global_currency);
            $not_allow_payment = apply_filters('arm_check_currency_status', $not_allow_payment, $global_currency);
            if (!empty($not_allow_payment)) {
                $pg_settings = get_option('arm_payment_gateway_settings', array());
                $new_pg_settings = maybe_unserialize($pg_settings);
                foreach ($not_allow_payment as $payment) {
                    if (isset($new_pg_settings[$payment])) {
                        $new_pg_settings[$payment]['status'] = 0;
                    }
                }
                update_option('arm_payment_gateway_settings', $new_pg_settings);
            }
            return;
        }

        function arm_filter_form_posted_plan_data($posted_data, $user_ID,$admin_save_flag=0) {

           
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $payment_done, $paid_trial_stripe_payment_done, $arm_transaction, $arm_members_class, $arm_membership_setup,$arm_manage_coupons;


            
            if (!empty($posted_data) && is_array($posted_data) && (!empty($posted_data['subscription_plan']) || !empty($posted_data['_subscription_plan']) || !empty($posted_data['arm_user_plan']))) {
                /* Set User Plan Values */

                if (isset($posted_data['arm_update_user_from_profile']) && $posted_data['arm_update_user_from_profile'] == 0) {
                    $arm_update_user_from_profile = 0;
                } else {
                    $arm_update_user_from_profile = 1;
                }
            
                if (!empty($posted_data['arm_user_plan'])) {
                    $subscription_plan = $posted_data['arm_user_plan'];
                } else {
                    $subscription_plan = (!empty($posted_data['subscription_plan'])) ? intval($posted_data['subscription_plan']) : 0;
                    if ($subscription_plan == 0) {
                        $subscription_plan = (!empty($posted_data['_subscription_plan'])) ? intval($posted_data['_subscription_plan']) : 0;
                    }
                }
                
                
                $setup_id = isset($posted_data['setup_id']) ? intval($posted_data['setup_id']) : 0;

                $entry_id = isset($posted_data['arm_entry_id']) ? $posted_data['arm_entry_id'] : 0;
                $entry_values = array();
                if(!empty($entry_id))
                {
                    $entry_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id`=%d",$entry_id), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_entries is a table name
                    $entry_values = maybe_unserialize($entry_data['arm_entry_value']); //phpcs:ignore --Reason $ARMember->tbl_arm_entries is a table name
                }
                
                $pgateway = isset($posted_data['payment_gateway']) ? sanitize_text_field($posted_data['payment_gateway']) : '';
                if ($pgateway === '') {
                    $pgateway = isset($posted_data['_payment_gateway']) ? sanitize_text_field($posted_data['_payment_gateway']) : '';
                }
                
                $gateway = (!empty($pgateway)) ? $pgateway : 'manual';
                
                $action = isset($posted_data['action']) ? sanitize_text_field($posted_data['action']) : '';
            
                
                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $all_active_pgs = $this->arm_get_active_payment_gateways();
                foreach ($all_active_pgs as $k => $data) {
                    if (isset($posted_data[$k])) {
                        unset($posted_data[$k]);
                    }
                }
                $posted_data['arm_user_plan'] = $subscription_plan;
                if(!empty($subscription_plan)){
                    if(is_array($subscription_plan)){
                        foreach($subscription_plan as $pid){
                            if(!empty($pid)){ 
                                $userPlanDatameta = get_user_meta($user_ID, 'arm_user_plan_'.$pid, true);
                                $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                                $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                                $update = false;
                                $plan = new ARM_Plan($pid);

                                if ($plan->is_free()) {
                                    $pgateway = '';
                                }

                                $posted_data['pgateway'] = $pgateway;
                                
                                $payment_mode = '';
                                $payment_cycle = '';

                                if ($plan->is_recurring()) { 

                                        $payment_mode_ = !empty($posted_data['arm_selected_payment_mode']) ? sanitize_text_field($posted_data['arm_selected_payment_mode']) : 'manual_subscription';
                                        if(isset($posted_data['arm_payment_mode'][$gateway])){
                                            $payment_mode_ = !empty($posted_data['arm_payment_mode'][$gateway]) ? sanitize_text_field($posted_data['arm_payment_mode'][$gateway]) : 'manual_subscription';
                                        }
                                        else{
                                            $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                                            if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                                                $setup_modules = $setup_data['setup_modules'];
                                                $modules = $setup_modules['modules'];
                                                $payment_mode_ = $modules['payment_mode'][$gateway];
                                            }
                                        }



                                        $payment_mode = 'manual_subscription';
                                        if ($payment_mode_ == 'both') {
                                            $payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? sanitize_text_field($posted_data['arm_selected_payment_mode']) : 'manual_subscription';
                                        } else {
                                            $payment_mode = $payment_mode_;
                                        }
                                    
                                    $payment_cycle = isset($posted_data['arm_selected_payment_cycle']) ? $posted_data['arm_selected_payment_cycle'] : 0;
                                }

                            /**  1) At import time if user with old date imported ( `arm_subscription_start_date`  set in csv ) than set that start date else assign current date . At import time don't allow trial period.
                             * 2) if user add from admin or updated from admin than don't allow trial period.
                             * */                               
                                if (isset($posted_data['arm_subscription_start_date']) && $posted_data['arm_subscription_start_date'] != '' || (isset($posted_data['arm_user_import']) && $posted_data['arm_user_import'] == true || ( !empty($posted_data['arm_action']) && ($posted_data['arm_action'] == 'add_member' || $posted_data['arm_action'] == 'update_member') ) ) ) {

                                    if (isset($posted_data['arm_subscription_start_date']) && $posted_data['arm_subscription_start_date'] != '') {
                                        $nowMysql = strtotime($posted_data['arm_subscription_start_date']);
                                    } else {
                                        if(isset($posted_data['arm_subscription_start_'.$pid]) && !empty($posted_data['arm_subscription_start_'.$pid]) && !is_array($posted_data['arm_subscription_start_'.$pid])){
                                            $nowMysql = strtotime($posted_data['arm_subscription_start_'.$pid]);
                                            unset($posted_data['arm_subscription_start_'.$pid]);
                                        }
                                        else{
                                            $nowMysql = strtotime(current_time('mysql'));
                                        }

                                        if ($plan->is_recurring() && isset($payment_cycle) && !empty($payment_cycle) && is_array($payment_cycle)) {
                                            if(isset($payment_cycle['arm_plan_cycle_'.$pid])){
                                                $payment_cycle = !empty($payment_cycle['arm_plan_cycle_'.$pid]) ? $payment_cycle['arm_plan_cycle_'.$pid] : 0;
                                            }
                                        }
                                    }
                                    $start_time = $nowMysql;
                                    $posted_data['start_time'] = $start_time;
                                } else {
                                    $nowMysql = strtotime(current_time('mysql'));
                                    $posted_data['start_time'] = $nowMysql;
                                     if ($pgateway != 'bank_transfer') {
                                        $trial_and_sub_start_date = $plan->arm_trial_and_plan_start_date($nowMysql, $payment_mode, true, $payment_cycle);
                                        $start_time = isset($trial_and_sub_start_date['subscription_start_date']) ? $trial_and_sub_start_date['subscription_start_date'] : '';

                                        if (isset($trial_and_sub_start_date['arm_trial_start_date']) && $trial_and_sub_start_date['arm_trial_start_date'] != '') {

                                            $userPlanData['arm_trial_start'] = $trial_and_sub_start_date['arm_trial_start_date'];
                                            if (isset($trial_and_sub_start_date['arm_expire_plan_trial']) && $trial_and_sub_start_date['arm_expire_plan_trial'] != '') {
                                                $userPlanData['arm_is_trial_plan'] = '1';
                                                $userPlanData['arm_trial_end'] =  $trial_and_sub_start_date['arm_expire_plan_trial'];
                                                $arm_is_trial = true;
                                            }
                                        }
                                    }
                                }
                                
                                $old_plan_ids = get_user_meta($user_ID, 'arm_user_plan_ids', true);
                                $old_plan_ids = !empty($old_plan_ids) ? $old_plan_ids : array(); 
                                $expire_time = false;
                                if ($pgateway == 'bank_transfer') {
                                    if ($plan->is_recurring()) {
                                        $payment_mode = 'manual_subscription';
                                        $userPlanData['arm_payment_mode'] = $payment_mode;
                                        $userPlanData['arm_payment_cycle'] = $payment_cycle;
                                        $update = false;
                                    }
                                    else{
                                        $userPlanData['arm_payment_mode'] = '';
                                        $userPlanData['arm_payment_cycle'] = '';
                                    }
                                } else {
                                    $plan_options = $plan->options;
                                    $expire_time = $plan->arm_plan_expire_time($start_time, $payment_mode, $payment_cycle);

                                    if(in_array($plan->ID, $old_plan_ids)){
                                            $expired = $userPlanData['arm_expire_plan'];
                                            if ($arm_update_user_from_profile == 0) {
                                                $expire_time = $plan->arm_plan_expire_time_for_renew_action($start_time);
                                                
                                                if ($plan->is_recurring()){ 
                                                    $completed_rec = $userPlanData['arm_completed_recurring'];
                                                    $completed_rec = !empty($completed_rec) ? $completed_rec : 0;
                                                    $userPlanData['arm_completed_recurring'] = $completed_rec + 1;
                                                    $userPlanData['arm_payment_mode'] = $payment_mode;
                                                    $userPlanData['arm_payment_cycle'] = $payment_cycle;

                                                }
                                            } else {
                                                if ($plan->is_recurring()/* && $old_payment_mode == 'manual_subscription' */) {
                                                    $completed_rec = $userPlanData['arm_completed_recurring'];
                                                    if ($completed_rec === '') {
                                                        $userPlanData['arm_completed_recurring'] = 1;
                                                    }
                                                }
                                            }
                                            if (!empty($expired) && $start_time > $expired) {
                                                $update = true;
                                            }
                                        }
                                     else {

                                        if ($plan->is_recurring()/* && $payment_mode == 'manual_subscription'*/) {
                                            if (!$plan->has_trial_period()) {
                                                $userPlanData['arm_completed_recurring'] = 1;
                                            }
                                            else
                                            {
                                                
                                                    if ($arm_update_user_from_profile == 0){
                                                        $userPlanData['arm_completed_recurring'] = 0;
                                                    }
                                                    else{
                                                        $userPlanData['arm_completed_recurring'] = 1;
                                                    }
                                                
                                            }

                                            $userPlanData['arm_payment_mode'] = $payment_mode;
                                            $userPlanData['arm_payment_cycle'] = $payment_cycle;
                                        }
                                        $update = true;
                                    }

                                }
                                
                                if ($update) {
                                    $posted_data['roles'] = (isset($posted_data['roles']) && !empty($posted_data['roles'])) ? $posted_data['roles'] : array();
                                    if (!empty($plan->plan_role)) {
                                        array_push($posted_data['roles'], $plan->plan_role);
                                    }

                                    $userPlanData['arm_start_plan'] = $start_time;
                                    if ($expire_time != false) {
                                        $userPlanData['arm_expire_plan'] = $expire_time;
                                    }
                                    $userPlanData['arm_user_gateway'] = (!empty($pgateway)) ? $pgateway : 'manual';
                                    /* Set Current Plan Detail */
                                    $curPlanDetail = (array) $plan->plan_detail;
                                    $curPlanDetail['arm_user_selected_payment_cycle'] = $payment_cycle;
                                    if(!empty($curPlanDetail['arm_subscription_plan_options']))
                                    {
                                        $arm_meta_plan_options = maybe_unserialize($curPlanDetail['arm_subscription_plan_options']);
                                    }
									$arm_payment_cycles = array();
									
									if($plan->is_recurring())
									{										
										$arm_payment_cycles = $arm_meta_plan_options['payment_cycles'][$payment_cycle];
									}
									
									$arm_subscription_plan_amount = isset($arm_payment_cycles['cycle_amount']) ? $arm_payment_cycles['cycle_amount'] : $curPlanDetail['arm_subscription_plan_amount'];	

                                    $arm_subscription_recurring_time = isset($arm_payment_cycles['recurring_time']) ? $arm_payment_cycles['recurring_time'] : '';
                                    if($arm_subscription_recurring_time=='infinite')
                                    {
                                        $userPlanData['arm_expire_plan'] = '';
                                    }
                                    $arm_modify_subscription_plan_amount = apply_filters('arm_modify_webhook_discount_amount', $arm_subscription_plan_amount, $plan->ID, $entry_id, $entry_values, $pgateway);
                                    
                                    $arm_modify_subscription_plan_amount = apply_filters('arm_modify_subscription_plan_amount_outside', $arm_subscription_plan_amount, $plan, $entry_id, $payment_cycle);
                                    
                                    $curPlanDetail['arm_subscription_plan_amount'] = $arm_modify_subscription_plan_amount;
                                    $arm_meta_plan_options['payment_cycles'][$payment_cycle]['cycle_amount'] = $arm_modify_subscription_plan_amount;
                                    $curPlanDetail['arm_subscription_plan_options'] =  maybe_serialize($arm_meta_plan_options);

                                    $userPlanData['arm_current_plan_detail'] = $curPlanDetail;
                                }
                                update_user_meta($user_ID, 'arm_user_plan_'.$plan->ID, $userPlanData);
                                if(!in_array($plan->ID, $old_plan_ids)){
                                    if($plan->is_recurring()/* && $payment_mode == 'manual_subscription'*/ )
                                    {
                                        $allow_trial = true;

                                        if($action=='add_member'|| $action=='update_member')
                                        {
                                            $allow_trial = false;
                                        }
                                        $arm_next_payment_due_date = $arm_members_class->arm_get_next_due_date($user_ID, $plan->ID, $allow_trial, $payment_cycle, $start_time);  
                                        $userPlanData['arm_next_due_payment'] = $arm_next_payment_due_date;
                                        update_user_meta($user_ID, 'arm_user_plan_'.$plan->ID, $userPlanData);
                                    }
                                }

                                /* Unset unsued details. */
                                if (!empty($payment_done) && $payment_done['status'] === TRUE) {
                                    $log_id = (!empty($payment_done['log_id'])) ? $payment_done['log_id'] : 0;
                                    $selectColumns = '`arm_log_id`, `arm_user_id`, `arm_transaction_id`';
                                    
                                    $armLogTable = $ARMember->tbl_arm_payment_log;
                                    $selectColumns .= ', `arm_token`, `arm_extra_vars`';
                                    
                                    $log_detail = $wpdb->get_row( $wpdb->prepare("SELECT {$selectColumns} FROM `{$armLogTable}` WHERE `arm_log_id`=%d",$log_id) ); //phpcs:ignore --Reason $armLogTable is a table name
                                    if (!empty($log_detail)) {
                                        $arm_first_name=(isset($posted_data['first_name']))?$posted_data['first_name']:'';
                                        $arm_last_name=(isset($posted_data['last_name']))?$posted_data['last_name']:'';
                                        if($user_ID){
                                            $user_detail = get_userdata($user_ID);
                                            if(empty($arm_first_name)){
                                                $arm_first_name=$user_detail->first_name;
                                            }
                                            if(empty($arm_last_name)){
                                                $arm_last_name=$user_detail->last_name;
                                            }    
                                        }
                                        $upData = array('arm_user_id' => $user_ID);
                                        $upData['arm_first_name']=$arm_first_name;
                                        $upData['arm_last_name']=$arm_last_name;
                                        if ($pgateway != 'bank_transfer') {
                                            $extra_vars = maybe_unserialize($log_detail->arm_extra_vars);
                                            if (isset($extra_vars['card_number']) && !empty($extra_vars['card_number'])) {
                                                $extra_vars['card_number'] = $extra_vars['card_number'];
                                            } else {
                                                $extra_vars['card_number'] = isset($posted_data[$pgateway]['card_number']) ? $posted_data[$pgateway]['card_number'] : '-';
                                            }
                                            $upData['arm_extra_vars'] = maybe_serialize($extra_vars);
                                        }
                                        $wpdb->update($armLogTable, $upData, array('arm_log_id' => $log_id));
                                        do_action('arm_after_new_user_update_transaction', $user_ID, $pid, $log_id, $pgateway);
                                        if ($pgateway == 'stripe') {
                                            $userPlanData['arm_stripe'] = array('customer_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
                                        }
                                        if ($pgateway == 'authorize_net') {
                                            $userPlanData['arm_authorize_net'] = array('subscription_id' => $log_detail->arm_token);
                                        }
                                        if ($pgateway == '2checkout') {
                                            $userPlanData['arm_2checkout'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
                                        }
                                        $userPlanData = apply_filters('arm_membership_update_user_meta_from_outside', $userPlanData, $user_ID, $plan, $log_detail, $pgateway);
                                    }
                                    $posted_data['arm_entry_id'] = (!empty($payment_done['entry_id'])) ? $payment_done['entry_id'] : 0;

                                    update_user_meta($user_ID, 'arm_user_plan_'.$plan->ID, $userPlanData);
                                    $arm_manage_coupons->arm_coupon_apply_to_subscription($user_ID, $log_detail, $pgateway, $userPlanData);
                                }

                                if (!empty($paid_trial_stripe_payment_done) && $paid_trial_stripe_payment_done['status'] === TRUE && $paid_trial_stripe_payment_done['gateway'] == $pgateway) {
                                    $log_id = (!empty($paid_trial_stripe_payment_done['log_id'])) ? $paid_trial_stripe_payment_done['log_id'] : 0;
                                    $selectColumns = '`arm_log_id`, `arm_user_id`, `arm_transaction_id`';
                                    if ($pgateway == 'stripe') {
                                        $armLogTable = $ARMember->tbl_arm_payment_log;
                                        $selectColumns .= ', `arm_token`, `arm_extra_vars`';
                                    }
                                    $log_detail = $wpdb->get_row($wpdb->prepare("SELECT {$selectColumns} FROM `{$armLogTable}` WHERE `arm_log_id`=%d",$log_id)); //phpcs:ignore --Reason $armLogTable is a table name
                                    if (!empty($log_detail)) {
                                        $arm_first_name=(isset($posted_data['first_name']))?$posted_data['first_name']:'';
                                        $arm_last_name=(isset($posted_data['last_name']))?$posted_data['last_name']:'';
                                        if($user_ID){
                                            $user_detail = get_userdata($user_ID);
                                            if(empty($arm_first_name)){
                                                $arm_first_name=$user_detail->first_name;
                                            }
                                            if(empty($arm_last_name)){
                                                $arm_last_name=$user_detail->last_name;
                                            }    
                                        }
                                        $upData = array('arm_user_id' => $user_ID);
                                        $upData['arm_first_name']=$arm_first_name;
                                        $upData['arm_last_name']=$arm_last_name;
                                        if ($pgateway == 'stripe') {
                                            $extra_vars = maybe_unserialize($log_detail->arm_extra_vars);
                                            if (isset($extra_vars['card_number']) && !empty($extra_vars['card_number'])) {
                                                $extra_vars['card_number'] = $extra_vars['card_number'];
                                            } else {
                                                $extra_vars['card_number'] = isset($posted_data[$pgateway]['card_number']) ? $posted_data[$pgateway]['card_number'] : '-';
                                            }
                                            $upData['arm_extra_vars'] = maybe_serialize($extra_vars);
                                        }
                                        $wpdb->update($armLogTable, $upData, array('arm_log_id' => $log_id));
                                    }
                                    update_user_meta($user_ID, 'arm_user_plan_'.$plan->ID, $userPlanData);
                                }
                            }
                        }
                    }
                    else{
                        $userPlanDatameta = get_user_meta($user_ID, 'arm_user_plan_'.$subscription_plan, true);
                        $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                        $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);

                        $update = false;

                        $plan = new ARM_Plan($subscription_plan);

                        if ($plan->is_free()) {
                            $pgateway = '';
                        }
                        
                        $posted_data['pgateway'] = $pgateway;

                        $payment_mode = '';
                        $payment_cycle = '';
                         
                        if($plan->is_recurring()){
                            $payment_mode_ = isset($posted_data['arm_payment_mode'][$gateway]) ? sanitize_text_field($posted_data['arm_payment_mode'][$gateway]) : 'both';
                            $payment_mode = 'manual_subscription';
                            if ($payment_mode_ == 'both') {
                                $payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? sanitize_text_field($posted_data['arm_selected_payment_mode']) : 'manual_subscription';
                            } else {
                                $payment_mode = $payment_mode_;
                            }
                            $payment_cycle = isset($posted_data['arm_selected_payment_cycle']) ? $posted_data['arm_selected_payment_cycle'] : 0;
                        }
                        $start_time = strtotime(current_time('mysql'));

                        /*                 *  1) At import time if user with old date imported ( `arm_subscription_start_date`  set in csv ) than set that start date else assign current date . At import time don't allow trial period.
                         * 2) if user add from admin or updated from admin than don't allow trial period.
                         * */
                        if (isset($posted_data['arm_subscription_start_date']) && $posted_data['arm_subscription_start_date'] != '' || (isset($posted_data['arm_user_import']) && $posted_data['arm_user_import'] == true || $action == 'add_member' || $action == 'update_member')) {

                            if (isset($posted_data['arm_subscription_start_date']) && $posted_data['arm_subscription_start_date'] != '') {
                                $nowMysql = strtotime($posted_data['arm_subscription_start_date']);
                              
                            } else {
                                $nowMysql = strtotime(current_time('mysql'));
                            }
                            $start_time = $nowMysql;
                            $posted_data['start_time'] = $nowMysql;
                        } else {
                            $nowMysql = strtotime(current_time('mysql'));
                            $posted_data['start_time'] = $nowMysql;
                             if ($pgateway != 'bank_transfer') {
                                $trial_and_sub_start_date = $plan->arm_trial_and_plan_start_date($nowMysql, $payment_mode, true, $payment_cycle);
                                $start_time = isset($trial_and_sub_start_date['subscription_start_date']) ? $trial_and_sub_start_date['subscription_start_date'] : '';

                                if (isset($trial_and_sub_start_date['arm_trial_start_date']) && $trial_and_sub_start_date['arm_trial_start_date'] != '') {

                                    $userPlanData['arm_trial_start'] = $trial_and_sub_start_date['arm_trial_start_date'];
                                    if (isset($trial_and_sub_start_date['arm_expire_plan_trial']) && $trial_and_sub_start_date['arm_expire_plan_trial'] != '') {

                                        $userPlanData['arm_is_trial_plan'] = '1';

                                        $userPlanData['arm_trial_end'] =  $trial_and_sub_start_date['arm_expire_plan_trial'];
                                        $arm_is_trial = true;
                                    }
                                }
                             }

                        }

                        
                        $old_plan_ids = get_user_meta($user_ID, 'arm_user_plan_ids', true);
                        $old_plan_ids = !empty($old_plan_ids) ? $old_plan_ids : array(); 
                        
                      
                        $expire_time = false;
                        if ($pgateway == 'bank_transfer') {

                            
                            if ($plan->is_recurring()) {
                                $payment_mode = 'manual_subscription';
                                $update = false;
                                $userPlanData['arm_payment_mode'] = $payment_mode;
                                $userPlanData['arm_payment_cycle'] = $payment_cycle;
                            }
                            else{
                                $userPlanData['arm_payment_mode'] = '';
                                $userPlanData['arm_payment_cycle'] = '';
                            }

                        } else {
                            $plan_options = $plan->options;
                            $expire_time = $plan->arm_plan_expire_time($start_time, $payment_mode, $payment_cycle);

                            if(in_array($plan->ID, $old_plan_ids)){

                                    $expired = $userPlanData['arm_expire_plan'];
                                    if ($arm_update_user_from_profile == 0) {
                                        $expire_time = $plan->arm_plan_expire_time_for_renew_action($start_time);
                                        $completed_rec = $userPlanData['arm_completed_recurring'];
                                        $completed_rec = !empty($completed_rec) ? $completed_rec : 0;
                                        if ($plan->is_recurring()) {
                                            $userPlanData['arm_completed_recurring'] = $completed_rec + 1;
                                            $userPlanData['arm_payment_mode'] = $payment_mode;
                                            $userPlanData['arm_payment_cycle'] = $payment_cycle;
                                        }
                                    } else {
                                        if ($plan->is_recurring() /*&& $old_payment_mode == 'manual_subscription'*/) {
                                            $completed_rec = $userPlanData['arm_completed_recurring'];
                                            if ($completed_rec === '') {
                                                $userPlanData['arm_completed_recurring'] = 1;
                                            }
                                        }
                                    }
                                    if (!empty($expired) && $start_time > $expired) {
                                        $update = true;
                                    }
                                }
                             else {
                                if ($plan->is_recurring()/* && $payment_mode == 'manual_subscription'*/) {
                                    if (!$plan->has_trial_period()) {
                                        $userPlanData['arm_completed_recurring'] = 1;
                                    }
                                    else
                                    {
                                        if ($arm_update_user_from_profile == 0){
                                            $userPlanData['arm_completed_recurring'] = 0;
                                        }
                                        else{
                                            $userPlanData['arm_completed_recurring'] = 1;
                                        }
                                    }
                                    $userPlanData['arm_payment_mode'] = $payment_mode;
                                    $userPlanData['arm_payment_cycle'] = $payment_cycle;
                                }
                                $update = true;
                            }

                    }
                    if ($update) {


                        /* Assign Membership Plan Role To User if member form has no role field */
                        if (empty($posted_data['roles']) && !empty($plan->plan_role)) {
                            $posted_data['roles'] = $plan->plan_role;
                        }

                        $posted_data['arm_user_plan'] = $subscription_plan;
                        $userPlanData['arm_start_plan'] = $start_time;
                        if ($expire_time != false) {
                            $userPlanData['arm_expire_plan'] = $expire_time;
                        }
                        $userPlanData['arm_user_gateway'] = (!empty($pgateway)) ? $pgateway : 'manual';
                        /* Set Current Plan Detail */

                        $curPlanDetail = (array) $plan->plan_detail;
                        $curPlanDetail['arm_user_selected_payment_cycle'] = $payment_cycle;
                        $arm_meta_plan_options = maybe_unserialize($curPlanDetail['arm_subscription_plan_options']);
                        $curPlanDetail['arm_subscription_plan_amount'] = !empty($curPlanDetail['arm_subscription_plan_amount']) ? $curPlanDetail['arm_subscription_plan_amount'] : 0;
                        $arm_subscription_plan_amount = !empty($arm_meta_plan_options['payment_cycles'][$payment_cycle]) ? $arm_meta_plan_options['payment_cycles'][$payment_cycle]['cycle_amount'] : $curPlanDetail['arm_subscription_plan_amount'];

                        $arm_modify_subscription_plan_amount = apply_filters('arm_modify_webhook_discount_amount', $arm_subscription_plan_amount, $plan->ID, $entry_id, $entry_values, $pgateway);

                        
                        $arm_modify_subscription_plan_amount = apply_filters('arm_modify_subscription_plan_amount_outside', $arm_subscription_plan_amount, $plan, $entry_id, $payment_cycle);
                        
                        $curPlanDetail['arm_subscription_plan_amount'] = $arm_modify_subscription_plan_amount;
                        $arm_meta_plan_options['payment_cycles'][$payment_cycle]['cycle_amount'] = $arm_modify_subscription_plan_amount;
                        $curPlanDetail['arm_subscription_plan_options'] =  maybe_serialize($arm_meta_plan_options);

                        $userPlanData['arm_current_plan_detail'] = $curPlanDetail;

                    }
                    update_user_meta($user_ID, 'arm_user_plan_'.$plan->ID, $userPlanData);
                    if(!in_array($plan->ID, $old_plan_ids)){
                        if($plan->is_recurring()/* && $payment_mode == 'manual_subscription'*/ )
                        {
                            $allow_trial = true;

                            if($action=='add_member'|| $action=='update_member')
                            {
                                $allow_trial = false;
                            }


                            
                            $arm_next_payment_due_date = $arm_members_class->arm_get_next_due_date($user_ID, $plan->ID, $allow_trial, $payment_cycle, $start_time);  
                            $userPlanData['arm_next_due_payment'] = $arm_next_payment_due_date;


                            update_user_meta($user_ID, 'arm_user_plan_'.$plan->ID, $userPlanData);
                        }
                    }
                    
                    /* Unset unsued details. */
                    if (!empty($payment_done) && $payment_done['status'] === TRUE) {
                        $log_id = (!empty($payment_done['log_id'])) ? $payment_done['log_id'] : 0;
                        $selectColumns = '`arm_log_id`, `arm_user_id`, `arm_transaction_id`';
                        
                        $armLogTable = $ARMember->tbl_arm_payment_log;
                        $selectColumns .= ', `arm_token`, `arm_extra_vars`';
                        
                        $log_detail = $wpdb->get_row( $wpdb->prepare("SELECT {$selectColumns} FROM `{$armLogTable}` WHERE `arm_log_id`=%d",$log_id) );//phpcs:ignore --Reason: $armLogTable is a table name
                        if (!empty($log_detail)) {
                            $arm_first_name=(isset($posted_data['first_name']))?$posted_data['first_name']:'';
                            $arm_last_name=(isset($posted_data['last_name']))?$posted_data['last_name']:'';
                            if($user_ID){
                                $user_detail = get_userdata($user_ID);
                                if(empty($arm_first_name)){
                                    $arm_first_name=$user_detail->first_name;
                                }
                                if(empty($arm_last_name)){
                                    $arm_last_name=$user_detail->last_name;
                                }    
                            }
                            $upData = array('arm_user_id' => $user_ID);
                            $upData['arm_first_name']=$arm_first_name;
                            $upData['arm_last_name']=$arm_last_name;
                            if ($pgateway != 'bank_transfer') {
                                $extra_vars = !empty($log_detail->arm_extra_vars) ? maybe_unserialize($log_detail->arm_extra_vars) : array();
                                if (isset($extra_vars['card_number']) && !empty($extra_vars['card_number'])) {
                                    $extra_vars['card_number'] = $extra_vars['card_number'];
                                } else {
                                    $extra_vars['card_number'] = isset($posted_data[$pgateway]['card_number']) ? $posted_data[$pgateway]['card_number'] : '-';
                                }
                                $upData['arm_extra_vars'] = maybe_serialize($extra_vars);
                            }
                            $wpdb->update($armLogTable, $upData, array('arm_log_id' => $log_id));
                            do_action('arm_after_new_user_update_transaction', $user_ID, $plan->ID, $log_id, $pgateway);
                            if ($pgateway == 'stripe') {
                                $userPlanData['arm_stripe'] = array('customer_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
                            }
                            if ($pgateway == 'authorize_net') {
                                $userPlanData['arm_authorize_net'] = array('subscription_id' => $log_detail->arm_token);
                            }
                            if ($pgateway == '2checkout') {
                                $userPlanData['arm_2checkout'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
                            }
                            $userPlanData = apply_filters('arm_membership_update_user_meta_from_outside', $userPlanData, $user_ID, $plan, $log_detail, $pgateway);
                            
                        }
                        $posted_data['arm_entry_id'] = (!empty($payment_done['entry_id'])) ? $payment_done['entry_id'] : 0;

                        update_user_meta($user_ID, 'arm_user_plan_'.$plan->ID, $userPlanData);
                        $arm_manage_coupons->arm_coupon_apply_to_subscription($user_ID, $log_detail, $pgateway, $userPlanData);
                    }

                    if (!empty($paid_trial_stripe_payment_done) && $paid_trial_stripe_payment_done['status'] === TRUE && $paid_trial_stripe_payment_done['gateway'] == $pgateway) {
                    $log_id = (!empty($paid_trial_stripe_payment_done['log_id'])) ? $paid_trial_stripe_payment_done['log_id'] : 0;
                    $selectColumns = '`arm_log_id`, `arm_user_id`, `arm_transaction_id`';
                    if ($pgateway == 'stripe') {
                        $armLogTable = $ARMember->tbl_arm_payment_log;
                        $selectColumns .= ', `arm_token`, `arm_extra_vars`';
                    }
                    $log_detail = $wpdb->get_row( $wpdb->prepare("SELECT {$selectColumns} FROM `{$armLogTable}` WHERE `arm_log_id`=%d",$log_id)); //phpcs:ignore --Reason $armLogTable is a table name
                    if (!empty($log_detail)) {
                        $arm_first_name=(isset($posted_data['first_name']))?$posted_data['first_name']:'';
                        $arm_last_name=(isset($posted_data['last_name']))?$posted_data['last_name']:'';
                        if($user_ID){
                            $user_detail = get_userdata($user_ID);
                            if(empty($arm_first_name)){
                                $arm_first_name=$user_detail->first_name;
                            }
                            if(empty($arm_last_name)){
                                $arm_last_name=$user_detail->last_name;
                            }    
                        }
                        $upData = array('arm_user_id' => $user_ID);
                        $upData['arm_first_name']=$arm_first_name;
                        $upData['arm_last_name']=$arm_first_name;
                        if ($pgateway == 'stripe') {
                            $extra_vars = maybe_unserialize($log_detail->arm_extra_vars);
                            if (isset($extra_vars['card_number']) && !empty($extra_vars['card_number'])) {
                                $extra_vars['card_number'] = $extra_vars['card_number'];
                            } else {
                                $extra_vars['card_number'] = isset($posted_data[$pgateway]['card_number']) ? $posted_data[$pgateway]['card_number'] : '-';
                            }
                            $upData['arm_extra_vars'] = maybe_serialize($extra_vars);
                        }
                        $wpdb->update($armLogTable, $upData, array('arm_log_id' => $log_id));
                    }
                    update_user_meta($user_ID, 'arm_user_plan_'.$plan->ID, $userPlanData);
                }
                }
                }
                unset($posted_data['payment_done']);
                unset($posted_data['subscription_plan']);
                unset($posted_data['_subscription_plan']);
                unset($posted_data['payment_gateway']);
                unset($posted_data['_payment_gateway']);
            }
            
            if (!empty($posted_data['arm_user_future_plan'])) {
                $subscription_future_plan = $posted_data['arm_user_future_plan'];
            } else {
                $subscription_future_plan = array();
            }

            if(!empty($posted_data['role']))
            {
                $meta_key = "role";
                $arm_form_id = isset($posted_data['arm_form_id']) ? intval($posted_data['arm_form_id']) : '';
                
                $user_form_id = !empty($arm_form_id) ? $arm_form_id : get_user_meta($user_ID, 'arm_form_id', true);

                $form = new ARM_Form('id', $user_form_id);

                if (!$form->exists() || $form->type != 'registration') {
                    $user_form_id = $default_form_id = $arm_member_forms->arm_get_default_form_id('registration');
                    $form = new ARM_Form('id', $default_form_id);
                }

                if ($form->exists() && !empty($form->fields)) 
                {
                    $arm_role_field_options = $arm_member_forms->arm_get_field_option_by_meta($meta_key, $user_form_id);
                    $arm_role_field_option = isset($arm_role_field_options['options']) ? $arm_role_field_options['options'] : '';
                    if(!empty($arm_role_field_option))
                    {
                        if (is_array($posted_data['role'])) {
                            $count = 0;
                            foreach ($posted_data['role'] as $posted_data_role) {
                                if(!array_key_exists($posted_data_role,$arm_role_field_option))
                                {
                                    if (($arm_role_key = array_search($posted_data_role, $posted_data['role'])) !== false) {
                                        unset($posted_data['role'][$arm_role_key]);
                                    }
                                }
                            }
                        }
                        else {
                            if(!array_key_exists($posted_data['role'],$arm_role_field_option))
                            {
                                //unset($posted_data['role']);
                                $posted_data['role'] = "";
                            }
                        }
                    }
                    else {
                        //unset($posted_data['role']);
                        if(is_array($posted_data['role']))
                        {
                            if(in_array('administrator', $posted_data['role']))
                            {
                                if (($arm_role_key = array_search('administrator', $posted_data['role'])) !== false) {
                                    unset($posted_data['role'][$arm_role_key]);
                                }
                            }
                        }
                        else {
                            if(!empty($posted_data['role']) && $posted_data['role']=='administrator')
                            {
                                $posted_data['role'] = "";
                            }
                        }
                    }
                }
                else {
                    //unset($posted_data['role']);
                    if(is_array($posted_data['role']))
                    {
                        if(in_array('administrator', $posted_data['role']))
                        {
                            if (($arm_role_key = array_search('administrator', $posted_data['role'])) !== false) {
                                unset($posted_data['role'][$arm_role_key]);
                            }
                        }
                    }
                    else {
                        if(!empty($posted_data['role']) && $posted_data['role']=='administrator')
                        {
                            $posted_data['role'] = "";
                        }
                    }
                }
            }

            if(!empty($posted_data['roles']) && ( ( !empty( $posted_data['arm_user_plan'] ) ) || ( empty( $posted_data['arm_user_plan'] ) && empty($admin_save_flag) ) ) )
            {
                $meta_key = "roles";
                $arm_form_id = isset($posted_data['arm_form_id']) ? $posted_data['arm_form_id'] : '';
                
                $user_form_id = !empty($arm_form_id) ? $arm_form_id : get_user_meta($user_ID, 'arm_form_id', true);

                $form = new ARM_Form('id', $user_form_id);

                if (!$form->exists() || $form->type != 'registration') {
                    $user_form_id = $default_form_id = $arm_member_forms->arm_get_default_form_id('registration');
                    $form = new ARM_Form('id', $default_form_id);
                }

                if ($form->exists() && !empty($form->fields)) 
                {
                    $arm_role_field_options = $arm_member_forms->arm_get_field_option_by_meta($meta_key, $user_form_id);
                    $arm_role_field_option = isset($arm_role_field_options['options']) ? $arm_role_field_options['options'] : '';
                    if(!empty($arm_role_field_option))
                    {
                        if (is_array($posted_data['roles'])) {
                            $count = 0;
                            foreach ($posted_data['roles'] as $posted_data_role) {
                                if(!array_key_exists($posted_data_role,$arm_role_field_option))
                                {
                                    if (($arm_role_key = array_search($posted_data_role, $posted_data['roles'])) !== false) {
                                        unset($posted_data['roles'][$arm_role_key]);
                                    }
                                }
                            }
                        }
                        else {
                            if(!array_key_exists($posted_data['roles'],$arm_role_field_option))
                            {
                                //unset($posted_data['roles']);
                                $posted_data['roles'] = "";
                            }
                        }
                    }
                    else {
                        //unset($posted_data['roles']);
                        //$posted_data['roles'] = "";
                        if(is_array($posted_data['roles']))
                        {
                            if(in_array('administrator', $posted_data['roles']))
                            {
                                if (($arm_role_key = array_search('administrator', $posted_data['roles'])) !== false) {
                                    unset($posted_data['roles'][$arm_role_key]);
                                }
                            }
                        }
                        else {
                            if(!empty($posted_data['roles']) && $posted_data['roles']=='administrator')
                            {
                                $posted_data['roles'] = "";
                            }
                        }
                    }
                }
                else {
                    //unset($posted_data['roles']);
                    if(is_array($posted_data['roles']))
                    {
                        if(in_array('administrator', $posted_data['roles']))
                        {
                            if (($arm_role_key = array_search('administrator', $posted_data['roles'])) !== false) {
                                unset($posted_data['roles'][$arm_role_key]);
                            }
                        }
                    }
                    else {
                        if(!empty($posted_data['roles']) && $posted_data['roles']=='administrator')
                        {
                            $posted_data['roles'] = "";
                        }
                    }
                }
            }
            
            $posted_data = array_merge(array('arm_user_future_plan' => $subscription_future_plan), $posted_data);
            
            return $posted_data;
        }

        function arm_save_payment_log($log_data = array()) {
            global $wp, $wpdb, $ARMember, $arm_subscription_plans, $arm_manage_coupons, $arm_transaction;
            $payment_log_id = $arm_transaction->arm_add_transaction($log_data);
            return $payment_log_id;
        }

        function arm_bank_transfer_payment_gateway_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {
            global $wpdb, $ARMember, $arm_global_settings, $payment_done, $arm_membership_setup, $arm_manage_coupons, $arm_subscription_plans, $arm_transaction, $arm_debug_payment_log_id,$arm_manage_communication,$is_multiple_membership_feature;

            $general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
            $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;
            

            if ($payment_gateway == 'bank_transfer') {
                do_action('arm_payment_log_entry', 'bank_transfer', 'Bank Transfer Posted Data', 'armember', $posted_data, $arm_debug_payment_log_id);
                $entry_data = $this->arm_get_entry_data_by_id($entry_id);
                //if (!empty($entry_data) && $posted_data['arm_currency'] == $entry_data['arm_currency'] && $posted_data['arm_total_payable_amount'] == $entry_data['arm_total_payable_amount']) { //payment verification with currency and amount
                    $posted_data = apply_filters('arm_handle_bank_transfer_before_payment_from_outside',$posted_data,$entry_data);

                    $arm_return_data = array();
                    $arm_return_data = apply_filters('arm_calculate_payment_gateway_submit_data', $arm_return_data, $payment_gateway, $payment_gateway_options, $posted_data, $entry_id);
                    
                    $user_id = $entry_data['arm_user_id'];
                    $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
                    do_action('arm_payment_log_entry', 'bank_transfer', 'Bank Transfer Entry values', 'armember', $entry_values, $arm_debug_payment_log_id);
                    $payment_cycle = $entry_values['arm_selected_payment_cycle']; 
                    $tax_percentage = isset($entry_values['tax_percentage']) ? $entry_values['tax_percentage'] : 0; 
                    $tax_display_type = !empty($entry_values['arm_tax_include_exclude_flag']) ? $entry_values['arm_tax_include_exclude_flag'] : 0; 

                  
                    $setup_id = (isset($entry_values['setup_id']) && !empty($entry_values['setup_id'])) ? $entry_values['setup_id'] : 0 ; 

                    $plan_id = !empty($arm_return_data['arm_plan_id']) ? $arm_return_data['arm_plan_id'] : 0;
                    $plan = !empty($arm_return_data['arm_plan_obj']) ? $arm_return_data['arm_plan_obj'] : array();
                    if(empty($plan_id)){
                        $plan_id = (!empty($posted_data['_subscription_plan'])) ? $posted_data['_subscription_plan'] : 0;
                        $plan = new ARM_Plan($plan_id);
                    }
                    
                    $payment_mode = 'one_time';
                    if ($plan->is_recurring()) {
                        $payment_mode = "manual_subscription";
                    }
                   
                    $arm_user_old_plan = (isset($posted_data['old_plan_id']) && !empty($posted_data['old_plan_id'])) ? explode(",", $posted_data['old_plan_id']) : array();
                    if (!empty($arm_user_old_plan)) {
                        if (in_array($plan_id, $arm_user_old_plan)) {
                            $recurring_payment_flag = '1';
                            $is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $plan_id, $payment_mode,$recurring_payment_flag);
                            if($is_recurring_payment){
                                $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                                $oldPlanDetail = $planData['arm_current_plan_detail'];
                                if (!empty($oldPlanDetail)) {
                                    $plan = new ARM_Plan(0);
                                    $plan->init((object) $oldPlanDetail);
                                }
                            }
                        }
                    }
                    
                    if($plan->is_recurring())
                    {
                        $recurring_data = $plan->prepare_recurring_data($payment_cycle);
                        $recurring_data = apply_filters('arm_modify_recurring_data_outside', $recurring_data, $plan, $plan->amount, $entry_id, $payment_cycle);
                        $amount = $recurring_data['amount'];
                    }
                    else{
                         $amount = $plan->amount;
                    }
                    $amount = str_replace(',','', $amount);
                    $arm_extra_vars = array();
                    $arm_extra_vars['plan_amount'] = $amount;
                    
                    if (!$plan->is_recurring() || $payment_mode == "manual_subscription") {
                
                        $bank_info = isset($posted_data['bank_transfer']) ? $posted_data['bank_transfer'] : array();
                        $arm_is_trial = '0';
                        
                        $arm_user_old_plan = (!empty($arm_user_old_plan)) ? $arm_user_old_plan : array(); 

                        if ($plan->is_recurring() && $plan->has_trial_period() && ( ( !$is_multiple_membership_feature->isMultipleMembershipFeature && empty($arm_user_old_plan) ) || ( $is_multiple_membership_feature->isMultipleMembershipFeature && !empty( $arm_user_old_plan ) && !in_array( $plan_id,$arm_user_old_plan ) ) ) ) {
                            $arm_is_trial = '1';
                            $arm_extra_vars['trial'] = $recurring_data['trial'];
                            $arm_extra_vars['arm_is_trial'] = $arm_is_trial;
                            $amount = $plan->options['trial']['amount'];
                        }
                        $coupon_code = (!empty($posted_data['arm_coupon_code'])) ? $posted_data['arm_coupon_code'] : '';
                        $coupon_amount = 0;
                        $couponType = '';
                        $couponDiscount = 0;
                        $arm_coupon_on_each_subscriptions=0;
                        if ($arm_manage_coupons->isCouponFeature && !empty($coupon_code)) {
                            $couponApply = $arm_manage_coupons->arm_apply_coupon_code($coupon_code, $plan, $setup_id, $payment_cycle, $arm_user_old_plan);
                            $global_currency = $this->arm_get_global_currency();
                            $couponType = ($couponApply['discount_type'] != 'percentage') ? $global_currency : "%";
                            $couponDiscount = $couponApply['discount'];
                            $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                            $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                            $discount_amt = str_replace(',','', $discount_amt);
                            $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : '0';

                            $arm_extra_vars['coupon'] = array(
                                'coupon_code' => $posted_data['arm_coupon_code'],
                                'amount' => $coupon_amount,
                                'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions,
                            );
                                                            
                        }

                        if (!empty($coupon_amount) && $coupon_amount > 0) {
                            $amount = $discount_amt;
                        }

                        $return_type = "amount";
                        $amount = apply_filters('arm_modify_membership_plan_amount_external', $amount,$return_type, $user_id, $plan_id, $payment_cycle,$plan,$entry_id);

                        if($tax_percentage > 0 && empty($arm_return_data['arm_is_prorated'])){
                            if( empty( $tax_display_type ) )
                            {
                                $arm_extra_vars['arm_tax_include_exclude_flag'] = 0;
                                $tax_amount = ($amount*$tax_percentage)/100;
                                $tax_amount = number_format((float)$tax_amount , $arm_currency_decimal, '.', '');
                                $amount = $amount + $tax_amount;
                            }
                            else
                            {
                                $arm_extra_vars['arm_tax_include_exclude_flag'] = 1;
                                $tax_amount = $arm_membership_setup->arm_calculate_included_tax($amount,$tax_percentage);
                                $tax_amount = number_format((float)$tax_amount , $arm_currency_decimal, '.', '');
                            }
                            
                            $arm_extra_vars['tax_amount'] = $tax_amount;
                            $arm_extra_vars['tax_percentage'] = $tax_percentage;
                        }
                        $amount = number_format((float)$amount , $arm_currency_decimal, '.', '');
                        $arm_extra_vars['paid_amount'] = $amount;
                        $arm_first_name = $arm_last_name ='';
                        if(isset($posted_data['first_name']) && isset($posted_data['last_name'])){
                            $arm_first_name=$posted_data['first_name'];
                            $arm_last_name=$posted_data['last_name'];
                        }else if(!empty($user_id)){
                            $user_detail = get_userdata($user_id);
                            $arm_first_name=$user_detail->first_name;
                            $arm_last_name=$user_detail->last_name;
                        }
                        $payment_data = array(
                            'arm_user_id' => $user_id,
                            'arm_first_name'=>$arm_first_name,
                            'arm_last_name'=>$arm_last_name,
                            'arm_plan_id' => $plan->ID,
                            'arm_old_plan_id' => isset($posted_data['old_plan_id']) ? $posted_data['old_plan_id'] : 0,
                            'arm_payer_email' => $entry_data['arm_entry_email'],
                            'arm_transaction_id' => (isset($bank_info['transaction_id']) && $amount > 0 ) ? $bank_info['transaction_id'] : '-',
                            'arm_bank_name' => (isset($bank_info['bank_name'])) ? $bank_info['bank_name'] : '',
                            'arm_account_name' => (isset($bank_info['account_name'])) ? $bank_info['account_name'] : '',
                            'arm_additional_info' => (isset($bank_info['additional_info'])) ? $bank_info['additional_info'] : '',
                            'arm_payment_transfer_mode' => (isset($bank_info['transfer_mode'])) ? $bank_info['transfer_mode'] : '',
                            'arm_amount' => $amount,
                            'arm_payment_gateway'=>'bank_transfer',
                            'arm_payment_type' => ($payment_mode=='manual_subscription')?'subscription':'one_time',
                            'arm_transaction_payment_type' => ($payment_mode=='manual_subscription')?'subscription':'one_time',
                            'arm_payment_mode' => $payment_mode,
                            'arm_payment_cycle' => $payment_cycle,
                            'arm_currency' => $this->arm_get_global_currency(),
                            'arm_extra_vars' => maybe_serialize($arm_extra_vars),
                            'arm_coupon_code' => $coupon_code,
                            'arm_coupon_discount' => $couponDiscount,
                            'arm_coupon_discount_type' => $couponType,
                            'arm_transaction_status' => 0,
                            'arm_is_trial' => $arm_is_trial,
                            'arm_created_date' => current_time('mysql'),
                            'arm_payment_date' => current_time('mysql'),
                            'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions,
                        );
                        $payment_data = apply_filters('arm_change_posted_data_before_bank_payment_outside', $payment_data, $posted_data);

                        do_action('arm_after_bank_transfer_payment',$plan,$payment_mode,$amount,$coupon_code,$arm_is_trial);
                        do_action('arm_before_add_transaction', $payment_data);
                        
                        $arm_last_invoice_id = get_option('arm_last_invoice_id', 0);
                        $arm_last_invoice_id++;
                        $payment_data['arm_invoice_id'] = $arm_last_invoice_id;
                        
                        do_action('arm_payment_log_entry', 'bank_transfer', 'Save payment log data', 'armember', $payment_data, $arm_debug_payment_log_id);

                        $payment_log = $wpdb->insert($ARMember->tbl_arm_payment_log, $payment_data);
                        $payment_log_id = $wpdb->insert_id;
                        
                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $payment_data['arm_plan_id'], 'user_id' => $user_id, 'action' => 'on_purchase_subscription_bank_transfer'));
                        
                        $payment_data['arm_log_id'] = $payment_log_id;
                        
                        do_action('arm_after_add_transaction', $payment_data);

                        
                        $payment_done = array();
                        if ($payment_log_id) {
                            update_option('arm_last_invoice_id', $arm_last_invoice_id);
                            if ($coupon_code != '') {

                                $arm_manage_coupons->arm_update_coupon_used_count($coupon_code);
                            }
                            $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                        }
                    } else {
                        $err_msg =  esc_html__('Selected plan is not valid for bank transfer.', 'ARMember');
                        $payment_done = array('status' => FALSE, 'error' => $err_msg);
                    }
                /* }
                else{
                    $err_msg =  esc_html__('Payment verification failed', 'ARMember');
                    $payment_done = array('status' => FALSE, 'error' => $err_msg);
                } */
            }
        }

        function arm_get_entry_data_by_id($entry_id = 0) {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans;
            $entry_data = array();
            if (!empty($entry_id) && $entry_id != 0) {
                $entry_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id` = %d LIMIT 1",$entry_id), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_entries is a table name
                if (!empty($entry_data)) {
                    $entry_data['arm_description'] = maybe_unserialize($entry_data['arm_description']);
                    $entry_data['arm_entry_value'] = maybe_unserialize($entry_data['arm_entry_value']);
                }
            }
            return $entry_data;
        }

        function arm_get_credit_card_box($type = 'stripe', $column_type = '1', $fieldPosition = 'left', $errPos = 'right', $form_style='writer',$arm_card_image = '') {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans;
            $type = (!empty($type)) ? $type : 'no_gateway';
            $gateways = $this->arm_get_all_payment_gateways();
	    
            $common_message = $arm_global_settings->common_message;
            $common_message = apply_filters('arm_modify_common_message_settings_externally', $common_message);
	    
            $gateways_opts = $gateways[$type];
            $cc_html = '';
            $ccFieldsHtml = '';
            $ccFieldsHtml .= apply_filters('arm_add_before_cc_field_filter', $ccFieldsHtml);

            $arm_card_holder_label_filter = apply_filters('arm_payment_card_holder_filter', $allowed_arr = array(), $type);

            $field_random_id = arm_generate_random_code();

            $card_imgs = '';
            foreach (array('card_holder_name', 'card_number', 'exp_month', 'exp_year', 'cvc') as $key) {
                $fieldLabel = $fieldClass = $fieldAttr = $validation = $fieldDesc = $fieldid = '';
                switch ($key) {
                    case 'card_holder_name':
                        if($type == 'stripe' || $type == 'paypal_pro' || $type == 'online_worldpay' || in_array($type, $arm_card_holder_label_filter)) {
                            $fieldLabel = !empty($gateways_opts['card_holder_name']) ? stripslashes($gateways_opts['card_holder_name']) : esc_html__('Card Holder Name', 'ARMember');
                            $fieldDesc = !empty($gateways_opts['card_holder_name_description']) ? stripslashes($gateways_opts['card_holder_name_description']) : '';
                            $fieldAttr = 'name="' . esc_attr($type) . '[' . esc_attr($key) . ']"';
                            $fieldid = 'arm_'.$type.'_card_holder_name';
                            $card_imgs = '';
                        }
                        break;
                    case 'card_number':
                        $fieldLabel = !empty($gateways_opts['cc_label']) ? stripslashes($gateways_opts['cc_label']) : esc_html__('Credit Card Number', 'ARMember');
                        $fieldDesc = !empty($gateways_opts['cc_desc']) ? stripslashes($gateways_opts['cc_desc']) : '';
                        $field_min_length_msg=esc_html__('Please enter at least 13 digits.', 'ARMember');
                        $field_mx_length_msg=esc_html__('Maximum 19 digits allowed.', 'ARMember');
                        $fieldAttr = 'name="' . esc_attr($type) . '[' . esc_attr($key) . ']" minlength="13" data-validation-minlength-message="'. esc_attr($field_min_length_msg) .'" maxlength="19" data-validation-maxlength-message="'. esc_attr($field_mx_length_msg) .'" onkeydown="armvalidatenumber(event);"';
                        $fieldAttr .= ' data-paymentgateway="'.esc_attr($type).'" ';
                        
                        $err_msg = !empty($common_message['arm_blank_credit_card_number']) ? $common_message['arm_blank_credit_card_number'] : '';
                        $cc_error = (!empty($err_msg)) ? $err_msg : esc_html__('This field can not be left blank', 'ARMember');

                        $fieldAttr .= ' required data-validation-required-message="'.esc_attr($cc_error).'"';
                        $fieldClass = ' cardNumber';
                        
                        $err_msg = $common_message['arm_invalid_credit_card'];
                        $ec_error = (!empty($err_msg)) ? $err_msg : esc_html__('Please enter correct card details.', 'ARMember');
                        $fieldid = 'arm_'.esc_attr($type).'_card_number';
                        $arm_card_image_url = !empty($arm_card_image)?$arm_card_image : MEMBERSHIP_IMAGES_URL."/arm_default_card_image_url.png";
                        $card_imgs ='<div class="arm_module_gateway_card_images">
                            <img src="'.esc_url($arm_card_image_url).'" alt="'.esc_html__('Credit card default image','ARMember').'"/>
                        </div>';
                        break;
                    case 'cvc':
                        $fieldLabel = !empty($gateways_opts['cvv_label']) ? stripslashes($gateways_opts['cvv_label']) : esc_html__('CVV Code', 'ARMember');
                        $fieldDesc = !empty($gateways_opts['cvv_desc']) ? stripslashes($gateways_opts['cvv_desc']) : '';
                        $field_mx_length_msg=esc_html__('Maximum 4 digits allowed.', 'ARMember');
                        $fieldAttr = 'name="' . esc_attr($type) . '[' . esc_attr($key) . ']"  onkeydown="armvalidatenumber(event);" maxlength="4" data-validation-maxlength-message="'.esc_attr($field_mx_length_msg).'"';
                        $err_msg = !empty($common_message['arm_blank_cvc_number']) ? $common_message['arm_blank_cvc_number'] : '';
                        $cvc_error = (!empty($err_msg)) ? $err_msg : esc_html__('This field can not be left blank', 'ARMember');

                        $fieldAttr .= ' required data-validation-required-message="'. esc_attr($cvc_error).'"';
                        $fieldClass = ' cardCVC';
                        $fieldid = 'arm_'.esc_attr($type).'_cvc';
                        $card_imgs = '';
                        break;
                    case 'exp_month':
                        $fieldLabel = !empty($gateways_opts['em_label']) ? stripslashes($gateways_opts['em_label']) : esc_html__('Expiration Month', 'ARMember');
                        $fieldDesc = !empty($gateways_opts['em_desc']) ? stripslashes($gateways_opts['em_desc']) : '';
                        $field_mx_length_msg=esc_html__('Maximum 2 digits allowed.', 'ARMember');
                        $fieldAttr = 'name="' . esc_attr($type) . '[' . esc_attr($key) . ']" onkeydown="armvalidatenumber(event);" maxlength="2" data-validation-maxlength-message="'.esc_attr($field_mx_length_msg).'" size="2"';
                        $err_msg = !empty($common_message['arm_blank_expire_month']) ? $common_message['arm_blank_expire_month'] : '';
                        $em_error = (!empty($err_msg)) ? $err_msg : esc_html__('This field can not be left blank', 'ARMember');
                        $fieldAttr .= ' required data-validation-required-message="'.esc_attr($em_error).'"';
                        $fieldClass = ' card-expiry-month';
                        $fieldid = 'arm_'.esc_attr($type).'_exp_month';
                        
                        $card_imgs = '';
                        break;
                    case 'exp_year':
                        $fieldLabel = !empty($gateways_opts['ey_label']) ? stripslashes($gateways_opts['ey_label']) : esc_html__('Expiration Year', 'ARMember');
                        $fieldDesc = !empty($gateways_opts['ey_desc']) ? stripslashes($gateways_opts['ey_desc']) : '';
                        $field_mx_length_msg=esc_html__('Maximum 4 digits allowed.', 'ARMember');
                        $fieldAttr = 'name="' . esc_attr($type) . '[' . esc_attr($key) . ']" onkeydown="armvalidatenumber(event);" maxlength="4" data-validation-maxlength-message="'.esc_attr($field_mx_length_msg).'" size="4"';
                        $err_msg = !empty($common_message['arm_blank_expire_year']) ? $common_message['arm_blank_expire_year'] : '';
                        $ey_error = (!empty($err_msg)) ? $err_msg : esc_html__('This field can not be left blank', 'ARMember');
                        $fieldAttr .= ' required data-validation-required-message="'.$ey_error.'"';
                        $fieldClass = ' card-expiry-year';
                        $fieldid = 'arm_'.$type.'_exp_year';
                        $card_imgs = '';
                        break;
                    default:
                        break;
                }
                if($type == 'authorize_net' && $key == "card_holder_name") {
                }else{
                    $fieldid = $fieldid.'_'.esc_attr($field_random_id);
                    $ccFieldsHtml .= '<div class="arm-control-group arm_cc_field_wrapper arm-df__form-group arm-df__form-group_text">';
                    if( !in_array( $form_style ,array( 'writer_border', 'writer' ) ) )
                    {
                        $ccFieldsHtml .= '<div class="arm_form_label_wrapper arm-df__field-label arm_form_member_field_text">';
                            $ccFieldsHtml .= '<label class="arm_form_field_label_text">' . esc_html($fieldLabel) . '</label>';
                        $ccFieldsHtml .= '</div>';
                    }
                    //$ccFieldsHtml .= '<div class="arm_label_input_separator"></div>';
                    $ccFieldsHtml .= '<div class="arm-df__form-field">';
                    $ccFieldsHtml .= '<div class="arm-df__form-field-wrap arm-controls arm-df__form-field-wrap_' . $key . '">';
                    if($form_style!='writer_border')
                    {
                        $ccFieldsHtml .= '<label class="arm-df__label-text" for="'. esc_attr($fieldid).'" >' . esc_html($fieldLabel) . '</label>';
                    }
                    $ccFieldsHtml .= '<input type="text" id="'.esc_attr($fieldid).'" value="" class="field_' . esc_attr($type) . ' arm-df__form-control ' . esc_attr($fieldClass) . '" ' . $fieldAttr . '>';
                    if($form_style=='writer_border')
                    {
                        $ccFieldsHtml .= '<div class="arm-notched-outline">
                                        <div class="arm-notched-outline__leading"></div>
                                        <div class="arm-notched-outline__notch">
                                            <label class="arm-df__label-text" for="'.esc_attr($fieldid).'">' . esc_html($fieldLabel) . '</label></div>
                                            <div class="arm-notched-outline__trailing"></div>
                                        </div>';
                    }


                    $ccFieldsHtml .= $validation;
                    $ccFieldsHtml .= '</div>';
                    if (!empty($fieldDesc)) {
                        $ccFieldsHtml .= '<span>' . $fieldDesc . '</span>';
                    }
                    $ccFieldsHtml .= '</div>';
                    $ccFieldsHtml .= $card_imgs;
                    $ccFieldsHtml .= '</div>';
                }
            }
            $ccFieldsHtml = apply_filters('arm_add_cc_fields_outside', $ccFieldsHtml, $type, $gateways_opts,$form_style);
            $cc_html .= '<div class="arm-df-wrapper arm_msg_pos_' . esc_attr($errPos) . '">';
            $cc_html .= '<div class="arm_cc_fields_container arm_' . esc_attr($type) . '_fields arm-df__fields-wrapper arm_field_position_' . esc_attr($fieldPosition) . '">';
            $cc_html .= '<span class="payment-errors"></span>';
            $cc_html .= $ccFieldsHtml;
            $cc_html .= '</div>';
            $cc_html .= '<div class="armclear"></div>';
            $cc_html .= '</div>';
            return $cc_html;
        }

        function arm_get_bank_transfer_form($options = array(), $fieldPosition = 'left', $errPos = 'right', $form_id = 0,$form_settings=array()) {
            $gateways = $this->arm_get_all_payment_gateways();
            $gateways_opts = $gateways['bank_transfer'];
            $bt_fields = isset($options['fields']) ? $options['fields'] : array();
            $bt_form = '<div class="arm-df-wrapper arm_msg_pos_' . esc_attr($errPos) . '">';
            $bt_form .= '<div class="arm_bank_transfer_fields_container arm-df__fields-wrapper arm_field_position_' . $fieldPosition . '">';
            /* Transaction ID Input */
            $transaction_id_field_label = !empty($gateways_opts['transaction_id_label']) ? stripslashes($gateways_opts['transaction_id_label']) : esc_html__('Transaction ID', 'ARMember');

            $field_random_id = arm_generate_random_code();

            $formStyles = (isset($form_settings['style']) && !empty($form_settings['style'])) ? $form_settings['style'] : array();
            $arm_allow_notched_outline = 0;
            if(isset($formStyles['form_layout']) && $formStyles['form_layout'] == 'writer_border')
            {
                $arm_allow_notched_outline = 1;
            }
            if (isset($bt_fields['transaction_id']) && $bt_fields['transaction_id'] == '1') {
                /*if there is material or not */
                $field_random_id_html = 'arm_bank_transfer_transaction_id_'.$form_id.'_'.$field_random_id;
              
                $arm_field_wrap_active_class = $ffield_label_html = $ffield_label = '';
                if(!empty($arm_allow_notched_outline))
                {
                    $arm_field_wrap_active_class = (!empty($field_val)) ? ' arm-df__form-material-field-wrap' : '';
                    $ffield_label_html = '<div class="arm-notched-outline">';
                    $ffield_label_html .= '<div class="arm-notched-outline__leading"></div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__notch">';
                    
		            $ffield_label_html .= '<label class="arm-df__label-text arm_material_label" for='.esc_attr($field_random_id_html).'>' . $transaction_id_field_label . '</label>';
		    
                    $ffield_label_html .= '</div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__trailing"></div>';
                    $ffield_label_html .= '</div>';

                    $ffield_label = $ffield_label_html;
                }
                else {
                    $class_label ='';
                    if(isset($formStyles['form_layout']) && $formStyles['form_layout'] == "writer")
                    {
                        $class_label="arm-df__label-text";
                        $ffield_label = '<label class="'.esc_attr($class_label).'" for="'.esc_attr($field_random_id_html).'">' . esc_html($transaction_id_field_label) . '</label>';
                    }
                }


                $bt_form .= '<div class="arm-control-group arm_bt_field_wrapper arm-df__form-group arm-df__form-group_text">';
                $bt_form .= '<div class="arm_form_label_wrapper arm-df__field-label arm_form_member_field_text">';
                $bt_form .= '<label class="arm_form_field_label_text" for='.esc_attr($field_random_id_html).'>' . esc_html($transaction_id_field_label) . '</label>';
                $bt_form .= '</div>';
                $bt_form .= '<div class="arm_label_input_separator"></div>';
                $bt_form .= '<div class="arm-df__form-field">';
                $bt_form .= '<div class="arm-df__form-field-wrap arm-controls arm-df__form-field-wrap_transaction_id">';
                $bt_transaction_id_error_msg=esc_html__('Please enter', 'ARMember') . " " . $transaction_id_field_label;
                $bt_form .= '<input type="text" id='.esc_attr($field_random_id_html).' name="bank_transfer[transaction_id]" value="" class="field_bank_transfer arm_bank_transaction_id arm-df__form-control"  required data-validation-required-message="'.esc_attr($bt_transaction_id_error_msg).'" >';
                $bt_form .= $ffield_label;
                $bt_form .= '<span class="arm_bank_transaction_id_error front_error notify_msg" style="display:none;">' . esc_html__('Please enter', 'ARMember') . " " . esc_html($transaction_id_field_label) . '.</span>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';
            }


            if (isset($bt_fields['bank_name']) && $bt_fields['bank_name'] == '1') {
                
                $field_random_id_html = 'arm_bank_transfer_bank_name_'.$form_id.'_'.$field_random_id;
                
                $bank_name_field_label = !empty($gateways_opts['bank_name_label']) ? stripslashes($gateways_opts['bank_name_label']) : esc_html__('Bank Name', 'ARMember');
                
                $arm_field_wrap_active_class = $ffield_label_html = $ffield_label = '';
                if(!empty($arm_allow_notched_outline))
                {
                    $arm_field_wrap_active_class = (!empty($field_val)) ? ' arm-df__form-material-field-wrap' : '';
                    $ffield_label_html = '<div class="arm-notched-outline">';
                    $ffield_label_html .= '<div class="arm-notched-outline__leading"></div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__notch">';
                    
		            $ffield_label_html .= '<label class="arm-df__label-text arm_material_label" for='.esc_attr($field_random_id_html).'>' . esc_html($bank_name_field_label) . '</label>';
		    
                    $ffield_label_html .= '</div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__trailing"></div>';
                    $ffield_label_html .= '</div>';

                    $ffield_label = $ffield_label_html;
                }
                else {
                    $class_label ='';
                    if(isset($formStyles['form_layout']) && $formStyles['form_layout'] == "writer")
                    {
                        $class_label="arm-df__label-text";   
                        $ffield_label = '<label class="'.esc_attr($class_label).'" for='.esc_attr($field_random_id_html).'>' . esc_html($bank_name_field_label) . '</label>';
                    }
                }
                /* Bank Name Input */

                $bt_form .= '<div class="arm-control-group arm_bt_field_wrapper arm-df__form-group arm-df__form-group_text">';
                $bt_form .= '<div class="arm_form_label_wrapper arm-df__field-label arm_form_member_field_text">';
                $bt_form .= '<label class="arm_form_field_label_text" for='.esc_attr($field_random_id_html).'>' . esc_html($bank_name_field_label) . '</label>';
                $bt_form .= '</div>';
                $bt_form .= '<div class="arm_label_input_separator"></div>';
                $bt_form .= '<div class="arm-df__form-field">';
                $bt_form .= '<div class="arm-df__form-field-wrap arm-controls arm-df__form-field-wrap_transaction_id">';
                $bt_bank_name_error_msg=esc_html__('Please enter', 'ARMember') . " " . $bank_name_field_label;
                $bt_form .= '<input type="text" id='. esc_attr($field_random_id_html) .' name="bank_transfer[bank_name]" value="" class="field_bank_transfer arm-df__form-control" required data-validation-required-message="'.esc_attr($bt_bank_name_error_msg).'">';
                $bt_form .= $ffield_label;
                
                $bt_form .= '<span class="arm_bank_transaction_id_error front_error notify_msg" style="display:none;">' . esc_html__('Please enter', 'ARMember') . " " . esc_html($bank_name_field_label) . '.</span>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';
            }
            if (isset($bt_fields['account_name']) && $bt_fields['account_name'] == '1') {
                
                $field_random_id_html = 'arm_bank_transfer_account_name_'.$form_id.'_'.$field_random_id;
                
                $account_name_field_label = !empty($gateways_opts['account_name_label']) ? stripslashes($gateways_opts['account_name_label']) : esc_html__('Account Holder Name', 'ARMember');
                
                $arm_field_wrap_active_class = $ffield_label_html = $ffield_label = '';
                if(!empty($arm_allow_notched_outline))
                {
                    $arm_field_wrap_active_class = (!empty($field_val)) ? ' arm-df__form-material-field-wrap' : '';
                    $ffield_label_html = '<div class="arm-notched-outline">';
                    $ffield_label_html .= '<div class="arm-notched-outline__leading"></div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__notch">';
                    
		            $ffield_label_html .= '<label class="arm-df__label-text arm_material_label" for='. esc_attr($field_random_id_html) .'>' . esc_html($account_name_field_label) . '</label>';
		    
                    $ffield_label_html .= '</div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__trailing"></div>';
                    $ffield_label_html .= '</div>';

                    $ffield_label = $ffield_label_html;
                }
                else {
                    $class_label ='';
                    if(isset($formStyles['form_layout']) && $formStyles['form_layout'] == "writer")
                    {
                        $class_label="arm-df__label-text";
                        $ffield_label = '<label class="'.esc_attr($class_label).'" for='. esc_attr($field_random_id_html) .'>' . esc_html($account_name_field_label) . '</label>';
                    }
                }
                /* Account Name Input */
                $bt_form .= '<div class="arm-control-group arm_bt_field_wrapper arm-df__form-group arm-df__form-group_text">';
                $bt_form .= '<div class="arm_form_label_wrapper arm-df__field-label arm_form_member_field_text">';
                $bt_form .= '<label class="arm_form_field_label_text" for='.esc_attr($field_random_id_html).'>' . esc_html($account_name_field_label) . '</label>';
                $bt_form .= '</div>';
                $bt_form .= '<div class="arm_label_input_separator"></div>';
                $bt_form .= '<div class="arm-df__form-field">';
                $bt_form .= '<div class="arm-df__form-field-wrap arm-controls arm-df__form-field-wrap_transaction_id">';
                $bt_account_name_error_msg=esc_html__('Please enter', 'ARMember') . " " . $account_name_field_label;
                $bt_form .= '<input type="text" id='. esc_attr($field_random_id_html).' name="bank_transfer[account_name]" value="" class="field_bank_transfer arm-df__form-control" required data-validation-required-message="'. esc_attr($bt_account_name_error_msg).'">';
                $bt_form .= $ffield_label;
                
                $bt_form .= '<span class="arm_bank_transaction_id_error front_error notify_msg" style="display:none;">' . esc_html__('Please enter', 'ARMember') . " " . esc_html($account_name_field_label) . '.</span>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';
            }
            if (isset($bt_fields['additional_info']) && $bt_fields['additional_info'] == '1') {

                $additional_info_field_label = !empty($gateways_opts['additional_info_label']) ? stripslashes($gateways_opts['additional_info_label']) : esc_html__('Additional Note', 'ARMember');
                $field_random_id_html = 'arm_bank_transfer_additional_info_'.$form_id.'_'.$field_random_id;
                $arm_field_wrap_active_class = $ffield_label_html = $ffield_label = '';
                if(!empty($arm_allow_notched_outline))
                {
                    $arm_field_wrap_active_class = (!empty($field_val)) ? ' arm-df__form-material-field-wrap' : '';
                    $ffield_label_html = '<div class="arm-notched-outline">';
                    $ffield_label_html .= '<div class="arm-notched-outline__leading"></div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__notch">';
                    $ffield_label_html .= '<label class="arm-df__label-text arm_material_label '. esc_attr($arm_field_wrap_active_class).'" for='.esc_attr($field_random_id_html).'>' . esc_html($additional_info_field_label) . '</label>';
		    
                    $ffield_label_html .= '</div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__trailing"></div>';
                    $ffield_label_html .= '</div>';

                    $ffield_label = $ffield_label_html;
                }
                else {
                    
                    if(isset($formStyles['form_layout']) && $formStyles['form_layout'] == "writer")
                    {
                        
                        $ffield_label = '<label class="arm-df__label-text '.esc_attr($arm_field_wrap_active_class).'" for='.esc_attr($field_random_id_html).'>' . esc_html($additional_info_field_label) . '</label>';
                    }
                }
                /* Additional Note Input */

                $bt_form .= '<div class="arm-control-group arm_bt_field_wrapper arm-df__form-group arm-df__form-group_textarea">';
                $bt_form .= '<div class="arm_form_label_wrapper arm-df__field-label arm_form_member_field_text">';
                $bt_form .= '<label class="arm_form_field_label_text" for='.esc_attr($field_random_id_html).'>' . esc_html($additional_info_field_label) . '</label>';
                $bt_form .= '</div>';
                $bt_form .= '<div class="arm_label_input_separator"></div>';
                $bt_form .= '<div class="arm-df__form-field">';
                
                $bt_form .= '<div class="arm-df__form-field-wrap arm-df__form-field-wrap_textarea arm-controls arm-df__form-field-wrap_transaction_id">';
                $bt_additional_info_error_msg=esc_html__('Please enter', 'ARMember') . " " . $additional_info_field_label;
                $bt_form .= '<textarea id='.esc_attr($field_random_id_html).' class="arm_textarea arm-df__form-control field_bank_transfer" name="bank_transfer[additional_info]" required data-validation-required-message="'.esc_attr($bt_additional_info_error_msg).'" rows="3" cols="10"></textarea>';
                $bt_form .= $ffield_label;
                
                $bt_form .= '<span class="arm_bank_transaction_id_error front_error notify_msg" style="display:none;">' . esc_html__('Please enter', 'ARMember') . " " . esc_html($additional_info_field_label) . '.</span>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';
            }
            if(isset($bt_fields['transfer_mode']) && $bt_fields['transfer_mode'] == '1') {
                
                /* Payment transfer mode Input */
                $field_random_id_html = 'arm_bank_transfer_transfer_mode_'.$form_id.'_'.$field_random_id;

                $transfer_mode = $this->arm_get_bank_transfer_mode_options();
                //$transfer_mode_li = '<option class="armMDOption armSelectOption arm_payment_transfer_mode_option_'.$form_id.'" role="option" aria-selected="false" value="">' . esc_html__('Select Payment Mode', 'ARMember') . '</option>';
                
                $transfer_mode_option_val_label = $transfer_mode_option_val_selected = esc_html__('Select Payment Mode', 'ARMember');
                
                $transfer_mode_li = '<li class="arm__dc--item arm_payment_transfer_mode_option_'.esc_attr($form_id).'" data-label="' . esc_attr($transfer_mode_option_val_label) . '" data-value="">' . esc_html($transfer_mode_option_val_label) . '</li>';

                $transfer_mode_option_arr = !empty($gateways_opts['fields']['transfer_mode_option']) ? $gateways_opts['fields']['transfer_mode_option'] : array();
                $drpdown_cntr = 0;
                $default_pg = $arm_field_wrap_active_class = $ffield_label = "";
                foreach ($transfer_mode as $key => $value) {
                    if(in_array($key, $transfer_mode_option_arr)) {
                        $transfer_mode_option_val = !empty($gateways_opts['fields']['transfer_mode_option_label'][$key]) ? stripslashes_deep($gateways_opts['fields']['transfer_mode_option_label'][$key]) : $value;
                        if($drpdown_cntr==0)
                        {
                            $default_pg = $transfer_mode_option_val;
                        }
                        //$transfer_mode_li .= '<option class="arm_payment_transfer_mode_option_'.$form_id.'" role="option" selected="false" value="' . esc_attr($key) . '">' . $transfer_mode_option_val . '</option>';
                        $transfer_mode_li .= '<li class="arm__dc--item arm_payment_transfer_mode_option_'.esc_attr($form_id).'" data-label="' . esc_html($transfer_mode_option_val) . '" data-value="' . esc_attr($transfer_mode_option_val) . '">' . esc_html($transfer_mode_option_val) . '</li>';
                    }
                }

                $ng_change_func = "";
                $name = "bank_transfer[transfer_mode]";
                $payment_mode_field_label = !empty($gateways_opts['transfer_mode_label']) ? stripslashes($gateways_opts['transfer_mode_label']) : esc_html__('Payment Mode', 'ARMember');
                $arm_bk_active_class ="";
                if( isset($formStyles['form_layout']) && ( $formStyles['form_layout'] == "writer" || $formStyles['form_layout'] == "writer_border" ) )
                {
                    $arm_bk_active_class = 'active';
                }
                $bt_transfer_mode_error_msg=esc_html__('Please select', 'ARMember') . " " . $payment_mode_field_label;
                $field_attr = 'name="' . esc_attr($name).'" class="arm-selectpicker-input-control arm-df__form-control" ';
                $field_attr .= ' aria-label="'.esc_attr($payment_mode_field_label).'"';
                $required = ' required="" data-validation-required-message="'.esc_attr($bt_transfer_mode_error_msg).'" ';
                $writter_class = (!empty($form) && isset($formStyles['rtl']) && $formStyles['rtl'] == '1') ? 'armSelectOptionRTL' : 'armSelectOptionLTR';  
                if(!empty($arm_allow_notched_outline))
                {   
                    $arm_bk_active_class ="";
                    $arm_field_wrap_active_class = (!empty($default_pg)) ? ' arm-df__form-material-field-wrap' : '';
                    if($arm_field_wrap_active_class != '')
                    {
                        $arm_bk_active_class = 'active';
                    }
                    $ffield_label_html = '<div class="arm-notched-outline">';
                    $ffield_label_html .= '<div class="arm-notched-outline__leading"></div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__notch">';
                    
		            $ffield_label_html .= '<label class="arm-df__label-text '.esc_attr($arm_bk_active_class).'" for='.esc_attr($field_random_id_html).'>' . esc_html($payment_mode_field_label) . '</label>';
		    
                    $ffield_label_html .= '</div>';
                    $ffield_label_html .= '<div class="arm-notched-outline__trailing"></div>';
                    $ffield_label_html .= '</div>';

                    $ffield_label = $ffield_label_html;
                }
                else {
                    $class_label ='';
                    if(isset($formStyles['form_layout']) && $formStyles['form_layout'] == "writer")
                    {
                        if(!empty($default_pg))
                        {
                            $arm_bk_active_class = 'active';
                        }
                        $class_label="arm-df__label-text ".esc_attr($arm_bk_active_class);
                        $ffield_label = '<label class="'. esc_attr($class_label).'" for='.esc_attr($field_random_id_html).'>' . esc_html($payment_mode_field_label) . '</label>';
                    }
                }


                $bt_form .= '<div class="arm-control-group arm_bt_field_wrapper arm-df__form-group arm-df__form-group_select">';
                $bt_form .= '<div class="arm_form_label_wrapper arm-df__field-label arm_form_member_field_text">';
                $bt_form .= '<label class="arm_form_field_label_text" for='.esc_attr($field_random_id_html).'>' . esc_html($payment_mode_field_label) . '</label>';
                $bt_form .= '</div>';
                $bt_form .= '<div class="arm_label_input_separator"></div>';
                $bt_form .= '<div class="arm-df__form-field">';
                $bt_form .= '<div class="arm-df__form-field-wrap arm-controls arm-df__form-field-wrap_select '.esc_attr($arm_field_wrap_active_class).'">';

                $bt_form .= '<input type="text" id="' . esc_attr($field_random_id_html) . '" ' . $field_attr . esc_attr($ng_change_func) .' value="'.esc_attr($default_pg).'" '.$required.'/>';
                $bt_form .=$ffield_label;
                $bt_form .= '<dl class="arm-df__dropdown-control column_level_dd arm_member_form_dropdown ">';
                $bt_form .= '<dt class="arm__dc--head"><span class="arm__dc--head__title">'.esc_html($default_pg).'</span><input type="text" style="display:none;" value="" class="arm-df__dc--head__autocomplete arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';
                $bt_form .= '<dd class="arm__dc--items-wrap"><ul class="arm__dc--items" data-id="' . esc_attr($field_random_id_html) . '" style="display:none;">';
                $bt_form .= $transfer_mode_li;
                $bt_form .= '';
                $bt_form .= '</ul></dd>';
                $bt_form .= '</dl>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';
                $bt_form .= '</div>';

            }

            $bt_form .= '</div>';
            $bt_form .= '<div class="armclear"></div>';
            $bt_form .= '</div>';
            return $bt_form;
        }

        /**
         * Get Currency Name/Label From Currency Code For Paypal
         */
        function arm_paypal_currency_symbol() {
            
            /* 26 currency */
            $currency_symbol = array(
                
                'AUD' => '$',
                'BRL' => 'R$',
                'CAD' => '$',
                'CZK' => '&#75;&#269;',
                'DKK' => '&nbsp;&#107;&#114;',
                'EUR' => '&#8364;',
                'HKD' => '&#36;',
                'HUF' => '&#70;&#116;',
                'ILS' => '&#8362;',
                'JPY' => '&#165;',
                'MYR' => '&#82;&#77;',
                'MXN' => '&#36;',
                'TWD' => '&#36;',
                'NZD' => '&#36;',
                'NOK' => '&nbsp;&#107;&#114;',
                'PHP' => '&#8369;',
                'PLN' => '&#122;&#322;',
                'GBP' => '&#163;',
                'RUB' => '&#1088;&#1091;',
                'SGD' => '&#36;',
                'SEK' => '&nbsp;&#107;&#114;',
                'CHF' => '&#67;&#72;&#70;',
                'THB' => '&#3647;',
                'USD' => '$',
                'TRY' => '&#89;&#84;&#76;',
                'INR' => '&#8377;',
            );
            return $currency_symbol;
        }

        /**
         * Get Currency Name/Label From Currency Code For Bank Transfer - Total 168
         */
        function arm_bank_transfer_currency_symbol()
        {
            $currency_symbol = array(
                'AED' => '&#x62f;&#x2e;&#x625;',
                'AFN' => '&#1547;',
                'ALL' => 'L',
                'AMD' => '&#1423;',
                'ANG' => '$',
                'AOA' => 'Kz',
                'ARS' => '&#36;',
                'AUD' => '$',
                'AWG' => '&#x192;',
                'AZN' => 'maH',
                'BAM' => '&#x4b;&#x4d',
                'BBD' => 'Bds&#36;',
                'BDT' => '&#2547;',
                'BGN' => '&#1083;&#1074;.',
                'BHD' => '.&#x62f;.&#x628;',
                'BIF' => 'FBu',
                'BMD' => 'BD$',
                'BND' => 'B$',
                'BOB' => 'Bs.',
                'BRL' => 'R$',
                'BSD' => '&#x0024;',
                'BTC' => '&#3647;',
                'BTN' => 'Nu.',
                'BWP' => 'P',
                'BYN' => 'Br',
                'BYR' => 'Br',
                'BZD' => 'BZ$',
                'CAD' => '$',
                'CDF' => 'FC',
                'CHF' => '&#67;&#72;&#70;',
                'CLP' => '&#36;',
                'CNY' => '&#165;',
                'COP' => '&#36;',
                'CRC' => '&#8353;',
                'CUC' => '&#36;',
                'CUP' => '&#36;',
                'CVE' => 'Esc',
                'CZK' => '&#75;&#269;',
                'DJF' => 'Fdj',
                'DKK' => '&nbsp;&#107;&#114;',
                'DOP' => 'RD$',
                'DZD' => 'DA',
                'EGP' => 'E&#163;',
                'ERN' => 'Nfk',
                'ETB' => 'Br',
                'EUR' => '&#8364;',
                'FJD' => 'FJ$',
                'FKP' => '&#xa3;',
                'GBP' => '&#163;',
                'GEL' => '&#x20be;',
                'GGP' => '&pound;',
                'GHS' => '&#x20b5;',
                'GIP' => '&#xa3;',
                'GMD' => 'D',
                'GNF' => 'FG',
                'GTQ' => 'Q',
                'GYD' => '&#x24;',
                'HKD' => '&#36;',
                'HNL' => 'L',
                'HRK' => 'kn',
                'HTG' => 'G',
                'HUF' => '&#70;&#116;',
                'IDR' => 'Rp',
                'ILS' => '&#8362;',
                'IMP' => '&pound;',
                'INR' => '&#8377;',
                'IQD' => '&#x639;.&#x62f;',
                'IRR' => '&#xfdfc;',
                'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
                'ISK' => '&nbsp;&#x6b;&#x72;',
                'JEP' => '&pound;',
                'JMD' => 'J$',
                'JOD' => '&#x62f;.&#x627;',
                'JPY' => '&#165;',
                'KES' => 'KSh',
                'KGS' => '&#x43b;&#x432;',
                'KHR' => '&#x17db;',
                'KMF' => 'CF',
                'KPW' => '&#x20a9;',
                'KRW' => '&#8361;',
                'KWD' => '&#x62f;.&#x643;',
                'KYD' => '$',
                'KZT' => '&#8376;',
                'LAK' => '&#8365;',
                'LBP' => 'L&#163;',
                'LKR' => '&#8360;',
                'LRD' => 'L$',
                'LSL' => 'L',
                'LYD' => '&#x644;.&#x62f;',
                'MAD' => '&#x2e;&#x62f;&#x2e;&#x645;',
                'MDL' => 'MDL',
                'MGA' => 'Ar',
                'MKD' => '&#x434;&#x435;&#x43d;',
                'MMK' => 'K',
                'MNT' => '&#x20ae;',
                'MOP' => 'P',
                'MRO' => 'UM',
                'MRU' => 'UM',
                'MUR' => '&#8360;',
                'MVR' => 'Rf',
                'MWK' => 'MK',
                'MXN' => '&#36;',
                'MYR' => '&#82;&#77;',
                'MZN' => '&#x4d;&#x54;',
                'NAD' => '$',
                'NGN' => '&#x20a6;',
                'NIO' => 'C$',
                'NOK' => '&nbsp;&#107;&#114;',
                'NPR' => '&#8360;',
                'NZD' => '&#36;',
                'OMR' => '&#x631;.&#x639;.',
                'PAB' => 'B/.',
                'PEN' => 'S/.',
                'PGK' => 'K',
                'PHP' => '&#8369;',
                'PKR' => '&#8360;',
                'PLN' => '&#122;&#322;',
                'PRB' => '&#x440;.',
                'PYG' => 'Gs',
                'QAR' => '&#65020;',
                'RMB' => '&yen;',
                'RON' => 'L',
                'RSD' => '&#x414;&#x438;&#x43d;&#x2e;',
                'RUB' => '&#1088;&#1091;',
                'RWF' => 'RF',
                'SAR' => '&#65020;',
                'SBD' => 'SI$',
                'SCR' => '&#8360;',
                'SDG' => '&pound;SD',
                'SEK' => '&nbsp;&#107;&#114;',
                'SGD' => '&#36;',
                'SGF' => 'S$',
                'SHP' => '&#xa3;',
                'SYP' => 'S&#163;',
                'TJS' => 'TSh',
                'SLL' => 'Le',
                'SOS' => 'S',
                'SRD' => '$',
                'SSP' => '&pound;',
                'STD' => 'Db',
                'STN' => 'Db',
                'SVC' => '$',
                'SZL' => 'SZL',
                'THB' => '&#3647;',
                'TMT' => 'm',
                'TND' => '&#x62f;.&#x62a;',
                'TOP' => 'T$',
                'TRY' => '&#89;&#84;&#76;',
                'TTD' => 'TT$',
                'TWD' => '&#36;',
                'TZS' => 'x',
                'UAH' => '&#8372;',
                'UGX' => 'USh',
                'USD' => '$',
                'UYU' => '$U',
                'UZS' => '&#x43b;&#x432;',
                'VEF' => 'Bs F',
                'VES' => 'Bs.S',
                'VND' => '&#8363;',
                'VUV' => 'VT',
                'WST' => 'T',
                'XAF' => 'FCFA',
                'XCD' => 'EC$',
                'XOF' => 'CFA',
                'XPF' => 'F',
                'YER' => '&#65020;',
                'ZAR' => 'R',
                'ZMW' => 'ZK',
            );
            return $currency_symbol;
        }

        /**
         * Get Currency Name/Label From Currency Code For Stripe
         */
        function arm_stripe_currency_symbol() {
            $currency_symbol = array(
                'AED' => '&#x62f;&#x2e;&#x625;',
                'AFN' => '&#1547;',
                'ALL' => 'L',
                'AMD' => '&#1423;',
                'ANG' => '$',
                'AOA' => 'Kz',
                'ARS' => '&#36;',
                'AUD' => '$',
                'AWG' => '&#x192;',
                'AZN' => 'maH',
                'BAM' => '&#x4b;&#x4d',
                'BBD' => 'Bds&#36;',
                'BDT' => '&#2547;',
                'BGN' => '&#1083;&#1074;.',
                'BIF' => 'FBu',
                'BMD' => 'BD$',
                'BND' => 'B$',
                'BOB' => 'Bs.',
                'BRL' => 'R$',
                'BSD' => '&#x0024;',
                'BWP' => 'P',
                'BZD' => 'BZ$',
                'CAD' => '$',
                'CDF' => 'FC',
                'CHF' => '&#67;&#72;&#70;',
                'CLP' => '&#36;',
                'CNY' => '&#165;',
                'COP' => '&#36;',
                'CRC' => '&#8353;',
                'CVE' => 'Esc',
                'CZK' => '&#75;&#269;',
                'DJF' => 'Fdj',
                'DKK' => '&nbsp;&#107;&#114;',
                'DOP' => 'RD$',
                'DZD' => 'DA',
                'EGP' => 'E&#163;',
                'ETB' => 'Br',
                'EUR' => '&#8364;',
                'FJD' => 'FJ$',
                'FKP' => '&#xa3;',
                'GBP' => '&#163;',
                'GEL' => '&#x20be;',
                'GHS' => '&#x20b5;',
                'GIP' => '&#xa3;',
                'GMD' => 'D',
                'GNF' => 'FG',
                'GTQ' => 'Q',
                'GYD' => '&#x24;',
                'HKD' => '&#36;',
                'HNL' => 'L',
                'HRK' => 'kn',
                'HTG' => 'G',
                'HUF' => '&#70;&#116;',
                'IDR' => 'Rp',
                'ILS' => '&#8362;',
                'INR' => '&#8377;',
                'ISK' => '&nbsp;&#x6b;&#x72;',
                'JMD' => 'J$',
                'JPY' => '&#165;',
                'KES' => 'KSh',
                'KGS' => '&#x43b;&#x432;',
                'KHR' => '&#x17db;',
                'KMF' => 'CF',
                'KRW' => '&#8361;',
                'KYD' => '$',
                'KZT' => '&#8376;',
                'LAK' => '&#8365;',
                'LBP' => 'L&#163;',
                'LKR' => '&#8360;',
                'LRD' => 'L$',
                'LSL' => 'L',
                'MAD' => '&#x2e;&#x62f;&#x2e;&#x645;',
                'MDL' => 'MDL',
                'MGA' => 'Ar',
                'MKD' => '&#x434;&#x435;&#x43d;',
                'MMK' => 'K',
                'MNT' => '&#x20ae;',
                'MOP' => 'P',
                'MRO' => 'UM',
                'MUR' => '&#8360;',
                'MVR' => 'Rf',
                'MWK' => 'MK',
                'MXN' => '&#36;',
                'MYR' => '&#82;&#77;',
                'MZN' => '&#x4d;&#x54;',
                'NAD' => '$',
                'NGN' => '&#x20a6;',
                'NIO' => 'C$',
                'NOK' => '&nbsp;&#107;&#114;',
                'NPR' => '&#8360;',
                'NZD' => '&#36;',
                'PAB' => 'B/.',
                'PEN' => 'S/.',
                'PGK' => 'K',
                'PHP' => '&#8369;',
                'PKR' => '&#8360;',
                'PLN' => '&#122;&#322;',
                'PYG' => 'Gs',
                'QAR' => '&#65020;',
                'RON' => 'L',
                'RSD' => '&#x414;&#x438;&#x43d;&#x2e;',
                'RUB' => '&#1088;&#1091;',
                'RWF' => 'RF',
                'SAR' => '&#65020;',
                'SBD' => 'SI$',
                'SCR' => '&#8360;',
                'SEK' => '&nbsp;&#107;&#114;',
                'SGD' => '&#36;',
                'SHP' => '&#xa3;',
                'TJS' => 'TSh',
                'SLL' => 'Le',
                'SOS' => 'S',
                'SRD' => '$',
                'STD' => 'Db',
                'SVC' => '$',
                'SZL' => 'SZL',
                'THB' => '&#3647;',
                'TOP' => 'T$',
                'TRY' => '&#89;&#84;&#76;',
                'TTD' => 'TT$',
                'TWD' => '&#36;',
                'TZS' => 'x',
                'UAH' => '&#8372;',
                'UGX' => 'USh',
                'USD' => '$',
                'UYU' => '$U',
                'UZS' => '&#x43b;&#x432;',
                'VND' => '&#8363;',
                'VUV' => 'VT',
                'WST' => 'T',
                'XAF' => 'FCFA',
                'XCD' => 'EC$',
                'XOF' => 'CFA',
                'XPF' => 'F',
                'YER' => '&#65020;',
                'ZAR' => 'R',
                'ZMW' => 'ZK'               
            );
            return $currency_symbol;
        }
        
        
        function arm_stripe_zero_decimal_currency_array(){
            return apply_filters('arm_stripe_zero_demial_currency', array('BIF', 'DJF','JPY', 'KRW','PYG', 'VND', 'XAF', 'XPF', 'CLP', 'GNF', 'KMF', 'MGA', 'RWF', 'VUV', 'XOF', 'UGX' ) );
        }

        /**
         * Get Currency Name/Label From Currency Code For Authorize.Net
         */
        function arm_authorize_net_currency_symbol() {
            $currency_symbol = array(
                'USD' => '$',
                'GBP' => '&#163;',
                'EUR' => '&#8364;',
            );
            return $currency_symbol;
        }

        /**
         * Get Currency Name/Label From Currency Code For 2Checkout
         */
        function arm_2checkout_currency_symbol() {
            $currency_symbol = array(
                /* 88 currency */
                
                'AFN' => '&#1547;',
                'ALL' => 'L',
                'ARS' => '&#36;',
                'AZN' => 'maH',
                'DZD' => 'DA',
                'AUD' => '$',
                'BSD' => '&#x0024;',
                'BDT' => '&#2547;',
                'BBD' => 'Bds&#36;',
                'BZD' => 'BZ$',
                
                'BMD' => 'BD$',
                'BOB' => 'Bs.',
                'BWP' => 'P',
                'BRL' => 'R$',
                'BND' => 'B$',
                'BGN' => '&#1083;&#1074;.',
                'GBP' => '&#163;',
                'USD' => '$',
                'CAD' => '$',
                'CZK' => '&#75;&#269;',
                
                'CLP' => '&#36;',
                'CNY' => '&#165;',
                'COP' => '&#36;',
                'CRC' => '&#8353;',
                'HRK' => 'kn',
                'DKK' => '&nbsp;&#107;&#114;',
                'DOP' => 'RD$',
                'XCD' => 'EC$',
                'EGP' => 'E&#163;',
                'EUR' => '&#8364;',
                
                'FJD' => 'FJ$',
                'GTQ' => 'Q',
                'HKD' => '&#36;',
                'HNL' => 'L',
                'HUF' => '&#70;&#116;',
                'ILS' => '&#8362;',
                'INR' => '&#8377;',
                'IDR' => 'Rp',
                'JMD' => 'J$',
                'JPY' => '&#165;',
                
                'KZT' => '&#8376;',
                'KES' => 'KSh',
                'LAK' => '&#8365;',
                'MMK' => 'K',
                'LBP' => 'L&#163;',
                'LRD' => 'L$',
                'MOP' => 'P',
                'MYR' => '&#82;&#77;',
                'MVR' => 'Rf',
                'MRO' => 'UM',
                
                'MUR' => '&#8360;',
                'MXN' => '&#36;',
                'MAD' => '&#x2e;&#x62f;&#x2e;&#x645;',
                'NPR' => '&#8360;',
                'TWD' => '&#36;',
                'NZD' => '&#36;',
                'NIO' => 'C$',
                'NOK' => '&nbsp;&#107;&#114;',
                'PKR' => '&#8360;',
                'PGK' => 'K',
                
                'PEN' => 'S/.',
                'PHP' => '&#8369;',
                'PLN' => '&#122;&#322;',
                'QAR' => '&#65020;',
                'RON' => 'L',
                'WST' => 'T',
                'SAR' => '&#65020;',
                'SCR' => '&#8360;',
                'SGF' => 'S$',
                'SBD' => 'SI$',
                
                'ZAR' => 'R',
                'KRW' => '&#8361;',
                'LKR' => '&#8360;',
                'SEK' => '&nbsp;&#107;&#114;',
                'CHF' => '&#67;&#72;&#70;',
                'SYP' => 'S&#163;',
                'TOP' => 'T$',
                'TTD' => 'TT$',
                'THB' => '&#3647;',
                'UAH' => '&#8372;',
                
                'AED' => '&#x62f;&#x2e;&#x625;',
                'VUV' => 'VT',
                'VND' => '&#8363;',
                'XOF' => 'CFA',
                'YER' => '&#65020;',
                'RUB' => '&#1088;&#1091;',
                'TRY' => '&#89;&#84;&#76;',
                
                'SGD' => '&#36;',
                
            );
            return $currency_symbol;
        }

        function arm_check_currency_status($arm_currency = 'USD') {
            global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_membership_setup, $arm_capabilities_global;
            $response = array('type' => 'error', 'msg' => esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
            $currency = (isset($_REQUEST['arm_currency']) && !empty($_REQUEST['arm_currency'])) ? sanitize_text_field($_REQUEST['arm_currency']) : $arm_currency;
            $message = '';
            $notAllow = $notAllowSetups = array();
            if (!empty($currency)) {
                if (!array_key_exists($currency, $this->currency['paypal'])) {
                    $notAllow[] = 'paypal';
                }
                if (!array_key_exists($currency, $this->currency['stripe'])) {
                    $notAllow[] = 'stripe';
                }
                if (!array_key_exists($currency, $this->currency['authorize_net'])) {
                    $notAllow[] = 'authorize_net';
                }
                if (!array_key_exists($currency, $this->currency['2checkout'])) {
                    $notAllow[] = '2checkout';
                }
                $notAllow = apply_filters('arm_currency_support', $notAllow, $currency);
                if (!empty($notAllow)) {
                    $message = esc_html__('This currency is not supported by', 'ARMember');
                    $message .= ' ' . implode(', ', $notAllow) . ' ';
                    $message .= esc_html__('payment gateway(s). If you will save this settings, than those payment gateway(s) will be disable.', 'ARMember');
                    $setups = $wpdb->get_results("SELECT `arm_setup_name`, `arm_setup_modules` FROM `" . $ARMember->tbl_arm_membership_setup . "` ORDER BY `arm_setup_id` DESC", ARRAY_A); //phpcs:ignore  --Reason Query without where clauses
                    if (!empty($setups)) {
                        foreach ($setups as $setup) {
                            $setupModules = maybe_unserialize($setup['arm_setup_modules']);
                            if (isset($setupModules['modules']['gateways']) && !empty($setupModules['modules']['gateways'])) {
                                $diffPG = array_diff($setupModules['modules']['gateways'], $notAllow);
                                if (empty($diffPG) || count($diffPG) < 1) {
                                    $notAllowSetups[] = $setup['arm_setup_name'];
                                }
                            }
                        }
                        $notAllowSetups = $ARMember->arm_array_unique($notAllowSetups);
                    }
                    if (!empty($notAllowSetups)) {
                        $message .= ' ' . esc_html__('As well as following setup(s) will not work properly', 'ARMember');
                        $message .= ': ' . implode(', ', $notAllowSetups) . '.';
                    }
                }
                $response = array('type' => 'success', 'msg' => $message);
            }
            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'arm_check_currency_status') {
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_payment_gateways'], '1',1);
                echo arm_pattern_json_encode($response);
                exit;
            }
            return $message;
        }

        function arm_check_currency_status_for_gateways($arm_currency = 'USD') {
            global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_membership_setup, $arm_capabilities_global;
            $response = array('type' => 'error', 'msg' => esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
            $currency = (isset($_REQUEST['arm_currency']) && !empty($_REQUEST['arm_currency'])) ? sanitize_text_field($_REQUEST['arm_currency']) : $arm_currency;
            $message = '';
            $notAllow = $notAllowSetups = array();

            if (!empty($currency)) {
                if (!array_key_exists($currency, $this->currency['paypal'])) {
                    $notAllow[] = 'paypal';
                }
                if (!array_key_exists($currency, $this->currency['stripe'])) {
                    $notAllow[] = 'stripe';
                }
                if (!array_key_exists($currency, $this->currency['authorize_net'])) {
                    $notAllow[] = 'authorize_net';
                }
                if (!array_key_exists($currency, $this->currency['2checkout'])) {
                    $notAllow[] = '2checkout';
                }
                $notAllow = apply_filters('arm_currency_support', $notAllow, $currency);
                if (!empty($notAllow)) {
                    $message = esc_html__('This currency is not supported by', 'ARMember');
                    $message .= ' ' . implode(', ', $notAllow) . ' ';
                    $message .= esc_html__('payment gateway(s). If you will save this settings, than those payment gateway(s) will be disable.', 'ARMember');
                    $setups = $wpdb->get_results("SELECT `arm_setup_name`, `arm_setup_modules` FROM `" . $ARMember->tbl_arm_membership_setup . "` ORDER BY `arm_setup_id` DESC", ARRAY_A); //phpcs:ignore --Reason Query without Where clauses
                    if (!empty($setups)) {
                        foreach ($setups as $setup) {
                            $setupModules = maybe_unserialize($setup['arm_setup_modules']);
                            if (isset($setupModules['modules']['gateways']) && !empty($setupModules['modules']['gateways'])) {
                                $diffPG = array_diff($setupModules['modules']['gateways'], $notAllow);
                                if (empty($diffPG) || count($diffPG) <= 1) {
                                    $notAllowSetups[] = $setup['arm_setup_name'];
                                }
                            }
                        }
                        $notAllowSetups = $ARMember->arm_array_unique($notAllowSetups);
                    }
                    if (!empty($notAllowSetups)) {
                        $message .= ' ' . esc_html__('As well as following setup(s) will not work properly', 'ARMember');
                        $message .= ': ' . implode(', ', $notAllowSetups) . '.';
                    }
                }
                $response = array('type' => 'success', 'msg' => $message);
            }
            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'arm_check_currency_status') {
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_payment_gateways'], '1',1);//phpcs:ignore --Reason:Verifying nonce
                echo json_encode($response);
                exit;
            }
            return $notAllow;
        }

        function arm_paypal_language() {
            $currency_symbol = array(
                'ar_EG' => esc_html__('Arab/Egypt', 'ARMember'),
                'da_DK' => esc_html__('Danish/Denmark', 'ARMember'),
                'nl_NL' => esc_html__('Dutch/Netherlands', 'ARMember'),
                'zh_XC' => esc_html__('Chinese', 'ARMember'),
                'zh_CN' => esc_html__('Chinese/China', 'ARMember'),
                'zh_HK' => esc_html__('Chino/Hong Kong', 'ARMember'),
                'zh_TW' => esc_html__('Chinese/Taiwan', 'ARMember'),
                'en_AU' => esc_html__('English/Australia', 'ARMember'),
                'en_GB' => esc_html__('English/United Kingdom', 'ARMember'),
                'en_US' => esc_html__('English/United States', 'ARMember'),
                'es_XC' => esc_html__('Eskimo Aleut/Anguilla', 'ARMember'),
                'fr_CA' => esc_html__('Francais/Canada', 'ARMember'),
                'fr_XC' => esc_html__('French/Angola', 'ARMember'),
                'fr_FR' => esc_html__('French/France', 'ARMember'),
                'de_DE' => esc_html__('German/Germany', 'ARMember'),
                'he_IL' => esc_html__('Hebrew/Israel', 'ARMember'),
                'id_ID' => esc_html__('Indonesian/Indonesia', 'ARMember'),
                'it_IT' => esc_html__('Italian/Italy', 'ARMember'),
                'ja_JP' => esc_html__('Japanese/Japan', 'ARMember'),
                'ko_KR' => esc_html__('Korean/South Korea', 'ARMember'),
                'no_NO' => esc_html__('Norwegian/Norway', 'ARMember'),
                'pl_PL' => esc_html__('Polish/Poland', 'ARMember'),
                'pt_BR' => esc_html__('Portugues/Brasil', 'ARMember'),
                'pt_PT' => esc_html__('Portuguese/Portugal', 'ARMember'),
                'ru_RU' => esc_html__('Rusia/Rusia', 'ARMember'),
                'sk_SK' => esc_html__('Slovakia/Slovakia', 'ARMember'),
                'es_ES' => esc_html__('Spanish/Spain', 'ARMember'),
                'sv_SE' => esc_html__('Swedish/Sweden', 'ARMember'),
                'th_TH' => esc_html__('Thai/Thailand', 'ARMember'),
                
                
                
            );
            return $currency_symbol;
        }
        
        function arm_2checkout_language() {
            $currency_symbol = array(
                'zh' => esc_html__('Chinese', 'ARMember'),
                'da' => esc_html__('Danish', 'ARMember'),
                'nl' => esc_html__('Dutch', 'ARMember'),
                'en' => esc_html__('English', 'ARMember'),
                'fr' => esc_html__('French', 'ARMember'),
                'gr' => esc_html__('German', 'ARMember'),
                'el' => esc_html__('Greek', 'ARMember'),
                'it' => esc_html__('Italian', 'ARMember'),
                'jp' => esc_html__('Japanese', 'ARMember'),
                'no' => esc_html__('Norwegian', 'ARMember'),
                'pt' => esc_html__('Portuguese', 'ARMember'),
                'sl' => esc_html__('Slovenian', 'ARMember'),
                'es_ib' => esc_html__('Spanish-European', 'ARMember'),
                'es_la' => esc_html__('Spanish-Latin American', 'ARMember'),
                'sv' => esc_html__('Swedish', 'ARMember'),
                
            );
            return $currency_symbol;
        }
        
        function arm_amount_set_separator($arm_currency = 'USD', $arm_amount = '0', $is_coupon_amount = false, $get_currency_wise_seperator = false) {
            global $arm_global_settings;

            $general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
	    $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;

            $arm_currency = strtoupper($arm_currency);
            $currency_separators = $this->get_other_seperator_currencies();
            
            $separator = $this->get_currency_separators_standard();
            $arm_amount = empty($arm_amount) ? '0' : $arm_amount;
            $arm_main_amount = (float)str_replace(',', '', $arm_amount);

            if($get_currency_wise_seperator == true && !empty($currency_separators[$arm_currency])) {
                $separator = array();
                $separator[$arm_currency] = $currency_separators[$arm_currency];
                //$arm_main_amount = (float)str_replace($separator[$arm_currency]['thousand'], '', $arm_amount);
            }
            if(isset($separator[$arm_currency]['decimal']) && $separator[$arm_currency]['decimal'] != '')
            {
                return number_format((float) $arm_main_amount , $arm_currency_decimal, $separator[$arm_currency]['decimal'], $separator[$arm_currency]['thousand']);
            }
            else if( isset($separator[$arm_currency]['decimal']) && $separator[$arm_currency]['decimal'] == '')
            {
                
                
                $floar_amount = floor($arm_main_amount);
                $after_decimal_point_val = $arm_main_amount - $floar_amount;
                if($is_coupon_amount && $after_decimal_point_val == '0.50')
                {
                    return number_format((float) floor($arm_main_amount));
                }
                else
                {
                    return number_format((float) $arm_main_amount,0,'',$separator[$arm_currency]['thousand']);
                }
            }
            else
            {
                return number_format((float) $arm_main_amount , $arm_currency_decimal, '.', ',');
            }
            
        }

        function arm_get_bank_transfer_mode_options() {
            $transfer_mode = array(
                    'bank_transfer' => esc_html__('Digital Transfer', 'ARMember'), 
                    'cheque' => esc_html__('Cheque', 'ARMember'), 
                    'cash' => esc_html__('Cash', 'ARMember')
                    );
            return $transfer_mode;
        }

        function get_currency_separators_standard() {
            $separator = array( "USD"=> array( 'decimal' => '.', 'thousand' => ',' ), "AUD"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "BRL"=> array( 'decimal' => '.', 'thousand' => ',' ), "CAD"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "CZK"=> array( 'decimal' => '.', 'thousand' => ',' ), "DKK"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "EUR"=> array( 'decimal' => '.', 'thousand' => ',' ), "HKD"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "ILS"=> array( 'decimal' => '.', 'thousand' => ',' ), "ZMW"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "MYR"=> array( 'decimal' => '.', 'thousand' => ',' ), "UZS"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "MXN"=> array( 'decimal' => '.', 'thousand' => ',' ), "NOK"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "NZD"=> array( 'decimal' => '.', 'thousand' => ',' ), "PHP"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "PLN"=> array( 'decimal' => '.', 'thousand' => ',' ), "GBP"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "RUB"=> array( 'decimal' => '.', 'thousand' => ',' ), "SGD"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "SEK"=> array( 'decimal' => '.', 'thousand' => ',' ), "CHF"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "THB"=> array( 'decimal' => '.', 'thousand' => ',' ), "UGX"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "TRY"=> array( 'decimal' => '.', 'thousand' => ',' ), "AFN"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "ALL"=> array( 'decimal' => '.', 'thousand' => ',' ), "ARS"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "AZN"=> array( 'decimal' => '.', 'thousand' => ',' ), "DZD"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "BSD"=> array( 'decimal' => '.', 'thousand' => ',' ), "BDT"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "BBD"=> array( 'decimal' => '.', 'thousand' => ',' ), "BZD"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "BMD"=> array( 'decimal' => '.', 'thousand' => ',' ), "BOB"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "BWP"=> array( 'decimal' => '.', 'thousand' => ',' ), "BND"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "BGN"=> array( 'decimal' => '.', 'thousand' => ',' ), "SHP"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "CNY"=> array( 'decimal' => '.', 'thousand' => ',' ), "COP"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "CRC"=> array( 'decimal' => '.', 'thousand' => ',' ), "HRK"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "DOP"=> array( 'decimal' => '.', 'thousand' => ',' ), "XCD"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "EGP"=> array( 'decimal' => '.', 'thousand' => ',' ), "FJD"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "GTQ"=> array( 'decimal' => '.', 'thousand' => ',' ), "HNL"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "INR"=> array( 'decimal' => '.', 'thousand' => ',' ), "IDR"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "JMD"=> array( 'decimal' => '.', 'thousand' => ',' ), "KZT"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "KES"=> array( 'decimal' => '.', 'thousand' => ',' ), "LAK"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "MMK"=> array( 'decimal' => '.', 'thousand' => ',' ), "LBP"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "LRD"=> array( 'decimal' => '.', 'thousand' => ',' ), "MOP"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "MVR"=> array( 'decimal' => '.', 'thousand' => ',' ), "MRO"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "MUR"=> array( 'decimal' => '.', 'thousand' => ',' ), "MAD"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "NPR"=> array( 'decimal' => '.', 'thousand' => ',' ), "NIO"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "PKR"=> array( 'decimal' => '.', 'thousand' => ',' ), "PGK"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "PEN"=> array( 'decimal' => '.', 'thousand' => ',' ), "QAR"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "RON"=> array( 'decimal' => '.', 'thousand' => ',' ), "WST"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "SAR"=> array( 'decimal' => '.', 'thousand' => ',' ), "SCR"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "SGF"=> array( 'decimal' => '.', 'thousand' => ',' ), "SBD"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "ZAR"=> array( 'decimal' => '.', 'thousand' => ',' ), "MKD"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "LKR"=> array( 'decimal' => '.', 'thousand' => ',' ), "SYP"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "TOP"=> array( 'decimal' => '.', 'thousand' => ',' ), "TTD"=> array( 'decimal' => '.', 'thousand' => ',' ), 
                                "UAH"=> array( 'decimal' => '.', 'thousand' => ',' ), "AED"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "YER"=> array( 'decimal' => '.', 'thousand' => ',' ), "ETB"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "AMD"=> array( 'decimal' => '.', 'thousand' => ',' ), "ANG"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "AOA"=> array( 'decimal' => '.', 'thousand' => ',' ), "AWG"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "CDF"=> array( 'decimal' => '.', 'thousand' => ',' ), "CVE"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "FKP"=> array( 'decimal' => '.', 'thousand' => ',' ), "GEL"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "GIP"=> array( 'decimal' => '.', 'thousand' => ',' ), "GMD"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "GYD"=> array( 'decimal' => '.', 'thousand' => ',' ), "KYD"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "HTG"=> array( 'decimal' => '.', 'thousand' => ',' ), "ISK"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "KGS"=> array( 'decimal' => '.', 'thousand' => ',' ), "KHR"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "LSL"=> array( 'decimal' => '.', 'thousand' => ',' ), "MDL"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "MNT"=> array( 'decimal' => '.', 'thousand' => ',' ), "MWK"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "MZN"=> array( 'decimal' => '.', 'thousand' => ',' ), "NAD"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "NGN"=> array( 'decimal' => '.', 'thousand' => ',' ), "PAB"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "RSD"=> array( 'decimal' => '.', 'thousand' => ',' ), "UYU"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "TJS"=> array( 'decimal' => '.', 'thousand' => ',' ), "SLL"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "SOS"=> array( 'decimal' => '.', 'thousand' => ',' ), "SRD"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "STD"=> array( 'decimal' => '.', 'thousand' => ',' ), "SVC"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "SZL"=> array( 'decimal' => '.', 'thousand' => ',' ), "TZS"=> array( 'decimal' => '.', 'thousand' => ',' ),
                                "BAM"=> array( 'decimal' => '.', 'thousand' => ',' ), "BIF"=> array( 'decimal' => '', 'thousand' => ',' ),
                                "KRW"=> array( 'decimal' => '', 'thousand' => ',' ), "MGA"=> array( 'decimal' => '', 'thousand' => ',' ),
                                "RWF"=> array( 'decimal' => '', 'thousand' => ',' ), "CLP"=> array( 'decimal' => '', 'thousand' => ',' ), 
                                "XAF"=> array( 'decimal' => '', 'thousand' => ',' ), "TWD"=> array( 'decimal' => '', 'thousand' => ',' ),
                                "XPF"=> array( 'decimal' => '', 'thousand' => ',' ), "JPY"=> array( 'decimal' => '', 'thousand' => ',' ),
                                "HUF"=> array( 'decimal' => '', 'thousand' => ',' ), "PYG"=> array( 'decimal' => '', 'thousand' => ',' ),
                                "VUV"=> array( 'decimal' => '', 'thousand' => ',' ), "VND"=> array( 'decimal' => '', 'thousand' => ',' ), 
                                "XOF"=> array( 'decimal' => '', 'thousand' => ',' ), "DJF"=> array( 'decimal' => '', 'thousand' => ',' ),
                                "GNF"=> array( 'decimal' => '', 'thousand' => ',' ), "KMF"=> array( 'decimal' => '', 'thousand' => ',' ),
                                "GHS"=> array( 'decimal' => '.','thousand' => ',' ),"BHD"=> array( 'decimal' => '.', 'thousand' => ','),
                                "BTC" => array('decimal' => '.','thousand' => ','), "BTN"=> array( 'decimal' => '.', 'thousand' => ','),
                                "BYR" => array('decimal' => '.','thousand' => ','),"BYN" => array( 'decimal' => '.', 'thousand' => ','),
                                "CUC" => array('decimal' => '.','thousand' => ','),"CUP" => array( 'decimal' => '.', 'thousand' => ','),
                                "ERN" => array('decimal' => '.','thousand' => ','), "GGP"=> array( 'decimal' => '.', 'thousand' => ','),
                                "IMP" => array('decimal' => '.','thousand' => ','), "IQD" => array( 'decimal' => '.', 'thousand' => ','),
                                "IRR" => array('decimal' => '.','thousand' => ','), "IRT" => array( 'decimal' => '.', 'thousand' => ','),
                                "JEP" => array('decimal' => '.','thousand' => ','), "JOD" => array( 'decimal' => '.', 'thousand' => ','),
                                "KPW" => array('decimal' => '.','thousand' => ','), "KWD" => array( 'decimal' => '.', 'thousand' => ','),
                                "LYD" => array('decimal' => '.','thousand' => ','), "MRU" => array( 'decimal' => '.', 'thousand' => ','),
                                "OMR" => array('decimal' => '.','thousand' => ','), "PRB" => array( 'decimal' => '.', 'thousand' => ','),
                                "RMB" => array('decimal' => '.','thousand' => ','), "SDG" => array( 'decimal' => '.', 'thousand' => ','),
                                "SSP" => array('decimal' => '.','thousand' => ','), "STN" => array( 'decimal' => '.', 'thousand' => ','),
                                "TMT" => array('decimal' => '.','thousand' => ','), "TND" => array( 'decimal' => '.', 'thousand' => ','),
                                "VEF" => array('decimal' => '.','thousand' => ','), "VES" => array( 'decimal' => '.', 'thousand' => ','),
                );
            return $separator;
        }

        function get_currency_wise_separator($arm_currency) {
            $separators = array();
            $arm_currency = strtoupper($arm_currency);
            $currency_separators = $this->get_other_seperator_currencies();

            if(!empty($arm_currency) && !empty($currency_separators[$arm_currency])) {
                $separators = $currency_separators[$arm_currency];
            }
            return $separators;
        }

        function get_other_seperator_currencies() {
            $currency_separators = array( "BRL" => array( 'decimal' => ',', 'thousand' => '.' ),
                                        "BYR" => array('decimal' => ',', 'thousand' => ''),
                                        "BYN" => array('decimal' => ',', 'thousand' => ''),
                                        "CUC" => array('decimal' => ',', 'thousand' => '.'),
                                        "CUP" => array('decimal' => ',', 'thousand' => '.'),
                                        "TMT" => array('decimal' => ',', 'thousand' => ''),
                                        "TND" => array('decimal' => ',', 'thousand' => '.'),
                                        "VEF" => array('decimal' => ',', 'thousand' => '.'), 
                                        "VES" => array('decimal' => ',', 'thousand' => '.'),
                );
            $currency_separators = apply_filters('arm_currency_separators', $currency_separators);
            
            return $currency_separators;
        }

        function arm_verify_stripe_webhook_func($secrate_key = '') {

            global $arm_global_settings, $ARMember, $arm_capabilities_global;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1);//phpcs:ignore --Reason:Verifying nonce

            $statusRes = array('type' => 'success', 'message' => esc_html__('Stripe Webhook has been verifed successfully.', 'ARMember'));
            $secrate_key = (isset($_POST['secrate_key'])) ? sanitize_text_field($_POST['secrate_key']) : $secrate_key; //phpcs:ignore
			
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
            echo arm_pattern_json_encode($statusRes);
	    die();			
        }
    }
}
global $arm_payment_gateways;
$arm_payment_gateways = new ARM_payment_gateways();
