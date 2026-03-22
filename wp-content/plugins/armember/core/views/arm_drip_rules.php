<?php
global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_access_rules, $arm_subscription_plans, $arm_drip_rules;
$all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name');

global $arm_members_activity;
$drip_types = $arm_drip_rules->arm_drip_rule_types();
?>
<div class="wrap arm_page arm_drip_content_main_wrapper">

	<div class="content_wrapper arm_drip_content_container" id="content_wrapper">
		<div class="page_title">
			<?php esc_html_e('Drip Content','ARMember');
                        if(!empty($all_plans))
                        {
                        ?>
                    
			<div class="arm_add_new_item_box">
				<a class="greensavebtn arm_add_drip_rule_link" href="javascrip:void(0)"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.svg"><span><?php esc_html_e('Add Rule', 'ARMember') ?></span></a>
			</div>
                        <?php } ?>
                        
			<div class="armclear"></div>
		</div>
		<div class="arm_solid_divider"></div>
		<div id="arm_drip_rules_grid_container" class="arm_drip_rules_grid_container">
			<?php 
			if (file_exists(MEMBERSHIP_VIEWS_DIR . '/arm_drip_rules_list_records.php')) {
				include( MEMBERSHIP_VIEWS_DIR.'/arm_drip_rules_list_records.php');
			}
			?>
		</div>
		<div class="armclear"></div>
	</div>
