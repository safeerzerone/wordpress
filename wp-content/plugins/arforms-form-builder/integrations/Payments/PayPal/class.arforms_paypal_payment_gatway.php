<?php
global $arf_paypal;
$arf_paypal = new ARForms_Paypal_payment_gatway();

class ARForms_Paypal_payment_gatway {

	public $db_paypal_forms;
	public $db_paypal_order;

	function __construct() {

		global $wpdb, $is_version_compatible, $arf_version;

		$is_version_compatible = $this->is_arforms_version();
		$arf_version = $this->get_arforms_version();

		$this->db_paypal_forms = $wpdb->prefix . 'arf_paypal_forms';
		$this->db_paypal_order = $wpdb->prefix . 'arf_paypal_order';

		if ( ! ( $this->is_arforms_support() ) ) {
			add_action( 'arfliteaftercreateentry', array( $this, 'arf_paypal_submission' ), 100, 2 );
			add_action( 'wp_ajax_arf_paypal_save_settings', array( $this, 'arf_paypal_save_settings_callback' ) );
		}

		add_action( 'arformslite_rearrange_submanu', array( $this, 'arf_paypal_menu' ), 27 );
		add_filter( 'arforms_rearrange_submenu_items', array( $this, 'arforms_paypal_menu_with_pro') );

		add_action( 'admin_notices', array( $this, 'arf_paypal_admin_notices' ) );

		add_filter( 'arflite_entry_payment_detail', array( $this, 'arf_paypal_payment_detail' ), 11, 1 );
		add_filter( 'arf_entry_payment_detail', array( $this, 'arf_paypal_payment_detail' ), 11, 1 );

		add_filter( 'arf_check_payment', array( $this, 'arf_check_payment' ), 11, 3 );

		add_action( 'wp_ajax_arf_paypal_delete_order', array( $this, 'arf_paypal_delete_order' ) );

		add_action( 'wp_ajax_arf_paypal_order_bulk_act', array( $this, 'arf_paypal_order_bulk_act' ) );

		add_action( 'wp_ajax_arf_paypal_delete_form', array( $this, 'arf_paypal_delete_form' ) );

		add_action( 'wp_ajax_arf_paypal_form_bulk_act', array( $this, 'arf_paypal_form_bulk_act' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'arf_set_js' ), 11 );

		add_action( 'admin_enqueue_scripts', array( $this, 'arf_set_css' ), 11 );

		add_action( 'wp_head', array( $this, 'arf_set_front_css') );

		add_action( 'wp_ajax_arf_paypal_field_dropdown', array( $this, 'arf_paypal_field_dropdown' ) );

		add_action( 'arflitebeforedestroyform', array( $this, 'arfdelete_paypal_form' ), 11, 1 );
		add_action( 'arfbeforedestroyform', array( $this, 'arfdelete_paypal_form' ), 11, 1 );

		add_action( 'parse_request', array( $this, 'paypal_api' ) );

		add_action( 'wp', array( $this, 'paypal_response' ), 5 );

		add_action( 'wp_ajax_arfp_form_order', array( $this, 'arfp_form_order' ) );

		add_action( 'init', array( $this, 'parse_standalone_request' ) );

		add_action( 'arf_after_paypal_successful_paymnet', array( $this, 'arf_change_form_entry' ), 8, 3 );
		
		add_action( 'check_arflite_payment_gateway', array( $this, 'arf_paypal_check_response_v3' ), 20, 2 );
		add_action( 'check_arf_payment_gateway', array( $this, 'arf_paypal_check_response_v3' ), 20, 2 );

		add_action( 'user_register', array( $this, 'arf_add_capabilities_to_new_user' ) );

		add_filter( 'arflite_hide_forms', array( $this, 'arf_display_message_content' ), 10, 2 );
		add_filter( 'arf_hide_forms', array( $this, 'arf_display_message_content' ), 10, 2 );

		add_filter( 'arflite_prevent_paypal_to_stop_sending_email_outside', array( $this, 'arf_paypal_to_prevent_send_mail' ), 10, 3 );
		add_filter( 'arf_prevent_paypal_to_stop_sending_email_outside', array( $this, 'arf_paypal_to_prevent_send_mail' ), 10, 3 );

		add_action( 'admin_init', array( $this, 'arf_paypal_check_redirection' ) );

		add_action( 'arflite_afterdisplay_form', array( $this, 'arf_set_paypal_front_js' ) );
		add_action( 'arf_afterdisplay_form', array( $this, 'arf_set_paypal_front_js' ) );

		add_action( 'arf_update_admin_email_notification_data_outside', array( $this, 'arf_paypal_admin_email_notification_data' ), 10, 3 );
		add_action( 'arflite_update_admin_email_notification_data_outside', array( $this, 'arf_paypal_admin_email_notification_data' ), 10, 3 );

		add_action( 'wp_ajax_arf_retrieve_paypal_config_data', array( $this, 'arf_retrieve_paypal_config_data' ) );

		add_action( 'wp_ajax_arf_retrieve_paypal_transaction_data', array( $this, 'arf_retrieve_paypal_transaction_data' ) );
	}

	function is_arforms_version() {
		if ( version_compare( $this->get_arforms_version(), '6.7', '>=' ) ) {
			return 1;
		} else {
			return 0;
		}
	}

    function get_arforms_version() {

		$arf_db_version = get_option( 'arf_db_version' );

		return ( isset( $arf_db_version ) ) ? $arf_db_version : 0;
	}

	function is_arforms_support() {
		if ( file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'arforms/arforms.php' );
	}

