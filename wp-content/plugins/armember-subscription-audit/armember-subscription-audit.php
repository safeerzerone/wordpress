<?php
/**
 * Plugin Name:       ARMember Subscription Audit
 * Description:       Audit of ARMember members, subscriptions and payment history (Phase 1, read-only) plus payment-gateway sync readiness, safe ARMember mapping and a background sync queue (Phase 2). Fully independent of other custom plugins.
 * Version:           2.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Zerone Consulting
 * License:           GPL-2.0-or-later
 * Text Domain:       armember-subscription-audit
 *
 * @package ARMSA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARMSA_VERSION', '2.0.1' );
define( 'ARMSA_FILE', __FILE__ );
define( 'ARMSA_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARMSA_URL', plugin_dir_url( __FILE__ ) );

// Phase 1.
require_once ARMSA_DIR . 'includes/class-payment-history.php';
require_once ARMSA_DIR . 'includes/class-subscription-audit.php';
require_once ARMSA_DIR . 'includes/class-member-repository.php';
require_once ARMSA_DIR . 'includes/class-admin-assets.php';

// Phase 2: core (WordPress-free where possible).
require_once ARMSA_DIR . 'includes/sync/class-status.php';
require_once ARMSA_DIR . 'includes/sync/class-http.php';
require_once ARMSA_DIR . 'includes/sync/class-installer.php';
require_once ARMSA_DIR . 'includes/sync/class-logger.php';
require_once ARMSA_DIR . 'includes/gateways/abstract-gateway-adapter.php';
require_once ARMSA_DIR . 'includes/gateways/class-stripe-client.php';
require_once ARMSA_DIR . 'includes/gateways/class-adapter-stripe-native.php';
require_once ARMSA_DIR . 'includes/gateways/class-adapter-paypal-native.php';
require_once ARMSA_DIR . 'includes/gateways/class-adapter-woo-stripe.php';
require_once ARMSA_DIR . 'includes/gateways/class-adapter-unsupported.php';
require_once ARMSA_DIR . 'includes/gateways/class-gateway-factory.php';
require_once ARMSA_DIR . 'includes/sync/class-assessor.php';
// Phase 2: WordPress-bound.
require_once ARMSA_DIR . 'includes/sync/class-secrets-provider.php';
require_once ARMSA_DIR . 'includes/sync/class-collector.php';
require_once ARMSA_DIR . 'includes/sync/class-arm-writer.php';
require_once ARMSA_DIR . 'includes/sync/class-sync-service.php';
require_once ARMSA_DIR . 'includes/sync/class-queue.php';
require_once ARMSA_DIR . 'includes/admin/class-sync-admin.php';

require_once ARMSA_DIR . 'includes/class-admin-menu.php';
require_once ARMSA_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( '\ARMSA\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\ARMSA\Plugin', 'deactivate' ) );

\ARMSA\Plugin::instance()->boot();
