<?php
global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings,$arm_members_badges,$arm_email_settings,$arm_manage_coupons,$arm_common_lite;
$badges_list = $arm_members_badges->arm_get_all_badges();
$bid = 0;
$achievementsList = '';
$ARMember->arm_session_start(true);
$_SESSION['arm_file_upload_arr']['arm_badges_icon'] = "-";
$get_page = !empty($_GET['page']) ? sanitize_text_field( $_GET['page'] ) : '';
$filter_search = !empty($_REQUEST['sSearch']) ? sanitize_text_field( $_REQUEST['sSearch'] ) : '';
?>
<style type="text/css" title="currentStyle">
	.paginate_page a{display:none;}
	#poststuff #post-body {margin-top: 32px;}
	.delete_box{float:left;}
	.row-actions{text-align: center;}
	.ColVis_Button{ display: none !important;}
</style>
<script type="text/javascript" charset="utf-8">
var ARM_IMAGE_URL = "<?php echo MEMBERSHIP_IMAGES_URL; //phpcs:ignore?>";
// <![CDATA[
jQuery(document).ready( function () {
    jQuery('#armember_datatable').dataTable().fnDestroy();
    arm_load_badge_list_grid();
  });

function arm_load_badge_list_filtered_grid(data)
{
    var tbl = jQuery('#armember_datatable').dataTable(); 
    tbl.fnDeleteRow(data);
    jQuery('#armember_datatable').dataTable().fnDestroy();
    arm_load_badge_list_grid();
}

function show_grid_loader() {
    jQuery('.arm_loading_grid').show();
}

jQuery(document).on('keyup','#armmanagebadge_new',function(e){
    var search = jQuery(this).val();       
    jQuery('#armmanagebadge_new').val(search);
    if(e.keyCode == 13)
    {
        jQuery('#armember_datatable').dataTable().fnDestroy();
        arm_load_badge_list_grid();
    }
});

function arm_badges_grid_filter_grid(){
    jQuery('#armember_datatable').dataTable().fnDestroy();
    arm_load_badge_list_grid();
}

