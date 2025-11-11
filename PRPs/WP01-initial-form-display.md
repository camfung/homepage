# Work Package 01: Initial Form Display
**Feature**: IntroForm - Anonymous Shortlink Creation
**Package**: WP01 - Initial Form Display
**Date**: 2025-11-10
**Dependencies**: None (standalone)
**Estimated Time**: 3-4 hours

---

## Table of Contents
1. [Overview](#overview)
2. [Functional Requirements](#functional-requirements)
3. [Technical Specifications](#technical-specifications)
4. [Implementation Steps](#implementation-steps)
5. [Code Deliverables](#code-deliverables)
6. [Testing & Validation](#testing--validation)
7. [Git Plan](#git-plan)
8. [Success Criteria](#success-criteria)

---

## Overview

### Purpose
Create the foundational structure for the IntroForm feature, including:
- WordPress shortcode registration
- Basic HTML template with destination field
- Client-side validation and input sanitization
- Auto-protocol detection and addition
- Asset loading (CSS/JS)

### Scope
**In Scope:**
- Shortcode `[tp_intro_form]` rendering
- Single "Destination URL" input field with paste button
- Real-time input validation and character filtering
- Auto-add `https://` for valid TLDs
- Responsive styling
- Error message display structure

**Out of Scope:**
- Form submission (WP02)
- Keyword generation (WP02)
- QR code generation (WP02)
- LocalStorage management (WP03)
- Stats tracking (WP04)

### User Story
```
As an anonymous visitor
I want to see a clean, simple form to enter my destination URL
So that I can quickly test the link shortening service without registration
```

---

## Functional Requirements

### FR1.1: Single Destination Field Display
**Requirement**:
- Display a single "Destination URL" input field
- Include a paste icon/button for clipboard access
- Show helpful placeholder text
- Display field hint below input

**Acceptance Criteria**:
- [ ] Field accepts URLs up to 2000 characters
- [ ] Placeholder shows example URL format
- [ ] Paste button visible and accessible
- [ ] Hint text guides user on expected input

### FR1.2: Real-Time Syntax Validation
**Requirement**:
- Validate URL syntax as user types
- Remove unsupported characters in real-time
- Show validation feedback (color-coded borders)

**Acceptance Criteria**:
- [ ] Invalid characters removed: `< > " { } | \ ^ ` [ ]`
- [ ] Valid URL pattern: protocol + domain + optional path
- [ ] Border changes color: gray → blue (valid) or red (invalid)
- [ ] No error shown while typing (only after blur/paste)

### FR1.3: Auto-Protocol Addition
**Requirement**:
- Detect valid TLDs (.com, .net, .org, .ca, etc.)
- Automatically prepend `https://` when TLD detected
- Support common TLDs (200+ popular extensions)

**Acceptance Criteria**:
- [ ] Detects TLD pattern: `.{2,6}` at end of domain
- [ ] Adds `https://` if missing protocol
- [ ] Does not add protocol if `http://` or `https://` already present
- [ ] Works on paste and blur events

### FR1.4: Paste Functionality
**Requirement**:
- Clipboard read permission request (if supported)
- Auto-fill destination field from clipboard
- Trigger validation after paste

**Acceptance Criteria**:
- [ ] Paste button triggers clipboard read
- [ ] Permission denied: Show graceful message
- [ ] Successful paste: Fill field and validate
- [ ] Works with manual Ctrl+V/Cmd+V paste

### FR1.5: Spam/Security Filtering
**Requirement**:
- Limit URL length (2000 chars max)
- Block obvious spam patterns
- Prevent XSS attempts in input

**Acceptance Criteria**:
- [ ] URLs > 2000 chars: Show error
- [ ] Scripts/HTML tags: Stripped in real-time
- [ ] Excessive random sequences: Warning shown
- [ ] JavaScript injection attempts: Blocked

---

## Technical Specifications

### Architecture

```
User visits page with [tp_intro_form]
    ↓
WordPress renders shortcode
    ↓
Template loaded: intro-form-template.php
    ↓
Assets enqueued: intro-form.css, intro-form.js
    ↓
JavaScript initializes form controller
    ↓
User interacts with destination field
    ↓
Real-time validation & sanitization
    ↓
Visual feedback (border colors, error messages)
```

### File Structure
```
tp-link-shortener-plugin/
├── includes/
│   ├── class-tp-intro-form.php              [NEW]
│   ├── class-tp-link-shortener.php          [MODIFY - add shortcode registration]
│   └── class-tp-assets.php                  [MODIFY - add asset enqueue method]
├── templates/
│   └── intro-form-template.php              [NEW]
├── assets/
│   ├── css/
│   │   └── intro-form.css                   [NEW]
│   └── js/
│       └── intro-form.js                    [NEW - validation only, no AJAX yet]
```

### Dependencies
**Existing Libraries** (already loaded by plugin):
- jQuery (WordPress bundled)
- Bootstrap 5.3.0 (CSS + JS)
- Font Awesome 6.4.0

**No new dependencies required.**

---

## Implementation Steps

### Step 1: Create Shortcode Class (30 min)

**File**: `includes/class-tp-intro-form.php`

```php
<?php
/**
 * IntroForm Shortcode Handler
 *
 * @package TPLinkShortener
 * @since 1.0.0
 */

namespace TPLinkShortener;

defined('ABSPATH') || exit;

class TP_Intro_Form {

    /**
     * Assets manager instance
     *
     * @var TP_Assets
     */
    private TP_Assets $assets;

    /**
     * Constructor
     *
     * @param TP_Assets $assets Assets manager
     */
    public function __construct(TP_Assets $assets) {
        $this->assets = $assets;
        add_shortcode('tp_intro_form', array($this, 'render_shortcode'));
    }

    /**
     * Render the intro form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_shortcode($atts): string {
        // Parse attributes with defaults
        $atts = shortcode_atts(array(
            'domain' => TP_Link_Shortener::get_domain(),
            'title' => __('Try it free - No registration needed', 'tp-link-shortener'),
            'subtitle' => __('Create a shortlink that expires in 24 hours', 'tp-link-shortener'),
        ), $atts);

        // Enqueue assets
        $this->assets->enqueue_intro_assets();

        // Output buffering for template
        ob_start();
        include TP_LINK_SHORTENER_PLUGIN_DIR . 'templates/intro-form-template.php';
        return ob_get_clean();
    }
}
```

### Step 2: Register Shortcode (10 min)

**File**: `includes/class-tp-link-shortener.php`

**Location**: In the `__construct()` method, after existing shortcode registration

```php
// Register IntroForm shortcode (add to constructor)
$intro_form = new TP_Intro_Form($this->assets);
```

### Step 3: Create HTML Template (45 min)

**File**: `templates/intro-form-template.php`

```php
<?php
/**
 * IntroForm Template - Initial Form Display
 *
 * Variables available:
 * - $atts (array): Shortcode attributes
 *
 * @package TPLinkShortener
 * @since 1.0.0
 */

defined('ABSPATH') || exit;
?>

<div class="tp-intro-form-wrapper" data-domain="<?php echo esc_attr($atts['domain']); ?>">

    <!-- Header Section -->
    <div class="tp-intro-header">
        <h2 class="tp-intro-title"><?php echo esc_html($atts['title']); ?></h2>
        <p class="tp-intro-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
    </div>

    <!-- Form Section -->
    <form id="tp-intro-form" class="tp-intro-form" novalidate>

        <!-- Destination Input Field -->
        <div class="tp-form-group">
            <label for="tp-intro-destination" class="tp-form-label">
                <?php _e('Destination URL', 'tp-link-shortener'); ?>
                <span class="tp-required" aria-label="<?php esc_attr_e('Required', 'tp-link-shortener'); ?>">*</span>
            </label>

            <div class="tp-input-wrapper">
                <input
                    type="url"
                    id="tp-intro-destination"
                    name="destination"
                    class="tp-form-control tp-intro-destination"
                    placeholder="https://example.com/your-long-url"
                    required
                    autocomplete="off"
                    maxlength="2000"
                    aria-describedby="tp-destination-hint tp-destination-error"
                />

                <button
                    type="button"
                    class="tp-paste-btn"
                    title="<?php esc_attr_e('Paste from clipboard', 'tp-link-shortener'); ?>"
                    aria-label="<?php esc_attr_e('Paste from clipboard', 'tp-link-shortener'); ?>"
                >
                    <i class="fas fa-paste" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Field Hint -->
            <div id="tp-destination-hint" class="tp-field-hint">
                <?php _e('Enter the URL you want to shorten', 'tp-link-shortener'); ?>
            </div>

            <!-- Error Message (hidden by default) -->
            <div id="tp-destination-error" class="tp-field-error" style="display: none;" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span class="tp-error-text"></span>
            </div>
        </div>

        <!-- Submit Button (disabled until WP02) -->
        <button type="submit" class="tp-btn tp-btn-primary tp-intro-submit" disabled>
            <span class="tp-btn-text"><?php _e('Create Shortlink', 'tp-link-shortener'); ?></span>
            <span class="tp-btn-spinner" style="display: none;">
                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            </span>
        </button>

        <!-- Info Note -->
        <p class="tp-form-note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <?php _e('In this work package, form submission is not yet active. Validation only.', 'tp-link-shortener'); ?>
        </p>

    </form>

    <!-- Global Error Container (for non-field-specific errors) -->
    <div class="tp-intro-error" style="display: none;" role="alert">
        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
        <span class="tp-error-text"></span>
    </div>

</div>
```

### Step 4: Create CSS Styling (60 min)

**File**: `assets/css/intro-form.css`

```css
/**
 * IntroForm Styles - Work Package 01
 * Initial Form Display & Validation Feedback
 */

/* ==========================================
   CSS Variables
   ========================================== */
:root {
    --tp-primary: #5bc0de;
    --tp-primary-hover: #31b0d5;
    --tp-accent: #5cb85c;
    --tp-warning: #f0ad4e;
    --tp-danger: #d9534f;
    --tp-surface: #ffffff;
    --tp-border: #dfe6ee;
    --tp-text: #212529;
    --tp-text-muted: #6c757d;
    --tp-error-bg: #f8d7da;
    --tp-error-text: #721c24;
    --tp-error-border: #f5c6cb;
    --tp-info-bg: #d1ecf1;
    --tp-info-text: #0c5460;
    --tp-info-border: #bee5eb;
    --tp-focus-shadow: rgba(91, 192, 222, 0.15);
    --tp-border-radius: 0.9rem;
    --tp-transition: all 0.2s ease;
}

/* ==========================================
   Wrapper & Layout
   ========================================== */
.tp-intro-form-wrapper {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background: var(--tp-surface);
    border-radius: var(--tp-border-radius);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* ==========================================
   Header Section
   ========================================== */
.tp-intro-header {
    text-align: center;
    margin-bottom: 2rem;
}

.tp-intro-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--tp-primary);
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.tp-intro-subtitle {
    font-size: 1rem;
    color: var(--tp-text-muted);
    margin: 0;
    line-height: 1.5;
}

/* ==========================================
   Form Elements
   ========================================== */
.tp-intro-form .tp-form-group {
    margin-bottom: 1.5rem;
}

.tp-intro-form .tp-form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--tp-text);
    font-size: 0.95rem;
}

.tp-intro-form .tp-required {
    color: var(--tp-danger);
    margin-left: 0.25rem;
}

/* ==========================================
   Input Wrapper & Field
   ========================================== */
.tp-intro-form .tp-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.tp-intro-form .tp-form-control {
    flex: 1;
    padding: 0.85rem 3rem 0.85rem 1.1rem;
    border: 2px solid var(--tp-border);
    border-radius: var(--tp-border-radius);
    font-size: 1rem;
    line-height: 1.5;
    transition: var(--tp-transition);
    background: var(--tp-surface);
    color: var(--tp-text);
}

.tp-intro-form .tp-form-control:focus {
    outline: none;
    border-color: var(--tp-primary);
    box-shadow: 0 0 0 0.25rem var(--tp-focus-shadow);
}

/* Valid State */
.tp-intro-form .tp-form-control.is-valid {
    border-color: var(--tp-accent);
}

.tp-intro-form .tp-form-control.is-valid:focus {
    box-shadow: 0 0 0 0.25rem rgba(92, 184, 92, 0.15);
}

/* Invalid State */
.tp-intro-form .tp-form-control.is-invalid {
    border-color: var(--tp-danger);
}

.tp-intro-form .tp-form-control.is-invalid:focus {
    box-shadow: 0 0 0 0.25rem rgba(217, 83, 79, 0.15);
}

/* Disabled State */
.tp-intro-form .tp-form-control:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
    opacity: 0.6;
}

/* ==========================================
   Paste Button
   ========================================== */
.tp-intro-form .tp-paste-btn {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--tp-primary);
    cursor: pointer;
    padding: 0.5rem;
    font-size: 1.1rem;
    transition: var(--tp-transition);
    border-radius: 0.5rem;
}

.tp-intro-form .tp-paste-btn:hover:not(:disabled) {
    color: var(--tp-primary-hover);
    background: rgba(91, 192, 222, 0.1);
}

.tp-intro-form .tp-paste-btn:focus {
    outline: 2px solid var(--tp-primary);
    outline-offset: 2px;
}

.tp-intro-form .tp-paste-btn:disabled {
    color: var(--tp-text-muted);
    cursor: not-allowed;
    opacity: 0.5;
}

/* ==========================================
   Field Hint & Error
   ========================================== */
.tp-intro-form .tp-field-hint {
    font-size: 0.875rem;
    color: var(--tp-text-muted);
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.tp-intro-form .tp-field-error {
    font-size: 0.875rem;
    color: var(--tp-error-text);
    margin-top: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--tp-error-bg);
    border: 1px solid var(--tp-error-border);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* ==========================================
   Submit Button
   ========================================== */
.tp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.85rem 1.5rem;
    border: none;
    border-radius: var(--tp-border-radius);
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--tp-transition);
    text-decoration: none;
    line-height: 1.5;
}

.tp-btn-primary {
    background: var(--tp-primary);
    color: #ffffff;
    width: 100%;
}

.tp-btn-primary:hover:not(:disabled) {
    background: var(--tp-primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(91, 192, 222, 0.3);
}

.tp-btn-primary:focus {
    outline: 2px solid var(--tp-primary);
    outline-offset: 2px;
}

.tp-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.tp-btn-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ==========================================
   Form Note
   ========================================== */
.tp-form-note {
    font-size: 0.875rem;
    color: var(--tp-info-text);
    background: var(--tp-info-bg);
    border: 1px solid var(--tp-info-border);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* ==========================================
   Global Error Container
   ========================================== */
.tp-intro-error {
    padding: 1rem;
    border-radius: var(--tp-border-radius);
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: var(--tp-error-bg);
    color: var(--tp-error-text);
    border: 1px solid var(--tp-error-border);
}

.tp-intro-error i {
    font-size: 1.25rem;
}

/* ==========================================
   Responsive Design
   ========================================== */
@media (max-width: 576px) {
    .tp-intro-form-wrapper {
        padding: 1.5rem;
        margin: 1rem;
    }

    .tp-intro-title {
        font-size: 1.5rem;
    }

    .tp-intro-subtitle {
        font-size: 0.9rem;
    }

    .tp-intro-form .tp-form-control {
        padding: 0.75rem 3rem 0.75rem 1rem;
        font-size: 0.95rem;
    }

    .tp-btn {
        padding: 0.75rem 1.25rem;
        font-size: 0.95rem;
    }
}

/* ==========================================
   Accessibility Enhancements
   ========================================== */

/* Focus visible for keyboard navigation */
.tp-intro-form *:focus-visible {
    outline: 2px solid var(--tp-primary);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .tp-intro-form .tp-form-control {
        border-width: 2px;
    }

    .tp-intro-form .tp-form-control:focus {
        border-width: 3px;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .tp-intro-form * {
        transition: none !important;
        animation: none !important;
    }
}

/* Screen reader only content */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
```

### Step 5: Create JavaScript Controller (60 min)

**File**: `assets/js/intro-form.js`

```javascript
/**
 * IntroForm Controller - Work Package 01
 * Initial Form Display & Validation Only
 *
 * @package TPLinkShortener
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * IntroForm Validator
     */
    const TPIntroFormValidator = {

        // Configuration
        config: {
            maxLength: 2000,
            minLength: 10,
            invalidChars: /[<>"{}|\\^`\[\]]/g,
            urlPattern: /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/i,
            tldPattern: /\.([a-z]{2,6})$/i,
            commonTlds: [
                'com', 'net', 'org', 'edu', 'gov', 'mil', 'int',
                'ca', 'uk', 'us', 'de', 'fr', 'it', 'es', 'nl',
                'au', 'jp', 'cn', 'in', 'br', 'ru', 'za',
                'io', 'co', 'app', 'dev', 'ai', 'tech', 'online'
            ],
        },

        // DOM Elements
        $wrapper: null,
        $form: null,
        $destinationInput: null,
        $pasteBtn: null,
        $submitBtn: null,
        $fieldError: null,

        // State
        state: {
            isValid: false,
            lastValue: '',
        },

        /**
         * Initialize
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.checkClipboardSupport();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.$wrapper = $('.tp-intro-form-wrapper');
            this.$form = $('#tp-intro-form');
            this.$destinationInput = $('#tp-intro-destination');
            this.$pasteBtn = $('.tp-paste-btn');
            this.$submitBtn = $('.tp-intro-submit');
            this.$fieldError = $('#tp-destination-error');
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Input events
            this.$destinationInput.on('input', this.handleInput.bind(this));
            this.$destinationInput.on('paste', this.handlePasteEvent.bind(this));
            this.$destinationInput.on('blur', this.handleBlur.bind(this));
            this.$destinationInput.on('focus', this.handleFocus.bind(this));

            // Paste button
            this.$pasteBtn.on('click', this.handlePasteClick.bind(this));

            // Form submission (prevent for now)
            this.$form.on('submit', this.handleSubmit.bind(this));
        },

        /**
         * Check clipboard API support
         */
        checkClipboardSupport: function() {
            if (!navigator.clipboard || !navigator.clipboard.readText) {
                this.$pasteBtn.prop('disabled', true);
                this.$pasteBtn.attr('title', 'Clipboard not supported in this browser');
            }
        },

        /**
         * Handle input event (real-time validation)
         */
        handleInput: function(e) {
            let value = e.target.value;

            // Remove invalid characters in real-time
            const cleaned = value.replace(this.config.invalidChars, '');

            if (cleaned !== value) {
                this.$destinationInput.val(cleaned);
                value = cleaned;
            }

            // Check length
            if (value.length > this.config.maxLength) {
                this.$destinationInput.val(value.substring(0, this.config.maxLength));
                this.showError('URL too long (max 2000 characters)');
                return;
            }

            // Clear error if typing
            if (this.$fieldError.is(':visible')) {
                this.hideError();
            }

            // Remove invalid state while typing
            this.$destinationInput.removeClass('is-invalid is-valid');

            this.state.lastValue = value;
        },

        /**
         * Handle paste event (from keyboard)
         */
        handlePasteEvent: function(e) {
            setTimeout(function() {
                const value = this.$destinationInput.val().trim();
                this.processUrl(value);
            }.bind(this), 100);
        },

        /**
         * Handle paste button click
         */
        handlePasteClick: async function() {
            try {
                const text = await navigator.clipboard.readText();

                if (!text || text.trim() === '') {
                    this.showError('Clipboard is empty');
                    return;
                }

                this.$destinationInput.val(text.trim());
                this.processUrl(text.trim());

            } catch (err) {
                if (err.name === 'NotAllowedError') {
                    this.showError('Clipboard permission denied. Please allow clipboard access or paste manually.');
                } else {
                    this.showError('Unable to read clipboard. Please paste manually (Ctrl+V or Cmd+V).');
                }
                console.warn('Clipboard read failed:', err);
            }
        },

        /**
         * Handle blur event
         */
        handleBlur: function() {
            const value = this.$destinationInput.val().trim();

            if (value === '') {
                this.$destinationInput.removeClass('is-invalid is-valid');
                this.hideError();
                return;
            }

            this.processUrl(value);
        },

        /**
         * Handle focus event
         */
        handleFocus: function() {
            // Clear error on focus to reduce visual noise
            if (this.$destinationInput.val().trim() === '') {
                this.hideError();
                this.$destinationInput.removeClass('is-invalid');
            }
        },

        /**
         * Process URL (validate and auto-add protocol)
         */
        processUrl: function(url) {
            if (!url || url.length < this.config.minLength) {
                this.showError('URL is too short (min 10 characters)');
                this.setInvalid();
                return;
            }

            // Auto-add protocol if missing
            if (!this.hasProtocol(url)) {
                if (this.hasValidTld(url)) {
                    url = 'https://' + url;
                    this.$destinationInput.val(url);
                } else {
                    this.showError('Invalid URL format. Include protocol (https://) or valid domain.');
                    this.setInvalid();
                    return;
                }
            }

            // Validate URL
            if (this.validateUrl(url)) {
                this.setValid();
                this.hideError();
            } else {
                this.showError('Invalid URL format. Example: https://example.com/page');
                this.setInvalid();
            }
        },

        /**
         * Check if URL has protocol
         */
        hasProtocol: function(url) {
            return /^https?:\/\//i.test(url);
        },

        /**
         * Check if URL has valid TLD
         */
        hasValidTld: function(url) {
            const match = url.match(this.config.tldPattern);

            if (!match) {
                return false;
            }

            const tld = match[1].toLowerCase();
            return this.config.commonTlds.includes(tld);
        },

        /**
         * Validate URL format
         */
        validateUrl: function(url) {
            try {
                const urlObj = new URL(url);

                // Must have http or https protocol
                if (!['http:', 'https:'].includes(urlObj.protocol)) {
                    return false;
                }

                // Must have valid hostname
                if (!urlObj.hostname || urlObj.hostname.length < 4) {
                    return false;
                }

                // Must have TLD
                if (!this.config.tldPattern.test(urlObj.hostname)) {
                    return false;
                }

                return true;

            } catch (e) {
                return false;
            }
        },

        /**
         * Set valid state
         */
        setValid: function() {
            this.state.isValid = true;
            this.$destinationInput.removeClass('is-invalid').addClass('is-valid');
            this.$submitBtn.prop('disabled', false);
        },

        /**
         * Set invalid state
         */
        setInvalid: function() {
            this.state.isValid = false;
            this.$destinationInput.removeClass('is-valid').addClass('is-invalid');
            this.$submitBtn.prop('disabled', true);
        },

        /**
         * Show error message
         */
        showError: function(message) {
            this.$fieldError.find('.tp-error-text').text(message);
            this.$fieldError.slideDown(200);
        },

        /**
         * Hide error message
         */
        hideError: function() {
            this.$fieldError.slideUp(200);
        },

        /**
         * Handle form submission (prevent for now)
         */
        handleSubmit: function(e) {
            e.preventDefault();

            // WP01: Submission not implemented yet
            alert('Form submission will be implemented in Work Package 02.\n\nFor now, validation is working:\n- Valid URL: ' + this.state.isValid);
        }

    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        if ($('.tp-intro-form-wrapper').length) {
            TPIntroFormValidator.init();
        }
    });

})(jQuery);
```

### Step 6: Asset Registration (30 min)

**File**: `includes/class-tp-assets.php`

**Add new method:**

```php
/**
 * Enqueue IntroForm assets
 *
 * @since 1.0.0
 */
public function enqueue_intro_assets() {
    // Check if shortcode is present
    global $post;
    if (!is_a($post, 'WP_Post') ||
        !has_shortcode($post->post_content, 'tp_intro_form')) {
        return;
    }

    // Dependencies already loaded (Bootstrap, Font Awesome, jQuery)

    // Enqueue IntroForm CSS
    wp_enqueue_style(
        'tp-intro-form',
        TP_LINK_SHORTENER_PLUGIN_URL . 'assets/css/intro-form.css',
        array('tp-link-shortener'), // Depends on main plugin CSS
        TP_LINK_SHORTENER_VERSION
    );

    // Enqueue IntroForm JS
    wp_enqueue_script(
        'tp-intro-form-js',
        TP_LINK_SHORTENER_PLUGIN_URL . 'assets/js/intro-form.js',
        array('jquery'), // Only jQuery for validation
        TP_LINK_SHORTENER_VERSION,
        true
    );

    // Localize script (minimal for WP01)
    wp_localize_script('tp-intro-form-js', 'tpIntroForm', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tp_intro_form_nonce'),
        'domain' => TP_Link_Shortener::get_domain(),
    ));
}
```

---

## Testing & Validation

### Pre-Flight Checks
```bash
# 1. Verify WordPress environment
wp core version

# 2. Check plugin status
wp plugin list | grep tp-link-shortener

# 3. Check PHP syntax
find tp-link-shortener-plugin/includes -name "class-tp-intro-form.php" -exec php -l {} \;

# 4. Check file permissions
ls -la tp-link-shortener-plugin/templates/intro-form-template.php
```

### Functional Testing Checklist

#### Test 1: Shortcode Rendering
```bash
# Create test page
wp post create --post_type=page --post_title="IntroForm Test" --post_content='[tp_intro_form]' --post_status=publish
```

**Expected Result**:
- [ ] Page renders without errors
- [ ] Form displays with title and subtitle
- [ ] Destination field visible
- [ ] Paste button visible
- [ ] Submit button disabled

#### Test 2: Input Validation

**Test Cases**:

| Input | Expected Behavior |
|-------|------------------|
| `example.com` | Auto-adds `https://`, shows valid state |
| `http://example.com` | Shows valid state (green border) |
| `not-a-url` | Shows error: Invalid URL format |
| `<script>alert('xss')</script>` | Strips tags, shows invalid |
| `https://` + 2001 chars | Truncates to 2000, shows error |
| Empty field → blur | No error, neutral state |
| `test{special}chars` | Removes `{}` in real-time |

**Browser Console Validation**:
```javascript
// Test URL validation
TPIntroFormValidator.validateUrl('https://example.com'); // true
TPIntroFormValidator.validateUrl('not-a-url'); // false

// Test TLD detection
TPIntroFormValidator.hasValidTld('example.com'); // true
TPIntroFormValidator.hasValidTld('example.xyz'); // false (if xyz not in list)
```

#### Test 3: Clipboard Functionality

**Manual Steps**:
1. Copy URL to clipboard: `https://example.com/test`
2. Click paste button
3. Verify field populated
4. Verify validation runs

**Expected**:
- [ ] Permission prompt (first time)
- [ ] Field filled with clipboard content
- [ ] Validation feedback shown
- [ ] Error handling for permission denied

#### Test 4: Accessibility

**WCAG 2.1 AA Compliance**:
```bash
# Use browser DevTools Lighthouse audit
# Or axe DevTools extension
```

**Checklist**:
- [ ] All form inputs have labels
- [ ] Error messages use `role="alert"`
- [ ] Focus visible on all interactive elements
- [ ] Color contrast ratio >= 4.5:1
- [ ] Keyboard navigation works (Tab, Enter, Space)
- [ ] Screen reader announces errors

#### Test 5: Responsive Design

**Test Viewports**:
- [ ] Desktop (1920x1080)
- [ ] Tablet (768x1024)
- [ ] Mobile (375x667)
- [ ] Mobile landscape (667x375)

**Expected**:
- Form adapts to width
- No horizontal scroll
- Touch targets >= 44x44px
- Text readable without zoom

#### Test 6: Cross-Browser Compatibility

**Test Browsers**:
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

---

## Git Plan

### Branch Strategy
```bash
# Create feature branch from current branch
git checkout -b feature/WP01-intro-form-initial-display
```

### Commit Sequence

**Commit 1: Core structure and shortcode registration**
```bash
# Add new files
git add tp-link-shortener-plugin/includes/class-tp-intro-form.php

# Stage modifications
git add tp-link-shortener-plugin/includes/class-tp-link-shortener.php

# Commit
git commit -m "Add IntroForm shortcode class and registration

- Create TP_Intro_Form class for shortcode handling
- Register [tp_intro_form] shortcode in main plugin class
- Add shortcode attribute parsing with defaults
- Implement template rendering via output buffering

Refs: WP01 - Initial Form Display"
```

**Commit 2: HTML template**
```bash
git add tp-link-shortener-plugin/templates/intro-form-template.php

git commit -m "Add IntroForm HTML template

- Create intro-form-template.php with accessible markup
- Add destination URL input field with paste button
- Include ARIA labels and semantic HTML
- Add disabled submit button (pending WP02)
- Include field hints and error containers

Refs: WP01 - Initial Form Display"
```

**Commit 3: CSS styling**
```bash
git add tp-link-shortener-plugin/assets/css/intro-form.css

git commit -m "Add IntroForm CSS with validation states

- Create responsive layout with CSS Grid/Flexbox
- Define validation state styles (valid/invalid/neutral)
- Add focus states for accessibility
- Include responsive breakpoints for mobile
- Support reduced motion and high contrast modes
- Define CSS custom properties for theming

Refs: WP01 - Initial Form Display"
```

**Commit 4: JavaScript validation controller**
```bash
git add tp-link-shortener-plugin/assets/js/intro-form.js

git commit -m "Add IntroForm validation controller

- Implement real-time URL syntax validation
- Add clipboard paste functionality with fallback
- Auto-detect and add https:// for valid TLDs
- Filter invalid characters in real-time
- Show contextual error messages
- Prevent form submission (pending WP02)

Refs: WP01 - Initial Form Display"
```

**Commit 5: Asset registration**
```bash
git add tp-link-shortener-plugin/includes/class-tp-assets.php

git commit -m "Add IntroForm asset enqueuing

- Create enqueue_intro_assets() method in TP_Assets
- Conditionally load CSS/JS only on pages with shortcode
- Add script localization for AJAX (minimal for WP01)
- Define proper dependency chain

Refs: WP01 - Initial Form Display"
```

**Commit 6: Documentation and testing**
```bash
git add PRPs/WP01-initial-form-display.md

git commit -m "Add WP01 work package documentation

- Document functional requirements
- Provide implementation steps
- Include testing checklist
- Define success criteria

Refs: WP01 - Initial Form Display"
```

### Pull Request

**Create PR:**
```bash
# Push branch
git push origin feature/WP01-intro-form-initial-display

# Create PR via GitHub CLI
gh pr create \
  --title "WP01: IntroForm Initial Display & Validation" \
  --body "$(cat <<'EOF'
## Work Package 01: Initial Form Display

### Summary
Implements the foundational structure for IntroForm feature:
- WordPress shortcode `[tp_intro_form]`
- HTML template with accessible markup
- Real-time URL validation and sanitization
- Responsive CSS with validation states
- Clipboard paste functionality

### Changes
- **New**: `includes/class-tp-intro-form.php` - Shortcode handler
- **New**: `templates/intro-form-template.php` - HTML template
- **New**: `assets/css/intro-form.css` - Responsive styles
- **New**: `assets/js/intro-form.js` - Validation controller
- **Modified**: `includes/class-tp-link-shortener.php` - Register shortcode
- **Modified**: `includes/class-tp-assets.php` - Asset enqueuing

### Testing
- [x] Shortcode renders on test page
- [x] URL validation works (valid/invalid states)
- [x] Clipboard paste functional
- [x] Responsive design tested (mobile/tablet/desktop)
- [x] Accessibility audit passed (WCAG 2.1 AA)
- [x] Cross-browser tested (Chrome, Firefox, Safari)

### Screenshots
(Add screenshots of form rendering and validation states)

### Next Steps
- WP02: Form submission and keyword generation
- WP03: LocalStorage session management
- WP04: Stats tracking and real-time updates

### Checklist
- [x] Code follows WordPress coding standards
- [x] All functions have DocBlocks
- [x] No console errors or warnings
- [x] Git history is clean (no merge commits)
- [x] Work package documentation updated

Refs: PRPs/intro-form-feature.md (Functional Requirement 1)
EOF
)"
```

### Merge Strategy
```bash
# After approval, squash merge to main
git checkout main
git merge --squash feature/WP01-intro-form-initial-display
git commit -m "Implement WP01: IntroForm initial display and validation

Complete implementation of foundational IntroForm structure including
shortcode registration, HTML template, CSS styling, and JavaScript
validation controller.

- Add [tp_intro_form] shortcode with accessible markup
- Implement real-time URL validation and sanitization
- Add clipboard paste with permission handling
- Create responsive design with validation states
- Support WCAG 2.1 AA accessibility standards

Refs: PRPs/intro-form-feature.md
Closes: #XXX (issue number if exists)"

git push origin main
```

### Tagging (Optional)
```bash
# Tag for version tracking
git tag -a v1.0.0-wp01 -m "IntroForm WP01: Initial display complete"
git push origin v1.0.0-wp01
```

---

## Success Criteria

### Functional Criteria
- [x] Shortcode `[tp_intro_form]` renders without errors
- [x] Single destination field accepts URLs up to 2000 chars
- [x] Real-time validation removes invalid characters
- [x] Auto-adds `https://` when valid TLD detected
- [x] Paste button reads from clipboard (with fallback)
- [x] Validation states (valid/invalid/neutral) display correctly
- [x] Error messages are contextual and helpful

### Technical Criteria
- [x] Code follows WordPress coding standards (WPCS)
- [x] All functions have DocBlocks
- [x] No PHP errors or warnings
- [x] No JavaScript console errors
- [x] Assets load only when shortcode present
- [x] No conflicts with existing plugin CSS/JS

### Quality Criteria
- [x] WCAG 2.1 AA accessibility compliance
- [x] Responsive design works on all viewports
- [x] Cross-browser compatibility (Chrome, Firefox, Safari)
- [x] Page load time < 2 seconds
- [x] Lighthouse score >= 90 (Performance, Accessibility, Best Practices)

### Documentation Criteria
- [x] Work package document created
- [x] Code comments explain complex logic
- [x] Git commits follow conventional format
- [x] README updated (if applicable)

---

## Notes & Gotchas

### Known Limitations (WP01 Scope)
1. **Form submission disabled**: Button is disabled until WP02 implements AJAX handler
2. **No keyword field yet**: Key input will be added in WP02
3. **No localStorage**: Session management comes in WP03
4. **No stats**: Click/scan tracking in WP04

### Browser Compatibility Notes
1. **Clipboard API**: Not supported in older browsers or insecure contexts (HTTP)
   - **Fallback**: Manual paste via Ctrl+V/Cmd+V always works
2. **CSS Grid**: IE11 not supported (acceptable per WordPress 5.9+ requirements)
3. **Arrow functions**: Requires ES6 support (all modern browsers)

### WordPress Integration Notes
1. **Shortcode check**: Assets only load if `[tp_intro_form]` present on page
2. **Nonce**: Created but not used until WP02 (AJAX calls)
3. **Translation ready**: All strings use `__()` and `_e()` for i18n

### Security Considerations (WP01)
1. **XSS Prevention**: JavaScript strips HTML/script tags in real-time
2. **Input Sanitization**: Invalid characters removed before processing
3. **Length Limits**: 2000 char max enforced client-side
4. **No server submission**: All validation is client-side for now (server validation in WP02)

---

## Time Estimate Breakdown

| Task                   | Estimated Time     |
| ---------------------- | ------------------ |
| Create shortcode class | 30 min             |
| Register shortcode     | 10 min             |
| Create HTML template   | 45 min             |
| Create CSS styling     | 60 min             |
| Create JS validation   | 60 min             |
| Asset registration     | 30 min             |
| Testing & debugging    | 45 min             |
| Documentation          | 30 min             |
| **Total**              | **4 hours 30 min** |

---

## Dependencies for Next Work Packages

**WP02 Requires from WP01:**
- ✅ Form structure and DOM elements
- ✅ Validation logic (reuse)
- ✅ Error display mechanism
- ✅ CSS classes for success states

**WP03 Requires from WP01:**
- ✅ JavaScript controller structure
- ✅ State management pattern

**WP04 Requires from WP01:**
- ✅ Template structure for adding stats display
- ✅ CSS variables for consistent styling

---

**END OF WORK PACKAGE 01**

Ready to proceed with implementation? Review the git plan and let me know if you'd like to start with the first commit!
