<?php
/**
 * Status vocabularies (gateway readiness, ARMember readiness, overall, step, sync, job).
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Status {

	// Gateway readiness.
	const GW_FOUND       = 'gw_found';
	const GW_VERIFIED    = 'gw_verified';
	const GW_LOOKUP      = 'gw_lookup_required';
	const GW_MISSING     = 'gw_missing';
	const GW_MANUAL      = 'gw_manual_input';
	const GW_INVALID     = 'gw_invalid';
	const GW_MULTIPLE    = 'gw_multiple';
	const GW_OWNERSHIP   = 'gw_ownership_required';
	const GW_INACTIVE    = 'gw_inactive';
	const GW_API_ERROR   = 'gw_api_error';
	const GW_UNSUPPORTED = 'gw_unsupported';
	const GW_NA          = 'gw_not_assessed';

	// ARMember renewal readiness.
	const ARM_COMPLETE       = 'arm_complete';
	const ARM_MISSING        = 'arm_missing';
	const ARM_MAPPING        = 'arm_mapping_required';
	const ARM_INCORRECT      = 'arm_incorrect_config';
	const ARM_NOT_VERIFIED   = 'arm_renewal_not_verified';
	const ARM_VERIFIED       = 'arm_renewal_verified';
	const ARM_MANUAL         = 'arm_manual_review';
	const ARM_NA             = 'arm_not_assessed';

	// Overall.
	const OV_NOT_ASSESSED   = 'not_assessed';
	const OV_IN_PROGRESS    = 'assessment_in_progress';
	const OV_MISSING        = 'missing_information';
	const OV_LOOKUP         = 'gateway_lookup_required';
	const OV_MANUAL         = 'manual_input_required';
	const OV_REVIEW         = 'needs_review';
	const OV_GW_FAILED      = 'gateway_verification_failed';
	const OV_MAPPING        = 'armember_mapping_required';
	const OV_READY          = 'ready_for_sync';
	const OV_QUEUED         = 'sync_queued';
	const OV_SYNCING        = 'sync_in_progress';
	const OV_SYNCED         = 'sync_completed';
	const OV_PARTIAL        = 'partially_synced';
	const OV_SYNC_FAILED    = 'sync_failed';
	const OV_LINKED_UNVER   = 'gateway_linked_renewal_not_verified';
	const OV_RENEWAL_OK     = 'renewal_readiness_verified';

	// Sync state (stored separately from the assessment).
	const SYNC_NONE    = 'never';
	const SYNC_QUEUED  = 'queued';
	const SYNC_RUNNING = 'in_progress';
	const SYNC_DONE    = 'completed';
	const SYNC_PARTIAL = 'partial';
	const SYNC_FAILED  = 'failed';
	/** Mapping written and verified, but renewal processing could not be verified (never reported as completed). */
	const SYNC_LINKED  = 'linked_unverified';

	// Log step statuses.
	const STEP_PENDING     = 'pending';
	const STEP_PROCESSING  = 'processing';
	const STEP_COMPLETED   = 'completed';
	const STEP_SKIPPED     = 'skipped';
	const STEP_WARNING     = 'warning';
	const STEP_FAILED      = 'failed';
	const STEP_REQUIRES    = 'requires_input';

	// Field statuses.
	const F_VERIFIED  = 'verified';
	const F_FOUND     = 'found';
	const F_MISSING   = 'missing';
	const F_INVALID   = 'invalid';
	const F_CONFLICT  = 'conflict';
	const F_AMBIGUOUS = 'ambiguous';
	const F_OPTIONAL  = 'optional_empty';
	const F_UNKNOWN   = 'unverified';

	public static function gateway_labels() {
		return array(
			self::GW_FOUND       => 'Gateway Information Found',
			self::GW_VERIFIED    => 'Gateway Information Verified',
			self::GW_LOOKUP      => 'Gateway Lookup Required',
			self::GW_MISSING     => 'Missing Gateway Information',
			self::GW_MANUAL      => 'Manual Input Required',
			self::GW_INVALID     => 'Invalid Gateway Information',
			self::GW_MULTIPLE    => 'Multiple Matching Records',
			self::GW_OWNERSHIP   => 'Ownership Verification Required',
			self::GW_INACTIVE    => 'Gateway Subscription Inactive',
			self::GW_API_ERROR   => 'Gateway API Error',
			self::GW_UNSUPPORTED => 'Unsupported Payment Model',
			self::GW_NA          => 'Not Assessed',
		);
	}

	public static function arm_labels() {
		return array(
			self::ARM_COMPLETE     => 'ARMember Data Complete',
			self::ARM_MISSING      => 'ARMember Data Missing',
			self::ARM_MAPPING      => 'ARMember Mapping Required',
			self::ARM_INCORRECT    => 'Incorrect Payment Configuration',
			self::ARM_NOT_VERIFIED => 'Renewal Processing Not Verified',
			self::ARM_VERIFIED     => 'Renewal Processing Verified',
			self::ARM_MANUAL       => 'Manual Review Required',
			self::ARM_NA           => 'Not Assessed',
		);
	}

	public static function overall_labels() {
		return array(
			self::OV_NOT_ASSESSED => 'Not Assessed',
			self::OV_IN_PROGRESS  => 'Assessment in Progress',
			self::OV_MISSING      => 'Missing Information',
			self::OV_LOOKUP       => 'Gateway Lookup Required',
			self::OV_MANUAL       => 'Manual Input Required',
			self::OV_REVIEW       => 'Needs Review',
			self::OV_GW_FAILED    => 'Gateway Verification Failed',
			self::OV_MAPPING      => 'ARMember Mapping Required',
			self::OV_READY        => 'Ready for Sync',
			self::OV_QUEUED       => 'Sync Queued',
			self::OV_SYNCING      => 'Sync in Progress',
			self::OV_SYNCED       => 'Sync Completed',
			self::OV_PARTIAL      => 'Partially Synced',
			self::OV_SYNC_FAILED  => 'Sync Failed',
			self::OV_LINKED_UNVER => 'Gateway Linked – ARMember Renewal Not Verified',
			self::OV_RENEWAL_OK   => 'Renewal Readiness Verified',
		);
	}

	public static function label( $group, $key ) {
		$map = 'gateway' === $group ? self::gateway_labels() : ( 'arm' === $group ? self::arm_labels() : self::overall_labels() );
		return isset( $map[ $key ] ) ? $map[ $key ] : (string) $key;
	}

	/**
	 * Overall status shown to the admin: a live sync state wins over the stored assessment.
	 */
	public static function effective_overall( $overall, $sync ) {
		switch ( $sync ) {
			case self::SYNC_QUEUED:
				return self::OV_QUEUED;
			case self::SYNC_RUNNING:
				return self::OV_SYNCING;
			case self::SYNC_FAILED:
				return self::OV_SYNC_FAILED;
			case self::SYNC_PARTIAL:
				return self::OV_PARTIAL;
			case self::SYNC_LINKED:
				return self::OV_LINKED_UNVER;
			case self::SYNC_DONE:
				return self::OV_SYNCED;
		}
		return $overall;
	}

	/** Overall statuses from which a sync may be queued. */
	public static function syncable( $overall ) {
		return self::OV_READY === $overall;
	}
}
