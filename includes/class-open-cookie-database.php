<?php

/**
 * Open Cookie Database Class
 *
 * Retrieves and integrates data from the Open Cookie Database for better cookie detection and categorization.
 *
 * @package CustomCookieConsent
 */

namespace CustomCookieConsent;

/**
 * OpenCookieDatabase class
 * 
 * Handles integration with the Open Cookie Database for better cookie categorization
 */
class OpenCookieDatabase
{

    /**
     * The URL to the Open Cookie Database CSV file
     */
    const DATABASE_URL = 'https://raw.githubusercontent.com/jkwakman/Open-Cookie-Database/master/open-cookie-database.csv';

    /**
     * Local option name for storing the database
     */
    const OPTION_NAME = 'custom_cookie_ocd_database';

    /**
     * Transient name for tracking update frequency
     */
    const UPDATE_TRANSIENT = 'custom_cookie_ocd_last_update';

    /**
     * Initialize the class and set up hooks
     */
    public function __construct()
    {
        // Skip adding hooks if WordPress functions are not available (e.g., in CLI mode)
        if (! function_exists('add_action')) {
            return;
        }

        // Register cron event for monthly updates
        add_action('init', array($this, 'register_update_schedule'));
        add_action('custom_cookie_ocd_update', array($this, 'update_database'));

        // Add settings field for enabling this feature
        add_action('custom_cookie_settings_fields', array($this, 'add_settings_field'));

        // Hook into cookie categorization
        add_filter('custom_cookie_auto_categorize', array($this, 'categorize_from_database'), 10, 2);

        // Register admin AJAX actions
        add_action('wp_ajax_custom_cookie_force_ocd_update', array($this, 'ajax_force_update'));

        // Register activation hook to download database on first activation
        add_action('custom_cookie_consent_activate', array($this, 'maybe_download_on_activation'));
    }

    /**
     * Register monthly update schedule
     */
    public function register_update_schedule()
    {
        // Check if we've already verified the schedule recently
        if (get_transient('custom_cookie_ocd_schedule_check')) {
            return;
        }

        // Check if enabled in settings
        $settings = get_option('custom_cookie_settings', array());
        if (empty($settings['enable_ocd']) || '1' !== $settings['enable_ocd']) {
            return;
        }

        // Clear existing schedule if any
        if (wp_next_scheduled('custom_cookie_ocd_update')) {
            wp_clear_scheduled_hook('custom_cookie_ocd_update');
        }

        // Schedule monthly update if not already scheduled
        if (! wp_next_scheduled('custom_cookie_ocd_update')) {
            wp_schedule_event(time(), 'monthly', 'custom_cookie_ocd_update');
        }

        // Set transient to prevent frequent schedule checks
        // Check again in 12 hours
        set_transient('custom_cookie_ocd_schedule_check', true, 12 * HOUR_IN_SECONDS);
    }