</div>
<?php 
/* **********./Begin Bulk Delete Drip Rules Popup/.********** */
$bulk_delete_drip_rules_popup_content = '<span class="arm_confirm_text">'.esc_html__("Are you sure you want to delete this rule(s)?",'ARMember' ).'</span>';
$bulk_delete_drip_rules_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
$bulk_delete_drip_rules_popup_arg = array(
	'id' => 'delete_bulk_drip_rules_message',
	'class' => 'arm_delete_bulk_action_message delete_bulk_drip_rules_message',
	'title' => 'Delete Rule(s)',
	'content' => $bulk_delete_drip_rules_popup_content,
	'button_id' => 'arm_bulk_delete_drip_rules_ok_btn',
	'button_onclick' => "arm_delete_bulk_drip_rules('true');",
	'ok_btn_text' => esc_html__('Delete Rules','ARMember')
);
echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_drip_rules_popup_arg); //phpcs:ignore
/* **********./End Bulk Delete Drip Rules Popup/.********** */
?>
<!--./******************** Add New Drip Rule Form ********************/.-->
<div class="arm_add_new_drip_rule_wrapper popup_wrapper">
	<div class="content_wrapper arm_add_edit_drip_rule_content" id="content_wrapper">
		<div class="popup_header page_title">
			<span class="arm_add_new_drip_rule_title"><?php esc_html_e('Add New Drip Rule','ARMember');?></span>
			<span class="arm_edit_drip_rule_title hidden_section"><?php esc_html_e('Edit Drip Rule','ARMember');?></span>
			<span class="arm_popup_close_btn add_new_drip_rule_close_btn"></span>
		</div>
		<div class="armclear"></div>
		<form method="post" action="#" id="arm_add_new_drip_rule_wrapper_frm" class="arm_admin_form arm_add_new_drip_rule_wrapper_frm">
			<div class="arm_admin_form_content">
				<div class="arm_admin_form_content_inner">
					<div class="arm_form_main_content arm_padding_40">
						<div class="arm_setup_section_title arm_margin_top_0 arm_margin_bottom_24"> <?php esc_html_e('Drip Content Rules','ARMember'); ?></div>
						<table class="form-table arm_content_type_table">
							<tr class="form-field form-required arm_auto_user_field">
								<th class="arm_font_size_16">
									<label><?php esc_html_e('Select content type', 'ARMember');?></label>
								</th>
								<td class="arm_margin_0 arm_padding_bottom_0">
									<input type="hidden" id="arm_add_rule_item_type" class="arm_rule_item_type_input" name="item_type" data-type="" value="page"/>
									<dl class="arm_selectbox column_level_dd arm_width_100_pct">
										<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
										<dd>
											<ul data-id="arm_add_rule_item_type">
												<?php 
												if (!empty($dripContentTypes)) {
													foreach ($dripContentTypes as $key => $val) {
														?><li data-label="<?php echo esc_attr($val);?>" data-value="<?php echo esc_attr($key);?>" data-type="<?php echo esc_attr($val);?>"><?php echo esc_attr($val);?></li><?php
													}
												}
												?>
											</ul>
										</dd>
									</dl>
								</td>
							</tr>
						</table>
						<div class="arm_field_spacing_div"></div>
						<div class="arm_drip_post_type_opts">
							<table class="form-table arm_content_type_table arm_post_page">
								<tr class="form-field form-required arm_auto_user_field">
									<th class="arm_font_size_16">
										<?php esc_html_e('Select', 'ARMember');?> <span class="arm_rule_item_type_text"><?php esc_html_e('Page', 'ARMember')?></span>
									</th>
									<td class="arm_height_auto arm_required_wrapper arm_margin_0 arm_padding_bottom_0">
										<div class="arm_text_align_center arm_width_100_pct" >
											<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_loader_img_drip_rule_items" class="arm_loader_img_drip_rule_items" style="display: none;" width="20" height="20" />
										</div>
										<input id="arm_drip_rule_items_input" type="text" value="" placeholder="<?php esc_attr_e('Search by title...', 'ARMember');?>" required data-msg-required="<?php esc_attr_e('Please select atleast one page/post.', 'ARMember');?>" class="arm_max_width_100_pct arm_width_100_pct">
										<div class="arm_drip_rule_items_list_container" id="arm_drip_rule_items_list_container"></div>
										<div class="arm_drip_rule_items " id="arm_drip_rule_items" style="display: none;"></div>
									</td>
								</tr>
							</table>
						</div>
						<div class="arm_drip_custom_content_opts hidden_section">
							<table class="form-table arm_content_type_table arm_post_page">
								<tr class="form-field form-required arm_auto_user_field">
									<th class="arm_font_size_16">
										<?php esc_html_e('Shortcode', 'ARMember');?>
									</th>
									<td class="arm_margin_0 arm_padding_bottom_0">
										<div class="arm_drip_custom_content_shortcode">
											<pre>[arm_drip_content id='xx']</pre>
											<pre>    <?php esc_html_e('Put Your Drip Content Here.', 'ARMember');?></pre>
											<pre>[arm_drip_else]</pre>
											<pre>    <?php esc_html_e('Put Your Restricted Content Message Here.', 'ARMember'); ?></pre>
											<pre>[/arm_drip_content]</pre>
										</div>
									</td>
								</tr>
							</table>
						</div>
						<div class="arm_field_spacing_div"></div>				
						<table class="form-table arm_content_type_table arm_post_page">
							<tr class="form-field">
								<th class="arm_font_size_16">
									<label><?php esc_html_e('Select Membership plans', 'ARMember');?></label>
								</th>
								<td class="arm_height_auto arm_required_wrapper arm_margin_0 arm_padding_bottom_0">
									<select id="arm_drip_rule_plans" class="arm_chosen_selectbox arm_width_500" data-msg-required="<?php esc_attr_e('Please select atleast one plan.', 'ARMember');?>" name="rule_plans[]" data-placeholder="<?php esc_attr_e('Select Plan(s)..', 'ARMember');?>" multiple="multiple" >
										<?php if (!empty($all_plans)):?>
											<?php foreach ($all_plans as $plan): ?>
												<option class="arm_message_selectbox_op" value="<?php echo esc_attr($plan['arm_subscription_plan_id']);?>"><?php echo stripslashes( esc_html($plan['arm_subscription_plan_name'])); //phpcs:ignore?></option>
											<?php endforeach;?>
										<?php else: ?>
											<option value=""><?php esc_html_e('No Subscription Plans Available', 'ARMember');?></option>
										<?php endif;?>
									</select>
								</td>
							</tr>
						</table>
						<div class="arm_field_spacing_div"></div>
					
						<table class="form-table arm_content_type_table">
							<tr class="form-field arm_width_100_pct">
								<th class="arm_font_size_16">
									<label><?php esc_html_e('Select Drip Type', 'ARMember');?></label>
								</th>
								<td class="arm_height_auto arm_margin_0 arm_padding_bottom_0">
									<input type="hidden" class="arm_drip_type_input" id="arm_add_drip_type" name="rule_type" value="instant"/>
										<dl class="arm_selectbox column_level_dd arm_width_100_pct">
											<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
											<dd>
												<ul data-id="arm_add_drip_type">
													<?php foreach($drip_types as $key => $val):?>
													<li data-label="<?php echo esc_attr($val);?>" data-value="<?php echo esc_attr($key);?>"><?php echo esc_attr($val);?></li>
													<?php endforeach;?>
												</ul>
											</dd>
										</dl>
										<div class="armclear"></div>
										<div class="arm_drip_type_options_wrapper arm_drip_wrapper_immediate">
											<div class="armswitch arm_display_flex arm_margin_bottom_20">
												<label for="arm_drip_expiration_immediate" class="arm_margin_right_25"><?php esc_html_e('Expiration','ARMember')?></label>
												<input type="checkbox" class="armswitch_input arm_private_content_status_action arm_drip_expiration_drip_type_immediate" id="arm_drip_expiration_immediate" name="rule_options[rule_expire_immediate]" value="1" data-item_id="2">
												<label class="armswitch_label arm_margin_left_0" for="arm_drip_expiration_immediate"></label>
												<i class="arm_helptip_icon armfa armfa-question-circle arm_margin_left_10" title="<?php echo esc_html("When enable the expiration for dripped content then allowed access will be restricted as per the expiration settings. Expiration of the dripped content will be calculated time period after the content is dripped to the member.", 'ARMember');?>"></i>
											</div>
											
											<div class="arm_drip_expire_after_immediate hidden_section">
												<label class="arm_hide_after_drip arm_font_size_15 arm_margin_right_16"><?php esc_html_e('Hide After', 'ARMember');?></label>
												<input type="number" id="arm_drip_type_exp_days" name="rule_options[expire_immediate_days]" min="0" value="10" data-msg-required="<?php esc_attr_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
												<input type="hidden" name="rule_options[expire_immediate_duration]" id="arm_drip_type_exp_dmy" value="day">
												<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
													<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
													<dd>
														<ul data-id="arm_drip_type_exp_dmy">
															<li data-label="<?php esc_attr_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
															<li data-label="<?php esc_attr_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
															<li data-label="<?php esc_attr_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
														</ul>
													</dd>
												</dl>
												<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
												<input type="hidden" name="rule_options[expire_duration_immediate_time]" id="arm_drip_type_exp_time" value="00:00">
												<dl class="arm_selectbox column_level_dd arm_drip_type_time">
													<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
													<dd>
														<ul data-id="arm_drip_type_exp_time">
															<?php for($i=0; $i<24 ; $i++)
															{?>
																<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
															<?php }?>
														</ul>
													</dd>
												</dl>
											</div>
										</div>
										<div class="arm_drip_type_options_wrapper arm_drip_type_options_days hidden_section arm_required_wrapper" id="arm_drip_type_options_days">
											<div class="arm_drip_type_options_container">
												<label class="arm_font_size_15 arm_margin_right_14"><?php esc_html_e('Show After', 'ARMember');?></label>
												<input type="number" id="arm_drip_type_days" name="rule_options[days]" min="0" value="10" data-msg-required="<?php esc_attr_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
												<input type="hidden" name="rule_options[duration]" id="arm_drip_type_dmy" value="day">
												<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
													<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
													<dd>
														<ul data-id="arm_drip_type_dmy">
															<li data-label="<?php esc_attr_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
															<li data-label="<?php esc_attr_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
															<li data-label="<?php esc_attr_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
														</ul>
													</dd>
												</dl>
													<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
												<input type="hidden" name="rule_options[duration_time]" id="arm_drip_type_time" value="00:00">
												<dl class="arm_selectbox column_level_dd arm_drip_type_time">
													<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
													<dd>
														<ul data-id="arm_drip_type_time">
															<?php for($i=0; $i<24 ; $i++)
															{?>
																<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
															<?php }?>
														</ul>
													</dd>
												</dl>
											</div>
											
											<div class="arm_drip_type_options_container">
											
												<div class="armswitch arm_display_flex arm_margin_bottom_20">
												<label for="arm_drip_expiration_days" class="arm_margin_right_25"><?php esc_html_e('Expiration','ARMember')?></label>
												<input type="checkbox" class="armswitch_input arm_private_content_status_action arm_drip_expiration_drip_type_days" id="arm_drip_expiration_days" name="rule_options[rule_expire_days]" value="1" data-item_id="2">
												<label class="armswitch_label arm_margin_left_0" for="arm_drip_expiration_days"></label>
												
												<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_html("When enable the expiration for dripped content then allowed access will be restricted as per the expiration settings. Expiration of the dripped content will be calculated time period after the content is dripped to the member.", 'ARMember');?>"></i>
												</div>
												<div class="arm_drip_expire_after_days hidden_section">
													<label class="arm_hide_after_drip arm_font_size_15 arm_margin_right_16"><?php esc_html_e('Hide After', 'ARMember');?></label>
													<input type="number" id="arm_drip_type_exp_days" name="rule_options[expire_days]" min="0" value="10" data-msg-required="<?php esc_attr_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
													<input type="hidden" name="rule_options[expire_duration]" id="arm_drip_type_exp_dmy" value="day">
													<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
														<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
														<dd>
															<ul data-id="arm_drip_type_exp_dmy">
																<li data-label="<?php esc_attr_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
																<li data-label="<?php esc_attr_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
																<li data-label="<?php esc_attr_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
															</ul>
														</dd>
													</dl>
													<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
													<input type="hidden" name="rule_options[expire_duration_time]" id="arm_drip_type_exp_time" value="00:00">
													<dl class="arm_selectbox column_level_dd arm_drip_type_time">
														<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
														<dd>
															<ul data-id="arm_drip_type_exp_time">
																<?php for($i=0; $i<24 ; $i++)
																{?>
																	<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
																<?php }?>
															</ul>
														</dd>
													</dl>
												</div>
											</div>
										</div>
										<div class="arm_drip_type_options_wrapper arm_drip_type_options_post_publish hidden_section arm_required_wrapper" id="arm_drip_type_options_post_publish">
											<div class="arm_drip_type_options_container">
												<label class="arm_font_size_15 arm_margin_right_14"><?php esc_html_e('Show After', 'ARMember');?></label>
												<input type="number" class="arm_margin_left_15" id="arm_edit_drip_type_post_publish_add" name="rule_options[post_publish]" min="0" value="10" data-msg-required="<?php esc_attr_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
												<input type="hidden" name="rule_options[post_publish_duration]" value="day" id="arm_drip_type_dmy">
												<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
													<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
													<dd>
														<ul data-id="arm_drip_type_dmy">
															<li data-label="<?php esc_attr_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
															<li data-label="<?php esc_attr_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
															<li data-label="<?php esc_attr_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
														</ul>
													</dd>
												</dl>
												<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
												<input type="hidden" name="rule_options[post_publish_duration_time]" id="arm_drip_type_time" value="00:00">
												<dl class="arm_selectbox column_level_dd arm_drip_type_time">
													<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
													<dd>
														<ul data-id="arm_drip_type_time">
															<?php for($i=0; $i<24 ; $i++)
															{?>
																<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
															<?php }?>
														</ul>
													</dd>
												</dl>
											</div>
											
											<div class="arm_drip_type_options_container">
											
												<div class="armswitch arm_display_flex arm_margin_bottom_20">
												<label for="arm_drip_exarm_drip_type_expire_post_publish piration_days" class="arm_margin_right_25"><?php esc_html_e('Expiration','ARMember')?></label>
												<input type="checkbox" class="armswitch_input arm_private_content_status_action arm_drip_type_expire_post_publish" id="arm_drip_type_expire_post_publish" name="rule_options[rule_expire_post_publish]" value="1" data-item_id="2">
												<label class="armswitch_label arm_margin_left_0" for="arm_drip_type_expire_post_publish"></label>
												<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_html("When enable the expiration for dripped content then allowed access will be restricted as per the expiration settings. Expiration of the dripped content will be calculated time period after the content is dripped to the member.", 'ARMember');?>"></i>
												</div>

												<div class="arm_drip_expire_post_publish hidden_section">
													<label class="arm_hide_after_drip arm_font_size_15 arm_margin_right_16"><?php esc_html_e('Hide After', 'ARMember');?></label>
													<input type="number" id="arm_edit_drip_type_post_publish_add" name="rule_options[exp_post_publish]" min="0" value="10" data-msg-required="<?php esc_attr_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
													<input type="hidden" name="rule_options[post_publish_exp_duration]" value="day" id="arm_drip_exp_dmy">
													<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
														<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
														<dd>
															<ul data-id="arm_drip_exp_dmy">
																<li data-label="<?php esc_attr_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
																<li data-label="<?php esc_attr_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
																<li data-label="<?php esc_attr_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
															</ul>
														</dd>
													</dl>
													<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
													<input type="hidden" name="rule_options[post_publish_exp_duration_time]" id="arm_drip_exp_time" value="00:00">
													<dl class="arm_selectbox column_level_dd arm_drip_type_time">
														<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
														<dd>
															<ul data-id="arm_drip_exp_time">
																<?php for($i=0; $i<24 ; $i++)
																{?>
																	<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
																<?php }?>
															</ul>
														</dd>
													</dl>
												</div>
											</div>
										</div>
										<div class="arm_drip_type_options_wrapper arm_drip_type_options_post_modify hidden_section arm_required_wrapper" id="arm_drip_type_options_post_modify">
											<div class="arm_drip_type_options_container">
												<label class="arm_font_size_15 arm_margin_right_14"><?php esc_html_e('Show After', 'ARMember');?></label>
												<input type="number" id="arm_edit_drip_type_post_modify_add" name="rule_options[post_modify]" min="0" value="10" data-msg-required="<?php esc_attr_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
												<input type="hidden" name="rule_options[post_modify_duration]" value="day" id="arm_drip_type_dmy">
												<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
													<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
													<dd>
														<ul data-id="arm_drip_type_dmy">
															<li data-label="<?php esc_attr_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
															<li data-label="<?php esc_attr_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
															<li data-label="<?php esc_attr_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
														</ul>
													</dd>
												</dl>
												<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
												<input type="hidden" name="rule_options[post_modify_duration_time]" id="arm_drip_type_time" value="00:00">
												<dl class="arm_selectbox column_level_dd arm_drip_type_time">
													<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
													<dd>
														<ul data-id="arm_drip_type_time">
															<?php for($i=0; $i<24 ; $i++)
															{?>
																<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
															<?php }?>
														</ul>
													</dd>
												</dl>
											</div>
											<div class="arm_drip_type_options_container">
												<div class="armswitch arm_display_flex arm_margin_bottom_20 ">
												<label for="arm_drip_type_expire_post_modify piration_days" class="arm_margin_right_25"><?php esc_html_e('Expiration','ARMember')?></label>
												<input type="checkbox" class="armswitch_input arm_private_content_status_action arm_drip_type_expire_post_modify" id="arm_drip_type_expire_post_modify" name="rule_options[rule_expire_post_modify]" value="1" data-item_id="2">
												<label class="armswitch_label arm_margin_left_0" for="arm_drip_type_expire_post_modify"></label>	
												<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_html("When enable the expiration for dripped content then allowed access will be restricted as per the expiration settings. Expiration of the dripped content will be calculated time period after the content is dripped to the member.", 'ARMember');?>"></i>
												</div>


												<div class="arm_drip_expire_post_modify hidden_section">
													<label class="arm_hide_after_drip arm_font_size_15 arm_margin_right_16"><?php esc_html_e('Hide After', 'ARMember');?></label>
													<input type="number" id="arm_edit_drip_type_post_modify_add" name="rule_options[exp_post_modify]" min="0" value="10" data-msg-required="<?php esc_attr_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
													<input type="hidden" name="rule_options[post_modify_exp_duration]" value="day" id="arm_drip_exp_dmy">
													<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
														<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
														<dd>
															<ul data-id="arm_drip_exp_dmy">
																<li data-label="<?php esc_attr_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
																<li data-label="<?php esc_attr_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
																<li data-label="<?php esc_attr_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
															</ul>
														</dd>
													</dl>
													<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
													<input type="hidden" name="rule_options[post_modify_exp_duration_time]" id="arm_drip_exp_time" value="00:00">
													<dl class="arm_selectbox column_level_dd arm_drip_type_time">
														<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
														<dd>
															<ul data-id="arm_drip_exp_time">
																<?php for($i=0; $i<24 ; $i++)
																{?>
																	<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
																<?php }?>
															</ul>
														</dd>
													</dl>
													
												</div>
											</div>
										</div>
										<div class="arm_drip_type_options_wrapper arm_drip_type_options_dates hidden_section arm_required_wrapper" id="arm_drip_type_options_dates">
											<div class="arm_drip_type_options_container">
												<label class="arm_font_size_16"><?php esc_html_e('From Date', 'ARMember');?></label>
												<input type="text" id="arm_drip_type_date_from" class="arm_datepicker arm_margin_left_0 arm_margin_top_12" name="rule_options[from_date]" value="<?php echo date('m/d/Y'); //phpcs:ignore?>" data-default_value="<?php echo date('m/d/Y'); //phpcs:ignore?>" data-msg-required="<?php esc_attr_e('Please select from date.', 'ARMember');?>"/>
											</div>
											<div class="arm_drip_type_options_container">
												<label class="arm_font_size_16 arm_d_flex"><?php esc_html_e('To Date', 'ARMember');?>&nbsp;<span class="arm_font_size_14">(<?php esc_html_e('optional', 'ARMember');?>)</span></label>
												<input type="text" id="arm_drip_type_date_to" class="arm_datepicker arm_margin_left_0 arm_margin_top_12 arm_margin_bottom_10" name="rule_options[to_date]" value=""/>
												<span class="arm_drip_never_exp_label">(<?php esc_html_e('Leave blank for never expiring', 'ARMember');?>)</span>
											</div>
										</div>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
			<div class="arm_submit_btn_container">
				<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_loader_img_add_drip_rule" class="arm_loader_img arm_submit_btn_loader"  style="top: 5px; float: <?php echo (is_rtl()) ? 'right' : '';?>; display: none;" width="20" height="20" />
				<button class="arm_cancel_btn add_new_drip_rule_close_btn" type="button"><?php esc_html_e('Cancel','ARMember');?></button>
				<button class="arm_save_btn arm_new_drip_rule_button" type="submit" data-type="add"><?php esc_html_e('Save', 'ARMember') ?></button>
			</div>
			<div class="armclear"></div>
		</form>
	</div>
