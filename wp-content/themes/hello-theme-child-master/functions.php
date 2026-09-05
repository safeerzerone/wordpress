<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.1'
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20 );

/**
 * Whether a country value represents the UK.
 *
 * Handles ARMember ISO codes (GB), labels (United Kingdom), and numeric IDs.
 *
 * @param mixed $country Country code, label, or ARMember numeric id.
 * @return bool
 */
function arsenal_is_uk_country( $country ) {
	if ( $country === null || $country === '' ) {
		return false;
	}

	$country = trim( wp_strip_all_tags( (string) $country ) );
	$normalized = strtoupper( $country );

	$uk_exact = array( 'GB', 'UK', 'GBR', '198', 'UNITED KINGDOM', 'UNITED KINGDOM (UK)' );
	if ( in_array( $normalized, $uk_exact, true ) ) {
		return true;
	}

	// Label variants such as "United Kingdom (UK):GB".
	if ( false !== stripos( $country, 'United Kingdom' ) ) {
		return true;
	}

	return false;
}

/**
 * Set Overseas (radio_djeo5) from the user's country.
 * UK => N (No), otherwise => Y (Yes).
 *
 * @param int   $user_id     User ID.
 * @param array $posted_data Optional posted registration/profile data.
 * @return void
 */
function arsenal_set_overseas_from_country( $user_id, $posted_data = array() ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return;
	}

	$country = '';
	if ( is_array( $posted_data ) && isset( $posted_data['country'] ) && $posted_data['country'] !== '' ) {
		$country = $posted_data['country'];
	} else {
		$country = get_user_meta( $user_id, 'country', true );
	}

	if ( $country === '' || $country === null ) {
		return;
	}

	$overseas = arsenal_is_uk_country( $country ) ? 'N' : 'Y';
	update_user_meta( $user_id, 'radio_djeo5', $overseas );
}

add_action( 'arm_after_add_new_user', 'arsenal_set_overseas_from_country', 20, 2 );
add_action( 'arm_member_update_meta', 'arsenal_set_overseas_from_country', 20, 2 );
add_action(
	'user_register',
	function( $user_id ) {
		arsenal_set_overseas_from_country( $user_id );
	},
	20
);

