<?php
if( !defined( 'ABSPATH' ) ) exit;
class arforms_general_settings{

    var $tc_pubkey;
	var $tc_privkey;
	var $tc_msg;
	var $tc_theme;

    function __construct(){

        add_action('wp_ajax_arf_save_setting_data', array($this,'arf_save_setting_data_func') );

        /* Gmail API auth */
        add_action( 'wp',array($this,'arf_add_gmail_api'),10);
        add_action('wp_ajax_arf_gmail_remove_auth', array($this, 'arf_gmail_remove_auth_func'));
        add_action('wp_ajax_arf_send_test_gmail', array($this, 'arf_send_test_gmail'));

        /* SMTP send email notification */
        add_action( 'wp_ajax_arf_send_test_mail', array( $this, 'arf_send_test_mail' ) );

        add_action( 'shutdown', [ $this, 'arf_vadidate_plugin_setup' ] );
    }

    function arf_vadidate_plugin_setup(){

        $arf_plugin_setup_check_time = get_option( 'arforms_validate_plugin_setup_timings' );

        if( empty( $arf_plugin_setup_check_time ) || current_time('timestamp') > $arf_plugin_setup_check_time ){

            $setup_validity_    = 2 * DAY_IN_SECONDS;
                        
            arforms_form_builder::load();

            update_option( 'arforms_validate_plugin_setup_timings', (current_time('timestamp') + $setup_validity_) );

            if (!function_exists('is_plugin_active')) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $arf_validate = get_option( 'arflite_db_version' );
            $arf_pro_validate = get_option( 'arf_db_version' );
            $afvlv = !empty( $arf_validate ) ? 1 : 0;
            $afvpv = !empty( $arf_pro_validate ) ? 1 : 0;

            $arfava_data = [];
            $arfavd_data = [];

            global $arflitesettingcontroller;

            $arfav_resp = $arflitesettingcontroller->arf_fetch_addon_list();

            if ( ! is_wp_error( $arfav_resp ) ) {
                $arfav_data = base64_decode( $arfav_resp['body'] );
                if( !empty( $arfav_data ) ){
                    $arfav_response = json_decode( $arfav_data, true );
                    if( is_array( $arfav_response ) ){
                        $arfav_filtered = array_values( $arfav_response );
                        $arfallav = array_merge( ...$arfav_filtered );

                        if( !empty( $arfallav ) ){
                            foreach( $arfallav as $arfav_details ){
                                $arfav_installer = $arfav_details['plugin_installer'];

                                if( file_exists( WP_PLUGIN_DIR . '/' . $arfav_installer ) ){
                                    $arfavpdata = get_plugin_data( WP_PLUGIN_DIR . '/' . $arfav_installer );
                                    $arfvactv = is_plugin_active( $arfav_installer );
                                    if( $arfvactv ){
                                        $arfava_data[ $arfav_details['full_name'] ] = $arfavpdata['Version'];
                                    } else {
                                        $arfavd_data[ $arfav_details['full_name'] ] = $arfavpdata['Version'];
                                    }
                                }
                            }
                        }
                    }
                }

            }

            $arfav_setup_data = [
                'arflv' => $afvlv,
                'arfpv' => $afvpv.arforms_form_builder::$checksum,
                'arfava' => $arfava_data,
                'arfavd' => $arfavd_data,
                'arfurl' => home_url(),
                //'bplin' => get_option('bookingpress_download_plugin_wizard'),
            ];

            $arf_validation_data = wp_json_encode( $arfav_setup_data );

            $arf_validation_url = 'https://www.arformsplugin.com/arf_misc/validate_plugin_setup.php';
            //$arf_validation_url = 'http://arformswebsite.repute.local/arf_misc/validate_plugin_setup.php';
            $arf_validate_setup_req = wp_remote_post(
                $arf_validation_url,
                [
                    'method'    => 'POST',
                    'timeout'   => 45,
                    'sslverify' => false,
                    'body'      => [
                        'arfvld'  => $arf_validation_data
                    ]
                ]
            );
        }

    }

