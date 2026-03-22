<?php
global $wpdb, $ARMember, $arm_global_settings, $arm_access_rules, $arm_drip_rules, $arm_subscription_plans, $arm_member_forms, $arm_social_feature,$arm_pay_per_post_feature;


$redirection_settings = get_option('arm_redirection_settings');
$redirection_settings = maybe_unserialize($redirection_settings);

$arm_forms = $arm_member_forms->arm_get_member_forms_and_fields_by_type('registration', 'arm_form_id, arm_form_type, arm_form_label', false);

$arm_edit_profile_forms = $arm_member_forms->arm_get_member_forms_and_fields_by_type('edit_profile', 'arm_form_id, arm_form_type, arm_form_label', false);

$arm_redirection_login_type_main = (isset($redirection_settings['login']['main_type']) && !empty($redirection_settings['login']['type'])) ? $redirection_settings['login']['main_type'] : 'fixed';
$arm_redirection_login_type = (isset($redirection_settings['login']['type']) && !empty($redirection_settings['login']['type'])) ? $redirection_settings['login']['type'] : 'page';
$arm_redirection_signup_redirection_type = (isset($redirection_settings['signup']['redirect_type']) && !empty($redirection_settings['signup']['redirect_type'])) ? $redirection_settings['signup']['redirect_type'] : 'common';
$arm_redirection_signup_type = (isset($redirection_settings['signup']['type']) && !empty($redirection_settings['signup']['type'])) ? $redirection_settings['signup']['type'] : 'page';

$arm_redirection_edit_profile_redirection_type = (isset($redirection_settings['edit_profile']['redirect_type']) && !empty($redirection_settings['edit_profile']['redirect_type'])) ? $redirection_settings['edit_profile']['redirect_type'] : 'message';
$arm_redirection_edit_profile_type = (isset($redirection_settings['edit_profile']['type']) && !empty($redirection_settings['edit_profile']['type'])) ? $redirection_settings['edit_profile']['type'] : 'page';


$arm_redirection_social_type = (isset($redirection_settings['social']['type']) && !empty($redirection_settings['social']['type'])) ? $redirection_settings['social']['type'] : 'page';
$arm_default_signup_url = (isset($redirection_settings['signup']['default']) && !empty($redirection_settings['signup']['default'])) ? $redirection_settings['signup']['default'] : ARM_HOME_URL;

$arm_default_edit_profile_url = (isset($redirection_settings['edit_profile']['default']) && !empty($redirection_settings['edit_profile']['default'])) ? $redirection_settings['edit_profile']['default'] : ARM_HOME_URL;

$arm_all_global_settings = $arm_global_settings->arm_get_all_global_settings();
$page_settings = $arm_all_global_settings['page_settings'];

$edit_profile_page_id = isset($page_settings['edit_profile_page_id']) ? $page_settings['edit_profile_page_id'] : 0;
$arm_redirection_login_page_id = (isset($redirection_settings['login']['page_id']) && !empty($redirection_settings['login']['page_id'])) ? $redirection_settings['login']['page_id'] : 0;
$arm_redirection_login_url = (isset($redirection_settings['login']['url']) && !empty($redirection_settings['login']['url'])) ? $redirection_settings['login']['url'] : '';
$arm_redirection_login_refferel = (isset($redirection_settings['login']['refferel']) && !empty($redirection_settings['login']['refferel'])) ? $redirection_settings['login']['refferel'] : '';
$arm_redirection_login_conditional = (isset($redirection_settings['login']['conditional_redirect']) && !empty($redirection_settings['login']['conditional_redirect'])) ? $redirection_settings['login']['conditional_redirect'] : array();
$arm_redirection_signup_conditional = (isset($redirection_settings['signup']['conditional_redirect']) && !empty($redirection_settings['signup']['conditional_redirect'])) ? $redirection_settings['signup']['conditional_redirect'] : array();
$arm_redirection_signup_refferel = (isset($redirection_settings['signup']['refferel']) && !empty($redirection_settings['signup']['refferel'])) ? $redirection_settings['signup']['refferel'] : ARM_HOME_URL;

$arm_redirection_edit_profile_conditional = (isset($redirection_settings['edit_profile']['conditional_redirect']) && !empty($redirection_settings['edit_profile']['conditional_redirect'])) ? $redirection_settings['edit_profile']['conditional_redirect'] : array();



$arm_redirection_setup_signup_type = (isset($redirection_settings['setup_signup']['type']) && !empty($redirection_settings['setup_signup']['type'])) ? $redirection_settings['setup_signup']['type'] : 'page';
$arm_redirection_setup_signup_page_id = (isset($redirection_settings['setup_signup']['page_id']) && !empty($redirection_settings['setup_signup']['page_id'])) ? $redirection_settings['setup_signup']['page_id'] : 0;
$arm_redirection_setup_signup_url = (isset($redirection_settings['setup_signup']['url']) && !empty($redirection_settings['setup_signup']['url'])) ? $redirection_settings['setup_signup']['url'] : ARM_HOME_URL;
$arm_redirection_setup_signup_conditional_redirect = (isset($redirection_settings['setup_signup']['conditional_redirect']) && !empty($redirection_settings['setup_signup']['conditional_redirect'])) ? $redirection_settings['setup_signup']['conditional_redirect'] : array();