function add_this_script_footer(){
?>

<script>
	jQuery(document).ready(function(){
		function arsenalIsUkCountry(value) {
			if (value === undefined || value === null) {
				return false;
			}
			var country = String(value).trim();
			if (!country) {
				return false;
			}
			var normalized = country.toUpperCase();
			var ukExact = ['GB', 'UK', 'GBR', '198', 'UNITED KINGDOM', 'UNITED KINGDOM (UK)'];
			if (ukExact.indexOf(normalized) !== -1) {
				return true;
			}
			return /united kingdom/i.test(country);
		}

		function arsenalSetOverseasFromCountry(countryValue) {
			var isUk = arsenalIsUkCountry(countryValue);
			var $yes = jQuery("input[name=radio_djeo5][value='Y']");
			var $no = jQuery("input[name=radio_djeo5][value='N']");

			// Fallback if options are stored as Yes/No instead of Y/N.
			if (!$yes.length) {
				$yes = jQuery("input[name=radio_djeo5][value='Yes']");
			}
			if (!$no.length) {
				$no = jQuery("input[name=radio_djeo5][value='No']");
			}

			if (isUk) {
				$yes.prop('checked', false);
				$no.prop('checked', true).trigger('change');
			} else {
				$no.prop('checked', false);
				$yes.prop('checked', true).trigger('change');
			}
		}

		function arsenalGetCountryInput() {
			return jQuery('.country-formfield-ar input.arm-selectpicker-input-control[name="country"], input.arm-selectpicker-input-control[name="country"]').first();
		}

		// Sync Overseas whenever country changes (direct change + ARMember dropdown click).
		jQuery(document).on('change', '.country-formfield-ar input.arm-selectpicker-input-control, input.arm-selectpicker-input-control[name="country"]', function(){
			arsenalSetOverseasFromCountry(jQuery(this).val());
		});

		jQuery(document).on('click', '.country-formfield-ar .arm__dc--item, .arm-df__form-group_select .arm__dc--item', function(){
			var $group = jQuery(this).closest('.arm-df__form-group');
			var isCountryField = $group.hasClass('country-formfield-ar') || $group.find('input[name="country"]').length;
			if (!isCountryField) {
				return;
			}
			var countryValue = jQuery(this).attr('data-value');
			arsenalSetOverseasFromCountry(countryValue);
		});

		// Apply once on load in case country already has a value.
		var $countryInput = arsenalGetCountryInput();
		if ($countryInput.length && $countryInput.val()) {
			arsenalSetOverseasFromCountry($countryInput.val());
		}
		
		// Functionality for subscription plan and payment option selection
		jQuery('#annual-membership').click(function(){
			jQuery(this).html('Selected');
			jQuery(this).css({'color':'#C2001F', 'background-color': '#FFFFFF', 'border': '1px solid #C2001F'});
			
			jQuery('#lifetime-membership').html('Select');
			jQuery('#lifetime-membership').css({'color':'#FFFFFF', 'background-color': '#C2001F'});
			
			jQuery('#arm_subscription_plan_option_3 input[name="subscription_plan"]').prop('checked',false);
			jQuery('#arm_subscription_plan_option_3 input[name="subscription_plan"]').attr('checked', false);
			//console.log( jQuery('#arm_subscription_plan_option_3 input[name="subscription_plan"]') );
			
			jQuery('#annual-payment-options').show();
			jQuery('#payment-options').hide();
			jQuery('#register-form').hide();
			
			jQuery('html, body').animate({
				scrollTop: jQuery("#annual-payment-options").offset().top
			}, 1000);
		});
		
		jQuery('#lifetime-membership').click(function(){
			jQuery(this).html('Selected');
			jQuery(this).css({'color':'#C2001F', 'background-color': '#FFFFFF', 'border': '1px solid #C2001F'});
			
			jQuery('#annual-membership').html('Select');
			jQuery('#annual-membership').css({'color':'#FFFFFF', 'background-color': '#C2001F'});
			
			jQuery('#arm_subscription_plan_option_1 input[name="subscription_plan"]').prop('checked',false);
			jQuery('#arm_subscription_plan_option_2 input[name="subscription_plan"]').prop('checked',false);
			jQuery('#arm_subscription_plan_option_3 input[name="subscription_plan"]').prop('checked',true);
			
			jQuery('#arm_subscription_plan_option_1 input[name="subscription_plan"]').attr('checked',false);
			jQuery('#arm_subscription_plan_option_2 input[name="subscription_plan"]').attr('checked',false);
			jQuery('#arm_subscription_plan_option_3 input[name="subscription_plan"]').attr('checked',true);
			
			
			jQuery('#annual-payment-options').hide();
			jQuery('#payment-options').hide();
			
			jQuery('#register-form').show();
			jQuery('html, body').animate({
				scrollTop: jQuery("#register-form").offset().top
			}, 1000);
		});
		
		jQuery('#annual-payment-recurring').click(function(){
			jQuery(this).css({'color':'#C2001F', 'background-color': '#FFFFFF', 'border': '1px solid #C2001F'});
			
			jQuery('#annual-payment-single').css({'color':'#FFFFFF', 'background-color': '#C2001F'});
			
			jQuery('#arm_subscription_plan_option_1 input[name="subscription_plan"]').prop('checked',true);
			jQuery('#arm_subscription_plan_option_2 input[name="subscription_plan"]').prop('checked',false);
			jQuery('#arm_subscription_plan_option_3 input[name="subscription_plan"]').prop('checked',false);
			
			jQuery('#arm_subscription_plan_option_1 input[name="subscription_plan"]').attr('checked', true);
			jQuery('#arm_subscription_plan_option_2 input[name="subscription_plan"]').attr('checked',false);
			jQuery('#arm_subscription_plan_option_3 input[name="subscription_plan"]').attr('checked',false);
			
			jQuery('#payment-options').hide();
			/*jQuery('#register-form').hide();
			
			jQuery('#payment-options').show();
			jQuery('html, body').animate({
				scrollTop: jQuery("#payment-options").offset().top
			}, 1000);*/
			
			jQuery('#register-form').show();
			jQuery('html, body').animate({
				scrollTop: jQuery("#register-form").offset().top
			}, 1000);
		});
		
		jQuery('#annual-payment-single').click(function(){
			jQuery(this).css({'color':'#C2001F', 'background-color': '#FFFFFF', 'border': '1px solid #C2001F'});
			
			jQuery('#annual-payment-recurring').css({'color':'#FFFFFF', 'background-color': '#C2001F'});
			
			jQuery('#arm_subscription_plan_option_1 input[name="subscription_plan"]').prop('checked',false);
			jQuery('#arm_subscription_plan_option_2 input[name="subscription_plan"]').prop('checked',true);
			jQuery('#arm_subscription_plan_option_3 input[name="subscription_plan"]').prop('checked',false);
			
			jQuery('#arm_subscription_plan_option_1 input[name="subscription_plan"]').attr('checked',false);
			jQuery('#arm_subscription_plan_option_2 input[name="subscription_plan"]').attr('checked',true);
			jQuery('#arm_subscription_plan_option_3 input[name="subscription_plan"]').attr('checked',false);
			
			jQuery('#payment-options').hide();
			
			jQuery('#register-form').show();
			jQuery('html, body').animate({
				scrollTop: jQuery("#register-form").offset().top
			}, 1000);
		});
		
		jQuery('#payment-paypal').click(function(){
			jQuery(this).css({'color':'#C2001F', 'background-color': '#FFFFFF', 'border': '1px solid #C2001F'});
			
			jQuery('#payment-direct').css({'color':'#FFFFFF', 'background-color': '#C2001F'});
			
			jQuery('#arm_selected_payment_mode_auto_1_N7xCp2ZsqI').prop('checked',true);
			jQuery('#arm_selected_payment_mode_semi_auto_1_N7xCp2ZsqI').prop('checked',false);
			
			jQuery('#register-form').show();
			jQuery('html, body').animate({
				scrollTop: jQuery("#register-form").offset().top
			}, 1000);
		});
		
		jQuery('#payment-direct').click(function(){
			jQuery(this).css({'color':'#C2001F', 'background-color': '#FFFFFF', 'border': '1px solid #C2001F'});
			
			jQuery('#payment-paypal').css({'color':'#FFFFFF', 'background-color': '#C2001F'});
			
			jQuery('#arm_selected_payment_mode_auto_1_N7xCp2ZsqI').prop('checked',false);
			jQuery('#arm_selected_payment_mode_semi_auto_1_N7xCp2ZsqI').prop('checked',true);
			
			jQuery('#register-form').show();
			jQuery('html, body').animate({
				scrollTop: jQuery("#register-form").offset().top
			}, 1000);
		});

		// Checkout page specific functionality
		if (window.location.href.indexOf('/checkout/') !== -1) {
			// Function to replace "log in" with a hyperlink once the error box is present
			function replaceLoginText() {
				const errorBox = document.querySelector('.arm-df__fc--validation__wrap');
				if (errorBox && errorBox.textContent.includes('log in')) {
					errorBox.innerHTML = errorBox.innerHTML.replace(
						'log in',
						'<a href="https://wiwqral9i8.wpdns.site/login" style="color: blue; text-decoration: underline;">log in</a>'
					);
				}
			}

			const intervalId = setInterval(function() {
				replaceLoginText();

				if (document.querySelector('.arm-df__fc--validation__wrap a')) {
					clearInterval(intervalId);
				}
			}, 500);
		}

		// Edit profile page specific functionality
		if (window.location.href.indexOf('/edit_profile/') !== -1) {
			const currentMembershipContent = document.querySelector('.arm_current_membership_content');
			const transactionContent = document.querySelector('.arm_transaction_content');
			
			function insertNoMembershipMessage(container) {
				if (container && container.innerHTML.includes('You have noactive membership')) {
					container.innerHTML = container.innerHTML.replace(
						'You have noactive membership, please subscribe here',
						'You have no active membership, please subscribe <a href="https://wiwqral9i8.wpdns.site/checkout" style="color: blue; text-decoration: underline;">here</a>'
					);
				}
			}
			
			insertNoMembershipMessage(currentMembershipContent);
			insertNoMembershipMessage(transactionContent);
		}
	});
</script>

<?php 
}
add_action('wp_footer', 'add_this_script_footer');

