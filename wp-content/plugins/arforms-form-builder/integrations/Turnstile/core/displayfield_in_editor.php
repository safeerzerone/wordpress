<?php
if ( ARF_TC_SLUG == $field['type'] ) {
	$arf_editor_unique_id = 5656;
	?>
	<img class="arf_tccaptcha_editor_img_default" src="<?php echo esc_url( ARF_TURNSTL_CAPT_URL ); ?>/images/new_turnstile_captcha.png"/>
	<input id='field_<?php echo esc_html( $field['field_key'] ) . '_' . esc_html( $arf_editor_unique_id ); ?>' name="item_meta[<?php echo esc_html( $field['id'] ); ?>]" type='hidden' class='arf_tccaptcha_output arf_tccaptcha_float_left' value='<?php echo esc_attr( $field['default_value'] ); ?>'/>
	<?php
}
