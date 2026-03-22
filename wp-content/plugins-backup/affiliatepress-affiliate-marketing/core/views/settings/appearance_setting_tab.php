<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<el-tab-pane class="ap-tabs--v_ls__tab-item--pane-body ap-tabl-appearance_settings" name ="appearance_settings"  data-tab_name="appearance_settings">
    <template #label>
        <span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path class="ap-setting-fill-stroke-active" d="M12 21.9969C9.34784 21.9969 6.8043 20.9437 4.92893 19.0689C3.05357 17.1941 2 14.6513 2 11.9999C2 9.34856 3.05357 6.80578 4.92893 4.93098C6.8043 3.05618 9.34784 2.00293 12 2.00293C17.5222 2.00293 22 5.98173 22 10.8891C22 12.0666 21.4733 13.1973 20.5356 14.0304C19.5978 14.8635 18.3256 15.3323 17 15.3323H14.2222C13.7265 15.3243 13.2424 15.4822 12.8468 15.781C12.4512 16.0798 12.167 16.5022 12.0392 16.9811C11.9115 17.46 11.9476 17.9678 12.1418 18.4239C12.3361 18.8799 12.6772 19.2578 13.1111 19.4977C13.333 19.7023 13.4851 19.9713 13.5462 20.2668C13.6072 20.5624 13.5741 20.8696 13.4515 21.1454C13.3289 21.4211 13.123 21.6516 12.8626 21.8043C12.6023 21.9571 12.3006 22.0244 12 21.9969Z" stroke="#4D5973" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path class="ap-setting-fill-active" d="M6.5 10C6.5 10.3978 6.65804 10.7794 6.93934 11.0607C7.22064 11.342 7.60218 11.5 8 11.5C8.39782 11.5 8.77936 11.342 9.06066 11.0607C9.34196 10.7794 9.5 10.3978 9.5 10C9.5 9.60218 9.34196 9.22064 9.06066 8.93934C8.77936 8.65804 8.39782 8.5 8 8.5C7.60218 8.5 7.22064 8.65804 6.93934 8.93934C6.65804 9.22064 6.5 9.60218 6.5 10Z" fill="#4D5973"/>
            <path class="ap-setting-fill-active" d="M10.5 7C10.5 7.39782 10.658 7.77936 10.9393 8.06066C11.2206 8.34196 11.6022 8.5 12 8.5C12.3978 8.5 12.7794 8.34196 13.0607 8.06066C13.342 7.77936 13.5 7.39782 13.5 7C13.5 6.60218 13.342 6.22064 13.0607 5.93934C12.7794 5.65804 12.3978 5.5 12 5.5C11.6022 5.5 11.2206 5.65804 10.9393 5.93934C10.658 6.22064 10.5 6.60218 10.5 7Z" fill="#4D5973"/>
            <path class="ap-setting-fill-active" d="M14.5 10C14.5 10.3978 14.658 10.7794 14.9393 11.0607C15.2206 11.342 15.6022 11.5 16 11.5C16.3978 11.5 16.7794 11.342 17.0607 11.0607C17.342 10.7794 17.5 10.3978 17.5 10C17.5 9.60218 17.342 9.22064 17.0607 8.93934C16.7794 8.65804 16.3978 8.5 16 8.5C15.6022 8.5 15.2206 8.65804 14.9393 8.93934C14.658 9.22064 14.5 9.60218 14.5 10Z" fill="#4D5973"/>
            </svg>
            <span class="ap-settings-tab-lbl"><?php esc_html_e('Appearance', 'affiliatepress-affiliate-marketing'); ?></span>
        </span>
    </template>
    <div class="ap-general-settings-tabs--pb__card">
        <div class="ap-settings-tab-content-body-wrapper">
            <div class="ap-back-loader-container" id="ap-page-loading-loader" v-if="ap_settings_content_loaded == '1'">
                <div class="ap-back-loader"></div>
            </div>  
            <div  v-else class="ap-gs--tabs-pb__content-body">
                <el-form ref="appearance_setting_form" :model="appearance_setting_form" @submit.native.prevent>
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Color Setting', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-gs_cb-item-setting-control-first">
                                        <el-form-item class="ap-color-option">                                       
                                            <el-color-picker size="large" class="ap-customize-tp__color-picker" v-model="appearance_setting_form.primary_color"></el-color-picker>
                                            <div class="ap-color-label"><?php esc_html_e('Primary', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-gs_cb-item-setting-control-first">
                                        <el-form-item class="ap-color-option">                                       
                                            <el-color-picker size="large" class="ap-customize-tp__color-picker" v-model="appearance_setting_form.background_color"></el-color-picker>
                                            <div class="ap-color-label"><?php esc_html_e('Panel Background', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-gs_cb-item-setting-control-first">
                                        <el-form-item class="ap-color-option">                                       
                                            <el-color-picker size="large" class="ap-customize-tp__color-picker" v-model="appearance_setting_form.panel_background_color"></el-color-picker>
                                            <div class="ap-color-label"><?php esc_html_e('Panel Sidebar', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-gs_cb-item-setting-control-first">
                                        <el-form-item class="ap-color-option">                                       
                                            <el-color-picker size="large" class="ap-customize-tp__color-picker" v-model="appearance_setting_form.border_color"></el-color-picker>
                                            <div class="ap-color-label"><?php esc_html_e('Border Color', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-gs_cb-item-setting-control-first">
                                        <el-form-item class="ap-color-option">                                       
                                            <el-color-picker size="large" class="ap-customize-tp__color-picker" v-model="appearance_setting_form.text_color"></el-color-picker>
                                            <div class="ap-color-label"><?php esc_html_e('Title Text Color', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </el-form-item>
                                    </div>
                                </el-col>
                                <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-gs_cb-item-setting-control-first">
                                        <el-form-item class="ap-color-option">                                       
                                            <el-color-picker size="large" class="ap-customize-tp__color-picker" v-model="appearance_setting_form.content_color"></el-color-picker>
                                            <div class="ap-color-label"><?php esc_html_e('Content Color', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </el-form-item>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>
                    <div class="ap-settings-new-section"></div>   
                    <div class="ap-gs__cb--item">
                        <div class="ap-gs__cb--item-heading ap-gs__cb--item--main-heading">
                            <?php esc_html_e('Font Setting', 'affiliatepress-affiliate-marketing'); ?>
                        </div>
                        <div class="ap-gs__cb--item-body">
                            <el-row type="flex" class="ap-gs--tabs-pb__cb-item-row" :gutter="32">
                                <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-gs__cb-item-left">
                                    <div class="ap-combine-field">
                                        <label><span class="ap-form-label"><?php esc_html_e('Select Font', 'affiliatepress-affiliate-marketing'); ?></span></label> 
                                        <el-form-item prop="font">
                                            <el-select filterable class="ap-form-control" v-model="appearance_setting_form.font" placeholder="Select" size="large">
                                                <el-option-group v-for="item_data in fonts_list" :key="item_data.label" :label="item_data.label">
                                                    <el-option v-for="item in item_data.options" :key="item" :label="item" :value="item"></el-option>
                                                </el-option-group>                                                          
                                            </el-select>                                                                                                                                 
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
                    <el-col :xs="24" :sm="24" :md="24" :lg="16" :xl="12">
                        <div class="ap-hw-right-btn-group ap-gs-tabs--pb__btn-group"> 
                            <el-popconfirm :disabled="is_display_delete_configuration_setting_btn"
                                confirm-button-text="<?php esc_html_e('Yes', 'affiliatepress-affiliate-marketing'); ?>"
                                cancel-button-text="<?php esc_html_e('No', 'affiliatepress-affiliate-marketing'); ?>"
                                confirm-button-type="danger"
                                cancel-button-type="plain"
                                :hide-icon="true"
                                placement="bottom"
                                popper-class="el-popover ap-popconfirm-delete ap-setting-confirm"                                                                                                      
                                title="<?php esc_html_e('Are you sure you want to reset the color configuration?', 'affiliatepress-affiliate-marketing'); ?>"
                                @confirm="ap_reset_appearance_color()"
                                width="280"> 
                                <template #reference>
                                    <el-button :class="(is_display_reset_color_setting_loader == '1') ? 'ap-btn--is-loader' : ''" class="ap-btn--second ap-margin-right--sec-btn" :disabled="is_display_reset_color_setting_loader == '1' ? true : false">
                                        <span class="ap-btn__label"><?php esc_html_e( 'Reset to Default', 'affiliatepress-affiliate-marketing' ); ?></span>
                                        <div class="ap-only--loader__circles" v-if="is_display_reset_color_setting_loader == '1'">
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                        </div>
                                    </el-button> 	
                                </template>                                         
                            </el-popconfirm>
                            <el-button  @click="(!is_disabled)?saveSettingsData('appearance_setting_form','appearance_settings'):''" type="primary" :class="(is_display_save_loader == '1') ? 'ap-btn--is-loader' : ''" class="ap-btn--primary ap-btn--big" :disabled="is_display_save_loader == '1' ? true : false">                 
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