// New webhook code to delete a user when deleted in Go High Level
add_action( 'init', function() {
    if ( isset( $_GET['wpf_action'] ) && $_GET['wpf_action'] === 'delete_user' && isset( $_GET['access_key'] ) && $_GET['access_key'] === 'd23a1e0f' && isset( $_GET['contact_id'] ) ) {
        $contact_id = sanitize_text_field( $_GET['contact_id'] );
        $user_id = wpf_get_user_id( $contact_id );
        if ( $user_id ) {
            require_once( ABSPATH . 'wp-admin/includes/user.php' );
            wp_delete_user( $user_id );
            wp_send_json_success( 'User deleted.' );
        } else {
            wp_send_json_error( 'User not found.' );
        }
    }
} );
// Webhook code to update a user in ARMember when updated in Go High Level


//js to hide renew button if subscribtion
function my_js_arm() {
	if ( is_page(470) ) {
		echo '<script type="text/javascript">
		jQuery(document).ready( function () {
			if ( jQuery(".arm_current_membership_content .arm_renew_subscription_button").text() == "Renew Subscription" ) {
				jQuery(".arm_current_membership_content .arm_renew_subscription_button").parent().css("display", "none");
			}

			/*
			var timer = setInterval(function () {
				var getnew = jQuery(".arm_success_msg ul li").text();
				if (getnew == "Your profile has been updated successfully.") {
					window.location.href = "/";
				}
			}, 1000);*/


		});
		</script>';
	}
}
// Add hook for front-end <head></head>
add_action( 'wp_head', 'my_js_arm' );

