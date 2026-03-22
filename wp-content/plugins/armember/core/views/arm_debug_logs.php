<?php
$arm_debug_log_html  = '';
global $wpdb, $ARMember, $arm_global_settings, $arm_payment_gateways,$arm_email_settings;
if($section == 'opt-ins' && $arm_email_settings->isOptInsFeature){

	$payment_gateways = $arm_payment_gateways->arm_get_all_payment_gateways_for_setup();
	$arm_common_date_format = $arm_global_settings->arm_get_wp_date_format();
	$arm_default_date = date_i18n($arm_common_date_format);

$arm_is_optins_log_enabled = get_option('arm_optins_debug_log');
$arm_optins_debug_log = ($arm_is_optins_log_enabled) ? 'checked=checked' : '';
$arm_is_optins_log_enabled_css =(empty($arm_is_optins_log_enabled)) ? "display:none;" :'';
$arm_debug_log_html = '
<div class="page_sub_title arm_font_size_18 arm_font_weight_500 arm_margin_top_48 arm_margin_bottom_24">'. esc_html__('Opt-ins Debug Log Settings', 'ARMember').'</div>
<div class="armclear"></div>
<table class="form-table">
    <div class="arm_setting_main_content arm_setting_main_content">
    <div class="arm_row_wrapper">
            <div class="left_content">
                        <div class="arm-form-table-label">'. esc_html__('Enable Opt-ins Debug Logs', 'ARMember').'</div>
            </div>
            <div class="right_content">		
                <div class="armswitch arm_payment_setting_switch arm_margin_0">
                    <input type="checkbox" id="arm_optins_debug_log" '. esc_attr($arm_optins_debug_log) .'
                        value="1" class="armswitch_input arm_debug_mode_switch" name="arm_optins_debug_log"
                        data-switch_key="optins" />
                    <label for="arm_optins_debug_log" class="armswitch_label"></label>
                </div>
            </div>
        </div>
        <div class="arm-form-table-content">
            
            <div class="arm_debug_switch_optins arm_debug_log_action_container" style="'.$arm_is_optins_log_enabled_css.'">
            <div class="arm_margin_top_24 arm_display_flex">
                <a href="javascript:void(0)"
                 class="arm_display_flex arm_debug_log"  onclick="arm_view_general_debug_logs(\'optins\', \'All\')"> <img src="'. MEMBERSHIPLITE_IMAGES_URL.'/view_log_icon.png" alt="View Log Icon" class="arm-debug-log-icon">'. esc_html__('View Log', 'ARMember').'</a>
    
                <a href="javascript:void(0)" onclick="arm_download_general_debug_logs(\'optins\')"
                    class="arm_margin_left_32 arm_display_flex arm_debug_log"> <img src="'. MEMBERSHIPLITE_IMAGES_URL.'/download_log_icon.png" alt="View Log Icon" class="arm-debug-log-icon">'.esc_html__('Download Log', 'ARMember').'</a>
                <div class="arm_confirm_box arm_general_debug_download_confirm_box"
                    id="arm_general_debug_download_confirm_box_optins">
                    <div class="arm_confirm_box_body">
                        <div class="arm_confirm_box_arrow"></div>
                        <div class="arm_confirm_box_text">
                            <div class="arm_download_duration_selection">
                                <label
                                    class="arm_select_duration_label">'. esc_html__('Select log duration to download', 'ARMember').'</label>
                                <input type="hidden" id="arm_general_download_duration" name="action1" value="7" />
                                <dl class="arm_selectbox column_level_dd arm_margin_top_12 arm_width_100_pct">
                                    <dt>
                                        <span>'.esc_html__('Last 1 Week','ARMember').'</span>
                                        <input type="text" style="display:none;" value="" class="arm_autocomplete" /><i
                                            class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_general_download_duration">
                                            <li data-label="'. esc_attr__('Last 1 Day', 'ARMember').'"
                                                data-value="1">'.esc_html__('Last 1 Day', 'ARMember').'
                                            </li>

                                            <li data-label="'. esc_attr__('Last 3 Days', 'ARMember').'"
                                                data-value="3">'. esc_attr__('Last 3 Days', 'ARMember').'
                                            </li>

                                            <li data-label="'. esc_attr__('Last 1 Week','ARMember').'"
                                                data-value="7">'. esc_attr__('Last 1 Week','ARMember').'
                                            </li>

                                            <li data-label="'. esc_attr__('Last 2 Weeks', 'ARMember').'"
                                                data-value="15">'. esc_attr__('Last 2 Weeks', 'ARMember').'</li>

                                            <li data-label="'. esc_attr__('Last Month', 'ARMember').'"
                                                data-value="30">'. esc_attr__('Last Month', 'ARMember').'
                                            </li>

                                            <li data-label="'. esc_attr__('All', 'ARMember').'"
                                                data-value="all">'. esc_attr__('All', 'ARMember').'</li>

                                            <li data-label="'. esc_attr__('Custom', 'ARMember').'"
                                                data-value="custom">'. esc_attr__('Custom', 'ARMember').'
                                            </li>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                            <form id="arm_general_debug_download_custom_duration_optins_form">
                                <div class="arm_download_custom_duration_div arm_margin_top_22">
                                    <div class="arm_datatable_filter_item arm_margin_left_0">
                                        <input type="text" name="arm_filter_pstart_date" id="arm_filter_pstart_date"
                                            class="arm_download_custom_duration_date"
                                            placeholder="'. esc_attr__('Start Date', 'ARMember').'"
                                            data-date_format="'. esc_attr($arm_common_date_format).'"
                                            value="'. esc_attr($arm_default_date).'" />
                                    </div>
                                    <div class="arm_datatable_filter_item">
                                        <input type="text" name="arm_filter_pend_date" id="arm_filter_pend_date"
                                            class="arm_download_custom_duration_date"
                                            placeholder="'. esc_attr__('End Date', 'ARMember').'"
                                            data-date_format="'. esc_attr($arm_common_date_format).'"
                                            value="'. esc_attr($arm_default_date).'" />
                                    </div>
                                </div>
                            </form>
                            <button type="button"
                                class="arm_confirm_box_btn armemailaddbtn arm_download_general_debug_log_btn"
                                data-selected_key="optins">
                                <img src="'. MEMBERSHIPLITE_IMAGES_URL .'/download-icon.svg" alt="Download Icon" class="arm-icon" />
                                '. esc_attr__('Download', 'ARMember').'
                            </button>
                        </div>
                    </div>
                </div>
                <a href="javascript:void(0)"  class="arm_clear_debug_log arm_margin_left_32 arm_display_flex arm_debug_log"
                    onclick="arm_clear_general_debug_logs(\'optins\')"><img src="'. MEMBERSHIPLITE_IMAGES_URL.'/clear_log_icon.png" alt="View Log Icon" class="arm-debug-log-icon">'. esc_attr__('Clear Log', 'ARMember').'</a>';
                
											$arm_debug_clear_log = $arm_global_settings->arm_get_confirm_box('optins', esc_html__("Are you sure you want to clear debug logs?", 'ARMember'), 'arm_clear_debug_log','',esc_html__("Clear", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Clear logs", 'ARMember'));
											$arm_debug_log_html .= $arm_debug_clear_log; //phpcs:ignore
            $arm_debug_log_html .= '</div>
        </div>
    </div>';

							$arm_general_debug_log_details = "";
							$arm_general_debug_log_details = apply_filters('arm_add_general_debug_log_details', $arm_general_debug_log_details);
$arm_debug_log_html .= '</table>';
}