    /**
     * Add settings fields to enable Open Cookie Database integration
     */
    public function add_settings_field($settings_section)
    {
        add_settings_field(
            'enable_ocd',
            __('Open Cookie Database', 'custom-cookie-consent'),
            function () {
                $settings    = get_option('custom_cookie_settings', array());
                $checked     = isset($settings['enable_ocd']) ? $settings['enable_ocd'] : '0';
                $last_update = get_transient(self::UPDATE_TRANSIENT);

                echo '<label for="enable_ocd">';
                echo '<input type="checkbox" id="enable_ocd" name="custom_cookie_settings[enable_ocd]" value="1" ' . checked('1', $checked, false) . '> ';
                echo esc_html__('Enable Open Cookie Database integration for better cookie detection', 'custom-cookie-consent');
                echo '</label>';

                echo '<p class="description">';
                echo esc_html__('Automatically download and use data from the Open Cookie Database to improve cookie categorization.', 'custom-cookie-consent');
                echo '</p>';

                if ($last_update) {
                    echo '<p class="description">';
                    printf(
                        /* translators: %s: formatted date and time of last update */
                        esc_html__('Last database update: %s', 'custom-cookie-consent'),
                        esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_update))
                    );
                    echo '</p>';

                    if (current_user_can('manage_options')) {
                        echo '<button type="button" class="button button-secondary js-force-ocd-update" style="margin-top: 10px;">';
                        echo esc_html__('Update Now', 'custom-cookie-consent');
                        echo '</button>';
                    }
                }
            },
            $settings_section,
            $settings_section
        );
    }

    /**
     * AJAX handler for forcing a database update
     */
    public function ajax_force_update()
    {
        // Verify nonce
        if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'cookie_management')) {
            wp_send_json_error(
                array(
                    'message' => __('Security verification failed.', 'custom-cookie-consent'),
                )
            );
            return;
        }

        // Check permissions
        if (! current_user_can('manage_options')) {
            wp_send_json_error(
                array(
                    'message' => __('Permission denied.', 'custom-cookie-consent'),
                )
            );
            return;
        }

        // Update the database
        $result = $this->update_database();

        if ($result['success']) {
            wp_send_json_success(
                array(
                    'message' => $result['message'],
                    'count'   => $result['count'],
                )
            );
        } else {
            wp_send_json_error(
                array(
                    'message' => $result['message'],
                )
            );
        }
    }

    /**
     * Update the Open Cookie Database
     */
    public function update_database()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Open Cookie Database: Starting update process');
        }

        try {
            // Fetch the CSV data
            $response = wp_remote_get(
                self::DATABASE_URL,
                array(
                    'timeout'    => 30,
                    'user-agent' => 'WordPress/Custom-Cookie-Consent',
                )
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if (200 !== $status_code) {
                throw new \Exception(
                    sprintf(
                        __('Failed to retrieve database: HTTP status %d', 'custom-cookie-consent'),
                        $status_code
                    )
                );
            }

            $csv_data = wp_remote_retrieve_body($response);
            if (empty($csv_data)) {
                throw new \Exception(__('Retrieved empty database file', 'custom-cookie-consent'));
            }

            // Parse the CSV data
            $cookies = $this->parse_csv_data($csv_data);

            if (empty($cookies)) {
                throw new \Exception(__('Failed to parse database or empty database', 'custom-cookie-consent'));
            }

            // Store the cookies in the options table
            update_option(self::OPTION_NAME, $cookies, false); // Don't autoload since it could be large

            // Update the last update time
            set_transient(self::UPDATE_TRANSIENT, time(), 90 * DAY_IN_SECONDS);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Open Cookie Database: Update completed successfully. Imported ' . count($cookies) . ' cookie definitions');
            }

            return array(
                'success' => true,
                'message' => sprintf(
                    __('Database updated successfully. Imported %d cookie definitions.', 'custom-cookie-consent'),
                    count($cookies)
                ),
                'count'   => count($cookies),
            );
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Open Cookie Database Error: ' . $e->getMessage());
            }

            return array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
    }

    /**
     * Parse CSV data from the Open Cookie Database
     *
     * @param string $csv_data Raw CSV data string
     * @return array Parsed cookie data
     */
    private function parse_csv_data($csv_data)
    {
        $cookies = array();
        $rows    = str_getcsv($csv_data, "\n"); // Split by newlines

        // Skip the first row (headers)
        $headers = str_getcsv(array_shift($rows), ',');

        // Map header indexes for easier access
        $header_indexes = array_flip($headers);

        foreach ($rows as $row) {
            $data = str_getcsv($row, ',');

            // Skip if we don't have a name
            if (empty($data[$header_indexes['name'] ?? 0])) {
                continue;
            }

            // Map categories to our system
            $ocd_category = $data[$header_indexes['category'] ?? 1] ?? '';
            $category     = $this->map_ocd_category($ocd_category);

            $cookie_name = trim($data[$header_indexes['name'] ?? 0]);

            // Store in our format
            $cookies[$cookie_name] = array(
                'name'        => $cookie_name,
                'category'    => $category,
                'domain'      => $data[$header_indexes['domain'] ?? 2] ?? '*',
                'description' => $data[$header_indexes['description'] ?? 3] ?? '',
                'duration'    => $data[$header_indexes['retention'] ?? 4] ?? '',
                'provider'    => $data[$header_indexes['provider'] ?? 5] ?? '',
                'source'      => 'Open Cookie Database',
                'is_pattern'  => strpos($cookie_name, '*') !== false,
            );
        }

        return $cookies;
    }

    /**
     * Map Open Cookie Database categories to our internal categories
     *
     * @param string $ocd_category Category from OCD
     * @return string Mapped category in our system
     */
    private function map_ocd_category($ocd_category)
    {
        $ocd_category = strtolower(trim($ocd_category));

        $category_mapping = array(
            'necessary'     => 'necessary',
            'essential'     => 'necessary',
            'required'      => 'necessary',
            'mandatory'     => 'necessary',
            'preferences'   => 'functional',
            'functionality' => 'functional',
            'functional'    => 'functional',
            'analytics'     => 'analytics',
            'statistical'   => 'analytics',
            'statistics'    => 'analytics',
            'marketing'     => 'marketing',
            'advertisement' => 'marketing',
            'targeting'     => 'marketing',
            'advertising'   => 'marketing',
        );

        return $category_mapping[$ocd_category] ?? 'uncategorized';
    }

    /**
     * Try to categorize a cookie using the Open Cookie Database
     *
     * @param string $current_category Current category (if already determined)
     * @param string $cookie_name Cookie name to check
     * @return string Category from database or original category
     */
    public function categorize_from_database($current_category, $cookie_name)
    {
        // If already categorized as necessary, respect that
        if ($current_category === 'necessary') {
            return $current_category;
        }

        // Check if database is enabled
        $settings = get_option('custom_cookie_settings', array());
        if (empty($settings['enable_ocd']) || '1' !== $settings['enable_ocd']) {
            return $current_category;
        }

        // Get the database
        $database = get_option(self::OPTION_NAME, array());
        if (empty($database)) {
            return $current_category;
        }

        // Direct match
        if (isset($database[$cookie_name])) {
            return $database[$cookie_name]['category'];
        }

        // Try pattern matching
        foreach ($database as $pattern => $cookie_data) {
            if (! empty($cookie_data['is_pattern'])) {
                // Convert wildcard pattern to regex
                $regex = '/^' . str_replace(array('*', '.'), array('.*', '\.'), $pattern) . '$/';
                if (preg_match($regex, $cookie_name)) {
                    return $cookie_data['category'];
                }
            }
        }

        // If no match found, return the original category
        return $current_category;
    }

    /**
     * Download the database on plugin activation if enabled
     */
    public function maybe_download_on_activation()
    {
        // Only download if setting is enabled and database doesn't exist
        $settings = get_option('custom_cookie_settings', array());
        $database = get_option(self::OPTION_NAME, array());

        if ((isset($settings['enable_ocd']) && '1' === $settings['enable_ocd']) || empty($database)) {
            // Set to enabled by default
            if (! isset($settings['enable_ocd'])) {
                $settings['enable_ocd'] = '1';
                update_option('custom_cookie_settings', $settings);
            }

            // Download the database
            $this->update_database();
        }
    }
}
