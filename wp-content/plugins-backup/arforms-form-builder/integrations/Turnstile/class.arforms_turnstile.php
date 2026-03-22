<?php

if( !defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'ARF_TC_SLUG' ) ) {
	define( 'ARF_TC_SLUG', 'tccaptcha' );
}

if( ! defined( 'ARF_TC_JS' ) ){
    define( 'ARF_TC_JS', ARFLITEURL . '/integrations/Turnstile/js' );
}

if( ! defined( 'ARF_TC_CSS' ) ){
    define( 'ARF_TC_CSS', ARFLITEURL . '/integrations/Turnstile/css' );
}

if( ! defined( 'ARF_TURNSTL_CAPT_URL' ) ){
	define( 'ARF_TURNSTL_CAPT_URL', ARFLITEURL .'/integrations/Turnstile' );
}

if( !defined( 'ARF_TC_CORE' ) ){
	define( 'ARF_TC_CORE', ARFLITE_FORMPATH . '/integrations/Turnstile/core' );
}
global $arformsmain, $arflitesettings;
$arforms_all_settings = $arformsmain->arforms_global_option_data();
$arflitesettings = json_decode( wp_json_encode( $arforms_all_settings['general_settings'] ) );

class arforms_turnstile{

    function __construct(){

		$arformsmain;

        add_action( 'include_outside_js_css_for_preview_header', array( $this, 'arf_set_js_outside_preview_header' ), 11 );
        add_action( 'arflite_include_outside_js_css_for_preview_header', array( $this, 'arf_set_js_outside_preview_header' ), 11 );

        add_filter( 'arf_display_field_name_box', array( $this, 'arf_hide_field_name_text' ), 10, 2 );
        add_filter( 'arflite_display_field_name_box', array( $this, 'arf_hide_field_name_text' ), 10, 2 );

        add_filter( 'arf_field_type_label_filter', array( $this, 'arf_field_label_for_options' ) );
        add_filter( 'arflite_field_type_label_filter', array( $this, 'arf_field_label_for_options' ) );

        add_filter( 'arf_wrap_input_field', array( $this, 'arf_wrap_tccaptcha_field_from_outside' ), 11, 2 );
        add_filter( 'arflite_wrap_input_field', array( $this, 'arf_wrap_tccaptcha_field_from_outside' ), 11, 2 );

        add_filter( 'arfaavailablefields', array( $this, 'arf_add_tccaptcha_field_in_list' ) );
        add_filter( 'arfliteaavailablefields', array( $this, 'arf_add_tccaptcha_field_in_list' ) );

        add_filter( 'arf_manage_field_element_order_outside', array( $this, 'arf_add_tccaptcha_field_in_order' ) );
        add_filter( 'arflite_manage_field_element_order_outside', array( $this, 'arf_add_tccaptcha_field_in_order' ) );

        add_filter( 'arform_input_fields', array( $this, 'arf_add_tccaptcha_field' ) );
        add_filter( 'arformlite_input_fields', array( $this, 'arf_add_tccaptcha_field' ) );

        add_filter( 'arf_positioned_field_options_icon', array( $this, 'arf_positioned_field_options_icon_tccaptcha' ), 10, 2 );
        add_filter( 'arflite_positioned_field_options_icon', array( $this, 'arf_positioned_field_options_icon_tccaptcha' ), 10, 2 );

        add_action( 'arf_add_fieldiconbox_class_outside', array( $this, 'arf_add_fieldiconbox_class_for_tccaptcha' ) );

        add_filter( 'arf_disply_required_field_outside', array( $this, 'arf_disply_required_field_outside_func' ), 10, 2 );
        add_filter( 'arflite_disply_required_field_outside', array( $this, 'arf_disply_required_field_outside_func' ), 10, 2 );

        add_filter( 'arf_display_duplicate_field_outside', array( $this, 'arf_display_duplicate_field_outside_func' ), 10, 2 );

        add_filter( 'arfavailablefieldsbasicoptions', array( $this, 'add_availablefieldsbasicoptions' ), 10, 3 );
        add_filter( 'arfliteavailablefieldsbasicoptions', array( $this, 'add_availablefieldsbasicoptions' ), 10, 3 );

        add_filter( 'arf_change_json_default_data_ouside', array( $this, 'arf_new_field_json_filter_outside' ), 10, 1 );
        add_filter( 'arflite_change_json_default_data_ouside', array( $this, 'arf_new_field_json_filter_outside' ), 10, 1 );

        add_filter( 'arf_new_field_array_filter_outside', array( $this, 'arf_new_field_array_filter_outside' ), 10, 4 );
        add_filter( 'arflite_new_field_array_filter_outside', array( $this, 'arf_new_field_array_filter_outside' ), 10, 4 );

        add_filter( 'arf_new_field_array_materialize_filter_outside', array( $this, 'arf_new_field_array_filter_outside_materialize' ), 10, 4 );
        add_filter( 'arflite_new_field_array_materialize_filter_outside', array( $this, 'arf_new_field_array_filter_outside_materialize' ), 10, 4 );

        add_filter( 'arf_new_field_array_materialize_outlined_filter_outside', array( $this, 'arf_new_field_array_filter_outside_materialize' ), 10, 4 );

        add_filter( 'arf_form_fields', array( $this, 'add_field_to_frontend' ), 11, 10 );
        add_filter( 'arflite_form_fields', array( $this, 'add_field_to_frontend' ), 11, 10 );

        add_action( 'arfdisplayaddedfields', array( $this, 'arf_display_tccaptcha_in_editor' ), 11 );
        add_action( 'arflitedisplayaddedfields', array( $this, 'arf_display_tccaptcha_in_editor' ), 11 );

        add_action( 'wp_footer', array( $this, 'tc_responsive' ) );
        add_action( 'wp_footer', array( $this, 'arflite_tc_responsive' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'arf_tc_set_js' ), 8 );
        add_action( 'admin_enqueue_scripts', array( $this, 'arf_tc_set_css' ), 10 );

        add_filter( 'arf_update_global_setting_outside', array( $this, 'arf_update_tccaptcha_global_option' ), 13, 2 );
        add_filter( 'arflite_update_global_setting_outside', array( $this, 'arf_update_tccaptcha_global_option' ), 13, 2 );

        add_action( 'arforms_load_captcha_settings', array( $this, 'add_turnstile_setting_outside' ) );

        add_filter( 'arf_validate_form_outside_errors', array( $this, 'arf_form_validation' ), 10, 4 );
        add_filter( 'arflite_validate_form_outside_errors', array( $this, 'arf_form_validation' ), 10, 4 );

        add_filter( 'arf_is_validateform_outside', array( $this, 'arforms_validate_form_outside_for_turnstile' ), 10, 2 );
        add_filter( 'arflite_is_validateform_outside', array( $this, 'arforms_validate_form_outside_for_turnstile' ), 10, 2 );

        add_filter( 'arf_additional_form_content_outside', array( $this, 'arf_add_captcha_inputs' ), 10, 5 );
        add_filter( 'arflite_additional_form_content_outside', array( $this, 'arf_add_captcha_inputs' ), 10, 5 );

        add_action( 'arf_disply_multicolumn_fieldolumn_field_outside', array( $this, 'arf_hide_multicolumn_for_turnstilefield' ), 10, 2 );
        add_action( 'arflite_disply_multicolumn_fieldolumn_field_outside', array( $this, 'arf_hide_multicolumn_for_turnstilefield' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'arforms_load_turnstile_inline_style'), 11  );

		add_filter( 'arforms_remove_captcha_fields_for_validation', array( $this, 'arforms_remove_turnstile_captcha_field'), 10, 2 );

    }

