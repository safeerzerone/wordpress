<?php
/**
 * The ONLY place that writes ARMember data. Uses the same supported mechanism ARMember itself uses
 * (get_user_meta → modify array → update_user_meta on arm_user_plan_{id}); never raw SQL.
 *
 * Guarantees: whitelisted paths only, id format re-validated, existing non-empty values are never
 * overwritten, all other keys of the meta array are preserved, the saved result is re-read and verified,
 * gateway / payment mode / next-due / counts / payment log are never touched.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Arm_Writer {

	/** Paths Sync may write inside arm_user_plan_{id}. */
	const ALLOWED = array(
		'arm_subscr_id'                  => '/^(?:sub_(?:sched_)?[A-Za-z0-9]+|I-[A-Z0-9]{8,})$/',
		'arm_stripe.customer_id'         => '/^cus_[A-Za-z0-9]+$/',
		'arm_stripe.transaction_id'      => '/^sub_(?:sched_)?[A-Za-z0-9]+$/',
		'arm_paypal.arm_subscr_id'       => '/^I-[A-Z0-9]{8,}$/',
	);

	/**
	 * @param int      $user_id   Member.
	 * @param int      $plan_id   Plan.
	 * @param array    $proposals Assessor proposals (path, proposed, field).
	 * @param Logger   $log       Logger (audit + timeline).
	 * @return array { applied: array[], skipped: array[], verified: bool, error: string }
	 */
	public function apply( $user_id, $plan_id, array $proposals, Logger $log ) {
		$out = array( 'applied' => array(), 'skipped' => array(), 'verified' => false, 'error' => '' );
		$key = 'arm_user_plan_' . absint( $plan_id );
		wp_cache_delete( absint( $user_id ), 'user_meta' );
		$meta = get_user_meta( $user_id, $key, true );
		if ( ! is_array( $meta ) || ! $meta ) {
			$out['error'] = 'ARMember plan meta ' . $key . ' is missing; nothing was written.';
			$log->write( 'ARMember mapping update', Status::STEP_FAILED, $out['error'] );
			return $out;
		}
		$new = $meta;
		foreach ( $proposals as $p ) {
			$dot = implode( '.', $p['path'] );
			if ( ! isset( self::ALLOWED[ $dot ] ) || ! preg_match( self::ALLOWED[ $dot ], (string) $p['proposed'] ) ) {
				$out['skipped'][] = array( 'path' => $dot, 'reason' => 'Path or value is not whitelisted.' );
				$log->write( 'ARMember mapping update', Status::STEP_SKIPPED, 'Refused to write ' . $dot . ': not whitelisted / invalid value.', $dot );
				continue;
			}
			$cur = $this->read( $new, $p['path'] );
			if ( '' !== $cur && $cur !== (string) $p['proposed'] ) {
				$out['skipped'][] = array( 'path' => $dot, 'reason' => 'Existing different value is never overwritten.' );
				$log->write( 'ARMember mapping update', Status::STEP_SKIPPED, $dot . ' already holds a different value; left unchanged.', $dot );
				continue;
			}
			if ( $cur === (string) $p['proposed'] ) {
				continue;
			}
			$this->set( $new, $p['path'], (string) $p['proposed'] );
			$out['applied'][] = array( 'path' => $dot, 'old' => $cur, 'new' => (string) $p['proposed'], 'field' => $p['field'] );
		}
		if ( ! $out['applied'] ) {
			$out['verified'] = true;
			return $out;
		}
		update_user_meta( $user_id, $key, $new );

		// Verify the saved result by re-reading from the database.
		wp_cache_delete( absint( $user_id ), 'user_meta' );
		$saved = get_user_meta( $user_id, $key, true );
		$ok    = is_array( $saved );
		foreach ( $out['applied'] as $a ) {
			$ok = $ok && $this->read( $saved, explode( '.', $a['path'] ) ) === $a['new'];
			$log->change( $a['field'] . ' @ ' . $key . ' → ' . $a['path'], $a['old'], $a['new'], 'sync_write' );
		}
		// Everything we did not intend to change must be identical.
		if ( $ok ) {
			foreach ( $meta as $k => $v ) {
				if ( isset( self::ALLOWED[ $k ] ) || 'arm_stripe' === $k || 'arm_paypal' === $k ) {
					continue;
				}
				if ( ! array_key_exists( $k, $saved ) || $saved[ $k ] !== $v ) {
					$ok = false;
				}
			}
		}
		$out['verified'] = $ok;
		$log->write( 'Saved mapping verified', $ok ? Status::STEP_COMPLETED : Status::STEP_FAILED, $ok ? count( $out['applied'] ) . ' value(s) re-read from the database and confirmed; other plan meta unchanged.' : 'Re-read after saving did not match what was written.' );
		return $out;
	}

	private function read( array $arr, array $path ) {
		$cur = $arr;
		foreach ( $path as $p ) {
			if ( ! is_array( $cur ) || ! isset( $cur[ $p ] ) ) {
				return '';
			}
			$cur = $cur[ $p ];
		}
		return is_scalar( $cur ) ? trim( (string) $cur ) : '';
	}

	private function set( array &$arr, array $path, $value ) {
		$ref = &$arr;
		foreach ( $path as $i => $p ) {
			if ( $i === count( $path ) - 1 ) {
				$ref[ $p ] = $value;
			} else {
				if ( ! isset( $ref[ $p ] ) || ! is_array( $ref[ $p ] ) ) {
					$ref[ $p ] = array();
				}
				$ref = &$ref[ $p ];
			}
		}
	}
}
