<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_wizard') ) {
    class affiliatepress_wizard Extends AffiliatePress_Core{
        
        function __construct(){
            
            /** Load wizard view file */
            add_filter('affiliatepress_lite_wizard_dynamic_view_load', array( $this, 'affiliatepress_load_wizard_view_func'), 10);

            /** Function for wizard vue method */
            add_filter('affiliatepress_lite_wizard_dynamic_vue_methods', array( $this, 'affiliatepress_wizard_vue_methods_func'),10,1);

            /** Wizard onload methods */
			add_filter('affiliatepress_lite_wizard_dynamic_on_load_methods', array( $this, 'affiliatepress_wizard_on_load_methods_func'));

            /** Function for dynamic variable  */
			add_filter('affiliatepress_lite_wizard_dynamic_data_fields', array( $this, 'affiliatepress_wizard_dynamic_data_fields_func'),10,1);

            /** Load wizard view file */
            add_action('wp_ajax_affiliatepress_lite_skip_wizard', array($this, 'affiliatepress_skip_wizard_func'));

            /**  Function for save wizard settings */
            add_action('wp_ajax_affiliatepress_save_lite_wizard_settings', array($this, 'affiliatepress_save_lite_wizard_settings_func'));

        }

        /**
         * Load wizard view file
         *
         * @return HTML
        */
        function affiliatepress_load_wizard_view_func(){
            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/wizard/manage_wizard.php';
			require $affiliatepress_load_file_name;
        }
        
        /**
         * Function for wizard vue method
         *
         * @param  string $affiliatepress_wizard_vue_methods
         * @return string
        */
        function affiliatepress_wizard_vue_methods_func($affiliatepress_wizard_vue_methods){

            $affiliatepress_wizard_vue_methods.= '
                isNumberValidate(evt) {
                    const vm = this;
                    const regex = /^(\d{1,3}(,\d{3})*|\d+)?(\.\d*)?$/;
                    if (regex.test(evt)) {
                        vm.inputValue = evt; 
                    } else {
                        vm.wizard_steps_data.default_commission_rate = vm.inputValue;
                    }                
                },
                affiliatepress_next_tab(current_tab){
                    const vm = this;
                    if(current_tab == "basic_settings"){
                        vm.affiliatepress_active_tab = "commission_setting";
				    }
                    else if(current_tab == "commission_setting"){
					    vm.affiliatepress_active_tab = "email_notification";
				    }
                    else if(current_tab == "email_notification")
                    {
                        vm.affiliatepress_active_tab = "style_settings";
                    }
                    else if(current_tab == "style_settings"){
					    var postData = [];
                        postData.action = "affiliatepress_save_lite_wizard_settings"
                        postData.wizard_data = vm.wizard_steps_data
                        postData._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'"
                        axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                        .then( function (response) {
                            if(response.data.variant != "error"){
                                vm.affiliatepress_active_tab = "finish_tab";
                            }else{
                                console.log(response.data.msg);
                            }
                            }.bind(this) 
                        )
				    }
                    else if(current_tab == "finish_tab")
                    {
                        window.location.href = "'.esc_html(admin_url() . "admin.php?page=affiliatepress").'";
                    }
                        
                },
                affiliatepress_close_tab(){
                    const vm = this;
                    window.location.href = "'.esc_html(admin_url() . "admin.php?page=affiliatepress").'";
                },
                affiliatepress_previous_tab(current_tab){
                    const vm = this;
                    if(current_tab == "commission_setting"){
                        vm.affiliatepress_active_tab = "basic_settings";
                    }else if(current_tab == "email_notification"){
                        vm.affiliatepress_active_tab = "commission_setting";
                    }else if(current_tab == "style_settings"){
                        vm.affiliatepress_active_tab = "email_notification";
                    }
			    },
                affiliatepress_skip_wizard()
                {
                    var postData = [];
                    postData.action = "affiliatepress_lite_skip_wizard"
                    postData._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'"
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
					if(response.data.variant != "error"){
						window.location.href = "'.esc_html(admin_url() . "admin.php?page=affiliatepress").'";
					}else{
						console.log(response.data.msg);
					}
				    }.bind(this) )
                },
            
            ';

            return $affiliatepress_wizard_vue_methods;
            ?>

            <?php
        }
        
        /**
         * Function for dynamic variable 
         *
         * @param  array $affiliatepress_lite_wizard_vue_data_fields
         * @return json
        */
        function affiliatepress_wizard_dynamic_data_fields_func($affiliatepress_lite_wizard_vue_data_fields){
            global $affiliatepress_lite_wizard_vue_data_fields, $affiliatepress_global_options;

            $affiliatepress_options                    = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_countries_currency_details = json_decode($affiliatepress_options['countries_json_details']);

            $affiliatepress_lite_wizard_vue_data_fields = array();

            $affiliatepress_lite_wizard_vue_data_fields['affiliatepress_active_tab'] = 'basic_settings';

            $affiliatepress_lite_wizard_vue_data_fields['affiliatepress_disabled_tabs'] = true;

            $affiliatepress_lite_wizard_vue_data_fields['currency_countries'] = $affiliatepress_countries_currency_details;

            $affiliatepress_all_plugin_integration = $affiliatepress_global_options->affiliatepress_all_plugin_integration();

            $affiliatepress_lite_wizard_vue_data_fields['all_integration_list'] = $affiliatepress_all_plugin_integration;

            $affiliatepress_inherit_fonts_list = array('Inherit Fonts',);
            $affiliatepress_default_fonts_list = $affiliatepress_global_options->affiliatepress_get_default_fonts();
            $affiliatepress_google_fonts_list  = $affiliatepress_global_options->affiliatepress_get_google_fonts();
            $affiliatepress_fonts_list         = array(
                array(
                    'label'   => esc_html__('Inherit Fonts', 'affiliatepress-affiliate-marketing'),
                    'options' => $affiliatepress_inherit_fonts_list,
                ),
                array(
                    'label'   => esc_html__('Default Fonts', 'affiliatepress-affiliate-marketing'),
                    'options' => $affiliatepress_default_fonts_list,
                ),
                array(
                    'label'   => esc_html__('Google Fonts', 'affiliatepress-affiliate-marketing'),
                    'options' => $affiliatepress_google_fonts_list,
                ),
            );

            $affiliatepress_lite_wizard_vue_data_fields['fonts_list'] = $affiliatepress_fonts_list;

            $affiliatepress_lite_wizard_vue_data_fields['wizard_steps_data'] = array(
                'company_name'                  =>  get_option('blogname'),
                'sender_email'                  =>  get_option('admin_email'),
                'admin_email'                   =>  get_option('admin_email'),
                'allow_affiliate_registration'  => true,
                'affiliate_default_status'      => true,
                'affiliate_usage_stats'         => true,
                'default_commission_rate'       => 10,
                'default_discount_type'         => 'percentage',
                'flat_rate_commission_basis'    => 'pre_product',
                'payment_default_currency'      => 'USD',
                'minimum_payment_amount'        => 10,
                'integrations'                  => $affiliatepress_global_options->affiliatepress_priority_wise_integration_get(),
                'refund_grace_period'           => 0,
                'affiliate_notification' =>array(
                    'admin_account_pending'                 => true ,
                    'admin_commission_registerd'            => true ,
                    'affiliate_account_pending'             => true,
                    'affiliate_commission_approved'         => true,
                    'affiliate_payment_paid'                => true
                ),
                'primary_color'         => '#6858e0',
                'background_color'      => '#ffffff',
                'panel_background_color'=> '#ffffff',
                'text_color'            => '#1A1E26',
                'content_color'         => '#576582',
                'font'                  => 'Poppins',
                'border_color'          => '#C9CFDB',
            );

            return wp_json_encode($affiliatepress_lite_wizard_vue_data_fields);
        }
        
                
        /**
         * Wizard onload methods
         *
         * @param  string $affiliatepress_wizard_on_load_methods
         * @return string
         */
        function affiliatepress_wizard_on_load_methods_func($affiliatepress_wizard_on_load_methods){

            $affiliatepress_wizard_on_load_methods.='
                document.body.classList.add("ap-fullscreen-wizard-setup-container");
            ';
            return $affiliatepress_wizard_on_load_methods;
        }
        
        /**
         * Function for save wizard settings
         *
         * @return json
        */
        function affiliatepress_save_lite_wizard_settings_func(){

            global $AffiliatePress , $affiliatepress_tbl_ap_notifications,$affiliatepress_global_options;

            $response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_ap_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_ap_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }

            if(!current_user_can('affiliatepress')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }

            $affiliatepress_wizard_data = !empty($_POST['wizard_data']) ?  $_POST['wizard_data'] : array(); // phpcs:ignore 

            // $affiliatepress_wizard_data = apply_filters('affiliatepress_save_extra_settings_data', $affiliatepress_wizard_data);

            if(!empty($affiliatepress_wizard_data)){

                /** basic_settins_tab */
                $affiliatepress_company_name = !empty($affiliatepress_wizard_data['company_name']) ? $affiliatepress_wizard_data['company_name'] : get_option('blogname');
                $AffiliatePress->affiliatepress_update_settings('company_name', 'email_notification_settings' , $affiliatepress_company_name);
                $AffiliatePress->affiliatepress_update_settings('sender_name', 'email_notification_settings' , $affiliatepress_company_name);

                $affiliatepress_sender_email = !empty($affiliatepress_wizard_data['sender_email']) ? $affiliatepress_wizard_data['sender_email'] : get_option('admin_email');
                $AffiliatePress->affiliatepress_update_settings('sender_email', 'email_notification_settings' , $affiliatepress_sender_email);

                $affiliatepress_admin_email = !empty($affiliatepress_wizard_data['admin_email']) ? $affiliatepress_wizard_data['admin_email'] : get_option('admin_email');
                $AffiliatePress->affiliatepress_update_settings('admin_email', 'email_notification_settings' , $affiliatepress_admin_email);

                $affiliatepress_allow_affiliate_registration = !empty($affiliatepress_wizard_data['allow_affiliate_registration']) ? $affiliatepress_wizard_data['allow_affiliate_registration'] : true;
                $AffiliatePress->affiliatepress_update_settings('allow_affiliate_registration', 'affiliate_settings' , $affiliatepress_allow_affiliate_registration);

                $affiliatepress_affiliate_default_status = !empty($affiliatepress_wizard_data['affiliate_default_status']) ? $affiliatepress_wizard_data['affiliate_default_status'] : true;
                $AffiliatePress->affiliatepress_update_settings('affiliate_default_status', 'affiliate_settings' , $affiliatepress_affiliate_default_status);

                $affiliatepress_affiliate_usage_stats = !empty($affiliatepress_wizard_data['affiliate_usage_stats']) ? $affiliatepress_wizard_data['affiliate_usage_stats'] : true;
                $AffiliatePress->affiliatepress_update_settings('affiliate_usage_stats', 'affiliate_settings' , $affiliatepress_affiliate_usage_stats);

                /** commission_settings_tab */
                $affiliatepress_default_commission_rate = !empty($affiliatepress_wizard_data['default_commission_rate']) ? $affiliatepress_wizard_data['default_commission_rate'] : 10;
                $AffiliatePress->affiliatepress_update_settings('default_discount_val', 'commissions_settings' , $affiliatepress_default_commission_rate);

                $affiliatepress_default_discount_type = !empty($affiliatepress_wizard_data['default_discount_type']) ? $affiliatepress_wizard_data['default_discount_type'] : 'percentage';
                $AffiliatePress->affiliatepress_update_settings('default_discount_type', 'commissions_settings' , $affiliatepress_default_discount_type);

                $affiliatepress_flat_rate_commission_basis = !empty($affiliatepress_wizard_data['flat_rate_commission_basis']) ? $affiliatepress_wizard_data['flat_rate_commission_basis'] : 'pre_product';
                $AffiliatePress->affiliatepress_update_settings('flat_rate_commission_basis', 'commissions_settings' , $affiliatepress_flat_rate_commission_basis);

                $affiliatepress_payment_default_currency = !empty($affiliatepress_wizard_data['payment_default_currency']) ? $affiliatepress_wizard_data['payment_default_currency'] : 'USD' ;
                $AffiliatePress->affiliatepress_update_settings('payment_default_currency', 'affiliate_settings' , $affiliatepress_payment_default_currency);

                $affiliatepress_minimum_payment_amount = !empty($affiliatepress_wizard_data['minimum_payment_amount']) ? $affiliatepress_wizard_data['minimum_payment_amount'] : 10;
                $AffiliatePress->affiliatepress_update_settings('minimum_payment_amount', 'commissions_settings' , $affiliatepress_minimum_payment_amount);
                
                $affiliatepress_selected_integrations = !empty( $affiliatepress_wizard_data['integrations']) ? $affiliatepress_wizard_data['integrations'] : '';

                if(!empty($affiliatepress_selected_integrations))
                {
                    $affiliatepress_all_plugin_integration = $affiliatepress_global_options->affiliatepress_all_plugin_integration();

                    $affiliatepress_enable_integartion = '';
                    foreach ($affiliatepress_all_plugin_integration as $affiliatepress_integration) {
                        if($affiliatepress_selected_integrations == $affiliatepress_integration['plugin_value']){
                            $affiliatepress_enable_integartion = isset($affiliatepress_integration['plugin_integration_setting_name']) ? sanitize_text_field($affiliatepress_integration['plugin_integration_setting_name']) : '';
                            break;
                        }
                    }

                    $AffiliatePress->affiliatepress_update_settings($affiliatepress_enable_integartion, 'integrations_settings' , 'true');
                }

                $refund_grace_period = !empty($affiliatepress_wizard_data['refund_grace_period']) ? $affiliatepress_wizard_data['refund_grace_period'] : 0;
                $AffiliatePress->affiliatepress_update_settings('refund_grace_period', 'commissions_settings' , $refund_grace_period);

                /** notification_Settings_tab */

                $affiliatepress_notification = !empty($affiliatepress_wizard_data['affiliate_notification']) ? $affiliatepress_wizard_data['affiliate_notification'] : array();
                
                if(!empty($affiliatepress_notification))
                {
                    
                    $affiliatepress_admin_account_pending = $affiliatepress_notification['admin_account_pending'] == 'false' ? 0 : 1;
                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_notifications, array('ap_notification_status' => $affiliatepress_admin_account_pending), array( 'ap_notification_receiver_type' => 'admin', 'ap_notification_slug' => 'affiliate_account_pending'));

                    $affiliatepress_admin_commission_registerd = $affiliatepress_notification['admin_commission_registerd'] == 'false' ? 0 : 1;
                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_notifications, array('ap_notification_status' => $affiliatepress_admin_commission_registerd), array( 'ap_notification_receiver_type' => 'admin', 'ap_notification_slug' => 'commission_registered'));

                    $affiliatepress_affiliate_account_pending = $affiliatepress_notification['affiliate_account_pending'] == 'false' ? 0 : 1;
                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_notifications, array('ap_notification_status' => $affiliatepress_affiliate_account_pending), array( 'ap_notification_receiver_type' => 'affiliate', 'ap_notification_slug' => 'affiliate_account_pending'));

                    $affiliatepress_affiliate_commission_approved = $affiliatepress_notification['affiliate_commission_approved'] == 'false' ? 0 : 1;
                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_notifications, array('ap_notification_status' => $affiliatepress_affiliate_commission_approved), array( 'ap_notification_receiver_type' => 'affiliate', 'ap_notification_slug' => 'commission_approved'));

                    $affiliatepress_affiliate_payment_paid = $affiliatepress_notification['affiliate_payment_paid'] == 'false' ? 0 : 1;
                    $this->affiliatepress_update_record($affiliatepress_tbl_ap_notifications, array('ap_notification_status' => $affiliatepress_affiliate_payment_paid), array( 'ap_notification_receiver_type' => 'affiliate', 'ap_notification_slug' => 'affiliate_payment_paid'));
                }

                $affiliatepress_primary_color = !empty($affiliatepress_wizard_data['primary_color']) ? $affiliatepress_wizard_data['primary_color'] : '#6858e0' ;
                $AffiliatePress->affiliatepress_update_settings('primary_color', 'appearance_settings' , $affiliatepress_primary_color);

                $affiliatepress_background_color = !empty($affiliatepress_wizard_data['background_color']) ? $affiliatepress_wizard_data['background_color'] : '#ffffff' ;
                $AffiliatePress->affiliatepress_update_settings('background_color', 'appearance_settings' , $affiliatepress_background_color);

                $affiliatepress_text_color = !empty($affiliatepress_wizard_data['text_color']) ? $affiliatepress_wizard_data['text_color'] : '#1A1E26' ;
                $AffiliatePress->affiliatepress_update_settings('text_color', 'appearance_settings' , $affiliatepress_text_color);

                $affiliatepress_content_color = !empty($affiliatepress_wizard_data['content_color']) ? $affiliatepress_wizard_data['content_color'] : '#576582' ;
                $AffiliatePress->affiliatepress_update_settings('content_color', 'appearance_settings' , $affiliatepress_content_color);

                $affiliatepress_panel_background_color = !empty($affiliatepress_wizard_data['panel_background_color']) ? $affiliatepress_wizard_data['panel_background_color'] : '#ffffff' ;
                $AffiliatePress->affiliatepress_update_settings('panel_background_color', 'appearance_settings' , $affiliatepress_panel_background_color);

                $affiliatepress_border_color = !empty($affiliatepress_wizard_data['border_color']) ? $affiliatepress_wizard_data['border_color'] : '#C9CFDB' ;
                $AffiliatePress->affiliatepress_update_settings('border_color', 'appearance_settings' , $affiliatepress_border_color);

                $affiliatepress_font = !empty($affiliatepress_wizard_data['font']) ? $affiliatepress_wizard_data['font'] : 'Poppins' ;
                $AffiliatePress->affiliatepress_update_settings('font', 'appearance_settings' , $affiliatepress_font);

                do_action('affiliatepress_after_save_wizard',$affiliatepress_wizard_data);

                update_option('affiliatepress_lite_wizard_complete', 1);
                update_option('affiliatepress_flush_rewrites',1);

                $response['variant']        = 'success';
                $response['title']          = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Wizard Saved successfully', 'affiliatepress-affiliate-marketing');

            }

            $AffiliatePress->affiliatepress_update_all_auto_load_settings();

            echo wp_json_encode($response);
			exit;

        }

        /**
         * Skip Wizard module at backend
         *
         * @return json
         */
        function affiliatepress_skip_wizard_func(){
			global $wpdb,$AffiliatePress;
			$response              = array();
            $affiliatepress_wpnonce               = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';// phpcs:ignore
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($affiliatepress_wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_verify_nonce_flag ) {
                $response['variant']        = 'error';
                $response['title']          = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']            = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                echo wp_json_encode($response);
                exit;
            }
			
            if(!current_user_can('affiliatepress')){
                $affiliatepress_error_msg = esc_html__( 'Sorry, you do not have permission to perform this action.', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg; 
                wp_send_json( $response );
                die;                
            }

            $AffiliatePress->affiliatepress_update_all_auto_load_settings();
			update_option('affiliatepress_lite_wizard_complete', 1);

			$response['variant']        = 'success';
			$response['title']          = esc_html__('Success', 'affiliatepress-affiliate-marketing');
			$response['msg']            = esc_html__('Wizard skipped successfully', 'affiliatepress-affiliate-marketing');

			echo wp_json_encode($response);
			exit;
		}
        
    }
}
global $affiliatepress_wizard;
$affiliatepress_wizard = new affiliatepress_wizard();