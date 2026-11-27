<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<style id="armsa-details-inline-css">
.armsa-filters { margin: 12px 0; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.armsa-count { margin: 6px 0; color: #50575e; }
.armsa-table td, .armsa-table th { word-wrap: break-word; }
.armsa-break { word-break: break-all; }
.armsa-kv th { width: 260px; }
.armsa-summary { background: #fff; border: 1px solid #c3c4c7; padding: 4px 16px; max-width: 900px; }
.armsa-list { margin: 0 0 8px 20px; list-style: disc; }
.armsa-group { margin: 8px 0; }
.armsa-badge, .armsa-pay { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; line-height: 1.6; background: #f0f0f1; color: #1d2327; }
.armsa-badge-subscription_linked { background: #d1e7dd; color: #0a3622; }
.armsa-badge-subscription_id_missing { background: #fff3cd; color: #664d03; }
.armsa-badge-no_recurring_information { background: #e2e3e5; color: #41464b; }
.armsa-badge-payment_history_missing { background: #ffe5d0; color: #6c3000; }
.armsa-badge-last_payment_failed { background: #f8d7da; color: #58151c; }
.armsa-badge-needs_investigation { background: #e0cffc; color: #3d1a78; }
.armsa-pay-success { background: #d1e7dd; color: #0a3622; }
.armsa-pay-failed { background: #f8d7da; color: #58151c; }

/* ---- Phase 2 ---- */
.armsa-scroll { overflow-x: auto; }
.armsa-queue { margin: 4px 0 8px; }
.armsa-bulk .tablenav { height: auto; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin: 6px 0; }
.armsa-p2, .armsa-f, .armsa-step { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; line-height: 1.6; background: #f0f0f1; color: #1d2327; }
.armsa-p2-gw_verified, .armsa-p2-arm_renewal_verified, .armsa-p2-arm_complete, .armsa-p2-ready_for_sync, .armsa-p2-sync_completed, .armsa-p2-renewal_readiness_verified, .armsa-f-ok, .armsa-step-completed { background: #d1e7dd; color: #0a3622; }
.armsa-p2-gw_found, .armsa-p2-gw_lookup_required, .armsa-p2-gateway_lookup_required, .armsa-p2-gw_manual_input, .armsa-p2-manual_input_required, .armsa-p2-gw_missing, .armsa-p2-missing_information, .armsa-p2-arm_missing, .armsa-p2-arm_mapping_required, .armsa-p2-armember_mapping_required, .armsa-p2-sync_queued, .armsa-p2-sync_in_progress, .armsa-p2-assessment_in_progress, .armsa-p2-gateway_linked_renewal_not_verified, .armsa-p2-arm_renewal_not_verified, .armsa-p2-partially_synced, .armsa-p2-linked_unverified, .armsa-f-warn, .armsa-step-warning, .armsa-step-requires_input, .armsa-step-processing { background: #fff3cd; color: #664d03; }
.armsa-p2-gw_invalid, .armsa-p2-gw_api_error, .armsa-p2-gw_inactive, .armsa-p2-gateway_verification_failed, .armsa-p2-sync_failed, .armsa-p2-arm_incorrect_config, .armsa-f-bad, .armsa-step-failed { background: #f8d7da; color: #58151c; }
.armsa-p2-gw_multiple, .armsa-p2-gw_ownership_required, .armsa-p2-needs_review, .armsa-p2-arm_manual_review, .armsa-p2-gw_unsupported { background: #e0cffc; color: #3d1a78; }
.armsa-p2-panel { background: #fff; border: 1px solid #c3c4c7; padding: 4px 16px 16px; margin: 8px 0 24px; }
.armsa-inline { display: inline-block; margin: 0 8px 4px 0; }
.armsa-why { color: #b32d2e; }
.armsa-enter { border-left: 3px solid #2271b1; padding: 4px 12px; margin: 8px 0; background: #f6f7f7; }
.armsa-enter label { display: block; margin: 2px 0; }
.armsa-checks li { margin: 2px 0; }
.armsa-check-fail strong { color: #b32d2e; }
.armsa-check-ok strong { color: #00a32a; }
.armsa-check-unknown strong, .armsa-check-warn strong { color: #996800; }
.armsa-actions { margin: 8px 0; }
.armsa-colpicker { margin: 8px 0; }
.armsa-colpicker form { background: #fff; border: 1px solid #c3c4c7; padding: 8px 12px; margin-top: 6px; display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 4px 12px; }
.armsa-colpicker form p, .armsa-colpicker form button { grid-column: 1 / -1; }

/* ---- Member details redesign ---- */
.armsa-details { max-width: 1280px; --armsa-r: 10px; --armsa-line: #e3e5e8; --armsa-muted: #646970; --armsa-accent: #2271b1; }
.armsa-details .armsa-toolbar { margin: 12px 0 8px; }
.armsa-hero { display: flex; flex-wrap: wrap; align-items: center; gap: 16px 20px; background: linear-gradient(135deg, #1d2327 0%, #2c3e50 100%); color: #fff; border-radius: var(--armsa-r); padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,.12); }
.armsa-avatar img { border-radius: 50%; border: 3px solid rgba(255,255,255,.35); display: block; }
.armsa-hero-main { flex: 1 1 280px; min-width: 0; }
.armsa-hero h1 { color: #fff; margin: 0 0 4px; padding: 0; font-size: 24px; line-height: 1.2; }
.armsa-hero-meta { margin: 0; display: flex; flex-wrap: wrap; gap: 4px 16px; color: rgba(255,255,255,.8); font-size: 13px; }
.armsa-hero-badges { display: flex; flex-direction: column; gap: 6px; align-items: flex-end; }
.armsa-hero-plan { display: flex; align-items: center; gap: 8px; }
.armsa-hero-plan-n { font-weight: 600; }
.armsa-tabs { display: flex; flex-wrap: wrap; gap: 4px; margin: 18px 0 0; border-bottom: 2px solid var(--armsa-line); }
.armsa-tab { background: none; border: 0; border-bottom: 3px solid transparent; margin-bottom: -2px; padding: 10px 16px; font-size: 14px; font-weight: 500; color: var(--armsa-muted); cursor: pointer; border-radius: 6px 6px 0 0; }
.armsa-tab:hover { color: #1d2327; background: #f0f0f1; }
.armsa-tab.is-active { color: var(--armsa-accent); border-bottom-color: var(--armsa-accent); font-weight: 600; }
.armsa-tab:focus-visible { outline: 2px solid var(--armsa-accent); outline-offset: -2px; }
.armsa-count-pill { background: #dcdcde; color: #1d2327; border-radius: 10px; padding: 0 7px; font-size: 11px; margin-left: 4px; }
.armsa-js .armsa-pane { display: none; }
.armsa-js .armsa-pane.is-active { display: block; }
.armsa-pane { padding-top: 16px; }
.armsa-pane-title { margin: 8px 0 4px; font-size: 16px; }
.armsa-card { background: #fff; border: 1px solid var(--armsa-line); border-radius: var(--armsa-r); padding: 16px 20px 12px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.armsa-card-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; }
.armsa-card-head h2 { margin: 0; font-size: 18px; padding: 0; }
.armsa-card-head small { color: var(--armsa-muted); font-weight: 400; font-size: 12px; margin-left: 6px; }
.armsa-card h3 { margin: 18px 0 8px; font-size: 14px; text-transform: uppercase; letter-spacing: .04em; color: var(--armsa-muted); }
.armsa-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 10px; margin-top: 14px; }
.armsa-tile { background: #f6f7f7; border: 1px solid var(--armsa-line); border-radius: 8px; padding: 10px 12px; display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.armsa-tile-l { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--armsa-muted); }
.armsa-tile-v { font-size: 14px; font-weight: 600; word-break: break-all; }
.armsa-tile-v code { background: none; padding: 0; font-weight: 600; }
.armsa-more { margin-top: 10px; }
.armsa-more summary { cursor: pointer; color: var(--armsa-accent); }
.armsa-kvlist { margin: 8px 0 0; padding: 0; list-style: none; }
.armsa-kvlist li { display: flex; gap: 12px; padding: 4px 0; border-bottom: 1px dashed var(--armsa-line); }
.armsa-kvlist li span { width: 160px; color: var(--armsa-muted); flex: none; }
.armsa-alert { border-left: 4px solid; border-radius: 6px; padding: 8px 14px; margin: 8px 0; }
.armsa-alert ul { margin: 4px 0 0 18px; list-style: disc; }
.armsa-alert-info { background: #eef5fb; border-color: #2271b1; }
.armsa-alert-warn { background: #fff8e5; border-color: #dba617; }
.armsa-alert-bad { background: #fcf0f1; border-color: #d63638; }
.armsa-alert-muted { background: #f6f7f7; border-color: #a7aaad; }
/* Sync panel inside the tab: lighter chrome, clearer sections */
.armsa-details .armsa-p2-panel { border-radius: var(--armsa-r); border-color: var(--armsa-line); box-shadow: 0 1px 2px rgba(0,0,0,.04); padding: 8px 20px 20px; }
.armsa-details .armsa-p2-panel > h3 { font-size: 16px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
.armsa-details .armsa-p2-panel h4 { margin: 22px 0 8px; padding-bottom: 6px; border-bottom: 1px solid var(--armsa-line); font-size: 14px; }
.armsa-details .armsa-actions { display: flex; flex-wrap: wrap; gap: 10px 14px; align-items: center; padding: 10px 12px; background: #f6f7f7; border-radius: 8px; }
.armsa-details .armsa-table th { background: #f6f7f7; font-weight: 600; }
.armsa-details .armsa-pay, .armsa-details .armsa-badge, .armsa-details .armsa-p2, .armsa-details .armsa-f, .armsa-details .armsa-step { border-radius: 999px; padding: 2px 10px; font-weight: 600; }
.armsa-hero .armsa-p2 { box-shadow: 0 0 0 1px rgba(255,255,255,.25); }
@media (max-width: 782px) {
	.armsa-hero-badges { align-items: flex-start; }
	.armsa-tab { padding: 8px 10px; font-size: 13px; }
	.armsa-kvlist li { flex-direction: column; gap: 0; }
}
@media (prefers-reduced-motion: no-preference) { .armsa-tab, .armsa-tile { transition: background .15s, color .15s; } }

/* ---- Advanced profile header ---- */
.armsa-details, .armsa-details * { box-sizing: border-box; }
.armsa-details { font-size: 14px; line-height: 1.5; color: #1d2327; }
.armsa-details .armsa-hero { position: relative; overflow: hidden; display: grid; grid-template-columns: auto 1fr auto; gap: 18px 22px; align-items: center; background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #2271b1 100%); }
.armsa-details .armsa-hero::after { content: ""; position: absolute; right: -60px; top: -60px; width: 220px; height: 220px; border-radius: 50%; background: rgba(255,255,255,.06); pointer-events: none; }
.armsa-details .armsa-avatar img { width: 72px; height: 72px; }
.armsa-details .armsa-hero-main { min-width: 0; }
.armsa-details .armsa-hero h1 { font-size: 26px; font-weight: 700; word-break: break-word; }
.armsa-details .armsa-hero-meta span { background: rgba(255,255,255,.12); border-radius: 999px; padding: 2px 10px; }
.armsa-details .armsa-hero-badges { position: relative; z-index: 1; }
.armsa-stats { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; position: relative; z-index: 1; }
.armsa-stat { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18); border-radius: 10px; padding: 10px 14px; }
.armsa-stat b { display: block; font-size: 20px; line-height: 1.2; }
.armsa-stat span { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: rgba(255,255,255,.75); }
.armsa-details .armsa-tabs { position: sticky; top: 32px; z-index: 5; background: #f0f0f1; padding-top: 6px; }
.armsa-details .armsa-card, .armsa-details .armsa-p2-panel { overflow-wrap: anywhere; }
.armsa-details table { width: 100%; }
.armsa-details .armsa-scroll { overflow-x: auto; background: #fff; border: 1px solid #e3e5e8; border-radius: 10px; }
.armsa-details .armsa-scroll table { border: 0; }
.armsa-details .armsa-timeline td, .armsa-details .armsa-timeline th { font-size: 12.5px; }
.armsa-details .armsa-enter input[type=text] { max-width: 100%; }
@media (max-width: 900px) { .armsa-details .armsa-hero { grid-template-columns: auto 1fr; } .armsa-details .armsa-hero-badges { grid-column: 1 / -1; align-items: flex-start; } .armsa-details .armsa-tabs { top: 46px; } }
.armsa-acts { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; margin: 10px 0 16px; }
.armsa-act { display: flex; flex-direction: column; gap: 8px; background: #fff; border: 1px solid #e3e5e8; border-radius: 10px; padding: 14px; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,.04); min-width: 0; }
.armsa-act.is-off { background: #f6f7f7; }
.armsa-act-n { position: absolute; top: 10px; right: 12px; width: 22px; height: 22px; border-radius: 50%; background: #2271b1; color: #fff; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.armsa-act.is-off .armsa-act-n { background: #a7aaad; }
.armsa-act-body { flex: 1; padding-right: 26px; }
.armsa-act-t { display: block; font-size: 14px; margin-bottom: 4px; }
.armsa-act-d { margin: 0; color: #646970; font-size: 12.5px; line-height: 1.45; }
.armsa-act-foot form { margin: 0; }
.armsa-act-foot .button { width: 100%; text-align: center; justify-content: center; }
.armsa-act-why { margin: 6px 0 0; font-size: 12px; color: #b32d2e; line-height: 1.4; }
</style>
