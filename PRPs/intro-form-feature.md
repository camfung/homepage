# PRP: IntroForm Feature Implementation
**Project**: Traffic Portal Link Shortener Plugin
**Feature**: Anonymous Shortlink Creation (IntroForm)
**Date**: 2025-11-09
**Implementation Type**: One-Pass WordPress Plugin Feature

---

## Table of Contents
1. [Executive Summary](#executive-summary)
2. [Requirements Overview](#requirements-overview)
3. [Architecture & Integration](#architecture--integration)
4. [Implementation Blueprint](#implementation-blueprint)
5. [Code Patterns & Examples](#code-patterns--examples)
6. [Validation Gates](#validation-gates)
7. [Gotchas & Critical Notes](#gotchas--critical-notes)
8. [Task Checklist](#task-checklist)

---

## Executive Summary

### Purpose
Create an IntroForm that allows anonymous visitors to instantly create and test shortlinks without registration. Links are valid for 24 hours and stored in the existing AWS Aurora MySQL database with `status='intro'`.

### User-Approved Decisions
✅ **Keyword Generation**: Hybrid approach - rule-based first, AI-ready for future
✅ **URL Validation**: Client-side only (syntax checking)
✅ **Thumbnails**: Skip for now (future enhancement)
✅ **Anonymous Users**: Browser-generated session IDs (localStorage, no database changes)

### Scope
- **Frontend**: New shortcode `[tp_intro_form]` with JavaScript controller
- **Backend**: AJAX handlers extending existing API patterns
- **Database**: Use existing `tp_map` table with `status='intro'`
- **Infrastructure**: Leverage existing 24h cleanup Lambda

### Success Criteria
1. Anonymous visitor can create shortlink in <5 seconds
2. Link expires after 24 hours automatically
3. QR code generated with `qr=1` parameter
4. Click/scan counters update in real-time
5. Returning visitors see previous link if still valid

---

## Requirements Overview

### Functional Requirements

#### 1. Initial Form Display
- Single "Destination URL" field with paste icon
- Syntax validation (client-side)
- Auto-add `https://` if valid TLD detected (.com, .net, .ca, etc.)
- Ignore unsupported characters during typing
- Validate on paste

#### 2. Successful Validation Actions
Upon valid URL submission, perform simultaneously:
1. Generate keyword (rule-based extraction)
2. Check keyword uniqueness via API
3. Create intro mapping record (`status='intro'`)
4. Display editable Key field with suggested keyword
5. Show clickable shortlink
6. Generate QR code with `?qr=1` parameter
7. Display 24-hour expiration countdown
8. Show click and scan counters (initially 0)
9. Display "TRY IT NOW…" encouragement message

#### 3. Visitor Interactions
- **Clicking/scanning** shortlink updates counters in real-time
- **Keyword modification**:
  - Manual edit → delayed update of shortlink/QR
  - Click "regenerate" icon → new keyword suggestion
  - Edit destination → delayed revalidation
- **Confirmation popup** when changing key/destination:
  > "Do you want to try a different keyword? The current shortlink will be disabled."
- After first click/scan → Replace "TRY…" with "SAVE TO KEEP IT" button

#### 4. LocalStorage Requirements
Store and retrieve:
- `tpIntroSessionId` - Browser-generated UUID
- `tpIntroKey` - Current active key
- `tpIntroDestination` - Current destination
- `tpIntroExpiry` - Expiration timestamp
- `tpIntroMid` - Mapping ID from database

#### 5. Returning Visitor Behavior
When IntroForm loads with localStorage data:
1. Validate stored key + destination against database
2. If **active** and `status='intro'` → Show active form with countdown
3. If **expired** but key available → Pre-fill and show "try new one" message
4. If **key unavailable** → Show encouragement to try new keyword or register

### Non-Functional Requirements
- **Performance**: Form response < 2 seconds
- **Security**: WordPress nonce validation, input sanitization
- **Compatibility**: Works for non-logged-in users
- **Accessibility**: WCAG 2.1 AA compliant forms
- **Mobile**: Responsive design, touch-friendly

---

## Architecture & Integration

### Existing Infrastructure

#### Database Schema (AWS Aurora MySQL)
**Table**: `tp_map` (link mappings)
```sql
CREATE TABLE tp_map (
    mid int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uid int DEFAULT 0,                      -- Use 0 for anonymous
    domain varchar(255) NOT NULL,           -- From config
    tpKey varchar(255) NOT NULL BINARY,     -- Case-sensitive!
    destination varchar(2000) NOT NULL,
    type varchar(255),                      -- 'redirect'
    settings varchar(2000),                 -- '{}'
    is_set int,                             -- 0
    is_cached int,                          -- 0
    status varchar(255),                    -- 'intro' for temporary
    tags varchar(255),                      -- 'intro,wordpress'
    notes varchar(2000),                    -- 'IntroForm creation'
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    validated DATETIME,
    cache_content int,                      -- 0
    file_key varchar(1000)                  -- NULL
);
```

**Table**: `tp_log` (click/scan tracking)
```sql
CREATE TABLE tp_log (
    dt DATETIME DEFAULT CURRENT_TIMESTAMP,
    uid int,
    mid int,
    request_uri varchar(255),               -- Includes ?qr=1 for scans
    request_ip varchar(255),
    referer varchar(255),
    location varchar(2000),
    request_header varchar(2000),
    response varchar(2000)
);
```

#### API Endpoint
**Endpoint**: `POST https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev/items`

**Authentication**:
- Header: `x-api-key: q9D7lp99A818aVMcVM9vU1QoY7KM0SZa5lyw8M0d`

**Request Payload** (for intro links):
```json
{
    "uid": 0,
    "tpKey": "generated-key",
    "domain": "dev.trfc.link",
    "destination": "https://example.com",
    "status": "intro",
    "type": "redirect",
    "is_set": 0,
    "tags": "intro,wordpress",
    "notes": "Created via IntroForm",
    "settings": "{}",
    "cache_content": 0
}
```

**Response**:
```json
{
    "message": "Record created successfully",
    "success": true,
    "source": {
        "mid": 12345,
        "uid": 0,
        "tpKey": "generated-key",
        "domain": "dev.trfc.link",
        "destination": "https://example.com",
        "status": "intro"
    }
}
```

#### Automatic Cleanup
**Lambda**: `scheduled_cleanIntroRecord` (runs every hour via EventBridge)
```python
# Deletes intro records older than 24 hours
DELETE FROM tp_map
WHERE status='intro' AND updated_at <= NOW() - INTERVAL 1 DAY
```

### Integration Points

#### 1. WordPress Plugin Structure
```
tp-link-shortener-plugin/
├── includes/
│   ├── class-tp-intro-form.php              [NEW] Main intro form class
│   ├── class-tp-api-handler.php             [EXTEND] Add intro AJAX handlers
│   ├── class-tp-assets.php                  [EXTEND] Enqueue intro assets
│   └── TrafficPortal/
│       └── TrafficPortalApiClient.php       [REUSE] Existing API client
├── assets/
│   ├── js/
│   │   ├── intro-form.js                    [NEW] Intro form controller
│   │   └── frontend.js                      [REUSE] QR code generation
│   └── css/
│       └── intro-form.css                   [NEW] Intro-specific styles
└── templates/
    └── intro-form-template.php              [NEW] Form HTML
```

#### 2. Data Flow
```
User visits page with [tp_intro_form]
    ↓
Check localStorage for tpIntroSessionId
    ↓
If exists → Restore session and validate via AJAX
If not → Show blank form
    ↓
User enters URL → Client validates syntax
    ↓
User submits → Generate keyword (rule-based)
    ↓
AJAX to WordPress → PHP validates & sanitizes
    ↓
WordPress → API call to /items (status='intro')
    ↓
API responds with mid, tpKey, etc.
    ↓
JavaScript receives response
    ↓
Generate QR code + Display shortlink + Start countdown
    ↓
Save to localStorage
    ↓
User clicks/scans → Lambda logs to tp_log
    ↓
After 24h → Automated Lambda cleanup
```

---

## Implementation Blueprint

### Phase 1: Core Classes & Registration

#### File: `includes/class-tp-intro-form.php`
```php
<?php
/**
 * IntroForm Shortcode Handler
 *
 * Manages the anonymous link creation form for non-registered users.
 * Links created are temporary (24h expiration) with status='intro'.
 */

namespace TPLinkShortener;

class TP_Intro_Form {

    private TP_Assets $assets;

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
        $atts = shortcode_atts(array(
            'domain' => TP_Link_Shortener::get_domain(),
            'title' => __('Try it free - No registration needed', 'tp-link-shortener'),
            'subtitle' => __('Create a shortlink that expires in 24 hours', 'tp-link-shortener'),
        ), $atts);

        // Enqueue assets
        $this->assets->enqueue_intro_assets();

        // Output buffering
        ob_start();
        include TP_LINK_SHORTENER_PLUGIN_DIR . 'templates/intro-form-template.php';
        return ob_get_clean();
    }
}
```

#### Extension: `includes/class-tp-api-handler.php`
```php
// Add to __construct() after existing AJAX handlers
add_action('wp_ajax_nopriv_tp_intro_create_link', array($this, 'ajax_intro_create_link'));
add_action('wp_ajax_nopriv_tp_intro_validate_key', array($this, 'ajax_intro_validate_key'));
add_action('wp_ajax_nopriv_tp_intro_get_stats', array($this, 'ajax_intro_get_stats'));
add_action('wp_ajax_nopriv_tp_intro_restore_session', array($this, 'ajax_intro_restore_session'));

/**
 * AJAX: Create intro link (anonymous users)
 */
public function ajax_intro_create_link() {
    // 1. Verify nonce
    check_ajax_referer('tp_intro_form_nonce', 'nonce');

    // 2. Sanitize inputs
    $destination = sanitize_url($_POST['destination']);
    $custom_key = sanitize_text_field($_POST['custom_key'] ?? '');
    $session_id = sanitize_text_field($_POST['session_id'] ?? '');

    // 3. Validate destination
    if (empty($destination) || !filter_var($destination, FILTER_VALIDATE_URL)) {
        wp_send_json_error([
            'message' => __('Please enter a valid URL', 'tp-link-shortener')
        ]);
    }

    // 4. Add protocol if missing
    if (!preg_match('/^https?:\/\//i', $destination)) {
        $destination = 'https://' . $destination;
    }

    // 5. Generate key if not provided
    if (empty($custom_key)) {
        $custom_key = $this->generate_intro_key($destination);
    }

    // 6. Validate key format
    if (!preg_match('/^[a-zA-Z0-9\.\-_]{1,255}$/', $custom_key)) {
        wp_send_json_error([
            'message' => __('Invalid key format. Use only letters, numbers, dots, dashes, and underscores.', 'tp-link-shortener')
        ]);
    }

    // 7. Create intro link via API
    try {
        $client = $this->get_api_client();

        $request = new \TrafficPortal\DTO\CreateMapRequest(
            uid: 0,  // Anonymous
            tpKey: $custom_key,
            domain: TP_Link_Shortener::get_domain(),
            destination: $destination,
            status: 'intro',  // Temporary status
            type: 'redirect',
            isSet: 0,
            tags: 'intro,wordpress',
            notes: sprintf('IntroForm session: %s', $session_id),
            settings: '{}',
            cacheContent: 0
        );

        $response = $client->createMaskedRecord($request);

        if ($response->isSuccess()) {
            $shortlink = sprintf('https://%s/%s',
                TP_Link_Shortener::get_domain(),
                $custom_key
            );

            wp_send_json_success([
                'mid' => $response->getMid(),
                'key' => $custom_key,
                'shortlink' => $shortlink,
                'destination' => $destination,
                'domain' => TP_Link_Shortener::get_domain(),
                'expiry' => strtotime('+24 hours'),
                'message' => __('Shortlink created successfully!', 'tp-link-shortener')
            ]);
        } else {
            wp_send_json_error([
                'message' => $response->getMessage()
            ]);
        }

    } catch (\TrafficPortal\Exception\ValidationException $e) {
        wp_send_json_error([
            'message' => __('This keyword is already taken. Try another one.', 'tp-link-shortener')
        ]);
    } catch (\Exception $e) {
        error_log('IntroForm API Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => __('Unable to create shortlink. Please try again.', 'tp-link-shortener')
        ]);
    }
}

/**
 * Generate keyword from URL (rule-based)
 */
private function generate_intro_key(string $url): string {
    $parsed = parse_url($url);
    $keywords = [];

    // Extract from path
    if (!empty($parsed['path'])) {
        $path = trim($parsed['path'], '/');
        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            // Clean and extract alphanumeric
            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $segment);
            if (strlen($clean) >= 3 && strlen($clean) <= 20) {
                $keywords[] = strtolower($clean);
            }
        }
    }

    // Extract from domain (if short)
    if (!empty($parsed['host'])) {
        $domain_parts = explode('.', $parsed['host']);
        $main_domain = $domain_parts[count($domain_parts) - 2] ?? '';

        if (strlen($main_domain) >= 3 && strlen($main_domain) <= 15) {
            $keywords[] = strtolower($main_domain);
        }
    }

    // Use first valid keyword or generate random
    if (!empty($keywords)) {
        return $keywords[0];
    }

    // Fallback: random string
    return $this->generate_random_key(6);
}

/**
 * Generate random alphanumeric key
 */
private function generate_random_key(int $length = 6): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $key = '';

    for ($i = 0; $i < $length; $i++) {
        $key .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $key;
}

/**
 * AJAX: Validate if key is available
 */
public function ajax_intro_validate_key() {
    check_ajax_referer('tp_intro_form_nonce', 'nonce');

    $key = sanitize_text_field($_POST['key'] ?? '');

    if (empty($key)) {
        wp_send_json_error(['message' => 'Key is required']);
    }

    try {
        $client = $this->get_api_client();
        $domain = TP_Link_Shortener::get_domain();

        // Use validate endpoint
        $is_available = $client->validateKey($key, $domain);

        wp_send_json_success([
            'available' => $is_available,
            'key' => $key
        ]);

    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * AJAX: Get click/scan stats for intro link
 */
public function ajax_intro_get_stats() {
    check_ajax_referer('tp_intro_form_nonce', 'nonce');

    $mid = (int) ($_POST['mid'] ?? 0);

    if ($mid === 0) {
        wp_send_json_error(['message' => 'Invalid mapping ID']);
    }

    try {
        $client = $this->get_api_client();
        $stats = $client->getLinkStats($mid);

        wp_send_json_success([
            'clicks' => $stats['clicks'] ?? 0,
            'scans' => $stats['scans'] ?? 0,
            'total' => $stats['total'] ?? 0
        ]);

    } catch (\Exception $e) {
        // Return zeros on error
        wp_send_json_success([
            'clicks' => 0,
            'scans' => 0,
            'total' => 0
        ]);
    }
}

/**
 * AJAX: Restore session for returning visitor
 */
public function ajax_intro_restore_session() {
    check_ajax_referer('tp_intro_form_nonce', 'nonce');

    $key = sanitize_text_field($_POST['key'] ?? '');
    $destination = sanitize_url($_POST['destination'] ?? '');

    if (empty($key)) {
        wp_send_json_error(['message' => 'Key is required']);
    }

    try {
        $client = $this->get_api_client();
        $domain = TP_Link_Shortener::get_domain();

        // Get record from API
        $record = $client->getRecord($key, $domain);

        if ($record && $record['status'] === 'intro') {
            $updated_at = strtotime($record['updated_at']);
            $expiry = $updated_at + (24 * 60 * 60);

            if (time() < $expiry) {
                // Still valid
                wp_send_json_success([
                    'valid' => true,
                    'mid' => $record['mid'],
                    'key' => $record['tpKey'],
                    'destination' => $record['destination'],
                    'shortlink' => sprintf('https://%s/%s', $domain, $record['tpKey']),
                    'expiry' => $expiry,
                    'status' => 'active'
                ]);
            } else {
                // Expired
                wp_send_json_success([
                    'valid' => false,
                    'status' => 'expired',
                    'message' => __('Your previous link has expired. Create a new one!', 'tp-link-shortener')
                ]);
            }
        } else {
            // Not found or not intro status
            wp_send_json_success([
                'valid' => false,
                'status' => 'unavailable',
                'message' => __('This keyword is no longer available.', 'tp-link-shortener')
            ]);
        }

    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
```

### Phase 2: Frontend Template

#### File: `templates/intro-form-template.php`
```php
<?php
/**
 * IntroForm Template
 *
 * Displays the anonymous link creation form.
 * Variables available: $atts (shortcode attributes)
 */

defined('ABSPATH') || exit;
?>

<div class="tp-intro-form-wrapper" data-domain="<?php echo esc_attr($atts['domain']); ?>">

    <!-- Header -->
    <div class="tp-intro-header">
        <h2 class="tp-intro-title"><?php echo esc_html($atts['title']); ?></h2>
        <p class="tp-intro-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
    </div>

    <!-- Form -->
    <form id="tp-intro-form" class="tp-intro-form">

        <!-- Destination Input (always visible) -->
        <div class="tp-form-group">
            <label for="tp-intro-destination" class="tp-form-label">
                <?php _e('Destination URL', 'tp-link-shortener'); ?>
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
                />
                <button type="button" class="tp-paste-btn" title="<?php esc_attr_e('Paste from clipboard', 'tp-link-shortener'); ?>">
                    <i class="fas fa-paste"></i>
                </button>
            </div>
            <div class="tp-field-hint">
                <?php _e('Enter the URL you want to shorten', 'tp-link-shortener'); ?>
            </div>
        </div>

        <!-- Key Input (shown after validation) -->
        <div class="tp-form-group tp-key-group" style="display: none;">
            <label for="tp-intro-key" class="tp-form-label">
                <?php _e('Your Shortlink Keyword', 'tp-link-shortener'); ?>
            </label>
            <div class="tp-input-wrapper">
                <input
                    type="text"
                    id="tp-intro-key"
                    name="custom_key"
                    class="tp-form-control tp-intro-key"
                    pattern="[a-zA-Z0-9\.\-_]+"
                    maxlength="255"
                    autocomplete="off"
                />
                <button type="button" class="tp-regenerate-btn" title="<?php esc_attr_e('Generate new keyword', 'tp-link-shortener'); ?>">
                    <i class="fas fa-lightbulb"></i>
                </button>
            </div>
            <div class="tp-field-hint">
                <?php _e('Customize your keyword or use suggested one', 'tp-link-shortener'); ?>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="tp-btn tp-btn-primary tp-intro-submit">
            <span class="tp-btn-text"><?php _e('Create Shortlink', 'tp-link-shortener'); ?></span>
            <span class="tp-btn-spinner" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i>
            </span>
        </button>

    </form>

    <!-- Error Message (hidden by default) -->
    <div class="tp-intro-error" style="display: none;">
        <i class="fas fa-exclamation-circle"></i>
        <span class="tp-error-text"></span>
    </div>

    <!-- Success Panel (hidden by default) -->
    <div class="tp-intro-success" style="display: none;">

        <!-- Shortlink Display -->
        <div class="tp-shortlink-display">
            <label class="tp-form-label"><?php _e('Your Shortlink', 'tp-link-shortener'); ?></label>
            <div class="tp-shortlink-wrapper">
                <a href="#" target="_blank" class="tp-shortlink-url"></a>
                <button type="button" class="tp-copy-btn" title="<?php esc_attr_e('Copy to clipboard', 'tp-link-shortener'); ?>">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>

        <!-- QR Code -->
        <div class="tp-qr-section">
            <label class="tp-form-label"><?php _e('QR Code', 'tp-link-shortener'); ?></label>
            <div class="tp-qr-container"></div>
            <button type="button" class="tp-btn tp-btn-sm tp-download-qr">
                <i class="fas fa-download"></i>
                <?php _e('Download QR', 'tp-link-shortener'); ?>
            </button>
        </div>

        <!-- Stats -->
        <div class="tp-stats-section">
            <div class="tp-stat-item">
                <i class="fas fa-mouse-pointer"></i>
                <span class="tp-stat-label"><?php _e('Clicks', 'tp-link-shortener'); ?></span>
                <span class="tp-stat-value tp-click-count">0</span>
            </div>
            <div class="tp-stat-item">
                <i class="fas fa-qrcode"></i>
                <span class="tp-stat-label"><?php _e('Scans', 'tp-link-shortener'); ?></span>
                <span class="tp-stat-value tp-scan-count">0</span>
            </div>
        </div>

        <!-- Expiration Timer -->
        <div class="tp-expiry-section">
            <i class="fas fa-clock"></i>
            <span class="tp-expiry-label"><?php _e('Expires in:', 'tp-link-shortener'); ?></span>
            <span class="tp-expiry-countdown"></span>
        </div>

        <!-- Call to Action -->
        <div class="tp-cta-section">
            <p class="tp-cta-text tp-try-message">
                <?php _e('👉 Try it now! Click or scan to see it in action.', 'tp-link-shortener'); ?>
            </p>
            <button type="button" class="tp-btn tp-btn-success tp-save-btn" style="display: none;">
                <i class="fas fa-save"></i>
                <?php _e('Save to Keep It Forever', 'tp-link-shortener'); ?>
            </button>
        </div>

        <!-- Create Another -->
        <button type="button" class="tp-btn tp-btn-outline tp-create-another">
            <i class="fas fa-plus"></i>
            <?php _e('Create Another Link', 'tp-link-shortener'); ?>
        </button>

    </div>

</div>
```

### Phase 3: JavaScript Controller

#### File: `assets/js/intro-form.js`
```javascript
/**
 * IntroForm Controller
 *
 * Handles anonymous link creation, localStorage session management,
 * and real-time stats updates.
 */

(function($) {
    'use strict';

    const TPIntroForm = {

        // Config
        config: {
            storagePrefix: 'tpIntro',
            statsInterval: 5000, // 5 seconds
            countdownInterval: 1000, // 1 second
            keyDebounce: 800, // 800ms debounce for key validation
        },

        // DOM Elements
        $wrapper: null,
        $form: null,
        $destinationInput: null,
        $keyInput: null,
        $keyGroup: null,
        $submitBtn: null,
        $errorContainer: null,
        $successPanel: null,
        $shortlinkUrl: null,
        $qrContainer: null,
        $clickCount: null,
        $scanCount: null,
        $expiryCountdown: null,
        $tryMessage: null,
        $saveBtn: null,

        // State
        state: {
            sessionId: null,
            mid: null,
            key: null,
            destination: null,
            shortlink: null,
            domain: null,
            expiry: null,
            statsTimer: null,
            countdownTimer: null,
            hasInteracted: false,
        },

        // QR Code instance
        qrCode: null,

        /**
         * Initialize
         */
        init: function() {
            this.cacheElements();
            this.loadOrCreateSession();
            this.bindEvents();
            this.attemptSessionRestore();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.$wrapper = $('.tp-intro-form-wrapper');
            this.$form = $('#tp-intro-form');
            this.$destinationInput = $('#tp-intro-destination');
            this.$keyInput = $('#tp-intro-key');
            this.$keyGroup = $('.tp-key-group');
            this.$submitBtn = $('.tp-intro-submit');
            this.$errorContainer = $('.tp-intro-error');
            this.$successPanel = $('.tp-intro-success');
            this.$shortlinkUrl = $('.tp-shortlink-url');
            this.$qrContainer = $('.tp-qr-container');
            this.$clickCount = $('.tp-click-count');
            this.$scanCount = $('.tp-scan-count');
            this.$expiryCountdown = $('.tp-expiry-countdown');
            this.$tryMessage = $('.tp-try-message');
            this.$saveBtn = $('.tp-save-btn');

            this.state.domain = this.$wrapper.data('domain');
        },

        /**
         * Load or create session ID
         */
        loadOrCreateSession: function() {
            const storageKey = this.config.storagePrefix + 'SessionId';

            try {
                let sessionId = localStorage.getItem(storageKey);

                if (!sessionId) {
                    // Generate UUID v4
                    sessionId = this.generateUUID();
                    localStorage.setItem(storageKey, sessionId);
                }

                this.state.sessionId = sessionId;
            } catch (e) {
                console.warn('localStorage unavailable, using temporary session');
                this.state.sessionId = this.generateUUID();
            }
        },

        /**
         * Generate UUID v4
         */
        generateUUID: function() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Form submission
            this.$form.on('submit', this.handleSubmit.bind(this));

            // Paste button
            $('.tp-paste-btn').on('click', this.handlePaste.bind(this));

            // Destination input validation
            this.$destinationInput.on('input', this.handleDestinationInput.bind(this));
            this.$destinationInput.on('paste', this.handleDestinationPaste.bind(this));

            // Key input validation
            this.$keyInput.on('input', this.handleKeyInput.bind(this));

            // Regenerate keyword
            $('.tp-regenerate-btn').on('click', this.handleRegenerateKey.bind(this));

            // Copy shortlink
            $('.tp-copy-btn').on('click', this.handleCopyShortlink.bind(this));

            // Download QR
            $('.tp-download-qr').on('click', this.handleDownloadQR.bind(this));

            // Create another
            $('.tp-create-another').on('click', this.handleCreateAnother.bind(this));

            // Save button
            this.$saveBtn.on('click', this.handleSaveLink.bind(this));
        },

        /**
         * Attempt to restore previous session
         */
        attemptSessionRestore: function() {
            const data = this.loadSessionData();

            if (!data || !data.key) {
                return; // No previous session
            }

            // Show loading state
            this.showLoading('Restoring your previous link...');

            $.ajax({
                url: tpIntroForm.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tp_intro_restore_session',
                    nonce: tpIntroForm.nonce,
                    key: data.key,
                    destination: data.destination
                },
                success: function(response) {
                    if (response.success) {
                        const result = response.data;

                        if (result.valid && result.status === 'active') {
                            // Restore active session
                            this.state.mid = result.mid;
                            this.state.key = result.key;
                            this.state.destination = result.destination;
                            this.state.shortlink = result.shortlink;
                            this.state.expiry = result.expiry;

                            this.showSuccess();
                            this.hideLoading();
                        } else {
                            // Expired or unavailable
                            this.hideLoading();
                            this.showInfo(result.message || 'Your previous link has expired.');
                            this.clearSessionData();
                        }
                    } else {
                        this.hideLoading();
                        this.clearSessionData();
                    }
                }.bind(this),
                error: function() {
                    this.hideLoading();
                    this.clearSessionData();
                }.bind(this)
            });
        },

        /**
         * Handle form submission
         */
        handleSubmit: function(e) {
            e.preventDefault();

            const destination = this.$destinationInput.val().trim();
            const customKey = this.$keyInput.val().trim();

            // Validate destination
            if (!this.validateUrl(destination)) {
                this.showError(tpIntroForm.strings.invalidUrl);
                return;
            }

            // Show loading
            this.setLoading(true);
            this.hideError();

            // AJAX request
            $.ajax({
                url: tpIntroForm.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tp_intro_create_link',
                    nonce: tpIntroForm.nonce,
                    destination: destination,
                    custom_key: customKey,
                    session_id: this.state.sessionId
                },
                success: this.handleCreateSuccess.bind(this),
                error: this.handleCreateError.bind(this)
            });
        },

        /**
         * Handle successful link creation
         */
        handleCreateSuccess: function(response) {
            this.setLoading(false);

            if (response.success) {
                const data = response.data;

                // Update state
                this.state.mid = data.mid;
                this.state.key = data.key;
                this.state.destination = data.destination;
                this.state.shortlink = data.shortlink;
                this.state.expiry = data.expiry;

                // Save to localStorage
                this.saveSessionData();

                // Show success panel
                this.showSuccess();

            } else {
                this.showError(response.data.message || tpIntroForm.strings.createError);
            }
        },

        /**
         * Handle creation error
         */
        handleCreateError: function(xhr) {
            this.setLoading(false);

            let message = tpIntroForm.strings.createError;

            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                message = xhr.responseJSON.data.message;
            }

            this.showError(message);
        },

        /**
         * Show success panel
         */
        showSuccess: function() {
            // Hide form
            this.$form.hide();

            // Update shortlink
            this.$shortlinkUrl.attr('href', this.state.shortlink).text(this.state.shortlink);

            // Generate QR code
            this.generateQRCode();

            // Start countdown
            this.startCountdown();

            // Start stats polling
            this.startStatsPolling();

            // Show success panel
            this.$successPanel.fadeIn();
        },

        /**
         * Generate QR code
         */
        generateQRCode: function() {
            // Clear existing
            this.$qrContainer.empty();

            // Add qr=1 parameter
            const qrUrl = this.state.shortlink + '?qr=1';

            // Create container
            const qrDiv = $('<div>').attr('id', 'tp-intro-qr-' + Date.now());
            this.$qrContainer.append(qrDiv);

            // Generate QR
            try {
                this.qrCode = new QRCode(qrDiv[0], {
                    text: qrUrl,
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
         * Start expiration countdown
         */
        startCountdown: function() {
            // Clear existing timer
            if (this.state.countdownTimer) {
                clearInterval(this.state.countdownTimer);
            }

            const updateCountdown = function() {
                const now = Math.floor(Date.now() / 1000);
                const remaining = this.state.expiry - now;

                if (remaining <= 0) {
                    this.$expiryCountdown.text('Expired');
                    clearInterval(this.state.countdownTimer);
                    this.showInfo('Your link has expired. Create a new one!');
                    return;
                }

                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;

                this.$expiryCountdown.text(
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0')
                );
            }.bind(this);

            updateCountdown();
            this.state.countdownTimer = setInterval(updateCountdown, this.config.countdownInterval);
        },

        /**
         * Start stats polling
         */
        startStatsPolling: function() {
            // Clear existing timer
            if (this.state.statsTimer) {
                clearInterval(this.state.statsTimer);
            }

            const pollStats = function() {
                $.ajax({
                    url: tpIntroForm.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'tp_intro_get_stats',
                        nonce: tpIntroForm.nonce,
                        mid: this.state.mid
                    },
                    success: function(response) {
                        if (response.success) {
                            const stats = response.data;

                            this.$clickCount.text(stats.clicks);
                            this.$scanCount.text(stats.scans);

                            // Check if user has interacted
                            if (stats.total > 0 && !this.state.hasInteracted) {
                                this.state.hasInteracted = true;
                                this.$tryMessage.fadeOut();
                                this.$saveBtn.fadeIn();
                            }
                        }
                    }.bind(this)
                });
            }.bind(this);

            pollStats();
            this.state.statsTimer = setInterval(pollStats, this.config.statsInterval);
        },

        /**
         * Handle paste button
         */
        handlePaste: async function() {
            try {
                const text = await navigator.clipboard.readText();
                this.$destinationInput.val(text).trigger('input');
            } catch (e) {
                console.warn('Clipboard access denied');
            }
        },

        /**
         * Handle destination input
         */
        handleDestinationInput: function(e) {
            const value = e.target.value.trim();

            // Remove unsupported characters in real-time
            const cleaned = value.replace(/[<>"{}|\\^`\[\]]/g, '');

            if (cleaned !== value) {
                this.$destinationInput.val(cleaned);
            }
        },

        /**
         * Handle destination paste
         */
        handleDestinationPaste: function(e) {
            setTimeout(function() {
                const value = this.$destinationInput.val().trim();

                if (this.validateUrl(value)) {
                    this.hideError();
                    this.showInfo(tpIntroForm.strings.validUrl);
                }
            }.bind(this), 100);
        },

        /**
         * Handle key input
         */
        handleKeyInput: function(e) {
            const value = e.target.value;

            // Remove invalid characters
            const cleaned = value.replace(/[^a-zA-Z0-9\.\-_]/g, '');

            if (cleaned !== value) {
                this.$keyInput.val(cleaned);
            }
        },

        /**
         * Handle regenerate key
         */
        handleRegenerateKey: function() {
            // Confirm if link already created
            if (this.state.mid) {
                if (!confirm(tpIntroForm.strings.confirmRegenerate)) {
                    return;
                }
            }

            // Generate new random key
            const newKey = this.generateRandomKey(8);
            this.$keyInput.val(newKey);

            // If link exists, recreate
            if (this.state.mid) {
                this.handleSubmit(new Event('submit'));
            }
        },

        /**
         * Generate random key
         */
        generateRandomKey: function(length) {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';

            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }

            return result;
        },

        /**
         * Handle copy shortlink
         */
        handleCopyShortlink: function() {
            const url = this.state.shortlink;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    this.showInfo(tpIntroForm.strings.copied);
                }.bind(this));
            } else {
                // Fallback
                const $temp = $('<input>');
                $('body').append($temp);
                $temp.val(url).select();
                document.execCommand('copy');
                $temp.remove();
                this.showInfo(tpIntroForm.strings.copied);
            }
        },

        /**
         * Handle download QR
         */
        handleDownloadQR: function() {
            const canvas = this.$qrContainer.find('canvas')[0];

            if (!canvas) {
                return;
            }

            canvas.toBlob(function(blob) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'qr-code-' + this.state.key + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }.bind(this));
        },

        /**
         * Handle create another
         */
        handleCreateAnother: function() {
            if (confirm(tpIntroForm.strings.confirmCreateAnother)) {
                // Clear state
                this.clearSessionData();

                // Stop timers
                if (this.state.statsTimer) clearInterval(this.state.statsTimer);
                if (this.state.countdownTimer) clearInterval(this.state.countdownTimer);

                // Reset UI
                this.$successPanel.hide();
                this.$form.show();
                this.$destinationInput.val('');
                this.$keyInput.val('');
                this.$keyGroup.hide();
                this.state.hasInteracted = false;
            }
        },

        /**
         * Handle save link (upgrade to permanent)
         */
        handleSaveLink: function() {
            // Redirect to registration page with pre-filled data
            const registerUrl = tpIntroForm.registerUrl || '/register';
            const params = new URLSearchParams({
                key: this.state.key,
                destination: this.state.destination,
                from: 'intro'
            });

            window.location.href = registerUrl + '?' + params.toString();
        },

        /**
         * Validate URL
         */
        validateUrl: function(url) {
            try {
                // Add protocol if missing and has valid TLD
                if (!/^https?:\/\//i.test(url)) {
                    const tldMatch = url.match(/\.([a-z]{2,})$/i);
                    if (tldMatch) {
                        url = 'https://' + url;
                    } else {
                        return false;
                    }
                }

                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        /**
         * Set loading state
         */
        setLoading: function(loading) {
            if (loading) {
                this.$submitBtn.prop('disabled', true);
                this.$submitBtn.find('.tp-btn-text').hide();
                this.$submitBtn.find('.tp-btn-spinner').show();
            } else {
                this.$submitBtn.prop('disabled', false);
                this.$submitBtn.find('.tp-btn-text').show();
                this.$submitBtn.find('.tp-btn-spinner').hide();
            }
        },

        /**
         * Show loading message
         */
        showLoading: function(message) {
            this.$form.prepend(
                '<div class="tp-loading-message">' +
                '<i class="fas fa-spinner fa-spin"></i> ' + message +
                '</div>'
            );
        },

        /**
         * Hide loading message
         */
        hideLoading: function() {
            $('.tp-loading-message').remove();
        },

        /**
         * Show error
         */
        showError: function(message) {
            this.$errorContainer.find('.tp-error-text').text(message);
            this.$errorContainer.slideDown();
        },

        /**
         * Hide error
         */
        hideError: function() {
            this.$errorContainer.slideUp();
        },

        /**
         * Show info message
         */
        showInfo: function(message) {
            // Use error container but with different styling
            this.$errorContainer
                .removeClass('tp-error')
                .addClass('tp-info')
                .find('.tp-error-text').text(message);
            this.$errorContainer.slideDown();

            setTimeout(function() {
                this.$errorContainer.slideUp();
            }.bind(this), 5000);
        },

        /**
         * Save session data to localStorage
         */
        saveSessionData: function() {
            const data = {
                mid: this.state.mid,
                key: this.state.key,
                destination: this.state.destination,
                shortlink: this.state.shortlink,
                expiry: this.state.expiry,
                savedAt: Date.now()
            };

            try {
                localStorage.setItem(this.config.storagePrefix + 'Data', JSON.stringify(data));
            } catch (e) {
                console.warn('Unable to save to localStorage');
            }
        },

        /**
         * Load session data from localStorage
         */
        loadSessionData: function() {
            try {
                const json = localStorage.getItem(this.config.storagePrefix + 'Data');
                return json ? JSON.parse(json) : null;
            } catch (e) {
                return null;
            }
        },

        /**
         * Clear session data
         */
        clearSessionData: function() {
            try {
                localStorage.removeItem(this.config.storagePrefix + 'Data');
            } catch (e) {
                // Ignore
            }
        }

    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.tp-intro-form-wrapper').length) {
            TPIntroForm.init();
        }
    });

})(jQuery);
```

### Phase 4: Styling

#### File: `assets/css/intro-form.css`
```css
/**
 * IntroForm Styles
 */

