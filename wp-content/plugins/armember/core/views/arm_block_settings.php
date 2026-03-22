<?php 
global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans;
$arm_all_block_settings = $arm_global_settings->arm_get_all_block_settings();
$all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all', ARRAY_A, true);
$failed_login_users = $arm_members_class->arm_get_failed_login_users();
$arm_all_block_settings['failed_login_lockdown'] = isset($arm_all_block_settings['failed_login_lockdown']) ? $arm_all_block_settings['failed_login_lockdown'] : 0;
$arm_all_block_settings['max_login_retries'] = isset($arm_all_block_settings['max_login_retries']) ? $arm_all_block_settings['max_login_retries'] : 5;
$arm_all_block_settings['temporary_lockdown_duration'] = isset($arm_all_block_settings['temporary_lockdown_duration']) ? $arm_all_block_settings['temporary_lockdown_duration'] : 10;
$arm_all_block_settings['permanent_login_retries'] = isset($arm_all_block_settings['permanent_login_retries']) ? $arm_all_block_settings['permanent_login_retries'] : 15;
$arm_all_block_settings['permanent_lockdown_duration'] = isset($arm_all_block_settings['permanent_lockdown_duration']) ? $arm_all_block_settings['permanent_lockdown_duration'] : 24;
$arm_all_block_settings['remained_login_attempts'] = isset($arm_all_block_settings['remained_login_attempts']) ? $arm_all_block_settings['remained_login_attempts'] : 0;
$arm_all_block_settings['track_login_history'] = isset($arm_all_block_settings['track_login_history']) ? $arm_all_block_settings['track_login_history'] : 1;
$arm_all_block_settings['arm_block_ips'] = isset($arm_all_block_settings['arm_block_ips']) ? $arm_all_block_settings['arm_block_ips'] : '';
$arm_all_block_settings['arm_conditionally_block_urls'] = isset($arm_all_block_settings['arm_conditionally_block_urls']) ? $arm_all_block_settings['arm_conditionally_block_urls'] : 0;
$conditionally_block_urls_options = (isset($arm_all_block_settings['arm_conditionally_block_urls_options']) && $arm_all_block_settings['arm_conditionally_block_urls'] == 1) ? $arm_all_block_settings['arm_conditionally_block_urls_options'] : array('0' => array('plan_id' => '', 'arm_block_urls' => ''));
$conditionally_block_urls_options_count = isset($arm_all_block_settings['arm_conditionally_block_urls_options'])?count($conditionally_block_urls_options):1;

