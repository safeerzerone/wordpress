<?php
/**
 * Plugin-owned tables (dbDelta, dynamic prefix). No credentials are ever stored in them.
 *
 * armsa_jobs         queue (unique active_key = duplicate prevention)
 * armsa_logs         append-only processing timeline
 * armsa_changes      append-only administrator before/after audit
 * armsa_assessments  latest assessment + sync state per member/plan (drives the members list)
 * armsa_mappings     staged administrator-supplied values awaiting verification/sync (see docs: why this table exists)
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {

	const DB_VERSION_OPTION = 'armsa_db_version';
	const DB_VERSION        = '2.0.0';

	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'armsa_' . $name;
	}

	/** Runs on activation and on admin_init when the stored version is older. */
	public static function maybe_install() {
		if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}
		self::install();
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();

		$jobs = self::table( 'jobs' );
		$logs = self::table( 'logs' );
		$chg  = self::table( 'changes' );
		$ass  = self::table( 'assessments' );
		$map  = self::table( 'mappings' );

		dbDelta(
			"CREATE TABLE {$jobs} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				job_type VARCHAR(20) NOT NULL,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				plan_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				status VARCHAR(20) NOT NULL DEFAULT 'queued',
				active_key VARCHAR(100) DEFAULT NULL,
				attempts SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
				max_attempts SMALLINT(5) UNSIGNED NOT NULL DEFAULT 3,
				run_after DATETIME NOT NULL,
				locked_until DATETIME DEFAULT NULL,
				last_error TEXT,
				result VARCHAR(190) DEFAULT NULL,
				created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY active_key (active_key),
				KEY status_run (status, run_after),
				KEY member (user_id, plan_id)
			) {$c};"
		);

		dbDelta(
			"CREATE TABLE {$logs} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				run_id VARCHAR(40) NOT NULL,
				created_at DATETIME NOT NULL,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				plan_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				operation VARCHAR(20) NOT NULL,
				step VARCHAR(190) NOT NULL,
				gateway VARCHAR(40) NOT NULL DEFAULT '',
				status VARCHAR(20) NOT NULL,
				description TEXT,
				ref VARCHAR(190) NOT NULL DEFAULT '',
				error_detail TEXT,
				initiated_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY member (user_id, plan_id, id),
				KEY run_id (run_id)
			) {$c};"
		);

		dbDelta(
			"CREATE TABLE {$chg} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				run_id VARCHAR(40) NOT NULL,
				created_at DATETIME NOT NULL,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				plan_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				field_key VARCHAR(80) NOT NULL,
				old_value TEXT,
				new_value TEXT,
				action VARCHAR(40) NOT NULL,
				admin_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY member (user_id, plan_id, id)
			) {$c};"
		);

		dbDelta(
			"CREATE TABLE {$ass} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				plan_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				model VARCHAR(40) NOT NULL DEFAULT '',
				gateway_status VARCHAR(40) NOT NULL DEFAULT 'gw_not_assessed',
				arm_status VARCHAR(40) NOT NULL DEFAULT 'arm_not_assessed',
				overall_status VARCHAR(50) NOT NULL DEFAULT 'not_assessed',
				sync_status VARCHAR(30) NOT NULL DEFAULT 'never',
				missing TEXT,
				summary LONGTEXT,
				assessed_at DATETIME DEFAULT NULL,
				synced_at DATETIME DEFAULT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY member_plan (user_id, plan_id),
				KEY overall (overall_status),
				KEY sync (sync_status)
			) {$c};"
		);

		dbDelta(
			"CREATE TABLE {$map} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				plan_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				field_key VARCHAR(80) NOT NULL,
				value VARCHAR(190) NOT NULL,
				confirmed TINYINT(1) NOT NULL DEFAULT 0,
				status VARCHAR(20) NOT NULL DEFAULT 'pending',
				note VARCHAR(255) NOT NULL DEFAULT '',
				created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY member_field (user_id, plan_id, field_key),
				KEY value_idx (value)
			) {$c};"
		);

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}
}
