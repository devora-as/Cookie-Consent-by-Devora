# Cookie Consent by Devora

A WordPress plugin for GDPR-compliant cookie consent management with automatic cookie detection.

=== Cookie Consent by Devora ===
Contributors: devoraas
Tested up to: 6.7
Requires PHP: 8.0
Requires at least: 6.5
Stable tag: 1.2.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

A lightweight, powerful cookie consent solution with Google Consent Mode v2 integration, built for performance and compliance.

== Description ==
A lightweight, customizable cookie consent solution with Google Consent Mode v2 integration and dynamic cookie scanning. First released in January 2025, with the latest features added in March 2025.

Cookie Consent by Devora provides a comprehensive solution for GDPR, CCPA, and other privacy regulations compliance. It offers a user-friendly interface for visitors to manage their cookie preferences while ensuring website owners can maintain analytics and marketing functionality with proper consent.

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
- 📝 **NEW (March 2025)**: Separate Cookie Policy and Privacy Policy support
- 🔍 **NEW (March 2025)**: Automatic cookie detection and categorization
- 🧠 **NEW (March 2025)**: Smart pattern matching for unknown cookies
- 🔒 **NEW (March 2025)**: Enhanced privacy protection with cookie monitoring
- 🚀 **NEW (March 2025)**: CDN support for improved performance
- 👤 **NEW (March 2025)**: User consent storage for logged-in users
- ♿ **NEW (March 2025)**: Full WCAG 2.2 Level AA accessibility compliance
- 🤖 **NEW (March 2025)**: Automatic consent for search engine bots
- 🎨 **NEW (March 2025)**: Improved styling and consistent appearance across devices
- 🔎 **NEW (March 2025)**: SEO-optimized with schema.org structured data
- 🌐 Full compliance with GDPR, CCPA, and other privacy regulations
- 📱 Fully responsive design that works on all devices
- 🔒 Secure by default with privacy-first approach
- 📊 Detailed consent logging and reporting
- 🧩 **NEW (March 2025)**: Full marketing cookie category support for ad tracking
- 🔄 **NEW (March 2025)**: Cache-friendly consent display with AJAX updates

## Key Benefits

- **Legal Compliance**: Stay compliant with GDPR, CCPA, ePrivacy, and other privacy regulations
- **User-Friendly**: Intuitive interface for visitors to manage their cookie preferences
- **Performance Optimized**: Minimal impact on page speed and Core Web Vitals
- **Developer Friendly**: Well-documented hooks and filters for customization
- **Integrated Solution**: Works seamlessly with popular analytics and marketing tools
- **Accessibility Focus**: Ensuring all users can interact with your consent mechanisms
- **SEO Friendly**: Structured data and bot detection to maintain search engine performance

## Technical Details

### Cookie Management

The plugin manages cookie consent across four primary categories:

1. **Necessary Cookies** - Required for basic website functionality (always enabled)
2. **Analytics Cookies** - Used to collect anonymous site usage data
3. **Functional Cookies** - Enable enhanced functionality and personalization
4. **Marketing Cookies** - Support advertising and cross-site tracking functionality

Each category can be individually enabled or disabled by the user, with granular control over specific cookies within each category.

### Bot Detection System

The advanced bot detection system uses both server-side and client-side techniques to identify search engine crawlers, including:

- User-Agent string matching against a comprehensive database of known bots
- Behavioral pattern analysis to detect crawler-like activity
- Integration with WordPress core bot detection functions
- IP-based verification for major search engines (optional)

When a bot is detected, full consent is automatically granted to ensure complete site indexing without consent barriers.

### Cookie Scanner Technology

The built-in cookie scanner uses multiple methods to detect and categorize cookies:

- JavaScript-based detection of client-side cookies
- Server-side scanning of Set-Cookie headers
- Integration with common services to identify known cookies
- Pattern matching to categorize unknown cookies based on naming conventions
- Regular expression parsing for cookie value analysis
- Periodic rescanning to detect new cookies

