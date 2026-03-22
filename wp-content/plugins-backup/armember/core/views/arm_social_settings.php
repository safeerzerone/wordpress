<?php
global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_social_feature, $arm_member_forms, $arm_subscription_plans, $arm_email_settings;
$global_settings = $arm_global_settings->global_settings;
$social_settings = !empty($arm_social_feature->social_settings) ? $arm_social_feature->social_settings : array();
$default_forms_id = $arm_member_forms->arm_get_default_form_id('registration');
$defaultRegisterPage = $arm_global_settings->arm_get_single_global_settings('register_page_id', 0);
// query monitor change
$all_free_plans = $arm_subscription_plans->arm_get_all_free_plans('arm_subscription_plan_id, arm_subscription_plan_name');
if (empty($social_settings)) {
    $social_settings = array();
    $social_settings['registration'] = array(
        'form_page' => $defaultRegisterPage,
        'form' => $default_forms_id,
    );
}
$arconnect_registration_page = '';
$social_options = (!empty($social_settings['options'])) ? $social_settings['options'] : '';
$social_reg_options = (!empty($social_settings['registration'])) ? $social_settings['registration'] : '';
$social_reg_options['form_page'] = (!empty($social_reg_options['form_page'])) ? $social_reg_options['form_page'] : '';
$social_reg_options['form'] = !empty($social_reg_options['form']) ? $social_reg_options['form'] : $default_forms_id;

$arm_social_icon_browser_info = $ARMember->getBrowser($_SERVER['HTTP_USER_AGENT']); //phpcs:ignore
$arm_social_icon_browser_name = $arm_social_icon_browser_info['name'];
$arm_social_icon_browser_version = $arm_social_icon_browser_info['version'];

