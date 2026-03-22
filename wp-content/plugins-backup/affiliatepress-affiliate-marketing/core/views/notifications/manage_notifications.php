<?php
    if ( ! defined( 'ABSPATH' ) ) { exit; } 
    global $AffiliatePress;
?>
<el-main class="ap-email-notifications-container ap-main-listing-card-container ap-default-card ap--is-page-non-scrollable-mob" id="ap-all-page-main-container">   
    <div v-if="ap_first_page_loaded == '1'" class="ap-back-loader-container" id="ap-page-loading-loader">
        <div class="ap-back-loader"></div>
    </div>     
    <div v-if="ap_first_page_loaded == '0'" id="ap-main-container">       
        <div class="ap-main-container-pad">
            <div class="ap-padding-left-none ap-noti-rtl-panel" >
                <div class="ap-en-left">
                    <div class="ap-en-left__item">
                        <div class="ap-en-left__item-body">
                            <div class="ap-en-left_item-body--list">
                                
                                <div class="ap-en-left_item-body--list__item" data-label="<?php echo addslashes( esc_html__('Account Pending', 'affiliatepress-affiliate-marketing') ); //phpcs:ignore ?>" data-key="affiliate_account_pending" :class="affiliatepress_active_email_notification == 'affiliate_account_pending' ? '__ap-is-active' : ''" @click="affiliatepress_select_email_notification('affiliate_account_pending')" ref="affiliate_account_pending">   
                                    <span class="material-icons-round --ap-item-status is-enabled" v-if="affiliatepress_email_notification_status['affiliate']['affiliate_account_pending'] == true || affiliatepress_email_notification_status['admin']['affiliate_account_pending'] == true" >circle</span>
                                    <span class="material-icons-round --ap-item-status" v-else>circle</span>                                  
                                    <p><?php esc_html_e('Account Pending', 'affiliatepress-affiliate-marketing'); ?></p>
                                </div>
                                
                                <div class="ap-en-left_item-body--list__item" data-label="<?php echo addslashes( esc_html__('Account Approved', 'affiliatepress-affiliate-marketing') ); //phpcs:ignore ?>" data-key="affiliate_account_approved" @click="affiliatepress_select_email_notification('affiliate_account_approved')"  :class="affiliatepress_active_email_notification == 'affiliate_account_approved' ? '__ap-is-active' : ''" ref="affiliate_account_approved">        
                                    <span class="material-icons-round --ap-item-status is-enabled" v-if="affiliatepress_email_notification_status['affiliate']['affiliate_account_approved'] == true || affiliatepress_email_notification_status['admin']['affiliate_account_approved'] == true" >circle</span>
                                    <span class="material-icons-round --ap-item-status" v-else>circle</span>                                                                 
                                    <p><?php esc_html_e('Account Approved', 'affiliatepress-affiliate-marketing'); ?></p>
                                </div>
                                <div class="ap-en-left_item-body--list__item" data-label="<?php echo addslashes( esc_html__('Account Rejected', 'affiliatepress-affiliate-marketing') ); //phpcs:ignore ?>" data-key="affiliate_account_rejected" @click="affiliatepress_select_email_notification('affiliate_account_rejected')"  :class="affiliatepress_active_email_notification == 'affiliate_account_rejected' ? '__ap-is-active' : ''" ref="affiliate_account_rejected">             
                                    <span class="material-icons-round --ap-item-status is-enabled" v-if="affiliatepress_email_notification_status['affiliate']['affiliate_account_rejected'] == true || affiliatepress_email_notification_status['admin']['affiliate_account_rejected'] == true" >circle</span>
                                    <span class="material-icons-round --ap-item-status" v-else>circle</span>                                                            
                                    <p><?php esc_html_e('Account Rejected', 'affiliatepress-affiliate-marketing'); ?></p>
                                </div>  
                                <div class="ap-en-left_item-body--list__item" data-label="<?php echo addslashes( esc_html__('Commission Registered', 'affiliatepress-affiliate-marketing') ); //phpcs:ignore ?>" data-key="commission_registered" @click="affiliatepress_select_email_notification('commission_registered')"  :class="affiliatepress_active_email_notification == 'commission_registered' ? '__ap-is-active' : ''" ref="commission_registered">
                                    <span class="material-icons-round --ap-item-status is-enabled" v-if="affiliatepress_email_notification_status['affiliate']['commission_registered'] == true || affiliatepress_email_notification_status['admin']['commission_registered'] == true" >circle</span>
                                    <span class="material-icons-round --ap-item-status" v-else>circle</span>                                                                         
                                    <p><?php esc_html_e('Commission Registered', 'affiliatepress-affiliate-marketing'); ?></p>
                                </div>    
                                <?php 
                                    $affiliatepress_commission_approved_text = addslashes( esc_html__('Commission Approved', 'affiliatepress-affiliate-marketing') );
                                    $affiliatepress_commission_approved_text = apply_filters('affiliatepress_commission_approved_notification_text', $affiliatepress_commission_approved_text);
                                ?>  
                                <div class="ap-en-left_item-body--list__item" data-label="<?php echo esc_html($affiliatepress_commission_approved_text); //phpcs:ignore ?>" data-key="commission_approved" @click="affiliatepress_select_email_notification('commission_approved')"  :class="affiliatepress_active_email_notification == 'commission_approved' ? '__ap-is-active' : ''" ref="commission_approved">   
                                    <span class="material-icons-round --ap-item-status is-enabled" v-if="affiliatepress_email_notification_status['affiliate']['commission_approved'] == true || affiliatepress_email_notification_status['admin']['commission_approved'] == true" >circle</span>
                                    <span class="material-icons-round --ap-item-status" v-else>circle</span>                                                                      
                                    <p><?php echo esc_html($affiliatepress_commission_approved_text); ?></p>
                                </div>
                                <?php do_action('affiliatepress_commission_approved_notification_after'); ?>
                                <div class="ap-en-left_item-body--list__item" data-label="<?php echo addslashes( esc_html__('Commission Paid', 'affiliatepress-affiliate-marketing') ); //phpcs:ignore ?>" data-key="affiliate_payment_paid" @click="affiliatepress_select_email_notification('affiliate_payment_paid')"  :class="affiliatepress_active_email_notification == 'affiliate_payment_paid' ? '__ap-is-active' : ''" ref="affiliate_payment_paid">    
                                    <span class="material-icons-round --ap-item-status is-enabled" v-if="affiliatepress_email_notification_status['affiliate']['affiliate_payment_paid'] == true || affiliatepress_email_notification_status['admin']['affiliate_payment_paid'] == true" >circle</span>
                                    <span class="material-icons-round --ap-item-status" v-else>circle</span>                                                                     
                                    <p><?php esc_html_e('Commission Paid', 'affiliatepress-affiliate-marketing'); ?></p>
                                </div>                                
                                <div class="ap-en-left_item-body--list__item" data-label="<?php echo addslashes( esc_html__('Payout Failed', 'affiliatepress-affiliate-marketing') ); //phpcs:ignore ?>" data-key="affiliate_payment_failed" @click="affiliatepress_select_email_notification('affiliate_payment_failed')"  :class="affiliatepress_active_email_notification == 'affiliate_payment_failed' ? '__ap-is-active' : ''" ref="affiliate_payment_failed" v-if="is_affiliate_pro_active == 1 ||     is_affiliate_pro_active == true" >     
                                    <span class="material-icons-round --ap-item-status is-enabled" v-if="affiliatepress_email_notification_status['affiliate']['affiliate_payment_failed'] == true || affiliatepress_email_notification_status['admin']['affiliate_payment_failed'] == true" >circle</span>
                                    <span class="material-icons-round --ap-item-status" v-else>circle</span> 
                                    <p><?php esc_html_e('Payout Failed', 'affiliatepress-affiliate-marketing'); ?></p>
                                </div> 
                            </div>
                        </div>
                    </div>    
                </div>
            </div>
            <div class="ap-padding-right-none ap-right-content-wrapper">
                <div class="ap-email-right-content">
                    <el-row>
                        <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                            <el-row type="flex" class="ap-mlc-head-wrap ap-head-wrap-notification">
                                <el-col :xs="24" :sm="18" :md="12" :lg="12" :xl="12" class="ap-gs-tabs--pb__heading--left">
                                    <h1 class="ap-page-heading" v-text="affiliatepress_email_notification_edit_text"></h1>
                                </el-col>
                                <el-col :xs="24" :sm="6" :md="12" :lg="12" :xl="12" class="ap-gs-tabs--pb__heading--right">
                                    <div class="ap-hw-right-btn-group">
                                    <el-button type="primary" class="ap-btn--primary ap-btn--big" :class="(is_display_save_loader == '1') ? 'ap-btn--is-loader' : ''" @click="affiliatepress_save_email_notification_data" :disabled="(is_disabled || ap_notifications_content_loaded == '1')" >                    
                                            <span class="ap-btn__label"><?php esc_html_e('Save', 'affiliatepress-affiliate-marketing'); ?></span>
                                            <div class="ap-btn--loader__circles">                    
                                                <div></div>
                                                <div></div>
                                                <div></div>
                                            </div>
                                        </el-button> 																				
                                        <?php do_action('affiliatepress_manage_notification_setting_header_button'); ?>
                                    </div>
                                </el-col>
                            </el-row>
                        </el-col>                                            
                    </el-row>
                    <el-row type="flex" class="ap-notification-tabs-row">
                        <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                            <div class="ap-cmc--tab-menu"> 
                                <div class="ap-cms-tm__body">
                                    <el-tabs class="ap-tabs1 ap-elm-tab-container ap-notification-tab-container" v-model="activeTabName" @tab-click="affiliatepress_change_tab">
                                        <el-tab-pane name="affiliate">
                                            <template #label>
                                                <span><?php esc_html_e('To Affiliate User', 'affiliatepress-affiliate-marketing'); ?></span>
                                            </template>
                                        </el-tab-pane>
                                        <el-tab-pane name="admin">
                                            <template #label>
                                                <span><?php esc_html_e('To Administrator', 'affiliatepress-affiliate-marketing'); ?></span>
                                            </template>
                                        </el-tab-pane>
                                    </el-tabs>
                                </div>
                            </div>
                        </el-col>
                    </el-row>
                    <div class="ap-back-loader-container" v-if="ap_notifications_content_loaded == '1'">
                        <div class="ap-back-loader"></div>
                    </div>
                    <div :class="ap_notifications_content_loaded == '0' ? '__ap_notifications_content_loaded_form_display' : '__ap_notifications_content_loaded_form'" >
                        <el-form class="ap-en-body-card__content--form ap-en-body-card__content--form-padding" id="email_notification_form" ref="email_notification_form" @submit.native.prevent>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row">
                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                    <div class="ap-en-status--swtich-row" v-if="activeTabName == 'affiliate'">
                                        <el-switch v-model="affiliatepress_email_notification_status[activeTabName][affiliatepress_active_email_notification]"></el-switch>    
                                        <label class="ap-form-label"><?php esc_html_e('Send Notification', 'affiliatepress-affiliate-marketing'); ?></label>
                                    </div>
                                    <div class="ap-en-status--swtich-row" v-if="activeTabName == 'admin'">
                                        <el-switch v-model="affiliatepress_email_notification_status[activeTabName][affiliatepress_active_email_notification]"></el-switch>    
                                        <label class="ap-form-label"><?php esc_html_e('Send Notification', 'affiliatepress-affiliate-marketing'); ?></label>
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row">
                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                    <el-form-item class="ap-notification-item-row">
                                        <template #label>
                                            <span class="ap-form-label"><?php esc_html_e('Email Subject', 'affiliatepress-affiliate-marketing'); ?></span>
                                        </template>
                                        <el-input class="ap-form-control" size="large" v-model="affiliatepress_email_notification_subject" placeholder="<?php esc_html_e('Enter Subject', 'affiliatepress-affiliate-marketing'); ?>"></el-input>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="16" :lg="17" :xl="17">                                                                
                                    <div class="ap-en-body-card">
                                            <div class="ap-back-loader-container" v-if="is_display_loader == '1'">
                                                <div class="ap-back-loader"></div>
                                            </div>                        
                                            <el-row type="flex" class="ap-en-body-card__content">
                                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                    <el-row type="flex">
                                                        <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                            <el-row>
                                                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                                    <el-form-item class="ap-notification-item-row">
                                                                        <template #label>
                                                                            <span class="ap-form-label"><?php esc_html_e('Email Message', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                        </template>
                                                                        <?php
                                                                        $affiliatepress_message_content_editor = array(
                                                                                'textarea_name' => 'affiliatepress_email_notification_subject_message',
                                                                                'media_buttons' => false,
                                                                                'textarea_rows' => 10,
                                                                                'default_editor' => 'html',
                                                                                'editor_css' => '',
                                                                                'tinymce' => true,
                                                                        );
                                                                        wp_editor('', 'affiliatepress_email_notification_subject_message', $affiliatepress_message_content_editor);
                                                                        ?>
                                                                        <span class="ap-sm__field-helper-label"><?php esc_html_e('Allowed HTML tags <div>, <label>, <span>, <p>, <ul>, <li>, <tr>, <td>, <a>, <br>, <b>, <h1>, <h2>, <hr>', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                    </el-form-item>
                                                                </el-col>
                                                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                                    <div class="ap-toast-notification --ap-warning">
                                                                        <div class="ap-front-tn-body">
                                                                            <p><?php esc_html_e('Note', 'affiliatepress-affiliate-marketing'); ?>: <?php esc_html_e('Please add <br /> in the email message to add a new line', 'affiliatepress-affiliate-marketing'); ?>. <?php esc_html_e('Enter key will not be considered as new line', 'affiliatepress-affiliate-marketing'); ?>.</p>
                                                                        </div>
                                                                    </div>
                                                                </el-col>
                                                            </el-row>
                                                        </el-col>
                                                    </el-row>
                                                </el-col>
                                            </el-row>                      
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="8" :lg="7" :xl="7">
                                        <div class="ap-email-tags-container">
                                            <div class="ap-gs__cb--item-heading">
                                                <h4 class="ap-sec--sub-heading ap-page-heading ap-page-heading-placeholder"><?php esc_html_e('Email Placeholders', 'affiliatepress-affiliate-marketing'); ?></h4>
                                            </div>
                                            <div class="ap-gs__cb--item-tags-body">
                                                <div>
                                                    <span class="ap-tags--item-sub-heading"><?php esc_html_e('Affiliate Releted', 'affiliatepress-affiliate-marketing'); ?></span>                                        
                                                    <span class="ap-tags--item-body" v-for="item in affiliatepress_affiliate_placeholders" @click="affiliatepress_insert_placeholder(item.value)">{{ item.name }}</span>
                                                </div>
                                            </div>
                                            <div v-if="(affiliatepress_active_email_notification == 'commission_registered' || affiliatepress_active_email_notification == 'commission_approved')" class="ap-gs__cb--item-tags-body">
                                                <div>
                                                    <span class="ap-tags--item-sub-heading"><?php esc_html_e('Commission Releted', 'affiliatepress-affiliate-marketing'); ?></span>
                                                    <span class="ap-tags--item-body" v-for="item in affiliatepress_commission_placeholders" @click="affiliatepress_insert_placeholder(item.value)">{{ item.name }}</span>
                                                </div>
                                            </div>
                                            <div v-if="(affiliatepress_active_email_notification == 'affiliate_payment_paid')" class="ap-gs__cb--item-tags-body">
                                                <div>
                                                    <span class="ap-tags--item-sub-heading"><?php esc_html_e('Payment Releted', 'affiliatepress-affiliate-marketing'); ?></span>
                                                    <span class="ap-tags--item-body" v-for="item in affiliatepress_payment_placeholders" @click="affiliatepress_insert_placeholder(item.value)">{{ item.name }}</span>
                                                </div>
                                            </div>                                
                                            <div v-if="(affiliatepress_active_email_notification == 'affiliate_payment_failed')" class="ap-gs__cb--item-tags-body">
                                                <div>
                                                    <span class="ap-tags--item-sub-heading"><?php esc_html_e('Payment Releted', 'affiliatepress-affiliate-marketing'); ?></span>
                                                    <span class="ap-tags--item-body" v-for="item in affiliatepress_payment_placeholders" @click="affiliatepress_insert_placeholder(item.value)">{{ item.name }}</span>
                                                </div>
                                            </div>        
                                        </div>
                                </el-col>
                            </el-row>
                        </el-form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</el-main>
<?php
    $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_footer.php';
    $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_footer_content', $affiliatepress_load_file_name,1);
    require $affiliatepress_load_file_name;
?>

