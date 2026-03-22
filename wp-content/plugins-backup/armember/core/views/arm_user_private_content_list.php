<?php
global $arm_common_lite;
$filter_search = (!empty($_POST['search'])) ? sanitize_text_field($_POST['search']) : '';//phpcs:ignore
?>

<style type="text/css" title="currentStyle">
	.paginate_page a{display:none;}
	#poststuff #post-body {margin-top: 32px;}
	.ColVis_Button{display:none;}
</style>

<script type="text/javascript" charset="utf-8">
// <![CDATA[
jQuery(document).ready( function () {
	jQuery("#dataTables_scroll").hide();
	jQuery(".footer").hide();
    arm_load_private_content_list_grid(false);
});

function arm_load_private_content_list_filtered_grid()
{
    jQuery('#armember_datatable').dataTable().fnDestroy();
    arm_load_private_content_list_grid();
}

function show_grid_loader() {
	jQuery(".dataTables_scroll").hide();
    jQuery(".footer").hide();
    jQuery('.arm_loading_grid').show();
}


jQuery(document).on('keyup','#armmanagemembers_search',function(e){
	var arm_search = jQuery(this).val();
	jQuery('.arm_datatable_searchbox #armmanagemembers_search').val(arm_search);
	if(e.keyCode == 13)
	{
		arm_load_private_content_list_filtered_grid()
	}
})

function arm_user_private_grid_filter_grid(){
	arm_load_private_content_list_filtered_grid();
}


