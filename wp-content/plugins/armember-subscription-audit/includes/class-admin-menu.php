<?php
/**
 * Admin menu, request parsing (sanitised / whitelisted) and view dispatch.
 *
 * @package ARMSA
 */

namespace ARMSA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin_Menu {

	const SLUG       = 'armsa-members';
	const CAPABILITY = 'manage_options';
	const NONCE      = 'armsa_view_member';

	/** @var Member_Repository */
	private $repository;

	public function __construct( Member_Repository $repository ) {
		$this->repository = $repository;
	}

	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	public function add_menu() {
		add_menu_page( 'ARMember Subscription Audit', 'ARMember Subscription Audit', self::CAPABILITY, self::SLUG, array( $this, 'render' ), 'dashicons-clipboard', 58 );
		add_submenu_page( self::SLUG, 'Members', 'Members', self::CAPABILITY, self::SLUG, array( $this, 'render' ) );
	}

	public static function gateway_options() {
		return array(
			'stripe'      => 'Stripe',
			'woocommerce' => 'WooCommerce',
			'paypal'      => 'PayPal',
			'gocardless'  => 'GoCardless',
			'other'       => 'Other',
			'none'        => 'Not configured',
		);
	}

	public static function mode_options() {
		return array(
			'auto_debit' => 'Auto Debit',
			'manual'     => 'Manual Subscription',
			'other'      => 'Other',
			'none'       => 'None',
		);
	}

	/** Details URL (nonce protected). */
	public static function details_url( $user_id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'page'    => self::SLUG,
					'view'    => 'details',
					'user_id' => absint( $user_id ),
				),
				admin_url( 'admin.php' )
			),
			self::NONCE
		);
	}

	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'armember-subscription-audit' ), 403 );
		}

		if ( ! $this->repository->payments()->is_available() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'ARMember payment log table (or its arm_user_id / arm_plan_id columns) was not found. Payment information will show as "No Record".', 'armember-subscription-audit' ) . '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing, details view verifies nonce below.
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		if ( 'details' === $view ) {
			$this->render_details();
			return;
		}
		$this->render_list();
	}

	private function render_list() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET filters on a read-only, capability-gated list.
		$get      = wp_unslash( $_GET );
		$gateway  = isset( $get['gateway'] ) ? sanitize_key( $get['gateway'] ) : '';
		$mode     = isset( $get['mode'] ) ? sanitize_key( $get['mode'] ) : '';
		$status   = isset( $get['audit_status'] ) ? sanitize_key( $get['audit_status'] ) : '';
		$orderby  = isset( $get['orderby'] ) && 'last_payment' === $get['orderby'] ? 'last_payment' : 'id';
		$order    = isset( $get['order'] ) && 'asc' === strtolower( (string) $get['order'] ) ? 'asc' : 'desc';
		$page_num = isset( $get['paged'] ) ? max( 1, absint( $get['paged'] ) ) : 1;
		$search   = isset( $get['s'] ) ? sanitize_text_field( $get['s'] ) : '';
		// phpcs:enable

		$args = array(
			'search'   => $search,
			'gateway'  => isset( self::gateway_options()[ $gateway ] ) ? $gateway : '',
			'mode'     => isset( self::mode_options()[ $mode ] ) ? $mode : '',
			'status'   => isset( Subscription_Audit::status_labels()[ $status ] ) ? $status : '',
			'orderby'  => $orderby,
			'order'    => $order,
			'page'     => $page_num,
			'per_page' => 20,
		);

		$result    = $this->repository->list_rows( $args );
		$rows      = $result['rows'];
		$total     = $result['total'];
		$pages     = $result['total_pages'];
		$truncated = $this->repository->truncated;
		$filters   = $args;

		include ARMSA_DIR . 'admin/views/members-list.php';
	}

	private function render_details() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified on the next line.
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_die( esc_html__( 'This link has expired. Please go back to the list and try again.', 'armember-subscription-audit' ), 403 );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		$rows    = $user_id ? $this->repository->rows_for_user( $user_id ) : array();
		if ( ! $rows ) {
			wp_die( esc_html__( 'Member not found.', 'armember-subscription-audit' ), 404 );
		}
		$history  = $this->repository->payments()->history_for_user( $user_id );
		$user     = $rows[0]['user'];
		$back_url = add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) );

		include ARMSA_DIR . 'admin/views/member-details.php';
	}
}
