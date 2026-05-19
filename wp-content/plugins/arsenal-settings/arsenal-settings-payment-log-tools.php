<?php
/**
 * One-off admin tools: re-run WooCommerce / Stripe → ARMember payment-log maintenance.
 *
 * @package Arsenal_Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Admin page slug. */
define( 'ARSENAL_SETTINGS_PAYMENT_TOOLS_PAGE', 'arsenal-settings-payment-log-tools' );

/** Transient prefix for run results (suffix: user id). */
define( 'ARSENAL_SETTINGS_PAYMENT_TOOLS_RESULT_PREFIX', 'arsenal_settings_payment_tools_result_' );

/**
 * Nonce action for the one-off maintenance form.
 *
 * @return string
 */
function arsenal_settings_payment_tools_nonce_action() {
	return 'arsenal_settings_payment_log_tools_run';
}

/**
 * Run payment-log maintenance (same steps as the 2-hour cron, with configurable limits).
 *
 * @param array $args {
 *     @type int  $failed_days       Look-back days for failed WooCommerce ARMember rows.
 *     @type int  $failed_limit      Max failed rows to process.
 *     @type int  $backfill_days     Look-back days for payment-date backfill.
 *     @type int  $dedupe_days       Look-back days for duplicate success log removal.
 *     @type int  $order_limit       Max WooCommerce renewal orders to sync.
 *     @type bool $repair_failed     Repair failed rows from Stripe.
 *     @type bool $backfill_dates    Backfill repaired payment dates.
 *     @type bool $dedupe_success    Remove duplicate success rows.
 *     @type bool $sync_wc_orders    Sync WPS Stripe renewal orders to ARMember.
 * }
 * @return array Summary for admin display.
 */
