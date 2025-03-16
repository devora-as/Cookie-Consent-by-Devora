# Cookie Consent by Devora

A lightweight, customizable cookie consent solution with Google Consent Mode v2 integration.

## Description

Cookie Consent by Devora is a comprehensive WordPress cookie consent management plugin that helps your website comply with GDPR, ePrivacy, and other privacy regulations. It features automatic cookie detection, customizable consent banners, and seamless integration with Google Consent Mode v2.

### Key Features

- **Customizable Banner**: Position the banner at the top, bottom, or center of the screen with customizable colors and text.
- **Google Consent Mode v2**: Native integration with Google's latest consent mode.
- **Automatic Cookie Detection**: Automatically scans and categorizes cookies used on your site.
- **Cookie Categorization**: Group cookies into necessary, analytics, functional, and marketing categories.
- **Performance Optimized**: Loads asynchronously with minimal impact on Core Web Vitals.
- **GDPR & ePrivacy Compliant**: Designed to meet European privacy regulations.
- **Google Site Kit Integration**: Seamless integration with Google's official WordPress plugin.
- **Direct Analytics Integration**: Use Google Tag Manager and GA4 without requiring Site Kit.
- **Matomo Support**: Full support for Matomo Analytics and Matomo Tag Manager.
- **Server-side Consent Logging**: Comprehensive logging for GDPR compliance documentation.
- **WP Consent API Compatible**: Works with the WordPress Consent API.
- **Bot Detection**: Automatic consent for search engine bots to ensure complete site indexing.
- **Multi-language Support**: Ready for translation with WPML, Polylang, and TranslatePress compatibility.
- **Accessibility Compliant**: Full WCAG 2.2 Level AA accessibility compliance.
- **SEO-optimized**: Schema.org structured data for cookie consent information.
- **Full HubSpot Integration**: Automatic consent management for HubSpot tracking code.

## Installation

### Automatic Updates

This plugin supports automatic updates when new releases are published on GitHub. Your WordPress admin area will notify you when updates are available, just like plugins from the WordPress.org repository.

### Manual Installation

