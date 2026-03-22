<?php
global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_email_settings, $arm_manage_coupons, $arm_payment_gateways, $arm_subscription_plans;
$globals_settings = $arm_global_settings->arm_get_all_global_settings();
$global_currency = $arm_payment_gateways->arm_get_global_currency();
$period_type = 'daterange';

$action = 'add_coupon';


if (isset($_REQUEST['arm_action']) && isset($_REQUEST['coupon_eid']) && $_REQUEST['coupon_eid'] != '') {
    $form_mode = esc_html__('Edit Coupon', 'ARMember');
} else {
    $form_mode = esc_html__('Add Coupon', 'ARMember');
}
?>
<?php

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();
?>
<div class="wrap arm_page arm_add_edit_coupon_main_wrapper popup_wrapper">
    <div class="content_wrapper arm_email_settings_content" id="content_wrapper">
        <div class="popup_header page_title">
            <span class="arm_coupon_form_label arm_add_coupon_label"><?php esc_html_e('Add Coupon', 'ARMember'); ?></span>
            <span class="arm_coupon_form_label arm_edit_coupon_label hidden_section"><?php esc_html_e('Edit Coupon', 'ARMember'); ?></span>
            <span class="arm_popup_close_btn arm_add_edit_coupon_cls_btn"></span>           
            
        </div>
        <div class="armclear"></div>
        <?php
        $c_discount='';
        $c_sdate='';
        $c_edate='';
        $c_allowed_uses='';
        $c_label='';
        $c_data='';
        $arm_coupon_type = 1;
        if (isset($_REQUEST['arm_action']) && isset($_REQUEST['coupon_eid']) && $_REQUEST['coupon_eid'] != '') {
            $cid = intval($_REQUEST['coupon_eid']);
            $result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_id`=%d",$cid) ); //phpcs:ignore --Reason $ARMember->tbl_arm_coupons is a table name
            $c_data=$result;
            $c_id = $result->arm_coupon_id;
            $c_code = $result->arm_coupon_code;
            $c_discount = $result->arm_coupon_discount;
            $c_type = $result->arm_coupon_discount_type;
            $c_coupon_on_each_subscriptions = isset($result->arm_coupon_on_each_subscriptions) ? $result->arm_coupon_on_each_subscriptions : 0;
            $c_sdate = $result->arm_coupon_start_date;
            $c_edate = $result->arm_coupon_expire_date;
            $c_subs = $result->arm_coupon_subscription;
            $c_subs = @explode(',', $c_subs);
            $c_paid_posts = !empty($result->arm_coupon_paid_posts) ? $result->arm_coupon_paid_posts : array();
            $c_paid_posts = !empty($c_paid_posts) ? @explode(',', $c_paid_posts) : array();
            $c_allowed_uses = $result->arm_coupon_allowed_uses;
            $c_label= $result->arm_coupon_label;
            $coupon_status = $result->arm_coupon_status;
            $c_allow_trial = $result->arm_coupon_allow_trial;
            $form_id = 'arm_edit_coupon_wrapper_frm';
            $readonly = 'readonly = readonly';
            $period_type = (!empty($result->arm_coupon_period_type)) ? $result->arm_coupon_period_type : 'daterange';
            $arm_coupon_type = isset($result->arm_coupon_type) ? $result->arm_coupon_type : 1;
            $edit_mode = true;
            $today = date('Y-m-d H:i:s');
            $action = 'edit_coupon';
            if ($today > $c_sdate) {
                $sdate_status = $readonly;
            } else {
                $sdate_status = '';
            }
        } else {
            $form_id = 'arm_add_coupon_wrapper_frm';
            $c_id = 0;
            $coupon_status = 1;
            $c_allow_trial = 0;
            $c_coupon_on_each_subscriptions = 0;
            $c_type = 'fixed';
            $edit_mode = false;
            $sdate_status = '';
            $c_subs = array();
            $c_paid_posts = array();
        }
        ?>
        <form  method="post" action="#" id="<?php echo esc_attr($form_id); ?>" class="arm_add_edit_coupon_wrapper_frm arm_admin_form"> 
            <input type="hidden" name="arm_edit_coupon_id" value="<?php echo(!empty($c_id) ? esc_attr($c_id) : '') ?>" />
            <input type="hidden" name="arm_action" value="<?php echo esc_attr($action) ?>">
            <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
				<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
            <div class="arm_admin_form_content arm_form_main_content arm_padding_40">
                <table class="form-table">
                    <tr class="form-field form-required arm_width_100_pct">
                        <td class="arm_padding_bottom_0 arm_padding_0 arm_height_auto">
                        <div class="arm_form_header_label arm_padding_0"><?php esc_html_e('Coupon Details','ARMember');?></div>
                        </td>
                    </tr>
                    <tr class="form-field form-required arm_width_100_pct arm_coupon_code_row">
                        <th class="arm_padding_top_24"><label class="arm-black-350 arm_font_size_16"><?php esc_html_e('Coupon Code', 'ARMember'); ?></label></th>
                        <td class="arm_padding_bottom_0">
                            <input type="text" <?php echo $sdate_status; ?> id="arm_coupon_code" name="arm_coupon_code" class="arm_coupon_input_fields <?php echo (($edit_mode != true || $sdate_status=='') ? 'arm_coupon_code_input_field' : '') ?>" value="<?php echo (!empty($c_code) ? esc_html(stripslashes( esc_attr($c_code))) : ''); //phpcs:ignore?>" data-msg-required="<?php esc_attr_e('Generate Coupon Code.', 'ARMember'); ?>" required />
                            <?php if ($sdate_status == '') : ?>
                                <button id="arm_generate_coupon_code" class="arm_button armemailaddbtn arm_font_size_16" onclick="generate_code()" type="button"><?php esc_html_e('Generate Coupon Code', 'ARMember'); ?></button>&nbsp;<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL . '/arm_loader.gif'; //phpcs:ignore?>" id="arm_generate_coupon_img" class="arm_submit_btn_loader" style="top:5px;display:none;<?php echo (is_rtl()) ? 'right:5px;' : 'left:5px;'; ?>" width="20" height="20" />
                            <?php endif; ?>
                            <?php if ($edit_mode == TRUE && $sdate_status != '') { ?>
                                <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php esc_attr_e("Coupon code can't be changed, Because its usage has been started.", 'ARMember'); ?>"></i>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php 
                        echo $arm_manage_coupons->arm_coupon_form_html($c_discount,$c_type,$period_type,$sdate_status,$edit_mode,$c_sdate,$c_edate,$c_allow_trial,$c_allowed_uses,$c_label,$c_coupon_on_each_subscriptions,$coupon_status,$c_subs,$c_data, $arm_coupon_type,$c_paid_posts); //phpcs:ignore
                    ?>
                    
                </table>
                <div class="armclear"></div>
                <!--<div class="arm_divider"></div>-->
                <div class="arm_submit_btn_container">
                    <img src="<?php echo MEMBERSHIPLITE_IMAGES_URL . '/arm_loader.gif'; //phpcs:ignore?>" id="arm_loader_img_bulk_coupon_field" class="arm_loader_img arm_submit_btn_loader" style=" <?php echo (is_rtl()) ? '' : 'top: 12px; float:left'; ?>;display: none; left:77%" width="20" height="20" />

                    <?php if (!$edit_mode) { ?>
                        <input type="hidden" name="op_type" id="form_type" value="add" />
                    <?php } else { ?>
                        <input type="hidden" name="op_type" id="form_type" value="edit" />
                    <?php } ?>
                    <a class="arm_cancel_btn arm_add_edit_coupon_cls_btn" href="javascript:void(0)"><?php esc_html_e('Close', 'ARMember') ?></a>
                    <button id="arm_coupon_operation" class="arm_save_btn" data-id="<?php echo esc_attr($c_id); ?>" data-type="edit" type="submit"><?php esc_html_e('Save', 'ARMember') ?></button>
                </div>
                <div class="armclear"></div>
            </div>
        </form>
        <div class="armclear"></div>
    </div>
</div>
<?php
    echo $ARMember->arm_get_need_help_html_content('member-coupon-add'); //phpcs:ignore
?>