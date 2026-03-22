<?php 
if( !defined( 'ABSPATH' ) ) exit;
    class arf_growth_plugin{

        public function __construct(){
            add_action('wp_ajax_arf_install_booking_press', array( $this, 'arf_install_booking_press_get_func'));  
            add_action('wp_ajax_arf_armember_install', array( $this, 'arf_armember_install_func'));         
            add_action('wp_ajax_arf_install_arprice', array( $this, 'arf_install_arprice_fun'));         
            add_action('wp_ajax_arf_install_affiliatepress', array( $this, 'arf_install_affiliatepress_func'));
        }

        function arf_pro_force_check_for_plugin_update( $param = [], $force_update = false, $slug = '' ){
            global $wp_version;

            $arforms_plugin_version = '';
            if( file_exists( WP_PLUGIN_DIR . '/arforms-form-builder/arforms-form-builder.php' ) ){
                $arforms_plugin_data         = get_plugin_data( WP_PLUGIN_DIR . '/arforms-form-builder/arforms-form-builder.php' );
                $arforms_plugin_version 	  = $arforms_plugin_data['Version'];
            }

            if( empty( $slug ) ){
                return false;
            }

            $arf_api_url = 'https://www.arpluginshop.com';
            $args = array(
                'slug' => $slug,
            );
        
            $request_string = array(
                'body' => array(
                    'action' => 'lite_plugin_new_version_check',
                    'request' => serialize( $args ),
                    'api-key' => md5( ARFLITE_HOME_URL ),
                    'is_update' => $force_update
                ),
                'sslverify' => false,
                'user-agent' => 'ARFLITE-WordPress/'.$wp_version.';'.ARFLITE_HOME_URL
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
                        //$checked_data->response[$bpa_plugin_slug .'/' . $bpa_plugin_slug .'.php'] = $update_package;
                        $version = $update_package['version'];
        
                        if( !empty( $param ) ){
                            $response_arr = [];
                            foreach( $param as  $post_key ){
                                $response_arr[ $post_key ] = !empty( $update_package[ $post_key ] ) ? $update_package[ $post_key ] : '';
                            }
        
                            return $response_arr;
                        }

                        $current_version = $arforms_plugin_version;
                        
                        if( version_compare( $current_version, $version, '>=') ){
                            delete_option( 'arforms_lite_show_update_failed_notice' );
                            return false;
                        }
                    }
                }
            }
            return true;
        }

        function arf_install_booking_press_get_func() {

            if( isset($_POST['arf_install_booking_press_nonce']) && $_POST['arf_install_booking_press_nonce'] != "" && wp_verify_nonce( $_POST['arf_install_booking_press_nonce'], 'arf_install_booking_press_nonce' ) && current_user_can( 'install_plugins' ) ){ //phpcs:ignore

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
                                $arm_install_activate = 1; 
                            }
                        }
                    } else {
                        $package_data = $this->arf_pro_force_check_for_plugin_update( ['version', 'dwlurl'], false, 'bookingpress-appointment-booking' );

                        $package_url = !empty( $package_data['dwlurl'] ) ? $package_data['dwlurl'] : '';

                        if( !empty( $package_url ) ){							
                            if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                            }
                            $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                            update_option('arforms_lite_download_automatic', 1);
                            if ( ! empty( $package_url ) ) {
                                if ( $upgrader->install( $package_url ) === true ) {
                                    activate_plugin( 'bookingpress-appointment-booking/bookingpress-appointment-booking.php' );
                                    $arm_install_activate = 1;
                                } 
                            }
                        }
                    }
                }
                if( $arm_install_activate = 1 ){
    
                    $response_data['variant']               = 'success';
                    $response_data['title']                 = esc_html__('Success', 'arforms-form-builder');
                    $response_data['msg']                   = esc_html__('BookingPress Successfully installed.', 'arforms-form-builder');
                     $response_data['redirect_url']          = admin_url('admin.php?page=ARForms-Growth-Tools');
                } else {
    
                    $response_data['variant']               = 'error';
                    $response_data['title']                 = esc_html__('error', 'arforms-form-builder');
                    $response_data['msg']                   = esc_html__('Something went wrong please try again later.', 'arforms-form-builder');
                }
                wp_send_json($response_data);
                die;
            }
        }
        
        function arf_armember_install_func() {

            if(isset( $_POST['arf_install_armember_nonce']) && $_POST['arf_install_armember_nonce'] !="" && wp_verify_nonce($_POST['arf_install_armember_nonce'],'arf_install_armember_nonce') && current_user_can( 'install_plugins' ) ){ //phpcs:ignore
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
                        $package_data = $this->arf_pro_force_check_for_plugin_update( ['version', 'dwlurl'], false, 'armember-membership' );

                        $package_url = !empty( $package_data['dwlurl'] ) ? $package_data['dwlurl'] : '';

                        if( !empty( $package_url ) ){							
                            if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                            }
                            $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                            update_option('arforms_lite_download_automatic', 1);
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
                    $response_data['title']                 = esc_html__('Success', 'arforms-form-builder');
                    $response_data['msg']                   = esc_html__('ARMember Successfully installed.', 'arforms-form-builder');
                    $response_data['redirect_url']          = admin_url('admin.php?page=ARForms-Growth-Tools');
                } else {
    
                    $response_data['variant']               = 'error';
                    $response_data['title']                 = esc_html__('error', 'arforms-form-builder');
                    $response_data['msg']                   = esc_html__('Something went wrong please try again later.', 'arforms-form-builder');
                }
                wp_send_json($response_data);
                die;
            }
        }

        function arf_install_arprice_fun(){
            
            if(isset( $_POST['arf_install_arprice_nonce'] ) && sanitize_text_field($_POST['arf_install_arprice_nonce']) !="" && wp_verify_nonce($_POST['arf_install_arprice_nonce'],'arf_install_arprice_nonce') && current_user_can( 'install_plugins' ) ){ //phpcs:ignore

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
                        $package_data = $this->arf_pro_force_check_for_plugin_update( ['version', 'dwlurl'], false, 'arprice-responsive-pricing-table' );

                        $package_url = !empty( $package_data['dwlurl'] ) ? $package_data['dwlurl'] : '';

                        if( !empty( $package_url ) ){							
                            if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                            }
                            $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                            update_option('arforms_lite_download_automatic', 1);
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
                    $response_data['title']                 = esc_html__('Success', 'arforms-form-builder');
                    $response_data['msg']                   = esc_html__('ARPrice Successfully installed.', 'arforms-form-builder');
                    $response_data['redirect_url']          = admin_url('admin.php?page=ARForms-Growth-Tools');
                } else {
    
                    $response_data['variant']               = 'error';
                    $response_data['title']                 = esc_html__('error', 'arforms-form-builder');
                    $response_data['msg']                   = esc_html__('Something went wrong please try again later.', 'arforms-form-builder');
                }
                wp_send_json($response_data);
                die;
            }
        }
        
        function arf_install_affiliatepress_func(){
            if(isset( $_POST['arf_install_affiliatepress_nonce'] ) && sanitize_text_field($_POST['arf_install_affiliatepress_nonce']) !="" && wp_verify_nonce($_POST['arf_install_affiliatepress_nonce'],'arf_install_affiliatepress_nonce') && current_user_can( 'install_plugins' ) ){ //phpcs:ignore
                $affiliatepress_install_activate = 0;
                if ( ! file_exists( WP_PLUGIN_DIR . '/affiliatepress-affiliate-marketing/affiliatepress-affiliate-marketing.php' ) ) {
                    if ( ! function_exists( 'plugins_api' ) ) {
                        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                    }
                    $response = plugins_api(
                        'plugin_information',
                        array(
                            'slug'   => 'affiliatepress-affiliate-marketing',
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
                                activate_plugin( 'affiliatepress-affiliate-marketing/affiliatepress-affiliate-marketing.php' );
                                $affiliatepress_install_activate = 1;
                            }
                        }
                    } else {
                        $package_data = $this->arf_pro_force_check_for_plugin_update( ['version', 'dwlurl'], false, 'affiliatepress-affiliate-marketing' );

                        $package_url = !empty( $package_data['dwlurl'] ) ? $package_data['dwlurl'] : '';

                        if( !empty( $package_url ) ){
                            if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
                                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                            }
                            $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
                            update_option('arforms_lite_download_automatic', 1);
                            if ( ! empty( $package_url ) ) {
                                if ( $upgrader->install( $package_url ) === true ) {
                                    activate_plugin( 'affiliatepress-affiliate-marketing/affiliatepress-affiliate-marketing.php' );
                                    $affiliatepress_install_activate = 1;
                                }
                            }
                        }
                    }
                }
                if( $affiliatepress_install_activate == 1 ){
                    $response_data['variant']               = 'success';
                    $response_data['title']                 = esc_html__('Success', 'arforms-form-builder');
                    $response_data['msg']                   = esc_html__('AffiliatePress Successfully installed.', 'arforms-form-builder');
                    $response_data['redirect_url']          = admin_url('admin.php?page=ARForms-Growth-Tools');
                } else {
                    $response_data['variant']               = 'error';
                    $response_data['title']                 = esc_html__('error', 'arforms-form-builder');
                    $response_data['msg']                   = esc_html__('Something went wrong please try again later.', 'arforms-form-builder');
                }
                wp_send_json($response_data);
                die;
            }
        }
    }
    

?>