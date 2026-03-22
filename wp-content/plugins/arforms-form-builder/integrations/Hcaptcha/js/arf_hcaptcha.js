var arf_hcaptcha_widgets = {};

var render_arf_hcaptcha = function () {

	setTimeout(
		function () {
			var pubkey = jQuery( "#arf_hcaptcha_public_key" ).val();
			var arf_theme = jQuery( "#arf_hcaptcha_theme" ).val();
			arf_theme = ( arf_theme != undefined || arf_theme != null ) ? arf_theme : 'light';

			jQuery( '[id^="hcaptcha_front_image_"]' ).each(
				function () {

					var hcaptcha_element_id = jQuery( this ).attr( 'id' );
					// console.log( hcaptcha_element_id  );

					arf_hcaptcha_widgets[ hcaptcha_element_id ] = hcaptcha.render( hcaptcha_element_id , { sitekey: pubkey, theme: arf_theme } );

				}
			);

		},
		500,
	);
}


var form_id = jQuery( '[data-id="form_id"]' ).val();
var form_key = jQuery( "[data-id='form_key_" + form_id + "']" ).val();

var is_formreset = jQuery( 'input[name="arf_is_resetform_aftersubmit_' + form_id + '"]').val();

if( is_formreset == 1 ){
	jQuery("form[data-form-id='form_" + form_key + "']").trigger("submit");
	wp.hooks.addAction( 'reset_field_in_outsite', 'ARForms_hcaptcha', arf_hcaptcha_reset_field );
}


function arf_hcaptcha_reset_field( object, result_data ) {

	var form_id = jQuery( object ).closest( 'form' ).find( '[data-id="form_id"]' ).val();
	var form_data_id = jQuery( object ).closest( 'form' ).find( '[data-id="form_data_id"]' ).val();
	var hcaptcha_widget_id = arf_hcaptcha_widgets[ 'hcaptcha_front_image_' + form_id + '_' + form_data_id ];
	hcaptcha.reset( hcaptcha_widget_id );
	hcaptcha.reset( hcaptcha_widget_id );
}