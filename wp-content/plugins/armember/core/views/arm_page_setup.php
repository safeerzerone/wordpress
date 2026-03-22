<?php
global $arm_global_settings,$arm_member_forms,$wp,$wpdb,$ARMember,$arm_pay_per_post_feature;
$arm_html_content = '';
if($section=='paid_post'){
    if($arm_pay_per_post_feature->isPayPerPostFeature){
        $arm_all_global_settings = $arm_global_settings->arm_get_all_global_settings();
        $page_settings = $arm_all_global_settings['page_settings'];
        $arm_html_content = '<div class="arm_form_field_block">
            <label class="arm-form-table-label">'. esc_html__('Paid Post Page', 'ARMember').'</label>
            <div class="arm-form-table-content">
                <span data-type="arm_setup" class="arm_page_type"></span>';
                $page_settings['paid_post_page_id'] = isset($page_settings['paid_post_page_id']) ? $page_settings['paid_post_page_id'] : 0;
                $is_valid_md_page = $arm_global_settings->arm_shortcode_exist_in_page('arm_setup', $page_settings['paid_post_page_id']);
                $arm_html_content .= $arm_global_settings->arm_wp_dropdown_pages(
                    array(
                        'selected' => $page_settings['paid_post_page_id'],
                        'name' => 'arm_page_settings[paid_post_page_id]',
                        'id' => 'paid_post_page_id',
                        'echo'=>0,
                        'show_option_none' => esc_html__('Select Page', 'ARMember'),
                        'option_none_value' => '0',
                        'class' => 'arm_page_setup_input',
                    )
                );
                $is_valid_msg = ($is_valid_md_page) ? 'arm_no_error' : '';
                $arm_html_content .= '<i class="armfa armfa-1x armfa-refresh armfa-spin arm_refresh arm_no_error"></i>
                <i class="armfa armfa-1x armfa-check arm_check arm_no_error"></i>
                <span class="arm_error_msg '. $is_valid_msg.'">'. esc_html__('Shortcode of Paid Post Setup not found on selected page. Please add shortcode on that page Or please select appropriate page.', 'ARMember').'</span>
            </div>
        </div>';
    }
    $nonce = wp_create_nonce('arm_wp_nonce');
    $arm_html_content .= '<input type="hidden" name="arm_wp_nonce" value="'.$nonce.'" />';
}