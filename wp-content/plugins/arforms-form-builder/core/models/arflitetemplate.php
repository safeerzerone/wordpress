<?php 

global $arflitefield,$arfliteform, $ARFLiteMdlDb, $wpdb, $tbl_arf_forms;

$wp_upload_dir = wp_upload_dir();

$upload_dir = $wp_upload_dir['basedir'] . '/arforms-form-builder/css/';
$main_css_dir = $wp_upload_dir['basedir'] . '/arforms-form-builder/maincss/';

$field_data = file_get_contents(ARFLITE_VIEWS_PATH . '/arflite_editor_data.json');

$field_data_obj = json_decode($field_data,true);

$field_data_obj = $field_data_obj['field_data'];

if( isset($arf_update_templates) && $arf_update_templates == true ){
    $values['id'] = 1;
}

$values['name'] = addslashes(esc_html__('Contact Us', 'arforms-form-builder'));
$values['description'] = addslashes(esc_html__('We would like to hear from you. Please send us a message by filling out the form below and we will get back with you shortly.', 'arforms-form-builder'));
$values['options']['custom_style'] = 1;
$values['is_template'] = '1';
$values['status'] = 'published';
$values['form_key'] = 'ContactUs';
$values['options']['display_title_form'] = "1";

$new_values = array(
    'arfmainformwidth' => '800',
    'form_width_unit' => 'px',
    'edit_msg' => 'Your submission was successfully saved.',
    'update_value' => 'Update',
    'arfeditoroff' => false,
    'arfmaintemplatepath' => '',
    'csv_format' => 'UTF-8',
    'date_format' => 'MMM D, YYYY',
    'cal_date_format' => 'MMM D, YYYY',
    'arfcalthemecss' => 'default_theme',
    'arfcalthemename' => 'default_theme',
    'theme_nicename' => 'default_theme',
    'permalinks' => false,
    'form_align' => 'left',
    'fieldset' => '2',
    'arfmainfieldsetcolor' => 'd9d9d9',
    'arfmainfieldsetpadding' => '30px 45px 30px 45px',
    'arfmainfieldsetradius' => '6',
    'font' => 'Helvetica',
    'font_other' => '',
    'font_size' => '16',
    'label_color' => '706d70',
    'weight' => 'normal',
    'position' => 'top',
    'hide_labels' => false,
    'align' => 'left',
    'width' => '130',
    'width_unit' => 'px',
    'arfdescfontsetting' => '"Lucida Grande","Lucida Sans Unicode",Tahoma,sans-serif',
    'arfdescfontsizesetting' => '12',
    'arfdesccolorsetting' => '666666',
    'arfdescweightsetting' => 'normal',
    'description_style' => 'normal',
    'arfdescalighsetting' => 'right',
    'field_font_size' => '14',
    'field_width' => '100',
    'field_width_unit' => '%',
    'field_width_tablet' => '',
    'field_width_unit_tablet' => '%',
    'field_width_mobile' => '',
    'field_width_unit_mobile' => '%',
    'auto_width' => false,
    'arffieldpaddingsetting' => '2',
    'arffieldmarginssetting' => 18,
    'arffieldanimationdurationsetting' => '0',
    'arfpbfieldanimationdurationsetting' => '0',
    'arffieldanimationdelaysetting' => '0',
    'arfpbfieldanimationdelaysetting' => '0',
    'bg_color' => 'ffffff',
    'text_color' => '17181c',
    'border_color' => 'b0b0b5',
    'arffieldborderwidthsetting' => '1',
    'arffieldborderstylesetting' => 'solid',
    'arfbgactivecolorsetting' => 'ffffff',
    'arfborderactivecolorsetting' => '087ee2',
    'arferrorbgcolorsetting' => 'ffffff',
    'arferrorbordercolorsetting' => 'ed4040',
    'arferrorborderwidthsetting' => '1',
    'arferrorborderstylesetting' => 'solid',
    'arfradioalignsetting' => 'inline',
    'arfcheckboxalignsetting' => 'block',
    'check_font' => 'Helvetica',
    'check_font_other' => '',
    'arfcheckboxfontsizesetting' => '12px',
    'arfcheckboxlabelcolorsetting' => '444444',
    'check_weight' => 'normal',
    'arfsubmitbuttonstylesetting' => false,
    'arfsubmitbuttonfontsizesetting' => '18',
    'arfsubmitbuttonwidthsetting' => 120,
    'arfsubmitbuttonwidthsetting_tablet' => 120,
    'arfsubmitbuttonwidthsetting_mobile' => 120,
    'arfsubmitbuttonheightsetting' => 40,
    'submit_bg_color' => '077BDD',
    'arfsubmitbuttonbgcolorhoversetting' => '0b68b7',
    'arfsubmitbgcolor2setting' => '',
    'arfsubmitbordercolorsetting' => 'f6f6f8',
    'arfsubmitborderwidthsetting' => '0',
    'arfsubmitboxxoffsetsetting' => '1', 
    'arfsubmitboxyoffsetsetting' => '2',
    'arfsubmitboxblursetting' => '3',
    'arfsubmitboxshadowsetting' => '0',
    'arfsubmittextcolorsetting' => 'ffffff',
    'arfsubmitweightsetting' => 'bold',
    'arfsubmitborderradiussetting' => '3',
    'submit_bg_img' => '',
    'submit_hover_bg_img' => '',
    'arfsubmitbuttonmarginsetting' => '10px 10px 0px 0px',
    'arfsubmitbuttonpaddingsetting' => '8',
    'arfsubmitshadowcolorsetting' => 'c6c8cc',
    'border_radius' => 2,
    'border_radius_tablet' => 2,
    'border_radius_mobile' => 2,
    'arferroriconsetting' => 'e1.png',
    'arferrorbgsetting' => 'F3CAC7',
    'arferrorbordersetting' => 'FA8B83',
    'arferrortextsetting' => '501411',
    'arffontsizesetting' => '14',
    'arfsucessiconsetting' => 's1.png',
    'success_bg' => NULL,
    'success_border' => NULL,
    'success_text' => NULL,
    'arfsucessfontsizesetting' => '14',
    'arftextareafontsizesetting' => '13px',
    'arftextareawidthsetting' => '400',
    'arftextareawidthunitsetting' => 'px',
    'arftextareapaddingsetting' => '2',
    'arftextareamarginsetting' => '20',
    'arftextareabgcolorsetting' => 'ffffff',
    'arftextareacolorsetting' => '444444',
    'arftextareabordercolorsetting' => 'dddddd',
    'arftextareaborderwidthsetting' => '1',
    'arftextareaborderstylesetting' => 'solid',
    'text_direction' => '1',
    'arffieldheightsetting' => '24',
    'arfmainformtitlecolorsetting' => '#0d0e12',
    'form_title_font_size' => '28',
    'error_font' => 'Lucida Sans Unicode',
    'error_font_other' => '',
    'arfactivebgcolorsetting' => 'FFFF00',
    'arfmainformbgcolorsetting' => 'ffffff',
    'arfmainformtitleweightsetting' => 'normal',
    'arfmainformtitlepaddingsetting' => '0px 0px 20px 0px',
    'arfmainformbordershadowcolorsetting' => 'f2f2f2',
    'form_border_shadow' => 'flat',
    'arfsubmitalignsetting' => 'left',
    'checkbox_radio_style' => '1',
    'bg_color_pg_break' => '087ee2',
    'bg_inavtive_color_pg_break' => '7ec3fc',
    'text_color_pg_break' => 'ffffff',
    'text_color_pg_break_style3' => '087ee2',
    'arfmainform_bg_img' => '',
    'arfmainform_opacity' => '1',
    'arfmainfield_opacity' => '0',
    'arfsubmitfontfamily' => 'Helvetica',
    'arfmainfieldsetpadding_1' => '30',
    'arfmainfieldsetpadding_2' => '45',
    'arfmainfieldsetpadding_3' => '30',
    'arfmainfieldsetpadding_4' => '45',
    "arfmainfieldsetpadding_1_tablet"=> "",
    "arfmainfieldsetpadding_2_tablet"=> "",
    "arfmainfieldsetpadding_3_tablet"=> "",
    "arfmainfieldsetpadding_4_tablet"=> "",
    "arfmainfieldsetpadding_1_mobile"=> "",
    "arfmainfieldsetpadding_2_mobile"=> "",
    "arfmainfieldsetpadding_3_mobile"=> "",
    "arfmainfieldsetpadding_4_mobile"=> "",
    'arfmainformtitlepaddingsetting_1' => '0',
    'arfmainformtitlepaddingsetting_2' => '0',
    'arfmainformtitlepaddingsetting_3' => 30,
    'arfmainformtitlepaddingsetting_4' => '0',
    'arffieldinnermarginssetting_1' => 10,
    'arffieldinnermarginssetting_2' => '10',
    'arffieldinnermarginssetting_3' => 10,
    'arffieldinnermarginssetting_4' => '10',
    'arfsubmitbuttonmarginsetting_1' => '10',
    'arfsubmitbuttonmarginsetting_2' => '10',
    'arfsubmitbuttonmarginsetting_3' => '0',
    'arfsubmitbuttonmarginsetting_4' => '0',
    'arfcheckradiostyle' => 'flat',
    'arffieldanimationstyle' => 'no animation',
    'arfpbfieldanimationstyle' => 'slideInLeft',
    "arfpagebreakinheritanimation" => "0",
    'arfcheckradiocolor' => 'blue',
    'arf_checked_checkbox_icon' => '',
    'enable_arf_checkbox' => '0',
    'arf_checked_radio_icon' => '',
    'enable_arf_radio' => '0',
    'checked_checkbox_icon_color' => '#0C7CD5',
    'checked_radio_icon_color' => '#0C7CD5',
    'arfformtitlealign' => 'left',
    'arferrorstyle' => 'advance',
    'arferrorstylecolor' => '#ed4040|#FFFFFF|#ed4040',
    'arferrorstylecolor2' => '#ed4040|#FFFFFF|#ed4040',
    'arferrorstyleposition' => 'bottom',
    'arfsubmitautowidth' => '100',
    'arftitlefontfamily' => 'Helvetica',
    'bar_color_survey' => '#007ee4',
    'bg_color_survey' => '#dadde2',
    'text_color_survey' => '#333333',
    'prefix_suffix_bg_color' => '#e7e8ec',
    'prefix_suffix_icon_color' => '#808080',
    'arfsectionpaddingsetting_1' => '20',
    'arfsectionpaddingsetting_2' => '0',
    'arfsectionpaddingsetting_3' => '20',
    'arfsectionpaddingsetting_4' => '20',
    'arfsectionpaddingsetting' => "20px 0px 20px 20px",
    'arffieldinnermarginssetting' => '8px 10px 8px 10px',
    'arfsucessbgcolorsetting' => '#E0FDE2',
    'arfsucessbordercolorsetting' => '#BFE0C1',
    'arfsucesstextcolorsetting' => '#4C4D4E',
    'arfformerrorbgcolorsetting' => '#FDECED',
    'arfformerrorbordercolorsetting' => '#F9CFD1',
    'arfformerrortextcolorsetting' => '#ED4040',
    "arfsubmitbuttonstyle"=>"border",
    'arfinputstyle' => 'standard',
    'arfcheckradiostyle' => 'default',
    'arffieldanimationstyle' => 'no animation',
    'arfpbfieldanimationstyle' => 'slideInLeft',
    "arfpagebreakinheritanimation" => "0",
    'arfmainform_color_skin' => 'blue',
    'arf_tooltip_bg_color' => '#000000',
    'arf_tooltip_font_color' => '#ffffff',
    "arfcommonfont"=>"Helvetica",
    "arfmainfieldcommonsize"=>"3",
    "arfvalidationbgcolorsetting"=>"#ed4040",
    "arfvalidationtextcolorsetting"=>"#ffffff",
    "arfdatepickerbgcolorsetting"=>"#007ee4",
    "arfdatepickertextcolorsetting"=>"#000000",
    "arfsectiontitlefamily"=>"Helvetica",
    "arfsectiontitlefontsizesetting"=>"16",
    "arfsectiontitleweightsetting"=>"bold",
    "arfsubmitbuttontext"=>"Submit",
    "arfuploadbtntxtcolorsetting"=>"#FFFFFF",
    "arfuploadbtnbgcolorsetting" =>"#0C7CD5",
    "arf_req_indicator"=>"0",
    "arf_section_inherit_bg" => "1",
    "arfformsectionbackgroundcolor"=>"#ffffff",
    "arfmainbasecolor" => "#0c7cd5",
    "arflikebtncolor"=>"#4786ff",
    "arfdislikebtncolor"=>"#ec3838",
    "arfstarratingcolor"=>"#FCBB1D",
    "arfsliderselectioncolor"=>"#d1dee5",
    "arfslidertrackcolor"=>"#bcc7cd",
    "arfplaceholder_opacity" => "0.5",
    "arf_bg_position_x" => "left",
    "arf_bg_position_input_x" => "",
    "arf_bg_position_y" => "top",
    "arf_bg_position_input_y" => "",
);

