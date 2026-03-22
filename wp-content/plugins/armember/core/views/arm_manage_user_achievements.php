<?php
global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings,$arm_members_badges,$arm_email_settings,$arm_manage_coupons,$arm_common_lite;
            
$profileTemplate = $ARMember->tbl_arm_member_templates;
$templateOptions = $wpdb->get_var($wpdb->prepare("SELECT `arm_options` FROM {$profileTemplate} WHERE arm_type = %s", 'profile')); //phpcs:ignore --Reason $profileTemplate is a table name
$display_admin_user = 0;
if (!empty($templateOptions)) {
    $templateOptions = maybe_unserialize($templateOptions);
    $display_admin_user = isset($templateOptions['show_admin_users']) ? $templateOptions['show_admin_users'] : 0;
}

if($display_admin_user == 1)
{
    $all_members = $arm_members_class->arm_get_all_members_with_administrators(0,0);
}
else
{
    $all_members = $arm_members_class->arm_get_all_members_without_administrator(0,0);
} 



$users_data = array();
$badges_list = $arm_members_badges->arm_get_all_badges();
$global_settings = $arm_global_settings->global_settings;
$badge_width = !empty($global_settings['badge_width']) ? $global_settings['badge_width'] : 30;
$badge_height = !empty($global_settings['badge_height']) ? $global_settings['badge_height'] : 30;
$badgeIconStyle = "width:" . $badge_width . "px; height:" . $badge_height . "px;";

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
// <![CDATA[
    jQuery(document).ready(function () {
        jQuery('#armember_datatable').dataTable().fnDestroy();
        arm_user_achievements();
    });
    function show_grid_loader() {        
        jQuery(".dataTables_scroll").hide();
        jQuery(".footer").hide();
        jQuery('.arm_loading_grid').show();
    }
    function arm_load_user_achievement_filter() {
        jQuery('#armember_datatable').dataTable().fnDestroy();
        arm_user_achievements();
    }

    jQuery(document).on('keyup','#armmanageuserachievement_new:last-child', function (e) {
        var search = jQuery(this).val();
        jQuery("#armmanageuserachievement_new:last-child").val(search);
        if(e.keyCode == 13 || e.key == 'ENTER')
        {
            jQuery('#armember_datatable').dataTable().fnDestroy();
            arm_user_achievements();
            return false;
        }

    });

    function arm_badges_achievement_grid_filter_grid(){
        jQuery('#armember_datatable').dataTable().fnDestroy();
        arm_user_achievements();
    }
    
    function arm_user_achievements() {
        //action=get_user_achievements
        var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMember')); //phpcs:ignore?>';
        var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing','ARMember').' <span class="arm-black-350 arm_font_size_15">0</span> - <span class="arm-black-350 arm_font_size_15">0</span> of <span class="arm-black-350 arm_font_size_15">0</span> '.esc_html__('User Badges','ARMember')); //phpcs:ignore?>';
        var __ARM_to = '<?php echo addslashes(esc_html__('to','ARMember')); //phpcs:ignore?>';
        var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMember')); //phpcs:ignore?>';
        var __ARM_RECORDS = '<?php echo addslashes(esc_html__('User Badges ','ARMember')); //phpcs:ignore?>';
        var __ARM_Show = '<?php echo addslashes(esc_html__('Show','ARMember')); //phpcs:ignore?>';
        var __ARM_NO_FOUND = '<?php echo addslashes(esc_html__('No any user badge found.','ARMember')); //phpcs:ignore?>';
        var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching records found.','ARMember')); //phpcs:ignore?>';
        var search_term = jQuery("#armmanageuserachievement_new:last-child").val();
        var ajax_url = '<?php echo admin_url('admin-ajax.php?'); //phpcs:ignore ?>';
        var _wpnonce = jQuery('input[name="arm_wp_nonce"]').val();
        var oTables = jQuery('#armember_datatable').dataTable({	
            "sDom": '<"H"Cfr>t<"footer"ipl>',
            "sPaginationType": "four_button",
            "bProcessing": false,
            "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sInfo": __ARM_Showing + " <span class='arm-black-350 arm_font_size_15'>_START_</span> " + __ARM_to + " <span class='arm-black-350 arm_font_size_15'>_END_</span> " + __ARM_of + " <span class='arm-black-350 arm_font_size_15'>_TOTAL_</span> " + __ARM_RECORDS,
                "sInfoEmpty": __ARM_Showing_empty,
                "sLengthMenu": __ARM_Show + "_MENU_"  ,
                "sEmptyTable": __ARM_NO_FOUND,
                "sZeroRecords": __ARM_NO_MATCHING
            },
            "columnDefs": [               
                { "sWidth": "20%", "aTargets": [0] },
                { "sWidth": "30%", "aTargets": [1] },
                {"bSortable": false, "aTargets": [2,3]},
              ],
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod":"POST",
            "fnServerParams":function(aoData){
                aoData.push({"name":"action","value":"get_user_achievements"});
                aoData.push({"name": "sColumns", "value": null});
                aoData.push({"name": "sSearch", "value": search_term});
                aoData.push({"name": "_wpnonce", "value": _wpnonce});
            },
            "bDestroy":true,
            "bRetrieve": false,
            "bJQueryUI": true,
            "bPaginate": true,
            "bStateSave": true,
            "iCookieDuration": 60 * 60,
            "sCookiePrefix": "arm_datatable_",
            "aLengthMenu": [10, 25, 50, 100, 150, 200],
            "iDisplayLength": 10,
            "bAutoWidth" : false,
            "sScrollX": "100%",
            "bScrollCollapse": false,
            "oColVis": {
                "aiExclude": [0, 1, 2, 3]
            },
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
                oData.iStart = 0;
            },
            "fnPreDrawCallback": function () {               
                jQuery(".dataTables_scroll").hide();
                jQuery(".footer").hide();
                jQuery('.arm_loading_grid').show();
                jQuery("#armmanagesearch").remove();
                jQuery(".ColVis_collectionBackground").remove();
                jQuery('.ColVis_collection').remove();
                jQuery('.ColVis_catcher').remove();
            },
            "fnCreatedRow": function( nRow, aData, iDataIndex ) {
                jQuery(nRow).find('.arm_grid_action_btn_container').each(function () {
                    jQuery(this).parent().addClass('armGridActionTD');
                    jQuery(this).parent().attr('data-key', 'armGridActionTD');
                    if(jQuery(this).html()==""){
                        jQuery(this).hide(0); 
                        jQuery(this).parent().remove();
                    }
                });
            },
            "fnDrawCallback": function () {               
                jQuery(".dataTables_scroll").show();
                jQuery(".footer").show();
                jQuery('.arm_loading_grid').hide();
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
        jQuery('div#armember_datatable_filter').parent().append(filter_box);
        jQuery('div#armember_datatable_filter').hide();
        if(search_term != ''){
			jQuery('#armmanageuserachievement_new:last-child').val(search_term)
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
                                <label class="arm_padding_0"><input type="text" placeholder="<?php esc_attr_e( 'Search Users', 'ARMember' ); ?>" id="armmanageuserachievement_new" value="<?php echo esc_attr($filter_search); ?>" tabindex="0"></label>
                            </div>
                        </div>
                        <div class="arm_filter_child_row arm_margin_left_12">
                            <div>
                                <input type="button" class="armemailaddbtn" id="arm_badges_grid_filter_btn" onclick="arm_badges_achievement_grid_filter_grid();" value="<?php esc_html_e('Apply','ARMember');?>">
                                <input type="button" class="arm_cancel_btn arm_margin_left_12 hidden_section" value="<?php esc_html_e('Clear','ARMember');?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form method="GET" id="achive_badges_list_form" class="data_grid_list arm_user_badges_grid_form">
            <input type="hidden" name="page" value="<?php echo esc_attr($get_page); ?>" />
            <input type="hidden" name="armaction" value="list" />
            <div id="armmainformnewlist">
                <div class="arm_loading_grid" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>
                <table cellpadding="0" cellspacing="0" border="0" class="display arm_hide_datatable arm_on_display arm_user_achievements_list_grid" id="armember_datatable"  style="visibility: hidden;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Username', 'ARMember'); ?></th>
                            <th><?php esc_html_e('Email Address', 'ARMember'); ?></th>
                            <th><?php esc_html_e('Badges', 'ARMember'); ?></th>
                            <th data-key="armGridActionTD" class="armGridActionTD noVis" style="display:none"></th>
                        </tr>
                    </thead>

                </table>
                <div class="armclear"></div>
                <input type="hidden" name="search_grid" id="search_grid" value="<?php esc_html_e('Search','ARMember');?>"/>
                <input type="hidden" name="entries_grid" id="entries_grid" value="<?php esc_html_e('members','ARMember');?>"/>
                <input type="hidden" name="show_grid" id="show_grid" value="<?php esc_html_e('Show','ARMember');?>"/>
                <input type="hidden" name="showing_grid" id="showing_grid" value="<?php esc_html_e('Showing','ARMember');?>"/>
                <input type="hidden" name="to_grid" id="to_grid" value="<?php esc_html_e('to','ARMember');?>"/>
                <input type="hidden" name="of_grid" id="of_grid" value="<?php esc_html_e('of','ARMember');?>"/>
                <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php esc_html_e('No matching records found.','ARMember');?>"/>
                <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php esc_html_e('No any user badge found.','ARMember');?>"/>
                <input type="hidden" name="filter_grid" id="filter_grid" value="<?php esc_html_e('filtered from','ARMember');?>"/>
                <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php esc_html_e('total','ARMember');?>"/>
            </div>
            <div class="footer_grid"></div>
        </form>
        <div class="armclear"></div>
	</div>
</div>
<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
<!--./******************** Add User Badges Form ********************/.-->
<div class="add_new_user_badges_wrapper popup_wrapper" >
	<form method="post" action="#" id="arm_add_user_badges_wrapper_frm" class="arm_admin_form arm_add_user_badges_wrapper_frm">
		<table cellspacing="0">
			<tr class="popup_wrapper_inner">	
				<td class="arm_add_user_badges_close_btn arm_popup_close_btn"></td>
				<td class="popup_header"><?php esc_html_e('Add User Badges','ARMember');?></td>
				<td class="popup_content_text">
					<table class="arm_table_label_on_top arm_padding_0">
                        <tr class="form-field">
							<th class="arm_padding_left_0 arm_padding_top_0"><?php esc_html_e('Select Badge Icon','ARMember'); ?></th>
                            <td class="arm_padding_left_0 arm_padding_right_0">
                                <div class="arm_badge_icon_lists arm_required_wrapper arm_width_100_pct">
								<?php 
                                if(!empty($badges_list))
                                {
                                    foreach ($badges_list as $badge) {
										
										$arm_badges_icon = $badge->arm_badges_icon;
										$arm_badges_icon_arr = explode('/', $arm_badges_icon);
										$arm_badges_icon_end = end($arm_badges_icon_arr);
										
                                        if( file_exists(MEMBERSHIP_UPLOAD_DIR.'/social_badges/'.$arm_badges_icon_end) ){
                                            $badge->arm_badges_icon =strstr($badge->arm_badges_icon, "//");
                                        }else if( file_exists(MEMBERSHIP_UPLOAD_DIR.'/social_badges/'.$arm_badges_icon_end) ){
                                        $badge->arm_badges_icon = $badge->arm_badges_icon;
                                        }else{
                                            $badge->arm_badges_icon = $badge->arm_badges_icon;
                                        }
                                        $arm_badges_name = !empty($badge->arm_badges_name) ? stripslashes($badge->arm_badges_name) : '';
                                        echo '<span class="arm_add_user_badges armhelptip_front" data-badge_id="'.esc_attr($badge->arm_badges_id).'" title="'.esc_attr($arm_badges_name).'"><img src="' . esc_attr($badge->arm_badges_icon) . '" alt="" style="'.esc_attr($badgeIconStyle).'" /></span>';
                                    }
                                }
                                else
                                {
                                    echo '<span>'.esc_html_e('No badge found. Create a new badge','ARMember').'</span>';
                                }
								?>
								<input type="hidden" id="arm_add_badges_id" value="" name="arm_user_badge_id" data-msg-required="<?php esc_html_e('Please select a badge icon.', 'ARMember');?>" />
                                </div>
							</td>
						</tr>
                        <tr class="form-field arm_margin_top_8">
							<th class="arm_padding_left_0"><?php esc_html_e('Select Users','ARMember'); ?></th>
                            <td class="arm_required_wrapper arm_multiauto_user_field arm_padding_left_0 arm_padding_right_0 arm_padding_bottom_0">
                                <input id="arm_user_multi_auto_selection" class="arm_max_width_100_pct arm_width_100_pct" type="text" name="arm_user_ids_text" value="" placeholder="<?php esc_html_e('Search by username or email...', 'ARMember');?>" data-msg-required="<?php esc_html_e('Please select user.', 'ARMember');?>" required>
                                <div class="arm_users_multiauto_items arm_required_wrapper" id="arm_users_multiauto_items" style="display: none;"></div>
                                <input type="hidden" name="arm_display_admin_user" id="arm_display_admin_user" value="<?php echo esc_attr($display_admin_user);?>">
								<?php /*?><select id="arm_user_ids_select" class="arm_chosen_selectbox arm_user_badges_add_achievement" data-msg-required="<?php esc_html_e('Please select atleast one user.', 'ARMember');?>" name="arm_user_ids[]" data-placeholder="<?php esc_html_e('Select User(s)..', 'ARMember');?>" multiple="multiple" style="width:500px;">
									<?php if (!empty($all_members)):?>
										<?php foreach ($all_members as $user): ?>
											<option class="arm_message_selectbox_op" value="<?php echo $user->ID;?>"><?php echo $user->user_login;?></option>
										<?php endforeach;?>
									<?php else: ?>
										<option value=""><?php esc_html_e('No User(s) Available', 'ARMember');?></option>
									<?php endif;?>
								</select><?php */?>
							</td>
						</tr>
					</table>
					<div class="armclear"></div>
				</td>
				<td class="popup_content_btn popup_footer arm_padding_top_0" style="border-top : none">
					<div class="popup_content_btn_wrapper arm_margin_0 arm_margin_bottom_20">
						<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif'; //phpcs:ignore ?>" class="arm_loader_img arm_submit_btn_loader" style="top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" width="20" height="20" />
						<button class="arm_cancel_btn arm_add_user_badges_close_btn arm_margin_top_0" type="button"><?php esc_html_e('Cancel','ARMember');?></button>
						<button class="arm_save_btn arm_add_user_badges_save arm_margin_right_0 arm_margin_top_0" type="submit" data-type="add"><?php esc_html_e('Save', 'ARMember') ?></button>
                        <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
                        <input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
					</div>
				</td>
			</tr>
		</table>
		<div class="armclear"></div>
	</form>
</div>
<script type="text/javascript">
    __NO_USER = '<?php echo addslashes( esc_html__('No users(s) Available','ARMember')); //phpcs:ignore?>';
    __SELECT_USER = '<?php echo addslashes( esc_html__('Select user','ARMember')); //phpcs:ignore?>';
    __USER_BADGE_DELETED = '<?php echo addslashes( esc_html__('User badge has been deleted successfully.','ARMember')); //phpcs:ignore?>';
    __USER_BADGE_DELETE_ERROR = '<?php echo addslashes( esc_html__('There is problem while deleting badges.Please try again letter.','ARMember')); //phpcs:ignore?>';

</script>
<div id="arm_user_lists_chosen" style="display:none;visibility: hidden;opacity:0;">
    <?php
        echo json_encode($all_members);
    ?>
</div>