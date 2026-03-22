<?php
global $arm_common_lite;
$filter_search = (!empty($_POST['sSearch'])) ? sanitize_text_field($_POST['sSearch']) : '';//phpcs:ignore
if (isset($_REQUEST['arm_default_paid_post_save'])) {
	do_action('arm_save_default_paid_post', $_REQUEST);
}

if(!wp_style_is( 'arm_lite_post_metabox_css', 'enqueued' ) && defined('MEMBERSHIPLITE_URL')){
    wp_enqueue_style('arm_lite_post_metabox_css', MEMBERSHIPLITE_URL . '/css/arm_post_metaboxes.css', array(), MEMBERSHIP_VERSION);
}
wp_enqueue_style('arm_post_metaboxes_css', MEMBERSHIP_URL . '/css/arm_post_metaboxes.css', array(), MEMBERSHIP_VERSION);
wp_enqueue_script('arm_tinymce', MEMBERSHIP_URL . '/js/arm_tinymce_member.js', array(), MEMBERSHIP_VERSION);

?>

<style type="text/css" title="currentStyle">
	.paginate_page a{display:none;}
	#poststuff #post-body {margin-top: 32px;}
	.ColVis_Button{display:none;}
</style>

<script type="text/javascript" charset="utf-8">
// <![CDATA[
jQuery(document).ready( function () {
    arm_load_paid_post_list_grid(false);
});

function arm_load_paid_post_list_filtered_grid(data)
{   
    var tbl = jQuery('#armember_datatable').dataTable(); 
        
    tbl.fnDeleteRow(data);
      
    jQuery('#armember_datatable').dataTable().fnDestroy();
    arm_load_paid_post_list_grid();
}

jQuery(document).on('keyup','#armmanagepost_search',function(e){
    var arm_search = jQuery(this).val();
    jQuery('#armmanagepost_search').val(arm_search)
    if(e.keyCode == 13)
    {
        jQuery('#armember_datatable').dataTable().fnDestroy();
        arm_load_paid_post_list_grid();
    }
})

function arm_paid_post_grid_filter_grid(){
    jQuery('#armember_datatable').dataTable().fnDestroy();
    arm_load_paid_post_list_grid();
}

function show_grid_loader() {
    jQuery(".dataTables_scroll").hide();
    jQuery(".footer").hide();
    jQuery('.arm_loading_grid').show();
}

