<?php

global $ARMember, $arm_ajaxurl, $arm_membership_setup;

$arm_transient_uniq_id = sanitize_text_field($_REQUEST['_wpnonce']);//phpcs:ignore

$setupData = get_transient('arm_preview_transient_'.$arm_transient_uniq_id);
$setupData = !empty($setupData) ? maybe_unserialize($setupData) : '';
$setupData = !empty($setupData) ? $setupData['setup_data'] : '';

$setup_labels = $setupData['setup_labels'];

if(!empty($setup_labels['credit_card_logos']))
{
    $arm_card_logos= explode(',',$setup_labels['credit_card_logos']);
    if(count($arm_card_logos) > 1)
    {
        $setup_labels['credit_card_logos'] = end($arm_card_logos);
    }
}

$setupData['setup_labels'] = $setup_labels;

//$setupData = isset($_REQUEST['setup_data']) ? maybe_serialize($_REQUEST['setup_data']) : '';
$browser_info = $ARMember->getBrowser($_SERVER['HTTP_USER_AGENT']); //phpcs:ignore

if (isset($browser_info) and $browser_info != "") 
{
    if ($browser_info['name'] == 'Internet Explorer' || $browser_info['name'] == 'Edge') 
    {
        if(empty($setupData) && isset($_COOKIE['arm_setupform_preview1']) && isset($_COOKIE['arm_setupform_preview2']) && isset($_COOKIE['arm_setupform_preview3']) && isset($_COOKIE['arm_setupform_preview4']) && isset($_COOKIE['arm_setupform_preview5']) )
        {
            $setupdataPreview =  $_COOKIE['arm_setupform_preview1'].$_COOKIE['arm_setupform_preview2'].$_COOKIE['arm_setupform_preview3'].$_COOKIE['arm_setupform_preview4'].$_COOKIE['arm_setupform_preview5'];//phpcs:ignore
            
            parse_str($setupdataPreview,$setupdataPreviewstr);
            
            $setupData = isset($setupdataPreviewstr['setup_data']) ? maybe_serialize($setupdataPreviewstr['setup_data']) : '';
        }
    }
}


$ARMember->set_front_js();
$ARMember->set_js();
$ARMember->set_front_css(2);
$ARMember->enqueue_angular_script();
$ARMember->set_global_javascript_variables();

wp_print_styles('arm_lite_front_css');
wp_print_styles('arm_front_css');
wp_print_styles('arm_lite_form_style_css');
wp_print_styles('arm_form_style_css');
wp_print_styles('arm_fontawesome_css');
wp_print_styles('arm_bootstrap_all_css');

wp_print_styles('arm_front_components_base-controls');
wp_print_styles('arm_front_components_form-style_base');
wp_print_styles('arm_front_components_form-style__arm-style-default');

//wp_print_styles('arm-font-awesome');

wp_print_styles('arm_front_components_form-style__arm-style-material');
wp_print_styles('arm_front_components_form-style__arm-style-outline-material');
wp_print_styles('arm_front_components_form-style__arm-style-rounded');

wp_print_styles('arm_front_component_css');
wp_print_styles('arm_custom_component_css');
?>
<script type='text/javascript'>
/* <![CDATA[ */
var ajaxurl = "<?php echo $arm_ajaxurl; //phpcs:ignore?>";
var armurl = "<?php echo MEMBERSHIP_URL; //phpcs:ignore?>";
var armviewurl = "<?php echo MEMBERSHIP_VIEWS_URL; //phpcs:ignore?>";
var imageurl = "<?php echo MEMBERSHIP_IMAGES_URL; //phpcs:ignore?>";
/* ]]> */
</script>
<?php
wp_print_scripts('jquery'); 
wp_print_scripts('arm_lite_common_js');
wp_print_scripts('arm_common_js');
wp_print_scripts('arm_admin_file_upload_js');
wp_print_scripts('arm_bootstrap_js');
wp_print_scripts('arm_bootstrap_datepicker_with_locale');
wp_print_scripts( 'wp-hooks' );
?>

<!--* Angular CSS & JS *-->
<?php
wp_print_styles('arm_angular_material_css');

wp_print_scripts('arm_angular_with_material');
wp_print_scripts('arm_jquery_validation');
wp_print_scripts('arm_form_validation');
?>
<style type="text/css">
    body{
        padding:0;
        margin:0;
    }
    .arm_setup_form_container{
        height: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        padding: 0px 8% 130px;
        box-sizing: border-box;
    }
    .arm_setup_form_container .arm_form_main_content{
        border: 1px solid var(--arm-gt-gray-100);
        border-radius: var(--arm-radius-12px);
        padding: 40px;
    }
    .arm_setup_form_container form{
        margin: 0 auto;
    }
</style>
<?php
echo $arm_membership_setup->arm_setup_shortcode_func(array('preview' => 'true','setup_data' => $setupData,)); //phpcs:ignore
