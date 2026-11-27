<?php
/**
 * Read-only Stripe REST helper: GET requests only (a unit test asserts no other verb exists).
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stripe_Client {

	const BASE = 'https://api.stripe.com/v1/';

	/** @var Http */
	private $http;
	/** @var string Secret key (kept in memory only; never logged or rendered). */
	private $key;

	public function __construct( Http $http, $key ) {
		$this->http = $http;
		$this->key  = (string) $key;
	}

	/**
	 * @param string $path  e.g. "subscriptions/sub_123" (validated).
	 * @param array  $query Query args.
	 * @return array { ok, code, body, error }
	 */
	public function get( $path, array $query = array() ) {
		if ( ! preg_match( '#^[a-z_]+(/[A-Za-z0-9_]+)*$#', $path ) ) {
			return array( 'ok' => false, 'code' => 0, 'body' => null, 'error' => 'Refused to request a malformed Stripe path.' );
		}
		$url = self::BASE . $path;
		if ( $query ) {
			$url .= '?' . http_build_query( $query );
		}
		return $this->http->get( $url, array( 'Authorization' => 'Bearer ' . $this->key ) );
	}

	/** Extract an id from a string or expanded object. */
	public static function id_of( $v ) {
		if ( is_array( $v ) && isset( $v['id'] ) ) {
			return (string) $v['id'];
		}
		return is_scalar( $v ) ? (string) $v : '';
	}
}
