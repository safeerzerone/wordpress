<?php
/**
 * Read-only assessment: facts + adapter (+ optional gateway lookup) → fields, readiness, proposals.
 *
 * Pure with respect to WordPress storage: it never writes to ARMember, WooCommerce or the database.
 * Every step is reported through the $log callable(step, status, description, ref, error).
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assessor {

	/**
	 * @param array           $facts   Collector facts.
	 * @param Gateway_Adapter $adapter Adapter.
	 * @param array           $opts    lookup(bool), http(Http), secrets(Secrets), log(callable).
	 * @return array Assessment.
	 */
	public function assess( array $facts, Gateway_Adapter $adapter, array $opts = array() ) {
		$log = isset( $opts['log'] ) && is_callable( $opts['log'] ) ? $opts['log'] : function () {};
		$log( 'Member and membership information loaded', Status::STEP_COMPLETED, 'User #' . $facts['user']['id'] . ', plan #' . $facts['plan_id'] . ' (' . $facts['plan_name'] . ').', (string) $facts['user']['id'] );
		$log( 'Existing ARMember payment metadata inspected', Status::STEP_COMPLETED, 'arm_user_plan_' . $facts['plan_id'] . ' ' . ( ! empty( $facts['has_plan_meta'] ) ? 'present' : 'MISSING' ) . '.' );
		$log( 'Existing payment history inspected', Status::STEP_COMPLETED, (int) $facts['pay']['count'] . ' payment log row(s).' );
		$log( 'Gateway identified', Status::STEP_COMPLETED, $adapter->label() . ' (ARMember gateway "' . ( isset( $facts['audit']['gateway'] ) ? $facts['audit']['gateway'] : '' ) . '").', $adapter->id() );

		$registry = $adapter->fields( $facts );
		$found    = $adapter->discover( $facts );
		$log( 'Local information discovery', Status::STEP_COMPLETED, count( $found ) . ' field(s) have at least one stored value.' );

		// ---- Optional read-only gateway lookup -------------------------------------------------
		$gateway = Gateway_Adapter::empty_lookup();
		if ( ! empty( $opts['lookup'] ) && isset( $opts['http'], $opts['secrets'] ) && $adapter->id() !== 'unsupported' ) {
			$log( 'Gateway lookup', Status::STEP_PROCESSING, 'Read-only requests to the gateway.' );
			try {
				$gateway = $adapter->lookup( $facts, $found, $opts['http'], $opts['secrets'] );
			} catch ( \Throwable $e ) {
				$gateway['attempted'] = true;
				$gateway['error']     = Logger::redact( $e->getMessage() );
			}
			foreach ( $gateway['steps'] as $s ) {
				$log( $s['step'], $s['status'], $s['desc'], $s['ref'], $s['error'] );
			}
		} else {
			$log( 'Gateway lookup', Status::STEP_SKIPPED, 'Not requested for this assessment (local data only).' );
		}

		// ---- Field evaluation ------------------------------------------------------------------
		$fields = array();
		foreach ( $registry as $f ) {
			$fields[ $f['key'] ] = $this->evaluate_field( $f, isset( $found[ $f['key'] ] ) ? $found[ $f['key'] ] : array(), $gateway, $facts );
		}
		$log( 'Required fields validated', Status::STEP_COMPLETED, 'Field registry evaluated.' );

		$gw  = $this->gateway_readiness( $adapter, $fields, $gateway, $facts );
		$log( 'Gateway readiness determined', 'gw_verified' === $gw['status'] || 'gw_found' === $gw['status'] ? Status::STEP_COMPLETED : Status::STEP_WARNING, $gw['label'] . ': ' . $gw['explain'] );

		$props = $this->proposals( $adapter, $fields, $gateway, $gw, $facts );
		$arm   = $this->arm_readiness( $adapter, $registry, $fields, $gateway, $gw, $props, $facts );
		$log( 'ARMember renewal readiness determined', Status::ARM_VERIFIED === $arm['status'] || Status::ARM_COMPLETE === $arm['status'] ? Status::STEP_COMPLETED : Status::STEP_WARNING, $arm['label'] . ': ' . $arm['explain'] );

		$overall = $this->overall( $adapter, $gw, $arm, $props );
		$log( 'Assessment completed', Status::STEP_COMPLETED, $overall['label'] . ' – ' . $overall['action'] );

		$missing = array();
		foreach ( $fields as $f ) {
			if ( $f['required'] && in_array( $f['status'], array( Status::F_MISSING, Status::F_INVALID ), true ) ) {
				$missing[] = $f['label'] . ( Status::F_INVALID === $f['status'] ? ' (invalid)' : '' );
			}
		}

		return array(
			'model'      => $adapter->id(),
			'model_label' => $adapter->label(),
			'fields'     => $fields,
			'gateway'    => $gw,
			'arm'        => $arm,
			'overall'    => $overall,
			'proposals'  => $props,
			'missing'    => $missing,
			'candidates' => $gateway['candidates'],
			'lookup'     => array( 'attempted' => $gateway['attempted'], 'error' => $gateway['error'], 'sub_status' => $gateway['sub_status'], 'sub_detail' => $gateway['sub_detail'], 'ownership' => $gateway['ownership'], 'ownership_note' => $gateway['ownership_note'] ),
			'assessed_at' => time(),
		);
	}

	// =========================================================================================
	// Fields
	// =========================================================================================

	private function evaluate_field( array $f, array $entries, array $gateway, array $facts ) {
		$key      = $f['key'];
		$distinct = array();
		$sources  = array();
		foreach ( $entries as $e ) {
			$distinct[ $e['value'] ] = true;
			$sources[]               = $e['source'];
		}
		$values  = array_keys( $distinct );
		$current = 1 === count( $values ) ? $values[0] : '';
		$status  = Status::F_MISSING;
		$note    = '';
		$gwv     = isset( $gateway['objects'][ $key ] ) ? $gateway['objects'][ $key ] : '';
		$actions = array();

		if ( ! $values ) {
			$status = $f['required'] ? Status::F_MISSING : Status::F_OPTIONAL;
		} elseif ( count( $values ) > 1 ) {
			$status = Status::F_CONFLICT;
			$note   = 'Conflicting values: ' . implode( ', ', $values ) . '.';
		} elseif ( '' !== $f['format'] && ! preg_match( $f['format'], $current ) ) {
			$status = Status::F_INVALID;
			$note   = 'Does not match the expected format' . ( '' !== $f['format_hint'] ? ' (' . $f['format_hint'] . ')' : '' ) . '.';
		} elseif ( isset( $gateway['invalid'][ $key ] ) ) {
			$status = Status::F_INVALID;
			$note   = $gateway['invalid'][ $key ];
		} elseif ( $f['verify'] ) {
			if ( '' !== $gwv && $gwv === $current ) {
				$status = Status::F_VERIFIED;
				$note   = 'Confirmed by the gateway.';
			} elseif ( '' !== $gwv && $gwv !== $current ) {
				$status = Status::F_CONFLICT;
				$note   = 'Gateway reports ' . $gwv . '.';
			} else {
				$status = Status::F_FOUND;
				$note   = $gateway['attempted'] ? 'Not confirmed by the gateway.' : 'Not yet verified with the gateway.';
			}
		} else {
			$status = Status::F_VERIFIED; // Local value, format valid.
			$note   = 'Valid local value.';
		}

		// Shared reference across members (duplicate gateway references).
		if ( $values && isset( $facts['shared_refs'][ $current ] ) && 1 === count( $values ) ) {
			$status = Status::F_CONFLICT;
			$note   = trim( $note . ' Also referenced by member(s) #' . implode( ', #', array_map( 'intval', $facts['shared_refs'][ $current ] ) ) . '.' );
		}

		// Missing but the gateway offers a verified value or candidates.
		$proposed = '';
		if ( ! $values && '' !== $gwv ) {
			$proposed = $gwv;
			$note     = 'Retrieved from the gateway; not stored yet.';
		}
		$cands = array();
		foreach ( $gateway['candidates'] as $c ) {
			if ( $c['key'] === $key ) {
				$cands[] = $c;
			}
		}
		if ( ! $values && $cands ) {
			$status = Status::F_AMBIGUOUS;
			$note   = count( $cands ) . ' candidate(s); ownership must be confirmed by an administrator.';
		}

		// Actions (only what is safe/technically supported).
		if ( $f['retrievable'] && ( ! $values || Status::F_FOUND === $status ) ) {
			$actions[] = 'retrieve';
		}
		if ( $f['verify'] && $values && Status::F_VERIFIED !== $status ) {
			$actions[] = 'verify';
		}
		if ( $f['editable'] && ( Status::F_MISSING === $status || Status::F_INVALID === $status || Status::F_CONFLICT === $status || Status::F_AMBIGUOUS === $status ) ) {
			$actions[] = 'enter';
		}
		$actions[] = 'view';

		return array_merge(
			$f,
			array(
				'values'   => $values,
				'current'  => $current,
				'sources'  => array_values( array_unique( $sources ) ),
				'status'   => $status,
				'note'     => $note,
				'proposed' => $proposed,
				'cands'    => $cands,
				'actions'  => $actions,
			)
		);
	}

	// =========================================================================================
	// Gateway readiness
	// =========================================================================================

	private function gateway_readiness( Gateway_Adapter $adapter, array $fields, array $gateway, array $facts ) {
		$mk = function ( $status, $explain ) {
			return array( 'status' => $status, 'label' => Status::label( 'gateway', $status ), 'explain' => $explain );
		};
		if ( 'unsupported' === $adapter->id() ) {
			$g = isset( $facts['audit']['gateway'] ) ? $facts['audit']['gateway'] : '';
			return $mk( Status::GW_UNSUPPORTED, 'No verified integration exists for gateway "' . ( '' === $g ? 'none' : $g ) . '". Handle manually.' );
		}
		$gw_fields = array();
		foreach ( $fields as $f ) {
			if ( in_array( $f['store'], array( 'gateway', 'woocommerce' ), true ) || ( in_array( $f['store'], array( 'armember', 'paylog' ), true ) && $f['verify'] ) ) {
				$gw_fields[ $f['key'] ] = $f;
			}
		}
		foreach ( $gw_fields as $f ) {
			if ( Status::F_INVALID === $f['status'] ) {
				return $mk( Status::GW_INVALID, $f['label'] . ': ' . $f['note'] );
			}
		}
		if ( $gateway['attempted'] && '' !== $gateway['error'] ) {
			return $mk( Status::GW_API_ERROR, $gateway['error'] );
		}
		if ( 'mismatch' === $gateway['ownership'] ) {
			return $mk( Status::GW_INVALID, 'Ownership check failed: ' . $gateway['ownership_note'] );
		}
		foreach ( $gw_fields as $f ) {
			if ( Status::F_CONFLICT === $f['status'] ) {
				return $mk( Status::GW_MULTIPLE, $f['label'] . ': ' . $f['note'] );
			}
		}
		foreach ( $gw_fields as $f ) {
			if ( Status::F_AMBIGUOUS === $f['status'] ) {
				return $mk( count( $f['cands'] ) > 1 ? Status::GW_MULTIPLE : Status::GW_OWNERSHIP, $f['label'] . ': ' . $f['note'] );
			}
		}
		if ( $gateway['attempted'] && 'unverified' === $gateway['ownership'] && ! empty( $gateway['objects'] ) ) {
			return $mk( Status::GW_OWNERSHIP, '' !== $gateway['ownership_note'] ? $gateway['ownership_note'] : 'Ownership of the gateway record is not proven.' );
		}
		$missing_required = array();
		foreach ( $gw_fields as $f ) {
			// A locally missing value that the gateway returned (and ownership allows) counts as obtained.
			if ( $f['required'] && Status::F_MISSING === $f['status'] && ( '' === $f['proposed'] || 'verified' !== $gateway['ownership'] ) ) {
				$missing_required[] = $f;
			}
		}
		if ( $missing_required ) {
			$names      = implode( ', ', wp_list_pluck_compat( $missing_required, 'label' ) );
			$retrieve   = false;
			$editable   = false;
			foreach ( $missing_required as $f ) {
				$retrieve = $retrieve || ! empty( $f['proposed'] ) || ( $f['retrievable'] && ! $gateway['attempted'] );
				$editable = $editable || $f['editable'];
			}
			if ( $retrieve && ! $gateway['attempted'] ) {
				return $mk( Status::GW_LOOKUP, 'Missing: ' . $names . '. Run "Retrieve Gateway Information" to look it up.' );
			}
			if ( $editable ) {
				return $mk( Status::GW_MANUAL, 'Missing: ' . $names . '. Not obtainable automatically – an administrator must supply it.' );
			}
			return $mk( Status::GW_MISSING, 'Missing: ' . $names . '. It cannot be entered here (created by the gateway plugin itself).' );
		}
		if ( ! $gateway['attempted'] ) {
			return $mk( Status::GW_FOUND, 'Required gateway information exists locally but was not verified with the gateway.' );
		}
		if ( 'inactive' === $gateway['sub_status'] ) {
			return $mk( Status::GW_INACTIVE, $gateway['sub_detail'] );
		}
		if ( 'verified' !== $gateway['ownership'] ) {
			return $mk( Status::GW_OWNERSHIP, '' !== $gateway['ownership_note'] ? $gateway['ownership_note'] : 'Ownership of the gateway record is not proven.' );
		}
		foreach ( $gw_fields as $f ) {
			if ( $f['required'] && Status::F_VERIFIED !== $f['status'] && '' === $f['proposed'] ) {
				return $mk( Status::GW_FOUND, $f['label'] . ' is present but not confirmed by the gateway.' );
			}
		}
		return $mk( Status::GW_VERIFIED, 'Gateway records exist, belong to this member and are active.' );
	}

	// =========================================================================================
	// Mapping proposals (what Sync would write to ARMember)
	// =========================================================================================

	private function proposals( Gateway_Adapter $adapter, array $fields, array $gateway, array $gw, array $facts ) {
		$out = array();
		if ( ! $adapter->supports_sync() || Status::GW_VERIFIED !== $gw['status'] ) {
			return $out;
		}
		foreach ( $fields as $f ) {
			if ( empty( $f['arm_paths'] ) || ! isset( $gateway['objects'][ $f['key'] ] ) ) {
				continue;
			}
			$verified = $gateway['objects'][ $f['key'] ];
			foreach ( $f['arm_paths'] as $path ) {
				$current = Gateway_Adapter::raw_value( $facts, $path );
				if ( $current === $verified ) {
					continue;
				}
				if ( '' !== $current ) {
					continue; // Never overwrite; a differing value is reported as a conflict field instead.
				}
				$out[] = array(
					'field'    => $f['key'],
					'label'    => $f['label'],
					'path'     => $path,
					'location' => 'usermeta arm_user_plan_' . $facts['plan_id'] . ' → ' . implode( ' → ', $path ),
					'current'  => $current,
					'proposed' => $verified,
					'reason'   => 'ARMember resolves webhook renewals by searching the member\'s arm_user_plan_{id} meta (and payment log token) for this id; it is not stored there yet.',
				);
			}
		}
		return $out;
	}

	// =========================================================================================
	// ARMember renewal readiness
	// =========================================================================================

	private function arm_readiness( Gateway_Adapter $adapter, array $registry, array $fields, array $gateway, array $gw, array $props, array $facts ) {
		$mk = function ( $status, $explain, array $checks = array() ) {
			return array( 'status' => $status, 'label' => Status::label( 'arm', $status ), 'explain' => $explain, 'checks' => $checks );
		};
		$exp    = $adapter->arm_expectations( $facts );
		$checks = array();
		if ( 'unsupported' === $adapter->id() ) {
			return $mk( Status::ARM_MANUAL, 'Unsupported payment model.' );
		}
		if ( 'recurring' !== $facts['plan_kind'] && empty( $facts['audit']['is_recurring'] ) ) {
			return $mk( Status::ARM_MANUAL, 'The plan is not recurring (' . $facts['plan_kind'] . '); no recurring payment configuration is expected.' );
		}
		$add = function ( $label, $ok, $detail ) use ( &$checks ) {
			$checks[] = array( 'label' => $label, 'status' => $ok ? 'ok' : 'fail', 'detail' => $detail );
			return $ok;
		};
		$add( 'ARMember plan meta exists', ! empty( $facts['has_plan_meta'] ), 'arm_user_plan_' . $facts['plan_id'] );
		$gw_ok = isset( $fields['arm_gateway'] ) && Status::F_VERIFIED === $fields['arm_gateway']['status'];
		$add( 'Gateway identifier', $gw_ok, $gw_ok ? 'arm_user_gateway is correct for this model.' : 'Expected ' . $exp['label'] . '; found "' . ( isset( $facts['meta']['gateway'] ) ? $facts['meta']['gateway'] : '' ) . '". Not changed automatically.' );
		$mode_ok = isset( $fields['arm_payment_mode'] ) && Status::F_VERIFIED === $fields['arm_payment_mode']['status'];
		$add( 'Payment mode', $mode_ok, $mode_ok ? 'arm_payment_mode is correct for this model.' : 'Expected ' . $exp['mode'] . '; found "' . ( isset( $facts['meta']['mode'] ) ? $facts['meta']['mode'] : '' ) . '". Never changed to satisfy this check.' );
		$nd_ok = isset( $fields['arm_next_due'] ) && Status::F_VERIFIED === $fields['arm_next_due']['status'];
		$add( 'Next payment due date', $nd_ok, $nd_ok ? 'Present.' : 'arm_next_due_payment is empty/invalid.' );
		$add( 'Payment history', ! empty( $facts['pay']['count'] ), (int) $facts['pay']['count'] . ' payment log row(s).' );
		if ( ! empty( $facts['meta']['cancelled'] ) ) {
			$add( 'Plan not cancelled in ARMember', false, 'arm_cencelled_plan is set.' );
		}

		$block = function ( $status, $explain ) use ( $mk, &$checks ) {
			return $mk( $status, $explain, $checks );
		};
		if ( ! $gw_ok || ! $mode_ok ) {
			return $block( Status::ARM_INCORRECT, 'Gateway identifier or payment mode does not match the payment model. This plugin never changes them automatically – manual review.' );
		}
		if ( empty( $facts['has_plan_meta'] ) || ! $nd_ok ) {
			return $block( Status::ARM_MISSING, 'Required ARMember data is missing (plan meta or next due date).' );
		}
		if ( $props ) {
			return $block( Status::ARM_MAPPING, count( $props ) . ' ARMember reference(s) must be stored before renewals can be matched.' );
		}
		if ( ! $adapter->supports_sync() ) {
			$r = $adapter->renewal_check( $facts, $gateway, $fields );
			foreach ( $r['checks'] as $c ) {
				$checks[] = $c;
			}
			return $block( 'verified' === $r['status'] ? Status::ARM_VERIFIED : Status::ARM_NOT_VERIFIED, $r['detail'] );
		}
		if ( Status::GW_VERIFIED !== $gw['status'] ) {
			return $block( Status::ARM_COMPLETE, 'ARMember data looks complete, but the gateway side is not verified, so renewal readiness cannot be concluded.' );
		}
		$r = $adapter->renewal_check( $facts, $gateway, $fields );
		foreach ( $r['checks'] as $c ) {
			$checks[] = $c;
		}
		return $block( 'verified' === $r['status'] ? Status::ARM_VERIFIED : ( 'manual' === $r['status'] ? Status::ARM_MANUAL : Status::ARM_NOT_VERIFIED ), $r['detail'] );
	}

	// =========================================================================================
	// Overall
	// =========================================================================================

	private function overall( Gateway_Adapter $adapter, array $gw, array $arm, array $props ) {
		$mk = function ( $status, $action ) {
			return array( 'status' => $status, 'label' => Status::label( 'overall', $status ), 'action' => $action );
		};
		switch ( $gw['status'] ) {
			case Status::GW_UNSUPPORTED:
				return $mk( Status::OV_REVIEW, 'Unsupported payment model – review and handle manually.' );
			case Status::GW_API_ERROR:
				return $mk( Status::OV_GW_FAILED, 'Gateway request failed: ' . $gw['explain'] . ' Retry later or check credentials.' );
			case Status::GW_INVALID:
			case Status::GW_INACTIVE:
				return $mk( Status::OV_GW_FAILED, $gw['explain'] );
			case Status::GW_MULTIPLE:
			case Status::GW_OWNERSHIP:
				return $mk( Status::OV_REVIEW, $gw['explain'] );
			case Status::GW_LOOKUP:
				return $mk( Status::OV_LOOKUP, $gw['explain'] );
			case Status::GW_MANUAL:
				return $mk( Status::OV_MANUAL, $gw['explain'] );
			case Status::GW_MISSING:
				return $mk( Status::OV_MISSING, $gw['explain'] );
			case Status::GW_FOUND:
				return $mk( Status::OV_LOOKUP, 'Verify the stored gateway information with the gateway (Retrieve Gateway Information).' );
		}
		// Gateway verified.
		switch ( $arm['status'] ) {
			case Status::ARM_INCORRECT:
			case Status::ARM_MANUAL:
				return $mk( Status::OV_REVIEW, $arm['explain'] );
			case Status::ARM_MISSING:
				return $mk( Status::OV_MISSING, $arm['explain'] );
			case Status::ARM_MAPPING:
				return $mk( Status::OV_READY, 'Safe to queue synchronisation: ' . count( $props ) . ' ARMember reference(s) will be stored.' );
			case Status::ARM_VERIFIED:
				return $mk( Status::OV_RENEWAL_OK, 'Nothing to do – ARMember can process future renewals for this member.' );
			default:
				return $mk( Status::OV_LINKED_UNVER, $arm['explain'] );
		}
	}
}

/** wp_list_pluck without requiring WordPress (keeps the assessor unit-testable). */
function wp_list_pluck_compat( array $rows, $key ) {
	$out = array();
	foreach ( $rows as $r ) {
		$out[] = $r[ $key ];
	}
	return $out;
}
