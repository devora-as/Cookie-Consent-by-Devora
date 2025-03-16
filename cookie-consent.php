<?php

declare(strict_types=1);

namespace CustomCookieConsent;

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

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin version
define('CUSTOM_COOKIE_VERSION', '1.2.0');

// Required files
require_once plugin_dir_path(__FILE__) . 'includes/class-cookie-categories.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cookie-scanner.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-integrations.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-banner-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wp-consent-wrapper.php';

/**
 * Handles the GitHub updater integration
 */
function initializeGitHubUpdater(): void
{
    // Only load updater in admin area
    if (!is_admin()) {
        return;
    }

    $updater_file = plugin_dir_path(__FILE__) . 'includes/plugin-update-checker/plugin-update-checker.php';
    if (!file_exists($updater_file)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cookie Consent: GitHub updater library not found at ' . $updater_file);
        }
        return;
    }

    require_once $updater_file;

    if (!class_exists('Puc_v4_Factory')) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cookie Consent: GitHub updater factory class not found');
        }
        return;
    }

    $myUpdateChecker = \Puc_v4_Factory::buildUpdateChecker(
        'https://github.com/devora-as/Cookie-Consent-by-Devora/',
        __FILE__,
        'custom-cookie-consent'
    );

    $myUpdateChecker->setBranch('main');
}

// Register the updater function with WordPress init hook - outside namespace
add_action('init', __NAMESPACE__ . '\\initializeGitHubUpdater');

// Register activation hook
register_activation_hook(__FILE__, __NAMESPACE__ . '\\CookieConsent::create_consent_log_table');

