<?php
global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings,$arm_members_badges,$arm_email_settings,$arm_manage_coupons;
$active = 'arm_general_settings_tab_active';
$b_action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : "manage_badges";
?>
<?php

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();
?>
<div class="wrap arm_page arm_general_settings_main_wrapper">
	
	<div class="content_wraper arm_badges_settings_content" id="content_wraper">
		<div class="page_title"><?php esc_html_e('Badges & Achievements','ARMember'); ?>
				<?php 
					if($b_action == 'manage_badges') { 
				?>
					 	<div class="arm_add_new_item_box arm_margin_bottom_20" >			
			            <a class="greensavebtn arm_add_new_badges_btn" href="javascript:void(0);" ><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.svg"><span><?php esc_html_e('Add New Badge', 'ARMember') ?></span></a>
			         </div>
	      	<?php 
	      		} elseif ($b_action == 'manage_achievements') { 
	      	?>
		      		<div class="arm_add_new_item_box arm_margin_bottom_20" >            
	            		<a class="greensavebtn arm_add_achievements_btn" href="javascript:void(0);" ><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.svg"><span><?php esc_html_e('Add New Achievement', 'ARMember') ?></span></a>
	        			</div>
	      	<?php 
	      		} elseif ($b_action == 'manage_user_achievements') { 
	      	?>
	      		  <div class="arm_add_new_item_box arm_margin_bottom_20" >			
            			<a class="greensavebtn arm_add_user_badges_btn" href="javascript:void(0);" ><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.svg"><span><?php esc_html_e('Add User Badges', 'ARMember');?></span></a>
        				</div>
        		<?php 
        			} 
        		?>
			</div>
		<div class="armclear"></div>
		<div class="arm_general_settings_wrapper">			
			<div class="arm_general_settings_tab_wrapper arm_padding_left_64 arm_width_auto">
				<a class="arm_general_settings_tab arm_badges_tab <?php echo(in_array($b_action, array('manage_badges'))) ? esc_attr($active) : ""; ?>" href="<?php echo esc_url( admin_url('admin.php?page=' . $arm_slugs->badges_achievements) ); ?>"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.5213 2.45706C9.31465 1.58592 10.6853 1.58592 11.4787 2.45706L11.9769 3.00413C12.3779 3.44439 12.9541 3.68309 13.549 3.65529L14.2881 3.62075C15.4651 3.56575 16.4343 4.53498 16.3793 5.71194L16.3447 6.45107C16.3169 7.0459 16.5556 7.62218 16.9959 8.02313L17.543 8.52135C18.4141 9.31469 18.4141 10.6854 17.543 11.4787L16.9959 11.977C16.5556 12.3779 16.3169 12.9542 16.3447 13.549L16.3793 14.2881C16.4343 15.4651 15.4651 16.4343 14.2881 16.3793L13.549 16.3448C12.9541 16.317 12.3779 16.5557 11.9769 16.996L11.4787 17.543C10.6853 18.4142 9.31465 18.4142 8.5213 17.543L8.02308 16.996C7.62213 16.5557 7.04585 16.317 6.45102 16.3448L5.71189 16.3793C4.53493 16.4343 3.5657 15.4651 3.62071 14.2881L3.65525 13.549C3.68305 12.9542 3.44434 12.3779 3.00408 11.977L2.45701 11.4787C1.58588 10.6854 1.58588 9.31469 2.45701 8.52135L3.00408 8.02313C3.44434 7.62218 3.68305 7.0459 3.65525 6.45107L3.62071 5.71194C3.5657 4.53498 4.53493 3.56575 5.71189 3.62075L6.45102 3.65529C7.04585 3.68309 7.62213 3.44439 8.02308 3.00413L8.5213 2.45706Z" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round"/><path d="M7.5 10L9.16667 11.6667L12.5 8.33333" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('Badges', 'ARMember'); ?></a>
                <a class="arm_general_settings_tab arm_achievements_tab arm_margin_left_32 <?php echo (in_array($b_action, array('manage_achievements'))) ? esc_attr($active) : "";?>" href="<?php echo esc_url( admin_url('admin.php?page=' . $arm_slugs->badges_achievements . '&action=manage_achievements') ); ?>"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.62051 3.33337H14.4271C14.4271 3.33337 13.6932 14.381 10.0238 14.381C8.23128 14.381 7.13929 11.7446 6.49115 9.04766C5.81252 6.22374 5.62051 3.33337 5.62051 3.33337Z" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14.4277 3.33329C14.4277 3.33329 15.1953 2.5144 15.8333 2.49995C17.0833 2.47164 17.3144 3.33329 17.3144 3.33329C17.5591 3.84123 17.7548 5.16186 16.5805 6.38091C15.4063 7.59996 14.0919 8.66662 13.557 9.04758" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.62023 3.3333C5.62023 3.3333 4.8204 2.50507 4.1658 2.49996C2.9158 2.4902 2.68471 3.3333 2.68471 3.3333C2.44008 3.84123 2.24438 5.16187 3.41859 6.38091C4.5928 7.59996 5.95603 8.66663 6.49088 9.04758" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.08955 16.6667C7.08955 15.1429 10.0251 14.381 10.0251 14.381C10.0251 14.381 12.9606 15.1429 12.9606 16.6667H7.08955Z" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('Achievements', 'ARMember'); ?></a>
				<a class="arm_general_settings_tab arm_user_badges_tab arm_margin_left_32 <?php echo (in_array($b_action, array('manage_user_achievements'))) ? esc_attr($active) : "";?>" href="<?php echo esc_url( admin_url('admin.php?page=' . $arm_slugs->badges_achievements . '&action=manage_user_achievements') ); ?>"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 10C11.841 10 13.3334 8.50766 13.3334 6.66671C13.3334 4.82576 11.841 3.33337 10 3.33337C8.15907 3.33337 6.66669 4.82576 6.66669 6.66671C6.66669 8.50766 8.15907 10 10 10Z" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.16669 16.6667V15.8333C4.16669 12.6117 6.77836 10 10 10C10.8947 10 11.7423 10.2014 12.5 10.5614" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.8625 13.6792L14.7283 11.8434C14.8395 11.6077 15.1606 11.6077 15.2717 11.8434L16.1376 13.6792L18.0738 13.9755C18.3223 14.0135 18.4213 14.3331 18.2414 14.5164L16.8406 15.9444L17.1712 17.9619C17.2137 18.2209 16.9538 18.4185 16.7315 18.2961L15 17.3431L13.2685 18.2961C13.0462 18.4185 12.7864 18.2209 12.8288 17.9619L13.1594 15.9444L11.7586 14.5164C11.5787 14.3331 11.6778 14.0135 11.9262 13.9755L13.8625 13.6792Z" stroke="#4D5973" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e('User Badges', 'ARMember'); ?></a>
				<div class="armclear"></div>
            </div>			
			<div class="arm_settings_container arm_padding_0">
				<?php 
				$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_badges.php';
				switch ($b_action)
				{
					case 'manage_badges':
						$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_badges.php';
						break;
					case 'manage_achievements':
						$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_achievements.php';
						break;					
					case 'manage_user_achievements':
						$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_user_achievements.php';
						break;
					default:
						$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_badges.php';
						break;
				}
				if (file_exists($file_path)) {
					include($file_path);
				}
                ?>
			</div>
		</div>
		<div class="armclear"></div>
	</div>
</div>
<?php
	echo $ARMember->arm_get_need_help_html_content('manage-user-badges-achievements'); //phpcs:ignore
?>