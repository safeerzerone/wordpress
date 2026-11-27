<?php
/**
 * Model D: ARMember's own PayPal integration = legacy PayPal Standard / recurring payment profiles (I-…)
 * driven by IPN (?arm-listener=arm_paypal_notify). The installed ARMember has no PayPal REST client.
 *
 * Supported read-only lookup: NVP GetRecurringPaymentsProfileDetails for a KNOWN profile id, using the
 * credentials ARMember already stores. There is NO supported API to search profiles by e-mail, so e-mail
 * discovery is reported as unsupported. The IPN URL of an existing profile cannot be read through the API,
 * therefore renewal processing can never be reported as verified here.
 *
 * Not supported (documented): PayPal Subscriptions API, vault tokens/billing agreements, and the
 * WooCommerce PayPal gateways (woocommerce-paypal-payments, pymntpl-paypal-woocommerce).
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Adapter_Paypal_Native extends Gateway_Adapter {

	const RE_PROFILE = '/^I-[A-Z0-9]{8,}$/';

	public function id() {
		return 'paypal_native';
	}

	public function label() {
		return 'Model D – ARMember PayPal recurring profile (IPN)';
	}

	public function matches( array $facts ) {
		$a = isset( $facts['audit'] ) ? $facts['audit'] : array();
		if ( 'paypal' !== ( isset( $a['gateway_key'] ) ? $a['gateway_key'] : '' ) ) {
			return false;
		}
		// ARMember's own gateway id is exactly "paypal". Other PayPal-looking ids (ppcp-gateway,
		// paypal_express, …) are WooCommerce gateways ⇒ Unsupported. Imported members whose ARMember
		// gateway is empty/manual are matched through their profile field and then flagged as mis-configured.
		return 'paypal' === strtolower( (string) ( isset( $a['gateway'] ) ? $a['gateway'] : '' ) ) || ! empty( $a['from_profile'] );
	}

	public function fields( array $facts ) {
		$rows = array(
			self::field( array(
				'key' => 'paypal_profile_id', 'label' => 'PayPal recurring profile / subscription ID', 'required' => true, 'format' => self::RE_PROFILE, 'format_hint' => 'I-XXXXXXXXXXXX',
				'store' => 'armember', 'source' => 'arm_user_plan_{id}.arm_subscr_id, arm_paypal.arm_subscr_id, payment log token', 'retrievable' => false, 'editable' => true, 'verify' => true,
				'arm_paths' => array( array( 'arm_subscr_id' ), array( 'arm_paypal', 'arm_subscr_id' ) ),
				'validation' => 'Verified via PayPal NVP GetRecurringPaymentsProfileDetails. Cannot be searched by e-mail (no supported API).',
			) ),
			self::field( array(
				'key' => 'paypal_payer_email', 'label' => 'PayPal payer e-mail (informational)', 'required' => false, 'type' => 'email', 'format' => '/^[^@\s]+@[^@\s]+$/', 'format_hint' => 'e-mail',
				'store' => 'paylog', 'source' => 'arm_payment_log.arm_payer_email', 'retrievable' => false, 'editable' => false, 'verify' => false, 'future' => false,
				'validation' => 'Informational only; e-mail alone is never proof of ownership. Payer ID is not stored by ARMember and not returned by the NVP profile call.',
			) ),
		);
		return array_merge( self::arm_common_fields( 'paypal', 'auto_debit_subscription|manual_subscription', 'paypal', 'auto_debit_subscription or manual_subscription' ), $rows );
	}

	public function discover( array $facts ) {
		$found = array();
		self::arm_common_discover( $facts, $found );
		self::add( $found, 'paypal_profile_id', self::raw_value( $facts, array( 'arm_paypal', 'arm_subscr_id' ) ), 'ARMember plan meta (arm_paypal.arm_subscr_id)' );
		self::add( $found, 'paypal_profile_id', self::raw_value( $facts, array( 'arm_subscr_id' ) ), 'ARMember plan meta (arm_subscr_id)' );
		foreach ( isset( $facts['pay']['log_refs'] ) ? $facts['pay']['log_refs'] : array() as $r ) {
			if ( preg_match( '/^I-[A-Za-z0-9]+$/', (string) $r['value'] ) ) {
				self::add( $found, 'paypal_profile_id', $r['value'], 'Payment log #' . $r['log_id'] . ' (' . $r['field'] . ')' );
			}
		}
		if ( ! empty( $facts['pay']['payer_emails'] ) ) {
			foreach ( $facts['pay']['payer_emails'] as $e ) {
				self::add( $found, 'paypal_payer_email', $e, 'arm_payment_log.arm_payer_email' );
			}
		}
		self::add_staged( $found, $facts, array( 'paypal_profile_id' ) );
		return $found;
	}

	public function lookup( array $facts, array $found, Http $http, Secrets $secrets ) {
		$b   = self::empty_lookup();
		$ids = array();
		foreach ( isset( $found['paypal_profile_id'] ) ? $found['paypal_profile_id'] : array() as $e ) {
			$ids[ $e['value'] ] = true;
		}
		$ids = array_keys( $ids );
		if ( ! $ids ) {
			self::step( $b, 'PayPal lookup', Status::STEP_SKIPPED, 'No profile id known. PayPal offers no supported API to search recurring profiles by e-mail; administrator input is required.' );
			return $b;
		}
		if ( count( $ids ) > 1 ) {
			foreach ( $ids as $i ) {
				$b['candidates'][] = array( 'key' => 'paypal_profile_id', 'value' => $i, 'note' => 'Several profile ids stored locally – choose one.' );
			}
			self::step( $b, 'PayPal lookup', Status::STEP_WARNING, 'Several different profile ids are stored; none selected automatically.' );
			return $b;
		}
		$id = $ids[0];
		if ( ! preg_match( self::RE_PROFILE, $id ) ) {
			$b['invalid']['paypal_profile_id'] = 'Not a valid PayPal recurring profile id.';
			self::step( $b, 'PayPal lookup', Status::STEP_FAILED, 'Profile id has an invalid format.', $id );
			return $b;
		}
		$nvp = $secrets->paypal_nvp();
		$b['attempted'] = true;
		if ( ! $nvp || empty( $nvp['user'] ) || empty( $nvp['pwd'] ) || empty( $nvp['signature'] ) ) {
			$b['error'] = 'ARMember PayPal API credentials (username/password/signature) are not configured.';
			self::step( $b, 'PayPal credentials', Status::STEP_FAILED, $b['error'], '', $b['error'] );
			return $b;
		}
		$url = ! empty( $nvp['sandbox'] ) ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';
		$r   = $http->post_form(
			$url,
			array(
				'USER'      => $nvp['user'],
				'PWD'       => $nvp['pwd'],
				'SIGNATURE' => $nvp['signature'],
				'METHOD'    => 'GetRecurringPaymentsProfileDetails', // Read-only.
				'VERSION'   => '124',
				'PROFILEID' => $id,
			)
		);
		if ( ! $r['ok'] ) {
			$b['error'] = $r['error'];
			self::step( $b, 'PayPal profile lookup', Status::STEP_FAILED, 'PayPal request failed.', $id, $r['error'] );
			return $b;
		}
		$body = $r['body'];
		$ack  = isset( $body['ACK'] ) ? strtolower( (string) $body['ACK'] ) : '';
		if ( 'success' !== $ack && 'successwithwarning' !== $ack ) {
			$code = isset( $body['L_ERRORCODE0'] ) ? (string) $body['L_ERRORCODE0'] : '';
			$msg  = isset( $body['L_LONGMESSAGE0'] ) ? (string) $body['L_LONGMESSAGE0'] : 'PayPal returned a failure.';
			// 11592 = invalid profile id (documented NVP error); anything else is treated as an API error, not as proof.
			if ( '11592' === $code ) {
				$b['invalid']['paypal_profile_id'] = 'PayPal does not recognise this profile id.';
				self::step( $b, 'PayPal profile lookup', Status::STEP_FAILED, 'Profile not found in PayPal.', $id, $msg );
			} else {
				$b['error'] = 'PayPal error ' . $code . ': ' . $msg;
				self::step( $b, 'PayPal profile lookup', Status::STEP_FAILED, 'PayPal returned an error.', $id, $b['error'] );
			}
			return $b;
		}
		$status                              = isset( $body['STATUS'] ) ? (string) $body['STATUS'] : '';
		$b['objects']['paypal_profile_id']   = $id;
		$b['sub_status']                     = 'Active' === $status ? 'active' : 'inactive';
		$b['sub_detail']                     = 'PayPal profile status: ' . $status . ( isset( $body['NEXTBILLINGDATE'] ) ? ', next billing ' . $body['NEXTBILLINGDATE'] : '' );
		self::step( $b, 'PayPal profile lookup', Status::STEP_COMPLETED, 'Profile found. ' . $b['sub_detail'], $id );

		// Ownership: the id must be linked to THIS member by ARMember data (plan meta / payment log), not only staged.
		$local = false;
		foreach ( $found['paypal_profile_id'] as $e ) {
			if ( 0 !== strpos( $e['source'], 'Admin input' ) ) {
				$local = true;
			}
		}
		if ( $local || ! empty( $facts['staged']['paypal_profile_id']['confirmed'] ) ) {
			$b['ownership']      = 'verified';
			$b['ownership_note'] = $local ? 'Profile id is stored in this member\'s ARMember records.' : 'Ownership confirmed by an administrator.';
		} else {
			$b['ownership']      = 'unverified';
			$b['ownership_note'] = 'Profile id was only supplied by an administrator and is not linked to this member by any ARMember record.';
		}
		$b['webhook'] = array( 'checked' => false, 'ok' => false, 'detail' => 'The IPN URL of an existing PayPal profile cannot be read via the API; verify IPN history in PayPal.' );
		return $b;
	}

	public function arm_expectations( array $facts ) {
		return array(
			'gateway_keys'   => array( 'paypal' ),
			'mode'           => 'auto_debit_subscription|manual_subscription',
			'label'          => 'arm_user_gateway=paypal',
			'renewal_driver' => 'PayPal IPN → ARMember (arm-listener=arm_paypal_notify)',
		);
	}

	public function renewal_check( array $facts, array $gateway, array $fields ) {
		$id     = isset( $gateway['objects']['paypal_profile_id'] ) ? $gateway['objects']['paypal_profile_id'] : '';
		$stored = '' !== $id && ( self::raw_value( $facts, array( 'arm_subscr_id' ) ) === $id || self::raw_value( $facts, array( 'arm_paypal', 'arm_subscr_id' ) ) === $id );
		$checks = array(
			array( 'label' => 'Profile id inside arm_user_plan_{id} (IPN member lookup uses usermeta LIKE)', 'status' => $stored ? 'ok' : 'fail', 'detail' => $stored ? 'Stored.' : 'Not stored in ARMember plan meta.' ),
			array( 'label' => 'PayPal profile active', 'status' => 'active' === $gateway['sub_status'] ? 'ok' : 'fail', 'detail' => $gateway['sub_detail'] ),
			array( 'label' => 'IPN delivery to ARMember listener', 'status' => 'unknown', 'detail' => 'Cannot be verified through any supported PayPal API. Check IPN history/notification URL in PayPal.' ),
		);
		return array(
			'status' => 'not_verified',
			'detail' => 'PayPal IPN delivery cannot be verified programmatically; renewal processing stays "not verified" by design.',
			'checks' => $checks,
		);
	}
}
