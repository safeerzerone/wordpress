<?php
global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_access_rules, $arm_subscription_plans, $arm_drip_rules,$arm_common_lite;
$dripRulesMembers = array();
$all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name');
$date_format = $arm_global_settings->arm_get_wp_date_format();
$drip_types = $arm_drip_rules->arm_drip_rule_types();

$filter_search = (!empty($_REQUEST['sSearch'])) ? sanitize_text_field($_REQUEST['sSearch']) : '';//phpcs:ignore
$filter_dctype = (!empty($_POST['dctype'])) ? sanitize_text_field($_POST['dctype']) : '0';//phpcs:ignore
$filter_plan_id = (!empty($_POST['plan_id']) && $_POST['plan_id'] != '0') ? intval($_POST['plan_id']) : '';//phpcs:ignore
$filter_drip_type = (!empty($_POST['drip_type']) && $_POST['drip_type'] != '0') ? sanitize_text_field($_POST['drip_type']) : '0';//phpcs:ignore

/* Custom Post Types */
$custom_post_types = get_post_types(array('public' => true, '_builtin' => false, 'show_ui' => true), 'objects');
$dripContentTypes = array('page' => esc_html__('Page', 'ARMember'), 'post' => esc_html__('Post', 'ARMember'));
if (!empty($custom_post_types)) {
	foreach ($custom_post_types as $cpt) {
		$dripContentTypes[$cpt->name] = $cpt->label;
	}
}
/* Add `Custom Content` Option */
$dripContentTypes['custom_content'] = esc_html__('Custom Content', 'ARMember');
?>
<script type="text/javascript">
// <![CDATA[
jQuery(document).ready( function () {
   	arm_load_drip_rules_list_grid(false);
	arm_tooltip_init();
	var count_checkbox = jQuery('.chkstanard:checked').length;
	if(count_checkbox > 0)
	{
		jQuery('.arm_bulk_action_section').removeClass('hidden_section');
	}
	else{
		jQuery('.arm_bulk_action_section').addClass('hidden_section');
	}
});

jQuery(document).on('change','.chkstanard',function()
{
	var count_checkbox = jQuery('.chkstanard:not(#cb-select-all-1):checked').length;
	var total_checkbox = jQuery('.chkstanard:not(#cb-select-all-1)').length;
	if(count_checkbox > 0)
	{
		jQuery('.arm_selected_chkcount').html(count_checkbox);
		jQuery('.arm_selected_chkcount_total').html(total_checkbox);	
		jQuery('.arm_bulk_action_section').removeClass('hidden_section').show();
	}
	else{	
		jQuery('.arm_bulk_action_section').addClass('hidden_section').hide();
	}
});

jQuery(document).on('click','.arm_reset_bulk_action',function(){
	jQuery('.chkstanard:checked').each(function(){
		jQuery(this).prop('checked',false).trigger('change');
	})
});

jQuery(document).on('keyup','.arm_datatable_searchbox #armmanagesearch_new_drip', function (e) {
	//e.stopPropagation();
	var arm_search_val = jQuery(this).val();
	jQuery('.arm_datatable_searchbox #armmanagesearch_new_drip:last-child').val(arm_search_val);	
	if (e.keyCode == 13 || 'Enter' == e.key) {
		arm_load_drip_rules_list_filtered_grid();
		jQuery('#armember_datatable').dataTable().fnDestroy();
		arm_load_drip_rules_list_grid(true,arm_search_val);
		return false;
	}
});

function arm_reset_drip_grid_filter(){
	hideConfirmBoxCallback_filter('manage_drip_filter');
}

