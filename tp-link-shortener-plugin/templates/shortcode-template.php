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

<div class="tp-link-shortener-wrapper py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card border-0 shadow-sm tp-card">
                <div class="card-body p-3 p-xl-4">
                    <div class="tp-header mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <span class="tp-title-icon">
                                <i class="fas fa-torii-gate"></i>
                            </span>
                            <h2 class="tp-title mb-0">
                                <?php esc_html_e('Make a key to your virtual gate', 'tp-link-shortener'); ?>
                            </h2>
                        </div>
                    </div>

                    <form id="tp-shortener-form" class="tp-shortener-form">
                        <!-- Main URL Input -->
                        <div class="tp-form-group mb-4">
                            <label for="tp-destination" class="tp-label mb-2">
                                <i class="fas fa-globe me-2 text-secondary"></i>
                                <?php esc_html_e('Destination URL', 'tp-link-shortener'); ?>
                            </label>
                            <div class="row g-3 align-items-center">
                                <div class="col-md">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-link text-muted"></i>
                                        </span>
                                        <input
                                            type="url"
                                            id="tp-destination"
                                            name="destination"
                                            class="form-control tp-form-control border-start-0"
                                            placeholder="https://example.com/long-url"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="col-md-auto">
                                    <button type="submit" class="btn tp-btn tp-btn-primary w-100 w-md-auto" id="tp-submit-btn">
                                        <i class="fas fa-key me-2"></i>
                                        <?php esc_html_e('Register', 'tp-link-shortener'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Shortcode Input (only show if not premium-only OR user is premium) -->
                        <?php if (!$is_premium_only || is_user_logged_in()): ?>
                        <div class="tp-form-group tp-custom-key-group mb-4">
                            <label for="tp-custom-key" class="tp-label mb-2">
                                <i class="fas fa-edit me-2"></i>
                                <?php esc_html_e('Custom Shortcode (Optional)', 'tp-link-shortener'); ?>
                            </label>
                            <input
                                type="text"
                                id="tp-custom-key"
                                name="custom_key"
                                class="form-control tp-form-control"
                                placeholder="<?php esc_attr_e('6MagicTricks', 'tp-link-shortener'); ?>"
                                pattern="[a-zA-Z0-9\.\-_]+"
                            />
                            <div class="form-text tp-help-text">
                                <?php esc_html_e('Leave empty to generate a random code', 'tp-link-shortener'); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Trial Message -->
                        <?php if (!is_user_logged_in()): ?>
                        <div class="tp-trial-message alert d-flex flex-column flex-md-row align-items-md-center gap-3 mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <span class="tp-trial-icon">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                                <p class="mb-0">
                                    <?php esc_html_e('Trial links expire in 24 hours. Create an account to keep them active and unlock extras.', 'tp-link-shortener'); ?>
                                </p>
                            </div>
                            <div class="tp-action-buttons d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 ms-md-auto">
                                <button type="button" class="btn tp-btn tp-btn-register w-100">
                                    <i class="fas fa-user-plus me-2"></i>
                                    <?php esc_html_e('Register', 'tp-link-shortener'); ?>
                                </button>
                                <span class="tp-or text-center mx-sm-2"><?php esc_html_e('or', 'tp-link-shortener'); ?></span>
                                <button type="button" class="btn tp-btn tp-btn-login w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    <?php esc_html_e('Login', 'tp-link-shortener'); ?>
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="tp-trial-message alert d-flex align-items-start gap-3 mb-4">
                            <span class="tp-trial-icon">
                                <i class="fas fa-user-check"></i>
                            </span>
                            <p class="mb-0">
                                <?php esc_html_e('You are logged in, so your shortened links stay active and ready to manage in your dashboard.', 'tp-link-shortener'); ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <!-- Naming Guidance Sections -->
                        <div class="tp-section tp-name-section rounded-4 p-3 p-md-4 mb-4">
                            <h3 class="tp-section-title h5 mb-2">
                                <i class="fas fa-signature me-2"></i>
                                <?php esc_html_e('Pick a short keyword', 'tp-link-shortener'); ?>
                            </h3>
                            <p class="tp-section-description mb-0">
                                <?php esc_html_e('Use a word, code, or acronym that you and your visitors will remember in seconds.', 'tp-link-shortener'); ?>
                            </p>
                        </div>

                        <div class="tp-section rounded-4 p-3 p-md-4 mb-4">
                            <h3 class="tp-section-title h5 mb-2">
                                <i class="fas fa-bullseye me-2"></i>
                                <?php esc_html_e('Keep it relevant', 'tp-link-shortener'); ?>
                            </h3>
                            <p class="tp-section-description mb-0">
                                <?php esc_html_e('Match the key to your campaign, location, or offer so it stays meaningful at a glance.', 'tp-link-shortener'); ?>
                            </p>
                        </div>

                        <div class="tp-section rounded-4 p-3 p-md-4 mb-4">
                            <h3 class="tp-section-title h5 mb-2">
                                <i class="fas fa-rocket me-2"></i>
                                <?php esc_html_e('Share it instantly', 'tp-link-shortener'); ?>
                            </h3>
                            <p class="tp-section-description mb-0">
                                <?php esc_html_e('Generate a random key when you are in a hurry and pair it with a QR code for quick scans.', 'tp-link-shortener'); ?>
                            </p>
                        </div>

                        <!-- Result Section (hidden initially) -->
                        <div id="tp-result-section" class="tp-result-section card border-0 shadow-sm rounded-4 mb-4 d-none">
                            <div class="card-body p-4">
                                <div class="tp-success-message alert alert-success d-flex align-items-center gap-3 mb-4">
                                    <i class="fas fa-check-circle fs-4"></i>
                                    <span class="fw-semibold"><?php esc_html_e('Link created successfully!', 'tp-link-shortener'); ?></span>
                                </div>

                                <div class="tp-result-content row g-4">
                                    <div class="tp-short-url-display col-12">
                                        <label class="form-label fw-semibold"><?php esc_html_e('Your Short URL', 'tp-link-shortener'); ?></label>
                                        <div class="input-group">
                                            <input
                                                type="text"
                                                id="tp-short-url-output"
                                                class="form-control tp-form-control"
                                                readonly
                                            />
                                            <button type="button" class="btn tp-btn tp-btn-copy" id="tp-copy-btn">
                                                <i class="fas fa-copy me-2"></i>
                                                <?php esc_html_e('Copy', 'tp-link-shortener'); ?>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- QR Code Section -->
                                    <div class="tp-qr-section col-12 col-md-6">
                                        <label class="form-label fw-semibold"><?php esc_html_e('QR Code', 'tp-link-shortener'); ?></label>
                                        <div id="tp-qr-code-container" class="tp-qr-code-container rounded-4"></div>
                                        <button type="button" class="btn tp-btn tp-btn-download-qr mt-3 w-100" id="tp-download-qr-btn">
                                            <i class="fas fa-download me-2"></i>
                                            <?php esc_html_e('Download QR Code', 'tp-link-shortener'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading Indicator -->
                        <div id="tp-loading" class="tp-loading d-none text-center py-4">
                            <div class="spinner-border text-primary tp-spinner" role="status">
                                <span class="visually-hidden"><?php esc_html_e('Loading...', 'tp-link-shortener'); ?></span>
                            </div>
                            <p class="mt-3 mb-0 text-muted"><?php esc_html_e('Creating your short link...', 'tp-link-shortener'); ?></p>
                        </div>

                        <!-- Error Message -->
                        <div id="tp-error-message" class="tp-error-message alert alert-danger d-none" role="alert"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
