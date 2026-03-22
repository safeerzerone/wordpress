<?php
/*
    Plugin Name: AffiliatePress - Affiliate Marketing
    Description: AffiliatePress is the reliable WordPress plugin to create and manage affiliate programs. Track Affiliate links and monitor commissions effortlessly within WordPress.
    Version: 2.3
    Requires at least: 5.3.0
    Requires PHP:      7.0
    Plugin URI: https://www.affiliatepressplugin.com/
    Author: Repute Infosystems
    Text Domain: affiliatepress-affiliate-marketing
    Domain Path: /languages
    Author URI: https://www.reputeinfosystems.com/
    License: GPLv2 or later
    License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !defined( 'AFFILIATEPRESS_DIR_NAME') ){
    define('AFFILIATEPRESS_DIR_NAME', dirname(plugin_basename(__FILE__)));
}

if( !defined( 'AFFILIATEPRESS_DIR' ) ){
    define('AFFILIATEPRESS_DIR', plugin_dir_path(__FILE__));
}

require_once AFFILIATEPRESS_DIR . '/autoload.php';

?>