<?php
/**
 * Assets handler for enqueuing CSS and JavaScript
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TP_Assets {

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Only enqueue on pages with our shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'tp_link_shortener')) {
            return;
        }

        // Enqueue Bootstrap CSS
        wp_enqueue_style(
            'tp-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );

        // Enqueue Font Awesome
        wp_enqueue_style(
            'tp-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );

        // Enqueue custom CSS
        wp_enqueue_style(
            'tp-link-shortener',
            TP_LINK_SHORTENER_PLUGIN_URL . 'assets/css/frontend.css',
            array('tp-bootstrap'),
            TP_LINK_SHORTENER_VERSION
        );

        // Enqueue QRCode.js library
        wp_enqueue_script(
            'tp-qrcode',
            'https://cdn.jsdelivr.net/npm/qrcodejs2@0.0.2/qrcode.min.js',
            array(),
            '0.0.2',
            true
        );

        // Enqueue Bootstrap JS
        wp_enqueue_script(
            'tp-bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
        );

        // Enqueue custom JS
        wp_enqueue_script(
            'tp-link-shortener-js',
            TP_LINK_SHORTENER_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'tp-qrcode', 'tp-bootstrap-js'),
            TP_LINK_SHORTENER_VERSION,
            true
        );

        // Localize script with AJAX URL and settings
        wp_localize_script('tp-link-shortener-js', 'tpLinkShortener', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tp_link_shortener_nonce'),
            'domain' => TP_Link_Shortener::get_domain(),
            'isPremiumOnly' => TP_Link_Shortener::is_premium_only(),
            'strings' => array(
                'creating' => __('Creating...', 'tp-link-shortener'),
                'success' => __('Link created successfully!', 'tp-link-shortener'),
                'error' => __('Error creating link. Please try again.', 'tp-link-shortener'),
                'invalidUrl' => __('Please enter a valid URL', 'tp-link-shortener'),
                'keyTaken' => __('This shortcode is already taken', 'tp-link-shortener'),
                'premiumOnly' => __('Custom shortcodes are only available for premium members', 'tp-link-shortener'),
                'copied' => __('Copied!', 'tp-link-shortener'),
            ),
        ));
    }
}