function arm_load_badge_list_grid() {
    var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMember')); //phpcs:ignore?>';
    var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing','ARMember').' <span class="arm-black-350 arm_font_size_15">0</span> - <span class="arm-black-350 arm_font_size_15">0</span> of <span class="arm-black-350 arm_font_size_15">0</span> '.esc_html__('badges','ARMember')); //phpcs:ignore?>';
    var __ARM_to = '-';
    var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMember')); //phpcs:ignore?>';
    var __ARM_RECORDS = '<?php echo addslashes(esc_html__('Badges','ARMember')); //phpcs:ignore?>';
    var __ARM_Show = '<?php echo addslashes(esc_html__('Show','ARMember')); //phpcs:ignore?>';
    var __ARM_NO_FOUND = '<?php echo addslashes(esc_html__('No any badge found.','ARMember')); //phpcs:ignore?>';
    var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching records found.','ARMember')); //phpcs:ignore?>';
    var ajax_url = '<?php echo admin_url('admin-ajax.php?'); //phpcs:ignore ?>';
    var _wpnonce = jQuery('input[name="arm_wp_nonce"]').val();
    var sSearch = jQuery('#armmanagebadge_new').val();

    var db_search_term = (typeof sSearch !== 'undefined' && sSearch !== '') ? sSearch : '';
	var oTables = jQuery('#armember_datatable').dataTable( {
		"sDom": '<"H"Cfr>t<"footer"ipl>',
		"sPaginationType": "four_button",
        "oLanguage": {
            "sProcessing": show_grid_loader(),
            "sInfo": __ARM_Showing + " <span class='arm-black-350 arm_font_size_15'>_START_</span> " + __ARM_to + " <span class='arm-black-350 arm_font_size_15'>_END_</span> " + __ARM_of + " <span class='arm-black-350 arm_font_size_15'>_TOTAL_</span> " + __ARM_RECORDS,
            "sInfoEmpty": __ARM_Showing_empty,
            "sLengthMenu": __ARM_Show + "_MENU_",
            "sEmptyTable": __ARM_NO_FOUND,
            "sZeroRecords": __ARM_NO_MATCHING
        },
        "bServerSide": true,
        "sAjaxSource": ajax_url,
        "sServerMethod":"POST",
        "fnServerParams":function(aoData){
            aoData.push({"name":"action","value":"get_user_badges"});   
            aoData.push({"name":"sSearch","value":db_search_term});   
            aoData.push({"name": "_wpnonce", "value": _wpnonce});
        },
        "bRetrieve": false,
		"bJQueryUI": true,
		"bPaginate": true,
		"bAutoWidth" : false,
        "sScrollX": "100%",
        "bScrollCollapse": true,
        "fixedColumns": false,
        "bStateSave": true,
        "columnDefs": [
            { orderable: false, targets: [0,1,2] }
        ],
        "oColVis": {"aiExclude": [0]},
        "iCookieDuration": 60 * 60,
        "sCookiePrefix": "arm_datatable_",
        "aLengthMenu": [10, 25, 50, 100, 150, 200],
        "iDisplayLength": 10,
        "language":{
            "searchPlaceholder": "Search",
            "search":"",
        },
        "fnStateSave": function (oSettings, oData) {
            oData.aaSorting = [];
            oData.abVisCols = [];
            oData.aoSearchCols = [];
            oData.iStart = 0;
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
        },
        "fnPreDrawCallback": function () {
            jQuery(".dataTables_scroll").hide();
            jQuery(".footer").hide();
            jQuery('.arm_loading_grid').show();
        },
        "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            jQuery(nRow).find('.arm_grid_action_btn_container').each(function () {
                jQuery(this).parent().addClass('armGridActionTD');
                jQuery(this).parent().attr('data-key', 'armGridActionTD');
            });
        },
        "fnDrawCallback":function(){
            jQuery('.arm_loading_grid').hide();           
            jQuery('.dataTables_scroll').show();
            jQuery(".footer").show();
            arm_show_data();
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
    if(db_search_term != ''){
        jQuery('.arm_datatable_searchbox').find('#armmanagebadge_new').val(db_search_term);
    }
}
	
					
// ]]>
</script>
<div class="arm_margin_0">
    <div class="page_sub_content arm_padding_0">
        <div class="arm_filter_wrapper" id="arm_filter_wrapper" style="display:none;">
            <div class="arm_datatable_filters_options arm_filters_searchbox">
                <div class='sltstandard'>
                    <div class="arm_confirm_box_btn_container arm_margin_0" bis_skin_checked="1">
                        <div class="arm_dt_filter_block arm_datatable_searchbox">
                            <div class="arm_datatable_filter_item">
                                <label class="arm_padding_0"><input type="text" placeholder="<?php esc_attr_e( 'Search Badges', 'ARMember' ); ?>" id="armmanagebadge_new" value="<?php echo esc_attr($filter_search); ?>" tabindex="0"></label>
                            </div>
                        </div>
                        <div class="arm_filter_child_row arm_margin_left_12">
                            <div>
                                <input type="button" class="armemailaddbtn" id="arm_badges_grid_filter_btn" onclick="arm_badges_grid_filter_grid();" value="<?php esc_html_e('Apply','ARMember');?>">
                                <input type="button" class="arm_cancel_btn arm_margin_left_12 hidden_section" value="<?php esc_html_e('Clear','ARMember');?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form method="GET" id="badges_list_form" class="data_grid_list">
            <input type="hidden" name="page" value="<?php echo esc_attr($get_page); ?>" />
            <input type="hidden" name="armaction" value="list" />
            <div id="armmainformnewlist">		
                <div class="arm_loading_grid" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>						
                <table cellpadding="0" cellspacing="0" border="0" class="display arm_hide_datatable" id="armember_datatable">
                    <thead>
                        <tr>
                            <th class="center arm_text_align_center"><?php esc_html_e('Badge icon', 'ARMember'); ?></th>
                            <th class="arm_text_align_left"><?php esc_html_e('Badge Title', 'ARMember'); ?></th>
                            <th class="arm_text_align_left"><?php esc_html_e('No of Achievement', 'ARMember'); ?></th>
                            <th class="armGridActionTD"></th>
                        </tr>
                    </thead>
                </table>
                <div class="armclear"></div>
                <input type="hidden" name="search_grid" id="search_grid" value="<?php esc_html_e('Search', 'ARMember'); ?>"/>
                <input type="hidden" name="entries_grid" id="entries_grid" value="<?php esc_html_e('badges', 'ARMember'); ?>"/>
                <input type="hidden" name="show_grid" id="show_grid" value="<?php esc_html_e('Show', 'ARMember'); ?>"/>
                <input type="hidden" name="showing_grid" id="showing_grid" value="<?php esc_html_e('Showing', 'ARMember'); ?>"/>
                <input type="hidden" name="to_grid" id="to_grid" value="<?php esc_html_e('to', 'ARMember'); ?>"/>
                <input type="hidden" name="of_grid" id="of_grid" value="<?php esc_html_e('of', 'ARMember'); ?>"/>
                <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php esc_html_e('No matching records found.', 'ARMember'); ?>"/>
                <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php esc_html_e('No any badge found.', 'ARMember'); ?>"/>
                <input type="hidden" name="filter_grid" id="filter_grid" value="<?php esc_html_e('filtered from', 'ARMember'); ?>"/>
                <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php esc_html_e('total', 'ARMember'); ?>"/>
                <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
				<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
            </div>
            <div class="footer_grid"></div>
        </form>
        <div class="armclear"></div>
    </div>