function arsenal_settings_run_payment_log_maintenance( array $args = array() ) {
	global $wpdb;

	$defaults = array(
		'failed_days'    => 30,
		'failed_limit'   => 100,
		'backfill_days'  => 14,
		'dedupe_days'    => 30,
		'order_limit'    => 50,
		'repair_failed'  => true,
		'backfill_dates' => true,
		'dedupe_success' => true,
		'sync_wc_orders' => false,
	);
	$args     = wp_parse_args( $args, $defaults );

	$started = microtime( true );
	$result  = array(
		'started_at'              => current_time( 'mysql' ),
		'options'                 => $args,
		'orders_checked'          => 0,
		'total_inserted'          => 0,
		'failed_logs_checked'     => 0,
		'failed_logs_repaired'    => 0,
		'payment_dates_backfilled' => 0,
		'duplicate_logs_removed'  => 0,
		'repair_details'          => array(),
		'errors'                  => array(),
		'duration_ms'             => 0,
	);

	$table = function_exists( 'arsenal_settings_get_armember_payment_log_table' )
		? arsenal_settings_get_armember_payment_log_table()
		: '';
	if ( '' === $table ) {
		$result['errors'][] = __( 'ARMember payment log table is not available.', 'arsenal-settings' );
		$result['duration_ms'] = (int) round( 1000 * ( microtime( true ) - $started ) );
		return $result;
	}

	if ( ! empty( $args['sync_wc_orders'] ) ) {
		if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'arsenal_settings_sync_wc_stripe_order_to_arm_payment_log' ) ) {
			$result['errors'][] = __( 'WooCommerce is not loaded; order sync was skipped.', 'arsenal-settings' );
		} else {
			$order_limit = max( 1, min( 200, (int) $args['order_limit'] ) );
			$orders      = wc_get_orders(
				array(
					'limit'          => $order_limit,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'status'         => array( 'completed', 'processing', 'wps_renewal', 'pending', 'on-hold' ),
					'payment_method' => 'stripe',
					'meta_query'     => array(
						array(
							'key'   => 'wps_sfw_renewal_order',
							'value' => 'yes',
						),
					),
					'return'         => 'ids',
				)
			);
			if ( ! is_array( $orders ) ) {
				$result['errors'][] = __( 'wc_get_orders returned an invalid result.', 'arsenal-settings' );
			} else {
				$result['orders_checked'] = count( $orders );
				foreach ( $orders as $order_id ) {
					$inserted = arsenal_settings_sync_wc_stripe_order_to_arm_payment_log( (int) $order_id, true );
					$result['total_inserted'] += (int) $inserted;
				}
			}
		}
	}

	if ( ! empty( $args['repair_failed'] ) && function_exists( 'arsenal_settings_repair_failed_arm_woocommerce_log_from_stripe' ) ) {
		$failed_days  = max( 1, min( 90, (int) $args['failed_days'] ) );
		$failed_limit = max( 1, min( 500, (int) $args['failed_limit'] ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from ARMember helper.
		$failed_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `arm_payment_gateway` = %s AND `arm_transaction_status` = %s AND `arm_created_date` >= %s ORDER BY `arm_log_id` DESC LIMIT %d",
				'woocommerce',
				'failed',
				gmdate( 'Y-m-d H:i:s', strtotime( '-' . $failed_days . ' days' ) ),
				$failed_limit
			),
			ARRAY_A
		);

		$result['failed_logs_checked'] = is_array( $failed_rows ) ? count( $failed_rows ) : 0;
		if ( is_array( $failed_rows ) ) {
			foreach ( $failed_rows as $failed_row ) {
				$log_id  = isset( $failed_row['arm_log_id'] ) ? (int) $failed_row['arm_log_id'] : 0;
				$user_id = isset( $failed_row['arm_user_id'] ) ? (int) $failed_row['arm_user_id'] : 0;
				$plan_id = isset( $failed_row['arm_plan_id'] ) ? (int) $failed_row['arm_plan_id'] : 0;

				$repaired = arsenal_settings_repair_failed_arm_woocommerce_log_from_stripe( $failed_row );
				if ( $repaired ) {
					$result['failed_logs_repaired']++;
					$result['repair_details'][] = array(
						'arm_log_id' => $log_id,
						'user_id'    => $user_id,
						'plan_id'    => $plan_id,
						'status'     => 'repaired',
					);
				} else {
					$result['repair_details'][] = array(
						'arm_log_id' => $log_id,
						'user_id'    => $user_id,
						'plan_id'    => $plan_id,
						'status'     => 'skipped',
					);
				}
			}
		}
	}

	if ( ! empty( $args['backfill_dates'] ) && function_exists( 'arsenal_settings_backfill_repaired_arm_payment_log_dates' ) ) {
		$backfill_days = max( 1, min( 90, (int) $args['backfill_days'] ) );
		$result['payment_dates_backfilled'] = arsenal_settings_backfill_repaired_arm_payment_log_dates( $backfill_days );
	}

	if ( ! empty( $args['dedupe_success'] ) && function_exists( 'arsenal_settings_arm_dedupe_duplicate_wc_success_payment_logs' ) ) {
		$dedupe_days = max( 1, min( 90, (int) $args['dedupe_days'] ) );
		$result['duplicate_logs_removed'] = arsenal_settings_arm_dedupe_duplicate_wc_success_payment_logs( $dedupe_days );
	}

	$result['duration_ms'] = (int) round( 1000 * ( microtime( true ) - $started ) );

	if ( function_exists( 'arsenal_settings_wc_stripe_arm_cron_log' ) ) {
		arsenal_settings_wc_stripe_arm_cron_log(
			'admin_payment_tools_complete',
			array(
				'orders_checked'           => $result['orders_checked'],
				'total_inserted'           => $result['total_inserted'],
				'failed_logs_checked'      => $result['failed_logs_checked'],
				'failed_logs_repaired'     => $result['failed_logs_repaired'],
				'payment_dates_backfilled' => $result['payment_dates_backfilled'],
				'duplicate_logs_removed'   => $result['duplicate_logs_removed'],
				'duration_ms'              => $result['duration_ms'],
				'user_id'                  => get_current_user_id(),
			)
		);
	}

	return $result;
}

/**
 * Handle POST from the maintenance form.
 */
function arsenal_settings_handle_payment_log_tools_run() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run this tool.', 'arsenal-settings' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( arsenal_settings_payment_tools_nonce_action() );

	$args = array(
		'failed_days'    => isset( $_POST['failed_days'] ) ? (int) $_POST['failed_days'] : 30,
		'failed_limit'   => isset( $_POST['failed_limit'] ) ? (int) $_POST['failed_limit'] : 100,
		'backfill_days'  => isset( $_POST['backfill_days'] ) ? (int) $_POST['backfill_days'] : 14,
		'dedupe_days'    => isset( $_POST['dedupe_days'] ) ? (int) $_POST['dedupe_days'] : 30,
		'order_limit'    => isset( $_POST['order_limit'] ) ? (int) $_POST['order_limit'] : 50,
		'repair_failed'  => ! empty( $_POST['repair_failed'] ),
		'backfill_dates' => ! empty( $_POST['backfill_dates'] ),
		'dedupe_success' => ! empty( $_POST['dedupe_success'] ),
		'sync_wc_orders' => ! empty( $_POST['sync_wc_orders'] ),
	);

	$result = arsenal_settings_run_payment_log_maintenance( $args );

	set_transient(
		ARSENAL_SETTINGS_PAYMENT_TOOLS_RESULT_PREFIX . get_current_user_id(),
		$result,
		15 * MINUTE_IN_SECONDS
	);

	wp_safe_redirect(
		add_query_arg(
			'arsenal_payment_tools_ran',
			'1',
			arsenal_settings_admin_page_url( ARSENAL_SETTINGS_PAYMENT_TOOLS_PAGE )
		)
	);
	exit;
}
add_action( 'admin_post_arsenal_settings_run_payment_log_tools', 'arsenal_settings_handle_payment_log_tools_run' );

