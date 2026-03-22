<?php
global $arflitefield,$wpdb, $arf_paypal, $arfliteform, $arflitemainhelper, $arfliteformcontroller, $arf_version,$arflitemaincontroller;
$field_array = array();

if ( isset( $_REQUEST['arfaction'] ) && 'edit' == $_REQUEST['arfaction'] ) {
		$form_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $arf_paypal->db_paypal_forms . ' WHERE id = %d', $_REQUEST['id'] ) );//phpcs:ignore
	
	if ( count( $form_data ) > 0 ) {
		$form_data = $form_data[0];

		$options = maybe_unserialize( $form_data->options );

		$values = array();

		$values['id']        = $form_data->id;
		$values['form_id']   = $form_data->form_id;
		$values['form_name'] = $form_data->form_name;

		if ( count( $options ) > 0 ) {
			foreach ( $options as $option_key => $option_val ) {
				$values[ $option_key ] = $option_val;
			}
		}

		$form_data_new = $arfliteform->arflitegetOne( $form_data->form_id );

		$form_options = maybe_unserialize( $form_data_new->options );

		$values['success_action'] = $form_options['success_action'];

		$values['success_msg'] = $form_options['success_msg'];

		$values['success_url'] = $form_options['success_url'];

		$values['success_page_id'] = $form_options['success_page_id'];

		$arfaction = 'edit';
	}
} else {
	$values['id']                 = '';
	$values['form_id']            = '';
	$values['form_name']          = '';
	$values['merchant_email']     = '';
	$values['paypal_mode']        = '1';
	$values['continue_label']     = '';
	$values['cancel_url']         = '';
	$values['title']              = '';
	$values['currency']           = '';
	$values['notification']       = 0;
	$values['user_notification']  = 0;
	$values['user_email_content'] = $this->user_defalut_email_content();
	$values['first_name']         = '';
	$values['last_name']          = '';
	$values['email']              = '';
	$values['address']            = '';
	$values['address_2']          = '';
	$values['city']               = '';
	$values['state']              = '';
	$values['zip']                = '';
	$values['country']            = '';
	$values['amount']             = '';
	$values['payment_type']       = 'product_service';
	$values['email_content']      = $this->defalut_email_content();
	$values['success_action']     = 'message';
	$values['success_msg']        = esc_html__( 'Thank you for subscription with us. We will contact you soon.', 'arforms-form-builder' );
	$values['success_url']        = '';
	$values['success_page_id']    = '';
	$values['shipping_info']      = '0';
	$values['paypal_condition']   = 0;

	$values['arf_payment_type']                            = '';
	
	$values['arf_multiple_product_service_type']           = '';
	$values['arf_multiple_donations_service_type']		   = '';
	$values['arf_multiple_subscription_type']              = '';

	$values['arf_cl_field_multiple_product_service_type']  = '';
	$values['arf_cl_op_multiple_product_service_type']     = '';
	$values['cl_rule_value_multiple_product_service_type'] = '';
	$values['arf_multiple_product_service_amount']         = '';

	$values['arf_cl_field_multiple_donations_service_type']  = '';
	$values['arf_cl_op_multiple_donations_service_type']     = '';
	$values['cl_rule_value_multiple_donations_service_type'] = '';
	$values['arf_multiple_donations_service_amount']         = '';
	
	$values['arf_cl_field_multiple_subscription_type']     = '';
	$values['arf_cl_op_multiple_subscription_type']        = '';
	$values['cl_rule_value_multiple_subscription_type']    = '';
	$values['arf_multiple_subscription_amount']            = '';

	$values['paypal_recurring_type'] 					   = 'M';
	$values['paypal_recurring_time'] 					   = 'infinite';

	$values['arf_paypal_trial_period']					   = '0';
	$values['paypal_trial_amount']					   	   = '0';
	$values['arf_paypal_trial_days']					   = '1';
	$values['arf_paypal_trial_months']					   = '1';
	$values['arf_paypal_trial_years']					   = '1';

	$values['paypal_trial_recurring_type']                 = 'M';
	

	$values['multiple_subscription_conditional_logic']['rules'] = array();
	$values['paypal_recurring_retry']                           = '0';
	$values['paypal_trial_period']                              = '';

	$values['conditional_logic'] = array(
		'if_cond' => 'all',
		'rules'   => array(),
	);

	$arfaction = 'new';
}

if ( isset( $values['form_id'] ) && $values['form_id'] != '' ) {
	$field_array = $arflitefield->arflitegetAll( 'fi.form_id=' . (int) $values['form_id'], 'id' );
}
$responder_list_option = $def_val = $selected_list_id = $selected_list_label = '';
?>

<div id="success_message" class="arf_success_message">
	<div class="message_descripiton">
		<div id="form_success_message_desc"></div>
		<div class="message_svg_icon">
			<svg>
				<path fill-rule="evenodd" clip-rule="evenodd" fill="#FFFFFF" d="M6.075,14.407l-5.852-5.84l1.616-1.613l4.394,4.385L17.181,0.411 l1.616,1.613L6.392,14.407H6.075z"></path>
			</svg>
		</div>
	</div>
