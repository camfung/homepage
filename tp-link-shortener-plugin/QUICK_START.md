# Quick Start Guide

Get the Traffic Portal Link Shortener plugin up and running in 5 minutes!

## Installation (3 steps)

### 1. Clone into WordPress

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/camfung/tp-link-shortener-plugin.git
```

### 2. Add API Key to wp-config.php

Open your `wp-config.php` file and add:

```php
// Traffic Portal API Configuration
define('API_KEY', 'your-api-key-here');

// Optional: Custom endpoint (defaults to dev)
define('TP_API_ENDPOINT', 'https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev');
```

**Important**: Add this BEFORE the line that says `/* That's all, stop editing! */`

### 3. Activate in WordPress

1. Go to **Plugins** in WordPress admin
2. Find "Traffic Portal Link Shortener"
3. Click **Activate**

Done! ✅

## Usage

### Add to Any Page

Edit any page or post and add the shortcode:

```
[tp_link_shortener]
```

### Configure Settings (Optional)

Go to **Settings → Link Shortener** to configure:

- **Premium-Only Mode**: Restrict custom shortcodes to logged-in users
- **User ID**: Your Traffic Portal user ID (default: 125)
- **Domain**: Short link domain (default: dev.trfc.link)

## Testing

### Quick Test

1. Create a new page: **Pages → Add New**
2. Add the shortcode: `[tp_link_shortener]`
3. Publish and view the page
4. Enter a URL and click **Register**
5. You should see:
   - Short URL created
   - QR code generated
   - Copy and download buttons working

### Test API Connection

If you get errors, check:

1. **API_KEY is defined** in `wp-config.php`
2. **User ID is correct** in Settings → Link Shortener
3. **Check error logs**: `wp-content/debug.log` (if WP_DEBUG is enabled)

## Common Issues

### "API_KEY not defined"

Add to `wp-config.php`:
```php
define('API_KEY', 'your-api-key-here');
```

### Assets not loading

Make sure you added the shortcode to the page: `[tp_link_shortener]`

### "Authentication failed"

- Check your API_KEY is correct
- Verify User ID in Settings → Link Shortener

## File Structure

```
tp-link-shortener-plugin/
├── tp-link-shortener.php       # Main plugin file
├── README.md                    # Full documentation
├── QUICK_START.md              # This file
├── includes/
│   ├── TrafficPortal/          # Bundled API client (self-contained)
│   └── class-*.php             # Plugin classes
├── assets/
│   ├── css/frontend.css
│   └── js/frontend.js
└── templates/
    └── shortcode-template.php
```

## Self-Contained Design

The plugin includes everything it needs:

✅ **Traffic Portal API Client** - Bundled in `includes/TrafficPortal/`
✅ **No External Dependencies** - Just clone and activate
✅ **PSR-4 Autoloading** - Automatic class loading
✅ **WordPress Standards** - Follows all WP best practices

## Next Steps

- Read [README.md](README.md) for complete documentation
- Check [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) for technical details
- Review [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md) for development roadmap

## Support

**Issues**: https://github.com/camfung/tp-link-shortener-plugin/issues

---

Happy link shortening! 🚀