//get order product names and add to customer meta
function wpf_product_meta_fields( $fields ) {
	$fields['product_name'] = array( 'label' => 'Product Name', 'type' => 'text', 'group' => 'woocommerce' );
	$fields['payment_type'] = array( 'label' => 'Payment Type', 'type' => 'text', 'group' => 'woocommerce' );
	$fields['payment_method_for_cms'] = array( 'label' => 'Payment Method in CMS', 'type' => 'text', 'group' => 'woocommerce' );
	$fields['country_for_cms'] = array( 'label' => 'Country in CMS', 'type' => 'text', 'group' => 'woocommerce' );
	return $fields;
}

add_filter( 'wpf_meta_fields', 'wpf_product_meta_fields' );

function get_order_product_name( $customer_data, $order ) {

	foreach ( $order->get_items() as $item_id => $line_item ) {
		
		//file_put_contents("log_woo1.txt", $order->get_payment_method() );
		
		if ($order->get_payment_method() == 'cheque') {
			$customer_data['payment_method_for_cms'] = "Cheque";
		} else if ($order->get_payment_method() == 'ppcp') {
			$customer_data['payment_method_for_cms'] = "PayPal";
		} else if ($order->get_payment_method() == 'gocardless') {
			$customer_data['payment_method_for_cms'] = "GoCardless";
		}
		
		if ( $line_item->get_name() == "Annual Membership - Recurring" || $line_item->get_name() == "Annual Membership - Single Year" ) {
			$customer_data['product_name'] = "Annual";
			
			if ( $line_item->get_name() == "Annual Membership - Recurring" ) {
			$customer_data['payment_type'] = "Recurring";
			} else {
				$customer_data['payment_type'] = "Single Year";
			}
			
		}
		if ( $line_item->get_name() == "Lifetime Membership" ) {
			$customer_data['product_name'] = "Life";
		}
	}

	return $customer_data;

}
add_filter( 'wpf_woocommerce_customer_data', 'get_order_product_name', 10, 2 );


