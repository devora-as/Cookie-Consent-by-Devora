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
            'options-general.php',
            __('Cookie Consent', 'custom-cookie-consent'),
            __('Cookie Consent', 'custom-cookie-consent'),
            'manage_options',
            'cookie-consent',
            [$this, 'render_admin_page']
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

    public function render_admin_page()
    {
        // Get current tab
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        // Get tabs
        $tabs = [
            'dashboard' => __('Dashboard', 'custom-cookie-consent'),
            'settings' => __('Settings', 'custom-cookie-consent'),
            'scanner' => __('Cookie Scanner', 'custom-cookie-consent'),
            'translations' => __('Translations', 'custom-cookie-consent'),
            'consent_logs' => __('Consent Logs', 'custom-cookie-consent'),
        ];

        // Start output buffering
        ob_start();

        // Include template
        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/header.php';

        // Include tab content
        switch ($tab) {
            case 'settings':
                include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/settings.php';
                break;
            case 'scanner':
                include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/scanner.php';
                break;
            case 'translations':
                include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/translations.php';
                break;
            case 'consent_logs':
                $this->render_consent_logs();
                break;
            default:
                include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/dashboard.php';
                break;
        }

        // Include footer
        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/footer.php';

        // End output buffering and echo content
        echo ob_get_clean();
    }

    /**
     * Render the consent logs page
     * 
     * @return void
     */
    private function render_consent_logs(): void
    {
        global $wpdb;

        // Process export if requested
        if (isset($_GET['export']) && $_GET['export'] === 'csv' && check_admin_referer('export_consent_logs')) {
            $this->export_consent_logs_csv();
            exit;
        }

        // Get the consent logs table name
        $table_name = $wpdb->prefix . 'cookie_consent_logs';

        // Pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        // Get total logs count
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_logs / $per_page);

        // Get logs
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

?>
        <div class="wrap">
            <h2><?php _e('Consent Logs', 'custom-cookie-consent'); ?></h2>

            <p><?php _e('This page displays a log of all consent choices made by your website visitors, as required by the ePrivacy Directive (ekomloven) §3-15.', 'custom-cookie-consent'); ?></p>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <?php
                    // Create export URL with nonce
                    $export_url = wp_nonce_url(
                        add_query_arg(
                            [
                                'page' => 'cookie-consent',
                                'tab' => 'consent_logs',
                                'export' => 'csv'
                            ],
                            admin_url('options-general.php')
                        ),
                        'export_consent_logs'
                    );
                    ?>
                    <a href="<?php echo esc_url($export_url); ?>" class="button"><?php _e('Export as CSV', 'custom-cookie-consent'); ?></a>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(
                                _n('%s item', '%s items', $total_logs, 'custom-cookie-consent'),
                                number_format_i18n($total_logs)
                            ); ?>
                        </span>

                        <span class="pagination-links">
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $page
                            ]);
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Date/Time', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('User', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Necessary', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Analytics', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Functional', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Marketing', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Details', 'custom-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8"><?php _e('No consent logs found.', 'custom-cookie-consent'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            // Parse consent data
                            $consent_data = json_decode($log->consent_data, true);
                            $categories = isset($consent_data['categories']) ? $consent_data['categories'] : [];

                            // Get user info
                            $user_info = '';
                            if (!empty($log->user_id)) {
                                $user = get_user_by('id', $log->user_id);
                                if ($user) {
                                    $user_info = esc_html($user->user_login) . ' (' . esc_html($user->user_email) . ')';
                                } else {
                                    $user_info = sprintf(__('User ID: %d (deleted)', 'custom-cookie-consent'), $log->user_id);
                                }
                            } else {
                                $user_info = __('Anonymous', 'custom-cookie-consent');
                            }

                            // Format checkmarks
                            $check = '✓';
                            $cross = '✗';
                            ?>
                            <tr>
                                <td><?php echo esc_html($log->id); ?></td>
                                <td><?php echo esc_html(get_date_from_gmt($log->timestamp, get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                                <td><?php echo $user_info; ?></td>
                                <td><?php echo isset($categories['necessary']) && $categories['necessary'] ? $check : $cross; ?></td>
                                <td><?php echo isset($categories['analytics']) && $categories['analytics'] ? $check : $cross; ?></td>
                                <td><?php echo isset($categories['functional']) && $categories['functional'] ? $check : $cross; ?></td>
                                <td><?php echo isset($categories['marketing']) && $categories['marketing'] ? $check : $cross; ?></td>
                                <td>
                                    <button type="button" class="button button-small toggle-consent-details"
                                        data-consent='<?php echo esc_attr(wp_json_encode($consent_data)); ?>'>
                                        <?php _e('View Details', 'custom-cookie-consent'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th><?php _e('ID', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Date/Time', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('User', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Necessary', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Analytics', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Functional', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Marketing', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Details', 'custom-cookie-consent'); ?></th>
                    </tr>
                </tfoot>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(
                                _n('%s item', '%s items', $total_logs, 'custom-cookie-consent'),
                                number_format_i18n($total_logs)
                            ); ?>
                        </span>

                        <span class="pagination-links">
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $page
                            ]);
                            ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Modal for consent details -->
            <div id="consent-details-modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
                <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px;">
                    <span id="close-modal" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                    <h3><?php _e('Consent Details', 'custom-cookie-consent'); ?></h3>
                    <div id="consent-details-content"></div>
                </div>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    // Handle view details button click
                    $('.toggle-consent-details').on('click', function() {
                        var consentData = $(this).data('consent');
                        var content = '<pre>' + JSON.stringify(consentData, null, 2) + '</pre>';
                        $('#consent-details-content').html(content);
                        $('#consent-details-modal').show();
                    });

                    // Close modal when X is clicked
                    $('#close-modal').on('click', function() {
                        $('#consent-details-modal').hide();
                    });

                    // Close modal when clicking outside the content
                    $(window).on('click', function(event) {
                        if ($(event.target).is('#consent-details-modal')) {
                            $('#consent-details-modal').hide();
                        }
                    });
                });
            </script>
        </div>
    <?php
    }

    /**
     * Export consent logs as CSV
     * 
     * @return void
     */
    private function export_consent_logs_csv(): void
    {
        global $wpdb;

        // Get the consent logs table name
        $table_name = $wpdb->prefix . 'cookie_consent_logs';

        // Get all logs
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="consent-logs-' . date('Y-m-d') . '.csv"');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add CSV header row
        fputcsv($output, [
            'ID',
            'Timestamp',
            'User ID',
            'IP Hash',
            'Necessary',
            'Analytics',
            'Functional',
            'Marketing',
            'Full Consent Data'
        ]);

        // Add data rows
        foreach ($logs as $log) {
            $consent_data = json_decode($log->consent_data, true);
            $categories = isset($consent_data['categories']) ? $consent_data['categories'] : [];

            fputcsv($output, [
                $log->id,
                $log->timestamp,
                $log->user_id ?: 'N/A',
                $log->ip_hash ?: 'N/A',
                isset($categories['necessary']) && $categories['necessary'] ? 'Yes' : 'No',
                isset($categories['analytics']) && $categories['analytics'] ? 'Yes' : 'No',
                isset($categories['functional']) && $categories['functional'] ? 'Yes' : 'No',
                isset($categories['marketing']) && $categories['marketing'] ? 'Yes' : 'No',
                $log->consent_data
            ]);
        }

        // Close the output stream
        fclose($output);
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
