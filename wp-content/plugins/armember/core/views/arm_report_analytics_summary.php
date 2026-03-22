<?php
global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $is_multiple_membership_feature, $arm_datepicker_loaded, $arm_pay_per_post_feature;
$arm_datepicker_loaded = 1;

$get_action = isset($_GET['action']) ? sanitize_text_field( $_GET['action'] ) : '';
if($arm_pay_per_post_feature->isPayPerPostFeature && !empty($get_action) && ($get_action == "pay_per_post_report"))
{
    $all_active_plans = $arm_subscription_plans->arm_get_paid_post_data();
}
else
{
    $all_active_plans = $arm_subscription_plans->arm_get_all_active_subscription_plans();
}

$payment_gateways = $arm_payment_gateways->arm_get_all_payment_gateways_for_setup();


$year = date("Y");
$month = date("n");
$month_label = "";
$to_year = $year - 12;
$yearLists = "";
$monthLists = "";
$gateways_list = "";

$firstmonth = date('n', strtotime("January"));
for($i = $firstmonth; $i <= 12; $i++){
    // $month_num = $i+1;
    $dateObj   = DateTime::createFromFormat('!m', $i);
    $month_name = $dateObj->format('F');
    $monthLists .= '<li data-label="' . $month_name . '" data-value="'.$i.'">' . $month_name . '</li>';
}

for($i=$year; $i>$year - 12; $i--) {
    $yearLists .= '<li data-label="' . $i . '" data-value="'.$i.'">' . $i . '</li>';
}


$gateways_list = '<li data-label="' . addslashes( esc_attr__('All Gateways', 'ARMember')) . '" data-value="">' . addslashes( esc_html__('All Gateways', 'ARMember') ) . '</li>';
if(!empty($payment_gateways)) {
    foreach ($payment_gateways as $key => $gateways) {
        $gateways_list .= '<li data-label="' . $gateways['gateway_name'] . '" data-value="' . $key . '">' . $gateways['gateway_name'] . '</li>';    
    }
}

$gateways_list .= "<li data-label='".esc_attr__('Manual','ARMember')."' data-value='manual'>".esc_html__('Manual','ARMember')."</li>";

/*$is_wc_feature = get_option('arm_is_woocommerce_feature');
if( '1' == $is_wc_feature ){
    $gateways_list .= '<li data-label="' . addslashes( esc_attr__('WooCommerce', 'ARMember') ) . '" data-value="woocommerce">'.addslashes( esc_html__('WooCommerce','ARMember') ).'</li>';
}*/

//echo "gateways_list : <br>".$gateways_list;die;
$plansLists = '<li data-label="' . addslashes( esc_attr__('All Plans', 'ARMember')) . '" data-value="">' . addslashes( esc_html__('All Plans', 'ARMember') ) . '</li>';
if (!empty($all_active_plans)) {
    foreach ($all_active_plans as $p) {
        $p_id = $p['arm_subscription_plan_id'];
        $plansLists .= '<li data-label="' . esc_attr($p['arm_subscription_plan_name']) . '" data-value="' . $p_id . '">' . esc_attr($p['arm_subscription_plan_name']) . '</li>';
    }
}

$page_title = "";

if (isset($get_action) && $get_action == 'member_report' ) {
    $page_title = esc_html__("Membership Report", "ARMember");
}
if (isset($get_action) && $get_action == 'payment_report' ) {
    $page_title = esc_html__("Payments Report", "ARMember");
}
if (isset($get_action) && $get_action == 'pay_per_post_report' ) {
    $page_title = esc_html__("Paid Post Report", "ARMember");
}
if (isset($get_action) && $get_action == 'coupon_report' ) {
    $page_title = esc_html__("Coupon Report", "ARMember");
}

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();

$log_id = isset($_REQUEST['log_id']) ? $_REQUEST['log_id'] : 0;

if(isset($_POST["arm_export_report_data"]) && $_POST["arm_export_report_data"] == 1) {//phpcs:ignore
    $type = sanitize_text_field($_POST['type']);//phpcs:ignore
    $graph_type = sanitize_text_field($_POST['graph_type']);//phpcs:ignore
    $arm_report_type = isset($_POST['arm_report_type']) ? sanitize_text_field($_POST['arm_report_type']) : '';//phpcs:ignore
    $is_export_to_csv = isset($_POST['is_export_to_csv']) ? intval($_POST['is_export_to_csv']) : false;//phpcs:ignore
    $is_pagination = false;
    require_once(MEMBERSHIP_VIEWS_DIR . '/arm_graph_ajax.php');
    exit;
}

?>

<?php
    $backToListingIcon = MEMBERSHIPLITE_IMAGES_URL . '/back_to_listing_arrow.png';
    if (is_rtl()) {
        $backToListingIcon = MEMBERSHIPLITE_IMAGES_URL . '/back_to_listing_arrow_right.png';
    }
