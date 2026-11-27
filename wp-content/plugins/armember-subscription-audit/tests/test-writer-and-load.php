<?php
/**
 * Stub-WordPress tests: full plugin load, Arm_Writer safety rules, queue duplicate-key behaviour is DB-level
 * (see INTEGRATION.md). Included by run-tests.php.
 */

use ARMSA\Status;

// ---- Minimal WordPress stubs (only what the tested code touches) -------------------------------
$GLOBALS['stub_meta']  = array();
$GLOBALS['stub_calls'] = array( 'update_user_meta' => 0, 'wpdb_insert' => array() );
if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $uid, $key, $single = false ) {
		return isset( $GLOBALS['stub_meta'][ $uid ][ $key ] ) ? $GLOBALS['stub_meta'][ $uid ][ $key ] : '';
	}
	function update_user_meta( $uid, $key, $val ) {
		++$GLOBALS['stub_calls']['update_user_meta'];
		$GLOBALS['stub_meta'][ $uid ][ $key ] = unserialize( serialize( $val ) );
		return true;
	}
	function wp_cache_delete() {
		return true;
	}
	function absint( $v ) {
		return abs( (int) $v );
	}
	function wp_generate_uuid4() {
		return 'test-run';
	}
}
class Stub_Wpdb {
	public $prefix = 'wp_';
	public function insert( $table, $data ) {
		$GLOBALS['stub_calls']['wpdb_insert'][] = array( $table, $data );
		return 1;
	}
}
$GLOBALS['wpdb'] = new Stub_Wpdb();
require_once dirname( __DIR__ ) . '/includes/sync/class-installer.php';
require_once dirname( __DIR__ ) . '/includes/sync/class-arm-writer.php';

section( 'Arm_Writer (stubbed WordPress meta)' );
$base_meta = array(
	'arm_user_gateway' => 'stripe', 'arm_payment_mode' => 'auto_debit_subscription', 'arm_next_due_payment' => 1900000000,
	'arm_subscr_id' => '', 'arm_stripe' => array( 'customer_id' => 'cus_1AAA', 'transaction_id' => '', 'extra' => 'keep' ),
	'arm_sent_msgs' => array( 'a' => 1 ), 'arm_current_plan_detail' => array( 'x' => 'y' ),
);
$GLOBALS['stub_meta'][10]['arm_user_plan_5'] = $base_meta;
$props = array(
	array( 'field' => 'stripe_subscription_id', 'path' => array( 'arm_subscr_id' ), 'proposed' => 'sub_1AAA' ),
	array( 'field' => 'stripe_subscription_id', 'path' => array( 'arm_stripe', 'transaction_id' ), 'proposed' => 'sub_1AAA' ),
);
$log = new \ARMSA\Logger( 'sync', 10, 5, 'stripe_native', 1 );
$w   = new \ARMSA\Arm_Writer();
$res = $w->apply( 10, 5, $props, $log );
$saved = $GLOBALS['stub_meta'][10]['arm_user_plan_5'];
t( 'writer stores both references and verifies the saved result', $res['verified'] && 2 === count( $res['applied'] ) && 'sub_1AAA' === $saved['arm_subscr_id'] && 'sub_1AAA' === $saved['arm_stripe']['transaction_id'] );
t( 'writer preserves every other key (gateway, mode, next due, messages, plan detail, extra stripe key)', 'stripe' === $saved['arm_user_gateway'] && 'auto_debit_subscription' === $saved['arm_payment_mode'] && 1900000000 === $saved['arm_next_due_payment'] && 'keep' === $saved['arm_stripe']['extra'] && $saved['arm_sent_msgs'] === $base_meta['arm_sent_msgs'] && $saved['arm_current_plan_detail'] === $base_meta['arm_current_plan_detail'] );
t( 'writer records before/after in the audit table for each change', 2 === count( array_filter( $GLOBALS['stub_calls']['wpdb_insert'], function ( $c ) { return false !== strpos( $c[0], 'armsa_changes' ) && '' === $c[1]['old_value'] && 'sub_1AAA' === $c[1]['new_value']; } ) ) );

