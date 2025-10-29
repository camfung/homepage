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

<div class="tp-link-shortener-wrapper container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card border-0 shadow-sm tp-card">
                <div class="card-body p-4 p-xl-5">
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
                                    <div class="input-group input-group-lg">
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
                                    <button type="submit" class="btn btn-lg tp-btn tp-btn-primary w-100 w-md-auto" id="tp-submit-btn">
                                        <i class="fas fa-key me-2"></i>
                                        <?php esc_html_e('Register', 'tp-link-shortener'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Trial Message -->
                        <div class="tp-trial-message alert d-flex flex-column flex-md-row align-items-md-center gap-3 mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <span class="tp-trial-icon">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                                <p class="mb-0">
                                    <?php esc_html_e('Be aware that your trial shortener expires in 24 hours since created. In order to keep it and to have a lot of extra services, please create an account.', 'tp-link-shortener'); ?>
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

                        <!-- Just Name It Section -->
                        <div class="tp-section tp-name-section rounded-4 p-4 mb-4">
                            <h3 class="tp-section-title h5 mb-3">
                                <i class="fas fa-signature me-2"></i>
                                <?php esc_html_e('Just name it', 'tp-link-shortener'); ?>
                            </h3>
                            <p class="tp-section-description mb-0">
                                <?php esc_html_e('Choose your own meaningful word or easy-to-remember code. Define as many aliases or synonyms as you want. Random keys are available also, but how about /6MagicTricks/ instead of https://www.youtube.com/watch?v=EqCeqYTpJpE Choose a short, easy to remember word or generate a random combination of letters. Provide the destination. No registration needed! Unique personalized FREE link shortener - no registration, no payment. Just use it.', 'tp-link-shortener'); ?>
                            </p>
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
                                class="form-control form-control-lg tp-form-control"
                                placeholder="<?php esc_attr_e('6MagicTricks', 'tp-link-shortener'); ?>"
                                pattern="[a-zA-Z0-9\.\-_]+"
                            />
                            <div class="form-text tp-help-text">
                                <?php esc_html_e('Leave empty to generate a random code', 'tp-link-shortener'); ?>
                            </div>
                        </div>
                        <?php endif; ?>

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