	function arforms_remove_turnstile_captcha_field( $fields, $form_id ){

		global $wpdb, $tbl_arf_fields, $arfliteformcontroller;

		$is_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $tbl_arf_fields . " WHERE form_id=%d AND type=%s", $form_id, ARF_TC_SLUG ) );//phpcs:ignore
		if ( $is_exists > 0 ) {
			$getKey = $arfliteformcontroller->arfliteSearchArray( 'tccaptcha', 'type', json_decode( wp_json_encode( $fields ), true )  );

			if( "" !== $getKey ){
				unset( $fields[ $getKey ] );
				array_values( $fields );
			}
		}

		return $fields;
	}

	function arforms_load_turnstile_inline_style(){

		$arf_turnstile_style = '.edit_field_type_tccaptcha .arf_fieldiconbox{ width: 84px; } .edit_field_type_tccaptcha .arf_fieldiconbox .arf_field_option_icon:first-child{ display:none; }';

		if( $this->is_arforms_support() ){
			wp_add_inline_style( 'arfdisplaycss_editor', $arf_turnstile_style );
		} else{
			wp_add_inline_style( 'arflitedisplaycss_editor', $arf_turnstile_style );
		}

	}

    function arf_hide_multicolumn_for_turnstilefield( $display_multicolumn, $field ) {
		if ( isset( $field['type'] ) && ARF_TC_SLUG == $field['type'] ) {
			$display_multicolumn = false;
		}

		return $display_multicolumn;
	}
	function arf_add_tccaptcha_field( $ordered_list ) {
		array_push( $ordered_list, ARF_TC_SLUG );
		return $ordered_list;
	}
	function arf_wrap_tccaptcha_field_from_outside( $wrap, $field_type ) {

		if ( ARF_TC_SLUG == $field_type ) {
			return false;
		}

		return $wrap;
	}
	function arf_display_duplicate_field_outside_func( $arf_duplicate_field, $field ) {

		if ( isset( $field['type'] ) && ARF_TC_SLUG == $field['type'] ) {

			$arf_duplicate_field = false;
		}

		return $arf_duplicate_field;
	}
	function arf_disply_required_field_outside_func( $arf_disply_required_field, $field ) {

		if ( isset( $field['type'] ) && ARF_TC_SLUG == $field['type'] ) {

			$arf_disply_required_field = false;
		}

		return $arf_disply_required_field;
	}
	function arf_field_label_for_options() {
		$field_label_arr[ARF_TC_SLUG] = esc_html__( 'Tccaptcha', 'arforms-form-builder' );

		return $field_label_arr;
	}
	function arf_hide_field_name_text( $display_name_box, $field ) {

		if ( isset( $field['type'] ) && ARF_TC_SLUG == $field['type'] ) {
			$display_name_box = false;
		}

		return $display_name_box;
	}
	function arforms_validate_form_outside_for_turnstile( $flag, $form ) {
		global $wpdb, $arformsmain, $tbl_arf_fields;

		$form_submit_type = $arformsmain->arforms_get_settings( 'form_submit_type', 'general_settings' );

		$form_id = $form->id;
		if ( 1 == $form_submit_type ) {
			return $flag;
		}

		$is_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $tbl_arf_fields . " WHERE form_id=%d AND type=%s", $form_id, ARF_TC_SLUG ) );//phpcs:ignore
		if ( $is_exists > 0 ) {
			return true;
		}

		return $flag;
	}
	function arf_update_tccaptcha_global_option( $opt_data_from_outside, $params ) {
		global $arformsmain;
		$tc_captch_keys = array( 'tc_pubkey', 'tc_privkey', 'tc_msg', 'tc_theme' );

		foreach ( $tc_captch_keys as $opt_key ) {
			$opt_value = ! empty( $params[ $opt_key ] ) ? $params[ $opt_key ] : array();
			$arformsmain->arforms_update_settings( $opt_key, $opt_value, 'general_settings' );
			$opt_data_from_outside[ $opt_key ] = $opt_value;
		}

		return $opt_data_from_outside;
	}
	function arf_tc_set_js() {
		global $arfforms_loaded, $arflitesettings, $arflite_db_version, $arf_form_all_footer_js;
		wp_register_script( 'tc-muliple_captcha', ARF_TC_JS . '/arf_duplicate_field_restriction.js', array(), $arflite_db_version );
		if ( isset( $_REQUEST['page'] ) && '' != $_REQUEST['page'] && 'ARForms' == $_REQUEST['page'] && isset( $_REQUEST['arfaction'] ) && in_array( $_REQUEST['arfaction'], array( 'new', 'edit', 'duplicate', 'update' ) ) ) {
			wp_enqueue_script( 'tc-muliple_captcha' );
		}
	}
	function arf_tc_set_css( $hook ) {
		if ( isset( $_REQUEST['page'] ) && '' != $_REQUEST['page'] && ( 'ARForms' == $_REQUEST['page'] && isset( $_REQUEST['arfaction'] ) && in_array( $_REQUEST['arfaction'], array( 'new', 'edit', 'duplicate', 'update' ) ) ) ) {
			wp_register_style( 'tc-captcha-multiple', ARF_TURNSTL_CAPT_URL . '/css/tc_captcha_style.css', array() );
			wp_enqueue_style( 'tc-captcha-multiple' );
		}
	}
	function arf_add_tccaptcha_field_in_order( $fields ) {
		array_splice( $fields, 12, 0, ARF_TC_SLUG );

		return $fields;
	}
	function arf_tccaptcha_admin_notices() {
		global $wp_version;
		if ( version_compare( $wp_version, '4.5.0', '<' ) ) {
			echo "<div class='error arf_error'><p>Please meet the minimum requirement of WordPress version 4.5 to activate ARForms - Turnstile Captcha Add-on</p></div>";
		}
		
		if ( $this->is_arforms_support() && version_compare( $this->get_arforms_version(), '1.6.3', '<=' ) ) { 
			echo "<div class='updated'><p>" . esc_html__( 'Turnstile captcha add-on for ARForms requires ARForms installed with version 6.4 or higher.', 'arforms-form-builder' ) . '</p></div>';
		}  
	}
	function is_arforms_support() {
		if ( file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'arforms/arforms.php' );
	}  
	function get_arforms_version() {

		$arf_db_version = get_option( 'arf_db_version' );

		return ( isset( $arf_db_version ) ) ? $arf_db_version : 0;
	} 
	function arf_add_tccaptcha_field_in_list( $fields ) {
		$restrict_cls = '';
		global $wpdb, $arf_tc_captcha, $tbl_arf_fields;

		if ( ! empty( $_REQUEST['arfaction'] ) && ( 'edit' == $_REQUEST['arfaction'] || 'duplicate' == $_REQUEST['arfaction'] ) && ! empty( $_REQUEST['id'] ) ) {
			$form_id = intval( $_REQUEST['id'] );

			$is_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tbl_arf_fields} WHERE form_id = %d AND type = %s", $form_id, ARF_TC_SLUG ) );//phpcs:ignore
			if ( $is_exists > 0 ) {
				$restrict_cls = 'arf_restricted_form_fields';
			}
		}
		$fields[ ARF_TC_SLUG ] = array(
			'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.0808 14.0833L21.8671 13.3372L21.2932 13.1077L6.54443 13.2224V20.3386L25.0808 20.396V14.0833Z" fill="white"/><path d="M19.0552 19.7072C19.1475 19.4628 19.1801 19.1998 19.1501 18.9403C19.1202 18.6807 19.0286 18.4321 18.883 18.2151C18.7335 18.0358 18.5501 17.8878 18.3433 17.7795C18.1365 17.6712 17.9104 17.6047 17.6779 17.5838L7.69234 17.4691C7.63495 17.4691 7.57756 17.4117 7.52017 17.4117C7.50681 17.4017 7.49596 17.3887 7.48849 17.3737C7.48102 17.3588 7.47713 17.3423 7.47713 17.3256C7.47713 17.3089 7.48102 17.2924 7.48849 17.2775C7.49596 17.2625 7.50681 17.2495 7.52017 17.2395C7.57756 17.1247 7.63495 17.0673 7.74973 17.0673L17.7927 16.9526C18.4302 16.8818 19.0375 16.6425 19.5519 16.2592C20.0663 15.8759 20.4693 15.3625 20.7195 14.7718L21.2933 13.2797C21.2933 13.2223 21.3507 13.1649 21.2933 13.1076C20.9813 11.7179 20.2242 10.468 19.137 9.54788C18.0498 8.62778 16.692 8.08773 15.2698 8.0098C13.8476 7.93187 12.4389 8.32033 11.2577 9.11614C10.0765 9.91195 9.1873 11.0716 8.72533 12.4189C8.1256 11.9906 7.39407 11.7874 6.65935 11.845C5.98508 11.9202 5.35644 12.2225 4.8767 12.7022C4.39697 13.182 4.09468 13.8106 4.01949 14.4849C3.98128 14.83 4.00067 15.1791 4.07688 15.5179C2.9859 15.548 1.94973 16.0026 1.18878 16.785C0.427836 17.5674 0.00216618 18.6158 0.00231839 19.7072C-0.00709937 19.9193 0.0122041 20.1316 0.0597057 20.3385C0.062341 20.3833 0.0813268 20.4256 0.113061 20.4573C0.144794 20.489 0.187071 20.508 0.231872 20.5106H18.6535C18.7683 20.5106 18.883 20.4533 18.883 20.3385L19.0552 19.7072Z" fill="#F48120"/><path d="M22.2117 13.2798H21.9247C21.8674 13.2798 21.81 13.3372 21.7526 13.3946L21.3509 14.7719C21.2585 15.0163 21.226 15.2793 21.2559 15.5388C21.2859 15.7984 21.3775 16.047 21.523 16.264C21.6726 16.4432 21.856 16.5913 22.0628 16.6996C22.2695 16.8079 22.4957 16.8744 22.7282 16.8952L24.8515 17.01C24.9089 17.01 24.9663 17.0674 25.0237 17.0674C25.0371 17.0774 25.0479 17.0904 25.0554 17.1054C25.0629 17.1203 25.0668 17.1368 25.0668 17.1535C25.0668 17.1702 25.0629 17.1867 25.0554 17.2016C25.0479 17.2166 25.0371 17.2295 25.0237 17.2396C24.9663 17.3543 24.9089 17.4117 24.7942 17.4117L22.6134 17.5265C21.9758 17.5973 21.3686 17.8366 20.8542 18.2199C20.3398 18.6032 19.9368 19.1166 19.6866 19.7073L19.5718 20.2238C19.5144 20.2811 19.5718 20.3959 19.6866 20.3959H27.2619C27.2852 20.3993 27.3091 20.3971 27.3315 20.3896C27.3539 20.3822 27.3743 20.3696 27.391 20.3529C27.4077 20.3362 27.4203 20.3158 27.4277 20.2934C27.4352 20.271 27.4374 20.2471 27.434 20.2238C27.5706 19.7375 27.6477 19.2365 27.6636 18.7317C27.6545 17.2885 27.0772 15.9071 26.0567 14.8867C25.0362 13.8662 23.6548 13.2889 22.2117 13.2798Z" fill="#FAAD3F"/></svg>',
			'label' => addslashes( esc_html__( 'Turnstile Captcha', 'arforms-form-builder' ) ),
			'class' => $restrict_cls,
		);

		return $fields;
	}
	function arf_positioned_field_options_icon_tccaptcha( $positioned_field_icons, $field_icons ) {
		$positioned_field_icons[ ARF_TC_SLUG ] = "{$field_icons['field_delete_icon']}" . str_replace( '{arf_field_type}', ARF_TC_SLUG, $field_icons['field_option_icon'] ) . "{$field_icons['arf_field_move_icon']}";
		return $positioned_field_icons;
	}
	function arf_form_validation( $arf_errors, $form_id, $values, $fields ) {

		global $arflitesettings, $fields, $wpdb, $field_tc, $tbl_arf_fields, $arformsmain;

		$arforms_all_settings = $arformsmain->arforms_global_option_data();
		$arflitesettings = json_decode( json_encode( $arforms_all_settings['general_settings'] ) );

		$results = $wpdb->get_row($wpdb->prepare("SELECT field_options, type FROM " . $tbl_arf_fields . " WHERE form_id = %d and type = %s " , $form_id, ARF_TC_SLUG ));//phpcs:ignore
		$field_options = isset( $results->field_options ) ? json_decode( $results->field_options, true ) : array();

		if ( empty( $results ) ) {
			return $arf_errors;
		}
		$captcha_failed_msg = ! empty( $arflitesettings->tc_msg ) ? $arflitesettings->tc_msg : esc_html__( 'Invalid Turnstile Captcha. Please try again', 'arforms-form-builder' );
		$privkey = $arflitesettings->tc_privkey;
		$pubkey = $arflitesettings->tc_pubkey;
		$tc_valid = $values['cf-turnstile-response'];
		$captcha_field = ! empty( $tc_valid ) ? $tc_valid : '';

		if ( empty( $captcha_field ) ) {
			$arf_errors['arf_message_error'] = $field_options['blank'];
			return $arf_errors;
		} else {
			$verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
			$args = array(
				'timeout' => 4500,
				'body' => 'secret=' . $privkey . '&response=' . $captcha_field,
			);

			$resp = wp_remote_post( $verify_url, $args );

			if ( is_wp_error( $resp ) ) {
				$arf_errors['arf_message_error'] = $resp->get_error_message();
			}

			$resp_body = json_decode( $resp['body'] );
			if ( empty( $resp_body->success ) || 1 != $resp_body->success ) {
				$arf_errors['arf_message_error'] = $captcha_failed_msg;
			}
		}

		return $arf_errors;
	}
	function arf_new_field_json_filter_outside( $field_json ) {

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;

		$field_data               = $wp_filesystem->get_contents( ARF_TC_CORE . '/arf_turnstile_captcha_field_data.json' );
		$field_data_array         = json_decode( $field_data, true );
		$field_data_obj_tccaptcha = $field_data_array['field_data'][ARF_TC_SLUG];

		$field_json['field_data'][ARF_TC_SLUG] = $field_data_obj_tccaptcha;
		return $field_json;
	}
	function arf_add_fieldiconbox_class_for_tccaptcha( $field ) {
		if ( ARF_TC_SLUG == $field['type'] ) {
			echo 'arf_tccaptcha_fieldiconbox';
		}
	}
	function arf_new_field_array_filter_outside( $field_options_new, $field_icons, $filed_data_array, $positioned_field_icons ) {

		global $arfieldcontroller, $arformsmain;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;

		$field_data               = $wp_filesystem->get_contents( ARF_TC_CORE . '/arf_turnstile_captcha_field_data.json' );
		$field_data_array         = json_decode( $field_data );
		$arf_editor_unique_id     = 5656;
		$field_data_obj_tccaptcha = $field_data_array->field_data->tccaptcha;
		$field_opt_arr            = array(
			ARF_TC_SLUG => array( 
				'requiredmsg'      => 2,
				'fielddescription' => 3,
			),
		);
		$field_order_tccaptcha   = isset( $field_opt_arr[ ARF_TC_SLUG ] ) ? $field_opt_arr[ ARF_TC_SLUG ] : '';
		$onclick_func = $arformsmain->arforms_is_pro_active() ? 'arf_close_field_option_popup()' : 'arflite_close_field_option_popup()';
		$tccaptcha_html = array(
						ARF_TC_SLUG => "
						<div class='arf_inner_wrapper_sortable arfmainformfield edit_form_item arffieldbox ui-state-default 1  arf1columns single_column_wrapper' data-id='arf_editor_main_row_{arf_editor_index_row}'>						
							<div class='sortable_inner_wrapper edit_field_type_tccaptcha' id='arfmainfieldid_{arf_field_id}' inner_class='arf_1col'>
								<div id='arf_field_{arf_field_id}' class='arfformfield control-group arfmainformfield top_container arf_field_{arf_field_id}'>
									<div class='fieldname-row arf_dig_display_block'>
										<div class='fieldname'></div>
									</div>
									<div class='arf_fieldiconbox arf_tccaptcha_fieldiconbox' data-field_id='{arf_field_id}' data-field_id='{arf_field_id}'>". $positioned_field_icons[ARF_TC_SLUG]."</div>
									<div class='controls'>
										<img class='arf_tccaptcha_editor_img_default' src='" . ARF_TURNSTL_CAPT_URL . "/images/turnstile_captcha.png'/>							
										<input id='field_{arf_unique_key}_" . $arf_editor_unique_id . "' name='item_meta[{arf_field_id}]' type='hidden' class='arf_tccaptcha_output arf_tccaptcha_float_left'/>
										<div class='arf_field_description' id='field_description_{arf_field_id}'></div>
										<div class='help-block'></div>
									</div>
								<input type='hidden' name='arf_field_data_{arf_field_id}' id='arf_field_data_{arf_field_id}' value='" . htmlspecialchars( wp_json_encode( $field_data_obj_tccaptcha ) ) . "' data-field_options='" . wp_json_encode( $field_order_tccaptcha ) . "' />
								<div class='arf_field_option_model arf_field_option_model_cloned' data-field_id='{arf_field_id}'>
									<div class='arf_field_option_model_header'>" . esc_html__( 'Field Options', 'arforms-form-builder' ) . "</div>
									<div class='arf_field_option_model_container'>
										<div class='arf_field_option_content_row'></div>
									</div>
									<div class='arf_field_option_model_footer'>
										<button type='button' class='arf_field_option_close_button' onClick='".$onclick_func."'>" . esc_html__( 'Cancel', 'arforms-form-builder' ) . "</button>
										<button type='button' class='arf_field_option_submit_button' data-field_id='{arf_field_id}'>" . esc_html__( 'OK', 'arforms-form-builder' ) . '</button>
									</div>
								</div>
							</div>
						</div>',
					);
		return array_merge( $field_options_new, $tccaptcha_html );
	}
	function arf_new_field_array_filter_outside_materialize( $field_options_new, $field_icons, $filed_data_array, $positioned_field_icons ) {

		global $arfieldcontroller, $arflitesettings, $arf_tc_captcha, $arformsmain;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;

		$field_data               = $wp_filesystem->get_contents( ARF_TC_CORE . '/arf_turnstile_captcha_field_data.json' );
		$field_data_array         = json_decode( $field_data );
		$arf_editor_unique_id     = 5656;
		$field_data_obj_tccaptcha = $field_data_array->field_data->tccaptcha;
		$field_opt_arr            = array(
			ARF_TC_SLUG => array(
				'requiredmsg'      => 1,
				'fielddescription' => 2,
			),
		);

		$tc_pubkey = isset( $arflitesettings->tc_pubkey  ) ? $arflitesettings->tc_pubkey  : ''; 
		$field_order_tccaptcha   = isset( $field_opt_arr[ ARF_TC_SLUG ] ) ? $field_opt_arr[ ARF_TC_SLUG ] : ''; 
		$onclick_func = $arformsmain->arforms_is_pro_active() ? 'arf_close_field_option_popup()' : 'arflite_close_field_option_popup()';
		$tccaptcha_html           = array(
			ARF_TC_SLUG => "<div class='arf_inner_wrapper_sortable arfmainformfield edit_form_item arffieldbox ui-state-default 1  arf1columns single_column_wrapper' data-id='arf_editor_main_row_{arf_editor_index_row}'>								
								<div class='sortable_inner_wrapper edit_field_type_tccaptcha' inner_class='arf_1col' id='arfmainfieldid_{arf_field_id}'>
									<div id='arf_field_{arf_field_id}' class='arfformfield control-group arfmainformfield top_container  arfformfield  arf_field_{arf_field_id}'>										
										<div class='fieldname-row arf_dig_display_block' >
											<div class='fieldname'></div>
										</div>
										<div class='arf_fieldiconbox arf_tccaptcha_fieldiconbox' data-field_id='{arf_field_id}'>" . $positioned_field_icons[ ARF_TC_SLUG ] . "</div>
										<div class='controls input-field'>
											<img class='arf_tccaptcha_editor_img_default' src='" . ARF_TURNSTL_CAPT_URL . "/images/new_turnstile_captcha.png'>
											<input type='hidden' id='arf_tccaptcha_public_key' value='" . $tc_pubkey . " '/>
											<input id='field_{arf_unique_key}_" . $arf_editor_unique_id . "' name='item_meta[{arf_field_id}]' type='hidden' class='arf_tccaptcha_output arf_tccaptcha_float_left'>
											<div class='arf_field_description' id='field_description_{arf_field_id}'></div>
											<div class='help-block'></div>
											<input type='hidden' name='arf_field_data_{arf_field_id}' id='arf_field_data_{arf_field_id}' value='" . htmlspecialchars( wp_json_encode( $field_data_obj_tccaptcha ) ) . "' data-field_options='" . wp_json_encode( $field_order_tccaptcha ) . "'/>
											<div class='arf_field_option_model arf_field_option_model_cloned' data-field_id='{arf_field_id}'>
												<div class='arf_field_option_model_header'>" . esc_html__( 'Field Options', 'arforms-form-builder' ) . "</div>
												<div class='arf_field_option_model_container'>
													<div class='arf_field_option_content_row'></div>
												</div>
												<div class='arf_field_option_model_footer'>
													<button type='button' class='arf_field_option_close_button' onClick='".$onclick_func."'>" . esc_html__( 'Cancel', 'arforms-form-builder' ) . "</button>
													<button type='button' class='arf_field_option_submit_button' data-field_id='{arf_field_id}'>" . esc_html__( 'OK', 'arforms-form-builder' ) . '</button>
												</div>
											</div>
										</div>
									</div>
								</div>',
		);
		$field_options_new = array_merge( $field_options_new, $tccaptcha_html );
		return $field_options_new;
	}
	function add_availablefieldsbasicoptions( $basic_option ) {

		$tccaptcha_filed_option = array(
			ARF_TC_SLUG => array(
				'requiredmsg'      => 1,
				'fielddescription' => 2,
			),
		);

		return array_merge( $basic_option, $tccaptcha_filed_option );
	} 
	function arf_display_tccaptcha_in_editor( $field ) {
		global $arfajaxurl;
		$field_name = 'item_meta[' . $field['id'] . ']';
		require ARF_TC_CORE . '/displayfield_in_editor.php';
	} 
	function add_field_to_frontend( $return_string, $form, $field_name, $arf_data_uniq_id, $field, $field_tootip, $field_description, $res_data, $inputStyle, $arf_main_label ) {
		global $style_settings, $arflitesettings, $arfeditingentry, $arffield;
		$entry_id = $arfeditingentry;

		require ARF_TC_CORE . '/displayfield_in_frontend.php';

		return $return_string;
	}
	function arf_add_captcha_inputs( $arf_form, $form, $arf_data_uniq_id, $arfbrowser_name, $browser_info ) {
		global $arflitesettings, $arf_form_all_footer_js, $wpdb, $arf_tc_captcha, $tbl_arf_fields;

		$form_id = $form->id;
		$is_exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl_arf_fields} WHERE form_id = %d AND type = %s", $form_id, ARF_TC_SLUG ) );// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: {$tbl_arf_fields} is table name defined globally. False Positive alarm
		if ( $is_exists > 0 ) {
			if ( ! empty( $arflitesettings->tc_pubkey ) && ! empty( $arflitesettings->tc_privkey ) ) {
				$dsize = 'normal';
				$arf_form .= '<input type="hidden" id="arf_tc_theme" value="' . $arflitesettings->tc_theme . '" />';
				$arf_form .= '<input type="hidden" id="arf_tccaptcha_public_key" value="' . $arflitesettings->tc_pubkey . '" />';
			}
		}
		return $arf_form;
	} 
	function arf_set_js_outside_preview_header() {
		global $arfforms_loaded, $arflitesettings, $arfversion, $arf_form_all_footer_js, $arf_tc_captcha;

		if ( ! empty( $arflitesettings->tc_pubkey ) && ! empty( $arflitesettings->tc_privkey ) ) {
			wp_register_script( 'arf-tccaptcha-front', ARF_TC_JS . '/arf_tccaptcha_front.js', array() );
			wp_register_script( 'cfturnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=_turnstileCb', array( 'jquery' ) );
			wp_print_scripts( 'arf-tccaptcha-front' );
			wp_print_scripts( 'cfturnstile' );
			wp_register_script( 'tc-responsive', ARF_TC_JS . '/arf_tc_responsive.js', array() );
			wp_print_scripts( 'tc-responsive' );
		}
	}
	function tc_responsive() {
		global $arfforms_loaded, $arflitesettings, $arfversion, $arf_form_all_footer_js, $arf_tc_captcha;

		if ( ! $this->is_arforms_support() ) {
			global $arflite_forms_loaded, $arflitesettings;
			$arfforms_loaded = $arflite_forms_loaded;
		}

		if ( is_array( $arfforms_loaded ) ) {

			foreach ( $arfforms_loaded as $form ) {

				if ( ! is_object( $form ) ) {
					continue;
				}

				$loaded_field = isset( $form->options['arf_loaded_field'] ) ? $form->options['arf_loaded_field'] : array();
				if ( in_array( ARF_TC_SLUG, $loaded_field ) || in_array( ARF_TC_SLUG, $loaded_field ) ) {
					if ( ! empty( $arflitesettings->tc_pubkey ) && ! empty( $arflitesettings->tc_privkey ) ) {
						wp_register_script( 'arf-tccaptcha-front', ARF_TC_JS . '/arf_tccaptcha_front.js', array() );
						wp_enqueue_script( 'arf-tccaptcha-front' );

						wp_register_script( 'cfturnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=_turnstileCb', array( 'jquery' ) );
						wp_enqueue_script( 'cfturnstile' );

					}

					wp_register_script( 'tc-responsive', ARF_TC_JS . '/arf_tc_responsive.js', array() );
					wp_enqueue_script( 'tc-responsive' );
					wp_register_style( 'tccaptcha-css', ARF_TURNSTL_CAPT_URL . '/css/arf_tccaptcha_frontend.css', array() );
					wp_enqueue_style( 'tccaptcha-css' );
				}
			}
		}
	} 
	public function arflite_tc_responsive() {

		global $arfliteversion, $arflite_forms_loaded, $arflitesettings;

		if ( is_array( $arflite_forms_loaded ) ) {

			foreach ( $arflite_forms_loaded as $form ) {

				if ( ! is_object( $form ) ) {
					continue;
				}

				$loaded_field = isset( $form->options['arf_loaded_field'] ) ? $form->options['arf_loaded_field'] : array();
				if ( in_array( ARF_TC_SLUG, $loaded_field ) || in_array( ARF_TC_SLUG, $loaded_field ) ) {

					if ( ! empty( $arflitesettings->tc_pubkey ) && ! empty( $arflitesettings->tc_privkey ) ) {

						wp_register_script( 'arf-tccaptcha-front', ARF_TC_JS . '/arf_tccaptcha_front.js', array() );
						wp_enqueue_script( 'arf-tccaptcha-front' );

						wp_register_script( 'cfturnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=_turnstileCb', array( 'jquery' ) );
						wp_enqueue_script( 'cfturnstile' );
					}

					wp_register_script( 'tc-responsive', ARF_TC_JS . '/arf_tc_responsive.js', array() );
					wp_enqueue_script( 'tc-responsive' );
					wp_register_style( 'tccaptcha-css', ARF_TURNSTL_CAPT_URL . '/css/arf_tccaptcha_frontend.css', array() );
					wp_enqueue_style( 'tccaptcha-css' );
				}
			}
		}
	} 
	function arf_tc_theme() {
		$arf_tc_theme = array(
			'light' => esc_html__( 'Light', 'arforms-form-builder' ),
			'dark'  => esc_html__( 'Dark', 'arforms-form-builder' ),
		);
		return $arf_tc_theme;
	} 
	function add_turnstile_setting_outside() {
		global $current_user, $arfliteformcontroller, $arformsmain, $arforms_general_settings, $arforms_modules;

		if( $arforms_modules->arforms_is_module_activate( 'arforms_tcaptcha' ) ):
			$arforms_all_settings = $arformsmain->arforms_global_option_data();
			$arflitesettings = json_decode( wp_json_encode( $arforms_all_settings['general_settings'] ) );
			?>
			<tr class="arfmainformfield" valign="top" style="display:table-row">
				<td class="lbltitle" colspan="2"><?php echo esc_html__( 'Turnstile CAPTCHA Configuration', 'arforms-form-builder' ); ?>&nbsp;
				</td>
			</tr>

			<tr class="arfmainformfield" valign="top" style="display:table-row">
				<td colspan="2" style="padding-left:0px; padding-bottom:30px;padding-top:15px;">
					<label class="lblsubtitle"><?php echo esc_html__( 'Turnstile CAPTCHA requires an API key, consisting of a "site" and a "secret" key. You can sign up for a', 'arforms-form-builder' ); ?>&nbsp;&nbsp;<a href="https://dash.cloudflare.com/login" target="_blank" class="arlinks"><b><?php echo esc_html__( 'free Turnstile CAPTCHA key', 'arforms-form-builder' ); ?></b></a>.</label>
				</td>
			</tr> 
			<tr class="arfmainformfield" valign="top" style="display:table-row" >
				<td class="tdclass" style="padding-left:30px;" width="18%">
					<label class="lblsubtitle"><?php echo esc_html__( 'Site Key', 'arforms-form-builder' ); ?></label>
				</td>
				<td>
					<input type="text" name="tc_pubkey" id="tc_arf_pubkey" class="txtmodal1" size="42" value="<?php echo isset( $arflitesettings->tc_pubkey ) ? esc_attr( $arflitesettings->tc_pubkey ) : ''; ?>"/>
				</td>
			</tr>
			<tr class="arfmainformfield" valign="top" style="display:table-row" >
				<td class="tdclass">
					<label class="lblsubtitle"><?php echo esc_html__( 'Secret Key', 'arforms-form-builder' ); ?></label>
				</td>
				<td>
					<input type="text" name="tc_privkey" id="tc_arf_privkey" class="txtmodal1" size="42" value="<?php echo isset( $arflitesettings->tc_privkey ) ? esc_attr( $arflitesettings->tc_privkey ) : ''; ?>"/>
				</td>
			</tr>								
			<tr class="arfmainformfield" valign="top" style="display: table-row">
				<td class="tdclass" >
					<label class="lblsubtitle"><?php echo esc_html__( 'Turnstile CAPTCHA Failed Message', 'arforms-form-builder' ); ?>&nbsp;&nbsp;</label>
				</td>						
				<td>				
					<input type="text" class="txtmodal1" value="<?php echo isset( $arflitesettings->tc_msg ) ? esc_attr( $arflitesettings->tc_msg ) : ''; ?>" id="arfvaluerecaptcha" name="tc_msg" />
				</td>
			</tr>		
			<tr class="arfmainformfield" valign="top" style="display: table-row">
				<td class="tdclass">
					<label class="lblsubtitle"><?php echo esc_html__( 'Turnstile CAPTCHA Theme', 'arforms-form-builder' ); ?></label>
				</td>
				<td class="email-setting-input-td">
					<?php
					global $arf_tc_captcha, $maincontroller, $arflitemaincontroller;
					$arf_turnstile_theme = '';
					$arf_tc_theme_options = array();
					$selected_list_label = '';
					$arf_tc_default_theme = esc_attr( 'light' );
					$arf_tc_theme = $this->arf_tc_theme();

					foreach ( $arf_tc_theme as $theme_value => $theme_name ) {

						if ( isset($arflitesettings->tc_theme) && $arflitesettings->tc_theme == $theme_value ) {
							$arf_tc_default_theme = esc_attr( $theme_value );
							$selected_list_label = $theme_name;
						} else {

							if( $theme_value == 'light' ){
								$arf_tc_default_theme = esc_attr( $theme_value );
								$selected_list_label = $theme_name;
							}
						}
						$arf_tc_theme_options[ $theme_value ] = esc_html($theme_name);
					}

					echo $arflitemaincontroller->arflite_selectpicker_dom( 'tc_theme', 'arf-tc-theme', '', 'width: 400px;', $arf_tc_default_theme, array(), $arf_tc_theme_options ); //phpcs:ignore

					?>
				</td>
			</tr>	
			<tr>
				<td colspan="2">
					<div class="dotted_line dottedline-width96"></div>
				</td>
			</tr>

			<?php
		endif;

		
	}

}

global $arforms_turnstile;
$arforms_turnstile = new arforms_turnstile();