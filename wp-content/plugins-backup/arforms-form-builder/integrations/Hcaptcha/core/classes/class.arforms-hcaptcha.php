<?php

/**
 * constant for arform-hcaptcha directory
 */
if ( ! defined( 'ARF_HCAPTCHA_DIR' ) ) {
	define( 'ARF_HCAPTCHA_DIR', ARFLITEURL . '/integrations/Hcaptcha' );
}

/**
 * constant for arform-hcaptcha core directory
 */
if ( ! defined( 'ARF_HCAPTCHA_CORE_DIR' ) ) {
	define( 'ARF_HCAPTCHA_CORE_DIR', ARF_HCAPTCHA_DIR . '/core' );
}


/**
 * constant for arform-hcaptcha class directory
 */
if ( ! defined( 'ARF_HCAPTCHA_CLASS_DIR' ) ) {
	define( 'ARF_HCAPTCHA_CLASS_DIR', ARF_HCAPTCHA_DIR . '/core/classes' );
}


/**
 * constant for arform-hcaptcha views directory
 */
if ( ! defined( 'ARF_HCAPTCHA_VIEWS_DIR' ) ) {
	define( 'ARF_HCAPTCHA_VIEWS_DIR', ARF_HCAPTCHA_DIR . '/core/views' );
}

/**
 * constant for arform-hcaptcha js directory
 */
if ( ! defined( 'ARF_HCAPTCHA_JS_DIR' ) ) {
	define( 'ARF_HCAPTCHA_JS_DIR', ARF_HCAPTCHA_DIR . '/js' );
}

/**
 * constant for arform-hcaptcha css directory
 */
if ( ! defined( 'ARF_HCAPTCHA_CSS_DIR' ) ) {
	define( 'ARF_HCAPTCHA_CSS_DIR', ARF_HCAPTCHA_DIR . '/css' );
}


/**
 * constant for arform-hcaptcha images directory
 */
if ( ! defined( 'ARF_HCAPTCHA_IMAGE_DIR' ) ) {
	define( 'ARF_HCAPTCHA_IMAGE_DIR', ARF_HCAPTCHA_DIR . '/images' );
}


/**
 * constant for arform-hcaptcha widgets directory
 */
if ( ! defined( 'ARF_HCAPTCHA_WIDGETS_DIR' ) ) {
	define( 'ARF_HCAPTCHA_WIDGETS_DIR', ARF_HCAPTCHA_DIR . '/core/widgets' );
}

/**
 * constant for hcaptcha slug
 */
if ( ! defined( 'ARF_HCAPTCHA_SLUG' ) ) {
	define( 'ARF_HCAPTCHA_SLUG', 'hcaptcha' );
}

global $MdlDb, $ARFLiteMdlDb, $arformsmain, $arflitesettings;

$arforms_all_settings = $arformsmain->arforms_global_option_data();
$arflitesettings = json_decode( wp_json_encode( $arforms_all_settings['general_settings'] ) );

/**
 * class ARF_hcaptcha
 */