if(isset($_POST["arm_export_login_history"]) && $_POST["arm_export_login_history"] == 1) {//phpcs:ignore
        $user_table = $wpdb->users;
        $arm_log_history_search_user = isset($_POST['arm_log_history_search_user']) ? sanitize_text_field($_POST['arm_log_history_search_user']): '';//phpcs:ignore
        $final_log = array();
        if (is_multisite()) {
            $wp_date_time_format = get_option('date_format') . " " . get_option('time_format');
        } else {
            $wp_date_time_format = get_site_option('date_format') . " " . get_site_option('time_format');
        }
        $arm_login_history_tmp = array (
            "Username" => '',
            "Logged_In_Date" => '',
            "Logged_In_IP" => '',
            "Browser_Name" => '',
            "Country" => '',
            "Logged_Out_Date" => ''
        );
        $history_where = "";
        if(!empty($arm_log_history_search_user))
        {
           $history_where .= $wpdb->prepare(' AND u.user_login LIKE %s ','%'.$arm_log_history_search_user.'%');
        }
        $historyRecords = $wpdb->get_results("SELECT u.user_login, l.arm_user_current_status, l.arm_user_id,l.arm_logged_in_ip, l.arm_logged_in_date, l.arm_logout_date, l.arm_history_browser, l.arm_login_country FROM `{$user_table}` u INNER JOIN `" . $ARMember->tbl_arm_login_history . "` l ON u.ID = l.arm_user_id where 1 = 1  $history_where ORDER BY l.arm_history_id DESC ", ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_login_history is a table name
        foreach ($historyRecords as $row) {
            $logout_date = !empty($row['arm_logout_date']) ? date_create($row['arm_logout_date']) : '';
            $login_date = !empty($row['arm_logged_in_date']) ? date_create($row['arm_logged_in_date']) : '';
            if (isset($row['arm_user_current_status']) && $row['arm_user_current_status'] == 1 && $row['arm_logout_date'] == "0000-00-00 00:00:00") {
                $arm_logged_out_date = esc_html__('Currently Logged In', 'ARMember');
            } else {
                if ($row['arm_user_current_status'] == 0 && $row['arm_logout_date'] == "0000-00-00 00:00:00") {
                    $arm_logged_out_date = "-";
                } else {
                    $arm_logged_out_date = date_i18n($wp_date_time_format, strtotime($row['arm_logout_date']));
                }
            }

            $tmp["Username"] = !empty($row['user_login']) ? $row['user_login'] : '-';
            $tmp["Logged_In_Date"] = !empty($row['arm_logged_in_date']) ? date_i18n($wp_date_time_format, strtotime($row['arm_logged_in_date'])) : '';
            $tmp["Logged_In_IP"] = !empty($row["arm_logged_in_ip"]) ? $row["arm_logged_in_ip"] : '';
            $tmp["Browser_Name"] = !empty($row["arm_history_browser"]) ? $row["arm_history_browser"] : '';
            $tmp["Country"] = !empty($row["arm_login_country"]) ? $row["arm_login_country"] : '';
            $tmp["Logged_Out_Date"] = $arm_logged_out_date;
            if($tmp["Logged_Out_Date"] == "-") {
                $tmp["Logged_Out_Date"] = "";
            }
            
            array_push($final_log, $tmp);
        }

        ob_clean();
        ob_start();
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=ARMember-export-login-history.csv");
        header("Content-Transfer-Encoding: binary");
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys($arm_login_history_tmp));
        if(!empty($final_log)) {
            foreach ($final_log as $row) {
                fputcsv($df, $row);
            }
        }
        fclose($df);
        exit;
}
$arm_block_ips = $arm_all_block_settings['arm_block_ips'];
$block_ips_data = (!empty($arm_block_ips)) ? esc_textarea( stripslashes_deep($arm_block_ips) ) : '';
$block_ip_message = (!empty($arm_all_block_settings['arm_block_ips_msg'])) ? esc_attr(stripslashes($arm_all_block_settings['arm_block_ips_msg'])) : '';

$block_url_data = (!empty($arm_all_block_settings['arm_block_urls'])) ?  esc_textarea( stripslashes_deep($arm_all_block_settings['arm_block_urls']) ) : '';

$is_conditional_redirection = ($arm_all_block_settings['arm_conditionally_block_urls'] == 1) ? 'checked="checked"' : '';

$condidtional_block_css = ($arm_all_block_settings['arm_conditionally_block_urls'] == 1) ? 'display:block;' : 'display:none;';

