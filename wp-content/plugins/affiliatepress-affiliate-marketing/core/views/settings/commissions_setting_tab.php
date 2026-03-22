<?php 
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $AffiliatePress;
?>
<el-tab-pane class="ap-tabs--v_ls__tab-item--pane-body" name ="commissions_settings"  data-tab_name="commissions_settings">
    <template #label>
        <span>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path class="ap-setting-fill-stroke-active" d="M3.98797 14.6539L2.4657 13.1317C1.84477 12.5107 1.84477 11.4892 2.4657 10.8683L3.98797 9.34599C4.24836 9.0856 4.45867 8.57482 4.45867 8.21429V6.06105C4.45867 5.17973 5.17976 4.45867 6.06108 4.45867H8.2143C8.57484 4.45867 9.0856 4.24838 9.34599 3.98799L10.8682 2.4657C11.4892 1.84477 12.5108 1.84477 13.1317 2.4657L14.654 3.98799C14.9144 4.24838 15.425 4.45867 15.7856 4.45867H17.9389C18.8202 4.45867 19.5412 5.17973 19.5412 6.06105V8.21429C19.5412 8.57482 19.7515 9.0856 20.0119 9.34599L21.5343 10.8683C22.1552 11.4892 22.1552 12.5107 21.5343 13.1317L20.0119 14.6539C19.7515 14.9143 19.5412 15.4252 19.5412 15.7857V17.9388C19.5412 18.8202 18.8202 19.5413 17.9389 19.5413H15.7856C15.425 19.5413 14.9144 19.7516 14.654 20.0119L13.1317 21.5342C12.5108 22.1551 11.4892 22.1551 10.8682 21.5342L9.34599 20.0119C9.0856 19.7516 8.57484 19.5413 8.2143 19.5413H6.06108C5.17976 19.5413 4.45867 18.8202 4.45867 17.9388V15.7857C4.45867 15.4152 4.24836 14.9043 3.98797 14.6539Z" stroke="#617191" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            <path class="ap-setting-fill-stroke-active" d="M9.00461 14.9943L15.0136 8.98535" stroke="#617191" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            <path class="ap-setting-fill-stroke-active" d="M14.5073 14.4941H14.5163" stroke="#617191" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path class="ap-setting-fill-stroke-active" d="M9.5 9.48633H9.50898" stroke="#617191" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="ap-settings-tab-lbl"><?php esc_html_e('Commissions', 'affiliatepress-affiliate-marketing'); ?></span>
        </span>
    </template>
    <div class="ap-general-settings-tabs--pb__card">
        <div class="ap-settings-tab-content-body-wrapper">
            <div class="ap-back-loader-container" id="ap-page-loading-loader" v-if="ap_settings_content_loaded == '1'">
                <div class="ap-back-loader"></div>
            </div>  
            <div v-else class="ap-gs--tabs-pb__content-body">
                <el-form :rules="rules_commissions" ref="commissions_setting_form" :model="commissions_setting_form" @submit.native.prevent>
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Commission Settings', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left ap-gs-col-first">
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Commission Rate', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="default_discount_val">
                                            <el-input class="ap-form-control ap-append-input-select" type="text" @input="isNumberValidate" v-model="commissions_setting_form.default_discount_val" size="large" placeholder="<?php esc_html_e('Enter Commission Rate', 'affiliatepress-affiliate-marketing'); ?>">    
                                                <template #append>
                                                    <el-select class="ap-form-control ap-append-select" v-model="commissions_setting_form.default_discount_type" placeholder="Select" size="large">
                                                        <el-option value="percentage" label="<?php esc_html_e( '%', 'affiliatepress-affiliate-marketing'); ?>"><?php esc_html_e( '%', 'affiliatepress-affiliate-marketing'); ?></el-option>
                                                        <el-option value="fixed" :label="current_currency_symbol">{{current_currency_symbol}}</el-option>                                                            
                                                    </el-select>
                                                </template>
                                            </el-input>
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Commission Basis', 'affiliatepress-affiliate-marketing'); ?></span></label>    
                                        <el-form-item prop="exclude_shipping">
                                            <el-select class="ap-form-control" v-model="commissions_setting_form.flat_rate_commission_basis" placeholder="Select" size="large">
                                                <el-option value="pre_product" label="<?php esc_html_e( 'Commission Per Product Sold', 'affiliatepress-affiliate-marketing'); ?>"><?php esc_html_e( 'Commission Per Product Sold', 'affiliatepress-affiliate-marketing'); ?></el-option>
                                                <el-option value="pre_order" label="<?php esc_html_e( 'Commission Per Order', 'affiliatepress-affiliate-marketing'); ?>"><?php esc_html_e( 'Commission Per Order', 'affiliatepress-affiliate-marketing'); ?></el-option>                                                            
                                            </el-select>                                                                            
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="22" :sm="22" :md="22" :lg="18" :xl="18" class="ap-gs__cb-item-left">
                                    <el-row type="flex" class="ap-gs--tabs-fields-label">
                                        <h4><?php esc_html_e('Allow Affiliates to Earn Commissions for Their Own Orders', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    </el-row>
                                    <el-row type="flex" class="ap-gs--tabs-fields-description">
                                        <div><?php esc_html_e('Allows affiliates to earn commissions on their own purchases.', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </el-row>
                                </el-col>
                                <el-col :xs="2" :sm="2" :md="2" :lg="6" :xl="6" class="ap-gs__cb-item-right">				
                                    <el-form-item prop="earn_commissions_own_orders">
                                        <el-switch  v-model="commissions_setting_form.earn_commissions_own_orders"/>                                         
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="20" :sm="20" :md="20" :lg="18" :xl="18" class="ap-gs__cb-item-left">
                                    <el-row type="flex" class="ap-gs--tabs-fields-label">
                                        <h4><?php esc_html_e('Allow Zero Amount Commission', 'affiliatepress-affiliate-marketing'); ?></h4>
                                    </el-row>
                                    <el-row type="flex" class="ap-gs--tabs-fields-description">
                                        <div><?php esc_html_e('Enables commissions with a zero amount.', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </el-row>
                                </el-col>
                                <el-col :xs="4" :sm="4" :md="4" :lg="6" :xl="6" class="ap-gs__cb-item-right">				
                                    <el-form-item prop="allow_zero_amount_commission">
                                        <el-switch  v-model="commissions_setting_form.allow_zero_amount_commission"/>                                         
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left ap-gs-col-first">
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                                <?php esc_html_e('Refund Grace Period', 'affiliatepress-affiliate-marketing'); ?>
                                                <el-tooltip popper-class="ap--setting-popover-tool-tip" raw-content content="<div><?php esc_html_e('The grace period (set in number of days) is used when generating payouts for your affiliates. It helps you filter out commissions that could still be rejected due to a refund of the underlying purchase. We recommend you to set this equal to your store refund policy.', 'affiliatepress-affiliate-marketing'); ?></div>" show-after="300" effect="light"  placement="bottom-start">
                                                <span class="ap-setting-info-icon">
                                                    <?php do_action('affiliatepress_common_svg_code','info_icon'); ?>                                        
                                                </span>
                                                </el-tooltip>                                            
                                            </span>
                                        </label>     
                                        <el-form-item prop="refund_grace_period">
                                            <el-input-number v-model="commissions_setting_form.refund_grace_period" class="ap-form-control--number" :min="0" :max="60" size="large" />                                     
                                        </el-form-item>    
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label>
                                            <span class="ap-form-label">
                                                <?php esc_html_e('Payment Minimum Number of orders', 'affiliatepress-affiliate-marketing'); ?> 
                                                <el-tooltip popper-class="ap--setting-popover-tool-tip" raw-content content="<div><?php esc_html_e('Set the minimum number of completed orders an affiliate must generate before they become eligible for payout. Affiliates will only receive payments once this threshold is reached.', 'affiliatepress-affiliate-marketing'); ?></div>" show-after="300" effect="light"  placement="bottom-start">
                                                <span class="ap-setting-info-icon">
                                                    <?php do_action('affiliatepress_common_svg_code','info_icon'); ?>                                        
                                                </span>
                                                </el-tooltip>    
                                                <div v-if="is_pro_active != '1'" @click="open_premium_modal" class="ap-premium-text"><?php echo $AffiliatePress->affiliatepress_get_premium_content(); //phpcs:ignore ?></div>
                                            </span>
                                        </label>
                                        <el-form-item prop="minimum_payment_order">
                                            <el-input-number :disabled="minimum_payment_order_disable" v-model.number="commissions_setting_form.minimum_payment_order"class="ap-form-control--number" size="large" :min="1" :step="1" :precision="0" />
                                        </el-form-item>   
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label>
                                            <span class="ap-form-label">
                                                <?php esc_html_e('Payment Minimum Amount', 'affiliatepress-affiliate-marketing'); ?> ({{current_currency_symbol}})                                          
                                            </span>
                                        </label>          
                                        <el-form-item prop="minimum_payment_amount">
                                            <el-input class="ap-form-control" type="text" v-model="commissions_setting_form.minimum_payment_amount" @input="ispaymentNumberValidate" size="large" placeholder="<?php esc_html_e('Enter Minimum Payment Amount', 'affiliatepress-affiliate-marketing'); ?>" />                                       
                                        </el-form-item>     
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left ap-gs-col-first">
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label">
                                                <?php esc_html_e('Commission Billing Cycle For Automatic-Payout', 'affiliatepress-affiliate-marketing'); ?>
                                                <el-tooltip popper-class="ap--setting-popover-tool-tip" raw-content content="<div><?php esc_html_e('Commission Billing Cycle will be created as per the selected time period for auto payout. For Example: If "Monthly" Cycle is selected then, Billing Cycle will be created every last month.', 'affiliatepress-affiliate-marketing'); ?></div>" show-after="300" effect="light"  placement="bottom-start">
                                                <span class="ap-setting-info-icon">
                                                    <?php do_action('affiliatepress_common_svg_code','info_icon'); ?>                                        
                                                </span>
                                                </el-tooltip>                                        
                                            </span>
                                        </label>        
                                        <el-form-item prop="commission_billing_cycle">
                                            <el-select @change="change_auto_payout_cycle" class="ap-form-control" v-model="commissions_setting_form.commission_billing_cycle" placeholder="Select" size="large">
                                                <el-option v-for="item in billing_cycle" :key="item.value" :label="item.label" :value="item.value"/>
                                            </el-select>                                       
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left" v-if="commissions_setting_form.commission_billing_cycle != 'disabled'">
                                    <div class="ap-combine-field">
                                        <label>
                                            <span class="ap-form-label" v-if="commissions_setting_form.commission_billing_cycle == 'yearly'">
                                                <?php esc_html_e('Month of Billing Cycle For Automatic-Payout', 'affiliatepress-affiliate-marketing'); ?>
                                                <el-tooltip popper-class="ap--setting-popover-tool-tip" raw-content content="<div><?php esc_html_e('Payment cycle will be created as per the selected month for the yearly cycle. For example, if you set the month as March, the payment cycle will be considered to start from the last day of February for auto payout.', 'affiliatepress-affiliate-marketing'); ?></div>" show-after="300" effect="light"  placement="bottom-end">
                                                <span class="ap-setting-info-icon">
                                                    <?php do_action('affiliatepress_common_svg_code','info_icon'); ?>                                        
                                                </span>
                                                </el-tooltip>                                         
                                            </span>
                                            <span class="ap-form-label" v-else>
                                                <?php esc_html_e('Day of Billing Cycle For Automatic-Payout', 'affiliatepress-affiliate-marketing'); ?>
                                                <el-tooltip v-if="commissions_setting_form.commission_billing_cycle == 'monthly'" popper-class="ap--setting-popover-tool-tip" raw-content content="<div><?php esc_html_e('Payment Cycle will created as per selected day for monthly cycle. For Example, if you have set a day as 8th Day then payment cycle will be considered to start from 8th day of the last month for auto payout.', 'affiliatepress-affiliate-marketing'); ?></div>" show-after="300" effect="light"  placement="bottom-end">
                                                <span class="ap-setting-info-icon">
                                                    <?php do_action('affiliatepress_common_svg_code','info_icon'); ?>                                        
                                                </span>
                                                </el-tooltip>    
                                                <el-tooltip v-else popper-class="ap--setting-popover-tool-tip" raw-content content="<div><?php esc_html_e('Payment cycle will be created as per the selected weekday for the weekly cycle. For example, if you set the day as Monday, the payment cycle will be considered to start from the previous Monday for auto payout.', 'affiliatepress-affiliate-marketing'); ?></div>" show-after="300" effect="light"  placement="bottom-end">
                                                <span class="ap-setting-info-icon">
                                                    <?php do_action('affiliatepress_common_svg_code','info_icon'); ?>                                        
                                                </span>
                                                </el-tooltip>                                        
                                            </span>
                                        </label>    
                                        <el-form-item v-if="commissions_setting_form.commission_billing_cycle == 'monthly'" prop="day_of_billing_cycle">
                                            <el-input-number v-model="commissions_setting_form.day_of_billing_cycle" class="ap-form-control--number" :min="1" :max="31" size="large" />
                                        </el-form-item>
                                        <el-form-item v-else-if="commissions_setting_form.commission_billing_cycle == 'weekly'" prop="day_of_billing_cycle">                                   
                                            <el-select  class="ap-form-control" v-model="commissions_setting_form.day_of_billing_cycle" placeholder="Select" size="large">
                                                <el-option v-for="item in weekly_cycle_days" :key="item.value" :label="item.text" :value="item.value"/>
                                            </el-select>                                                                            
                                        </el-form-item>   
                                        <el-form-item v-else-if="commissions_setting_form.commission_billing_cycle == 'yearly'" prop="day_of_billing_cycle">                                   
                                            <el-select class="ap-form-control" v-model="commissions_setting_form.day_of_billing_cycle" placeholder="Select" size="large">
                                                <el-option v-for="item in yearly_cycle_months" :key="item.value" :label="item.text" :value="item.value"/>
                                            </el-select>                                                                            
                                        </el-form-item>   
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label>
                                            <span class="ap-form-label">
                                                <?php esc_html_e('Default Commission Status', 'affiliatepress-affiliate-marketing'); ?> 
                                                <div v-if="is_pro_active != '1'" @click="open_premium_modal" class="ap-premium-text"><?php echo $AffiliatePress->affiliatepress_get_premium_content(); //phpcs:ignore ?></div>
                                            </span>
                                        </label>        
                                        <el-form-item prop="default_commission_status">
                                            <el-select class="ap-form-control" v-model="commissions_setting_form.default_commission_status" placeholder="Select" size="large" :disabled="default_commission_status_disable">
                                                <el-option v-for="item in default_commission_status_option" :key="item.value" :label="item.label" :value="item.value"/>
                                            </el-select>                                       
                                        </el-form-item>   
                                    </div>
                                </el-col>
                                <?php 
                                    $affiliatepress_add_default_status_data = "";
                                    $affiliatepress_add_default_status_data = apply_filters('affiliatepress_add_default_commission_status_other_settings',$affiliatepress_add_default_status_data);
                                    echo $affiliatepress_add_default_status_data; //phpcs:ignore
                                ?>
                            </el-row>
                            <?php do_action('affiliatepress_extra_commissions_setting_html');?>
                        </div>
                    </div>
                    <div class="ap-settings-new-section"></div>      
                    <?php 
                        do_action('affiliatepress_extra_commissions_setting_section_html');       
                    ?>             
                </el-form>
                <el-row type="flex" class="ap-mlc-head-wrap-settings ap-gs-tabs--pb__heading ap-gs-tabs--pb__footer">
                    <el-col :xs="12" :sm="12" :md="12" :lg="8" :xl="12" class="ap-gs-tabs--pb__heading--left"></el-col>
                    <el-col :xs="12" :sm="12" :md="12" :lg="16" :xl="12">
                        <div class="ap-hw-right-btn-group ap-gs-tabs--pb__btn-group">        
                        <el-button  @click="(!is_disabled)?saveCommissionSettingsData():''" type="primary" :class="(is_display_save_loader == '1') ? 'ap-btn--is-loader' : ''" class="ap-btn--primary ap-btn--big" :disabled="is_display_save_loader == '1' ? true : false">                 
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