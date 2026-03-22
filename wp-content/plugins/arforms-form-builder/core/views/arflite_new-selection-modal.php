<?php 
if( !defined( 'ABSPATH' ) ) exit;

$sv[1] = '<div class="arfcontactform arftemplateicondiv"></div>';
$sv[2] = '<div class="arfsubscriptionform arftemplateicondiv"></div>';
$sv[3] = '<div class="arfffedbackform arftemplateicondiv"></div>';
?>
<div id="new_form_selection_modal">
	<form method="get" name="new" id="new">
		<input type="hidden" name="arfaction" id="arfnewaction" value="new" />
		<input type="hidden" name="page" value="ARForms" />

		<input type="hidden" name="id" id="template_list_id" value="" />
	<div class="newform_modal_title_container">
		<div class="newform_modal_title"><?php echo esc_html__( 'New Form', 'arforms-form-builder' ); ?></div>
	</div>


	<div class="newform_modal_fields_start_left">

		<div class="arf_form_type_selection_container">
			<div class="arf_radio_wrapper">
				<div class="arf_custom_radio_div">
					<div class="arf_custom_radio_wrapper">
						<input type="radio" class="arf_custom_radio arf_form_type" name="arf_form_type" id="arf_form_type_blank" value="blank_form" checked="checked" />
						<svg width="18px" height="18px">
						<?php echo ARFLITE_CUSTOM_UNCHECKEDRADIO_ICON; //phpcs:ignore ?>
						<?php echo ARFLITE_CUSTOM_CHECKEDRADIO_ICON; //phpcs:ignore ?>
						</svg>
					</div>
				</div>
				<span>
					<label for="arf_form_type_blank"><?php echo esc_html__( 'Blank Form', 'arforms-form-builder' ); ?></label>
				</span>
			</div>

			<div class="arf_radio_wrapper">
	            <div class="arf_custom_radio_div">
	                <div class="arf_custom_radio_wrapper">
	                    <input type="radio" class="arf_custom_radio arf_form_type" name="arf_form_type" id="arf_form_type_template" value="template_form" />
	                    <svg width="18px" height="18px">
						<?php echo ARFLITE_CUSTOM_UNCHECKEDRADIO_ICON; //phpcs:ignore ?>
						<?php echo ARFLITE_CUSTOM_CHECKEDRADIO_ICON; //phpcs:ignore ?>
	                    </svg>
	                </div>
	            </div>
	            <span>
	                <label for="arf_form_type_template"><?php echo addslashes(esc_html__('Templates', 'arforms-form-builder')); ?></label>
	            </span>
	        </div>


			<div class="arf_radio_wrapper">
				<div class="arf_custom_radio_div">
					<div class="arf_custom_radio_wrapper">
						<input type="radio" class="arf_custom_radio arf_form_type" name="arf_form_type" id="arf_form_type_sample" value="sample_form" />
						<svg width="18px" height="18px">
						<?php echo ARFLITE_CUSTOM_UNCHECKEDRADIO_ICON; //phpcs:ignore ?>
						<?php echo ARFLITE_CUSTOM_CHECKEDRADIO_ICON; //phpcs:ignore ?>
						</svg>
					</div>
				</div>
				<span>
					<label for="arf_form_type_sample"><?php echo esc_html__( 'Sample Forms', 'arforms-form-builder' ); ?><span class="arflite_pro_version_notice">(Premium)</span></label>
				</span>
			</div>
		</div>

		<div class="arf_new_form_option_container">
			<div class="newmodal_field_title "><?php echo esc_html__( 'Form Title', 'arforms-form-builder' ); ?>&nbsp;<span class="newmodal_required newformtitle_required">*</span></div>
			<div class="newmodal_field">
				<input name="form_name" id="form_name_new" value="" class="txtmodal1" /><br />
				<div id="form_name_new_required" class="arferrmessage display-none-cls"><?php echo esc_html__( 'Please enter form title', 'arforms-form-builder' ); ?></div>
			</div>

			<div class="newmodal_field_title">
				<?php echo esc_html__( 'Form Description', 'arforms-form-builder' ); ?>
			</div>
			<div class="newmodal_field">
				<textarea name="form_desc" id="form_desc_new" class="txtmultimodal1" rows="2" ></textarea>
			</div>

			<div class="arf_theme_style_container">
				<div class="newmodal_field_title">
					<?php echo esc_html__( 'Select Theme', 'arforms-form-builder' ); ?>
				</div>
				<div class="newmodal_field">
					<?php
						global $arflitemaincontroller;

						$inputStyle = array(
							'standard'          => addslashes( esc_html__( 'Standard Style', 'arforms-form-builder' ) ),
							'rounded'           => addslashes( esc_html__( 'Rounded Style', 'arforms-form-builder' ) ),
							'material'          => addslashes( esc_html__( 'Material Style', 'arforms-form-builder' ) ),
							'material_outlined' => addslashes( esc_html__( 'Material Outlined Style', 'arforms-form-builder' ) ) . '<span class="arflite_pro_version_notice">(Premium)</span>',
						);

						$arfmainforminputstyle_options_cls = array();

						foreach ( $inputStyle as $style => $value ) {
							if ( $style == 'material_outlined' ) {
								$arfmainforminputstyle_options_cls['material_outlined'] = 'arf_restricted_control';
							}
						}

						echo $arflitemaincontroller->arflite_selectpicker_dom( 'templete_style', 'templete_style', 'arf_templete_style_dt', 'width:102.6%', 'material', array(), $inputStyle, false, $arfmainforminputstyle_options_cls, false, array(), false, array(), false, 'arf_templete_style_ul' ); //phpcs:ignore
						?>
				</div>
			</div>
			<?php if ( is_rtl() ) { ?>

			<div class="newmodal_field_title">
				<?php echo addslashes( esc_html__( 'Input Direction', 'arforms-form-builder' ) ); //phpcs:ignore ?>
			</div>
			<div class="newmodal_field">
				<?php
					$direction_opts = array(
						'no'  => addslashes( esc_html__( 'Left to Right', 'arforms-form-builder' ) ),
						'yes' => addslashes( esc_html__( 'Right to Left', 'arforms-form-builder' ) ),
					);

					echo $arflitemaincontroller->arflite_selectpicker_dom( 'arf_rtl_switch_mode', 'arf_load_form_rtl_switch', 'arf_templete_style_dt', 'width:102.6%', 'yes', array(), $direction_opts ); //phpcs:ignore
					?>
			</div>
			<?php } ?>
		</div>

		<!-- template start -->

		<div class="newmodal_field arfdefaulttemplate" style="display:none;margin-top: 20px;<?php echo (is_rtl()) ? 'float: right;' : 'float: left;';?>">
        
			<div class="newmodal_field_title" style="text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;margin:10px 0;">
				<div>
					<?php echo addslashes(esc_html__('Select Template','arforms-form-builder'));?>&nbsp;<span class="newmodal_required" style="color:#ff0000; vertical-align:top;">*</span>	
				</div>
				<p class="newmodal_default_template_required_error"><?php esc_html_e('Please select any Template.', 'arforms-form-builder'); ?></p>
			</div>

			<?php 
				global $arfdefaulttemplate;
				if( $arfdefaulttemplate )
				{
					$ti = 1;
					foreach($arfdefaulttemplate as $template_id => $template_name)
					{
						?>
						<div id="arftemplate_<?php echo $template_id ?>" onclick="arflite_selectform('<?php echo $template_id ?>','<?php echo $template_name['theme'] ?>','<?php echo $template_name['name'] ?>');" class="arf_modalform_box" <?php if($ti <= 3){ ?>style="margin-bottom:5px;"<?php } ?>>
						<div class="arf_formbox_hover"></div>
						<?php echo $sv[$template_id];?>		
						<div class="arf_modalform_boxtitle"><?php echo $template_name['name'];?></div>  
						</div>
						<?php
							$ti++;
					}
				}
			?> 
		
		</div>
		<!-- template end -->

		<div class="arf_sample_template_container">
			 <div class="newmodal_field_title">

				<div>
					<?php echo esc_html__( 'Select Sample', 'arforms-form-builder' ); ?>&nbsp;<span class="newmodal_required newformtitle_required">*</span>
				 </div>
				<p class="newmodal_sample_template_required_error"><?php esc_html_e( 'Please select any Sample.', 'arforms-form-builder' ); ?></p>
			</div>
			<input type="hidden" class="arf_sample_form_id" name="arf_sample_form_id" value="">
			<?php
				global $arflitesamplecontroller;
				$load_list_into_new_form_popup = true;
				$sample_lists                  = $arflitesamplecontroller->arflite_samples_list( $load_list_into_new_form_popup );
			?>
		</div>

	</div>
	<div class="arflite-clear-float"></div>


	<div id="arfcontinuebtn" >
		<button type="button" class="rounded_button arf_btn_dark_blue" id="submit_new_form" onclick="arflite_submit_form_type();"><?php echo esc_html__( 'Continue', 'arforms-form-builder' ); ?></button>
		<button type="button" class="rounded_button arfnewmodalclose"><?php echo esc_html__( 'Cancel', 'arforms-form-builder' ); ?></button>
	</div>
	<div class="arf_sample_form_loader_wrapper">
		<div class="arf_loader_icon_wrapper" id="arf_sample_form_loader display-none-cls"><div class="arf_loader_icon_box"><div class="arf-spinner arf-skeleton arf-grid-loader"></div></div></div>
	</div>
	</form>
</div>
