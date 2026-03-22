<?php 
global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_email_settings, $arm_social_feature, $arm_slugs, $arm_subscription_plans, $arm_manage_communication;

$arm_all_email_settings = $arm_email_settings->arm_get_all_email_settings();
$template_list = $arm_email_settings->arm_get_all_email_template();
$messages = $wpdb->get_results("SELECT * FROM `".$ARMember->tbl_arm_auto_message."` ORDER BY `arm_message_id` DESC"); //phpcs:ignore --Reason $ARMember->tbl_arm_auto_message is a table name and query is without where so need to skip

$get_page = !empty($_GET['page']) ? sanitize_text_field( $_GET['page'] ) : '';

$form_id = 'arm_add_message_wrapper_frm';
$mid = 0;
$edit_mode = false;
$msg_type = 'on_new_subscription';

?>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
var current_language = '<?php echo !empty($local) ? $local : ''; //phpcs:ignore?>';

function arm_load_communication_list_filtered_grid(data)
{
    var tbl = jQuery('#armember_datatable').dataTable(); 
	tbl.fnDeleteRow(data);
	
	jQuery('#armember_datatable').dataTable().fnDestroy();
	arm_load_communication_messages_list_grid();
}

function arm_load_communication_messages_list_grid() {
	
	jQuery('#armember_datatable').dataTable({
		"sDom": '<"H"Cfr>t<"footer"ipl>',
		"sPaginationType": "four_button",
		"oLanguage": {
			"sEmptyTable": "No any automated email message found.",
			"sZeroRecords": "No matching records found."
			},
		"bJQueryUI": true,
		"bPaginate": true,
		"bAutoWidth": false,
		"aaSorting": [],
		"aoColumnDefs": [
			{"bVisible": false, "aTargets": []},
			{"bSortable": false, "aTargets": [0, 2, 5]}
		],
		"language":{
            "searchPlaceholder": "Search",
            "search":"",
        },
		"oColVis": {
			"aiExclude": [0, 5]
		},
		"fnDrawCallback": function () {
			jQuery("#cb-select-all-1").prop("checked", false);
		},
	});
        
	var filter_box = jQuery('#arm_filter_wrapper_after_filter').html();
         
	jQuery('div#armember_datatable_filter').parent().append(filter_box);
	jQuery('#arm_filter_wrapper').remove();
}

