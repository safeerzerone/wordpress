# ARMember Subscription Audit – Phase 2 technical plan & findings

Written before any Phase 2 code. Everything marked **[source]** was read in the installed code; **[not verified]**
means it could not be checked in this environment (see "Environment limits").

## 1. Environment limits (important)

* The local MySQL server was **not running** while this was built (`wp-load.php` → "Error establishing a database
  connection"), so **no live data was inspected**. Table/column assumptions come from installed source and Phase 1.
  Run the test suite and a read-only "Assess" on a staging copy before trusting results on production.
* The "technical analysis document" mentioned in the brief is **not in the repository**; nothing was compared with it.
* Two ARMember plugins are present (`armember` 7.1 and `armember-membership` 5.2). Both are assumed to use the same
  `arm_user_plan_{id}` structure; only `armember` 7.1 was read.
* `arsenal-settings` was **read for understanding only** (to learn that the WooCommerce models are reconciled by custom
  code). The new code never calls, hooks into, or requires it.

## 2. Phase 1 (reused, not rebuilt)

`Member_Repository` (user+plan rows, pagination, filters), `Payment_History` (payment log, schema-checked),
`Subscription_Audit` (pure classifier, gateway/mode labels), views `members-list.php`, `member-details.php`,
`Admin_Menu`. Phase 1 is strictly read-only; Phase 2 adds writes **only** in new, nonce/capability-gated handlers.

## 3. How renewals really work (from installed source)

| Model | Stored in `arm_user_plan_{pid}` | How ARMember finds the member on a renewal | Renewal driver |
|---|---|---|---|
| **A. Native ARMember Stripe** (`arm_user_gateway=stripe`, `arm_payment_mode=auto_debit_subscription`) | `arm_subscr_id` = `sub_…`, `arm_stripe.customer_id` = `cus_…`, `arm_stripe.transaction_id` = `sub_…` [source: class.arm_gateways_stripe_sca.php:1340] | Stripe webhook `?arm-listener=arm_stripe_notify` (also `arm_stripe_api`, `stripe`). `arm_modify_payment_webhook_data` searches `usermeta.meta_value LIKE %<sub id>%`, else `arm_payment_log.arm_token` [source: class.arm_membership_setup.php:8106+]. Legacy handler additionally looks up entries by customer email + plan. | Stripe `invoice.payment_succeeded` / `customer.subscription.updated` → ARMember updates next-due & count. |
| **B. WooCommerce (gateway `woocommerce`, `manual_subscription`)** | `arm_user_gateway=woocommerce`, no external id required | WooCommerce order → `arm_woocommerce_add_member` on `woocommerce_order_status_completed` [source: class.arm_wocommerce_feature.php:54]. | Renewal orders / WC gateway tokens. ARMember core has **no** renewal scheduler for this model; on this site it is a custom reconciliation (arsenal-settings cron) which the plugin must not call ⇒ **never reported "Renewal Processing Verified"**. |
| **C. Direct Stripe sub with WooCommerce-style metadata** (`woocommerce`/`manual_subscription` meta + `auto_debit_subscription` payment log, `sub_` token) | Reference lives in `arm_payment_log.arm_token` | Same custom reconciliation as B. | Custom ⇒ renewal **not verifiable** by this plugin. |
| **D. PayPal (ARMember core)** | `arm_subscr_id` and `arm_paypal.arm_subscr_id` = `I-…` | **Legacy PayPal Standard IPN** `?arm-listener=arm_paypal_notify`; `subscr_id` matched by `usermeta LIKE` then `arm_payment_log.arm_token`; entry id from IPN `custom` [source: class.arm_gateways_paypal.php:599-770]. | PayPal-side recurring profile sends IPN. |

Other PayPal plugins installed (`woocommerce-paypal-payments`, `pymntpl-paypal-woocommerce`) are WooCommerce
gateways; ARMember does not integrate with them. PayPal Subscriptions API / vault tokens are therefore **out of
scope ⇒ "Unsupported Payment Model"** unless ARMember's own `arm_paypal` model is in use. GoCardless
(two WC plugins installed) and any other gateway ⇒ **Unsupported Payment Model / manual review**.

## 4. Gateway lookups actually used (all read-only GET)

* **Stripe** (documented): `GET /v1/subscriptions/{id}`, `GET /v1/customers?email=` (discovery only),
  `GET /v1/subscriptions?customer=&status=all`, `GET /v1/payment_methods?customer=&limit=`,
  `GET /v1/webhook_endpoints` (is ARMember's listener URL registered with the needed events).
  Key source: ARMember's active Stripe gateway options (model A) or `woocommerce_stripe_settings` (model B/C).
  Keys are read in-process, never rendered, logged or stored.
* **PayPal** (documented NVP): `GetRecurringPaymentsProfileDetails` with the credentials ARMember already holds,
  **only to verify a known `I-…` profile**. There is **no supported API to search profiles by e-mail**, so e-mail
  lookup is reported as unsupported ⇒ "Manual Input Required".
* **WooCommerce**: `WC_Payment_Tokens::get_customer_tokens`, `get_user_option('_stripe_customer_id')` (WC Stripe
  stores it with `update_user_option`), order meta `_stripe_customer_id`, `_stripe_source_id`, `_stripe_mandate_id`.
  `WC_Subscriptions` is **not** installed; the WPSwings subscription plugin is not inspected (documented limitation).

## 5. Architecture (all inside this plugin)

```
includes/sync/   class-status, class-field-registry, class-collector (WP reads → facts),
                 class-assessor (pure), class-sync-service (assess/stage/sync), class-arm-writer,
                 class-logger (append-only), class-queue, class-installer, class-http (injectable)
includes/gateways/ abstract-gateway-adapter, adapter-stripe-native, adapter-woo-stripe,
                 adapter-paypal-native, adapter-unsupported, class-gateway-factory
includes/admin/  class-sync-admin (admin-post handlers, bulk, export), view partials
tests/           plain-PHP runner (no WP needed) for pure logic
```

Design rules: collector (WP-dependent) is separate from the assessor/adapters (pure) so every model is testable
with fake facts + fake HTTP. Assessment never writes to ARMember/WooCommerce.

### Tables (prefix dynamic; dbDelta)
`armsa_jobs` (queue, unique `active_key` = duplicate prevention, attempts, lock), `armsa_logs` (append-only
timeline; the code exposes no update/delete), `armsa_changes` (admin before/after audit), `armsa_assessments`
(latest assessment + sync state per user/plan, drives the list), `armsa_mappings` (staged admin-supplied values).
**Why `armsa_mappings`:** ARMember has no place for an *unverified* candidate id; writing it into `arm_subscr_id`
would let ARMember's webhook act on it. Staged values are applied to ARMember only by Sync after verification.
No credentials are stored anywhere in these tables.

### Sync writes (only these, via `get_user_meta`/`update_user_meta` with the array merged – the same API ARMember uses)
Model A: `arm_subscr_id`, `arm_stripe.customer_id`, `arm_stripe.transaction_id`.
Model D: `arm_subscr_id`, `arm_paypal.arm_subscr_id`.
Never written: gateway, payment mode, next-due, completed count, payment log, WooCommerce data. A gateway/mode
mismatch ⇒ "Incorrect Payment Configuration" / manual review (no auto-fix). No charge, subscription, mandate or
agreement is ever created (adapters only issue GET / read-only NVP calls; a unit test asserts this).

### Queue
Action Scheduler is **not** installed standalone here (only bundled in WooCommerce) – the queue uses
`as_enqueue_async_action` when available, otherwise WP-Cron (`armsa_process_queue`, every minute). Jobs are claimed
with an atomic `UPDATE … WHERE status='queued'`; retry with back-off (max 3) for gateway/API errors; unsafe records
become `skipped`, not failed. Production: disable WP-Cron page-load trigger and run
`* * * * * php /path/wp-cron.php` (or `wp cron event run --due-now`).

## 6. Unsupported / manual scenarios (by design)
PayPal e-mail search; PayPal Subscriptions API, vault tokens, WC PayPal gateways; GoCardless; other gateways;
WooCommerce-model renewal verification; WPSwings subscriptions; payment-mode changes; payment-log rewrites.
