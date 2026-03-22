<?php

global $arfsiteurl;
$arfsiteurl = home_url();
if ( is_ssl() && ( ! preg_match( '/^https:\/\/.*\..*$/', $arfsiteurl ) || ! preg_match( '/^https:\/\/.*\..*$/', WP_PLUGIN_URL ) ) ) {
	$arfsiteurl = str_replace( 'http://', 'https://', $arfsiteurl );
	define( 'ARF_G_RECAPTCHA_URL', str_replace( 'http://', 'https://', WP_PLUGIN_URL . '/arforms-form-builder' ) );
} else {
	define( 'ARF_G_RECAPTCHA_URL', WP_PLUGIN_URL . '/arforms-form-builder' );
}

if ( ! defined( 'ARF_G_RECATPCHA_DIR' ) ) {
	define( 'ARF_G_RECATPCHA_DIR', WP_PLUGIN_DIR . '/arforms-form-builder' );
}

class ARForms_Google_Captcha {

    function __construct() {

        add_action( 'arforms_load_captcha_settings', array( $this, 'arforms_render_gcaptcha_settings') );

        //if ( $this->arforms_check_googlecaptcha_module_activation() ) {
            if( $this->gc_arformslite_version_compatible() ){
                add_action('arflite_add_form_additional_input_settings', array( $this, 'arf_enable_grecaptcha_v3'),10,2);

                add_filter('arflite_save_form_options_outside', array($this, 'arf_save_recaptcha_options'), 10, 2);

                add_action('wp_footer', array( $this, 'arflite_add_grecaptcha_js') );

                add_filter( 'arflite_additional_form_content_outside', array( $this, 'arflite_add_captcha_v3_inputs'), 10, 5 );

                add_action( 'arflite_update_global_setting_outside', array( $this, 'arf_update_recaptcha_global_option'),13,2);
            }

            //add_action('admin_init',array($this,'upgrade_arformsgooglecaptcha_data'), 9);

            add_action('arf_add_form_additional_input_settings', array($this,'arf_enable_grecaptcha_v3'), 10, 2);

            add_filter('arf_save_form_options_outside', array($this, 'arf_save_recaptcha_options'), 10, 2);

            add_action( 'wp_footer', array($this, 'arf_add_grecaptcha_js') );

            add_filter( 'arf_additional_form_content_outside', array( $this, 'arf_add_captcha_v3_inputs'), 10, 5 );

            add_filter('arf_update_global_setting_outside',  array($this, 'arf_update_recaptcha_global_option'),13,2);

        //}

    }

