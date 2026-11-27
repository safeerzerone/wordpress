<?php
/**
 * Phase 2: "Payment Gateway Sync & Readiness" panel for ONE membership row.
 *
 * @var array $row  Phase 1 audit row.
 * @var array $user
 * @var array $p2   Per-plan Phase 2 data (stored, a, is_stored, jobs).
 *
 * @package ARMSA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ARMSA\Status;
use ARMSA\Sync_Admin;

$pd      = $p2[ $row['plan_id'] ];
$a       = $pd['a'];
$uid     = (int) $user['id'];
$pid     = (int) $row['plan_id'];
$local_t = function ( $gmt ) {
	return $gmt ? get_date_from_gmt( $gmt, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '—';
};
$fstat   = array(
	Status::F_VERIFIED  => array( 'Verified', 'ok' ),
	Status::F_FOUND     => array( 'Found – not verified', 'warn' ),
	Status::F_MISSING   => array( 'Missing', 'bad' ),
	Status::F_INVALID   => array( 'Invalid', 'bad' ),
	Status::F_CONFLICT  => array( 'Conflict', 'bad' ),
	Status::F_AMBIGUOUS => array( 'Multiple matches', 'bad' ),
	Status::F_OPTIONAL  => array( 'Optional – empty', 'muted' ),
	Status::F_UNKNOWN   => array( 'Unverified', 'warn' ),
);
$stored_row = $pd['stored'];
$sync_state = $stored_row ? $stored_row['sync_status'] : Status::SYNC_NONE;
$overall_k  = $a ? Status::effective_overall( $a['overall']['status'], $sync_state ) : Status::OV_NOT_ASSESSED;
?>
<div class="armsa-p2-panel">
	<h3><?php esc_html_e( 'Payment Gateway Sync & Readiness', 'armember-subscription-audit' ); ?>
		<span class="armsa-p2 armsa-p2-<?php echo esc_attr( $overall_k ); ?>"><?php echo esc_html( Status::label( 'overall', $overall_k ) ); ?></span>
	</h3>

	<?php if ( ! $a ) : ?>
		<p><?php esc_html_e( 'This membership could not be assessed (no ARMember membership row).', 'armember-subscription-audit' ); ?></p>
		<?php return; ?>
	<?php endif; ?>

	<p class="description">
		<?php
		echo esc_html(
			$pd['is_stored']
				? sprintf( /* translators: %s: date */ __( 'Showing the stored assessment from %s (UTC-converted to site time). It never contacts the gateway on page load.', 'armember-subscription-audit' ), $local_t( $stored_row['assessed_at'] ) )
				: __( 'Not assessed yet — showing a live local preview (WordPress/ARMember/WooCommerce data only; no gateway contacted, nothing saved).', 'armember-subscription-audit' )
		);
		?>
		<strong><?php echo esc_html( $a['model_label'] ); ?></strong>
	</p>

	<?php $pt = isset( $a['payment_type'] ) ? $a['payment_type'] : null; if ( $pt ) : ?><p class="armsa-alert armsa-alert-<?php echo 'recurring' === $pt['type'] ? 'info' : 'warn'; ?>"><strong><?php esc_html_e( 'Payment type:', 'armember-subscription-audit' ); ?> <?php echo esc_html( $pt['label'] ); ?></strong> — <?php echo esc_html( $pt['reason'] ); ?></p><?php endif; ?>
	<p><strong><?php esc_html_e( 'What is required next:', 'armember-subscription-audit' ); ?></strong> <?php echo esc_html( $a['overall']['action'] ); ?></p>

	<?php /* ---------------- A. Member payment information ---------------- */ ?>
	<h4><?php esc_html_e( 'A. Member Payment Information', 'armember-subscription-audit' ); ?></h4>
	<?php
	$f        = $a['fields'];
	$token    = '';
	foreach ( array( 'stripe_payment_method', 'wc_payment_token', 'paypal_profile_id' ) as $tk ) {
		if ( isset( $f[ $tk ] ) && '' !== $f[ $tk ]['current'] ) {
			$token = $f[ $tk ]['current'];
			break;
		}
	}
	?>
	<table class="widefat striped armsa-kv"><tbody>
		<tr><th><?php esc_html_e( 'Member name', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $user['name'] ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Member email', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $user['email'] ); ?></td></tr>
		<tr><th><?php esc_html_e( 'ARMember member ID', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $uid ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Membership plan', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( $row['plan_name'] . ' (#' . $pid . ')' ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Current payment gateway', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( \ARMSA\Subscription_Audit::gateway_label( $row['audit']['gateway'] ) . ( '' !== $row['audit']['gateway'] ? ' (' . $row['audit']['gateway'] . ')' : '' ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Current payment mode', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( \ARMSA\Subscription_Audit::mode_label( $row['audit']['mode'] ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Existing subscription reference', 'armember-subscription-audit' ); ?></th><td><?php echo '' !== $row['audit']['reference'] ? '<code>' . esc_html( $row['audit']['reference'] ) . '</code> — ' . esc_html( $row['audit']['reference_source'] ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
		<tr><th><?php esc_html_e( 'Existing payment token / reference', 'armember-subscription-audit' ); ?></th><td><?php echo '' !== $token ? '<code>' . esc_html( $token ) . '</code>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
		<tr><th><?php esc_html_e( 'Last payment status', 'armember-subscription-audit' ); ?></th><td><?php echo esc_html( ucfirst( $row['audit']['latest_status'] ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Next payment due date', 'armember-subscription-audit' ); ?></th><td><?php echo is_numeric( $row['meta']['next_due'] ) && (int) $row['meta']['next_due'] > 0 ? esc_html( wp_date( get_option( 'date_format' ), (int) $row['meta']['next_due'] ) ) : '—'; ?></td></tr>
	</tbody></table>

	<?php /* ---------------- B. Discovered information ---------------- */ ?>
	<h4><?php esc_html_e( 'B. Discovered Gateway Information', 'armember-subscription-audit' ); ?></h4>
	<table class="wp-list-table widefat fixed striped armsa-table">
		<thead><tr>
			<th><?php esc_html_e( 'Field', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Current value', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Source', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Verification status', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Required / Optional', 'armember-subscription-audit' ); ?></th>
			<th><?php esc_html_e( 'Action', 'armember-subscription-audit' ); ?></th>
		</tr></thead>
		<tbody>
		<?php foreach ( $a['fields'] as $fk => $fd ) :
			$st = isset( $fstat[ $fd['status'] ] ) ? $fstat[ $fd['status'] ] : array( $fd['status'], 'muted' );
			?>
			<tr>
				<td><strong><?php echo esc_html( $fd['label'] ); ?></strong><br><span class="description"><?php echo esc_html( ucfirst( $fd['store'] ) ); ?> · <?php echo $fd['future'] ? esc_html__( 'needed for future payments', 'armember-subscription-audit' ) : esc_html__( 'informational', 'armember-subscription-audit' ); ?></span></td>
				<td class="armsa-break">
					<?php
					if ( count( $fd['values'] ) > 1 ) {
						echo esc_html( implode( ' | ', $fd['values'] ) );
					} elseif ( '' !== $fd['current'] ) {
						echo '<code>' . esc_html( $fd['current'] ) . '</code>';
					} elseif ( '' !== $fd['proposed'] ) {
						echo '<em>' . esc_html__( 'from gateway:', 'armember-subscription-audit' ) . '</em> <code>' . esc_html( $fd['proposed'] ) . '</code>';
					} else {
						echo '—';
					}
					?>
				</td>
				<td class="armsa-break"><?php echo $fd['sources'] ? esc_html( implode( '; ', $fd['sources'] ) ) : esc_html( $fd['source'] ); ?></td>
				<td><span class="armsa-f armsa-f-<?php echo esc_attr( $st[1] ); ?>"><?php echo esc_html( $st[0] ); ?></span><?php echo '' !== $fd['note'] ? '<br><span class="description">' . esc_html( $fd['note'] ) . '</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<td><?php echo $fd['required'] ? esc_html__( 'Required', 'armember-subscription-audit' ) : esc_html__( 'Optional', 'armember-subscription-audit' ); ?></td>
				<td>
					<?php
					$labels = array( 'retrieve' => __( 'Retrieve', 'armember-subscription-audit' ), 'verify' => __( 'Verify', 'armember-subscription-audit' ), 'enter' => __( 'Enter manually', 'armember-subscription-audit' ), 'view' => __( 'View', 'armember-subscription-audit' ) );
					foreach ( $fd['actions'] as $act ) {
						if ( 'retrieve' === $act || 'verify' === $act ) {
							Sync_Admin::form_open( 'queue_assess', $uid, $pid );
							echo '<button type="submit" class="button button-small">' . esc_html( $labels[ $act ] ) . '</button></form> ';
						} elseif ( 'enter' === $act ) {
							echo '<a class="button button-small" href="#armsa-enter-' . esc_attr( $pid . '-' . $fk ) . '">' . esc_html( $labels[ $act ] ) . '</a> ';
						}
					}
					?>
					<details><summary><?php esc_html_e( 'View', 'armember-subscription-audit' ); ?></summary>
						<div class="description">
							<?php esc_html_e( 'Storage:', 'armember-subscription-audit' ); ?> <?php echo esc_html( $fd['source'] ); ?><br>
							<?php if ( '' !== $fd['format_hint'] ) : ?><?php esc_html_e( 'Format:', 'armember-subscription-audit' ); ?> <?php echo esc_html( $fd['format_hint'] ); ?><br><?php endif; ?>
							<?php esc_html_e( 'Retrievable automatically:', 'armember-subscription-audit' ); ?> <?php echo $fd['retrievable'] ? esc_html__( 'yes', 'armember-subscription-audit' ) : esc_html__( 'no', 'armember-subscription-audit' ); ?> ·
							<?php esc_html_e( 'Admin input allowed:', 'armember-subscription-audit' ); ?> <?php echo $fd['editable'] ? esc_html__( 'yes (validated, staged)', 'armember-subscription-audit' ) : esc_html__( 'no', 'armember-subscription-audit' ); ?> ·
							<?php esc_html_e( 'Needs gateway verification:', 'armember-subscription-audit' ); ?> <?php echo $fd['verify'] ? esc_html__( 'yes', 'armember-subscription-audit' ) : esc_html__( 'no', 'armember-subscription-audit' ); ?><br>
							<?php if ( '' !== $fd['validation'] ) : ?><?php echo esc_html( $fd['validation'] ); ?><?php endif; ?>
						</div>
					</details>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php /* ---------------- C. Missing information management ---------------- */ ?>
	<h4><?php esc_html_e( 'C. Missing Information / Manual Input', 'armember-subscription-audit' ); ?></h4>
	<?php
	$any = false;
	foreach ( $a['fields'] as $fk => $fd ) :
		if ( ! in_array( 'enter', $fd['actions'], true ) ) {
			continue;
		}
		$any = true;
		?>
		<div class="armsa-enter" id="armsa-enter-<?php echo esc_attr( $pid . '-' . $fk ); ?>">
			<strong><?php echo esc_html( $fd['label'] ); ?></strong>
			<?php if ( $fd['cands'] ) : ?>
				<p class="description"><?php esc_html_e( 'Candidates found at the gateway (matched by e-mail or stored locally). Ownership is NOT proven by e-mail — confirm only after checking the record yourself:', 'armember-subscription-audit' ); ?></p>
				<ul class="armsa-list">
				<?php foreach ( $fd['cands'] as $c ) : ?>
					<li><code><?php echo esc_html( $c['value'] ); ?></code> — <?php echo esc_html( $c['note'] ); ?></li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php Sync_Admin::form_open( 'stage', $uid, $pid ); ?>
				<input type="hidden" name="field_key" value="<?php echo esc_attr( $fk ); ?>">
				<input type="text" name="field_value" class="regular-text" placeholder="<?php echo esc_attr( $fd['format_hint'] ); ?>" required>
				<label><input type="checkbox" name="confirm_ownership" value="1"> <?php esc_html_e( 'I verified this record belongs to this member', 'armember-subscription-audit' ); ?></label>
				<label><input type="checkbox" name="confirm_replace" value="1"> <?php esc_html_e( 'Replace an existing staged value', 'armember-subscription-audit' ); ?></label>
				<label><input type="checkbox" name="confirm_stage" value="1" required> <?php esc_html_e( 'Save (validated, audited, verified with the gateway before any sync)', 'armember-subscription-audit' ); ?></label>
				<?php Sync_Admin::button( __( 'Save value', 'armember-subscription-audit' ), true ); ?>
			</form>
			<?php
			$staged_val = '';
			if ( $pd['stored'] ) {
				global $wpdb;
				$mt         = \ARMSA\Installer::table( 'mappings' );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$staged_val = (string) $wpdb->get_var( $wpdb->prepare( "SELECT value FROM `{$mt}` WHERE user_id=%d AND plan_id=%d AND field_key=%s AND status IN ('pending','verified')", $uid, $pid, $fk ) );
			}
			if ( '' !== $staged_val ) :
				Sync_Admin::form_open( 'discard_staged', $uid, $pid );
				?>
				<input type="hidden" name="field_key" value="<?php echo esc_attr( $fk ); ?>">
				<span class="description"><?php esc_html_e( 'Staged (not in ARMember yet):', 'armember-subscription-audit' ); ?> <code><?php echo esc_html( $staged_val ); ?></code></span>
				<?php Sync_Admin::button( __( 'Discard staged value', 'armember-subscription-audit' ), true ); ?>
				</form>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
	<?php if ( ! $any ) : ?>
		<p class="description"><?php esc_html_e( 'No field needs manual input (or manual entry is not supported/safe for this payment model: such identifiers are created only by the gateway or by WooCommerce).', 'armember-subscription-audit' ); ?></p>
	<?php endif; ?>

	<?php /* ---------------- D. ARMember updates ---------------- */ ?>
	<h4><?php esc_html_e( 'D. ARMember Information Update (what Sync would store)', 'armember-subscription-audit' ); ?></h4>
	<?php if ( empty( $a['proposals'] ) ) : ?>
		<p class="description"><?php echo esc_html( 'ready_for_sync' === $a['overall']['status'] ? '' : __( 'No ARMember update is proposed. Updates are proposed only when the gateway record is verified, ownership is proven and the ARMember field is empty. Existing values are never overwritten; gateway and payment mode are never changed.', 'armember-subscription-audit' ) ); ?></p>
	<?php else : ?>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'ARMember location', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Existing value', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Proposed value', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Why it is required', 'armember-subscription-audit' ); ?></th></tr></thead><tbody>
		<?php foreach ( $a['proposals'] as $pr ) : ?>
			<tr><td class="armsa-break"><?php echo esc_html( $pr['location'] ); ?></td><td><?php echo '' === $pr['current'] ? '—' : '<code>' . esc_html( $pr['current'] ) . '</code>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td><td><code><?php echo esc_html( $pr['proposed'] ); ?></code></td><td><?php echo esc_html( $pr['reason'] ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	<?php endif; ?>

	<?php /* ---------------- Readiness ---------------- */ ?>
	<h4><?php esc_html_e( 'Gateway Readiness', 'armember-subscription-audit' ); ?></h4>
	<p><span class="armsa-p2 armsa-p2-<?php echo esc_attr( $a['gateway']['status'] ); ?>"><?php echo esc_html( $a['gateway']['label'] ); ?></span> <?php echo esc_html( $a['gateway']['explain'] ); ?></p>
	<?php if ( ! empty( $a['lookup']['attempted'] ) ) : ?>
		<p class="description"><?php echo esc_html( trim( $a['lookup']['sub_detail'] . ' ' . $a['lookup']['ownership_note'] ) ); ?></p>
	<?php endif; ?>

	<h4><?php esc_html_e( 'ARMember Renewal Readiness', 'armember-subscription-audit' ); ?></h4>
	<p><span class="armsa-p2 armsa-p2-<?php echo esc_attr( $a['arm']['status'] ); ?>"><?php echo esc_html( $a['arm']['label'] ); ?></span> <?php echo esc_html( $a['arm']['explain'] ); ?></p>
	<?php if ( ! empty( $a['arm']['checks'] ) ) : ?>
		<ul class="armsa-list armsa-checks">
		<?php foreach ( $a['arm']['checks'] as $c ) : ?>
			<li class="armsa-check-<?php echo esc_attr( $c['status'] ); ?>"><strong><?php echo esc_html( strtoupper( $c['status'] ) ); ?></strong> — <?php echo esc_html( $c['label'] ); ?>: <?php echo esc_html( $c['detail'] ); ?></li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php /* ---------------- Sync actions ---------------- */ ?>
	<h4><?php esc_html_e( 'Synchronisation Actions', 'armember-subscription-audit' ); ?></h4>
	<?php
	$jobs        = $pd['jobs'];
	$active      = function ( $type ) use ( $jobs ) {
		foreach ( $jobs as $j ) {
			if ( $j['job_type'] === $type && in_array( $j['status'], array( 'queued', 'processing' ), true ) ) {
				return true;
			}
		}
		return false;
	};
	$last_sync   = null;
	foreach ( $jobs as $j ) {
		if ( 'sync' === $j['job_type'] ) {
			$last_sync = $j;
			break;
		}
	}
	$ptype       = isset( $a['payment_type'] ) ? $a['payment_type'] : array( 'type' => 'recurring', 'label' => 'Recurring', 'reason' => '' );
	$is_rec      = 'recurring' === $ptype['type'];
	$unsupported = 'unsupported' === $a['model'] || ! $is_rec;
	$can_lookup  = ! $unsupported && ! $active( 'assess' );
	$why_lookup  = ! $is_rec ? __( 'Only recurring plans are synchronised with a payment gateway.', 'armember-subscription-audit' ) : ( $unsupported ? __( 'No supported gateway integration for this payment model.', 'armember-subscription-audit' ) : __( 'A gateway lookup is already queued or running.', 'armember-subscription-audit' ) );
	$can_sync    = $pd['is_stored'] && Status::OV_READY === $a['overall']['status'] && ! $active( 'sync' ) && Status::SYNC_QUEUED !== $sync_state && Status::SYNC_RUNNING !== $sync_state;
	$why_sync    = ! $pd['is_stored'] ? __( 'Not assessed with the gateway yet — run "Retrieve Gateway Information" first.', 'armember-subscription-audit' ) : ( Status::OV_READY !== $a['overall']['status'] ? $a['overall']['action'] : __( 'A synchronisation is already queued or running.', 'armember-subscription-audit' ) );
	$can_retry   = $last_sync && 'failed' === $last_sync['status'];
	?>
	<?php
	// One action card: step number, title, what it does, button, and (when disabled) why.
	$card = function ( $step, $title, $desc, $action, $label, $enabled, $why = '', $primary = false, $hidden = '' ) use ( $uid, $pid ) {
		echo '<div class="armsa-act' . ( $enabled ? '' : ' is-off' ) . '"><span class="armsa-act-n">' . esc_html( $step ) . '</span>';
		echo '<div class="armsa-act-body"><strong class="armsa-act-t">' . esc_html( $title ) . '</strong><p class="armsa-act-d">' . esc_html( $desc ) . '</p></div>';
		echo '<div class="armsa-act-foot">';
		Sync_Admin::form_open( $action, $uid, $pid );
		echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped values below.
		printf( '<button type="submit" class="button%s" %s>%s</button>', $primary ? ' button-primary' : '', $enabled ? '' : 'disabled', esc_html( $label ) );
		echo '</form>';
		if ( ! $enabled && '' !== $why ) {
			echo '<p class="armsa-act-why">⚠ ' . esc_html( $why ) . '</p>';
		}
		echo '</div></div>';
	};
	?>
	<div class="armsa-acts">
		<?php
		$card( '1', __( 'Assess Member', 'armember-subscription-audit' ), __( 'Checks WordPress, ARMember and WooCommerce data instantly. No gateway is contacted.', 'armember-subscription-audit' ), 'assess_local', __( 'Assess Member', 'armember-subscription-audit' ), $is_rec, __( 'Only recurring (Subscription / Recurring Payment) plans are assessed.', 'armember-subscription-audit' ) );
		$card( '2', __( 'Retrieve Gateway Information', 'armember-subscription-audit' ), __( 'Queued background lookup that retrieves and verifies the gateway records (read-only).', 'armember-subscription-audit' ), 'queue_assess', __( 'Retrieve / Verify', 'armember-subscription-audit' ), $can_lookup, $why_lookup );
		$card( '3', __( 'Queue Synchronisation', 'armember-subscription-audit' ), __( 'Stores the verified references in ARMember. Re-checked live before anything is written.', 'armember-subscription-audit' ), 'queue_sync', __( 'Queue Synchronisation', 'armember-subscription-audit' ), $can_sync, $why_sync, true );
		$card( '4', __( 'Retry Synchronisation', 'armember-subscription-audit' ), __( 'Re-queues the latest sync job if it failed.', 'armember-subscription-audit' ), 'retry_jobs', __( 'Retry Synchronisation', 'armember-subscription-audit' ), (bool) $can_retry, __( 'The latest sync job has not failed.', 'armember-subscription-audit' ), false, '<input type="hidden" name="job_ids[]" value="' . esc_attr( $last_sync ? $last_sync['id'] : 0 ) . '">' );
		?>
	</div>
	<?php if ( $jobs ) : ?>
		<table class="widefat striped armsa-jobs"><thead><tr><th>#</th><th><?php esc_html_e( 'Type', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Status', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Attempts', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Result / error', 'armember-subscription-audit' ); ?></th><th><?php esc_html_e( 'Updated', 'armember-subscription-audit' ); ?></th></tr></thead><tbody>
		<?php foreach ( $jobs as $j ) : ?>
			<tr><td><?php echo esc_html( $j['id'] ); ?></td><td><?php echo esc_html( $j['job_type'] ); ?></td><td><?php echo esc_html( $j['status'] ); ?></td><td><?php echo esc_html( $j['attempts'] . '/' . $j['max_attempts'] ); ?></td><td class="armsa-break"><?php echo esc_html( trim( (string) $j['result'] . ' ' . (string) $j['last_error'] ) ); ?></td><td><?php echo esc_html( $local_t( $j['updated_at'] ) ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	<?php endif; ?>
</div>
