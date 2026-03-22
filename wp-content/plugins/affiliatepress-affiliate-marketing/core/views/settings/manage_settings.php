<?php
    
    if ( ! defined( 'ABSPATH' ) ) { exit; }

    global $affiliatepress_slugs;
    $affiliatepress_setting_module                = ! empty($_REQUEST['setting_page']) ? sanitize_text_field($_REQUEST['setting_page']) : 'affiliate'; // phpcs:ignore
    $affiliatepress_setting_page_url = add_query_arg('page', $affiliatepress_slugs->affiliatepress_settings, esc_url(admin_url() . 'admin.php?page=affiliatepress'));

    $affiliatepress_settings_url      = $affiliatepress_setting_page_url;
    $affiliatepress_company_settings_url      = add_query_arg('setting_page', 'affiliate', $affiliatepress_setting_page_url);
    $affiliatepress_commissions_settings_url = add_query_arg('setting_page', 'commissions', $affiliatepress_setting_page_url);
    $affiliatepress_integrations_settings_url    = add_query_arg('setting_page', 'integrations', $affiliatepress_setting_page_url);
    $affiliatepress_email_notification_settings_url      = add_query_arg('setting_page', 'email_notification', $affiliatepress_setting_page_url);
?>
<el-main class="ap-main-listing-card-container ap-default-card ap--is-page-non-scrollable-mob ap-settings-main-wrapper" id="ap-all-page-main-container">
    <div class="ap-settings--main-container">
        <div v-if="ap_first_page_loaded == '1'" class="ap-back-loader-container" id="ap-page-loading-loader">
            <div class="ap-back-loader"></div>
        </div>        
        <div v-if="ap_first_page_loaded == '0'" id="ap-main-container">
            <el-tabs ref="affiliatepress_setting_tabs" type="card" v-model="selected_tab_name" tab-position="left" class="ap-tabs ap-tabs--vertical__left-side" @tab-click="settings_tab_select($event)">
                <?php 
                    require AFFILIATEPRESS_VIEWS_DIR . '/settings/affiliate_setting_tab.php';

                    require AFFILIATEPRESS_VIEWS_DIR . '/settings/commissions_setting_tab.php';

                    do_action('affiliatepress_add_other_settings_after_commission_tab');

                    require AFFILIATEPRESS_VIEWS_DIR . '/settings/integrations_setting_tab.php';

                    require AFFILIATEPRESS_VIEWS_DIR . '/settings/email_notification_setting_tab.php';

                    require AFFILIATEPRESS_VIEWS_DIR . '/settings/messages_setting_tab.php';
		    
                    require AFFILIATEPRESS_VIEWS_DIR . '/settings/appearance_setting_tab.php';

                    do_action('affiliatepress_add_other_settings_tab');

                    require AFFILIATEPRESS_VIEWS_DIR . '/settings/debug_log_setting_tab.php';

                    $affiliatepress_file_url = array();
                    $affiliatepress_file_url = apply_filters( 'affiliatepress_lite_settings_add_tab_filter', $affiliatepress_file_url );
                    if ( ! empty( $affiliatepress_file_url ) && is_array( $affiliatepress_file_url ) ) {
                        foreach ( $affiliatepress_file_url as $affiliatepress_file_key => $affiliatepress_file_url_val ) {
                            if ( ! empty( $affiliatepress_file_url_val ) ) {
                                require $affiliatepress_file_url_val;
                            }
                        }
                    }  

                ?>
            </el-tabs>
        </div>
    </div>
</el-main>
<?php
    $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_footer.php';
    $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_footer_content', $affiliatepress_load_file_name,1);
    require $affiliatepress_load_file_name;
?>