<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<el-main v-cloak class="ap-front-creative-container <?php echo esc_html((is_rtl())?'ap-front-creative-container-rtl':''); ?>" id="affiliatepress_creative_form_<?php echo esc_html( $affiliatepress_uniq_id ); ?>">
    <div id="ap-none-field">
        <?php wp_nonce_field('ap_wp_nonce'); ?>
    </div>
    <div ref="container" id="ap-vue-cont-id" class="ap-main-creative-card-container">
        <div v-if="is_affiliate_creative_form_loader == '1'" class="ap-front-loader-container" id="ap-page-front-loading-loader">
            <div class="ap-front-loader"></div>
        </div>
        <div v-if="is_affiliate_creative_form_loader == '0' && typeof creative != 'undefined' && creative != ''" class="ap-main-reg-frm-body ap-single-form">
            <div class="ap-front-page-title">{{creative.ap_creative_name}}</div>
            <div class="ap-affiliate-creative-detail">
                <div class="ap-affiliate-dialog-subtitle ap-affiliate-creative-detail" v-html="creative.ap_creative_description"></div>                             
                <div class="ap-affiliate-creative-preview">
                    <div class="ap-affiliate-small-title"><?php esc_html_e('Preview', 'affiliatepress-affiliate-marketing'); ?></div>
                    <div class="ap-affiliate-creative-img-preview">
                        <el-image v-if="creative.ap_creative_type == 'image'" :src="creative.image_url">
                            <template #placeholder>
                                <div class="image-slot"><?php esc_html_e('Loading', 'affiliatepress-affiliate-marketing'); ?><span class="dot">...</span></div>
                            </template>
                        </el-image>
                        <div class="ap-text-preview" v-else>
                            <a href="javascript:void(0);" v-html="creative.ap_creative_text"></a>
                        </div>
                    </div>
                    <div v-if="creative.ap_creative_type == 'image'" class="ap-flex-between">
                            <div v-if="creative.image_data != ''" class="ap-img-info">
                                <div>{{creative.image_data.type}}</div>
                                <div class="ap-flex-center"><span>{{creative.image_data.width}} x {{creative.image_data.height}}</span> <span class="ap-dot-between"></span> <span>{{creative.image_data.fileSize}}</span></div>
                            </div>                    
                            <div v-if="creative.ap_creative_type == 'image'" class="ap-preview-dwld-btn">
                                <el-button @click="download_preview_image(creative.image_url)" type="primary" plain class="ap-btn--primary ap-icon-plain-btn ap-remove-m-b-title">
                                    <span class="ap-btn__icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','download_icon'); ?></span>
                                    <span class="ap-btn__label"><?php esc_html_e('Download', 'affiliatepress-affiliate-marketing'); ?></span>
                                </el-button>                                                 
                            </div>
                    </div>                                        
                </div>
                <div class="ap-affiliate-creative-source-code">
                    <div class="ap-affiliate-small-title"><?php esc_html_e('HTML Code', 'affiliatepress-affiliate-marketing'); ?></div>
                        <div class="ap-affiliate-html-code">
                            <pre v-html="creative.ap_creative_code_preview" class="ap-creative-code-block"></pre>
                        </div>                        
                        <div class="ap-preview-dwld-btn">
                            <el-button @click="affiliatepress_copy_data(creative.ap_creative_code)" type="primary" plain class="ap-btn--primary ap-icon-plain-btn ap-remove-m-b-title">
                                <span class="ap-btn__icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','copy_icon'); ?></span>
                                <span class="ap-btn__label"><?php esc_html_e('Copy Code', 'affiliatepress-affiliate-marketing'); ?></span>
                            </el-button>                                                 
                        </div>   
                                                                  
                </div>
            </div>            
        </div>
    </div>    
</el-main>