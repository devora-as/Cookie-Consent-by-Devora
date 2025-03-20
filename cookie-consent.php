<?php

/**
 * Plugin Name: Cookie Consent by Devora
 * Plugin URI: https://devora.no/plugins/cookie-consent
 * Description: A lightweight, customizable cookie consent solution with Google Consent Mode v2 integration.
 * Version: 1.2.1
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

use \Exception;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Define plugin version
define('CUSTOM_COOKIE_VERSION', '1.2.1');

// Define plugin database version
define('CUSTOM_COOKIE_DB_VERSION', '1.0');

// First, register the text domain loading at the correct hook BEFORE any class instantiation
add_action('plugins_loaded', function () {
    load_plugin_textdomain('custom-cookie-consent', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Require dependencies
require_once plugin_dir_path(__FILE__) . 'includes/class-cookie-categories.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cookie-scanner.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-integrations.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-banner-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wp-consent-wrapper.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-github-updater.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-consent-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-open-cookie-database.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-matomo-integration.php';

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
     * Plugin version
     *
     * @var string
     */
    private string $version = '1.0.0';

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
     * @var OpenCookieDatabase
     */
    private $open_cookie_db;

    /**
     * @var MatomoIntegration
     */
    private $matomo_integration;

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
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor method. Sets up the plugin.
     */
    private function __construct()
    {
        // Load dependencies
        require_once plugin_dir_path(__FILE__) . 'includes/class-cookie-scanner.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-integrations.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-banner-generator.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-open-cookie-database.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-wp-consent-wrapper.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-matomo-integration.php';

        // Initialize components
        $this->cookie_scanner = new CookieScanner();
        $this->admin_interface = AdminInterface::get_instance();
        $this->integrations = new Integrations();
        $this->banner_generator = new BannerGenerator();
        $this->open_cookie_db = new OpenCookieDatabase();
        $this->matomo_integration = new MatomoIntegration();

        // Load settings
        $this->settings = get_option('custom_cookie_settings', []);

        // Initialize the plugin
        $this->init();
    }

    /**
     * Initialize plugin
     */
    public function init(): void
    {
        // Initialize translations
        load_plugin_textdomain('custom-cookie-consent', false, basename(dirname(__FILE__)) . '/languages/');

        // Add shortcode
        add_shortcode('cookie_settings', [$this, 'cookie_settings_shortcode']);
        add_shortcode('show_my_consent_data', [$this, 'show_consent_data_shortcode']);

        // Register AJAX endpoints
        add_action('wp_ajax_custom_cookie_scan', [$this, 'ajax_scan_cookies']);
        add_action('wp_ajax_nopriv_custom_cookie_scan', [$this, 'ajax_scan_cookies']);
        add_action('wp_ajax_custom_cookie_save_consent', [$this, 'ajax_save_consent']);
        add_action('wp_ajax_nopriv_custom_cookie_save_consent', [$this, 'ajax_save_consent']);
        add_action('wp_ajax_custom_cookie_get_consent', [$this, 'ajax_get_consent_data']);
        add_action('wp_ajax_nopriv_custom_cookie_get_consent', [$this, 'ajax_get_consent_data']);

        // Admin-only AJAX endpoints
        add_action('wp_ajax_custom_cookie_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_custom_cookie_save_integration_settings', [$this, 'ajax_save_integration_settings']);

        // Front-end scripts and styles
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

            // Consent nonce for AJAX requests
            add_action('wp_head', [$this, 'output_consent_nonce']);

            // Output consent schema (JSON-LD)
            add_action('wp_head', [$this, 'output_consent_schema']);

            // Google consent mode
            add_action('wp_head', [$this, 'output_consent_mode'], 1);

            // Add GTM noscript tag
            add_action('wp_body_open', [$this, 'output_gtm_noscript'], 1);

            // Early anti-blocker script
            add_action('wp_head', [$this, 'output_early_anti_blocker'], 0);

            // Add additional CSS for banner position
            add_action('wp_footer', [$this, 'add_banner_position_css'], 100);
        }

        // Handle admin hooks separately
        if (is_admin()) {
            $this->handle_admin_actions();
        }

        // Privacy policy hooks
        add_action('admin_init', [$this, 'add_privacy_policy_content']);

        // Register GDPR exporters and erasers
        add_filter('wp_privacy_personal_data_exporters', [$this, 'register_privacy_exporters']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'register_privacy_erasers']);

        // Robots.txt
        add_filter('robots_txt', [$this, 'modify_robots_txt']);

        // Exclude cookie policy from sitemap
        add_filter('wp_sitemaps_post_types', [$this, 'exclude_from_sitemap']);

        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_routes']);

        // Register cookies
        $this->register_cookies();

        // Maybe update database schema
        $this->maybe_update_db_schema();
    }

    /**
     * Handle admin actions related to cookie consent
     *
     * @return void
     */
    public function handle_admin_actions(): void
    {
        // Handle AJAX calls for settings
        \add_action('wp_ajax_custom_cookie_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Handles AJAX request to save settings.
     */
    public function ajax_save_settings(): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('ajax_save_settings() - Received settings save request', $_POST);
        }

        if (! isset($_POST['nonce']) || ! \wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'cookie_management')) {
            \wp_send_json_error(array('message' => __('Invalid security token. Please refresh the page and try again.', 'custom-cookie-consent')));
            return;
        }

        if (! \current_user_can('manage_options')) {
            \wp_send_json_error(array('message' => __('Permission denied. You do not have sufficient permissions to modify these settings.', 'custom-cookie-consent')));
            return;
        }

        $settings = array();

        // Sanitize and save settings - comprehensive list of all possible text fields
        $text_fields = array(
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
            'scan_frequency',
        );

        // Get existing settings first to avoid unnecessary updates
        $existing_settings = \get_option('custom_cookie_settings', array());
        $settings          = $existing_settings;
        $changed           = false;

        // Process text fields
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                $new_value = sanitize_text_field(wp_unslash($_POST[$field]));
                // Only update if the value has changed
                if (! isset($existing_settings[$field]) || $existing_settings[$field] !== $new_value) {
                    $settings[$field] = $new_value;
                    $changed            = true;
                }
            }
        }

        // Checkbox fields - explicitly handle all checkboxes
        $checkbox_fields = array(
            'auto_scan',
            'defer_css',
            'wp_consent_api',
            'sitekit_integration',
            'hubspot_integration',
            'enable_anti_blocker',
        );

        foreach ($checkbox_fields as $field) {
            $new_value = isset($_POST[$field]) && $_POST[$field] == '1';
            // Only update if the value has changed
            if (! isset($existing_settings[$field]) || (bool) $existing_settings[$field] !== $new_value) {
                $settings[$field] = $new_value;
                $changed            = true;
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
                $this->debug_log(
                    'ajax_save_settings() - Update result:',
                    array(
                        'updated'  => $updated,
                        'settings' => $settings,
                    )
                );
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

                $this->debug_log(
                    'ajax_save_settings() - Banner template regenerated',
                    array(
                        'timestamp' => time(),
                    )
                );
            }

            // Trigger banner generation and reset cron schedule if needed
            \do_action('custom_cookie_rules_updated');

            if (isset($_POST['scan_frequency'])) {
                \do_action('custom_cookie_scan_schedule_updated');
            }

            if ($updated) {
                \wp_send_json_success(array('message' => __('Settings saved successfully', 'custom-cookie-consent')));
            } else {
                \wp_send_json_error(array('message' => __('Error saving settings. Please try again.', 'custom-cookie-consent')));
            }
        } else {
            // No changes were made
            \wp_send_json_success(array('message' => __('No changes were made to settings', 'custom-cookie-consent')));
        }
    }

    /**
     * Handles AJAX request to save integration settings.
     * This is a dedicated endpoint for integration settings to ensure they're properly saved.
     */
    public function ajax_save_integration_settings(): void
    {
        // Check nonce
        if (! isset($_POST['nonce']) || ! \wp_verify_nonce($_POST['nonce'], 'custom_cookie_nonce')) {
            $this->debug_log('ajax_save_integration_settings() - Nonce verification failed', $_POST);
            \wp_send_json_error(array('message' => __('Security check failed.', 'custom-cookie-consent')));
        }

        // Check permissions
        if (! \current_user_can('manage_options')) {
            $this->debug_log('ajax_save_integration_settings() - Permission check failed', array('user_id' => \get_current_user_id()));
            \wp_send_json_error(array('message' => __('Permission denied. You do not have sufficient permissions to modify these settings.', 'custom-cookie-consent')));
        }

        // Get existing settings
        $existing_settings = \get_option('custom_cookie_settings', array());

        // Integration options: sitekit_integration, wp_consent_api, hubspot_integration
        $integration_fields = array(
            'wp_consent_api',
            'sitekit_integration',
            'hubspot_integration',
            'direct_tracking_enabled',
        );

        // Process each integration field
        foreach ($integration_fields as $field) {
            if (isset($_POST[$field])) {
                $existing_settings[$field] = ($_POST[$field] === '1');
            } else {
                $existing_settings[$field] = false;
            }
        }

        // Handle tracking IDs (sanitize to prevent XSS)
        if (isset($_POST['ga_tracking_id'])) {
            $existing_settings['ga_tracking_id'] = sanitize_text_field($_POST['ga_tracking_id']);
        }

        if (isset($_POST['gtm_id'])) {
            $existing_settings['gtm_id'] = sanitize_text_field($_POST['gtm_id']);
        }

        // Handle consent region setting
        if (isset($_POST['consent_region']) && in_array($_POST['consent_region'], array('NO', 'EEA', 'GLOBAL'))) {
            $existing_settings['consent_region'] = sanitize_text_field($_POST['consent_region']);
        } else {
            $existing_settings['consent_region'] = 'NO'; // Default to Norway if not set or invalid
        }

        // Debug log the integration settings being saved
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log(
                'ajax_save_integration_settings() - Integration settings being saved:',
                array(
                    'wp_consent_api'         => $existing_settings['wp_consent_api'],
                    'sitekit_integration'    => $existing_settings['sitekit_integration'],
                    'hubspot_integration'    => $existing_settings['hubspot_integration'],
                    'direct_tracking_enabled' => $existing_settings['direct_tracking_enabled'] ?? false,
                    'ga_tracking_id'         => $existing_settings['ga_tracking_id'] ?? '',
                    'gtm_id'                 => $existing_settings['gtm_id'] ?? '',
                    'consent_region'         => $existing_settings['consent_region'],
                )
            );
        }

        // Save the updated settings
        $updated = \update_option('custom_cookie_settings', $existing_settings);

        // Debug log the update result
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log(
                'ajax_save_integration_settings() - Update result:',
                array(
                    'updated'                => $updated,
                    'wp_consent_api'         => $existing_settings['wp_consent_api'],
                    'sitekit_integration'    => $existing_settings['sitekit_integration'],
                    'hubspot_integration'    => $existing_settings['hubspot_integration'],
                    'direct_tracking_enabled' => $existing_settings['direct_tracking_enabled'] ?? false,
                    'ga_tracking_id'         => $existing_settings['ga_tracking_id'] ?? '',
                    'gtm_id'                 => $existing_settings['gtm_id'] ?? '',
                    'consent_region'         => $existing_settings['consent_region'],
                )
            );
        }

        if ($updated) {
            \wp_send_json_success(array('message' => __('Integration settings saved successfully', 'custom-cookie-consent')));
        } else {
            \wp_send_json_error(array('message' => __('No changes made or error saving integration settings', 'custom-cookie-consent')));
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
        if (! isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $user_agent = strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])));

        $bot_patterns = array(
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
            'lighthouse',
        );

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
        $settings = get_option('custom_cookie_settings', array());

        // Get the latest template timestamp for cache busting
        $template_timestamp = get_option('custom_cookie_banner_last_updated', time());

        // Prepare version string for cache busting
        $version = defined('WP_DEBUG') && WP_DEBUG ? time() : $template_timestamp;

        // Main CSS - only enqueue if not set to defer
        if (empty($settings['defer_css'])) {
            wp_enqueue_style(
                'custom-cookie-css',
                $this->get_asset_url('css/cookie-consent.css'),
                array(),
                $version
            );
        }

        // Banner template script
        wp_enqueue_script(
            'custom-cookie-template',
            $this->get_asset_url('js/banner-template.js'),
            array(),
            $version,
            true
        );

        // Get the server-generated template
        $banner_template = get_option('custom_cookie_banner_template', '');

        // If we have a server-generated template, add it as an inline script before banner-template.js loads
        if (! empty($banner_template)) {
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
            array(),
            $version,
            true
        );

        // Cookie enforcer script
        wp_enqueue_script(
            'custom-cookie-enforcer',
            $this->get_asset_url('js/cookie-enforcer.js'),
            array('custom-cookie-rules'),
            $version,
            true
        );

        // Main consent script - load after template
        wp_enqueue_script(
            'custom-cookie-js',
            $this->get_asset_url('js/consent-manager.js'),
            array('custom-cookie-template'),
            $version,
            true
        );

        // Anti-ad-blocker script (only if enabled)
        if (!empty($settings['enable_anti_blocker'])) {
            wp_enqueue_script(
                'custom-cookie-anti-blocker',
                $this->get_asset_url('js/anti-ad-blocker.js'),
                array('custom-cookie-js'),
                $version,
                true
            );
        }

        // Add settings to the page
        wp_localize_script(
            'custom-cookie-js',
            'cookieConsentSettings',
            array(
                'ajaxUrl'             => admin_url('admin-ajax.php'),
                'position'            => $settings['position'] ?? 'bottom',
                'privacyUrl'          => $settings['privacy_url'] ?? '',
                'cookiePolicyUrl'     => $settings['cookie_policy_url'] ?? '',
                'consentVersion'      => '1',
                'gtmId'               => $settings['gtm_id'] ?? '',
                'gaTrackingId'        => $settings['ga_tracking_id'] ?? '',
                'directTrackingEnabled' => isset($settings['direct_tracking_enabled']) ? (bool)$settings['direct_tracking_enabled'] : false,
                'enableAntiBlocker'   => isset($settings['enable_anti_blocker']) ? (bool)$settings['enable_anti_blocker'] : false,
                'debug'               => defined('WP_DEBUG') && WP_DEBUG ? true : false,
                'isBot'               => $this->is_bot(),
                'templateTimestamp'   => $template_timestamp,
                'consent_region'      => $settings['consent_region'] ?? 'NO',
            )
        );

        // If CSS is deferred, output the inline preload
        if (! empty($settings['defer_css'])) {
            add_action('wp_head', array($this, 'load_full_css'));
        }
    }

    /**
     * Load the full CSS for the cookie consent banner.
     * This is called from wp_footer when CSS loading is deferred.
     */
    public function load_full_css(): void
    {
        // Get settings
        $settings = get_option('custom_cookie_settings', array());
        $anti_blocker_enabled = !empty($settings['enable_anti_blocker']);

        // Get the latest template timestamp for cache busting
        $template_timestamp = get_option('custom_cookie_banner_last_updated', time());
        $version = defined('WP_DEBUG') && WP_DEBUG ? time() : $template_timestamp;

        // When anti-blocker is enabled, use unique CSS with each load to bypass caching/blocking
        if ($anti_blocker_enabled) {
            $version .= '.' . substr(uniqid(), -6);
        }

        // Use the correct CSS file
        $css_url = $this->get_asset_url('css/cookie-consent.css');

        // Add version parameter for cache busting
        $css_url = add_query_arg('ver', $version, $css_url);

        // If anti-blocker enabled, load CSS via a less detectable method
        if ($anti_blocker_enabled) {
            // Use a random ID that won't be recognized by ad blockers
            $random_id = 'ui-style-' . substr(uniqid(), -8);

            // Use data attributes instead of rel="stylesheet" which might be blocked
            echo '<link id="' . esc_attr($random_id) . '" data-dd-style="true" href="' . esc_url($css_url) . '" media="all">';

            // Add a script to ensure the CSS is applied properly
            echo '<script>
                (function() {
                    var style = document.getElementById("' . esc_js($random_id) . '");
                    if (style) {
                        style.rel = "stylesheet";
                        style.type = "text/css";
                    }
                })();
            </script>';
        } else {
            // Standard CSS loading
            echo '<link rel="stylesheet" href="' . esc_url($css_url) . '" media="all">';
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cookie Consent: Loading full CSS with version ' . $version . ' and anti-blocker ' . ($anti_blocker_enabled ? 'enabled' : 'disabled'));
        }
    }

    /**
     * Outputs the Google Consent Mode v2 integration.
     *
     * @return void
     */
    public function output_consent_mode(): void
    {
        // Get settings
        $settings = get_option('custom_cookie_settings', array());
        $direct_tracking_enabled = isset($settings['direct_tracking_enabled']) ? (bool)$settings['direct_tracking_enabled'] : false;
        $ga_tracking_id = isset($settings['ga_tracking_id']) ? sanitize_text_field($settings['ga_tracking_id']) : '';
        $gtm_id = isset($settings['gtm_id']) ? sanitize_text_field($settings['gtm_id']) : '';
        $consent_region = isset($settings['consent_region']) ? sanitize_text_field($settings['consent_region']) : 'NO';
?>
        <script>
            // Initialize dataLayer immediately
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            // Set default consent state - moved to head for immediate effect
            const defaultConsent = {
                'ad_storage': 'denied',
                'analytics_storage': 'denied',
                'functionality_storage': 'denied',
                'personalization_storage': 'denied',
                'security_storage': 'granted',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'wait_for_update': 2000
            };

            <?php if ($consent_region === 'NO'): ?>
                // Only apply to Norway
                defaultConsent.region = ['NO'];
            <?php elseif ($consent_region === 'EEA'): ?>
                // Apply to all EEA countries
                defaultConsent.region = [
                    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE',
                    'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT',
                    'RO', 'SK', 'SI', 'ES', 'SE', // EU countries
                    'NO', 'IS', 'LI', 'GB' // Norway, Iceland, Liechtenstein, UK
                ];
            <?php endif; ?>
            // Note: For 'GLOBAL', we don't set any region which applies restrictions globally

            // Set default consent immediately in <head>
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
        </script>

        <?php if ($direct_tracking_enabled): ?>
            <?php if (!empty($gtm_id)): ?>
                <!-- Google Tag Manager -->
                <script>
                    (function(w, d, s, l, i) {
                        w[l] = w[l] || [];
                        w[l].push({
                            'gtm.start': new Date().getTime(),
                            event: 'gtm.js'
                        });
                        var f = d.getElementsByTagName(s)[0],
                            j = d.createElement(s),
                            dl = l != 'dataLayer' ? '&l=' + l : '';
                        j.async = true;
                        j.src =
                            'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                        f.parentNode.insertBefore(j, f);
                    })(window, document, 'script', 'dataLayer', '<?php echo esc_js($gtm_id); ?>');
                </script>
                <!-- End Google Tag Manager -->
            <?php endif; ?>

            <?php if (!empty($ga_tracking_id)): ?>
                <!-- Google tag (gtag.js) - GA4 -->
                <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js($ga_tracking_id); ?>"></script>
                <script>
                    window.dataLayer = window.dataLayer || [];

                    function gtag() {
                        dataLayer.push(arguments);
                    }
                    gtag('js', new Date());
                    // Note: config command respects consent mode settings automatically
                    gtag('config', '<?php echo esc_js($ga_tracking_id); ?>');
                </script>
                <!-- End Google tag -->
            <?php endif; ?>
        <?php endif; ?>
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
        // Get the consent status for each category
        $analytics_consent  = WPConsentWrapper::has_consent('analytics');
        $functional_consent = WPConsentWrapper::has_consent('functional');
        $marketing_consent  = WPConsentWrapper::has_consent('marketing');

        // Map consent settings for Site Kit with proper separation between categories
        $consent_settings = array(
            // Analytics storage should only be granted if analytics consent is given
            'analytics_storage'       => $analytics_consent ? 'granted' : 'denied',

            // Functional cookies control functionality and personalization
            'functionality_storage'   => $functional_consent ? 'granted' : 'denied',
            'personalization_storage' => $functional_consent ? 'granted' : 'denied',

            // Security storage is always granted (necessary cookies)
            'security_storage'        => 'granted',

            // Marketing consent ONLY affects ad storage, user data, and personalization
            // These should never be granted if only analytics consent is given
            'ad_storage'              => $marketing_consent ? 'granted' : 'denied',
            'ad_user_data'            => $marketing_consent ? 'granted' : 'denied',
            'ad_personalization'      => $marketing_consent ? 'granted' : 'denied',
        );

        // Get the consent region setting
        $settings       = get_option('custom_cookie_settings', array());
        $consent_region = isset($settings['consent_region']) ? $settings['consent_region'] : 'NO';

        // Add the appropriate region based on the setting
        if ($consent_region === 'NO') {
            $consent_settings['region'] = array('NO');
        } elseif ($consent_region === 'EEA') {
            // EEA countries list - all EU countries plus Norway, Iceland, and Liechtenstein
            $consent_settings['region'] = array(
                'AT',
                'BE',
                'BG',
                'HR',
                'CY',
                'CZ',
                'DK',
                'EE',
                'FI',
                'FR',
                'DE',
                'GR',
                'HU',
                'IE',
                'IT',
                'LV',
                'LT',
                'LU',
                'MT',
                'NL',
                'PL',
                'PT',
                'RO',
                'SK',
                'SI',
                'ES',
                'SE', // EU countries
                'NO',
                'IS',
                'LI',
                'GB', // Norway, Iceland, Liechtenstein, UK
            );
        }
        // For 'GLOBAL', we don't set any region which applies restrictions globally

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('Site Kit Consent Settings:', $consent_settings);
            $this->debug_log(
                'Based on user consent status:',
                array(
                    'analytics'  => $analytics_consent,
                    'functional' => $functional_consent,
                    'marketing'  => $marketing_consent,
                )
            );
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
        $settings = get_option('custom_cookie_settings', array());

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
    public function cookie_settings_shortcode(array $atts = array()): string
    {
        $atts = shortcode_atts(
            array(
                'class' => '',
                'text'  => '',
            ),
            $atts
        );

        return self::get_cookie_settings_link($atts['class'], $atts['text']);
    }

    /**
     * Log debug messages if WP_DEBUG is enabled
     *
     * @param string|array $message The message to log
     * @return void
     */
    private function debug_log($message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // If message is an array or object, convert to string
            if (is_array($message) || is_object($message)) {
                // phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
                $message = print_r($message, true);
                // phpcs:enable
            }

            // phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[Cookie Consent] ' . $message);
            // phpcs:enable
        }
    }

    /**
     * Get user consent data from browser cookies and server-side storage
     *
     * @param bool $include_all_cookies Whether to include all detected cookies
     * @return array User consent data
     */
    public function get_user_consent_data(bool $include_all_cookies = false): array
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Starting consent data collection');
        }

        // Initialize with default values (only necessary cookies are permitted by default)
        $consent_data = array(
            'cookies_present' => array(),
            'consent_status'  => array(
                'necessary'  => true,
                'analytics'  => false,
                'functional' => false,
                'marketing'  => false,
            ),
        );

        try {
            // Get stored consent from cookie or localStorage (this is the source of truth)
            $stored_consent = $this->get_stored_consent();

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Stored consent data: ' . ($stored_consent ? json_encode($stored_consent) : 'None'));
            }

            // If we have stored consent from the client, use that as the primary source
            if ($stored_consent && isset($stored_consent['categories'])) {
                // Only update specific categories from stored consent
                foreach ($stored_consent['categories'] as $category => $status) {
                    if (isset($consent_data['consent_status'][$category])) {
                        // Ensure we convert to boolean
                        $consent_data['consent_status'][$category] = (bool)$status;

                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('Updated consent status from client storage: ' . $category . ' = ' .
                                ($consent_data['consent_status'][$category] ? 'true' : 'false'));
                        }
                    }
                }
            }
            // For logged-in users, also check user meta as a backup
            elseif (is_user_logged_in()) {
                $user_consent = get_user_meta(get_current_user_id(), 'custom_cookie_consent_data', true);

                if ($user_consent && isset($user_consent['categories'])) {
                    foreach ($user_consent['categories'] as $category => $status) {
                        if (isset($consent_data['consent_status'][$category])) {
                            // Ensure we convert to boolean
                            $consent_data['consent_status'][$category] = (bool)$status;
                        }
                    }
                }
            }

            // Always ensure necessary cookies are allowed
            $consent_data['consent_status']['necessary'] = true;

            // Get all browser cookies
            $all_cookies = $this->get_all_browser_cookies();

            // Settings for registered cookies in each category
            $settings = get_option('custom_cookie_settings', []);
            $cookies_by_category = [];

            // Necessary cookies - always include the consent cookie
            $cookies_by_category['necessary'] = [
                [
                    'name' => 'devora_cookie_consent',
                    'category' => 'necessary',
                    'description' => __('Cookie for storing your cookie preferences', 'custom-cookie-consent'),
                    'expiry' => '1 year'
                ]
            ];

            // Add WordPress cookies to necessary
            $wp_cookies = ['wordpress_test_cookie', 'wp-settings-time', 'wp_lang'];
            foreach ($wp_cookies as $wp_cookie) {
                if (isset($all_cookies[$wp_cookie])) {
                    $cookies_by_category['necessary'][] = [
                        'name' => $wp_cookie,
                        'category' => 'necessary',
                        'description' => __('WordPress cookie required for site functionality', 'custom-cookie-consent'),
                        'expiry' => 'Session'
                    ];
                }
            }

            // Add registered necessary cookies from settings
            if (isset($settings['necessary_cookies'])) {
                $registered_cookies = array_filter(explode("\n", $settings['necessary_cookies']));
                foreach ($registered_cookies as $cookie_name) {
                    $cookie_name = trim($cookie_name);
                    if (!empty($cookie_name)) {
                        $cookies_by_category['necessary'][] = [
                            'name' => $cookie_name,
                            'category' => 'necessary',
                            'description' => isset($settings['necessary_description']) ? $settings['necessary_description'] : __('Required for the website to function', 'custom-cookie-consent'),
                            'expiry' => '1 year'
                        ];
                    }
                }
            }

            // Add registered analytics cookies
            if (isset($settings['analytics_cookies'])) {
                $cookies_by_category['analytics'] = [];
                $registered_cookies = array_filter(explode("\n", $settings['analytics_cookies']));
                foreach ($registered_cookies as $cookie_name) {
                    $cookie_name = trim($cookie_name);
                    if (!empty($cookie_name)) {
                        $cookies_by_category['analytics'][] = [
                            'name' => $cookie_name,
                            'category' => 'analytics',
                            'description' => isset($settings['analytics_description']) ? $settings['analytics_description'] : __('Used for measuring website usage', 'custom-cookie-consent'),
                            'expiry' => $this->get_cookie_expiry($cookie_name)
                        ];
                    }
                }
            }

            // Add registered functional cookies
            if (isset($settings['functional_cookies'])) {
                $cookies_by_category['functional'] = [];
                $registered_cookies = array_filter(explode("\n", $settings['functional_cookies']));
                foreach ($registered_cookies as $cookie_name) {
                    $cookie_name = trim($cookie_name);
                    if (!empty($cookie_name)) {
                        $cookies_by_category['functional'][] = [
                            'name' => $cookie_name,
                            'category' => 'functional',
                            'description' => isset($settings['functional_description']) ? $settings['functional_description'] : __('Used for enhanced functionality', 'custom-cookie-consent'),
                            'expiry' => $this->get_cookie_expiry($cookie_name)
                        ];
                    }
                }
            }

            // Add registered marketing cookies
            if (isset($settings['marketing_cookies'])) {
                $cookies_by_category['marketing'] = [];
                $registered_cookies = array_filter(explode("\n", $settings['marketing_cookies']));
                foreach ($registered_cookies as $cookie_name) {
                    $cookie_name = trim($cookie_name);
                    if (!empty($cookie_name)) {
                        $cookies_by_category['marketing'][] = [
                            'name' => $cookie_name,
                            'category' => 'marketing',
                            'description' => isset($settings['marketing_description']) ? $settings['marketing_description'] : __('Used for personalized content and ads', 'custom-cookie-consent'),
                            'expiry' => $this->get_cookie_expiry($cookie_name)
                        ];
                    }
                }
            }

            // Get detected cookies from the cookie scanner
            $detected_cookies = get_option('custom_cookie_detected', array());

            // Process all browser cookies - either add to appropriate category or categorize
            foreach ($all_cookies as $name => $value) {
                $found = false;

                // First check if it's a registered cookie
                foreach ($cookies_by_category as $category => $cookies) {
                    foreach ($cookies as $cookie) {
                        if ($cookie['name'] === $name) {
                            $found = true;
                            break 2;
                        }
                    }
                }

                // If not registered, check if it's detected and categorized
                if (!$found || $include_all_cookies) {
                    $existing_category = null;

                    // Look for this cookie in detected cookies
                    foreach ($detected_cookies as $detected) {
                        if (
                            isset($detected['name']) && $detected['name'] === $name &&
                            isset($detected['status']) && $detected['status'] === 'categorized' &&
                            isset($detected['category'])
                        ) {

                            $existing_category = $detected['category'];

                            if (!isset($cookies_by_category[$existing_category])) {
                                $cookies_by_category[$existing_category] = [];
                            }

                            // Add cookie to the appropriate category
                            $cookies_by_category[$existing_category][] = [
                                'name' => $name,
                                'category' => $existing_category,
                                'description' => isset($detected['description']) ? $detected['description'] : '',
                                'expiry' => isset($detected['expires']) ? $detected['expires'] : $this->get_cookie_expiry($name),
                                'source' => isset($detected['source']) ? $detected['source'] : ''
                            ];

                            $found = true;
                            break;
                        }
                    }

                    // If still not found or we want all cookies, auto-categorize
                    if ((!$found || $include_all_cookies) && !in_array($name, ['devora_cookie_consent'])) {
                        // Auto-categorize
                        $category = $this->categorize_cookie($name);

                        if (!isset($cookies_by_category[$category])) {
                            $cookies_by_category[$category] = [];
                        }

                        // See if we have a detected cookie to use for description
                        $description = '';
                        $expires = '';
                        $source = '';

                        foreach ($detected_cookies as $detected) {
                            if (isset($detected['name']) && $detected['name'] === $name) {
                                $description = isset($detected['description']) ? $detected['description'] : '';
                                $expires = isset($detected['expires']) ? $detected['expires'] : '';
                                $source = isset($detected['source']) ? $detected['source'] : '';
                                break;
                            }
                        }

                        // If no description, generate one
                        if (empty($description)) {
                            $description = $this->get_cookie_description($name, $category);
                        }

                        // If no expiry, try to determine
                        if (empty($expires)) {
                            $expires = $this->get_cookie_expiry($name);
                        }

                        // If no source, try to determine
                        if (empty($source)) {
                            $source = $this->detect_cookie_source($name);
                        }

                        // Add the cookie to its category
                        $cookies_by_category[$category][] = [
                            'name' => $name,
                            'category' => $category,
                            'description' => $description,
                            'expiry' => $expires,
                            'source' => $source
                        ];
                    }
                }
            }

            // Combine all cookies into a single array based on consent status
            $cookies_present = [];

            foreach ($cookies_by_category as $category => $cookies) {
                // Only include necessary cookies or categories the user has consented to
                // But if include_all_cookies is true, include everything
                if (
                    $include_all_cookies ||
                    $category === 'necessary' ||
                    (isset($consent_data['consent_status'][$category]) && $consent_data['consent_status'][$category])
                ) {
                    foreach ($cookies as $cookie) {
                        $cookies_present[] = $cookie;
                    }
                }
            }

            $consent_data['cookies_present'] = $cookies_present;

            // Detect if we have any Google Analytics cookies
            $consent_data['has_ga'] = $this->has_ga_cookies();

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Final consent data: ' . json_encode([
                    'consent_status' => $consent_data['consent_status'],
                    'cookies_count' => count($consent_data['cookies_present']),
                ]));
            }

            return $consent_data;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Error getting user consent data: ' . $e->getMessage());
            }
            return $consent_data; // Return default data if error
        }
    }

    /**
     * Get all cookies from the browser request
     *
     * @return array
     */
    private function get_all_browser_cookies(): array
    {
        $cookies = array();

        // Get cookies from $_COOKIE superglobal
        if (!empty($_COOKIE)) {
            foreach ($_COOKIE as $name => $value) {
                // Skip cookies that have array values
                if (is_array($value)) {
                    continue;
                }
                $cookies[$name] = $value;
            }
        }

        // Try to detect cookies in HTTP_COOKIE for better coverage
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $raw_cookies = sanitize_text_field(wp_unslash($_SERVER['HTTP_COOKIE']));
            $cookies_arr = explode(';', $raw_cookies);

            foreach ($cookies_arr as $cookie) {
                $parts = explode('=', $cookie, 2);
                if (count($parts) === 2) {
                    $name = trim($parts[0]);
                    $value = trim($parts[1]);

                    // Skip if already in the array
                    if (!isset($cookies[$name])) {
                        $cookies[$name] = $value;
                    }
                }
            }
        }

        // Get detected cookies from scanner to include third-party domains
        $detected_cookies = get_option('custom_cookie_detected', array());
        foreach ($detected_cookies as $detected) {
            if (isset($detected['name']) && !isset($cookies[$detected['name']])) {
                // Include detected cookies that might not be directly visible
                $cookies[$detected['name']] = 'detected';
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Detected cookies: ' . json_encode(array_keys($cookies)));
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
        $consent_data = null;
        $cookie_name = 'devora_cookie_consent';

        // Try to get from cookie first
        if (isset($_COOKIE[$cookie_name])) {
            try {
                $cookie_value = sanitize_text_field(wp_unslash($_COOKIE[$cookie_name]));
                $decoded = json_decode($cookie_value, true);

                if (is_array($decoded) && isset($decoded['categories'])) {
                    $consent_data = $decoded;

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Retrieved consent from cookie: ' . json_encode($consent_data));
                    }
                }
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Error decoding cookie consent: ' . $e->getMessage());
                }
            }
        }

        // If no cookie data, try to get from server for logged-in users
        if (!$consent_data && is_user_logged_in()) {
            $user_consent = get_user_meta(get_current_user_id(), 'custom_cookie_consent_data', true);

            if (is_array($user_consent) && isset($user_consent['categories'])) {
                $consent_data = $user_consent;

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Retrieved consent from user meta: ' . json_encode($consent_data));
                }
            }
        }

        return $consent_data;
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
                $this->debug_log('Empty consent value');
            }
            return null;
        }

        try {
            // Step 1: URL decode
            $decoded_value = urldecode($value);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log('URL decoded consent value', $decoded_value);
            }

            // Step 2: Try direct JSON decode
            $consent_data = json_decode($decoded_value, true);
            $json_error   = json_last_error();

            // If successful, validate and return
            if ($json_error === JSON_ERROR_NONE && $this->validate_consent_data($consent_data)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log('Successfully parsed consent data', $consent_data);
                }
                return $consent_data;
            }

            // Step 3: Try with stripslashes if direct decode failed
            if ($json_error !== JSON_ERROR_NONE) {
                $cleaned_value = stripslashes($decoded_value);

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log('Attempting parse with stripslashes', $cleaned_value);
                }

                $consent_data = json_decode($cleaned_value, true);

                if (json_last_error() === JSON_ERROR_NONE && $this->validate_consent_data($consent_data)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log('Successfully parsed consent after stripslashes', $consent_data);
                    }
                    return $consent_data;
                }
            }

            // Step 4: Try handling potential double encoding
            $double_decoded = urldecode($decoded_value);
            if ($double_decoded !== $decoded_value) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log('Attempting parse with double URL decode', $double_decoded);
                }

                $consent_data = json_decode($double_decoded, true);

                if (json_last_error() === JSON_ERROR_NONE && $this->validate_consent_data($consent_data)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log('Successfully parsed consent after double decode', $consent_data);
                    }
                    return $consent_data;
                }

                // Try with stripslashes on double decoded value
                $cleaned_double = stripslashes($double_decoded);
                $consent_data   = json_decode($cleaned_double, true);

                if (json_last_error() === JSON_ERROR_NONE && $this->validate_consent_data($consent_data)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log('Successfully parsed consent after double decode + stripslashes', $consent_data);
                    }
                    return $consent_data;
                }
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log(
                    'All parsing attempts failed',
                    array(
                        'original'   => $value,
                        'decoded'    => $decoded_value,
                        'last_error' => json_last_error_msg(),
                    )
                );
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log(
                    'Exception while parsing consent',
                    array(
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                    )
                );
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
        if (! is_array($data) || ! isset($data['categories'])) {
            return false;
        }

        // Categories must be an array
        if (! is_array($data['categories'])) {
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
            '__hssc'                => '30 minutes',
            '__hssrc'               => 'Session',
            'hubspotutk'            => '13 months',
            '__hstc'                => '13 months',
            '_ga'                   => '2 years',
            '_gid'                  => '24 hours',
            '_ga_3LEBTMR1DL'        => '2 years',
            '_gcl_au'               => '3 months',
            '_lscache_vary'         => 'Session',
            '__cf_bm'               => '30 minutes',
            '_cfuvid'               => 'Session',
            'devora_cookie_consent' => '1 year',
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
                $this->debug_log('Found GA cookie:', $name);
            }
        }
        return $has_cookies;
    }

    /**
     * Shortcode for showing current user consent data
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function show_consent_data_shortcode(array $atts = []): string
    {
        // Add nocache headers to prevent caching this page
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        // Apply nocache headers if page contains this shortcode
        nocache_headers();

        // Enqueue the required scripts/styles for this shortcode
        // Only enqueue if not already loaded
        if (!wp_script_is('custom-cookie-consent', 'enqueued')) {
            $this->enqueue_scripts();
        }

        // Generate a unique nonce for AJAX security
        $nonce = wp_create_nonce('consent_data_nonce');

        // Directly inject ajaxurl for front-end
        $ajaxurl = esc_js(admin_url('admin-ajax.php'));

        // Get settings for JavaScript templating - use Norwegian text if available
        $settings = get_option('custom_cookie_settings', array());
        $necessary_title = isset($settings['necessary_title']) && !empty($settings['necessary_title']) ?
            esc_js($settings['necessary_title']) : __('Nødvendige', 'custom-cookie-consent');
        $analytics_title = isset($settings['analytics_title']) && !empty($settings['analytics_title']) ?
            esc_js($settings['analytics_title']) : __('Analyse', 'custom-cookie-consent');
        $functional_title = isset($settings['functional_title']) && !empty($settings['functional_title']) ?
            esc_js($settings['functional_title']) : __('Funksjonell', 'custom-cookie-consent');
        $marketing_title = isset($settings['marketing_title']) && !empty($settings['marketing_title']) ?
            esc_js($settings['marketing_title']) : __('Markedsføring', 'custom-cookie-consent');

        $consent_choices_heading = isset($settings['consent_choices_heading']) && !empty($settings['consent_choices_heading']) ?
            esc_js($settings['consent_choices_heading']) : __('Dine samtykkevalg', 'custom-cookie-consent');
        $active_cookies_heading = isset($settings['active_cookies_heading']) && !empty($settings['active_cookies_heading']) ?
            esc_js($settings['active_cookies_heading']) : __('Aktive informasjonskapsler', 'custom-cookie-consent');
        $last_updated = isset($settings['consent_last_updated']) && !empty($settings['consent_last_updated']) ?
            esc_js($settings['consent_last_updated']) : __('Sist oppdatert', 'custom-cookie-consent');

        $consent_status_accepted = isset($settings['consent_status_accepted']) && !empty($settings['consent_status_accepted']) ?
            esc_js($settings['consent_status_accepted']) : __('Godtatt', 'custom-cookie-consent');
        $consent_status_declined = isset($settings['consent_status_declined']) && !empty($settings['consent_status_declined']) ?
            esc_js($settings['consent_status_declined']) : __('Avslått', 'custom-cookie-consent');

        $cookie_category_label = isset($settings['cookie_category_label']) && !empty($settings['cookie_category_label']) ?
            esc_js($settings['cookie_category_label']) : __('Kategori', 'custom-cookie-consent');
        $cookie_purpose_label = isset($settings['cookie_purpose_label']) && !empty($settings['cookie_purpose_label']) ?
            esc_js($settings['cookie_purpose_label']) : __('Formål', 'custom-cookie-consent');
        $cookie_expiry_label = isset($settings['cookie_expiry_label']) && !empty($settings['cookie_expiry_label']) ?
            esc_js($settings['cookie_expiry_label']) : __('Utløper', 'custom-cookie-consent');

        $no_cookies_message = isset($settings['no_cookies_message']) && !empty($settings['no_cookies_message']) ?
            esc_js($settings['no_cookies_message']) : __('Ingen informasjonskapsler oppdaget.', 'custom-cookie-consent');
        $edit_consent_button_text = isset($settings['edit_consent_button_text']) && !empty($settings['edit_consent_button_text']) ?
            esc_js($settings['edit_consent_button_text']) : __('ENDRE SAMTYKKEINNSTILLINGER', 'custom-cookie-consent');

        $error_message = esc_js(__('Feil ved lasting av samtykkedata. Vennligst prøv igjen.', 'custom-cookie-consent'));

        $output = '<div class="cookie-consent-data-display" id="devora-consent-data" style="max-width: 800px; margin: 2rem auto; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">';
        $output .= '<input type="hidden" id="consent_data_nonce" value="' . esc_attr($nonce) . '">';
        $output .= '<input type="hidden" id="consent_data_ajaxurl" value="' . esc_attr($ajaxurl) . '">';

        // Add loading indicator
        $output .= '<div id="cookie-consent-data-loading" class="consent-loading" style="text-align: center; padding: 2rem;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid rgba(42, 46, 100, 0.3); border-radius: 50%; border-top-color: #2a2e64; animation: cookie-consent-spin 1s ease-in-out infinite;"></div>
            <p>' . esc_html__('Laster samtykkedata...', 'custom-cookie-consent') . '</p>
        </div>';

        // Container for consent data
        $output .= '<div id="cookie-consent-data-container" style="display: none;"></div>';

        // Add footer with button to open consent settings
        $output .= '<div style="margin-top: 2rem; text-align: center;">
            <button type="button" id="cookie-consent-settings-button" class="open-cookie-settings" style="background-color: #2a2e64; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase;">
                ' . esc_html($edit_consent_button_text) . '
            </button>
        </div>';

        // Add CSS for spinner and data display
        $output .= '<style>
            @keyframes cookie-consent-spin {
                to { transform: rotate(360deg); }
            }
            .cookie-consent-data-display {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                color: #333;
                line-height: 1.6;
            }
            .cookie-consent-data-display h3, 
            .cookie-consent-data-display h4, 
            .cookie-consent-data-display h5 {
                margin-top: 1.5rem;
                margin-bottom: 0.75rem;
                color: #222;
                font-weight: 600;
            }
            .cookie-consent-data-display .timestamp {
                color: #666;
                font-size: 0.9em;
                margin-bottom: 1rem;
            }
            .cookie-consent-data-display .consent-category {
                margin-bottom: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .cookie-consent-data-display .category-name {
                font-weight: 500;
            }
            .cookie-consent-data-display .status-accepted {
                color: #2a2e64;
                font-weight: 500;
            }
            .cookie-consent-data-display .status-declined {
                color: #666;
                font-weight: 500;
            }
            .cookie-consent-data-display .cookie-category-wrap {
                margin-bottom: 1.5rem;
            }
            .cookie-consent-data-display .cookie-category-title {
                margin: 0;
                padding: 0.75rem;
                background: #f1f1f1;
                color: #333;
                font-weight: 600;
                font-size: 1.1em;
                display: block;
                width: 100%;
                border-left: 3px solid #2a2e64;
            }
            .cookie-consent-data-display .cookie-list {
                margin-top: 0;
                padding: 0;
            }
            .cookie-consent-data-display .cookie-details-row {
                display: flex;
                padding: 1rem;
                border-bottom: 1px solid #eee;
                background: #fff;
            }
            .cookie-consent-data-display .cookie-details-row:last-child {
                border-bottom: none;
            }
            .cookie-consent-data-display .cookie-name-col {
                flex: 0 0 35%;
                font-weight: 500;
                padding-right: 1rem;
            }
            .cookie-consent-data-display .cookie-meta-col {
                flex: 0 0 65%;
            }
            .cookie-consent-data-display .cookie-meta {
                margin: 0 0 0.35rem;
                font-size: 0.9em;
            }
            .cookie-consent-data-display .cookie-meta:last-child {
                margin-bottom: 0;
            }
            .cookie-consent-data-display .cookie-meta strong {
                display: inline-block;
                min-width: 80px;
                color: #666;
            }
            .cookie-consent-data-display .error-message {
                color: #666;
                text-align: center;
            }
            #cookie-consent-settings-button {
                background-color: #2a2e64;
                color: white;
                border: none;
                padding: 0.75rem 1.5rem;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }
            #cookie-consent-settings-button:hover {
                background-color: #3a3e74;
            }
            @media (max-width: 600px) {
                .cookie-consent-data-display .cookie-details-row {
                    flex-direction: column;
                }
                .cookie-consent-data-display .cookie-name-col {
                    flex: 0 0 100%;
                    padding-right: 0;
                    padding-bottom: 0.5rem;
                    margin-bottom: 0.5rem;
                    border-bottom: 1px solid #f5f5f5;
                }
                .cookie-consent-data-display .cookie-meta-col {
                    flex: 0 0 100%;
                }
            }
        </style>';

        // JavaScript to fetch the latest consent data
        $output .= <<<JAVASCRIPT
<script>
(function() {
    // Configuration
    const config = {
        retryDelay: 1000,
        maxRetries: 3,
        strings: {
            necessary: "{$necessary_title}",
            analytics: "{$analytics_title}",
            functional: "{$functional_title}",
            marketing: "{$marketing_title}",
            lastUpdated: "{$last_updated}",
            choicesHeading: "{$consent_choices_heading}",
            accepted: "{$consent_status_accepted}",
            declined: "{$consent_status_declined}",
            activeCookies: "{$active_cookies_heading}",
            noCookies: "{$no_cookies_message}",
            category: "{$cookie_category_label}",
            purpose: "{$cookie_purpose_label}",
            expiry: "{$cookie_expiry_label}",
            error: "{$error_message}"
        }
    };
    
    // Always define ajaxurl for the front-end
    var ajaxurl = document.getElementById('consent_data_ajaxurl').value;
    
    // Debug logging - only logs to console when debug is enabled
    function debug(message, data) {
        if (window.cookieConsentSettings?.debug) {
            console.log("[Consent Data]", message, data || "");
        }
    }
    
    // Get the nonce from the hidden field
    function getNonce() {
        const nonceField = document.getElementById("consent_data_nonce");
        return nonceField ? nonceField.value : "";
    }
    
    // Fetch consent data with retries
    function fetchConsentData(retryCount = 0) {
        const container = document.getElementById("cookie-consent-data-container");
        const loading = document.getElementById("cookie-consent-data-loading");
        
        if (!container || !loading) {
            debug("Required DOM elements not found");
            return;
        }
        
        // Show loading
        loading.style.display = "block";
        container.style.display = "none";
        
        // Get the nonce
        const nonce = getNonce();
        
        if (!ajaxurl) {
            debug("AJAX URL is missing");
            loading.innerHTML = '<p class="error-message">Error: AJAX URL missing</p>';
            return;
        }
        
        debug("Fetching consent data", { 
            attempt: retryCount + 1,
            url: ajaxurl,
            nonce: nonce ? "Valid" : "Missing"
        });
        
        // Add cache-busting parameter for CDN compatibility
        const cacheBuster = new Date().getTime();
        
        // Fetch data with proper error handling
        fetch(ajaxurl + '?_=' + cacheBuster, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: "custom_cookie_get_consent",
                security: nonce
            })
        })
        .then(response => {
            debug("Response status", response.status);
            
            if (!response.ok) {
                debug("Response not OK", {
                    status: response.status,
                    statusText: response.statusText
                });
                throw new Error("Network error: " + response.status + " " + response.statusText);
            }
            return response.json();
        })
        .then(response => {
            debug("Response received", response);
            
            if (response.success && response.data) {
                renderConsentData(response.data);
                loading.style.display = "none";
                container.style.display = "block";
            } else {
                debug("Invalid response format", response);
                throw new Error(response.data?.message || "Invalid response format");
            }
        })
        .catch(error => {
            debug("Error fetching consent data", error.message);
            
            if (retryCount < config.maxRetries) {
                debug("Retrying (" + (retryCount + 1) + "/" + config.maxRetries + ")...");
                setTimeout(() => fetchConsentData(retryCount + 1), config.retryDelay);
                return;
            }
            
            container.innerHTML = 
                "<p class=\"error-message\">" + 
                config.strings.error + 
                "</p>" +
                "<p class=\"error-details\">" +
                error.message +
                "</p>";
            loading.style.display = "none";
            container.style.display = "block";
        });
    }
    
    // Function to render consent data
    function renderConsentData(data) {
        const container = document.getElementById("cookie-consent-data-container");
        if (!container) {
            debug("Container not found");
            return;
        }
        
        debug("Rendering consent data", data);
        
        let html = "";
        
        // Add last updated time if available
        if (data.timestamp) {
            const date = new Date(data.timestamp * 1000);
            const formattedDate = date.toLocaleDateString() + " " + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            html += "<p class=\"timestamp\">" + config.strings.lastUpdated + ": " + formattedDate + "</p>";
        }
        
        // Show consent status
        html += "<h3>" + config.strings.choicesHeading + "</h3>";
        html += "<div style=\"margin-bottom: 2rem;\">";
        
        // Display consent status for each category
        if (data.consent_status) {
            for (const category in data.consent_status) {
                if (Object.prototype.hasOwnProperty.call(data.consent_status, category)) {
                    const status = !!data.consent_status[category]; // Force boolean
                    
                    // Get category title
                    let categoryDisplay = category;
                    if (category === "necessary") {
                        categoryDisplay = config.strings.necessary;
                    } else if (category === "analytics") {
                        categoryDisplay = config.strings.analytics;
                    } else if (category === "functional") {
                        categoryDisplay = config.strings.functional;
                    } else if (category === "marketing") {
                        categoryDisplay = config.strings.marketing;
                    }
                    
                    const statusClass = status ? "status-accepted" : "status-declined";
                    const statusText = status 
                        ? config.strings.accepted
                        : config.strings.declined;
                    
                    html += 
                        "<div class=\"consent-category\">" +
                        "<span class=\"category-name\">" + categoryDisplay + ":</span>" +
                        "<span class=\"" + statusClass + "\">" + statusText + "</span>" +
                        "</div>";
                }
            }
        }
        html += "</div>";
        
        // Show active cookies
        if (data.cookies_present && data.cookies_present.length > 0) {
            html += "<h4>" + config.strings.activeCookies + ":</h4>";
            
            // Group cookies by category
            const cookiesByCategory = {};
            for (const cookie of data.cookies_present) {
                if (!cookiesByCategory[cookie.category]) {
                    cookiesByCategory[cookie.category] = [];
                }
                cookiesByCategory[cookie.category].push(cookie);
            }
            
            // Display cookies by category
            for (const category in cookiesByCategory) {
                if (Object.prototype.hasOwnProperty.call(cookiesByCategory, category)) {
                    const cookies = cookiesByCategory[category];
                    if (cookies.length === 0) continue;
                    
                    // Get category title
                    let categoryDisplay = category;
                    if (category === "necessary") {
                        categoryDisplay = config.strings.necessary;
                    } else if (category === "analytics") {
                        categoryDisplay = config.strings.analytics;
                    } else if (category === "functional") {
                        categoryDisplay = config.strings.functional;
                    } else if (category === "marketing") {
                        categoryDisplay = config.strings.marketing;
                    }
                    
                    html += "<div class=\"cookie-category-wrap\">";
                    html += "<h5 class=\"cookie-category-title\">" + categoryDisplay + "</h5>";
                    html += "<div class=\"cookie-list\">";
                    
                    for (const cookie of cookies) {
                        html += 
                            "<div class=\"cookie-details-row\">" +
                            "<div class=\"cookie-name-col\">" + (cookie.name || "Unknown") + "</div>" +
                            "<div class=\"cookie-meta-col\">" +
                            "<p class=\"cookie-meta\"><strong>" + config.strings.purpose + ":</strong> " + (cookie.description || "Not specified") + "</p>" +
                            "<p class=\"cookie-meta\"><strong>" + config.strings.expiry + ":</strong> " + (cookie.expiry || "Not specified") + "</p>" +
                            "</div>" +
                            "</div>";
                    }
                    
                    html += "</div></div>";
                }
            }
        } else {
            html += "<p style=\"margin: 1.5rem 0; color: #666;\">" + config.strings.noCookies + "</p>";
        }
        
        container.innerHTML = html;
    }
    
    // Format date to local string
    function formatDate(timestamp) {
        if (!timestamp) return '';
        
        // Check if timestamp is a MySQL date string
        if (typeof timestamp === 'string' && timestamp.includes('-')) {
            return new Date(timestamp).toLocaleString();
        }
        
        // Handle UNIX timestamp (seconds)
        if (typeof timestamp === 'number' || !isNaN(parseInt(timestamp))) {
            const ts = parseInt(timestamp);
            // Convert seconds to milliseconds if needed
            const date = new Date(ts < 10000000000 ? ts * 1000 : ts);
            return date.toLocaleString();
        }
        
        return timestamp;
    }

    // Add event listener to the settings button
    const settingsButton = document.getElementById("cookie-consent-settings-button");
    if (settingsButton) {
        settingsButton.addEventListener("click", function(e) {
            e.preventDefault();
            
            // Try multiple approaches to open the consent settings
            try {
                // Approach 1: Use the global consentManager object if available
                if (window.consentManager && typeof window.consentManager.showConsentBanner === 'function') {
                    // Preferred approach
                    window.consentManager.showConsentBanner();
                    return;
                }
                
                // Approach 2: Use the constructor if available but instance not created yet
                if (typeof window.ConsentManager === 'function' && !window.consentManager) {
                    window.consentManager = new window.ConsentManager();
                    if (typeof window.consentManager.showConsentBanner === 'function') {
                        window.consentManager.showConsentBanner();
                        return;
                    }
                }
                
                // Approach 3: Dispatch a custom event that the banner listens for
                const event = new CustomEvent('showCookieBanner');
                document.dispatchEvent(event);
                
                // Approach 4: Find and click an existing cookie settings link
                const existingLinks = document.querySelectorAll('.cookie-settings-trigger');
                if (existingLinks && existingLinks.length > 0) {
                    existingLinks[0].click();
                    return;
                }
                
                // Approach 5: Reload the page to show the banner
                // This is a fallback if nothing else works
                document.cookie = "devora_cookie_consent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                window.location.reload();
            } catch (err) {
                console.error("Error showing cookie banner:", err);
                
                // Final fallback - reload page
                document.cookie = "devora_cookie_consent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                window.location.reload();
            }
        });
    }
    
    // Add event listener for consent updated events
    document.addEventListener("consentUpdated", function(event) {
        debug("Consent updated event received", event.detail);
        
        // Add a delay to allow storage to be updated
        setTimeout(function() {
            fetchConsentData();
            
            // Reload page after consent update (improves compatibility)
            if (event.detail && event.detail.reload !== false) {
                window.location.reload();
            }
        }, 1000);
    });
    
    // Initial fetch - use a small delay to ensure DOM is ready
    setTimeout(function() {
        fetchConsentData();
    }, 500);
})();
</script>
JAVASCRIPT;

        $output .= '</div>';

        return $output;
    }

    /**
     * Get the admin-ajax.php URL
     *
     * @return string
     */
    private function get_admin_ajax_url(): string
    {
        return admin_url('admin-ajax.php');
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
        $settings     = get_option('custom_cookie_settings', array());
        $banner_title = $settings['banner_title'] ?? __('Cookie Consent', 'custom-cookie-consent');
        $banner_text  = $settings['banner_text'] ?? __('We use cookies to improve your experience on our website.', 'custom-cookie-consent');

        // Get categories
        $categories     = CookieCategories::get_categories();
        $category_names = array_map(
            function ($cat) {
                return $cat['title'] ?? '';
            },
            $categories
        );

        // Build schema.org structured data
        $schema = array(
            '@context'             => 'https://schema.org',
            '@type'                => 'WebSite',
            'name'                 => get_bloginfo('name'),
            'potentialAction'      => array(
                '@type'      => 'CommunicateAction',
                'about'      => array(
                    '@type'       => 'Thing',
                    'name'        => $banner_title,
                    'description' => $banner_text,
                ),
                'instrument' => array(
                    '@type'               => 'WebApplication',
                    'name'                => 'Cookie Consent by Devora',
                    'applicationCategory' => 'Privacy Tool',
                    'offers'              => array(
                        '@type'    => 'Offer',
                        'category' => implode(', ', $category_names),
                    ),
                ),
            ),
            'accessModeSufficient' => array(
                'visual',
                'textual',
                'auditory',
            ),
            'accessibilityControl' => array(
                'fullKeyboardControl',
                'fullMouseControl',
                'fullTouchControl',
            ),
            'accessibilityFeature' => array(
                'highContrast',
                'largePrint',
                'structuralNavigation',
                'alternativeText',
            ),
            'accessibilityHazard'  => array(
                'noFlashingHazard',
                'noMotionSimulationHazard',
                'noSoundHazard',
            ),
        );

        // Add bot-specific information
        if ($is_bot) {
            $schema['potentialAction']['result'] = array(
                '@type'       => 'SearchAction',
                'description' => 'Automatic consent granted for search engine crawlers',
                'query'       => 'Full content access enabled for bots',
            );

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
        $exporters['cookie-consent-by-devora'] = array(
            'exporter_friendly_name' => __('Cookie Consent Data', 'custom-cookie-consent'),
            'callback'               => array($this, 'export_cookie_consent_data'),
        );

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
        $user         = get_user_by('email', $email_address);
        $export_items = array();

        if (! $user) {
            return array(
                'data' => array(),
                'done' => true,
            );
        }

        // Get consent data from user meta
        $consent_data = get_user_meta($user->ID, 'custom_cookie_consent_data', true);

        if (! empty($consent_data)) {
            $data = array();

            // Add consent status for each category
            if (isset($consent_data['categories'])) {
                foreach ($consent_data['categories'] as $category => $status) {
                    $data[] = array(
                        'name'  => sprintf(__('%s Cookies', 'custom-cookie-consent'), ucfirst($category)),
                        'value' => $status ? __('Accepted', 'custom-cookie-consent') : __('Declined', 'custom-cookie-consent'),
                    );
                }
            }

            // Add consent timestamp
            if (isset($consent_data['timestamp'])) {
                $data[] = array(
                    'name'  => __('Consent Date', 'custom-cookie-consent'),
                    'value' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($consent_data['timestamp'])),
                );
            }

            // Add consent version
            if (isset($consent_data['version'])) {
                $data[] = array(
                    'name'  => __('Consent Version', 'custom-cookie-consent'),
                    'value' => $consent_data['version'],
                );
            }

            $export_items[] = array(
                'group_id'    => 'cookie-consent',
                'group_label' => __('Cookie Consent', 'custom-cookie-consent'),
                'item_id'     => 'cookie-consent-' . $user->ID,
                'data'        => $data,
            );
        }

        return array(
            'data' => $export_items,
            'done' => true,
        );
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
        $erasers['cookie-consent-by-devora'] = array(
            'eraser_friendly_name' => __('Cookie Consent Data', 'custom-cookie-consent'),
            'callback'             => array($this, 'erase_cookie_consent_data'),
        );

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
        $user           = get_user_by('email', $email_address);
        $items_removed  = false;
        $items_retained = false;
        $messages       = array();

        if ($user) {
            // Check if user has consent data
            $consent_data = get_user_meta($user->ID, 'custom_cookie_consent_data', true);

            if (! empty($consent_data)) {
                // Delete the consent data
                $deleted = delete_user_meta($user->ID, 'custom_cookie_consent_data');

                if ($deleted) {
                    $items_removed = true;
                    $messages[]    = __('Cookie consent data has been removed.', 'custom-cookie-consent');
                } else {
                    $items_retained = true;
                    $messages[]     = __('Cookie consent data could not be removed.', 'custom-cookie-consent');
                }
            } else {
                $messages[] = __('No cookie consent data found for this user.', 'custom-cookie-consent');
            }
        } else {
            $messages[] = __('No user found with this email address.', 'custom-cookie-consent');
        }

        return array(
            'items_removed'  => $items_removed,
            'items_retained' => $items_retained,
            'messages'       => $messages,
            'done'           => true,
        );
    }

    /**
     * Adds privacy policy content.
     *
     * @since 1.1.4
     * @return void
     */
    public function add_privacy_policy_content()
    {
        if (! function_exists('wp_add_privacy_policy_content')) {
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
        if (! is_user_logged_in()) {
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
        try {
            // Get and validate the consent data
            $raw_consent_data = isset($_POST['consent_data']) ? sanitize_text_field(wp_unslash($_POST['consent_data'])) : '';
            $consent_data = json_decode($raw_consent_data, true);

            if (!$consent_data || !isset($consent_data['categories'])) {
                wp_send_json_error(['message' => __('Invalid consent data format', 'custom-cookie-consent')]);
                return;
            }

            // Validate categories structure
            foreach ($consent_data['categories'] as $category => $status) {
                if (!in_array($category, ['necessary', 'analytics', 'functional', 'marketing'])) {
                    wp_send_json_error(['message' => __('Invalid category in consent data', 'custom-cookie-consent')]);
                    return;
                }
                $consent_data['categories'][$category] = (bool)$status;
            }

            // Save consent for logged-in users
            if (is_user_logged_in()) {
                update_user_meta(get_current_user_id(), 'custom_cookie_consent_data', $consent_data);
            }

            // Log the consent data
            $consent_logger = new ConsentLogger();
            $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'banner';
            $consent_logger->log_consent($consent_data, $source);

            // Update WP Consent API if enabled
            if (!empty($this->settings['wp_consent_api'])) {
                foreach ($consent_data['categories'] as $category => $status) {
                    WPConsentWrapper::set_consent($category, (bool)$status);
                }
            }

            // Update consent statistics
            $this->update_consent_statistics($consent_data);

            wp_send_json_success([
                'message' => __('Consent preferences saved', 'custom-cookie-consent'),
                'data' => $consent_data
            ]);
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Cookie Consent Error: ' . $e->getMessage());
            }
            wp_send_json_error([
                'message' => __('Error saving consent data', 'custom-cookie-consent'),
                'error' => WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Updates consent statistics
     *
     * @param array $consent_data
     * @return void
     */
    private function update_consent_statistics(array $consent_data): void
    {
        $consent_stats = get_option('custom_cookie_consent_stats', [
            'total' => 0,
            'analytics_accepted' => 0,
            'functional_accepted' => 0,
            'marketing_accepted' => 0,
            'last_updated' => time()
        ]);

        $consent_stats['total']++;
        if (!empty($consent_data['categories']['analytics'])) {
            $consent_stats['analytics_accepted']++;
        }
        if (!empty($consent_data['categories']['functional'])) {
            $consent_stats['functional_accepted']++;
        }
        if (!empty($consent_data['categories']['marketing'])) {
            $consent_stats['marketing_accepted']++;
        }
        $consent_stats['last_updated'] = time();

        update_option('custom_cookie_consent_stats', $consent_stats);
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
     * AJAX handler for getting consent data
     */
    public function ajax_get_consent_data()
    {
        try {
            // Check for nonce
            if (!isset($_POST['security'])) {
                $this->debug_log("AJAX get_consent_data missing security token");
                throw new Exception("Missing security token");
            }

            // Verify nonce
            if (!wp_verify_nonce($_POST['security'], 'consent_data_nonce')) {
                $this->debug_log("AJAX get_consent_data invalid security token");
                throw new Exception("Invalid security token");
            }

            // Get all consent data including detected cookies
            $consent_data = $this->get_user_consent_data(true);

            // Add current timestamp
            $consent_data['timestamp'] = current_time('timestamp');

            // Log some debug info
            $this->debug_log("Consent data retrieved for shortcode: " . json_encode([
                'consent_status' => $consent_data['consent_status'] ?? null,
                'cookie_count' => count($consent_data['cookies_present'] ?? [])
            ]));

            // Return the data
            wp_send_json_success($consent_data);
        } catch (Exception $e) {
            $this->debug_log("AJAX get_consent_data error: " . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        wp_die();
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

    private function enqueue_frontend_scripts(): void
    {
        $settings     = \get_option('custom_cookie_settings', array());
        $cache_buster = isset($settings['last_updated']) ? $settings['last_updated'] : time();

        // Enqueue cookie consent styles
        \wp_enqueue_style(
            'custom-cookie-style',
            \plugins_url('css/cookie-consent.css', __FILE__),
            array(),
            $cache_buster
        );

        // Enqueue the consent manager script
        \wp_enqueue_script(
            'custom-cookie-script',
            \plugins_url('js/consent-manager.js', __FILE__),
            array(),
            $cache_buster,
            true
        );

        // Get banner position setting - check both keys for backward compatibility
        $position = 'bottom'; // Default position
        if (isset($settings['position']) && in_array($settings['position'], array('bottom', 'top', 'center'), true)) {
            $position = $settings['position'];
        } elseif (isset($settings['banner_position']) && in_array($settings['banner_position'], array('bottom', 'top', 'center'), true)) {
            $position = $settings['banner_position'];
        }

        // Get cookie policy URLs
        $privacy_url       = isset($settings['privacy_url']) ? $settings['privacy_url'] : '';
        $cookie_policy_url = isset($settings['cookie_policy_url']) ? $settings['cookie_policy_url'] : $privacy_url;

        // Get the consent region setting
        $consent_region = isset($settings['consent_region']) ? $settings['consent_region'] : 'NO';

        // Localize script with settings
        \wp_localize_script(
            'custom-cookie-script',
            'cookieSettings',
            array(
                'ajaxUrl'            => \admin_url('admin-ajax.php'),
                'nonce'              => \wp_create_nonce('custom_cookie_nonce'),
                'position'           => $position,
                'privacyPolicyUrl'   => $privacy_url,
                'cookiePolicyUrl'    => $cookie_policy_url,
                'enableCookieGroups' => true,
                'cookieExpiration'   => 365, // Days
                'consent_region'     => $consent_region,
                'debugMode'          => defined('WP_DEBUG') && WP_DEBUG,
            )
        );

        // Enqueue the banner template script
        \wp_enqueue_script(
            'cookie-banner-template',
            \plugins_url('js/banner-template.js', __FILE__),
            array('custom-cookie-script'),
            $cache_buster,
            true
        );
    }

    /**
     * Register REST API routes for cookie scanning
     */
    public function register_routes(): void
    {
        register_rest_route(
            'custom-cookie-consent/v1',
            '/scan',
            array(
                'methods'             => 'POST',
                'callback'           => array($this->cookie_scanner, 'scan_cookies'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            )
        );

        register_rest_route(
            'custom-cookie-consent/v1',
            '/categorize',
            array(
                'methods'             => 'POST',
                'callback'           => array($this->cookie_scanner, 'categorize_cookie'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            )
        );

        register_rest_route(
            'custom-cookie-consent/v1',
            '/bulk-categorize',
            array(
                'methods'             => 'POST',
                'callback'           => array($this->cookie_scanner, 'bulk_categorize_cookies'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            )
        );
    }

    /**
     * Add banner position CSS
     */
    public function add_banner_position_css(): void
    {
        // Get settings
        $settings = \get_option('custom_cookie_settings', array());

        // Get position from settings (check both keys for backward compatibility)
        $position = 'bottom'; // Default position

        if (isset($settings['position']) && in_array($settings['position'], array('bottom', 'top', 'center'), true)) {
            $position = $settings['position'];
        } elseif (isset($settings['banner_position']) && in_array($settings['banner_position'], array('bottom', 'top', 'center'), true)) {
            $position = $settings['banner_position'];
        }

        // Don't add CSS if using the new method with load_full_css
        if (! empty($settings['defer_css'])) {
            return;
        }

        // Output position-specific CSS
        echo '<style id="cookie-banner-position-css">
            .cookie-consent-banner {
                ' . ($position === 'bottom' ? 'bottom: 0;' : ($position === 'top' ? 'top: 0;' : 'top: 50%;')) . '
                ' . ($position === 'center' ? 'left: 50%; transform: translate(-50%, -50%);' : 'left: 0; right: 0; transform: none;') . '
                ' . ($position !== 'center' ? 'width: 100%;' : 'max-width: 500px; width: calc(100% - 40px);') . '
            }
        </style>';
    }

    /**
     * Outputs GTM noscript tag.
     *
     * @return void
     */
    public function output_gtm_noscript(): void
    {
        $settings = get_option('custom_cookie_settings', array());
        $direct_tracking_enabled = isset($settings['direct_tracking_enabled']) ? (bool)$settings['direct_tracking_enabled'] : false;
        $gtm_id = isset($settings['gtm_id']) ? sanitize_text_field($settings['gtm_id']) : '';

        if ($direct_tracking_enabled && !empty($gtm_id)) {
            echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr($gtm_id) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';
        }
    }

    /**
     * Outputs early-loading anti-ad-blocker script in the head
     * This must run before ad blockers can initialize to ensure it's not blocked
     * 
     * @return void
     */
    public function output_early_anti_blocker(): void
    {
        // Only output if anti-blocker is enabled
        $settings = get_option('custom_cookie_settings', array());
        if (empty($settings['enable_anti_blocker'])) {
            return;
        }

        // Use a neutral name without "cookie" or "banner" to avoid detection
    ?>
        <script data-cfasync="false">
            (function() {
                // Skip early detection for bots
                if (/bot|googlebot|crawler|spider|robot|crawling/i.test(navigator.userAgent)) {
                    return;
                }

                // Create a tiny sentinel element to detect blocking
                var sentinelId = 'privacy-ui-' + Math.random().toString(36).substring(2, 10);
                var sentinel = document.createElement('div');
                sentinel.id = sentinelId;
                sentinel.style.position = 'absolute';
                sentinel.style.width = '1px';
                sentinel.style.height = '1px';
                sentinel.style.left = '-9999px';
                sentinel.style.opacity = '0.01';
                sentinel.setAttribute('data-privacy-component', 'true');

                // Add custom attributes to avoid common ad blocker filters
                sentinel.setAttribute('data-cfasync', 'false');
                sentinel.setAttribute('data-dd-privacy', 'compliance');

                // Add to document as early as possible
                (document.head || document.documentElement).appendChild(sentinel);

                // Store references to key functions that might be blocked or overridden
                var originalAppendChild = Element.prototype.appendChild;
                var originalCreateElement = document.createElement;
                var originalGetElementById = document.getElementById;

                // Extend window with a property that ad blockers are unlikely to block
                window.privacyManagerConfig = {
                    sentinelId: sentinelId,
                    domReady: false
                };

                // Check DOM readiness
                document.addEventListener('DOMContentLoaded', function() {
                    window.privacyManagerConfig.domReady = true;
                });
            })();
        </script>
    <?php
    }

    /**
     * Auto-categorize cookies based on name patterns
     *
     * @param string $cookie_name Name of the cookie
     * @return string Category (necessary, analytics, functional, marketing)
     */
    private function categorize_cookie(string $cookie_name): string
    {
        // Convert to lowercase for case-insensitive matching
        $name = strtolower($cookie_name);

        // Common analytics cookies
        $analytics_patterns = [
            '_ga',
            '_gid',
            '_gat', // Google Analytics
            'statcounter',
            'sc_is_visitor_unique', // StatCounter
            'matomo',
            'piwik',
            '_pk_', // Matomo
            'amplitude',
            'mixpanel', // Other analytics
            'plausible',
            'ahoy_',
            'ahoy_visitor', // Other analytics
            'countly',
            'hotjar',
            '_hjSession',
            '_hj', // Hotjar
            'optimizely',
            'fullstory',
            '_fs', // Testing/Analytics
            '_clck',
            '_clsk',
            'clarity', // Microsoft Clarity
            'yt-player-', // YouTube
        ];

        // Common marketing cookies
        $marketing_patterns = [
            'fbp',
            '_fbp', // Facebook
            'personalization_id', // Twitter
            'lidc',
            'bcookie',
            'bscookie', // LinkedIn
            'hubspotutk',
            '__hssc',
            '__hssrc',
            '__hstc', // HubSpot
            'adroll',
            'criteo',
            'adform', // Ad networks
            'doubleclick',
            '__gads',
            '_gcl_', // Google Ads
            'pinterest_',
            '_pin_', // Pinterest
            'MUID',
            'MC1', // Bing
            'IDE',
            'id', // DoubleClick
            'anj',
            'uids',
            'uuid2', // AppNexus
            'tuuid',
            'tusn',
            'tuid', // Improve Digital
            'c',
            'cid',
            'cto_', // Criteo
            'fr',
            'tr', // Facebook retargeting
            'user_id',
            'uid',
            'ads', // Various ad networks
            'VISITOR_INFO1_LIVE', // YouTube cookie
            'CONSENT', // Google consent
            'NID',
            'DV', // Google
            'taboola_', // Taboola
            'outbrain_', // Outbrain
            'intercom', // Intercom
            'drip_', // Drip
            'pardot', // Pardot
            'eloqua', // Eloqua
            'drift', // Drift
            'zendesk', // Zendesk
            'freshchat', // Freshchat
            'tawk', // Tawk
        ];

        // Common functional cookies
        $functional_patterns = [
            'wordpress_logged_in_',
            'wordpress_sec_', // WordPress auth
            'wp-settings-',
            'wp_', // WordPress settings
            'comment_',
            'comment-', // Comments
            'sessionid',
            'sid',
            'PHPSESSID', // Session IDs
            'token',
            'auth',
            'logged_in', // Auth tokens
            'lang',
            'language',
            'locale',
            'country', // Language/locale
            'timezone',
            'currency', // User preferences
            'font',
            'text_size', // Display preferences
            'recently_viewed',
            'recently_searched', // User history
            'shopping_cart',
            'cart_', // Shopping cart
            'wishlist',
            'favorites', // User saved items
            'cf_use_it',
            'cf_', // Contact Form
            'wfwaf-',
            'wordfence', // Wordfence
            'akm_mobile', // Mobile detection
            'gdpr',
            'ccpa', // Consent related
            'cookie_notice', // Cookie notice
            'woocommerce_',
            'wp_woocommerce_', // WooCommerce
            'jetpack', // Jetpack
            'elementor', // Elementor
            'et_', // Divi theme
            'cerber',
            'sucuri',
            'litespeed', // Security plugins
            'ninja_forms_',
            'gform_',
            'formidable', // Forms
            'wpforms',
            'fluentform', // Forms
            'theme_',
            'template_', // Theme settings
            'popup',
            'modal',
            'notification', // UI elements
            'recaptcha',
            'captcha', // CAPTCHA
        ];

        // Necessary cookies (default whitelist)
        $necessary_patterns = [
            'wp-settings-time', // WordPress core
            'devora_cookie_consent', // Our own cookie consent
            'cookie_consent',
            'cookie-consent', // Generic consent cookies
            'wordpress_test_cookie', // WP test cookie
            'wp_lang', // WordPress language
            'wc_', // WooCommerce
            'wordpress_', // WordPress session
            'gdpr_',
            'consent', // GDPR
            'cb-enabled', // Cookie banner
            'CookieConsent', // Generic consent
            'euconsent', // IAB consent
            'cookielawinfo-', // GDPR cookie consent
            'complianz', // Complianz 
            'borlabs-cookie', // Borlabs cookie
            'cc_cookie', // Cookie control
            'moove_gdpr', // GDPR Cookie Compliance
            'cookieconsent_', // Cookie consent
            'viewed_cookie_policy', // Cookie policy 
            'wordpress_sec', // WordPress security
            'wp-', // WordPress prefix
            'xf_', // XenForo 
            'csrf',
            'csrftoken', // CSRF protection
            'cf_clearance', // Cloudflare
            'CloudFront', // AWS CloudFront
            'AWSELB',
            'AWSALB', // AWS ELB
            'ARRAffinity', // Azure
            'JSESSIONID', // Java Session
            'SSESS', // Generic session
            'X-Mapping', // Load balancer
        ];

        // Check if the cookie name matches any pattern
        foreach ($analytics_patterns as $pattern) {
            if (strpos($name, $pattern) !== false) {
                return 'analytics';
            }
        }

        foreach ($marketing_patterns as $pattern) {
            if (strpos($name, $pattern) !== false) {
                return 'marketing';
            }
        }

        foreach ($functional_patterns as $pattern) {
            if (strpos($name, $pattern) !== false) {
                return 'functional';
            }
        }

        foreach ($necessary_patterns as $pattern) {
            if (strpos($name, $pattern) !== false) {
                return 'necessary';
            }
        }

        // If no pattern matches, return uncategorized
        return 'uncategorized';
    }

    /**
     * AJAX handler for scanning cookies
     */
    public function ajax_scan_cookies(): void
    {
        check_ajax_referer('cookie_management', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to scan cookies.', 'custom-cookie-consent')]);
            return;
        }

        // Get all cookies from browser
        $all_cookies = $this->get_all_browser_cookies();

        // Get existing detected cookies
        $detected_cookies = get_option('custom_cookie_detected', array());

        // Array to store new cookies
        $new_cookies = array();

        // Process each cookie
        foreach ($all_cookies as $name => $value) {
            // Skip if cookie is already tracked
            $found = false;
            foreach ($detected_cookies as $cookie) {
                if ($cookie['name'] === $name) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Auto-categorize the cookie based on name patterns
                $category = $this->categorize_cookie($name);
                $status = $category !== 'uncategorized' ? 'categorized' : 'uncategorized';

                // Add the new cookie
                $new_cookies[] = array(
                    'name' => $name,
                    'category' => $category,
                    'status' => $status,
                    'detected' => current_time('mysql'),
                    'domain' => $_SERVER['HTTP_HOST'] ?? '',
                    'description' => $this->get_cookie_description($name, $category),
                    'source' => $this->detect_cookie_source($name),
                );
            }
        }

        // Add new cookies to detected cookies
        if (!empty($new_cookies)) {
            $detected_cookies = array_merge($detected_cookies, $new_cookies);
            update_option('custom_cookie_detected', $detected_cookies);
        }

        // Return all cookies (including previously detected ones)
        wp_send_json_success([
            'cookies' => $detected_cookies,
            'new_count' => count($new_cookies)
        ]);
    }

    /**
     * Get a generic description for a cookie based on its name and category
     *
     * @param string $name Cookie name
     * @param string $category Cookie category
     * @return string Cookie description
     */
    private function get_cookie_description(string $name, string $category): string
    {
        $name_lower = strtolower($name);

        // Google Analytics cookies
        if (strpos($name_lower, '_ga') === 0) {
            return __('Google Analytics cookie used to distinguish users.', 'custom-cookie-consent');
        }

        // HubSpot cookies
        if (strpos($name_lower, '__hs') === 0 || strpos($name_lower, 'hubspot') !== false) {
            return __('HubSpot cookie used for marketing analytics.', 'custom-cookie-consent');
        }

        // WordPress cookies
        if (strpos($name_lower, 'wordpress') === 0 || strpos($name_lower, 'wp_') === 0) {
            return __('WordPress cookie used for site functionality.', 'custom-cookie-consent');
        }

        // Session cookies
        if (strpos($name_lower, 'sess') !== false || strpos($name_lower, 'sid') !== false) {
            return __('Session cookie used to maintain user session.', 'custom-cookie-consent');
        }

        // Generic descriptions based on category
        switch ($category) {
            case 'necessary':
                return __('Technical cookie necessary for the site to function.', 'custom-cookie-consent');
            case 'analytics':
                return __('Analytics cookie used to measure site usage.', 'custom-cookie-consent');
            case 'functional':
                return __('Functional cookie used to improve user experience.', 'custom-cookie-consent');
            case 'marketing':
                return __('Marketing cookie used for advertising purposes.', 'custom-cookie-consent');
            default:
                return __('Cookie with unspecified purpose.', 'custom-cookie-consent');
        }
    }

    /**
     * Attempt to detect the source of a cookie
     *
     * @param string $name Cookie name
     * @return string Cookie source
     */
    private function detect_cookie_source(string $name): string
    {
        $name_lower = strtolower($name);

        if (strpos($name_lower, '_ga') === 0 || strpos($name_lower, 'google') !== false) {
            return 'Google Analytics';
        }

        if (strpos($name_lower, 'hubspot') !== false || strpos($name_lower, '__hs') === 0) {
            return 'HubSpot';
        }

        if (strpos($name_lower, 'fb') === 0 || strpos($name_lower, 'facebook') !== false) {
            return 'Facebook';
        }

        if (strpos($name_lower, 'wordpress') !== false || strpos($name_lower, 'wp_') === 0) {
            return 'WordPress';
        }

        if (strpos($name_lower, 'devora') !== false) {
            return 'Devora';
        }

        // Default to site domain
        return $_SERVER['HTTP_HOST'] ?? 'Unknown';
    }

    /**
     * Enqueue scripts and styles for frontend
     */
    public function enqueue_scripts(): void
    {
        // Don't load scripts/styles in admin
        if (is_admin()) {
            return;
        }

        // Get settings
        $settings = get_option('custom_cookie_settings', []);

        // Force a unique version timestamp to avoid caching issues
        $version = CUSTOM_COOKIE_VERSION . '.' . time();

        // Enqueue styles
        wp_enqueue_style(
            'custom-cookie-consent',
            plugin_dir_url(__FILE__) . 'css/cookie-consent.css',
            [],
            $version
        );

        // Register banner template script first (it must be loaded before consent manager)
        wp_register_script(
            'custom-cookie-banner-template',
            plugin_dir_url(__FILE__) . 'js/banner-template.js',
            [],
            $version,
            true
        );

        // Register and enqueue individual scripts with explicit dependencies
        // Dynamic rules (can be loaded separately)
        wp_register_script(
            'custom-cookie-rules',
            plugin_dir_url(__FILE__) . 'js/dynamic-enforcer-rules.js',
            [], // No dependencies
            $version,
            true
        );

        // Main consent manager script (depends on banner template)
        if (!wp_script_is('custom-cookie-consent', 'registered')) {
            wp_register_script(
                'custom-cookie-consent',
                plugin_dir_url(__FILE__) . 'js/consent-manager.js',
                ['custom-cookie-banner-template'], // Depends on the banner template
                $version,
                true
            );
        }

        // Enqueue consent manager (this is the main script)
        wp_enqueue_script('custom-cookie-consent');

        // Now enqueue the rules (before enforcer)
        wp_enqueue_script('custom-cookie-rules');

        // Enqueue cookie enforcer (depends on consent manager and rules)
        wp_enqueue_script(
            'custom-cookie-enforcer',
            plugin_dir_url(__FILE__) . 'js/cookie-enforcer.js',
            ['custom-cookie-consent', 'custom-cookie-rules'],
            $version,
            true
        );

        // Add localization for JavaScript
        wp_localize_script('custom-cookie-consent', '_devoraCookieL10n', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cookie_management'),
            'save' => __('Save Preferences', 'custom-cookie-consent'),
            'accept_all' => __('Accept All', 'custom-cookie-consent'),
            'decline_all' => __('Decline Optional', 'custom-cookie-consent'),
            'banner_title' => isset($settings['banner_title']) ? $settings['banner_title'] : __('We use cookies', 'custom-cookie-consent'),
            'banner_description' => isset($settings['banner_description']) ? $settings['banner_description'] : __('This website uses cookies to improve your experience.', 'custom-cookie-consent'),
            'preference_title' => isset($settings['preference_title']) ? $settings['preference_title'] : __('Cookie Preferences', 'custom-cookie-consent'),
            'necessary_title' => isset($settings['necessary_title']) ? $settings['necessary_title'] : __('Necessary', 'custom-cookie-consent'),
            'necessary_description' => isset($settings['necessary_description']) ? $settings['necessary_description'] : __('Necessary cookies help make a website usable by enabling basic functions like page navigation and access to secure areas of the website. The website cannot function properly without these cookies.', 'custom-cookie-consent'),
            'analytics_title' => isset($settings['analytics_title']) ? $settings['analytics_title'] : __('Analytics', 'custom-cookie-consent'),
            'analytics_description' => isset($settings['analytics_description']) ? $settings['analytics_description'] : __('Analytics cookies help website owners understand how visitors interact with websites by collecting and reporting information anonymously.', 'custom-cookie-consent'),
            'functional_title' => isset($settings['functional_title']) ? $settings['functional_title'] : __('Functional', 'custom-cookie-consent'),
            'functional_description' => isset($settings['functional_description']) ? $settings['functional_description'] : __('Functional cookies enable the website to provide enhanced functionality and personalization. They may be set by the website or by third party providers whose services have been added to the website.', 'custom-cookie-consent'),
            'marketing_title' => isset($settings['marketing_title']) ? $settings['marketing_title'] : __('Marketing', 'custom-cookie-consent'),
            'marketing_description' => isset($settings['marketing_description']) ? $settings['marketing_description'] : __('Marketing cookies are used to track visitors across websites. The intention is to display ads that are relevant and engaging for the individual user and thereby more valuable for publishers and third party advertisers.', 'custom-cookie-consent'),
            'more_info' => isset($settings['more_info']) ? $settings['more_info'] : __('More information', 'custom-cookie-consent'),
            'privacy_policy' => isset($settings['privacy_policy']) ? $settings['privacy_policy'] : __('Privacy Policy', 'custom-cookie-consent'),
            'privacy_policy_url' => isset($settings['privacy_policy_url']) ? $settings['privacy_policy_url'] : '',
            'service_notice' => isset($settings['service_notice']) ? $settings['service_notice'] : __('This content may collect data from third party services.', 'custom-cookie-consent'),
            'service_button' => isset($settings['service_button']) ? $settings['service_button'] : __('Accept', 'custom-cookie-consent'),
            'reload_after_save' => true, // Force page reload after consent is saved
            'version' => $version,
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);

        // Add cookie consent settings 
        $settings_json = wp_json_encode([
            'banner_position' => $settings['position'] ?? 'bottom',
            'layout_type' => $settings['layout_type'] ?? 'block',
            'color_primary' => $settings['color_primary'] ?? '#2a2e64',
            'color_secondary' => $settings['color_secondary'] ?? '#ffffff',
            'color_banner_bg' => $settings['color_banner_bg'] ?? '#ffffff',
            'color_banner_text' => $settings['color_banner_text'] ?? '#333333',
            'auto_accept' => $settings['auto_accept'] ?? 'no',
            'auto_accept_days' => $settings['auto_accept_days'] ?? '30',
            'consent_expiration' => $settings['consent_expiration'] ?? '365',
            'reload_after_save' => true, // Force page reload after consent is saved 
            'privacy_policy_url' => $settings['privacy_policy_url'] ?? '',
            'categories' => [
                'necessary' => true, // Always enabled
                'analytics' => $settings['enable_analytics'] ?? 'yes',
                'functional' => $settings['enable_functional'] ?? 'yes',
                'marketing' => $settings['enable_marketing'] ?? 'yes',
            ],
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
        ]);

        wp_add_inline_script(
            'custom-cookie-consent',
            'window.cookieConsentSettings = ' . $settings_json . ';',
            'before'
        );

        // Define ajaxurl if it's not already defined (for front-end)
        wp_add_inline_script(
            'custom-cookie-consent',
            'if (typeof ajaxurl === "undefined") { var ajaxurl = "' . esc_js(admin_url('admin-ajax.php')) . '"; }',
            'before'
        );

        // Add a unique cache-busting parameter to dynamically generated scripts
        wp_add_inline_script(
            'custom-cookie-consent',
            'window.cookieConsentCacheBuster = "' . esc_js($version) . '";',
            'before'
        );

        // Add initialization script to ensure consentManager is only created once
        wp_add_inline_script(
            'custom-cookie-enforcer',
            '
            // Initialize ConsentManager only once after all scripts are loaded
            document.addEventListener("DOMContentLoaded", function() {
                // Check if consentManager already exists in global scope
                if (!window.consentManager && typeof ConsentManager === "function") {
                    window.consentManager = new ConsentManager();
                }
            });
            ',
            'after'
        );
    }
}

// Initialize the plugin
CookieConsent::get_instance();

/**
 * Fix for banner positioning issue
 */
function fix_banner_position()
{
    $settings = get_option('custom_cookie_settings', array());
    $position = 'bottom'; // Default position

    if (isset($settings['position']) && in_array($settings['position'], array('bottom', 'top', 'center'), true)) {
        $position = $settings['position'];
    } elseif (isset($settings['banner_position']) && in_array($settings['banner_position'], array('bottom', 'top', 'center'), true)) {
        $position = $settings['banner_position'];
    }

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to fix banner position
            function fixBannerPosition() {
                console.log("Fixing banner position to: <?php echo esc_js($position); ?>");
                const banner = document.querySelector('.cookie-consent-banner');
                if (banner) {
                    // Force the position attribute
                    banner.dataset.position = "<?php echo esc_js($position); ?>";

                    // Remove all position classes
                    banner.classList.remove('position-bottom', 'position-top', 'position-center');

                    // Add the correct position class
                    banner.classList.add('position-<?php echo esc_js($position); ?>');

                    // If center position, make sure overlay is created
                    if ('<?php echo esc_js($position); ?>' === 'center' && !document.querySelector('.cookie-consent-overlay')) {
                        const overlay = document.createElement('div');
                        overlay.className = 'cookie-consent-overlay';
                        document.body.appendChild(overlay);

                        // Make overlay visible
                        setTimeout(function() {
                            overlay.style.display = 'block';
                            overlay.classList.add('visible');
                        }, 10);
                    }
                }
            }

            // Run immediately
            fixBannerPosition();

            // Also run when banner is dynamically added or updated
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                        for (let i = 0; i < mutation.addedNodes.length; i++) {
                            const node = mutation.addedNodes[i];
                            if (node.classList && node.classList.contains('cookie-consent-banner')) {
                                fixBannerPosition();
                                break;
                            }
                        }
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Force banner recreation if it already exists but position is wrong
            window.setTimeout(function() {
                const banner = document.querySelector('.cookie-consent-banner');
                if (banner) {
                    const currentPosition = banner.dataset.position;
                    if (currentPosition !== "<?php echo esc_js($position); ?>") {
                        console.log("Recreating banner with correct position");
                        // Force template reset
                        if (typeof cookieConsent !== 'undefined') {
                            banner.remove();
                            if (document.querySelector('.cookie-consent-overlay')) {
                                document.querySelector('.cookie-consent-overlay').remove();
                            }
                            cookieConsent.showConsentBanner();
                            fixBannerPosition();
                        }
                    }
                }
            }, 500);
        });
    </script>
<?php
}

// Register the fix to run in the footer - using proper namespace
add_action('wp_footer', __NAMESPACE__ . '\fix_banner_position', 100);

// Add plugin row meta
add_filter('plugin_row_meta', function ($links, $file) {
    if (plugin_basename(__FILE__) !== $file) {
        return $links;
    }

    $check_updates_link = sprintf(
        '<a href="#" class="check-for-updates-link" data-plugin="%s">%s</a>',
        esc_attr(plugin_basename(__FILE__)),
        esc_html__('Check for updates', 'custom-cookie-consent')
    );

    $links[] = $check_updates_link;

    return $links;
}, 10, 2);

// Add JavaScript to handle the update check
add_action('admin_footer', function () {
    if (!current_user_can('update_plugins')) {
        return;
    }
?>
    <script>
        jQuery(document).ready(function($) {
            $('.check-for-updates-link').on('click', function(e) {
                e.preventDefault();
                var $link = $(this);
                var $row = $link.closest('tr');

                $link.text('<?php echo esc_js(__('Checking...', 'custom-cookie-consent')); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'check_plugin_updates',
                        _ajax_nonce: '<?php echo wp_create_nonce('check_plugin_updates'); ?>',
                        plugin: $link.data('plugin')
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            $link.text('<?php echo esc_js(__('Check for updates', 'custom-cookie-consent')); ?>');
                            alert('<?php echo esc_js(__('Failed to check for updates. Please try again.', 'custom-cookie-consent')); ?>');
                        }
                    },
                    error: function() {
                        $link.text('<?php echo esc_js(__('Check for updates', 'custom-cookie-consent')); ?>');
                        alert('<?php echo esc_js(__('Failed to check for updates. Please try again.', 'custom-cookie-consent')); ?>');
                    }
                });
            });
        });
    </script>
<?php
});

// Add AJAX handler for update check
add_action('wp_ajax_check_plugin_updates', function () {
    if (!current_user_can('update_plugins')) {
        wp_die(-1);
    }

    check_ajax_referer('check_plugin_updates');

    delete_site_transient('update_plugins');
    wp_update_plugins();

    wp_send_json_success();
});
