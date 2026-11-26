<?php
/**
 * Plugin Name:       ARMember Subscription Audit
 * Description:       Read-only admin audit of ARMember members, their recurring subscription configuration and payment history. Phase 1 (no Stripe calls, no writes).
 * Version:           1.0.0
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

define( 'ARMSA_VERSION', '1.0.0' );
define( 'ARMSA_FILE', __FILE__ );
define( 'ARMSA_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARMSA_URL', plugin_dir_url( __FILE__ ) );

require_once ARMSA_DIR . 'includes/class-payment-history.php';
require_once ARMSA_DIR . 'includes/class-subscription-audit.php';
require_once ARMSA_DIR . 'includes/class-member-repository.php';
require_once ARMSA_DIR . 'includes/class-admin-assets.php';
require_once ARMSA_DIR . 'includes/class-admin-menu.php';
require_once ARMSA_DIR . 'includes/class-plugin.php';

\ARMSA\Plugin::instance()->boot();