$icon_upload_dir = MEMBERSHIP_UPLOAD_DIR . '/social_icon/';
if (!is_dir($icon_upload_dir)) {
	wp_mkdir_p($icon_upload_dir);
}
$arm_one_click_social_signup = (!empty($social_options)) ? (isset($social_options['arm_one_click_social_signup']) ? $social_options['arm_one_click_social_signup'] : 0) : 0;
$arm_social_connect_registration_page = '';
$arm_social_connect_redirection_page = '';
if($arm_one_click_social_signup == 0)
{
    $arm_social_connect_redirection_page = 'hidden_section';
    $arm_social_connect_registration_page = '';
    $arconnect_registration_page = '';
}
else{
    $arm_social_connect_registration_page = 'hidden_section';
    $arm_social_connect_redirection_page = '';
    $arconnect_registration_page = 'hidden_section';
}
?>
<div class="arm_global_settings_main_wrapper armPageContainer">
	<div class="page_sub_content">
		<form method="post" id="arm_social_settings_form" class="arm_social_settings_form arm_admin_form" enctype="multipart/form-data">
			<div class="arm_hide_show_social_setting">
                                <!-- <div class="arm_solid_divider"></div> -->
				<div class="armclear"></div>
				<!-- *****************************/.Social Registration Settings./***************************** -->
				<div class="page_sub_title arm_margin_bottom_32">
					<?php esc_html_e('Social Signup Configuration', 'ARMember'); ?>
				</div>
				<div class="arm_setting_main_content arm_padding_0 arm_margin_top_24">
                    <div class="arm_row_wrapper arm_row_wrapper_padding_before">
                        <div class="left_content">
                            <div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Enable One Click Sign Up', 'ARMember'); ?></div>
                        </div>
                        <div class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch arm_margin_right_0">
                                
                                <input id="arm_one_click_social_signup" class="armswitch_input arm_one_click_social_signup" type="checkbox" name="arm_social_settings[options][arm_one_click_social_signup]" value="1" data-stype="fb" <?php checked($arm_one_click_social_signup, 1); ?>>

                                <label for="arm_one_click_social_signup" class="armswitch_label"></label>
                            </div>
                        </div>
                    </div>

                    <div class="arm_content_border"></div>
                    <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block  arm_padding_top_24 arm_enable_one_click_sign_up" style="align-items: start;">
                        <div class="arm_page_setup_flex_group arm_width_100_pct">
                        <?php  
                            $email_tools = $arm_email_settings->arm_get_optin_settings();
                            $email_tools = apply_filters('arm_opt_ins_details', $email_tools);
                            if((!empty($email_tools) && $arm_email_settings->isOptInsFeature)):
                                $arm_options_name = isset($social_options['optins_name']) ? $social_options['optins_name'] : 0;
                                $list_html = '';
                        ?>
                        <div class="arm_form_field_block arm_one_click_redirection_page <?php echo esc_attr($arm_social_connect_redirection_page); ?> arm_one_click_redirection_wrapper">
                            <div class="arm-form-table-label"><?php esc_html_e('Opt-Ins', 'ARMember'); ?></div>
                                <div class="arm-form-table-content">
                                    <input type="hidden" id="arm_optins_on_click_signup" name="arm_social_settings[options][optins_name]" value="<?php echo esc_attr($arm_options_name); ?>" />
                                    <dl class="arm_selectbox column_level_dd arm_width_100_pct arm_margin_top_12">
                                        <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                        <dd>
                                            <ul class="arm_optins_on_click_signup" data-id="arm_optins_on_click_signup">
                                                <li data-label="<?php esc_html_e('Select Optins','ARMember');?>" data-value="0"><?php esc_html_e('Select Optins', 'ARMember');?></li>
                                                <?php
                                                foreach ($email_tools as $etool => $etsetting) {
                                                    if (!isset($etsetting['status']) || $etsetting['status'] != 1) { continue; }
                                                    $etoolName = isset($etsetting['optins_name']) ? $etsetting['optins_name'] : '';
                                                    $etoolName = apply_filters('arm_opt_ins_display_name', $etool, $etoolName);
                                                    echo '<li class="arm_optins_li '.stripslashes( esc_attr($etool) ).'" data-label="'.stripslashes( esc_attr($etoolName) ).'" data-value="'.esc_attr($etool).'">'.stripslashes( esc_html($etoolName) ).'</li>'; //phpcs:ignore
                                                    
                                                    /********** display list begins ********/
                                                    if (!isset($etsetting['status']) || $etsetting['status'] != 1 || empty($etsetting['list']))
                                                        { continue; }
                                                        $sc_list_id = (isset($etsetting['list_id'])) ? $etsetting['list_id'] : '';
                                                        $lists = (isset($etsetting['list'])) ? $etsetting['list'] : array();
                                                        $sc_style = 'style="display:none;"';
                                                        if($arm_options_name == $etool && $arm_options_name != '0') {
                                                            $sc_list_id = isset($social_options[$arm_options_name]['list_id'])  ? $social_options[$arm_options_name]['list_id'] : $sc_list_id;
                                                            $sc_style = '';
                                                        }
                                                        $list_html .= '<div class="farm_form_field_block arm_one_click_redirection_page '.$arm_social_connect_redirection_page .'--'.$arm_options_name.'--'.$etool.' arm_email_tool_list '.$etool.'_list_name '.$arm_social_connect_redirection_page.'" '.$sc_style.'>';
                                                        $list_html .= '<label class="arm-form-table-label">'.esc_html__('List Name', 'ARMember').'</label>';
                                                        $list_html .= '<div class="arm-form-table-content">';
                                                        $list_html .= '<input type="hidden" id="'.$etool.'_list_name" name="arm_social_settings[options]['.$etool.'][list_id]" value="'.$sc_list_id.'"/>';
                                                        $list_html .= '<dl class="arm_selectbox column_level_dd arm_width_100_pct arm_margin_top_12">';
                                                        $list_html .= '<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';
                                                        $list_html .= '<dd>';
                                                        $list_html .= '<ul data-id="'.$etool.'_list_name" id="arm_'.$etool.'_list">';
                                                        if (!empty($lists)) {
                                                            foreach ($lists as $list) {
                                                                $list_html .= '<li data-label="'.$list['name'].'" data-value="'.$list['id'].'">'.$list['name'].'</li>';
                                                            }
                                                        }
                                                        $list_html .= '</ul>';
                                                        $list_html .= '</dd>';
                                                        $list_html .= '</dl>';
                                                        $list_html .= '</div>';
                                                        $list_html .= '</div>';
                                                    /********** display list end ***********/
    
                                                }?>
                                            </ul>
                                        </dd>
                                    </dl>
                                </div>
                        </div>
                                            
                        <?php echo $list_html; //phpcs:ignore?>
    
                        <?php endif; ?>
                        <div class="arm_form_field_block arm_one_click_redirection_page <?php echo esc_attr($arm_social_connect_redirection_page); ?> arm_one_click_redirection_wrapper">
                            <label class="arm-form-table-label"><?php esc_html_e('Assign Default Plan', 'ARMember'); ?></label>
                            <div class="arm-form-table-content arm_margin_top_12">
                                <input type="hidden" id="arm_assign_default_plan" name="arm_social_settings[options][assign_default_plan]" value="<?php echo isset($social_options['assign_default_plan']) ? esc_attr($social_options['assign_default_plan']) : '0'; ?>" />
                                <dl class="arm_selectbox column_level_dd arm_width_100_pct">
                                        <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                        <dd>
                                                <ul class="arm_assign_default_plan_wrapper" data-id="arm_assign_default_plan">
                                                        <li data-label="<?php esc_html_e('Select Plan','ARMember');?>" data-value="0"><?php esc_html_e('Select Plan', 'ARMember');?></li>
                                                        <?php if(!empty($all_free_plans)): /* query monitor change */ ?>
                                                                <?php foreach($all_free_plans as $plan): ?>
                                                                        <li class="arm_assign_default_plan_li <?php echo stripslashes( esc_attr($plan['arm_subscription_plan_name']) ); //phpcs:ignore?>" data-label="<?php echo stripslashes( esc_attr($plan['arm_subscription_plan_name']) ); //phpcs:ignore?>" data-value="<?php echo esc_attr($plan['arm_subscription_plan_id']);?>"><?php echo stripslashes( esc_html($plan['arm_subscription_plan_name'])); //phpcs:ignore?></li>
                                                                <?php endforeach;?>
                                                        <?php endif;?>
                                                </ul>
                                        </dd>
                                </dl>	
                            </div>
                        </div>
                                    
                        <div class="arm_form_field_block arm_social_connect_registration_page <?php echo esc_attr($arm_social_connect_registration_page); ?> arm_social_connect_registration_wrapper">
                            <div class="arm-form-table-label">
                                <?php esc_html_e('Registration Form Page', 'ARMember'); ?>
                                <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php esc_html_e("Map your registration page & form for new member registration using social media options.", 'ARMember');?>" style="margin-top: -3px !important;"></i>                  
                            </div>
                            <div class="arm-form-table-content">
                                <?php
                                $arm_global_settings->arm_wp_dropdown_pages(
                                    array(
                                        'selected' => $social_reg_options['form_page'],
                                        'name' => 'arm_social_settings[registration][form_page]',
                                        'id' => 'register_form_page_id',
                                        'show_option_none' => esc_html__('Select Page', 'ARMember'),
                                        'option_none_value' => '',
                                        'class' => 'arm_social_form_page_input',
                                        'required_msg' => esc_html__('Please select atleast one registration page.', 'ARMember'),
                                    )
                                );
                                $page_shortcode_data = $arm_global_settings->arm_get_social_form_page_shortcodes($social_reg_options['form_page'], $social_reg_options['form']);
                                $shortcode_forms = $page_shortcode_data['forms'];
                                ?>
                                <i class="armfa armfa-1x armfa-refresh armfa-spin arm_refresh arm_no_error"></i>
                                <i class="armfa armfa-1x armfa-check arm_check arm_no_error"></i>
                                <span class="arm_error_msg <?php echo ($page_shortcode_data['status']) ? 'arm_no_error' : ''; ?>"><?php esc_html_e('Shortcode of Registration Form not found on selected page. Please add shortcode there Or please select the page having registration form of ARMember', 'ARMember');?></span>
                            </div>
                        </div>
                        <div class="arm_form_field_block arm_social_connect_registration_form <?php echo esc_attr($arconnect_registration_page); ?> arm_social_connect_registration_wrapper">
                            <label class="arm-form-table-label"><?php esc_html_e('Registration Form', 'ARMember'); ?></label>
                            <div class="arm-form-table-content">
                                <div class="arm_social_form_container arm_margin_top_12 arm_info_text arm_info_text arm_info_text_style arm-note-message --warning arm_margin_bottom_0 arm_pading_bottom_0 arm_height_40">
                                    <?php echo $shortcode_forms; //phpcs:ignore?>
                                </div>						
                            </div>
                        </div>
                        </div>
                    </div>      
				</div>
                <div class="arm_setting_main_content  arm_margin_top_24">  

                        <div class="arm_row_wrapper">
                            <div class="left_content">
                                <div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Get Social Avatar', 'ARMember'); ?></div>
                                <span class="arm_global_setting_label" ><?php echo esc_html__('Get social avatar while authenticate with social connect buttons','ARMember'); ?></span>
                                <span class="arm_global_setting_label" ><?php echo esc_html__('This options will only works if','ARMember').' <b>allow_url_fopen</b> '.esc_html__(' setting is ON in your server configuration','ARMember'); ?></span>
                            </div>
                            <div class="arm-form-table-content">
                                <div class="armswitch arm_global_setting_switch arm_margin_right_0">
                                    <?php
                                    $social_avatar = (!empty($social_options)) ? (isset($social_options['social_avatar']) ? $social_options['social_avatar'] : 0) : 0;
                                    ?>
                                    <input id="arm_get_social_avatar" class="armswitch_input arm_get_social_avatar" type="checkbox" name="arm_social_settings[options][social_avatar]" value="1" data-stype="fb" <?php checked($social_avatar, 1); ?>>

                                    <label for="arm_get_social_avatar" class="armswitch_label"></label>
                                </div>
                            </div>
                        </div>
                            </div>
				<div class="arm_margin_top_32"></div>
				<div class="armclear"></div>
				<div class="page_sub_title"><?php esc_html_e('Select Your Social Network', 'ARMember'); ?></div>
				
					<!-- *****************************/.Facebook Settings./***************************** -->
					
					<?php 
					$fb_status = (!empty($social_options)) ? (isset($social_options['facebook']['status']) ? $social_options['facebook']['status'] : 0) : 0;
					$fbDisabledAttr = ($fb_status == 0) ? 'disabled="disabled"' : '';
					$fbReadonlyAttr = ($fb_status == 0) ? 'readonly="readonly"' : '';
					?>

					<div class="arm_setting_main_content arm_margin_top_32">
						<div class="arm_row_wrapper">
                            <div class="left_content">
                                <div class="arm_form_header_label arm-setting-hadding-label" for="arm_facebook_status"><?php esc_html_e('Facebook', 'ARMember'); ?></div>
                            </div>
                            
							<div class="arm-form-table-content">
								<div class="armswitch arm_global_setting_switch arm_margin_right_0">
									<input id="arm_facebook_status" class="armswitch_input arm_social_network_status" type="checkbox" name="arm_social_settings[options][facebook][status]" value="1" data-stype="fb" <?php checked($fb_status, 1);?>>
									<label for="arm_facebook_status" class="armswitch_label"></label>
                                    <?php $arm_sc_fb_icon = (!empty($social_options)) ? ((!empty($social_options['facebook']['icon'])) ? $social_options['facebook']['icon'] : 'fb_1.png') : 'fb_1.png';
                                    $arm_sc_fb_custom_icon = (!empty($social_options)) ? ((!empty($social_options['facebook']['custom_icon'])) ? $social_options['facebook']['custom_icon'] : '') : '';?>
                                    <input type="hidden" name="arm_social_settings[options][facebook][icon]" value="<?php echo esc_attr($arm_sc_fb_icon);?>">
								</div>
							</div>
						</div>
                        <div class="arm_display_block arm_facebook_fields_wrapper <?php echo ($fb_status == 1) ? '' : 'hidden_section'; ?>">
                        <div class="arm_content_border arm_margin_top_24 arm_margin_bottom_24"></div>

                            <div class="arm_email_setting_flex_group social_network_wrapper">
                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_facebook_app_id" class="arm-form-table-label"><?php esc_html_e('App ID', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct ">
                                        <input type="text" name="arm_social_settings[options][facebook][app_id]" class="arm_input_fb" id="arm_facebook_app_id" value="<?php echo (!empty($social_options) && !empty($social_options['facebook']['app_id'])) ? esc_attr($social_options['facebook']['app_id']) : '';?>" data-msg-required="<?php esc_html_e('App ID can not be left blank.', 'ARMember'); ?>" <?php echo $fbReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>
                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_facebook_app_secret" class="arm-form-table-label"><?php esc_html_e('App Secret', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][facebook][app_secret]" class="arm_input_fb" id="arm_facebook_app_secret" value="<?php echo (!empty($social_options) && !empty($social_options['facebook']['app_secret'])) ? esc_attr($social_options['facebook']['app_secret']) : '';?>" data-msg-required="<?php esc_html_e('App Secret can not be left blank.', 'ARMember'); ?>" <?php echo $fbReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>
                            </div>  
                        </div>
					</div>
					<div class="arm_margin_top_24"></div>
					<div class="armclear"></div>
					<!-- *****************************/.Twitter Settings./***************************** -->
					
					<?php 
					$tw_status = (!empty($social_options) && isset($social_options['twitter']['status'])) ? $social_options['twitter']['status'] : 0;
					$twDisabledAttr = ($tw_status == 0) ? 'disabled="disabled"' : '';
					$twReadonlyAttr = ($tw_status == 0) ? 'readonly="readonly"' : '';
					?>
					<div class="arm_setting_main_content">
                        <div class="arm_row_wrapper">
                            <div class="left_content">
                                <div for="arm_twitter_status" class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Twitter', 'ARMember'); ?></div>
                            </div>
                            <div class="arm-form-table-content">
                                <div class="armswitch arm_global_setting_switch arm_margin_right_0">
                                    <input id="arm_twitter_status" class="armswitch_input arm_social_network_status" type="checkbox" name="arm_social_settings[options][twitter][status]" value="1" data-stype="tw" <?php checked($tw_status, 1);?>>
                                    <label for="arm_twitter_status" class="armswitch_label"></label>
                                    <?php $arm_sc_tw_icon = (!empty($social_options)) ? ((!empty($social_options['twitter']['icon'])) ? $social_options['twitter']['icon'] : 'tw_1.png') : 'tw_1.png'; 
                                    $arm_sc_tw_custom_icon = (!empty($social_options)) ? ((!empty($social_options['twitter']['custom_icon'])) ? $social_options['twitter']['custom_icon'] : '') : '';?>
                                    <input type="hidden" name="arm_social_settings[options][twitter][icon]" value="<?php echo esc_attr($arm_sc_tw_icon);?>">
                                </div>
                            </div>
                        </div>
                        <div class="arm_display_block arm_twitter_fields_wrapper <?php echo ($tw_status == 1) ? '' : 'hidden_section'; ?>">
                        <div class="arm_content_border arm_margin_top_24 arm_margin_bottom_24"></div>
                            <div class="arm_email_setting_flex_group social_network_wrapper">
                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_twitter_customer_key" class="arm-form-table-label"><?php esc_html_e('Customer Key', 'ARMember'); ?> *</label></th>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][twitter][customer_key]" class="arm_input_tw" id="arm_twitter_customer_key" value="<?php echo (!empty($social_options) && !empty($social_options['twitter']['customer_key'])) ? esc_attr($social_options['twitter']['customer_key']) : '';?>" data-msg-required="<?php esc_html_e('Customer Key can not be left blank.', 'ARMember'); ?>" <?php echo $twReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>
                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_twitter_customer_secret" class="arm-form-table-label"><?php esc_html_e('Customer Secret', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][twitter][customer_secret]" class="arm_input_tw" id="arm_twitter_customer_secret" value="<?php echo (!empty($social_options) && !empty($social_options['twitter']['customer_secret'])) ? esc_attr($social_options['twitter']['customer_secret']) : '';?>" data-msg-required="<?php esc_html_e('Customer Secret can not be left blank.', 'ARMember'); ?>" <?php echo $twReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>
                            </div>
                                <div class="form-field arm_margin_top_24">
                                    <label class="arm-form-table-label"><?php esc_html_e('Callback URL', 'ARMember'); ?></label>
                                    <div class="arm-form-table-content ">
                                        <span class="arm_info_text arm_info_text arm_info_text_style arm-note-message --warning arm_margin_top_15 arm_pading_bottom_0"><?php echo ARM_HOME_URL.'/?page=arm_twitter_return'; //phpcs:ignore?></span>
                                        <span class="arm_info_text"><?php esc_html_e('Callback URLs that you should add in Twitter Application -> Settings.', 'ARMember'); ?></span>
                                    </div>
                                </div>
                        </div>
					</div>
                
					<div class="arm_margin_top_24"></div>
					<div class="armclear"></div>
					<!-- *****************************/.LinkedIn Settings./***************************** -->
					
                        <?php 
                        $li_status = (!empty($social_options) && isset($social_options['linkedin']['status'])) ? $social_options['linkedin']['status'] : 0;
                        $liDisabledAttr = ($li_status == 0) ? 'disabled="disabled"' : '';
                        $liReadonlyAttr = ($li_status == 0) ? 'readonly="readonly"' : '';
                        ?>
					<div class="arm_setting_main_content">
						<div class="arm_row_wrapper">
                        <div class="left_content">
							<div for="arm_linkedin_status" class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('LinkedIn', 'ARMember'); ?></div>
                        </div>

							<div class="arm-form-table-content">
								<div class="armswitch arm_global_setting_switch arm_margin_right_0">
									<input id="arm_linkedin_status" class="armswitch_input arm_social_network_status" type="checkbox" name="arm_social_settings[options][linkedin][status]" value="1" data-stype="li" <?php checked($li_status, 1);?>>
									<label for="arm_linkedin_status" class="armswitch_label"></label>
                                    <?php $arm_sc_li_icon = (!empty($social_options)) ? ((!empty($social_options['linkedin']['icon'])) ? $social_options['linkedin']['icon'] : 'li_1.png') : 'li_1.png';
                                    $arm_sc_li_custom_icon = (!empty($social_options)) ? ((!empty($social_options['linkedin']['custom_icon'])) ? $social_options['linkedin']['custom_icon'] : '') : '';?>
                                    <input type="hidden" name="arm_social_settings[options][linkedin][icon]" value="<?php echo esc_attr($arm_sc_li_icon);?>">
								</div>
							</div>
						</div>
                        <div class="arm_display_block arm_linkedin_fields_wrapper <?php echo ($li_status == 1) ? '' : 'hidden_section'; ?>">
                            <div class="arm_content_border arm_margin_top_24 arm_margin_bottom_24"></div>
                        <div class="arm_email_setting_flex_group social_network_wrapper">
                            <div class="form-field arm_social_network_form_field">
                               <label for="arm_linkedin_client_id" class="arm-form-table-label"><?php esc_html_e('Client ID', 'ARMember'); ?> *</label>
                                <div class="arm_margin_top_12 arm_width_100_pct">
                                    <input type="text" name="arm_social_settings[options][linkedin][client_id]" class="arm_input_li" id="arm_linkedin_client_id" value="<?php echo (!empty($social_options) && !empty($social_options['linkedin']['client_id'])) ? esc_attr($social_options['linkedin']['client_id']) : '';?>" data-msg-required="<?php esc_html_e('Client ID can not be left blank.', 'ARMember'); ?>" <?php echo $liReadonlyAttr; //phpcs:ignore?>>
                                </div>
                            </div>
                            <div class="form-field arm_social_network_form_field">
                                <label for="arm_linkedin_client_secret" class="arm-form-table-label"><?php esc_html_e('Client Secret', 'ARMember'); ?> *</label>
                                <div class="arm_margin_top_12 arm_width_100_pct">
                                    <input type="text" name="arm_social_settings[options][linkedin][client_secret]" class="arm_input_li" id="arm_linkedin_client_secret" value="<?php echo (!empty($social_options) && !empty($social_options['linkedin']['client_secret'])) ? esc_attr($social_options['linkedin']['client_secret']) : '';?>" data-msg-required="<?php esc_html_e('Client Secret can not be left blank.', 'ARMember'); ?>" <?php echo $liReadonlyAttr; //phpcs:ignore?>>
                                </div>
                            </div>
                        </div>
                            <div class="form-field arm_margin_top_24">
                                <label class="arm-form-table-label"><?php esc_html_e('Callback URL', 'ARMember'); ?></label>
                                <div class="arm_margin_top_12 arm_width_100_pct">
                                    <span class="arm_info_text arm_info_text arm_info_text_style arm-note-message --warning arm_margin_top_15 arm_pading_bottom_0"><?php echo ARM_HOME_URL; //phpcs:ignore?></span>
                                    <span class="arm_info_text"><?php esc_html_e('Callback URLs that you should add in LinkedIn Application', 'ARMember'); ?></span>
                                </div>
                            </div>
                        </div>
					</div>
					<div class="arm_margin_top_24"></div>
					<div class="armclear"></div>
                   <!-- *****************************/.Google Settings./***************************** -->
                    <?php 
                    $google_status = (!empty($social_options)) ? (isset($social_options['google']['status']) ? $social_options['google']['status'] : 0) : 0;
                    $googleDisabledAttr = ($google_status == 0) ? 'disabled="disabled"' : '';
                    $googleReadonlyAttr = ($google_status == 0) ? 'readonly="readonly"' : '';
                    ?>
                    <div class="arm_setting_main_content">
                        <div class="arm_row_wrapper">
                            <div class="left_content">
                                <div for="arm_google_status" class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Google SignIn', 'ARMember'); ?></div>
                            </div>

                            <div class="arm-form-table-content">
                                <div class="armswitch arm_global_setting_switch arm_margin_right_0">
                                    <input id="arm_google_status" class="armswitch_input arm_social_network_status" type="checkbox" name="arm_social_settings[options][google][status]" value="1" data-stype="google" <?php checked($google_status, 1);?>>
                                    <label for="arm_google_status" class="armswitch_label"></label>
                                    <?php 
                                    $arm_sc_google_icon = (!empty($social_options)) ? ((!empty($social_options['google']['icon'])) ? $social_options['google']['icon'] : 'google_1.png') : 'google_1.png';
                                    $arm_sc_google_custom_icon = (!empty($social_options)) ? ((!empty($social_options['google']['custom_icon'])) ? $social_options['google']['custom_icon'] : '') : '';
                                    ?>
                                    <input type="hidden" name="arm_social_settings[options][google][icon]" value="<?php echo esc_attr($arm_sc_google_icon);?>">
                                </div>
                            </div>
                        </div>
                       
                        <div class="arm_display_block arm_google_fields_wrapper <?php echo ($google_status == 1) ? '' : 'hidden_section'; ?>">
                            <div class="arm_content_border arm_margin_top_24 arm_margin_bottom_24"></div>

                            <div class="arm_email_setting_flex_group social_network_wrapper">
                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_google_client_id" class="arm-form-table-label"><?php esc_html_e('Client ID', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][google][client_id]" class="arm_input_google" id="arm_google_client_id" value="<?php echo (!empty($social_options) && !empty($social_options['google']['client_id'])) ? esc_attr($social_options['google']['client_id']) : '';?>" data-msg-required="<?php esc_html_e('Client ID can not be left blank.', 'ARMember'); ?>" <?php echo $googleReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>

                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_google_client_secret" class="arm-form-table-label"><?php esc_html_e('Client Secret', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][google][client_secret]" class="arm_input_google" id="arm_google_client_secret" value="<?php echo (!empty($social_options) && !empty($social_options['google']['client_secret'])) ? esc_attr($social_options['google']['client_secret']) : '';?>" data-msg-required="<?php esc_html_e('Client Secret can not be left blank.', 'ARMember'); ?>" <?php echo $googleReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>
                            </div>

                            <div class="form-field arm_margin_top_24">
                                <label class="arm-form-table-label"><?php esc_html_e('Authorized Redirect URIs', 'ARMember'); ?></label>
                                <div class="arm_margin_top_12 arm_width_100_pct">
                                    <span class="arm_info_text arm_info_text arm_info_text_style arm-note-message --warning arm_margin_top_15 arm_pading_bottom_0"><?php echo ARM_HOME_URL.'/?arm_google_action=arm_google_signin_response'; //phpcs:ignore?></span>
                                    <span class="arm_info_text"><?php esc_html_e(' Set Authorized redirect URIs URL in your Google Web Application settings.', 'ARMember'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="arm_margin_top_24"></div>
                    <div class="armclear"></div>

					<!-- *****************************/.VK Settings./***************************** -->
                    
                    <?php 
                    $vk_status = (!empty($social_options) && isset($social_options['vk']['status'])) ? $social_options['vk']['status'] : 0;
                    $vkDisabledAttr = ($vk_status == 0) ? 'disabled="disabled"' : '';
                    $vkReadonlyAttr = ($vk_status == 0) ? 'readonly="readonly"' : '';
                    $titleTooltip = esc_html__('To get more information about how to set vk redirect url. Please refer this', 'ARMember').' <a href="'.MEMBERSHIP_DOCUMENTATION_URL.'" target="_blank">'.esc_html__('document', 'ARMember').'</a>. ';
                    ?>
                    <div class="arm_setting_main_content">
                        <div class="arm_row_wrapper">
                            <div class="left_content">
                                <div for="arm_vk_status" class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('VK', 'ARMember'); ?></div>
                            </div>

                            <div class="arm-form-table-content">
                                <div class="armswitch arm_global_setting_switch arm_margin_right_0">
                                    <input id="arm_vk_status" class="armswitch_input arm_social_network_status" type="checkbox" name="arm_social_settings[options][vk][status]" value="1" data-stype="vk" <?php checked($vk_status, 1);?>>
                                    <label for="arm_vk_status" class="armswitch_label"></label>
                                    <?php 
                                    $arm_sc_vk_icon = (!empty($social_options)) ? ((!empty($social_options['vk']['icon'])) ? $social_options['vk']['icon'] : 'vk_1.png' ) : 'vk_1.png';
                                    $arm_sc_vk_custom_icon = (!empty($social_options)) ? ((!empty($social_options['vk']['custom_icon'])) ? $social_options['vk']['custom_icon'] : '') : '';
                                    ?>
                                    <input type="hidden" name="arm_social_settings[options][vk][icon]" value="<?php echo esc_attr($arm_sc_vk_icon);?>">
                                </div>
                            </div>
                        </div>
                       
                        <div class="arm_display_block arm_vk_fields_wrapper <?php echo ($vk_status == 1) ? '' : 'hidden_section'; ?>">
                            <div class="arm_content_border arm_margin_top_24 arm_margin_bottom_24"></div>

                            <div class="arm_email_setting_flex_group social_network_wrapper">
                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_vk_app_id" class="arm-form-table-label"><?php esc_html_e('App ID', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][vk][app_id]" class="arm_input_vk" id="arm_vk_app_id" value="<?php echo (!empty($social_options) && !empty($social_options['vk']['app_id'])) ? esc_attr($social_options['vk']['app_id']) : '';?>" data-msg-required="<?php esc_html_e('App ID can not be left blank.', 'ARMember'); ?>" <?php echo $vkReadonlyAttr; //phpcs:ignore?> onkeydown="javascript:return checkNumber(event)">
                                    </div>
                                </div>

                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_vk_app_secret" class="arm-form-table-label"><?php esc_html_e('App Secure Key', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][vk][app_secret]" class="arm_input_vk" id="arm_vk_app_secret" value="<?php echo (!empty($social_options) && !empty($social_options['vk']['app_secret'])) ? esc_attr($social_options['vk']['app_secret']) : '';?>" data-msg-required="<?php esc_html_e('App secure key can not be left blank.', 'ARMember'); ?>" <?php echo $vkReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>
                            </div>

                            <div class="form-field arm_margin_top_24">
                                <label class="arm-form-table-label"><?php esc_html_e('Authorized Redirect URI', 'ARMember'); ?></label>
                                <div class="arm_margin_top_12 arm_width_100_pct">
                                    <span class="arm_info_text arm_info_text arm_info_text_style arm-note-message --warning arm_margin_top_15 arm_pading_bottom_0"><?php echo MEMBERSHIP_VIEWS_URL.'/callback/vk_callback.php'; //phpcs:ignore?></span>
                                    <span class="arm_info_text"><?php esc_html_e('Redirect URI that you should add in VK API.', 'ARMember'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="arm_margin_top_24"></div>
                    <div class="armclear"></div>


                   <!-- *****************************/.Instagram Settings./***************************** -->
                    <?php 
                    $insta_status = (!empty($social_options) && isset($social_options['insta']['status'])) ? $social_options['insta']['status'] : 0;
                    $instaDisabledAttr = ($insta_status == 0) ? 'disabled="disabled"' : '';
                    $instaReadonlyAttr = ($insta_status == 0) ? 'readonly="readonly"' : '';
                    ?>
                    <div class="arm_setting_main_content ">
                        <div class="arm_row_wrapper">
                            <div class="left_content">
                                <div for="arm_insta_status" class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Instagram', 'ARMember'); ?></div>
                            </div>

                            <div class="arm-form-table-content">
                                <div class="armswitch arm_global_setting_switch arm_margin_right_0">
                                    <input id="arm_insta_status" class="armswitch_input arm_social_network_status" type="checkbox" name="arm_social_settings[options][insta][status]" value="1" data-stype="insta" <?php checked($insta_status, 1);?>>
                                    <label for="arm_insta_status" class="armswitch_label"></label>
                                    <?php 
                                    $arm_sc_insta_icon = (!empty($social_options)) ? ((!empty($social_options['insta']['icon'])) ? $social_options['insta']['icon'] : 'insta_1.png' ) : 'insta_1.png';
                                    $arm_sc_insta_custom_icon = (!empty($social_options)) ? ((!empty($social_options['insta']['custom_icon'])) ? $social_options['insta']['custom_icon'] : '') : '';
                                    ?>
                                    <input type="hidden" name="arm_social_settings[options][insta][icon]" value="<?php echo esc_attr($arm_sc_insta_icon);?>">
                                </div>
                            </div>
                        </div>
                    
                        <div class="arm_display_block arm_insta_fields_wrapper <?php echo ($insta_status == 1) ? '' : 'hidden_section'; ?>">
                            <div class="arm_content_border arm_margin_top_24 arm_margin_bottom_24"></div>

                            <div class="arm_email_setting_flex_group social_network_wrapper">
                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_insta_client_id" class="arm-form-table-label"><?php esc_html_e('Client ID', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][insta][client_id]" class="arm_input_insta" id="arm_insta_client_id" value="<?php echo (!empty($social_options) && !empty($social_options['insta']['client_id'])) ? esc_attr($social_options['insta']['client_id']) : '';?>" data-msg-required="<?php esc_html_e('Client ID can not be left blank.', 'ARMember'); ?>" <?php echo $instaReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>

                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_insta_client_secret" class="arm-form-table-label"><?php esc_html_e('Client Secure Key', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][insta][client_secret]" class="arm_input_insta" id="arm_insta_client_secret" value="<?php echo (!empty($social_options) && !empty($social_options['insta']['client_secret'])) ? esc_attr($social_options['insta']['client_secret']) : '';?>" data-msg-required="<?php esc_html_e('Client Secure Key can not be left blank.', 'ARMember'); ?>" <?php echo $instaReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>
                            </div>

                            <div class="form-field arm_margin_top_24">
                                <label class="arm-form-table-label"><?php esc_html_e('Valid Redirect URIs', 'ARMember'); ?></label>
                                <div class="arm_margin_top_12 arm_width_100_pct">
                                    <span class="arm_info_text arm_info_text arm_info_text_style arm-note-message --warning arm_margin_top_15 arm_pading_bottom_0"><?php echo MEMBERSHIP_VIEWS_URL.'/callback/insta_callback.php'; //phpcs:ignore?></span>
                                    <span class="arm_info_text"><?php esc_html_e('Redirect URI that you should add in Instagram API.', 'ARMember'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="arm_margin_top_24"></div>
                    <div class="armclear"></div>

                    <!-- *****************************/.Tumblr Settings./***************************** -->
                   
                    <?php 
                    $tu_status = (!empty($social_options) && isset($social_options['tumblr']['status'])) ? $social_options['tumblr']['status'] : 0;
                    $tuDisabledAttr = ($tu_status == 0) ? 'disabled="disabled"' : '';
                    $tuReadonlyAttr = ($tu_status == 0) ? 'readonly="readonly"' : '';
                    ?>
                    <div class="arm_setting_main_content">
                        <div class="arm_row_wrapper">
                            <div class="left_content">
                                <div for="arm_tumblr_status" class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Tumblr', 'ARMember'); ?></div>
                            </div>

                            <div class="arm-form-table-content">
                                <div class="armswitch arm_global_setting_switch arm_margin_right_0">
                                    <input id="arm_tumblr_status" class="armswitch_input arm_social_network_status" type="checkbox" name="arm_social_settings[options][tumblr][status]" value="1" data-stype="tu" <?php checked($tu_status, 1);?>>
                                    <label for="arm_tumblr_status" class="armswitch_label"></label>
                                    <?php 
                                    $arm_sc_tu_icon = (!empty($social_options)) ? ((!empty($social_options['tumblr']['icon'])) ? $social_options['tumblr']['icon'] : 'tu_1.png') : 'tu_1.png'; 
                                    $arm_sc_tu_custom_icon = (!empty($social_options)) ? ((!empty($social_options['tumblr']['custom_icon'])) ? $social_options['tumblr']['custom_icon'] : '') : '';
                                    ?>
                                    <input type="hidden" name="arm_social_settings[options][tumblr][icon]" value="<?php echo esc_attr($arm_sc_tu_icon);?>">
                                </div>
                            </div>
                        </div>
                    
                        <div class="arm_display_block arm_tumblr_fields_wrapper <?php echo ($tu_status == 1) ? '' : 'hidden_section'; ?>">
                            <div class="arm_content_border arm_margin_top_24 arm_margin_bottom_24"></div>
                             
                            <div class="arm_email_setting_flex_group social_network_wrapper">
                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_tumblr_consumer_key" class="arm-form-table-label"><?php esc_html_e('Consumer Key', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][tumblr][consumer_key]" class="arm_input_tu" id="arm_tumblr_consumer_key" value="<?php echo (!empty($social_options) && !empty($social_options['tumblr']['consumer_key'])) ? esc_attr($social_options['tumblr']['consumer_key']) : '';?>" data-msg-required="<?php esc_html_e('Consumer Key can not be left blank.', 'ARMember'); ?>" <?php echo $tuReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>

                                <div class="form-field arm_social_network_form_field">
                                    <label for="arm_tumblr_consumer_secret" class="arm-form-table-label"><?php esc_html_e('Consumer Secret', 'ARMember'); ?> *</label>
                                    <div class="arm_margin_top_12 arm_width_100_pct">
                                        <input type="text" name="arm_social_settings[options][tumblr][consumer_secret]" class="arm_input_tu" id="arm_tumblr_consumer_secret" value="<?php echo (!empty($social_options) && !empty($social_options['tumblr']['consumer_secret'])) ? esc_attr($social_options['tumblr']['consumer_secret']) : '';?>" data-msg-required="<?php esc_html_e('Consumer Secret can not be left blank.', 'ARMember'); ?>" <?php echo $tuReadonlyAttr; //phpcs:ignore?>>
                                    </div>
                                </div>
                            </div>

                            <div class="form-field arm_margin_top_24">
                                <label class="arm-form-table-label"><?php esc_html_e('Callback URL', 'ARMember'); ?></label>
                                <div class="arm_margin_top_12 arm_width_100_pct">
                                    <span class="arm_info_text arm_info_text arm_info_text_style arm-note-message --warning arm_margin_top_15 arm_pading_bottom_0"><?php echo ARM_HOME_URL.'/?page=arm_tumblr_return'; //phpcs:ignore?></span>
                                    <span class="arm_info_text"><?php esc_html_e('Callback URLs that you should add in Tumblr Application -> Settings.', 'ARMember'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="armclear"></div>
                    <!-- Tumblr module over -->
				
			</div>
			<div class="armclear"></div>
			<div class="arm_submit_btn_container arm_apply_changes_btn_container">
				<input type="hidden" name="s_action" value="arm_update_social_settings">
				<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif'; //phpcs:ignore ?>" id="arm_loader_img" class="arm_submit_btn_loader" style="display:none;" width="24" height="24" />&nbsp;<button class="arm_save_btn" type="submit" id="arm_social_settings_btn" name="arm_social_settings_btn"><?php esc_html_e('Apply Changes', 'ARMember');?></button>
                <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
				<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
			</div>
        </form>
        <div class="armclear"></div>
	</div>
</div>
