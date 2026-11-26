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

$base_args = array(
	'page'         => Admin_Menu::SLUG,
	's'            => $filters['search'],
	'gateway'      => $filters['gateway'],
	'mode'         => $filters['mode'],
	'audit_status' => $filters['status'],
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

	<table class="wp-list-table widefat fixed striped armsa-table">
		<thead>
			<tr>
				<th class="column-id"><?php echo $sort_link( 'id', __( 'Member ID', 'armember-subscription-audit' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
				<th><?php esc_html_e( 'Member Name', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Email', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Membership Plan', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Gateway', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Payment Mode', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Subscription Reference', 'armember-subscription-audit' ); ?></th>
				<th><?php echo $sort_link( 'last_payment', __( 'Last Payment Date', 'armember-subscription-audit' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
				<th><?php esc_html_e( 'Last Payment Amount', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Last Payment Status', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Audit Status', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Action', 'armember-subscription-audit' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( ! $rows ) : ?>
			<tr><td colspan="12"><?php esc_html_e( 'No ARMember members match your criteria.', 'armember-subscription-audit' ); ?></td></tr>
		<?php endif; ?>
		<?php
		foreach ( $rows as $row ) :
			$a      = $row['audit'];
			$latest = $row['pay']['latest'];
			$date   = $latest ? Payment_History::effective_date( $latest ) : '';
			$amount = ( $latest && isset( $latest['arm_amount'] ) ) ? trim( number_format_i18n( (float) $latest['arm_amount'], 2 ) . ' ' . ( isset( $latest['arm_currency'] ) ? $latest['arm_currency'] : '' ) ) : '—';
			?>
			<tr>
				<td><?php echo esc_html( $row['user']['id'] ); ?></td>
				<td><?php echo esc_html( $row['user']['name'] ); ?></td>
				<td><?php echo esc_html( $row['user']['email'] ); ?></td>
				<td><?php echo esc_html( $row['plan_name'] ); ?></td>
				<td><?php echo esc_html( Subscription_Audit::gateway_label( $a['gateway'] ) ); ?></td>
				<td><?php echo esc_html( $a['is_recurring'] || '' !== $a['mode'] ? Subscription_Audit::mode_label( $a['mode'] ) : '—' ); ?></td>
				<td><?php echo '' !== $a['reference'] ? '<code>' . esc_html( $a['reference'] ) . '</code>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<td><?php echo $date ? esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date ) ) : '—'; ?></td>
				<td><?php echo esc_html( $amount ); ?></td>
				<td><span class="armsa-pay armsa-pay-<?php echo esc_attr( $a['latest_status'] ); ?>"><?php echo esc_html( $pay_labels[ $a['latest_status'] ] ); ?></span></td>
				<td><span class="armsa-badge armsa-badge-<?php echo esc_attr( $a['status'] ); ?>"><?php echo esc_html( $a['label'] ); ?></span></td>
				<td><a class="button button-small" href="<?php echo esc_url( Admin_Menu::details_url( $row['user']['id'] ) ); ?>"><?php esc_html_e( 'View Details', 'armember-subscription-audit' ); ?></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

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
