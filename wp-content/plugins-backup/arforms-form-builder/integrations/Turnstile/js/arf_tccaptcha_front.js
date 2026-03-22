"use strict";
var arf_tc_widgets = {};
function _turnstileCb() {
	setTimeout(
		function () {
			var pubkey = jQuery( "#arf_tccaptcha_public_key" ).val();
			var arf_theme = jQuery( "#arf_tc_theme" ).val();
			arf_theme = ( arf_theme != undefined || arf_theme != null ) ? arf_theme : 'light';   

			jQuery('[id^="tccaptcha_res_"]').each(function() {				
				var tc_element_id = jQuery(this).attr('id');
				arf_tc_widgets[ tc_element_id ] = turnstile.render( '#'+tc_element_id, { sitekey: pubkey, theme: arf_theme } ) ; 
			});
		},
		500
	);
}

jQuery( document ).ready(
	function () {
		wp.hooks.addAction( 'reset_field_in_outsite', 'arf-tc-reset', art_tc_reset_field_callback, 13 );
	}
);

function art_tc_reset_field_callback( object, result_data ) { 

	var form_id = jQuery( object ).closest( 'form' ).find( '[data-id="form_id"]' ).val();
	var form_data_id = jQuery( object ).closest( 'form' ).find( '[data-id="form_data_id"]' ).val();
	var arf_tc_widgetid = arf_tc_widgets[ 'tccaptcha_res_'+ form_id + '_' + form_data_id ];

	turnstile.reset( arf_tc_widgetid );
}