function arm_load_drip_rules_list_filtered_grid()
{
	jQuery('.arm_reset_bulk_action').trigger('click');
	var is_filtered = 0;
	var is_before_filtered = 0;
	hideConfirmBoxCallback_close_filter('manage_drip_filter');
	if(!jQuery('.arm_membership_drip_filters_items').hasClass('hidden_section'))
	{
		is_before_filtered = 1;
	}
	else{
		is_before_filtered = 0;
	}
	jQuery('.arm_membership_drip_filters_items').removeClass('hidden_section');

	var drip_content_type_val = jQuery('#arm_filter_dctype').val();
	if(drip_content_type_val != '0')
	{
		var drip_content_type_html = jQuery('ul[data-id="arm_filter_dctype"] li[data-value="'+drip_content_type_val+'"]').attr('data-label')
		jQuery('.arm_drip_content_filter_value').html(drip_content_type_html);
		jQuery('.arm_drip_content_filters:first-child').removeClass('hidden_section');
	}
	else{
		var drip_content_type_html = jQuery('ul[data-id="arm_filter_dctype"] li[data-value="0"]').attr('data-label');
		jQuery('.arm_drip_content_filter_value').html(drip_content_type_html);
		jQuery('.arm_drip_content_filters:first-child').addClass('hidden_section');
	}

	var drip_type_val = jQuery('input#arm_filter_drip_type').val();
	if(drip_type_val != '0')
	{
		var drip_type_html = jQuery('ul[data-id="arm_filter_drip_type"] li[data-value="'+drip_type_val+'"]').attr('data-label');
		jQuery('.arm_drip_type_filter_value').html(drip_type_html);
		jQuery('.arm_drip_type_filters').removeClass('hidden_section');
	}
	else{
		var drip_type_html = jQuery('ul[data-id="arm_filter_drip_type"] li[data-value="0"]').attr('data-label');
		jQuery('.arm_drip_type_filter_value').html(drip_type_html);
		jQuery('.arm_drip_type_filters').addClass('hidden_section');
	}

	var chk_count = 0;
	var arm_selected_plan = jQuery('.arm_filter_plans_box').find('#arm_filter_dplan_id').val();
	if(arm_selected_plan != '')
	{
		var arm_plans = arm_selected_plan.split(',');
		chk_count = arm_plans.length;
	}	
	if(chk_count > 0)
	{
		jQuery('.arm_drip_plan_filter_value').html('');
		let arm_plan_label = '';
		let arm_selected_plan_labels = [];
		var first_selected_plan_lbl = jQuery('.arm_filter_plans_box').find('.arm_icheckbox:checked:first').parent().attr('data-label');
	
		jQuery('.arm_filter_plans_box .arm_icheckbox').each(function(){
			if(jQuery(this).prop('checked'))
			{
				var plan_id = jQuery(this).val();
				var plan_label = jQuery('.arm_filter_plans_box').find('li[data-value="'+plan_id+'"]').attr('data-label');
				arm_selected_plan_labels.push(plan_label);
			}
		});
		if(chk_count > 1)
		{
			first_selected_plan_lbl += '...';
		}
		var arm_plan_label_temp = '';
		if(typeof arm_selected_plan_labels != 'undefined')
		{
			arm_selected_plan_labels.forEach(
				function(plan_label) {
					arm_plan_label_temp += plan_label+',</br>';
				}
			);
			arm_plan_label = arm_plan_label_temp;
			arm_selected_plan_labels = [];
			arm_plan_label_temp = '';
		}
		jQuery('.arm_plan_tp').removeClass('hidden_section');
		jQuery('.arm_drip_plan_filter_value_tooltip').html(arm_plan_label);
		first_selected_plan_lbl = first_selected_plan_lbl != '' ? first_selected_plan_lbl : jQuery('.arm_filter_plans_box').find('ul[data-id="arm_filter_dplan_id"]').attr('data-placeholder');
		jQuery('.arm_drip_plan_filter_value').html(first_selected_plan_lbl);
		jQuery('.arm_drip_plan_filters').removeClass('hidden_section')
	}
	else{
		var first_selected_plan_lbl = jQuery('.arm_filter_plans_box').find('ul[data-id="arm_filter_dplan_id"]').attr('data-placeholder');
		jQuery('.arm_drip_plan_filter_value').html(first_selected_plan_lbl);
		jQuery('.arm_drip_plan_filter_value_tooltip').html('');
		jQuery('.arm_plan_tp').addClass('hidden_section');
		jQuery('.arm_drip_plan_filters').addClass('hidden_section')
	}
	if(drip_content_type_val != '0' || drip_type_val !='0' || chk_count > 0){
		is_filtered = 1;
	}
	else{
		is_filtered = 0;
	}
	if(drip_content_type_val == '0' && drip_type_val =='0' && chk_count == 0){
		jQuery('.arm_membership_drip_filters_items').addClass('hidden_section');
	}
	var search = jQuery('.arm_datatable_searchbox #armmanagesearch_new_drip:last-child').val();	
	jQuery('#armember_datatable').dataTable().fnDestroy();
	arm_load_drip_rules_list_grid(true,search);
	
}
function arm_load_drip_rules_list_grid(is_filtered,filter_search="") {


	var ajax_url = '<?php echo esc_url(admin_url("admin-ajax.php"));?>';
	var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMember')); //phpcs:ignore?>';
    var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing','ARMember').' <span class="arm-black-350 arm_font_size_15">0</span> - <span class="arm-black-350 arm_font_size_15">0</span> of <span class="arm-black-350 arm_font_size_15">0</span> '.esc_html__('rules','ARMember')); //phpcs:ignore?>';
    var __ARM_to = '<?php echo addslashes(esc_html__('to','ARMember')); //phpcs:ignore?>';
    var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMember')); //phpcs:ignore?>';
    var __ARM_RECORDS = '<?php echo addslashes(esc_html__('Rules','ARMember')); //phpcs:ignore?>';
    var __ARM_Show = '<?php echo addslashes(esc_html__('Show','ARMember')); //phpcs:ignore?>';
    var __ARM_NO_FOUND = '<?php echo addslashes(esc_html__('No any record found.','ARMember')); //phpcs:ignore?>';
    var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching records found.','ARMember')); //phpcs:ignore?>';
	var filtered_data = (typeof is_filtered !== 'undefined' && is_filtered !== false) ? true : false;
	// var filter_search = jQuery('#armmanagesearch_new_drip').val();
	var filter_plan_id = jQuery('#arm_filter_dplan_id').val();
	var filter_drip_type = jQuery('#arm_filter_drip_type').val();
	var filter_dctype = jQuery('#arm_filter_dctype').val();
	var _wpnonce = jQuery('input[name="arm_wp_nonce"]').val();

	
	var oTables = jQuery('#armember_datatable').dataTable({
            "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sInfo": __ARM_Showing + " <span class='arm-black-350 arm_font_size_15'>_START_</span> " + __ARM_to + " <span class='arm-black-350 arm_font_size_15'>_END_</span> " + __ARM_of + " <span class='arm-black-350 arm_font_size_15'>_TOTAL_</span> " + __ARM_RECORDS,
                "sInfoEmpty": __ARM_Showing_empty,
               
                "sLengthMenu": __ARM_Show + "_MENU_",
                "sEmptyTable": __ARM_NO_FOUND,
                "sZeroRecords": __ARM_NO_MATCHING,
            },
            "bDestroy": true,
            "buttons":[],
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_filter_drip_rules_list'});
                aoData.push({'name': 'plan_id', 'value': filter_plan_id});
                aoData.push({'name': 'drip_type', 'value': filter_drip_type});
                aoData.push({'name': 'dctype','value': filter_dctype});
				aoData.push({'name': 'sSearch', 'value': filter_search});
                aoData.push({'name': 'sColumns', 'value':null});
                aoData.push({'name': '_wpnonce', 'value': _wpnonce});
            },		
            "bRetrieve": false,
            "sDom": '<"H"CBfr>t<"footer"ipl>',
            "sPaginationType": "four_button",
            "bJQueryUI": true,
            "bPaginate": true,
            "bAutoWidth": false,
            "sScrollX": "100%",
            "bScrollCollapse": true,
            "aoColumnDefs": [
				{ "sType": "html", "bVisible": false, "aTargets": [] },
				{"sClass": "center arm_min_width_30", "aTargets": [0]},
                {"bSortable": false, "aTargets": [ 0, 1, 2, 3, 4, 5, 6, 7]},
				{"sWidth": "10%", "aTargets": [1]},
				{"sWidth": "15%", "aTargets": [6,2]},
				{"sWidth": "20%", "aTargets": [3]},
				{"sWidth": "20%", "aTargets": [4,5]},
            ],
            "fixedColumns": false,
            "bStateSave": true,
            "iCookieDuration": 60 * 60,
            "sCookiePrefix": "arm_datatable_",
            "aLengthMenu": [10, 25, 50, 100, 150, 200],
            "fnStateSave": function (oSettings, oData) {
                oData.aaSorting = [];
                oData.abVisCols = [];
                oData.aoSearchCols = [];
                this.oApi._fnCreateCookie(
                    oSettings.sCookiePrefix + oSettings.sInstance,
                    this.oApi._fnJsonString(oData),
                    oSettings.iCookieDuration,
                    oSettings.sCookiePrefix,
                    oSettings.fnCookieCallback
                );
            },
            "stateSaveParams":function(oSettings,oData){
                oData.start=0;
            },
            "fnStateLoadParams": function (oSettings, oData) {
                oData.iLength = 10;
                oData.iStart = 1;
                //oData.oSearch.sSearch = db_search_term;
            },
            "fnPreDrawCallback": function () {
                show_grid_loader();
            },
			"fnCreatedRow": function (nRow, aData, iDataIndex) {
                jQuery(nRow).find('.arm_grid_action_btn_container').each(function () {
                    jQuery(this).parent().addClass('armGridActionTD');
                    jQuery(this).parent().attr('data-key', 'armGridActionTD');
                });
            },
            "fnDrawCallback": function (oSettings) {
				jQuery('.arm_loading_grid').hide();
				jQuery(".dataTables_scroll").show();
				jQuery(".footer").show();
                arm_show_data();
				jQuery(".cb-select-all-th").removeClass('sorting_asc').addClass('sorting_disabled');
                jQuery("#cb-select-all-1").prop("checked", false);
                arm_selectbox_init();
                jQuery('#arm_filter_wrapper').hide();
                filtered_data = false;
                if (jQuery.isFunction(jQuery().tipso)) {
                    jQuery('.armhelptip').each(function () {
                        jQuery(this).tipso({
                            position: 'top',
                            size: 'small',
                            background: '#939393',
                            color: '#ffffff',
                            width: false,
                            maxWidth: 400,
                            useTitle: true
                        });
                    });
                }
                oTables.dataTable().fnAdjustColumnSizing(false);
            }
        });
		var filter_box = jQuery('#arm_filter_wrapper').html();
        jQuery('.arm_filter_grid_list_container').find('.arm_datatable_filters_options').remove();
		jQuery('div#armember_datatable_filter').parent().append(filter_box);
		jQuery('div#armember_datatable_filter').hide();

		if(filter_search != ''){
			jQuery('.arm_datatable_searchbox').find('#armmanagesearch_new_drip:last-child').val(filter_search)
		}
	}
