<?php
/**
 * Shortcode Template - Matches screenshot design
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$is_premium_only = TP_Link_Shortener::is_premium_only();
$domain = isset($atts['domain']) ? esc_attr($atts['domain']) : TP_Link_Shortener::get_domain();
?>

<div class="tp-link-shortener-wrapper">
    <div class="tp-header">
        <h2 class="tp-title">
            <i class="fas fa-torii-gate"></i>
            <?php esc_html_e('Make a key to your virtual gate', 'tp-link-shortener'); ?>
        </h2>
    </div>

    <form id="tp-shortener-form" class="tp-shortener-form">
        <!-- Main URL Input -->
        <div class="tp-form-group tp-url-input-group">
            <div class="tp-input-wrapper">
                <span class="tp-input-icon">
                    <i class="fas fa-globe"></i>
                </span>
                <input
                    type="url"
                    id="tp-destination"
                    name="destination"
                    class="tp-form-control"
                    placeholder="http://..."
                    required
                />
            </div>
            <button type="submit" class="tp-btn tp-btn-primary" id="tp-submit-btn">
                <i class="fas fa-key"></i>
                <?php esc_html_e('Register', 'tp-link-shortener'); ?>
            </button>
        </div>

        <!-- Trial Message -->
        <div class="tp-trial-message">
            <p>
                <?php esc_html_e('Be aware that your trial shortener expires in 24 hours since created. In order to keep it and to have a lot of extra services, please create an account.', 'tp-link-shortener'); ?>
            </p>
            <div class="tp-action-buttons">
                <button type="button" class="tp-btn tp-btn-register">
                    <i class="fas fa-user-plus"></i>
                    <?php esc_html_e('Register', 'tp-link-shortener'); ?>
                </button>
                <span class="tp-or"><?php esc_html_e('or', 'tp-link-shortener'); ?></span>
                <button type="button" class="tp-btn tp-btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php esc_html_e('Login', 'tp-link-shortener'); ?>
                </button>
            </div>
        </div>

        <!-- Just Name It Section -->
        <div class="tp-section tp-name-section">
            <h3 class="tp-section-title">
                <i class="fas fa-signature"></i>
                <?php esc_html_e('Just name it', 'tp-link-shortener'); ?>
            </h3>
            <p class="tp-section-description">
                <?php esc_html_e('Choose your own meaningful word or easy-to-remember code. Define as many aliases or synonyms as you want. Random keys are available also, but how about /6MagicTricks/ instead of https://www.youtube.com/watch?v=EqCeqYTpJpE Choose a short, easy to remember word or generate a random combination of letters. Provide the destination. No registration needed! Unique personalized FREE link shortener - no registration, no payment. Just use it.', 'tp-link-shortener'); ?>
            </p>
        </div>

        <!-- Custom Shortcode Input (only show if not premium-only OR user is premium) -->
        <?php if (!$is_premium_only || is_user_logged_in()): ?>
        <div class="tp-form-group tp-custom-key-group">
            <label for="tp-custom-key" class="tp-label">
                <i class="fas fa-edit"></i>
                <?php esc_html_e('Custom Shortcode (Optional)', 'tp-link-shortener'); ?>
            </label>
            <input
                type="text"
                id="tp-custom-key"
                name="custom_key"
                class="tp-form-control"
                placeholder="<?php esc_attr_e('6MagicTricks', 'tp-link-shortener'); ?>"
                pattern="[a-zA-Z0-9\.\-_]+"
            />
            <small class="tp-help-text">
                <?php esc_html_e('Leave empty to generate a random code', 'tp-link-shortener'); ?>
            </small>
        </div>
        <?php endif; ?>

        <!-- Result Section (hidden initially) -->
        <div id="tp-result-section" class="tp-result-section" style="display: none;">
            <div class="tp-success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php esc_html_e('Link created successfully!', 'tp-link-shortener'); ?></span>
            </div>

            <div class="tp-result-content">
                <div class="tp-short-url-display">
                    <label><?php esc_html_e('Your Short URL:', 'tp-link-shortener'); ?></label>
                    <div class="tp-url-copy-wrapper">
                        <input
                            type="text"
                            id="tp-short-url-output"
                            class="tp-form-control"
                            readonly
                        />
                        <button type="button" class="tp-btn tp-btn-copy" id="tp-copy-btn">
                            <i class="fas fa-copy"></i>
                            <?php esc_html_e('Copy', 'tp-link-shortener'); ?>
                        </button>
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="tp-qr-section">
                    <label><?php esc_html_e('QR Code:', 'tp-link-shortener'); ?></label>
                    <div id="tp-qr-code-container" class="tp-qr-code-container"></div>
                    <button type="button" class="tp-btn tp-btn-download-qr" id="tp-download-qr-btn">
                        <i class="fas fa-download"></i>
                        <?php esc_html_e('Download QR Code', 'tp-link-shortener'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="tp-loading" class="tp-loading" style="display: none;">
            <div class="tp-spinner"></div>
            <span><?php esc_html_e('Creating your short link...', 'tp-link-shortener'); ?></span>
        </div>

        <!-- Error Message -->
        <div id="tp-error-message" class="tp-error-message" style="display: none;"></div>
    </form>
</div>