function arm_load_private_content_list_grid(is_filtered){

	var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMember')); //phpcs:ignore?>';
	var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing','ARMember').' <span class="arm-black-350 arm_font_size_15">0</span> - <span class="arm-black-350 arm_font_size_15">0</span> of <span class="arm-black-350 arm_font_size_15">0</span> '.esc_html__('entries','ARMember')); //phpcs:ignore?>';
    var __ARM_to = ' - ';
    var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMember')); //phpcs:ignore?>';
    var __ARM_Entries = ' <?php echo addslashes(esc_html__('entries','ARMember')); //phpcs:ignore?>';
    var __ARM_Show = '<?php echo addslashes(esc_html__('Show','ARMember'));//phpcs:ignore ?> ';
    var __ARM_NO_FOUND = '<?php echo addslashes(esc_html__('No user private content found.','ARMember')); //phpcs:ignore?>';
    var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching records found.','ARMember')); //phpcs:ignore?>';

	var nonce = jQuery('input[name="arm_wp_nonce"]').val();

	var search_term = jQuery('.arm_datatable_searchbox #armmanagemembers_search').val();
    var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';
	
	var ajax_url = '<?php echo admin_url("admin-ajax.php"); //phpcs:ignore?>';
	var table = jQuery('#armember_datatable').dataTable({
		"sDom": '<"H"Cfr>t<"footer"ipl>',
		"sPaginationType": "four_button",
		"sProcessing": show_grid_loader(),
        "oLanguage": {
            "sProcessing": show_grid_loader(),
            "sInfo": __ARM_Showing + " <span class='arm-black-350 arm_font_size_15'>_START_</span> " + __ARM_to + " <span class='arm-black-350 arm_font_size_15'>_END_</span> " + __ARM_of + " <span class='arm-black-350 arm_font_size_15'>_TOTAL_</span> " + __ARM_Entries,
            "sInfoEmpty": __ARM_Showing_empty,
           
            "sLengthMenu": __ARM_Show + "_MENU_",
            "sEmptyTable": __ARM_NO_FOUND,
            "sZeroRecords": __ARM_NO_MATCHING,
        },
        "language":{
            "searchPlaceholder": "Search",
            "search":"",
        },
        "bProcessing": false,
        "bServerSide": true,
        "sAjaxSource": ajax_url,
		"bJQueryUI": true,
		"bPaginate": true,
		"sServerMethod": "POST",
		"bAutoWidth" : false,
		"sScrollX": "100%",
        "bScrollCollapse": true,
		"aaSorting": [],
		"aoColumnDefs": [
			{ "bVisible": false, "aTargets": [] },
			{ "bSortable": false, "aTargets": [0,1,2,3] },
			{ "sClass": 'center', "aTargets": [0,1,3]},
			{ "sWidth": '10%', "aTargets": [0]},
			{ "sWidth": '40%', "aTargets": [1]},
			{ "sWidth": '50%', "aTargets": [2]},
		],
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
		"fnPreDrawCallback": function () {
			jQuery('.page_title.arm_defualt_private_content_title, #default_private_content_form').hide();
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
		"fnServerParams": function (aoData) {
            aoData.push({'name': 'action', 'value': 'get_private_content_data'});
			aoData.push({'name': 'sSearch', 'value': db_search_term});
			aoData.push({'name': '_wpnonce', 'value': nonce});
        },
		"fnDrawCallback":function(){
			jQuery('.arm_loading_grid').hide();
			setTimeout(function(){
				arm_show_data();
				jQuery('.dataTables_scroll').show();
				jQuery(".footer").show();
				jQuery('.page_title.arm_defualt_private_content_title, #default_private_content_form').show();
			},250);
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
	if(typeof db_search_term != 'undefined'){
		jQuery('.arm_datatable_searchbox #armmanagemembers_search').val(db_search_term);
	}
}
function ChangeID(id) {
	document.getElementById('delete_id').value = id;
}


// ]]>
</script>

<?php
$filter_search = (!empty($_POST['search'])) ? sanitize_text_field($_POST['search']) : '';//phpcs:ignore

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();

global $wpdb, $ARMember, $arm_global_settings;
$user_table = $ARMember->tbl_arm_members;
$user_meta_table = $wpdb->usermeta;


?>
<div class="wrap arm_page arm_private_content_main_wrapper">

	<div class="content_wrapper arm_private_content_wrapper arm_position_relative" id="content_wrapper" >
		<div class="arm_loading_grid" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>
		<div class="page_title">
			<?php esc_html_e('Manage Userwise Private Content','ARMember');?>
			
		<div class="arm_add_new_item_box">
			<a class="greensavebtn arm_add_user_private_content" href="javascript:void(0)"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.png"><span><?php esc_html_e('Add Private Content', 'ARMember') ?></span></a>
		</div>		
		</div>
		<div class="armclear"></div>
		<div class="arm_solid_divider"></div>
		<div class="arm_filter_wrapper" id="arm_filter_wrapper" style="display:none;">
            <div class="arm_datatable_filters_options arm_filters_searchbox">
                <div class="sltstandard">
					<div class="arm_confirm_box_btn_container arm_margin_0" bis_skin_checked="1">
						<div class="arm_dt_filter_block arm_datatable_searchbox">
							<div class="arm_datatable_filter_item">
								<label class="arm_padding_0"><input type="text" placeholder="<?php esc_attr_e( 'Search Member', 'ARMember' ); ?>" id="armmanagemembers_search" value="<?php echo esc_attr($filter_search); ?>" tabindex="-1"></label>
							</div>
                        </div>
						<div class="arm_filter_child_row arm_margin_left_12">
							<div>
								<input type="button" class="armemailaddbtn" id="arm_user_private_grid_filter_btn" onclick="arm_user_private_grid_filter_grid();" value="<?php esc_html_e('Apply','ARMember');?>">
								<input type="button" class="arm_cancel_btn arm_margin_left_12 hidden_section" value="<?php esc_html_e('Clear','ARMember');?>">
							</div>
						</div>
                    </div>
                </div>
            </div>
        </div>

		<div class="arm_private_content_list">
			<form method="GET" id="subscription_plans_list_form" class="data_grid_list">
				<input type="hidden" name="page" value="<?php echo esc_attr($arm_slugs->private_content);?>" />
				<input type="hidden" name="armaction" value="list" />
				<div id="armmainformnewlist">
					<table cellpadding="0" cellspacing="0" border="0" class="display arm_hide_datatable" id="armember_datatable">
						<thead>
							<tr>
								<th class="arm_text_align_center arm_min_width_150"><?php esc_html_e('Enable / Disable','ARMember');?></th>
								<th class="arm_text_align_center"><?php esc_html_e('User ID','ARMember');?></th>
								<th><?php esc_html_e('Username','ARMember');?></th>
								<th class="armGridActionTD"></th>
							</tr>
						</thead>
					</table>
					<div class="armclear"></div>
					<input type="hidden" name="show_hide_columns" id="show_hide_columns" value="<?php esc_html_e('Columns','ARMember');?>"/>
					<input type="hidden" name="search_grid" id="search_grid" value="<?php esc_html_e('Search','ARMember');?>"/>
					<input type="hidden" name="entries_grid" id="entries_grid" value="<?php esc_html_e('plans','ARMember');?>"/>
					<input type="hidden" name="show_grid" id="show_grid" value="<?php esc_html_e('Show','ARMember');?>"/>
					<input type="hidden" name="showing_grid" id="showing_grid" value="<?php esc_html_e('Showing','ARMember');?>"/>
					<input type="hidden" name="to_grid" id="to_grid" value="<?php esc_html_e('to','ARMember');?>"/>
					<input type="hidden" name="of_grid" id="of_grid" value="<?php esc_html_e('of','ARMember');?>"/>
					<input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php esc_html_e('No matching plans found','ARMember');?>"/>
					<input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php esc_html_e('No any subscription plan found.','ARMember');?>"/>
					<input type="hidden" name="filter_grid" id="filter_grid" value="<?php esc_html_e('filtered from','ARMember');?>"/>
					<input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php esc_html_e('total','ARMember');?>"/>
					<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
					<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
				</div>
				<div class="footer_grid"></div>
			</form>
		</div>
		<?php 
		/* **********./Begin Bulk Delete Plan Popup/.********** */
		$bulk_delete_plan_popup_content = '<span class="arm_confirm_text">'.esc_html__("Are you sure you want to delete this plan(s)?",'ARMember' ).'</span>';
		$bulk_delete_plan_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
		$bulk_delete_plan_popup_arg = array(
			'id' => 'delete_bulk_plan_message',
			'class' => 'delete_bulk_plan_message',
            'title' => esc_html__('Delete Plan(s)', 'ARMember'),
			'content' => $bulk_delete_plan_popup_content,
			'button_id' => 'arm_bulk_delete_plan_ok_btn',
			'button_onclick' => "arm_delete_bulk_plan('true');",
		);
		echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_plan_popup_arg); //phpcs:ignore
		/* **********./End Bulk Delete Plan Popup/.********** */
		?>
		<div class="armclear"></div>

		<div class="page_title arm_defualt_private_content_title" style="display:none;">
			<?php esc_html_e('Default Private Content','ARMember');?>		
			<div class="armclear"></div>
			<span class="arm-note-message --alert arm_margin_top_10 arm_margin_bottom_10 arm_display_block"><?php printf( esc_html__('NOTE : If private content is not set for specific user and user is not in above list than default private content will be displayed to the user.','ARMember'),'&lt;','&gt;'); //phpcs:ignore?></span>
		</div>

		<form action="javascript:void(0)" name="default_private_content_form" id="default_private_content_form" style="display:none;">
			<table cellpadding="0" cellspacing="0" border="0" class="display arm_on_display" id="armember_private_content_default">
				<tr>
					<td colspan="2">
						<div class="arm_default_private_content_editor">
						<?php 
							$default_private_content = get_option('arm_member_default_private_content');
							$default_private_content = !empty($default_private_content) ? stripslashes_deep($default_private_content) : '';
							$arm_message_editor = array(
								'textarea_name' => 'arm_default_private_content',
								'editor_class' => 'arm_default_private_content',
								'media_buttons' => true,
								'textarea_rows' => 10,
								'editor_css' => '<style type="text/css"> body#tinymce{margin:0px !important;} </style>'
							);
							wp_editor($default_private_content, 'arm_default_private_content', $arm_message_editor);

						?>
						</div>
					</td>
				</tr>
				<tr>				
					<td class="arm_padding_top_0">
						<div class="arm_membership_setup_shortcode_box arm_user_private_shortcode">
	                        <span class="arm_shortcode_label arm_margin_0"><?php esc_html_e("Userwise Private Content Shortcode", "ARMember"); ?></span>
	                        <span class="arm_form_shortcode arm_shortcode_text arm_form_shortcode_box arm_margin_bottom_20">
	                            <span class="armCopyText" >[arm_user_private_content]</span>
	                            <span class="arm_click_to_copy_text arm_padding_top_8" data-code="[arm_user_private_content]"><?php esc_html_e("Click to Copy", "ARMember"); ?></span>
	                            <span class="arm_copied_text">
	                                <img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/copied_ok.png'; //phpcs:ignore?>">
	                                <?php esc_html_e("Code Copied", "ARMember"); ?>
	                            </span>
	                        </span>  
	                    </div>
					</td>
					<td class="arm_padding_top_0 arm_vertical_align_top arm_default_content_btn">
						<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_loader_img_defualt_private_content" class="arm_loader_img arm_submit_btn_loader"  style=" display: none;" width="20" height="20" style="position:absolute;top:20px;left:79%"/>
						<button class="arm_save_btn arm_margin_0" value="" id="arm_default_private_content_save" name="arm_default_private_content_save" type="submit"><?php esc_html_e('Save', 'ARMember') ?></button>
					</td>
				</tr>			
			</table>
		</form>
	</div>
</div>

<?php require_once(MEMBERSHIPLITE_VIEWS_DIR.'/arm_view_member_details.php')?>
<?php
	echo $ARMember->arm_get_need_help_html_content('users-private-content-list'); //phpcs:ignore
?>