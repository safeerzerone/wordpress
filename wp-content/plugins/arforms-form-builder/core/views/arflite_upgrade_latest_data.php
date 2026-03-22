<?php
if( !defined( 'ABSPATH' ) ) exit;
if ( version_compare( $arflitenewdbversion, '1.3', '<' ) ) {
	$nextEvent = strtotime( '+1 week' );

	wp_schedule_single_event( $nextEvent, 'arflite_display_ratenow_popup' );
}

if ( version_compare( $arflitenewdbversion, '1.5.1', '<' ) ) {
	global $wpdb, $tbl_arf_forms, $tbl_arf_fields;

	$wpdb->query( 'ALTER TABLE `' . $tbl_arf_forms . "` ADD `arflite_update_form` TINYINT(1) NOT NULL DEFAULT '0'" ); //phpcs:ignore

	$get_form_ids = $wpdb->get_results( $wpdb->prepare( 'SELECT form_id FROM `' . $tbl_arf_fields . '` WHERE ( type = %s OR type = %s OR type = %s ) GROUP BY form_id', 'select', 'checkbox', 'radio' ) ); //phpcs:ignore

	if ( ! empty( $get_form_ids ) ) {
		foreach ( $get_form_ids as $form_data ) {
			$form_id = $form_data->form_id;

			$wpdb->update(
				$tbl_arf_forms,
				array(
					'arflite_update_form' => 1,
				),
				array(
					'id' => $form_id,
				)
			);
		}
	}
}

if ( version_compare( $arflitenewdbversion, '1.5.2', '<' ) ) {
	global $wpdb, $tbl_arf_forms;

	$wpdb->update(
		$tbl_arf_forms,
		array(
			'arflite_update_form' => 1,
		),
		array(
			'is_template' => 0,
		)
	);
}

