=== ARMember Subscription Audit ===
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Read-only admin audit of ARMember members, recurring subscription configuration and payment history.

== Description ==
Phase 1: a standalone, read-only audit page (ARMember Subscription Audit > Members).
It never writes to the database, never calls Stripe, never registers webhooks or cron events,
and has no dependency on any other custom plugin.

== Installation ==
1. Zip the `armember-subscription-audit` folder.
2. WordPress Admin > Plugins > Add New Plugin > Upload Plugin, then Activate.
3. Open "ARMember Subscription Audit" in the admin menu (administrators / manage_options only).

== Changelog ==
= 1.0.0 =
* Initial release.