/* Wrapper */
.tp-intro-form-wrapper {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background: var(--tp-surface, #ffffff);
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Header */
.tp-intro-header {
    text-align: center;
    margin-bottom: 2rem;
}

.tp-intro-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--tp-primary, #5bc0de);
    margin-bottom: 0.5rem;
}

.tp-intro-subtitle {
    font-size: 1rem;
    color: #6c757d;
    margin: 0;
}

/* Form */
.tp-intro-form .tp-form-group {
    margin-bottom: 1.5rem;
}

.tp-intro-form .tp-form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #212529;
}

.tp-intro-form .tp-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.tp-intro-form .tp-form-control {
    flex: 1;
    padding: 0.85rem 3rem 0.85rem 1.1rem;
    border: 1px solid var(--tp-border, #dfe6ee);
    border-radius: 0.9rem;
    font-size: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.tp-intro-form .tp-form-control:focus {
    outline: none;
    border-color: var(--tp-primary, #5bc0de);
    box-shadow: 0 0 0 0.25rem rgba(91, 192, 222, 0.15);
}

.tp-intro-form .tp-paste-btn,
.tp-intro-form .tp-regenerate-btn {
    position: absolute;
    right: 0.75rem;
    background: transparent;
    border: none;
    color: var(--tp-primary, #5bc0de);
    cursor: pointer;
    padding: 0.5rem;
    font-size: 1.1rem;
    transition: color 0.2s ease;
}

.tp-intro-form .tp-paste-btn:hover,
.tp-intro-form .tp-regenerate-btn:hover {
    color: #31b0d5;
}

.tp-intro-form .tp-field-hint {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.5rem;
}

/* Buttons */
.tp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.85rem 1.5rem;
    border: none;
    border-radius: 0.9rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.tp-btn-primary {
    background: var(--tp-primary, #5bc0de);
    color: #ffffff;
    width: 100%;
}

.tp-btn-primary:hover:not(:disabled) {
    background: #31b0d5;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(91, 192, 222, 0.3);
}

.tp-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.tp-btn-success {
    background: var(--tp-accent, #5cb85c);
    color: #ffffff;
    width: 100%;
    margin-top: 1rem;
}

.tp-btn-success:hover {
    background: #4cae4c;
}

.tp-btn-outline {
    background: transparent;
    color: var(--tp-primary, #5bc0de);
    border: 1px solid var(--tp-primary, #5bc0de);
    width: 100%;
    margin-top: 1rem;
}

.tp-btn-outline:hover {
    background: var(--tp-primary, #5bc0de);
    color: #ffffff;
}

.tp-btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

/* Error/Info Messages */
.tp-intro-error,
.tp-intro-error.tp-info {
    padding: 1rem;
    border-radius: 0.9rem;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.tp-intro-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.tp-intro-error.tp-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* Success Panel */
.tp-intro-success {
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 0.9rem;
}

.tp-shortlink-display {
    margin-bottom: 1.5rem;
}

.tp-shortlink-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.85rem;
    background: #ffffff;
    border: 1px solid var(--tp-border, #dfe6ee);
    border-radius: 0.9rem;
}

.tp-shortlink-url {
    flex: 1;
    color: var(--tp-primary, #5bc0de);
    text-decoration: none;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tp-shortlink-url:hover {
    text-decoration: underline;
}

.tp-copy-btn {
    background: transparent;
    border: none;
    color: var(--tp-primary, #5bc0de);
    cursor: pointer;
    padding: 0.5rem;
    font-size: 1.1rem;
    transition: color 0.2s ease;
}

.tp-copy-btn:hover {
    color: #31b0d5;
}

/* QR Section */
.tp-qr-section {
    text-align: center;
    margin-bottom: 1.5rem;
}

.tp-qr-container {
    display: inline-block;
    padding: 1rem;
    background: #ffffff;
    border: 1px solid var(--tp-border, #dfe6ee);
    border-radius: 0.9rem;
    margin-bottom: 1rem;
}

/* Stats Section */
.tp-stats-section {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.tp-stat-item {
    flex: 1;
    text-align: center;
    padding: 1rem;
    background: #ffffff;
    border: 1px solid var(--tp-border, #dfe6ee);
    border-radius: 0.9rem;
}

.tp-stat-item i {
    display: block;
    font-size: 1.5rem;
    color: var(--tp-primary, #5bc0de);
    margin-bottom: 0.5rem;
}

.tp-stat-label {
    display: block;
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.tp-stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 600;
    color: #212529;
}

/* Expiry Section */
.tp-expiry-section {
    text-align: center;
    padding: 1rem;
    background: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 0.9rem;
    margin-bottom: 1.5rem;
}

.tp-expiry-section i {
    color: #856404;
    margin-right: 0.5rem;
}

.tp-expiry-label {
    font-weight: 500;
    color: #856404;
    margin-right: 0.5rem;
}

.tp-expiry-countdown {
    font-weight: 600;
    color: #856404;
    font-size: 1.1rem;
}

/* CTA Section */
.tp-cta-section {
    text-align: center;
}

.tp-cta-text {
    font-size: 1.1rem;
    color: #212529;
    margin-bottom: 1rem;
}

/* Loading Message */
.tp-loading-message {
    text-align: center;
    padding: 1rem;
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 0.9rem;
    color: #0c5460;
    margin-bottom: 1rem;
}

/* Responsive */
@media (max-width: 576px) {
    .tp-intro-form-wrapper {
        padding: 1.5rem;
        margin: 1rem;
    }

    .tp-intro-title {
        font-size: 1.5rem;
    }

    .tp-stats-section {
        flex-direction: column;
    }
}
```

### Phase 5: Asset Registration

#### Extension: `includes/class-tp-assets.php`
```php
/**
 * Enqueue IntroForm assets
 */
public function enqueue_intro_assets() {
    // Already have shortcode check from parent method

    // Enqueue dependencies first (Bootstrap, Font Awesome, QRCode - already loaded)

    // Enqueue intro-specific CSS
    wp_enqueue_style(
        'tp-intro-form',
        TP_LINK_SHORTENER_PLUGIN_URL . 'assets/css/intro-form.css',
        array('tp-link-shortener'), // Depends on main plugin CSS
        TP_LINK_SHORTENER_VERSION
    );

    // Enqueue intro-specific JS
    wp_enqueue_script(
        'tp-intro-form-js',
        TP_LINK_SHORTENER_PLUGIN_URL . 'assets/js/intro-form.js',
        array('jquery', 'tp-qrcode', 'tp-link-shortener-js'),
        TP_LINK_SHORTENER_VERSION,
        true
    );

    // Localize script
    wp_localize_script('tp-intro-form-js', 'tpIntroForm', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tp_intro_form_nonce'),
        'domain' => TP_Link_Shortener::get_domain(),
        'registerUrl' => home_url('/register'), // Adjust as needed
        'strings' => array(
            'invalidUrl' => __('Please enter a valid URL', 'tp-link-shortener'),
            'createError' => __('Error creating link. Please try again.', 'tp-link-shortener'),
            'validUrl' => __('URL looks good! Click Create to continue.', 'tp-link-shortener'),
            'copied' => __('Copied to clipboard!', 'tp-link-shortener'),
            'confirmRegenerate' => __('Do you want to try a different keyword? The current shortlink will be disabled.', 'tp-link-shortener'),
            'confirmCreateAnother' => __('Create a new link? Your current link will remain active until it expires.', 'tp-link-shortener'),
        ),
    ));
}
```

### Phase 6: API Client Extensions

#### Extension: `includes/TrafficPortal/TrafficPortalApiClient.php`
```php
/**
 * Validate if key is available
 *
 * @param string $key The key to validate
 * @param string $domain The domain to check against
 * @return bool True if available, false if taken
 */
public function validateKey(string $key, string $domain): bool
{
    $url = $this->apiEndpoint . '/validate';

    $ch = curl_init($url . '?' . http_build_query([
        'tpKey' => $key,
        'domain' => $domain
    ]));

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $this->apiKey,
        ],
        CURLOPT_TIMEOUT => $this->timeout,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['available'] ?? false;
    }

    return false;
}

/**
 * Get record by key and domain
 *
 * @param string $key The key
 * @param string $domain The domain
 * @return array|null Record data or null
 */
public function getRecord(string $key, string $domain): ?array
{
    $url = $this->apiEndpoint . '/items/' . urlencode($key);

    $ch = curl_init($url . '?domain=' . urlencode($domain));

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $this->apiKey,
        ],
        CURLOPT_TIMEOUT => $this->timeout,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['record'] ?? null;
    }

    return null;
}

/**
 * Get link statistics
 *
 * @param int $mid Mapping ID
 * @return array Stats array with clicks, scans, total
 */
public function getLinkStats(int $mid): array
{
    $url = $this->apiEndpoint . '/stats/' . $mid;

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $this->apiKey,
        ],
        CURLOPT_TIMEOUT => $this->timeout,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);

        return [
            'clicks' => $data['clicks'] ?? 0,
            'scans' => $data['scans'] ?? 0,
            'total' => $data['total'] ?? 0,
        ];
    }

    return ['clicks' => 0, 'scans' => 0, 'total' => 0];
}
```

### Phase 7: Plugin Registration

#### Extension: `includes/class-tp-link-shortener.php`
```php
// In __construct() method, after existing shortcode registration

// Register IntroForm shortcode
$intro_form = new TP_Intro_Form($this->assets);
```

---

## Code Patterns & Examples

### Pattern 1: WordPress Nonce Security
**All AJAX calls MUST use nonces:**
```javascript
// JavaScript
data: {
    action: 'tp_intro_create_link',
    nonce: tpIntroForm.nonce,
    destination: url
}
```
```php
// PHP
check_ajax_referer('tp_intro_form_nonce', 'nonce');
```

### Pattern 2: API Request/Response
**Follow existing DTO pattern:**
```php
$request = new CreateMapRequest(
    uid: 0,
    tpKey: $key,
    domain: $domain,
    destination: $url,
    status: 'intro',  // IMPORTANT: Use 'intro' for temporary
    type: 'redirect',
    isSet: 0,
    tags: 'intro,wordpress',
    notes: 'IntroForm',
    settings: '{}',
    cacheContent: 0
);

$response = $client->createMaskedRecord($request);
```

### Pattern 3: LocalStorage with Try/Catch
**Always wrap localStorage in try/catch:**
```javascript
try {
    localStorage.setItem('tpIntroData', JSON.stringify(data));
} catch (e) {
    console.warn('localStorage unavailable');
    // Fallback behavior
}
```

### Pattern 4: QR Code Generation
**Follow existing pattern from frontend.js:**
```javascript
const separator = url.includes('?') ? '&' : '?';
const qrUrl = url + separator + 'qr=1';  // IMPORTANT: Add qr=1

this.qrCode = new QRCode(container, {
    text: qrUrl,
    width: 156,
    height: 156,
    colorDark: '#000000',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.H
});
```

### Pattern 5: Expiration Countdown
**Use JavaScript intervals:**
```javascript
const updateCountdown = function() {
    const now = Math.floor(Date.now() / 1000);
    const remaining = expiry - now;

    if (remaining <= 0) {
        // Handle expiration
        return;
    }

    const hours = Math.floor(remaining / 3600);
    const minutes = Math.floor((remaining % 3600) / 60);
    const seconds = remaining % 60;

    display.text(
        String(hours).padStart(2, '0') + ':' +
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0')
    );
};

setInterval(updateCountdown, 1000);
```

---

## Validation Gates

### Pre-Implementation Checklist
```bash
# 1. Verify WordPress environment
wp core version
wp plugin list

# 2. Check PHP version (should be >= 7.4)
php -v

# 3. Verify database connectivity
wp db check

# 4. Test existing API connectivity
curl -X POST https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev/items \
  -H "x-api-key: q9D7lp99A818aVMcVM9vU1QoY7KM0SZa5lyw8M0d" \
  -H "Content-Type: application/json" \
  -d '{
    "uid": 0,
    "tpKey": "test123",
    "domain": "dev.trfc.link",
    "destination": "https://example.com",
    "status": "intro"
  }'
```

### Post-Implementation Testing

#### 1. Syntax Validation
```bash
# PHP syntax check
find tp-link-shortener-plugin/includes -name "*.php" -exec php -l {} \;

# JavaScript syntax check (if you have Node.js)
npx eslint tp-link-shortener-plugin/assets/js/intro-form.js
```

#### 2. Functional Testing
```bash
# Test shortcode rendering
wp eval 'echo do_shortcode("[tp_intro_form]");'

# Test AJAX endpoints (from browser console)
jQuery.post(ajaxurl, {
    action: 'tp_intro_create_link',
    nonce: tpIntroForm.nonce,
    destination: 'https://example.com',
    session_id: 'test-session'
});
```

#### 3. Database Validation
```sql
-- Check intro records created
SELECT * FROM tp_map WHERE status='intro' ORDER BY updated_at DESC LIMIT 10;

-- Check expiration logic
SELECT COUNT(*) FROM tp_map
WHERE status='intro' AND updated_at <= NOW() - INTERVAL 1 DAY;

-- Check click tracking
SELECT * FROM tp_log WHERE mid IN (
    SELECT mid FROM tp_map WHERE status='intro'
) ORDER BY dt DESC LIMIT 20;
```

#### 4. Security Testing
```bash
# Test nonce validation (should fail)
curl -X POST https://yoursite.com/wp-admin/admin-ajax.php \
  -d "action=tp_intro_create_link&destination=https://example.com&nonce=invalid"

# Test input sanitization (should remove invalid chars)
curl -X POST https://yoursite.com/wp-admin/admin-ajax.php \
  -d "action=tp_intro_create_link&custom_key=test<script>&nonce=VALID_NONCE"
```

#### 5. LocalStorage Testing
```javascript
// Browser console tests

// Test save
localStorage.setItem('tpIntroData', JSON.stringify({
    key: 'test123',
    expiry: Date.now() + 86400000
}));

// Test restore
const data = JSON.parse(localStorage.getItem('tpIntroData'));
console.log(data);

// Test clear
localStorage.removeItem('tpIntroData');
```

#### 6. End-to-End Testing Checklist
```
[ ] New user visits page with [tp_intro_form]
[ ] Form displays with single destination field
[ ] Paste button works (if clipboard API available)
[ ] URL validation shows errors for invalid URLs
[ ] Protocol auto-added for URLs with TLD
[ ] Submit creates intro record with status='intro'
[ ] Keyword generated from URL or random
[ ] Shortlink displayed and clickable
[ ] QR code generated with ?qr=1 parameter
[ ] Countdown timer shows 24 hours and decrements
[ ] Click/scan counters start at 0
[ ] Clicking shortlink increments click counter
[ ] Scanning QR increments scan counter
[ ] After interaction, "SAVE" button appears
[ ] Session saved to localStorage
[ ] Returning visitor sees previous link if valid
[ ] Expired link shows appropriate message
[ ] "Create another" resets form correctly
[ ] Record automatically deleted after 24 hours
```

---

## Gotchas & Critical Notes

### 1. **Case-Sensitive Keys** ⚠️
```sql
-- tpKey uses BINARY collation (case-sensitive)
tpKey varchar(255) NOT NULL DEFAULT '' collate utf8_bin
```
- `MyKey` ≠ `mykey`
- Frontend should preserve user's case
- Validation must use BINARY comparison

### 2. **Anonymous UID = 0** ⚠️
```php
uid: 0,  // All anonymous users use UID 0
```
- No separate anonymous user records in `tp_user` table
- Backend API validates `uid` exists, but `uid=0` bypasses this
- Ensure API accepts `uid=0` without foreign key constraint issues

### 3. **QR Parameter Tracking** ⚠️
```javascript
const qrUrl = url + '?qr=1';  // CRITICAL: Don't forget
```
- QR codes MUST include `?qr=1` parameter
- This differentiates scans from direct clicks
- Logged in `tp_log.request_uri` field
- Statistics queries filter by this parameter

### 4. **24-Hour Expiration** ⚠️
- Backend Lambda runs **every hour** (not continuously)
- Records may exist up to 1 hour past expiration
- Frontend countdown should warn when < 1 hour remains
- Don't rely on exact 24:00:00 deletion timing

### 5. **Protocol Requirement** ⚠️
```python
# Backend regex requires protocol
"pattern": r"(https?|ftps?)://[-a-zA-Z0-9@:%.+~#=]{1,256}..."
```
- Frontend MUST add `https://` if missing
- Backend validation will reject URLs without protocol
- Auto-detection: Check for valid TLD before adding

### 6. **WordPress Nonce Expiration** ⚠️
```php
wp_create_nonce('tp_intro_form_nonce');  // Expires in 24 hours by default
```
- Long sessions may encounter expired nonces
- Frontend should refresh page if nonce fails
- Especially important for intro form (24h lifecycle)

### 7. **LocalStorage Limitations** ⚠️
- Private browsing/incognito may block localStorage
- Safari has strict policies for cross-site tracking
- Always wrap in try/catch
- Provide graceful fallback (session still works, just won't restore)

### 8. **Stats Polling Performance** ⚠️
```javascript
statsInterval: 5000, // Poll every 5 seconds
```
- Don't poll too frequently (avoid API rate limits)
- Stop polling when user navigates away
- Consider visibility API to pause when tab inactive

### 9. **URL Validation Scope** ⚠️
**Client-side only (as per user decision):**
- No backend check if URL is actually reachable
- No SSL certificate validation
- No redirect following
- Users may create links to dead/invalid URLs
- This is acceptable for MVP (can add server validation later)

### 10. **Keyword Generation Quality** ⚠️
**Rule-based extraction has limitations:**
- URLs like `https://example.com/123456` → poor keywords
- Random fallback may not be memorable
- Consider showing multiple suggestions in future
- AI integration ready for enhancement

### 11. **AJAX for Anonymous Users** ⚠️
```php
// CRITICAL: Use nopriv hook for non-logged-in users
add_action('wp_ajax_nopriv_tp_intro_create_link', ...);
```
- Regular `wp_ajax_` only works for logged-in users
- IntroForm targets anonymous users
- Must use `wp_ajax_nopriv_` hook

### 12. **Database Foreign Key Constraints** ⚠️
```sql
FOREIGN KEY (uid) REFERENCES tp_user(uid)
```
- If `uid=0` doesn't exist in `tp_user`, INSERT will fail
- **SOLUTION**: Create placeholder record in `tp_user` with `uid=0`
- Or modify foreign key to allow orphan records
- Verify this during implementation

### 13. **CSS Specificity Conflicts** ⚠️
- Existing plugin CSS may conflict with intro form
- Use specific class names: `.tp-intro-` prefix
- Test with different WordPress themes
- Check Bootstrap version compatibility

### 14. **Mobile Clipboard API** ⚠️
```javascript
navigator.clipboard.readText()  // May require HTTPS + user gesture
```
- Clipboard API has strict security requirements
- May not work on HTTP (local dev)
- Requires user interaction (button click)
- Provide fallback for unsupported browsers

### 15. **Timezone Considerations** ⚠️
```sql
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
```
- Database uses server timezone (likely UTC)
- JavaScript uses browser timezone
- Expiry calculations must account for this
- Use Unix timestamps for consistency

---

## Task Checklist

### Implementation Order (Sequential)

#### Phase 1: Core Structure
- [ ] Create `includes/class-tp-intro-form.php`
- [ ] Add shortcode registration in main plugin class
- [ ] Create `templates/intro-form-template.php`
- [ ] Verify shortcode renders on test page

#### Phase 2: AJAX Handlers
- [ ] Extend `includes/class-tp-api-handler.php` with intro methods
- [ ] Add `ajax_intro_create_link()` method
- [ ] Add `ajax_intro_validate_key()` method
- [ ] Add `ajax_intro_get_stats()` method
- [ ] Add `ajax_intro_restore_session()` method
- [ ] Add `generate_intro_key()` helper method
- [ ] Test AJAX endpoints with browser console

#### Phase 3: API Client Extensions
- [ ] Add `validateKey()` method to `TrafficPortalApiClient.php`
- [ ] Add `getRecord()` method to `TrafficPortalApiClient.php`
- [ ] Add `getLinkStats()` method to `TrafficPortalApiClient.php`
- [ ] Test API methods independently

#### Phase 4: Frontend JavaScript
- [ ] Create `assets/js/intro-form.js`
- [ ] Implement form initialization
- [ ] Implement localStorage session management
- [ ] Implement form submission handler
- [ ] Implement QR code generation
- [ ] Implement expiration countdown
- [ ] Implement stats polling
- [ ] Implement keyword regeneration
- [ ] Implement session restoration
- [ ] Implement copy/download functionality
- [ ] Test all user interactions

#### Phase 5: Styling
- [ ] Create `assets/css/intro-form.css`
- [ ] Style form elements
- [ ] Style success panel
- [ ] Style error messages
- [ ] Add responsive breakpoints
- [ ] Test on mobile devices
- [ ] Test with different WordPress themes

#### Phase 6: Asset Registration
- [ ] Extend `includes/class-tp-assets.php`
- [ ] Add `enqueue_intro_assets()` method
- [ ] Enqueue CSS files
- [ ] Enqueue JavaScript files
- [ ] Add script localization
- [ ] Verify assets load only on pages with shortcode

#### Phase 7: Database Preparation
- [ ] Verify `tp_map` table exists with correct schema
- [ ] Create `uid=0` placeholder in `tp_user` table (if needed)
- [ ] Test insert with `status='intro'`
- [ ] Verify existing cleanup Lambda works with intro records

#### Phase 8: Integration Testing
- [ ] Test complete user flow (first-time visitor)
- [ ] Test session restoration (returning visitor)
- [ ] Test expiration handling
- [ ] Test click/scan tracking
- [ ] Test multiple concurrent sessions
- [ ] Test edge cases (invalid URLs, duplicate keys, etc.)

#### Phase 9: Security Review
- [ ] Verify nonce validation on all AJAX endpoints
- [ ] Test input sanitization
- [ ] Test XSS prevention
- [ ] Test CSRF protection
- [ ] Review error messages (no sensitive data leaked)

#### Phase 10: Performance Optimization
- [ ] Optimize stats polling frequency
- [ ] Add visibility API to pause polling
- [ ] Minify CSS/JS for production
- [ ] Test with slow network connection
- [ ] Profile database queries

#### Phase 11: Documentation
- [ ] Add PHP docblocks to all methods
- [ ] Add inline comments for complex logic
- [ ] Document shortcode usage
- [ ] Create user guide
- [ ] Document localStorage schema

#### Phase 12: Deployment
- [ ] Test on staging environment
- [ ] Run all validation gates
- [ ] Create git commit
- [ ] Deploy to production
- [ ] Monitor error logs
- [ ] Collect user feedback

---

## Future Enhancements (Out of Scope)

These features are designed to be added later without major refactoring:

### 1. AI Keyword Generation
**Ready for integration:**
```php
// Current: Rule-based
$key = $this->generate_intro_key($url);

// Future: AI-powered
$key = $this->generate_ai_keyword($url);
```

**Implementation hints:**
- Create new Lambda with OpenAI/Claude/Gemini integration
- New endpoint: `POST /generate-keywords`
- Return array of suggestions
- Frontend shows multiple options to choose from

### 2. Server-Side URL Validation
**Ready for integration:**
```php
// Add validation before creating link
$validation = $client->validateUrl($destination);

if (!$validation['valid']) {
    wp_send_json_error(['message' => $validation['message']]);
}
```

**Implementation hints:**
- Reuse `scheduled_mapDestinationValidator` Lambda logic
- New endpoint: `POST /validate-url`
- Check HTTP status, SSL cert, redirects
- Show suggestions for redirect targets

### 3. Thumbnail Previews
**Ready for integration:**
```javascript
// Display thumbnail in success panel
$('.tp-thumbnail-preview').html(
    '<img src="' + data.thumbnail_url + '" />'
);
```

**Implementation hints:**
- Use external service (screenshotapi.net, urlbox.io)
- Or build custom Lambda with Puppeteer/Playwright
- Cache thumbnails in S3
- Show placeholder while generating

### 4. Upgrade to Permanent Link
**Partially implemented (Save button exists):**
```php
// Redirect to registration with pre-filled data
window.location.href = '/register?key=' + key + '&destination=' + destination;
```

**Implementation hints:**
- Build registration form that accepts query params
- Update `status='intro'` to `status='active'`
- Associate with new user account
- Preserve click/scan history

### 5. Analytics Dashboard
**Data already collected:**
```sql
SELECT
    DATE(dt) as date,
    COUNT(*) as total_clicks,
    COUNT(CASE WHEN request_uri LIKE '%qr=1%' THEN 1 END) as qr_scans
FROM tp_log
WHERE status='intro'
GROUP BY DATE(dt);
```

**Implementation hints:**
- Create admin page for stats
- Show popular keywords
- Track conversion rate (intro → registered)
- Geographic distribution from `tp_log.location`

---

## Reference Documentation

### WordPress Codex
- [Shortcode API](https://developer.wordpress.org/plugins/shortcodes/)
- [AJAX in Plugins](https://developer.wordpress.org/plugins/javascript/ajax/)
- [wp_localize_script()](https://developer.wordpress.org/reference/functions/wp_localize_script/)
- [check_ajax_referer()](https://developer.wordpress.org/reference/functions/check_ajax_referer/)

### External Libraries
- [QRCode.js Documentation](https://github.com/davidshimjs/qrcodejs)
- [Bootstrap 5.3 Documentation](https://getbootstrap.com/docs/5.3/)
- [Font Awesome 6.4 Icons](https://fontawesome.com/v6/search)

### AWS Documentation
- [API Gateway REST API](https://docs.aws.amazon.com/apigateway/latest/developerguide/api-gateway-basic-concept.html)
- [Lambda Python Runtime](https://docs.aws.amazon.com/lambda/latest/dg/lambda-python.html)
- [EventBridge Scheduled Events](https://docs.aws.amazon.com/eventbridge/latest/userguide/eb-create-rule-schedule.html)

### PHP Libraries
- [cURL Documentation](https://www.php.net/manual/en/book.curl.php)
- [JSON Schema Validation](https://json-schema.org/)

---

## PRP Confidence Score

### Scoring Criteria
1. **Context Completeness** (25 points): ✅ 25/25
   - All codebase patterns documented
   - Database schema fully understood
   - API endpoints identified
   - Integration points mapped

2. **Implementation Clarity** (25 points): ✅ 24/25
   - Code examples provided
   - Patterns extracted from existing code
   - File locations specified
   - Minor ambiguity: API stats endpoint may need creation

3. **Validation Readiness** (20 points): ✅ 19/20
   - Executable test commands provided
   - Security checks documented
   - Database queries included
   - Minor gap: No automated test suite

4. **Risk Mitigation** (15 points): ✅ 15/15
   - All gotchas documented
   - Edge cases identified
   - Fallback strategies defined
   - Security considerations covered

5. **Task Organization** (15 points): ✅ 15/15
   - Clear sequential order
   - Dependencies identified
   - Logical grouping
   - Completion criteria defined

### **TOTAL SCORE: 98/100** ✅

### Confidence Level
**9.8/10** - Very high confidence for one-pass implementation

### Remaining Uncertainties
1. **API Stats Endpoint**: May need to be created if `/stats/{mid}` doesn't exist
   - Mitigation: Can query `tp_log` table directly via separate endpoint

2. **UID=0 Foreign Key**: Need to verify if `tp_user` requires placeholder record
   - Mitigation: Quick database check before implementation

3. **Nonce Refresh**: Long sessions (>24h) may need nonce refresh logic
   - Mitigation: Can be added as enhancement if issues arise

---

## Conclusion

This PRP provides comprehensive context for implementing the IntroForm feature with minimal iteration. The implementation leverages existing infrastructure (database, API, cleanup Lambda) and follows established patterns from the codebase.

**Key Success Factors:**
- ✅ No database migrations required
- ✅ Reuses existing API endpoint with `status='intro'`
- ✅ Follows WordPress/PHP conventions
- ✅ Maintains security best practices
- ✅ Designed for future AI enhancement
- ✅ Mobile-responsive and accessible

**Estimated Implementation Time:**
- Phase 1-3 (Backend): 4-6 hours
- Phase 4-5 (Frontend): 6-8 hours
- Phase 6-12 (Testing/Polish): 4-6 hours
- **Total**: 14-20 hours for experienced WordPress developer

Ready for one-pass implementation! 🚀
