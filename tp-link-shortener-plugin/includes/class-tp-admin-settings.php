<?php
/**
 * Admin Settings Page
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TP_Admin_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        // Hooks are registered in main plugin class
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Traffic Portal Link Shortener', 'tp-link-shortener'),
            __('Link Shortener', 'tp-link-shortener'),
            'manage_options',
            'tp-link-shortener',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        register_setting('tp_link_shortener_settings', 'tp_link_shortener_premium_only');
        register_setting('tp_link_shortener_settings', 'tp_link_shortener_user_id');
        register_setting('tp_link_shortener_settings', 'tp_link_shortener_domain');

        // Add settings section
        add_settings_section(
            'tp_link_shortener_main_section',
            __('General Settings', 'tp-link-shortener'),
            array($this, 'render_section_description'),
            'tp-link-shortener'
        );

        // Premium only field
        add_settings_field(
            'tp_link_shortener_premium_only',
            __('Premium-Only Custom Shortcodes', 'tp-link-shortener'),
            array($this, 'render_premium_only_field'),
            'tp-link-shortener',
            'tp_link_shortener_main_section'
        );

        // User ID field
        add_settings_field(
            'tp_link_shortener_user_id',
            __('API User ID', 'tp-link-shortener'),
            array($this, 'render_user_id_field'),
            'tp-link-shortener',
            'tp_link_shortener_main_section'
        );

        // Domain field
        add_settings_field(
            'tp_link_shortener_domain',
            __('Short Link Domain', 'tp-link-shortener'),
            array($this, 'render_domain_field'),
            'tp-link-shortener',
            'tp_link_shortener_main_section'
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if settings saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'tp_link_shortener_messages',
                'tp_link_shortener_message',
                __('Settings saved successfully.', 'tp-link-shortener'),
                'success'
            );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php settings_errors('tp_link_shortener_messages'); ?>

            <div class="tp-admin-container">
                <div class="tp-admin-main">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('tp_link_shortener_settings');
                        do_settings_sections('tp-link-shortener');
                        submit_button(__('Save Settings', 'tp-link-shortener'));
                        ?>
                    </form>
                </div>

                <div class="tp-admin-sidebar">
                    <div class="tp-admin-box">
                        <h3><?php esc_html_e('About', 'tp-link-shortener'); ?></h3>
                        <p><?php esc_html_e('Traffic Portal Link Shortener allows you to create short, memorable links using the Traffic Portal API.', 'tp-link-shortener'); ?></p>
                        <p><strong><?php esc_html_e('Version:', 'tp-link-shortener'); ?></strong> <?php echo TP_LINK_SHORTENER_VERSION; ?></p>
                    </div>

                    <div class="tp-admin-box">
                        <h3><?php esc_html_e('Configuration Required', 'tp-link-shortener'); ?></h3>
                        <p><?php esc_html_e('Add this to your wp-config.php:', 'tp-link-shortener'); ?></p>
                        <pre>define('API_KEY', 'your-api-key');</pre>
                    </div>

                    <div class="tp-admin-box">
                        <h3><?php esc_html_e('Usage', 'tp-link-shortener'); ?></h3>
                        <p><?php esc_html_e('Use the shortcode on any page:', 'tp-link-shortener'); ?></p>
                        <code>[tp_link_shortener]</code>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .tp-admin-container {
                display: grid;
                grid-template-columns: 1fr 300px;
                gap: 20px;
                margin-top: 20px;
            }
            .tp-admin-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 15px;
                margin-bottom: 20px;
            }
            .tp-admin-box h3 {
                margin-top: 0;
            }
            .tp-admin-box pre,
            .tp-admin-box code {
                background: #f0f0f1;
                padding: 5px 8px;
                display: inline-block;
                font-family: monospace;
            }
            @media (max-width: 782px) {
                .tp-admin-container {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }

    /**
     * Render section description
     */
    public function render_section_description() {
        echo '<p>' . esc_html__('Configure the Traffic Portal Link Shortener settings below.', 'tp-link-shortener') . '</p>';
    }

    /**
     * Render premium only field
     */
    public function render_premium_only_field() {
        $value = get_option('tp_link_shortener_premium_only', '0');
        ?>
        <label>
            <input
                type="checkbox"
                name="tp_link_shortener_premium_only"
                value="1"
                <?php checked('1', $value); ?>
            />
            <?php esc_html_e('Enable premium-only custom shortcodes', 'tp-link-shortener'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, only logged-in premium members can enter custom shortcodes. Non-premium users will get automatically generated random codes.', 'tp-link-shortener'); ?>
        </p>
        <?php
    }

    /**
     * Render user ID field
     */
    public function render_user_id_field() {
        $value = get_option('tp_link_shortener_user_id', '125');
        ?>
        <input
            type="number"
            name="tp_link_shortener_user_id"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            min="1"
        />
        <p class="description">
            <?php esc_html_e('The Traffic Portal user ID to use for API requests.', 'tp-link-shortener'); ?>
        </p>
        <?php
    }

    /**
     * Render domain field
     */
    public function render_domain_field() {
        $value = get_option('tp_link_shortener_domain', 'dev.trfc.link');
        ?>
        <input
            type="text"
            name="tp_link_shortener_domain"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="dev.trfc.link"
        />
        <p class="description">
            <?php esc_html_e('The domain to use for short links (e.g., dev.trfc.link or trfc.link).', 'tp-link-shortener'); ?>
        </p>
        <?php
    }
}