$new_values1 = maybe_serialize($new_values);
$values['form_css'] = $new_values1;
$form_id = $arfliteform->arflitecreate($values);
if (!empty($new_values)) {

    $use_saved = true;

    $arfssl = (is_ssl()) ? 1 : 0;

    $filename = ARFLITE_FORMPATH . '/core/arflite_css_create_main.php';

    $wp_upload_dir = wp_upload_dir();

    $target_path = $wp_upload_dir['basedir'] . '/arforms-form-builder/maincss';

    $css = $warn = "/* WARNING: Any changes made to this file will be lost when your ARForms settings are updated */";


    $css .= "\n";


    ob_start();


    include $filename;


    $css .= ob_get_contents();


    ob_end_clean();


    $css .= "\n " . $warn;

    $css_file = $target_path . '/maincss_' . $form_id . '.css';

    WP_Filesystem();
    global $wp_filesystem;
    $css = str_replace('##', '#', $css);
    $wp_filesystem->put_contents($css_file, $css, 0777);

    wp_cache_delete($form_id, 'arfform');

    $filename1 = ARFLITE_FORMPATH . '/core/arflite_css_create_materialize.php';
    $css1 = $warn1 = "/* WARNING: Any changes made to this file will be lost when your ARForms settings are updated */";
    $css1 .= "\n";
    ob_start();
    include $filename1;
    $css1 .= ob_get_contents();
    ob_end_clean();
    $css1 .= "\n " . $warn1;
    $css_file1 = $target_path . '/maincss_materialize_' . $form_id . '.css';
    WP_Filesystem();
    $css1 = str_replace('##', '#', $css1);
    $wp_filesystem->put_contents($css_file1, $css1, 0777);
    wp_cache_delete($form_id, 'arfform');

    // $filename2 = ARFLITE_FORMPATH . '/core/css_create_materialize_outline.php';
    // $css2 = $warn2 = "/* WARNING: Any changes made to this file will be lost when your ARForms settings are updated */";
    // $css2 .= "\n";
    // ob_start();
    // // include $filename2;
    // $css2 .= ob_get_contents();
    // ob_end_clean();
    // $css2 .= "\n" . $warn2;
    // $css_file2 = $target_path . '/maincss_materialize_outlined_' . $form_id . '.css';
    // WP_Filesystem();
    // $css2 = str_replace( '##', '#', $css2 );
    // $wp_filesystem->put_contents( $css_file2, $css2, 0777 );
} else {

    $query_results = true;
}

