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
require_once plugin_dir_path(__FILE__) . 'includes/class-bannergenerator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wp-consent-wrapper.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-github-updater.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-consent-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-open-cookie-database.php';

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
     * @var OpenCookieDatabase
     */
    private $open_cookie_db;

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
        // Initialize classes
        $this->cookie_scanner   = new CookieScanner();
        $this->admin_interface  = AdminInterface::get_instance(); // Use singleton
        $this->integrations     = new Integrations();
        $this->banner_generator = new BannerGenerator();

        // Initialize Open Cookie Database integration
        $this->open_cookie_db = new OpenCookieDatabase();

        // Initialize GitHub updater
        if (class_exists('\\CustomCookieConsent\\GitHubUpdater')) {
            GitHubUpdater::init(__FILE__);
        }

        // Get saved settings
        $this->settings = get_option('custom_cookie_settings', array());

        // Debug log the integration settings
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log(
                '__construct() - Initializing plugin with integration settings:',
                array(
                    'wp_consent_api'      => isset($this->settings['wp_consent_api']) ? $this->settings['wp_consent_api'] : false,
                    'sitekit_integration' => isset($this->settings['sitekit_integration']) ? $this->settings['sitekit_integration'] : false,
                    'hubspot_integration' => isset($this->settings['hubspot_integration']) ? $this->settings['hubspot_integration'] : false,
                )
            );
        }

        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Check if database needs updating
        $this->maybe_update_db_schema();

        // Add action to init method to register all hooks
        add_action('init', array($this, 'init'));
    }

    /**
     * Initializes the plugin.
     *
     * @return void
     */
    public function init(): void
    {
        // Register hooks for frontend
        add_action('wp_head', array($this, 'add_banner_position_css'));
        add_action('wp_head', array($this, 'output_consent_mode'));
        add_action('wp_head', array($this, 'output_consent_nonce'));
        add_action('wp_head', array($this, 'output_early_anti_blocker'), 1); // High priority to load early
        add_action('wp_footer', array($this, 'load_full_css'), 999);

        // Add GTM noscript tag if enabled
        add_action('wp_body_open', array($this, 'output_gtm_noscript'));

        // Register REST routes
        add_action('rest_api_init', array($this, 'register_routes'));

        // Register shortcodes
        add_shortcode('cookie_settings', array($this, 'cookie_settings_shortcode'));
        add_shortcode('consent_data', array($this, 'show_consent_data_shortcode'));

        // Register AJAX handlers
        add_action('wp_ajax_custom_cookie_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_custom_cookie_save_integration_settings', array($this, 'ajax_save_integration_settings'));
        add_action('wp_ajax_nopriv_custom_cookie_save_consent', array($this, 'ajax_save_consent'));
        add_action('wp_ajax_custom_cookie_save_consent', array($this, 'ajax_save_consent'));
        add_action('wp_ajax_nopriv_custom_cookie_get_consent', array($this, 'ajax_get_consent_data'));
        add_action('wp_ajax_custom_cookie_get_consent', array($this, 'ajax_get_consent_data'));

        // Register assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', array($this, 'handle_admin_actions'));
        }

        // WP Core integrations
        add_filter('googlesitekit_consent_settings', array($this, 'filter_sitekit_consent_settings'));
        add_filter('wp_sitemaps_post_types', array($this, 'exclude_from_sitemap'));
        add_filter('robots_txt', array($this, 'modify_robots_txt'), 10, 1);

        // Privacy integrations
        add_action('admin_init', array($this, 'add_privacy_policy_content'));
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_privacy_exporters'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_privacy_erasers'));

        // Register custom cookies
        $this->register_cookies();
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
     * Gets the user consent data for display.
     *
     * @return array
     */
    public function get_user_consent_data(): array
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('Starting consent data collection');
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
            'cookies_blocked' => array(),
        );

        // Get stored consent from cookie or localStorage (this is the source of truth)
        $stored_consent = $this->get_stored_consent();

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('Stored consent data:', $stored_consent);
        }

        // If we have stored consent from the client, use that as the primary source
        if ($stored_consent && isset($stored_consent['categories'])) {
            foreach ($stored_consent['categories'] as $category => $status) {
                if (isset($consent_data['consent_status'][$category])) {
                    $consent_data['consent_status'][$category] = filter_var($status, FILTER_VALIDATE_BOOLEAN);

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log(
                            'Updated consent status from client cookie:',
                            array(
                                'category' => $category,
                                'status'   => $consent_data['consent_status'][$category],
                            )
                        );
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
                        $consent_data['consent_status'][$category] = filter_var($status, FILTER_VALIDATE_BOOLEAN);

                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            $this->debug_log(
                                'Updated consent status from user meta:',
                                array(
                                    'category' => $category,
                                    'status'   => $consent_data['consent_status'][$category],
                                )
                            );
                        }
                    }
                }
            }
        }

        // Also check WP Consent API if enabled and available
        if (! empty($this->settings['wp_consent_api']) && WPConsentWrapper::is_consent_api_active()) {
            // Check consent status for each category from the WP Consent API
            $categories = array('necessary', 'analytics', 'functional', 'marketing');

            foreach ($categories as $category) {
                $has_consent                                 = WPConsentWrapper::has_consent($category);
                $consent_data['consent_status'][$category] = $has_consent;

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log("WP Consent API status for $category:", $has_consent);
                }
            }
        }

        // Get all cookie data and categorize them
        $all_cookies      = $this->get_all_browser_cookies();
        $detected_cookies = get_option('custom_cookie_detected', array());

        // Add all cookies to cookies_present
        foreach ($all_cookies as $name => $value) {
            // Check if this cookie exists in detected cookies
            $category    = 'necessary'; // Default category if not found
            $description = '';
            $expiry      = '';
            $source      = '';

            // Look for this cookie in the detected cookies
            foreach ($detected_cookies as $detected) {
                if ($detected['name'] === $name && $detected['status'] === 'categorized') {
                    $category    = $detected['category'];
                    $description = $detected['description'] ?? '';
                    $expiry      = isset($detected['expires']) ? $detected['expires'] : '';
                    $source      = isset($detected['source']) ? $detected['source'] : '';
                    break;
                }
            }

            // Add to cookies_present array
            $consent_data['cookies_present'][] = array(
                'name'        => $name,
                'category'    => $category,
                'description' => $description,
                'expiry'      => $expiry,
                'source'      => $source,
            );

            // If this cookie is in a non-necessary category and that category is not consented to,
            // add it to the cookies_blocked array
            if ($category !== 'necessary' && ! $consent_data['consent_status'][$category]) {
                $consent_data['cookies_blocked'][] = $name;
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log(
                'Final consent data:',
                array(
                    'consent_status' => $consent_data['consent_status'],
                    'cookies_count'  => count($consent_data['cookies_present']),
                    'blocked_count'  => count($consent_data['cookies_blocked']),
                )
            );
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
        $cookies = array();

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
            $this->debug_log('Starting consent retrieval');
        }

        // First try: Direct $_COOKIE access
        if (isset($_COOKIE[$this->storageKey])) {
            $cookie_value = sanitize_text_field(wp_unslash($_COOKIE[$this->storageKey]));

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log('Found cookie in $_COOKIE array', $cookie_value);
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
                $name  = trim($parts[0]);

                if ($name === $this->storageKey && isset($parts[1])) {
                    $value = trim($parts[1]);

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log('Found cookie in HTTP_COOKIE', $value);
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
                $value_part    = explode(';', $cookie_string)[0];
                $value         = substr($value_part, strlen($this->storageKey . '='));

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $this->debug_log('Found cookie in headers', $value);
                }

                $result = $this->parse_consent_value($value);
                if ($result) {
                    return $result;
                }
            }
        }

        // Last resort: Check localStorage via JavaScript
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('Cookie not found in any server sources, will check localStorage via JS');
        }

        // Add a fallback to empty consent with only necessary cookies
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('Using fallback consent (necessary only)');
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
     * Show consent data shortcode.
     *
     * Usage: [show_my_consent_data]
     *
     * @return string
     */
    public function show_consent_data_shortcode(): string
    {
        $settings = get_option('custom_cookie_settings', array());

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
        // Verify nonce if available
        $nonce_verified = false;

        if (isset($_POST['nonce'])) {
            $nonce          = sanitize_text_field($_POST['nonce']);
            $nonce_verified = wp_verify_nonce($nonce, 'cookie_management');
        }

        // Allow saving consent even without a nonce for frontend users
        if (! $nonce_verified && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cookie Consent: Nonce verification failed for consent save, but proceeding anyway for frontend users');
        }

        // Get the consent data
        $consent_data = null;
        if (isset($_POST['consent_data'])) {
            $consent_data = json_decode(wp_unslash($_POST['consent_data']), true);
        }

        if (! $consent_data || ! isset($consent_data['categories'])) {
            wp_send_json_error(array('message' => __('Invalid consent data', 'custom-cookie-consent')));
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
        if (! empty($this->settings['wp_consent_api'])) {
            // Register the consent for each category
            foreach ($consent_data['categories'] as $category => $status) {
                WPConsentWrapper::set_consent($category, (bool) $status);
            }

            // Debug WP Consent API integration
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log('Updated WP Consent API with consent data');
            }
        }

        // Save the consent status to a site option for reference
        // This helps track overall consent rates
        $consent_stats = get_option(
            'custom_cookie_consent_stats',
            array(
                'total'               => 0,
                'analytics_accepted'  => 0,
                'functional_accepted' => 0,
                'last_updated'        => time(),
            )
        );

        $consent_stats['total']++;
        if (! empty($consent_data['categories']['analytics'])) {
            $consent_stats['analytics_accepted']++;
        }
        if (! empty($consent_data['categories']['functional'])) {
            $consent_stats['functional_accepted']++;
        }
        $consent_stats['last_updated'] = time();

        update_option('custom_cookie_consent_stats', $consent_stats);

        // Success response
        wp_send_json_success(
            array(
                'message' => __('Consent preferences saved', 'custom-cookie-consent'),
                'data'    => $consent_data,
            )
        );
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
            $nonce          = sanitize_text_field($_POST['nonce']);
            $nonce_verified = wp_verify_nonce($nonce, 'cookie_management');
        }

        // Allow fetching consent data even without a nonce for frontend users
        if (! $nonce_verified && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cookie Consent: Nonce verification failed for consent data fetch, but proceeding anyway for frontend users');
        }

        // Get consent data
        $consent_data = $this->get_user_consent_data();

        // Debug log the fetched consent data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('Fetched consent data:', $consent_data);
        }

        // Success response
        wp_send_json_success(
            array(
                'data' => $consent_data,
            )
        );
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
