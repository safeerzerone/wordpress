( function ( $ ) {
	'use strict';

	var config = window.arsenalSettingsWcStripeDdCheckout || {};
	var paymentMethodIds = config.paymentMethodIds || [ 'stripe_bacs_debit' ];
	var billingData = config.billingData || {};
	var fieldDefaults = config.fieldDefaults || {
		billing_first_name: 'Customer',
		billing_last_name: 'Member',
		billing_address_1: 'N/A',
		billing_address_2: 'N/A',
		billing_city: 'N/A',
		billing_state: 'N/A',
		billing_postcode: '000000',
		billing_phone: '0000000000',
	};

	function resolveBillingValue( name, value ) {
		value = value == null ? '' : String( value ).trim();
		if ( value ) {
			return value;
		}
		if ( Object.prototype.hasOwnProperty.call( fieldDefaults, name ) && fieldDefaults[ name ] ) {
			return String( fieldDefaults[ name ] ).trim();
		}
		return '';
	}

	function savePaymentMethodFieldName( paymentMethodId ) {
		paymentMethodId = paymentMethodId || getSelectedPaymentMethod();
		if ( paymentMethodId.indexOf( 'stripe_' ) !== 0 ) {
			return '';
		}
		return 'wc-stripe_' + paymentMethodId.substring( 7 ) + '-new-payment-method';
	}

	function injectSavePaymentMethodField() {
		if ( ! config.forceSavePaymentMethod && ! isDirectDebitSelected() ) {
			return;
		}

		var fieldName = savePaymentMethodFieldName();
		if ( ! fieldName ) {
			return;
		}

		var $form = getCheckoutForm();
		var $field = $form.find( '[name="' + fieldName + '"]' ).first();
		if ( ! $field.length ) {
			$field = $( '<input type="hidden" />' )
				.attr( 'name', fieldName )
				.appendTo( $form );
		}
		$field.val( '1' );
	}

	function syncBillingFieldsToCheckout() {
		var $form = getCheckoutForm();
		if ( ! $form.length ) {
			return;
		}

		var fieldsToSync = $.extend( {}, fieldDefaults, billingData );

		$.each( fieldsToSync, function ( name ) {
			var value = resolveBillingValue( name, billingData[ name ] );
			if ( ! value ) {
				return;
			}

			var $field = $form.find( '[name="' + name + '"]' ).first();
			if ( ! $field.length ) {
				$field = $( '<input type="hidden" />' )
					.attr( 'name', name )
					.appendTo( $form );
			}

			if ( name.indexOf( 'billing_' ) === 0 ) {
				$field.attr( 'id', name );
			}

			if ( ! $.trim( $field.val() ) ) {
				$field.val( value );
			}
		} );
	}

	function getCheckoutForm() {
		return $( 'form.checkout' ).first();
	}

	function getSelectedPaymentMethod() {
		return getCheckoutForm().find( 'input[name="payment_method"]:checked' ).val() || '';
	}

	function isDirectDebitSelected() {
		return paymentMethodIds.indexOf( getSelectedPaymentMethod() ) !== -1;
	}

	function hasRequiredBillingForDirectDebit() {
		var email = resolveBillingValue( 'billing_email', billingData.billing_email || $( '#billing_email' ).val() );
		var country = resolveBillingValue( 'billing_country', billingData.billing_country || $( '#billing_country' ).val() );

		return email !== '' && country !== '';
	}

	function validateDirectDebitBilling() {
		if ( ! isDirectDebitSelected() ) {
			return true;
		}

		syncBillingFieldsToCheckout();

		if ( hasRequiredBillingForDirectDebit() ) {
			injectSavePaymentMethodField();
			return true;
		}

		if ( ! resolveBillingValue( 'billing_email', billingData.billing_email ) && config.emailRequiredMsg ) {
			window.alert( config.emailRequiredMsg );
			return false;
		}

		if ( config.addressRequiredMsg ) {
			window.alert( config.addressRequiredMsg );
		}

		return false;
	}

	syncBillingFieldsToCheckout();
	injectSavePaymentMethodField();

	$( document.body ).on( 'updated_checkout payment_method_selected', function () {
		syncBillingFieldsToCheckout();
		injectSavePaymentMethodField();
	} );

	$.each( paymentMethodIds, function ( _, paymentMethodId ) {
		$( document.body ).on( 'checkout_place_order_' + paymentMethodId, function () {
			return validateDirectDebitBilling();
		} );
	} );

	getCheckoutForm().on( 'checkout_place_order', function () {
		if ( isDirectDebitSelected() || config.forceSavePaymentMethod ) {
			syncBillingFieldsToCheckout();
			injectSavePaymentMethodField();
		}
	} );
}( jQuery ) );
