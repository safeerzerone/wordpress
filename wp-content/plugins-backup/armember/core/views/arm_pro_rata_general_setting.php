<?php

global $arm_global_settings,$arm_member_forms,$wp,$wpdb,$ARMember;
$all_global_settings = $arm_global_settings->arm_get_all_global_settings();
$is_permalink = $arm_global_settings->is_permalink();
$general_settings = $all_global_settings['general_settings'];
$arm_pro_ration_feature = get_option('arm_is_pro_ration_feature', 0);
    if($arm_pro_ration_feature == 1) {
        $arm_enable_reset_billing = isset($general_settings['arm_enable_reset_billing']) ? $general_settings['arm_enable_reset_billing'] : 0;
        $is_reset_billing_enabled = ($arm_enable_reset_billing == '1') ? 'checked' : '';
        ?>

<div id="pro_rata_configuration_sec"  class="arm_settings_section arm_margin_bottom_32" style="display:none;">
        <div class="page_sub_title" id="ARMProRataConfig"><?php esc_html_e('Pro-Rata Configuration', 'ARMember')?></div>
        <div class="arm_setting_main_content arm_padding_0 arm_margin_top_32 arm_margin_bottom_32">
				<div class="arm_row_wrapper arm_row_wrapper_padding_before">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Pro-Rata Configuration', 'ARMember')?></div>
					</div>
				</div>
				<div class="arm_content_border"></div>
				
					<div class="arm_row_wrapper arm_row_wrapper_padding_after arm_padding_top_24 arm_display_block">
                        <div class="arm_email_setting_flex_group arm_payment_getway_page">

                            <div class="arm_form_field_block">
                                <div class="arm-form-table-label arm_margin_bottom_12"><?php esc_html_e('Pro-Rata Method', 'ARMember')?>
                                <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo sprintf( addslashes(esc_html__('Pro-Rata Cost-based calculation is a type of pseudo-pro-rata where the value of an upgrade or downgrade is calculated based on the cost difference between the current and new mebership plan and Time-based Pro-Rata calculation is the calculated amount of the remaining duration for the current mebership plan which will adjust the cost of the new mebership plan. For more information, please refer %s documentation %s', 'ARMember')),"<a target='_blank' href='https://www.armemberplugin.com/documents/pro-rata/'>", "</a>" ); //phpcs:ignore ?>"></i>
                                </div>
                                <div class="arm-form-table-content"><?php 
                                    $arm_pro_ration_method = isset($general_settings['arm_pro_ration_method']) ? $general_settings['arm_pro_ration_method'] : 'cost_base';?>
                                    <input type="hidden" id="arm_pro_ration_method" name="arm_general_settings[arm_pro_ration_method]" value="<?php echo $arm_pro_ration_method ?>" />
                                    <dl class="arm_selectbox column_level_dd arm_width_100_pct">
                                            <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                            <dd>
                                                <ul class="arm_pro_ration_method" data-id="arm_pro_ration_method">
                                                    <li data-label="<?php esc_html_e('Cost-Based Calculation', 'ARMember')?>" data-value="cost_base"><?php esc_html_e('Cost-Based Calculation', 'ARMember')?></li>
                                                    <li data-label="<?php esc_html_e('Time-Based Calculation', 'ARMember') ?>" data-value="time_base"><?php esc_html_e('Time-Based Calculation', 'ARMember') ?></li>
                                                </ul>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="form-field arm_margin_top_24">
                            <div class="arm-form-table-label"><?php esc_html_e('Reset Billing Period','ARMember') ?></div>
                            <div class="arm_global_settings_sub_content arm_row_wrapper arm_margin_top_12">
                                <label for="arm_enable_reset_billing" class="arm_global_setting_switch_label"><?php esc_html_e('Enable reset billing period on plan purchase with Pro-Ration','ARMember') ?>
                                <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php esc_html_e('When this setting is enabled then subscription membership plan cycle will be reset and starts from the date of purchasing new membership plan.', 'ARMember')?>"></i> </label>
                                <div class="armswitch arm_global_setting_switch arm_margin_0">
                                    <input type="checkbox" id="arm_enable_reset_billing" <?php echo $is_reset_billing_enabled ?> value="1" class="armswitch_input" name="arm_general_settings[arm_enable_reset_billing]"/>
                                    <label for="arm_enable_reset_billing" class="armswitch_label"></label>
                                </div>
                            </div>
                        </div>                              
					</div>
				
			</div>
         
    </div>
    <?php
    }
    ?>