# Traffic Portal Link Shortener Plugin - Development Plan

## Overview
WordPress plugin to create short links using Traffic Portal API with QR code generation, matching the provided screenshot interface.

## Git Milestones & Commit Strategy

### Milestone 1: Plugin Foundation ✅
**Files Created:**
- `tp-link-shortener.php` - Main plugin file
- `includes/autoload.php` - API client autoloader
- `includes/class-tp-link-shortener.php` - Main plugin class
- `includes/class-tp-assets.php` - Assets handler

**Git Commit:**
```
git add tp-link-shortener-plugin/
git commit -m "Milestone 1: Plugin foundation with basic structure

- Created main plugin file with activation/deactivation hooks
- Implemented autoloader for Traffic Portal API client
- Added main plugin class with initialization
- Created assets handler for CSS/JS enqueuing
- Set up plugin constants and defaults"
```

### Milestone 2: PHP API Client Integration
**Files to Create:**
- `includes/class-tp-api-handler.php` - Wrapper for API client

**Tasks:**
1. Create API handler that uses the TrafficPortal\TrafficPortalApiClient
2. Get API_KEY from wp-config.php (defined as API_KEY constant)
3. Handle API responses and errors
4. Implement AJAX endpoint for link creation

**Git Commit:**
```
git add includes/class-tp-api-handler.php
git commit -m "Milestone 2: Traffic Portal API client integration

- Integrated TrafficPortal PHP API client
- Created API handler wrapper class
- Configured to read API_KEY from wp-config.php
- Set up AJAX endpoints for form submission"
```

### Milestone 3: Shortcode Interface (Screenshot Match)
**Files to Create:**
- `includes/class-tp-shortcode.php` - Shortcode renderer
- `templates/shortcode-template.php` - HTML template
- `assets/css/frontend.css` - Styles matching screenshot

**Features:**
- Input field with icon (Make a key to your virtual gate)
- "Just name it" section with description
- Submit button labeled "Register" or "Create"
- Clean, minimal design matching screenshot
- NO custom shortcode field initially (non-premium)

**Git Commit:**
```
git add includes/class-tp-shortcode.php templates/ assets/css/
git commit -m "Milestone 3: Basic shortcode UI matching screenshot

- Created shortcode [tp_link_shortener]
- Built template matching provided screenshot design
- Added CSS styles for clean interface
- Implemented destination URL input field
- No custom shortcode input (premium feature)"
```

### Milestone 4: Form Submission & Link Creation
**Files to Create/Modify:**
- `assets/js/frontend.js` - Form handling and AJAX
- Modify `class-tp-api-handler.php` - Add AJAX callbacks

**Features:**
1. Form validation
2. AJAX submission
3. Generate random key automatically
4. Call API to create short link
5. Display result with success message
6. Show generated short URL

**Git Commit:**
```
git add assets/js/frontend.js includes/class-tp-api-handler.php
git commit -m "Milestone 4: Form submission and link creation

- Implemented AJAX form submission
- Added auto-generation of random shortcodes
- Integrated API client for creating links
- Added success/error message handling
- Display generated short URL to user"
```

### Milestone 5: QR Code Generation
**Files to Modify:**
- `assets/js/frontend.js` - Add QR code generation
- `assets/css/frontend.css` - QR code styling
- `templates/shortcode-template.php` - QR code container

**Features:**
1. Use QRCode.js library (already enqueued)
2. Generate QR code for created short link
3. Display QR code below generated link
4. Add download button for QR code

**Implementation from tp-homepage:**
```javascript
createQRCode: function(url, options = {}) {
    const defaultOptions = {
        width: 156,
        height: 156,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    };

    const qrOptions = { ...defaultOptions, ...options };
    new QRCode(container, {
        text: url,
        ...qrOptions
    });
}
```