$arm_redirection_setup_paid_post_type=(isset($redirection_settings['setup_paid_post']['type']) && !empty($redirection_settings['setup_paid_post']['type'])) ? $redirection_settings['setup_paid_post']['type'] : '0';
$arm_redirection_setup_paid_post_page_id = (isset($redirection_settings['setup_paid_post']['page_id']) && !empty($redirection_settings['setup_paid_post']['page_id'])) ? $redirection_settings['setup_paid_post']['page_id'] : 0;

$arm_redirection_setup_change_type = (isset($redirection_settings['setup_change']['type']) && !empty($redirection_settings['setup_change']['type'])) ? $redirection_settings['setup_change']['type'] : 'page';
$arm_redirection_setup_change_page_id = (isset($redirection_settings['setup_change']['type']) && !empty($redirection_settings['setup_change']['page_id'])) ? $redirection_settings['setup_change']['page_id'] : 0;
$arm_redirection_setup_change_url = (isset($redirection_settings['setup_change']['url']) && !empty($redirection_settings['setup_change']['url'])) ? $redirection_settings['setup_change']['url'] : ARM_HOME_URL;


$arm_redirection_setup_renew_type = (isset($redirection_settings['setup_renew']['type']) && !empty($redirection_settings['setup_renew']['type'])) ? $redirection_settings['setup_renew']['type'] : 'page';
$arm_redirection_setup_renew_page_id = (isset($redirection_settings['setup_renew']['type']) && !empty($redirection_settings['setup_renew']['page_id'])) ? $redirection_settings['setup_renew']['page_id'] : 0;
$arm_redirection_setup_renew_url = (isset($redirection_settings['setup_renew']['url']) && !empty($redirection_settings['setup_renew']['url'])) ? $redirection_settings['setup_renew']['url'] : ARM_HOME_URL;
$arm_default_setup_url = (isset($redirection_settings['setup']['default']) && !empty($redirection_settings['setup']['default'])) ? $redirection_settings['setup']['default'] : ARM_HOME_URL;

$arm_redirection_signup_page_id = (isset($redirection_settings['signup']['page_id']) && !empty($redirection_settings['signup']['page_id'])) ? $redirection_settings['signup']['page_id'] : 0;
$arm_redirection_signup_url = (isset($redirection_settings['signup']['url']) && !empty($redirection_settings['signup']['url'])) ? $redirection_settings['signup']['url'] : '';

$arm_redirection_edit_profile_page_id = (isset($redirection_settings['edit_profile']['page_id']) && !empty($redirection_settings['edit_profile']['page_id'])) ? $redirection_settings['edit_profile']['page_id'] : 0;
$arm_redirection_edit_profile_url = (isset($redirection_settings['edit_profile']['url']) && !empty($redirection_settings['edit_profile']['url'])) ? $redirection_settings['edit_profile']['url'] : '';

$arm_redirection_social_page_id = (isset($redirection_settings['social']['page_id']) && !empty($redirection_settings['social']['page_id'])) ? $redirection_settings['social']['page_id'] : 0;
$arm_redirection_social_url = (isset($redirection_settings['social']['url']) && !empty($redirection_settings['social']['url'])) ? $redirection_settings['social']['url'] : '';

$arm_redirection_oneclick = (isset($redirection_settings['oneclick']['redirect_to']) && !empty($redirection_settings['oneclick']['redirect_to'])) ? $redirection_settings['oneclick']['redirect_to'] : 0;

$arm_default_redirection_rules = (isset($redirection_settings['default_access_rules']) && !empty($redirection_settings['default_access_rules'])) ? $redirection_settings['default_access_rules'] : array();

$arm_non_logged_in_type = $arm_logged_in_type = $arm_drip_type = $arm_blocked_type = $arm_pending_type = 'home'; 
$arm_non_logged_in_redirect_to = $arm_logged_in_redirect_to = $arm_drip_redirect_to = $arm_blocked_redirect_to = $arm_pending_redirect_to = 0;



