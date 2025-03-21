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
    private static $instance = null;
    private $hook_suffix = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_custom_cookie_categorize', [$this, 'ajax_categorize_cookie']);
        add_action('wp_ajax_custom_cookie_bulk_categorize', [$this, 'ajax_bulk_categorize']);
    }

    public function add_admin_menu()
    {
        // Check if menu already exists
        global $submenu;
        if (isset($submenu['custom-cookie-consent'])) {
            return;
        }

        // Add main menu
        $this->hook_suffix = add_menu_page(
            __('Cookie Consent', 'custom-cookie-consent'),
            __('Cookie Consent', 'custom-cookie-consent'),
            'manage_options',
            'custom-cookie-consent',
            [$this, 'render_main_page'],
            'dashicons-privacy'
        );

        // Add submenus
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
            __('Settings', 'custom-cookie-consent'),
            __('Settings', 'custom-cookie-consent'),
            'manage_options',
            'custom-cookie-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'custom-cookie-consent',
            __('Styling', 'custom-cookie-consent'),
            __('Styling', 'custom-cookie-consent'),
            'manage_options',
            'custom-cookie-design',
            [$this, 'render_design_page']
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

        add_submenu_page(
            'custom-cookie-consent',
            __('Documentation', 'custom-cookie-consent'),
            __('Documentation', 'custom-cookie-consent'),
            'manage_options',
            'custom-cookie-documentation',
            [$this, 'render_documentation_page']
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

        // Enqueue Open Cookie Database script for settings page
        if (strpos($hook, 'custom-cookie-settings') !== false) {
            wp_enqueue_script(
                'custom-cookie-database-js',
                plugin_dir_url(dirname(__FILE__)) . 'admin/js/cookie-database.js',
                ['jquery', 'custom-cookie-admin-js'],
                defined('WP_DEBUG') && WP_DEBUG ? time() : CUSTOM_COOKIE_VERSION,
                true
            );
        }

        // Localize script with settings and translations
        wp_localize_script('custom-cookie-admin-js', 'customCookieAdminSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cookie_management'),
            'scanNonce' => wp_create_nonce('cookie_scan'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'updating_text' => esc_html__('Updating...', 'custom-cookie-consent'),
            'update_now_text' => esc_html__('Update Now', 'custom-cookie-consent'),
            'update_complete_text' => esc_html__('Update Complete', 'custom-cookie-consent'),
            'ajax_error_text' => esc_html__('An error occurred during the update', 'custom-cookie-consent'),
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

    /**
     * Render the styling customization page
     */
    public function render_design_page()
    {
        // Get design settings with defaults
        $default_design = [
            // Banner appearance
            'inherit_theme' => true, // Default to inheriting from theme
            'banner_position' => 'bottom', // Default position
            'banner_layout' => 'bar', // Default layout (bar, card, modal)

            // Colors
            'banner_background_color' => '#ffffff',
            'banner_text_color' => '#333333',
            'banner_border_color' => '#dddddd',

            // Button colors
            'accept_button_background' => '#4C4CFF',
            'accept_button_text_color' => '#ffffff',
            'accept_button_border_color' => '#4C4CFF',

            'decline_button_background' => '#f5f5f5',
            'decline_button_text_color' => '#333333',
            'decline_button_border_color' => '#dddddd',

            'save_button_background' => '#e0e0fd',
            'save_button_text_color' => '#333333',
            'save_button_border_color' => '#4C4CFF',

            // Typography
            'font_family' => 'inherit',
            'font_size' => '14px',
            'font_weight' => 'normal',

            // Spacing
            'banner_padding' => '15px',
            'button_padding' => '8px 16px',
            'elements_spacing' => '10px',
            'border_radius' => '4px',

            // Animation
            'animation_type' => 'fade',
            'animation_speed' => '0.3s',

            // Advanced
            'mobile_breakpoint' => '768px',
            'z_index' => '9999',
        ];

        $saved_design = get_option('custom_cookie_design', []);
        $design = array_merge($default_design, $saved_design);

        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/design.php';
    }

    public function render_translations_page()
    {
        // Get settings
        $settings = get_option('custom_cookie_settings', array());

        // Set default values for text fields if not set
        $default_texts = array(
            // Banner text
            'banner_title'          => __('We use cookies', 'custom-cookie-consent'),
            'banner_text'           => __('We use cookies to improve your experience, personalize content and analyze our traffic.', 'custom-cookie-consent'),
            'privacy_text'          => __('Privacy Policy', 'custom-cookie-consent'),
            'cookie_policy_text'    => __('Cookie Policy', 'custom-cookie-consent'),
            'close_button_text'     => __('Close', 'custom-cookie-consent'),
            'close_button_aria_label' => __('Close cookie banner', 'custom-cookie-consent'),

            // Category titles and descriptions
            'necessary_title'       => __('Necessary', 'custom-cookie-consent'),
            'necessary_description' => __('These cookies are necessary for the website to function and cannot be disabled.', 'custom-cookie-consent'),
            'analytics_title'       => __('Analytics', 'custom-cookie-consent'),
            'analytics_description' => __('These cookies help us understand how visitors use the website.', 'custom-cookie-consent'),
            'functional_title'      => __('Functional', 'custom-cookie-consent'),
            'functional_description' => __('These cookies enable enhanced functionality and personalization.', 'custom-cookie-consent'),
            'marketing_title'       => __('Marketing', 'custom-cookie-consent'),
            'marketing_description' => __('These cookies are used to track visitors across websites to display relevant advertisements.', 'custom-cookie-consent'),

            // Button text
            'accept_button'         => __('Accept All', 'custom-cookie-consent'),
            'decline_button'        => __('Decline All', 'custom-cookie-consent'),
            'save_button'           => __('Save Preferences', 'custom-cookie-consent'),
            'change_settings_button' => __('Change Cookie Settings', 'custom-cookie-consent'),

            // Consent data labels
            'consent_choices_heading' => __('Your Consent Choices', 'custom-cookie-consent'),
            'active_cookies_heading' => __('Active Cookies:', 'custom-cookie-consent'),
            'consent_status_accepted' => __('Accepted', 'custom-cookie-consent'),
            'consent_status_declined' => __('Declined', 'custom-cookie-consent'),
            'cookie_category_label' => __('Category:', 'custom-cookie-consent'),
            'cookie_purpose_label'  => __('Purpose:', 'custom-cookie-consent'),
            'cookie_expiry_label'   => __('Expires:', 'custom-cookie-consent'),
            'sources_label'         => __('Sources:', 'custom-cookie-consent'),
            'consent_last_updated'  => __('Last updated:', 'custom-cookie-consent'),
            'no_cookies_message'    => __('No cookies are currently active in this category.', 'custom-cookie-consent'),
        );

        // Apply defaults to settings
        foreach ($default_texts as $key => $default_value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $default_value;
            }
        }

        // Include the template
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/templates/translations.php';
    }

    /**
     * Render the analytics page
     */
    public function render_analytics_page()
    {
        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/analytics.php';
    }

    /**
     * Render the documentation page with best practices for GDPR compliance
     */
    public function render_documentation_page()
    {
        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/documentation.php';
    }

    public function ajax_categorize_cookie()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'cookie_management')) {
            wp_send_json_error(['message' => esc_html__('Security verification failed', 'custom-cookie-consent')]);
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied', 'custom-cookie-consent')]);
            return;
        }

        // Get and sanitize parameters
        $cookie_name = isset($_POST['cookie_name']) ? sanitize_text_field(wp_unslash($_POST['cookie_name'])) : '';
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
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'cookie_management')) {
            wp_send_json_error(['message' => esc_html__('Security verification failed', 'custom-cookie-consent')]);
            return;
        }

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
