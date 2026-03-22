<?php
global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_manage_coupons, $arm_subscription_plans, $arm_payment_gateways,$arm_group_membership,$arm_pay_per_post_feature,$arm_common_lite;
$globals_settings = $arm_global_settings->arm_get_all_global_settings();
$res_coupons = $arm_manage_coupons->arm_get_all_coupons();
$global_currency = $arm_payment_gateways->arm_get_global_currency();
$date_format = $arm_global_settings->arm_get_wp_date_format();
$filter_search  = ( ! empty( $_REQUEST['sSearch'] ) ) ? $_REQUEST['sSearch'] : ''; //phpcs:ignore
?>
<?php

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();

$get_page = !empty($_GET['page']) ? sanitize_text_field( $_GET['page'] ) : '';
?>
<style type="text/css" title="currentStyle">
	.paginate_page a{display:none;}
	#poststuff #post-body {margin-top: 32px;}
	.ColVis_Button{ display: none !important;}
</style>
<script type="text/javascript" charset="utf-8">

function show_grid_loader() {
	jQuery('.arm_bulk_action_section').hide();
	jQuery('.dataTables_scroll').hide();
	jQuery('.footer').hide();
    jQuery('.arm_loading_grid').show();
	var count_checkbox = jQuery('.chkstanard:checked').length;
	if(count_checkbox > 0)
	{
		jQuery('#armember_datatable_filter').addClass('hidden_section');
		jQuery('.arm_bulk_action_section').removeClass('hidden_section');
	}
	else{
		jQuery('#armember_datatable_filter').removeClass('hidden_section');
		jQuery('.arm_bulk_action_section').addClass('hidden_section');
	}
}

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

jQuery(document).on('keyup','#armmanagesearch_coupon', function (e) {
	e.stopPropagation();
	var arm_search = jQuery(this).val();
	jQuery('#armmanagesearch_coupon').val(arm_search);
	if(e.keyCode ==13)
	{
		jQuery('#armember_datatable').dataTable().fnDestroy();
		arm_loadCouponData();
	}
	// jQuery('#armember_datatable_filter input[type="search"]').val(arm_search).trigger('keyup');
	return false;
});