### Data Storage Architecture

Consent data is stored using a multi-layered approach:

- First-party cookies with appropriate security attributes
- localStorage fallback for browsers with cookie limitations
- Server-side storage for logged-in users via WordPress user meta
- Optional database logging for compliance purposes
- Export functionality for audit requirements

## Best Practices for GDPR Compliance

To ensure your site remains compliant with GDPR, CCPA, and other privacy regulations, follow these best practices when using the Cookie Consent plugin:

### Granular Consent Implementation

The plugin implements proper granular consent with distinct categories that comply with GDPR requirements:

1. **Necessary Cookies** - Always enabled, essential for basic site functionality
2. **Analytics Cookies** - Enabled only when the user consents to analytics
3. **Functional Cookies** - Enabled only when the user consents to functional features
4. **Marketing Cookies** - Enabled ONLY when the user explicitly consents to marketing/advertising

Each category is processed separately, ensuring that if a user only accepts analytics cookies, marketing/advertising features remain disabled. This granular approach is essential for GDPR compliance, which requires specific consent for each purpose.

### Google Consent Mode Implementation

When using Google services (Analytics, Ads, etc.), proper implementation is critical:

- **Do not** add Google Tag Manager code directly to your site. Instead, enter your GTM ID in the plugin settings to ensure proper consent handling.
- Analytics tracking only begins after the user gives consent for the analytics category.
- Marketing/advertising features (ad_storage, ad_user_data, ad_personalization) are only enabled when marketing consent is explicitly given.
- The plugin correctly separates analytics_storage and ad_storage signals to Google services.

### Recommended Settings

For optimal compliance:

1. **Banner Visibility**: Place the banner prominently at the bottom or center of the screen.
2. **Decline Button**: Always include a visible "Decline All" button that's as prominent as the "Accept" button.
3. **Default State**: Ensure all non-necessary cookie categories are unchecked by default.
4. **Cookie Settings Link**: Add a permanent cookie settings link in your footer using the shortcode `[cookie_settings]`.
5. **Consent Region**: Configure the appropriate region in the settings (Norway, EEA, or Global) based on your audience.
6. **Documentation**: Maintain a detailed cookie policy that lists all cookies and their purposes.

### Common Implementation Mistakes to Avoid

1. ❌ **Incorrect Google Tag Integration**: Adding GTM or GA4 code directly to your site instead of using the plugin's integration features.
2. ❌ **Pre-checked Boxes**: Having any non-necessary cookie categories pre-selected (this violates GDPR).
3. ❌ **No Decline Option**: Making it difficult to decline cookies or hiding the decline button.
4. ❌ **Loading Tracking Before Consent**: Allowing scripts to load before user consent is given.
5. ❌ **Ignoring Geo-specific Rules**: Not configuring the consent region appropriately for your audience.

### Handling User Consent Changes

When a user changes their consent preferences:

1. The plugin immediately updates the consent state in Google Consent Mode.
2. Previously allowed cookies from categories that are now declined will be blocked from reading/writing data.
3. New cookies from declined categories will not be set.
4. Existing cookies from declined categories should be deleted where possible.

## Screenshots

1. **Cookie Consent Banner** - The main consent banner that appears to visitors
2. **Customization Options** - Admin interface for customizing the banner appearance
3. **Cookie Categories** - Manage and categorize the cookies used on your site
4. **Integration Settings** - Configure integration with analytics and marketing tools
5. **Consent Logs** - View and export consent logs for compliance purposes

## Accessibility

The Cookie Consent by Devora plugin is fully compliant with WCAG 2.2 Level AA standards and the European Accessibility Act (EAA). Key accessibility features include:

- Keyboard navigation support
- Screen reader compatibility
- Sufficient color contrast
- Focus indicators
- Semantic HTML structure
- ARIA attributes
- Reduced motion support
- Text resizing compatibility
- Consistent navigation
- Error identification

