<?php
	global $maincontroller, $armainhelper, $arf_paypal, $arfform, $arf_version;

	$maincontroller->arfafterinstall();

	global $style_settings, $wpdb;

	$entry_id     = $armainhelper->get_param( 'entry_id', false );
	$where_clause = '';
	$form_name    = '';

	if ( $entry_id ) {

		$where_clause .= ' id in (';

		$entry_ids = explode( ',', $entry_id );

		foreach ( (array) $entry_ids as $k => $it ) {

			if ( $k ) {
				$where_clause .= ',';
			}

			$where_clause .= (int) $it;

			unset( $k );

			unset( $it );
		}

		$where_clause .= ')';
	} else {
		exit;
	}

	if( version_compare( $arf_version, '4.3', '<' ) ){
		$all_transactions = $wpdb->get_results( 'SELECT * FROM ' . $arf_paypal->db_paypal_order . ' WHERE ' . $where_clause );
	} else {
		$all_transactions = $arfform->arf_select_db_data( true, '', $arf_paypal->db_paypal_order, '*', 'WHERE '. $where_clause );
	}

	$filename = 'ARForms_' . $form_name . '_' . time() . '_0.csv';

	$wp_date_format = apply_filters( 'arfcsvdateformat', 'Y-m-d H:i:s' );

	$charset = get_option( 'blog_charset' );

	$to_encoding = $style_settings->csv_format;

	header( 'Content-Description: File Transfer' );
	header( "Content-Disposition: attachment; filename=\"$filename\"" );
	header( 'Content-Type: text/csv; charset=' . $charset, true );
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', mktime( date( 'H' ) + 2, date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) ) . ' GMT' );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	header( 'Cache-Control: no-cache, must-revalidate' );
	header( 'Pragma: no-cache' );

	echo '"Transaction ID","Payment Status","Total Amount","Payment Type","Payment Date"' . "\n";

	foreach ( $all_transactions as $transaction ) {
		$formatted_date = date( $wp_date_format, strtotime( $transaction->created_at ) );
		$total_amount   = $transaction->mc_gross . ' ' . $transaction->mc_currency;
		$payment_type   = ( isset( $transaction->payment_type ) and $transaction->payment_type == 1 ) ? esc_html__( 'Donations', 'arforms-form-builder' ) : esc_html__( 'Product / Service', 'arforms-form-builder' );
		echo "\"{$transaction->txn_id}\",";
		echo "\"{$transaction->payment_status}\",";
		echo "\"{$total_amount}\",";
		echo "\"{$payment_type}\",";
		echo "\"{$formatted_date}\"\n";
		unset( $transaction );
	}
exit;
