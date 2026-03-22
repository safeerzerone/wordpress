<?php
if( !defined( 'ABSPATH' ) ) exit;
class arf_divi_builder{
    function __construct(){
        add_action( 'wp_ajax_arforms_divi_preview', array( $this, 'arforms_divi_module_preview' ) );

        add_action( 'divi_extensions_init', array( $this, 'arforms_load_divi_extension' ) );
    }

    function arforms_divi_module_preview(){

        if ( empty( $_POST['_wpnonce_arfnonce'] ) || ( isset( $_POST['_wpnonce_arfnonce'] ) && '' != $_POST['_wpnonce_arfnonce'] && ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce_arfnonce'] ), 'arflite_wp_nonce' ) ) ) {
            echo wp_json_encode(
                array(
                    'data' => esc_html__( 'Sorry, your request could not be processed due to security reason.', 'arforms-form-builder' ),
                    'success' => false
                )
            );
            die;
		}

        if ( ! current_user_can( 'arfviewforms' ) ) {
            echo wp_json_encode(
                array(
                    'data' => esc_html__( 'Sorry, you are not allowed to access this data.', 'arforms-form-builder' ),
                    'success' => false
                )
            );
            die;
        }

        $form_id = !empty($_POST['form_id']) ? intval($_POST['form_id']) : '';

        $params = '';
        $params = ' is_divibuilder="true" ';

        if ( is_plugin_active( 'arforms/arforms.php' ) ) {
            $form_string = do_shortcode( '[ARForms id='.$form_id.' '.$params.' ]' );
        } else {
            $form_string = do_shortcode( '[ARForms id='.$form_id.' '.$params.' ]' );
        }

        wp_send_json_success(
            $form_string
		);
    }

    function arforms_load_divi_extension(){
        require_once ARFLITE_FORMPATH.'/integrations/Divi/class.arforms_divi_extension.php';
    }
}
global $arf_divi_builder;
$arf_divi_builder =new arf_divi_builder();

?>