if(!empty($arm_default_redirection_rules)){
    $arm_non_logged_in_type = (isset($arm_default_redirection_rules['non_logged_in']['type']) && !empty($arm_default_redirection_rules['non_logged_in']['type'])) ? $arm_default_redirection_rules['non_logged_in']['type'] : 'home'; 
    $arm_non_logged_in_redirect_to = (isset($arm_default_redirection_rules['non_logged_in']['redirect_to']) && !empty($arm_default_redirection_rules['non_logged_in']['redirect_to'])) ? $arm_default_redirection_rules['non_logged_in']['redirect_to'] : 0; 
    
    $arm_logged_in_type = (isset($arm_default_redirection_rules['logged_in']['type']) && !empty($arm_default_redirection_rules['logged_in']['type'])) ? $arm_default_redirection_rules['logged_in']['type'] : 'home'; 
    $arm_logged_in_redirect_to = (isset($arm_default_redirection_rules['logged_in']['redirect_to']) && !empty($arm_default_redirection_rules['logged_in']['redirect_to'])) ? $arm_default_redirection_rules['logged_in']['redirect_to'] : 0; 

    $arm_drip_type = (isset($arm_default_redirection_rules['drip']['type']) && !empty($arm_default_redirection_rules['drip']['type'])) ? $arm_default_redirection_rules['drip']['type'] : 'home'; 
    $arm_drip_redirect_to = (isset($arm_default_redirection_rules['drip']['redirect_to']) && !empty($arm_default_redirection_rules['drip']['redirect_to'])) ? $arm_default_redirection_rules['drip']['redirect_to'] : 0; 
    
    $arm_blocked_type = (isset($arm_default_redirection_rules['blocked']['type']) && !empty($arm_default_redirection_rules['blocked']['type'])) ? $arm_default_redirection_rules['blocked']['type'] : 'home'; 
    $arm_blocked_redirect_to = (isset($arm_default_redirection_rules['blocked']['redirect_to']) && !empty($arm_default_redirection_rules['blocked']['redirect_to'])) ? $arm_default_redirection_rules['blocked']['redirect_to'] : 0; 
    
    //$arm_pending_type = (isset($arm_default_redirection_rules['pending']['type']) && !empty($arm_default_redirection_rules['pending']['type'])) ? $arm_default_redirection_rules['pending']['type'] : 'home'; 
    //$arm_pending_redirect_to = (isset($arm_default_redirection_rules['pending']['redirect_to']) && !empty($arm_default_redirection_rules['pending']['redirect_to'])) ? $arm_default_redirection_rules['pending']['redirect_to'] : 0; 

}
$all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name');
$arm_redirection_rule_html ='';