$field_order = array();
$inner_field_order = array();
$field_values = array();
$field_values['field_options'] = $field_data_obj['text'];
$field_values['name'] = 'First Name';
$field_values['field_options']['name'] = 'First Name';
$field_values['type'] = 'text';
$field_values['field_options']['description'] = '';
$field_values['field_options']['required'] = 1;
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter first name', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('First Name', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['arf_regular_expression_msg'] = addslashes(esc_html__('Entered value is invalid', 'arforms-form-builder'));

$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 1;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['text'];
$field_values['name'] = 'Last Name';
$field_values['type'] = 'text';
$field_values['field_options']['name'] = 'Last Name';
$field_values['field_options']['required'] = 1;
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter last name', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Last Name', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['arf_regular_expression_msg'] = addslashes(esc_html__('Entered value is invalid', 'arforms-form-builder'));
$field_values['field_options']['label'] = 'hidden';
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 2;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['email'];
$field_values['name'] = addslashes(esc_html__('Email', 'arforms-form-builder'));
$field_values['field_options']['name'] = addslashes(esc_html__('Email', 'arforms-form-builder'));
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'email';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter email address', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Email Address', 'arforms-form-builder'));
$field_values['field_options']['invalid'] = addslashes(esc_html__('Please enter a valid email address', 'arforms-form-builder'));
$field_values['field_options']['confirm_email_label'] = addslashes(esc_html__('Confirm Email Address', 'arforms-form-builder'));
$field_values['field_options']['invalid_confirm_email'] = addslashes(esc_html__('Confirm email address does not match with email', 'arforms-form-builder'));
$field_values['field_options']['confirm_email_placeholder'] = addslashes(esc_html__('Confirm Email Address', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 3;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['url'];
$field_values['name'] = addslashes(esc_html__('Website', 'arforms-form-builder'));
$field_values['field_options']['name'] = addslashes(esc_html__('Website', 'arforms-form-builder'));
$field_values['type'] = 'url';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter your website URL', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Website', 'arforms-form-builder'));
$field_values['field_options']['invalid'] = addslashes(esc_html__('Please enter a valid website', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 4;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['text'];
$field_values['name'] = addslashes(esc_html__('Subject', 'arforms-form-builder'));
$field_values['field_options']['name'] = addslashes(esc_html__('Subject', 'arforms-form-builder'));
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'text';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter subject', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Subject', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 5;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['textarea'];
$field_values['name'] = addslashes(esc_html__('Message', 'arforms-form-builder'));
$field_values['field_options']['name'] = addslashes(esc_html__('Message', 'arforms-form-builder'));
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'textarea';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter your message', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Message', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 6;
unset($field_values);
unset($field_id);

unset($values);

$field_options = $wpdb->get_results($wpdb->prepare("SELECT `options` FROM `" . $tbl_arf_forms . "` WHERE `id` = %d", $form_id)); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Reason: $tbl_arf_forms is table name defined globally. False Positive alarm

$form_opt = maybe_unserialize($field_options[0]->options);

$form_opt['arf_field_order'] = wp_json_encode($field_order);
$form_opt['arf_inner_field_order'] = wp_json_encode( $inner_field_order );

$form_options = maybe_serialize($form_opt);

$wpdb->update($tbl_arf_forms, array('options' => $form_options), array('id' => $form_id));

unset($field_order);
unset($inner_field_order);


if( isset($arf_update_templates) && $arf_update_templates == true ){
    $values['id'] = 2;
}
$values['name'] = 'Subscription Form';
$values['description'] = 'Gather user information';
$values['options']['custom_style'] = 1;
$values['is_template'] = '1';
$values['status'] = 'published';
$values['form_key'] = 'Subscription';
$values['options']['display_title_form'] = "1";

$new_values = array(
    'arfmainformwidth' => '550',
    'form_width_unit' => 'px',
    'edit_msg' => 'Your submission was successfully saved.',
    'update_value' => 'Update',
    'arfeditoroff' => false,
    'arfmaintemplatepath' => '',
    'csv_format' => 'UTF-8',
    'date_format' => 'MMM D, YYYY',
    'cal_date_format' => 'MMM D, YYYY',
    'arfcalthemecss' => 'default_theme',
    'arfcalthemename' => 'default_theme',
    'theme_nicename' => 'default_theme',
    'permalinks' => false,
    'form_align' => 'left',
    'fieldset' => '2',
    'arfmainfieldsetcolor' => 'd9d9d9',
    'arfmainfieldsetpadding' => '30px 45px 30px 45px',
    'arfmainfieldsetradius' => '6',
    'font' => 'Helvetica',
    'font_other' => '',
    'font_size' => '16',
    'label_color' => '706d70',
    'weight' => 'normal',
    'position' => 'top',
    'hide_labels' => false,
    'align' => 'left',
    'width' => 90,
    'width_unit' => 'px',
    'arfdescfontsetting' => '"Lucida Grande","Lucida Sans Unicode",Tahoma,sans-serif',
    'arfdescfontsizesetting' => 14,
    'arfdesccolorsetting' => '666666',
    'arfdescweightsetting' => 'normal',
    'description_style' => 'normal',
    'arfdescalighsetting' => 'right',
    'field_font_size' => '14',
    'field_width' => '100',
    'field_width_unit' => '%',
    'field_width_tablet' => '',
    'field_width_unit_tablet' => '%',
    'field_width_mobile' => '',
    'field_width_unit_mobile' => '%',
    'auto_width' => false,
    'arffieldpaddingsetting' => '2',
    'arffieldmarginssetting' => 20,
    'arffieldanimationdurationsetting' => '0',
    'arfpbfieldanimationdurationsetting' => '0',
    'arffieldanimationdelaysetting' => '0',
    'arfpbfieldanimationdelaysetting' => '0',
    'bg_color' => 'ffffff',
    'text_color' => '17181c',
    'border_color' => 'b0b0b5',
    'arffieldborderwidthsetting' => '2',
    'arffieldborderstylesetting' => 'solid',
    'arfbgactivecolorsetting' => '#fafafa',
    'arfborderactivecolorsetting' => '#20bfe3',
    'arferrorbgcolorsetting' => 'ffffff',
    'arferrorbordercolorsetting' => 'ed4040',
    'arferrorborderwidthsetting' => '1',
    'arferrorborderstylesetting' => 'solid',
    'arfradioalignsetting' => 'inline',
    'arfcheckboxalignsetting' => 'block',
    'check_font' => 'Helvetica',
    'check_font_other' => '',
    'arfcheckboxfontsizesetting' => '12px',
    'arfcheckboxlabelcolorsetting' => '444444',
    'check_weight' => 'normal',
    'arfsubmitbuttonstylesetting' => false,
    'arfsubmitbuttonfontsizesetting' => '18',
    'arfsubmitbuttonwidthsetting' => '150',
    'arfsubmitbuttonwidthsetting_mobile' => '150',
    'arfsubmitbuttonwidthsetting_tablet' => '150',
    'arfsubmitbuttonheightsetting' => '42',
    'submit_bg_color' => '#20bfe3',
    'arfsubmitbuttonbgcolorhoversetting' => '#19adcf',
    'arfsubmitbgcolor2setting' => '',
    'arfsubmitbordercolorsetting' => '#e1e1e3',
    'arfsubmitborderwidthsetting' => '0',
    'arfsubmitboxxoffsetsetting' => '1', 
    'arfsubmitboxyoffsetsetting' => '2',
    'arfsubmitboxblursetting' => '3',
    'arfsubmitboxshadowsetting' => '0',
    'arfsubmittextcolorsetting' => 'ffffff',
    'arfsubmitweightsetting' => 'bold',
    'arfsubmitborderradiussetting' => '3',
    'submit_bg_img' => '',
    'submit_hover_bg_img' => '',
    'arfsubmitbuttonmarginsetting' => '10px 10px 0px 0px',
    'arfsubmitbuttonpaddingsetting' => '8',
    'arfsubmitshadowcolorsetting' => '#f0f0f0',
    'border_radius' => '3',
    'border_radius_tablet' => '3',
    'border_radius_mobile' => '3',
    'arferroriconsetting' => 'e1.png',
    'arferrorbgsetting' => 'F3CAC7',
    'arferrorbordersetting' => 'FA8B83',
    'arferrortextsetting' => '501411',
    'arffontsizesetting' => '14',
    'arfsucessiconsetting' => 's1.png',
    'success_bg' => '',
    'success_border' => '',
    'success_text' => '',
    'arfsucessfontsizesetting' => '14',
    'arftextareafontsizesetting' => '13px',
    'arftextareawidthsetting' => '400',
    'arftextareawidthunitsetting' => 'px',
    'arftextareapaddingsetting' => '2',
    'arftextareamarginsetting' => '20',
    'arftextareabgcolorsetting' => 'ffffff',
    'arftextareacolorsetting' => '444444',
    'arftextareabordercolorsetting' => 'dddddd',
    'arftextareaborderwidthsetting' => '1',
    'arftextareaborderstylesetting' => 'solid',
    'text_direction' => '1',
    'arffieldheightsetting' => '24',
    'arfmainformtitlecolorsetting' => '#696969',
    'form_title_font_size' => 26,
    'error_font' => 'Lucida Sans Unicode',
    'error_font_other' => '',
    'arfactivebgcolorsetting' => 'FFFF00',
    'arfmainformbgcolorsetting' => 'ffffff',
    'arfmainformtitleweightsetting' => 'normal',
    'arfmainformtitlepaddingsetting' => '0px 0px 20px 0px',
    'arfmainformbordershadowcolorsetting' => '#d4d2d4',
    'form_border_shadow' => 'shadow',
    'arfsubmitalignsetting' => 'center',
    'checkbox_radio_style' => '1',
    'bg_color_pg_break' => '087ee2',
    'bg_inavtive_color_pg_break' => '7ec3fc',
    'text_color_pg_break' => 'ffffff',
    'text_color_pg_break_style3' => '087ee2',
    'arfmainform_bg_img' => '',
    'arfmainform_opacity' => '1',
    'arfmainfield_opacity' => '0',
    'arfsubmitfontfamily' => 'Helvetica',
    'arfmainfieldsetpadding_1' => '30',
    'arfmainfieldsetpadding_2' => '45',
    'arfmainfieldsetpadding_3' => '30',
    'arfmainfieldsetpadding_4' => '45',
    'arfmainfieldsetpadding_1_tablet'=> '',
    'arfmainfieldsetpadding_2_tablet'=> '',
    'arfmainfieldsetpadding_3_tablet'=> '',
    'arfmainfieldsetpadding_4_tablet'=> '',
    'arfmainfieldsetpadding_1_mobile'=> '',
    'arfmainfieldsetpadding_2_mobile'=> '',
    'arfmainfieldsetpadding_3_mobile'=> '',
    'arfmainfieldsetpadding_4_mobile'=> '',
    'arfmainformtitlepaddingsetting_1' => '0',
    'arfmainformtitlepaddingsetting_2' => '0',
    'arfmainformtitlepaddingsetting_3' => 25,
    'arfmainformtitlepaddingsetting_4' => '0',
    'arffieldinnermarginssetting_1' => '10',
    'arffieldinnermarginssetting_2' => '10',
    'arffieldinnermarginssetting_3' => '10',
    'arffieldinnermarginssetting_4' => '10',
    'arfsubmitbuttonmarginsetting_1' => '10',
    'arfsubmitbuttonmarginsetting_2' => '10',
    'arfsubmitbuttonmarginsetting_3' => '0',
    'arfsubmitbuttonmarginsetting_4' => '0',
    'arfcheckradiostyle' => 'flat',
    'arffieldanimationstyle' => 'no animation',
    'arfpbfieldanimationstyle' => 'slideInLeft',
    "arfpagebreakinheritanimation" => "0",
    'arfcheckradiocolor' => 'blue',
    'arf_checked_checkbox_icon' => '',
    'enable_arf_checkbox' => '0',
    'arf_checked_radio_icon' => '',
    'enable_arf_radio' => '0',
    'checked_checkbox_icon_color' => '#23b7e5',
    'checked_radio_icon_color' => '#23b7e5',
    'arfformtitlealign' => 'center',
    'arferrorstyle' => 'normal',
    'arferrorstylecolor' => '#ed4040|#FFFFFF|#ed4040',
    'arferrorstylecolor2' => '#ed4040|#FFFFFF|#ed4040',
    'arferrorstyleposition' => 'bottom',
    'arfsubmitautowidth' => '100',
    'arftitlefontfamily' => 'Helvetica',
    'bar_color_survey' => '#007ee4',
    'bg_color_survey' => '#dadde2',
    'text_color_survey' => '#333333',
    'prefix_suffix_bg_color' => '#e7e8ec',
    'prefix_suffix_icon_color' => '#808080',
    'arfsectionpaddingsetting_1' => '20',
    'arfsectionpaddingsetting_2' => '0',
    'arfsectionpaddingsetting_3' => '20',
    'arfsectionpaddingsetting_4' => '20',
    'arfsectionpaddingsetting' => "20px 0px 20px 20px",
    'arffieldinnermarginssetting' => '10px 10px 10px 10px',
    'arfsucessbgcolorsetting' => '#E0FDE2',
    'arfsucessbordercolorsetting' => '#BFE0C1',
    'arfsucesstextcolorsetting' => '#4C4D4E',
    'arfformerrorbgcolorsetting' => '#FDECED',
    'arfformerrorbordercolorsetting' => '#F9CFD1',
    'arfformerrortextcolorsetting' => '#ED4040',
    'check_weight_form_title' => 'bold',
    "arfsubmitbuttonstyle"=>"border",
    'arfinputstyle' => 'standard',
    'arfcheckradiostyle' => 'default',
    'arffieldanimationstyle' => 'no animation',
    'arfpbfieldanimationstyle' => 'slideInLeft',
    "arfpagebreakinheritanimation" => "0",
    'arfmainform_color_skin' => 'cyan',
    'arf_tooltip_bg_color' => '#000000',
    'arf_tooltip_font_color' => '#ffffff',
    "arfcommonfont"=>"Helvetica",
    "arfmainfieldcommonsize"=>"3",
    "arfvalidationbgcolorsetting"=>"#ed4040",
    "arfvalidationtextcolorsetting"=>"#ffffff",
    "arfdatepickerbgcolorsetting"=>"#007ee4",
    "arfdatepickertextcolorsetting"=>"#000000",
    "arfsectiontitlefamily"=>"Helvetica",
    "arfsectiontitlefontsizesetting"=>"16",
    "arfsectiontitleweightsetting"=>"bold",
    "arfsubmitbuttontext"=>"Submit",
    "arfuploadbtntxtcolorsetting"=>"#ffffff",
    "arfuploadbtnbgcolorsetting" =>"#23b7e5",
    "arf_req_indicator"=>"0",
    "arf_section_inherit_bg" => "1",
    "arfformsectionbackgroundcolor"=>"#ffffff",
    "arfmainbasecolor" => "#23b7e5",
    "arflikebtncolor"=>"#4786ff",
    "arfdislikebtncolor"=>"#ec3838",
    "arfstarratingcolor"=>"#FCBB1D",
    "arfsliderselectioncolor"=>"#d1dee5",
    "arfslidertrackcolor"=>"#bcc7cd",
    "arfplaceholder_opacity" => "0.5",
    "arf_bg_position_x" => "left",
    "arf_bg_position_input_x" => "",
    "arf_bg_position_y" => "top",
    "arf_bg_position_input_y" => "",
);

$new_values1 = maybe_serialize($new_values);
$values['form_css'] = $new_values1;
$form_id = $arfliteform->arflitecreate($values);
if (!empty($new_values)) {

    $use_saved = true;

    $arfssl = (is_ssl()) ? 1 : 0;

    $filename = ARFLITE_FORMPATH . '/core/arflite_css_create_main.php';

    $wp_upload_dir = wp_upload_dir();

    $target_path = $wp_upload_dir['basedir'] . '/arforms-form-builder/maincss';

    $css = $warn = "/* WARNING: Any changes made to this file will be lost when your ARForms settings are updated */";


    $css .= "\n";


    ob_start();


    include $filename;


    $css .= ob_get_contents();


    ob_end_clean();


    $css .= "\n " . $warn;

    $css_file = $target_path . '/maincss_' . $form_id . '.css';

    WP_Filesystem();
    global $wp_filesystem;
    $css = str_replace('##', '#', $css);
    $wp_filesystem->put_contents($css_file, $css, 0777);
    wp_cache_delete($form_id, 'arfform');

    $filename1 = ARFLITE_FORMPATH . '/core/arflite_css_create_materialize.php';
    $css1 = $warn1 = "/* WARNING: Any changes made to this file will be lost when your ARForms settings are updated */";
    $css1 .= "\n";
    ob_start();
    include $filename1;
    $css1 .= ob_get_contents();
    ob_end_clean();
    $css1 .= "\n " . $warn1;
    $css_file1 = $target_path . '/maincss_materialize_' . $form_id . '.css';
    WP_Filesystem();
    $css1 = str_replace('##', '#', $css1);
    $wp_filesystem->put_contents($css_file1, $css1, 0777);
    wp_cache_delete($form_id, 'arfform');

    // $filename2 = ARFLITE_FORMPATH . '/core/css_create_materialize_outline.php';
    // $css2 = $warn2 = "/* WARNING: Any changes made to this file will be lost when your ARForms settings are updated */";
    // $css2 .= "\n";
    // ob_start();
    // // include $filename2;
    // $css2 .= ob_get_contents();
    // ob_end_clean();
    // $css2 .= "\n" . $warn2;
    // $css_file2 = $target_path . '/maincss_materialize_outlined_' . $form_id . '.css';
    // WP_Filesystem();
    // $css2 = str_replace( '##', '#', $css2 );
    // $wp_filesystem->put_contents( $css_file2, $css2, 0777 );

} else {
    $query_results = true;
}

$field_order = array();
$inner_field_order = array();

$field_values = array();
$field_values['field_options'] = $field_data_obj['text'];
$field_values['name'] = 'First Name';
$field_values['field_options']['name'] = 'First Name';
$field_values['field_options']['placeholdertext'] = 'First Name';
$field_values['field_options']['description'] = '';
$field_values['type'] = 'text';
$field_values['field_options']['required'] = 1;
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter first name', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('First Name', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['arf_regular_expression_msg'] = addslashes(esc_html__('Entered value is invalid', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 1;
unset($field_values);

$field_values = array();
$field_values['field_options'] = $field_data_obj['text'];
$field_values['name'] = 'Last Name';
$field_values['field_options']['name'] = 'Last Name';
$field_values['field_options']['placeholdertext'] = 'Last Name';
$field_values['type'] = 'text';
$field_values['field_options']['description'] = '';
$field_values['field_options']['required'] = 1;
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter last name', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Last Name', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['arf_regular_expression_msg'] = addslashes(esc_html__('Entered value is invalid', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 2;
unset($field_values);

$field_values = array();
$field_values['field_options'] = $field_data_obj['email'];
$field_values['name'] = 'Email';
$field_values['type'] = 'email';
$field_values['field_options']['name'] = 'Email';
$field_values['field_options']['placeholdertext'] = 'Email Address';
$field_values['field_options']['required'] = 1;
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter email address', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Email Address', 'arforms-form-builder'));
$field_values['field_options']['invalid'] = addslashes(esc_html__('Please enter a valid email address', 'arforms-form-builder'));
$field_values['field_options']['confirm_email_label'] = addslashes(esc_html__('Confirm Email Address', 'arforms-form-builder'));
$field_values['field_options']['invalid_confirm_email'] = addslashes(esc_html__('Confirm email address does not match with email', 'arforms-form-builder'));
$field_values['field_options']['confirm_email_placeholder'] = addslashes(esc_html__('Confirm Email Address', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 3;
unset($field_values);
unset($field_id);
unset($values);

$field_options = $wpdb->get_results($wpdb->prepare("SELECT `options` FROM `" . $tbl_arf_forms . "` WHERE `id` = %d", $form_id)); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Reason: $tbl_arf_forms is table name defined globally. False Positive alarm 

$form_opt = maybe_unserialize($field_options[0]->options);

$form_opt['arf_field_order'] = wp_json_encode($field_order);
$form_opt['arf_inner_field_order'] = wp_json_encode( $inner_field_order );

$form_options = maybe_serialize($form_opt);

$wpdb->update($tbl_arf_forms, array('options' => $form_options), array('id' => $form_id));

unset($field_order);
unset($inner_field_order);


if( isset($arf_update_templates) && $arf_update_templates == true ){
    $values['id'] = 3;
}
$values['name'] = 'Feedback Form';
$values['description'] = 'Gather User information';
$values['options']['custom_style'] = 1;
$values['is_template'] = '1';
$values['status'] = 'published';
$values['form_key'] = 'Feedback';

$new_values = array(
    'arfmainformwidth' => '800',
    'form_width_unit' => 'px',
    'edit_msg' => 'Your submission was successfully saved.',
    'update_value' => 'Update',
    'arfeditoroff' => false,
    'arfmaintemplatepath' => '',
    'csv_format' => 'UTF-8',
    'date_format' => 'MMM D, YYYY',
    'cal_date_format' => 'MMM D, YYYY',
    'arfcalthemecss' => 'default_theme',
    'arfcalthemename' => 'default_theme',
    'theme_nicename' => 'default_theme',
    'permalinks' => false,
    'form_align' => 'left',
    'fieldset' => '2',
    'arfmainfieldsetcolor' => 'd9d9d9',
    'arfmainfieldsetpadding' => '30px 45px 30px 45px',
    'arfmainfieldsetradius' => '6',
    'font' => 'Helvetica',
    'font_other' => '',
    'font_size' => '16',
    'label_color' => '706d70',
    'weight' => 'normal',
    'position' => 'top',
    'hide_labels' => false,
    'align' => 'left',
    'width' => '130',
    'width_unit' => 'px',
    'arfdescfontsetting' => '"Lucida Grande","Lucida Sans Unicode",Tahoma,sans-serif',
    'arfdescfontsizesetting' => '12',
    'arfdesccolorsetting' => '666666',
    'arfdescweightsetting' => 'normal',
    'description_style' => 'normal',
    'arfdescalighsetting' => 'right',
    'field_font_size' => '14',
    'field_width' => '100',
    'field_width_unit' => '%',
    'field_width_tablet' => '',
    'field_width_unit_tablet' => '%',
    'field_width_mobile' => '',
    'field_width_unit_mobile' => '%',
    'auto_width' => false,
    'arffieldpaddingsetting' => '2',
    'arffieldmarginssetting' => '23',
    'arffieldanimationdurationsetting' => '0',
    'arfpbfieldanimationdurationsetting' => '0',
    'arffieldanimationdelaysetting' => '0',
    'arfpbfieldanimationdelaysetting' => '0',
    'bg_color' => 'ffffff',
    'text_color' => '17181c',
    'border_color' => 'b0b0b5',
    'arffieldborderwidthsetting' => '1',
    'arffieldborderstylesetting' => 'solid',
    'arfbgactivecolorsetting' => 'ffffff',
    'arfborderactivecolorsetting' => '087ee2',
    'arferrorbgcolorsetting' => 'ffffff',
    'arferrorbordercolorsetting' => 'ed4040',
    'arferrorborderwidthsetting' => '1',
    'arferrorborderstylesetting' => 'solid',
    'arfradioalignsetting' => 'inline',
    'arfcheckboxalignsetting' => 'block',
    'check_font' => 'Helvetica',
    'check_font_other' => '',
    'arfcheckboxfontsizesetting' => '12px',
    'arfcheckboxlabelcolorsetting' => '444444',
    'check_weight' => 'normal',
    'arfsubmitbuttonstylesetting' => false,
    'arfsubmitbuttonfontsizesetting' => '18',
    'arfsubmitbuttonwidthsetting' => '',
    'arfsubmitbuttonwidthsetting_tablet' => '',
    'arfsubmitbuttonwidthsetting_mobile' => '',
    'arfsubmitbuttonheightsetting' => '38',
    'submit_bg_color' => '077BDD',
    'arfsubmitbuttonbgcolorhoversetting' => '0b68b7',
    'arfsubmitbgcolor2setting' => '',
    'arfsubmitbordercolorsetting' => 'f6f6f8',
    'arfsubmitborderwidthsetting' => '0',
    'arfsubmitboxxoffsetsetting' => '1', 
    'arfsubmitboxyoffsetsetting' => '2',
    'arfsubmitboxblursetting' => '3',
    'arfsubmitboxshadowsetting' => '0',
    'arfsubmittextcolorsetting' => 'ffffff',
    'arfsubmitweightsetting' => 'bold',
    'arfsubmitborderradiussetting' => '3',
    'submit_bg_img' => '',
    'submit_hover_bg_img' => '',
    'arfsubmitbuttonmarginsetting' => '10px 10px 0px 0px',
    'arfsubmitbuttonpaddingsetting' => '8',
    'arfsubmitshadowcolorsetting' => 'c6c8cc',
    'border_radius' => '3',
    'border_radius_tablet' => '3',
    'border_radius_mobile' => '3',
    'arferroriconsetting' => 'e1.png',
    'arferrorbgsetting' => 'F3CAC7',
    'arferrorbordersetting' => 'FA8B83',
    'arferrortextsetting' => '501411',
    'arffontsizesetting' => '14',
    'arfsucessiconsetting' => 's1.png',
    'success_bg' => NULL,
    'success_border' => NULL,
    'success_text' => NULL,
    'arfsucessfontsizesetting' => '14',
    'arftextareafontsizesetting' => '13px',
    'arftextareawidthsetting' => '400',
    'arftextareawidthunitsetting' => 'px',
    'arftextareapaddingsetting' => '2',
    'arftextareamarginsetting' => '20',
    'arftextareabgcolorsetting' => 'ffffff',
    'arftextareacolorsetting' => '444444',
    'arftextareabordercolorsetting' => 'dddddd',
    'arftextareaborderwidthsetting' => '1',
    'arftextareaborderstylesetting' => 'solid',
    'text_direction' => '1',
    'arffieldheightsetting' => '24',
    'arfmainformtitlecolorsetting' => '4a494a',
    'form_title_font_size' => '28',
    'error_font' => 'Lucida Sans Unicode',
    'error_font_other' => '',
    'arfactivebgcolorsetting' => 'FFFF00',
    'arfmainformbgcolorsetting' => 'ffffff',
    'arfmainformtitleweightsetting' => 'normal',
    'arfmainformtitlepaddingsetting' => '0px 0px 20px 0px',
    'arfmainformbordershadowcolorsetting' => 'f2f2f2',
    'form_border_shadow' => 'flat',
    'arfsubmitalignsetting' => 'left',
    'checkbox_radio_style' => '1',
    'bg_color_pg_break' => '087ee2',
    'bg_inavtive_color_pg_break' => '7ec3fc',
    'text_color_pg_break' => 'ffffff',
    'text_color_pg_break_style3' => '087ee2',
    'arfmainform_bg_img' => '',
    'arfmainform_opacity' => '1',
    'arfmainfield_opacity' => '0',
    'arfsubmitfontfamily' => 'Helvetica',
    'arfmainfieldsetpadding_1' => '30',
    'arfmainfieldsetpadding_2' => '45',
    'arfmainfieldsetpadding_3' => '30',
    'arfmainfieldsetpadding_4' => '45',
    "arfmainfieldsetpadding_1_tablet"=> "",
    "arfmainfieldsetpadding_2_tablet"=> "",
    "arfmainfieldsetpadding_3_tablet"=> "",
    "arfmainfieldsetpadding_4_tablet"=> "",
    "arfmainfieldsetpadding_1_mobile"=> "",
    "arfmainfieldsetpadding_2_mobile"=> "",
    "arfmainfieldsetpadding_3_mobile"=> "",
    "arfmainfieldsetpadding_4_mobile"=> "",
    'arfmainformtitlepaddingsetting_1' => '0',
    'arfmainformtitlepaddingsetting_2' => '0',
    'arfmainformtitlepaddingsetting_3' => '20',
    'arfmainformtitlepaddingsetting_4' => '0',
    'arffieldinnermarginssetting_1' => '8',
    'arffieldinnermarginssetting_2' => '10',
    'arffieldinnermarginssetting_3' => '8',
    'arffieldinnermarginssetting_4' => '10',
    'arfsubmitbuttonmarginsetting_1' => '10',
    'arfsubmitbuttonmarginsetting_2' => '10',
    'arfsubmitbuttonmarginsetting_3' => '0',
    'arfsubmitbuttonmarginsetting_4' => '0',
    'arfcheckradiostyle' => 'flat',
    'arffieldanimationstyle' => 'no animation',
    'arfpbfieldanimationstyle' => 'slideInLeft',
    "arfpagebreakinheritanimation" => "0",
    'arfcheckradiocolor' => 'blue',
    'arf_checked_checkbox_icon' => '',
    'enable_arf_checkbox' => '0',
    'arf_checked_radio_icon' => '',
    'enable_arf_radio' => '0',
    'checked_checkbox_icon_color' => '#0C7CD5',
    'checked_radio_icon_color' => '#0C7CD5',
    'arfformtitlealign' => 'left',
    'arferrorstyle' => 'advance',
    'arferrorstylecolor' => '#ed4040|#FFFFFF|#ed4040',
    'arferrorstylecolor2' => '#ed4040|#FFFFFF|#ed4040',
    'arferrorstyleposition' => 'bottom',
    'arfsubmitautowidth' => '100',
    'arftitlefontfamily' => 'Helvetica',
    'bar_color_survey' => '#007ee4',
    'bg_color_survey' => '#dadde2',
    'text_color_survey' => '#333333',
    'prefix_suffix_bg_color' => '#e7e8ec',
    'prefix_suffix_icon_color' => '#808080',
    'arfsectionpaddingsetting_1' => '20',
    'arfsectionpaddingsetting_2' => '0',
    'arfsectionpaddingsetting_3' => '20',
    'arfsectionpaddingsetting_4' => '20',
    'arfsectionpaddingsetting' => "20px 0px 20px 20px",
    'arffieldinnermarginssetting' => '8px 10px 8px 10px',
    'arfsucessbgcolorsetting' => '#E0FDE2',
    'arfsucessbordercolorsetting' => '#BFE0C1',
    'arfsucesstextcolorsetting' => '#4C4D4E',
    'arfformerrorbgcolorsetting' => '#FDECED',
    'arfformerrorbordercolorsetting' => '#F9CFD1',
    'arfformerrortextcolorsetting' => '#ED4040',
    'arfinputstyle' => 'standard',
    'arfcheckradiostyle' => 'default',
    'arffieldanimationstyle' => 'no animation',
    'arfpbfieldanimationstyle' => 'slideInLeft',
    "arfpagebreakinheritanimation" => "0",
    'arfmainform_color_skin' => 'blue',
    'arf_tooltip_bg_color' => '#000000',
    'arf_tooltip_font_color' => '#ffffff',
    "arfcommonfont"=>"Helvetica",
    "arfmainfieldcommonsize"=>"3",
    "arfvalidationbgcolorsetting"=>"#ed4040",
    "arfvalidationtextcolorsetting"=>"#ffffff",
    "arfdatepickerbgcolorsetting"=>"#007ee4",
    "arfdatepickertextcolorsetting"=>"#000000",
    "arfsectiontitlefamily"=>"Helvetica",
    "arfsectiontitlefontsizesetting"=>"16",
    "arfsectiontitleweightsetting"=>"bold",
    "arfsubmitbuttontext"=>"Submit",
    "arfuploadbtntxtcolorsetting"=>"#FFFFFF",
    "arfuploadbtnbgcolorsetting" =>"#0C7CD5",
    "arf_req_indicator"=>"0",
    "arf_section_inherit_bg" => "1",
    "arfformsectionbackgroundcolor"=>"#ffffff",
    "arfmainbasecolor" => "#0c7cd5",
    "arflikebtncolor"=>"#4786ff",
    "arfdislikebtncolor"=>"#ec3838",
    "arfstarratingcolor"=>"#FCBB1D",
    "arfsliderselectioncolor"=>"#d1dee5",
    "arfslidertrackcolor"=>"#bcc7cd",
    "arfplaceholder_opacity" => "0.5",
    "arf_bg_position_x" => "left",
    "arf_bg_position_input_x" => "",
    "arf_bg_position_y" => "top",
    "arf_bg_position_input_y" => "",
);

$new_values1 = maybe_serialize($new_values);
$values['form_css'] = $new_values1;
$form_id = $arfliteform->arflitecreate($values);
if (!empty($new_values)) {

    $use_saved = true;

    $arfssl = (is_ssl()) ? 1 : 0;

    $filename = ARFLITE_FORMPATH . '/core/arflite_css_create_main.php';

    $wp_upload_dir = wp_upload_dir();

    $target_path = $wp_upload_dir['basedir'] . '/arforms-form-builder/maincss';

    $css = $warn = "/* WARNING: Any changes made to this file will be lost when your ARForms settings are updated */";


    $css .= "\n";


    ob_start();


    include $filename;


    $css .= ob_get_contents();


    ob_end_clean();


    $css .= "\n " . $warn;

    $css_file = $target_path . '/maincss_' . $form_id . '.css';

    WP_Filesystem();
    global $wp_filesystem;
    $css = str_replace('##', '#', $css);
    $wp_filesystem->put_contents($css_file, $css, 0777);

    wp_cache_delete($form_id, 'arfform');

    $filename1 = ARFLITE_FORMPATH . '/core/arflite_css_create_materialize.php';
    $css1 = $warn1 = "/* WARNING: Any changes made to this file will be lost when your ARForms settings are updated */";
    $css1 .= "\n";
    ob_start();
    include $filename1;
    $css1 .= ob_get_contents();
    ob_end_clean();
    $css1 .= "\n " . $warn1;
    $css_file1 = $target_path . '/maincss_materialize_' . $form_id . '.css';
    WP_Filesystem();
    $css1 = str_replace('##', '#', $css1);
    $wp_filesystem->put_contents($css_file1, $css1, 0777);
    wp_cache_delete($form_id, 'arfform');

    // $filename2 = ARFLITE_FORMPATH . '/core/css_create_materialize_outline.php';
    // $css2 = $warn2 = "/* WARNING: Any changes made to this file will be lost when your ARForms settings are updated */";
    // $css2 .= "\n";
    // ob_start();
    // // include $filename2;
    // $css2 .= ob_get_contents();
    // ob_end_clean();
    // $css2 .= "\n" . $warn2;
    // $css_file2 = $target_path . '/maincss_materialize_outlined_' . $form_id . '.css';
    // WP_Filesystem();
    // $css2 = str_replace( '##', '#', $css2 );
    // $wp_filesystem->put_contents( $css_file2, $css2, 0777 );
} else {

    $query_results = true;
}
$field_order = array();
$inner_field_order = array();

$field_values = array();
$field_values['field_options'] = $field_data_obj['text'];
$field_values['name'] = 'First Name';
$field_values['field_options']['name'] = 'First Name';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'text';
$field_values['field_options']['classes'] = '';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter first name', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('First Name', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['arf_regular_expression_msg'] = addslashes(esc_html__('Entered value is invalid', 'arforms-form-builder'));
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 1;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['text'];
$field_values['name'] = 'Last Name';
$field_values['field_options']['name'] = 'Last Name';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'text';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter last name', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Last Name', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['arf_regular_expression_msg'] = addslashes(esc_html__('Entered value is invalid', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 2;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['email'];
$field_values['name'] = 'E-mail Address';
$field_values['field_options']['name'] = 'E-mail Address';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'email';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter email address', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Email Address', 'arforms-form-builder'));
$field_values['field_options']['invalid'] = addslashes(esc_html__('Please enter a valid email address', 'arforms-form-builder'));
$field_values['field_options']['confirm_email_label'] = addslashes(esc_html__('Confirm Email Address', 'arforms-form-builder'));
$field_values['field_options']['invalid_confirm_email'] = addslashes(esc_html__('Confirm email address does not match with email', 'arforms-form-builder'));
$field_values['field_options']['confirm_email_placeholder'] = addslashes(esc_html__('Confirm Email Address', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 3;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['text'];
$field_values['name'] = 'Company Name';
$field_values['field_options']['name'] = 'Company Name';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'text';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter your comapany name', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Company Name', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['arf_regular_expression_msg'] = addslashes(esc_html__('Entered value is invalid', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 4;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['url'];
$field_values['name'] = 'Website';
$field_values['field_options']['name'] = 'Website';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'url';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter your website URL', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Website', 'arforms-form-builder'));
$field_values['field_options']['invalid'] = addslashes(esc_html__('Please enter a valid website', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 5;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['text'];
$field_values['name'] = 'Subject';
$field_values['field_options']['name'] = 'Subject';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'text';
$field_values['field_options']['blank'] = addslashes(esc_html__('Please enter subject', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Subject', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 6;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['radio'];
$field_values['name'] = 'How did you find us?';
$field_values['field_options']['name'] = 'How did you find us?';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'radio';
$field_values['field_options']['invalid'] = addslashes(esc_html__('This field cannot be blank.', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['field_options']['separate_value'] = "false";
$field_values['options'] = wp_json_encode(array('Search Engine', 'Link From Another Site', 'News Article', 'Televistion Ad', 'Word of Mouth'));
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 7;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['radio'];
$field_values['name'] = 'How often do you visit our site?';
$field_values['field_options']['name'] = 'How often do you visit our site?';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'radio';
$field_values['field_options']['invalid'] = addslashes(esc_html__('This field cannot be blank.', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['field_options']['separate_value'] = "false";
$field_values['options'] = wp_json_encode(array('Daily', 'Weekly', 'Monthly', 'Yearly'));
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 8;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['select'];
$field_values['name'] = 'Please rate the quality of our content. (10=Best 1=Worst)';
$field_values['field_options']['name'] = 'Please rate the quality of our content. (10=Best 1=Worst)';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'select';
$field_values['field_options']['invalid'] = addslashes(esc_html__('This field cannot be blank.', 'arforms-form-builder'));
$field_values['field_options']['classes'] = '';
$field_values['field_options']['separate_value'] = "false";
$field_values['options'] = wp_json_encode(array('10', '9', '8', '7', '6', '5', '4', '3', '2', '1'));
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 9;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['select'];
$field_values['name'] = 'Please rate the quality of our site design. (10=Best 1=Worst)';
$field_values['field_options']['name'] = 'Please rate the quality of our site design. (10=Best 1=Worst)';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'select';
$field_values['field_options']['classes'] = '';
$field_values['field_options']['invalid'] = addslashes(esc_html__('This field cannot be blank.', 'arforms-form-builder'));
$field_values['field_options']['separate_value'] = "false";
$field_values['options'] = wp_json_encode(array('10', '9', '8', '7', '6', '5', '4', '3', '2', '1'));
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 10;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['checkbox'];
$field_values['name'] = 'Suitable word for arforms-form-builder';
$field_values['field_options']['name'] = 'Suitable word for arforms-form-builder';
$field_values['field_options']['required'] = 1;
$field_values['type'] = 'checkbox';
$field_values['field_options']['classes'] = '';
$field_values['field_options']['invalid'] = addslashes(esc_html__('This field cannot be blank.', 'arforms-form-builder'));
$field_values['field_options']['separate_value'] = "false";
$field_values['field_options']['options'] = wp_json_encode(array('Good', 'Best', 'Difficult', 'Creative', 'Helpful', 'Unhelpful'));
$field_values['options'] = wp_json_encode(array('Good', 'Best', 'Difficult', 'Creative', 'Helpful', 'Unhelpful'));
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 11;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['textarea'];
$field_values['name'] = 'What was your favorite part of the arforms-form-builder?';
$field_values['field_options']['name'] = 'What was your favorite part of the arforms-form-builder?';
$field_values['field_options']['required'] = 0;
$field_values['type'] = 'textarea';
$field_values['field_options']['classes'] = '';
$field_values['field_options']['blank'] = addslashes(esc_html__('This field cannot be blank.', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('What was your favorite part of the arforms-form-builder?', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 12;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['textarea'];
$field_values['name'] = 'Did you experience any problems or have any suggestions?';
$field_values['field_options']['name'] = 'Did you experience any problems or have any suggestions?';
$field_values['field_options']['required'] = 0;
$field_values['type'] = 'textarea';
$field_values['field_options']['classes'] = '';
$field_values['field_options']['blank'] = addslashes(esc_html__('This field cannot be blank.', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Did you experience any problems or have any suggestions?', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder'));
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 13;
unset($field_values);
unset($field_id);

$field_values = array();
$field_values['field_options'] = $field_data_obj['textarea'];
$field_values['name'] = 'Other Comment';
$field_values['field_options']['name'] = 'Other Comment';
$field_values['type'] = 'textarea';
$field_values['field_options']['classes'] = '';
$field_values['field_options']['blank'] = addslashes(esc_html__('This field cannot be blank.', 'arforms-form-builder'));
$field_values['field_options']['placeholdertext'] = addslashes(esc_html__('Other Comment', 'arforms-form-builder'));
$field_values['field_options']['minlength_message'] = addslashes(addslashes(esc_html__('Invalid minimum characters length', 'arforms-form-builder')));
$field_values['form_id'] = $form_id;
$field_id = $arflitefield->arflitecreate($field_values, true);
$field_order[$field_id] = 14;
unset($field_values);
unset($field_id);
unset($values);

$field_options = $wpdb->get_results($wpdb->prepare("SELECT `options` FROM `" . $tbl_arf_forms . "` WHERE `id` = %d", $form_id)); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Reason: $tbl_arf_forms is table name defined globally. False Positive alarm

$form_opt = maybe_unserialize($field_options[0]->options);

$form_opt['arf_field_order'] = wp_json_encode($field_order);
$form_opt['arf_inner_field_order'] = wp_json_encode( $inner_field_order );

$form_options = maybe_serialize($form_opt);

$wpdb->update($tbl_arf_forms, array('options' => $form_options), array('id' => $form_id));

unset($field_order);
unset($inner_field_order);
