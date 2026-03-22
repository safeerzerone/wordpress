<?php
global $wpdb, $ARMember;

$form_mode = esc_html__("Add Userwise Private Content", 'ARMember');
$action = 'add_private_content';
$edit_mode = 0;

$member_id = "";
$private_content = "";
$enable_private_content = "1";
$member_login_name = '';

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();
$user_table = $ARMember->tbl_arm_members;

$get_all_armembers = $wpdb->get_results("SELECT arm_member_id,arm_user_id,arm_user_login FROM {$user_table}", ARRAY_A);//phpcs:ignore --Reason $user_table is a table name without where query

?>

<div class="wrap arm_page arm_add_edit_private_content_main_wrapper armPageContainer popup_wrapper">
	<div class="content_wrapper arm_private_content_wrapper" id="content_wrapper">
		
		<div class="page_title arm_add_private_content_title">
			<?php esc_html_e("Add Userwise Private Content", 'ARMember');?>
			<a class="arm_popup_close_btn arm_upc_cancel_btn" href="javascript:void(0)"></a>
		</div>

		<div class="page_title arm_edit_private_content_title hidden_section">
			<?php esc_html_e("Edit Userwise Private Content", 'ARMember');?>
			<a class="arm_popup_close_btn arm_upc_cancel_btn" href="javascript:void(0)"></a>
		</div>
		<div class="arm_add_edit_private_content_text">
			<form  method="post" id="arm_add_edit_private_content_form" class="arm_add_edit_private_content_form arm_admin_form arm_margin_bottom_60">
				<div class="arm_form_main_content arm_padding_40">
					<input type="hidden" name="id" id="arm_add_edit_private_content_id" value="<?php echo esc_attr($member_id); ?>" />
					<input type="hidden" name="arm_action" id="arm_private_content_action" value="<?php echo esc_attr($action) ?>" />
					<input type="hidden" name="enable_private_content" id="arm_private_content_status_input" value="<?php echo esc_attr($enable_private_content); ?>" />
					<div class="arm_admin_form_content">
						<table class="form-table arm_user_private_content_row">
							<tr class="form-field form-required arm_width_50_pct">
								<th class="arm_padding_0">
									<label for="user_name"><?php echo sprintf(esc_html__('Select User%ss%s', 'ARMember'), "(",")"); //phpcs:ignore?></label>
								</th>
								<td class="arm_required_member_wrapper">                  	
									<div class="arm_edit_private_content_section hidden_section">
										<strong class="arm_user_name_section"><?php echo esc_html($member_login_name); ?></strong>
										<input type="hidden" name="arm_member_input_hidden" value="<?php echo esc_attr($member_id); ?>">
										
									</div>
									<div class="arm_add_private_content_section">
										<input type="hidden" id="arm_member_item_type" class="arm_rule_item_type_input" name="arm_member_input_hidden" data-type="" value=""/>
		
										<input id="arm_member_items_input" type="text" value="" placeholder="<?php esc_html_e('Search by username or email...', 'ARMember');?>" data-msg-required="<?php esc_html_e('Please select atleast one member.', 'ARMember');?>" class="arm_width_100_pct arm_max_width_100_pct">
										<div class="arm_private_content_items arm_required_wrapper arm_display_block" id="arm_private_content_items" style="display: none;"></div>
		
									</div>
									<span class="arm_required_member_err"></span>
								</td>
							</tr>
							<tr class="form-field arm_user_private_content_row">
								<th class="arm_padding_0">
									<label for="private_content"><?php esc_html_e('Private Content', 'ARMember'); ?></label>
								</th>
								<td class="arm_padding_bottom_0">
									
									<div class="arm_private_content_editor">
									<?php 
										$arm_message_editor = array('textarea_name' => 'arm_private_content',
											'editor_class' => 'arm_private_content',
											'media_buttons' => true,
											'textarea_rows' => 15,
											/*'default_editor' => 'html',*/
											'editor_css' => '<style type="text/css"> body#tinymce{margin:0px !important;} </style>'
										);
										wp_editor($private_content, 'arm_private_content', $arm_message_editor);
		
									?>
									<span id="arm_comm_wp_validate_msg" class="error" style="display:none;"><?php esc_html_e('Content Cannot Be Empty.', 'ARMember');?></span>
									</div>
								</td>
							</tr>
							
						</table>
		
		
						<div class="arm_submit_btn_container">
							<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_loader_img_add_member_plan_loader" class="arm_loader_img arm_submit_btn_loader"  style=" display: none;" width="20" height="20" style="position:absolute;top:20px;left:79%"/>
							<a class="arm_cancel_btn arm_upc_cancel_btn" href="javascript:void(0)"><?php esc_html_e('Close', 'ARMember'); ?></a>
							<button class="arm_save_btn arm_upc_save_btn" type="submit"><?php esc_html_e('Save', 'ARMember') ?></button>
						</div>
						<div class="armclear"></div>
					</div>
					<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
					<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
				</div>
			 </form>
		</div>
        <div class="armclear"></div>
    </div>
