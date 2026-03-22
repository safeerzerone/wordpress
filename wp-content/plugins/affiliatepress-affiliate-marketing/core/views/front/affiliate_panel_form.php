<?php  
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $affiliatepress_get_setting_data;
?>
<div v-cloak :class="affiliatepress_container_dynamic_class" class="ap-front-reg-container  <?php echo esc_html((is_rtl())?'ap-front-panel-container-rtl':''); ?>" id="affiliatepress_panel_<?php echo esc_html( $affiliatepress_uniq_id ); ?>" style="min-height:600px;">
    <div id="ap-none-field">
        <?php wp_nonce_field('ap_wp_nonce'); ?>    
    </div>
    <div ref="affiliatepresspanel" class="ap-vue-main-front-container" :class="(is_login == 'true' && allow_user_access == 'true')?'ap-affiliate-panel-main':''" id="ap-vue-cont-id" style="min-height:600px;">
        <div style="display:none;" :style="(is_login == 'false') ? 'display:flex;' : ''" class="ap-main-reg-card-container" :class="(show_forgot_password_form == '1')?'ap-main-forgot-password-container':''">
            <div style="display:none;" :style="(is_affiliate_form_loader == '0') ? 'display:block;' : ''" class="ap-front-loader-container" id="ap-page-front-loading-loader">
                <div class="ap-front-loader"></div>
            </div>
            <div class="ap-main-reg-card-inside" style="display:none;" :style="(is_affiliate_form_loader == '1') ? 'display:block;' : ''">
                <div class="ap-front-toast-notification --ap-error ap-front-msg-panel" style="display:none;" :style="(is_display_error == '1') ? 'display:block;' : ''">
                    <div class="ap-front-tn-body">                                                
                        <p :aria-label="is_error_msg">{{ is_error_msg }}</p>                        
                    </div>
                </div>
                <div class="ap-front-toast-notification --ap-success ap-front-msg-panel" style="display:none;" :style="(is_display_success == '1') ? 'display:block;' : ''">
                    <div class="ap-front-tn-body">
                        <p :aria-label="is_success_msg">{{ is_success_msg }}</p>
                    </div>
                </div>                
                <div :class="(show_forgot_password_form == '0')?'':'ap-hide-form'" class="ap-main-reg-frm-body ap-single-form">                    
                    <el-form :class="(show_register_form == '0')?'':'ap-hide-form'" @submit.native.prevent ref="affiliates_login_form_data" :rules="affiliatepress_login_form_rules" require-asterisk-position="right" :model="affiliatepress_login_form" label-position="top"> 
                        <div class="ap-front-page-title" :aria-label="affiliate_panel_labels.login_signin" v-html="affiliate_panel_labels.login_signin"></div>
                        <div class="ap-front-page-sub-title" :aria-label="affiliate_panel_labels.login_login_description" v-html="affiliate_panel_labels.login_login_description"></div>
                        <div class="ap-single-field__form">                    
                            <el-form-item prop="affiliatepress_username">
                                <template #label>
                                    <span class="ap-form-label" :aria-label="affiliate_panel_labels.login_user_name" v-html="affiliate_panel_labels.login_user_name"></span>
                                </template>
                                <el-input ref="loginInput" class="ap-form-control" type="text" size="large" v-model="affiliatepress_login_form.affiliatepress_username" :placeholder="affiliate_panel_labels.login_user_name_placeholder" />
                            </el-form-item>                     
                        </div>
                        <div class="ap-single-field__form">                    
                            <el-form-item prop="affiliatepress_password">
                                <template #label>
                                    <span class="ap-form-label" :aria-label="affiliate_panel_labels.login_password" v-html="affiliate_panel_labels.login_password"></span>
                                </template>
                                <el-input class="ap-form-control" type="password" size="large" v-model="affiliatepress_login_form.affiliatepress_password" :placeholder="affiliate_panel_labels.login_password_placeholder" show-password />
                            </el-form-item>                     
                        </div>
                        <div class="ap-single-field__form">  
                            <div class="ap-disp-flex-box">
                                <el-checkbox v-model="affiliatepress_login_form.affiliatepress_is_remember" class="ap-form-label ap-custom-checkbox--is-label" size="large"><div :aria-label="affiliate_panel_labels.login_remember_me" v-html="affiliate_panel_labels.login_remember_me"></div></el-checkbox>
                                <div><a href="javascript:void(0);" @click="showForgotpassword()" class="ap-acnt-link ap-acnt-link-forgot ap-title-text-color" :aria-label="affiliate_panel_labels.login_forgot_password" v-html="affiliate_panel_labels.login_forgot_password"></a></div>
                            </div>      
                        </div>                
                        <div class="ap-frm-btn">
                            <el-button native-type="submit" @click="affiliatepress_affiliate_login" :disabled="(login_form_loader == '1')?true:false" :class="(login_form_loader == '1') ? 'ap-btn--is-loader' : ''" class="ap-btn--primary ap-btn--big ap-form-full-width-control"  type="primary">
                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.login_signin_button" v-html="affiliate_panel_labels.login_signin_button"></span>
                                <div class="ap-btn--loader__circles">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>                    
                            </el-button>                 
                        </div>
                        <div style="display:none;" :style="(allow_affiliate_registration != 'false') ? 'display:flex;' : ''" class="ap-frm-account-link-upper">
                            <span :aria-label="affiliate_panel_labels.login_dont_have_account" v-html="affiliate_panel_labels.login_dont_have_account"></span>&nbsp;<a  class="ap-acnt-link ap-title-text-color" @click="affiliatepress_go_to_register" href="javascript:void(0);"  :aria-label="affiliate_panel_labels.login_create_account" v-html="affiliate_panel_labels.login_create_account"></a>
                        </div>
                    </el-form>
                    <div :class="(show_register_form == '0')?'ap-hide-form':''">
                    <el-form @submit.native.prevent ref="affiliates_reg_form_data" :rules="rules" require-asterisk-position="right" :model="affiliates" label-position="top"> 
                        <div class="el-form-item__error el-form-item is-error el-input__wrapper el-input__inner el-checkbox el-checkbox--large is-checked ap-form-label ap-custom-checkbox--is-label el-checkbox__input is-checked el-checkbox__original el-checkbox__inner el-checkbox__label" style="display:none"></div> 
                            <div class="ap-front-page-title" :aria-label="affiliate_panel_labels.create_an_account" v-html="affiliate_panel_labels.create_an_account"></div>
                            <div class="ap-front-page-sub-title" :aria-label="affiliate_panel_labels.create_account_description" v-html="affiliate_panel_labels.create_account_description"></div>
                            <div v-for="affiliate_field in affiliate_fields">                    
                                <div v-if="affiliate_field.ap_form_field_type == 'Text' && affiliate_field.ap_field_is_default == '1'" class="ap-single-field__form" :class="affiliate_field.ap_field_class">                    
                                    <el-form-item :prop="affiliate_field.ap_form_field_name">
                                        <template #label>
                                            <span class="ap-form-label" v-html="affiliate_field.ap_field_label" :aria-label="affiliate_field.ap_field_label"></span>
                                        </template>
                                        <el-input class="ap-form-control" :readonly="(is_user_login == '1' && affiliate_field.ap_form_field_name == 'username')?true:false"  type="text" size="large" v-model="affiliates[affiliate_field.ap_form_field_name]" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>
                                <div v-if="affiliate_field.ap_form_field_type == 'Email'" class="ap-single-field__form" :class="affiliate_field.ap_field_class">                    
                                    <el-form-item :prop="affiliate_field.ap_form_field_name">
                                        <template #label>
                                            <span class="ap-form-label" v-html="affiliate_field.ap_field_label" :aria-label="affiliate_field.ap_field_label"></span>
                                        </template>
                                        <el-input class="ap-form-control" :readonly="(is_user_login == '1')?true:false" type="text" size="large" v-model="affiliates[affiliate_field.ap_form_field_name]" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div> 
                                <div v-if="affiliate_field.ap_form_field_type == 'Password' && is_user_login == '0'" class="ap-single-field__form" :class="affiliate_field.ap_field_class">                    
                                    <el-form-item :prop="affiliate_field.ap_form_field_name">
                                        <template #label>
                                            <span class="ap-form-label" v-html="affiliate_field.ap_field_label" :aria-label="affiliate_field.ap_field_label"></span>
                                        </template>
                                        <el-input class="ap-form-control" type="password" :show-password="true" v-model="affiliates[affiliate_field.ap_form_field_name]" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>  
                                <div v-if="affiliate_field.ap_form_field_type == 'Password' && is_user_login == '0' && confirm_password_field.is_display_confirm_password == 'true'" class="ap-single-field__form" :class="affiliate_field.ap_field_class"> 
                                    <el-form-item prop="confirm_password">
                                        <template #label>
                                            <span class="ap-form-label" v-html="confirm_password_field.confirm_password_label"  :aria-label="confirm_password_field.confirm_password_label"></span>
                                        </template>
                                        <el-input  class="ap-form-control" v-model="affiliates.confirm_password" type="password"  :show-password="true" size="large" :placeholder="confirm_password_field.confirm_password_placeholder"/>
                                    </el-form-item>
                                </div> 
                                <div v-if="affiliate_field.ap_form_field_type == 'Textarea' && affiliate_field.ap_field_is_default == '1'" class="ap-single-field__form" :class="affiliate_field.ap_field_class">                    
                                    <el-form-item :prop="affiliate_field.ap_form_field_name">
                                        <template #label>
                                            <span class="ap-form-label" v-html="affiliate_field.ap_field_label" :aria-label="affiliate_field.ap_field_label"></span>
                                        </template>
                                        <el-input class="ap-form-control" type="textarea" :rows="4" size="large" v-model="affiliates[affiliate_field.ap_form_field_name]" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>  
                                <div v-if="affiliate_field.ap_form_field_type == 'terms_and_conditions'" class="ap-single-field__form ap-checkbox-control ap-checkbox-control-single ap-term-condition-box" :class="affiliate_field.ap_field_class">                    
                                    <el-form-item :prop="affiliate_field.ap_form_field_name">                            
                                        <el-checkbox class="ap-form-label ap-custom-checkbox--is-label" @change="register_terms_and_condition(affiliate_field.ap_form_field_name)" v-model="affiliates[affiliate_field.ap_form_field_name]" size="large"><div v-html="affiliate_field.ap_field_label"></div></el-checkbox>                            
                                    </el-form-item>                     
                                </div>                                                                              
                            </div>
                            <div class="ap-frm-btn">
                                <el-button native-type="submit" :disabled="(is_display_reg_save_loader == '1')?true:false" :class="(is_display_reg_save_loader == '1') ? 'ap-btn--is-loader' : ''" @click="registerAffiliate()" class="ap-btn--primary ap-btn--big ap-form-full-width-control"  type="primary">
                                    <span class="ap-btn__label" :aria-label="affiliate_panel_labels.create_account_button" v-html="affiliate_panel_labels.create_account_button"></span>
                                    <div class="ap-btn--loader__circles">
                                        <div></div>
                                        <div></div>
                                        <div></div>
                                    </div>                    
                                </el-button>                 
                            </div>
                            <div class="ap-frm-account-link-upper">
                                <span :aria-label="affiliate_panel_labels.do_you_have_account" v-html="affiliate_panel_labels.do_you_have_account"></span>&nbsp;<a @click="go_to_login_page()" href="javascript:void(0);" class="ap-acnt-link ap-title-text-color"  :aria-label="affiliate_panel_labels.signin" v-html="affiliate_panel_labels.signin"></a>
                            </div>
                    </el-form>

                    </div>                    
                </div>
                <div :class="(show_forgot_password_form == '1')?'':'ap-hide-form'">
                    <div class="ap-main-reg-frm-body ap-single-form"> 
                        <el-form @submit.native.prevent ref="affiliatepress_forgot_password_form" :rules="affiliatepress_forgot_password_form_rules" require-asterisk-position="right" :model="affiliatepress_forgot_password_form" label-position="top">    
                            <div class="ap-front-page-title"  :aria-label="affiliate_panel_labels.forget_password_label" v-html="affiliate_panel_labels.forget_password_label"></div>
                            <div class="ap-front-page-sub-title" :aria-label="affiliate_panel_labels.forget_password_description" v-html="affiliate_panel_labels.forget_password_description"></div>
                            <div class="ap-single-field__form">                    
                                <el-form-item prop="affiliatepress_email">
                                    <template #label>
                                        <span class="ap-form-label" :aria-label="affiliate_panel_labels.forget_password_email" v-html="affiliate_panel_labels.forget_password_email"></span>
                                    </template>
                                    <el-input ref="forgetInput" class="ap-form-control" type="text" size="large" v-model="affiliatepress_forgot_password_form.affiliatepress_email" :placeholder="affiliate_panel_labels.forget_password_placeholder" />
                                </el-form-item>                     
                            </div>
                            <div class="ap-frm-btn">
                                <el-button native-type="submit" :disabled="(forgot_form_loader == '1')?true:false" :class="(forgot_form_loader == '1') ? 'ap-btn--is-loader' : ''" @click="affiliatepress_forgot_password" class="ap-btn--primary ap-btn--big ap-form-full-width-control"  type="primary">
                                    <span class="ap-btn__label" :aria-label="affiliate_panel_labels.forget_password_button" v-html="affiliate_panel_labels.forget_password_button"></span>
                                    <div class="ap-btn--loader__circles">
                                        <div></div>
                                        <div></div>
                                        <div></div>
                                    </div>                    
                                </el-button>                 
                            </div>
                            <div class="ap-frm-account-link-upper">
                                <a class="ap-acnt-link ap-title-text-color" @click="showLoginForm()" href="javascript:void(0);" :aria-label="affiliate_panel_labels.forget_password_signin" v-html="affiliate_panel_labels.forget_password_signin"></a>
                            </div>
                        </el-form>

                    </div>                

                </div>    
            </div>
        </div>
        <div class="ap-main-reg-card-container" style="display:none;" :style="(is_affiliate_form_loader == '1' && is_login == 'true' && allow_user_access == 'false' && allow_signup == 'false') ? 'display:block;' : ''">
            <div class="ap-flex-center">
                <div class="ap-lock-icon ap-flex-center">
                    <?php do_action('affiliatepress_common_affiliate_panel_svg_code','lock_screen_icon'); ?>
                </div>
            </div>
            <div class="ap-box-card-value ap-affiliate-not-allow-msg ap-mt-20">{{ not_allow_user_affiliate_panel }}</div>                 
        </div>
        <div class="ap-main-reg-card-container" v-if="is_login == 'true' && allow_user_access == 'false' && allow_signup == 'true'">
            <div style="display:none;" :style="(is_affiliate_form_loader == '0') ? 'display:block;' : ''" class="ap-front-loader-container" id="ap-page-front-loading-loader">
                <div class="ap-front-loader"></div>
            </div>
            <div class="ap-front-toast-notification --ap-error ap-front-msg-panel" style="display:none;" :style="(is_display_error == '1') ? 'display:block;' : ''">
                <div class="ap-front-tn-body">                                                
                    <p>{{ is_error_msg }}</p>                        
                </div>
            </div>
            <div class="ap-front-toast-notification --ap-success ap-front-msg-panel" style="display:none;" :style="(is_display_success == '1') ? 'display:block;' : ''">
                <div class="ap-front-tn-body">
                    <p>{{ is_success_msg }}</p>
                </div>
            </div>  
            <div class="ap-front-toast-notification --ap-success ap-front-msg-panel" style="display:none;" :style="(register_form_msg != '') ? 'display:block;' : ''" >
                <div class="ap-front-tn-body">
                    <p class="ap-success-register-message">{{ register_form_msg }}</p>
                </div>
            </div>                          
            <div class="ap-main-reg-frm-body ap-single-form" :style="(is_affiliate_form_loader == '1') ? 'display:block;' : 'display:none'">

                <el-form style="display:none;" :style="(is_show_register_form == 'true') ? 'display:block;' : ''"  @submit.native.prevent ref="affiliates_reg_form_data" :rules="rules" require-asterisk-position="right" :model="affiliates" label-position="top">  
                    <div class="el-form-item__error el-form-item is-error el-input__wrapper el-input__inner el-checkbox el-checkbox--large is-checked ap-form-label ap-custom-checkbox--is-label el-checkbox__input is-checked el-checkbox__original el-checkbox__inner el-checkbox__label" style="display:none"></div>   
                        <div class="ap-front-page-title" :aria-label="affiliate_panel_labels.create_an_account" v-html="affiliate_panel_labels.create_an_account"></div>
                        <div class="ap-front-page-sub-title" :aria-label="affiliate_panel_labels.create_account_description" v-html="affiliate_panel_labels.create_account_description"></div>
                        <div v-for="affiliate_field in affiliate_fields">                    
                            <div v-if="affiliate_field.ap_form_field_type == 'Text' && affiliate_field.ap_field_is_default == '1'" class="ap-single-field__form" :class="affiliate_field.ap_field_class">                    
                                <el-form-item :prop="affiliate_field.ap_form_field_name">
                                    <template #label>
                                        <span class="ap-form-label" v-html="affiliate_field.ap_field_label"></span>
                                    </template>
                                    <el-input class="ap-form-control" :readonly="(is_user_login == '1' && affiliate_field.ap_form_field_name == 'username')?true:false"  type="text" size="large" v-model="affiliates[affiliate_field.ap_form_field_name]" :placeholder="affiliate_field.ap_field_placeholder" />
                                </el-form-item>                     
                            </div>
                            <div v-if="affiliate_field.ap_form_field_type == 'Email'" class="ap-single-field__form" :class="affiliate_field.ap_field_class">                    
                                <el-form-item :prop="affiliate_field.ap_form_field_name">
                                    <template #label>
                                        <span class="ap-form-label" v-html="affiliate_field.ap_field_label"></span>
                                    </template>
                                    <el-input class="ap-form-control" :readonly="(is_user_login == '1')?true:false" type="text" size="large" v-model="affiliates[affiliate_field.ap_form_field_name]" :placeholder="affiliate_field.ap_field_placeholder" />
                                </el-form-item>                     
                            </div> 
                            <div v-if="affiliate_field.ap_form_field_type == 'Password' && is_user_login == '0'" class="ap-single-field__form" :class="affiliate_field.ap_field_class">                    
                                <el-form-item :prop="affiliate_field.ap_form_field_name">
                                    <template #label>
                                        <span class="ap-form-label" v-html="affiliate_field.ap_field_label"></span>
                                    </template>
                                    <el-input class="ap-form-control" type="password" :show-password="true" v-model="affiliates[affiliate_field.ap_form_field_name]" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                </el-form-item>                     
                            </div>  
                            <div v-if="affiliate_field.ap_form_field_type == 'Textarea' && affiliate_field.ap_field_is_default == '1'" class="ap-single-field__form" :class="affiliate_field.ap_field_class">                    
                                <el-form-item :prop="affiliate_field.ap_form_field_name">
                                    <template #label>
                                        <span class="ap-form-label" v-html="affiliate_field.ap_field_label"></span>
                                    </template>
                                    <el-input class="ap-form-control" type="textarea" :rows="4" size="large" v-model="affiliates[affiliate_field.ap_form_field_name]" :placeholder="affiliate_field.ap_field_placeholder" />
                                </el-form-item>                     
                            </div>  
                            <div v-if="affiliate_field.ap_form_field_type == 'terms_and_conditions'" class="ap-single-field__form ap-checkbox-control ap-checkbox-control-single ap-term-condition-box" :class="affiliate_field.ap_field_class">                    
                                <el-form-item :prop="affiliate_field.ap_form_field_name">                            
                                    <el-checkbox class="ap-form-label ap-custom-checkbox--is-label" v-model="affiliates[affiliate_field.ap_form_field_name]" size="large"><div v-html="affiliate_field.ap_field_label"></div></el-checkbox>                            
                                </el-form-item>                     
                            </div>                                                                              
                        </div>  
                        <div class="ap-frm-btn">
                            <el-button native-type="submit" :disabled="(is_display_reg_save_loader == '1')?true:false" :class="(is_display_reg_save_loader == '1') ? 'ap-btn--is-loader' : ''" @click="registerAffiliate()" class="ap-btn--primary ap-btn--big ap-form-full-width-control"  type="primary">
                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.create_account_button" v-html="affiliate_panel_labels.create_account_button"></span>
                                <div class="ap-btn--loader__circles">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>                    
                            </el-button>                 
                        </div>
                </el-form>
            </div>
        </div>        
        <div class="affiliatepress-affiliate-panel" v-if="is_login == 'true' && allow_user_access == 'true'">

            <el-drawer :direction="drawer_direction" modal-class="ap-add__drawer-main ap-menu__drawer-main" :withHeader="false" @close="closeDrawerModal()" v-model="open_mobile_menu_modal" style="display:none;" :style="(is_affiliate_form_loader == '1') ? 'display:inherit;' : ''">   
                <div class="ap-mobile-menu">
                    <div @click="close_drawer_menu" class="ap-drawer-close">
                        <?php do_action('affiliatepress_common_affiliate_panel_svg_code','front_menu_close_icon'); ?>
                    </div>    
                    <div class="ap-affiliate-panel-sidebar">
                        <a href="javascript:void(0);" @click="affiliatepress_change_tab('dashboard')" :class="(affiliate_current_tab == 'dashboard')?'ap-affiliate-menu-item-active':''" class="ap-affiliate-menu-item">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','dashboard'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.dashboard_menu" v-html="affiliate_panel_labels.dashboard_menu"></div>                                        
                        </a>
                        <a href="javascript:void(0);" @click="affiliatepress_change_tab('commission')" :class="(affiliate_current_tab == 'commission')?'ap-affiliate-menu-item-active':''" class="ap-affiliate-menu-item">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','commission'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.commission_menu" v-html="affiliate_panel_labels.commission_menu"></div>
                        </a>
                        <a href="javascript:void(0);" @click="affiliatepress_change_tab('affiliates_links')" :class="(affiliate_current_tab == 'affiliates_links')?'ap-affiliate-menu-item-active':''" class="ap-affiliate-menu-item">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','affiliates_links'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.affiliate_links_menu" v-html="affiliate_panel_labels.affiliate_links_menu"></div>                                         
                        </a>
                        <a href="javascript:void(0);" @click="affiliatepress_change_tab('visit')" :class="(affiliate_current_tab == 'visit')?'ap-affiliate-menu-item-active':''" class="ap-affiliate-menu-item">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','visit'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.visits_menu" v-html="affiliate_panel_labels.visits_menu"></div>                                          
                        </a>
                        <a href="javascript:void(0);" @click="affiliatepress_change_tab('creative')" class="ap-affiliate-menu-item" :class="(affiliate_current_tab == 'creative')?'ap-affiliate-menu-item-active':''">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','creative'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.creative_menu" v-html="affiliate_panel_labels.creative_menu"></div>                                           
                        </a>
                        <a href="javascript:void(0);" @click="affiliatepress_change_tab('payments')" class="ap-affiliate-menu-item" :class="(affiliate_current_tab == 'payments')?'ap-affiliate-menu-item-active':''">                        
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','payouts'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.paymnets_menu" v-html="affiliate_panel_labels.paymnets_menu"></div>                    
                        </a>
                        <div class="ap-front-menu-seperator"></div>
                        <div class="ap-sidebar-profile-section">
                            <img :src="userAvatar" alt="User Avatar" /><?php // phpcs:ignore ?>
                            <div class="ap-sidebar-profile-detais">
                                <div class="ap-sidebar-username">{{ userName }}</div>
                                <div class="ap-sidebar-useremail">{{ userEmail }}</div>
                            </div>
                            <div class="ap-sidebar-profile-action">
                                <div tabindex="0" class="ap-edit-icon" :class="(affiliate_current_tab == 'edit_profile')?'ap-affiliate-menu-active':''" @click="affiliatepress_change_tab('edit_profile')"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','edit_profile_icon'); ?></div>
                                <div tabindex="0" class="ap-logout-icon" @click="affiliatepanel_logout('<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>')"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','logout_account_icon'); ?></div>
                            </div>
                        </div> 
                    </div> 
                </div>
            </el-drawer>
            <div class="ap-welcome-container" v-if="(current_screen_size != 'desktop')" style="display:none;" :style="(is_affiliate_form_loader == '1') ? 'display:inherit;' : ''">
                <el-button @click="open_mobile_menu_modal = true" class="ap-side-menu-button" aria-label="<?php esc_attr_e('Affiliate Menu', 'affiliatepress-affiliate-marketing'); ?>">
                    <?php do_action('affiliatepress_common_affiliate_panel_svg_code','ap_menu_icon'); ?>
                </el-button>
                <div class="ap-avatar-container">                    
                    <el-dropdown popper-class="ap-top-profile-menu" trigger="click" >
                        <div class="ap-top-profile">
                        <img :src="userAvatar" alt="User Avatar" /><?php // phpcs:ignore ?>
                        <span class="ap-droup-down-arrow" tabindex="0">
                            <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 1.5L6 6.5L11 1.5" stroke="#656E81" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>                                
                        </span>
                        </div>
                        <template #dropdown>
                        <el-dropdown-menu>
                            <el-dropdown-item @click="affiliatepress_change_tab('edit_profile')">
                                <div class="ap-top-profile-menu-item"><span class="ap-top-profile-menu-icon ap-flex-center"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','profile_menu_edit_profile_icon'); ?></span><span :aria-label="affiliate_panel_labels.edit_details" v-html="affiliate_panel_labels.edit_details"></div>
                            </el-dropdown-item>                            
                            <el-dropdown-item @click="affiliatepanel_logout('<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>')">
                                <div class="ap-top-profile-menu-item"><span class="ap-top-profile-menu-icon ap-flex-center"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','profile_menu_logout_icon'); ?></span><span v-html="affiliate_panel_labels.log_out"></span></div>
                            </el-dropdown-item>
                        </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>
            </div>            
            <div class="ap-affiliate-panel" style="min-height:800px;background-color: var(--ap-front-cl-white);">
                <div v-if="is_affiliate_form_loader == '0'" class="ap-front-loader-container" style="margin:auto;">
                    <div class="ap-front-loader"></div>
                </div>
                <el-dialog modal-class="ap-affiliate-dialog ap-close-account ap-mobile-full-screen-dialog ap-mobile-center-popup" v-model="open_close_account_modal" title="" width="600" style="display:none;" :style="(is_affiliate_form_loader == '1') ? 'display:inherit;' : ''">
                    <div class="ap-affiliate-dialog">                        
                        <div class="ap-affiliate-dialog-body ap-close-account-popup-body">
                            <div class="ap-close-account-image">
                                <?php do_action('affiliatepress_common_affiliate_panel_svg_code','close_account_image'); ?>
                            </div>
                            <div class="ap-close-account-detail">
                                <div class="ap-close-account-title" :aria-label = "affiliate_panel_labels.delete_account_confirmation_msg" v-html="affiliate_panel_labels.delete_account_confirmation_msg"></div>
                                <div class="ap-close-account-txt" :aria-label = "affiliate_panel_labels.delete_account_confirmation_description" v-html="affiliate_panel_labels.delete_account_confirmation_description"></div>   
                                <div class="ap-cancel-close-btn">                                    
                                    <el-button @click="open_close_account_modal = false" plain class="ap-btn--primary ap-border-btn"  type="primary">
                                        <span class="ap-btn__label" :aria-label = "affiliate_panel_labels.delete_account_cancel_button" v-html="affiliate_panel_labels.delete_account_cancel_button"></span>
                                    </el-button>
                                    <el-button plain @click="close_acount_action" class="ap-btn--primary ap-red-btn" :disabled="(affiliate_close_account_loader == '1')?true:false" :class="(affiliate_close_account_loader == '1') ? 'ap-btn--is-loader' : ''" type="primary">
                                        <span class="ap-btn__label" :aria-label = "affiliate_panel_labels.delete_account_close_button" v-html="affiliate_panel_labels.delete_account_close_button"></span>
                                        <div class="ap-btn--loader__circles">
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                        </div>                                          
                                    </el-button>                                                                        
                                </div>
                            </div>                              
                        </div>
                    </div>
                </el-dialog>
                <div class="ap-affiliate-panel-container" style="display:none;" :style="(is_affiliate_form_loader == '1') ? 'display:inherit;' : ''">
                    <div class="ap-affiliate-panel-sidebar" v-if="(current_screen_size == 'desktop')">
                        <a href="javascript:void(0);" @click.prevent="affiliatepress_change_tab('dashboard')" :class="(affiliate_current_tab == 'dashboard')?'ap-affiliate-menu-item-active':''" class="ap-affiliate-menu-item">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','dashboard'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.dashboard_menu" v-html="affiliate_panel_labels.dashboard_menu"></div>                                        
                        </a>
                        <a href="javascript:void(0);" @click.prevent="affiliatepress_change_tab('commission')" :class="(affiliate_current_tab == 'commission')?'ap-affiliate-menu-item-active':''" class="ap-affiliate-menu-item">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','commission'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.commission_menu" v-html="affiliate_panel_labels.commission_menu"></div>
                        </a>
                        <a href="javascript:void(0);" @click.prevent="affiliatepress_change_tab('affiliates_links')" :class="(affiliate_current_tab == 'affiliates_links')?'ap-affiliate-menu-item-active':''" class="ap-affiliate-menu-item">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','affiliates_links'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.affiliate_links_menu" v-html="affiliate_panel_labels.affiliate_links_menu"></div>                                         
                        </a>
                        <a href="javascript:void(0);" @click.prevent="affiliatepress_change_tab('visit')" :class="(affiliate_current_tab == 'visit')?'ap-affiliate-menu-item-active':''" class="ap-affiliate-menu-item">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','visit'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.visits_menu" v-html="affiliate_panel_labels.visits_menu"></div>                                          
                        </a>
                        <a href="javascript:void(0);" @click.prevent="affiliatepress_change_tab('creative')" class="ap-affiliate-menu-item" :class="(affiliate_current_tab == 'creative')?'ap-affiliate-menu-item-active':''">
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','creative'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.creative_menu" v-html="affiliate_panel_labels.creative_menu"></div>                                           
                        </a>
                        <a href="javascript:void(0);" @click.prevent="affiliatepress_change_tab('payments')" class="ap-affiliate-menu-item" :class="(affiliate_current_tab == 'payments')?'ap-affiliate-menu-item-active':''">                        
                            <div class="ap-affiliate-menu-item-icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','payouts'); ?></div>    
                            <div class="ap-affiliate-menu-item-txt" :aria-label="affiliate_panel_labels.paymnets_menu" v-html="affiliate_panel_labels.paymnets_menu"></div>                    
                        </a>
                        <div class="ap-front-menu-seperator"></div>
                        <div class="ap-sidebar-profile-section">
                            <img :src="userAvatar" alt="User Avatar" /><?php // phpcs:ignore ?>
                            <div class="ap-sidebar-profile-detais">
                                <div class="ap-sidebar-username">{{ userName }}</div>
                                <div class="ap-sidebar-useremail">{{ userEmail }}</div>
                            </div>
                            <div class="ap-sidebar-profile-action">
                                <div tabindex="0" class="ap-edit-icon" :class="(affiliate_current_tab == 'edit_profile')?'ap-affiliate-menu-active':''" @click="affiliatepress_change_tab('edit_profile')" :title="affiliate_panel_labels.edit_details"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','edit_profile_icon'); ?></div>
                                <div tabindex="0" class="ap-logout-icon" :title="affiliate_panel_labels.log_out" @click="affiliatepanel_logout('<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>')"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','logout_account_icon'); ?></div>
                            </div>
                        </div> 
                    </div>  
                    
                    
                    <div v-if="affiliate_current_tab == 'payments'" class="ap-affiliate-panel-content">  
                        <div v-if="is_display_tab_content_loader == '1'" class="ap-panel-loader">
                            <div class="ap-front-loader-container ap-panel-front-loader">
                                <div class="ap-front-loader"></div>
                            </div>
                        </div>
                        <div v-if="is_display_tab_content_loader == '0'" class="ap-panel-detail">

                            <el-dialog modal-class="ap-affiliate-dialog ap-affiliate-filter-dialog ap-affiliate-creative-filter-dialog ap-mobile-full-screen-dialog" v-model="open_payment_filter_modal" title="" width="767">
                                    <div class="ap-affiliate-dialog">                               
                                        <div class="ap-affiliate-dialog-header">
                                            <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.filters" v-html="affiliate_panel_labels.filters"></div>
                                            <div @click="open_payment_filter_modal = false" class="ap-dialog-close1 ap-flex-center">
                                                <?php do_action('affiliatepress_common_affiliate_panel_svg_code','close_dialog'); ?>
                                            </div>                                     
                                        </div>
                                        <div class="ap-affiliate-dialog-body">
                                            <div class="ap-single-field__form">  
                                                <span class="ap-form-label" :aria-label="affiliate_panel_labels.paymnet_date" v-html="affiliate_panel_labels.paymnet_date"></span>
                                                <el-date-picker popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="payments_search.ap_payment_created_date" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliate_panel_labels.start_date" :end-placeholder="affiliate_panel_labels.end_date"  :default-time="defaultTime"/>
                                            </div>
                                            <div class="ap-single-field__form">  
                                                <span class="ap-form-label" aria-label="affiliate_panel_labels.paymnet_status" v-html="affiliate_panel_labels.paymnet_status" ></span>
                                                <el-select class="ap-form-control" size="large" v-model="payments_search.payment_status" :placeholder="affiliate_panel_labels.paymnet_select_status" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">                            
                                                    <el-option v-for="item in all_payments_status" :key="item.value" :label="item.text" :value="item.value"></el-option>                            
                                                </el-select>
                                            </div>                                                                        
                                        </div>
                                    </div>
                                    <div class="ap-tf-btn-group ap-filter-popup-btn-group">
                                        <el-button @click="applypopupPaymentFilter" class="ap-btn--primary ap-btn--full-width" plain type="primary" :disabled="is_apply_disabled">
                                            <span class="ap-btn__label" :aria-label="affiliate_panel_labels.apply" v-html="affiliate_panel_labels.apply"></span>
                                        </el-button>
                                        <el-button @click="resetpopuppayment" class="ap-btn--second ap-btn--full-width">
                                            <span class="ap-btn__label" :aria-label="affiliate_panel_labels.reset" v-html="affiliate_panel_labels.reset"></span>
                                        </el-button>
                                    </div>                            
                            </el-dialog>
                        
                            <div class="ap-affiliate-panel-content-header ap-affiliat-panel-pading">
                                <div class="ap-tab-heading" v-html="affiliate_panel_labels.paymnet_title"></div>   
                                <div class="ap-header-right ap-flex-center ap-header-filter-right">
                                    <el-button @click="open_payment_filter_modal = true" class="ap-filter-icon" aria-label="<?php esc_attr_e('Filter', 'affiliatepress-affiliate-marketing'); ?>">
                                        <?php do_action('affiliatepress_common_affiliate_panel_svg_code','filter_icon'); ?>
                                    </el-button>
                                </div>                                                     
                            </div>
                            <div class="ap-table-filter ap-affiliat-panel-pading">
                                <el-row type="flex" :gutter="24">
                                    <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="17" :lg="17" :xl="17">   
                                        <el-row type="flex" :gutter="16"> 
                                            <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="17" :xl="17">   
                                                <div>  
                                                    <el-date-picker popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="payments_search.ap_payment_created_date" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliate_panel_labels.start_date" :end-placeholder="affiliate_panel_labels.end_date"  :default-time="defaultTime"/>
                                                </div>       
                                            </el-col>
                                            <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="7" :xl="7">   
                                                <div>    
                                                    <el-select class="ap-form-control" size="large" v-model="payments_search.payment_status" :placeholder="affiliate_panel_labels.paymnet_select_status" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">                            
                                                        <el-option v-for="item in all_payments_status" :key="item.value" :label="item.text" :value="item.value"></el-option>                            
                                                    </el-select>
                                                </div>               
                                            </el-col>
                                        </el-row>
                                    </el-col> 
                                    <el-col class="ap-front-filter-btn" :xs="24" :sm="24" :md="24" :lg="7" :xl="7">
                                        <div class="ap-tf-btn-group">
                                            <el-button @click="applyPaymentFilter" class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.apply" v-html="affiliate_panel_labels.apply"></span>
                                            </el-button>
                                            <el-button @click="resetpayments" class="ap-btn--second" v-if="(payments_search.ap_payment_created_date && payments_search.ap_payment_created_date != 0) || payments_search.payment_status != ''">
                                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.reset" v-html="affiliate_panel_labels.reset"></span>
                                            </el-button>
                                        </div>
                                    </el-col>
                                </el-row>
                            </div> 

                            <div class="ap-panel-data-container ap-aff-panel-table">
                                <div v-if="affiliate_payments_loader == '1'" class="ap-panel-loader ap-panel-inner-loader">
                                    <div class="ap-front-loader-container ap-panel-front-loader">
                                        <div class="ap-front-loader"></div>
                                    </div>
                                </div>   
                                <div :class="(affiliatepress_footer_dynamic_class != '')?'ap-front-content-with-scroll':''" class="ap-front-content-data">
                                    <div id="ap-loader-div" v-if="payments_items.length == 0 && affiliate_payments_loader == '0'">
                                        <el-row type="flex">
                                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                <div class="ap-data-empty-view">
                                                    <div class="ap-ev-left-vector">
                                                        <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                                        <div class="no-data-found-text" :aria-label="affiliate_panel_labels.no_data" v-html="affiliate_panel_labels.no_data"></div>
                                                        <div class="no-data-found-description" :aria-label="affiliate_panel_labels.no_data_description" v-html="affiliate_panel_labels.no_data_description"></div>
                                                    </div>                       
                                                </div>
                                            </el-col>
                                        </el-row>
                                    </div> 
                                    <el-table :class="(affiliate_payments_loader == '1')?'ap-hidden-table':''" v-if="payments_items.length != 0 && (current_screen_size == 'tablet' || current_screen_size == 'desktop')" @sort-change="handlePaymentSortChange" :data="payments_items">
                                        <el-table-column prop="" label="" width="28"></el-table-column>
                                        <el-table-column sortable sort-by="ap_payment_id" prop="ap_payment_id" :label="affiliate_panel_labels.paymnet_id" width="150">
                                            <template #default="scope"> 
                                                <span :aria-label="scope.row.ap_payment_id">#{{scope.row.ap_payment_id}}</span>
                                            </template>
                                        </el-table-column>
                                        <el-table-column sortable sort-by="ap_payment_created_date" prop="ap_payment_created_date_formated" :label="affiliate_panel_labels.paymnet_date">
                                            <template #default="scope"> 
                                                <span :aria-label="scope.row.ap_payment_created_date_formated">{{scope.row.ap_payment_created_date_formated}}</span>
                                            </template>
                                        </el-table-column>                                      
                                        <el-table-column prop="payment_method_name" :label="affiliate_panel_labels.paymnet_method">
                                            <template #default="scope"> 
                                                <span :aria-label="scope.row.payment_method_name">{{scope.row.payment_method_name}}</span>
                                            </template>
                                        </el-table-column> 
                                        <el-table-column align="right" width="150" header-align="right" prop="ap_payment_amount" :label="affiliate_panel_labels.paymnet_amount">
                                            <template #default="scope"> 
                                                <span :aria-label="scope.row.ap_formated_payment_amount">{{scope.row.ap_formated_payment_amount}}</span>
                                            </template>
                                        </el-table-column>                                        
                                        <el-table-column align="center" header-align="center"  class-name="ap-padding-left-cls" prop="ap_payment_status" :label="affiliate_panel_labels.paymnet_status">
                                            <template #default="scope">  
                                                <span class="ap-status-col" :class="(scope.row.ap_payment_status == '1' ? 'ap-status-blue' : ''),(scope.row.ap_payment_status == '2' ? 'ap-status-orange' : ''),(scope.row.ap_payment_status == '3' ? 'ap-status-red' : ''),(scope.row.ap_payment_status == '4' ? 'ap-status-green' : ''),(scope.row.ap_payment_status == '5' ? 'ap-status-orange' : '')" :aria-label="scope.row.payment_status_name">{{scope.row.payment_status_name}}</span>
                                            </template>
                                        </el-table-column>
                                    </el-table>
                                    <el-table :class="(affiliate_payments_loader == '1')?'ap-hidden-table':''" class="ap-mobile-view-table" cell-class-name="ap-expand-title-col" v-if="payments_items.length != 0 && (current_screen_size == 'mobile')" @sort-change="handleCommissionSortChange" :data="payments_items">                                        
                                        <el-table-column prop="ap_commission_id">
                                            <template #default="scope">
                                                <div class="ap-expand-top-row ap-expand-top-row-padding">
                                                    <div class="ap-expand-top-row-data ap-mb-5">
                                                        <div class="ap-expan-top-data ap-expand-top-head-left"><span class="ap-com-date"><span class="ap-date-cal-icon ap-mr-8"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','date_calendar_icon'); ?></span><span :aria-label="scope.row.ap_payment_created_date_formated">{{scope.row.ap_payment_created_date_formated}}</span></span></div>
                                                        <div class="ap-expan-top-data ap-expand-top-head-right"><span class="ap-status-col" :class="(scope.row.ap_payment_status == '1' ? 'ap-status-blue' : ''),(scope.row.ap_payment_status == '2' ? 'ap-status-orange' : ''),(scope.row.ap_payment_status == '3' ? 'ap-status-red' : ''),(scope.row.ap_payment_status == '4' ? 'ap-status-green' : ''),(scope.row.ap_payment_status == '5' ? 'ap-status-orange' : '')" :aria-label="scope.row.payment_status_name">{{scope.row.payment_status_name}}</span></div>
                                                    </div>
                                                    <div class="ap-expand-top-row-data">
                                                        <div class="ap-expan-top-data">
                                                            <span class="ap-expand-top-price" :aria-label="scope.row.ap_formated_payment_amount">{{scope.row.ap_formated_payment_amount}}</span>                                                            
                                                        </div>                                                                                                                
                                                    </div>                                                    
                                                </div>    
                                            </template>
                                        </el-table-column>
                                    </el-table>                                    

                                    <el-row id="ap_fixed_pagination_id" :class="affiliatepress_footer_dynamic_class" v-if="payments_items.length != 0 && payments_pagination_count != 1 && payments_pagination_count != 0" class="ap-pagination" type="flex"> 
                                        <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" >
                                            <div class="ap-pagination-left">
                                                <p><span :aria-label="payments_pagination_label" v-html="payments_pagination_label"></p>
                                            </div>
                                        </el-col>
                                        <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" class="ap-pagination-nav">
                                            <el-pagination :pager-count="(current_screen_size == 'mobile')?3:7" @current-change="handlePaymentPage" v-model:current-page="payments_currentPage" background layout="prev, pager, next" :total="payments_totalItems" :page-size="payments_perpage"></el-pagination>
                                        </el-col>                                               
                                    </el-row>                            
                                </div>
                            </div>

                        </div>
                    </div>

                    <div v-if="affiliate_current_tab == 'commission'" class="ap-affiliate-panel-content">  
                        <div v-if="is_display_tab_content_loader == '1'" class="ap-panel-loader">
                            <div class="ap-front-loader-container ap-panel-front-loader">
                                <div class="ap-front-loader"></div>
                            </div>
                        </div>
                        <div v-if="is_display_tab_content_loader == '0'" class="ap-panel-detail">


                            <el-dialog modal-class="ap-affiliate-dialog ap-affiliate-filter-dialog ap-affiliate-creative-filter-dialog ap-mobile-full-screen-dialog" v-model="open_commission_filter_modal" title="" width="767">
                                <div class="ap-affiliate-dialog">                               
                                    <div class="ap-affiliate-dialog-header">
                                        <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.filters" v-html="affiliate_panel_labels.filters"></div>
                                        <div @click="open_commission_filter_modal = false" class="ap-dialog-close1 ap-flex-center">
                                            <?php do_action('affiliatepress_common_affiliate_panel_svg_code','close_dialog'); ?>
                                        </div>                                     
                                    </div>
                                    <div class="ap-affiliate-dialog-body">
                                        <div class="ap-single-field__form">  
                                            <span class="ap-form-label" aria-label="affiliate_panel_labels.commission_date" v-html="affiliate_panel_labels.commission_date"></span>
                                            <el-date-picker popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="commissions_search.ap_commission_search_date" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliate_panel_labels.start_date" :end-placeholder="affiliate_panel_labels.end_date"  :default-time="defaultTime"/>
                                        </div>
                                        <div class="ap-single-field__form">  
                                            <span class="ap-form-label" aria-label="affiliate_panel_labels.commission_status" v-html="affiliate_panel_labels.commission_status"></span>
                                            <el-select class="ap-form-control" size="large" v-model="commissions_search.commission_status" :placeholder="affiliate_panel_labels.commission_select_status" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">                            
                                                <el-option v-for="item in all_commissions_status" :key="item.value" :label="item.text" :value="item.value"></el-option>                            
                                            </el-select>
                                        </div>                                                                        
                                    </div>
                                </div>
                                <div class="ap-tf-btn-group ap-filter-popup-btn-group">
                                    <el-button @click="applypopupCommissionFilter" class="ap-btn--primary ap-btn--full-width" plain type="primary" :disabled="is_apply_disabled">
                                        <span class="ap-btn__label" :aria-label="affiliate_panel_labels.apply" v-html="affiliate_panel_labels.apply"></span>
                                    </el-button>
                                    <el-button @click="resetpopupcommission" class="ap-btn--second ap-btn--full-width">
                                        <span class="ap-btn__label" :aria-label="affiliate_panel_labels.reset" v-html="affiliate_panel_labels.reset"></span>
                                    </el-button>
                                </div>                            
                            </el-dialog>
                            <div class="ap-affiliate-panel-content-header ap-affiliat-panel-pading">
                                <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.commission_affiliate_commission" v-html="affiliate_panel_labels.commission_affiliate_commission"></div>   
                                <div class="ap-header-right ap-flex-center ap-header-filter-right">
                                    <el-button @click="open_commission_filter_modal = true" class="ap-filter-icon" aria-label="<?php esc_attr_e('Filter', 'affiliatepress-affiliate-marketing'); ?>">
                                        <?php do_action('affiliatepress_common_affiliate_panel_svg_code','filter_icon'); ?>
                                    </el-button>
                                </div>                                                     
                            </div>
                            <div class="ap-table-filter ap-affiliat-panel-pading">
                                <el-row type="flex" :gutter="24">
                                    <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="17" :xl="17">   
                                        <el-row type="flex" :gutter="16"> 
                                            <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="17" :xl="17">   
                                                <div>  
                                                    <el-date-picker popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="commissions_search.ap_commission_search_date" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliate_panel_labels.start_date" :end-placeholder="affiliate_panel_labels.end_date"  :default-time="defaultTime"/>
                                                </div> 
                                            </el-col>
                                            <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="7" :xl="7">   
                                                <div>    
                                                    <el-select class="ap-form-control" size="large" v-model="commissions_search.commission_status" :placeholder="affiliate_panel_labels.commission_select_status" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">                            
                                                        <el-option v-for="item in all_commissions_status" :key="item.value" :label="item.text" :value="item.value"></el-option>                            
                                                    </el-select>
                                                </div>               
                                            </el-col>
                                        </el-row>
                                    </el-col> 
                                    <el-col class="ap-front-filter-btn" :xs="24" :sm="24" :md="24" :lg="7" :xl="7">
                                        <div class="ap-tf-btn-group">
                                            <el-button @click="applyCommissionsFilter" class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.apply" v-html="affiliate_panel_labels.apply"></span>
                                            </el-button>
                                            <el-button @click="resetcommissions" class="ap-btn--second" v-if="(commissions_search.ap_commission_search_date && commissions_search.ap_commission_search_date.length != 0) || commissions_search.commission_status != ''">
                                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.reset" v-html="affiliate_panel_labels.reset"></span>
                                            </el-button>
                                        </div>
                                    </el-col>
                                </el-row>
                            </div>                    
                            <div class="ap-panel-data-container ap-aff-panel-table">
                                <div v-if="affiliate_commission_loader == '1'" class="ap-panel-loader ap-panel-inner-loader">
                                    <div class="ap-front-loader-container ap-panel-front-loader">
                                        <div class="ap-front-loader"></div>
                                    </div>
                                </div>   
                                <div :class="(affiliatepress_footer_dynamic_class != '' && open_commission_filter_modal == false)?'ap-front-content-with-scroll':''" class="ap-front-content-data">
                                    <div id="ap-loader-div" v-if="commission_items.length == 0 && affiliate_commission_loader == '0'">
                                        <el-row type="flex">
                                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                <div class="ap-data-empty-view">
                                                    <div class="ap-ev-left-vector">
                                                        <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                                        <div class="no-data-found-text" :aria-label="affiliate_panel_labels.no_data" v-html="affiliate_panel_labels.no_data"></div>
                                                        <div class="no-data-found-description" :aria-label="affiliate_panel_labels.no_data_description" v-html="affiliate_panel_labels.no_data_description"></div>
                                                    </div>                       
                                                </div>
                                            </el-col>
                                        </el-row>
                                    </div>

                                    <el-table v-if="commission_items.length != 0 && (current_screen_size == 'tablet' || current_screen_size == 'desktop')" @sort-change="handleCommissionSortChange" :class="(affiliate_commission_loader == '1')?'ap-hidden-table':''" :data="commission_items">
                                        <el-table-column prop="" label="" width="28"></el-table-column>
                                        <el-table-column  prop="ap_commission_id" :label="affiliate_panel_labels.commission_id" >
                                            <template #default="scope">  
                                                <span :aria-label="scope.row.ap_commission_id">#{{scope.row.ap_commission_id}}</span>
                                            </template>
                                        </el-table-column>
                                        <el-table-column width="220"  prop="affiliatepress_commission_product" :label="affiliate_panel_labels.commission_product">
                                            <template #default="scope">  
                                                <span :aria-label="scope.row.affiliatepress_commission_product">{{scope.row.affiliatepress_commission_product}}</span>
                                            </template>
                                        </el-table-column>                                        
                                        <el-table-column sortable sort-by="ap_commission_created_date" prop="commission_created_date_formated" :label="affiliate_panel_labels.commission_date">
                                            <template #default="scope">  
                                                <span :aria-label="scope.row.commission_created_date_formated">{{scope.row.commission_created_date_formated}}</span>
                                            </template>
                                        </el-table-column>
                                        <el-table-column width="200" class-name="ap-padding-right-cls" align="right" header-align="right" prop="ap_formated_commission_amount" :label="affiliate_panel_labels.commission_amount">
                                            <template #default="scope">  
                                                <span :aria-label="scope.row.ap_formated_commission_amount">{{scope.row.ap_formated_commission_amount}}</span>
                                            </template>
                                        </el-table-column>
                                        <el-table-column prop="ap_commission_status" :label="affiliate_panel_labels.commission_status">
                                            <template #default="scope">  
                                                <span :aria-label="scope.row.ap_commission_status_name" class="ap-status-col" :class="(scope.row.ap_commission_status == '1' ? 'ap-status-blue' : ''),(scope.row.ap_commission_status == '2' ? 'ap-status-orange' : ''),(scope.row.ap_commission_status == '3' ? 'ap-status-red' : ''),(scope.row.ap_commission_status == '4' ? 'ap-status-green' : '')">{{scope.row.ap_commission_status_name}}</span>
                                            </template>
                                        </el-table-column>
                                    </el-table>
                                    <el-table ref="multipleTable"  class="ap-mobile-view-table" :class="(affiliate_commission_loader == '1')?'ap-hidden-table':''" cell-class-name="ap-expand-title-col" v-if="commission_items.length != 0 && (current_screen_size == 'mobile')" @sort-change="handleCommissionSortChange" :data="commission_items" @row-click="affiliatepress_panel_full_row_clickable">                                        
                                        <el-table-column type="expand">
                                            <template #default="scope">
                                                <div class="ap-expand-row">
                                                    <div class="ap-expand-sub-row ap-mb-5">
                                                        <div class="ap-expand-value">
                                                            <span class="ap-mr-3" :aria-label="affiliate_panel_labels.commission_id">{{affiliate_panel_labels.commission_id}} :</span><span class="ap-expand-row-title" :aria-label="scope.row.ap_commission_id">#{{scope.row.ap_commission_id}}</span>                                                             
                                                        </div>
                                                        <div class="ap-expand-value">
                                                            <span class="ap-mr-3" :aria-label="affiliate_panel_labels.commission_date">{{affiliate_panel_labels.commission_date}} :</span><span class="ap-expand-row-title" :aria-label="scope.row.commission_created_date_formated">{{scope.row.commission_created_date_formated}}</span>                                                             
                                                        </div>
                                                    </div>
                                                    <div class="ap-expand-sub-row ap-mb-5">
                                                        <div class="ap-expand-value">
                                                            <span class="ap-mr-3" :aria-label="affiliate_panel_labels.commission_amount">{{affiliate_panel_labels.commission_amount}} :</span>                                                             
                                                        </div>
                                                        <div class="ap-expand-value">
                                                            <span class="ap-expan-price-data" :aria-label="scope.row.ap_formated_commission_amount">{{scope.row.ap_formated_commission_amount}}</span>                                                             
                                                        </div>
                                                    </div>
                                                    <div class="ap-expand-sub-row ap-mb-5">
                                                        <div class="ap-expand-value">
                                                            <span class="ap-mr-3" :aria-label="affiliate_panel_labels.commission_product">{{affiliate_panel_labels.commission_product}} :</span>
                                                            <span class="ap-expand-row-title" :aria-label="scope.row.affiliatepress_commission_product">{{scope.row.affiliatepress_commission_product}}</span>                                                             
                                                        </div>                                                        
                                                    </div>                                                                                    
                                                </div>    
                                            </template>
                                        </el-table-column>
                                        <el-table-column prop="ap_commission_id">
                                            <template #default="scope">
                                                <div class="ap-expand-top-row">
                                                    <div class="ap-expand-top-row-data ap-mb-5">
                                                        <div class="ap-expan-top-data ap-expand-top-head-left"><span class="ap-com-date"><span class="ap-date-cal-icon ap-mr-8"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','date_calendar_icon'); ?></span><span :aria-label="scope.row.commission_created_date_formated">{{scope.row.commission_created_date_formated}}</span></span></div>
                                                        <div class="ap-expan-top-data ap-expand-top-head-right"><span class="ap-status-col" :class="(scope.row.ap_commission_status == '1' ? 'ap-status-blue' : ''),(scope.row.ap_commission_status == '2' ? 'ap-status-orange' : ''),(scope.row.ap_commission_status == '3' ? 'ap-status-red' : ''),(scope.row.ap_commission_status == '4' ? 'ap-status-green' : '')" :aria-label="scope.row.ap_commission_status_name">{{scope.row.ap_commission_status_name}}</span></div>
                                                    </div>
                                                    <div class="ap-expand-top-row-data">
                                                        <div class="ap-expan-top-data">
                                                            <span class="ap-expand-top-price" :aria-label="scope.row.ap_formated_commission_amount">{{scope.row.ap_formated_commission_amount}}</span>
                                                        </div>                                                                                                                
                                                    </div>                                                    
                                                </div>    
                                            </template>
                                        </el-table-column>
                                    </el-table>

                                    <el-row id="ap_fixed_pagination_id" :class="affiliatepress_footer_dynamic_class" v-if="commission_items.length != 0 && commission_pagination_counts != 0 && commission_pagination_counts != 1" class="ap-pagination ap-pagination-mobile-nav" type="flex">
                                        <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" >
                                            <div class="ap-pagination-left">
                                                <p><span :aria-label="commission_pagination_labels" v-html="commission_pagination_labels"></span>
                                            </div>
                                        </el-col>
                                        <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" class="ap-pagination-nav" >
                                            <el-pagination :pager-count="(current_screen_size == 'mobile')?3:7" :pager-count="(current_screen_size == 'desktop')?7:5" @current-change="handleCommissionPage" v-model:current-page="commission_currentPage" background layout="prev, pager, next" :total="commission_totalItems" :page-size="commission_perpage"></el-pagination>
                                        </el-col>                                               
                                    </el-row>                                                            
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="affiliate_current_tab == 'dashboard'" class="ap-affiliate-panel-content ap-dashboard-content">
                        <div v-if="is_display_tab_content_loader == '1'" class="ap-panel-loader">
                            <div class="ap-front-loader-container ap-panel-front-loader">
                                <div class="ap-front-loader"></div>
                            </div>
                        </div>
                        <div v-if="is_display_tab_content_loader == '0'" class="ap-panel-detail">
                            <div class="ap-affiliate-panel-content-header ap-affiliat-panel-pading">
                                <div class="ap-tab-heading" v-html="affiliate_panel_labels.dashboard_affiliate_dashboard" :aria-label="affiliate_panel_labels.dashboard_affiliate_dashboard"></div>
                            </div>
                            <div class="ap-table-filter ap-affiliat-panel-pading">
                                <el-row type="flex" :gutter="24">
                                    <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="12" :lg="12" :xl="12">   
                                        <div>  
                                            <el-date-picker :teleported="false" popper-class="ap-date-range-picker-widget-wrapper ap-date-range-picker-sidebar-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="dashboard_date_range" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliate_panel_labels.start_date" :end-placeholder="affiliate_panel_labels.end_date"  :shortcuts="shortcuts" :default-time="defaultTime"/>
                                        </div>       
                                    </el-col> 
                                    <el-col class="ap-front-filter-btn" :xs="24" :sm="24" :md="7" :lg="7" :xl="7">
                                        <div class="ap-tf-btn-group">
                                            <el-button @click="change_dashboard_date" class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                                                <span class="ap-btn__label" v-html="affiliate_panel_labels.apply" :aria-label="affiliate_panel_labels.apply"></span>
                                            </el-button>
                                        </div>
                                    </el-col>
                                </el-row>
                            </div> 
                            <div class="ap-panel-data-container ap-aff-panel-table">
                                <div class="ap-front-content-data">
                                    <div class="ap-panel-loader ap-panel-inner-loader" v-if="affiliate_dashboard_loader == '1'">
                                        <div class="ap-front-loader-container ap-panel-front-loader">
                                            <div class="ap-front-loader"></div>
                                        </div>
                                    </div>               
                                    <div v-if="affiliate_dashboard_loader == '0'">
                                        <div class="ap-cards-container ap-affiliat-panel-pading">
                                                <el-row :gutter="24" type="flex">
                                                    <el-col :xs="24" :sm="12" :md="12" :lg="8" :xl="8">
                                                        <el-card class="ap-box-card">
                                                            <div class="ap-box-card-flex">
                                                                <div class="ap-box-card-icon">
                                                                    <?php do_action('affiliatepress_common_affiliate_panel_svg_code','total_earnings') ?>
                                                                </div>
                                                                <div class="ap-box-right">
                                                                    <div class="header">
                                                                        <span v-html="affiliate_panel_labels.dashboard_total_earnings" :aria-label="affiliate_panel_labels.dashboard_total_earnings"></span>
                                                                    </div>
                                                                    <div class="ap-box-card-value" v-html="dashboard_total_earning" :aria-label="dashboard_total_earning"></div>
                                                                </div>
                                                        </div>
                                                        </el-card>
                                                    </el-col>
                                                    <el-col :xs="24" :sm="12" :md="12" :lg="8" :xl="8">
                                                        <el-card class="ap-box-card">
                                                            <div class="ap-box-card-flex">
                                                                <div class="ap-box-card-icon">
                                                                    <?php do_action('affiliatepress_common_affiliate_panel_svg_code','paid_earnings') ?>
                                                                </div>
                                                                <div class="ap-box-right">
                                                                    <div class="header">
                                                                    <span v-html="affiliate_panel_labels.dashboard_paid_earnings" :aria-label="affiliate_panel_labels.dashboard_paid_earnings"></span>
                                                                    </div>
                                                                    <div class="ap-box-card-value" v-html="dashboard_paid_earning" :aria-label="dashboard_paid_earning"></div>
                                                                </div>
                                                        </div>
                                                        </el-card>
                                                    </el-col>
                                                    <el-col :xs="24" :sm="12" :md="12" :lg="8" :xl="8">
                                                        <el-card class="ap-box-card">
                                                            <div class="ap-box-card-flex">
                                                                <div class="ap-box-card-icon">
                                                                    <?php do_action('affiliatepress_common_affiliate_panel_svg_code','unpaid_earnings') ?>
                                                                </div>
                                                                <div class="ap-box-right">
                                                                    <div class="header">
                                                                    <span v-html="affiliate_panel_labels.dashboard_unpaid_earnings" :aria-label="affiliate_panel_labels.dashboard_unpaid_earnings"></span>
                                                                    </div>
                                                                    <div class="ap-box-card-value" v-html="dashboard_unpaid_earning" :aria-label="dashboard_unpaid_earning"></div>
                                                                </div>
                                                        </div>
                                                        </el-card>
                                                    </el-col>
                                                    <el-col :xs="24" :sm="12" :md="12" :lg="8" :xl="8">
                                                        <el-card class="ap-box-card">
                                                            <div class="ap-box-card-flex">
                                                                <div class="ap-box-card-icon">
                                                                    <?php do_action('affiliatepress_common_affiliate_panel_svg_code','visits') ?>
                                                                </div>
                                                                <div class="ap-box-right">
                                                                    <div class="header">
                                                                    <span v-html="affiliate_panel_labels.dashboard_visits_count" :aria-label="affiliate_panel_labels.dashboard_visits_count"></span>
                                                                    </div>
                                                                    <div class="ap-box-card-value" v-html="dashboard_total_visits" :aria-label="dashboard_total_visits"></div>
                                                                </div>
                                                        </div>
                                                        </el-card>
                                                    </el-col>
                                                    <el-col :xs="24" :sm="12" :md="12" :lg="8" :xl="8">
                                                        <el-card class="ap-box-card">
                                                            <div class="ap-box-card-flex">
                                                                <div class="ap-box-card-icon">
                                                                    <?php do_action('affiliatepress_common_affiliate_panel_svg_code','total_commission') ?>
                                                                </div>
                                                                <div class="ap-box-right">
                                                                    <div class="header">
                                                                    <span v-html="affiliate_panel_labels.dashboard_commissions_count" :aria-label="affiliate_panel_labels.dashboard_commissions_count"></span>
                                                                    </div>
                                                                    <div class="ap-box-card-value" v-html="dashboard_total_commission" :aria-label="dashboard_total_commission"></div>
                                                                </div>
                                                        </div>
                                                        </el-card>
                                                    </el-col>
                                                    <el-col :xs="24" :sm="12" :md="12" :lg="8" :xl="8">
                                                        <el-card class="ap-box-card">
                                                            <div class="ap-box-card-flex">
                                                                <div class="ap-box-card-icon">
                                                                    <?php do_action('affiliatepress_common_affiliate_panel_svg_code','commission_rate') ?>
                                                                </div>
                                                                <div class="ap-box-right">
                                                                    <div class="header">
                                                                    <span v-html="affiliate_panel_labels.dashboard_commission_rate" :aria-label="affiliate_panel_labels.dashboard_commission_rate"></span>
                                                                    </div>
                                                                    <div class="ap-box-card-value" v-html="default_commission_rate" :aria-label="default_commission_rate"></div>
                                                                </div>
                                                        </div>
                                                        </el-card>
                                                    </el-col>
                                                </el-row>
                                        </div>  
                                        <div class="ap-chart-data-card ap-affiliat-panel-pading">
                                                <div class="ap-tab-heading" v-html="affiliate_panel_labels.dashboard_reports" :aria-label="affiliate_panel_labels.dashboard_reports"></div>
                                                <div class="ap-chart-data">
                                                    <canvas class="ap-canvas-chart-data" id="revenue_chart"></canvas>                                        
                                                </div>                                    
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>   
                    <div v-if="affiliate_current_tab == 'visit'" class="ap-affiliate-panel-content">
                        <div v-if="is_display_tab_content_loader == '1'" class="ap-panel-loader">
                            <div class="ap-front-loader-container ap-panel-front-loader">
                                <div class="ap-front-loader"></div>
                            </div>
                        </div>
                        <div :class="(affiliatepress_footer_dynamic_class != '')?'ap-front-content-with-scroll':''" v-if="is_display_tab_content_loader == '0'" class="ap-panel-detail">
                            <el-dialog modal-class="ap-affiliate-dialog ap-affiliate-filter-dialog ap-affiliate-creative-filter-dialog ap-mobile-full-screen-dialog" v-model="open_visit_filter_modal" title="" width="767">
                                    <div class="ap-affiliate-dialog">                               
                                        <div class="ap-affiliate-dialog-header">
                                            <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.filters" v-html="affiliate_panel_labels.filters"></div>
                                            <div @click="open_visit_filter_modal = false" class="ap-dialog-close1 ap-flex-center">
                                                <?php do_action('affiliatepress_common_affiliate_panel_svg_code','close_dialog'); ?>
                                            </div>                                     
                                        </div>
                                        <div class="ap-affiliate-dialog-body">
                                            <div class="ap-single-field__form">  
                                                <span class="ap-form-label" aria-label="affiliate_panel_labels.visit_date" v-html="affiliate_panel_labels.visit_date"></span>
                                                <el-date-picker popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="visits_search.ap_visit_date" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliate_panel_labels.start_date" :end-placeholder="affiliate_panel_labels.end_date"  :unlink-panels="false" :default-time="defaultTime"/>
                                            </div>
                                            <div class="ap-single-field__form">  
                                                <span class="ap-form-label" aria-label="affiliate_panel_labels.visit_select_type" v-html="affiliate_panel_labels.visit_select_type"></span>
                                                <el-select class="ap-form-control" size="large" v-model="visits_search.visit_type" :placeholder="affiliate_panel_labels.visit_select_type" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">   
                                                    <el-option :label="affiliate_panel_labels.visit_all" value="all_visit"></el-option>                                  
                                                    <el-option :label="affiliate_panel_labels.visit_converted" value="converted"></el-option>
                                                    <el-option :label="affiliate_panel_labels.visit_unconverted_status" value="not_converted"></el-option>                           
                                                </el-select>
                                            </div>                                                                        
                                        </div>
                                    </div>
                                    <div class="ap-tf-btn-group ap-filter-popup-btn-group">
                                        <el-button @click="applypopupVisitFilter" class="ap-btn--primary ap-btn--full-width" plain type="primary" :disabled="is_apply_disabled">
                                            <span class="ap-btn__label" :aria-label="affiliate_panel_labels.apply" v-html="affiliate_panel_labels.apply"></span>
                                        </el-button>
                                        <el-button @click="resetpopupvisit" class="ap-btn--second ap-btn--full-width">
                                            <span class="ap-btn__label" :aria-label="affiliate_panel_labels.reset" v-html="affiliate_panel_labels.reset"></span>
                                        </el-button>
                                    </div>                            
                            </el-dialog>

                            <div class="ap-affiliate-panel-content-header ap-affiliat-panel-pading">
                                <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.visit_visits" v-html="affiliate_panel_labels.visit_visits"></div>                        
                                <div class="ap-header-right ap-flex-center ap-header-filter-right">
                                    <el-button @click="open_visit_filter_modal = true" class="ap-filter-icon" aria-label="<?php esc_attr_e('Filter', 'affiliatepress-affiliate-marketing'); ?>">
                                        <?php do_action('affiliatepress_common_affiliate_panel_svg_code','filter_icon'); ?>
                                    </el-button>
                                </div>
                            </div>
                            <div class="ap-table-filter ap-affiliat-panel-pading">
                                <el-row type="flex" :gutter="24">
                                    <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="17" :xl="17">   
                                        <el-row type="flex" :gutter="16"> 
                                            <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="17" :xl="17">   
                                                <div>  
                                                <el-date-picker popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="visits_search.ap_visit_date" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliate_panel_labels.start_date" :end-placeholder="affiliate_panel_labels.end_date"  :default-time="defaultTime"/>
                                                </div>       
                                            </el-col>
                                            <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="7" :xl="7">   
                                                <div>    
                                                    <el-select class="ap-form-control" size="large" v-model="visits_search.visit_type" :placeholder="affiliate_panel_labels.visit_select_type" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">       
                                                        <el-option :label="affiliate_panel_labels.visit_all" value="all_visit"></el-option>                              
                                                        <el-option :label="affiliate_panel_labels.visit_converted" value="converted"></el-option>
                                                        <el-option :label="affiliate_panel_labels.visit_unconverted_status" value="not_converted"></el-option>                           
                                                    </el-select>
                                                </div>               
                                            </el-col>
                                        </el-row>
                                    </el-col> 
                                    <el-col class="ap-front-filter-btn" :xs="24" :sm="24" :md="24" :lg="7" :xl="7">
                                        <div class="ap-tf-btn-group">
                                            <el-button @click="applyVisitFilter"  class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.apply" v-html="affiliate_panel_labels.apply"></span>
                                            </el-button>
                                            <el-button @click="resetvisit" class="ap-btn--second" v-if="(visits_search.ap_visit_date && visits_search.ap_visit_date.length != 0) || visits_search.visit_type != 'all_visit'">
                                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.reset" v-html="affiliate_panel_labels.reset"></span>
                                            </el-button>
                                        </div>
                                    </el-col>
                                </el-row>
                            </div>                    
                            <div class="ap-panel-data-container ap-aff-panel-table">
                                <div class="ap-front-content-data">
                                    <div v-if="affiliate_visit_loader == '1'" class="ap-panel-loader ap-panel-inner-loader">
                                        <div class="ap-front-loader-container ap-panel-front-loader">
                                            <div class="ap-front-loader"></div>
                                        </div>
                                    </div>  
                                    <div id="ap-loader-div" v-if="visits_items.length == 0 && affiliate_visit_loader == '0'">
                                        <el-row type="flex">
                                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                <div class="ap-data-empty-view">
                                                    <div class="ap-ev-left-vector">
                                                        <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                                        <div class="no-data-found-text" :aria-label="affiliate_panel_labels.no_data" v-html="affiliate_panel_labels.no_data"></div>
                                                        <div class="no-data-found-description" :aria-label="affiliate_panel_labels.no_data_description" v-html="affiliate_panel_labels.no_data_description"></div>
                                                    </div>                       
                                                </div>
                                            </el-col>
                                        </el-row>
                                    </div>                                                              
                                    <div v-if="visits_items.length != 0 && (current_screen_size == 'tablet' || current_screen_size == 'desktop')" :class="(visits_height)?'ap-panel-visits-table':''" class="ap-visits-table-data ap-horizontal-scroll">
                                        <el-table :class="(affiliate_visit_loader == '1')?'ap-hidden-table':''"  @sort-change="handleVisitSortChange"  :data="visits_items">
                                            <el-table-column prop="" label="" min-width="10"></el-table-column>
                                            <el-table-column  min-width="70" prop="sr_no" :label="affiliate_panel_labels.visit_serial_number" sortable sort-by="sr_no">
                                                <template #default="scope">
                                                    <span :aria-label="scope.row.sr_no">{{scope.row.sr_no}}</span>
                                                </template>
                                            </el-table-column>
                                            <el-table-column min-width="150" sortable sort-by="ap_visit_created_date" prop="visit_created_date_formated" :label="affiliate_panel_labels.visit_date">
                                                <template #default="scope">
                                                    <span :aria-label="scope.row.visit_created_date_formated">{{scope.row.visit_created_date_formated}}</span>
                                                </template>
                                            </el-table-column>
                                            <el-table-column min-width="150" prop="ap_affiliates_campaign_name" :label="affiliate_panel_labels.visit_compaign">
                                                <template #default="scope">
                                                    <span :aria-label="scope.row.ap_affiliates_campaign_name">{{scope.row.ap_affiliates_campaign_name}}</span>
                                                </template>
                                            </el-table-column>
                                            <el-table-column min-width="120" prop="ap_visit_ip_address" :label="affiliate_panel_labels.visit_ip_address">
                                                <template #default="scope">
                                                    <span :aria-label="scope.row.ap_visit_ip_address">{{scope.row.ap_visit_ip_address}}</span>
                                                </template>
                                            </el-table-column>
                                            <el-table-column min-width="120" align="center" prop="ap_commission_id" :label="affiliate_panel_labels.visit_converted">
                                                <template #default="scope">
                                                    <span v-if="scope.row.ap_commission_id == 0 || scope.row.ap_commission_id == ''">
                                                    -
                                                    </span>
                                                    <span v-else>
                                                        <?php do_action('affiliatepress_common_svg_code','right_icon'); ?>                                        
                                                    </span>                                    
                                                </template>                                     
                                            </el-table-column>
                                            <el-table-column min-width="300" prop="ap_visit_landing_url" :label="affiliate_panel_labels.visit_landing_url">
                                                <template #default="scope">
                                                    <div  class="ap-url-wrapper"  :class="{ clickable: scope.row._isOverflow }" @click="scope.row._isOverflow ? (scope.row._expanded = true) : null">
                                                        <div class="ap-url-text" :class="{ expanded: scope.row._expanded }" :ref="checklandingOverflow(scope.row)" >  {{ scope.row.ap_visit_landing_url }}</div>
                                                        <a v-if="scope.row._isOverflow && !scope.row._expanded" class="ap-more-inline ap-refrance-link" @click.stop="scope.row._expanded = true" >...</a>
                                                    </div>
                                                </template>
                                            </el-table-column>
                                            <el-table-column min-width="250" prop="ap_referrer_url" :label="affiliate_panel_labels.visit_referrer_url">
                                                <template #default="scope">
                                                    <div   v-if="scope.row.ap_referrer_url"  class="ap-url-wrapper" :class="{ clickable: scope.row._refOverflow }" @click="scope.row._refOverflow ? (scope.row._refExpanded = true) : null">
                                                        <div class="ap-url-text" :class="{ expanded: scope.row._refExpanded }" :ref="checkrefgOverflow(scope.row)" >{{ scope.row.ap_referrer_url }}</div>
                                                        <a v-if="scope.row._refOverflow && !scope.row._refExpanded"  class="ap-more-inline ap-refrance-link" @click.stop="scope.row._refExpanded = true">...</a>
                                                    </div>
                                                    <span v-if="!scope.row.ap_referrer_url">-</span>
                                                </template>
                                            </el-table-column>
                                            <el-table-column prop="" label="" min-width="30"></el-table-column>
                                        </el-table>
                                    </div>
                                    <el-table ref="multipleTable" class="ap-mobile-view-table" cell-class-name="ap-expand-title-col" v-if="visits_items.length != 0 && (current_screen_size == 'mobile')" @sort-change="handleCommissionSortChange" :class="(affiliate_visit_loader == '1')?'ap-hidden-table':''" :data="visits_items" @row-click="affiliatepress_panel_full_row_clickable">                                        
                                        <el-table-column type="expand">
                                            <template #default="scope">
                                                <div class="ap-expand-row">
                                                    <div class="ap-expand-sub-row ap-mb-10">
                                                        <div class="ap-expand-value">
                                                            <span class="ap-mr-3" :aria-label="affiliate_panel_labels.visit_serial_number">{{affiliate_panel_labels.visit_serial_number}} :</span><span class="ap-expand-row-title" :aria-label="scope.row.sr_no">{{scope.row.sr_no}}</span>                                                             
                                                        </div>
                                                        <div class="ap-expand-value">
                                                            <span class="ap-expand-row-title" :aria-label="scope.row.visit_created_date_formated">{{scope.row.visit_created_date_formated}}</span>       
                                                        </div>
                                                    </div>
                                                    <div class="ap-expand-sub-row">
                                                        <div v-if="scope.row.ap_affiliates_campaign_name" class="ap-expand-value">
                                                            <span :aria-label="affiliate_panel_labels.visit_compaign">{{affiliate_panel_labels.visit_compaign}}:</span>                                                           
                                                        </div>
                                                        <div class="ap-expand-value">
                                                            <span :aria-label="affiliate_panel_labels.visit_ip_address">{{affiliate_panel_labels.visit_ip_address}}:</span>       
                                                        </div>
                                                    </div>
                                                    <div class="ap-expand-sub-row ap-mb-10">
                                                        <div v-if="scope.row.ap_affiliates_campaign_name" class="ap-expand-value">
                                                            <span class="ap-expand-row-title" :aria-label="scope.row.ap_affiliates_campaign_name">{{scope.row.ap_affiliates_campaign_name}}</span>                                                             
                                                        </div>
                                                        <div class="ap-expand-value">
                                                            <span class="ap-expand-row-title" :aria-label="scope.row.ap_visit_ip_address">{{scope.row.ap_visit_ip_address}}</span>       
                                                        </div>
                                                    </div>
                                                    <div class="ap-expand-sub-row">
                                                        <div class="ap-expand-value">
                                                            <span :aria-label="affiliate_panel_labels.visit_landing_url">{{affiliate_panel_labels.visit_landing_url}}:</span>                                                           
                                                        </div>                                                        
                                                    </div>
                                                    <div class="ap-expand-sub-row ap-mb-10">
                                                        <div class="ap-expand-value">
                                                            <span class="ap-expand-row-title ap-data-single-line" :aria-label="scope.row.ap_visit_landing_url">{{scope.row.ap_visit_landing_url}}</span>                                                             
                                                        </div>                                                        
                                                    </div>
                                                    <div class="ap-border-seperator ap-mb-10"></div>
                                                        <div class="ap-expand-sub-row">
                                                            <div class="ap-expand-value">
                                                                <span :aria-label="affiliate_panel_labels.visit_referrer_url">{{affiliate_panel_labels.visit_referrer_url}}:</span>                                                           
                                                            </div>                                                        
                                                        </div>
                                                        <div class="ap-expand-sub-row ap-mb-5">
                                                            <div class="ap-expand-value">
                                                                <span v-if="scope.row.ap_referrer_url" class="ap-expand-row-title ap-data-single-line" :aria-label="scope.row.ap_referrer_url">{{scope.row.ap_referrer_url}} </span>                                                                                     
                                                                <span class="ap-expand-row-title ap-data-single-line" v-else >-</span>
                                                            </div>                                                        
                                                        </div>                               
                                                    </div>   
                                                </div>     
                                            </template>
                                        </el-table-column>
                                        <el-table-column prop="ap_commission_id">
                                            <template #default="scope">
                                                <div class="ap-expand-top-row">
                                                    <div class="ap-expand-top-row-data ap-mb-5">
                                                        <div class="ap-expan-top-data ap-expand-top-head-left"><span class="ap-com-date"><span class="ap-date-cal-icon ap-mr-8"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','date_calendar_icon'); ?></span><span :aria-label="scope.row.visit_created_date_formated">{{scope.row.visit_created_date_formated}}</span></span></div>
                                                        <div class="ap-expan-top-data ap-expand-top-head-right">
                                                            <span v-html="scope.row.ap_commission_id == '0' ? affiliate_panel_labels.visit_converted : affiliate_panel_labels.visit_unconverted_status" class="ap-status-col"  :class="scope.row.ap_commission_id == '0' ? 'ap-status-red' : 'ap-status-green'":aria-label="scope.row.ap_commission_id == '0'  ? affiliate_panel_labels.visit_converted  : affiliate_panel_labels.visit_unconverted_status"></span>
                                                        </div>
                                                    </div>
                                                    <div class="ap-expand-top-row-data">
                                                        <div class="ap-expan-top-data">
                                                            <span class="ap-expand-top-price" :aria-label="scope.row.ap_affiliates_campaign_name">{{scope.row.ap_affiliates_campaign_name}}</span>                                                            
                                                        </div>                                                                                                                
                                                    </div>                                                    
                                                </div>    
                                            </template>
                                        </el-table-column>
                                    </el-table>                                    

                                    <el-row id="ap_fixed_pagination_id" :class="affiliatepress_footer_dynamic_class" v-if="visits_items.length != 0 && visits_pagination_count != 0 && visits_pagination_count != 1" class="ap-pagination" type="flex"> 
                                        <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" >
                                            <div class="ap-pagination-left">
                                                <p><span :aria-label="visits_pagination_label" v-html="visits_pagination_label">
                                                <div class="ap-pagination-per-page">
                                                    <p :aria-label="affiliate_panel_labels.pagination_change_label" v-html="affiliate_panel_labels.pagination_change_label"></p>
                                                    <el-select v-model="visit_pagination_length_val" placeholder="Select" @change="visitchangePaginationSize($event)" size="large" class="ap-form-control" popper-class="ap-pagination-dropdown">
                                                        <el-option v-for="item in visit_pagination_val" :key="item.text" :label="item.text" :value="item.value"></el-option>
                                                    </el-select>
                                                </div>
                                            </div>
                                        </el-col>
                                        <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" class="ap-pagination-nav">
                                            <el-pagination :pager-count="(current_screen_size == 'mobile')?3:7" @current-change="handleVisitPage" v-model:current-page="visits_currentPage" background layout="prev, pager, next" :total="visits_totalItems" :page-size="visits_perpage"></el-pagination>
                                        </el-col>                                               
                                    </el-row>
                                </div>
                            </div>

                        </div>
                    </div>                    

                    <div v-if="affiliate_current_tab == 'affiliates_links'" class="ap-affiliate-panel-content">

                        <div v-if="is_display_tab_content_loader == '1'" class="ap-panel-loader">
                            <div class="ap-front-loader-container ap-panel-front-loader">
                                <div class="ap-front-loader"></div>
                            </div>
                        </div>
                        <div v-if="is_display_tab_content_loader == '0'" class="ap-panel-detail">

                            <div class="ap-affiliate-panel-content-header ap-affiliat-panel-pading">
                                <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.link_affiliate_links" v-html="affiliate_panel_labels.link_affiliate_links"></div>                              
                            </div>
                            <div class="ap-affiliate-default-link ap-affiliat-panel-pading">
                                <div class="ap-tab-detail-txt" :aria-label="affiliate_panel_labels.link_description" v-html="affiliate_panel_labels.link_description"></div>
                                <div class="ap-default-link-field">
                                    <div class="ap-tab-detail-txt ap-default-link-field-heading" :aria-label="affiliate_panel_labels.link_your_affiliate_link" v-html="affiliate_panel_labels.link_your_affiliate_link"></div>
                                    <div class="ap-front-copy-field">        
                                        <div class="ap-tax-link-copier" tabindex="0" @keydown.enter.prevent="affiliatepress_copy_data(affiliate_common_link)">
                                            <span class="ap-copy-data" tabindex="0" :aria-label="affiliate_common_link">{{affiliate_common_link}}</span>
                                            <el-button @click="affiliatepress_copy_data(affiliate_common_link)" type="primary" class="ap-btn--primary ap-copy-txt-btn"><span class="ap-btn__label" v-html="affiliate_panel_labels.link_click_to_copy"></span></el-button>
                                        </div> 
                                        <div class="ap-field-desc">"{{affiliate_link_slug}}" <span :aria-label="affiliate_panel_labels.link_parameter_description" v-html="affiliate_panel_labels.link_parameter_description"></span></div>                                       
                                    </div>
                                </div>    
                            </div>
                            <div class="ap-affiliat-panel-pading">
                                <div class="ap-cookie-duration-wrapper">
                                    <div class="ap-duration-data">
                                        <div><?php do_action('affiliatepress_common_affiliate_panel_svg_code','cookie_duration') ?></div>
                                        <div><strong>{{tracking_cookie_days}}</strong> {{affiliate_panel_labels.link_cookie_duration}}</div>
                                    </div>
                                    <div class="ap-cookie-description" :aria-label="affiliate_panel_labels.link_cookie_duration_description" v-html="affiliate_panel_labels.link_cookie_duration_description"></div>
                                </div>
                            </div>
                            <div class="affiliatepress_panel_separator ap-affiliate-link-seprator"></div>
                            <div class="ap-affiliate-generated-link">
                                <div class="ap-affiliate-generated-link-header ap-affiliat-panel-pading">
                                    <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.link_custome_Affiliate_links" v-html="affiliate_panel_labels.link_custome_Affiliate_links"></div>
                                    <el-button type="primary" @click="open_affiliate_link_model" class="ap-btn--primary ap-remove-m-b-title" :disabled="!is_add_affiliate_link">
                                        <span class="ap-btn__icon"><?php do_action('affiliatepress_common_svg_code','add_icon'); ?></span>
                                        <span class="ap-btn__label" :aria-label="affiliate_panel_labels.link_generate_affiliate_link" v-html="affiliate_panel_labels.link_generate_affiliate_link"></span>
                                    </el-button>                                
                                </div>
                                <el-dialog modal-class="ap-affiliate-dialog ap-mobile-full-screen-dialog ap-mobile-center-popup" v-model="open_modal" title="" width="630" >
                                    <div class="ap-affiliate-dialog">
                                        <div @click="open_modal = false" class="ap-dialog-close Fap-mobile-diplay">
                                            <?php do_action('affiliatepress_common_affiliate_panel_svg_code','close_dialog'); ?>
                                        </div>
                                        <div class="ap-affiliate-dialog-header">
                                            <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.link_generate_custome_affiliate_links" v-html="affiliate_panel_labels.link_generate_custome_affiliate_links"></div>
                                        </div>
                                        <div class="ap-affiliate-dialog-body">
                                            <div class="ap-affiliate-dialog-subtitle ap-mobile-detail-txt" :aria-label="affiliate_panel_labels.link_generate_link_description" v-html="affiliate_panel_labels.link_generate_link_description"></div>
                                            <el-form ref="affiliate_links_frm" :rules="affiliate_links_data_rules" require-asterisk-position="right" :model="affiliate_links_data">
                                                <div class="ap-single-field__form">                    
                                                    <el-form-item prop="ap_page_link">
                                                        <template #label>
                                                            <span class="ap-form-label" :aria-label="affiliate_panel_labels.link_page_url" v-html="affiliate_panel_labels.link_page_url"></span>
                                                        </template>
                                                        <el-input  ref="generate_link_pageUrlInput" class="ap-form-control" type="text" v-model="affiliate_links_data.ap_page_link" size="large" :placeholder="affiliate_panel_labels.link_enter_page_url"  maxlength="255"/>
                                                    </el-form-item>                     
                                                </div> 
                                                <div class="ap-single-field__form">                    
                                                    <el-form-item prop="ap_affiliates_campaign_name">
                                                        <template #label>
                                                            <span class="ap-form-label" :aria-label="affiliate_panel_labels.link_compaign_name" v-html="affiliate_panel_labels.link_compaign_name"></span>
                                                        </template>
                                                        <el-input class="ap-form-control" v-model="affiliate_links_data.ap_affiliates_campaign_name" type="text" size="large" :placeholder="affiliate_panel_labels.link_enter_compaign_name" />
                                                    </el-form-item>                     
                                                </div> 
                                                <div class="ap-single-field__form">                    
                                                    <el-form-item  prop="ap_affiliates_sub_id">
                                                        <template #label>
                                                            <span class="ap-form-label" :aria-label="affiliate_panel_labels.link_sub_id" v-html="affiliate_panel_labels.link_sub_id"></span>
                                                        </template>
                                                        <el-input class="ap-form-control" v-model="affiliate_links_data.ap_affiliates_sub_id" type="text" size="large" :placeholder="affiliate_panel_labels.link_enter_sub_id" />
                                                    </el-form-item>                     
                                                </div> 
                                                <div class="ap-frm-btn ap-flex-start-right">
                                                    <el-button :disabled="(affiliate_custom_link_loader == '1')?true:false" :class="(affiliate_custom_link_loader == '1') ? 'ap-btn--is-loader' : ''" @click="add_affliate_custom_link()" class="ap-btn--primary ap-btn--big"  type="primary">
                                                        <span class="ap-btn__label" :aria-label="affiliate_panel_labels.link_generate_link" v-html="affiliate_panel_labels.link_generate_link"></span>
                                                        <div class="ap-btn--loader__circles">
                                                            <div></div>
                                                            <div></div>
                                                            <div></div>
                                                        </div>                    
                                                    </el-button>                 
                                                </div>
                                            </el-form>    
                                        </div>
                                    </div>
                                </el-dialog>                            
                                <div v-if="affiliate_custom_links != '' && affiliate_custom_links.length != 0" class="ap-affiliate-link-table">
                                    <el-table v-if="(current_screen_size == 'tablet' || current_screen_size == 'desktop')" :data="affiliate_custom_links">    
                                        <el-table-column prop="" label="" min-width="10"></el-table-column>                                
                                        <el-table-column min-width="70" prop="sr_no" :label="affiliate_panel_labels.link_serial_number">
                                            <template #default="scope">
                                                <span  class="ap-copy-data" :aria-label="scope.row.sr_no">{{scope.row.sr_no}}</span>
                                            </template>   
                                        </el-table-column>
                                        <el-table-column tabindex="0" min-width="100" prop="ap_affiliates_campaign_name" :label="affiliate_panel_labels.link_campaign_name">
                                            <template #default="scope">
                                                <span  class="ap-copy-data" :aria-label="scope.row.ap_affiliates_campaign_name">{{scope.row.ap_affiliates_campaign_name}}</span>
                                            </template>   
                                        </el-table-column>                                    
                                        <el-table-column min-width="250" prop="ap_page_link" :label="affiliate_panel_labels.link_affiliate_url">
                                            <template #default="scope">
                                                <div class="ap-tax-link-copier" tabindex="0" @keydown.enter.prevent="affiliatepress_copy_data(scope.row.ap_page_link)">
                                                    <span class="ap-copy-data" :aria-label="scope.row.ap_page_link">{{scope.row.ap_page_link}}</span>
                                                    <el-button @click="affiliatepress_copy_data(scope.row.ap_page_link)" type="primary" class="ap-btn--primary ap-copy-txt-btn"><span class="ap-btn__label" v-html="affiliate_panel_labels.link_click_to_copy"></span></el-button>
                                                </div>
                                            </template>    
                                        </el-table-column>
                                        <el-table-column prop="ap_page_link" label="" min-width="115">
                                            <template #default="scope">
                                                <el-popconfirm 
                                                    :confirm-button-text=affiliate_panel_labels.yes_label
                                                    :cancel-button-text=affiliate_panel_labels.no_label
                                                    confirm-button-type="danger"
                                                    cancel-button-type="plain"
                                                    :hide-icon="true"
                                                    :placement="bottom"  
                                                    popper-class="el-popover ap-popconfirm-delete"                                                                                                      
                                                    :title= affiliate_panel_labels.custome_link_delete_confirm 
                                                    @confirm="deleteAffiliatelink(scope.row.ap_affiliate_link_id,scope.$index)"
                                                    width="300"> 
                                                    <template #reference>  
                                                        <el-button type="primary" :disabled="(affiliate_delete_link_loader === scope.$index)?true:false" :class="affiliate_delete_link_loader === scope.$index ? 'ap-btn--is-loader' : ''" class="ap-btn--primary ap-delete-account-btn">
                                                            <span class="ap-btn__label">{{affiliate_panel_labels.delete_custome_link_label}}</span>
                                                            <div class="ap-btn--loader__circles">
                                                                <div></div>
                                                                <div></div>
                                                                <div></div>
                                                            </div>
                                                        </el-button>    
                                                    </template>                                         
                                            </el-popconfirm>
                                            </template> 
                                        </el-table-column>
                                    </el-table>
                                    <el-table class="ap-mobile-view-table" cell-class-name="ap-single-row-table" v-if="current_screen_size == 'mobile'" :data="affiliate_custom_links">                                                                                
                                        <el-table-column prop="ap_affiliate_link_id">
                                            <template #default="scope">
                                                <div class="ap-expand-top-row">
                                                    <div class="ap-expand-top-row-data ap-mb-10">
                                                        <div class="ap-expan-top-data">                                                            
                                                            <span class="ap-mr-3" :aria-label="affiliate_panel_labels.link_serial_number" > {{affiliate_panel_labels.link_serial_number}} :</span><span :aria-label="scope.row.sr_no">{{scope.row.sr_no}}</span>
                                                        </div>                                                                                                                
                                                    </div>                                                    
                                                    <div class="ap-expand-top-row-data ap-mb-10">
                                                        <div class="ap-expan-top-data">
                                                            <span class="ap-expand-top-price" :aria-label="scope.row.ap_affiliates_campaign_name">{{scope.row.ap_affiliates_campaign_name}}</span>                                                            
                                                        </div>                                                                                                                
                                                    </div>
                                                    <div class="ap-expand-top-row-data ap-mb-10">
                                                        <div class="ap-tax-link-copier">
                                                            <span class="ap-copy-data" :aria-label="scope.row.ap_page_link">{{scope.row.ap_page_link}}</span>
                                                            <el-button @click="affiliatepress_copy_data(scope.row.ap_page_link)" type="primary" class="ap-btn--primary ap-copy-txt-btn"><span class="ap-btn__label" v-html="affiliate_panel_labels.link_click_to_copy"></span></el-button>
                                                        </div>
                                                    </div>    
                                                    <div class="ap-expand-top-row-data ap-mb-10">
                                                        <div class="ap-expan-top-data">
                                                            <el-popconfirm 
                                                                :confirm-button-text=affiliate_panel_labels.yes_label
                                                                :cancel-button-text=affiliate_panel_labels.no_label
                                                                confirm-button-type="danger"
                                                                cancel-button-type="plain"
                                                                :hide-icon="true"
                                                                :placement="bottom"  
                                                                popper-class="el-popover ap-popconfirm-delete"                                                                                                      
                                                                :title= affiliate_panel_labels.custome_link_delete_confirm 
                                                                @confirm="deleteAffiliatelink(scope.row.ap_affiliate_link_id,scope.$index)"
                                                                width="300"> 
                                                                <template #reference>  
                                                                    <el-button type="primary" class="ap-btn--primary ap-delete-account-btn" tabindex="0">  
                                                                        <span class="ap-btn-lbl" >{{affiliate_panel_labels.delete_custome_link_label}}</span>
                                                                    </el-button> 
                                                                </template>                                         
                                                            </el-popconfirm>                                                        
                                                        </div>      
                                                    </div>                                                                                                     
                                                </div>    
                                            </template>
                                        </el-table-column>
                                    </el-table>       
                                </div>
                            </div>

                        </div>

                    </div>

                    <div v-if="affiliate_current_tab == 'creative'" class="ap-affiliate-panel-content ap-panel-form-content">

                        <div v-if="is_display_tab_content_loader == '1'" class="ap-panel-loader">
                            <div class="ap-front-loader-container ap-panel-front-loader">
                                <div class="ap-front-loader"></div>
                            </div>                           
                        </div>
                        <div :class="(affiliatepress_footer_dynamic_class != '')?'ap-front-content-with-scroll':''" v-if="is_display_tab_content_loader == '0'" class="ap-panel-detail">

                            <div class="ap-affiliate-panel-content-header ap-affiliat-panel-pading">
                                <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.creative_title" v-html="affiliate_panel_labels.creative_title"></div>  
                                <div class="ap-header-right ap-flex-center ap-header-filter-right">
                                    <el-button @click="open_creative_filter_modal = true" class="ap-filter-icon" aria-label="<?php esc_attr_e('Filter', 'affiliatepress-affiliate-marketing'); ?>">
                                        <?php do_action('affiliatepress_common_affiliate_panel_svg_code','filter_icon'); ?>
                                    </el-button>
                                </div>                                                             
                            </div>
                            <div class="ap-table-filter ap-affiliat-panel-pading">
                                <el-row type="flex" :gutter="24">
                                    <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="17" :xl="17">   
                                        <el-row type="flex" :gutter="16"> 
                                            <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="17" :xl="17">   
                                                <div>  
                                                    <el-input class="ap-form-control" v-model="creative_search.ap_creative_name"  size="large" :placeholder="affiliate_panel_labels.creative_enter_creative_name"></el-input>
                                                </div>       
                                            </el-col>
                                            <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="7" :xl="7">   
                                                <div>    
                                                    <el-select class="ap-form-control" size="large" v-model="creative_search.creative_type" :placeholder="affiliate_panel_labels.creative_select_type" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">                            
                                                        <el-option :label="affiliate_panel_labels.creative_image" value="image"></el-option>
                                                        <el-option :label="affiliate_panel_labels.creative_text_link" value="text_link"></el-option>                           
                                                    </el-select>
                                                </div>               
                                            </el-col>
                                        </el-row>
                                    </el-col> 
                                    <el-col class="ap-front-filter-btn" :xs="24" :sm="24" :md="24" :lg="7" :xl="7">
                                        <div class="ap-tf-btn-group">
                                            <el-button @click="applyCreativeFilter" class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.apply" v-html="affiliate_panel_labels.apply"></span>
                                            </el-button>
                                            <el-button @click="resetcreative" class="ap-btn--second" v-if="creative_search.ap_creative_name != '' || creative_search.creative_type != ''">
                                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.reset" v-html="affiliate_panel_labels.reset"></span>
                                            </el-button>
                                        </div>
                                    </el-col>
                                </el-row>
                            </div>  
                            <div class="ap-creative-list ap-panel-data-container ap-affiliat-panel-pading">
                                <div class="ap-front-content-data">
                                    <div v-if="affiliate_creative_loader == '1'" class="ap-panel-loader ap-panel-inner-loader">
                                        <div class="ap-front-loader-container ap-panel-front-loader">
                                            <div class="ap-front-loader"></div>
                                        </div>
                                    </div>  
                                    <div id="ap-loader-div" v-if="creative_items.length == 0 && affiliate_creative_loader == '0'">
                                        <el-row type="flex">
                                            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                <div class="ap-data-empty-view">
                                                    <div class="ap-ev-left-vector">
                                                        <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                                        <div class="no-data-found-text" :aria-label="affiliate_panel_labels.no_data" v-html="affiliate_panel_labels.no_data"></div>
                                                        <div class="no-data-found-description" :aria-label="affiliate_panel_labels.no_data_description" v-html="affiliate_panel_labels.no_data_description"></div>
                                                    </div>                       
                                                </div>
                                            </el-col>
                                        </el-row>
                                    </div>                                                              
                                    <el-row :class="(affiliate_creative_loader == '1')?'ap-hidden-table':''" v-if="creative_items.length != 0" :gutter="24">
                                        <el-col v-for="creative_item in creative_items" class="ap-creative-box-col ap-creative-tab" :xs="24" :sm="12" :md="12" :lg="8" :xl="8">
                                            <div class="ap-creative-box"> 
                                                <div :class="(creative_item.ap_creative_type == 'image')?'':'ap-creative-txt-img'" class="ap-creative-image">
                                                    <el-image v-if="creative_item.ap_creative_type == 'image'" :src="creative_item.image_url">
                                                        <template #placeholder>
                                                            <div class="image-slot" aria-label="<?php esc_attr_e('Loading', 'affiliatepress-affiliate-marketing'); ?>"><?php esc_html_e('Loading', 'affiliatepress-affiliate-marketing'); ?><span class="dot">...</span></div>
                                                        </template>
                                                    </el-image>
                                                    <div v-else>
                                                        <?php do_action('affiliatepress_common_affiliate_panel_svg_code','text_link_icon'); ?>
                                                    </div>
                                                </div>
                                                <div class="ap-creative-detail">
                                                    <div class="ap-creative-name" :aria-label="creative_item.ap_creative_name">{{creative_item.ap_creative_name}}</div>
                                                    <div  v-if="creative_item.ap_creative_type == 'image'" class="ap-creative-type-label" :aria-label="affiliate_panel_labels.creative_image" v-html="affiliate_panel_labels.creative_image"></div>
                                                    <div  v-else class="ap-creative-type-label" :aria-label="affiliate_panel_labels.creative_text_link" v-html="affiliate_panel_labels.creative_text_link"></div>
                                                    <div class="ap-creative-dwld-btn">
                                                        <el-button type="primary" @click="open_creative_popup(creative_item);" class="ap-btn--primary ap-btn--full-width">
                                                            <span class="ap-btn__icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','download_icon'); ?></span>
                                                            <span class="ap-btn__label" :aria-label="affiliate_panel_labels.creative_download" v-html="affiliate_panel_labels.creative_download"></span>
                                                        </el-button>                                                 
                                                    </div>
                                                </div>                                        
                                            </div>
                                        </el-col>                                    

                                    </el-row>
                                    <el-row id="ap_fixed_pagination_id" :class="affiliatepress_footer_dynamic_class" v-if="creative_items.length != 0 && creative_pagination_count != 0 && creative_pagination_count != 1" class="ap-pagination" type="flex"> 
                                        <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" >
                                            <div class="ap-pagination-left">
                                                <p><span :aria-label="creative_pagination_label" v-html="creative_pagination_label"></span> </p>
                                            </div>
                                        </el-col>
                                        <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" class="ap-pagination-nav">
                                            <el-pagination :pager-count="(current_screen_size == 'mobile')?3:7" @current-change="handlecreativePage" v-model:current-page="creative_currentPage" background layout="prev, pager, next" :total="creative_totalItems" :page-size="creative_perpage"></el-pagination>
                                        </el-col>                                               
                                    </el-row> 
                                </div>                                  
                            </div>                        
                        </div>
                        <el-dialog modal-class="ap-affiliate-dialog ap-affiliate-creative-dialog ap-mobile-full-screen-dialog" v-model="open_creative_modal" title="" width="767">
                            <div v-if="creative_popup_data != '' && creative_popup_data.length != 0" class="ap-affiliate-dialog">
                                <div @click="open_creative_modal = false" class="ap-dialog-close" tabindex="0" @keydown.enter.prevent="open_creative_modal = false">
                                    <?php do_action('affiliatepress_common_affiliate_panel_svg_code','close_dialog'); ?>
                                </div>                                
                                <div class="ap-affiliate-dialog-header ap-flex-col">
                                    <div class="ap-tab-heading" v-html="creative_popup_data.ap_creative_name" :aria-label="creative_popup_data.ap_creative_name"></div>
                                    <div class="ap-creative-head-info">
                                        <div class="ap-flex-between">
                                            <div class="ap-img-info">                                                
                                                <div class="ap-flex-center" :aria-label="creative_popup_data.ap_creative_id"><span>ID: #{{creative_popup_data.ap_creative_id}}</span> <span class="ap-dot-between"></span><span v-if="(creative_popup_data.ap_creative_type == 'image')" :aria-label = "affiliate_panel_labels.creative_image">{{affiliate_panel_labels.creative_image}}</span><span v-else :aria-label = "affiliate_panel_labels.creative_text_link">{{affiliate_panel_labels.creative_text_link}}</span><span class="ap-dot-between"></span> <span :aria-label="creative_popup_data.ap_creative_created_at_formated">{{creative_popup_data.ap_creative_created_at_formated}}</span> </div>
                                            </div>                                            
                                        </div>                                        
                                    </div>
                                </div>
                                <div class="ap-affiliate-dialog-body">
                                    <div class="ap-affiliate-dialog-subtitle ap-affiliate-creative-detail" v-html="creative_popup_data.ap_creative_description" :aria-label="creative_popup_data.ap_creative_description"></div>                             
                                    <div class="ap-affiliate-creative-preview">
                                        <div class="ap-affiliate-small-title" :aria-label="affiliate_panel_labels.creative_preview" v-html="affiliate_panel_labels.creative_preview"></div>
                                        <div class="ap-affiliate-creative-img-preview">
                                            <el-image v-if="creative_popup_data.ap_creative_type == 'image'" :src="creative_popup_data.image_url">
                                                <template #placeholder>
                                                    <div class="image-slot" aria-label="<?php esc_attr_e('Loading', 'affiliatepress-affiliate-marketing'); ?>"><?php esc_html_e('Loading', 'affiliatepress-affiliate-marketing'); ?><span class="dot">...</span></div>
                                                </template>
                                            </el-image>
                                            <div class="ap-text-preview" v-else>
                                                <a href="javascript:void(0);" v-html="creative_popup_data.ap_creative_text" :aria-label="creative_popup_data.ap_creative_text"></a>
                                            </div>
                                        </div>
                                        <div v-if="creative_popup_data.ap_creative_type == 'image'" class="ap-flex-between">
                                            <div v-if="creative_popup_data.image_data != ''" class="ap-img-info">
                                                <div :aria-label="creative_popup_data.image_data.type">{{creative_popup_data.image_data.type}}</div>
                                                <div class="ap-flex-center"><span>{{creative_popup_data.image_data.width}} x {{creative_popup_data.image_data.height}}</span> <span class="ap-dot-between"></span> <span :aria-label="creative_popup_data.image_data.fileSize">{{creative_popup_data.image_data.fileSize}}</span></div>
                                            </div>
                                            <div class="ap-preview-dwld-btn">
                                                <el-button @click="download_preview_image(creative_popup_data.image_url)" type="primary" plain class="ap-btn--primary ap-icon-plain-btn ap-remove-m-b-title">
                                                    <span class="ap-btn__icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','download_icon'); ?></span>
                                                    <span class="ap-btn__label" :aria-label="affiliate_panel_labels.creative_download" v-html="affiliate_panel_labels.creative_download"></span>
                                                </el-button>                                                 
                                            </div>
                                        </div>                                        
                                    </div>
                                    <div class="ap-affiliate-creative-source-code">
                                        <div class="ap-affiliate-small-title" v-html="affiliate_panel_labels.creative_html_code" :aria-label="affiliate_panel_labels.creative_html_code"></div>
                                            <div class="ap-affiliate-html-code">
                                                <pre v-html="creative_popup_data.ap_creative_code_preview" class="ap-creative-code-block" :aria-label="creative_popup_data.ap_creative_code_preview.type"></pre>
                                            </div>
                                            <div class="ap-preview-dwld-btn">
                                                <el-button @click="affiliatepress_copy_data(creative_popup_data.ap_creative_code)" type="primary" plain class="ap-btn--primary ap-icon-plain-btn ap-remove-m-b-title">
                                                    <span class="ap-btn__icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','copy_icon'); ?></span>
                                                    <span class="ap-btn__label" v-html="affiliate_panel_labels.creative_copy_code" :aria-label="affiliate_panel_labels.creative_copy_code"></span>
                                                </el-button>                                                 
                                            </div>                                             
                                    </div>
                                </div>
                            </div>
                        </el-dialog> 
                        <el-dialog modal-class="ap-affiliate-dialog ap-affiliate-filter-dialog ap-affiliate-creative-filter-dialog ap-mobile-full-screen-dialog" v-model="open_creative_filter_modal" title="" width="767">
                            <div class="ap-affiliate-dialog">                               
                                <div class="ap-affiliate-dialog-header">
                                    <div class="ap-tab-heading" :aria-label="affiliate_panel_labels.filters" v-html="affiliate_panel_labels.filters"></div>
                                    <div @click="open_creative_filter_modal = false" class="ap-dialog-close1 ap-flex-center">
                                        <?php do_action('affiliatepress_common_affiliate_panel_svg_code','close_dialog'); ?>
                                    </div>                                     
                                </div>
                                <div class="ap-affiliate-dialog-body">
                                    <div class="ap-single-field__form">  
                                        <span class="ap-form-label" :aria-label="affiliate_panel_labels.creative_name" v-html="affiliate_panel_labels.creative_name"></span>
                                        <el-input class="ap-form-control" v-model="creative_search.ap_creative_name"  size="large" :placeholder="affiliate_panel_labels.creative_enter_creative_name"></el-input>
                                    </div>
                                    <div class="ap-single-field__form">  
                                        <span class="ap-form-label" :aria-label="affiliate_panel_labels.creative_type" v-html="affiliate_panel_labels.creative_type"></span>
                                        <el-select class="ap-form-control" size="large" v-model="creative_search.creative_type" :placeholder="affiliate_panel_labels.creative_select_type" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">                            
                                            <el-option :label="affiliate_panel_labels.creative_image" value="image"></el-option>
                                            <el-option :label="affiliate_panel_labels.creative_text_link" value="text_link"></el-option>                           
                                        </el-select>                                        
                                    </div>                                                                        
                                </div>
                            </div>
                            <div class="ap-tf-btn-group ap-filter-popup-btn-group">
                                <el-button @click="applypopupCreativeFilter" class="ap-btn--primary ap-btn--full-width" plain type="primary" :disabled="is_apply_disabled">
                                    <span class="ap-btn__label"  :aria-label="affiliate_panel_labels.apply" v-html="affiliate_panel_labels.apply"></span>
                                </el-button>
                                <el-button @click="resetcreative" class="ap-btn--second ap-btn--full-width">
                                    <span class="ap-btn__label"  :aria-label="affiliate_panel_labels.reset" v-html="affiliate_panel_labels.reset"></span>
                                </el-button>
                            </div>
                        </el-dialog>

                    </div>

                    <div v-if="affiliate_current_tab == 'edit_profile'" class="ap-affiliate-panel-content">

                        <div v-if="is_display_tab_content_loader == '1'" class="ap-panel-loader">
                            <div class="ap-front-loader-container ap-panel-front-loader">
                                <div class="ap-front-loader"></div>
                            </div>
                        </div>
                        <div v-if="is_display_tab_content_loader == '0'" class="ap-panel-detail">                    
                            <div class="ap-affiliate-panel-content-header ap-affiliat-panel-pading">
                                <div class="ap-tab-heading ap-edit-profile-title" :aria-label="affiliate_panel_labels.edit_details" v-html="affiliate_panel_labels.edit_details"></div>                              
                            </div>
                            <div class="ap-affiliat-panel-pading">
                                <div class="ap-profile-edit-wrapper">
                                    <div class="ap-profile-sub-heading" :aria-label="affiliate_panel_labels.profile_details" v-html="affiliate_panel_labels.profile_details"></div>    
                                    <div class="ap-profile-avtar-wrapper">
                                        {{affiliate_panel_labels.profile_picture}}
                                        <div class="ap-edit-profile-avatar-container">
                                            <img class="ap-edit-profile-avatar-img" alt="User Avatar" :src="userAvatar" /><?php // phpcs:ignore ?>
                                            <div class="ap-tf-btn-group">
                                                <el-upload class="ap-edit-profile-avatar" ref="avatarRef" multiple="false" limit="1" action="<?php echo wp_nonce_url(admin_url('admin-ajax.php') . '?action=affiliatepress_upload_edit_profile_image', 'affiliatepress_upload_edit_profile_image'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - esc_html is already used by wp_nonce_url function and it's false positive ?>" 
                                                    :file-list="image_list"
                                                    :on-success="affiliatepress_upload_edit_profile_image_func"
                                                    :on-exceed="affiliatepress_image_upload_limit"
                                                    :on-remove="affiliatepress_remove_affiliate_avatar"
                                                    :on-preview="affiliatepress_edit_profile_preview"
                                                    :before-upload="checkUploadedFile">
                                                    <label>
                                                        <span class="ap-btn--primary is-plain">{{affiliate_panel_labels.change_button}}</span>                              
                                                    </label> 
                                                </el-upload>            
                                                <el-button v-if="default_userAvatar_show == 'false'" @click="affiliatepress_remove_affiliate_avatar" class="ap-btn--second ap-btn--full-width">
                                                    <span class="ap-btn__label" :aria-label="affiliate_panel_labels.remove_button" v-html="affiliate_panel_labels.remove_button"></span>
                                                </el-button>
                                            </div>
                                        </div>
                                    </div>       
                                    <div class="ap-edit-profile-frm">                               
                                        <el-form @submit.native.prevent ref="affiliates_profile_form_data" :rules="rules" require-asterisk-position="right" :model="affiliates_profile_fields" label-position="top">
                                        <div v-for="affiliate_field in affiliate_fields">

                                            <div v-if="affiliate_field.ap_form_field_type == 'Text' && affiliate_field.ap_form_field_name != 'ap_affiliates_payment_email' && affiliate_field.ap_field_is_default == '1'" class="ap-single-field__form">                    
                                                <el-form-item :prop="affiliate_field.ap_form_field_name">
                                                    <template #label>
                                                        <span class="ap-form-label" v-html="affiliate_field.ap_field_label" :aria-label="affiliate_field.ap_field_label"></span>
                                                    </template>
                                                    <el-input :readonly="(affiliate_field.ap_form_field_name == 'username')?true:false" class="ap-form-control" type="text" size="large" v-model="affiliates_profile_fields[affiliate_field.ap_form_field_name]" :placeholder="affiliate_field.ap_field_placeholder" />
                                                </el-form-item>                     
                                            </div>
                                            <div v-if="affiliate_field.ap_form_field_type == 'Email'" class="ap-single-field__form">                    
                                                <el-form-item :prop="affiliate_field.ap_form_field_name">
                                                    <template #label>
                                                        <span class="ap-form-label" v-html="affiliate_field.ap_field_label" :aria-label="affiliate_field.ap_field_label"></span>
                                                    </template>
                                                    <el-input :readonly="(affiliate_field.ap_form_field_name == 'email')?true:false" class="ap-form-control" type="text" size="large" v-model="affiliates_profile_fields[affiliate_field.ap_form_field_name]" :placeholder="affiliate_field.ap_field_placeholder" />
                                                </el-form-item>                     
                                            </div>                                                                     
                                            <div v-if="affiliate_field.ap_form_field_type == 'Textarea' && affiliate_field.ap_field_is_default == '1'" class="ap-single-field__form">                    
                                                <el-form-item :prop="affiliate_field.ap_form_field_name">
                                                    <template #label>
                                                        <span class="ap-form-label" v-html="affiliate_field.ap_field_label" :aria-label="affiliate_field.ap_field_label"></span>
                                                    </template>
                                                    <el-input class="ap-form-control" type="textarea" :rows="4" size="large" v-model="affiliates_profile_fields[affiliate_field.ap_form_field_name]" :placeholder="affiliate_field.ap_field_placeholder" />
                                                </el-form-item>                     
                                            </div>
                                        </div>
                                        <div v-if="affiliate_fields.length != 0 && affiliatepress_paymnet_email_show_panel != 0" class="ap-other-prof-setting">
                                            <div class="ap-profile-sub-heading" :aria-label="affiliate_panel_labels.paymnet_detail" v-html="affiliate_panel_labels.paymnet_detail"></div>    
                                            <div  class="ap-single-field__form">                    
                                                <el-form-item prop="ap_affiliates_payment_email">
                                                    <template #label>
                                                        <span class="ap-form-label" v-html="affiliate_fields_payout_label" :aria-label="affiliate_fields_payout_label"></span>
                                                    </template>
                                                    <el-input  class="ap-form-control" :placeholder="affiliate_fields_payout_placeholder" type="text" size="large" v-model="affiliates_profile_fields['ap_affiliates_payment_email']" />
                                                </el-form-item>                     
                                            </div>
                                        </div>
                                        <div class="ap-edit-profile-save">
                                            <el-button native-type="submit" @click="save_edit_profile_data" :disabled="(affiliate_edit_profile_loader == '1')?true:false" :class="(affiliate_edit_profile_loader == '1') ? 'ap-btn--is-loader' : ''" class="ap-btn--primary ap-btn--big"  type="primary">
                                                <span class="ap-btn__label" :aria-label="affiliate_panel_labels.save_changes" v-html="affiliate_panel_labels.save_changes"></span>
                                                <div class="ap-btn--loader__circles">
                                                    <div></div>
                                                    <div></div>
                                                    <div></div>
                                                </div>                    
                                            </el-button>                 
                                        </div>                                 
                                        </el-form>
                                    </div>
                                </div>
                                <div class="ap-profile-edit-wrapper">
                                    <div class="ap-profile-sub-heading" :aria-label="affiliate_panel_labels.chnage_password" v-html="affiliate_panel_labels.chnage_password"></div>   
                                    <div class="ap-change-password-frm ap-edit-profile-frm">
                                        <el-form @submit.native.prevent ref="affiliates_change_pass_form_data" :rules="affiliate_change_password_rules" require-asterisk-position="right" :model="affiliate_change_password" label-position="top">
                                            <el-row type="flex" :gutter="24">
                                                <el-col :xs="24" :sm="24" :md="8" :lg="8" :xl="12" class="ap-fields-pw">
                                                    <div class="ap-single-field__form">  
                                                        <el-form-item prop="old_password">
                                                            <template #label>
                                                                <span class="ap-form-label" :aria-label="affiliate_panel_labels.current_password" v-html="affiliate_panel_labels.current_password"></span>
                                                            </template>
                                                            <el-input class="ap-form-control" type="password" :show-password="true" v-model="affiliate_change_password.old_password" size="large" :placeholder="affiliate_panel_labels.current_password" />
                                                        </el-form-item> 
                                                    </div>
                                                </el-col>
                                            </el-row>
                                            <el-row type="flex" :gutter="24">
                                                <el-col :xs="24" :sm="24" :md="8" :lg="8" :xl="12">
                                                    <div class="ap-single-field__form">  
                                                        <el-form-item prop="new_password">
                                                            <template #label>
                                                                <span class="ap-form-label" :aria-label="affiliate_panel_labels.new_password" v-html="affiliate_panel_labels.new_password"></span>
                                                            </template>
                                                            <el-input class="ap-form-control" type="password" :show-password="true" v-model="affiliate_change_password.new_password" size="large" :placeholder="affiliate_panel_labels.new_password" />
                                                        </el-form-item>
                                                    </div>
                                                </el-col>
                                                <el-col :xs="24" :sm="24" :md="8" :lg="8" :xl="12">
                                                    <div class="ap-single-field__form">  
                                                        <el-form-item prop="confirm_password">
                                                            <template #label>
                                                                <span class="ap-form-label" :aria-label="affiliate_panel_labels.confirm_new_password" v-html="affiliate_panel_labels.confirm_new_password"></span>
                                                            </template>
                                                            <el-input class="ap-form-control" type="password" :show-password="true" v-model="affiliate_change_password.confirm_password" size="large" :placeholder="affiliate_panel_labels.confirm_new_password" />
                                                        </el-form-item>  
                                                    </div>  
                                                </el-col>
                                            </el-row>
                                            <div class="ap-edit-profile-save">
                                                <el-button native-type="submit" @click="change_password_request" :disabled="(affiliate_change_password_loader == '1')?true:false" :class="(affiliate_change_password_loader == '1') ? 'ap-btn--is-loader' : ''" class="ap-btn--primary ap-btn--big"  type="primary">
                                                    <span class="ap-btn__label" :aria-label="affiliate_panel_labels.save_password" v-html="affiliate_panel_labels.save_password"></span>
                                                    <div class="ap-btn--loader__circles">
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>                    
                                                </el-button>                 
                                            </div>                                                                                                                             
                                        </el-form>
                                    </div>
                                </div>
                                <div class="ap-profile-edit-wrapper" v-if="affiliate_user_self_closed_account == 'true'">
                                    <el-row class="ap-delete-account-row" type="flex" justify="space-between" align="middle">
                                        <div class="ap-delete-account-left">
                                            <div class="ap-profile-sub-heading" :aria-label="affiliate_panel_labels.delete_account">
                                                {{affiliate_panel_labels.delete_account}}
                                            </div>
                                            <div class="ap-delete-account-text">
                                               {{affiliate_panel_labels.delete_account_description}}
                                            </div>
                                        </div>
                                        <div class="ap-delete-account-right">
                                            <el-button native-type="submit" @click="open_close_account_modal = true" class="ap-btn--primary ap-btn--big ap-delete-account-btn" type="primary">
                                                <span class="ap-btn__label"
                                                :aria-label="affiliate_panel_labels.delete_account" v-html="affiliate_panel_labels.delete_account"></span>
                                                <div class="ap-btn--loader__circles">
                                                    <div></div>
                                                    <div></div>
                                                    <div></div>
                                                </div>
                                            </el-button>
                                        </div>
                                    </el-row>
                                </div>
                            </div>
                        </div>

                    </div>    


                </div>

            </div>
        </div>
    </div>    
</div>