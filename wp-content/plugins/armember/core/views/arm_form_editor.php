<?php
global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_email_settings, $arm_social_feature, $arm_subscription_plans;
$form_color_schemes = $arm_member_forms->arm_form_color_schemes();
$form_gradient_scheme = $arm_member_forms->arm_default_button_gradient_color();
$formColorSchemes = isset($form_color_schemes) ? $form_color_schemes : array();
$formButtonSchemes = isset($form_gradient_scheme) ? $form_gradient_scheme : array();
$email_tools = $arm_email_settings->arm_get_optin_settings();
$activeSocialNetworks = $arm_social_feature->arm_get_active_social_options();
$thank_you_page_id = $arm_global_settings->arm_get_single_global_settings('thank_you_page_id', 0);
$all_global_settings = $arm_global_settings->global_settings;
$form_id = $show_registration_link = $show_forgot_password_link = 0;
$prefix_name = $form_styles = '';
$form_detail = $socialFieldsOptions = $submitBtnOptions = array();
$default_form_style = $arm_member_forms->arm_default_form_style();
$sectionPlaceholder = esc_html__('Drop Fields Here.', 'ARMember');
$opt_ins_feature = get_option('arm_is_opt_ins_feature');
$form_settings = array(
    'message' => esc_html__('Form has been successfully submitted.', 'ARMember'),
    'redirect_type' => 'page',
    'redirect_page' => '',
    'redirect_url' => ARM_HOME_URL,
    'auto_login' => 0,
    'show_rememberme' => 0,
    'show_registration_link' => 0,
    'show_forgot_password_link' => 0,
    'registration_link_margin' => array(),
    'forgot_password_link_margin' => array(),
    'enable_social_login' => 0,
    'social_networks' => array(),
    'social_networks_order' => array(),
    'social_networks_settings' => array(),
    'style' => $default_form_style,
    "date_format" => "d/m/Y",
    'show_time' => 0,
    'is_hidden_fields' => 0,
    'custom_css' => ''
);
$social_networks = $social_networks_order = $formSocialNetworksSettings = array();
foreach ($activeSocialNetworks as $sk => $so) {
    if ($so['status'] == 1) {
        $social_networks[] = $sk;
    }
}
if (!empty($_GET['form_id']) && $_GET['form_id'] != 0) {
    $form_id = !empty( $_REQUEST['form_id']) ? intval($_REQUEST['form_id']) : '';//phpcs:ignore
    //Remove fields for non-saved forms
    $delete_field_status = $wpdb->delete($ARMember->tbl_arm_form_field, array('arm_form_field_status' => 2));
    //Update field status for non-saved forms
    $update_field_status = $wpdb->update($ARMember->tbl_arm_form_field, array('arm_form_field_status' => '1'), array('arm_form_field_form_id' => $form_id));

    $form_detail = $arm_member_forms->arm_get_single_member_forms($form_id);
    $form_settings = (!empty($form_detail['arm_form_settings'])) ? maybe_unserialize($form_detail['arm_form_settings']) : array();
    $form_settings['style'] = (isset($form_settings['style'])) ? $form_settings['style'] : array();
    $form_settings['style'] = shortcode_atts($default_form_style, $form_settings['style']);
    $login_regex = "/template-login(.*?)/";
    $register_regex = "/template-registration(.*?)/";
    preg_match($login_regex, $form_detail['arm_form_slug'], $match_login);
    preg_match($register_regex, $form_detail['arm_form_slug'], $match_register);
    $reference_template = $form_detail['arm_ref_template'];
    if (isset($match_login[0]) && !empty($match_login[0])) {
        $form_detail['arm_form_type'] = 'login';
    } else if (isset($match_register[0]) && !empty($match_register[0])) {
        $form_detail['arm_form_type'] = 'registration';
    }
}
$get_action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action']) : '';
$get_set_name = isset( $_GET['set_name'] ) ? sanitize_text_field( $_GET['set_name']) : '';
$get_form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id']) : '';
if( 'new_form' == sanitize_text_field( $get_action ) && isset( $_GET['arm_form_type'] ) && 'edit_profile' == $_GET['arm_form_type'] ){
    $form_detail['arm_form_type'] = 'edit_profile';
    if( isset( $_GET['form_meta_fields'] ) && '' != $_GET['form_meta_fields'] ) {
        $form_meta_fields = explode( ',', sanitize_text_field($_GET['form_meta_fields']) );
    }
    if( empty($form_meta_fields) ){
        foreach( $form_detail['fields'] as $fkey => $fvalue ){
            if( 'submit' != $fvalue['arm_form_field_option']['type'] ){
                unset( $form_detail['fields'][$fkey] );
            }
        }
        $form_detail['fields'] = array_values( $form_detail['fields'] );
    } else {
        foreach( $form_detail['fields'] as $fkey => $fvalue ){
            if( $fvalue['arm_form_field_option']['type'] != 'submit' && !in_array( $fvalue['arm_form_field_option']['meta_key'], $form_meta_fields) ){
                unset( $form_detail['fields'][$fkey]);
            } else {
                if( $fvalue['arm_form_field_option']['type'] != 'submit' ){
                    $form_detail['fields'][$fkey]['arm_form_field_option']['default_field'] = 0;
                }
            }
        }
        $form_detail['fields'] = array_values( $form_detail['fields'] );
    }

    $form_detail_fields = array();

    foreach( $form_detail['fields'] as $ofk => $ofv ){
        if( $ofv['arm_form_field_option']['type'] != 'submit' ){
            $form_detail_fields[] = $ofv['arm_form_field_option']['meta_key'];
        }
    }
}

$isRegister = ($form_detail['arm_form_type'] == 'registration') ? true : false;
$isEditProfile = ( 'edit_profile' == $form_detail['arm_form_type'] ) ? true : false;
$formDateFormat = !empty($form_settings['date_format']) ? $form_settings['date_format'] : 'd/m/Y';
$showTimePicker = !empty($form_settings['show_time']) ? $form_settings['show_time'] : 0;
$setID = $form_detail['arm_set_id'];
$is_rtl = (isset($form_settings['style']['rtl']) && $form_settings['style']['rtl'] == '1') ? $form_settings['style']['rtl'] : '0';
//Form Classes
$form_class = '';
$formLayout = !empty($form_settings['style']['form_layout']) ? $form_settings['style']['form_layout'] : 'writer';

$form_class .= ' arm_form_' . $form_id;
$form_class .= ' arm_form_layout_' . $formLayout;
$form_class .= ' armf_layout_' . $form_settings['style']['label_position'];
$form_class .= ' armf_button_position_' . $form_settings['style']['button_position'];
$form_class .= ($form_settings['style']['label_hide'] == '1') ? ' armf_label_placeholder' : '';
$form_class .= ' armf_alignment_' . $form_settings['style']['label_align'];
$form_class .= ($is_rtl == '1') ? ' arm_form_rtl' : ' arm_form_ltr';
if (is_rtl()) {
    $form_class .= ' arm_rtl_site';
}
if($formLayout=='writer' || $formLayout=='writer_border'){
    $form_class .= ' arm_materialize_form';
}

if($formLayout=='writer')
{
    $form_class .= ' arm-default-form arm-material-style ';
}
else if($formLayout=='rounded')
{
    $form_class .= ' arm-default-form arm-rounded-style ';
}
else if($formLayout=='writer_border')
{
    $form_class .= ' arm-default-form arm--material-outline-style ';
}
else {
    $form_class .= ' arm-default-form ';
}
$arm_form_fields_for_cl = array();

$arm_form_fields_cl_omited_fields = array("password", "html", "file", "section", "avatar", "arm_captcha");

$arm_max_field_id = 0;
if(!empty($_REQUEST['is_clone']) && $_REQUEST['is_clone'] == 1) {
    $max_field_id = $wpdb->get_row( $wpdb->prepare("SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=%s AND TABLE_NAME = %s",DB_NAME,$ARMember->tbl_arm_form_field) );
    if(!empty($max_field_id))
    {
    	$arm_max_field_id = $max_field_id->AUTO_INCREMENT;
    }
}

$_SESSION['arm_file_upload_arr']['form_bg_file'] = "-";

