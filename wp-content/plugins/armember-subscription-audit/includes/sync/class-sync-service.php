<?php
/**
 * The three operations: Assess (read-only), Update information (stage admin input), Synchronise.
 * Assessment never touches ARMember/WooCommerce. Sync writes only through Arm_Writer, only for members whose
 * LIVE re-assessment says Ready for Sync, never creates a payment, subscription, mandate or agreement.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sync_Service {

	/** @var Collector */
	private $collector;
	/** @var Assessor */
	private $assessor;
	/** @var Http */
	private $http;
	/** @var Secrets */
	private $secrets;
	/** @var Arm_Writer */
	private $writer;

	public function __construct( Collector $collector, Http $http = null, Secrets $secrets = null, Assessor $assessor = null, Arm_Writer $writer = null ) {
		$this->collector = $collector;
		$this->http      = $http ? $http : new Wp_Http();
		$this->secrets   = $secrets ? $secrets : new Secrets_Provider();
		$this->assessor  = $assessor ? $assessor : new Assessor();
		$this->writer    = $writer ? $writer : new Arm_Writer();
	}

	// =========================================================================================
	// Operation 1: Assess
	// =========================================================================================

	/**
	 * @param int  $initiator Admin id; 0 = background worker.
	 * @param bool $lookup    Perform read-only gateway lookups (only from the worker or an explicit admin action).
	 * @return array|null Assessment (null if the member/plan does not exist).
	 */
	public function assess( $user_id, $plan_id, $initiator = 0, $lookup = false, $operation = 'assess' ) {
		$facts = $this->collector->collect( $user_id, $plan_id );
		$log   = new Logger( $operation, $user_id, $plan_id, '', $initiator );
		$log->write( 'Assessment started', Status::STEP_PROCESSING, ( $lookup ? 'With read-only gateway lookup.' : 'Local data only.' ) . ' No ARMember data is changed by an assessment.' );
		if ( ! $facts ) {
			$log->write( 'Member and membership information loaded', Status::STEP_FAILED, 'No ARMember membership row exists for this member and plan.', '', 'not_found' );
			return null;
		}
		$adapter = Gateway_Factory::for_facts( $facts );
		$log->set_gateway( $adapter->id() );
		$result = $this->assessor->assess( $facts, $adapter, array( 'lookup' => $lookup, 'http' => $this->http, 'secrets' => $this->secrets, 'log' => $log->callback() ) );
		$result['_facts_gateway'] = $facts['audit']['gateway'];
		$this->store( $user_id, $plan_id, $result );
		return $result;
	}

	/** Persist the latest assessment (own table only). Sync state is preserved unless told otherwise. */
	public function store( $user_id, $plan_id, array $a, $sync_status = null, $synced = false ) {
		global $wpdb;
		$t   = Installer::table( 'assessments' );
		$now = gmdate( 'Y-m-d H:i:s' );
		$slim = $a;
		unset( $slim['_log'], $slim['_facts_gateway'] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, sync_status FROM `{$t}` WHERE user_id = %d AND plan_id = %d", $user_id, $plan_id ), ARRAY_A );
		$data = array(
			'user_id'        => (int) $user_id,
			'plan_id'        => (int) $plan_id,
			'model'          => $a['model'],
			'gateway_status' => $a['gateway']['status'],
			'arm_status'     => $a['arm']['status'],
			'overall_status' => $a['overall']['status'],
			'missing'        => wp_json_encode( $a['missing'] ),
			'summary'        => wp_json_encode( $this->slim_summary( $slim ) ),
			'assessed_at'    => $now,
			'updated_at'     => $now,
		);
		if ( null !== $sync_status ) {
			$data['sync_status'] = $sync_status;
		}
		if ( $synced ) {
			$data['synced_at'] = $now;
		}
		// A fresh assessment that finds nothing left to do clears a stale "queued/failed" sync state.
		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $t, $data, array( 'id' => (int) $existing['id'] ) );
		} else {
			$data['sync_status'] = null === $sync_status ? Status::SYNC_NONE : $sync_status;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert( $t, $data );
		}
	}

	/**
	 * The stored summary is the full assessment (identifiers and statuses only – adapters never put credentials
	 * into it), so the details page can show the last gateway lookup without calling the gateway again.
	 */
	private function slim_summary( array $a ) {
		return $a;
	}

	/** Decode the stored full assessment of a stored row, or null. */
	public static function decode( $row ) {
		if ( ! $row || empty( $row['summary'] ) ) {
			return null;
		}
		$d = json_decode( $row['summary'], true );
		return is_array( $d ) && isset( $d['fields'] ) ? $d : null;
	}

	/** Read-only live preview (no log rows, no storage, no gateway calls). */
	public function preview( $user_id, $plan_id ) {
		$facts = $this->collector->collect( $user_id, $plan_id );
		if ( ! $facts ) {
			return null;
		}
		return $this->assessor->assess( $facts, Gateway_Factory::for_facts( $facts ), array( 'lookup' => false ) );
	}

	public static function stored( $user_id, $plan_id ) {
		global $wpdb;
		$t = Installer::table( 'assessments' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE user_id = %d AND plan_id = %d", $user_id, $plan_id ), ARRAY_A );
	}

	/** Stored assessments for many members at once (members list; no live calls). @return array<string,array> */
	public static function stored_many( array $pairs ) {
		global $wpdb;
		if ( ! $pairs ) {
			return array();
		}
		$t     = Installer::table( 'assessments' );
		$uids  = array_values( array_unique( array_map( function ( $p ) {
			return (int) $p[0];
		}, $pairs ) ) );
		$ph    = implode( ',', array_fill( 0, count( $uids ), '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE user_id IN ({$ph})", $uids ), ARRAY_A );
		$out   = array();
		foreach ( (array) $rows as $r ) {
			$out[ $r['user_id'] . ':' . $r['plan_id'] ] = $r;
		}
		return $out;
	}

	// =========================================================================================
	// Operation 2: Update information (stage + confirm; nothing is written to ARMember here)
	// =========================================================================================

	/**
	 * Stage an administrator-supplied value for an editable gateway-identifier field.
	 *
	 * @param bool $confirm_ownership Admin explicitly asserts this id belongs to the member.
	 * @param bool $confirm_replace   Admin confirms replacing an existing staged value.
	 * @return array { ok, message }
	 */
	public function stage_value( $user_id, $plan_id, $field_key, $value, $confirm_ownership, $confirm_replace, $admin_id ) {
		global $wpdb;
		$log   = new Logger( 'update', $user_id, $plan_id, '', $admin_id );
		$facts = $this->collector->collect( $user_id, $plan_id );
		if ( ! $facts ) {
			return array( 'ok' => false, 'message' => 'Member/plan not found.' );
		}
		$adapter = Gateway_Factory::for_facts( $facts );
		$log->set_gateway( $adapter->id() );
		$def = null;
		foreach ( $adapter->fields( $facts ) as $f ) {
			if ( $f['key'] === $field_key ) {
				$def = $f;
			}
		}
		$value = trim( (string) $value );
		if ( ! $def || ! $def['editable'] ) {
			$log->write( 'Administrator input rejected', Status::STEP_FAILED, 'Field is not editable for this payment model.', (string) $field_key );
			return array( 'ok' => false, 'message' => 'This field cannot be entered manually for this payment model.' );
		}
		if ( '' === $value || ( '' !== $def['format'] && ! preg_match( $def['format'], $value ) ) ) {
			$log->write( 'Administrator input rejected', Status::STEP_FAILED, 'Value does not match the required format (' . $def['format_hint'] . ').', (string) $field_key );
			return array( 'ok' => false, 'message' => 'Invalid format. Expected ' . $def['format_hint'] . '.' );
		}
		// Never stage something ARMember already contradicts.
		foreach ( $def['arm_paths'] as $p ) {
			$cur = Gateway_Adapter::raw_value( $facts, $p );
			if ( '' !== $cur && $cur !== $value ) {
				$log->write( 'Administrator input rejected', Status::STEP_FAILED, 'ARMember already holds a different value (' . implode( '.', $p ) . '); it is never overwritten automatically.', (string) $field_key );
				return array( 'ok' => false, 'message' => 'ARMember already stores a different value for this field. Resolve it manually; Sync never overwrites it.' );
			}
		}
		$t   = Installer::table( 'mappings' );
		$old = isset( $facts['staged'][ $field_key ] ) ? $facts['staged'][ $field_key ]['value'] : '';
		if ( '' !== $old && $old !== $value && ! $confirm_replace ) {
			return array( 'ok' => false, 'message' => 'A different staged value exists (' . $old . '). Tick the confirmation to replace it.' );
		}
		$now = gmdate( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( $wpdb->prepare( "INSERT INTO `{$t}` (user_id, plan_id, field_key, value, confirmed, status, note, created_by, created_at, updated_at) VALUES (%d,%d,%s,%s,%d,'pending','',%d,%s,%s) ON DUPLICATE KEY UPDATE value = VALUES(value), confirmed = VALUES(confirmed), status = 'pending', created_by = VALUES(created_by), updated_at = VALUES(updated_at)", $user_id, $plan_id, $field_key, $value, $confirm_ownership ? 1 : 0, $admin_id, $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$log->change( $field_key, $old, $value, 'stage_value' . ( $confirm_ownership ? '+ownership_confirmed' : '' ) );
		$log->write( 'Administrator input staged', Status::STEP_COMPLETED, 'Value validated and staged (not yet in ARMember). It must pass gateway verification before Sync.', $field_key );
		return array( 'ok' => true, 'message' => 'Value staged. A gateway verification has been queued.' );
	}

	/** Discard a staged value (audited). */
	public function discard_staged( $user_id, $plan_id, $field_key, $admin_id ) {
		global $wpdb;
		$t   = Installer::table( 'mappings' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$old = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM `{$t}` WHERE user_id=%d AND plan_id=%d AND field_key=%s AND status IN ('pending','verified')", $user_id, $plan_id, $field_key ) );
		if ( null === $old ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $t, array( 'status' => 'rejected', 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ), array( 'user_id' => $user_id, 'plan_id' => $plan_id, 'field_key' => $field_key ) );
		$log = new Logger( 'update', $user_id, $plan_id, '', $admin_id );
		$log->change( $field_key, $old, '', 'discard_staged' );
		return true;
	}

	// =========================================================================================
	// Operation 3: Synchronise (worker only)
	// =========================================================================================

	/**
	 * @return array { result: completed|linked_unverified|skipped|retry|failed, message }
	 */
	public function sync( $user_id, $plan_id, $initiator = 0 ) {
		$log = new Logger( 'sync', $user_id, $plan_id, '', $initiator );
		$log->write( 'Synchronisation started', Status::STEP_PROCESSING, 'Live re-assessment decides eligibility; the queue-time status is not trusted.' );
		$this->store_sync_state( $user_id, $plan_id, Status::SYNC_RUNNING );

		$facts = $this->collector->collect( $user_id, $plan_id );
		if ( ! $facts ) {
			$log->write( 'Eligibility check', Status::STEP_FAILED, 'Membership row no longer exists.' );
			$this->store_sync_state( $user_id, $plan_id, Status::SYNC_FAILED );
			return array( 'result' => 'failed', 'message' => 'Membership not found.' );
		}
		$adapter = Gateway_Factory::for_facts( $facts );
		$log->set_gateway( $adapter->id() );
		$a = $this->assessor->assess( $facts, $adapter, array( 'lookup' => true, 'http' => $this->http, 'secrets' => $this->secrets, 'log' => $log->callback() ) );
		$this->store( $user_id, $plan_id, $a );

		if ( Status::GW_API_ERROR === $a['gateway']['status'] ) {
			$log->write( 'Eligibility check', Status::STEP_WARNING, 'Gateway unreachable; the job will be retried.', '', $a['gateway']['explain'] );
			$this->store_sync_state( $user_id, $plan_id, Status::SYNC_QUEUED );
			return array( 'result' => 'retry', 'message' => $a['gateway']['explain'] );
		}
		if ( ! $adapter->supports_sync() || ! Status::syncable( $a['overall']['status'] ) || ! $a['proposals'] ) {
			$msg = 'Not eligible: ' . $a['overall']['label'] . ' – ' . $a['overall']['action'];
			$log->write( 'Eligibility check', Status::STEP_SKIPPED, $msg );
			$this->store_sync_state( $user_id, $plan_id, Status::SYNC_NONE );
			return array( 'result' => 'skipped', 'message' => $msg );
		}
		$log->write( 'Eligibility check', Status::STEP_COMPLETED, 'Gateway verified, ownership proven, ' . count( $a['proposals'] ) . ' reference(s) to store.' );

		$w = $this->writer->apply( $user_id, $plan_id, $a['proposals'], $log );
		if ( $w['error'] || ! $w['verified'] ) {
			$this->store_sync_state( $user_id, $plan_id, $w['applied'] ? Status::SYNC_PARTIAL : Status::SYNC_FAILED );
			return array( 'result' => 'failed', 'message' => $w['error'] ? $w['error'] : 'Saved mapping could not be verified.' );
		}
		$this->mark_staged_applied( $user_id, $plan_id );

		// Post-sync: re-assess against the saved data, including renewal compatibility.
		$log->write( 'Renewal processing compatibility check', Status::STEP_PROCESSING, 'Re-assessing saved data.' );
		$facts2 = $this->collector->collect( $user_id, $plan_id );
		$b      = $this->assessor->assess( $facts2, Gateway_Factory::for_facts( $facts2 ), array( 'lookup' => true, 'http' => $this->http, 'secrets' => $this->secrets, 'log' => $log->callback() ) );
		if ( $b['proposals'] ) {
			$this->store( $user_id, $plan_id, $b, Status::SYNC_PARTIAL, true );
			$log->write( 'Synchronisation finished', Status::STEP_WARNING, 'Some references are still missing after saving.' );
			return array( 'result' => 'failed', 'message' => 'Partially synchronised.' );
		}
		if ( Status::OV_RENEWAL_OK === $b['overall']['status'] ) {
			$this->store( $user_id, $plan_id, $b, Status::SYNC_DONE, true );
			$log->write( 'Synchronisation finished', Status::STEP_COMPLETED, 'Mapping saved, verified, and renewal prerequisites confirmed.' );
			return array( 'result' => 'completed', 'message' => 'Sync completed.' );
		}
		$this->store( $user_id, $plan_id, $b, Status::SYNC_LINKED, true );
		$log->write( 'Synchronisation finished', Status::STEP_WARNING, 'Gateway Linked – ARMember Renewal Not Verified: ' . $b['arm']['explain'] );
		return array( 'result' => 'linked_unverified', 'message' => 'Gateway Linked – ARMember Renewal Not Verified.' );
	}

	public function store_sync_state( $user_id, $plan_id, $state ) {
		global $wpdb;
		$t = Installer::table( 'assessments' );
		$now = gmdate( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( $wpdb->prepare( "INSERT INTO `{$t}` (user_id, plan_id, sync_status, updated_at) VALUES (%d,%d,%s,%s) ON DUPLICATE KEY UPDATE sync_status = VALUES(sync_status), updated_at = VALUES(updated_at)", $user_id, $plan_id, $state, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	private function mark_staged_applied( $user_id, $plan_id ) {
		global $wpdb;
		$t = Installer::table( 'mappings' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE `{$t}` SET status = 'applied', updated_at = %s WHERE user_id = %d AND plan_id = %d AND status IN ('pending','verified')", gmdate( 'Y-m-d H:i:s' ), $user_id, $plan_id ) );
	}
}
