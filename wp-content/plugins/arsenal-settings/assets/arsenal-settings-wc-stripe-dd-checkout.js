( function ( $ ) {
	'use strict';

	var config = window.arsenalSettingsWcStripeDdCheckout || {};
	var paymentMethodIds = config.paymentMethodIds || [ 'stripe_bacs_debit' ];
	var sessionEmail = $.trim( config.customerEmail || '' );

	function getCheckoutForm() {
		return $( 'form.checkout' ).first();
	}

	function getSelectedPaymentMethod() {
		return getCheckoutForm().find( 'input[name="payment_method"]:checked' ).val() || '';
	}

	function isDirectDebitSelected() {
		return paymentMethodIds.indexOf( getSelectedPaymentMethod() ) !== -1;
	}

	/**
	 * WooCommerce Stripe UPE reads #billing_email when creating the payment method.
	 */
	function ensureStripeBillingEmailField() {
		var email = sessionEmail;
		var $form = getCheckoutForm();

		if ( ! email || ! $form.length ) {
			return email;
		}

		var $emailField = $form.find( '#billing_email, input[name="billing_email"]' ).first();
		if ( $emailField.length ) {
			$emailField.attr( 'id', 'billing_email' );
			if ( ! $.trim( $emailField.val() ) ) {
				$emailField.val( email );
			}
			return $.trim( $emailField.val() );
		}

		$( '<input type="hidden" id="billing_email" name="billing_email" autocomplete="email" />' )
			.val( email )
			.appendTo( $form );

		return email;
	}

	function validateCustomerEmail() {
		if ( ! isDirectDebitSelected() ) {
			return true;
		}
		return !!ensureStripeBillingEmailField();
	}

	function syncSessionEmailToCheckout() {
		if ( sessionEmail ) {
			ensureStripeBillingEmailField();
		}
	}

	syncSessionEmailToCheckout();

	$( document.body ).on( 'updated_checkout payment_method_selected', syncSessionEmailToCheckout );

	$.each( paymentMethodIds, function ( _, paymentMethodId ) {
		$( document.body ).on( 'checkout_place_order_' + paymentMethodId, function () {
			if ( ! validateCustomerEmail() ) {
				if ( config.emailRequiredMsg ) {
					window.alert( config.emailRequiredMsg );
				}
				return false;
			}
			return true;
		} );
	} );

	getCheckoutForm().on( 'checkout_place_order', function () {
		if ( isDirectDebitSelected() ) {
			syncSessionEmailToCheckout();
		}
	} );
}( jQuery ) );