$socialLoginBtns = '';
$otherForms = $otherFormIDs = array();
$mainSortableClass = 'arm_main_sortable';
if ($isRegister || $isEditProfile) {
    $otherForms[] = $form_detail;
} else {
    $mainSortableClass = 'arm_no_sortable arm_set_editor_ul';
    $otherForms = $arm_member_forms->arm_get_other_member_forms($setID);
}
$otherFormsValues = array_values($otherForms);
$firstForm = array_shift($otherFormsValues);
$form_settings = (!empty($firstForm['arm_form_settings'])) ? maybe_unserialize($firstForm['arm_form_settings']) : array();
$form_settings['style'] = (isset($form_settings['style'])) ? $form_settings['style'] : array();
$form_settings['style'] = shortcode_atts($default_form_style, $form_settings['style']);
$form_settings['style']['form_width'] = (!empty($form_settings['style']['form_width'])) ? $form_settings['style']['form_width'] : '600';
$form_settings['hide_title'] = (isset($form_settings['hide_title'])) ? $form_settings['hide_title'] : '0';
$form_settings['is_hidden_fields'] = (isset($form_settings['is_hidden_fields'])) ? $form_settings['is_hidden_fields'] : '0';
$formFieldPosition = (!empty($form_settings['style']['field_position'])) ? $form_settings['style']['field_position'] : 'left';
$mainSortableClass .= ' arm_field_position_' . $formFieldPosition . ' ';
$enable_social_login = (isset($form_settings['enable_social_login'])) ? $form_settings['enable_social_login'] : 0;
$social_btn_type = (!empty($form_settings['style']['social_btn_type'])) ? $form_settings['style']['social_btn_type'] : 'horizontal';
$social_btn_align = (!empty($form_settings['style']['social_btn_align'])) ? $form_settings['style']['social_btn_align'] : 'left';
$enable_social_btn_separator = (isset($form_settings['style']['enable_social_btn_separator'])) ? $form_settings['style']['enable_social_btn_separator'] : 0;
$social_btn_separator = (isset($form_settings['style']['social_btn_separator'])) ? $form_settings['style']['social_btn_separator'] : '';
$social_btn_position = (isset($form_settings['style']['social_btn_position'])) ? $form_settings['style']['social_btn_position'] : 'bottom';
if ($enable_social_login == '1') {
    $social_networks = (isset($form_settings['social_networks']) && $form_settings['social_networks'] != '') ? explode(',', $form_settings['social_networks']) : array();
    $social_networks_order = (isset($form_settings['social_networks_order']) && $form_settings['social_networks_order'] != '') ? explode(',', $form_settings['social_networks_order']) : array();
    $form_settings['social_networks_settings'] = (isset($form_settings['social_networks_settings'])) ? stripslashes_deep($form_settings['social_networks_settings']) : '';
    $formSocialNetworksSettings = maybe_unserialize($form_settings['social_networks_settings']);
} else {
    $enable_social_btn_separator = 0;
}
if ($firstForm['arm_form_type'] == 'login' && $arm_social_feature->isSocialLoginFeature) {
    if (!empty($social_networks)) {
        foreach ($social_networks as $sk) {
            $so = isset($activeSocialNetworks[$sk]) ? $activeSocialNetworks[$sk] : array();
            if (isset($so['status']) && $so['status'] == 1 && is_array($so)) {
                $so = isset($formSocialNetworksSettings[$sk]) ? $formSocialNetworksSettings[$sk] : $so;
                $icon_url = '';
                $icons = $arm_social_feature->arm_get_social_network_icons($sk);
                if(is_array($icons) && !empty($icons))
                {
                    if (isset($so['icon'])) {
                        if (isset($icons[$so['icon']]) && $icons[$so['icon']] != '') {
                            $icon_url = $icons[$so['icon']];
                        } else {
                            $icon = array_slice($icons, 0, 1);
                            $icon_url = array_shift($icon);
                        }
                    } else {
                        $icon = array_slice($icons, 0, 1);
                        $icon_url = array_shift($icon);
                    }
                }
                $so['label'] = isset($so['label']) ? $so['label'] : $sk;
                $socialLoginBtns .= '<div class="arm_social_link_container arm_social_link_container_' . esc_attr($sk) . '">';
                /*
                if (file_exists(strstr($icon_url, "//"))) 
                {
                    $icon_url = strstr($icon_url, "//");
                } 
                else if (file_exists($icon_url)) 
                {
                    $icon_url = $icon_url;
                }
                else 
                {
                    $icon_url = $icon_url;
                }
                */

                $socialLoginBtns .= '<a href="#"><img src="' . esc_url($icon_url) . '" alt="' . esc_attr($so['label']) . '"></a>';
                $socialLoginBtns .= '</div>';
            }
        }
    }
}

$show_reg_link = (isset($form_settings['show_registration_link'])) ? $form_settings['show_registration_link'] : 0;
$show_fp_link = (isset($form_settings['show_forgot_password_link'])) ? $form_settings['show_forgot_password_link'] : 0;
$arm_show_captcha_field=(isset($form_settings['show_other_form_captcha_field'])) ? $form_settings['show_other_form_captcha_field'] : 0;
$registration_link_label = (isset($form_settings['registration_link_label'])) ? stripslashes($form_settings['registration_link_label']) : esc_html__('Register', 'ARMember');
$forgot_password_link_label = (isset($form_settings['forgot_password_link_label'])) ? stripslashes($form_settings['forgot_password_link_label']) : esc_html__('Forgot Password', 'ARMember');
$registration_link_label = $arm_member_forms->arm_parse_login_links($registration_link_label, '#');
$forgot_password_link_label = $arm_member_forms->arm_parse_login_links($forgot_password_link_label, '#');
reset($otherForms);

