<?php
/**
 * Gateway adapter contract + field-registry helper.
 *
 * An adapter is pure with respect to WordPress: it receives a "facts" array built by the Collector,
 * an Http object and a Secrets object. It must never write anywhere and never issue anything but
 * read-only gateway requests.
 *
 * Facts shape (see Collector):
 *   user{id,email,login,name}, plan_id, plan_name, plan_kind, has_plan_meta,
 *   meta{gateway,mode,subscr_id,next_due,completed,cancelled,cycle,profile_gateway,selected_payment_method,raw{}},
 *   pay{count,latest,log_refs[{log_id,field,value,gateway}],gateways[],modes[]},
 *   wc{available,stripe_customer_id,tokens[],orders,order_refs{customers[],sources[],mandates[]},subscriptions_ext},
 *   staged{key=>{value,confirmed,status}}, shared_refs{value=>[user ids]}, creds{stripe_native,stripe_woo,paypal}
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Secrets {
	/** @param string $scope native|woo */
	public function stripe_key( $scope );
	/** @return array|null { user, pwd, signature, sandbox:bool } */
	public function paypal_nvp();
}

abstract class Gateway_Adapter {

	/** Stable model id. */
	abstract public function id();

	/** Human label of the payment model. */
	abstract public function label();

	/** Does this adapter handle the facts? */
	abstract public function matches( array $facts );

	/** Registry rows (see self::field()). */
	abstract public function fields( array $facts );

	/**
	 * Local discovery.
	 *
	 * @return array key => list of { value, source }
	 */
	abstract public function discover( array $facts );

	/**
	 * Read-only gateway lookup. Must return self::empty_lookup() shaped data.
	 */
	abstract public function lookup( array $facts, array $found, Http $http, Secrets $secrets );

	/**
	 * ARMember-side expectations.
	 *
	 * @return array { gateway_keys[], mode, label, renewal_driver }
	 */
	abstract public function arm_expectations( array $facts );

	/**
	 * Renewal compatibility. Never claims verification the plugin cannot actually establish.
	 *
	 * @return array { status: verified|not_verified|manual, detail: string, checks: array[] }
	 */
	abstract public function renewal_check( array $facts, array $gateway, array $fields );

	/** Whether this adapter can ever write mapping data (false = report only). */
	public function supports_sync() {
		return true;
	}

	/** @return array Normalised empty lookup block. */
	public static function empty_lookup() {
		return array(
			'attempted'   => false,
			'error'       => '',
			'objects'     => array(),   // key => verified value.
			'candidates'  => array(),   // list of { key, value, note }.
			'invalid'     => array(),   // key => message (gateway says the id does not exist / is wrong).
			'ownership'   => 'n/a',     // verified | unverified | mismatch | n/a.
			'ownership_note' => '',
			'sub_status'  => 'n/a',     // active | inactive | unknown | n/a.
			'sub_detail'  => '',
			'webhook'     => array( 'checked' => false, 'ok' => false, 'detail' => '' ),
			'steps'       => array(),   // list of { step, status, desc, ref, error }.
		);
	}

	protected static function step( array &$block, $step, $status, $desc, $ref = '', $error = '' ) {
		$block['steps'][] = array(
			'step'   => $step,
			'status' => $status,
			'desc'   => $desc,
			'ref'    => $ref,
			'error'  => $error,
		);
	}

	/**
	 * Registry row with defaults.
	 *
	 * @param array $f Overrides.
	 * @return array
	 */
	public static function field( array $f ) {
		return array_merge(
			array(
				'key'          => '',
				'label'        => '',
				'required'     => false,
				'type'         => 'string',
				'format'       => '',      // PCRE (delimited) or ''.
				'format_hint'  => '',
				'store'        => 'armember', // wordpress | armember | woocommerce | gateway | paylog.
				'source'       => '',
				'retrievable'  => false,   // Can be obtained from the gateway automatically.
				'editable'     => false,   // Admin may stage a value.
				'verify'       => false,   // Needs gateway verification.
				'future'       => true,    // Needed for future recurring payments.
				'arm_paths'    => array(), // Paths inside arm_user_plan_{id} where Sync writes it; empty = never written.
				'validation'   => '',
			),
			$f
		);
	}

	/** Collapse a discovery list entry helper. */
	protected static function add( array &$found, $key, $value, $source ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( '' === $value ) {
			return;
		}
		$found[ $key ][] = array( 'value' => $value, 'source' => $source );
	}

	/** Staged admin values enter discovery with their own source label. */
	protected static function add_staged( array &$found, array $facts, array $keys ) {
		foreach ( $keys as $k ) {
			if ( ! empty( $facts['staged'][ $k ]['value'] ) ) {
				self::add( $found, $k, $facts['staged'][ $k ]['value'], 'Admin input (staged, not yet in ARMember)' );
			}
		}
	}

