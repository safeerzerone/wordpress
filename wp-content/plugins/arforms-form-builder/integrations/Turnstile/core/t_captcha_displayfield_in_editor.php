<?php
global $arfsettings;
if ( ARF_TC_SLUG == $field['type'] ) {
	$arf_editor_unique_id = 5656;
	?>
	<img class="arf_signature_edior_img" src="<?php echo esc_url( ARF_TURNSTL_CAPT_URL ); ?>/images/new_turnstile_captcha.png" style='width:230px;height:58px;' />
	<input id='field_<?php echo esc_html( $field['field_key'] ) . '_' . esc_html( $arf_editor_unique_id ); ?>' name="item_meta[<?php echo esc_html( $field['id'] ); ?>]" type='hidden' class='arf_signature_output arf_dig_sig_float_left' value="<?php echo esc_attr( $field['default_value'] ); ?>">
	<?php
} ?>
