<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<el-tab-pane class="ap-tabs--v_ls__tab-item--pane-body" name ="email_notification_settings"  data-tab_name="email_notification_settings">
    <template #label>
        <span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="ap-setting-fill-stroke-active" d="M12 20H7C4 20 2 18.5 2 15V8C2 4.5 4 3 7 3H17C20 3 22 4.5 22 8V11" stroke="#4D5973" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-setting-fill-stroke-active" d="M17 8.5L13.87 11C12.84 11.82 11.15 11.82 10.12 11L7 8.5" stroke="#4D5973" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-setting-fill-stroke-active" d="M19.2097 14.2702L15.6698 17.8102C15.5298 17.9502 15.3997 18.2102 15.3697 18.4002L15.1797 19.7502C15.1097 20.2402 15.4497 20.5803 15.9397 20.5103L17.2897 20.3202C17.4797 20.2902 17.7497 20.1602 17.8797 20.0202L21.4198 16.4803C22.0298 15.8703 22.3198 15.1603 21.4198 14.2603C20.5298 13.3703 19.8197 13.6602 19.2097 14.2702Z" stroke="#4D5973" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-setting-fill-stroke-active" d="M18.6992 14.7803C18.9992 15.8603 19.8392 16.7003 20.9192 17.0003" stroke="#4D5973" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="ap-settings-tab-lbl"><?php esc_html_e('Email Settings', 'affiliatepress-affiliate-marketing'); ?></span>
        </span>
    </template>
    <div class="ap-general-settings-tabs--pb__card ap-email-settings-tabs">
        <div class="ap-settings-tab-content-body-wrapper">
            <div class="ap-back-loader-container" id="ap-page-loading-loader" v-if="ap_settings_content_loaded == '1'">
                <div class="ap-back-loader"></div>
            </div>  
            <div v-if="ap_settings_content_loaded == '0'" class="ap-gs--tabs-pb__content-body">
                <el-form :rules="rules_notification" ref="notification_setting_form" :model="notification_setting_form" @submit.native.prevent> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Email Settings', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-gs__cb-item-left ap-gs__cb-item-flex-col">
                                    <h4><?php esc_html_e('Email Delivery Method', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    <div class="ap-gs_cb-item-setting-control-first">
                                        <el-form-item prop="default_time_slot_step">
                                            <el-radio-group v-model="notification_setting_form.selected_mail_service" class="ap-radio-control">
                                                <el-radio size="large" label="wp_mail"><?php esc_html_e('WordPress default', 'affiliatepress-affiliate-marketing'); ?></el-radio>
                                                <el-radio size="large" label="php_mail"><?php esc_html_e('PHP mail() function', 'affiliatepress-affiliate-marketing'); ?></el-radio>
                                                <el-radio size="large" label="smtp"><?php esc_html_e('SMTP method', 'affiliatepress-affiliate-marketing'); ?></el-radio>
                                            </el-radio-group>                                        
                                        </el-form-item>
                                    </div>                                 
                                </el-col>
                            </el-row>
                            <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left ap-gs-col-first">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('Company Name', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="sender_name">
                                            <el-input class="ap-form-control" size="large" v-model="notification_setting_form.company_name" placeholder="<?php esc_html_e('Enter company name', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item>                     
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('From Name', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="sender_name">
                                            <el-input class="ap-form-control" size="large" v-model="notification_setting_form.sender_name" placeholder="<?php esc_html_e('Enter from name', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row> 
                            <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left ap-gs-col-first">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('From Email', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="sender_email">    
                                            <el-input class="ap-form-control" size="large" type="email" v-model="notification_setting_form.sender_email" placeholder="<?php esc_html_e('example@example.com', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item>              
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('Admin Email', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="admin_email">    
                                            <el-input class="ap-form-control" size="large" type="email" v-model="notification_setting_form.admin_email" placeholder="<?php esc_html_e('example@example.com', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item> 
                                    </div>
                                </el-col>
                            </el-row> 
                            <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row" v-if="notification_setting_form.selected_mail_service == 'smtp'">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('Host Name', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="smtp_host">    
                                            <el-input class="ap-form-control" size="large" v-model="notification_setting_form.smtp_host" placeholder="<?php esc_html_e('Host name', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item>             
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('Port', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="smtp_port">    
                                            <el-input class="ap-form-control" size="large" v-model="notification_setting_form.smtp_port" placeholder="<?php esc_html_e('Port', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row> 
                            <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row" v-if="notification_setting_form.selected_mail_service == 'smtp'">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('Secure connection', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="smtp_secure">    
                                            <el-select size="large" class="ap-form-control" placeholder="<?php esc_html_e('Select secure', 'affiliatepress-affiliate-marketing'); ?>" v-model="notification_setting_form.smtp_secure">
                                                <el-option v-for="item in default_smtp_secure_options" :key="item.text" :label="item.text" :value="item.value"></el-option>
                                            </el-select>                                
                                        </el-form-item>        
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('Username', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="smtp_username">    
                                            <el-input class="ap-form-control" size="large" v-model="notification_setting_form.smtp_username" placeholder="<?php esc_html_e('Username', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row> 
                            <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row" v-if="notification_setting_form.selected_mail_service == 'smtp'">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('Password', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="smtp_password">    
                                            <el-input class="ap-form-control" type="password" size="large" v-model="notification_setting_form.smtp_password" placeholder="<?php esc_html_e('Password', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item>     
                                    </div>
                                </el-col>
                            </el-row> 
                        </div>
                    </div>
                    <div class="ap-settings-new-section"></div>   
                </el-form>   
                <el-row type="flex" class="ap-mlc-head-wrap-settings ap-gs-tabs--pb__heading ap-gs-tabs--pb__footer">
                    <el-col :xs="12" :sm="12" :md="12" :lg="8" :xl="12" class="ap-gs-tabs--pb__heading--left"></el-col>
                    <el-col :xs="12" :sm="12" :md="12" :lg="16" :xl="12">
                        <div class="ap-hw-right-btn-group ap-gs-tabs--pb__btn-group">        
                            <el-button @click="(!is_disabled)?saveEmailNotificationSettingsData():''"  type="primary" :class="(is_display_save_loader == '1') ? 'ap-btn--is-loader' : ''" class="ap-btn--primary ap-btn--big" :disabled="is_display_save_loader == '1' ? true : false">                 
                                <span class="ap-btn__label"><?php esc_html_e('Save', 'affiliatepress-affiliate-marketing'); ?></span>
                                <div class="ap-btn--loader__circles">                    
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>                                                     
                            </el-button>
                        </div>
                    </el-col>
                </el-row>      
            </div>
            <div class="ap-gs--tabs-pb__content-body" v-if="notification_setting_form.selected_mail_service == 'wp_mail' && ap_settings_content_loaded == '0'">
                <el-form :rules="rules_wpmail_test_mail" ref="notification_wpmail_test_mail_form" :model="notification_wpmail_test_mail_form" @submit.native.prevent> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--sub-heading">
                            <?php esc_html_e('Send Test Email Notification', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('To', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="wpmail_test_receiver_email">    
                                            <el-input class="ap-form-control" size="large" v-model="notification_wpmail_test_mail_form.wpmail_test_receiver_email" placeholder="<?php esc_html_e('Enter Email Address', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item> 
                                    </div>
                                </el-col>
                            </el-row> 
                        </div>
                        <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row ap-email-send-row">
                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-gs__cb-item-left">                                
                                <div class="ap-combine-field">
                                    <label><span class="ap-form-label">
                                        <?php esc_html_e('Message', 'affiliatepress-affiliate-marketing'); ?>
                                    </span></label>
                                    <el-form-item prop="wpmail_test_msg">    
                                        <el-input type="textarea" :rows="4" class="ap-form-control" size="large" v-model="notification_wpmail_test_mail_form.wpmail_test_msg" placeholder="<?php esc_html_e('Message', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                    </el-form-item>
                                </div>
                            </el-col>
                        </el-row> 
                        <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row">
                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-gs__cb-item-left">                                
                            </el-col>
                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-gs__cb-item-right">                                
                                <el-button plain type="primary" class="ap-btn--primary ap-btn__medium" :class="(is_display_send_test_wpmail_mail_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disable_send_test_wpmail_email_btn" @click="affiliatepress_send_test_wpmail_email">
                                    <span class="ap-btn__label"><?php esc_html_e('Send Email', 'affiliatepress-affiliate-marketing'); ?></span>
                                    <div class="ap-btn--loader__circles">                    
                                        <div></div>
                                        <div></div>
                                        <div></div>
                                    </div>
                                </el-button>   
                            </el-col>
                        </el-row> 
                    </div> 
                </el-form>   
            </div>
            <div class="ap-gs--tabs-pb__content-body" v-if="notification_setting_form.selected_mail_service == 'smtp' && ap_settings_content_loaded == '0'">
                <el-form :rules="rules_smtp_test_mail" ref="notification_smtp_test_mail_form" :model="notification_smtp_test_mail_form" @submit.native.prevent> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--sub-heading">
                            <?php esc_html_e('Send Test Email Notification', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('To', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="smtp_test_receiver_email">    
                                            <el-input class="ap-form-control" size="large" v-model="notification_smtp_test_mail_form.smtp_test_receiver_email" placeholder="<?php esc_html_e('Enter Email Address', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row> 
                        </div>
                        <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row ap-email-send-row">
                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-gs__cb-item-left">                                
                                <div class="ap-combine-field">
                                    <label><span class="ap-form-label">
                                        <?php esc_html_e('Message', 'affiliatepress-affiliate-marketing'); ?>
                                    </span></label>
                                    <el-form-item prop="smtp_test_msg">    
                                        <el-input type="textarea" :rows="4" class="ap-form-control" size="large" v-model="notification_smtp_test_mail_form.smtp_test_msg" placeholder="<?php esc_html_e('Message', 'affiliatepress-affiliate-marketing'); ?>"></el-input>        
                                    </el-form-item>
                                </div>
                            </el-col>
                        </el-row> 
                        <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row">
                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-gs__cb-item-left">                                
                            </el-col>
                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-gs__cb-item-right">                                
                                <el-button plain type="primary" class="ap-btn--primary ap-btn__medium" :class="(is_display_send_test_mail_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disable_send_test_email_btn" @click="affiliatepress_send_test_email">
                                <span class="ap-btn__label"><?php esc_html_e('Send Email', 'affiliatepress-affiliate-marketing'); ?></span>
                                <div class="ap-btn--loader__circles">                    
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                </el-button>      
                            </el-col>
                        </el-row> 
                    </div> 
                </el-form>   
            </div>
        </div>
    </div>
</el-tab-pane>