/**
 * Register submenu under Dashboard → Arsenal.
 */
function arsenal_settings_register_payment_log_tools_menu() {
	add_submenu_page(
		'arsenal-settings',
		__( 'Payment log tools', 'arsenal-settings' ),
		__( 'Payment log tools', 'arsenal-settings' ),
		'manage_options',
		ARSENAL_SETTINGS_PAYMENT_TOOLS_PAGE,
		'arsenal_settings_render_payment_log_tools_page'
	);
}
add_action( 'admin_menu', 'arsenal_settings_register_payment_log_tools_menu', 12 );

/**
 * Render the one-off maintenance page.
 */
function arsenal_settings_render_payment_log_tools_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$result = null;
	if ( isset( $_GET['arsenal_payment_tools_ran'] ) && '1' === (string) wp_unslash( $_GET['arsenal_payment_tools_ran'] ) ) {
		$result = get_transient( ARSENAL_SETTINGS_PAYMENT_TOOLS_RESULT_PREFIX . get_current_user_id() );
		if ( is_array( $result ) ) {
			delete_transient( ARSENAL_SETTINGS_PAYMENT_TOOLS_RESULT_PREFIX . get_current_user_id() );
		}
	}

	$action_url = admin_url( 'admin-post.php' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Payment log tools', 'arsenal-settings' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Run the same maintenance steps as the scheduled cron manually: repair failed WooCommerce payment logs from Stripe, backfill payment dates, and remove duplicate success rows. Use after fixing dedupe scoring or when members are missing payment history.', 'arsenal-settings' ); ?>
		</p>

		<p>
			<a href="<?php echo esc_url( arsenal_settings_admin_page_url( 'arsenal-settings' ) ); ?>"><?php esc_html_e( 'REST API key', 'arsenal-settings' ); ?></a>
			|
			<a href="<?php echo esc_url( arsenal_settings_admin_page_url( 'arsenal-settings-stripe' ) ); ?>"><?php esc_html_e( 'Stripe', 'arsenal-settings' ); ?></a>
			|
			<a href="<?php echo esc_url( arsenal_settings_admin_page_url( 'arsenal-settings-api-logs' ) ); ?>"><?php esc_html_e( 'API logs', 'arsenal-settings' ); ?></a>
			|
			<a href="<?php echo esc_url( arsenal_settings_admin_page_url( 'arsenal-settings-wc-dd-failures' ) ); ?>"><?php esc_html_e( 'DD payment failures', 'arsenal-settings' ); ?></a>
		</p>

		<?php if ( is_array( $result ) ) : ?>
			<div class="notice notice-success">
				<p><strong><?php esc_html_e( 'Maintenance run completed.', 'arsenal-settings' ); ?></strong>
					<?php
					printf(
						/* translators: %d: duration in milliseconds */
						esc_html__( 'Duration: %d ms.', 'arsenal-settings' ),
						isset( $result['duration_ms'] ) ? (int) $result['duration_ms'] : 0
					);
					?>
				</p>
			</div>
			<table class="widefat striped" style="max-width:720px;margin:12px 0;">
				<tbody>
					<tr><th scope="row"><?php esc_html_e( 'WooCommerce orders checked', 'arsenal-settings' ); ?></th><td><?php echo (int) ( $result['orders_checked'] ?? 0 ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Payment logs inserted from orders', 'arsenal-settings' ); ?></th><td><?php echo (int) ( $result['total_inserted'] ?? 0 ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Failed logs checked', 'arsenal-settings' ); ?></th><td><?php echo (int) ( $result['failed_logs_checked'] ?? 0 ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Failed logs repaired', 'arsenal-settings' ); ?></th><td><?php echo (int) ( $result['failed_logs_repaired'] ?? 0 ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Payment dates backfilled', 'arsenal-settings' ); ?></th><td><?php echo (int) ( $result['payment_dates_backfilled'] ?? 0 ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Duplicate success logs removed', 'arsenal-settings' ); ?></th><td><?php echo (int) ( $result['duplicate_logs_removed'] ?? 0 ); ?></td></tr>
				</tbody>
			</table>
			<?php if ( ! empty( $result['errors'] ) ) : ?>
				<div class="notice notice-warning inline">
					<ul>
						<?php foreach ( $result['errors'] as $err ) : ?>
							<li><?php echo esc_html( (string) $err ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $result['repair_details'] ) ) : ?>
				<h2><?php esc_html_e( 'Failed log repair details', 'arsenal-settings' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Log ID', 'arsenal-settings' ); ?></th>
							<th><?php esc_html_e( 'User ID', 'arsenal-settings' ); ?></th>
							<th><?php esc_html_e( 'Plan ID', 'arsenal-settings' ); ?></th>
							<th><?php esc_html_e( 'Result', 'arsenal-settings' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $result['repair_details'] as $row ) : ?>
							<tr>
								<td><?php echo (int) ( $row['arm_log_id'] ?? 0 ); ?></td>
								<td><?php echo (int) ( $row['user_id'] ?? 0 ); ?></td>
								<td><?php echo (int) ( $row['plan_id'] ?? 0 ); ?></td>
								<td>
									<?php
									$status = isset( $row['status'] ) ? (string) $row['status'] : '';
									if ( 'repaired' === $status ) {
										echo '<span style="color:#00a32a;">' . esc_html__( 'Repaired', 'arsenal-settings' ) . '</span>';
									} else {
										echo '<span style="color:#996800;">' . esc_html__( 'Skipped (no Stripe match)', 'arsenal-settings' ) . '</span>';
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description"><?php esc_html_e( 'Skipped rows remain failed in ARMember until Stripe shows a paid subscription that matches plan/amount, or you fix them manually.', 'arsenal-settings' ); ?></p>
			<?php endif; ?>
			<hr />
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( $action_url ); ?>">
			<input type="hidden" name="action" value="arsenal_settings_run_payment_log_tools" />
			<?php wp_nonce_field( arsenal_settings_payment_tools_nonce_action() ); ?>

			<h2><?php esc_html_e( 'Steps to run', 'arsenal-settings' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Actions', 'arsenal-settings' ); ?></th>
						<td>
							<label><input type="checkbox" name="repair_failed" value="1" checked="checked" /> <?php esc_html_e( 'Repair failed WooCommerce payment logs from Stripe', 'arsenal-settings' ); ?></label><br />
							<label><input type="checkbox" name="backfill_dates" value="1" checked="checked" /> <?php esc_html_e( 'Backfill payment dates on “Paid By system” success rows', 'arsenal-settings' ); ?></label><br />
							<label><input type="checkbox" name="dedupe_success" value="1" checked="checked" /> <?php esc_html_e( 'Remove duplicate success payment logs', 'arsenal-settings' ); ?></label><br />
							<label><input type="checkbox" name="sync_wc_orders" value="1" /> <?php esc_html_e( 'Sync WPS Stripe renewal orders to ARMember (optional)', 'arsenal-settings' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="failed_days"><?php esc_html_e( 'Failed logs look-back (days)', 'arsenal-settings' ); ?></label></th>
						<td><input name="failed_days" id="failed_days" type="number" min="1" max="90" value="30" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="failed_limit"><?php esc_html_e( 'Max failed logs to process', 'arsenal-settings' ); ?></label></th>
						<td><input name="failed_limit" id="failed_limit" type="number" min="1" max="500" value="100" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="backfill_days"><?php esc_html_e( 'Date backfill look-back (days)', 'arsenal-settings' ); ?></label></th>
						<td><input name="backfill_days" id="backfill_days" type="number" min="1" max="90" value="14" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="dedupe_days"><?php esc_html_e( 'Dedupe look-back (days)', 'arsenal-settings' ); ?></label></th>
						<td><input name="dedupe_days" id="dedupe_days" type="number" min="1" max="90" value="30" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="order_limit"><?php esc_html_e( 'Max renewal orders (if syncing)', 'arsenal-settings' ); ?></label></th>
						<td><input name="order_limit" id="order_limit" type="number" min="1" max="200" value="50" class="small-text" /></td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( __( 'Run maintenance now', 'arsenal-settings' ), 'primary', 'submit', false ); ?>
		</form>

		<p class="description">
			<?php esc_html_e( 'Recommended order: repair failed logs first, then backfill dates, then dedupe. Dedupe now prefers Stripe-repaired rows over older duplicates. Events are also written to the WC Stripe ARM cron log under uploads.', 'arsenal-settings' ); ?>
		</p>
	</div>
	<?php
}
