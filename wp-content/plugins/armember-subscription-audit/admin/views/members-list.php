<?php
/**
 * Members listing view.
 *
 * @var array  $rows, $filters
 * @var int    $total, $pages
 * @var bool   $truncated
 *
 * @package ARMSA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ARMSA\Admin_Menu;
use ARMSA\Payment_History;
use ARMSA\Subscription_Audit;
use ARMSA\Status;
use ARMSA\Sync_Admin;
use ARMSA\Queue;

$base_args = array(
	'page'         => Admin_Menu::SLUG,
	's'            => $filters['search'],
	'gateway'      => $filters['gateway'],
	'mode'         => $filters['mode'],
	'audit_status' => $filters['status'],
	'gw_ready'     => $filters['gw_ready'],
	'arm_ready'    => $filters['arm_ready'],
	'sync'         => $filters['sync'],
);
$base_args = array_filter( $base_args, 'strlen' );

$sort_link = function ( $key, $label ) use ( $base_args, $filters ) {
	$is_cur = $filters['orderby'] === $key;
	$next   = ( $is_cur && 'asc' === $filters['order'] ) ? 'desc' : 'asc';
	$url    = add_query_arg( array_merge( $base_args, array( 'orderby' => $key, 'order' => $next ) ), admin_url( 'admin.php' ) );
	$arrow  = $is_cur ? ( 'asc' === $filters['order'] ? ' ▲' : ' ▼' ) : '';
	return '<a href="' . esc_url( $url ) . '">' . esc_html( $label . $arrow ) . '</a>';
};
$pay_labels = array(
	Payment_History::STATUS_SUCCESS   => 'Success',
	Payment_History::STATUS_FAILED    => 'Failed',
	Payment_History::STATUS_PENDING   => 'Other (pending)',
	Payment_History::STATUS_CANCELLED => 'Other (cancelled)',
	Payment_History::STATUS_UNKNOWN   => 'Other',
	Payment_History::STATUS_NONE      => 'No Record',
);
?>
<div class="wrap armsa-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'ARMember Subscription Audit — Members', 'armember-subscription-audit' ); ?></h1>
	<hr class="wp-header-end">
	<p class="description"><?php esc_html_e( 'Read-only audit. "Subscription Linked" only means a reference is stored in ARMember; it does not confirm the external subscription is active.', 'armember-subscription-audit' ); ?></p>

	<form method="get" class="armsa-filters">
		<input type="hidden" name="page" value="<?php echo esc_attr( Admin_Menu::SLUG ); ?>">
		<label class="screen-reader-text" for="armsa-s"><?php esc_html_e( 'Search members', 'armember-subscription-audit' ); ?></label>
		<input type="search" id="armsa-s" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Name, email or user ID', 'armember-subscription-audit' ); ?>">

		<select name="gateway">
			<option value=""><?php esc_html_e( 'All gateways', 'armember-subscription-audit' ); ?></option>
			<?php foreach ( Admin_Menu::gateway_options() as $k => $l ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filters['gateway'], $k ); ?>><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="mode">
			<option value=""><?php esc_html_e( 'All payment modes', 'armember-subscription-audit' ); ?></option>
			<?php foreach ( Admin_Menu::mode_options() as $k => $l ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filters['mode'], $k ); ?>><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="audit_status">
			<option value=""><?php esc_html_e( 'All audit statuses', 'armember-subscription-audit' ); ?></option>
			<?php foreach ( Subscription_Audit::status_labels() as $k => $l ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filters['status'], $k ); ?>><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="gw_ready">
			<option value=""><?php esc_html_e( 'All gateway readiness', 'armember-subscription-audit' ); ?></option>
			<?php foreach ( Status::gateway_labels() as $k => $l ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filters['gw_ready'], $k ); ?>><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="arm_ready">
			<option value=""><?php esc_html_e( 'All ARMember readiness', 'armember-subscription-audit' ); ?></option>
			<?php foreach ( Status::arm_labels() as $k => $l ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filters['arm_ready'], $k ); ?>><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="sync">
			<option value=""><?php esc_html_e( 'All sync statuses', 'armember-subscription-audit' ); ?></option>
			<?php foreach ( Status::overall_labels() as $k => $l ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filters['sync'], $k ); ?>><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select>

		<?php if ( 'id' !== $filters['orderby'] ) : ?>
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $filters['orderby'] ); ?>">
			<input type="hidden" name="order" value="<?php echo esc_attr( $filters['order'] ); ?>">
		<?php endif; ?>

		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'armember-subscription-audit' ); ?></button>
		<?php if ( count( $base_args ) > 1 ) : ?>
			<a class="button-link" href="<?php echo esc_url( add_query_arg( array( 'page' => Admin_Menu::SLUG ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Reset', 'armember-subscription-audit' ); ?></a>
		<?php endif; ?>
	</form>

	<?php if ( $truncated ) : ?>
		<div class="notice notice-warning inline"><p>
			<?php
			/* translators: %d: number of members scanned. */
			echo esc_html( sprintf( __( 'Gateway / mode / status filters scan members in batches; only the first %d members were scanned. Narrow the search for complete results.', 'armember-subscription-audit' ), \ARMSA\Member_Repository::MAX_SCAN ) );
			?>
		</p></div>
	<?php endif; ?>

	<p class="armsa-count">
		<?php
		/* translators: %s: number of rows. */
		echo esc_html( sprintf( _n( '%s membership row', '%s membership rows', $total, 'armember-subscription-audit' ), number_format_i18n( $total ) ) );
		?>
	</p>

	<?php
	$qc = Queue::counts();
	?>
	<p class="description armsa-queue">
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: queued 2: processing 3: failed */
				__( 'Background queue — queued: %1$d, processing: %2$d, failed: %3$d. Lists show the last stored assessment; no gateway is contacted while browsing.', 'armember-subscription-audit' ),
				isset( $qc['queued'] ) ? $qc['queued'] : 0,
				isset( $qc['processing'] ) ? $qc['processing'] : 0,
				isset( $qc['failed'] ) ? $qc['failed'] : 0
			)
		);
		?>
	</p>

	<?php
	$opt_cols = Sync_Admin::optional_columns();
	$hidden   = Sync_Admin::hidden_columns();
	if ( $hidden ) {
		echo '<style>' . esc_html( implode( ',', array_map( function ( $k ) {
			return '.armsa-table .armsa-c-' . $k;
		}, $hidden ) ) ) . '{display:none}</style>';
	}
	?>
	<details class="armsa-colpicker">
		<summary class="button"><?php esc_html_e( 'Columns', 'armember-subscription-audit' ); ?></summary>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( Sync_Admin::NONCE ); ?>
			<input type="hidden" name="action" value="armsa_columns">
			<p class="description"><?php esc_html_e( 'Member ID, Email and Action are always shown. Your choice is saved for your account.', 'armember-subscription-audit' ); ?></p>
			<?php foreach ( $opt_cols as $ck => $cl ) : ?>
				<label><input type="checkbox" name="show_cols[]" value="<?php echo esc_attr( $ck ); ?>" <?php checked( ! in_array( $ck, $hidden, true ) ); ?> onchange="jQuery('.armsa-table .armsa-c-<?php echo esc_js( $ck ); ?>').toggle(this.checked);"> <?php echo esc_html( $cl ); ?></label>
			<?php endforeach; ?>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save columns', 'armember-subscription-audit' ); ?></button>
			<button type="submit" name="reset_cols" value="1" class="button"><?php esc_html_e( 'Show all', 'armember-subscription-audit' ); ?></button>
		</form>
	</details>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="armsa-bulk">
		<?php wp_nonce_field( Sync_Admin::NONCE ); ?>
		<input type="hidden" name="action" value="armsa_bulk" id="armsa-bulk-action-hook">
		<input type="hidden" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>">
		<input type="hidden" name="gateway" value="<?php echo esc_attr( $filters['gateway'] ); ?>">
		<input type="hidden" name="mode" value="<?php echo esc_attr( $filters['mode'] ); ?>">
		<input type="hidden" name="audit_status" value="<?php echo esc_attr( $filters['status'] ); ?>">
		<input type="hidden" name="gw_ready" value="<?php echo esc_attr( $filters['gw_ready'] ); ?>">
		<input type="hidden" name="arm_ready" value="<?php echo esc_attr( $filters['arm_ready'] ); ?>">
		<input type="hidden" name="sync" value="<?php echo esc_attr( $filters['sync'] ); ?>">
		<div class="tablenav top">
			<select name="bulk_action">
				<option value=""><?php esc_html_e( 'Bulk actions…', 'armember-subscription-audit' ); ?></option>
				<option value="assess"><?php esc_html_e( 'Assess / recheck gateway information (queued)', 'armember-subscription-audit' ); ?></option>
				<option value="queue_sync"><?php esc_html_e( 'Queue sync (ready members only)', 'armember-subscription-audit' ); ?></option>
				<option value="retry_failed"><?php esc_html_e( 'Retry failed jobs', 'armember-subscription-audit' ); ?></option>
			</select>
			<select name="bulk_scope">
				<option value="selected"><?php esc_html_e( 'Selected rows', 'armember-subscription-audit' ); ?></option>
				<option value="filtered"><?php esc_html_e( 'All filtered members (max 2000)', 'armember-subscription-audit' ); ?></option>
			</select>
			<button type="submit" class="button" onclick="document.getElementById('armsa-bulk-action-hook').value='armsa_bulk';"><?php esc_html_e( 'Apply', 'armember-subscription-audit' ); ?></button>
			<button type="submit" class="button" onclick="document.getElementById('armsa-bulk-action-hook').value='armsa_export';"><?php esc_html_e( 'Export members with missing information (CSV)', 'armember-subscription-audit' ); ?></button>
			<span class="description"><?php esc_html_e( 'Unsafe records (missing/ambiguous/inactive/unsupported/manual) are never queued for sync, whatever is selected.', 'armember-subscription-audit' ); ?></span>
		</div>
	<div class="armsa-scroll">
	<table class="wp-list-table widefat fixed striped armsa-table">
		<thead>
			<tr>
				<td class="check-column"><input type="checkbox" onclick="jQuery('.armsa-sel').prop('checked', this.checked);"></td>
				<th class="column-id"><?php echo $sort_link( 'id', __( 'Member ID', 'armember-subscription-audit' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
				<th class="armsa-c-name"><?php esc_html_e( 'Member Name', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Email', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-plan"><?php esc_html_e( 'Membership Plan', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-gateway"><?php esc_html_e( 'Gateway', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-method"><?php esc_html_e( 'Selected Payment Method', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-mode"><?php esc_html_e( 'Payment Mode', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-ref"><?php esc_html_e( 'Subscription Reference', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-lastdate"><?php echo $sort_link( 'last_payment', __( 'Last Payment Date', 'armember-subscription-audit' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
				<th class="armsa-c-amount"><?php esc_html_e( 'Last Payment Amount', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-paystatus"><?php esc_html_e( 'Last Payment Status', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-audit"><?php esc_html_e( 'Audit Status', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-gwready"><?php esc_html_e( 'Gateway Readiness', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-armready"><?php esc_html_e( 'ARMember Readiness', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-sync"><?php esc_html_e( 'Sync Status', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-missing"><?php esc_html_e( 'Missing Information', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-assessed"><?php esc_html_e( 'Last Assessment', 'armember-subscription-audit' ); ?></th>
				<th class="armsa-c-synced"><?php esc_html_e( 'Last Sync', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Action', 'armember-subscription-audit' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( ! $rows ) : ?>
			<tr><td colspan="20"><?php esc_html_e( 'No ARMember members match your criteria.', 'armember-subscription-audit' ); ?></td></tr>
		<?php endif; ?>
		<?php
		foreach ( $rows as $row ) :
			$a      = $row['audit'];
			$latest = $row['pay']['latest'];
			$date   = $latest ? Payment_History::effective_date( $latest ) : '';
			$amount = ( $latest && isset( $latest['arm_amount'] ) ) ? trim( number_format_i18n( (float) $latest['arm_amount'], 2 ) . ' ' . ( isset( $latest['arm_currency'] ) ? $latest['arm_currency'] : '' ) ) : '—';
			?>
			<tr>
				<th scope="row" class="check-column"><input type="checkbox" class="armsa-sel" name="selected[]" value="<?php echo esc_attr( $row['user']['id'] . ':' . $row['plan_id'] ); ?>"></th>
					<td><?php echo esc_html( $row['user']['id'] ); ?></td>
				<td class="armsa-c-name"><?php echo esc_html( $row['user']['name'] ); ?></td>
				<td><?php echo esc_html( $row['user']['email'] ); ?></td>
				<td class="armsa-c-plan"><?php echo esc_html( $row['plan_name'] ); ?> <?php $pt_l = \ARMSA\Assessor::payment_type( array( 'plan_kind' => $row['plan_kind'], 'meta' => array( 'mode' => $row['meta']['mode'] ) ) ); ?><span class="armsa-p2 armsa-p2-<?php echo 'recurring' === $pt_l['type'] ? 'gw_verified' : 'gw_not_assessed'; ?>"><?php echo esc_html( $pt_l['label'] ); ?></span></td>
				<td class="armsa-c-gateway"><?php echo esc_html( Subscription_Audit::gateway_label( $a['gateway'] ) ); ?></td>
				<td class="armsa-c-method"><?php echo '' !== $row['meta']['selected_payment_method'] ? esc_html( $row['meta']['selected_payment_method'] ) : '—'; ?></td>
				<td class="armsa-c-mode"><?php echo esc_html( $a['is_recurring'] || '' !== $a['mode'] ? Subscription_Audit::mode_label( $a['mode'] ) : '—' ); ?></td>
				<td class="armsa-c-ref"><?php echo '' !== $a['reference'] ? '<code>' . esc_html( $a['reference'] ) . '</code>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<td class="armsa-c-lastdate"><?php echo $date ? esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date ) ) : '—'; ?></td>
				<td class="armsa-c-amount"><?php echo esc_html( $amount ); ?></td>
				<td class="armsa-c-paystatus"><span class="armsa-pay armsa-pay-<?php echo esc_attr( $a['latest_status'] ); ?>"><?php echo esc_html( $pay_labels[ $a['latest_status'] ] ); ?></span></td>
				<td class="armsa-c-audit"><span class="armsa-badge armsa-badge-<?php echo esc_attr( $a['status'] ); ?>"><?php echo esc_html( $a['label'] ); ?></span></td>
				<?php
					$p2    = $row['p2'];
					$when  = function ( $v ) {
						return $v ? esc_html( get_date_from_gmt( $v, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) : '—';
					};
					$eff   = $p2['effective'];
					$short = $p2['missing'] ? implode( '; ', array_slice( $p2['missing'], 0, 3 ) ) : '';
					?>
					<td class="armsa-c-gwready"><span class="armsa-p2 armsa-p2-<?php echo esc_attr( $p2['gateway_status'] ); ?>"><?php echo esc_html( Status::label( 'gateway', $p2['gateway_status'] ) ); ?></span></td>
					<td class="armsa-c-armready"><span class="armsa-p2 armsa-p2-<?php echo esc_attr( $p2['arm_status'] ); ?>"><?php echo esc_html( Status::label( 'arm', $p2['arm_status'] ) ); ?></span></td>
					<td class="armsa-c-sync"><span class="armsa-p2 armsa-p2-<?php echo esc_attr( $eff ); ?>"><?php echo esc_html( Status::label( 'overall', $eff ) ); ?></span></td>
					<td class="armsa-c-missing"><?php echo '' !== $short ? esc_html( $short ) : '—'; ?></td>
					<td class="armsa-c-assessed"><?php echo $when( $p2['assessed_at'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in closure. ?></td>
					<td class="armsa-c-synced"><?php echo $when( $p2['synced_at'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in closure. ?></td>
					<td><a class="button button-small" href="<?php echo esc_url( Admin_Menu::details_url( $row['user']['id'] ) ); ?>"><?php echo esc_html( Status::OV_READY === $eff ? __( 'Review & Sync', 'armember-subscription-audit' ) : ( $p2['has'] ? __( 'View Details', 'armember-subscription-audit' ) : ( 'recurring' === $pt_l['type'] ? __( 'Assess', 'armember-subscription-audit' ) : __( 'View Details', 'armember-subscription-audit' ) ) ) ); ?></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>
	</form>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav bottom"><div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( array_merge( $base_args, array( 'orderby' => $filters['orderby'], 'order' => $filters['order'], 'paged' => '%#%' ) ), admin_url( 'admin.php' ) ),
						'format'    => '',
						'current'   => $filters['page'],
						'total'     => $pages,
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
					)
				)
			);
			?>
		</div></div>
	<?php endif; ?>
</div>
