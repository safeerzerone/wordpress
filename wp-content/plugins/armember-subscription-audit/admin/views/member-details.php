<?php
/**
 * Member details view – tabbed layout (Overview · Gateway Sync · Payment History · Timeline).
 *
 * @var array  $rows     One audit row per plan.
 * @var array  $history  Payment log rows, newest first.
 * @var array  $user
 * @var string $back_url
 * @var array  $p2       Phase 2 data per plan.
 * @var array  $timeline
 *
 * @package ARMSA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ARMSA\Payment_History;
use ARMSA\Status;
use ARMSA\Subscription_Audit;

$dt_fmt   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
$fmt_date = function ( $value ) use ( $dt_fmt ) {
	return $value ? mysql2date( $dt_fmt, $value ) : '—';
};
$show     = function ( $value ) {
	if ( is_array( $value ) ) {
		return $value ? wp_json_encode( $value ) : '—';
	}
	return '' === trim( (string) $value ) ? '—' : (string) $value;
};
$ts_date  = function ( $value ) use ( $fmt_date, $dt_fmt ) {
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		return wp_date( $dt_fmt, (int) $value );
	}
	return '' === trim( (string) $value ) ? '—' : $fmt_date( (string) $value );
};
// One tile of the key-facts grid.
$tile = function ( $label, $value, $code = false ) {
	echo '<div class="armsa-tile"><span class="armsa-tile-l">' . esc_html( $label ) . '</span><span class="armsa-tile-v">';
	echo $code && '—' !== $value ? '<code>' . esc_html( $value ) . '</code>' : esc_html( $value );
	echo '</span></div>';
};
?>
<?php
include ARMSA_DIR . 'admin/views/details-styles.php';

// Profile statistics from the payment log.
$st_total = 0.0;
$st_ok    = 0;
$st_fail  = 0;
$st_last  = '';
foreach ( $history as $h ) {
	$n = Payment_History::normalize_status( isset( $h['arm_transaction_status'] ) ? $h['arm_transaction_status'] : '' );
	if ( Payment_History::STATUS_SUCCESS === $n ) {
		++$st_ok;
		$st_total += isset( $h['arm_amount'] ) ? (float) $h['arm_amount'] : 0;
	} elseif ( Payment_History::STATUS_FAILED === $n ) {
		++$st_fail;
	}
	if ( '' === $st_last ) {
		$st_last = Payment_History::effective_date( $h );
	}
}
?>
<div class="wrap armsa-wrap armsa-details">
	<div class="armsa-toolbar">
		<a href="<?php echo esc_url( $back_url ); ?>" class="button">← <?php esc_html_e( 'Back to members', 'armember-subscription-audit' ); ?></a>
	</div>

	<header class="armsa-hero">
		<div class="armsa-avatar"><?php echo get_avatar( (int) $user['id'], 64 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<div class="armsa-hero-main">
			<h1><?php echo esc_html( $user['name'] ); ?></h1>
			<p class="armsa-hero-meta">
				<span>✉ <?php echo esc_html( $user['email'] ); ?></span>
				<span>ID <strong>#<?php echo esc_html( $user['id'] ); ?></strong></span>
				<?php if ( ! empty( $user['login'] ) ) : ?><span>@<?php echo esc_html( $user['login'] ); ?></span><?php endif; ?>
				<span><?php esc_html_e( 'Registered', 'armember-subscription-audit' ); ?> <?php echo esc_html( $fmt_date( $user['registered'] ) ); ?></span>
			</p>
		</div>
		<div class="armsa-hero-badges">
			<?php foreach ( $rows as $r ) :
				$pd  = $p2[ $r['plan_id'] ];
				$sst = $pd['stored'] ? $pd['stored']['sync_status'] : Status::SYNC_NONE;
				$ov  = $pd['a'] ? Status::effective_overall( $pd['a']['overall']['status'], $sst ) : Status::OV_NOT_ASSESSED;
				?>
				<div class="armsa-hero-plan"><span class="armsa-hero-plan-n"><?php echo esc_html( $r['plan_name'] ); ?></span>
					<span class="armsa-p2 armsa-p2-<?php echo esc_attr( $ov ); ?>"><?php echo esc_html( Status::label( 'overall', $ov ) ); ?></span></div>
			<?php endforeach; ?>
		</div>
		<div class="armsa-stats">
			<div class="armsa-stat"><b><?php echo esc_html( count( $rows ) ); ?></b><span><?php esc_html_e( 'Memberships', 'armember-subscription-audit' ); ?></span></div>
			<div class="armsa-stat"><b><?php echo esc_html( $st_ok ); ?></b><span><?php esc_html_e( 'Successful payments', 'armember-subscription-audit' ); ?></span></div>
			<div class="armsa-stat"><b><?php echo esc_html( $st_fail ); ?></b><span><?php esc_html_e( 'Failed payments', 'armember-subscription-audit' ); ?></span></div>
			<div class="armsa-stat"><b><?php echo esc_html( number_format_i18n( $st_total, 2 ) ); ?></b><span><?php esc_html_e( 'Total paid (logged)', 'armember-subscription-audit' ); ?></span></div>
			<div class="armsa-stat"><b><?php echo esc_html( $st_last ? mysql2date( get_option( 'date_format' ), $st_last ) : '—' ); ?></b><span><?php esc_html_e( 'Last payment', 'armember-subscription-audit' ); ?></span></div>
		</div>
	</header>

	<nav class="armsa-tabs" role="tablist">
		<button type="button" class="armsa-tab is-active" data-tab="overview" role="tab"><?php esc_html_e( 'Overview', 'armember-subscription-audit' ); ?></button>
		<button type="button" class="armsa-tab" data-tab="sync" role="tab"><?php esc_html_e( 'Gateway Sync & Readiness', 'armember-subscription-audit' ); ?></button>
		<button type="button" class="armsa-tab" data-tab="history" role="tab"><?php esc_html_e( 'Payment History', 'armember-subscription-audit' ); ?> <span class="armsa-count-pill"><?php echo esc_html( count( $history ) ); ?></span></button>
		<button type="button" class="armsa-tab" data-tab="timeline" role="tab"><?php esc_html_e( 'Timeline & Audit', 'armember-subscription-audit' ); ?></button>
	</nav>

	<section class="armsa-pane is-active" data-pane="overview">
		<?php foreach ( $rows as $row ) :
			$a = $row['audit'];
			$m = $row['meta'];
			$s = is_array( $m['stripe'] ) ? $m['stripe'] : array();
			?>
			<article class="armsa-card">
				<div class="armsa-card-head">
					<h2><?php echo esc_html( $row['plan_name'] ); ?> <small>#<?php echo esc_html( $row['plan_id'] ); ?> · <?php echo esc_html( $row['plan_kind'] ); ?></small></h2>
					<span class="armsa-badge armsa-badge-<?php echo esc_attr( $a['status'] ); ?>"><?php echo esc_html( $a['label'] ); ?></span>
				</div>

				<div class="armsa-tiles">
					<?php
					$tile( __( 'Gateway', 'armember-subscription-audit' ), Subscription_Audit::gateway_label( $a['gateway'] ) . ( '' !== $a['gateway'] ? ' (' . $a['gateway'] . ')' : '' ) );
					$tile( __( 'Payment mode', 'armember-subscription-audit' ), Subscription_Audit::mode_label( $a['mode'] ) );
					$tile( __( 'Next due payment', 'armember-subscription-audit' ), $ts_date( $m['next_due'] ) );
					$tile( __( 'Completed recurring', 'armember-subscription-audit' ), $show( $m['completed'] ) );
					$tile( __( 'Payment cycle', 'armember-subscription-audit' ), $show( $m['cycle'] ) );
					$tile( __( 'Detected model', 'armember-subscription-audit' ), $show( $a['model'] ) );
					$tile( __( 'Subscription ID (arm_subscr_id)', 'armember-subscription-audit' ), $show( $m['subscr_id'] ), true );
					$tile( __( 'Reference used by audit', 'armember-subscription-audit' ), '' !== $a['reference'] ? $a['reference'] : '—', true );
					$tile( __( 'Cancellation', 'armember-subscription-audit' ), ! empty( $m['cancelled'] ) ? 'Cancelled (' . $show( $m['cancelled'] ) . ')' : __( 'Not cancelled', 'armember-subscription-audit' ) );
					?>
				</div>

				<?php if ( $s ) : ?>
					<details class="armsa-more"><summary><?php esc_html_e( 'Stripe metadata (arm_stripe)', 'armember-subscription-audit' ); ?></summary>
						<ul class="armsa-kvlist">
						<?php foreach ( $s as $k => $v ) : ?>
							<li><span><?php echo esc_html( $k ); ?></span><code><?php echo esc_html( is_scalar( $v ) ? $v : wp_json_encode( $v ) ); ?></code></li>
						<?php endforeach; ?>
						</ul>
					</details>
				<?php endif; ?>

				<h3><?php esc_html_e( 'Audit summary', 'armember-subscription-audit' ); ?></h3>
				<?php
				$groups  = array(
					array( __( 'Reasons', 'armember-subscription-audit' ), $a['reasons'], 'info' ),
					array( __( 'Missing fields', 'armember-subscription-audit' ), $a['missing'], 'warn' ),
					array( __( 'Inconsistencies', 'armember-subscription-audit' ), $a['inconsistencies'], 'bad' ),
					array( __( 'Notes', 'armember-subscription-audit' ), $a['notes'], 'muted' ),
				);
				$printed = false;
				foreach ( $groups as $g ) :
					if ( ! $g[1] ) {
						continue;
					}
					$printed = true;
					?>
					<div class="armsa-alert armsa-alert-<?php echo esc_attr( $g[2] ); ?>">
						<strong><?php echo esc_html( $g[0] ); ?></strong>
						<ul><?php foreach ( $g[1] as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul>
					</div>
				<?php endforeach; ?>
				<?php if ( ! $printed ) : ?><p class="description"><?php esc_html_e( 'Nothing to flag.', 'armember-subscription-audit' ); ?></p><?php endif; ?>
			</article>
		<?php endforeach; ?>
	</section>

	<section class="armsa-pane" data-pane="sync">
		<?php foreach ( $rows as $row ) : ?>
			<h2 class="armsa-pane-title"><?php echo esc_html( $row['plan_name'] ); ?></h2>
			<?php include ARMSA_DIR . 'admin/views/sync-panel.php'; ?>
		<?php endforeach; ?>
	</section>

	<section class="armsa-pane" data-pane="history">
		<div class="armsa-scroll">
		<table class="wp-list-table widefat striped armsa-table">
			<thead><tr>
				<th><?php esc_html_e( 'Date', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Status', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Plan', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Gateway', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Type', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Transaction ID', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Token / reference', 'armember-subscription-audit' ); ?></th>
				<th><?php esc_html_e( 'Log', 'armember-subscription-audit' ); ?></th>
			</tr></thead>
			<tbody>
			<?php if ( ! $history ) : ?>
				<tr><td colspan="9"><?php esc_html_e( 'No payment log records for this member.', 'armember-subscription-audit' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $history as $h ) :
				$norm = Payment_History::normalize_status( isset( $h['arm_transaction_status'] ) ? $h['arm_transaction_status'] : '' );
				$get  = function ( $k ) use ( $h ) {
					return isset( $h[ $k ] ) && '' !== (string) $h[ $k ] ? (string) $h[ $k ] : '—';
				};
				?>
				<tr>
					<td><?php echo esc_html( $fmt_date( Payment_History::effective_date( $h ) ) ); ?></td>
					<td><span class="armsa-pay armsa-pay-<?php echo esc_attr( $norm ); ?>"><?php echo esc_html( $get( 'arm_transaction_status' ) ); ?></span></td>
					<td><strong><?php echo esc_html( isset( $h['arm_amount'] ) ? number_format_i18n( (float) $h['arm_amount'], 2 ) . ' ' . $get( 'arm_currency' ) : '—' ); ?></strong></td>
					<td><?php echo esc_html( $get( 'arm_plan_id' ) ); ?></td>
					<td><?php echo esc_html( $get( 'arm_payment_gateway' ) ); ?></td>
					<td><?php echo esc_html( $get( 'arm_payment_type' ) ); ?></td>
					<td class="armsa-break"><code><?php echo esc_html( $get( 'arm_transaction_id' ) ); ?></code></td>
					<td class="armsa-break"><code><?php echo esc_html( $get( 'arm_token' ) ); ?></code></td>
					<td>#<?php echo esc_html( $get( 'arm_log_id' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
	</section>

	<section class="armsa-pane" data-pane="timeline">
		<?php include ARMSA_DIR . 'admin/views/sync-timeline.php'; ?>
	</section>
</div>

<script>
(function () {
	var root = document.querySelector('.armsa-details');
	if (!root) { return; }
	root.classList.add('armsa-js');
	var tabs = root.querySelectorAll('.armsa-tab'), panes = root.querySelectorAll('.armsa-pane');
	function show(name) {
		tabs.forEach(function (t) { t.classList.toggle('is-active', t.dataset.tab === name); });
		panes.forEach(function (p) { p.classList.toggle('is-active', p.dataset.pane === name); });
		try { history.replaceState(null, '', '#' + name); } catch (e) {}
	}
	tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); }); });
	var h = (location.hash || '').replace('#', '');
	if (h && root.querySelector('[data-pane="' + h + '"]')) { show(h); }
	root.addEventListener('click', function (e) {
		var a = e.target.closest('a[href^="#armsa-enter-"]');
		if (a) { show('sync'); }
	});
})();
</script>