</div>
<!--./******************** Edit Drip Rule Form ********************/.-->
<div class="arm_edit_drip_rule_wrapper popup_wrapper" >
	<div class="content_wrapper arm_add_edit_drip_rule_content" id="content_wrapper">
		<div class="popup_header page_title">
			<span class="arm_add_new_drip_rule_title hidden_section"><?php esc_html_e('Add New Drip Rule','ARMember');?></span>
			<span class="arm_edit_drip_rule_title "><?php esc_html_e('Edit Drip Rule','ARMember');?></span>
			<span class="arm_popup_close_btn edit_drip_rule_close_btn"></span>
		</div>
		<div class="armclear"></div>
		<form method="post" action="#" id="arm_edit_drip_rule_wrapper_frm" class="arm_admin_form arm_edit_drip_rule_wrapper_frm">
			<input type="hidden" name="rule_id" id="arm_edit_drip_rule_id" value=""/>
			<div class="arm_admin_form_content">	
				<div class="arm_admin_form_content_inner">
					<div class="arm_form_main_content arm_padding_40">
						<div class="arm_setup_section_title arm_margin_bottom_24"> <?php esc_html_e('Drip Content Rules','ARMember'); ?></div>
				<table class="form-table arm_content_type_table ">
					<tbody>	
					<tr class="form-field form-required arm_auto_user_field arm_width_auto">
									<td class="arm_padding_0  arm_height_auto arm_font_size_16">
							<div id="arm_edit_rule_item_type" class="arm_line_height_24 arm_padding_bottom_22" ></div>
							</td>
						</tr>
					</tbody>
				</table>
			
						<table class="form-table arm_content_type_table">
							<tbody class="arm_margin_0 arm_padding_0">
								<tr class="form-field form-required arm_auto_user_field arm_width_100_pct">
							<th class="arm_padding_0 arm_font_size_16">
								<label><?php esc_html_e('Select Membership plans', 'ARMember');?></label>
							</th>
							<td class="arm_padding_left_0 arm_height_auto arm_padding_top_12 arm_padding_bottom_22">
							<select id="arm_edit_drip_rule_plans" class="arm_chosen_selectbox arm_width_500" data-msg-required="<?php esc_attr_e('Please select atleast one plan.', 'ARMember');?>" name="rule_plans[]" data-placeholder="<?php esc_attr_e('Select Plan(s)..', 'ARMember');?>" multiple="multiple" >
									<?php if (!empty($all_plans)):?>
										<?php foreach ($all_plans as $plan): ?>
											<option class="arm_message_selectbox_op" value="<?php echo esc_attr($plan['arm_subscription_plan_id']);?>"><?php echo stripslashes( esc_html($plan['arm_subscription_plan_name'])); //phpcs:ignore?></option>
										<?php endforeach;?>
									<?php else: ?>
										<option value="">No Subscription Plans Available</option>
									<?php endif;?>
								</select>
							</td>
						</tr>
					</tbody>
					</table>
						<table class="form-table arm_content_type_table">
						<tbody class="arm_margin_0">
								<tr class="form-field form-required arm_auto_user_field arm_width_100_pct">
							<th class="arm_padding_0 arm_font_size_16">
								<label><?php esc_html_e('Select Drip Type', 'ARMember');?></label>
							</th>
									<td class="arm_height_auto arm_padding_left_0 arm_padding_top_12 arm_padding_bottom_0">
							<input type="hidden" class="arm_drip_type_input arm_padding_left_0" id="arm_edit_drip_type" name="rule_type" value="immediately"/>
								<dl class="arm_selectbox column_level_dd arm_width_100_pct">
									<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
									<dd>
										<ul data-id="arm_edit_drip_type">
											<?php foreach($drip_types as $key => $val):?>
											<li data-label="<?php echo esc_attr($val);?>" data-value="<?php echo esc_attr($key);?>"><?php echo esc_attr($val);?></li>
											<?php endforeach;?>
										</ul>
									</dd>
								</dl>
								<div class="armclear"></div>
								<div class="arm_drip_type_options_wrapper arm_edit_drip_wrapper_immediate hidden_section" id="arm_edit_drip_type_options_instant">
					
									<div class="armswitch arm_display_flex arm_margin_bottom_20">
											<label for="arm_edit_drip_expiration_immediate piration_days" class="arm_margin_right_25"><?php esc_html_e('Expiration','ARMember')?></label>
											<input type="checkbox" class="armswitch_input arm_private_content_status_action arm_drip_expiration_drip_type_immediate" id="arm_edit_drip_expiration_immediate" name="rule_options[rule_expire_immediate]" value="1" data-item_id="2">
											<label class="armswitch_label arm_margin_left_0" for="arm_edit_drip_expiration_immediate"></label>	
											<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_html("When enable the expiration for dripped content then allowed access will be restricted as per the expiration settings. Expiration of the dripped content will be calculated time period after the content is dripped to the member.", 'ARMember');?>"></i>
											</div>

									<div class="arm_edit_drip_expire_after_immediate hidden_section">
										<label class="arm_hide_after_drip arm_font_size_15 arm_margin_right_16"><?php esc_html_e('Hide After', 'ARMember');?></label>
										<input type="number" id="arm_drip_edit_type_exp_imm" name="rule_options[expire_immediate_days]" min="0" value="10" data-msg-required="<?php esc_attr_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
										<input type="hidden" name="rule_options[expire_immediate_duration]" id="arm_drip_type_exp_dmy_imm" value="day">
										<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
											<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
											<dd>
												<ul data-id="arm_drip_type_exp_dmy_imm">
													<li data-label="<?php esc_attr_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
													<li data-label="<?php esc_attr_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
													<li data-label="<?php esc_attr_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
												</ul>
											</dd>
										</dl>
										<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
										<input type="hidden" name="rule_options[expire_duration_immediate_time]" id="arm_drip_type_exp_time_imm" value="00:00">
										<dl class="arm_selectbox column_level_dd arm_drip_type_time">
											<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
											<dd>
												<ul data-id="arm_drip_type_exp_time_imm">
													<?php for($i=0; $i<24 ; $i++)
													{?>
														<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
													<?php }?>
												</ul>
											</dd>
										</dl>
									</div>
								</div>
								<div class="arm_drip_type_options_wrapper arm_edit_drip_type_options_days hidden_section arm_required_wrapper" id="arm_edit_drip_type_options_days">
									<div class="arm_drip_type_options_container ">
										<label class="arm_font_size_15 arm_margin_right_14"><?php esc_html_e('Show After', 'ARMember');?></label>
										<input type="number" id="arm_edit_drip_type_days" name="rule_options[days]" min="0" value="10" data-msg-required="<?php esc_html_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
										<input type="hidden" name="rule_options[duration]" id="arm_edit_drip_type_duration" value="day">
										<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
											<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
											<dd>
												<ul data-id="arm_edit_drip_type_duration">
													<li data-label="<?php esc_html_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
													<li data-label="<?php esc_html_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
													<li data-label="<?php esc_html_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
												</ul>
											</dd>
										</dl>
										<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
										<input type="hidden" name="rule_options[duration_time]" id="arm_edit_drip_type_duration_time" value="00:00">
										<dl class="arm_selectbox column_level_dd arm_drip_type_time">
											<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
											<dd>
												<ul data-id="arm_edit_drip_type_duration_time">
													<?php for($i=0; $i<24 ; $i++)
													{?>
														<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
													<?php }?>
												</ul>
											</dd>
										</dl>
									</div>
									
											<div class="armswitch arm_display_flex">
										<label for="arm_edit_drip_expiration_drip_type_days piration_days" class="arm_margin_right_25"><?php esc_html_e('Expiration','ARMember')?></label>
										<input type="checkbox" class="armswitch_input arm_private_content_status_action arm_drip_expiration_drip_type_immediate" id="arm_drip_edit_expiration_days" name="rule_options[rule_expire_days]" value="1" data-item_id="2">
										<label class="armswitch_label arm_margin_left_0" for="arm_drip_edit_expiration_days"></label>	
										<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_html("When enable the expiration for dripped content then allowed access will be restricted as per the expiration settings. Expiration of the dripped content will be calculated time period after the content is dripped to the member.", 'ARMember');?>"></i>
									</div>

											<div class="arm_edit_drip_expire_after_days hidden_section arm_margin_top_20">
											<label class="arm_hide_after_drip arm_font_size_15 arm_margin_right_16"><?php esc_html_e('Hide After', 'ARMember');?></label>
											<input type="number" id="arm_edit_drip_exp_days" name="rule_options[expire_days]" min="0" value="10" data-msg-required="<?php esc_html_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
											<input type="hidden" name="rule_options[expire_duration]" id="arm_edit_drip_exp_duration" value="day">
											<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
												<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
												<dd>
													<ul data-id="arm_edit_drip_exp_duration">
														<li data-label="<?php esc_html_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
														<li data-label="<?php esc_html_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
														<li data-label="<?php esc_html_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
													</ul>
												</dd>
											</dl>
												<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
											<input type="hidden" name="rule_options[expire_duration_time]" id="arm_edit_drip_exp_duration_time" value="00:00">
											<dl class="arm_selectbox column_level_dd arm_drip_type_time">
												<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
												<dd>
													<ul data-id="arm_edit_drip_exp_duration_time">
														<?php for($i=0; $i<24 ; $i++)
														{?>
															<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
														<?php }?>
													</ul>
												</dd>
											</dl>
										</div>
									</div>
								<div class="arm_drip_type_options_wrapper arm_edit_drip_type_options_post_publish hidden_section arm_required_wrapper" id="arm_edit_drip_type_options_post_publish">
									<div class="arm_drip_type_options_container">
										<label class="arm_font_size_15 arm_margin_right_14"><?php esc_html_e('Show After', 'ARMember');?></label>
										<input type="number" id="arm_edit_drip_type_post_publish" name="rule_options[post_publish]" min="0" value="10" data-msg-required="<?php esc_html_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
										<input type="hidden" name="rule_options[post_publish_duration]" id="arm_edit_drip_type_post_publish_duration" value="day">
										<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
											<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
											<dd>
												<ul data-id="arm_edit_drip_type_post_publish_duration">
													<li data-label="<?php esc_html_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
													<li data-label="<?php esc_html_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
													<li data-label="<?php esc_html_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
												</ul>
											</dd>
										</dl>
											<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
										<input type="hidden" name="rule_options[post_publish_duration_time]" id="arm_edit_drip_type_post_publish_duration_time" value="00:00">
										<dl class="arm_selectbox column_level_dd arm_drip_type_time">
											<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
											<dd>
												<ul data-id="arm_edit_drip_type_post_publish_duration_time">
													<?php for($i=0; $i<24 ; $i++)
													{?>
														<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore ?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
													<?php }?>
												</ul>
											</dd>
										</dl>
									</div>
									
									<div class="arm_drip_type_options_container">
										<div class="armswitch arm_display_flex arm_margin_bottom_20" bis_skin_checked="1">
											<label for="arm_drip_edit_expire_post_publish piration_days" class="arm_margin_right_25">Expiration</label>
											<input type="checkbox" class="armswitch_input arm_private_content_status_action arm_edit_drip_type_expire_post_publish" id="arm_drip_edit_expire_post_publish" name="rule_options[rule_expire_post_publish]" value="1">

											<label class="armswitch_label arm_margin_left_0" for="arm_drip_edit_expire_post_publish"></label>
											<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_html("When enable the expiration for dripped content then allowed access will be restricted as per the expiration settings. Expiration of the dripped content will be calculated time period after the content is dripped to the member.", 'ARMember');?>"></i>
										</div>
									
										
										<div class="arm_edit_drip_expire_post_publish hidden_section">
											<label class="arm_hide_after_drip arm_font_size_15 arm_margin_right_16"><?php esc_html_e('Hide After', 'ARMember');?></label>
											<input type="number" id="arm_edit_drip_exp_post_publish" name="rule_options[exp_post_publish]" min="0" value="10" data-msg-required="<?php esc_html_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
											<input type="hidden" name="rule_options[post_publish_exp_duration]" value="day" id="arm_edit_drip_exp_post_publish_dmy">
											<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
												<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
												<dd>
													<ul data-id="arm_edit_drip_exp_post_publish_dmy">
														<li data-label="<?php esc_html_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
														<li data-label="<?php esc_html_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
														<li data-label="<?php esc_html_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
													</ul>
												</dd>
											</dl>
											<label class="arm_margin_left_12 arm_margin_right_10" ><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
											<input type="hidden" name="rule_options[post_publish_exp_duration_time]" id="arm_edit_drip_exp_post_publish_time" value="00:00">
											<dl class="arm_selectbox column_level_dd arm_drip_type_time">
												<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
												<dd>
													<ul data-id="arm_edit_drip_exp_post_publish_time">
														<?php for($i=0; $i<24 ; $i++)
														{?>
															<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
														<?php }?>
													</ul>
												</dd>
											</dl>
											
										</div>
									</div>
								</div>

								<div class="arm_drip_type_options_wrapper arm_edit_drip_type_options_post_modify hidden_section arm_required_wrapper" id="arm_edit_drip_type_options_post_modify">
									<div class="arm_drip_type_options_container">
										<label class="arm_font_size_15 arm_margin_right_14"><?php esc_html_e('Show After', 'ARMember');?></label>
										<input type="number" id="arm_edit_drip_type_post_modify" name="rule_options[post_modify]" min="0" value="10" data-msg-required="<?php esc_html_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
										<input type="hidden" name="rule_options[post_modify_duration]" id="arm_edit_drip_type_post_modify_duration" value="day">
										<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
											<dt><span class="arm_no_auto_complete"></span></i></dt>
											<dd>
												<ul data-id="arm_edit_drip_type_post_modify_duration">
													<li data-label="<?php esc_html_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
													<li data-label="<?php esc_html_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
													<li data-label="<?php esc_html_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
												</ul>
											</dd>
										</dl>
											<label class="arm_margin_left_12 arm_margin_right_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
										<input type="hidden" name="rule_options[post_modify_duration_time]" id="arm_edit_drip_type_post_modify_duration_time" value="00:00">
										<dl class="arm_selectbox column_level_dd arm_drip_type_time">
											<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
											<dd>
												<ul data-id="arm_edit_drip_type_post_modify_duration_time">
													<?php for($i=0; $i<24 ; $i++)
													{?>
														<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
													<?php }?>
												</ul>
											</dd>
										</dl>
									</div>
									
									<div class="arm_drip_type_options_container">
										<div class="armswitch arm_display_flex arm_margin_bottom_20 " bis_skin_checked="1">
											<label for="arm_drip_edit_expire_post_modify piration_days" class="arm_margin_right_25"><?php esc_html_e('Expiration','ARMember');?></label>
											<input type="checkbox" class="armswitch_input arm_private_content_status_action arm_edit_drip_type_expire_post_modify" id="arm_drip_edit_expire_post_modify" name="rule_options[rule_expire_post_modify]" value="1" data-item_id="2">
											<label class="armswitch_label arm_margin_left_0" for="arm_drip_edit_expire_post_modify"></label>	
											<i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_html("When enable the expiration for dripped content then allowed access will be restricted as per the expiration settings. Expiration of the dripped content will be calculated time period after the content is dripped to the member.", 'ARMember');?>"></i>
										</div>
									
										<div class="arm_edit_drip_expire_post_modify hidden_section">
											<label class="arm_hide_after_drip arm_font_size_15 arm_margin_right_16"><?php esc_html_e('Hide After', 'ARMember');?></label>
											<input type="number" id="arm_edit_drip_exp_post_modify" name="rule_options[exp_post_modify]" min="0" value="10" data-msg-required="<?php esc_html_e('Please enter days.', 'ARMember');?>" onkeydown="javascript:return checkNumber(event)"/>
											<input type="hidden" name="rule_options[post_modify_exp_duration]" value="day" id="arm_edit_drip_exp_post_modify_dmy">
											<dl class="arm_selectbox column_level_dd arm_drip_duration_type">
												<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
												<dd>
													<ul data-id="arm_edit_drip_exp_post_modify_dmy">
														<li data-label="<?php esc_html_e('Day(s)','ARMember');?>" data-value="day"><?php esc_html_e('Day(s)','ARMember'); ?></li>
														<li data-label="<?php esc_html_e('Month(s)','ARMember');?>" data-value="month"><?php esc_html_e('Month(s)','ARMember'); ?></li>
														<li data-label="<?php esc_html_e('Year(s)','ARMember');?>" data-value="year"><?php esc_html_e('Year(s)','ARMember'); ?></li>
													</ul>
												</dd>
											</dl>
											<label class="arm_margin_left_12 arm_margin_left_10"><?php esc_html_e('at', 'ARMember');?>&nbsp;</label>
											<input type="hidden" name="rule_options[post_modify_exp_duration_time]" id="arm_edit_drip_exp_post_modify_time" value="00:00">
											<dl class="arm_selectbox column_level_dd arm_drip_type_time">
												<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
												<dd>
													<ul data-id="arm_edit_drip_exp_post_modify_time">
														<?php for($i=0; $i<24 ; $i++)
														{?>
															<li data-label="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>" data-value="<?php echo sprintf("%02d", $i).":00" //phpcs:ignore?>"><?php echo sprintf("%02d", $i).":00" //phpcs:ignore?></li>
														<?php }?>
													</ul>
												</dd>
											</dl>
											
										</div>
									</div>
								</div>



								<div class="arm_drip_type_options_wrapper arm_edit_drip_type_options_dates hidden_section arm_required_wrapper" id="arm_edit_drip_type_options_dates">
									<div class="arm_drip_type_options_container">
										<label class="arm_font_size_16"><?php esc_html_e('From Date', 'ARMember');?></label>
										<input type="text" id="arm_edit_drip_type_date_from" class="arm_datepicker arm_margin_left_0 arm_margin_top_12" name="rule_options[from_date]" value="<?php echo date('Y-m-d'); //phpcs:ignore?>" data-msg-required="<?php esc_html_e('Please select from date.', 'ARMember');?>"/>
									</div>
									<div class="arm_drip_type_options_container">
										<label class="arm_font_size_16 arm_d_flex"><?php esc_html_e('To Date', 'ARMember');?><span class="arm_font_size_14">(<?php esc_html_e('optional', 'ARMember');?>)</span></label>
										<input type="text" id="arm_edit_drip_type_date_to" class="arm_datepicker arm_margin_left_0 arm_margin_top_12 arm_margin_bottom_10" name="rule_options[to_date]" value=""/>
										<span class="arm_drip_never_exp_label">(<?php esc_html_e('Leave blank for never expiring', 'ARMember');?>)</span>
									</div>
								</div>
							</td>
						</tr>
						</tbody>
					</table>
					</div>
				</div>
			</div>
			<div class=" arm_submit_btn_container popup_content_btn_wrapper">
				<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_loader_img_edit_drip_rule" class="arm_loader_img arm_submit_btn_loader" style="top: 5px;float: <?php echo (is_rtl()) ? 'right' : '';?>; display: none;" width="20" height="20" />
				<button class="arm_cancel_btn edit_drip_rule_close_btn" type="button"><?php esc_html_e('Cancel','ARMember');?></button>
				<button class="arm_save_btn arm_update_drip_rule_button arm_margin_right_8_pct" type="submit" data-type="add"><?php esc_html_e('Save', 'ARMember') ?></button>
			</div>

			<div class="armclear"></div>															

		</form>
	</div>
