<?php
global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_buddypress_feature;
$totalUsersToSync = $wpdb->get_var('SELECT COUNT(*) FROM `'.$wpdb->prefix.'users`');
$check_buddyp_buddyb = $arm_buddypress_feature->arm_check_buddypress_buddyboss();
?>
<div class="arm_global_settings_main_wrapper">
	<div class="page_sub_content arm_margin_bottom_60">
		<div class="page_sub_title"><?php echo esc_html__('Map with','ARMember').' '. esc_html($check_buddyp_buddyb['arm_title']) .' '. esc_html__('Profile Fields','ARMember'); ?></div>
		<div class="armclear"></div>
		<form  method="post" action="#" id="arm_buddypress_settings" class="arm_buddypress_settings_from arm_admin_form">
                                <div class="arm_setting_main_content arm_padding_0 arm_margin_top_24">
                                    <div class="">
                                    <div class="arm_row_wrapper arm_row_wrapper_padding_before ">
                                    <div class="left_content">
                                        <div class="arm_form_header_label arm-setting-hadding-label"><?php echo esc_html($check_buddyp_buddyb['arm_title']) .' '. esc_html__('Profile Fields','ARMember'); ?></div>
                                    </div>
                                </div>
                                <div class="arm_content_border"></div>

                               <!-- <div class="arm_bp_fields_label"><?php esc_html_e('Form Fields', 'ARMember'); ?></div>
                               <div class="arm_bp_fields_input"><?php echo esc_html($check_buddyp_buddyb['arm_title']).' '.esc_html__('Fields', 'ARMember'); ?></div> -->
                               <div class="arm_page_setup_flex_group arm_row_wrapper_padding_after  arm_padding_top_24 arm_padding_32">
                            <?php 

                            $arm_get_form_fields = $wpdb->get_results( $wpdb->prepare("SELECT `arm_form_field_id`, `arm_form_field_form_id`, `arm_form_field_slug`, `arm_form_field_option`, `arm_form_field_bp_field_id`, `arm_form_field_status` FROM " . $ARMember->tbl_arm_form_field . " WHERE `arm_form_field_slug` !=%s AND `arm_form_field_status` = %d",'',1)); //phpcs:ignore --Reason $ARMember->tbl_arm_form_field is a table name
                            if(!empty($arm_get_form_fields)){
                                $i = 0;
                                $j = 0;
                                $maparray = $arm_buddypress_feature->arm_map_buddypress_armember_field_types();
                                $form_object = array(); 
                                foreach($arm_get_form_fields as $arm_get_form_field){
                                    $arm_form_field_id = $arm_get_form_field->arm_form_field_id;
                                    $arm_form_field_form_id = $arm_get_form_field->arm_form_field_form_id;
                                    $arm_form_field_slug = $arm_get_form_field->arm_form_field_slug;
                                    $arm_form_field_option= maybe_unserialize($arm_get_form_field->arm_form_field_option);
                                    $arm_from_field_bp_field_id = $arm_get_form_field->arm_form_field_bp_field_id;

                                    $arm_form_field_type = $arm_form_field_option['type'];
                                    $arm_form_field_label = $arm_form_field_option['label'];
                                 
                          
                                    if(isset($form_object[$arm_form_field_form_id]) && !empty($form_object[$arm_form_field_form_id]))
                                    {
                                        
                                      $form = $form_object[$arm_form_field_form_id];
                                        
                                    }
                                    else{
                                      
                                        $form = new ARM_Form('id', $arm_form_field_form_id);
                                        $form_object[$arm_form_field_form_id] = $form;
                                    }
                                    $from_type = $form->type;
                                    $is_default_form = $form->template;
                                    if($form->type == 'registration' && $is_default_form != true){
                                        if (!in_array($arm_form_field_type, array('hidden', 'html', 'info', 'section', 'rememberme', 'submit', 'repeat_pass', 'repeat_email','avatar', 'file', 'password', 'social_fields','arm_captcha'))) {
                                            $maparray_type = isset($maparray[$arm_form_field_type]) ? $maparray[$arm_form_field_type] : array();
                                            if(!empty($maparray_type))
                                            {
                                                $super_admin_placeholders = ' AND `type` IN (';
                                                $super_admin_placeholders .= rtrim( str_repeat( '%s,', count( $maparray_type ) ), ',' );
                                                $super_admin_placeholders .= ')';
                                
                                                array_unshift( $maparray_type, $super_admin_placeholders );
                                
                                                $user_where = call_user_func_array(array( $wpdb, 'prepare' ), $maparray_type );
                                                $arm_result = $wpdb->get_results( $wpdb->prepare("SELECT `id`, `name` FROM `" . $wpdb->prefix . "bp_xprofile_fields` WHERE `parent_id`=%d ".$user_where,0)); //phpcs:ignore --Reason $wpdb->prefix . "bp_xprofile_fields` is a table name
                                            
                                                ?>
                                                    
                                                        <div class="arm_form_field_block">
                                                            <div class="arm-form-table-label"><?php echo !empty($arm_form_field_label) ? stripslashes_deep( esc_html($arm_form_field_label)) : '&nbsp;'; //phpcs:ignore?></div>
                                                            <div class="arm_width_100_pct">
                                                                <input type='hidden' id="arm_map_buddypress_field_<?php echo esc_attr($i); ?>"  value="<?php echo esc_attr($arm_from_field_bp_field_id); ?>" name="arm_buddypress_field_id[<?php echo esc_attr($arm_form_field_id); ?>]" />
                                                                <dl class="arm_selectbox column_level_dd arm_width_100_pct arm_margin_top_12">
                                                                    <dt><span><?php echo esc_html__('Select','ARMember').' '. esc_attr($check_buddyp_buddyb['arm_title']) .' '. esc_html__('Field', 'ARMember'); ?></span>
                                                                    <input type="text" style="display:none;" value="" class="arm_autocomplete"  />
                                                                    <i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                                    <dd>
                                                                        <ul data-id="arm_map_buddypress_field_<?php echo esc_attr($i); ?>">
                                                                            <li data-label="<?php echo esc_attr__('Select', 'ARMember') .' '. esc_attr($check_buddyp_buddyb['arm_title']) .' '.esc_attr__('field', 'ARMember'); ?>" data-value="">
                                                                                <?php echo esc_html__('Select', 'ARMember').' '. esc_html($check_buddyp_buddyb['arm_title']) .' '. esc_html__('field', 'ARMember'); ?>
                                                                            </li>
                                                                        <?php
                                                                            if(!empty($arm_result)){
                                                                                foreach ($arm_result as $a) {
                                                                        ?>
                                                                                <li data-label="<?php echo esc_attr($a->name); ?>" data-value="<?php echo esc_attr($a->id); ?>"><?php echo esc_html($a->name) ?></li>
                                                                        <?php 
                                                                                } 
                                                                            } 
                                                                        ?>
                                                                        </ul>
                                                                    </dd>
                                                                </dl>    
                                                            </div>
                                                        </div>
                                                <?php
                                            }
                                            $i++;
                                            $j++;
                                        }
                                    }

                                }
                            }
                            ?>
                            </div>
                        </div>                        
                    </div>
                    <div class="armclear"></div>
                    
                <div class="arm_setting_main_content arm_margin_top_24">
                    <div class="arm_row_wrapper">
                        <div class="arm-form-table-label"><?php echo esc_html__('Map with','ARMember').' '. esc_attr($check_buddyp_buddyb['arm_title']) .' '. esc_html__('avatar','ARMember'); ?></div>
                        <div class="arm-form-table-content">						
                            <div class="armswitch arm_global_setting_switch arm_margin_0">
                                <input type="checkbox" id="map_with_buddypress_avatar" value="1" class="armswitch_input" name="map_with_buddypress_avatar" <?php checked($arm_buddypress_feature->map_with_buddypress_avatar, 1);?>/>
                                <label for="map_with_buddypress_avatar" class="armswitch_label"></label>
                            </div>                        
                        </div>
                    </div>
                </div>
                  
                <div class="arm_setting_main_content arm_margin_top_24">
                    <div class="arm_row_wrapper">
                        <div class="arm-form-table-label"><?php echo esc_html__('Map with','ARMember').' '. esc_attr($check_buddyp_buddyb['arm_title']) .' '. esc_html__('cover photo','ARMember'); ?></div>
                        <div class="arm-form-table-content">						
                            <div class="armswitch arm_global_setting_switch arm_margin_0">
                                <input type="checkbox" id="map_with_buddypress_profile_cover" value="1" class="armswitch_input" name="map_with_buddypress_profile_cover" <?php checked($arm_buddypress_feature->map_with_buddypress_profile_cover, 1);?>/>
                                <label for="map_with_buddypress_profile_cover" class="armswitch_label"></label>
                            </div>                    
                        </div>
                    </div>
                </div>
                <div class="arm_margin_top_24"></div> 
                                
                    <div class="page_sub_title arm_margin_bottom_32"><?php esc_html_e('Map ARMember Profile Page','ARMember'); ?></div>
                    <div class="arm_setting_main_content arm_padding_0"> 
                    <div class="arm_row_wrapper arm_row_wrapper_padding_before ">
						<div class="left_content">
							<div class="arm_form_header_label arm-setting-hadding-label arm_font_">
                            <?php esc_html_e('Map ARMember Profile Page','ARMember'); ?></div>
						</div>
					</div>  
                    <div class="arm_content_border"></div>

                    <div class="arm_row_wrapper arm_row_wrapper_padding_after  arm_display_block">
                        <div class="arm-form-table-label"><?php echo esc_html__('Armember Profile page for','ARMember').' '. esc_attr($check_buddyp_buddyb['arm_title']); ?>
                        <?php $arm_bp_profile_tooltip = esc_html__('Select page to redirect at custom profile page instead','ARMember').' '. $check_buddyp_buddyb['arm_title'].' '.esc_html__('default profile page.', 'ARMember'); ?>
                        <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_attr($arm_bp_profile_tooltip); ?>"></i>
                    </div>
                            <div class="arm-form-table-content">
                                <?php 
                                $arm_global_settings->arm_wp_dropdown_pages(
                                        array(
                                                'selected'              => isset($arm_buddypress_feature->show_armember_profile) ? $arm_buddypress_feature->show_armember_profile : 0,
                                                'name'                  => 'show_armember_profile',
                                                'id'                    => 'show_armember_profile',
                                                'show_option_none'      => 'Select Page',
                                                'option_none_value'     => '0',
                                        )
                                );
                                ?>                                
                                <div class="arm_info_text arm_info_text_style" ><?php echo esc_html__('Choose ARMember profile page to replace','ARMember').' '. esc_html($check_buddyp_buddyb['arm_title']) .' '. esc_html__('profile page.','ARMember'); ?></div>
                            </div>
                    </div>   
                </div>
                    
                    <div class="arm_submit_btn_container arm_apply_changes_btn_container arm_buddypress_submit_btn">
                    <img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_loader_img" class="arm_submit_btn_loader" style="display:none;" width="24" height="24" />&nbsp;<button class="arm_save_btn arm_buddypress_settings_btn" type="submit" id="arm_buddypress_settings_btn" name="arm_buddypress_settings_btn"><?php esc_html_e('Apply Changes', 'ARMember') ?></button>
                    </div>
                    <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
                    <input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
                </form>
                
                
                <div class="arm_margin_top_24"></div> 
                <div class="page_sub_title arm_margin_bottom_32"><?php echo esc_html__('Sync','ARMember') . ' ' . esc_html($check_buddyp_buddyb['arm_title']) .' '. esc_html__('& ARMember','ARMember'); ?></div>
                <div class="arm_admin_form">
                    <div class="arm_setting_main_content arm_padding_0 ">
                    <div class="arm_row_wrapper arm_row_wrapper_padding_before ">
						<div class="left_content">
							<div class="arm_form_header_label arm-setting-hadding-label arm_font_">
                            <?php echo esc_html__('How to sync with', 'ARMember'). ' ' . esc_html($check_buddyp_buddyb['arm_title']) .esc_html__('?', 'ARMember'); ?>	</div>
						</div>
					</div>
                    <div class="arm_content_border"></div>
                        <div class="arm_row_wrapper arm_row_wrapper_padding_after  arm_display_block">
                           
                                <input type="radio" name="arm_bp_sync" id="arm_by_sync_pull" value="pull"  class="arm_iradio"><label class="arm_width_280" for="arm_by_sync_pull" ><?php echo esc_html__('Pull Data from', 'ARMember').' '. esc_html($check_buddyp_buddyb['arm_title']); ?></label>
                                <input type="radio" name="arm_bp_sync" id="arm_by_sync_push" value="push" checked="checked" class="arm_iradio"><label for="arm_by_sync_push"><?php esc_html_e('Pull Data from ARMember', 'ARMember'); ?></label>
                                
                                <div class="arm_buddypress_sync_progressbar">
                                    <div class="arm_buddypress_sync_progressbar_inner"></div>
                                </div>
                                
                                <div class="arm_submit_btn_container arm_buddypress_sync_btn_div arm_padding_0" style="display: inline-block;width: 100%; border-top : 0">
                                    <img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_loader_img_sync" class="arm_submit_btn_loader" style="display:none;" width="24" height="24" />&nbsp;<button class="arm_save_btn arm_buddypress_sync_btn arm_margin_right_0" data-total-users="<?php echo esc_attr($totalUsersToSync); ?>" type="button" id="arm_buddypress_sync_btn" name="arm_buddypress_sync_btn"><?php esc_html_e('Sync', 'ARMember') ?></button>
                                    <div class="armclear"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
	</div>
</div>