if ( ! class_exists( 'ARF_hcaptcha' ) ) {

	class ARF_hcaptcha {

		/**
		 * __construct()
		 *
		 * @return void
		 */
		public function __construct() {
			global  $wpdb, $arflitesettings, $arformsmain;

			add_action( 'admin_notices', array( $this, 'arf_hcaptcha_admin_notices' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'arf_hcaptcha_set_css' ), 8 );
			add_action( 'admin_enqueue_scripts', array( $this, 'arf_hcaptcha_set_js' ), 10 );

			add_action( 'include_outside_js_css_for_preview_header', array( $this, 'arf_hcaptcha_set_js_outside_preview_header' ), 11 );
			add_action( 'arflite_include_outside_js_css_for_preview_header', array( $this, 'arf_hcaptcha_set_js_outside_preview_header' ), 11 );

			add_action( 'arforms_load_captcha_settings', array( $this, 'arflite_hcaptcha_settings_block' ) );

			add_filter( 'arf_update_global_setting_outside', array( $this, 'arf_update_hcaptcha_global_option' ), 13, 2 );
			add_filter( 'arflite_update_global_setting_outside', array( $this, 'arf_update_hcaptcha_global_option' ), 13, 2 );

			add_filter( 'arf_display_field_name_box', array( $this, 'arf_hide_hcaptcha_field_name_text' ), 10, 2 );
			add_filter( 'arflite_display_field_name_box', array( $this, 'arf_hide_hcaptcha_field_name_text' ), 10, 2 );

			add_filter( 'arf_field_type_label_filter', array( $this, 'arf_hcaptcha_field_label_for_options' ), 10, 1 );
			add_filter( 'arflite_field_type_label_filter', array( $this, 'arf_hcaptcha_field_label_for_options' ), 10, 1 );

			add_filter( 'arf_wrap_input_field', array( $this, 'arf_wrap_hcaptcha_field_from_outside' ), 11, 2 );
			add_filter( 'arflite_wrap_input_field', array( $this, 'arf_wrap_hcaptcha_field_from_outside' ), 11, 2 );

			add_filter( 'arfaavailablefields', array( $this, 'arf_add_hcaptcha_field_outside' ) );
			add_filter( 'arfliteaavailablefields', array( $this, 'arf_add_hcaptcha_field_outside' ) );

			add_filter( 'arf_manage_field_element_order_outside', array( $this, 'arf_add_hcaptcha_field_order' ), 10, 1 );
			add_filter( 'arflite_manage_field_element_order_outside', array( $this, 'arf_add_hcaptcha_field_order' ), 10, 1 );

			add_filter( 'arform_input_fields', array( $this, 'arf_add_hcaptcha_in_input_fields' ), 10, 1 );
			add_filter( 'arformlite_input_fields', array( $this, 'arf_add_hcaptcha_in_input_fields' ), 10, 1 );

			add_filter( 'arf_positioned_field_options_icon', array( $this, 'arf_hcaptcha_positioned_field_options_icon' ), 10, 2 );
			add_filter( 'arflite_positioned_field_options_icon', array( $this, 'arf_hcaptcha_positioned_field_options_icon' ), 10, 2 );

			add_action( 'arf_add_fieldiconbox_class_outside', array( $this, 'arf_hcaptcha_add_fieldiconbox_class' ) );

			add_filter( 'arf_disply_required_field_outside', array( $this, 'arf_hcaptcha_display_required_field' ), 10, 2 );
			add_filter( 'arflite_disply_required_field_outside', array( $this, 'arf_hcaptcha_display_required_field' ), 10, 2 );

			add_filter( 'arf_display_duplicate_field_outside', array( $this, 'arf_hcaptcha_display_duplicate_field' ), 10, 2 );

			add_filter( 'arfavailablefieldsbasicoptions', array( $this, 'arf_hcaptcha_availablefieldsbasicoptions' ), 10, 3 );
			add_filter( 'arfliteavailablefieldsbasicoptions', array( $this, 'arf_hcaptcha_availablefieldsbasicoptions' ), 10, 3 );

			add_filter( 'arf_change_json_default_data_ouside', array( $this, 'arf_hcaptcha_add_new_field_json_data' ), 10, 1 );
			add_filter( 'arflite_change_json_default_data_ouside', array( $this, 'arf_hcaptcha_add_new_field_json_data' ), 10, 1 );

			add_filter( 'arf_new_field_array_filter_outside', array( $this, 'arf_hcaptcha_new_field_array' ), 10, 4 );
			add_filter( 'arflite_new_field_array_filter_outside', array( $this, 'arf_hcaptcha_new_field_array' ), 10, 4 );

			add_filter( 'arf_new_field_array_materialize_filter_outside', array( $this, 'arf_hcaptcha_new_field_materialize_outside' ), 10, 4 );
			add_filter( 'arflite_new_field_array_materialize_filter_outside', array( $this, 'arf_hcaptcha_new_field_materialize_outside' ), 10, 4 );

			add_filter( 'arf_new_field_array_materialize_outlined_filter_outside', array( $this, 'arf_hcaptcha_new_field_materialize_outside' ), 10, 4 );

			add_filter( 'arf_form_fields', array( $this, 'arf_hcaptcha_field_to_frontend' ), 11, 10 );
			add_filter( 'arflite_form_fields', array( $this, 'arf_hcaptcha_field_to_frontend' ), 11, 10 );

			add_action( 'arfdisplayaddedfields', array( $this, 'arf_display_hcaptcha_field_in_editor' ), 11 );
			add_action( 'arflitedisplayaddedfields', array( $this, 'arf_display_hcaptcha_field_in_editor' ), 11 );

			add_action( 'wp_footer', array( $this, 'arf_add_hcaptcha_js' ) );
			add_action( 'wp_footer', array( $this, 'arflite_add_hcaptcha_js' ) );

			add_filter( 'arf_additional_form_content_outside', array( $this, 'arf_add_hcaptcha_inputs' ), 10, 5 );
			add_filter( 'arflite_additional_form_content_outside', array( $this, 'arf_add_hcaptcha_inputs' ), 10, 5 );

			// for normal submission
			add_filter( 'arf_is_validateform_outside', array( $this, 'hcaptcha_validate_form_outside' ), 10, 2 );
			add_filter( 'arflite_is_validateform_outside', array( $this, 'hcaptcha_validate_form_outside' ), 10, 2 );

			// for ajax submission
			add_filter( 'arf_validate_form_outside_errors', array( $this, 'hcaptcha_ajax_form_validation' ), 10, 4 );
			add_filter( 'arflite_validate_form_outside_errors', array( $this, 'hcaptcha_ajax_form_validation' ), 10, 4 );

			add_action( 'arf_disply_multicolumn_fieldolumn_field_outside', array( $this, 'arf_hide_multicolumn_for_hcaptcha' ), 10, 2 );
			add_action( 'arflite_disply_multicolumn_fieldolumn_field_outside', array( $this, 'arf_hide_multicolumn_for_hcaptcha' ), 10, 2 );

			add_filter( 'arforms_remove_captcha_fields_for_validation', array( $this, 'arforms_remove_hcaptcha_captcha_field' ), 10, 2 );

			add_action( 'admin_enqueue_scripts', array( $this, 'arf_hcaptcha_inline_style' ), 11 );
		}



		/**
		 * arf_hcaptcha_set_css()
		 * to addd js and css for hcaptcha
		 *
		 * @return void
		 */
		function arf_hcaptcha_set_css() {

			if ( isset( $_REQUEST['page'] ) && '' != $_REQUEST['page'] && ( 'ARForms' == $_REQUEST['page'] && isset( $_REQUEST['arfaction'] ) && in_array( $_REQUEST['arfaction'], array( 'new', 'edit', 'duplicate' ) ) ) ) {
				wp_register_style( 'arf_hcaptcha_admin_css', ARF_HCAPTCHA_CSS_DIR . '/hcaptcha_style.css', array() );
				wp_enqueue_style( 'arf_hcaptcha_admin_css' );
			}
		}



		/**
		 * arf_hcaptcha_set_js()
		 * to add js for hcaptcha
		 */
		function arf_hcaptcha_set_js() {

			if ( isset( $_REQUEST['page'] ) && '' != $_REQUEST['page'] && ( 'ARForms' == $_REQUEST['page'] && isset( $_REQUEST['arfaction'] ) && in_array( $_REQUEST['arfaction'], array( 'new', 'edit', 'duplicate' ) ) ) ) {
				wp_register_script( 'arf_hcaptcha_admin_js', ARF_HCAPTCHA_JS_DIR . '/hcaptcha_restrict_duplicate_field.js', array() );
				wp_enqueue_script( 'arf_hcaptcha_admin_js' );
			}
		}



		/**
		 * arf_hcaptcha_set_js_outside_preview_header()
		 */
		function arf_hcaptcha_set_js_outside_preview_header() {

			global $arfforms_loaded, $arflitesettings, $arfversion, $arf_form_all_footer_js, $arforms_hcaptcha;

			if ( ! empty( $arflitesettings->hcaptcha_public_key ) && ! empty( $arflitesettings->hcaptcha_private_key ) ) {
				wp_register_script( 'hcaptcha-js', 'https://js.hcaptcha.com/1/api.js?onload=render_arf_hcaptcha&render=onload', array( 'jquery' ) );
				wp_register_script( 'arf_hcaptcha_js', ARF_HCAPTCHA_JS_DIR . '/arf_hcaptcha.js', array() );
				wp_print_scripts( 'hcaptcha-js' );
				wp_enqueue_script( 'arf_hcaptcha_js' );
				

			}
		}



		/**
		 * get_arforms_version()
		 * to get current version of ARForms from db
		 *
		 * @return float $arf_db_version
		 */
		function get_arforms_version() {
			$arf_db_version = get_option( 'arf_db_version' );

			return isset( $arf_db_version ) ? $arf_db_version : 0;
		}



		/**
		 * get_arformslite_version()
		 * to get current version of ARForms from db
		 *
		 * @return float $arflite_db_version
		 */
		function get_arformslite_version() {
			$arflite_db_version = get_option( 'arflite_db_version' );

			return isset( $arflite_db_version ) ? $arflite_db_version : 0;
		}



		/**
		 * is_arforms_version()
		 * to compare the version of arforms
		 * arforms version should be greater than or equal to 5.5
		 *
		 * @return bool
		 */
		function is_arforms_version() {

			if ( ! version_compare( $this->get_arforms_version(), '5.5', '>=' ) ) {
				return false;
			} else {
				return true;
			}
		}



		/**
		 * is_arformslite_version()
		 * to compare the version of arformslite
		 *
		 * @return bool
		 */
		function is_arformslite_version() {

			if ( ! version_compare( $this->get_arformslite_version(), '1.0', '>=' ) ) {
				return false;
			} else {
				return true;
			}
		}


		/**
		 * arf_hcaptcha_theme()
		 */
		function arf_hcaptcha_theme() {
			$arf_hcaptcha_theme = array(
				'light' => __( 'Light', 'arforms-form-builder' ),
				'dark'  => __( 'Dark', 'arforms-form-builder' ),
			);
			return $arf_hcaptcha_theme;
		}



		/**
		 * arf_hcaptcha_admin_notices()
		 * to display admin notices if arforms is not installed / activated or version is not compatible
		 *
		 * @return void
		 */
		function arf_hcaptcha_admin_notices() {

			// check if hcaptcha supports arforms lite and arforms pro
			// if hcaptcha supports arforms check for version compatibili
			global $wp_version;
			if ( version_compare( $wp_version, '4.5.0', '<' ) ) {
				echo "<div class='error arf_error'><p>Please meet the minimum requirement of WordPress version 4.5 to activate ARForms - hCaptcha Add-on</p></div>";
			}

			if ( $this->is_arforms_support() && version_compare( $this->get_arforms_version(), '1.6.3', '<=' ) ) {
				echo "<div class='updated'><p>" . esc_html__( 'Hcaptcha add-on for ARForms requires ARForms installed with version 6.4 or higher.', 'arforms-form-builder' ) . '</p></div>';
			}
		}



		/**
		 * is_arforms_support()
		 * to check if arforms plugin active is not
		 *
		 * @return bool
		 */
		function is_arforms_support() {
			if ( file_exists( ABSPATH . '/wp-admin/includes/plugin.php' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';

				return is_plugin_active( 'arforms/arforms.php' );
			}
		}



		/**
		 * arflite_hcaptcha_settings_block()
		 * to display hcaptcha configuration block in general settings
		 *
		 * @return void
		 */
		function arflite_hcaptcha_settings_block() {

			global $arformsmain, $arforms_modules;

			if ( $arforms_modules->arforms_is_module_activate( 'arforms_hcaptcha' ) ) :

				$arforms_all_settings = $arformsmain->arforms_global_option_data();

				$arflitesettings = json_decode( json_encode( $arforms_all_settings['general_settings'] ) );

				?>

				<!-- main heading -->
				<tr class="arfmainformfield" valign="top">
					<td class="lbltitle" colspan="2"><?php echo esc_html__( 'hCaptcha Configuration', 'arforms-form-builder' ); ?>&nbsp;
					</td>
				</tr>

				<!-- redirect api key generate link -->
				<tr class="arfmainformfield" valign="top">
					<td colspan="2" style="padding-left:0px; padding-bottom:30px;padding-top:15px;">
						<label class="lblsubtitle"><?php echo esc_html__( 'hCaptcha requires an API key, consisting of a "site" and a "private" key. You can sign up for a', 'arforms-form-builder' ); ?>&nbsp;&nbsp;<a href="https://dashboard.hcaptcha.com/login" target="_blank" class="arlinks"><b><?php echo esc_html__( 'free hCaptcha key', 'arforms-form-builder' ); ?></b></a>.</label>
					</td>
				</tr>

				<!-- site key -->
				<tr class="arfmainformfield" valign="top">
					<td class="tdclass" style="padding-left:30px;" width="18%">
						<label class="lblsubtitle"><?php echo esc_html__( 'Site Key', 'arforms-form-builder' ); ?></label>
					</td>

					<td>
						<input type="text" name="hcaptcha_public_key" id="hcaptcha_public_key" class="txtmodal1" size="42"  value="<?php echo esc_attr( $arflitesettings->hcaptcha_public_key ); ?>" />
					</td>
				</tr>

				<!-- secret key -->
				<tr class="arfmainformfield" valign="top">
					<td class="tdclass">
						<label class="lblsubtitle"><?php echo esc_html__( 'Secret Key', 'arforms-form-builder' ); ?></label>
					</td>

					<td>
						<input type="text" name="hcaptcha_private_key" id="hcaptcha_private_key" class="txtmodal1" size="42" value="<?php echo esc_attr( $arflitesettings->hcaptcha_private_key ); ?>" />
					</td>
				</tr>


				<!-- theme -->
				<tr class="arfmainformfield" valign="top">
					<td class="tdclass">
						<label class="lblsubtitle"><?php echo esc_html__( 'hCaptcha Theme', 'arforms-form-builder' ); ?></label>
					</td>

					<td style="padding-bottom:10px;">
					
					<?php

					global $arforms_hcaptcha, $maincontroller, $arflitemaincontroller;

					$hcaptcha_theme = $arforms_hcaptcha->arf_hcaptcha_theme();
					$hcaptcha_default_theme = ( 'light' );
					$hcaptcha_theme_label = '';
					$selected_list_label = esc_html__( 'Light', 'arforms-form-builder' );

					foreach ( $hcaptcha_theme as $theme_value => $theme_name ) {
						if ( isset( $arflitesettings->hcaptcha_theme ) && $arflitesettings->hcaptcha_theme == $theme_value ) {
							$hcaptcha_default_theme    = esc_attr( $theme_value );
							$selected_list_label = $theme_name;
						}
						$hcaptcha_theme_options[ $theme_value ] = esc_html__( $theme_name, 'arforms-form-builder' );
					}

					echo $arflitemaincontroller->arflite_selectpicker_dom( 'hcaptcha_theme', 'hcaptcha_theme', '', 'width:400px;', $hcaptcha_default_theme, array(), $hcaptcha_theme_options );
					?>
				   
				</tr>


				<!-- Failed Message -->
				<tr class="arfmainformfield" valign="top">
					<td class="tdclass" >
						<label class="lblsubtitle"><?php echo esc_html__( 'hCaptcha Failed Message', 'arforms-form-builder' ); ?>&nbsp;&nbsp;<span style="vertical-align:middle" class="arfglobalrequiredfield">*</span></label>
					</td>
					
					<td>
						<?php $hcaptcha_msg = isset( $arflitesettings->hcaptcha_message ) ? $arflitesettings->hcaptcha_message : 'Invalid hCaptcha. Please try again.'; ?>
						<input type="text" class="txtmodal1" value="<?php echo esc_attr( $hcaptcha_msg ); ?>" id="hcaptcha_message" name="hcaptcha_message" />
						<div class="arferrmessage" id="arferrorsubmitvalue_captcha" style="display:none;"><?php echo esc_html__( 'This field cannot be blank.', 'arforms-form-builder' ); ?></div>
					</td>
				</tr>

				<!-- dotted line -->
				<tr class="arfmainformfield" valign="top">
					<td colspan="2"><div style="width:96%" class="dotted_line"></div></td>
				</tr>
			   
				<?php
			endif;
		}



		/**
		 * arf_update_hcaptcha_global_option()
		 * to save hcaptcha settings
		 *
		 * @param $opt_data_from_outside, $params
		 * @return $opt_data_from_outside
		 */
		function arf_update_hcaptcha_global_option( $opt_data_from_outside, $params ) {
			global $arformsmain;

			$hcaptcha_keys = array( 'hcaptcha_public_key', 'hcaptcha_private_key', 'hcaptcha_theme', 'hcaptcha_message' );

			foreach ( $hcaptcha_keys as $opt_key ) {
				$opt_value = ! empty( $params[ $opt_key ] ) ? $params[ $opt_key ] : array();

				$arformsmain->arforms_update_settings( $opt_key, $opt_value, 'general_settings' );

				$opt_data_from_outside[ $opt_key ] = $opt_value;
			}

			return $opt_data_from_outside;
		}



		/**
		 * arf_hide_hcaptcha_field_name_text()
		 *
		 * @param $display_name_box, $field
		 * @return $display_name_box
		 */
		function arf_hide_hcaptcha_field_name_text( $display_name_box, $field ) {
			if ( isset( $field['type'] ) && 'hcaptcha' == $field['type'] ) {
				$display_name_box = false;
			}

			return $display_name_box;
		}



		/**
		 * arf_hcaptcha_field_label_for_options()
		 *
		 * @param $field_label_arr
		 * @return $field_label_arr
		 */
		function arf_hcaptcha_field_label_for_options( $field_label_arr ) {
			$field_label_arr['hcaptcha'] = esc_html__( 'hCaptcha', 'arforms-form-builder' );

			return $field_label_arr;
		}



		/**
		 * arf_wrap_hcaptcha_field_from_outside()
		 *
		 * @param $wrap, $field_type
		 * @return $wrap
		 */
		function arf_wrap_hcaptcha_field_from_outside( $wrap, $field_type ) {

			if ( 'hcaptcha' == $field_type ) {
				return false;
			}

			return $wrap;
		}



		/**
		 * arf_add_hcaptcha_field_outside()
		 *
		 * @param $pro_fields
		 * @return $pro_fields
		 */
		function arf_add_hcaptcha_field_outside( $fields ) {

			$restrict_cls = '';

			global $wpdb, $MdlDb, $tbl_arf_fields;

			if ( ! empty( $_REQUEST['arfaction'] ) && ( 'edit' == $_REQUEST['arfaction'] || 'duplicate' == $_REQUEST['arfaction'] ) && ! empty( $_REQUEST['id'] ) ) {

				$form_id = intval( $_REQUEST['id'] );

				$is_exists = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE form_id = %d AND type = %s', $tbl_arf_fields, $form_id, 'hcaptcha' ) );

				if ( $is_exists > 0 ) {
					$restrict_cls = 'arf_restricted_form_fields';
				}
			}

			$fields['hcaptcha'] = array(
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" style="margin-left:-8px !important;" height="25" viewBox="0 0 599.18 599.18" width="25"><path d="m374.48 524.29h74.9v74.89h-74.9z" fill="#0074bf" opacity=".502"/><path d="m299.59 524.29h74.89v74.89h-74.89zm-74.89 0h74.89v74.89h-74.89z" fill="#0074bf" opacity=".702"/><path d="m149.8 524.29h74.9v74.89h-74.9z" fill="#0074bf" opacity=".502"/><path d="m449.39 449.39h74.9v74.9h-74.9z" fill="#0082bf" opacity=".702"/><path d="m374.48 449.39h74.9v74.9h-74.9z" fill="#0082bf" opacity=".8"/><path d="m299.59 449.39h74.89v74.9h-74.89zm-74.89 0h74.89v74.9h-74.89z" fill="#0082bf"/><path d="m149.8 449.39h74.9v74.9h-74.9z" fill="#0082bf" opacity=".8"/><path d="m74.89 449.39h74.9v74.9h-74.9z" fill="#0082bf" opacity=".702"/><g fill="#008fbf"><path d="m524.29 374.48h74.89v74.9h-74.89z" opacity=".502"/><path d="m449.39 374.48h74.9v74.9h-74.9z" opacity=".8"/><path d="m374.48 374.48h74.9v74.9h-74.9zm-74.89 0h74.89v74.9h-74.89zm-74.89 0h74.89v74.9h-74.89z"/><path d="m149.8 374.48h74.9v74.9h-74.9z"/><path d="m74.89 374.48h74.9v74.9h-74.9z" opacity=".8"/><path d="m0 374.48h74.89v74.9h-74.89z" opacity=".502"/></g><path d="m524.29 299.59h74.89v74.89h-74.89z" fill="#009dbf" opacity=".702"/><path d="m449.39 299.59h74.9v74.89h-74.9zm-74.91 0h74.9v74.89h-74.9zm-74.89 0h74.89v74.89h-74.89zm-74.89 0h74.89v74.89h-74.89z" fill="#009dbf"/><path d="m149.8 299.59h74.9v74.89h-74.9zm-74.91 0h74.9v74.89h-74.9z" fill="#009dbf"/><path d="m0 299.59h74.89v74.89h-74.89z" fill="#009dbf" opacity=".702"/><path d="m524.29 224.7h74.89v74.89h-74.89z" fill="#00abbf" opacity=".702"/><path d="m449.39 224.7h74.9v74.89h-74.9zm-74.91 0h74.9v74.89h-74.9zm-74.89 0h74.89v74.89h-74.89zm-74.89 0h74.89v74.89h-74.89z" fill="#00abbf"/><path d="m149.8 224.7h74.9v74.89h-74.9zm-74.91 0h74.9v74.89h-74.9z" fill="#00abbf"/><path d="m0 224.7h74.89v74.89h-74.89z" fill="#00abbf" opacity=".702"/><g fill="#00b9bf"><path d="m524.29 149.8h74.89v74.9h-74.89z" opacity=".502"/><path d="m449.39 149.8h74.9v74.9h-74.9z" opacity=".8"/><path d="m374.48 149.8h74.9v74.9h-74.9zm-74.89 0h74.89v74.9h-74.89zm-74.89 0h74.89v74.9h-74.89z"/><path d="m149.8 149.8h74.9v74.9h-74.9z"/><path d="m74.89 149.8h74.9v74.9h-74.9z" opacity=".8"/><path d="m0 149.8h74.89v74.9h-74.89z" opacity=".502"/></g><path d="m449.39 74.89h74.9v74.9h-74.9z" fill="#00c6bf" opacity=".702"/><path d="m374.48 74.89h74.9v74.9h-74.9z" fill="#00c6bf" opacity=".8"/><path d="m299.59 74.89h74.89v74.9h-74.89zm-74.89 0h74.89v74.9h-74.89z" fill="#00c6bf"/><path d="m149.8 74.89h74.9v74.9h-74.9z" fill="#00c6bf" opacity=".8"/><path d="m74.89 74.89h74.9v74.9h-74.9z" fill="#00c6bf" opacity=".702"/><path d="m374.48 0h74.9v74.89h-74.9z" fill="#00d4bf" opacity=".502"/><path d="m299.59 0h74.89v74.89h-74.89zm-74.89 0h74.89v74.89h-74.89z" fill="#00d4bf" opacity=".702"/><path d="m149.8 0h74.9v74.89h-74.9z" fill="#00d4bf" opacity=".502"/><path d="m197.2 275.96 20.87-46.71c7.61-11.97 6.6-26.64-1.72-34.96-.28-.28-.56-.55-.86-.81-.29-.26-.59-.52-.89-.76a21.043 21.043 0 0 0 -1.92-1.37 22.68 22.68 0 0 0 -4.51-2.13c-1.58-.55-3.21-.92-4.87-1.12-1.66-.19-3.34-.2-5-.03s-3.3.51-4.88 1.04c-1.79.55-3.53 1.27-5.19 2.13a32.32 32.32 0 0 0 -4.72 3.02 32.38 32.38 0 0 0 -4.12 3.82 32 32 0 0 0 -3.37 4.48c-.98 1.59-28.57 66.66-39.2 96.62s-6.39 84.91 34.61 125.99c43.48 43.48 106.43 53.41 146.58 23.28.42-.21.84-.44 1.24-.67.41-.23.81-.48 1.2-.74.4-.25.78-.52 1.16-.8.38-.27.75-.56 1.11-.86l123.73-103.32c6.01-4.97 14.9-15.2 6.92-26.88-7.79-11.39-22.55-3.64-28.57.21l-71.21 51.78c-.33.27-.72.48-1.13.6-.42.12-.85.16-1.28.11s-.85-.19-1.22-.4c-.38-.21-.71-.5-.97-.85-1.81-2.22-2.13-8.11.71-10.44l109.16-92.64c9.43-8.49 10.74-20.84 3.1-29.3-7.45-8.29-19.29-8.04-28.8.53l-98.28 76.83c-.46.38-.99.66-1.56.82s-1.17.21-1.76.13-1.15-.27-1.66-.58c-.51-.3-.96-.7-1.3-1.18-1.94-2.18-2.69-5.89-.5-8.07l111.3-108.01c2.09-1.95 3.78-4.29 4.96-6.88 1.18-2.6 1.85-5.41 1.95-8.26s-.36-5.7-1.36-8.37c-1-2.68-2.51-5.13-4.45-7.22-.97-1.03-2.05-1.95-3.2-2.75a21.14 21.14 0 0 0 -3.69-2.05c-1.3-.55-2.65-.97-4.03-1.26-1.38-.28-2.79-.42-4.2-.41-1.44-.02-2.88.1-4.29.37a21.906 21.906 0 0 0 -7.96 3.16c-1.21.78-2.34 1.68-3.38 2.68l-113.73 106.83c-2.72 2.72-8.04 0-8.69-3.18-.06-.28-.08-.57-.07-.86s.06-.58.15-.85c.08-.28.2-.55.35-.79.15-.25.33-.48.54-.68l87.05-99.12a21.38 21.38 0 0 0 6.82-15.3c.11-5.81-2.15-11.42-6.25-15.53-4.11-4.12-9.71-6.4-15.52-6.31s-11.34 2.53-15.32 6.77l-132.01 145.95c-4.73 4.73-11.7 4.97-15.02 2.22-.51-.4-.93-.9-1.24-1.46-.32-.56-.52-1.18-.6-1.82-.08-.65-.03-1.3.14-1.92s.46-1.21.85-1.72z" fill="#fff"/></svg>',

				'label' => esc_html__( 'hCaptcha', 'arforms-form-builder' ),

				'class' => $restrict_cls,
			);

			$fields['hcaptcha'] = isset( $fields['hcaptcha'] ) ? $fields['hcaptcha'] : array();

			return $fields;
		}



		/**
		 * arf_add_hcaptcha_field_order()
		 *
		 * @param $fields
		 * @return $fields
		 */
		function arf_add_hcaptcha_field_order( $fields ) {

			if ( $this->is_arforms_support() ) {
				array_push( $fields, 'hcaptcha' );
			} else {
				$fields[12] = 'hcaptcha';
			}

			return $fields;
		}



		/**
		 * arf_add_hcaptcha_in_input_fields()
		 *
		 * @param $inputFields
		 * @return $inputFields
		 */
		function arf_add_hcaptcha_in_input_fields( $inputFields ) {

			array_push( $inputFields, 'hcaptcha' );

			return $inputFields;
		}



		/**
		 * arf_hcaptcha_positioned_field_options_icon()
		 *
		 * @param $positioned_field_icons, $field_icons
		 * @return $positioned_field_icons
		 */
		function arf_hcaptcha_positioned_field_options_icon( $positioned_field_icons, $field_icons ) {

			$positioned_field_icons['hcaptcha'] = "{$field_icons['field_delete_icon']}" . str_replace( '{arf_field_type}', 'hcaptcha', $field_icons['field_option_icon'] ) . "{$field_icons['arf_field_move_icon']}";

			return $positioned_field_icons;
		}



		/**
		 * arf_hcaptcha_add_fieldiconbox_class()
		 *
		 * @param $field
		 */
		function arf_hcaptcha_add_fieldiconbox_class( $field ) {
			if ( 'hcaptcha' == $field['type'] ) {
				echo 'arf_hcaptcha_fieldiconbox';
			}
		}



		/**
		 * arf_hcaptcha_display_required_field()
		 *
		 * @param $arf_display_required_field, $field
		 * @return $arf_display_required_field
		 */
		function arf_hcaptcha_display_required_field( $arf_disply_required_field, $field ) {
			if ( isset( $field['type'] ) && 'hcaptcha' == $field['type'] ) {

				$arf_disply_required_field = false;
			}

			return $arf_disply_required_field;
		}



		/**
		 * arf_hcaptcha_display_duplicate_field()
		 *
		 * @param $arf_duplicate_field, $field
		 * @return $arf_duplicate_field
		 */
		function arf_hcaptcha_display_duplicate_field( $arf_duplicate_field, $field ) {
			if ( isset( $field['type'] ) && 'hcaptcha' == $field['type'] ) {

				$arf_duplicate_field = false;
			}

			return $arf_duplicate_field;
		}



		/**
		 * arf_hcaptcha_availablefieldsbasicoptions()
		 *
		 * @param $args
		 * @return $args
		 */
		function arf_hcaptcha_availablefieldsbasicoptions( $args ) {
			$hcaptcha_filed_option = array(
				'hcaptcha' => array(
					'requiredmsg'      => 1,
					'fielddescription' => 2,
				),
			);

			return array_merge( $args, $hcaptcha_filed_option );
		}



		/**
		 * arf_hcaptcha_add_new_field_json_data()
		 *
		 * @param $field_json
		 * @return $field_json
		 */
		function arf_hcaptcha_add_new_field_json_data( $field_json ) {

			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			WP_Filesystem();
			global $wp_filesystem;

			$hcaptcha_field_data = $wp_filesystem->get_contents( ARF_HCAPTCHA_CORE_DIR . '/arf_hcaptcha_field_data.json' );
			$hcaptcha_field_data_array = json_decode( $hcaptcha_field_data, true );
			$hcaptcha_field_data_obj = $hcaptcha_field_data_array['field_data']['hcaptcha'];

			$field_json['field_data']['hcaptcha'] = $hcaptcha_field_data_obj;

			return $field_json;
		}



		/**
		 * arf_hcaptcha_new_field_array()
		 *
		 * @param $arflite_new_field_array, $field_icons
		 * @return $arflite_new_field_array
		 */
		function arf_hcaptcha_new_field_array( $arflite_new_field_array, $field_icons, $field_data_array, $positioned_field_icons ) {
			global $arfieldcontroller, $arformsmain;

			if ( ! function_exists( 'WP_Flisystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			WP_Filesystem();
			global $wp_filesystem;

			$field_data = $wp_filesystem->get_contents( ARF_HCAPTCHA_CORE_DIR . '/arf_hcaptcha_field_data.json' );

			$field_data_array = json_decode( $field_data );

			$arf_editor_unique_id = 1221;

			$hcaptcha_field_data_obj = $field_data_array->field_data->hcaptcha;

			$field_opt_arr            = array(
				'hcaptcha' => array(
					'requiredmsg'      => 2,
					'fielddescription' => 3,
				),
			);

			$field_order_hcaptcha   = isset( $field_opt_arr['hcaptcha'] ) ? $field_opt_arr['hcaptcha'] : '';

			$onclick_func = $arformsmain->arforms_is_pro_active() ? 'arf_close_field_option_popup()' : 'arflite_close_field_option_popup()';

			$hcaptcha_html = array(
				'hcaptcha' => "<div class='arf_inner_wrapper_sortable arfmainformfield edit_form_item arffieldbox ui-state-default 1  arf1columns single_column_wrapper' data-id='arf_editor_main_row_{arf_editor_index_row}'>

                <div class='sortable_inner_wrapper edit_field_type_hcaptcha'  id='arfmainfieldid_{arf_field_id}' inner_class='arf_1col'>

                <div id='arf_field_{arf_field_id}' class='arfformfield control-group arfmainformfield top_container arf_field_{arf_field_id}'>
                        <div class='fieldname-row arf_dig_display_block'>
                            <div class='fieldname'></div>
                        </div>
                        <div class='arf_fieldiconbox arf_hcaptcha_fieldiconbox' data-field_id='{arf_field_id}' data-field_id='{arf_field_id}'>" . $positioned_field_icons['hcaptcha'] . "</div>
                        <div class='controls'>
                            <img class='arf_hcaptcha_editor_img_default' src='" . ARF_HCAPTCHA_IMAGE_DIR . "/hcaptcha_field_image.jpg'  />
                            
                            <input id='field_{arf_unique_key}_" . $arf_editor_unique_id . " name='item_meta[{arf_field_id}]' type='hidden' class='arf_hcaptcha_output arf_hcaptcha_float_left'/>
                            <div class='arf_field_description' id='field_description_{arf_field_id}'></div>
                            <div class='help-block'></div>
                        </div>
                        <input type='hidden' name='arf_field_data_{arf_field_id}' id='arf_field_data_{arf_field_id}' value='" . htmlspecialchars( json_encode( $hcaptcha_field_data_obj ) ) . "' data-field_options='" . json_encode( $field_order_hcaptcha ) . "' />
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

			return array_merge( $arflite_new_field_array, $hcaptcha_html );
		}



		/**
		 * arf_hcaptcha_new_field_materialize_outside()
		 *
		 * @param $arflite_new_field_array_materialize, $field_icons, $field_data_array, $positioned_field_icons
		 * @return $arflite_new_field_array_materialize
		 */
		function arf_hcaptcha_new_field_materialize_outside( $arflite_new_field_array_materialize, $field_icons, $field_data_array, $positioned_field_icons ) {
			global $arfieldcontroller, $arflitesettings, $arforms_hcaptcha, $arformsmain;

			if ( function_Exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
			}

			WP_Filesystem();
			global $wp_filesystem;

			$field_data = $wp_filesystem->get_contents( ARF_HCAPTCHA_CORE_DIR . '/arf_hcaptcha_field_data.json' );

			$field_data_array = json_decode( $field_data );

			$arf_editor_unique_id = 1221;

			$hcaptcha_field_data_obj = $field_data_array->field_data->hcaptcha;

			$field_opt_arr = array(
				'hcaptcha' => array(
					'requiredmsg' => 1,
					'fielddescription' => 2,
				),
			);

			$hcaptcha_public_key = isset( $arflitesettings->hcaptcha_public_key ) ? $arflitesettings->hcaptcha_public_key : '';

			$field_order_hcaptcha = isset( $field_opt_arr['hcaptcha'] ) ? $field_opt_arr['hcaptcha'] : '';

			$onclick_func = $arformsmain->arforms_is_pro_active() ? 'arf_close_field_option_popup()' : 'arflite_close_field_option_popup()';

			$hcaptcha_html  = array(
				'hcaptcha' => "<div class='arf_inner_wrapper_sortable arfmainformfield edit_form_item arffieldbox ui-state-default 1  arf1columns single_column_wrapper' data-id='arf_editor_main_row_{arf_editor_index_row}'>

                <div class='sortable_inner_wrapper edit_field_type_hcaptcha' inner_class='arf_1col' id='arfmainfieldid_{arf_field_id}'>
                    <div id='arf_field_{arf_field_id}' class='arfformfield control-group arfmainformfield top_container  arfformfield  arf_field_{arf_field_id}'>
                        
                        <div class='fieldname-row arf_dig_display_block' >
                            <div class='fieldname'></div>
                        </div>
                        <div class='arf_fieldiconbox arf_hcaptcha_fieldiconbox' data-field_id='{arf_field_id}'>" . $positioned_field_icons['hcaptcha'] . "</div> 
                        <div class='controls input-field'>
                            <img class='arf_hcaptcha_editor_img_default' src='" . ARF_HCAPTCHA_IMAGE_DIR . "/hcaptcha_field_image.jpg' >
                            <input type='hidden'  id='arf_hcaptcha_public_key' value='" . $arflitesettings->hcaptcha_public_key . " '/>
                            <input id='field_{arf_unique_key}_" . $arf_editor_unique_id . "' name='item_meta[{arf_field_id}]' type='hidden' class='arf_hcaptcha_output arf_hcaptcha_float_left'>
                            <div class='arf_field_description' id='field_description_{arf_field_id}'></div>
                            <div class='help-block'></div>
                            <input type='hidden' name='arf_field_data_{arf_field_id}' id='arf_field_data_{arf_field_id}' value='" . htmlspecialchars( json_encode( $hcaptcha_field_data_obj ) ) . "' data-field_options='" . json_encode( $field_order_hcaptcha ) . "'/>
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
			$arflite_new_field_array_materialize = array_merge( $arflite_new_field_array_materialize, $hcaptcha_html );
			return $arflite_new_field_array_materialize;
		}



		/**
		 * arf_hcaptcha_field_to_frontend()
		 *
		 * @param $return_string, $form, $field_name, $arflite_data_uniq_id, $field
		 * @param $field_tooltip, $field_description, $OFData, $inputStyle, $arf_main_label
		 * @return $return_string
		 */
		function arf_hcaptcha_field_to_frontend( $return_string, $form, $field_name, $arf_data_uniq_id, $field, $field_tooltip, $field_description, $OFData, $inputStyle, $arf_main_label ) {

			global $wpdb, $armainhelper, $arfieldhelper, $arformscontroller, $recordcontroller, $arfieldcontroller, $arflitesettings;

			$form->form_css = ( $form->form_css );
			$formid = $form->id;
			$aweber_arr     = '';
			$aweber_arr     = $form->form_css;
			$newarr         = array();

			if ( '' != $aweber_arr ) {
				$arr = maybe_unserialize( $aweber_arr );
				foreach ( $arr as $k => $v ) {
					$newarr[ $k ] = $v;
				}
			}
			$inline_css_with_style_tag = '';
			$inline_css_without_style  = '';

			if ( 'hcaptcha' == $field['type'] ) {
				$description_style = ( isset( $field['field_width'] ) and $field['field_width'] != '' ) ? 'style="width:' . $field['field_width'] . 'px;"' : '';
			}

			$return_string .= '<div class="arfformfield">';
			$return_string .= '<div class="controls">';
				$return_string .= '<div id="hcaptcha_front_image_' . $formid . '_' . $arf_data_uniq_id . '" class="h-captcha" data-sitekey=' . $arflitesettings->hcaptcha_public_key . '>';

				$return_string .= '</div>';
				$return_string .= ( isset( $field['description'] ) && $field['description'] != '' ) ? '<div class="arf_field_description formdescription_style arf_heading_description" ' . $description_style . '>' . $field['description'] . '</div>' : '';
			$return_string .= '</div>';
			$return_string .= '</div>';

			return $return_string;
		}



		/**
		 * arf_display_hcaptcha_field_in_editor()
		 *
		 * @param $field
		 * @return $field
		 */
		function arf_display_hcaptcha_field_in_editor( $field ) {

			$field_name = 'item_meta[' . $field['id'] . ']';
			if ( 'hcaptcha' == $field['type'] ) {
				$arf_editor_unique_id = 1221;
				?>
				<img class="arf_hcaptcha_editor_img_default" src="<?php echo esc_url( ARF_HCAPTCHA_IMAGE_DIR . '/hcaptcha_field_image.jpg' ); ?>" />

				<input id='field_<?php echo esc_html( $field['field_key'] ) . '_' . esc_html( $arf_editor_unique_id ); ?>' name="item_meta[<?php echo esc_html( $field['id'] ); ?>]" type='hidden' class='arf_hcaptcha_output arf_hcaptcha_float_left' value='<?php echo esc_attr( $field['default_value'] ); ?>'/>
				<?php
			}
		}



		/**
		 * arflite_add_hcaptcha_js()
		 * to add hcaptcha js in arforms lite version
		 *
		 * @return void
		 */
		function arflite_add_hcaptcha_js() {

			global $arfliteversion, $arflite_forms_loaded, $arflitesettings;

			if ( is_array( $arflite_forms_loaded ) ) {

				foreach ( $arflite_forms_loaded as $form ) {

					if ( ! is_object( $form ) ) {
						continue;
					}

					$loaded_field = isset( $form->options['arf_loaded_field'] ) ? $form->options['arf_loaded_field'] : array();

					if ( in_array( 'hcaptcha', $loaded_field ) || in_array( 'hcaptcha', $loaded_field ) ) {

						if ( ! empty( $arflitesettings->hcaptcha_public_key ) && ! empty( $arflitesettings->hcaptcha_private_key ) ) {

							wp_register_script( 'arf_hcaptcha_js', ARF_HCAPTCHA_JS_DIR . '/arf_hcaptcha.js', array() );
							wp_enqueue_script( 'arf_hcaptcha_js' );

							wp_register_script( 'hcaptcha-js', 'https://js.hcaptcha.com/1/api.js?onload=render_arf_hcaptcha&render=explicit', array( 'jquery' ) );
							wp_enqueue_script( 'hcaptcha-js' );

						}
					}
				}
			}
		}



		/**
		 * arf_add_hcaptcha_js()
		 * to add hcaptcha js in arforms pro version
		 *
		 * @return void
		 */
		function arf_add_hcaptcha_js() {

			global $arfforms_loaded, $arflitesettings, $arfversion, $arf_form_all_footer_js, $arforms_hcaptcha;

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

					$form->options = maybe_unserialize( $form->options );

					if ( in_array( 'hcaptcha', $loaded_field ) ) {
						if ( ! empty( $arflitesettings->hcaptcha_public_key ) && ! empty( $arflitesettings->hcaptcha_private_key ) ) {

							wp_register_script( 'arf_hcaptcha_js', ARF_HCAPTCHA_JS_DIR . '/arf_hcaptcha.js', array(  ) );
							wp_enqueue_script( 'arf_hcaptcha_js' );

							wp_register_script( 'hcaptcha-js', 'https://js.hcaptcha.com/1/api.js?onload=render_arf_hcaptcha&render=explicit', array( 'jquery' ) );
							wp_enqueue_script( 'hcaptcha-js' );

						}
					}
				}
			}
		}



		/**
		 * arf_add_hcaptcha_inputs()
		 *
		 * @param $arf_form, $form, $arf_data_uniq_id, $arfbrowser_name, $browser_info
		 * @return $arf_form
		 */
		function arf_add_hcaptcha_inputs( $arf_form, $form, $arf_data_uniq_id, $arfbrowser_name, $browser_info ) {

			global $arflitesettings, $arf_form_all_footer_js, $wpdb, $MdlDb, $arf_tc_captcha, $tbl_arf_fields;
			$enable_hcaptcha = false;

			$form_id = $form->id;

			$is_exists = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE form_id = %d AND type = %s', $tbl_arf_fields, $form_id, 'hcaptcha' ) );

			if ( $is_exists > 0 ) {
				if ( ! empty( $arflitesettings->hcaptcha_public_key ) && ! empty( $arflitesettings->hcaptcha_private_key ) ) {
					$dsize = 'normal';
					$arf_form .= '<input type="hidden" id="arf_hcaptcha_theme" value="' . $arflitesettings->hcaptcha_theme . '" />';
					$arf_form .= '<input type="hidden" id="arf_hcaptcha_public_key" value="' . $arflitesettings->hcaptcha_public_key . '" />';
				}
			}

			return $arf_form;
		}



		/**
		 * hcaptcha_validate_form_outside()
		 * to validate form for normal submission
		 *
		 * @param $flag, $form
		 * @return $flag
		 */
		function hcaptcha_validate_form_outside( $flag, $form ) {
			global $wpdb, $arformsmain, $tbl_arf_fields;

			$form_submit_type = $arformsmain->arforms_get_settings( 'form_submit_type', 'general_settings' );

			$form_id = $form->id;

			if ( 1 == $form_submit_type ) {
				return $flag;
			}

			$is_exists = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE form_id=%d AND type=%s', $tbl_arf_fields, $form_id, 'hcaptcha' ) );

			if ( $is_exists > 0 ) {
				return true;
			}

			return $flag;
		}




		/**
		 * hcaptcha_ajax_form_validation()
		 * to validate form for ajax submission
		 *
		 * @param $arf_errors, $form_id, $values, $fields
		 * @return $arf_erros
		 */
		function hcaptcha_ajax_form_validation( $arf_errors, $form_id, $values, $fields ) {
			global $arflitesettings, $fields, $wpdb, $tbl_arf_fields, $arformsmain;

			$arforms_all_settings = $arformsmain->arforms_global_option_data();
			$arflitesettings = json_decode( json_encode( $arforms_all_settings['general_settings'] ) );

			$results = $wpdb->get_row( $wpdb->prepare( 'SELECT field_options, type FROM %i WHERE form_id = %d and type = %s ', $tbl_arf_fields, $form_id, 'hcaptcha' ) );
			$field_options = isset( $results->field_options ) ? json_decode( $results->field_options, true ) : array();

			if ( empty( $results ) ) {
				return $arf_errors;
			}

			$captcha_failed_msg = ! empty( $arflitesettings->hcaptcha_message ) ? $arflitesettings->hcaptcha_message : esc_html__( 'Invalid hCaptcha. Please try again', 'arforms-form-builder' );
			$privkey = $arflitesettings->hcaptcha_private_key;
			$pubkey = $arflitesettings->hcaptcha_public_key;

			$hcaptcha_valid = $values['h-captcha-response'];

			$captcha_field = ! empty( $hcaptcha_valid ) ? $hcaptcha_valid : '';

			if ( empty( $captcha_field ) ) {
				$arf_errors['arf_message_error'] = $field_options['blank'];

				return $arf_errors;
			} else {
				$verify_url = 'https://api.hcaptcha.com/siteverify';

				$args = array(
					'timeout' => 4500,
					'body' => array(
						'secret' => $privkey,
						'response' => $captcha_field,
					),
				);

				$resp = wp_remote_post( $verify_url, $args );

				if ( is_wp_error( $resp ) ) {
					$arf_errors['arf_message_error'] = $resp->get_error_message();
				}

				$resp_body = json_decode( $resp['body'] );

				if ( ! empty( $resp_body->success ) && 1 != $resp_body->success ) {

					$arf_errors['arf_message_error'] = $captcha_failed_msg;

				}
			}

			return $arf_errors;
		}



		/**
		 * arf_hide_multicolumn_for_hcaptcha()
		 *
		 * @param $display_multicolumn, $field
		 * @return $display_multicolumn
		 */
		function arf_hide_multicolumn_for_hcaptcha( $display_multicolumn, $field ) {

			if ( isset( $field ) && 'hcaptcha' == $field['type'] ) {
				$display_multicolumn = false;
			}

			return $display_multicolumn;
		}



		/**
		 * arforms_remove_hcaptcha_captcha_field()
		 */
		function arforms_remove_hcaptcha_captcha_field( $fields, $form_id ) {
			global $wpdb, $tbl_arf_fields, $arfliteformcontroller;

			$is_exists = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE form_id=%d AND type=%s', $tbl_arf_fields, $form_id, 'hcaptcha' ) );

			if ( $is_exists > 0 ) {
				$getKey = $arfliteformcontroller->arfliteSearchArray( 'hcaptcha', 'type', json_decode( wp_json_encode( $fields ), true ) );

				if ( '' !== $getKey ) {
					unset( $fields[ $getKey ] );
					array_values( $fields );
				}
			}

			return $fields;
		}



		/**
		 * arf_hcaptcha_inline_style()
		 * to add inline css
		 */
		function arf_hcaptcha_inline_style() {
			$arf_hcaptcha_style = '.edit_field_type_hcaptcha .arf_fieldiconbox{ width: 84px; }';

			if ( $this->is_arforms_support() ) {
				wp_add_inline_style( 'arfdisplaycss_editor', $arf_hcaptcha_style );
			} else {
				wp_add_inline_style( 'arflitedisplaycss_editor', $arf_hcaptcha_style );
			}
		}
	}

	global $arforms_hcaptcha;
	$arforms_hcaptcha = new ARF_hcaptcha();
}

?>
