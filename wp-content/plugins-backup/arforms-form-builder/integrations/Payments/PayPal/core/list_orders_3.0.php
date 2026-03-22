<?php
global $wpdb,$arf_paypal,$arformcontroller,$arfform,$arf_version, $arfsettings,$arflitemaincontroller, $tbl_arf_paypal_forms, $tbl_arf_paypal_order, $arfliterecordcontroller;
$actions = array( '-1' => addslashes( esc_html__( 'Bulk Actions', 'arforms-form-builder' ) ),
                  'bulk_delete' => addslashes(esc_html__('Delete', 'arforms-form-builder')) );

global $style_settings, $wp_scripts;
$wp_format_date = get_option( 'date_format' );

if ( $wp_format_date == 'F j, Y' || $wp_format_date == 'm/d/Y' ) {
	$date_format_new  = 'MM/DD/YYYY';
	$date_format_new1 = 'MM-DD-YYYY';
	$start_date_new   = '01/01/1970';
	$end_date_new     = '12/31/2050';
} elseif ( $wp_format_date == 'd/m/Y' ) {
	$date_format_new  = 'DD/MM/YYYY';
	$date_format_new1 = 'DD-MM-YYYY';
	$start_date_new   = '01/01/1970';
	$end_date_new     = '31/12/2050';
} elseif ( $wp_format_date == 'Y/m/d' ) {
	$date_format_new  = 'DD/MM/YYYY';
	$date_format_new1 = 'DD-MM-YYYY';
	$start_date_new   = '01/01/1970';
	$end_date_new     = '31/12/2050';
} else {
	$date_format_new  = 'MM/DD/YYYY';
	$date_format_new1 = 'MM-DD-YYYY';
	$start_date_new   = '01/01/1970';
	$end_date_new     = '12/31/2050';
}
?>
<input type="hidden" name="_wpnonce_arforms" id="arforms_wp_nonce" value="<?php echo wp_create_nonce( 'arforms_wp_nonce' ); ?>" />
<?php echo str_replace( 'id="{arf_id}"', 'id="arf_full_width_loader"', ARFLITE_LOADER_ICON ); //phpcs:ignore?>
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
			<svg>
				<path fill-rule="evenodd" clip-rule="evenodd" fill="#ffffff" d="M10.702,10.909L6.453,6.66l-4.249,4.249L1.143,9.848l4.249-4.249L1.154,1.361l1.062-1.061l4.237,4.237l4.238-4.237l1.061,1.061L7.513,5.599l4.249,4.249L10.702,10.909z"></path>
			</svg>
		</div>
	</div>