</div>
<?php require_once(MEMBERSHIPLITE_VIEWS_DIR.'/arm_view_member_details.php')?>
<!--./******************** Drip Rule Members List ********************/.-->
<div class="arm_members_list_detail_popup popup_wrapper arm_members_list_detail_popup_wrapper" >
	<div class="arm_loading_grid" id="arm_loading_grid_members" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>
    <div class="popup_wrapper_inner" style="overflow: hidden;">
        <div class="popup_header page_title">
            <span class="popup_close_btn arm_popup_close_btn arm_members_list_detail_close_btn"></span>
            <span class="add_rule_content"><?php esc_html_e('Members Details', 'ARMember'); ?></span>
        </div>
        <div class="popup_content_text arm_members_list_detail_popup_text">
            <table width="100%" cellspacing="0" class="display arm_min_width_802 arm_no_margin" id="armember_datatable_1" >
                <thead>
                    <tr>
                        <th><?php esc_html_e('Username', 'ARMember'); ?></th>
                        <th><?php esc_html_e('Email', 'ARMember'); ?></th>
                        <th><?php esc_html_e('Days Of Subscription', 'ARMember'); ?></th>
                        <th class="arm-no-sort arm_width_170" ><?php esc_html_e('View Detail', 'ARMember'); ?></th>
                    </tr>
                </thead>
            </table>
            <input type="hidden" name="search_grid" id="search_grid" value="<?php esc_html_e('Search','ARMember');?>"/>
            <input type="hidden" name="entries_grid" id="entries_grid" value="<?php esc_html_e('members','ARMember');?>"/>
            <input type="hidden" name="show_grid" id="show_grid" value="<?php esc_html_e('Show','ARMember');?>"/>
            <input type="hidden" name="showing_grid" id="showing_grid" value="<?php esc_html_e('Showing','ARMember');?>"/>
            <input type="hidden" name="to_grid" id="to_grid" value="<?php esc_html_e('to','ARMember');?>"/>
            <input type="hidden" name="of_grid" id="of_grid" value="<?php esc_html_e('of','ARMember');?>"/>
            <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php esc_html_e('No matching members found','ARMember');?>"/>
            <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php esc_html_e('There is no any member found.','ARMember');?>"/>
            <input type="hidden" name="filter_grid" id="filter_grid" value="<?php esc_html_e('filtered from','ARMember');?>"/>
            <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php esc_html_e('total','ARMember');?>"/>
            <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
			<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
        </div>
        <div class="armclear"></div>
    </div>