</div>

<div id="error_message" class="arf_error_message">
	<div class="message_descripiton">
		<div id="form_error_message_des"></div>
		<div class="message_svg_icon">
			<svg >
				<path fill-rule="evenodd" clip-rule="evenodd" fill="#ffffff" d="M10.702,10.909L6.453,6.66l-4.249,4.249L1.143,9.848l4.249-4.249L1.154, 1.361l1.062-1.061l4.237,4.237l4.238-4.237l1.061,1.061L7.513,5.599l4.249,4.249L10.702,10.909z"></path>
			</svg>
		</div>
	</div>
</div>

<div class="wrap paypal_page"> 

	<div class="top_bar">
		<span class="h2"><?php esc_html_e( 'PayPal Configuration', 'arforms-form-builder' ); ?></span>
	</div>

	<div id="poststuff" class="metabox-holder">
		<div id="post-body">
			<div class="inside">
				<div class="arf_clear_both"></div>
				<div class="frm_settings_form arf_paypal_edit_settings wrap_content">
					<div class="arf_clear_both"></div>
					<?php

					if ( ! isset( $arfaction ) || $arfaction == '' ) {
						$arfaction = sanitize_text_field($_REQUEST['arfaction']);
					}

					if ( isset( $values['form_id'] ) and $values['form_id'] != '' ) {
						$values['form_name'] = $field_array[0]->form_name;
					}

				
					if ( ! isset( $values['success_msg'] ) or empty( $values['success_msg'] ) ) {
						$values['success_msg'] = esc_html__( 'Thank you for subscription with us. We will contact you soon.', 'arforms-form-builder' );
					}

					if ( ! isset( $values['success_action'] ) or empty( $values['success_action'] ) ) {
						$values['success_action'] = 'message';
					}

					if ( ! isset( $values['email_content'] ) or empty( $values['email_content'] ) ) {
						$values['email_content'] = $this->defalut_email_content();
					};

					$paypal_nonce = wp_create_nonce( 'arforms_paypal_nonce' );
					?>
					<input type="hidden" value='<?php echo esc_html( $paypal_nonce ); ?>' id="_wpnonce_paypal" />
					<input type="hidden" value="<?php echo esc_url(admin_url( 'admin-ajax.php' )); ?>" id="ajax_url" />
					<form name="arf_paypal_setting" id="arf_paypal_setting" method="post" onSubmit="return arf_paypal_save();" enctype="multipart/form-data">
						<input type="hidden" id="arfaction" name="arfaction" value="<?php echo esc_html( $arfaction ); ?>">
						<input type="hidden" name="form_id" id="form_id" value="<?php echo esc_html( $values['form_id'] ); ?>">
						<input type="hidden" name="id" id="id" value="<?php echo esc_html( $values['id'] ); ?>"> 
						<input type="hidden" name="form_name" id="form_name" value="<?php echo $values['form_name']; ?>">                          

						<table class="form-table">
							<tr class="tdclass">
								<td valign="top" class="tdclass arf_pp_valign_top">
									<label class="lblsubtitle">
										<?php
										esc_html_e( 'Select Form', 'arforms-form-builder' );
										if ( $arfaction == 'new' ) {
											?>
											&nbsp;&nbsp;<span class="arfglobalrequiredfield arf_pp_valign_top">*</span>
										<?php } ?>
									</label>
								</td>
								<td id="arf_paypal_form_name">
									<?php
									if ( $arfaction == 'new' ) {
										global $arfform, $arf_paypal, $arflitemaincontroller;

										$exclude_ids = array( '-1' );
										
											$form_data   = $wpdb->get_results( 'SELECT * FROM ' . $arf_paypal->db_paypal_forms );//phpcs:ignore
									
										if ( count( $form_data ) > 0 ) {
											foreach ( $form_data as $form_new ) {
												$exclude_ids[] = $form_new->form_id;
											}
										}

										$where = "is_template=0 AND (status is NULL OR status = '' OR status = 'published') AND id not in (" . implode( ',', $exclude_ids ) . ')';

										$return_results = array();
										$return_results = $wpdb->get_results("SELECT id,name,form_key,is_template,status FROM " . $wpdb->prefix . "arf_forms WHERE ".$where. "ORDER BY name"); ?>
										
									<div class="arf_form_dropdown">
										<div class="sltstandard">
											<input name="arf_paypal_form" id="arf_paypal_form" value="<?php echo esc_html( $selected_list_id ); ?>" type="hidden" onChange="arf_paypal_form_change();" />
											<?php
												$selected_list_id = '';
												$selected_list_label = esc_html__( 'Select Form', 'arforms-form-builder' );

												$list_of_form = array(
													'' => esc_html__('Select Form','arforms-form-builder'),
												);

												foreach ( $return_results as $form ) {

													if ( isset( $values['form_id'] ) && $values['form_id'] == $form->id ) {
														$selected_list_id    = $form->id;
														$selected_list_label = $form->name."(".$form->id.")";
													}

													$list_of_form[$form->id] = $form->name."(".$form->id.")";
													
												}
												$arf_paypal_arforms_form_change_attr = array(
															'onchange' => 'arf_paypal_form_change',
															);

												echo $arflitemaincontroller->arflite_selectpicker_dom( 'arf_paypal_form', 'arf_paypal_form', 'arf_pp_select_box', 'width:100%','', $arf_paypal_arforms_form_change_attr, $list_of_form, false, array(), false, array(), false, array(), true); //phpcs:ignore
											?>
										</div>
									</div>
									<div class="arferrmessage arf_pp_error_msg" id="arf_paypal_form_msg">
										<?php esc_html_e( 'This field cannot be blank.', 'arforms-form-builder' ); ?>
									</div>
									<?php } else { ?>
										<label class="lblsubtitle arf_pp_selected_form">
											<strong><?php echo stripslashes( $values['form_name'] ); ?></strong>
										</label>
										<input type="hidden" name="arf_paypal_form" id="arf_paypal_form" value="<?php echo esc_html( $values['form_id'] ); ?>"  />
										<?php
									}
									if ( $arfaction == 'new' ) {
										?>
										<label class="lblsubtitle arf_pp_new_form_note arf_label">
											(<?php echo esc_attr(stripslashes( esc_html__( 'Please choose your form to be configured with paypal.', 'arforms-form-builder' )) ); ?>)
										</label>
									<?php } ?>
								</td>
							</tr>
							<tr class="tdclass">
								<td valign="top" colspan="2" class="lbltitle titleclass">
									<?php esc_html_e( 'PayPal Settings', 'arforms-form-builder' ); ?>
								</td>
							</tr>

							<tr class="tdclass">
								<td width="18%" class="tdclass" valign="top">
									<label class="lblsubtitle">
										<?php esc_html_e( 'Merchant Email', 'arforms-form-builder' ); ?>&nbsp;&nbsp;<span class="arfglobalrequiredfield arf_pp_valign_top">*</span>
									</label>
								</td>
								<td>
									<input type="text" name="arf_paypal_email" id="arf_paypal_email" value="<?php echo  $values['merchant_email'] ; ?>" class="txtmodal1 arf_pp_setting_input_box" />
									<div class="arferrmessage arf_pp_error_msg" id="arf_paypal_email_msg">
										<?php esc_html_e( 'This field cannot be blank.', 'arforms-form-builder' ); ?>
									</div>
								</td>
							</tr>
							<tr class="tdclass">
								<td valign="top" class="tdclass">
									<label class="lblsubtitle"><?php esc_html_e( 'PayPal Mode', 'arforms-form-builder' ); ?></label>
								</td>
								<td class="paypal_fields_td">
									<div class="arf_radio_wrapper">
										<div class="arf_custom_radio_div">
											<div class="arf_custom_radio_wrapper">
												<input type="radio" class="" id="arf_paypal_production" name="arf_paypal_mode" value="1" <?php checked( $values['paypal_mode'], 1 ); ?> />
												<svg width="18px" height="18px">
													<?php echo ARFLITE_CUSTOM_UNCHECKEDRADIO_ICON . ARFLITE_CUSTOM_CHECKEDRADIO_ICON; //phpcs:ignore?>
												</svg>
											</div>
										</div>
										<span>
											<label for="arf_paypal_production"><?php esc_html_e( 'Production', 'arforms-form-builder' ); ?></label>
										</span>
									</div>
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<div class="arf_radio_wrapper">
										<div class="arf_custom_radio_div">
											<div class="arf_custom_radio_wrapper">
												<input type="radio" class="" id="arf_paypal_test" name="arf_paypal_mode" value="0" <?php checked( $values['paypal_mode'], 0 ); ?> />
												<svg width="18px" height="18px">
													<?php echo ARFLITE_CUSTOM_UNCHECKEDRADIO_ICON . ARFLITE_CUSTOM_CHECKEDRADIO_ICON; //phpcs:ignore?>
												</svg>
											</div>
										</div>
										<span>
											<label for="arf_paypal_test"><?php esc_html_e( 'Test Mode', 'arforms-form-builder' ); ?></label>
										</span>
									</div>
								</td>
							</tr>

							<tr class="tdclass">
								<td width="18%" valign="top" class="tdclass">
									<label class="lblsubtitle">
										<?php esc_html_e( 'Title', 'arforms-form-builder' ); ?>&nbsp;&nbsp;<span class="arfglobalrequiredfield arf_pp_valign_middle">*</span>
									</label>
								</td>
								<td>
									<input type="text" name="arf_paypal_title" id="arf_paypal_title" value="<?php echo esc_attr( $values['title'] ); ?>" class="txtmodal1 arf_pp_setting_input_box" />
									<div class="arferrmessage arf_pp_error_msg" id="arf_paypal_title_msg">
										<?php esc_html_e( 'This field cannot be blank.', 'arforms-form-builder' ); ?>
									</div>
								</td>
							</tr>
							<tr class="tdclass">
								<td width="18%" valign="top" class="tdclass">
									<label class="lblsubtitle">
										<?php esc_html_e( 'Currency', 'arforms-form-builder' ); ?>&nbsp;&nbsp;<span class="arfglobalrequiredfield arf_pp_valign_middle">*</span>
									</label>
								</td>
								<td class="paypal_fields_td">
									<div class="arf_form_dropdown">	
										<div class="sltstandard">
												<?php
												$selected_list_id      = 'USD';
												$selected_list_label   = esc_html__( 'Select Currency', 'arforms-form-builder' );
												$currency_list         = $arf_paypal->currency_symbol();
												foreach ( $currency_list as $currency => $currency_symbl ) {

													if ( $values['currency'] == $currency ) {
														$selected_list_id    = $currency;
														$selected_list_label = $currency_symbl;
													}
												}
												?>
												<input name="arf_paypal_currency" id="arf_paypal_currency" class="arf_paypal_currency" value="<?php echo esc_attr( $selected_list_id ); ?>" type="hidden" />
												<?php
												echo $arflitemaincontroller->arflite_selectpicker_dom( 'arf_paypal_currency', 'arf_paypal_currency', 'arf_pp_small_dd_field', 'width:100%', $selected_list_id, array(), $currency_list, false, array(), false, array(), false, array(), true);
												?>
												
										</div>
									<div class="arferrmessage arf_pp_error_msg" id="arf_paypal_currency_msg">
										<?php esc_html_e( 'This field cannot be blank.', 'arforms-form-builder' ); ?>
									</div>
								</td>
							</tr>

							<tr class="tdclass">
								<td valign="top" class="tdclass">
									<label class="lblsubtitle" for="shipping_info"><?php esc_html_e( 'Collect shipping info', 'arforms-form-builder' ); ?></label>
								</td>
								<td class="paypal_fields_td">
									<label class="arf_js_switch_label">
										<span><?php esc_html_e( 'No', 'arforms-form-builder' ); ?>&nbsp;</span>
									</label>
									<span class="arf_js_switch_wrapper">
										<input type="checkbox" class="js-switch" name="shipping_info" onchange="is_shipping_info();" id="shipping_info" class="" value="1" <?php checked( $values['shipping_info'], 1 ); ?> />
										<span class="arf_js_switch"></span>
									</span>
									<label class="arf_js_switch_label">
										<span>&nbsp;<?php esc_html_e( 'Yes', 'arforms-form-builder' ); ?></span>
									</label>
								</td>
							</tr>

							<tr class="tdclass 
							<?php
							if ( $values['shipping_info'] != 1 ) {
								echo ' arf_pp_display_none '; }
							?>
							" id="paypal_shipping_fields">
								<td class="tdclass arf_pp_valign_top" valign="top">
									<label class="lblsubtitle"><?php esc_html_e( 'Customer', 'arforms-form-builder' ); ?></label>
								</td>
								<td>
									<table border="0" class="form-table arf_pp_form_table">
										<tr>
											<td>
												<strong><?php esc_html_e( 'PayPal Fields', 'arforms-form-builder' ); ?></strong>
											</td>
											<td>
												<strong><?php esc_html_e( 'Form Fields', 'arforms-form-builder' ); ?></strong>
											</td>
										</tr>
										<tr>
											<td valign="top" class="paypal_fields_td">
												<?php esc_html_e( 'First Name', 'arforms-form-builder' ); ?>
											</td>
											<td class="paypal_fields_td">
												<?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_first_name', 'arf_paypal_fields', $values['first_name'], $field_array );//phpcs:ignore ?>
											</td>
										</tr>
										<tr>
											<td valign="top" class="paypal_fields_td"><?php esc_html_e( 'Last Name', 'arforms-form-builder' ); ?></td>
											<td class="paypal_fields_td">
												<?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_last_name', 'arf_paypal_fields', $values['last_name'], $field_array ); //phpcs:ignore?>
											</td>
										</tr>
										<tr>
											<td valign="top" class="paypal_fields_td"><?php esc_html_e( 'Email', 'arforms-form-builder' ); ?></td>
											<td class="paypal_fields_td"><?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_email', 'arf_paypal_fields', $values['email'], $field_array );//phpcs:ignore ?></td>
										</tr>
										<tr>
											<td valign="top" class="paypal_fields_td"><?php esc_html_e( 'Address', 'arforms-form-builder' ); ?></td>
											<td class="paypal_fields_td"><?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_address', 'arf_paypal_fields', $values['address'], $field_array );//phpcs:ignore ?></td>
										</tr>
										<tr><td valign="top" class="paypal_fields_td"><?php esc_html_e( 'Address 2', 'arforms-form-builder' ); ?></td><td class="paypal_fields_td"><?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_address_2', 'arf_paypal_fields', $values['address_2'], $field_array ); //phpcs:ignore?></td></tr>
										<tr><td valign="top" class="paypal_fields_td"><?php esc_html_e( 'City', 'arforms-form-builder' ); ?></td><td class="paypal_fields_td"><?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_city', 'arf_paypal_fields', $values['city'], $field_array ); //phpcs:ignore?></td></tr>
										<tr><td valign="top" class="paypal_fields_td"><?php esc_html_e( 'State', 'arforms-form-builder' ); ?></td><td class="paypal_fields_td"><?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_state', 'arf_paypal_fields', $values['state'], $field_array );//phpcs:ignore ?></td></tr>
										<tr><td valign="top" class="paypal_fields_td"><?php esc_html_e( 'Zip', 'arforms-form-builder' ); ?></td><td class="paypal_fields_td"><?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_zip', 'arf_paypal_fields', $values['zip'], $field_array ); //phpcs:ignore?></td></tr>
										<tr><td valign="top" class="paypal_fields_td"><?php esc_html_e( 'Country', 'arforms-form-builder' ); ?></td><td class="paypal_fields_td"><?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_country', 'arf_paypal_fields', $values['country'], $field_array );//phpcs:ignore ?></td></tr>
										<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
									</table>    
								</td>
							</tr>


							<tr class="tdclass">
								<td valign="top" colspan="2" class="lbltitle titleclass"><br /><?php esc_html_e( 'Payment Type', 'arforms-form-builder' ); ?></td>
							</tr>
							
							<tr class="tdclass arfpaymenttypesingledata">
								<td width="18%" valign="top" class="tdclass arf_pp_valign_top">
									<label class="lblsubtitle arf_pp_type_label"><?php esc_html_e( 'Payment Type', 'arforms-form-builder' ); ?></label>
								</td>
								<td>
									<div class="arf_form_dropdown">
										<div class="sltstandard">
											<?php
											$selected_list_id    = 'product_service';
											$selected_list_label = array(
												'product_service'=>esc_html__( 'Product / Service', 'arforms-form-builder' ),
											);
											if ( $values['payment_type'] == 'subscription' ) {
												$selected_list_id    = 'subscription';
												$selected_list_label = esc_html__( 'Subscription', 'arforms-form-builder' );
											} elseif ( $values['payment_type'] == 'donation' ) {
												$selected_list_id    = 'donation';
												$selected_list_label = array(
													'donation'=>esc_html__( 'Donations', 'arforms-form-builder' ),
												);
											}
											?>

											<input name="arf_paypal_payment_type" id="arf_paypal_payment_type" class="arf_paypal_payment_type" value="<?php echo esc_attr( $selected_list_id ); ?>" type="hidden"  onChange="arf_paypal_payment_type_change();" />
											<?php	
											
												$arf_paypal_arforms_payment_type_change_attr = array(
													'onchange' => 'arf_paypal_payment_type_change',
													);
												$selected_list_label = array(
													'product_service' => esc_html__( 'Product / Service', 'arforms-form-builder' ),
													'donation' =>  esc_html__( 'Donations', 'arforms-form-builder' ),
												);
												echo $arflitemaincontroller->arflite_selectpicker_dom( 'arf_paypal_payment_type', 'arf_paypal_payment_type', 'arf_pp_small_dd_field', 'width:100%',$selected_list_id, $arf_paypal_arforms_payment_type_change_attr, $selected_list_label, false, array(), false, array(), false, array(), true);
											?>
											
											
										</div>
									</div>
									<div class="arferrmessage arf_pp_error_msg" id="arf_paypal_currency_msg"><?php esc_html_e( 'This field cannot be blank.', 'arforms-form-builder' ); ?></div>
								</td>
							</tr>

							<tr class="tdclass arfpaymenttypesingledata">
								<td width="18%" class="tdclass" valign="top">
									<label class="lblsubtitle arf_pp_amount_label"><?php esc_html_e( 'Amount', 'arforms-form-builder' ); ?></label>&nbsp;&nbsp;<span class="arfglobalrequiredfield arf_pp_valign_middle">*</span>
								</td>
								<td class="arf_pp_amount_field_wrapper">
									<div class="arf_amount_dropdown">
										<?php echo $arf_paypal->field_dropdown( $values['form_id'], 'arf_amount', 'arf_paypal_fields', $values['amount'], $field_array ); //phpcs:ignore?>
									</div>
									<div class="arferrmessage arf_pp_error_msg" id="arf_amount_msg"><?php esc_html_e( 'This field cannot be blank.', 'arforms-form-builder' ); ?></div>
								</td>
							</tr>
							<tr class="tdclass">
								<td valign="top" colspan="2" class="lbltitle titleclass"><br /><?php esc_html_e( 'Return Action', 'arforms-form-builder' ); ?></td>
							</tr>   

							<tr class="tdclass"><td class="tdclass" valign="top">
									<label class="lblsubtitle"><?php esc_html_e( 'Action After Paypal Response', 'arforms-form-builder' ); ?></label>            
									<br /></td>
								<td class="paypal_fields_td">        
									<div class="arf_radio_wrapper"><div class="arf_custom_radio_div"><div class="arf_custom_radio_wrapper"><input type="radio" class="" name="success_action" id="success_action_redirect" value="redirect" <?php checked( $values['success_action'], 'redirect' ); ?> /><svg width="18px" height="18px"><?php echo ARFLITE_CUSTOM_UNCHECKEDRADIO_ICON . ARFLITE_CUSTOM_CHECKEDRADIO_ICON;//phpcs:ignore ?></svg></div></div><span><label for="success_action_redirect"><?php esc_html_e( 'Redirect to URL', 'arforms-form-builder' ); ?></label></span></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;        
									<div class="arf_radio_wrapper"><div class="arf_custom_radio_div"><div class="arf_custom_radio_wrapper"><input type="radio" class="" name="success_action" id="success_action_message" value="message" <?php checked( $values['success_action'], 'message' ); ?> /><svg width="18px" height="18px"><?php echo ARFLITE_CUSTOM_UNCHECKEDRADIO_ICON . ARFLITE_CUSTOM_CHECKEDRADIO_ICON; //phpcs:ignore?></svg></div></div><span><label for="success_action_message"><?php esc_html_e( 'Display a Message', 'arforms-form-builder' ); ?></label></span></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;         
									<div class="arf_radio_wrapper"><div class="arf_custom_radio_div"><div class="arf_custom_radio_wrapper"><input type="radio" class="" name="success_action" id="success_action_page" value="page" <?php checked( $values['success_action'], 'page' ); ?> /><svg width="18px" height="18px"><?php echo ARFLITE_CUSTOM_UNCHECKEDRADIO_ICON . ARFLITE_CUSTOM_CHECKEDRADIO_ICON; //phpcs:ignore?></svg></div></div><span><label for="success_action_page"><?php esc_html_e( 'Display content from another page', 'arforms-form-builder' ); ?></label></span></div><br />
								</td>        
							</tr>                

					<tr>
						<td></td>
						<td class="tdclass" valign="top">
							<label class="lblsubtitle arf_label arf_pp_return_act_msg">(<?php echo stripslashes( esc_html__( 'Return action should be "redirected to URL" in case of modal forms or forms in widget.', 'arforms-form-builder' ) ); //phpcs:ignore?>)</label>
						</td>
					</tr>

					<tr class="tdclass success_action_redirect_box success_action_box <?php echo ( $values['success_action'] == 'redirect' ) ? '' : ' arf_pp_display_none '; ?>">
						<td class="tdclass" valign="top">
							<label class="lblsubtitle"><?php esc_html_e( 'Redirect to URL', 'arforms-form-builder' ); ?></label>
						</td>
						<td>
							<input type="text" name="success_url" id="success_url" value="<?php echo ( isset( $values['success_url'] ) ) ? esc_attr( $values['success_url'] ) : ''; ?>" class="txtmodal1 arf_pp_success_url_input" size="55"><div class="arferrmessage arf_pp_display_none" id="success_url_error" ><?php esc_html_e( 'This field cannot be blank.', 'arforms-form-builder' ); ?></div></td>        
					</tr>

					<tr class="tdclass success_action_message_box success_action_box <?php echo ( $values['success_action'] == 'message' ) ? '' : ' arf_pp_display_none '; ?>">
						<td class="tdclass" valign="top">
							<label class="lblsubtitle arf_pp_valign_top" ><?php esc_html_e( 'Confirmation Message', 'arforms-form-builder' ); ?></label>
						</td>        
						<td>
							<textarea id="success_msg" name="success_msg" cols="50" rows="4" class="txtmultimodal1 arf_pp_form_success_msg"><?php echo $arflitemainhelper->arflite_esc_textarea( $arfliteformcontroller->arflitebr2nl( $values['success_msg'] ) ); //phpcs:ignore?></textarea><br />
							<div class="arferrmessage arf_pp_error_msg" id="success_msg_error"><?php esc_html_e( 'This field cannot be blank.', 'arforms-form-builder' ); ?></div>
						<td>        
					</tr>                
					<tr class="tdclass success_action_page_box success_action_box <?php echo ( 'page' == $values['success_action'] ) ? '' : ' arf_pp_display_none '; ?>">
						<td class="tdclass" valign="top"><label class="lblsubtitle arf_pp_valign_super"><?php esc_html_e( 'Use Content from Page', 'arforms-form-builder' ); ?></label></td>
						<td>
							<div class="arf_form_dropdown">
								<div class="sltstandard arf_pp_no_float">
									<?php
									$pages                 = $arflitemainhelper->arflite_get_pages();
									$selected_list_id      = '';
									$selected_list_label   = '';
									$cntr                  = 0;
									foreach ( $pages as $page ) {
										if ( isset( $values['success_page_id'] ) && $values['success_page_id'] == $page->ID || $cntr == 0 ) {
											$selected_list_id    = $page->ID;
											$selected_list_label = $page->post_title;
										}

										$post_title_value[$page->ID]       = $page->post_title;
										$cntr++;
									}
									?>

									<input name="success_page_id" id="option_success_page_id" value="<?php echo esc_attr( $selected_list_id ); ?>" type="hidden">
									<?php
										echo $arflitemaincontroller->arflite_selectpicker_dom( 'success_page_id', 'option_success_page_id', 'arf_pp_success_page_dd', 'width:100%','', '', $post_title_value, false, array(), false, array(), false, array(), true); //phpcs:ignore
									?>
									
								</div>
							</div>
							<div class="arferrmessage arf_pp_error_msg" id="option_success_page_id_error"><?php esc_html_e( 'This field cannot be blank.', 'arforms-form-builder' ); ?></div></td>
					</tr>

					<tr class="tdclass">
						<td width="18%" valign="top" class="tdclass">
							<label class="lblsubtitle"><?php esc_html_e( 'Cancel URL', 'arforms-form-builder' ); ?></label>
						</td>
						<td>
							<input type="text" name="arf_paypal_cancel_url" id="arf_paypal_cancel_url" value="<?php echo esc_attr( $values['cancel_url'] ); ?>" class="txtmodal1 arf_pp_cancel_url_input" />
						</td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr class="tdclass">
						<td valign="top" colspan="2" class="lbltitle titleclass"><br /><?php esc_html_e( 'Email Notification', 'arforms-form-builder' ); ?></td>
					</tr>
					<tr class="tdclass">
						<td width="18%" valign="top" class="tdclass">
							<label class="lblsubtitle"><?php esc_html_e( 'Notification', 'arforms-form-builder' ); ?></label>
						</td>
						<td class="arf_pp_notification_wrapper">
							<label class="arf_js_switch_label">
								<span>&nbsp;</span>
							</label>
							<span class="arf_js_switch_wrapper">
								<input type="checkbox" class="js-switch" name="arf_paypal_notification" id="arf_paypal_notification" onchange="is_notification_info();" value="1" <?php checked( $values['notification'], 1 ); ?> />
								<span class="arf_js_switch"></span>
							</span>
							<label class="arf_js_switch_label" for="arf_paypal_notification">
								<span>&nbsp;<?php esc_html_e( 'Send notification to admin when payment is received.', 'arforms-form-builder' ); ?></span>
							</label>                                                   
						</td></tr>

					<tr class="tdclass <?php echo ( 1 != $values['notification'] ) ? ' arf_pp_display_none' : ''; ?>" id="notification_option">
						<td width="18%" valign="top">
							<label class="lblsubtitle"></label>
						</td>
						<td>
							<textarea name="email_content" id="email_content" class="txtmultimodal1 arf_pp_email_content_textarea" rows="8"><?php echo $arflitemainhelper->arflite_esc_textarea( $arfliteformcontroller->arflitebr2nl( $values['email_content'] ) ); //phpcs:ignore?></textarea>
							<div class="arf_pp_display_block">
							<div class="sub_content"><label class="lblsubtitle">[transaction_id] - </label><label class="lblsubtitle arf_pp_no_text_shadow"><?php esc_html_e( 'Transaction id of paypal transaction', 'arforms-form-builder' ); ?></label></div>
							<div class="sub_content"><label class="lblsubtitle">[amount] - </label><label class="lblsubtitle arf_pp_no_text_shadow"><?php esc_html_e( 'Amount paid at paypal', 'arforms-form-builder' ); ?></label></div>
							<div class="sub_content"><label class="lblsubtitle">[currency] - </label><label class="lblsubtitle arf_pp_no_text_shadow"><?php esc_html_e( 'Currency of payment made.', 'arforms-form-builder' ); ?></label></div>
							<div class="sub_content"><label class="lblsubtitle">[payer_email] - </label><label class="lblsubtitle arf_pp_no_text_shadow"><?php esc_html_e( 'Payer email', 'arforms-form-builder' ); ?></label></div>
						<div class="sub_content"><label class="lblsubtitle">[payer_id] - </label><label class="lblsubtitle arf_pp_no_text_shadow"><?php esc_html_e( 'Payer id', 'arforms-form-builder' ); ?></label></div>
						<div class="sub_content"><label class="lblsubtitle">[payer_fname] - </label><label class="lblsubtitle arf_pp_no_text_shadow"><?php esc_html_e( 'Payer firstname', 'arforms-form-builder' ); ?></label></div>
						<div class="sub_content"><label class="lblsubtitle">[payer_lname] - </label><label class="lblsubtitle arf_pp_no_text_shadow"><?php esc_html_e( 'Payer lastname', 'arforms-form-builder' ); ?></label></div>
							<div class="sub_content"><label class="lblsubtitle">[payment_date] - </label><label class="lblsubtitle arf_pp_no_text_shadow"><?php esc_html_e( 'Date of payment', 'arforms-form-builder' ); ?></label></div>
							<div class="sub_content"><label class="lblsubtitle">[site_name] - </label><label class="lblsubtitle arf_pp_no_text_shadow"><?php esc_html_e( 'Name of site', 'arforms-form-builder' ); ?></label></div>
						</div>
						</td>
					</tr>

					<tr class="tdclass">
						<td width="18%" valign="top" class="tdclass">
							<label class="lblsubtitle"><?php esc_html_e( 'User Notifications', 'arforms-form-builder' ); ?></label>
						</td>
						<td class="paypal_fields_td arf_pp_user_notification_wrapper">
							<label class="arf_js_switch_label">
								<span>&nbsp;</span>
							</label>
							<span class="arf_js_switch_wrapper">
								<input type="checkbox" class="js-switch" name="arf_paypal_user_notification" id="arf_paypal_user_notification" <?php isset( $values['user_notification'] ) ? checked( $values['user_notification'], 1 ) : ''; ?>  onchange="is_user_notification_info();" value="1" />
								<span class="arf_js_switch"></span>
							</span>
							<label class="arf_js_switch_label" for="arf_paypal_user_notification">
								<span>&nbsp;<?php esc_html_e( 'Send notifications to user when payment is received.', 'arforms-form-builder' ); ?></span>
							</label>
						</td>
					</tr>

					<tr class="tdclass <?php echo ( ! isset( $values['user_notification'] ) || 1 != $values['user_notification'] ) ? ' arf_pp_display_none ' : ''; ?>" id="user_notification_option" >
						<td width="18%" valign="top">
							<label class="lblsubtitle"></label>
						</td>

						<td>
							<textarea name="user_email_content" id="user_email_content" class="txtmultinew arf_pp_email_content_textarea" rows="8"><?php echo isset( $values['user_email_content'] ) ? $arflitemainhelper->arflite_esc_textarea( $arfliteformcontroller->arflitebr2nl( $values['user_email_content'] ) ) : $arf_paypal->user_defalut_email_content(); //phpcs:ignore?></textarea>
							<div class="arf_pp_display_block">
						<div class="sub_content"><label class="lblsubtitle">[transaction_id] - </label><label class="lblsubtitle arf_pp_no_text_shadow" ><?php esc_html_e( 'Transaction id of paypal transaction', 'arforms-form-builder' ); ?></label></div>
						<div class="sub_content"><label class="lblsubtitle">[amount] - </label><label class="lblsubtitle arf_pp_no_text_shadow" ><?php esc_html_e( 'Amount paid at paypal', 'arforms-form-builder' ); ?></label></div>
						<div class="sub_content"><label class="lblsubtitle">[currency] - </label><label class="lblsubtitle arf_pp_no_text_shadow" ><?php esc_html_e( 'Currency of payment made.', 'arforms-form-builder' ); ?></label></div>
						 <div class="sub_content"><label class="lblsubtitle">[payer_email] - </label><label class="lblsubtitle arf_pp_no_text_shadow" ><?php esc_html_e( 'Payer email', 'arforms-form-builder' ); ?></label></div>
					   <div class="sub_content"><label class="lblsubtitle">[payer_id] - </label><label class="lblsubtitle arf_pp_no_text_shadow" ><?php esc_html_e( 'Payer id', 'arforms-form-builder' ); ?></label></div>
					   <div class="sub_content"><label class="lblsubtitle">[payer_fname] - </label><label class="lblsubtitle arf_pp_no_text_shadow" ><?php esc_html_e( 'Payer firstname', 'arforms-form-builder' ); ?></label></div>
					   <div class="sub_content"><label class="lblsubtitle">[payer_lname] - </label><label class="lblsubtitle arf_pp_no_text_shadow" ><?php esc_html_e( 'Payer lastname', 'arforms-form-builder' ); ?></label></div>
						<div class="sub_content"><label class="lblsubtitle">[payment_date] - </label><label class="lblsubtitle arf_pp_no_text_shadow" ><?php esc_html_e( 'Date of payment', 'arforms-form-builder' ); ?></label></div>
						<div class="sub_content"><label class="lblsubtitle">[site_name] - </label><label class="lblsubtitle arf_pp_no_text_shadow" ><?php esc_html_e( 'Name of site', 'arforms-form-builder' ); ?></label></div>
					</div>
						</td>
					</tr>

					<tr class="tdclass arf_pp_notification_note">
						<td width="18%" valign="top"></td>
						<td class="arf_pp_no_paddingtop">
							<label>
								<span></span>
								<?php esc_html_e( '(Email will be sent only if you have collect user information.)', 'arforms-form-builder' ); ?>
							</label>
						</td>
					</tr>
				 
				 <tr>
					<td colspan="2">&nbsp;</td>
				</tr> 
				   
				
				
																																		  
				</table>
				 
				<div class="arf_pp_submit_btn_wrapper">
					<button class="rounded_button btn_green arf_pp_save_btn" id="save_arf_paypal" name="save_arf_paypal" type="submit"><?php esc_html_e( 'Save', 'arforms-form-builder' ); ?></button>
					&nbsp;&nbsp;
					<button class="rounded_button arf_btn_cancel arf_pp_cancel_btn" type="button" onclick="location.href = '?page=ARForms-Paypal'"><?php esc_html_e( 'Cancel', 'arforms-form-builder' ); ?></button>
					&nbsp;&nbsp;<span class="arf_pp_edit_loader"></span>
				</div>
				</form>
		
		</div>
	  </div>  
	</div>
  </div>
</div>
