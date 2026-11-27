<?php
/**
 * Discovery engine, WordPress side: reads wp_users, usermeta, ARMember (plan meta, payment log, entries),
 * WooCommerce (tokens, customer option, orders) and the plugin's staged values, and returns the "facts" array
 * the pure adapters/assessor consume. Read-only; uses the dynamic table prefix everywhere.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Collector {

	/** @var Member_Repository */
	private $repo;

	public function __construct( Member_Repository $repo ) {
		$this->repo = $repo;
	}

	/**
	 * @return array|null Facts, or null when the user has no such ARMember plan row.
	 */
	public function collect( $user_id, $plan_id ) {
		$user_id = absint( $user_id );
		$plan_id = absint( $plan_id );
		$row     = null;
		foreach ( $this->repo->rows_for_user( $user_id ) as $r ) {
			if ( (int) $r['plan_id'] === $plan_id ) {
				$row = $r;
				break;
			}
		}
		if ( ! $row ) {
			return null;
		}
		$a    = $row['audit'];
		$m    = $row['meta'];
		$gkey = Subscription_Audit::gateway_key( $a['gateway'] );

		// Raw ARMember plan meta, reduced to the keys Phase 2 reasons about (scalars only; no plan detail blob).
		$raw_meta = $plan_id ? get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true ) : array();
		$raw      = array();
		if ( is_array( $raw_meta ) ) {
			foreach ( array( 'arm_user_gateway', 'arm_payment_mode', 'arm_subscr_id', 'arm_next_due_payment', 'arm_cencelled_plan' ) as $k ) {
				if ( isset( $raw_meta[ $k ] ) && is_scalar( $raw_meta[ $k ] ) ) {
					$raw[ $k ] = $raw_meta[ $k ];
				}
			}
			foreach ( array( 'arm_stripe', 'arm_paypal' ) as $k ) {
				$raw[ $k ] = array();
				if ( isset( $raw_meta[ $k ] ) && is_array( $raw_meta[ $k ] ) ) {
					foreach ( $raw_meta[ $k ] as $kk => $vv ) {
						if ( is_scalar( $vv ) ) {
							$raw[ $k ][ $kk ] = $vv;
						}
					}
				}
			}
		}

		// Payment log identifiers.
		$log_refs = array();
		$payers   = array();
		foreach ( $this->repo->payments()->refs_for_user( $user_id, $plan_id ) as $lr ) {
			foreach ( array( 'arm_token', 'arm_transaction_id' ) as $f ) {
				$v = isset( $lr[ $f ] ) ? trim( (string) $lr[ $f ] ) : '';
				if ( '' !== $v ) {
					$log_refs[] = array( 'log_id' => isset( $lr['arm_log_id'] ) ? (int) $lr['arm_log_id'] : 0, 'field' => $f, 'value' => $v, 'gateway' => isset( $lr['arm_payment_gateway'] ) ? (string) $lr['arm_payment_gateway'] : '' );
				}
			}
			if ( ! empty( $lr['arm_payer_email'] ) ) {
				$payers[ strtolower( (string) $lr['arm_payer_email'] ) ] = true;
			}
		}

		$staged = $this->staged( $user_id, $plan_id );
		$facts  = array(
			'user'          => $row['user'],
			'plan_id'       => $plan_id,
			'plan_name'     => $row['plan_name'],
			'plan_kind'     => $row['plan_kind'],
			'has_plan_meta' => is_array( $raw_meta ) && ! empty( $raw_meta ),
			'audit'         => array(
				'gateway'      => (string) $a['gateway'],
				'gateway_key'  => $gkey,
				'mode'         => (string) $a['mode'],
				'is_recurring' => ! empty( $a['is_recurring'] ),
				'model'        => (string) $a['model'],
				'phase1'       => (string) $a['status'],
				'from_profile' => in_array( strtolower( (string) $m['gateway'] ), array( '', 'manual' ), true ) && '' !== (string) $m['profile_gateway'],
			),
			'meta'          => array(
				'gateway'                 => (string) $m['gateway'],
				'mode'                    => (string) $m['mode'],
				'subscr_id'               => (string) $m['subscr_id'],
				'next_due'                => $m['next_due'],
				'completed'               => $m['completed'],
				'cancelled'               => $m['cancelled'],
				'cycle'                   => $m['cycle'],
				'profile_gateway'         => (string) $m['profile_gateway'],
				'selected_payment_method' => (string) $m['selected_payment_method'],
				'raw'                     => $raw,
			),
			'pay'           => array(
				'count'        => (int) $row['pay']['count'],
				'latest'       => $row['pay']['latest'],
				'log_refs'     => $log_refs,
				'gateways'     => $row['pay']['gateways'],
				'modes'        => $row['pay']['modes'],
				'payer_emails' => array_keys( $payers ),
			),
			'wc'            => $this->woocommerce( $user_id ),
			'staged'        => $staged,
			'shared_refs'   => array(),
		);

		// Duplicate reference detection across other members.
		$ids = array();
		foreach ( $log_refs as $r ) {
			if ( preg_match( '/^(sub_|cus_|I-)/', $r['value'] ) ) {
				$ids[] = $r['value'];
			}
		}
		foreach ( array( array( 'arm_subscr_id' ), array( 'arm_stripe', 'customer_id' ), array( 'arm_stripe', 'transaction_id' ), array( 'arm_paypal', 'arm_subscr_id' ) ) as $p ) {
			$v = Gateway_Adapter::raw_value( $facts, $p );
			if ( '' !== $v ) {
				$ids[] = $v;
			}
		}
		foreach ( $staged as $s ) {
			$ids[] = $s['value'];
		}
		$facts['shared_refs'] = $this->shared_refs( array_unique( $ids ), $user_id );
		return $facts;
	}

	private function staged( $user_id, $plan_id ) {
		global $wpdb;
		$t = Installer::table( 'mappings' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT field_key, value, confirmed, status FROM `{$t}` WHERE user_id = %d AND plan_id = %d AND status IN ('pending','verified')", $user_id, $plan_id ), ARRAY_A );
		$out  = array();
		foreach ( (array) $rows as $r ) {
			$out[ $r['field_key'] ] = array( 'value' => $r['value'], 'confirmed' => (bool) $r['confirmed'], 'status' => $r['status'] );
		}
		return $out;
	}

	private function woocommerce( $user_id ) {
		$wc = array( 'available' => class_exists( 'WooCommerce' ), 'stripe_customer_id' => '', 'tokens' => array(), 'orders' => 0, 'order_refs' => array( 'customers' => array(), 'sources' => array(), 'mandates' => array() ), 'subscriptions_ext' => class_exists( 'WC_Subscriptions' ) );
		if ( ! $wc['available'] ) {
			return $wc;
		}
		$cid = get_user_option( '_stripe_customer_id', $user_id ); // WooCommerce Stripe stores it with update_user_option.
		$wc['stripe_customer_id'] = is_string( $cid ) ? trim( $cid ) : '';
		if ( class_exists( 'WC_Payment_Tokens' ) ) {
			foreach ( \WC_Payment_Tokens::get_customer_tokens( $user_id ) as $token ) {
				$wc['tokens'][] = array( 'id' => (int) $token->get_id(), 'gateway_id' => (string) $token->get_gateway_id(), 'token' => (string) $token->get_token(), 'type' => (string) $token->get_type() );
			}
		}
		if ( function_exists( 'wc_get_orders' ) ) {
			$ids = wc_get_orders( array( 'customer_id' => $user_id, 'limit' => 20, 'return' => 'ids', 'orderby' => 'date', 'order' => 'DESC' ) );
			$wc['orders'] = is_array( $ids ) ? count( $ids ) : 0;
			foreach ( (array) $ids as $oid ) {
				$o = wc_get_order( $oid );
				if ( ! $o ) {
					continue;
				}
				foreach ( array( '_stripe_customer_id' => 'customers', '_stripe_source_id' => 'sources', '_stripe_mandate_id' => 'mandates' ) as $mk => $bucket ) {
					$v = $o->get_meta( $mk );
					if ( is_string( $v ) && '' !== trim( $v ) ) {
						$wc['order_refs'][ $bucket ][ trim( $v ) ] = trim( $v );
					}
				}
			}
			foreach ( $wc['order_refs'] as $k => $v ) {
				$wc['order_refs'][ $k ] = array_values( $v );
			}
		}
		return $wc;
	}

	/** @return array<string,int[]> */
	private function shared_refs( array $ids, $user_id ) {
		global $wpdb;
		$ids = array_values( array_filter( $ids ) );
		if ( ! $ids ) {
			return array();
		}
		$out = $this->repo->payments()->other_users_with_refs( $ids, $user_id );
		// ARMember plan meta of other members (webhook lookups use the same LIKE search).
		foreach ( $ids as $id ) {
			$like = '%' . $wpdb->esc_like( $id ) . '%';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$users = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND meta_value LIKE %s AND user_id <> %d LIMIT 20", $wpdb->esc_like( 'arm_user_plan_' ) . '%', $like, $user_id ) );
			foreach ( (array) $users as $u ) {
				$out[ $id ][] = (int) $u;
			}
		}
		return array_map( function ( $v ) {
			return array_values( array_unique( $v ) );
		}, array_filter( $out ) );
	}
}