</div>
<div class="wrap paypal_page">
	<div class="top_bar">
		<span class="h2"><?php esc_html_e( 'PayPal Transactions', 'arforms-form-builder' ); ?></span>
	</div>
	<div id="poststuff" class="metabox-holder">
		<div id="post-body">
			<div class="inside">
				<div class="frm_settings_form wrap_content arf_paypal_order_list" id="arf_paypal_order_form">
					<div class="arf_clear_both"></div>
					<input type='hidden' id='datepicker_locale' value='<?php echo isset( $options['locale'] ) ? esc_attr($options['locale']) : ''; ?>' />
					<input type='hidden' id='datepicker_format' value='<?php echo esc_html( $date_format_new ); ?>' />
					<input type='hidden' id='datepicker_format_new' value='<?php echo esc_html( $date_format_new1 ); ?>' />
					<input type='hidden' id='datepicker_start_date' value='<?php echo esc_html( $start_date_new ); ?>' />
					<input type='hidden' id='datepicker_end_date' value='<?php echo esc_html( $end_date_new ); ?>' />
					   <form name="arf_paypal_order" class="arf_pp_full_width" method="get" id="arf_paypal_order" onsubmit="return arf_paypal_order_bulk_act();">
							<input type="hidden" name="arf_paypal_order_list" id="arf_paypal_order_list_nonce" value="<?php echo esc_attr(wp_create_nonce('arf_paypal_order_nonce'));?>">   
						 <div id="arf_paypal_orders">
							<div class="arf_form_entry_select">
								<table class="arf_form_entry_select_sub">
									<tr>
										<th class="arf_form_entry_left arf_pp_select_form_label"><?php esc_html_e( 'Select form', 'arforms-form-builder' ); ?>:</th>
										<th class="arf_form_entry_left"><?php esc_html_e( 'Select Date', 'arforms-form-builder' ); ?> (<?php esc_html_e( 'optional', 'arforms-form-builder' ); ?>)</th>
									</tr>
									<tr>
										<td>
											<div class="sltstandard arf_pp_form_list_dd">
												<?php
												$get_form_id = ( isset( $_GET['form'] ) ) ? esc_attr($_GET['form']) : '';
												$arf_paypal->paypal_form_dropdown( 'arf_paypal_forms_dropdown', $get_form_id, esc_html__( 'All Forms', 'arforms-form-builder' ) );
												?>
											</div>
										</td>
										<td>
											<?php
											if ( is_rtl() ) {
												$sel_frm_sel_date = 'float:right;';
												$sel_frm_button   = 'float:right;';
											} else {
												$sel_frm_sel_date = 'float:left;';
												$sel_frm_button   = 'float:left;';
											}
											?>
											<div class="arf_pp_date_picker_wrapper">
												<div class="arf_pp_from_sel_date">
													<div class="arfentrytitle arf_pp_from_sel_date_label"><?php esc_html_e( 'From', 'arforms-form-builder' ); ?></div>
													<input type="text" class="txtmodal1 arf_pp_from_date_input" id="datepicker_from" value="" name="datepicker_from" />
												</div>
												<div class="arfentrytitle"><?php esc_html_e( 'To', 'arforms-form-builder' ); ?></div>
												&nbsp;&nbsp;
												<div class="arf_pp_from_sel_date">
													<input type="text" class="txtmodal1 arf_pp_to_date_input" id="datepicker_to" name="datepicker_to"/>
												</div>
												<div class="arf_pp_from_sel_date">
													<div class="arf_form_entry_left">&nbsp;</div>
													<div class="arf_pp_form_date_button_wrapper">
														<button type="button" class="rounded_button arf_pp_form_go_btn arf_btn_dark_blue" onclick="change_form_orders();"><?php esc_html_e( 'Go', 'arforms-form-builder' ); ?></button>
													</div>
												</div>
												<input type="hidden" name="please_select_form" id="please_select_form" value="<?php esc_html_e( 'Please select a form', 'arforms-form-builder' ); ?>" />
											</div>
										</td>
									</tr>
								</table>
							</div>
							<?php $two = '1'; ?>
							<div class="alignleft actions">
								<div class="arf_list_bulk_action_wrapper">
									<input id="arf_bulk_action_one" name="action<?php echo esc_html( $two ); ?>" value="-1" type="hidden" />
									<?php
									$li_content = '';
									foreach ( $actions as $name => $title ) {
										$class = 'edit' == $name ? ' class="hide-if-no-js" ' : '';
										$li_content .= '<li '.$class .' data-value="'. $name .'"  data-label="'. $title .'" >'. $title .' </li>';
										$li_class_form_arr[$name] = $title;
									}
									echo $arflitemaincontroller->arflite_selectpicker_dom('action1', 'arf_bulk_action_one', ' arf_selectbox', '', '', array(), $li_class_form_arr);
									?>
								</div>
								<?php
									echo '<input type="submit" id="doaction' . esc_attr($two) . '" class="arf_bulk_action_btn rounded_button btn_green" value="' . esc_html__( 'Apply', 'arforms-form-builder' ) . '" />';
									echo "\n";
								?>
							</div>

							<table cellpadding="0" cellspacing="0" border="0" class="display table_grid" id="example">
								<thead>
									<tr>
										<th class="center">
											<div class="arf_custom_checkbox_div">
												<div class="arf_custom_checkbox_wrapper arf_pp_cb_all_checkbox">
													<input id="cb-select-all-1" class="" type="checkbox" />
													<svg width="18px" height="18px">
														<?php echo ARFLITE_CUSTOM_UNCHECKED_ICON; //phpcs:ignore?>
														<?php echo ARFLITE_CUSTOM_CHECKED_ICON; //phpcs:ignore?>
													</svg>
												</div>
											</div>
										</th>
										<th class=""><?php esc_html_e( 'Transaction ID', 'arforms-form-builder' ); ?></th>
										<th class=""><?php esc_html_e( 'Payment Status', 'arforms-form-builder' ); ?></th>
										<th class=""><?php esc_html_e( 'Total Amount', 'arforms-form-builder' ); ?></th>
										<th class=""><?php esc_html_e( 'Payment Type', 'arforms-form-builder' ); ?></th>
										<th class=""><?php esc_html_e( 'Payment Date', 'arforms-form-builder' ); ?></th>
										<th class=""><?php esc_html_e( 'Payer Email', 'arforms-form-builder' ); ?></th>
										<th class=""><?php esc_html_e( 'Payer Name', 'arforms-form-builder' ); ?></th>
										<th class="arf_col_action arf_action_cell "><?php esc_html_e( 'Action', 'arforms-form-builder' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
                                		global $arf_version;
                                		
											if ( isset( $_GET['form'] ) and $_GET['form'] != '' ) {
												
													$orders = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $tbl_arf_paypal_order . ' WHERE form_id = %d ORDER BY id DESC', $_GET['form'] ) );//phpcs:ignore
												
											} else {
												
													$orders = $wpdb->get_results( 'SELECT * FROM ' . $tbl_arf_paypal_order . ' ORDER BY id DESC' );//phpcs:ignore
												
											}
											

											if ( count( $orders ) > 0 ) {
												foreach ( $orders as $order ) {
											?>

											<tr>
												<td class="center">
													<div class="arf_custom_checkbox_wrapper arfmarginl15">
														<input id="cb-item-action-<?php echo esc_html( $order->id ); ?>" class="" type="checkbox" value="<?php echo esc_html( $order->id ); ?>" name="item-action[]" />
														<svg width="18px" height="18px">
															<?php echo ARFLITE_CUSTOM_UNCHECKED_ICON;//phpcs:ignore ?>
															<?php echo ARFLITE_CUSTOM_CHECKED_ICON; //phpcs:ignore?>
														</svg>
													</div>
													<label for="cb-item-action-<?php echo esc_html( $order->id ); ?>"><span></span></label>
												</td>
												<td><?php echo esc_html( $order->txn_id ); ?></td>
												<td><?php echo ( $order->payment_status == 'Completed' ) ? '<font class="arf_pp_complete_status">' . esc_attr($order->payment_status) . '</font>' : '<font class="arf_pp_incomplete_status">' . esc_attr($order->payment_status) . '</font>'; ?></td>
												<td>
													<?php
														$order_mc_gross = $order->mc_gross;

														if( !empty( $arfsettings->decimal_separator ) && $arfsettings->decimal_separator == ',' ){
															echo number_format( (float) $order->mc_gross, 2, ',', '.' );
														} else {
															echo number_format( (float) $order->mc_gross, 2 );
														}
														
													?>
												</td>
												<td class="">
					<?php
					if ( isset( $order->payment_type ) and $order->payment_type == 1 ) {
						esc_html_e( 'Donations', 'arforms-form-builder' );
					} elseif ( isset( $order->payment_type ) and $order->payment_type == 2 ) {
						esc_html_e( 'Subscription', 'arforms-form-builder' );
					} else {
						esc_html_e( 'Product / Service', 'arforms-form-builder' );
					}
					?>
												</td>
												<td><?php echo esc_attr(date( get_option( 'date_format' ), strtotime( $order->payment_date ) )); ?></td>
												<td class=""><?php echo esc_html( $order->payer_email ); ?></td>
												<td class=""><?php echo esc_html( $order->payer_name ); ?></td>
												<td class="arf_col_action arf_action_cell">
													<div class="arf-row-actions">
					   <?php
						echo "<div class='arfformicondiv arfhelptip' title='" . esc_html__( 'View Entry', 'arforms-form-builder' ) . "'><a href='javascript:void(0);' onclick='open_entry_thickbox({$order->entry_id});'><svg width='30px' height='30px' viewBox='-3 -8 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M12.993,15.23c-7.191,0-11.504-7.234-11.504-7.234  S5.801,0.85,12.993,0.85c7.189,0,11.504,7.19,11.504,7.19S20.182,15.23,12.993,15.23z M12.993,2.827  c-5.703,0-8.799,5.214-8.799,5.214s3.096,5.213,8.799,5.213c5.701,0,8.797-5.213,8.797-5.213S18.694,2.827,12.993,2.827z   M12.993,11.572c-1.951,0-3.531-1.581-3.531-3.531s1.58-3.531,3.531-3.531c1.949,0,3.531,1.581,3.531,3.531  S14.942,11.572,12.993,11.572z'/></svg></a></div>";

														// echo "<div class='arfformicondiv arfhelptip' title='" .esc_html__("Cancel Subscription","ARForms-paypal"). "'><a href='javascript:void(0);' onclick='cancel_subscription_func(this,{$order->entry_id}, {$order->form_id}, \"{$order->txn_id}\")'><svg xmlns:x='http://ns.adobe.com/Extensibility/1.0/' xmlns:i='http://ns.adobe.com/AdobeIllustrator/10.0/' xmlns:graph='http://ns.adobe.com/Graphs/1.0/' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' fill='#ffffff' version='1.1' x='0px' y='0px' viewBox='-20 -20 140 140' enable-background='new -20 -20 140 140' xml:space='preserve'><metadata><sfw xmlns='http://ns.adobe.com/SaveForWeb/1.0/'><slices/><sliceSourceBounds width='1447.7' height='1443.7' x='1.2' y='-1446.3' bottomLeftOrigin='true'/></sfw></metadata><path d='M50,97.4c26.1,0,47.4-21.3,47.4-47.4S76.1,2.6,50,2.6C23.9,2.6,2.6,23.9,2.6,50S23.9,97.4,50,97.4z M50,13.9  c19.9,0,36.1,16.2,36.1,36.1S69.9,86.1,50,86.1c-19.9,0-36.1-16.2-36.1-36.1S30.1,13.9,50,13.9z M35.6,56.4L42,50l-6.4-6.4  c-2.2-2.2-2.2-5.8,0-8c2.2-2.2,5.8-2.2,8,0L50,42l6.4-6.4c2.2-2.2,5.8-2.2,8,0c2.2,2.2,2.2,5.8,0,8L58,50l6.4,6.4  c2.2,2.2,2.2,5.8,0,8c-1.1,1.1-2.5,1.7-4,1.7s-2.9-0.6-4-1.7L50,58l-6.4,6.4c-1.1,1.1-2.5,1.7-4,1.7s-2.9-0.6-4-1.7  C33.4,62.2,33.4,58.6,35.6,56.4z'/></svg></a></div>";


														/* echo "<div class='arfformicondiv arfhelptip arfdeleteentry_div_" . $order->id . "' title='" . esc_html__( 'Delete', 'arforms-form-builder' ) . "'><a class='arf_delete_entry' data-id='" . $order->id . "' ><svg width='30px' height='30px' viewBox='-5 -5 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M18.435,4.857L18.413,19.87L3.398,19.88L3.394,4.857H1.489V2.929  h1.601h3.394V0.85h8.921v2.079h3.336h1.601l0,0v1.928H18.435z M15.231,4.857H6.597H5.425l0.012,13.018h10.945l0.005-13.018H15.231z   M11.4,6.845h2.029v9.065H11.4V6.845z M8.399,6.845h2.03v9.065h-2.03V6.845z' /></svg></a></div>"; */

														echo "<div class='arfformicondiv arfhelptip arfdeleteentry_div_" . esc_attr($order->id) . "' title='" . esc_html__( 'Delete', 'arforms-form-builder' ) . "'><a class='arf_delete_entry' data-id='" . esc_attr($order->id) . "' ><svg width='30px' height='30px' viewBox='-5 -5 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M18.435,4.857L18.413,19.87L3.398,19.88L3.394,4.857H1.489V2.929  h1.601h3.394V0.85h8.921v2.079h3.336h1.601l0,0v1.928H18.435z M15.231,4.857H6.597H5.425l0.012,13.018h10.945l0.005-13.018H15.231z   M11.4,6.845h2.029v9.065H11.4V6.845z M8.399,6.845h2.03v9.065h-2.03V6.845z' /></svg></a></div>";

														echo "<div id='view_entry_detail_container_{$order->entry_id}' class='arf_pp_display_none'>" . $arfliterecordcontroller->arflite_get_entries_list( $order->entry_id ) . "</div><div class='arf_clear_both arfmnarginbtm10'></div>";//phpcs:ignore

						?>
													 </div>
												 </td>
											</tr>

                                			<?php 
		                                		}
			                                }
                                		
                                	?>
								</tbody>
								  
							</table>
							<div class="clear"></div>
							<input type="hidden" name="show_hide_columns" id="show_hide_columns" value="<?php esc_html_e( 'Show / Hide columns', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="search_grid" id="search_grid" value="<?php esc_html_e( 'Search', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="entries_grid" id="entries_grid" value="<?php esc_html_e( 'entries', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="show_grid" id="show_grid" value="<?php esc_html_e( 'Show', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="showing_grid" id="showing_grid" value="<?php esc_html_e( 'Showing', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="to_grid" id="to_grid" value="<?php esc_html_e( 'to', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="of_grid" id="of_grid" value="<?php esc_html_e( 'of', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php esc_html_e( 'No matching records found', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php esc_html_e( 'No data available in table', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="filter_grid" id="filter_grid" value="<?php esc_html_e( 'filtered from', 'arforms-form-builder' ); ?>"/>
							<input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php esc_html_e( 'total', 'arforms-form-builder' ); ?>"/>
		<?php $two = '3'; ?>
							<div class="alignleft actions2">
								<div class="arf_list_bulk_action_wrapper">
									<input id="arf_bulk_action_two" name="action<?php echo esc_html( $two ); ?>" value="-1" type="hidden" />
									<?php
									$li_content = '';
									foreach ( $actions as $name => $title ) {
										$class = 'edit' == $name ? ' class="hide-if-no-js" ' : '';
										$li_content .= '<li '.$class .' data-value="'. $name .'"  data-label="'. $title .'" >'. $title .' </li>';
										$li_class_form_arr[$name] = $title;
									}
									echo $arflitemaincontroller->arflite_selectpicker_dom('action3', 'arf_bulk_action_one', ' arf_selectbox', '', '', array(), $li_class_form_arr);
									?>
								</div>
								<?php
									echo '<input type="submit" id="doaction' . esc_attr($two) . '" class="arf_bulk_action_btn rounded_button btn_green" value="' . esc_html__( 'Apply', 'arforms-form-builder' ) . '" />';
									echo "\n";
								?>
							</div>
							<div class="footer_grid"></div>
						</div>
					</form>

					<div class='arf_modal_overlay'>
						<div class='arf_popup_container arf_view_entry_modal'>
							<div class='arf_popup_container_header'><?php echo esc_html__( 'VIEW ENTRY', 'arforms-form-builder' ); ?>
								<div class='arf_modal_close_btn arf_entry_model_close' data-dismiss='arfmodal'>
									
								</div>
							</div>
							<div class='arfentry_modal_content arf_popup_content_container'></div>
						</div>
					</div>

				</div>
			
				 <div class="documentation_link" align="right">
					 <a href="<?php echo esc_url( ARFLITEURL . '/documentation/index.html' ); ?>" class="arf_pp_doc_link" target="_blank"><?php esc_html_e( 'PayPal Documentation', 'arforms-form-builder' ); ?></a>|<a href="http://reputeinfosystems.com/support/" class="arf_pp_support_link" target="_blank"><?php esc_html_e( 'Support', 'arforms-form-builder' ); ?></a> &nbsp;&nbsp;<img src="<?php echo esc_url( ARFLITEURL . '/images/dot.png' ); ?>" height="4" width="4" onclick="javascript:OpenInNewTab('<?php echo esc_url( ARFLITEURL . '/documentation/assets/sysinfo.php' ); ?>');" />
				 </div>
			  </div>    
		</div>
	</div>      

	<div class="arf_modal_overlay">
		<div id="delete_bulk_transaction_message" class="arfdeletemodabox arfmodal arf_popup_container arfdeletemodalboxnew">
			<input type="hidden" value="false" id="delete_bulk_entry_flag"/>
			<div class="arfdelete_modal_msg delete_confirm_message"><?php echo sprintf( esc_html__( 'Are you sure you want to %s delete this transaction?', 'arforms-form-builder' ), '<br/>' ); //phpcs:ignore?></div>
			<div class="arf_delete_modal_row delete_popup_footer">
				<button class="rounded_button add_button arf_delete_modal_left arfdelete_color_red" onclick="arf_delete_bulk_transaction('true');">&nbsp;<?php echo addslashes( esc_html__( 'Okay', 'arforms-form-builder' ) ); //phpcs:ignore?></button>&nbsp;&nbsp;<button class="arf_delete_modal_right rounded_button delete_button arfdelete_color_gray arf_bulk_delete_transaction_close_btn" data-dismiss="arfmodal">&nbsp;<?php echo addslashes( esc_html__( 'Cancel', 'arforms-form-builder' ) ); //phpcs:ignore?></button>
			</div>
		</div>
	</div>
</div>