if( version_compare( $arflitenewdbversion, '1.5.9', '<') ){
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$tbl_arf_settings = $wpdb->prefix . 'arf_settings';

	$sql = "CREATE TABLE IF NOT EXISTS `{$tbl_arf_settings}`(
		`setting_id` int(11) NOT NULL AUTO_INCREMENT,
		`setting_name` varchar(255) NOT NULL,
		`setting_value` TEXT DEFAULT NULL,
		`setting_type` varchar(255) DEFAULT NULL,
		`created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`setting_id`)
	) {$charset_collate}";
		
	dbDelta( $sql );
	if ( $wpdb->last_error !== '' ) {
		update_option( 'ARF_ERROR_' . time() . rand(), 'ERROR===>' . htmlspecialchars( $wpdb->last_result, ENT_QUOTES ) . 'QUERY===>' . htmlspecialchars( $wpdb->last_query, ENT_QUOTES ) ); 
	} else {
		update_option( 'arforms_setting_table_exists', 1 );
	}

	$tbl_arf_forms = $wpdb->prefix .'arflite_forms';
	$check_table = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema=%s AND table_name=%s",DB_NAME,$tbl_arf_forms));

	if( 1 == $check_table ){
		$wpdb->query( "ALTER TABLE {$tbl_arf_forms} ADD COLUMN arf_is_lite_form tinyint(1) default 0, ADD COLUMN arf_lite_form_id int(11) default NULL AFTER arf_is_lite_form" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_arf_forms is table name defined globally. False
		$wpdb->query( "UPDATE {$tbl_arf_forms} SET arf_is_lite_form = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_arf_forms is table name defined globally. False Positive alarm 
	}

	update_option( 'arforms_use_legacy_tables', 1 );
	update_option( 'arforms_process_db_update', 1 );
}

if( version_compare( $arflitenewdbversion, '1.6.0', '<') ){
	global $arformsmain,$wpdb, $tbl_arf_settings;

	if( $arformsmain->arforms_is_pro_active() ){
		$gmail_oauth_data = get_option('arf_gmail_api_response_data');
		if( !empty( $gmail_oauth_data )){
			$arforms_gmail_oauth_data = $gmail_oauth_data;
		}
		$arf_gmail_email = get_option('arf_gmail_api_connected_email');
		if( !empty( $arf_gmail_email ) ){
			$arf_connected_email = $arf_gmail_email;
		}
		$gmail_access_token = get_option('arf_gmail_api_access_token');
		if( !empty( $gmail_access_token) ){
			$arf_gmail_access_token = $gmail_access_token;
		}
	} else {
		$gmail_oauth_data = get_option('arflite_gmail_api_response_data');
		if( !empty( $gmail_oauth_data )){
			$arforms_gmail_oauth_data = $gmail_oauth_data;
		}
		$arf_gmail_email = get_option('arflite_gmail_api_connected_email');
		if( !empty( $arf_gmail_email ) ){
			$arf_connected_email = $arf_gmail_email;
		}
		$gmail_access_token = get_option('arflite_gmail_api_access_token');
		if( !empty( $gmail_access_token) ){
			$arf_gmail_access_token = $gmail_access_token;
		}
	}
	if( !empty( $arforms_gmail_oauth_data )){
		$arformsmain->arforms_update_settings( 'arf_gmail_api_response_data', $arforms_gmail_oauth_data, 'general_settings' );
	} 
	if( !empty( $arf_connected_email)){
		$arformsmain->arforms_update_settings( 'arf_gmail_api_connected_email', $arf_connected_email, 'general_settings' );
	}
	if( !empty( $arf_gmail_access_token)){
		$arformsmain->arforms_update_settings( 'arf_gmail_api_access_token', $arf_gmail_access_token, 'general_settings' );
	}
	
	$get_all_general_settings = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl_arf_settings} WHERE setting_type = %s", 'general_settings' ) ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_arf_settings is table name defined globally. False

	if( empty( $get_all_general_settings ) ){
		update_option( 'arforms_process_db_update', 1 );
	}

	
}

if( version_compare( $arflitenewdbversion, '1.6.1', '<') ){
	global $wpdb, $arformsmain;

	$charset_collate = '';

	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}

	$tbl_arf_debug_log_setting 	= $wpdb->prefix . 'arf_debug_log_setting';
	$tbl_arf_fields				= $wpdb->prefix . 'arf_fields';

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE IF NOT EXISTS {$tbl_arf_debug_log_setting} (
		`arf_debug_log_id` bigint(11) NOT NULL AUTO_INCREMENT,
		`arf_debug_log_ref_id` bigint(11) NOT NULL DEFAULT '0',
		`arf_debug_log_type` varchar(255) DEFAULT NULL,
		`arf_debug_log_event` varchar(255) DEFAULT NULL,
		`arf_debug_log_event_from` varchar(255) DEFAULT NULL,
		`arf_debug_log_raw_data` TEXT DEFAULT NULL,		
		`arf_debug_log_added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`arf_debug_log_id`)
	) {$charset_collate};";

	dbDelta( $sql );

	$tbl_arf_settings = $wpdb->prefix . 'arf_settings';

	$arforms_log = $arformsmain->arf_log_default_options();
	foreach( $arforms_log as $log_key=>$log_val ){
		global $tbl_arf_settings, $wpdb;
		$wpdb->insert(
			$tbl_arf_settings,
			array(
				'setting_name' => $log_key,
				'setting_value' => $log_val,
				'setting_type' => 'debug_log_settings'
			),
			array(
				'%s',
				'%s',
				'%s'
			)
		);
	}

	/** delete flag */
	delete_option( 'arforms_process_db_update' );

	/** Delete all schedular settings */
	$get_schedular_settings = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl_arf_settings} WHERE setting_type = %s", 'scheduling_settings' ) ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_arf_settings is table name defined globally. False Positive alarm

	if( $get_schedular_settings > 0 ){
		$wpdb->delete(
			$tbl_arf_settings,
			array(
				'setting_type' => 'scheduling_settings'
			)
		);
	}

	/** Check for the settings */
	$get_all_general_settings = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl_arf_settings} WHERE setting_type = %s", 'general_settings' ) ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_arf_settings is table name defined globally. False Positive alarm

	if( empty( $get_all_general_settings ) ){
		if( $arformsmain->arforms_is_premium_available() ){
			/** Process to check if it's been allowed to process with pro */
			$arflite_options = get_option( 'arf_options' );
		} else {
			$arflite_options = get_option( 'arflite_options' );
		}

		foreach( $arflite_options as $setting_key => $setting_val ){
			$setting_val = is_array( $setting_val ) ? wp_json_encode( $setting_val ) : $setting_val;
			$wpdb->insert(
				$tbl_arf_settings,
				array(
					'setting_name' => $setting_key,
					'setting_value' => $setting_val,
					'setting_type' => 'general_settings'
				),
				array(
					'%s',
					'%s',
					'%s'
				)
			);
		}
	}
	
	if( $arformsmain->arforms_is_pro_active() ){
		$gmail_oauth_data = get_option('arf_gmail_api_response_data');
		if( !empty( $gmail_oauth_data )){
			$arforms_gmail_oauth_data = $gmail_oauth_data;
		}
		$arf_gmail_email = get_option('arf_gmail_api_connected_email');
		if( !empty( $arf_gmail_email ) ){
			$arf_connected_email = $arf_gmail_email;
		}
		$gmail_access_token = get_option('arf_gmail_api_access_token');
		if( !empty( $gmail_access_token) ){
			$arf_gmail_access_token = $gmail_access_token;
		}
	} else {
		$gmail_oauth_data = get_option('arflite_gmail_api_response_data');
		if( !empty( $gmail_oauth_data )){
			$arforms_gmail_oauth_data = $gmail_oauth_data;
		}
		$arf_gmail_email = get_option('arflite_gmail_api_connected_email');
		if( !empty( $arf_gmail_email ) ){
			$arf_connected_email = $arf_gmail_email;
		}
		$gmail_access_token = get_option('arflite_gmail_api_access_token');
		if( !empty( $gmail_access_token) ){
			$arf_gmail_access_token = $gmail_access_token;
		}
	}
	
	if( !empty( $arforms_gmail_oauth_data )){
		$arformsmain->arforms_update_settings( 'arf_gmail_api_response_data', $arforms_gmail_oauth_data, 'general_settings' );
	}
	if( !empty( $arf_connected_email)){
		$arformsmain->arforms_update_settings( 'arf_gmail_api_connected_email', $arf_connected_email, 'general_settings' );
	}
	if( !empty( $arf_gmail_access_token)){
		$arformsmain->arforms_update_settings( 'arf_gmail_api_access_token', $arf_gmail_access_token, 'general_settings' );
	}

	$gmail_api_clientid = get_option( 'arflite_gmail_api_clientid' );
	$gmail_api_clientsecret = get_option( 'arflite_gmail_api_clientsecret');
	if( $arformsmain->arforms_is_pro_active() ){
		$gmail_api_clientid = get_option( 'arf_gmail_api_clientid' );
		$gmail_api_clientsecret = get_option( 'arf_gmail_api_clientsecret');
	}

	$get_gmail_api_client_id = $arformsmain->arforms_get_settings( 'gmail_api_clientid', 'general_settings' );

	if( empty( $get_gmail_api_client_id ) ){
		$arformsmain->arforms_update_settings( 'gmail_api_clientid', $gmail_api_clientid, 'general_settings' );
	}

	$get_gmail_api_client_secret = $arformsmain->arforms_get_settings( 'gmail_api_clientsecret', 'general_settings' );

	if( empty( $get_gmail_api_client_secret ) ){
		$arformsmain->arforms_update_settings( 'gmail_api_clientsecret', $gmail_api_clientsecret, 'general_settings' );
	}

	global $arforms_schedular;
	$parmas = array(
		'hook' => 'arforms_merge_tables_159',
		'is_pro_check' => 1,
		'execute_with_pro' => 0,
		'use_pro_data' => 0,
		'hook_order' => 1
	);
	$arforms_schedular->arforms_do_migrate_159_tables( $parmas );

	$arf_is_field_order_exists = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$tbl_arf_fields} LIKE %s", 'field_order' ) ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_arf_fields is table name defined globally. False Positive alarm

	if( false == $arf_is_field_order_exists ){
		$wpdb->query( "ALTER TABLE {$tbl_arf_fields} ADD COLUMN field_order int(11) DEFAULT NULL AFTER form_id" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_arf_fields is table name defined globally. False Positive alarm
	}

	$global_custom_css = '';
	if( $arformsmain->arforms_is_premium_available() ){
		$global_custom_css = get_option('arf_global_css');
	} else {
		$global_custom_css = get_option('arflite_global_css');
	}

	$wpdb->insert(
		$tbl_arf_settings,
		array(
			'setting_name' => 'arf_global_css',
			'setting_value' => $global_custom_css,
			'setting_type' => 'general_settings'
		)
	);

}