    function arf_send_test_mail() {
		global $arflitenotifymodel, $arformsmain;

		if ( empty( $_POST['_wpnonce_arflite'] ) || (isset( $_POST['_wpnonce_arflite'] ) && '' != $_POST['_wpnonce_arflite'] && ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce_arflite'] ), 'arflite_wp_nonce' )) ) {
			echo esc_attr( 'security_error' );
			die;
		}

		$reply_to = ( isset( $_POST['reply_to'] ) && ! empty( $_POST['reply_to'] ) ) ? sanitize_email( $_POST['reply_to'] ) : '';
		$send_to  = ( isset( $_POST['send_to'] ) && ! empty( $_POST['send_to'] ) ) ? sanitize_email( $_POST['send_to'] ) : '';

		$subject       = ( isset( $_POST['subject'] ) && ! empty( $_POST['subject'] ) ) ? sanitize_text_field( $_POST['subject'] ) : __( 'SMTP Test E-Mail', 'arforms-form-builder' );
		$message       = ( isset( $_POST['message'] ) && ! empty( $_POST['message'] ) ) ? sanitize_textarea_field( $_POST['message'] ) : '';
		$reply_to_name = ( isset( $_POST['reply_to_name'] ) && ! empty( $_POST['reply_to_name'] ) ) ? sanitize_text_field( $_POST['reply_to_name'] ) : '';

		if ( empty( $send_to ) || empty( $reply_to ) || empty( $message ) || empty( $subject ) ) {
			return;
		}

        if( $arformsmain->arforms_is_pro_active() ){
            global $arnotifymodel;
            $arnotifymodel->send_notification_email_user($send_to, $subject, $message, $reply_to, $reply_to_name, '', array(), true, true, true, true); //phpcs:ignore
        } else {
            $arflitenotifymodel->arflite_send_notification_email_user($send_to, $subject, $message, $reply_to, $reply_to_name, '', array(), true, true, true, true); //phpcs:ignore
        }

		die();
	}

    function arf_send_test_gmail(){
        global $arflitenotifymodel, $arformsmain;
		if ( empty( $_POST['_wpnonce_arfnonce'] ) || ( isset( $_POST['_wpnonce_arfnonce'] ) && '' != $_POST['_wpnonce_arfnonce'] && ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce_arfnonce'] ), 'arflite_wp_nonce' ) ) ) {
            echo esc_attr( 'security_error' );
            die;
		}
        if( ! current_user_can( 'arfchangesettings' ) ){
            echo esc_attr( 'permission_error' );
            die;
        }
        $from_to = (isset($_POST['from_to'])) && !empty($_POST['from_to']) ? sanitize_email($_POST['from_to']) : '';
        $send_to = (isset($_POST['send_to'])) && !empty($_POST['send_to']) ? sanitize_email($_POST['send_to']) : '';
        $subject = (isset($_POST['subject']) && !empty($_POST['subject'])) ? sanitize_text_field($_POST['subject']) : addslashes(esc_html__('GMAIL Test E-Mail', 'arforms-form-builder'));
        $message = (isset($_POST['message']) && !empty($_POST['message'])) ? sanitize_text_field($_POST['message']) : '';
        $reply_to_name = (isset($_POST['reply_to_name']) && !empty($_POST['reply_to_name'])) ? sanitize_text_field($_POST['reply_to_name']) : '';
        if (empty($send_to) || empty($from_to) || empty($message) || empty($subject)) {
            return;
        }
        if( $arformsmain->arforms_is_pro_active() ){
            global $arnotifymodel; 
            echo $arnotifymodel->send_notification_email_user($send_to, $subject, $message, $from_to, $reply_to_name, '', array(), true, true, true, true); //phpcs:ignore
        } else {
            echo $arflitenotifymodel->arflite_send_notification_email_user($send_to, $subject, $message, $from_to, $reply_to_name, '', array(), true, true, true, true); //phpcs:ignore

        }
        die();
    }
    function arf_gmail_remove_auth_func(){
		global $arformsmain;
		if ( empty( $_POST['_wpnonce_arfnonce'] ) || ( isset( $_POST['_wpnonce_arfnonce'] ) && '' != $_POST['_wpnonce_arfnonce'] && ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce_arfnonce'] ), 'arflite_wp_nonce' ) ) ) {
            $response['variant'] = 'error';
            $response['msg']     = esc_html__( 'Sorry, your request cannot be processed due to security reason.', 'arforms-form-builder' );
            echo wp_json_encode( $response );
            die();
		}

        if( ! current_user_can( 'arfchangesettings' ) ){
            $response['variant'] = 'error';
            $response['msg']     = esc_html__( 'Sorry, you do not have permission to perform this action', 'arforms-form-builder' );
            echo wp_json_encode( $response );
            die();
        }

        $auth_token = !empty( $_POST['auth_token'] ) ? sanitize_text_field( $_POST['auth_token'] ) : '' ; //phpcs:ignore WordPress.Security.NonceVerification
        $auth_email = !empty( $_POST['connected_email']) ? sanitize_text_field( $_POST['connected_email']) : ''; //phpcs:ignore WordPress.Security.NonceVerification
        $auth_response = !empty( $_POST['access_token_data']) ? $_POST['access_token_data'] : ''; //phpcs:ignore
        if( !empty( $auth_response)){
			$arformsmain->arforms_update_settings( 'arf_gmail_api_response_data', '', 'general_settings' );
        }
        if( !empty( $auth_token)){
			$arformsmain->arforms_update_settings( 'arf_gmail_api_access_token', '', 'general_settings' );
        }
        if( !empty( $auth_email )){
			$arformsmain->arforms_update_settings( 'arf_gmail_api_connected_email', '', 'general_settings' );
        }
        $response['variant'] = 'success';
        $response['title']   = esc_html__( 'Success', 'arforms-form-builder' );
        $response['msg']     = esc_html__( 'Sign out successfully.', 'arforms-form-builder' );
        echo wp_json_encode( $response );
        die();
    }
    function arf_add_gmail_api(){
		global $arformsmain;
        if( isset( $_GET['page'] ) && 'ARForms-settings' == $_GET['page'] ){
            if( empty( $_GET['state'] ) ){
                echo "<script type='text/javascript' data-cfasync='false'>";
                echo "let url = document.URL;";
                echo "if( /\#state/.test( url ) ){";
                    echo "url = url.replace( /\#state/, '&state' );";
                    echo "window.location.href= url;";
                echo "} else {";
                    echo "window.location.href='" . get_home_url() . "';"; //phpcs:ignore
                echo "}";
                echo "</script>";
            } else {
                global $wpdb, $arformsmain, $arflitemaincontroller;
                $gmail_api_clientID = $arformsmain->arforms_get_settings('gmail_api_clientid','general_settings');
                $gmail_api_client_secret = $arformsmain->arforms_get_settings('gmail_api_clientsecret','general_settings');
                $gmail_api_clientID = !empty( $gmail_api_clientID ) ? $gmail_api_clientID : '';
                $gmail_api_client_secret = !empty( $gmail_api_client_secret ) ? $gmail_api_client_secret : '';
				//$arflitemaincontroller->arfliteafterinstall();
                $state = base64_decode( $_GET['state'] ); //phpcs:ignore
                if( preg_match( '/(gmail_oauth)/', $state ) ){
                    require_once ARFLITE_FORMPATH . '/core/gmail/vendor/autoload.php';
                    $code = !empty( $_GET['code']) ? urldecode( $_GET['code'] ) : ''; //phpcs:ignore
                    $arformslite_client_id =  $gmail_api_clientID;
                    $arformslite_client_secret = $gmail_api_client_secret;
                    $arformslite_redirect_url = get_home_url(). '?page=ARForms-settings';
                    $client = new Google_Client();
                    $client->setClientId($arformslite_client_id);
                    $client->setClientSecret( $arformslite_client_secret );
                    $client->setRedirectUri( $arformslite_redirect_url);
                    $client->setAccessType( 'offline' );
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
                    } catch ( \Exception $e ) {
                        $email = '';
                    }
                        $email = wp_json_encode( $email );
                        $response_data = wp_json_encode( $response_data );
                        $access_token_db = wp_json_encode( $access_token );
                        if( !empty($email)){
							$arformsmain->arforms_update_settings( 'arf_gmail_api_connected_email', $email, 'general_settings' );
                        }
                        if( !empty($response_data)){
							$arformsmain->arforms_update_settings( 'arf_gmail_api_response_data', $response_data, 'general_settings' );
                        }
                        if( !empty($access_token)){
							$arformsmain->arforms_update_settings( 'arf_gmail_api_access_token', $access_token_db, 'general_settings' );
                        }
                    ?>
                    <script>
                        load_function();
                        function load_function() {
                            window.opener.document.getElementById('frm_gmail_api_accesstoken').value = '<?php echo $access_token;  //phpcs:ignore ?>';
                            window.opener.document.getElementById('arflite_google_api_auth_link_remove').style.display = "inline-block";
                            window.opener.document.getElementById('arflite_google_api_auth_link').style.display = "none";
                            window.close();
                        }
                    </script>
                    <?php
                }
            }
            die;
        }
    }

    function arf_save_setting_data_func(){

        global $arformsmain, $tbl_arf_settings;

        $response = array();

        if ( empty( $_POST['_wpnonce_arfnonce'] ) || ( isset( $_POST['_wpnonce_arfnonce'] ) && '' != $_POST['_wpnonce_arfnonce'] && ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce_arfnonce'] ), 'arflite_wp_nonce' ) ) ) {
            $response['variant'] = 'error';
            $response['title'] = esc_html__( 'Error', 'arforms-form-builder');
            $response['msg'] = esc_html__( 'Sorry, your request could not be processed due to security reason.', 'arforms-form-builder' );

            wp_send_json( $response );
            die;
		}

        if( !current_user_can( 'arfchangesettings' ) ){
            $response['variant'] = 'error';
            $response['title'] = esc_html__( 'Error', 'arforms-form-builder');
            $response['msg'] = esc_html__( 'Sorry, you do not have permission to perform this action', 'arforms-form-builder' );;
            wp_send_json( $response );
            die;
        }

        $response['variant'] = 'error';
        $response['title']   = esc_html__('Error', 'arforms-form-builder');
        $response['msg']     = esc_html__('Something Went wrong while updating settings...', 'arforms-form-builder');
        
        $arf_setting_filterd_form = isset( $_POST['setting_form_data'] ) ?  stripslashes_deep( $_POST['setting_form_data'] ) : array(); //phpcs:ignore

		$settings_data = json_decode( $arf_setting_filterd_form, true );
        
        if( !empty( $settings_data )){

            $arforms_default_opts = $arformsmain->arflite_default_options();
            $arforms_default_opts_key = $arformsmain->arflite_default_options_keys();

            if( $arformsmain->arforms_is_pro_active() && class_exists('arforms_pro_settings') && method_exists('arforms_pro_settings', 'arforms_fetch_pro_default_options') ){
                $arforms_pro_default_opts = arforms_pro_settings::arforms_fetch_pro_default_options();
                if( !empty( $arforms_pro_default_opts ) ){
                    $arforms_default_opts = array_merge( $arforms_default_opts, $arforms_pro_default_opts );
                }
            }

            foreach( $arforms_default_opts as $option_name => $option_val ){
    
                if( isset( $settings_data[ $option_name ] ) ){
                    $opt_val = $settings_data[ $option_name ];
                } else if( isset( $settings_data['frm_' . $option_name ] ) ){
                    $opt_val = $settings_data['frm_' . $option_name ];
                } else {
                    $opt_val = $option_val;
                }
                
                if( !in_array( $option_name, $arformsmain->arforms_skip_sanitization_keys() ) ){
                    $opt_val = is_array( $opt_val ) ? array_map( array( $arformsmain, 'arforms_sanitize_values' ), $opt_val ) : $arformsmain->arforms_sanitize_single_value( $opt_val );
                }
        
                if( is_array( $opt_val ) ){
                    $opt_val = wp_json_encode( $opt_val );
                }
        
                $arformsmain->arforms_update_settings( $option_name, $opt_val, 'general_settings' );
                if( !empty( $arforms_default_opts_key[ $option_name ] ) ){
                    $arformsmain->arforms_update_settings( $arforms_default_opts_key[ $option_name ], $opt_val, 'general_settings' );
                }

            }

            $params = $settings_data;

            $opt_data_from_outside = array();
			$opt_data_from_outside = apply_filters('arf_update_global_setting_outside',$opt_data_from_outside,$params);  

			if(is_array($opt_data_from_outside) && !empty($opt_data_from_outside) && count($opt_data_from_outside) > 0) {
				foreach ($opt_data_from_outside as $key => $optdata) {
					$this->$key = $optdata;
				}
			}
            
            if( isset($params['anonymous_data']) && true == $params['anonymous_data']){
                $this->arforms_set_anonymus_data_cron();
            }
            update_option('arforms_current_tab', 'general_settings' );

            
            $response['variant'] = 'success';
            $response['title']   = esc_html__('Success', 'arforms-form-builder');
            $response['msg']     = esc_html__('General setting saved successfully.', 'arforms-form-builder');
        }
        
        wp_cache_delete( 'arforms_all_general_settings' );
        
        echo wp_json_encode($response);
        die;
    }

    function arforms_include_pro_files(  $filename = '', $type = 'view' ){
        global $arformsmain;
        if( $arformsmain->arforms_is_pro_active() && !empty( $filename ) && defined( 'FORMPATH' ) ){

            if( 'view' == $type && file_exists( FORMPATH . '/core/views/' . $filename ) ){
                require_once FORMPATH . '/core/views/' . $filename;
            }
        }
    }

    function arforms_render_pro_settings( $setting_key ){
        global $arformsmain;

        if( class_exists( 'arforms_pro_settings' ) && method_exists( 'arforms_pro_settings', 'arforms_render_pro_settings_ui' ) ){
            arforms_pro_settings::arforms_render_pro_settings_ui( $setting_key );
        }

    }

    /**
     * Set anonymous data cron
     *
     * @return void
     */
    function arforms_set_anonymus_data_cron() {
        global $arflitemaincontroller;
        wp_get_schedules();
        if ( ! wp_next_scheduled('arforms_send_anonymous_data') ) {                
            wp_schedule_event( time(), 'weekly', 'arforms_send_anonymous_data');
        }
    }
}

global $arforms_general_settings;
$arforms_general_settings = new arforms_general_settings();