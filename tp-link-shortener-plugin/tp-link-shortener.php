<?php
/**
 * Plugin Name: Traffic Portal Link Shortener
 * Plugin URI: https://trafficportal.dev
 * Description: Create short links using Traffic Portal API with QR code generation. Simple interface for creating memorable short URLs.
 * Version: 1.0.0
 * Author: Traffic Portal
 * Author URI: https://trafficportal.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tp-link-shortener
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TP_LINK_SHORTENER_VERSION', '1.0.0');
define('TP_LINK_SHORTENER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TP_LINK_SHORTENER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TP_LINK_SHORTENER_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoload Traffic Portal API Client
require_once TP_LINK_SHORTENER_PLUGIN_DIR . 'includes/autoload.php';

// Include plugin files
require_once TP_LINK_SHORTENER_PLUGIN_DIR . 'includes/class-tp-link-shortener.php';
require_once TP_LINK_SHORTENER_PLUGIN_DIR . 'includes/class-tp-api-handler.php';
require_once TP_LINK_SHORTENER_PLUGIN_DIR . 'includes/class-tp-shortcode.php';
require_once TP_LINK_SHORTENER_PLUGIN_DIR . 'includes/class-tp-admin-settings.php';
require_once TP_LINK_SHORTENER_PLUGIN_DIR . 'includes/class-tp-assets.php';

/**
 * Initialize the plugin
 */
function tp_link_shortener_init() {
    $plugin = new TP_Link_Shortener();
    $plugin->init();
}
add_action('plugins_loaded', 'tp_link_shortener_init');

/**
 * Activation hook
 */
function tp_link_shortener_activate() {
    // Set default options
    if (!get_option('tp_link_shortener_premium_only')) {
        add_option('tp_link_shortener_premium_only', '0');
    }

    if (!get_option('tp_link_shortener_user_id')) {
        add_option('tp_link_shortener_user_id', '125');
    }

    if (!get_option('tp_link_shortener_domain')) {
        add_option('tp_link_shortener_domain', 'dev.trfc.link');
    }

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'tp_link_shortener_activate');

/**
 * Deactivation hook
 */
function tp_link_shortener_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'tp_link_shortener_deactivate');
