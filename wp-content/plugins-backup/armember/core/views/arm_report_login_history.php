<?php
global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_subscription_plans, $arm_members_activity, $check_sorting;
$setact = 0;
$setact = $arm_members_activity->$check_sorting();


$page_title = "";
if ( isset($_GET['action']) && $_GET['action'] == 'login_history' ) {
    $page_title = esc_html__("Login Reports", "ARMember");
}


if(!empty($_POST['arm_export_login_history']) && $_POST['arm_export_login_history'] == '1') {//phpcs:ignore
    global $arm_report_analytics;
    $arm_report_analytics->arm_all_user_login_history_page_export_func($_POST);//phpcs:ignore
    exit;
}

?>


<div class="wrap arm_page arm_report_analytics_main_wrapper">
  
    <div class="content_wrapper arm_report_analytics_content arm_report_login_history_content" id="content_wrapper">
        <div class="page_title">
            <?php echo esc_html($page_title); ?>
            <div class="armclear"></div>
            <div class="sltstandard">
                <div class="arm_report_chart_type">
                    <a href="javascript:void(0);" class="btn_chart_type active" id="login_history" onclick="javascript:arm_change_login_hisotry_report('login_history');"><?php echo addslashes(esc_html__('Loggedin History', 'ARMember')); //phpcs:ignore?></a>
                
                    <a href="javascript:void(0);" class="btn_chart_type" id="fail_login_history" onclick="javascript:arm_change_login_hisotry_report('fail_login_history');"><?php echo addslashes(esc_html__('Fail Login Attempt History', 'ARMember')); //phpcs:ignore?></a>
                </div>
                <div class="arm_report_analtics_filter_div">
                    <div class="arm_filter_div">
                        <label class="arm_log_history_search_lbl_user"><input type="text" placeholder="<?php esc_html_e('Search by member', 'ARMember'); ?>" id="arm_log_history_search_user" name="arm_log_history_search_user" value="" tabindex="-1" ></label>
                    </div>
                    <div class="arm_filter_div">
                        <div class="arm_all_loginhistory_filter_inner">
                            <button id="arm_login_history_page_search_btn" class="armemailaddbtn arm_login_history_page_search_btn arm_margin_right_8" type="button"><?php esc_html_e('Apply', 'ARMember'); ?></button>
                            <button id="arm_login_history_page_export_btn" class="arm_cancel_btn arm_margin_0 armhelptip tipso_style " title="<?php esc_html_e( 'Export to CSV', 'ARMember' ); ?>"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore ?>/arm_export_icon_pg.svg" /></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="armclear"></div>

    <?php
    if(isset($_GET['action']) && $_GET['action'] == 'login_history') {
    ?>
        <form  method="post" action="#" id="arm_login_history_page_form" class="arm_block_settings arm_admin_form">
        <table class="form-table">
            <tr class="arm_global_settings_sub_content track_login_history" >
                <td class="arm-form-table-content" colspan="2">
                <?php
                    global $arm_report_analytics;
                    $arm_log_history_search_user = '';
                    $login_history = $arm_report_analytics->arm_get_all_user_for_login_history_page(1, 10, $arm_log_history_search_user);
                ?>
                <?php if(isset($login_history) && !empty($login_history)): ?>
                
                    <div class="arm_view_member_sub_content arm_membership_history_list armPageContainer">
                        <div class="arm_all_loginhistory_main_wrapper" id="arm_all_loginhistory_page_main_wrapper"> 
                            <?php echo $login_history; //phpcs:ignore?>
                        </div>
                    </div>
                <?php
                    $totalRecord = $wpdb->get_var("SELECT COUNT(`arm_history_id`) FROM `" . $ARMember->tbl_arm_login_history . "`"); //phpcs:ignore --Reason Query without where clause so ignore it
                    if ($totalRecord > 0) {}
                ?>
                    
                <?php endif;?>
                                            
                </td>
            </tr>
                            
        </table>
        <input type="hidden" id="arm_login_history_type" name="arm_login_history_type" class="arm_login_history_type" value="login_history" />
        <input type='hidden' name='arm_export_login_history' value='0'>
        <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
        <input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
        </form>
    <?php
    }
    ?>

    </div>
</div>
<script type="text/javascript">
    jQuery(document).on('mouseover','#arm_login_history_page_export_btn',function(){
        jQuery(this).find('img').attr('src','<?php echo MEMBERSHIPLITE_IMAGES_URL; ?>/arm_export_icon_pg_hover.svg');
    });

    jQuery(document).on('mouseout','#arm_login_history_page_export_btn',function(){
        jQuery(this).find('img').attr('src','<?php echo MEMBERSHIPLITE_IMAGES_URL; ?>/arm_export_icon_pg.svg');
    });
</script>
<?php require_once(MEMBERSHIPLITE_VIEWS_DIR.'/arm_view_member_details.php')?>
<?php
    echo $ARMember->arm_get_need_help_html_content('members-report-analysis'); //phpcs:ignore
?>