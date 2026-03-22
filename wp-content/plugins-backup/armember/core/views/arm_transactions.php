<?php global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_email_settings, $arm_manage_coupons,$arm_payment_gateways,$arm_subscription_plans, $arm_pay_per_post_feature;?>

	<?php if($arm_pay_per_post_feature->isPayPerPostFeature && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'paid_post'){ ?>
	<div class="content_wrapper arm_transactions_container" id="content_wrapper">
		<div class="arm_paid_post_transactions_grid_container" id="arm_paid_post_transactions_grid_container">
			<?php 
			if (file_exists(MEMBERSHIP_VIEWS_DIR . '/arm_paid_post_transaction_list_records.php')) {
				include( MEMBERSHIP_VIEWS_DIR.'/arm_paid_post_transaction_list_records.php');
			}
			?>
		</div>
		<?php 
		/* **********./Begin Change Transaction Status Popup/.********** */
		$change_transaction_status_popup_content = '<span class="arm_confirm_text">'.esc_html__("Are you sure you want to change transaction status?",'ARMember' ).'</span>';
		$change_transaction_status_popup_content .= '<input type="hidden" value="" id="pp_log_id"/>';
		$change_transaction_status_popup_content .= '<input type="hidden" value="" id="pp_log_status"/>';
		$change_transaction_status_popup_arg = array(
			'id' => 'change_pp_transaction_status_message',
			'class' => 'arm_delete_bulk_action_message change_transaction_status_message',
            'title' => esc_html__('Change Transaction Status', 'ARMember'),
			'content' => $change_transaction_status_popup_content,
			'button_id' => 'arm_change_transaction_status_ok_btn',
			'button_onclick' => "arm_change_bank_transfer_status_func();",
		);
		echo $arm_global_settings->arm_get_bpopup_html($change_transaction_status_popup_arg); //phpcs:ignore
		/* **********./End Change Transaction Status Popup/.********** */
		/* **********./Begin Bulk Delete Transaction Popup/.********** */
		$bulk_delete_transaction_popup_content = '<span class="arm_confirm_text">'.esc_html__("Are you sure you want to delete this transaction(s)?",'ARMember' ).'</span>';
		$bulk_delete_transaction_popup_content .= '<input type="hidden" value="false" id="bulk_pp_delete_flag"/>';
		$bulk_delete_transaction_popup_arg = array(
			'id' => 'delete_bulk_pp_transactions_message',
			'class' => 'delete_bulk_transactions_message',
            'title' => esc_html__('Delete Transaction(s)', 'ARMember'),
			'content' => $bulk_delete_transaction_popup_content,
			'button_id' => 'arm_bulk_pp_delete_pp_transactions_ok_btn',
			'button_onclick' => "apply_pp_transactions_bulk_action('bulk_pp_delete_flag');",
		);
		echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_transaction_popup_arg); //phpcs:ignore
		/* **********./End Bulk Delete Transaction Popup/.********** */
		?>
	</div>
	
<style type="text/css" title="currentStyle">
		.paginate_page a{display:none;}
		#poststuff #post-body {margin-top: 32px;}
		.arm_status_filter_label, .arm_status_filter_label select{min-width:120px;}
	</style>
	<script type="text/javascript" charset="utf-8">
	// <![CDATA[
	jQuery(window).on("load", function () {
		document.onkeypress = stopEnterKey;
	});
	jQuery(document).on('click', "#armember_datatable_1_wrapper .ColVis_Button:not(.ColVis_MasterButton)", function () {
		var column_list = "";
		var _wpnonce = jQuery('input[name="arm_wp_nonce"]').val();
		var column_list_str = '';
		jQuery('#armember_datatable_1_wrapper .ColVis_Button:not(.ColVis_MasterButton)').each(function(){
			if(jQuery(this).hasClass('active'))
			{
				column_list_str += '1,';
			}
			else {
				column_list_str += '0,';
			}
		});
		column_list_str += '1';
		column_list = [[ column_list_str ]];
		jQuery.ajax({
			type:"POST",
			url:__ARMAJAXURL,
			data:"action=arm_transaction_hide_show_columns&column_list="+column_list+"&_wpnonce="+_wpnonce+"&transaction_history_type=paid_post",
			success: function (msg) {
				return false;
			}
		});
	});
	// ]]>
	</script>
<?php } ?>