	function arf_retrieve_paypal_config_data(){

		if ( empty( $_POST['_wpnonce_arforms'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce_arforms'] ), 'arforms_wp_nonce' ) ) {
            echo esc_attr( 'security_error' );
            die;
		}

		if(!current_user_can('arfpaypalconfiguration')){
            $arf_error_msg = __( 'Sorry, you do not have permission to perform this action', 'arforms-form-builder' );
            wp_die($arf_error_msg);
        }

		global $wpdb, $arf_paypal, $arfform, $arfsettings,$tbl_arf_forms, $tbl_arf_paypal_forms, $tbl_arf_paypal_order, $arformsmain;
		
		$requested_data = !empty($_REQUEST['data']) ? arflite_json_decode( stripslashes_deep( sanitize_text_field($_REQUEST['data']) ), true ) : '';

		$filtered_aoData  = $requested_data['aoData'];

		$return_data = array();

		$order_by = !empty( $filtered_aoData['iSortCol_0'] ) ? $filtered_aoData['iSortCol_0'] : 1;

		$order_by_str = 'ORDER BY';

		
	    if( 1 == $order_by ){
	        $order_by_str .= ' arfrm.form_id';
	    } else if( 2 == $order_by ){
	        $order_by_str .= ' arfrm.form_name';
	    } else if( 3 == $order_by ){
	        $order_by_str .= ' total_entries';
	    } else if( 4 == $order_by ){
	        $order_by_str .= ' total_amount';
	    } else if( 5 == $order_by ){
	        $order_by_str .= ' arfrm.created_at';
	    } else {
	        $order_by_str .= ' arfrm.form_id';
	    }

	    $order_by_str .=  ' ' . ( !empty( $filtered_aoData['sSortDir_0'] ) ? strtoupper($filtered_aoData['sSortDir_0']) : 'DESC' );
	    
	    $form_params = 'arfrm.*,SUM(ord.mc_gross) as total_amount, COUNT(ord.form_id) as total_entries';
	    $group_by_param = 'GROUP BY arfrm.id';

	    $offset = isset($filtered_aoData['iDisplayStart']) ? $filtered_aoData['iDisplayStart'] : 0;
	    $limit = isset($filtered_aoData['iDisplayLength']) ? $filtered_aoData['iDisplayLength'] : 10;

	    $limit_param = 'LIMIT '.$offset.', '.$limit;

	    $where_clause = "WHERE pyl.id != '' " ;
		if( $arformsmain->arforms_is_pro_active() ){
			$where_clause .= ' AND frm.arf_is_lite_form = 0 ';
		} else {
			$where_clause .= ' AND frm.arf_is_lite_form = 1 ';
		}
	    $where_params = array();

	    if( !empty( $filtered_aoData['sSearch'] ) ){
	        $wild = '%';
	        $find = trim( $filtered_aoData['sSearch'] );
	        $getdate = date("F j, Y", strtotime( $find ) );
	        $checkdate = $wild . $wpdb->esc_like( date('Y-m-d', strtotime( $getdate ) ) ) . $wild;
	        $like = $wild . $wpdb->esc_like( $find ) . $wild;
	        $where_clause .= ' AND ( pyl.form_name LIKE %s ) OR pyl.form_id LIKE %s OR pyl.created_at LIKE %s';
	        $where_params[0] = $like;
	        $where_params[1] = $like;
	        $where_params[2] = $checkdate;
	    }
	    
	   
		$forms =  $wpdb->get_results( 'SELECT pyl.*,frm.name FROM ' . $tbl_arf_paypal_forms . ' pyl INNER JOIN ' . $tbl_arf_forms . ' frm ON frm.id=pyl.form_id '.$where_clause.' ORDER BY pyl.id DESC' );//phpcs:ignore 

		
		$total_records = $wpdb->get_var( "SELECT count(*) FROM " .$tbl_arf_paypal_forms ." pyl INNER JOIN {$tbl_arf_forms} frm ON frm.id=pyl.form_id WHERE frm.arf_is_lite_form = 0 " );//phpcs:ignore

	    $data = array();
	    if( count( $forms ) > 0 ){
	    	$ai = 0;
    		foreach ( $forms as $form_data ) {
				
    			$options     = maybe_unserialize( $form_data->options );
				$data[$ai][0] = "<div class='arf_custom_checkbox_wrapper arfmarginl15'>
					<input id='cb-item-action-'" . esc_html( $form_data->id ) ."' class='' type='checkbox' value='". esc_html( $form_data->id ) ."' name='item-action[]' />
					<svg width='18px' height='18px'>". ARFLITE_CUSTOM_UNCHECKED_ICON . ARFLITE_CUSTOM_CHECKED_ICON ."</svg>
				</div>
				<label for='cb-item-action-'". esc_html( $form_data->id ) ."'><span></span></label>";

          		$data[$ai][1] = $form_data->form_id;
         
         		$data[$ai][2] = "<a class='row-title' href='".wp_nonce_url( "?page=ARForms-Paypal&arfaction=edit&id={$form_data->id}")."'>".stripslashes( $form_data->form_name )."</a>";
         
				$total_amt_entr = $wpdb->get_results( $wpdb->prepare( "SELECT count(*) AS record_count,SUM(mc_gross) AS total_amount FROM " . $tbl_arf_paypal_order . " WHERE form_id = %d", $form_data->form_id  ) );//phpcs:ignore
				$total_amt_entr = $total_amt_entr[0];
				$data[$ai][3] = "<a href='" . wp_nonce_url( "?page=ARForms-Paypal-order&form={$form_data->form_id}" ) . "'>" . $total_amt_entr->record_count  . "</a>";

          		$total_amount = isset( $total_amt_entr->total_amount ) ? $total_amt_entr->total_amount :0;

				
				$total_amount = number_format( (float) $total_amt_entr->total_amount, 2 );
				

          		$data[$ai][4] = $total_amount . ' ' . $options['currency'];

          		$data[$ai][5] = date( get_option( 'date_format' ), strtotime( $form_data->created_at ) );

          		$edit_link = "?page=ARForms-Paypal&arfaction=edit&id={$form_data->id}";

          		$action_row_data = "<div class='arf-row-actions'>";

          		$action_row_data .= "<div class='arfformicondiv arfhelptip' title='" . esc_html__( 'Edit Configuration', 'arforms-form-builder' ) . "'><a href='" . wp_nonce_url( $edit_link ) . "'><svg width='30px' height='30px' viewBox='-5 -4 30 30' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill='#ffffff' d='M17.469,7.115v10.484c0,1.25-1.014,2.264-2.264,2.264H3.75c-1.25,0-2.262-1.014-2.262-2.264V5.082  c0-1.25,1.012-2.264,2.262-2.264h9.518l-2.264,2.001H3.489v13.042h11.979V9.379L17.469,7.115z M15.532,2.451l-0.801,0.8l2.4,2.401  l0.801-0.8L15.532,2.451z M17.131,0.85l-0.799,0.801l2.4,2.4l0.801-0.801L17.131,0.85z M6.731,11.254l2.4,2.4l7.201-7.202  l-2.4-2.401L6.731,11.254z M5.952,14.431h2.264l-2.264-2.264V14.431z'></path></svg></a></div>"; 

          		$action_row_data .= "<div class='arfformicondiv arfhelptip' title='" . esc_html__( 'Transactions', 'arforms-form-builder' ) . "'><a href='" . wp_nonce_url( "?page=ARForms-Paypal-order&form={$form_data->form_id}" ) . "'><svg width='30px' height='30px' viewBox='-5 -4 30 30' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill='#ffffff' d='M12.32,5.952c1.696-1.316,2.421-2.171,2.747-3.272c0.307-1.039-0.35-2.396-1.703-2.576    c-0.881-0.114-2.071,0.374-3.53,0.811c-0.477,0.143-0.979,0.143-1.451,0c-1.459-0.432-2.653-0.965-3.53-0.811    C3.234,0.389,2.892,1.73,3.149,2.68C3.45,3.789,4.2,4.635,5.896,5.952c-2.319,1.745-4.889,6.095-4.889,8.504    c0,3.314,3.854,5.647,8.101,5.647s8.141-2.333,8.141-5.647C17.249,12.047,14.639,7.696,12.32,5.952z M4.762,2.231    c-0.04-0.143-0.068-0.399,0.311-0.469c0.444-0.082,1.3-0.227,2.837,0.229c0.786,0.232,1.618,0.232,2.405,0    c1.536-0.457,2.393-0.307,2.837-0.229c0.313,0.053,0.346,0.326,0.31,0.469c-0.285,1.019-1.02,1.817-2.797,2.824    C10.167,4.884,9.65,4.79,9.116,4.79c-0.533,0-1.056,0.094-1.549,0.265C5.778,4.048,5.043,3.247,4.762,2.231z M9.108,18.093    c-2.462,0-5.51-0.747-5.51-3.637c0-2.633,2.624-8.007,5.51-8.007s5.471,5.374,5.471,8.007    C14.579,17.346,11.615,18.093,9.108,18.093z M9.202,12.316c-0.408,0-0.742-0.334-0.742-0.742s0.334-0.742,0.742-0.742    c0.208,0,0.399,0.082,0.542,0.232c0.27,0.286,0.722,0.302,1.007,0.033s0.302-0.721,0.033-1.007    c-0.241-0.257-0.539-0.448-0.869-0.563H8.489c-0.849,0.298-1.456,1.101-1.456,2.046c0,1.194,0.975,2.168,2.169,2.168    c0.407,0,0.742,0.334,0.742,0.742c0,0.408-0.335,0.742-0.742,0.742c-0.208,0-0.399-0.082-0.542-0.232    c-0.27-0.285-0.722-0.302-1.007-0.033s-0.302,0.722-0.033,1.007c0.241,0.257,0.538,0.449,0.869,0.563c0,0,0.738,0.281,1.426,0    c0.849-0.297,1.455-1.101,1.455-2.046C11.37,13.286,10.396,12.316,9.202,12.316z'/></svg></a></div>";
         
				
         		$action_row_data .= "<div class='arfformicondiv arfhelptip arfdeleteform_div_" . $form_data->id . "' title='" . esc_html__( 'Delete', 'arforms-form-builder' ) . "'><a class='arf_paypal_delete' data-id='" . $form_data->id . "' ><svg width='30px' height='30px' viewBox='-5 -5 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M18.435,4.857L18.413,19.87L3.398,19.88L3.394,4.857H1.489V2.929  h1.601h3.394V0.85h8.921v2.079h3.336h1.601l0,0v1.928H18.435z M15.231,4.857H6.597H5.425l0.012,13.018h10.945l0.005-13.018H15.231z   M11.4,6.845h2.029v9.065H11.4V6.845z M8.399,6.845h2.03v9.065h-2.03V6.845z' /></svg></a></div>";
          
          		$action_row_data .= "</div>";

          		$data[$ai][6] = $action_row_data ;

          		$ai++;

    		}

	    	$sEcho = isset($filtered_aoData['sEcho']) ? intval($filtered_aoData['sEcho']) : intval(10);

	        $return_data = array(
				'sEcho' => $sEcho,
				'iTotalRecords' => (int)$total_records,
				'iTotalDisplayRecords' => (int)$total_records,
				'aaData' => $data,
	        );
	    } else {
			$sEcho = isset($filtered_aoData['sEcho']) ? intval($filtered_aoData['sEcho']) : intval(10);
			$return_data = array(
				'sEcho' => $sEcho,
				'iTotalRecords' => (int)$total_records,
				'iTotalDisplayRecords' => (int)$total_records,
				'aaData' => $data,
			);
	    }

		echo json_encode( $return_data );
        die;
	}

	function arf_retrieve_paypal_transaction_data(){

		if ( empty( $_POST['_wpnonce_arforms'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce_arforms'] ), 'arforms_wp_nonce' ) ) {
            echo esc_attr( 'security_error' );
            die;
		}

		if(!current_user_can('arfpaypaltransaction')){
            $arf_error_msg = __( 'Sorry, you do not have permission to perform this action', 'arforms-form-builder' );
            wp_die($arf_error_msg);
        }


		global $wpdb, $arf_paypal, $tbl_arf_forms,$tbl_arf_entries, $arfform, $arfsettings, $arfliterecordcontroller, $arformsmain;
		

		$requested_data = !empty($_REQUEST['data']) ? arflite_json_decode( stripslashes_deep( sanitize_text_field($_REQUEST['data'] )), true ) : '';

		$filtered_aoData  = $requested_data['aoData'];

		$form_id = !empty( $filtered_aoData['form_id'] ) ? $filtered_aoData['form_id'] : '';


		$start_date = !empty( $filtered_aoData['start_date'] ) ? $filtered_aoData['start_date'] : '';

		$end_date = !empty( $filtered_aoData['end_date'] ) ? $filtered_aoData['end_date'] : '';

		$return_data = array();

		$order_by = !empty( $filtered_aoData['iSortCol_0'] ) ? $filtered_aoData['iSortCol_0'] : 5;

	    $order_by_str = 'ORDER BY';

	    if( 1 == $order_by ){
	        $order_by_str .= ' txn_id';
	    } else if( 2 == $order_by ){
	        $order_by_str .= ' payment_status';
	    } else if( 3 == $order_by ){
	        $order_by_str .= ' mc_gross';
	    } else if( 5 == $order_by ){
	        $order_by_str .= ' created_at';
	    } else if( 6 == $order_by ){
	        $order_by_str .= ' payer_email';
	    } else if( 7 == $order_by ){
	        $order_by_str .= ' payer_name';
	    } else {
	        $order_by_str .= ' created_at';
	    }
	    


		$wp_format_date = get_option( 'date_format' );

		if ( $wp_format_date == 'F j, Y' || $wp_format_date == 'm/d/Y' ) {
			$date_format_new = 'mm/dd/yy';
		} elseif ( $wp_format_date == 'd/m/Y' ) {
			$date_format_new = 'dd/mm/yy';
		} elseif ( $wp_format_date == 'Y/m/d' ) {
			$date_format_new = 'dd/mm/yy';
		} else {
			$date_format_new = 'mm/dd/yy';
		}

		$datequery = '';
		if ( $start_date != '' and $end_date != '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$start_date = str_replace( '/', '-', $start_date );
				$end_date   = str_replace( '/', '-', $end_date );
			}
			$new_start_date_var = date( 'Y-m-d', strtotime( $start_date ) );
			$new_end_date_var   = date( 'Y-m-d', strtotime( $end_date ) );

			$datequery .= " and DATE( created_at) >= '" . $new_start_date_var . "' and DATE( created_at) <= '" . $new_end_date_var . "'";
		} elseif ( $start_date != '' and $end_date == '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$start_date = str_replace( '/', '-', $start_date );
			}
			$new_start_date_var = date( 'Y-m-d', strtotime( $start_date ) );

			$datequery .= " and DATE( created_at) >= '" . $new_start_date_var . "'";
		} elseif ( $start_date == '' and $end_date != '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$end_date = str_replace( '/', '-', $end_date );
			}
			$new_end_date_var = date( 'Y-m-d', strtotime( $end_date ) );

			$datequery .= " and DATE( created_at) <= '" . $new_end_date_var . "'";
		}

	    $order_by_str .=  ' ' . ( !empty( $filtered_aoData['sSortDir_0'] ) ? strtoupper($filtered_aoData['sSortDir_0']) : 'DESC' );

	    $form_table_param = $tbl_arf_forms .' f LEFT JOIN '.$tbl_arf_entries.' e ON f.id = e.form_id';

	    $group_by_param = 'GROUP BY f.id';

	    $offset = isset($filtered_aoData['iDisplayStart']) ? $filtered_aoData['iDisplayStart'] : 0;
	    $limit = isset($filtered_aoData['iDisplayLength']) ? $filtered_aoData['iDisplayLength'] : 10;

	    $limit_param = 'LIMIT '.$offset.', '.$limit;
	    
	    $where_clause = " WHERE arfrm.id != '' ";
	    
	    $where_params = array();
	    

	    if( !empty( $filtered_aoData['sSearch'] ) ){
	    		
	        $wild = '%';
	        $find = trim( $filtered_aoData['sSearch'] );
	        $like = $wild . $wpdb->esc_like( $find ) . $wild;
	        $where_clause .= $wpdb->prepare( ' AND ( arfrm.txn_id LIKE %s OR arfrm.payment_status LIKE %s OR arfrm.mc_gross LIKE %s OR arfrm.payer_email LIKE %s OR arfrm.payer_name LIKE %s )', $like, $like, $like, $like, $like);
	    }

		if( $arformsmain->arforms_is_pro_active() ){
			$where_clause .= $wpdb->prepare( " AND frm.arf_is_lite_form = %d", 0 );
		} else {
			$where_clause .= $wpdb->prepare( " AND frm.arf_is_lite_form = %d", 1 );
		}
		
		
		if ( isset( $form_id ) and $form_id != '' ) {
			$orders = $wpdb->get_results( $wpdb->prepare( 'SELECT *, arfrm.id as id FROM ' . $arf_paypal->db_paypal_order . ' arfrm LEFT JOIN '.$tbl_arf_forms.' frm ON frm.id = arfrm.form_id ' .$where_clause. ' AND arfrm.form_id = %d' . $datequery . ' ORDER BY arfrm.id DESC', $form_id ) );//phpcs:ignore	
		} else {
			$orders = $wpdb->get_results( 'SELECT *, arfrm.id as id FROM ' . $arf_paypal->db_paypal_order . ' arfrm LEFT JOIN '.$tbl_arf_forms.' frm ON frm.id=arfrm.form_id '. $where_clause.' ' . $datequery . ' ORDER BY arfrm.id DESC' );//phpcs:ignore
		}

		$total_records = $wpdb->get_var( "SELECT count(*) FROM " . $arf_paypal->db_paypal_order );//phpcs:ignore 
		
	    $data = array();
	    if( count( $orders ) > 0 ){
			$ai = 0;
			foreach ( $orders as $order ) {
				$data[$ai][0] = "<div class='arf_custom_checkbox_wrapper arfmarginl15'>
						<input id='cb-item-action-'" . esc_html( $order->id ) ."' class='' type='checkbox' value='". esc_html( $order->id ) ."' name='item-action[]' />
						<svg width='18px' height='18px'>". ARFLITE_CUSTOM_UNCHECKED_ICON . ARFLITE_CUSTOM_CHECKED_ICON ."</svg>
					</div>
					<label for='cb-item-action-'". esc_html( $order->id ) ."'><span></span></label>";

				$data[$ai][1] = $order->txn_id;

				$data[$ai][2] = ( $order->payment_status == 'Completed' ) ? '<font class="arf_pp_complete_status">' . $order->payment_status . '</font>' : '<font class="arf_pp_incomplete_status">' . $order->payment_status . '</font>';

				$order_mc_gross = $order->mc_gross;

				
				$data[$ai][3] = number_format( (float) $order->mc_gross, 2 );
				

				if ( isset( $order->payment_type ) and $order->payment_type == 1 ) {
					$data[$ai][4] = esc_html( 'Donations', 'arforms-form-builder' );
				} else {
					$data[$ai][4] = esc_html( 'Product / Service', 'arforms-form-builder' );
				}

				$data[$ai][5] = date( get_option( 'date_format' ), strtotime( $order->created_at ) );

				$data[$ai][6] = esc_html( $order->payer_email );

				$data[$ai][7] = esc_html( $order->payer_name );


				$action_row_data = "<div class='arf-row-actions'>";

				$action_row_data .= "<div class='arfformicondiv arfhelptip' title='" . esc_html__( 'View Entry', 'arforms-form-builder' ) . "'><a href='javascript:void(0);' onclick='open_entry_thickbox({$order->entry_id});'><svg width='30px' height='30px' viewBox='-3 -8 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M12.993,15.23c-7.191,0-11.504-7.234-11.504-7.234  S5.801,0.85,12.993,0.85c7.189,0,11.504,7.19,11.504,7.19S20.182,15.23,12.993,15.23z M12.993,2.827  c-5.703,0-8.799,5.214-8.799,5.214s3.096,5.213,8.799,5.213c5.701,0,8.797-5.213,8.797-5.213S18.694,2.827,12.993,2.827z   M12.993,11.572c-1.951,0-3.531-1.581-3.531-3.531s1.58-3.531,3.531-3.531c1.949,0,3.531,1.581,3.531,3.531  S14.942,11.572,12.993,11.572z'/></svg></a></div>"; 

				$action_row_data .= "<div class='arfformicondiv arfhelptip arfdeleteentry_div_" . $order->id . "' title='" . esc_html__( 'Delete', 'arforms-form-builder' ) . "'><a class='arf_delete_entry' data-id='" . $order->id . "' ><svg width='30px' height='30px' viewBox='-5 -5 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M18.435,4.857L18.413,19.87L3.398,19.88L3.394,4.857H1.489V2.929  h1.601h3.394V0.85h8.921v2.079h3.336h1.601l0,0v1.928H18.435z M15.231,4.857H6.597H5.425l0.012,13.018h10.945l0.005-13.018H15.231z   M11.4,6.845h2.029v9.065H11.4V6.845z M8.399,6.845h2.03v9.065h-2.03V6.845z' /></svg></a></div>";


				$action_row_data .= "<div id='view_entry_detail_container_{$order->entry_id}' class='arf_pp_display_none'>" . $arfliterecordcontroller->arflite_get_entries_list( $order->entry_id ) . "</div><div class='arf_clear_both arfmnarginbtm10'></div>";


				$action_row_data .= "</div>";

				$data[$ai][8] = $action_row_data ;

				$ai++;
	    	}

	    	$sEcho = isset($filtered_aoData['sEcho']) ? intval($filtered_aoData['sEcho']) : intval(10);

	        $return_data = array(
				'sEcho' => $sEcho,
				'iTotalRecords' => (int)$total_records,
				'iTotalDisplayRecords' => (int)$total_records,
				'aaData' => $data,
			);
	    } else {
			$sEcho = isset($filtered_aoData['sEcho']) ? intval($filtered_aoData['sEcho']) : intval(10);
			$return_data = array(
			    'sEcho' => $sEcho,
			    'iTotalRecords' => (int)$total_records,
			    'iTotalDisplayRecords' => (int)$total_records,
			    'aaData' => $data,
			);
	    }
	    
		echo wp_json_encode( $return_data );
        die;
	}

	function arf_paypal_check_redirection() {
		global $wpdb,$arf_paypal, $arfform ;
		if ( isset( $_REQUEST['arfaction'] ) && 'edit' == $_REQUEST['arfaction'] && isset( $_REQUEST['id'] ) && isset($_REQUEST['page']) && 'ARForms-Paypal' == $_REQUEST['page'] ) {
			
				$form_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $arf_paypal->db_paypal_forms . ' WHERE id = %d', $_REQUEST['id'] ) );//phpcs:ignore 
			
			if ( count( $form_data ) == 0 ) {
				wp_redirect( admin_url( 'admin.php?page=ARForms-Paypal&err=1' ), 302, 'arforms-form-builder' );
				die;
			}
		}
	}

	function arf_set_paypal_front_js( $form ) {
		global $wpdb, $arf_paypal, $arf_paypal_assets_version, $arfform;
		if ( '' != $form->id ) {
			
				$form_data = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM `' . $arf_paypal->db_paypal_forms . '` WHERE form_id = %d', $form->id ) );//phpcs:ignore 
			

				if ( $this->is_arforms_support() && $this->is_arforms_version() ) {
					wp_register_script( 'arf_paypal_front_js', ARFURL . '/integrations/Payments/PayPal/js/arf_paypal_front.js', array('jquery'), $arf_paypal_assets_version );
				}else{
					wp_register_script( 'arf_paypal_front_js', ARFLITEURL . '/integrations/Payments/PayPal/js/arf_paypal_front.js', array('jquery'), $arf_paypal_assets_version );
				}
				wp_enqueue_script( 'arf_paypal_front_js' );
			
		}
	}

	function arf_paypal_to_prevent_send_mail( $prevent_sending_email, $entry_id, $form_id ) {
		global $wpdb, $arfform, $tbl_arf_entry_values;
		if ( $prevent_sending_email ) {
			return $prevent_sending_email;
		}
		
		$form_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM  ' . $this->db_paypal_forms . ' WHERE form_id = %d', $form_id ) );//phpcs:ignore 
		
		if ( ! $form_data || count( $form_data ) < 1 ) {
			$prevent_sending_email = false;
		} else {
			$prevent_sending_email = true;
		}

		return $prevent_sending_email;
	}

	public static function arf_paypal_check_network_activation( $network_wide ) {
		if ( ! $network_wide ) {
			return;
		}

		deactivate_plugins( plugin_basename( __FILE__ ), true, true );

		header( 'Location: ' . network_admin_url( 'plugins.php?deactivate=true' ) );
		exit;
	}


	function arf_paypal_getapiurl() {
		$api_url = 'https://www.arpluginshop.com/';
		return $api_url;
	}

	function arf_paypal_get_remote_post_params( $plugin_info = '' ) {
		global $wpdb, $arfversion;

		$action = '';
		$action = $plugin_info;

		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_list = get_plugins();
		$site_url    = home_url();
		$plugins     = array();

		$active_plugins = get_option( 'active_plugins' );

		foreach ( $plugin_list as $key => $plugin ) {
			$is_active = in_array( $key, $active_plugins );

			if ( strpos( strtolower( $plugin['Title'] ), 'arformspaypal' ) !== false ) {
				$name      = substr( $key, 0, strpos( $key, '/' ) );
				$plugins[] = array(
					'name'      => $name,
					'version'   => $plugin['Version'],
					'is_active' => $is_active,
				);
			}
		}
		$plugins = wp_json_encode( $plugins );

		$theme            = wp_get_theme();
		$theme_name       = $theme->get( 'Name' );
		$theme_uri        = $theme->get( 'ThemeURI' );
		$theme_version    = $theme->get( 'Version' );
		$theme_author     = $theme->get( 'Author' );
		$theme_author_uri = $theme->get( 'AuthorURI' );

		$im        = is_multisite();
		$sortorder = get_option( 'arfSortOrder' );

		$post = array(
			'wp'        => get_bloginfo( 'version' ),
			'php'       => phpversion(),
			'mysql'     => $wpdb->db_version(),
			'plugins'   => $plugins,
			'tn'        => $theme_name,
			'tu'        => $theme_uri,
			'tv'        => $theme_version,
			'ta'        => $theme_author,
			'tau'       => $theme_author_uri,
			'im'        => $im,
			'sortorder' => $sortorder,
		);

		return $post;
	}

	function arf_add_capabilities_to_new_user( $user_id ) {
		if ( '' == $user_id ) {
			return;
		}
		if ( user_can( $user_id, 'administrator' ) ) {

			$paypalcapabilities = array(
				'arfpaypalconfiguration' => esc_html__( 'Configure PayPal Forms', 'arforms-form-builder' ),
				'arfpaypaltransaction'   => esc_html__( 'View PayPal Transactions', 'arforms-form-builder' ),
			);

			$arfroles = $paypalcapabilities;

			$userObj = new WP_User( $user_id );
			foreach ( $arfroles as $arfrole => $arfroledescription ) {
				$userObj->add_cap( $arfrole );
			}
			unset( $arfrole );
			unset( $arfroles );
			unset( $arfroledescription );
		}
	}

	function arf_paypal_admin_notices() {

		global $wp_version;

		if ( version_compare( $wp_version, '4.5.0', '<' ) ) {
			deactivate_plugins( __FILE__, false, true );

			echo "<div class='updated'><p>" . esc_html__( 'Please meet the minimum requirement of WordPress version 4.5 to active ARForms Paypal add-on', 'arforms-form-builder' );
		}
	}

	 
	function get_arformslite_version() {

		$arf_db_version = get_option( 'arflite_db_version' );

		return ( isset( $arf_db_version ) ) ? $arf_db_version : 0;
	}

	function route() {
		global $arf_paypal, $arf_paypal_version;
		if ( isset( $_REQUEST['page'] ) && 'ARForms-Paypal' == $_REQUEST['page'] && isset( $_REQUEST['arfaction'] ) && ( 'new' == $_REQUEST['arfaction'] || 'edit' == $_REQUEST['arfaction'] ) ) {
			
			if ( $this->is_arforms_support() && $this->is_arforms_version() ) {
				include FORMPATH . '/integrations/Payments/PayPal/core/edit_3.0.php';
			}else{
				include ARFLITE_FORMPATH . '/integrations/Payments/PayPal/core/edit_3.0.php';
			}
			
		} elseif ( isset( $_REQUEST['page'] ) && 'ARForms-Paypal-order' == $_REQUEST['page'] ) {

			return $arf_paypal->list_orders();
		} else {

			return $arf_paypal->list_forms();
		}
	}


	function arf_set_js() {

		global $arf_paypal_assets_version;

		if ( $this->is_arforms_support() && $this->is_arforms_version() ) {
			wp_register_script( 'arfpaypal-js', ARFURL . '/integrations/Payments/PayPal/js/arf_paypal.js', array( 'jquery' ), $arf_paypal_assets_version );
		}else{
			wp_register_script( 'arfpaypal-js', ARFLITEURL . '/integrations/Payments/PayPal/js/arf_paypal.js', array( 'jquery' ), $arf_paypal_assets_version );
		}

		wp_register_script( 'tipso', ARFLITEURL . '/js/tipso.min.js', array( 'jquery' ), $arf_paypal_assets_version );

		wp_register_script( 'bootstrap-locale', ARFLITEURL . '/bootstrap/js/moment-with-locales.js', array(), $arf_paypal_assets_version );

		wp_register_script( 'bootstrap-datetimepicker', ARFLITEURL . '/bootstrap/js/bootstrap-datetimepicker.js', array( 'jquery' ), $arf_paypal_assets_version, true );

		if ( isset( $_REQUEST['page'] ) && '' != $_REQUEST['page'] && ( 'ARForms-Paypal' == $_REQUEST['page'] || 'ARForms-Paypal-order' == $_REQUEST['page'] ) ) {

			wp_enqueue_script( 'arfpaypal-js' );
			wp_enqueue_script( 'datatables' );
			wp_enqueue_script( 'buttons-colvis' );
			wp_enqueue_style( 'datatables' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'tipso' );
			wp_enqueue_script( 'bootstrap-locale' );
			wp_enqueue_script( 'bootstrap-datetimepicker' );
		}

		$script_data  = "__NO_FORM_FOUND_MSG='" . esc_html__( 'There is no any form found', 'arforms-form-builder' ) . "';";
		$script_data .= "__VALID_ACTION='" . esc_html__( 'Please select valid action', 'arforms-form-builder' ) . "';";
		$script_data .= "__SELECT_RECORD='" . esc_html__( 'Please select one or more record to perform action', 'arforms-form-builder' ) . "';";
		$script_data .= "__ARFADDRULE='" . esc_html__( 'Please add one or more rules', 'arforms-form-builder' ) . "';";
		$script_data .= "__INVALID_FORM_MSG='" . esc_html__( 'This form is already deleted or does not exist.', 'arforms-form-builder' ) . "';";
		$script_data .= "__DELETE_CONFIG='" . sprintf( esc_html__( 'Are you sure you want to %s delete this configuration?', 'arforms-form-builder' ), '<br/>' ) . "';";//phpcs:ignore 
		$script_data .= "__DELETE_ORDER='" . sprintf( esc_html__( 'Are you sure you want to %s delete this transaction?', 'arforms-form-builder' ), '<br/>' ) . "';";//phpcs:ignore 
		$script_data .= "__DELETE='" . esc_html__( 'Delete', 'arforms-form-builder' )."';";
		$script_data .= "__CANCEL='" . esc_html__( 'Cancel', 'arforms-form-builder' )."';";
		$script_data .= "__ARF_SEARCH_PLACEHOLDER='" . esc_html__( 'Search', 'arforms-form-builder' )."';";
		wp_add_inline_script( 'arfpaypal-js', $script_data, 'after' );
	}

	function arf_set_css() {
		global $arf_paypal_version,$arf_paypal_assets_version;

		if ( $this->is_arforms_support() && $this->is_arforms_version() ) {
			wp_register_style( 'arfpaypal-css', ARFURL . '/integrations/Payments/PayPal/css/arf_paypal.css', array(), $arf_paypal_assets_version );
		}else{
			wp_register_style( 'arfpaypal-css', ARFLITEURL . '/integrations/Payments/PayPal/css/arf_paypal.css', array(), $arf_paypal_assets_version );
		}


		wp_register_style( 'tipso', ARFLITEURL . '/css/tipso.min.css', array(), $arf_paypal_assets_version );

		wp_register_style( 'bootstrap-datetimepicker', ARFLITEURL . '/bootstrap/css/bootstrap-datetimepicker.css', array(), $arf_paypal_assets_version );

		if ( isset( $_REQUEST['page'] ) && '' != $_REQUEST['page'] && ( 'ARForms-Paypal' == $_REQUEST['page'] || 'ARForms-Paypal-order' == $_REQUEST['page'] ) ) {

			wp_enqueue_style( 'arfpaypal-css' );


			wp_enqueue_style( 'tipso' );

			wp_enqueue_style( 'bootstrap-datetimepicker' );
		}

		if( isset( $hook ) && 'plugins.php' == $hook ){
			global $wp_version;

			if( version_compare($wp_version, '4.5', '<') ){
				wp_print_styles( 'arfpaypal-css' );
				deactivate_plugins( plugin_basename( __FILE__ ), true, false );
				$redirect_url = network_admin_url( 'plugins.php?deactivate=true' );
				wp_die( '<div class="arf_paypal_wp_notice"><p class="arf_paypal_wp_notice_text" >Please meet the minimum requirement of WordPress version 4.5 to activate ARForms - PayPal Add-on<p class="arf_paypal_wp_notice_continue">Please <a href="javascript:void(0)" onclick="window.location.href=\'' . esc_url( $redirect_url ) . '\'">Click Here</a> to continue.</p></div>' );
			}
		}
	}

	function arf_set_front_css(){
		global $wpdb,$arf_paypal_assets_version, $arfform;

		wp_register_style( 'arf_paypal_front_css', ARFLITEURL . '/integrations/Payments/PayPal/css/arf_paypal_front.css',array(), $arf_paypal_assets_version );

		if ( isset( $_REQUEST['arf_conf'] ) && '' != $_REQUEST['arf_conf'] ) {
			$form_id = $_REQUEST['arf_conf'];
			
				$form_data = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM ' . $this->db_paypal_forms . " WHERE `form_id` = '%d'", $form_id ) );//phpcs:ignore 
			

			if( $form_data > 0 ){
				wp_enqueue_style( 'arf_paypal_front_css' );
			}
		}
	}

	function arf_paypal_save_debug_log_settings($posted_data){
		global $arformsmain;
		$paypal_debug_log = isset( $posted_data['arflite_paypal_debug_log'] ) ? $posted_data['arflite_paypal_debug_log'] : 0;
		
		$arformsmain->arforms_update_settings( 'arflite_paypal_debug_log', $paypal_debug_log, 'debug_log_settings' );
	}
	function arf_paypal_debug_log_block(){
		global $paypal_log,$arformsmain;
		$paypal_log = $arformsmain->arforms_get_settings( 'arflite_paypal_debug_log', 'debug_log_settings' );
		$onchange_func = 'arforms_hide_show_debug_settings(this.checked,"arflite_paypal_debug_log");';
		$onclick_func = '';
		$log_cls = '';
		$arflite_payapl_view_debug_log_nonce= wp_create_nonce('arflite_paypal_view_debug_log_nonce');
		?>
		<div class="arf_debug_log_parent_wrapper">
            <div class="arf-debug-log-sub-heading">
				<span class="lbltitle"><?php echo esc_html__( 'Payment Gateways Debug Log', 'arforms-form-builder' ); ?></span>
			</div>
			<div class="arf-inner-heading">
            	<span class="lblsubtitle lblnotetitle "><?php echo esc_html__( 'Paypal Debug Log', 'arforms-form-builder' ); ?></span>
                <div class="arf_js_switch_wrapper arf-checkbox">                    
                    <input type="checkbox" class="js-switch" name="arflite_paypal_debug_log" id="arflite_paypal_debug_log" value="1" <?php echo !empty( $onclick_func ) ? 'onclick="' . esc_attr($onclick_func) . '"' : ''; ?><?php checked($paypal_log, 1);?> onchange="<?php echo esc_attr( $onchange_func ); ?>" />                                
                    <span class="arf_js_switch"></span>
                </div>
                <div class="arforms_debug_log_setting_wrapper" data-type="arflite_paypal_debug_log" style="<?php echo ( 1 == $paypal_log ) ? 'display:table-row;' : 'display:none;'; ?>">
                    <div class="arf-log-button-div">
                        <button type="button" class="arf-debug-log-button arf-view-img arforms_view_debug_logs" id="arf_paypal_popup_view_log" data-log-type="arflite_paypal_debug_log" data-token="<?php echo $arflite_payapl_view_debug_log_nonce;//phpcs:ignore ?>" ><?php esc_html_e('View Logs', 'arforms-form-builder' ); ?></button>
                        <button type="button" class="arf-debug-log-button arf-download-img" id="arf_paypal_popup_download_log" onclick="return Show_downloadpopup('arflite_paypal_debug_log')";><?php esc_html_e('Download Logs', 'arforms-form-builder'); ?></button> <?php //phpcs:ignore ?>
                        <button type="button" class="arf-debug-log-button arf-clear-img" id="arf_paypal_popup_clear_log" onclick="return Show_clearpopup('arflite_paypal_debug_log')";><?php esc_html_e('Clear Logs', 'arforms-form-builder'); ?></button> <?php //phpcs:ignore ?>
                     </div>
                </div>
        	</div>
		</div>
		<?php
	}
	function arf_paypal_submission( $entry_id, $form_id ) {

		global $wpdb, $arfliterecordmeta,$arf_paypal,$arfform, $arfsettings,$arformsmain;
		
		$form_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM  ' . $arf_paypal->db_paypal_forms . ' WHERE form_id = %d', $form_id ) );//phpcs:ignore 
		

		if ( count( $form_data ) > 0 ) {

			$form_data = $form_data[0];

			$options = maybe_unserialize( $form_data->options );

			$paypal_field_amount = '';

			if ( '' != $options['amount'] ) {
				$paypal_field_amount = $options['amount'];
			}

			global $paypal_fields;
			$paypal_fields = array(
				'first_name' => $options['first_name'],
				'last_name'  => $options['last_name'],
				'email'      => $options['email'],
				'address1'   => $options['address'],
				'address2'   => $options['address_2'],
				'city'       => $options['city'],
				'state'      => $options['state'],
				'zip'        => $options['zip'],
				'country'    => $options['country'],
			);

			if ( ! isset( $paypal_field_amount ) || '' == $paypal_field_amount ) {
				return;
			}

			$entry_ids = array( $entry_id );
			$values    = $arfliterecordmeta->arflitegetAll( 'it.field_id != 0 and it.entry_id in ( ' . implode( ',', $entry_ids ) . ')', ' ORDER BY fi.id' );

			$amount        = '';
			$paypal_values = array();

			$mapped_conditional_field = array();

			$mapped_field_values = array();
			if ( count( $values ) > 0 ) {
				foreach ( $values as $value ) {
					if ( $value->field_id == $paypal_field_amount ) {
						$amount = $value->entry_value;
					}

					if ( $mapped_conditional_field ) {
						foreach ( $mapped_conditional_field as $rule_field ) {
							if ( $rule_field == $value->field_id ) {
								$mapped_field_values[ $value->field_id ] = $value->entry_value;
							}
						}
					}

					foreach ( $paypal_fields as $paypal_field_key => $paypal_field_id ) {
						if ( $value->field_id == $paypal_field_id ) {
							$paypal_values[ $paypal_field_key ] = $value->entry_value;
						}
					}
				}
			}

			$amount = isset( $amount ) ? $amount : 0;

			$sandbox = ( isset( $options['paypal_mode'] ) && 0 == $options['paypal_mode'] ) ? 'sandbox.' : '';

			$currency = ( isset( $options['currency'] ) ) ? $options['currency'] : 'USD';

			$merchant_email = ( isset( $options['merchant_email'] ) ) ? esc_attr( $options['merchant_email'] ) : '';

			$item_name = ( isset( $options['title'] ) ) ? esc_attr( $options['title'] ) : '';

			$cancel_url = ( isset( $options['cancel_url'] ) ) ? esc_attr( $options['cancel_url'] ) : '';

			$continue_text = ( isset( $options['continue_label'] ) ) ? esc_attr( $options['continue_label'] ) : '';

			if ( is_numeric( $amount ) && 0 == $amount ) {
				return;
			}

			$amount = str_replace(".", ".", $amount);

			if ( '' != $merchant_email ) {
				global $tbl_arf_entries;

				$pageURL = 'http';
				if ( isset( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] ) {
					$pageURL .= 's';
				}
				$pageURL .= '://';
				$_SERVER['SERVER_NAME'] = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field($_SERVER['SERVER_NAME']) : '';
				$_SERVER['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
				if ( isset( $_SERVER['SERVER_PORT'] ) && '80' != $_SERVER['SERVER_PORT'] ) {
					$pageURL .= sanitize_text_field($_SERVER['SERVER_NAME']) . ':' . sanitize_text_field($_SERVER['SERVER_PORT']) . sanitize_text_field($_SERVER['REQUEST_URI']);
				} else {
					$pageURL .= sanitize_text_field($_SERVER['SERVER_NAME']) . sanitize_text_field($_SERVER['REQUEST_URI']);
				}

				$wpdb->update( $tbl_arf_entries, array( 'form_id' => '0' ), array( 'id' => $entry_id ) );

				$_SESSION['arf_return_url'][ $form_id ] = $pageURL;

				if ( is_numeric( $amount ) ) {
					$amount = number_format( (float) $amount, 2 );
				}

				if ( is_numeric( $amount ) && ( 'HUF' == $currency || 'JPY' == $currency || 'TWD' == $currency ) ) {
					$amount = (float) $amount;
					$amount = floor( $amount );
				}

				$amount = preg_replace( '/\,/', '', $amount );

				if ( ! isset( $cancel_url ) || empty( $cancel_url ) ) {
					$cancel_url = get_home_url();
				}

				if ( ! isset( $continue_text ) || empty( $continue_text ) ) {
					$continue_text = esc_html__( 'Click here to continue', 'arforms-form-builder' );
				}

				$payment_type_val_int = 0;
				if ( isset( $options['payment_type'] ) ) {
					if ( 'donation' == $options['payment_type'] ) {
						$payment_type_val_int = 1;
					} else {
						$payment_type_val_int = 0;
					}
				} else {
					$payment_type_val_int = 0;
				}
				$payment_type = $payment_type_val_int;

				$payment_type_val = ( isset( $options['payment_type'] ) ) ? $options['payment_type'] : 'product_service';

				if ( 'donation' == $payment_type_val ) {
					$cmd = '_donations';
				} elseif ( 'product_service' == $payment_type_val ) {
					$cmd = '_xclick';
				} else {
					$cmd = '_xclick';
				}

				$arf_pyapal_home_url = get_home_url() . '/';

				if ( strstr( $arf_pyapal_home_url, '?' ) ) {
					$apyapal_return_url    = $arf_pyapal_home_url . '&arf_page=arforms_paypal_response&custom=' . $entry_id . '|' . $form_id . '|' . $payment_type;
					$arf_pyapal_notify_url = $arf_pyapal_home_url . '&arf_page=arforms_paypal_api';
				} else {
					$apyapal_return_url    = $arf_pyapal_home_url . '?arf_page=arforms_paypal_response&custom=' . $entry_id . '|' . $form_id . '|' . $payment_type;
					$arf_pyapal_notify_url = $arf_pyapal_home_url . '?arf_page=arforms_paypal_api';
				}

				$arforms_all_settings = $arformsmain->arforms_global_option_data();
				$arflitesettings = json_decode( wp_json_encode( $arforms_all_settings['general_settings'] ) );
				
				if(1 == $arflitesettings->form_submit_type){
					$return['conf_method'] = 'addon';
				}
				
				$message          = '';
				$is_normal_submit = '';

				if ( 1 != $arflitesettings->form_submit_type ) {

					global $arf_paypal_assets_version;
					$is_normal_submit = 'arf_paypal_form_normal';
					wp_print_scripts( 'jquery' );

					if ( $this->is_arforms_support() && $this->is_arforms_version() ) {
						wp_register_script( 'arf_paypal_front_js', ARFURL . '/integrations/Payments/PayPal/js/arf_paypal_front.js', array(), $arf_paypal_assets_version );
					}else{
						wp_register_script( 'arf_paypal_front_js', ARFLITEURL . '/integrations/Payments/PayPal/js/arf_paypal_front.js', array(), $arf_paypal_assets_version );
					}
					
					wp_print_scripts( 'arf_paypal_front_js' );
				}

				$message .= '<form name="_xclick" class="' . $is_normal_submit . '" id="arf_paypal_form" action="https://www.' . $sandbox . 'paypal.com/cgi-bin/webscr" method="post">
							<input type="hidden" name="cmd" value="' . $cmd . '">
							<input type="hidden" name="charset" value="UTF-8">
							<input type="hidden" name="business" value="' . $merchant_email . '">
							<input type="hidden" name="notify_url" value="' . esc_url( $arf_pyapal_notify_url ) . '">
							<input type="hidden" name="return" value="' . esc_url( $apyapal_return_url ) . '">
							<input type="hidden" name="cancel_return" value="' . $cancel_url . '">
						
							<input type="hidden" name="currency_code" value="' . $currency . '" />
							<input type="hidden" name="item_name" value="' . $item_name . '">
							
							<input type="hidden" name="custom" value="' . $entry_id . '|' . $form_id . '|' . $payment_type . '">
							
							<input type="hidden" name="cbt" value="' . $continue_text . '">
							<input type="hidden" name="rm" value="2">
							<input type="hidden" name="amount" value="' . $amount . '">';
			
				if ( isset( $options['shipping_info'] ) && 1 == $options['shipping_info'] ) {
					$message .= '<input type="hidden" name="first_name" value="' . ( isset( $paypal_values['first_name'] ) ? $paypal_values['first_name'] : '' ) . '" />
                    <input type="hidden" name="last_name" value="' . ( isset( $paypal_values['last_name'] ) ? $paypal_values['last_name'] : '' ) . '" />
                    <input type="hidden" name="email" value="' . ( isset( $paypal_values['email'] ) ? $paypal_values['email'] : '' ) . '" />
                    <input type="hidden" name="address1" value="' . ( isset( $paypal_values['address1'] ) ? $paypal_values['address1'] : '' ) . '" />
                    <input type="hidden" name="address2" value="' . ( isset( $paypal_values['address2'] ) ? $paypal_values['address2'] : '' ) . '" />
                    <input type="hidden" name="city" value="' . ( isset( $paypal_values['city'] ) ? $paypal_values['city'] : '' ) . '" />
                    <input type="hidden" name="state" value="' . ( isset( $paypal_values['state'] ) ? $paypal_values['state'] : '' ) . '" />
                    <input type="hidden" name="zip" value="' . ( isset( $paypal_values['zip'] ) ? $paypal_values['zip'] : '' ) . '" />
                    <input type="hidden" name="country" value="' . ( isset( $paypal_values['country'] ) ? $paypal_values['country'] : '' ) . '" />';
				}

				$message .= '</form>';

				$return['message'] = $message;

				 

				$return = apply_filters( 'arflite_reset_built_in_captcha', $return, $_POST );//phpcs:ignore 

				if ( $arflitesettings->form_submit_type == 1 ) {
					echo wp_json_encode( $return );
				} else {
					echo esc_attr($message);
				}

				exit;
			}
		}
	}

	function list_orders() {
		global $arf_paypal_version, $arf_paypal;
		
			$file = 'list_orders_3.0.php';
		
		include ARFLITE_FORMPATH . '/integrations/Payments/PayPal/core/' . $file;
	}

	function arf_check_payment( $arf_check_payment, $form_id , $entry_id){
		global $arfliterecordmeta, $arf_paypal, $arfform, $wpdb;

		if ( $arf_check_payment ) {
			return $arf_check_payment;
		}

		$is_paypal_form = $wpdb->get_results( $wpdb->prepare('SELECT COUNT(id) FROM `'.$arf_paypal->db_paypal_forms.'` WHERE form_id = %d',  $form_id ) );
		if( $is_paypal_form > 0 ){

			$paypal_form_data = $wpdb->get_results( $wpdb->prepare('SELECT * FROM `'.$arf_paypal->db_paypal_forms.'` WHERE form_id = %d', $form_id ));
			$options = maybe_unserialize($paypal_form_data[0]->options);
			
			$paypal_field_amount = '';

			if ( '' != $options['amount'] ) {
				$paypal_field_amount = $options['amount'];
			}
		 
			$arf_check_payment = true;
		} 

		return $arf_check_payment;
	}

	function arf_paypal_payment_detail( $entry_id ) {

		global $arflitemainhelper,$arf_paypal;
		$var = '';

		if ( $entry_id ) {
			global $wpdb,$arformcontroller,$arfform;
			
			$payment_detail = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $arf_paypal->db_paypal_order . ' WHERE entry_id = %d', $entry_id ) );//phpcs:ignore 
			
			if ( isset( $payment_detail ) && '' != $payment_detail && is_array( $payment_detail ) && isset( $payment_detail[0] ) ) {
				$payment_detail = $payment_detail[0];
			}

			if ( ! empty( $payment_detail ) ) {

				$var .= '</tbody></table><div class="arfentrydivider">' . esc_html__( 'Payment Details', 'arforms-form-builder' ) . '</div><table class="form-table"><tbody>';

				$var .= '<tr class="arfviewentry_row" scope="row"><td class="arfviewentry_left"><strong>' . esc_html__( 'Transaction ID', 'arforms-form-builder' ) . ':</strong></td><td class="arfviewentry_right">' . $payment_detail->txn_id . '</td></tr>';

				$var .= '<tr class="arfviewentry_row" scope="row"><td class="arfviewentry_left"><strong>' . esc_html__( 'Payment Status', 'arforms-form-builder' ) . ':</strong></td><td class="arfviewentry_right">' . ( ( 'Completed' == $payment_detail->payment_status ) ? '<font class="arf_pp_complete_status">' . $payment_detail->payment_status . '</font>' : '<font class="arf_pp_incomplete_status">' . $payment_detail->payment_status . '</font>' ) . '</td></tr>';

				
					$var .= '<tr class="arfviewentry_row" scope="row"><td class="arfviewentry_left"><strong>' . esc_html__( 'Total Amount', 'arforms-form-builder' ) . ':</strong></td><td class="arfviewentry_right">' . $payment_detail->mc_gross . ' ' . $payment_detail->mc_currency . '</td></tr>';
				

				$date_format  = get_option( 'date_format' );
				$time_format  = get_option( 'time_format' );
				$payment_date = $arflitemainhelper->arflite_get_formatted_time( $payment_detail->created_at, $date_format, $time_format );

				$var .= '<tr class="arfviewentry_row" scope="row"><td class="arfviewentry_left"><strong>' . esc_html__( 'Payment at', 'arforms-form-builder' ) . ':</strong></td><td class="arfviewentry_right">' . $payment_date . '</td></tr>';

				$var .= '<tr class="arfviewentry_row" scope="row"><td class="arfviewentry_left"><strong>' . esc_html__( 'Payer email', 'arforms-form-builder' ) . ':</strong></td><td class="arfviewentry_right">' . $payment_detail->payer_email . '</td></tr>';

				$var .= '<tr class="arfviewentry_row" scope="row"><td class="arfviewentry_left"><strong>' . esc_html__( 'Payer name', 'arforms-form-builder' ) . ':</strong></td><td class="arfviewentry_right">' . $payment_detail->payer_name . '</td></tr>';
			}
		}

		return $var;
	}

	function currency_list() {
		$currency = array( 'USD', 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'TWD', 'THB' );

		return $currency;
	}

	function currency_symbol( $currency_code = 'USD' ) {
		$currency_symbol = array(
			'USD' => 'USD   &nbsp;( &nbsp;&nbsp;$&nbsp;&nbsp;)',
			'AUD' => 'AUD   &nbsp;( &nbsp;&nbsp;$&nbsp;&nbsp;)',
			'BRL' => 'BRL   &nbsp;( &nbsp;&nbsp;R$&nbsp;&nbsp;)',
			'CAD' => 'CAD   &nbsp;( &nbsp;&nbsp;$&nbsp;&nbsp;)',
			'CZK' => 'CZK   &nbsp;( &nbsp;&nbsp;&#75;&#269;&nbsp;&nbsp;)',
			'DKK' => 'DKK   &nbsp;( &nbsp;&nbsp;&#107;&#114;&nbsp;&nbsp;)',
			'EUR' => 'EUR   &nbsp;( &nbsp;&nbsp;&#128;&nbsp;&nbsp;)',
			'HKD' => 'HKD   &nbsp;( &nbsp;&nbsp;&#20803;&nbsp;&nbsp;)',
			'HUF' => 'HUF   &nbsp;( &nbsp;&nbsp;&#70;&#116;&nbsp;&nbsp;)',
			'ILS' => 'ILS   &nbsp;( &nbsp;&nbsp;&#8362;&nbsp;&nbsp;)',
			'JPY' => 'JPY   &nbsp;( &nbsp;&nbsp;&#165;&nbsp;&nbsp;)',
			'MYR' => 'MYR   &nbsp;( &nbsp;&nbsp;&#82;&#77;&nbsp;&nbsp;)',
			'MXN' => 'MXN   &nbsp;( &nbsp;&nbsp;&#36;&nbsp;&nbsp;)',
			'NOK' => 'NOK   &nbsp;( &nbsp;&nbsp;&#107;&#114;&nbsp;&nbsp;)',
			'NZD' => 'NZD   &nbsp;( &nbsp;&nbsp;&#36;&nbsp;&nbsp;)',
			'PHP' => 'PHP   &nbsp;( &nbsp;&nbsp;&#80;&#104;&#8369;&nbsp;&nbsp;)',
			'PLN' => 'PLN   &nbsp;( &nbsp;&nbsp;&#122;&#322;&nbsp;&nbsp;)',
			'GBP' => 'GBP   &nbsp;( &nbsp;&nbsp;&#163;&nbsp;&nbsp;)',
			'RUB' => 'RUB   &nbsp;( &nbsp;&nbsp;&#1088;&#1091;&nbsp;&nbsp;)',
			'SGD' => 'SGD   &nbsp;( &nbsp;&nbsp;&#36;&nbsp;&nbsp;)',
			'SEK' => 'SEK   &nbsp;( &nbsp;&nbsp;&#107;&#114;&nbsp;&nbsp;)',
			'CHF' => 'CHF   &nbsp;( &nbsp;&nbsp;&#67;&#72;&#70;&nbsp;&nbsp;)',
			'TWD' => 'TWD   &nbsp;( &nbsp;&nbsp;&#36;&nbsp;&nbsp;)',
			'THB' => 'THB   &nbsp;( &nbsp;&nbsp;&#3647;&nbsp;&nbsp;)',
		);

		return $currency_symbol;
	}

	function arf_paypal_delete_order() {
		if ( empty( $_POST['wp_arflite_paypal_nonce'] ) || ( isset( $_POST['wp_arflite_paypal_nonce'] ) && '' != $_POST['wp_arflite_paypal_nonce'] && ! wp_verify_nonce( sanitize_text_field( $_POST['wp_arflite_paypal_nonce'] ), 'arf_paypal_order_nonce' ) ) ) {
			echo esc_attr( 'security_error' );
			die;
		}
		if ( ! current_user_can( 'arfpaypaltransaction' ) ) {
			$status['errorMessage'] = esc_html__( 'Sorry, you do not have permission to perform this action', 'arforms-form-builder' );
			wp_send_json_error( $status );
		}

		$id      = isset( $_POST['id'] ) ? sanitize_text_field($_POST['id']) : '';
		$action  = isset( $_POST['act'] ) ? sanitize_text_field($_POST['act']) : '';
		$form_id = isset( $_POST['form_id'] ) ? sanitize_text_field($_POST['form_id']) : '';

		$startdate = isset( $_POST['start_date'] ) ? sanitize_text_field($_POST['start_date']) : '';
		$enddate   = isset( $_POST['end_date'] ) ? sanitize_text_field($_POST['end_date']) : '';

		global $style_settings, $wp_scripts;
		$wp_format_date = get_option( 'date_format' );

		if ( $wp_format_date == 'F j, Y' || $wp_format_date == 'm/d/Y' ) {
			$date_format_new = 'mm/dd/yy';
		} elseif ( $wp_format_date == 'd/m/Y' ) {
			$date_format_new = 'dd/mm/yy';
		} elseif ( $wp_format_date == 'Y/m/d' ) {
			$date_format_new = 'dd/mm/yy';
		} else {
			$date_format_new = 'mm/dd/yy';
		}

		$datequery = '';
		if ( '' != $startdate && '' != $enddate ) {
			if ( 'dd/mm/yy' == $date_format_new ) {
				$startdate = str_replace( '/', '-', $startdate );
				$enddate   = str_replace( '/', '-', $enddate );
			}
			$new_start_date_var = date( 'Y-m-d', strtotime( $startdate ) );
			$new_end_date_var   = date( 'Y-m-d', strtotime( $enddate ) );

			$datequery .= " and DATE( created_at) >= '" . $new_start_date_var . "' and DATE( created_at) <= '" . $new_end_date_var . "'";
		} elseif ( $startdate != '' && $enddate == '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$startdate = str_replace( '/', '-', $startdate );
			}
			$new_start_date_var = date( 'Y-m-d', strtotime( $startdate ) );

			$datequery .= " and DATE( created_at) >= '" . $new_start_date_var . "'";
		} elseif ( $startdate == '' && $enddate != '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$enddate = str_replace( '/', '-', $enddate );
			}
			$new_end_date_var = date( 'Y-m-d', strtotime( $enddate ) );

			$datequery .= " and DATE( created_at) <= '" . $new_end_date_var . "'";
		}


		if ( $action == 'delete' && $id ) {
			$res     = $this->delete_orders( $id );
			$message = esc_html__( 'Record is deleted successfully.', 'arforms-form-builder' );
			$errors  = array();

			$gridData = $this->arf_paypal_get_order_data( $form_id, $datequery );
			echo wp_json_encode(
				array(
					'errors'   => $errors,
					'message'  => $message,
					'gridData' => $gridData,
				)
			);
		 
		}
		
		die();
	}

	function delete_orders( $id = 0 ) {
		if ( $id == 0 ) {
			return;
		}

		if ( $id ) {
			global $wpdb,$arf_paypal;
			$res = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $arf_paypal->db_paypal_order . ' WHERE id = %d', $id ) );//phpcs:ignore 

			return $res;
		}
	}

	function arf_paypal_order_bulk_act() {
		global $arfform,$arflitemainhelper;

		if ( empty( $_POST['wp_arflite_paypal_nonce'] ) || ( isset( $_POST['wp_arflite_paypal_nonce'] ) && '' != $_POST['wp_arflite_paypal_nonce'] && ! wp_verify_nonce( sanitize_text_field( $_POST['wp_arflite_paypal_nonce'] ), 'arf_paypal_order_nonce' ) ) ) {
			echo esc_attr( 'security_error' );
			die;
		}
		if ( ! current_user_can( 'arfpaypaltransaction' ) ) {
			$status['errorMessage'] = esc_html__( 'Sorry, you do not have permission to perform this action', 'arforms-form-builder' );
			wp_send_json_error( $status );
		}

		if ( ! isset( $_POST ) ) {
			return;
		}
		
		$bulkaction = $arflitemainhelper->arflite_get_param( 'action1' );

		$message = '';

		$errors = array();
		

		if ( $bulkaction == -1 ) {
			$bulkaction = $arflitemainhelper->arflite_get_param( 'action3' );
		}

		if ( ! empty( $bulkaction ) and strpos( $bulkaction, 'bulk_' ) === 0 ) {
			$_SERVER['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
			if ( isset( $_GET ) and isset( $_GET['action1'] ) ) {
				$_SERVER['REQUEST_URI'] = str_replace( '&action=' . sanitize_text_field($_GET['action1']), '', sanitize_text_field($_SERVER['REQUEST_URI']) );
			}

			if ( isset( $_GET ) and isset( $_GET['action3'] ) ) {
				$_SERVER['REQUEST_URI'] = str_replace( '&action=' . sanitize_text_field($_GET['action3']), '', sanitize_text_field($_SERVER['REQUEST_URI']) );
			}

			$bulkaction = str_replace( 'bulk_', '', $bulkaction );
		} else {
			$bulkaction = '-1';
			$_POST['bulkaction1'] = isset($_POST['bulkaction1']) ? sanitize_text_field($_POST['bulkaction1']) : '';
			if ( isset( $_POST['bulkaction'] ) and $_POST['bulkaction1'] != '-1' ) {
				$bulkaction = sanitize_text_field($_POST['bulkaction1']);

			} elseif ( isset( $_POST['bulkaction2'] ) and $_POST['bulkaction2'] != '-1' ) {
				$bulkaction = sanitize_text_field($_POST['bulkaction2']);
			}
		}

		$ids = $_POST['item-action'];

		$form_id   = !empty($_POST['p_form_id']) ? sanitize_text_field($_POST['p_form_id']) : '';
		$startdate = !empty($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
		$enddate   = !empty($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

		global $style_settings, $wp_scripts;
		$wp_format_date = get_option( 'date_format' );

		if ( $wp_format_date == 'F j, Y' || $wp_format_date == 'm/d/Y' ) {
			$date_format_new = 'mm/dd/yy';
		} elseif ( $wp_format_date == 'd/m/Y' ) {
			$date_format_new = 'dd/mm/yy';
		} elseif ( $wp_format_date == 'Y/m/d' ) {
			$date_format_new = 'dd/mm/yy';
		} else {
			$date_format_new = 'mm/dd/yy';
		}

		$datequery = '';
		if ( $startdate != '' and $enddate != '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$startdate = str_replace( '/', '-', $startdate );
				$enddate   = str_replace( '/', '-', $enddate );
			}
			$new_start_date_var = date( 'Y-m-d', strtotime( $startdate ) );
			$new_end_date_var   = date( 'Y-m-d', strtotime( $enddate ) );

			$datequery .= " and DATE( created_at) >= '" . $new_start_date_var . "' and DATE( created_at) <= '" . $new_end_date_var . "'";
		} elseif ( $startdate != '' and $enddate == '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$startdate = str_replace( '/', '-', $startdate );
			}
			$new_start_date_var = date( 'Y-m-d', strtotime( $startdate ) );

			$datequery .= " and DATE( created_at) >= '" . $new_start_date_var . "'";
		} elseif ( $startdate == '' and $enddate != '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$enddate = str_replace( '/', '-', $enddate );
			}
			$new_end_date_var = date( 'Y-m-d', strtotime( $enddate ) );

			$datequery .= " and DATE( created_at) <= '" . $new_end_date_var . "'";
		}

		if ( empty( $ids ) ) {
			$errors[] = esc_html__( 'Please select one or more records.', 'arforms-form-builder' );
		} else {
			if ( ! is_array( $ids ) ) {
				$ids = explode( ',', $ids );
			}

			if ( is_array( $ids ) ) {
				if ( $bulkaction == 'delete' ) {

					foreach ( $ids as $oid ) {
						$res_var = $this->delete_orders( $oid );
					}

					if ( $res_var ) {
						$message = esc_html__( 'Record is deleted successfully.', 'arforms-form-builder' );
					}
				} elseif ( $bulkaction == 'csv' ) {
					echo wp_json_encode(
						array(
							'errors'   => $errors,
							'message'  => 'csv',
							'url'      => ARFSCRIPTURL . '-Paypal&arfaction=csv&entry_id=' . implode( ',', $ids ),
							'gridData' => $this->arf_paypal_get_order_data( $form_id, $datequery )
						)
					);
					die;
				}
			}
		}

		 
		$gridData = $this->arf_paypal_get_order_data( $form_id, $datequery );
		echo wp_json_encode(
			array(
				'errors'   => $errors,
				'message'  => $message,
				'gridData' => $gridData,
			)
		);
		
		die();
	}

	function arf_paypal_get_order_data( $form_id, $datequery ) {
		global $wpdb, $arfliterecordcontroller, $arf_paypal,$arfliteformcontroller,$style_settings, $wp_scripts;

		$orderData = array();

		if ( isset( $form_id ) and $form_id != '' ) {
			if ( $datequery != '' ) {
				
				//$orders = $arfform->arf_select_db_data( true, '', $arf_paypal->db_paypal_order, '*', 'WHERE form_id = %d ' . $datequery, array( $form_id ), '', 'ORDER BY id DESC' );
				$orders = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $arf_paypal->db_paypal_order . ' WHERE form_id = %d ' . $datequery . ' ORDER BY id DESC', $form_id ) );//phpcs:ignore 

			} else {
				
				//$orders = $arfform->arf_select_db_data( true, '', $arf_paypal->db_paypal_order, '*', 'WHERE form_id = %d', array( $form_id ), '', 'ORDER BY id DESC' );
				$orders = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $arf_paypal->db_paypal_order . ' WHERE form_id = %d ORDER BY id DESC', $form_id ) );//phpcs:ignore
			}
		} else {
			if ( $datequery != '' ) {
				
				//$orders = $arfform->arf_select_db_data( true, '', $arf_paypal->db_paypal_order, '*', "WHERE id != '' " . $datequery, array(), '', 'ORDER BY id DESC' );
				$orders = $wpdb->get_results( 'SELECT * FROM ' . $arf_paypal->db_paypal_order . " WHERE id!='' " . $datequery . ' ORDER BY id DESC' );//phpcs:ignore

			} else {
				
				//$orders = $arfform->arf_select_db_data( true, '', $arf_paypal->db_paypal_order, '*', '', array(), '', 'ORDER BY id DESC' );
				$orders = $wpdb->get_results( 'SELECT * FROM ' . $arf_paypal->db_paypal_order . ' ORDER BY id DESC' );//phpcs:ignore
			}
		}

		if ( count( $orders ) > 0 ) {
			$o = 0;
			foreach ( $orders as $order ) {
				if ( ! isset( $orderData[ $o ] ) ) {
					$orderData[ $o ] = array();
				}
				
				$orderData[ $o ][] = '<div class="arf_custom_checkbox_wrapper arfmarginl15"><input id="cb-item-action-' . $order->id . '" type="checkbox" value="' . $order->id . '" name="item-action[]" /><svg width="18px" height="18px">' . ARFLITE_CUSTOM_UNCHECKED_ICON . ARFLITE_CUSTOM_CHECKED_ICON . '</svg></div><label for="cb-item-action-' . $order->id . '"><span></span></label>';
				
				$orderData[ $o ][] = $order->txn_id;
				$orderData[ $o ][] = ( $order->payment_status == 'Completed' ) ? "<font class='arf_pp_complete_status'>" . $order->payment_status . '</font>' : "<font class='arf_pp_incomplete_status'>" . $order->payment_status . '</font>';

				$orderData[ $o ][] = $arfliteformcontroller->arflitedeciamlseparator( $order->mc_gross ) . ' ' . $order->mc_currency;
				
				if ( isset( $order->payment_type ) && 1 == $order->payment_type ) {
					$orderData[ $o ][] = esc_html__( 'Donations', 'arforms-form-builder' );
				}else {
					$orderData[ $o ][] = esc_html__( 'Product / Service', 'arforms-form-builder' );
				}

				$orderData[ $o ][] = date( get_option( 'date_format' ), strtotime( $order->created_at ) );
				$orderData[ $o ][] = $order->payer_email;
				$orderData[ $o ][] = $order->payer_name;

				$action_btn = '<div class="arf-row-actions">';

				$action_btn .= "<div class='arfformicondiv arfhelptip' title='" . esc_html__( 'View Entry', 'arforms-form-builder' ) . "'><a href='javascript:void( 0);' onclick='open_entry_thickbox( {$order->entry_id});'><svg width='30px' height='30px' viewBox='-3 -8 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M12.993,15.23c-7.191,0-11.504-7.234-11.504-7.234  S5.801,0.85,12.993,0.85c7.189,0,11.504,7.19,11.504,7.19S20.182,15.23,12.993,15.23z M12.993,2.827 c-5.703,0-8.799,5.214-8.799,5.214s3.096,5.213,8.799,5.213c5.701,0,8.797-5.213,8.797-5.213S18.694,2.827,12.993,2.827z M12.993,11.572c-1.951,0-3.531-1.581-3.531-3.531s1.58-3.531,3.531-3.531c1.949,0,3.531,1.581,3.531,3.531  S14.942,11.572,12.993,11.572z'/></svg></a></div>";

				$action_btn .= "<div class='arfformicondiv arfhelptip arfdeleteentry_div_" . $order->id . "' title='" . esc_html__( 'Delete', 'arforms-form-builder' ) . "'><a class='arf_delete_entry' data-id='" . $order->id . "'><svg width='30px' height='30px' viewBox='-5 -5 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M18.435,4.857L18.413,19.87L3.398,19.88L3.394,4.857H1.489V2.929  h1.601h3.394V0.85h8.921v2.079h3.336h1.601l0,0v1.928H18.435z M15.231,4.857H6.597H5.425l0.012,13.018h10.945l0.005-13.018H15.231z   M11.4,6.845h2.029v9.065H11.4V6.845z M8.399,6.845h2.03v9.065h-2.03V6.845z' /></svg></a></div>";

				$action_btn .= "<div id='view_entry_detail_container_{$order->entry_id}' class='arf_pp_display_none'>" . $arfliterecordcontroller->arflite_get_entries_list( $order->entry_id ) . "</div><div class='arfmnarginbtm10 arf_clear_both'></div>";

				$action_btn .= '</div>';
				

				$orderData[ $o ][] = $action_btn;
				$o++;
			}
		}

		return $orderData;
	}

	function list_forms( $message = '' ) {
		global $arf_paypal_version;
		
			$file = 'list_forms_3.0.php';
		
		include ARFLITE_FORMPATH . '/integrations/Payments/PayPal/core/' . $file;
	}

	function arf_paypal_delete_form() {

		if ( empty( $_POST['wp_arf_paypal_forms_nonce'] ) || ( isset( $_POST['wp_arf_paypal_forms_nonce'] ) && '' != $_POST['wp_arf_paypal_forms_nonce'] && ! wp_verify_nonce( sanitize_text_field( $_POST['wp_arf_paypal_forms_nonce'] ), 'arf_paypal_form_list_nonce' ) ) ) {
			echo esc_attr( 'security_error' );
			die;
		}

		if ( ! current_user_can( 'arfpaypalconfiguration' ) ) {
			$status['errorMessage'] = esc_html__( 'Sorry, you do not have permission to perform this action', 'arforms-form-builder' );
			wp_send_json_error( $status );
		}

		$id     = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
		$action = !empty($_POST['act']) ? sanitize_text_field($_POST['act']) : '';
		if ( $action == 'delete' && $id ) {
			$res     = $this->delete_forms( $id );
			$message = esc_html__( 'Record is deleted successfully.', 'arforms-form-builder' );
			$errors  = array();
			
			echo wp_json_encode(
				array(
					'errors'   => $errors,
					'message'  => $message,
					'gridData' => $this->arf_get_paypal_form_data(),
				)
			);
			
		}
		die();
	}

	function delete_forms( $id = 0 ) {
		if ( $id == 0 ) {
			return;
		}

		if ( $id ) {
			global $wpdb,$arf_paypal;
			$form = $wpdb->get_results( $wpdb->prepare( 'SELECT form_id FROM ' . $arf_paypal->db_paypal_forms . ' WHERE id = %d', $id ) );//phpcs:ignore
			$form = $form[0];

			if ( isset( $form->form_id ) and $form->form_id != '' ) {
				$res = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $arf_paypal->db_paypal_order . ' WHERE form_id = %d', $form->form_id ) );//phpcs:ignore
			}

			$res = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $arf_paypal->db_paypal_forms . ' WHERE id = %d', $id ) );//phpcs:ignore

			return $res;
		}
	}

	function arf_paypal_form_bulk_act() {

		if ( empty( $_POST['wp_arf_paypal_forms_nonce'] ) || ( isset( $_POST['wp_arf_paypal_forms_nonce'] ) && '' != $_POST['wp_arf_paypal_forms_nonce'] && ! wp_verify_nonce( sanitize_text_field( $_POST['wp_arf_paypal_forms_nonce'] ), 'arf_paypal_form_list_nonce' ) ) ) {
			echo esc_attr( 'security_error' );
			die;
		}

		if ( ! current_user_can( 'arfpaypalconfiguration' ) ) {
			$status['errorMessage'] = esc_html__( 'Sorry, you do not have permission to perform this action', 'arforms-form-builder' );
			wp_send_json_error( $status );
		}

		global $arflitemainhelper;

		if ( ! isset( $_POST ) ) {
			return;
		}

		global $arfform;

		$bulkaction = $arflitemainhelper->arflite_get_param( 'action1' );
		
		$message = '';

		$errors = array();


		if ( $bulkaction == -1 ) {
			$bulkaction = $arflitemainhelper->arflite_get_param( 'action3' );
		}
		
		if ( ! empty( $bulkaction ) and strpos( $bulkaction, 'bulk_' ) === 0 ) {
			
			$_SERVER['REQUEST_URI'] = !empty($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
			if ( isset( $_GET ) and isset( $_GET['action1'] ) ) {
				$_SERVER['REQUEST_URI'] = str_replace( '&action=' . sanitize_text_field($_GET['action1']), '', sanitize_text_field($_SERVER['REQUEST_URI'] ));
			}

			if ( isset( $_GET ) and isset( $_GET['action3'] ) ) {
				$_SERVER['REQUEST_URI'] = str_replace( '&action=' . sanitize_text_field($_GET['action3']), '', sanitize_text_field($_SERVER['REQUEST_URI']) );
			}

			$bulkaction = str_replace( 'bulk_', '', $bulkaction );
						
		} else {
			$bulkaction = '-1';

			$_POST['bulkaction1'] = !empty($_POST['bulkaction1']) ? sanitize_text_field($_POST['bulkaction1']) : '';
			if ( isset( $_POST['bulkaction'] ) and $_POST['bulkaction1'] != '-1' ) {
				$bulkaction = sanitize_text_field($_POST['bulkaction1']);

			} elseif ( isset( $_POST['bulkaction3'] ) and $_POST['bulkaction3'] != '-1' ) {
				$bulkaction = sanitize_text_field($_POST['bulkaction3']);
			}
		}
		
		
		$ids = $_POST['item-action'];
		
		
		if ( empty( $ids ) ) {
			$errors[] = esc_html__( 'Please select one or more records', 'arforms-form-builder' );
		} else {
			if ( ! is_array( $ids ) ) {
				$ids = explode( ',', $ids );
			}

			if ( is_array( $ids ) ) {
				if ( $bulkaction == 'delete' ) {

					foreach ( $ids as $fid ) {
						$res_var = $this->delete_forms( $fid );
					}

					if ( $res_var ) {
						$message = esc_html__( 'Record is deleted successfully.', 'arforms-form-builder' );
					}
				}
			}
		}

		 
		echo wp_json_encode(
			array(
				'errors'   => $errors,
				'message'  => $message,
				'gridData' => $this->arf_get_paypal_form_data(),
			)
		);
		

		die();
	}

	function arf_get_paypal_form_data() {
		global $wpdb, $arf_paypal, $arfliteformcontroller;

		$rowData = array();
		$forms   = $wpdb->get_results( 'SELECT * FROM ' . $arf_paypal->db_paypal_forms . ' ORDER BY id DESC' );//phpcs:ignore
		if ( count( $forms ) > 0 ) {
			$n = 0;
			foreach ( $forms as $form ) {
				$options = maybe_unserialize( $form->options );
				if ( ! isset( $rowData[ $n ] ) ) {
					$rowData[ $n ] = array();
				}

				$rowData[ $n ][] = "<div class='arf_custom_checkbox_wrapper arfmarginl15'><input id='cb-item-action-" . $form->id . "' type='checkbox' value='" . $form->id . "'  name='item-action[]' /><svg width='18px' height='18px'>" . ARFLITE_CUSTOM_UNCHECKED_ICON . ARFLITE_CUSTOM_CHECKED_ICON . "</svg></div><label for='cb-item-action-" . $form->id . "'></label>";
				
				$rowData[ $n ][] = $form->form_id;

				$rowData[ $n ][] = "<a class='row-title' href='" . wp_nonce_url( "?page=ARForms-Paypal&arfaction=edit&id={$form->id}" ) . "'>" . $form->form_name . '</a>';

				$record_count = $wpdb->get_var( $wpdb->prepare( 'SELECT count( *) AS record_count FROM ' . $arf_paypal->db_paypal_order . ' WHERE form_id = %d', $form->form_id ) );//phpcs:ignore

				$rowData[ $n ][] = "<a href='" . wp_nonce_url( '?page=ARForms-Paypal-order&form=' . $form->form_id ) . "'>" . $record_count . '</a>';

				$total_amount = $wpdb->get_var( $wpdb->prepare( 'SELECT SUM( mc_gross) AS total_amount FROM ' . $arf_paypal->db_paypal_order . ' WHERE form_id = %d', $form->form_id ) );//phpcs:ignore
				
				$rowData[ $n ][] = ( ( isset( $total_amount ) ) ? $arfliteformcontroller->arflitedeciamlseparator( $total_amount ) : $arfliteformcontroller->arflitedeciamlseparator( 0 ) ) . ' ' . $options['currency'];

				$rowData[ $n ][] = date( get_option( 'date_format' ), strtotime( $form->created_at ) );

				$edit_link = "?page=ARForms-Paypal&arfaction=edit&id={$form->id}";

				$action_div = "<div class='arf-row-actions'>";

				$action_div .= "<div class='arfformicondiv arfhelptip' title='" . esc_html__( 'Edit Configuration', 'arforms-form-builder' ) . "'><a href='" . wp_nonce_url( $edit_link ) . "'><svg width='30px' height='30px' viewBox='-5 -4 30 30' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill='#ffffff' d='M17.469,7.115v10.484c0,1.25-1.014,2.264-2.264,2.264H3.75c-1.25,0-2.262-1.014-2.262-2.264V5.082  c0-1.25,1.012-2.264,2.262-2.264h9.518l-2.264,2.001H3.489v13.042h11.979V9.379L17.469,7.115z M15.532,2.451l-0.801,0.8l2.4,2.401  l0.801-0.8L15.532,2.451z M17.131,0.85l-0.799,0.801l2.4,2.4l0.801-0.801L17.131,0.85z M6.731,11.254l2.4,2.4l7.201-7.202  l-2.4-2.401L6.731,11.254z M5.952,14.431h2.264l-2.264-2.264V14.431z'></path></svg></a></div>";

				$action_div .= "<div class='arfformicondiv arfhelptip' title='" . esc_html__( 'Transactions', 'arforms-form-builder' ) . "'><a href='" . wp_nonce_url( "?page=ARForms-Paypal-order&form={$form->form_id}" ) . "'><svg width='30px' height='30px' viewBox='-5 -4 30 30' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill='#ffffff' d='M12.32,5.952c1.696-1.316,2.421-2.171,2.747-3.272c0.307-1.039-0.35-2.396-1.703-2.576    c-0.881-0.114-2.071,0.374-3.53,0.811c-0.477,0.143-0.979,0.143-1.451,0c-1.459-0.432-2.653-0.965-3.53-0.811    C3.234,0.389,2.892,1.73,3.149,2.68C3.45,3.789,4.2,4.635,5.896,5.952c-2.319,1.745-4.889,6.095-4.889,8.504    c0,3.314,3.854,5.647,8.101,5.647s8.141-2.333,8.141-5.647C17.249,12.047,14.639,7.696,12.32,5.952z M4.762,2.231    c-0.04-0.143-0.068-0.399,0.311-0.469c0.444-0.082,1.3-0.227,2.837,0.229c0.786,0.232,1.618,0.232,2.405,0    c1.536-0.457,2.393-0.307,2.837-0.229c0.313,0.053,0.346,0.326,0.31,0.469c-0.285,1.019-1.02,1.817-2.797,2.824    C10.167,4.884,9.65,4.79,9.116,4.79c-0.533,0-1.056,0.094-1.549,0.265C5.778,4.048,5.043,3.247,4.762,2.231z M9.108,18.093    c-2.462,0-5.51-0.747-5.51-3.637c0-2.633,2.624-8.007,5.51-8.007s5.471,5.374,5.471,8.007    C14.579,17.346,11.615,18.093,9.108,18.093z M9.202,12.316c-0.408,0-0.742-0.334-0.742-0.742s0.334-0.742,0.742-0.742    c0.208,0,0.399,0.082,0.542,0.232c0.27,0.286,0.722,0.302,1.007,0.033s0.302-0.721,0.033-1.007    c-0.241-0.257-0.539-0.448-0.869-0.563H8.489c-0.849,0.298-1.456,1.101-1.456,2.046c0,1.194,0.975,2.168,2.169,2.168    c0.407,0,0.742,0.334,0.742,0.742c0,0.408-0.335,0.742-0.742,0.742c-0.208,0-0.399-0.082-0.542-0.232    c-0.27-0.285-0.722-0.302-1.007-0.033s-0.302,0.722-0.033,1.007c0.241,0.257,0.538,0.449,0.869,0.563c0,0,0.738,0.281,1.426,0    c0.849-0.297,1.455-1.101,1.455-2.046C11.37,13.286,10.396,12.316,9.202,12.316z'/></svg></a></div>";

				$action_div .= "<div class='arfformicondiv arfhelptip arfdeleteform_div_" . $form->id . "' title='" . esc_html__( 'Delete', 'arforms-form-builder' ) . "'><a class='arf_paypal_delete' data-id='" . $form->id . "'><svg width='30px' height='30px' viewBox='-5 -5 32 32' class='arfsvgposition'><path xmlns='http://www.w3.org/2000/svg' fill-rule='evenodd' clip-rule='evenodd' fill='#ffffff' d='M18.435,4.857L18.413,19.87L3.398,19.88L3.394,4.857H1.489V2.929  h1.601h3.394V0.85h8.921v2.079h3.336h1.601l0,0v1.928H18.435z M15.231,4.857H6.597H5.425l0.012,13.018h10.945l0.005-13.018H15.231z   M11.4,6.845h2.029v9.065H11.4V6.845z M8.399,6.845h2.03v9.065h-2.03V6.845z' /></svg></a></div>";

				$action_div .= '</div>';
				 

				$rowData[ $n ][] = $action_div;

				$n++;
			}
		}

		return $rowData;

	}

	function arf_paypal_save_settings_callback() {
		global $wpdb, $arfliteform, $armainhelper, $arf_paypal_version, $arflitefieldhelper, $arflitenotifymodel, $arf_paypal, $arflitefield, $tbl_arf_forms;

		$check_cap = $this->arf_paypal_check_user_cap( 'arfpaypalconfiguration', true );

		if ( 'success' != $check_cap ) {
			$user_cap = json_decode( $check_cap, true );
			echo json_encode(
				array(
					'success' => false,
					'message' => $user_cap[0],
				)
			);
			die;
		}

		if ( isset( $_REQUEST['arfaction'] ) && 'edit' == $_REQUEST['arfaction'] ) {
			$id = isset( $_REQUEST['id'] ) ? sanitize_text_field($_REQUEST['id']) : '';

			if ( '' == $id ) {
				echo wp_json_encode(
					array(
						'success' => false,
						'message' => 'redirect',
						'url'     => admin_url( 'admin.php?page=ARForms-Paypal&err=1' ),
					)
				);
				die;
			} else {
				$arf_form_chk = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM `' . $tbl_arf_forms . '` WHERE id = %d', $_REQUEST['form_id'] ), ARRAY_A );//phpcs:ignore
				$form_chk     = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM `' . $arf_paypal->db_paypal_forms . '` WHERE id = %d', $id ), ARRAY_A );//phpcs:ignore
				if ( count( $arf_form_chk ) == 0 || count( $form_chk ) == 0 ) {
					echo wp_json_encode(
						array(
							'success' => false,
							'message' => 'redirect',
							'url'     => admin_url( 'admin.php?page=ARForms-Paypal&err=1' ),
						)
					);
					die;
				} else {
					$form_data = !empty($_REQUEST['form_id']) ? $arfliteform->arflitegetOne( sanitize_text_field($_REQUEST['form_id']) ) : '';

					$new_values['form_name'] = $form_data->name;

					$options['merchant_email'] = !empty($_REQUEST['arf_paypal_email']) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_email']) ) : '';

					$options['paypal_mode'] = esc_attr($_REQUEST['arf_paypal_mode']) ;

					$options['cancel_url'] = !empty($_REQUEST['arf_paypal_cancel_url']) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_cancel_url'] )) : '';

					$options['notification'] = isset( $_REQUEST['arf_paypal_notification'] ) ? 1 : 0;

					$options['user_notification']  = isset( $_REQUEST['arf_paypal_user_notification'] ) ? 1 : 0;
					$options['user_email_content'] = isset( $_REQUEST['user_email_content'] ) ? esc_attr(sanitize_text_field($_REQUEST['user_email_content'])) : '' ;

					$options['title'] = isset($_REQUEST['arf_paypal_title']) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_title'] )) : '';

					$options['currency'] = isset($_REQUEST['arf_paypal_currency']) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_currency'] )) : '';

					$options['first_name'] = isset($_REQUEST['arf_first_name']) ? esc_attr( sanitize_text_field($_REQUEST['arf_first_name'] )) : '';

					$options['last_name'] = isset($_REQUEST['arf_last_name']) ? esc_attr( sanitize_text_field($_REQUEST['arf_last_name'] )) : '';

					$options['email'] = isset($_REQUEST['arf_email']) ? esc_attr( sanitize_text_field($_REQUEST['arf_email'] ) ) : '';

					$options['state'] = isset($_REQUEST['arf_state']) ? esc_attr(sanitize_text_field($_REQUEST['arf_state'] )) : '';

					$options['address'] = isset($_REQUEST['arf_address']) ? esc_attr( sanitize_text_field($_REQUEST['arf_address'] )) : '';

					$options['address_2'] = isset($_REQUEST['arf_address_2']) ? esc_attr( sanitize_text_field($_REQUEST['arf_address_2'] )) : '';

					$options['city'] = isset($_REQUEST['arf_city']) ? esc_attr( sanitize_text_field($_REQUEST['arf_city'] )) : '';

					$options['zip'] = isset($_REQUEST['arf_zip']) ? esc_attr( sanitize_text_field($_REQUEST['arf_zip'] )) : '';

					$options['country'] = isset($_REQUEST['arf_country']) ? esc_attr( sanitize_text_field($_REQUEST['arf_country'] )) : '';

					$options['amount'] = isset($_REQUEST['arf_amount']) ? esc_attr( $_REQUEST['arf_amount'] ) : '';

					$options['payment_type'] = isset($_REQUEST['arf_paypal_payment_type']) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_payment_type'] )) : '';

					$options['email_content'] = isset($_REQUEST['email_content']) ? esc_attr( sanitize_text_field($_REQUEST['email_content']) ) : '';

					$options['shipping_info'] = ( isset( $_REQUEST['shipping_info'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['shipping_info'] )) : 0;

					$options['paypal_condition'] = ( isset( $_REQUEST['arf_paypal_condition'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_condition'] )) : 0;

					$options['arf_payment_type'] = isset($_REQUEST['arf_payment_type']) ? esc_attr( sanitize_text_field($_REQUEST['arf_payment_type'] )) : '';

					$options['arf_multiple_product_service_type']           = ( isset( $_REQUEST['arf_multiple_product_service_type'] ) ) ? esc_attr(sanitize_text_field( $_REQUEST['arf_multiple_product_service_type'] )) : 0;
					$options['arf_cl_field_multiple_product_service_type']  = isset($_REQUEST['arf_cl_field_multiple_product_service_type']) ? esc_attr( sanitize_text_field($_REQUEST['arf_cl_field_multiple_product_service_type'] )) : '';
					$options['arf_cl_op_multiple_product_service_type']     = isset($_REQUEST['arf_cl_op_multiple_product_service_type']) ? esc_attr( sanitize_text_field($_REQUEST['arf_cl_op_multiple_product_service_type'] )) : '';
					$options['cl_rule_value_multiple_product_service_type'] = isset($_REQUEST['cl_rule_value_multiple_product_service_type']) ? esc_attr( sanitize_text_field($_REQUEST['cl_rule_value_multiple_product_service_type'] )) : '';
					$options['arf_multiple_product_service_amount'] = isset($_REQUEST['arf_multiple_product_service_amount']) ? esc_attr( sanitize_text_field($_REQUEST['arf_multiple_product_service_amount'] )) : '';

					$options['arf_multiple_donations_service_type'] = (isset($_REQUEST['arf_multiple_donations_service_type'])) ? esc_attr(sanitize_text_field($_REQUEST['arf_multiple_donations_service_type'])) : 0;
	                $options['arf_cl_field_multiple_donations_service_type'] = isset($_REQUEST['arf_cl_field_multiple_donations_service_type']) ? esc_attr(sanitize_text_field($_REQUEST['arf_cl_field_multiple_donations_service_type'])) : '';
	                $options['arf_cl_op_multiple_donations_service_type'] = isset($_REQUEST['arf_cl_op_multiple_donations_service_type']) ? esc_attr(sanitize_text_field($_REQUEST['arf_cl_op_multiple_donations_service_type'])) : '';
	                $options['cl_rule_value_multiple_donations_service_type'] = isset($_REQUEST['cl_rule_value_multiple_donations_service_type']) ? esc_attr(sanitize_text_field($_REQUEST['cl_rule_value_multiple_donations_service_type'])) : '';
	                $options['arf_multiple_donations_service_amount'] = isset($_REQUEST['arf_multiple_donations_service_amount']) ? esc_attr(sanitize_text_field($_REQUEST['arf_multiple_donations_service_amount'])) : '';

					

					

					$conditional_logic_rules = array();

					$rule_array = ( isset( $_REQUEST['rule_array_paypal'] ) && ! empty( $_REQUEST['rule_array_paypal'] ) ) ? sanitize_text_field($_REQUEST['rule_array_paypal']) : array();

					if ( count( $rule_array ) > 0 ) {
						$i = 1;
						foreach ( $rule_array as $v ) {

							$conditional_logic_field      = isset( $_REQUEST['arf_cl_field_paypal_' . $v] ) ? stripslashes_deep( sanitize_text_field($_REQUEST[ 'arf_cl_field_paypal_' . $v ] )) : '';
							$conditional_logic_field_type = $arflitefieldhelper->arflite_get_field_type( $conditional_logic_field );
							$conditional_logic_op         = isset( $_REQUEST['arf_cl_op_paypal_' . $v] ) ? stripslashes_deep( sanitize_text_field($_REQUEST[ 'arf_cl_op_paypal_' . $v ] )) : '';
							$conditional_logic_value      = isset( $_REQUEST['cl_rule_value_paypal_' . $v] ) ? stripslashes_deep( sanitize_text_field($_REQUEST[ 'cl_rule_value_paypal_' . $v ] )) : '';

							$conditional_logic_rules[ $i ] = array(
								'id'         => $i,
								'field_id'   => $conditional_logic_field,
								'field_type' => $conditional_logic_field_type,
								'operator'   => $conditional_logic_op,
								'value'      => $conditional_logic_value,
							);
							$i++;
						}
					}

					$options['conditional_logic'] = array(
						'if_cond' => isset( $_REQUEST['conditional_logic_if_cond_paypal'] ) ? esc_attr( sanitize_text_field($_REQUEST['conditional_logic_if_cond_paypal'] )) : '',
						'rules'   => $conditional_logic_rules,
					);

					$options['paypal_days']   = ( isset( $_REQUEST['arf_paypal_days'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_days'] )) : 1;
					$options['paypal_months'] = ( isset( $_REQUEST['arf_paypal_months'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_months'] )) : 1;
					$options['paypal_years']  = ( isset( $_REQUEST['arf_paypal_years'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_years'] )) : 1;

					$options['paypal_recurring_type'] = ( isset( $_REQUEST['arf_paypal_recurring_type'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_recurring_type'] )) : 'M';
					$options['paypal_recurring_time'] = ( isset( $_REQUEST['arf_paypal_recurring_time'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_recurring_time'] )) : 'infinite';

					$options['paypal_recurring_retry'] = ( isset( $_REQUEST['arf_paypal_recurring_retry'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_recurring_retry'] )) : '0';

					$options['paypal_trial_period'] = ( isset( $_REQUEST['arf_paypal_trial_period'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_trial_period']) ) : '0';
					$options['paypal_trial_amount'] = ( isset( $_REQUEST['arf_paypal_trial_amount'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_trial_amount'] )) : '0';
					$options['paypal_trial_days']   = ( isset( $_REQUEST['arf_paypal_trial_days'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_trial_days'] )) : '1';
					$options['paypal_trial_months'] = ( isset( $_REQUEST['arf_paypal_trial_months'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_trial_months'] )) : '1';
					$options['paypal_trial_years']  = ( isset( $_REQUEST['arf_paypal_trial_years'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_trial_years']) ) : '1';

					$options['paypal_trial_recurring_type'] = ( isset( $_REQUEST['arf_paypal_trial_recurring_type'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['arf_paypal_trial_recurring_type'] )) : 'M';

					$form_options                    = maybe_unserialize( $form_data->options );
					$form_options['success_action']  = isset($_REQUEST['success_action']) ? esc_attr( sanitize_text_field($_REQUEST['success_action'] )) : '';
					$form_options['success_msg']     = isset($_REQUEST['success_msg']) ? esc_attr( sanitize_text_field($_REQUEST['success_msg'] )) : '';
					$form_options['success_url']     = isset($_REQUEST['success_url']) ? esc_attr( sanitize_text_field($_REQUEST['success_url'] )) : '';
					$form_options['success_page_id'] = isset($_REQUEST['success_page_id']) ? esc_attr( sanitize_text_field($_REQUEST['success_page_id'] )) : '';
					$new_form_options                = maybe_serialize( $form_options );

					$opt_to_update = array(
						'options' => $new_form_options,
					);
					
					$form_options = maybe_unserialize( $form_data->arf_mapped_addon );
					if ( isset( $form_options['arf_mapped_addon'] ) && ! empty( $form_options['arf_mapped_addon'] ) ) {
						if ( ! in_array( 'paypal', $form_options['arf_mapped_addon'] ) ) {
							array_push( $form_options['arf_mapped_addon'], 'paypal' );
						}
					} else {
						$form_options                     = array();
						$form_options['arf_mapped_addon'] = array( 'paypal' );
					}

					$new_form_options_mapped_addon     = maybe_serialize( $form_options );
					$opt_to_update['arf_mapped_addon'] = $new_form_options_mapped_addon;
					

					$wpdb->update(
						$tbl_arf_forms,
						$opt_to_update,
						array( 'id' => $form_data->id )
					);

					$options               = apply_filters( 'arf_trim_values', $options );
					$new_values['options'] = maybe_serialize( $options );

					global $arf_paypal;
					$res = $wpdb->update( $arf_paypal->db_paypal_forms, $new_values, array( 'id' => $id ) );

					$values = array();

					$values = $new_values;

					$values['form_id'] = esc_attr( sanitize_text_field($_REQUEST['form_id']) );

					$values['form_name'] = isset($_REQUEST['form_name']) ? esc_attr( sanitize_text_field($_REQUEST['form_name'] )) : '';

					$values['success_action'] = isset($_REQUEST['success_action']) ? esc_attr( sanitize_text_field($_REQUEST['success_action'] )) : '';

					$values['success_msg'] = isset($_REQUEST['success_msg']) ? esc_attr( sanitize_text_field($_REQUEST['success_msg'] )) : '';

					$values['success_url'] = isset($_REQUEST['success_url']) ? esc_attr( sanitize_text_field($_REQUEST['success_url'] )) : '';

					$values['success_page_id'] = isset($_REQUEST['success_page_id']) ? esc_attr( sanitize_text_field($_REQUEST['success_page_id'] )) : '';

					$values['id'] = $id;

					unset( $values['options'] );

					foreach ( $options as $option_key => $option_val ) {
						   $values[ $option_key ] = $option_val;
					}

					$arfaction = 'edit';

					$message = esc_html__( 'Configuration saved successfully.', 'arforms-form-builder' );

					echo wp_json_encode(
						array(
							'success' => true,
							'message' => $message,
						)
					);
					die;
				}
			}
		} else {

			if ( isset( $_REQUEST['arf_paypal_form'] ) && '' != $_REQUEST['arf_paypal_form'] ) {
				$new_form_id = sanitize_text_field($_REQUEST['arf_paypal_form']);

				$form_data = $arfliteform->arflitegetOne( $new_form_id );

				$new_values['form_id'] = $new_form_id;

				$new_values['form_name'] = $form_data->name;

				$options['merchant_email'] = esc_attr( sanitize_text_field($_REQUEST['arf_paypal_email'] ));

				$options['paypal_mode'] = esc_attr( $_REQUEST['arf_paypal_mode'] );

				$options['cancel_url'] = esc_attr(sanitize_text_field($_REQUEST['arf_paypal_cancel_url'] ));

				$options['notification'] = isset( $_REQUEST['arf_paypal_notification'] ) ? 1 : 0;

				$options['user_notification']  = isset( $_REQUEST['arf_paypal_user_notification'] ) ? 1 : 0;
				$options['user_email_content'] = esc_attr( isset( $_REQUEST['user_email_content'] ) ? sanitize_text_field($_REQUEST['user_email_content']) : '' );

				$options['title'] = esc_attr( sanitize_text_field($_REQUEST['arf_paypal_title']) );

				$options['currency'] = esc_attr( sanitize_text_field($_REQUEST['arf_paypal_currency'] ));

				$options['first_name'] = esc_attr( sanitize_text_field($_REQUEST['arf_first_name']) );

				$options['last_name'] = esc_attr( sanitize_text_field($_REQUEST['arf_last_name']) );

				$options['email'] = esc_attr( sanitize_text_field($_REQUEST['arf_email']) );

				$options['state'] = esc_attr( sanitize_text_field($_REQUEST['arf_state']) );

				$options['address'] = esc_attr( sanitize_text_field($_REQUEST['arf_address']) );

				$options['address_2'] = esc_attr( sanitize_text_field($_REQUEST['arf_address_2'] ));

				$options['city'] = esc_attr( sanitize_text_field($_REQUEST['arf_city']) );

				$options['zip'] = esc_attr( sanitize_text_field($_REQUEST['arf_zip']) );

				$options['country'] = esc_attr( sanitize_text_field($_REQUEST['arf_country']) );

				$options['amount'] = esc_attr( $_REQUEST['arf_amount'] );

				$options['payment_type'] = esc_attr( sanitize_text_field($_REQUEST['arf_paypal_payment_type'] ));

				$options['email_content'] = esc_attr( sanitize_text_field($_REQUEST['email_content']) );

				$options['shipping_info'] = ( isset( $_REQUEST['shipping_info'] ) ) ? esc_attr( sanitize_text_field($_REQUEST['shipping_info'] )) : 0;

				$form_options                    = maybe_unserialize( $form_data->options );
				$form_options['success_action']  = esc_attr( sanitize_text_field($_REQUEST['success_action']) );
				$form_options['success_msg']     = esc_attr( sanitize_text_field($_REQUEST['success_msg'] ));
				$form_options['success_url']     = esc_attr( sanitize_text_field($_REQUEST['success_url'] ));
				$form_options['success_page_id'] = esc_attr( sanitize_text_field($_REQUEST['success_page_id'] ));

				$new_form_options                = maybe_serialize( $form_options );

				$opt_to_update = array(
					'options' => $new_form_options,
				);
				
				$form_arf_mapped_addon = maybe_unserialize( $form_data->arf_mapped_addon );
				if ( isset( $form_arf_mapped_addon['arf_mapped_addon'] ) && ! empty( $form_arf_mapped_addon['arf_mapped_addon'] ) ) {
					if ( ! in_array( 'paypal', $form_arf_mapped_addon['arf_mapped_addon'] ) ) {
						array_push( $form_arf_mapped_addon['arf_mapped_addon'], 'paypal' );
					}
				} else {
					$form_arf_mapped_addon                     = array();
					$form_arf_mapped_addon['arf_mapped_addon'] = array( 'paypal' );
				}
				$new_form_arf_mapped_addon         = maybe_serialize( $form_arf_mapped_addon );
				$opt_to_update['arf_mapped_addon'] = $new_form_arf_mapped_addon;
				

				$wpdb->update(
					$tbl_arf_forms,
					$opt_to_update,
					array( 'id' => $form_data->id )
				);

				$options               = apply_filters( 'arf_trim_values', $options );

				$new_values['options'] = maybe_serialize( $options );

				$new_values['created_at'] = current_time( 'mysql' );

				$id = $wpdb->insert( $arf_paypal->db_paypal_forms, $new_values );

				$id = $wpdb->insert_id;

				$values = array();

				$values = $new_values;

				$values['success_action'] = esc_attr( sanitize_text_field($_REQUEST['success_action']) );

				$values['success_msg'] = esc_attr( sanitize_text_field($_REQUEST['success_msg']) );

				$values['success_url'] = esc_attr( sanitize_text_field($_REQUEST['success_url']) );

				$values['success_page_id'] = esc_attr(sanitize_text_field($_REQUEST['success_page_id'] ));

				$values['id'] = $id;

				unset( $values['options'] );

				foreach ( $options as $option_key => $option_val ) {
						  $values[ $option_key ] = $option_val;
				}

				$arfaction = 'edit';

				$message = esc_html__( 'Configuration saved successfully.', 'arforms-form-builder' );

				echo wp_json_encode(
					array(
						'success'   => true,
						'message'   => $message,
						'arfaction' => 'new',
						'new_id'    => $id,
						'form_id'   => $new_form_id,
						'form_name' => $new_values['form_name'],
					)
				);
			}
		}
		die;
	}

	function field_dropdown( $form_id, $name , $class , $default_value , $field_array ) {
		

		global $arffield, $arflitemainhelper, $arflitefieldhelper,$arflitemaincontroller;
		$field_list = array();
		$id         = ( isset( $id ) && $id != '' ) ? $id : '';
		$field_list = $field_array;

		$exclude    = array( 'divider', 'captcha', 'section', 'break', 'file', 'like','arf_repeater', 'matrix' );

		if ( $name == 'arf_amount' || $name == 'arf_multiple_product_service_amount' || $name == 'arf_multiple_donations_service_amount') {

			$selected_list_id      = '';
			$selected_list_label   = esc_html__( 'Select Field', 'arforms-form-builder' );
			$responder_list_option = array(
				'' => esc_html__('Select Field','arforms-form-builder'),
			);
			if ( count( $field_list ) > 0 ) {
				array_push($exclude, 'arf_multiselect', 'checkbox');
				$cntr = 0;
				foreach ( $field_list as $field ) {
					if ( in_array( $field->type, $exclude ) ) {
						continue;
					}
					if( isset($field->field_options['parent_field_type']) && 'arf_repeater' == $field->field_options['parent_field_type'] ){
						continue;
					}

					if ( $default_value == $field->id ) {
						$selected_list_id    = $field->id;
						$selected_list_label =  $field->name ;
					}
					
						$responder_list_option[$field->id] = $arflitemainhelper->arflitetruncate( $field->name, 33 );
					
					$cntr++;
				}
			}

			?>

			<input id="<?php echo esc_attr( $name ); ?>"  name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $selected_list_id ); ?>" type="hidden" >
			
			
			<?php echo $arflitemaincontroller->arflite_selectpicker_dom( $name, $name, 'arf_pp_small_dd_field', '',$selected_list_id, array(), $responder_list_option,  false, array(), false, array(), false, array(), false, $class, ''.$name.'_name'); //phpcs:ignore ?>
			
			
			
		
			
		
		<?php	
		} else {
			$selected_list_id      = '';
			$selected_list_label   = esc_html__( 'Select Field', 'arforms-form-builder' );
			$responder_list_option = array(
				'' => esc_html__('Select Field','arforms-form-builder'),
			);
			if ( count( $field_list ) > 0 ) {
				$cntr = 0;
				foreach ( $field_list as $field ) {
					if ( in_array( $field->type, $exclude ) ) {
						continue;
					}
					if( isset($field->field_options['parent_field_type']) && 'arf_repeater' == $field->field_options['parent_field_type'] ){
						continue;
					}

					if ( $default_value == $field->id ) {
						$selected_list_id    = $field->id;
						$selected_list_label = $field->name;
					}
					
					$responder_list_option[$field->id] = $arflitemainhelper->arflitetruncate( $field->name, 33 );
					
					$cntr++;
				}
			}

			?>

			
			
				<input id="<?php echo esc_attr( $name ); ?>"  name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $selected_list_id ); ?>" type="hidden" >
				
				<?php
				 	echo $arflitemaincontroller->arflite_selectpicker_dom( $name, $name, 'arf_pp_small_dd_field', '',$selected_list_id, array(), $responder_list_option,  false, array(), false, array(), false, array(), false, $class, ''.$name.'_name'); //phpcs:ignore
				 ?>
				
				<?php
			
		}
	}

	function arf_paypal_field_dropdown() {

		$arf_paypal_nonce = isset( $_POST['_wpnonce_paypal'] ) ? sanitize_text_field( $_POST['_wpnonce_paypal'] ) : '';
		if ( empty( $arf_paypal_nonce ) || ! wp_verify_nonce( $arf_paypal_nonce, 'arforms_paypal_nonce' ) ) {
			echo esc_attr( 'security_error' );
			die;
		}

		if ( ! current_user_can( 'arfpaypalconfiguration' ) ) {
			echo esc_attr( 'security_error' );
			die;
		}

		global $arflitefield, $arflitemainhelper,$arflitefieldhelper;

		$form_id = isset($_REQUEST['form_id']) ? sanitize_text_field($_REQUEST['form_id']) : '';

		$field_list = array();
		$res        = '';
		if ( is_numeric( $form_id ) ) {

			$exclude = "'divider','section','captcha','break','file','like','arf_repeater', 'checkbox', 'arf_multiselect', 'matrix'";

			$field_list = $arflitefield->arflitegetAll( 'fi.type not in ( ' . $exclude . ') and fi.form_id=' . (int) $form_id, 'id' );
		}
		?>
	   
		<?php
		
		global $armainhelper;
		$selected_list_id      = '';
		$selected_list_label   = esc_html__( 'Select Field', 'arforms-form-builder' );
		$responder_list_option = '<li class="arf_selectbox_option" data-value="" data-label=' . esc_html__( 'Select Field', 'arforms-form-builder' ) . '>' . esc_html__( 'Select Field', 'arforms-form-builder' ) . '</li>';
		if ( count( $field_list ) > 0 ) {
			$cntr = 0;
			foreach ( $field_list as $field ) {

				if( isset($field->field_options['parent_field_type']) && 'arf_repeater' == $field->field_options['parent_field_type']){
					continue;

				}
				if ( isset( $default_value ) && $default_value == $field->id ) {
					$selected_list_id    = $field->id;
					$selected_list_label = $field->name;
				}
				$responder_list_option .= '<li class="arf_selectbox_option" data-value="' . esc_html( $field->id ) . '" data-label="' . $arflitefieldhelper->arflite_execute_function( $field->name, 'strip_tags' ) . '">' . $arflitemainhelper->arflitetruncate( $arflitefieldhelper->arflite_execute_function( $field->name, 'strip_tags' ), 33 ) . '</li>';
				$cntr++;
			}
		}
		
		$res .= $responder_list_option;
		$res .= '^|^';
		if ( is_numeric( $form_id ) ) {
			$exclude = "'divider','section','captcha','break','file','like','arf_repeater', 'matrix'";

			$field_list = $arflitefield->arflitegetAll( 'fi.type not in ( ' . $exclude . ') and fi.form_id=' . (int) $form_id, 'id' );
		}

		$selected_list_id      = '';
		$selected_list_label   = esc_html__( 'Select Field', 'arforms-form-builder' );
		$responder_list_option = '';
		if ( count( $field_list ) > 0 ) {
			$cntr = 0;
			foreach ( $field_list as $field ) {
				if(isset($field->field_options['parent_field_type']) && 'arf_repeater' == $field->field_options['parent_field_type']){
					continue;

				}
				if ( isset( $default_value ) && $default_value == $field->id ) {
					$selected_list_id    = $field->id;
					$selected_list_label = $field->name;
				}
				$responder_list_option .= '<li class="arf_selectbox_option" data-value="' . esc_html( $field->id ) . '" data-label="' . $arflitefieldhelper->arflite_execute_function( $field->name, 'strip_tags' ) . '">' . $arflitemainhelper->arflitetruncate( $arflitefieldhelper->arflite_execute_function( $field->name, 'strip_tags' ), 33 ) . '</li>';
				$cntr++;
			}
		}
		

		$res .= $responder_list_option;
		echo $res;
		die();
	}

	function arfdelete_paypal_form( $form_id ) {
		global $wpdb, $arflitedb_record,$arf_paypal;

		if ( ! $form_id ) {
			return;
		}

		$form_data = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $arf_paypal->db_paypal_forms . ' WHERE form_id = %d', $form_id ) );//phpcs:ignore

		if ( count( $form_data ) > 0 ) {
			$form_data = $form_data[0];
			$entries   = $arflitedb_record->arflitegetAll( array( 'it.form_id' => $form_id ) );
			if ( count( $entries ) > 0 ) {
				foreach ( $entries as $item ) {
					$res = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $arf_paypal->db_paypal_order . ' WHERE entry_id = %d', $item->id ) );//phpcs:ignore
				}
			}

			$res = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $arf_paypal->db_paypal_forms . ' WHERE id = %d', $form_data->id ) );//phpcs:ignore
		}
	}

	function paypal_api() {

		global $arflitenotifymodel, $arf_paypal,$arflitemainhelper,$arflitefieldhelper, $tbl_arf_entries, $tbl_arf_forms, $tbl_arf_entry_values, $arformsmain, $arf_debug_log_id;
		 
		if ( ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] != 'arforms_paypal_api' ) && ( ! isset($_REQUEST['arf_page'] ) || $_REQUEST['arf_page'] != 'arforms_paypal_api' ) ) {
			return;
		}

		if ( isset( $_POST['txn_id'] ) ) {//phpcs:ignore
			global $wpdb;

			$req = 'cmd=_notify-validate';

			foreach ( $_POST as $key => $value ) {//phpcs:ignore
				$value = urlencode( stripslashes( $value ) );
				$req  .= "&$key=$value";
			}
			$_REQUEST['custom'] = isset($_REQUEST['custom']) ? sanitize_text_field($_REQUEST['custom']) : '';
			$customs = explode( '|', sanitize_text_field($_REQUEST['custom']) );
			$form_id = $customs[1];
			if ( ! $form_id ) {
				return;
			}

			$form_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $arf_paypal->db_paypal_forms . ' WHERE form_id = %d', $form_id ) );//phpcs:ignore

			if ( count( $form_data ) == 0 ) {
				return;
			}

			$form_data = $form_data[0];
			$options   = maybe_unserialize( $form_data->options );

			$sandbox = ( isset( $options['paypal_mode'] ) and $options['paypal_mode'] == 0 ) ? 'sandbox.' : '';

			$url = 'https://www.' . $sandbox . 'paypal.com/cgi-bin/webscr/';

			$request  = new WP_Http();
			$response = $request->post(
				$url,
				array(
					'sslverify' => false,
					'ssl'       => true,
					'body'      => $req,
					'timeout'   => 20,
				)
			);
			
			if(is_wp_error($response)){
				$arflite_paypal_error_msg = array(
					'success' => 'false',
					'message' => esc_html($response['body']),
					'posted_data' => $req
				);
				do_action( 'arforms_debug_log_entry', 'arflite_paypal_debug_log', 'paypal payment verification failed', 'arflite_paypal_data', $arflite_paypal_error_msg, $arf_debug_log_id );
			} else {
				$success_type = ( 'VERIFIED' == $response['body'] ) ? 'true' : 'false';
				$arflite_paypal_success_data = array(
					'success' => $success_type,
					'message' => $response['body'],
					'posted_data' => $req
				);
				do_action( 'arforms_debug_log_entry', 'arflite_paypal_debug_log', 'paypal payment verification succeed', 'arflite_paypal_data', $arflite_paypal_success_data, $arf_debug_log_id );
			}

			if ( ! is_wp_error( $response ) and $response['body'] == 'VERIFIED' ) {


				$txn_id          = isset( $_POST['txn_id'] ) ? sanitize_text_field($_POST['txn_id']) : '';//phpcs:ignore
				$payment_status  = isset( $_POST['payment_status'] ) ? sanitize_text_field($_POST['payment_status']) : '';//phpcs:ignore
				
				$is_payment_exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) from `" . $arf_paypal->db_paypal_order . "` WHERE txn_id = %s AND payment_status = %s", $txn_id, $payment_status  ) );//phpcs:ignore

				if( $is_payment_exists > 0 ){
					$arflite_paypal_error_msg = array(
						'success' => 'false',
						'message' => esc_html($response['body']),
						'posted_data' => $req
					);
					do_action( 'arforms_debug_log_entry', 'arflite_paypal_debug_log', 'paypal payment already exists', 'arflite_paypal_data', $arflite_paypal_error_msg, $arf_debug_log_id );
					return;
				}

				
				$payment_results = $wpdb->get_row( $wpdb->prepare( 'SELECT payment_status FROM ' . $arf_paypal->db_paypal_order . ' WHERE txn_id = %s', $txn_id ) );//phpcs:ignore

				$existed_payment_status = !empty( $payment_results ) ? $payment_results->payment_status : '';

				if( $existed_payment_status != '' && $existed_payment_status == $payment_status ){
					return;
				}

				$item_name        = isset( $_POST['item_name'] ) ? $_POST['item_name'] : '';//phpcs:ignore
				$txn_id           = isset( $_POST['txn_id'] ) ? $_POST['txn_id'] : '';//phpcs:ignore
				$payment_status   = isset( $_POST['payment_status'] ) ? $_POST['payment_status'] : '';//phpcs:ignore
				$payment_amount   = isset( $_POST['mc_gross'] ) ? $_POST['mc_gross'] : '';//phpcs:ignore
				$payment_currency = isset( $_POST['mc_currency'] ) ? $_POST['mc_currency'] : '';//phpcs:ignore
				$receiver_email   = isset( $_POST['receiver_email'] ) ? $_POST['receiver_email'] : '';//phpcs:ignore
				$payer_email      = isset( $_POST['payer_email'] ) ? $_POST['payer_email'] : '';//phpcs:ignore
				$quantity         = isset( $_POST['quantity'] ) ? $_POST['quantity'] : '';//phpcs:ignore
				$user_id          = get_current_user_id();
				$payment_date     = isset( $_POST['payment_date'] ) ? $_POST['payment_date'] : '';//phpcs:ignore
				$payer_name       = ( isset( $_POST['first_name'] ) && isset( $_POST['last_name'] ) ) ? $_POST['first_name'] . ' ' . $_POST['last_name'] : '';//phpcs:ignore
				$entry_id         = $customs[0];
				$form_id          = $customs[1];
				$payment_type     = $customs[2];

				$insert_array = array(
					'item_name'      => $item_name,
					'txn_id'         => $txn_id,
					'payment_status' => $payment_status,
					'mc_gross'       => floatval( $payment_amount ),
					'mc_currency'    => $payment_currency,
					'quantity'       => $quantity,
					'payer_email'    => $payer_email,
					'payer_name'     => $payer_name,
					'payment_type'   => $payment_type,
					'user_id'        => $user_id,
					'entry_id'       => $entry_id,
					'form_id'        => $form_id,
					'payment_date'   => $payment_date,
					'created_at'     => current_time( 'mysql' ),
					'is_verified'    => 1,
				);
				if( empty( $payment_results ) ){
					$wpdb->insert(
						$arf_paypal->db_paypal_order,
						$insert_array,
						array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d' ) );
				} else if( $existed_payment_status != $payment_status) {
					$wpdb->update(
						$arf_paypal->db_paypal_order,
						array(
							'payment_status' => $payment_status
						),
						array(
							'id' => $payment_results->id,
							'txn_id' => $txn_id
						)
					);
					return;
				} else if( $existed_payment_status == $payment_status ){
					return;
				}

				$wpdb->update( $tbl_arf_entries, array( 'form_id' => $form_id ), array( 'id' => $entry_id ) );

				update_option( 'IPN_LOG' . $form_id . '_' . time(), maybe_serialize( $_POST ) );//phpcs:ignore

				do_action( 'arf_after_paypal_successful_paymnet', $form_id, $entry_id, $txn_id );

				$admin_email_notification_data = get_option( 'arf_paypal_admin_email_notification_' . $entry_id . '_' . $form_id );
				$user_email_notification_data = get_option( 'arf_paypal_user_email_notification_' . $entry_id . '_' . $form_id );

				if( !empty( $admin_email_notification_data ) ){
					$admin_email_notify_data = json_decode( $admin_email_notification_data, true );

					$arf_to_mail       			= $admin_email_notify_data['arf_admin_emails'];
					$arf_admin_subject  		= $admin_email_notify_data['arf_admin_subject'];
					$arf_admin_mail_body       	= $admin_email_notify_data['arf_admin_mail_body'];
					$arf_admin_reply_to      	= $admin_email_notify_data['arf_admin_reply_to'];
					$arf_admin_reply_to_email   = $admin_email_notify_data['arf_admin_reply_to_email'];
					$arf_admin_reply_to_name 	= $admin_email_notify_data['arf_admin_reply_to_name'];
					$arf_admin_plain_text    	= $admin_email_notify_data['arf_admin_plain_text'];
					$arf_admin_attachments   	= $admin_email_notify_data['arf_admin_attachments'];
					$arf_admin_cc_emails 		= $admin_email_notify_data['arf_admin_cc_emails'];
					$arf_admin_bcc_emails 		= $admin_email_notify_data['arf_admin_bcc_emails'];
					foreach( $arf_to_mail as $email ){
						if( $arformsmain->arforms_is_pro_active() ){
							global $arnotifymodel;
							$arnotifymodel->send_notification_email_user( $email, $arf_admin_subject, $arf_admin_mail_body, $arf_admin_reply_to, $arf_admin_reply_to_name, $arf_admin_plain_text, $arf_admin_attachments, false, false, true, false, $arf_admin_reply_to_email, $arf_admin_cc_emails, $arf_admin_bcc_emails );
						} else {
							$arflitenotifymodel->arflite_send_notification_email_user( $email, $arf_admin_subject, $arf_admin_mail_body, $arf_admin_reply_to, $arf_admin_reply_to_name, $arf_admin_plain_text, $arf_admin_attachments, false, false, true, false, $arf_admin_reply_to_email, $arf_admin_cc_emails, $arf_admin_bcc_emails );
						}
					}
					delete_option( 'arf_paypal_admin_email_notification_' . $entry_id . '_' . $form_id );
				}

				if( !empty( $user_email_notification_data ) ){
					$user_email_notify_data = json_decode( $user_email_notification_data, true );

					$arf_to_mail       = $user_email_notify_data['to'];
					$arf_mail_subject  = $user_email_notify_data['subject'];
					$arf_message       = $user_email_notify_data['message'];
					$arf_reply_to      = $user_email_notify_data['reply_to'];
					$arf_reply_to_name = $user_email_notify_data['reply_to_name'];
					$arf_plain_text    = $user_email_notify_data['plain_text'];
					$arf_attachments   = $user_email_notify_data['attachments'];
					$arf_return_value  = $user_email_notify_data['return_value'];
					$arf_use_only_smtp = $user_email_notify_data['use_only_smtp'];
					$arf_nreply_to     = $user_email_notify_data['nreply_to'];
					if( $arformsmain->arforms_is_pro_active() ){
						global $arnotifymodel;
						$arnotifymodel->send_notification_email_user( $arf_to_mail, $arf_mail_subject, $arf_message, $arf_reply_to, $arf_reply_to_name, $arf_plain_text, $arf_attachments, $arf_return_value, $arf_use_only_smtp, true, false, $arf_nreply_to );
					} else {
						$arflitenotifymodel->arflite_send_notification_email_user( $arf_to_mail, $arf_mail_subject, $arf_message, $arf_reply_to, $arf_reply_to_name, $arf_plain_text, $arf_attachments, $arf_return_value, $arf_use_only_smtp, true, false, $arf_nreply_to );
					}
					delete_option( 'arf_paypal_user_email_notification_' . $entry_id . '_' . $form_id );
				}
				
				if ( ( ( isset( $options['notification'] ) and $options['notification'] ) || ( isset( $options['user_notification'] ) and $options['user_notification'] ) ) ) {
					global $arfsettings;

					$arf_form_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $tbl_arf_forms . ' WHERE id = %d', $form_id ) );//phpcs:ignore
					$arf_form_data = $arf_form_data[0];
					$arf_options   = maybe_unserialize( $arf_form_data->options );

					$arfblogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

					$admin_email = $arf_options['reply_to'];
					if ( ! is_email( $admin_email ) ) {
						$admin_email = $arfsettings->reply_to;
					}

					$admin_from_reply = $arf_options['ar_admin_from_email'];
					if ( ! is_email( $admin_from_reply ) ) {
						$admin_from_reply = $admin_email;
					}

					$reply_to_name = ( isset( $arf_options['ar_admin_from_name'] ) ) ? $arf_options['ar_admin_from_name'] : $arfsettings->reply_to_name;

					$entry = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $tbl_arf_forms . '` WHERE id = %d', $entry_id ) );//phpcs:ignore

					$admin_nreply_to = isset( $arf_options['ar_admin_reply_to_email'] ) ? $arf_options['ar_admin_reply_to_email'] : $arfsettings->reply_to_email;

					if ( isset( $admin_from_reply ) && $admin_from_reply != '' ) {
						$shortcodes       = $arflitemainhelper->arfliteget_shortcodes( $admin_from_reply, $form_id );
						$reply_to         = $arflitefieldhelper->arflite_replace_shortcodes( $admin_from_reply, $entry, $shortcodes );
						$reply_to         = trim( $reply_to );
						$admin_from_reply = $arflitefieldhelper->arflite_replace_shortcodes( $reply_to, $entry );
					}

					if ( isset( $admin_nreply_to ) && $admin_nreply_to != '' ) {
						$shortcodes      = $arflitemainhelper->arfliteget_shortcodes( $admin_nreply_to, $form_id );
						$reply_to        = $arflitefieldhelper->arflite_replace_shortcodes( $admin_nreply_to, $entry, $shortcodes );
						$reply_to        = trim( $reply_to );
						$admin_nreply_to = $arflitefieldhelper->arflite_replace_shortcodes( $reply_to, $entry );
					}

					$reply_to_name = ( isset( $arf_options['ar_admin_from_name'] ) ) ? $arf_options['ar_admin_from_name'] : $arfsettings->reply_to_name;

					$user_from_email = isset( $arf_options['ar_user_from_email'] ) ? $arf_options['ar_user_from_email'] : $arfsettings->reply_to;

					if ( isset( $user_from_email ) && $user_from_email != '' ) {
						$shortcodes      = $arflitemainhelper->arfliteget_shortcodes( $user_from_email, $form_id );
						$reply_to        = $arflitefieldhelper->arflite_replace_shortcodes( $user_from_email, $entry, $shortcodes );
						$reply_to        = trim( $reply_to );
						$user_from_email = $arflitefieldhelper->arflite_replace_shortcodes( $reply_to, $entry );
					}

					$user_reply_to_email = isset( $arf_options['ar_user_nreplyto_email'] ) ? $arf_options['ar_user_nreplyto_email'] : $arfsettings->reply_to_email;

					if ( isset( $user_reply_to_email ) && $user_reply_to_email != '' ) {
						$shortcodes          = $arflitemainhelper->arfliteget_shortcodes( $user_reply_to_email, $form_id );
						$reply_to            = $arflitefieldhelper->arflite_replace_shortcodes( $user_reply_to_email, $entry, $shortcodes );
						$reply_to            = trim( $reply_to );
						$user_reply_to_email = $arflitefieldhelper->arflite_replace_shortcodes( $reply_to, $entry );
					}

					$user_reply_to_name = isset( $arf_options['ar_user_from_name'] ) ? $arf_options['ar_user_from_name'] : $arfsettings->reply_to_name;

					$item_name        = $_POST['item_name'];//phpcs:ignore
					$txn_id           = $_POST['txn_id'];//phpcs:ignore
					$payment_status   = $_POST['payment_status'];//phpcs:ignore
					$payment_amount   = $_POST['mc_gross'];//phpcs:ignore
					$payment_currency = $_POST['mc_currency'];//phpcs:ignore
					$payment_date     = $_POST['payment_date'];//phpcs:ignore
					$payer_email      = $_POST['payer_email'];//phpcs:ignore
					$payer_id         = $_POST['payer_id'];//phpcs:ignore
					$payer_fname      = $_POST['first_name'];//phpcs:ignore
					$payer_lname      = $_POST['last_name'];//phpcs:ignore

					if ( isset( $options['notification'] ) and $options['notification'] ) {

						$subject = esc_html__( 'Payment received on', 'arforms-form-builder' ) . ' ' . $arfblogname;
						$message = nl2br( $options['email_content'] );
						if ( empty( $message ) ) {
							$message = $arf_paypal->defalut_email_content();
						}

						$message = str_replace( '[transaction_id]', $txn_id, $message );
						$message = str_replace( '[amount]', floatval( $payment_amount ), $message );
						$message = str_replace( '[currency]', $payment_currency, $message );
						$message = str_replace( '[payment_date]', $payment_date, $message );
						$message = str_replace( '[site_name]', $arfblogname, $message );
						$message = str_replace( '[payer_email]', $payer_email, $message );
						$message = str_replace( '[payer_id]', $payer_id, $message );
						$message = str_replace( '[payer_fname]', $payer_fname, $message );
						$message = str_replace( '[payer_lname]', $payer_lname, $message );

						if( $arformsmain->arforms_is_pro_active() ){
							global $arnotifymodel;
							$arnotifymodel->send_notification_email_user( $admin_email, $subject, $message, $admin_from_reply, $reply_to_name, true, array(), false, false, true, false, $admin_nreply_to );
						} else {
							$arflitenotifymodel->arflite_send_notification_email_user( $admin_email, $subject, $message, $admin_from_reply, $reply_to_name, true, array(), false, false, true, false, $admin_nreply_to );
						}
					}

					if ( isset( $options['user_notification'] ) and $options['user_notification'] ) {
						$email_field       = $options['email'];
						$entry_payer_email = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $tbl_arf_entry_values . ' WHERE field_id = %d AND entry_id = %d', $email_field, $entry_id ) )->entry_value;//phpcs:ignore

						echo esc_html( $email_field );
						echo '<br>' . esc_html( $entry_payer_email );

						if ( ! empty( $entry_payer_email ) ) {
							$subject = esc_html__( 'Payment received on', 'arforms-form-builder' ) . ' ' . $arfblogname;
							$message = nl2br( $options['user_email_content'] );
							if ( empty( $message ) ) {
								$message = $arf_paypal->user_defalut_email_content();
							}

							$message = str_replace( '[transaction_id]', $txn_id, $message );
							$message = str_replace( '[amount]', floatval( $payment_amount ), $message );
							$message = str_replace( '[currency]', $payment_currency, $message );
							$message = str_replace( '[payment_date]', $payment_date, $message );
							$message = str_replace( '[site_name]', $arfblogname, $message );
							$message = str_replace( '[payer_email]', $payer_email, $message );
							$message = str_replace( '[payer_id]', $payer_id, $message );
							$message = str_replace( '[payer_fname]', $payer_fname, $message );
							$message = str_replace( '[payer_lname]', $payer_lname, $message );

							if( $arformsmain->arforms_is_pro_active() ){
								global $arnotifymodel;
								$arnotifymodel->send_notification_email_user( $entry_payer_email, $subject, $message, $admin_from_reply, $reply_to_name, true, array(), false, false, true, false, $user_reply_to_email );
							} else {
								$arflitenotifymodel->arflite_send_notification_email_user( $entry_payer_email, $subject, $message, $admin_from_reply, $reply_to_name, true, array(), false, false, true, false, $user_reply_to_email );
							}
						}
					}
				}
			}
		}
	}

	function paypal_response() {
	 
		if ( ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] != 'arforms_paypal_response' ) && ( !isset( $_REQUEST['arf_page'] ) || $_REQUEST['arf_page'] != 'arforms_paypal_response' ) ) {
			return;
		}

		global $wpdb,$tbl_arf_entry_values,$arf_paypal,$tbl_arf_entries,$arrecordcontroller, $arflitemaincontroller, $arflitenotifymodel,$fid,$tbl_arf_forms, $tbl_arf_fields, $tbl_arf_entry_values, $arformsmain;

		$arflitemaincontroller->arflite_start_session(true);

		 
		if ( isset( $_POST['txn_id'] ) && $_POST['txn_id'] != '' ) {//phpcs:ignore
 

			$item_name        = !empty( $_POST['item_name'] ) ? $_POST['item_name'] : '';//phpcs:ignore
			$txn_id           = $_POST['txn_id'];//phpcs:ignore
			$payment_status   = $_POST['payment_status'];//phpcs:ignore
			$payment_amount   = $_POST['mc_gross'];//phpcs:ignore
			$payment_currency = $_POST['mc_currency'];//phpcs:ignore
			$receiver_email   = isset( $_POST['receiver_email'] ) ? $_POST['receiver_email'] : '';//phpcs:ignore
			$payer_email      = $_POST['payer_email'];//phpcs:ignore
			$quantity         = !empty( $_POST['quantity'] ) ? $_POST['quantity'] : 1;//phpcs:ignore
			$user_id          = get_current_user_id();
			$payment_date     = $_POST['payment_date'];//phpcs:ignore
			$customs          = explode( '|', $_REQUEST['custom'] );//phpcs:ignore
			$entry_id         = $customs[0];
			$form_id          = $customs[1];
			$payment_type     = $customs[2];
			$fid = $form_id;

			$payment_results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $arf_paypal->db_paypal_order . ' WHERE txn_id = %s', $txn_id ) );//phpcs:ignore

		 
			$pageurl = isset( $_SESSION['arf_return_url'][ $form_id ] ) ? $_SESSION['arf_return_url'][ $form_id ] : '';//phpcs:ignore
			if ( ! isset( $pageurl ) || $pageurl == '' ) {
				$pageurl = get_home_url();
			}
 



			$admin_email_notification_data = get_option( 'arf_paypal_admin_email_notification_' . $entry_id . '_' . $form_id );
			$user_email_notification_data = get_option( 'arf_paypal_user_email_notification_' . $entry_id . '_' . $form_id );

			if( !empty( $admin_email_notification_data ) ){
				$admin_email_notify_data = json_decode( $admin_email_notification_data, true );

				$arf_to_mail       			= $admin_email_notify_data['arf_admin_emails'];
				$arf_admin_subject  		= $admin_email_notify_data['arf_admin_subject'];
				$arf_admin_mail_body       	= $admin_email_notify_data['arf_admin_mail_body'];
				$arf_admin_reply_to      	= $admin_email_notify_data['arf_admin_reply_to'];
				$arf_admin_reply_to_email   = $admin_email_notify_data['arf_admin_reply_to_email'];
				$arf_admin_reply_to_name 	= $admin_email_notify_data['arf_admin_reply_to_name'];
				$arf_admin_plain_text    	= $admin_email_notify_data['arf_admin_plain_text'];
				$arf_admin_attachments   	= $admin_email_notify_data['arf_admin_attachments'];
				$arf_admin_cc_emails 		= $admin_email_notify_data['arf_admin_cc_emails'];
				$arf_admin_bcc_emails 		= $admin_email_notify_data['arf_admin_bcc_emails'];
				foreach( $arf_to_mail as $email ){
					if( $arformsmain->arforms_is_pro_active() ){
						global $arnotifymodel;
						$arnotifymodel->send_notification_email_user( $email, $arf_admin_subject, $arf_admin_mail_body, $arf_admin_reply_to, $arf_admin_reply_to_name, $arf_admin_plain_text, $arf_admin_attachments, false, false, true, false, $arf_admin_reply_to_email, $arf_admin_cc_emails, $arf_admin_bcc_emails  );
					} else {
						$arflitenotifymodel->arflite_send_notification_email_user( $email, $arf_admin_subject, $arf_admin_mail_body, $arf_admin_reply_to, $arf_admin_reply_to_name, $arf_admin_plain_text, $arf_admin_attachments, false, false, true, false, $arf_admin_reply_to_email, $arf_admin_cc_emails, $arf_admin_bcc_emails );
					}
				}
				delete_option( 'arf_paypal_admin_email_notification_' . $entry_id . '_' . $form_id );
			}

			if( !empty( $user_email_notification_data ) ){
				$user_email_notify_data = json_decode( $user_email_notification_data, true );

				$arf_to_mail       = $user_email_notify_data['to'];
				$arf_mail_subject  = $user_email_notify_data['subject'];
				$arf_message       = $user_email_notify_data['message'];
				$arf_reply_to      = $user_email_notify_data['reply_to'];
				$arf_reply_to_name = $user_email_notify_data['reply_to_name'];
				$arf_plain_text    = $user_email_notify_data['plain_text'];
				$arf_attachments   = $user_email_notify_data['attachments'];
				$arf_return_value  = $user_email_notify_data['return_value'];
				$arf_use_only_smtp = $user_email_notify_data['use_only_smtp'];
				$arf_nreply_to     = $user_email_notify_data['nreply_to'];
				if( $arformsmain->arforms_is_pro_active() ){
					global $arnotifymodel;
					$arnotifymodel->send_notification_email_user( $arf_to_mail, $arf_mail_subject, $arf_message, $arf_reply_to, $arf_reply_to_name, $arf_plain_text, $arf_attachments, $arf_return_value, $arf_use_only_smtp, true, false, $arf_nreply_to );
				} else {
					$arflitenotifymodel->arflite_send_notification_email_user( $arf_to_mail, $arf_mail_subject, $arf_message, $arf_reply_to, $arf_reply_to_name, $arf_plain_text, $arf_attachments, $arf_return_value, $arf_use_only_smtp, true, false, $arf_nreply_to );
				}
				delete_option( 'arf_paypal_user_email_notification_' . $entry_id . '_' . $form_id );
			}

			$arflitenotifymodel->arfliteautoresponder($entry_id, $form_id, true);
			do_action( 'arf_after_payment_check' , $entry_id , $form_id );

			$wpdb->update( $tbl_arf_entries, array( 'form_id' => $form_id ), array( 'id' => $entry_id ) );

			$arf_form_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $tbl_arf_forms . ' WHERE id = %d', $form_id ) );//phpcs:ignore
			$arf_form_data = $arf_form_data[0];
			$arf_options   = maybe_unserialize( $arf_form_data->options );

			if ( $arf_options['success_action'] == 'redirect' and isset( $arf_options['success_url'] ) and $arf_options['success_url'] != '' ) {
				if ( isset( $arf_options['arf_data_with_url'] ) && $arf_options['arf_data_with_url'] == 1 ) {
					$fields            = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM `' . $tbl_arf_fields . '` WHERE form_id = %d', $form_id ) );//phpcs:ignore
					$posted_field_data = array();
					foreach ( $fields as $k => $field ) {
						$field_id    = $field->id;
						$entry_value = $wpdb->get_row( $wpdb->prepare( 'SELECT entry_value FROM `' . $tbl_arf_entry_values . '` WHERE entry_id = %d AND field_id = %d', $entry_id, $field_id ) );//phpcs:ignore
						if ( isset( $entry_value ) ) {
							$posted_field_data[ $field_id ] = $entry_value->entry_value;
						}
					}

					echo esc_attr($arrecordcontroller->generate_redirect_form( $arf_form_data, $arf_options['success_url'], $arf_options['arf_data_with_url_type'], $posted_field_data ));
				} else {
					wp_redirect( $arf_options['success_url'] );
					die;
				}
				exit;
			} else {

				$entry_metas = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $tbl_arf_entry_values . ' WHERE field_id = 0 AND entry_id = %d', $entry_id ) );//phpcs:ignore
				if ( isset( $entry_metas->entry_value ) ) {
					$page_return_url = explode( '|', $entry_metas->entry_value );
				} else {
					$page_return_url = array(
						'0' => '1',
						'1' => '',
					);
				}

				if ( isset( $page_return_url[1] ) && $page_return_url[1] != '' ) {
					$pageurl = $page_return_url[1];
				}

				if ( isset( $page_return_url[0] ) && $page_return_url[0] == 0 ) {

					if ( strstr( $pageurl, '?' ) ) {
						$pageurl = esc_url( $pageurl . '&arf_conf=' . $form_id );
					} else {
						$pageurl = esc_url( $pageurl . '?arf_conf=' . $form_id );
					}
				}

				wp_redirect( html_entity_decode( $pageurl ) );
				exit;
			}
		} else {
			if ( isset( $_GET['custom'] ) && $_GET['custom'] != '' ) {
				$customs  = explode( '|', sanitize_text_field($_GET['custom'] ));
				$entry_id = $customs[0];
				$form_id  = $customs[1];

				$pageurl = isset( $_SESSION['arf_return_url'][ $form_id ] ) ? sanitize_text_field($_SESSION['arf_return_url'][ $form_id ]) : '';
				if ( ! isset( $pageurl ) || $pageurl == '' ) {
					$pageurl = get_home_url();
				}

				$admin_email_notification_data = get_option( 'arf_paypal_admin_email_notification_' . $entry_id . '_' . $form_id );
				$user_email_notification_data = get_option( 'arf_paypal_user_email_notification_' . $entry_id . '_' . $form_id );

				if( !empty( $admin_email_notification_data ) ){
					$admin_email_notify_data = json_decode( $admin_email_notification_data, true );

					$arf_to_mail       			= $admin_email_notify_data['arf_admin_emails'];
					$arf_admin_subject  		= $admin_email_notify_data['arf_admin_subject'];
					$arf_admin_mail_body       	= $admin_email_notify_data['arf_admin_mail_body'];
					$arf_admin_reply_to      	= $admin_email_notify_data['arf_admin_reply_to'];
					$arf_admin_reply_to_email   = $admin_email_notify_data['arf_admin_reply_to_email'];
					$arf_admin_reply_to_name 	= $admin_email_notify_data['arf_admin_reply_to_name'];
					$arf_admin_plain_text    	= $admin_email_notify_data['arf_admin_plain_text'];
					$arf_admin_attachments   	= $admin_email_notify_data['arf_admin_attachments'];
					$arf_admin_cc_emails 		= $admin_email_notify_data['arf_admin_cc_emails'];
					$arf_admin_bcc_emails 		= $admin_email_notify_data['arf_admin_bcc_emails'];
					foreach( $arf_to_mail as $email ){
						if( $arformsmain->arforms_is_pro_active() ){
							global $arnotifymodel;
							$arnotifymodel->send_notification_email_user( $email, $arf_admin_subject, $arf_admin_mail_body, $arf_admin_reply_to, $arf_admin_reply_to_name, $arf_admin_plain_text, $arf_admin_attachments, false, false, true, false, $arf_admin_reply_to_email, $arf_admin_cc_emails, $arf_admin_bcc_emails );
						} else {
							$arflitenotifymodel->arflite_send_notification_email_user( $email, $arf_admin_subject, $arf_admin_mail_body, $arf_admin_reply_to, $arf_admin_reply_to_name, $arf_admin_plain_text, $arf_admin_attachments, false, false, true, false, $arf_admin_reply_to_email, $arf_admin_cc_emails, $arf_admin_bcc_emails );
						}
					}
					delete_option( 'arf_paypal_admin_email_notification_' . $entry_id . '_' . $form_id );
				}

				if( !empty( $user_email_notification_data ) ){
					$user_email_notify_data = json_decode( $user_email_notification_data, true );

					$arf_to_mail       = $user_email_notify_data['to'];
					$arf_mail_subject  = $user_email_notify_data['subject'];
					$arf_message       = $user_email_notify_data['message'];
					$arf_reply_to      = $user_email_notify_data['reply_to'];
					$arf_reply_to_name = $user_email_notify_data['reply_to_name'];
					$arf_plain_text    = $user_email_notify_data['plain_text'];
					$arf_attachments   = $user_email_notify_data['attachments'];
					$arf_return_value  = $user_email_notify_data['return_value'];
					$arf_use_only_smtp = $user_email_notify_data['use_only_smtp'];
					$arf_nreply_to     = $user_email_notify_data['nreply_to'];
					if( $arformsmain->arforms_is_pro_active() ){
						global $arnotifymodel;
						$arnotifymodel->send_notification_email_user( $arf_to_mail, $arf_mail_subject, $arf_message, $arf_reply_to, $arf_reply_to_name, $arf_plain_text, $arf_attachments, $arf_return_value, $arf_use_only_smtp, true, false, $arf_nreply_to );
					} else {
						$arflitenotifymodel->arflite_send_notification_email_user( $arf_to_mail, $arf_mail_subject, $arf_message, $arf_reply_to, $arf_reply_to_name, $arf_plain_text, $arf_attachments, $arf_return_value, $arf_use_only_smtp, true, false, $arf_nreply_to );
					}
					delete_option( 'arf_paypal_user_email_notification_' . $entry_id . '_' . $form_id );
				}
				
				do_action( 'arf_after_payment_check' , $entry_id , $form_id );
				$wpdb->update( $tbl_arf_entries, array( 'form_id' => $form_id ), array( 'id' => $entry_id ) );

				$arf_form_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $tbl_arf_forms . ' WHERE id = %d', $form_id ) );//phpcs:ignore
				$arf_form_data = $arf_form_data[0];
				$arf_options   = maybe_unserialize( $arf_form_data->options );

				if ( $arf_options['success_action'] == 'redirect' and isset( $arf_options['success_url'] ) && $arf_options['success_url'] != '' ) {

					if ( isset( $arf_options['arf_data_with_url'] ) && $arf_options['arf_data_with_url'] == 1 ) {
						$fields            = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM `' . $tbl_arf_fields . '` WHERE form_id = %d', $form_id ) );//phpcs:ignore
						$posted_field_data = array();
						foreach ( $fields as $k => $field ) {
							$field_id    = $field->id;
							$entry_value = $wpdb->get_row( $wpdb->prepare( 'SELECT entry_value FROM `' . $tbl_arf_entry_values . '` WHERE entry_id = %d AND field_id = %d', $entry_id, $field_id ) );//phpcs:ignore
							if ( isset( $entry_value ) ) {
								$posted_field_data[ $field_id ] = $entry_value->entry_value;
							}
						}
						//need to confirm with azharsir
						echo $arrecordcontroller->generate_redirect_form( $arf_form_data, $arf_options['success_url'], $arf_options['arf_data_with_url_type'], $posted_field_data );//phpcs:ignore
					} else {
						wp_redirect( $arf_options['success_url'] );
						die;
					}
					exit;
				} else {

					$entry_metas = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $tbl_arf_entry_values . ' WHERE field_id = 0 AND entry_id = %d', $entry_id ) );//phpcs:ignore
					if ( isset( $entry_metas->entry_value ) ) {
						$page_return_url = explode( '|', $entry_metas->entry_value );
					} else {
						$page_return_url = array(
							'0' => '1',
							'1' => '',
						);
					}

					if ( isset( $page_return_url[1] ) and $page_return_url[1] != '' ) {
						$pageurl = $page_return_url[1];
					}

					if ( isset( $page_return_url[0] ) and $page_return_url[0] == 0 ) {

						if ( strstr( $pageurl, '?' ) ) {
							$pageurl = esc_url( $pageurl . '&arf_conf=' . $form_id );
						} else {
							$pageurl = esc_url( $pageurl . '?arf_conf=' . $form_id );
						}
					}
					wp_redirect( html_entity_decode( $pageurl ) );
					exit;
				}
			} else {
				$pageurl = get_home_url();
				wp_redirect( $pageurl );
				exit;
			}
		}
	}

	function paypal_form_dropdown( $name, $default_value = '', $blank = true, $onchange = false ) {
		global $wpdb,$arf_paypal,$tbl_arf_forms,$arflitemaincontroller,$arflitemainhelper;
		$forms                 = $wpdb->get_results( 'SELECT pyl.*,frm.name FROM ' . $arf_paypal->db_paypal_forms . ' pyl INNER JOIN ' . $tbl_arf_forms . ' frm ON frm.id=pyl.form_id' );//phpcs:ignore
		$responder_list_option = '';
		$list_options[''] = '-All Forms -';
		$def_id                = '';
		$def_label             = '-' . $blank . ' -';
		foreach ( $forms as $form ) {
			if ( $default_value == $form->form_id ) {
				$def_id    = $form->form_id;
				$def_label = $form->form_name;
			}
			$form_title             = $this->get_form_name( $form->form_id );
			$list_options[$form->form_id] =	$arflitemainhelper->arflitetruncate( esc_html( stripslashes( $form_title ) ), 50 ) . ' (' . $this->get_order_count( $form->form_id ) . ')';
			
		}
		?>
		
		<?php
		$form_attrs = array();
		if ( $onchange ) {
			$form_attrs = array(
	          'onChange' => $onchange
	      	);
		}
		$name = esc_html( $name );
		$default = esc_attr( $def_id );
		echo $arflitemaincontroller->arflite_selectpicker_dom( $name, $name, 'arf_autocomplete', '',$default, $form_attrs,$list_options, false, array(), false, array(), false, array(), true, 'arf_selectbox_option','arf_selectbox'); //phpcs:ignore
		?>
		<?php
	}

	function get_order_count( $form_id = 0 ) {
		global $wpdb,$arf_paypal;
		$record_count = $wpdb->get_results( $wpdb->prepare( 'SELECT count(*) AS record_count FROM ' . $arf_paypal->db_paypal_order . ' WHERE form_id = %d', $form_id ) );//phpcs:ignore
		$record_count = $record_count[0];
		return ( isset( $record_count->record_count ) ) ? $record_count->record_count : 0;
	}

	function get_form_name( $form_id = 0 ) {
		global $wpdb,$tbl_arf_forms;
		$form_name = $wpdb->get_results( $wpdb->prepare( 'SELECT name FROM ' . $tbl_arf_forms . ' WHERE id = %d', $form_id ) );//phpcs:ignore
		$form_name = $form_name[0];
		return ( isset( $form_name->name ) ) ? stripslashes( $form_name->name ) : '';
	}

	function arfp_form_order() {

		if ( empty( $_POST['_wp_arf_form_orders_nonce'] ) || ( isset( $_POST['_wp_arf_form_orders_nonce'] ) && '' != $_POST['_wp_arf_form_orders_nonce'] && ! wp_verify_nonce( sanitize_text_field( $_POST['_wp_arf_form_orders_nonce'] ), 'arf_paypal_order_nonce' ) ) ) {
			echo esc_attr( 'security_error' );
			die;
		}

		$check_cap = $this->arf_paypal_check_user_cap( 'arfpaypaltransaction', true );

		if ( 'success' != $check_cap ) {
			$user_cap = json_decode( $check_cap, true );
			echo json_encode(
				array(
					'success' => false,
					'message' => $user_cap[0],
				)
			);
			die;
		}

		$form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';

		$startdate = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
		$enddate   = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

		global $style_settings, $wp_scripts;
		$wp_format_date = get_option( 'date_format' );

		if ( $wp_format_date == 'F j, Y' || $wp_format_date == 'm/d/Y' ) {
			$date_format_new = 'mm/dd/yy';
		} elseif ( $wp_format_date == 'd/m/Y' ) {
			$date_format_new = 'dd/mm/yy';
		} elseif ( $wp_format_date == 'Y/m/d' ) {
			$date_format_new = 'dd/mm/yy';
		} else {
			$date_format_new = 'mm/dd/yy';
		}

		$datequery = '';
		if ( $startdate != '' and $enddate != '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$startdate = str_replace( '/', '-', $startdate );
				$enddate   = str_replace( '/', '-', $enddate );
			}
			$new_start_date_var = date( 'Y-m-d', strtotime( $startdate ) );
			$new_end_date_var   = date( 'Y-m-d', strtotime( $enddate ) );

			$datequery .= " and DATE( created_at) >= '" . $new_start_date_var . "' and DATE( created_at) <= '" . $new_end_date_var . "'";
		} elseif ( $startdate != '' and $enddate == '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$startdate = str_replace( '/', '-', $startdate );
			}
			$new_start_date_var = date( 'Y-m-d', strtotime( $startdate ) );

			$datequery .= " and DATE( created_at) >= '" . $new_start_date_var . "'";
		} elseif ( $startdate == '' and $enddate != '' ) {
			if ( $date_format_new == 'dd/mm/yy' ) {
				$enddate = str_replace( '/', '-', $enddate );
			}
			$new_end_date_var = date( 'Y-m-d', strtotime( $enddate ) );

			$datequery .= " and DATE( created_at) <= '" . $new_end_date_var . "'";
		}

		$message = '';
		$errors  = array();
		
		$gridData = $this->arf_paypal_get_order_data( $form_id, $datequery );

		echo wp_json_encode(
			array(
				'errors'   => $errors,
				'message'  => $message,
				'gridData' => $gridData,
			)
		);
		die();
	}

	function defalut_email_content() {
		$message = '';

		$message .= esc_html__( 'Hello admin,', 'arforms-form-builder' ) . "\n\n";
		$message .= esc_html__( 'Payment successfully received from', 'arforms-form-builder' ) . " [site_name]. \r\n\n";
		$message .= esc_html__( 'Following are the payment details:', 'arforms-form-builder' ) . "\r\n\n";
		$message .= esc_html__( 'Transaction id', 'arforms-form-builder' ) . ": [transaction_id] \n";
		$message .= esc_html__( 'Amount paid', 'arforms-form-builder' ) . ": [amount] [currency] \n";
		$message .= esc_html__( 'Payment date', 'arforms-form-builder' ) . ": [payment_date] \n";
		$message .= esc_html__( 'Payer Email', 'arforms-form-builder' ) . ": [payer_email] \n";
		$message .= esc_html__( 'Payer ID', 'arforms-form-builder' ) . ": [payer_id]\n";
		$message .= esc_html__( 'Payer First name', 'arforms-form-builder' ) . ": [payer_fname] \n";
		$message .= esc_html__( 'Payer Last name', 'arforms-form-builder' ) . ': [payer_lname]';
		$message .= "\n\n\n" . esc_html__( 'Thank you', 'arforms-form-builder' );
		$message .= "\n[site_name]";

		return $message;
	}

	function user_defalut_email_content() {
		$message  = '';
		$message .= esc_html__( 'Hello user,', 'arforms-form-builder' ) . "\n\n";
		$message .= esc_html__( 'Payment successfully received from', 'arforms-form-builder' ) . " [site_name]. \r\n\n";
		$message .= esc_html__( 'Following are the payment details:', 'arforms-form-builder' ) . "\r\n\n";
		$message .= esc_html__( 'Transaction id', 'arforms-form-builder' ) . ": [transaction_id] \n";
		$message .= esc_html__( 'Amount paid', 'arforms-form-builder' ) . ": [amount] [currency] \n";
		$message .= esc_html__( 'Payment date', 'arforms-form-builder' ) . ": [payment_date] \n";
		$message .= esc_html__( 'Payer Email', 'arforms-form-builder' ) . ": [payer_email] \n";
		$message .= esc_html__( 'Payer ID', 'arforms-form-builder' ) . ": [payer_id]\n";
		$message .= esc_html__( 'Payer First name', 'arforms-form-builder' ) . ": [payer_fname] \n";
		$message .= esc_html__( 'Payer Last name', 'arforms-form-builder' ) . ': [payer_lname]';
		$message .= "\n\n\n" . esc_html__( 'Thank you', 'arforms-form-builder' );
		$message .= "\n[site_name]";
		return $message;
	}

	function parse_standalone_request() {

		global $armainhelper;
		 

		$plugin = $armainhelper->get_param( 'plugin' );

		$action = isset( $_REQUEST['arfaction'] ) ? 'arfaction' : 'action';

		$action = $armainhelper->get_param( $action );

		if ( ! empty( $plugin ) and $plugin == 'ARForms-Paypal' and ! empty( $action ) and $action == 'csv' ) {

			$this->export_to_csv();
		}
	}

	function export_to_csv() {
		if ( ! current_user_can( 'arfpaypaltransaction' ) ) {

			global $arfsettings;

			wp_die( esc_attr($arfsettings->admin_permission) );
		}

		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( 0 );
		}

		global $current_user, $arfform, $arffield, $arfrecordmeta, $wpdb, $style_settings;

		include ARFLITE_FORMPATH . '/integrations/Payments/PayPal/core/export_data.php';
	}

	function arf_change_form_entry( $form_id, $entry_id, $txn_id ) {
		if ( ! $entry_id || ! $form_id ) {
			return;
		}

		global $wpdb, $tbl_arf_entries;

		$wpdb->update( $tbl_arf_entries, array( 'form_id' => $form_id ), array( 'id' => $entry_id ) );
	}

	function arforms_paypal_menu_with_pro( $arf_submenus ){

		foreach( $arf_submenus as $submenu_key => $submenu_data ){
			if( !empty( $submenu_data['arforms/arforms.php'] ) ) {
				$arforms_menu = $submenu_data['arforms/arforms.php']['menu_items'];

				$paypal_form_submenu = array( 'ARForms', 'ARForms | ' . esc_html__( 'PayPal Configuration', 'arforms-form-builder' ), esc_html__( 'PayPal Configuration', 'arforms-form-builder' ), 'arfpaypalconfiguration', 'ARForms-Paypal', array( $this, 'route' ) );

				$paypal_order_submenu = array( 'ARForms', 'ARForms | ' . esc_html__( 'PayPal Transactions', 'arforms-form-builder' ), esc_html__( 'PayPal Transactions', 'arforms-form-builder' ), 'arfpaypaltransaction', 'ARForms-Paypal-order', array( $this, 'route' ) );

				array_splice( $arforms_menu, 6, 0, [$paypal_form_submenu] );
				array_splice( $arforms_menu, 7, 0, [$paypal_order_submenu] );

				$arf_submenus[ $submenu_key ]['arforms/arforms.php']['menu_items'] = $arforms_menu;
			}
		}

		return $arf_submenus;

	}
	
	function arf_paypal_menu() {

		 
		global $arfsettings;
 
		add_submenu_page( 'ARForms', 'ARForms | ' . esc_html__( 'PayPal Configuration', 'arforms-form-builder' ), esc_html__( 'PayPal Configuration', 'arforms-form-builder' ), 'arfpaypalconfiguration', 'ARForms-Paypal', array( $this, 'route' ) );

		add_submenu_page( 'ARForms', 'ARForms | ' . esc_html__( 'PayPal Transactions', 'arforms-form-builder' ), esc_html__( 'PayPal Transactions', 'arforms-form-builder' ), 'arfpaypaltransaction', 'ARForms-Paypal-order', array( $this, 'route' ) );
		
	}

	function arf_paypal_admin_email_notification_data( $notification_data, $entry_id, $form_id ){
		global $arfform, $wpdb;

		//$is_paypal_form = $arfform->arf_select_db_data( true, '', $this->db_paypal_forms, 'COUNT(id)', 'WHERE form_id = %d', array( $form_id ), '', '', '', true );
		$is_paypal_form = $wpdb->get_row( $wpdb->prepare('SELECT COUNT(id) `'.$this->db_paypal_forms.'` WHERE form_id=%d', array( $form_id)));

		if( $is_paypal_form > 0 ){
			update_option( 'arf_paypal_admin_email_notification_' . $entry_id . '_' . $form_id, json_encode( $notification_data ) );
		}
	}

	function arf_paypal_check_response_v3( $response = array(), $paypal = false ){
		global $is_submit, $arflitenotifymodel, $wpdb, $arfform, $arfrecordmeta;

		if( false == $paypal ){
			$form_id = isset( $response['form_id'] ) ? $response['form_id'] : '';

			if( '' == $form_id ){
				$is_submit = true;
			} else {
				//$is_paypal_form = $arfform->arf_select_db_data( true, '', $this->db_paypal_forms, 'COUNT(id)', 'WHERE form_id = %d', array( $form_id ), '', '', '', true );
				$is_paypal_form = $wpdb->get_var( $wpdb->prepare('SELECT COUNT(id) FROM `'.$this->db_paypal_forms.'` WHERE form_id = %d', array($form_id) ) );
				if( $is_paypal_form > 0 ){

					//$paypal_form_data = $arfform->arf_select_db_data( true, '', $this->db_paypal_forms, '*', 'WHERE form_id = %d', array( $form_id ) );
					$paypal_form_data = $wpdb->get_results( $wpdb->prepare('SELECT * FROM `'.$this->db_paypal_forms.'` WHERE form_id = %d', $form_id ));

					$entry_id = $response['entry_id'];

					$options = maybe_unserialize($paypal_form_data[0]->options);
					$is_submit = false;

					$paypal_field_amount = '';

					if ( '' != $options['amount'] ) {
						$paypal_field_amount = $options['amount'];
					}

					if( !empty( $paypal_field_amount )){
						global $tbl_arf_entry_values;
		
						$arf_get_paypal_amount = $wpdb->get_row( $wpdb->prepare('SELECT * FROM `'.$tbl_arf_entry_values.'` WHERE entry_id = %d AND field_id = %d', $entry_id, $paypal_field_amount ));
		
						if( $arf_get_paypal_amount->entry_value > 0 ){
		
							$is_submit = true;
						} else {
							$is_submit = false;
						}
					}

					update_option( 'arf_paypal_user_email_notification_' . $entry_id .'_'.$form_id, json_encode( $response ) );					

				} else {
					$is_submit = true;
				}
			}
		}
	}

	function arf_paypal_check_response( $response = array(), $paypal = false ) {

		global $is_submit,$arflitenotifymodel,$arf_paypal,$wpdb, $arflitemaincontroller, $tbl_arf_forms, $arformsmain;

		if ( $paypal == false ) {
			$form_id = isset( $response['form_id'] ) ? $response['form_id'] : '';
			if ( $form_id === '' ) {
				$is_submit = true;
			} else {
				global $wpdb;
				$table          = $arf_paypal->db_paypal_forms;
				$is_paypal_form = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( *) FROM `{$table}` WHERE form_id =%d ", $form_id ) );//phpcs:ignore
				if ( $is_paypal_form > 0 ) {
					$arflitemaincontroller->arflite_start_session( true );
					$_SESSION['arf_to_mail']        = isset( $response['to'] ) ? $response['to'] : '';
					$_SESSION['arf_mail_subject']   = isset( $response['subject'] ) ? $response['subject'] : '';
					$_SESSION['arf_message']        = isset( $response['message'] ) ? $response['message'] : '';
					$_SESSION['arf_reply_to']       = isset( $response['reply_to'] ) ? $response['reply_to'] : '';
					$_SESSION['arf_reply_to_name']  = isset( $response['reply_to_name'] ) ? $response['reply_to_name'] : '';
					$_SESSION['arf_plain_text']     = isset( $response['plain_text'] ) ? $response['plain_text'] : '';
					$_SESSION['arf_attachments']    = isset( $response['attachments'] ) ? $response['attachments'] : array();
					$_SESSION['arf_return_value']   = isset( $response['return_value'] ) ? $response['return_value'] : '';
					$_SESSION['arf_use_only_smtp']  = isset( $response['use_only_smtp'] ) ? $response['use_only_smtp'] : '';
					$_SESSION['arf_reply_to_email'] = isset( $response['nreply_to'] ) ? $response['nreply_to'] : '';
					$_SESSION['form_id']            = isset( $response['form_id'] ) ? $response['form_id'] : '';
					$is_submit                      = false;
				} else {
					$is_submit = true;
				}
			}
		} else {
			$is_submit = true;
			if ( isset( $_SESSION['form_id'] ) && $_SESSION['form_id'] != '' ) {
				$form_id      = sanitize_text_field($_SESSION['form_id']);
				$options      = $wpdb->get_row( $wpdb->prepare( 'SELECT options FROM `' . $tbl_arf_forms . '` WHERE ID = %d LIMIT 1', $form_id ) );//phpcs:ignore
				$form_options = maybe_unserialize( $options->options );
				if ( isset( $form_options['auto_responder'] ) && $form_options['auto_responder'] == 1 ) {
					$arf_to_mail       = isset($_SESSION['arf_to_mail']) ? sanitize_text_field($_SESSION['arf_to_mail']) : '';
					$arf_mail_subject  = isset($_SESSION['arf_mail_subject']) ? $_SESSION['arf_mail_subject'] : '';
					$arf_message       = isset($_SESSION['arf_message']) ? sanitize_text_field($_SESSION['arf_message']) : '';
					$arf_reply_to      = isset($_SESSION['arf_replay_to']) ? sanitize_text_field($_SESSION['arf_reply_to']) : '';
					$arf_reply_to_name = isset($_SESSION['arf_replay_to_name']) ? sanitize_text_field($_SESSION['arf_reply_to_name']) : '';
					$arf_plain_text    = isset($_SESSION['arf_plain_text']) ? sanitize_text_field($_SESSION['arf_plain_text']) : '';
					$arf_attachments   = isset($_SESSION['arf_attachments']) ? sanitize_text_field($_SESSION['arf_attachments']) : '';
					$arf_return_value  = isset($_SESSION['use_only_smtp']) ? sanitize_text_field($_SESSION['use_only_smtp']) : '';
					$arf_use_only_smtp = isset($_SESSION['arf_use_only_smtp']) ? sanitize_text_field($_SESSION['arf_use_only_smtp']) : '';
					$arf_nreply_to     = isset($_SESSION['arf_reply_to_email']) ? sanitize_text_field($_SESSION['arf_reply_to_email']) : '';
					unset( $_SESSION['arf_to_mail'] );
					unset( $_SESSION['arf_mail_subject'] );
					unset( $_SESSION['arf_message'] );
					unset( $_SESSION['arf_reply_to'] );
					unset( $_SESSION['arf_reply_to_name'] );
					unset( $_SESSION['arf_plain_text'] );
					unset( $_SESSION['arf_attachments'] );
					unset( $_SESSION['use_only_smtp'] );
					unset( $_SESSION['arf_use_only_smtp'] );
					unset( $_SESSION['form_id'] );
					unset( $_SESSION['arf_from_autoresponder'] );
					unset( $_SESSION['arf_reply_to_email'] );
					if( $arformsmain->arforms_is_pro_active() ){
						global $arnotifymodel;
						$arnotifymodel->send_notification_email_user( $arf_to_mail, $arf_mail_subject, $arf_message, $arf_reply_to, $arf_reply_to_name, $arf_plain_text, $arf_attachments, $arf_return_value, $arf_use_only_smtp, true, false, $arf_nreply_to );
					} else {
						$arflitenotifymodel->arflite_send_notification_email_user( $arf_to_mail, $arf_mail_subject, $arf_message, $arf_reply_to, $arf_reply_to_name, $arf_plain_text, $arf_attachments, $arf_return_value, $arf_use_only_smtp, true, false, $arf_nreply_to );
					}
				}

				if ( isset( $form_options['chk_admin_notification'] ) && $form_options['chk_admin_notification'] == 1 ) {
					$admin_emails = isset( $_SESSION['arf_admin_emails'] ) ? sanitize_text_field($_SESSION['arf_admin_emails']) : array();
					if ( ! empty( $admin_emails ) ) {
						$arf_admin_subject        = isset($_SESSION['arf_admin_subject']) ? sanitize_text_field($_SESSION['arf_admin_subject']) : '';
						$arf_admin_mail_body      = isset($_SESSION['arf_admin_mail_body']) ? sanitize_text_field($_SESSION['arf_admin_mail_body']) : '';
						$arf_admin_reply_to       = isset($_SESSION['arf_admin_reply_to']) ? sanitize_text_field($_SESSION['arf_admin_reply_to']) : '';
						$arf_admin_reply_to_name  = isset($_SESSION['arf_admin_reply_to_name']) ? sanitize_text_field($_SESSION['arf_admin_reply_to_name']) : '';
						$arf_admin_plain_text     = isset($_SESSION['arf_admin_plain_text']) ? sanitize_text_field($_SESSION['arf_admin_plain_text']) : '';
						$arf_admin_attachments    = isset($_SESSION['arf_admin_attachments']) ? sanitize_text_field($_SESSION['arf_admin_attachments']) : '';
						$arf_admin_reply_to_email = isset($_SESSION['arf_admin_reply_to_email']) ? sanitize_text_field($_SESSION['arf_admin_reply_to_email']) : '';
						unset( $_SESSION['arf_admin_emails'] );
						unset( $_SESSION['arf_admin_subject'] );
						unset( $_SESSION['arf_admin_mail_body'] );
						unset( $_SESSION['arf_admin_reply_to'] );
						unset( $_SESSION['arf_admin_reply_to_name'] );
						unset( $_SESSION['arf_admin_plain_text'] );
						unset( $_SESSION['arf_admin_attachments'] );
						unset( $_SESSION['arf_admin_reply_to_email'] );
						foreach ( $admin_emails as $email ) {
							if( $arformsmain->arforms_is_pro_active() ){
								global $arnotifymodel;
								$arnotifymodel->send_notification_email_user( $email, $arf_admin_subject, $arf_admin_mail_body, $arf_admin_reply_to, $arf_admin_reply_to_name, $arf_admin_plain_text, $arf_admin_attachments, false, false, true, false, $arf_admin_reply_to_email );
							} else {
								$arflitenotifymodel->arflite_send_notification_email_user( $email, $arf_admin_subject, $arf_admin_mail_body, $arf_admin_reply_to, $arf_admin_reply_to_name, $arf_admin_plain_text, $arf_admin_attachments, false, false, true, false, $arf_admin_reply_to_email );
							}
						}
					}
				}
			}
		}
	}

	function arf_display_message_content( $content, $form_id ) {

		global $arformcontroller,$wpdb,$tbl_arf_forms, $arformsmain;

		update_option("inside_this_function",'3746');

		$return = array();
		if ( $form_id != '' && isset( $_REQUEST['arf_conf'] ) && $_REQUEST['arf_conf'] == $form_id ) {

			$table     = $tbl_arf_forms;
			$form_data = $wpdb->get_row( $wpdb->prepare( 'SELECT `options` FROM ' . $tbl_arf_forms . " WHERE `id` = '%d'", $form_id ) );//phpcs:ignore
			
			$options = maybe_unserialize( $form_data->options );
			$success_msg = $options['success_msg'];

			$msg  = "<div class='arf_form arflite_main_div_{$form_id}' id='arffrm_{$form_id}_container'><div id='arf_message_success'><div class='msg-detail'><div class='msg-description-success arf_pp_text_align_center'>{$success_msg}</div></div></div>";

			if( $arformsmain->arforms_is_pro_active() ){

				$msg = apply_filters('arf_display_digital_product_link',$msg, $options, $form_id);
			}

			$msg .= "</div>";

			$return['conf_method'] = 'message';
			$return['message']     = $msg;

			if ( empty( $form_data ) ) {
				return $content;
			}
			if ( empty( $return ) ) {
				return $content;
			}
			return wp_json_encode( $return );
		}
		return $content;
	}

	function arf_paypal_check_user_cap( $capability = '', $ajax_call = false ) {

		if ( true == $ajax_call ) {
			if ( ! current_user_can( $capability ) ) {
				return wp_json_encode(
					array(
						esc_html__( 'Sorry, you do not have enough permission to perform this action', 'arforms-form-builder' ),
					)
				);
			}
		}

		$arf_paypal_nonce = isset( $_POST['_wpnonce_arfpaypal'] ) ? $_POST['_wpnonce_arfpaypal'] : '';//phpcs:ignore
		if ( '' == $arf_paypal_nonce ) {
			$arf_paypal_nonce = isset( $_REQUEST['_wpnonce_arfpaypal'] ) ? sanitize_text_field($_REQUEST['_wpnonce_arfpaypal']) : '';
		}

		$arfpaypal_nonce_flag = wp_verify_nonce( $arf_paypal_nonce, 'arforms_paypal_nonce' );

		if ( ! $arfpaypal_nonce_flag ) {
			return wp_json_encode(
				array(
					esc_html__( 'Sorry, your request could not be processed due to security reason', 'arforms-form-builder' ),
				)
			);
		}

		return 'success';

	}

}