// ]]>
function show_grid_loader() {
	jQuery('.arm_bulk_action_section').hide();
	jQuery(".dataTables_scroll").hide();
	jQuery(".footer").hide();
	jQuery('.arm_loading_grid').show();
}
</script>
<?php if (!empty($all_plans)) { ?>
	<div class="arm_drip_rule_list">
		<div class="arm_filter_wrapper" id="arm_filter_wrapper" style="display:none;">
			<div class="arm_datatable_filters_options arm_bulk_action_section hidden_section">
				 <span class="arm_reset_bulk_action"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6.34313 17.6569L12 12M17.6568 6.34315L12 12M12 12L6.34313 6.34315M12 12L17.6568 17.6569" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
				<span class="arm_selected_chkcount"></span>&nbsp;&nbsp;<span><?php esc_html_e('of','ARMember');?></span>&nbsp;&nbsp;<span class="arm_selected_chkcount_total"></span>&nbsp;&nbsp;<span><?php esc_html_e('Selected','ARMember');?></span><div class="arm_margin_right_10"></div><div class="arm_margin_left_10"></div>
				<div class='sltstandard'>
					<input type="hidden" id="arm_drip_rule_bulk_action" name="action1" value="delete_drip_rule" />
					<dl class="arm_selectbox column_level_dd arm_width_250">
						<dt><span class="arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
						<dd>
							<ul data-id="arm_drip_rule_bulk_action">
								<li data-label="<?php esc_attr_e('Bulk Actions','ARMember');?>" data-value="-1"><?php esc_html_e('Bulk Actions','ARMember');?></li>
								<li data-label="<?php esc_attr_e('Delete', 'ARMember');?>" data-value="delete_drip_rule"><?php esc_html_e('Delete', 'ARMember');?></li>
							</ul>
						</dd>
					</dl>
				</div>
				<input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php esc_attr_e('Go','ARMember');?>"/>
			</div>
			<div class="arm_datatable_filters_options arm_filters_fields">
				<div class='sltstandard'>
					<div class="arm_confirm_box_btn_container arm_margin_0">
						<div class="arm_dt_filter_block arm_datatable_searchbox">
							<div class="arm_datatable_filter_item">
								<label><input type="text" placeholder="<?php esc_attr_e('Search', 'ARMember');?>" id="armmanagesearch_new_drip" value="<?php echo esc_attr($filter_search);?>" tabindex="0"></label>
							</div>
						</div>
						<div class="arm_filter_child_row">
							<div>
								<div class="arm_datatable_filter_item arm_filter_dctype_label">
									<input type="text" id="arm_filter_dctype" class="arm_filter_dctype arm-selectpicker-input-control" value="<?php echo esc_attr($filter_dctype);?>" />
									<dl class="arm_selectbox column_level_dd arm_width_230">
										<dt>
											<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.8333 6.10587L12.4167 2.66066C12 2.25053 11.4167 2.00444 10.75 2.00444H5.83334C4.58333 1.92241 3.5 2.98878 3.5 4.21921V15.7032C3.5 16.9336 4.5 18 5.83334 18H14.1667C15.4167 18 16.5 17.0157 16.5 15.7032V7.66441C16.5 7.09021 16.25 6.51601 15.8333 6.10587ZM7.5 8.48472H10C10.3334 8.48472 10.6667 8.7308 10.6667 9.14096C10.6667 9.55104 10.4166 9.79712 10 9.79712H7.5C7.16667 9.79712 6.83333 9.55104 6.83333 9.14096C6.83333 8.7308 7.16667 8.48472 7.5 8.48472ZM12.5 13.0783H7.5C7.16667 13.0783 6.83333 12.8322 6.83333 12.4221C6.83333 12.0119 7.08334 11.7658 7.5 11.7658H12.5C12.8333 11.7658 13.1666 12.0119 13.1666 12.4221C13.1666 12.8322 12.8333 13.0783 12.5 13.0783Z" fill="#9CA7BD"/></svg>
											<span class="arm_drip_content_filter_value arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i>
										</dt>
										<dd>
											<ul data-id="arm_filter_dctype">
												<li data-label="<?php esc_attr_e('Select Content Type','ARMember');?>" data-value="0"><?php esc_html_e('Select Content Type','ARMember');?></li>
												<?php 
												if (!empty($dripContentTypes)) {
													foreach ($dripContentTypes as $key => $val) {
														?><li data-label="<?php echo esc_attr($val);?>" data-value="<?php echo esc_attr($key);?>"><?php echo esc_html($val);?></li><?php
													}
												}
												?>
											</ul>
										</dd>
									</dl>
								</div>
							</div>
						</div>
						<div class="arm_filter_child_row">							
							<div>
								<?php if (!empty($all_plans)): ?>
									<div class="arm_filter_plans_box arm_datatable_filter_item arm_filter_plan_id_label">
										<input type="text" id="arm_filter_dplan_id" class="arm_filter_dplan_id arm-selectpicker-input-control" value="<?php echo esc_attr($filter_plan_id);?>" />
										<dl class="arm_multiple_selectbox arm_width_230">
											<dt>
												<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_7522_5770)"><rect x="6" y="2" width="8" height="16" rx="2" fill="#9CA7BD"/><path opacity="0.4" d="M11 4H16C17.1046 4 18 4.89543 18 6V14C18 15.1046 17.1046 16 16 16H11V4Z" fill="#9CA7BD"/><path opacity="0.4" d="M2 6C2 4.89543 2.89543 4 4 4H9V16H4C2.89543 16 2 15.1046 2 14V6Z" fill="#9CA7BD"/></g><defs><clipPath id="clip0_7522_5770"><rect width="16" height="16" fill="white" transform="translate(2 2)"/></clipPath></defs></svg>
												<span class="arm_drip_plan_filter_value"></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i>
											</dt>
											<dd>
												<ul data-id="arm_filter_dplan_id" data-placeholder="<?php esc_attr_e('Select Plans', 'ARMember');?>">
													<?php foreach ($all_plans as $plan): ?>
													<li data-label="<?php echo esc_html(stripslashes($plan['arm_subscription_plan_name'])); ?>" data-value="<?php echo esc_attr($plan['arm_subscription_plan_id']); ?>"><input type="checkbox" class="arm_icheckbox" value="<?php echo esc_attr($plan['arm_subscription_plan_id']);?>"/><?php echo esc_html(stripslashes($plan['arm_subscription_plan_name'])); ?></li>
													<?php endforeach;?>
												</ul>
											</dd>
										</dl>
									</div>
								<?php endif;?>
							</div>
						</div>
						<div class="arm_filter_child_row">							
							<div>
								<div class="arm_datatable_filter_item arm_filter_drip_type_label" style="">
									<input type="text" id="arm_filter_drip_type" class="arm_filter_drip_type arm-selectpicker-input-control" value="<?php echo esc_attr($filter_drip_type);?>" />
									<dl class="arm_selectbox column_level_dd arm_width_230">
										<dt>
											<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.4 3.6H15.6V2.8C15.6 2.32 15.28 2 14.8 2C14.32 2 14 2.32 14 2.8V3.6H6V2.8C6 2.32 5.68 2 5.2 2C4.72 2 4.4 2.32 4.4 2.8V3.6H2.8C2.4 3.6 2 3.92 2 4.4V15.6C2 16.08 2.4 16.4 2.8 16.4H7.68C7.12 15.44 6.8 14.32 6.8 13.2C6.8 9.68 9.68 6.8 13.2 6.8C14.72 6.8 16.08 7.36 17.2 8.24V4.4C17.2 4 16.8 3.6 16.4 3.6Z" fill="#9CA7BD"/><path d="M13.2 8.40002C10.56 8.40002 8.40002 10.56 8.40002 13.2C8.40002 15.84 10.56 18 13.2 18C15.84 18 18 15.84 18 13.2C18 10.56 15.84 8.40002 13.2 8.40002ZM14.8 14H13.2C12.72 14 12.4 13.68 12.4 13.2V10.8C12.4 10.32 12.72 10 13.2 10C13.68 10 14 10.32 14 10.8V12.4H14.8C15.28 12.4 15.6 12.72 15.6 13.2C15.6 13.68 15.28 14 14.8 14Z" fill="#9CA7BD"/></svg>
											<span class="arm_drip_type_filter_value arm_no_auto_complete"></span><i class="armfa armfa-caret-down armfa-lg"></i>
										</dt>
										<dd>
											<ul data-id="arm_filter_drip_type">
												<li data-label="<?php esc_attr_e('Select Drip Type','ARMember');?>" data-value="0"><?php esc_html_e('Select Drip Type','ARMember');?></li>
												<?php 
												if (!empty($dripContentTypes)) {
													foreach ($drip_types as $key => $val) {
														?><li data-label="<?php echo esc_attr($val);?>" data-value="<?php echo esc_attr($key);?>"><?php echo esc_html($val);?></li><?php
													}
												}
												?>
											</ul>
										</dd>
									</dl>
								</div>
							</div>
						</div>
						<div class="arm_filter_child_row">
							<div>
								<input type="button" class="armemailaddbtn" id="arm_drip_rule_grid_filter_btn" onclick="arm_load_drip_rules_list_filtered_grid();" value="<?php esc_html_e('Apply','ARMember');?>">
								<input type="button" class="arm_cancel_btn arm_margin_left_12 hidden_section" value="<?php esc_html_e('Clear','ARMember');?>">
							</div>
						</div>
					</div>
				</div>
				<div class="arm_filter_hide_show_btn_section arm_hide">
					<button type="button" class="arm_filter_hide_show_btn" id="arm_filter_hide_show_btn" data-status="0">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_7619_15796)"><g clip-path="url(#clip1_7619_15796)"><path d="M17 1H3C1.89543 1 1 1.89557 1 3.00031V4.17207C1 4.70259 1.21071 5.21137 1.58579 5.58651L7.41421 11.4158C7.78929 11.791 8 12.2998 8 12.8302V18.0027V18.2884C8 18.9211 8.7649 19.2379 9.2122 18.7906L10 18.0027L11.4142 16.5882C11.7893 16.2131 12 15.7043 12 15.1738V12.8302C12 12.2998 12.2107 11.791 12.5858 11.4158L18.4142 5.58651C18.7893 5.21137 19 4.70259 19 4.17207V3.00031C19 1.89557 18.1046 1 17 1Z" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></g></g><defs><clipPath id="clip0_7619_15796"><rect width="20" height="20" fill="white"/></clipPath><clipPath id="clip1_7619_15796"><rect width="20" height="20" fill="white"/></clipPath></defs></svg>
					</button>
				</div>
			</div>
		</div>
		<form method="GET" id="drip_rule_list_form" class="data_grid_list drip_rule_list_form" onsubmit="return apply_bulk_action_drip_list();">
			<input type="hidden" name="page" value="<?php echo esc_attr($arm_slugs->drip_rules);?>" />
			<input type="hidden" name="armaction" value="list" />
			<div id="armmainformdriplist" class="arm_filter_grid_list_container">
				<div class="arm_loading_grid" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>
				<div class="response_messages"></div>
				<table cellpadding="0" cellspacing="0" border="0" class="display arm_hide_datatable" id="armember_datatable">
					<thead>
						<tr>
							<th class="center cb-select-all-th"><input id="cb-select-all-1" type="checkbox" class="chkstanard"></th>
							<th class="center"><?php esc_html_e('Enable/Disable','ARMember');?></th>
							<th class=""><?php esc_html_e('Content Type','ARMember');?></th>
							<th class=""><?php esc_html_e('Page/Post Name','ARMember');?></th>
							<th class=""><?php esc_html_e('Drip Type', 'ARMember'); ?></th>
							<th class=""><?php esc_html_e('Shortcode','ARMember');?></th>
							<th class=""><?php esc_html_e('Plans','ARMember');?></th>
							<th class="armGridActionTD"></th>
						</tr>
					</thead>
					<tbody id="arm_drip_rules_wrapper">
					</tbody>
				</table>
				<div class="armclear"></div>
				<input type="hidden" name="search_grid" id="search_grid" value="<?php esc_attr_e('Search','ARMember');?>"/>
				<input type="hidden" name="entries_grid" id="entries_grid" value="<?php esc_attr_e('rules','ARMember');?>"/>
				<input type="hidden" name="show_grid" id="show_grid" value="<?php esc_attr_e('Show','ARMember');?>"/>
				<input type="hidden" name="showing_grid" id="showing_grid" value="<?php esc_attr_e('Showing','ARMember');?>"/>
				<input type="hidden" name="to_grid" id="to_grid" value="<?php esc_attr_e('to','ARMember');?>"/>
				<input type="hidden" name="of_grid" id="of_grid" value="<?php esc_attr_e('of','ARMember');?>"/>
				<input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php esc_attr_e('No matching rule found','ARMember');?>"/>
				<input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php esc_attr_e('No any rule found.','ARMember');?>"/>
				<input type="hidden" name="filter_grid" id="filter_grid" value="<?php esc_html_e('filtered from', 'ARMember'); ?>"/>
				<input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php esc_html_e('total', 'ARMember'); ?>"/>
			 </div>
			 <div class="footer_grid"></div>
		</form>
	</div>
<?php 
} else {
	?>
<h4 class="arm_no_access_rules_message"><?php esc_html_e('There is no any plan configured yet', 'ARMember'); ?>, <a href="<?php echo esc_url(admin_url('admin.php?page=' . $arm_slugs->manage_plans . '&action=new')); ?>" class="arm_ref_info_links" target="_blank"><?php esc_html_e('Please add new plan.', 'ARMember'); ?></a></h4>
	<?php
}
?>
<script type="text/javascript">
    __ARM_Showing = '<?php esc_html_e('Showing','ARMember'); ?>';
    __ARM_Showing_empty = '<?php esc_html_e('Showing 0 to 0 of 0 members','ARMember'); ?>';
    __ARM_to = '<?php esc_html_e('to','ARMember'); ?>';
    __ARM_of = '<?php esc_html_e('of','ARMember'); ?>';
    __ARM_members = '<?php esc_html_e('members','ARMember'); ?>';
    __ARM_Show = '<?php esc_html_e('Members per pages','ARMember'); ?>';
    __ARM_NO_FOUNT = '<?php esc_html_e('No any member found.','ARMember'); ?>';
    __ARM_NO_MATCHING = '<?php esc_html_e('No matching members found.','ARMember'); ?>';
</script>