jQuery(document).on('click','#arm_member_coupon_grid_filter_btn',function(e){
	jQuery('#armember_datatable').dataTable().fnDestroy();
	arm_loadCouponData();
})
function arm_loadCouponData()
{
	var __ARM_Coupon_List_Left = [2,5,8,9];
	var __ARM_Coupon_List_right = [4];
	var __ARM_Coupon_List_Center = [7];
	var __ARM_subscription_plan_List_col = [9];
	var __ARM_subscription_plan_col_width = 'arm_min_width_400';

	<?php
	if( isset( $arm_group_membership ) ) {  ?>	
		__ARM_Coupon_List_Left = [2,6,9,10];
		__ARM_Coupon_List_Center =[8];  
		__ARM_subscription_plan_List_col = [10]; 
		var __ARM_subscription_plan_col_width = 'arm_min_width_300';  <?php		
	} ?>	
	var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMember')); //phpcs:ignore?>';
    var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing','ARMember').' <span class="arm-black-350 arm_font_size_15">0</span> - <span class="arm-black-350 arm_font_size_15">0</span> of <span class="arm-black-350 arm_font_size_15">0</span> '.esc_html__('coupons','ARMember')); //phpcs:ignore?>';
    var __ARM_to = '-';
    var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMember')); //phpcs:ignore?>';

    var __ARM_Entries = ' <?php echo addslashes(esc_html__('Coupons','ARMember')); //phpcs:ignore?>';
    var __ARM_Show = '<?php echo addslashes(esc_html__('Show','ARMember')); //phpcs:ignore?>';
    var __ARM_NO_FOUND = '<?php echo addslashes(esc_html__('No any coupon found.','ARMember')); //phpcs:ignore?>';
    var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching coupon found.','ARMember')); //phpcs:ignore?>';
	var _wpnonce = jQuery('input[name="arm_wp_nonce"]').val();
	var search = jQuery('#armmanagesearch_coupon').val();

	var ajax_url = '<?php echo admin_url("admin-ajax.php"); //phpcs:ignore?>';
	var table = jQuery('#armember_datatable').dataTable({
		"sDom": '<"H"Cfr>t<"footer"ipl>',
		"sPaginationType": "four_button",
		"sProcessing": show_grid_loader(),
        "oLanguage": {
            "sInfo": __ARM_Showing + " <span class='arm-black-350 arm_font_size_15'>_START_</span> " + __ARM_to + " <span class='arm-black-350 arm_font_size_15'>_END_</span> " + __ARM_of + " <span class='arm-black-350 arm_font_size_15'>_TOTAL_</span> " + __ARM_Entries,
            "sInfoEmpty": __ARM_Showing_empty,
            "sLengthMenu": __ARM_Show+ "_MENU_",
            "sEmptyTable": __ARM_NO_FOUND,
            "sZeroRecords": __ARM_NO_MATCHING,
        },
		"bDestroy": true,
        "language": {
			"searchPlaceholder": "<?php echo esc_html__( 'Search Coupon', 'ARMember' ); ?>",
			"search": "",
		},	
        "bProcessing": false,
        "bServerSide": true,
        "sAjaxSource": ajax_url,
		"sServerMethod": "POST",
		"bRetrieve": false,
		"bJQueryUI": true,
		"bPaginate": true,
		"bAutoWidth" : false,
		"sScrollX": "100%",
    		"bScrollCollapse": true,
		"aaSorting": [],
		"ordering": false,
		/*"aoColumnDefs": [
			{ "bVisible": false, "aTargets": [] },
			{ "bSortable": false, "aTargets": [0, 5, 9] }
		],*/
		"aoColumnDefs": [
		 	{"sClass": "dt-center", "aTargets": __ARM_Coupon_List_Center},
         	{"sClass": "dt-left", "aTargets": __ARM_Coupon_List_Left},
			{"sClass": "dt-right", "aTargets": __ARM_Coupon_List_right},
			{"sClass": "arm_min_width_150", "aTargets":[5,6]},
			{"sClass": "arm_max_width_60 dt-center", "aTargets":[0]},
			{"sClass": "arm_min_width_80 arm_max_width_100 dt-center", "aTargets":[1]},		
        ],
		"oColVis": {
		   "aiExclude": [ 0, 9 ]
		},
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
		"fnPreDrawCallback": function () {
            show_grid_loader();
        },
        "fnCreatedRow": function (nRow, aData, iDataIndex) {
            jQuery(nRow).find('.arm_grid_action_wrapper').each(function () {
                jQuery(this).parent().addClass('armGridActionTD');
                jQuery(this).parent().attr('data-key', 'armGridActionTD');
            });
        },
        "fnStateLoadParams": function (oSettings, oData) {
            oData.iLength = 10;
            oData.iStart = 0;
        },
		"stateSaveParams":function(oSettings,oData){
			oData.start=0;
		},
		"fnServerParams": function (aoData) {
            aoData.push({'name': 'action', 'value': 'arm_get_coupon_data'});
			aoData.push({'name': 'sSearch', 'value': search});
			aoData.push({'name': '_wpnonce', 'value': _wpnonce});
        },
		"fnDrawCallback":function(){
			arm_show_data();
			jQuery('.arm_loading_grid').hide();		
			jQuery('.dataTables_scroll').show();
			jQuery('.footer').show();
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
			table.dataTable().fnAdjustColumnSizing(false);
		}
	});
	var filter_box = jQuery('#arm_filter_wrapper').html();
	jQuery('div#armember_datatable_filter').parent().append(filter_box);
	if(search != ''){
        	jQuery('.arm_datatable_searchbox').find('#armmanagesearch_coupon').val(search);
    	}
}

jQuery(document).ready( function ($) {
	arm_loadCouponData();
});


function ChangeID(id) {
	document.getElementById('delete_id').value = id;
}						

