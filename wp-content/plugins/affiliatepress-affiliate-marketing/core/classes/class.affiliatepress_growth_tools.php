<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_growth_tools') ) {
    class affiliatepress_growth_tools Extends AffiliatePress_Core{
        
        function __construct(){

            add_action( 'admin_init', array( $this, 'affiliatepress_growth_tools_vue_data_fields') );

            /* Dynamic Vue Fields */
            add_filter('affiliatepress_growth_tools_dynamic_data_fields',array($this,'affiliatepress_growth_tools_dynamic_data_fields_func'),10,1);

            /** Load wizard view file */
            add_filter('affiliatepress_growth_tools_dynamic_view_load', array( $this, 'affiliatepress_load_growth_tools_view_func'), 10);

            /** Function for wizard vue method */
            add_filter('affiliatepress_growth_tools_dynamic_vue_methods', array( $this, 'affiliatepress_growth_tools_vue_methods_func'),10,1);

            add_action('wp_ajax_affiliatepress_get_arforms', array( $this, 'affiliatepress_get_arforms_func'));  
            add_action('wp_ajax_affiliatepress_activate_arforms', array( $this, 'affiliatepress_activate_arforms_func')); 
            
            add_action('wp_ajax_affiliatepress_get_armember', array( $this, 'affiliatepress_get_armember_func'));   
            add_action('wp_ajax_affiliatepress_activate_armember', array( $this, 'affiliatepress_activate_armember_func'));    
            
            add_action('wp_ajax_affiliatepress_get_arprice', array( $this, 'affiliatepress_get_arprice_func')); 
            add_action('wp_ajax_affiliatepress_activate_arprice', array( $this, 'affiliatepress_activate_arprice_func'));   
            
            add_action('wp_ajax_affiliatepress_get_bookingpress', array( $this, 'affiliatepress_get_bookingpress_func')); 
            add_action('wp_ajax_affiliatepress_activate_bookingpress', array( $this, 'affiliatepress_activate_bookingpress_func')); 


        }

         /**
         * Function for affiliates default Vue Data
         *
         * @return void
        */
        function affiliatepress_growth_tools_vue_data_fields(){

            global $AffiliatePress,$affiliatepress_growth_tools_vue_data_fields,$affiliatepress_global_options;   
        }

        /**
         * Function for commission vue data
         *
         * @param  array $affiliatepress_growth_tools_vue_data_fields
         * @return json
         */
        function affiliatepress_growth_tools_dynamic_data_fields_func($affiliatepress_growth_tools_vue_data_fields){            
            
            global $affiliatepress_growth_tools_vue_data_fields;

            $affiliatepress_growth_tools_vue_data_fields = array(
                'is_display_loader'          => '0',
                'is_disabled'                => false,
                'is_display_save_loader'     => '0',
                'is_display_arforms_save_loader' => '0',
                'is_display_arprice_save_loader' => '0',
                'is_display_bookingpress_save_loader' => '0',
            );
            
            return wp_json_encode($affiliatepress_growth_tools_vue_data_fields);

        }

        /**
         * Load Growth Tools view file
         *
         * @return HTML
        */
        function affiliatepress_load_growth_tools_view_func(){
            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/growth_tools/manage_growth_tools.php';
			require $affiliatepress_load_file_name;
        }

        /**
         * Function for wizard vue method
         *
         * @param  string $affiliatepress_wizard_vue_methods
         * @return string
        */
        function affiliatepress_growth_tools_vue_methods_func($affiliatepress_growth_tools_vue_methods){

            global $affiliatepress_notification_duration;

            $affiliatepress_growth_tools_vue_methods.= ' 
            affiliatepress_download_plugins( plugin_data ){
                if(plugin_data == "arforms"){
                    const vm = this;
                    vm.is_disabled = true
                    vm.is_display_arforms_save_loader = "1"
                    vm.savebtnloading = true

                    var postData = { action:"affiliatepress_get_arforms",_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(  response.data.variant == "success" ){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            location.reload();
                        }

                        vm.is_disabled = false
                        vm.is_display_arforms_save_loader = "0"
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                }

                if( plugin_data == "armember" ){

                    const vm = this;
                    vm.is_disabled = true
                    vm.is_display_save_loader = "1"
                    vm.savebtnloading = true

                    var postData = { action:"affiliatepress_get_armember",_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(  response.data.variant == "success" ){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            location.reload();
                        }

                        vm.is_disabled = false
                        vm.is_display_save_loader = "0"
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                }

                if( plugin_data == "arprice" ){

                    const vm = this;
                    vm.is_disabled = true
                    vm.is_display_arprice_save_loader = "1"
                    vm.savebtnloading = true

                    var postData = { action:"affiliatepress_get_arprice",_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(  response.data.variant == "success" ){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            location.reload();
                        }

                        vm.is_disabled = false
                        vm.is_display_arprice_save_loader = "0"
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                }

                if( plugin_data == "bookingpress" ){

                    const vm = this;
                    vm.is_disabled = true
                    vm.is_display_bookingpress_save_loader = "1"
                    vm.savebtnloading = true

                    var postData = { action:"affiliatepress_get_bookingpress",_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(  response.data.variant == "success" ){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            location.reload();
                        }

                        vm.is_disabled = false
                        vm.is_display_bookingpress_save_loader = "0"
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                }
            },

            affiliatepress_activate_plugins( plugin_data ){
                if(plugin_data == "arforms"){
                    const vm = this;
                    vm.is_disabled = true
                    vm.is_display_arforms_save_loader = "1"
                    vm.savebtnloading = true

                    var postData = { action:"affiliatepress_activate_arforms",_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(  response.data.variant == "success" ){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            location.reload();
                        }

                        vm.is_disabled = false
                        vm.is_display_arforms_save_loader = "0"
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                }

                if( plugin_data == "armember" ){

                    const vm = this;
                    vm.is_disabled = true
                    vm.is_display_save_loader = "1"
                    vm.savebtnloading = true

                    var postData = { action:"affiliatepress_activate_armember",_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(  response.data.variant == "success" ){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            location.reload();
                        }

                        vm.is_disabled = false
                        vm.is_display_save_loader = "0"
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                }

                if( plugin_data == "arprice" ){

                    const vm = this;
                    vm.is_disabled = true
                    vm.is_display_arprice_save_loader = "1"
                    vm.savebtnloading = true

                    var postData = { action:"affiliatepress_activate_arprice",_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(  response.data.variant == "success" ){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            location.reload();
                        }

                        vm.is_disabled = false
                        vm.is_display_arprice_save_loader = "0"
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                }

                if( plugin_data == "bookingpress" ){

                    const vm = this;
                    vm.is_disabled = true
                    vm.is_display_bookingpress_save_loader = "1"
                    vm.savebtnloading = true

                    var postData = { action:"affiliatepress_activate_bookingpress",_wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" }    
                    axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                    .then( function (response) {
                        if(  response.data.variant == "success" ){
                            vm.$notify({
                                title: response.data.title,
                                message: response.data.msg,
                                type: response.data.variant,
                                duration:'.intval($affiliatepress_notification_duration).',
                            });
                            location.reload();
                        }

                        vm.is_disabled = false
                        vm.is_display_bookingpress_save_loader = "0"
                    }.bind(this) )
                    .catch( function (error) {
                        console.log(error);
                    });
                }
            },

            goToLearnMore(plugin_name) {
                if(plugin_name == "arprice"){
                    window.open("https://www.arpriceplugin.com/", "_blank");
                }

                if(plugin_name == "arforms"){
                    window.open("https://www.arformsplugin.com/", "_blank");
                }

                if(plugin_name == "armember"){
                    window.open("https://www.armemberplugin.com/", "_blank");
                }

                if(plugin_name == "bookingpress"){
                    window.open("https://www.bookingpressplugin.com/", "_blank");
                }
            },
            ';
            return $affiliatepress_growth_tools_vue_methods;
        }

        function affiliatepress_get_arforms_func() {

           global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_plugin', true, 'ap_wp_nonce' ); // phpcs:ignore
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_growth_tools')){
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

            if ( ! file_exists( WP_PLUGIN_DIR . '/arforms-form-builder/arforms-form-builder.php' ) ) {
        
                if ( ! function_exists( 'plugins_api' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                }
                $response = plugins_api(
                    'plugin_information',
                    array(
                        'slug'   => 'arforms-form-builder',
                        'fields' => array(
                            'sections' => false,
                            'versions' => true,
                        ),
                    )
                );
                if ( ! is_wp_error( $response ) && property_exists( $response, 'versions' ) ) {
                    if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                    }
                    $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                    $source   = ! empty( $response->download_link ) ? $response->download_link : '';
                    
                    if ( ! empty( $source ) ) {
                        if ( $upgrader->install( $source ) === true ) {
                            activate_plugin( 'arforms-form-builder/arforms-form-builder.php' );
                            $arf_install_activate = 1; 
                        }
                    }
                } else {
                    $package_data = $this->affiliatepress_pro_force_check_for_plugin_update( ['version', 'dwlurl'], false, 'arforms-form-builder' );
                    $package_url = !empty( $package_data['dwlurl'] ) ? $package_data['dwlurl'] : '';
                    if( !empty( $package_url ) ) {
                        if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                        }
                        $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                        if ( ! empty( $package_url ) ) {
                            if ( $upgrader->install( $package_url ) === true ) {
                                activate_plugin( 'arforms-form-builder/arforms-form-builder.php' );
                                $arm_install_activate = 1;
                            } 
                        }
                    }
                }
            }
            if( $arf_install_activate = 1 ){

                $response_data['variant']               = 'success';
                $response_data['title']                 = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response_data['msg']                   = esc_html__('ARForms Successfully installed.', 'affiliatepress-affiliate-marketing');
            } else {

                $response_data['variant']               = 'error';
                $response_data['title']                 = esc_html__('error', 'affiliatepress-affiliate-marketing');
                $response_data['msg']                   = esc_html__('Somthing went wrong please try again later.', 'affiliatepress-affiliate-marketing');
            }
            wp_send_json($response_data);
            die;
        }

        function affiliatepress_activate_arforms_func() {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_plugin', true, 'ap_wp_nonce' ); // phpcs:ignore
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');

            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }
 
            if(!current_user_can('affiliatepress_growth_tools')){
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

            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $plugin_file = 'arforms-form-builder/arforms-form-builder.php';

            if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
                $activate_result = activate_plugin( $plugin_file );

                if ( ! is_wp_error( $activate_result ) ) {
                    $response_data = array(
                        'variant' => 'success',
                        'title'   => esc_html__( 'Success', 'affiliatepress-affiliate-marketing' ),
                        'msg'     => esc_html__( 'ARForms Successfully Activated.', 'affiliatepress-affiliate-marketing' ),
                    );
                } else {
                    $response_data = array(
                        'variant' => 'error',
                        'title'   => esc_html__( 'Error', 'affiliatepress-affiliate-marketing' ),
                        'msg'     => esc_html__( 'Activation failed:', 'affiliatepress-affiliate-marketing' ).$activate_result->get_error_message(),
                    );
                }
            } else {
                $response_data = array(
                    'variant' => 'error',
                    'title'   => esc_html__( 'Error', 'affiliatepress-affiliate-marketing' ),
                    'msg'     => esc_html__( 'ARForms plugin is not installed.', 'affiliatepress-affiliate-marketing' ),
                );
            }

            wp_send_json( $response_data );
            die;
        }

        function affiliatepress_get_bookingpress_func() {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
             $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_plugin', true, 'ap_wp_nonce' ); // phpcs:ignore
             $response = array();
             $response['variant'] = 'error';
             $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
             $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');
             if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                 $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                 $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                 $response['variant'] = 'error';
                 $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                 $response['msg'] = $affiliatepress_error_msg;
                 wp_send_json( $response );
                 die;
             }
 
             if(!current_user_can('affiliatepress_growth_tools')){
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
 
             if ( ! file_exists( WP_PLUGIN_DIR . '/bookingpress-appointment-booking/bookingpress-appointment-booking.php' ) ) {
         
                 if ( ! function_exists( 'plugins_api' ) ) {
                     require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                 }
                 $response = plugins_api(
                     'plugin_information',
                     array(
                         'slug'   => 'bookingpress-appointment-booking',
                         'fields' => array(
                             'sections' => false,
                             'versions' => true,
                         ),
                     )
                 );
                 if ( ! is_wp_error( $response ) && property_exists( $response, 'versions' ) ) {
                     if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                         require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                     }
                     $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                     $source   = ! empty( $response->download_link ) ? $response->download_link : '';
                     
                     if ( ! empty( $source ) ) {
                         if ( $upgrader->install( $source ) === true ) {
                             activate_plugin( 'bookingpress-appointment-booking/bookingpress-appointment-booking.php' );
                             $arf_install_activate = 1; 
                         }
                     }
                 } else {
                     $package_data = $this->affiliatepress_pro_force_check_for_plugin_update( ['version', 'dwlurl'], false, 'bookingpress-appointment-booking' );
                     $package_url = !empty( $package_data['dwlurl'] ) ? $package_data['dwlurl'] : '';
                     if( !empty( $package_url ) ) {
                         if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                             require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                         }
                         $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                         if ( ! empty( $package_url ) ) {
                             if ( $upgrader->install( $package_url ) === true ) {
                                 activate_plugin( 'bookingpress-appointment-booking/bookingpress-appointment-booking.php' );
                                 $arm_install_activate = 1;
                             } 
                         }
                     }
                 }
             }
             if( $arf_install_activate = 1 ){
 
                 $response_data['variant']               = 'success';
                 $response_data['title']                 = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                 $response_data['msg']                   = esc_html__('BookingPress Successfully installed.', 'affiliatepress-affiliate-marketing');
             } else {
 
                 $response_data['variant']               = 'error';
                 $response_data['title']                 = esc_html__('error', 'affiliatepress-affiliate-marketing');
                 $response_data['msg']                   = esc_html__('Somthing went wrong please try again later.', 'affiliatepress-affiliate-marketing');
             }
             wp_send_json($response_data);
             die;
        }

        function affiliatepress_activate_bookingpress_func() {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_plugin', true, 'ap_wp_nonce' ); // phpcs:ignore
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');

            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }
 
            if(!current_user_can('affiliatepress_growth_tools')){
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

            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $plugin_file = 'bookingpress-appointment-booking/bookingpress-appointment-booking.php';

            if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
                $activate_result = activate_plugin( $plugin_file );

                if ( ! is_wp_error( $activate_result ) ) {
                    $response_data = array(
                        'variant' => 'success',
                        'title'   => esc_html__( 'Success', 'affiliatepress-affiliate-marketing' ),
                        'msg'     => esc_html__( 'BookingPress Successfully Activated.', 'affiliatepress-affiliate-marketing' ),
                    );
                } else {
                    $response_data = array(
                        'variant' => 'error',
                        'title'   => esc_html__( 'Error', 'affiliatepress-affiliate-marketing' ),
                        'msg'     => esc_html__( 'Activation failed:', 'affiliatepress-affiliate-marketing' ).$activate_result->get_error_message(),
                    );
                }
            } else {
                $response_data = array(
                    'variant' => 'error',
                    'title'   => esc_html__( 'Error', 'affiliatepress-affiliate-marketing' ),
                    'msg'     => esc_html__( 'BookingPress plugin is not installed.', 'affiliatepress-affiliate-marketing' ),
                );
            }

            wp_send_json( $response_data );
            die;
        }

        function affiliatepress_get_armember_func(){

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_plugin', true, 'ap_wp_nonce' ); // phpcs:ignore
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_growth_tools')){
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

            if ( ! file_exists( WP_PLUGIN_DIR . '/armember-membership/armember-membership.php' ) ) {
        
                if ( ! function_exists( 'plugins_api' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                }
                $response = plugins_api(
                    'plugin_information',
                    array(
                        'slug'   => 'armember-membership',
                        'fields' => array(
                            'sections' => false,
                            'versions' => true,
                        ),
                    )
                );
                if ( ! is_wp_error( $response ) && property_exists( $response, 'versions' ) ) {
                    if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                    }
                    $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                    $source   = ! empty( $response->download_link ) ? $response->download_link : '';
                    
                    if ( ! empty( $source ) ) {
                        if ( $upgrader->install( $source ) === true ) {
                            activate_plugin( 'armember-membership/armember-membership.php' );
                            $arm_install_activate = 1; 
                        }
                    }
                } else {
                    $package_data = $this->affiliatepress_pro_force_check_for_plugin_update( ['version', 'dwlurl'], false, 'armember-membership' );
                    $package_url = !empty( $package_data['dwlurl'] ) ? $package_data['dwlurl'] : '';
                    if( !empty( $package_url ) ) {
                        if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                        }
                        $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                        if ( ! empty( $package_url ) ) {
                            if ( $upgrader->install( $package_url ) === true ) {
                                activate_plugin( 'armember-membership/armember-membership.php' );
                                $arm_install_activate = 1;
                            } 
                        }
                    }
                }
            }
            if( $arm_install_activate = 1 ){

                $response_data['variant']               = 'success';
                $response_data['title']                 = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response_data['msg']                   = esc_html__('ARMember Successfully installed.', 'affiliatepress-affiliate-marketing');
            } else {

                $response_data['variant']               = 'error';
                $response_data['title']                 = esc_html__('error', 'affiliatepress-affiliate-marketing');
                $response_data['msg']                   = esc_html__('Somthing went wrong please try again later.', 'affiliatepress-affiliate-marketing');
            }
            wp_send_json($response_data);
            die;
        }

        function affiliatepress_activate_armember_func() {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_plugin', true, 'ap_wp_nonce' ); // phpcs:ignore
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');

            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }
 
            if(!current_user_can('affiliatepress_growth_tools')){
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

            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $plugin_file = 'armember-membership/armember-membership.php';

            if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
                $activate_result = activate_plugin( $plugin_file );

                if ( ! is_wp_error( $activate_result ) ) {
                    $response_data = array(
                        'variant' => 'success',
                        'title'   => esc_html__( 'Success', 'affiliatepress-affiliate-marketing' ),
                        'msg'     => esc_html__( 'ARMember Successfully Activated.', 'affiliatepress-affiliate-marketing' ),
                    );
                } else {
                    $response_data = array(
                        'variant' => 'error',
                        'title'   => esc_html__( 'Error', 'affiliatepress-affiliate-marketing' ),
                        'msg'     => esc_html__( 'Activation failed:', 'affiliatepress-affiliate-marketing' ).$activate_result->get_error_message(),
                    );
                }
            } else {
                $response_data = array(
                    'variant' => 'error',
                    'title'   => esc_html__( 'Error', 'affiliatepress-affiliate-marketing' ),
                    'msg'     => esc_html__( 'ARMember plugin is not installed.', 'affiliatepress-affiliate-marketing' ),
                );
            }

            wp_send_json( $response_data );
            die;
        }

        function affiliatepress_get_arprice_func() {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_plugin', true, 'ap_wp_nonce' ); // phpcs:ignore
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_growth_tools')){
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

            if ( ! file_exists( WP_PLUGIN_DIR . '/arprice-responsive-pricing-table/arprice-responsive-pricing-table.php' ) ) {
        
                if ( ! function_exists( 'plugins_api' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                }
                $response = plugins_api(
                    'plugin_information',
                    array(
                        'slug'   => 'arprice-responsive-pricing-table',
                        'fields' => array(
                            'sections' => false,
                            'versions' => true,
                        ),
                    )
                );
                if ( ! is_wp_error( $response ) && property_exists( $response, 'versions' ) ) {
                    if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                    }
                    $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                    $source   = ! empty( $response->download_link ) ? $response->download_link : '';
                    
                    if ( ! empty( $source ) ) {
                        if ( $upgrader->install( $source ) === true ) {
                            activate_plugin( 'arprice-responsive-pricing-table/arprice-responsive-pricing-table.php' );
                            $arp_install_activate = 1; 
                        }
                    }
                } else {
                    $package_data = $this->affiliatepress_pro_force_check_for_plugin_update( ['version', 'dwlurl'], false, 'arprice-responsive-pricing-table' );
                    $package_url = !empty( $package_data['dwlurl'] ) ? $package_data['dwlurl'] : '';
                    if( !empty( $package_url ) ) {
                        if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                        }
                        $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                        if ( ! empty( $package_url ) ) {
                            if ( $upgrader->install( $package_url ) === true ) {
                                activate_plugin( 'arprice-responsive-pricing-table/arprice-responsive-pricing-table.php' );
                                $arm_install_activate = 1;
                            }
                        }
                    }
                }
            }
            if( $arp_install_activate = 1 ){

                $response_data['variant']               = 'success';
                $response_data['title']                 = esc_html__('Success', 'affiliatepress-affiliate-marketing');
                $response_data['msg']                   = esc_html__('ARPrice Successfully installed.', 'affiliatepress-affiliate-marketing');
            } else {

                $response_data['variant']               = 'error';
                $response_data['title']                 = esc_html__('error', 'affiliatepress-affiliate-marketing');
                $response_data['msg']                   = esc_html__('Somthing went wrong please try again later.', 'affiliatepress-affiliate-marketing');
            }
            wp_send_json($response_data);
            die;
        }

        function affiliatepress_activate_arprice_func() {

            global $wpdb, $affiliatepress_tbl_ap_affiliate_commissions, $AffiliatePress;
            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_plugin', true, 'ap_wp_nonce' ); // phpcs:ignore
            $response = array();
            $response['variant'] = 'error';
            $response['title']   = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
            $response['msg']     = esc_html__( 'Something went wrong..', 'affiliatepress-affiliate-marketing');

            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }
 
            if(!current_user_can('affiliatepress_growth_tools')){
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

            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $plugin_file = 'arprice-responsive-pricing-table/arprice-responsive-pricing-table.php';

            if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
                $activate_result = activate_plugin( $plugin_file );

                if ( ! is_wp_error( $activate_result ) ) {
                    $response_data = array(
                        'variant' => 'success',
                        'title'   => esc_html__( 'Success', 'affiliatepress-affiliate-marketing' ),
                        'msg'     => esc_html__( 'ARPrice Successfully Activated.', 'affiliatepress-affiliate-marketing' ),
                    );
                } else {
                    $response_data = array(
                        'variant' => 'error',
                        'title'   => esc_html__( 'Error', 'affiliatepress-affiliate-marketing' ),
                        'msg'     => esc_html__( 'Activation failed:', 'affiliatepress-affiliate-marketing' ).$activate_result->get_error_message(),
                    );
                }
            } else {
                $response_data = array(
                    'variant' => 'error',
                    'title'   => esc_html__( 'Error', 'affiliatepress-affiliate-marketing' ),
                    'msg'     => esc_html__( 'ARPrice plugin is not installed.', 'affiliatepress-affiliate-marketing' ),
                );
            }

            wp_send_json( $response_data );
            die;
        }

        function affiliatepress_pro_force_check_for_plugin_update( $param = [], $force_update = false, $slug = '' ){
            global $wp_version;

            if( empty( $slug ) ){
                return false;
            }

            $arf_api_url = 'https://www.arpluginshop.com';
            $args = array(
                'slug' => $slug,
            );

            if( 'armember-membership' == $slug ){
                $user_agent = 'ARMLITE-WordPress/'. $wp_version.';'.AFFILIATEPRESS_HOME_URL;
            } else if( 'arprice-responsive-pricing-table' == $slug ){
                $user_agent = 'ARPLITE-WordPress/'. $wp_version.';'.AFFILIATEPRESS_HOME_URL;
            } else if( 'arforms-form-builder' == $slug ){
                $user_agent = 'ARFLITE-WordPress/'. $wp_version.';'.AFFILIATEPRESS_HOME_URL;
            } else if( 'bookingpress-appointment-booking' == $slug ){
                $user_agent = 'BKPLITE-WordPress/'. $wp_version.';'.AFFILIATEPRESS_HOME_URL;
            }
        
            $request_string = array(
                'body' => array(
                    'action' => 'lite_plugin_new_version_check',
                    'request' => serialize( $args ),
                    'api-key' => md5( AFFILIATEPRESS_HOME_URL ),
                    'is_update' => $force_update
                ),
                'sslverify' => false,
                'user-agent' => $user_agent
            );
        
            //Start checking for an update
            $raw_response = wp_remote_post( $arf_api_url, $request_string );
        
            if( !is_wp_error( $raw_response ) && ( $raw_response['response']['code'] == 200 ) ){
                $response = @unserialize( $raw_response['body'] );
            }
            
            
            if( isset( $response['access_request'] ) && !empty( $response['access_request'] ) && 'success' == $response['access_request'] ){
                if( isset( $response['access_package'] ) && !empty( $response['access_package'] ) ){
                    $update_package = @unserialize( $response['access_package'] );
                    if( isset( $update_package ) && is_array( $update_package ) && !empty( $update_package ) ){
                        $version = $update_package['version'];
        
                        if( !empty( $param ) ){
                            $response_arr = [];
                            foreach( $param as  $post_key ){
                                $response_arr[ $post_key ] = !empty( $update_package[ $post_key ] ) ? $update_package[ $post_key ] : '';
                            }
        
                            return $response_arr;
                        }
                    }
                }
            }
            return true;
        }
        
    }
}
global $affiliatepress_growth_tools;
$affiliatepress_growth_tools = new affiliatepress_growth_tools();