    function arforms_render_gcaptcha_settings(){
        global $arforms_modules, $arflitesettings, $arformsmain;
        
        $arforms_all_settings = $arformsmain->arforms_global_option_data();
        $arflitesettings = json_decode( wp_json_encode( $arforms_all_settings['general_settings'] ) );

        $google_recaptcha_theme = '';
        $google_rclang			= '';
        $selected_list_label   = '';

        $captcha_theme = array(
            'light' => __( 'Light', 'arforms-form-builder' ),
            'dark'  => __( 'Dark', 'arforms-form-builder' ),
        );
        $rc_default_theme = 'light';

        $rc_default_lang       = 'en';
        $rc_default_lang_label = __( 'English (US)', 'arforms-form-builder' );
        $rclang                = array();
        $rclang['en']          = __( 'English (US)', 'arforms-form-builder' );
        $rclang['ar']          = __( 'Arabic', 'arforms-form-builder' );
        $rclang['bn']          = __( 'Bengali', 'arforms-form-builder' );
        $rclang['bg']          = __( 'Bulgarian', 'arforms-form-builder' );
        $rclang['ca']          = __( 'Catalan', 'arforms-form-builder' );
        $rclang['zh-CN']       = __( 'Chinese(Simplified)', 'arforms-form-builder' );
        $rclang['zh-TW']       = __( 'Chinese(Traditional)', 'arforms-form-builder' );
        $rclang['hr']          = __( 'Croatian', 'arforms-form-builder' );
        $rclang['cs']          = __( 'Czech', 'arforms-form-builder' );
        $rclang['da']          = __( 'Danish', 'arforms-form-builder' );
        $rclang['nl']          = __( 'Dutch', 'arforms-form-builder' );
        $rclang['en-GB']       = __( 'English (UK)', 'arforms-form-builder' );
        $rclang['et']          = __( 'Estonian', 'arforms-form-builder' );
        $rclang['fil']         = __( 'Filipino', 'arforms-form-builder' );
        $rclang['fi']          = __( 'Finnish', 'arforms-form-builder' );
        $rclang['fr']          = __( 'French', 'arforms-form-builder' );
        $rclang['fr-CA']       = __( 'French (Canadian)', 'arforms-form-builder' );
        $rclang['de']          = __( 'German', 'arforms-form-builder' );
        $rclang['gu']          = __( 'Gujarati', 'arforms-form-builder' );
        $rclang['de-AT']       = __( 'German (Autstria)', 'arforms-form-builder' );
        $rclang['de-CH']       = __( 'German (Switzerland)', 'arforms-form-builder' );
        $rclang['el']          = __( 'Greek', 'arforms-form-builder' );
        $rclang['iw']          = __( 'Hebrew', 'arforms-form-builder' );
        $rclang['hi']          = __( 'Hindi', 'arforms-form-builder' );
        $rclang['hu']          = __( 'Hungarian', 'arforms-form-builder' );
        $rclang['id']          = __( 'Indonesian', 'arforms-form-builder' );
        $rclang['it']          = __( 'Italian', 'arforms-form-builder' );
        $rclang['ja']          = __( 'Japanese', 'arforms-form-builder' );
        $rclang['kn']          = __( 'Kannada', 'arforms-form-builder' );
        $rclang['ko']          = __( 'Korean', 'arforms-form-builder' );
        $rclang['lv']          = __( 'Latvian', 'arforms-form-builder' );
        $rclang['lt']          = __( 'Lithuanian', 'arforms-form-builder' );
        $rclang['ms']          = __( 'Malay', 'arforms-form-builder' );
        $rclang['ml']          = __( 'Malayalam', 'arforms-form-builder' );
        $rclang['mr']          = __( 'Marathi', 'arforms-form-builder' );
        $rclang['no']          = __( 'Norwegian', 'arforms-form-builder' );
        $rclang['fa']          = __( 'Persian', 'arforms-form-builder' );
        $rclang['pl']          = __( 'Polish', 'arforms-form-builder' );
        $rclang['pt']          = __( 'Portuguese', 'arforms-form-builder' );
        $rclang['pt-BR']       = __( 'Portuguese (Brazil)', 'arforms-form-builder' );
        $rclang['pt-PT']       = __( 'Portuguese (Portugal)', 'arforms-form-builder' );
        $rclang['ro']          = __( 'Romanian', 'arforms-form-builder' );
        $rclang['ru']          = __( 'Russian', 'arforms-form-builder' );
        $rclang['sr']          = __( 'Serbian', 'arforms-form-builder' );
        $rclang['sk']          = __( 'Slovak', 'arforms-form-builder' );
        $rclang['sl']          = __( 'Slovenian', 'arforms-form-builder' );
        $rclang['es']          = __( 'Spanish', 'arforms-form-builder' );
        $rclang['es-149']      = __( 'Spanish (Latin America)', 'arforms-form-builder' );
        $rclang['sv']          = __( 'Swedish', 'arforms-form-builder' );
        $rclang['ta']          = __( 'Tamil', 'arforms-form-builder' );
        $rclang['te']          = __( 'Telugu', 'arforms-form-builder' );
        $rclang['th']          = __( 'Thai', 'arforms-form-builder' );
        $rclang['tr']          = __( 'Turkish', 'arforms-form-builder' );
        $rclang['uk']          = __( 'Ukrainian', 'arforms-form-builder' );
        $rclang['ur']          = __( 'Urdu', 'arforms-form-builder' );
        $rclang['vi']          = __( 'Vietnamese', 'arforms-form-builder' );

 
        if( $arforms_modules->arforms_is_module_activate( 'arforms_gcaptcha' ) ): ?>
        <tr class="arfmainformfield" valign="top" style="display: table-row">
            <td class="lbltitle" colspan="2"><?php echo esc_html__( 'reCAPTCHA Configuration', 'arforms-form-builder' ); ?>&nbsp;</td>
        </tr>
        <tr class="arfmainformfield" valign="top" style="display: table-row">
            <td colspan="2" style="padding-left:0px; padding-bottom:30px;padding-top:15px;">
                <label class="lblsubtitle"><?php echo stripslashes( esc_html__( 'reCAPTCHA requires an API key, consisting of a "site" and a "private" key. You can sign up for a', 'arforms-form-builder' ) ); ?>&nbsp;&nbsp;<a href="https://www.google.com/recaptcha/" target="_blank" class="arlinks"><b><?php echo esc_html__( 'free reCAPTCHA key', 'arforms-form-builder' ); //phpcs:ignore ?></b></a>.</label>
            </td>
        </tr>
        <tr class="arfmainformfield" valign="top" style="display: table-row">
            <td class="tdclass email-setting-label-td" width="18%">
                <label class="lblsubtitle"><?php echo esc_html__( 'Site Key', 'arforms-form-builder' ); ?></label>
            </td>
            <td>
                <input type="text" name="frm_pubkey" id="frm_pubkey" class="txtmodal1" size="42" value="<?php echo esc_attr( $arflitesettings->pubkey ); ?>" />
            </td>
        </tr>
        <tr class="arfmainformfield" valign="top" style="display: table-row">
            <td class="tdclass">
                <label class="lblsubtitle"><?php echo esc_html__( 'Secret Key', 'arforms-form-builder' ); ?></label>
            </td>
            <td>
                <input type="text" name="frm_privkey" id="frm_privkey" class="txtmodal1" size="42" value="<?php echo esc_attr( $arflitesettings->privkey ); ?>" />
            </td>
        </tr>
        <tr class="arfmainformfield" valign="top" style="display: table-row">
            <td class="tdclass">
                <label class="lblsubtitle"><?php echo esc_html__( 'reCAPTCHA Theme', 'arforms-form-builder' ); ?></label>
            </td>
            <td class="email-setting-input-td">
                <?php
                foreach ( $captcha_theme as $theme_value => $theme_name ) {
                    if ( $arflitesettings->re_theme == $theme_value ) {
                        $rc_default_theme    = esc_attr( $theme_value );
                        $selected_list_label = $theme_name;
                    }
                    $google_recaptcha_theme .= '<li class="arf_selectbox_option" data-value="' . esc_attr( $rc_default_theme ) . '" data-label="' . esc_attr( $theme_name ) . '">' . $theme_name . '</li>';
                }
                ?>
                <div class="sltstandard arffloat-none">
                    <input id="frm_re_theme" name="frm_re_theme" value="<?php echo esc_attr( $rc_default_theme ); ?>" type="hidden" class="frm-dropdown frm-pages-dropdown">
                    <dl class="arf_selectbox width400px" data-name="frm_re_theme" data-id="frm_re_theme">
                        <dt><span><?php echo esc_html( $selected_list_label ); ?></span>
                        <svg viewBox="0 0 2000 1000" width="15px" height="15px">
                        <g fill="#000">
                        <path d="M1024 320q0 -26 -19 -45t-45 -19h-896q-26 0 -45 19t-19 45t19 45l448 448q19 19 45 19t45 -19l448 -448q19 -19 19 -45z"></path>
                        </g>
                        </svg>
                        </dt>
                        <dd>
                            <ul class="field_dropdown_menu field_dropdown_list_menu display-none-cls" data-id="frm_re_theme">
                                <?php
                                echo wp_kses(
                                    $google_recaptcha_theme,
                                    array(
                                        'li' => array(
                                            'class'      => array(),
                                            'data-label' => array(),
                                            'data-value' => array(),
                                        ),
                                    )
                                );
                                ?>
                            </ul>
                        </dd>
                    </dl>
                </div>
            </td>
        </tr>
        <tr class="arfmainformfield" valign="top" style="display: table-row">
            <td class="tdclass">
                <label class="lblsubtitle"><?php echo esc_html__( 'reCAPTCHA Language', 'arforms-form-builder' ); ?></label>
            </td>

            <td class="email-setting-input-td">
                <div class="sltstandard arfrecaptchalang">
                    <?php
                    foreach ( $rclang as $lang => $lang_name ) {
                        if ( $arflitesettings->re_lang == $lang ) {
                            $rc_default_lang    = esc_attr( $lang );
                            $rc_default_lang_label = $lang_name;
                        }
                        $google_rclang .= '<li class="arf_selectbox_option" data-value="' . esc_attr( $lang ) . '" data-label="' . esc_attr( $lang_name ) . '">' . $lang_name . '</li>';
                    }
                    ?>
                    <input id="frm_re_lang" name="frm_re_lang" value="<?php echo esc_attr( $rc_default_lang ); ?>" type="hidden" class="frm-dropdown frm-pages-dropdown">
                    <dl class="arf_selectbox width400px" data-name="frm_re_lang" data-id="frm_re_lang">
                        <dt><span><?php echo esc_html( $rc_default_lang_label ); ?></span>
                        <svg viewBox="0 0 2000 1000" width="15px" height="15px">
                        <g fill="#000">
                        <path d="M1024 320q0 -26 -19 -45t-45 -19h-896q-26 0 -45 19t-19 45t19 45l448 448q19 19 45 19t45 -19l448 -448q19 -19 19 -45z"></path>
                        </g>
                        </svg>
                        </dt>
                        <dd>
                            <ul class="field_dropdown_menu field_dropdown_list_menu display-none-cls" data-id="frm_re_lang">
                                <?php
                                echo wp_kses(
                                    $google_rclang,
                                    array(
                                        'li' => array(
                                            'class'      => array(),
                                            'data-value' => array(),
                                            'data-label' => array(),
                                        ),
                                    )
                                );
                                ?>
                            </ul>
                        </dd>
                    </dl>
                </div>
            </td>
        </tr>
        <tr class="arfmainformfield" valign="top" style="display: table-row">
            <td class="tdclass" >
                <label class="lblsubtitle"><?php echo esc_html__( 'reCAPTCHA Failed Message', 'arforms-form-builder' ); ?>&nbsp;&nbsp;<span style="vertical-align:middle" class="arfglobalrequiredfield">*</span></label>
            </td>
            
            <td>				
                <input type="text" class="txtmodal1" value="<?php echo esc_attr( $arflitesettings->recaptcha_value ); ?>" id="arfvaluerecaptcha" name="frm_recaptcha_value" />
                <div class="arferrmessage" id="arferrorsubmitvalue" style="display:none;"><?php echo esc_html__( 'This field cannot be blank.', 'arforms-form-builder' ); ?></div>
            </td>
        </tr>
        <tr class="arfmainformfield" valign="top" style="display: table-row">
            <td colspan="2"><div  class="dotted_line dottedline-width96"></div></td>
        </tr>

        <?php endif;

    }