function example_sync_lifetime_value( $user_meta, $user_id ) {

		$countries = [
		"0" => "Country",
		"GB" => "United Kingdom (UK)",
		"AF" => "Afghanistan",
		"AL" => "Albania",
		"DZ" => "Algeria",
		"AD" => "Andorra",
		"AO" => "Angola",
		"AI" => "Anguilla",
		"AQ" => "Antarctica",
		"AG" => "Antigua and Barbuda",
		"AR" => "Argentina",
		"AM" => "Armenia",
		"AW" => "Aruba",
		"AU" => "Australia",
		"AT" => "Austria",
		"AZ" => "Azerbaijan",
		"BS" => "Bahamas",
		"BH" => "Bahrain",
		"BD" => "Bangladesh",
		"BB" => "Barbados",
		"BY" => "Belarus",
		"BE" => "Belgium",
		"BZ" => "Belize",
		"BJ" => "Benin",
		"BM" => "Bermuda",
		"BT" => "Bhutan",
		"BO" => "Bolivia",
		"BA" => "Bosnia and Herzegovina",
		"BW" => "Botswana",
		"BR" => "Brazil",
		"BN" => "Brunei",
		"BG" => "Bulgaria",
		"BF" => "Burkina Faso",
		"BI" => "Burundi",
		"KH" => "Cambodia",
		"CM" => "Cameroon",
		"CA" => "Canada",
		"CV" => "Cape Verde",
		"KY" => "Cayman Islands",
		"CF" => "Central African Republic",
		"TD" => "Chad",
		"CL" => "Chile",
		"CN" => "China",
		"CO" => "Colombia",
		"KM" => "Comoros",
		"CD" => "Congo (Kinshasa)",
		"CR" => "Costa Rica",
		"HR" => "Croatia",
		"CU" => "Cuba",
		"CY" => "Cyprus",
		"CZ" => "Czech Republic",
		"DK" => "Denmark",
		"DJ" => "Djibouti",
		"DM" => "Dominica",
		"DO" => "Dominican Republic",
		"EC" => "Ecuador",
		"EG" => "Egypt",
		"SV" => "El Salvador",
		"GQ" => "Equatorial Guinea",
		"ER" => "Eritrea",
		"EE" => "Estonia",
		"ET" => "Ethiopia",
		"FJ" => "Fiji",
		"FI" => "Finland",
		"FR" => "France",
		"GF" => "French Guiana",
		"PF" => "French Polynesia",
		"GA" => "Gabon",
		"GM" => "Gambia",
		"GE" => "Georgia",
		"DE" => "Germany",
		"GH" => "Ghana",
		"GI" => "Gibraltar",
		"GR" => "Greece",
		"GL" => "Greenland",
		"GD" => "Grenada",
		"GT" => "Guatemala",
		"GN" => "Guinea",
		"GW" => "Guinea-Bissau",
		"GY" => "Guyana",
		"HT" => "Haiti",
		"HN" => "Honduras",
		"HK" => "Hong Kong",
		"HU" => "Hungary",
		"IS" => "Iceland",
		"IN" => "India",
		"ID" => "Indonesia",
		"IR" => "Iran",
		"IQ" => "Iraq",
		"IL" => "Israel",
		"IT" => "Italy",
		"JM" => "Jamaica",
		"JP" => "Japan",
		"JO" => "Jordan",
		"KZ" => "Kazakhstan",
		"KE" => "Kenya",
		"KI" => "Kiribati",
		"KW" => "Kuwait",
		"KG" => "Kyrgyzstan",
		"LA" => "Laos",
		"LV" => "Latvia",
		"LB" => "Lebanon",
		"LS" => "Lesotho",
		"LR" => "Liberia",
		"LY" => "Libya",
		"LI" => "Liechtenstein",
		"LT" => "Lithuania",
		"LU" => "Luxembourg",
		"MK" => "Macedonia",
		"MG" => "Madagascar",
		"MW" => "Malawi",
		"MY" => "Malaysia",
		"MV" => "Maldives",
		"ML" => "Mali",
		"MT" => "Malta",
		"US" => "United States (US)"
	];
	
	file_put_contents("log_country0.txt", serialize($user_meta) );
	
	$country_code = $user_meta['country'];
	if ( !empty( $country_code ) ) {
		$user_meta['country_for_cms'] = $countries[$country_code];
		file_put_contents("log_country1.txt", $countries[$country_code]);
	}

	return $user_meta;

}

//add_filter( 'wpf_get_user_meta', 'example_sync_lifetime_value', 10, 2 );