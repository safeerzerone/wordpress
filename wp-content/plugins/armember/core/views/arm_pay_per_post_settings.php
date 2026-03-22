<?php
global $wpdb, $ARMember, $arm_global_settings, $arm_member_forms,$arm_pay_per_post_feature;

if(!$arm_pay_per_post_feature->isPayPerPostFeature):
     wp_redirect(admin_url('admin.php?page=arm_general_settings'));
endif;
$all_global_settings = $arm_global_settings->arm_get_all_global_settings();
$default_common_messages = $arm_global_settings->arm_default_common_messages();
$general_settings = $all_global_settings['general_settings'];
$general_settings['arm_pay_per_post_default_content'] = !empty($general_settings['arm_pay_per_post_default_content']) ? $general_settings['arm_pay_per_post_default_content'] : $default_common_messages['arm_pay_per_post_default_content'];


$arm_pay_per_post_buynow_var = (!empty($general_settings['arm_pay_per_post_buynow_var'])) ? $general_settings['arm_pay_per_post_buynow_var'] : 'arm_paid_post';

$arm_pay_per_post_allow_fancy_url = (!empty($general_settings['arm_pay_per_post_allow_fancy_url'])) ? $general_settings['arm_pay_per_post_allow_fancy_url'] : '';


$enable_tax= isset($general_settings['enable_tax']) ? $general_settings['enable_tax'] : 0;

?>
<div class="arm_global_settings_main_wrapper armPageContainer">
	<div class="page_sub_content">
   
		<form method="post" action="#" id="arm_pay_per_post_settings" class="arm_pay_per_post_settings arm_admin_form" onsubmit="return false;">
            <div class="form-table" width="100%">
                        <div class="page_sub_title arm_margin_bottom_32"><?php esc_html_e('Paid Post Buy Now Settings', 'ARMember'); ?></div>
                    <div class="arm_setting_main_content arm_padding_0 arm_margin_top_240">
                            <div class="arm_row_wrapper arm_row_wrapper_padding_before">
                                <div class="left_content">
                                    <div class="arm-form-table-label arm_form_header_label arm-setting-hadding-label arm_margin_bottom_0">
                                        <?php esc_html_e('Paid Post URL Parameter name', 'ARMember'); ?>  <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php esc_html_e('This parameter will be used while redirecting to the page when user will purchase specific post. By adding [arm_paid_post_buy_now] shortcode at the Alternative Content. If you have not setup page for \'Post Setup\' then please set \'post setup\' page from ARMember -> General Settings -> Page Setup page.', 'ARMember'); ?>"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="arm_content_border"></div>
                            <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block">
                                <label class="arm-form-table-label"><?php esc_html_e('Paid Post URL Parameter name', 'ARMember'); ?></label>
                                <input id="arm_pay_per_post_buynow_var" type="text" class="arm_margin_top_12" name="arm_general_settings[arm_pay_per_post_buynow_var]" value="<?php echo esc_attr($arm_pay_per_post_buynow_var); ?>" >
                                <span class="arm_info_text_style arm_padding_0 arm_margin_0 arm_margin_top_12"><?php esc_html_e('Paid Post URL parameter name Ex. :', 'ARMember'); echo 'arm_paid_post'; ?></span>
                            </div>
                    </div>
                
                    <div class="form-field arm_setting_main_content arm_margin_top_24" style="display: none;">
                        <div class="arm_row_wrapper">
                            <div class="left_content"> 
                                <div class="arm-form-table-label arm_form_header_label arm-setting-hadding-label arm_margin_0" style="vertical-align: baseline !important;">
                                    <?php esc_html_e('Enable Fancy URL for Paid Post', 'ARMember'); ?>
                                </div>
                            </div>
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_pay_per_post_allow_fancy_url" <?php checked($arm_pay_per_post_allow_fancy_url, '1');?> value="1" class="armswitch_input" name="arm_general_settings[arm_pay_per_post_allow_fancy_url]"/>
                                <label for="arm_pay_per_post_allow_fancy_url" class="armswitch_label"></label>
                            </div>
                        </div>
                        <div class="arm_content_border arm_margin_top_24"></div>
                        <div class="arm-note-message --warning arm_margin_top_24">
                            <p class="arm_margin_0"><?php esc_html_e('URL:', 'ARMember'); ?></p>
                            <span class="arm_info_text " ><code><span id="armpay_per_post_buynow_url_example"><?php echo ARM_HOME_URL.'/'; //phpcs:ignore?><?php echo ($arm_pay_per_post_allow_fancy_url == 1) ? ''.$arm_pay_per_post_buynow_var.'/' : '?'.$arm_pay_per_post_buynow_var.'='; //phpcs:ignore?>{post_id}</span></span></code>
                        </div>
                    </div>
                    <div class="form-field arm_margin_top_32">
                        <div class="page_sub_title"><?php esc_html_e('Default Alternative Content', 'ARMember'); ?></div>
                    </div>

                    <div class="arm_setting_main_content arm_margin_top_24 arm_padding_0 ">
                        <div class="arm_row_wrapper arm_row_wrapper_padding_before">
                            <div class="left_content">
                                <div class="arm-form-table-label arm_form_header_label arm-setting-hadding-label arm_margin_bottom_0">
                                <?php esc_html_e('Default Alternative Content', 'ARMember'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="arm_content_border"></div>

                        <div class="arm_row_wrapper arm_row_wrapper_padding_after arm_display_block">
                            <div class="arm_pay_per_post_default_content">
                                <?php 
                                $arm_pay_per_post_content = array(
                                    'textarea_name' => 'arm_general_settings[arm_pay_per_post_default_content]',
                                    'editor_class' => 'arm_pay_per_post_default_content',
                                    'textarea_rows' => 18,
                                    'default_editor' => 'tinymce',
                                    'editor_css' => '<style type="text/css"> body#tinymce{margin:0px !important;} </style>',

                                );
                                wp_editor(stripslashes($general_settings['arm_pay_per_post_default_content']), 'arm_pay_per_post_content', $arm_pay_per_post_content);
                                ?>
                                <span id="arm_comm_wp_validate_msg" class="error" style="display:none;"><?php esc_html_e('Content Cannot Be Empty.', 'ARMember');?></span>
                            </div>       
                        </div>
                    </div>
                </div>

            <?php 
                $arm_before_paid_post_settings_html = "";
                echo apply_filters('arm_before_paid_post_settings_html', $arm_before_paid_post_settings_html); //phpcs:ignore
            ?>
            
            <div class="arm_submit_btn_container arm_apply_changes_btn_container">
                    <img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_loader_img" class="arm_submit_btn_loader" style="display:none;" width="24" height="24" />&nbsp;<button id="arm_pay_per_post_settings_btn" class="arm_save_btn" name="arm_pay_per_post_settings_btn" type="submit"><?php esc_html_e('Apply Changes', 'ARMember') ?></button>
            </div>
            <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
            <input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
		</form>
	</div>
</div>
<script type="text/javascript">
    var ARM_PAY_PER_POST_RESET_ERROR = "<?php esc_html_e('Sorry, something went wrong.', 'ARMember'); ?>";
</script>