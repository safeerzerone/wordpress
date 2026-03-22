<?php
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $AffiliatePress;
?>
<el-main class="ap-main-listing-card-container ap-default-card ap--is-page-non-scrollable-mob" id="ap-all-page-main-container">
    <el-row :gutter="12" type="flex" class="ap-head-wrap">
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-left">
            <h1 class="ap-page-heading"><?php esc_html_e('Affiliate Form Editor', 'affiliatepress-affiliate-marketing'); ?></h1>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-right">
            <div class="ap-hw-right-btn-group">              
                <el-button type="primary" :class="(is_disabled) ? 'ap-btn--is-loader' : ''" @click="ap_save_field_settings_data()" :disabled="is_disabled" class="ap-btn--primary ap-btn--big">                    
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
    <div v-if="ap_first_page_loaded == '1'" class="ap-back-loader-container" id="ap-page-loading-loader">
        <div class="ap-back-loader"></div>
    </div>    
    <div v-if="ap_first_page_loaded == '0'" id="ap-main-container">       
        <el-container class="ap-customize-main-container">
            <div class="ap-back-loader-container" v-if="is_display_loader == '1'">
                <div class="ap-back-loader"></div>
            </div>
            <div class="ap-customize-body-wrapper">
                <el-row type="flex" :gutter="32">
                    <el-col :xs="5" :sm="7" :md="7" :lg="5" :xl="5">
                        <div class="ap-customize-step-side-panel ap-side-panel-disabled">
                            <div class="ap-cs__items" id="ap-input-fields">
                                <div class="ap-restricted">
                                    <h5 class="ap-form-item-field-heading"><?php esc_html_e( 'Form Elements', 'affiliatepress-affiliate-marketing' ); ?>
                                    <div @click="open_premium_modal" class="ap-premium-text" v-if="is_pro_active != '1'"><?php echo $AffiliatePress->affiliatepress_get_premium_content(); //phpcs:ignore ?></div>
                                    </h5>
                                </div>
                                <div data-type="single_line" class="ap-cs__item">
                                    <span class="material-icons-round">short_text</span>
                                    <p><?php esc_html_e( 'Text Field', 'affiliatepress-affiliate-marketing' ); ?></p>
                                </div>
                                <div data-type="textarea" class="ap-cs__item">
                                    <span class="material-icons-round">notes</span>
                                    <p><?php esc_html_e( 'Textarea', 'affiliatepress-affiliate-marketing' ); ?></p>
                                </div>
                                <div data-type="checkbox" class="ap-cs__item">
                                    <span class="material-icons-round">check_box</span>
                                    <p><?php esc_html_e( 'Checkbox', 'affiliatepress-affiliate-marketing' ); ?></p>
                                </div>
                                <div data-type="radio" class="ap-cs__item">
                                    <span class="material-icons-round">radio_button_checked</span>
                                    <p><?php esc_html_e( 'Radio', 'affiliatepress-affiliate-marketing' ); ?></p>
                                </div>
                                <div data-type="dropdown" class="ap-cs__item">
                                    <span class="material-icons-round">arrow_drop_down_circle</span>
                                    <p><?php esc_html_e( 'Dropdown', 'affiliatepress-affiliate-marketing' ); ?></p>
                                </div>
                                <div data-type="datepicker" class="ap-cs__item">
                                    <span class="material-icons-round">insert_invitation</span>
                                    <p><?php esc_html_e( 'DatePicker', 'affiliatepress-affiliate-marketing' ); ?></p>
                                </div>
                                <div data-type="file_upload" class="ap-cs__item">
                                    <span class="material-icons-round">upload</span>
                                    <p><?php esc_html_e( 'File Upload', 'affiliatepress-affiliate-marketing' ); ?></p>
                                </div>             
                            </div>
                        </div>
                    </el-col>
                    <el-col :xs="12" :sm="10" :md="10" :lg="13" :xl="13">
                        <div class="ap-customize-field-settings-body-container">
                            <el-row>
                                <draggable v-model="field_settings_fields" item-key="id" class="list-group" ghost-class="ghost" @start="dragging = true" @end="endDragposistion" :move="updateFieldPos">   
                                    <template #item="{element, index}">                                          
                                        <el-col class="item" :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                <div class="ap-cfs-item-card list-group-item">
                                                    <div class="ap-cfs-ic--head">
                                                        <div class="ap-cfs-ic--head__type-label">
                                                            <div class="ap-cfs-ic--head-drag-icon">
                                                                <?php do_action('affiliatepress_common_svg_code','field_drag_icon'); ?>
                                                            </div>
                                                            <div class="ap-cfs-ic--head__type-label-heading" v-if="element.field_type == 'terms_and_conditions'"><?php esc_html_e('Terms and conditions', 'affiliatepress-affiliate-marketing'); ?></div>
                                                            <div class="ap-cfs-ic--head__type-label-heading" v-else>{{ element.field_type }}</div>
                                                        </div>
                                                        <div class="ap-cfs-ic--head__field-controls">
                                                            <div class="ap-cfs-ic--head__fc-swtich">
                                                            <el-switch v-model="element.is_required" size="small" :disabled="element.is_edit == '0' || element.field_name == 'terms_and_conditions' ? true :false || element.field_name == 'ap_affiliates_payment_email' ? true :false"></el-switch> 
                                                                <label class="ap-csf-field-required"><?php esc_html_e('Required', 'affiliatepress-affiliate-marketing'); ?></label>
                                                            </div>
                                                            <div class="ap-cfs-ic--head__fc-actions">
                                                                <el-popover trigger="click" width="350" popper-class="ap-field-settings-edit-popover" placement="bottom-end" v-model="element.show_setting"  ref="fields_settings_popover">
                                                                    <el-container class="ap-field-settings-edit-container">
                                                                        <div class="ap-combine-field ap-fs-item-settings-form-control-item">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Label', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-input class="ap-form-control" size="large" v-model="element.label"></el-input>
                                                                        </div>
                                                                        <div class="ap-combine-field ap-fs-item-settings-form-control-item" v-show="element.field_name != 'phone_number' && element.field_name != 'terms_and_conditions' ">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Placeholder', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-input class="ap-form-control" size="large" v-model="element.placeholder"></el-input>
                                                                        </div>
                                                                        <div class="ap-combine-field ap-fs-item-settings-form-control-item">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Error message', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-input class="ap-form-control" size="large" v-model="element.error_message"></el-input>
                                                                        </div>
                                                                        <div class="ap-combine-field ap-fs-item-settings-form-control-item">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('CSS Class', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-input class="ap-form-control" size="large" v-model="element.field_class"></el-input>
                                                                        </div>
                                                                        <div v-if="element.is_edit != '0'" class="ap-fs-item-settings-form-control-item ap-combine-field">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Show in Affiliate registration', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-switch v-model="element.show_signup_field"></el-switch>
                                                                        </div>
                                                                        <div v-if="element.field_name != 'terms_and_conditions' && element.is_edit != '0'" class="ap-fs-item-settings-form-control-item ap-combine-field">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Show in Affiliate profile', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-switch  v-model="element.show_profile_field" ></el-switch>
                                                                        </div> 
                                                                        <div class="ap-fs-item-settings-form-control-item ap-combine-field" v-if="element.field_name == 'password'">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Enable Confirm Password', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-switch v-model="confirm_password_field.enable_confirm_password"></el-switch>
                                                                        </div>       
                                                                        <div class="ap-combine-field ap-fs-item-settings-form-control-item" v-if="element.field_name == 'password' && confirm_password_field.enable_confirm_password == true">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Confirm password Label', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-input class="ap-form-control" size="large" v-model="confirm_password_field.confirm_password_label"></el-input>
                                                                        </div>
                                                                        <div class="ap-combine-field ap-fs-item-settings-form-control-item" v-if="element.field_name == 'password' && confirm_password_field.enable_confirm_password == true">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Confirm password Placeholder', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-input class="ap-form-control" size="large" v-model="confirm_password_field.confirm_password_placeholder"></el-input>
                                                                        </div>
                                                                        <div class="ap-combine-field ap-fs-item-settings-form-control-item" v-if="element.field_name == 'password' && confirm_password_field.enable_confirm_password == true">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Confirm password Error message', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-input class="ap-form-control" size="large" v-model="confirm_password_field.confirm_password_error_msg"></el-input>
                                                                        </div>            
                                                                        <div class="ap-combine-field ap-fs-item-settings-form-control-item" v-if="element.field_name == 'password' && confirm_password_field.enable_confirm_password == true">
                                                                            <label>
                                                                                <span class="ap-form-label"><?php esc_html_e('Validation Error message', 'affiliatepress-affiliate-marketing'); ?></span>
                                                                            </label>
                                                                            <el-input class="ap-form-control" size="large" v-model="confirm_password_field.confirm_password_validation_msg"></el-input>
                                                                        </div>                                                  
                                                                        <div class="ap-customize--edit-label-popover--actions">
                                                                            <el-button type="primary" class="ap-btn ap-btn__small ap-btn--primary" @click="closeFieldSettings(element.field_name)"><?php esc_html_e('Save', 'affiliatepress-affiliate-marketing'); ?></el-button>
                                                                        </div>
                                                                    </el-container>
                                                                    <template #reference>
                                                                        <span  class="ap-btn ap-field-left-mar ap-btn--icon-without-box" @click="element.show_setting = true">
                                                                            <el-tooltip effect="dark" content="<?php esc_html_e( 'Field Settings', 'affiliatepress-affiliate-marketing'); ?>" placement="top" open-delay="300">
                                                                                <span class="ap-cfs-settings">
                                                                                    <?php do_action('affiliatepress_common_svg_code','field_setting_icon'); ?>
                                                                                </span>
                                                                            </el-tooltip>
                                                                        </span>
                                                                    </template>
                                                                </el-popover>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="ap-cfs-ic--body">
                                                        <div class="ap-combine-field ap-cfs-ic--body__field-preview">
                                                            <label><span class="ap-form-label" v-html="element.label" v-if="element.field_type != 'terms_and_conditions'"></span></label>
                                                            <el-input class="ap-form-control" size="large"  v-if="element.field_type != 'terms_and_conditions'" :placeholder="element.placeholder"></el-input>
                                                            <template v-if='element.field_type == "terms_and_conditions"'>
                                                                <el-checkbox :class="(element.field_type == 'terms_and_conditions')?'ap-cf-field-terms-cond-chk':''" class="ap-form-label ap-custom-checkbox--is-label ap-csf-custom-checkbox" size="large" :label="element.label"><div v-html="element.label"></div></el-checkbox>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                        </el-col>                        
                                    </template>             
                                </draggable>
                            </el-row>
                        </div>
                    </el-col>
                    <el-col :xs="6" :sm="6" :md="6" :lg="6" :xl="6">
                        <div class="ap-customize-step-side-panel ap-message-settings-col">
                            <div class="ap-cs__items" id="ap-input-fields">
                                <div class="ap-restricted">
                                    <h5 class="ap-form-item-field-heading"><?php esc_html_e( 'Form Messages', 'affiliatepress-affiliate-marketing' ); ?></h5>
                                </div>
                                <el-form :rules="rules_messages" ref="field_messages_settings_form" :model="messages_setting_form" @submit.native.prevent>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Login Error Message', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="login_error_message">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_error_message" size="large"  />                                     
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Register with auto approval', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="affiliate_register_with_auto_approved">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_register_with_auto_approved" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Register with pending status', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="affiliate_register_with_pending">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_register_with_pending" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Required field validation', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="required_field_validation">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.required_field_validation" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Username already exists', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="username_already_exists">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.username_already_exists" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Email already exists', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="email_already_exists">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.email_already_exists" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Affiliate Registration Disabled', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="affiliate_registration_disabled">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_registration_disabled" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Login is not allowed', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="login_is_not_allowed">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.login_is_not_allowed" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Wrong email/username', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="affiliate_wrong_email">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_wrong_email" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Password reset link', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="send_password_reset_link">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.send_password_reset_link" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Account closure request success', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="account_closure_request_success">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.account_closure_request_success" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Affiliate custom link added', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="affiliate_custom_link_added">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_custom_link_added" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Campaign name already added', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="campaign_name_already_added">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.campaign_name_already_added" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Incorrect current password', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="incorrect_current_password">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.incorrect_current_password" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Mismatch Passwords', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="new_and_current_password_not_match">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.new_and_current_password_not_match" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Password successfully updated', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="password_successfully_updated">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.password_successfully_updated" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Profile fields successfully update', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="profile_fields_successfully_updated">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.profile_fields_successfully_updated" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Affiliate Pending Account Login', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="affiliate_pending_register_message">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_pending_register_message" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Affiliate Account Already Registered', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="affiliate_already_registered_message">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_already_registered_message" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Affiliate Account Blocked', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="affiliate_user_block_message">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_user_block_message" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Affiliate Link Limit Reached', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="link_limit_reached_error">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_limit_reached_error" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Affiliate Link Deleted', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="affiliate_link_delete">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.affiliate_link_delete" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Affiliate Link Copied', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="link_copied">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.link_copied" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Avatar Invalid File Type Message', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="file_upload_type_validation">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.file_upload_type_validation" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Avatar File Size Limit Message', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="file_upload_limit_validation">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.file_upload_limit_validation" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div class="ap-message-settings-wrapper">
                                        <div class="ap-message-label"><?php esc_html_e( 'Not Allow Affiliate Registration', 'affiliatepress-affiliate-marketing' ); ?></div>
                                        <div class="ap-message-text"> 
                                            <el-form-item prop="not_allow_affiliate_register">
                                                <el-input class="ap-form-control" type="text" v-model="messages_setting_form.not_allow_affiliate_register" size="large"  />                                                                             
                                            </el-form-item>
                                        </div>
                                    </div>
                                </el-form>  
                            </div>
                        </div>
                    </el-col>
                </el-row>
            </div>
        </el-container>
</div>
</el-main>
<?php
    $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_footer.php';
    $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_footer_content', $affiliatepress_load_file_name,1);
    require $affiliatepress_load_file_name;
?>


