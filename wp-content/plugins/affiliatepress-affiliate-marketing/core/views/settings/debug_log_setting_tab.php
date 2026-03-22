<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<el-tab-pane class="ap-tabs--v_ls__tab-item--pane-body ap-tabl-debug_log_settings" name ="debug_log_settings"  data-tab_name="debug_log_settings">
    <template #label>
        <span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="ap-setting-fill-stroke-active" d="M19 15V11.9375C19 9.76288 17.2371 8 15.0625 8H8.9375C6.76288 8 5 9.76288 5 11.9375V15C5 18.866 8.13401 22 12 22C15.866 22 19 18.866 19 15Z" stroke="#4D5973" stroke-width="1.5"/>
                <path class="ap-setting-fill-stroke-active" d="M16.5 8.5V7.5C16.5 5.01472 14.4853 3 12 3C9.51472 3 7.5 5.01472 7.5 7.5V8.5" stroke="#4D5973" stroke-width="1.5"/>
                <path class="ap-setting-fill-stroke-active" d="M19 14H22" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path class="ap-setting-fill-stroke-active" d="M5 14H2" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path class="ap-setting-fill-stroke-active" d="M14.5 3.5L17 2" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path class="ap-setting-fill-stroke-active" d="M9.5 3.5L7 2" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path class="ap-setting-fill-stroke-active" d="M20.5 20.0002L18.5 19.2002" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path class="ap-setting-fill-stroke-active" d="M20.5 8L18.5 8.8" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path class="ap-setting-fill-stroke-active" d="M3.5 20.0002L5.5 19.2002" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path class="ap-setting-fill-stroke-active" d="M3.5 8L5.5 8.8" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
                <path class="ap-setting-fill-stroke-active" d="M12 21.5V15" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span class="ap-settings-tab-lbl"><?php esc_html_e('Debug Log', 'affiliatepress-affiliate-marketing'); ?></span>
        </span>
    </template>
    <div class="ap-general-settings-tabs--pb__card">
        <div class="ap-settings-tab-content-body-wrapper">
            <div class="ap-back-loader-container" id="ap-page-loading-loader" v-if="ap_settings_content_loaded == '1'">
                <div class="ap-back-loader"></div>
            </div>  
            <div v-else class="ap-gs--tabs-pb__content-body">
                <el-form ref="debug_log_setting_form" :model="debug_log_setting_form" @submit.native.prevent>
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Email Notification', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row ap-debug-log-btn-row" :gutter="32">
                                <el-col :xs="12" :sm="12" :md="12" :lg="18" :xl="18" class="ap-gs__cb-item-left">
                                    <el-row type="flex" class="ap-gs--tabs-fields-label">
                                        <h4><?php esc_html_e('Email Notification Logs', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    </el-row>
                                </el-col>
                                <el-col :xs="12" :sm="12" :md="12" :lg="6" :xl="6" class="ap-gs__cb-item-right">				
                                    <el-form-item>
                                        <el-switch v-model="debug_log_setting_form.email_notification_debug_logs"></el-switch>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                    <div class="ap-debug-item__btns" v-if="debug_log_setting_form.email_notification_debug_logs == true">
                                        <div class="ap-di__btn-item">
                                            <el-button class="ap-btn ap-btn__small ap-btn__plain-small-icon ap-hover-primary" @click="affiliatepess_view_log('email_notification_debug_logs', '', '<?php esc_html_e( 'Debug Logs ( Email Notification )', 'affiliatepress-affiliate-marketing'); ?>','yes')" >
                                                <span class="ap-btn-icn">
                                                    <?php do_action('affiliatepress_common_svg_code' ,'view_log_icon'); ?>
                                                </span>
                                                <span class="ap-btn-lbl"><?php esc_html_e( 'View', 'affiliatepress-affiliate-marketing'); ?></span>
                                            </el-button>
                                        </div>
                                        <div class="ap-di__btn-item">
                                            <el-popover placement="bottom"  width="450" trigger="click">                                                    
                                                <div class="ap-dialog-download"> 
                                                    <el-row type="flex">
                                                        <el-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14" class="ap-download-dropdown-label">			
                                                            <label for="start_time" class="el-form-item__label">
                                                                <span class="ap-form-label"><?php esc_html_e( 'Select duration', 'affiliatepress-affiliate-marketing'); ?></span>
                                                            </label>			
                                                        </el-col>			
                                                        <el-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">											
                                                            <el-select :popper-append-to-body="proper_body_class" :teleported="false" v-model="select_download_log" class="ap-form-control ap-form-control__left-icon">	
                                                                <el-option v-for="download_option in log_download_default_option" :key="download_option.key" :label="download_option.key" :value="download_option.value"></el-option>
                                                            </el-select>										
                                                        </el-col>		
                                                    </el-row>										
                                                    <el-row v-if="select_download_log == 'custom'" class="ap-download-datepicker">
                                                        <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" >											
                                                            <el-date-picker :teleported="false"  popper-class="ap-el-select--is-with-modal" class="ap-form-control--date-range-picker ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" v-model="download_log_daterange" type="daterange" :start-placeholder="affiliatepress_start_date" :end-placeholder="affiliatepress_end_date" :clearable="false" value-format="YYYY-MM-DD" :format="ap_common_date_format"  :picker-options="filter_pickerOptions"> </el-date-picker>
                                                        </el-col>
                                                    </el-row>
                                                    <el-row>													
                                                        <el-col class="ap-flex-right" :xs="24" :sm="24" :md="24" :lg="24" :xl="24" >										
                                                            <el-button type="primary" class="ap-btn1 ap-btn--primary ap-btn--log-download" :class="is_display_download_save_loader == '1' ? 'ap-btn--is-loader' : ''" @click="affiliatepess_download_log('email_notification_debug_logs', select_download_log, download_log_daterange)" :disabled="is_disabled" >
                                                                <span class="ap-btn__label"><?php esc_html_e( 'Download', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                <div class="ap-btn--loader__circles">
                                                                    <div></div>
                                                                    <div></div>
                                                                    <div></div>
                                                                </div>
                                                            </el-button>	
                                                        </el-col>
                                                    </el-row>	
                                                </div>
                                                <template #reference>
                                                    <el-button class="ap-btn ap-btn__small ap-btn__plain-small-icon ap-hover-primary">
                                                    <span class="ap-btn-icn">
                                                        <?php do_action('affiliatepress_common_svg_code' ,'download_log') ?>
                                                    </span>
                                                    <span class="ap-btn-lbl"><?php esc_html_e( 'Download', 'affiliatepress-affiliate-marketing'); ?></span>
                                                    </el-button>
                                                </template>    
                                            </el-popover>	
                                        </div>
                                        <div class="ap-di__btn-item">
                                            <el-popconfirm 
                                                    confirm-button-text="<?php esc_html_e('Delete', 'affiliatepress-affiliate-marketing'); ?>"
                                                    cancel-button-text="<?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?>"
                                                    confirm-button-type="danger"
                                                    cancel-button-type="plain"
                                                    popper-class="el-popover ap-popconfirm-delete-debug" 
                                                    :hide-icon="true"
                                                    title="<?php esc_html_e('Are you sure you want to clear debug logs?', 'affiliatepress-affiliate-marketing'); ?>"
                                                    @confirm="affiliatepress_clear_bebug_log('email_notification_debug_logs')"
                                                    width="280">  
                                                    <template #reference>                                              
                                                        <el-button  class="ap-btn ap-btn__plain-small-icon ap-hover-danger">
                                                            <span class="ap-btn-icn">
                                                            <?php do_action('affiliatepress_common_svg_code','delete_log') ?>
                                                            </span>
                                                            <span class="ap-btn-lbl"><?php esc_html_e( 'Clear', 'affiliatepress-affiliate-marketing'); ?></span>
                                                        </el-button>         
                                                    </template>                                         
                                            </el-popconfirm>
                                        </div>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Commission Tracking Debug Logs', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row ap-debug-log-btn-row" :gutter="32">
                                <el-col :xs="20" :sm="20" :md="20" :lg="18" :xl="18" class="ap-gs__cb-item-left">
                                    <el-row type="flex" class="ap-gs--tabs-fields-label">
                                        <h4><?php esc_html_e('Commission Tracking Logs', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    </el-row>
                                </el-col>
                                <el-col :xs="4" :sm="4" :md="4" :lg="6" :xl="6" class="ap-gs__cb-item-right">				
                                    <el-form-item>
                                        <el-switch  v-model="debug_log_setting_form.commission_tracking_debug_logs"></el-switch>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                <div class="ap-debug-item__btns" v-if="debug_log_setting_form.commission_tracking_debug_logs == true">
                                    <div class="ap-di__btn-item">
                                        <el-button class="ap-btn ap-btn__small ap-btn__plain-small-icon ap-hover-primary" @click="affiliatepess_view_log('commission_tracking_debug_logs', '', '<?php esc_html_e( 'Debug Logs ( Commission Tracking )', 'affiliatepress-affiliate-marketing'); ?>','yes')" >
                                            <span class="ap-btn-icn">
                                                <?php do_action('affiliatepress_common_svg_code' ,'view_log_icon'); ?>
                                            </span>
                                            <span class="ap-btn-lbl"><?php esc_html_e( 'View', 'affiliatepress-affiliate-marketing'); ?></span>
                                        </el-button>
                                    </div>
                                        <div class="ap-di__btn-item">
                                            <el-popover
                                                placement="bottom"
                                                width="450"
                                                trigger="click">                                                    
                                                <div class="ap-dialog-download"> 
                                                    <el-row type="flex">
                                                        <el-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14" class="ap-download-dropdown-label">			
                                                            <label for="start_time" class="el-form-item__label">
                                                                <span class="ap-form-label"><?php esc_html_e( 'Select duration', 'affiliatepress-affiliate-marketing'); ?></span>
                                                            </label>			
                                                        </el-col>			
                                                        <el-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">											
                                                            <el-select :popper-append-to-body="proper_body_class" :teleported="false" v-model="select_download_log" class="ap-form-control ap-form-control__left-icon">	
                                                                <el-option v-for="download_option in log_download_default_option" :key="download_option.key" :label="download_option.key" :value="download_option.value"></el-option>
                                                            </el-select>										
                                                        </el-col>		
                                                    </el-row>										
                                                    <el-row v-if="select_download_log == 'custom'" class="ap-download-datepicker">
                                                        <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" >											
                                                            <el-date-picker :teleported="false"  popper-class="ap-el-select--is-with-modal" class="ap-form-control--date-range-picker ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" v-model="download_log_daterange" type="daterange" :start-placeholder="affiliatepress_start_date" :end-placeholder="affiliatepress_end_date" :clearable="false" value-format="YYYY-MM-DD" :format="ap_common_date_format"  :picker-options="filter_pickerOptions"> </el-date-picker>
                                                        </el-col>
                                                    </el-row>
                                                    <el-row>													
                                                        <el-col class="ap-flex-right" :xs="24" :sm="24" :md="24" :lg="24" :xl="24" >										
                                                            <el-button type="primary" class="ap-btn1 ap-btn--primary ap-btn--log-download" :class="is_display_download_save_loader == '1' ? 'ap-btn--is-loader' : ''" @click="affiliatepess_download_log('commission_tracking_debug_logs', select_download_log, download_log_daterange)" :disabled="is_disabled" >
                                                                <span class="ap-btn__label"><?php esc_html_e( 'Download', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                <div class="ap-btn--loader__circles">
                                                                    <div></div>
                                                                    <div></div>
                                                                    <div></div>
                                                                </div>
                                                            </el-button>	
                                                        </el-col>
                                                    </el-row>	
                                                </div>
                                                <template #reference>
                                                    <el-button class="ap-btn ap-btn__small ap-btn__plain-small-icon ap-hover-primary">
                                                    <span class="ap-btn-icn">
                                                        <?php do_action('affiliatepress_common_svg_code' ,'download_log') ?>
                                                    </span>
                                                        <span class="ap-btn-lbl"><?php esc_html_e( 'Download', 'affiliatepress-affiliate-marketing'); ?></span>
                                                    </el-button>
                                                </template>    
                                            </el-popover>	
                                        </div>
                                        <div class="ap-di__btn-item">
                                            <el-popconfirm 
                                                    confirm-button-text="<?php esc_html_e('Delete', 'affiliatepress-affiliate-marketing'); ?>"
                                                    cancel-button-text="<?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?>"
                                                    confirm-button-type="danger"
                                                    cancel-button-type="plain"
                                                    popper-class="el-popover ap-popconfirm-delete-debug"
                                                    :hide-icon="true"
                                                    title="<?php esc_html_e('Are you sure you want to clear debug logs?', 'affiliatepress-affiliate-marketing'); ?>"
                                                    @confirm="affiliatepress_clear_bebug_log('commission_tracking_debug_logs')"
                                                    width="280">  
                                                    <template #reference>                                              
                                                        <el-button  class="ap-btn ap-btn__plain-small-icon ap-hover-danger">
                                                            <span class="ap-btn-icn">
                                                                <?php do_action('affiliatepress_common_svg_code','delete_log') ?>                                                      
                                                            </span>
                                                            <span class="ap-btn-lbl"><?php esc_html_e( 'Clear', 'affiliatepress-affiliate-marketing'); ?></span>
                                                        </el-button>         
                                                    </template>                                         
                                            </el-popconfirm>
                                        </div>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Payout Tracking Debug Logs', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row ap-debug-log-btn-row" :gutter="32">
                                <el-col :xs="12" :sm="12" :md="12" :lg="18" :xl="18" class="ap-gs__cb-item-left">
                                    <el-row type="flex" class="ap-gs--tabs-fields-label">
                                        <h4><?php esc_html_e('Payout Tracking Logs', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    </el-row>
                                </el-col>
                                <el-col :xs="12" :sm="12" :md="12" :lg="6" :xl="6" class="ap-gs__cb-item-right">				
                                    <el-form-item>
                                        <el-switch v-model="debug_log_setting_form.payout_tracking_debug_logs"></el-switch>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                    <div class="ap-debug-item__btns" v-if="debug_log_setting_form.payout_tracking_debug_logs == true">
                                        <div class="ap-di__btn-item">
                                            <el-button class="ap-btn ap-btn__small ap-btn__plain-small-icon ap-hover-primary" @click="affiliatepess_view_log('payout_tracking_debug_logs', '', '<?php esc_html_e( 'Debug Logs ( Payout Tracking )', 'affiliatepress-affiliate-marketing'); ?>','yes')" >
                                                <span class="ap-btn-icn">
                                                    <?php do_action('affiliatepress_common_svg_code' ,'view_log_icon'); ?>
                                                </span>
                                                <span class="ap-btn-lbl"><?php esc_html_e( 'View', 'affiliatepress-affiliate-marketing'); ?></span>
                                            </el-button>
                                        </div>
                                        <div class="ap-di__btn-item">
                                            <el-popover
                                                placement="bottom"
                                                width="450"
                                                trigger="click">                                                    
                                                <div class="ap-dialog-download"> 
                                                    <el-row type="flex">
                                                        <el-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14" class="ap-download-dropdown-label">			
                                                            <label for="start_time" class="el-form-item__label">
                                                                <span class="ap-form-label"><?php esc_html_e( 'Select duration', 'affiliatepress-affiliate-marketing'); ?></span>
                                                            </label>			
                                                        </el-col>			
                                                        <el-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">											
                                                            <el-select :popper-append-to-body="proper_body_class" :teleported="false" v-model="select_download_log" class="ap-form-control ap-form-control__left-icon">	
                                                                <el-option v-for="download_option in log_download_default_option" :key="download_option.key" :label="download_option.key" :value="download_option.value"></el-option>
                                                            </el-select>										
                                                        </el-col>		
                                                    </el-row>										
                                                    <el-row v-if="select_download_log == 'custom'" class="ap-download-datepicker">
                                                        <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" >											
                                                            <el-date-picker :teleported="false"  popper-class="ap-el-select--is-with-modal" class="ap-form-control--date-range-picker ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" v-model="download_log_daterange" type="daterange" :start-placeholder="affiliatepress_start_date" :end-placeholder="affiliatepress_end_date" :clearable="false" value-format="YYYY-MM-DD" :format="ap_common_date_format"  :picker-options="filter_pickerOptions"> </el-date-picker>
                                                        </el-col>
                                                    </el-row>
                                                    <el-row>													
                                                        <el-col class="ap-flex-right" :xs="24" :sm="24" :md="24" :lg="24" :xl="24" >										
                                                            <el-button type="primary" class="ap-btn1 ap-btn--primary ap-btn--log-download" :class="is_display_download_save_loader == '1' ? 'ap-btn--is-loader' : ''" @click="affiliatepess_download_log('payout_tracking_debug_logs', select_download_log, download_log_daterange)" :disabled="is_disabled" >
                                                                <span class="ap-btn__label"><?php esc_html_e( 'Download', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                <div class="ap-btn--loader__circles">
                                                                    <div></div>
                                                                    <div></div>
                                                                    <div></div>
                                                                </div>
                                                            </el-button>	
                                                        </el-col>
                                                    </el-row>	
                                                </div>
                                                <template #reference>
                                                    <el-button class="ap-btn ap-btn__small ap-btn__plain-small-icon ap-hover-primary">
                                                    <span class="ap-btn-icn">
                                                        <?php do_action('affiliatepress_common_svg_code' ,'download_log'); ?>
                                                    </span>
                                                        <span class="ap-btn-lbl"><?php esc_html_e( 'Download', 'affiliatepress-affiliate-marketing'); ?></span>
                                                    </el-button>
                                                </template>    
                                            </el-popover>	
                                        </div>
                                        <div class="ap-di__btn-item">
                                            <el-popconfirm 
                                                    confirm-button-text="<?php esc_html_e('Delete', 'affiliatepress-affiliate-marketing'); ?>"
                                                    cancel-button-text="<?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?>"
                                                    confirm-button-type="danger"
                                                    cancel-button-type="plain"
                                                    popper-class="el-popover ap-popconfirm-delete-debug"
                                                    :hide-icon="true"
                                                    title="<?php esc_html_e('Are you sure you want to clear debug logs?', 'affiliatepress-affiliate-marketing'); ?>"
                                                    @confirm="affiliatepress_clear_bebug_log('payout_tracking_debug_logs')"
                                                    width="280">  
                                                    <template #reference>                                              
                                                        <el-button  class="ap-btn ap-btn__plain-small-icon ap-hover-danger">
                                                            <span class="ap-btn-icn">
                                                            <?php do_action('affiliatepress_common_svg_code' ,'delete_log'); ?>                                                                     
                                                            </span>
                                                            <span class="ap-btn-lbl"><?php esc_html_e( 'Clear', 'affiliatepress-affiliate-marketing'); ?></span>
                                                        </el-button>         
                                                    </template>                                         
                                            </el-popconfirm>
                                        </div>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>
                    <div class="ap-settings-new-section"></div> 
                    <?php 
                        do_action('affiliatepress_extra_debug_log_setting_section_html');       
                    ?>                  
                </el-form>     
                <el-row type="flex" class="ap-mlc-head-wrap-settings ap-gs-tabs--pb__heading ap-gs-tabs--pb__footer">
                    <el-col :xs="12" :sm="12" :md="12" :lg="8" :xl="12" class="ap-gs-tabs--pb__heading--left"></el-col>
                    <el-col :xs="12" :sm="12" :md="12" :lg="16" :xl="12">
                        <div class="ap-hw-right-btn-group ap-gs-tabs--pb__btn-group">        
                        <el-button  @click="(!is_disabled)?saveSettingsData('debug_log_setting_form','debug_log_settings'):''" type="primary" :class="(is_display_save_loader == '1') ? 'ap-btn--is-loader' : ''" class="ap-btn--primary ap-btn--big" :disabled="is_display_save_loader == '1' ? true : false">                 
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
        </div>
    </div>
</el-tab-pane>
<el-dialog custom-class="ap-dialog ap-dialog--debug-log" width="75%" v-model="open_display_log_modal" :title="open_view_model_gateway" class="ap-debug-dialog-box">  
    <div class="ap-back-loader-container" v-if="is_display_loader_view == '1'">
        <div class="ap-back-loader"></div>
    </div>    
    <div class="ap-dialog-body ap-dialog--debug-log-body">        
        <el-row type="flex" v-if="(items.length == 0) && (is_display_loader_view == '0')">
            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                <div class="ap-data-empty-view">
                    <div class="ap-ev-left-vector">
                        <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                        <h4 class="no-data-found-text"><?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></h4>
                    </div>
                    <div class="ap-ev-right-content">                                                                 
                    </div>
                </div>
            </el-col>
        </el-row>
        <el-row v-if="items.length > 0"> 
            <el-column class="ap-dialog--debug-log-col" :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                <el-container class="ap-grid-list-container">                    
                    <el-table class="ap-dialog--debug-log-table" ref="multipleTable" :data="items">
                        <el-table-column width="100" align="center" prop="payment_debug_log_id" label="<?php esc_html_e('Log Id', 'affiliatepress-affiliate-marketing'); ?>"></el-table-column>
                        <el-table-column width="300" prop="payment_debug_log_name" label="<?php esc_html_e('Log Name', 'affiliatepress-affiliate-marketing'); ?>"></el-table-column>
                        <el-table-column  prop="payment_debug_log_data" label="<?php esc_html_e('Log Data', 'affiliatepress-affiliate-marketing'); ?>"></el-table-column>
                        <el-table-column width="200" align="center" prop="payment_debug_log_added_date" label="<?php esc_html_e('Log Added Date', 'affiliatepress-affiliate-marketing'); ?>"></el-table-column>
                    </el-table>                                        
                </el-container>
            </el-column>
        </el-row>
        <el-row class="ap-pagination" type="flex" v-if="items.length > 0">
            <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" >
                <div class="ap-pagination-left">
                    <p><?php esc_html_e('Showing', 'affiliatepress-affiliate-marketing'); ?>&nbsp;{{ items.length }} <?php esc_html_e('out of', 'affiliatepress-affiliate-marketing'); ?>&nbsp;{{ totalItems }}</p>                    
                </div>
            </el-col>
            <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-pagination-nav">
                <el-pagination ref="ap_pagination" @size-change="handleSizeChange" @current-change="handleCurrentChange" v-model:current-page="currentPage" background layout="prev, pager, next" :total="totalItems" :page-size="perPage"></el-pagination>
            </el-col>
        </el-row>
    </div>
</el-dialog>