if($section == 'login_selection_section')
{
    $is_fixed_redirection_checked = ($arm_redirection_login_type_main == 'fixed') ? "checked='checked'": '';
    $is_conditional_redirection_checked = ($arm_redirection_login_type_main == 'conditional_redirect') ? "checked='checked'": '';
    $arm_redirection_rule_html ='<div class="arm_setting_main_content arm_padding_0">
        <div class="arm_row_wrapper arm_row_wrapper_padding_before">
            <div class="left_content">
                <div class="arm_form_header_label arm-setting-hadding-label">'. esc_html__('Select Redirection Type','ARMember').'</div>
            </div>
        </div>
        <div class="arm_content_border"></div>
        <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block redirection_rules_type_wrapper">                     
            <label class="arm_min_width_100">
                    <input type="radio" name="arm_redirection_settings[login][main_type]" value="fixed" class="arm_redirection_settings_login_radio_type arm_iradio" '.$is_fixed_redirection_checked.'><span>'. esc_html__('Fixed Redirection','ARMember').'</span>
            </label>
            <label class="arm_min_width_100">
                    <input type="radio" name="arm_redirection_settings[login][main_type]" value="conditional_redirect" class="arm_redirection_settings_login_radio_type arm_iradio" '.$is_conditional_redirection_checked.'><span>'. esc_html__('Conditional Redirection','ARMember').'</span>
            </label>
        </div>
    </div>';
}
if($section == 'signup_selection_section')
{
    $is_signup_common_redirection_checked = ($arm_redirection_signup_redirection_type == 'common') ? "checked='checked'" : '';
    $is_signup_formwise_redirection_checked = ($arm_redirection_signup_redirection_type == 'formwise') ? "checked='checked'" : '';
    $arm_redirection_rule_html ='<div class="arm_setting_main_content arm_padding_0 arm_margin_bottom_24 arm_margin_top_24">
        <div class="arm_row_wrapper arm_row_wrapper_padding_before">
            <div class="left_content">
                <div class="arm_form_header_label arm-setting-hadding-label">'. esc_html__('Select Redirection Type', 'ARMember').'</div>
          </div>
        </div>
        <div class="arm_content_border"></div>
        <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block redirection_rules_type_wrapper">                     
            <label class="arm_min_width_100">
                    <input type="radio" name="arm_redirection_settings[signup][redirect_type]" value="common" class="arm_redirection_settings_signup_redirection_type arm_iradio" '.$is_signup_common_redirection_checked.'><span>'. esc_html__('Fixed Redirection','ARMember').'</span>
            </label>
            <label class="arm_min_width_100">
                    <input type="radio" name="arm_redirection_settings[signup][redirect_type]" value="formwise" class="arm_redirection_settings_signup_redirection_type arm_iradio" '.$is_signup_formwise_redirection_checked.' ><span>'. esc_html__('Form wise redirection','ARMember').'</span>
            </label>
            
        </div>
    </div>';
}
if($section == 'after_edit_profile_selection_section')
{
    $arm_redirection_edit_profile_redirection_type_common = ($arm_redirection_edit_profile_redirection_type == 'common') ? "checked='checked'" : '';
    $arm_redirection_edit_profile_redirection_type_conditional = ($arm_redirection_edit_profile_redirection_type == 'formwise')? "checked='checked'" : '';
    $arm_redirection_edit_profile_redirection_type_message = ($arm_redirection_edit_profile_redirection_type == 'message')? "checked='checked'" : '';

    $is_common_type_hidden = ($arm_redirection_edit_profile_redirection_type != 'common') ? 'hidden_section' : '';

    $arm_redirection_edit_profile_type_page_checked = ($arm_redirection_edit_profile_type == 'page') ? "checked='checked'" : '';

    $arm_redirection_edit_profile_type_url_checked = ($arm_redirection_edit_profile_type == 'url') ? "checked='checked'" : '';

    $arm_redirection_rule_html ='
    <div class="page_sub_title arm_font_size_18 arm_font_weight_500 arm_margin_top_48">'. esc_html__('After Edit Profile Form Redirection Rules','ARMember').'</div>
    <div class="arm_setting_main_content arm_padding_0 arm_margin_top_24">
			<div class="arm_row_wrapper arm_row_wrapper_padding_before">
				<div class="left_content">
					<div class="arm_form_header_label arm-setting-hadding-label">'. esc_html__('Select Redirection Type', 'ARMember').'</div>
				</div>
			</div>
			<div class="arm_content_border"></div>
            <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block redirection_rules_type_wrapper">
            <div class="redirection_rules_type ">                     
                <label class="arm_min_width_100">
                        <input type="radio" name="arm_redirection_settings[edit_profile][redirect_type]" value="common" class="arm_redirection_settings_edit_profile_redirection_type arm_iradio" '.$arm_redirection_edit_profile_redirection_type_common.'><span>'. esc_html__('Fixed Redirection','ARMember').'</span>
                </label>
                <label class="arm_min_width_100">
                        <input type="radio" name="arm_redirection_settings[edit_profile][redirect_type]" value="formwise" class="arm_redirection_settings_edit_profile_redirection_type arm_iradio" '.$arm_redirection_edit_profile_redirection_type_conditional.' ><span>'. esc_html__('Form wise redirection','ARMember').'</span>
                </label>
                <label class="arm_min_width_100">
                        <input type="radio" name="arm_redirection_settings[edit_profile][redirect_type]" value="message" class="arm_redirection_settings_edit_profile_redirection_type arm_iradio" '.$arm_redirection_edit_profile_redirection_type_message.'><span>'. esc_html__('Success Message','ARMember').'</span>
                </label>    
            </div></div></div>';
        

        $arm_redirection_rule_html .='<div class="arm_redirection_edit_profile_common_settings arm_redirection_settings_edit_profile '.$is_common_type_hidden.' arm_setting_main_content arm_padding_0 arm_margin_top_24">
            <div class="arm_row_wrapper arm_row_wrapper_padding_before">
                 <div class="left_content">
                     <div class="arm_form_header_label arm-setting-hadding-label">'. esc_html__('Default Redirect To','ARMember').'</div>
                </div>
            </div>
            <div class="arm_content_border"></div>
            <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block">                     
                <label class="arm_min_width_100">
                        <input type="radio" name="arm_redirection_settings[edit_profile][type]" value="page" class="arm_redirection_settings_edit_profile_radio arm_iradio" '.$arm_redirection_edit_profile_type_page_checked.'><span>'. esc_html__('Specific Page','ARMember').'</span>
                </label>
                <label class="arm_min_width_100">
                        <input type="radio" name="arm_redirection_settings[edit_profile][type]" value="url" class="arm_redirection_settings_edit_profile_radio arm_iradio" '.$arm_redirection_edit_profile_type_url_checked.' ><span>'. esc_html__('Specific URL','ARMember').'</span>
                </label>';
        $arm_edit_profile_common_section = ($arm_redirection_edit_profile_type != 'page' || $arm_redirection_edit_profile_redirection_type != 'common' ) ? 'hidden_section' : '';
        
        $arm_redirection_rule_html .='<div id="arm_redirection_edit_profile_settings_page" class="arm_redirection_edit_profile_common_settings arm_redirection_settings_edit_profile arm_edit_profile_settings_common  '.$arm_edit_profile_common_section.' arm_margin_top_24 arm_redirection_settings_selectbox">
                <div class="arm_default_redirection_lbl arm_padding_0 arm_info_text_select_page arm_margin_0">'. esc_html__('Select Page', 'ARMember').'</div>
                <div class="arm_default_redirection_txt">
                   <dl class="arm_margin_0">'.
                    $arm_global_settings->arm_wp_dropdown_pages(
                            array(
                                    'selected' => $arm_redirection_edit_profile_page_id,
                                    'name' => 'arm_redirection_settings[edit_profile][page_id]',
                                    'id' => 'form_action_edit_profile_page',
                                    'show_option_none' => esc_html__('Select Page', 'ARMember'),
                                    'option_none_value' => '',
                                    'class' => 'form_action_edit_profile_page ',
                                    'required' => true,
                                    'echo' => 0,
                                    'required_msg' => esc_html__('Please select redirection page.', 'ARMember'),
                            )
                    )
                    .'</dl>
                    <span class="arm_redirection_edit_profile_page_selection">'. esc_html__('Please select Page.', 'ARMember').'</span> 
                </div>
        </div>';
        $arm_edit_profile_common_section = ($arm_redirection_edit_profile_type != 'url' || $arm_redirection_edit_profile_redirection_type != 'common') ? 'hidden_section' : '';

        $arm_redirection_rule_html .='<div id="arm_redirection_edit_profile_settings_url" class="arm_redirection_edit_profile_common_settings arm_redirection_settings_edit_profile arm_edit_profile_settings_common '.$arm_edit_profile_common_section.' arm_margin_top_32">
                <div class="arm_default_redirection_lbl arm_info_text arm_padding_0 ">'. esc_html__('Add URL', 'ARMember').'</div>
                <div class="arm_default_redirection_txt arm_width_57_pct">
                    <input type="text" name="arm_redirection_settings[edit_profile][url]" value="'. esc_attr($arm_redirection_edit_profile_url).'" data-msg-required="'. esc_html__('Please enter URL.', 'ARMember') .'" class="arm_member_form_input arm_edit_profile_redirection_url arm_margin_top_12 arm_max_width_57_pct">
                    <span class="arm_redirection_edit_profile_url_selection">'. esc_html__('Please enter URL.', 'ARMember').'</span>           
                    <span class="arm_info_text arm_margin_top_12">'. esc_html__('Enter URL with http:// or https://.', 'ARMember').'</span>
                    <span class="arm_info_text arm_margin_top_12">';
                    $arm_redirection_rule_html .= sprintf( esc_html__('Use %s to add current user\'s usrename in url.', 'ARMember'),"<strong>{ARMCURRENTUSERNAME}</strong>"); //phpcs:ignore
                    $arm_redirection_rule_html .='</span>
                    <span class="arm_info_text arm_margin_top_12">';
                    $arm_redirection_rule_html .= sprintf(esc_html__('Use %s to add current user\'s id in url.', 'ARMember'),"<strong>{ARMCURRENTUSERID}</strong>");//phpcs:ignore
                    $arm_redirection_rule_html .='</span>
                    </div>
        </div></div></div>'; //phpcs:ignore
        
        $arm_form_wise_hidden_section = ($arm_redirection_edit_profile_redirection_type != 'formwise') ? 'hidden_section' : '';
        $arm_redirection_rule_html .='<div class="arm_redirection_edit_profile_formwise_settings arm_redirection_settings_edit_profile '.$arm_form_wise_hidden_section.'">
            <div>  
                <div class="arm_edit_profile_conditional_redirection_main_div arm_setting_main_content arm_margin_top_24">
                    <ul class="arm_edit_profile_conditional_redirection_ul arm_margin_bottom_20" >
                    ';
                    if(empty($arm_redirection_edit_profile_conditional)){
                        $ckey = 1;
                        $plan_id = 0;
                        $condition = '';
                        $url = ARM_HOME_URL;
                        $form_id = !empty($form_id) ? $form_id : '';

                        $arm_redirection_rule_html .='<li id="arm_edit_profile_conditional_redirection_box0" class=" arm_email_setting_flex_group">
                 
                       
                            <div class="arm_form_field_block">
                                <label>'. esc_html__('If Profile form is', 'ARMember').'</label>
                                <div>
                                        <input type="hidden" id="arm_conditional_edit_profile_redirect_form_id_0" name="arm_redirection_settings[edit_profile][conditional_redirect][0][form_id]" class="arm_form_conditional_redirect" value="'. intval($form_id).'" />
                                        <dl class="arm_selectbox column_level_dd arm_margin_top_12 arm_width_100_pct">
                                            <dt class="arm_edit_profile_redirection_dt"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                            <dd>
                                                <ul data-id="arm_conditional_edit_profile_redirect_form_id_0">
                                                        <li data-label="'. esc_html__('Select Form','ARMember').'" data-value="0">'. esc_html__('Select Form', 'ARMember').'</li>
                                                        <li data-label="'. esc_html__('All Forms','ARMember').'" data-value="-2">'. esc_html__('All Forms', 'ARMember').'</li>';
                                                    if(!empty($arm_edit_profile_forms)):
                                                        foreach($arm_edit_profile_forms as $_form):
                                                            $formTitle = strip_tags(stripslashes($_form['arm_form_label'])) . ' &nbsp;(ID: ' . $_form['arm_form_id'] . ')';
                                                            $arm_redirection_rule_html .='<li class="arm_shortcode_form_id_li '. esc_attr($_form['arm_form_type']).'" data-label="'. esc_attr($_form['arm_form_label']).'" data-value="'. intval($_form['arm_form_id']).'">'. esc_html($formTitle).'</li>';
                                                        endforeach;
                                                    endif;
                                                    $arm_redirection_rule_html .='</ul>
                                            </dd>
                                        </dl>
                                        <span class="arm_rsc_error arm_redirection_settings_edit_profile_condition_form_0">'.
                                            esc_html__('Please select Edit Profile form.', 'ARMember').'
                                        </span>  
                                </div>
                            </div>
                            <div class="arm_form_field_block">
                                <label>'. esc_html__('Then Redirect To', 'ARMember').'</label>
                                <div colspan="3" width="540px">'.
                                    $arm_global_settings->arm_wp_dropdown_pages(
                                            array(
                                                    'selected' => 0,
                                                    'name' => 'arm_redirection_settings[edit_profile][conditional_redirect][0][url]',
                                                    'id' => 'arm_edit_profile_conditional_redirection_url_0',
                                                    'show_option_none' => esc_html__('Select Page', 'ARMember'),
                                                    'option_none_value' => 0,
                                                    'class' => 'arm_member_form_input arm_edit_profile_conditional_redirection_url',
                                                    'required' => true,
                                                    'echo' => 0,
                                                    'required_msg' => esc_html__('Please select redirection page.', 'ARMember'),
                                            )
                                    )
                                    .'
                                    <span class="arm_rsc_error arm_redirection_settings_edit_profile_condition_url_0">'.
                                        esc_html__('Please select a page.', 'ARMember').'
                                    </span>  
                                </div>
                            </div>

                            <div class="arm_plan_payment_cycle_action_buttons arm_margin_top_35">
                                <a id="arm_edit_profile_conditional_redirection_add_new_condition" class="arm_edit_profile_conditional_redirection_add_new_condition arm_plan_cycle_plus_icon" href="javascript:void(0)" data-field_index="2"></a>
                                 <a class="arm_remove_edit_profile_redirection_condition arm_plan_cycle_minus_icon arm_margin_0" href="javascript:void(0)" data-index="0"></a>									
                            </div>
                    </li>';
                    }
                    else{
                        $ckey = 0;
                     
                        foreach($arm_redirection_edit_profile_conditional as $arm_edit_profile_conditional){
                            if(is_array($arm_edit_profile_conditional)){
                            $form_id = (isset($arm_edit_profile_conditional['form_id']) && !empty($arm_edit_profile_conditional['form_id'])) ? $arm_edit_profile_conditional['form_id'] : 0;

                            $url = (isset($arm_edit_profile_conditional['url']) && !empty($arm_edit_profile_conditional['url'])) ? $arm_edit_profile_conditional['url'] : ARM_HOME_URL;

                            $arm_redirection_rule_html .='<li id="arm_edit_profile_conditional_redirection_box'. intval($ckey).'" class="arm_email_setting_flex_group">
                 
                        <div class="arm_form_field_block">
                            <label>'. esc_html__('If Profile form is', 'ARMember').'</label>
                            <div>
                                    <input type="hidden" id="arm_conditional_edit_profile_redirect_form_id_'. intval($ckey).'" class="arm_form_conditional_redirect" name="arm_redirection_settings[edit_profile][conditional_redirect]['. intval($ckey).'][form_id]" value="'. intval($form_id).'" />
                                    <dl class="arm_selectbox column_level_dd arm_margin_top_12 arm_width_100_pct">
                                        <dt class="arm_edit_profile_redirection_dt"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                        <dd>
                                            <ul data-id="arm_conditional_edit_profile_redirect_form_id_'. intval($ckey).'">
                                                
                                                

                                                    <li data-label="'. esc_html__('Select Form','ARMember').'" data-value="0">'. esc_html__('Select Form', 'ARMember').'</li>
                                                    <li data-label="'. esc_html__('All Forms','ARMember').'" data-value="-2">'. esc_html__('All Forms', 'ARMember').'</li>';
                                                    if(!empty($arm_edit_profile_forms)):
                                                        foreach($arm_edit_profile_forms as $_form):
                                                            $formTitle = strip_tags(stripslashes($_form['arm_form_label'])) . ' &nbsp;(ID: ' . $_form['arm_form_id'] . ')';

                                                            $arm_redirection_rule_html .='<li class="arm_shortcode_form_id_li '. esc_attr($_form['arm_form_type']).'" data-label="'. esc_attr($formTitle).'" data-value="'. intval($_form['arm_form_id']).'">'. esc_html($formTitle).'</li>';
                                                        endforeach;
                                                    endif;
                                            $arm_redirection_rule_html .='</ul>
                                        </dd>
                                    </dl>
                                    <span class="arm_rsc_error arm_redirection_settings_edit_profile_condition_form_'. intval($ckey).'">
                                        '. esc_html__('Please select Edit Profile form.', 'ARMember').'
                                    </span>  
                            </div>
                          
                        </div>
                        <div class="arm_form_field_block">
                            <label>'. esc_html__('Then Redirect To', 'ARMember').'</label>
                            <div colspan="3">'.
                                $arm_global_settings->arm_wp_dropdown_pages(
                                        array(
                                                'selected' => $url,
                                                'name' => 'arm_redirection_settings[edit_profile][conditional_redirect]['.$ckey.'][url]',
                                                'id' => 'arm_edit_profile_conditional_redirection_url_'.$ckey,
                                                'show_option_none' => esc_html__('Select Page', 'ARMember'),
                                                'option_none_value' => 0,
                                                'class' => 'arm_member_form_input arm_edit_profile_conditional_redirection_url',
                                                'required' => true,
                                                'echo' => 0,
                                                'required_msg' => esc_html__('Please select redirection page.', 'ARMember'),
                                        )
                                )
                                .'
                                <span class="arm_rsc_error arm_redirection_settings_edit_profile_condition_url_'. intval($ckey).'">
                                    '. esc_html__('Please select a page.', 'ARMember').'
                                </span>  
                            </div>
                        </div>
                        <div class="arm_plan_payment_cycle_action_buttons arm_margin_top_35">
                        <a id="arm_edit_profile_conditional_redirection_add_new_condition" class="arm_edit_profile_conditional_redirection_add_new_condition arm_plan_cycle_plus_icon arm_margin_0 arm_margin_right_10" href="javascript:void(0)" data-field_index="2"></a>
                         <a class="arm_remove_edit_profile_redirection_condition arm_plan_cycle_minus_icon arm_margin_0" href="javascript:void(0)" data-index="'. intval($ckey).'"></a>
                        </div>
                    </li>';
                            $ckey++;
                            }
                        }
                    }
                            
                    
                
                    $arm_redirection_rule_html .='</ul>
                    <div class="arm_edit_profile_conditional_redirection_link">
                        <input id="arm_total_edit_profile_conditional_redirection_condition" name="arm_total_edit_profile_conditional_redirection_condition" value="'. intval($ckey).'" type="hidden">
                        <input id="arm_order_edit_profile_conditional_redirection_condition" name="arm_order_edit_profile_conditional_redirection_condition" value="'. intval($ckey).'" type="hidden">
                    </div>
                    <label class="arm_default_redirection_lbl arm_width_100_pct arm_paddin_0">
                        '. esc_html__('Default Redirect URL', 'ARMember').' 
                    </label>
                    <div class="arm_default_redirection_txt arm_default_redirection_full">
                        <input type="text" name="arm_redirection_settings[edit_profile][default]" value="'. esc_attr($arm_default_edit_profile_url).'" data-msg-required="'. esc_html__('Please enter URL.', 'ARMember').'" class="arm_member_form_input arm_edit_profile_redirection_conditional_redirection arm_max_width_50_pct arm_margin_top_12">
                        <span class="arm_redirection_edit_profile_conditional_redirection_selection">
                            '. esc_html__('Please enter URL.', 'ARMember').'
                        </span>   
                        <div class="arm_info_text_style arm_padding_0 arm_margin_bottom_0 arm_font_size_14">'. esc_html__('Default Redirect to above url if any of above conditions do not match.', 'ARMember').'</div>
                    </div>
                </div>
            </div>
        </div>';
}
if($arm_pay_per_post_feature->isPayPerPostFeature && $section == 'after_paid_post_selection_section'){
    $is_paid_post_url_checked = ($arm_redirection_setup_paid_post_type  == '0') ? "checked='checked'" : '' ;
    $is_paid_post_page_checked = ($arm_redirection_setup_paid_post_type  == '1') ? "checked='checked'" : '' ;
    $is_paid_post_url_section = ($arm_redirection_setup_paid_post_type != '1') ? 'hidden_section' : '' ;
    $arm_redirection_rule_html ='

                    <div class="page_sub_title arm_font_size_18 arm_font_weight_500 arm_margin_top_48">
                                '.esc_html__( "After Paid Post obtaining Redirection Rules", "ARMember" ).'
                    </div>
                        <div class="page_sub_title"></div>
                        
                    <div class="arm_setting_main_content arm_padding_0 arm_margin_top_24">
                        <div class="arm_row_wrapper arm_row_wrapper_padding_before">
                             <div class="left_content">
                                 <div class="arm_form_header_label arm-setting-hadding-label">'. esc_html__('Redirection after Paid Post Purchase', 'ARMember').'</div>
                            </div>
                         </div>
                            <div class="arm_content_border"></div>
                            <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block redirection_rules_type_wrapper"> 
                                <label class="arm_min_width_100">
                                        <input type="radio" name="arm_redirection_settings[setup_paid_post][type]" value="0" class="arm_redirection_settings_setup_paid_post_radio arm_iradio" '.$is_paid_post_url_checked.' ><span>'. esc_html__('Same page (Paid Post URL)','ARMember').'</span>
                                </label>                    
                                <label class="arm_min_width_100">
                                        <input type="radio" name="arm_redirection_settings[setup_paid_post][type]" value="1" class="arm_redirection_settings_setup_paid_post_radio arm_iradio" '.$is_paid_post_page_checked.'><span>'. esc_html__('Specific Page','ARMember').'</span>
                                </label>
                           
                             <div id="arm_redirection_settings_setup_paid_post_1" class="arm_redirection_settings_setup_paid_post '.$is_paid_post_url_section.' arm_redirection_settings_selectbox">

                            <div class="arm_margin_top_32">
                                    <label class="arm_info_text_select_page arm_margin_0">'. esc_html__('Select Page', 'ARMember').'</label>
                                <div class="arm_default_redirection_txt"> <dl class="arm_margin_0">'.
                                    $arm_global_settings->arm_wp_dropdown_pages(
                                            array(
                                                    'selected' => $arm_redirection_setup_paid_post_page_id,
                                                    'name' => 'arm_redirection_settings[setup_paid_post][page_id]',
                                                    'id' => 'arm_form_action_setup_paid_post_page',
                                                    'show_option_none' => esc_html__('Select Page', 'ARMember'),
                                                    'option_none_value' => '',
                                                    'class' => 'form_action_setup_paid_post_page',
                                                    'required' => true,
                                                    'echo' => 0,
                                                    'required_msg' => esc_html__('Please select redirection page.', 'ARMember'),
                                            )
                                    ).'</dl>
                                    <div class="armclear"></div>
                                    <span class="arm_form_action_setup_paid_post_page_require">
                                        '. esc_html__('Please Select Page.', 'ARMember').'
                                    </span>
                                </div>
                            </div>
                        </div>
                         </div>
                    </div>';
}
if($arm_social_feature->isSocialLoginFeature && $section =='after_social_login_selection_section'){
    $arm_social_login_page_redirect = ($arm_redirection_social_type == 'page') ? "checked='checked'" : '';
    $arm_social_login_url_redirect = ($arm_redirection_social_type == 'url') ? "checked='checked'" : '';
    $social_page_redirect_section = ($arm_redirection_social_type != 'page') ? 'hidden_section' : '';
    $social_url_redirect_section = ($arm_redirection_social_type != 'url') ? 'hidden_section' : '';
    $arm_redirection_rule_html ='
        <div class="page_sub_title arm_font_size_18 arm_font_weight_500 arm_margin_top_48">'. esc_html__('Social Connect Redirection( For One Click Sign up )','ARMember').'</div>
        <div class="arm_setting_main_content arm_padding_0 arm_margin_top_24">
            <div class="arm_row_wrapper arm_row_wrapper_padding_before">
                <div class="left_content">
                <div class="arm_form_header_label arm-setting-hadding-label">'. esc_html__('Default Redirect To','ARMember').'</div>
                </div>
            </div>
            <div class="arm_content_border"></div>
            <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block">
                <div class="arm-form-table-content">                     
                    <label class="arm_min_width_100">
                            <input type="radio" name="arm_redirection_settings[social][type]" value="page" class="arm_redirection_settings_social_radio arm_iradio" '. $arm_social_login_page_redirect.'><span>'. esc_html__('Specific Page','ARMember').'</span>
                    </label>
                    <label class="arm_min_width_100">
                            <input type="radio" name="arm_redirection_settings[social][type]" value="url" class="arm_redirection_settings_social_radio arm_iradio" '.$arm_social_login_url_redirect.' ><span>'. esc_html__('Specific URL','ARMember').'</span>
                    </label>
                    
                </div>
                <div id="arm_redirection_social_settings_page" class="arm_redirection_settings_social '.$social_page_redirect_section.' arm_redirection_settings_selectbox">

                <div class="arm_margin_top_32">
                    <label class="arm-form-table-label">'. esc_html__('Select Page', 'ARMember').'</label>
                   
                    <div class="arm_default_redirection_txt">
                    <dl class="arm_margin_0">'.$arm_global_settings->arm_wp_dropdown_pages(
                                array(
                                        'selected' => $arm_redirection_social_page_id,
                                        'name' => 'arm_redirection_settings[social][page_id]',
                                        'id' => 'arm_form_action_social_page',
                                        'show_option_none' => esc_html__('Select Page', 'ARMember'),
                                        'option_none_value' => '',
                                        'class' => 'form_action_social_page',
                                        'required' => true,
                                        'echo' => 0,
                                        'required_msg' => esc_html__('Please select redirection page.', 'ARMember'),
                                )
                        ).'</dl>
                        <span class="arm_redirection_social_page_selection">
                            '. esc_html__('Please Select Page.', 'ARMember').'
                        </span>      
                    </div>
                </div>
            </div>
            
            <div id="arm_redirection_social_settings_url" class="arm_redirection_settings_social '.$social_url_redirect_section.'">

                <div class="arm_margin_top_32">
                    
                    <label class="arm-form-table-label arm_width_100_pct">'. esc_html__('Add URL', 'ARMember').'</label>
                    <div class="arm_default_redirection_txt">
                        <input type="text" name="arm_redirection_settings[social][url]" value="'. esc_attr($arm_redirection_social_url).'" data-msg-required="'. esc_html__('Please enter URL.', 'ARMember').'" class="arm_member_form_input arm_social_redirection_url arm_margin_top_12 arm_max_width_57_pct">
                        <span class="arm_redirection_social_url_selection arm_margin_top_10">'. esc_html__('Please enter URL.', 'ARMember').'</span>
                        <span class="arm_info_text_style arm_padding_0 arm_margin_bottom_0 arm_font_size_14">'. esc_html__('Enter URL with http:// or https://.', 'ARMember') .'</span>
                        <span class="arm_info_text_style arm_padding_0 arm_margin_bottom_0 arm_font_size_14">';
                        $arm_redirection_rule_html .=sprintf( esc_html__('Use %s to add current user\'s usrename in url.', 'ARMember'),"<strong>{ARMCURRENTUSERNAME}</strong>"); //phpcs:ignore
                        $arm_redirection_rule_html .='</span>
                        <span class="arm_info_text_style arm_padding_0 arm_margin_bottom_0 arm_font_size_14">';
                        $arm_redirection_rule_html .= sprintf(esc_html__('Use %s to add current user\'s id in url.', 'ARMember'),"<strong>{ARMCURRENTUSERID}</strong>"); //phpcs:ignore
                        $arm_redirection_rule_html .='</span>
                    </div>  
                </div>
            </div>
            </div>
        </div>'; //phpcs:ignore
    }
         
    $arm_redirection_rule_html .="<script>
        var IF_EDIT_PROFILE_FORM_IS = '". addslashes( esc_html__('If Profile Form is', 'ARMember'))."';
    </script>";