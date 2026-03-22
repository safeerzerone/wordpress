<?php 
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('AffiliatePress') ) {
    class AffiliatePress Extends AffiliatePress_Core{

        var $affiliatepress_slugs;

        function __construct(){

			global $wp, $wpdb, $affiliatepress_capabilities_global, $affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_settings, $affiliatepress_tbl_ap_affiliate_visits, $affiliatepress_common_time_format, $affiliatepress_common_date_format,$affiliatepress_notification_duration,$affiliatepress_tbl_ap_creative,$affiliatepress_tbl_ap_notifications,$affiliatepress_tbl_ap_other_debug_logs, $affiliatepress_setting_logs_data,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_customer,$affiliatepress_tbl_ap_commission_products, $affiliatepress_tbl_ap_commission_debug_logs,$affiliatepress_tbl_ap_affiliate_form_fields,$affiliatepress_tbl_ap_affiliate_links,$affiliatepress_tbl_ap_payout_debug_logs,$affiliatepress_tbl_ap_payouts,$affiliatepress_tbl_ap_payments,$affiliatepress_tbl_ap_payment_commission,$affiliatepress_tbl_ap_payment_affiliate_note,$affiliatepress_get_setting_data,$affiliatepress_auto_load_settings,$affiliatepress_has_affiliate_settings_table, $pagenow,$affiliatepress_tbl_ap_affiliate_report,$affiliatepress_max_tracking_cookie_days;

            $affiliatepress_get_setting_data                      = array();
            $affiliatepress_tbl_ap_affiliates                     = $wpdb->prefix.'affiliatepress_affiliates';
            $affiliatepress_tbl_ap_settings                       = $wpdb->prefix.'affiliatepress_settings';
            $affiliatepress_tbl_ap_affiliate_visits               = $wpdb->prefix.'affiliatepress_affiliate_visits';
            $affiliatepress_tbl_ap_creative                       = $wpdb->prefix.'affiliatepress_creative';
            $affiliatepress_tbl_ap_notifications                  = $wpdb->prefix.'affiliatepress_notifications';
            $affiliatepress_tbl_ap_other_debug_logs               = $wpdb->prefix.'affiliatepress_other_debug_logs';
            $affiliatepress_tbl_ap_affiliate_commissions          = $wpdb->prefix.'affiliatepress_commissions';
            $affiliatepress_tbl_ap_customer                       = $wpdb->prefix.'affiliatepress_customer';
            $affiliatepress_tbl_ap_commission_products            = $wpdb->prefix.'affiliatepress_commission_products';
            $affiliatepress_tbl_ap_commission_debug_logs          = $wpdb->prefix.'affiliatepress_commission_debug_logs';
            $affiliatepress_tbl_ap_affiliate_form_fields          = $wpdb->prefix.'affiliatepress_affiliate_form_fields';
            $affiliatepress_tbl_ap_affiliate_links                = $wpdb->prefix.'affiliatepress_affiliate_links';
            $affiliatepress_tbl_ap_payout_debug_logs              = $wpdb->prefix.'affiliatepress_payout_debug_logs';
            $affiliatepress_tbl_ap_payouts                        = $wpdb->prefix.'affiliatepress_payouts';
            $affiliatepress_tbl_ap_payments                       = $wpdb->prefix.'affiliatepress_payments';
            $affiliatepress_tbl_ap_payment_commission             = $wpdb->prefix.'affiliatepress_payment_commission';
            $affiliatepress_tbl_ap_payment_affiliate_note         = $wpdb->prefix.'affiliatepress_payment_affiliate_note';
            $affiliatepress_tbl_ap_affiliate_report               = $wpdb->prefix.'affiliatepress_affiliate_report';

            $affiliatepress_setting_logs_data      = array();
            $affiliatepress_notification_duration  = 2000;      
            $affiliatepress_common_time_format = $this->affiliatepress_check_common_time_format(get_option('time_format'));
            $affiliatepress_common_date_format = $this->affiliatepress_check_common_date_format(get_option('date_format'));
            $affiliatepress_auto_load_settings = get_option('affiliatepress_auto_load_settings');
            $affiliatepress_max_tracking_cookie_days = 180;

            $affiliatepress_has_affiliate_settings_table = $this->affiliatepress_check_affiliate_settings_table_exists();

            register_activation_hook(AFFILIATEPRESS_DIR . '/affiliatepress-affiliate-marketing.php', array( 'AffiliatePress', 'install' ));
            register_activation_hook(AFFILIATEPRESS_DIR . '/affiliatepress-affiliate-marketing.php', array( 'AffiliatePress', 'affiliatepress_check_network_activation' ));
            register_uninstall_hook(AFFILIATEPRESS_DIR . '/affiliatepress-affiliate-marketing.php', array( 'AffiliatePress', 'uninstall' ));

            /* Set Page Capabilities Global */
            $affiliatepress_capabilities_global = array(
                'affiliatepress'                  => 'affiliatepress',
                'affiliatepress_affiliates'       => 'affiliatepress_affiliates',
                'affiliatepress_settings'         => 'affiliatepress_settings',
                'affiliatepress_visits'           => 'affiliatepress_visits',
                'affiliatepress_creative'         => 'affiliatepress_creative',
                'affiliatepress_notifications'    => 'affiliatepress_notifications',
                'affiliatepress_commissions'      => 'affiliatepress_commissions',
                'affiliatepress_affiliate_fields' => 'affiliatepress_affiliate_fields',
                'affiliatepress_payout'           => 'affiliatepress_payout',
                'affiliatepress_growth_tools'     => 'affiliatepress_growth_tools',
                'affiliatepress_addons'           => 'affiliatepress_addons',
            );

            /* AffiliatePress Admin Menu Slug */
            $this->affiliatepress_slugs = $this->affiliatepress_page_slugs();

            /* AffiliatePress Admin Menu Added */
            add_action('admin_menu', array( $this, 'affiliatepress_menu' ), 26);
            add_action('adminmenu', array( $this, 'affiliatepress_menu_style' ), 10);
            add_action('admin_enqueue_scripts', array( $this, 'affiliatepress_set_admin_css' ), 11);
            add_action('admin_enqueue_scripts', array( $this, 'affiliatepress_set_admin_js' ), 11);  

            /**Function for add common svg code */            
            add_action('affiliatepress_common_svg_code',array($this,'affiliatepress_common_svg_code_func'),10,1);

            /* Other Debug Log Entry */
            add_action('affiliatepress_other_debug_log_entry', array( $this, 'affiliatepress_other_debug_logs_func' ), 10, 5);

            /* Commission Debug Log Entry */
            add_action('affiliatepress_commission_debug_log_entry', array( $this, 'affiliatepress_commission_debug_log_entry_func' ), 10, 5);

             /* Payout Debug Log Entry */
             add_action('affiliatepress_payout_debug_log_entry', array( $this, 'affiliatepress_payout_debug_log_entry_func' ), 10, 5);

            /* Affiliatepress fancy url rule add */
            
            add_action( 'init', array( $this, 'affiliatepress_flush_rewrite_rules'), 100 );
            add_action( 'init', array( $this, 'affiliatepress_add_fancy_url_rule'), 101 );

            add_action( 'admin_init', array( $this, 'affiliatepress_flush_rewrite_rules' ) );

            /* block category add */
            if (! empty($GLOBALS['wp_version']) && version_compare($GLOBALS['wp_version'], '5.7.2', '>') ) {
                add_filter('block_categories_all', array( $this, 'affiliatepress_gutenberg_category' ), 10, 2);
            } else {
                add_filter('block_categories', array( $this, 'affiliatepress_gutenberg_category' ), 10, 2);
            }

            /* block add */
            add_action( 'admin_init', array($this, 'affiliatepress_add_gutenbergblock' ));

            /** visual composer add */
            add_action('plugins_loaded', array( $this, 'affiliatepress_check_plugin_dependencies' ), 10);
            
            /**Function for hide update notice */
            add_action('admin_init', array( $this, 'affiliatepress_hide_update_notice' ), 1);

            /**Hide all admin notices when AffiliatePress page loads */
            add_action('admin_head', array( $this, 'affiliatepress_hide_admin_notices' ));

            add_action('admin_init', array( $this, 'upgrade_data' ));


            if($pagenow == "plugins.php"){
                add_action('admin_footer', array( $this, 'affiliatepress_deactivate_feedback_popup' ));
                add_filter('plugin_action_links', array( $this, 'affiliatepress_plugin_action_links' ), 10, 2);                
            }

            add_action('wp_ajax_affiliatepress_lite_deactivate_plugin', array( $this, 'affiliatepress_lite_deactivate_plugin_func' ),15);

            /* AffiliatePress Document Ajax Call Add */
            add_action('wp_ajax_affiliatepress_get_help_data', array($this,'affiliatepress_get_help_data_func'),10);
            
            add_action('affiliatepress_send_anonymous_data',array($this,'affiliatepress_send_anonymous_data_cron'));

            /* Function for add Affiliate Privacy Policy Document Add In WordPress Privacy Page */
            //add_filter('wp_privacy_policy_content', array($this,'affiliatepress_add_plugin_privacy_content_func'),10);

            add_action( 'admin_init', array( $this, 'affiliatepress_add_plugin_privacy_content_func' ), 20 );

            add_action('user_register', array($this,'affiliatepress_add_capabilities_to_new_user'));
            add_action('set_user_role', array($this, 'affiliatepress_assign_caps_on_role_change'), 10, 3);

            if (! function_exists('is_plugin_active') ) {
                include ABSPATH . '/wp-admin/includes/plugin.php';
            }

            if (is_plugin_active('wp-rocket/wp-rocket.php') && ! is_admin() ) {
                add_filter('script_loader_tag', array( $this, 'affiliatepress_prevent_rocket_loader_script' ), 10, 2);
                add_filter('rocket_delay_js_exclusions',array($this,'affiliatepress_wp_rocket_excluded_js') ,20);
            }

            if (is_plugin_active('wp-optimize/wp-optimize.php') && ! is_admin() ) {
                add_filter('script_loader_tag', array( $this, 'affiliatepress_wp_optimize_excluded_delay_js' ), 10, 3);
                /** Enable merging of JavaScript files  --its option exclude for add */
                add_filter( 'wp-optimize-minify-default-exclusions', array($this,'affiliatepress_wp_optimize_minify_exclude_scripts'),10,1);
            }

            if (is_plugin_active('wp-hummingbird/wp-hummingbird.php') && ! is_admin() ) {
                add_filter('wphb_delay_js_exclusions', array( $this, 'affiliatepress_hummingbird_excluded_delay_js' ), 10, 1);
            }

            if (! is_admin() ) {
                add_filter('script_loader_tag', array( $this, 'affiliatepress_prevent_rocket_loader_script_clf' ), 10, 2);
		        add_filter('script_loader_tag', array( $this, 'affiliatepress_prevent_rocket_loader_script_clf_advanced' ), 11, 2);
            }

            /**lifetime deal notice dismissed */
            add_action('wp_ajax_affiliatepress_lifetime_deal_close', array( $this, 'affiliatepress_lifetime_deal_close_func' ));   

            add_action('admin_init', array( $this, 'affiliatepress_get_lifetime_deal_content' ), 10);

            add_filter( 'admin_body_class',array($this,'affiliatepress_add_admin_page_css') );

            add_action( 'shutdown', array( $this, 'affiliatepress_validate_plugin_setup' ) );

        }

        function affiliatepress_add_admin_page_css( $classes ) {
            $screen = get_current_screen();
        
            // Check if we're on AffiliatePress admin pages
            if ( isset( $_REQUEST['page'] ) && strpos( $_REQUEST['page'], 'affiliatepress' ) !== false ) {// phpcs:ignore
                $classes .= ' ap-affiliate-admin-page';
            }
        
            return $classes;
        }

        /**
         * Add AffiliatePress capabilities when new admin user register from backend
         *
         * @param  mixed $user_id   New registered user id
         * @return void
         */
        function affiliatepress_add_capabilities_to_new_user($user_id) {
            global $AffiliatePress;

            if ($user_id == '') {
                return;
            }
            if (user_can($user_id, 'administrator')) {
                $affiliatepressroles = $AffiliatePress->affiliatepress_capabilities();
                $userObj = new WP_User($user_id);
                foreach ($affiliatepressroles as $affiliatepress_role => $affiliatepress_role_desc) {
                    $userObj->add_cap($affiliatepress_role);
                }
                unset($affiliatepress_role);
                unset($affiliatepressroles);
                unset($affiliatepress_role_desc);
            }
        }

        /**
         * Any user role change to administrator from backend then all AffiliatePress capabilities assign
         *
         * @param  mixed $user_id
         * @param  mixed $role
         * @param  mixed $old_roles
         * @return void
         */
        function affiliatepress_assign_caps_on_role_change($user_id, $role, $old_roles){
            global $AffiliatePress;

            if(!empty($user_id) && $role == "administrator"){
                $affiliatepressroles = $AffiliatePress->affiliatepress_capabilities();
                $userObj = new WP_User($user_id);
                foreach ($affiliatepressroles as $affiliatepress_role => $affiliatepress_role_desc) {
                    $userObj->add_cap($affiliatepress_role);
                }
                unset($affiliatepress_role);
                unset($affiliatepressroles);
                unset($affiliatepress_role_desc);
            }
        }
        
        /**
         * Function for add WordPress Privacy Page
         *
         * @param  mixed $content
         * @return void
        */
        function affiliatepress_add_plugin_privacy_content_func(){

            if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
                
                $content = '<h2>' . esc_html__( 'What Personal Data Is Collected in AffiliatePress', 'affiliatepress-affiliate-marketing' ) . '</h2>'
                . '<p>' . esc_html__( 'User\'s Signup Details such as Username, First Name, Last Name and Custom Fields value( Website, Payment Email etc)', 'affiliatepress-affiliate-marketing' ) . '</p>'
                . '<p>' . esc_html__( 'User\'s IP Address Information', 'affiliatepress-affiliate-marketing' ) . '</p>'
                . '<p>' . esc_html__( 'User\'s Basic Details Sending to opt-ins such as (Email, First Name, Last Name)', 'affiliatepress-affiliate-marketing' ) . '</p>'
                . '<p>' . esc_html__( 'User\'s Logged in / Logout details', 'affiliatepress-affiliate-marketing' ) . '</p>'
                . '<p>' . esc_html__( 'Basic transaction information stored (e.g., payment status, amount, transaction id, and method used). AffiliatePress does not store any sensitive financial data, such as credit or debit card numbers.', 'affiliatepress-affiliate-marketing' ) . '</p>';

                wp_add_privacy_policy_content( 'AffiliatePress', $content );                           

            }            

        }
        
        /**
         * Function for api url
         *
         * @return void
        */
        public function affiliatepress_get_apiurl(){
            $api_url = 'https://arpluginshop.com';
            return $api_url;
        }
        
        /**
         * Function for AffiliatePress remote post params
         *
         * @param  mixed $plugin_info
         * @return void
        */
        function affiliatepress_get_remote_post_params( $plugin_info = '' ){
            global $wpdb;

			$action = '';
			$action = $plugin_info;

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugin_list = get_plugins();
			$site_url    = AFFILIATEPRESS_HOME_URL;
			$plugins     = array();

			$active_plugins = get_option( 'active_plugins' );

			foreach ( $plugin_list as $key => $plugin ) {
				$is_active = in_array( $key, $active_plugins );

				// filter for only AffiliatePress ones, may get some others if using our naming convention
				if ( strpos( strtolower( $plugin['Title'] ), 'affiliatepress - affiliate marketing' ) !== false){
					$name      = substr( $key, 0, strpos( $key, '/' ) );
					$plugins[] = array(
						'name'      => $name,
						'version'   => $plugin['Version'],
						'is_active' => $is_active,
					);
				}
			}
			$plugins = json_encode( $plugins );

			// get theme info
			$theme            = wp_get_theme();
			$theme_name       = $theme->get( 'Name' );
			$theme_uri        = $theme->get( 'ThemeURI' );
			$theme_version    = $theme->get( 'Version' );
			$theme_author     = $theme->get( 'Author' );
			$theme_author_uri = $theme->get( 'AuthorURI' );

			$im        = is_multisite();
			$sortorder = get_option( 'armSortOrder' );

			$post = array(
				'wp'        => get_bloginfo( 'version' ),
				'php'       => phpversion(),
				'mysql'     => $wpdb->db_version(),
				'plugins'   => $plugins,
				'tn'        => $theme_name,
				'tu'        => $theme_uri,
				'tv'        => $theme_version,
				'ta'        => $theme_author,
				'tau'       => $theme_author_uri,
				'im'        => $im,
				'sortorder' => $sortorder,
			);

			return $post;
        }

        /**
         * Function For Send AffiliatePress Anonymous Data Send 
         *
         * @return void
        */
        function affiliatepress_send_anonymous_data_cron(){
            global $AffiliatePress, $affiliatepress_global_options, $affiliatepress_website_url;

            $affiliatepress_affiliate_usage_stats = $this->affiliatepress_get_settings('affiliate_usage_stats', 'affiliate_settings');

            if($affiliatepress_affiliate_usage_stats == "true"){

                if(!function_exists('get_plugins')){
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }

                global $wpdb, $wp_version, $affiliatepress_tbl_ap_affiliates, $affiliatepress_tbl_ap_affiliate_commissions;

                $affiliatepress_active_plugins_arr  = $affiliatepress_inactive_plugin_arr = array();


                $affiliatepresspress_total_affiliates = $wpdb->get_var( "SELECT count(ap_affiliates_id) FROM {$affiliatepress_tbl_ap_affiliates}"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliates is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                $affiliatepresspress_total_commissions = $wpdb->get_var( "SELECT count(ap_commission_id) FROM {$affiliatepress_tbl_ap_affiliate_commissions}");  // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_affiliate_commissions is table name defined globally & already prepare by affiliatepress_tablename_prepare function. False Positive alarm

                $affiliatepress_active_integration_name = array();

                $affiliatepress_all_plugin_integration  = $affiliatepress_global_options->affiliatepress_all_plugin_integration();

                if(!empty($affiliatepress_all_plugin_integration)){
                    foreach($affiliatepress_all_plugin_integration as $single_integration){                        
                        $affiliatepress_plugin_value       = (isset($single_integration['plugin_value']))?$single_integration['plugin_value']:'';
                        $affiliatepress_enable_integration = $AffiliatePress->affiliatepress_get_settings('enable_'.$affiliatepress_plugin_value, 'integrations_settings');                                 
                        if($affiliatepress_enable_integration == "true"){
                            $affiliatepress_active_integration_name[] = $single_integration['plugin_name'];
                        }
                    }
                }
               
                $affiliatepress_lite_version = $affiliatepress_lite_installation_date = $affiliatepress_pro_version = $affiliatepress_pro_installation_date =
                $affiliatepress_home_url = $affiliatepress_admin_url = $affiliatepress_site_timezone = $affiliatepress_site_locale = '';

                $affiliatepress_lite_version             = get_option('affiliatepress_version');
                $affiliatepress_lite_version             = !empty($affiliatepress_lite_version) ? $affiliatepress_lite_version : '';
                $affiliatepress_lite_installation_date   = get_option('affiliatepress_install_date');
                $affiliatepress_lite_installation_date   = !empty($affiliatepress_lite_installation_date) ? $affiliatepress_lite_installation_date : '';
                $affiliatepress_pro_version              = get_option('affiliatepress_pro_version');
                $affiliatepress_pro_version              = !empty($affiliatepress_pro_version) ? $affiliatepress_pro_version : '';
                $affiliatepress_pro_installation_date    = get_option('affiliatepress_pro_install_date');
                $affiliatepress_pro_installation_date    = !empty($affiliatepress_pro_installation_date) ? $affiliatepress_pro_installation_date : '';

                $affiliatepress_home_url                 = home_url();
                $affiliatepress_admin_url                = admin_url();
                $affiliatepress_site_locale              = get_locale();
                $affiliatepress_site_locale              = !empty($site_locale)?$site_locale:'';
                $affiliatepress_site_timezone            = wp_timezone_string(); 
                
                $affiliatepress_server_information = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field($_SERVER['SERVER_SOFTWARE']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $affiliatepress_my_theme           = wp_get_theme();
                $affiliatepress_theme_data         = $affiliatepress_my_theme->get('Name').'  ('.$affiliatepress_my_theme->get('Version').' )';
                $affiliatepress_is_multisite       = is_multisite() ? 'Yes' : 'NO';
                    
                $affiliatepress_plugin_list    = get_plugins();                               
                $affiliatepress_active_plugins = get_option('active_plugins');

                foreach ( $affiliatepress_plugin_list as $key => $plugin_detail ) {
                    $is_active = in_array($key, $affiliatepress_active_plugins);
                    if ($is_active == 1 ) {
                        $name      = substr($key, 0, strpos($key, '/'));
                        $affiliatepress_active_plugins_arr[] = array(            
                            $plugin_detail['Name'] => $plugin_detail['Version']
                        );
                    } else {
                        $affiliatepress_inactive_plugin_arr[]  = array(            
                            $plugin_detail['Name'] => $plugin_detail['Version']
                        );
                    }
                }                

                $affiliatepress_total_wp_users =  count_users();
                $affiliatepress_total_wp_users = $affiliatepress_total_wp_users['total_users'];
                
                $affiliatepress_addon_url = $affiliatepress_website_url.'ap_misc/addons_list.php';
                $affiliatepress_addons_res = wp_remote_post(
                    $affiliatepress_addon_url,
                    array(
                        'method'    => 'POST',
                        'timeout'   => 45,
                        'sslverify' => false,
                        'body'      => array(
                            'affiliatepress_addons_list' => 1,
                        ),
                    )
                );
                $affiliatepress_active_addon_list = $affiliatepress_inactive_addon_list =  array();
                if ( ! is_wp_error( $affiliatepress_addons_res ) ) {
                    $affiliatepress_body_res = base64_decode( $affiliatepress_addons_res['body'] );
                    if ( ! empty( $affiliatepress_body_res ) ) {
                        $affiliatepress_body_res = json_decode( $affiliatepress_body_res, true );
                        foreach ( $affiliatepress_body_res as $affiliatepress_body_key => $affiliatepress_body_data_arr ) {  
                            foreach ( $affiliatepress_body_data_arr as $affiliatepress_body_data_key => $affiliatepress_body_val ) {
                                if(!empty($affiliatepress_body_val['addon_installer'])) {
                                    if(file_exists( WP_PLUGIN_DIR . '/'.$affiliatepress_body_val['addon_installer'])) {        
                                        $is_addon_active = is_plugin_active($affiliatepress_body_val['addon_installer']);
                                        if($is_addon_active) {
                                            $affiliatepress_active_addon_list[$affiliatepress_body_val['addon_name']] = $affiliatepress_body_val['addon_version'];
                                        } else {
                                            $affiliatepress_inactive_addon_list[$affiliatepress_body_val['addon_name']] = $affiliatepress_body_val['addon_version'];
                                        } 
                                    }
                                }
                            }
                        }
                    }
                } 

                $affiliatepress_currency_name = $this->affiliatepress_get_settings('payment_default_currency', 'affiliate_settings');
                $affiliatepress_currency = $affiliatepress_currency_name;
              
                $affiliatepress_anonymous_data = array(
                    'php_version'                             => phpversion(),
                    'affiliatepress_lite_version'             => $affiliatepress_lite_version,
                    'affiliatepress_lite_installation_date'   => $affiliatepress_lite_installation_date,
                    'affiliatepress_pro_version'              => $affiliatepress_pro_version,
                    'affiliatepress_pro_installation_date'    => $affiliatepress_pro_installation_date,                    
                    'wp_version'                              => $wp_version,
                    'server_information'                      => $affiliatepress_server_information,
                    'is_multisite'                            => $affiliatepress_is_multisite,
                    'theme_data'                              => $affiliatepress_theme_data,
                    'home_url'                                => $affiliatepress_home_url,
                    'admin_url'                               => $affiliatepress_admin_url,
                    'active_plugin_list'                      => wp_json_encode($affiliatepress_active_plugins_arr),
                    'inactivate_plugin_list'                  => wp_json_encode($affiliatepress_inactive_plugin_arr),
                    'site_locale'                             => $affiliatepress_site_locale,
                    'site_timezone'                           => $affiliatepress_site_timezone,
                    'activated_addons'                        => wp_json_encode($affiliatepress_active_addon_list),
                    'inactive_addons'                         => wp_json_encode($affiliatepress_inactive_addon_list),
                    'total_affiliates'                        => $affiliatepresspress_total_affiliates,
                    'total_commissions'                       => $affiliatepresspress_total_commissions,
                    'activated_integrations'                  => $affiliatepress_active_integration_name,
                    'affiliatepress_currency'                 => $affiliatepress_currency
                );
                                
                $url = '';
                $url = $affiliatepress_website_url.'ap_misc/ap_tracking_usage.php';
                $response = wp_remote_post(
                    $url,
                    array(
                        'timeout' => 500,
                        'body'    => array( 'ap_anonymous_data' =>  wp_json_encode($affiliatepress_anonymous_data)),
                    )
                );


            }
                
        }
                
        /**
         * Function for get help drawer
         *
         * @return HTML
        */
        function affiliatepress_get_help_data_func(){
            global $affiliatepress_website_url;
            $response              = array();
            $wpnonce               = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $affiliatepress_verify_nonce_flag = wp_verify_nonce($wpnonce, 'ap_wp_nonce');
            if (! $affiliatepress_verify_nonce_flag ) {
                $response['variant'] = 'error';
                $response['title']   = esc_html__('Error', 'affiliatepress-affiliate-marketing');
                $response['msg']     = esc_html__('Sorry, Your request can not be processed due to security reason.', 'affiliatepress-affiliate-marketing');
                wp_send_json($response);
                die();
            }
            $affiliatepress_documentation_content = '';
            if (! empty($_POST['action']) && ! empty($_POST['module']) && ! empty($_POST['page']) && ! empty($_POST['type']) ) {
                $help_module = sanitize_text_field($_POST['module']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $help_page   = sanitize_text_field($_POST['page']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $help_type   = sanitize_text_field($_POST['type']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

                $affiliatepress_remote_url = $affiliatepress_website_url;
                
                if ($help_type == 'list' ) {
                    $affiliatepress_remote_params = array(
                        'method'  => 'POST',
                        'body'    => array(
                            'action' => 'get_documentation',
                            'module' => $help_module,
                            'page'   => 'list_' . $help_module,
                        ),
                        'timeout' => 45,
                    );

                    $affiliatepress_documentation_res = wp_remote_post($affiliatepress_remote_url, $affiliatepress_remote_params);
                    if (! is_wp_error($affiliatepress_documentation_res) ) {
                        $affiliatepress_documentation_content = ! empty($affiliatepress_documentation_res['body']) ? $affiliatepress_documentation_res['body'] : '';
                    } else {
                        $affiliatepress_documentation_content = $affiliatepress_documentation_res->get_error_message();
                    }
                } elseif ($help_type == 'add'){

                    $affiliatepress_remote_params = array(
                        'method'  => 'POST',
                        'body'    => array(
                            'action' => 'get_documentation',
                            'module' => $help_module,
                            'page'   => 'list_' . $help_module,
                        ),
                        'timeout' => 45,
                    );

                    $affiliatepress_documentation_res = wp_remote_post($affiliatepress_remote_url, $affiliatepress_remote_params);
                    if (! is_wp_error($affiliatepress_documentation_res) ) {
                        $affiliatepress_documentation_content = ! empty($affiliatepress_documentation_res['body']) ? $affiliatepress_documentation_res['body'] : '';
                    } else {
                        $affiliatepress_documentation_content = $affiliatepress_documentation_res->get_error_message();
                    }

                }
            }
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: output is properly escaped or hardcoded

            echo $affiliatepress_documentation_content; //phpcs:ignore
            exit();
        }

        /**
         * Function for deative lite plugin
         *
         * @return void
        */
        function affiliatepress_lite_deactivate_plugin_func(){
            global $wpdb, $affiliatepress_website_url;
            check_ajax_referer('affiliatepress_lite_deactivate_plugin', 'security');
            if (! empty($_POST['affiliatepresslite_reason']) && isset($_POST['affiliatepresslite_details']) ) {
                $affiliatepresslite_anonymous               = isset($_POST['affiliatepresslite_anonymous']) && sanitize_text_field($_POST['affiliatepresslite_anonymous']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $args                            = $_POST;
                $data                            = array(
                'option_name'  => 'ap_deactivation_feedback',
                'option_value' => serialize($args),
                );
                $args['affiliatepresslite_site_url']        = site_url();
                $args['affiliatepresslite_site_ip_address'] = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

                if (! $affiliatepresslite_anonymous ) {
                    $args['affiliatepress_lite_site_email'] = get_option('admin_email');
                }

               $affiliatepress_deactive_lite_data = array();
               $affiliatepress_deactive_lite_data['affiliatepress_deactive_lite_data'] = $args;                
               
               $url = $affiliatepress_website_url.'ap_misc/aplite_feedback.php';

                $response = wp_remote_post(
                    $url,
                    array(
                        'timeout' => 500,
                        'body'    => $affiliatepress_deactive_lite_data,
                    )
                );

            }
            echo wp_json_encode(
                array(
                'status' => 'OK',
                )
            );
            die();            
        }

        /**
         * Modify AffiliatePress Lite Plugin Action Links
         *
         * @param  mixed $links
         * @param  mixed $file
         * @return array
        */
        function affiliatepress_plugin_action_links( $links, $file ){
            global $wp, $wpdb;
            if ($file == 'affiliatepress-affiliate-marketing/affiliatepress-affiliate-marketing.php' ) {
                if(isset($links['deactivate'])){
                    
                    $affiliatepress_deactivation_link = $links['deactivate'];
                    $affiliatepress_is_rtl_enabled = is_rtl() ? 'ap_rtl_enabled' : ''; 

                    $extra_popup_class = '';
                    if($this->affiliatepress_pro_install()) {
                        $extra_popup_class = 'affiliatepresslite-confirm-deactivate-wrapper';
                    }

                    $affiliatepress_deactivation_link   = str_replace(
                        '<a',
                        '<div class="affiliatepresslite-deactivate-form-wrapper">
	                         <span class="affiliatepresslite-deactivate-form '.$affiliatepress_is_rtl_enabled.' '.$extra_popup_class.'" id="affiliatepress-deactivate-form-' . esc_attr('affiliatepressLite') . '"></span>
	                     </div><a id="affiliatepress-deactivate-link-' . esc_attr('affiliatepressLite') . '"',
                        $affiliatepress_deactivation_link
                    );

                    $links['deactivate'] = $affiliatepress_deactivation_link;

                }
            }
            return $links;
        }        

        /**
         * Deactive feedback popup content
         *
         * @return html
        */
        function affiliatepress_deactivate_feedback_popup(){
            ?>
                     <style type="text/css" id="affiliatepress_deactivate_popup_css">
                        .affiliatepress-deactivate-btn{display: inline-block;font-weight: 400;text-align: center;white-space;vertical-align: nowrap;user-select: none;border: 1px solid transparent;padding: .375rem .75rem;font-size:1rem;line-height:1.5;border-radius:0.25rem;transition:color .15s }
                        .affiliatepress-deactivate-btn-primary{
                            color: #fff;
                            background-color: #6858e0;
                            border-color:none !important;
                        }
                        .affiliatepress-deactivate-btn:hover{
                            color: white;
                        }                    
                        .affiliatepress-deactivate-btn-cancel:hover ,.affiliatepress-deactivate-btn-cancel {
                            color: #2c3338;
                            background-color: #fff;
                            border-color:#2c3338 !important;
                            margin-right: 10px;
                        }
                        .affiliatepresslite-deactivate-form-active .affiliatepresslite-deactivate-form-bg {background: rgba( 0, 0, 0, .5 );position: fixed;top: 0;left: 0;width: 100%;height: 100%; z-index: 99;}
                        .affiliatepresslite-deactivate-form-wrapper {position: relative;z-index: 999;display: none; }
                        .affiliatepresslite-deactivate-form-active .affiliatepresslite-deactivate-form-wrapper {display: inline-block;}
                        .affiliatepresslite-deactivate-form {display: none;}
                        .affiliatepresslite-deactivate-form-active .affiliatepresslite-deactivate-form {position: absolute;bottom: 30px;left: 0;max-width: 500px;min-width: 360px;background: #fff;white-space: normal;}
                        .affiliatepresslite-deactivate-form-active .affiliatepresslite-deactivate-form.ap_rtl_enabled {position: absolute;bottom: 30px;left: unset;max-width: 500px;min-width: 360px;background: #fff;white-space: normal;}
                        .affiliatepresslite-deactivate-form-head {background: #6858e0;color: #fff;padding: 8px 18px;}
                        .affiliatepresslite-deactivate-confirm-head p{color: #fff; padding-left:10px}
                        .affiliatepresslite-deactivate-confirm-head{padding: 4px 18px; background:red; }
                        .affiliatepresslite-deactivate-form-body {padding: 8px 18px 0;color: #444;}
                        .affiliatepresslite-deactivate-form-body label[for="affiliatepresslite-remove-settings"] {font-weight: bold;}
                        .deactivating-spinner {display: none;}
                        .deactivating-spinner .spinner {float: none;margin: 4px 4px 0 18px;vertical-align: bottom;visibility: visible;}
                        .affiliatepresslite-deactivate-form-footer {padding: 0 18px 8px;}
                        .affiliatepresslite-deactivate-form-footer label[for="affiliatepresslite_anonymous"] {visibility: hidden;}
                        .affiliatepresslite-deactivate-form-footer p {display: flex;align-items: center;justify-content: space-between;margin: 0;}
                        #affiliatepresslite-deactivate-submit-form span {display: none;}
                        .affiliatepresslite-deactivate-form.process-response .affiliatepresslite-deactivate-form-body,.affiliatepresslite-deactivate-form.process-response .affiliatepresslite-deactivate-form-footer {position: relative;}
                        .affiliatepresslite-deactivate-form.process-response .affiliatepresslite-deactivate-form-body:after,.affiliatepresslite-deactivate-form.process-response .affiliatepresslite-deactivate-form-footer:after {content: "";display: block;position: absolute;top: 0;left: 0;width: 100%;height: 100%;background-color: rgba( 255, 255, 255, .5 );}
                        button#affiliatepresslite-deactivate-submit-btn{cursor:pointer;}
                        button#affiliatepresslite-deactivate-submit-btn[disabled=disabled]{ 
                            cursor:not-allowed;
                            opacity: 0.5;
                        }         
                        .affiliatepresslite-confirm-deactivate-wrapper{
                            width:550px;
                            max-width:600px !important;
                        }
                        .affiliatepresslite-confirm-deactivate-wrapper .affiliatepresslite-deactivate-confirm-head strong {
                            margin-bottom:unset;
                        }
                        .affiliatepresslite-confirm-deactivate-wrapper .affiliatepresslite-deactivate-confirm-head {
                            display: flex;
                            align-items: center;
                        }
                        body.rtl .affiliatepresslite-deactivate-form-footer p{ justify-content: space-between;}
                        body.rtl .affiliatepress-deactivate-btn-cancel:hover ,.affiliatepress-deactivate-btn-cancel{margin-left: 10px; }
                        .affiliatepress-pro-notice-plugin-label{ color: #000000; }
                        .ap-plugin-upgrade-to-pro{ color: #6858e0;}
                        a.ap-plugin-upgrade-to-pro:hover{color: #6858e0;}
                        @media (max-width: 768px){
                            .affiliatepresslite-deactivate-reason{
                                /* height: 20px !important;
                                width: 20px !important; */
                                height: 15px !important;
                                width: 15px !important;
                            }
                        }
                    </style>
                <?php
                $question_options                      = array();
                $question_options['list_data_options'] = array(
                    'setup-difficult'  => esc_html__('Set up is too difficult', 'affiliatepress-affiliate-marketing'),
                    'docs-improvement' => esc_html__('Lack of documentation', 'affiliatepress-affiliate-marketing'),
                    'features'         => esc_html__('Not the features I wanted', 'affiliatepress-affiliate-marketing'),
                    'better-plugin'    => esc_html__('Found a better plugin', 'affiliatepress-affiliate-marketing'),
                    'incompatibility'  => esc_html__('Incompatible with theme or plugin', 'affiliatepress-affiliate-marketing'),
                    'maintenance'      => esc_html__('Other', 'affiliatepress-affiliate-marketing'),
                );
    
                $html                                  = '<div class="affiliatepresslite-deactivate-form-head"><strong>' . esc_html__('AffiliatePress - Sorry to see you go', 'affiliatepress-affiliate-marketing') . '</strong></div>';
                $html                                 .= '<div class="affiliatepresslite-deactivate-form-body">';
                if (is_array($question_options['list_data_options']) ) {
                    $html .= '<div class="affiliatepresslite-deactivate-options">';
                    $html .= '<p><strong>' . esc_html__('Before you deactivate the AffiliatePress Lite plugin, would you quickly give us your reason for doing so?', 'affiliatepress-affiliate-marketing') . '</strong></p><p>';
    
                    foreach ( $question_options['list_data_options'] as $key => $option ) {
                        $html .= '<input type="radio" class="affiliatepresslite-deactivate-reason" name="affiliatepresslite-deactivate-reason" id="' . esc_attr($key) . '" value="' . esc_attr($key) . '"> <label for="' . esc_attr($key) . '">' . esc_attr($option) . '</label><br>';
                    }
                    $html .= '</p><label id="affiliatepresslite-deactivate-details-label" for="affiliatepresslite-deactivate-reasons"><strong>' . esc_html__('How could we improve ?', 'affiliatepress-affiliate-marketing') . '</strong></label><textarea name="affiliatepresslite-deactivate-details" id="affiliatepresslite-deactivate-details" rows="2" style="width:100%"></textarea>';
                    $html .= '</div>';
                }
                    $html .= '<hr/>';
    
                    $html .= '</div>';
                    $html .= '<p class="deactivating-spinner"><span class="spinner"></span> ' . esc_html__('Submitting form', 'affiliatepress-affiliate-marketing') . '</p>';
                    $html .= '<div class="affiliatepresslite-deactivate-form-footer"><p>';
                    $html .= '<label for="affiliatepresslite_anonymous" title="'
                        . esc_html__('If you uncheck this option, then your email address will be sent along with your feedback. This can be used by affiliatepress to get back to you for more information or a solution.', 'affiliatepress-affiliate-marketing')
                        . '"><input type="checkbox" name="affiliatepresslite-deactivate-tracking" id="affiliatepresslite_anonymous"> ' . esc_html__('Send anonymous', 'affiliatepress-affiliate-marketing') . '</label><br>';
                    $html .= '<a id="affiliatepresslite-deactivate-submit-form"  class="affiliatepress-deactivate-btn affiliatepress-deactivate-btn-primary" href="#"><span>'
                    . esc_html__('Submit and', 'affiliatepress-affiliate-marketing').'</span> '.esc_html__('Deactivate', 'affiliatepress-affiliate-marketing')
                    . '</a>';
                    $html .= '</p></div>';
                ?>
                    <div class="affiliatepresslite-deactivate-form-bg"></div>
                   
                    <script type="text/javascript">
                        jQuery(document).ready(function($){
                            var affiliatepresslite_deactivateURL = $("#affiliatepress-deactivate-link-<?php echo esc_attr('affiliatepressLite'); ?>")
                                affiliatepresslite_formContainer = $('#affiliatepress-deactivate-form-<?php echo esc_attr('affiliatepressLite'); ?>'),
                                affiliatepresslite_deactivated = true,
                                affiliatepresslite_detailsStrings = {
                                    'setup-difficult' : '<?php echo esc_html__('What was the dificult part?', 'affiliatepress-affiliate-marketing'); ?>',
                                    'docs-improvement' : '<?php echo esc_html__('What can we describe more?', 'affiliatepress-affiliate-marketing'); ?>',
                                    'features' : '<?php echo esc_html__('How could we improve?', 'affiliatepress-affiliate-marketing'); ?>',
                                    'better-plugin' : '<?php echo esc_html__('Can you mention it?', 'affiliatepress-affiliate-marketing'); ?>',
                                    'incompatibility' : '<?php echo esc_html__('With what plugin or theme is incompatible?', 'affiliatepress-affiliate-marketing'); ?>',
                                    'bought-premium' : '<?php echo esc_html__('Please specify experience', 'affiliatepress-affiliate-marketing'); ?>',
                                    'maintenance' : '<?php echo esc_html__('Please specify', 'affiliatepress-affiliate-marketing'); ?>',
                                };
    
                            jQuery( affiliatepresslite_deactivateURL).attr('onclick', "javascript:event.preventDefault();");
                            jQuery( affiliatepresslite_deactivateURL ).on("click", function(){
    
                                function affiliatepressliteSubmitData(affiliatepresslite_data, affiliatepresslite_formContainer)
                                {
                                    affiliatepresslite_data['action']          = 'affiliatepress_lite_deactivate_plugin';
                                    affiliatepresslite_data['security']        = '<?php echo esc_html(wp_create_nonce('affiliatepress_lite_deactivate_plugin')); ?>';
                                    affiliatepresslite_data['dataType']        = 'json';
                                    affiliatepresslite_formContainer.addClass( 'process-response' );
                                    affiliatepresslite_formContainer.find(".deactivating-spinner").show();
                                    jQuery.post(ajaxurl,affiliatepresslite_data,function(response){                                           
                                        window.location.href = affiliatepresslite_url;                                       
                                    });
                                }
                                
                                var affiliatepresslite_url = affiliatepresslite_deactivateURL.attr( 'href' );
                                jQuery('body').toggleClass('affiliatepresslite-deactivate-form-active');
    
                                affiliatepresslite_formContainer.show({complete: function(){
                                    var offset = affiliatepresslite_formContainer.offset();
                                    if( offset.top < 50) {
                                        $(this).parent().css('top', (50 - offset.top) + 'px')
                                    }
                                    jQuery('html,body').animate({ scrollTop: Math.max(0, offset.top - 50) });
                                }});
    
                                <?php  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: output is properly escaped or hardcoded ?>
                                affiliatepresslite_formContainer.html( '<?php echo $html; //phpcs:ignore ?>');
                                
    
                                affiliatepresslite_formContainer.on( 'change', 'input[type=radio]', function()
                                {
                                    var affiliatepresslite_detailsLabel = affiliatepresslite_formContainer.find( '#affiliatepresslite-deactivate-details-label strong' );
                                    var affiliatepresslite_anonymousLabel = affiliatepresslite_formContainer.find( 'label[for="affiliatepresslite_anonymous"]' )[0];
                                    var affiliatepresslite_submitSpan = affiliatepresslite_formContainer.find( '#affiliatepresslite-deactivate-submit-form span' )[0];
                                    var affiliatepresslite_value = affiliatepresslite_formContainer.find( 'input[name="affiliatepresslite-deactivate-reason"]:checked' ).val();
    
                                    affiliatepresslite_detailsLabel.text( affiliatepresslite_detailsStrings[ affiliatepresslite_value ] );
                                    affiliatepresslite_anonymousLabel.style.visibility = "visible";
                                    affiliatepresslite_submitSpan.style.display = "inline-block";
                                    if(affiliatepresslite_deactivated)
                                    {
                                        affiliatepresslite_deactivated = false;
                                        jQuery('#affiliatepresslite-deactivate-submit-form').removeAttr("disabled");
                                        affiliatepresslite_formContainer.off('click', '#affiliatepresslite-deactivate-submit-form');
                                        affiliatepresslite_formContainer.on('click', '#affiliatepresslite-deactivate-submit-form', function(e){
                                            e.preventDefault();
                                            var data = {
                                                affiliatepresslite_reason: affiliatepresslite_formContainer.find('input[name="affiliatepresslite-deactivate-reason"]:checked').val(),
                                                affiliatepresslite_details: affiliatepresslite_formContainer.find('#affiliatepresslite-deactivate-details').val(),
                                                affiliatepresslite_anonymous: affiliatepresslite_formContainer.find('#affiliatepresslite_anonymous:checked').length,
                                            };
                                            affiliatepressliteSubmitData(data, affiliatepresslite_formContainer);
                                        });
                                    }
                                });
    
                                affiliatepresslite_formContainer.on('click', '#affiliatepresslite-deactivate-submit-form', function(e){
                                    e.preventDefault();
                                    affiliatepressliteSubmitData({}, affiliatepresslite_formContainer);
                                });
                                $('.affiliatepresslite-deactivate-form-bg').on('click',function(){
                                    affiliatepresslite_formContainer.fadeOut(); 
                                    $('body').removeClass('affiliatepresslite-deactivate-form-active');
                                });
    
                                affiliatepresslite_formContainer.on( 'change', '#affiliatepresslite-risk-confirm', function() {
                                    if(jQuery(this).is(":checked")) {
                                        $('#affiliatepresslite-deactivate-submit-btn').removeAttr("disabled");
                                    } else {
                                        $('#affiliatepresslite-deactivate-submit-btn').attr('disabled','disabled');
                                    }
                                });                            
                                affiliatepresslite_formContainer.on( 'click', '#affiliatepresslite-deactivate-cancel-btn', function(e) {
                                    e.preventDefault();
                                    affiliatepresslite_formContainer.fadeOut(); 
                                    $('body').removeClass('affiliatepresslite-deactivate-form-active');
                                    return false;
                                });
                                affiliatepresslite_formContainer.on( 'click', '#affiliatepresslite-deactivate-submit-btn', function() {
                                    window.location.href = affiliatepresslite_url;
                                    return false;
                                });                            
                            });
                        });
                    </script>
                <?php
        }

        /**
         * Update lite version details
         *
         * @return void
         */
        function upgrade_data()
        {
            global $affiliatepress_version, $AffiliatePress;
            $affiliatepress_old_version = get_option('affiliatepress_version', true);
            if (version_compare($affiliatepress_old_version, '2.3', '<') ) {
                $affiliatepress_load_upgrade_file = AFFILIATEPRESS_VIEWS_DIR . '/upgrade_latest_data.php';
                include $affiliatepress_load_upgrade_file;
                $AffiliatePress->affiliatepress_send_anonymous_data_cron();
            }
        }
        
        /**
         * Function for generate minor dark color 
         *
         * @param  string $affiliatepress_color
         * @param  integer $affiliatepress_percent
         * @return string (color code)
        */
        function affiliatepress_generate_darken_color($affiliatepress_color, $affiliatepress_percent = 10){
            
            if (substr($affiliatepress_color, 0, 1) == '#') {
                $affiliatepress_color = substr($affiliatepress_color, 1);
            }        
            if (strlen($affiliatepress_color) != 6) {
                return $affiliatepress_color;
            }

            $r = hexdec(substr($affiliatepress_color, 0, 2));
            $affiliatepress_g = hexdec(substr($affiliatepress_color, 2, 2));
            $affiliatepress_b = hexdec(substr($affiliatepress_color, 4, 2));
            
            $r = max(0, round($r - ($r * $affiliatepress_percent / 100)));
            $affiliatepress_g = max(0, round($affiliatepress_g - ($affiliatepress_g * $affiliatepress_percent / 100)));
            $affiliatepress_b = max(0, round($affiliatepress_b - ($affiliatepress_b * $affiliatepress_percent / 100)));

            return sprintf("#%02x%02x%02x", $r, $affiliatepress_g, $affiliatepress_b);
        }

        function affiliatepress_hex_to_rgb_color($hex,$opacity = 1 ){
            $rgb_color = '';
			if ( ! empty( $hex ) ) {
				list($r, $g, $b) = sscanf( $hex, '#%02x%02x%02x' );
                $rgb_color = 'rgba('.$r.','.$g.','.$b.','.$opacity.')';
			}
			return $rgb_color;
        }
        
        /**
         * Function for generate lighter color
         *
         * @param  string $affiliatepress_color
         * @param  integer $affiliatepress_percent
         * @return string (color code value)
        */
        function affiliatepress_generate_lighten_color($affiliatepress_color, $affiliatepress_percent = 10) {

            if (substr($affiliatepress_color, 0, 1) == '#') {
                $affiliatepress_color = substr($affiliatepress_color, 1);
            }        
            if (strlen($affiliatepress_color) != 6) {
                return $affiliatepress_color;
            }
        
            $r = hexdec(substr($affiliatepress_color, 0, 2));
            $affiliatepress_g = hexdec(substr($affiliatepress_color, 2, 2));
            $affiliatepress_b = hexdec(substr($affiliatepress_color, 4, 2));
        
            $r = min(255, round($r + ((255 - $r) * $affiliatepress_percent / 100)));
            $affiliatepress_g = min(255, round($affiliatepress_g + ((255 - $affiliatepress_g) * $affiliatepress_percent / 100)));
            $affiliatepress_b = min(255, round($affiliatepress_b + ((255 - $affiliatepress_b) * $affiliatepress_percent / 100)));
        
            return sprintf("#%02x%02x%02x", $r, $affiliatepress_g, $affiliatepress_b);
            
        }        
        
        /**
         * Function for dynamic css
         *
         * @return String 
         */
        function affiliatepress_front_dynamic_variable_add(){
            global $AffiliatePress;

            $affiliatepress_default_primary_color          = '#6858e0';
            $affiliatepress_default_title_color            = '#1A1E26';
            $affiliatepress_default_content_color          = '#656E81';
            $affiliatepress_default_background_color       = '#ffffff';
            $affiliatepress_default_border_color           = '#C9CFDB';
            $affiliatepress_default_panel_background_color = '#ffffff';

            $affiliatepress_primary_color = $AffiliatePress->affiliatepress_get_settings('primary_color', 'appearance_settings');            
            if(empty($affiliatepress_primary_color)){
                $affiliatepress_primary_color = $affiliatepress_default_primary_color;
            }
            $affiliatepress_text_color = $AffiliatePress->affiliatepress_get_settings('text_color', 'appearance_settings');            
            if(empty($affiliatepress_text_color)){
                $affiliatepress_text_color = $affiliatepress_default_title_color;
            } 
            $affiliatepress_content_color = $AffiliatePress->affiliatepress_get_settings('content_color', 'appearance_settings');            
            if(empty($affiliatepress_content_color)){
                $affiliatepress_content_color = $affiliatepress_default_content_color;
            }             
            $affiliatepress_background_color = $AffiliatePress->affiliatepress_get_settings('background_color', 'appearance_settings');            
            if(empty($affiliatepress_background_color)){
                $affiliatepress_background_color = $affiliatepress_default_background_color;
            } 
            $affiliatepress_border_color = $AffiliatePress->affiliatepress_get_settings('border_color', 'appearance_settings');            
            if(empty($affiliatepress_border_color)){
                $affiliatepress_border_color = $affiliatepress_default_border_color;
            }            
            $affiliatepress_panel_background_color = $AffiliatePress->affiliatepress_get_settings('panel_background_color', 'appearance_settings');            
            if(empty($affiliatepress_panel_background_color)){
                $affiliatepress_panel_background_color = $affiliatepress_default_panel_background_color;
            }            

            $affiliatepress_title_dark_color = $this->affiliatepress_generate_darken_color($affiliatepress_text_color,20);
            
            $affiliatepress_primary_dark_color = $this->affiliatepress_generate_darken_color($affiliatepress_primary_color);
            $affiliatepress_primary_light3_color = $this->affiliatepress_generate_lighten_color($affiliatepress_primary_color, 10);
            $affiliatepress_primary_light5_color = $this->affiliatepress_generate_lighten_color($affiliatepress_primary_color, 20);
            $affiliatepress_primary_light7_color = $this->affiliatepress_generate_lighten_color($affiliatepress_primary_color, 40);
            $affiliatepress_primary_light8_color = $this->affiliatepress_generate_lighten_color($affiliatepress_primary_color, 60);
            $affiliatepress_primary_light10_color = $this->affiliatepress_generate_lighten_color($affiliatepress_primary_color, 92);

            $affiliatepress_primary_opacity_border = 0.2;
            $affiliatepress_primary_rgb_color = $this->affiliatepress_hex_to_rgb_color($affiliatepress_primary_color,$affiliatepress_primary_opacity_border);
            $affiliatepress_primary_color_opacity_5 = $this->affiliatepress_hex_to_rgb_color($affiliatepress_primary_color,0.05);
            $affiliatepress_primary_color_opacity_6 = $this->affiliatepress_hex_to_rgb_color($affiliatepress_primary_color,0.06);
            $affiliatepress_primary_color_opacity_1 = $this->affiliatepress_hex_to_rgb_color($affiliatepress_primary_color,0.1);

            $affiliatepress_orignal_date_icon_base64 = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMSA2LjQ0NDQ0SDE1TTQuMTExMSAxVjIuNTU1NTZNMTEuODg4OSAxVjIuNTU1NTZNMy40ODg4OSAxNUgxMi41MTExQzEzLjM4MjMgMTUgMTMuODE3OSAxNSAxNC4xNTA3IDE0LjgzMDRDMTQuNDQzMyAxNC42ODEzIDE0LjY4MTMgMTQuNDQzMyAxNC44MzA0IDE0LjE1MDdDMTUgMTMuODE3OSAxNSAxMy4zODIzIDE1IDEyLjUxMTFWNS4wNDQ0NEMxNSA0LjE3MzI1IDE1IDMuNzM3NjUgMTQuODMwNCAzLjQwNDlDMTQuNjgxMyAzLjExMjIgMTQuNDQzMyAyLjg3NDIzIDE0LjE1MDcgMi43NTEwQzEzLjgxNzkgMi41NTU2NiAxMy4zODIzIDIuNTU1NiAxMi41MTExIDIuNTU1NkgzLjQ4ODg5QzIuNjE3NyAyLjU1NTYgMi4xODIxIDIuNTU1NiAxLjg0OTM1IDIuNzUxMEMxLjU1NjY1IDIuODc0MjMgMS4zMTg2OCAzLjExMjIgMS4xNjk1NSAzLjQwNDlDMSAzLjczNzY1IDEgNC4xNzMyNSAxIDUuMDQ0NDRWMTIuNTExMUMxIDEzLjM4MjMgMSAxMy44MTc5IDEuMTY5NTUgMTQuMTUwN0MxLjMxODY4IDE0LjQ0MzMgMS41NTY2NSAxNC42ODEzIDEuODQ5MzUgMTQuODMwNEMyLjE4MjEgMTUgMi42MTc2OSAxNSAzLjQ4ODg5IDE1WiIgc3Ryb2tlPSIjNjE3MTkxIiBzdHJva2Utd2lkdGg9IjEuMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+PC9zdmc+";

            $affiliatepress_svg_content = base64_decode(explode(",", $affiliatepress_orignal_date_icon_base64)[1]);
            $affiliatepress_modified_svg_content = str_replace('stroke="#617191"', 'stroke="'.$affiliatepress_border_color.'"', $affiliatepress_svg_content);
            $affiliatepress_date_icon_base64 = "data:image/svg+xml;base64,".base64_encode($affiliatepress_modified_svg_content);

            $affiliatepress_orignal_close_icon_base64 = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTQiIGhlaWdodD0iMTQiIHZpZXdCb3g9IjAgMCAxNCAxNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTYuMzE5MDggNi45OTcwNEM2LjIzODk2IDcuMDY1NDkgNi4xODg0NyA3LjEwMzA1IDYuMTQ0MjQgNy4xNDY4N0M0LjE3MDUzIDkuMTIwNjQgMi4xOTc2NSAxMS4wOTUyIDAuMjIyNjgzIDEzLjA2NzNDMC4wNzUzODQ2IDEzLjIxNDcgLTAuMDA3NjUzMjMgMTMuMzc3OCAwLjAzOTQ5ODkgMTMuNTg0OUMwLjEyMjUzNyAxMy45NDU5IDAuNTU2MDg2IDE0LjA4OTUgMC44NDQwMDYgMTMuODU0MUMwLjg4ODIzOCAxMy44MTc4IDAuOTI2NjI3IDEzLjc3NDggMC45NjcxMDMgMTMuNzM0M0MyLjkyOTk3IDExLjc3MjIgNC44OTI4MyA5LjgxMDE0IDYuODU1MjggNy44NDc2NEM2Ljg5OTA5IDcuODAzODIgNi45MzU4MSA3Ljc1MzMyIDYuOTgyNTUgNy42OTc4MUM3LjA0NjM5IDcuNzU4MzMgNy4wOTE0NiA3Ljc5OTIzIDcuMTM0ODUgNy44NDIyMkM5LjExOSA5LjgyNzI1IDExLjEwNCAxMS44MTE5IDEzLjA4NjUgMTMuNzk4MUMxMy4yMzI1IDEzLjk0NDYgMTMuMzkyNyAxNC4wMzM1IDEzLjYwMjIgMTMuOTg4QzEzLjk2NTIgMTMuOTA5MiAxNC4xMTY3IDEzLjQ4MDUgMTMuODg2OCAxMy4xODg0QzEzLjg0ODQgMTMuMTM5NSAxMy44MDE3IDEzLjA5NyAxMy43NTc0IDEzLjA1MjdDMTEuNzg3OSAxMS4wODIzIDkuODE4NzcgOS4xMTIyOSA3Ljg0ODQgNy4xNDI3QzcuODA0NTggNy4wOTg4OCA3Ljc1MDM0IDcuMDY1OSA3LjY5MTA4IDcuMDIwODNDNy43NTcwMSA2Ljk1MTEzIDcuNzk4MzIgNi45MDYwNSA3Ljg0MTMgNi44NjMwNkM5LjgyMjUzIDQuODgyMiAxMS44MDQ2IDIuOTAyNiAxMy43ODE2IDAuOTE3OTg4QzEzLjg3NTUgMC44MjM2NjIgMTMuOTU0IDAuNjkyNjA3IDEzLjk4NzggMC41NjQ4OUMxNC4wMzk1IDAuMzY5NTU5IDEzLjkyMSAwLjE2NDYzIDEzLjc0NTggMC4wNjU3MTJDMTMuNTY4IC0wLjAzNDg3NDkgMTMuMzQ5OCAtMC4wMTkwMTUzIDEzLjE4ODcgMC4xMDk1MzVDMTMuMTQwMyAwLjE0ODM1MiAxMy4wOTczIDAuMTk0NjggMTMuMDUzNSAwLjIzODkyMUMxMS4wODMxIDIuMjA4NTEgOS4xMTMxNiA0LjE3ODA5IDcuMTQzNjIgNi4xNDgxQzcuMDk5OCA2LjE5MTkyIDcuMDY2IDYuMjQ1NzYgNy4wMjc2MiA2LjI5NTAxQzcuMDA1MDggNi4zMDI5NCA2Ljk4MjU1IDYuMzEwNDYgNi45NjAwMiA2LjMxODM5QzYuOTI3ODkgNi4yNjQ1NSA2LjkwNTM1IDYuMjAxMTEgNi44NjIzNyA2LjE1ODUzQzQuODc5MDYgNC4xNzIyNSAyLjg5MzY2IDIuMTg4NDcgMC45MDk5MzYgMC4yMDIxOTFDMC43NTA5NTQgMC4wNDMxNzI4IDAuNTc3MzY3IC0wLjA0Njk3OSAwLjM0OTk1MiAwLjAyNTIyNjZDMC4wMTMyMTA2IDAuMTMyNDkxIC0wLjEwOTQ2OSAwLjUzNDAwMyAwLjExMDg1MyAwLjgxMDcyMkMwLjE0OTY2IDAuODU5MTM4IDAuMTk2Mzk1IDAuOTAxNzExIDAuMjQwNjI2IDAuOTQ1OTUyQzIuMjEzOTIgMi45MjAxMyA0LjE4NjggNC44OTM4OSA2LjE2MDUyIDYuODY3MjNDNi4yMDA5OSA2LjkwNzMgNi4yNDg1NiA2LjkzOTQ0IDYuMzE5MDggNi45OTcwNFoiIGZpbGw9ImJsYWNrIi8+Cjwvc3ZnPgo=";

            $affiliatepress_svg_content = base64_decode(explode(",", $affiliatepress_orignal_close_icon_base64)[1]);
            $affiliatepress_modified_svg_content = str_replace('fill="black"', 'fill="'.$affiliatepress_border_color.'"', $affiliatepress_svg_content);
            $affiliatepress_date_close_icon_base64 = "data:image/svg+xml;base64,".base64_encode($affiliatepress_modified_svg_content);

            $affiliatepress_orignal_date_icon_base64 = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTMuMzMzMDEgMTIuNUgxNi42NjYzTDExLjY2NjQgNy41IiBzdHJva2U9IiM5Q0E3QkQiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPC9zdmc+Cg==";
            $affiliatepress_svg_content = base64_decode(explode(",", $affiliatepress_orignal_date_icon_base64)[1]);
            $affiliatepress_modified_svg_content = str_replace('stroke="#9CA7BD"', 'stroke="'.$affiliatepress_border_color.'"', $affiliatepress_svg_content);
            $affiliatepress_date_separator_icon_base64 = "data:image/svg+xml;base64,".base64_encode($affiliatepress_modified_svg_content);
            
            $affiliatepress_title_dark_color = $this->affiliatepress_generate_lighten_color($affiliatepress_text_color,30);


            global $affiliatepress_global_options, $AffiliatePress;

            $affiliatepress_google_fonts_list  = $affiliatepress_global_options->affiliatepress_get_google_fonts();            
            $affiliatepress_font_family = $AffiliatePress->affiliatepress_get_settings('font', 'appearance_settings');  
            $affiliatepress_font_family = $affiliatepress_font_family == 'Inherit Fonts' ? 'inherit' : $affiliatepress_font_family;
            
            if(empty($affiliatepress_font_family)){
                $affiliatepress_font_family = "Inter";
            }

            if (! empty($affiliatepress_font_family) && ($affiliatepress_font_family != 'Inter') && in_array( $affiliatepress_font_family, $affiliatepress_google_fonts_list ) ) {
                $affiliatepress_google_font_url = 'https://fonts.googleapis.com/css2?family=' . $affiliatepress_font_family . '&display=swap';
                $affiliatepress_google_font_url = apply_filters('affiliatepress_modify_google_font_url', $affiliatepress_google_font_url, $affiliatepress_font_family);

                wp_register_style('affiliatepress_front_font_css_' . $affiliatepress_font_family, $affiliatepress_google_font_url, array(), AFFILIATEPRESS_VERSION);
                wp_enqueue_style('affiliatepress_front_font_css_' . $affiliatepress_font_family);                    
            } 

            $affiliatepress_primary_light12_color = $this->affiliatepress_generate_lighten_color($affiliatepress_primary_color, 96);

            $affiliatepress_front_dynamic_loader_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" width="85" height="85" style="shape-rendering: auto; display: block; background: '.$affiliatepress_background_color.';" xmlns:xlink="http://www.w3.org/1999/xlink"><g><circle stroke-linecap="round" fill="none" stroke-dasharray="50.26548245743669 50.26548245743669" stroke="{affiliatepress_primary_color}" stroke-width="6" r="32" cy="50" cx="50"><animateTransform values="0 50 50;360 50 50" keyTimes="0;1" dur="2.127659574468085s" repeatCount="indefinite" type="rotate" attributeName="transform"></animateTransform></circle><g></g></g></svg>';

            $affiliatepress_front_dynamic_loader_icon = base64_encode(str_replace('{affiliatepress_primary_color}', $affiliatepress_primary_color, $affiliatepress_front_dynamic_loader_icon ));


			$hex                               = $affiliatepress_primary_color;
			list($r, $g, $b)                   = sscanf($hex, '#%02x%02x%02x');			        

            $affiliatepress_front_dynamic_var = '
                :root {
                    --ap-primary-r:'.$r.';
                    --ap-primary-g:'.$g.';
                    --ap-primary-b:'.$b.';                
                    --ap-primary-color: '.$affiliatepress_primary_color.';
                    --ap-front-primary-color: '.$affiliatepress_primary_color.';
                    --ap-front-title-color:'.$affiliatepress_text_color.';
                    --ap-front-content-color:'.$affiliatepress_content_color.';                    
                    --ap-content-color:'.$affiliatepress_content_color.';
                    --ap-placeholder-color:'.$affiliatepress_content_color.';
                    --ap-front-cl-white:'.$affiliatepress_background_color.';
                    --ap-panel-sidebar-color:'.$affiliatepress_panel_background_color.';
                    --ap-color-primary-light-10:'.$affiliatepress_panel_background_color.';
                    --ap-front-color-primary-light-10:'.$affiliatepress_panel_background_color.';
                    --ap-front-primary-font:'.$affiliatepress_font_family.';
                    --ap-primary-font:'.$affiliatepress_font_family.';

                    --ap-front-border-color:'.$affiliatepress_border_color.';   
                    --el-text-color:'.$affiliatepress_border_color.';                 
                    --el-fill-color-blank'.$affiliatepress_background_color.';

                    --ap-primary-color-hover:'.$affiliatepress_primary_dark_color.';
                    --ap-color-primary-light-3:'.$affiliatepress_primary_light3_color.';
                    --ap-color-primary-light-5:'. $affiliatepress_primary_light5_color.';
                    --ap-front-color-primary-light-5:'. $affiliatepress_primary_light5_color.';
                    --ap-color-primary-light-7:'. $affiliatepress_primary_light7_color.';
                    --ap-color-primary-light-8:'. $affiliatepress_primary_light8_color.';     
                    --ap-color-primary-rgb-border:'. $affiliatepress_primary_rgb_color.';    
                    --ap-color-primary-color-opacity-5:'. $affiliatepress_primary_color_opacity_5.'; 
                    --ap-color-primary-color-opacity-6:'. $affiliatepress_primary_color_opacity_6.';    
                    --ap-color-primary-color-opacity-1:'. $affiliatepress_primary_color_opacity_1.';  
                    --ap-heading-color:'. $affiliatepress_text_color.';  
                }

                .ap-empty-img-bg{
                    fill:'.$affiliatepress_primary_light12_color.';
                }
                .ap-empty-img-symbol{
                    fill:'.$affiliatepress_primary_light8_color.';
                }
                .ap-empty-img-lite-2{
                    fill:'.$affiliatepress_primary_light5_color.';
                }
                .ap-empty-img-lite-3{
                    fill:'.$affiliatepress_primary_light7_color.';
                }
                .ap-empty-img-lite-2{
                    fill:'.$affiliatepress_primary_light8_color.';
                }
                .ap-empty-img-lite-1{
                    fill:'.$affiliatepress_primary_light10_color.';
                }
                .ap-primary-color-fill,
                .ap-empty-data-svg-icon-color{
                    fill:'.$affiliatepress_primary_color.';
                }
                .ap-primary-color-stroke{
                    stroke:'.$affiliatepress_primary_color.';
                }
                .ap-empty-data-svg-icon-background-color{
                    fill:'.$affiliatepress_background_color.';
                }
                .ap-background-color-stroke{
                    stroke:'.$affiliatepress_background_color.';
                }
                .el-table .descending .sort-caret.descending {
                    border-top-color: '.$affiliatepress_title_dark_color.' !important;
                }     
                .el-table .ascending .sort-caret.ascending {
                    border-bottom-color: '.$affiliatepress_title_dark_color.' !important;
                }                               
                .ap-form-date-picker-control .el-input__prefix .el-input__icon::before, .ap-form-date-range-control.el-date-editor .el-range__icon::before {
                    background: url("'.$affiliatepress_date_icon_base64.'") !important;
                }
                .ap-form-date-range-control.el-date-editor .el-range__close-icon::before{                    
                    background:url("'.$affiliatepress_date_close_icon_base64.'") !important;
                }
                .ap-form-date-range-control .el-range-separator::after{
                    background: url("'.$affiliatepress_date_separator_icon_base64.'") !important;
                }
                .ap-svg-border-color{
                    stroke: '.$affiliatepress_border_color.';
                }
                .ap-svg-content-color{
                    stroke: '.$affiliatepress_content_color.';
                }
                .ap-svg-content-color-fill{
                    fill: '.$affiliatepress_content_color.';
                }
            ';


            
            $affiliatepress_front_dynamic_var .='.ap-front-loader-container .ap-front-loader{ background: url(data:image/svg+xml;base64,'.$affiliatepress_front_dynamic_loader_icon.') no-repeat left top !important; background-size: 100%;}';
            

            return $affiliatepress_front_dynamic_var;

        }

        
        /**
         * function for return payment gateway name
         *
         * @param  string $affiliatepress_payment_gateway
         * @return String 
         */
        function affiliatepress_get_payment_method_name_by_slug($affiliatepress_payment_gateway){
            if(!empty($affiliatepress_payment_gateway)){
                global $affiliatepress_global_options;
                $affiliatepress_all_payment_gateway_arr = array();
                $affiliatepress_all_payment_method = (isset($affiliatepress_global_data['payment_method']))?$affiliatepress_global_data['payment_method']:array();
                if(!empty($affiliatepress_all_payment_method)){
                    foreach($affiliatepress_all_payment_method as $affiliatepress_payment){
                        $affiliatepress_all_payment_gateway_arr[$affiliatepress_payment['value']] = $affiliatepress_payment['text'];
                    }
                }
                if(isset($affiliatepress_all_payment_gateway_arr[$affiliatepress_payment_gateway])){
                    return $affiliatepress_all_payment_gateway_arr[$affiliatepress_payment_gateway];
                }
            }
            return $affiliatepress_payment_gateway;
        }
        
        /**
         * Function for check Pro Version Install or not
         *
         * @return boolean 
         */
        function affiliatepress_pro_install(){
            $affiliatepress_flag = true;
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            if ( !is_plugin_active('affiliatepress-affiliate-marketing-pro/affiliatepress-affiliate-marketing-pro.php')){
                $affiliatepress_flag = false;
            }
            return $affiliatepress_flag;            
        }      
                
        /**
         * Function for get default commission rate
         *
         * @return array 
        */
        function affiliatepress_get_default_commission_rate(){
            global $AffiliatePress;
            $affiliatepress_commission_rule = array();
            $affiliatepress_default_discount_val = $AffiliatePress->affiliatepress_get_settings('default_discount_val', 'commissions_settings');
            $affiliatepress_default_discount_type = $AffiliatePress->affiliatepress_get_settings('default_discount_type', 'commissions_settings');
            $affiliatepress_flat_rate_commission_basis = $AffiliatePress->affiliatepress_get_settings('flat_rate_commission_basis', 'commissions_settings');
            $affiliatepress_commission_rule['discount_val'] = $affiliatepress_default_discount_val;
            $affiliatepress_commission_rule['discount_type'] = $affiliatepress_default_discount_type;
            $affiliatepress_commission_rule['commission_base'] = $affiliatepress_flat_rate_commission_basis;
            return $affiliatepress_commission_rule;
        }

        
        /**
         * Function for check affiliate setting table exists
         *
         * @return boolean
        */
        function affiliatepress_check_affiliate_settings_table_exists(){
            global $wpdb,$affiliatepress_tbl_ap_settings, $affiliatepress_auto_load_settings; 
            if(!empty($affiliatepress_auto_load_settings) && isset($affiliatepress_auto_load_settings['table_logs']['settings_table']) && !empty($affiliatepress_auto_load_settings['table_logs']['settings_table'])){
                return true;
            }                      
            $affiliatepress_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$affiliatepress_tbl_ap_settings'") == $affiliatepress_tbl_ap_settings; // phpcs:ignore 
            if($affiliatepress_table_exists){
                return true;
            } else {
                return false;
            }            
        }

        /**
         * Function for update auto load settings
         *
         * @return void
        */
        function affiliatepress_update_all_auto_load_settings(){            
            global $wpdb, $affiliatepress_tbl_ap_settings,$AffiliatePress,$affiliatepress_auto_load_settings;
            $affiliatepress_common_auto_load_settings = array('affiliate_url_parameter','enable_fancy_affiliate_url','payment_default_currency','enable_learn_dash','enable_memberpress','enable_easy_digital_downloads','enable_armember','enable_woocommerce','price_number_of_decimals','price_separator','price_symbol_position');
            $affiliatepress_default_settings = $AffiliatePress->affiliatepress_install_all_settings();
            foreach($affiliatepress_default_settings as $affiliatepress_all_set){
                $affiliatepress_auto_load = (isset($affiliatepress_all_set['auto_load']))?$affiliatepress_all_set['auto_load']:'';            
                if($affiliatepress_auto_load == 1){
                    $affiliatepress_common_auto_load_settings[] = $affiliatepress_all_set['ap_setting_name'];
                }
            }
            $affiliatepress_tbl_ap_settings_name = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_settings); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_settings contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_settings_key_placeholder  = ' ap_setting_name IN(';
            $affiliatepress_settings_key_placeholder .= rtrim( str_repeat( '%s,', count( $affiliatepress_common_auto_load_settings ) ), ',' );
            $affiliatepress_settings_key_placeholder .= ')';
            array_unshift( $affiliatepress_common_auto_load_settings, $affiliatepress_settings_key_placeholder );            
            $affiliatepress_where_clause = call_user_func_array( array( $wpdb, 'prepare' ), $affiliatepress_common_auto_load_settings );

            $affiliatepress_all_auto_load_settings = $wpdb->get_results( "SELECT ap_setting_name,ap_setting_value,ap_setting_type FROM {$affiliatepress_tbl_ap_settings_name} WHERE {$affiliatepress_where_clause} ORDER BY ap_setting_type ASC",ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_settings_name is table name defined globally. 
            $affiliatepress_all_settings_data = array();
            if($this->affiliatepress_check_affiliate_settings_table_exists()){
                $affiliatepress_all_settings_data['table_logs']['settings_table'] = 'true';
            }           
            if(!empty($affiliatepress_all_auto_load_settings)){
                foreach($affiliatepress_all_auto_load_settings as $affiliatepress_setting_data){
                    $affiliatepress_setting_name = (isset($affiliatepress_setting_data['ap_setting_name']))?$affiliatepress_setting_data['ap_setting_name']:'';
                    $affiliatepress_setting_value = (isset($affiliatepress_setting_data['ap_setting_value']))?$affiliatepress_setting_data['ap_setting_value']:'';
                    $affiliatepress_setting_type = (isset($affiliatepress_setting_data['ap_setting_type']))?$affiliatepress_setting_data['ap_setting_type']:'';
                    $affiliatepress_all_settings_data[$affiliatepress_setting_type][$affiliatepress_setting_name] = $affiliatepress_setting_value;
                }
            }            
            update_option('affiliatepress_auto_load_settings',$affiliatepress_all_settings_data);
            $affiliatepress_auto_load_settings = get_option('affiliatepress_auto_load_settings');
        }

        /**
         * Hide all admin notices when AffiliatePress page loads
         *
         * @return void
         */
        function affiliatepress_hide_admin_notices(){
            if (! empty($_GET['page']) && (sanitize_text_field($_GET['page']) == 'affiliatepress' ) ) { // phpcs:ignore
                remove_all_actions('network_admin_notices', 100);
                remove_all_actions('user_admin_notices', 100);
                remove_all_actions('admin_notices', 100);
                remove_all_actions('all_admin_notices', 100);
            }
        }        
        
        /**
         * Function for hide update notice
         *
         * @return void
        */
        function affiliatepress_hide_update_notice(){
            global $affiliatepress_slugs;
            if (isset($_REQUEST['page']) && in_array(sanitize_text_field($_REQUEST['page']), (array) $affiliatepress_slugs) ) { // phpcs:ignore
                remove_action('admin_notices', 'update_nag', 3);
                remove_action('network_admin_notices', 'update_nag', 3);
                remove_action('admin_notices', 'maintenance_nag');
                remove_action('network_admin_notices', 'maintenance_nag');
                remove_action('admin_notices', 'site_admin_notice');
                remove_action('network_admin_notices', 'site_admin_notice');                              
            }            
        }
        

        /**
         * Function for Fancy url rule
         *
         * @return void
         */
        function affiliatepress_add_fancy_url_rule(){
            global $AffiliatePress;

            $affiliatepress_url_parameter = $AffiliatePress->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');
            $affiliatepress_enable_fancy_affiliate_url = $AffiliatePress->affiliatepress_get_settings('enable_fancy_affiliate_url' , 'affiliate_settings');

            $affiliatepress_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ), 'objects' );
            foreach( $affiliatepress_taxonomies as $affiliatepress_tax_id => $affiliatepress_tax ) {
                if(!empty($affiliatepress_tax->rewrite) && !empty($affiliatepress_tax->rewrite['slug'])){
                    add_rewrite_rule( $affiliatepress_tax->rewrite['slug'] . '/(.+?)/' . $affiliatepress_url_parameter . '(/(.*))?/?$', 'index.php?' . $affiliatepress_tax_id . '=$matches[1]&' . $affiliatepress_url_parameter . '=$matches[3]', 'top');
                }
            }
            add_rewrite_endpoint( $affiliatepress_url_parameter, EP_ROOT | EP_PERMALINK | EP_PAGES | EP_CATEGORIES | EP_TAGS | EP_SEARCH | EP_ALL_ARCHIVES, false );
        }

        function affiliatepress_commission_type_priorities(){
            $affiliatepress_commission_type_priorities = array(
               'default' => 10,
            );

            $affiliatepress_commission_type_priorities = apply_filters('affiliatepress_set_commission_type_priorities', $affiliatepress_commission_type_priorities); 

            return $affiliatepress_commission_type_priorities;
        }
        
        /**
         * Function for flush or clear rules
         *
         * @return void
        */
        function affiliatepress_flush_rewrite_rules() {
           if(get_option('affiliatepress_flush_rewrites')){
                flush_rewrite_rules();
                delete_option( 'affiliatepress_flush_rewrites' );
            }
        }
        
        /**
         * Add category in frontend block
         *
         * @param  array $affiliatepress_category
         * @param  array $post
         * @return array
         */
        function affiliatepress_gutenberg_category( $affiliatepress_category, $post )
        {
            $affiliatepress_new_category     = array(
                array(
                    'slug'  => 'affiliatepress',
                    'title' => 'AffiliatePress Blocks',
                ),
            );
            $affiliatepress_final_categories = array_merge($affiliatepress_category, $affiliatepress_new_category);
            
            return $affiliatepress_final_categories;
        }

        /**
         * function for Add Gutenberg Blocks
         *
         * @return void
         */
        function affiliatepress_add_gutenbergblock() {
            register_block_type( AFFILIATEPRESS_DIR . '/js/build/signup-form' );                
            register_block_type( AFFILIATEPRESS_DIR . '/js/build/affiliate-panel' );   
        }
        
        /**
         * function for includ visual composer support for file
         *
         * @return void
         */
        function affiliatepress_check_plugin_dependencies() {
            if(!function_exists('is_plugin_active')){
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }            
            if (is_plugin_active('js_composer/js_composer.php') && file_exists(AFFILIATEPRESS_CORE_DIR . '/vc/affiliatepress_class_vc_extend.php')) {
                include_once AFFILIATEPRESS_CORE_DIR . '/vc/affiliatepress_class_vc_extend.php';
        
                global $affiliatepress_vcextend;
                $affiliatepress_vcextend = new AffiliatePress_VCExtend();
            }
        }
        
                
        /**
         * Function for return commissition data using order id & referance
         *
         * @param  mixed $affiliatepress_order_id
         * @param  mixed $affiliatepress_source
         * @return void
        */
        function affiliatepress_get_commission_by_order_and_source($affiliatepress_order_id = 0,$affiliatepress_source = ''){
            global $affiliatepress_tbl_ap_affiliate_commissions,$wpdb;

            $affiliatepress_tbl_ap_affiliate_commissions_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_commissions contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_commission_data = $wpdb->get_row($wpdb->prepare("SELECT ap_commission_id FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} Where ap_commission_source = %s  AND ap_commission_reference_id = %d",$affiliatepress_source,$affiliatepress_order_id),ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions is a table name. false alarm

            return $affiliatepress_commission_data;

        }

        /**
         * Function for return commissition data using order id & referance
         *
         * @param  mixed $affiliatepress_order_id
         * @param  mixed $affiliatepress_source
         * @return void
        */
        function affiliatepress_get_all_commission_by_order_and_source($affiliatepress_order_id = 0, $affiliatepress_source = '', $affiliatepress_commission_status_conditions = ''){
            global $affiliatepress_tbl_ap_affiliate_commissions,$wpdb;

            $affiliatepress_tbl_ap_affiliate_commissions_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_affiliate_commissions contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            if(empty($affiliatepress_commission_status_conditions)){
                $affiliatepress_commission_data = $wpdb->get_results($wpdb->prepare("SELECT ap_commission_id, ap_commission_status FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} Where ap_commission_source = %s  AND ap_commission_reference_id = %d AND ap_commission_status <> 4 ", $affiliatepress_source, $affiliatepress_order_id),ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions is a table name. false alarm

                if($affiliatepress_source == "download_manager"){
                    $affiliatepress_commission_data = $wpdb->get_results($wpdb->prepare("SELECT ap_commission_id, ap_commission_status FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} Where ap_commission_source = %s  AND ap_commission_reference_id = %s AND ap_commission_status <> 4 ", $affiliatepress_source, $affiliatepress_order_id),ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions is a table name. false alarm
                }
            }else{
                $affiliatepress_commission_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} Where ap_commission_source = %s  AND ap_commission_reference_id = %d AND ap_commission_status <> 4 {$affiliatepress_commission_status_conditions} ", $affiliatepress_source, $affiliatepress_order_id),ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions is a table name. false alarm

                if($affiliatepress_source == "download_manager"){
                    $affiliatepress_commission_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp} Where ap_commission_source = %s  AND ap_commission_reference_id = %s AND ap_commission_status <> 4 {$affiliatepress_commission_status_conditions} ", $affiliatepress_source, $affiliatepress_order_id),ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions is a table name. false alarm
                }
            }
            return $affiliatepress_commission_data;

        }        

        
        /**
         * Function for common for all integration commission customer add 
         *
         * @param  array $affiliatepress_args
         * @return integer
         */
        function affiliatepress_add_commission_customer($affiliatepress_args = array()){
            global $wpdb,$affiliatepress_tbl_ap_customer;
            $affiliatepress_customer_id   = 0;
            $affiliatepress_customer_data = array();
        
            $affiliatepress_affiliate_id  = ( ! empty( $affiliatepress_args['affiliate_id'] ) ? absint( $affiliatepress_args['affiliate_id'] ) : 0 );
        
            if (empty( $affiliatepress_args['email'])){
                return $affiliatepress_customer_id;
            }        
            /* Set up customer data. */
            if (!empty( $affiliatepress_args['first_name'] ) ) {
                $affiliatepress_customer_data['first_name'] = sanitize_text_field( $affiliatepress_args['first_name'] );
            }        
            if (!empty( $affiliatepress_args['last_name'] ) ) {
                $affiliatepress_customer_data['last_name'] = sanitize_text_field( $affiliatepress_args['last_name'] );
            }            
            $affiliatepress_user_id = (isset($affiliatepress_args['user_id']))?$affiliatepress_args['user_id']:0;
            $affiliatepress_email = (isset($affiliatepress_args['email']))?$affiliatepress_args['email']:'';

            $affiliatepress_tbl_ap_customer_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_customer); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_customer contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            if($affiliatepress_user_id == 0 || $affiliatepress_user_id == ''){                
                $affiliatepress_customer_id = intval($wpdb->get_var($wpdb->prepare("SELECT ap_customer_id FROM {$affiliatepress_tbl_ap_customer_temp} Where ap_customer_email = %s",$affiliatepress_email))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_customer_temp is a table name already prepare using above function affiliatepress_tablename_prepare. false alarm               
            }else{
                $affiliatepress_customer_id = intval($wpdb->get_var($wpdb->prepare("SELECT ap_customer_id FROM {$affiliatepress_tbl_ap_customer_temp} Where ap_customer_user_id = %d",$affiliatepress_user_id))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_customer_temp is a table name already prepare using above function affiliatepress_tablename_prepare. false alarm
                if($affiliatepress_customer_id == 0){
                    $affiliatepress_customer_id = intval($wpdb->get_var($wpdb->prepare("SELECT ap_customer_id FROM {$affiliatepress_tbl_ap_customer_temp} Where ap_customer_email = %s",$affiliatepress_email))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_customer_temp is a table name already prepare using above function affiliatepress_tablename_prepare. false alarm
                    if($affiliatepress_customer_id != 0){
                        $this->affiliatepress_update_record($affiliatepress_tbl_ap_customer, array('ap_customer_user_id'=>$affiliatepress_user_id), array( 'ap_customer_id' => $affiliatepress_customer_id ));
                    }
                }
            }
            if($affiliatepress_customer_id == 0){

                $affiliatepress_customer_arg = array(
                    'ap_customer_user_id'    => intval($affiliatepress_args['user_id']),
                    'ap_customer_email'      => $affiliatepress_email,
                    'ap_customer_first_name' => $affiliatepress_args['first_name'],
                    'ap_customer_last_name'  => $affiliatepress_args['last_name'],
                    'ap_affiliates_id'       => intval($affiliatepress_args['affiliate_id'])                               
                );                
                $affiliatepress_customer_id = $wpdb->insert($affiliatepress_tbl_ap_customer, $affiliatepress_customer_arg); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $affiliatepress_customer_id = $wpdb->insert_id;

            }
            return $affiliatepress_customer_id;
        }

        
        /**
         * Function for get ip address
         *
         * @return string
        */
        function affiliatepress_get_ip_address() {
            $affiliatepress_ipaddress = '';
            if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
                $affiliatepress_ipaddress = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']); //phpcs:ignore
            } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $affiliatepress_ipaddress = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']); //phpcs:ignore
            } else if (isset($_SERVER['HTTP_X_FORWARDED']) && !empty($_SERVER['HTTP_X_FORWARDED'])) {
                $affiliatepress_ipaddress = sanitize_text_field($_SERVER['HTTP_X_FORWARDED']); //phpcs:ignore
            } else if (isset($_SERVER['HTTP_FORWARDED_FOR']) && !empty($_SERVER['HTTP_FORWARDED_FOR'])) {
                $affiliatepress_ipaddress = sanitize_text_field($_SERVER['HTTP_FORWARDED_FOR']); //phpcs:ignore
            } else if (isset($_SERVER['HTTP_FORWARDED']) && !empty($_SERVER['HTTP_FORWARDED'])) {
                $affiliatepress_ipaddress = sanitize_text_field($_SERVER['HTTP_FORWARDED']); //phpcs:ignore
            } else if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
                $affiliatepress_ipaddress = sanitize_text_field($_SERVER['REMOTE_ADDR']); //phpcs:ignore
            } else {
                $affiliatepress_ipaddress = 'UNKNOWN';
            }
            /* For Public IP Address. */            
            return $affiliatepress_ipaddress;
        }        

        /**
         * Function for get date between months
         *
         * @param  datetime $affiliatepress_startDate
         * @param  datetime $affiliatepress_endDate
         * @return array
        */
        function affiliatepress_get_months_between($affiliatepress_startDate, $affiliatepress_endDate) {

            $affiliatepress_months = [];                        
            $affiliatepress_start = new DateTime($affiliatepress_startDate);
            $affiliatepress_end = new DateTime($affiliatepress_endDate);
            $affiliatepress_end->modify('first day of next month');                        
            $affiliatepress_interval = new DateInterval('P1M');
            $affiliatepress_period = new DatePeriod($affiliatepress_start, $affiliatepress_interval, $affiliatepress_end);
            foreach ($affiliatepress_period as $affiliatepress_date){                
                $affiliatepress_months[''.$affiliatepress_date->format('m-Y')] = $affiliatepress_date->format('M Y');
            }      
            return $affiliatepress_months;

        }

        /**
         * Function for get date between months
         *
         * @param  datetime $affiliatepress_startDate
         * @param  datetime $affiliatepress_endDate
         * @return array
        */
        function affiliatepress_get_date_between_year($affiliatepress_startDate, $affiliatepress_endDate){
            $affiliatepress_start = new DateTime($affiliatepress_startDate);
            $affiliatepress_end = new DateTime($affiliatepress_endDate);            
            if ($affiliatepress_end < $affiliatepress_start) {
                throw new InvalidArgumentException('End date must be after start date.');
            }
            $affiliatepress_yearsArray = [];            
            for ($affiliatepress_year = $affiliatepress_start->format('Y'); $affiliatepress_year <= $affiliatepress_end->format('Y'); $affiliatepress_year++) {
                $affiliatepress_yearsArray[$affiliatepress_year] = $affiliatepress_year;
            }        
            return $affiliatepress_yearsArray;            
        }        
        
        /**
         * Function for get all between date get
         *
         * @param  datetime $affiliatepress_startDate
         * @param  datetime $affiliatepress_endDate
         * @return array
        */
        function affiliatepress_get_dates_between($affiliatepress_startDate, $affiliatepress_endDate) {
            $affiliatepress_dates = [];
            $affiliatepress_start = new DateTime($affiliatepress_startDate);
            $affiliatepress_end = new DateTime($affiliatepress_endDate);        
            while ($affiliatepress_start <= $affiliatepress_end) {
                $affiliatepress_dates[$affiliatepress_start->format('Y-m-d')] = $affiliatepress_start->format('j M');
                $affiliatepress_start->modify('+1 day');
            }        
            return $affiliatepress_dates;
        }
        

        /**
         * Function for get country code by IP Address 
         *
         * @param  string $affiliatepress_logged_in_ip
         * @return string
        */

        function affiliatepress_get_country_from_ip($affiliatepress_logged_in_ip=""){ 
            if( '' == $affiliatepress_logged_in_ip ){
                return '';
            }
            $affiliatepress_country = array();
        
            try {
                require_once AFFILIATEPRESS_LIBRARY_DIR.'/ip2location/vendor/autoload.php';

                $ip = $affiliatepress_logged_in_ip; 
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $db_path = AFFILIATEPRESS_LIBRARY_DIR . '/ip2location/vendor/ip2location/ip2location-php/data/IP2LOCATION-LITE-DB1.IPV6.BIN';
                } else {
                    $db_path = AFFILIATEPRESS_LIBRARY_DIR . '/ip2location/vendor/ip2location/ip2location-php/data/IP2LOCATION-LITE-DB1.BIN';
                }
                $database = new \IP2Location\Database($db_path, \IP2Location\Database::FILE_IO);    

                $info = $database->lookup($ip, \IP2Location\Database::ALL);
                
                $affiliatepress_country = array(
                    'iso_code' => $info['countryCode'] ?? '',
                    'country_name' => $info['countryName'] ?? ''
                );
        
            } catch (\Exception $e) {
                error_log('GeoIP lookup error: ' . $e->getMessage());//phpcs:ignore
            }
        
            return $affiliatepress_country;
        }
        
        /**
         * Function for get user agent browser info
         *
         * @param  string $affiliatepress_user_agent
         * @return array
        */
        function affiliatepress_get_browser($affiliatepress_user_agent) {
            $affiliatepress_u_agent = $affiliatepress_user_agent;
            $affiliatepress_bname = 'Unknown';
            $affiliatepress_platform = 'Unknown';
            $affiliatepress_version = "";
            $affiliatepress_ub = "";
            /* First get the platform? */
            if (@preg_match('/linux/i', $affiliatepress_u_agent)) {
                $affiliatepress_platform = 'linux';
            } elseif (@preg_match('/macintosh|mac os x/i', $affiliatepress_u_agent)) {
                $affiliatepress_platform = 'mac';
            } elseif (@preg_match('/windows|win32/i', $affiliatepress_u_agent)) {
                $affiliatepress_platform = 'windows';
            }

            /* Next get the name of the useragent yes seperately and for good reason */
            if (@preg_match('/MSIE/i', $affiliatepress_u_agent) && !@preg_match('/Opera/i', $affiliatepress_u_agent)) {
                $affiliatepress_bname = 'Internet Explorer';
                $affiliatepress_ub = "MSIE";
            } elseif (@preg_match('/Firefox/i', $affiliatepress_u_agent)) {
                $affiliatepress_bname = 'Mozilla Firefox';
                $affiliatepress_ub = "Firefox";
            } elseif (@preg_match('/OPR/i', $affiliatepress_u_agent)) {
                $affiliatepress_bname = 'Opera';
                $affiliatepress_ub = "OPR";
            } elseif (@preg_match('/Edg/i', $affiliatepress_u_agent)) {
                $affiliatepress_bname = 'Microsoft Edge';
                $affiliatepress_ub = "Edg";
            } elseif (@preg_match('/Chrome/i', $affiliatepress_u_agent)) {
                $affiliatepress_bname = 'Google Chrome';
                $affiliatepress_ub = "Chrome";
            } elseif (@preg_match('/Safari/i', $affiliatepress_u_agent)) {
                $affiliatepress_bname = 'Apple Safari';
                $affiliatepress_ub = "Safari";
            } elseif (@preg_match('/Opera/i', $affiliatepress_u_agent)) {
                $affiliatepress_bname = 'Opera';
                $affiliatepress_ub = "Opera";
            } elseif (@preg_match('/Netscape/i', $affiliatepress_u_agent)) {
                $affiliatepress_bname = 'Netscape';
                $affiliatepress_ub = "Netscape";
            } elseif (@preg_match('/Trident/', $affiliatepress_u_agent)) {
                $affiliatepress_bname = 'Internet Explorer';
                $affiliatepress_ub = "rv";
            }
            /* finally get the correct version number */
            $affiliatepress_known = array('Version', $affiliatepress_ub, 'other');
            $affiliatepress_pattern = '#(?<browser>' . join('|', $affiliatepress_known) . ')[/ |:]+(?<version>[0-9.|a-zA-Z.]*)#';

            if (!@preg_match_all($affiliatepress_pattern, $affiliatepress_u_agent, $matches)) {
                /* we have no matching number just continue */
            }

            /* see how many we have */
            $affiliatepress_i = count($matches['browser']);
            if ($affiliatepress_i != 1) {
                /* we will have two since we are not using 'other' argument yet */
                /* see if version is before or after the name */
                if (strripos($affiliatepress_u_agent, "Version") < strripos($affiliatepress_u_agent, $affiliatepress_ub)) {
                    $affiliatepress_version = $matches['version'][0];
                } else {
                    $affiliatepress_version = $matches['version'][1];
                }
            } else {
                $affiliatepress_version = $matches['version'][0];
            }

            /* check if we have a number */
            if ($affiliatepress_version == null || $affiliatepress_version == "") {
                $affiliatepress_version = "?";
            }

            return array(
                'userAgent' => $affiliatepress_u_agent,
                'name' => $affiliatepress_bname,
                'version' => $affiliatepress_version,
                'platform' => $affiliatepress_platform,
                'pattern' => $affiliatepress_pattern
            );
        }

        
        /**
         * Function for add commission debug log
         *
         * @param  mixed $affiliatepress_commission_log_type
         * @param  mixed $affiliatepress_commission_log_event
         * @param  mixed $affiliatepress_commission_log_event_from
         * @param  mixed $affiliatepress_commission_log_raw_data
         * @param  mixed $affiliatepress_ref_id
         * @return integer
         */
        function affiliatepress_commission_debug_log_entry_func($affiliatepress_commission_log_type = '', $affiliatepress_commission_log_event = '', $affiliatepress_commission_log_event_from = '', $affiliatepress_commission_log_raw_data = '', $affiliatepress_ref_id = 0){

            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_commission_debug_logs, $affiliatepress_commission_debug_log_id, $affiliatepress_setting_logs_data;

            $affiliatepress_active_log = 'false';
            if(!isset($affiliatepress_setting_logs_data[$affiliatepress_commission_log_type])){
                $affiliatepress_setting_logs_data[$affiliatepress_commission_log_type] = $AffiliatePress->affiliatepress_get_settings($affiliatepress_commission_log_type, 'debug_log_settings');
                $affiliatepress_active_log = $affiliatepress_setting_logs_data[$affiliatepress_commission_log_type];
            }else{
                $affiliatepress_active_log = $affiliatepress_setting_logs_data[$affiliatepress_commission_log_type];
            }            
            $affiliatepress_inserted_id = 0;
            if($affiliatepress_active_log == 'true'){

            if(is_array($affiliatepress_commission_log_raw_data)){
                $affiliatepress_commission_log_raw_data['backtrace_summary'] = $affiliatepress_commission_log_type;
            }else{
                $affiliatepress_commission_log_raw_data .= " | Backtrace Summary ==> ".$affiliatepress_commission_log_type;
            }

                if ($affiliatepress_ref_id == null ) {
                    $affiliatepress_ref_id = 0;
                }
                $affiliatepress_database_log_data = array(
                    'ap_commission_log_ref_id'     => sanitize_text_field($affiliatepress_ref_id),
                    'ap_commission_log_type'       => sanitize_text_field($affiliatepress_commission_log_type),
                    'ap_commission_log_event'      => sanitize_text_field($affiliatepress_commission_log_event),
                    'ap_commission_log_event_from' => sanitize_text_field($affiliatepress_commission_log_event_from),
                    'ap_commission_log_raw_data'   => wp_json_encode(stripslashes_deep($affiliatepress_commission_log_raw_data)),
                    'ap_commission_log_added_date' => current_time('mysql'),
                );
                $wpdb->insert($affiliatepress_tbl_ap_commission_debug_logs, $affiliatepress_database_log_data);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery               
                $affiliatepress_inserted_id = $wpdb->insert_id;
                if(empty($affiliatepress_ref_id) ) {
                    $affiliatepress_ref_id = $affiliatepress_inserted_id;
                }
            }
            $affiliatepress_commission_debug_log_id = $affiliatepress_ref_id;            
            return $affiliatepress_inserted_id;

        }

        /**
         * Function for add payout debug log
         *
         * @param  mixed $affiliatepress_payout_type
         * @param  mixed $affiliatepress_payout_event
         * @param  mixed $affiliatepress_payout_event_from
         * @param  mixed $affiliatepress_payout_raw_data
         * @param  mixed $affiliatepress_ref_id
         * @return integer
         */
        function affiliatepress_payout_debug_log_entry_func($affiliatepress_payout_log_type = '', $affiliatepress_payout_log_event = '', $affiliatepress_payout_log_event_from = '', $affiliatepress_payout_log_raw_data = '', $affiliatepress_ref_id = 0){

            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_payout_debug_logs, $affiliatepress_payout_debug_log_id, $affiliatepress_setting_logs_data;

            $affiliatepress_active_log = 'false';
            if(!isset($affiliatepress_setting_logs_data[$affiliatepress_payout_log_type])){
                $affiliatepress_setting_logs_data[$affiliatepress_payout_log_type] = $AffiliatePress->affiliatepress_get_settings($affiliatepress_payout_log_type, 'debug_log_settings');
                $affiliatepress_active_log = $affiliatepress_setting_logs_data[$affiliatepress_payout_log_type];
            }else{
                $affiliatepress_active_log = $affiliatepress_setting_logs_data[$affiliatepress_payout_log_type];
            }            
            $affiliatepress_inserted_id = 0;
            if($affiliatepress_active_log == 'true'){
	            if(is_array($affiliatepress_payout_log_raw_data)){
	                $affiliatepress_payout_log_raw_data['backtrace_summary'] = $affiliatepress_payout_log_type;
	            }else{
	                $affiliatepress_payout_log_raw_data .= " | Backtrace Summary ==> ".$affiliatepress_payout_log_type;
	            }

                if ($affiliatepress_ref_id == null ) {
                    $affiliatepress_ref_id = 0;
                }
                $affiliatepress_database_log_data = array(
                    'ap_payout_log_ref_id'     => sanitize_text_field($affiliatepress_ref_id),
                    'ap_payout_log_type'       => sanitize_text_field($affiliatepress_payout_log_type),
                    'ap_payout_log_event'      => sanitize_text_field($affiliatepress_payout_log_event),
                    'ap_payout_log_event_from' => sanitize_text_field($affiliatepress_payout_log_event_from),
                    'ap_payout_log_raw_data'   => wp_json_encode(stripslashes_deep($affiliatepress_payout_log_raw_data)),
                    'ap_payout_log_added_date' => current_time('mysql'),
                );
                $wpdb->insert($affiliatepress_tbl_ap_payout_debug_logs, $affiliatepress_database_log_data); // phpcs:ignore WordPress.DB.DirectDatabaseQuery                
                $affiliatepress_inserted_id = $wpdb->insert_id;
                if(empty($affiliatepress_ref_id) ) {
                    $affiliatepress_ref_id = $affiliatepress_inserted_id;
                }
            }
            $affiliatepress_payout_debug_log_id = $affiliatepress_ref_id;            
            return $affiliatepress_inserted_id;

        }

        
        /**
         * Function for debug log
         *
         * @param  mixed $affiliatepress_other_log_type
         * @param  mixed $affiliatepress_other_log_event
         * @param  mixed $affiliatepress_other_log_event_from
         * @param  mixed $affiliatepress_other_log_raw_data
         * @param  mixed $affiliatepress_ref_id
         * @return Integer
         */
        function affiliatepress_other_debug_logs_func($affiliatepress_other_log_type = '', $affiliatepress_other_log_event = '', $affiliatepress_other_log_event_from = '', $affiliatepress_other_log_raw_data = '', $affiliatepress_ref_id = 0){
            
            global $wpdb, $AffiliatePress, $affiliatepress_other_debug_log_id, $affiliatepress_tbl_ap_other_debug_logs,$affiliatepress_setting_logs_data;

            $affiliatepress_active_log = 'false';
            if(!isset($affiliatepress_setting_logs_data[$affiliatepress_other_log_type])){
                $affiliatepress_setting_logs_data[$affiliatepress_other_log_type] = $AffiliatePress->affiliatepress_get_settings($affiliatepress_other_log_type, 'debug_log_settings');
                $affiliatepress_active_log = $affiliatepress_setting_logs_data[$affiliatepress_other_log_type];
            }else{
                $affiliatepress_active_log = $affiliatepress_setting_logs_data[$affiliatepress_other_log_type];
            }
            //$affiliatepress_active_log = 'true';
            $affiliatepress_inserted_id = 0;
            if($affiliatepress_active_log == 'true'){

	            if(is_array($affiliatepress_other_log_raw_data)){
	                $affiliatepress_other_log_raw_data['backtrace_summary'] = $affiliatepress_other_log_type;
	            }else{
	                $affiliatepress_other_log_raw_data .= " | Backtrace Summary ==> ".$affiliatepress_other_log_type;
	            }

                if ($affiliatepress_ref_id == null ) {
                    $affiliatepress_ref_id = 0;
                }
                $affiliatepress_database_log_data = array(
                    'ap_other_log_ref_id'     => sanitize_text_field($affiliatepress_ref_id),
                    'ap_other_log_type'       => sanitize_text_field($affiliatepress_other_log_type),
                    'ap_other_log_event'      => sanitize_text_field($affiliatepress_other_log_event),
                    'ap_other_log_event_from' => sanitize_text_field($affiliatepress_other_log_event_from),
                    'ap_other_log_raw_data'   => wp_json_encode(stripslashes_deep($affiliatepress_other_log_raw_data)),
                    'ap_other_log_added_date' => current_time('mysql'),
                );
                $wpdb->insert($affiliatepress_tbl_ap_other_debug_logs, $affiliatepress_database_log_data); // phpcs:ignore WordPress.DB.DirectDatabaseQuery               
                $affiliatepress_inserted_id = $wpdb->insert_id;
                if(empty($affiliatepress_ref_id) ) {
                    $affiliatepress_ref_id = $affiliatepress_inserted_id;
                }
            }
            $affiliatepress_other_debug_log_id = $affiliatepress_ref_id;
            return $affiliatepress_inserted_id;

        }
        
        /**
         * Function for affiliate formated date display
         *
         * @param  date $affiliatepress_date
         * @return date
        */
        function affiliatepress_formated_date_display($affiliatepress_date){
            $affiliatepress_common_date_format = get_option('date_format');
            if($affiliatepress_date){
                $affiliatepress_date = date($affiliatepress_common_date_format,strtotime($affiliatepress_date)); // phpcs:ignore
            }
            return $affiliatepress_date;
        }

        
        /**
         * Function for encode affiliate ID
         *
         * @param  integer $affiliatepress_affiliate_id
         * @return string
        */
        function affiliatepress_encode_affiliate_id($affiliatepress_affiliate_id){   
            
            global $AffiliatePress;
            $affiliatepress_default_link_type = $AffiliatePress->affiliatepress_get_settings('default_url_type', 'affiliate_settings');
            
            if($affiliatepress_affiliate_id){

                if($affiliatepress_default_link_type == 'affiliate_default_url'){
                    $affiliatepress_alphabet = "abcdefghijklmnopqrstuvwxyz12345679";
                    $random_index = wp_rand(0, strlen($affiliatepress_alphabet) - 1);
                    $random_alphabet = $affiliatepress_alphabet[$random_index];
                    $random_index = wp_rand(0, strlen($affiliatepress_alphabet) - 1);                                                                
                    $affiliatepress_encode_id = 'a'.base_convert($affiliatepress_affiliate_id,10,36).'9';
                    return $affiliatepress_encode_id;
                }
                elseif ($affiliatepress_default_link_type == "id") {
                    return $affiliatepress_affiliate_id;
                }
                elseif ($affiliatepress_default_link_type == "username") {
                    $affiliatepress_user_id = $this->affiliatepress_get_affiliate_user_id($affiliatepress_affiliate_id);
                    $affiliatepress_user_name = $this->affiliatepress_get_affiliate_username($affiliatepress_user_id,$affiliatepress_affiliate_id);
                    return $affiliatepress_user_name;
                }
                elseif ($affiliatepress_default_link_type == "md5") {
                    $affiliatepress_md5_id = md5($affiliatepress_affiliate_id);
                    return $affiliatepress_md5_id;
                }
                
            }else{
                return $affiliatepress_affiliate_id;
            }
        }

        /**
         * Function for decode affiliate ID
         *
         * @param  integer $affiliatepress_affiliate_id
         * @return integer
        */
        function affiliatepress_decode_affiliate_id($affiliatepress_affiliate_id){  
            
            global $wpdb,$AffiliatePress,$affiliatepress_tbl_ap_affiliates;
            $affiliatepress_default_link_type = $AffiliatePress->affiliatepress_get_settings('default_url_type', 'affiliate_settings');
            
            if($affiliatepress_affiliate_id){   
                if($affiliatepress_default_link_type == "affiliate_default_url"){
                    if(!empty($affiliatepress_affiliate_id)){
                        $affiliatepress_affiliate_id = substr($affiliatepress_affiliate_id, 1);
                    }                
                    if(!empty($affiliatepress_affiliate_id)){
                        $affiliatepress_affiliate_id = substr($affiliatepress_affiliate_id, 0, -1);   
                    }                                               
                    $affiliatepress_decode_id = base_convert($affiliatepress_affiliate_id,36,10);
                    return $affiliatepress_decode_id;                                 
                }
                elseif ($affiliatepress_default_link_type == "id"){
                    return $affiliatepress_affiliate_id;
                } 
                elseif ($affiliatepress_default_link_type == "username"){
                    $affiliatepress_user_data = get_user_by('login', $affiliatepress_affiliate_id);
                    if ($affiliatepress_user_data) {
                        $affiliatepress_user_id = $affiliatepress_user_data->ID;
                        $affiliatepress_affiliate_id = $this->affiliatepress_get_affiliate_id_by_userid($affiliatepress_user_id);

                        return $affiliatepress_affiliate_id;
                    }
                }
                elseif ($affiliatepress_default_link_type == "md5"){
                    $affiliatepress_actual_affiliate_id = $wpdb->get_var( $wpdb->prepare( "SELECT ap_affiliates_id as encoding_id FROM $affiliatepress_tbl_ap_affiliates WHERE md5(ap_affiliates_id) = %s ", $affiliatepress_affiliate_id ) ); //phpcs:ignore
                    return $affiliatepress_actual_affiliate_id;
                }
            }else{
                return $affiliatepress_affiliate_id;
            }
        }        

        
        /**
         * Function for affiliate custom link
         *
         * @param  integer $affiliatepress_affiliate_id
         * @param  string $affiliatepress_url
         * @return String
        */
        function affiliatepress_get_affiliate_custom_link($affiliatepress_affiliate_id,$affiliatepress_url = ''){
            $affiliatepress_affiliate_link = '';
            $affiliatepress_affiliate_id = $this->affiliatepress_encode_affiliate_id($affiliatepress_affiliate_id);
            $affiliatepress_url_parameter = $this->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');
            $affiliatepress_enable_fancy_affiliate_url = $this->affiliatepress_get_settings('enable_fancy_affiliate_url' , 'affiliate_settings');           
            if($affiliatepress_enable_fancy_affiliate_url == 'true'){
                if(empty($affiliatepress_url)){
                    $affiliatepress_url = site_url();
                }                
                $affiliatepress_url= parse_url( $affiliatepress_url );// phpcs:ignore
                $affiliatepress_query_string = array_key_exists( 'query', $affiliatepress_url ) ? '?' . $affiliatepress_url['query'] : '';
                $affiliatepress_url_scheme      = isset( $affiliatepress_url['scheme'] ) ? $affiliatepress_url['scheme'] : 'http';
                $affiliatepress_url_host        = isset( $affiliatepress_url['host'] ) ? $affiliatepress_url['host'] : '';
                $affiliatepress_url_path = isset($affiliatepress_url['path']) ? $affiliatepress_url['path'] : '';
                $affiliatepress_constructed_url = $affiliatepress_url_scheme . '://' . $affiliatepress_url_host . $affiliatepress_url_path;
                $affiliatepress_base_url = $affiliatepress_constructed_url;
                $affiliatepress_ref_url = trailingslashit( $affiliatepress_base_url ) . trailingslashit($affiliatepress_url_parameter) . trailingslashit($affiliatepress_affiliate_id) . $affiliatepress_query_string;
                $affiliatepress_affiliate_link = $affiliatepress_ref_url;                
            }else{
                if(empty($affiliatepress_url)){
                    $affiliatepress_url = site_url();
                }                           
                $affiliatepress_affiliate_link = add_query_arg( $affiliatepress_url_parameter,$affiliatepress_affiliate_id,$affiliatepress_url);                
            }

            return $affiliatepress_affiliate_link;
        }

        
        /**
         * affiliatepress_get_affiliate_common_link
         *
         * @param  integer $affiliatepress_affiliate_id
         * @param  string $affiliatepress_url
         * @return string
         */
        function affiliatepress_get_affiliate_common_link($affiliatepress_affiliate_id,$affiliatepress_url = ''){
            $affiliatepress_affiliate_link = '';
            $affiliatepress_affiliate_id = $this->affiliatepress_encode_affiliate_id($affiliatepress_affiliate_id);
            $affiliatepress_url_parameter = $this->affiliatepress_get_settings('affiliate_url_parameter', 'affiliate_settings');
            $affiliatepress_enable_fancy_affiliate_url = $this->affiliatepress_get_settings('enable_fancy_affiliate_url' , 'affiliate_settings');           
            if($affiliatepress_enable_fancy_affiliate_url == 'true'){
                $affiliatepress_url = site_url();
                $affiliatepress_url= parse_url( $affiliatepress_url );// phpcs:ignore
                $affiliatepress_query_string = array_key_exists( 'query', $affiliatepress_url ) ? '?' . $affiliatepress_url['query'] : '';
                $affiliatepress_url_scheme      = isset( $affiliatepress_url['scheme'] ) ? $affiliatepress_url['scheme'] : 'http';
                $affiliatepress_url_host        = isset( $affiliatepress_url['host'] ) ? $affiliatepress_url['host'] : '';
                $affiliatepress_url_path = isset($affiliatepress_url['path']) ? $affiliatepress_url['path'] : '';
                $affiliatepress_constructed_url = $affiliatepress_url_scheme . '://' . $affiliatepress_url_host . $affiliatepress_url_path;
                $affiliatepress_base_url = $affiliatepress_constructed_url;
                $affiliatepress_ref_url = trailingslashit( $affiliatepress_base_url ) . trailingslashit($affiliatepress_url_parameter) . trailingslashit($affiliatepress_affiliate_id) . $affiliatepress_query_string;
                $affiliatepress_affiliate_link = $affiliatepress_ref_url;                
            }else{
                $affiliatepress_affiliate_link = site_url().'?'.$affiliatepress_url_parameter.'='.$affiliatepress_affiliate_id;
            }

            return $affiliatepress_affiliate_link;
        }


        
                
        /**
         * Function for check affiliate user email
         *
         * @param  integer $affiliatepress_affiliate_id
         * @param  string $affiliatepress_commission_user_email
         * @return boolean
         */
        function affiliatepress_affiliate_has_email($affiliatepress_affiliate_id, $affiliatepress_commission_user_email){
            $affiliatepress_affiliate_has_email = false;
            $affiliatepress_affiliate_user_email = $this->affiliatepress_get_affiliate_user_email_by_affiliate_id($affiliatepress_affiliate_id);
            if($affiliatepress_affiliate_user_email == $affiliatepress_commission_user_email){
                $affiliatepress_affiliate_has_email = true;
            }
            return $affiliatepress_affiliate_has_email;
        }

        /**
         * Function for get affiliate user id
         *
         * @param  integer $affiliatepress_affiliate_id
         * @return string
        */
        function affiliatepress_get_affiliate_user_email_by_affiliate_id($affiliatepress_affiliate_id = ''){
            global $affiliatepress_tbl_ap_affiliates;            
            $affiliatepress_user_email = "";
            if($affiliatepress_affiliate_id){
                $affiliatepress_user_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_id', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliate_id ), '', '', '', true, false, ARRAY_A);
                if($affiliatepress_user_id){
                    $affiliatepress_user = get_user_by('ID', $affiliatepress_user_id);
                    $affiliatepress_user_email = $affiliatepress_user->user_email;                    
                }
            }
            return $affiliatepress_user_email;
        }        

        /**
         * Function for get affiliate user id
         *
         * @param  integer $affiliatepress_affiliate_id
         * @return string
        */
        function affiliatepress_get_affiliate_user_avatar_image_by_user_id($affiliatepress_affiliate_user_id = ''){
            global $affiliatepress_tbl_ap_affiliates;
            $affiliatepress_affiliates_user_avatar = '';
            if($affiliatepress_affiliate_user_id){
                $affiliatepress_affiliates_user_avatar = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_avatar', 'WHERE ap_affiliates_user_id = %d', array( $affiliatepress_affiliate_user_id ), '', '', '', true, false,ARRAY_A);
            }
            return $affiliatepress_affiliates_user_avatar;
        }


        /**
         * Function for get affiliate user id
         *
         * @param  mixed $affiliatepress_affiliate_id
         * @return integer
        */
        function affiliatepress_get_affiliate_user_id($affiliatepress_affiliate_id = ''){
            global $affiliatepress_tbl_ap_affiliates;
            $affiliatepress_user_id = 0;
            if($affiliatepress_affiliate_id){
                $affiliatepress_user_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_id', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliate_id ), '', '', '', true, false,ARRAY_A);
            }
            return $affiliatepress_user_id;
        }

        /**
         * Function for get user name by User ID
         *
         * @param  integer $affiliatepress_user_id
         * @return string
        */
        function affiliatepress_get_affiliate_user_name_by_id($affiliatepress_user_id = '',$affiliatepress_affiliate_id = ''){
            $affiliatepress_affiliate_user_name = '';  
            global $affiliatepress_tbl_ap_affiliates;  
            if(empty($affiliatepress_user_id) && !empty($affiliatepress_affiliate_id)){
                $affiliatepress_user_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_id', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliate_id ), '', '', '', true, false,ARRAY_A);
            }            
            if($affiliatepress_user_id){                
                $affiliatepress_user_info = get_userdata($affiliatepress_user_id);
                $affiliatepress_first_name = $affiliatepress_user_info->first_name;
                $affiliatepress_last_name = $affiliatepress_user_info->last_name;
                $affiliatepress_display_name = $affiliatepress_user_info->display_name;
                if(!empty($affiliatepress_display_name)){
                    return $affiliatepress_display_name;
                }else{
                    if(!empty($affiliatepress_first_name) && !empty($affiliatepress_last_name)){
                        $affiliatepress_affiliate_user_name = $affiliatepress_first_name.' '.$affiliatepress_last_name;    
                    }
                }
            }            
            return $affiliatepress_affiliate_user_name;
        }

         /**
         * Function for get username by User ID
         *
         * @param  integer $affiliatepress_user_id
         * @return string
        */
        function affiliatepress_get_affiliate_username($affiliatepress_user_id = '',$affiliatepress_affiliate_id = ''){
            $affiliatepress_affiliate_user_name = '';  
            global $affiliatepress_tbl_ap_affiliates;  
            if(empty($affiliatepress_user_id) && !empty($affiliatepress_affiliate_id)){
                $affiliatepress_user_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_id', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliate_id ), '', '', '', true, false,ARRAY_A);
            }            
            if($affiliatepress_user_id){                
                $affiliatepress_user_info = get_userdata($affiliatepress_user_id);
                if ($affiliatepress_user_info) {
                    $affiliatepress_username = $affiliatepress_user_info->user_login;
                    $affiliatepress_affiliate_user_name =  $affiliatepress_username;
                }
            }            
            return $affiliatepress_affiliate_user_name;
        }

        /**
         * Function for get affiliate user id
         *
         * @param  mixed $affiliatepress_affiliate_id
         * @return integer
        */
        function affiliatepress_get_affiliate_id_by_userid($affiliatepress_user_id = ''){
            global $affiliatepress_tbl_ap_affiliates;
            $affiliatepress_affiliate_id = 0;
            if($affiliatepress_user_id){
                $affiliatepress_affiliate_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_id', 'WHERE ap_affiliates_user_id = %d', array( $affiliatepress_user_id ), '', '', '', true, false,ARRAY_A);
            }
            return $affiliatepress_affiliate_id;
        }

        /**
         * Function for get user name by User ID
         *
         * @param  integer $affiliatepress_user_id
         * @return string
        */
        function affiliatepress_get_affiliate_user_first_name_by_id($affiliatepress_user_id = '',$affiliatepress_affiliate_id = ''){
            $affiliatepress_affiliate_user_first_name = '';  
            global $affiliatepress_tbl_ap_affiliates;  
            if(empty($affiliatepress_user_id) && !empty($affiliatepress_affiliate_id)){
                $affiliatepress_user_id = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliates, 'ap_affiliates_user_id', 'WHERE ap_affiliates_id = %d', array( $affiliatepress_affiliate_id ), '', '', '', true, false,ARRAY_A);
            }            
            if($affiliatepress_user_id){                
                $affiliatepress_user_info = get_userdata($affiliatepress_user_id);
                $affiliatepress_affiliate_user_first_name = $affiliatepress_user_info->first_name;
                
            }            
            return $affiliatepress_affiliate_user_first_name;
        }

        
        /**
         * Function for get all wordpress pages list
         *
         * @return array
        */
        function affiliatepress_get_all_wp_pages(){			
			$affiliatepress_new_wp_pages = array();
			$affiliatepress_wp_pages = get_pages();
			if(!empty($affiliatepress_wp_pages)){
				foreach($affiliatepress_wp_pages as $affiliatepress_wp_page_key => $affiliatepress_wp_page_val){
					$affiliatepress_new_wp_pages[] = array(
						'id' => $affiliatepress_wp_page_val->ID,
						'title' => $affiliatepress_wp_page_val->post_title,
						'url' => get_permalink(get_page_by_path($affiliatepress_wp_page_val->post_name)),
					);
				}
			}
            return $affiliatepress_new_wp_pages;
        }


        /**
         * Get specific setting value.
         *
         * @param  string $affiliatepress_setting_name
         * @param  string $affiliatepress_setting_type
         * 
         * @return string
        */
        public function affiliatepress_get_settings( $affiliatepress_setting_name, $affiliatepress_setting_type ){
            global $wpdb, $affiliatepress_tbl_ap_settings,$affiliatepress_get_setting_data,$affiliatepress_auto_load_settings,$affiliatepress_has_affiliate_settings_table;
            if(!$affiliatepress_has_affiliate_settings_table){
                $affiliatepress_has_affiliate_settings_table = $this->affiliatepress_check_affiliate_settings_table_exists();
                return '';
            }

            $affiliatepress_tbl_ap_settings_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_settings); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason - $affiliatepress_tbl_ap_settings contains table name and it's prepare properly using 'affiliatepress_tablename_prepare' function

            $affiliatepress_setting_value = '';
            if (! empty($affiliatepress_setting_name) ) {
                if(!empty($affiliatepress_auto_load_settings) && is_array($affiliatepress_auto_load_settings) && isset($affiliatepress_auto_load_settings[$affiliatepress_setting_type][$affiliatepress_setting_name])){
                     return $affiliatepress_auto_load_settings[$affiliatepress_setting_type][$affiliatepress_setting_name];
                }
                if(isset($affiliatepress_get_setting_data[$affiliatepress_setting_name])){
                    return $affiliatepress_get_setting_data[$affiliatepress_setting_name];
                }
                if ( false !== wp_cache_get($affiliatepress_setting_name) ) {
                    $affiliatepress_setting_value = wp_cache_get($affiliatepress_setting_name);
                    $affiliatepress_get_setting_data[$affiliatepress_setting_name] = $affiliatepress_setting_value;
                } else {
                    $affiliatepress_get_setting   = $wpdb->get_row($wpdb->prepare("SELECT ap_setting_value FROM {$affiliatepress_tbl_ap_settings_temp} WHERE ap_setting_name = %s AND ap_setting_type = %s", $affiliatepress_setting_name, $affiliatepress_setting_type), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_settings_temp is table name already prepare by affiliatepress_tablename_prepare function. False Positive alarm
                    if( !empty( $affiliatepress_get_setting ) ){
                        $affiliatepress_setting_value = $affiliatepress_get_setting['ap_setting_value'];
                        $affiliatepress_get_setting_data[$affiliatepress_setting_name] = $affiliatepress_setting_value;
                    }
                    wp_cache_set($affiliatepress_setting_name, $affiliatepress_setting_value);
                }
            }
            return $affiliatepress_setting_value;
        }


        /**
         * Insert or Update AffiliatePress settings value
         *
         * @param  string $affiliatepress_setting_name
         * @param  string $affiliatepress_setting_type
         * @param  string $affiliatepress_setting_value
         * @return integer
         */
        public function affiliatepress_update_settings( $affiliatepress_setting_name, $affiliatepress_setting_type, $affiliatepress_setting_value = '' ){
            global $wpdb, $affiliatepress_tbl_ap_settings;
            if (! empty($affiliatepress_setting_name) ) {
                $affiliatepress_tbl_ap_settings_temp = $this->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_settings);
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_settings is table name defined globally. False Positive alarm
                $affiliatepress_check_record_existance = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ap_setting_id) FROM {$affiliatepress_tbl_ap_settings_temp} WHERE ap_setting_name = %s AND ap_setting_type = %s", $affiliatepress_setting_name, $affiliatepress_setting_type)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_tbl_ap_settings_temp is prepare above using affiliatepress_tablename_prepare function. False Positive alarm
                if ($affiliatepress_check_record_existance > 0 ) {
                    // If record already exists then update data.
                    $affiliatepress_update_data = array(
                        'ap_setting_value' => ( ! empty($affiliatepress_setting_value) && (gettype($affiliatepress_setting_value) === 'boolean' || $affiliatepress_setting_name == 'smtp_password') ) ? $affiliatepress_setting_value : sanitize_text_field($affiliatepress_setting_value),
                        'ap_setting_type'  => $affiliatepress_setting_type,
                        'ap_settings_updated_at'    => current_time('mysql'),
                    );
                    $affiliatepress_update_where_condition = array(
                     'ap_setting_name' => $affiliatepress_setting_name,
                     'ap_setting_type' => $affiliatepress_setting_type,
                    );
                    $affiliatepress_update_affected_rows = $wpdb->update($affiliatepress_tbl_ap_settings, $affiliatepress_update_data, $affiliatepress_update_where_condition); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    if ($affiliatepress_update_affected_rows > 0 ) {
                        wp_cache_delete($affiliatepress_setting_name);
                        wp_cache_set($affiliatepress_setting_name, $affiliatepress_setting_value);
                        return 1;
                    }
                } else {
                    /* If record not exists hen insert data. */
                    $affiliatepress_insert_data = array(
                        'ap_setting_name'  => $affiliatepress_setting_name,
                        'ap_setting_value' => ( ! empty($affiliatepress_setting_value) && (gettype($affiliatepress_setting_value) === 'boolean' || $affiliatepress_setting_name == 'smtp_password') ) ? $affiliatepress_setting_value : sanitize_text_field($affiliatepress_setting_value),
                        'ap_setting_type'  => $affiliatepress_setting_type,
                        'ap_settings_updated_at'    => current_time('mysql'),
                    );
                    $affiliatepress_inserted_id = $wpdb->insert($affiliatepress_tbl_ap_settings, $affiliatepress_insert_data); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    if ($affiliatepress_inserted_id > 0 ) {
                        wp_cache_delete($affiliatepress_setting_name);
                        wp_cache_set($affiliatepress_setting_name, $affiliatepress_setting_value);
                        return 1;
                    }
                }
            }

            return 0;
        }

        
        /**
         * Function for sanatize array all integer fields
         *
         * @param  array $affiliatepress_data_array
         * @return mixed
         */
        function affiliatepress_array_sanatize_integer_field( $affiliatepress_data_array ){
            if( null == $affiliatepress_data_array ){
                return $affiliatepress_data_array;
            }
            if (is_array($affiliatepress_data_array) ) {
                return array_map(array( $this, __FUNCTION__ ), $affiliatepress_data_array);
            } else {                
                return intval($affiliatepress_data_array);                
            }
        }         


        /**
         * Function for sanatize array all fields
         *
         * @param  array $affiliatepress_data_array
         * @return mixed
         */
        function affiliatepress_array_sanatize_field( $affiliatepress_data_array ){
            if( null == $affiliatepress_data_array ){
                return $affiliatepress_data_array;
            }
            if (is_array($affiliatepress_data_array) ) {
                return array_map(array( $this, __FUNCTION__ ), $affiliatepress_data_array);
            } else {
                if(preg_match( '/<[^<]+>/', $affiliatepress_data_array ) ) {
                    global $affiliatepress_global_options;                     
                    $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
                    $affiliatepress_allow_tag = json_decode($affiliatepress_global_options_data['allowed_html'], true);
			        return wp_kses( $affiliatepress_data_array, $affiliatepress_allow_tag );
                } else {
                    return sanitize_text_field($affiliatepress_data_array);
                }
            }
        }  

        /**
         * Function for add admin page vue js script
         *
         * @return void
        */
        function affiliatepress_admin_print_scripts_func(){

            global $AffiliatePress;

            $requested_module = ! empty($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : 'dashboard'; // phpcs:ignore 
            if (!empty($requested_module) && (strpos($requested_module, 'affiliatepress_') !== false || $requested_module == 'affiliatepress' )) {

                global $AffiliatePress,$affiliatepress_common_date_format, $affiliatepress_website_url;
                $requested_module = ( ! empty($_REQUEST['page']) && ( $_REQUEST['page'] != 'affiliatepress' ) ) ? sanitize_text_field(str_replace('affiliatepress_', '', sanitize_text_field($_REQUEST['page']))) : 'dashboard'; // phpcs:ignore 
                $affiliatepress_start_of_week = get_option('start_of_week');
                
                $affiliatepress_front_booking_dynamic_helper_vars = '';
                $affiliatepress_front_booking_dynamic_helper_vars = apply_filters('affiliatepress_' . $requested_module . '_dynamic_helper_vars', $affiliatepress_front_booking_dynamic_helper_vars);
                
                $affiliatepress_dynamic_data_fields = '';
                $affiliatepress_dynamic_data_fields = apply_filters('affiliatepress_' . $requested_module . '_dynamic_data_fields', $affiliatepress_dynamic_data_fields);

                $affiliatepress_dynamic_constant_define = '';
                $affiliatepress_dynamic_constant_define = apply_filters('affiliatepress_' . $requested_module . '_dynamic_constant_define', $affiliatepress_dynamic_constant_define);

                $affiliatepress_all_dynamic_constant_define = '';
                $affiliatepress_all_dynamic_constant_define = apply_filters('affiliatepress_all_dynamic_constant_define', $affiliatepress_all_dynamic_constant_define);

                $affiliatepress_dynamic_components = '';
                $affiliatepress_dynamic_components = apply_filters('affiliatepress_' . $requested_module . '_dynamic_components', $affiliatepress_dynamic_components);                

                $affiliatepress_dynamic_on_load_methods = '';
                $affiliatepress_dynamic_on_load_methods = apply_filters('affiliatepress_' . $requested_module . '_dynamic_on_load_methods', $affiliatepress_dynamic_on_load_methods);                   

                $affiliatepress_dynamic_vue_methods = '';
                $affiliatepress_dynamic_vue_methods = apply_filters('affiliatepress_' . $requested_module . '_dynamic_vue_methods', $affiliatepress_dynamic_vue_methods); 

                $affiliatepress_all_dynamic_vue_methods = '';
                $affiliatepress_all_dynamic_vue_methods = apply_filters('affiliatepress_all_dynamic_vue_methods', $affiliatepress_all_dynamic_vue_methods); 

                $affiliatepress_dynamic_computed_methods = '';
                $affiliatepress_dynamic_computed_methods = apply_filters('affiliatepress_' . $requested_module . '_dynamic_computed_methods', $affiliatepress_dynamic_computed_methods);                 


                $affiliatepress_change_help_request_module_data = '';
                $affiliatepress_change_help_request_module_data = apply_filters('affiliatepress_change_help_request_module_data', $affiliatepress_change_help_request_module_data);     
                
                $affiliatepress_additional_menu_help_content = '';
                $affiliatepress_additional_menu_help_content = apply_filters('affiliatepress_additional_menu_help_content', $affiliatepress_additional_menu_help_content);

                $affiliatepress_additional_settings_tab_help_content = '';
                $affiliatepress_additional_settings_tab_help_content = apply_filters('affiliatepress_additional_settings_tab_help_content', $affiliatepress_additional_settings_tab_help_content);    

                $affiliatepress_selected_tab_name  = !empty($_REQUEST['setting_page']) ? sanitize_text_field($_REQUEST['setting_page']) : ''; // phpcs:ignore

                ob_start();
                $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/manage_language.php';                            
                include $affiliatepress_load_file_name;            
                $affiliatepress_localization_data_content = ob_get_clean(); 
                
                $affiliatepress_layout = (is_rtl())?esc_html("ltr"):esc_html("rtl");
                $affiliatepress_script_return_data  = '';
                $affiliatepress_script_return_data .= $affiliatepress_localization_data_content;

                if(!empty($affiliatepress_selected_tab_name)){
                    $affiliatepress_script_return_data .= 'sessionStorage.setItem("current_affiliatpress_tabname", "'.esc_html($affiliatepress_selected_tab_name).'");';
                }
                $affiliatepress_selected_optins_name  = !empty($_REQUEST['optins_setting']) ? sanitize_text_field($_REQUEST['optins_setting']) : ''; // phpcs:ignore
                if(!empty($affiliatepress_selected_optins_name)){
                    $affiliatepress_script_return_data .= 'sessionStorage.setItem("selected_optins", "'.esc_html($affiliatepress_selected_optins_name).'");';
                }

                $affiliatepress_is_rtl = "";
                if(is_rtl()){
                    $affiliatepress_is_rtl = "is_rtl";
                }

                $affiliatepress_is_pro_active = $AffiliatePress->affiliatepress_pro_install();

                $affiliatepress_script_return_data .= '                
                var app;
                    function affiliatepress_admin_vue_data_load(){                    
                    const { ref, createApp, reactive, onBeforeMount, onMounted,mounted} = Vue;    
                    app = createApp({                    
                    components: {'.$affiliatepress_dynamic_components.'},
                    data(){
                        var affiliatepress_requested_module = "'.esc_html($requested_module).'";
                        var affiliatepress_start_of_week = "'.esc_html($affiliatepress_start_of_week).'";
                        '.$affiliatepress_front_booking_dynamic_helper_vars.'
                        var affiliatepress_return_data = '.$affiliatepress_dynamic_data_fields.'; 
                          
                        if(affiliatepress_requested_module == "affiliates"){
                            if (affiliatepress_return_data.rules) {
                                affiliatepress_return_data.rules.confirm_password = [
                                    { required: true, message: affiliatepress_return_data.confirm_password_field.confirm_password_error_msg, trigger: "blur" },
                                    { validator: this.validateConfirmPassword, trigger: "blur" }
                                ];
                            }

                            if (affiliatepress_return_data.rules && affiliatepress_return_data.rules.password) {
                                affiliatepress_return_data.rules.password.push({
                                    validator: this.validatePassword,
                                    trigger: ["blur", "change"]
                                });
                            }
                        }
                        //affiliatepress_return_data["ap_common_date_format"] = "YYYY-MM-DD";
                        affiliatepress_return_data["ap_common_date_format"] = "'.esc_html($affiliatepress_common_date_format).'";                        
                        affiliatepress_return_data["drawer_direction"] = "'.$affiliatepress_layout.'";
                        affiliatepress_return_data["affiliatepress_selected_tab_name"] = "'.$affiliatepress_selected_tab_name.'";
                        affiliatepress_return_data["affiliatepress_start_date"] = "'.esc_html__('Start Date', 'affiliatepress-affiliate-marketing').'";
                        affiliatepress_return_data["affiliatepress_end_date"] = "'.esc_html__('End Date', 'affiliatepress-affiliate-marketing').'";
                        affiliatepress_return_data["is_rtl"] = "'.$affiliatepress_is_rtl.'";

                        affiliatepress_return_data["ap_first_page_loaded"] = "1";
                        affiliatepress_return_data["current_screen_size"] = "desktop";
                        affiliatepress_return_data["current_grid_screen_size"] = "desktop";
                         affiliatepress_return_data["is_pro_active"] = "'.$affiliatepress_is_pro_active.'";

                        var today = new Date();                         
                        const dayOfWeek = today.getDay(); 

                        const Last30DaysendDate = new Date();
                        const Last30DaysstartDate = new Date();
                        Last30DaysstartDate.setDate(Last30DaysendDate.getDate() - 30);

                        const yesterday = new Date(today);
                        yesterday.setDate(today.getDate() - 1);

                        const startDateWeek = new Date(today);
                        startDateWeek.setDate(today.getDate() - dayOfWeek); 
                        const endDateWeek = new Date(startDateWeek);
                        endDateWeek.setDate(startDateWeek.getDate() + 6);

                        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                        const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                        const lastWeekStart = new Date(today.setDate(today.getDate() - dayOfWeek - 1));                    
                        const lastWeekEnd = new Date(today.setDate(lastWeekStart.getDate() - 6));                                
                        today = new Date();
                        const startOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        const endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0); // Last day of the last month
                        const startOfThreeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 3, 1);
                        const endOfLastThreeeMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                        const currentDate = new Date();
                        const startDate = new Date(today);
                        startDate.setMonth(currentDate.getMonth() - 6);
                        const startOfLastsixMonth = startDate.setDate(1);
                        const endDate = new Date();
                        const endOfLastsixMonth = endDate.setDate(0);                                
                        const startDateNew = new Date(currentDate);
                        startDateNew.setFullYear(currentDate.getFullYear() - 1);
                        startDateNew.setMonth(1 - 1); 
                        const startOfLastyear = startDateNew.setDate(1);
                        const endDatenew = new Date(startDateNew);
                        const endOfLastyear = endDatenew.setFullYear(startDateNew.getFullYear(), startDateNew.getMonth() + 12, 0); 

                        const shortcuts = [
                            { text: "'.esc_html__("Today", "affiliatepress-affiliate-marketing").'",
                                value: () => {
                                    return [today, today]
                                },
                            },
                            { text: "'.esc_html__("Yesterday", "affiliatepress-affiliate-marketing").'",
                                value: () => {
                                    return [yesterday, yesterday]
                                },
                            },                        
                            { text: "'.esc_html__("This Week", "affiliatepress-affiliate-marketing").'",
                                value: () => {
                                    return [startDateWeek, endDateWeek]
                                },
                            },
                            { text: "'.esc_html__("Last Week", "affiliatepress-affiliate-marketing").'",
                            value: () => {
                                return [lastWeekEnd, lastWeekStart]
                            },
                            },
                            {
                                text: "'.esc_html__('Last Month', "affiliatepress-affiliate-marketing").'",
                                value: () => {
                                    return [startOfLastMonth, endOfLastMonth]
                                },
                            },
                            {
                                text: "'.esc_html__('This Month', "affiliatepress-affiliate-marketing").'",
                                value: () => {
                                    return [startOfMonth, endOfMonth]
                                },
                            }, 
                            {
                                text: "'.esc_html__('30 Days', "affiliatepress-affiliate-marketing").'",
                                value: () => {
                                    return [Last30DaysstartDate, Last30DaysendDate]
                                },
                            },                                               
                            {
                            text: "'.esc_html__('3 Months', 'affiliatepress-affiliate-marketing').'",
                            value: () => {
                                return [startOfThreeMonthsAgo, endOfLastThreeeMonth]
                            },
                            
                            },
                            {
                                text: "'.esc_html__('6 Months', 'affiliatepress-affiliate-marketing').'",
                                value: () => {
                                    return [startOfLastsixMonth, endOfLastsixMonth]
                                },                                    
                            },
                            {
                                text: "'.esc_html__('Last Year', 'affiliatepress-affiliate-marketing').'",
                                value: () => {
                                    return [startOfLastyear, endOfLastyear]
                                },                                    
                            },                                                                        
                        ];
                        affiliatepress_return_data["shortcuts"] = shortcuts;

                        affiliatepress_return_data["affiliatepress_fab_floating_btn"] = "0";

                        const needHelpDrawer = ref(false);                        
                        affiliatepress_return_data["needHelpDrawer"] = needHelpDrawer;
                        affiliatepress_return_data["needHelpDrawerDirection"] = "rtl";
                        affiliatepress_return_data["helpDrawerData"] = "";                      

                        const affiliatepress_premium_modal = ref(false);
                        affiliatepress_return_data["affiliatepress_premium_modal"] = affiliatepress_premium_modal;

                        const affiliatepress_sale_premium_modal = ref(false);
                        affiliatepress_return_data["affiliatepress_sale_premium_modal"] = affiliatepress_sale_premium_modal;

                        '.$affiliatepress_dynamic_constant_define.'
                        '.$affiliatepress_all_dynamic_constant_define.'

                        affiliatepress_return_data["ap_optin_active_tab"] = "'.$affiliatepress_selected_optins_name.'";
                        
                        return affiliatepress_return_data;
                    },
                    mounted(){
                        if(window.screen.width > 1600){
                            this.current_grid_screen_size = this.current_screen_size = "desktop";
                        }else if(window.screen.width <= 1600 && window.screen.width >= 1200){
                            this.current_screen_size = "desktop";
                            this.current_grid_screen_size = "tablet";
                        }else if(window.screen.width < 1200 && window.screen.width >= 768){
                            this.current_grid_screen_size = this.current_screen_size = "tablet";
                        }else if(window.screen.width < 768){
                            this.current_grid_screen_size = this.current_screen_size = "mobile";
                        }
                        window.addEventListener("resize", function(event){                            
                            if(window.screen.width > 1600){
                                this.current_grid_screen_size = this.current_screen_size = "desktop";
                            }else if(window.screen.width <= 1600 && window.screen.width >= 1200){
                                this.current_screen_size = "desktop";
                                this.current_grid_screen_size = "tablet";
                            }else if(window.screen.width < 1200 && window.screen.width >= 768){
                                this.current_grid_screen_size = this.current_screen_size = "tablet";
                            }else if(window.screen.width < 768){
                                this.current_grid_screen_size = this.current_screen_size = "mobile";
                            }
                        });
                        document.onreadystatechange = () => { 
                            vm.ap_first_page_loaded = "0";
                        }                                
                        '.$affiliatepress_dynamic_on_load_methods.'
                    },
                    methods:{
                        affiliatepress_redirect_premium_page(){
                            const vm = this;
                            window.open("'.$affiliatepress_website_url.'pricing/?utm_source=liteversion&utm_medium=plugin&utm_campaign=Upgrade+to+Premium&utm_id=affiliatepress_2");
                        },
                        affiliatepress_redirect_website(){
                            const vm = this;
                            window.open("'.$affiliatepress_website_url.'");
                        },
                        affiliatepress_redirect_lite_vs_preminum_page(){
                            const vm = this;
                            window.open("'.$affiliatepress_website_url.'affiliate-lite-vs-pro/");
                        },      
                        affiliatepress_lifetime_deal_close(){
                            const vm = this;
                            var postData = [];
                            postData.action = "affiliatepress_lifetime_deal_close";
                            postData._wpnonce = "'.esc_html(wp_create_nonce("ap_wp_nonce")).'"
                            axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                            .then( function (response) {
                                const notice = document.querySelector(".ap-header-deal-belt-wrapper");
                                if (notice) {
                                    notice.style.display = "none";
                                }
                            }.bind(this) )
                            .catch( function (error) {   
                               console.log(error);
                            });
                        },
                        affiliatepress_redirect_pricing_page(){
                            const vm = this;
                            window.open("'.$affiliatepress_website_url.'pricing/?utm_campaign=PluginTopBar");
                        },                                    
                        ap_fab_floating_action_btn(){
                            const vm = this;
                            vm.affiliatepress_fab_floating_btn = 1;                         
                        },
                        ap_fab_floating_close_btn(){
                            const vm = this;
                            vm.affiliatepress_fab_floating_btn = 0;
                        },  
                        open_feature_request_url(){
                            window.open("https://ideas.affiliatepressplugin.com/", "_blank");
                        },
                        open_facebook_community_url(){
                            window.open("https://www.facebook.com/groups/affiliatepress", "_blank");
                        },
                        open_youtube_channel_url(){
                            window.open("https://www.youtube.com/@AffiliatePress/", "_blank");
                        },  
                        resetNeedHelpModal(){
                            const vm = this;
                            
                        },
                        open_need_help_url(){
                            const vm = this;
                            var ap_get_url_param = new URLSearchParams(window.location.search);
                            var ap_get_page = ap_get_url_param.get("page");
                            var ap_get_action = ap_get_url_param.get("action");
                            var ap_get_setting_page = ap_get_url_param.get("setting_page");

                            if(ap_get_page == "affiliatepress_lite_wizard"){ 
                                vm.read_more_link = "'.$affiliatepress_website_url.'documents/quick-start-guide/";
                                vm.openNeedHelper("list_quick-start-guide", "quick-start-guide", "Quick Start Guide");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if(ap_get_page == "affiliatepress"){
                                vm.read_more_link = "'.$affiliatepress_website_url.'documents/dashboard/";
                                vm.openNeedHelper("list_dashboard", "dashboard", "Dashboard");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if(ap_get_page == "affiliatepress_affiliates"){
                                vm.read_more_link = "'.$affiliatepress_website_url.'documents/affiliate-management/";
                                vm.openNeedHelper("list_affiliate-management", "affiliate-management", "Affiliates");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if(ap_get_page == "affiliatepress_commissions"){
                                vm.read_more_link = "'.$affiliatepress_website_url.'documents/commission/";
                                vm.openNeedHelper("list_commission", "commission", "Commissions");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if(ap_get_page == "affiliatepress_visits"){
                                vm.read_more_link = "'.$affiliatepress_website_url.'documents/visits/";
                                vm.openNeedHelper("list_visits", "visits", "Visits");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if(ap_get_page == "affiliatepress_creative"){
                                vm.read_more_link = "'.$affiliatepress_website_url.'documents/creatives/";
                                vm.openNeedHelper("list_creatives", "creatives", "Creatives");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if(ap_get_page == "affiliatepress_payout"){
                                vm.read_more_link = "'.$affiliatepress_website_url.'documents/payouts/";
                                vm.openNeedHelper("list_payouts", "payouts", "Payouts");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if(ap_get_page == "affiliatepress_notifications"){
                                vm.read_more_link = "'.$affiliatepress_website_url.'documents/notifications/";
                                vm.openNeedHelper("list_notifications", "notifications", "Notifications");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if(ap_get_page == "affiliatepress_affiliate_fields"){
                                vm.read_more_link = "'.$affiliatepress_website_url.'documents/form-fields/";
                                vm.openNeedHelper("list_form-fields", "form-fields", "Form Fields");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if(ap_get_page == "affiliatepress_addons"){
                                vm.read_more_link = "'.$affiliatepress_website_url.'add-ons/";
                                vm.openNeedHelper("list_installing-affiliatepress", "installing-affiliatepress", "Addons");
                                vm.affiliatepress_fab_floating_btn = 0;
                            }else if( ap_get_page == "affiliatepress_settings"){                               
                                var selected_tab_name = sessionStorage.getItem("current_affiliatpress_tabname");                            
                                if(vm.affiliatepress_selected_tab_name != "" && selected_tab_name == ""){
                                    selected_tab_name = vm.affiliatepress_selected_tab_name;
                                }
                                if(selected_tab_name == "affiliate_settings" || selected_tab_name == "" || selected_tab_name === null){
                                    vm.read_more_link = "'.$affiliatepress_website_url.'documents/general-settings/";
                                    vm.openNeedHelper("list_general-settings", "general-settings", "General Settings");
                                    vm.affiliatepress_fab_floating_btn = 0;                                    
                                }else if(selected_tab_name == "commissions_settings"){
                                    vm.read_more_link = "'.$affiliatepress_website_url.'documents/commissions-settings/";
                                    vm.openNeedHelper("list_commissions-settings", "commissions-settings", "Commissions Settings");
                                    vm.affiliatepress_fab_floating_btn = 0; 
                                }else if(selected_tab_name == "integrations_settings"){
                                    vm.read_more_link = "'.$affiliatepress_website_url.'documents/integrations-settings/";
                                    vm.openNeedHelper("list_integrations-settings", "integrations-settings", "Integrations Settings");
                                    vm.affiliatepress_fab_floating_btn = 0; 
                                }else if(selected_tab_name == "email_notification_settings"){
                                    vm.read_more_link = "'.$affiliatepress_website_url.'documents/email-notifications-settings/";
                                    vm.openNeedHelper("list_email-notifications-settings", "email-notifications-settings", "Email Settings");
                                    vm.affiliatepress_fab_floating_btn = 0; 
                                }else if(selected_tab_name == "debug_log_settings"){
                                    vm.read_more_link = "'.$affiliatepress_website_url.'documents/debug-log-settings/";
                                    vm.openNeedHelper("list_debug-log-settings", "debug-log-settings", "Debug Log Settings");
                                    vm.affiliatepress_fab_floating_btn = 0; 
                                }else if(selected_tab_name == "message_settings"){
                                    vm.read_more_link = "'.$affiliatepress_website_url.'documents/message-settings/";
                                    vm.openNeedHelper("list_message-settings", "message-settings", "Message Settings");
                                    vm.affiliatepress_fab_floating_btn = 0; 
                                }else if(selected_tab_name == "appearance_settings"){
                                    vm.read_more_link = "'.$affiliatepress_website_url.'documents/appearance/";
                                    vm.openNeedHelper("list_appearance", "appearance", "Appearance Settings");
                                    vm.affiliatepress_fab_floating_btn = 0; 
                                }   
                                '.$affiliatepress_additional_settings_tab_help_content.'                                 
                            }
                            '.$affiliatepress_additional_menu_help_content.'

                        },
                        open_premium_modal(){
                            const vm = this;
                            vm.affiliatepress_premium_modal = true;
                        },
                        open_sale_premium_modal(){
                            const vm = this;
                            vm.affiliatepress_sale_premium_modal = true;
                        },
                        close_need_help_popup(){
                            const vm = this;
                            vm.needHelpDrawer = false;
                        },
                        affiliatepress_redirect_sale_premium_page(){
                            window.open("'.$affiliatepress_website_url.'pricing/?utm_source=blackfriday_liteversionpopup&utm_medium=liteversion&utm_campaign=blackfriday", "_blank");
                        },
                        openNeedHelper(page_name = "", module_name = "", module_title = ""){
                            const vm = this;
                            vm.helpDrawerData = "";
                            vm.is_display_drawer_loader = "1";
                            vm.needHelpDrawer = true;
                            var help_page_name = "list_"+"'.esc_html($requested_module).'";
                            if(page_name != ""){
                                help_page_name = page_name
                            }
                            var help_module_name = "'.esc_html($requested_module).'";
                            if(module_name != ""){
                                help_module_name = module_name
                            }
                            if(module_title != ""){
                                this.requested_module = module_title
                            }
                            '.$affiliatepress_change_help_request_module_data.'
                            var postData = { action:"affiliatepress_get_help_data",  module: help_module_name, page: help_page_name, type: "list",_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                            axios.post(affiliatepress_ajax_obj.ajax_url, Qs.stringify(postData)).then(function(response){
                                vm.is_display_drawer_loader = "0";
                                vm.helpDrawerData = response.data;
                                var elements = jQuery(".ap-help-drawer__body-wrapper");                                
                                if(elements.length == 0){
                                    /* jQuery(".ap-hd-header").next().andSelf().wrapAll(`<div class="ap-help-drawer__body-wrapper"></div>`); */
                                }                               
                                jQuery(document).ready(function(){
                                    jQuery("figure#watch_now_btn").each(function(){
                                        var affiliatepress_data_video_link = jQuery(this).attr("data-video");                                        
                                        jQuery(this).children().wrap(`<a href="`+ affiliatepress_data_video_link +`" target="_blank" />`);  
                                    });    
                                });
                               
                            }.bind(vm) )
                            .catch( function (error) {
                                console.log(error);
                            });
                        },                          
                        '.$affiliatepress_dynamic_vue_methods.'    
                        '.$affiliatepress_all_dynamic_vue_methods.'                
                    },
                    computed: {
                      '.$affiliatepress_dynamic_computed_methods.'
                    }
                });   

                app.use(ElementPlus, {
                    locale: ElementPlusLocaleData,
                });      
                app.mount("#affiliatepress_root_app");

                }
                ';

                $affiliatepress_script_data = $affiliatepress_script_return_data;
                $affiliatepress_script_data.= "                 
                var affiliatepress_is_script_loaded = false;
                affiliatepress_beforeload_data = '';
                if( null != document.getElementById('#affiliatepress_root_app') ){
                    affiliatepress_beforeload_data = document.getElementById('#affiliatepress_root_app').innerHTML;
                }
                window.addEventListener('load', function() {
                    if( affiliatepress_is_script_loaded == false) {
                        affiliatepress_is_script_loaded = true;                        
                        affiliatepress_admin_vue_data_load();
                    }
                });";  

               return $affiliatepress_script_data;

            }else{
                return '';
            }


        }

        /**
         * Load admin side JS
         *
         * @return void
         */
        function affiliatepress_set_admin_js(){
            global $affiliatepress_slugs;


            $affiliatepress_custom_inline_script = 'var AFFILIATEPRESS_IMAGE_URL_VAR = "' . esc_url(AFFILIATEPRESS_IMAGES_URL) . '";';           
            wp_add_inline_script( 'affiliatepress-signup-form-editor-script', $affiliatepress_custom_inline_script, 'before' );

            /* Plugin JS File */
            wp_register_script('affiliatepress_admin_js', AFFILIATEPRESS_URL . 'js/affiliatepress_vue.min.js', array(), AFFILIATEPRESS_VERSION,false); 
            wp_register_script('affiliatepress_axios_js', AFFILIATEPRESS_URL . 'js/affiliatepress_axios.min.js', array(), AFFILIATEPRESS_VERSION,false ); 
            wp_register_script('affiliatepress_wordpress_vue_qs_js', AFFILIATEPRESS_URL . 'js/affiliatepress_wordpress_vue_qs_helper.js', array(), AFFILIATEPRESS_VERSION,false ); 
            wp_register_script('affiliatepress_element_js', AFFILIATEPRESS_URL . 'js/affiliatepress_element.min.js', array(), AFFILIATEPRESS_VERSION,false );
            wp_register_script('affiliatepress_sortable_js', AFFILIATEPRESS_URL . 'js/affiliatepress_sortable.min.js', array( 'affiliatepress_admin_js' ), AFFILIATEPRESS_VERSION,false);
            wp_register_script('affiliatepress_draggable_js', AFFILIATEPRESS_URL . 'js/affiliatepress_vuedraggable.min.js', array( 'affiliatepress_admin_js' ), AFFILIATEPRESS_VERSION,false );
            wp_register_script('affiliatepress_charts_js_admin', AFFILIATEPRESS_URL . 'js/affiliatepress_chart.umd.min.js', array(), AFFILIATEPRESS_VERSION,false );

            /* Add JS File Only For Plugin Pages */
            if (isset($_REQUEST['page']) && in_array(sanitize_text_field($_REQUEST['page']), (array) $affiliatepress_slugs) ){ // phpcs:ignore
                
                wp_enqueue_script('affiliatepress_admin_js');
                wp_enqueue_script('affiliatepress_axios_js');                
                wp_enqueue_script('affiliatepress_wordpress_vue_qs_js');
                wp_enqueue_script('affiliatepress_element_js');
                wp_enqueue_script( 'moment' );

                if (isset($_REQUEST['page']) && ( sanitize_text_field($_REQUEST['page']) == 'affiliatepress' || sanitize_text_field($_REQUEST['page']) == 'affiliatepress' ) ) { // phpcs:ignore                     
                    wp_enqueue_script('affiliatepress_charts_js_admin');
                }                

                /* Add JS file only for plugin pages. */
                if (isset($_REQUEST['page']) && ( sanitize_text_field($_REQUEST['page']) == 'affiliatepress_affiliate_fields' || sanitize_text_field($_REQUEST['page']) == 'affiliatepress_affiliate_fields' ) ) { // phpcs:ignore 
                    wp_enqueue_script('affiliatepress_sortable_js');
                    wp_enqueue_script('affiliatepress_draggable_js');
                }                

                $affiliatepress_data = 'var affiliatepress_ajax_obj = '.wp_json_encode( array(
                    'ajax_url' => admin_url( 'admin-ajax.php')
                    )
                ).';';
                wp_add_inline_script('affiliatepress_admin_js', $affiliatepress_data, 'before');  
    
                $affiliatepress_inline_script = $this->affiliatepress_admin_print_scripts_func();
                wp_add_inline_script('affiliatepress_element_js', $affiliatepress_inline_script,'after');

                do_action('affiliatepress_after_add_admin_js');


            } 



        }

        
        /**
         * Set admin CSS
         *
         * @return void
         */
        function affiliatepress_set_admin_css(){
            global $affiliatepress_slugs;
            /* Plugin Style */
            wp_register_style('affiliatepress_variables_css', AFFILIATEPRESS_URL . 'css/affiliatepress_variables.css', array(), AFFILIATEPRESS_VERSION);
            wp_register_style('affiliatepress_elements_css', AFFILIATEPRESS_URL . 'css/affiliatepress_elements.css', array(), AFFILIATEPRESS_VERSION);
            wp_register_style('affiliatepress_component_css', AFFILIATEPRESS_URL . 'css/affiliatepress_component.css', array(), AFFILIATEPRESS_VERSION);
            wp_register_style('affiliatepress_admin_css', AFFILIATEPRESS_URL . 'css/affiliatepress_admin.css', array(), AFFILIATEPRESS_VERSION);
            wp_register_style('affiliatepress_wizard_style', AFFILIATEPRESS_URL . 'css/affiliatepress_wizard.css', array(), AFFILIATEPRESS_VERSION);
            wp_register_style('affiliatepress_growth_tool_style', AFFILIATEPRESS_URL . 'css/affiliatepress_growth_tools.css', array(), AFFILIATEPRESS_VERSION);
            wp_register_style('affiliatepress_addon_style', AFFILIATEPRESS_URL . 'css/affiliatepress_addon_list.css', array(), AFFILIATEPRESS_VERSION);

            wp_register_style('affiliatepress_admin_rtl_css', AFFILIATEPRESS_URL . 'css/affiliatepress_admin_rtl.css', array(), AFFILIATEPRESS_VERSION);

            echo "<style type='text/css'>#toplevel_page_affiliatepress .wp-menu-image img, #toplevel_page_affiliatepress_lite_wizard .wp-menu-image img, #toplevel_page_affiliatepress_wizard .wp-menu-image img{ padding: 0 !important; opacity: 1 !important; width: 23px !important; height:23px; padding-top: 5px !important;}</style>";

            /* Add CSS file only for plugin pages. */
            if (isset($_REQUEST['page']) && in_array(sanitize_text_field($_REQUEST['page']), (array) $affiliatepress_slugs) ){ // phpcs:ignore
                
                wp_enqueue_style('affiliatepress_variables_css');
                wp_enqueue_style('affiliatepress_elements_css');                
                wp_enqueue_style('affiliatepress_component_css');
                wp_enqueue_style('affiliatepress_admin_css');

                if(!empty($_REQUEST['page']) && (sanitize_text_field(wp_unslash($_REQUEST['page'])) == 'affiliatepress_lite_wizard' || sanitize_text_field(wp_unslash($_REQUEST['page'])) == 'affiliatepress_wizard')){ // phpcs:ignore
                    wp_enqueue_style('affiliatepress_wizard_style');
                }

                if(!empty($_REQUEST['page']) && (sanitize_text_field(wp_unslash($_REQUEST['page'])) == 'affiliatepress_growth_tools' || sanitize_text_field(wp_unslash($_REQUEST['page'])) == 'affiliatepress_wizard')){ // phpcs:ignore
                    wp_enqueue_style('affiliatepress_growth_tool_style');
                }

                if(!empty($_REQUEST['page']) && (sanitize_text_field(wp_unslash($_REQUEST['page'])) == 'affiliatepress_addons' || sanitize_text_field(wp_unslash($_REQUEST['page'])) == 'affiliatepress_wizard')){ // phpcs:ignore
                    wp_enqueue_style('affiliatepress_addon_style');
                }

                if(is_rtl()){
                    wp_enqueue_style('affiliatepress_admin_rtl_css');
                }

                do_action('affiliatepress_after_add_admin_css');

            }

            /*
            $affiliatepress_custom_css = "
                #adminmenu #toplevel_page_affiliatepress .wp-menu-image, #adminmenu  #toplevel_page_affiliatepress_wizard .wp-menu-image{
                    #toplevel_page_affiliatepress .wp-menu-image img, #toplevel_page_affiliatepress_wizard .wp-menu-image img, #toplevel_page_affiliatepress_wizard .wp-menu-image img{ padding: 0 !important; opacity: 1 !important; width: 23px !important; height:23px; padding-top: 5px !important;
                }
            ";
            wp_add_inline_style('affiliatepress_admin_menu_style_css', $affiliatepress_custom_css);            
            */
                
        }


        function affiliatepress_prevent_rocket_loader_script( $tag, $handle )
        {
            $script   = htmlspecialchars($tag);
            $pattern2 = '/\/(wp\-content\/plugins\/affiliatepress)|(wp\-includes\/js)/';
            preg_match($pattern2, $script, $match_script);

            /* Check if current script is loaded from affiliatepress only */
            if (! isset($match_script[0]) || $match_script[0] == '' ) {
                return $tag;
            }

            $pattern = '/(.*?)(data\-cfasync\=)(.*?)/';
            preg_match_all($pattern, $tag, $matches);
            if (! is_array($matches) ) {
                return str_replace(' src', ' data-cfasync="false" src', $tag);
            } elseif (! empty($matches) && ! empty($matches[2]) && ! empty($matches[2][0]) && strtolower(trim($matches[2][0])) != 'data-cfasync=' ) {
                return str_replace(' src', ' data-cfasync="false" src', $tag);
            } elseif (! empty($matches) && empty($matches[2]) ) {
                return str_replace(' src', ' data-cfasync="false" src', $tag);
            } else {
                return $tag;
            }
        }

        function affiliatepress_prevent_rocket_loader_script_clf_advanced( $tag, $handle ){
			
			$script = htmlspecialchars($tag);

			$regex = '/(.*?)(<script(\s)(.*?)(|\s)id=(\'|\")affiliatepress(.*)\-(after|before)(\'|\"))\>(.*?)/';

            $handle_arr = ['wcap_vue_js'];

            $handle_arr = apply_filters( 'affiliatepress_skip_loader_tags', $handle_arr, $tag, $handle );

			if( preg_match( '/affiliatepress/', $handle ) || preg_match( '/affiliatepress/', $script ) || preg_match('/id=&#039;affiliatepress/', $script) || in_array( $handle, $handle_arr ) ){
                if( preg_match( '/\=(\'|")/', $tag, $matches_ ) ){
                    if( !empty( $matches_[1] ) ){
                        $tag = str_replace( " src", " data-cfasync=". $matches_[1]."false".$matches_[1]." src", $tag );
                    } else {
                        $tag = str_replace(' src', ' data-cfasync="false" src', $tag);
                    }
                } else {
                    $tag = str_replace(' src', ' data-cfasync="false" src', $tag);
                }
			}

			if( preg_match( $regex, $tag, $matches ) ){
				$replaced = preg_replace( $regex, '$1<script$3$4$5id=$6affiliatepress$7-$8$9 data-cfasync=$6false$9>$10', $tag );
                if( null != $replaced ){
                    $tag = $replaced;
                }
			}

            if( preg_match( '/(<img data\-cfasync\=\"false\")/', $tag ) ){
				$tag = preg_replace( '/(<img data\-cfasync\=\"false\")/', '<img ', $tag );
			}
			if( preg_match( '/(&lt;img data\-cfasync\=\"false\")/', $tag ) ){
				$tag = preg_replace( '/(&lt;img data\-cfasync\=\"false\")/', '&lt;img ', $tag );
			}

			return $tag;
		}

        function affiliatepress_prevent_rocket_loader_script_clf( $tag, $handle )
        {
            $script   = htmlspecialchars($tag);
            $pattern2 = '/\/(wp\-content\/plugins\/affiliatepress)|(wp\-includes\/js)/';
            preg_match($pattern2, $script, $match_script);

            /* Check if current script is loaded from affiliatepress only */
            if (! isset($match_script[0]) || $match_script[0] == '' ) {
                return $tag;
            }

            $pattern = '/(.*?)(data\-cfasync\=)(.*?)/';
            preg_match_all($pattern, $tag, $matches);

            $pattern3 = '/type\=(\'|")[a-zA-Z0-9]+\-(text\/javascript)(\'|")/';
            preg_match_all($pattern3, $tag, $match_tag);

            if (! isset($match_tag[0]) || empty($match_tag[0]) ) {
                return $tag;
            }

            if (! is_array($matches) ) {
                return str_replace(' src', ' data-cfasync="false" src', $tag);
            } elseif (! empty($matches) && ! empty($matches[2]) && ! empty($matches[2][0]) && strtolower(trim($matches[2][0])) != 'data-cfasync=' ) {
                return str_replace(' src', ' data-cfasync="false" src', $tag);
            } elseif (! empty($matches) && empty($matches[2]) ) {
                return str_replace(' src', ' data-cfasync="false" src', $tag);
            } else {
                return $tag;
            }
        }

        function affiliatepress_get_excluded_js() {
            $affiliatepress_js =  array(
                'affiliatepress_front_js',
                'affiliatepress_element_js',
                'affiliatepress_axios_js',
                'affiliatepress_social_sharing_js', //pro js
            );

            return $affiliatepress_js;
        }

        function affiliatepress_get_excluded_js_with_file() {
            $affiliatepress_js =  array(
                'affiliatepress_axios.min.js',
                'affiliatepress_vue.min.js',
                'affiliatepress_element.min.js',
                'social-sharing.js', //pro js
            );

            return $affiliatepress_js;
        }

         /**
         * Function for excluded delay js wp rocket
         *
         * @param  mixed $affiliatepress_bytes
         * @return void
        */
        function affiliatepress_wp_rocket_excluded_js( $excluded_js ) {

            if( !is_array($excluded_js) )
            {
                $excluded_js = array();
            }

            $affiliatepress_js = $this->affiliatepress_get_excluded_js();

            if(!empty($affiliatepress_js)){
                return array_merge( $excluded_js, $affiliatepress_js );
            }else{
                return $excluded_js;
            }
        }

        /**
         * Function for excluded delay js wp optimize
         * 
        */
        function affiliatepress_wp_optimize_excluded_delay_js($tag, $handle, $src ) {

            $exclude_handles = $this->affiliatepress_get_excluded_js();
        
            if ( in_array( $handle, $exclude_handles, true ) ) {
                $tag = str_replace( '<script ', '<script data-no-delay-js ', $tag );
            }
        
            return $tag;
        }

        /**
         * Function for excluded Enable merging of JavaScript files  wp optimize
         *
         * @param  mixed $handles
         * @return array
        */
        function affiliatepress_wp_optimize_minify_exclude_scripts( $handles ) {

            if( !is_array($handles) )
            {
                $handles = array();
            }

            $affiliatepress_js = $this->affiliatepress_get_excluded_js_with_file();

            if(!empty($affiliatepress_js)){
                return array_merge( $handles, $affiliatepress_js );
            }else{
                return $handles;
            }
        }

        function affiliatepress_hummingbird_excluded_delay_js( $scripts ) {

            if( !is_array($scripts) )
            {
                $scripts = array();
            }

            $affiliatepress_js = $this->affiliatepress_get_excluded_js_with_file();
            $affiliatepress_js[] = 'moment.min.js';

            if(!empty($affiliatepress_js)){
                return array_merge( $scripts, $affiliatepress_js );
            }else{
                return $scripts;
            }
        }

        /**
         * Function for affiliatepress capabilities
         *
         * @return array
        */
        function affiliatepress_capabilities(){
            $affiliatepress_cap = array(
                'affiliatepress'                  => '',                
                'affiliatepress_affiliates'       => esc_html__('Affiliates', 'affiliatepress-affiliate-marketing'),
                'affiliatepress_settings'         => esc_html__('Settings', 'affiliatepress-affiliate-marketing'),
                'affiliatepress_visits'           => esc_html__('Visits', 'affiliatepress-affiliate-marketing'),
                'affiliatepress_creative'         => esc_html__('Creative', 'affiliatepress-affiliate-marketing'),  
                'affiliatepress_notifications'    => esc_html__('Notifications', 'affiliatepress-affiliate-marketing'),  
                'affiliatepress_commissions'      => esc_html__('Commission', 'affiliatepress-affiliate-marketing'),
                'affiliatepress_affiliate_fields' => esc_html__('Affiliate Fields', 'affiliatepress-affiliate-marketing'),
                'affiliatepress_payout'           => esc_html__('Payout', 'affiliatepress-affiliate-marketing'),
                'affiliatepress_growth_tools'     => esc_html__('Growth Plugins', 'affiliatepress-affiliate-marketing'),
                'affiliatepress_addons'           => esc_html__('Addons', 'affiliatepress-affiliate-marketing'),
            );
            return $affiliatepress_cap;
        }

                  		
		/**
		 * Function for check single capability for user
		 *
		 * @param  string $affiliatepress_capability
		 * @return boolean
		 */
		public function affiliatepress_check_capability( $affiliatepress_capability ) {
			$return = false;
			if ( ! empty( $affiliatepress_capability ) ) {
				$affiliatepress_user_id    = get_current_user_id();
				$affiliatepress_user_info  = get_userdata( $affiliatepress_user_id );
				$affiliatepress_user_roles = $affiliatepress_user_info->roles;
                if ( current_user_can( $affiliatepress_capability ) ) {
                    $return = true;
                }
			}
			return $return;
		}

        /**
         * Function for create a Affiliate User Role
         *
         * @return void
         * 
        */
        function affiliatepress_add_user_role_and_capabilities(){
            global $wp_roles;
            $role_name  = 'AffiliatePress Affiliate User';
            $role_slug  = sanitize_title($role_name);
            $affiliatepress_basic_caps = array(
            $role_slug => true,
                'read'     => true,
                'level_0'  => true,
            );
            $wp_roles->add_role($role_slug, $role_name, $affiliatepress_basic_caps);
        }
        

        
        /**
         * Function for menu page slug 
         *
         * @return object
        */
        function affiliatepress_page_slugs(){
            global $affiliatepress_slugs;
            $affiliatepress_slugs = new stdClass();

            /* Admin-Pages-Slug */
            $affiliatepress_slugs->affiliatepress                    = 'affiliatepress';
            $affiliatepress_slugs->affiliatepress_lite_wizard        = 'affiliatepress_lite_wizard';
            $affiliatepress_slugs->affiliatepress_affiliates         = 'affiliatepress_affiliates';
            $affiliatepress_slugs->affiliatepress_settings           = 'affiliatepress_settings';
            $affiliatepress_slugs->affiliatepress_visits             = 'affiliatepress_visits';
            $affiliatepress_slugs->affiliatepress_creative           = 'affiliatepress_creative';
            $affiliatepress_slugs->affiliatepress_notifications      = 'affiliatepress_notifications';
            $affiliatepress_slugs->affiliatepress_commissions        = 'affiliatepress_commissions';
            $affiliatepress_slugs->affiliatepress_affiliate_fields   = 'affiliatepress_affiliate_fields';
            $affiliatepress_slugs->affiliatepress_payout             = 'affiliatepress_payout';
            $affiliatepress_slugs->affiliatepress_growth_tools       = 'affiliatepress_growth_tools';
            $affiliatepress_slugs->affiliatepress_addons             = 'affiliatepress_addons';

            return $affiliatepress_slugs;
        }

        
        /**
         * Function for backend menu
         *
         * @return void
        */
        function affiliatepress_menu(){

            global $AffiliatePress, $affiliatepress_slugs;
            
            $affiliatepress_place = $this->affiliatepress_get_free_menu_position(26.1, 0.3);            
            $affiliatepress_is_wizard_complete = get_option('affiliatepress_lite_wizard_complete');

            
            $ap_requested_page = !empty( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : ''; // phpcs:ignore 
            $affiliatepress_icon_selected = '';
            if( !empty( $affiliatepress_slugs ) && in_array($ap_requested_page, (array)$affiliatepress_slugs) )
            {
                $affiliatepress_icon_selected = "_selected";
            }
            $affiliatepress_icon = esc_url(AFFILIATEPRESS_IMAGES_URL)."/affiliatepress_menu_icon" . $affiliatepress_icon_selected . ".svg";
            
            if(empty($affiliatepress_is_wizard_complete) || $affiliatepress_is_wizard_complete == 0){
                $affiliatepress_menu_hook = add_menu_page(esc_html__('AffiliatePress', 'affiliatepress-affiliate-marketing'), esc_html__('AffiliatePress', 'affiliatepress-affiliate-marketing'), 'affiliatepress', $affiliatepress_slugs->affiliatepress_lite_wizard, array( $this, 'route' ), $affiliatepress_icon, $affiliatepress_place);
            }else{
                $affiliatepress_menu_hook = add_menu_page(esc_html__('AffiliatePress', 'affiliatepress-affiliate-marketing'), esc_html__('AffiliatePress', 'affiliatepress-affiliate-marketing'), 'affiliatepress', $affiliatepress_slugs->affiliatepress, array( $this, 'route' ), $affiliatepress_icon, $affiliatepress_place);
            }

            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Dashboard', 'affiliatepress-affiliate-marketing'), esc_html__('Dashboard', 'affiliatepress-affiliate-marketing'), 'affiliatepress', $affiliatepress_slugs->affiliatepress);

            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Commission', 'affiliatepress-affiliate-marketing'), esc_html__('Commission', 'affiliatepress-affiliate-marketing'), 'affiliatepress_commissions', $affiliatepress_slugs->affiliatepress_commissions, array( $this, 'route' ));

            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Visits', 'affiliatepress-affiliate-marketing'), esc_html__('Visits', 'affiliatepress-affiliate-marketing'), 'affiliatepress_visits', $affiliatepress_slugs->affiliatepress_visits, array( $this, 'route' ));            

            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Affiliates', 'affiliatepress-affiliate-marketing'), esc_html__('Affiliates', 'affiliatepress-affiliate-marketing'), 'affiliatepress_affiliates', $affiliatepress_slugs->affiliatepress_affiliates, array( $this, 'route' ));            

            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Creatives', 'affiliatepress-affiliate-marketing'), esc_html__('Creatives', 'affiliatepress-affiliate-marketing'), 'affiliatepress_creative', $affiliatepress_slugs->affiliatepress_creative, array( $this, 'route' ));

            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Payouts', 'affiliatepress-affiliate-marketing'), esc_html__('Payouts', 'affiliatepress-affiliate-marketing'), 'affiliatepress_payout', $affiliatepress_slugs->affiliatepress_payout, array( $this, 'route' ));
            
            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Notifications', 'affiliatepress-affiliate-marketing'), esc_html__('Notifications', 'affiliatepress-affiliate-marketing'), 'affiliatepress_notifications', $affiliatepress_slugs->affiliatepress_notifications, array( $this, 'route' ));

            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Form Editor', 'affiliatepress-affiliate-marketing'), esc_html__('Form Editor', 'affiliatepress-affiliate-marketing'), 'affiliatepress_affiliate_fields', $affiliatepress_slugs->affiliatepress_affiliate_fields, array( $this, 'route' ));

            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Settings', 'affiliatepress-affiliate-marketing'), esc_html__('Settings', 'affiliatepress-affiliate-marketing'), 'affiliatepress_settings', $affiliatepress_slugs->affiliatepress_settings, array( $this, 'route' ));   
            
            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Addons', 'affiliatepress-affiliate-marketing'), esc_html__('Addons', 'affiliatepress-affiliate-marketing'), 'affiliatepress_addons', $affiliatepress_slugs->affiliatepress_addons, array( $this, 'route' ));  
            
            add_submenu_page($affiliatepress_slugs->affiliatepress, esc_html__('Growth Plugins', 'affiliatepress-affiliate-marketing'), esc_html__('Growth Plugins', 'affiliatepress-affiliate-marketing'), 'affiliatepress_growth_tools', $affiliatepress_slugs->affiliatepress_growth_tools, array( $this, 'route' )); 
            
            if(!$this->affiliatepress_pro_install()){

                $upgrade_menu_text = esc_html__( 'Upgrade to Pro', 'affiliatepress-affiliate-marketing' );
                $affiliatepress_current_date_for_bf_popup = current_time( 'timestamp', true );
                $affiliatepress_sale_popup_details = $this->affiliatepress_get_sales_data();
                $current_year = gmdate('Y', current_time('timestamp', true ) );

                if( !empty( $affiliatepress_sale_popup_details[ $current_year ] ) ){
                    
                    $sale_details = $affiliatepress_sale_popup_details[ $current_year ];
                    
                    $affiliatepress_bf_popup_start_time = $sale_details['start_time'];
                    $affiliatepress_bf_popup_end_time = $sale_details['end_time'];
    
                    $type = !empty( $sale_details['type'] ) ? $sale_details['type'] : 'black_friday';
                    
                    if( $affiliatepress_current_date_for_bf_popup >= $affiliatepress_bf_popup_start_time && $affiliatepress_current_date_for_bf_popup <= $affiliatepress_bf_popup_end_time ){
                        if( 'black_friday' == $type ){
                            $upgrade_menu_text = esc_html__( 'Black Friday Sale', 'affiliatepress-affiliate-marketing' );
                        }
                    }
                }
                add_submenu_page($affiliatepress_slugs->affiliatepress, $upgrade_menu_text, $upgrade_menu_text, 'affiliatepress', $affiliatepress_slugs->affiliatepress."&upgrade_action=upgrade_to_pro", array( $this, 'route' ), '99');
            }

        }

        function affiliatepress_get_sales_data(){
            
            global $affiliatepress_website_url;

            $fetch_sale_detais = get_transient( 'affiliatepress_retrieve_sale_details' );
            $sale_details = array();
            
            if( false == $fetch_sale_detais ){

                $fetch_url = $affiliatepress_website_url.'ap_misc/ap_sale_dates.json';
                $fetch_dates = wp_remote_get( $fetch_url, array( 'timeout' => 4000, 'accept' => 'application/json' ) );
                if( !is_wp_error( $fetch_dates ) ){
                    $details = wp_remote_retrieve_body( $fetch_dates );
                    $sale_details = json_decode( $details, true );
                    set_transient( 'affiliatepress_retrieve_sale_details', $sale_details, ( HOUR_IN_SECONDS * 12 ) );
                }
            } else {
                $sale_details = $fetch_sale_detais;
            }

            return $sale_details;
        }

        /**
         * Function for check affiliatepress admin menu style
         */
        function affiliatepress_menu_style()
        {
            $ap_admin_menu_script = '<style type="text/css">';
            $ap_admin_menu_script .= '#toplevel_page_affiliatepress .wp-submenu li:last-child a, #toplevel_page_affiliatepress .wp-submenu li:last-child a:hover { color: #1CC6C9 !important; font-weight: 600 !important; }';
            $ap_admin_menu_script .= '#toplevel_page_affiliatepress .wp-submenu li:last-child a:after { content: " "; width: 14px; height:14px; background-image: url(' . esc_url(AFFILIATEPRESS_IMAGES_URL) . '/affiliatepress_upgrade_pro_crown.svg); background-repeat:no-repeat; vertical-align: text-bottom; display: inline-block; margin-left: 5px; }';
            $ap_admin_menu_script .= '</style>';
           
            echo  $ap_admin_menu_script .= '';//phpcs:ignore
        }
        
        /**
         * Function for admin page  route
         *
         * @return page html data
        */
        function route(){
            global $affiliatepress_slugs;
            if (isset($_REQUEST['page']) ) { // phpcs:ignore
                $affiliatepress_pageWrapperClass = '';
                if (is_rtl() ) {
                    $affiliatepress_pageWrapperClass = 'affiliatepress_page_rtl';
                }
                echo '<div class="affiliatepress_page_wrapper ' . esc_html($affiliatepress_pageWrapperClass) . '" id="affiliatepress_root_app">';
                $requested_page = sanitize_text_field($_REQUEST['page']); // phpcs:ignore 
                do_action('affiliatepress_admin_messages', $requested_page);

                if (file_exists(AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_main.php') ) {
                    include AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_main.php';
                }
                echo '</div>';
            }
        }

        /**
         * Function for set menu position 
         *
         * @param  mixed $affiliatepress_start
         * @param  mixed $affiliatepress_increment
         * @return float
        */
        function affiliatepress_get_free_menu_position( $affiliatepress_start, $affiliatepress_increment = 0.1 ){
            foreach ( $GLOBALS['menu'] as $affiliatepress_key => $affiliatepress_menu ) {
                $affiliatepress_menus_positions[] = floatval($affiliatepress_key);
            }
            if (! in_array($affiliatepress_start, $affiliatepress_menus_positions) ) {
                $affiliatepress_start = strval($affiliatepress_start);
                return $affiliatepress_start;
            } else {
                $affiliatepress_start += $affiliatepress_increment;
            }
            /* the position is already reserved find the closet one */
            while ( in_array($affiliatepress_start, $affiliatepress_menus_positions) ) {
                $affiliatepress_start += $affiliatepress_increment;
            }
            $affiliatepress_start = strval($affiliatepress_start);
            return $affiliatepress_start;
        }

         
        /**
         * Function for date formate
         *
         * @param  string $affiliatepress_selected_date_format
         * @return String
        */
        function affiliatepress_check_common_date_format( $affiliatepress_selected_date_format ){
            $return_final_date_format              = '';
            $affiliatepress_elementer_default_formate = array(
                'Y' => 'YYYY',
                'y' => 'YY',
                'F' => 'MMMM',
                'M' => 'MMM',
                'm' => 'MM',
                'n' => 'M',
                'l' => 'dddd',
                'D' => 'ddd',
                'd' => 'DD',
                'j' => 'DD',
            );
            $affiliatepress_supported_date_formats   = array( 'd', 'D', 'm', 'M', 'y', 'Y', 'F', 'j', 'l', 'n' );

            if ($affiliatepress_selected_date_format == 'M j, Y' ) {
                return 'MMM DD, YYYY';
            }else if ($affiliatepress_selected_date_format == 'F j, Y' ) {
                return 'MMMM DD, YYYY';
            } elseif (substr_count($affiliatepress_selected_date_format, '-') ) {
                $affiliatepress_tmp_date_format_arr = explode('-', $affiliatepress_selected_date_format);
                if (in_array($affiliatepress_tmp_date_format_arr[0], $affiliatepress_supported_date_formats) && in_array($affiliatepress_tmp_date_format_arr[1], $affiliatepress_supported_date_formats) && in_array($affiliatepress_tmp_date_format_arr[2], $affiliatepress_supported_date_formats) ) {

                    $return_final_date_format = '';
                    if (in_array($affiliatepress_tmp_date_format_arr[0], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[0] ] . '-';
                    }
                    if (in_array($affiliatepress_tmp_date_format_arr[1], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $return_final_date_format . $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[1] ] . '-';
                    }
                    if (in_array($affiliatepress_tmp_date_format_arr[2], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $return_final_date_format . $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[2] ];
                    }
                    return $return_final_date_format;
                } else {
                    return 'MMMM d, YYYY';
                }
            } elseif (substr_count($affiliatepress_selected_date_format, '/') ) {
                $affiliatepress_tmp_date_format_arr = explode('/', $affiliatepress_selected_date_format);                

                if (in_array($affiliatepress_tmp_date_format_arr[0], $affiliatepress_supported_date_formats) && in_array($affiliatepress_tmp_date_format_arr[1], $affiliatepress_supported_date_formats) && in_array($affiliatepress_tmp_date_format_arr[2], $affiliatepress_supported_date_formats) ) {

                    $return_final_date_format = '';
                    if (in_array($affiliatepress_tmp_date_format_arr[0], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[0] ] . '/';
                    }
                    if (in_array($affiliatepress_tmp_date_format_arr[1], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $return_final_date_format . $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[1] ] . '/';
                    }
                    if (in_array($affiliatepress_tmp_date_format_arr[2], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $return_final_date_format . $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[2] ];
                    }
                    return $return_final_date_format;
                } else {
                    return 'MMMM d, YYYY';
                }
            } elseif (substr_count($affiliatepress_selected_date_format, ' ') ) {

                $affiliatepress_selected_date_format = str_replace(",","",$affiliatepress_selected_date_format);
                $affiliatepress_tmp_date_format_arr = explode(' ', $affiliatepress_selected_date_format);
                $return_final_date_format         = '';

                if (in_array($affiliatepress_tmp_date_format_arr[0], $affiliatepress_supported_date_formats) && in_array($affiliatepress_tmp_date_format_arr[1], $affiliatepress_supported_date_formats) && in_array($affiliatepress_tmp_date_format_arr[2], $affiliatepress_supported_date_formats) ) {

                    if (in_array($affiliatepress_tmp_date_format_arr[0], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[0] ] . ' ';
                    }
                    if (in_array($affiliatepress_tmp_date_format_arr[1], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $return_final_date_format . $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[1] ] . ' ';
                    }
                    if (in_array($affiliatepress_tmp_date_format_arr[2], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $return_final_date_format . $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[2] ];
                    }
                    return $return_final_date_format;

                } else {
                    return 'MMMM d, YYYY';
                }
            } elseif (substr_count($affiliatepress_selected_date_format, '.') ) {
                $affiliatepress_tmp_date_format_arr = explode('.', $affiliatepress_selected_date_format);
                $return_final_date_format         = '';

                if (in_array($affiliatepress_tmp_date_format_arr[0], $affiliatepress_supported_date_formats) && in_array($affiliatepress_tmp_date_format_arr[1], $affiliatepress_supported_date_formats) && in_array($affiliatepress_tmp_date_format_arr[2], $affiliatepress_supported_date_formats) ) {

                    if (in_array($affiliatepress_tmp_date_format_arr[0], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[0] ] . '.';
                    }
                    if (in_array($affiliatepress_tmp_date_format_arr[1], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $return_final_date_format . $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[1] ] . '.';
                    }
                    if (in_array($affiliatepress_tmp_date_format_arr[2], $affiliatepress_supported_date_formats) ) {
                        $return_final_date_format = $return_final_date_format . $affiliatepress_elementer_default_formate[ $affiliatepress_tmp_date_format_arr[2] ];
                    }
                    return $return_final_date_format;

                } else {
                    return 'MMMM d, YYYY';
                }
            } else {
                return 'MMMM d, YYYY';
            }
        }
        
        
        /**
         * Function for time formate
         *
         * @param  string $affiliatepress_selected_time_format
         * @return string
         */
        function affiliatepress_check_common_time_format( $affiliatepress_selected_time_format ) {
            if ( $affiliatepress_selected_time_format == 'g:i a' ) {
                return 'h mm a';
            } else if( $affiliatepress_selected_time_format == 'g:i A' ) {
                return 'h mm A';
            } else if( $affiliatepress_selected_time_format == 'H:i' ) {
                return 'hh mm';
            } else if( $affiliatepress_selected_time_format == 'H:i:s' ) {
                return 'hh mm ss';
            } else {
                return 'h mm a';
            }
        }

        
        /**
         * Function for prevent network active
         *
         * @param  integer $affiliatepress_network_wide
         * @return void
        */
        public static function affiliatepress_check_network_activation( $affiliatepress_network_wide ){
            if (! $affiliatepress_network_wide ) {
                return;
            }
            deactivate_plugins(plugin_basename(AFFILIATEPRESS_DIR . '/affiliatepress-affiliate-marketing.php'), true, true);
            header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
            exit;
        }

            
        
        /**
         * Function for write admin response
         *
         * @param  mixed $response_data
         * @param  mixed $affiliatepress_file_name
         * @param  mixed $affiliatepress_mode - possible values 
         * @param  mixed $affiliatepress_overwrite 
         * @return void
         */
        function affiliatepress_write_response( $response_data, $affiliatepress_file_name = '', $affiliatepress_mode = 'file', $affiliatepress_overwrite = false ){
            global $wp, $wpdb, $wp_filesystem;
            $affiliatepress_file_path = AFFILIATEPRESS_DIR . '/log/response.txt';
            if (file_exists(ABSPATH . 'wp-admin/includes/file.php') && ( 'file' == $affiliatepress_mode || 'both' == $affiliatepress_mode ) ) {
                include_once ABSPATH . 'wp-admin/includes/file.php';
                if (false === ( $affiliatepress_creds = request_filesystem_credentials($affiliatepress_file_path, '', false, false) ) ) {
                    return true;
                }

                if (! WP_Filesystem($affiliatepress_creds) ) {
                    request_filesystem_credentials($affiliatepress_file_path, $method, true, false);
                    return true;
                }
                @$affiliatepress_file_data = $wp_filesystem->get_contents($affiliatepress_file_path);
                $affiliatepress_file_data .= $response_data;
                $affiliatepress_file_data .= "\r\n===========================================================================\r\n";
                $affiliatepress_breaks     = array( '<br />', '<br>', '<br/>' );
                $affiliatepress_file_data  = str_ireplace($affiliatepress_breaks, "\r\n", $affiliatepress_file_data);

                if( true == $affiliatepress_overwrite ){
                    $wp_filesystem->put_contents( $affiliatepress_file_path, '', 0755 ); // clear file every time logs write
                }

                @$write_file = $wp_filesystem->put_contents($affiliatepress_file_path, $affiliatepress_file_data, 0755);
            }

            if( 'db' == $affiliatepress_mode || 'both' == $affiliatepress_mode ){
                $affiliatepress_option_name = 'affiliatepress_debug_log_' . current_time('timestamp');
                $affiliatepress_option_value = $response_data;

                update_option( $affiliatepress_option_name, $affiliatepress_option_value );
            }
            return;
        }

        
        /**
         * Function for add default affiliatepress page
         *
         * @return void
        */
        function affiliatepress_install_default_affiliate_pages_data(){
            global $wpdb, $affiliatepress_tbl_ap_settings, $AffiliatePress;

            $affiliatepress_post_table = $wpdb->posts;
            $affiliatepress_post_author = get_current_user_id();

            $affiliatepress_affiliate_panel_content = '<!-- wp:shortcode -->[affiliatepress_affiliate_panel]<!-- /wp:shortcode -->';
            $affiliatepress_affiliate_panel_page_details = array(
                'post_title'    => esc_html__('Affiliate Panel', 'affiliatepress-affiliate-marketing'),
                'post_name'     => 'affiliate-panel',
                'post_content'  => $affiliatepress_affiliate_panel_content,
                'post_status'   => 'publish',
                'post_parent'   => 0,
                'post_author'   => 1,
                'post_type'     => 'page',
                'post_author'   => $affiliatepress_post_author,
                'post_date'     => current_time( 'mysql' ),
                'post_date_gmt' => current_time( 'mysql', 1 ),
            );

            $wpdb->insert( $affiliatepress_post_table, $affiliatepress_affiliate_panel_page_details ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $affiliatepress_post_id = $wpdb->insert_id; 
            
            $affiliatepress_current_guid = get_post_field( 'guid', $affiliatepress_post_id );
            $affiliatepress_where = array( 'ID' => $affiliatepress_post_id );
            if( '' === $affiliatepress_current_guid ){
                $wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $affiliatepress_post_id ) ), $affiliatepress_where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            }            
            $AffiliatePress->affiliatepress_update_settings('affiliate_account_page_id', 'affiliate_settings', $affiliatepress_post_id);
            

            $affiliatepress_signup_content = '<!-- wp:shortcode -->[affiliatepress_affiliate_registration]<!-- /wp:shortcode -->';
            $affiliatepress_affiliate_panel_page_details = array(
                'post_title'    => esc_html__('Affiliate Signup', 'affiliatepress-affiliate-marketing'),
                'post_name'     => 'affiliate-signup',
                'post_content'  => $affiliatepress_signup_content,
                'post_status'   => 'publish',
                'post_parent'   => 0,
                'post_author'   => 1,
                'post_type'     => 'page',
                'post_author'   => $affiliatepress_post_author,
                'post_date'     => current_time( 'mysql' ),
                'post_date_gmt' => current_time( 'mysql', 1 ),
            );

            $wpdb->insert( $affiliatepress_post_table, $affiliatepress_affiliate_panel_page_details ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $affiliatepress_post_id = $wpdb->insert_id; 
            
            $affiliatepress_current_guid = get_post_field( 'guid', $affiliatepress_post_id );
            $affiliatepress_where = array( 'ID' => $affiliatepress_post_id );
            if( '' === $affiliatepress_current_guid ){
                $wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $affiliatepress_post_id ) ), $affiliatepress_where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            }            
            $AffiliatePress->affiliatepress_update_settings('affiliate_registration_page_id', 'affiliate_settings', $affiliatepress_post_id);


        }

        
        /**
         * Function for install all setting
         *
         * @return array
        */
        function affiliatepress_install_all_settings(){

            $wordpress_admin_email = get_bloginfo('admin_email');
            $wordpress_sitename    = get_bloginfo('name');

            $affiliatepress_confirm_password_settings = array(
                'enable_confirm_password' => 'true',
                'confirm_password_label' => esc_html__('Confirm Password', 'affiliatepress-affiliate-marketing'),
                'confirm_password_placeholder' => esc_html__('Enter your Confirm password', 'affiliatepress-affiliate-marketing'),
                'confirm_password_error_msg' => esc_html__('Please enter your confirm password', 'affiliatepress-affiliate-marketing'),
                'confirm_password_validation_msg' => esc_html__('Confirm password do not match', 'affiliatepress-affiliate-marketing'),
            );
            
            $affiliatepress_default_settings = array(
                array('ap_setting_name' => 'affiliate_user_self_closed_account','ap_setting_value' => 'false','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'allow_affiliate_registration','ap_setting_value' => 'true','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_default_status','ap_setting_value' => 'true','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'tracking_cookie_days','ap_setting_value' => '30','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'integer'),
                array('ap_setting_name' => 'affiliate_link_limit','ap_setting_value' => '50','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'integer'),
                array('ap_setting_name' => 'affiliate_url_parameter','ap_setting_value' => 'afref','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'enable_fancy_affiliate_url','ap_setting_value' => 'false','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_usage_stats','ap_setting_value' => 'false','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'default_discount_val','ap_setting_value' => '10','ap_setting_type' => 'commissions_settings','auto_load'=>1,'type'=>'float'),
                array('ap_setting_name' => 'default_discount_type','ap_setting_value' => 'percentage','ap_setting_type' => 'commissions_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'default_commission_status','ap_setting_value' => '2','ap_setting_type' => 'commissions_settings','auto_load'=>1,'type'=>'text'),                
                array('ap_setting_name' => 'flat_rate_commission_basis','ap_setting_value' => 'pre_product','ap_setting_type' => 'commissions_settings','auto_load'=>1,'type'=>'text'),
                // array('ap_setting_name' => 'exclude_shipping','ap_setting_value' => 'true','ap_setting_type' => 'commissions_settings'),
                // array('ap_setting_name' => 'exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'commissions_settings'),
                array('ap_setting_name' => 'earn_commissions_own_orders','ap_setting_value' => 'false','ap_setting_type' => 'commissions_settings','auto_load'=>1,'type'=>'text'),
                // array('ap_setting_name' => 'reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'commissions_settings'),
                array('ap_setting_name' => 'allow_zero_amount_commission','ap_setting_value' => 'true','ap_setting_type' => 'commissions_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'refund_grace_period','ap_setting_value' => '10','ap_setting_type' => 'commissions_settings','auto_load'=>0,'type'=>'integer'),
                array('ap_setting_name' => 'minimum_payment_amount','ap_setting_value' => '10','ap_setting_type' => 'commissions_settings','auto_load'=>1,'type'=>'float'),
                array('ap_setting_name' => 'minimum_payment_order','ap_setting_value' => '1','ap_setting_type' => 'commissions_settings','auto_load'=>1,'type'=>'text'),    
                array('ap_setting_name' => 'commission_billing_cycle','ap_setting_value' => 'monthly','ap_setting_type' => 'commissions_settings','auto_load'=>0,'type'=>'text'),                
                array('ap_setting_name' => 'day_of_billing_cycle','ap_setting_value' => '3','ap_setting_type' => 'commissions_settings','auto_load'=>0,'type'=>'integer'),
                array('ap_setting_name' => 'payment_default_currency','ap_setting_value' => 'USD','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'currency_symbol_position','ap_setting_value' => 'before','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'default_url_type','ap_setting_value' => 'affiliate_default_url','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'currency_separator','ap_setting_value' => 'comma-dot','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'number_of_decimals','ap_setting_value' => '2','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'integer'),
                array('ap_setting_name' => 'enable_woocommerce','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'woocommerce_exclude_shipping','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'woocommerce_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'woocommerce_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),

                array('ap_setting_name' => 'enable_accept_stripe_payments','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1),
                array('ap_setting_name' => 'accept_stripe_payments_exclude_shipping','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0),
                array('ap_setting_name' => 'accept_stripe_payments_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0),
                array('ap_setting_name' => 'accept_stripe_payments_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),

                array('ap_setting_name' => 'enable_forminator_forms','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1),

                array('ap_setting_name' => 'enable_armember','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'armember_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'armember_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_restrict_content','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'restrict_content_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_paid_membership_pro','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'enable_easy_digital_downloads','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'easy_digital_downloads_exclude_shipping','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'easy_digital_downloads_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'easy_digital_downloads_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'easy_digital_downloads_disable_commission_on_upgrade','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'memberpress_disable_commission_on_upgrade','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_surecart','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'surecart_exclude_shipping','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'surecart_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'surecart_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_ecwid_ecommerce','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'enable_wp_easycart','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'wp_easycart_exclude_shipping','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'wp_easycart_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'wp_easycart_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_learndash','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'enable_gamipress','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'enable_give_wp','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'give_wp_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_wp_forms','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'enable_masteriyo_lms','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1),
                array('ap_setting_name' => 'masteriyo_lms_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0),
                array('ap_setting_name' => 'enable_memberpress','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),

                array('ap_setting_name' => 'enable_paid_memberships_subscriptions','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'paid_memberships_subscriptions_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paid_memberships_subscriptions_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),

                array('ap_setting_name' => 'memberpress_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'memberpress_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_ultimate_membership_pro','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'ultimate_membership_pro_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'ultimate_membership_pro_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_simple_membership','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'enable_wp_members_membership','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),                
                array('ap_setting_name' => 'enable_learn_dash','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'learn_dash_exclude_taxes','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'learn_dash_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_lifter_lms','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'lifter_lms_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_learnpress','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'learnpress_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_tutor_lms','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'enable_download_manager','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'download_manager_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'enable_bookingpress','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'bookingpress_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'selected_mail_service','ap_setting_value' => 'wp_mail','ap_setting_type' => 'email_notification_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'sender_name','ap_setting_value' => $wordpress_sitename,'ap_setting_type' => 'email_notification_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'sender_email','ap_setting_value' => $wordpress_admin_email,'ap_setting_type' => 'email_notification_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'admin_email','ap_setting_value' => $wordpress_admin_email,'ap_setting_type' => 'email_notification_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'company_name','ap_setting_value' => $wordpress_sitename,'ap_setting_type' => 'email_notification_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'auto_approve_commission_after_days','ap_setting_value' => '0','ap_setting_type' => 'commissions_settings','auto_load'=>1,'type'=>'text'),

                array('ap_setting_name' => 'login_error_message','ap_setting_value' => esc_html__('Username/Password you have entered is invalid.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_register_with_auto_approved','ap_setting_value' => esc_html__('Your registration is now complete. You can login to the affiliate area and begin promoting.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_register_with_pending','ap_setting_value' => esc_html__('Your application has been submitted. We shall contact you soon.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'username_already_exists','ap_setting_value' => esc_html__('Username already exists. Please choose a different one.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'email_already_exists','ap_setting_value' => esc_html__('Email already exists. Please use a different email address.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_registration_disabled','ap_setting_value' => esc_html__('Affiliate registration is currently disabled by the admin. Please contact support for assistance.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_is_not_allowed','ap_setting_value' => esc_html__('Login is not allowed. Please contact the admin for assistance.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_wrong_email','ap_setting_value' => esc_html__('There is no affiliate user registered with that email address.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'send_password_reset_link','ap_setting_value' => esc_html__('We have sent you a password reset link, Please check your mail.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'account_closure_request_success','ap_setting_value' => esc_html__('Your account closure request email has been sent successfully. If you have any further questions or need assistance, please feel free to reach out.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_custom_link_added','ap_setting_value' => esc_html__('Affiliate custom link successfully added.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'campaign_name_already_added','ap_setting_value' => esc_html__('Sorry, your added campaign name and sub id is already added.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'incorrect_current_password','ap_setting_value' => esc_html__('Current password is incorrect.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'new_and_current_password_not_match','ap_setting_value' => esc_html__('New password and confirm password do not match.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'password_successfully_updated','ap_setting_value' => esc_html__('Password successfully updated.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'profile_fields_successfully_updated','ap_setting_value' => esc_html__('Affiliate profile fields successfully updated.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_pending_register_message','ap_setting_value' => esc_html__('Your affiliate account is currently under review. You will be notified by email once it has been approved.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_user_block_message','ap_setting_value' => esc_html__('Sorry, Affiliate user temporarily blocked by admin.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_limit_reached_error','ap_setting_value' => esc_html__('You cannot add a custom affiliate link because the maximum limit has been reached.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_link_delete','ap_setting_value' => esc_html__('Affiliate Link has been deleted successfully.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_copied','ap_setting_value' => esc_html__('Link copied successfully.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'file_upload_type_validation','ap_setting_value' => esc_html__('Please upload jpg,jpeg,png or webp file only.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'file_upload_limit_validation','ap_setting_value' => esc_html__('Please upload maximum 1 MB file only.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'not_allow_affiliate_register','ap_setting_value' => esc_html__('Sorry! you are not allowed to access the affiliate panel.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_empty_validation','ap_setting_value' => esc_html__('Please add page link.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_pattern_validation','ap_setting_value' => esc_html__('Please enter a valid URL.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_campaign_name_empty_validation','ap_setting_value' => esc_html__('Please add campaign name.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_already_registered_message','ap_setting_value' => esc_html__('Your account is already registered as an affiliate.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'required_field_validation','ap_setting_value' => esc_html__('This field is required.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'login_error_message','ap_setting_value' => esc_html__('Dashboard', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                //form label
                array('ap_setting_name' => 'dashboard_menu','ap_setting_value' => esc_html__('Dashboard', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'commission_menu','ap_setting_value' => esc_html__('Commission', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_links_menu','ap_setting_value' => esc_html__('Affiliates Links', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visits_menu','ap_setting_value' => esc_html__('Visits', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_menu','ap_setting_value' => esc_html__('Creatives', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paymnets_menu','ap_setting_value' => esc_html__('Payments', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_affiliate_dashboard','ap_setting_value' => esc_html__('Affiliate Dashboard', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_total_earnings','ap_setting_value' => esc_html__('Total Earning', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_paid_earnings','ap_setting_value' => esc_html__('Paid Earning', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_unpaid_earnings','ap_setting_value' => esc_html__('Unpaid Earning', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_visits_count','ap_setting_value' => esc_html__('Visits', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_commissions_count','ap_setting_value' => esc_html__('Commissions', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_commission_rate','ap_setting_value' => esc_html__('Commission Rate', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_chart_earnings','ap_setting_value' => esc_html__('Earnings', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_chart_commisisons','ap_setting_value' => esc_html__('Commissions', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'dashboard_reports','ap_setting_value' => esc_html__('Reports', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'commission_affiliate_commission','ap_setting_value' => esc_html__('Affiliate Commission', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'commission_select_status','ap_setting_value' => esc_html__('Select Status', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'commission_id','ap_setting_value' => esc_html__('Commission ID', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'commission_product','ap_setting_value' => esc_html__('Product', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'commission_date','ap_setting_value' => esc_html__('Date', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'commission_amount','ap_setting_value' => esc_html__('Commission', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'commission_status','ap_setting_value' => esc_html__('Status', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_affiliate_links','ap_setting_value' => esc_html__('Affiliate Links', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_description','ap_setting_value' => esc_html__('This is your referral URL. Share it with your audience to earn commissions.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_your_affiliate_link','ap_setting_value' => esc_html__('Your Affiliate Link', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_parameter_description','ap_setting_value' => esc_html__('will work in all page.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_cookie_duration','ap_setting_value' => esc_html__('Cookie Duration', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_cookie_duration_description','ap_setting_value' => esc_html__('Defines the duration for which the affiliate tracking cookie remains active after a user clicks a referral link.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_custome_Affiliate_links','ap_setting_value' => esc_html__('Custom Affiliate Links', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_generate_affiliate_link','ap_setting_value' => esc_html__('Generate Affiliate Link', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_serial_number','ap_setting_value' => esc_html__('ID', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_campaign_name','ap_setting_value' => esc_html__('Campaign Name', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_affiliate_url','ap_setting_value' => esc_html__('Affiliate URL', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_click_to_copy','ap_setting_value' => esc_html__('Click to Copy', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_generate_custome_affiliate_links','ap_setting_value' => esc_html__('Generate Custom Affiliate Links', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_generate_link_description','ap_setting_value' => esc_html__('Add any URL from this website in the field below to generate a referral link.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_page_url','ap_setting_value' => esc_html__('Page URL', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_enter_page_url','ap_setting_value' => esc_html__('Enter Page URL', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_compaign_name','ap_setting_value' => esc_html__('Campaign Name', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_enter_compaign_name','ap_setting_value' => esc_html__('Enter Campaign Name', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_sub_id','ap_setting_value' => esc_html__('Sub ID', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_enter_sub_id','ap_setting_value' => esc_html__('Enter Sub ID', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'link_generate_link','ap_setting_value' => esc_html__('Generate Link', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_visits','ap_setting_value' => esc_html__('Visits', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_select_type','ap_setting_value' => esc_html__('Visit Type', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_serial_number','ap_setting_value' => esc_html__('ID', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_date','ap_setting_value' => esc_html__('Date', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_compaign','ap_setting_value' => esc_html__('Campaign', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_ip_address','ap_setting_value' => esc_html__('IP Address', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_converted','ap_setting_value' => esc_html__('Converted', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_all','ap_setting_value' => esc_html__('All Visits', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_unconverted_status','ap_setting_value' => esc_html__('Not converted', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_landing_url','ap_setting_value' => esc_html__('Landing URL', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'visit_referrer_url','ap_setting_value' => esc_html__('Referrer URL', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_title','ap_setting_value' => esc_html__('Creative', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_enter_creative_name','ap_setting_value' => esc_html__('Enter Creative Name', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_select_type','ap_setting_value' => esc_html__('Select Type', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_download','ap_setting_value' => esc_html__('Download', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_preview','ap_setting_value' => esc_html__('Preview', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_html_code','ap_setting_value' => esc_html__('HTML Code', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_copy_code','ap_setting_value' => esc_html__('Copy Code', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_image','ap_setting_value' => esc_html__('Image', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_text_link','ap_setting_value' => esc_html__('Text Link', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_name','ap_setting_value' => esc_html__('Creative Name', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'creative_type','ap_setting_value' => esc_html__('Creative Type', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paymnet_title','ap_setting_value' => esc_html__('Payments', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paymnet_select_status','ap_setting_value' => esc_html__('Select Status', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'payment_minimum_amount_label','ap_setting_value' => esc_html__('Minimum Amount', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paymnet_id','ap_setting_value' => esc_html__('Payment ID', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paymnet_date','ap_setting_value' => esc_html__('Date', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'filters','ap_setting_value' => esc_html__('Filters', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paymnet_method','ap_setting_value' => esc_html__('Payment Method', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paymnet_amount','ap_setting_value' => esc_html__('Amount', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paymnet_status','ap_setting_value' => esc_html__('Status', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'edit_details','ap_setting_value' => esc_html__('Edit Details', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'profile_details','ap_setting_value' => esc_html__('Profile Details', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'profile_picture','ap_setting_value' => esc_html__('Profile Picture', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'change_button','ap_setting_value' => esc_html__('Change', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'remove_button','ap_setting_value' => esc_html__('Remove', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'paymnet_detail','ap_setting_value' => esc_html__('Payment Detail', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'save_changes','ap_setting_value' => esc_html__('Save Changes', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'chnage_password','ap_setting_value' => esc_html__('Change Password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'current_password','ap_setting_value' => esc_html__('Current Password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'new_password','ap_setting_value' => esc_html__('New Password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'confirm_new_password','ap_setting_value' => esc_html__('Confirm New Password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'save_password','ap_setting_value' => esc_html__('Save Password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'delete_account','ap_setting_value' => esc_html__('Delete Account', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'delete_account_description','ap_setting_value' => esc_html__('Permanently delete your account and data.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'log_out','ap_setting_value' => esc_html__('Logout', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'create_an_account','ap_setting_value' => esc_html__('Create an account', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'create_account_description','ap_setting_value' => esc_html__('Enter your details to create your affiliate account', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'create_account_button','ap_setting_value' => esc_html__('Create Account', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'do_you_have_account','ap_setting_value' => esc_html__('Do you have an account?', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'signin','ap_setting_value' => esc_html__('Sign in', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_signin','ap_setting_value' => esc_html__('Sign in', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_login_description','ap_setting_value' => esc_html__('Stay updated on your professional world', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_username_empty_validation','ap_setting_value' => esc_html__('Please enter username or email address', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_password_empty_validation','ap_setting_value' => esc_html__('Please enter password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_user_name','ap_setting_value' => esc_html__('Username or Email Address', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_user_name_placeholder','ap_setting_value' => esc_html__('Enter Email Address', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'delete_account_confirmation_msg','ap_setting_value' => esc_html__('Are you sure you want to delete your account?', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'delete_account_cancel_button','ap_setting_value' => esc_html__('Cancel', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'delete_account_close_button','ap_setting_value' => esc_html__('Delete', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'delete_account_confirmation_description','ap_setting_value' => esc_html__('Delete the account will be delete all the records under the account.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_password','ap_setting_value' => esc_html__('Password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_password_placeholder','ap_setting_value' => esc_html__('Enter Password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_remember_me','ap_setting_value' => esc_html__('Remember Me', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_forgot_password','ap_setting_value' => esc_html__('Forgot Password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_signin_button','ap_setting_value' => esc_html__('Sign in', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_dont_have_account','ap_setting_value' => sanitize_text_field(__("Don't have an account?", 'affiliatepress-affiliate-marketing')) ,'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'login_create_account','ap_setting_value' => esc_html__('Create account ', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'forget_password_label','ap_setting_value' => esc_html__('Forgot Password', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'forget_password_description','ap_setting_value' => esc_html__('Stay updated on your professional world', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'forget_password_email','ap_setting_value' => esc_html__('Email Address', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'forget_password_empty_validation','ap_setting_value' => esc_html__('Please enter email address', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'forget_password_placeholder','ap_setting_value' => esc_html__('Enter Email Address', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'forget_password_button','ap_setting_value' => esc_html__('Submit', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'forget_password_signin','ap_setting_value' => esc_html__('Sign in ', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'apply','ap_setting_value' => esc_html__('Apply', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'reset','ap_setting_value' => esc_html__('Reset', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'pagination','ap_setting_value' => esc_html__('Showing [start] out of [total]', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'start_date','ap_setting_value' => esc_html__('Start date', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'end_date','ap_setting_value' => esc_html__('End date', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'no_data','ap_setting_value' => esc_html__('No Data Found!', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'no_data_description','ap_setting_value' => esc_html__('but the journey has just begun. Let every click will write your story.', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'pagination_change_label','ap_setting_value' => esc_html__('Per Page', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'custome_link_delete_confirm','ap_setting_value' => esc_html__('Are you sure you want to delete this Link?', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'delete_custome_link_label','ap_setting_value' => esc_html__('Delete', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'no_label','ap_setting_value' => esc_html__('No', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                array('ap_setting_name' => 'yes_label','ap_setting_value' => esc_html__('Yes', 'affiliatepress-affiliate-marketing'),'ap_setting_type' => 'message_settings','auto_load'=>0,'type'=>'text'),
                //complete form label
                array('ap_setting_name' => 'enable_ninjaforms','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),                
                array('ap_setting_name' => 'enable_paid_memberships_pro','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'paid_memberships_pro_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0,'type'=>'text'),

                array('ap_setting_name' => 'enable_gravity_forms','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'enable_wp_simple_pay','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1),

                array('ap_setting_name' => 'enable_getpaid','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1),
                array('ap_setting_name' => 'getpaid_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>1),

                array('ap_setting_name' => 'enable_member_mouse','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1),
                array('ap_setting_name' => 'member_mouse_reject_commission_on_refund','ap_setting_value' => 'true','ap_setting_type' => 'integrations_settings','auto_load'=>0),

                array('ap_setting_name' => 'enable_arforms','ap_setting_value' => 'false','ap_setting_type' => 'integrations_settings','auto_load'=>1),

                array('ap_setting_name' => 'primary_color','ap_setting_value' => '#6858e0','ap_setting_type' => 'appearance_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'background_color','ap_setting_value' => '#ffffff','ap_setting_type' => 'appearance_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'panel_background_color','ap_setting_value' => '#ffffff','ap_setting_type' => 'appearance_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'text_color','ap_setting_value' => '#1A1E26','ap_setting_type' => 'appearance_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'content_color','ap_setting_value' => '#576582','ap_setting_type' => 'appearance_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'border_color','ap_setting_value' => '#C9CFDB','ap_setting_type' => 'appearance_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'font','ap_setting_value' => 'Poppins','ap_setting_type' => 'appearance_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'affiliate_account_page_id','ap_setting_value' => '','ap_setting_type' => 'affiliate_settings','auto_load'=>1,'type'=>'text'),
                array('ap_setting_name' => 'confirm_password_field','ap_setting_value' => maybe_serialize($affiliatepress_confirm_password_settings),'ap_setting_type' => 'field_settings','auto_load'=>1,'type'=>'text'),
              );

              $affiliatepress_default_settings = apply_filters('affiliatepress_auto_load_settings' , $affiliatepress_default_settings);

              return $affiliatepress_default_settings;

        }

        /**
         * Function for install default settings data
         *
         * @return void
        */
        function affiliatepress_install_default_settings_data(){
            global $wpdb, $affiliatepress_tbl_ap_settings, $AffiliatePress;
            $wordpress_admin_email = get_bloginfo('admin_email');
            $wordpress_sitename    = get_bloginfo('name');
            $affiliatepress_default_settings = $AffiliatePress->affiliatepress_install_all_settings();
            foreach($affiliatepress_default_settings as $affiliatepress_default_setting){
                $affiliatepress_if_setting_exists = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_settings, 'COUNT(ap_setting_id)', ' WHERE ap_setting_name = %s AND ap_setting_type = %s', array( $affiliatepress_default_setting['ap_setting_name'], $affiliatepress_default_setting['ap_setting_type']), '', '', '', true, false,ARRAY_A));
                if($affiliatepress_if_setting_exists == 0){
                    $AffiliatePress->affiliatepress_update_settings($affiliatepress_default_setting['ap_setting_name'], $affiliatepress_default_setting['ap_setting_type'], $affiliatepress_default_setting['ap_setting_value']);
                }
            }
            $AffiliatePress->affiliatepress_update_all_auto_load_settings();
        }
        
        /**
         * Function for affiliate fields add
         *
         * @return void
        */
        function affiliatepress_install_default_affiliate_fields_data(){

            global $wpdb, $AffiliatePress, $affiliatepress_tbl_ap_affiliate_form_fields;

            $affiliatepress_terms_condition_page_link = "#";
            /* Terms & Conditions Page Add Start */
            $affiliatepress_post_table = $wpdb->posts;
            $affiliatepress_post_author = get_current_user_id();
            $affiliatepress_affiliate_panel_content = '';
            $affiliatepress_affiliate_panel_page_details = array(
                'post_title'    => esc_html__('Terms & Conditions', 'affiliatepress-affiliate-marketing'),
                'post_name'     => 'affiliate-terms-and-conditions',
                'post_content'  => $affiliatepress_affiliate_panel_content,
                'post_status'   => 'publish',
                'post_parent'   => 0,
                'post_author'   => 1,
                'post_type'     => 'page',
                'post_author'   => $affiliatepress_post_author,
                'post_date'     => current_time( 'mysql' ),
                'post_date_gmt' => current_time( 'mysql', 1 ),
            );
            $wpdb->insert( $affiliatepress_post_table, $affiliatepress_affiliate_panel_page_details ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $affiliatepress_post_id = $wpdb->insert_id; 
            $affiliatepress_current_guid = get_post_field( 'guid', $affiliatepress_post_id );
            $affiliatepress_where = array( 'ID' => $affiliatepress_post_id );
            if( '' === $affiliatepress_current_guid ){
                $wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $affiliatepress_post_id ) ), $affiliatepress_where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            }            
            $AffiliatePress->affiliatepress_update_settings('affiliate_terms_and_condition_page_id', 'affiliate_settings', $affiliatepress_post_id);            
            /* Terms & Conditions Page Add Over */
            if($affiliatepress_post_id){
                $affiliatepress_terms_condition_page_link = get_permalink($affiliatepress_post_id);
            }

            $affiliatepress_form_fields_default_data = array(
                'firstname'     => array(
                    'field_name'     => 'firstname',
                    'field_type'     => 'Text',
                    'is_edit'        => 0,
                    'is_required'    => 1,
                    'label'          => wp_kses_post( esc_html__('First Name', 'affiliatepress-affiliate-marketing') ),
                    'placeholder'    => wp_kses_post( esc_html__('Enter your first name', 'affiliatepress-affiliate-marketing') ),
                    'error_message'  => wp_kses_post( esc_html__('Please enter your first name', 'affiliatepress-affiliate-marketing') ),
                    'show_sign_up'   => 1,
                    'show_profile'   => 1,
                    'field_position' => 1,
                    'is_default'     => 1,
                ),
                'lastname'      => array(
                    'field_name'     => 'lastname',
                    'field_type'     => 'Text',
                    'is_edit'        => 0,
                    'is_required'    => 1,
                    'label'          => wp_kses_post( esc_html__('Last Name', 'affiliatepress-affiliate-marketing') ),
                    'placeholder'    => wp_kses_post( esc_html__('Enter your last name', 'affiliatepress-affiliate-marketing') ),
                    'error_message'  => wp_kses_post( esc_html__('Please enter your last name', 'affiliatepress-affiliate-marketing') ),
                    'show_sign_up'   => 1,
                    'show_profile'   => 1,
                    'field_position' => 2,
                    'is_default'     => 1,
                ),
                'username'      => array(
                    'field_name'     => 'username',
                    'field_type'     => 'Text',
                    'is_edit'        => 0,
                    'is_required'    => 1,
                    'label'          => wp_kses_post( esc_html__('Username', 'affiliatepress-affiliate-marketing') ),
                    'placeholder'    => wp_kses_post( esc_html__('Enter your Username', 'affiliatepress-affiliate-marketing') ),
                    'error_message'  => wp_kses_post(esc_html__('Please enter your Username', 'affiliatepress-affiliate-marketing') ),
                    'show_sign_up'   => 1,
                    'show_profile'   => 1,
                    'field_position' => 3,
                    'is_default'     => 1,
                ),                
                'email_address' => array(
                    'field_name'     => 'email',
                    'field_type'     => 'Email',
                    'is_edit'        => 0,
                    'is_required'    => 1,
                    'label'          => wp_kses_post( esc_html__('User Email', 'affiliatepress-affiliate-marketing') ),
                    'placeholder'    => wp_kses_post( esc_html__('Enter your email address', 'affiliatepress-affiliate-marketing') ),
                    'error_message'  => wp_kses_post( esc_html__('Please enter valid email address', 'affiliatepress-affiliate-marketing') ),
                    'show_sign_up'   => 1,
                    'show_profile'   => 1,
                    'field_position' => 4,
                    'is_default'     => 1,
                ),
                'password'  => array(
                    'field_name'     => 'password',
                    'field_type'     => 'Password',
                    'is_edit'        => 0,
                    'is_required'    => 1,
                    'label'          => wp_kses_post( esc_html__('Password', 'affiliatepress-affiliate-marketing') ),
                    'placeholder'    => wp_kses_post( esc_html__('Enter your password', 'affiliatepress-affiliate-marketing') ),
                    'error_message'  => wp_kses_post( esc_html__('Please enter your password', 'affiliatepress-affiliate-marketing') ),
                    'show_sign_up'   => 1,
                    'show_profile'   => 1,
                    'field_position' => 5,
                    'is_default'     => 1,
                ),
                'ap_affiliates_payment_email'  => array(
                    'field_name'     => 'ap_affiliates_payment_email',
                    'field_type'     => 'Text',
                    'is_edit'        => 1,
                    'is_required'    => 1,
                    'label'          => wp_kses_post( esc_html__('Payout Email', 'affiliatepress-affiliate-marketing') ),
                    'placeholder'    => wp_kses_post( esc_html__('Enter payment email', 'affiliatepress-affiliate-marketing') ),
                    'error_message'  => wp_kses_post(esc_html__('Please enter valid payment email', 'affiliatepress-affiliate-marketing') ),
                    'show_sign_up'   => 1,
                    'show_profile'   => 1,
                    'field_position' => 6,
                    'is_default'     => 1,
                ),
                'ap_affiliates_website'  => array(
                    'field_name'     => 'ap_affiliates_website',
                    'field_type'     => 'Text',
                    'is_edit'        => 1,
                    'is_required'    => 1,
                    'label'          => wp_kses_post( esc_html__('Website', 'affiliatepress-affiliate-marketing') ),
                    'placeholder'    => wp_kses_post( esc_html__('Enter website link', 'affiliatepress-affiliate-marketing') ),
                    'error_message'  => wp_kses_post(esc_html__('Please enter website link', 'affiliatepress-affiliate-marketing') ),
                    'show_sign_up'   => 1,
                    'show_profile'   => 1,
                    'field_position' => 7,
                    'is_default'     => 1,
                ),                                
                'ap_affiliates_promote_us' => array(
                    'field_name'     => 'ap_affiliates_promote_us',
                    'field_type'     => 'Textarea',
                    'is_edit'        => 1,
                    'is_required'    => 0,
                    'label'          => wp_kses_post( esc_html__('How Will You Promote Us?', 'affiliatepress-affiliate-marketing') ),
                    'placeholder'    => wp_kses_post( esc_html__('Enter how you promote us', 'affiliatepress-affiliate-marketing') ),
                    'error_message'  => wp_kses_post( esc_html__('Please add promote us detail', 'affiliatepress-affiliate-marketing') ),
                    'show_sign_up'   => 1,
                    'show_profile'   => 1,                    
                    'field_position' => 8,
                    'is_default'     => 1,
                ),
                'terms_condition'    => array(
                    'field_name'     => 'terms_and_conditions',
                    'field_type'     => 'terms_and_conditions',
                    'is_edit'        => 1,
                    'is_required'    => 1,
                    'placeholder'    => '',
                    'label'          => str_replace( '&amp;', '&',  esc_html__('I agree with', 'affiliatepress-affiliate-marketing').' <a target="_blank" href="'.$affiliatepress_terms_condition_page_link.'">'.esc_html__('terms & conditions ', 'affiliatepress-affiliate-marketing').'</a>'),
                    'error_message'  => wp_kses_post( esc_html__('Please tick this box if you want to proceed', 'affiliatepress-affiliate-marketing') ),
                    'show_sign_up'   => 1,
                    'show_profile'   => 0,
                    'field_position' => 9,
                    'is_default'     => 1,
                )
            );

            foreach($affiliatepress_form_fields_default_data as $affiliatepress_form_field_key => $affiliatepress_form_field_val){
                
                $affiliatepress_if_field_exists = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_affiliate_form_fields, 'COUNT(ap_form_field_id)', ' WHERE ap_form_field_name = %s ', array( $affiliatepress_form_field_val['field_name']), '', '', '', true, false,ARRAY_A));

                if($affiliatepress_if_field_exists == 0){
                    $affiliatepress_form_field_db_data = array(
                        'ap_form_field_name'      => $affiliatepress_form_field_val['field_name'],
                        'ap_form_field_type'      => $affiliatepress_form_field_val['field_type'],
                        'ap_field_required'       => $affiliatepress_form_field_val['is_required'],
                        'ap_field_label'          => stripslashes_deep($affiliatepress_form_field_val['label']),
                        'ap_field_placeholder'    => $affiliatepress_form_field_val['placeholder'],
                        'ap_field_error_message'  => $affiliatepress_form_field_val['error_message'],
                        'ap_show_signup_field'    => $affiliatepress_form_field_val['show_sign_up'],
                        'ap_show_profile_field'   => $affiliatepress_form_field_val['show_profile'],                        
                        'ap_field_position'       => $affiliatepress_form_field_val['field_position'],
                        'ap_field_is_default'     => 1,
                        'ap_field_edit'           => $affiliatepress_form_field_val['is_edit']
                    );
                    $wpdb->insert($affiliatepress_tbl_ap_affiliate_form_fields, $affiliatepress_form_field_db_data); // phpcs:ignore WordPress.DB.DirectDatabaseQuery               
                }

            }

        }

        function affiliatepress_install_default_creative_data(){

            global $affiliatepress_tbl_ap_creative;

            $affiliatepress_creative = array();

            $affiliatepress_creative[] = array(
                'ap_creative_name'              => esc_html__('Creative 1', 'affiliatepress-affiliate-marketing'),
                'ap_creative_description'       => '',
                'ap_creative_type'              => 'image',                
                'ap_creative_alt_text'          => '',
                'ap_creative_status'            => '1',
                'ap_creative_text'              => '',
                'ap_creative_landing_url'       => site_url(),
                'ap_creative_image_url'         => 'creative_image_1.jpg'
            );

            $affiliatepress_creative[] = array(
                'ap_creative_name'              => esc_html__('Creative 2', 'affiliatepress-affiliate-marketing'),
                'ap_creative_description'       => '',
                'ap_creative_type'              => 'image',                
                'ap_creative_alt_text'          => '',
                'ap_creative_status'            => '1',
                'ap_creative_text'              => '',
                'ap_creative_landing_url'       => site_url(),
                'ap_creative_image_url'         => 'creative_image_2.jpg'
            );

            $affiliatepress_creative[] = array(
                'ap_creative_name'              => esc_html__('Creative 3', 'affiliatepress-affiliate-marketing'),
                'ap_creative_description'       => '',
                'ap_creative_type'              => 'image',              
                'ap_creative_alt_text'          => '',
                'ap_creative_status'            => '1',
                'ap_creative_text'              => '',
                'ap_creative_landing_url'       => site_url(),
                'ap_creative_image_url'         => 'creative_image_3.jpg'
            );
            
            $affiliatepress_creative_rows = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_creative, 'ap_creative_id', '', array(), '', '', '', false, false,ARRAY_A);
            
            $affiliatepress_affiliates_creative_count = count($affiliatepress_creative_rows);

            if ( ! function_exists( 'WP_Filesystem' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
        
            global $wp_filesystem;
            WP_Filesystem();

            foreach ($affiliatepress_creative as $affiliatepress_creative_value) {

                $affiliatepress_creative_rows = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_creative, 'ap_creative_id', '', array(), '', '', '', false, false,ARRAY_A);

                $affiliatepress_affiliates_creative_count = count($affiliatepress_creative_rows);
                
                if($affiliatepress_affiliates_creative_count < 3){

                    $affiliatepress_user_img_name = isset($affiliatepress_creative_value['ap_creative_image_url']) ? sanitize_text_field($affiliatepress_creative_value['ap_creative_image_url']) : '';
                    $source_path = AFFILIATEPRESS_IMAGES_DIR.'/' .$affiliatepress_user_img_name;
                    $affiliatepress_upload_dir                 = AFFILIATEPRESS_UPLOAD_DIR . '/';
                    $affiliatepress_new_file_name = current_time('timestamp') . '_' . $affiliatepress_user_img_name;
                    $destination_path                = $affiliatepress_upload_dir . $affiliatepress_new_file_name;

                    if ( $wp_filesystem->exists( $source_path ) ) {
                        $file_content = $wp_filesystem->get_contents( $source_path );
                        if ( false !== $file_content ) {
                            $wp_filesystem->put_contents( $destination_path, $file_content, FS_CHMOD_FILE );
                            $affiliatepress_creative_value['ap_creative_image_url'] = $affiliatepress_new_file_name;
                        }
                    }

                    $affiliatepress_creative_id = $this->affiliatepress_insert_record($affiliatepress_tbl_ap_creative, $affiliatepress_creative_value);   
                }
            }

        }

        /**
         * Function for install default notification data
         *
         * @return void
        */
        function affiliatepress_install_default_notification_data(){
            
            global $wpdb, $affiliatepress_tbl_ap_notifications;
            $affiliatepress_default_notifications_name_arr = array();

            $affiliatepress_default_notifications_name_arr[] = array(
                'ap_notification_name' => esc_html__('Account Pending', 'affiliatepress-affiliate-marketing'),
                'ap_notification_slug' => 'affiliate_account_pending',
                'ap_notification_subject' => esc_html__('Account Pending', 'affiliatepress-affiliate-marketing'),
                'ap_notification_message_admin' => sprintf( esc_html__('Hey admin,%1$s The user %2$s (%3$s) just signed up for the affiliate program. it is waiting for a confirmation. %4$sThanks.','affiliatepress-affiliate-marketing'), '<br>','%affiliate_username%', '%affiliate_email%', '<br/>'),// phpcs:ignore
                'ap_notification_message_affiliate' => sprintf( esc_html__('Hey %1$s, %2$s Thank you for your application! We wanted to inform you that your account is currently in pending status. Our team is reviewing your details, and we will notify you as soon as your account is approved or if we need any additional information. We appreciate your patience during this process! Thank you for choosing us,%3$s%4$s','affiliatepress-affiliate-marketing'), '%affiliate_first_name%', '<br/>','<br/>', '%company_name%' ),// phpcs:ignore
                'ap_notification_type' => 'affiliate',
                'ap_notification_status_affiliate' => 1,
                'ap_notification_status_admin' => 1,
            );

            $affiliatepress_default_notifications_name_arr[] = array(
                'ap_notification_name' => esc_html__('Account Approved', 'affiliatepress-affiliate-marketing'),
                'ap_notification_slug' => 'affiliate_account_approved',
                'ap_notification_subject' => esc_html__('Account Approved', 'affiliatepress-affiliate-marketing'),
                'ap_notification_message_admin' => sprintf( esc_html__('Hey admin, %1$s %2$s affiliate account has been approved. %3$s Thanks.','affiliatepress-affiliate-marketing'), '<br>', '%affiliate_first_name%','<br>'),// phpcs:ignore
                'ap_notification_message_affiliate' => sprintf( esc_html__('Hey %1$s, %2$s Your application for our affiliate account has been approved. %3$s Welcome to the team!. Thank you for choosing us,%4$s%5$s','affiliatepress-affiliate-marketing'), '%affiliate_first_name%', '<br>', '<br>', '<br/>', '%company_name%' ),// phpcs:ignore
                'ap_notification_type' => 'affiliate',
                'ap_notification_status_affiliate' => 1,
                'ap_notification_status_admin' => 1,                                
            );

            $affiliatepress_default_notifications_name_arr[] = array(
                'ap_notification_name' => esc_html__('Account Rejected', 'affiliatepress-affiliate-marketing'),
                'ap_notification_slug' => 'affiliate_account_rejected',
                'ap_notification_subject' => esc_html__('Account Rejected', 'affiliatepress-affiliate-marketing'),
                'ap_notification_message_admin' => sprintf( esc_html__('Hey admin, %1$s %2$s affiliate account was rejected %3$s %4$s','affiliatepress-affiliate-marketing'), '<br>', '%affiliate_first_name%','<br>' ,'%company_name%'),// phpcs:ignore
                'ap_notification_message_affiliate' => sprintf( esc_html__('Hey %1$s, %2$s Unfortunately, your application for an affiliate account was rejected. If you would like to know more about the reasons for this decision or if you believe there has been a mistake, please feel free to reach out to us. We appreciate your understanding and hope to have the opportunity to work with you in the future. %3$s %4$s','affiliatepress-affiliate-marketing'), '%affiliate_first_name%', '<br>' , '<br>' , '%company_name%' ),// phpcs:ignore
                'ap_notification_type' => 'affiliate',
                'ap_notification_status_affiliate' => 1,
                'ap_notification_status_admin' => 0,                 
            );

            $affiliatepress_default_notifications_name_arr[] = array(
                'ap_notification_name' => esc_html__('Commission Registered', 'affiliatepress-affiliate-marketing'),
                'ap_notification_slug' => 'commission_registered',
                'ap_notification_subject' => esc_html__('Commission Registered', 'affiliatepress-affiliate-marketing'),
                'ap_notification_message_admin' => sprintf( esc_html__('Hey admin, %1$s A new commission was generated for your affiliate partner %2$s (%3$s). %4$s Thanks','affiliatepress-affiliate-marketing'), '<br>', '%affiliate_first_name%', '%affiliate_email%','<br>' ),// phpcs:ignore
                'ap_notification_message_affiliate' => sprintf( esc_html__('Hey %1$s %2$s, %3$s We are excited to let you know that your commission has been successfully registered! %4$s Details: %5$s Commission ID: %6$s %7$s Commission Amount: %8$s %9$s  %10$s','affiliatepress-affiliate-marketing'), '%affiliate_first_name%', '%affiliate_last_name%','<br>', '<br>','<br>','%commission_id%','<br>', '%commission_amount%','<br>','%company_name%' ),// phpcs:ignore
                'ap_notification_type' => 'commission',
                'ap_notification_status_affiliate' => 1,
                'ap_notification_status_admin' => 1,                
            ); 
                        
            $affiliatepress_default_notifications_name_arr[] = array(
                'ap_notification_name' => esc_html__('Affiliate Commission Approved', 'affiliatepress-affiliate-marketing'),
                'ap_notification_slug' => 'commission_approved',
                'ap_notification_subject' => esc_html__('Affiliate Commission Approved', 'affiliatepress-affiliate-marketing'),
                'ap_notification_message_affiliate' => sprintf( esc_html__('Hey %1$s, %2$s We are pleased to inform you that your commission has been approved!. %3$s Details: %4$s Commission ID: %5$s %6$s Commission Amount: %7$s %8$s Thank you for your efforts and contributions. If you have any questions or need further assistance, feel free to reach out. %9$s %10$s','affiliatepress-affiliate-marketing'), '%affiliate_first_name%','<br>','<br>','<br>','%commission_id%','<br>', '%commission_amount%','<br>','<br>','%company_name%'),// phpcs:ignore
                'ap_notification_message_admin' => sprintf( esc_html__('Hey admin, %1$s %2$s rewarded a new commission of %3$s. %4$s Thanks','affiliatepress-affiliate-marketing'), '<br>','%affiliate_first_name%','%commission_amount%','<br>' ),// phpcs:ignore
                'ap_notification_type' => 'commission',
                'ap_notification_status_affiliate' => 1,
                'ap_notification_status_admin' => 1,                 
            );            
            
            $affiliatepress_default_notifications_name_arr[] = array(
                'ap_notification_name' => esc_html__('Commission Paid', 'affiliatepress-affiliate-marketing'),
                'ap_notification_slug' => 'affiliate_payment_paid',
                'ap_notification_subject' => esc_html__('Commission Paid', 'affiliatepress-affiliate-marketing'),
                'ap_notification_message_affiliate' => sprintf( esc_html__('Hey %1$s, %2$s We have processed your affiliate earnings and sent out your payment. %3$s Payment amount: %4$s %5$s Payment method: %6$s %7$s Payment ID: %8$s %9$s %10$s','affiliatepress-affiliate-marketing'),'%affiliate_first_name%','<br>','<br>','%payment_amount%','<br>','%payment_payout_method%','<br>','%payment_id%','<br>','%company_name%'),// phpcs:ignore
                'ap_notification_message_admin' => sprintf( esc_html__('Hey Admin, %1$s %2$s user payment paid. %3$s Payment amount: %4$s %5$s Payment method: %6$s %7$s Payment ID: %8$s %9$s Thanks','affiliatepress-affiliate-marketing'),'<br>','%affiliate_first_name%','<br>','%payment_amount%','<br>','%payment_payout_method%','<br>','%payment_id%','<br>'),// phpcs:ignore
                'ap_notification_type' => 'payment',
                'ap_notification_status_affiliate' => 1,
                'ap_notification_status_admin' => 1,                 
            );

            $affiliatepress_default_notifications_name_arr[] = array(
                'ap_notification_name' => esc_html__('Payout Failed', 'affiliatepress-affiliate-marketing'),
                'ap_notification_slug' => 'affiliate_payment_failed',
                'ap_notification_subject' => esc_html__('Payout Failed', 'affiliatepress-affiliate-marketing'),
                'ap_notification_message_affiliate' => sprintf( esc_html__('Hey %1$s, %2$s Unfortunately, we were unable to process your affiliate payment. %3$s Please check your payment details and try again. %4$s Payment amount: %5$s %6$s Payment method: %7$s %8$s Payment ID: %9$s %10$s %11$s If you have any questions, please contact support.','affiliatepress-affiliate-marketing'),'%affiliate_first_name%','<br>','<br>','<br>','%payment_amount%','<br>','%payment_payout_method%','<br>','%payment_id%','<br>','%company_name%'),// phpcs:ignore
                'ap_notification_message_admin' => sprintf( esc_html__('Hey Admin, %1$s %2$s user payment failed. %3$s Payment amount: %4$s %5$s Payment method: %6$s %7$s Payment ID: %8$s %9$s Please review and take necessary action.','affiliatepress-affiliate-marketing'),'<br>','%affiliate_first_name%','<br>','%payment_amount%','<br>','%payment_payout_method%','<br>','%payment_id%','<br>'),// phpcs:ignore
                'ap_notification_type' => 'payment',
                'ap_notification_status_affiliate' => 1,
                'ap_notification_status_admin' => 1,                 
            );

            foreach($affiliatepress_default_notifications_name_arr as $affiliatepress_notification_val){
                $affiliatepress_notification_receiver_types = array('affiliate','admin');
                foreach($affiliatepress_notification_receiver_types as $receiver){
                    $affiliatepress_if_notification_exists = intval($this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_notifications, 'COUNT(ap_notification_id)', ' WHERE ap_notification_slug = %s AND ap_notification_receiver_type = %s', array( $affiliatepress_notification_val['ap_notification_slug'], $receiver), '', '', '', true, false,ARRAY_A));
                    if($affiliatepress_if_notification_exists == 0){
                        $affiliatepress_args = array(
                            'ap_notification_name' => $affiliatepress_notification_val['ap_notification_name'],
                            'ap_notification_slug' => $affiliatepress_notification_val['ap_notification_slug'],
                            'ap_notification_subject' => $affiliatepress_notification_val['ap_notification_subject'],
                            'ap_notification_message' => $affiliatepress_notification_val['ap_notification_message_'.$receiver],
                            'ap_notification_type' => $affiliatepress_notification_val['ap_notification_type'],
                            'ap_notification_status' => $affiliatepress_notification_val['ap_notification_status_'.$receiver],
                            'ap_notification_receiver_type' => $receiver,
                            'ap_notification_is_custom' => 0,
                        );
                        $this->affiliatepress_insert_record($affiliatepress_tbl_ap_notifications, $affiliatepress_args);
                    }

                }
            }

        }

        
        /**
         * Get currency symbol from curreny name
         *
         * @param  mixed $affiliatepress_currency_name
         * @return void
         */
        public function affiliatepress_get_currency_symbol( $affiliatepress_currency_name ){
            if (! empty($affiliatepress_currency_name) ) {
                global $affiliatepress_global_options;
                $affiliatepress_options                    = $affiliatepress_global_options->affiliatepress_global_options();
                $affiliatepress_countries_currency_details = json_decode($affiliatepress_options['countries_json_details']);

                $affiliatepress_currency_symbol = '';

                foreach ( $affiliatepress_countries_currency_details as $affiliatepress_currency_key => $affiliatepress_currency_val ) {
                    if ($affiliatepress_currency_val->code == $affiliatepress_currency_name ) {
                        $affiliatepress_currency_symbol = $affiliatepress_currency_val->symbol;
                        break;
                    }
                }

                return $affiliatepress_currency_symbol;
            }

            return '';
        }

        
        /**
         * Function for default currency code
         *
         * @return void
        */
        public function affiliatepress_get_default_currency_code(){
            $affiliatepress_default_currency_code = $this->affiliatepress_get_settings('payment_default_currency', 'affiliate_settings');
            return $affiliatepress_default_currency_code;
        }

        /**
         * Function for get current currency symbol 
         *
         * @return void
        */
        public function affiliatepress_get_current_currency_symbol(){
            $affiliatepress_currency_name = $this->affiliatepress_get_settings('payment_default_currency', 'affiliate_settings');
            $affiliatepress_currency_symbol              = (!empty($affiliatepress_currency_name))?$this->affiliatepress_get_currency_symbol($affiliatepress_currency_name):'';
            return $affiliatepress_currency_symbol;
        }

        /**
         * Format price with currency and other general setting which applied in 'General settings'
         *
         * @param  mixed $affiliatepress_price
         * @param  mixed $affiliatepress_currency_symbol
         * @param  mixed $affiliatepress_is_symbol_added
         * @return String
        */
        public function affiliatepress_price_formatter_with_currency_symbol( $affiliatepress_price, $affiliatepress_currency_symbol = '', $affiliatepress_is_symbol_added = 1, $affiliatepress_currency_name = '' ){

            global $affiliatepress_global_options;

            $affiliatepress_decimal_points = $this->affiliatepress_get_settings('number_of_decimals', 'affiliate_settings');
            $affiliatepress_decimal_points = intval($affiliatepress_decimal_points);
            if (gettype($affiliatepress_price) == 'string' ) {
                $affiliatepress_price = floatval($affiliatepress_price);
            }
            $affiliatepress_price_separator_pos = $this->affiliatepress_get_settings('currency_separator', 'affiliate_settings');
            if ($affiliatepress_price_separator_pos == 'comma-dot' && gettype($affiliatepress_price) != 'NULL') {
                $affiliatepress_price = number_format($affiliatepress_price, $affiliatepress_decimal_points, '.', ',');
            } elseif ($affiliatepress_price_separator_pos == 'dot-comma' && gettype($affiliatepress_price) != 'NULL') {
                $affiliatepress_price = number_format($affiliatepress_price, $affiliatepress_decimal_points, ',', '.');
            } elseif ($affiliatepress_price_separator_pos == 'space-dot' && gettype($affiliatepress_price) != 'NULL') {
                $affiliatepress_price = number_format($affiliatepress_price, $affiliatepress_decimal_points, '.', ' ');
            } elseif ($affiliatepress_price_separator_pos == 'space-comma' && gettype($affiliatepress_price) != 'NULL') {
                $affiliatepress_price = number_format($affiliatepress_price, $affiliatepress_decimal_points, ',', ' ');
            } elseif ($affiliatepress_price_separator_pos == 'Custom' && gettype($affiliatepress_price) != 'NULL') {
                $affiliatepress_comma_separator = $this->affiliatepress_get_settings('custom_comma_separator', 'affiliate_settings');
                $affiliatepress_dot_separator   = $this->affiliatepress_get_settings('custom_dot_separator', 'affiliate_settings');
                if(empty($affiliatepress_comma_separator)){
                    $affiliatepress_comma_separator = ',';
                }
                if(empty($affiliatepress_dot_separator)){
                    $affiliatepress_dot_separator   = '.';
                }                                
                $affiliatepress_price                          = number_format($affiliatepress_price, $affiliatepress_decimal_points, $affiliatepress_dot_separator, $affiliatepress_comma_separator);
            }

            $affiliatepress_price_with_symbol = $affiliatepress_price;
            $affiliatepress_currency_name = $this->affiliatepress_get_settings('payment_default_currency', 'affiliate_settings');
            if($affiliatepress_is_symbol_added == 1){
                if (empty($affiliatepress_currency_symbol) ) {                    
                    $affiliatepress_currency_symbol            = ! empty($affiliatepress_currency_name) ? $this->affiliatepress_get_currency_symbol($affiliatepress_currency_name) : '';
                }
                $affiliatepress_price_symbol_position = $this->affiliatepress_get_settings('currency_symbol_position', 'affiliate_settings');      

                $affiliatepress_price_with_symbol = $affiliatepress_currency_symbol . $affiliatepress_price;
                if ($affiliatepress_price_symbol_position == 'before' || $affiliatepress_price_symbol_position == 'before_with_space' ) {
                    $affiliatepress_price_with_symbol = $affiliatepress_currency_symbol . $affiliatepress_price;
                } elseif ($affiliatepress_price_symbol_position == 'before_with_space' ) {
                    $affiliatepress_price_with_symbol = $affiliatepress_currency_symbol . ' ' . $affiliatepress_price;
                } elseif ($affiliatepress_price_symbol_position == 'after' ) {
                    $affiliatepress_price_with_symbol = $affiliatepress_price . $affiliatepress_currency_symbol;
                } elseif ($affiliatepress_price_symbol_position == 'after_with_space' ) {
                    $affiliatepress_price_with_symbol = $affiliatepress_price . ' ' . $affiliatepress_currency_symbol;
                }
            }

            return $affiliatepress_price_with_symbol;
        } 
        


        /**
         * Function for get plugin name by plugin key
         *
         * @param  string $affiliatepress_plugin_key
         * @return string
        */
        function affiliatepress_get_supported_addon_name($affiliatepress_plugin_key){
            global $affiliatepress_global_options;
            $affiliatepress_plugin_name = '';
            $affiliatepress_all_plugin_integration = $affiliatepress_global_options->affiliatepress_all_plugin_integration();
            if(!empty($affiliatepress_all_plugin_integration)){
                foreach($affiliatepress_all_plugin_integration as $affiliatepresss_integration_single){
                    if($affiliatepresss_integration_single['plugin_value'] == $affiliatepress_plugin_key){
                        $affiliatepress_plugin_name = $affiliatepresss_integration_single['plugin_name'];
                        break;
                    }
                }
            }
            return $affiliatepress_plugin_name;
        }

        /**
         * Function for install
         *
         * @return void
        */
        public static function install(){
            global $wpdb, $AffiliatePress, $affiliatepress_version ,$affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_settings,$affiliatepress_tbl_ap_affiliate_visits,$affiliatepress_tbl_ap_creative,$affiliatepress_tbl_ap_notifications,$affiliatepress_tbl_ap_other_debug_logs,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_customer,$affiliatepress_tbl_ap_commission_products,$affiliatepress_tbl_ap_commission_debug_logs,$affiliatepress_tbl_ap_affiliate_form_fields,$affiliatepress_tbl_ap_affiliate_links,$affiliatepress_tbl_ap_payout_debug_logs,$affiliatepress_tbl_ap_payouts,$affiliatepress_tbl_ap_payments,$affiliatepress_tbl_ap_payment_commission,$affiliatepress_tbl_ap_payment_affiliate_note, $affiliatepress_tbl_ap_affiliate_report;

            $affiliatepress_version_check = get_option('affiliatepress_version');
            if (empty($affiliatepress_version_check) || $affiliatepress_version_check == '' ) {
                
                $affiliatepress_custom_css_key = uniqid();
                update_option('affiliatepress_custom_css_key', $affiliatepress_custom_css_key);

                include_once ABSPATH . 'wp-admin/includes/upgrade.php';

                $affiliatepress_charset_collate = '';
                if ($wpdb->has_cap('collation') ) {
                    if (! empty($wpdb->charset) ) {
                        $affiliatepress_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                    }
                    if (! empty($wpdb->collate) ) {
                        $affiliatepress_charset_collate .= " COLLATE $wpdb->collate";
                    }
                }
                
                update_option('affiliatepress_version', $affiliatepress_version);               
                update_option('affiliatepress_plugin_activated', 1);                               

                add_option('affiliatepress_install_date',current_time('mysql'));

                /* AffiliatePress Reports Table Add */
                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_affiliate_report}`(
                    `ap_affiliate_report_id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `ap_affiliates_id` INT(11) NOT NULL default 0,
                    `ap_affiliate_report_date` DATE NOT NULL,
                    `ap_affiliate_report_total_commission` INT(11) DEFAULT 0,
                    `ap_affiliate_report_paid_commission` INT(11) DEFAULT 0,
                    `ap_affiliate_report_unpaid_commission` INT(11) DEFAULT 0,
                    `ap_affiliate_report_total_commission_revenue` float DEFAULT 0,
                    `ap_affiliate_report_total_commission_amount` float DEFAULT 0,
                    `ap_affiliate_report_paid_commission_amount` float DEFAULT 0,
                    `ap_affiliate_report_unpaid_commission_amount` float DEFAULT 0,
                    `ap_affiliate_report_visits` INT(11) DEFAULT 0,
                    `ap_affiliate_report_converted_visits` INT(11) DEFAULT 0,
                    `ap_affiliate_report_unconverted_visits` INT(11) DEFAULT 0,
                    `ap_affiliate_report_created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,                                        
                    PRIMARY KEY (`ap_affiliate_report_id`)
                ) {$affiliatepress_charset_collate}";
                dbDelta($affiliatepress_sql_table);


                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_payouts}`(
                    `ap_payout_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `ap_payout_created_by` INT(11) NOT NULL default 0,
                    `ap_payout_amount` Float DEFAULT 0,
                    `ap_payout_total_affiliate` INT(11) DEFAULT 0,
                    `ap_payment_method` varchar(255) NOT NULL,
                    `ap_payout_upto_date` DATETIME NOT NULL,
                    `ap_payout_selected_affiliate` TEXT DEFAULT NULL,
                    `ap_payment_min_amount` varchar(255) DEFAULT NULL,
                    `ap_payment_min_order` varchar(255) DEFAULT NULL,
                    `ap_payout_process` INT(11) DEFAULT 0,
                    `ap_payout_created_date` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `ap_payout_updated_date` DATETIME default NULL,                                        
                    PRIMARY KEY (`ap_payout_id`)
                ) {$affiliatepress_charset_collate}";
                dbDelta($affiliatepress_sql_table);

                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_payments}`(
                    `ap_payment_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `ap_payout_id` INT(11) NOT NULL,
                    `ap_affiliates_id` INT(11) NOT NULL,
                    `ap_affiliates_name` varchar(255) NOT NULL,
                    `ap_payment_amount` Float DEFAULT 0,
                    `ap_payment_currency` varchar(255) NOT NULL,
                    `ap_payment_method` varchar(255) NOT NULL,
                    `ap_payment_transaction_id` varchar(255) default NULL,
                    `ap_payment_note` TEXT default NULL,
                    `ap_payment_status` INT(11) default 1,
                   `ap_payment_visit` INT(11) default 0,
                    `ap_payment_created_date` timestamp DEFAULT CURRENT_TIMESTAMP, 
                    `ap_payment_updated_date` DATETIME default NULL,                   
                    PRIMARY KEY (`ap_payment_id`)
                ) {$affiliatepress_charset_collate}";
                dbDelta($affiliatepress_sql_table);

                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_payment_commission}`(
                    `ap_payment_commission_id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `ap_payment_id` INT(11) NOT NULL,
                    `ap_payout_id` INT(11) NOT NULL,
                    `ap_affiliates_id` INT(11) NOT NULL,                    
                    `ap_commission_id` bigint(20) NOT NULL,
                    `ap_commission_amount` Float DEFAULT 0,
                    `ap_payment_commission_created_date` timestamp DEFAULT CURRENT_TIMESTAMP,                    
                    PRIMARY KEY (`ap_payment_commission_id`)
                ) {$affiliatepress_charset_collate}";
                dbDelta($affiliatepress_sql_table);                


                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_affiliate_links}`(
                    `ap_affiliate_link_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `ap_affiliates_id` INT(11) NOT NULL,
                    `ap_page_link` varchar(255) NOT NULL,
                    `ap_affiliates_campaign_name` varchar(255) NOT NULL,
                    `ap_affiliates_sub_id` varchar(255) default NULL,
                    `ap_created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`ap_affiliate_link_id`)
                ) {$affiliatepress_charset_collate}";
                dbDelta($affiliatepress_sql_table);                

                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_affiliate_form_fields}`(
					`ap_form_field_id` INT(11) NOT NULL AUTO_INCREMENT,
					`ap_form_field_name` varchar(255) NOT NULL,
                    `ap_form_field_type` varchar(255) NOT NULL,
					`ap_field_required` TINYINT(1) DEFAULT 0,
					`ap_field_label` TEXT NOT NULL,
					`ap_field_placeholder` TEXT DEFAULT NULL,
					`ap_field_error_message` VARCHAR(255) DEFAULT NULL,
                    `ap_field_edit` TINYINT(1) DEFAULT 0,
					`ap_show_signup_field` TINYINT(1) DEFAULT 1,
                    `ap_show_profile_field` TINYINT(1) DEFAULT 1, 
                    `ap_field_class` varchar(255) NOT NULL,                    
					`ap_field_position` FLOAT DEFAULT 0,
                    `ap_field_is_default` tinyint(1) DEFAULT 0,
					`ap_created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`ap_form_field_id`)
				) {$affiliatepress_charset_collate}";
                dbDelta($affiliatepress_sql_table);

                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_affiliates}`(
					`ap_affiliates_id` INT(11) NOT NULL AUTO_INCREMENT, 
					`ap_affiliates_user_id` bigint(20) NOT NULL,                    
                    `ap_affiliates_first_name` VARCHAR(255) NOT NULL,
                    `ap_affiliates_last_name` VARCHAR(255) NOT NULL,                    
                    `ap_affiliates_user_name` VARCHAR(255) NOT NULL,
                    `ap_affiliates_user_email` VARCHAR(255) NOT NULL,
                    `ap_affiliates_payment_email` VARCHAR(255) NOT NULL,
					`ap_affiliates_website` VARCHAR(255) DEFAULT NULL,
					`ap_affiliates_status` INT(11) NOT NULL,
					`ap_affiliates_user_avatar` VARCHAR(255) DEFAULT NULL,
					`ap_affiliates_promote_us` MEDIUMTEXT DEFAULT NULL,
					`ap_affiliates_note` TEXT DEFAULT NULL,  
					`ap_affiliates_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`ap_affiliates_id`)
				) {$affiliatepress_charset_collate};";
                dbDelta($affiliatepress_sql_table);

                

                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_settings}`(
					`ap_setting_id` int(11) NOT NULL AUTO_INCREMENT,
					`ap_setting_name` varchar(255) NOT NULL,
					`ap_setting_value` TEXT DEFAULT NULL,
					`ap_setting_type` varchar(255) DEFAULT NULL,
					`ap_settings_updated_at` timestamp DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`ap_setting_id`)
				) {$affiliatepress_charset_collate}";
                dbDelta($affiliatepress_sql_table);   
                
                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_affiliate_visits}`(
					`ap_visit_id` bigint(20) NOT NULL AUTO_INCREMENT, 
					`ap_affiliates_id` INT(11) NOT NULL,					
                    `ap_visit_created_date` DATETIME NOT NULL,
                    `ap_visit_updated_date` DATETIME NOT NULL,
                    `ap_visit_ip_address` VARCHAR(255) DEFAULT NULL,
					`ap_visit_country` VARCHAR(255) DEFAULT NULL,
                    `ap_visit_iso_code` VARCHAR(10) DEFAULT NULL,
                    `ap_visit_browser` VARCHAR(255) DEFAULT NULL,
                    `ap_affiliates_campaign_name` varchar(255) default NULL,
                    `ap_affiliates_sub_id` varchar(255) default NULL,                    
					`ap_visit_landing_url` MEDIUMTEXT DEFAULT NULL,
					`ap_referrer_url` MEDIUMTEXT DEFAULT NULL,
					`ap_commission_id` bigint(20) DEFAULT 0,
					PRIMARY KEY (`ap_visit_id`)
				) {$affiliatepress_charset_collate};";
                dbDelta($affiliatepress_sql_table);

                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_creative}`(
					`ap_creative_id` INT(11) NOT NULL AUTO_INCREMENT, 
					`ap_creative_name` VARCHAR(255) DEFAULT NULL,
					`ap_creative_description` TEXT DEFAULT NULL,
					`ap_creative_type` VARCHAR(255) NOT NULL,
					`ap_creative_image_url` MEDIUMTEXT DEFAULT NULL,
					`ap_creative_alt_text` VARCHAR(255) DEFAULT NULL,
					`ap_creative_text` TEXT DEFAULT NULL,
                    `ap_creative_landing_url` MEDIUMTEXT DEFAULT NULL,
                    `ap_creative_status` INT(11) NOT NULL DEFAULT 1,
					`ap_creative_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`ap_creative_id`)
				) {$affiliatepress_charset_collate};";
                dbDelta($affiliatepress_sql_table);
                
                /*  Notification Table Install Here.... */
                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_notifications}`(
					`ap_notification_id` INT(11) NOT NULL AUTO_INCREMENT,
					`ap_notification_receiver_type` varchar(11) DEFAULT 'affiliate',
					`ap_notification_is_custom` TINYINT(1) DEFAULT 0,
					`ap_notification_name` varchar(255) NOT NULL,
                    `ap_notification_slug` varchar(255) NOT NULL,
					`ap_notification_status` TINYINT(1) DEFAULT 0,
					`ap_notification_type` varchar(255) DEFAULT 'affiliate',
					`ap_notification_subject` TEXT DEFAULT NULL,
					`ap_notification_message` TEXT DEFAULT NULL,
					`ap_notification_created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
					`ap_notification_updated_at` datetime DEFAULT NULL,
					PRIMARY KEY (`ap_notification_id`)
				) {$affiliatepress_charset_collate}";
                dbDelta($affiliatepress_sql_table);
                
                /* AffiliatePress Other Debug Log Table */
                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_other_debug_logs}`(
			        `ap_other_log_id` int(11) NOT NULL AUTO_INCREMENT,
					`ap_other_log_ref_id` int(11) NOT NULL,
					`ap_other_log_type` varchar(255) DEFAULT NULL,
					`ap_other_log_event` varchar(255) DEFAULT NULL,
					`ap_other_log_event_from` varchar(255) DEFAULT NULL,
					`ap_other_log_raw_data` TEXT DEFAULT NULL,		
			        `ap_other_log_added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			        PRIMARY KEY (`ap_other_log_id`)
			    ) {$affiliatepress_charset_collate};";
                dbDelta($affiliatepress_sql_table);

                /* Commissions Table Install Here */
                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_affiliate_commissions}`(
					`ap_commission_id` bigint(20) NOT NULL AUTO_INCREMENT,
					`ap_affiliates_id` INT(11) NOT NULL,
                    `ap_visit_id` bigint(20) DEFAULT 0,
					`ap_commission_type` varchar(255) DEFAULT 'sale',
					`ap_commission_status` TINYINT(1) DEFAULT 2,
                    `ap_commission_reference_id` varchar(255) DEFAULT 0,
                    `ap_commission_product_ids` TEXT DEFAULT NULL,
                    `ap_commission_reference_detail` TEXT DEFAULT NULL,					
					`ap_commission_reference_amount` Float DEFAULT 0,
					`ap_commission_source` varchar(255) DEFAULT NULL,
					`ap_commission_amount` Float DEFAULT 0,
                    `ap_commission_order_amount` Float DEFAULT 0,
					`ap_commission_currency` varchar(255) DEFAULT NULL,
                    `ap_commission_note` TEXT DEFAULT NULL,   
                    `ap_commission_ip_address` varchar(255) DEFAULT NULL,
                    `ap_customer_id` bigint(20) DEFAULT 0,
                    `ap_commission_payment_id` bigint(20) DEFAULT 0,
                    `ap_commission_rate` varchar(255) DEFAULT NULL,
					`ap_commission_created_date` datetime DEFAULT NULL,
                    `ap_commission_updated_date` datetime DEFAULT NULL,
					PRIMARY KEY (`ap_commission_id`)
				) {$affiliatepress_charset_collate}";
                dbDelta($affiliatepress_sql_table);

                $affiliatepress_tbl_ap_affiliate_commissions_temp = $AffiliatePress->affiliatepress_tablename_prepare($affiliatepress_tbl_ap_affiliate_commissions);

                $affiliatepress_commission_id = intval($wpdb->get_var( "SELECT MAX(ap_commission_id) FROM {$affiliatepress_tbl_ap_affiliate_commissions_temp}"));// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions_temp is a table name & already prepare by above affiliatepress_tablename_prepare function. false alarm                
                if($affiliatepress_commission_id == 0){            
                    $wpdb->query( $wpdb->prepare( "ALTER TABLE {$affiliatepress_tbl_ap_affiliate_commissions_temp} AUTO_INCREMENT = %d;", 1000));// phpcs:ignore WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $affiliatepress_tbl_ap_affiliate_commissions_temp is a table name & already prepare by above affiliatepress_tablename_prepare function. false alarm
                }                
                                
                /* Commission Product Table Add Here */
                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_commission_products}`(
                    `ap_commission_product_rel_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `ap_commission_id` bigint(20) NOT NULL DEFAULT 0,
                    `ap_commission_product_id` INT(11) NOT NULL DEFAULT 0,
                    `ap_commission_product_order_id` INT(11) NOT NULL DEFAULT 0,
                    `ap_commission_product_name` MEDIUMTEXT DEFAULT NULL,
                    `ap_commission_product_amount` Float DEFAULT 0,
                    `ap_commission_source` VARCHAR(255) DEFAULT NULL,
                    `ap_commission_product_price` Float DEFAULT 0,
                    `ap_commission_product_rate` Float DEFAULT 0,
                    `ap_commission_product_type` VARCHAR(255) DEFAULT NULL,
                    `ap_commission_type` VARCHAR(255) DEFAULT NULL,
                    `ap_commission_product_created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`ap_commission_product_rel_id`)
                ) {$affiliatepress_charset_collate};";
                dbDelta($affiliatepress_sql_table);


                /* Commission Customer Table Add Here */
                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_customer}`(
                    `ap_customer_id` bigint(20) NOT NULL AUTO_INCREMENT, 
                    `ap_customer_user_id` bigint(20) NOT NULL DEFAULT 0,
                    `ap_customer_email` VARCHAR(255) NOT NULL,
                    `ap_customer_first_name` VARCHAR(255) DEFAULT NULL,
                    `ap_customer_last_name` VARCHAR(255) DEFAULT NULL,
                    `ap_affiliates_id` INT(11) NOT NULL DEFAULT 0,
                    `ap_customer_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`ap_customer_id`)
                ) {$affiliatepress_charset_collate};";
                dbDelta($affiliatepress_sql_table);                

                /* AffiliatePress Commission Debug Log Table */
                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_commission_debug_logs}`(
                    `ap_commission_log_id` int(11) NOT NULL AUTO_INCREMENT,
                    `ap_commission_log_ref_id` int(11) NOT NULL,
                    `ap_commission_log_type` varchar(255) DEFAULT NULL,
                    `ap_commission_log_event` varchar(255) DEFAULT NULL,
                    `ap_commission_log_event_from` varchar(255) DEFAULT NULL,
                    `ap_commission_log_raw_data` TEXT DEFAULT NULL,		
                    `ap_commission_log_added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`ap_commission_log_id`)
                ) {$affiliatepress_charset_collate};";
                dbDelta($affiliatepress_sql_table);

                /* AffiliatePress Payout Debug Log Table */
                $affiliatepress_sql_table = "CREATE TABLE IF NOT EXISTS `{$affiliatepress_tbl_ap_payout_debug_logs}`(
                    `ap_payout_log_id` int(11) NOT NULL AUTO_INCREMENT,
                    `ap_payout_log_ref_id` int(11) NOT NULL,
                    `ap_payout_log_type` varchar(255) DEFAULT NULL,
                    `ap_payout_log_event` varchar(255) DEFAULT NULL,
                    `ap_payout_log_event_from` varchar(255) DEFAULT NULL,
                    `ap_payout_log_raw_data` TEXT DEFAULT NULL,		
                    `ap_payout_log_added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`ap_payout_log_id`)
                ) {$affiliatepress_charset_collate};";
                dbDelta($affiliatepress_sql_table);

                /* Function for install default settings */
                $AffiliatePress->affiliatepress_install_default_settings_data();
                
                /* Function for install default notifications */
                $AffiliatePress->affiliatepress_install_default_notification_data();   

                /* Function for add affiliate user role */
                $AffiliatePress->affiliatepress_add_user_role_and_capabilities();

                /* Function for install affiliate pages */
                $AffiliatePress->affiliatepress_install_default_affiliate_pages_data();                                

                /* Function for install affiliate fields */
                $AffiliatePress->affiliatepress_install_default_affiliate_fields_data();

                /* Give a capability to admin user */
                $affiliatepress_args  = array(
                    'role'   => 'administrator',
                    'fields' => 'id',
                );
                $affiliatepress_users = get_users($affiliatepress_args);    
                if (count($affiliatepress_users) > 0 ) {
                    foreach ( $affiliatepress_users as $affiliatepress_key => $affiliatepress_user_id ) {
                        $affiliatepressroles = $AffiliatePress->affiliatepress_capabilities();
                        $affiliatepress_userObj           = new WP_User($affiliatepress_user_id);
                        foreach ( $affiliatepressroles as $affiliatepressrole => $affiliatepress_roledescription ) {
                            $affiliatepress_userObj->add_cap($affiliatepressrole);
                        }
                        unset($affiliatepressrole);
                        unset($affiliatepressroles);
                        unset($affiliatepress_roledescription);
                    }
                }

                $affiliatepress_check_db_permission = $AffiliatePress->affiliatepress_check_db_permission();
                if($affiliatepress_check_db_permission){
                       
                    $affiliatepress_add_index_report_date = $wpdb->get_results(  $wpdb->prepare("SHOW INDEX FROM {$affiliatepress_tbl_ap_affiliate_report} where Key_name=%s ",'affiliatepress_report_date-affiliatepress_report_affiliates_id') ); //phpcs:ignore --Reason $affiliatepress_tbl_ap_affiliate_report is a table name
                    if(empty($affiliatepress_add_index_report_date)){
                        $wpdb->query("ALTER TABLE {$affiliatepress_tbl_ap_affiliate_report} ADD INDEX `affiliatepress_report_date-affiliatepress_report_affiliates_id` (`ap_affiliate_report_date`,`ap_affiliates_id`)");//phpcs:ignore --Reason $affiliatepress_add_index_report_date is a table name
                    }
                    
                    $affiliatepress_add_index_report_date = $wpdb->get_results(  $wpdb->prepare("SHOW INDEX FROM {$affiliatepress_tbl_ap_affiliate_visits} where Key_name=%s ",'affiliatepress_visit_date-affiliatepress_visit_affiliates_id') ); //phpcs:ignore --Reason $affiliatepress_tbl_ap_affiliate_visits is a table name
                    if(empty($affiliatepress_add_index_report_date)){
                        $wpdb->query("ALTER TABLE {$affiliatepress_tbl_ap_affiliate_visits} ADD INDEX `affiliatepress_visit_date-affiliatepress_visit_affiliates_id` (`ap_visit_created_date`,`ap_affiliates_id`)");//phpcs:ignore --Reason $affiliatepress_tbl_ap_affiliate_visits is a table name
                    }                    

                }

                $AffiliatePress->affiliatepress_send_anonymous_data_cron();

                $AffiliatePress->affiliatepress_install_default_creative_data();

                update_option( 'affiliatepress_flush_rewrites','1');
                flush_rewrite_rules();

            }

        }
                
        /**
         * Function for check db permission
         *
         * @return void
        */
        function affiliatepress_check_db_permission(){
			global $wpdb;
            $affiliatepress_results = $wpdb->get_results("SHOW GRANTS FOR CURRENT_USER;");// phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $affiliatepress_allowed_index = 0;
            foreach($affiliatepress_results as $result){
                if(is_object($result)){
                    foreach($result as $res){
                        $result_data = stripslashes_deep($res);
                    }
                }else {
                    $result_data = stripslashes_deep($result);
                }
                if( !empty($result_data) && ((strpos($result_data, "ALL PRIVILEGES") !== false || strpos($result_data, "INDEX") !== false) && (strpos($result_data, "ON *.*") || strpos($result_data, "`".DB_NAME."`") ) )){
                    $affiliatepress_allowed_index = 1;
                    break;
                }
            }
            return $affiliatepress_allowed_index;
		}

        /**
         * Uninstall Function
         *
         * @return void
         */
        public static function uninstall(){
            
            global $wp, $wpdb,$AffiliatePress,$affiliatepress_version,$affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_settings,$affiliatepress_tbl_ap_affiliate_visits,$affiliatepress_tbl_ap_creative,$affiliatepress_tbl_ap_notifications,$affiliatepress_tbl_ap_other_debug_logs,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_customer,$affiliatepress_tbl_ap_affiliate_form_fields,$affiliatepress_tbl_ap_payout_debug_logs,$affiliatepress_tbl_ap_commission_debug_logs,$affiliatepress_tbl_ap_payouts,$affiliatepress_tbl_ap_payments,$affiliatepress_tbl_ap_payment_commission,$affiliatepress_tbl_ap_affiliate_links;

            if ( is_multisite() ) {
                $blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                if ( $blogs ) {
                    foreach ( $blogs as $blog ) {
                        switch_to_blog( $blog['blog_id'] );
                        $AffiliatePress->affiliatepress_uninstall_data();
                    }
                    restore_current_blog();
                }
            }else{
                $AffiliatePress->affiliatepress_uninstall_data();
            }
            
        }

        public function affiliatepress_uninstall_data(){

            global $wp, $wpdb,$AffiliatePress,$affiliatepress_version,$affiliatepress_tbl_ap_affiliates,$affiliatepress_tbl_ap_settings,$affiliatepress_tbl_ap_affiliate_visits,$affiliatepress_tbl_ap_creative,$affiliatepress_tbl_ap_notifications,$affiliatepress_tbl_ap_other_debug_logs,$affiliatepress_tbl_ap_affiliate_commissions,$affiliatepress_tbl_ap_customer,$affiliatepress_tbl_ap_affiliate_form_fields,$affiliatepress_tbl_ap_payout_debug_logs,$affiliatepress_tbl_ap_commission_debug_logs,$affiliatepress_tbl_ap_payouts,$affiliatepress_tbl_ap_payments,$affiliatepress_tbl_ap_payment_commission,$affiliatepress_tbl_ap_affiliate_links,$affiliatepress_tbl_ap_affiliate_report,$affiliatepress_tbl_ap_commission_products;

            delete_option( 'affiliatepress_version' );

            /* Delete AffiliatePress Option  */
            $wpdb->query('DELETE FROM `' . $wpdb->options . "` WHERE  `option_name` LIKE  '%affiliatepress\_%'");// phpcs:ignore WordPress.DB.DirectDatabaseQuery

            $prefix = $wpdb->prefix;
            
            $affiliatepress_all_tables = array(
                $prefix.'affiliatepress_affiliates',
                $prefix.'affiliatepress_settings',
                $prefix.'affiliatepress_affiliate_visits',
                $prefix.'affiliatepress_creative',
                $prefix.'affiliatepress_notifications',
                $prefix.'affiliatepress_other_debug_logs',
                $prefix.'affiliatepress_commissions',
                $prefix.'affiliatepress_customer',
                $prefix.'affiliatepress_commission_products',
                $prefix.'affiliatepress_commission_debug_logs',
                $prefix.'affiliatepress_affiliate_form_fields',
                $prefix.'affiliatepress_affiliate_links',
                $prefix.'affiliatepress_payout_debug_logs',
                $prefix.'affiliatepress_payouts',
                $prefix.'affiliatepress_payments',
                $prefix.'affiliatepress_payment_commission',
                $prefix.'affiliatepress_payment_affiliate_note',
                $prefix.'affiliatepress_affiliate_report',

            );
            foreach ( $affiliatepress_all_tables as $affiliatepress_table ) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $affiliatepress_table is table name defined globally. False Positive alarm
                $wpdb->query("DROP TABLE IF EXISTS $affiliatepress_table ");// phpcs:ignore
            }
 
            /* Remove all capabilities assigned to administrator */
            $affiliatepress_args  = array(
                'role'   => 'administrator',
                'fields' => 'id',
            );
            $affiliatepress_users = get_users($affiliatepress_args);
            if (count($affiliatepress_users) > 0 ) {
                foreach ( $affiliatepress_users as $affiliatepress_key => $affiliatepress_user_id ) {
                    $affiliatepressroles = $AffiliatePress->affiliatepress_capabilities();
                    $affiliatepress_userObj           = new WP_User($affiliatepress_user_id);
                    foreach ( $affiliatepressroles as $affiliatepressrole => $affiliatepress_roledescription ) {
                        if($affiliatepress_userObj->has_cap($affiliatepressrole)){
                            $affiliatepress_userObj->remove_cap($affiliatepressrole, true);
                        }
                    }
                }
            }

            $affiliatepress_args = array(
                'role'   => 'affiliatepress-affiliate-user',
                'fields' => 'ID',
            );
            $affiliatepress_users = get_users($affiliatepress_args);
            if (!empty($affiliatepress_users)) {
                foreach ($affiliatepress_users as $user_id) {
                    $user = new WP_User($user_id);
                    $user->remove_role('affiliatepress-affiliate-user');
                    $affiliatepressroles = $AffiliatePress->affiliatepress_capabilities(); 
                    foreach ($affiliatepressroles as $cap => $desc) {
                        if ($user->has_cap($cap)) {
                            $user->remove_cap($cap);
                        }
                    }
                    delete_user_meta($user_id, 'affiliatepress_affiliate_user');
                }
            }
            
        }
        
        /**
         * Function for add common svg code
         *
        */
        function affiliatepress_common_svg_code_func($affiliatepress_type){
            if($affiliatepress_type == 'empty_view'){
            ?>
                <svg width="161" height="160" viewBox="0 0 221 220" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect class="ap-empty-data-svg-icon-background-color" width="160" height="160" transform="translate(0.5)" fill="white"/>
                <path class="ap-empty-data-svg-icon-color" d="M162.056 82C172.012 82 176.99 82.0004 180.793 83.9424C184.138 85.6506 186.858 88.3758 188.562 91.7285C190.5 95.54 190.5 100.53 190.5 110.509V151.491C190.5 161.47 190.5 166.46 188.562 170.271C186.859 173.624 184.138 176.35 180.793 178.058C176.99 180 172.012 180 162.056 180H58.9443C48.988 180 44.0099 180 40.207 178.058C36.8619 176.35 34.1419 173.624 32.4375 170.271C30.4998 166.46 30.5 161.47 30.5 151.491V110.509C30.5 100.53 30.4998 95.54 32.4375 91.7285C34.1419 88.3759 36.862 85.6506 40.207 83.9424C44.0099 82.0003 48.988 82 58.9443 82H92.5V97.5869C92.5001 108.227 101.348 117 112.172 117C118.478 117 124.407 113.92 128.172 108.88C130.619 105.52 131.843 101.32 131.843 97.2129C131.843 95.2532 130.243 93.6671 128.267 93.667C126.29 93.667 124.69 95.2531 124.689 97.2129C124.689 99.8262 123.843 102.627 122.337 104.773C119.984 108.04 116.125 110 112.172 110C105.301 110 99.6535 104.4 99.6533 97.5869V82H162.056Z" fill="#6858E0" fill-opacity="0.2"/>
                <path class="ap-empty-data-svg-icon-color" d="M78.0537 56C85.3291 56 88.9673 56.0001 92.2383 57.3447C92.761 57.5596 93.2662 57.8048 93.7666 58.0869C92.9453 60.6203 92.5 63.3243 92.5 66.1338V82H58.9443C48.988 82 44.0099 82.0003 40.207 83.9424C36.862 85.6506 34.1419 88.3759 32.4375 91.7285C30.6 95.3428 30.5069 100.017 30.502 109H30.5V82.5C30.5 74.2688 30.4997 70.1529 31.8545 66.9062C33.6611 62.5775 37.1268 59.1378 41.4883 57.3447C44.7595 56 48.9065 56 57.2002 56H78.0537Z" fill="#6858E0" fill-opacity="0.3"/>
                <path class="ap-empty-data-svg-icon-color" d="M162.055 82H144.266C137.678 82 132.69 75.9333 134.007 69.5867L136.831 48.2133C136.925 47.4667 137.584 47 138.149 47C138.525 47 138.807 47.0933 139.09 47.4667L146.243 55.4H159.985L166.95 47.3733C167.232 47.0933 167.608 46.9067 167.891 46.9067C168.456 46.9067 169.114 47.28 169.208 48.0267L172.314 69.7733C173.538 76.12 168.55 82 162.055 82ZM149.161 74.16C146.431 73.5067 142.102 73.5067 139.278 74.16C138.807 74.2533 138.525 74.72 138.619 75.1867C138.713 75.6533 139.09 75.9333 139.56 75.9333C139.655 75.9333 139.749 75.9333 139.749 75.9333C142.196 75.3733 146.243 75.3733 148.69 75.9333C149.161 76.0267 149.725 75.7467 149.82 75.28C149.914 74.8133 149.631 74.2533 149.161 74.16ZM165.632 74.16C162.902 73.5067 158.573 73.5067 155.749 74.16C155.279 74.2533 154.996 74.72 155.09 75.1867C155.184 75.6533 155.561 75.9333 156.032 75.9333C156.126 75.9333 156.22 75.9333 156.22 75.9333C158.667 75.3733 162.714 75.3733 165.161 75.9333C165.632 76.0267 166.197 75.7467 166.291 75.28C166.385 74.8133 166.103 74.2533 165.632 74.16ZM92.5 66.1333V78.5467V82V97.5867C92.5 108.227 101.347 117 112.171 117C118.477 117 124.407 113.92 128.172 108.88C130.619 105.52 131.843 101.32 131.843 97.2133C131.843 95.2533 130.242 93.6667 128.266 93.6667C126.289 93.6667 124.689 95.2533 124.689 97.2133C124.689 99.8267 123.842 102.627 122.336 104.773C119.983 108.04 116.124 110 112.171 110C105.3 110 99.6532 104.4 99.6532 97.5867V82H137.678C136.549 81.3467 135.513 80.4133 134.666 79.3867C132.313 76.4933 131.372 72.8533 132.125 69.2133L134.948 47.84C135.043 47.1867 135.419 46.6267 135.796 46.16C131.184 42.3333 125.254 40 118.76 40C104.265 40 92.5 51.6667 92.5 66.1333Z" fill="#6858E0" fill-opacity="0.8"/>
                <path class="ap-empty-data-svg-icon-color" d="M187.999 31.0315V29.1704L195.057 18.9477V18.8598H188.636V16H199.389V17.9973L192.485 28.0835V28.1717H199.5V31.0315H187.999ZM178.956 36.0161V34.8119L183.523 28.1972V28.1403H179.368V26.2898H186.326V27.5822L181.858 34.1085V34.1657H186.398V36.0161H178.956ZM171.5 40V39.1242L174.822 34.3135V34.2721H171.8V32.9263H176.86V33.8663L173.611 38.6127V38.6542H176.912V40H171.5Z" fill="#6858E0" fill-opacity="0.8"/>
                </svg>

            <?php 
            }else if($affiliatepress_type == 'add_icon'){
            ?>
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 1V11M11 6H1" stroke="white" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            <?php 
            }else if($affiliatepress_type == 'link_icon'){
            ?>
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_753_1066)"> <path class="ap-small-btn-icon-stoke-fill-white" d="M3.22987 16.77L3.22985 16.77C2.39011 15.9302 2.39012 14.5694 3.22967 13.7298L7.47249 9.48703L7.40178 9.41632L7.47249 9.48703C8.31204 8.64744 9.67289 8.64744 10.5124 9.48703C10.8444 9.81897 11.3826 9.81897 11.7145 9.48703C12.0465 9.15508 12.0465 8.61687 11.7145 8.28493C10.2111 6.78146 7.77386 6.78146 6.27039 8.28493L2.0276 12.5277C0.524132 14.0312 0.524109 16.4684 2.02758 17.9718C3.53089 19.4761 5.96829 19.4761 7.47253 17.9719L9.59357 15.8508C9.92551 15.5189 9.92551 14.9807 9.59357 14.6487C9.26162 14.3168 8.72337 14.3168 8.39143 14.6488L6.27039 16.7698C5.43009 17.6101 4.0694 17.6101 3.22987 16.77Z" fill="#656E81" stroke="#656E81" stroke-width="0.2"/><path class="ap-small-btn-icon-stoke-fill-white" d="M17.9706 2.0276C16.4671 0.524132 14.0292 0.524132 12.5257 2.0276L9.9809 4.57239C9.64896 4.90433 9.64896 5.44255 9.9809 5.77449C10.3128 6.10643 10.8511 6.10643 11.183 5.77449L13.7278 3.2297L13.6571 3.15899L13.7278 3.2297C14.5673 2.39012 15.9289 2.39012 16.7685 3.2297C17.6081 4.06925 17.6081 5.4301 16.7685 6.26965L12.1019 10.9363C11.2624 11.7758 9.90155 11.7758 9.062 10.9363C8.73005 10.6043 8.19184 10.6043 7.85989 10.9363C7.52795 11.2682 7.52795 11.8064 7.85989 12.1384C9.36337 13.6418 11.8006 13.6418 13.304 12.1384L17.9706 7.47179C19.4741 5.96832 19.4741 3.53108 17.9706 2.0276Z" fill="#656E81" stroke="#656E81" stroke-width="0.2"/> </g><defs><clipPath id="clip0_753_1066"> <rect width="20" height="20" fill="white"/></clipPath></defs></svg>    
            <?php 
            }else if($affiliatepress_type == 'delete_icon'){
            ?>
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_753_1083)"><path class="ap-small-btn-icon-fill-white" d="M16.5447 5.45427C16.3036 5.45427 16.0724 5.55004 15.9019 5.72052C15.7314 5.891 15.6357 6.12222 15.6357 6.36331V16.5364C15.6096 16.9961 15.4029 17.4269 15.0607 17.7349C14.7185 18.0429 14.2684 18.2032 13.8085 18.1809H6.55431C6.09443 18.2032 5.64435 18.0429 5.30211 17.7349C4.95988 17.4269 4.75321 16.9961 4.72713 16.5364V6.36331C4.72713 6.12222 4.63136 5.891 4.46088 5.72052C4.2904 5.55004 4.05918 5.45427 3.81809 5.45427C3.577 5.45427 3.34578 5.55004 3.1753 5.72052C3.00482 5.891 2.90904 6.12222 2.90904 6.36331V16.5364C2.93499 17.4784 3.33319 18.3717 4.01642 19.0207C4.69966 19.6697 5.61225 20.0215 6.55431 19.999H13.8085C14.7506 20.0215 15.6631 19.6697 16.3464 19.0207C17.0296 18.3717 17.4278 17.4784 17.4538 16.5364V6.36331C17.4538 6.12222 17.358 5.891 17.1875 5.72052C17.017 5.55004 16.7858 5.45427 16.5447 5.45427ZM17.4538 2.72713H13.8176V0.909045C13.8176 0.667951 13.7218 0.436732 13.5513 0.266253C13.3808 0.095774 13.1496 0 12.9085 0H7.45427C7.21317 0 6.98196 0.095774 6.81148 0.266253C6.641 0.436732 6.54522 0.667951 6.54522 0.909045V2.72713H2.90904C2.66795 2.72713 2.43673 2.82291 2.26625 2.99339C2.09577 3.16387 2 3.39509 2 3.63618C2 3.87727 2.09577 4.10849 2.26625 4.27897C2.43673 4.44945 2.66795 4.54522 2.90904 4.54522H17.4538C17.6949 4.54522 17.9261 4.44945 18.0966 4.27897C18.267 4.10849 18.3628 3.87727 18.3628 3.63618C18.3628 3.39509 18.267 3.16387 18.0966 2.99339C17.9261 2.82291 17.6949 2.72713 17.4538 2.72713ZM8.36331 2.72713V1.81809H11.9995V2.72713H8.36331Z"/><path class="ap-small-btn-icon-fill-white" d="M9.27121 14.5448V8.18151C9.27121 7.94041 9.17544 7.70919 9.00496 7.53871C8.83448 7.36824 8.60326 7.27246 8.36217 7.27246C8.12108 7.27246 7.88986 7.36824 7.71938 7.53871C7.5489 7.70919 7.45312 7.94041 7.45312 8.18151V14.5448C7.45312 14.7859 7.5489 15.0171 7.71938 15.1876C7.88986 15.3581 8.12108 15.4539 8.36217 15.4539C8.60326 15.4539 8.83448 15.3581 9.00496 15.1876C9.17544 15.0171 9.27121 14.7859 9.27121 14.5448ZM12.9074 14.5448V8.18151C12.9074 7.94041 12.8116 7.70919 12.6411 7.53871C12.4707 7.36824 12.2394 7.27246 11.9983 7.27246C11.7573 7.27246 11.526 7.36824 11.3556 7.53871C11.1851 7.70919 11.0893 7.94041 11.0893 8.18151V14.5448C11.0893 14.7859 11.1851 15.0171 11.3556 15.1876C11.526 15.3581 11.7573 15.4539 11.9983 15.4539C12.2394 15.4539 12.4707 15.3581 12.6411 15.1876C12.8116 15.0171 12.9074 14.7859 12.9074 14.5448Z"/></g><defs><clipPath id="clip0_753_1083"><rect width="20" height="20" fill="white"/></clipPath></defs></svg>
            <?php 
            }else if($affiliatepress_type == 'edit_icon'){
            ?>
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_753_1100)"><g clip-path="url(#clip1_753_1100)"><path class="ap-small-btn-icon-stoke-white" d="M17.8801 5.10143L5.55182 17.4296L1.69922 18.2002L2.46973 14.3476L14.798 2.01933C15.2235 1.59382 15.9135 1.59382 16.339 2.01933L17.8801 3.56041C18.3056 3.98592 18.3056 4.67585 17.8801 5.10143Z"  stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path class="ap-small-btn-icon-stoke-white" d="M13.2578 3.56054L16.3398 6.64258"  stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/></g></g><defs><clipPath id="clip0_753_1100"><rect width="20" height="20" fill="white"/></clipPath><clipPath id="clip1_753_1100"><rect width="20" height="20" fill="white"/></clipPath></defs></svg>
            <?php 
            }
            elseif ($affiliatepress_type == 'details_action') {
                ?>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="ap-small-btn-icon-stoke-white" d="M1.8999 8.19997C1.8999 4.80587 1.8999 3.10883 2.95431 2.05441C4.00873 1 5.70577 1 9.09987 1H10.8999C14.2939 1 15.991 1 17.0454 2.05441C18.0998 3.10883 18.0998 4.80587 18.0998 8.19997V11.8C18.0998 15.194 18.0998 16.8911 17.0454 17.9455C15.991 18.9999 14.2939 18.9999 10.8999 18.9999H9.09987C5.70577 18.9999 4.00873 18.9999 2.95431 17.9455C1.8999 16.8911 1.8999 15.194 1.8999 11.8V8.19997Z" stroke="#4D5973" stroke-width="1.8"/>
                    <path class="ap-small-btn-icon-stoke-white" d="M6.3999 10H13.5999" stroke="#4D5973" stroke-width="1.8" stroke-linecap="round"/>
                    <path class="ap-small-btn-icon-stoke-white" d="M6.3999 6.40002H13.5999" stroke="#4D5973" stroke-width="1.8" stroke-linecap="round"/>
                    <path class="ap-small-btn-icon-stoke-white" d="M6.3999 13.5999H10.8999" stroke="#4D5973" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                <?php
            }            
            else if($affiliatepress_type == 'info_icon'){
            ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_4872_2591)">
                <circle cx="8" cy="8" r="8" fill="#9CA7BD"/>
                <path d="M5.66675 5.61308C5.66675 2.85551 10 2.85553 10 5.61309C10 7.58276 8.03036 7.18877 8.03036 9.55239" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8.03027 12.7129L8.03903 12.7031" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <defs>
                <clipPath id="clip0_4872_2591">
                <rect width="16" height="16" rx="8" fill="white"/>
                </clipPath>
                </defs>
                </svg>
            <?php 
            }else if($affiliatepress_type == 'wrong_icon'){
            ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.5 4.5L19.5 19.5" stroke="#EB5757" stroke-width="2.5" stroke-linecap="round"/><path d="M19.5 4.5L4.5 19.5" stroke="#EB5757" stroke-width="2.5" stroke-linecap="round"/></svg>          
            <?php
            }else if($affiliatepress_type == 'right_icon'){
            ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.7071 4.99311C21.3166 4.60256 20.6835 4.60256 20.2929 4.99311L8.31228 16.9738L3.70713 12.3687C3.31662 11.9781 2.6835 11.9782 2.29291 12.3687C1.90236 12.7592 1.90236 13.3923 2.29291 13.7829L7.60517 19.095C7.99556 19.4855 8.62915 19.4853 9.01939 19.095L21.7071 6.40732C22.0977 6.01681 22.0976 5.38365 21.7071 4.99311Z" fill="#1CC985" stroke="#1CC985" stroke-width="0.4"/></svg>  
            <?php 
            }else if($affiliatepress_type == 'field_setting_icon'){
            ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_1605_666)"><mask id="mask0_1605_666" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="3" y="3" width="19" height="19"> <path d="M20.5714 20.5713V3.99986H4V20.5713H20.5714Z" fill="white" stroke="white" stroke-width="2"/> </mask> <g mask="url(#mask0_1605_666)"> <path class="ap-svg-hover-primary-color" d="M12.2851 9.432C10.7092 9.432 9.43169 10.7095 9.43169 12.2854C9.43169 13.8613 10.7092 15.1388 12.2851 15.1388C13.861 15.1388 15.1386 13.8613 15.1386 12.2854C15.1386 10.7095 13.861 9.432 12.2851 9.432ZM20.2941 10.2492L19.5216 10.8852C18.6403 11.6107 18.6403 12.9601 19.5216 13.6856L20.2941 14.3216C20.5679 14.547 20.6386 14.9373 20.4613 15.2444L18.9358 17.8867C18.7585 18.1938 18.3851 18.3277 18.053 18.2033L17.1159 17.8523C16.047 17.4518 14.8784 18.1265 14.6907 19.2524L14.5262 20.2395C14.4679 20.5893 14.1653 20.8457 13.8107 20.8457H10.7596C10.405 20.8457 10.1023 20.5893 10.044 20.2395L9.87951 19.2524C9.69187 18.1265 8.52321 17.4518 7.4543 17.8523L6.51724 18.2033C6.18514 18.3277 5.81178 18.1938 5.63445 17.8867L4.10894 15.2444C3.93165 14.9373 4.00234 14.547 4.27612 14.3216L5.04872 13.6856C5.92996 12.9601 5.92996 11.6107 5.04872 10.8852L4.27612 10.2492C4.00234 10.0238 3.93165 9.63353 4.10894 9.32641L5.63445 6.68415C5.81178 6.37704 6.18514 6.24312 6.51724 6.36753L7.4543 6.71858C8.52321 7.11902 9.69187 6.44432 9.87951 5.31839L10.044 4.33131C10.1023 3.98154 10.405 3.72517 10.7596 3.72517H13.8107C14.1653 3.72517 14.4679 3.98154 14.5262 4.33131L14.6907 5.31839C14.8784 6.44432 16.047 7.11902 17.1159 6.71858L18.053 6.36753C18.3851 6.24312 18.7585 6.37704 18.9358 6.68415L20.4613 9.32641C20.6386 9.63353 20.5679 10.0238 20.2941 10.2492Z" stroke="#000000" stroke-width="1.6" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/> </g> </g> <defs> <clipPath id="clip0_1605_666"> <rect width="24" height="24" fill="white"/> </clipPath> </defs> </svg> 
            <?php 
            }else if($affiliatepress_type == 'field_drag_icon'){
            ?>
                <svg width="14" height="10" viewBox="0 0 14 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1.5 0C0.675 0 0 0.675 0 1.5C0 2.325 0.675 3 1.5 3C2.325 3 3 2.325 3 1.5C3 0.675 2.325 0 1.5 0ZM12.5 0C11.675 0 11 0.675 11 1.5C11 2.325 11.675 3 12.5 3C13.325 3 14 2.325 14 1.5C14 0.675 13.325 0 12.5 0ZM7 0C6.175 0 5.5 0.675 5.5 1.5C5.5 2.325 6.175 3 7 3C7.825 3 8.5 2.325 8.5 1.5C8.5 0.675 7.825 0 7 0Z" fill="#929DB4"/><path d="M1.5 7C0.675 7 0 7.675 0 8.5C0 9.325 0.675 10 1.5 10C2.325 10 3 9.325 3 8.5C3 7.675 2.325 7 1.5 7ZM12.5 7C11.675 7 11 7.675 11 8.5C11 9.325 11.675 10 12.5 10C13.325 10 14 9.325 14 8.5C14 7.675 13.325 7 12.5 7ZM7 7C6.175 7 5.5 7.675 5.5 8.5C5.5 9.325 6.175 10 7 10C7.825 10 8.5 9.325 8.5 8.5C8.5 7.675 7.825 7 7 7Z" fill="#929DB4"/></svg>
            <?php 
            }else if($affiliatepress_type == 'mark_as_paid_icon'){
            ?>
           <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="32" height="32" rx="16" fill="#6858E0"/>
                <path d="M19 13.5C19 12.1193 17.6569 11 16 11C14.3431 11 13 12.1193 13 13.5C13 14.8807 14.3431 16 16 16C17.6569 16 19 17.1193 19 18.5C19 19.8807 17.6569 21 16 21C14.3431 21 13 19.8807 13 18.5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M26 16C26 10.4772 21.5228 6 16 6C10.4772 6 6 10.4772 6 16C6 21.5228 10.4772 26 16 26" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M16 21V21.5V22" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M16 10V10.5V11" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M19 24L22 26.5L27 20" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php 
            }else if($affiliatepress_type == 'mark_as_unpaid_icon'){
            ?>
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="32" height="32" rx="16" fill="#EB5160"/>
                <path d="M19 13.5C19 12.1193 17.6569 11 16 11C14.3431 11 13 12.1193 13 13.5C13 14.8807 14.3431 16 16 16C17.6569 16 19 17.1193 19 18.5C19 19.8807 17.6569 21 16 21C14.3431 21 13 19.8807 13 18.5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                <line x1="8.46967" y1="22.4697" x2="22.4697" y2="8.46967" stroke="white" stroke-width="1.5"/>
                <path d="M16 26C21.5228 26 26 21.5228 26 16C26 10.4772 21.5228 6 16 6C10.4772 6 6 10.4772 6 16C6 21.5228 10.4772 26 16 26Z" stroke="white" stroke-width="1.5"/>
                <path d="M16 21V21.5V22" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M16 10V10.5V11" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <?php 
            }else if($affiliatepress_type == 'payout_note_icon'){
            ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 14V7C20 5.34315 18.6569 4 17 4H12M20 14L13.5 20M20 14H15.5C14.3954 14 13.5 14.8954 13.5 16V20M13.5 20H7C5.34315 20 4 18.6569 4 17V12" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7 4V7M7 7V10M7 7H4M7 7H10" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>           
            <?php 
            }else if($affiliatepress_type == 'export_icon'){
            ?>
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 11C3 15.4183 6.58173 19 11 19C15.4183 19 19 15.4183 19 11" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round"/>
                <path d="M11 13V3M11 3L14 6.00001M11 3L8 6.00001" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            <?php 
            }else if($affiliatepress_type == 'import_icon'){
            ?>
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 11C3 15.4183 6.58173 19 11 19C15.4183 19 19 15.4183 19 11" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round"/>
                <path d="M11 3V13M11 13L14 10M11 13L8 10" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            <?php 
            }else if($affiliatepress_type == 'upload_field_icon'){
            ?>
            <svg width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0.000489249 9.72372C0.0880648 9.38614 0.140904 9.03437 0.269576 8.71343C0.761271 7.48443 1.66883 6.72072 2.95653 6.41836C3.10722 6.38313 3.14929 6.32198 3.16691 6.17716C3.47269 3.70156 4.73104 1.87715 6.94195 0.724972C7.99628 0.175545 9.13623 -0.049509 10.3197 0.00969015C11.7757 0.082099 13.0879 0.56988 14.2381 1.4745C15.405 2.39233 16.1922 3.56751 16.5973 4.99513C16.6423 5.15414 16.723 5.2241 16.8766 5.26814C18.9692 5.86795 20.3107 8.01184 19.9384 10.1508C19.6223 11.966 18.341 13.3143 16.5361 13.7272C16.3845 13.762 16.2279 13.7948 16.0738 13.7957C15.1046 13.8021 14.1354 13.7987 13.151 13.7987C13.151 13.4092 13.151 13.0036 13.151 12.5751C13.2254 12.5751 13.2948 12.5751 13.3643 12.5751C14.1001 12.5751 14.8355 12.577 15.5713 12.5746C17.1394 12.5687 18.3845 11.5476 18.6947 10.0158C19.0235 8.39101 17.9021 6.70212 16.2724 6.40711C15.8101 6.32345 15.5889 6.09448 15.4705 5.64388C14.8614 3.32778 13.3589 1.90749 11.0272 1.41873C8.80012 0.951983 6.4209 2.03958 5.23692 3.98533C4.72957 4.81901 4.44091 5.71727 4.40617 6.6943C4.40373 6.76573 4.39639 6.83716 4.39003 6.90859C4.35089 7.35821 4.13904 7.55488 3.69138 7.57788C2.77111 7.62583 2.06316 8.04267 1.59789 8.83916C0.661954 10.4419 1.80337 12.5154 3.65811 12.5667C4.64688 12.5941 5.63712 12.5736 6.62638 12.5746C6.70369 12.5746 6.7805 12.5746 6.87199 12.5746C6.87199 12.9934 6.87199 13.3985 6.87199 13.8246C6.80349 13.8246 6.73989 13.8246 6.67678 13.8246C5.73302 13.8246 4.78877 13.8261 3.84501 13.8241C1.92422 13.8202 0.441792 12.6069 0.0596883 10.7277C0.0391399 10.6274 0.01957 10.5271 0 10.4268C0.000489249 10.1924 0.000489249 9.95807 0.000489249 9.72372Z" fill="#9CA7BD"/>
                <path d="M12.9254 10.2386C12.6352 10.5282 12.3505 10.812 12.0569 11.1045C11.6161 10.6598 11.1557 10.196 10.6959 9.73169C10.6787 9.74098 10.6621 9.74979 10.645 9.75909C10.645 11.7312 10.645 13.7034 10.645 15.6878C10.2208 15.6878 9.8201 15.6878 9.39494 15.6878C9.39494 13.7161 9.39494 11.742 9.39494 9.74147C8.91352 10.2327 8.45656 10.698 8.00988 11.1534C7.6767 10.8208 7.38804 10.5326 7.08911 10.2346C7.35379 9.97044 7.62973 9.69597 7.90518 9.42053C8.43455 8.89165 8.96293 8.36179 9.49279 7.8334C9.84114 7.48653 10.1792 7.48506 10.5261 7.83096C11.2957 8.5981 12.0633 9.3672 12.8314 10.1363C12.8681 10.1725 12.9009 10.2116 12.9254 10.2386Z" fill="#9CA7BD"/>
            </svg>            
            <?php 
            }else if($affiliatepress_type == 'empty_report_icon'){
            ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="24" rx="12" fill="#DBDBDB"/>
                <path d="M12 16.6665V7.33317" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 11.3335L12 7.3335" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 11.3335L12 7.3335" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>            
            <?php 
            }else if($affiliatepress_type == 'performance_not_calculate'){
            ?>
            <svg width="96" height="96" viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_2088_563)">
            <path d="M-52 53.5854V149H97V28.349C93.7673 22.312 91.5876 9.28759 83.3343 6.26584C78.0663 4.33707 78.0444 12.5364 71.432 43.0086C68.6726 55.7248 59.5996 52.2972 56.6679 47.4118C56.3568 46.8932 56.1713 46.3139 56.0189 45.7286L55.2158 42.6442C54.2726 39.0217 48.4607 38.1133 45.7927 40.7389C41.8213 44.6469 37.3422 47.0241 35.7248 41.8854C34.7019 38.6351 33.1458 36.1533 31.4305 34.2891C27.1721 29.6608 20.9159 32.4761 18.7154 38.368C14.4801 49.7078 8.94504 67.7926 5.33152 71.6966C-0.977165 78.5124 -3.00071 75.4807 -8.47618 66.7565C-13.9516 58.0322 -19.9032 64.0629 -23.5932 66.1076C-27.2832 68.1524 -30.9732 58.2013 -31.8064 55.8839C-32.6397 53.5666 -37.52 56.5655 -40.0196 55.8839C-42.0194 55.3387 -42.7546 48.4195 -45.5591 48.7764L-52 53.5854Z" fill="url(#paint0_linear_2088_563)" stroke="#DBDBDB" stroke-width="2"/>
            </g>
            <defs>
            <linearGradient id="paint0_linear_2088_563" x1="22.5" y1="48.7556" x2="22.5" y2="149" gradientUnits="userSpaceOnUse">
            <stop stop-color="#FDFDFD"/>
            <stop offset="1" stop-color="#F0F0F0" stop-opacity="0"/>
            </linearGradient>
            <clipPath id="clip0_2088_563">
            <rect width="96" height="96" fill="white"/>
            </clipPath>
            </defs>
            </svg>                
            <?php 
            }else if($affiliatepress_type == 'facebook'){
            ?>
            <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_13_2105)">
                <rect width="50" height="50" rx="25" fill="#0866FF"/>
                <path d="M50 25C50 11.193 38.807 0 25 0C11.193 0 0 11.193 0 25C0 36.724 8.072 46.562 18.961 49.264V32.64H13.806V25H18.961V21.708C18.961 13.199 22.812 9.255 31.166 9.255C32.75 9.255 35.483 9.566 36.601 9.876V16.801C36.011 16.739 34.986 16.708 33.713 16.708C29.614 16.708 28.03 18.261 28.03 22.298V25H36.196L34.793 32.64H28.03V49.817C40.407 48.322 50 37.782 50 25Z" fill="#0866FF"/>
                <path d="M34.792 32.6399L36.195 24.9999H28.029V22.2979C28.029 18.2609 29.613 16.7079 33.712 16.7079C34.985 16.7079 36.01 16.7389 36.6 16.8009V9.87588C35.482 9.56488 32.749 9.25488 31.165 9.25488C22.811 9.25488 18.96 13.1989 18.96 21.7079V24.9999H13.805V32.6399H18.96V49.2639C20.894 49.7439 22.917 49.9999 24.999 49.9999C26.024 49.9999 27.035 49.9369 28.028 49.8169V32.6399H34.792Z" fill="white"/>
                </g>
                <defs>
                <clipPath id="clip0_13_2105">
                <rect width="50" height="50" rx="25" fill="white"/>
                </clipPath>
                </defs>
                </svg>
            <?php 
            }else if($affiliatepress_type == 'youtube'){
            ?>
           <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="50" height="50" rx="25" fill="#FF0000"/>
                <g clip-path="url(#clip0_13_2106)">
                <path d="M40.3336 17.4356C39.9648 16.0825 38.8821 15.0193 37.5044 14.6571C35.0072 14 25 14 25 14C25 14 14.9928 14 12.4982 14.6571C11.1205 15.0193 10.0379 16.0825 9.66908 17.4356C9 19.8855 9 25 9 25C9 25 9 30.1145 9.66908 32.5644C10.0379 33.9174 11.1205 34.9807 12.4982 35.3429C14.9928 36 25 36 25 36C25 36 35.0072 36 37.5018 35.3429C38.8795 34.9807 39.9621 33.9174 40.3309 32.5644C41 30.1145 41 25 41 25C41 25 41 19.8855 40.3336 17.4356Z" fill="white"/>
                <path d="M21.7995 29.7137L30.1156 25.0002L21.7995 20.2866V29.7137Z" fill="#FF0000"/>
                </g>
                <defs>
                <clipPath id="clip0_13_2106">
                <rect width="32" height="22" fill="white" transform="translate(9 14)"/>
                </clipPath>
                </defs>
            </svg>

            <?php 
            }
            elseif ($affiliatepress_type == 'help_close') {
            ?>
             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" width="24" height="24" fill="#fff">
                <path d="M764.288 214.592 512 466.88 259.712 214.592a31.936 31.936 0 0 0-45.12 45.12l252.288 252.288-252.288 252.288a31.936 31.936 0 0 0 45.12 45.184L512 557.184l252.288 252.288a31.936 31.936 0 0 0 45.12-45.184L557.184 512l252.288-252.288a31.936 31.936 0 0 0-45.12-45.12z"/>
            </svg>
            <?php
            }elseif ($affiliatepress_type == 'redirect_icon') {
                ?>
                 <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="ap-svg-hover-primary-color" d="M23.3333 4.66699L14 14.0003M23.3333 4.66699V9.91699M23.3333 4.66699H18.0833M22.1666 14.5837V19.6003C22.1666 20.9071 22.1666 21.5606 21.9123 22.0597C21.6886 22.4987 21.3316 22.8557 20.8926 23.0793C20.3935 23.3337 19.7401 23.3337 18.4333 23.3337H8.39996C7.09318 23.3337 6.43977 23.3337 5.94065 23.0793C5.5016 22.8557 5.14464 22.4987 4.92095 22.0597C4.66663 21.5606 4.66663 20.9071 4.66663 19.6003V9.56699C4.66663 8.26021 4.66663 7.60681 4.92095 7.10768C5.14464 6.66863 5.5016 6.31168 5.94065 6.08798C6.43977 5.83366 7.09316 5.83366 8.39996 5.83366H13.4166" stroke="#4D5973" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php
            }
            elseif ($affiliatepress_type == 'view_log_icon') {
                ?>
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.45617 11.4718C1.81872 10.6436 1.5 10.2296 1.5 9C1.5 7.77045 1.81872 7.3564 2.45617 6.52825C3.72897 4.87467 5.86358 3 9 3C12.1364 3 14.271 4.87467 15.5438 6.52825C16.1812 7.3564 16.5 7.77045 16.5 9C16.5 10.2296 16.1812 10.6436 15.5438 11.4718C14.271 13.1253 12.1364 15 9 15C5.86358 15 3.72897 13.1253 2.45617 11.4718Z" stroke="#4D5973" stroke-width="1.5"/>
                    <path d="M11.25 9C11.25 10.2427 10.2427 11.25 9 11.25C7.75732 11.25 6.75 10.2427 6.75 9C6.75 7.75732 7.75732 6.75 9 6.75C10.2427 6.75 11.25 7.75732 11.25 9Z" stroke="#4D5973" stroke-width="1.5"/>
                </svg>
                <?php
            }
            elseif ($affiliatepress_type == 'download_log') {
                ?>
                <svg width="16" height="16" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.25 11.25C2.25 13.3713 2.25 14.4319 2.90901 15.091C3.56802 15.75 4.62868 15.75 6.75 15.75H11.25C13.3713 15.75 14.4319 15.75 15.091 15.091C15.75 14.4319 15.75 13.3713 15.75 11.25" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 2.25V12M9 12L12 8.71875M9 12L6 8.71875" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php
            }
            elseif ($affiliatepress_type == 'delete_log') {
                ?>
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.75 4.5H2.25" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M14.25 4.5L13.8966 11.1231C13.7606 13.6718 13.6926 14.9462 13.028 15.7231C12.3635 16.5 11.3413 16.5 9.29706 16.5H8.70294C6.65863 16.5 5.6365 16.5 4.97192 15.7231C4.30735 14.9462 4.23936 13.6718 4.10337 11.1231L3.75 4.5" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M4.5 4.5C4.54572 4.5 4.56858 4.5 4.5893 4.49936C5.26303 4.47849 5.85738 3.95491 6.08663 3.18032C6.09369 3.15649 6.10091 3.12999 6.11537 3.07697L6.19481 2.78571C6.26262 2.53708 6.29652 2.41276 6.34149 2.3072C6.52092 1.88607 6.85288 1.59364 7.2365 1.51877C7.33265 1.5 7.43989 1.5 7.65434 1.5H10.3457C10.5601 1.5 10.6674 1.5 10.7635 1.51877C11.1472 1.59364 11.4791 1.88607 11.6585 2.3072C11.7035 2.41276 11.7374 2.53708 11.8052 2.78571L11.8847 3.07697C11.8991 3.12992 11.9063 3.15651 11.9134 3.18032C12.1426 3.95491 12.737 4.47849 13.4107 4.49936C13.4314 4.5 13.4543 4.5 13.5 4.5" stroke="#4D5973" stroke-width="1.5"/>
                    </svg>

                <?php
            }
            elseif ($affiliatepress_type == 'add_round') {
                ?>
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect class="ap-hover-primary" x="0.5" y="0.5" width="27" height="27" rx="13.5" fill="white"/>
                        <rect class="ap-hover-primary" x="0.5" y="0.5" width="27" height="27" rx="13.5" stroke="#C9CFDB"/>
                        <path class="ap-hover-background-color" d="M14 9.33301V18.6663M9.33337 13.9997H18.6667" stroke="#4D5973" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                <?php
            }
            elseif ($affiliatepress_type == 'minus') {
                ?>
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect class="ap-hover-danger" x="0.5" y="0.5" width="27" height="27" rx="13.5" fill="white"/>
                        <rect class="ap-hover-danger" x="0.5" y="0.5" width="27" height="27" rx="13.5" stroke="#C9CFDB"/>
                        <path class="ap-hover-background-color" d="M9.33337 14H18.6667" stroke="#4D5973" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                <?php
            }
            elseif ($affiliatepress_type == 'file-upload-icon') {
                ?>
                    <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path class="ap-primary-color-fill" fill-rule="evenodd" clip-rule="evenodd" d="M31.1875 11.5C29.7385 11.5 28.5625 10.324 28.5625 8.875V3.625L36.4375 11.5H31.1875ZM28.5625 1V1.03675C28.3945 1.03675 10.1875 1 10.1875 1C7.28819 1 4.9375 3.35069 4.9375 6.25V37.75C4.9375 40.6493 7.28819 43 10.1875 43H33.8125C36.7118 43 39.0625 40.6493 39.0625 37.75V14.125V11.5L28.5625 1Z" fill="#6858E0" fill-opacity="0.12"/>
                        <rect class="ap-primary-color-fill" x="24" y="24" width="26" height="26" rx="13" fill="#6858E0"/>
                        <path class="ap-background-color-stroke" d="M31 39C31 40.8856 31 41.8284 31.5858 42.4142C32.1716 43 33.1144 43 35 43H39C40.8856 43 41.8284 43 42.4142 42.4142C43 41.8284 43 40.8856 43 39" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path class="ap-background-color-stroke" d="M37.0007 39.6667V31M37.0007 31L39.6673 33.9167M37.0007 31L34.334 33.9167" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                <?php
            }
            elseif ($affiliatepress_type == 'more_info') {
                ?>
                   <svg width="9" height="10" viewBox="0 0 9 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path class="ap-hover-primary" d="M4.00098 1.46436L7.53651 4.99989L4.00098 8.53542" stroke="#576582" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                <?php
            }
            elseif ($affiliatepress_type == 'popup_close') {
                ?>
                   <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 18L18 6" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6 6L18 18" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                <?php
            }
            
            do_action('affiliatepress_extra_common_icon',$affiliatepress_type);
        }

        function affiliatepress_get_premium_content(){
            $affiliatepress_content = '
                <div class="ap-premium-content" @click="open_premium_modal">
                    <div>
                        <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.1199 11.8872H2.87992C2.35064 11.8872 1.91992 12.3179 1.91992 12.8472C1.91992 13.3765 2.35064 13.8072 2.87992 13.8072H13.1199C13.6492 13.8072 14.0799 13.3765 14.0799 12.8472C14.0799 12.3179 13.6492 11.8872 13.1199 11.8872Z" fill="#FA6732"/><path d="M14.72 2.20003C14.0141 2.20003 13.44 2.77412 13.44 3.48003C13.44 3.95428 13.7024 4.3645 14.087 4.58594C13.3466 6.33953 12.1913 7.41537 11.1117 7.31809C9.91103 7.22019 8.93181 5.80065 8.39166 3.42562C9.08412 3.25025 9.59997 2.62625 9.59997 1.88003C9.59997 0.997467 8.88253 0.280029 7.99997 0.280029C7.11741 0.280029 6.39997 0.997467 6.39997 1.88003C6.39997 2.62628 6.91581 3.25028 7.60828 3.42562C7.06812 5.80065 6.08891 7.22019 4.88828 7.31809C3.81309 7.41537 2.65275 6.33953 1.91294 4.58594C2.29756 4.3645 2.55997 3.95425 2.55997 3.48003C2.55997 2.77412 1.98588 2.20003 1.27997 2.20003C0.574094 2.20003 0 2.77412 0 3.48003C0 4.13669 0.499188 4.673 1.13663 4.74594L2.36928 11.16H13.6307L14.8633 4.74594C15.5008 4.673 16 4.13669 16 3.48003C16 2.77412 15.4259 2.20003 14.72 2.20003Z" fill="#FA6732"/></svg>
                    </div>
                    <div class="ap-premium-content-text">'.esc_html__('Premium', 'affiliatepress-affiliate-marketing').'</div>
                </div>
            ';
 
            return $affiliatepress_content;
        }

        /**lifetime deal notice dismissed */
        function affiliatepress_lifetime_deal_close_func(){
            global $wpdb;
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

            update_option('affiliatepress_lifetime_deal_notice_dismissed', current_time('timestamp'));

			$response['variant']        = 'success';
			$response['title']          = esc_html__('Success', 'affiliatepress-affiliate-marketing');
			$response['msg']            = esc_html__('Notice Set successfully', 'affiliatepress-affiliate-marketing');

			echo wp_json_encode($response);
			exit;
        }

         /**lifetime deal content get */
         function affiliatepress_get_lifetime_deal_content(){

            global $affiliatepress_website_url,$AffiliatePress;

            $affiliatepress_lifetime_deal_content = get_transient( 'affiliatepress_lifetime_deal_content' );
            if(!empty($affiliatepress_lifetime_deal_content)){
                return;
            }

            $url = $affiliatepress_website_url.'ap_misc/ap_exclusive_offers_at_panel.php';
            $affiliatepress_lifetime_deal_res = wp_remote_post(
                $url,
                array(
                    'method'    => 'POST',
                    'timeout'   => 45,
                    'sslverify' => false,
                    'body'      => array(
                        'affiliatepress_lifetime_deal' => 1,
                    ),
                )
            ); 
            
            if ( ! is_wp_error( $affiliatepress_lifetime_deal_res ) ) {
                $affiliatepress_body_res = base64_decode( $affiliatepress_lifetime_deal_res['body'] );
                if ( ! empty( $affiliatepress_body_res ) ) {
                    $affiliatepress_lifetime_deal_content = json_decode( $affiliatepress_body_res, true );
                    if(is_array($affiliatepress_lifetime_deal_content)){
                        set_transient( 'affiliatepress_lifetime_deal_content', $affiliatepress_body_res, 86400 );
                    }
                }
            }
        }

        /** affiliatepress lifetime deal content  */
        function affiliatepress_lifetime_deal_header_belt(){

            global $AffiliatePress;

            $affiliatepress_lifetime_deal_belt = "";
            if(!$AffiliatePress->affiliatepress_pro_install()){

                $affiliatepress_get_lifetime_deal_content = "";  
                $affiliatepress_lifetime_deal_content = get_transient( 'affiliatepress_lifetime_deal_content' );              
                if(empty($affiliatepress_lifetime_deal_content)){
                    return $affiliatepress_lifetime_deal_belt;
                }

                $affiliatepress_lifetime_deal_content = json_decode( $affiliatepress_lifetime_deal_content, true );
                $affiliatepress_get_lifetime_deal_content = $affiliatepress_lifetime_deal_content['lifetime_deal_content'];

                $affiliatepress_lifetime_deal_start_time = $affiliatepress_lifetime_deal_content['deal_start_date'];
                $affiliatepress_lifetime_deal_end_time   = $affiliatepress_lifetime_deal_content['deal_end_date'];
                $current_time = time();
        
                $affiliatepress_lifetime_deal_notice_show = get_option('affiliatepress_lifetime_deal_notice_dismissed');
        
                $affiliatepress_is_in_deal_period = ($current_time >= $affiliatepress_lifetime_deal_start_time && $current_time <= $affiliatepress_lifetime_deal_end_time);
                $affiliatepress_dismissed_this_sale = ($affiliatepress_lifetime_deal_notice_show >= $affiliatepress_lifetime_deal_start_time && $affiliatepress_lifetime_deal_notice_show <= $affiliatepress_lifetime_deal_end_time);
        
                if ($affiliatepress_is_in_deal_period && !$affiliatepress_dismissed_this_sale) 
                {
                    $affiliatepress_lifetime_deal_belt = '<div class="ap-header-deal-belt-wrapper">
                        <div class="ap-belt-content">
                            <div class="ap-belt-main-content">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_8111_5858)"><path d="M8 0C3.58862 0 0 3.58862 0 8C0 12.4114 3.58862 16 8 16C12.4114 16 16 12.4114 16 8C16 3.58862 12.4114 0 8 0ZM11.8047 12.1379C11.6747 12.2679 11.504 12.3334 11.3334 12.3334C11.1627 12.3334 10.9919 12.2679 10.8621 12.1379L7.52869 8.80469C7.40332 8.68005 7.33337 8.51062 7.33337 8.33337V4C7.33337 3.63135 7.63196 3.33337 8 3.33337C8.36804 3.33337 8.66663 3.63135 8.66663 4V8.05737L11.8047 11.1953C12.0653 11.4561 12.0653 11.8773 11.8047 12.1379Z" fill="#A8ACAF"/></g><defs><clipPath id="clip0_8111_5858"><rect width="16" height="16" fill="white"/></clipPath></defs></svg>
                                '.$affiliatepress_get_lifetime_deal_content.'
                                <div class="ap-deal-close">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" @click="affiliatepress_lifetime_deal_close">
                                    <path d="M13.5 13.5L9 9M9 9L4.5 4.5M9 9L13.5 4.5M9 9L4.5 13.5" stroke="#A8ACAF" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
            }
            return $affiliatepress_lifetime_deal_belt;
        }

        /** validate affiliate plugin */
        function affiliatepress_validate_plugin_setup(){

            global $affiliatepress_website_url;

            $ap_plugin_setup_check_time = get_option( 'affiliatepress_validate_plugin_setup_timings' );

            if( empty( $ap_plugin_setup_check_time ) || current_time( 'timestamp' ) > $ap_plugin_setup_check_time ){

                $validate_setup_timings = 2 * DAY_IN_SECONDS;

                update_option( 'affiliatepress_validate_plugin_setup_timings', ( current_time('timestamp') + $validate_setup_timings ) );

                parent::load();

                if (!function_exists('is_plugin_active')) {
                    include_once ABSPATH . 'wp-admin/includes/plugin.php';
                }

                $ap_validate = get_option( 'affiliatepress_version' );
                $ap_pro_validate = get_option( 'affiliatepress_pro_version' );
                $avlv = !empty( $ap_validate ) ? 1 : 0;
                $avpv = !empty( $ap_pro_validate ) ? 1 : 0;

                $avava_data = [];
                $avavd_data = [];

                $avav_url =  $affiliatepress_website_url.'ap_misc/addons_list.php';
                $avav_resp = wp_remote_post(
                    $avav_url,
                    array(
                        'method'    => 'POST',
                        'timeout'   => 45,
                        'sslverify' => false,
                        'body'      => array(
                            'affiliatepress_addons_list' => 1,
                        ),
                    )
                );            
                if ( ! is_wp_error( $avav_resp ) ) {
                    $avav_data = base64_decode( $avav_resp['body'] );
                    if( !empty( $avav_data ) ){
                        $avav_response = json_decode( $avav_data, true );
                        if( !empty( $avav_response ) && is_array( $avav_response ) )
                        {
                            $avav_filtered = array_values( $avav_response );
                            $avallav = array_merge( ...$avav_filtered );
                            if( !empty( $avallav ) ) {
                                foreach( $avallav as $avav_details ){
                                    $avav_installer = $avav_details['addon_installer'];

                                    if( file_exists( WP_PLUGIN_DIR . '/' . $avav_installer ) ){
                                        $avavpdata = get_plugin_data( WP_PLUGIN_DIR . '/' . $avav_installer );
                                        $avavactv = is_plugin_active( $avav_installer );
                                        if( $avavactv ){
                                            $avava_data[ $avav_details['addon_name'] ] = $avavpdata['Version'];
                                        } else {
                                            $avavd_data[ $avav_details['addon_name'] ] = $avavpdata['Version'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $avav_setup_data = [
                    'avlv' => $avlv,
                    'avpv' => $avpv.static::$checksum,
                    'avava' => $avava_data,
                    'avavd' => $avavd_data,
                    'avurl' => home_url()
                ];

                $ap_validation_data = wp_json_encode( $avav_setup_data );
                
                $ap_validation_url = $affiliatepress_website_url.'ap_misc/validate_plugin_setup.php';
                $ap_validate_setup_req = wp_remote_post(
                    $ap_validation_url,
                    [
                        'method'    => 'POST',
                        'timeout'   => 45,
                        'sslverify' => false,
                        'body'      => [
                            'avld'  => $ap_validation_data
                        ]
                    ]
                );

            }

        }
    }
}
global $AffiliatePress;
$AffiliatePress = new AffiliatePress();
