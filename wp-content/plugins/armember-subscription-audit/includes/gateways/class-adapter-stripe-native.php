<?php
/**
 * Model A: native ARMember Stripe recurring (arm_user_gateway=stripe, auto_debit_subscription).
 *
 * Renewal path (installed ARMember source): Stripe webhook ?arm-listener=arm_stripe_notify →
 * arm_modify_payment_webhook_data() finds the member by `usermeta.meta_value LIKE %<sub id>%`
 * (arm_user_plan_{pid}) or arm_payment_log.arm_token. Therefore the subscription id MUST be inside the
 * member's arm_user_plan_{pid} meta (arm_subscr_id / arm_stripe.transaction_id) for renewals to resolve.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Adapter_Stripe_Native extends Gateway_Adapter {

	const RE_CUS = '/^cus_[A-Za-z0-9]+$/';
	const RE_SUB = '/^sub_(?:sched_)?[A-Za-z0-9]+$/';
	const RE_PM  = '/^(?:pm|card|src)_[A-Za-z0-9]+$/';

	public function id() {
		return 'stripe_native';
	}

	public function label() {
		return 'Model A – Native ARMember Stripe subscription';
	}

	public function matches( array $facts ) {
		return 'stripe' === ( isset( $facts['audit']['gateway_key'] ) ? $facts['audit']['gateway_key'] : '' );
	}

	public function fields( array $facts ) {
		$rows = array(
			self::field( array(
				'key' => 'stripe_customer_id', 'label' => 'Stripe Customer ID', 'required' => true, 'format' => self::RE_CUS, 'format_hint' => 'cus_…',
				'store' => 'armember', 'source' => 'arm_user_plan_{id}.arm_stripe.customer_id / payment log token', 'retrievable' => true, 'editable' => true, 'verify' => true,
				'arm_paths' => array( array( 'arm_stripe', 'customer_id' ) ), 'validation' => 'Must exist in Stripe and belong to this member (not e-mail alone).',
			) ),
			self::field( array(
				'key' => 'stripe_subscription_id', 'label' => 'Stripe Subscription ID', 'required' => true, 'format' => self::RE_SUB, 'format_hint' => 'sub_… (or sub_sched_…)',
				'store' => 'armember', 'source' => 'arm_user_plan_{id}.arm_subscr_id, arm_stripe.transaction_id, payment log token', 'retrievable' => true, 'editable' => true, 'verify' => true,
				'arm_paths' => array( array( 'arm_subscr_id' ), array( 'arm_stripe', 'transaction_id' ) ), 'validation' => 'Must exist in Stripe, be active/trialing and belong to the verified customer.',
			) ),
			self::field( array(
				'key' => 'stripe_payment_method', 'label' => 'Stripe default PaymentMethod', 'required' => true, 'format' => self::RE_PM, 'format_hint' => 'pm_…',
				'store' => 'gateway', 'source' => 'Stripe subscription / customer default', 'retrievable' => true, 'editable' => false, 'verify' => true,
				'validation' => 'Read from Stripe only; ARMember does not store it. A renewal cannot be charged without one.',
			) ),
		);
		return array_merge( self::arm_common_fields( 'stripe', 'auto_debit_subscription', 'stripe', 'auto_debit_subscription' ), $rows );
	}

	public function discover( array $facts ) {
		$found = array();
		self::arm_common_discover( $facts, $found );

		self::add( $found, 'stripe_customer_id', self::raw_value( $facts, array( 'arm_stripe', 'customer_id' ) ), 'ARMember plan meta (arm_stripe.customer_id)' );
		self::add( $found, 'stripe_subscription_id', self::raw_value( $facts, array( 'arm_stripe', 'transaction_id' ) ), 'ARMember plan meta (arm_stripe.transaction_id)' );
		$sub = self::raw_value( $facts, array( 'arm_subscr_id' ) );
		if ( preg_match( self::RE_SUB, $sub ) ) {
			self::add( $found, 'stripe_subscription_id', $sub, 'ARMember plan meta (arm_subscr_id)' );
		} elseif ( '' !== $sub ) {
			self::add( $found, 'stripe_subscription_id', $sub, 'ARMember plan meta (arm_subscr_id)' ); // Kept so format validation flags it.
		}
		foreach ( isset( $facts['pay']['log_refs'] ) ? $facts['pay']['log_refs'] : array() as $r ) {
			foreach ( self::stripe_ids_from( $r['value'], 'cus_' ) as $id ) {
				self::add( $found, 'stripe_customer_id', $id, 'Payment log #' . $r['log_id'] . ' (' . $r['field'] . ')' );
			}
			foreach ( self::stripe_ids_from( $r['value'], 'sub_' ) as $id ) {
				self::add( $found, 'stripe_subscription_id', $id, 'Payment log #' . $r['log_id'] . ' (' . $r['field'] . ')' );
			}
		}
		self::add_staged( $found, $facts, array( 'stripe_customer_id', 'stripe_subscription_id' ) );
		return $found;
	}

	/** Customer ids that are linked to THIS user by locally stored, user-specific records. */
	private function local_customers( array $facts, array $found ) {
		$ids = array();
		foreach ( isset( $found['stripe_customer_id'] ) ? $found['stripe_customer_id'] : array() as $e ) {
			if ( 0 !== strpos( $e['source'], 'Admin input' ) || ! empty( $facts['staged']['stripe_customer_id']['confirmed'] ) ) {
				$ids[ $e['value'] ] = true;
			}
		}
		return array_keys( $ids );
	}

	public function lookup( array $facts, array $found, Http $http, Secrets $secrets ) {
		$b   = self::empty_lookup();
		$key = $secrets->stripe_key( 'native' );
		if ( '' === (string) $key ) {
			$b['attempted'] = true;
			$b['error']     = 'ARMember Stripe credentials are not configured or the Stripe gateway is inactive.';
			self::step( $b, 'Stripe credentials', Status::STEP_FAILED, 'No active ARMember Stripe credentials.', '', $b['error'] );
			return $b;
		}
		$b['attempted'] = true;
		$api            = new Stripe_Client( $http, $key );

		$subs = $this->distinct( $found, 'stripe_subscription_id' );
		$cus  = $this->distinct( $found, 'stripe_customer_id' );
		$local = $this->local_customers( $facts, $found );

		$sub_obj = null;
		if ( 1 === count( $subs ) && preg_match( self::RE_SUB, $subs[0] ) ) {
			$sub_id = $subs[0];
			$path   = 0 === strpos( $sub_id, 'sub_sched_' ) ? 'subscription_schedules/' . $sub_id : 'subscriptions/' . $sub_id;
			$r      = $api->get( $path );
			if ( $r['ok'] && 'subscription_schedule' === ( isset( $r['body']['object'] ) ? $r['body']['object'] : '' ) ) {
				$real = Stripe_Client::id_of( isset( $r['body']['subscription'] ) ? $r['body']['subscription'] : '' );
				self::step( $b, 'Subscription schedule lookup', Status::STEP_COMPLETED, 'Schedule resolved to subscription ' . ( $real ? $real : '(not started yet)' ), $sub_id );
				if ( '' === $real ) {
					$b['sub_status'] = 'inactive';
					$b['sub_detail'] = 'Schedule has no live subscription yet.';
					return $this->finish_cus( $b, $api, $facts, $cus, $local );
				}
				$sub_id = $real;
				$r      = $api->get( 'subscriptions/' . $sub_id );
			}
			if ( $r['ok'] ) {
				$sub_obj                          = $r['body'];
				$b['objects']['stripe_subscription_id'] = $sub_id;
				self::step( $b, 'Subscription lookup', Status::STEP_COMPLETED, 'Stripe subscription found, status ' . ( isset( $sub_obj['status'] ) ? $sub_obj['status'] : '?' ), $sub_id );
			} elseif ( 404 === $r['code'] ) {
				$b['invalid']['stripe_subscription_id'] = 'Stripe reports this subscription does not exist (' . $r['error'] . ').';
				self::step( $b, 'Subscription lookup', Status::STEP_FAILED, 'Subscription not found in Stripe.', $sub_id, $r['error'] );
			} else {
				$b['error'] = $r['error'];
				self::step( $b, 'Subscription lookup', Status::STEP_FAILED, 'Stripe request failed.', $sub_id, $r['error'] );
				return $b;
			}
		} elseif ( count( $subs ) > 1 ) {
			foreach ( $subs as $s ) {
				$b['candidates'][] = array( 'key' => 'stripe_subscription_id', 'value' => $s, 'note' => 'Several subscription ids are stored locally – choose one.' );
			}
			self::step( $b, 'Subscription lookup', Status::STEP_WARNING, count( $subs ) . ' different subscription ids are stored locally; none selected automatically.' );
		}

		if ( $sub_obj ) {
			$status = isset( $sub_obj['status'] ) ? (string) $sub_obj['status'] : '';
			$b['sub_status'] = in_array( $status, array( 'active', 'trialing' ), true ) ? 'active' : 'inactive';
			$b['sub_detail'] = 'Stripe status: ' . $status;
			$sub_cus         = Stripe_Client::id_of( isset( $sub_obj['customer'] ) ? $sub_obj['customer'] : '' );
			if ( $sub_cus ) {
				$b['objects']['stripe_customer_id'] = $sub_cus;
			}
			if ( $local ) {
				if ( in_array( $sub_cus, $local, true ) ) {
					$b['ownership']      = 'verified';
					$b['ownership_note'] = 'Subscription customer matches a customer id stored for this member.';
				} else {
					$b['ownership']      = 'mismatch';
					$b['ownership_note'] = 'Subscription belongs to ' . $sub_cus . ', but this member is linked to ' . implode( ', ', $local ) . '.';
				}
			} elseif ( ! empty( $facts['staged']['stripe_subscription_id']['confirmed'] ) && ( isset( $facts['staged']['stripe_subscription_id']['value'] ) && $facts['staged']['stripe_subscription_id']['value'] === $sub_id ) ) {
				$b['ownership']      = 'verified';
				$b['ownership_note'] = 'Ownership confirmed by an administrator.';
			} else {
				$b['ownership']      = 'unverified';
				$b['ownership_note'] = 'No locally stored customer link for this member; ownership not proven.';
			}
			$pm = Stripe_Client::id_of( isset( $sub_obj['default_payment_method'] ) ? $sub_obj['default_payment_method'] : '' );
			if ( '' === $pm && $sub_cus ) {
				$c = $api->get( 'customers/' . $sub_cus );
				if ( $c['ok'] && isset( $c['body']['invoice_settings']['default_payment_method'] ) ) {
					$pm = Stripe_Client::id_of( $c['body']['invoice_settings']['default_payment_method'] );
				}
			}
			if ( '' !== $pm ) {
				$b['objects']['stripe_payment_method'] = $pm;
				self::step( $b, 'Payment method lookup', Status::STEP_COMPLETED, 'Default payment method present.', $pm );
			} else {
				self::step( $b, 'Payment method lookup', Status::STEP_WARNING, 'No default payment method on the subscription or customer.' );
			}
			$this->webhook_check( $b, $api );
			return $b;
		}

		return $this->finish_cus( $b, $api, $facts, $cus, $local );
	}

	/** No (single, valid) subscription id: use customer-based discovery. */
	private function finish_cus( array $b, Stripe_Client $api, array $facts, array $cus, array $local ) {
		if ( ! empty( $b['invalid'] ) || ! empty( $b['candidates'] ) ) {
			$this->webhook_check( $b, $api );
			return $b;
		}
		if ( 1 === count( $cus ) && preg_match( self::RE_CUS, $cus[0] ) ) {
			$r = $api->get( 'subscriptions', array( 'customer' => $cus[0], 'status' => 'all', 'limit' => 10 ) );
			if ( ! $r['ok'] ) {
				if ( 404 === $r['code'] ) {
					$b['invalid']['stripe_customer_id'] = 'Stripe reports this customer does not exist.';
				} else {
					$b['error'] = $r['error'];
				}
				self::step( $b, 'Customer subscriptions lookup', Status::STEP_FAILED, 'Stripe request failed.', $cus[0], $r['error'] );
				return $b;
			}
			$b['objects']['stripe_customer_id'] = $cus[0];
			$live = array();
			foreach ( isset( $r['body']['data'] ) ? $r['body']['data'] : array() as $s ) {
				if ( isset( $s['status'] ) && in_array( $s['status'], array( 'active', 'trialing', 'past_due' ), true ) ) {
					$live[] = $s;
				}
			}
			self::step( $b, 'Customer subscriptions lookup', Status::STEP_COMPLETED, count( $live ) . ' live subscription(s) on the customer.', $cus[0] );
			$b['ownership']      = $local && in_array( $cus[0], $local, true ) ? 'verified' : 'unverified';
			$b['ownership_note'] = 'verified' === $b['ownership'] ? 'Customer id is stored for this member.' : 'Customer id not linked to this member by a local record.';
			foreach ( $live as $s ) {
				$b['candidates'][] = array( 'key' => 'stripe_subscription_id', 'value' => (string) $s['id'], 'note' => 'status ' . $s['status'] );
			}
			if ( 1 === count( $live ) ) {
				// A single live subscription on a customer owned by this member: unambiguous.
				if ( 'verified' === $b['ownership'] ) {
					$b['objects']['stripe_subscription_id'] = (string) $live[0]['id'];
					$b['candidates']                         = array();
					$b['sub_status']                         = 'active';
					$b['sub_detail']                         = 'Stripe status: ' . $live[0]['status'];
					$pm = Stripe_Client::id_of( isset( $live[0]['default_payment_method'] ) ? $live[0]['default_payment_method'] : '' );
					if ( '' !== $pm ) {
						$b['objects']['stripe_payment_method'] = $pm;
					}
				}
			} elseif ( 0 === count( $live ) ) {
				$b['sub_status'] = 'inactive';
				$b['sub_detail'] = 'No active/trialing/past_due subscription on this customer.';
			}
			$this->webhook_check( $b, $api );
			return $b;
		}
		// Nothing stored: e-mail discovery, ownership can never be inferred from it.
		$email = isset( $facts['user']['email'] ) ? $facts['user']['email'] : '';
		if ( '' !== $email ) {
			$r = $api->get( 'customers', array( 'email' => $email, 'limit' => 10 ) );
			if ( $r['ok'] ) {
				foreach ( isset( $r['body']['data'] ) ? $r['body']['data'] : array() as $c ) {
					$b['candidates'][] = array( 'key' => 'stripe_customer_id', 'value' => (string) $c['id'], 'note' => 'Matched by e-mail only – ownership NOT proven.' );
				}
				self::step( $b, 'Customer e-mail discovery', Status::STEP_COMPLETED, count( $b['candidates'] ) . ' Stripe customer(s) share the member e-mail.' );
				$b['ownership'] = 'unverified';
			} else {
				$b['error'] = $r['error'];
				self::step( $b, 'Customer e-mail discovery', Status::STEP_FAILED, 'Stripe request failed.', '', $r['error'] );
			}
		}
		return $b;
	}

	private function webhook_check( array &$b, Stripe_Client $api ) {
		$r = $api->get( 'webhook_endpoints', array( 'limit' => 100 ) );
		if ( ! $r['ok'] ) {
			$b['webhook'] = array( 'checked' => false, 'ok' => false, 'detail' => 'Webhook endpoints could not be listed (' . $r['error'] . '). A restricted key may lack permission.' );
			self::step( $b, 'Webhook endpoint check', Status::STEP_WARNING, 'Could not list Stripe webhook endpoints.', '', $r['error'] );
			return;
		}
		foreach ( isset( $r['body']['data'] ) ? $r['body']['data'] : array() as $e ) {
			$url    = isset( $e['url'] ) ? (string) $e['url'] : '';
			$events = isset( $e['enabled_events'] ) ? (array) $e['enabled_events'] : array();
			$on     = isset( $e['status'] ) && 'enabled' === $e['status'];
			if ( $on && false !== strpos( $url, 'arm-listener=arm_stripe' ) && ( in_array( '*', $events, true ) || in_array( 'invoice.payment_succeeded', $events, true ) ) ) {
				$b['webhook'] = array( 'checked' => true, 'ok' => true, 'detail' => 'Enabled Stripe endpoint for ARMember listener receives invoice.payment_succeeded.' );
				self::step( $b, 'Webhook endpoint check', Status::STEP_COMPLETED, 'ARMember webhook endpoint is registered and enabled.' );
				return;
			}
		}
		$b['webhook'] = array( 'checked' => true, 'ok' => false, 'detail' => 'No enabled Stripe endpoint with arm-listener=arm_stripe_* and invoice.payment_succeeded was found.' );
		self::step( $b, 'Webhook endpoint check', Status::STEP_WARNING, $b['webhook']['detail'] );
	}

	private function distinct( array $found, $key ) {
		$out = array();
		foreach ( isset( $found[ $key ] ) ? $found[ $key ] : array() as $e ) {
			$out[ $e['value'] ] = true;
		}
		return array_keys( $out );
	}

	public function arm_expectations( array $facts ) {
		return array(
			'gateway_keys'   => array( 'stripe' ),
			'mode'           => 'auto_debit_subscription',
			'label'          => 'arm_user_gateway=stripe, arm_payment_mode=auto_debit_subscription',
			'renewal_driver' => 'Stripe webhook → ARMember (arm-listener=arm_stripe_notify)',
		);
	}

	public function renewal_check( array $facts, array $gateway, array $fields ) {
		$checks = array();
		$sub    = isset( $gateway['objects']['stripe_subscription_id'] ) ? $gateway['objects']['stripe_subscription_id'] : '';
		$stored = self::raw_first( $facts, array( array( 'arm_subscr_id' ), array( 'arm_stripe', 'transaction_id' ) ) );
		$ok_ref = '' !== $sub && '' !== $stored && ( self::raw_value( $facts, array( 'arm_subscr_id' ) ) === $sub || self::raw_value( $facts, array( 'arm_stripe', 'transaction_id' ) ) === $sub );
		$checks[] = array(
			'label' => 'Subscription id inside arm_user_plan_{id} (webhook member lookup uses usermeta LIKE)',
			'status' => $ok_ref ? 'ok' : 'fail',
			'detail' => $ok_ref ? 'Stored value equals the verified Stripe subscription.' : 'Verified subscription id is not stored in ARMember plan meta, so the webhook could not resolve this member from meta.',
		);
		$checks[] = array(
			'label' => 'Payment history preserved',
			'status' => ! empty( $facts['pay']['count'] ) ? 'ok' : 'warn',
			'detail' => ! empty( $facts['pay']['count'] ) ? (int) $facts['pay']['count'] . ' payment log row(s) exist.' : 'No payment log rows for this member/plan (fallback token lookup unavailable).',
		);
		$checks[] = array(
			'label' => 'Stripe subscription active',
			'status' => 'active' === $gateway['sub_status'] ? 'ok' : ( 'n/a' === $gateway['sub_status'] ? 'unknown' : 'fail' ),
			'detail' => $gateway['sub_detail'],
		);
		$checks[] = array(
			'label' => 'Default payment method available in Stripe',
			'status' => isset( $gateway['objects']['stripe_payment_method'] ) ? 'ok' : 'fail',
			'detail' => isset( $gateway['objects']['stripe_payment_method'] ) ? 'Present.' : 'None found – the next invoice could not be charged.',
		);
		$checks[] = array(
			'label' => 'Stripe webhook → ARMember listener',
			'status' => ! empty( $gateway['webhook']['checked'] ) ? ( $gateway['webhook']['ok'] ? 'ok' : 'fail' ) : 'unknown',
			'detail' => $gateway['webhook']['detail'] ? $gateway['webhook']['detail'] : 'Not checked.',
		);
		$bad = false;
		foreach ( $checks as $c ) {
			if ( 'ok' !== $c['status'] && 'warn' !== $c['status'] ) {
				$bad = true;
			}
		}
		return array(
			'status' => $bad ? 'not_verified' : 'verified',
			'detail' => $bad ? 'One or more renewal prerequisites are not confirmed.' : 'All renewal prerequisites confirmed statically (no payment was run or simulated).',
			'checks' => $checks,
		);
	}
}
