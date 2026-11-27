<?php
/**
 * Pure classification logic. No database or WordPress calls, so it can be unit-tested.
 *
 * Input context (array):
 *  plan_id, plan_kind (recurring|one_time|lifetime|free|unknown), has_plan_meta (bool),
 *  meta  => gateway, mode, cycle, completed, next_due, subscr_id, stripe (array), cancelled
 *  pay   => count, latest (row|null), sub_refs[], order_refs[], gateways[], modes[]
 *
 * NOTE: "Subscription Linked" only means a reference is *stored in ARMember*. It does not
 * prove that the external Stripe subscription exists or is active (Phase 2).
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Subscription_Audit {

	const LINKED        = 'subscription_linked';
	const ID_MISSING    = 'subscription_id_missing';
	const NO_RECURRING  = 'no_recurring_information';
	const NO_PAYMENTS   = 'payment_history_missing';
	const LAST_FAILED   = 'last_payment_failed';
	const INVESTIGATE   = 'needs_investigation';

	const RECURRING_MODES = array( 'auto_debit_subscription', 'manual_subscription' );

	/**
	 * @return array<string,string> status => label
	 */
	public static function status_labels() {
		return array(
			self::LINKED       => 'Subscription Linked',
			self::ID_MISSING   => 'Subscription ID Missing',
			self::NO_RECURRING => 'No Recurring Information',
			self::NO_PAYMENTS  => 'Payment History Missing',
			self::LAST_FAILED  => 'Last Payment Failed',
			self::INVESTIGATE  => 'Needs Investigation',
		);
	}

	/** Normalise a raw gateway value to stripe | woocommerce | paypal | gocardless | other | '' (empty). */
	public static function gateway_key( $gateway ) {
		$g = strtolower( trim( (string) $gateway ) );
		if ( '' === $g ) {
			return '';
		}
		$flat = preg_replace( '/[^a-z0-9]/', '', $g );
		if ( 'stripe' === $g || 'woocommerce' === $g ) {
			return $g;
		}
		// paypal, paypal_express, "PayPal Standard", woocommerce_paypal, ppcp-gateway (WC PayPal Payments), ppec_paypal.
		if ( false !== strpos( $flat, 'paypal' ) || 0 === strpos( $flat, 'ppcp' ) || 0 === strpos( $flat, 'ppec' ) ) {
			return 'paypal';
		}
		if ( false !== strpos( $flat, 'gocardless' ) ) {
			return 'gocardless';
		}
		return 'other';
	}

	public static function gateway_label( $gateway ) {
		$labels = array(
			''           => 'Not configured',
			'stripe'     => 'Stripe',
			'woocommerce' => 'WooCommerce',
			'paypal'     => 'PayPal',
			'gocardless' => 'GoCardless',
			'other'      => 'Other',
		);
		return $labels[ self::gateway_key( $gateway ) ];
	}

	public static function mode_label( $mode ) {
		$m = strtolower( trim( (string) $mode ) );
		if ( '' === $m ) {
			return '—';
		}
		if ( 'auto_debit_subscription' === $m ) {
			return 'Auto Debit';
		}
		if ( 'manual_subscription' === $m ) {
			return 'Manual Subscription';
		}
		return 'Other';
	}

	/**
	 * Classify one member + plan.
	 *
	 * @param array $ctx Context described in the class docblock.
	 * @return array status, label, reasons[], missing[], inconsistencies[], notes[], reference, reference_source, gateway, mode, is_recurring, model
	 */
	public function analyze( array $ctx ) {
		$meta    = isset( $ctx['meta'] ) && is_array( $ctx['meta'] ) ? $ctx['meta'] : array();
		$pay     = isset( $ctx['pay'] ) && is_array( $ctx['pay'] ) ? $ctx['pay'] : array();
		$kind    = isset( $ctx['plan_kind'] ) ? $ctx['plan_kind'] : 'unknown';
		$has_met = ! empty( $ctx['has_plan_meta'] );

		$gateway = strtolower( trim( (string) ( isset( $meta['gateway'] ) ? $meta['gateway'] : '' ) ) );
		$mode    = strtolower( trim( (string) ( isset( $meta['mode'] ) ? $meta['mode'] : '' ) ) );

		$reasons = array();
		$missing = array();
		$incons  = array();
		$notes   = array();

		// Fall back to the payment log when the plan meta carries no gateway / mode.
		$log_gateways = isset( $pay['gateways'] ) ? $pay['gateways'] : array();
		$log_modes    = isset( $pay['modes'] ) ? $pay['modes'] : array();
		// Imported members: ARMember gateway is empty / "manual" while the real method lives in profile fields.
		$profile_gw   = isset( $meta['profile_gateway'] ) ? (string) $meta['profile_gateway'] : '';
		$from_profile = false;
		if ( '' !== $profile_gw && in_array( $gateway, array( '', 'manual' ), true ) ) {
			$notes[]      = 'Gateway taken from the member profile payment-method field (ARMember plan metadata has "' . ( '' === $gateway ? 'empty' : $gateway ) . '").';
			$gateway      = $profile_gw;
			$from_profile = true;
		}
		if ( '' === $gateway && 1 === count( $log_gateways ) ) {
			$gateway = $log_gateways[0];
			$notes[] = 'Gateway taken from payment log (not present in plan metadata).';
		}
		if ( '' === $mode && 1 === count( $log_modes ) && in_array( $log_modes[0], self::RECURRING_MODES, true ) ) {
			$mode    = $log_modes[0];
			$notes[] = 'Payment mode taken from payment log (not present in plan metadata).';
		}

		$is_recurring = ( 'recurring' === $kind ) || in_array( $mode, self::RECURRING_MODES, true );

		// Subscription reference lookup.
		list( $reference, $reference_source, $multi ) = $this->find_reference( $meta, $pay, $gateway );

		$result = array(
			'gateway'          => $gateway,
			'mode'             => $mode,
			'is_recurring'     => $is_recurring,
			'reference'        => $reference,
			'reference_source' => $reference_source,
			'model'            => $this->detect_model( $gateway, $mode, $log_modes ),
		);

		$count       = isset( $pay['count'] ) ? (int) $pay['count'] : 0;
		$latest      = isset( $pay['latest'] ) && is_array( $pay['latest'] ) ? $pay['latest'] : null;
		$latest_stat = $latest ? Payment_History::normalize_status( isset( $latest['arm_transaction_status'] ) ? $latest['arm_transaction_status'] : '' ) : Payment_History::STATUS_NONE;
		$result['latest_status'] = $latest_stat;

		if ( ! empty( $meta['cancelled'] ) ) {
			$notes[] = 'ARMember marks this plan as cancelled (arm_cencelled_plan is set).';
		}

		// ---- Not recurring -------------------------------------------------------------
		if ( ! $is_recurring ) {
			if ( ! $has_met && 'unknown' === $kind ) {
				$missing[] = 'Plan definition and arm_user_plan_{id} metadata';
				return $this->finish( $result, self::INVESTIGATE, array( 'Plan not found in ARMember and no plan metadata exists for this member.' ), $missing, $incons, $notes );
			}
			if ( 'unknown' === $kind ) {
				$incons[] = 'Plan definition not found (deleted plan?) and metadata has no recurring payment mode.';
				return $this->finish( $result, self::INVESTIGATE, array( 'Cannot determine whether this plan is recurring.' ), $missing, $incons, $notes );
			}
			$kinds = array( 'one_time' => 'one-time (finite)', 'lifetime' => 'lifetime', 'free' => 'free' );
			$reasons[] = 'Plan is ' . ( isset( $kinds[ $kind ] ) ? $kinds[ $kind ] : $kind ) . '; no recurring subscription is expected.';
			if ( Payment_History::STATUS_FAILED === $latest_stat ) {
				$notes[] = 'Latest payment record for this plan has a failed status (informational; plan is not recurring).';
			}
			return $this->finish( $result, self::NO_RECURRING, $reasons, $missing, $incons, $notes );
		}

		// ---- Recurring: consistency checks ---------------------------------------------
		if ( 'recurring' === $kind && ! in_array( $mode, self::RECURRING_MODES, true ) ) {
			$incons[] = 'Plan is defined as recurring but metadata payment mode is "' . ( '' === $mode ? 'empty' : $mode ) . '".';
		}
		if ( in_array( $kind, array( 'one_time', 'lifetime', 'free' ), true ) ) {
			$incons[] = 'Plan definition is ' . $kind . ' but metadata has a recurring payment mode (' . $mode . ').';
		}
		if ( '' === $gateway ) {
			$missing[] = 'Gateway';
		} elseif ( 'other' === self::gateway_key( $gateway ) ) {
			$incons[] = 'Unrecognised gateway "' . $gateway . '".';
		}
		if ( '' === $mode ) {
			$missing[] = 'Payment mode';
		} elseif ( ! in_array( $mode, self::RECURRING_MODES, true ) ) {
			$incons[] = 'Unrecognised payment mode "' . $mode . '".';
		}
		if ( 'stripe' === $gateway && 'manual_subscription' === $mode ) {
			$incons[] = 'Gateway is Stripe but payment mode is manual_subscription.';
		}
		if ( 'woocommerce' === $gateway && 'auto_debit_subscription' === $mode ) {
			$incons[] = 'Gateway is WooCommerce but payment mode is auto_debit_subscription.';
		}
		if ( 'woocommerce' === $gateway && 'manual_subscription' === $mode && in_array( 'auto_debit_subscription', $log_modes, true ) ) {
			$notes[] = 'Pattern: WooCommerce/manual_subscription metadata with auto_debit_subscription payment log (likely direct Stripe subscription, Model C). Verify in Phase 2.';
		}
		if ( count( $log_gateways ) > 1 ) {
			$incons[] = 'Payment log uses several gateways: ' . implode( ', ', $log_gateways ) . '.';
		}
		if ( ! $from_profile && '' !== $gateway && $log_gateways && ! in_array( $gateway, $log_gateways, true ) && ! ( 'woocommerce' === $gateway && in_array( 'stripe', $log_gateways, true ) ) ) {
			$incons[] = 'Metadata gateway "' . $gateway . '" differs from payment log gateway(s): ' . implode( ', ', $log_gateways ) . '.';
		}
		if ( $multi ) {
			$incons[] = 'Multiple different subscription references found: ' . implode( ', ', $multi ) . '.';
		}
		if ( Payment_History::STATUS_UNKNOWN === $latest_stat && $latest ) {
			$incons[] = 'Latest payment has an unrecognised status "' . ( isset( $latest['arm_transaction_status'] ) ? $latest['arm_transaction_status'] : '' ) . '".';
		}
		if ( ! $has_met ) {
			$missing[] = 'arm_user_plan_' . (int) $ctx['plan_id'] . ' user meta';
		}

		// ---- Recurring: priority order -------------------------------------------------
		// 1. Failed latest payment (a fact, even when the reference exists).
		if ( Payment_History::STATUS_FAILED === $latest_stat ) {
			$reasons[] = 'The most recent payment record has a failed status.';
			if ( $reference ) {
				$notes[] = 'A subscription reference is stored, but the latest payment failed.';
			}
			return $this->finish( $result, self::LAST_FAILED, $reasons, $missing, $incons, $notes );
		}
		// 2. Conflicts or missing core metadata.
		if ( $incons || in_array( 'Gateway', $missing, true ) || in_array( 'Payment mode', $missing, true ) || ! $has_met ) {
			$reasons[] = 'Metadata or payment records are incomplete or inconsistent and cannot be classified safely.';
			return $this->finish( $result, self::INVESTIGATE, $reasons, $missing, $incons, $notes );
		}
		// 3. No payment log for this member + plan.
		if ( 0 === $count ) {
			$reasons[] = 'Recurring configuration exists but no payment log record exists for this member and plan.';
			return $this->finish( $result, self::NO_PAYMENTS, $reasons, $missing, $incons, $notes );
		}
		// 4. Missing subscription reference.
		if ( ! $reference ) {
			$missing[] = 'Subscription reference';
			$reasons[] = 'Recurring configuration and gateway are identifiable, but no subscription reference was found in ARMember metadata or payment log.';
			return $this->finish( $result, self::ID_MISSING, $reasons, $missing, $incons, $notes );
		}
		// 5. Linked.
		$reasons[] = 'A subscription reference is stored in ARMember (' . $reference_source . '). This does NOT confirm the external subscription is active.';
		if ( Payment_History::STATUS_PENDING === $latest_stat || Payment_History::STATUS_CANCELLED === $latest_stat ) {
			$notes[] = 'Latest payment status is ' . $latest_stat . '.';
		}
		return $this->finish( $result, self::LINKED, $reasons, $missing, $incons, $notes );
	}

	/**
	 * @return array [reference, source, conflicting references or empty array]
	 */
	private function find_reference( array $meta, array $pay, $gateway ) {
		$candidates = array();
		$subscr     = isset( $meta['subscr_id'] ) ? trim( (string) $meta['subscr_id'] ) : '';
		if ( '' !== $subscr ) {
			$candidates[ $subscr ] = 'plan metadata arm_subscr_id';
		}
		$stripe = isset( $meta['stripe'] ) && is_array( $meta['stripe'] ) ? $meta['stripe'] : array();
		if ( ! empty( $stripe['transaction_id'] ) && is_scalar( $stripe['transaction_id'] ) ) {
			$id = trim( (string) $stripe['transaction_id'] );
			if ( '' !== $id && ! isset( $candidates[ $id ] ) ) {
				$candidates[ $id ] = 'plan metadata arm_stripe.transaction_id';
			}
		}
		if ( ! empty( $pay['sub_refs'] ) ) {
			foreach ( $pay['sub_refs'] as $ref ) {
				if ( ! isset( $candidates[ $ref ] ) ) {
					$candidates[ $ref ] = 'payment log token/transaction ID';
				}
			}
		}
		// Only genuine subscription-looking ids can conflict with each other.
		$real = array();
		foreach ( $candidates as $ref => $src ) {
			if ( Payment_History::is_subscription_reference( $ref ) ) {
				$real[] = $ref;
			}
		}
		$multi = count( $real ) > 1 ? $real : array();

		if ( $candidates ) {
			// Prefer a real subscription id as the displayed reference.
			foreach ( $candidates as $ref => $src ) {
				if ( Payment_History::is_subscription_reference( $ref ) ) {
					return array( $ref, $src, $multi );
				}
			}
			reset( $candidates );
			return array( (string) key( $candidates ), current( $candidates ), $multi );
		}
		// WooCommerce renews via orders; an empty token is normal, so accept the order/transaction id.
		if ( 'woocommerce' === $gateway && ! empty( $pay['order_refs'] ) ) {
			return array( $pay['order_refs'][0], 'payment log transaction ID (WooCommerce order reference)', array() );
		}
		return array( '', '', array() );
	}

	private function detect_model( $gateway, $mode, array $log_modes ) {
		if ( 'stripe' === $gateway && 'auto_debit_subscription' === $mode ) {
			return 'A: native ARMember Stripe';
		}
		if ( 'woocommerce' === $gateway && 'manual_subscription' === $mode ) {
			return in_array( 'auto_debit_subscription', $log_modes, true ) ? 'C: direct Stripe with WooCommerce-style metadata' : 'B: WooCommerce gateway';
		}
		return '';
	}

	private function finish( array $result, $status, array $reasons, array $missing, array $incons, array $notes ) {
		$labels                  = self::status_labels();
		$result['status']        = $status;
		$result['label']         = $labels[ $status ];
		$result['reasons']       = $reasons;
		$result['missing']       = array_values( array_unique( $missing ) );
		$result['inconsistencies'] = $incons;
		$result['notes']         = $notes;
		return $result;
	}
}
