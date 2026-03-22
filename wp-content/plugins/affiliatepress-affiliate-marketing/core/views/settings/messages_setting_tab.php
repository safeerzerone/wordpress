<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<el-tab-pane class="ap-tabs--v_ls__tab-item--pane-body" name ="message_settings"  data-tab_name="message_settings">
    <template #label>
        <span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="ap-setting-fill-stroke-active" d="M17 18.4298H13L8.54999 21.3897C7.88999 21.8297 7 21.3598 7 20.5598V18.4298C4 18.4298 2 16.4298 2 13.4298V7.42969C2 4.42969 4 2.42969 7 2.42969H17C20 2.42969 22 4.42969 22 7.42969V13.4298C22 16.4298 20 18.4298 17 18.4298Z" stroke="#4D5973" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-setting-fill-stroke-active" d="M12.0001 11.3594V11.1494C12.0001 10.4694 12.4201 10.1094 12.8401 9.8194C13.2501 9.5394 13.66 9.1794 13.66 8.5194C13.66 7.5994 12.9201 6.85938 12.0001 6.85938C11.0801 6.85938 10.3401 7.5994 10.3401 8.5194" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="ap-setting-fill-stroke-active" d="M11.9955 13.75H12.0045" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="ap-settings-tab-lbl"><?php esc_html_e('Labels', 'affiliatepress-affiliate-marketing'); ?></span>
        </span>
    </template>
    <div class="ap-general-settings-tabs--pb__card">
        <div class="ap-settings-tab-content-body-wrapper ap-message-settings-container">
            <div class="ap-back-loader-container" id="ap-page-loading-loader" v-if="ap_settings_content_loaded == '1'">
                <div class="ap-back-loader"></div>
            </div>  
            <div v-else class="ap-gs--tabs-pb__content-body">
                <el-form :rules="rules_messages" ref="messages_setting_form" :model="messages_setting_form" @submit.native.prevent>
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Affiliate Panel Menu', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Dashboard', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_menu">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_menu" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Commission', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="commission_menu">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.commission_menu" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Affiliate Links', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="affiliate_links_menu">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_links_menu" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Visits', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visits_menu">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visits_menu" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Creatives', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_menu">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_menu" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Payments', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="paymnets_menu">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.paymnets_menu" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <?php
                                    $affiliatepress_tab_menu = "";
                                    $affiliatepress_tab_menu = apply_filters( 'affiliatepress_extra_tab_menu_labels', $affiliatepress_tab_menu );
                                    echo $affiliatepress_tab_menu;//phpcs:ignore
                                ?>
                            </el-row>
                        </div>
                    </div> 
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Dashboard Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Affiliate Dashboard', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_affiliate_dashboard">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_affiliate_dashboard" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Total Earning', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_total_earnings">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_total_earnings" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Paid Earning', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_paid_earnings">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_paid_earnings" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Unpaid Earning', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_unpaid_earnings">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_unpaid_earnings" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Visits', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_visits_count">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_visits_count" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Commissions', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_commissions_count">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_commissions_count" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Commission Rate', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_commission_rate">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_commission_rate" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Reports', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_reports">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_reports" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Chart Earnings', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_reports">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_chart_earnings" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Chart Commissions', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="dashboard_reports">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.dashboard_chart_commisisons" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>    
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Commission Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Affiliate Commission', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="commission_affiliate_commission">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.commission_affiliate_commission" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Select Status', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="commission_select_status">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.commission_select_status" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Commission ID', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="commission_id">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.commission_id" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Product', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="commission_product">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.commission_product" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="commission_date">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.commission_date" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Commission', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="commission_amount">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.commission_amount" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="commission_status">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.commission_status" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>    
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Affiliate Link Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Affiliate Links', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_affiliate_links">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_affiliate_links" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Referral URL Description', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_description" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('will work in all page', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_parameter_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_parameter_description" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Cookie Duration', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_cookie_duration">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_cookie_duration" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Cookie Duration Description', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_cookie_duration_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_cookie_duration_description" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Custom Affiliate Links', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_custome_Affiliate_links">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_custome_Affiliate_links" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Generate Affiliate Link', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_generate_affiliate_link">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_generate_affiliate_link" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_serial_number">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_serial_number" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Campaign Name', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_campaign_name">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_campaign_name" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Affiliate URL', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_affiliate_url">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_affiliate_url" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Your Affiliate Link', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_your_affiliate_link">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_your_affiliate_link" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Click to Copy', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_click_to_copy">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_click_to_copy" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Generate Custom Affiliate Links', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_generate_custome_affiliate_links">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_generate_custome_affiliate_links" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Genrate Link Description', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_generate_link_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_generate_link_description" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Page URL', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_page_url">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_page_url" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Enter Page URL', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_enter_page_url">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_enter_page_url" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Link Required Message', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_empty_validation">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_empty_validation" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Custom Link Invalid Format Message', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_pattern_validation">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_pattern_validation" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Campaign Name', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_compaign_name">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_compaign_name" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Campaign name Required Message', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_campaign_name_empty_validation">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_campaign_name_empty_validation" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Enter Campaign Name', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_enter_compaign_name">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_enter_compaign_name" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Sub ID', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_sub_id">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_sub_id" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Enter Sub ID', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_enter_sub_id">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_enter_sub_id" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Generate Link', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="link_generate_link">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_generate_link" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Are you sure you want to delete this Link?', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="custome_link_delete_confirm">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.custome_link_delete_confirm" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Delete', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="delete_custome_link_label">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.delete_custome_link_label" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <?php
                                    $affiliatepress_link_tab = "";
                                    $affiliatepress_link_tab = apply_filters( 'affiliatepress_extra_add_link_tab_labels', $affiliatepress_link_tab );
                                    echo $affiliatepress_link_tab;//phpcs:ignore
                                ?>
                            </el-row>
                        </div>
                    </div>    
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Visit Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Visits', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_visits">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_visits" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Select Type', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_select_type">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_select_type" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_serial_number">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_serial_number" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_date">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_date" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Campaign', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_compaign">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_compaign" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('IP Address', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_ip_address">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_ip_address" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Converted', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_converted">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_converted" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Not converted', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_unconverted_status">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_unconverted_status" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('All Visits', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_all">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_all" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Landing URL', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_landing_url">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_landing_url" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Referrer URL', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="visit_referrer_url">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.visit_referrer_url" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>    
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Creative Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Creative', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_title">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_title" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Enter Creative Name', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_enter_creative_name">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_enter_creative_name" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Select Type', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_select_type">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_select_type" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Image', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_image">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_image" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Text Link', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_text_link">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_text_link" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Creative Name', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_name">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_name" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Creative Type', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_type">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_type" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Download', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_download">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_download" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Preview', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_preview">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_preview" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('HTML Code', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_html_code">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_html_code" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Copy Code', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="creative_copy_code">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.creative_copy_code" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>   
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Paymnet Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Payments', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="paymnet_title">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.paymnet_title" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Select Status', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="paymnet_select_status">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.paymnet_select_status" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Payment ID', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="paymnet_id">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.paymnet_id" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="paymnet_date">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.paymnet_date" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Payment Method', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="paymnet_method">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.paymnet_method" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Amount', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="paymnet_amount">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.paymnet_amount" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="paymnet_status">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.paymnet_status" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Minimum Amount', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="payment_minimum_amount_label">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.payment_minimum_amount_label" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <?php
                                    $affiliatepress_paymnet_tab = "";
                                    $affiliatepress_paymnet_tab = apply_filters( 'affiliatepress_extra_add_paymnet_tab_labels', $affiliatepress_paymnet_tab );
                                    echo $affiliatepress_paymnet_tab;//phpcs:ignore
                                ?>
                            </el-row>
                        </div>
                    </div> 
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Edit Profile Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Edit Details', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="edit_details">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.edit_details" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Profile Details', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="profile_details">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.profile_details" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Profile Picture', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="profile_picture">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.profile_picture" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Change Button', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="change_button">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.change_button" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Remove Button', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="remove_button">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.remove_button" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Payment Detail', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="paymnet_detail">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.paymnet_detail" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Save Changes Button', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="save_changes">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.save_changes" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Change Password', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="chnage_password">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.chnage_password" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Current Password', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="current_password">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.current_password" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('New Password', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="new_password">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.new_password" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Confirm New Password', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="confirm_new_password">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.confirm_new_password" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Save Password', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="save_password">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.save_password" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Delete Account', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="delete_account">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.delete_account" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Permanently delete your account and data.', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="delete_account_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.delete_account_description" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Logout', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="log_out">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.log_out" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Are you sure you want to delete your account?', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="delete_account_confirmation_msg">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.delete_account_confirmation_msg" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Delete the account will be delete all the records under the account.', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="delete_account_confirmation_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.delete_account_confirmation_description" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Delete Button', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="delete_account_close_button">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.delete_account_close_button" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Cancel Button', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="delete_account_cancel_button">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.delete_account_cancel_button" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>   
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Register Form Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Create an account', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="create_an_account">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.create_an_account" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Enter your details to create your affiliate account', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="create_account_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.create_account_description" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Create Account', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="create_account_button">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.create_account_button" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Do you have an account', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="do_you_have_account">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.do_you_have_account" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Sign in', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="signin">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.signin" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>    
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Login Form Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Sign in', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_signin">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_signin" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Stay updated on your professional world', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_login_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_login_description" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Username or Email Address', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_user_name">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_user_name" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Enter Email Address', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_user_name_placeholder">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_user_name_placeholder" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Username or Email Required Message', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_username_empty_validation">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_username_empty_validation" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Password', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_password">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_password" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Enter Your Password', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_password_placeholder">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_password_placeholder" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Password Required Message', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_password_empty_validation">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_password_empty_validation" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Remember Me', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_remember_me">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_remember_me" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Forgot Password', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_forgot_password">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_forgot_password" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Sign in', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_signin_button">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_signin_button" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Don’t have an account?', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_dont_have_account">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_dont_have_account" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Create account ', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="login_create_account">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_create_account" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>    
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Forget Password Form Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Forget Password Heading', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="forget_password_label">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.forget_password_label" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Stay updated on your professional world', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="forget_password_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.forget_password_description" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Username or Email Address', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="forget_password_email">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.forget_password_email" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Enter Email Address', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="forget_password_placeholder">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.forget_password_placeholder" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Email Required for Password Reset Message', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="forget_password_empty_validation">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.forget_password_empty_validation" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Submit', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="forget_password_button">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.forget_password_button" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Sign in Link', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="forget_password_signin">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.forget_password_signin" size="large"  />
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div> 
                    <?php
                        $affiliatepress_tab = "";
                        $affiliatepress_tab = apply_filters( 'affiliatepress_extra_tab_add_labels', $affiliatepress_tab );
                        echo $affiliatepress_tab;//phpcs:ignore
                    ?>   
                    <div class="ap-settings-new-section"></div> 
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Common Labels', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Apply Button', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="apply">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.apply" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Reset Button', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="reset">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.reset" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Pagination', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="reset">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.pagination" size="large" /> 
                                        </el-form-item>
                                        <div class="ap-field-desc"><?php esc_html_e('[start] = Pagination start number, [total] = Total Records', 'affiliatepress-affiliate-marketing'); ?></div>          
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Start Date', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="start_date">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.start_date" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('End Date', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="end_date">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.end_date" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('No Data Label', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="no_data">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.no_data" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('No Data Description', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="no_data_description">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.no_data_description" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Filters', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="filters">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.filters" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Per Page', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="pagination_change_label">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.pagination_change_label" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('No', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="no_label">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.no_label" size="large"  />                                         
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-settings-col ap-gs__cb-item-left" >
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Yes', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="yes_label">
                                            <el-input class="ap-form-control" type="text" v-model="messages_setting_form.yes_label" size="large"  />                                         
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
                        <el-button type="primary" :class="(is_display_save_loader == '1') ? 'ap-btn--is-loader' : ''" @click="(!is_disabled)?saveMessageSettingsData():''" class="ap-btn--primary ap-btn--big" :disabled="is_display_save_loader == '1' ? true : false">
                            <span class="ap-btn__label" ><?php esc_html_e('Save', 'affiliatepress-affiliate-marketing'); ?></span>
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