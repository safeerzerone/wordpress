<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_addons') ) {
    class affiliatepress_addons Extends AffiliatePress_Core{
        
        function __construct(){

            add_action( 'admin_init', array( $this, 'affiliatepress_addons_vue_data_fields') );

            /* Dynamic Vue Fields */
            add_filter('affiliatepress_addons_dynamic_data_fields',array($this,'affiliatepress_addons_dynamic_data_fields_func'),10,1);

            /** Load wizard view file */
            add_filter('affiliatepress_addons_dynamic_view_load', array( $this, 'affiliatepress_load_growth_tools_view_func'), 10);

            /** Function for wizard vue method */
            add_filter('affiliatepress_addons_dynamic_vue_methods', array( $this, 'affiliatepress_addons_vue_methods_func'),10,1);

            /* Dynamic On Load Method */
            add_filter('affiliatepress_addons_dynamic_on_load_methods', array( $this, 'affiliatepress_addons_dynamic_on_load_methods_func' ), 10,1);

            add_action( 'wp_ajax_affiliatepress_get_remote_addons_list', array( $this, 'affiliatepress_get_remote_addons_list_func') );


        }

         /**
         * Function for affiliates default Vue Data
         *
         * @return void
        */
        function affiliatepress_addons_vue_data_fields(){

            global $AffiliatePress,$affiliatepress_addons_vue_data_fields,$affiliatepress_global_options;   
        }

        /**
         * Function for commission vue data
         *
         * @param  array $affiliatepress_addons_vue_data_fields
         * @return json
         */
        function affiliatepress_addons_dynamic_data_fields_func($affiliatepress_addons_vue_data_fields){            
            
            global $affiliatepress_addons_vue_data_fields;

            $affiliatepress_addons_vue_data_fields = array(
                'ap_lite_addons'  =>array(),
                'is_display_loader' => "1",
            );
            
            $affiliatepress_addons_vue_data_fields = apply_filters('affiliatepress_backend_modify_addon_data_fields', $affiliatepress_addons_vue_data_fields);

            return wp_json_encode($affiliatepress_addons_vue_data_fields);

        }

        /**
         * Load Growth Tools view file
         *
         * @return HTML
        */
        function affiliatepress_load_growth_tools_view_func(){
            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/addons/manage_addon_list.php';
            $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_addons_view_file_path', $affiliatepress_load_file_name);
			require $affiliatepress_load_file_name;
        }

        /**
         * Addon module on load methods
         *
         * @param  string $affiliatepress_addons_dynamic_on_load_methods
         * @return string
         */
        function affiliatepress_addons_dynamic_on_load_methods_func($affiliatepress_addons_dynamic_on_load_methods){
            $affiliatepress_addons_dynamic_on_load_methods.='
                const vm = this;
                vm.affiliatepress_get_remote_addons_list();  
            ';
            return $affiliatepress_addons_dynamic_on_load_methods;
        }        

        /**
         * Function for wizard vue method
         *
         * @param  string $affiliatepress_wizard_vue_methods
         * @return string
        */
        function affiliatepress_addons_vue_methods_func($affiliatepress_addons_vue_methods){

            global $affiliatepress_notification_duration;

            $affiliatepress_addons_vue_methods.= ' 
            affiliatepress_get_remote_addons_list(){
                const vm = this;
                vm.is_display_loader = "1";
                var head = document.getElementsByTagName("head")[0];
                var postData = { action:"affiliatepress_get_remote_addons_list", _wpnonce:"'.esc_html(wp_create_nonce('ap_wp_nonce')).'" };
                axios.post( affiliatepress_ajax_obj.ajax_url, Qs.stringify( postData ) )
                .then( function (response) {
                    vm.ap_lite_addons = response.data.addons_response
                    var addon_css = response.data.css;
                    var head = document.getElementsByTagName("head")[0];
                    var s = document.createElement("style");
                    s.setAttribute("type", "text/css");
                    if (s.styleSheet) {
                        s.styleSheet.cssText = css;
                    } else {
                        s.appendChild(document.createTextNode(addon_css));
                    }
                    head.appendChild(s);
                    vm.is_display_loader = "0";
                }.bind(this) )
                .catch( function (error) {
                    console.log(error);
                });
            },
            ';
            return $affiliatepress_addons_vue_methods;
        }

         /**
         * Get addons list from remote
         *
         * @return void
         */
        function affiliatepress_get_remote_addons_list_func(){
            global $wpdb, $affiliatepress_website_url;
			$response              = array();

            $affiliatepress_ap_check_authorization = $this->affiliatepress_ap_check_authentication( 'retrieve_addon', true, 'ap_wp_nonce' );
            $response = array();
            
            if( preg_match( '/error/', $affiliatepress_ap_check_authorization ) ){
                $affiliatepress_auth_error = explode( '^|^', $affiliatepress_ap_check_authorization );
                $affiliatepress_error_msg = !empty( $affiliatepress_auth_error[1] ) ? $affiliatepress_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'affiliatepress-affiliate-marketing');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'affiliatepress-affiliate-marketing');
                $response['msg'] = $affiliatepress_error_msg;
                wp_send_json( $response );
                die;
            }

            if(!current_user_can('affiliatepress_addons')){
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

            $response['variant']         = 'error';
            $response['title']           = esc_html__( 'Error', 'affiliatepress-affiliate-marketing' );
            $response['msg']             = esc_html__( 'Something went wrong while fetching list of addons', 'affiliatepress-affiliate-marketing' );
            $response['addons_response'] = '';
            $response['css'] = '';

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

            if ( ! is_wp_error( $affiliatepress_addons_res ) ) {
                $affiliatepress_body_res = base64_decode( $affiliatepress_addons_res['body'] );

                if ( ! empty( $affiliatepress_body_res ) ) {
                    $affiliatepress_body_res = json_decode( $affiliatepress_body_res, true );                        
                    $affiliatepress_addon_list_css = '';

                    foreach ( $affiliatepress_body_res as $affiliatepress_body_key => $affiliatepress_body_data_arr ) {

                        foreach ( $affiliatepress_body_data_arr as $affiliatepress_body_data_key => $affiliatepress_body_val ) {

                            if ( is_plugin_active( $affiliatepress_body_val['addon_installer'] ) ) {
                                $affiliatepress_body_res[$affiliatepress_body_key][$affiliatepress_body_data_key]['addon_isactive'] = 1;
                                $affiliatepress_body_data_arr[$affiliatepress_body_data_key]['addon_isactive'] = 1;
                            } else {
                                if ( ! file_exists( WP_PLUGIN_DIR . '/' . $affiliatepress_body_val['addon_installer'] ) ) {
                                    $affiliatepress_body_res[$affiliatepress_body_key][$affiliatepress_body_data_key]['addon_isactive'] = 2;
                                    $affiliatepress_body_data_arr[$affiliatepress_body_data_key]['addon_isactive'] = 2;
                                }
                            }
                            $affiliatepress_horizontal_postion = isset($affiliatepress_body_val['addon_icon_horizontal_position'])  ? $affiliatepress_body_val['addon_icon_horizontal_position'] : 0;
                            $addon_icon_vertical_position = isset($affiliatepress_body_val['addon_icon_vertical_position'])  ? $affiliatepress_body_val['addon_icon_vertical_position'] : 0;
                            $addon_icon_slug = isset($affiliatepress_body_val['addon_icon_slug'])  ? $affiliatepress_body_val['addon_icon_slug'] : '';
                            $addon_icon_background = isset($affiliatepress_body_val['addon_icon_background'])  ? $affiliatepress_body_val['addon_icon_background'] : '';

                        }
                        $affiliatepress_body_res[$affiliatepress_body_key] = $affiliatepress_body_data_arr;
                    }

                    $affiliatepress_body_res = apply_filters( 'affiliatepress_addon_list_data_filter', $affiliatepress_body_res );
                    $affiliatepress_addon_list_css = "";

                    $response['variant']         = 'success';
                    $response['title']           = esc_html__( 'Success', 'affiliatepress-affiliate-marketing' );
                    $response['msg']             = esc_html__( 'Addons list fetched successfully', 'affiliatepress-affiliate-marketing' );
                    $response['addons_response'] = $affiliatepress_body_res;
                    $response['css']             = $affiliatepress_addon_list_css; 
                }
            } else {
                $response['msg'] = $affiliatepress_addons_res->get_error_message();
            }
            echo wp_json_encode( $response );
            die;
        }
        
    }
}
global $affiliatepress_addons;
$affiliatepress_addons = new affiliatepress_addons();