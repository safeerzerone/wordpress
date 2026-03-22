function addhcaptchafield(id){
	var type = 'hcaptcha';
	addnewfrmfield( id, type );
}

jQuery( document ).ready(
	function () {

		wp.hooks.addAction( 'arf_add_new_fields_outside', 'ARForms_hcaptcha', arforms_hcaptcha_restriction, 1 );

		wp.hooks.addAction( 'arforms_load_bootstrap_js_css', 'ARForms_hcaptcha', arforms_hcaptcha_restriction_drag_drop, 2 );

		wp.hooks.addAction( 'arforms_remove_form_fields_outside', 'ARForms_hcaptcha', arforms_hcaptcha_revoke_restriction );

		// restrict drag drop onload
		if ( jQuery( "#new_fields" ).find( '.edit_field_type_hcaptcha' ).length > 0 ) {
			  jQuery( '.arf_form_element_item[data-type="hcaptcha"]' ).addClass( 'arflite_prevent_sorting' );
		}

		// revoke drag drop restriction onload
		if ( jQuery( "#new_fields" ).find( '.edit_field_type_hcaptcha' ).length == 0 ) {
			jQuery( '.arf_form_element_item[data-type="hcaptcha"]' ).removeClass( 'arflite_prevent_sorting' );
		}

		// hide require and duplicate field
		icon = jQuery( ".edit_field_type_hcaptcha" ).find( '.arf_field_option_icon' );

		if ( icon ) {
			for ( i = 0; i < jQuery( ".edit_field_type_hcaptcha" ).find( '.arf_field_option_input' ).length; i++ ) {

				if ( jQuery( icon[i] ).children().attr( 'title' ) == 'Required' ) {
					   jQuery( icon[i] ).hide();
				}

				if ( jQuery( icon[i] ).children().attr( 'title' ) == 'Duplicate Field' ) {
					 jQuery( icon[i] ).hide();
				}
			}
		}

		// restrict onclick
		if ( jQuery( "#new_fields" ).find( '.edit_field_type_hcaptcha' ).length > 0 ) {
			jQuery( '.arf_form_element_item[data-type="hcaptcha"]' ).on(
				'click',
				function () {
					jQuery( "#new_fields" ).find( '.edit_field_type_hcaptcha' ).off( 'click' );
				}
			);
		}

	}
);


// to restrict hcaptcha field
function arforms_hcaptcha_restriction( field_type ){

	if ( 'hcaptcha' == field_type ) {
		if ( jQuery( "#new_fields" ).find( '.edit_field_type_hcaptcha' ).length > 0 ) {
			jQuery( '.arf_form_element_item[data-type="hcaptcha"]' ).addClass( 'arflite_prevent_sorting' );
		}
	}

}


// to restrict drag drop hcaptcha field
function arforms_hcaptcha_restriction_drag_drop( field_type ){

	if ( 'hcaptcha' == field_type ) {
		if ( jQuery( "#new_fields" ).find( '.edit_field_type_hcaptcha' ).length > 0 ) {
			jQuery( '.arf_form_element_item[data-type="hcaptcha"]' ).addClass( 'arflite_prevent_sorting' );
			jQuery( '.arf_form_element_item[data-type="hcaptcha"]' ).prop( 'disabled', true );
		}
	}

}


// to revoke restriction from hcaptcha field
function arforms_hcaptcha_revoke_restriction( field_type ) {
	console.log( 'revoke' );
	if ( 'hcaptcha' == field_type ) {
		console.log( 'revoke--' );
		if ( jQuery( "#new_fields" ).find( '.edit_field_type_hcaptcha' ).length == 0 ) {
			jQuery( '.arf_form_element_item[data-type="hcaptcha"]' ).removeClass( 'arflite_prevent_sorting' );
		}
	}

}