</div>
<style type="text/css" title="currentStyle">
	.ColVis_Button:not(.manage_drip_filter_btn), .paginate_page a{display:none;}
	.wrap table.dataTable thead tr th, .wrap table.dataTable thead tr td,
	.wrap #armember_datatable_wrapper tr td{width: auto;}
</style>
<script type="text/javascript" charset="utf-8">

// <![CDATA[
jQuery(window).on("load", function () {
	document.onkeypress = stopEnterKey;
});
jQuery(document).ready( function ($) {
	jQuery(document).on('click', '.arm_remove_selected_itembox', function () {
		jQuery(this).parents('.arm_drip_rule_itembox').remove();
		if(jQuery('#arm_drip_rule_items .arm_drip_rule_itembox').length == 0) {
			jQuery('#arm_drip_rule_items_input').attr('required', 'required');
			jQuery('#arm_drip_rule_items').hide();
		}
		return false;
	});
	if (jQuery.isFunction(jQuery().autocomplete))
	{
		jQuery('#arm_drip_rule_items_input').autocomplete({
			minLength: 0,
			delay: 500,
			appendTo: "#arm_drip_rule_items_list_container",
			source: function (request, response) {
				var post_type = jQuery('#arm_add_rule_item_type').val();
				var _wpnonce = jQuery('input[name="arm_wp_nonce"]').val();
				jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					dataType: 'json',
					data: "action=arm_get_drip_rule_item_options&arm_post_type=" + post_type + "&search_key="+request.term + "&_wpnonce=" + _wpnonce,
					beforeSend: function () {},
					success: function (res) {
						response(res.data);
					}
				});
			},
			focus: function() {return false;},
			select: function(event, ui) {
				var itemData = ui.item;
				jQuery("#arm_drip_rule_items_input").val('');
				if(jQuery('#arm_drip_rule_items .arm_drip_rule_itembox_'+itemData.id).length > 0) {
				} else {
					var itemHtml = '<div class="arm_drip_rule_itembox arm_drip_rule_itembox_'+itemData.id+'">';
					itemHtml += '<input type="hidden" name="item_id['+itemData.id+']" value="'+itemData.id+'"/>';
					itemHtml += '<label>'+itemData.label+'<span class="arm_remove_selected_itembox">x</span></label>';
					itemHtml += '</div>';
					jQuery("#arm_drip_rule_items").append(itemHtml);
					jQuery('#arm_drip_rule_items_input').removeAttr('required');
				}
				jQuery('#arm_drip_rule_items').show();
				return false;
			},
		}).data('uiAutocomplete')._renderItem = function (ul, item) {
			var itemClass = 'ui-menu-item';
			if(jQuery('#arm_drip_rule_items .arm_drip_rule_itembox_'+item.id).length > 0) {
				itemClass += ' ui-menu-item-selected';
			}
			var itemHtml = '<li class="'+itemClass+'" data-value="'+item.value+'" data-id="'+item.id+'" ><a>' + item.label + '</a></li>';
			return jQuery(itemHtml).appendTo(ul);
		};
	}
});


// ]]>
</script>
<?php
	echo $ARMember->arm_get_need_help_html_content('manage-drip-rules'); //phpcs:ignore
?>