// ]]>
</script>
<div class="arm_email_notifications_main_wrapper arm_advanced_email_notifications_main_wrapper">
	
		<div class="page_sub_content arm_padding_top_24">
			<div class="page_sub_title" style="float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" ><?php esc_html_e('Automated Email Messages','ARMember');?></div>
			<div class="armclear"></div>
			<div class="arm_email_templates_list">
				<form method="GET" id="communication_list_form" class="data_grid_list arm_email_settings_wrapper">
					<input type="hidden" name="page" value="<?php echo esc_attr( $get_page ); ?>" />
					<input type="hidden" name="armaction" value="list" />
					<div id="armmainformnewlist">
						<div class="divTable arm_email_template_table">
							<div class="divTableHeading">
								<div class="divTableRow divTableRowheader arm_email_template_table">
									<div class="divTableHead arm_padding_left_32 arm_min_width_140"><?php esc_html_e('Active/Inactive', 'ARMember');?></div>
									<div class="divTableHead"><?php esc_html_e('Message Subject', 'ARMember');?></div>
									<div class="divTableHead"><?php esc_html_e('Subscription', 'ARMember');?></div>
									<div class="divTableHead"><?php esc_html_e('Type', 'ARMember');?></div>
								</div>
							</div>
							<div class="divTableBody">
							<?php if(!empty($messages)){?>
							<?php 
								foreach ($messages as $key => $rc) {
									$messageID = $rc->arm_message_id;
									$edit_link = admin_url('admin.php?page=' . $arm_slugs->email_notifications . '&action=edit_communication&message_id=' . $messageID);
									?>
									<div class="divTableRow arm_message_tr_<?php echo esc_attr($messageID);?> member_row_<?php echo esc_attr($messageID);?> arm_email_template_table" onmouseover="arm_datatable_row_hover('member_row_<?php echo intval($messageID); ?>','hovered')" onmouseleave="arm_datatable_row_hover('member_row_<?php echo intval($messageID); ?>');">
										<div class="divTableCell"><?php 
											$switchChecked = ($rc->arm_message_status == '1') ? 'checked="checked"' : '';
											echo '<div class="armswitch">
												<input type="checkbox" class="armswitch_input arm_communication_status_action" id="arm_communication_status_input_'.esc_attr($messageID).'" value="1" data-item_id="'.esc_attr($messageID).'" '.esc_attr($switchChecked).'>
												<label class="armswitch_label" for="arm_communication_status_input_'.esc_attr($messageID).'"></label>
												<span class="arm_status_loader_img arm_right_30"></span>
											</div>'; //phpcs:ignore
										?></div>
										<div class="divTableCell"><?php echo esc_html(stripslashes($rc->arm_message_subject));?></div>
										<?php
										$subs_plan_title = '';
										if(!empty($rc->arm_message_subscription)){
											$plans_id = @explode(',', $rc->arm_message_subscription);
											$subs_plan_title = $arm_subscription_plans->arm_get_comma_plan_names_by_ids($plans_id);
								$subs_plan_title = (!empty($subs_plan_title)) ? stripslashes_deep($subs_plan_title) : '--';
										} else {
											$subs_plan_title = esc_html__('All Membership Plans', 'ARMember');
										}
										?>
										<div class="divTableCell"><?php echo esc_html($subs_plan_title);?></div>
										<div class="divTableCell"><?php 
										$msge_type = '';
										switch ($rc->arm_message_type)
										{
											case 'on_new_subscription':
												$msge_type = esc_html__('On New Subscription', 'ARMember');
												break;
																			case 'on_menual_activation':
												$msge_type = esc_html__('On Manual User Activation', 'ARMember');
												break;
											case 'on_change_subscription':
												$msge_type = esc_html__('On Change Subscription', 'ARMember');
												break;
											case 'on_renew_subscription':
												$msge_type = esc_html__('On Renew Subscription', 'ARMember');
												break;
											case 'on_failed':
												$msge_type = esc_html__('On Failed Payment', 'ARMember');
												break;
																			case 'on_next_payment_failed':
												$msge_type = esc_html__('On Semi Automatic Subscription Failed Payment', 'ARMember');
												break;
											case 'trial_finished':
												$msge_type = esc_html__('Trial Finished', 'ARMember');
												break;
											case 'on_expire':
												$msge_type = esc_html__('On Membership Expired', 'ARMember');
												break;
											case 'before_expire':
												$msge_per_unit = $rc->arm_message_period_unit;
												$msge_per_type = $rc->arm_message_period_type;
												$msge_type = $msge_per_unit . ' ' . $msge_per_type . '(s) ' . esc_html__('Before Membership Expired', 'ARMember');
												break;
											case 'manual_subscription_reminder':
													$msge_per_unit = $rc->arm_message_period_unit;
												$msge_per_type = $rc->arm_message_period_type;
												$msge_type = esc_html__('Semi Automatic Subscription Payment due', 'ARMember');
												$msge_type.= "(BeFore ".$msge_per_unit . ' ' . $msge_per_type . "(s))";
												break;
											case 'automatic_subscription_reminder':
													$msge_per_unit = $rc->arm_message_period_unit;
												$msge_per_type = $rc->arm_message_period_type;
												$msge_type = esc_html__('Automatic Subscription Payment due', 'ARMember');
												$msge_type.= "(BeFore ".$msge_per_unit . ' ' . $msge_per_type . "(s))";
												break;
											case 'on_change_subscription_by_admin':
													$msge_type = esc_html__('On Change Subscription By Admin', 'ARMember');
												break;
											case 'before_dripped_content_available':
													$msge_per_unit = $rc->arm_message_period_unit;
												$msge_per_type = $rc->arm_message_period_type;
												$msge_type = $msge_per_unit . ' ' . $msge_per_type . '(s) ' . esc_html__('Before Dripped Content Available', 'ARMember');
												break;
											case 'on_cancel_subscription':
												$msge_type = esc_html__('On Cancel Membership', 'ARMember');
												break;
											case 'on_recurring_subscription':
												$msge_type = esc_html__('On Recurring Subscription', 'ARMember');
												break;
											case 'on_close_account':
												$msge_type = esc_html__('On Close User Account', 'ARMember');
												break;
											case 'on_login_account':
												$msge_type = esc_html__('On User Login', 'ARMember');
												break;
											case 'on_new_subscription_post':
												$msge_type = esc_html__('On new paid post purchase', 'ARMember');
												break;	
											case 'on_recurring_subscription_post':
												$msge_type = esc_html__('On recurring paid post purchase', 'ARMember');
												break;
											case 'on_renew_subscription_post':
												$msge_type = esc_html__('On renew paid post purchase', 'ARMember');
												break;
											case 'on_cancel_subscription_post':
												$msge_type = esc_html__('On cancel paid post', 'ARMember');
												break;
											case 'before_expire_post':
												$msge_type = esc_html__('Before paid post expire', 'ARMember');
												break;
											case 'on_expire_post':
												$msge_type = esc_html__('On Expire paid post', 'ARMember');
												break;
											case 'on_purchase_subscription_bank_transfer':
												$msge_type = esc_html__('On Purchase membership plan using Bank Transfer', 'ARMember');
												break;
											default:
												$msge_type = apply_filters('arm_notification_get_list_msg_type',$rc->arm_message_type);
												break;
										}
										echo $msge_type; //phpcs:ignore
										?></div>
										<div class="divTableCell arm_grid_action_wrapper hidden_section"><?php
											
											$gridAction = "<div class='arm_grid_action_btn_container'>";

											$gridAction .= "<a class='arm_edit_message_btn pro arm_margin_right_5 armhelptip tipso_style' title='".esc_attr__('Edit Message','ARMember')."' href='javascript:void(0);' data-message_id='".$messageID."'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M13.2594 3.60022L5.04936 12.2902C4.73936 12.6202 4.43936 13.2702 4.37936 13.7202L4.00936 16.9602C3.87936 18.1302 4.71936 18.9302 5.87936 18.7302L9.09936 18.1802C9.54936 18.1002 10.1794 17.7702 10.4894 17.4302L18.6994 8.74022C20.1194 7.24022 20.7594 5.53022 18.5494 3.44022C16.3494 1.37022 14.6794 2.10022 13.2594 3.60022Z' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M11.8906 5.0498C12.3206 7.8098 14.5606 9.9198 17.3406 10.1998' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M3 22H21' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/></svg></a>";

											$gridAction .= "<a class='arm_test_mail_btn_pro arm_margin_right_5 armhelptip tipso_style' title='".esc_attr__('Send Test Mail','ARMember')."' href='javascript:void(0);' data-message_id='".$messageID."'><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'><path d='M7 19C4 19 2 17.5 2 14V7C2 3.5 4 2 7 2H17C20 2 22 3.5 22 7V11' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M17 6L12.9032 8.7338C12.3712 9.08873 11.6288 9.08873 11.0968 8.7338L7 6' stroke='#617191' stroke-width='1.5' stroke-linecap='round'/><path d='M19.8942 18.0232C20.1376 16.8612 19.9704 15.6089 19.3301 14.4998C17.9494 12.1083 14.8915 11.289 12.5 12.6697C10.1085 14.0504 9.28916 17.1083 10.6699 19.4998C11.8597 21.5606 14.2948 22.454 16.4758 21.7782' stroke='#617191' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/><path d='M19.988 20.1047C19.7581 20.4134 19.2802 20.3574 19.1279 20.0039L17.7572 16.8233C17.6049 16.4699 17.8923 16.084 18.2746 16.1288L21.7144 16.5321C22.0967 16.5769 22.2871 17.0187 22.0571 17.3274L19.988 20.1047Z' fill='#617191'/></svg></a>"; 

											$gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$messageID});' class='arm_grid_delete_action armhelptip tipso_style' title='".esc_attr__('Delete','ARMember')."'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M3 5.33333H21M16.5 5.33333L16.1956 4.43119C15.9005 3.55694 15.7529 3.11982 15.4793 2.79664C15.2376 2.51126 14.9274 2.29036 14.5768 2.1542C14.1798 2 13.7134 2 12.7803 2H11.2197C10.2866 2 9.8202 2 9.4232 2.1542C9.07266 2.29036 8.76234 2.51126 8.5207 2.79664C8.24706 3.11982 8.09954 3.55694 7.80447 4.43119L7.5 5.33333M18.75 5.33333V16.6667C18.75 18.5336 18.75 19.4669 18.3821 20.18C18.0586 20.8072 17.5423 21.3171 16.9072 21.6367C16.1852 22 15.2402 22 13.35 22H10.65C8.75982 22 7.81473 22 7.09278 21.6367C6.45773 21.3171 5.94143 20.8072 5.61785 20.18C5.25 19.4669 5.25 18.5336 5.25 16.6667V5.33333M14.25 9.77778V17.5556M9.75 9.77778V17.5556' stroke='#617191' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/></svg></a>";
											$gridAction .= $arm_global_settings->arm_get_confirm_box($messageID, esc_html__("Are you sure you want to delete this message?", 'ARMember'), 'arm_communication_delete_btn','',esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));
											$gridAction .= "</div>";
											echo '<div class="arm_grid_action_wrapper">'.$gridAction.'</div>'; //phpcs:ignore
										?></div>
									</div>
									<?php } ?>  
								<?php }
								else{?>
								<div class="divTableRow arm_margin_top_24 no_record_row"  id="arm_empty_message_template">
										<div class="divTableCell arm_width_100_pct"><center><?php esc_html_e('No available Advanced Email notifications','ARMember')?></center></div>
									</div>
								<?php }?>
							</div>
						</div>
						<div class="armclear"></div>
						<input type="hidden" name="search_grid" id="automated_search_grid" value="<?php esc_html_e('Search', 'ARMember');?>"/>
						<input type="hidden" name="entries_grid" id="automated_entries_grid" value="<?php esc_html_e('messages', 'ARMember');?>"/>
						<input type="hidden" name="show_grid" id="automated_show_grid" value="<?php esc_html_e('Show', 'ARMember');?>"/>
						<input type="hidden" name="showing_grid" id="automated_showing_grid" value="<?php esc_html_e('Showing', 'ARMember');?>"/>
						<input type="hidden" name="to_grid" id="automated_to_grid" value="<?php esc_html_e('to', 'ARMember');?>"/>
						<input type="hidden" name="of_grid" id="automated_of_grid" value="<?php esc_html_e('of', 'ARMember');?>"/>
						<input type="hidden" name="no_match_record_grid" id="automated_no_match_record_grid" value="<?php esc_html_e('No matching messages found', 'ARMember');?>"/>
						<input type="hidden" name="no_record_grid" id="automated_no_record_grid" value="<?php esc_html_e('There is no any communication message found.', 'ARMember');?>"/>
						<input type="hidden" name="filter_grid" id="automated_filter_grid" value="<?php esc_html_e('filtered from', 'ARMember');?>"/>
						<input type="hidden" name="totalwd_grid" id="automated_totalwd_grid" value="<?php esc_html_e('total', 'ARMember');?>"/>
						<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
						<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
					</div>
					<div class="footer_grid"></div>
				</form>
			</div>
			<div class="armclear"></div>
			<?php 
			/* **********./Begin Bulk Delete Communication Popup/.********** */
			$bulk_delete_message_popup_content = '<span class="arm_confirm_text">'.esc_html__("Are you sure you want to delete this message(s)?",'ARMember' ).'</span>';
			$bulk_delete_message_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
			$bulk_delete_message_popup_arg = array(
				'id' => 'delete_bulk_communication_message',
				'class' => 'delete_bulk_communication_message',
				'title' => 'Delete Communication Message(s)',
				'content' => $bulk_delete_message_popup_content,
				'button_id' => 'arm_bulk_delete_message_ok_btn',
				'button_onclick' => "arm_delete_bulk_communication('true');",
			);
			echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_message_popup_arg); //phpcs:ignore
			/* **********./End Bulk Delete Communication Popup/.********** */
			?>
			<div class="armclear"></div>
		</div>	
</div>
<!--./******************** Add New Member Form ********************/.-->
<?php 

	$arm_add_new_response_email = '';
	echo apply_filters('arm_pro_email_notification_automated_notification_form',$arm_add_new_response_email); //phpcs:ignore
?>
<script type="text/javascript">
	__ARM_ADDNEWRESPONSE = '<?php esc_html_e( 'Add New Response', 'ARMember' ); ?>';
	__ARM_VALUE = '<?php esc_html_e( 'Value', 'ARMember' ); ?>';
</script>