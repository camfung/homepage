<?php
/**
 * Shortcode Handler
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TP_Shortcode {

    /**
     * Assets handler
     */
    private $assets;

    /**
     * Constructor
     */
    public function __construct(TP_Assets $assets) {
        $this->assets = $assets;
        add_shortcode('tp_link_shortener', array($this, 'render_shortcode'));
    }

    /**
     * Render the shortcode
     */
    public function render_shortcode($atts): string {
        $atts = shortcode_atts(array(
            'domain' => TP_Link_Shortener::get_domain(),
        ), $atts);

        // Enqueue assets
        $this->assets->enqueue_assets();

        // Start output buffering
        ob_start();

        // Include template
        include TP_LINK_SHORTENER_PLUGIN_DIR . 'templates/shortcode-template.php';

        return ob_get_clean();
    }
}
