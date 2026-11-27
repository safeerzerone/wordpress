<?php
/**
 * Phase 2 admin handlers (admin-post.php). Every handler: capability check → nonce → sanitised input →
 * action → redirect with a notice. Gateway calls and ARMember writes never happen here – only queueing,
 * local (no-network) assessment and staging of administrator input.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sync_Admin {

	const NONCE      = 'armsa_p2';
	const CAPABILITY = 'manage_options';

	/** @var Member_Repository */
	private $repo;

	public function __construct( Member_Repository $repo ) {
		$this->repo = $repo;
	}

	public function register() {
		foreach ( array( 'assess_local', 'queue_assess', 'stage', 'discard_staged', 'queue_sync', 'retry_jobs', 'bulk', 'export', 'columns' ) as $a ) {
			add_action( 'admin_post_armsa_' . $a, array( $this, 'handle_' . $a ) );
		}
	}

	// ---------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------

	private function guard() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'armember-subscription-audit' ), 403 );
		}
		check_admin_referer( self::NONCE );
	}

	private function service() {
		return new Sync_Service( new Collector( $this->repo ) );
	}

	private function ids() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in guard().
		return array( isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0, isset( $_POST['plan_id'] ) ? absint( $_POST['plan_id'] ) : 0 );
	}

	private function notice( $type, $msg ) {
		set_transient( 'armsa_notice_' . get_current_user_id(), array( $type, $msg ), 120 );
	}

	public static function render_notice() {
		$n = get_transient( 'armsa_notice_' . get_current_user_id() );
		if ( ! is_array( $n ) ) {
			return;
		}
		delete_transient( 'armsa_notice_' . get_current_user_id() );
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( 'success' === $n[0] ? 'success' : ( 'error' === $n[0] ? 'error' : 'warning' ) ), esc_html( $n[1] ) );
	}

	private function back_to_member( $user_id ) {
		wp_safe_redirect( Admin_Menu::details_url( $user_id ) );
		exit;
	}

	private function back_to_list( array $args = array() ) {
		wp_safe_redirect( add_query_arg( array_merge( array( 'page' => Admin_Menu::SLUG ), $args ), admin_url( 'admin.php' ) ) );
		exit;
	}

	// ---------------------------------------------------------------------------------------
	// Per-member handlers
	// ---------------------------------------------------------------------------------------

	/** Operation 1 (local): inspects WordPress/ARMember/WooCommerce only – no gateway request, no writes to them. */
	public function handle_assess_local() {
		$this->guard();
		list( $uid, $pid ) = $this->ids();
		$pv = $this->service()->preview( $uid, $pid );
		if ( $pv && 'recurring' !== $pv['payment_type']['type'] ) {
			$this->notice( 'warning', 'Assessment applies to recurring (Subscription / Recurring Payment) plans only. This plan is: ' . $pv['payment_type']['label'] . '.' );
			$this->back_to_member( $uid );
		}
		$a = $this->service()->assess( $uid, $pid, get_current_user_id(), false );
		$this->notice( $a ? 'success' : 'error', $a ? 'Local assessment completed: ' . $a['overall']['label'] . '. (No gateway was contacted; use "Retrieve Gateway Information" for that.)' : 'Member / plan not found.' );
		$this->back_to_member( $uid );
	}

	/** Retrieve / verify via gateway: queued, runs in the worker. */
	public function handle_queue_assess() {
		$this->guard();
		list( $uid, $pid ) = $this->ids();
		$r = ( new Queue() )->enqueue( Queue::TYPE_ASSESS, $uid, $pid, get_current_user_id() );
		Queue::kick();
		$this->notice( $r['ok'] ? 'success' : 'warning', $r['ok'] ? 'Gateway lookup queued (job #' . $r['job_id'] . '). Refresh in a minute to see the result.' : $r['reason'] );
		$this->back_to_member( $uid );
	}

	/** Operation 2: stage an administrator-supplied value (validated, audited), then queue verification. */
	public function handle_stage() {
		$this->guard();
		list( $uid, $pid ) = $this->ids();
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in guard().
		$field   = isset( $_POST['field_key'] ) ? sanitize_key( wp_unslash( $_POST['field_key'] ) ) : '';
		$value   = isset( $_POST['field_value'] ) ? sanitize_text_field( wp_unslash( $_POST['field_value'] ) ) : '';
		$own     = ! empty( $_POST['confirm_ownership'] );
		$replace = ! empty( $_POST['confirm_replace'] );
		$confirm = ! empty( $_POST['confirm_stage'] );
		// phpcs:enable
		if ( ! $confirm ) {
			$this->notice( 'error', 'Please tick the confirmation box before saving.' );
			$this->back_to_member( $uid );
		}
		$r = $this->service()->stage_value( $uid, $pid, $field, $value, $own, $replace, get_current_user_id() );
		if ( $r['ok'] ) {
			( new Queue() )->enqueue( Queue::TYPE_ASSESS, $uid, $pid, get_current_user_id() );
			Queue::kick();
		}
		$this->notice( $r['ok'] ? 'success' : 'error', $r['message'] );
		$this->back_to_member( $uid );
	}

	public function handle_discard_staged() {
		$this->guard();
		list( $uid, $pid ) = $this->ids();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$field = isset( $_POST['field_key'] ) ? sanitize_key( wp_unslash( $_POST['field_key'] ) ) : '';
		$ok    = $this->service()->discard_staged( $uid, $pid, $field, get_current_user_id() );
		$this->notice( $ok ? 'success' : 'warning', $ok ? 'Staged value discarded (recorded in the audit trail).' : 'Nothing to discard.' );
		$this->back_to_member( $uid );
	}

	/** Operation 3 (queue only): eligibility is re-checked live by the worker. */
	public function handle_queue_sync() {
		$this->guard();
		list( $uid, $pid ) = $this->ids();
		$r = ( new Queue() )->enqueue( Queue::TYPE_SYNC, $uid, $pid, get_current_user_id() );
		Queue::kick();
		$this->notice( $r['ok'] ? 'success' : 'warning', $r['ok'] ? 'Synchronisation queued (job #' . $r['job_id'] . ').' : $r['reason'] );
		$this->back_to_member( $uid );
	}

	public function handle_retry_jobs() {
		$this->guard();
		list( $uid, $pid ) = $this->ids();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ids = isset( $_POST['job_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['job_ids'] ) ) : array();
		$n   = ( new Queue() )->retry_failed( $ids, get_current_user_id() );
		$this->notice( $n ? 'success' : 'warning', $n ? $n . ' failed job(s) re-queued.' : 'No eligible failed job to retry (skipped jobs are not retried – they were ineligible).' );
		if ( $uid ) {
			$this->back_to_member( $uid );
		}
		$this->back_to_list();
	}

	// ---------------------------------------------------------------------------------------
	// Bulk
	// ---------------------------------------------------------------------------------------

	private function filters_from_post() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in guard().
		$g = function ( $k ) {
			return isset( $_POST[ $k ] ) ? sanitize_key( wp_unslash( $_POST[ $k ] ) ) : '';
		};
		$f = array(
			'search'    => isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '',
			'gateway'   => isset( Admin_Menu::gateway_options()[ $g( 'gateway' ) ] ) ? $g( 'gateway' ) : '',
			'mode'      => isset( Admin_Menu::mode_options()[ $g( 'mode' ) ] ) ? $g( 'mode' ) : '',
			'status'    => isset( Subscription_Audit::status_labels()[ $g( 'audit_status' ) ] ) ? $g( 'audit_status' ) : '',
			'gw_ready'  => isset( Status::gateway_labels()[ $g( 'gw_ready' ) ] ) ? $g( 'gw_ready' ) : '',
			'arm_ready' => isset( Status::arm_labels()[ $g( 'arm_ready' ) ] ) ? $g( 'arm_ready' ) : '',
			'sync'      => isset( Status::overall_labels()[ $g( 'sync' ) ] ) ? $g( 'sync' ) : '',
			'orderby'   => 'id',
			'order'     => 'asc',
		);
		// phpcs:enable
		return $f;
	}

	public function handle_bulk() {
		$this->guard();
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in guard().
		$action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$scope  = isset( $_POST['bulk_scope'] ) ? sanitize_key( wp_unslash( $_POST['bulk_scope'] ) ) : 'selected';
		$sel    = isset( $_POST['selected'] ) ? (array) wp_unslash( $_POST['selected'] ) : array();
		// phpcs:enable
		$filters = $this->filters_from_post();
		$back    = array_filter(
			array( 's' => $filters['search'], 'gateway' => $filters['gateway'], 'mode' => $filters['mode'], 'audit_status' => $filters['status'], 'gw_ready' => $filters['gw_ready'], 'arm_ready' => $filters['arm_ready'], 'sync' => $filters['sync'] ),
			'strlen'
		);

		if ( 'retry_failed' === $action ) {
			global $wpdb;
			$t   = Installer::table( 'jobs' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$ids = $wpdb->get_col( "SELECT id FROM `{$t}` WHERE status = 'failed' ORDER BY id ASC LIMIT 200" );
			$n   = ( new Queue() )->retry_failed( array_map( 'intval', (array) $ids ), get_current_user_id() );
			$this->notice( $n ? 'success' : 'warning', $n . ' failed job(s) re-queued.' );
			$this->back_to_list( $back );
		}
		if ( ! in_array( $action, array( 'assess', 'queue_sync' ), true ) ) {
			$this->notice( 'error', 'Choose a bulk action.' );
			$this->back_to_list( $back );
		}

		$pairs = array();
		if ( 'filtered' === $scope ) {
			foreach ( $this->repo->matching_rows( $filters, 2000 ) as $row ) {
				$pairs[ $row['user']['id'] . ':' . $row['plan_id'] ] = array( (int) $row['user']['id'], (int) $row['plan_id'] );
			}
		} else {
			foreach ( $sel as $s ) {
				if ( preg_match( '/^(\d+):(\d+)$/', (string) $s, $m ) ) {
					$pairs[ $m[1] . ':' . $m[2] ] = array( (int) $m[1], (int) $m[2] );
				}
			}
		}
		if ( ! $pairs ) {
			$this->notice( 'warning', 'No members selected.' );
			$this->back_to_list( $back );
		}
		$type    = 'assess' === $action ? Queue::TYPE_ASSESS : Queue::TYPE_SYNC;
		$queue   = new Queue();
		$queued  = 0;
		$reasons = array();
		foreach ( $pairs as $p ) {
			$r = $queue->enqueue( $type, $p[0], $p[1], get_current_user_id() );
			if ( $r['ok'] ) {
				++$queued;
			} else {
				$reasons[ $r['reason'] ] = ( isset( $reasons[ $r['reason'] ] ) ? $reasons[ $r['reason'] ] : 0 ) + 1;
			}
		}
		Queue::kick();
		$msg = $queued . ' of ' . count( $pairs ) . ' queued.';
		foreach ( $reasons as $why => $n ) {
			$msg .= ' ' . $n . ' skipped: ' . $why;
		}
		$this->notice( $queued ? 'success' : 'warning', $msg );
		$this->back_to_list( $back );
	}

	/** CSV of assessed members with missing information (CSV-injection safe). */
	public function handle_export() {
		$this->guard();
		$rows = $this->repo->matching_rows( $this->filters_from_post(), 5000 );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="armsa-missing-information-' . gmdate( 'Ymd-His' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'User ID', 'Name', 'Email', 'Plan ID', 'Plan', 'Gateway', 'Gateway readiness', 'ARMember readiness', 'Overall status', 'Missing information', 'Last assessment (UTC)' ) );
		$safe = function ( $v ) {
			$v = (string) $v;
			return ( '' !== $v && in_array( $v[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) ? "'" . $v : $v;
		};
		foreach ( $rows as $r ) {
			$p = $r['p2'];
			if ( ! $p['has'] || ( ! $p['missing'] && ! in_array( $p['overall'], array( Status::OV_MISSING, Status::OV_MANUAL, Status::OV_LOOKUP ), true ) ) ) {
				continue;
			}
			fputcsv(
				$out,
				array_map(
					$safe,
					array( $r['user']['id'], $r['user']['name'], $r['user']['email'], $r['plan_id'], $r['plan_name'], Subscription_Audit::gateway_label( $r['audit']['gateway'] ), Status::label( 'gateway', $p['gateway_status'] ), Status::label( 'arm', $p['arm_status'] ), Status::label( 'overall', $p['effective'] ), implode( '; ', $p['missing'] ), $p['assessed_at'] )
				)
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	// ---------------------------------------------------------------------------------------
	// Column visibility (per administrator; Member ID, Email and Action are always shown)
	// ---------------------------------------------------------------------------------------

	const COLUMNS_META = 'armsa_hidden_columns';

	/** @return array<string,string> key => label of the optional columns. */
	public static function optional_columns() {
		return array(
			'name'       => __( 'Member Name', 'armember-subscription-audit' ),
			'plan'       => __( 'Membership Plan', 'armember-subscription-audit' ),
			'gateway'    => __( 'Gateway', 'armember-subscription-audit' ),
			'method'     => __( 'Selected Payment Method', 'armember-subscription-audit' ),
			'mode'       => __( 'Payment Mode', 'armember-subscription-audit' ),
			'ref'        => __( 'Subscription Reference', 'armember-subscription-audit' ),
			'lastdate'   => __( 'Last Payment Date', 'armember-subscription-audit' ),
			'amount'     => __( 'Last Payment Amount', 'armember-subscription-audit' ),
			'paystatus'  => __( 'Last Payment Status', 'armember-subscription-audit' ),
			'audit'      => __( 'Audit Status', 'armember-subscription-audit' ),
			'gwready'    => __( 'Gateway Readiness', 'armember-subscription-audit' ),
			'armready'   => __( 'ARMember Readiness', 'armember-subscription-audit' ),
			'sync'       => __( 'Sync Status', 'armember-subscription-audit' ),
			'missing'    => __( 'Missing Information', 'armember-subscription-audit' ),
			'assessed'   => __( 'Last Assessment', 'armember-subscription-audit' ),
			'synced'     => __( 'Last Sync', 'armember-subscription-audit' ),
		);
	}

	/** @return string[] Hidden column keys for the current administrator. */
	public static function hidden_columns() {
		$h = get_user_meta( get_current_user_id(), self::COLUMNS_META, true );
		return is_array( $h ) ? array_values( array_intersect( $h, array_keys( self::optional_columns() ) ) ) : array();
	}

	public function handle_columns() {
		$this->guard();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in guard().
		$show   = isset( $_POST['show_cols'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['show_cols'] ) ) : array();
		$hidden = array_values( array_diff( array_keys( self::optional_columns() ), $show ) );
		if ( isset( $_POST['reset_cols'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$hidden = array();
		}
		update_user_meta( get_current_user_id(), self::COLUMNS_META, $hidden );
		$this->back_to_list( array_filter( array(
			's'            => isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		), 'strlen' ) );
	}

	// ---------------------------------------------------------------------------------------
	// View helpers
	// ---------------------------------------------------------------------------------------

	/** Hidden fields + nonce for a POST form. */
	public static function form_open( $action, $user_id = 0, $plan_id = 0 ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="armsa-inline">';
		echo '<input type="hidden" name="action" value="armsa_' . esc_attr( $action ) . '">';
		wp_nonce_field( self::NONCE );
		if ( $user_id ) {
			echo '<input type="hidden" name="user_id" value="' . esc_attr( $user_id ) . '"><input type="hidden" name="plan_id" value="' . esc_attr( $plan_id ) . '">';
		}
	}

	/** A button that is disabled with a visible explanation when not applicable. */
	public static function button( $label, $enabled, $why = '', $primary = false ) {
		printf(
			'<button type="submit" class="button%s" %s>%s</button>%s',
			$primary ? ' button-primary' : '',
			$enabled ? '' : 'disabled',
			esc_html( $label ),
			( ! $enabled && '' !== $why ) ? ' <span class="description armsa-why">' . esc_html( $why ) . '</span>' : ''
		);
	}
}