### Detailed Accessibility Features

- **Keyboard Navigation**: All banner functionality is accessible via keyboard, with logical tab order and visible focus states
- **Screen Readers**: ARIA landmarks, roles, and labels ensure proper screen reader announcements
- **Color Contrast**: All text meets WCAG 2.2 AA contrast requirements (4.5:1 for normal text, 3:1 for large text)
- **Focus Management**: Focus is trapped within the banner when open and returned to the triggering element when closed
- **Reduced Motion**: Respects the user's prefers-reduced-motion setting to minimize animations
- **Text Sizing**: All text can be resized up to 200% without loss of content or functionality
- **Semantic Structure**: Proper heading hierarchy and semantic HTML throughout
- **Error Prevention**: Confirmation before saving preferences and clear error messages

## Installation

1. Upload the plugin files to the `/wp-content/plugins/custom-cookie-consent` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->Cookie Consent screen to configure the plugin.

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

### Custom Cookie Categories

To add or modify cookie categories:

1. Go to Settings > Cookie Consent > Categories
2. Add new categories or edit existing ones
3. Assign cookies to each category manually or use automatic detection

## Customization Options

### Banner Appearance

The plugin offers extensive customization options for the cookie banner:

- **Position**: Bottom, top, bottom-left, bottom-right, center
- **Layout**: Full-width bar, floating card, centered modal
- **Colors**: Customize background, text, button, and border colors
- **Typography**: Font family, size, and weight options
- **Spacing**: Control padding, margins, and element spacing
- **Animation**: Choose entrance/exit animations and timing
- **Mobile Adaptations**: Specific layouts for mobile devices

### Text Customization

All text in the banner can be customized:

- Banner heading and description
- Category titles and descriptions
- Button labels
- Privacy policy links
- Cookie settings link text
- Legal compliance text

### Button Styles

Buttons can be styled individually:

- **Accept Button**: Color, text, hover state, border radius
- **Decline Button**: Color, text, hover state, border radius
- **Save Preferences Button**: Color, text, hover state, border radius
- **Close Button**: Visibility, position, style

### Advanced CSS

For more detailed customization, a custom CSS field allows precise styling:

```css
/* Example customizations */
.cookie-consent-banner {
  background: linear-gradient(to right, #f8f9fa, #e9ecef);
  border-radius: 10px 10px 0 0;
}

.cookie-consent-accept {
  background: #28a745;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
```

## Integration with Third-Party Services

### Google Services

The plugin seamlessly integrates with:

- **Google Analytics 4**: Automatically respects analytics consent
- **Google Tag Manager**: Full Google Consent Mode v2 support
- **Google Ads**: Proper ad_storage and personalization consent handling
- **Google Site Kit**: Native integration with WordPress Site Kit

### Marketing Platforms

Ready-to-use integrations with:

- **HubSpot**: Automatic consent management for HubSpot tracking
- **Facebook Pixel**: Conditional loading based on marketing consent
- **LinkedIn Insight Tag**: Respects marketing consent settings
- **Twitter Pixel**: Conditional loading with consent checks

### E-commerce

Specialized support for e-commerce platforms:

- **WooCommerce**: Product recommendation cookies managed properly
- **Easy Digital Downloads**: Cart and session handling compliance
- **MemberPress**: Member authentication cookie management

### Analytics Tools

Compatible with popular analytics solutions:

- **Matomo (Piwik)**: Both self-hosted and cloud versions supported
- **Hotjar**: Proper consent handling for session recording
- **Clarity**: Microsoft Clarity integration with consent checks
- **Plausible**: Privacy-focused analytics support

## Internationalization

### Multilingual Support

The plugin supports multiple languages through:

- **WordPress Translation**: Core plugin text domain ready for translation
- **WPML Integration**: Direct compatibility with WPML for multilingual sites
- **Polylang Support**: Native support for Polylang translation
- **TranslatePress**: Compatible with TranslatePress workflows

