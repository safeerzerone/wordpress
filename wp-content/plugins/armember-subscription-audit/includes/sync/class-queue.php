<?php
/**
 * Background queue. Admin requests only INSERT jobs; gateway calls and writes happen in the worker.
 *
 * - Duplicate prevention: `active_key` is UNIQUE and set only while a job is queued/processing.
 * - Job locking: atomic UPDATE … WHERE status='queued' claims a job (rows_affected must be 1);
 *   a lock (`locked_until`) expires so crashed workers are recovered.
 * - Idempotency: sync re-assesses live and writes only missing values, so replays are harmless.
 * - Retry: gateway/API errors re-queue with back-off up to max_attempts; ineligible records are "skipped".
 * - Transport: Action Scheduler async action when present, otherwise WP-Cron (single + every-minute pickup).
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Queue {

	const HOOK       = 'armsa_process_queue';
	const SCHEDULE   = 'armsa_every_minute';
	const LOCK_SECS  = 300;
	const BATCH      = 5;
	const BUDGET_SEC = 45;

	const TYPE_ASSESS = 'assess';
	const TYPE_SYNC   = 'sync';

	/** @var Sync_Service|null */
	private $service;

	public function __construct( Sync_Service $service = null ) {
		$this->service = $service;
	}

	public static function register_hooks( Queue $queue ) {
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
		add_action( self::HOOK, array( $queue, 'process_batch' ) );
		add_action( 'init', array( __CLASS__, 'ensure_scheduled' ) );
	}

	public static function cron_schedules( $s ) {
		$s[ self::SCHEDULE ] = array( 'interval' => 60, 'display' => 'Every minute (ARMember Subscription Audit queue)' );
		return $s;
	}

	public static function ensure_scheduled() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + 60, self::SCHEDULE, self::HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/** Ask the transport to run soon (never runs gateway code in the admin request). */
	public static function kick() {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::HOOK, array(), 'armsa' ) ) {
				return;
			}
			as_enqueue_async_action( self::HOOK, array(), 'armsa' );
			return;
		}
		// WP-Cron ignores a duplicate event inside its 10-minute window; the every-minute event picks the job up then.
		wp_schedule_single_event( time() + 5, self::HOOK );
	}

	// ---------------------------------------------------------------------------------------
	// Enqueue
	// ---------------------------------------------------------------------------------------

	/**
	 * @return array { ok:bool, status:'queued'|'duplicate'|'rejected', job_id:int, reason:string }
	 */
	public function enqueue( $type, $user_id, $plan_id, $admin_id ) {
		global $wpdb;
		if ( ! in_array( $type, array( self::TYPE_ASSESS, self::TYPE_SYNC ), true ) ) {
			return array( 'ok' => false, 'status' => 'rejected', 'job_id' => 0, 'reason' => 'Unknown job type.' );
		}
		if ( self::TYPE_SYNC === $type ) {
			$stored = Sync_Service::stored( $user_id, $plan_id );
			if ( ! $stored || ! Status::syncable( $stored['overall_status'] ) ) {
				$why = $stored ? Status::label( 'overall', $stored['overall_status'] ) : 'Not assessed';
				return array( 'ok' => false, 'status' => 'rejected', 'job_id' => 0, 'reason' => 'Not eligible (' . $why . '). Only members assessed as "Ready for Sync" can be queued.' );
			}
		}
		$t   = Installer::table( 'jobs' );
		$now = gmdate( 'Y-m-d H:i:s' );
		$key = $type . ':' . (int) $user_id . ':' . (int) $plan_id;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$n = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `{$t}` (job_type, user_id, plan_id, status, active_key, attempts, max_attempts, run_after, created_by, created_at, updated_at) VALUES (%s,%d,%d,'queued',%s,0,3,%s,%d,%s,%s)", $type, $user_id, $plan_id, $key, $now, $admin_id, $now, $now ) );
		if ( ! $n ) {
			return array( 'ok' => false, 'status' => 'duplicate', 'job_id' => 0, 'reason' => 'An identical job is already queued or running.' );
		}
		$id  = (int) $wpdb->insert_id;
		$log = new Logger( 'queue', $user_id, $plan_id, '', $admin_id );
		$log->write( ucfirst( $type ) . ' job queued', Status::STEP_COMPLETED, 'Job #' . $id . ' will run in the background.', (string) $id );
		if ( self::TYPE_SYNC === $type ) {
			( new Sync_Service( new Collector( self::repo() ) ) )->store_sync_state( $user_id, $plan_id, Status::SYNC_QUEUED );
		}
		return array( 'ok' => true, 'status' => 'queued', 'job_id' => $id, 'reason' => '' );
	}

	/** Shared repository builder (the worker runs outside the admin screens). */
	public static function repo() {
		return new Member_Repository( new Payment_History(), new Subscription_Audit() );
	}

	// ---------------------------------------------------------------------------------------
	// Worker
	// ---------------------------------------------------------------------------------------

	public function process_batch() {
		global $wpdb;
		$t     = Installer::table( 'jobs' );
		$start = time();
		$this->recover_stale();
		$done = 0;
		while ( $done < self::BATCH && ( time() - $start ) < self::BUDGET_SEC ) {
			$now = gmdate( 'Y-m-d H:i:s' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$t}` WHERE status = 'queued' AND run_after <= %s ORDER BY id ASC LIMIT 1", $now ) );
			if ( ! $id ) {
				break;
			}
			$lock = gmdate( 'Y-m-d H:i:s', time() + self::LOCK_SECS );
			// Atomic claim: only one worker can flip queued → processing.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$claimed = $wpdb->query( $wpdb->prepare( "UPDATE `{$t}` SET status='processing', locked_until=%s, attempts=attempts+1, updated_at=%s WHERE id=%d AND status='queued'", $lock, $now, $id ) );
			if ( 1 !== (int) $claimed ) {
				continue; // Someone else got it.
			}
			$this->run_job( $id );
			++$done;
		}
		if ( $done >= self::BATCH ) {
			self::kick(); // More may be waiting.
		}
	}

	private function service() {
		if ( ! $this->service ) {
			$this->service = new Sync_Service( new Collector( self::repo() ) );
		}
		return $this->service;
	}

	private function run_job( $id ) {
		global $wpdb;
		$t   = Installer::table( 'jobs' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE id = %d", $id ), ARRAY_A );
		if ( ! $job ) {
			return;
		}
		$uid = (int) $job['user_id'];
		$pid = (int) $job['plan_id'];
		try {
			if ( self::TYPE_ASSESS === $job['job_type'] ) {
				$a = $this->service()->assess( $uid, $pid, 0, true, 'retrieve' );
				if ( null === $a ) {
					$this->finish( $id, 'failed', 'Membership not found.', 'not_found' );
				} elseif ( Status::GW_API_ERROR === $a['gateway']['status'] ) {
					$this->retry_or_fail( $job, $a['gateway']['explain'] );
				} else {
					$this->finish( $id, 'completed', '', $a['overall']['status'] );
				}
				return;
			}
			$r = $this->service()->sync( $uid, $pid, 0 );
			switch ( $r['result'] ) {
				case 'retry':
					$this->retry_or_fail( $job, $r['message'] );
					break;
				case 'skipped':
					$this->finish( $id, 'skipped', $r['message'], 'skipped' );
					break;
				case 'failed':
					$this->finish( $id, 'failed', $r['message'], 'failed' );
					break;
				default:
					$this->finish( $id, 'completed', '', $r['result'] );
			}
		} catch ( \Throwable $e ) {
			$msg = Logger::redact( $e->getMessage() );
			( new Logger( $job['job_type'], $uid, $pid, '', 0 ) )->write( 'Worker exception', Status::STEP_FAILED, 'Unhandled error in background job.', (string) $id, $msg );
			$this->retry_or_fail( $job, $msg );
		}
	}

	private function retry_or_fail( array $job, $error ) {
		global $wpdb;
		$t        = Installer::table( 'jobs' );
		$attempts = (int) $job['attempts'] + 0; // Already incremented at claim time; re-read to be exact.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$attempts = (int) $wpdb->get_var( $wpdb->prepare( "SELECT attempts FROM `{$t}` WHERE id = %d", $job['id'] ) );
		$log      = new Logger( $job['job_type'], $job['user_id'], $job['plan_id'], '', 0 );
		if ( $attempts >= (int) $job['max_attempts'] ) {
			$log->write( 'Job failed', Status::STEP_FAILED, 'Giving up after ' . $attempts . ' attempt(s).', (string) $job['id'], $error );
			$this->finish( (int) $job['id'], 'failed', $error, 'failed' );
			if ( self::TYPE_SYNC === $job['job_type'] ) {
				$this->service()->store_sync_state( (int) $job['user_id'], (int) $job['plan_id'], Status::SYNC_FAILED );
			}
			return;
		}
		$delay = (int) pow( 2, $attempts ) * 60; // 2, 4, 8 minutes.
		$log->write( 'Job will be retried', Status::STEP_WARNING, 'Attempt ' . $attempts . ' of ' . $job['max_attempts'] . ' failed; retry in ' . ( $delay / 60 ) . ' min.', (string) $job['id'], $error );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $t, array( 'status' => 'queued', 'locked_until' => null, 'last_error' => $error, 'run_after' => gmdate( 'Y-m-d H:i:s', time() + $delay ), 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ), array( 'id' => (int) $job['id'] ) );
	}

	/** Final state: clears active_key so the same job may be queued again later. */
	private function finish( $id, $status, $error, $result ) {
		global $wpdb;
		$t = Installer::table( 'jobs' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $t, array( 'status' => $status, 'active_key' => null, 'locked_until' => null, 'last_error' => $error, 'result' => mb_substr( (string) $result, 0, 190 ), 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ), array( 'id' => (int) $id ) );
	}

	/** A worker that died leaves jobs 'processing' with an expired lock. */
	private function recover_stale() {
		global $wpdb;
		$t   = Installer::table( 'jobs' );
		$now = gmdate( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE `{$t}` SET status = IF(attempts >= max_attempts, 'failed', 'queued'), active_key = IF(attempts >= max_attempts, NULL, active_key), last_error = 'Worker lock expired', locked_until = NULL, updated_at = %s WHERE status = 'processing' AND locked_until < %s", $now, $now ) );
	}

	// ---------------------------------------------------------------------------------------
	// Admin helpers
	// ---------------------------------------------------------------------------------------

	/** Retry failed jobs (not skipped ones). @return int re-queued count */
	public function retry_failed( array $job_ids, $admin_id ) {
		global $wpdb;
		$t = Installer::table( 'jobs' );
		$n = 0;
		foreach ( array_map( 'absint', $job_ids ) as $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE id = %d AND status = 'failed'", $id ), ARRAY_A );
			if ( ! $job ) {
				continue;
			}
			$key = $job['job_type'] . ':' . (int) $job['user_id'] . ':' . (int) $job['plan_id'];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$ok = $wpdb->query( $wpdb->prepare( "UPDATE `{$t}` SET status='queued', active_key=%s, attempts=0, last_error=NULL, run_after=%s, updated_at=%s WHERE id=%d AND status='failed'", $key, gmdate( 'Y-m-d H:i:s' ), gmdate( 'Y-m-d H:i:s' ), $id ) );
			if ( $ok ) {
				( new Logger( 'queue', $job['user_id'], $job['plan_id'], '', $admin_id ) )->write( 'Failed job re-queued', Status::STEP_COMPLETED, 'Job #' . $id . ' reset for retry.', (string) $id );
				++$n;
			}
			// A duplicate active job makes the UPDATE fail on the unique key – that is the intended protection.
		}
		if ( $n ) {
			self::kick();
		}
		return $n;
	}

	/** @return array[] Latest jobs for a member. */
	public static function jobs_for( $user_id, $plan_id, $limit = 10 ) {
		global $wpdb;
		$t = Installer::table( 'jobs' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE user_id = %d AND plan_id = %d ORDER BY id DESC LIMIT %d", $user_id, $plan_id, $limit ), ARRAY_A );
	}

	/** Queue counters for the list header. */
	public static function counts() {
		global $wpdb;
		$t = Installer::table( 'jobs' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = (array) $wpdb->get_results( "SELECT status, COUNT(*) c FROM `{$t}` GROUP BY status", ARRAY_A );
		$out  = array();
		foreach ( $rows as $r ) {
			$out[ $r['status'] ] = (int) $r['c'];
		}
		return $out;
	}
}
