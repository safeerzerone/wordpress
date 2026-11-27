<?php
/**
 * Phase 2: processing timeline + administrator change audit (append-only data, read-only view).
 *
 * @var array $user
 * @var array $timeline Log rows (newest first).
 * @var array $rows     Phase 1 rows (for plan names).
 *
 * @package ARMSA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$who = function ( $id ) {
	if ( ! (int) $id ) {
		return __( 'Background worker', 'armember-subscription-audit' );
	}
	$u = get_userdata( (int) $id );
	return $u ? $u->display_name . ' (#' . (int) $id . ')' : '#' . (int) $id;
};
$ts  = function ( $gmt ) {
	return get_date_from_gmt( $gmt, get_option( 'date_format' ) . ' H:i:s' );
};
$changes = \ARMSA\Logger::changes( (int) $user['id'], 100 );
?>
<h2><?php esc_html_e( 'Processing Timeline / Audit Logs', 'armember-subscription-audit' ); ?></h2>
<p class="description"><?php esc_html_e( 'Append-only: entries are never edited or deleted. Credentials, tokens and card data are redacted before logging.', 'armember-subscription-audit' ); ?></p>
<div class="armsa-scroll">
<table class="wp-list-table widefat fixed striped armsa-table armsa-timeline">
	<thead><tr>
		<th><?php esc_html_e( 'Timestamp', 'armember-subscription-audit' ); ?></th>
		<th><?php esc_html_e( 'Operation', 'armember-subscription-audit' ); ?></th>
		<th><?php esc_html_e( 'Processing step', 'armember-subscription-audit' ); ?></th>
		<th><?php esc_html_e( 'Gateway', 'armember-subscription-audit' ); ?></th>
		<th><?php esc_html_e( 'Member / plan', 'armember-subscription-audit' ); ?></th>
		<th><?php esc_html_e( 'Status', 'armember-subscription-audit' ); ?></th>
		<th><?php esc_html_e( 'Description', 'armember-subscription-audit' ); ?></th>
		<th><?php esc_html_e( 'Reference', 'armember-subscription-audit' ); ?></th>
		<th><?php esc_html_e( 'Error', 'armember-subscription-audit' ); ?></th>
		<th><?php esc_html_e( 'Initiated by', 'armember-subscription-audit' ); ?></th>
	</tr></thead>
	<tbody>
	<?php if ( ! $timeline ) : ?>
		<tr><td colspan="10"><?php esc_html_e( 'No Phase 2 operation has been recorded for this member yet.', 'armember-subscription-audit' ); ?></td></tr>
	<?php endif; ?>
	<?php foreach ( $timeline as $l ) : ?>
		<tr>
			<td><?php echo esc_html( $ts( $l['created_at'] ) ); ?></td>
			<td><?php echo esc_html( $l['operation'] ); ?></td>
			<td><?php echo esc_html( $l['step'] ); ?></td>
			<td><?php echo esc_html( $l['gateway'] ); ?></td>
			<td><?php echo esc_html( '#' . (int) $l['user_id'] . ' / plan ' . (int) $l['plan_id'] ); ?></td>
			<td><span class="armsa-step armsa-step-<?php echo esc_attr( $l['status'] ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $l['status'] ) ) ); ?></span></td>
			<td class="armsa-break"><?php echo esc_html( $l['description'] ); ?></td>
			<td class="armsa-break"><?php echo esc_html( $l['ref'] ); ?></td>
			<td class="armsa-break"><?php echo esc_html( $l['error_detail'] ); ?></td>
			<td><?php echo esc_html( $who( $l['initiated_by'] ) ); ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
</div>

<h3><?php esc_html_e( 'Administrator changes (before / after)', 'armember-subscription-audit' ); ?></h3>
<table class="widefat striped armsa-table">
	<thead><tr><th><?php esc_html_e( 'Timestamp', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Administrator', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Action', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Field', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Original value', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Updated value', 'armember-subscription-audit' ); ?></th></tr></thead>
	<tbody>
	<?php if ( ! $changes ) : ?>
		<tr><td colspan="6"><?php esc_html_e( 'No changes recorded.', 'armember-subscription-audit' ); ?></td></tr>
	<?php endif; ?>
	<?php foreach ( $changes as $c ) : ?>
		<tr>
			<td><?php echo esc_html( $ts( $c['created_at'] ) ); ?></td>
			<td><?php echo esc_html( $who( $c['admin_id'] ) ); ?></td>
			<td><?php echo esc_html( $c['action'] ); ?></td>
			<td class="armsa-break"><?php echo esc_html( $c['field_key'] ); ?></td>
			<td class="armsa-break"><?php echo '' === (string) $c['old_value'] ? '—' : esc_html( $c['old_value'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
			<td class="armsa-break"><?php echo '' === (string) $c['new_value'] ? '—' : esc_html( $c['new_value'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