if( version_compare( $arflitenewdbversion, '1.6.2', '<') ){

	global $wpdb;

	$arf_entry_separator = get_option('arflite_form_entry_separator');
	if( !empty( $arf_entry_separator )){
		update_option( 'arf_form_entry_separator', $arf_entry_separator );
	}

	$arf_form_columns = get_option('arfliteformcolumnlist');
	if( !empty( $arf_form_columns )){
		update_option('arfformcolumnlist', $arf_form_columns);
	}

	$tbl_arf_settings = $wpdb->prefix . 'arf_settings';

	$wpdb->insert(
		$tbl_arf_settings,
		array(
			'setting_name' => 'anonymous_data',
			'setting_value' => 0,
			'setting_type' => 'general_settings'
		)
	);
}

if( version_compare( $arflitenewdbversion, '1.7.1', '<') ){

	global $arformsmain, $wpdb, $tbl_arf_entries, $tbl_arf_paypal_forms, $tbl_arf_paypal_order, $tbl_arf_settings;

	$wpdb->query( "ALTER TABLE {$tbl_arf_entries} ADD status TINYINT(1) NOT NULL DEFAULT 0 AFTER user_id" );

	$wpdb->query( "UPDATE {$tbl_arf_entries} SET status = 1" );

	if( ! $arformsmain->arforms_is_pro_active() && '' == get_option('arf_db_version') ){
		$arf_update_templates = true;
		include(ARFLITE_MODELS_PATH."/arflitetemplate.php");	
		$arf_update_templates = false;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include ABSPATH . '/wp-admin/includes/plugin.php';
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	/* for GooglereCaptcha addon */
	if (is_plugin_active('arformsgooglecaptcha/arformsgooglecaptcha.php')) {
		$arformsmain->arforms_update_settings( 'arforms_gcaptcha', 1, 'arforms_module' );
		deactivate_plugins('arformsgooglecaptcha/arformsgooglecaptcha.php');
    } else {
		$arformsmain->arforms_update_settings( 'arforms_gcaptcha', 0, 'arforms_module' );
	}
    

	$arformsmain->arforms_update_settings( 'arforms_tcaptcha', 0, 'arforms_module' );

	if( is_plugin_active( 'arformspaypal/arformspaypal.php') ){
		$arformsmain->arforms_update_settings( 'arforms_paypal', 1, 'arforms_module' );
	} else {
		$arformsmain->arforms_update_settings( 'arforms_paypal', 0, 'arforms_module' );

		$charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$charset_collate .= " COLLATE $wpdb->collate";
			}
		}

		$sql = "CREATE TABLE IF NOT EXISTS {$tbl_arf_paypal_forms} ( 
    
				id int( 11) NOT NULL auto_increment,
				
				form_id int( 11) NOT NULL,
				
				form_name varchar( 255) default NULL,
				
				options longtext default NULL,
				
				created_at datetime NOT NULL,
					
				PRIMARY KEY ( id)

			) {$charset_collate};";

		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS {$tbl_arf_paypal_order} ( 
    
				id int( 11) NOT NULL auto_increment,
				
				item_name varchar( 255) default NULL,
				
				txn_id varchar( 255) default NULL,
				
				payment_status varchar( 255) default NULL,

				mc_gross float( 11,2) default NULL,

				mc_currency varchar( 255) default NULL,
				
				quantity varchar( 255) default NULL,
				
				payer_email varchar( 255) default NULL,
				
				payer_name varchar( 255) default NULL,
				
				payment_type varchar( 255) default NULL,
						
				user_id int( 11) default NULL,
				
				entry_id int( 11) default NULL,  

				form_id int( 11) default NULL,
				
				payment_date varchar( 255) NOT NULL,
				
				created_at datetime NOT NULL,
				
				is_verified tinyint( 1) default 0, 

				PRIMARY KEY ( id)

			) {$charset_collate};";

		dbDelta( $sql );
	}

    $wpdb->insert(
		$tbl_arf_settings,
		array(
			'setting_name' => 'arfmainformiplogging',
			'setting_value' => 0,
			'setting_type' => 'general_settings'
		)
	);

	$wpdb->insert(
		$tbl_arf_settings,
		array(
			'setting_name' => 'empty_msg',
			'setting_value' => 'Sorry, you cannot submit an empty form.',
			'setting_type' => 'general_settings'
		)
	);

	$args  = array(
		'role'   => 'administrator',
		'fields' => 'id',
	);
	$users = get_users( $args );
	if ( count( $users ) > 0 ) {
		foreach ( $users as $key => $user_id ) {

			global $current_user;
			$arfroles = array(
				'arfpaypalconfiguration' => esc_html__( 'Configure PayPal Forms', 'arforms-form-builder' ),
				'arfpaypaltransaction'   => esc_html__( 'View PayPal Transactions', 'arforms-form-builder')
			);

			$userObj = new WP_User( $user_id );
			foreach ( $arfroles as $arfrole => $arfroledescription ) {
				$userObj->add_cap( $arfrole );
			}
			unset( $arfrole );
			unset( $arfroles );
			unset( $arfroledescription );
		}
	}

	
}
update_option( 'arflite_db_version', '1.8.1' );
delete_transient( 'arforms_form_builder_addon_page_notice' );

global $arflitenewdbversion;
$arflitenewdbversion = '1.8.1';

update_option( 'arflite_new_version_installed', intval( 1 ) );
update_option( 'arflite_update_date_' . $arflitenewdbversion, current_time( 'mysql' ) );