?>

    <div id="tabsetting-2" class="arm-tab-content">
        <div class="arm_form_setting_options_head style_setting_main_heading"><?php esc_html_e('Style Settings', 'ARMember'); ?></div>
        <div id="arm_form_styles_fields_container" class="arm_form_styles_fields_container" data-form_id="<?php echo esc_attr($form_id); ?>">
            <div id="arm_accordion">
                <ul>
                    <li class="arm_active_section">
                        <a href="javascript:void(0)" class="arm_accordion_header"><?php esc_html_e('Form Options', 'ARMember'); ?>:<i></i></a>
                        <div id="one" class="arm_accordion default">
                            <table class="arm_form_settings_style_block arm_tbl_label_left_input_right">
                                <tr>
                                    <td><?php esc_html_e('Form Style', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type="hidden" id="arm_manage_form_layout1" name="arm_form_settings[style][form_layout]" class="arm_manage_form_layout armMappedTextbox" data-id="arm_manage_form_layout" value="<?php echo esc_attr($formLayout); ?>" data-old_value="<?php echo esc_attr($formLayout); ?>"/>
                                            <dl class="arm_selectbox column_level_dd arm_width_160">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_manage_form_layout1">
                                                        <li data-label="<?php esc_attr_e('Material Outline', 'ARMember'); ?>" data-value="writer_border"><?php esc_html_e('Material Outline', 'ARMember'); ?></li>
                                                        <li data-label="<?php esc_attr_e('Material Style', 'ARMember'); ?>" data-value="writer"><?php esc_html_e('Material Style', 'ARMember'); ?></li>
                                                        <li data-label="<?php esc_attr_e('Standard Style', 'ARMember'); ?>" data-value="iconic"><?php esc_html_e('Standard Style', 'ARMember'); ?></li>
                                                        <li data-label="<?php esc_attr_e('Rounded Style', 'ARMember'); ?>" data-value="rounded"><?php esc_html_e('Rounded Style', 'ARMember'); ?></li>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Form Width', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type="text" id="arm_form_width" name="arm_form_settings[style][form_width]" class="arm_form_width arm_form_setting_input armMappedTextbox arm_width_130" data-id="arm_form_width1" value="<?php echo !empty($form_settings['style']['form_width']) ? esc_attr($form_settings['style']['form_width']) : '600'; //phpcs:ignore?>" onkeydown="javascript:return checkNumber(event)"/><span>&nbsp;(px)</span>
                                            <input type='hidden' id="arm_form_width_type" name="arm_form_settings[style][form_width_type]" class="arm_form_width_type" value="px" />
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="arm_form_editor_field_label"><?php esc_html_e('Border', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='text' id="arm_form_border_width" name="arm_form_settings[style][form_border_width]" class="arm_form_width arm_form_setting_input arm_width_80" value="<?php echo isset($form_settings['style']['form_border_width']) ? esc_attr($form_settings['style']['form_border_width']) : '0'; ?>" onkeydown="javascript:return checkNumber(event)" />

                                            <br />Width (px)
                                        </div>
                                    </td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='text' id="arm_form_border_radius" name="arm_form_settings[style][form_border_radius]" class="arm_form_width arm_form_setting_input arm_width_80" value="<?php echo isset($form_settings['style']['form_border_radius']) ? esc_attr($form_settings['style']['form_border_radius']) : '8'; //phpcs:ignore?>" onkeydown="javascript:return checkNumber(event)" />

                                            <br />Radius (px)
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_form_border_style" name="arm_form_settings[style][form_border_style]" class="arm_form_border_style" value="<?php echo!empty($form_settings['style']['form_border_style']) ? esc_attr($form_settings['style']['form_border_style']) : 'solid'; ?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_150">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_form_border_style">
                                                        <li data-label="Solid" data-value="solid">Solid</li>
                                                        <li data-label="Dashed" data-value="dashed">Dashed</li>
                                                        <li data-label="Dotted" data-value="dotted">Dotted</li>
                                                    </ul>
                                                </dd>
                                            </dl>
                                            <br />Style
                                        </div>
                                    </td>                                            
                                </tr>
                                <tr>
                                    <td class="arm_form_editor_field_label"><?php esc_html_e('Form Padding', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_button_margin_inputs_container arm_right">
                                            <?php
                                            $form_settings['style']['form_padding_left'] = (is_numeric($form_settings['style']['form_padding_left'])) ? $form_settings['style']['form_padding_left'] : 20;
                                            $form_settings['style']['form_padding_top'] = (is_numeric($form_settings['style']['form_padding_top'])) ? $form_settings['style']['form_padding_top'] : 20;
                                            $form_settings['style']['form_padding_right'] = (is_numeric($form_settings['style']['form_padding_right'])) ? $form_settings['style']['form_padding_right'] : 20;
                                            $form_settings['style']['form_padding_bottom'] = (is_numeric($form_settings['style']['form_padding_bottom'])) ? $form_settings['style']['form_padding_bottom'] : 20;
                                            ?>
                                            <div class="arm_button_margin_inputs">
                                                <input type="text" name="arm_form_settings[style][form_padding_left]" id="arm_form_padding_left" class="arm_form_padding_left" value="<?php echo esc_attr($form_settings['style']['form_padding_left']); //phpcs:ignore?>"/>
                                                <br /><?php esc_html_e('Left', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_button_margin_inputs">
                                                <input type="text" name="arm_form_settings[style][form_padding_top]" id="arm_form_padding_top" class="arm_form_padding_top" value="<?php echo esc_attr($form_settings['style']['form_padding_top']); ?>"/>
                                                <br /><?php esc_html_e('Top', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_button_margin_inputs">
                                                <input type="text" name="arm_form_settings[style][form_padding_right]" id="arm_form_padding_right" class="arm_form_padding_right" value="<?php echo esc_attr($form_settings['style']['form_padding_right']); ?>"/>
                                                <br /><?php esc_html_e('Right', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_button_margin_inputs">
                                                <input type="text" name="arm_form_settings[style][form_padding_bottom]" id="arm_form_padding_bottom" class="arm_form_padding_bottom" value="<?php echo esc_attr($form_settings['style']['form_padding_bottom']); ?>"/>
                                                <br /><?php esc_html_e('Bottom', 'ARMember'); ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="arm_vertical_align_top"><?php esc_html_e('Background', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <?php
                                            $isFormBGImg = !empty($form_settings['style']['form_bg']) && file_exists(MEMBERSHIP_UPLOAD_DIR . '/' . basename($form_settings['style']['form_bg'])) ? true : false;
                                            $form_settings['style']['form_bg'] = ($isFormBGImg) ? $form_settings['style']['form_bg'] : '';
                                            ?>
                                            <div class="arm_form_bg_upload_wrapper">
                                                <div class="armFileUploadWrapper">
                                                    <div class="armFileUploadContainer" style="<?php echo ($isFormBGImg) ? 'display: none;' : ''; ?>">
                                                        <div class="armFileUpload-icon"></div><?php esc_html_e('Upload', 'ARMember'); ?>
                                                        <input id="armFormBGFileUpload" class="armFileUpload armFormBGFileUpload armIgnore" name="arm_form_settings[style][form_bg_file]" data-file_type='arm_form_bg' type="file" value="" accept=".jpg,.jpeg,.png,.gif,.bmp" data-file_size="5"/>
                                                    </div>
                                                    <div class="arm_image_file_preview"><?php
                                                        if ($isFormBGImg) {
                                                            echo '<img alt="" src="' . esc_attr($form_settings['style']['form_bg']) . '"/>';
                                                        }
                                                        ?></div>
                                                    <div class="armFileRemoveContainer" style="<?php echo ($isFormBGImg) ? 'display: inline-block;' : ''; ?>"><div class="armFileRemove-icon"></div><?php esc_html_e('Remove', 'ARMember'); ?></div>
                                                    <div class="armUploadedFileName" id="armFormBGUploadedFileName"></div>
                                                    <div class="armFileMessages" id="armFileUploadMsg"></div>
                                                    <input class="arm_file_url" type="hidden" name="arm_form_settings[style][form_bg]" value="<?php echo esc_attr($form_settings['style']['form_bg']); ?>">
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Opacity', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_form_opacity" name="arm_form_settings[style][form_opacity]" class="arm_form_opacity" value="<?php echo !empty($form_settings['style']['form_opacity']) ? esc_attr($form_settings['style']['form_opacity']) : '1'; //phpcs:ignore?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_80 arm_min_width_50">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_form_opacity">
                                                        <li data-label="1.0" data-value="1">1.0</li>
                                                        <li data-label="0.9" data-value="0.9">0.9</li>
                                                        <li data-label="0.8" data-value="0.8">0.8</li>
                                                        <li data-label="0.7" data-value="0.7">0.7</li>
                                                        <li data-label="0.6" data-value="0.6">0.6</li>
                                                        <li data-label="0.5" data-value="0.5">0.5</li>
                                                        <li data-label="0.4" data-value="0.4">0.4</li>
                                                        <li data-label="0.3" data-value="0.3">0.3</li>
                                                        <li data-label="0.2" data-value="0.2">0.2</li>
                                                        <li data-label="0.1" data-value="0.1">0.1</li>
                                                        <li data-label="0.0" data-value="0.0">0.0</li>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="font_settings_label"><?php esc_html_e('Form Title Settings', 'ARMember'); ?></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Hide Title', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <div class="armswitch arm_global_setting_switch arm_vertical_align_middle" >
                                                <input type="checkbox" id="arm_hide_form_title" <?php checked($form_settings['hide_title'], '1'); ?> value="1" class="armswitch_input armIgnore" name="arm_form_settings[hide_title]"/>
                                                <label for="arm_hide_form_title" class="armswitch_label"></label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Family', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_form_title_font_family" name="arm_form_settings[style][form_title_font_family]" class="arm_form_title_font_family" value="<?php echo !empty($form_settings['style']['form_title_font_family']) ? esc_attr($form_settings['style']['form_title_font_family']) : 'Helvetica'; //phpcs:ignore?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_150">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_form_title_font_family">
                                                        <?php echo $arm_member_forms->arm_fonts_list(); //phpcs:ignore?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Size', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_form_title_font_size" name="arm_form_settings[style][form_title_font_size]" class="arm_form_title_font_size" value="<?php echo isset($form_settings['style']['form_title_font_size']) ? esc_attr($form_settings['style']['form_title_font_size']) : '26'; ?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_120">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_form_title_font_size">
                                                        <?php
                                                        for ($i = 8; $i < 41; $i++) {
                                                            ?><li data-label="<?php echo esc_attr($i); ?>" data-value="<?php echo esc_attr($i); ?>"><?php echo esc_attr($i); ?></li><?php
                                                        }
                                                        ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                            <span>(px)</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Style', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <div class="arm_font_style_options">
                                                <!--/. Font Bold Option ./-->
                                                <label class="arm_font_style_label <?php echo ($form_settings['style']['form_title_font_bold'] == '1') ? 'arm_style_active' : ''; ?>" data-value="bold" data-field="arm_form_title_font_bold"><i class="armfa armfa-bold"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][form_title_font_bold]" id="arm_form_title_font_bold" class="arm_form_title_font_bold" value="<?php echo esc_attr($form_settings['style']['form_title_font_bold']); ?>" />
                                                <!--/. Font Italic Option ./-->
                                                <label class="arm_font_style_label <?php echo ($form_settings['style']['form_title_font_italic'] == '1') ? 'arm_style_active' : ''; ?>" data-value="italic" data-field="arm_form_title_font_italic"><i class="armfa armfa-italic"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][form_title_font_italic]" id="arm_form_title_font_italic" class="arm_form_title_font_italic" value="<?php echo esc_attr($form_settings['style']['form_title_font_italic']); ?>" />
                                                <!--/. Text Decoration Options ./-->
                                                <label class="arm_font_style_label arm_decoration_label <?php echo ($form_settings['style']['form_title_font_decoration'] == 'underline') ? 'arm_style_active' : ''; ?>" data-value="underline" data-field="arm_form_title_font_decoration"><i class="armfa armfa-underline"></i></label>
                                                <label class="arm_font_style_label arm_decoration_label <?php echo ($form_settings['style']['form_title_font_decoration'] == 'line-through') ? 'arm_style_active' : ''; ?>" data-value="line-through" data-field="arm_form_title_font_decoration"><i class="armfa armfa-strikethrough"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][form_title_font_decoration]" id="arm_form_title_font_decoration" class="arm_form_title_font_decoration" value="<?php echo esc_attr($form_settings['style']['form_title_font_decoration']); ?>" />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Title Position', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <?php $form_settings['style']['form_title_position'] = (!empty($form_settings['style']['form_title_position'])) ? $form_settings['style']['form_title_position'] : 'left'; ?>
                                            <div class="arm_switch arm_switch3 arm_form_title_position_switch">
                                                <label data-value="left" class="arm_switch_label <?php echo ($form_settings['style']['form_title_position'] == 'left') ? 'active' : ''; ?>"><?php esc_html_e('Left', 'ARMember'); ?></label>
                                                <label data-value="center" class="arm_switch_label <?php echo ($form_settings['style']['form_title_position'] == 'center') ? 'active' : ''; ?>"><?php esc_html_e('Center', 'ARMember'); ?></label>
                                                <label data-value="right" class="arm_switch_label <?php echo ($form_settings['style']['form_title_position'] == 'right') ? 'active' : ''; ?>"><?php esc_html_e('Right', 'ARMember'); ?></label>
                                                <input type="hidden" class="arm_switch_radio" name="arm_form_settings[style][form_title_position]" value="<?php echo esc_attr($form_settings['style']['form_title_position']); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="arm_validation_message_type_container <?php echo ($formLayout == 'writer' || $formLayout == 'writer_border') ? 'hidden_section' : ''; ?>">
                                    <td colspan="3"><?php esc_html_e('Validation Message Type', 'ARMember'); ?></td>
                                </tr>
                                <tr class="arm_validation_message_type_container <?php echo ($formLayout == 'writer' || $formLayout == 'writer_border') ? 'hidden_section' : ''; ?>">
                                    <td colspan="3">
                                        <?php $msg_validation_type = $form_settings['style']['validation_type'] = (!empty($form_settings['style']['validation_type'])) ? $form_settings['style']['validation_type'] : 'modern'; ?>
                                        <div class="arm_switch arm_switch2 arm_validation_style_switch">
                                            <label data-value="modern" class="arm_switch_label <?php echo ($form_settings['style']['validation_type'] == 'modern') ? 'active' : ''; ?>"><?php esc_html_e('Modern', 'ARMember'); ?></label>
                                            <label data-value="standard" class="arm_switch_label <?php echo ($form_settings['style']['validation_type'] == 'standard') ? 'active' : ''; ?>"><?php esc_html_e('Standard', 'ARMember'); ?></label>
                                            <input type="hidden" class="arm_switch_radio" name="arm_form_settings[style][validation_type]" value="<?php echo esc_attr($form_settings['style']['validation_type']); ?>">
                                        </div>
                                    </td>
                                </tr>

                                <tr class="arm_validation_message_position_container <?php echo ($formLayout == 'writer' || $formLayout == 'writer_border' || $msg_validation_type == 'standard') ? 'hidden_section' : ''; ?>">
                                    <td colspan="3"><?php esc_html_e('Validation Message Position', 'ARMember'); ?></td>
                                </tr>
                                <tr class="arm_validation_message_position_container <?php echo ($formLayout == 'writer' || $formLayout == 'writer_border' || $msg_validation_type == 'standard') ? 'hidden_section' : ''; ?>">
                                    <td colspan="3">
                                        <?php $form_settings['style']['validation_position'] = (!empty($form_settings['style']['validation_position'])) ? $form_settings['style']['validation_position'] : 'bottom'; ?>
                                        <div class="arm_switch arm_switch4 arm_validation_position_switch">
                                            <label data-value="top" class="arm_switch_label <?php echo ($form_settings['style']['validation_position'] == 'top') ? 'active' : ''; ?>"><?php esc_html_e('Top', 'ARMember'); ?></label>
                                            <label data-value="bottom" class="arm_switch_label <?php echo ($form_settings['style']['validation_position'] == 'bottom') ? 'active' : ''; ?>"><?php esc_html_e('Bottom', 'ARMember'); ?></label>
                                            <label data-value="left" class="arm_switch_label <?php echo ($form_settings['style']['validation_position'] == 'left') ? 'active' : ''; ?>"><?php esc_html_e('Left', 'ARMember'); ?></label>
                                            <label data-value="right" class="arm_switch_label <?php echo ($form_settings['style']['validation_position'] == 'right') ? 'active' : ''; ?>"><?php esc_html_e('Right', 'ARMember'); ?></label>
                                            <input type="hidden" class="arm_switch_radio" name="arm_form_settings[style][validation_position]" value="<?php echo esc_attr($form_settings['style']['validation_position']); ?>">
                                        </div>
                                    </td>
                                </tr>
                                <tr class="arm_registration_link_options <?php echo ($show_reg_link != '1') ? 'hidden_section' : ''; ?>">
                                    <td colspan="2" class="font_settings_label"><?php esc_html_e('Link Position Settings', 'ARMember'); ?></td>
                                    <td></td>
                                </tr>
                                <tr class="arm_registration_link_options <?php echo ($show_reg_link != '1') ? 'hidden_section' : ''; ?>">
                                    <td class="arm_form_editor_field_label"><?php esc_html_e('Registration Link Margin', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <?php
                                        $registration_link_margin = (isset($form_settings['registration_link_margin'])) ? $form_settings['registration_link_margin'] : array();
                                        $registration_link_margin['left'] = (isset($registration_link_margin['left']) && is_numeric($registration_link_margin['left'])) ? $registration_link_margin['left'] : 0;
                                        $registration_link_margin['top'] = (isset($registration_link_margin['top']) && is_numeric($registration_link_margin['top'])) ? $registration_link_margin['top'] : 0;
                                        $registration_link_margin['right'] = (isset($registration_link_margin['right']) && is_numeric($registration_link_margin['right'])) ? $registration_link_margin['right'] : 0;
                                        $registration_link_margin['bottom'] = (isset($registration_link_margin['bottom']) && is_numeric($registration_link_margin['bottom'])) ? $registration_link_margin['bottom'] : 0;
                                        ?>
                                        <div class="arm_registration_link_margin_inputs_container">
                                            <div class="arm_registration_link_margin_inputs">
                                                <input type="text" name="arm_form_settings[registration_link_margin][left]" id="arm_registration_link_margin_left" class="arm_registration_link_margin_left" value="<?php echo esc_attr($registration_link_margin['left']); ?>"/>
                                                <br /><?php esc_html_e('Left', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_registration_link_margin_inputs">
                                                <input type="text" name="arm_form_settings[registration_link_margin][top]" id="arm_registration_link_margin_top" class="arm_registration_link_margin_top" value="<?php echo esc_attr($registration_link_margin['top']); ?>"/>
                                                <br /><?php esc_html_e('Top', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_registration_link_margin_inputs">
                                                <input type="text" name="arm_form_settings[registration_link_margin][right]" id="arm_registration_link_margin_right" class="arm_registration_link_margin_right" value="<?php echo esc_attr($registration_link_margin['right']); ?>"/>
                                                <br /><?php esc_html_e('Right', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_registration_link_margin_inputs">
                                                <input type="text" name="arm_form_settings[registration_link_margin][bottom]" id="arm_registration_link_margin_bottom" class="arm_registration_link_margin_bottom" value="<?php echo esc_attr($registration_link_margin['bottom']); ?>"/>
                                                <br /><?php esc_html_e('Bottom', 'ARMember'); ?>
                                            </div>
                                        </div>
                                        <div class="armclear"></div>
                                    </td>
                                </tr>
                                <tr class="arm_forgot_password_link_options <?php echo ($show_fp_link != '1') ? 'hidden_section' : ''; ?>">
                                    <td class="arm_form_editor_field_label"><?php esc_html_e('Forgot Password Link Margin', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <?php
                                        $forgot_password_link_margin = (isset($form_settings['forgot_password_link_margin'])) ? $form_settings['forgot_password_link_margin'] : array();
                                        $forgot_password_link_margin['left'] = (isset($forgot_password_link_margin['left']) && is_numeric($forgot_password_link_margin['left'])) ? $forgot_password_link_margin['left'] : 0;
                                        $forgot_password_link_margin['top'] = (isset($forgot_password_link_margin['top']) && is_numeric($forgot_password_link_margin['top'])) ? $forgot_password_link_margin['top'] : 0;
                                        $forgot_password_link_margin['right'] = (isset($forgot_password_link_margin['right']) && is_numeric($forgot_password_link_margin['right'])) ? $forgot_password_link_margin['right'] : 0;
                                        $forgot_password_link_margin['bottom'] = (isset($forgot_password_link_margin['bottom']) && is_numeric($forgot_password_link_margin['bottom'])) ? $forgot_password_link_margin['bottom'] : 0;
                                        ?>
                                        <div class="arm_forgot_password_link_margin_inputs_container">
                                            <div class="arm_forgot_password_link_margin_inputs">
                                                <input type="text" name="arm_form_settings[forgot_password_link_margin][left]" id="arm_forgot_password_link_margin_left" class="arm_forgot_password_link_margin_left" value="<?php echo esc_attr($forgot_password_link_margin['left']); ?>"/>
                                                <br /><?php esc_html_e('Left', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_forgot_password_link_margin_inputs">
                                                <input type="text" name="arm_form_settings[forgot_password_link_margin][top]" id="arm_forgot_password_link_margin_top" class="arm_forgot_password_link_margin_top" value="<?php echo esc_attr($forgot_password_link_margin['top']); ?>"/>
                                                <br /><?php esc_html_e('Top', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_forgot_password_link_margin_inputs">
                                                <input type="text" name="arm_form_settings[forgot_password_link_margin][right]" id="arm_forgot_password_link_margin_right" class="arm_forgot_password_link_margin_right" value="<?php echo esc_attr($forgot_password_link_margin['right']); ?>"/>
                                                <br /><?php esc_html_e('Right', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_forgot_password_link_margin_inputs">
                                                <input type="text" name="arm_form_settings[forgot_password_link_margin][bottom]" id="arm_forgot_password_link_margin_bottom" class="arm_forgot_password_link_margin_bottom" value="<?php echo esc_attr($forgot_password_link_margin['bottom']); ?>"/>
                                                <br /><?php esc_html_e('Bottom', 'ARMember'); ?>
                                            </div>
                                        </div>
                                        <div class="armclear"></div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </li>
                    <li id="arm_color_scheme_container" class="arm_form_style_color_schemes">
                        <a href="javascript:void(0)" class="arm_accordion_header"><?php esc_html_e('Color Options', 'ARMember'); ?>:<i></i></a>
                        <div id="two" class="arm_accordion">
                            <table class="arm_form_settings_style_block">
                                <tr>
                                    <td colspan="2">
                                        <div class="c_schemes">
                                            <?php foreach ($formColorSchemes as $color => $color_opt) { ?>
                                                <?php if ($color != 'custom') { ?>
                                                    <label class="arm_color_scheme_block arm_color_scheme_block_<?php echo esc_attr($color); ?> <?php echo ($form_settings['style']['color_scheme'] == $color) ? 'arm_color_box_active' : ''; ?>" style="background-color:<?php echo isset($color_opt['main_color']) ? esc_attr($color_opt['main_color']) : ''; ?>">
                                                        <input id="arm_color_block_radio_<?php echo esc_attr($color); ?>" type="radio" name="arm_form_settings[style][color_scheme]" value="<?php echo esc_attr($color); ?>" class="arm_color_block_radio armMappedRadio" data-id="arm_color_block_radio_<?php echo esc_attr($color); ?>1" <?php checked($form_settings['style']['color_scheme'], $color) ?>/>
                                                    </label>
                                                <?php } ?>
                                            <?php } ?>
                                            <label class="arm_color_scheme_block arm_color_scheme_block_custom">
                                                <span><?php esc_html_e('Custom Color', 'ARMember'); ?></span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <div class="arm_form_custom_style_opts arm_slider_box arm_custom_scheme_box">
                                <div class="arm_form_field_settings_menu arm_slider_box_container arm_custom_scheme_container">
                                    <div class="arm_slider_box_arrow arm_custom_scheme_arrow"></div>
                                    <div class="arm_slider_box_heading" style="display: none;"><?php esc_html_e('Custom Setting', 'ARMember'); ?></div>
                                    <div class="arm_slider_box_body arm_custom_scheme_block">
                                        <?php
                                        $formColorScheme = isset($form_settings['style']['color_scheme']) ? $form_settings['style']['color_scheme'] : 'blue';
                                        $formColors = isset($formColorSchemes[$formColorScheme]) ? $formColorSchemes[$formColorScheme] : array();
                                        ?>
                                        <table class="arm_form_settings_style_block">
                                            <tr>
                                                <td class="arm_custom_scheme_main_label" colspan="4"><?php esc_html_e('Form', 'ARMember'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_form_title_font_color" type="text" name="arm_form_settings[style][form_title_font_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['form_title_font_color']) ; ?>"/>
                                                    <span><?php esc_html_e('Form Title', 'ARMember'); ?></span>
                                                </td>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_form_bg_color" type="text" name="arm_form_settings[style][form_bg_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['form_bg_color']); ?>"/>
                                                    <span><?php esc_html_e('Form Background', 'ARMember'); ?></span>
                                                </td>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_form_border_color" type="text" name="arm_form_settings[style][form_border_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['form_border_color']); ?>"/>
                                                    <span><?php esc_html_e('Form Border', 'ARMember'); ?></span>
                                                </td>
                                                <?php if (!$isRegister && !$isEditProfile) { ?>
                                                    <td class="arm_custom_scheme_sub_label">
                                                        <input id="arm_login_link_font_color" type="text" name="arm_form_settings[style][login_link_font_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['login_link_font_color']); ?>"/>
                                                        <span><?php esc_html_e('Forgot / Register Link', 'ARMember'); ?></span>
                                                    </td>
                                                <?php } ?>


                                                <?php
                                                    if($isRegister)
                                                    {
                                                ?>
                                                        <td class="arm_custom_scheme_sub_label">
                                                            <input id="arm_register_link_font_color" type="text" name="arm_form_settings[style][register_link_font_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['register_link_font_color']); ?>"/>
                                                            <span><?php esc_html_e('Register Link', 'ARMember'); ?></span>
                                                        </td>
                                                <?php        
                                                    }
                                                ?>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_divider" colspan="4"></td>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_main_label" colspan="4"><?php esc_html_e('Label & Input Fields', 'ARMember'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_field_font_color" type="text" name="arm_form_settings[style][field_font_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['field_font_color']); ?>"/>
                                                    <span><?php esc_html_e('Field Font', 'ARMember'); ?></span>
                                                </td>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_field_border_color" type="text" name="arm_form_settings[style][field_border_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['field_border_color']); ?>"/>
                                                    <span><?php esc_html_e('Field Border', 'ARMember'); ?></span>
                                                </td>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_field_focus_color" type="text" name="arm_form_settings[style][field_focus_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['field_focus_color']); ?>" data-form_id="<?php echo esc_attr($form_id); ?>"/>
                                                    <span><?php esc_html_e('Field Focus', 'ARMember'); ?></span>
                                                </td>
                                                <td class="arm_custom_scheme_sub_label arm_custom_scheme_sub_label_no_writer <?php echo ($formLayout == 'writer' || $formLayout == 'writer_border') ? 'hidden_section' : ''; ?>">
                                                    <input id="arm_field_bg_color" type="text" name="arm_form_settings[style][field_bg_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['field_bg_color']); ?>" data-form_id="<?php echo esc_attr($form_id); ?>"/>
                                                    <span><?php esc_html_e('Field Background', 'ARMember'); ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_lable_font_color" type="text" name="arm_form_settings[style][lable_font_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['lable_font_color']); ?>"/>
                                                    <span><?php esc_html_e('Label Font', 'ARMember'); ?></span>
                                                </td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_divider" colspan="4"></td>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_main_label" colspan="4"><?php esc_html_e('Submit Button', 'ARMember'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_button_back_color" type="text" name="arm_form_settings[style][button_back_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['button_back_color']); ?>"/>
                                                    <span><?php esc_html_e('Button Background', 'ARMember'); ?></span>
                                                </td>
                                                <?php
                                                if (in_array($reference_template, array(3))) {
                                                    ?>
                                                    <td class="arm_custom_scheme_sub_label" id="arm_button_gradient_color" colspan="2">
                                                        <input id="arm_button_back_color_gradient" type="text" name="arm_form_settings[style][button_back_color_gradient]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['button_back_color_gradient']); ?>"/>
                                                        <span><?php esc_html_e('Button Background 2', 'ARMember') ?></span>
                                                    </td>
                                                <?php } ?>
                                                <td class="arm_custom_scheme_sub_label arm_button_font_color_wrapper <?php echo (!empty($form_settings['style']['button_style']) && $form_settings['style']['button_style'] == 'border') ? 'hidden_section' : ''; ?>">
                                                    <input id="arm_button_font_color" type="text" name="arm_form_settings[style][button_font_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['button_font_color']); ?>"/>
                                                    <span><?php esc_html_e('Button Font', 'ARMember'); ?></span>
                                                </td>
                                                <?php
                                                if (!in_array($reference_template, array(3))) {
                                                    ?>
                                                    <td class="arm_custom_scheme_sub_label">
                                                        <input id="arm_button_hover_color" type="text" name="arm_form_settings[style][button_hover_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['button_hover_color']); ?>"/>
                                                        <span><?php esc_html_e('Hover Background', 'ARMember'); ?></span>
                                                    </td>
                                                    <?php
                                                }
                                                ?>
                                                <?php
                                                if (!in_array($reference_template, array(3))) {
                                                    ?>
                                                    <td class="arm_custom_scheme_sub_label arm_button_hover_font_color_wrapper <?php echo (!empty($form_settings['style']['button_style']) && $form_settings['style']['button_style'] == 'reverse_border') ? 'hidden_section' : ''; ?>">
                                                        <input id="arm_button_hover_font_color" type="text" name="arm_form_settings[style][button_hover_font_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['button_hover_font_color']); ?>"/>
                                                        <span><?php esc_html_e('Hover Font', 'ARMember'); ?></span>
                                                    </td>
                                                <?php } ?>
                                            </tr>
                                            <tr>
                                                <?php
                                                if (in_array($reference_template, array(3))) {
                                                    ?>
                                                    <td class="arm_custom_scheme_sub_label">
                                                        <input id="arm_button_hover_color" type="text" name="arm_form_settings[style][button_hover_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['button_hover_color']); ?>"/>
                                                        <span><?php esc_html_e('Hover Background', 'ARMember'); ?></span>
                                                    </td>
                                                    <?php
                                                }
                                                ?>
                                                <?php
                                                if (in_array($reference_template, array(3))) {
                                                    ?>
                                                    <td class="arm_custom_scheme_sub_label" id="arm_button_hover_gradient_color" colspan="2">
                                                        <input id="arm_button_hover_color_gradient" type="text" name="arm_form_settings[style][button_hover_color_gradient]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['button_hover_color_gradient']); ?>" />
                                                        <span><?php esc_html_e('Hover Background 2', 'ARMember'); ?></span>
                                                    </td>
                                                <?php } ?>
                                                <?php
                                                if (in_array($reference_template, array(3))) {
                                                    ?>
                                                    <td class="arm_custom_scheme_sub_label arm_button_hover_font_color_wrapper <?php echo (!empty($form_settings['style']['button_style']) && $form_settings['style']['button_style'] == 'reverse_border') ? 'hidden_section' : ''; ?>">
                                                        <input id="arm_button_hover_font_color" type="text" name="arm_form_settings[style][button_hover_font_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['button_hover_font_color']); ?>"/>
                                                        <span><?php esc_html_e('Hover Font', 'ARMember'); ?></span>
                                                    </td>
                                                <?php } ?>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_divider" colspan="4"></td>
                                            </tr>
                                            <tr class=" arm_custom_scheme_sub_label_no_writer">
                                                <td class="arm_custom_scheme_main_label" colspan="4"><?php esc_html_e('Prefix / Suffix Icon Color', 'ARMember'); ?></td>
                                            </tr>
                                            <tr class=" arm_custom_scheme_sub_label_no_writer">
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_prefix_suffix_color" type="text" name="arm_form_settings[style][prefix_suffix_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['prefix_suffix_color']); ?>"/>
                                                    <span><?php esc_html_e('Icon Color', 'ARMember'); ?></span>
                                                </td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_divider arm_custom_scheme_sub_label_no_writer <?php echo ($formLayout == 'writer') ? 'hidden_section' : ''; ?>" colspan="4"></td>
                                            </tr>
                                            <tr>
                                                <td class="arm_custom_scheme_main_label" colspan="4"><?php esc_html_e('Validation Color', 'ARMember'); ?></td>
                                            </tr>
                                            <tr>
                                                <?php
                                                $d_error_font_color = $form_settings['style']['error_font_color'];
                                                $d_error_field_bg_color = $form_settings['style']['error_field_bg_color'];
                                                if ($formLayout == 'writer' || $formLayout == 'writer_border' || (isset($form_settings['style']['validation_type']) && $form_settings['style']['validation_type'] == 'standard')) {
                                                    $d_error_font_color = $form_settings['style']['error_field_bg_color'];
                                                    $d_error_field_bg_color = $form_settings['style']['error_font_color'];
                                                }
                                                
                                                ?>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_error_font_color" type="text" name="arm_form_settings[style][error_font_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['error_font_color']); ?>" data-old_color="<?php echo esc_attr($d_error_font_color); ?>"/>
                                                    <span><?php esc_html_e('Validation Message Font', 'ARMember'); ?></span>
                                                </td>
                                                <td class="arm_custom_scheme_sub_label">
                                                    <input id="arm_error_field_border_color" type="text" name="arm_form_settings[style][error_field_border_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['error_field_border_color']); ?>"/>
                                                    <span><?php esc_html_e('Error Field Border', 'ARMember'); ?></span>
                                                </td>
                                                <td class="arm_custom_scheme_sub_label arm_custom_scheme_sub_label_no_writer <?php echo ($formLayout == 'writer' || $formLayout == 'writer_border') ? 'hidden_section' : ''; ?>">
                                                    <input id="arm_error_field_bg_color" type="text" name="arm_form_settings[style][error_field_bg_color]" class="arm_colorpicker arm_custom_scheme_colorpicker" value="<?php echo esc_attr($form_settings['style']['error_field_bg_color']); ?>" data-old_color="<?php echo esc_attr($d_error_field_bg_color); ?>"/>
                                                    <span><?php esc_html_e('Validation Message Background', 'ARMember'); ?></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>    
                    <li>
                        <a href="javascript:void(0)" class="arm_accordion_header"><?php esc_html_e('Input Field Options', 'ARMember'); ?>:<i></i></a>
                        <div id="three" class="arm_accordion">
                            <table class="arm_form_settings_style_block arm_tbl_label_left_input_right">
                                <tr>
                                    <td><?php esc_html_e('Field Width', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type="text" id="arm_field_width" name="arm_form_settings[style][field_width]" class="arm_field_width arm_form_setting_input arm_width_140" value="<?php echo!empty($form_settings['style']['field_width']) ? esc_attr($form_settings['style']['field_width']) : '100'; //phpcs:ignore?>" onkeydown="javascript:return checkNumber(event)"/><span>&nbsp;(%)&nbsp;</span>
                                            <input type='hidden' id="arm_field_width_type" name="arm_form_settings[style][field_width_type]" class="arm_field_width_type" value="%" />
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Field Height', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type="text" id="arm_field_height" name="arm_form_settings[style][field_height]" class="arm_field_height arm_form_setting_input arm_width_140" value="<?php echo isset($form_settings['style']['field_height']) ? esc_attr($form_settings['style']['field_height']) : '33'; //phpcs:ignore?>"  onkeydown="javascript:return checkNumber(event)"/><span>&nbsp;(px)</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Field Spacing', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type="text" id="arm_field_spacing" name="arm_form_settings[style][field_spacing]" class="arm_field_spacing arm_form_setting_input arm_width_140" value="<?php echo isset($form_settings['style']['field_spacing']) ? esc_attr($form_settings['style']['field_spacing']) : '10'; //phpcs:ignore?>" onkeydown="javascript:return checkNumber(event)"/><span>&nbsp;(px)</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="arm_vertical_align_top"><?php esc_html_e('Border', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='text' id="arm_field_border_width" name="arm_form_settings[style][field_border_width]" class="arm_field_border_width arm_form_setting_input arm_width_80" value="<?php echo isset($form_settings['style']['field_border_width']) ? esc_attr($form_settings['style']['field_border_width']) : '1'; //phpcs:ignore?>" onkeydown="javascript:return checkNumber(event)" />
                                            <br />Width (px)
                                        </div>
                                    </td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='text' id="arm_field_border_radius" name="arm_form_settings[style][field_border_radius]" class="arm_field_border_radius arm_form_setting_input arm_width_80" value="<?php echo isset($form_settings['style']['field_border_radius']) ? esc_attr($form_settings['style']['field_border_radius']) : '3'; //phpcs:ignore?>" onkeydown="javascript:return checkNumber(event)" <?php echo ($formLayout=='writer_border' || $formLayout=='writer') ? 'readonly="readonly"' : ''; ?> />

                                            <br />Radius (px)
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_field_border_style" name="arm_form_settings[style][field_border_style]" class="arm_field_border_style" value="<?php echo!empty($form_settings['style']['field_border_style']) ? esc_attr($form_settings['style']['field_border_style']) : 'solid'; ?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_140">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_field_border_style">
                                                        <li data-label="Solid" data-value="solid">Solid</li>
                                                        <li data-label="Dashed" data-value="dashed">Dashed</li>
                                                        <li data-label="Dotted" data-value="dotted">Dotted</li>
                                                    </ul>
                                                </dd>
                                            </dl>
                                            <br />Style
                                        </div>
                                    </td>                                            
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Field Alignment', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <?php $form_settings['style']['field_position'] = (!empty($form_settings['style']['field_position'])) ? $form_settings['style']['field_position'] : 'left'; ?>
                                            <div class="arm_switch arm_switch3 arm_field_position_switch">
                                                <label data-value="left" class="arm_switch_label <?php echo ($form_settings['style']['field_position'] == 'left') ? 'active' : ''; ?>"><?php esc_html_e('Left', 'ARMember'); ?></label>
                                                <label data-value="center" class="arm_switch_label <?php echo ($form_settings['style']['field_position'] == 'center') ? 'active' : ''; ?>"><?php esc_html_e('Center', 'ARMember'); ?></label>
                                                <label data-value="right" class="arm_switch_label <?php echo ($form_settings['style']['field_position'] == 'right') ? 'active' : ''; ?>"><?php esc_html_e('Right', 'ARMember'); ?></label>
                                                <input type="hidden" class="arm_switch_radio" name="arm_form_settings[style][field_position]" value="<?php echo esc_attr($form_settings['style']['form_position']); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font_settings_label"><?php esc_html_e('Font Settings', 'ARMember'); ?></td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Family', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_field_font_family" name="arm_form_settings[style][field_font_family]" class="arm_field_font_family" value="<?php echo !empty($form_settings['style']['field_font_family']) ? esc_attr($form_settings['style']['field_font_family']) : 'Helvetica'; ?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_150">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_field_font_family">
                                                        <?php echo $arm_member_forms->arm_fonts_list(); //phpcs:ignore?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Size', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_field_font_size" name="arm_form_settings[style][field_font_size]" class="arm_field_font_size" value="<?php echo isset($form_settings['style']['field_font_size']) ? esc_attr($form_settings['style']['field_font_size']) : '14'; //phpcs:ignore?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_120">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_field_font_size">
                                                        <?php
                                                        for ($i = 8; $i < 41; $i++) {
                                                            ?><li data-label="<?php echo esc_attr($i); ?>" data-value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></li><?php
                                                        }
                                                        ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                            <span>(px)</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Style', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <div class="arm_font_style_options">
                                                <!--/. Font Bold Option ./-->
                                                <label class="arm_font_style_label <?php echo ($form_settings['style']['field_font_bold'] == '1') ? 'arm_style_active' : ''; ?>" data-value="bold" data-field="arm_field_font_bold"><i class="armfa armfa-bold"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][field_font_bold]" id="arm_field_font_bold" class="arm_field_font_bold" value="<?php echo esc_attr($form_settings['style']['field_font_bold']); ?>" />
                                                <!--/. Font Italic Option ./-->
                                                <label class="arm_font_style_label <?php echo ($form_settings['style']['field_font_italic'] == '1') ? 'arm_style_active' : ''; ?>" data-value="italic" data-field="arm_field_font_italic"><i class="armfa armfa-italic"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][field_font_italic]" id="arm_field_font_italic" class="arm_field_font_italic" value="<?php echo esc_attr($form_settings['style']['field_font_italic']); ?>" />
                                                <!--/. Text Decoration Options ./-->
                                                <label class="arm_font_style_label arm_decoration_label <?php echo ($form_settings['style']['field_font_decoration'] == 'underline') ? 'arm_style_active' : ''; ?>" data-value="underline" data-field="arm_field_font_decoration"><i class="armfa armfa-underline"></i></label>
                                                <label class="arm_font_style_label arm_decoration_label <?php echo ($form_settings['style']['field_font_decoration'] == 'line-through') ? 'arm_style_active' : ''; ?>" data-value="line-through" data-field="arm_field_font_decoration"><i class="armfa armfa-strikethrough"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][field_font_decoration]" id="arm_field_font_decoration" class="arm_field_font_decoration" value="<?php echo esc_attr($form_settings['style']['field_font_decoration']); ?>" />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Text Direction', 'ARMember'); ?></td>
                                    <td colspan="2">
                                        <div class="arm_right">
                                            <div class="arm_switch arm_form_rtl_switch">
                                                <label data-value="0" class="arm_switch_label <?php echo ($is_rtl == '0') ? 'active' : ''; ?>"><?php esc_html_e('LTR', 'ARMember'); ?></label>
                                                <label data-value="1" class="arm_switch_label <?php echo ($is_rtl == '1') ? 'active' : ''; ?>"><?php esc_html_e('RTL', 'ARMember'); ?></label>
                                                <input type="hidden" class="arm_switch_radio arm_form_rtl_support_chk" name="arm_form_settings[style][rtl]" value="<?php echo esc_attr($is_rtl); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php if ($isRegister || $isEditProfile) { ?>
                                    <tr>
                                        <td class="font_settings_label"><?php esc_html_e('Calendar Style', 'ARMember'); ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Date Format', 'ARMember'); ?></td>
                                        <td colspan="2">
                                            <div class="arm_right">
                                                <?php

                                                $wp_default_dateFormatOpts = array('F d, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y');


                                                $dateFormatOpts = array('d/m/Y', 'm/d/Y', 'Y/m/d', 'M d, Y', 'F d, Y');
                                                $wp_format_date = get_option('date_format');
                                                if ($wp_format_date == 'F j, Y' || $wp_format_date == 'm/d/Y') {
                                                    $dateFormatOpts = array('m/d/Y', 'M d, Y', 'F d, Y');
                                                    
                                                        $dateFormatOpts = array_unique(array_merge($dateFormatOpts, $wp_default_dateFormatOpts));



                                                    if(!in_array($formDateFormat, $dateFormatOpts)){
                                                        if (in_array($formDateFormat, array('m/d/Y', 'd/m/Y', 'Y/m/d'))) {
                                                            $formDateFormat = 'm/d/Y';
                                                        } elseif (in_array($formDateFormat, array('M d, Y', 'd M, Y', 'Y, M d'))) {
                                                            $formDateFormat = 'M d, Y';
                                                        } elseif (in_array($formDateFormat, array('F d, Y', 'd F, Y', 'Y, F d'))) {
                                                            $formDateFormat = 'F d, Y';
                                                        }
                                                    }
                                                } else if ($wp_format_date == 'd/m/Y') {
                                                    $dateFormatOpts = array('d/m/Y', 'd M, Y', 'd F, Y');
                                                    $dateFormatOpts = array_unique(array_merge($dateFormatOpts, $wp_default_dateFormatOpts));

                                                    if(!in_array($formDateFormat, $dateFormatOpts)){
                                                        if (in_array($formDateFormat, array('m/d/Y', 'd/m/Y', 'Y/m/d'))) {
                                                            $formDateFormat = 'd/m/Y';
                                                        } elseif (in_array($formDateFormat, array('M d, Y', 'd M, Y', 'Y, M d'))) {
                                                            $formDateFormat = 'd M, Y';
                                                        } elseif (in_array($formDateFormat, array('F d, Y', 'd F, Y', 'Y, F d'))) {
                                                            $formDateFormat = 'd F, Y';
                                                        }
                                                    }
                                                } else if ($wp_format_date == 'Y/m/d') {
                                                    $dateFormatOpts = array('Y/m/d', 'Y, M d', 'Y, F d');

                                                    $dateFormatOpts = array_unique(array_merge($dateFormatOpts, $wp_default_dateFormatOpts));

                                                    if(!in_array($formDateFormat, $dateFormatOpts)){
                                                        if (in_array($formDateFormat, array('m/d/Y', 'd/m/Y', 'Y/m/d'))) {
                                                            $formDateFormat = 'Y/m/d';
                                                        } elseif (in_array($formDateFormat, array('M d, Y', 'd M, Y', 'Y, M d'))) {
                                                            $formDateFormat = 'Y, M d';
                                                        } elseif (in_array($formDateFormat, array('F d, Y', 'd F, Y', 'Y, F d'))) {
                                                            $formDateFormat = 'Y, F d';
                                                        }
                                                    }
                                                } else {
                                                    $dateFormatOpts = array('d/m/Y', 'm/d/Y', 'Y/m/d', 'M d, Y', 'F d, Y');
                                                }

                                                $dateFormatOpts = array_unique(array_merge($dateFormatOpts, $wp_default_dateFormatOpts));
                                                
                                                ?>
                                                <input type='hidden' id="arm_calendar_date_format" name="arm_form_settings[date_format]" class="arm_calendar_date_format armIgnore" value="<?php echo esc_attr($formDateFormat); ?>" />
                                                <dl class="arm_selectbox column_level_dd arm_width_150">
                                                    <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                    <dd>
                                                        <ul data-id="arm_calendar_date_format"><?php
                                                            foreach ($dateFormatOpts as $df) {
                                                                echo '<li data-label="' . date($df, current_time('timestamp')) . '" data-value="' . $df . '">' . date($df, current_time('timestamp')) . '</li>'; //phpcs:ignore
                                                            }
                                                            ?></ul>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Show Time', 'ARMember'); ?></td>
                                        <td colspan="2">
                                            <div class="arm_right">
                                                <div class="arm_switch arm_show_time_switch">
                                                    <label data-value="1" class="arm_switch_label <?php echo ($showTimePicker == '1') ? 'active' : ''; ?>"><?php esc_html_e('Yes', 'ARMember'); ?></label>
                                                    <label data-value="0" class="arm_switch_label <?php echo ($showTimePicker == '0') ? 'active' : ''; ?>"><?php esc_html_e('No', 'ARMember'); ?></label>
                                                    <input type="hidden" class="arm_switch_radio" name="arm_form_settings[show_time]" value="<?php echo esc_attr($showTimePicker); ?>">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="arm_accordion_header"><?php esc_html_e('Label Options', 'ARMember'); ?>:<i></i></a>
                        <div id="four" class="arm_accordion">
                            <table class="arm_form_settings_style_block arm_tbl_label_left_input_right">
                                <tr>
                                    <td><?php esc_html_e('Label Width', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type="text" id="arm_label_width" name="arm_form_settings[style][label_width]" class="arm_label_width arm_form_setting_input arm_width_140" value="<?php echo!empty($form_settings['style']['label_width']) ? esc_attr($form_settings['style']['label_width']) : '150'; ?>" onkeydown="javascript:return checkNumber(event)"/>&nbsp;(px)
                                            <input type='hidden' id="arm_label_width_type" name="arm_form_settings[style][label_width_type]" class="arm_label_width_type" value="px" />
                                        </div>
                                    </td>
                                </tr>
                                <tr class="arm_field_label_position_container">
                                    <td><?php esc_html_e('Position', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <?php
                                            $form_settings['style']['label_position'] = (!empty($form_settings['style']['label_position'])) ? $form_settings['style']['label_position'] : 'inline';
                                            ?>
                                            <div class="arm_switch arm_switch3 arm_label_position_switch">
                                                <label data-value="block" class="arm_switch_label <?php echo ($form_settings['style']['label_position'] == 'block') ? 'active' : ''; ?> <?php echo ($formLayout == 'writer_border') ? 'disable_section' : ''; ?>"><?php esc_html_e('Top', 'ARMember'); ?></label>
                                                <label data-value="inline" class="arm_switch_label <?php echo ($form_settings['style']['label_position'] == 'inline') ? 'active' : ''; ?> <?php echo ($formLayout == 'writer_border') ? 'disable_section' : ''; ?>"><?php esc_html_e('Left', 'ARMember'); ?></label>
                                                <label data-value="inline_right" class="arm_switch_label <?php echo ($form_settings['style']['label_position'] == 'inline_right') ? 'active' : ''; ?> <?php echo ($formLayout == 'writer_border') ? 'disable_section' : ''; ?>"><?php esc_html_e('Right', 'ARMember'); ?></label>
                                                <input type="hidden" class="arm_switch_radio" name="arm_form_settings[style][label_position]" value="<?php echo esc_attr($form_settings['style']['label_position']); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="arm_field_label_align_container">
                                    <td><?php esc_html_e('Align', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <div class="arm_switch arm_label_align_switch">
                                                <label data-value="left" class="arm_switch_label <?php echo ($form_settings['style']['label_align'] == 'left') ? 'active' : ''; ?> <?php echo ($formLayout == 'writer_border') ? 'disable_section' : ''; ?>"><?php esc_html_e('Left', 'ARMember'); ?></label>
                                                <label data-value="right" class="arm_switch_label <?php echo ($form_settings['style']['label_align'] == 'right') ? 'active' : ''; ?> <?php echo ($formLayout == 'writer_border') ? 'disable_section' : ''; ?>"><?php esc_html_e('Right', 'ARMember'); ?></label>
                                                <input type="hidden" class="arm_switch_radio" name="arm_form_settings[style][label_align]" value="<?php echo esc_attr($form_settings['style']['label_align']); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="arm_field_label_hide_container <?php echo ($formLayout == 'writer' || $formLayout == 'writer_border') ? 'hidden_section' : ''; ?>">
                                    <td><?php esc_html_e('Hide Label', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <?php $form_settings['style']['label_hide'] = (!empty($form_settings['style']['label_hide'])) ? $form_settings['style']['label_hide'] : '0'; ?>
                                            <div class="arm_switch arm_label_hide_switch">
                                                <label data-value="1" class="arm_switch_label <?php echo ($form_settings['style']['label_hide'] == '1') ? 'active' : ''; ?> <?php echo ($formLayout == 'writer_border') ? 'disable_section' : ''; ?>" ><?php esc_html_e('Yes', 'ARMember'); ?></label>
                                                <label data-value="0" class="arm_switch_label <?php echo ($form_settings['style']['label_hide'] == '0') ? 'active' : ''; ?> <?php echo ($formLayout == 'writer_border') ? 'disable_section' : ''; ?>"><?php esc_html_e('No', 'ARMember'); ?></label>
                                                <input type="hidden" class="arm_switch_radio" name="arm_form_settings[style][label_hide]" value="<?php echo esc_attr($form_settings['style']['label_hide']); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font_settings_label"><?php esc_html_e('Font Settings', 'ARMember'); ?></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Family', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_label_font_family" name="arm_form_settings[style][label_font_family]" class="arm_label_font_family" value="<?php echo !empty($form_settings['style']['label_font_family']) ? esc_attr($form_settings['style']['label_font_family']) : 'Helvetica'; //phpcs:ignore?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_150">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_label_font_family">
                                                        <?php echo $arm_member_forms->arm_fonts_list(); //phpcs:ignore?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Size', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_label_font_size" name="arm_form_settings[style][label_font_size]" class="arm_label_font_size" value="<?php echo !empty($form_settings['style']['label_font_size']) ? esc_attr($form_settings['style']['label_font_size']) : '16'; //phpcs:ignore?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_120">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_label_font_size">
                                                        <?php
                                                        for ($i = 8; $i < 41; $i++) {
                                                            ?><li data-label="<?php echo esc_attr($i); ?>" data-value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></li><?php
                                                        }
                                                        ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                            <span>(px)</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Desc. Font Size', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_description_font_size" name="arm_form_settings[style][description_font_size]" class="arm_description_font_size" value="<?php echo !empty($form_settings['style']['description_font_size']) ? esc_attr($form_settings['style']['description_font_size']) : '16'; //phpcs:ignore?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_120">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_description_font_size">
                                                        <?php
                                                        for ($i = 8; $i < 41; $i++) {
                                                            ?><li data-label="<?php echo esc_attr($i); ?>" data-value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></li><?php
                                                        }
                                                        ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                            <span>(px)</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Style', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <div class="arm_font_style_options">
                                                <!--/. Font Bold Option ./-->
                                                <label class="arm_font_style_label <?php echo ($form_settings['style']['label_font_bold'] == '1') ? 'arm_style_active' : ''; ?>" data-value="bold" data-field="arm_label_font_bold"><i class="armfa armfa-bold"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][label_font_bold]" id="arm_label_font_bold" class="arm_label_font_bold" value="<?php echo esc_attr($form_settings['style']['label_font_bold']); ?>" />
                                                <!--/. Font Italic Option ./-->
                                                <label class="arm_font_style_label <?php echo ($form_settings['style']['label_font_italic'] == '1') ? 'arm_style_active' : ''; ?>" data-value="italic" data-field="arm_label_font_italic"><i class="armfa armfa-italic"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][label_font_italic]" id="arm_label_font_italic" class="arm_label_font_italic" value="<?php echo esc_attr($form_settings['style']['label_font_italic']); ?>" />
                                                <!--/. Text Decoration Options ./-->
                                                <label class="arm_font_style_label arm_decoration_label <?php echo ($form_settings['style']['label_font_decoration'] == 'underline') ? 'arm_style_active' : ''; ?>" data-value="underline" data-field="arm_label_font_decoration"><i class="armfa armfa-underline"></i></label>
                                                <label class="arm_font_style_label arm_decoration_label <?php echo ($form_settings['style']['label_font_decoration'] == 'line-through') ? 'arm_style_active' : ''; ?>" data-value="line-through" data-field="arm_label_font_decoration"><i class="armfa armfa-strikethrough"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][label_font_decoration]" id="arm_label_font_decoration" class="arm_label_font_decoration" value="<?php echo esc_attr($form_settings['style']['label_font_decoration']); ?>" />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="arm_accordion_header"><?php esc_html_e('Submit Button Options', 'ARMember'); ?>:<i></i></a>
                        <div id="five" class="arm_accordion">
                            <table class="arm_form_settings_style_block arm_tbl_label_left_input_right">
                                <tr>
                                    <td><?php esc_html_e('Width', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type="text" id="arm_button_width" name="arm_form_settings[style][button_width]" class="arm_button_width arm_form_setting_input arm_width_140" value="<?php echo !empty($form_settings['style']['button_width']) ? esc_attr($form_settings['style']['button_width']) : '150'; //phpcs:ignore?>" onkeydown="javascript:return checkNumber(event)"/><span>&nbsp;(px)</span>
                                            <input type='hidden' id="arm_button_width_type" name="arm_form_settings[style][button_width_type]" class="arm_button_width_type" value="<?php echo !empty($form_settings['style']['button_width_type']) ? esc_attr($form_settings['style']['button_width_type']) : 'px'; //phpcs:ignore?>" />
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Height', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type="text" id="arm_button_height" name="arm_form_settings[style][button_height]" class="arm_button_height arm_form_setting_input arm_width_140" value="<?php echo !empty($form_settings['style']['button_height']) ? esc_attr($form_settings['style']['button_height']) : '35'; //phpcs:ignore?>" onkeydown="javascript:return checkNumber(event)"/><span>&nbsp;(px)</span>
                                            <input type='hidden' id="arm_button_height_type" name="arm_form_settings[style][button_height_type]" class="arm_button_height_type" value="<?php echo !empty($form_settings['style']['button_height_type']) ? esc_attr($form_settings['style']['button_height_type']) : 'px'; //phpcs:ignore?>" />
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Border Radius', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type="text" id="arm_button_border_radius" name="arm_form_settings[style][button_border_radius]" class="arm_button_border_radius arm_form_setting_input arm_width_140" value="<?php echo isset($form_settings['style']['button_border_radius']) ? esc_attr($form_settings['style']['button_border_radius']) : '4'; //phpcs:ignore?>" onkeydown="javascript:return checkNumber(event)"/><span>&nbsp;(px)</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Button Style', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_button_style" name="arm_form_settings[style][button_style]" class="arm_button_style" value="<?php echo !empty($form_settings['style']['button_style']) ? esc_attr($form_settings['style']['button_style']) : 'flat'; //phpcs:ignore?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_150">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_button_style">
                                                        <li data-value="flat" data-label="<?php esc_html_e('Flat', 'ARMember'); ?>"><?php esc_html_e('Flat', 'ARMember'); ?></li>
                                                        <li data-value="classic" data-label="<?php esc_html_e('Classic', 'ARMember'); ?>"><?php esc_html_e('Classic', 'ARMember'); ?></li>
                                                        <li data-value="border" data-label="<?php esc_html_e('Border', 'ARMember'); ?>"><?php esc_html_e('Border', 'ARMember'); ?></li>
                                                        <li data-value="reverse_border" data-label="<?php esc_html_e('Reverse Border', 'ARMember'); ?>"><?php esc_html_e('Reverse Border', 'ARMember'); ?></li>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font_settings_label"><?php esc_html_e('Font Settings', 'ARMember'); ?></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Family', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_button_font_family" name="arm_form_settings[style][button_font_family]" class="arm_button_font_family" value="<?php echo !empty($form_settings['style']['button_font_family']) ? esc_attr($form_settings['style']['button_font_family']) : 'Helvetica'; ?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_150">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_button_font_family">
                                                        <?php echo $arm_member_forms->arm_fonts_list(); //phpcs:ignore?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Size', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <input type='hidden' id="arm_button_font_size" name="arm_form_settings[style][button_font_size]" class="arm_button_font_size" value="<?php echo !empty($form_settings['style']['button_font_size']) ? esc_attr($form_settings['style']['button_font_size']) : '16'; ?>" />
                                            <dl class="arm_selectbox column_level_dd arm_width_130">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_button_font_size">
                                                        <?php
                                                        for ($i = 8; $i < 41; $i++) {
                                                            ?><li data-label="<?php echo esc_attr($i); ?>" data-value="<?php echo esc_attr($i); ?>"><?php echo esc_attr($i); ?></li><?php
                                                        }
                                                        ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                            <span>px</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Font Style', 'ARMember'); ?></td>
                                    <td>
                                        <div class="arm_right">
                                            <div class="arm_font_style_options">
                                                <!--/. Font Bold Option ./-->
                                                <label class="arm_font_style_label <?php echo ($form_settings['style']['button_font_bold'] == '1') ? 'arm_style_active' : ''; ?>" data-value="bold" data-field="arm_button_font_bold"><i class="armfa armfa-bold"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][button_font_bold]" id="arm_button_font_bold" class="arm_button_font_bold" value="<?php echo esc_attr($form_settings['style']['button_font_bold']); ?>" />
                                                <!--/. Font Italic Option ./-->
                                                <label class="arm_font_style_label <?php echo ($form_settings['style']['button_font_italic'] == '1') ? 'arm_style_active' : ''; ?>" data-value="italic" data-field="arm_button_font_italic"><i class="armfa armfa-italic"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][button_font_italic]" id="arm_button_font_italic" class="arm_button_font_italic" value="<?php echo esc_attr($form_settings['style']['button_font_italic']); ?>" />
                                                <!--/. Text Decoration Options ./-->
                                                <label class="arm_font_style_label arm_decoration_label <?php echo ($form_settings['style']['button_font_decoration'] == 'underline') ? 'arm_style_active' : ''; ?>" data-value="underline" data-field="arm_button_font_decoration"><i class="armfa armfa-underline"></i></label>
                                                <label class="arm_font_style_label arm_decoration_label <?php echo ($form_settings['style']['button_font_decoration'] == 'line-through') ? 'arm_style_active' : ''; ?>" data-value="line-through" data-field="arm_button_font_decoration"><i class="armfa armfa-strikethrough"></i></label>
                                                <input type="hidden" name="arm_form_settings[style][button_font_decoration]" id="arm_button_font_decoration" class="arm_button_font_decoration" value="<?php echo esc_attr($form_settings['style']['button_font_decoration']); ?>" />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php esc_html_e('Margin', 'ARMember'); ?>
                                    </td>
                                    <td style="padding-right: 0;">
                                        <?php
                                        $form_settings['style']['button_margin_left'] = (is_numeric($form_settings['style']['button_margin_left'])) ? $form_settings['style']['button_margin_left'] : 0;
                                        $form_settings['style']['button_margin_top'] = (is_numeric($form_settings['style']['button_margin_top'])) ? $form_settings['style']['button_margin_top'] : 0;
                                        $form_settings['style']['button_margin_right'] = (is_numeric($form_settings['style']['button_margin_right'])) ? $form_settings['style']['button_margin_right'] : 0;
                                        $form_settings['style']['button_margin_bottom'] = (is_numeric($form_settings['style']['button_margin_bottom'])) ? $form_settings['style']['button_margin_bottom'] : 0;
                                        ?>
                                        <div class="arm_button_margin_inputs_container">
                                            <div class="arm_button_margin_inputs">
                                                <input type="text" name="arm_form_settings[style][button_margin_left]" id="arm_button_margin_left" class="arm_button_margin_left" value="<?php echo esc_attr($form_settings['style']['button_margin_left']); ?>"/>
                                                <br /><?php esc_html_e('Left', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_button_margin_inputs">
                                                <input type="text" name="arm_form_settings[style][button_margin_top]" id="arm_button_margin_top" class="arm_button_margin_top" value="<?php echo esc_attr($form_settings['style']['button_margin_top']); ?>"/>
                                                <br /><?php esc_html_e('Top', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_button_margin_inputs">
                                                <input type="text" name="arm_form_settings[style][button_margin_right]" id="arm_button_margin_right" class="arm_button_margin_right" value="<?php echo esc_attr($form_settings['style']['button_margin_right']); ?>"/>
                                                <br /><?php esc_html_e('Right', 'ARMember'); ?>
                                            </div>
                                            <div class="arm_button_margin_inputs">
                                                <input type="text" name="arm_form_settings[style][button_margin_bottom]" id="arm_button_margin_bottom" class="arm_button_margin_bottom" value="<?php echo esc_attr($form_settings['style']['button_margin_bottom']); ?>"/>
                                                <br /><?php esc_html_e('Bottom', 'ARMember'); ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Button Position', 'ARMember'); ?></td>															<td>
                                        <div class="arm_right">
                                            <?php $form_settings['style']['button_position'] = (!empty($form_settings['style']['button_position'])) ? $form_settings['style']['button_position'] : 'left'; ?>
                                            <div class="arm_switch arm_switch3 arm_button_position_switch">
                                                <label data-value="left" class="arm_switch_label <?php echo ($form_settings['style']['button_position'] == 'left') ? 'active' : ''; ?>"><?php esc_html_e('Left', 'ARMember'); ?></label>
                                                <label data-value="center" class="arm_switch_label <?php echo ($form_settings['style']['button_position'] == 'center') ? 'active' : ''; ?>"><?php esc_html_e('Center', 'ARMember'); ?></label>
                                                <label data-value="right" class="arm_switch_label <?php echo ($form_settings['style']['button_position'] == 'right') ? 'active' : ''; ?>"><?php esc_html_e('Right', 'ARMember'); ?></label>
                                                <input type="hidden" class="arm_switch_radio" name="arm_form_settings[style][button_position]" value="<?php echo esc_attr($form_settings['style']['button_position']); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="arm_accordion_header"><?php esc_html_e('Custom Css', 'ARMember'); ?>:<i></i></a>
                        <div id="six" class="arm_accordion">
                            <div class="arm_form_settings_style_block arm_form_custom_css_wrapper">
                                <textarea name="arm_form_settings[custom_css]" col="40" row="10"><?php echo isset($form_settings['custom_css']) ? stripslashes_deep( esc_attr($form_settings['custom_css']) ) : ''; //phpcs:ignore?></textarea>
                            </div>
                            <div class="arm_form_settings_custom_style_block">
                                <span class="arm_section_custom_css_eg">(e.g.)&nbsp;&nbsp; .arm-df__form-control-submit-btn{color:#000000;}</span>
                                <span class="arm_section_custom_css_section" style="display: inline-block;">
                                    <a class="arm_section_custom_css_detail arm_section_custom_css_detail_link" href="javascript:void(0)" data-section="arm_form" style="padding: 0px;"><?php esc_html_e('CSS Class Information', 'ARMember'); ?></a>
                                </span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="armclear"></div>
        </div>
    </div>
<?php
/**
 * Social Profile Fields Popup (Social Network List)
 */
$socialProfileFields = $arm_member_forms->arm_social_profile_field_types();
$activeSPF = array('facebook', 'twitter', 'linkedin');
if (!empty($socialFieldsOptions)) {
    $activeSPF = isset($socialFieldsOptions['arm_form_field_option']['options']) ? $socialFieldsOptions['arm_form_field_option']['options'] : array();
}
$activeSPF = (!empty($activeSPF)) ? $activeSPF : array();
?>

<?php echo $form_styles; //phpcs:ignore?>
<?php
/* Angular JS */
$ARMember->enqueue_angular_script();
?>
<style type="text/css">#wpbody-content{padding:0;}html{background: #FFFFFF;}#adminmenuwrap{z-index: 9970;}</style>
<?php
$arm_form_css = $arm_member_forms->arm_ajax_generate_form_styles($form_id, $form_settings, array(), $reference_template);
if (isset($arm_form_css['arm_link']) && !empty($arm_form_css['arm_link'])) {
    echo $arm_form_css['arm_link']; //phpcs:ignore
} else {
    echo '<link id="google-font-' . esc_attr($form_id) . '" rel="stylesheet" type="text/css" href="#" />';
}
?>
<script type="text/javascript">
    var ARM_REMOVE_IMAGE_ICON = '<?php echo MEMBERSHIPLITE_IMAGES_URL?>/delete.svg';
    var ARM_REMOVE_IMAGE_ICON_HOVER = '<?php echo MEMBERSHIPLITE_IMAGES_URL?>/delete_hover.svg';
</script>
