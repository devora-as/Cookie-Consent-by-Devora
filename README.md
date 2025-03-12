# Custom Cookie Consent

A WordPress plugin for GDPR-compliant cookie consent management.

=== Custom Cookie Consent ===
Contributors: devoraas
Tested up to: 6.7
Requires PHP: 8.0
Requires at least: 6.5
Stable tag: 1.0.8
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

== Description ==
A lightweight, customizable cookie consent solution with Google Consent Mode v2 integration.

## Features

- 🍪 GDPR compliant cookie consent banner
- 🔄 Google Consent Mode v2 integration
- 📊 Google Analytics 4 support via Site Kit
- 🔌 WP Consent API compatible
- 🎯 HubSpot integration
- 🎨 Customizable design
- 🌍 Multi-language support
- ⚡ Easy-to-add cookie settings link
- ✖️ Close button with text

## Installation

1. Upload the `custom-cookie-consent` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure Google Site Kit if you're using Google Analytics
4. The cookie consent banner will automatically appear for new visitors

## Cookie Categories

The plugin manages three categories of cookies:

1. **Necessary** (Always enabled)

   - Essential for basic website functionality
   - Cannot be disabled by users
   - Includes security and session cookies

2. **Statistical**

   - Used for anonymous analytics
   - Includes Google Analytics cookies
   - Helps understand website usage

3. **Marketing**
   - Used for personalized advertising
   - Includes tracking cookies
   - Controls ad personalization

## Integration with Google Site Kit

The plugin automatically integrates with Google Site Kit and configures Google Consent Mode v2 with the following default settings:

```javascript
{
    'ad_storage': 'denied',
    'analytics_storage': 'denied',
    'functionality_storage': 'denied',
    'personalization_storage': 'denied',
    'security_storage': 'granted'
}
```

These settings are updated based on user consent choices.

## WP Consent API Integration

The plugin is fully compatible with the WP Consent API and registers the following:

- Cookie categories and their purposes
- Consent status for each category
- Cookie information for transparency

## HubSpot Integration

HubSpot tracking is automatically managed based on user consent:

- Analytics features require Statistical consent
- Chat and forms require Necessary consent

## Adding "Manage Cookies" Link

There are several ways to add the cookie settings link to your site:

### 1. Using Shortcode (Recommended)

Add the link anywhere that accepts shortcodes (posts, pages, widgets):

```
[cookie_settings]
```

Customize the link with attributes:

```
[cookie_settings class="my-custom-class" text="Cookie Settings"]
```

### 2. Direct in Template Files

Add the link directly in your template files:

```php
<?php
if (class_exists('\CustomCookieConsent\CookieConsent')) {
    echo \CustomCookieConsent\CookieConsent::get_cookie_settings_link('your-custom-class');
}
?>
```

### 3. In WordPress Menu

Add this code to your theme's `functions.php`:

```php
add_filter('wp_nav_menu_items', function($items, $args) {
    if ($args->theme_location == 'footer-menu') {
        if (class_exists('\CustomCookieConsent\CookieConsent')) {
            $items .= '<li class="menu-item">';
            $items .= \CustomCookieConsent\CookieConsent::get_cookie_settings_link('menu-link');
            $items .= '</li>';
        }
    }
    return $items;
}, 99, 2);
```

### 4. Using Widget

Add it to a text widget using the shortcode:

```
[cookie_settings]
```

## Banner Features

### Close Button

The banner includes a close button that:

- Appears in the top-right corner
- Shows "Close ×" text for clarity
- Allows users to dismiss the banner without making a choice
- Maintains accessibility with proper ARIA labels

## Customization

### CSS Classes

Key CSS classes for styling:

```css
.cookie-consent-banner    /* Main banner container */
/* Main banner container */
/* Main banner container */
/* Main banner container */
.cookie-consent-content   /* Content wrapper */
.cookie-consent-close     /* Close button */
.cookie-category          /* Individual category container */
.toggle-switch            /* Toggle switch component */
.cookie-consent-buttons; /* Button container */
```

### Filters

Available WordPress filters:

```php
// Modify cookie categories
add_filter('custom_cookie_consent_categories', function($categories) {
    return $categories;
});

// Customize consent mode settings
add_filter('custom_cookie_consent_mode_settings', function($settings) {
    return $settings;
});
```

## Development

### Building from Source

1. Clone the repository
2. Install dependencies: `npm install`
3. Build assets: `npm run build`

### Testing

Run PHP tests:

```bash
composer test
```

Run JavaScript tests:

```bash
npm test
```

## License

This project is licensed under the GPL v3 or later.

## Changelog

See [changelog.txt](changelog.txt) for a complete history of changes.