// Now begin the plugin namespace for the main class
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
     * @var string
     */
    private $gtm_body_tag = '';

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
        // Initialize classes with error handling
        $this->cookie_scanner = new CookieScanner();
        $this->admin_interface = new AdminInterface();
        $this->integrations = new Integrations();

        // Add error handling for BannerGenerator initialization
        try {
            $this->banner_generator = new BannerGenerator();
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log('Error initializing BannerGenerator: ' . $e->getMessage());
            }
        }

        // Get saved settings
        $this->settings = \get_option('custom_cookie_settings', []);

        // Debug log the integration settings
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('__construct() - Initializing plugin with integration settings:', [
                'wp_consent_api' => isset($this->settings['wp_consent_api']) ? $this->settings['wp_consent_api'] : false,
                'sitekit_integration' => isset($this->settings['sitekit_integration']) ? $this->settings['sitekit_integration'] : false,
                'hubspot_integration' => isset($this->settings['hubspot_integration']) ? $this->settings['hubspot_integration'] : false,
                'matomo_integration' => isset($this->settings['matomo_integration']) ? $this->settings['matomo_integration'] : false,
                'matomo_site_id' => isset($this->settings['matomo_site_id']) ? $this->settings['matomo_site_id'] : '',
                'matomo_url' => isset($this->settings['matomo_url']) ? $this->settings['matomo_url'] : '',
                'matomo_track_without_cookies' => isset($this->settings['matomo_track_without_cookies']) ? $this->settings['matomo_track_without_cookies'] : false
            ]);
        }

        // Check if consent log table exists and create it if needed
        global $wpdb;
        $table_name = $wpdb->prefix . 'cookie_consent_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            self::create_consent_log_table();
        }

        // Initialize plugin
        $this->init();

        // Register assets and output
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        \add_action('wp_head', [$this, 'output_consent_nonce']);
        \add_action('wp_footer', [$this, 'load_full_css'], 999);

        // Register AJAX handlers
        \add_action('wp_ajax_save_cookie_settings', [$this, 'ajax_save_settings']);
        \add_action('wp_ajax_save_integration_settings', [$this, 'ajax_save_integration_settings']);
        \add_action('wp_ajax_save_cookie_consent', [$this, 'ajax_save_consent']);
        \add_action('wp_ajax_nopriv_save_cookie_consent', [$this, 'ajax_save_consent']);
        // Also register with the new action name for compatibility
        \add_action('wp_ajax_custom_cookie_save_consent', [$this, 'ajax_save_consent']);
        \add_action('wp_ajax_nopriv_custom_cookie_save_consent', [$this, 'ajax_save_consent']);
        // Add AJAX endpoint for fetching consent data
        \add_action('wp_ajax_get_cookie_consent_data', [$this, 'ajax_get_consent_data']);
        \add_action('wp_ajax_nopriv_get_cookie_consent_data', [$this, 'ajax_get_consent_data']);

        // Register shortcodes
        \add_shortcode('cookie_settings', [$this, 'cookie_settings_shortcode']);
        \add_shortcode('show_my_consent_data', [$this, 'show_consent_data_shortcode']);

        // Register schema output
        \add_action('wp_head', [$this, 'output_consent_schema']);
    }

    /**
     * Initializes the plugin.
     *
     * @return void
     */
    public function init(): void
    {
        // Register hooks
        \add_action('wp_enqueue_scripts', [$this, 'register_cookies']);
        \add_action('admin_menu', [$this->admin_interface, 'add_admin_menu']);
        \add_action('admin_enqueue_scripts', [$this->admin_interface, 'enqueue_admin_assets']);
        \add_action('admin_init', [$this, 'handle_admin_actions']);
        \add_action('wp_ajax_scan_cookies', [$this->cookie_scanner, 'ajax_scan_cookies']);
        \add_action('wp_ajax_categorize_cookie', [$this->admin_interface, 'ajax_categorize_cookie']);

        // Add Site Kit filter if integration is enabled
        if (!empty($this->settings['sitekit_integration'])) {
            \add_filter('googlesitekit_consent_state', [$this, 'filter_sitekit_consent_settings']);
        }

        // Load template generator with error handling
        if (isset($this->banner_generator) && $this->banner_generator instanceof BannerGenerator && method_exists($this->banner_generator, 'init')) {
            \add_action('init', [$this->banner_generator, 'init']);
        } else {
            // Log the error if debug is enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log('Error: BannerGenerator not properly initialized or missing init method');
            }

            // Try to recover by initializing a new instance if needed
            if (!isset($this->banner_generator) || !($this->banner_generator instanceof BannerGenerator)) {
                try {
                    $this->banner_generator = new BannerGenerator();
                    if (method_exists($this->banner_generator, 'init')) {
                        \add_action('init', [$this->banner_generator, 'init']);
                    }
                } catch (\Throwable $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $this->debug_log('Failed to recover BannerGenerator: ' . $e->getMessage());
                    }
                }
            }
        }

        // Register privacy hooks - changed from wp_loaded to admin_init for privacy policy content
        \add_action('admin_init', [$this, 'add_privacy_policy_content']);
        \add_filter('wp_privacy_personal_data_exporters', [$this, 'register_privacy_exporters']);
        \add_filter('wp_privacy_personal_data_erasers', [$this, 'register_privacy_erasers']);

        // Register sitemap and robots hooks
        \add_filter('wp_sitemaps_post_types', [$this, 'exclude_from_sitemap']);
        \add_filter('robots_txt', [$this, 'modify_robots_txt']);

        // Register consent mode output - use wp_head or wp_footer based on settings
        $use_head_tag = !empty($this->settings['use_head_tag']);
        if ($use_head_tag) {
            // Add to head for better performance (recommended)
            \add_action('wp_head', [$this, 'output_consent_mode'], 1); // Priority 1 ensures it runs early
        } else {
            // Add to footer if head tag not selected
            \add_action('wp_footer', [$this, 'output_consent_mode'], 1);
        }
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
            'hubspot_integration',
            'use_head_tag',
            'use_eu_consent_regions',
            'matomo_integration',
            'matomo_track_without_cookies'
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

        // Process text fields
        $text_fields = [
            'gtm_id',
            'ga4_id',
            'matomo_site_id',
            'matomo_url'
        ];

        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                $existing_settings[$field] = sanitize_text_field($_POST[$field]);
            }
        }

        // Debug log the integration settings being saved
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('ajax_save_integration_settings() - Integration settings being saved:', [
                'wp_consent_api' => $existing_settings['wp_consent_api'] ?? false,
                'sitekit_integration' => $existing_settings['sitekit_integration'] ?? false,
                'hubspot_integration' => $existing_settings['hubspot_integration'] ?? false,
                'matomo_integration' => $existing_settings['matomo_integration'] ?? false,
                'matomo_site_id' => $existing_settings['matomo_site_id'] ?? '',
                'matomo_url' => $existing_settings['matomo_url'] ?? '',
                'matomo_track_without_cookies' => $existing_settings['matomo_track_without_cookies'] ?? false,
                'gtm_id' => $existing_settings['gtm_id'] ?? '',
                'ga4_id' => $existing_settings['ga4_id'] ?? '',
                'use_head_tag' => $existing_settings['use_head_tag'] ?? false,
                'use_eu_consent_regions' => $existing_settings['use_eu_consent_regions'] ?? false
            ]);
        }

        // Save the updated settings
        $updated = \update_option('custom_cookie_settings', $existing_settings);

        // Debug log the result
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log('ajax_save_integration_settings() - Update result:', [
                'updated' => $updated,
                'wp_consent_api' => $existing_settings['wp_consent_api'] ?? false,
                'sitekit_integration' => $existing_settings['sitekit_integration'] ?? false,
                'hubspot_integration' => $existing_settings['hubspot_integration'] ?? false,
                'matomo_integration' => $existing_settings['matomo_integration'] ?? false,
                'matomo_site_id' => $existing_settings['matomo_site_id'] ?? '',
                'matomo_url' => $existing_settings['matomo_url'] ?? '',
                'matomo_track_without_cookies' => $existing_settings['matomo_track_without_cookies'] ?? false,
                'gtm_id' => $existing_settings['gtm_id'] ?? '',
                'ga4_id' => $existing_settings['ga4_id'] ?? '',
                'use_head_tag' => $existing_settings['use_head_tag'] ?? false,
                'use_eu_consent_regions' => $existing_settings['use_eu_consent_regions'] ?? false
            ]);
        }

        if ($updated) {
            \wp_send_json_success(['message' => __('Integration settings saved successfully', 'custom-cookie-consent')]);
        } else {
            \wp_send_json_error(['message' => __('No changes made or error saving integration settings', 'custom-cookie-consent')]);
        }
    }

    /**
     * Logs debug messages if WP_DEBUG is enabled
     *
     * @param string $message The message to log
     * @param array $data Optional data to log
     * @return void
     */
    private function debug_log(string $message, array $data = []): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = sprintf(
                '[Cookie Consent Debug] [%s:%d] %s',
                basename(__FILE__),
                debug_backtrace()[0]['line'],
                $message
            );

            if (!empty($data)) {
                $log_message .= ' Data: ' . json_encode($data, JSON_PRETTY_PRINT);
            }

            error_log($log_message);
        }
    }

    /**
     * Creates the consent log table in the database
     *
     * @return void
     */
    public static function create_consent_log_table(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cookie_consent_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            consent_date datetime DEFAULT CURRENT_TIMESTAMP,
            consent_data longtext NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY consent_date (consent_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Registers cookies and their categories
     *
     * @return void
     */
    public function register_cookies(): void
    {
        try {
            // Get cookie categories from database or default settings
            $cookie_categories = get_option('custom_cookie_categories', []);

            if (empty($cookie_categories)) {
                $this->debug_log('No cookie categories found in database');
                return;
            }

            // Register each category
            foreach ($cookie_categories as $category) {
                if (!isset($category['name']) || !isset($category['cookies'])) {
                    continue;
                }

                // Process cookies in this category
                foreach ($category['cookies'] as $cookie) {
                    // Implementation for registering individual cookies
                    // This will vary based on your specific needs
                }
            }
        } catch (\Throwable $e) {
            $this->debug_log('Error in register_cookies: ' . $e->getMessage());
        }
    }

    /**
     * Add privacy policy content for cookie consent
     *
     * @return void
     */
    public function add_privacy_policy_content(): void
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = sprintf(
            '<h2>%s</h2><p>%s</p>',
            __('Cookie Consent', 'custom-cookie-consent'),
            __('This site uses cookies and similar technologies to enhance your browsing experience. You can choose to accept or decline certain categories of cookies. Essential cookies are always active as they are necessary for the website to function properly.', 'custom-cookie-consent')
        );

        // Get cookie categories
        $cookie_categories = get_option('custom_cookie_categories', []);
        if (!empty($cookie_categories)) {
            $content .= '<h3>' . __('Cookie Categories', 'custom-cookie-consent') . '</h3><ul>';
            foreach ($cookie_categories as $category) {
                if (isset($category['name']) && isset($category['description'])) {
                    $content .= sprintf(
                        '<li><strong>%s</strong>: %s</li>',
                        esc_html($category['name']),
                        esc_html($category['description'])
                    );
                }
            }
            $content .= '</ul>';
        }

        wp_add_privacy_policy_content(
            'Cookie Consent by Devora',
            wp_kses_post($content)
        );
    }

    /**
     * Modify robots.txt to exclude cookie consent pages
     *
     * @param string $output Current robots.txt content
     * @return string Modified robots.txt content
     */
    public function modify_robots_txt(string $output): string
    {
        $cookie_settings_page = isset($this->settings['cookie_policy_url']) ? esc_url($this->settings['cookie_policy_url']) : '';

        if (!empty($cookie_settings_page)) {
            $parsed_url = parse_url($cookie_settings_page);
            if (isset($parsed_url['path'])) {
                $path = $parsed_url['path'];
                $output .= "\n# Added by Cookie Consent by Devora\n";
                $output .= "Disallow: $path\n";
            }
        }

        return $output;
    }

    /**
     * Enqueue frontend scripts and styles
     *
     * @return void
     */
    public function enqueue_assets(): void
    {
        // Only load if banner should be shown
        if ($this->should_show_banner()) {
            $plugin_url = plugin_dir_url(__FILE__);
            $min_suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

            // Enqueue the main cookie consent script
            wp_enqueue_script(
                'custom-cookie-consent',
                $plugin_url . "js/cookie-consent$min_suffix.js",
                ['jquery'],
                CUSTOM_COOKIE_VERSION,
                true
            );

            // Localize the script with our data
            wp_localize_script(
                'custom-cookie-consent',
                'customCookieConsent',
                [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('custom_cookie_consent_nonce'),
                    'cookieExpiry' => 365, // Days
                    'storageKey' => $this->storageKey,
                    'integrations' => [
                        'wp_consent_api' => !empty($this->settings['wp_consent_api']),
                        'hubspot' => !empty($this->settings['hubspot_integration']),
                        'matomo' => !empty($this->settings['matomo_integration']),
                    ]
                ]
            );

            // Only enqueue main CSS if not deferred
            if (empty($this->settings['defer_css'])) {
                wp_enqueue_style(
                    'custom-cookie-consent',
                    $plugin_url . "css/cookie-consent$min_suffix.css",
                    [],
                    CUSTOM_COOKIE_VERSION
                );
            }
        }
    }

    /**
     * Check if the banner should be shown
     *
     * @return bool Whether the banner should be shown
     */
    private function should_show_banner(): bool
    {
        // Don't show in admin or when doing AJAX
        if (is_admin() || wp_doing_ajax()) {
            return false;
        }

        // Check if banner is disabled via filter
        if (apply_filters('custom_cookie_consent_disable_banner', false)) {
            return false;
        }

        return true;
    }

    /**
     * Exclude cookie consent settings page from sitemap
     *
     * @param array $post_types Array of post types
     * @return array Modified array of post types
     */
    public function exclude_from_sitemap(array $post_types): array
    {
        // If there's a dedicated cookie settings page, try to exclude it from the sitemap
        if (!empty($this->settings['cookie_policy_url'])) {
            $url = $this->settings['cookie_policy_url'];
            $post_id = url_to_postid($url);

            if ($post_id > 0) {
                $post = get_post($post_id);
                if ($post && isset($post_types[$post->post_type])) {
                    // We can't easily exclude just one post, so we'll add a filter to remove it
                    add_filter('wp_sitemaps_posts_query_args', function ($args, $post_type) use ($post_id, $post) {
                        if ($post_type === $post->post_type) {
                            if (!isset($args['post__not_in'])) {
                                $args['post__not_in'] = [];
                            }
                            $args['post__not_in'][] = $post_id;
                        }
                        return $args;
                    }, 10, 2);
                }
            }
        }

        return $post_types;
    }

    /**
     * Register privacy data exporters
     *
     * @param array $exporters Current exporters
     * @return array Modified exporters
     */
    public function register_privacy_exporters(array $exporters): array
    {
        $exporters['custom-cookie-consent'] = [
            'exporter_friendly_name' => __('Cookie Consent Data', 'custom-cookie-consent'),
            'callback' => [$this, 'privacy_exporter'],
        ];

        return $exporters;
    }

    /**
     * Privacy data exporter
     *
     * @param string $email_address User email address
     * @param int $page Page
     * @return array Export data
     */
    public function privacy_exporter(string $email_address, int $page = 1): array
    {
        $user = get_user_by('email', $email_address);
        $export_items = [];

        if ($user && $user->ID) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'cookie_consent_logs';

            $query = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d ORDER BY consent_date DESC",
                $user->ID
            );

            $logs = $wpdb->get_results($query);

            if ($logs) {
                foreach ($logs as $log) {
                    $consent_data = json_decode($log->consent_data, true);

                    $data = [];

                    // Add basic consent info
                    $data[] = [
                        'name' => __('Consent Date', 'custom-cookie-consent'),
                        'value' => $log->consent_date
                    ];

                    // Add each consent category
                    if (isset($consent_data['categories']) && is_array($consent_data['categories'])) {
                        foreach ($consent_data['categories'] as $category => $value) {
                            $data[] = [
                                'name' => sprintf(__('Consent for %s', 'custom-cookie-consent'), $category),
                                'value' => $value ? __('Accepted', 'custom-cookie-consent') : __('Declined', 'custom-cookie-consent')
                            ];
                        }
                    }

                    $export_items[] = [
                        'group_id' => 'cookie-consent',
                        'group_label' => __('Cookie Consent Data', 'custom-cookie-consent'),
                        'item_id' => 'consent-' . $log->id,
                        'data' => $data,
                    ];
                }
            }
        }

        return [
            'data' => $export_items,
            'done' => true,
        ];
    }

    /**
     * Register privacy data erasers
     *
     * @param array $erasers Current erasers
     * @return array Modified erasers
     */
    public function register_privacy_erasers(array $erasers): array
    {
        $erasers['custom-cookie-consent'] = [
            'eraser_friendly_name' => __('Cookie Consent Data', 'custom-cookie-consent'),
            'callback' => [$this, 'privacy_eraser'],
        ];

        return $erasers;
    }

    /**
     * Privacy data eraser
     *
     * @param string $email_address User email address
     * @param int $page Page
     * @return array Eraser status
     */
    public function privacy_eraser(string $email_address, int $page = 1): array
    {
        $user = get_user_by('email', $email_address);
        $items_removed = false;

        if ($user && $user->ID) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'cookie_consent_logs';

            // Delete consent logs for this user
            $deleted = $wpdb->delete(
                $table_name,
                ['user_id' => $user->ID],
                ['%d']
            );

            $items_removed = ($deleted > 0);
        }

        return [
            'items_removed' => $items_removed,
            'items_retained' => false,
            'messages' => [],
            'done' => true,
        ];
    }

    /**
     * Output the Google Consent Mode v2 snippet
     *
     * @return void
     */
    public function output_consent_mode(): void
    {
        // Don't output in admin or when doing AJAX
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        // Check if Google Tag Manager ID is set
        $gtm_id = !empty($this->settings['gtm_id']) ? sanitize_text_field($this->settings['gtm_id']) : '';
        $ga4_id = !empty($this->settings['ga4_id']) ? sanitize_text_field($this->settings['ga4_id']) : '';

        // Only proceed if we have at least one ID
        if (empty($gtm_id) && empty($ga4_id)) {
            return;
        }

        // Get consent status from cookies (default to 'denied' for safety)
        $analytics_consent = isset($_COOKIE[$this->storageKey . '_analytics']) && $_COOKIE[$this->storageKey . '_analytics'] === 'true';
        $marketing_consent = isset($_COOKIE[$this->storageKey . '_marketing']) && $_COOKIE[$this->storageKey . '_marketing'] === 'true';
        $functional_consent = isset($_COOKIE[$this->storageKey . '_functional']) && $_COOKIE[$this->storageKey . '_functional'] === 'true';

        // Determine region setting for EU-only mode
        $use_eu_only = !empty($this->settings['use_eu_consent_regions']);
        $region_setting = $use_eu_only ? "'region': 'eu'," : '';

        // Output the consent mode code
        echo "<!-- Google Consent Mode v2 by Custom Cookie Consent by Devora -->\n";
        echo "<script>\n";
        echo "window.dataLayer = window.dataLayer || [];\n";
        echo "function gtag(){dataLayer.push(arguments);}\n";
        echo "gtag('consent', 'default', {\n";
        echo "  'analytics_storage': '" . ($analytics_consent ? 'granted' : 'denied') . "',\n";
        echo "  'ad_storage': '" . ($marketing_consent ? 'granted' : 'denied') . "',\n";
        echo "  'functionality_storage': '" . ($functional_consent ? 'granted' : 'denied') . "',\n";
        echo "  'personalization_storage': '" . ($functional_consent ? 'granted' : 'denied') . "',\n";
        echo "  'security_storage': 'granted',\n";
        echo "  $region_setting\n";
        echo "  'wait_for_update': 500\n";
        echo "});\n";

        // Add GTM if provided
        if (!empty($gtm_id)) {
            echo "// Google Tag Manager\n";
            echo "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\n";
            echo "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n";
            echo "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n";
            echo "'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n";
            echo "})(window,document,'script','dataLayer','" . esc_js($gtm_id) . "');\n";

            // Save the body tag for later output, if needed
            $this->gtm_body_tag = "<!-- Google Tag Manager (noscript) -->\n";
            $this->gtm_body_tag .= "<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=" . esc_attr($gtm_id) . "\" height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n";
            $this->gtm_body_tag .= "<!-- End Google Tag Manager (noscript) -->";

            // Add action to output the body tag if not already added
            if (!has_action('wp_body_open', [$this, 'output_gtm_body_tag'])) {
                add_action('wp_body_open', [$this, 'output_gtm_body_tag']);
            }
        }

        // Add GA4 if provided
        if (!empty($ga4_id)) {
            echo "// Google Analytics 4\n";
            echo "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\n";
            echo "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n";
            echo "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n";
            echo "'https://www.googletagmanager.com/gtag/js?id='+i+dl;f.parentNode.insertBefore(j,f);\n";
            echo "})(window,document,'script','dataLayer','" . esc_js($ga4_id) . "');\n";
            echo "window.dataLayer = window.dataLayer || [];\n";
            echo "function gtag(){dataLayer.push(arguments);}\n";
            echo "gtag('js', new Date());\n";
            echo "gtag('config', '" . esc_js($ga4_id) . "', { 'anonymize_ip': true });\n";
        }

        echo "</script>\n";
        echo "<!-- End Google Consent Mode v2 -->\n";
    }

    /**
     * Output the GTM body tag
     * 
     * @return void
     */
    public function output_gtm_body_tag(): void
    {
        if (!empty($this->gtm_body_tag)) {
            echo $this->gtm_body_tag;
        }
    }

    /**
     * Filter Site Kit consent settings based on user consent
     *
     * @param array $consent_state Current consent state
     * @return array Modified consent state
     */
    public function filter_sitekit_consent_settings(array $consent_state): array
    {
        // Get consent status from cookies (default to false for safety)
        $analytics_consent = isset($_COOKIE[$this->storageKey . '_analytics']) && $_COOKIE[$this->storageKey . '_analytics'] === 'true';
        $marketing_consent = isset($_COOKIE[$this->storageKey . '_marketing']) && $_COOKIE[$this->storageKey . '_marketing'] === 'true';

        // Override Site Kit consent state
        $consent_state['analytics_storage'] = $analytics_consent ? 'granted' : 'denied';
        $consent_state['ad_storage'] = $marketing_consent ? 'granted' : 'denied';

        return $consent_state;
    }

    /**
     * Output consent nonce for AJAX operations
     *
     * @return void
     */
    public function output_consent_nonce(): void
    {
        echo "<meta name='custom-cookie-consent-nonce' content='" . wp_create_nonce('custom_cookie_consent_nonce') . "'>\n";
    }

    /**
     * Load full CSS if deferred
     *
     * @return void
     */
    public function load_full_css(): void
    {
        // Only load if banner should be shown and CSS is deferred
        if ($this->should_show_banner() && !empty($this->settings['defer_css'])) {
            $plugin_url = plugin_dir_url(__FILE__);
            $min_suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

            echo '<style id="custom-cookie-consent-css">';
            $css_file = plugin_dir_path(__FILE__) . "css/cookie-consent$min_suffix.css";
            if (file_exists($css_file)) {
                echo file_get_contents($css_file);
            }
            echo '</style>';
        }
    }

    /**
     * AJAX handler for saving consent
     *
     * @return void
     */
    public function ajax_save_consent(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'custom_cookie_consent_nonce')) {
            wp_send_json_error(['message' => __('Invalid security token', 'custom-cookie-consent')]);
            return;
        }

        // Get consent data
        $consent_data = [];
        if (isset($_POST['consent']) && is_array($_POST['consent'])) {
            foreach ($_POST['consent'] as $key => $value) {
                $consent_data[sanitize_key($key)] = (bool)$value;
            }
        }

        // Record consent in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'cookie_consent_logs';

        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        $data = [
            'user_id' => $user_id > 0 ? $user_id : null,
            'consent_date' => current_time('mysql'),
            'consent_data' => json_encode([
                'categories' => $consent_data,
                'timestamp' => time()
            ]),
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        ];

        $formats = ['%d', '%s', '%s', '%s', '%s'];

        $result = $wpdb->insert($table_name, $data, $formats);

        if ($result) {
            // Also update WP Consent API if enabled
            if (!empty($this->settings['wp_consent_api']) && function_exists('wp_set_consent')) {
                if (isset($consent_data['analytics'])) {
                    wp_set_consent('statistics', $consent_data['analytics'] ? 'allow' : 'deny');
                }
                if (isset($consent_data['marketing'])) {
                    wp_set_consent('marketing', $consent_data['marketing'] ? 'allow' : 'deny');
                }
                if (isset($consent_data['functional'])) {
                    wp_set_consent('preferences', $consent_data['functional'] ? 'allow' : 'deny');
                }
            }

            wp_send_json_success(['message' => __('Consent saved successfully', 'custom-cookie-consent')]);
        } else {
            wp_send_json_error(['message' => __('Error saving consent', 'custom-cookie-consent')]);
        }
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip(): string
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        // Anonymize IP by removing last octet
        $ip_parts = explode('.', $ip);
        if (count($ip_parts) === 4) {
            $ip_parts[3] = '0';
            $ip = implode('.', $ip_parts);
        }

        return $ip;
    }

    /**
     * AJAX handler for getting consent data
     *
     * @return void
     */
    public function ajax_get_consent_data(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'custom_cookie_consent_nonce')) {
            wp_send_json_error(['message' => __('Invalid security token', 'custom-cookie-consent')]);
            return;
        }

        // Get cookie categories
        $cookie_categories = get_option('custom_cookie_categories', []);

        // Format the data for display
        $formatted_categories = [];
        foreach ($cookie_categories as $category) {
            if (isset($category['name']) && isset($category['cookies'])) {
                $category_id = sanitize_title($category['name']);
                $formatted_categories[$category_id] = [
                    'name' => $category['name'],
                    'description' => $category['description'] ?? '',
                    'cookies' => $category['cookies']
                ];
            }
        }

        // Get user's current consent
        $user_id = get_current_user_id();
        $consent = [];

        if ($user_id > 0) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'cookie_consent_logs';

            $query = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d ORDER BY consent_date DESC LIMIT 1",
                $user_id
            );

            $log = $wpdb->get_row($query);

            if ($log) {
                $consent_data = json_decode($log->consent_data, true);
                if (isset($consent_data['categories']) && is_array($consent_data['categories'])) {
                    $consent = $consent_data['categories'];
                }
            }
        }

        wp_send_json_success([
            'categories' => $formatted_categories,
            'consent' => $consent
        ]);
    }

    /**
     * Shortcode to display cookie settings form
     *
     * @param array $atts Shortcode attributes
     * @return string Settings form HTML
     */
    public function cookie_settings_shortcode(array $atts = []): string
    {
        // Parse attributes
        $atts = shortcode_atts([
            'title' => __('Cookie Settings', 'custom-cookie-consent'),
            'show_title' => 'yes',
            'button_text' => __('Save Settings', 'custom-cookie-consent')
        ], $atts);

        // Get cookie categories
        $cookie_categories = get_option('custom_cookie_categories', []);

        ob_start();

        // Output title if enabled
        if ($atts['show_title'] === 'yes') {
            echo '<h2>' . esc_html($atts['title']) . '</h2>';
        }

        echo '<div class="custom-cookie-settings-form">';
        echo '<form id="custom-cookie-settings-form">';

        // Add nonce field
        wp_nonce_field('custom_cookie_consent_nonce', 'cookie_consent_nonce');

        // Output categories
        foreach ($cookie_categories as $category) {
            if (!isset($category['name'])) {
                continue;
            }

            $category_id = sanitize_title($category['name']);
            $is_necessary = strtolower($category['name']) === 'necessary' || strtolower($category['name']) === 'essential';

            echo '<div class="cookie-category">';
            echo '<div class="cookie-category-header">';
            echo '<label>';
            echo '<input type="checkbox" name="consent[' . esc_attr($category_id) . ']" ' . ($is_necessary ? 'checked disabled' : '') . '>';
            echo esc_html($category['name']);
            echo '</label>';
            echo '</div>';

            if (isset($category['description'])) {
                echo '<div class="cookie-category-description">';
                echo esc_html($category['description']);
                echo '</div>';
            }

            echo '</div>';
        }

        // Submit button
        echo '<div class="cookie-submit">';
        echo '<button type="submit" class="button">' . esc_html($atts['button_text']) . '</button>';
        echo '</div>';

        echo '</form>';
        echo '</div>';

        // Add inline JavaScript
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var form = document.getElementById("custom-cookie-settings-form");
                if (form) {
                    form.addEventListener("submit", function(e) {
                        e.preventDefault();
                        
                        var formData = new FormData(form);
                        formData.append("action", "save_cookie_consent");
                        formData.append("nonce", document.querySelector(\'[name="cookie_consent_nonce"]\').value);
                        
                        fetch(ajaxurl, {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert(data.data.message || "Error saving settings");
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                        });
                    });
                }
            });
        </script>';

        return ob_get_clean();
    }

    /**
     * Shortcode to display user's consent data
     *
     * @param array $atts Shortcode attributes
     * @return string User consent data HTML
     */
    public function show_consent_data_shortcode(array $atts = []): string
    {
        // Parse attributes
        $atts = shortcode_atts([
            'title' => __('Your Cookie Consent Choices', 'custom-cookie-consent'),
            'show_title' => 'yes'
        ], $atts);

        // Get user ID
        $user_id = get_current_user_id();

        ob_start();

        // Output title if enabled
        if ($atts['show_title'] === 'yes') {
            echo '<h2>' . esc_html($atts['title']) . '</h2>';
        }

        // If user is not logged in
        if ($user_id === 0) {
            echo '<p>' . esc_html__('You must be logged in to view your consent data.', 'custom-cookie-consent') . '</p>';
            return ob_get_clean();
        }

        // Get user's consent data
        global $wpdb;
        $table_name = $wpdb->prefix . 'cookie_consent_logs';

        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY consent_date DESC LIMIT 1",
            $user_id
        );

        $log = $wpdb->get_row($query);

        // If no consent data found
        if (!$log) {
            echo '<p>' . esc_html__('No consent data found.', 'custom-cookie-consent') . '</p>';
            return ob_get_clean();
        }

        // Display consent data
        $consent_data = json_decode($log->consent_data, true);
        $consent_date = $log->consent_date;

        echo '<div class="user-consent-data">';
        echo '<p><strong>' . esc_html__('Last updated:', 'custom-cookie-consent') . '</strong> ' . esc_html($consent_date) . '</p>';

        if (isset($consent_data['categories']) && is_array($consent_data['categories'])) {
            echo '<table class="consent-table">';
            echo '<tr><th>' . esc_html__('Category', 'custom-cookie-consent') . '</th><th>' . esc_html__('Status', 'custom-cookie-consent') . '</th></tr>';

            foreach ($consent_data['categories'] as $category => $value) {
                echo '<tr>';
                echo '<td>' . esc_html(ucfirst($category)) . '</td>';
                echo '<td>' . ($value ? esc_html__('Accepted', 'custom-cookie-consent') : esc_html__('Declined', 'custom-cookie-consent')) . '</td>';
                echo '</tr>';
            }

            echo '</table>';
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Output structured data schema for cookie consent
     *
     * @return void
     */
    public function output_consent_schema(): void
    {
        // Get cookie categories
        $cookie_categories = get_option('custom_cookie_categories', []);

        if (empty($cookie_categories)) {
            return;
        }

        // Build schema JSON
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'cookieConsent' => [
                '@type' => 'CookieConsent',
                'cookiePurposes' => []
            ]
        ];

        // Add cookie purposes based on categories
        foreach ($cookie_categories as $category) {
            if (!isset($category['name']) || !isset($category['description'])) {
                continue;
            }

            $schema['cookieConsent']['cookiePurposes'][] = [
                '@type' => 'CookiePurpose',
                'purpose' => $category['name'],
                'description' => $category['description']
            ];
        }

        // Output schema JSON
        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
    }
}

// Initialize the plugin
$cookie_consent = CookieConsent::get_instance();