?>
<div class="wrap arm_page arm_report_analytics_main_wrapper">
  
    <div class="content_wrapper arm_report_analytics_content arm_report_analytics_summary" id="content_wrapper">
        <div class="page_title">
            <span><?php echo esc_html($page_title); ?></span>
            <div class="armclear"></div>
            <?php 
            if ($get_action == 'member_report') { ?>
                <div class="sltstandard">
                    <div class="arm_report_chart_type">
                        <a href="javascript:void(0);" class="btn_chart_type" id="daily_members" onclick="javascript:arm_change_graph('daily', 'members');"><?php echo addslashes(esc_html__('Hourly', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="monthly_members" onclick="javascript:arm_change_graph('monthly', 'members');"><?php echo addslashes(esc_html__('Daily', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="yearly_members" onclick="javascript:arm_change_graph('yearly', 'members');"><?php echo addslashes(esc_html__('Monthly', 'ARMember')); //phpcs:ignore?></a>                   
                    </div>
                    
                    <div class="arm_report_analtics_filter_div">
                        <div class="arm_filter_div arm_hide" id="arm_date_filter_item">
                            <div id="arm_date_filter_item" class="arm_filter_status_box arm_import_export_date_fields">
                                <input type="text" id="arm_date_filter" placeholder="Select date" class="arm_datepicker_filter" value="" autocomplete="off">
                                
                            </div>
                        </div>
        
        
                        <div class="arm_filter_div" id="arm_month_filter_item">
                            <div id="arm_month_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_month_filter" class="arm_month_filter" value="<?php echo esc_attr($month); ?>">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3333 2.66666V3.33333C16.8054 3.33503 17.9983 4.52795 18 5.99999V15.3333C17.9983 16.8054 16.8054 17.9983 15.3333 18H4.66666C3.19459 17.9983 2.00168 16.8054 2 15.3333V5.99999C2.00168 4.52795 3.19459 3.33503 4.66666 3.33333V2.66666C4.66666 2.29846 4.96512 2 5.33332 2C5.70152 2 5.99999 2.29846 5.99999 2.66666V3.33333H9.33333V2.66666C9.33333 2.29846 9.63183 2 10 2C10.3682 2 10.6667 2.29846 10.6667 2.66666V3.33333H14V2.66666C14 2.29846 14.2985 2 14.6667 2C15.0349 2 15.3333 2.29846 15.3333 2.66666ZM16.6666 6.00001L16.6666 7.33334H3.33333V6.00001C3.33414 5.26398 3.93063 4.6675 4.66666 4.66668V5.33335C4.66666 5.70155 4.96512 6.00001 5.33332 6.00001C5.70152 6.00001 5.99999 5.70155 5.99999 5.33335V4.66668H9.33331V5.33335C9.33331 5.70155 9.63177 6.00001 9.99997 6.00001C10.3682 6.00001 10.6666 5.70155 10.6666 5.33335V4.66668H14V5.33335C14 5.70155 14.2984 6.00001 14.6666 6.00001C15.0348 6.00001 15.3333 5.70155 15.3333 5.33335V4.66668C16.0693 4.6675 16.6658 5.26398 16.6666 6.00001ZM10.5111 15.5H14.6844C14.7954 15.5 14.8743 15.4778 14.9211 15.4333C14.9737 15.3833 15 15.3056 15 15.2V14.225C15 14.1194 14.9737 14.0444 14.9211 14C14.8743 13.95 14.7954 13.925 14.6844 13.925H12.1857V13.75C12.1857 13.6611 12.2003 13.6028 12.2295 13.575C12.2646 13.5472 12.3259 13.5222 12.4136 13.5L13.606 13.1417C14.0268 13.0194 14.36 12.8472 14.6055 12.625C14.851 12.4028 14.9737 12.0833 14.9737 11.6667V10.8833C14.9737 10.4333 14.8393 10.0917 14.5704 9.85833C14.3074 9.61944 13.9275 9.5 13.4306 9.5H10.8442C10.7332 9.5 10.6514 9.525 10.5988 9.575C10.552 9.61944 10.5286 9.69444 10.5286 9.8V10.775C10.5286 10.8806 10.552 10.9583 10.5988 11.0083C10.6514 11.0528 10.7332 11.075 10.8442 11.075H12.7468C12.8344 11.075 12.8958 11.0917 12.9309 11.125C12.966 11.1528 12.9835 11.2083 12.9835 11.2917C12.9835 11.3806 12.966 11.4472 12.9309 11.4917C12.9017 11.5361 12.8403 11.5694 12.7468 11.5917L11.5719 11.9417C11.3732 11.9972 11.1891 12.0639 11.0196 12.1417C10.8501 12.2194 10.704 12.325 10.5812 12.4583C10.4585 12.5917 10.362 12.7639 10.2919 12.975C10.2276 13.1806 10.1955 13.4444 10.1955 13.7667V15.2C10.1955 15.3056 10.2188 15.3833 10.2656 15.4333C10.3182 15.4778 10.4 15.5 10.5111 15.5ZM9.19083 15.5H5.31563C5.20457 15.5 5.12274 15.4778 5.07014 15.4333C5.02338 15.3833 5 15.3056 5 15.2V14.1917C5 14.0861 5.02338 14.0111 5.07014 13.9667C5.12274 13.9167 5.20457 13.8917 5.31563 13.8917H6.29758V11.1083H5.49098C5.37992 11.1083 5.29809 11.0861 5.24549 11.0417C5.19873 10.9917 5.17535 10.9139 5.17535 10.8083V9.8C5.17535 9.69444 5.19873 9.61944 5.24549 9.575C5.29809 9.525 5.37992 9.5 5.49098 9.5H8.03353C8.14458 9.5 8.22349 9.525 8.27025 9.575C8.32285 9.61944 8.34916 9.69444 8.34916 9.8V13.8917H9.19083C9.30188 13.8917 9.38079 13.9167 9.42755 13.9667C9.48015 14.0111 9.50646 14.0861 9.50646 14.1917V15.2C9.50646 15.3056 9.48015 15.3833 9.42755 15.4333C9.38079 15.4778 9.30188 15.5 9.19083 15.5Z" fill="#9CA7BD"/></svg>
                                        <span><?php echo $month_label; //phpcs:ignore?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_month_filter" data-placeholder="Select Status">
                                            <?php echo $monthLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
        
                        <div class="arm_filter_div" id="arm_year_filter_item">
                            <div id="arm_year_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_year_filter" class="arm_year_filter" value="<?php echo esc_attr($year); ?>">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3333 3.33333V2.66666C15.3333 2.29846 15.0349 2 14.6667 2C14.2985 2 14 2.29846 14 2.66666V3.33333H10.6667V2.66666C10.6667 2.29846 10.3682 2 10 2C9.63183 2 9.33333 2.29846 9.33333 2.66666V3.33333H5.99999V2.66666C5.99999 2.29846 5.70152 2 5.33332 2C4.96512 2 4.66666 2.29846 4.66666 2.66666V3.33333C3.19459 3.33503 2.00168 4.52795 2 5.99999V15.3333C2.00168 16.8054 3.19459 17.9983 4.66666 18H15.3333C16.8054 17.9983 17.9983 16.8054 18 15.3333V5.99999C17.9983 4.52795 16.8054 3.33503 15.3333 3.33333ZM16.6666 7.33334L16.6666 6.00001C16.6658 5.26398 16.0693 4.6675 15.3333 4.66668V5.33335C15.3333 5.70155 15.0348 6.00001 14.6666 6.00001C14.2984 6.00001 14 5.70155 14 5.33335V4.66668H10.6666V5.33335C10.6666 5.70155 10.3682 6.00001 9.99997 6.00001C9.63177 6.00001 9.33331 5.70155 9.33331 5.33335V4.66668H5.99999V5.33335C5.99999 5.70155 5.70152 6.00001 5.33332 6.00001C4.96512 6.00001 4.66666 5.70155 4.66666 5.33335V4.66668C3.93063 4.6675 3.33414 5.26398 3.33333 6.00001V7.33334H16.6666ZM11.9908 15.5H8.11563C8.00457 15.5 7.92274 15.4778 7.87014 15.4333C7.82338 15.3833 7.8 15.3056 7.8 15.2V14.1917C7.8 14.0861 7.82338 14.0111 7.87014 13.9667C7.92274 13.9167 8.00457 13.8917 8.11563 13.8917H9.09758V11.1083H8.29098C8.17992 11.1083 8.09809 11.0861 8.04549 11.0417C7.99873 10.9917 7.97535 10.9139 7.97535 10.8083V9.8C7.97535 9.69444 7.99873 9.61944 8.04549 9.575C8.09809 9.525 8.17992 9.5 8.29098 9.5H10.8335C10.9446 9.5 11.0235 9.525 11.0702 9.575C11.1229 9.61944 11.1492 9.69444 11.1492 9.8V13.8917H11.9908C12.1019 13.8917 12.1808 13.9167 12.2275 13.9667C12.2802 14.0111 12.3065 14.0861 12.3065 14.1917V15.2C12.3065 15.3056 12.2802 15.3833 12.2275 15.4333C12.1808 15.4778 12.1019 15.5 11.9908 15.5Z" fill="#9CA7BD"/></svg>
                                        <span><?php echo esc_html($year); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_year_filter" data-placeholder="Select Status">
                                            <?php echo $yearLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
        
                        <div class="arm_filter_div" id="arm_plan_filter_item">
                            <div id="arm_plan_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_plan_filter" class="arm_plan_filter" value="">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_7522_5770)"><rect x="6" y="2" width="8" height="16" rx="2" fill="#9CA7BD"/><path opacity="0.4" d="M11 4H16C17.1046 4 18 4.89543 18 6V14C18 15.1046 17.1046 16 16 16H11V4Z" fill="#9CA7BD"/><path opacity="0.4" d="M2 6C2 4.89543 2.89543 4 4 4H9V16H4C2.89543 16 2 15.1046 2 14V6Z" fill="#9CA7BD"/></g><defs><clipPath id="clip0_7522_5770"><rect width="16" height="16" fill="white" transform="translate(2 2)"/></clipPath></defs></svg>
                                        <span><?php esc_html_e('All Plans', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_plan_filter" data-placeholder="Select Status">
                                            <?php echo $plansLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="arm_filter_div">
                            <input type="button" class="armemailaddbtn arm_margin_right_8" id="arm_report_apply_filter_button" value="<?php esc_html_e('Apply','ARMember'); ?>" />
                            <button type="button" class="arm_cancel_btn arm_margin_0 arm_margin_right_0 armhelptip tipso_style" id="arm_report_export_button" title="<?php esc_html_e( 'Export to CSV', 'ARMember' ); ?>"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore ?>/arm_export_icon_pg.svg" /></button>
                            <input type="hidden" value="monthly" name="armgraphval_members" id="armgraphval_members" />
                        </div>
                    </div>
                </div>
            <?php } 
            else if ($get_action == 'payment_report') { ?>
                <div class="sltstandard">
                    <div class="arm_report_chart_type">
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="daily_payment_history" onclick="javascript:arm_change_graph('daily', 'payment_history');"><?php echo addslashes(esc_html__('Hourly', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="monthly_payment_history" onclick="javascript:arm_change_graph('monthly', 'payment_history');"><?php echo addslashes(esc_html__('Daily', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="yearly_payment_history" onclick="javascript:arm_change_graph('yearly', 'payment_history');"><?php echo addslashes(esc_html__('Monthly', 'ARMember')); //phpcs:ignore?></a>

                    </div>

                    <div class="arm_report_analtics_filter_div">
                        <div class="arm_filter_div arm_hide" id="arm_date_filter_item">
                            <div id="arm_date_filter_item" class="arm_filter_status_box arm_import_export_date_fields">
                                <input type="text" id="arm_date_filter" placeholder="Select date" class="arm_datepicker_filter" value="" autocomplete="off">  
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_month_filter_item">
                            <div id="arm_month_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_month_filter" class="arm_month_filter" value="<?php echo esc_attr($month); ?>">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3333 2.66666V3.33333C16.8054 3.33503 17.9983 4.52795 18 5.99999V15.3333C17.9983 16.8054 16.8054 17.9983 15.3333 18H4.66666C3.19459 17.9983 2.00168 16.8054 2 15.3333V5.99999C2.00168 4.52795 3.19459 3.33503 4.66666 3.33333V2.66666C4.66666 2.29846 4.96512 2 5.33332 2C5.70152 2 5.99999 2.29846 5.99999 2.66666V3.33333H9.33333V2.66666C9.33333 2.29846 9.63183 2 10 2C10.3682 2 10.6667 2.29846 10.6667 2.66666V3.33333H14V2.66666C14 2.29846 14.2985 2 14.6667 2C15.0349 2 15.3333 2.29846 15.3333 2.66666ZM16.6666 6.00001L16.6666 7.33334H3.33333V6.00001C3.33414 5.26398 3.93063 4.6675 4.66666 4.66668V5.33335C4.66666 5.70155 4.96512 6.00001 5.33332 6.00001C5.70152 6.00001 5.99999 5.70155 5.99999 5.33335V4.66668H9.33331V5.33335C9.33331 5.70155 9.63177 6.00001 9.99997 6.00001C10.3682 6.00001 10.6666 5.70155 10.6666 5.33335V4.66668H14V5.33335C14 5.70155 14.2984 6.00001 14.6666 6.00001C15.0348 6.00001 15.3333 5.70155 15.3333 5.33335V4.66668C16.0693 4.6675 16.6658 5.26398 16.6666 6.00001ZM10.5111 15.5H14.6844C14.7954 15.5 14.8743 15.4778 14.9211 15.4333C14.9737 15.3833 15 15.3056 15 15.2V14.225C15 14.1194 14.9737 14.0444 14.9211 14C14.8743 13.95 14.7954 13.925 14.6844 13.925H12.1857V13.75C12.1857 13.6611 12.2003 13.6028 12.2295 13.575C12.2646 13.5472 12.3259 13.5222 12.4136 13.5L13.606 13.1417C14.0268 13.0194 14.36 12.8472 14.6055 12.625C14.851 12.4028 14.9737 12.0833 14.9737 11.6667V10.8833C14.9737 10.4333 14.8393 10.0917 14.5704 9.85833C14.3074 9.61944 13.9275 9.5 13.4306 9.5H10.8442C10.7332 9.5 10.6514 9.525 10.5988 9.575C10.552 9.61944 10.5286 9.69444 10.5286 9.8V10.775C10.5286 10.8806 10.552 10.9583 10.5988 11.0083C10.6514 11.0528 10.7332 11.075 10.8442 11.075H12.7468C12.8344 11.075 12.8958 11.0917 12.9309 11.125C12.966 11.1528 12.9835 11.2083 12.9835 11.2917C12.9835 11.3806 12.966 11.4472 12.9309 11.4917C12.9017 11.5361 12.8403 11.5694 12.7468 11.5917L11.5719 11.9417C11.3732 11.9972 11.1891 12.0639 11.0196 12.1417C10.8501 12.2194 10.704 12.325 10.5812 12.4583C10.4585 12.5917 10.362 12.7639 10.2919 12.975C10.2276 13.1806 10.1955 13.4444 10.1955 13.7667V15.2C10.1955 15.3056 10.2188 15.3833 10.2656 15.4333C10.3182 15.4778 10.4 15.5 10.5111 15.5ZM9.19083 15.5H5.31563C5.20457 15.5 5.12274 15.4778 5.07014 15.4333C5.02338 15.3833 5 15.3056 5 15.2V14.1917C5 14.0861 5.02338 14.0111 5.07014 13.9667C5.12274 13.9167 5.20457 13.8917 5.31563 13.8917H6.29758V11.1083H5.49098C5.37992 11.1083 5.29809 11.0861 5.24549 11.0417C5.19873 10.9917 5.17535 10.9139 5.17535 10.8083V9.8C5.17535 9.69444 5.19873 9.61944 5.24549 9.575C5.29809 9.525 5.37992 9.5 5.49098 9.5H8.03353C8.14458 9.5 8.22349 9.525 8.27025 9.575C8.32285 9.61944 8.34916 9.69444 8.34916 9.8V13.8917H9.19083C9.30188 13.8917 9.38079 13.9167 9.42755 13.9667C9.48015 14.0111 9.50646 14.0861 9.50646 14.1917V15.2C9.50646 15.3056 9.48015 15.3833 9.42755 15.4333C9.38079 15.4778 9.30188 15.5 9.19083 15.5Z" fill="#9CA7BD"/></svg>
                                        <span><?php echo esc_html($month_label); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_month_filter" data-placeholder="Select Status">
                                            <?php echo $monthLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_year_filter_item">
                            <div id="arm_year_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_year_filter" class="arm_year_filter" value="<?php echo esc_attr($year); ?>">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3333 3.33333V2.66666C15.3333 2.29846 15.0349 2 14.6667 2C14.2985 2 14 2.29846 14 2.66666V3.33333H10.6667V2.66666C10.6667 2.29846 10.3682 2 10 2C9.63183 2 9.33333 2.29846 9.33333 2.66666V3.33333H5.99999V2.66666C5.99999 2.29846 5.70152 2 5.33332 2C4.96512 2 4.66666 2.29846 4.66666 2.66666V3.33333C3.19459 3.33503 2.00168 4.52795 2 5.99999V15.3333C2.00168 16.8054 3.19459 17.9983 4.66666 18H15.3333C16.8054 17.9983 17.9983 16.8054 18 15.3333V5.99999C17.9983 4.52795 16.8054 3.33503 15.3333 3.33333ZM16.6666 7.33334L16.6666 6.00001C16.6658 5.26398 16.0693 4.6675 15.3333 4.66668V5.33335C15.3333 5.70155 15.0348 6.00001 14.6666 6.00001C14.2984 6.00001 14 5.70155 14 5.33335V4.66668H10.6666V5.33335C10.6666 5.70155 10.3682 6.00001 9.99997 6.00001C9.63177 6.00001 9.33331 5.70155 9.33331 5.33335V4.66668H5.99999V5.33335C5.99999 5.70155 5.70152 6.00001 5.33332 6.00001C4.96512 6.00001 4.66666 5.70155 4.66666 5.33335V4.66668C3.93063 4.6675 3.33414 5.26398 3.33333 6.00001V7.33334H16.6666ZM11.9908 15.5H8.11563C8.00457 15.5 7.92274 15.4778 7.87014 15.4333C7.82338 15.3833 7.8 15.3056 7.8 15.2V14.1917C7.8 14.0861 7.82338 14.0111 7.87014 13.9667C7.92274 13.9167 8.00457 13.8917 8.11563 13.8917H9.09758V11.1083H8.29098C8.17992 11.1083 8.09809 11.0861 8.04549 11.0417C7.99873 10.9917 7.97535 10.9139 7.97535 10.8083V9.8C7.97535 9.69444 7.99873 9.61944 8.04549 9.575C8.09809 9.525 8.17992 9.5 8.29098 9.5H10.8335C10.9446 9.5 11.0235 9.525 11.0702 9.575C11.1229 9.61944 11.1492 9.69444 11.1492 9.8V13.8917H11.9908C12.1019 13.8917 12.1808 13.9167 12.2275 13.9667C12.2802 14.0111 12.3065 14.0861 12.3065 14.1917V15.2C12.3065 15.3056 12.2802 15.3833 12.2275 15.4333C12.1808 15.4778 12.1019 15.5 11.9908 15.5Z" fill="#9CA7BD"/></svg>
                                        <span><?php echo esc_html($year); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_year_filter" data-placeholder="Select Status">
                                            <?php echo $yearLists; //phpcs:ignore ?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_plan_filter_item">
                            <div id="arm_plan_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_plan_filter" class="arm_plan_filter" value="">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_7522_5770)"><rect x="6" y="2" width="8" height="16" rx="2" fill="#9CA7BD"/><path opacity="0.4" d="M11 4H16C17.1046 4 18 4.89543 18 6V14C18 15.1046 17.1046 16 16 16H11V4Z" fill="#9CA7BD"/><path opacity="0.4" d="M2 6C2 4.89543 2.89543 4 4 4H9V16H4C2.89543 16 2 15.1046 2 14V6Z" fill="#9CA7BD"/></g><defs><clipPath id="clip0_7522_5770"><rect width="16" height="16" fill="white" transform="translate(2 2)"/></clipPath></defs></svg>
                                        <span><?php esc_html_e('All Plans', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_plan_filter" data-placeholder="Select Status">
                                            <?php echo $plansLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_gateway_filter_item">
                            <div id="arm_gateway_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_gateway_filter" class="arm_gateway_filter" value="">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_7528_8356)"><path d="M10 1.5C5.30576 1.5 1.5 5.30576 1.5 10C1.5 14.6942 5.30576 18.5 10 18.5C14.6942 18.5 18.5 14.6942 18.5 10C18.5 5.30576 14.6942 1.5 10 1.5ZM12.4416 13.351C12.0708 13.8023 11.5881 14.1066 11.0249 14.2605C10.78 14.327 10.6681 14.4564 10.6821 14.7117C10.6926 14.9636 10.6821 15.2119 10.6786 15.4638C10.6786 15.6877 10.5632 15.8066 10.3428 15.8136C10.1994 15.8171 10.056 15.8206 9.91255 15.8206C9.78663 15.8206 9.6607 15.8206 9.53477 15.8171C9.29691 15.8136 9.18498 15.6772 9.18498 15.4463C9.18148 15.2644 9.18148 15.079 9.18148 14.8971C9.17798 14.4914 9.16399 14.4774 8.77572 14.4144C8.27901 14.334 7.7893 14.222 7.33457 14.0016C6.97778 13.8267 6.9393 13.7393 7.04074 13.3615C7.1177 13.0817 7.19465 12.8019 7.2821 12.5255C7.34506 12.3226 7.40453 12.2317 7.51296 12.2317C7.57593 12.2317 7.65638 12.2632 7.76482 12.3191C8.26852 12.5815 8.8037 12.7284 9.36687 12.7984C9.46132 12.8088 9.55576 12.8158 9.65021 12.8158C9.91255 12.8158 10.1679 12.7669 10.4163 12.6584C11.0424 12.3856 11.1403 11.6615 10.6121 11.2278C10.4337 11.0809 10.2274 10.9724 10.014 10.878C9.46482 10.6366 8.89465 10.4547 8.37696 10.1434C7.53745 9.63971 7.00576 8.95062 7.06872 7.92922C7.13868 6.7749 7.7928 6.05432 8.85267 5.66955C9.28992 5.51214 9.29342 5.51564 9.29342 5.06091C9.29342 4.907 9.28992 4.75309 9.29691 4.59568C9.30741 4.25288 9.36338 4.19342 9.70617 4.18292C9.74465 4.18292 9.78663 4.18292 9.8251 4.18292C9.89156 4.18292 9.95802 4.18292 10.0245 4.18292C10.0525 4.18292 10.0805 4.18292 10.1049 4.18292C10.7556 4.18292 10.7556 4.21091 10.7591 4.91399C10.7626 5.43169 10.7626 5.43169 11.2767 5.51214C11.672 5.5751 12.0463 5.69054 12.4101 5.85144C12.6095 5.93889 12.6864 6.07881 12.6235 6.29218C12.5325 6.607 12.4451 6.92531 12.3471 7.23663C12.2842 7.42551 12.2247 7.51296 12.1128 7.51296C12.0498 7.51296 11.9728 7.48848 11.8749 7.43951C11.3712 7.19465 10.843 7.07572 10.2903 7.07572C10.2204 7.07572 10.1469 7.07922 10.077 7.08272C9.91255 7.09321 9.75165 7.1142 9.59774 7.18066C9.05206 7.41852 8.96461 8.02016 9.42984 8.39095C9.6642 8.57984 9.93354 8.71276 10.2099 8.82819C10.6926 9.02757 11.1753 9.21996 11.6335 9.47181C13.0747 10.2763 13.4665 12.1058 12.4416 13.351Z" fill="#9CA7BD"/></g><defs><clipPath id="clip0_7528_8356"><rect width="17" height="17" fill="white" transform="translate(1.5 1.5)"/></clipPath></defs></svg>
                                        <span><?php esc_html_e('All Gateways', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_gateway_filter" data-placeholder="Select Status">
                                            <?php echo $gateways_list; //phpcs:ignore?>
                                            <?php ?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="arm_filter_div">
                            <input type="button" class="armemailaddbtn arm_margin_right_8" id="arm_report_apply_filter_button" value="<?php esc_html_e('Apply','ARMember'); ?>" />
                            <button class="arm_cancel_btn arm_margin_0 arm_margin_right_0 armhelptip tipso_style" id="arm_report_export_button" title="<?php esc_html_e( 'Export to CSV', 'ARMember' ); ?>"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore ?>/arm_export_icon_pg.svg" /></button>
                            <input type="hidden" value="monthly" name="armgraphval_payment_history" id="armgraphval_payment_history" />
                        </div>
                    </div>
                </div>
                <?php
            }
            else if ($get_action == 'pay_per_post_report') { ?>
                <div class="sltstandard">
                    <div class="arm_report_chart_type">
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="daily_pay_per_post_report" onclick="javascript:arm_change_graph('daily', 'pay_per_post_report');"><?php echo addslashes(esc_html__('Hourly', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="monthly_pay_per_post_report" onclick="javascript:arm_change_graph('monthly', 'pay_per_post_report');"><?php echo addslashes(esc_html__('Daily', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="yearly_pay_per_post_report" onclick="javascript:arm_change_graph('yearly', 'pay_per_post_report');"><?php echo addslashes(esc_html__('Monthly', 'ARMember')); //phpcs:ignore?></a>
                    </div>

                    <div class="arm_report_analtics_filter_div">
                        <div class="arm_filter_div arm_hide" id="arm_date_filter_item">
                            <div id="arm_date_filter_item" class="arm_filter_status_box arm_import_export_date_fields">
                                <input type="text" id="arm_date_filter" placeholder="Select date" class="arm_datepicker_filter" value="" autocomplete="off">  
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_month_filter_item">
                            <div id="arm_month_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_month_filter" class="arm_month_filter" value="<?php echo esc_attr($month); ?>">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3333 2.66666V3.33333C16.8054 3.33503 17.9983 4.52795 18 5.99999V15.3333C17.9983 16.8054 16.8054 17.9983 15.3333 18H4.66666C3.19459 17.9983 2.00168 16.8054 2 15.3333V5.99999C2.00168 4.52795 3.19459 3.33503 4.66666 3.33333V2.66666C4.66666 2.29846 4.96512 2 5.33332 2C5.70152 2 5.99999 2.29846 5.99999 2.66666V3.33333H9.33333V2.66666C9.33333 2.29846 9.63183 2 10 2C10.3682 2 10.6667 2.29846 10.6667 2.66666V3.33333H14V2.66666C14 2.29846 14.2985 2 14.6667 2C15.0349 2 15.3333 2.29846 15.3333 2.66666ZM16.6666 6.00001L16.6666 7.33334H3.33333V6.00001C3.33414 5.26398 3.93063 4.6675 4.66666 4.66668V5.33335C4.66666 5.70155 4.96512 6.00001 5.33332 6.00001C5.70152 6.00001 5.99999 5.70155 5.99999 5.33335V4.66668H9.33331V5.33335C9.33331 5.70155 9.63177 6.00001 9.99997 6.00001C10.3682 6.00001 10.6666 5.70155 10.6666 5.33335V4.66668H14V5.33335C14 5.70155 14.2984 6.00001 14.6666 6.00001C15.0348 6.00001 15.3333 5.70155 15.3333 5.33335V4.66668C16.0693 4.6675 16.6658 5.26398 16.6666 6.00001ZM10.5111 15.5H14.6844C14.7954 15.5 14.8743 15.4778 14.9211 15.4333C14.9737 15.3833 15 15.3056 15 15.2V14.225C15 14.1194 14.9737 14.0444 14.9211 14C14.8743 13.95 14.7954 13.925 14.6844 13.925H12.1857V13.75C12.1857 13.6611 12.2003 13.6028 12.2295 13.575C12.2646 13.5472 12.3259 13.5222 12.4136 13.5L13.606 13.1417C14.0268 13.0194 14.36 12.8472 14.6055 12.625C14.851 12.4028 14.9737 12.0833 14.9737 11.6667V10.8833C14.9737 10.4333 14.8393 10.0917 14.5704 9.85833C14.3074 9.61944 13.9275 9.5 13.4306 9.5H10.8442C10.7332 9.5 10.6514 9.525 10.5988 9.575C10.552 9.61944 10.5286 9.69444 10.5286 9.8V10.775C10.5286 10.8806 10.552 10.9583 10.5988 11.0083C10.6514 11.0528 10.7332 11.075 10.8442 11.075H12.7468C12.8344 11.075 12.8958 11.0917 12.9309 11.125C12.966 11.1528 12.9835 11.2083 12.9835 11.2917C12.9835 11.3806 12.966 11.4472 12.9309 11.4917C12.9017 11.5361 12.8403 11.5694 12.7468 11.5917L11.5719 11.9417C11.3732 11.9972 11.1891 12.0639 11.0196 12.1417C10.8501 12.2194 10.704 12.325 10.5812 12.4583C10.4585 12.5917 10.362 12.7639 10.2919 12.975C10.2276 13.1806 10.1955 13.4444 10.1955 13.7667V15.2C10.1955 15.3056 10.2188 15.3833 10.2656 15.4333C10.3182 15.4778 10.4 15.5 10.5111 15.5ZM9.19083 15.5H5.31563C5.20457 15.5 5.12274 15.4778 5.07014 15.4333C5.02338 15.3833 5 15.3056 5 15.2V14.1917C5 14.0861 5.02338 14.0111 5.07014 13.9667C5.12274 13.9167 5.20457 13.8917 5.31563 13.8917H6.29758V11.1083H5.49098C5.37992 11.1083 5.29809 11.0861 5.24549 11.0417C5.19873 10.9917 5.17535 10.9139 5.17535 10.8083V9.8C5.17535 9.69444 5.19873 9.61944 5.24549 9.575C5.29809 9.525 5.37992 9.5 5.49098 9.5H8.03353C8.14458 9.5 8.22349 9.525 8.27025 9.575C8.32285 9.61944 8.34916 9.69444 8.34916 9.8V13.8917H9.19083C9.30188 13.8917 9.38079 13.9167 9.42755 13.9667C9.48015 14.0111 9.50646 14.0861 9.50646 14.1917V15.2C9.50646 15.3056 9.48015 15.3833 9.42755 15.4333C9.38079 15.4778 9.30188 15.5 9.19083 15.5Z" fill="#9CA7BD"/></svg>
                                        <span><?php echo esc_html($month_label); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_month_filter" data-placeholder="Select Status">
                                            <?php echo $monthLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_year_filter_item">
                            <div id="arm_year_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_year_filter" class="arm_year_filter" value="<?php echo esc_attr($year); ?>">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3333 3.33333V2.66666C15.3333 2.29846 15.0349 2 14.6667 2C14.2985 2 14 2.29846 14 2.66666V3.33333H10.6667V2.66666C10.6667 2.29846 10.3682 2 10 2C9.63183 2 9.33333 2.29846 9.33333 2.66666V3.33333H5.99999V2.66666C5.99999 2.29846 5.70152 2 5.33332 2C4.96512 2 4.66666 2.29846 4.66666 2.66666V3.33333C3.19459 3.33503 2.00168 4.52795 2 5.99999V15.3333C2.00168 16.8054 3.19459 17.9983 4.66666 18H15.3333C16.8054 17.9983 17.9983 16.8054 18 15.3333V5.99999C17.9983 4.52795 16.8054 3.33503 15.3333 3.33333ZM16.6666 7.33334L16.6666 6.00001C16.6658 5.26398 16.0693 4.6675 15.3333 4.66668V5.33335C15.3333 5.70155 15.0348 6.00001 14.6666 6.00001C14.2984 6.00001 14 5.70155 14 5.33335V4.66668H10.6666V5.33335C10.6666 5.70155 10.3682 6.00001 9.99997 6.00001C9.63177 6.00001 9.33331 5.70155 9.33331 5.33335V4.66668H5.99999V5.33335C5.99999 5.70155 5.70152 6.00001 5.33332 6.00001C4.96512 6.00001 4.66666 5.70155 4.66666 5.33335V4.66668C3.93063 4.6675 3.33414 5.26398 3.33333 6.00001V7.33334H16.6666ZM11.9908 15.5H8.11563C8.00457 15.5 7.92274 15.4778 7.87014 15.4333C7.82338 15.3833 7.8 15.3056 7.8 15.2V14.1917C7.8 14.0861 7.82338 14.0111 7.87014 13.9667C7.92274 13.9167 8.00457 13.8917 8.11563 13.8917H9.09758V11.1083H8.29098C8.17992 11.1083 8.09809 11.0861 8.04549 11.0417C7.99873 10.9917 7.97535 10.9139 7.97535 10.8083V9.8C7.97535 9.69444 7.99873 9.61944 8.04549 9.575C8.09809 9.525 8.17992 9.5 8.29098 9.5H10.8335C10.9446 9.5 11.0235 9.525 11.0702 9.575C11.1229 9.61944 11.1492 9.69444 11.1492 9.8V13.8917H11.9908C12.1019 13.8917 12.1808 13.9167 12.2275 13.9667C12.2802 14.0111 12.3065 14.0861 12.3065 14.1917V15.2C12.3065 15.3056 12.2802 15.3833 12.2275 15.4333C12.1808 15.4778 12.1019 15.5 11.9908 15.5Z" fill="#9CA7BD"/></svg>
                                        <span><?php echo esc_html($year); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_year_filter" data-placeholder="Select Status">
                                            <?php echo $yearLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_plan_filter_item">
                            <div id="arm_plan_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_plan_filter" class="arm_plan_filter" value="">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_7522_5770)"><rect x="6" y="2" width="8" height="16" rx="2" fill="#9CA7BD"/><path opacity="0.4" d="M11 4H16C17.1046 4 18 4.89543 18 6V14C18 15.1046 17.1046 16 16 16H11V4Z" fill="#9CA7BD"/><path opacity="0.4" d="M2 6C2 4.89543 2.89543 4 4 4H9V16H4C2.89543 16 2 15.1046 2 14V6Z" fill="#9CA7BD"/></g><defs><clipPath id="clip0_7522_5770"><rect width="16" height="16" fill="white" transform="translate(2 2)"/></clipPath></defs></svg>
                                        <span><?php esc_html_e('All Plans', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_plan_filter" data-placeholder="Select Status">
                                            <?php echo $plansLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_gateway_filter_item">
                            <div id="arm_gateway_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_gateway_filter" class="arm_gateway_filter" value="">
                                <dl class="arm_selectbox arm_width_220">
                                    <dt>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_7528_8356)"><path d="M10 1.5C5.30576 1.5 1.5 5.30576 1.5 10C1.5 14.6942 5.30576 18.5 10 18.5C14.6942 18.5 18.5 14.6942 18.5 10C18.5 5.30576 14.6942 1.5 10 1.5ZM12.4416 13.351C12.0708 13.8023 11.5881 14.1066 11.0249 14.2605C10.78 14.327 10.6681 14.4564 10.6821 14.7117C10.6926 14.9636 10.6821 15.2119 10.6786 15.4638C10.6786 15.6877 10.5632 15.8066 10.3428 15.8136C10.1994 15.8171 10.056 15.8206 9.91255 15.8206C9.78663 15.8206 9.6607 15.8206 9.53477 15.8171C9.29691 15.8136 9.18498 15.6772 9.18498 15.4463C9.18148 15.2644 9.18148 15.079 9.18148 14.8971C9.17798 14.4914 9.16399 14.4774 8.77572 14.4144C8.27901 14.334 7.7893 14.222 7.33457 14.0016C6.97778 13.8267 6.9393 13.7393 7.04074 13.3615C7.1177 13.0817 7.19465 12.8019 7.2821 12.5255C7.34506 12.3226 7.40453 12.2317 7.51296 12.2317C7.57593 12.2317 7.65638 12.2632 7.76482 12.3191C8.26852 12.5815 8.8037 12.7284 9.36687 12.7984C9.46132 12.8088 9.55576 12.8158 9.65021 12.8158C9.91255 12.8158 10.1679 12.7669 10.4163 12.6584C11.0424 12.3856 11.1403 11.6615 10.6121 11.2278C10.4337 11.0809 10.2274 10.9724 10.014 10.878C9.46482 10.6366 8.89465 10.4547 8.37696 10.1434C7.53745 9.63971 7.00576 8.95062 7.06872 7.92922C7.13868 6.7749 7.7928 6.05432 8.85267 5.66955C9.28992 5.51214 9.29342 5.51564 9.29342 5.06091C9.29342 4.907 9.28992 4.75309 9.29691 4.59568C9.30741 4.25288 9.36338 4.19342 9.70617 4.18292C9.74465 4.18292 9.78663 4.18292 9.8251 4.18292C9.89156 4.18292 9.95802 4.18292 10.0245 4.18292C10.0525 4.18292 10.0805 4.18292 10.1049 4.18292C10.7556 4.18292 10.7556 4.21091 10.7591 4.91399C10.7626 5.43169 10.7626 5.43169 11.2767 5.51214C11.672 5.5751 12.0463 5.69054 12.4101 5.85144C12.6095 5.93889 12.6864 6.07881 12.6235 6.29218C12.5325 6.607 12.4451 6.92531 12.3471 7.23663C12.2842 7.42551 12.2247 7.51296 12.1128 7.51296C12.0498 7.51296 11.9728 7.48848 11.8749 7.43951C11.3712 7.19465 10.843 7.07572 10.2903 7.07572C10.2204 7.07572 10.1469 7.07922 10.077 7.08272C9.91255 7.09321 9.75165 7.1142 9.59774 7.18066C9.05206 7.41852 8.96461 8.02016 9.42984 8.39095C9.6642 8.57984 9.93354 8.71276 10.2099 8.82819C10.6926 9.02757 11.1753 9.21996 11.6335 9.47181C13.0747 10.2763 13.4665 12.1058 12.4416 13.351Z" fill="#9CA7BD"/></g><defs><clipPath id="clip0_7528_8356"><rect width="17" height="17" fill="white" transform="translate(1.5 1.5)"/></clipPath></defs></svg>
                                        <span><?php esc_html_e('All Gateways', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_gateway_filter" data-placeholder="Select Status">
                                            <?php echo $gateways_list; //phpcs:ignore?>
                                            <?php ?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="arm_filter_div">
                            <input type="button" class="armemailaddbtn arm_margin_right_8" id="arm_report_apply_filter_button" value="<?php esc_html_e('Apply','ARMember'); ?>" />
                            <button type="button" class="arm_cancel_btn arm_margin_right_0 arm_margin_0 armhelptip tipso_style" id="arm_report_export_button" title="<?php esc_html_e( 'Export to CSV', 'ARMember' ); ?>"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore ?>/arm_export_icon_pg.svg" /></button>
                            <input type="hidden" value="monthly" name="armgraphval_pay_per_post_report" id="armgraphval_pay_per_post_report" />
                        </div>
                    </div>
                </div>
            <?php } else if ($get_action == 'coupon_report') { ?>
                    <div class="sltstandard">
                        <div class="arm_report_chart_type">
                            <a href="javascript:void(0);" class="btn_chart_type" id="daily_coupon_report" onclick="javascript:arm_change_graph('daily', 'coupon_report');"><?php echo addslashes(esc_html__('Hourly', 'ARMember')); //phpcs:ignore?></a>
                        
                            <a href="javascript:void(0);" class="btn_chart_type" id="monthly_coupon_report" onclick="javascript:arm_change_graph('monthly', 'coupon_report');"><?php echo addslashes(esc_html__('Daily', 'ARMember')); //phpcs:ignore?></a>
                        
                            <a href="javascript:void(0);" class="btn_chart_type" id="yearly_coupon_report" onclick="javascript:arm_change_graph('yearly', 'coupon_report');"><?php echo addslashes(esc_html__('Monthly', 'ARMember')); //phpcs:ignore?></a>
                        </div>
                        <div class="arm_report_analtics_filter_div arm_coupon_report_filter">
                            <div class="arm_filter_div arm_hide" id="arm_date_filter_item">
                                <div id="arm_date_filter_item" class="arm_filter_status_box arm_import_export_date_fields">
                                    <input type="text" id="arm_date_filter" placeholder="Select date" class="arm_datepicker_filter" value="" autocomplete="off">  
                                </div>
                            </div>
    
                            <div class="arm_filter_div" id="arm_month_filter_item">
                                <div id="arm_month_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                    <input type="hidden" id="arm_month_filter" class="arm_month_filter" value="<?php echo esc_attr($month); ?>">
                                    <dl class="arm_selectbox arm_width_220">
                                        <dt>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3333 2.66666V3.33333C16.8054 3.33503 17.9983 4.52795 18 5.99999V15.3333C17.9983 16.8054 16.8054 17.9983 15.3333 18H4.66666C3.19459 17.9983 2.00168 16.8054 2 15.3333V5.99999C2.00168 4.52795 3.19459 3.33503 4.66666 3.33333V2.66666C4.66666 2.29846 4.96512 2 5.33332 2C5.70152 2 5.99999 2.29846 5.99999 2.66666V3.33333H9.33333V2.66666C9.33333 2.29846 9.63183 2 10 2C10.3682 2 10.6667 2.29846 10.6667 2.66666V3.33333H14V2.66666C14 2.29846 14.2985 2 14.6667 2C15.0349 2 15.3333 2.29846 15.3333 2.66666ZM16.6666 6.00001L16.6666 7.33334H3.33333V6.00001C3.33414 5.26398 3.93063 4.6675 4.66666 4.66668V5.33335C4.66666 5.70155 4.96512 6.00001 5.33332 6.00001C5.70152 6.00001 5.99999 5.70155 5.99999 5.33335V4.66668H9.33331V5.33335C9.33331 5.70155 9.63177 6.00001 9.99997 6.00001C10.3682 6.00001 10.6666 5.70155 10.6666 5.33335V4.66668H14V5.33335C14 5.70155 14.2984 6.00001 14.6666 6.00001C15.0348 6.00001 15.3333 5.70155 15.3333 5.33335V4.66668C16.0693 4.6675 16.6658 5.26398 16.6666 6.00001ZM10.5111 15.5H14.6844C14.7954 15.5 14.8743 15.4778 14.9211 15.4333C14.9737 15.3833 15 15.3056 15 15.2V14.225C15 14.1194 14.9737 14.0444 14.9211 14C14.8743 13.95 14.7954 13.925 14.6844 13.925H12.1857V13.75C12.1857 13.6611 12.2003 13.6028 12.2295 13.575C12.2646 13.5472 12.3259 13.5222 12.4136 13.5L13.606 13.1417C14.0268 13.0194 14.36 12.8472 14.6055 12.625C14.851 12.4028 14.9737 12.0833 14.9737 11.6667V10.8833C14.9737 10.4333 14.8393 10.0917 14.5704 9.85833C14.3074 9.61944 13.9275 9.5 13.4306 9.5H10.8442C10.7332 9.5 10.6514 9.525 10.5988 9.575C10.552 9.61944 10.5286 9.69444 10.5286 9.8V10.775C10.5286 10.8806 10.552 10.9583 10.5988 11.0083C10.6514 11.0528 10.7332 11.075 10.8442 11.075H12.7468C12.8344 11.075 12.8958 11.0917 12.9309 11.125C12.966 11.1528 12.9835 11.2083 12.9835 11.2917C12.9835 11.3806 12.966 11.4472 12.9309 11.4917C12.9017 11.5361 12.8403 11.5694 12.7468 11.5917L11.5719 11.9417C11.3732 11.9972 11.1891 12.0639 11.0196 12.1417C10.8501 12.2194 10.704 12.325 10.5812 12.4583C10.4585 12.5917 10.362 12.7639 10.2919 12.975C10.2276 13.1806 10.1955 13.4444 10.1955 13.7667V15.2C10.1955 15.3056 10.2188 15.3833 10.2656 15.4333C10.3182 15.4778 10.4 15.5 10.5111 15.5ZM9.19083 15.5H5.31563C5.20457 15.5 5.12274 15.4778 5.07014 15.4333C5.02338 15.3833 5 15.3056 5 15.2V14.1917C5 14.0861 5.02338 14.0111 5.07014 13.9667C5.12274 13.9167 5.20457 13.8917 5.31563 13.8917H6.29758V11.1083H5.49098C5.37992 11.1083 5.29809 11.0861 5.24549 11.0417C5.19873 10.9917 5.17535 10.9139 5.17535 10.8083V9.8C5.17535 9.69444 5.19873 9.61944 5.24549 9.575C5.29809 9.525 5.37992 9.5 5.49098 9.5H8.03353C8.14458 9.5 8.22349 9.525 8.27025 9.575C8.32285 9.61944 8.34916 9.69444 8.34916 9.8V13.8917H9.19083C9.30188 13.8917 9.38079 13.9167 9.42755 13.9667C9.48015 14.0111 9.50646 14.0861 9.50646 14.1917V15.2C9.50646 15.3056 9.48015 15.3833 9.42755 15.4333C9.38079 15.4778 9.30188 15.5 9.19083 15.5Z" fill="#9CA7BD"/></svg>
                                            <span><?php echo esc_html($month_label); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                        </dt>
                                        <dd>
                                            <ul data-id="arm_month_filter" data-placeholder="Select Status">
                                                <?php echo $monthLists; //phpcs:ignore?>
                                            </ul>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
    
                            <div class="arm_filter_div" id="arm_year_filter_item">
                                <div id="arm_year_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                    <input type="hidden" id="arm_year_filter" class="arm_year_filter" value="<?php echo esc_attr($year); ?>">
                                    <dl class="arm_selectbox arm_width_220">
                                        <dt>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3333 3.33333V2.66666C15.3333 2.29846 15.0349 2 14.6667 2C14.2985 2 14 2.29846 14 2.66666V3.33333H10.6667V2.66666C10.6667 2.29846 10.3682 2 10 2C9.63183 2 9.33333 2.29846 9.33333 2.66666V3.33333H5.99999V2.66666C5.99999 2.29846 5.70152 2 5.33332 2C4.96512 2 4.66666 2.29846 4.66666 2.66666V3.33333C3.19459 3.33503 2.00168 4.52795 2 5.99999V15.3333C2.00168 16.8054 3.19459 17.9983 4.66666 18H15.3333C16.8054 17.9983 17.9983 16.8054 18 15.3333V5.99999C17.9983 4.52795 16.8054 3.33503 15.3333 3.33333ZM16.6666 7.33334L16.6666 6.00001C16.6658 5.26398 16.0693 4.6675 15.3333 4.66668V5.33335C15.3333 5.70155 15.0348 6.00001 14.6666 6.00001C14.2984 6.00001 14 5.70155 14 5.33335V4.66668H10.6666V5.33335C10.6666 5.70155 10.3682 6.00001 9.99997 6.00001C9.63177 6.00001 9.33331 5.70155 9.33331 5.33335V4.66668H5.99999V5.33335C5.99999 5.70155 5.70152 6.00001 5.33332 6.00001C4.96512 6.00001 4.66666 5.70155 4.66666 5.33335V4.66668C3.93063 4.6675 3.33414 5.26398 3.33333 6.00001V7.33334H16.6666ZM11.9908 15.5H8.11563C8.00457 15.5 7.92274 15.4778 7.87014 15.4333C7.82338 15.3833 7.8 15.3056 7.8 15.2V14.1917C7.8 14.0861 7.82338 14.0111 7.87014 13.9667C7.92274 13.9167 8.00457 13.8917 8.11563 13.8917H9.09758V11.1083H8.29098C8.17992 11.1083 8.09809 11.0861 8.04549 11.0417C7.99873 10.9917 7.97535 10.9139 7.97535 10.8083V9.8C7.97535 9.69444 7.99873 9.61944 8.04549 9.575C8.09809 9.525 8.17992 9.5 8.29098 9.5H10.8335C10.9446 9.5 11.0235 9.525 11.0702 9.575C11.1229 9.61944 11.1492 9.69444 11.1492 9.8V13.8917H11.9908C12.1019 13.8917 12.1808 13.9167 12.2275 13.9667C12.2802 14.0111 12.3065 14.0861 12.3065 14.1917V15.2C12.3065 15.3056 12.2802 15.3833 12.2275 15.4333C12.1808 15.4778 12.1019 15.5 11.9908 15.5Z" fill="#9CA7BD"/></svg>
                                            <span><?php echo esc_html($year); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                        </dt>
                                        <dd>
                                            <ul data-id="arm_year_filter" data-placeholder="Select Status">
                                                <?php echo $yearLists; //phpcs:ignore?>
                                            </ul>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
    
                            <div class="arm_filter_div" id="arm_search_coupon_filter_item">
                                <div id="arm_search_coupon_filter_item" class="arm_filter_status_box arm_datatable_searchbox arm_datatable_filter_item">
                                    <input type="text" id="arm_search_coupon" class="arm_search_coupon" name="arm_search_coupon" placeholder="<?php esc_html_e('Coupon Code', 'ARMember');?>" value="">
                                </div>
                            </div>
                            <div class="arm_filter_div" id="arm_gateway_filter_item">
                                <div id="arm_gateway_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                    <input type="hidden" id="arm_gateway_filter" class="arm_gateway_filter" value="">
                                    <dl class="arm_selectbox arm_width_220">
                                        <dt>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_7528_8356)"><path d="M10 1.5C5.30576 1.5 1.5 5.30576 1.5 10C1.5 14.6942 5.30576 18.5 10 18.5C14.6942 18.5 18.5 14.6942 18.5 10C18.5 5.30576 14.6942 1.5 10 1.5ZM12.4416 13.351C12.0708 13.8023 11.5881 14.1066 11.0249 14.2605C10.78 14.327 10.6681 14.4564 10.6821 14.7117C10.6926 14.9636 10.6821 15.2119 10.6786 15.4638C10.6786 15.6877 10.5632 15.8066 10.3428 15.8136C10.1994 15.8171 10.056 15.8206 9.91255 15.8206C9.78663 15.8206 9.6607 15.8206 9.53477 15.8171C9.29691 15.8136 9.18498 15.6772 9.18498 15.4463C9.18148 15.2644 9.18148 15.079 9.18148 14.8971C9.17798 14.4914 9.16399 14.4774 8.77572 14.4144C8.27901 14.334 7.7893 14.222 7.33457 14.0016C6.97778 13.8267 6.9393 13.7393 7.04074 13.3615C7.1177 13.0817 7.19465 12.8019 7.2821 12.5255C7.34506 12.3226 7.40453 12.2317 7.51296 12.2317C7.57593 12.2317 7.65638 12.2632 7.76482 12.3191C8.26852 12.5815 8.8037 12.7284 9.36687 12.7984C9.46132 12.8088 9.55576 12.8158 9.65021 12.8158C9.91255 12.8158 10.1679 12.7669 10.4163 12.6584C11.0424 12.3856 11.1403 11.6615 10.6121 11.2278C10.4337 11.0809 10.2274 10.9724 10.014 10.878C9.46482 10.6366 8.89465 10.4547 8.37696 10.1434C7.53745 9.63971 7.00576 8.95062 7.06872 7.92922C7.13868 6.7749 7.7928 6.05432 8.85267 5.66955C9.28992 5.51214 9.29342 5.51564 9.29342 5.06091C9.29342 4.907 9.28992 4.75309 9.29691 4.59568C9.30741 4.25288 9.36338 4.19342 9.70617 4.18292C9.74465 4.18292 9.78663 4.18292 9.8251 4.18292C9.89156 4.18292 9.95802 4.18292 10.0245 4.18292C10.0525 4.18292 10.0805 4.18292 10.1049 4.18292C10.7556 4.18292 10.7556 4.21091 10.7591 4.91399C10.7626 5.43169 10.7626 5.43169 11.2767 5.51214C11.672 5.5751 12.0463 5.69054 12.4101 5.85144C12.6095 5.93889 12.6864 6.07881 12.6235 6.29218C12.5325 6.607 12.4451 6.92531 12.3471 7.23663C12.2842 7.42551 12.2247 7.51296 12.1128 7.51296C12.0498 7.51296 11.9728 7.48848 11.8749 7.43951C11.3712 7.19465 10.843 7.07572 10.2903 7.07572C10.2204 7.07572 10.1469 7.07922 10.077 7.08272C9.91255 7.09321 9.75165 7.1142 9.59774 7.18066C9.05206 7.41852 8.96461 8.02016 9.42984 8.39095C9.6642 8.57984 9.93354 8.71276 10.2099 8.82819C10.6926 9.02757 11.1753 9.21996 11.6335 9.47181C13.0747 10.2763 13.4665 12.1058 12.4416 13.351Z" fill="#9CA7BD"/></g><defs><clipPath id="clip0_7528_8356"><rect width="17" height="17" fill="white" transform="translate(1.5 1.5)"/></clipPath></defs></svg>
                                            <span><?php esc_html_e('All Gateways', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i>
                                        </dt>
                                        <dd>
                                            <ul data-id="arm_gateway_filter" data-placeholder="Select Status">
                                                <?php echo $gateways_list; //phpcs:ignore?>
                                                <?php ?>
                                            </ul>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="arm_filter_div">
                                <input type="button" class="armemailaddbtn arm_margin_right_8" id="arm_report_apply_filter_button" value="<?php esc_html_e('Apply','ARMember'); ?>" />
                                <button type="button" class="arm_cancel_btn arm_margin_right_0 armhelptip tipso_style" id="arm_report_export_button" title="<?php esc_html_e( 'Export to CSV', 'ARMember' ); ?>"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore ?>/arm_export_icon_pg.svg" /></button>
                                <input type="hidden" value="monthly" name="armgraphval_coupon_report" id="armgraphval_coupon_report" />
                            </div>
                        </div>
                    </div>
                <?php }?>
        </div>

        <div class="armclear"></div>
        <form  method="post" action="#" id="arm_report_analytics_form" style="visibility:hidden">

<?php
if (in_array($get_action, array('member_report', 'payment_report', 'pay_per_post_report','coupon_report'))) {
    echo '<input type="hidden" name="arm_report_type" id="arm_report_type" value="'.esc_attr($get_action).'">';
    if ($get_action == 'member_report') { ?>

        <div class="arm_members_chart">
            <div id="chart_container_members">
                <div id="daily_chart" class="arm_chart_container">
                    <label class="lbltitle">Daily chart</label><br />
                    <div id="chart1_members" class="arm_chart_container_inner" ></div>
                </div>

                <div id="monthly_chart" class="arm_chart_container">
                    <label class="lbltitle">Monthly chart</label><br />
                    <div id="chart2_members" class="arm_chart_container_inner" ></div>
                </div>

                <div id="yearly_chart" class="arm_chart_container">
                    <label class="lbltitle">Yearly chart</label><br />
                    <div id="chart3_members" class="arm_chart_container_inner" ></div>
                </div>
                <span class="lbltitle next_chart" style="display: none;">Previous</span>
                <span class="lbltitle next_chart" style="display: none;">Next</span>
                <br /><br />
            </div>
            <br>
            <div class="arm_members_table_container">
                <div class="arm_all_loginhistory_wrapper">
                    <table class="form-table arm_member_last_subscriptions_table" width="100%">
                        <thead>
                            <tr>
                                <td><?php esc_html_e('Member', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Email', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Plan', 'ARMember'); ?></td>                                     
                                <td><?php esc_html_e('Next Recurring Date', 'ARMember'); ?></td>                                
                                <td><?php esc_html_e('Plan Expire Date', 'ARMember'); ?></td>                                          
                                <td><?php esc_html_e('Join Date', 'ARMember'); ?></td>
                            </tr>
                        </thead>
                        <tbody class="arm_members_table_body_content">
                            
                        </tbody>
                    </table>
                    <div class="arm_membership_history_pagination_block">
                        <div class="arm_membership_history_paging_container" id="arm_members_table_paging">
                        </div>
                    </div>
                </div>
            </div>

        </div>

<?php }
    else if ($get_action == 'payment_report') { ?>

        <div class="arm_member_payment_history_chart">
            <div id="chart_container_payment_history">
                <div id="daily_chart" class="arm_chart_container">
                    <label class="lbltitle">Daily chart</label><br />
                    <div id="chart1_payment_history" class="arm_chart_container_inner"></div>
                </div>

                <div id="monthly_chart" class="arm_chart_container">
                    <label class="lbltitle">Monthly chart</label><br />
                    <div id="chart2_payment_history" class="arm_chart_container_inner"></div>
                </div>

                <div id="yearly_chart" class="arm_chart_container">
                    <label class="lbltitle">Yearly chart</label><br />
                    <div id="chart3_payment_history" class="arm_chart_container_inner"></div>
                </div>
                <span class="lbltitle next_chart" style="display: none;">Previous</span>
                <span class="lbltitle next_chart" style="display: none;">Next</span>
                <br /><br />
            </div>

            <br>
            <div class="arm_members_table_container">

                <div class="arm_all_loginhistory_wrapper">
                    <table class="form-table arm_member_last_subscriptions_table" width="100%">
                        <thead>
                            <tr class="arm_subscription_table_header">
                                <td><?php esc_html_e('Invoice ID', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Member' , 'ARMember'); ?></td>
                                <td><?php esc_html_e('Paid By', 'ARMember'); ?></td>                                
                                <td><?php esc_html_e('Plan', 'ARMember'); ?></td>
                                <td class="arm_align_right"><?php esc_html_e('Paid Amount', 'ARMember'); ?></td>
                                <td class="arm_align_center"><?php esc_html_e('Payment Gateway', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Payment Date', 'ARMember'); ?></td>
                            </tr>
                        </thead>
                        <tbody class="arm_payments_table_body_content">
                            
                        </tbody>
                    </table>
                    <div class="arm_membership_history_pagination_block">
                        <div class="arm_membership_history_paging_container" id="arm_payments_table_paging">
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <div class="arm_invoice_detail_container">
            <div class="arm_invoice_detail_popup popup_wrapper arm_invoice_detail_popup_wrapper">
                <div class="popup_wrapper_inner" style="overflow: hidden;">
                    <div class="popup_header arm_text_align_center" >
                        <span class="popup_close_btn arm_popup_close_btn arm_invoice_detail_close_btn"></span>
                        <span class="add_rule_content"><?php esc_html_e('Invoice Detail','ARMember' );?></span>
                    </div>
                    <div class="popup_content_text arm_invoice_detail_popup_text arm_padding_24" id="arm_invoice_detail_popup_text" ></div>
                    <div class="popup_footer arm_text_align_center" style=" padding: 0;">
                        <?php 
                        $invoice_pdf_icon_html='';
                        $invoice_pdf_icon_html=apply_filters('arm_membership_invoice_details_outside',$invoice_pdf_icon_html,$log_id);
                        echo $invoice_pdf_icon_html; //phpcs:ignore
                        ?>
                    </div>
                </div>
            </div>
        </div>

<?php }
    else if ($get_action == 'pay_per_post_report') { ?>

        <div class="arm_member_pay_per_post_report_chart">
            <div id="chart_container_pay_per_post_report">
                <div id="daily_chart" class="arm_chart_container">
                    <label class="lbltitle">Daily chart</label><br />
                    <div id="chart1_pay_per_post_report" class="arm_chart_container_inner"></div>
                </div>

                <div id="monthly_chart" class="arm_chart_container">
                    <label class="lbltitle">Monthly chart</label><br />
                    <div id="chart2_pay_per_post_report" class="arm_chart_container_inner"></div>
                </div>

                <div id="yearly_chart" class="arm_chart_container">
                    <label class="lbltitle">Yearly chart</label><br />
                    <div id="chart3_pay_per_post_report" class="arm_chart_container_inner"></div>
                </div>
                <span class="lbltitle next_chart" style="display: none;">Previous</span>
                <span class="lbltitle next_chart" style="display: none;">Next</span>
                <br /><br />
            </div>

            <br>
            <div class="arm_members_table_container">

                <div class="arm_all_loginhistory_wrapper">
                    <table class="form-table arm_member_last_subscriptions_table" width="100%">
                        <thead>
                            <tr class="arm_subscription_table_header">
                                <td><?php esc_html_e('Invoice ID', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Member', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Paid By', 'ARMember'); ?></td>                                
                                <td><?php esc_html_e('Paid Post', 'ARMember'); ?></td>
                                <td class="arm_align_right"><?php esc_html_e('Paid Amount', 'ARMember'); ?></td>
                                <td class="arm_align_center"><?php esc_html_e('Payment Gateway', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Payment Date', 'ARMember'); ?></td>
                            </tr>
                        </thead>
                        <tbody class="arm_pay_per_post_report_table_body_content">
                            
                        </tbody>
                    </table>
                    <div class="arm_membership_history_pagination_block">
                        <div class="arm_membership_history_paging_container" id="arm_payments_table_paging">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="arm_invoice_detail_container">
            <div class="arm_invoice_detail_popup popup_wrapper arm_invoice_detail_popup_wrapper">
                <div class="popup_wrapper_inner" style="overflow: hidden;">
                    <div class="popup_header arm_text_align_center" >
                        <span class="popup_close_btn arm_popup_close_btn arm_invoice_detail_close_btn"></span>
                        <span class="add_rule_content"><?php esc_html_e('Invoice Detail','ARMember' );?></span>
                    </div>
                    <div class="popup_content_text arm_invoice_detail_popup_text arm_padding_0" id="arm_invoice_detail_popup_text" ></div>
                    <div class="popup_footer arm_text_align_center" style=" padding: 0 0 35px;">
                        <?php 
                        $invoice_pdf_icon_html='';
                        $invoice_pdf_icon_html=apply_filters('arm_membership_invoice_details_outside',$invoice_pdf_icon_html,$log_id);
                        echo $invoice_pdf_icon_html; //phpcs:ignore
                        ?>
                    </div>
                </div>
            </div>
        </div>

<?php } else if ($get_action == 'coupon_report') { ?>

        <div class="arm_member_coupon_report_chart">
            <div id="chart_container_coupon_report">
                <div id="daily_chart" class="arm_chart_container">
                    <label class="lbltitle">Daily chart</label><br />
                    <div id="chart1_coupon_report" class="arm_chart_container_inner"></div>
                </div>

                <div id="monthly_chart" class="arm_chart_container">
                    <label class="lbltitle">Monthly chart</label><br />
                    <div id="chart2_coupon_report" class="arm_chart_container_inner"></div>
                </div>

                <div id="yearly_chart" class="arm_chart_container">
                    <label class="lbltitle">Yearly chart</label><br />
                    <div id="chart3_coupon_report" class="arm_chart_container_inner"></div>
                </div>
                <span class="lbltitle next_chart" style="display: none;">Previous</span>
                <span class="lbltitle next_chart" style="display: none;">Next</span>
                <br /><br />
            </div>

            <br>
            <div class="arm_members_table_container">

                <div class="arm_all_loginhistory_wrapper">
                    <table class="form-table arm_member_last_subscriptions_table" width="100%">
                        <thead>
                            <tr class="arm_subscription_table_header">
                                <td><?php esc_html_e('Coupon Code', 'ARMember'); ?></td>
                                <td class='arm_align_right'><?php esc_html_e('Coupon Discount', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Member', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Plan', 'ARMember'); ?></td>
                                <td class="arm_align_right"><?php esc_html_e('Paid Amount', 'ARMember'); ?></td>
                                <td class="arm_align_center"><?php esc_html_e('Payment Gateway', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Payment Date', 'ARMember'); ?></td>
                            </tr>
                        </thead>
                        <tbody class="arm_coupon_report_table_body_content">                            
                        </tbody>
                    </table>
                    <div class="arm_membership_history_pagination_block">
                        <div class="arm_membership_history_paging_container" id="arm_coupon_report_table_paging">
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="arm_invoice_detail_container">
            <div class="arm_invoice_detail_popup popup_wrapper arm_invoice_detail_popup_wrapper">
                <div class="popup_wrapper_inner" style="overflow: hidden;">
                    <div class="popup_header arm_text_align_center" >
                        <span class="popup_close_btn arm_popup_close_btn arm_invoice_detail_close_btn"></span>
                        <span class="add_rule_content"><?php esc_html_e('Invoice Detail','ARMember' );?></span>
                    </div>
                    <div class="popup_content_text arm_invoice_detail_popup_text arm_padding_0" id="arm_invoice_detail_popup_text" ></div>
                    <div class="popup_footer arm_text_align_center" style=" padding: 0 0 35px;">
                        <?php 
                        $invoice_pdf_icon_html='';
                        $invoice_pdf_icon_html=apply_filters('arm_membership_invoice_details_outside',$invoice_pdf_icon_html,$log_id);
                        echo $invoice_pdf_icon_html; //phpcs:ignore
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php }
}
?>
            <input type='hidden' name='is_export_to_csv' value='0'>
            <input type='hidden' name='current_page' value=''>
            <input type='hidden' name='gateway_filter' value=''>
            <input type='hidden' name='date_filter' value=''>
            <input type='hidden' name='month_filter' value=''>
            <input type='hidden' name='year_filter' value=''>
            <input type='hidden' name='plan_id' value=''>
            <input type='hidden' name='plan_type' value=''>
            <input type='hidden' name='graph_type' value=''>
            <input type='hidden' name='type' value=''>
            <input type='hidden' name='action' value=''>
            <input type='hidden' name='arm_export_report_data' value='0'>
            <input type="hidden" name="arm_search_coupon" value="">

        </form>
    </div>
</div>
<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
<script type="text/javascript">
    var ARM_IMAGE_URL = "<?php echo MEMBERSHIPLITE_IMAGES_URL;?>";
    
    jQuery(document).on('mouseover','#arm_report_export_button',function(){
        jQuery(this).find('img').attr('src','<?php echo MEMBERSHIPLITE_IMAGES_URL; ?>/arm_export_icon_pg_hover.svg');
    });

    jQuery(document).on('mouseout','#arm_report_export_button',function(){
        jQuery(this).find('img').attr('src','<?php echo MEMBERSHIPLITE_IMAGES_URL; ?>/arm_export_icon_pg.svg');
    });
</script>
<?php require_once(MEMBERSHIPLITE_VIEWS_DIR.'/arm_view_member_details.php')?>
<?php
    echo $ARMember->arm_get_need_help_html_content('members-report-analysis'); //phpcs:ignore
?>