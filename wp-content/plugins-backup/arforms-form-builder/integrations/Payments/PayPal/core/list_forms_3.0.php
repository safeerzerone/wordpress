<?php
global $wpdb, $arf_paypal, $arformcontroller, $arfform, $arf_version, $arfsettings,$arflitemaincontroller;
$actions = array( '-1' => addslashes( esc_html__( 'Bulk Actions', 'arforms-form-builder' ) ),
                  'bulk_delete' => addslashes(esc_html__('Delete', 'arforms-form-builder')) );
echo str_replace( 'id="{arf_id}"', 'id="arf_full_width_loader"', ARFLITE_LOADER_ICON );//phpcs:ignore?>
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
<?php
if ( isset( $_GET['err'] ) && 1 == $_GET['err'] ) {
	echo '<input type="hidden" id="arfpaypal_form_error" />';
}
?>
<input type="hidden" name="_wpnonce_arforms" id="arforms_wp_nonce" value="<?php echo wp_create_nonce( 'arforms_wp_nonce' ); ?>" />
<div class="wrap paypal_page">
	<div class="top_bar">
		<span class="h2"><?php esc_html_e( 'Paypal Configuration', 'arforms-form-builder' ); ?></span>
	</div>

	  <div id="poststuff" class="metabox-holder">
		<div id="post-body">
			  <div class="inside">
				<div class="frm_settings_form wrap_content arf_paypal_list_forms" id="arf_paypal_list_form">
					   <form name="arf_paypal_forms" class="arf_pp_full_width" method="get" id="arf_paypal_forms" onsubmit="return arf_paypal_form_bulk_act();">
						<input type="hidden" name="arf_paypal_forms" id="arf_paypal_form_list" value="<?php echo esc_attr(wp_create_nonce('arf_paypal_form_list_nonce')); ?>">	   
					   <div id="arf_paypal_forms">
							<div class="arf_newaction_rounded_blue_button">
								<button class="rounded_button arf_btn_dark_blue arf_paypal_add_new_config_button" type="button" onclick="location.href='<?php echo esc_url(admin_url( 'admin.php?page=ARForms-Paypal&arfaction=new' )); ?>';"><svg width="20px" height="20px" class="arf_pp_valign_middle"><path xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" fill="#FFFFFF" d="M16.997,7.32v2h-7v6.969h-2V9.32h-7v-2h7V0.289h2V7.32H16.997z"/></svg>&nbsp;<?php esc_html_e( 'Configure New Form', 'arforms-form-builder' ); ?></button>
							</div>
		<?php $two = '1'; ?>
							<div class="alignleft actions">
								<div class="arf_list_bulk_action_wrapper arf_pp_list_bulk_action_wrapper">
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
										<th class=""><?php esc_html_e( 'Form ID', 'arforms-form-builder' ); ?></th>
										<th class=""><?php esc_html_e( 'Form Title', 'arforms-form-builder' ); ?></th>
										<th class="arf_pp_transaction_col"><?php esc_html_e( 'Transactions', 'arforms-form-builder' ); ?></th>
										<th class="arf_pp_total_amount_col"><?php esc_html_e( 'Total Amount', 'arforms-form-builder' ); ?></th>
										<th class=""><?php esc_html_e( 'Created Date', 'arforms-form-builder' ); ?></th>
										<th class="arf_col_action arf_action_cell"><?php esc_html_e( 'Action', 'arforms-form-builder' ); ?></th>
									</tr>
                                </thead>
                                <tbody>
                                	<?php
                                		global $arf_version,$tbl_arf_forms ;
                                		
                                			
												$forms = $wpdb->get_results( 'SELECT pyl.*,frm.name FROM ' . $arf_paypal->db_paypal_forms . ' pyl INNER JOIN ' . $tbl_arf_forms  . ' frm ON frm.id=pyl.form_id ORDER BY pyl.id DESC' );//phpcs:ignore
								  			
											if ( count( $forms ) > 0 ) {
											foreach ( $forms as $form ) {
												$options     = maybe_unserialize( $form->options );
												
													$ord_details = $wpdb->get_results( $wpdb->prepare( 'SELECT count(*) AS record_count,SUM(mc_gross) AS total_amount FROM ' . $arf_paypal->db_paypal_order . ' WHERE form_id = %d', $form->form_id ) );//phpcs:ignore
												
												$ord_details = $ord_details[0];
												?>
												<tr>
													<td class="center">
														<div class="arf_custom_checkbox_wrapper arfmarginl15">
															<input id="cb-item-action-<?php echo esc_html( $form->id ); ?>" class="" type="checkbox" value="<?php echo esc_html( $form->id ); ?>" name="item-action[]" />
															<svg width="18px" height="18px">
																<?php echo ARFLITE_CUSTOM_UNCHECKED_ICON; //phpcs:ignore?>
																<?php echo ARFLITE_CUSTOM_CHECKED_ICON; //phpcs:ignore?>
															</svg>
														</div>
														<label for="cb-item-action-<?php echo esc_html( $form->id ); ?>"><span></span></label>
													</td>
													<td><?php echo esc_html( $form->form_id ); ?></td>
													<td class="form_name">
														<a class="row-title" href="<?php echo esc_url(wp_nonce_url( "?page=ARForms-Paypal&arfaction=edit&id={$form->id}" )); ?>">
					<?php echo esc_attr(stripslashes( $form->name )); ?>
														</a>
													</td>
													<td class="center">
					<?php echo "<a href='" . esc_url(wp_nonce_url( "?page=ARForms-Paypal-order&form={$form->form_id}" )) . "'>" . esc_attr($ord_details->record_count) . '</a>'; ?>
													</td>
													<td class="center">
													<?php
														$total_amount = isset( $ord_details->total_amout ) ? $ord_details->total_amount : 0;

														
														$total_amount = number_format( (float)$ord_details->total_amount, 2, '.', '' );
														
														
														echo esc_attr($total_amount) . ' ' . esc_attr($options['currency']);
													?>
													</td>
													<td><?php echo esc_attr(date( get_option( 'date_format' ), strtotime( $form->created_at ) )); ?></td>
													<td class="arf_action_cell">
														<div class="arf-row-actions">
					<?php
					$edit_link = "?page=ARForms-Paypal&arfaction=edit&id={$form->id}";
					echo "<div class='arfformicondiv arfhelptip' title='" . esc_html__( 'Edit Configuration', 'arforms-form-builder' ) . "'><a href='" . esc_url(wp_nonce_url( $edit_link )) . "'><svg width='30px' height='30px' viewBox='-5 -4 30 30' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill='#ffffff' d='M17.469,7.115v10.484c0,1.25-1.014,2.264-2.264,2.264H3.75c-1.25,0-2.262-1.014-2.262-2.264V5.082  c0-1.25,1.012-2.264,2.262-2.264h9.518l-2.264,2.001H3.489v13.042h11.979V9.379L17.469,7.115z M15.532,2.451l-0.801,0.8l2.4,2.401  l0.801-0.8L15.532,2.451z M17.131,0.85l-0.799,0.801l2.4,2.4l0.801-0.801L17.131,0.85z M6.731,11.254l2.4,2.4l7.201-7.202  l-2.4-2.401L6.731,11.254z M5.952,14.431h2.264l-2.264-2.264V14.431z'></path></svg></a></div>";

					echo "<div class='arfformicondiv arfhelptip' title='" . esc_html__( 'Transactions', 'arforms-form-builder' ) . "'><a href='" . esc_url(wp_nonce_url( "?page=ARForms-Paypal-order&form={$form->form_id}" )) . "'><svg width='30px' height='30px' viewBox='-5 -4 30 30' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill='#ffffff' d='M12.32,5.952c1.696-1.316,2.421-2.171,2.747-3.272c0.307-1.039-0.35-2.396-1.703-2.576    c-0.881-0.114-2.071,0.374-3.53,0.811c-0.477,0.143-0.979,0.143-1.451,0c-1.459-0.432-2.653-0.965-3.53-0.811    C3.234,0.389,2.892,1.73,3.149,2.68C3.45,3.789,4.2,4.635,5.896,5.952c-2.319,1.745-4.889,6.095-4.889,8.504    c0,3.314,3.854,5.647,8.101,5.647s8.141-2.333,8.141-5.647C17.249,12.047,14.639,7.696,12.32,5.952z M4.762,2.231    c-0.04-0.143-0.068-0.399,0.311-0.469c0.444-0.082,1.3-0.227,2.837,0.229c0.786,0.232,1.618,0.232,2.405,0    c1.536-0.457,2.393-0.307,2.837-0.229c0.313,0.053,0.346,0.326,0.31,0.469c-0.285,1.019-1.02,1.817-2.797,2.824    C10.167,4.884,9.65,4.79,9.116,4.79c-0.533,0-1.056,0.094-1.549,0.265C5.778,4.048,5.043,3.247,4.762,2.231z M9.108,18.093    c-2.462,0-5.51-0.747-5.51-3.637c0-2.633,2.624-8.007,5.51-8.007s5.471,5.374,5.471,8.007    C14.579,17.346,11.615,18.093,9.108,18.093z M9.202,12.316c-0.408,0-0.742-0.334-0.742-0.742s0.334-0.742,0.742-0.742    c0.208,0,0.399,0.082,0.542,0.232c0.27,0.286,0.722,0.302,1.007,0.033s0.302-0.721,0.033-1.007    c-0.241-0.257-0.539-0.448-0.869-0.563H8.489c-0.849,0.298-1.456,1.101-1.456,2.046c0,1.194,0.975,2.168,2.169,2.168    c0.407,0,0.742,0.334,0.742,0.742c0,0.408-0.335,0.742-0.742,0.742c-0.208,0-0.399-0.082-0.542-0.232    c-0.27-0.285-0.722-0.302-1.007-0.033s-0.302,0.722-0.033,1.007c0.241,0.257,0.538,0.449,0.869,0.563c0,0,0.738,0.281,1.426,0    c0.849-0.297,1.455-1.101,1.455-2.046C11.37,13.286,10.396,12.316,9.202,12.316z'/></svg></a></div>";

					echo "<div class='arfformicondiv arfhelptip arfdeleteform_div_" . esc_attr($form->id) . "' title='" . esc_html__( 'Delete', 'arforms-form-builder' ) . "'><a class='arf_paypal_delete' data-id='" . esc_attr($form->id) . "' ><svg width='30px' height='30px' viewBox='-5 -5 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M18.435,4.857L18.413,19.87L3.398,19.88L3.394,4.857H1.489V2.929  h1.601h3.394V0.85h8.921v2.079h3.336h1.601l0,0v1.928H18.435z M15.231,4.857H6.597H5.425l0.012,13.018h10.945l0.005-13.018H15.231z   M11.4,6.845h2.029v9.065H11.4V6.845z M8.399,6.845h2.03v9.065h-2.03V6.845z' /></svg></a></div>";

					?>
														</div>
													 </td>
												</tr>

                                		<?php 
                                			}
                                		}
                                		//else end
                                	
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
								<div class="arf_list_bulk_action_wrapper arf_pp_list_bulk_action_wrapper">
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
				</div>
				<div class="documentation_link" align="right"><a href="<?php echo esc_url( ARFLITEURL . '/documentation/index.html' ); ?>" class="arf_pp_doc_link" target="_blank"><?php esc_html_e( 'PayPal Documentation', 'arforms-form-builder' ); ?></a>|<a href="http://reputeinfosystems.com/support" ctlass="arf_pp_support_link" target="_blank"><?php esc_html_e( 'Support', 'arforms-form-builder' ); ?></a> &nbsp;&nbsp;<img src="<?php echo esc_url( ARFLITEURL . '/images/dot.png' ); ?>" height="4" width="4" onclick="javascript:OpenInNewTab('<?php echo esc_url( ARFLITEURL . "/documenation/assets/sysinfo.php" ); ?>');" /></div>
			</div>
		</div>
	</div>
	<div class="arf_modal_overlay">
		<div id="delete_bulk_paypal_form_message" class="arfdeletemodabox arfmodal arf_popup_container arfdeletemodalboxnew">
			<input type="hidden" value="false" id="delete_bulk_entry_flag" />
			<div class="arfdelete_modal_msg delete_confirm_message"><?php echo sprintf( esc_html__( 'Are you sure you want to %s delete selected form(s)?', 'arforms-form-builder' ) , '<br/>' ); ?></div>
			<div class="arf_delete_modal_row delete_popup_footer">
				<button class="rounded_button add_button arf_delete_modal_left arfdelete_color_red" onclick="arf_delete_bulk_paypal_form('true');">&nbsp;<?php echo addslashes( esc_html__( 'Okay', 'arforms-form-builder' ) ); //phpcs:ignore?></button>&nbsp;&nbsp;<button class="arf_delete_modal_right rounded_button delete_button arfdelete_color_gray arf_bulk_delete_transaction_close_btn" data-dismiss="arfmodal">&nbsp;<?php echo addslashes( esc_html__( 'Cancel', 'arforms-form-builder' ) ); //phpcs:ignore?></button>
			</div>
		</div>
	</div>
</div>