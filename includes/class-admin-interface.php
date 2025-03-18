<?php

/**
 * Admin Interface Class
 *
 * Handles the WordPress admin interface for cookie management.
 *
 * @package CustomCookieConsent
 */

namespace CustomCookieConsent;

class AdminInterface
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_categorize_cookie', [$this, 'ajax_categorize_cookie']);
        add_action('wp_ajax_bulk_categorize_cookies', [$this, 'ajax_bulk_categorize']);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __('Cookie Consent by Devora', 'custom-cookie-consent'),
            __('Cookie Consent', 'custom-cookie-consent'),
            'manage_options',
            'custom-cookie-consent',
            [$this, 'render_main_page'],
            'dashicons-privacy',
            80
        );

        add_submenu_page(
            'custom-cookie-consent',
            __('Cookie Scanner', 'custom-cookie-consent'),
            __('Cookie Scanner', 'custom-cookie-consent'),
            'manage_options',
            'custom-cookie-scanner',
            [$this, 'render_scanner_page']
        );

        add_submenu_page(
            'custom-cookie-consent',
            __('Cookie Settings', 'custom-cookie-consent'),
            __('Settings', 'custom-cookie-consent'),
            'manage_options',
            'custom-cookie-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'custom-cookie-consent',
            __('Text & Translations', 'custom-cookie-consent'),
            __('Text & Translations', 'custom-cookie-consent'),
            'manage_options',
            'custom-cookie-translations',
            [$this, 'render_translations_page']
        );

        add_submenu_page(
            'custom-cookie-consent',
            __('Analytics & Statistics', 'custom-cookie-consent'),
            __('Analytics & Statistics', 'custom-cookie-consent'),
            'manage_options',
            'custom-cookie-analytics',
            [$this, 'render_analytics_page']
        );
    }

    public function enqueue_admin_assets($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'custom-cookie') === false) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'custom-cookie-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin-style.css',
            [],
            defined('WP_DEBUG') && WP_DEBUG ? time() : CUSTOM_COOKIE_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'custom-cookie-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/admin-script.js',
            ['jquery'],
            defined('WP_DEBUG') && WP_DEBUG ? time() : CUSTOM_COOKIE_VERSION,
            true
        );

        // Localize script with settings and translations
        wp_localize_script('custom-cookie-admin-js', 'customCookieAdminSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cookie_management'),
            'scanNonce' => wp_create_nonce('cookie_scan'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'messages' => [
                /* translators: Message shown when cookie scan completes successfully */
                'scanComplete' => esc_html__('Cookie scan completed successfully', 'custom-cookie-consent'),
                /* translators: Error message shown when cookie scan fails */
                'scanError' => esc_html__('Error scanning cookies', 'custom-cookie-consent'),
                /* translators: Message shown when settings are saved successfully */
                'settingsSaved' => esc_html__('Settings saved successfully', 'custom-cookie-consent'),
                /* translators: Message shown when integration settings are saved successfully */
                'integrationSaved' => esc_html__('Integration settings saved successfully', 'custom-cookie-consent'),
                /* translators: Message shown when scanner settings are saved successfully */
                'scannerSaved' => esc_html__('Scanner settings saved successfully', 'custom-cookie-consent'),
                /* translators: Message shown when a cookie is categorized successfully */
                'cookieCategorized' => esc_html__('Cookie categorized successfully', 'custom-cookie-consent'),
                /* translators: Message shown when multiple cookies are categorized successfully */
                'bulkCategorized' => esc_html__('Cookies categorized successfully', 'custom-cookie-consent')
            ]
        ]);

        // Load Chart.js for analytics page
        if ($hook === 'cookie-consent_page_custom-cookie-analytics') {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                [],
                '4.4.1',
                true
            );
        }
    }

    public function render_main_page()
    {
        // Main dashboard
        $detected = get_option('custom_cookie_detected', []);
        $categories = CookieCategories::get_categories();

        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/dashboard.php';
    }

    public function render_scanner_page()
    {
        // Cookie scanner interface
        $last_scan = get_option('custom_cookie_last_scan');
        $detected = get_option('custom_cookie_detected', []);
        $uncategorized_count = count(array_filter($detected, function ($cookie) {
            return $cookie['status'] === 'uncategorized';
        }));

        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/scanner.php';
    }

    public function render_settings_page()
    {
        // Plugin settings
        $default_settings = [
            'banner_title' => 'Vi bruker informasjonskapsler (cookies)',
            'banner_text' => 'Vi bruker informasjonskapsler for å forbedre brukeropplevelsen...',
            'accept_button' => 'Godta alle',
            'decline_button' => 'Avslå alle',
            'save_button' => 'Lagre preferanser',
            'position' => 'bottom',
            'auto_scan' => true,
            'scan_frequency' => 'daily',
            // Integration settings with explicit false defaults
            'wp_consent_api' => false,
            'sitekit_integration' => false,
            'hubspot_integration' => false
        ];

        $saved_settings = get_option('custom_cookie_settings', []);

        // Ensure integration settings are explicitly boolean values
        foreach (['wp_consent_api', 'sitekit_integration', 'hubspot_integration'] as $key) {
            if (isset($saved_settings[$key])) {
                // Convert to explicit boolean
                $saved_settings[$key] = (bool)$saved_settings[$key];
            }
        }

        $settings = array_merge($default_settings, $saved_settings);

        // Debug the settings being displayed
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Admin Interface - Displaying settings: ' . print_r([
                'wp_consent_api' => $settings['wp_consent_api'],
                'sitekit_integration' => $settings['sitekit_integration'],
                'hubspot_integration' => $settings['hubspot_integration']
            ], true));
        }

        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/settings.php';
    }

    public function render_translations_page()
    {
        // Text and translation settings
        $settings = get_option('custom_cookie_settings', []);

        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/translations.php';
    }

    /**
     * Render the analytics page
     */
    public function render_analytics_page()
    {
        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/analytics.php';
    }

    public function ajax_categorize_cookie()
    {
        // Verify nonce
        check_ajax_referer('cookie_management', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied', 'custom-cookie-consent')]);
            return;
        }

        // Get and sanitize parameters
        $cookie_name = isset($_POST['cookie']) ? sanitize_text_field(wp_unslash($_POST['cookie'])) : '';
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';

        if (empty($cookie_name) || empty($category)) {
            wp_send_json_error(['message' => esc_html__('Missing required parameters', 'custom-cookie-consent')]);
            return;
        }

        // Get detected cookies
        $detected = get_option('custom_cookie_detected', []);

        // Update the cookie category
        if (isset($detected[$cookie_name])) {
            $detected[$cookie_name]['category'] = $category;
            $detected[$cookie_name]['status'] = 'categorized';
            $detected[$cookie_name]['last_updated'] = current_time('mysql');

            // Save the updated cookie data
            update_option('custom_cookie_detected', $detected);

            // Regenerate enforcer rules
            $this->regenerate_enforcer_rules();

            wp_send_json_success([
                'message' => sprintf(
                    /* translators: %s: Cookie name */
                    esc_html__('Cookie "%s" categorized successfully', 'custom-cookie-consent'),
                    esc_html($cookie_name)
                )
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('Cookie not found', 'custom-cookie-consent')]);
        }
    }

    public function ajax_bulk_categorize()
    {
        // Verify nonce
        check_ajax_referer('cookie_management', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied', 'custom-cookie-consent')]);
            return;
        }

        // Get and sanitize parameters
        $cookies_raw = isset($_POST['cookies']) ? sanitize_text_field(wp_unslash($_POST['cookies'])) : '';
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';

        if (empty($cookies_raw) || empty($category)) {
            wp_send_json_error(['message' => esc_html__('Missing required parameters', 'custom-cookie-consent')]);
            return;
        }

        // Parse cookie names
        $cookies = json_decode($cookies_raw);
        if (!is_array($cookies) || empty($cookies)) {
            wp_send_json_error(['message' => esc_html__('Invalid cookie data', 'custom-cookie-consent')]);
            return;
        }

        // Get detected cookies
        $detected = get_option('custom_cookie_detected', []);
        $updated_count = 0;

        // Update each cookie
        foreach ($cookies as $cookie_name) {
            $cookie_name = sanitize_text_field($cookie_name);
            if (isset($detected[$cookie_name])) {
                $detected[$cookie_name]['category'] = $category;
                $detected[$cookie_name]['status'] = 'categorized';
                $detected[$cookie_name]['last_updated'] = current_time('mysql');
                $updated_count++;
            }
        }

        if ($updated_count > 0) {
            // Save the updated cookie data
            update_option('custom_cookie_detected', $detected);

            // Regenerate enforcer rules
            $this->regenerate_enforcer_rules();

            wp_send_json_success([
                'message' => sprintf(
                    /* translators: %d: Number of cookies */
                    esc_html__('%d cookies categorized successfully', 'custom-cookie-consent'),
                    $updated_count
                )
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('No cookies were updated', 'custom-cookie-consent')]);
        }
    }

    private function regenerate_enforcer_rules()
    {
        // Generate dynamic enforcer JavaScript
        $detected = get_option('custom_cookie_detected', []);
        $categorized = [];

        foreach ($detected as $cookie) {
            if ($cookie['status'] === 'categorized') {
                $categorized[$cookie['category'] ?? 'unrecognized'][] = $cookie;
            }
        }

        // Update dynamic rules file
        $rules_content = $this->generate_enforcer_rules($categorized);

        // Create directory if it doesn't exist
        $dir_path = plugin_dir_path(dirname(__FILE__)) . 'js';
        if (!file_exists($dir_path)) {
            if (!wp_mkdir_p($dir_path)) {
                // Log error if directory creation fails
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Failed to create directory: ' . esc_html($dir_path));
                }
                return false;
            }
        }

        // Check if directory is writable
        if (!is_writable($dir_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Directory is not writable: ' . esc_html($dir_path));
            }
            return false;
        }

        $file_path = $dir_path . '/dynamic-enforcer-rules.js';
        $result = file_put_contents($file_path, $rules_content);

        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Failed to write to file: ' . esc_html($file_path));
            }
            return false;
        }

        // Trigger action to notify that rules have been updated
        do_action('custom_cookie_rules_updated');

        return true;
    }

    private function generate_enforcer_rules($categorized)
    {
        ob_start();
?>
        /**
        * Dynamically generated cookie enforcer rules
        * Last updated: <?php echo current_time('mysql'); ?>
        */
        window.dynamicCookieRules = {
        analytics: [
        <?php foreach ($categorized['analytics'] ?? [] as $cookie): ?>
            '<?php echo esc_js($cookie['name']); ?>',
        <?php endforeach; ?>
        ],
        marketing: [
        <?php foreach ($categorized['marketing'] ?? [] as $cookie): ?>
            '<?php echo esc_js($cookie['name']); ?>',
        <?php endforeach; ?>
        ],
        functional: [
        <?php foreach ($categorized['functional'] ?? [] as $cookie): ?>
            '<?php echo esc_js($cookie['name']); ?>',
        <?php endforeach; ?>
        ]
        };
<?php
        return ob_get_clean();
    }
}