	/** First non-empty value ARMember holds at any of the given paths. */
	public static function raw_first( array $facts, array $paths ) {
		foreach ( $paths as $p ) {
			$v = self::raw_value( $facts, $p );
			if ( '' !== $v ) {
				return $v;
			}
		}
		return '';
	}

	/** Value stored by ARMember at $path inside the raw plan meta. */
	public static function raw_value( array $facts, array $path ) {
		$cur = isset( $facts['meta']['raw'] ) ? $facts['meta']['raw'] : array();
		foreach ( $path as $p ) {
			if ( ! is_array( $cur ) || ! isset( $cur[ $p ] ) ) {
				return '';
			}
			$cur = $cur[ $p ];
		}
		return is_scalar( $cur ) ? trim( (string) $cur ) : '';
	}

	/** Payment-log values for ids matching a pattern. */
	protected static function paylog_values( array $facts, $pattern ) {
		$out = array();
		foreach ( isset( $facts['pay']['log_refs'] ) ? $facts['pay']['log_refs'] : array() as $r ) {
			if ( preg_match( $pattern, (string) $r['value'] ) ) {
				$out[] = $r;
			}
		}
		return $out;
	}

	/**
	 * ARMember-side registry rows shared by every model.
	 *
	 * @param string $gateway_re Expected arm_user_gateway (PCRE body, no delimiters).
	 * @param string $mode_re    Expected arm_payment_mode.
	 */
	protected static function arm_common_fields( $gateway_re, $mode_re, $hint_gw, $hint_mode ) {
		return array(
			self::field( array( 'key' => 'arm_member_id', 'label' => 'ARMember member / WP user ID', 'required' => true, 'type' => 'int', 'format' => '/^[1-9][0-9]*$/', 'store' => 'wordpress', 'source' => 'wp_users', 'future' => true ) ),
			self::field( array( 'key' => 'arm_plan_id', 'label' => 'ARMember plan ID', 'required' => true, 'type' => 'int', 'format' => '/^[1-9][0-9]*$/', 'store' => 'armember', 'source' => 'arm_user_plan_ids / arm_subscription_plans', 'future' => true ) ),
			self::field( array( 'key' => 'arm_gateway', 'label' => 'ARMember gateway (arm_user_gateway)', 'required' => true, 'format' => '/^(?:' . $gateway_re . ')$/', 'format_hint' => $hint_gw, 'store' => 'armember', 'source' => 'arm_user_plan_{id}.arm_user_gateway', 'future' => true, 'validation' => 'Never auto-changed by this plugin; mismatch ⇒ manual review.' ) ),
			self::field( array( 'key' => 'arm_payment_mode', 'label' => 'ARMember payment mode (arm_payment_mode)', 'required' => true, 'format' => '/^(?:' . $mode_re . ')$/', 'format_hint' => $hint_mode, 'store' => 'armember', 'source' => 'arm_user_plan_{id}.arm_payment_mode', 'future' => true, 'validation' => 'Never auto-changed by this plugin.' ) ),
			self::field( array( 'key' => 'arm_next_due', 'label' => 'Next payment due date', 'required' => true, 'type' => 'timestamp', 'format' => '/^[1-9][0-9]{8,}$/', 'format_hint' => 'Unix timestamp', 'store' => 'armember', 'source' => 'arm_user_plan_{id}.arm_next_due_payment', 'future' => true ) ),
			self::field( array( 'key' => 'arm_payment_log', 'label' => 'ARMember payment log history', 'required' => false, 'type' => 'int', 'store' => 'armember', 'source' => 'arm_payment_log', 'future' => true ) ),
		);
	}

	protected static function arm_common_discover( array $facts, array &$found ) {
		self::add( $found, 'arm_member_id', $facts['user']['id'], 'wp_users' );
		self::add( $found, 'arm_plan_id', $facts['plan_id'], 'arm_user_plan_ids' );
		$gw = isset( $facts['meta']['gateway'] ) ? $facts['meta']['gateway'] : '';
		self::add( $found, 'arm_gateway', $gw, 'ARMember plan meta' );
		self::add( $found, 'arm_payment_mode', isset( $facts['meta']['mode'] ) ? $facts['meta']['mode'] : '', 'ARMember plan meta' );
		self::add( $found, 'arm_next_due', isset( $facts['meta']['next_due'] ) ? $facts['meta']['next_due'] : '', 'ARMember plan meta' );
		if ( ! empty( $facts['pay']['count'] ) ) {
			self::add( $found, 'arm_payment_log', $facts['pay']['count'], 'arm_payment_log' );
		}
	}

	/** Tokens may contain "cus_x|sub_y" – extract all stripe-looking ids. */
	public static function stripe_ids_from( $text, $prefix ) {
		preg_match_all( '/\b' . preg_quote( $prefix, '/' ) . '[A-Za-z0-9]+/', (string) $text, $m );
		return array_values( array_unique( $m[0] ) );
	}
}
