<?php

/**
 * Plugin Name: Cookie Consent by Devora
 * Plugin URI: https://devora.no/plugins/cookie-consent
 * Description: A lightweight, customizable cookie consent solution with Google Consent Mode v2 integration.
 * Version: 1.2.0
 * Author: Devora AS
 * Author URI: https://devora.no
 * License: GPL v3 or later
 * License URI: http://www.gnu.org/licenses/gpl.html
 * Text Domain: custom-cookie-consent
 * Domain Path: /languages
 * GitHub Plugin URI: devora-as/custom-cookie-consent
 * GitHub Plugin URI: https://github.com/devora-as/custom-cookie-consent
 * Primary Branch: main
 * Release Asset: true
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace CustomCookieConsent;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin version
define('CUSTOM_COOKIE_VERSION', '1.2.0');

// Define plugin database version
define('CUSTOM_COOKIE_DB_VERSION', '1.0');

// Require dependencies
require_once plugin_dir_path(__FILE__) . 'includes/class-cookie-categories.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cookie-scanner.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-integrations.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-banner-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wp-consent-wrapper.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-github-updater.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-consent-logger.php';

/**
 * Custom Cookie Consent Plugin
 *
 * Handles GDPR-compliant cookie consent with Google Consent Mode v2 support.
 * Integrates with WordPress, Google Site Kit, and HubSpot.
 * Features automatic cookie scanning and categorization.
 *
 * @package CustomCookieConsent
 * @since 1.0.0
 */
class CookieConsent
{
    /**
     * @var CookieConsent|null
     */
    private static $instance = null;

    /**
     * @var string
     */
    private $storageKey = 'devora_cookie_consent';

    /**
     * @var CookieScanner
     */
    private $cookie_scanner;

    /**
     * @var AdminInterface
     */
    private $admin_interface;

    /**
     * @var Integrations
     */
    private $integrations;

    /**
     * @var BannerGenerator
     */
    private $banner_generator;

    /**
     * @var array
     */
    private $settings;

    /**
     * Gets the singleton instance of the class.
     *
     * @return CookieConsent
     */
    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Initialize classes
        $this->cookie_scanner = new CookieScanner();
        $this->admin_interface = new AdminInterface();
        $this->integrations = new Integrations();
        $this->banner_generator = new BannerGenerator();

        // Initialize GitHub updater
        if (class_exists('\\CustomCookieConsent\\GitHubUpdater')) {
            GitHubUpdater::init(__FILE__);
        }

        // Get saved settings
        $this->settings = get_option('custom_cookie_settings', []);