</script>
<div class="wrap arm_page arm_manage_coupon_main_wrapper">
	<div class="content_wrapper" id="content_wrapper">
		<div class="page_title arm_padding_bottom_24">
			<?php esc_html_e('Coupons','ARMember');?>
			<div class="arm_add_new_item_box">
				<a class="greensavebtn arm_add_coupon_btn" href="javascript:void(0)"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.svg"><span><?php esc_html_e('Add Coupon', 'ARMember') ?></span></a>
				<a class="greensavebtn arm_blk_action_btn" href="javascript:void(0)" onclick="arm_open_bulk_coupon_popup();"><img align="absmiddle" src="<?php echo MEMBERSHIP_IMAGES_URL //phpcs:ignore?>/arm_bulk_action_icon.svg"><span><?php esc_html_e('Bulk Create', 'ARMember') ?></span></a>
			</div>
		</div>	
		<div class="arm_filter_wrapper" id="arm_filter_wrapper" style="display:none;">

			<div class="arm_datatable_filters_options arm_bulk_action_section hidden_section">
				<span class="arm_reset_bulk_action"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6.34313 17.6569L12 12M17.6568 6.34315L12 12M12 12L6.34313 6.34315M12 12L17.6568 17.6569" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span><span class="arm_selected_chkcount"></span>&nbsp;&nbsp;<span><?php esc_html_e('of','ARMember');?></span>&nbsp;&nbsp;<span class="arm_selected_chkcount_total arm-black-600 arm_font_size_15"></span>&nbsp;&nbsp;<span><?php esc_html_e('Selected','ARMember');?></span><div class="arm_margin_right_10"></div><div class="arm_margin_left_10"></div>
				<div class='sltstandard'>
					<input type="hidden" id="arm_coupons_bulk_action1" name="action1" value="-1" />
					<dl class="arm_selectbox column_level_dd arm_width_250">
						<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
						<dd>
							<ul data-id="arm_coupons_bulk_action1">
								<li data-label="<?php esc_html_e('Bulk Actions','ARMember');?>" data-value="-1"><?php esc_html_e('Bulk Actions','ARMember');?></li>
								<li data-label="<?php esc_html_e('Delete', 'ARMember');?>" data-value="delete_coupon"><?php esc_html_e('Delete', 'ARMember');?></li>
							</ul>
						</dd>
					</dl>
				</div>
				<input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php esc_html_e('Go','ARMember');?>"/>
			</div>

			<div class="arm_datatable_filters_options arm_filters_searchbox">
				<div class="sltstandard">
					<div class="arm_confirm_box_btn_container" bis_skin_checked="1">
						<div class="arm_dt_filter_block arm_datatable_searchbox">
							<div class="arm_datatable_filter_item">
								<label class="arm_padding_0"><input type="text" placeholder="<?php esc_attr_e( 'Search Coupon', 'ARMember' ); ?>" id="armmanagesearch_coupon" value="<?php echo esc_attr($filter_search); ?>" tabindex="0"></label>
							</div>
						</div>
						<div class="arm_filter_child_row arm_margin_left_12">
							<div>
								<input type="button" class="armemailaddbtn arm_margin_left_12" id="arm_member_coupon_grid_filter_btn" value="<?php esc_html_e('Apply','ARMember');?>">
								<input type="button" class="arm_cancel_btn arm_margin_left_12 hidden_section" value="<?php esc_html_e('Clear','ARMember');?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="arm_filter_wrapper" id="arm_filter_wrapper_after_filter" style="display:none;">
			<div class="arm_datatable_filters_options">
				<div class='sltstandard'>
					<input type='hidden' id='arm_coupons_bulk_action1' name="action1" value="-1" />
					<dl class="arm_selectbox arm_width_250">
						<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
						<dd>
							<ul data-id="arm_coupons_bulk_action1">
								<li data-label="<?php esc_html_e('Bulk Actions','ARMember');?>" data-value="-1"><?php esc_html_e('Bulk Actions','ARMember');?></li>
								<li data-label="<?php esc_html_e('Delete', 'ARMember');?>" data-value="delete_coupon"><?php esc_html_e('Delete', 'ARMember');?></li>
							</ul>
						</dd>
					</dl>
				</div>
				<input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php esc_html_e('Go','ARMember');?>"/>
			</div>
		</div>
		<div class="arm_members_list" id="arm_member_list_form">
			<form method="GET" id="coupon_list_form" class="data_grid_list" onsubmit="return apply_bulk_action_coupon_list();">
				<input type="hidden" name="page" value="<?php echo esc_attr($get_page); ?>" />
				<input type="hidden" name="armaction" value="list" />
				<div id="armmainformnewlist">
            		<div class="arm_loading_grid" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>
					<table cellpadding="0" cellspacing="0" border="0" class="display arm_hide_datatable" id="armember_datatable">
						<thead>
							<tr>
								<th class="center cb-select-all-th arm_width_60" ><input id="cb-select-all-1" type="checkbox" class="chkstanard"></th>
								<th class="arm_width_100 dt-center"><?php esc_html_e('Active', 'ARMember'); ?></th>
								<th><?php esc_html_e('Coupon Label','ARMember');?></th>
                                <th><?php esc_html_e('Coupon Code','ARMember');?></th>
								<th><?php esc_html_e('Discount','ARMember');?></th>
								<?php
									$arm_coupon_filter_heading = "";
									echo apply_filters('arm_add_new_coupon_field_heading', $arm_coupon_filter_heading); //phpcs:ignore
								?>
								<th><?php esc_html_e('Start Date','ARMember');?></th>
								<th><?php esc_html_e('Expire Date','ARMember');?></th>
								<th><?php esc_html_e('Used','ARMember');?></th>
								<th><?php esc_html_e('Allowed Uses','ARMember');?></th>
								<th><?php esc_html_e('Subscription','ARMember');?></th>
								<th class="armGridActionTD"></th>
							</tr>
						</thead>
						
					</table>
					<div class="armclear"></div>
					<input type="hidden" name="search_grid" id="search_grid" value="<?php esc_html_e('Search','ARMember');?>"/>
					<input type="hidden" name="entries_grid" id="entries_grid" value="<?php esc_html_e('coupons','ARMember');?>"/>
					<input type="hidden" name="show_grid" id="show_grid" value="<?php esc_html_e('Show','ARMember');?>"/>
					<input type="hidden" name="showing_grid" id="showing_grid" value="<?php esc_html_e('Showing','ARMember');?>"/>
					<input type="hidden" name="to_grid" id="to_grid" value="<?php esc_html_e('to','ARMember');?>"/>
					<input type="hidden" name="of_grid" id="of_grid" value="<?php esc_html_e('of','ARMember');?>"/>
					<input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php esc_html_e('No matching coupons found','ARMember');?>"/>
					<input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php esc_html_e('There is no any coupon found.','ARMember');?>"/>
					<input type="hidden" name="filter_grid" id="filter_grid" value="<?php esc_html_e('filtered from','ARMember');?>"/>
					<input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php esc_html_e('total','ARMember');?>"/>
					<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
				<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
				 </div>
				 <div class="footer_grid"></div>
			</form>
		</div>
		<div class="armclear"></div>
		<div class="arm_bulk_coupon_form_fields_popup_div popup_wrapper <?php echo (is_rtl()) ? 'arm_page_rtl' : ''; ?>" style="">
			<div class="content_wrapper arm_email_settings_content">
				<div class="popup_header page_title">
					<span class="arm_coupon_form_label arm_add_coupon_label"><?php esc_html_e('Bulk Create Coupon', 'ARMember'); ?></span>
					<span class="popup_close_btn arm_popup_close_btn arm_bulk_coupon_fields_close_btn"></span>
				</div>
				<form  method="post" action="#" id="arm_bulk_coupon_wrapper_frm" class="arm_add_edit_bulk_coupon_wrapper_frm arm_admin_form"> 
                    <div class="arm_admin_form_content arm_bulk_coupon_form_fields_popup_text">
					            <input type="hidden" name="arm_action" value="add_coupon">
								<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
								<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
					            <?php
					            $period_type = 'daterange';
					            $c_discount='';
						        $c_sdate='';
						        $c_edate='';
						        $c_allowed_uses='';
						        $c_label='';
						        $c_id = 0;
					            $coupon_status = 1;
					            $c_allow_trial = 0;
					            $c_coupon_on_each_subscriptions = 0;
					            $c_type = 'fixed';
					            $edit_mode = false;
					            $sdate_status = '';
					            $c_subs = array();
					            $c_data='';
					            ?>
					            <div class="arm_admin_form_content arm_form_main_content arm_padding_40">
					                <table class="form-table">
										<tr class="form-field form-required arm_width_100_pct">
											<td class="arm_padding_bottom_0 arm_padding_top_0">
											<div class="arm_form_header_label arm_padding_left_0 arm_padding_0"><?php esc_html_e('Coupon Code Details','ARMember');?></div>
											</td>
										</tr>
					                	<tr class="form-field form-required arm_width_100_pct">
					                        <th class="arm_padding_top_16"><label class="arm_font_size_16"><?php esc_html_e('Code type', 'ARMember'); ?></label></th>
					                        <td class="arm_padding_bottom_15">
					                            <div class="arm_coupon_type_box">
					                                <span class="arm_coupon_types_container" id="arm_coupon_types_container">
					                                    <input type="radio" class="arm_iradio" checked="checked" value="alphanumeric" name="arm_coupon_code_type" id="period_type_alfn" >
					                                    <label for="period_type_alfn" class="arm_padding_right_46"><?php esc_html_e('Alphanumeric', 'ARMember'); ?></label>
					                                    <input type="radio" class="arm_iradio" value="alphabetical" name="arm_coupon_code_type" id="period_type_alp" >
					                                    <label for="period_type_alp" class="arm_padding_right_46"><?php esc_html_e('Alphabetical', 'ARMember'); ?></label>
					                                    <input type="radio" class="arm_iradio" value="numeric" name="arm_coupon_code_type" id="period_type_aln" >
					                                    <label for="period_type_aln"><?php esc_html_e('Numeric', 'ARMember'); ?></label>
					                                </span>
					                                <div class="armclear"></div>
					                            </div> 
					                        </td>
					                    </tr>
										<tr class="form-field form-required">
											<td class="arm_padding_top_0">
												<div>
													<table class="arm_width_100_pct">
														<tbody class="arm_display_grid arm_grid_col_3">
															<tr class="form-field form-required">
																<th class="arm_padding_top_0"><label class="arm_font_size_16"><?php esc_html_e('Code Length', 'ARMember'); ?></label><i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_html("For Bulk Creation Coupon Code Length should be Minimum 5 and Maximum 50.", 'ARMember');?>"></i></th>
																<td class="arm_padding_bottom_0">
																	<input type="text"  id="arm_coupon_code_length" value="5" min="5" max="50" onkeypress="return ArmNumberValidation(event, this)" name="arm_coupon_code_length" class="arm_no_paste" data-msg-required="<?php esc_html_e('Please add Coupon Code Length.', 'ARMember'); ?>" required />
																</td>
															</tr>
															<tr class="form-field form-required">
																<th class="arm_padding_top_0 arm_width_100_pct"><label class="arm_font_size_16"><?php esc_html_e('Enter number of Coupon(s) to Generate', 'ARMember'); ?></label><i class="arm_helptip_icon armfa armfa-question-circle" title="<?php echo esc_html("Enter Number of Bulk Creation Coupon Code Which should be Minimum 1 and Maximum 1000.", 'ARMember');?>"></i></th>
																<td class="arm_padding_bottom_0">
																	<input type="text" id="arm_coupon_quantity" value="" min="1" max="1000" onkeypress="return ArmNumberValidation(event, this)" name="arm_coupon_quantity" class="arm_no_paste" data-msg-required="<?php esc_html_e('Please Enter number of Coupon(s) to Generate.', 'ARMember'); ?>" required />
																</td>
															</tr> 
														</tbody>
													</table>
												</div>
											</td>
										</tr>
										<tr class="form-field form-required arm_width_100_pct arm_margin_top_8 arm_blk_action_divider_row">
											<td class="arm_padding_bottom_0 arm_padding_top_0">
												<div class="arm_solid_divider arm_blk_action_divider arm_margin_top_20"></div>                                               
											</td>
										</tr>
										<tr class="form-field form-required arm_width_100_pct">
											<td class="arm_required_member_wrapper arm_padding_bottom_0 arm_padding_top_12">
												<div class="arm_form_header_label arm_padding_0 "><?php esc_html_e('Coupon Details','ARMember');?></div>
											</td>
										</tr>
										<?php
											$arm_coupon_type = 1;
											$arm_paid_posts = array();
											$is_bulk_create=1;
											echo $arm_manage_coupons->arm_coupon_form_html($c_discount,$c_type,$period_type,$sdate_status,$edit_mode,$c_sdate,$c_edate,$c_allow_trial,$c_allowed_uses,$c_label,$c_coupon_on_each_subscriptions,$coupon_status,$c_subs,$c_data,$arm_coupon_type,$arm_paid_posts,$is_bulk_create); //phpcs:ignore
					                    ?>
					                </table>
					                <div class="armclear"></div>
					            </div>
					        
                    </div>
                    <div class="arm_submit_btn_container">
							<input type="hidden" name="op_type" id="form_type" value="bulk_add" />
                            <img src="<?php echo MEMBERSHIPLITE_IMAGES_URL . '/arm_loader.gif'; //phpcs:ignore?>" id="arm_loader_img_coupon_field" class="arm_loader_img arm_submit_btn_loader" style=" display: none;" width="20" height="20" />
                            <button class="arm_cancel_btn arm_bulk_coupon_fields_close_btn" type="button"><?php esc_html_e('Cancel', 'ARMember'); ?></button>
                            <button class="arm_save_btn" id="arm_coupon_operation" type="submit"><?php esc_html_e('Save', 'ARMember') ?></button>
                    </div>
                    <div class="armclear"></div>
				</form>
			</div>
    	</div>
		<?php 
		/* **********./Begin Bulk Delete Coupon Popup/.********** */
		$bulk_delete_coupon_popup_content = '<span class="arm_confirm_text">'.esc_html__("Are you sure you want to delete this coupon(s)?",'ARMember' ).'</span>';
		$bulk_delete_coupon_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
		$bulk_delete_coupon_popup_arg = array(
			'id' => 'delete_bulk_coupon_message',
			'class' => 'arm_delete_bulk_action_message delete_bulk_coupon_message',
			'title' => 'Delete Coupon(s)',
			'content' => $bulk_delete_coupon_popup_content,
			'button_id' => 'arm_bulk_delete_coupon_ok_btn',
			'button_onclick' => "arm_delete_bulk_coupons('true');",
		);
		echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_coupon_popup_arg); //phpcs:ignore
		/* **********./End Bulk Delete Coupon Popup/.********** */
		?>
	</div>

