<?php
global $arm_global_settings,$arm_member_forms,$wp,$wpdb,$ARMember,$arm_pay_per_post_feature,$arm_is_plan_limit_feature;

$arm_html_content='';

$common_messages         = $arm_global_settings->arm_get_all_common_message_settings();

$default_common_messages = $arm_global_settings->arm_default_common_messages();

if($section=="social_connect"){
    $social_failed_login_msg = (!empty($common_messages['social_login_failed_msg'])) ? esc_html(stripslashes($common_messages['social_login_failed_msg'])) : '';
    $arm_html_content = '<tr class="form-field">
        <th class="arm-form-table-label armember_general_setting_lbl"><label for="social_login_failed_msg">'. esc_html__('Login Failed Message for Social Connect', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[social_login_failed_msg]" id="social_login_failed_msg" value="'. $social_failed_login_msg .'"/>
        </td>
    </tr>';
}
if($section=="payment_related"){
    $stripe_payment_failed_msg = (!empty($common_messages['arm_payment_fail_stripe'])) ? esc_attr($common_messages['arm_payment_fail_stripe']) : '';

    $authorize_payment_failed_msg = (!empty($common_messages['arm_payment_fail_authorize_net'])) ? esc_attr($common_messages['arm_payment_fail_authorize_net']) : '';

    $checkout_payment_failed_msg = (!empty($common_messages['arm_payment_fail_2checkout'])) ? esc_attr($common_messages['arm_payment_fail_2checkout']) : '';

    $invalid_card_detail_msg = (!empty($common_messages['arm_invalid_credit_card'])) ? esc_attr($common_messages['arm_invalid_credit_card']) : '';

    $unauthorized_card_detail_msg = (!empty($common_messages['arm_unauthorized_credit_card'])) ? esc_attr($common_messages['arm_unauthorized_credit_card']) : '';

    $declined_credit_card_msg = (!empty($common_messages['arm_credit_card_declined'])) ? esc_attr($common_messages['arm_credit_card_declined']) : '';

    $blank_expiry_month_msg = (!empty($common_messages['arm_blank_expire_month'])) ? esc_attr($common_messages['arm_blank_expire_month']) : '';

    $blank_expiry_year_msg = (!empty($common_messages['arm_blank_expire_year'])) ? esc_attr($common_messages['arm_blank_expire_year']) : '';

    $blank_cvc_msg = (!empty($common_messages['arm_blank_cvc_number'])) ? esc_attr($common_messages['arm_blank_cvc_number']): '';

    $blank_credit_card_number_msg = (!empty($common_messages['arm_blank_credit_card_number'])) ? esc_attr($common_messages['arm_blank_credit_card_number']) : '';

    $arm_html_content = '<tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_payment_fail_stripe">'. esc_html__('Payment Fail (Stripe)', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_payment_fail_stripe]" id="arm_payment_fail_stripe" value="'. $stripe_payment_failed_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_payment_fail_authorize_net">'. esc_html__('Payment Fail (Authorize.net)', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_payment_fail_authorize_net]" id="arm_payment_fail_authorize_net" value="'. $authorize_payment_failed_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_payment_fail_2checkout">'. esc_html__('Payment Fail (2Checkout)', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_payment_fail_2checkout]" id="arm_payment_fail_2checkout" value="'. $checkout_payment_failed_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_invalid_credit_card">'. esc_html__('Invalid Credit Card Detail', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_invalid_credit_card]" id="arm_invalid_credit_card" value="'. $invalid_card_detail_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_unauthorized_credit_card">'. esc_html__('Credit Card Not Authorized', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_unauthorized_credit_card]" id="arm_unauthorized_credit_card" value="'. $unauthorized_card_detail_msg .'"/>
            <br/><span class="remained_login_attempts_notice">('. esc_html__('in case of Authorize.net payment gateway only.','ARMember').')</span>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_credit_card_declined">'. esc_html__('Credit Card Declined', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_credit_card_declined]" id="arm_credit_card_declined" value="'. $declined_credit_card_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_blank_expire_month">'. esc_html__('Blank Expiry Month', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_blank_expire_month]" id="arm_blank_expire_month" value="'. $blank_expiry_month_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_blank_expire_year">'. esc_html__('Blank Expiry Year', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_blank_expire_year]" id="arm_blank_expire_year" value="'. $blank_expiry_year_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_blank_cvc_number">'. esc_html__('Blank CVC Number', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_blank_cvc_number]" id="arm_blank_cvc_number" value="'. $blank_cvc_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_blank_credit_card_number">'. esc_html__('Blank Credit Card Number', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_blank_credit_card_number]" id="arm_blank_credit_card_number" value="'.  $blank_credit_card_number_msg .'"/>
        </td>
    </tr>';
}
if($section=="bank_payment_gateway"){
    $do_not_allow_pending_msg = (!empty($common_messages['arm_do_not_allow_pending_payment_bank_transfer'])) ? esc_attr($common_messages['arm_do_not_allow_pending_payment_bank_transfer']) : esc_html(stripslashes($default_common_messages['arm_do_not_allow_pending_payment_bank_transfer']));
    $arm_html_content = '<tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_do_not_allow_pending_payment_bank_transfer">'. esc_html__('Do not allow pending payment (Bank Transfer)', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_do_not_allow_pending_payment_bank_transfer]" id="arm_do_not_allow_pending_payment_bank_transfer" value="'. $do_not_allow_pending_msg .'"/>
        </td>
    </tr>';
}
if($section == 'coupon_related'){
    $coupon_applied_success_msg = (!empty($common_messages['arm_success_coupon'])) ? esc_attr($common_messages['arm_success_coupon']) : '';

    $coupon_empty_msg = (!empty($common_messages['arm_empty_coupon'])) ? esc_attr($common_messages['arm_empty_coupon']) : '';

    $coupon_expired_msg = (!empty($common_messages['arm_coupon_expire'])) ? esc_attr($common_messages['arm_coupon_expire']) : '';

    $invalid_coupon_msg = (!empty($common_messages['arm_invalid_coupon'])) ? esc_attr($common_messages['arm_invalid_coupon']) : '';

    $invalid_coupon_plan_msg = (!empty($common_messages['arm_invalid_coupon_plan'])) ? esc_attr($common_messages['arm_invalid_coupon_plan']) : '';

    $arm_html_content = '<div class="page_sub_title">'. esc_html__('Coupon Related Messages', 'ARMember').'</div>
    <div class="armclear"></div>
    <table class="form-table">
        <tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_success_coupon">'. esc_html__('Coupon Applied Successfully', 'ARMember').'</label></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_success_coupon]" id="arm_success_coupon" value="'. $coupon_applied_success_msg .'"/>
            </td>
        </tr>
        <tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_empty_coupon">'. esc_html__('Coupon Empty', 'ARMember') .'</label></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_empty_coupon]" id="arm_empty_coupon" value="'. $coupon_empty_msg .'"/>
            </td>
        </tr>
        <tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_coupon_expire">'. esc_html__('Coupon Expired', 'ARMember').'</label></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_coupon_expire]" id="arm_coupon_expire" value="'. $coupon_expired_msg .'"/>
            </td>
        </tr>
        <tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_invalid_coupon">'. esc_html__('Invalid Coupon', 'ARMember').'</label></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_invalid_coupon]" id="arm_invalid_coupon" value="'. $invalid_coupon_msg .'"/>
            </td>
        </tr>
        <tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_invalid_coupon_plan">'. esc_html__('Invalid Coupon For Plan', 'ARMember').'</label></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_invalid_coupon_plan]" id="arm_invalid_coupon_plan" value="'. $invalid_coupon_plan_msg .'"/>
            </td>
        </tr>
    </table>';
}
if($section=='directory_search'){
    $search_filter_title = (!empty($common_messages['profile_template_search_filter_title'])) ? esc_attr($common_messages['profile_template_search_filter_title']) : esc_attr__('Search Members', 'ARMember');

    $sort_by_field_label = (!empty($common_messages['directory_sort_by_field'])) ? esc_attr($common_messages['directory_sort_by_field']) : esc_attr__('Sort By', 'ARMember');

    $arm_html_content = '<tr class="form-field">
    <th class="arm-form-table-label"><label for="profile_template_search_filter_title">'. esc_html__('Search Filter Title', 'ARMember').'</label></th>
    <td class="arm-form-table-content">
        <input type="text" name="arm_common_message_settings[profile_template_search_filter_title]" id="profile_template_search_filter_title" value="'. $search_filter_title .'"/>
    </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="directory_sort_by_field">'. esc_html__('Sort By (Directory Filter)', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[directory_sort_by_field]" id="directory_sort_by_field" value="'. $sort_by_field_label .'"/>
        </td>
    </tr>';
}
if($section=='personal_detail'){

    $personal_detail_msg = (isset($common_messages['arm_profile_member_personal_detail'])) ? esc_attr($common_messages['arm_profile_member_personal_detail']) : esc_attr__('Personal Details', 'ARMember');

    $arm_html_content = '<tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_profile_member_personal_detail">'. esc_html__('Personal Details', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_profile_member_personal_detail]" id="arm_profile_member_personal_detail" value="'. $personal_detail_msg .'"/>
        </td>
    </tr>';
}
if($section == 'directory_filter'){
    $search_placeholder_msg = (isset($common_messages['arm_directory_search_placeholder'])) ? esc_attr($common_messages['arm_directory_search_placeholder']) : esc_attr__('Search', 'ARMember');

    $search_btn_msg = (isset($common_messages['arm_directory_search_button'])) ? esc_attr($common_messages['arm_directory_search_button']) : esc_attr__('Search', 'ARMember');

    $reset_btn_msg = (isset($common_messages['arm_directory_reset_button'])) ? esc_attr($common_messages['arm_directory_reset_button']) : esc_attr__('Reset', 'ARMember');

    $arm_html_content = '<tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_directory_search_placeholder">'. esc_html__('Directory Search Placeholder', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_directory_search_placeholder]" id="arm_directory_search_placeholder" value="'. $search_placeholder_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_directory_search_button">'. esc_html__('Directory Search Button', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_directory_search_button]" id="arm_directory_search_button" value="'. $search_btn_msg .'"/>
        </td>
    </tr>
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_directory_reset_button">'. esc_html__('Directory Reset Button', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_directory_reset_button]" id="arm_directory_reset_button" value="'. $reset_btn_msg .'"/>
        </td>
    </tr>';
}
if($section == 'miscellaneous'){

    $invalid_recaptcha_msg = (!empty($common_messages['arm_recptcha_invalid'])) ? esc_attr($common_messages['arm_recptcha_invalid']) : 'Google reCAPTCHA Invalid or Expired. Please reload page and try again.';

    $arm_html_content = '
    <tr class="form-field">
        <th class="arm-form-table-label"><label for="arm_recptcha_invalid">'. esc_html__('Invalid reCAPTCHA', 'ARMember').'</label></th>
        <td class="arm-form-table-content">
            <input type="text" name="arm_common_message_settings[arm_recptcha_invalid]" id="arm_recptcha_invalid" value="'.$invalid_recaptcha_msg.'"/>
        </td>
    </tr>';
    
    if($arm_is_plan_limit_feature==1)
    {               
        $purchase_limit_exceed_msg = (!empty($common_messages['arm_purchase_limit_error'])) ? esc_attr($common_messages['arm_purchase_limit_error']) : 'Sorry, purchase limit has been exceeded.';
        $arm_html_content .= '<tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_purchase_limit_error">'. esc_html__('Membership Limit Message', 'ARMember').'</label></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_purchase_limit_error]" id="arm_purchase_limit_error" value="'. $purchase_limit_exceed_msg .'"/>
            </td>
        </tr>';
    }
}