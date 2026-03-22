<?php

if( !defined( 'ABSPATH' ) ){ exit; }

class arforms_modules{

    function __construct(){
        
        add_action( 'init', array( $this, 'arforms_load_google_recaptcha_module' ) );
        add_action( 'init', array( $this, 'arforms_load_turnstile_module' ) );
        add_action( 'init', array( $this, 'arforms_load_hcaptcha_module' ) );
        add_action( 'init', array( $this, 'arforms_load_paypal_module' ) );

        add_action( 'wp_ajax_arforms_module_action', array( $this, 'arforms_module_action_func') );

    }

    function arforms_is_module_activate( $module_name = '' ){

        if( empty( $module_name ) ){
            return false;
        }

        global $arformsmain;


        return $arformsmain->arforms_get_settings( $module_name, 'arforms_module');
    }

    function arforms_module_action_func(){

        global $arformsmain;

        if ( empty( $_POST['_wpnonce_arflite'] ) || ( isset( $_POST['_wpnonce_arflite'] ) && '' != $_POST['_wpnonce_arflite'] && ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce_arflite'] ), 'arflite_wp_nonce' ) ) ) {
			echo wp_json_encode(
                [
                    'variant' => 'error',
                    'msg' => esc_html__( 'Sorry! Your request can not be processed due to security reason', 'arforms-form-builder' )
                ]
            );
			die;
		}

        if ( ! current_user_can( 'install_plugins' ) ) {
			$status['errorMessage'] = __( 'Sorry, you are not allowed to activate/deactivate additional module on this site.', 'arforms-form-builder' );
			wp_send_json_error( $status );
		}

        $module_name = !empty( $_REQUEST['module'] ) ? sanitize_text_field( $_REQUEST['module'] ) : 0;
        $module_status = !empty( $_REQUEST['status'] ) ? intval( $_REQUEST['status'] ) : 0;

        $single_captcha_arr = [
            'arforms_gcaptcha' => $arformsmain->arforms_get_settings('arforms_gcaptcha', 'arforms_module'),
            'arforms_tcaptcha' => $arformsmain->arforms_get_settings('arforms_tcaptcha', 'arforms_module'),
            'arforms_hcaptcha' => $arformsmain->arforms_get_settings('arforms_hcaptcha', 'arforms_module')
        ];

        $new_status_arr = [];

        if( 0 == $module_status ){
            $arformsmain->arforms_update_settings( $module_name, 1, 'arforms_module' );
            $new_status_arr[ $module_name ] = 1;
            if( array_key_exists( $module_name, $single_captcha_arr ) ){
                foreach( $single_captcha_arr as $module_key => $module_val ){
                    if( $module_key != $module_name ){
                        $arformsmain->arforms_update_settings( $module_key, 0, 'arforms_module' );
                        $new_status_arr[ $module_key ] = 0;
                    }
                }
            }
        } else {
            $arformsmain->arforms_update_settings( $module_name, 0, 'arforms_module' );
            $new_status_arr[ $module_name ] = 0;
        }

        echo wp_json_encode([
            'variant' => 'success',
            'msg' => ( 0 == $module_status ) ? esc_html__( 'Module Activated Successfully', 'arforms-form-builder' ) : esc_html__( 'Module Deactivated Successfully', 'arforms-form-builder' ),
            'module_status' => $new_status_arr
        ]);

        die;

    }

    function arforms_load_google_recaptcha_module(){
        global $arformsmain;
        $arf_is_tcaptcha_enabled = $arformsmain->arforms_get_settings( 'arforms_gcaptcha', 'arforms_module' );

        if( 1 == $arf_is_tcaptcha_enabled ){
            require_once ARFLITE_FORMPATH . '/integrations/Google/Captcha/class.arforms_recaptcha.php';
        }
    }
    
    function arforms_load_turnstile_module(){
        global $arformsmain;
        
        $arf_is_tcaptcha_enabled = $arformsmain->arforms_get_settings( 'arforms_tcaptcha', 'arforms_module' );

        if( 1 == $arf_is_tcaptcha_enabled ){
            require_once ARFLITE_FORMPATH . '/integrations/Turnstile/class.arforms_turnstile.php';
        }
    }
    
    function arforms_load_hcaptcha_module(){
        global $arformsmain;
        
        $arf_is_hcaptcha_enabled = $arformsmain->arforms_get_settings( 'arforms_hcaptcha', 'arforms_module' );

        if( 1 == $arf_is_hcaptcha_enabled ){
            require_once ARFLITE_FORMPATH . '/integrations/Hcaptcha/core/classes/class.arforms-hcaptcha.php';
        }
    }
    
    function arforms_load_paypal_module(){
        global $arformsmain;

        $arf_is_paypal_enable = $arformsmain->arforms_get_settings('arforms_paypal','arforms_module');

        if ( ! function_exists( 'is_plugin_active' ) ) {
            include ABSPATH . '/wp-admin/includes/plugin.php';
        }


        if( 1 == $arf_is_paypal_enable && !is_plugin_active( 'arformspaypal/arformspaypal.php') ){
            require_once ARFLITE_FORMPATH . '/integrations/Payments/PayPal/class.arforms_paypal_payment_gatway.php';
            if( is_plugin_active( 'arforms/arforms.php' ) ){
                require_once FORMPATH . '/integrations/Payments/PayPal/class_arforms_paypal_integration.php';
            }
            
        }
        
    }


}

global $arforms_modules;
$arforms_modules = new arforms_modules();