    function arf_add_captcha_v3_inputs( $arf_form, $form, $arf_data_uniq_id, $arfbrowser_name, $browser_info ){

        $enable_captcha_v3 = false;
         if( isset( $form ) && !empty( $form->options['arf_enable_recaptcha'] ) ){
             $enable_captcha_v3 = ( 1 == $form->options['arf_enable_recaptcha'] ) ? true : false;
         }
 
         if( $enable_captcha_v3 ){
             global $arfsettings, $arf_form_all_footer_js;
             if( !empty( $arfsettings->pubkey ) && !empty( $arfsettings->privkey ) ){
 
                 $re_lang = !empty( $arfsettings->re_lang ) ? $arfsettings->re_lang : 'en';
                 $re_theme = !empty( $arfsettings->re_theme ) ? $arfsettings->re_theme : 'light';
                 $dsize = 'normal';
 
                 $arf_form .= '<input type="hidden" id="arf_recaptcha_v3_public_key" value="' . $arfsettings->pubkey . '" />';
                 $arf_form .= '<input type="hidden" id="arf_recaptcha_v3_private_key" value="' . $arfsettings->privkey . '" />';
                 $arf_form .= '<input type="hidden" id="arf_recaptcha_v3_theme" value="' . $re_theme . '" />';
                 $arf_form .= '<input type="hidden" id="arf_recaptcha_v3_lang" value="' . $re_lang . '" />';
                 $arf_form .= '<input type="hidden" name="arf_captcha_' . $arf_data_uniq_id .'" id="arf_captcha_' . $arf_data_uniq_id .'" class="arf_required" value="" />';
 
                 $arf_form .= '<script type="text/javascript">';
                     $arf_form .= 'window.addEventListener("DOMContentLoaded", function() { (function($) {
                                 jQuery(document).ready(function (){
                             if( !window["arf_recaptcha_v3"] ){
                                 window["arf_recaptcha_v3"] = {};
                             }
                             
                             window["arf_recaptcha_v3"]["arf_captcha_'.$arf_data_uniq_id.'"] = {
                                 size : "' . $dsize . '"
                             };
                             
                             }); })(jQuery); });';
                 $arf_form .= '</script>';
             }
         }
 
         return $arf_form;
 
    }

    function arf_enable_grecaptcha_v3($id,$values){
        global $arformsmain;

        $gcaptcha_options_data = $arformsmain->arforms_get_settings( ['pubkey', 'privkey'], 'general_settings' );
        $sitekey = $gcaptcha_options_data['pubkey'];
        $secretkey = $gcaptcha_options_data['privkey'];
    ?>
    <div class="arf_accordion_container_row_separator"></div>
    <div class="arf_accordion_container_row arf_padding">
        <div class="arf_accordion_outer_title"><?php echo addslashes(esc_html__('Google reCaptcha (v3)', 'arforms-form-builder')); ?></div>
    </div>
    <div class="arf_accordion_container_row arf_half_width">
        <div class="arf_accordion_inner_title arf_two_row_text" style="padding-right: 0; width: 42%;"><?php echo addslashes(esc_html__('Enable reCaptcha', 'arforms-form-builder')); ?></div>
        <div class="arf_accordion_content_container" style="width: 58%;">
            <div class="arf_float_right" style="margin-right:5px;">
                <label class="arf_js_switch_label">
                    <span class=""><?php esc_html_e('No','arforms-form-builder'); ?>&nbsp;</span>
                </label>
                <span class="arf_js_switch_wrapper">
                   <input type="checkbox" class="js-switch chkstanard" <?php echo(empty($sitekey) && empty($secretkey)) ? 'disabled="disabled"' : '' ?> name="options[arf_enable_recaptcha]" id="arf_enable_recaptcha" <?php !empty( $values['arf_enable_recaptcha'] ) ? checked($values['arf_enable_recaptcha'],1) : ''; ?> value="1">
                    <span class="arf_js_switch"></span>
                </span>
                <label class="arf_js_switch_label">
                    <span class="">&nbsp;<?php esc_html_e('Yes','arforms-form-builder'); ?></span>
                </label>
            </div>
        </div>
    </div>

    <?php
    }

    function arf_save_recaptcha_options($options,$values){

        if( isset($_REQUEST['arfaction'] ) && 'preview' == $_REQUEST['arfaction'] ){
            return $options;
        }
        $options['arf_enable_recaptcha'] = isset($values['options']['arf_enable_recaptcha']) ? $values['options']['arf_enable_recaptcha'] : '';

        return $options;
    }

    function arf_add_grecaptcha_js(){

        global $arfforms_loaded,$arfsettings,$arfversion, $arf_form_all_footer_js;

        if(is_array($arfforms_loaded)){

	        foreach ($arfforms_loaded as $form) {

	            if (!is_object($form)){
	                continue;
	            }

	            $form->options = maybe_unserialize($form->options);

	            if(isset($form->options['arf_enable_recaptcha']) && '1' == $form->options['arf_enable_recaptcha']){

	                if(!empty($arfsettings->pubkey) && !empty($arfsettings->privkey)){


	                    wp_enqueue_script( 'arf-google-recaptcha-front', ARFLITEURL.'/js/arf_recaptcha_front.js', array(), $arfversion );
	                    
	                    $arf_google_recaptcha_url = add_query_arg(
	                        array(
	                            'hl'=> $arfsettings->re_lang,
	                            'render' => $arfsettings->pubkey,
	                            'onload'=>'render_arf_captcha_v3'
	                        ),
	                        'https://www.google.com/recaptcha/api.js'
	                    );
                        
	                    wp_enqueue_script('arf-google-recaptcha-v3',$arf_google_recaptcha_url, array('jquery'), $arfversion);
	                }
	            }
	        }
	    }
    }

    function arflite_add_grecaptcha_js(){

        global $arfliteversion, $arflite_forms_loaded, $arformsmain;

        $gcaptcha_options_data = $arformsmain->arforms_get_settings( ['re_lang', 're_theme','pubkey', 'privkey'], 'general_settings' );

        $re_theme = $gcaptcha_options_data['re_theme'];
        $re_lang = $gcaptcha_options_data['re_lang'];
        $sitekey = $gcaptcha_options_data['pubkey'];
        $secretkey = $gcaptcha_options_data['privkey'];

        if(is_array($arflite_forms_loaded)){

        	foreach ($arflite_forms_loaded as $form) {

	            if (!is_object($form)){
	                continue;
	            }
	            
	            $form->options = maybe_unserialize($form->options);

	            if(isset($form->options['arf_enable_recaptcha']) && '1' == $form->options['arf_enable_recaptcha']){

	                if(!empty($sitekey) && !empty($secretkey)){

	                    wp_enqueue_script( 'arf-google-recaptcha-front', ARFLITEURL .'/js/arf_recaptcha_front.js', array(), $arfliteversion );
	        
	                    $arflite_google_recaptcha_url = add_query_arg(
	                        array(
	                            'hl'=> $re_theme,
	                            'render' => $sitekey,
	                            'onload'=>'render_arflite_captcha_v3'
	                        ),
	                        'https://www.google.com/recaptcha/api.js'
	                    );

	                    wp_enqueue_script('arflite-google-recaptcha-v3',$arflite_google_recaptcha_url, array('jquery'), $arfliteversion);
	                }
            	}
        	}
        }
    }

    function arflite_add_captcha_v3_inputs( $arf_form, $form, $arf_data_uniq_id, $arfbrowser_name, $browser_info ){
        $enable_captcha_v3 = false;
        if( isset( $form ) && !empty( $form->options['arf_enable_recaptcha'] ) ){
            $enable_captcha_v3 = ( 1 == $form->options['arf_enable_recaptcha'] ) ? true : false;
        }

        if( $enable_captcha_v3 ){
            global $arf_form_all_footer_js, $arformsmain;

            $gcaptcha_options_data = $arformsmain->arforms_get_settings( ['re_lang', 're_theme','pubkey', 'privkey'], 'general_settings' );
            $re_theme = $gcaptcha_options_data['re_theme'];
            $re_lang = $gcaptcha_options_data['re_lang'];
            $sitekey = $gcaptcha_options_data['pubkey'];
            $secretkey = $gcaptcha_options_data['privkey'];

            if( !empty( $sitekey ) && !empty( $secretkey ) ){

                $dsize = 'normal';

                $arf_form .= '<input type="hidden" id="arflite_recaptcha_v3_public_key" value="' . $sitekey . '" />';
                $arf_form .= '<input type="hidden" id="arflite_recaptcha_v3_private_key" value="' . $secretkey . '" />';
                $arf_form .= '<input type="hidden" id="arflite_recaptcha_v3_theme" value="' . $re_theme . '" />';
                $arf_form .= '<input type="hidden" id="arflite_recaptcha_v3_lang" value="' . $re_lang . '" />';
                $arf_form .= '<input type="hidden" name="arflite_captcha_' . $arf_data_uniq_id .'" id="arflite_captcha_' . $arf_data_uniq_id .'" class="arf_required" value="" />';

                $arf_form .= '<script type="text/javascript">';
                    $arf_form .= 'window.addEventListener("DOMContentLoaded", function() { (function($) {
                                jQuery(document).ready(function (){
                            if( !window["arflite_recaptcha_v3"] ){
                                window["arflite_recaptcha_v3"] = {};
                            }
                            
                            window["arflite_recaptcha_v3"]["arflite_captcha_'.$arf_data_uniq_id.'"] = {
                                size : "' . $dsize . '"
                            };
                            
                            }); })(jQuery); });';
                $arf_form .= '</script>';

            }
        }

        return $arf_form;

    }

    function arf_update_recaptcha_global_option($opt_data_from_outside,$params){

        if( !empty( get_option('arf_check_recaptcha_key' ) ) ){
	        global $arfsettings;
	    	$siteKey = $arfsettings->pubkey;
	    	$arf_google_recaptcha_url = add_query_arg(
	            array(
	                'hl'=> 'en',
	                'render' => $siteKey,
	                'onload'=>'render_arf_captcha_v3'
	            ),
	            'https://www.google.com/recaptcha/api.js'
	        );

	    	$data = wp_remote_get( $arf_google_recaptcha_url, array(
	    		'timeout' => 5000
	    	) );
	    	
	    	if( !empty( $data['response']['code'] ) && 200 == $data['response']['code'] ){
	    		delete_option('display_update_key_notice');
	    		delete_option('arf_check_recaptcha_key');
	    	}
        } else {
        	delete_option( 'display_update_key_notice' );
        }

        return $opt_data_from_outside;
    }

    function get_arformslite_version(){
        $arflite_db_version = get_option('arflite_db_version' );

        return  isset( $arflite_db_version ) ? $arflite_db_version : 0;
    }

    function gc_arformslite_version_compatible(){
        if( version_compare( $this->get_arformslite_version(), '1.0', '>='  ) ){
            return true;
        }
    }

    function gc_arforms_version_compatible() {
        if (version_compare($this->get_arforms_version(), '4.1', '>='))
        {
            return true;
        } else {
            return false;
        }
    }


    function arforms_check_googlecaptcha_module_activation(){

        $is_google_recaptcha_module_activated = 0;
    }
}

global $arforms_google_captcha;
$arforms_google_captcha = new ARForms_Google_Captcha();