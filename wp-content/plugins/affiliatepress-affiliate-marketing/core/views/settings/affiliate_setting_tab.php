<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<el-tab-pane class="ap-tabs--v_ls__tab-item--pane-body" name ="affiliate_settings"  data-tab_name="affiliate_settings">
    <template #label>
        <span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="ap-setting-fill-stroke-active" d="M6.75 3C3.88235 3 3 3.88235 3 6.75C3 9.61765 3.88235 10.5 6.75 10.5C9.61765 10.5 10.5 9.61765 10.5 6.75C10.5 3.88235 9.61765 3 6.75 3Z" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-setting-fill-stroke-active" d="M6.75 13.5C3.88235 13.5 3 14.3824 3 17.25C3 20.1176 3.88235 21 6.75 21C9.61765 21 10.5 20.1176 10.5 17.25C10.5 14.3824 9.61765 13.5 6.75 13.5Z" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-setting-fill-stroke-active" d="M17.25 13.5C14.3824 13.5 13.5 14.3824 13.5 17.25C13.5 20.1176 14.3824 21 17.25 21C20.1176 21 21 20.1176 21 17.25C21 14.3824 20.1176 13.5 17.25 13.5Z" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-setting-fill-stroke-active" d="M17.25 3C14.3824 3 13.5 3.88235 13.5 6.75C13.5 9.61765 14.3824 10.5 17.25 10.5C20.1176 10.5 21 9.61765 21 6.75C21 3.88235 20.1176 3 17.25 3Z" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="ap-settings-tab-lbl"><?php esc_html_e('General', 'affiliatepress-affiliate-marketing'); ?></span>
        </span>
    </template>
    <div class="ap-general-settings-tabs--pb__card">
        <div class="ap-settings-tab-content-body-wrapper">
            <div class="ap-back-loader-container" id="ap-page-loading-loader" v-if="ap_settings_content_loaded == '1'">
                <div class="ap-back-loader"></div>
            </div>  
            <div v-else class="ap-gs--tabs-pb__content-body">
                <el-form :rules="rules_affiliate" ref="affiliate_setting_form" :model="affiliate_setting_form" @submit.native.prevent>
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('General Settings', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="20" :sm="20" :md="20" :lg="14" :xl="14" class="ap-gs__cb-item-left">
                                    <el-row type="flex" class="ap-gs--tabs-fields-label">
                                        <h4><?php esc_html_e('Allow Affiliate Registration', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    </el-row>
                                    <el-row type="flex" class="ap-gs--tabs-fields-description">
                                        <div><?php esc_html_e('Allows new users to register as Affiliates.', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </el-row>
                                </el-col>
                                <el-col :xs="4" :sm="4" :md="4" :lg="10" :xl="10" class="ap-gs__cb-item-right">				
                                    <el-form-item prop="allow_affiliate_registration">
                                        <el-switch v-model="affiliate_setting_form.allow_affiliate_registration"/>                                         
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="20" :sm="20" :md="20" :lg="16" :xl="16" class="ap-gs__cb-item-left">
                                    <el-row type="flex" class="ap-gs--tabs-fields-label">
                                        <h4><?php esc_html_e('Auto Approve & Activate New User Registration', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    </el-row>
                                    <el-row type="flex" class="ap-gs--tabs-fields-description">
                                        <div><?php esc_html_e('Automatically approve and activate affiliate accounts upon registration.', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </el-row>
                                </el-col>
                                <el-col :xs="4" :sm="4" :md="4" :lg="8" :xl="8" class="ap-gs__cb-item-right">
                                    <el-form-item prop="affiliate_default_status">
                                        <el-switch v-model="affiliate_setting_form.affiliate_default_status"/>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label>
                                            <span class="ap-form-label"><?php esc_html_e('Tracking Cookie Days', 'affiliatepress-affiliate-marketing'); ?> 
                                            <el-tooltip popper-class="ap--setting-popover-tool-tip" raw-content content="<div><?php esc_html_e('The number of days a referred visitor is being tracked', 'affiliatepress-affiliate-marketing'); ?></div> <div><?php esc_html_e('If the referred visitor makes a purchase in this timeframe, the referring affiliate will be rewarded a commission.', 'affiliatepress-affiliate-marketing'); ?></div>" show-after="300" effect="light"  placement="bottom-start">
                                                <span class="ap-setting-info-icon">
                                                    <?php do_action('affiliatepress_common_svg_code','info_icon'); ?>                                        
                                                </span>
                                            </el-tooltip>
                                            </span>
                                        </label>
                                        <el-form-item prop="tracking_cookie_days">
                                            <el-input-number v-model="affiliate_setting_form.tracking_cookie_days" class="ap-form-control--number" :min="0" :max="max_tracking_cookie_days" size="large" />                                    
                                        </el-form-item>
                                        <div class="ap-field-desc"><?php esc_html_e('Set value to "0" for maximum cookie duration.', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label>
                                            <span class="ap-form-label"><?php esc_html_e('Affiliate URL Parameter', 'affiliatepress-affiliate-marketing'); ?>
                                            <el-tooltip popper-class="ap--setting-popover-tool-tip" raw-content content="<div><?php esc_html_e('Please be careful with this change. Whenever you will change this parameter, existing affiliate URLs will be affected. Old Affiliate URLs will stop working. ( So Please do not change until its necessary. )', 'affiliatepress-affiliate-marketing'); ?></div>" show-after="300" effect="light"  placement="bottom-start">
                                                <span class="ap-setting-info-icon">
                                                    <?php do_action('affiliatepress_common_svg_code','info_icon'); ?>                                        
                                                </span>
                                            </el-tooltip> 
                                            </span>
                                        </label>    
                                        <el-form-item prop="affiliate_url_parameter">
                                            <el-input class="ap-form-control" type="text" v-model="affiliate_setting_form.affiliate_url_parameter" size="large" placeholder="<?php esc_html_e('Enter URL Parameter', 'affiliatepress-affiliate-marketing'); ?>" />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label>
                                            <span class="ap-form-label"><?php esc_html_e('Default Affiliate URL Format', 'affiliatepress-affiliate-marketing'); ?></span>
                                        </label>
                                        <el-form-item prop="affiliate_setting_form.default_url_type">
                                            <el-select class="ap-form-control" v-model="affiliate_setting_form.default_url_type" placeholder="<?php esc_html_e('Select Affiliate URL Formate', 'affiliatepress-affiliate-marketing'); ?>" size="large">
                                                <el-option v-for="item in affiliatepress_url_types" :key="item.value" :label="item.text" :value="item.value"></el-option>
                                            </el-select>                                       
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                    <el-row type="flex" :gutter="32">
                                        <el-col :xs="20" :sm="20" :md="20" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                            <el-row type="flex" class="ap-gs--tabs-fields-label">
                                                <h4><?php esc_html_e('Enable Fancy Affiliate URL', 'affiliatepress-affiliate-marketing'); ?></h4>
                                            </el-row>
                                            <el-row type="flex" class="ap-gs--tabs-fields-description">
                                                <div><?php esc_html_e('Generates clean, user-friendly affiliate URLs.', 'affiliatepress-affiliate-marketing'); ?></div>
                                            </el-row>
                                        </el-col>
                                        <el-col :xs="4" :sm="4" :md="4" :lg="12" :xl="12" class="ap-gs__cb-item-right">				
                                            <el-form-item prop="enable_fancy_affiliate_url">
                                                <el-switch  v-model="affiliate_setting_form.enable_fancy_affiliate_url"/>                                         
                                            </el-form-item>
                                        </el-col>
                                    </el-row>
                                    <el-row type="flex" :gutter="32">
                                        <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12">
                                            <div class="ap-gs_cb-item-setting-control">			
                                                <el-input class="ap-form-control ap-frm-disable-active-txt ap-mt-10" readonly="true" type="text" :value="affiliate_url_display(affiliate_setting_form.affiliate_url_parameter,affiliate_setting_form.enable_fancy_affiliate_url)" size="large" placeholder="Enter URL Parameter" />
                                            </div>
                                        </el-col>
                                    </el-row>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="22" :sm="20" :md="20" :lg="14" :xl="14" class="ap-gs__cb-item-left">
                                    <el-row type="flex" class="ap-gs--tabs-fields-label">
                                        <h4><?php esc_html_e('Allowed Affiliate User to Close Their Account', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    </el-row>
                                    <el-row type="flex" class="ap-gs--tabs-fields-description">
                                        <div><?php esc_html_e('Allows affiliate users to close their accounts from the Affiliate Panel.', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </el-row>
                                </el-col>
                                <el-col :xs="2" :sm="4" :md="4" :lg="10" :xl="10" class="ap-gs__cb-item-right">				
                                    <el-form-item prop="allow_affiliate_registration">
                                        <el-switch  v-model="affiliate_setting_form.affiliate_user_self_closed_account"/>                                         
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="20" :sm="20" :md="20" :lg="18" :xl="18" class="ap-gs__cb-item-left">
                                    <el-row type="flex" class="ap-gs--tabs-fields-label">
                                        <h4><?php esc_html_e('Help us Improve AffiliatePress by sending anonymous usage stats', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    </el-row>
                                    <el-row type="flex" class="ap-gs--tabs-fields-description">
                                        <div><?php esc_html_e('Sends anonymous data to help improve the plugin. Never sends personal or sensitive data.', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </el-row>
                                </el-col>
                                <el-col :xs="4" :sm="4" :md="4" :lg="6" :xl="6" class="ap-gs__cb-item-right">				
                                    <el-form-item prop="affiliate_usage_stats">
                                        <el-switch  v-model="affiliate_setting_form.affiliate_usage_stats"/>                                         
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label>
                                            <span class="ap-form-label"><?php esc_html_e('Maximum Custom Affiliate Links', 'affiliatepress-affiliate-marketing'); ?> 
                                            <el-tooltip popper-class="ap--setting-popover-tool-tip" raw-content content="<div><?php esc_html_e('Set the maximum number of custom affiliate links a user can create from the frontend.', 'affiliatepress-affiliate-marketing'); ?></div>" show-after="300" effect="light"  placement="bottom-start">
                                                <span class="ap-setting-info-icon">
                                                    <?php do_action('affiliatepress_common_svg_code','info_icon'); ?>                                        
                                                </span>
                                            </el-tooltip>
                                            </span>
                                        </label>
                                        <el-form-item prop="affiliate_link_limit">
                                            <el-input-number v-model="affiliate_setting_form.affiliate_link_limit" class="ap-form-control--number" :min="0" size="large" />                                    
                                        </el-form-item>
                                        <div class="ap-field-desc"><?php esc_html_e('Set the value to "0" to allow unlimited custom affiliate links.', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </div>
                                </el-col>
                            </el-row>
                            <?php do_action('affiliatepress_extra_affiliate_setting_html');     ?>
                        </div>
                    </div>
                    <div class="ap-settings-new-section"></div>
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Currency Settings', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left ap-gs-col-first">
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Currency', 'affiliatepress-affiliate-marketing'); ?>      </span></label>    
                                        <el-form-item prop="payment_default_currency">
                                            <el-select @change="default_currency_change($event)" class="ap-form-control" v-model="affiliate_setting_form.payment_default_currency" placeholder="<?php esc_html_e('Select Currency', 'affiliatepress-affiliate-marketing'); ?>" size="large">
                                                <el-option v-for="currency_data in currency_countries" :key="currency_data.name" :label="currency_data.name+' '+currency_data.symbol" :value="currency_data.code"/>                                        
                                            </el-select>                                       
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Currency Symbol Position', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="affiliate_setting_form.currency_symbol_position">
                                            <el-select class="ap-form-control" v-model="affiliate_setting_form.currency_symbol_position" placeholder="<?php esc_html_e('Currency Symbol Position', 'affiliatepress-affiliate-marketing'); ?>" size="large">
                                                <el-option v-for="price_data in price_symbol_position_val" :value="price_data.value" :label="price_data.text">{{ price_data.text }} - <span class="affiliatepress_payment_ex_position_styles">{{ price_data.position_ex }}</span></el-option>
                                            </el-select>                                       
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left ap-gs-col-first">
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Number of Decimals', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="number_of_decimals">
                                            <el-input-number v-model="affiliate_setting_form.number_of_decimals" class="ap-form-control--number" :min="0" :max="5" size="large" />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Currency Separator', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="currency_separator">
                                            <el-select class="ap-form-control" v-model="affiliate_setting_form.currency_separator" placeholder="<?php esc_html_e('Select Separator', 'affiliatepress-affiliate-marketing'); ?>" size="large">                                        
                                                <el-option v-for="price_data in price_separator_vals" :value="price_data.value" :label="price_data.text">
                                                    <span>{{ price_data.text }} </span>
                                                    <span class="affiliatepress_payment_ex_position_styles"> {{ price_data.separator_ex }}</span>
                                                </el-option>
                                            </el-select>                                       
                                        </el-form-item>                                                              
                                        <el-row gutter="24" class="ap-gs__pst-custom-price-sep" v-if="affiliate_setting_form.currency_separator == 'Custom'">
                                            <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
                                                <el-form-item prop="price_separator_vals">
                                                    <span class="ap-form-label"><?php esc_html_e('Thousand Separator', 'affiliatepress-affiliate-marketing'); ?></span>    
                                                    <el-input size="large" class="ap-form-control" maxlength="5" v-model="affiliate_setting_form.custom_comma_separator" placeholder="<?php esc_html_e('Enter Thousand Separator', 'affiliatepress-affiliate-marketing'); ?>"></el-input>
                                                </el-form-item>
                                            </el-col>
                                            <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
                                                <el-form-item prop="price_separator_vals">
                                                    <span class="ap-form-label"><?php esc_html_e('Decimal Separator', 'affiliatepress-affiliate-marketing'); ?></span>
                                                    <el-input size="large" class="ap-form-control" maxlength="5" v-model="affiliate_setting_form.custom_dot_separator" placeholder="<?php esc_html_e('Enter Decimal Separator', 'affiliatepress-affiliate-marketing'); ?>"></el-input>
                                                </el-form-item>
                                            </el-col>
                                        </el-row>  
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>
                    <div class="ap-settings-new-section"></div>            
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Page Settings', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" :gutter="32" class="ap-gs--tabs-pb__cb-item-row">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left ap-gs-col-first">                                
                                    <div class="ap-combine-field">
                                    <label><span class="ap-form-label">
                                        <?php esc_html_e('Affiliate Account Page', 'affiliatepress-affiliate-marketing'); ?>
                                    </span></label>
                                    <el-form-item prop="affiliate_account_page_id">
                                        <el-select @change="affiliatepress_get_page_url($event,'account')" class="ap-form-control" v-model="affiliate_setting_form.affiliate_account_page_id" placeholder="Select Page" size="large">
                                            <el-option v-for="item in all_wordpress_pages" :key="item.value" :key="item.id" :label="item.title" :value="''+item.id"/>
                                        </el-select>
                                    </el-form-item>
                                    <div class="ap-field-desc">
                                        <?php esc_html_e('Use [affiliatepress_affiliate_panel] shortcode.', 'affiliatepress-affiliate-marketing'); ?>
                                        <a class="ap-refrance-link" v-if="affiliate_setting_form.affiliate_account_page_id" :href="affiliate_account_page_url" target="_blank"><?php esc_html_e('Preview', 'affiliatepress-affiliate-marketing'); ?></a>
                                    </div>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">                                
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                            <?php esc_html_e('Affiliate Registration Page', 'affiliatepress-affiliate-marketing'); ?>
                                        </span></label>
                                        <el-form-item prop="affiliate_registration_page_id">			
                                            <el-select class="ap-form-control" @change="affiliatepress_get_page_url($event,'register')" v-model="affiliate_setting_form.affiliate_registration_page_id" placeholder="Select Page" size="large">
                                                <el-option v-for="item in all_wordpress_pages" :key="item.value" :key="item.id" :label="item.title" :value="''+item.id"/>
                                            </el-select>
                                        </el-form-item> 
                                        <div class="ap-field-desc">
                                            <?php esc_html_e('Use [affiliatepress_affiliate_registration] shortcode.', 'affiliatepress-affiliate-marketing'); ?>
                                            <a class="ap-refrance-link" v-if="affiliate_setting_form.affiliate_registration_page_id" :href="affiliate_register_page_url" target="_blank"><?php esc_html_e('Preview', 'affiliatepress-affiliate-marketing'); ?></a>
                                        </div>
                                    </div>
                                </el-col>
                            </el-row>                                             
                            <?php 
                                do_action('affiliatepress_extra_affiliate_page_setting_html');       
                            ?>
                        </div>
                    </div>
                    <div class="ap-settings-new-section"></div>
                    <?php 
                        do_action('affiliatepress_extra_affiliate_setting_section_html');
                    ?>         
                </el-form>                 
                <el-row type="flex" class="ap-mlc-head-wrap-settings ap-gs-tabs--pb__heading ap-gs-tabs--pb__footer">
                    <el-col :xs="12" :sm="12" :md="12" :lg="8" :xl="12" class="ap-gs-tabs--pb__heading--left"></el-col>
                    <el-col :xs="12" :sm="12" :md="12" :lg="16" :xl="12">
                        <div class="ap-hw-right-btn-group ap-gs-tabs--pb__btn-group">        
                            <el-button type="primary" :class="(is_display_save_loader == '1') ? 'ap-btn--is-loader' : ''" @click="(!is_disabled)?saveAffiliateSettingsData():''" class="ap-btn--primary ap-btn--big" :disabled="is_display_save_loader == '1' ? true : false">       
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