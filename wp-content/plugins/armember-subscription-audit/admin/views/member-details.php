<?php
/**
 * Member details view (read-only).
 *
 * @var array  $rows    One audit row per plan.
 * @var array  $history Payment log rows, newest first.
 * @var array  $user
 * @var string $back_url
 *
 * @package ARMSA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ARMSA\Payment_History;
use ARMSA\Subscription_Audit;

$fmt_date = function ( $value ) {
	return $value ? mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $value ) : '—';
};
$show = function ( $value ) {
	if ( is_array( $value ) ) {
		return $value ? wp_json_encode( $value ) : '—';
	}
	return '' === trim( (string) $value ) ? '—' : (string) $value;
};
// ARMember stores next due / dates as Unix timestamps in plan meta.
$ts_date = function ( $value ) use ( $fmt_date ) {
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $value );
	}
	return '' === trim( (string) $value ) ? '—' : $fmt_date( (string) $value );
};
?>
<div class="wrap armsa-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Member Audit Details', 'armember-subscription-audit' ); ?></h1>
	<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action"><?php esc_html_e( '← Back to members', 'armember-subscription-audit' ); ?></a>
	<hr class="wp-header-end">

	<h2><?php esc_html_e( 'Member Information', 'armember-subscription-audit' ); ?></h2>
	<table class="widefat striped armsa-kv"><tbody>
		<tr><th><?php esc_html_e( 'WordPress user ID', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $user['id'] ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Name', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $user['name'] ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Email', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $user['email'] ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Registration date', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $fmt_date( $user['registered'] ) ); ?></td></tr>
	</tbody></table>

	<?php foreach ( $rows as $row ) :
		$a = $row['audit'];
		$m = $row['meta'];
		$s = is_array( $m['stripe'] ) ? $m['stripe'] : array();
		?>
		<h2>
			<?php
			/* translators: %s: plan name */
			echo esc_html( sprintf( __( 'Membership: %s', 'armember-subscription-audit' ), $row['plan_name'] ) );
			?>
			<span class="armsa-badge armsa-badge-<?php echo esc_attr( $a['status'] ); ?>"><?php echo esc_html( $a['label'] ); ?></span>
		</h2>

		<h3><?php esc_html_e( 'Membership Information', 'armember-subscription-audit' ); ?></h3>
		<table class="widefat striped armsa-kv"><tbody>
			<tr><th><?php esc_html_e( 'ARMember plan ID', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $row['plan_id'] ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Plan name', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $row['plan_name'] ); ?> <em>(<?php echo esc_html( $row['plan_kind'] ); ?>)</em></td></tr>
			<tr><th><?php esc_html_e( 'Gateway', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( Subscription_Audit::gateway_label( $a['gateway'] ) . ( '' !== $a['gateway'] ? ' (' . $a['gateway'] . ')' : '' ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Payment mode', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( Subscription_Audit::mode_label( $a['mode'] ) . ( '' !== $a['mode'] ? ' (' . $a['mode'] . ')' : '' ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Detected model', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $show( $a['model'] ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Payment cycle', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $show( $m['cycle'] ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Completed recurring count', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $show( $m['completed'] ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Next due payment', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $ts_date( $m['next_due'] ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Subscription ID (arm_subscr_id)', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $show( $m['subscr_id'] ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Reference used by audit', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( '' !== $a['reference'] ? $a['reference'] . ' — ' . $a['reference_source'] : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Stripe metadata (arm_stripe)', 'armember-subscription-audit' ); ?></th><td>
				<?php
				if ( $s ) {
					foreach ( $s as $k => $v ) {
						echo esc_html( $k . ': ' . ( is_scalar( $v ) ? $v : wp_json_encode( $v ) ) ) . '<br>';
					}
				} else {
					echo '—';
				}
				?>
			</td></tr>
			<tr><th><?php esc_html_e( 'Cancellation status', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( ! empty( $m['cancelled'] ) ? 'Cancelled (' . $show( $m['cancelled'] ) . ')' : 'Not cancelled' ); ?></td></tr>
		</tbody></table>

		<h3><?php esc_html_e( 'Audit Summary', 'armember-subscription-audit' ); ?></h3>
		<div class="armsa-summary">
			<p><strong><?php esc_html_e( 'Overall status:', 'armember-subscription-audit' ); ?></strong>
				<span class="armsa-badge armsa-badge-<?php echo esc_attr( $a['status'] ); ?>"><?php echo esc_html( $a['label'] ); ?></span></p>
			<?php
			$groups = array(
				__( 'Reasons', 'armember-subscription-audit' )          => $a['reasons'],
				__( 'Missing fields', 'armember-subscription-audit' )   => $a['missing'],
				__( 'Inconsistencies', 'armember-subscription-audit' )  => $a['inconsistencies'],
				__( 'Notes', 'armember-subscription-audit' )            => $a['notes'],
			);
			foreach ( $groups as $title => $items ) :
				?>
				<div class="armsa-group"><strong><?php echo esc_html( $title ); ?>:</strong>
				<?php if ( ! $items ) : ?>
					<?php esc_html_e( 'None', 'armember-subscription-audit' ); ?>
				<?php else : ?>
					<ul class="armsa-list">
					<?php foreach ( $items as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>

	<h2><?php esc_html_e( 'Payment History (latest first)', 'armember-subscription-audit' ); ?></h2>
	<table class="wp-list-table widefat fixed striped armsa-table">
		<thead><tr>
			<th><?php esc_html_e( 'Log ID', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Plan ID', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Gateway', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Payment type', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Transaction ID', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Token / reference', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Status', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Amount', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Currency', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Payment date', 'armember-subscription-audit' ); ?></th>
		</tr></thead>
		<tbody>
		<?php if ( ! $history ) : ?>
			<tr><td colspan="10"><?php esc_html_e( 'No payment log records for this member.', 'armember-subscription-audit' ); ?></td></tr>
		<?php endif; ?>
		<?php foreach ( $history as $h ) :
			$norm = Payment_History::normalize_status( isset( $h['arm_transaction_status'] ) ? $h['arm_transaction_status'] : '' );
			$get  = function ( $k ) use ( $h ) {
				return isset( $h[ $k ] ) && '' !== (string) $h[ $k ] ? (string) $h[ $k ] : '—';
			};
			$d = Payment_History::effective_date( $h );
			?>
			<tr>
				<td><?php echo esc_html( $get( 'arm_log_id' ) ); ?></td>
				<td><?php echo esc_html( $get( 'arm_plan_id' ) ); ?></td>
				<td><?php echo esc_html( $get( 'arm_payment_gateway' ) ); ?></td>
				<td><?php echo esc_html( $get( 'arm_payment_type' ) ); ?></td>
				<td class="armsa-break"><?php echo esc_html( $get( 'arm_transaction_id' ) ); ?></td>
				<td class="armsa-break"><?php echo esc_html( $get( 'arm_token' ) ); ?></td>
				<td><span class="armsa-pay armsa-pay-<?php echo esc_attr( $norm ); ?>"><?php echo esc_html( $get( 'arm_transaction_status' ) ); ?></span></td>
				<td><?php echo esc_html( isset( $h['arm_amount'] ) ? number_format_i18n( (float) $h['arm_amount'], 2 ) : '—' ); ?></td>
				<td><?php echo esc_html( $get( 'arm_currency' ) ); ?></td>
				<td><?php echo esc_html( $fmt_date( $d ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