**Git Commit:**
```
git add assets/js/frontend.js assets/css/frontend.css templates/shortcode-template.php
git commit -m "Milestone 5: QR code generation integration

- Integrated QRCode.js library
- Generate QR code for created shortlinks
- Added QR code display container
- Styled QR code section
- Based on tp-homepage implementation"
```

### Milestone 6: Premium Member Configuration
**Files to Create:**
- `includes/class-tp-admin-settings.php` - Admin settings page
- `templates/admin-settings.php` - Settings interface

**Features:**
1. Admin settings page under Settings menu
2. Toggle: "Allow custom shortcodes for premium members only"
3. Configuration for user ID
4. Configuration for default domain
5. When premium-only is enabled:
   - Show custom shortcode input field in shortcode
   - Add validation to check if user is premium
   - Fallback to random if non-premium user tries custom

**Settings Interface:**
```
Traffic Portal Settings
━━━━━━━━━━━━━━━━━━━━━━
□ Enable Premium-Only Custom Shortcodes
  When enabled, only premium members can enter custom shortcodes.
  Non-premium users will get automatically generated codes.

User ID for API: [125]
Default Domain: [dev.trfc.link]

[Save Settings]
```

**Git Commit:**
```
git add includes/class-tp-admin-settings.php templates/admin-settings.php
git commit -m "Milestone 6: Premium member configuration system

- Created admin settings page
- Added premium-only toggle for custom shortcodes
- Implemented user ID configuration
- Added domain configuration
- Updated shortcode to respect premium settings
- Hide/show custom input based on premium status"
```

### Milestone 7: Final Polish & Documentation
**Files to Create/Modify:**
- `README.md` - User documentation
- `INSTALLATION.md` - Installation guide
- Add inline code documentation
- Create example wp-config.php snippet

**Git Commit:**
```
git add README.md INSTALLATION.md
git commit -m "Milestone 7: Documentation and final polish

- Added comprehensive README
- Created installation guide
- Documented API_KEY configuration
- Added usage examples
- Plugin ready for v1.0 release"
```

### Final Release
```
git tag -a v1.0.0 -m "Traffic Portal Link Shortener Plugin v1.0.0

Features:
- Create short links via Traffic Portal API
- QR code generation for shortlinks
- Premium member custom shortcode support
- Clean UI matching design specifications
- WordPress admin configuration panel"

git push origin main --tags
```

## File Structure
```
tp-link-shortener-plugin/
├── tp-link-shortener.php          # Main plugin file
├── README.md                       # Documentation
├── INSTALLATION.md                 # Installation guide
├── DEVELOPMENT_PLAN.md            # This file
├── includes/
│   ├── autoload.php               # API client autoloader
│   ├── class-tp-link-shortener.php # Main plugin class
│   ├── class-tp-shortcode.php     # Shortcode handler
│   ├── class-tp-api-handler.php   # API wrapper
│   ├── class-tp-admin-settings.php # Settings page
│   └── class-tp-assets.php        # Assets handler
├── assets/
│   ├── css/
│   │   └── frontend.css           # Frontend styles
│   ├── js/
│   │   └── frontend.js            # Frontend JavaScript
│   └── images/
│       └── icon.png               # Plugin icon
└── templates/
    ├── shortcode-template.php     # Shortcode HTML
    └── admin-settings.php         # Admin settings HTML
```

## Configuration (wp-config.php)
```php
// Traffic Portal API Configuration
define('API_KEY', 'your-api-key-here');
define('TP_API_ENDPOINT', 'https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev');
```

## Shortcode Usage
```
[tp_link_shortener]
```

## Features Checklist
- [x] Plugin foundation
- [x] API client integration
- [ ] Screenshot-matching UI
- [ ] Form submission
- [ ] Auto-generate shortcodes
- [ ] QR code generation
- [ ] Premium member settings
- [ ] Admin configuration panel
- [ ] Documentation

## Next Steps
Continue with Milestone 3: Create the shortcode interface and template.
