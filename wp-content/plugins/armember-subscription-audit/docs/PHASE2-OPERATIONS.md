# Phase 2 – operations, supported models, limitations, test results

Read `PHASE2-PLAN.md` first for the source-level findings. This file is the runbook.

## 1. What each payment model supports

| Model | Adapter | Gateway lookup (read-only) | Admin input | Sync writes to ARMember | Renewal "Verified"? |
|---|---|---|---|---|---|
| A. Native ARMember Stripe | `Adapter_Stripe_Native` | subscription, customer subscriptions, e-mail customer discovery, default PaymentMethod, webhook endpoints | customer id, subscription id (staged) | `arm_subscr_id`, `arm_stripe.customer_id`, `arm_stripe.transaction_id` – only when empty | Yes, statically: id in plan meta + active sub + payment method + enabled ARMember webhook endpoint with `invoice.payment_succeeded` |
| D. ARMember PayPal (IPN) | `Adapter_Paypal_Native` | NVP `GetRecurringPaymentsProfileDetails` for a **known** `I-…` id only | profile id (staged) | `arm_subscr_id`, `arm_paypal.arm_subscr_id` – only when empty | Never – IPN URL of an existing profile is not readable via any API |
| B/C. WooCommerce (+ Stripe tokens / direct sub) | `Adapter_Woo_Stripe` | customer, token PaymentMethod ownership, direct subscription | none | none (report only) | Never – ARMember has no renewal processor for this model; it is site-specific code this plugin does not call |
| GoCardless, 2Checkout, Authorize.net, bank transfer, WC PayPal gateways (`ppcp-gateway`), unknown | `Adapter_Unsupported` | none | none | none | n/a → Needs Review |

### Unsupported / manual
* PayPal: e-mail search (no supported API), Subscriptions API, billing agreements, vault tokens, WooCommerce PayPal gateways.
* WooCommerce: WPSwings / WooCommerce Subscriptions records are not inspected.
* Changing `arm_user_gateway` / `arm_payment_mode` (imported members with empty/`manual` gateway are reported as *Incorrect Payment Configuration*).
* Overwriting a different existing ARMember reference; rewriting payment-log rows; creating any subscription, mandate, agreement or charge.

## 2. Workflow
1. **Assess Member** – instant, local only; no gateway contacted, nothing changed.
2. **Retrieve Gateway Information / Verify** – queued job, read-only gateway calls, result stored.
3. **Update Missing Information** – only for "Enter manually" fields: validated, checked against ARMember, confirmed by tick-box, audited (before/after, admin, time), **staged** in `armsa_mappings`, verification queued. Nothing reaches ARMember yet.
4. **Queue Synchronisation** – enabled only for *Ready for Sync*. The worker re-assesses live, then writes through `Arm_Writer`, re-reads and verifies.
5. Afterwards: *Sync Completed* only if renewal prerequisites are confirmed; otherwise **Gateway Linked – ARMember Renewal Not Verified**.

Ownership: never inferred from e-mail alone. The id must already be linked to the member by ARMember/WooCommerce data, or an administrator must tick "I verified this record belongs to this member" (audited).

## 3. Queue & cron
* Tables `armsa_jobs` (unique `active_key` prevents duplicate active jobs), `armsa_logs`, `armsa_changes` (append-only), `armsa_assessments`, `armsa_mappings`; kept on deactivation.
* Transport: Action Scheduler async action if available, else WP-Cron hook `armsa_process_queue` (every minute).
* Worker: atomic claim, max 5 jobs / 45 s per run, 5-min lock with stale recovery, 3 attempts with 2/4/8-min back-off for API errors; ineligible members end `skipped`.
* Production cron:
  ```php
  define( 'DISABLE_WP_CRON', true ); // wp-config.php
  ```
  ```
  * * * * * cd /path/to/wordpress && php wp-cron.php >/dev/null 2>&1
  ```
  On Windows use Task Scheduler to run `php wp-cron.php` every minute.

## 4. Security
`manage_options` + nonce on every handler; prepared SQL; sanitised input; escaped output. Gateway keys are read in-process from ARMember / WooCommerce Stripe settings for read-only calls and are never rendered, logged or stored (`Logger::redact()` also strips keys, bearer tokens, NVP secrets, card-like numbers).

## 5. Tests
`php tests/run-tests.php` (no WordPress/DB needed): **58 passed, 0 failed**. Covers Stripe, PayPal, WooCommerce and unsupported scenarios, read-only HTTP guarantee, redaction, step logging, `Arm_Writer` safety (no overwrite, whitelist, preservation of other keys, idempotency, verification, audit rows) and static checks (no `arsenal` reference, nonce+capability on all handlers, writes only in `Arm_Writer`).

### NOT tested
The local MySQL server was down. Nothing was run against a database, WordPress, real ARMember data or real gateways: dbDelta install, queue claim / duplicate key / retry / stale lock, admin-post handlers, bulk and CSV export, list filters, view rendering, and end-to-end bulk-sync and retry scenarios. Run `tests/INTEGRATION.md` on a staging copy before production.