        // Debug log the integration settings
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('__construct() - Initializing plugin with integration settings:', [
                'wp_consent_api' => isset($this->settings['wp_consent_api']) ? $this->settings['wp_consent_api'] : false,
                'sitekit_integration' => isset($this->settings['sitekit_integration']) ? $this->settings['sitekit_integration'] : false,
                'hubspot_integration' => isset($this->settings['hubspot_integration']) ? $this->settings['hubspot_integration'] : false
            ]);
        }

        // Initialize plugin
        $this->init();

        // Register assets and output
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_head', [$this, 'output_consent_mode']);
        add_action('wp_head', [$this, 'output_consent_nonce']);
        add_action('wp_footer', [$this, 'load_full_css'], 999);

        // Register AJAX handlers
        add_action('wp_ajax_save_cookie_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_save_integration_settings', [$this, 'ajax_save_integration_settings']);
        add_action('wp_ajax_save_cookie_consent', [$this, 'ajax_save_consent']);
        add_action('wp_ajax_nopriv_save_cookie_consent', [$this, 'ajax_save_consent']);
        // Also register with the new action name for compatibility
        add_action('wp_ajax_custom_cookie_save_consent', [$this, 'ajax_save_consent']);
        add_action('wp_ajax_nopriv_custom_cookie_save_consent', [$this, 'ajax_save_consent']);
        // Add AJAX endpoint for fetching consent data
        add_action('wp_ajax_get_cookie_consent_data', [$this, 'ajax_get_consent_data']);
        add_action('wp_ajax_nopriv_get_cookie_consent_data', [$this, 'ajax_get_consent_data']);

        // Register shortcodes
        add_shortcode('cookie_settings', [$this, 'cookie_settings_shortcode']);
        add_shortcode('show_my_consent_data', [$this, 'show_consent_data_shortcode']);

        // WP Consent API integration
        if (!empty($this->settings['wp_consent_api'])) {
            $this->register_cookies();
        }

        // Site Kit integration
        if (!empty($this->settings['sitekit_integration'])) {
            add_filter('googlesitekit_consent_mode_settings', [$this, 'filter_sitekit_consent_settings']);
        }

        // Sitemap exclusions
        add_filter('wp_sitemaps_post_types', [$this, 'exclude_from_sitemap']);
        add_filter('robots_txt', [$this, 'modify_robots_txt'], 10, 1);

        // Privacy data exporters and erasers
        add_filter('wp_privacy_personal_data_exporters', [$this, 'register_privacy_exporters']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'register_privacy_erasers']);
        add_action('admin_init', [$this, 'add_privacy_policy_content']);

        // Schema.org structured data
        add_action('wp_head', [$this, 'output_consent_schema']);

        // Register activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    /**
     * Initializes the plugin.
     *
     * @return void
     */
    public function init(): void
    {
        // Check if database needs updating
        $this->maybe_update_db_schema();

        // Register admin scripts and styles
        add_action('admin_enqueue_scripts', function ($hook) {
            if (strpos($hook, 'custom-cookie') !== false) {
                wp_enqueue_style('custom-cookie-admin-style', plugin_dir_url(__FILE__) . 'admin/css/admin-style.css', [], '1.0.0');
                wp_enqueue_script('custom-cookie-admin-script', plugin_dir_url(__FILE__) . 'admin/js/admin-script.js', ['jquery'], '1.0.0', true);

                // Localize script with settings and nonces
                wp_localize_script('custom-cookie-admin-script', 'customCookieAdminSettings', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('cookie_management'),
                    'scanNonce' => wp_create_nonce('cookie_scan'),
                    'messages' => [
                        'scanComplete' => __('Scan completed successfully', 'custom-cookie-consent'),
                        'cookieCategorized' => __('Cookie categorized successfully', 'custom-cookie-consent'),
                        'bulkCategorized' => __('Cookies categorized successfully', 'custom-cookie-consent'),
                        'settingsSaved' => __('Banner settings saved successfully', 'custom-cookie-consent'),
                        'scannerSaved' => __('Scanner settings saved successfully', 'custom-cookie-consent'),
                        'integrationSaved' => __('Integration settings saved successfully', 'custom-cookie-consent')
                    ]
                ]);
            }
        });

        // Register frontend scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Handle admin actions related to cookie consent
     *
     * @return void
     */
    public function handle_admin_actions(): void
    {
        // Handle AJAX calls for settings
        \add_action('wp_ajax_save_cookie_settings', [$this, 'ajax_save_settings']);
    }

    /**
     * Handles AJAX request to save settings.
     */
    public function ajax_save_settings(): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('ajax_save_settings() - Received settings save request', $_POST);
        }

        if (!isset($_POST['nonce']) || !\wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'cookie_management')) {
            \wp_send_json_error(['message' => __('Invalid security token. Please refresh the page and try again.', 'custom-cookie-consent')]);
            return;
        }

        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => __('Permission denied. You do not have sufficient permissions to modify these settings.', 'custom-cookie-consent')]);
            return;
        }

        $settings = [];

        // Sanitize and save settings - comprehensive list of all possible text fields
        $text_fields = [
            // Banner settings
            'banner_title',
            'banner_text',
            'position',
            'privacy_url',
            'privacy_text',
            'cookie_policy_url',
            'cookie_policy_text',
            'close_button_text',
            'close_button_aria_label',

            // Button text
            'accept_button',
            'decline_button',
            'save_button',
            'change_settings_button',

            // Category titles and descriptions
            'necessary_title',
            'necessary_description',
            'analytics_title',
            'analytics_description',
            'functional_title',
            'functional_description',
            'marketing_title',
            'marketing_description',

            // Consent data display
            'consent_choices_heading',
            'active_cookies_heading',
            'consent_status_accepted',
            'consent_status_declined',
            'cookie_category_label',
            'cookie_purpose_label',
            'cookie_expiry_label',
            'sources_label',
            'consent_last_updated',
            'no_cookies_message',

            // Scanner settings
            'scan_frequency'
        ];

        // Get existing settings first to avoid unnecessary updates
        $existing_settings = \get_option('custom_cookie_settings', []);
        $settings = $existing_settings;
        $changed = false;

        // Process text fields
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                $new_value = sanitize_text_field(wp_unslash($_POST[$field]));
                // Only update if the value has changed
                if (!isset($existing_settings[$field]) || $existing_settings[$field] !== $new_value) {
                    $settings[$field] = $new_value;
                    $changed = true;
                }
            }
        }

        // Checkbox fields - explicitly handle all checkboxes
        $checkbox_fields = [
            'auto_scan',
            'defer_css',
            'wp_consent_api',
            'sitekit_integration',
            'hubspot_integration'
        ];

        foreach ($checkbox_fields as $field) {
            $new_value = isset($_POST[$field]) && $_POST[$field] == '1';
            // Only update if the value has changed
            if (!isset($existing_settings[$field]) || (bool)$existing_settings[$field] !== $new_value) {
                $settings[$field] = $new_value;
                $changed = true;
            }
        }

        // Only update if something has changed
        if ($changed) {
            // Add a timestamp to force template refresh
            $settings['last_updated'] = time();

            // Debug the final settings before saving
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log('ajax_save_settings() - Settings before save:', $settings);
            }

            // Save settings
            $updated = \update_option('custom_cookie_settings', $settings);

            // Debug the update result
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log('ajax_save_settings() - Update result:', [
                    'updated' => $updated,
                    'settings' => $settings
                ]);
            }

            // Force regeneration of the banner template with the new translations
            if ($updated) {
                // Force regeneration of the banner template
                $banner_generator = new BannerGenerator();
                $banner_generator->update_banner_template();

                // Delete any cached version of the banner template
                \delete_transient('custom_cookie_consent_banner_template');

                // Add timestamp to force refresh on client side
                $settings['template_updated'] = time();
                \update_option('custom_cookie_settings', $settings);

                $this->debug_log('ajax_save_settings() - Banner template regenerated', [
                    'timestamp' => time()
                ]);
            }

            // Trigger banner generation and reset cron schedule if needed
            \do_action('custom_cookie_rules_updated');

            if (isset($_POST['scan_frequency'])) {
                \do_action('custom_cookie_scan_schedule_updated');
            }

            if ($updated) {
                \wp_send_json_success(['message' => __('Settings saved successfully', 'custom-cookie-consent')]);
            } else {
                \wp_send_json_error(['message' => __('Error saving settings. Please try again.', 'custom-cookie-consent')]);
            }
        } else {
            // No changes were made
            \wp_send_json_success(['message' => __('No changes were made to settings', 'custom-cookie-consent')]);
        }
    }

    /**
     * Handles AJAX request to save integration settings.
     * This is a dedicated endpoint for integration settings to ensure they're properly saved.
     */
    public function ajax_save_integration_settings(): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('ajax_save_integration_settings() - Received integration settings save request', $_POST);
        }

        if (!isset($_POST['nonce']) || !\wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'cookie_management')) {
            \wp_send_json_error(['message' => __('Invalid security token. Please refresh the page and try again.', 'custom-cookie-consent')]);
            return;
        }

        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => __('Permission denied. You do not have sufficient permissions to modify these settings.', 'custom-cookie-consent')]);
            return;
        }

        // Get existing settings
        $existing_settings = \get_option('custom_cookie_settings', []);

        // Process integration checkboxes with explicit true/false values
        $integration_fields = [
            'wp_consent_api',
            'sitekit_integration',
            'hubspot_integration'
        ];

        foreach ($integration_fields as $field) {
            if (isset($_POST[$field])) {
                // Convert "1" to true and "0" to false
                $existing_settings[$field] = ($_POST[$field] === "1");
            } else {
                // If not set, default to false
                $existing_settings[$field] = false;
            }
        }

        // Debug log the integration settings being saved
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('ajax_save_integration_settings() - Integration settings being saved:', [
                'wp_consent_api' => $existing_settings['wp_consent_api'],
                'sitekit_integration' => $existing_settings['sitekit_integration'],
                'hubspot_integration' => $existing_settings['hubspot_integration']
            ]);
        }

        // Save the updated settings
        $updated = \update_option('custom_cookie_settings', $existing_settings);

        // Debug log the result
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('ajax_save_integration_settings() - Update result:', [
                'updated' => $updated,
                'wp_consent_api' => $existing_settings['wp_consent_api'],
                'sitekit_integration' => $existing_settings['sitekit_integration'],
                'hubspot_integration' => $existing_settings['hubspot_integration']
            ]);
        }

        if ($updated) {
            \wp_send_json_success(['message' => __('Integration settings saved successfully', 'custom-cookie-consent')]);
        } else {
            \wp_send_json_error(['message' => __('No changes made or error saving integration settings', 'custom-cookie-consent')]);
        }
    }

    /**
     * Registers cookies with the WP Consent API.
     */
    public function register_cookies(): void
    {
        // Use our custom wrapper class for compatibility
        // Register necessary cookies
        WPConsentWrapper::register_cookie(
            '__hssc',
            'HubSpot',
            'necessary',
            'HubSpot cookie used for website analytics',
            '30 minutes'
        );

        WPConsentWrapper::register_cookie(
            '__hssrc',
            'HubSpot',
            'necessary',
            'HubSpot cookie used to track sessions',
            'End of session'
        );

        WPConsentWrapper::register_cookie(
            '__cf_bm',
            'Cloudflare',
            'necessary',
            'Cloudflare bot protection cookie',
            '30 minutes'
        );

        WPConsentWrapper::register_cookie(
            '_cfuvid',
            'Cloudflare',
            'necessary',
            'Cloudflare unique visitor identification for bot protection',
            '1 year'
        );

        // Register analytics cookies
        WPConsentWrapper::register_cookie(
            'hubspotutk',
            'HubSpot',
            'analytics',
            'HubSpot cookie used for visitor identification',
            '13 months'
        );

        WPConsentWrapper::register_cookie(
            '__hstc',
            'HubSpot',
            'analytics',
            'HubSpot cookie for cross-domain tracking',
            '13 months'
        );

        WPConsentWrapper::register_cookie(
            '_ga',
            'Google Analytics',
            'analytics',
            'Google Analytics cookie used to distinguish users',
            '2 years'
        );

        WPConsentWrapper::register_cookie(
            '_gid',
            'Google Analytics',
            'analytics',
            'Google Analytics cookie used to distinguish users',
            '24 hours'
        );

        // Register functional cookies
        WPConsentWrapper::register_cookie(
            '_lscache_vary',
            'LiteSpeed Cache',
            'functional',
            'LiteSpeed Cache cookie for handling variations',
            'End of session'
        );
    }

    /**
     * Sets the consent type.
     *
     * @param string $type
     * @return string
     */
    public function set_consent_type(string $type): string
    {
        // Default to opt-in for GDPR compliance
        return 'optin';
    }

    /**
     * Gets the asset URL with CDN support.
     *
     * @param string $path The relative path to the asset.
     * @return string The full URL to the asset, potentially modified for CDN.
     */
    private function get_asset_url(string $path): string
    {
        $default_url = \plugins_url($path, __FILE__);

        /**
         * Filter the base URL for plugin assets.
         * 
         * This filter allows CDN URLs to be used for plugin assets.
         * 
         * @since 1.1.2
         * 
         * @param string $default_url The default URL to the asset.
         * @param string $path        The relative path to the asset.
         */
        return esc_url(apply_filters('custom_cookie_consent_asset_url', $default_url, $path));
    }

    /**
     * Checks if the current user agent is a bot/crawler
     *
     * @return bool True if the user agent is a bot
     */
    private function is_bot(): bool
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $user_agent = strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])));

        $bot_patterns = [
            'googlebot',
            'bingbot',
            'yandexbot',
            'duckduckbot',
            'slurp',
            'baiduspider',
            'facebookexternalhit',
            'linkedinbot',
            'twitterbot',
            'applebot',
            'msnbot',
            'aolbuild',
            'yahoo',
            'teoma',
            'sogou',
            'exabot',
            'facebot',
            'ia_archiver',
            'semrushbot',
            'ahrefsbot',
            'mj12bot',
            'seznambot',
            'yeti',
            'naverbot',
            'crawler',
            'spider',
            'mediapartners-google',
            'adsbot-google',
            'feedfetcher',
            'bot',
            'crawl',
            'slurp',
            'spider',
            'mediapartners',
            'lighthouse'
        ];

        foreach ($bot_patterns as $pattern) {
            if (strpos($user_agent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enqueues the assets.
     *
     * @return void
     */
    public function enqueue_assets(): void
    {
        // Don't load assets for admin pages
        if (is_admin()) {
            return;
        }

        // Get settings
        $settings = get_option('custom_cookie_settings', []);

        // Get the latest template timestamp for cache busting
        $template_timestamp = get_option('custom_cookie_banner_last_updated', time());

        // Prepare version string for cache busting
        $version = defined('WP_DEBUG') && WP_DEBUG ? time() : $template_timestamp;

        // Main CSS - only enqueue if not set to defer
        if (empty($settings['defer_css'])) {
            wp_enqueue_style(
                'custom-cookie-css',
                $this->get_asset_url('css/cookie-consent.css'),
                [],
                $version
            );
        }

        // Banner template script
        wp_enqueue_script(
            'custom-cookie-template',
            $this->get_asset_url('js/banner-template.js'),
            [],
            $version,
            true
        );

        // Get the server-generated template
        $banner_template = get_option('custom_cookie_banner_template', '');

        // If we have a server-generated template, add it as an inline script before banner-template.js loads
        if (!empty($banner_template)) {
            // This ensures the template with translations is loaded before the banner-template.js executes
            wp_add_inline_script(
                'custom-cookie-template',
                $banner_template,
                'before'
            );
        }

        // Dynamic cookie enforcer rules
        wp_enqueue_script(
            'custom-cookie-rules',
            $this->get_asset_url('js/dynamic-enforcer-rules.js'),
            [],
            $version,
            true
        );

        // Cookie enforcer script
        wp_enqueue_script(
            'custom-cookie-enforcer',
            $this->get_asset_url('js/cookie-enforcer.js'),
            ['custom-cookie-rules'],
            $version,
            true
        );

        // Main consent script - load after template
        wp_enqueue_script(
            'custom-cookie-js',
            $this->get_asset_url('js/consent-manager.js'),
            ['custom-cookie-template'],
            $version,
            true
        );

        // Add settings to the page
        wp_localize_script('custom-cookie-js', 'cookieConsentSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'position' => $settings['position'] ?? 'bottom',
            'privacyUrl' => $settings['privacy_url'] ?? '',
            'cookiePolicyUrl' => $settings['cookie_policy_url'] ?? '',
            'consentVersion' => '1',
            'gtmId' => $settings['gtm_id'] ?? '',
            'debug' => defined('WP_DEBUG') && WP_DEBUG ? true : false,
            'isBot' => $this->is_bot(),
            'templateTimestamp' => $template_timestamp
        ]);

        // If CSS is deferred, output the inline preload
        if (!empty($settings['defer_css'])) {
            add_action('wp_head', [$this, 'load_full_css']);
        }
    }

    /**
     * Load the full CSS for the cookie consent banner.
     * This is called from wp_footer when CSS loading is deferred.
     */
    public function load_full_css(): void
    {
        // Get the latest template timestamp for cache busting
        $template_timestamp = get_option('custom_cookie_banner_last_updated', time());
        $version = defined('WP_DEBUG') && WP_DEBUG ? time() : $template_timestamp;

        // Use the correct CSS file
        $css_url = $this->get_asset_url('css/cookie-consent.css');

        // Add version parameter for cache busting
        $css_url = add_query_arg('ver', $version, $css_url);

        echo '<link rel="stylesheet" href="' . esc_url($css_url) . '" media="all">';

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cookie Consent: Loading full CSS with version ' . $version);
        }
    }

    /**
     * Outputs the consent mode script.
     *
     * @return void
     */
    public function output_consent_mode(): void
    {
        // Remove inline critical CSS output since we're now inlining all CSS
?>
        <script>
            // Defer dataLayer initialization
            window.addEventListener('DOMContentLoaded', function() {
                window.dataLayer = window.dataLayer || [];

                function gtag() {
                    dataLayer.push(arguments);
                }

                // Set default consent state
                const defaultConsent = {
                    'ad_storage': 'denied',
                    'analytics_storage': 'denied',
                    'functionality_storage': 'denied',
                    'personalization_storage': 'denied',
                    'security_storage': 'granted',
                    'ad_user_data': 'denied',
                    'ad_personalization': 'denied',
                    'wait_for_update': 2000,
                    'region': ['NO']
                };

                gtag('consent', 'default', defaultConsent);

                // Add consent update listener that works with Site Kit
                window.addEventListener('consentUpdated', function(e) {
                    if (e.detail && e.detail.analytics === true) {
                        const consentUpdate = {
                            'ad_storage': 'granted',
                            'analytics_storage': 'granted',
                            'ad_user_data': 'granted',
                            'ad_personalization': 'granted'
                        };
                        gtag('consent', 'update', consentUpdate);

                        // Force a Site Kit analytics update
                        if (window.googlesitekit) {
                            window.googlesitekit.dispatch('modules/analytics-4').setConsentState(consentUpdate);
                        }
                    }
                });
            });
        </script>
<?php
    }

    /**
     * Filters the Site Kit consent settings.
     *
     * @param array $settings
     * @return array
     */
    public function filter_sitekit_consent_settings(array $settings): array
    {
        $analytics_consent = WPConsentWrapper::has_consent('analytics');

        // Map consent settings for Site Kit
        $consent_settings = [
            'ad_storage' => $analytics_consent ? 'granted' : 'denied',
            'analytics_storage' => $analytics_consent ? 'granted' : 'denied',
            'functionality_storage' => WPConsentWrapper::has_consent('functional') ? 'granted' : 'denied',
            'personalization_storage' => WPConsentWrapper::has_consent('functional') ? 'granted' : 'denied',
            'security_storage' => 'granted',
            'ad_user_data' => $analytics_consent ? 'granted' : 'denied',
            'ad_personalization' => $analytics_consent ? 'granted' : 'denied'
        ];

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('Site Kit Consent Settings:', $consent_settings);
        }

        return $consent_settings;
    }

    /**
     * Gets the cookie settings link.
     *
     * @param string $class
     * @param string $text
     * @return string
     */
    public static function get_cookie_settings_link(string $class = '', string $text = ''): string
    {
        $settings = get_option('custom_cookie_settings', []);

        if (empty($text)) {
            $text = $settings['change_settings_button'] ?? \__('Administrer informasjonskapsler', 'custom-cookie-consent');
        }
        return sprintf(
            '<a href="#" class="cookie-settings-trigger %s">%s</a>',
            \esc_attr($class),
            \esc_html($text)
        );
    }

    /**
     * Renders the cookie settings shortcode.
     *
     * @param array $atts
     * @return string
     */
    public function cookie_settings_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'class' => '',
            'text' => ''
        ], $atts);

        return self::get_cookie_settings_link($atts['class'], $atts['text']);
    }

    /**
     * Logs debug messages.
     *
     * @param string $message
     * @param mixed|null $data
     * @param string $prefix
     * @return void
     */
    private function debug_log(string $message, $data = null, string $prefix = ''): void
    {
        // Only log if WP_DEBUG is enabled
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        // phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
        // phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
        // The following code is for development purposes only and will not run in production

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($trace[1]['function']) ? $trace[1]['function'] : '';
        $line = isset($trace[0]['line']) ? $trace[0]['line'] : '';
        $file = isset($trace[0]['file']) ? basename($trace[0]['file']) : '';

        $log_prefix = "[Cookie Consent Debug]";
        if ($prefix) {
            $log_prefix .= " {$prefix}";
        }
        $log_prefix .= " [{$file}:{$line}] {$caller}()";

        error_log("{$log_prefix} - {$message}");

        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                error_log("{$log_prefix} Data: " . \wp_json_encode($data, JSON_PRETTY_PRINT));
            } else {
                error_log("{$log_prefix} Data: {$data}");
            }
        }
        // phpcs:enable
    }

    /**
     * Gets the user consent data for display.
     *
     * @return array
     */
    public function get_user_consent_data(): array
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log("Starting consent data collection");
        }

        // Initialize with default values (only necessary cookies are permitted by default)
        $consent_data = array(
            'cookies_present' => array(),
            'consent_status' => array(
                'necessary' => true,
                'analytics' => false,
                'functional' => false,
                'marketing' => false
            ),
            'cookies_blocked' => array()
        );

        // Get stored consent from cookie or localStorage (this is the source of truth)
        $stored_consent = $this->get_stored_consent();

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log("Stored consent data:", $stored_consent);
        }

        // If we have stored consent from the client, use that as the primary source
        if ($stored_consent && isset($stored_consent['categories'])) {
            foreach ($stored_consent['categories'] as $category => $status) {
                if (isset($consent_data['consent_status'][$category])) {
                    $consent_data['consent_status'][$category] = filter_var($status, FILTER_VALIDATE_BOOLEAN);

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log("Updated consent status from client cookie:", [
                            'category' => $category,
                            'status' => $consent_data['consent_status'][$category]
                        ]);
                    }
                }
            }
        }
        // For logged-in users, also check user meta as a backup
        else if (is_user_logged_in()) {
            $user_consent = get_user_meta(get_current_user_id(), 'custom_cookie_consent_data', true);

            if ($user_consent && isset($user_consent['categories'])) {
                foreach ($user_consent['categories'] as $category => $status) {
                    if (isset($consent_data['consent_status'][$category])) {
                        $consent_data['consent_status'][$category] = filter_var($status, FILTER_VALIDATE_BOOLEAN);

                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            $this->debug_log("Updated consent status from user meta:", [
                                'category' => $category,
                                'status' => $consent_data['consent_status'][$category]
                            ]);
                        }
                    }
                }
            }
        }

        // Also check WP Consent API if enabled and available
        if (!empty($this->settings['wp_consent_api']) && WPConsentWrapper::is_consent_api_active()) {
            // Check consent status for each category from the WP Consent API
            $categories = ['necessary', 'analytics', 'functional', 'marketing'];

            foreach ($categories as $category) {
                $has_consent = WPConsentWrapper::has_consent($category);
                $consent_data['consent_status'][$category] = $has_consent;

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log("WP Consent API status for $category:", $has_consent);
                }
            }
        }

        // Get all cookie data and categorize them
        $all_cookies = $this->get_all_browser_cookies();
        $detected_cookies = get_option('custom_cookie_detected', []);

        // Add all cookies to cookies_present
        foreach ($all_cookies as $name => $value) {
            // Check if this cookie exists in detected cookies
            $category = 'necessary'; // Default category if not found
            $description = '';
            $expiry = '';
            $source = '';

            // Look for this cookie in the detected cookies
            foreach ($detected_cookies as $detected) {
                if ($detected['name'] === $name && $detected['status'] === 'categorized') {
                    $category = $detected['category'];
                    $description = $detected['description'] ?? '';
                    $expiry = isset($detected['expires']) ? $detected['expires'] : '';
                    $source = isset($detected['source']) ? $detected['source'] : '';
                    break;
                }
            }

            // Add to cookies_present array
            $consent_data['cookies_present'][] = [
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'expiry' => $expiry,
                'source' => $source
            ];

            // If this cookie is in a non-necessary category and that category is not consented to,
            // add it to the cookies_blocked array
            if ($category !== 'necessary' && !$consent_data['consent_status'][$category]) {
                $consent_data['cookies_blocked'][] = $name;
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log("Final consent data:", [
                'consent_status' => $consent_data['consent_status'],
                'cookies_count' => count($consent_data['cookies_present']),
                'blocked_count' => count($consent_data['cookies_blocked'])
            ]);
        }

        return $consent_data;
    }

    /**
     * Get all browser cookies from $_COOKIE
     *
     * @return array
     */
    private function get_all_browser_cookies(): array
    {
        $cookies = [];

        foreach ($_COOKIE as $name => $value) {
            // Skip some WordPress cookies if needed
            if (strpos($name, 'wordpress_test_cookie') !== false) {
                continue;
            }

            $cookies[$name] = $value;
        }

        return $cookies;
    }

    /**
     * Gets the stored consent data.
     *
     * @return array|null
     */
    public function get_stored_consent(): ?array
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log("Starting consent retrieval");
        }

        // First try: Direct $_COOKIE access
        if (isset($_COOKIE[$this->storageKey])) {
            $cookie_value = sanitize_text_field(wp_unslash($_COOKIE[$this->storageKey]));

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log("Found cookie in \$_COOKIE array", $cookie_value);
            }

            $result = $this->parse_consent_value($cookie_value);
            if ($result) {
                return $result;
            }
        }

        // Second try: Check in raw HTTP_COOKIE
        if (isset($_SERVER['HTTP_COOKIE'])) {
            // Properly sanitize and unslash the HTTP_COOKIE server variable
            $raw_cookies = sanitize_text_field(wp_unslash($_SERVER['HTTP_COOKIE']));
            $cookies_arr = explode(';', $raw_cookies);

            foreach ($cookies_arr as $cookie) {
                $parts = explode('=', $cookie, 2);
                $name = trim($parts[0]);

                if ($name === $this->storageKey && isset($parts[1])) {
                    $value = trim($parts[1]);

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log("Found cookie in HTTP_COOKIE", $value);
                    }

                    $result = $this->parse_consent_value($value);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        // Third try: Check headers for Set-Cookie
        $headers = headers_list();
        foreach ($headers as $header) {
            if (strpos($header, 'Set-Cookie: ' . $this->storageKey . '=') === 0) {
                $cookie_string = substr($header, strlen('Set-Cookie: '));
                $value_part = explode(';', $cookie_string)[0];
                $value = substr($value_part, strlen($this->storageKey . '='));

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log("Found cookie in headers", $value);
                }

                $result = $this->parse_consent_value($value);
                if ($result) {
                    return $result;
                }
            }
        }

        // Last resort: Check localStorage via JavaScript
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log("Cookie not found in any server sources, will check localStorage via JS");
        }

        // Add a fallback to empty consent with only necessary cookies
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log("Using fallback consent (necessary only)");
        }

        // Fallback to localStorage will be handled by the JS code
        return null;
    }

    /**
     * Parse and validate a consent value.
     *
     * @param string $value The raw cookie value to parse
     * @return array|null Parsed consent data or null if invalid
     */
    private function parse_consent_value(string $value): ?array
    {
        if (empty($value)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log("Empty consent value");
            }
            return null;
        }

        try {
            // Step 1: URL decode
            $decoded_value = urldecode($value);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log("URL decoded consent value", $decoded_value);
            }

            // Step 2: Try direct JSON decode
            $consent_data = json_decode($decoded_value, true);
            $json_error = json_last_error();

            // If successful, validate and return
            if ($json_error === JSON_ERROR_NONE && $this->validate_consent_data($consent_data)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log("Successfully parsed consent data", $consent_data);
                }
                return $consent_data;
            }

            // Step 3: Try with stripslashes if direct decode failed
            if ($json_error !== JSON_ERROR_NONE) {
                $cleaned_value = stripslashes($decoded_value);

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log("Attempting parse with stripslashes", $cleaned_value);
                }

                $consent_data = json_decode($cleaned_value, true);

                if (json_last_error() === JSON_ERROR_NONE && $this->validate_consent_data($consent_data)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log("Successfully parsed consent after stripslashes", $consent_data);
                    }
                    return $consent_data;
                }
            }

            // Step 4: Try handling potential double encoding
            $double_decoded = urldecode($decoded_value);
            if ($double_decoded !== $decoded_value) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log("Attempting parse with double URL decode", $double_decoded);
                }

                $consent_data = json_decode($double_decoded, true);

                if (json_last_error() === JSON_ERROR_NONE && $this->validate_consent_data($consent_data)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log("Successfully parsed consent after double decode", $consent_data);
                    }
                    return $consent_data;
                }

                // Try with stripslashes on double decoded value
                $cleaned_double = stripslashes($double_decoded);
                $consent_data = json_decode($cleaned_double, true);

                if (json_last_error() === JSON_ERROR_NONE && $this->validate_consent_data($consent_data)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log("Successfully parsed consent after double decode + stripslashes", $consent_data);
                    }
                    return $consent_data;
                }
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log("All parsing attempts failed", [
                    'original' => $value,
                    'decoded' => $decoded_value,
                    'last_error' => json_last_error_msg()
                ]);
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log("Exception while parsing consent", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        }

        return null;
    }

    /**
     * Validate consent data structure.
     *
     * @param mixed $data The data to validate
     * @return bool Whether the data is valid
     */
    private function validate_consent_data($data): bool
    {
        // Must be an array and have the categories key
        if (!is_array($data) || !isset($data['categories'])) {
            return false;
        }

        // Categories must be an array
        if (!is_array($data['categories'])) {
            return false;
        }

        // Convert string values to boolean if needed
        foreach ($data['categories'] as $category => $status) {
            if (is_string($status)) {
                $data['categories'][$category] = filter_var($status, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return true;
    }

    /**
     * Gets the expiry time for a cookie.
     *
     * @param string $cookie_name
     * @return string
     */
    private function get_cookie_expiry(string $cookie_name): string
    {
        static $expiry_times = array(
            '__hssc' => '30 minutes',
            '__hssrc' => 'Session',
            'hubspotutk' => '13 months',
            '__hstc' => '13 months',
            '_ga' => '2 years',
            '_gid' => '24 hours',
            '_ga_3LEBTMR1DL' => '2 years',
            '_gcl_au' => '3 months',
            '_lscache_vary' => 'Session',
            '__cf_bm' => '30 minutes',
            '_cfuvid' => 'Session',
            'devora_cookie_consent' => '1 year'
        );

        return isset($expiry_times[$cookie_name]) ? $expiry_times[$cookie_name] : 'Unknown';
    }

    /**
     * Checks if Google Analytics cookies are present.
     *
     * @return bool
     */
    private function has_ga_cookies(): bool
    {
        $has_cookies = false;
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, '_ga') === 0) {
                $has_cookies = true;
                $this->debug_log("Found GA cookie:", $name);
            }
        }
        return $has_cookies;
    }

    /**
     * Show consent data shortcode.
     * 
     * Usage: [show_my_consent_data]
     *
     * @return string
     */
    public function show_consent_data_shortcode(): string
    {
        $settings = get_option('custom_cookie_settings', []);

        // Create a placeholder that will be populated via AJAX
        $output = '<div class="cookie-consent-data-display" style="max-width: 800px; margin: 2rem auto; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';

        // Add loading indicator
        $output .= '<div id="cookie-consent-data-loading" style="text-align: center; padding: 2rem;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid rgba(76, 76, 255, 0.3); border-radius: 50%; border-top-color: #4C4CFF; animation: cookie-consent-spin 1s ease-in-out infinite;"></div>
            <p>' . esc_html__('Loading consent data...', 'custom-cookie-consent') . '</p>
        </div>';

        // Container for consent data
        $output .= '<div id="cookie-consent-data-container" style="display: none;"></div>';

        // Add refresh button
        $output .= '<div style="margin-top: 2rem; text-align: center;">
            <button type="button" id="cookie-consent-refresh" style="background-color: #4C4CFF; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
                ' . esc_html__('Refresh Consent Data', 'custom-cookie-consent') . '
            </button>
        </div>';

        // Add CSS for spinner
        $output .= '<style>
            @keyframes cookie-consent-spin {
                to { transform: rotate(360deg); }
            }
        </style>';

        // Add JavaScript to fetch and display consent data
        $output .= '<script>
            (function() {
                // Function to fetch consent data
                function fetchConsentData() {
                    const container = document.getElementById("cookie-consent-data-container");
                    const loading = document.getElementById("cookie-consent-data-loading");
                    
                    // Show loading
                    loading.style.display = "block";
                    container.style.display = "none";
                    
                    // Create form data
                    const formData = new FormData();
                    formData.append("action", "get_cookie_consent_data");
                    formData.append("nonce", document.querySelector(\'meta[name="cookie_consent_nonce"]\')?.content || "");
                    
                    // Fetch data
                    fetch("' . admin_url('admin-ajax.php') . '", {
                        method: "POST",
                        credentials: "same-origin",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(response => {
                        if (response.success && response.data) {
                            renderConsentData(response.data.data);
                            loading.style.display = "none";
                            container.style.display = "block";
                        } else {
                            container.innerHTML = "<p style=\"color: #666; text-align: center;\">' . esc_js(__('Error loading consent data.', 'custom-cookie-consent')) . '</p>";
                            loading.style.display = "none";
                            container.style.display = "block";
                        }
                    })
                    .catch(error => {
                        // Only log in debug mode
                        if (window.cookieConsentSettings?.debug) {
                            console.error("Error fetching consent data:", error);
                        }
                        container.innerHTML = "<p style=\"color: #666; text-align: center;\">' . esc_js(__('Error loading consent data.', 'custom-cookie-consent')) . '</p>";
                        loading.style.display = "none";
                        container.style.display = "block";
                    });
                }
                
                // Function to render consent data
                function renderConsentData(data) {
                    const container = document.getElementById("cookie-consent-data-container");
                    let html = "";
                    
                    // Add last updated time if available
                    if (data.timestamp) {
                        const date = new Date(data.timestamp);
                        html += `<p style="color: #666; font-size: 0.9em;">' . esc_js($settings['consent_last_updated'] ?? 'Sist oppdatert:') . ' ${date.toLocaleString()}</p>`;
                    }
                    
                    // Show consent status
                    html += `<h3 style="margin: 0 0 1rem; color: #333;">' . esc_js($settings['consent_choices_heading'] ?? 'Dine samtykkevalg') . '</h3>`;
                    html += `<div style="margin-bottom: 2rem;">`;
                    
                    // Display consent status for each category
                    if (data.consent_status) {
                        for (const [category, status] of Object.entries(data.consent_status)) {
                            // Get category title
                            let categoryDisplay = category;
                            if (category === "necessary") {
                                categoryDisplay = "' . esc_js($settings['necessary_title'] ?? 'Nødvendige') . '";
                            } else if (category === "analytics") {
                                categoryDisplay = "' . esc_js($settings['analytics_title'] ?? 'Analyse') . '";
                            } else if (category === "functional") {
                                categoryDisplay = "' . esc_js($settings['functional_title'] ?? 'Funksjonell') . '";
                            } else if (category === "marketing") {
                                categoryDisplay = "' . esc_js($settings['marketing_title'] ?? 'Markedsføring') . '";
                            }
                            
                            const statusColor = status ? "#4C4CFF" : "#666";
                            const statusText = status 
                                ? "' . esc_js($settings['consent_status_accepted'] ?? 'Godtatt') . '"
                                : "' . esc_js($settings['consent_status_declined'] ?? 'Avslått') . '";
                            
                            html += `
                                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="flex: 1; font-weight: 500;">${categoryDisplay}:</span>
                                    <span style="color: ${statusColor}; font-weight: 500;">${statusText}</span>
                                </div>
                            `;
                        }
                    }
                    html += `</div>`;
                    
                    // Show active cookies
                    if (data.cookies_present && data.cookies_present.length > 0) {
                        html += `<h4 style="margin: 1.5rem 0 1rem; color: #333;">' . esc_js($settings['active_cookies_heading'] ?? 'Aktive informasjonskapsler:') . '</h4>`;
                        
                        // Group cookies by category
                        const cookiesByCategory = {};
                        for (const cookie of data.cookies_present) {
                            if (!cookiesByCategory[cookie.category]) {
                                cookiesByCategory[cookie.category] = [];
                            }
                            cookiesByCategory[cookie.category].push(cookie);
                        }
                        
                        // Display cookies by category
                        for (const [category, cookies] of Object.entries(cookiesByCategory)) {
                            if (cookies.length === 0) continue;
                            
                            // Get category title
                            let categoryDisplay = category;
                            if (category === "necessary") {
                                categoryDisplay = "' . esc_js($settings['necessary_title'] ?? 'Nødvendige') . '";
                            } else if (category === "analytics") {
                                categoryDisplay = "' . esc_js($settings['analytics_title'] ?? 'Analyse') . '";
                            } else if (category === "functional") {
                                categoryDisplay = "' . esc_js($settings['functional_title'] ?? 'Funksjonell') . '";
                            } else if (category === "marketing") {
                                categoryDisplay = "' . esc_js($settings['marketing_title'] ?? 'Markedsføring') . '";
                            }
                            
                            html += `
                                <div style="margin-bottom: 1.5rem;">
                                    <h5 style="margin: 1rem 0 0.5rem; color: #555;">${categoryDisplay}</h5>
                                    <div style="border-left: 3px solid #4C4CFF; padding-left: 1rem;">
                            `;
                            
                            for (const cookie of cookies) {
                                html += `
                                    <div style="margin-bottom: 1rem; padding: 0.75rem; background: #fff; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                        <p style="margin: 0 0 0.5rem; font-weight: 500;">${cookie.name}</p>
                                        <p style="margin: 0 0 0.25rem; font-size: 0.9em;"><strong>' . esc_js($settings['cookie_purpose_label'] ?? 'Formål:') . '</strong> ${cookie.description || "Ikke spesifisert"}</p>
                                        <p style="margin: 0; font-size: 0.9em;"><strong>' . esc_js($settings['cookie_expiry_label'] ?? 'Utløper:') . '</strong> ${cookie.expiry || "Ikke spesifisert"}</p>
                                    </div>
                                `;
                            }
                            
                            html += `</div></div>`;
                        }
                    } else {
                        html += `<p style="margin: 1.5rem 0; color: #666;">' . esc_js($settings['no_cookies_message'] ?? 'Ingen informasjonskapsler funnet.') . '</p>`;
                    }
                    
                    container.innerHTML = html;
                }
                
                // Add event listener to refresh button
                document.getElementById("cookie-consent-refresh").addEventListener("click", fetchConsentData);
                
                // Add event listener for consent updated events
                window.addEventListener("consentUpdated", function() {
                    setTimeout(fetchConsentData, 500);
                });
                
                // Initial fetch
                document.addEventListener("DOMContentLoaded", fetchConsentData);
                
                // Fetch now if the page is already loaded
                if (document.readyState === "complete" || document.readyState === "interactive") {
                    fetchConsentData();
                }
            })();
        </script>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Outputs schema.org structured data for the cookie consent banner.
     *
     * @return void
     */
    public function output_consent_schema(): void
    {
        // Only output schema if the banner is active
        if (isset($_COOKIE[$this->storageKey])) {
            return;
        }

        // Check if current user agent is a bot
        $is_bot = $this->is_bot();

        // Get settings
        $settings = get_option('custom_cookie_settings', []);
        $banner_title = $settings['banner_title'] ?? __('Cookie Consent', 'custom-cookie-consent');
        $banner_text = $settings['banner_text'] ?? __('We use cookies to improve your experience on our website.', 'custom-cookie-consent');

        // Get categories
        $categories = CookieCategories::get_categories();
        $category_names = array_map(function ($cat) {
            return $cat['title'] ?? '';
        }, $categories);

        // Build schema.org structured data
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'potentialAction' => [
                '@type' => 'CommunicateAction',
                'about' => [
                    '@type' => 'Thing',
                    'name' => $banner_title,
                    'description' => $banner_text
                ],
                'instrument' => [
                    '@type' => 'WebApplication',
                    'name' => 'Cookie Consent by Devora',
                    'applicationCategory' => 'Privacy Tool',
                    'offers' => [
                        '@type' => 'Offer',
                        'category' => implode(', ', $category_names)
                    ]
                ]
            ],
            'accessModeSufficient' => [
                'visual',
                'textual',
                'auditory'
            ],
            'accessibilityControl' => [
                'fullKeyboardControl',
                'fullMouseControl',
                'fullTouchControl'
            ],
            'accessibilityFeature' => [
                'highContrast',
                'largePrint',
                'structuralNavigation',
                'alternativeText'
            ],
            'accessibilityHazard' => [
                'noFlashingHazard',
                'noMotionSimulationHazard',
                'noSoundHazard'
            ]
        ];

        // Add bot-specific information
        if ($is_bot) {
            $schema['potentialAction']['result'] = [
                '@type' => 'SearchAction',
                'description' => 'Automatic consent granted for search engine crawlers',
                'query' => 'Full content access enabled for bots'
            ];

            $schema['accessMode'] = 'automated';
        }

        // Output the schema
        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
    }

    /**
     * Exclude cookie consent banner from sitemaps.
     * This prevents search engines from indexing the banner as a separate page.
     *
     * @since 1.1.3
     * @param array $excluded_post_types Array of post types to exclude.
     * @return array Modified array of post types.
     */
    public function exclude_from_sitemap($excluded_post_types)
    {
        // No actual post types to exclude, but we can use this to add custom sitemap entries
        return $excluded_post_types;
    }

    /**
     * Modify robots.txt to prevent indexing of cookie-related assets.
     * This helps search engines focus on your content rather than cookie scripts.
     *
     * @since 1.1.3
     * @param string $output Current robots.txt content.
     * @return string Modified robots.txt content.
     */
    public function modify_robots_txt($output)
    {
        $plugin_url = parse_url(plugins_url('', __FILE__), PHP_URL_PATH);

        $output .= "\n# Custom Cookie Consent Plugin\n";
        $output .= "Disallow: {$plugin_url}/assets/js/\n";
        $output .= "Disallow: {$plugin_url}/assets/css/\n";

        return $output;
    }

    /**
     * Registers privacy exporters.
     *
     * @since 1.1.4
     * @param array $exporters Array of registered exporters.
     * @return array Updated array of registered exporters.
     */
    public function register_privacy_exporters($exporters)
    {
        $exporters['cookie-consent-by-devora'] = [
            'exporter_friendly_name' => __('Cookie Consent Data', 'custom-cookie-consent'),
            'callback'               => [$this, 'export_cookie_consent_data'],
        ];

        return $exporters;
    }

    /**
     * Exports cookie consent data for a user.
     *
     * @since 1.1.4
     * @param string $email_address The user's email address.
     * @param int    $page          Page number.
     * @return array Export data.
     */
    public function export_cookie_consent_data($email_address, $page = 1)
    {
        $user = get_user_by('email', $email_address);
        $export_items = [];

        if (!$user) {
            return [
                'data' => [],
                'done' => true,
            ];
        }

        // Get consent data from user meta
        $consent_data = get_user_meta($user->ID, 'custom_cookie_consent_data', true);

        if (!empty($consent_data)) {
            $data = [];

            // Add consent status for each category
            if (isset($consent_data['categories'])) {
                foreach ($consent_data['categories'] as $category => $status) {
                    $data[] = [
                        'name'  => sprintf(__('%s Cookies', 'custom-cookie-consent'), ucfirst($category)),
                        'value' => $status ? __('Accepted', 'custom-cookie-consent') : __('Declined', 'custom-cookie-consent'),
                    ];
                }
            }

            // Add consent timestamp
            if (isset($consent_data['timestamp'])) {
                $data[] = [
                    'name'  => __('Consent Date', 'custom-cookie-consent'),
                    'value' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($consent_data['timestamp'])),
                ];
            }

            // Add consent version
            if (isset($consent_data['version'])) {
                $data[] = [
                    'name'  => __('Consent Version', 'custom-cookie-consent'),
                    'value' => $consent_data['version'],
                ];
            }

            $export_items[] = [
                'group_id'    => 'cookie-consent',
                'group_label' => __('Cookie Consent', 'custom-cookie-consent'),
                'item_id'     => 'cookie-consent-' . $user->ID,
                'data'        => $data,
            ];
        }

        return [
            'data' => $export_items,
            'done' => true,
        ];
    }

    /**
     * Registers privacy erasers.
     *
     * @since 1.1.4
     * @param array $erasers Array of registered erasers.
     * @return array Updated array of registered erasers.
     */
    public function register_privacy_erasers($erasers)
    {
        $erasers['cookie-consent-by-devora'] = [
            'eraser_friendly_name' => __('Cookie Consent Data', 'custom-cookie-consent'),
            'callback'             => [$this, 'erase_cookie_consent_data'],
        ];

        return $erasers;
    }

    /**
     * Erases cookie consent data for a user.
     *
     * @since 1.1.4
     * @param string $email_address The user's email address.
     * @param int    $page          Page number.
     * @return array Erasure data.
     */
    public function erase_cookie_consent_data($email_address, $page = 1)
    {
        $user = get_user_by('email', $email_address);
        $items_removed = false;
        $items_retained = false;
        $messages = [];

        if ($user) {
            // Check if user has consent data
            $consent_data = get_user_meta($user->ID, 'custom_cookie_consent_data', true);

            if (!empty($consent_data)) {
                // Delete the consent data
                $deleted = delete_user_meta($user->ID, 'custom_cookie_consent_data');

                if ($deleted) {
                    $items_removed = true;
                    $messages[] = __('Cookie consent data has been removed.', 'custom-cookie-consent');
                } else {
                    $items_retained = true;
                    $messages[] = __('Cookie consent data could not be removed.', 'custom-cookie-consent');
                }
            } else {
                $messages[] = __('No cookie consent data found for this user.', 'custom-cookie-consent');
            }
        } else {
            $messages[] = __('No user found with this email address.', 'custom-cookie-consent');
        }

        return [
            'items_removed'  => $items_removed,
            'items_retained' => $items_retained,
            'messages'       => $messages,
            'done'           => true,
        ];
    }

    /**
     * Adds privacy policy content.
     *
     * @since 1.1.4
     * @return void
     */
    public function add_privacy_policy_content()
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = '<h3>' . __('Cookie Consent by Devora', 'custom-cookie-consent') . '</h3>';

        $content .= '<p>' . __('This website uses Cookie Consent by Devora to manage cookie consent and comply with privacy regulations. The plugin stores the following data when users interact with the cookie consent banner:', 'custom-cookie-consent') . '</p>';

        $content .= '<ul>';
        $content .= '<li>' . __('<strong>Consent Preferences</strong>: We store your cookie consent preferences (necessary, analytics, functional, marketing) in a cookie named "devora_cookie_consent". This cookie contains information about which cookie categories you have accepted or declined.', 'custom-cookie-consent') . '</li>';
        $content .= '<li>' . __('<strong>Consent Timestamp</strong>: We record when you provided your consent to help us determine when to ask for renewed consent.', 'custom-cookie-consent') . '</li>';
        $content .= '<li>' . __('<strong>Consent Version</strong>: We store the version of the consent you provided to track if our cookie policy has been updated since your last consent.', 'custom-cookie-consent') . '</li>';
        $content .= '</ul>';

        $content .= '<p>' . __('This data is stored in your browser using cookies and/or local storage and is not sent to any third-party servers. The data is used solely to remember your cookie preferences and to ensure compliance with privacy regulations.', 'custom-cookie-consent') . '</p>';

        $content .= '<p>' . __('You can change your cookie preferences at any time by clicking on the "Cookie Settings" link in the footer of our website. You can also delete the stored consent data by clearing your browser cookies and local storage.', 'custom-cookie-consent') . '</p>';

        wp_add_privacy_policy_content('Cookie Consent by Devora', wp_kses_post($content));
    }

    /**
     * Store user consent data in user meta.
     * This is called when a user provides consent and is logged in.
     *
     * @since 1.1.4
     * @param array $consent_data The consent data.
     * @return void
     */
    public function store_user_consent_data($consent_data)
    {
        // Only store for logged-in users
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();

        // Store the consent data in user meta
        update_user_meta($user_id, 'custom_cookie_consent_data', $consent_data);
    }

    /**
     * Handles AJAX request to save consent data.
     *
     * @return void
     */
    public function ajax_save_consent(): void
    {
        // Verify nonce if available
        $nonce_verified = false;

        if (isset($_POST['nonce'])) {
            $nonce = sanitize_text_field($_POST['nonce']);
            $nonce_verified = wp_verify_nonce($nonce, 'cookie_management');
        }

        // Allow saving consent even without a nonce for frontend users
        if (!$nonce_verified && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cookie Consent: Nonce verification failed for consent save, but proceeding anyway for frontend users');
        }

        // Get the consent data
        $consent_data = null;
        if (isset($_POST['consent_data'])) {
            $consent_data = json_decode(wp_unslash($_POST['consent_data']), true);
        }

        if (!$consent_data || !isset($consent_data['categories'])) {
            wp_send_json_error(['message' => __('Invalid consent data', 'custom-cookie-consent')]);
            return;
        }

        // Debug log the received consent data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('Received consent data:', $consent_data);
        }

        // Save consent for logged-in users
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'custom_cookie_consent_data', $consent_data);

            // Debug logged-in user consent storage
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log('Saved consent data for user ID: ' . get_current_user_id());
            }
        }

        // Log the consent data to the database
        $consent_logger = new ConsentLogger();
        $consent_logger->log_consent($consent_data, isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'banner');

        // Update the WP Consent API if available
        if (!empty($this->settings['wp_consent_api'])) {
            // Register the consent for each category
            foreach ($consent_data['categories'] as $category => $status) {
                WPConsentWrapper::set_consent($category, (bool)$status);
            }

            // Debug WP Consent API integration
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log('Updated WP Consent API with consent data');
            }
        }

        // Save the consent status to a site option for reference
        // This helps track overall consent rates
        $consent_stats = get_option('custom_cookie_consent_stats', [
            'total' => 0,
            'analytics_accepted' => 0,
            'functional_accepted' => 0,
            'last_updated' => time()
        ]);

        $consent_stats['total']++;
        if (!empty($consent_data['categories']['analytics'])) {
            $consent_stats['analytics_accepted']++;
        }
        if (!empty($consent_data['categories']['functional'])) {
            $consent_stats['functional_accepted']++;
        }
        $consent_stats['last_updated'] = time();

        update_option('custom_cookie_consent_stats', $consent_stats);

        // Success response
        wp_send_json_success([
            'message' => __('Consent preferences saved', 'custom-cookie-consent'),
            'data' => $consent_data
        ]);
    }

    /**
     * Outputs the consent nonce in the HTML head.
     *
     * @return void
     */
    public function output_consent_nonce(): void
    {
        // Only output the nonce on frontend pages
        if (is_admin()) {
            return;
        }

        // Create a nonce for cookie consent operations
        $nonce = wp_create_nonce('cookie_management');

        // Output meta tag with the nonce
        echo '<meta name="cookie_consent_nonce" content="' . esc_attr($nonce) . '" />' . "\n";
    }

    /**
     * Handles AJAX request to fetch consent data.
     *
     * @return void
     */
    public function ajax_get_consent_data(): void
    {
        // Verify nonce if available
        $nonce_verified = false;

        if (isset($_POST['nonce'])) {
            $nonce = sanitize_text_field($_POST['nonce']);
            $nonce_verified = wp_verify_nonce($nonce, 'cookie_management');
        }

        // Allow fetching consent data even without a nonce for frontend users
        if (!$nonce_verified && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cookie Consent: Nonce verification failed for consent data fetch, but proceeding anyway for frontend users');
        }

        // Get consent data
        $consent_data = $this->get_user_consent_data();

        // Debug log the fetched consent data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('Fetched consent data:', $consent_data);
        }

        // Success response
        wp_send_json_success([
            'data' => $consent_data
        ]);
    }

    /**
     * Plugin activation handler
     */
    public function activate(): void
    {
        // Check if we need to update the database schema
        $this->maybe_update_db_schema();

        // Trigger action for other components to hook into
        do_action('custom_cookie_consent_activate');
    }

    /**
     * Check and update database schema if needed
     */
    private function maybe_update_db_schema(): void
    {
        $db_version = get_option('custom_cookie_db_version', '0');

        // If database version is current, no need to update
        if (version_compare($db_version, CUSTOM_COOKIE_DB_VERSION, '>=')) {
            return;
        }

        // Trigger database table creation
        do_action('custom_cookie_consent_activate');

        // Update the database version option
        update_option('custom_cookie_db_version', CUSTOM_COOKIE_DB_VERSION);
    }
}

// Initialize the plugin
CookieConsent::get_instance();