function arm_load_paid_post_list_grid(is_filtered){

	var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMember')); //phpcs:ignore?>';
    var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing','ARMember').' <span class="arm-black-350 arm_font_size_15">0</span> - <span class="arm-black-350 arm_font_size_15">0</span> of <span class="arm-black-350 arm_font_size_15">0</span> '.esc_html__('Posts','ARMember')); //phpcs:ignore?>';
    var __ARM_to = '-';
    var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMember')); //phpcs:ignore?>';
    var __ARM_Entries = ' <?php echo addslashes(esc_html__('Posts','ARMember')); //phpcs:ignore?>';
    var __ARM_Show = '<?php echo addslashes(esc_html__('Show','ARMember')); //phpcs:ignore?> ';
    var __ARM_NO_FOUND = '<?php echo addslashes(esc_html__('No paid post found.','ARMember')); //phpcs:ignore?>';
    var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching records found.','ARMember')); //phpcs:ignore?>';
	var nonce=jQuery('input[name="arm_wp_nonce"]').val();

    var search_term = jQuery('#armmanagepost_search').val();
    var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';

	var ajax_url = '<?php echo esc_url(admin_url("admin-ajax.php")); //phpcs:ignore?>';
	var table = jQuery('#armember_datatable').dataTable({
		"sDom": '<"H"Cfr>t<"footer"ipl>',
		"sPaginationType": "four_button",
		"sProcessing": show_grid_loader(),
        "oLanguage": {
            "sProcessing": show_grid_loader(),
            "sInfo": __ARM_Showing + " <span class='arm-black-350 arm_font_size_15'>_START_</span> " + __ARM_to + " <span class='arm-black-350 arm_font_size_15'>_END_</span> " + __ARM_of + " _TOTAL_ " + __ARM_Entries,
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
			{ "bSortable": false, "aTargets": [1,3,4] },
            { "sClass": "arm_padding_left_24", "aTargets": [1,2,3] }
		],
		"iCookieDuration": 60 * 60,
        "sCookiePrefix": "arm_datatable_",
        "aLengthMenu": [10, 25, 50, 100, 150, 200],
        "fnStateSave": function (oSettings, oData) {
            oData.aaSorting = [];
            oData.abVisCols = [];
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
            aoData.push({'name': 'action', 'value': 'get_paid_post_data'});
            aoData.push({'name': 'sSearch', 'value': db_search_term});
			aoData.push({'name': '_wpnonce', 'value': nonce});
        },
		"fnDrawCallback":function(){
			arm_show_data();
			jQuery('.arm_loading_grid').hide();           
            jQuery(".dataTables_scroll").show();
            jQuery(".footer").show();
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
	// jQuery('#arm_filter_wrapper').remove();
    if(typeof db_search_term != 'undefined'){
		jQuery('#armmanagepost_search:last-child').val(db_search_term);
	}
}
function ChangeID(id) {
	document.getElementById('delete_id').value = id;
}

// ]]>
</script>

<?php

$get_msg = !empty($_GET['msg']) ? esc_html( sanitize_text_field($_GET['msg']) ) : '';
if( isset( $_GET['status'] ) && 'success' == $_GET['status'] ){
	echo "<script type='text/javascript'>";
		echo "jQuery(document).ready(function(){";
			echo "armToast('" . $get_msg . "','success');"; //phpcs:ignore
			echo "var pageurl = ArmRemoveVariableFromURL( document.URL, 'status' );";  
			echo "pageurl = ArmRemoveVariableFromURL( pageurl, 'msg' );";  
			echo "window.history.pushState( { path: pageurl }, '', pageurl );";
		echo "});";
	echo "</script>";
}

$filter_search = (!empty($_POST['search'])) ? sanitize_text_field($_POST['search']) : '';//phpcs:ignore

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();

global $wpdb, $ARMember, $arm_global_settings;
$user_table = $ARMember->tbl_arm_members;
$user_meta_table = $wpdb->usermeta;

$PaidPostContentTypes = array('page' => esc_html__('Page', 'ARMember'), 'post' => esc_html__('Post', 'ARMember'));
$custom_post_types = get_post_types(array('public' => true, '_builtin' => false, 'show_ui' => true), 'objects');
if (!empty($custom_post_types)) {
	foreach ($custom_post_types as $cpt) {
		$PaidPostContentTypes[$cpt->name] = $cpt->label;
	}
}
?>
<div class="wrap arm_page arm_paid_posts_main_wrapper">
	<div class="content_wrapper arm_paid_posts_wrapper arm_position_relative" id="content_wrapper" >
		<div class="arm_loading_grid" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>
		<div class="page_title">
			<?php esc_html_e('Manage Paid Posts','ARMember');?>
			<div class="arm_add_new_item_box">
				<a class="greensavebtn arm_add_paid_post_link" href="javascript:void(0)"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.svg"><span><?php esc_html_e('Add Paid Post', 'ARMember') ?></span></a>
			</div>	
			<div class="armclear"></div>
		</div>		
        <div class="arm_solid_divider"></div>
        <div class="arm_filter_wrapper" id="arm_filter_wrapper" style="display:none;">
            <div class="arm_datatable_filters_options arm_filters_searchbox">
                <div class="sltstandard">
                    <div class="arm_confirm_box_btn_container arm_margin_0" bis_skin_checked="1">
                        <div class="arm_dt_filter_block arm_datatable_searchbox">
                            <div class="arm_datatable_filter_item">
                                <label class="arm_padding_0"><input type="text" placeholder="<?php esc_attr_e( 'Search Post', 'ARMember' ); ?>" id="armmanagepost_search" value="<?php echo esc_attr($filter_search); ?>" tabindex="0"></label>
                            </div>				
                        </div>
                        <div class="arm_filter_child_row arm_margin_left_12">
                            <div>
                                <input type="button" class="armemailaddbtn" id="arm_paid_post_grid_filter_btn" onclick="arm_paid_post_grid_filter_grid();" value="<?php esc_html_e('Apply','ARMember');?>">
                                <input type="button" class="arm_cancel_btn arm_margin_left_12 hidden_section" value="<?php esc_html_e('Clear','ARMember');?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<div class="arm_paid_posts_list arm_main_wrapper_seperator">
			<form method="GET" id="subscription_plans_list_form" class="data_grid_list">
				<input type="hidden" name="page" value="<?php echo isset( $arm_slugs->paid_post ) ? esc_attr($arm_slugs->paid_post) : '';?>" />
				<input type="hidden" name="armaction" value="list" />
				<div id="armmainformnewlist" class="armember_activity_datatable_div ">
					<table cellpadding="0" cellspacing="0" border="0" class="display arm_hide_datatable" id="armember_datatable">
						<thead>
							<tr>
								<?php /*<th style="max-width:140px"><?php esc_html_e('Enable / Disable Paid Post','ARMember');?></th>*/ ?>
								<th class=""><?php esc_html_e('Post ID','ARMember');?></th>
								<th class=""><?php esc_html_e('Paid Post ID','ARMember');?></th>
								<th class=""><?php esc_html_e('Post Title','ARMember');?></th>
								<th class=""><?php esc_html_e('Post Type','ARMember');?></th>
								<th class=""><?php esc_html_e(' Members','ARMember');?></th>
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
		<div class="armclear"></div>
		<br>
		
	</div>
</div>

<?php require_once(MEMBERSHIPLITE_VIEWS_DIR.'/arm_view_member_details.php')?>

<!--./******************** Paid Post Members List ********************/.-->
<div class="arm_members_list_detail_popup popup_wrapper arm_members_list_detail_popup_wrapper" >
	<div class="arm_loading_grid" id="arm_loading_grid_members" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>
    <div class="popup_wrapper_inner" style="overflow: hidden;">
        <div class="popup_header page_title">
            <span class="popup_close_btn arm_popup_close_btn arm_members_list_detail_close_btn"></span>
            <span class="add_rule_content"><?php esc_html_e('Members Details', 'ARMember'); ?><span class="arm_member_paid_post_name"></span></span>
        </div>
        <div class="popup_content_text arm_members_list_detail_popup_text">
            <table width="100%" cellspacing="0" class="display arm_no_margin" id="armember_datatable_1" >
                <thead>
                    <tr>
                        <th class="arm_width_250"><?php esc_html_e('Username', 'ARMember'); ?></th>
                        <th class="arm_width_400"><?php esc_html_e('Email', 'ARMember'); ?></th>
                        <th class="arm-no-sort arm_width_170 center" ><?php esc_html_e('View Detail', 'ARMember'); ?></th>
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
<!--./******************** Add New Paid Post Form ********************/.-->


<div class="arm_paid_post_cycle_detail_popup popup_wrapper arm_import_user_list_detail_popup_wrapper <?php echo (is_rtl()) ? 'arm_page_rtl' : ''; ?>" style="width:850px; min-height: 200px;">    
    <div>
        <div class="popup_header">
            <span class="popup_close_btn arm_popup_close_btn arm_paid_post_cycle_detail_close_btn"></span>
            <input type="hidden" id="arm_edit_plan_user_id" />
            <span class="add_rule_content"><?php esc_html_e('Paid Post Cycles', 'ARMember'); ?> <span class="arm_paid_post_name"></span></span>
        </div>
        <div class="arm_paid_post_cycle_text arm_text_align_center" >
        	<div class="arm_width_100_pct" style="margin: 45px auto;">	<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL."/arm_loader.gif"; //phpcs:ignore?>"></div>
        </div>

    </div>

</div>
<script type="text/javascript">
    __ARM_Showing = '<?php esc_html_e('Showing','ARMember'); ?>';
    __ARM_Showing_empty = '<?php esc_html_e('Showing 0 to 0 of 0 members','ARMember'); ?>';
    __ARM_to = '<?php esc_html_e('to','ARMember'); ?>';
    __ARM_of = '<?php esc_html_e('of','ARMember'); ?>';
    __ARM_members = '<?php esc_html_e('members','ARMember'); ?>';
    __ARM_Show = '<?php esc_html_e('Members per page','ARMember'); ?>';
    __ARM_NO_FOUNT = '<?php esc_html_e('No any member found.','ARMember'); ?>';
    __ARM_NO_MATCHING = '<?php esc_html_e('No matching members found.','ARMember'); ?>';
</script>

<script type="text/javascript" charset="utf-8">
var ARM_IMAGE_URL = "<?php echo MEMBERSHIP_IMAGES_URL; //phpcs:ignore?>";
// <![CDATA[
jQuery(window).on("load", function () {
	document.onkeypress = stopEnterKey;
});
// ]]>
jQuery(document).ready( function ($) {
	jQuery(document).on('click', '.arm_remove_selected_itembox', function () {
		jQuery(this).parents('.arm_paid_post_itembox').remove();
		if(jQuery('#arm_paid_post_items .arm_paid_post_itembox').length == 0) {
			jQuery('#arm_paid_post_items_input').attr('required', 'required');
			jQuery('#arm_paid_post_items').hide();
		}
		return false;
	});	
});



</script>
<?php
	echo $ARMember->arm_get_need_help_html_content('paid-posts-list'); //phpcs:ignore
?>