<div class="arm_members_list_detail_popup popup_wrapper arm_members_list_detail_popup_wrapper">
	<div class="arm_loading_grid" id="arm_loading_grid_members" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>
    <div class="popup_wrapper_inner" style="overflow: hidden;">
		<div class="popup_header page_title">
            <span class="popup_close_btn arm_popup_close_btn arm_members_list_detail_close_btn arm_right_8_pct"></span>
            <span class="add_rule_content"><?php esc_html_e('Members Details', 'ARMember'); ?></span>
        </div>
        <div class="popup_content_text arm_members_list_detail_popup_text">
            <table width="100%" cellspacing="0" class="display arm_min_width_802 arm_no_margin" id="armember_datatable_1" >
                <thead>
                    <tr>
                        <th><?php esc_html_e('Username', 'ARMember'); ?></th>
                        <th><?php esc_html_e('Email', 'ARMember'); ?></th>
                        <th class="arm_width_170"><?php esc_html_e('Coupon Code', 'ARMember'); ?></th>
                        <th class="arm-no-sort arm_width_170"><center><?php esc_html_e('View Detail', 'ARMember'); ?></center></th>
                    </tr>
                </thead>
            </table>
            <input type="hidden" name="search_grid" id="search_grid" value="<?php esc_html_e('Search','ARMember');?>"/>
            <input type="hidden" name="entries_grid" id="entries_grid" value="<?php esc_html_e('members','ARMember');?>"/>
            <input type="hidden" name="show_grid" id="show_grid" value="<?php esc_html_e('Show','ARMember');?>"/>
            <input type="hidden" name="showing_grid" id="showing_grid" value="<?php esc_html_e('Showing','ARMember');?>"/>
            <input type="hidden" name="to_grid" id="to_grid" value="<?php esc_html_e('to','ARMember');?>"/>
            <input type="hidden" name="of_grid" id="of_grid" value="<?php esc_html_e('of','ARMember');?>"/>
            <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php esc_html_e('No matching members found','ARMember');?>"/>
            <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php esc_html_e('There is no any member found.','ARMember');?>"/>
            <input type="hidden" name="filter_grid" id="filter_grid" value="<?php esc_html_e('filtered from','ARMember');?>"/>
            <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php esc_html_e('total','ARMember');?>"/>
            <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
				<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
        </div>
        <div class="armclear"></div>
    </div>
</div>

<script type="text/javascript">
    __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMember')); //phpcs:ignore?>';
    __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing 0 to 0 of 0 members','ARMember')); //phpcs:ignore?>';
    __ARM_to = '<?php echo addslashes(esc_html__('to','ARMember')); //phpcs:ignore?>';
    __ARM_of = '<?php echo addslashes(esc_html__('of','ARMember')); //phpcs:ignore?>';
    __ARM_members = '<?php echo addslashes(esc_html__('members','ARMember')); //phpcs:ignore?>';
    __ARM_Show = '<?php echo addslashes(esc_html__('Members per page','ARMember')); //phpcs:ignore?>';
    __ARM_NO_FOUNT = '<?php echo addslashes(esc_html__('No any coupon found.','ARMember')); //phpcs:ignore?>';
    __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching coupon found.','ARMember')); //phpcs:ignore?>';
</script>

<?php require_once(MEMBERSHIPLITE_VIEWS_DIR.'/arm_view_member_details.php')?>
<?php
	echo $ARMember->arm_get_need_help_html_content('member-coupon-list'); //phpcs:ignore
?>