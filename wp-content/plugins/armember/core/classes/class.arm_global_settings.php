<?php 
if (!class_exists('ARM_global_settings')) {

    class ARM_global_settings {

        private $s;
        private $sub_folder;
        private $is_subdir_mu;
        private $blog_path;
        var $global_settings;
        var $block_settings;
        var $common_message;
        var $profile_url;

        function __construct() {
            global $wpdb, $ARMember, $arm_slugs; 
            /* ====================================/.Begin Set Global Settings For Class./==================================== */
            //$this->global_settings = $this->arm_get_all_global_settings(TRUE);
            //$this->block_settings = $this->arm_get_parsed_block_settings();
            //$this->common_message = $this->arm_get_all_common_message_settings();

            $sub_installation = trim(str_replace(ARM_HOME_URL, '', site_url()), ' /');
            if ($sub_installation && substr($sub_installation, 0, 4) != 'http') {
                $this->sub_folder = $sub_installation . '/';
            }
            $this->is_subdir_mu = false;
            if (is_multisite()) {
                $this->is_subdir_mu = true;
                if ((defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) || (defined('VHOST') && VHOST == 'yes')) {
                    $this->is_subdir_mu = false;
                }
            }
            if (is_multisite() && !$this->sub_folder && $this->is_subdir_mu) {
                $this->sub_folder = ltrim(parse_url(trim(get_blog_option(BLOG_ID_CURRENT_SITE, 'home'), '/') . '/', PHP_URL_PATH), '/');
            }
            if (is_multisite() && !$this->blog_path && $this->is_subdir_mu) {
                global $current_blog;
                $this->blog_path = str_replace($this->sub_folder, '', $current_blog->path);
            }
            /* ====================================/.End Set Global Settings For Class./==================================== */
            add_filter( 'armember_modify_general_settings_view_file_path', array($this,'armember_modify_general_settings_view_file_path'), 10);
            add_action('wp_ajax_arm_send_test_mail', array($this, 'arm_send_test_mail'));
            add_action('wp_ajax_arm_send_test_gmail', array($this, 'arm_send_test_mail'));
            add_action('wp_ajax_arm_validate_test_gmail', array($this, 'arm_validate_test_gmail'));
            add_action('wp_ajax_arm_update_global_settings', array($this, 'arm_update_all_settings'));
            add_action('wp_ajax_arm_update_block_settings', array($this, 'arm_update_all_settings'));
            add_action('wp_ajax_arm_update_redirect_settings', array($this, 'arm_update_all_settings'));
            add_action('wp_ajax_arm_page_settings', array($this, 'arm_update_all_settings'));
            add_action('wp_ajax_arm_update_common_message_settings', array($this, 'arm_update_all_settings'));
            add_action('wp_ajax_arm_update_invoice_settings', array($this, 'arm_update_all_settings'));
            add_action('wp_ajax_arm_update_access_restriction_settings', array($this, 'arm_update_all_settings'));
            
            add_action('wp_ajax_arm_shortcode_exist_in_page', array($this, 'arm_shortcode_exist_in_page'));
            add_action('wp_ajax_arm_social_form_exist_in_page', array($this, 'arm_social_form_exist_in_page'));
            add_action('wp_ajax_arm_update_feature_settings', array($this, 'arm_update_feature_settings'));
            add_action('wp_ajax_arm_update_pay_per_post_settings', array($this, 'arm_update_all_settings'));
            add_action('wp_ajax_arm_update_api_service_feature', array(&$this, 'arm_update_all_settings'));
            add_filter('arm_addon_activate_button_section',array(&$this,'arm_addon_activate_button_section_func'),10,4);
            add_filter('arm_addon_activate_license_form',array(&$this,'arm_addon_activate_license_form_func'));

            /* Apply Global Setting Action */
            //add_action('init', array($this, 'arm_apply_global_settings'), 200);
            add_action('login_init', array($this, 'arm_check_ip_block_before_login'), 1);
            add_action('login_head', array($this, 'arm_login_enqueue_assets'), 50);
            add_filter('option_users_can_register', array($this, 'arm_remove_registration_link'));
            add_filter('lostpassword_redirect', array($this, 'arm_modify_lost_password_link'));

            /* Enable Shortcodes in Widgets */
            add_filter('widget_text', 'do_shortcode');
            /* Filter Post Excerpt for plugin shortcodes */
            add_filter("the_excerpt", array($this, 'arm_filter_the_excerpt'));
            add_filter("the_excerpt_rss", array($this, 'arm_filter_the_excerpt'));

            /* Rewrite Rules */
            add_action('admin_notices', array($this, 'arm_admin_notices'));
            add_action('updated_option', array($this, 'arm_updated_option'), 10, 3);

            add_filter('arm_display_admin_notices', array($this, 'arm_global_settings_notices'));
            /* Filter `get_avatar` */
            add_filter('get_avatar', array($this, 'arm_filter_get_avatar'), 20, 5);
	    /* Filter `get_avatar_url` */
            add_filter('get_avatar_url', array($this, 'arm_filter_get_avatar_url'), 20, 3);
            add_filter('arm_check_member_status_before_login', array($this, 'arm_check_member_status'), 10, 2);
            /* add_filter('arm_check_member_status_before_login', array($this, 'arm_check_block_settings'), 5, 2); */
            /* Delete Term Action Hook */
            add_action('delete_term', array($this, 'arm_after_delete_term'), 10, 4);
            /* Added From Name And Form Email Hook */
            //add_action('admin_enqueue_scripts', array($this, 'arm_add_page_label_css'), 20);
            add_filter('display_post_states', array($this, 'arm_add_set_page_label'), 999, 2);

            add_action('wp_ajax_arm_custom_css_detail', array($this, 'arm_custom_css_detail'));
            add_action('wp_ajax_arm_section_custom_css_detail', array($this, 'arm_section_custom_css_detail'));
            /* Set Global Profile URL */
            add_filter('query_vars', array($this, 'arm_user_query_vars'), 10, 1);
            add_action('wp_ajax_arm_clear_form_fields', array($this, 'arm_clear_form_fields'));
            add_action('wp_ajax_arm_failed_login_lockdown_clear', array($this, 'arm_failed_login_lockdown_clear'));

            add_action('wp_ajax_arm_failed_login_history_clear', array($this, 'arm_failed_login_history_clear'));


            /* bbpress change forum author link */
            add_filter('bbp_get_topic_author_url', array($this, 'arm_bbpress_change_topic_author_url'), 10, 2);
            add_filter('bbp_get_reply_author_url', array($this, 'arm_bbpress_change_reply_author_url'), 10, 2);

            add_action('after_switch_theme',array($this,'arm_set_permalink_for_profile_page'),10);
            add_action('permalink_structure_changed', array($this,'arm_set_session_for_permalink'));
            //add_action('admin_footer',array($this,'arm_rewrite_rules_for_profile_page'),100);

            add_filter( 'generate_rewrite_rules', array($this,'arm_generate_rewrite_rules'),10 );

            add_action('wp_ajax_arm_reset_invoice_to_default', array($this, 'arm_reset_invoice_to_default'));

            //add_action('admin_init',array($this,'arm_plugin_add_suggested_privacy_content'),20);

            add_action('wp_ajax_arm_check_setup_payment_gateway_fields', array($this, 'arm_check_setup_payment_fields'));

            add_action( 'wp',array($this,'arm_validate_api'),10);

            /** General settings pro plugin's content filter */

            add_filter( 'arm_load_global_settings_section', array($this,'arm_load_global_settings_section'), 10, 1 );

            add_filter( 'arm_load_restriction_settings_section', array($this,'arm_load_restriction_settings_section'), 10);

            add_filter( 'arm_load_security_settings_section', array($this,'arm_load_security_settings_section'), 10);

            add_filter( 'arm_load_page_setup_section', array($this,'arm_load_page_setup_section'), 10, 1 );

            add_filter( 'arm_load_common_message_section', array($this,'arm_load_common_message_section'), 10, 1 );

            add_filter( 'arm_load_debug_settings_section', array($this,'arm_load_debug_settings_section'), 10, 1 );
            
            add_filter( 'arm_payment_gateway_sections', array($this,'arm_payment_gateway_sections'), 10, 1 );

            add_filter( 'arm_redirection_rules_sections', array($this,'arm_redirection_rules_sections'), 10, 1 );
        }
        function arm_redirection_rules_sections($section)
        {
            $file = MEMBERSHIP_VIEWS_DIR . '/arm_redirection_settings.php';
            require $file;

            return $arm_redirection_rule_html;
        }
        function arm_payment_gateway_sections($section)
        {
            $file = MEMBERSHIP_VIEWS_DIR . '/arm_manage_payment_gateways.php';
            require $file;

            return $arm_payment_gateway_html;
        }
        function arm_load_debug_settings_section($section)
        {
            $file = MEMBERSHIP_VIEWS_DIR . '/arm_debug_logs.php';
            require $file;

            return $arm_debug_log_html;
        }
        function arm_load_restriction_settings_section($section){
            $file = MEMBERSHIP_VIEWS_DIR . '/arm_access_restriction_settings.php';
            require $file;

            return $arm_restriction_settings;
        }

        function arm_load_security_settings_section($section){
            $file = MEMBERSHIP_VIEWS_DIR . '/arm_block_settings.php';
            require $file;

            return $arm_block_settings;
        }
        function arm_load_global_settings_section($section){

            $file = MEMBERSHIP_VIEWS_DIR . '/arm_global_settings.php';
            require $file;

            return $arm_html_content;
        }
        function arm_load_page_setup_section($section){
            
            $file = MEMBERSHIP_VIEWS_DIR . '/arm_page_setup.php';
            require $file;

            return $arm_html_content;
        }
        function arm_load_common_message_section($section){

            $file = MEMBERSHIP_VIEWS_DIR . '/arm_common_messages_settings.php';
            require $file;
            
            return $arm_html_content;
        }
        function armember_modify_general_settings_view_file_path(){

            $file = MEMBERSHIP_VIEWS_DIR . '/arm_general_settings.php';
            require $file;
        }
        function arm_validate_stripe_api(){
            global $arm_debug_payment_log_id,$arm_payment_gateways,$arm_stripe;
            if( isset( $_REQUEST['page'] ) && 'arm_validate_stripe' == $_REQUEST['page'] ){//phpcs:ignore
                $access_code = !empty($_REQUEST['code']) ? sanitize_text_field($_REQUEST['code']) : '';//phpcs:ignore
                $secret_key = (!empty($_REQUEST['connection_mode']) && $_REQUEST['connection_mode'] =='test') ? $arm_stripe->arm_test_client_secret_key : $arm_stripe->arm_live_client_secret_key;//phpcs:ignore
                $headers = array(
                    'Authorization' => 'Bearer '.$secret_key,
                );
                $url = 'https://connect.stripe.com/oauth/token';
                $data = array('grant_type'=>'authorization_code', 'code'=>$access_code);
                $arm_stripe_plan_data = array('method' => 'POST', 'headers' => $headers, 'body' => $data);
                $response = wp_remote_post(
                    $url,
                    $arm_stripe_plan_data
                );

                $stripe_token_result_data=addslashes(serialize($response));

                $response_data = json_decode($response['body']);
                $account_id = $response_data->stripe_user_id;
                $arm_stripe_secret_key = $response_data->access_token;
                $arm_stripe_publish_key = $response_data->stripe_publishable_key;
                if(!empty($arm_stripe_secret_key) && !empty($arm_stripe_publish_key)){
                    $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
                    $stripe_options = $all_payment_gateways['stripe'];
                    $stripe_options['account_id'] = $account_id;    
                    if (str_contains($arm_stripe_secret_key, 'test')) { 
                        $stripe_options['stripe_test_secret_key'] = $arm_stripe_secret_key;
                        $stripe_options['stripe_test_pub_key'] = $arm_stripe_secret_key;
                    }
                    else
                    {
                        $stripe_options['stripe_secret_key'] = $arm_stripe_secret_key;
                        $stripe_options['stripe_pub_key'] = $arm_stripe_publish_key;
                    }
                    $all_payment_gateways['stripe'] = $stripe_options;
                    update_option('arm_payment_gateway_settings',$all_payment_gateways);
                    echo "<script type='text/javascript'> window.opener.location.reload(true)</script>";
                    echo "<script type='text/javascript'>window.close();</script>";
                }
            }
        }

        function arm_check_setup_payment_fields()
        {
            global $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1);
            $arm_selected_payment_gateways = json_decode(stripslashes($_POST['arm_selected_pgs'])); //phpcs:ignore
            if(!empty($arm_selected_payment_gateways))
            {
                $arm_payment_gateway_options = get_option('arm_payment_gateway_settings');
                $arm_pg_validation_return['status'] = 0;
                $arm_pg_validation_return['message'] = '';

                if(in_array('stripe', $arm_selected_payment_gateways) && !empty($arm_payment_gateway_options['stripe']['status']))
                {
                    $arm_stripe_fields_validate['stripe_payment_mode'] = esc_html__("Stripe Payment Mode", "ARMember");

                    if($arm_payment_gateway_options['stripe']['stripe_payment_method'] == "popup")
                    {
                        $arm_stripe_fields_validate['stripe_popup_title'] = esc_html__("Popup Title", "ARMember");
                        $arm_stripe_fields_validate['stripe_popup_button_lbl'] = esc_html__("Popup Button Title", "ARMember");
                    }

                    if($arm_payment_gateway_options['stripe']['stripe_payment_mode'] == "test")
                    {
                        $arm_stripe_fields_validate['stripe_test_secret_key'] = esc_html__("Stripe Test Secret Key", "ARMember");
                        $arm_stripe_fields_validate['stripe_test_pub_key'] = esc_html__("Stripe Test Public Key", "ARMember");
                    }
                    else
                    {
                        $arm_stripe_fields_validate['stripe_secret_key'] = esc_html__("Stripe Live Secret Key", "ARMember");
                        $arm_stripe_fields_validate['stripe_pub_key'] = esc_html__("Stripe Live Public Key", "ARMember");
                    }

                    $arm_stripe_error_fields = array();
                    foreach($arm_stripe_fields_validate as $arm_stripe_validation_key => $arm_stripe_validation_field)
                    {
                        if(empty($arm_payment_gateway_options['stripe'][$arm_stripe_validation_key]))
                        {
                            array_push($arm_stripe_error_fields, $arm_stripe_validation_field);
                        }
                    }

                    if(!empty($arm_stripe_error_fields))
                    {
                        $arm_validation_msg = "<div class='arm_pg_error_msg'>";
                        $arm_validation_msg .= implode(',', $arm_stripe_error_fields).esc_html__(' fields required', 'ARMember');
                        $arm_validation_msg .= "</div>";

                        $arm_pg_validation_return['status'] = 1;
                        $arm_pg_validation_return['message'] = $arm_validation_msg;
                    }
                }
                
                if(in_array('paypal', $arm_selected_payment_gateways) && !empty($arm_payment_gateway_options['paypal']['status']))
                {
                    $arm_paypal_fields_validate['paypal_payment_mode'] = esc_html__("Paypal Payment Mode", "ARMember");
                    if($arm_payment_gateway_options['paypal']['paypal_payment_mode'] == "sandbox")
                    {
                        $arm_paypal_fields_validate['sandbox_api_username'] = esc_html__('Paypal Sandbox API Username', 'ARMember');
                        $arm_paypal_fields_validate['sandbox_api_password'] = esc_html__('Paypal Sandbox API Password', 'ARMember');
                        $arm_paypal_fields_validate['sandbox_api_signature'] = esc_html__('Paypal Sandbox API Signature', 'ARMember');
                    }
                    else
                    {
                        $arm_paypal_fields_validate['sandbox_api_username'] = esc_html__('Paypal Live API Username', 'ARMember');
                        $arm_paypal_fields_validate['sandbox_api_password'] = esc_html__('Paypal Live API Password', 'ARMember');
                        $arm_paypal_fields_validate['sandbox_api_signature'] = esc_html__('Paypal Live API Signature', 'ARMember');
                    }

                    $arm_paypal_error_fields = array();
                    foreach($arm_paypal_fields_validate as $arm_paypal_validate_key => $arm_paypal_validation_field)
                    {
                        if(empty($arm_payment_gateway_options['paypal'][$arm_paypal_validate_key]))
                        {
                            array_push($arm_paypal_error_fields, $arm_paypal_validation_field);
                        }
                    }

                    $arm_validation_msg = "";
                    if(!empty($arm_paypal_error_fields))
                    {
                        $arm_validation_msg .= "<div class='arm_pg_error_msg'>";
                        $arm_validation_msg .= implode(',', $arm_paypal_error_fields).esc_html__(' fields required', 'ARMember');
                        $arm_validation_msg .= "</div>";

                        $arm_pg_validation_return['status'] = 1;
                        $arm_pg_validation_return['message'] = $arm_pg_validation_return['message']." ".$arm_validation_msg;
                    }
                }


                if(in_array('authorize_net', $arm_selected_payment_gateways) && !empty($arm_payment_gateway_options['authorize_net']['status']))
                {
                    $arm_autho_fields_validate['autho_mode'] = esc_html__("Authorize.Net Payment Mode", "ARMember");
                    
                    $arm_autho_fields_validate['autho_api_login_id'] = esc_html__('Authorize.Net API Login ID', 'ARMember');

                    $arm_autho_fields_validate['autho_transaction_key'] = esc_html__('Authorize.Net Transaction Key', 'ARMember');

                    $arm_autho_error_fields = array();
                    foreach($arm_autho_fields_validate as $arm_autho_validate_key => $arm_autho_validation_field)
                    {
                        if(empty($arm_payment_gateway_options['authorize_net'][$arm_autho_validate_key]))
                        {
                            array_push($arm_autho_error_fields, $arm_autho_validation_field);
                        }
                    }

                    $arm_validation_msg = "";
                    if(!empty($arm_autho_error_fields))
                    {
                        $arm_validation_msg .= "<div class='arm_pg_error_msg'>";
                        $arm_validation_msg .= implode(',', $arm_autho_error_fields).esc_html__(' fields required', 'ARMember');
                        $arm_validation_msg .= "</div>";

                        $arm_pg_validation_return['status'] = 1;
                        $arm_pg_validation_return['message'] = $arm_pg_validation_return['message']." ".$arm_validation_msg;
                    }
                }


                if(in_array('2checkout', $arm_selected_payment_gateways) && !empty($arm_payment_gateway_options['2checkout']['status']))
                {
                    $arm_2checkout_fields_validate['payment_mode'] = esc_html__("2 Checkout Payment Mode", "ARMember");
                    
                    $arm_2checkout_fields_validate['username'] = esc_html__('2 Checkout Username', 'ARMember');

                    $arm_2checkout_fields_validate['password'] = esc_html__('2 Checkout Password', 'ARMember');

                    $arm_2checkout_fields_validate['sellerid'] = esc_html__('2 Checkout Seller ID', 'ARMember');

                    $arm_2checkout_fields_validate['private_key'] = esc_html__('2 Checkout Private Key', 'ARMember');

                    $arm_2checkout_fields_validate['api_secret_key'] = esc_html__('2 Checkout Secret Key', 'ARMember');

                    $arm_2checkout_fields_validate['secret_word'] = esc_html__('2 Checkout Secret Word', 'ARMember');

                    $arm_2checkout_error_fields = array();
                    foreach($arm_2checkout_fields_validate as $arm_2checkout_validate_key => $arm_2checkout_validation_field)
                    {
                        if(empty($arm_payment_gateway_options['2checkout'][$arm_2checkout_validate_key]))
                        {
                            array_push($arm_2checkout_error_fields, $arm_2checkout_validation_field);
                        }
                    }

                    $arm_validation_msg = "";
                    if(!empty($arm_2checkout_error_fields))
                    {
                        $arm_validation_msg .= "<div class='arm_pg_error_msg'>";
                        $arm_validation_msg .= implode(',', $arm_2checkout_error_fields).esc_html__(' fields required', 'ARMember');
                        $arm_validation_msg .= "</div>";

                        $arm_pg_validation_return['status'] = 1;
                        $arm_pg_validation_return['message'] = $arm_pg_validation_return['message']." ".$arm_validation_msg;
                    }
                }


                $arm_pg_validation_return = apply_filters('arm_configure_setup_payment_gateway_validations', $arm_pg_validation_return, $arm_selected_payment_gateways);
                echo arm_pattern_json_encode($arm_pg_validation_return);
                exit();
            }
            echo arm_pattern_json_encode(array('status'=>0));
            exit();
        }

        function arm_check_common_date_format($selected_date_format)
        {
            $return_final_date_format = '';
            if($selected_date_format == 'F j, Y' || $selected_date_format == 'Y-m-d' || $selected_date_format == 'm/d/Y' || $selected_date_format == 'j F Y' || $selected_date_format == "Y m d")
            {
                return $selected_date_format;
            }
            else if($selected_date_format == 'd/m/Y' || $selected_date_format == 'd-m-Y' || $selected_date_format == 'd m Y' || $selected_date_format == 'j. F Y' || $selected_date_format == 'j F, Y')
            {
                return 'm/d/Y';
            }
            else
            {
                $arm_supported_date_formats = array('d', 'D', 'm', 'M', 'y', 'Y', 'f', 'F', 'j', 'J');
                
                if(substr_count($selected_date_format, '-'))
                {
                    $arm_tmp_date_format_arr = explode('-', $selected_date_format);
                    $return_final_date_format = "";
                    foreach($arm_tmp_date_format_arr as $arm_key => $arm_value)
                    {
                        if(in_array($arm_value, $arm_supported_date_formats))
                        {
                            $return_final_date_format .= $return_final_date_format.'-';
                        }
                    }
                    if(in_array($arm_tmp_date_format_arr[0], $arm_supported_date_formats))
                    {
                        $return_final_date_format = $arm_tmp_date_format_arr[0].'-';
                    }

                    if(in_array($arm_tmp_date_format_arr[1], $arm_supported_date_formats))
                    {
                        $return_final_date_format = $return_final_date_format.$arm_tmp_date_format_arr[1].'-';
                    }

                    if(in_array($arm_tmp_date_format_arr[2], $arm_supported_date_formats))
                    {
                        $return_final_date_format = $return_final_date_format.$arm_tmp_date_format_arr[2];
                    }

                    return $return_final_date_format;
                }
                else if(substr_count($selected_date_format, '/'))
                {
                    $arm_tmp_date_format_arr = explode('/', $selected_date_format);
                    $return_final_date_format = "";
                    if(in_array($arm_tmp_date_format_arr[0], $arm_supported_date_formats))
                    {
                        $return_final_date_format = $arm_tmp_date_format_arr[0].'/';
                    }

                    if(in_array($arm_tmp_date_format_arr[1], $arm_supported_date_formats))
                    {
                        $return_final_date_format = $return_final_date_format.$arm_tmp_date_format_arr[1].'/';
                    }

                    if(in_array($arm_tmp_date_format_arr[2], $arm_supported_date_formats))
                    {
                        $return_final_date_format = $return_final_date_format.$arm_tmp_date_format_arr[2];
                    }

                    return $return_final_date_format;
                }
                /*
                else if(substr_count($selected_date_format, ' '))
                {
                    $arm_tmp_date_format_arr = explode(' ', $selected_date_format);
                    $return_final_date_format = "";
                    if(in_array($arm_tmp_date_format_arr[0], $arm_supported_date_formats))
                    {
                        $return_final_date_format = $arm_tmp_date_format_arr[0].' ';
                    }


                    if(in_array($arm_tmp_date_format_arr[1], $arm_supported_date_formats))
                    {
                        $return_final_date_format = $return_final_date_format.$arm_tmp_date_format_arr[1].' ';
                    }
                    

                    if(in_array($arm_tmp_date_format_arr[2], $arm_supported_date_formats))
                    {
                        $return_final_date_format = $return_final_date_format.$arm_tmp_date_format_arr[2];
                    }

                    return $return_final_date_format;
                }
                */
                else
                {
                    return 'm/d/Y';
                }
            }
        }

        function arm_plugin_add_suggested_privacy_content(){
            if(function_exists('wp_add_privacy_policy_content'))
            {
                $content = $this->arm_get_privacy_content();
                wp_add_privacy_policy_content( 'ARMember', $content);
            }
        }

        function arm_get_privacy_content(){
            $arm_gdpr_mode_cnt_default = '<h2>'.esc_html__('What personal data collected in ARMember','ARMember') .'</h2>'
                            . '<p>'.esc_html__('User\'s Signup Details such as Username, Password, First Name, Last Name and Custom Fields value( Address, Gender etc)','ARMember') . '</p>'
                            . '<p>'.esc_html__('User\'s IP Address Information','ARMember') . '</p>'
                            . '<p>'.esc_html__('User\'s Basic Details Sending to opt-ins such as (Email, First Name, Last Name)','ARMember') . '</p>'
                            . '<p>'.esc_html__('User\'s Logged in / Logout details','ARMember') . '</p>'
                            . '<p>'.esc_html__('User\'s Basic Social Accounts Details','ARMember') . '</p>'
                            . '<p>'.esc_html__('User\'s Basic Payment Transaction Details (Not Storing any sensitive Payment Data such as Credit/Debit Card Details.)','ARMember') . '</p>';

            return $arm_gdpr_mode_cnt_default;
        }

        function arm_reset_invoice_to_default(){
            global $ARMember, $arm_capabilities_global;
            $response = array('type'=> 'error');
            if(isset($_POST['action']) && $_POST['action'] == 'arm_reset_invoice_to_default'){ //phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
                $all_global_settings = $this->arm_get_all_global_settings();
                $general_settings = $all_global_settings['general_settings'];

                $arm_default_invoice_template = $this->arm_get_default_invoice_template();

                $general_settings['arm_invoice_template'] = $arm_default_invoice_template;
                $all_global_settings['general_settings'] = $general_settings;
                
                update_option('arm_global_settings', $all_global_settings);
                $response = array('type'=> 'success','msg'=>esc_html__('Invoice Reset Successfully','ARMember'),'arm_default_invoice' => $arm_default_invoice_template);
            }
            echo arm_pattern_json_encode($response);
            die();
            

        }

        function arm_set_permalink_for_profile_page(){
            $this->arm_user_rewrite_rules();
        }
        function arm_set_session_for_permalink(){
            global $ARMember;
            $ARMember->arm_session_start();
            $_SESSION['arm_site_permalink_is_changed'] = true;
        }
        function arm_rewrite_rules_for_profile_page(){
            global $wp_rewrite, $ARMember;
            $ARMember->arm_session_start();
            if( isset($_SESSION['arm_site_permalink_is_changed']) && $_SESSION['arm_site_permalink_is_changed'] == true ){
                $this->arm_user_rewrite_rules();
                $wp_rewrite->flush_rules(false);
                unset($_SESSION['arm_site_permalink_is_changed']);
            }
        }

        function arm_bbpress_change_topic_author_url($url, $topic_id) {
            global $arm_social_feature, $ARMember, $wpdb;
            $profileTemplate = $ARMember->tbl_arm_member_templates;
            if (!function_exists('is_plugin_active')) {
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if (is_plugin_active('bbpress/bbpress.php')) {
                $all_global_settings = $this->arm_get_all_global_settings();
                $general_settings = $all_global_settings['general_settings'];
                $bbpress_profile_page_id = (isset($general_settings['bbpress_profile_page'])) ? $general_settings['bbpress_profile_page'] : 0;

                if (!empty($bbpress_profile_page_id) && $bbpress_profile_page_id != 0) {
                    $profile_page_id = isset($this->global_settings['member_profile_page_id']) ? $this->global_settings['member_profile_page_id'] : 0;

                    if ($profile_page_id == $bbpress_profile_page_id) {

                        if ($arm_social_feature->isSocialFeature) {
                            if (function_exists('bbp_get_topic_author_id')) {
                                $author_id = bbp_get_topic_author_id($topic_id);
                                if (!empty($author_id)) {
                                    $templateOptions = $wpdb->get_var($wpdb->prepare("SELECT `arm_options` FROM {$profileTemplate} WHERE arm_type = %s", 'profile')); //phpcs:ignore --Reason $profileTemplate is a table name
                                    $display_admin_user = 0;
                                    if (!empty($templateOptions)) {
                                        $templateOptions = maybe_unserialize($templateOptions);
                                        $display_admin_user = $templateOptions['show_admin_users'];
                                    }
                                    $url = $this->arm_get_user_profile_url($author_id, $display_admin_user);
                                }
                            }
                        } else {
                            $url = get_permalink($bbpress_profile_page_id);
                        }
                    } else {
                        $url = get_permalink($bbpress_profile_page_id);
                    }
                    $url = apply_filters('arm_modify_redirection_page_external', $url,0,$profile_page_id);
                }
            }
            return $url;
        }

        function arm_bbpress_change_reply_author_url($url, $reply_id) {
            global $arm_social_feature, $ARMember, $wpdb;
            $profileTemplate = $ARMember->tbl_arm_member_templates;
            if (!function_exists('is_plugin_active')) {
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if (is_plugin_active('bbpress/bbpress.php')) {
                $all_global_settings = $this->arm_get_all_global_settings();
                $general_settings = $all_global_settings['general_settings'];
                $bbpress_profile_page_id = (isset($general_settings['bbpress_profile_page'])) ? $general_settings['bbpress_profile_page'] : 0;

                if (!empty($bbpress_profile_page_id) && $bbpress_profile_page_id != 0) {
                    $profile_page_id = isset($this->global_settings['member_profile_page_id']) ? $this->global_settings['member_profile_page_id'] : 0;
                    if ($profile_page_id == $bbpress_profile_page_id) {

                        if ($arm_social_feature->isSocialFeature) {
                            if (function_exists('bbp_get_topic_author_id') && function_exists('bbp_user_has_profile')) {
                                $author_id = bbp_get_reply_author_id($reply_id);
                                $anonymous = bbp_is_reply_anonymous($reply_id);

                                if (empty($anonymous) && !empty($author_id) && $author_id != 0 && bbp_user_has_profile($author_id)) {
                                    $templateOptions = $wpdb->get_var($wpdb->prepare("SELECT `arm_options` FROM {$profileTemplate} WHERE arm_type = %s", 'profile')); //phpcs:ignore --Reason $profileTemplate is a table name
                                    $display_admin_user = 0;
                                    if (!empty($templateOptions)) {
                                        $templateOptions = maybe_unserialize($templateOptions);
                                        $display_admin_user = $templateOptions['show_admin_users'];
                                    }
                                    $url = $this->arm_get_user_profile_url($author_id, $display_admin_user);
                                }
                            }
                        } else {
                            $url = get_permalink($bbpress_profile_page_id);
                        }
                    } else {
                        $url = get_permalink($bbpress_profile_page_id);
                    }
                    $url = apply_filters('arm_modify_redirection_page_external', $url,0,$profile_page_id);
                }
            }
            return $url;
        }

        function arm_failed_login_lockdown_clear() {
            global $wpdb, $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_block_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            if(!empty($_POST['reset_attempts_users']))//phpcs:ignore
            {
                $arm_reset_attempts_users = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST['reset_attempts_users'] );//phpcs:ignore
                if(isset($arm_reset_attempts_users) && !empty($arm_reset_attempts_users)) {
                 
                    if(in_array('all', $arm_reset_attempts_users)) {
                       
                        $delete = $wpdb->query("DELETE FROM `$ARMember->tbl_arm_fail_attempts`");//phpcs:ignore --Reason: $ARMember->tbl_arm_fail_attempts is table name
                        $delete = $wpdb->query("DELETE FROM `$ARMember->tbl_arm_lockdown`");//phpcs:ignore --Reason: $ARMember->tbl_arm_fail_attempts is table name. False
                    } else {
                        
                        foreach($arm_reset_attempts_users as $user_id){
                            $wpdb->delete( $ARMember->tbl_arm_fail_attempts, array( 'arm_user_id' => $user_id ), array( '%d' ) );
                            $wpdb->delete( $ARMember->tbl_arm_lockdown, array( 'arm_user_id' => $user_id ), array( '%d' ) );
                        }
                    }
                }
            }
            die();
        }

        function arm_failed_login_history_clear() {
            global $wpdb, $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_block_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $armembermessage = $ARMember->arm_alert_messages();
            $message = $armembermessage['clearLoginHistory'];
            $delete = $wpdb->query("DELETE FROM `$ARMember->tbl_arm_login_history`"); //phpcs:ignore --Reason $ARMember->tbl_arm_login_history is a table name without where clause
            $ARMember->arm_set_message('success', $message);

            die();
        }

        function arm_clear_form_fields() {
            global $wpdb, $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $arm_posted_data = isset($_POST['clear_fields']) ? array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST['clear_fields'] ) : array(); //phpcs:ignore

            $arm_deleted_fields = array();
            $presetFormFields = get_option('arm_preset_form_fields', '');
            $dbFormFields = maybe_unserialize($presetFormFields);

            if (isset($arm_posted_data) && !empty($arm_posted_data)) {
                foreach ($arm_posted_data as $key => $arm_field_key) {
                    $wpdb->query( $wpdb->prepare("DELETE FROM `" . $wpdb->usermeta . "` WHERE  `meta_key`=%s",$key) );//phpcs:ignore --Reason: $wpdb->usermeta is a table name .
                    unset($dbFormFields['other'][$key]);
                    array_push($arm_deleted_fields, $key);
                }
            }
            update_option('arm_preset_form_fields', $dbFormFields);
            echo arm_pattern_json_encode($arm_deleted_fields);
            die();
        }

        function arm_send_test_mail() {
            global $ARMember, $arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce

            $reply_to = (isset($_POST['reply_to']) && !empty($_POST['reply_to'])) ? sanitize_email($_POST['reply_to']) : ''; //phpcs:ignore
            $send_to = (isset($_POST['send_to']) && !empty($_POST['send_to'])) ? sanitize_email($_POST['send_to']) : ''; //phpcs:ignore
            $subject = (isset($_POST['subject']) && !empty($_POST['subject'])) ? sanitize_text_field($_POST['subject']) : esc_html__('SMTP Test E-Mail', 'ARMember'); //phpcs:ignore
            $message = (isset($_POST['message']) && !empty($_POST['message'])) ? sanitize_textarea_field($_POST['message']) : ''; //phpcs:ignore
            $reply_to_name = (isset($_POST['reply_to_name']) && !empty($_POST['reply_to_name'])) ? sanitize_text_field($_POST['reply_to_name']) : '';//phpcs:ignore

            
            $arm_mail_server = (isset($_POST['mail_server']) && !empty($_POST['mail_server'])) ? sanitize_text_field($_POST['mail_server']) : '';//phpcs:ignore
            if ($arm_mail_server =='gmail')
            {
                $arm_mail_client_id = (isset($_POST['client_id']) && !empty($_POST['client_id'])) ? sanitize_text_field($_POST['client_id']) : ''; //phpcs:ignore
                $arm_mail_client_secret = (isset($_POST['client_secret']) && !empty($_POST['client_secret'])) ? sanitize_text_field($_POST['client_secret']) : ''; //phpcs:ignore
                $arm_mail_client_token = (isset($_POST['client_token']) && !empty($_POST['client_token'])) ? sanitize_text_field($_POST['client_token']) : ''; //phpcs:ignore
                $arm_mail_auth_url = (isset($_POST['arm_google_auth_url']) && !empty($_POST['arm_google_auth_url'])) ? $_POST['arm_google_auth_url'] : ''; //phpcs:ignore


                if (empty($send_to) || empty($reply_to) || empty($message) || empty($subject)) {
                    return;
                }
                echo $arm_ajax_pattern_start;
                echo $this->arm_send_test_gmail_func($reply_to, $send_to, $subject, $message, array(), $reply_to_name, $arm_mail_server, $arm_mail_client_id, $arm_mail_client_secret, $arm_mail_client_token, $arm_mail_auth_url); //phpcs:ignore
                echo $arm_ajax_pattern_end;
            }
            else
            {
	    	$mail_authentication = (isset($_POST['mail_authentication'])) ? intval($_POST['mail_authentication']) : '1';//phpcs:ignore
                $arm_mail_port = (isset($_POST['mail_port']) && !empty($_POST['mail_port'])) ? intval($_POST['mail_port']) : '';//phpcs:ignore
                $arm_mail_login_name = (isset($_POST['mail_login_name']) && !empty($_POST['mail_login_name'])) ? sanitize_text_field($_POST['mail_login_name']) : '';//phpcs:ignore
                $arm_mail_password = (isset($_POST['mail_password']) && !empty($_POST['mail_password'])) ? $_POST['mail_password'] : ''; //phpcs:ignore
                $arm_mail_enc = (isset($_POST['mail_enc']) && !empty($_POST['mail_enc'])) ? sanitize_text_field($_POST['mail_enc']) : '';//phpcs:ignore
    
                if (empty($send_to) || empty($reply_to) || empty($message) || empty($subject)) {
                    return;
                }
                echo $arm_ajax_pattern_start;
                echo $this->arm_send_tedst_mail_func($reply_to, $send_to, $subject, $message, array(), $reply_to_name, $arm_mail_server, $arm_mail_port, $arm_mail_login_name, $arm_mail_password, $arm_mail_enc, $mail_authentication); //phpcs:ignore
                echo $arm_ajax_pattern_end;
            }
            die();
        }

        function arm_validate_test_gmail(){
            global $ARMember, $arm_capabilities_global,$arm_email_settings;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce

            $arm_gmail_client_id = (isset($_POST['client_id']) && !empty($_POST['client_id'])) ? sanitize_text_field($_POST['client_id']) : '';//phpcs:ignore

            $arm_gmail_secret = (isset($_POST['client_secret']) && !empty($_POST['client_secret'])) ? sanitize_text_field($_POST['client_secret']) : '';//phpcs:ignore
            
            $arm_gmail_auth_url = (isset($_POST['auth_url']) && !empty($_POST['auth_url'])) ? sanitize_text_field($_POST['auth_url']) : '';//phpcs:ignore
            
            $arm_gmail_auth_token = (isset($_POST['auth_token']) && !empty($_POST['auth_token'])) ? sanitize_text_field($_POST['auth_token']) : '';//phpcs:ignore

            $arm_gmail_validate_url = "https://accounts.google.com/o/oauth2/auth";

            $arm_gmailapi_redirect_uri = get_home_url().'?page=arm_gmailapi'; 
            $state = base64_encode( 'action:gmail_oauth' );
			
			$email_settings = $arm_email_settings->arm_get_all_email_settings();

			$email_settings['arm_google_client_id'] = $arm_gmail_client_id;
			$email_settings['arm_google_auth_url'] = $arm_gmailapi_redirect_uri;
			$email_settings['arm_google_client_secret'] = $arm_gmail_secret;
			
			update_option('arm_email_settings',$email_settings);

            $oauth_url = $arm_gmail_validate_url . '?response_type=code&access_type=offline&client_id='.$arm_gmail_client_id.'&redirect_uri='.urlencode( $arm_gmailapi_redirect_uri).'&state='. $state.'&scope=https://mail.google.com/&approval_prompt=force&include_granted_scopes=false';

            $return = array('status' => 'success', 'type' => 'redirect', 'message' => $oauth_url);
            echo $oauth_url;//phpcs:ignore
            exit;

        }

        public function arm_validate_api()
        {
            if( isset( $_REQUEST['page'] ) && 'arm_gmailapi' == $_REQUEST['page'] ){
                global $ARMember, $arm_capabilities_global;
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1');        
                if( empty( $_REQUEST['state'] ) ){
                    
                    echo "<script type='text/javascript' data-cfasync='false'>";
                    echo "let url = document.URL;";
                    echo "if( /\#state/.test( url ) ){";
                        echo "url = url.replace( /\#state/, '&state' );";
                        echo "window.location.href= url;";
                    echo "} else {";
                        echo "window.location.href='" . get_home_url() . "';";//phpcs:ignore
                    echo "}";
                    echo "</script>";
                } else {
                    global $wpdb , $arm_email_settings;

                    $state = base64_decode( sanitize_text_field($_GET['state']) );//phpcs:ignore
                    
                    if( preg_match( '/(gmail_oauth)/', $state ) ){
                        
                        require_once MEMBERSHIP_LIBRARY_DIR . "/gmail/vendor/autoload.php";
                        
                        $code = urldecode( sanitize_text_field($_GET['code']) );//phpcs:ignore

                        $email_settings = $arm_email_settings->arm_get_all_email_settings();

                        $arm_client_id = $email_settings['arm_google_client_id'];
                        $arm_client_secret = $email_settings['arm_google_client_secret'];
                        $arm_redirect_url = get_home_url() .'?page=arm_gmailapi';

                        $client = new Google_Client();
                        $client->setClientId($arm_client_id);
                        $client->setClientSecret( $arm_client_secret );
                        $client->setRedirectUri( $arm_redirect_url);
                        $client->setAccessType( 'offline' );
                        $access_token='';

                        $response_data  = $client->authenticate( $code );
                        if( !empty($response_data)){
                            if( isset($response_data['access_token']) && $response_data['access_token'] != '' ){

                                $access_token = $response_data['access_token'];
                            }
                        }

                        $client->setAccessToken( $response_data );

                        $service = new Google\Service\Gmail( $client );  

                        try {
                            $email = $service->users->getProfile( 'me' )->getEmailAddress();

                            $email_settings['arm_email_server'] = 'google_gmail';
                            $email_settings['arm_google_auth_token'] = $access_token;
                            $email_settings['arm_google_connected_account'] = $email;
                            $email_settings['arm_google_auth_response'] = json_encode($response_data);
                            $email_settings['arm_gmail_verified_status'] = '1';
                            update_option('arm_email_settings',$email_settings);
                            echo "<script type='text/javascript' data-cfasync='false'>
                                window.close();
				window.opener.document.getElementById('arm_google_auth_response').value = '".json_encode($response_data)."';
                                window.opener.document.getElementById('arm_google_auth_token').value = '".esc_attr($access_token)."';
                                window.opener.document.getElementById('arm_gmail_verified_status').value = '1';
                                window.opener.document.getElementById(arm_google_auth_url).value = '".esc_attr($arm_redirect_url)."';
                                window.opener.document.getElementById('arm_google_connected_account').value = '".esc_attr($email)."';
                                window.opener.document.getElementById('arm_google_gmail_api_verify').style.display = '';
                                window.opener.document.getElementById('arm_google_gmail_api_verify').innerHTML('".esc_html__('Connected with Gmail API','ARMember')."');</script>";
                        } catch ( \Exception $e ) {
                            $email = '';
                            echo "<script type='text/javascript' data-cfasync='false'>window.close();
                                window.opener.document.getElementById('arm_google_auth_token').value = '';
                                window.opener.document.getElementById('arm_gmail_verified_status').value = '';
                                window.opener.document.getElementById(arm_google_auth_url).value = '';
                                window.opener.document.getElementById('arm_google_connected_account').value = '';
                                window.opener.document.getElementById('arm_google_auth_response').value = '';
                                window.opener.document.getElementById('arm_google_gmail_api_verify').style.display = 'none';
                                window.opener.document.getElementById('arm_google_gmail_api_error').style.display = 'block';
                                window.opener.document.getElementById('arm_google_gmail_api_error').innerHTML('".$e."');</script>"; //phpcs:ignore 
                        }
                        
                    }
                }
                die();
            }
        }

        public function arm_send_tedst_mail_func($from, $recipient, $subject, $message, $attachments = array(), $reply_to_name = '', $arm_mail_server = '', $arm_mail_port = '', $arm_mail_login_name = '', $arm_mail_password = '', $arm_mail_enc = '', $mail_authentication = '1') {
            global $wp, $wpdb, $ARMember, $arm_slugs, $arm_email_settings, $arm_plain_text, $wp_version,$arm_capabilities_global;
            $return = false;
            $reply_to_name = ($reply_to_name == '') ? wp_specialchars_decode(get_option('blogname'), ENT_QUOTES) : $reply_to_name;
            $use_only_smtp_settings = false;
            $emailSettings = $arm_email_settings->arm_get_all_email_settings();
            $email_server = 'smtp_server';
            $reply_to_name = ($reply_to_name == '') ? wp_specialchars_decode(get_option('blogname'), ENT_QUOTES) : $reply_to_name;
            $reply_to = ($from == '' or $from == '[admin_email]') ? get_option('admin_email') : $from;
            $from_name = (!empty($emailSettings['arm_email_from_name'])) ? $emailSettings['arm_email_from_name'] : wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
            $from_email = (!empty($emailSettings['arm_email_from_email'])) ? $emailSettings['arm_email_from_email'] : get_option('admin_email');
            $content_type = (@$arm_plain_text) ? 'text/plain' : 'text/html';
            $from_name = $from_name;
            $reply_to = (!empty($from)) ? $from : $from_email;
            /* Set Email Headers */
            $headers = array();
            $header[] = 'From: "' . $reply_to_name . '" <' . $reply_to . '>';
            $header[] = 'Reply-To: ' . $reply_to;
            $headers[] = 'Content-Type: ' . $content_type . '; charset="' . get_option('blog_charset') . '"';
            /* Filter Email Subject & Message */
            $subject = wp_specialchars_decode(strip_tags(stripslashes($subject)), ENT_QUOTES);
            $message = do_shortcode($message);
            $message = wordwrap(stripslashes($message), 70, "\r\n");
            if (@$arm_plain_text) {
                $message = wp_specialchars_decode(strip_tags($message), ENT_QUOTES);
            }

            $subject = apply_filters('arm_email_subject', $subject);
            $message = apply_filters('arm_change_email_content', $message);
            $recipient = apply_filters('arm_email_recipients', $recipient);
            $headers = apply_filters('arm_email_header', $headers, $recipient, $subject);
            remove_filter('wp_mail_from', 'bp_core_email_from_address_filter');
            remove_filter('wp_mail_from_name', 'bp_core_email_from_name_filter');
            
            if( version_compare( $wp_version, '5.5', '<' ) ){
                require_once ABSPATH . WPINC . '/class-phpmailer.php';
                require_once ABSPATH . WPINC . '/class-smtp.php';
                $armPMailer = new PHPMailer();
            } else {
                require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                $armPMailer = new PHPMailer\PHPMailer\PHPMailer();
            }
            
            do_action('arm_before_send_email_notification', $from, $recipient, $subject, $message, $attachments);
            /* Character Set of the message. */
            $armPMailer->CharSet = "UTF-8";

            $armPMailer->SMTPDebug = 1;
            ob_start();
            echo '<span class="arm_smtp_debug_title">';
            echo addslashes(esc_html__('The SMTP debugging output is shown below:', 'ARMember')); //phpcs:ignore
            echo '</span><pre>';
            /* $armPMailer->Debugoutput = 'html'; */

            if ($email_server == 'smtp_server') {
                $armPMailer->isSMTP();
                $armPMailer->Host = isset($arm_mail_server) ? $arm_mail_server : '';
                $armPMailer->SMTPAuth = ($mail_authentication==1) ? true : false;
                $armPMailer->Username = isset($arm_mail_login_name) ? $arm_mail_login_name : '';
                $armPMailer->Password = isset($arm_mail_password) ? $arm_mail_password : '';
                if (isset($arm_mail_enc) && !empty($arm_mail_enc) && $arm_mail_enc != 'none') {
                    $armPMailer->SMTPSecure = $arm_mail_enc;
                }
                if( $arm_mail_enc == 'none' ){
                    $armPMailer->SMTPAutoTLS = false;
                }
                $armPMailer->Port = isset($arm_mail_port) ? $arm_mail_port : '';
            } else {
                $armPMailer->isMail();
            }

            $armPMailer->setFrom($reply_to, $reply_to_name);
            $armPMailer->addReplyTo($reply_to, $reply_to_name);
            $armPMailer->addAddress($recipient);
            if (isset($attachments) && !empty($attachments)) {
                foreach ($attachments as $attachment) {
                    $armPMailer->addAttachment($attachment);
                }
            }
            $armPMailer->isHTML(true);
            $armPMailer->Subject = $subject;
            $armPMailer->Body = $message;
            if (@$arm_plain_text) {
                $armPMailer->AltBody = $message;
            }
            /* Send Email */            

            $arm_email_content  = '';            
            if ($email_server == 'smtp_server' || $email_server == 'phpmailer') {


                if (!$armPMailer->send()) {

                    echo '</pre><span class="arm_smtp_debug_title">';
                    echo addslashes(esc_html__('The full debugging output is shown below:', 'ARMember')); //phpcs:ignore
                    echo '</span>';
                    var_dump($armPMailer);
                    $smtp_debug_log = ob_get_clean();

                    $popup = '<div id="arm_smtp_debug_notices" class="popup_wrapper smtp_debug_notices" style="width:1000px;"><div class="popup_wrapper_inner">';
                    $popup .= '<div class="popup_header" >';
                    $popup .= '<span class="popup_close_btn arm_popup_close_btn"></span>';
                    $popup .= '<span class="popup_header_text">SMTP Test Full Log</span>';
                    $popup .= '</div>';
                    $popup .= '<div class="popup_content_text"><pre>' . $smtp_debug_log . '</pre></div>';
                    $popup .= '<div class="armclear"></div>';
                    $popup .= '</div></div>';

                    echo json_encode(
                    array(
                        'success' => 'false',
                        'msg' => $armPMailer->ErrorInfo.'<span class="arm_error_full_log_container"><a class="" id="smtp_debug_notices_link" href="javascript:void(0)" >'.esc_html__('Check Full Log','ARMember').'</a></span>',
                        'log' => $popup
                        )
                    );
                    $return = false;

                } else {
                    $smtp_debug_log = ob_get_clean();
                    echo json_encode(array('success' => 'true', 'msg' => ''));                    
                    $return = true;
                }
            } else {
                if (!wp_mail($recipient, $subject, $message, $header, $attachments)) {

                    if (!$armPMailer->send()) {

                        $return  = false;
                    } else {

                        $return = true;
                    }
                } else {

                    $return = true;
                }                
            }
	    
            $is_mail_send = ($return == true ) ? 'Yes' : 'No';
            $arm_email_content .= 'Email Sent Successfully: '.$is_mail_send.', To Email: '.$recipient.', From Email: '.$reply_to.'{ARMNL}';
            $arm_email_content .= 'Subject: '.$subject.'{ARMNL}';
            $arm_email_content .= 'Content: {ARMNL}'.$message.'{ARMNL}';
            do_action('arm_general_log_entry','email','send test email detail','armember', $arm_email_content);
            if ($email_server != 'smtp_server' && $email_server != 'phpmailer') {
                return $return;           
            }
        }

        public function arm_send_test_gmail_func($from, $recipient, $subject, $message, $attachments = array(), $reply_to_name='',$arm_mail_server = '', $arm_mail_client_id='', $arm_mail_client_secret='', $arm_mail_client_token='', $arm_mail_auth_url='')
        {
            //Google_send funtion test

            $is_mail_sent     = 0;
            $return_error_msg = esc_html__('Gmail Test Email cannot sent successfully', 'ARMember');
            $return_error_log = '';

            require_once MEMBERSHIP_LIBRARY_DIR . "/gmail/vendor/autoload.php";

            $arm_gmailapi_redirect_uri = !empty($arm_mail_auth_url) ? $arm_mail_auth_url : '';
            $email_settings = maybe_unserialize(get_option('arm_email_settings'));
            $gmail_oauth_data = $email_settings['arm_google_auth_response'];

            $arm_gmail_auth = stripslashes_deep( $gmail_oauth_data );
            $gmail_oauth_data = json_decode( $arm_gmail_auth, true);
            try{

                $client = new Google_Client();
                $client->setClientId($arm_mail_client_id);
                $client->setClientSecret( $arm_mail_client_secret );
                $client->setRedirectUri( $arm_gmailapi_redirect_uri);
                $client->setAccessToken( $gmail_oauth_data );
    
                /** Refresh Google API Token */
    
                if( $client->isAccessTokenExpired() ){
                    $is_refreshed = $client->refreshToken( $gmail_oauth_data['refresh_token'] );
    
                    if( !empty( $is_refreshed['error'] ) ){
                        
                        $refreshed_token_err = $is_refreshed['error'];
                        do_action('arm_general_log_entry','email','send test email detail','armember', $refreshed_token_err);
                        $msg = esc_html__('Invalid Email Token','ARMember');
                        echo "<script> armToast(".$msg.", 'success');</script>"; //phpcs:ignore
                        return false;
                    }
                    $refresh_token = $gmail_oauth_data['refresh_token'];
    
                    if( empty( $gmail_oauth_data['refresh_token'] ) ){
                        $gmail_oauth_data['refresh_token'] = $refresh_token;
                    }
                    $client->setAccessToken( $gmail_oauth_data );
                    $email_settings = maybe_unserialize(get_option('arm_email_settings'));
                    $email_settings['arm_google_auth_token'] = $gmail_oauth_data;
                    update_option('arm_email_settings',$email_settings);
                } else {
                    $verify_token_url = 'https://www.googleapis.com/oauth2/v3/tokeninfo';
                    
                    $args = array(
                        'timeout' => false,
                        'method' => 'GET',
                        'body' => array(
                            'access_token' => $gmail_oauth_data['access_token']
                        )
                    );
                    $check_access_token = wp_remote_get( $verify_token_url, $args );
                    
                    if( is_wp_error( $check_access_token ) ){
                        return false;
                    }
    
                    $valid_access_token_code = wp_remote_retrieve_response_code( $check_access_token );
    
                    if( 200 != $valid_access_token_code ){
                        $validate_access_token = json_decode( wp_remote_retrieve_body( $check_access_token ), 1 );   
                        $msg = esc_html__('Invalid Email Token','ARMember');
                        echo "<script> armToast(".$msg.", 'success');</script>"; //phpcs:ignore
                        return false;
                    }
                    
                }
    
                $service = new Google\Service\Gmail( $client );
    
                $user = 'me';
                $subjectCharset = $charset = 'utf-8';
                $arm_gmail_sent_data = "From: {$reply_to_name} <{$from}> \r\n";
                $arm_gmail_sent_data .= "To: ".$recipient."\r\n";
                $arm_gmail_sent_data .= 'Subject: =?' . $subjectCharset . '?B?' . base64_encode($subject) . "?=\r\n";
                $arm_gmail_sent_data .= "MIME-Version: 1.0\r\n";
                $arm_gmail_sent_data .= "Content-Type: text/html; charset=utf-8\r\n";
                $arm_gmail_sent_data .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
                $arm_gmail_sent_data .= "".$message."\r\n";
    
                $mime = rtrim(strtr(base64_encode($arm_gmail_sent_data), '+/', '-_'), '=');
                $msg = new Google_Service_Gmail_Message();
                $msg->setRaw($mime);
    
                $return_error_msg = esc_html__('Something went wrong..','ARMember');
                try {
                    $message = $service->users_messages->send('me', $msg);
                    $is_mail_sent = true;
                    $return_error_msg = esc_html__('Mail sent successfully','ARMember');
                    $return_msg = array('success' => 'true', 'msg' => '');  
                    do_action('arm_general_log_entry','email','Send Test Email notification GMail success response','armember', $is_mail_sent);
                } catch (Exception $e) {
                    $is_mail_sent = false;
                    $return_error_msg = 'An error occurred: ' . $e->getMessage();
                    do_action('arm_general_log_entry','email','Send Test Email notification GMail error response','armember', json_encode( $e ));
                }
    
                $return_msg = array(
                    'success'  => $is_mail_sent,
                    'msg'     => $return_error_msg,
                );
    
                do_action('arm_general_log_entry','email','Test G-mail notification send response','armember', $return_msg);
    
            }
            catch(Exception $e)
            {
                $is_mail_sent = false;
                $return_error_msg = 'An error occurred: ' . $e->getMessage();
                $return_msg = array(
                    'success'  => $is_mail_sent,
                    'msg'     => $return_error_msg,
                );
    
            }
            echo wp_json_encode($return_msg);
           exit;
        }

        function arm_change_from_email($from_email) {
            global $arm_email_settings;
            $all_email_settings = $arm_email_settings->arm_get_all_email_settings();
            $from_email = (!empty($all_email_settings['arm_email_from_email'])) ? $all_email_settings['arm_email_from_email'] : get_option('admin_email');
            return $from_email;
        }

        function arm_change_from_name($from_name) {
            global $arm_email_settings;
            $all_email_settings = $arm_email_settings->arm_get_all_email_settings();
            $from_name = (!empty($all_email_settings['arm_email_from_name'])) ? $all_email_settings['arm_email_from_name'] : get_option('blogname');
            return $from_name;
        }

        /* ====================================/.Begin Rename WP-ADMIN Folder Settings./==================================== */

        function arm_updated_option($option, $old_value, $value) {
            global $wp, $wpdb, $wp_rewrite, $arm_errors, $arm_slugs, $ARMember;
            if (!empty($option) && $option == 'permalink_structure') {
                if (empty($value)) {
                    $rename_wp_admin = $this->global_settings['rename_wp_admin'];
                    if (!empty($rename_wp_admin) && $rename_wp_admin == 1) {
                        $all_settings = $this->arm_get_all_global_settings();
                        $all_settings['general_settings']['rename_wp_admin'] = 0;
                        $all_settings['general_settings']['temp_wp_admin_path'] = 'wp-admin';
                        $all_settings['general_settings']['new_wp_admin_path'] = 'wp-admin';
                        update_option('arm_global_settings', $all_settings);
                        if (function_exists('save_mod_rewrite_rules')) {
                            save_mod_rewrite_rules();
                        }
                    }
                }
            }
        }

        function arm_admin_notices() {
            global $wp, $wpdb, $wp_rewrite, $arm_errors, $arm_slugs, $ARMember;
            /*             * ====================/.Begin Display Admin Notices./====================* */
            $current_cookie = str_replace(SITECOOKIEPATH, '', ADMIN_COOKIE_PATH);
            /* For non-sudomain and with paths mu: */
            if (!$current_cookie) {
                $current_cookie = 'wp-admin';
            }
            if (!trim($this->global_settings['temp_wp_admin_path'], ' /') || trim($this->global_settings['temp_wp_admin_path'], ' /') == 'wp-admin') {
                $new_admin_path = 'wp-admin';
            } else {
                $new_admin_path = trim($this->global_settings['temp_wp_admin_path'], ' /');
            }


            if(isset($_GET['page']))
            {
                $_GET['page'] = isset($_GET['page']) ? sanitize_text_field($_GET['page'] ) : '';
            }

            global $current_screen, $pagenow, $arm_access_rules;
            $default_rule_link = admin_url('admin.php?page=' . $arm_slugs->access_rules );

            if ($current_screen->base == 'nav-menus' || $pagenow == 'nav-menus.php') {
                $default_access_rules = $arm_access_rules->arm_get_default_access_rules();
                $nav_rules = (isset($default_access_rules['nav_menu'])) ? $default_access_rules['nav_menu'] : '';
                if (!empty($nav_rules)) {
                    $warning_msg = '<div class="error arm_admin_notices_container" style="color: #F00;"><p>';
                    $warning_msg .= '<strong>' . esc_html__('ARMember Warning', 'ARMember') . ':</strong> ';
                    $warning_msg .= esc_html__('Please review', 'ARMember');
                    $warning_msg .= ' <a href="' . $default_rule_link . '"><strong>' . esc_html__('Content Access Rules', 'ARMember') . '</strong></a> ';
                    $warning_msg .= esc_html__('after adding new menu items. Default access rule will be applied to new menu items.', 'ARMember');
                    $warning_msg .= '</p></div>';
                    echo $warning_msg; //phpcs:ignore
                }
            }
            if ($current_screen->base == 'edit-tags' || $pagenow == 'edit-tags.php') {
                if (!isset($_REQUEST['tag_ID']) || empty($_REQUEST['tag_ID'])) {
                    $taxonomy = $current_screen->taxonomy;
                    $taxo_data = get_taxonomy($taxonomy);
                    $default_access_rules = $arm_access_rules->arm_get_default_access_rules();
                    if ($taxo_data->name == 'category') {
                        $taxo_rules = (isset($default_access_rules['category'])) ? $default_access_rules['category'] : '';
                        $taxo_data->label = esc_html__('category(s)', 'ARMember');
                    } else {
                        $taxo_rules = (isset($default_access_rules['taxonomy'])) ? $default_access_rules['taxonomy'] : '';
                        $taxo_data->label = esc_html__('custom taxonomy(s)', 'ARMember');
                    }
                    if (!empty($taxo_rules)) {
                        $warning_msg = '<div class="error arm_admin_notices_container" style="color: #F00;"><p>';
                        $warning_msg .= '<strong>' . esc_html__('ARMember Warning', 'ARMember') . ':</strong> ';
                        $warning_msg .= esc_html__('Please review', 'ARMember');
                        $warning_msg .= ' <a href="' . $default_rule_link . '"><strong>' . esc_html__('Access Rules', 'ARMember') . '</strong></a> ';
                        $warning_msg .= esc_html__('after adding new', 'ARMember') . ' ' . $taxo_data->label . '. ';
                        $warning_msg .= esc_html__('Default access rule will be applied to new', 'ARMember') . ' ' . $taxo_data->label . '. ';
                        $warning_msg .= '</p></div>';
                        echo $warning_msg; //phpcs:ignore
                    }
                }
            }
            /*             * ====================/.End Display Admin Notices./====================* */
        }

        function is_permalink() {
            global $wp_rewrite;
            if (!isset($wp_rewrite) || !is_object($wp_rewrite) || !$wp_rewrite->using_permalinks()) {
                return false;
            }
            return true;
        }

        function arm_mod_rewrite_rules($rules) {
            global $wp, $wpdb, $arm_errors, $ARMember;
            $home_root = parse_url(ARM_HOME_URL);
            if (isset($home_root['path'])) {
                $home_root = trailingslashit($home_root['path']);
            } else {
                $home_root = '/';
            }
            $rules = str_replace('(.*) ' . $home_root . '$1$2 ', '(.*) $1$2 ', $rules);
            return $rules;
        }

        function arm_wp_admin_rewrite_rules($wp_rewrite) {
            global $wp, $wpdb, $arm_errors, $ARMember;
            $rename_wp_admin = $this->global_settings['rename_wp_admin'];
            $new_non_wp_rules = array();
            if (!empty($rename_wp_admin) && $rename_wp_admin == 1) {
                $new_wp_admin_path = !empty($this->global_settings['new_wp_admin_path']) ? $this->global_settings['new_wp_admin_path'] : 'wp-admin';
                $new_wp_admin_path = (trim($new_wp_admin_path, ' /')) ? trim($new_wp_admin_path, ' /') : 'wp-admin';
                if ($new_wp_admin_path != 'wp-admin' && $this->is_permalink()) {
                    $rel_admin_path = $this->sub_folder . 'wp-admin';
                    $new_admin_path = trim($new_wp_admin_path, ' /');
                    $new_non_wp_rules[$new_admin_path . '/(.*)'] = $rel_admin_path . '/$1';
                }
                add_filter('mod_rewrite_rules', array($this, 'arm_mod_rewrite_rules'), 10, 1);
            }
            if (isset($new_non_wp_rules) && $this->is_permalink()) {
                $wp_rewrite->non_wp_rules = array_merge($wp_rewrite->non_wp_rules, $new_non_wp_rules);
            }
            return $wp_rewrite;
        }

        function arm_replace_admin_url($url, $path = '', $scheme = 'admin') {
            global $wp, $wpdb, $arm_errors, $ARMember;
            $rename_wp_admin = $this->global_settings['rename_wp_admin'];
            if (!empty($rename_wp_admin) && $rename_wp_admin == 1) {
                $new_wp_admin_path = !empty($this->global_settings['new_wp_admin_path']) ? $this->global_settings['new_wp_admin_path'] : 'wp-admin';
                $new_wp_admin_path = (trim($new_wp_admin_path, ' /')) ? trim($new_wp_admin_path, ' /') : 'wp-admin';
                /* Replace New Admin Path */
                if ($new_wp_admin_path != 'wp-admin' && $this->is_permalink()) {
                    $url = str_replace('wp-admin/', $new_wp_admin_path . '/', $url);
                }
            }
            return $url;
        }

        /* ====================================/.End Rename WP-ADMIN Folder Settings./==================================== */

        function arm_apply_global_settings() {
            global $wp, $wpdb, $wp_rewrite, $arm_errors, $current_user, $arm_slugs, $ARMember, $arm_members_class, $arm_restriction, $arm_member_forms;
            $all_settings = $this->global_settings;

            if (isset($_REQUEST['arm_wpdisable']) && !empty($_REQUEST['arm_wpdisable'])) {
                $arm_hide_wp_admin_option = get_option('arm_hide_wp_amin_disable');
                if ($arm_hide_wp_admin_option == $_REQUEST['arm_wpdisable']) {

                    $all_saved_global_settings = maybe_unserialize(get_option('arm_global_settings'));
                    $new_wp_admin_path = $all_saved_global_settings['general_settings']['new_wp_admin_path'];

                    $home_path = $this->arm_get_home_path();
                    $rewritecode = '';

                    if (!file_exists($home_path . '.htaccess') || !is_writable($home_path . '.htaccess') || !file_exists($home_path . 'wp-config.php') || !is_writable($home_path . 'wp-config.php')) {

                        $config_error = true;
                        $htaccess_notice = true;

                        $arm_rename_wp = new ARM_rename_wp();
                        $arm_rename_wp->enable_rename_wp = 0;
                        $arm_rename_wp->new_wp_admin_name = 'wp-admin';
                        $arm_rename_wp->arm_replace = array();
                        $arm_rename_wp->armBuildRedirect();
                        $rewrite_notice = '';

                        $rewrites = array();
                        if (!empty($arm_rename_wp->arm_replace)) {
                            foreach ($arm_rename_wp->arm_replace['to'] as $key => $replace) {
                                if ($arm_rename_wp->arm_replace['rewrite'][$key]) {
                                    $rewrites[] = array(
                                        'from' => $arm_rename_wp->arm_replace['to'][$key] . '(.*)',
                                        'to' => $arm_rename_wp->arm_replace['from'][$key] . '$' . (substr_count($arm_rename_wp->arm_replace['to'][$key], '(') + 1)
                                    );
                                }
                            }
                        }

                        foreach ($rewrites as $rewrite) {

                            if (strpos($rewrite['to'], 'index.php') === false) {
                                $rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $home_root . $rewrite['to'] . " [QSA,L]<br />";
                            }
                        }

                        $rewrite_notice .= '.htaccess file is not writable. You need to update your .htaccess file by deleting following line before \'RewriteCond %{REQUEST_FILENAME} !-f\': <br /><code>' . $rewritecode . '</code><br/>';

                        $rewritecode = "define('ADMIN_COOKIE_PATH','" . rtrim(wp_make_link_relative(network_site_url($new_wp_admin_path)), '/') . "');";
                        $rewrite_notice .= 'wp-config.php file is not writable. You need to update your wp-config.php file by deleting following line <code>' . $rewritecode . '</code>';

                    } else {

                        $htaccess_notice = false;
                        $config_error = false;
                        require_once ABSPATH . 'wp-admin/includes/misc.php';
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        $removeTag = $all_saved_global_settings['general_settings']['new_wp_admin_path'] . '/(.*)';
                        $wp_rewrite->remove_rewrite_tag($removeTag);
                        $rewrite_notice = '';
                        if (!function_exists('save_mod_rewrite_rules')) {
                            $htaccess_notice = true;
                            $rewritecode = "RewriteRule ^{$all_saved_global_settings['general_settings']['new_wp_admin_path']}/(.*) {$home_root}wp-admin/$1 [QSA,L]";
                            $rewrite_notice .= '.htaccess file is not writable. You need to update your .htaccess file by remove following line from your .htaccess file <br /><code>' . $rewritecode . '</code>';
                        } else {
                            if (!save_mod_rewrite_rules()) {
                                $htaccess_notice = true;
                                $rewritecode = "RewriteRule ^{$all_saved_global_settings['general_settings']['new_wp_admin_path']}/(.*) {$home_root}wp-admin/$1 [QSA,L]";
                                $rewrite_notice .= '.htaccess file is not writable. You need to update your .htaccess file by remove following line from your .htaccess file <br /><code>' . $rewritecode . '</code>';
                            }
                        }

                        if (!$this->remove_config_file()) {
                            $config_error = true;
                            $rewritecode = "define('ADMIN_COOKIE_PATH','".sanitize_text_field($_POST['arm_general_settings']['new_wp_admin_path'])."');";//phpcs:ignore
                            $rewrite_notice .= 'wp-config.php file is not writable. You need to update your wp-config.php file by removing following line <code>' . $rewritecode . '</code>';
                        }
                    }
                    $all_saved_global_settings['general_settings']['rename_wp_admin'] = 0;
                            $all_saved_global_settings['general_settings']['new_wp_admin_path'] = 'wp-admin';
                            $all_saved_global_settings['general_settings']['temp_wp_admin_path'] = 'wp-admin';
                            update_option('arm_global_settings', $all_saved_global_settings);

                        if ($htaccess_notice == true || $config_error == true) {
                            wp_die($rewrite_notice); //phpcs:ignore
                        } else {
                           
                            wp_destroy_current_session();
                            wp_clear_auth_cookie();
                            $success_msg = sprintf('<b>'.esc_html__('Rename wp-admin folder','ARMember').'</b>' .esc_html__('setting is revereted. Now you can access admin panel with /wp-admin. Return to', 'ARMember'));

                            $success_msg .= ' <a href="'.ARM_HOME_URL.'">'.esc_html__('Home Page', 'ARMember').'</a>';

                            wp_die($success_msg); //phpcs:ignore
                            exit;
                        }
                    
                }
            }
            /* Hide admin bar for non-admin users. */
            $allow_access_admin_roles = array();
            $hide_admin_bar = isset($all_settings['hide_admin_bar']) ? $all_settings['hide_admin_bar'] : 0;
            if ($hide_admin_bar == 1) {
                if(isset($all_settings['arm_exclude_role_for_hide_admin']) && is_array($all_settings['arm_exclude_role_for_hide_admin']))
                {
                    $allow_access_admin_roles = $all_settings['arm_exclude_role_for_hide_admin'];
                } else {

                    $allow_access_admin_roles = (isset($all_settings['arm_exclude_role_for_hide_admin']) && !empty($all_settings['arm_exclude_role_for_hide_admin'])) ? explode(',', $all_settings['arm_exclude_role_for_hide_admin']) : array(); 
                }
                $user_match_role = array_intersect($current_user->roles, $allow_access_admin_roles);
                if(empty($user_match_role)) {
                    if (!is_admin() && !current_user_can('administrator')) {
                        remove_all_filters('show_admin_bar');
                        add_filter('show_admin_bar', '__return_false');
                    }
                }
                
            }/* End `($hide_admin_bar == 1)` */
            /* New User Verification */
            $user_register_verification = isset($all_settings['user_register_verification']) ? sanitize_text_field( $all_settings['user_register_verification'] ) : 'auto';
            if ($user_register_verification != 'auto') {
                add_action('user_register', array($arm_members_class, 'arm_add_member_activation_key'));
            }
            /* Verify Member Detail Before Login */
            if(!is_admin())
            {
                add_filter('authenticate', array(&$arm_members_class, 'arm_user_register_verification'), 10, 3);
            }
            /**
             * Load Google Fonts for TinyMCE Editor
             */
        }

        function arm_get_home_path() {
            $home = get_option('home');
            $siteurl = get_option('siteurl');
            if (!empty($home) && 0 !== strcasecmp($home, $siteurl)) {
                $wp_path_rel_to_home = str_ireplace($home, '', $siteurl); /* $siteurl - $home */
		$script_filename     = !empty( $_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';//phpcs:ignore
                $pos = strripos(str_replace('\\', '/', sanitize_text_field( $script_filename)), trailingslashit($wp_path_rel_to_home));//phpcs:ignore
                $home_path = substr( sanitize_text_field( $script_filename ), 0, $pos); //phpcs:ignore
                $home_path = trailingslashit($home_path);
            } else {
                $home_path = ABSPATH;
            }
            return $home_path;
        }

        function arm_check_member_status($return = true, $user_id = 0) {
            global $wp, $wpdb, $arm_errors, $ARMember, $arm_members_class, $arm_member_forms;
            if (!empty($user_id) && $user_id != 0) {
                if (is_super_admin($user_id)) {
                    return true;
                }
                $primary_status = arm_get_member_status($user_id);
                $secondary_status = arm_get_member_status($user_id, 'secondary');
                switch ($primary_status) {
                    case 'pending':
                    case 3:
                        $pending_msg = (!empty($this->common_message['arm_account_pending'])) ? $this->common_message['arm_account_pending'] : '<strong>' . esc_html__('Account Pending', 'ARMember') . '</strong>: ' . esc_html__('Your account is currently not active. An administrator needs to activate your account before you can login.', 'ARMember');
                        $return = $arm_errors;
                        /* Remove other filters when there is an error */
                        remove_all_filters('arm_check_member_status_before_login');
                        break;
                    case 'inactive':
                    case 2:
                        if(($primary_status == '2' && in_array($secondary_status, array(0,1))) || $primary_status == 4){
                            $err_msg = (!empty($this->common_message['arm_account_inactive'])) ? $this->common_message['arm_account_inactive'] : '<strong>' . esc_html__('Account Inactive', 'ARMember') . '</strong>: ' . esc_html__('Your account is currently not active. Please contact the system administrator.', 'ARMember');
                            $arm_errors->add('access_denied', $err_msg);
                        }
                        $return = $arm_errors;
                            /* Remove other filters when there is an error */
                            remove_all_filters('arm_check_member_status_before_login');
                        break;
                    case 'active':
                    case 1:
                        $return = TRUE;
                        break;
                    default:
                        $return = TRUE;
                        break;
                }
            } else {
                $return = FALSE;
            }
            return $return;
        }

        function arm_check_ip_block_before_login() {
            global $wp, $wpdb, $arm_errors, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings;
            
            $general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
            $disable_wp_login_style = isset($general_settings['disable_wp_login_style']) ? $general_settings['disable_wp_login_style'] : 0;
            if($disable_wp_login_style == 0)
            {
                /* Remove Label For Custom Style. */
                add_filter('gettext', array($this, 'remove_loginpage_label_text'), 50);
                add_filter( 'login_headerurl', array($this, 'arm_wp_login_logo_url'), 50);
            }
            $block_list = $this->block_settings;
            /* Get Visitor's IP Address. */
            $currentr_ip = $ARMember->arm_get_ip_address();
	    $arm_block_ips = isset($block_list['arm_block_ips']) ? $block_list['arm_block_ips'] : array();
            $arm_block_ips = apply_filters('arm_restrict_user_before_login', $arm_block_ips);
            $block_ips_msg = (!empty($block_list['arm_block_ips_msg'])) ? $block_list['arm_block_ips_msg'] : '<strong>' . esc_html__('Blocked', 'ARMember') . ': </strong>' . esc_html__('Your IP has been blocked.', 'ARMember');

            if (!empty($arm_block_ips) && in_array($currentr_ip, $arm_block_ips)) {
                $arm_errors->add('blocked_ip', $block_ips_msg);
                login_header('', '', $arm_errors);
                echo '</div>';
                do_action('login_footer');
                echo '</body></html>';
                exit;
            }
            wp_enqueue_script('jquery');
        }

        function arm_global_settings_notices($notices = array()) {
            global $wp, $wpdb, $arm_errors, $ARMember, $arm_slugs, $arm_social_feature;
            $default_global_settings = $this->arm_default_global_settings();
            $default_page_settings = $default_global_settings['page_settings'];
            $page_settings = $this->arm_get_single_global_settings('page_settings');
            $final_page_settings = shortcode_atts($default_page_settings, $page_settings);
            if (!empty($final_page_settings)) {
                $empty_pages = array();
                foreach ($final_page_settings as $key => $page_id) {
                    if (in_array($key, array('logout_page_id', 'guest_page_id', 'thank_you_page_id', 'cancel_payment_page_id'))) {
                        continue;
                    }
                    if ($key == 'member_profile_page_id' && !$arm_social_feature->isSocialFeature) {
                        continue;
                    }
                    if (empty($page_id) || $page_id == 0) {
                        $name = str_replace('_page_id', '', $key);
                        $name = str_replace('_', ' ', $name);
                        $name = ucfirst($name);
                        $empty_pages[] = $name;
                    }
                }
                if (!empty($empty_pages)) {
                    $empty_pages = trim(implode(', ', $empty_pages), ', ');
                    $page_settings_url = admin_url('admin.php?page=' . $arm_slugs->general_settings . '&action=page_setup');
                    $notices[] = array('type' => 'error', 'message' => esc_html__('You need to set', 'ARMember') . ' <b>\'' . $empty_pages . '\'</b> ' . esc_html__('page(s) in', 'ARMember') . ' <a href="' . $page_settings_url . '">' . esc_html__('page settings', 'ARMember') . '</a>');
                }
            }
            return $notices;
        }

        function arm_get_default_invoice_template(){
            $arm_default_invoice_template = '<div id="arm_invoice_div" class="entry-content ms-invoice">';
            $arm_default_invoice_template .= '<style>';
            $arm_default_invoice_template .= '#arm_invoice_div table, th, td { margin: 0; font-size: 14px; }';
            $arm_default_invoice_template .= '#arm_invoice_div table { padding: 0; border: 1px solid #DDD; width: 100%; background-color: #FFF; box-shadow: 0 1px 8px #F0F0F0; }';
            $arm_default_invoice_template .= '#arm_invoice_div th, td { border: 0; padding: 8px; }';
            $arm_default_invoice_template .= '#arm_invoice_div th { font-weight: bold; text-align: left; text-transform: none; font-size: 13px; }';
            $arm_default_invoice_template .= '#arm_invoice_div tr.alt { background-color: #F9F9F9; }';
            $arm_default_invoice_template .= '#arm_invoice_div tr.sep th, #arm_invoice_div tr.sep td { border-top: 1px solid #DDD; padding-top: 16px; }';
            $arm_default_invoice_template .= '#arm_invoice_div tr.space th, #arm_invoice_div tr.space td { padding-bottom: 16px; }';
            $arm_default_invoice_template .= '#arm_invoice_div tr.ms-inv-sep th,#arm_invoice_div tr.ms-inv-sep td { line-height: 1px; height: 1px; padding: 0; border-bottom: 1px solid #DDD; background-color: #F9F9F9; }';
            $arm_default_invoice_template .= '#arm_invoice_div .ms-inv-total .ms-inv-price { font-weight: bold; font-size: 18px; text-align: right; }';
            $arm_default_invoice_template .= '#arm_invoice_div h2 { text-align: right; padding: 0 10px 0 0;margin:0 auto;}';
            $arm_default_invoice_template .= '#arm_invoice_div h2 a { color: #000; }';             
            $arm_default_invoice_template .= '</style>';
	    $arm_default_invoice_template .= '<div class="ms-invoice-details ms-status-paid">';
                                        $arm_default_invoice_template .= '<table class="ms-purchase-table" cellspacing="0">';
                                            $arm_default_invoice_template .= '<tbody>';
                                                $arm_default_invoice_template .= '<tr class="ms-inv-title">';
                                                    $arm_default_invoice_template .= '<td colspan="2" align="right">';
                                                    $arm_default_invoice_template .= '<h2>Invoice {ARM_INVOICE_INVOICEID}</h2>';
                                                    $arm_default_invoice_template .= '<div style="text-align: right; padding: 0px 10px 10px 0px;">{ARM_INVOICE_PAYMENTDATE}</div>';
                                                $arm_default_invoice_template .= '</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                             
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-to alt space sep">';
                                                    $arm_default_invoice_template .= '<th>Invoice to</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-text">{ARM_INVOICE_USERFIRSTNAME} {ARM_INVOICE_USERLASTNAME} ( {ARM_INVOICE_PAYEREMAIL} )</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                          
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-item-name space">';
                                                    $arm_default_invoice_template .= '<th>Plan Name</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-text">{ARM_INVOICE_SUBSCRIPTIONNAME}</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-description alt space">';
                                                    $arm_default_invoice_template .= '<th>Description</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-text">{ARM_INVOICE_SUBSCRIPTIONDESCRIPTION}</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount space">';
                                                    $arm_default_invoice_template .= '<th>Plan Amount</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_AMOUNT}</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                                
                                               
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount alt space">';
                                                    $arm_default_invoice_template .= '<th>transaction Id</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_TRANSACTIONID}</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount space">';
                                                    $arm_default_invoice_template .= '<th>subscription id</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_SUBSCRIPTIONID}</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount space alt">';
                                                    $arm_default_invoice_template .= '<th>payment gateway</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_GATEWAY}</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                                                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount space">';
                                                    $arm_default_invoice_template .= '<th>trial amount</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_TRIALAMOUNT}</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount space alt">';
                                                    $arm_default_invoice_template .= '<th>trial period</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_TRIALPERIOD}</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount space">';
                                                    $arm_default_invoice_template .= '<th>coupon code</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_COUPONCODE}</td>';
                                                $arm_default_invoice_template .= '</tr>';
                                                
                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount alt space">';
                                                    $arm_default_invoice_template .= '<th>coupon discount</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_COUPONAMOUNT}</td>';
                                                $arm_default_invoice_template .= '</tr>';

                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount space">';
                                                    $arm_default_invoice_template .= '<th>Tax Percentage</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_TAXPERCENTAGE}</td>';
                                                $arm_default_invoice_template .= '</tr>';

                                                $arm_default_invoice_template .= '<tr class="ms-inv-amount alt space">';
                                                    $arm_default_invoice_template .= '<th>Tax Amount</th>';
                                                    $arm_default_invoice_template .= '<td class="ms-inv-price">{ARM_INVOICE_TAXAMOUNT}</td>';
                                                $arm_default_invoice_template .= '</tr>';

                                               
                                                
                                                $arm_default_invoice_template .= '</tbody>';
                                            $arm_default_invoice_template .= '</table>';
                                       $arm_default_invoice_template .= '</div>';
                                    $arm_default_invoice_template .= '</div>';

                                    return $arm_default_invoice_template;
        }

        function arm_default_global_settings() {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_pay_per_post_feature, $arm_pro_ration_feature;
            $default_global_settings = array();
            /* General Settings */
            
            
            $arm_default_invoice_template = $this->arm_get_default_invoice_template();
            
            $default_global_settings['general_settings'] = array(
                'hide_admin_bar' => 0,
                'arm_exclude_role_for_hide_admin' => 0,
                'restrict_admin_panel' => 0,
                'arm_exclude_role_for_restrict_admin' => 0,
                'hide_wp_login' => 0,
                'rename_wp_admin' => 0,
                'temp_wp_admin_path' => '',
                'new_wp_admin_path' => 'wp-admin',
                'hide_register_link' => 0,
                'user_register_verification' => 'auto',
                'arm_new_signup_status' => 1,
                'hide_feed' => 0,
                'disable_wp_login_style' => 0,
                'restrict_site_access' => 0,
                'arm_access_page_for_restrict_site' => 0,
                'autolock_shared_account' => 0,
                'paymentcurrency' => 'USD',
                'arm_specific_currency_position' => 'suffix',
                'custom_currency' => array(
                    'status' => 0,
                    'symbol' => '',
                    'shortname' => '',
                    'place' => 'prefix',
                ),
                'arm_currency_decimal_digit'=>2,
                'arm_anonymous_data' => 0,
                'enable_tax' => 0,
                'tax_type' => 'common_tax',
                'tax_amount' => 0,
                'country_tax_field' => '',
                'arm_tax_include_exclude_flag' => 0,
                "arm_tax_country_name" => '',
                "arm_country_tax_val" => 0,
                "arm_country_tax_default_val" => 0,
                "invc_pre_sfx_mode" => 0,
                "invc_prefix_val" => '#',
                "invc_suffix_val" => '',
                "invc_min_digit" => 0,
                'file_upload_size_limit' => '2',
                'enable_gravatar' => 1,
                'enable_crop' => 1,
                'spam_protection'=> 1,
                'enqueue_all_js_css' => 0,
                'global_custom_css' => '',
                'badge_width' => 30,
                'badge_height' => 30,
                'profile_permalink_base' => 'user_login',
                'bbpress_profile_page' => 0,
                'arm_email_schedular_time' => 12,
                'arm_invoice_template' => $arm_default_invoice_template,
                'arm_recaptcha_site_key' => '',
                'arm_recaptcha_private_key' => '',
                'arm_recaptcha_theme' => '',
                'arm_recaptcha_lang' => '',
                'front_settings' => array(
                    'level_1_font' => array(
                        'font_family' => 'Open Sans',
                        'font_size' => '18',
                        'font_color' => '#32323a',
                        'font_bold' => 1,
                        'font_italic' => 0,
                        'font_decoration' => '',
                    ),
                    'level_2_font' => array(
                        'font_family' => 'Open Sans',
                        'font_size' => '16',
                        'font_color' => '#32323a',
                        'font_bold' => 1,
                        'font_italic' => 0,
                        'font_decoration' => '',
                    ),
                    'level_3_font' => array(
                        'font_family' => 'Open Sans',
                        'font_size' => '15',
                        'font_color' => '#727277',
                        'font_bold' => 0,
                        'font_italic' => 0,
                        'font_decoration' => '',
                    ),
                    'level_4_font' => array(
                        'font_family' => 'Open Sans',
                        'font_size' => '14',
                        'font_color' => '#727277',
                        'font_bold' => 0,
                        'font_italic' => 0,
                        'font_decoration' => '',
                    ),
                    'link_font' => array(
                        'font_family' => 'Open Sans',
                        'font_size' => '14',
                        'font_color' => '#0c7cd5',
                        'font_bold' => 0,
                        'font_italic' => 0,
                        'font_decoration' => '',
                    ),
                    'button_font' => array(
                        'font_family' => 'Open Sans',
                        'font_size' => '14',
                        'font_color' => '#FFFFFF',
                        'font_bold' => 0,
                        'font_italic' => 0,
                        'font_decoration' => '',
                    ),
                ),
                'arm_pay_per_post_buynow_var' => '',
                'arm_pay_per_post_allow_fancy_url' => '',
                'arm_pay_per_post_default_content' => '',
                'arm_pro_ration_method' => 'cost_base',
                'arm_enable_reset_billing' => 0,
            );
            /* Page Settings */
            $default_global_settings['page_settings'] = array(
                'register_page_id' => 0,
                'login_page_id' => 0,
                'forgot_password_page_id' => 0,
                'edit_profile_page_id' => 0,
                'change_password_page_id' => 0,
                'member_profile_page_id' => 0,
                'logout_page_id' => 0,
                'guest_page_id' => 0,
                'thank_you_page_id' => 0,
                'cancel_payment_page_id' => 0,
            );
            if(!empty($arm_pay_per_post_feature->isPayPerPostFeature)){
                $default_global_settings['page_settings']['paid_post_page_id'] = 0;
            }


            if(!empty($arm_api_service_feature->isAPIServiceFeature)){
                $default_global_settings['api_service'] = array(
                    'arm_api_service_security_key' => '',
                    'arm_list_membership_plans' => 0,
                    'arm_membership_plan_details' => 0,
                    'arm_member_details' => 0,
                    'arm_member_memberships' => 0,
                    'arm_member_paid_posts' => 0,
                    'arm_member_payments' => 0,
                    'arm_member_paid_post_payments' => 0,
                    'arm_check_coupon_code' => 0,
                    'arm_member_add_membership' => 0,
                    'arm_member_cancel_membership' => 0,
                    'arm_check_member_membership' => 0,
                    'arm_create_transaction' => 0,
                );
            }
            
            
            $default_global_settings = apply_filters('arm_default_global_settings', $default_global_settings);
            return $default_global_settings;
        }

        function arm_default_pages_content() {
            global $wpdb, $ARMember, $arm_members_class, $arm_slugs, $arm_member_forms;
            $default_rf_id = $arm_member_forms->arm_get_default_form_id('registration');
            $default_lf_id = $arm_member_forms->arm_get_default_form_id('login');
            $default_ff_id = $arm_member_forms->arm_get_default_form_id('forgot_password');
            $default_cf_id = $arm_member_forms->arm_get_default_form_id('change_password');
            $default_ep_id = $arm_member_forms->arm_get_default_form_id('edit_profile');
            $logged_in_message = esc_html__('You are already logged in.', 'ARMember');
            $all_pages = array(
                'register_page_id' => array(
                    'post_title' => 'Register',
                    'post_name' => 'register',
                    'post_content' => '[arm_form id="' . $default_rf_id . '" logged_in_message="' . $logged_in_message . '"]',
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    'post_author' => 1,
                    'post_type' => 'page',
                ),
                'login_page_id' => array(
                    'post_title' => 'Login',
                    'post_name' => 'login',
                    'post_content' => '[arm_form id="' . $default_lf_id . '" logged_in_message="' . $logged_in_message . '"]',
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    'post_author' => 1,
                    'post_type' => 'page',
                ),
                'forgot_password_page_id' => array(
                    'post_title' => 'Forgot Password',
                    'post_name' => 'forgot_password',
                    'post_content' => '[arm_form id="' . $default_ff_id . '" logged_in_message="' . $logged_in_message . '"]',
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    'post_author' => 1,
                    'post_type' => 'page',
                ),
                'edit_profile_page_id' => array(
                    'post_title' => 'Edit Profile',
                    'post_name' => 'edit_profile',
                    'post_content' => '[arm_profile_detail id="' . $default_ep_id . '"]',
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    'post_author' => 1,
                    'post_type' => 'page',
                ),
                'change_password_page_id' => array(
                    'post_title' => 'Change Password',
                    'post_name' => 'change_password',
                    'post_content' => '[arm_form id="' . $default_cf_id . '"]',
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    'post_author' => 1,
                    'post_type' => 'page',
                ),
                'guest_page_id' => array(
                    'post_title' => 'Guest',
                    'post_name' => 'guest',
                    'post_content' => '<h3>' . esc_html__('Welcome Guest', 'ARMember') . ',</h3>',
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    'post_author' => 1,
                    'post_type' => 'page',
                ),
                'thank_you_page_id' => array(
                    'post_title' => 'Thank You',
                    'post_name' => 'thank_you',
                    'post_content' => "<h3>" . esc_html__('Thank you for payment with us, We will reach you soon.', 'ARMember') . "</h3>",
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    'post_author' => 1,
                    'post_type' => 'page',
                ),
                'cancel_payment_page_id' => array(
                    'post_title' => 'Cancel Payment',
                    'post_name' => 'cancel_payment',
                    'post_content' => esc_html__('Your purchase has not been completed.', 'ARMember') . '<br/>' . esc_html__('Sorry something went wrong while processing your payment.', 'ARMember'),
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    'post_author' => 1,
                    'post_type' => 'page',
                ),
            );
            return $all_pages;
        }

        function arm_default_common_messages() {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms;
            $common_messages = array(
                'arm_user_not_exist' => esc_html__('No such user exists in the system.', 'ARMember'),
                'arm_invalid_password_login' => esc_html__('The password you entered is invalid.', 'ARMember'),
                'arm_attempts_login_failed' => esc_html__('Remaining Login Attempts :', 'ARMember') . '&nbsp;' . '[ATTEMPTS]',
                'arm_attempts_many_login_failed' => esc_html__('Your Account is locked for', 'ARMember').' [LOCKDURATION] '.esc_html__('minutes.', 'ARMember'),
                'arm_permanent_locked_message' => esc_html__('Your Account is locked for', 'ARMember').' [LOCKDURATION] '.esc_html__('hours.', 'ARMember'),
                'arm_not_authorized_login' => esc_html__('Your account is inactive, you are not authorized to login.', 'ARMember'),
                'arm_spam_msg' => esc_html__('Spam detected.', 'ARMember'),
                'social_login_failed_msg' => esc_html__('Login Failed, please try again.', 'ARMember'),
                'arm_no_registered_email' => esc_html__('There is no user registered with that email address/Username.', 'ARMember'),
                'arm_reset_pass_not_allow' => esc_html__('Password reset is not allowed for this user.', 'ARMember'),
                'arm_email_not_sent' => esc_html__('Email could not be sent, please contact the site admin.', 'ARMember'),
                'arm_password_reset' => esc_html__('Password Reset Successfully!', 'ARMember') . ' [SUBTITLE]' . esc_html__('Your Password has been reset, Login now and get started', 'ARMember') . ' [/SUBTITLE]',
                'arm_password_reset_loginlink' => esc_html__('Login Now', 'ARMember'),
                'arm_password_enter_new_pwd' => esc_html__('Please enter new password', 'ARMember'),
                'arm_password_reset_pwd_link_expired' => esc_html__('Reset Password Link is invalid.', 'ARMember'),
                'arm_form_title_close_account' => esc_html__('Close Account', 'ARMember'),
                'arm_form_description_close_account' => esc_html__('Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account enter your password below.', 'ARMember'),
                'arm_password_label_close_account' => esc_html__('Your Password', 'ARMember'),
                'arm_submit_btn_close_account' => esc_html__('Submit', 'ARMember'),
                'arm_blank_password_close_account' => esc_html__('Password cannot be left Blank.', 'ARMember'),
                'arm_invalid_password_close_account' => esc_html__('The password you entered is invalid.', 'ARMember'),
                'arm_user_not_created' => esc_html__('Error while creating user.', 'ARMember'),
                'arm_username_exist' => esc_html__('This username is already registered, please choose another one.', 'ARMember'),
                'arm_email_exist' => esc_html__('This email is already registered, please choose another one.', 'ARMember'),
                'arm_avtar_label' => esc_html__('Avatar', 'ARMember'),
                'arm_profile_cover_label' => esc_html__('Profile Cover.', 'ARMember'),
                'arm_maxlength_invalid' => esc_html__('Maximum', 'ARMember') . ' [MAXVALUE]' . esc_html__(' characters allowed.', 'ARMember'),
                'arm_minlength_invalid' => esc_html__('Please enter at least', 'ARMember') . ' [MINVALUE]' . esc_html__(' characters.', 'ARMember'),
                'arm_expire_activation_link' => esc_html__('Activation link is expired or invalid.', 'ARMember'),
                'arm_expire_reset_password_activation_link' => esc_html__('Reset Password Link is expired.', 'ARMember'),
                'arm_email_activation_manual_pending' => esc_html__('Your account is not activated yet. Please contact site administrator.', 'ARMember'),
                'arm_already_active_account' => esc_html__('Your account has been activated.', 'ARMember'),
                'arm_account_disabled' => esc_html__('Your account is disabled. Please contact system administrator.', 'ARMember'),
                'arm_account_inactive' => esc_html__('Your account is currently not active. Please contact the system administrator.', 'ARMember'),
                'arm_account_pending' => esc_html__('Your account is currently not active. An administrator needs to activate your account before you can login.', 'ARMember'),
                'arm_account_expired' => esc_html__('Your account has expired. Please contact system administrator.', 'ARMember'),
                'arm_payment_fail_stripe' => esc_html__('Sorry something went wrong while processing payment with Stripe.', 'ARMember'),
                'arm_payment_fail_authorize_net' => esc_html__('Sorry, something went wrong while processing payment with Authorize.Net.', 'ARMember'),
                'arm_payment_fail_2checkout' => esc_html__('Sorry, something went wrong while processing payment with 2Checkout.', 'ARMember'),
                'arm_invalid_credit_card' => esc_html__('Please enter the correct card details.', 'ARMember'),
                'arm_unauthorized_credit_card' => esc_html__('Card details could not be authorized, please use other card detail.', 'ARMember'),
                'arm_credit_card_declined' => esc_html__('Your Card is declined.', 'ARMember'),
                'arm_blank_expire_month' => esc_html__('Expiry month should not be blank.', 'ARMember'),
                'arm_blank_expire_year' => esc_html__('Expiry year should not be blank.', 'ARMember'),
                'arm_blank_cvc_number' => esc_html__('CVC Number should not be blank.', 'ARMember'),
                'arm_blank_credit_card_number' => esc_html__('Card Number should not be blank.', 'ARMember'),
                'arm_invalid_plan_select' => esc_html__('Selected plan is not valid.', 'ARMember'),
                'arm_no_select_payment_geteway' => esc_html__('Your selected plan is paid, please select a payment method.', 'ARMember'),
                'arm_inactive_payment_gateway' => esc_html__('Payment gateway is not active, please contact the site administrator.', 'ARMember'),
                'arm_general_msg' => esc_html__('Sorry, something went wrong. Please contact the site administrator.', 'ARMember'),
                'arm_search_result_found' => esc_html__('No Search Result Found.', 'ARMember'),
                'arm_armif_invalid_argument' => esc_html__('Invalid conditional argument(s).', 'ARMember'),
                'arm_armif_already_logged_in' => esc_html__('You are already logged in.', 'ARMember'),
                'arm_success_coupon' => esc_html__('Coupon has been successfully applied.', 'ARMember'),
                'arm_empty_coupon' => esc_html__('Please enter the coupon code.', 'ARMember'),
                'arm_coupon_expire' => esc_html__('Coupon code has expired.', 'ARMember'),
                'arm_invalid_coupon' => esc_html__('Coupon code is not valid.', 'ARMember'),
                'arm_invalid_coupon_plan' => esc_html__('Coupon code is not valid for the selected plan.', 'ARMember'),
                'profile_directory_upload_cover_photo' => esc_html__('Upload Cover Photo', 'ARMember'),
                'profile_directory_remove_cover_photo' => esc_html__('Remove Cover Photo', 'ARMember'),
                'profile_template_upload_profile_photo' => esc_html__('Upload Profile Photo', 'ARMember'),
                'profile_template_remove_profile_photo' => esc_html__('Remove Profile Photo', 'ARMember'),
                'profile_template_search_filter_title' => esc_html__('Search Members', 'ARMember'),
                'directory_sort_by_field' => esc_html__('Sort By', 'ARMember'),
                'arm_profile_member_personal_detail' => esc_html__('Personal Details', 'ARMember'),
                'arm_directory_search_placeholder' => esc_html__('Search', 'ARMember'),
                'arm_directory_search_button' => esc_html__('Search', 'ARMember'),
                'arm_directory_reset_button' => esc_html__('Reset', 'ARMember'),
                'arm_recptcha_invalid' => esc_html__('Google reCAPTCHA Invalid or Expired. Please reload page and try again.', 'ARMember'),
                'directory_sort_by_alphabatically' => esc_html__('Alphabetically', 'ARMember'),
                'directory_sort_by_recently_joined' => esc_html__('Recently Joined', 'ARMember'),
                'arm_profile_member_since' => esc_html__('Member Since', 'ARMember'),
                'arm_profile_view_profile' => esc_html__('View profile', 'ARMember'),
                'arm_do_not_allow_pending_payment_bank_transfer' => esc_html__('Sorry! You have already one pending payment transaction. You will be able to proceed after that transaction will be approved.', 'ARMember'),
                'arm_pay_per_post_default_content' => esc_html__('Content is Restricted. Buy this post to get access to full content!','ARMember'),
                'arm_disabled_submission' => esc_html__('Sorry! Submit Button is disable to avoid any issues because you are logged in as an administrator.', 'ARMember'),
                'arm_purchase_limit_error' => esc_html__('Sorry, purchase limit has been exceeded.', 'ARMember'),
            );
            $common_messages = apply_filters('arm_default_common_messages', $common_messages);
            return $common_messages;
        }
        function get_section_wise_common_messages(){

            global $arm_social_feature,$arm_is_plan_limit_feature;

            $common_settings = array(
                "Login Related Messages" => array(
                                                "arm_user_not_exist" => esc_html__("Incorrect Username/Email",'ARMember'),
                                                "arm_invalid_password_login" => esc_html__("Incorrect Password",'ARMember'),
                                                "arm_attempts_many_login_failed" => esc_html__("Too Many Failed Login Attempts(Temporary)",'ARMember'),
                                                "arm_permanent_locked_message" => esc_html__("Too Many Failed Login Attempts(Permanent)",'ARMember'),
                                                "arm_attempts_login_failed" => esc_html__("Remained Login Attempts Warning",'ARMember'),
                                                "arm_armif_already_logged_in" => esc_html__("User Already LoggedIn Message",'ARMember'),
                                                "arm_spam_msg" => esc_html__("System Detected Spam Robots",'ARMember'),
                                            ),
                "Forgot Password Messages" => array(
                                                "arm_no_registered_email" => esc_html__("Incorrect Username/Email",'ARMember'),
                                                "arm_reset_pass_not_allow" => esc_html__("Password Reset Not Allowed",'ARMember'),
                                                "arm_email_not_sent" => esc_html__("Email Not Sent",'ARMember'),
                                            ),
                "Change Password Messages" => array(
                                                "arm_password_reset" => esc_html__("Your password has been reset",'ARMember'),
                                                'arm_password_reset_loginlink' => esc_html__('Login Now', 'ARMember'),
                                                "arm_password_enter_new_pwd" => esc_html__("Please enter new password",'ARMember'),
                                                "arm_password_reset_pwd_link_expired" => esc_html__("Reset Password Link is invalid",'ARMember'),
                                            ),
                "Close Account Messages" => array(
                                                "arm_form_title_close_account" => esc_html__("Form Title",'ARMember'),
                                                "arm_form_description_close_account" => esc_html__("Form Description",'ARMember'),
                                                "arm_password_label_close_account" => esc_html__("Password Field Label",'ARMember'),
                                                "arm_submit_btn_close_account" => esc_html__("Submit Button Label",'ARMember'),
                                                "arm_blank_password_close_account" => esc_html__("Empty Password Message",'ARMember'),
                                                "arm_invalid_password_close_account" => esc_html__("Invalid Password Message",'ARMember'),
                                            ),
                "Registration / Edit Profile Labels" => array(
                                                "arm_user_not_created" => esc_html__("User Not Created",'ARMember'),
                                                "arm_username_exist" => esc_html__("Username Already Exist",'ARMember'),
                                                "arm_email_exist" => esc_html__("Email Already Exist",'ARMember'),
                                                "arm_avtar_label" => esc_html__("Avatar Field Label( Edit Profile )",'ARMember'),
                                                "arm_profile_cover_label" => esc_html__("Profile Cover Field Label( Edit Profile )",'ARMember'),
                                                "arm_minlength_invalid" => esc_html__("Minlength",'ARMember'),
                                                "arm_maxlength_invalid" => esc_html__("Maxlength",'ARMember'),
                                            ),
                "Account Related Messages" => array(
                                                "arm_expire_activation_link" => esc_html__("Expire Activation Link",'ARMember'),
                                                "arm_already_active_account" => esc_html__("Account Activated",'ARMember'),
                                                "arm_account_pending" => esc_html__("Account Pending",'ARMember'),
                                                "arm_account_inactive" => esc_html__("Account Inactivated",'ARMember'),
                                            ),
                "Payment Related Messages" => array(
                                                "arm_payment_fail_stripe" => esc_html__("Payment Fail (Stripe)",'ARMember'),
                                                "arm_payment_fail_authorize_net" => esc_html__("Payment Fail (Authorize.net)",'ARMember'),
                                                "arm_payment_fail_2checkout" => esc_html__("Payment Fail (2Checkout)",'ARMember'),
                                                "arm_invalid_credit_card" => esc_html__("Invalid Credit Card Detail",'ARMember'),
                                                "arm_unauthorized_credit_card" => esc_html__("Credit Card Not Authorized",'ARMember'),
                                                "arm_credit_card_declined" => esc_html__("Credit Card Declined",'ARMember'),
                                                "arm_blank_expire_month" => esc_html__("Blank Expiry Month",'ARMember'),
                                                "arm_blank_expire_year" => esc_html__("Blank Expiry Year",'ARMember'),
                                                "arm_blank_cvc_number" => esc_html__("Blank CVC Number",'ARMember'),
                                                "arm_blank_credit_card_number" => esc_html__("Blank Credit Card Number",'ARMember'),
                                                "arm_invalid_plan_select" => esc_html__("Invalid Plan Selected",'ARMember'),
                                                "arm_no_select_payment_geteway" => esc_html__("No Gateway Selected For Paid Plan",'ARMember'),
                                                "arm_inactive_payment_gateway" => esc_html__("Payment Gateway Inactive",'ARMember'),
                                                "arm_do_not_allow_pending_payment_bank_transfer" => esc_html__("Do not allow pending payment (Bank Transfer)",'ARMember'),
                                            ),
                "Coupon Related Messages" => array(
                                                "arm_success_coupon" => esc_html__("Coupon Applied Successfully",'ARMember'),
                                                "arm_empty_coupon" => esc_html__("Coupon Empty",'ARMember'),
                                                "arm_coupon_expire" => esc_html__("Coupon Expired",'ARMember'),
                                                "arm_invalid_coupon" => esc_html__("Invalid Coupon",'ARMember'),
                                                "arm_invalid_coupon_plan" => esc_html__("Invalid Coupon For Plan",'ARMember'),
                                            ),
                "Profile/Directory Related Messages" => array(
                                                "profile_directory_upload_cover_photo" => esc_html__("Upload Cover Photo",'ARMember'),
                                                "profile_directory_remove_cover_photo" => esc_html__("Remove Cover Photo",'ARMember'),
                                                "profile_template_upload_profile_photo" => esc_html__("Upload Profile Photo",'ARMember'),
                                                "profile_template_remove_profile_photo" => esc_html__("Remove Profile Photo",'ARMember'),
                                                "profile_template_search_filter_title" => esc_html__("Search Filter Title",'ARMember'),
                                                "directory_sort_by_field" => esc_html__("Sort By (Directory Filter)",'ARMember'),
                                                "directory_sort_by_alphabatically" => esc_html__("Alphabatically (Directory Filter)",'ARMember'),
                                                "directory_sort_by_recently_joined" => esc_html__("Recently Joined (Directory Filter)",'ARMember'),
                                                "arm_profile_member_since" => esc_html__("Member Since",'ARMember'),
                                                "arm_profile_member_personal_detail" => esc_html__("Personal Details",'ARMember'),
                                                "arm_profile_view_profile" => esc_html__("View profile",'ARMember'),
                                                "arm_directory_search_placeholder" => esc_html__("Directory Search Placeholder",'ARMember'),
                                                "arm_directory_search_button" => esc_html__("Directory Search Button",'ARMember'),
                                                "arm_directory_reset_button" => esc_html__("Reset",'ARMember'),
                                            ),
                "Miscellaneous Messages" => array(
                                                "arm_general_msg" => esc_html__("General Message",'ARMember'),
                                                "arm_search_result_found" => esc_html__("No Search Result Found",'ARMember'),
                                                "arm_armif_invalid_argument" => esc_html__("Invalid Arguments (ARM If Shortcode)",'ARMember'),
                                                "arm_recptcha_invalid" => esc_html__("Invalid reCAPTCHA",'ARMember'),
                                            ),
            );

            if ( $arm_social_feature->isSocialFeature ) {
				$common_settings['Login Related Messages']['arm_social_login_msg'] = esc_html__('Login Failed Message for Social Connect', 'ARMember');
			}
            if($arm_is_plan_limit_feature==1){
                $common_settings['Miscellaneous Messages']['arm_purchase_limit_error'] = esc_html__("Membership Limit Message",'ARMember');
            }
            $common_settings = apply_filters( "add_common_settings_fields_for_translation", $common_settings);

            return $common_settings;
        }
        function get_common_messages_key_wise_notice(){
            $common_messages_notice = array(
                "arm_attempts_many_login_failed" => esc_html__("To display the duration of locked account, use",'ARMember')." <b>[LOCKDURATION]</b> " . esc_html__("shortcode in a message.",'ARMember'),
                "arm_permanent_locked_message" => esc_html__("To display the duration of locked account, use",'ARMember')." <b>[LOCKDURATION]</b> " . esc_html__("shortcode in a message.",'ARMember'),
                "arm_attempts_login_failed" => esc_html__("To display the number of remaining attempts use",'ARMember')." <b>[ATTEMPTS]</b>  " . esc_html__("shortcode in a message.",'ARMember'),
                "arm_armif_already_logged_in" => esc_html__("User already loggedIn message for modal forms ( Navigation Popup )",'ARMember'),
                "arm_password_reset" => esc_html__("To display password reset message use",'ARMember'). " <b>[SUBTITLE]".esc_html__("Success message description",'ARMember')."[/SUBTITLE]</b> " . esc_html__("shortcode in message.",'ARMember')."<br>".esc_html__("(This message will be used only when password is changed from password reset link sent in mail)",'ARMember'),
                "arm_password_reset_loginlink" => esc_html__("(This text will be displayed in reset password success message link after password reset successfully)",'ARMember'),
                "arm_password_enter_new_pwd" => esc_html__("(This message will be displayed in reset password form where user comes by clicking on reset password link)",'ARMember'),
                "arm_password_reset_pwd_link_expired" => esc_html__("(This message will be displayed on page where user comes by clicking expired reset password link)",'ARMember'),
                "arm_minlength_invalid" => esc_html__("To display allowed minimum characters use",'ARMember')." <b>[MINVALUE]</b> " . esc_html__("shortcode in message.",'ARMember'),
                "arm_maxlength_invalid" => esc_html__("To display allowed maximum characters",'ARMember')." <b>[MAXVALUE]</b> " . esc_html__("shortcode in message.",'ARMember'),
                "arm_unauthorized_credit_card" => esc_html__("(in case of Authorize.net payment gateway only.)",'ARMember'),
            );
            $common_messages_notice = apply_filters( "add_common_settings_notices_for_translation", $common_messages_notice);
            
            return $common_messages_notice;
        }

        function get_common_settings_section_titles() {
            $common_settings_section = array(
                "Login Related Messages" => esc_html__("Login Related Messages",'ARMember'),
                "Forgot Password Messages" => esc_html__("Forgot Password Messages",'ARMember'),
                "Change Password Messages" => esc_html__("Change Password Messages",'ARMember'),
                "Close Account Messages" => esc_html__("Close Account Messages",'ARMember'),
                "Registration / Edit Profile Labels" => esc_html__("Registration / Edit Profile Labels",'ARMember'),
                "Account Related Messages" => esc_html__("Account Related Messages",'ARMember'),
                "Payment Related Messages" => esc_html__("Payment Related Messages",'ARMember'),
                "Coupon Related Messages" => esc_html__("Coupon Related Messages",'ARMember'),
                "Profile/Directory Related Messages" => esc_html__("Profile/Directory Related Messages",'ARMember'),
                "Miscellaneous Messages" => esc_html__("Miscellaneous Messages",'ARMember'),
            );
            $common_settings_section = apply_filters('arm_add_new_section_titles_for_common_settings', $common_settings_section);
            return $common_settings_section;
        }

        function arm_get_social_form_page_shortcodes($page_id = 0, $selected_form = '') {
            global $wp, $wpdb, $ARMember, $arm_member_forms;
            $form_shortcodes = $setupForms = array();
            $sel_form_id = (!empty($selected_form)) ? $selected_form : '';
            $form_select_box = '';
            $error_message = true;
            $page_detail = get_post($page_id);
            $page_on_front = get_option('page_on_front');
            $page_for_posts = get_option('page_for_posts');
            if (!empty($page_detail->ID) && $page_detail->ID != 0 && !in_array($page_detail->ID, array($page_on_front, $page_for_posts))) {
                $post_content = $page_detail->post_content;
                $is_setup_shortcode = $this->arm_find_match_shortcode_func('arm_setup', $post_content);
                if ($is_setup_shortcode) {
                    $allSetups = $wpdb->get_results("SELECT `arm_setup_id`, `arm_setup_modules` FROM `" . $ARMember->tbl_arm_membership_setup . "` ORDER BY `arm_setup_id` DESC", ARRAY_A); //phpcs:ignore --Reason: $ARMember->tbl_arm_membership_setup is a table name. SELECT QUERY without where clause
                    if (!empty($allSetups)) {
                        foreach ($allSetups as $setup) {
                            $setup_id = $setup['arm_setup_id'];
                            $setupModules = maybe_unserialize($setup['arm_setup_modules']);
                            foreach (array("'$setup_id'", $setup_id, '"' . $setup_id . '"') as $val) {
                                if (preg_match_all('/\[arm_setup(.*)id=' . $val . '(.*)\]/s', $post_content, $matches) > 0) {
                                    if (isset($setupModules['modules']['forms']) && !empty($setupModules['modules']['forms'])) {
                                        $setupForms[] = $setupModules['modules']['forms'];
                                    }
                                }
                            }
                        }
                        $setupForms = (!empty($setupForms)) ? $ARMember->arm_array_unique($setupForms) : array();
                    }
                }
                $is_shortcode = $this->arm_find_match_shortcode_func('arm_form', $post_content);
                if (!$is_shortcode) {
                    $is_shortcode = apply_filters('armember_cs_check_shortcode_in_page', $is_shortcode, 'cs_armember_cs', $post_content);
                }
                $forms = $arm_member_forms->arm_get_member_forms_by_type('registration');
                $allow_fields = array('text', 'email', 'textarea', 'hidden');
                if (!empty($forms)) {
                    foreach ($forms as $form) {
                        $form_id = $form['arm_form_id'];
                        $form_slug = $form['arm_form_slug'];
                        if (in_array($form_id, $setupForms)) {
                            $form_shortcodes['forms'][$form_id] = array(
                                'id' => $form['arm_form_id'],
                                'slug' => $form['arm_form_slug'],
                                'name' => strip_tags(stripslashes($form['arm_form_label'])),
                            );
                        }
                        if ($is_shortcode) {
                            foreach (array("'$form_id'", $form_id, '"' . $form_id . '"') as $val) {
                                if (preg_match_all('/id=' . $val . '|arm_form_registration=' . $val . '/s', $post_content, $matches) > 0) {
                                    $form_shortcodes['forms'][$form_id] = array(
                                        'id' => $form['arm_form_id'],
                                        'slug' => $form['arm_form_slug'],
                                        'name' => strip_tags(stripslashes($form['arm_form_label'])),
                                    );
                                }
                            } /* END `foreach (array("'$form_slug'", $form_slug, '"' . $form_slug . '"') as $val)` */
                        }
                    } /* END `foreach ($forms as $form)` */
                } /* END `if (!empty($forms))` */
                if (!empty($form_shortcodes['forms'])) {
                    $form_select_box = '';
                    $allFoundForms = $form_shortcodes['forms'];
                    $firstForm = array_shift($form_shortcodes['forms']);
                    $sel_form_id = (!empty($selected_form)) ? $selected_form : $firstForm['id'];
                    if (count($allFoundForms) == 1) {
                        $form_select_box .= '<input type="hidden" name="arm_social_settings[registration][form]" value="' . $firstForm['id'] . '"/>';
                        $form_select_box .= $firstForm['name'];
                    } else {
                        $form_select_box .= '<input type="hidden" name="arm_social_settings[registration][form]" id="arm_social_reg_form" class="arm_social_reg_form" value="' . esc_attr($sel_form_id) . '" data-msg-required="' . esc_attr__('Registration form is required.', 'ARMember') . '" />';
                        $form_select_box .= '<dl class="arm_selectbox column_level_dd">';
                        $form_select_box .= '<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';
                        $form_select_box .= '<dd><ul data-id="arm_social_reg_form">';
                        if (!empty($allFoundForms)) {
                            foreach ($allFoundForms as $reg_form) {
                                $form_select_box .= '<li data-label="' . esc_attr( $reg_form['name'] ) . '" data-value="' . esc_attr( $reg_form['id'] ) . '">' . esc_html( $reg_form['name'] ) . '</li>';
                            }
                        }
                        $form_select_box .= '</ul></dd>';
                        $form_select_box .= '</dl>';
                    }
                }
            }
            if (empty($form_select_box)) {
                $error_message = false;
                $form_select_box .= '<input type="hidden" name="arm_social_settings[registration][form]" value="" data-msg-required="' . esc_attr__('Registration form is required. Please select valid registration page.', 'ARMember') . '"/>';
            }
            $return_data = array(
                'forms' => $form_select_box,
                'form_id' => $sel_form_id,
                'status' => $error_message,
            );
            return $return_data;
        }

        function arm_social_form_exist_in_page() {
            global $wp, $wpdb, $ARMember, $arm_member_forms, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1);
            $page_id = intval($_POST['page_id']);//phpcs:ignore
            $form_shortcodes = $this->arm_get_social_form_page_shortcodes($page_id);
            $forms = $form_shortcodes['forms'];
            $return = array('forms' => $forms, 'form_id' => $form_shortcodes['form_id'], 'status' => $form_shortcodes['status']);
            echo arm_pattern_json_encode($return);
            exit;
        }
        
        
        function arm_registration_form_shortcode_exist_in_page($shortcode_type = '', $page_id = 0)
        {
                
        
            global $wp, $wpdb, $ARMember, $arm_member_forms;
                $is_exist = false;

                $page_detail = get_post($page_id);

                if (!empty($page_detail->ID) && $page_detail->ID != 0)
                {
                        $post_content = $page_detail->post_content;
                        $shortcode_text = array();
                        switch ($shortcode_type) {
                            case 'registration':
                            case 'login':
                                $is_shortcode = $this->arm_find_match_shortcode_func('arm_form', $post_content);
                                $is_cs_shortcode = $this->arm_find_match_shortcode_func('cs_armember_cs', $post_content);
                                if ($is_shortcode || $is_cs_shortcode) {
                                        $forms = $arm_member_forms->arm_get_member_forms_by_type($shortcode_type, false);
                                        if (!empty($forms)) {
                                                foreach ($forms as $form) {
                                                        $form_slug = $form['arm_form_id'];
                                                        $shortcode_text[] = "id='$form_slug'";
                                                        $shortcode_text[] = "id=$form_slug";
                                                        $shortcode_text[] = 'id="' . $form_slug . '"';
                                                        if( $shortcode_type == 'registration' ){
                                                                $shortcode_text[] = 'arm_form_registration="'.$form_slug.'"';
                                                        } else if( $shortcode_type == 'login' ){
                                                                $shortcode_text[] = 'arm_form_login="'.$form_slug.'"';
                                                        } else if( $shortcode_type == 'change_password' ){
                                                                $shortcode_text[] = 'arm_form_change_password="'.$form_slug.'"';
                                                        } else if( $shortcode_type == 'forgot_password' ){
                                                                $shortcode_text[] = 'arm_form_forgot_password="'.$form_slug.'"';
                                                        }
                                                }
                                                $is_exist = $this->arm_find_registration_match_func($shortcode_text, $post_content);
                                        }
                                }
                            break;
                            default :
                                break;    
                        }
                }
                return $is_exist; 
        }
                                
        function arm_shortcode_exist_in_page($shortcode_type = '', $page_id = 0) {
            global $wp, $wpdb, $ARMember, $arm_member_forms, $arm_capabilities_global;
            $is_exist = false;
            $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
            if (isset($posted_data['action']) && $posted_data['action'] == 'arm_shortcode_exist_in_page') {
                $shortcode_type = sanitize_text_field( $posted_data['shortcode_type'] );
                $page_id = intval( $posted_data['page_id'] );
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            }
            $page_detail = get_post($page_id);
            if (!empty($shortcode_type) && !empty($page_detail->ID) && $page_detail->ID != 0) {
                $post_content = $page_detail->post_content;
                $shortcode_text = array();
                switch ($shortcode_type) {
                    case 'registration':
                    case 'login':
                    case 'forgot_password':
                    case 'change_password':
                        $is_shortcode = $this->arm_find_match_shortcode_func('arm_form', $post_content);
                        $is_cs_shortcode = false;
                        $is_cs_shortcode = apply_filters('armember_cs_check_shortcode_in_page', $is_cs_shortcode, 'cs_armember_cs', $post_content);
                        if ($is_shortcode || $is_cs_shortcode) {
                            $forms = $arm_member_forms->arm_get_member_forms_by_type($shortcode_type, false);
                            
                             
                            if (!empty($forms)) {
                                foreach ($forms as $form) {
                                    $form_slug = $form['arm_form_id'];
                                    $shortcode_text[] = "id='$form_slug'";
                                    $shortcode_text[] = "id=$form_slug";
                                    $shortcode_text[] = 'id="' . $form_slug . '"';
                                    if ($shortcode_type == 'registration') {
                                        $shortcode_text[] = 'arm_form_registration="' . $form_slug . '"';
                                    } else if ($shortcode_type == 'login') {
                                        $shortcode_text[] = 'arm_form_login="' . $form_slug . '"';
                                    } else if ($shortcode_type == 'change_password') {
                                        $shortcode_text[] = 'arm_form_change_password="' . $form_slug . '"';
                                    } else if ($shortcode_type == 'forgot_password') {
                                        $shortcode_text[] = 'arm_form_forgot_password="' . $form_slug . '"';
                                    }
                                }
                                $is_exist = $this->arm_find_match_func($shortcode_text, $post_content);
                            }
                        }
                        /* Check Membership Setup Wizard Shortcode */
                        if ($shortcode_type == 'registration' && !$is_exist) {
                            $is_exist = $this->arm_find_match_shortcode_func('arm_setup', $post_content);
                            if (!$is_exist) {
                                $is_exist = apply_filters('armember_cs_check_shortcode_in_page', $is_exist, 'cs_armember_cs', $post_content);
                            }
                        }
                        break;
                    case 'edit_profile':
                        $is_exist = $this->arm_find_match_shortcode_func('arm_edit_profile', $post_content);
                        $is_exist_profile = $this->arm_find_match_shortcode_func('arm_profile_detail', $post_content);
                        $is_exist = (empty($is_exist) && !empty($is_exist_profile)) ? $is_exist_profile : $is_exist;
                        if (!$is_exist && !$is_exist_profile) {
                            $is_exist = apply_filters('armember_cs_check_shortcode_in_page', $is_exist, 'cs_armember_cs', $post_content);
                        }
                        break;
                    case 'members_directory':
                        $is_exist = $this->arm_find_match_shortcode_func('arm_template type="profile"', $post_content);
                        break;
                    case 'arm_setup':
                        $is_exist = $this->arm_find_match_shortcode_func('arm_setup', $post_content);
                        break;
                    default :
                        $is_exist = apply_filters('arm_shortcode_exist_in_page', $is_exist, $post_content);
                        break;
                }
            }
            if (isset($posted_data['action']) && $posted_data['action'] == 'arm_shortcode_exist_in_page') {
                echo arm_pattern_json_encode(array('status' => $is_exist));
                exit;
            } else {
                return $is_exist;
            }
        }

        function arm_find_match_shortcode_func($key = '', $string = '') {
            $matched = false;
            $pattern = '\[' . $key . '(.*?)\]';
            if (!empty($key) && !empty($string)) {
                if (preg_match_all('/' . $pattern . '/s', $string, $matches) > 0) {
                    $matched = true;
                }
            }
            return $matched;
        }

        function arm_find_match_func($key = array(), $string = '') {
            if (!empty($key) && !empty($string)) {
                foreach ($key as $val) {
                    if (preg_match_all('/' . $val . '/s', $string, $matches) > 0) {
                        return true;
                    }
                }
               
            }
            return false;
        }
        
        function arm_find_registration_match_func($key = array(), $string = '') {
            if (!empty($key) && !empty($string)) {
                foreach ($key as $val) {
                    if (preg_match_all('/' . $val . '/s', $string, $matches) > 0) {
                        
                        $val = preg_replace('/[a-z=\'\"]/','',$val);
                        return $val;
                    }
                }
            }
            return false;
        }

        /**
         * Parse shortcodes in Feed Post Excerpt
         */
        function arm_filter_the_excerpt($content) {
            $isARMShortcode = $this->arm_find_match_shortcode_func('arm_', $content);
            $isARMIFShortcode = $this->arm_find_match_shortcode_func('armif', $content);
            if ($isARMShortcode || $isARMIFShortcode) {
                $content = do_shortcode($content);
            }
            return $content;
        }

        function arm_get_all_roles() {
            $allRoles = array();
            if (!function_exists('get_editable_roles') && file_exists(ABSPATH . '/wp-admin/includes/user.php')) {
                require_once(ABSPATH . '/wp-admin/includes/user.php');
            }
            global $wp_roles;
            $roles = get_editable_roles();
            if (!empty($roles)) {
                unset($roles['administrator']);
                foreach ($roles as $key => $role) {
                    $allRoles[$key] = $role['name'];
                }
            }


            return $allRoles;
        }

        function arm_get_all_roles_for_badges() {
            $allRoles = array();
            if (!function_exists('get_editable_roles') && file_exists(ABSPATH . '/wp-admin/includes/user.php')) {
                require_once(ABSPATH . '/wp-admin/includes/user.php');
            }
            global $wp_roles;
            $roles = get_editable_roles();
            if (!empty($roles)) {
                unset($roles['administrator']);
                foreach ($roles as $key => $role) {
                    $allRoles[$key] = $role['name'];
                }
            }

            if (is_plugin_active('bbpress/bbpress.php')) {

                if (function_exists('bbp_get_dynamic_roles')) {
                    foreach (bbp_get_dynamic_roles() as $role => $details) {
                        $allRoles[$role] = $details['name'];
                    }
                }
            }

            return $allRoles;
        }

        function arm_get_permalink($slug = '', $id = 0) {
            global $wp, $wpdb, $ARMember;
            $link = ARM_HOME_URL;
            if (!empty($slug) && $slug != '') {
                $object = $wpdb->get_results( $wpdb->prepare("SELECT `ID` FROM " . $wpdb->posts . " WHERE `post_name`=%s",$slug) ); //phpcs:ignore --Reason: $wpdb->posts is a table name
                if (!empty($object)) {
                    $link = get_permalink($object[0]->ID);
                    $id = $object[0]->ID;
                }
            } elseif (!empty($id) && $id != 0) {
                $link = get_permalink($id);
            }
	    
            $link = apply_filters('arm_modify_redirection_page_external', $link,0,$id);
	    
            return $link;
        }

        function arm_get_user_profile_url($userid = 0, $show_admin_users = 0) {
            global $wp, $wpdb, $ARMember, $arm_social_feature;
            if ($show_admin_users == 0) {
                if (user_can($userid, 'administrator')) {
                    return '#';
                }
            }
            $profileUrl = ARM_HOME_URL;
            if ($arm_social_feature->isSocialFeature) {
                if (isset($this->profile_url) && !empty($this->profile_url)) {
                    $profileUrl = $this->profile_url;
                    $profileUrl = apply_filters('arm_modify_redirection_page_external', $profileUrl,$userid,0);
                } else {
                    $profile_page_id = isset($this->global_settings['member_profile_page_id']) ? $this->global_settings['member_profile_page_id'] : 0;
                    $profile_page_url = get_permalink($profile_page_id);
                    $profileUrl = (!empty($profile_page_url)) ? $profile_page_url : $profileUrl;
                    $profileUrl = apply_filters('arm_modify_redirection_page_external', $profileUrl,$userid,$profile_page_id);
                    $this->profile_url = $profileUrl;
                }
                if (!empty($userid) && $userid != 0) {
                    $permalinkBase = isset($this->global_settings['profile_permalink_base']) ? $this->global_settings['profile_permalink_base'] : 'user_login';
                    $userBase = $userid;
                    if ($permalinkBase == 'user_login') {
                        $userInfo = get_userdata($userid);
                        $userBase = !empty( $userInfo->user_login ) ? $userInfo->user_login : '';
                    }
                    if (get_option('permalink_structure')) {
                        $profileUrl = trailingslashit(untrailingslashit($profileUrl));
                        $profileUrl = $profileUrl . $userBase . '/';
                    } else {
                        $profileUrl = $this->add_query_arg('arm_user', $userBase, $profileUrl);
                    }
                }
            } else {
                if (isset($this->global_settings['edit_profile_page_id']) && $this->global_settings['edit_profile_page_id'] != 0) {
                    $profileUrl = get_permalink($this->global_settings['edit_profile_page_id']);
                    $profileUrl = apply_filters('arm_modify_redirection_page_external', $profileUrl,$userid,$this->global_settings['edit_profile_page_id']);
                }
            }
            return $profileUrl;
        }

        function arm_user_query_vars($public_query_vars) {
            $public_query_vars[] = 'arm_user';
            return $public_query_vars;
        }

        function arm_user_rewrite_rules() {
            global $wp, $wpdb, $wp_rewrite, $ARMember;
            $allGlobalSettings = $this->arm_get_all_global_settings(TRUE);
            if (isset($allGlobalSettings['member_profile_page_id']) && $allGlobalSettings['member_profile_page_id'] != 0) {
                $profile_page_id = $allGlobalSettings['member_profile_page_id'];
                $profilePage = get_post($profile_page_id);
                $is_parent_page = isset($profilePage->post_parent) && $profilePage->post_parent != 0 ? true : false ; 
                $parent_page_id = isset($profilePage->post_parent) && !empty($profilePage->post_parent) ? $profilePage->post_parent : 0 ; 
                $profileParentSlug = '';
                while ( $is_parent_page ) {
                    $parentPage = get_post($parent_page_id);
                    $profileParentSlug = isset($parentPage->post_name) &&  !empty($parentPage->post_name) ? $parentPage->post_name.'/'.$profileParentSlug : '' ;                                        
                    $parent_page_id = $parentPage->post_parent;                    
                    if($parent_page_id != 0) {
                        $is_parent_page = true;
                    } else {
                        $is_parent_page = false;
                        break;
                    }
                }
                if (isset($profilePage->post_name)) {
                    $profileSlug = $profilePage->post_name;
                    add_rewrite_rule($profileParentSlug.$profileSlug . '/([^/]+)/?$', 'index.php?page_id=' . $profile_page_id . '&arm_user=$matches[1]', 'top');
                    do_action( 'arm_add_rewrite_rule_for_url_modification_externally', $profileParentSlug.$profileSlug . '/([^/]+)/?$', $profile_page_id,'profile_page','top');
                }
            }
        }

        function arm_generate_rewrite_rules( $wp_rewrite ) {
            global $wp, $wpdb, $wp_rewrite, $ARMember;
            $allGlobalSettings = $this->arm_get_all_global_settings(TRUE);
            if (isset($allGlobalSettings['member_profile_page_id']) && $allGlobalSettings['member_profile_page_id'] != 0) {
                $profile_page_id = $allGlobalSettings['member_profile_page_id'];
                $profilePage = get_post($profile_page_id);
                if (isset($profilePage->post_name)) {
                    $profileSlug = $profilePage->post_name;
                    //add_rewrite_rule($profileSlug . '/([^/]+)/?$', 'index.php?page_id=' . $profile_page_id . '&arm_user=$matches[1]', 'top');
                    $feed_rules = array(
                      $profileSlug.'/([^/]+)/?$' => 'index.php?page_id=' . $profile_page_id . '&arm_user=$matches[1]',
                    );
                    
                    $wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
                }
            }
            return $wp_rewrite->rules;
        }

        /**
         * Create Pagination Links
         * @param Int $total Total Number Of Records
         * @param Int $per_page Number Of Records Per Page
         */
        function arm_get_paging_links($current = 1, $total = 10, $per_page = 10, $type = "",$pagination_label="") {
            global $wp, $wp_rewrite;
            $return_links = '';
            $current = (!empty($current) && $current != 0) ? $current : 1;
            $total_links = ceil($total / $per_page);
            /* Don't print empty markup if there's only one page. */
            if ($total_links < 1) {
                return;
            }
            $end_size = 1;
            $mid_size = 1;
            $page_links = array();
            $dots = false;
            if ($current && 1 < $current) {
                $prev = $current - 1;
                $page_links[] = '<a class="arm_prev arm_page_numbers" href="javascript:void(0)" data-page="' . esc_attr($prev) . '" data-per_page="' . esc_attr($per_page) . '"></a>';
            } else {
                $page_links[] = '<a class="arm_prev current arm_page_numbers" href="javascript:void(0)" data-per_page="' . esc_attr($per_page) . '"></a>';
            }
            for ($n = 1; $n <= $total_links; $n++) {
                if ($n == $current) {
                    $page_links[] = '<a class="current arm_page_numbers" href="javascript:void(0)" data-page="' . esc_attr($current) . '" data-per_page="' . esc_attr($per_page) . '">' . number_format_i18n($n) . '</a>';
                    $dots = true;
                } else {
                    if ($n <= $end_size || ($current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total_links - $end_size) {
                        $page_links[] = '<a class="arm_page_numbers" href="javascript:void(0)" data-page="' . esc_attr($n) . '" data-per_page="' . esc_attr($per_page ). '">' . number_format_i18n($n) . '</a>';
                        $dots = true;
                    } elseif ($dots) {
                        $page_links[] = '<span class="arm_page_numbers dots">&hellip;</span>';
                        $dots = false;
                    }
                }
            }
            if ($current && ( $current < $total_links || -1 == $total_links )) {
                $next = $current + 1;
                $page_links[] = '<a class="arm_next arm_page_numbers" href="javascript:void(0)" data-page="' . esc_attr($next) . '" data-per_page="' . esc_attr($per_page) . '"></a>';
            } else {
                $page_links[] = '<a class="arm_next current arm_page_numbers" href="javascript:void(0)" data-per_page="' . esc_attr($per_page) . '"></a>';
            }
            if (!empty($page_links)) {

                $startNum = (!empty($current) && $current > 1) ? (($current - 1) * $per_page) + 1 : 1;
                $endNum = $current * $per_page;
                $endNum = ($endNum > $total) ? $total : $endNum;
                /* Join Links */
                $links = join("\n", $page_links);
                $return_links = '<div class="arm_paging_wrapper arm_paging_wrapper_' . esc_attr($type) . '">';
                $return_links .= '<div class="arm_paging_info">';
                switch ($type) {
                    case 'activity':
                        $return_links .= esc_html__('Showing', 'ARMember') . ' ' . esc_html($startNum) . ' ' . esc_html__('to', 'ARMember') . ' ' . esc_html($endNum) . ' ' . esc_html__('of', 'ARMember') . ' ' . esc_html($total) . ' ' . esc_html__('total activities', 'ARMember');
                        break;
                    case 'membership_history':
                        $return_links .= esc_html__('Showing', 'ARMember') . ' ' . esc_html($startNum) . ' ' . esc_html__('to', 'ARMember') . ' ' . esc_html($endNum) . ' ' . esc_html__('of', 'ARMember') . ' ' . esc_html($total) . ' ' . esc_html__('total records', 'ARMember');
                        break;
                    case 'directory':
                        $return_links .= esc_html__('Showing', 'ARMember') . ' ' . esc_html($startNum) . ' - ' . esc_html($endNum) . ' ' . esc_html__('of', 'ARMember') . ' ' . esc_html($total) . ' ' . esc_html__('members', 'ARMember');
                        break;
                    case 'transaction':
                        $return_links .= esc_html__('Showing', 'ARMember') . ' ' . esc_html($startNum) . ' - ' . esc_html($endNum) . ' ' . esc_html__('of', 'ARMember') . ' ' . esc_html($total) . ' ' . esc_html__('transactions', 'ARMember');
                        break;
                    case 'current_membership':
                        $pagination_label = !empty($pagination_label) ? esc_html($pagination_label) : esc_html__('Membership', 'ARMember');
                        $return_links .= esc_html__('Showing', 'ARMember') . ' ' . esc_html($startNum) . ' - ' . esc_html($endNum) . ' ' . esc_html__('of', 'ARMember') . ' ' . esc_html($total) . ' ' . $pagination_label;
                        break;
                    case 'paid_post_membership':
                        $pagination_label = !empty($pagination_label) ? esc_html($pagination_label) : esc_html__('Paid Post', 'ARMember');
                        $return_links .= esc_html__('Showing', 'ARMember') . ' ' . esc_html($startNum) . ' - ' . esc_html($endNum) . ' ' . esc_html__('of', 'ARMember') . ' ' . esc_html($total) . ' ' . $pagination_label;
                        break;
                    default:
                        $return_links .= esc_html__('Showing', 'ARMember') . ' ' . esc_html($startNum) . ' - ' . esc_html($endNum) . ' ' . esc_html__('of', 'ARMember') . ' ' . esc_html($total) . ' ' . esc_html__('records', 'ARMember');
                        break;
                }
                $return_links .= '</div>';
                $return_links .= '<div class="arm_paging_links">' . $links . '</div>';
                $return_links .= '</div>';
            }
            return $return_links;
        }

        function arm_filter_get_avatar($avatar, $id_or_email, $size, $default, $alt = '') {
            global $pagenow;
            /* Do not filter if inside WordPress options page OR `enable_gravatar` set to '0' */
            if ('options-discussion.php' == $pagenow) {
                return $avatar;
            }
            $user_avatar = $this->arm_get_user_avatar($id_or_email, $size, $default, $alt);
            if (!empty($user_avatar)) {
                $avatar = $user_avatar;
            } else {
                if ( empty($this->global_settings['enable_gravatar']) ) {
                    $avatar = "<img src='" . MEMBERSHIPLITE_IMAGES_URL . "/avatar_placeholder.png' class='avatar arm_grid_avatar arm-avatar avatar-".esc_attr($size)."' width='".esc_attr($size)."' />";
                } else {
                    $avatar = str_replace('avatar-' . $size, 'arm_grid_avatar arm-avatar avatar-' . $size, $avatar);
                }
            }
            return apply_filters('arm_change_user_avatar', $avatar, $id_or_email, $size, $default, $alt);
        }

        function arm_filter_get_avatar_url($url, $id_or_email, $args){
            if (is_numeric($id_or_email)) {
                $user_id = (int) $id_or_email;
            } elseif (is_string($id_or_email) && ($user = get_user_by('email', $id_or_email))) {
                $user_id = $user->ID;
            } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
                $user_id = (int) $id_or_email->user_id;
            } else {
                $user_id = 0;
            }
	    
	    if(!empty($user_id))
	    {
		$avatar_url = get_user_meta($user_id, 'avatar', true);
		if (!empty($avatar_url) && file_exists(MEMBERSHIP_UPLOAD_DIR . '/' . basename($avatar_url))) {
		return $avatar_url;
		}
	    }
            return $url;
        }

        function arm_get_avatar($id_or_email, $size = '96', $default = '', $alt = false) {
            global $wp, $wpdb, $ARMember;
            $user_avatar = $this->arm_get_user_avatar($id_or_email, $size, $default, $alt);
            if ($this->global_settings['enable_gravatar'] == '1' && !empty($user_avatar)) {
                $avatar = apply_filters('arm_change_user_avatar', $user_avatar, $id_or_email, $size, $default, $alt);
            } else {
                $avatar = get_avatar($id_or_email, $size, $default, $alt);
            }
            return $avatar;
        }

        function arm_get_user_avatar($id_or_email, $size = '96', $default = '', $alt = false) {
            global $wp, $wpdb, $ARMember;
            $safe_alt = (false === $alt) ? '' : esc_attr($alt);
            if (is_numeric($id_or_email)) {
                $user_id = (int) $id_or_email;
            } elseif (is_string($id_or_email) && ($user = get_user_by('email', $id_or_email))) {
                $user_id = $user->ID;
            } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
                $user_id = (int) $id_or_email->user_id;
            } else {
                $user_id = 0;
            }
            $user = get_user_by('id', $user_id);
            $avatar_url = get_user_meta($user_id, 'avatar', true);
            $avatar_w_h_class = '';
            if (!empty($avatar_url) && file_exists(MEMBERSHIP_UPLOAD_DIR . '/' . basename($avatar_url))) {
                $avatar_detail = @getimagesize(MEMBERSHIP_UPLOAD_DIR . '/' . basename($avatar_url));
                if ($size > $avatar_detail[0]) {
                    $avatar_w_h_class = ' arm_avatar_small_width';
                }
                if ($size > $avatar_detail[1]) {
                    $avatar_w_h_class .= ' arm_avatar_small_height';
                }
            }
            $avatar_class = 'arm_grid_avatar gravatar avatar arm-avatar photo avatar-' . $size . ' ' . $avatar_w_h_class;
            if (empty($safe_alt) && $user) {
                $safe_alt = esc_html__('Profile photo of', 'ARMember') . $user->user_login;
            }
            
            if (!empty($avatar_url) && file_exists(MEMBERSHIP_UPLOAD_DIR . '/' . basename($avatar_url))) {
                $avatar_filesize =  @filesize(MEMBERSHIP_UPLOAD_DIR . '/' . basename($avatar_url));
                if($avatar_filesize>0)
                {
                    /*
                    if (file_exists(strstr($avatar_url, "//"))) {
                        $avatar_url = strstr($avatar_url, "//");
                    } else if (file_exists($avatar_url)) {
                        $avatar_url = $avatar_url;
                    } else {
                        $avatar_url = $avatar_url;
                    }
                    */
                    $avatar = '<img src="' . $avatar_url . '" class="' . esc_attr($avatar_class) . '" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" alt="' . esc_attr($safe_alt) . '"/>';
                }
                else {
                    $avatar = '';
                }
            } else {
                $avatar = '';
            }
            return $avatar;
        }

        function arm_default_avatar_url($default = '') {
            global $wp, $wpdb, $ARMember;
            $avatar_default = get_option('avatar_default');
            $default = (!empty($avatar_default)) ? $avatar_default : 'mystery';
            if (is_ssl()) {
                $host = 'https://secure.gravatar.com';
            } else {
                $host = 'http://0.gravatar.com';
            }
            if ('mystery' == $default) {
                $default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}";
            } elseif ('blank' == $default) {
                $default = includes_url('images/blank.gif');
            } elseif ('gravatar_default' == $default) {
                $default = "$host/avatar/?s={$size}";
            } elseif (strpos($default, 'http://') === 0) {
                $default = add_query_arg('s', $size, $default);
            }
            return esc_url($default);
        }

        /**
         * Get Single Global Setting by option name
         */
        function arm_get_single_global_settings($option_name, $default = '') {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms;
            $all_settings = $this->global_settings;
            $option_value = $default;
            if (!empty($option_name)) {
                if (isset($all_settings[$option_name]) && !empty($all_settings[$option_name])) {
                    $option_value = $all_settings[$option_name];
                } elseif ($option_name == 'page_settings') {
                    $defaultGS = $this->arm_default_global_settings();
                    $option_value = shortcode_atts($defaultGS['page_settings'], $all_settings);
                }
            }
            return $option_value;
        }

        function arm_get_all_global_settings($merge = FALSE) {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms;
            $default_global_settings = $this->arm_default_global_settings();


            $global_settings = get_option('arm_global_settings', $default_global_settings);
           
         
            $all_global_settings = maybe_unserialize($global_settings);
            $all_global_settings = apply_filters('arm_get_all_global_settings', $all_global_settings);
            if ($merge) {
                $all_global_settings['general_settings'] = isset($all_global_settings['general_settings']) ? $all_global_settings['general_settings'] : $default_global_settings['general_settings'];
                $all_global_settings['page_settings'] = isset($all_global_settings['page_settings']) ? $all_global_settings['page_settings'] : $default_global_settings['page_settings'];
                $arm_merge_global_settings = array_merge($all_global_settings['general_settings'], $all_global_settings['page_settings']);
                return $arm_merge_global_settings;
            }
            return $all_global_settings;
        }

        function arm_get_all_block_settings() {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms;
            $default_block_settings = array(
                'failed_login_lockdown' => 1,
                'remained_login_attempts' => 1,
                'track_login_history' => 1,
                'max_login_retries' => 5,
                'temporary_lockdown_duration' => 10,
                'permanent_login_retries' => 15,
                'permanent_lockdown_duration' => 24,
                'arm_block_ips' => '',
                'arm_block_ips_msg' => esc_html__('Account Blocked: Your IP is blocked. Please contact system administrator.', 'ARMember'),
                'arm_block_usernames' => '',
                'arm_block_usernames_msg' => esc_html__('Username should not contain bad words.', 'ARMember'),
                'arm_block_emails' => '',
                'arm_block_emails_msg' => esc_html__('Email Address should not contain bad words.', 'ARMember'),
                'arm_block_urls' => '',
                'arm_block_urls_option' => 'message',
                'arm_block_urls_option_message' => esc_html__('Account Blocked: Your account is blocked. Please contact system administrator.', 'ARMember'),
                'arm_block_urls_option_redirect' => site_url(),
            );
            $block_settings = get_option('arm_block_settings', $default_block_settings);
            $all_block_settings = maybe_unserialize($block_settings);
            if(!is_array($all_block_settings)) {
                $all_block_settings = array();
            }
            $all_block_settings['arm_block_ips_msg'] = !empty($all_block_settings['arm_block_ips_msg']) ? stripslashes($all_block_settings['arm_block_ips_msg']) : '';
            $all_block_settings['arm_block_usernames_msg'] = !empty($all_block_settings['arm_block_usernames_msg']) ? stripslashes($all_block_settings['arm_block_usernames_msg']) : '';
            $all_block_settings['arm_block_emails_msg'] = !empty($all_block_settings['arm_block_emails_msg']) ? stripslashes($all_block_settings['arm_block_emails_msg']) : '';
            $all_block_settings['arm_block_urls_option_message'] = !empty($all_block_settings['arm_block_urls_option_message']) ? stripslashes($all_block_settings['arm_block_urls_option_message']) : '';
            $all_block_settings = apply_filters('arm_get_all_block_settings', $all_block_settings);
            return $all_block_settings;
        }

        function arm_get_parsed_block_settings() {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms;
            $parsed_block_settings = $this->arm_get_all_block_settings();
            if(is_array($parsed_block_settings))
            {
                foreach ($parsed_block_settings as $type => $val) {
                    if (!empty($val) && in_array($type, array('arm_block_ips', 'arm_block_usernames', 'arm_block_emails', 'arm_block_urls', 'arm_conditionally_block_urls_options'))) {
                        if($type == 'arm_conditionally_block_urls_options') {
                            $new_val = $val;
                        }else{
                            $new_val = array_map('strtolower', array_map('trim', explode("\n", $val)));
                        }
                        $parsed_block_settings[$type] = $new_val;
                    }
                }
            }
            $parsed_block_settings = apply_filters('arm_get_parsed_block_settings', $parsed_block_settings);
            return $parsed_block_settings;
        }

        function arm_get_all_common_message_settings() {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms;
            $arm_default_common_messages = $this->arm_default_common_messages();
            $common_message_settings = get_option('arm_common_message_settings', $arm_default_common_messages);
            $all_common_message_settings = maybe_unserialize($common_message_settings);
            $all_common_message_settings = (!empty($all_common_message_settings)) ? $all_common_message_settings : array();
            //$all_common_message_settings = shortcode_atts($arm_default_common_messages, $all_common_message_settings);
            if(!empty($all_common_message_settings) && is_array($all_common_message_settings)) {
                foreach ($all_common_message_settings as $key => $val) {
                    if(is_array($val)) {
                        foreach ($val as $k => $v) {
                            $all_common_message_settings[$key][$k] = stripslashes($v);
                        }
                    } else {
                        $all_common_message_settings[$key] = stripslashes($val);
                    }
                }
            }

            $all_common_message_settings = apply_filters('arm_get_all_common_message_settings', $all_common_message_settings);
            return $all_common_message_settings;
        }
        
        function arm_addon_activate_license_form_func($arm_lincense_activate_form)
        {
            global $armember_check_plugin_copy, $ARMember;
            $hostname = $_SERVER["SERVER_NAME"]; //phpcs:ignore
            $arm_close_btn_img_url = MEMBERSHIPLITE_IMAGES_URL . '/close.svg';
            $arm_loader_img_url =  MEMBERSHIP_IMAGES_URL  . '/loading_activation.gif';
            $arm_lincense_activate_form = '<div id="arfactlicenseform" style="display:none;">
                <div class="arfnewactmodalclose" style="float:right;text-align:right;cursor:pointer;" onclick="javascript:return false;"><img src="'. $arm_close_btn_img_url .'" align="absmiddle" width="24" height="24"/></div>
                <div class="newform_modal_title_container">
                    <div class="newform_modal_title">&nbsp;Product License</div>
                </div>';
            
            if( empty( $armember_check_plugin_copy ) )
            {
                $arm_lincense_activate_form .= '<table class="form-table">
                    <tr class="form-field">
                        <th class="arm-form-table-label">'. esc_html__('Customer Name', 'ARMember').'</th>
                        <td class="arm-form-table-content">
                            <input type="text" name="li_customer_name" id="li_customer_name" value="" autocomplete="off" />
                            <div class="arperrmessage" id="li_customer_name_error" style="display:none;">'. esc_html__('This field cannot be blank.', 'ARMember') .'</div>         
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label">'. esc_html__('Customer Email', 'ARMember').'</th>
                        <td class="arm-form-table-content">
                            <input type="text" name="li_customer_email" id="li_customer_email" value="" autocomplete="off" />
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label">'. esc_html__('Purchase Code', 'ARMember').'</th>
                        <td class="arm-form-table-content">
                            <input type="text" name="li_license_key" id="li_license_key" value="" autocomplete="off" />
                            <div class="arperrmessage" id="li_license_key_error" style="display:none;">'. esc_html__('This field cannot be blank.', 'ARMember').'</div>        
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label">'. esc_html__('Domain Name', 'ARMember').'</th>
                        <td class="arm-form-table-content">
                            <label class="lblsubtitle">'. esc_html($hostname).'</label>
                            <input type="hidden" name="li_domain_name" id="li_domain_name" value="'. esc_attr($hostname).'" autocomplete="off" />        
                        </td>
                    </tr>
                    <input type="hidden" name="receive_updates" id="receive_updates" value="0" autocomplete="off" />
                    <tr class="form-field">
                        <th class="arm-form-table-label">&nbsp;</th>
                        <td class="arm-form-table-content">
                            <span id="license_link"><button type="button" id="verify-purchase-code-addon" name="continue" style="width:150px; cursor:pointer; background-color:#53ba73; border:0px; color:#FFFFFF; height:40px; border-radius:3px;" class="greensavebtn">'. esc_html__('Activate', 'ARMember').'</button></span>
                            <span id="license_loader" style="display:none;position:absolute;margin-top:12px; padding-left:10px;"><img src="'.$arm_loader_img_url.'" height="15" /></span> 
                            <span id="license_error" style="display:none;position:absolute;margin-top:12px; padding-left:10px;">&nbsp;</span>
                            <span id="license_success" style="display:none;position:absolute;margin-top:12px;">'. esc_html__('License Activated Successfully.', 'ARMember').'</span>
                            <input type="hidden" name="ajaxurl" id="ajaxurl" value="'. esc_url(admin_url('admin-ajax.php')).'"  />        
                        </td>
                    </tr>
                    
                </table>';
                }
                else
                {   $arm_pkg_armember_content = "";
                    $form_flag = 1;
                    $arm_lincense_activate_form .= $ARMember->arm_armember_pkg_content_external($arm_pkg_armember_content, $form_flag ); //phpcs:ignore
                }
            $arm_lincense_activate_form .='</div>';
            return $arm_lincense_activate_form;
        }
        function arm_addon_activate_button_section_func($arm_addon_btn, $arm_addon_section,$plugin_installer,$arm_addon_config_url='')
        {
            global $wpdb, $ARMember, $arm_slugs, $arm_social_feature,$myplugarr, $arm_admin_mycred_feature,$arm_version,$arm_members_activity;
            $setact = 0;
            global $check_sorting;
            $setact = $arm_members_activity->$check_sorting();
            $is_addon_feature_enabled = 0;
            $arm_no_config_class = '';
            if(empty($arm_addon_config_url))
            {
                $arm_no_config_class = 'arm_no_config_feature_btn';
            }
            switch($arm_addon_section){
                case 'social':
                    $is_addon_feature_enabled = get_option( 'arm_is_social_feature' );
                    break;
                case 'pro_ration':
                    $is_addon_feature_enabled = get_option('arm_is_pro_ration_feature');
                    break;
                case 'drip_content':
                    $is_addon_feature_enabled = get_option('arm_is_drip_content_feature');
                    break;
                case 'social_login':
                    $is_addon_feature_enabled = get_option('arm_is_social_login_feature');
                    break;
                case 'pay_per_post':
                    $is_addon_feature_enabled = get_option('arm_is_pay_per_post_feature');
                    break;
                case 'coupon':
                    $is_addon_feature_enabled = get_option('arm_is_coupon_feature');
                    break;
                case 'invoice_tax':
                    $is_addon_feature_enabled = get_option('arm_is_invoice_tax_feature');
                    break;
                case 'user_private_content':
                    $is_addon_feature_enabled = get_option('arm_is_user_private_content_feature');
                    break;
                case 'multiple_membership':
                    $is_addon_feature_enabled = get_option('arm_is_multiple_membership_feature');
                    break;
                case 'plan_limit':
                    $is_addon_feature_enabled = get_option('arm_is_plan_limit_feature');
                    break;
                case 'api_service':
                    $is_addon_feature_enabled = get_option('arm_is_api_service_feature');
                    break;
                case 'buddypress':
                    $is_addon_feature_enabled = get_option('arm_is_buddypress_feature');
                    break;
                case 'woocommerce':
                    $is_addon_feature_enabled = get_option('arm_is_woocommerce_feature');
                    break;
                case 'mycred':
                    $is_addon_feature_enabled = get_option('arm_is_mycred_feature');
                    break;
                case 'gutenberg_block_restriction':
                    $is_addon_feature_enabled = get_option('arm_is_gutenberg_block_restriction_feature');
                    break;
                case 'beaver_builder_restriction':
                    $is_addon_feature_enabled = get_option('arm_is_beaver_builder_restriction_feature');
                    break;
                case 'divi_builder_restriction': 
                    $is_addon_feature_enabled = get_option('arm_is_divi_builder_restriction_feature');
                    break;
                case 'wpbakery_page_builder_restriction': 
                    $is_addon_feature_enabled = get_option('arm_is_wpbakery_page_builder_restriction_feature');
                    break;
                case 'fusion_builder_restriction': 
                    $is_addon_feature_enabled = get_option('arm_is_fusion_builder_restriction_feature');
                    break;
                case 'oxygen_builder_restriction': 
                    $is_addon_feature_enabled = get_option('arm_is_oxygen_builder_restriction_feature');
                    break;
                case 'siteorigin_builder_restriction': 
                    $is_addon_feature_enabled = get_option('arm_is_siteorigin_builder_restriction_feature');
                    break;
                case 'bricks_builder_restriction': 
                    $is_addon_feature_enabled = get_option('arm_is_bricks_builder_restriction_feature');
                    break;
                default:
                    $is_active_plugin = !empty($plugin_installer) ? is_plugin_active($plugin_installer['plugin_installer']) : 0;
                    $is_addon_feature_enabled = !empty($is_active_plugin) ? 1 : 0;
                    break;
            }

            $loader_img_url = MEMBERSHIPLITE_IMAGES_URL . '/arm_loader.gif';
            $arm_social_feature_hidden_btn = ($setact == 1 && $is_addon_feature_enabled == 1) ? 'hidden_section':'';
            if(empty($plugin_installer))
            {
                if ($setact != 1) {
                    $arm_addon_btn = '<div class="arm_feature_button_activate_wrapper '. $arm_social_feature_hidden_btn .'">
                        <a href="javascript:void(0)" class="arm_feature_activate_btn arm_feature_activation_license" data-feature_val="1" data-feature="'.$arm_addon_section.'">'. esc_html__('Activate','ARMember').'</a>
                        <span class="arm_addon_loader">
                            <svg class="arm_circular" viewBox="0 0 60 60">
                                <circle class="path" cx="25px" cy="23px" r="18" fill="none" stroke-width="4" stroke-miterlimit="7"></circle>
                            </svg>
                        </span>
                    </div>';
                } else {
                    $arm_addon_btn = '<div class="arm_feature_button_activate_wrapper '. $arm_social_feature_hidden_btn .'">
                        <a href="javascript:void(0)" class="arm_feature_activate_btn arm_feature_settings_switch arm_pro_feature" data-feature_val="1" data-feature="'.$arm_addon_section.'">'. esc_html__('Activate','ARMember') .'</a>
                        <span class="arm_addon_loader">
                            <svg class="arm_circular" viewBox="0 0 60 60">
                                <circle class="path" cx="25px" cy="23px" r="18" fill="none" stroke-width="4" stroke-miterlimit="7"></circle>
                            </svg>
                        </span>
                    </div>';
                    $is_deactivate_btn = ($is_addon_feature_enabled == 1)  ? '' : 'hidden_section';
                    $arm_addon_btn .= '<div class="arm_feature_button_deactivate_wrapper '.  $is_deactivate_btn .'">
                        <a href="javascript:void(0)" class="arm_feature_deactivate_btn arm_feature_settings_switch arm_pro_feature '.$arm_no_config_class.'" data-feature_val="0" data-feature="'. esc_attr($arm_addon_section).'">'. esc_html__( 'Deactivate', 'ARMember' ).'</a>';
                        if(empty($arm_no_config_class))
                        {
                            $arm_addon_btn .= '<a href="'.$arm_addon_config_url.'" class="arm_feature_configure_btn">'. esc_html__( 'Configure', 'ARMember' ).'</a>';
                        }
                        $arm_addon_btn .= '<span class="arm_addon_loader">
                            <svg class="arm_circular" viewBox="0 0 60 60">
                                <circle class="path" cx="25px" cy="23px" r="18" fill="none" stroke-width="4" stroke-miterlimit="7"></circle>
                            </svg>
                        </span>
                    </div>
                    ';
                }
            }
            else
            {

                $addon_resp = "";
				$addon_resp = $arm_social_feature->addons_page();
				$plugins = get_plugins();
				$installed_plugins = array();
				foreach ($plugins as $key => $plugin) {
					$is_active = is_plugin_active($key);
					$installed_plugin = array("plugin" => $key, "name" => $plugin["Name"], "is_active" => $is_active);
					$installed_plugin["activation_url"] = $is_active ? "" : wp_nonce_url("plugins.php?action=activate&plugin={$key}", "activate-plugin_{$key}");
					$installed_plugin["deactivation_url"] = !$is_active ? "" : wp_nonce_url("plugins.php?action=deactivate&plugin={$key}", "deactivate-plugin_{$key}");

					$installed_plugins[] = $installed_plugin;
				}

                $is_config = ( isset( $plugin_installer['display_config'] ) && 'yes' == $plugin_installer['display_config'] ) ? true : false;
                $config_url = isset( $plugin_installer['config_args'] ) ? admin_url( $plugin_installer['config_args'] ) : '';
                if ($setact != 1) {
                    $arm_addon_btn = '<div class="arm_feature_button_activate_wrapper ">
                        <a href="javascript:void(0)" class="arm_feature_activate_btn arm_feature_activation_license" data-feature_val="1" data-feature="'. esc_attr($plugin_installer['short_name']).'">'. esc_html__('Activate License', 'ARMember').'</a>
                        <span class="arm_addon_loader">
                            <svg class="arm_circular" viewBox="0 0 60 60">
                                <circle class="path" cx="25px" cy="23px" r="18" fill="none" stroke-width="4" stroke-miterlimit="7"></circle>
                            </svg>
                        </span>
                    </div>';
                } else {
                    $arm_addon_btn = '<div class="arm_feature_button_activate_wrapper ">';
                    $arm_addon_btn .= $arm_social_feature->CheckpluginStatus($installed_plugins, $plugin_installer['plugin_installer'], 'plugin', $plugin_installer['short_name'], $plugin_installer['plugin_type'], $plugin_installer['install_url'], $plugin_installer['armember_version'], $arm_version, $is_config, $config_url);  //phpcs:ignore
                        $arm_addon_btn .='<span class="arm_addon_loader">
                            <svg class="arm_circular" viewBox="0 0 60 60">
                                <circle class="path" cx="25px" cy="23px" r="18" fill="none" stroke-width="4" stroke-miterlimit="7"></circle>
                            </svg>
                        </span>
                    </div>';
                }
            }

            return $arm_addon_btn;
        }

        function arm_update_all_settings() {
            global $wpdb, $wp_rewrite, $ARMember, $arm_members_class, $arm_member_forms, $arm_email_settings, $arm_payment_gateways, $arm_access_rules, $arm_crons, $arm_capabilities_global, $ARMemberAllowedHTMLTagsArray;
            $response = array('type' => 'error', 'msg' => esc_html__('There is an error while updating settings, please try again.', 'ARMember'));
            $is_new_wp_admin_path = FALSE;
            $default_global_settings = $this->arm_default_global_settings();
            $old_global_settings = $this->arm_get_all_global_settings();
            
            if (isset($_POST['action']) && $_POST['action'] == 'arm_update_global_settings') { //phpcs:ignore

                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
                $posted_data = $_POST; //phpcs:ignore
                $save_all = isset($_POST['save_all']) ? $posted_data['save_all'] : '';//phpcs:ignore
             
                $_POST['arm_general_settings']['hide_register_link'] = isset($posted_data['arm_general_settings']['hide_register_link']) ? intval($posted_data['arm_general_settings']['hide_register_link']) : 0;
                $_POST['arm_general_settings']['enable_gravatar'] = isset($posted_data['arm_general_settings']['enable_gravatar']) ? intval($posted_data['arm_general_settings']['enable_gravatar']) : 0;
                $_POST['arm_general_settings']['enable_crop'] = isset($posted_data['arm_general_settings']['enable_crop']) ? intval($posted_data['arm_general_settings']['enable_crop']) : 0;
                $_POST['arm_general_settings']['spam_protection'] = isset($posted_data['arm_general_settings']['spam_protection']) ? intval($posted_data['arm_general_settings']['spam_protection']) : 0;
                $_POST['arm_general_settings']['arm_anonymous_data'] = isset($posted_data['arm_general_settings']['arm_anonymous_data']) ? intval($posted_data['arm_general_settings']['arm_anonymous_data']) : 0;
                $_POST['arm_general_settings']['enable_tax'] = isset($posted_data['arm_general_settings']['enable_tax']) ? intval($posted_data['arm_general_settings']['enable_tax']) : 0;

                $_POST['arm_general_settings']['tax_type'] = isset($posted_data['arm_general_settings']['tax_type']) ? sanitize_text_field($posted_data['arm_general_settings']['tax_type']) : 'common_tax';

                $_POST['arm_general_settings']['country_tax_field'] = isset($posted_data['arm_general_settings']['country_tax_field']) ? sanitize_text_field($posted_data['arm_general_settings']['country_tax_field']) : '';

                $_POST['arm_general_settings']["arm_tax_country_name"] = isset($posted_data['arm_general_settings']['arm_tax_country_name']) ? maybe_serialize($posted_data['arm_general_settings']['arm_tax_country_name']) : '';

                $_POST['arm_general_settings']["arm_country_tax_val"] = isset($posted_data['arm_general_settings']['arm_country_tax_val']) ? maybe_serialize($posted_data['arm_general_settings']['arm_country_tax_val']) : '';

                $_POST['arm_general_settings']["arm_country_tax_default_val"] = isset($posted_data['arm_general_settings']['arm_country_tax_default_val']) ? $posted_data['arm_general_settings']['arm_country_tax_default_val'] : 0;

                $_POST['arm_general_settings']['invc_pre_sfx_mode'] = isset($posted_data['arm_general_settings']['invc_pre_sfx_mode']) ? intval($posted_data['arm_general_settings']['invc_pre_sfx_mode']) : 0;

                $_POST['arm_general_settings']['invc_prefix_val'] = isset($posted_data['arm_general_settings']['invc_prefix_val']) ? sanitize_text_field($posted_data['arm_general_settings']['invc_prefix_val']) : '#';

                $_POST['arm_general_settings']['invc_suffix_val'] = isset($posted_data['arm_general_settings']['invc_suffix_val']) ? sanitize_text_field($posted_data['arm_general_settings']['invc_suffix_val']) : '';

                $_POST['arm_general_settings']['invc_min_digit'] = isset($posted_data['arm_general_settings']['invc_min_digit']) ? intval($posted_data['arm_general_settings']['invc_min_digit']) : 0;

                $_POST['arm_general_settings']['arm_recaptcha_site_key'] = isset($posted_data['arm_general_settings']['arm_recaptcha_site_key']) ? sanitize_text_field($posted_data['arm_general_settings']['arm_recaptcha_site_key']) : '';

                $_POST['arm_general_settings']['arm_recaptcha_private_key'] = isset($posted_data['arm_general_settings']['arm_recaptcha_private_key']) ? sanitize_text_field($posted_data['arm_general_settings']['arm_recaptcha_private_key']) : '';

                $_POST['arm_general_settings']['arm_recaptcha_theme'] = isset($posted_data['arm_general_settings']['arm_recaptcha_theme']) ? sanitize_text_field($posted_data['arm_general_settings']['arm_recaptcha_theme']) : '';
                
                $_POST['arm_general_settings']['arm_recaptcha_lang'] = isset($posted_data['arm_general_settings']['arm_recaptcha_lang']) ? sanitize_text_field($posted_data['arm_general_settings']['arm_recaptcha_lang']) : '';
                $_POST['arm_general_settings']['arm_pro_ration_method'] = isset($posted_data['arm_general_settings']['arm_pro_ration_method']) ? sanitize_text_field($posted_data['arm_general_settings']['arm_pro_ration_method']) : 'cost_base';
                $_POST['arm_general_settings']['arm_enable_reset_billing'] = isset($posted_data['arm_general_settings']['arm_enable_reset_billing']) ? sanitize_text_field($posted_data['arm_general_settings']['arm_enable_reset_billing']) : 0;
                $arm_general_settings = isset($_POST['arm_general_settings']) ? $_POST['arm_general_settings'] : array(); //phpcs:ignore
                
                $new_global_settings['general_settings'] = shortcode_atts($default_global_settings['general_settings'], $arm_general_settings);
                if ($new_global_settings['general_settings']['user_register_verification'] != 'auto') {
                    $new_global_settings['general_settings']['arm_new_signup_status'] = 3;
                }
                /* ===========================/.Rename Admin Folder Options./=========================== */
                if (!trim($this->global_settings['new_wp_admin_path'], ' /') || trim($this->global_settings['new_wp_admin_path'], ' /') == 'wp-admin') {
                    $current_wp_admin_path = 'wp-admin';
                } else {
                    $current_wp_admin_path = trim($this->global_settings['new_wp_admin_path'], ' /');
                }
                $rename_wp_admin = (isset($new_global_settings['general_settings']['rename_wp_admin'])) ? $new_global_settings['general_settings']['rename_wp_admin'] : '';
                $rename_wp_admin = empty($rename_wp_admin) ? 0 : 1;
                $new_wp_admin_path_input = (isset($new_global_settings['general_settings']['new_wp_admin_path'])) ? $new_global_settings['general_settings']['new_wp_admin_path'] : '';

                $flush_rewrite_rules = false;

                $all_saved_global_settings = maybe_unserialize(get_option('arm_global_settings'));


                $saved_rename_wp = $all_saved_global_settings['general_settings']['rename_wp_admin'];
                if (empty($saved_rename_wp)) {
                    $saved_rename_wp = 0;
                } else {
                    $saved_rename_wp = 1;
                }

                $logout = true;

                $home_root = parse_url(home_url());
                if (isset($home_root['path']))
                    $home_root = trailingslashit($home_root['path']);
                else
                    $home_root = '/';

                global $wp_rewrite;
                $config_error = false;
                $htaccess_notice = false;
                $rewritecode = '';
                $rewrite_htaccess_notice = $rewrite_config_notice = "";
                $saved_admin_path = trim($all_saved_global_settings['general_settings']['new_wp_admin_path'], '/');
                if (empty($saved_admin_path)) {
                    $saved_admin_path = 'wp-admin';
                }

                if (!trim($new_wp_admin_path_input, ' /') || trim($new_wp_admin_path_input, ' /') == 'wp-admin') {
                    $new_wp_admin_path = 'wp-admin';
                } else {
                    $new_wp_admin_path = trim($new_wp_admin_path_input, '/');
                }

                $new_wp_admin_path = !empty($new_wp_admin_path) ? $new_wp_admin_path : 'wp-admin';
                $arm_rename_wp = new ARM_rename_wp();
                $arm_rename_wp->new_wp_admin_name = $new_wp_admin_path;
                $arm_rename_wp->enable_rename_wp = $rename_wp_admin;
                $arm_rename_wp->arm_replace = array();
                $arm_rename_wp->armBuildRedirect();
                $rewrite_notice = '';

                $new_global_settings['general_settings']['new_wp_admin_path'] = $saved_admin_path;
                $new_global_settings['general_settings']['rename_wp_admin'] = $saved_rename_wp;
                $redirect_to = get_site_url($GLOBALS['blog_id'], $saved_admin_path . '/', 'admin') . 'admin.php?page=arm_general_settings';
                $home_path = $this->arm_get_home_path();
                if (!empty($rename_wp_admin) && $rename_wp_admin == 1) {
                    if ($save_all == '') {
                        if ($saved_admin_path != $new_wp_admin_path) {
                            if (!file_exists($home_path . '.htaccess') || !is_writable($home_path . '.htaccess') || !file_exists($home_path . 'wp-config.php') || !is_writable($home_path . 'wp-config.php')) {

                                $rewrites = array();
                                if (!empty($arm_rename_wp->arm_replace)) {
                                    foreach ($arm_rename_wp->arm_replace['to'] as $key => $replace) {
                                        if ($arm_rename_wp->arm_replace['rewrite'][$key]) {
                                            $rewrites[] = array(
                                                'from' => $arm_rename_wp->arm_replace['to'][$key] . '(.*)',
                                                'to' => $arm_rename_wp->arm_replace['from'][$key] . '$' . (substr_count($arm_rename_wp->arm_replace['to'][$key], '(') + 1)
                                            );
                                        }
                                    }
                                }
                                $htaccess_rewritecode = '';
                                foreach ($rewrites as $rewrite) {

                                    if (strpos($rewrite['to'], 'index.php') === false) {
                                        $htaccess_rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $home_root . $rewrite['to'] . " [QSA,L]<br />";
                                    }
                                }

                                $rewrite_notice .= '.htaccess file is not writable. You need to update your .htaccess file by adding following line before \'RewriteCond %{REQUEST_FILENAME} !-f\': <br /><code>' . $htaccess_rewritecode . '</code><br/>';

                                $config_rewritecode = "define('ADMIN_COOKIE_PATH','" . rtrim(wp_make_link_relative(network_site_url($new_wp_admin_path)), '/') . "');";
                                $rewrite_notice .= 'wp-config.php file is not writable. You need to update your wp-config.php file by adding following line <code>' . $config_rewritecode . '</code>';
                                
                                $response['type'] = 'notice';
                                $response['notice_msg'] = $rewrite_notice;
                                $logout = false;
                                echo json_encode($response);
                                die();
                            }
                        }


                        if (!empty($rename_wp_admin) && $rename_wp_admin == 1) {
                            if ($saved_admin_path != $new_wp_admin_path) {
                                if (!$arm_rename_wp->arm_rewrite_rules($wp_rewrite)) {
                                    $htaccess_notice = true;
                                    $rewrites = array();
                                    if (!empty($arm_rename_wp->arm_replace)) {
                                        foreach ($arm_rename_wp->arm_replace['to'] as $key => $replace) {
                                            if ($arm_rename_wp->arm_replace['rewrite'][$key]) {
                                                $rewrites[] = array(
                                                    'from' => $arm_rename_wp->arm_replace['to'][$key] . '(.*)',
                                                    'to' => $arm_rename_wp->arm_replace['from'][$key] . '$' . (substr_count($arm_rename_wp->arm_replace['to'][$key], '(') + 1)
                                                );
                                            }
                                        }
                                    }
                                    $htaccess_rewritecode = '';
                                    foreach ($rewrites as $rewrite) {

                                        if (strpos($rewrite['to'], 'index.php') === false) {
                                            $htaccess_rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $home_root . $rewrite['to'] . " [QSA,L]<br />";
                                        }
                                    }

                                    $rewrite_notice .= '.htaccess file is not writable. You need to update your .htaccess file by adding following line before \'RewriteCond %{REQUEST_FILENAME} !-f\': <br /><code>' . $htaccess_rewritecode . '</code><br/>';
                                }

                                if (!$this->rewrite_config_file($new_wp_admin_path)) {

                                    $config_error = true;
                                    $config_rewritecode = "define('ADMIN_COOKIE_PATH','" . rtrim(wp_make_link_relative(network_site_url($new_wp_admin_path)), '/') . "');";
                                    $rewrite_notice .= 'wp-config.php file is not writable. You need to update your wp-config.php file by adding following line <code>' . $config_rewritecode . '</code>';
                                }
                                if ($config_error == true || $htaccess_notice == true) {
                                    $response['type'] = 'notice';
                                    $response['notice_msg'] = $rewrite_notice;
                                    $logout = false;
                                    echo json_encode($response);
                                    die();
                                }
                            }
                        }
                    }

                    if ($save_all != 'cancel_all') {

                        $new_global_settings['general_settings']['new_wp_admin_path'] = $new_wp_admin_path;
                        $new_global_settings['general_settings']['rename_wp_admin'] = $rename_wp_admin;
                        $arm_rename_wp->enable_rename_wp = 1;
                        $arm_rename_wp->new_wp_admin_name = $new_wp_admin_path;
                        if ($saved_admin_path == $new_wp_admin_path) {
                            $logout = false;
                        }
                    } else {
                        $arm_rename_wp->enable_rename_wp = $saved_rename_wp;
                        $arm_rename_wp->new_wp_admin_name = $saved_admin_path;
                        $logout = false;
                    }
                } else {

                    if ($save_all == '') {
                        if ($new_wp_admin_path != 'wp-admin') {
                            if (!file_exists($home_path . '.htaccess') || !is_writable($home_path . '.htaccess') || !file_exists($home_path . 'wp-config.php') || !is_writable($home_path . 'wp-config.php')) {

                                $rewrites = array();
                                if (!empty($arm_rename_wp->arm_replace)) {
                                    foreach ($arm_rename_wp->arm_replace['to'] as $key => $replace) {
                                        if ($arm_rename_wp->arm_replace['rewrite'][$key]) {
                                            $rewrites[] = array(
                                                'from' => $arm_rename_wp->arm_replace['to'][$key] . '(.*)',
                                                'to' => $arm_rename_wp->arm_replace['from'][$key] . '$' . (substr_count($arm_rename_wp->arm_replace['to'][$key], '(') + 1)
                                            );
                                        }
                                    }
                                }
                                $htaccess_rewritecode = '';
                                foreach ($rewrites as $rewrite) {

                                    if (strpos($rewrite['to'], 'index.php') === false) {
                                        $htaccess_rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $home_root . $rewrite['to'] . " [QSA,L]<br />";
                                    }
                                }


                                $rewrite_notice .= '.htaccess file is not writable. You need to update your .htaccess file by removing following line before \'RewriteCond %{REQUEST_FILENAME} !-f\': <br /><code>' . $htaccess_rewritecode . '</code><br/>';

                                $config_rewritecode = "define('ADMIN_COOKIE_PATH','" . rtrim(wp_make_link_relative(network_site_url($new_wp_admin_path)), '/') . "');";
                                $rewrite_notice .= 'wp-config.php file is not writable. You need to update your wp-config.php file by deleting following line <code>' . $config_rewritecode . '</code>';


                                $response['type'] = 'notice';
                                $response['notice_msg'] = $rewrite_notice;
                                $logout = false;
                                echo json_encode($response);
                                die();
                            }
                        }



                        if ($saved_admin_path != 'wp-admin') {
                            require_once ABSPATH . 'wp-admin/includes/misc.php';
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                            $removeTag = sanitize_text_field($_POST['arm_general_settings']['new_wp_admin_path']) . '/(.*)';//phpcs:ignore
                            $wp_rewrite->remove_rewrite_tag($removeTag);
                            $rewrite_notice = '';
                            if (!function_exists('save_mod_rewrite_rules')) {
                                $htaccess_notice = true;
                                $rewritecode = "RewriteRule ^".sanitize_text_field($_POST['arm_general_settings']['new_wp_admin_path'])."/(.*) {$home_root}wp-admin/$1 [QSA,L]";//phpcs:ignore
                                $rewrite_notice .= '.htaccess file is not writable. You need to update your .htaccess file by remove following line from your .htaccess file <br /><code>' . $rewritecode . '</code>';
                            } else {
                                if (!save_mod_rewrite_rules()) {
                                    $htaccess_notice = true;
                                    $rewritecode = "RewriteRule ^".sanitize_text_field($_POST['arm_general_settings']['new_wp_admin_path'])."/(.*) {$home_root}wp-admin/$1 [QSA,L]";//phpcs:ignore
                                    $rewrite_notice .= '.htaccess file is not writable. You need to update your .htaccess file by remove following line from your .htaccess file <br /><code>' . $rewritecode . '</code>';
                                }
                            }
                            $redirect_to = apply_filters('admin_url', admin_url('admin.php?page=arm_general_settings'));
                            if (!$this->remove_config_file()) {
                                $config_error = true;
                                $rewritecode = "define('ADMIN_COOKIE_PATH','".sanitize_text_field($_POST['arm_general_settings']['new_wp_admin_path'])."');";//phpcs:ignore
                                $rewrite_notice .= 'wp-config.php file is not writable. You need to update your wp-config.php file by removing following line <code>' . $rewritecode . '</code>';
                            }

                            if ($htaccess_notice == true || $config_error == true) {
                                $response['type'] = 'notice';
                                $response['notice_msg'] = $rewrite_notice;
                                echo json_encode($response);
                                die();
                            }
                        }
                    }



                    if ($save_all != 'cancel_all') {


                        $new_global_settings['general_settings']['new_wp_admin_path'] = 'wp-admin';
                        $new_global_settings['general_settings']['rename_wp_admin'] = 0;
                        $arm_rename_wp->enable_rename_wp = 0;
                        $arm_rename_wp->new_wp_admin_name = 'wp-admin';
                        if ($saved_admin_path == 'wp-admin') {

                            $logout = false;
                        }
                    } else {
                        $arm_rename_wp->enable_rename_wp = $saved_rename_wp;
                        $arm_rename_wp->new_wp_admin_name = $saved_admin_path;
                        $logout = false;
                    }
                }
                /* ===========================/.End Rename Admin Folder Options./=========================== */
                if (!isset($new_global_settings['general_settings']['custom_currency']['status'])) {
                    $new_global_settings['general_settings']['custom_currency'] = array(
                        'status' => 0,
                        'symbol' => '',
                        'shortname' => '',
                        'place' => 'prefix',
                    );
                }


                //$new_global_settings['arm_specific_currency_position'] = (isset($_POST['arm_prefix_suffix_val']) && !empty($_POST['arm_prefix_suffix_val'])) ? $_POST['arm_prefix_suffix_val'] : 'suffix';


                $new_global_settings['page_settings'] = $old_global_settings['page_settings'];
                $new_global_settings['api_service'] = isset($old_global_settings['api_service']) ? $old_global_settings['api_service'] : array();
                
                $arm_exclude_role_for_hide_admin = ( isset($_POST['arm_general_settings']['arm_exclude_role_for_hide_admin']) && !empty($_POST['arm_general_settings']['arm_exclude_role_for_hide_admin']) )? implode(',',$_POST['arm_general_settings']['arm_exclude_role_for_hide_admin']) : ''; //phpcs:ignore
                $new_general_settings['arm_exclude_role_for_hide_admin'] = $arm_exclude_role_for_hide_admin;

                // set old global setting because its updated from other page
                $new_global_settings['general_settings']['arm_invoice_template'] = $old_global_settings['general_settings']['arm_invoice_template'];
                $new_global_settings['general_settings']['arm_exclude_role_for_restrict_admin'] = isset($old_global_settings['general_settings']['arm_exclude_role_for_restrict_admin']) ? $old_global_settings['general_settings']['arm_exclude_role_for_restrict_admin'] : '';
                $new_global_settings['general_settings']['restrict_admin_panel'] = isset($old_global_settings['general_settings']['restrict_admin_panel']) ? $old_global_settings['general_settings']['restrict_admin_panel'] : 0;
                $new_global_settings['general_settings']['hide_feed'] = isset($old_global_settings['general_settings']['hide_feed']) ? $old_global_settings['general_settings']['hide_feed'] : 0;
                $new_global_settings['general_settings']['restrict_site_access'] = isset($old_global_settings['general_settings']['restrict_site_access']) ? $old_global_settings['general_settings']['restrict_site_access'] : 0;
                $new_global_settings['general_settings']['arm_pay_per_post_buynow_var'] = isset($old_global_settings['general_settings']['arm_pay_per_post_buynow_var']) ? $old_global_settings['general_settings']['arm_pay_per_post_buynow_var'] : '';
                $new_global_settings['general_settings']['arm_pay_per_post_allow_fancy_url'] = isset($old_global_settings['general_settings']['arm_pay_per_post_allow_fancy_url']) ? $old_global_settings['general_settings']['arm_pay_per_post_allow_fancy_url'] : '';
                $new_global_settings['general_settings']['arm_pay_per_post_default_content'] = isset($old_global_settings['general_settings']['arm_pay_per_post_default_content']) ? $old_global_settings['general_settings']['arm_pay_per_post_default_content'] : '';
                $new_global_settings['page_settings']['guest_page_id'] = isset($old_global_settings['page_settings']['guest_page_id']) ? $old_global_settings['page_settings']['guest_page_id'] : 0;
                $new_global_settings['page_settings']['arm_access_page_for_restrict_site'] = isset($old_global_settings['page_settings']['arm_access_page_for_restrict_site']) ? $old_global_settings['page_settings']['arm_access_page_for_restrict_site'] : '';
                
                
                $new_global_settings = apply_filters('arm_before_update_global_settings', $new_global_settings, $_POST);//phpcs:ignore

                /* -------- Update Email Schedular Start ------- */
                $arm_old_general_settings = $old_global_settings['general_settings'];
                $arm_old_email_schedular = isset($arm_old_general_settings['arm_email_schedular_time']) ? $arm_old_general_settings['arm_email_schedular_time'] : 0;

                if (!empty($new_global_settings['general_settings']['arm_email_schedular_time']) && $arm_old_email_schedular != $new_global_settings['general_settings']['arm_email_schedular_time']) {
                    $arm_all_crons = $arm_crons->arm_get_cron_hook_names();
                    
                 
                    foreach ($arm_all_crons as $arm_cron_hook_name) {
                        $arm_crons->arm_clear_cron($arm_cron_hook_name);
                    }
                }
                /* -------- Update Email Schedular End------- */              
                
                update_option('arm_global_settings', $new_global_settings);

                $arm_email_settings->arm_update_email_settings();
                $arm_payment_gateways->arm_update_payment_gate_status($_POST['arm_general_settings']['paymentcurrency']);//phpcs:ignore
                $response = array('type' => 'success', 'msg' => esc_html__('Global Settings Saved Successfully.', 'ARMember'));
                if (isset($redirect_to) && $redirect_to != '') {
                    if (!$logout) {
                        $response['url'] = $redirect_to;
                    } else {
                        wp_destroy_current_session();
                        wp_clear_auth_cookie();
                        $response['url'] = wp_login_url();
                    }
                } else {
                    if ($htaccess_notice) {
                        $response['notice'] = true;
                        $response['notice_msg'] = $rewrite_htaccess_notice;
                    } else if ($config_error) {
                        $response['config_notice'] = true;
                        $response['config_notice_msg'] = $rewrite_config_notice;
                    }
                }
                update_option('arm_recaptcha_notice_flag','2');
            }
            if (isset($_POST['action']) && $_POST['action'] == 'arm_page_settings') { //phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
                $default_global_settings = $this->arm_default_global_settings();
                $arm_page_settings = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST['arm_page_settings'] );//phpcs:ignore
                $old_page_settings = shortcode_atts($default_global_settings['page_settings'], $old_global_settings['page_settings']);
                $new_global_settings['page_settings'] = shortcode_atts($old_page_settings, $arm_page_settings);

                if(isset($_POST['arm_page_settings']['paid_post_page_id']) && !empty($_POST['arm_page_settings']['paid_post_page_id']))//phpcs:ignore
                {
                    $new_global_settings['page_settings']['paid_post_page_id'] = $arm_page_settings['paid_post_page_id'];
                }

                $new_global_settings['api_service'] = isset($old_global_settings['api_service']) ? $old_global_settings['api_service'] : array();
                $new_global_settings['general_settings'] = $old_global_settings['general_settings'];
                $new_global_settings = apply_filters('arm_before_update_page_settings', $new_global_settings, $_POST);//phpcs:ignore
                update_option('arm_global_settings', $new_global_settings);
                $this->arm_user_rewrite_rules();
                $wp_rewrite->flush_rules(false);
                $response = array('type' => 'success', 'msg' => esc_html__('Page Settings Saved Successfully.', 'ARMember'));
            }
            if (isset($_POST['action']) && $_POST['action'] == 'arm_update_block_settings') { //phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
                $post_block_settings = $_POST['arm_block_settings']; //phpcs:ignore
                $post_block_settings['failed_login_lockdown'] = isset($post_block_settings['failed_login_lockdown']) ? intval($post_block_settings['failed_login_lockdown']) : 0;
                $post_block_settings['remained_login_attempts'] = isset($post_block_settings['remained_login_attempts']) ? intval($post_block_settings['remained_login_attempts']) : 0;
                $post_block_settings['track_login_history'] = isset($post_block_settings['track_login_history']) ? intval($post_block_settings['track_login_history']) : 0;
                $arm_block_ips = implode(PHP_EOL, array_filter(array_map('trim', explode(PHP_EOL, $post_block_settings['arm_block_ips']))));
                $arm_block_usernames = implode(PHP_EOL, array_filter(array_map('trim', explode(PHP_EOL, $post_block_settings['arm_block_usernames']))));
                $arm_block_emails = implode(PHP_EOL, array_filter(array_map('trim', explode(PHP_EOL, $post_block_settings['arm_block_emails']))));
                $arm_block_urls = implode(PHP_EOL, array_filter(array_map('trim', explode(PHP_EOL, $post_block_settings['arm_block_urls']))));
                $conditionally_block_urls = isset($post_block_settings['arm_conditionally_block_urls'])?$post_block_settings['arm_conditionally_block_urls']:0;
                if($conditionally_block_urls == 1){
                    $conditionally_block_urls_options = array();
                    $condition_count = 0;
                    foreach($post_block_settings['arm_conditionally_block_urls_options'] as $condition){
                        $condition_count++;
                        $conditionally_block_urls_options[$condition_count]['plan_id'] = $condition['plan_id'];
                        $arm_block_url = implode(PHP_EOL, array_filter(array_map('trim', explode(PHP_EOL, $condition['arm_block_urls']))));
                        $conditionally_block_urls_options[$condition_count]['arm_block_urls'] = $arm_block_url;
                    }
                }
                
                $is_update = true;
                if ($is_update == true) {
                    $post_block_settings['arm_block_ips'] = $arm_block_ips;
                    $post_block_settings['arm_block_usernames'] = $arm_block_usernames;
                    $post_block_settings['arm_block_emails'] = $arm_block_emails;
                    $post_block_settings['arm_block_urls'] = $arm_block_urls;
                    if($conditionally_block_urls == 1) {
                        $post_block_settings['arm_conditionally_block_urls'] = $conditionally_block_urls;
                        $post_block_settings['arm_conditionally_block_urls_options'] = $conditionally_block_urls_options;
                    }
                    
                    $post_block_settings = apply_filters('arm_before_update_block_settings', $post_block_settings, $_POST);//phpcs:ignore

                    update_option('arm_block_settings', $post_block_settings);

                    $response = array('type' => 'success', 'msg' => esc_html__('Settings Saved Successfully.', 'ARMember'));
                } else {
                    $response = array('type' => 'error', 'msg' => esc_html__('Some of users are having administrator previlegs. So those cant be block.', 'ARMember'));
                }
            }
            if (isset($_POST['action']) && $_POST['action'] == 'arm_update_redirect_settings') { //phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
                $post_redirection_settings = array_map( array( $ARMember, 'arm_recursive_sanitize_data_extend_only_kses'), $_POST['arm_redirection_settings'] );//phpcs:ignore
                
                $default_redirection_url = $post_redirection_settings['login']['conditional_redirect']['default'];
                unset($post_redirection_settings['login']['conditional_redirect']['default']);
                
                $post_redirection_settings['login']['conditional_redirect'] = array_values($post_redirection_settings['login']['conditional_redirect']);
                $post_redirection_settings['login']['conditional_redirect']['default'] = $default_redirection_url;  
                $is_update = true;
                if ($is_update == true) {
                    $post_redirection_settings = apply_filters('arm_before_update_redirection_settings', $post_redirection_settings, $_POST);//phpcs:ignore
                    update_option('arm_redirection_settings', $post_redirection_settings);
                    $response = array('type' => 'success', 'msg' => esc_html__('Settings Saved Successfully.', 'ARMember'));
                } else {
                    $response = array('type' => 'error', 'msg' => esc_html__('Some of users are having administrator previlegs. So those cant be block.', 'ARMember'));
                }
            }
          
            if ( isset( $_POST['action'] ) && $_POST['action'] == 'arm_update_common_message_settings' ) { //phpcs:ignore

                if(isset($_POST['common_message_post_data'])){
                    $_POST = json_decode(stripslashes($_POST['common_message_post_data']),true);
                }
                
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
                $common_messages = !empty($_POST['arm_common_message_settings']) ? array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST['arm_common_message_settings'] ) : array(); //phpcs:ignore
                $common_messages = apply_filters('arm_before_update_common_message_settings', $common_messages, $_POST);//phpcs:ignore
                
                $arm_default_common_messages = $this->arm_default_common_messages();
                $common_message = shortcode_atts($arm_default_common_messages, $common_messages);

                if(!empty($common_messages) && is_array($common_messages) )
				{
					foreach($common_messages as $common_message_key => $common_message_val)
					{
						$common_message_key = wp_kses($common_message_key, $ARMemberAllowedHTMLTagsArray);
						$common_messages[$common_message_key] = wp_kses($common_message_val, $ARMemberAllowedHTMLTagsArray);
					}
					update_option( 'arm_common_message_settings', $common_messages );
				}
                $response = array(
					'type' => 'success',
					'msg'  => esc_html__( 'Settings Saved Successfully.', 'ARMember' ),
				);
            }
            
            if (isset($_POST['action']) && $_POST['action'] == 'arm_update_invoice_settings') { //phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce

                $default_global_settings = $this->arm_default_global_settings();
                $arm_invoice_template = isset($_POST['arm_general_settings']['arm_invoice_template']) ? $_POST['arm_general_settings']['arm_invoice_template'] : $old_global_settings['general_settings']['arm_invoice_template']; //phpcs:ignore
                $new_general_settings = shortcode_atts($default_global_settings['general_settings'], $old_global_settings['general_settings']);
                $new_general_settings['arm_invoice_template'] = $arm_invoice_template;
                $new_global_settings['page_settings'] = $old_global_settings['page_settings'];
                $new_global_settings['general_settings'] = $new_general_settings;
                $new_global_settings = apply_filters('arm_before_update_invoice_settings', $new_global_settings, $_POST);//phpcs:ignore
                $new_global_settings['api_service'] = isset($old_global_settings['api_service']) ? $old_global_settings['api_service'] : array();
                update_option('arm_global_settings', $new_global_settings);
                $response = array('type' => 'success', 'msg' => esc_html__('Global Settings Saved Successfully.', 'ARMember'));
            }
            
            if (isset($_POST['action']) && $_POST['action'] == 'arm_update_access_restriction_settings') { //phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
                $posted_general_setting = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST );//phpcs:ignore
                $default_global_settings = $this->arm_default_global_settings();
                $restrict_admin_panel = isset($posted_general_setting['arm_general_settings']['restrict_admin_panel']) ? intval($posted_general_setting['arm_general_settings']['restrict_admin_panel']) : 0;
                $arm_exclude_role_for_restrict_admin = ( isset($posted_general_setting['arm_general_settings']['arm_exclude_role_for_restrict_admin']) && !empty($posted_general_setting['arm_general_settings']['arm_exclude_role_for_restrict_admin']) )? implode(',',$posted_general_setting['arm_general_settings']['arm_exclude_role_for_restrict_admin']) : '';
                $hide_feed = isset($posted_general_setting['arm_general_settings']['hide_feed']) ? intval($posted_general_setting['arm_general_settings']['hide_feed']) : 0;
                $restrict_site_access = isset($posted_general_setting['arm_general_settings']['restrict_site_access']) ? intval($posted_general_setting['arm_general_settings']['restrict_site_access']) : 0;
                
                $new_general_settings = shortcode_atts($default_global_settings['general_settings'], $old_global_settings['general_settings']);
                $new_general_settings['restrict_admin_panel'] = $restrict_admin_panel;
                $new_general_settings['arm_exclude_role_for_restrict_admin'] = $arm_exclude_role_for_restrict_admin;
                $new_general_settings['hide_feed'] = $hide_feed;
                $new_general_settings['restrict_site_access'] = $restrict_site_access;
                
                $new_global_settings['page_settings'] = $old_global_settings['page_settings'];
                $new_global_settings['page_settings']['guest_page_id'] = isset($posted_general_setting['arm_page_settings']['guest_page_id']) ? intval($posted_general_setting['arm_page_settings']['guest_page_id']) : 0;
                $new_global_settings['page_settings']['arm_access_page_for_restrict_site'] = (isset($posted_general_setting['arm_general_settings']['arm_access_page_for_restrict_site']) && !empty($posted_general_setting['arm_general_settings']['arm_access_page_for_restrict_site'])) ? implode(',',$posted_general_setting['arm_general_settings']['arm_access_page_for_restrict_site']) : '';


                $new_general_settings['arm_allow_drip_expired_plan'] = isset($posted_general_setting['arm_general_settings']['arm_allow_drip_expired_plan']) ? intval($posted_general_setting['arm_general_settings']['arm_allow_drip_expired_plan']) : 0;

                $new_general_settings['arm_allow_drip_expired_plan_is_sync'] = isset($posted_general_setting['arm_general_settings']['arm_allow_drip_expired_plan_is_sync']) ? intval($posted_general_setting['arm_general_settings']['arm_allow_drip_expired_plan_is_sync']) : 0;

                $new_general_settings['arm_drip_restrict_old_posts'] = isset($posted_general_setting['arm_general_settings']['arm_drip_restrict_old_posts']) ? intval($posted_general_setting['arm_general_settings']['arm_drip_restrict_old_posts']) : 0;
                
                
                $new_global_settings['general_settings'] = $new_general_settings;
                $new_global_settings = apply_filters('arm_before_update_access_restriction_settings', $new_global_settings, $posted_general_setting);
                update_option('arm_global_settings', $new_global_settings);
                $arm_access_rules->arm_update_default_access_rules();
                $response = array('type' => 'success', 'msg' => esc_html__('Global Settings Saved Successfully.', 'ARMember'));
            }

            if (isset($_POST['action']) && $_POST['action'] == 'arm_update_pay_per_post_settings') { //phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce

                $default_global_settings = $this->arm_default_global_settings();
                $arm_pay_per_post_default_content = isset($_POST['arm_general_settings']['arm_pay_per_post_default_content']) ? $_POST['arm_general_settings']['arm_pay_per_post_default_content'] : $old_global_settings['general_settings']['arm_pay_per_post_default_content']; //phpcs:ignore
                $new_general_settings = shortcode_atts($default_global_settings['general_settings'], $old_global_settings['general_settings']);

                $new_general_settings['arm_pay_per_post_buynow_var'] = isset($_POST['arm_general_settings']['arm_pay_per_post_buynow_var']) ? $_POST['arm_general_settings']['arm_pay_per_post_buynow_var'] : ''; //phpcs:ignore
                $new_general_settings['arm_pay_per_post_allow_fancy_url'] = isset($_POST['arm_general_settings']['arm_pay_per_post_allow_fancy_url']) ? $_POST['arm_general_settings']['arm_pay_per_post_allow_fancy_url'] : ''; //phpcs:ignore


                $new_general_settings['arm_pay_per_post_default_content'] = $arm_pay_per_post_default_content;
                $new_global_settings['page_settings'] = $old_global_settings['page_settings'];
                $new_global_settings['general_settings'] = $new_general_settings;
                $new_global_settings = apply_filters('arm_before_save_fields_paid_post', $new_global_settings, $_POST);//phpcs:ignore
                $new_global_settings['api_service'] = isset($old_global_settings['api_service']) ? $old_global_settings['api_service'] : array();
                update_option('arm_global_settings', $new_global_settings);
                $response = array('type' => 'success', 'msg' => esc_html__('Global Settings Saved Successfully.', 'ARMember'));
            }

            if (isset($_POST['action']) && $_POST['action'] == 'arm_update_api_service_feature') { //phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
                $default_global_settings = $this->arm_default_global_settings();
                $new_general_settings = shortcode_atts($default_global_settings['general_settings'], $old_global_settings['general_settings']);
                $api_service['arm_api_service_security_key'] = isset($_POST['arm_general_settings']['arm_api_security_key']) ? sanitize_text_field($_POST['arm_general_settings']['arm_api_security_key']) : '';//phpcs:ignore

                $api_service['arm_list_membership_plans'] = isset($_POST['arm_general_settings']['arm_list_membership_plans']) ? intval($_POST['arm_general_settings']['arm_list_membership_plans']) : 0;//phpcs:ignore
                $api_service['arm_membership_plan_details'] = isset($_POST['arm_general_settings']['arm_membership_plan_details']) ? intval($_POST['arm_general_settings']['arm_membership_plan_details']) : 0;//phpcs:ignore
                $api_service['arm_member_details'] = isset($_POST['arm_general_settings']['arm_member_details']) ? intval($_POST['arm_general_settings']['arm_member_details']) : 0;//phpcs:ignore
                $api_service['arm_member_memberships'] = isset($_POST['arm_general_settings']['arm_member_memberships']) ? intval($_POST['arm_general_settings']['arm_member_memberships']) : 0;//phpcs:ignore
                $api_service['arm_member_paid_posts'] = isset($_POST['arm_general_settings']['arm_member_paid_posts']) ? intval($_POST['arm_general_settings']['arm_member_paid_posts']) : 0;//phpcs:ignore
                $api_service['arm_member_payments'] = isset($_POST['arm_general_settings']['arm_member_payments']) ? intval($_POST['arm_general_settings']['arm_member_payments']) : 0;//phpcs:ignore
                $api_service['arm_member_paid_post_payments'] = isset($_POST['arm_general_settings']['arm_member_paid_post_payments']) ? intval($_POST['arm_general_settings']['arm_member_paid_post_payments']) : 0;//phpcs:ignore
                $api_service['arm_check_coupon_code'] = isset($_POST['arm_general_settings']['arm_check_coupon_code']) ? intval($_POST['arm_general_settings']['arm_check_coupon_code']) : 0;//phpcs:ignore
                $api_service['arm_member_add_membership'] = isset($_POST['arm_general_settings']['arm_member_add_membership']) ? intval($_POST['arm_general_settings']['arm_member_add_membership']) : 0;//phpcs:ignore
                $api_service['arm_create_transaction'] = isset($_POST['arm_general_settings']['arm_create_transaction']) ? intval($_POST['arm_general_settings']['arm_create_transaction']) : 0;//phpcs:ignore
                $api_service['arm_member_cancel_membership'] = isset($_POST['arm_general_settings']['arm_member_cancel_membership']) ? intval($_POST['arm_general_settings']['arm_member_cancel_membership']) : 0;//phpcs:ignore
                $api_service['arm_check_member_membership'] = isset($_POST['arm_general_settings']['arm_check_member_membership']) ? intval($_POST['arm_general_settings']['arm_check_member_membership']) : 0;//phpcs:ignore

                $new_global_settings['api_service'] = $api_service;
                $new_global_settings['page_settings'] = $old_global_settings['page_settings'];
                $new_global_settings['general_settings'] = $new_general_settings;
                update_option('arm_global_settings', $new_global_settings);
                $response = array('type' => 'success', 'msg' => esc_html__('API settings Saved Successfully.', 'ARMember'));
            }
            echo arm_pattern_json_encode($response);
            die();
        }

        function arm_manipulate_invoice_id($org_invoice_id) {
            $invoice_id = !empty($org_invoice_id) ? $org_invoice_id : 0;
            $invc_prefix = '#';
            $invc_suffix = "";
            $arm_invoice_tax_feature = get_option('arm_is_invoice_tax_feature', 0);
            if($arm_invoice_tax_feature == 1 && isset($this->global_settings["invc_pre_sfx_mode"]) && $this->global_settings["invc_pre_sfx_mode"] == 1 && !empty($invoice_id)) {
                $invc_prefix = isset($this->global_settings["invc_prefix_val"]) ? $this->global_settings["invc_prefix_val"] : $invc_prefix;
                $invc_suffix = isset($this->global_settings["invc_suffix_val"]) ? $this->global_settings["invc_suffix_val"] : '';
                $invc_min_digit = isset($this->global_settings["invc_min_digit"]) ? $this->global_settings["invc_min_digit"] : 0;
                if($invc_min_digit > 0) {
                    $invoice_id = str_pad($invoice_id, $invc_min_digit, "0", STR_PAD_LEFT);
                }
            }
            $new_invoice_id = $invc_prefix . $invoice_id . $invc_suffix;
            return $new_invoice_id;
        }

        function rewrite_config_file($url) {

            global $ARMember;
            if (file_exists(ABSPATH . 'wp-config.php')) {
                $global_config_file = ABSPATH . 'wp-config.php';
            } else {
                $global_config_file = dirname(ABSPATH) . '/wp-config.php';
            }
            if (is_multisite()) {
                $line = '';
            } else {
                if (ADMIN_COOKIE_PATH <> rtrim(wp_make_link_relative(network_site_url($url)), '/')) {
                    $line = 'define( \'ADMIN_COOKIE_PATH\', \'' . rtrim(wp_make_link_relative(network_site_url($url)), '/') . '\' );';
                }
            }

            if (isset($line)) {
                if (!is_writable($global_config_file) || !$this->arm_replace_cookie_path_line('define *\( *\'ADMIN_COOKIE_PATH\'', $line, $global_config_file)) {
                    return false;
                }
            }
            return true;
        }

        function remove_config_file() {
            if (file_exists(ABSPATH . 'wp-config.php')) {
                $global_config_file = ABSPATH . 'wp-config.php';
            } else {
                $global_config_file = dirname(ABSPATH) . '/wp-config.php';
            }
            if (!is_writable($global_config_file) || !$this->arm_replace_cookie_path_line('define *\( *\'ADMIN_COOKIE_PATH\'', '', $global_config_file)) {
                return false;
            }
            return true;
        }

        function arm_replace_cookie_path_line($old, $new, $file) {
            global $ARMember;
            if (@is_file($file) == false) {
                return false;
            }

            $found = false;
            $lines = file($file);
            foreach ((array) $lines as $line) {
                if (preg_match("/$old/", $line)) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $fd = fopen($file, 'w');
                foreach ((array) $lines as $line) {
                    if (!preg_match("/$old/", $line))
                        fputs($fd, $line);
                    elseif ($new != '') {
                        fputs($fd, "$new //Added by ARMember\r\n");
                    }
                }
                fclose($fd);
                return true;
            }

            $fd = fopen($file, 'w');
            $done = false;
            foreach ((array) $lines as $line) {
                if ($done || !preg_match('/^(if\ \(\ \!\ )?define|\$|\?>/', $line)) {
                    fputs($fd, $line);
                } else {
                    if ($new != '') {
                        fputs($fd, "$new //Added by ARMember\r\n");
                    }
                    fputs($fd, $line);
                    $done = true;
                }
            }
            fclose($fd);
            return true;
        }


        function remove_loginpage_label_text($text) {
            $remove_txts = array(
                'username', 'username:', 'username *',
                'username or email', 'username or email address', 'username or email address *',
                'password', 'my password:', 'password *',
                'e-mail', 'email address *', 'email address',
                'first name *',
                'last name *',
                'email'
            );
            if (in_array(strtolower($text), $remove_txts)) {
                $text = '';
            }
            if ($text == 'Remember Me') {
                $text = 'Remember';
            }
            return $text;
        }

        function arm_wp_login_logo_url($url)
        {
            return 'https://www.armemberplugin.com';
        }

        function arm_remove_registration_link($value) {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $pagenow;
            $hideRegister = isset($this->global_settings['hide_register_link']) ? $this->global_settings['hide_register_link'] : 0;
            if ($hideRegister == 1) {
                $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
                if ($pagenow == 'wp-login.php' && $action != 'register') {
                    $value = false;
                }
            }
            return $value;
        }

        function arm_modify_lost_password_link($lost_password_link) {
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $pagenow;
            $forgot_password_page_id = isset($this->global_settings['forgot_password_page_id']) ? $this->global_settings['forgot_password_page_id'] : 0;
            if ( !empty( $forgot_password_page_id ) ) {
                $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
                if ($pagenow == 'wp-login.php' && ( $action != 'register' || $action != 'lostpassword' ) ) {
                    $lost_password_link = false;
                }
            }
            return $lost_password_link;
        }

        function arm_login_enqueue_assets() {
            global $arm_global_settings, $ARMember;
            $all_global_settings = $arm_global_settings->arm_get_all_global_settings();
            $general_settings = $all_global_settings['general_settings'];
            $disable_wp_login_style = isset($general_settings['disable_wp_login_style']) ? $general_settings['disable_wp_login_style'] : 0;
            if($disable_wp_login_style == 0)
            {
                wp_enqueue_style('arm_wp_login', MEMBERSHIPLITE_URL . '/css/arm_wp_login.css', array(), MEMBERSHIP_VERSION);
                ?>
                <script data-cfasync="false" type="text/javascript">
                    jQuery.fn.outerHTML = function (s) {
                        return s ? this.before(s).remove() : jQuery("<p>").append(this.eq(0).clone()).html();
                    };
                    jQuery(function ($) {
                        jQuery('input[type=text]').each(function (e) {
                            var label = jQuery(this).parents('label').text().replace('*', '');
                            jQuery(this).attr('placeholder', label);
                        });
                        jQuery('input#user_login').attr('placeholder', 'Username').attr('autocomplete', 'off');
                        jQuery('input#user_email').attr('placeholder', 'E-mail').attr('autocomplete', 'off');
                        jQuery('input#user_pass').attr('placeholder', 'Password').attr('autocomplete', 'off');
                        jQuery('input[type=checkbox]').each(function () {
                            var input_box = jQuery(this).outerHTML();
                            jQuery(this).replaceWith('<span class="arm_input_checkbox">' + input_box + '</span>');
                        });
                        jQuery('input[type=checkbox]').on('change', function () {
                            if (jQuery(this).is(':checked')) {
                                jQuery(this).closest('.arm_input_checkbox').addClass('arm_input_checked');
                            } else {
                                jQuery(this).closest('.arm_input_checkbox').removeClass('arm_input_checked');
                            }
                        });
                    });
                </script>
                <?php
            }
        }

        public function add_query_arg() {
            $args = func_get_args();
            if (is_array($args[0])) {
                if (count($args) < 2 || false === $args[1]) {
                    $uri = $_SERVER['REQUEST_URI']; //phpcs:ignore
                } else {
                    $uri = $args[1];
                }
            } else {
                if (count($args) < 3 || false === $args[2]) {
                    $uri = $_SERVER['REQUEST_URI']; //phpcs:ignore
                } else {
                    $uri = $args[2];
                }
            }
            if ($frag = strstr($uri, '#')) {
                $uri = substr($uri, 0, -strlen($frag));
            } else {
                $frag = '';
            }

            if (0 === stripos($uri, 'http://')) {
                $protocol = 'http://';
                $uri = substr($uri, 7);
            } elseif (0 === stripos($uri, 'https://')) {
                $protocol = 'https://';
                $uri = substr($uri, 8);
            } else {
                $protocol = '';
            }

            if (strpos($uri, '?') !== false) {
                list( $base, $query ) = explode('?', $uri, 2);
                $base .= '?';
            } elseif ($protocol || strpos($uri, '=') === false) {
                $base = $uri . '?';
                $query = '';
            } else {
                $base = '';
                $query = $uri;
            }
            wp_parse_str($query, $qs);
            $qs = urlencode_deep($qs); /* This re-URL-encodes things that were already in the query string */
            if (is_array($args[0])) {
                $kayvees = $args[0];
                $qs = array_merge($qs, $kayvees);
            } else {
                $qs[$args[0]] = $args[1];
            }
            foreach ($qs as $k => $v) {
                if ($v === false) {
                    unset($qs[$k]);
                }
            }
            $ret = build_query($qs);
            $ret = trim($ret, '?');
            $ret = preg_replace('#=(&|$)#', '$1', $ret);
            $ret = $protocol . $base . $ret . $frag;
            $ret = rtrim($ret, '?');
            $ret = esc_url_raw($ret);
            return $ret;
        }

        public function handle_return_messages($errors = '', $message = '') {
            global $wpdb, $ARMember, $arm_members_class;
            $type = 'error';
            $return = '';
            if (!empty($errors)) {
                if (isset($errors) && is_array($errors) && count($errors) > 0) {
                    foreach ($errors as $error) {
                        $return .= '<div>' . stripslashes($error) . '</div>';
                    }
                }
            } elseif (isset($message) && $message != '') {
                $type = 'success';
                $return = $message;
            } else {
                $return = false;
            }
            return array('type' => $type, 'msg' => $return);
        }

        public function get_param($param, $default = '', $src = 'get') {
            if (strpos($param, '[')) {
                $params = explode('[', $param);
                $param = $params[0];
            }
            global $ARMember; 
            
            $str = isset($_POST['form']) ? stripslashes_deep( $_POST['form'] ) : ''; //phpcs:ignore
            $str = json_decode( $str, true );
            $str = !empty($str) ? $str : array();
            $str = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $str ); //phpcs:ignore

            if ($src == 'get') {
                if(isset($_POST[$param]))  //phpcs:ignore
                {
                    $value = stripslashes_deep($_POST[$param]);  //phpcs:ignore
                }
                else if(isset($str[$param]))
                {
                    $value = stripslashes_deep($str[$param]);
                }
                else if(isset($_GET[$param]))
                {
                    $value = stripslashes_deep($_GET[$param]);  //phpcs:ignore
                }
                else {
                    $value = $default;
                }

                if ((!isset($_POST[$param]) or ! isset($str[$param])) and isset($_GET[$param]) and ! is_array($value)) {//phpcs:ignore
                    $value = urldecode($value);
                }
            } else {
                if(isset($_POST[$param])) {  //phpcs:ignore
                    $value = stripslashes_deep(maybe_unserialize($_POST[$param])); //phpcs:ignore
                } else if(isset($str[$param])) {
                    $value = stripslashes_deep(maybe_unserialize($str[$param]));
                } else {
                    $value = $default;
                }
            }

            if (isset($params) and is_array($value) and ! empty($value)) {
                foreach ($params as $k => $p) {
                    if (!$k or ! is_array($value)) {
                        continue;
                    }
                    $p = trim($p, ']');
                    $value = (isset($value[$p])) ? $value[$p] : $default;
                }
            }
            return $value;
        }

        public function get_unique_key($name = '', $table_name = '', $column = '', $id = 0, $num_chars = 8) {
            global $wpdb;
            $key = '';
            if (!empty($name)) {
                if (function_exists('sanitize_key'))
                    $key = sanitize_key($name);
                else
                    $key = sanitize_title_with_dashes($name);
            }
            if (empty($key)) {
                $max_slug_value = pow(36, $num_chars);
                $min_slug_value = 37;
                $key = base_convert(rand($min_slug_value, $max_slug_value), 10, 36);
            }

            if (!empty($table_name)) {
                $key_check = $wpdb->get_var($wpdb->prepare("SELECT $column FROM `$table_name` WHERE `$column` = %s", $key)); //phpcs:ignore --Reason: $table_name is a table name
                if ($key_check or is_numeric($key_check)) {
                    $suffix = 2;
                    do {
                        $alt_post_name = substr($key, 0, 200 - (strlen($suffix) + 1)) . "$suffix";
                        $key_check = $wpdb->get_var($wpdb->prepare("SELECT $column FROM `$table_name` WHERE `$column` = %s", $alt_post_name, $id));//phpcs:ignore --Reason: $table_name is a table name
                        $suffix++;
                    } while ($key_check || is_numeric($key_check));
                    $key = $alt_post_name;
                }
            }
            return $key;
        }

        public function armStringMatchWithWildcard($source, $pattern) {
            $pattern = preg_quote($pattern, '/');
            $pattern = str_replace('\*', '.*', $pattern);
            return preg_match('/^' . $pattern . '$/i', $source);
        }

        public function arm_find_url_match($check_url = '', $urls = array()) {
            global $wp, $wpdb, $arm_errors;
            if (!empty($check_url) && !empty($urls)) {
                if (!preg_match('#^http(s)?://#', $check_url)) {
                    $check_url = 'http://' . $check_url;
                }
                $parse_check_url = parse_url($check_url);
                $parse_check_url['path'] = (isset($parse_check_url['path'])) ? $parse_check_url['path'] : '';
                $parse_check_url['query'] = (isset($parse_check_url['query'])) ? $parse_check_url['query'] : '';
                foreach ($urls as $url) {
                    $check_wildcard = explode('*', $url);
                    $wildcard_count = substr_count($url, '*');
                    if ($wildcard_count > 0) {
                        if ($this->armStringMatchWithWildcard($check_url, $url)) {
                            return TRUE;
                        }
                        if ($this->armStringMatchWithWildcard($check_url, $url . '/')) {
                            return TRUE;
                        }
                    } else {
                        if (!preg_match('/^http(s)?:\/\//', $url)) {
                            $url = 'http://' . $url;
                        }
                        $parse_url = parse_url($url);
                        $parse_url = is_array($parse_url) ? $parse_url : array();
                        $parse_url['path'] = (isset($parse_url['path'])) ? $parse_url['path'] : '';
                        $parse_url['query'] = (isset($parse_url['query'])) ? $parse_url['query'] : '';
                        /* Compare URL Details. */
                        $diff = array_diff($parse_check_url, $parse_url);
                        if ($parse_check_url['path'] == $parse_url['path']) {
                            if (isset($parse_check_url['query']) || isset($parse_url['query'])) {
                                if ($parse_check_url['query'] == $parse_url['query']) {
                                    return TRUE;
                                } else {
                                    continue;
                                }
                            }
                            return TRUE;
                        }
                    }
                }
            }
            return FALSE;
        }

        /**
         * Set Email Content Type
         */
        public function arm_mail_content_type() {
            return 'text/html';
        }

        public function arm_mailer($temp_slug, $user_id, $admin_template_id = '', $follower_id = '') {
            global $wpdb, $ARMember, $arm_slugs, $arm_email_settings;
            if (!empty($user_id) && $user_id != 0) {
                $user_info = get_user_by('id', $user_id);
                $to_user = $user_info->user_email;
                $to_admin = get_option('admin_email');

                $all_email_settings = $arm_email_settings->arm_get_all_email_settings();
        
                $email_css = '<style>
                table, th, td {
                    border: 1px solid grey;
                    border-collapse: collapse;
                }
                table {
                    table-layout: auto;
                }
                th, td {
                    padding: 5px;
                    text-align: left;
                }
                </style>';
        
                if (!empty($temp_slug)) {
                    $template = $arm_email_settings->arm_get_email_template($temp_slug);
		    
                    $template = apply_filters('arm_get_modify_mail_template_object_for_send_mail', $template,$user_id);
		    
                    if ($template->arm_template_status == '1') {
                        $message = $this->arm_filter_email_with_user_detail($template->arm_template_content, $user_id, 0, $follower_id);
                        $subject = $this->arm_filter_email_with_user_detail($template->arm_template_subject, $user_id, 0, $follower_id);
                        $message = $email_css . $message;
                        $user_send_mail = $this->arm_wp_mail('', $to_user, $subject, $message);
                    }
                }
                if (!empty($admin_template_id)) {
                    $admin_template = $arm_email_settings->arm_get_single_email_template($admin_template_id);
                    if ($admin_template->arm_template_status == '1') {
		    
                        $admin_template = apply_filters('arm_get_modify_admin_mail_template_object_for_send_mail', $admin_template, $user_id, 0, $follower_id);
                        $admin_subject = $this->arm_filter_email_with_user_detail($admin_template->arm_template_subject, $user_id, 0, $follower_id);
                        $admin_message = $this->arm_filter_email_with_user_detail($admin_template->arm_template_content, $user_id, 0, $follower_id);
                        $admin_message = $email_css . $admin_message;
                        $admin_send_mail = $this->arm_send_message_to_armember_admin_users($to_user, $admin_message, $admin_subject);
                    }
                }
            }
        }

        public function arm_send_message_to_armember_admin_users($from = '', $subject = '', $message = '',$attachments=array()) {
            global $arm_email_settings, $arm_global_settings;
            $all_email_settings = $arm_email_settings->arm_get_all_email_settings();
            $admin_email = (!empty($all_email_settings['arm_email_admin_email'])) ? $all_email_settings['arm_email_admin_email'] : get_option('admin_email');

            $exploded_admin_email = array();
            if (strpos($admin_email, ',') !== false) {
                $exploded_admin_email = explode(",", trim($admin_email));
            }

            if (isset($exploded_admin_email) && !empty($exploded_admin_email)) {
                foreach ($exploded_admin_email as $admin_email_from_array) {
                    if ($admin_email_from_array != '') {
                        $admin_email_from_array = apply_filters('arm_admin_email', trim($admin_email_from_array));
                        
                        $admin_send_mail = $arm_global_settings->arm_wp_mail($from, $admin_email_from_array, $subject, $message,$attachments,1);
                    }
                }
            } else {
                if ($admin_email) {
                    $admin_email = apply_filters('arm_admin_email', $admin_email);
                    $admin_send_mail = $arm_global_settings->arm_wp_mail($from, $admin_email, $subject, $message,$attachments,1);
                }
            }

            return $admin_send_mail;
        }

        public function arm_wp_mail($from, $recipient, $subject, $message, $attachments = array(), $is_admin=0) {
            global $wp, $wpdb, $ARMember, $arm_slugs, $arm_email_settings, $arm_plain_text, $wp_version;
            remove_all_actions('phpmailer_init');
            $return = false;
            $emailSettings = $arm_email_settings->arm_get_all_email_settings();
            $arm_mail_authentication = (isset($emailSettings['arm_mail_authentication'])) ? $emailSettings['arm_mail_authentication'] : '1';
            $email_server = (!empty($emailSettings['arm_email_server'])) ? $emailSettings['arm_email_server'] : 'wordpress_server';
            $from_name = (!empty($emailSettings['arm_email_from_name'])) ? stripslashes_deep($emailSettings['arm_email_from_name']) : wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
            $from_email = (!empty($emailSettings['arm_email_from_email'])) ? $emailSettings['arm_email_from_email'] : get_option('admin_email');
            $content_type = (@$arm_plain_text) ? 'text/plain' : 'text/html';
            $from_name = $from_name;
            $reply_to = (!empty($from)) ? $from : $from_email;
            /* Set Email Headers */
            $headers = array();
            //$headers[] = 'From: "' . $from_name . '" <' . $reply_to . '>'; //changes from v3.0
            $headers[] = 'From: "' . $from_name . '" <' . $from_email . '>';
            $headers[] = 'Reply-To: ' . $reply_to;
            $headers[] = 'Content-Type: ' . $content_type . '; charset="' . get_option('blog_charset') . '"';
            /* Filter Email Subject & Message */
            $message_html = "<html>
            <head>";
            $message_html .= '<style>
						table, th, td {
							border: 1px solid grey;
							border-collapse: collapse;
						}
						table {
							table-layout: auto;
						}
						th, td {
							padding: 5px;
							text-align: left;
						}
					</style>';
            
                $message_html .= "</head><body>";
            $message_html .= $message;           
            $message_html .= "</body></html>";
            $message = $message_html;
            $subject = wp_specialchars_decode(strip_tags(stripslashes($subject)), ENT_QUOTES);
            $message = do_shortcode($message);
            $message = wordwrap(stripslashes($message), 70, "\r\n");
            if (@$arm_plain_text) {
                $message = wp_specialchars_decode(strip_tags($message), ENT_QUOTES);
            }

            $subject = apply_filters('arm_email_subject', $subject);
            $message = apply_filters('arm_change_email_content', $message);
            $recipient = apply_filters('arm_email_recipients', $recipient);
            $headers = apply_filters('arm_email_header', $headers, $recipient, $subject);
            remove_filter('wp_mail_from', 'bp_core_email_from_address_filter');
            remove_filter('wp_mail_from_name', 'bp_core_email_from_name_filter');
            
            if( version_compare( $wp_version, '5.5', '<' ) ){
                require_once ABSPATH . WPINC . '/class-phpmailer.php';
                require_once ABSPATH . WPINC . '/class-smtp.php';
                $armPMailer = new PHPMailer();
            } else {
                require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                $armPMailer = new PHPMailer\PHPMailer\PHPMailer();
            }
            do_action('arm_before_send_email_notification', $from, $recipient, $subject, $message, $attachments);
            /* Character Set of the message. */
            $armPMailer->CharSet = "UTF-8";
            $armPMailer->SMTPDebug = 0;
            /* $armPMailer->Debugoutput = 'html'; */
            if ($email_server == 'smtp_server') {
                $armPMailer->isSMTP();
                $armPMailer->Host = isset($emailSettings['arm_mail_server']) ? $emailSettings['arm_mail_server'] : '';
                $armPMailer->SMTPAuth = ($arm_mail_authentication==1) ? true : false;
                $armPMailer->Username = isset($emailSettings['arm_mail_login_name']) ? $emailSettings['arm_mail_login_name'] : '';
                $armPMailer->Password = isset($emailSettings['arm_mail_password']) ? $emailSettings['arm_mail_password'] : '';
                if (isset($emailSettings['arm_smtp_enc']) && !empty($emailSettings['arm_smtp_enc']) && $emailSettings['arm_smtp_enc'] != 'none') {
                    $armPMailer->SMTPSecure = $emailSettings['arm_smtp_enc'];
                }
                if($emailSettings['arm_smtp_enc'] == 'none'){
                    $armPMailer->SMTPAutoTLS = false;
                }
                
                $armPMailer->Port = isset($emailSettings['arm_mail_port']) ? $emailSettings['arm_mail_port'] : '';
            } else {
                $armPMailer->isMail();
            }
            //$armPMailer->setFrom($reply_to, $from_name);
	    $armPMailer->setFrom($from_email, $from_name);
            $armPMailer->addReplyTo($reply_to, $from_name);
            $armPMailer->addAddress($recipient);
            $arm_attachment_urls = "";
            if (isset($attachments) && !empty($attachments) && is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    $armPMailer->addAttachment($attachment);
                    $arm_attachment_urls .= $attachment.', ';
                }
            }
            
           
            $armPMailer->isHTML(true);
            $armPMailer->Subject = $subject;
            $armPMailer->Body = $message;
            if (@$arm_plain_text) {
                $armPMailer->AltBody = $message;
            }
            if (MEMBERSHIP_DEBUG_LOG == true) {
                if (MEMBERSHIP_DEBUG_LOG_TYPE == 'ARM_ALL' || MEMBERSHIP_DEBUG_LOG_TYPE == 'ARM_MAIL') {
                    global $arm_case_types, $wpdb;
                    $arm_case_types['mail']['protected'] = true;
                    $arm_case_types['mail']['type'] = '';
                    $arm_case_types['mail']['message'] = " Email Server : " . $email_server . " <br/> Email Recipient : " . $recipient . " <br/> Message Content : " . $message;
                    $ARMember->arm_debug_response_log('arm_wp_mail', $arm_case_types, array(), $wpdb->last_query, true);
                }
            }
            /* Send Email */
            $email_server_name = "WordPress Server";
            if ($email_server == 'smtp_server' || $email_server == 'phpmailer') {
                if ($armPMailer->send()) {
                    $return = true;
                }
                if($email_server=='smtp_server')
                {
                    $email_server_name = "SMTP Server";
                }
                else {
                    $email_server_name = "PHP Mailer";
                }
            } else if($email_server == "google_gmail"){
                if(!empty( $emailSettings['arm_google_client_id']) && !empty($emailSettings['arm_google_client_secret']) && !empty($emailSettings['arm_google_auth_token']) ){				
                    $ARMember->arm_write_response("ARMember Gmail Check fetched data =======>".maybe_serialize($emailSettings));
                    require_once MEMBERSHIP_LIBRARY_DIR . "/gmail/vendor/autoload.php";
					$email_server_name = "Google Gmail";
                    $arm_client_auth_url = $emailSettings['arm_google_auth_url'];
                    $arm_client_id = $emailSettings['arm_google_client_id'];
                    $arm_client_secret = $emailSettings['arm_google_client_secret'];

					$gmail_oauth_data = $emailSettings['arm_google_auth_response'];
					
					$arm_gmail_auth = stripslashes_deep( $gmail_oauth_data );
					$gmail_oauth_data = json_decode( $arm_gmail_auth, true);

                    $client = new Google_Client();
                    $client->setClientId($arm_client_id);
                    $client->setClientSecret( $arm_client_secret );
                    $client->setRedirectUri( $arm_client_auth_url);
                    $client->setAccessToken( $gmail_oauth_data );
                    
                    /** Refresh Google API Token */
                    if( $client->isAccessTokenExpired() ){
						$ARMember->arm_write_response("ARMember Gmail Test Reset Tocken");
                        $is_refreshed = $client->refreshToken( $gmail_oauth_data['refresh_token'] );

                        do_action('arm_general_log_entry','email','send test email after expiration token','armember', $is_refreshed);

                        if( !empty( $is_refreshed['error'] ) ){
                            return false;
                        }
                        $refresh_token = $gmail_oauth_data['refresh_token'];
                        $gmail_oauth_data =  $client->getAccessToken();
                        do_action('arm_general_log_entry','email','Token refresh data','armember', $gmail_oauth_data);
                        if( empty( $gmail_oauth_data['refresh_token'] ) ){
                            $gmail_oauth_data['refresh_token'] = $refresh_token;
                        }

                        $client->setAccessToken( $gmail_oauth_data );
                        $emailSettings['arm_google_auth_token'] = $gmail_oauth_data['access_token'];
                        $emailSettings['arm_google_auth_response'] = json_encode($gmail_oauth_data);
                        update_option('arm_email_settings',$emailSettings);
                    } else {
						$ARMember->arm_write_response("ARMember Gmail Verify fetched data =======>".maybe_serialize($gmail_oauth_data));
                        $verify_token_url = 'https://www.googleapis.com/oauth2/v3/tokeninfo';
                        
                        $args = array(
                            'timeout' => false,
                            'method' => 'GET',
                            'body' => array(
                                'access_token' => $gmail_oauth_data['access_token']
                            )
                        );
                        $check_access_token = wp_remote_get( $verify_token_url, $args );
                        $ARMember->arm_write_response("ARMember Gmail Toekn Check data =======>".maybe_serialize($check_access_token));
                        if( is_wp_error( $check_access_token ) ){
                            return false;
                        }

                        $valid_access_token_code = wp_remote_retrieve_response_code( $check_access_token );

                        if( 200 != $valid_access_token_code ){
                            $validate_access_token = json_decode( wp_remote_retrieve_body( $check_access_token ), 1 );
                            return false;
                        }
                        
                    }
		    
                    do_action('arm_general_log_entry','email','gmail receipent token','armember', $recipient);
                            
                    $service = new Google\Service\Gmail( $client );
                    $user = 'me';
                    $subjectCharset = $charset = 'utf-8';
                    $boundary = uniqid(rand(), true);
                    $arm_gmail_message_content = "From: {$from_name} <{$from}> \r\n";
                    $arm_gmail_message_content .= "To: ".$recipient."\r\n";
                    $arm_gmail_message_content .= 'Subject: =?' . $subjectCharset . '?B?' . base64_encode($subject) . "?=\r\n";
                    $arm_gmail_message_content .= 'MIME-Version: 1.0' . "\r\n";
                    $arm_gmail_message_content .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";
                    $arm_gmail_message_content .= "\r\n--{$boundary}\r\n";
                    $arm_gmail_message_content .= 'Content-Type: text/html; charset=' . $charset . "\r\n";
                    $arm_gmail_message_content .= "Content-Transfer-Encoding: quoted-printable" . "\r\n\r\n";
                    $arm_gmail_message_content .= quoted_printable_encode($message) . "\r\n";

                    foreach( $attachments as $attachment_file ){
                        $attachment_name = basename( $attachment_file );
                        $attachment_type = mime_content_type( $attachment_file );
                
                        if (! function_exists('WP_Filesystem') ) {
                            include_once ABSPATH . 'wp-admin/includes/file.php';
                        }
                
                        WP_Filesystem();
                        global $wp_filesystem;
                
                        $file_content  = $wp_filesystem->get_contents($attachment_file);
                        $file_content = chunk_split( base64_encode( $file_content ) );
                        $arm_gmail_message_content .= "--$boundary\r\n";
                        $arm_gmail_message_content .= "Content-Type: {$attachment_type}; name={$attachment_name}\r\n";
                        $arm_gmail_message_content .= "Content-Disposition: attachment; filename={$attachment_name}\r\n";
                        $arm_gmail_message_content .= "Content-Transfer-Encoding: base64\r\n";
                        $arm_gmail_message_content .= "X-Attachment-Id: {$attachment_id}\r\n\r\n";
                        $arm_gmail_message_content .= $file_content;
                    }
                    $arm_gmail_message_content .= "\r\n--{$boundary}--\r\n";
					
					$ARMember->arm_write_response("ARMember Gmail Contents Check data =======>".maybe_serialize($arm_gmail_message_content));

                    // The message needs to be encoded in Base64URL
                    $mime = rtrim(strtr(base64_encode($arm_gmail_message_content), '+/', '-_'), '=');
                    $msg = new Google_Service_Gmail_Message();
                    $msg->setRaw($mime);

                    $return_error_msg = esc_html__('Something went wrong..','ARMember');
                    try {
                        $message = $service->users_messages->send("me", $msg);
						$ARMember->arm_write_response("ARMember Gmail Contents Sent =======>".maybe_serialize($message));
                        do_action('arm_general_log_entry','email','send gmail detail success','armember', maybe_serialize($message));
                        $return = true;
                    } catch (Exception $e) {
                        $return_error_msg = 'An error occurred: ' . $e->getMessage();
                        $return = false;
                        $ARMember->arm_write_response("ARMember Gmail Contents Sent Error =======>".maybe_serialize($e));
                    }
                }

            }
            else{
                add_filter('wp_mail_content_type', array($this, 'arm_mail_content_type'));
                if (!wp_mail($recipient, $subject, $message, $headers, $attachments)) {
                    if ($armPMailer->send()) {
                        $return = true;
                    }
                } else {
                    $return = true;
                }
                remove_filter('wp_mail_content_type', array($this, 'arm_mail_content_type'));
            }

            /* arm_email_log_entry */
            $is_mail_send = ($return == true ) ? 'Yes' : 'No';
			$arm_mail_message = is_object($message) ? $arm_gmail_message_content : $message ;
            $arm_email_content  = '';
            $arm_email_content .= 'Email Sent Successfully: '.$is_mail_send.', To Email: '.$recipient.', From Email: '.$from_email. ', Email Server:'.$email_server_name.'{ARMNL}';   
            $arm_email_content .= 'Subject: '.$subject.'{ARMNL}';
            $arm_email_content .= 'Content: {ARMNL}'.$arm_mail_message.'{ARMNL}';

            if(!empty($arm_attachment_urls))
            {
                $arm_attachment_urls = rtrim($arm_attachment_urls, ',');
                $arm_email_content .= '{ARMNL}Attachment URL(s): {ARMNL}'.$arm_attachment_urls.'{ARMNL}';
            }
	    
            $arm_email_content = apply_filters('arm_modify_email_content_before_log_entry', $arm_email_content,$is_admin,$recipient);

   
            do_action('arm_general_log_entry','email','send email detail','armember', $arm_email_content);

            do_action('arm_after_send_email_notification', $from, $recipient, $subject, $message, $attachments);
            
            return $return;
        }

        public function arm_filter_email_with_user_detail($content, $user_id = 0, $plan_id = 0, $follower_id = 0, $key = '') {
            global $wp, $wpdb, $ARMember, $arm_slugs, $arm_payment_gateways, $arm_email_settings, $arm_global_settings;
            $user_info = get_user_by('id', $user_id);
            $f_displayname = '';
            $u_plan_description = '';
            if ($follower_id != 0 && !empty($follower_id)) {
                $follower_info = get_user_by('id', $follower_id);
                $follower_name = $follower_info->first_name . ' ' . (isset($follower_info->last_name))?$follower_info->last_name:'';
                if (empty($follower_info->first_name) && empty($follower_info->last_name)) {
                    $follower_name = $follower_info->user_login;
                }
                $f_displayname = "<a href='" . $this->arm_get_user_profile_url($follower_id) . "'>" . $follower_name . "</a>";
            }
            if ($user_id != 0 && !empty($user_info)) {
                $u_email = $user_info->user_email;
                $u_displayname = $user_info->display_name;
                $u_userurl = $user_info->user_url;
                $u_username = $user_info->user_login;
                $u_fname = (isset($user_info->first_name))?$user_info->first_name:'';
                $u_lname = (isset($user_info->last_name))?$user_info->last_name:'';
                $u_grace_period_days = 0;
                $u_trial_amount = 0;
                $u_plan_discount  = 0;
                $u_payable_amount = 0;
                $now = current_time('timestamp'); // or your date as well
                
                $arm_is_user_in_grace = 0;
                $arm_user_grace_end_date = '';
                $plan_detail = array(); 
                $user_plans = get_user_meta($user_id, 'arm_user_plan_ids', true);
                $user_plan = 0;
                $using_gateway = '';
                $payment_cycle = 0;
                if(!empty($plan_id)){
                    $user_plan = $plan_id; 
                    $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                    if(!empty($planData))
                    {
                        $arm_is_user_in_grace = (isset($planData['arm_is_user_in_grace']) && !empty($planData['arm_is_user_in_grace'])) ? $planData['arm_is_user_in_grace'] : 0;
                        $arm_user_grace_end_date = $planData['arm_grace_period_end'];
                        $plan_detail = $planData['arm_current_plan_detail'];
                        $using_gateway = $planData['arm_user_gateway'];
                        $payment_cycle = $planData['arm_payment_cycle'];
                        $expire_time = $planData['arm_expire_plan'];
                    }
                }
           
                if ($arm_is_user_in_grace == 1) {
                    $datediff = $arm_user_grace_end_date - $now;
                    $u_grace_period_days = floor($datediff / (60 * 60 * 24));
                }
                $activation_key = get_user_meta($user_id, 'arm_user_activation_key', true);
                $login_page_id = isset($this->global_settings['login_page_id']) ? $this->global_settings['login_page_id'] : 0;
                if ($login_page_id == 0) {
                    $arm_login_page_url = wp_login_url();
                } else {

                    $arm_login_page_url = $this->arm_get_permalink('', $login_page_id);
                    $arm_login_page_url = apply_filters('arm_modify_redirection_page_external', $arm_login_page_url,$user_id,$login_page_id);
                }
                
                
                 $arm_login_page_url = $arm_global_settings->add_query_arg('arm-key', urlencode($activation_key), $arm_login_page_url);
                 $arm_login_page_url = $arm_global_settings->add_query_arg('email', urlencode($u_email), $arm_login_page_url);
                
                
                $validate_url = $arm_login_page_url;
                $pending = '';

                $login_url = $this->arm_get_permalink('', $login_page_id);
                $login_url = apply_filters('arm_modify_redirection_page_external', $login_url,$user_id,$login_page_id);
                $profile_link = $this->arm_get_user_profile_url($user_info->ID);
                $blog_name = get_bloginfo('name');
                $blog_url = ARM_HOME_URL;
                $arm_currency = $arm_payment_gateways->arm_get_global_currency();

                $all_email_settings = $arm_email_settings->arm_get_all_email_settings();
                $admin_email = (!empty($all_email_settings['arm_email_admin_email'])) ? $all_email_settings['arm_email_admin_email'] : get_option('admin_email');

                $u_plan_name = '-';
                $u_plan_amount = '-';
                $u_plan_discount = '-';
                $u_payment_type = '-';
                $u_payment_gateway = '-';
                $u_transaction_id = '-';
                $plan_expire = '';
                $u_tax_percentage = '-';
                $u_tax_amount = '-';
                $u_payment_date = '-';
                $u_coupon_code = '-';

             
                if (!empty($plan_detail)) {
                    $plan_detail = maybe_unserialize($plan_detail);
                    if (!empty($plan_detail)) {
                        $planObj = new ARM_Plan(0);
                        $planObj->init((object) $plan_detail);
                    } else {
                        $planObj = new ARM_Plan($user_plan);
                    }
                    $u_plan_name = $planObj->name;
                    $u_plan_description = $planObj->description;
                    
                    if($planObj->is_recurring()){
                        $plan_data = $planObj->prepare_recurring_data($payment_cycle);
                        $u_plan_amount = $plan_data['amount'];
                        $u_plan_amount = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $u_plan_amount);
                    }
                    else{
                        $u_plan_amount = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $planObj->amount);
                    }

                    $plan_expire = esc_html__('Never Expires', 'ARMember');
                
                if (!empty($expire_time)) {
                    $date_format = $this->arm_get_wp_date_format();
                    $plan_expire = date_i18n($date_format, $expire_time);
                }
                    
                    
                   
                    if (!empty($using_gateway)) {
                        $u_payment_gateway = $arm_payment_gateways->arm_gateway_name_by_key($using_gateway);
                    }
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

                    $selectColumns = '`arm_log_id`, `arm_user_id`, `arm_transaction_id`, `arm_is_trial`, `arm_amount`, `arm_extra_vars`, `arm_coupon_discount`, `arm_coupon_discount_type`,`arm_payment_date`, `arm_coupon_code`';
                    $where_bt='';
                    if ($using_gateway == 'bank_transfer') {
                       $where_bt=" AND arm_payment_gateway='bank_transfer'";
                    }    

                    $armLogTable = $ARMember->tbl_arm_payment_log;
                    $selectColumns .= ', `arm_token`';
                    
                    $log_detail = $wpdb->get_row( $wpdb->prepare("SELECT {$selectColumns} FROM `{$armLogTable}` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d {$where_bt} ORDER BY `arm_log_id` DESC",$user_id,$user_plan) ); //phpcs:ignore --Reason $armLogTable is a table name
                    if (!empty($log_detail)) {
                        $u_transaction_id = $log_detail->arm_transaction_id;

                        $extravars = maybe_unserialize($log_detail->arm_extra_vars);


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


                        if(isset($extravars['tax_percentage'])){
                            $u_tax_percentage = ($extravars['tax_percentage'] != '') ? $extravars['tax_percentage'].'%': '-';
                        }

                        if(isset($extravars['tax_amount'])){
                            $u_tax_amount = ($extravars['tax_amount'] != '') ? $arm_payment_gateways->arm_amount_set_separator($arm_currency, $extravars['tax_amount']): '-';
                        }

                        
                        if (!empty($log_detail->arm_is_trial) && $log_detail->arm_is_trial == 1) {
                            $u_trial_amount= isset($extravars['trial']['amount']) ? $extravars['trial']['amount'] : 0;

                        }
                        $u_payable_amount = $log_detail->arm_amount;

                        if (!empty($log_detail->arm_payment_date)) {
                                $date_format = $this->arm_get_wp_date_format();
                                $u_payment_date = date_i18n($date_format, $log_detail->arm_payment_date);
                        }

                    }
                }


                if (empty($user_plans)) {
                    $arm_user_entry_id = get_user_meta($user_id, 'arm_entry_id', true);
                    if (isset($arm_user_entry_id) && $arm_user_entry_id != '') {
                        $armentryTable = $ARMember->tbl_arm_entries;
                        $arm_user_entry_data_ser = $wpdb->get_var( $wpdb->prepare("SELECT `arm_entry_value` FROM `{$armentryTable}` WHERE `arm_entry_id` = %d",$arm_user_entry_id) ); //phpcs:ignore --Reason armentryTable is a table name
                        $arm_user_entry_data = maybe_unserialize($arm_user_entry_data_ser);
                        $arm_user_payment_gateway = '';

                        if (isset($arm_user_entry_data['arm_front_gateway_skin_type']) && $arm_user_entry_data['arm_front_gateway_skin_type'] == 'dropdown') {

                            $arm_user_payment_gateway = !empty($arm_user_entry_data['_payment_gateway']) ? $arm_user_entry_data['_payment_gateway'] : '' ;
                            $arm_plan_skin_type = $arm_user_entry_data['arm_front_plan_skin_type'];
                            if ($arm_plan_skin_type == 'skin5') {
                                $arm_subscription_plan = isset($arm_user_entry_data['_subscription_plan']) ? $arm_user_entry_data['_subscription_plan'] : '';
                            } else {
                                $arm_subscription_plan = isset($arm_user_entry_data['subscription_plan']) ? $arm_user_entry_data['subscription_plan'] : '';
                            }
                        } else if (isset($arm_user_entry_data['arm_front_gateway_skin_type']) && $arm_user_entry_data['arm_front_gateway_skin_type'] == 'radio') {

                            $arm_user_payment_gateway = $arm_user_entry_data['payment_gateway'];
                            $arm_plan_skin_type = !empty($arm_user_entry_data['arm_front_plan_skin_type']) ? $arm_user_entry_data['arm_front_plan_skin_type'] : 'skin1';
                            if ($arm_plan_skin_type == 'skin5') {
                                $arm_subscription_plan = isset($arm_user_entry_data['_subscription_plan']) ? $arm_user_entry_data['_subscription_plan'] : '';
                            } else {
                                $arm_subscription_plan = isset($arm_user_entry_data['subscription_plan']) ? $arm_user_entry_data['subscription_plan'] : '';
                            }
                        }

                        if ($arm_user_payment_gateway == 'bank_transfer') {

                            $userplanObj = new ARM_Plan($arm_subscription_plan);
                            $u_plan_name = $userplanObj->name;
                            $u_plan_description = $userplanObj->description;
                            $u_payment_gateway = $arm_payment_gateways->arm_gateway_name_by_key('bank_transfer');
                            $plan_expire = '';
                            $u_trial_amount = 0;
                            $u_plan_discount  =0;
                            $u_payable_amount = 0;
                            
                            
                            if($userplanObj->is_recurring()){
                                $plan_data = $userplanObj->prepare_recurring_data($payment_cycle);
                                $u_plan_amount = $plan_data['amount'];
                                $u_plan_amount = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $u_plan_amount);
                            }
                            else{
                                $u_plan_amount = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $userplanObj->amount);
                            }

                            if ($userplanObj->has_trial_period()) {
                                $planTrialOpts = isset($userplanObj->options['trial']) ? $userplanObj->options['trial'] : array();
                                $u_plan_amount = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $planTrialOpts['amount']);
                            }

                            if ($userplanObj->is_paid()) {
                                if ($userplanObj->is_lifetime()) {
                                    $u_payment_type = esc_html__('Life Time', 'ARMember');
                                } else {
                                    if ($userplanObj->is_recurring()) {
                                        $u_payment_type = esc_html__('Subscription', 'ARMember');
                                    } else {
                                        $u_payment_type = esc_html__('One Time', 'ARMember');
                                    }
                                }
                            }

                            $selectColumns = '`arm_coupon_discount_type`, `arm_coupon_discount`, `arm_transaction_id`, `arm_extra_vars`, `arm_is_trial`, `arm_amount`';

                            $armLogTable = $ARMember->tbl_arm_payment_log;

                            $log_detail = $wpdb->get_row( $wpdb->prepare("SELECT {$selectColumns} FROM `{$armLogTable}` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND arm_payment_gateway=%s ORDER BY `arm_log_id` DESC",$user_id,$arm_subscription_plan,'bank_transfer') ); //phpcs:ignore --Reason armLogTable is a table name
                            if (!empty($log_detail)) {
                                $u_transaction_id = $log_detail->arm_transaction_id;
                               $u_payable_amount = $log_detail->arm_amount;

                                $extravars = maybe_unserialize($log_detail->arm_extra_vars);

                        if (!empty($log_detail->arm_coupon_discount) && $log_detail->arm_coupon_discount > 0) {
                            $u_plan_discount = isset($extravars['coupon']['amount']) ? $extravars['coupon']['amount'] : 0;

                        } 

                        if(!empty($log_detail->arm_coupon_code)) {
                            $u_coupon_code = $log_detail->arm_coupon_code;
                        } else if(isset($extravars['coupon'])) {
                            $u_coupon_code = isset($extravars['coupon']['coupon_code']) ? $extravars['coupon']['coupon_code'] : "";
                        }

                        if (!empty($log_detail->arm_is_trial) && $log_detail->arm_is_trial == 1) {
                            $u_trial_amount= isset($extravars['trial']['amount']) ? $extravars['trial']['amount'] : 0;

                        }


                            }
                        }
                    }
                }



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

                    $varification_key = get_user_meta($user_id, 'arm_user_activation_key', true);
                    $user_status = arm_get_member_status($user_id);
                    if($user_status == 3){
                        $rp_link =  $arm_global_settings->add_query_arg('varify_key', rawurlencode($varification_key), $arm_reset_password_link);
                    }



                    $content = str_replace('{ARM_RESET_PASSWORD_LINK}', $arm_reset_password_link, $content);
                } else {

                    $content = str_replace('{ARM_RESET_PASSWORD_LINK}', '', $content);
                }

                $u_payable_amount = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $u_payable_amount);

                $content = str_replace('{ARM_USER_ID}', $user_id, $content);
                $content = str_replace('{ARM_USERNAME}', $u_username, $content);
                $content = str_replace('{ARM_FIRST_NAME}', $u_fname, $content);
                $content = str_replace('{ARM_LAST_NAME}', $u_lname, $content);
                $content = str_replace('{ARM_NAME}', $u_displayname, $content);
                $content = str_replace('{ARM_EMAIL}', $u_email, $content);
                $content = str_replace('{ARM_ADMIN_EMAIL}', $admin_email, $content);
                $content = str_replace('{ARM_BLOGNAME}', $blog_name, $content);
                $content = str_replace('{ARM_BLOG_URL}', $blog_url, $content);
                $content = str_replace('{ARM_VALIDATE_URL}', $validate_url, $content);
                $content = str_replace('{ARM_CHANGE_PASSWORD_CONFIRMATION_URL}', $pending, $content);
                $content = str_replace('{ARM_PENDING_REQUESTS_URL}', $pending, $content);
                $content = str_replace('{ARM_PROFILE_FIELDS}', $pending, $content);
                $content = str_replace('{ARM_PROFILE_LINK}', $profile_link, $content);
                $content = str_replace('{ARM_LOGIN_URL}', $login_url, $content);
                $content = str_replace('{ARM_PLAN}', $u_plan_name, $content);
                $content = str_replace('{ARM_PLAN_DESCRIPTION}', $u_plan_description, $content);
                $content = str_replace('{ARM_PLAN_AMOUNT}', $u_plan_amount, $content);
                $content = str_replace('{ARM_PLAN_DISCOUNT}', $u_plan_discount, $content);
                $content = str_replace('{ARM_TRIAL_AMOUNT}', $u_trial_amount, $content);
                $content = str_replace('{ARM_PAYABLE_AMOUNT}', $u_payable_amount, $content);
                $content = str_replace('{ARM_PAYMENT_TYPE}', $u_payment_type, $content);
                $content = str_replace('{ARM_PAYMENT_GATEWAY}', $u_payment_gateway, $content);
                $content = str_replace('{ARM_TRANSACTION_ID}', $u_transaction_id, $content);
                $content = str_replace('{ARM_TAX_PERCENTAGE}', $u_tax_percentage, $content);
                $content = str_replace('{ARM_TAX_AMOUNT}', $u_tax_amount, $content);
                $content = str_replace('{ARM_GRACE_PERIOD_DAYS}', $u_grace_period_days, $content);
                $content = str_replace('{ARM_CURRENCY}',$arm_currency, $content);
                $content = str_replace('{ARM_PLAN_EXPIRE}',$plan_expire, $content);
                $content = str_replace('{ARM_USERMETA_user_url}', $u_userurl, $content);
                $content = str_replace('{ARM_PAYMENT_DATE}', $u_payment_date, $content);
                $content = str_replace('{ARM_PLAN_COUPON_CODE}', $u_coupon_code, $content);

                $networ_name = get_site_option('site_name');
                $networ_url = get_site_option('siteurl');

                $content = str_replace('{ARM_MESSAGE_NETWORKNAME}',$networ_name, $content);

                $content = str_replace('{ARM_MESSAGE_NETWORKURL}',$networ_url, $content);



                /* Content replace for user meta */
                $matches = array();
                preg_match_all("/\b(\w*ARM_USERMETA_\w*)\b/", $content, $matches, PREG_PATTERN_ORDER);
                $matches = $matches[0];
                if (!empty($matches)) {
                    foreach ($matches as $mat_var) {
                        $key = str_replace('ARM_USERMETA_', '', $mat_var);
                        $meta_val = "";
                        if (!empty($key)) {
                            $meta_val = do_shortcode('[arm_usermeta id=' . intval( $user_id ) . ' meta="' . sanitize_text_field( $key ) . '"]',true);
                            /* $meta_val = get_user_meta($user_id, $key, TRUE);
                            if(is_array($meta_val))
                            {
                                $replace_val = "";
                                foreach ($meta_val as $key => $value) {
                                    $replace_val .= ($value != '') ? $value."," : "";   
                                }
                                $meta_val = rtrim($replace_val, ",");
                            } */
                        }
                        $content = str_replace('{' . $mat_var . '}', $meta_val, $content);
                    }
                }
            }

            $arm_is_html = $this->arm_is_html($content);
            if( !$arm_is_html )
            {
                $content = nl2br( $content );
            }
            $content = apply_filters('arm_change_email_content_with_user_detail', $content, $user_id);
            return $content;
        }

        function arm_is_html( $content )
        {
            return preg_match( "/<[^<]+>/", $content, $m ) != 0;
        }

        function arm_get_wp_pages($args = '', $columns = array()){
             $defaults = array(
                'depth' => 0, 'child_of' => 0,
                'selected' => 0, 'echo' => 1,
                'name' => 'page_id', 'id' => '',
                'show_option_none' => 'Select Page', 'show_option_no_change' => '',
                'option_none_value' => '',
                'class' => '',
                'required' => false,
                'required_msg' => false,
            );
            $arm_r = wp_parse_args($args, $defaults);
            $arm_pages = get_pages($arm_r);
            $arm_new_pages = array();
            if(!empty($arm_pages)){
                if(!empty($columns))
                {
                    $n = 0;
                    foreach($arm_pages as $page)
                    {
                        foreach($columns as $column){
                            $arm_new_pages[$n][$column] = sanitize_text_field( $page->$column );
                        }
                        $n++;
                    }
                }
                else
                {
                    $arm_new_pages = $arm_pages;
                }
            }
            return $arm_new_pages;
        }
        
        function arm_wp_dropdown_pages($args = '', $dd_class = '') {
            $defaults = array(
                'depth' => 0, 'child_of' => 0,
                'selected' => 0, 'echo' => 1,
                'name' => 'page_id', 'id' => '',
                'show_option_none' => 'Select Page', 'show_option_no_change' => '',
                'option_none_value' => '',
                'class' => '',
                'required' => false,
                'required_msg' => false,
            );
            $r = wp_parse_args($args, $defaults);
            $pages = get_pages($r);
            $output = '';
            if (empty($r['id'])) {
                $r['id'] = $r['name'];
            }

            $pageIds = array();
            if (!empty($pages)) {
                $pageIds = array();
                foreach ($pages as $p) {
                    $pageIds[] = $p->ID;
                }
            }
            if (!in_array($r['selected'], $pageIds)) {
                $r['selected'] = '';
            }
   
            $required = ($r['required']) ? 'required="required"' : '';
            $required_msg = ($r['required_msg']) ? 'data-msg-required="' . esc_attr( $r['required_msg'] ) . '"' : '';
            $output .= "<input type='hidden'  name='" . esc_attr($r['name']) . "' id='" . esc_attr($r['id']) . "' class='" . esc_attr( $r['class'] ) . "' value='" . esc_attr( $r['selected'] ) . "' $required $required_msg/>";
            $output .= "<dl class='arm_selectbox column_level_dd arm_width_100_pct arm_margin_top_12'>";
            $output .= "<dt class='". esc_attr( $dd_class ) ."'><span>" . (!empty($r['selected']) ? esc_html( get_the_title($r['selected']) ) : 'Select Page') . "</span><input type='text' style='display:none;' value='" . (!empty($r['selected']) ? esc_attr( get_the_title($r['selected']) ) : 'Select Page') . "' class='arm_autocomplete'  /><i class='armfa armfa-caret-down armfa-lg'></i></dt>";
            $output .= "<dd>";
            $output .= "<ul data-id='" . esc_attr($r['id']) . "'>";

            if ($r['show_option_no_change']) {

                $output .= "<li data-label='" . esc_attr( $r['show_option_no_change'] ) . "' data-value='-1'>" . $r['show_option_no_change'] . "</li>";
            }
            if ($r['show_option_none']) {
                $output .= "<li data-label='" . esc_attr( $r['show_option_none'] ) . "' data-value='" . esc_attr($r['option_none_value']) . "'>" . esc_html( $r['show_option_none'] ) . "</li>";
            }
            if (!empty($pages)) {
                foreach ($pages as $p) {
                    $is_protected = 0;
                    $item_plans = get_post_meta($p->ID, 'arm_access_plan');
                    $item_plans = (!empty($item_plans)) ? $item_plans : array();

                    if (count($item_plans) == 0)
                        $is_protected = 0;
                    else
                        $is_protected = 1;

                    if(empty($p->post_title)) {
                        $arm_post_title = esc_html__("(no title)", "ARMember");
                    } else {
		    	$arm_post_title = sanitize_text_field( $p->post_title );
                    }
		    $output .= "<li data-label='" . esc_attr( $arm_post_title ) . "' data-value='" . esc_attr($p->ID) . "' data-protected='" . esc_attr($is_protected) . "' >" . esc_html( $arm_post_title ) . "</li>";
                    
                }
            }
            $output .= "</ul>";
            $output .= "</dd>";
            $output .= "</dl>";

            $html = apply_filters('arm_wp_dropdown_pages', $output);

            if ($r['echo']) {
                echo $html; //phpcs:ignore
            }
            return $html;
        }

        function arm_get_wp_date_format() {
            global $wp, $wpdb;
            if (is_multisite()) {
                $wp_format_date = get_option('date_format');
            }
            else{
                $wp_format_date = get_site_option('date_format');
            }
            if (empty($wp_format_date)) {
                $date_format = 'M d, Y';
            } else {
                $date_format = $wp_format_date;
            }
            return $date_format;
        }
        
        function arm_get_wp_date_time_format() {
            global $wp, $wpdb;
            
            if (is_multisite()) {
                $wp_date_time_format = get_option('date_format') . " " . get_option('time_format');
            } else {
                $wp_date_time_format = get_site_option('date_format') . " " . get_site_option('time_format');
            }
                        
            if (empty($wp_date_time_format)) {
                $date_time_format = 'M d, Y H:i:s';
            } else {
                $date_time_format = $wp_date_time_format;
            }
            return $date_time_format;
        }

        function arm_time_elapsed($ptime) {
            $etime = current_time('timestamp') - $ptime;
            if ($etime < 1) {
                return esc_html__('now!', 'ARMember');
            }
            $a = array(12 * 30 * 24 * 60 * 60 => esc_html__('year', 'ARMember'),
                30 * 24 * 60 * 60 => esc_html__('month', 'ARMember'),
                24 * 60 * 60 => esc_html__('day', 'ARMember'),
                60 * 60 => esc_html__('hour', 'ARMember'),
                60 => esc_html__('minute', 'ARMember'),
                1 => esc_html__('second', 'ARMember')
            );
            foreach ($a as $secs => $str) {
                $d = $etime / $secs;
                if ($d >= 1) {
                    $r = round($d);
                    return $r . ' ' . $str . ($r > 1 ? 's' : '') . esc_html__(' ago', 'ARMember');
                }
            }
            return '-';
        }

        function arm_time_remaining($rtime) {
            $etime = $rtime - current_time('timestamp');
            if ($etime < 1) {
                return esc_html__('now!', 'ARMember');
            }
            $a = array(12 * 30 * 24 * 60 * 60 => esc_html__('year', 'ARMember'),
                30 * 24 * 60 * 60 => esc_html__('month', 'ARMember'),
                24 * 60 * 60 => esc_html__('day', 'ARMember'),
                60 * 60 => esc_html__('hour', 'ARMember'),
                60 => esc_html__('minute', 'ARMember'),
                1 => esc_html__('second', 'ARMember')
            );
            foreach ($a as $secs => $str) {
                $d = $etime / $secs;
                if ($d >= 1) {
                    $r = round($d);
                    return $r . ' ' . $str . ($r > 1 ? 's' : '');
                }
            }
            return '-';
        }

        function arm_get_remaining_occurrence($start_date, $end_date, $interval) {
            $dates = array();
            $now = current_time('timestamp');
            while ($start_date <= $end_date) {
                if ($now < $start_date) {
                    $dates[] = date('Y-m-d H:i:s', $start_date);
                }
                $start_date = strtotime($interval, $start_date);
            }
            return (count($dates) - 1);
        }

        function arm_get_confirm_box($item_id = 0, $confirmText = '', $btnClass = '', $deleteType = '',$deleteText='',$cancelText='',$confirmTextTitle='') {
            global $wp, $wpdb, $ARMember, $arm_slugs;
            $deleteText = !empty($deleteText) ? sanitize_text_field( $deleteText ) : esc_html__('Delete', 'ARMember');
            $cancelText = !empty($cancelText) ? sanitize_text_field( $cancelText ) : esc_html__('Cancel', 'ARMember');
            $confirmBox = "<div class='arm_confirm_box arm_confirm_box_".esc_attr($item_id)."' id='arm_confirm_box_".esc_attr($item_id)."'>";
            $confirmBox .= "<div class='arm_confirm_box_body'>";
            $confirmBox .= "<div class='arm_confirm_box_arrow'></div>";
            $confirmBox .= "<div class='arm_confirm_box_text_title'>".$confirmTextTitle."</div>";
            $confirmBox .= "<div class='arm_confirm_box_text'>".$confirmText."</div>"; //phpcs:ignore
            $confirmBox .= "<div class='arm_confirm_box_btn_container'>";
            $confirmBox .= "<button type='button' class='arm_confirm_box_btn armcancel' onclick='hideConfirmBoxCallback();'>".esc_html($cancelText)."</button>";
            $confirmBox .= "<button type='button' class='arm_confirm_box_btn armok ".esc_attr($btnClass)."' data-item_id='".esc_attr($item_id)."' data-type='".esc_attr( $deleteType )."'>".esc_html($deleteText)."</button>";
            $confirmBox .= "</div>";
            $confirmBox .= "</div>";
            $confirmBox .= "</div>";
            return $confirmBox;
        }

        function arm_get_badges_confirm_box($item_id = 0, $confirmText = '', $btnClass = '', $deleteType = '') {
            global $wp, $wpdb, $ARMember, $arm_slugs, $arm_members_badges;
            $confirmBox = "<div class='arm_confirm_box arm_confirm_box_".esc_attr($item_id)."' id='arm_confirm_box_".esc_attr($item_id)."'>";
            $confirmBox .= "<div class='arm_confirm_box_body arm_badge_confirm_box_body'>";
            $confirmBox .= "<div class='arm_confirm_box_arrow'></div>";
            $confirmBox .= "<div class='arm_confirm_box_text_title'>".esc_html__('Delete', 'ARMember')."</div>";
            $confirmBox .= "<div class='arm_confirm_box_text arm_badge_confirm_box_text_".esc_attr($item_id)." arm_margin_bottom_12'>".esc_html($confirmText)."</div>";
            $user_achievements_list = $arm_members_badges->arm_get_user_achievements_badges_list($item_id);
            $user_achievements_list = !empty($user_achievements_list) ? $user_achievements_list : '--';
            $confirmBox .= $user_achievements_list;
            $nonce = wp_create_nonce('arm_wp_nonce');
            $confirmBox .="<input type='hidden' name='arm_wp_nonce' value='".esc_attr( $nonce )."'/>";
            $confirmBox .= "<div class='arm_badge_error_red arm_badge_error_red_".esc_attr($item_id)."' style='display:none;'>" . esc_html__('Please select atleast one badge.', 'ARMember') . "</div>";
            $confirmBox .= "<div class='arm_confirm_box_btn_container'>";
            $confirmBox .= "<button type='button' class='arm_confirm_box_btn armcancel' onclick='hideConfirmBoxCallback();'>" . esc_html__('Cancel', 'ARMember') . "</button>";
            $confirmBox .= "<button type='button' class='arm_confirm_box_btn armok ".esc_attr($btnClass)."' data-item_id='".esc_attr($item_id)."' data-type='".esc_attr($deleteType)."'>" . esc_html__('Delete', 'ARMember') . "</button>";
            $confirmBox .= "</div>";
            $confirmBox .= "</div>";
            $confirmBox .= "</div>";
            return $confirmBox;
        }

        function arm_get_bpopup_html($args) {
            global $wp, $wpdb, $ARMember, $arm_slugs;
            $defaults = array(
                'id' => '',
                'class' => 'arm_bpopup_wrapper',
                'title' => '',
                'content' => '',
                'button_id' => '',
                'button_onclick' => '',
                'ok_btn_class' => '',
                'ok_btn_text' => esc_html__('Ok', 'ARMember'),
                'cancel_btn_text' => esc_html__('Cancel', 'ARMember'),
                'close_icon' => false
            );
            extract(shortcode_atts($defaults, $args));
            /* Generate Popup HTML */
            $popup = '<div id="' . esc_attr( $id ) . '" class="popup_wrapper ' . esc_attr( $class ) . '"><div class="popup_wrapper_inner">';
            $popup .= '<div class="popup_header">';           
            $popup .= '<span class="popup_header_text">' . $title; //phpcs:ignore
            if($close_icon)
            {
                $popup         .= '<span class="popup_close_btn arm_popup_close_btn"></span>'; //phpcs:ignore
            }
            $popup .= '</span>';
            $popup .= '</div>';
            $popup .= '<div class="popup_content_text">' . $content . '</div>';//phpcs:ignore
            $popup .= '<div class="armclear"></div>';
            $popup .= '<div class="popup_footer">';
            $popup .= '<div class="popup_content_btn_wrapper">';
            $ok_btn_onclick = (!empty($button_onclick)) ? 'onclick="' . $button_onclick . '"' : '';
            $popup .= '<button type="button" class="arm_submit_btn popup_ok_btn ' . esc_attr($ok_btn_class) . '" id="' . esc_attr( $button_id ) . '" ' . $ok_btn_onclick . '>' . esc_html($ok_btn_text) . '</button>';
            $popup .= '</div>';
            $popup .= '<div class="popup_content_btn_wrapper">';
            $popup .= '<button class="arm_cancel_btn popup_close_btn" type="button">' . esc_html($cancel_btn_text) . '</button>';
            $popup .= '</div>';           
            $popup .= '</div>';
            $popup .= '<div class="armclear"></div>';
            $popup .= '</div></div>';
            return $popup;
        }

        function arm_get_bpopup_html_payment($args) {
            global $wp, $wpdb, $ARMember, $arm_slugs;
            $defaults = array(
                'id' => '',
                'class' => 'arm_bpopup_wrapper',
                'title' => '',
                'content' => '',
                'button_id' => '',
                'button_onclick' => '',
                'ok_btn_class' => '',
                'ok_btn_text' => esc_html__('Ok', 'ARMember'),
                'cancel_btn_text' => esc_html__('Cancel', 'ARMember'),
            );
            extract(shortcode_atts($defaults, $args));
            /* Generate Popup HTML */
            $popup = '<div id="' . esc_attr($id) . '" class="popup_wrapper ' . esc_attr($class) . '"><div class="popup_wrapper_inner">';
            $popup .= '<div class="popup_header">';
            $popup .= '<span class="popup_close_btn arm_popup_close_btn"></span>';
            $popup .= '<span class="popup_header_text">' . esc_html($title) . '</span>';
            $popup .= '</div>';
            $popup .= '<div class="popup_content_text">' . $content . '</div>'; //phpcs:ignore
            $popup .= '<div class="armclear"></div>';
            $popup .= '<div class="popup_footer">';
            $popup .= '<div class="popup_content_btn_wrapper">';
            $ok_btn_onclick = (!empty($button_onclick)) ? 'onclick="' . $button_onclick . '"' : '';
            $popup .= '<button type="button" class="arm_submit_btn popup_ok_btn ' . esc_attr($ok_btn_class) . '" id="' . esc_attr($button_id) . '" ' . $ok_btn_onclick . '>' . esc_html($ok_btn_text) . '</button>';
            $popup .= '</div>';

            $popup .= '</div>';
            $popup .= '<div class="armclear"></div>';
            $popup .= '</div></div>';
            return $popup;
        }

        function arm_after_delete_term($term, $tt_id, $taxonomy, $deleted_term) {
            global $wp, $wpdb, $ARMember, $arm_slugs;
            delete_arm_term_meta($term, 'arm_protection');
            delete_arm_term_meta($term, 'arm_access_plan');
        }

        /**         * **************************************************************************************
         * * String Utilities Functions
         * * ************************************************************************************* */

        /**
         * Trims deeply; alias of `trim_deep`.
         * @param string|array $value Either a string, an array, or a multi-dimensional array, filled with integer and/or string values.
         * @return string|array Either the input string, or the input array; after all data is trimmed up according to arguments passed in.
         */
        public static function trim($value = '', $chars = FALSE, $extra_chars = FALSE) {
            return self::trim_deep($value, $chars, $extra_chars);
        }

        /**
         * Trims deeply; or use {@link s2Member\Utilities\self::trim()}.
         * @param string|array $value Either a string, an array, or a multi-dimensional array, filled with integer and/or string values.
         * @return string|array Either the input string, or the input array; after all data is trimmed up according to arguments passed in.
         */
        public static function trim_deep($value = '', $chars = FALSE, $extra_chars = FALSE) {
            $chars = (is_string($chars)) ? $chars : " \t\n\r\0\x0B";
            $chars = (is_string($extra_chars)) ? $chars . $extra_chars : $chars;
            if (is_array($value)) {
                foreach ($value as &$r) {
                    $r = self::trim_deep($r, $chars);
                }
                return $value;
            }
            return trim((string) $value, $chars);
        }

        /**
         * Trims all single/double quote entity variations deeply.
         * This is useful on Shortcode attributes mangled by a Visual Editor.
         * @param string|array $value Either a string, an array, or a multi-dimensional array, filled with integer and/or string values.
         * @return string|array Either the input string, or the input array; after all data is trimmed up.
         */
        public static function trim_qts_deep($value = '') {
            $quote_entities_variations = array(
                '&apos;' => '&apos;',
                '&#0*39;' => '&#39;',
                '&#[xX]0*27;' => '&#x27;',
                '&lsquo;' => '&lsquo;',
                '&#0*8216;' => '&#8216;',
                '&#[xX]0*2018;' => '&#x2018;',
                '&rsquo;' => '&rsquo;',
                '&#0*8217;' => '&#8217;',
                '&#[xX]0*2019;' => '&#x2019;',
                '&quot;' => '&quot;',
                '&#0*34;' => '&#34;',
                '&#[xX]0*22;' => '&#x22;',
                '&ldquo;' => '&ldquo;',
                '&#0*8220;' => '&#8220;',
                '&#[xX]0*201[cC];' => '&#x201C;',
                '&rdquo;' => '&rdquo;',
                '&#0*8221;' => '&#8221;',
                '&#[xX]0*201[dD];' => '&#x201D;'
            );
            $qts = implode('|', array_keys($quote_entities_variations));
            return is_array($value) ? array_map('self::trim_qts_deep', $value) : preg_replace('/^(?:' . $qts . ')+|(?:' . $qts . ')+$/', '', (string) $value);
        }

        /**
         * Trims HTML whitespace.
         * This is useful on Shortcode content.
         * @param string $string Input string to trim.
         * @return string Output string with all HTML whitespace trimmed away.
         */
        public static function trim_html($string = '') {
            $whitespace = '&nbsp;|\<br\>|\<br\s*\/\>|\<p\>(?:&nbsp;)*\<\/p\>';
            return preg_replace('/^(?:' . $whitespace . ')+|(?:' . $whitespace . ')+$/', '', (string) $string);
        }

        public static function arm_set_ini_for_access_rules() {
            $memoryLimit = ini_get('memory_limit');
            if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
                if ($matches[2] == 'M') {
                    $memoryLimit = $matches[1] * 1024 * 1024;
                } else if ($matches[2] == 'K') {
                    $memoryLimit = $matches[1] * 1024;
                }
            }
            if ($memoryLimit < (256 * 1024 * 1024)) {
                /* @define('WP_MEMORY_LIMIT', '256M'); */
                @ini_set('memory_limit', '256M');//phpcs:ignore
            }
            set_time_limit(0); /* Set Maximum Execution Time */
        }

        public static function arm_set_ini_for_importing_users() {
            $memoryLimit = ini_get('memory_limit');
            if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
                if ($matches[2] == 'M') {
                    $memoryLimit = $matches[1] * 1024 * 1024;
                } else if ($matches[2] == 'K') {
                    $memoryLimit = $matches[1] * 1024;
                }
            }
            if ($memoryLimit < (512 * 1024 * 1024)) {
                /* @define('WP_MEMORY_LIMIT', '256M'); */
                @ini_set('memory_limit', '512M');//phpcs:ignore
            }
            set_time_limit(0); /* Set Maximum Execution Time */
        }

        function arm_add_page_label_css($hook) {
            if ('edit.php' != $hook) {
                return;
            }
            $postLabelCss = '<style type="text/css">';
            $postLabelCss .= '.arm_set_page_label, .arm_set_page_label_protected, .arm_set_page_label_drippred{display: inline-block;margin-right: 5px;padding: 3px 8px;font-size: 11px;line-height: normal;color: #fff;border-radius: 10px;-webkit-border-radius: 10px;-moz-border-radius: 10px;-o-border-radius: 10px;}';
            $postLabelCss .= ' .arm_set_page_label{background-color: #53ba73;}';
            $postLabelCss .= ' .arm_set_page_label_protected{background-color: #191111;}';
            $postLabelCss .= ' .arm_set_page_label_drippred{background-color: #e34581;}';
            $postLabelCss .= '</style>';
            echo $postLabelCss; //phpcs:ignore
        }

        function arm_add_set_page_label($states, $post = null) {
            global $wpdb, $ARMember, $arm_drip_rules, $post;
            if (isset($post->ID)) {
                $str = '';
                if (get_post_type($post->ID) == 'page') {
                    $arm_page_settings = $this->arm_get_single_global_settings('page_settings');
                    if (!empty($arm_page_settings)) {
                        foreach ($arm_page_settings as $key => $value) {
                            if ($value == $post->ID) {
                                switch (strtolower($key)) {
                                    case 'register_page_id':
                                        $title_label = esc_html__('Registration page', 'ARMember');
                                        break;
                                    case 'login_page_id':
                                        $title_label = esc_html__('Login page', 'ARMember');
                                        break;
                                    case 'forgot_password_page_id':
                                        $title_label = esc_html__('Forgot Password page', 'ARMember');
                                        break;
                                    case 'edit_profile_page_id':
                                        $title_label = esc_html__('Edit Profile page', 'ARMember');
                                        break;
                                    case 'change_password_page_id':
                                        $title_label = esc_html__('Change Password page', 'ARMember');
                                        break;
                                    case 'member_profile_page_id':
                                        $title_label = esc_html__('Member Profile page', 'ARMember');
                                        break;
                                    case 'guest_page_id':
                                        $title_label = esc_html__('Guest page', 'ARMember');
                                        break;
                                    case 'paid_post_page_id':
                                        $title_label = esc_html__('Paid Post Purchase page', 'ARMember');
                                        break;
                                    case 'community_group_page_id':
                                        $title_label = esc_html__('ARMember Social Community Group Page', 'ARMember');
                                        break;
                                    case 'child_user_signup_page_id':
                                        $title_label = esc_html__('ARMember Child Signup Page', 'ARMember');
                                        break;
                                }
                                if (!empty($title_label)) {
                                    $str .= '<div class="arm_set_page_label">ARMember ' . esc_html($title_label) . '</div>';
                                }
                            }
                        }
                    }
                }

                $arm_protect = 0;
                $item_plans = get_post_meta($post->ID, 'arm_access_plan');
                $item_plans = (!empty($item_plans)) ? $item_plans : array();

                if (count($item_plans) == 0)
                    $arm_protect = 0;
                else
                    $arm_protect = 1;

                if (!empty($arm_protect) && $arm_protect == 1) {
                    $str .= '<div class="arm_set_page_label_protected">' . esc_html__("ARMember Protected", 'ARMember') . '</div>';
                }
                /**
                 * Check If Post Has Drip Rules
                 */
                if ($arm_drip_rules->isDripFeature) {
                    $rule_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(`arm_rule_id`) FROM `" . $ARMember->tbl_arm_drip_rules . "` WHERE `arm_item_id`=%d AND `arm_rule_status`=%d",$post->ID,1) ); //phpcs:ignore --Reason $ARMember->tbl_arm_drip_rules is a table name
                    if (!empty($rule_count)) {
                        $str .= '<div class="arm_set_page_label_drippred">' . esc_html__("ARMember Dripped", 'ARMember') . '</div>';
                    }
                }

                if (!empty($str)) {
                    $states[] = $str;
                }
            }
            return $states;
        }

        function arm_update_feature_settings() {
            global $wp, $wpdb, $wp_rewrite, $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_feature_settings'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $response = array('type' => 'error', 'msg' => esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
            if (!empty($_POST['arm_features_options'])) {//phpcs:ignore
                $features_options = sanitize_text_field($_POST['arm_features_options']);//phpcs:ignore
                $arm_features_status = (!empty($_POST['arm_features_status'])) ? intval($_POST['arm_features_status']) : 0;//phpcs:ignore

                $arm_default_module_array = array(
                    'arm_is_user_private_content_feature',
                    'arm_is_social_feature', 
                    'arm_is_opt_ins_feature', 
                    'arm_is_pro_ration_feature',
                    'arm_is_drip_content_feature', 
                    'arm_is_social_login_feature', 
                    'arm_is_coupon_feature', 
                    'arm_is_buddypress_feature', 
                    'arm_is_invoice_tax_feature', 
                    'arm_is_woocommerce_feature', 
                    'arm_is_multiple_membership_feature', 
                    'arm_is_gutenberg_block_restriction_feature', 
                    'arm_is_mycred_feature',
                    'arm_is_pay_per_post_feature',
                    'arm_is_api_service_feature',
                    'arm_is_plan_limit_feature',
                    'arm_is_beaver_builder_restriction_feature', 
                    'arm_is_divi_builder_restriction_feature', 
                    'arm_is_wpbakery_page_builder_restriction_feature', 
                    'arm_is_fusion_builder_restriction_feature', 
                    'arm_is_oxygen_builder_restriction_feature', 
                    'arm_is_siteorigin_builder_restriction_feature', 
                    'arm_is_bricks_builder_restriction_feature', 
                );
                if(in_array($features_options, $arm_default_module_array))
                {
                    if ($arm_features_status == 1) {

                        do_action('arm_update_feature_settings', $_POST);//phpcs:ignore

                        if ($features_options == 'arm_is_buddypress_feature') {
                            if (file_exists( WP_PLUGIN_DIR . "/buddypress/bp-loader.php") || file_exists( WP_PLUGIN_DIR . "/buddyboss-platform/bp-loader.php")) {
                                if (is_plugin_active('buddypress/bp-loader.php') || is_plugin_active('buddyboss-platform/bp-loader.php')) {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_buddypress_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } else {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'buddypress_error', 'msg' => esc_html__('Please activate BuddyPress/Buddyboss and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'buddypress_error', 'msg' => esc_html__('Please install BuddyPress/Buddyboss and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_user_private_content_feature'){
                            $isPageExist = false;
                            $arm_private_content_status = (!empty($_POST['arm_features_status'])) ? intval($_POST['arm_features_status']) : 0;//phpcs:ignore
                            update_option($features_options, $arm_private_content_status);
                            $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                            echo wp_json_encode($response);
                            die();

                        } else if ($features_options == 'arm_is_social_feature') {
                            $isPageExist = false;
                            $old_member_profile_page_id = isset($this->global_settings['member_profile_page_id']) ? $this->global_settings['member_profile_page_id'] : 0;
                            if (!empty($old_member_profile_page_id) && $old_member_profile_page_id != 0) {
                                $isPageExist = true;
                                $pageData = get_post($old_member_profile_page_id);
                                if (!isset($pageData->ID) || empty($pageData->ID)) {
                                    $isPageExist = false;
                                }
                            }
                            if (!$isPageExist) {
                                $profileTemplateID = $wpdb->get_var( $wpdb->prepare("SELECT `arm_id` FROM `" . $ARMember->tbl_arm_member_templates . "` WHERE `arm_type`=%s ORDER BY `arm_id` ASC LIMIT 1",'profile') ); //phpcs:ignore --Reason $ARMember->tbl_arm_member_templates is a table name
                                $profileTemplateShortcode = (!empty($profileTemplateID)) ? '[arm_template type="profile" id="' . $profileTemplateID . '"]' : '';
                                $profilePageData = array(
                                    'post_title' => 'Profile',
                                    'post_name' => 'arm_member_profile',
                                    'post_content' => $profileTemplateShortcode,
                                    'post_status' => 'publish',
                                    'post_parent' => 0,
                                    'post_author' => 1,
                                    'post_type' => 'page',
                                );
                                $page_id = wp_insert_post($profilePageData);
                                $new_global_settings = $this->arm_get_all_global_settings();
                                $new_global_settings['page_settings']['member_profile_page_id'] = $page_id;
                                update_option('arm_global_settings', $new_global_settings);
                                $this->arm_user_rewrite_rules();
                                $wp_rewrite->flush_rules(false);
                            }
                            $arm_features_status = (!empty($_POST['arm_features_status'])) ? intval($_POST['arm_features_status']) : 0;//phpcs:ignore
                            update_option($features_options, $arm_features_status);
                            $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                            echo wp_json_encode($response);
                            die();
                        } else if ($features_options == 'arm_is_pro_ration_feature') {
                            $is_multiple_membership_feature = get_option('arm_is_multiple_membership_feature', 0);
                            $isMultipleMembershipFeature = ($is_multiple_membership_feature == '1') ? true : false;
                            if ($isMultipleMembershipFeature != true) {
                                update_option($features_options, $arm_features_status);
                                update_option('arm_is_pro_ration_feature_old', $arm_features_status);
                                $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'pro_ration_error', 'msg' => esc_html__('ARMember Multiple Membership module is Activated. So ARMember Pro-Rata can\'t Activate.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_multiple_membership_feature'){
                            $is_pro_ration_feature = get_option('arm_is_pro_ration_feature', 0);
                            $isProRationFeature = ($is_pro_ration_feature == '1') ? true : false;
                            if ($isProRationFeature == true) {
                                update_option($features_options, 0);
                                $response = array('type' => 'multiple_membership_error', 'msg' => esc_html__('ARMember Pro-Rata module is Activated. So ARMember Multiple Membership can\'t Activate.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            } else {                            
                                $user_id = get_current_user_id();
                                $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : '0';//phpcs:ignore
                                $column_list = maybe_unserialize(get_user_meta($user_id, 'arm_members_hide_show_columns_' . $form_id, true));
                                if ($column_list != "") {
                                    $total_column = count($column_list);
                                    $column_list['0'] = 1;
                                    $column_list['1'] = 1;
                                    $column_list[$total_column - 2] = 1;
                                    $column_list[$total_column - 3] = 1;
                                    $members_show_hide_serialize = maybe_serialize($column_list);
                                    $prev_value = maybe_unserialize(get_user_meta($user_id, 'arm_members_hide_show_columns_' . $form_id, true));
                                    update_user_meta($user_id, 'arm_members_hide_show_columns_' . $form_id, $members_show_hide_serialize);
                                }
                                $arm_features_status = (!empty($_POST['arm_features_status'])) ? intval($_POST['arm_features_status']) : 0;//phpcs:ignore
                                update_option($features_options, $arm_features_status);
                                $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_mycred_feature') {
                            if (file_exists(WP_PLUGIN_DIR . "/mycred/mycred.php")) {
                                if (is_plugin_active('mycred/mycred.php')) {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_mycred_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } else {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'mycred_error', 'msg' => esc_html__('Please activate myCRED and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'mycred_error', 'msg' => esc_html__('Please install myCRED and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_pay_per_post_feature'){
                            $isPageExist = false;
                            $arm_pay_per_post_status = (!empty($_POST['arm_features_status'])) ? intval($_POST['arm_features_status']) : 0;//phpcs:ignore
                            update_option($features_options, $arm_pay_per_post_status);
                            $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                            echo wp_json_encode($response);
                            die();

                        } else if ($features_options == 'arm_is_woocommerce_feature') 
                        {
                            if (file_exists(WP_PLUGIN_DIR . "/woocommerce/woocommerce.php")) 
                            {
                                if (is_plugin_active('woocommerce/woocommerce.php')) 
                                {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_woocommerce_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully....', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } 
                                else 
                                {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'wocommerce_error', 'msg' => esc_html__('Please activate Woocommerce and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } 
                            else 
                            {
                                update_option($features_options, 0);
                                $response = array('type' => 'wocommerce_error', 'msg' => esc_html__('Please install Woocommerce and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_beaver_builder_restriction_feature') {
                            if (file_exists( WP_PLUGIN_DIR . "/beaver-builder-lite-version/fl-builder.php") || file_exists( WP_PLUGIN_DIR . "/bb-plugin/fl-builder.php")) {
                                if (is_plugin_active('beaver-builder-lite-version/fl-builder.php') || is_plugin_active('bb-plugin/fl-builder.php')) {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_beaver_builder_restriction_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } else {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'beaver_builder_error', 'msg' => esc_html__('Please activate Beaver Builder and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'beaver_builder_error', 'msg' => esc_html__('Please install Beaver Builder and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_divi_builder_restriction_feature') {
                            if (file_exists( WP_PLUGIN_DIR . "/divi-builder/divi-builder.php") || wp_get_theme()->get('Name') == 'Divi' || is_child_theme('Divi')) {
                                if (is_plugin_active('divi-builder/divi-builder.php') || wp_get_theme()->get('Name') == 'Divi' || is_child_theme('Divi')) {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_divi_builder_restriction_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } else {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'divi_builder_error', 'msg' => esc_html__('Please activate Divi Builder or Divi Theme and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'divi_builder_error', 'msg' => esc_html__('Please install Divi Builder or Divi Theme and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_wpbakery_page_builder_restriction_feature') {
                            if ( file_exists( WP_PLUGIN_DIR . "/js_composer/js_composer.php") ) {
                                if ( is_plugin_active('js_composer/js_composer.php') ) {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_wpbakery_page_builder_restriction_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } else {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'wpbakery_page_builder_error', 'msg' => esc_html__('Please activate WPBakery Page Builder and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'wpbakery_page_builder_error', 'msg' => esc_html__('Please install WPBakery Page Builder and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_fusion_builder_restriction_feature') {
                            if (file_exists( WP_PLUGIN_DIR . "/fusion-builder/fusion-builder.php") ) {
                                if (is_plugin_active('fusion-builder/fusion-builder.php') ) {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_fusion_builder_restriction_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } else {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'fusion_builder_error', 'msg' => esc_html__('Please activate Fusion Builder or Avada Theme and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'fusion_builder_error', 'msg' => esc_html__('Please install Fusion Builder or Avada Theme and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_oxygen_builder_restriction_feature') {
                            if (file_exists( WP_PLUGIN_DIR . "/oxygen/functions.php") ) {
                                if (is_plugin_active('oxygen/functions.php') ) {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_oxygen_builder_restriction_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } else {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'oxygen_builder_error', 'msg' => esc_html__('Please activate Oxygen Builder and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'oxygen_builder_error', 'msg' => esc_html__('Please install Oxygen Builder and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_siteorigin_builder_restriction_feature') {
                            if (file_exists( WP_PLUGIN_DIR . "/siteorigin-panels/siteorigin-panels.php") ) {
                                if (is_plugin_active('siteorigin-panels/siteorigin-panels.php') ) {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_siteorigin_builder_restriction_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } else {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'siteorigin_builder_error', 'msg' => esc_html__('Please activate SiteOrigin Builder and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'siteorigin_builder_error', 'msg' => esc_html__('Please install SiteOrigin Builder and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else if ($features_options == 'arm_is_bricks_builder_restriction_feature') {
                            if (wp_get_theme()->get('Name') == 'Bricks' || is_child_theme('Bricks')) {
                                if (wp_get_theme()->get('Name') == 'Bricks' || is_child_theme('Bricks')) {
                                    update_option($features_options, $arm_features_status);
                                    update_option('arm_is_bricks_builder_restriction_feature_old', $arm_features_status);
                                    $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                } else {
                                    update_option($features_options, 0);
                                    $response = array('type' => 'bricks_builder_error', 'msg' => esc_html__('Please activate Bricks Builder and try to active this add-on.', 'ARMember'));
                                    echo wp_json_encode($response);
                                    die();
                                }
                            } else {
                                update_option($features_options, 0);
                                $response = array('type' => 'bricks_builder_error', 'msg' => esc_html__('Please install Bricks Builder and try to active this add-on.', 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }
                        } else {
                            $arm_features_status = (!empty($_POST['arm_features_status'])) ? intval($_POST['arm_features_status']) : 0;//phpcs:ignore
                            update_option($features_options, $arm_features_status);
                            $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                            echo wp_json_encode($response);
                            die();
                        }
                    } else {
                        if($_POST['arm_features_options'] == "arm_is_pay_per_post_feature")//phpcs:ignore
                        {
                            $args = array(
                                'meta_query' => array(
                                    array(
                                        'key' => 'arm_user_post_ids',
                                        'value' => '',
                                        'compare' => '!='
                                    ),
                                )
                            );

                            $armDeactiveCount = 0;
                            $amTotalUsers = get_users($args);
                            if (!empty($amTotalUsers)) 
                            {
                                foreach ($amTotalUsers as $usr) 
                                {
                                    $user_id = $usr->ID;
                                    $arm_user_paid_post = get_user_meta($user_id,'arm_user_post_ids', true);

                                    if(!empty($arm_user_paid_post) && is_array($arm_user_paid_post))
                                    {
                                        $armDeactiveCount++;
                                    }
                                }
                            }

                            if($armDeactiveCount > 0)
                            {
                                $response = array('type' => 'wocommerce_error', 'msg' => esc_html__("One or more users have paid post, so addon can't be deactivated.", 'ARMember'));
                                echo wp_json_encode($response);
                                die();
                            }                        
                        }
                        do_action('arm_deactivate_feature_settings', $_POST);//phpcs:ignore
                        if($features_options == 'arm_is_invoice_tax_feature'){
                            $all_opts = maybe_unserialize(get_option('arm_global_settings'));
                            $all_opts["general_settings"]["enable_tax"] = 0;
                            $all_opts["general_settings"]["tax_amount"] = 0;
                            update_option('arm_global_settings', $all_opts);
                        } 
                        
                        
                        update_option($features_options, 0);
                        $response = array('type' => 'success', 'msg' => esc_html__('Features Settings Updated Successfully.', 'ARMember'));
                        echo wp_json_encode($response);
                        die();
                    }
                }
            } /* END `(!empty($_POST['arm_features_options']))` */
        }

        function arm_custom_css_detail() {
            global $wp, $wpdb, $arm_slugs, $current_user, $arm_errors, $ARMember, $arm_subscription_plans, $arm_drip_rules,$arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
            $css_section = !empty($_POST['css_section']) ? sanitize_text_field($_POST['css_section']) : '';//phpcs:ignore
            $default_select = '';
            if (!empty($css_section)) {
                if ($css_section == 'arm_general') {
                    $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'],'1',1);
                    $default_select = 'arm_account_detail';
                } else {
                    if($css_section == 'arm_membership_setup'){
                        $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_setups'],'1',1);
                    }
                    $default_select = $css_section;
                }
            }
            $membership_allow_keys = array('arm_membership_setup', 'arm_membership_setup_plans', 'arm_membership_setup_form', 'arm_membership_setup_gateways', 'arm_membership_setup_coupon', 'arm_membership_setup_summary');
            $membership_not_allow_keys = array('arm_membership_setup_plans', 'arm_membership_setup_form', 'arm_membership_setup_gateways', 'arm_membership_setup_coupon', 'arm_membership_setup_summary');
            $arm_custom_css_arr = arm_custom_css_class_info();
            if (!empty($arm_custom_css_arr)) {
                echo $arm_ajax_pattern_start;
                ?>
                <div class="arm_custom_css_detail_popup popup_wrapper arm_custom_css_detail_popup_wrapper">
                    <div class="popup_wrapper_inner" style="overflow: hidden;">
                        <div class="popup_header">
                            <span class="popup_close_btn arm_popup_close_btn arm_custom_css_detail_close_btn"></span>
                            <span class="add_rule_content"><?php esc_html_e('ARMember CSS Class Information', 'ARMember'); ?></span>
                        </div>
                        <div class="popup_content_text arm_custom_css_detail_popup_text">
                            <div class="arm_custom_css_detail_list">
                                <div class="arm_custom_css_detail_list_left_box">
                                    <ul>
                                        <?php
                                        foreach ($arm_custom_css_arr as $key => $css_detail) {
                                            if ($css_section == 'arm_general' && in_array($key, $membership_not_allow_keys)) {
                                                continue;
                                            }
                                            if ($css_section == 'arm_membership_setup' && !in_array($key, $membership_allow_keys)) {
                                                continue;
                                            }
                                            ?>											
                                            <li><a class="arm_custom_css_menu_link <?php echo ($key == $default_select) ? 'active' : ''; //phpcs:ignore?>" data-custom-class="<?php echo esc_attr($key); ?>"><?php echo esc_attr($css_detail['section_title']['title']); ?></a></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                                <div class="arm_custom_css_detail_list_right_box">
                                    <?php foreach ($arm_custom_css_arr as $key => $css_detail) { ?>
                                        <div class="arm_custom_css_detail_list_item <?php echo esc_attr($key) . "_section"; ?> <?php echo ($key == $default_select) ? '' : 'hidden_section'; ?>">
                                            <div class="arm_custom_css_detail_title"><?php echo esc_attr($css_detail['section_title']['title']); ?></div>
                                            <?php foreach ($css_detail['section_class'] as $class_detail) { ?>
                                                <div class="arm_custom_css_detail_cls"><?php echo esc_attr($class_detail['class']); ?></div>
                                                <div class="arm_custom_css_detail_sub_note">
                                                    {<br><span class="arm_custom_css_detail_sub_note_text"><?php echo "// " . esc_attr($class_detail['note']); ?></span><br>}
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <?php
                                        if ($css_section == 'arm_general' && $key == 'arm_membership_setup') {
                                            foreach ($membership_not_allow_keys as $membership_not_allow) {
                                                if ($membership_not_allow == 'arm_membership_setup_form') {
                                                    continue;
                                                }
                                                $setup_css_detail = $arm_custom_css_arr[$membership_not_allow];
                                                ?>
                                                <div class="arm_custom_css_detail_list_item <?php echo esc_attr($key) . "_section"; ?> hidden_section">
                                                    <div class="arm_custom_css_detail_title"><?php echo esc_attr($setup_css_detail['section_title']['title']); ?></div>
                                                        <?php foreach ($setup_css_detail['section_class'] as $setup_class_detail) { ?>														
                                                        <div class="arm_custom_css_detail_cls"><?php echo esc_attr($setup_class_detail['class']); ?></div>
                                                        <div class="arm_custom_css_detail_sub_note">
                                                            {<br><span class="arm_custom_css_detail_sub_note_text"><?php echo "// " . esc_attr($setup_class_detail['note']); ?></span><br>}
                                                        </div>
                                                            <?php
                                                        }
                                                        ?>
                                                </div>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>									
                                </div>								
                            </div>
                        </div>
                        <div class="armclear"></div>
                    </div>
                </div>
                <?php
                echo $arm_ajax_pattern_end;
            }
            exit;
        }

        function arm_section_custom_css_detail() {
            global $wp, $wpdb, $arm_slugs, $current_user, $arm_errors, $ARMember, $arm_subscription_plans, $arm_drip_rules,$arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
            $css_section = $_POST['css_section']; //phpcs:ignore
            if($css_section == 'arm_form'){
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_forms'],'1',1);
            }
            else if($css_section == 'arm_directory' || $css_section == 'arm_membership_card'){
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_member_templates'],'1',1);
            }
            $arm_custom_css_arr = arm_custom_css_class_info();
            if (!empty($arm_custom_css_arr[$css_section])) {
                $css_detail = $arm_custom_css_arr[$css_section];
                echo $arm_ajax_pattern_start;
                ?>
                <div class="arm_section_custom_css_detail_popup popup_wrapper arm_section_custom_css_detail_popup_wrapper">
                    <div class="popup_wrapper_inner" style="overflow: hidden;">
                        <div class="popup_header">
                            <span class="popup_close_btn arm_popup_close_btn arm_section_custom_css_detail_close_btn"></span>
                            <span class="add_rule_content"><?php esc_html_e('ARMember CSS Class Information', 'ARMember'); ?></span>
                        </div>
                        <div class="popup_content_text arm_section_custom_css_detail_popup_text">
                            <div class="arm_section_custom_css_detail_list">
                                <div class="arm_section_custom_css_detail_list_right_box">
                                    <div class="arm_section_custom_css_detail_list_item <?php echo esc_attr($css_section) . "_section"; ?>">
                                        <div class="arm_section_custom_css_detail_title"><?php echo esc_attr($css_detail['section_title']['title']); ?></div>
                                    <?php foreach ($css_detail['section_class'] as $class_detail) { ?>
                                            <div class="arm_section_custom_css_detail_cls"><?php echo esc_attr($class_detail['class']); ?></div>
                                            <div class="arm_section_custom_css_detail_sub_note">
                                                {<br><span class="arm_section_custom_css_detail_sub_note_text"><?php echo "// " . esc_attr($class_detail['note']); ?></span><br>}
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>								
                            </div>
                        </div>
                        <div class="armclear"></div>
                    </div>
                </div>
                    <?php
                    echo $arm_ajax_pattern_end;
            }
            exit;
        }

        function arm_get_front_font_style() {
            global $wp, $wpdb, $arm_slugs, $current_user, $arm_errors, $ARMember, $arm_subscription_plans, $arm_drip_rules, $arm_member_forms;
            $frontfontstyle = array();
            $frontFontFamilys = array();
            $frontfontOptions = array('level_1_font', 'level_2_font', 'level_3_font', 'level_4_font', 'link_font', 'button_font');
            $frontOptions = isset($this->global_settings['front_settings']) ? $this->global_settings['front_settings'] : array();
            foreach ($frontfontOptions as $key) {
                $ffont_family = (isset($frontOptions[$key]['font_family'])) ? esc_attr($frontOptions[$key]['font_family']) : "Helvetica";
                $ffont_family = ($ffont_family == 'inherit') ? '' : $ffont_family; 
                $frontFontFamilys[] = $ffont_family;
                $ffont_size = (isset($frontOptions[$key]['font_size'])) ? esc_attr($frontOptions[$key]['font_size']) : "";
                $ffont_color = (isset($frontOptions[$key]['font_color'])) ? esc_attr($frontOptions[$key]['font_color']) : "";
                $ffont_bold = (isset($frontOptions[$key]['font_bold']) && $frontOptions[$key]['font_bold'] == '1') ? "font-weight: bold !important;" : "font-weight: normal !important;";
                $ffont_italic = (isset($frontOptions[$key]['font_italic']) && $frontOptions[$key]['font_italic'] == '1') ? "font-style: italic !important;" : "font-style: normal !important;";
                $ffont_decoration = (!empty($frontOptions[$key]['font_decoration'])) ? "text-decoration: " . $frontOptions[$key]['font_decoration'] . " !important;" : "text-decoration: none !important;";

                $front_font_family = (!empty($ffont_family)) ? "font-family: ".esc_attr($ffont_family).", sans-serif, 'Trebuchet MS' !important;" : "";

                $frontOptions[$key]['font'] = "{$front_font_family} font-size: ".esc_attr($ffont_size)."px !important;color: ".esc_attr($ffont_color)." !important;".esc_attr($ffont_bold) . esc_attr($ffont_italic) . esc_attr($ffont_decoration);
            }
            $gFontUrl = $arm_member_forms->arm_get_google_fonts_url($frontFontFamilys);
            if (!empty($gFontUrl)) {
                $frontfontstyle['google_font_url'] = esc_url( $gFontUrl );
            }
            $frontfontstyle['frontOptions'] = $frontOptions;
            return $frontfontstyle;
        }




        function arm_transient_set_action($arm_transient_name, $arm_transient_value, $arm_transient_time)
        {
            $arm_return_transient_status = 0;
            if(!empty($arm_transient_name) && !empty($arm_transient_value) && !empty($arm_transient_time))
            {
                set_transient($arm_transient_name, $arm_transient_value, $arm_transient_time);
                $arm_return_transient_status = 1;
            }
            return $arm_return_transient_status;
        }

        function arm_transient_get_action($arm_transient_name)
        {
            global $ARMember;
            $arm_return_transient_status = 0;
            if(!empty($arm_transient_name))
            {
                $arm_get_transient_value = get_transient($arm_transient_name);
                if(!empty($arm_get_transient_value))
                {
                    $arm_return_transient_status = 1;
                }
            }
            return $arm_return_transient_status;
        }

    }

}
global $arm_global_settings;
$arm_global_settings = new ARM_global_settings();
if (!function_exists('arm_generate_random_code')) {

    function arm_generate_random_code($length = 10) {
        $charLength = round($length * 0.8);
        $numLength = round($length * 0.2);
        $keywords = array(
            array('count' => $charLength, 'char' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
            array('count' => $numLength, 'char' => '0123456789')
        );
        $temp_array = array();
        foreach ($keywords as $char_set) {
            for ($i = 0; $i < $char_set['count']; $i++) {
                $temp_array[] = $char_set['char'][rand(0, strlen($char_set['char']) - 1)];
            }
        }
        shuffle($temp_array);
        return implode('', $temp_array);
    }

}

if (!function_exists('arm_generate_captcha_code')) {

    function arm_generate_captcha_code($length = 8) {
        $possible_letters = '23456789bcdfghjkmnpqrstvwxyz';
        $random_dots = 0;
        $random_lines = 20;
        $code = '';
        $i = 0;
        while ($i < $length) {
            $code .= substr($possible_letters, mt_rand(0, strlen($possible_letters) - 1), 1);
            $i++;
        }
        return $code;
    }

}

if (!function_exists('add_arm_term_meta')) {

    /**
     * Add meta data field to a term.
     *
     * @param int $term_id Post ID.
     * @param string $key Metadata name.
     * @param mixed $value Metadata value.
     * @param bool $unique Optional, default is false. Whether the same key should not be added.
     * @return bool False for failure. True for success.
     */
    function add_arm_term_meta($term_id, $meta_key, $meta_value, $unique = false) {
        return add_metadata('arm_term', $term_id, $meta_key, $meta_value, $unique);
    }

}
if (!function_exists('delete_arm_term_meta')) {

    /**
     * Remove metadata matching criteria from a term.
     *
     * You can match based on the key, or key and value. Removing based on key and
     * value, will keep from removing duplicate metadata with the same key. It also
     * allows removing all metadata matching key, if needed.
     *
     * @param int $term_id term ID
     * @param string $meta_key Metadata name.
     * @param mixed $meta_value Optional. Metadata value.
     * @return bool False for failure. True for success.
     */
    function delete_arm_term_meta($term_id, $meta_key, $meta_value = '') {
        return delete_metadata('arm_term', $term_id, $meta_key, $meta_value);
    }

}
if (!function_exists('get_arm_term_meta')) {

    /**
     * Retrieve term meta field for a term.
     *
     * @param int $term_id Term ID.
     * @param string $key The meta key to retrieve.
     * @param bool $single Whether to return a single value.
     * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
     *  is true.
     */
    function get_arm_term_meta($term_id, $key, $single = false) {
        return get_metadata('arm_term', $term_id, $key, $single);
    }

}
if (!function_exists('update_arm_term_meta')) {

    /**
     * Update term meta field based on term ID.
     *
     * Use the $prev_value parameter to differentiate between meta fields with the
     * same key and term ID.
     *
     * If the meta field for the term does not exist, it will be added.
     *
     * @param int $term_id Term ID.
     * @param string $key Metadata key.
     * @param mixed $value Metadata value.
     * @param mixed $prev_value Optional. Previous value to check before removing.
     * @return bool False on failure, true if success.
     */
    function update_arm_term_meta($term_id, $meta_key, $meta_value, $prev_value = '') {
        return update_metadata('arm_term', $term_id, $meta_key, $meta_value, $prev_value);
    }

}
if (!function_exists('armXML_to_Array')) {

    /**
     * Convert XML File Data Into Array
     * @param type $content (xml file content)
     */
    function armXML_to_Array($contents, $get_attributes = 1, $priority = 'tag') {
        if (!$contents) {
            return array();
        }
        if (!function_exists('xml_parser_create')) {
            /* print "'xml_parser_create()' function not found!"; */
            return array();
        }
        /* Get the XML parser of PHP - PHP must have this module for the parser to work */
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);
        if (!$xml_values) {
            return;
        }
        /* Initializations */
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array; /* Refference */

        $repeated_tag_index = array(); /* Multiple tags with same name will be turned into an array */
        foreach ($xml_values as $data) {
            unset($attributes, $value); /* Remove existing values, or there will be trouble */
            /**
             * This command will extract these variables into the foreach scope tag(string), type(string), level(int), attributes(array).
             */
            extract($data);
            $result = array();
            $attributes_data = array();
            if (isset($value)) {
                if ($priority == 'tag') {
                    $result = $value;
                } else {
                    $result['value'] = $value; /* Put the value in a assoc array if we are in the 'Attribute' mode */
                }
            }
            /* Set the attributes too. */
            if (isset($attributes) and $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag') {
                        $attributes_data[$attr] = $val;
                    } else {
                        $result['attr'][$attr] = $val; /* Set all the attributes in a array called 'attr' */
                    }
                }
            }
            /* See tag status and do the needed. */
            if ($type == "open") {
                /* The starting of the tag '<tag>' */
                $parent[$level - 1] = &$current;
                if (!is_array($current) or ( !in_array($tag, array_keys($current)))) {
                    $current[$tag] = $result;
                    if ($attributes_data){
                        $current[$tag . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level] = 1;

                    $current = &$current[$tag];
                } else {
                    /* There was another element with the same tag name */
                    if (isset($current[$tag][0])) {
                        /* If there is a 0th element it is already an array */
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else {
                        /* This section will make the value an array if multiple tags with the same name appear together */
                        /* This will combine the existing item and the new item together to make an array */
                        $current[$tag] = array($current[$tag], $result);
                        $repeated_tag_index[$tag . '_' . $level] = 2;

                        if (isset($current[$tag . '_attr'])) {
                            /* The attribute of the last(0th) tag must be moved as well */
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = &$current[$tag][$last_item_index];
                }
            } elseif ($type == "complete") {
                /* Tags that ends in 1 line '<tag />' */
                /* See if the key is already taken. */
                if (!isset($current[$tag])) {
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                } else {
                    /* If taken, put all things inside a list(array) */
                    if (isset($current[$tag][0]) and is_array($current[$tag])) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else {
                        $current[$tag] = array($current[$tag], $result);
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag . '_attr'])) {
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }
                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++; /* 0 and 1 index is already taken */
                    }
                }
            } elseif ($type == 'close') {
                /* End of tag '</tag>' */
                $current = &$parent[$level - 1];
            }
        }
        return $xml_array;
    }

}
if (!function_exists('arm_custom_css_class_info')) {

    function arm_custom_css_class_info() {
        $arm_custom_css_info = apply_filters('arm_available_css_info', array(
            'arm_account_detail' => array(
                'section_title' => array(
                    'title' => esc_html__('My Profile', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on your profile', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_account_detail_wrapper',
                        'note' => esc_html__('It will apply on main container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_account_detail_tab_content_wrapper',
                        'note' => esc_html__('It will apply on tabs detail wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_account_detail_tab_content',
                        'note' => esc_html__('It will apply on specific tab detail content wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_account_detail_tab_heading',
                        'note' => esc_html__('It will apply on specific tab content heading.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_account_detail_tab_body',
                        'note' => esc_html__('It will apply on specific tab content body wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_member_detail_action_links',
                        'note' => esc_html__('It will apply on member action wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_member_detail_action_links a',
                        'note' => esc_html__('It will apply on member action links.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_view_profile_wrapper',
                        'note' => esc_html__('It will apply on profile wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_view_profile_wrapper table',
                        'note' => esc_html__('It will apply on table of profile.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_view_profile_wrapper table tr',
                        'note' => esc_html__('It will apply on row of profile table.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_view_profile_wrapper table th',
                        'note' => esc_html__('It will apply on header of profile table.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_view_profile_wrapper table td',
                        'note' => esc_html__('It will apply on item of profile table.', 'ARMember'),
                    ),
                ),
            ),
            'arm_member_transaction' => array(
                'section_title' => array(
                    'title' => esc_html__('Payment Transaction', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on payment transaction', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_transactions_container',
                        'note' => esc_html__('It will apply on main container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_transactions_heading_main',
                        'note' => esc_html__('It will apply on heading.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_transaction_form_container',
                        'note' => esc_html__('It will apply on form container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_transactions_wrapper',
                        'note' => esc_html__('It will apply on wrapper of transactions list.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_transaction_list_table',
                        'note' => esc_html__('It will apply on table.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_transaction_list_header',
                        'note' => esc_html__('It will apply on header(tr).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_transaction_list_item',
                        'note' => esc_html__('It will apply on item(td).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_transaction_paging_container',
                        'note' => esc_html__('It will apply on pagination wrapper.', 'ARMember'),
                    )
                )
            ),
            'arm_current_membership' => array(
                'section_title' => array(
                    'title' => esc_html__('Current Membership', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on current membership', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_front_edit_subscriptions_link',
                        'note' => esc_html__('It will apply on change membership link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_subscriptions_wrapper',
                        'note' => esc_html__('It will apply on membership detail wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_subscriptions_wrapper table',
                        'note' => esc_html__('It will apply on table of membership detail.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_subscriptions_wrapper table tr',
                        'note' => esc_html__('It will apply on row of membership table.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_subscriptions_wrapper table th',
                        'note' => esc_html__('It will apply on header of membership table.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_subscriptions_wrapper table td',
                        'note' => esc_html__('It will apply on item of membership table.', 'ARMember'),
                    )
                )
            ),
            'arm_close_account' => array(
                'section_title' => array(
                    'title' => esc_html__('Close Account', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on close account', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_close_account_container',
                        'note' => esc_html__('It will apply on main container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_close_account_form_container',
                        'note' => esc_html__('It will apply on form wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_form_close_account',
                        'note' => esc_html__('It will apply on form.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df-wrapper',
                        'note' => esc_html__('It will apply on form inner wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_form_message_container',
                        'note' => esc_html__('It will apply on error / success message wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_close_account_message',
                        'note' => esc_html__('It will apply on message wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fields-wrapper',
                        'note' => esc_html__('It will apply on form fields wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-group',
                        'note' => esc_html__('It will apply on specific field container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__field-label',
                        'note' => esc_html__('It will apply on field label wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__label-text',
                        'note' => esc_html__('It will apply on field label text.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-field',
                        'note' => esc_html__('It will apply on input field wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-control',
                        'note' => esc_html__('It will apply on input field.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_close_account_btn',
                        'note' => esc_html__('It will apply on submit button.', 'ARMember'),
                    )
                ),
            ),
            'arm_cancel_membership' => array(
                'section_title' => array(
                    'title' => esc_html__('Cancel Subscription', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on cancel Subscription', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_cancel_membership_form_container',
                        'note' => esc_html__('It will apply on main container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_cancel_membership_link (arm_cancel_membership_btn)',
                        'note' => esc_html__('It will apply on submit link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_cancel_membership_button (arm_cancel_membership_btn)',
                        'note' => esc_html__('It will apply on submit button.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_form_message_container',
                        'note' => esc_html__('It will apply on error / success message wrapper.', 'ARMember'),
                    )
                ),
            ),
            'arm_form' => array(
                'section_title' => array(
                    'title' => esc_html__('Form Set / Edit Profile', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on other forms and edit profile', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm-form-container',
                        'note' => esc_html__('It will apply on main container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-default-form',
                        'note' => esc_html__('It will apply on form.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_form_edit_profile',
                        'note' => esc_html__('It will apply on edit profile form.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df-wrapper',
                        'note' => esc_html__('It will apply on form inner container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_form_message_container',
                        'note' => esc_html__('It will apply on error / success message wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fields-wrapper',
                        'note' => esc_html__('It will apply on form fields wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fields-wrapper_edit_profile',
                        'note' => esc_html__('It will apply on edit profile form fields wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__heading',
                        'note' => esc_html__('It will apply on form title (not for popup).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-group',
                        'note' => esc_html__('It will apply on specific field container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-group_{type}',
                        'note' => esc_html__('It will apply on specific field type container (type: text, email, radio, checkbox, select, avatar, file, submit, etc...).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_form_label_wrapper',
                        'note' => esc_html__('It will apply on field label wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__label-asterisk',
                        'note' => esc_html__('It will apply on required text (*).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__label-text',
                        'note' => esc_html__('It will apply on field label text.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__label-text',
                        'note' => esc_html__('It will apply on field label text (material).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-field',
                        'note' => esc_html__('It will apply on input field wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-field-wrap',
                        'note' => esc_html__('It will apply on input inner container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-field-wrap_{type}',
                        'note' => esc_html__('It will apply on specific input field type container (type: text, email, radio, checkbox, select, avatar, file, submit, etc...).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-control',
                        'note' => esc_html__('It will apply on input field.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-control-submit-btn',
                        'note' => esc_html__('It will apply on submit button.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fc--validation',
                        'note' => esc_html__('It will apply on field error message wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fc--validation__wrap',
                        'note' => esc_html__('It will apply on field error message text.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_pass_strength_meter',
                        'note' => esc_html__('It will apply on password strength meter wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_strength_meter_block',
                        'note' => esc_html__('It will apply on password strength meter block.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_strength_meter_label',
                        'note' => esc_html__('It will apply on password strength meter block.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_popup_member_form (arm_popup_wrapper)',
                        'note' => esc_html__('It will apply on popup form main wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.popup_wrapper_inner',
                        'note' => esc_html__('It will apply on popup inner wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.popup_header',
                        'note' => esc_html__('It will apply on popup header wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.popup_close_btn',
                        'note' => esc_html__('It will apply on popup close wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.popup_header_text',
                        'note' => esc_html__('It will apply on popup heading(title).', 'ARMember'),
                    ),
                    array(
                        'class' => '.popup_content_text',
                        'note' => esc_html__('It will apply on popup body wrapper.', 'ARMember'),
                    )
                ),
            ),
            'arm_logout' => array(
                'section_title' => array(
                    'title' => esc_html__('Logout', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on logout', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_logout_form_container',
                        'note' => esc_html__('It will apply on main container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_logout_link (arm_logout_btn)',
                        'note' => esc_html__('It will apply on submit link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_logout_button (arm_logout_btn)',
                        'note' => esc_html__('It will apply on submit button.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-logged-in-as',
                        'note' => esc_html__('It will apply on logged in as text wrapper.', 'ARMember'),
                    ),
                ),
            ),
            'arm_membership_setup' => array(
                'section_title' => array(
                    'title' => esc_html__('Membership Setup Wizard', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on membership setup wizard', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_setup_form_container',
                        'note' => esc_html__('It will apply on main container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_form_title',
                        'note' => esc_html__('It will apply on heading.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_messages.arm_form_message_container',
                        'note' => esc_html__('It will apply on message container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_membership_setup_form',
                        'note' => esc_html__('It will apply on membership setup wizard form.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_form_inner_container',
                        'note' => esc_html__('It will apply on membership setup wizard form inner wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_submit_btn_wrapper',
                        'note' => esc_html__('It will apply on submit button wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_submit_btn',
                        'note' => esc_html__('It will apply on submit button.', 'ARMember'),
                    ),
                ),
            ),
            'arm_membership_setup_plans' => array(
                'section_title' => array(
                    'title' => esc_html__('Plans Section', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on membership setup wizard plan section', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_module_plans_container',
                        'note' => esc_html__('It will apply on container of membership setup wizard plans.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_module_plans_ul',
                        'note' => esc_html__('It will apply on plans list.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_column_item',
                        'note' => esc_html__('It will apply on plans list item.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_column_item.arm_active',
                        'note' => esc_html__('It will apply on selected plan.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_module_plan_option',
                        'note' => esc_html__('It will apply on label of plans list.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_module_plan_name',
                        'note' => esc_html__('It will apply on name of plan.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_module_plan_description',
                        'note' => esc_html__('It will apply on description of plan.', 'ARMember'),
                    ),
                ),
            ),
            'arm_membership_setup_form' => array(
                'section_title' => array(
                    'title' => esc_html__('Form Section', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on membership setup wizard form section', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm-default-form',
                        'note' => esc_html__('It will apply on setup form.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df-wrapper',
                        'note' => esc_html__('It will apply on setup form inner wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_form_message_container',
                        'note' => esc_html__('It will apply on error / success message wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fields-wrapper',
                        'note' => esc_html__('It will apply on setup form fields wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__heading',
                        'note' => esc_html__('It will apply on form title (not for popup).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-group',
                        'note' => esc_html__('It will apply on specific field container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-group_{type}',
                        'note' => esc_html__('It will apply on specific field type container (type: text, email, radio, checkbox, select, avatar, file, submit, etc...).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_form_label_wrapper',
                        'note' => esc_html__('It will apply on field label wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__label-asterisk',
                        'note' => esc_html__('It will apply on required text (*).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__label-text',
                        'note' => esc_html__('It will apply on field label text.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__label-text',
                        'note' => esc_html__('It will apply on field label text (material).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-field',
                        'note' => esc_html__('It will apply on input field wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-field-wrap',
                        'note' => esc_html__('It will apply on input inner container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-field-wrap_{type}',
                        'note' => esc_html__('It will apply on specific input field type container (type: text, email, radio, checkbox, select, avatar, file, submit, etc...).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-control',
                        'note' => esc_html__('It will apply on input field.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-control-submit-btn',
                        'note' => esc_html__('It will apply on submit button.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fc--validation',
                        'note' => esc_html__('It will apply on field error message wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fc--validation__wrap',
                        'note' => esc_html__('It will apply on field error message text.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_pass_strength_meter',
                        'note' => esc_html__('It will apply on password strength meter wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_strength_meter_block',
                        'note' => esc_html__('It will apply on password strength meter block.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_strength_meter_label',
                        'note' => esc_html__('It will apply on password strength meter label.', 'ARMember'),
                    ),
                ),
            ),
            'arm_membership_setup_gateways' => array(
                'section_title' => array(
                    'title' => esc_html__('Payment Gateways Section', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on membership setup wizard payment gateways section', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_module_gateways_container',
                        'note' => esc_html__('It will apply on container of payment gateways.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_module_gateways_ul',
                        'note' => esc_html__('It will apply on payment gateways list.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_gateway_paypal',
                        'note' => esc_html__('It will apply on paypal.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_gateway_stripe',
                        'note' => esc_html__('It will apply on stripe.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_gateway_authorize_net',
                        'note' => esc_html__('It will apply on authorize net.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_gateway_2checkout',
                        'note' => esc_html__('It will apply on 2checkout.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_gateway_bank_transfer',
                        'note' => esc_html__('It will apply on bank transfer.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_column_item',
                        'note' => esc_html__('It will apply on payment gateways list item.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_column_item.arm_active',
                        'note' => esc_html__('It will apply on selected payment gateway.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_module_gateway_option',
                        'note' => esc_html__('It will apply on label of payment gateways list.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_check_circle',
                        'note' => esc_html__('It will apply on checked circle of selected payment gateway.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_check_circle i',
                        'note' => esc_html__('It will apply on checked circle icon of selected payment gateway.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_module_gateway_name',
                        'note' => esc_html__('It will apply on name of payment gateway.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_module_gateway_fields',
                        'note' => esc_html__('It will apply on payment gateway fields wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_cc_fields_container',
                        'note' => esc_html__('It will apply on credit card fields container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_cc_field_wrapper',
                        'note' => esc_html__('It will apply on credit card fields wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.payment-errors',
                        'note' => esc_html__('It will apply on payment errors.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_bank_transfer_fields_container',
                        'note' => esc_html__('It will apply on bank transfer fields container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_bt_field_wrapper',
                        'note' => esc_html__('It will apply on bank transfer fields wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_form_label_wrapper',
                        'note' => esc_html__('It will apply on field label wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__label-text',
                        'note' => esc_html__('It will apply on field label text.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-field',
                        'note' => esc_html__('It will apply on input field wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__form-field-wrap',
                        'note' => esc_html__('It will apply on input inner container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__label-text',
                        'note' => esc_html__('It will apply on field label text (material).', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fc--validation',
                        'note' => esc_html__('It will apply on field error message wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-df__fc--validation__wrap',
                        'note' => esc_html__('It will apply on field error message text.', 'ARMember'),
                    ),
                ),
            ),
            'arm_membership_setup_coupon' => array(
                'section_title' => array(
                    'title' => esc_html__('Coupon & Amount Section', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on membership setup wizard Coupon section', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_module_coupons_container',
                        'note' => esc_html__('It will apply on main container of coupon section.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_apply_coupon_container',
                        'note' => esc_html__('It will apply on inner container of coupon section.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_apply_coupon_container label',
                        'note' => esc_html__('It will apply on label of coupon section.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_coupon_code',
                        'note' => esc_html__('It will apply on input field.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_apply_coupon_btn',
                        'note' => esc_html__('It will apply on coupon apply button.', 'ARMember'),
                    ),
                )
            ),
            'arm_membership_setup_summary' => array(
                'section_title' => array(
                    'title' => esc_html__('Summary Section', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on membership setup wizard Summary section', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_setup_summary_text_container',
                        'note' => esc_html__('It will apply on main container of summary section.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_summary_text',
                        'note' => esc_html__('It will apply on inner container of summary section.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_setup_summary_text div',
                        'note' => esc_html__('It will apply on text of summary section.', 'ARMember'),
                    ),
                )
            ),
            'arm_directory' => array(
                'section_title' => array(
                    'title' => esc_html__('Member Directory', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on member directory', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_template_wrapper',
                        'note' => esc_html__('It will apply on template main wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_template_wrapper_directorytemplate1',
                        'note' => esc_html__('It will apply on main wrapper of directory template1.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_template_wrapper_directorytemplate2',
                        'note' => esc_html__('It will apply on main wrapper of directory template2.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_template_wrapper_directorytemplate3',
                        'note' => esc_html__('It will apply on main wrapper of directory template3.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_form_container',
                        'note' => esc_html__('It will apply on directory form container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_filters_wrapper',
                        'note' => esc_html__('It will apply on filters wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_search_wrapper',
                        'note' => esc_html__('It will apply on search wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_search_box',
                        'note' => esc_html__('It will apply on search input field.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_search_btn',
                        'note' => esc_html__('It will apply on search submit button.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_list_of_filters',
                        'note' => esc_html__('It will apply on list of wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_search_filter_title_label',
                        'note' => esc_html__('It will apply on Filters title.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_search_filter_fields_wrapper_top .arm_search_filter_field_item_top',
                        'note' => esc_html__('It will apply on Filters Field width.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_search_filter_container_type_0',
                        'note' => esc_html__('It will apply on Single Search type filter.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_search_filter_container_type_1',
                        'note' => esc_html__('It will apply on Multiple Search type filter.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_button_search_filter_btn_div_top',
                        'note' => esc_html__('It will apply on set position of Search and Reset button.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_list_of_filters label',
                        'note' => esc_html__('It will apply on list of label.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_list_of_filters .arm_active',
                        'note' => esc_html__('It will apply on active list button.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_list_by_filters',
                        'note' => esc_html__('It will apply on list by wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_listby_select',
                        'note' => esc_html__('It will apply on select box of list by user.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_listby_select option',
                        'note' => esc_html__('It will apply on options of list by user.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_template_container',
                        'note' => esc_html__('It will apply on template container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_container',
                        'note' => esc_html__('It will apply on directory template container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_block',
                        'note' => esc_html__('It will apply on user block of lists.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_block_left',
                        'note' => esc_html__('It will apply on left block.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_block_right',
                        'note' => esc_html__('It will apply on left block.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_dp_user_link',
                        'note' => esc_html__('It will apply on user avatar link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_avatar',
                        'note' => esc_html__('It will apply on user avatar section.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_avatar img',
                        'note' => esc_html__('It will apply on user avatar image.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_link',
                        'note' => esc_html__('It will apply on username.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_badges_detail',
                        'note' => esc_html__('It will apply on user badges section.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-user-badge',
                        'note' => esc_html__('It will apply on user badges items lists.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-user-badge img',
                        'note' => esc_html__('It will apply on user badges image.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_paging_container',
                        'note' => esc_html__('It will apply on paging container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_directory_load_more_btn',
                        'note' => esc_html__('It will apply on load more link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_paging_wrapper_directory',
                        'note' => esc_html__('It will apply on paging inner wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_paging_info',
                        'note' => esc_html__('It will apply on paging info.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_paging_links',
                        'note' => esc_html__('It will apply on paging links.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_prev',
                        'note' => esc_html__('It will apply on paging previous link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.current',
                        'note' => esc_html__('It will apply on paging current link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_page_numbers',
                        'note' => esc_html__('It will apply on paging numbers link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_page_numbers.dots',
                        'note' => esc_html__('It will apply on paging dots link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_next',
                        'note' => esc_html__('It will apply on paging next link.', 'ARMember'),
                    ),
                )
            ),
            'arm_profile' => array(
                'section_title' => array(
                    'title' => esc_html__('Public Profile', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on public profile', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_template_wrapper',
                        'note' => esc_html__('It will apply on template main wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_template_wrapper_profiletemplate1',
                        'note' => esc_html__('It will apply on main wrapper of profile template1.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_template_wrapper_profiletemplate2',
                        'note' => esc_html__('It will apply on main wrapper of profile template2.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_template_wrapper_profiletemplate3',
                        'note' => esc_html__('It will apply on main wrapper of profile template3.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_template_container',
                        'note' => esc_html__('It will apply on template container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_container',
                        'note' => esc_html__('It will apply on profile template container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_detail_wrapper',
                        'note' => esc_html__('It will apply on profile detail container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_picture_block',
                        'note' => esc_html__('It will apply on picture block container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_picture_block_inner',
                        'note' => esc_html__('It will apply on picture block inner container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_header_top_box',
                        'note' => esc_html__('It will apply on header top block.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_avatar',
                        'note' => esc_html__('It will apply on user avatar section.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_avatar img',
                        'note' => esc_html__('It will apply on user avatar image.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_header_info',
                        'note' => esc_html__('It will apply on header info block.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_name_link',
                        'note' => esc_html__('It will apply on username.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_name_link a',
                        'note' => esc_html__('It will apply on username link.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_badges_detail',
                        'note' => esc_html__('It will apply on user badges section.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-user-badge',
                        'note' => esc_html__('It will apply on user badges items lists.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm-user-badge img',
                        'note' => esc_html__('It will apply on user badges image.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_last_active_text',
                        'note' => esc_html__('It will apply on last login detail text.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_item_status_text',
                        'note' => esc_html__('It will apply on last login status text.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_user_about_me',
                        'note' => esc_html__('It will apply on user info.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_cover_upload_container',
                        'note' => esc_html__('It will apply on cover upload container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.armCoverUploadBtnContainer',
                        'note' => esc_html__('It will apply on cover upload inner container.', 'ARMember'),
                    ),
                    array(
                        'class' => '.armCoverUploadBtn',
                        'note' => esc_html__('It will apply on cover upload button.', 'ARMember'),
                    ),
                    array(
                        'class' => '.armRemoveCover',
                        'note' => esc_html__('It will apply on cover remove button.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_defail_container',
                        'note' => esc_html__('It will apply on main container of profile defail.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_tab_detail',
                        'note' => esc_html__('It will apply on specific tab content wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_general_info_container',
                        'note' => esc_html__('It will apply on profile wrapper.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_detail_tbl',
                        'note' => esc_html__('It will apply on table of profile.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_detail_tbl tr',
                        'note' => esc_html__('It will apply on row of profile table.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_profile_detail_tbl td',
                        'note' => esc_html__('It will apply on item of profile table.', 'ARMember'),
                    ),
                )
            ),
            'arm_membership_card' => array(
                'section_title' => array(
                    'title' => esc_html__('Membership Card', 'ARMember'),
                    'note' => esc_html__('Please use following css class if you want to add custom property on membership card', 'ARMember'),
                ),
                'section_class' => array(
                    array(
                        'class' => '.arm_card_background',
                        'note' => esc_html__('It will apply on card background.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_card_title',
                        'note' => esc_html__('It will apply on card title.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_card_left_logo',
                        'note' => esc_html__('It will apply on card logo.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_card_label',
                        'note' => esc_html__('It will apply on card label.', 'ARMember'),
                    ),
                    array(
                        'class' => '.arm_card_value',
                        'note' => esc_html__('It will apply on card value.', 'ARMember'),
                    ),
                )
            ),
        ));
        return $arm_custom_css_info;
    }
}

if (!function_exists('arm_array_map')) {

    function arm_array_map($input = array()) {
        if (empty($input)) {
            return $input;
        }

        return is_array($input) ? array_map('arm_array_map', $input) : trim($input);
    }

}

if (!function_exists('arm_wp_date_format_to_bootstrap_datepicker')) {

    function arm_wp_date_format_to_bootstrap_datepicker($date_format = '') {
        if ($date_format == '') {
            $date_format = get_option('date_format');
        }

        $SYMBOLS_MATCHING = array(
            'd' => 'DD',
            'D' => 'ddd',
            'j' => 'D',
            'l' => 'dddd',
            'N' => '',
            'S' => '',
            'w' => '',
            'z' => 'o',
            'W' => '',
            'F' => 'MMMM',
            'm' => 'MM',
            'M' => 'M',
            'n' => 'm',
            't' => '',
            'L' => '',
            'o' => '',
            'Y' => 'YYYY',
            'y' => 'y',
            'a' => '',
            'A' => '',
            'B' => '',
            'g' => '',
            'G' => '',
            'h' => '',
            'H' => '',
            'i' => '',
            's' => '',
            'u' => ''
        );
        $jqueryui_format = "";
        $escaping = false;
        for ($i = 0; $i < strlen($date_format); $i++) {
            $char = $date_format[$i];
            if ($char === '\\') { // PHP date format escaping character
                $i++;
                if ($escaping)
                    $jqueryui_format .= $date_format[$i];
                else
                    $jqueryui_format .= '\'' . $date_format[$i];
                $escaping = true;
            }
            else {
                if ($escaping) {
                    $jqueryui_format .= "'";
                    $escaping = false;
                }
                if (isset($SYMBOLS_MATCHING[$char]))
                    $jqueryui_format .= $SYMBOLS_MATCHING[$char];
                else
                    $jqueryui_format .= $char;
            }
        }

        return $jqueryui_format;
    }

}

if (!function_exists('arm_strtounicode')) {

    function arm_strtounicode($str = '') {
        if ($str == '') {
            return $str;
        }

        return preg_replace_callback("([\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3}|[\xF8-\xFB][\x80-\xBF]{4}|[\xFC-\xFD][\x80-\xBF]{5})", function($m) {
            $c = $m[0];
            $out = bindec(ltrim(decbin(ord($c[0])), "1"));
            $l = strlen($c);
            for ($i = 1; $i < $l; $i++) {
                $out = ($out << 6) | bindec(ltrim(decbin(ord($c[$i])), "1"));
            }
            if ($out < 256)
                return chr($out);
            return "&#" . $out . ";";
        }, $str);
    }

}
if( !function_exists('arm_check_date_format') ){

    function arm_check_date_format($date_value,$key = 0){
        $date_formats = array(
            'd/m/Y',
            'm/d/Y',
            'Y/m/d',
            'M d, Y',
            'F d, Y',
            'd M, Y',
            'd F, Y',
            'Y, M d',
            'Y, F d'
        );
        $final_date_format = false;
        foreach($date_formats as $k => $format){
            if( DateTime::createFromFormat($format,$date_value) ){
                $final_date_format = DateTime::createFromFormat($format,$date_value);
                break;
        }

        }
        if( $final_date_format == "" || empty($final_date_format)){
            try{
                $final_date_format = new DateTime($date_value);
            } catch(Exception $e){
                $date_value = str_replace('/', '-', $date_value);
                $final_date_format = new DateTime($date_value);
            }
        }
        return $final_date_format;
    }
}