/**
 * Traffic Portal Link Shortener - Frontend JavaScript
 * Form handling, AJAX submission, and QR code generation
 */

(function($) {
    'use strict';

    // Main plugin object
    const TPLinkShortener = {
        // Elements
        $form: null,
        $submitBtn: null,
        $destinationInput: null,
        $customKeyInput: null,
        $loading: null,
        $errorMessage: null,
        $resultSection: null,
        $shortUrlOutput: null,
        $copyBtn: null,
        $qrContainer: null,
        $downloadQrBtn: null,

        // State
        qrCode: null,
        lastShortUrl: '',

        /**
         * Initialize
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.$form = $('#tp-shortener-form');
            this.$submitBtn = $('#tp-submit-btn');
            this.$destinationInput = $('#tp-destination');
            this.$customKeyInput = $('#tp-custom-key');
            this.$loading = $('#tp-loading');
            this.$errorMessage = $('#tp-error-message');
            this.$resultSection = $('#tp-result-section');
            this.$shortUrlOutput = $('#tp-short-url-output');
            this.$copyBtn = $('#tp-copy-btn');
            this.$qrContainer = $('#tp-qr-code-container');
            this.$downloadQrBtn = $('#tp-download-qr-btn');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            this.$form.on('submit', this.handleSubmit.bind(this));
            this.$copyBtn.on('click', this.copyToClipboard.bind(this));
            this.$downloadQrBtn.on('click', this.downloadQRCode.bind(this));
        },

        /**
         * Handle form submission
         */
        handleSubmit: function(e) {
            e.preventDefault();

            // Get form data
            const destination = this.$destinationInput.val().trim();
            const customKey = this.$customKeyInput.val().trim();

            // Validate
            if (!this.validateUrl(destination)) {
                this.showError(tpLinkShortener.strings.invalidUrl);
                return;
            }

            // Show loading
            this.setLoadingState(true);
            this.hideError();
            this.hideResult();

            // Prepare data
            const data = {
                action: 'tp_create_link',
                nonce: tpLinkShortener.nonce,
                destination: destination,
                custom_key: customKey
            };

            // Send AJAX request
            $.ajax({
                url: tpLinkShortener.ajaxUrl,
                type: 'POST',
                data: data,
                success: this.handleSuccess.bind(this),
                error: this.handleError.bind(this),
                complete: function() {
                    this.setLoadingState(false);
                }.bind(this)
            });
        },

        /**
         * Handle successful response
         */
        handleSuccess: function(response) {
            if (response.success && response.data) {
                const shortUrl = response.data.short_url;
                this.lastShortUrl = shortUrl;

                // Display result
                this.$shortUrlOutput.val(shortUrl);
                this.showResult();

                // Generate QR code
                this.generateQRCode(shortUrl);

                // Reset form
                this.$destinationInput.val('');
                this.$customKeyInput.val('');
            } else {
                this.showError(response.data.message || tpLinkShortener.strings.error);
            }
        },

        /**
         * Handle error response
         */
        handleError: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            this.showError(tpLinkShortener.strings.error);
        },

        /**
         * Validate URL format
         */
        validateUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        /**
         * Set loading state
         */
        setLoadingState: function(loading) {
            if (loading) {
                this.$submitBtn.prop('disabled', true);
                this.$loading.show();
            } else {
                this.$submitBtn.prop('disabled', false);
                this.$loading.hide();
            }
        },

        /**
         * Show error message
         */
        showError: function(message) {
            this.$errorMessage.html('<i class="fas fa-exclamation-circle"></i> ' + message).show();
        },

        /**
         * Hide error message
         */
        hideError: function() {
            this.$errorMessage.hide();
        },

        /**
         * Show result section
         */
        showResult: function() {
            this.$resultSection.slideDown(300);
        },

        /**
         * Hide result section
         */
        hideResult: function() {
            this.$resultSection.hide();
        },

        /**
         * Generate QR Code
         */
        generateQRCode: function(url) {
            // Clear existing QR code
            this.$qrContainer.empty();

            // Create new container div
            const qrDiv = $('<div>').attr('id', 'qr-code-' + Date.now());
            this.$qrContainer.append(qrDiv);

            // Generate QR code
            try {
                this.qrCode = new QRCode(qrDiv[0], {
                    text: url,
                    width: 156,
                    height: 156,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            } catch (e) {
                console.error('QR Code generation failed:', e);
            }
        },

        /**
         * Copy short URL to clipboard
         */
        copyToClipboard: function() {
            this.$shortUrlOutput.select();
            document.execCommand('copy');

            // Visual feedback
            const originalText = this.$copyBtn.html();
            this.$copyBtn.html('<i class="fas fa-check"></i> Copied!');

            setTimeout(function() {
                this.$copyBtn.html(originalText);
            }.bind(this), 2000);
        },

        /**
         * Download QR Code
         */
        downloadQRCode: function() {
            if (!this.qrCode) {
                return;
            }

            // Get the canvas
            const canvas = this.$qrContainer.find('canvas')[0];
            if (!canvas) {
                return;
            }

            // Convert to blob and download
            canvas.toBlob(function(blob) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'qr-code-' + Date.now() + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#tp-shortener-form').length) {
            TPLinkShortener.init();
        }
    });

})(jQuery);
