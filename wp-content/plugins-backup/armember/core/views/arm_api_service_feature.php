<?php
global $arm_global_settings, $arm_api_service_feature;
if(!$arm_api_service_feature->isAPIServiceFeature):
	wp_redirect(admin_url('admin.php?page=arm_general_settings'));
endif;

$arm_api_uri = home_url().'/wp-json/armember/v1/';

$all_global_settings = $arm_global_settings->arm_get_all_global_settings();
$general_settings = isset($all_global_settings['api_service']) ? $all_global_settings['api_service'] : array();

$arm_api_key = !empty($general_settings['arm_api_service_security_key']) ? $general_settings['arm_api_service_security_key'] : '';
if(empty($arm_api_key))
{
	$arm_api_key = $arm_api_service_feature->arm_generate_api_key();
}

$general_settings['arm_list_membership_plans'] = !empty($general_settings['arm_list_membership_plans']) ? $general_settings['arm_list_membership_plans'] : 0;
$general_settings['arm_membership_plan_details'] = !empty($general_settings['arm_membership_plan_details']) ? $general_settings['arm_membership_plan_details'] : 0;
$general_settings['arm_member_details'] = !empty($general_settings['arm_member_details']) ? $general_settings['arm_member_details'] : 0;
$general_settings['arm_member_memberships'] = !empty($general_settings['arm_member_memberships']) ? $general_settings['arm_member_memberships'] : 0;
$general_settings['arm_member_paid_posts'] = !empty($general_settings['arm_member_paid_posts']) ? $general_settings['arm_member_paid_posts'] : 0;
$general_settings['arm_member_payments'] = !empty($general_settings['arm_member_payments']) ? $general_settings['arm_member_payments'] : 0;
$general_settings['arm_member_paid_post_payments'] = !empty($general_settings['arm_member_paid_post_payments']) ? $general_settings['arm_member_paid_post_payments'] : 0;
$general_settings['arm_check_coupon_code'] = !empty($general_settings['arm_check_coupon_code']) ? $general_settings['arm_check_coupon_code'] : 0;
$general_settings['arm_member_add_membership'] = !empty($general_settings['arm_member_add_membership']) ? $general_settings['arm_member_add_membership'] : 0;
$general_settings['arm_create_transaction'] = !empty($general_settings['arm_create_transaction']) ? $general_settings['arm_create_transaction'] : 0;
$general_settings['arm_member_cancel_membership'] = !empty($general_settings['arm_member_cancel_membership']) ? $general_settings['arm_member_cancel_membership'] : 0;
$general_settings['arm_check_member_membership'] = !empty($general_settings['arm_check_member_membership']) ? $general_settings['arm_check_member_membership'] : 0;
?>
<div class="arm_global_settings_main_wrapper armPageContainer">
	<div class="page_sub_content">
		<form method="post" action="#" id="arm_api_security_key_form" class="arm_api_security_key_form arm_admin_form" onsubmit="return false;">
			<div class="page_sub_title arm_margin_bottom_32"><?php esc_html_e('API Services', 'ARMember'); ?></div>
			
			<div class="arm_setting_main_content arm_padding_0">
				<div class="arm_row_wrapper arm_row_wrapper_padding_before ">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Security Key', 'ARMember'); ?></div>
					</div>
				</div>
				<div class="arm_content_border"></div>
				<div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block ">
					<div class="arm_width_100_pct arm_display_flex">
						<input id="arm_api_security_key" class="arm_max_width_85_pct arm_margin_right_20" type="text" name="arm_general_settings[arm_api_security_key]" value="<?php echo esc_attr($arm_api_key); ?>" data-old_value="<?php echo esc_attr($arm_api_key); ?>">
						<button id="arm_generate_security_key" class="arm_button armemailaddbtn" onclick="generate_security_key()" type="button"><?php esc_html_e('Generate', 'ARMember'); ?></button>&nbsp;<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_generate_security_key_img" style="position: relative; top: 5px; left: 5px; display: none;" width="20" height="20">
					</div>
				</div>
			</div>
	
			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('List of Membership Plans','ARMember');?></div>
						<span for="arm_list_membership_plans" class="arm_global_setting_label"><?php esc_html_e('Get list of all membership plans.','ARMember');?></span>
						<?php $list_plan_tooltip = esc_attr__("Get list of membership plans ID, Name, and Description.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($list_plan_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_list_membership_plans" <?php checked($general_settings['arm_list_membership_plans'], '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_list_membership_plans]"/>
							<label for="arm_list_membership_plans" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_list_membership_plans_hide <?php echo ($general_settings['arm_list_membership_plans'] == '1') ? '' : ' hidden_section' ; ?> ">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
						<div class="arm_content_border"></div>
							<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember');?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_html($arm_api_uri).'arm_memberships?arm_api_key='.esc_html($arm_api_key); ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_memberships?arm_api_key='.esc_attr($arm_api_key); ?>"><?php esc_html_e('Click to copy', 'ARMember');?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember');?></span>
							</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>
			
			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Membership Plan Details','ARMember');?></div>
						<span for="arm_membership_plan_details" class="arm_global_setting_label"><?php esc_html_e('Details of certain membership plan.','ARMember');?></span>
						<?php $plan_details_tooltip = esc_attr__("Get membership plan details based on the plan ID.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($plan_details_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_membership_plan_details" <?php checked($general_settings['arm_membership_plan_details'], '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_membership_plan_details]"/>
							<label for="arm_membership_plan_details" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_membership_plan_details_hide <?php echo ($general_settings['arm_membership_plan_details'] == '1') ? '' : ' hidden_section' ; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
					
							<div class="arm_content_border"></div>
							<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember');?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_membership_details?arm_api_key='.esc_attr($arm_api_key).'&arm_plan_id={PLAN_ID}'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_membership_details?arm_api_key='.esc_attr($arm_api_key).'&arm_plan_id={PLAN_ID}'; ?>"><?php esc_html_e('Click to copy', 'ARMember');?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember');?></span>
							</div>
						</div>
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember');?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember');?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Plan ID', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_plan_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember');?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					
				</div>
			</div>

			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Member Details','ARMember');?></div>
						<span for="arm_member_details" class="arm_global_setting_label"><?php esc_html_e('Details of certain member.','ARMember');?></span>
						<?php $member_details_tooltip = esc_attr__("Get member details based on the ID.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($member_details_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_member_details" <?php checked($general_settings['arm_member_details'], '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_member_details]"/>
							<label for="arm_member_details" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_member_details_hide <?php echo ($general_settings['arm_member_details'] == '1') ? '' : ' hidden_section' ; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
						<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember');?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_member_details?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_metakeys={FIELD_METAKEYS}'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_member_details?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_metakeys={FIELD_METAKEYS}'; ?>"><?php esc_html_e('Click to copy', 'ARMember');?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember');?></span>
							</div>
						
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember');?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember');?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Member ID', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_user_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Field Metakeys', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_metakeys</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
								<?php $member_fields_tooltip = esc_attr__("Multiple Metakeys with comma separated.", 'ARMember'); ?>
								<span class="arm_api_field_default"><i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($member_fields_tooltip); ?>"></i></span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>

			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Member\'s Membership Plans','ARMember');?></div>
						<span for="arm_member_memberships" class="arm_global_setting_label"><?php esc_html_e('A list of member\'s membership plans.','ARMember');?></span>
						<?php $member_plans_tooltip = esc_attr__("Get list of member's plans based on the ID.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($member_plans_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_member_memberships" <?php checked($general_settings['arm_member_memberships'], '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_member_memberships]"/>
							<label for="arm_member_memberships" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_member_memberships_hide <?php echo ($general_settings['arm_member_memberships'] == '1') ? '' : ' hidden_section' ; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
							<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember');?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_member_memberships?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_page=1&arm_perpage=5'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_member_memberships?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_page=1&arm_perpage=5'; ?>"><?php esc_html_e('Click to copy', 'ARMember');?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember');?></span>
							</div>
						
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember');?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember');?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Member ID', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_user_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Page Number', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_page</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 1', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Per Page', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_perpage</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 5', 'ARMember');?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>

			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Member\'s Paid Posts','ARMember');?></div>
						<span for="arm_member_paid_posts" class="arm_global_setting_label"><?php esc_html_e('A list of member\'s paid posts.','ARMember');?></span>
						<?php $member_posts_tooltip = esc_attr__("Get list of member's paid posts based on the ID.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($member_posts_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_member_paid_posts" <?php checked($general_settings['arm_member_paid_posts'], '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_member_paid_posts]"/>
							<label for="arm_member_paid_posts" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_member_paid_posts_hide <?php echo ($general_settings['arm_member_paid_posts'] == '1') ? '' : ' hidden_section' ; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
							<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember');?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_member_paid_posts?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_page=1&arm_perpage=5'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_member_paid_posts?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_page=1&arm_perpage=5'; ?>"><?php esc_html_e('Click to copy', 'ARMember');?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember');?></span>
							</div>
						
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember');?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember');?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Member ID', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_user_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Page Number', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_page</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 1', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Per Page', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_perpage</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 5', 'ARMember');?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>

			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Member\'s Plan Payment Transactions','ARMember');?></div>
						<span for="arm_member_payments" class="arm_global_setting_label"><?php esc_html_e('A list of member\'s membership plan payment transactions.','ARMember');?></span>
						<?php $member_plan_transactions_tooltip = esc_attr__("Get member's membership plan payment transactions based on the ID.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($member_plan_transactions_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_member_payments" <?php checked($general_settings['arm_member_payments'], '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_member_payments]"/>
							<label for="arm_member_payments" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_member_payments_hide <?php echo ($general_settings['arm_member_payments'] == '1') ? '' : ' hidden_section' ; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
							<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember');?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_member_payments?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_page=1&arm_perpage=5'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_member_payments?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_page=1&arm_perpage=5'; ?>"><?php esc_html_e('Click to copy', 'ARMember');?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember');?></span>
							</div>
						
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember');?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember');?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Member ID', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_user_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Page Number', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_page</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 1', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Per Page', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_perpage</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 5', 'ARMember');?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>

			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Member\'s Paid Post Payment Transactions','ARMember');?></div>
						<sapn for="arm_member_paid_post_payments" class="arm_global_setting_label"><?php esc_html_e('A list of member\'s paid post payment Transactions.','ARMember');?></sapn>
						<?php $member_post_transactions_tooltip = esc_attr__("Get member's paid post payment transactions based on the ID.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($member_post_transactions_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_member_paid_post_payments" <?php checked($general_settings['arm_member_paid_post_payments'], '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_member_paid_post_payments]"/>
							<label for="arm_member_paid_post_payments" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_member_paid_post_payments_hide <?php echo ($general_settings['arm_member_paid_post_payments'] == '1') ? '' : ' hidden_section' ; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
							<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember');?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_member_paid_post_payments?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_page=1&arm_perpage=5'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_member_paid_post_payments?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_page=1&arm_perpage=5'; ?>"><?php esc_html_e('Click to copy', 'ARMember');?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember');?></span>
							</div>
						
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember');?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember');?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Member ID', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_user_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Page Number', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_page</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 1', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Per Page', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>arm_perpage</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 5', 'ARMember');?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>

			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Check Coupon Code','ARMember');?></div>
						<sapn for="arm_check_coupon_code" class="arm_global_setting_label"><?php esc_html_e('Check Coupon Code.','ARMember');?></sapn>
						<?php $check_coupon_code_tooltip = esc_attr__("Check coupon code valid with plan and calculate discount. Return discount, discount type, and coupon on subscriptions if coupon code valid.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($check_coupon_code_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_check_coupon_code" <?php checked($general_settings['arm_check_coupon_code'], '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_check_coupon_code]"/>
							<label for="arm_check_coupon_code" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_check_coupon_code_hide <?php echo ($general_settings['arm_check_coupon_code'] == '1') ? '' : ' hidden_section' ; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
							<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember');?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_check_coupon_code?arm_api_key='.esc_attr($arm_api_key).'&coupon_code={CODE}&plan_id={PLAN_ID}&gateway={GATEWAY}'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_check_coupon_code?arm_api_key='.esc_attr($arm_api_key).'&coupon_code={CODE}&plan_id={PLAN_ID}&gateway={GATEWAY}'; ?>"><?php esc_html_e('Click to copy', 'ARMember');?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember');?></span>
							</div>
						
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember');?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember');?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Coupon Code', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>coupon_code</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Plan ID', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>plan_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember');?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Payment Gateway', 'ARMember');?></span>
								<span class="arm_api_field_label"><code>gateway</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember');?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>
	
			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Add Plan to Member','ARMember');?></div>
						<sapn for="arm_member_add_membership" class="arm_global_setting_label"><?php esc_html_e('Add new membership plan to member.','ARMember');?></sapn>
						<?php $add_plan_member_tooltip = esc_attr__("Add plan to member using plan id. Return 1 if the membership was successfully added to Member.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($add_plan_member_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_member_add_membership" <?php checked($general_settings['arm_member_add_membership'], '1'); ?> value="1" class="armswitch_input" name="arm_general_settings[arm_member_add_membership]" />
							<label for="arm_member_add_membership" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_member_add_membership_hide <?php echo ($general_settings['arm_member_add_membership'] == '1') ? '' : 'hidden_section'; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
							<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember'); ?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_add_member_membership?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_plan_id={PLAN_ID}'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_add_member_membership?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_plan_id={PLAN_ID}'; ?>"><?php esc_html_e('Click to copy', 'ARMember'); ?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember'); ?></span>
							</div>
					
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember'); ?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember'); ?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Member ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_user_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember'); ?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Plan ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_plan_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember'); ?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>

			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Add Transaction','ARMember');?></div>
						<span for="arm_create_transaction" class="arm_global_setting_label"><?php esc_html_e('Add Payment Transaction.','ARMember');?></span>
						<?php $add_transaction_tooltip = esc_attr__("Add payment transaction to member. Return payment transaction log ID if the transaction was successfully added.", 'ARMember'); ?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($add_transaction_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_create_transaction" <?php checked($general_settings['arm_create_transaction'], '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_create_transaction]" />
							<label for="arm_create_transaction" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_create_transaction_hide <?php echo ($general_settings['arm_create_transaction'] == '1') ? '' : 'hidden_section'; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
						<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember'); ?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_add_member_transaction?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&plan_id={PLAN_ID}&arm_trans_id={TRANSACTION_ID}&gateway={GATEWAY}&arm_status={STATUS}&arm_amount={AMOUNT}&arm_total={TOTAL}&arm_tax_amount={TAX}&coupon_code={COUPON}&is_paid_post_payment=0&arm_paid_post_id=0'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_add_member_transaction?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&plan_id={PLAN_ID}&arm_trans_id={TRANSACTION_ID}&gateway={GATEWAY}&arm_status={STATUS}&arm_amount={AMOUNT}&arm_total={TOTAL}&arm_tax_amount={TAX}&coupon_code={COUPON}&is_paid_post_payment=0&arm_paid_post_id=0'; ?>"><?php esc_html_e('Click to copy', 'ARMember'); ?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember'); ?></span>
							</div>
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember'); ?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember'); ?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Member ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_user_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember'); ?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Plan ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>plan_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember'); ?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Transaction ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_trans_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember'); ?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Payment Gateway', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>gateway</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember'); ?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default:', 'ARMember'); ?> manual)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Status', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_status</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember'); ?>)</span>
								<?php $trans_status_tooltip = esc_attr__("Allows status", 'ARMember').': success, pending, canceled, failed'; ?>
								<span class="arm_api_field_default">(<?php esc_html_e('Default:', 'ARMember'); ?> pending) <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($trans_status_tooltip); ?>"></i></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Amount', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_amount</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember'); ?>)</span>
								<?php $tran_amount_tooltip = esc_attr__("Not including tax amount.", 'ARMember'); ?>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 0', 'ARMember'); ?>) <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($tran_amount_tooltip); ?>"></i></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Total', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_total</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember'); ?>)</span>
								<?php $tran_total_tooltip = esc_attr__("Including tax amount.", 'ARMember'); ?>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 0', 'ARMember'); ?>) <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($tran_total_tooltip); ?>"></i></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Tax Amount', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_tax_amount</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember'); ?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 0', 'ARMember'); ?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Coupon Code', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>coupon_code</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember'); ?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Paid Post Payment', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>is_post_payment</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember'); ?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 0', 'ARMember'); ?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Paid Post ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>is_post_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Optional', 'ARMember'); ?>)</span>
								<span class="arm_api_field_default">(<?php esc_html_e('Default: 0', 'ARMember'); ?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>

			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Cancel Member\'s Plan', 'ARMember'); ?></div>
						<span for="arm_member_cancel_membership" class="arm_global_setting_label"><?php esc_html_e('Cancel member\'s membership plan.', 'ARMember'); ?></span>
						<?php 
							$cancel_plan_member_tooltip = esc_attr__("Cancel membership plan to member. Return 1 if membership plan was successfully canceled.", 'ARMember');
						?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($cancel_plan_member_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_member_cancel_membership" <?php checked($general_settings['arm_member_cancel_membership'], '1'); ?> value="1" class="armswitch_input" name="arm_general_settings[arm_member_cancel_membership]" />
							<label for="arm_member_cancel_membership" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_member_cancel_membership_hide <?php echo ($general_settings['arm_member_cancel_membership'] == '1') ? '' : 'hidden_section'; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
						<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember'); ?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_cancel_member_membership?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_plan_id={PLAN_ID}'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_cancel_member_membership?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_plan_id={PLAN_ID}'; ?>"><?php esc_html_e('Click to copy', 'ARMember'); ?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore ?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember'); ?></span>
							</div>
						
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember'); ?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember'); ?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Member ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_user_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember'); ?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Plan ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_plan_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember'); ?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>
		
			<div class="arm_setting_main_content arm_margin_top_24">
				<div class="arm_row_wrapper">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Check Member\'s Membership', 'ARMember'); ?></div>
						<sapn for="arm_check_member_membership" class="arm_global_setting_label"><?php esc_html_e('Check member\'s membership plan assigned and activated.', 'ARMember'); ?></sapn>
						<?php 
							$check_member_plan_tooltip = esc_attr__("Check member has membership and activated. Return 1 if member has membership plan and activated.", 'ARMember');
						?>
						<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($check_member_plan_tooltip); ?>"></i>
					</div>
					<div class="right_content">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_check_member_membership" <?php checked($general_settings['arm_check_member_membership'], '1'); ?> value="1" class="armswitch_input" name="arm_general_settings[arm_check_member_membership]" />
							<label for="arm_check_member_membership" class="armswitch_label"></label>
						</div>
					</div>
				</div>
				<div class="form-field arm_check_member_membership_hide <?php echo ($general_settings['arm_check_member_membership'] == '1') ? '' : 'hidden_section'; ?>">
					<div class="arm-form-table-content form-field arm_exclude_role_for_hide_admin arm_margin_top_24">
						
							<div class="arm_content_border"></div>
						<label class="arm_padding_top_24"><?php esc_html_e('API URL', 'ARMember'); ?></label>
							<div class="arm_shortcode_text arm_form_shortcode_box">
								<span class="armCopyText arm_api_key_data_text" style="font-size: 13px;"><?php echo esc_attr($arm_api_uri).'arm_check_member_membership?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_plan_id={PLAN_ID}'; ?></span>
								<span class="arm_click_to_copy_text arm_api_key_data" data-code="<?php echo esc_attr($arm_api_uri).'arm_check_member_membership?arm_api_key='.esc_attr($arm_api_key).'&arm_user_id={USER_ID}&arm_plan_id={PLAN_ID}'; ?>"><?php esc_html_e('Click to copy', 'ARMember'); ?></span>
								<span class="arm_copied_text"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore ?>/copied_ok.png" alt="ok"><?php esc_html_e('Code Copied', 'ARMember'); ?></span>
							</div>
						
						<div class="armclear"></div>
						<div class="arm_margin_top_24">
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><strong><?php esc_html_e('Field Name', 'ARMember'); ?></strong></span>
								<span class="arm_api_field_label"><strong><?php esc_html_e('Parameters', 'ARMember'); ?></strong></span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Member ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_user_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember'); ?>)</span>
							</div>
							<div class="arm_api_fields">
								<span class="arm_api_field_name"><?php esc_html_e('Plan ID', 'ARMember'); ?></span>
								<span class="arm_api_field_label"><code>arm_plan_id</code></span>
								<span class="arm_api_field_optional">(<?php esc_html_e('Required', 'ARMember'); ?>)</span>
							</div>
						</div>
						<div class="armclear"></div>
					</div>
				</div>
			</div>


			<div class="arm_submit_btn_container arm_apply_changes_btn_container">
				<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif'; //phpcs:ignore ?>" id="arm_loader_img" style="position:relative;top:8px;display:none;" width="24" height="24" />&nbsp;<button id="arm_api_service_feature_btn" class="arm_save_btn" name="arm_api_service_feature_btn" type="submit"><?php esc_html_e('Apply Changes', 'ARMember') ?></button>
			</div>
			<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
				<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
		</form>
	</div>
</div>