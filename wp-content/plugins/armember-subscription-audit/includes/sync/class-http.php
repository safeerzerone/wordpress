<?php
/**
 * Minimal read-only HTTP layer for gateway lookups (injectable for tests).
 *
 * Only GET and the PayPal NVP "Get…Details" POST are ever issued. Secrets are passed in
 * headers/body by the adapter and are never logged here.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Http {
	/**
	 * @param string $url     Absolute URL.
	 * @param array  $headers Headers.
	 * @return array { ok:bool, code:int, body:array|null, error:string }
	 */
	public function get( $url, array $headers = array() );

	/**
	 * Read-only form POST (PayPal NVP detail lookups only).
	 *
	 * @param string $url  Absolute URL.
	 * @param array  $form Form fields.
	 * @return array { ok:bool, code:int, body:array|null, error:string }
	 */
	public function post_form( $url, array $form );
}

final class Wp_Http implements Http {

	public function get( $url, array $headers = array() ) {
		$r = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => $headers,
			)
		);
		return $this->normalise( $r, false );
	}

	public function post_form( $url, array $form ) {
		$r = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'body'    => $form,
			)
		);
		return $this->normalise( $r, true );
	}

	private function normalise( $r, $nvp ) {
		if ( is_wp_error( $r ) ) {
			return array( 'ok' => false, 'code' => 0, 'body' => null, 'error' => Logger::redact( $r->get_error_message() ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $r );
		$raw  = (string) wp_remote_retrieve_body( $r );
		if ( $nvp ) {
			parse_str( $raw, $body );
		} else {
			$body = json_decode( $raw, true );
		}
		$ok = $code >= 200 && $code < 300 && is_array( $body );
		$err = '';
		if ( ! $ok ) {
			$err = 'HTTP ' . $code;
			if ( is_array( $body ) && isset( $body['error']['message'] ) ) {
				$err .= ': ' . Logger::redact( (string) $body['error']['message'] );
			}
		}
		return array( 'ok' => $ok, 'code' => $code, 'body' => is_array( $body ) ? $body : null, 'error' => $err );
	}
}
