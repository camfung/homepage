# Traffic Portal Link Shortener Plugin

A WordPress plugin for creating short links using the Traffic Portal API with QR code generation and premium member support.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![License](https://img.shields.io/badge/license-GPL--2.0-green)

## Features

- **Short Link Creation**: Create custom short URLs via Traffic Portal API
- **QR Code Generation**: Automatically generate QR codes for all short links
- **Download QR Codes**: Download generated QR codes as PNG images
- **Copy to Clipboard**: One-click copy functionality for short URLs
- **Premium Member Support**: Restrict custom shortcodes to premium members
- **Admin Configuration**: Easy-to-use settings panel in WordPress admin
- **Self-Contained**: Includes bundled Traffic Portal API client (no external dependencies)
- **Modern UI**: Clean, responsive interface with Bootstrap 5

## Requirements

- **PHP**: 8.0 or higher
- **WordPress**: 5.8 or higher
- **Traffic Portal API Key**: Required for creating short links

## Installation

### Method 1: Clone Repository (Recommended for Development)

1. Clone the repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/camfung/tp-link-shortener-plugin.git
   ```

2. Add your Traffic Portal API credentials to `wp-config.php`:
   ```php
   // Required: Traffic Portal API Key
   define('API_KEY', 'your-api-key-here');

   // Optional: Custom API endpoint (defaults to dev environment)
   define('TP_API_ENDPOINT', 'https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev');
   ```

3. Activate the plugin in WordPress:
   - Go to **Plugins** in WordPress admin
   - Find "Traffic Portal Link Shortener"
   - Click **Activate**

### Method 2: Download and Install

1. Download the plugin as a ZIP file
2. Go to **Plugins → Add New** in WordPress admin
3. Click **Upload Plugin** and select the ZIP file
4. Click **Install Now** and then **Activate**
5. Add your API credentials to `wp-config.php` (see Method 1, step 2)

## Configuration

### Required: API Configuration

Add your Traffic Portal API key to `wp-config.php`:

```php
define('API_KEY', 'your-api-key-here');
```

### Optional: Plugin Settings

Navigate to **Settings → Link Shortener** in WordPress admin to configure:

1. **Premium-Only Custom Shortcodes** (default: disabled)
   - Enable to restrict custom shortcode creation to logged-in users only
   - When disabled, all users can create custom shortcodes

2. **API User ID** (default: 125)
   - The Traffic Portal user ID for API requests

3. **Short Link Domain** (default: dev.trfc.link)
   - The domain used for generated short URLs

## Usage

### Using the Shortcode

Add the link shortener interface to any page or post:

```
[tp_link_shortener]
```

#### With Custom Domain (Optional)

```
[tp_link_shortener domain="trfc.link"]
```

### User Interface

The shortcode displays a form with:

1. **URL Input**: Enter the long URL you want to shorten
2. **Custom Shortcode Field**: (Optional) Enter a custom shortcode
   - Automatically hidden for non-premium users if premium-only mode is enabled
3. **Register Button**: Create the short link
4. **Result Display**: Shows the short URL and QR code
   - Copy to clipboard button
   - Download QR code button

### Programmatic Usage

Access plugin functionality in your theme or other plugins:

```php
// Get plugin instance
$plugin = TP_Link_Shortener::get_instance();

// Check if premium-only mode is enabled
$is_premium = TP_Link_Shortener::is_premium_only();

// Get configuration
$domain = TP_Link_Shortener::get_domain();
$uid = TP_Link_Shortener::get_user_id();
$api_key = TP_Link_Shortener::get_api_key();
```

## Technical Details

### File Structure

```
tp-link-shortener-plugin/
├── tp-link-shortener.php              # Main plugin file
├── README.md                           # This file
├── DEVELOPMENT_PLAN.md                 # Development roadmap
├── PROJECT_CONTEXT.md                  # Complete technical context
├── includes/
│   ├── autoload.php                   # PSR-4 autoloader for API client
│   ├── class-tp-link-shortener.php    # Main plugin class
│   ├── class-tp-api-handler.php       # API wrapper with AJAX handlers
│   ├── class-tp-shortcode.php         # Shortcode registration
│   ├── class-tp-admin-settings.php    # Admin settings page
│   ├── class-tp-assets.php            # Asset management
│   └── TrafficPortal/                 # Bundled API client
│       ├── TrafficPortalApiClient.php
│       ├── DTO/
│       │   ├── CreateMapRequest.php
│       │   └── CreateMapResponse.php
│       └── Exception/
│           ├── ApiException.php
│           ├── AuthenticationException.php
│           ├── ValidationException.php
│           └── NetworkException.php
├── assets/
│   ├── css/
│   │   └── frontend.css               # Plugin styles
│   └── js/
│       └── frontend.js                # AJAX, QR code generation
└── templates/
    └── shortcode-template.php         # Shortcode HTML template
```

### API Integration

The plugin uses the Traffic Portal API to create short links:

- **Endpoint**: Configurable via `TP_API_ENDPOINT` constant
- **Authentication**: API key via `x-api-key` header
- **No Token Required**: Simplified authentication (no `tpTkn` needed)

### Security Features

- ✅ API key stored in `wp-config.php` (not in database)
- ✅ AJAX nonce verification
- ✅ URL sanitization and validation
- ✅ Input validation on client and server
- ✅ Capability checks for admin settings

### Performance

- Assets only loaded on pages with shortcode
- CDN-hosted libraries (Bootstrap, QRCode.js, Font Awesome)
- Client-side QR code generation (no server load)

## Troubleshooting

### "API_KEY not defined" Error

Make sure you've added the API key to your `wp-config.php`:

```php
define('API_KEY', 'your-api-key-here');
```

### Assets Not Loading

- Verify the plugin is activated
- Check that the shortcode is present on the page
- Check browser console for errors

### QR Code Not Generating

- Ensure QRCode.js library is loading (check browser console)
- Verify browser supports HTML5 canvas

### "401 Authentication Failed" Error

- Verify your `API_KEY` is correct
- Check the **API User ID** setting in **Settings → Link Shortener**
- Ensure the user ID exists in Traffic Portal database

### Custom Shortcode Field Not Visible

- Check **Settings → Link Shortener**
- If "Premium-Only Custom Shortcodes" is enabled, you must be logged in to see the field

## Premium Member Integration

The plugin includes a basic premium member system:

- **Current Implementation**: Uses `is_user_logged_in()` to check premium status
- **Custom Integration**: Modify `is_user_premium()` method in `includes/class-tp-api-handler.php` to integrate with your membership plugin

Example integration with popular membership plugins:

```php
// WooCommerce Memberships
private function is_user_premium(): bool {
    return wc_memberships_is_user_active_member(get_current_user_id(), 'premium');
}

// MemberPress
private function is_user_premium(): bool {
    return defined('MEPR_PLUGIN_NAME') &&
           mepr_user_has_subscription(get_current_user_id());
}
```

## Changelog

### Version 1.0.0 (2025-10-28)

- Initial release
- Core link creation functionality
- QR code generation with download
- Premium member system
- Admin configuration panel
- Bundled Traffic Portal API client

## Support

For issues, questions, or feature requests:

- **GitHub Issues**: [https://github.com/camfung/tp-link-shortener-plugin/issues](https://github.com/camfung/tp-link-shortener-plugin/issues)
- **Documentation**: See `PROJECT_CONTEXT.md` for complete technical documentation

## License

This plugin is licensed under the GNU General Public License v2.0 or later.

See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Credits

- **Traffic Portal API**: Traffic Portal Development Team
- **QR Code Library**: [QRCode.js](https://github.com/davidshimjs/qrcodejs) by davidshimjs
- **UI Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.4

---

Made with ❤️ for the Traffic Portal community