$arm_block_settings='
			<div class="arm_setting_main_content arm_padding_0 arm_margin_top_24 ">
                <div class="arm_row_wrapper arm_row_wrapper_padding_before">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label">'. esc_html__('Block IP Addresses', 'ARMember') .'</div>
					</div>
				</div>
				<div class="arm_content_border"></div>
                <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block arm_security_options_block">
                    <div class="form-field page_sub_title arm_font_size_16 arm_font_weight_500">
                        <label for="arm_block_ips" class="arm-form-table-label">'. esc_html__('Block IP Addresses', 'ARMember') .'
                        <i class="arm_helptip_icon armfa armfa-question-circle" title="'. esc_attr__("Those IP Address(es) which are entered here, will not be able to access your website. Please note that IP address should exact match. For example, 0.0.0.1 will be banned if and only if IP address will exact match with user's IP address.", 'ARMember').'"></i></label>
                    </div>
                    <div class="form-field">
                          <div class="arm-form-table-content">
                            <textarea name="arm_block_settings[arm_block_ips]" id="arm_block_ips" rows="8" cols="40">'. 
                            $block_ips_data .'</textarea>
                            <div class="arm_info_text_style arm_padding_0 arm_margin_0 arm_margin_top_5">'. esc_html__('You should place each IP Address on a new line.','ARMember').'</div>
                        </div>
                    </div>
                    <div class="form-field arm_margin_top_32">
                        <label for="arm_block_ips_msg" class="arm-form-table-label">'. esc_html__('Blocked IP Address Message', 'ARMember').'</label>
                        <i class="arm_helptip_icon armfa armfa-question-circle" title="'. esc_attr__("This message will be display when IP Address is blocked.", 'ARMember').'"></i>
                        <div class="arm-form-table-content arm_margin_top_12 arm_security_options_block_input arm_max_width_100_pct arm_width_100_pct">
                            <input type="text" class="" name="arm_block_settings[arm_block_ips_msg]" id="arm_block_ips_msg" value="'. $block_ip_message .'"/>
                        </div>
                    </div>
                </div>
			</div>

			<div class="arm_setting_main_content arm_padding_0 arm_margin_top_24">
                <div class="arm_row_wrapper arm_row_wrapper_padding_before">
					<div class="left_content">
						<div class="arm_form_header_label arm-setting-hadding-label">'. esc_html__('Blocked URLs', 'ARMember') .'</div>
					</div>
				</div>
				<div class="arm_content_border"></div>
                <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block arm_security_options_block">
                    <div class="form-field">
                        <label for="arm_block_urls" class="arm-form-table-label">'. esc_html__('Block URLs', 'ARMember').'</label>
                        <div class="arm-form-table-content arm_margin_top_12">
                            <textarea name="arm_block_settings[arm_block_urls]" id="arm_block_urls" rows="8" cols="40">'. $block_url_data .'</textarea>
                            <div class="arm_info_text_style arm_padding_0 arm_margin_0 arm_margin_top_12">'. esc_html__('You should place each URL on a new line','ARMember') .'</div>
                            <div class="arm_info_text_style arm_padding_0 arm_margin_0 arm_margin_top_12">'. esc_html__('Entered URLs will be blocked for all users and visitors except administrator.','ARMember').'</div>
                            <div class="arm_info_text_style arm_padding_0 arm_margin_0 arm_margin_top_12">'. esc_html__('You can use wildcard(*) for specific pattern.','ARMember').'(i.e. http://www.example.com/<b>*some_text*</b>/page)</div>

                        <div class="arm_content_border arm_margin_top_24"></div>                            
                        <div class="conditionally_block_url_div arm_global_settings_sub_content track_login_history_div arm_row_wrapper arm_margin_top_24">
                            <div class="left_content">
                                <label for="conditionally_block_urls" class="arm_form_header_label arm-setting-hadding-label arm_margin_0"><b>'. esc_html__('Conditionally Plan','ARMember'). esc_html__(' wise Block URLs', 'ARMember').'</b></label>
                            </div>
                            <div class="armswitch arm_global_setting_switch arm_margin_0">
                                    <input type="checkbox" id="conditionally_block_urls" value="1" class="armswitch_input" name="arm_block_settings[arm_conditionally_block_urls]" '.$is_conditional_redirection.'/>
                                    <label for="conditionally_block_urls" class="armswitch_label"></label>
                                    <input type="hidden" name="arm_conditional_no" id="arm_conditional_no" value="'. esc_attr($conditionally_block_urls_options_count).'" />
                                    <input type="hidden" id="arm_plus_icon" value="'. MEMBERSHIPLITE_IMAGES_URL.'/add_plan.svg" />
                                    <input type="hidden" id="arm_plus_icon_hover" value="'. MEMBERSHIPLITE_IMAGES_URL.'/add_plan_hover.png" />
                            </div>
                        </div>
                                                    
                        <div class="arm_conditionally_block_urls_tbl " style="'.$condidtional_block_css.'">';
                        if (!empty($conditionally_block_urls_options)) :
                            $condition_count = 0;
                            foreach ($conditionally_block_urls_options as $condition) :
                                $condition_count++;
                        
                                // Set the conditional plan ID
                                $conditional_plan_id = isset($condition['plan_id']) ? esc_attr($condition['plan_id']) : '';
                        
                                // Start first row for each condition
                                $arm_block_settings .= '<div id="cond_block_url_first_row' . esc_attr($condition_count) . '">
                                    <div class="arm-form-table-label arm_select_plan_condtionally arm_margin_top_24 ">
                                        <label for="arm_plan_">' . esc_html__('Select Plan', 'ARMember') . '</label>
                                    </div>
                                    <div class="arm_conditionally_block_urls_content arm_email_setting_flex_group">
                                        <div class="arm_form_field_block">
                                            <input type="hidden" id="arm_plan_' . esc_attr($condition_count) . '" name="arm_block_settings[arm_conditionally_block_urls_options][' . esc_attr($condition_count) . '][plan_id]" value="' . $conditional_plan_id . '" />
                                            <dl class="arm_selectbox column_level_dd arm_width_100_pct arm_margin_top_12">
                                                <dt class="arm_display_100_pct"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul data-id="arm_plan_' . esc_attr($condition_count) . '" class="arm_conditional_plans_li">
                                                        <li data-label="' . esc_attr__('Select Plan', 'ARMember') . '" data-value="">' . esc_html__('Select Plan', 'ARMember') . '</li>';
                        
                                                        if (!empty($all_plans)) {
                                                            foreach ($all_plans as $p) {
                                                                $arm_block_settings .= '<li data-label="' . esc_attr(stripslashes($p['arm_subscription_plan_name'])) . '" data-value="' . esc_attr($p['arm_subscription_plan_id']) . '">' . esc_html(stripslashes($p['arm_subscription_plan_name'])) . '</li>';
                                                            }
                                                        }
                        
                                            $arm_block_settings .= '</ul>
                                                </dd>
                                            </dl>
                                            <span class="arm_invalid arm_block_url_plan_error_' . esc_attr($condition_count) . ' arm_width_100_pct arm_margin_top_12 arm_padding_0">' . esc_html__('Please select plan.', 'ARMember') . '</span>
                                        </div>
                                        <div class="arm_add_conditionally arm_condition_icon arm_margin_top_12 arm_width_90">
                                            <a href="javascript:void(0);" class="arm_add_conditionally_block_urls arm_plan_cycle_plus_icon arm_margin_right_0">
                                                <!-- Additional logic can be added here if needed -->
                                            </a>
                                            <a href="javascript:void(0);" class="arm_remove_conditionally_block_urls arm_plan_cycle_minus_icon arm_margin_left_10 arm_margin_right_0" 
                                                data_conditionally_id="' . esc_attr($condition_count) . '" ' . ($condition_count == 1 ? "style='display:none;'" : '') . '>
                                            </a>
                                        </div>
                                    </div>
                                </div>';
                        
                                // Block URLs section
                                $blocked_URLs = !empty($condition['arm_block_urls']) ? esc_textarea(stripslashes_deep($condition['arm_block_urls'])) : '';
                                $arm_block_settings .= '<div id="cond_block_url_second_row' . esc_attr($condition_count) . '">
                                    <div class="arm_margin_top_24 arm_block_urls_conditionally">
                                        <label for="arm_block_urls">' . esc_html__('Block URLs', 'ARMember') . '</label>
                                    </div>
                                    <div class="arm_conditionally_block_urls_content arm_margin_top_12">
                                        <textarea name="arm_block_settings[arm_conditionally_block_urls_options][' . esc_attr($condition_count) . '][arm_block_urls]" id="arm_block_urls" rows="8" cols="40">' . $blocked_URLs . '</textarea>
                                    </div>
                                </div>';
                        
                            endforeach;
                        endif;
                        
                            $arm_all_block_settings['arm_block_urls_option'] = (!empty($arm_all_block_settings['arm_block_urls_option'])) ? $arm_all_block_settings['arm_block_urls_option'] : 'message';

                            $blockled_url_msg = ($arm_all_block_settings['arm_block_urls_option'] == 'message') ? "checked='checked'" : '';

                            $blockled_url_redirect = ($arm_all_block_settings['arm_block_urls_option'] == 'redirect') ? 'checked="checked"' : '';

                            $blocked_message_section = ($arm_all_block_settings['arm_block_urls_option']=='message') ? '':'hidden_section';
                            
                            $blocked_url_section = ($arm_all_block_settings['arm_block_urls_option']=='redirect') ? '':'hidden_section';

                            $blocked_url_message = (!empty($arm_all_block_settings['arm_block_urls_option_message'])) ? esc_html(stripslashes($arm_all_block_settings['arm_block_urls_option_message'])) : '';

                            $blocked_url_redirection = (!empty($arm_all_block_settings['arm_block_urls_option_redirect'])) ? esc_attr(stripslashes($arm_all_block_settings['arm_block_urls_option_redirect'])) : '';

                            $arm_block_settings .='</div>
                        </div>
                    </div>
                    <div class="form-field arm_margin_top_24">
                        <label class="arm-form-table-label">'. esc_html__('Blocked URLs Options', 'ARMember').'</label>
                        <div class="arm-form-table-content arm_padding_top_20 arm_block_urls_opt" >					
                            <input type="radio" id="arm_block_urls_opt_radio_message" class="arm_iradio arm_block_urls_opt_radio" name="arm_block_settings[arm_block_urls_option]" value="message" '.$blockled_url_msg.'><label for="arm_block_urls_opt_radio_message" class="arm-form-table-label arm_padding_right_0">'.esc_html__('Display Message', 'ARMember').'</label>

                            <i class="arm_helptip_icon armfa armfa-question-circle arm_padding_right_46 arm_padding_top_5" title="'. esc_attr__("This message will be display when requested URL or URL pattern is blocked.", 'ARMember').'"></i></label>

                            <input type="radio" id="arm_block_urls_opt_radio_redirect" class="arm_iradio arm_block_urls_opt_radio" name="arm_block_settings[arm_block_urls_option]" value="redirect" '.$blockled_url_redirect.'><label for="arm_block_urls_opt_radio_redirect" class="arm-form-table-label arm_padding_right_0"><span>'. esc_html__('Redirect to url', 'ARMember').'</span></label>
                            <i class="arm_helptip_icon armfa armfa-question-circle arm_padding_top_5" title="'. esc_attr__("Member will be redirect to this URL when requested URL or URL pattern is blocked.", 'ARMember').'"></i></label>
                            <div class="armclear"></div>
                            <div class="arm_block_urls_option_fields arm_block_urls_option_fields_message '.$blocked_message_section.' ">
                                <label class="arm_margin_top_12"><input type="text" name="arm_block_settings[arm_block_urls_option_message]" value="'. $blocked_url_message.'"/>
                            </div>
                            <div class="arm_block_urls_option_fields arm_block_urls_option_fields_redirect '. $blocked_url_section.'">
                                <label class="arm_margin_top_12"><input type="text" name="arm_block_settings[arm_block_urls_option_redirect]" value="'. $blocked_url_redirection .'"/>
                            </div>
                        </div>
                    </div>
                </div>
			</div>';

            $allow_track_login_history = ($arm_all_block_settings['track_login_history'] == 1) ? 'checked="checked"' : '';
            $arm_block_settings .='<div class="arm_margin_top_24"></div>
            <div class="form-table">
                <div class="arm_setting_main_content">
                    <div class="arm_global_settings_sub_content track_login_history_div arm_row_wrapper">
                        <div class="left_content">
                            <div class="arm_form_header_label arm-setting-hadding-label arm_margin_0">'. esc_html__('Record Login History','ARMember').'</div>
                        </div>
                        <div class="arm-form-table-content">						
                            <div class="armswitch arm_global_setting_switch arm_margin_0">
                                <input type="checkbox" id="track_login_history" value="1" class="armswitch_input" name="arm_block_settings[track_login_history]" '.$allow_track_login_history.'/>
                                <label for="track_login_history" class="armswitch_label"></label>
                            </div>               
                        </div>
                    </div>
		    <div class="arm_margin_top_24">
                    <div class ="arm_position_relative">
                            <button onclick="showConfirmBoxCallback(\'arm_clear_login_history\');" class="arm_save_btn arm_black_btn" type="button">'. esc_html__('Reset Login History', 'ARMember').'</button>
                        '. $arm_global_settings->arm_get_confirm_box('arm_clear_login_history', esc_html__("Are you sure want to reset all user's login history?", "ARMember"), 'arm_reset_login_history', '',esc_html__("Reset", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Reset Login History", 'ARMember')).'&nbsp;<img src="'. MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif" class="arm_submit_btn_loader" id="arm_reset_history_loader_img" style="display:none;" width="24" height="24" />
                    </div>
                </div>
                </div>                
			</div>';

            $arm_block_settings .='
            
<script>
    var ARM_CONDI_BLOCK_REQ_MSG = "'. esc_html__('Please select plan.', 'ARMember').'";
</script>
 
<script>
    var NO_USERS_AVAILABE = "'. addslashes( esc_html__('No Users Available', 'ARMember')).'";
</script>';