</div>

<script type="text/javascript">

	jQuery(document).ready( function ($) {
		jQuery(document).on('click', '.arm_remove_selected_itembox', function () {
			jQuery(this).parents('.arm_private_content_itembox').remove();
			if(jQuery('#arm_private_content_items .arm_private_content_itembox').length == 0) {
				jQuery('#arm_member_items_input').attr('required', 'required');
				jQuery('#arm_private_content_items').hide();
			}
			return false;
		});


		if (jQuery.isFunction(jQuery().autocomplete))
		{
			if(jQuery("#arm_member_items_input").length > 0){
				jQuery('#arm_member_items_input').autocomplete({
					minLength: 0,
					delay: 500,
					appendTo: ".arm_private_content_main_wrapper",
					source: function (request, response) {
						var post_type = jQuery('#arm_member_item_type').val();
						var _wpnonce = jQuery('input[name="arm_wp_nonce"]').val();
						jQuery.ajax({
							type: "POST",
							url: ajaxurl,
							dataType: 'json',
							data: "action=get_member_list&txt="+request.term + "&_wpnonce=" + _wpnonce,
							beforeSend: function () {},
							success: function (res) {
								response(res.data);
							}
						});
					},
					focus: function() {return false;},
					select: function(event, ui) {
						var itemData = ui.item;
						jQuery("#arm_member_items_input").val('');
						if(jQuery('#arm_private_content_items .arm_private_content_itembox_'+itemData.id).length > 0) {
						} else {
							var itemHtml = '<div class="arm_private_content_itembox arm_private_content_itembox_'+itemData.id+'">';
							itemHtml += '<input type="hidden" name="arm_member_input_hidden['+itemData.id+']" value="'+itemData.id+'"/>';
							itemHtml += '<label>'+itemData.label+'<span class="arm_remove_selected_itembox"><img src="<?php echo MEMBERSHIP_IMAGES_URL; //pgpcs:ignore?>/cancel_icon_white.png"></span></label>';
							itemHtml += '</div>';
							jQuery("#arm_private_content_items").append(itemHtml);
							jQuery('#arm_member_items_input').removeAttr('required');
							if(jQuery("#arm_private_content_items_input_error").length > 0){
								jQuery("#arm_private_content_items_input_error").remove();
							}
						}
						jQuery('#arm_private_content_items').show();
						return false;
					},
				}).data('uiAutocomplete')._renderItem = function (ul, item) {
					var itemClass = 'ui-menu-item';
					if(jQuery('#arm_private_content_items .arm_private_content_itembox_'+item.id).length > 0) {
						itemClass += ' ui-menu-item-selected';
					}
					var itemHtml = '<li class="'+itemClass+'" data-value="'+item.value+'" data-id="'+item.id+'" ><a>' + item.label + '</a></li>';
					return jQuery(itemHtml).appendTo(ul);
				};
			}
		}
	});

</script>
<?php
	echo $ARMember->arm_get_need_help_html_content('users-private-content-add'); //phpcs:ignore
?>