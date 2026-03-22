<?php 
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $affiliatepress_get_setting_data;
?>
<div v-cloak class="ap-front-reg-container <?php echo esc_html((is_rtl())?'ap-front-reg-container-rtl':''); ?>" id="affiliatepress_reg_form_<?php echo esc_html( $affiliatepress_uniq_id ); ?>" style="min-height:700px;">
    <div id="ap-none-field">
        <?php wp_nonce_field('ap_wp_nonce'); ?> 
    </div>
    <div ref="container" id="ap-vue-cont-id" class="ap-main-reg-card-container" style="min-height:700px;">
        <div style="display:none;" :style="(is_affiliate_form_loader == '0') ? 'display:flex;' : ''" class="ap-front-loader-container" id="ap-page-front-loading-loader">
            <div class="ap-front-loader"></div>
        </div>
        <div class="ap-main-reg-frm-body ap-single-form" style="display:none;" :style="(is_affiliate_form_loader == '1') ? 'display:block;' : ''">
            <el-form @submit.native.prevent ref="affiliates_reg_form_data" :rules="rules" require-asterisk-position="right" :model="affiliates" label-position="top">
                <div class="el-form-item__error el-form-item is-error el-input__wrapper el-input__inner el-checkbox el-checkbox--large is-checked ap-form-label ap-custom-checkbox--is-label el-checkbox__input is-checked el-checkbox__original el-checkbox__inner el-checkbox__label" style="display:none"></div> 
                <div class="ap-front-page-title" :aria-label="affiliate_panel_labels.create_an_account" v-html="affiliate_panel_labels.create_an_account"></div>
                <div class="ap-front-page-sub-title" :aria-label="affiliate_panel_labels.create_account_description" v-html="affiliate_panel_labels.create_account_description"></div>

                <div class="ap-front-toast-notification --ap-error" style="display:none;" :style="(is_display_error == '1') ? 'display:block;' : ''">
                    <div class="ap-front-tn-body">                                                
                        <p :aria-label="is_error_msg">{{ is_error_msg }}</p>                        
                    </div>
                </div>
                <div class="ap-front-toast-notification --ap-success" style="display:none;" :style="(is_display_success == '1') ? 'display:block;' : ''" >
                    <div class="ap-front-tn-body">
                        <p :aria-label="is_success_msg">{{ is_success_msg }}</p>
                    </div>
                </div>
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
                            <el-checkbox class="ap-form-label ap-custom-checkbox--is-label" @change="register_terms_and_condition(affiliate_field.ap_form_field_name)" v-model="affiliates[affiliate_field.ap_form_field_name]" size="large"><div v-html="affiliate_field.ap_field_label" ></div></el-checkbox>                            
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
                    <span :aria-label="affiliate_panel_labels.do_you_have_account" v-html="affiliate_panel_labels.do_you_have_account"></span>&nbsp;<a href="javascript:void(0);"  class="ap-acnt-link ap-title-text-color" @click="go_to_login_page()" href="javascript:void(0);"  :aria-label="affiliate_panel_labels.signin" v-html="affiliate_panel_labels.signin"></a>
                </div>
            </el-form>
        </div>
    </div>    
</div>