### RTL Language Support

Full right-to-left (RTL) language support with:

- Automatically mirrored layouts for RTL languages
- Proper text alignment and direction
- RTL-specific styling adjustments
- Cultural adaptations for different regions

### Translation Files

Included translations:

- English (default)
- Norwegian Bokmål
- Norwegian Nynorsk
- Swedish
- Danish
- German
- French
- Spanish
- Italian
- Dutch

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

### Themes

Tested with popular theme frameworks:

- **Twenty Twenty-Five**: WordPress default theme
- **Astra**: Compatible with all Astra features
- **GeneratePress**: Works with GP elements and hooks
- **OceanWP**: Compatible with Ocean extensions
- **Kadence**: Works with all Kadence features

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

### Consent API Integration

This plugin implements the WordPress Consent API, allowing other plugins to check consent status:

```php
if (function_exists('wp_has_consent') && wp_has_consent('marketing')) {
    // Marketing functionality
}
```

## Frequently Asked Questions

### Is this plugin GDPR compliant?

Yes, this plugin is designed to help your website comply with GDPR, CCPA, and other privacy regulations by providing users with clear information about cookies and obtaining proper consent.

### Can I customize the appearance of the cookie banner?

Yes, you can customize colors, text, position, and other aspects of the cookie consent banner to match your website's design.

### Does this plugin work with caching plugins?

Yes, the plugin is compatible with popular caching plugins like WP Rocket, W3 Total Cache, and others.

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

### How does the plugin handle cookie expiration?

Consent is stored for 12 months by default (configurable), after which the user will be prompted again. This follows GDPR guidelines for consent refreshing.

### Can I use this plugin with Cloudflare?

Yes, the plugin is fully compatible with Cloudflare, including Rocket Loader and other optimization features. It also correctly handles proxy IP detection.

### Does it support custom cookie categories?

Yes, you can add or modify cookie categories beyond the default four (necessary, analytics, functional, and marketing). Each category can have its own title, description, and cookie list.

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

### Resource Loading

Assets are loaded efficiently:

- CSS is minified and critical styles are inlined
- JavaScript is deferred and only loaded when needed
- Images use WebP format with proper sizing
- SVGs are used for icons to reduce requests
- HTTP/2 preloading for performance-critical assets

## Privacy Considerations

This plugin processes and stores the following data:

1. **Cookie Preferences**: The consent choices made by visitors
2. **User IDs**: For logged-in users, consent is linked to their user account
3. **Timestamps**: When consent was provided or updated
4. **IP Addresses**: Optionally stored for compliance purposes (can be disabled)

All data storage complies with GDPR principles of data minimization and purpose limitation.

### Data Security

The plugin implements several security measures:

- Secure cookie attributes (HttpOnly, Secure, SameSite)
- Data sanitization and validation
- Protection against CSRF attacks
- XSS prevention
- SQL injection protection
- Rate limiting for API requests

### User Rights

The plugin supports user data rights required by privacy regulations:

- **Right to Access**: Users can view their stored consent data
- **Right to Erasure**: Delete consent data on request
- **Right to Object**: Easily withdraw consent at any time
- **Right to Restrict Processing**: Granular control over cookie categories

## Changelog

See the [changelog.txt](changelog.txt) file for a detailed list of changes.

## Roadmap

The following features are planned for upcoming versions of Cookie Consent by Devora:

### Upcoming Features

