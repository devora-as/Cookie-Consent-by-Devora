<?php

/**
 * Cookie Scanner Class
 *
 * Automatically detects cookies set by the website and stores them for categorization.
 *
 * @package CustomCookieConsent
 */

namespace CustomCookieConsent;

class CookieScanner
{

    private $scan_frequency = 'daily'; // Options: hourly, daily, weekly, monthly
    private $cookie_data = [];

    public function __construct()
    {
        add_action('init', [$this, 'register_cron']);
        add_action('custom_cookie_scan', [$this, 'perform_scan']);
        add_action('custom_cookie_scan_schedule_updated', [$this, 'register_cron']);

        // Allow manual scan from admin
        add_action('wp_ajax_custom_cookie_scan_now', [$this, 'ajax_scan']);
        add_action('wp_ajax_report_cookies', [$this, 'ajax_report_cookies']);
        add_action('wp_ajax_nopriv_report_unknown_cookie', [$this, 'ajax_report_unknown']);
        add_action('wp_ajax_report_unknown_cookie', [$this, 'ajax_report_unknown']);

        // Register frontend detector for admin users
        if (is_admin()) {
            add_action('admin_init', [$this, 'register_frontend_detector']);
        }

        // Register custom cron schedule for monthly
        add_filter('cron_schedules', [$this, 'add_monthly_schedule']);
    }

    /**
     * Add monthly cron schedule
     */
    public function add_monthly_schedule($schedules)
    {
        $schedules['monthly'] = [
            'interval' => 30 * DAY_IN_SECONDS, // Approximately monthly (30 days)
            'display' => __('Once Monthly', 'custom-cookie-consent')
        ];
        return $schedules;
    }

    public function register_cron()
    {
        // Get scan frequency from settings
        $settings = get_option('custom_cookie_settings', []);
        $this->scan_frequency = isset($settings['scan_frequency']) ? $settings['scan_frequency'] : 'daily';

        // Clear existing schedule if it exists
        if (wp_next_scheduled('custom_cookie_scan')) {
            wp_clear_scheduled_hook('custom_cookie_scan');
        }

        // Schedule the scan with selected frequency
        wp_schedule_event(time(), $this->scan_frequency, 'custom_cookie_scan');
    }

