# Integration checklist (staging copy, WordPress + DB running, Stripe test keys)

1. Activate/reload admin: tables `armsa_jobs/logs/changes/assessments/mappings` exist.
2. Members list: new columns/filters render; no gateway request occurs (check Stripe dashboard logs).
3. Native Stripe member, ids in payment log only: Retrieve Gateway Information → job completes → *Ready for Sync*; Queue Synchronisation → `arm_user_plan_{id}` gains `arm_subscr_id` + `arm_stripe.*`, all other keys identical, timeline shows every step, status *Renewal Readiness Verified* (or *Gateway Linked – Renewal Not Verified* if webhook endpoint missing).
4. Two Stripe customers with the member e-mail: status *Multiple Matching Records*; bulk sync skips the member with a reason.
5. PayPal member with `I-` id and sandbox NVP credentials: *Gateway Verified*, overall *Gateway Linked – Renewal Not Verified*.
6. WooCommerce member with/without token: verified / *Missing Gateway Information*; no sync button.
7. Queue: click Queue Synchronisation twice → second is "duplicate"; set Stripe key invalid → job retries 3× then *failed*; Retry Synchronisation re-queues it; kill PHP mid-job → lock expires and job recovers.
8. Confirm no new Stripe objects / charges were created and payment-log row count is unchanged.
9. CSV export opens safely in Excel (cells starting with `= + - @` are prefixed with `'`).
