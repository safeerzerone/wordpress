function addtccaptchafield(id){
	var type = 'tccaptcha';
	addnewfrmfield( id, type );
}

jQuery( document ).ready(
	function () {
		wp.hooks.addAction( 'arf_add_new_fields_outside', 'arforms-turnstile-captcha', arforms_tc_captcha_restriction, 1 );
		wp.hooks.addAction( 'arforms_remove_form_fields_outside', 'arforms-turnstile-captcha', arforms_tc_captcha_revoke_restriction );
		wp.hooks.addAction( 'arforms_load_bootstrap_js_css', 'arforms-turnstile-captcha', arforms_tc_captcha_restriction_drag_drop, 2 );


		if ( jQuery( "#new_fields" ).find( '.edit_field_type_tccaptcha' ).length >= 1 ) {
			jQuery( '.arf_form_element_item[data-type="tccaptcha"]' ).addClass( 'arflite_prevent_sorting' );
		}	
	}
);

function arforms_tc_captcha_restriction_drag_drop( field_type, field_id ){
	console.log( arguments );
	if ( 'tccaptcha' == field_type ) {
		if ( jQuery( "#new_fields" ).find( '.edit_field_type_tccaptcha' ).length >= 1 ) {
			jQuery( '.arf_form_element_item[data-type="tccaptcha"]' ).addClass( 'arflite_prevent_sorting' );
		}
	}
}

function arforms_tc_captcha_restriction( field_type ){
	console.log( field_type );
	if ( 'tccaptcha' == field_type ) {
		if ( jQuery( "#new_fields" ).find( '.edit_field_type_tccaptcha' ).length >= 1 ) {
			jQuery( '.arf_form_element_item[data-type="tccaptcha"]' ).addClass( 'arflite_prevent_sorting' );
		}
	}

}

function arforms_tc_captcha_revoke_restriction( field_type ){

	if ( 'tccaptcha' == field_type ) {
		if ( jQuery( "#new_fields" ).find( '.edit_field_type_tccaptcha' ).length == 0 ) {
			jQuery( '.arf_form_element_item[data-type="tccaptcha"]' ).removeClass( 'arflite_prevent_sorting' );
		}
	}

}