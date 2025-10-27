<?php
/**
 * Main plugin class
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TP_Link_Shortener {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Assets handler
     */
    private $assets;

    /**
     * Shortcode handler
     */
    private $shortcode;

    /**
     * Admin settings
     */
    private $admin;

    /**
     * API handler
     */
    private $api_handler;

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components
        $this->assets = new TP_Assets();
        $this->api_handler = new TP_API_Handler();
        $this->shortcode = new TP_Shortcode($this->assets);
        $this->admin = new TP_Admin_Settings();

        // Register hooks
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this->assets, 'enqueue_assets'));
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        add_action('admin_init', array($this->admin, 'register_settings'));
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'tp-link-shortener',
            false,
            dirname(TP_LINK_SHORTENER_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Get API key from WordPress config
     */
    public static function get_api_key(): string {
        // Try to get from wp-config.php constant
        if (defined('API_KEY')) {
            return API_KEY;
        }

        // Fallback to empty string
        return '';
    }

    /**
     * Get API endpoint
     */
    public static function get_api_endpoint(): string {
        if (defined('TP_API_ENDPOINT')) {
            return TP_API_ENDPOINT;
        }

        return 'https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev';
    }

    /**
     * Get user ID for API calls
     */
    public static function get_user_id(): int {
        $uid = get_option('tp_link_shortener_user_id', '125');
        return (int) $uid;
    }

    /**
     * Get domain for short links
     */
    public static function get_domain(): string {
        return get_option('tp_link_shortener_domain', 'dev.trfc.link');
    }

    /**
     * Check if premium-only mode is enabled
     */
    public static function is_premium_only(): bool {
        return (bool) get_option('tp_link_shortener_premium_only', false);
    }
}