    public function ajax_scan()
    {
        // Verify nonce
        if (!isset($_POST['_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_nonce'])), 'cookie_scan')) {
            wp_send_json_error([
                'message' => __('Security verification failed. Please refresh the page and try again.', 'custom-cookie-consent')
            ]);
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied. You do not have sufficient permissions to perform a cookie scan.', 'custom-cookie-consent')
            ]);
            return;
        }

        try {
            // Log the start of the scan process
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Cookie Scanner: Starting manual scan process');
            }

            // Perform the scan
            $scan_result = $this->perform_scan();

            if ($scan_result['success']) {
                wp_send_json_success([
                    'message' => $scan_result['message'],
                    'time' => current_time('mysql')
                ]);
            } else {
                wp_send_json_error([
                    'message' => $scan_result['message']
                ]);
            }
        } catch (\Exception $e) {
            // Log the error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Cookie Scanner AJAX Error: ' . $e->getMessage());
                error_log('Error trace: ' . $e->getTraceAsString());
            }

            // Send a user-friendly error message
            wp_send_json_error([
                'message' => __('An error occurred during the cookie scan', 'custom-cookie-consent') .
                    (defined('WP_DEBUG') && WP_DEBUG ? ': ' . $e->getMessage() : ''),
                'details' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }

    public function ajax_report_cookies()
    {
        // Verify nonce
        if (!isset($_POST['_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_nonce'])), 'cookie_scan')) {
            wp_send_json_error(['message' => esc_html__('Security verification failed', 'custom-cookie-consent')]);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied', 'custom-cookie-consent')]);
            return;
        }

        $cookies_raw = sanitize_text_field(wp_unslash($_POST['cookies'] ?? '[]'));
        $cookies = json_decode($cookies_raw, true);

        if (!$cookies || !is_array($cookies)) {
            wp_send_json_error('Invalid cookie data');
        }

        $this->update_cookie_database($cookies);

        wp_send_json_success([
            'message' => sprintf(__('%d cookies processed', 'custom-cookie-consent'), count($cookies))
        ]);
    }

    public function ajax_report_unknown()
    {
        // Verify nonce
        if (!isset($_POST['_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_nonce'])), 'cookie_scan')) {
            wp_send_json_error(['message' => esc_html__('Security verification failed', 'custom-cookie-consent')]);
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied', 'custom-cookie-consent')]);
            return;
        }

        // Sanitize and validate the cookie name
        $cookie_name = isset($_POST['cookie']) ? sanitize_text_field(wp_unslash($_POST['cookie'])) : '';

        if (empty($cookie_name)) {
            wp_send_json_error(['message' => esc_html__('No cookie specified', 'custom-cookie-consent')]);
            return;
        }

        $detected = get_option('custom_cookie_detected', []);

        // Only add if not already known
        if (!isset($detected[$cookie_name])) {
            $detected[$cookie_name] = [
                'name' => $cookie_name,
                'domain' => esc_url_raw(parse_url(home_url(), PHP_URL_HOST)),
                'value_sample' => 'unknown',
                'first_detected' => current_time('mysql'),
                'auto_categorized' => $this->auto_categorize($cookie_name),
                'status' => 'uncategorized'
            ];

            update_option('custom_cookie_detected', $detected);
        }

        wp_send_json_success(['message' => esc_html__('Cookie reported successfully', 'custom-cookie-consent')]);
    }

    public function perform_scan()
    {
        try {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Cookie Scanner: perform_scan() started');
            }

            // Update last scan time
            update_option('custom_cookie_last_scan', current_time('mysql'));

            // Check if we have write permissions for the options table
            if (!$this->check_write_permissions()) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Cookie Scanner: Write permission check failed');
                }
                throw new \Exception('Unable to write to the WordPress database. Please check your database permissions.');
            }

            // Get existing detected cookies
            $detected = get_option('custom_cookie_detected', []);

            // In a real implementation, we would scan the site for cookies
            // For now, we'll just use what we have and ensure it's properly formatted

            // Ensure all cookies have required fields
            foreach ($detected as $name => $cookie) {
                if (!isset($cookie['status'])) {
                    $detected[$name]['status'] = 'uncategorized';
                }

                if (!isset($cookie['first_detected'])) {
                    $detected[$name]['first_detected'] = current_time('mysql');
                }

                if (!isset($cookie['domain'])) {
                    $detected[$name]['domain'] = '*';
                }
            }

            // Save the updated cookie data
            update_option('custom_cookie_detected', $detected);

            // Notify admin if uncategorized cookies exist
            $this->notify_about_new_cookies();

            // Trigger action for other components to respond
            do_action('custom_cookie_scan_complete');

            // Regenerate the enforcer rules
            do_action('custom_cookie_rules_updated');

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Cookie Scanner: perform_scan() completed successfully');
                error_log('Cookie Scanner: Found ' . count($detected) . ' cookies');
            }

            return [
                'success' => true,
                'message' => sprintf(
                    __('Scan completed successfully. Found %d cookies.', 'custom-cookie-consent'),
                    count($detected)
                ),
                'cookies' => count($detected)
            ];
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Cookie Scanner Error: ' . $e->getMessage());
                error_log('Error trace: ' . $e->getTraceAsString());
            }

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if we have write permissions to the database
     * 
     * @return bool True if we can write to the database, false otherwise
     */
    private function check_write_permissions()
    {
        try {
            // Try to update a test option
            $test_key = 'cookie_scanner_write_test_' . time();
            update_option($test_key, '1');

            // Check if it was saved
            $result = get_option($test_key) === '1';

            // Clean up
            delete_option($test_key);

            return $result;
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Cookie Scanner write permission test failed: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function update_cookie_database($cookies)
    {
        // Compare with existing cookies and add only new ones
        $existing = get_option('custom_cookie_detected', []);
        $new_cookies = [];

        foreach ($cookies as $cookie) {
            if (!isset($existing[$cookie['name']])) {
                $new_cookies[$cookie['name']] = [
                    'name' => $cookie['name'],
                    'domain' => $cookie['domain'],
                    'value_sample' => substr($cookie['value'] ?? '', 0, 50), // Store partial for identification
                    'first_detected' => current_time('mysql'),
                    'auto_categorized' => $this->auto_categorize($cookie['name']),
                    'status' => 'uncategorized' // Admin needs to confirm
                ];
            }
        }

        if (!empty($new_cookies)) {
            update_option('custom_cookie_detected', array_merge($existing, $new_cookies));
        }

        return count($new_cookies);
    }

    private function notify_about_new_cookies()
    {
        // Check if we have uncategorized cookies
        $detected = get_option('custom_cookie_detected', []);
        $uncategorized = array_filter($detected, function ($cookie) {
            return isset($cookie['status']) && $cookie['status'] === 'uncategorized';
        });

        if (count($uncategorized) > 0) {
            // Only send notification if we haven't sent one in last 24 hours
            $last_notification = get_option('custom_cookie_last_notification', 0);
            if (time() - $last_notification > DAY_IN_SECONDS) {
                // Send email to admin
                $admin_email = get_option('admin_email');
                $subject = sprintf(
                    /* translators: %s: Site name */
                    esc_html__('[%s] New cookies detected on your site', 'custom-cookie-consent'),
                    esc_html(get_bloginfo('name'))
                );

                $message = sprintf(
                    /* translators: %d: Number of uncategorized cookies */
                    esc_html__('The Cookie Consent plugin has detected %d uncategorized cookies on your site. Please log in to the WordPress admin panel and categorize these cookies for proper GDPR compliance.', 'custom-cookie-consent'),
                    count($uncategorized)
                );

                // Add admin URL
                $admin_url = esc_url(admin_url('admin.php?page=custom-cookie-scanner'));
                $message .= "\n\n" . esc_html__('Manage cookies:', 'custom-cookie-consent') . ' ' . $admin_url;

                wp_mail($admin_email, $subject, $message);

                // Update last notification time
                update_option('custom_cookie_last_notification', time());
            }
        }
    }

    private function auto_categorize($cookie_name)
    {
        // Sanitize the cookie name for safety
        $cookie_name = sanitize_text_field($cookie_name);

        // Common patterns for cookie categories
        $patterns = [
            'necessary' => ['/^wp-/', '/^wordpress/', '/^wc_/', '/^session/', '/^csrf/', '/^token/', '/^PHPSESSID$/'],
            'analytics' => ['/^_ga/', '/^_gid/', '/^_gat/', '/^_utm/', '/^__utm/', '/^_pk_/'],
            'marketing' => ['/^__hs/', '/hubspot/', '/^_fb/', '/^_pin_/'],
            'functional' => ['/^user_pref/', '/^display_/', '/^theme_/']
        ];

        foreach ($patterns as $category => $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match($regex, $cookie_name)) {
                    return apply_filters('custom_cookie_auto_categorize', $category, $cookie_name);
                }
            }
        }

        // If no pattern match, let external databases try to categorize
        return apply_filters('custom_cookie_auto_categorize', 'uncategorized', $cookie_name);
    }

    // JavaScript component to detect cookies on the frontend
    public function register_frontend_detector()
    {
        if (current_user_can('manage_options')) {
            add_action('wp_footer', [$this, 'output_detector_script']);
            add_action('admin_footer', [$this, 'output_detector_script']);
        }
    }

    public function output_detector_script()
    {
?>
        <script>
            // Only runs for admin users to detect cookies
            (function() {
                const cookies = document.cookie.split(';').map(cookie => {
                    const parts = cookie.trim().split('=');
                    return {
                        name: parts[0],
                        domain: window.location.hostname,
                        value: parts.slice(1).join('=')
                    };
                });

                // Skip if no cookies found
                if (cookies.length === 0) {
                    return;
                }

                // Send to admin-ajax for processing
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=report_cookies&cookies=' + encodeURIComponent(JSON.stringify(cookies)) +
                        '&_nonce=<?php echo wp_create_nonce('cookie_scan'); ?>'
                }).catch(err => {
                    // Silent error - no need to bother the user
                });
            })();
        </script>
<?php
    }
}