1. Download the latest release from the [GitHub Releases page](https://github.com/devora-as/Cookie-Consent-by-Devora/releases).
2. Upload the plugin folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Configure the plugin settings under "Cookie Consent" in the WordPress admin menu.

## Configuration

1. **Banner Settings**: Customize the appearance, position, and text of your cookie consent banner.
2. **Google Integration**: Configure Google Consent Mode v2 with your GTM or GA4 details.
3. **Cookie Scanner**: Scan your site to detect and categorize cookies.
4. **Translations**: Customize all text and messages for your audience.

## Usage

### Basic Setup

1. After activation, go to Settings > Cookie Consent
2. Configure your cookie categories and descriptions
3. Customize the appearance of your cookie banner
4. Save your settings to activate the cookie consent system

### Adding the Cookie Settings Button

You can add a cookie settings button anywhere on your site using:

1. **Shortcode**: `[cookie_settings_button]`
2. **Block**: Add the "Cookie Settings Button" block in the editor
3. **Widget**: Add the "Cookie Settings" widget to any widget area
4. **PHP Function**: Use `<?php echo CustomCookieConsent\CookieConsent::get_cookie_settings_link(); ?>` in your template files

### Google Consent Mode Integration

The plugin automatically integrates with Google Consent Mode v2. To use with Google Tag Manager:

1. Configure your GTM ID in the plugin settings
2. The plugin will automatically handle consent signals to GTM
3. Use the "Default Consent Settings" to configure initial consent state

### Matomo Integration

For Matomo Analytics users:

1. Enable Matomo integration in the plugin settings
2. Enter your Matomo Site ID and URL
3. Configure tracking options (with or without cookies)
4. The plugin will automatically manage consent for Matomo tracking

## Cookie Management

The plugin manages cookie consent across four primary categories:

1. **Necessary Cookies** - Required for basic website functionality (always enabled)
2. **Analytics Cookies** - Used to collect anonymous site usage data
3. **Functional Cookies** - Enable enhanced functionality and personalization
4. **Marketing Cookies** - Support advertising and cross-site tracking functionality

Each category can be individually enabled or disabled by the user, with granular control over specific cookies within each category.

## Advanced Configuration

### Available Hooks and Filters

The plugin provides several hooks and filters for developers to extend and customize functionality:

```php
// Customize asset URL (e.g., for CDN usage)
add_filter('custom_cookie_consent_asset_url', function($url, $path) {
    return 'https://your-cdn.com/path/' . $path;
}, 10, 2);

// Modify cookie categories
add_filter('custom_cookie_consent_categories', function($categories) {
    $categories['marketing']['title'] = 'Advertising Cookies';
    return $categories;
});

// Add custom logic before consent is stored
add_action('custom_cookie_before_store_consent', function($consent_data, $user_id) {
    // Your custom logic here
}, 10, 2);
```

### Complete Hooks Reference

| Hook Name                               | Type   | Description              | Parameters                  |
| --------------------------------------- | ------ | ------------------------ | --------------------------- |
| `custom_cookie_consent_categories`      | Filter | Modify cookie categories | `$categories`               |
| `custom_cookie_consent_banner_template` | Filter | Customize banner HTML    | `$template`                 |
| `custom_cookie_consent_asset_url`       | Filter | Modify asset URLs        | `$url`, `$path`             |
| `custom_cookie_before_store_consent`    | Action | Before consent is stored | `$consent_data`, `$user_id` |
| `custom_cookie_after_store_consent`     | Action | After consent is stored  | `$consent_data`, `$user_id` |
| `custom_cookie_consent_schema`          | Filter | Modify schema.org data   | `$schema`                   |
| `custom_cookie_consent_bot_detection`   | Filter | Customize bot detection  | `$is_bot`, `$user_agent`    |
| `custom_cookie_consent_settings`        | Filter | Modify plugin settings   | `$settings`                 |

### Programmatic Consent Checking

Check for consent in your theme or plugin:

```php
if (\wp_has_consent('analytics')) {
    // Load analytics code
}

// Or using the plugin's function
if (CustomCookieConsent\CookieConsent::get_instance()->get_stored_consent()['categories']['marketing']) {
    // Load marketing pixels
}
```

## Compatibility

### Hosting Environments

Tested and compatible with:

- **Shared Hosting**: Optimized for limited resource environments
- **Managed WordPress**: Works with WP Engine, Kinsta, Flywheel, etc.
- **Cloud Platforms**: AWS, Google Cloud, Azure deployments
- **Local Environments**: Local by Flywheel, DevKinsta, XAMPP, etc.

### Caching Plugins

Compatible with popular caching solutions:

- **WP Rocket**: Special integration for cookie-based exclusions
- **W3 Total Cache**: Compatible with all caching modes
- **LiteSpeed Cache**: Full support for LSCache dynamics
- **Autoptimize**: Works with CSS/JS optimization
- **SG Optimizer**: Compatible with SiteGround's caching

### Page Builders

Works seamlessly with:

- **Elementor**: Including Elementor Pro forms and popups
- **Divi Builder**: Compatible with all Divi elements
- **Beaver Builder**: Works with all BB modules
- **Gutenberg**: Native blocks for WordPress editor
- **Classic Editor**: Traditional shortcode support

## Frequently Asked Questions

### How does automatic updating work?

The plugin checks GitHub for new releases and will notify you in the WordPress admin when updates are available. You can update with a single click just like plugins from WordPress.org.

### Is this plugin compatible with caching plugins?

Yes, the plugin is designed to work with popular caching plugins like WP Rocket, W3 Total Cache, and LiteSpeed Cache.

### How does the automatic cookie detection work?

The plugin scans your website for cookies and automatically categorizes them based on their purpose and source. It can identify cookies from common services like Google Analytics, Facebook, and others.

### Can I integrate with Google Tag Manager?

Yes, the plugin offers native integration with Google Tag Manager and implements Google Consent Mode v2 for proper consent handling.

### Does it support multilingual sites?

Yes, all user-facing text is fully customizable and compatible with translation plugins like WPML and Polylang.

### How can I export consent logs for compliance?

You can export consent logs from the "Consent Logs" tab in the plugin settings. These logs include timestamp, user ID (if available), and consent choices.

### Does the plugin slow down my website?

No, the plugin is designed for minimal performance impact and uses best practices like:

- Conditional loading of assets
- Deferred script loading
- Minimal CSS footprint
- Hardware-accelerated animations

### Is automatic bot detection reliable?

Yes, the plugin uses a multi-layered approach to detect bots, combining user agent analysis, behavioral patterns, and IP verification. This ensures high accuracy in identifying legitimate search engine crawlers.

## Performance

This plugin is optimized for performance with:

- Minimal CSS and JavaScript footprint
- Deferred loading of non-critical resources
- Conditional asset loading based on consent status
- Hardware-accelerated animations
- Optimized DOM operations with document fragments
- Full Core Web Vitals compliance

### Core Web Vitals Impact

The plugin is designed to have minimal impact on Core Web Vitals:

- **Largest Contentful Paint (LCP)**: Banner loads after critical content
- **First Input Delay (FID)**: Minimal JavaScript execution on main thread
- **Cumulative Layout Shift (CLS)**: No layout shifts caused by banner appearance
- **Interaction to Next Paint (INP)**: Optimized event handlers for responsiveness

## Privacy Considerations

This plugin processes and stores the following data:

1. **Cookie Preferences**: The consent choices made by visitors
2. **User IDs**: For logged-in users, consent is linked to their user account
3. **Timestamps**: When consent was provided or updated
4. **IP Addresses**: Optionally stored for compliance purposes (can be disabled)

All data storage complies with GDPR principles of data minimization and purpose limitation.

## Changelog

For detailed changelog information, please see the [GitHub Releases page](https://github.com/devora-as/Cookie-Consent-by-Devora/releases).

## License

This plugin is licensed under the GPL v3 or later.

## Credits

Developed by [Devora AS](https://devora.no)

## Support

For support, please visit [our support forum](https://devora.no/support) or contact us at support@devora.no.