$before = $GLOBALS['stub_calls']['update_user_meta'];
$res2   = $w->apply( 10, 5, $props, $log );
t( 'idempotent: a second run writes nothing', $res2['verified'] && ! $res2['applied'] && $before === $GLOBALS['stub_calls']['update_user_meta'] );

$GLOBALS['stub_meta'][10]['arm_user_plan_5']['arm_subscr_id'] = 'sub_OLDER';
$before = $GLOBALS['stub_calls']['update_user_meta'];
$res3   = $w->apply( 10, 5, array( array( 'field' => 'stripe_subscription_id', 'path' => array( 'arm_subscr_id' ), 'proposed' => 'sub_NEW' ) ), $log );
t( 'never overwrites an existing different value', 'sub_OLDER' === $GLOBALS['stub_meta'][10]['arm_user_plan_5']['arm_subscr_id'] && ! $res3['applied'] && $res3['skipped'] && $before === $GLOBALS['stub_calls']['update_user_meta'] );

$res4 = $w->apply( 10, 5, array( array( 'field' => 'x', 'path' => array( 'arm_payment_mode' ), 'proposed' => 'manual_subscription' ) ), $log );
t( 'refuses to touch payment mode (path not whitelisted)', 'auto_debit_subscription' === $GLOBALS['stub_meta'][10]['arm_user_plan_5']['arm_payment_mode'] && ! $res4['applied'] );
$res5 = $w->apply( 10, 5, array( array( 'field' => 'x', 'path' => array( 'arm_user_gateway' ), 'proposed' => 'paypal' ) ), $log );
t( 'refuses to touch gateway (path not whitelisted)', 'stripe' === $GLOBALS['stub_meta'][10]['arm_user_plan_5']['arm_user_gateway'] && ! $res5['applied'] );
$res6 = $w->apply( 10, 5, array( array( 'field' => 'x', 'path' => array( 'arm_stripe', 'customer_id' ), 'proposed' => "cus_x'; DROP TABLE" ) ), $log );
t( 'rejects malformed identifier values', ! $res6['applied'] );
$res7 = $w->apply( 11, 5, $props, $log );
t( 'missing plan meta: nothing written, reported', $res7['error'] && ! $res7['applied'] );

// ---- Source-level safety assertions ---------------------------------------------------------
section( 'Static safety checks' );
$root = dirname( __DIR__ );
$all  = '';
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/includes' ) ) as $f ) {
	if ( $f->isFile() && 'php' === $f->getExtension() ) {
		$all .= "\n" . file_get_contents( $f->getPathname() );
	}
}
t( 'no dependency on arsenal-settings anywhere in plugin code', false === stripos( $all, 'arsenal' ) );
$views = file_get_contents( $root . '/admin/views/sync-panel.php' ) . file_get_contents( $root . '/admin/views/members-list.php' );
t( 'all POST handlers verify capability + nonce (guard())', substr_count( $all, '$this->guard();' ) >= 8 );
t( 'writes to ARMember data happen only in Arm_Writer', 1 === preg_match_all( '/update_user_meta\((?!\s*get_current_user_id\(\), self::COLUMNS_META)/', $all ) && false !== strpos( file_get_contents( $root . '/includes/sync/class-arm-writer.php' ), 'update_user_meta(' ) );
t( 'no raw SQL against ARMember tables for writes (no UPDATE/DELETE/INSERT on arm_ tables)', ! preg_match( '/(UPDATE|DELETE\s+FROM|INSERT\s+INTO)\s+`?\{?\$?[a-z_>\-]*arm_(?!ARMSA)(?!sa)/i', preg_replace( '/armsa_/', '', $all ) ) );
t( 'credentials are never rendered in views', false === stripos( $views, 'secret' ) && false === stripos( $views, 'signature' ) );
t( 'logs/changes tables have no update or delete code', ! preg_match( '/(UPDATE|DELETE FROM)\s+`?\{\$(logs|chg)\}/i', $all ) && ! preg_match( '/->(update|delete)\(\s*Installer::table\(\s*\'(logs|changes)\'/', $all ) );