- **Enhanced Email Notifications** - Upgraded layout styling of email notifications sent when uncategorized cookies are detected
- **WordPress Multisite Support** - Full integration with WordPress Multisite / Network installations
- **Standalone Google Analytics 4 Support** - In addition to Google Site Kit integration, direct support for GA4 without requiring Site Kit
- **Consent Analytics Dashboard** - Statistics and charts showing how many users accepted your cookies (all anonymous)
- **Enhanced Multilingual Support** - Support for most commonly used languages with ability to choose language or translate strings manually
- **Cookie Database Integration** - Auto detection of cookie data and categories from cookiedatabase.org, with clear and continuously updated cookie descriptions
- **Advanced Design Customization** - Enhanced customization options for colors, effects, and text with live preview
- **Compliance Checklist** - A simple checklist outlining steps for legally compliant setup, written in non-legal language
- **Anti-Ad-Blocker System** - Functionality to ensure cookie consent works even with ad blockers enabled
- **Geo-targeted Consent Management** - Region-based consent banners with different behavior for different regions (e.g., EU vs. US)
- **Consent Optimization** - A/B testing functionality for consent banners to optimize user experience and acceptance rates
- **Cookie Policy Generator** - Built-in tool to generate a comprehensive cookie policy for your website

These planned features represent our commitment to continuously improving the Cookie Consent by Devora plugin based on user feedback and evolving privacy regulations.

## Upgrade Notice

### 1.1.9 - March 13, 2025

Fixes important issues with the WP Consent API button, marketing cookie handling, and enhances cache compatibility. Update strongly recommended for all users.

### 1.1.8 - March 12, 2025

This update includes important styling fixes and performance improvements. Update recommended for all users.

### 1.1.7 - March 10, 2025

Adds automatic bot detection and consent - improves SEO performance.

### 1.1.6 - March 8, 2025

Adds full WCAG 2.2 Level AA compliance - important for accessibility requirements.

## Credits

- Developed by the team at [Devora AS](https://devora.no)
- Uses the WordPress Consent API
- Special thanks to all our beta testers and contributors

## Support

For support, please visit [our support forum](https://devora.no/support) or contact us at support@devora.no.

## GitHub Integration for Automatic Updates

This plugin now supports automatic updates directly from GitHub releases. To set up:

1. Create a GitHub repository for your plugin (if you haven't already)
2. Replace `GITHUB_USERNAME` in the plugin header with your actual GitHub username
3. Push your plugin code to the repository
4. Create releases in GitHub with version numbers that match your plugin version

### Setting up GitHub Repository

1. Create a new repository on GitHub (e.g., `custom-cookie-consent`)
2. Initialize your local repository:
   ```
   cd /path/to/custom-cookie-consent
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/YOUR_USERNAME/custom-cookie-consent.git
   git push -u origin main
   ```

### Creating Releases

1. On GitHub, navigate to your repository
2. Click on "Releases" in the right sidebar
3. Click "Create a new release"
4. Set the tag version to match your plugin version (e.g., "1.1.9")
5. Add a title and description
6. Attach a ZIP file of your plugin if you want to use a specific build
7. Publish the release

### Plugin Header Configuration

The plugin header includes special fields that control how updates work:

```php
/**
 * GitHub Plugin URI: YOUR_USERNAME/custom-cookie-consent
 * GitHub Plugin URI: https://github.com/YOUR_USERNAME/custom-cookie-consent
 * Primary Branch: main
 * Release Asset: true
 */
```

- `GitHub Plugin URI`: Your GitHub username and repository name
- `Primary Branch`: The branch to use for updates (default: main)
- `Release Asset`: Whether to use attached release assets (true) or source code (false)

### Optional: Access Token for Private Repositories

If your repository is private, you'll need to define a GitHub access token:

1. Create a Personal Access Token on GitHub with `repo` scope
2. Add this to your wp-config.php:
   ```php
   define('GITHUB_ACCESS_TOKEN', 'your_token_here');
   ```

## Installation

1. Upload the `custom-cookie-consent` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings under the 'Cookie Consent' menu

## Usage

The plugin automatically adds a cookie consent banner to your website.

- Use the shortcode `[cookie_settings]` to add a settings button anywhere on your site
- Use the shortcode `[show_my_consent_data]` to display the user's current consent choices

## Documentation

For full documentation, please visit the [plugin website](https://devora.no/plugins/cookie-consent).