</div>
<!--./******************** Add New Badge Form ********************/.-->
<div class="add_new_badges_wrapper popup_wrapper">
	<form method="post" action="#" id="arm_add_badges_wrapper_frm" class="arm_admin_form arm_add_badges_wrapper_frm">
		<table cellspacing="0">
			<tr class="popup_wrapper_inner">	
				<td class="add_new_badges_close_btn arm_popup_close_btn" style="right : 32px"></td>
				<td class="popup_header arm_font_size_20 arm_font_weight_500"><?php esc_html_e('Add New Badge','ARMember');?></td>
				<td class="popup_content_text arm_padding_32 arm_padding_top_10">
                    <div style="width: 100%;position:absolute;top:50%;left:0;text-align:center;display:none;" class="arm_loading_popup"><img src="<?php echo MEMBERSHIPLITE_IMAGES_URL;?>/arm_loader.gif"></div>
					<table class="arm_table_label_on_top arm_padding_0 arm_badge_popup_content">	
						<tr>
							<th class="arm_padding_0"><?php esc_html_e('Badge Title', 'ARMember');?></th>
							<td class="arm_required_wrapper arm_padding_0 arm_margin_top_12">
								<input type="text" id="arm_badges_name" class="arm_width_100_pct" name="arm_badges_name" data-msg-required="<?php esc_html_e('Title can not be left blank.', 'ARMember');?>" value="" >
							</td>
						</tr>
						<tr>
							<th class="arm_padding_0 arm_margin_top_28"><?php esc_html_e('Badge Icon', 'ARMember');?></th>
							<td class="arm_required_wrapper arm_padding_0 arm_margin_top_12">
								<div id="arm_add_badge_file_container"></div>
								<div id="arm_edit_badge_file_container"></div>
							</td>
						</tr>
                        <?php $nonce = wp_create_nonce('arm_wp_nonce');?>
                        <input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($nonce);?>"/>
					</table>
					<div class="armclear"></div>
				</td>
				<td class="popup_content_btn popup_footer arm_padding_top_0 arm_padding_bottom_0 " style="border-top: none;">
					<div class="popup_content_btn_wrapper add_new_badges_btn_wrapper arm_margin_bottom_32">
						<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif'; //phpcs:ignore?>" id="arm_loader_img" class="arm_loader_img arm_submit_btn_loader" style="top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;<?php echo (is_rtl()) ? 'right:60%' : 'left:60%';?>" width="20" height="20" />
						<input type="hidden" id="arm_badges_id_box" name="edit_id" value="<?php echo esc_attr($bid);?>" />
						<input type="hidden" id="arm_membership_url" name="arm_membership_url" value="<?php echo MEMBERSHIP_URL; //phpcs:ignore?>" />
						<input type="hidden" id="arm_membership_view_url" name="arm_membership_view_url" value="<?php echo MEMBERSHIP_VIEWS_URL; //phpcs:ignore?>" />						
						<?php  $browser_info = $ARMember->getBrowser($_SERVER['HTTP_USER_AGENT']); //phpcs:ignore?>
						<input type="hidden" id="arm_badge_icon_browser_name" name="arm_badge_icon_browser_name" value="<?php echo esc_attr($browser_info['name']);?>" />
						<input type="hidden" id="arm_badge_icon_browser_version" name="arm_badge_icon_browser_version" value="<?php echo esc_attr($browser_info['version']);?>" />
					
						<button class="arm_cancel_btn add_new_badges_close_btn arm_margin_bottom_0 arm_margin_right_10" type="button"><?php esc_html_e('Cancel','ARMember');?></button>
						<button class="arm_save_btn arm_button_manage_badges arm_margin_right_0 arm_margin_bottom_0" type="submit" data-type="add"><?php esc_html_e('Save', 'ARMember') ?></button>
					</div>
				</td>
			</tr>
		</table>
		<div class="armclear"></div>
	</form>
</div>
<!--./******************** Preview Badge Details Form ********************/.-->
<div class="arm_badge_details_popup_container" style="display:none;"></div>

<script type="text/javascript">
    __ARM_ADDNEWBADGE = '<?php echo esc_html__('Add New Badge','ARMember'); ?>';
    __ARM_UPLOAD = '<?php echo esc_html__('Drop file here or click to select.', 'ARMember'); ?>';
    __ARM_REMOVE = '<?php echo esc_html__('Remove','ARMember'); ?>';
    __ARM_SELECTFILE = '<?php echo esc_html__('Please select file','ARMember'); ?>';
    __ARM_INVALIDEFILE = '<?php echo esc_html__('Invalid file selected','ARMember'); ?>';
    var ARM_REMOVE_IMAGE_ICON = '<?php echo MEMBERSHIPLITE_IMAGES_URL?>/delete.svg';
    var ARM_REMOVE_IMAGE_ICON_HOVER = '<?php echo MEMBERSHIPLITE_IMAGES_URL?>/delete_hover.svg';
</script>

<div class="arm_preview_badge_details_popup_wrapper popup_wrapper">
    <div class="popup_wrapper_inner" style="overflow: hidden;">
        <div class="popup_header">
            <span class="popup_close_btn arm_popup_close_btn add_preview_badge_close_btn"></span>
            <span class="add_rule_content"><?php esc_html_e('Achivements','ARMember' );?></span>
        </div>
        <div class="popup_content_text arm_preview_badge_details_wrapper"></div>       
        <div class="armclear"></div>
    </div>
</div>