<?php 
global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_email_settings;
$all_email_settings = $arm_email_settings->arm_get_all_email_settings();
?>
<div class="arm_global_settings_main_wrapper">
	<div class="page_sub_content">
	<div class="page_sub_title arm_margin_bottom_32">
			<?php esc_html_e('Opt-ins Configuration','ARMember');?>
		</div>
		<form  method="post" action="#" id="arm_opt_ins_options" class="arm_opt_ins_options arm_admin_form" onsubmit="return false;">
			<?php $emailTools = (!empty($all_email_settings['arm_email_tools'])) ? $all_email_settings['arm_email_tools'] : array(); 
			
			$wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
			<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
			<?php 

            do_action('arm_add_new_optins');
                        
			$customEmailTools = apply_filters('arm_add_new_optin_settings', '', $emailTools);
			echo $customEmailTools; //phpcs:ignore
			$optins_status = 0;
			$is_optins_active = apply_filters('arm_check_optin_status_external', $optins_status);
			if(!empty($emailTools) || $is_optins_active == 1){
			?>
			
			<div class="arm_submit_btn_container arm_apply_changes_btn_container">
				<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif'; //phpcs:ignore?>" id="arm_loader_img" class="arm_submit_btn_loader" style="display:none;" width="24" height="24" />&nbsp;<button class="arm_save_btn arm_opt_ins_options_btn" type="submit" id="arm_opt_ins_options_btn" name="arm_opt_ins_options_btn"><?php esc_html_e('Apply Changes', 'ARMember') ?></button>
			</div>
			<?php
			}
			?>
		</form>
		<div class="armclear"></div>
	</div>
</div>
<script type="text/javascript">
	arm_membership_optins_continue_flag = 0;
	arm_optins_continue_flag = 0;
</script>
