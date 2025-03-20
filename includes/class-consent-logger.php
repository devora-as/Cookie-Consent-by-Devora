<?php

/**
 * Consent Logger Class
 *
 * Handles server-side logging of consent data and provides analytics functionality.
 *
 * @package CustomCookieConsent
 */

namespace CustomCookieConsent;

/**
 * ConsentLogger Class
 */
class ConsentLogger
{
    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cookie_consent_logs';

        // Initialize hooks
        add_action('plugins_loaded', array($this, 'register_hooks'));
    }

    /**
     * Register hooks
     */
    public function register_hooks(): void
    {
        // Create database table on plugin activation
        add_action('custom_cookie_consent_activate', array($this, 'create_database_table'));

        // AJAX endpoint for consent logging
        add_action('wp_ajax_custom_cookie_log_consent', array($this, 'ajax_log_consent'));
        add_action('wp_ajax_nopriv_custom_cookie_log_consent', array($this, 'ajax_log_consent'));

        // AJAX endpoint for exporting logs
        add_action('wp_ajax_export_consent_logs', array($this, 'ajax_export_logs'));
    }

    /**
     * Create the database table for consent logs
     */
    public function create_database_table(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) DEFAULT NULL,
			visitor_id varchar(64) DEFAULT NULL,
			ip_hash varchar(64) DEFAULT NULL,
			consent_version varchar(32) DEFAULT NULL,
			consent_timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			consent_data longtext NOT NULL,
			consent_categories text NOT NULL,
			user_agent text DEFAULT NULL,
			consent_source varchar(32) DEFAULT 'banner',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY visitor_id (visitor_id),
			KEY consent_timestamp (consent_timestamp)
		) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log consent data
     *
     * @param array  $consent_data The full consent data.
     * @param string $source The source of consent (banner, settings, etc.).
     * @return int|false The ID of the inserted log entry, or false on failure.
     */
    public function log_consent(array $consent_data, string $source = 'banner')
    {
        global $wpdb;

        // Get user ID if logged in
        $user_id = is_user_logged_in() ? get_current_user_id() : null;

        // Create a visitor ID for anonymous users
        $visitor_id = $user_id ? null : $this->get_visitor_id();

        // Get consent categories
        $categories = array();
        if (isset($consent_data['categories'])) {
            foreach ($consent_data['categories'] as $category => $value) {
                if ($value) {
                    $categories[] = $category;
                }
            }
        }

        // Create anonymized IP hash
        $ip_hash = $this->anonymize_ip($_SERVER['REMOTE_ADDR'] ?? '');

        // Insert log entry
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id'           => $user_id,
                'visitor_id'        => $visitor_id,
                'ip_hash'           => $ip_hash,
                'consent_version'   => $consent_data['version'] ?? '1.0',
                'consent_timestamp' => current_time('mysql'),
                'consent_data'      => wp_json_encode($consent_data),
                'consent_categories' => implode(',', $categories),
                'user_agent'        => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'consent_source'    => $source,
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Handle AJAX consent logging
     */
    public function ajax_log_consent(): void
    {
        // Verify nonce
        if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'cookie_management')) {
            wp_send_json_error(array('message' => __('Security check failed', 'custom-cookie-consent')));
            return;
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

        // Get the consent source
        $source = isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : 'banner';

        // Check if table exists
        if (! $this->table_exists()) {
            $this->create_database_table();
        }

        // Log the consent
        $log_id = $this->log_consent($consent_data, $source);

        if ($log_id) {
            wp_send_json_success(array('log_id' => $log_id));
        } else {
            wp_send_json_error(array('message' => __('Failed to log consent', 'custom-cookie-consent')));
        }
    }

    /**
     * Generate a persistent visitor ID for anonymous users
     * 
     * @return string Visitor ID
     */
    private function get_visitor_id(): string
    {
        // Check if visitor ID already exists in cookie
        $visitor_id = isset($_COOKIE['cookie_consent_visitor']) ? sanitize_text_field(wp_unslash($_COOKIE['cookie_consent_visitor'])) : '';

        // If not, generate a new one
        if (empty($visitor_id)) {
            $visitor_id = wp_generate_uuid4();

            // Set cookie for 1 year
            setcookie(
                'cookie_consent_visitor',
                $visitor_id,
                time() + YEAR_IN_SECONDS,
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
        }

        return $visitor_id;
    }

    /**
     * Anonymize IP address
     * 
     * @param string $ip IP address.
     * @return string Hashed IP
     */
    private function anonymize_ip(string $ip): string
    {
        // For IPv4, remove the last octet
        // For IPv6, remove the last 80 bits
        if (strpos($ip, ':') === false) {
            // IPv4
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[3] = '0';
                $anonymized = implode('.', $parts);
            } else {
                $anonymized = $ip;
            }
        } else {
            // IPv6
            $anonymized = substr($ip, 0, strrpos($ip, ':')) . ':0000';
        }

        // Create a one-way hash
        return hash('sha256', $anonymized . wp_salt());
    }

    /**
     * Check if the consent logs table exists in the database
     *
     * @return bool True if table exists, false otherwise
     */
    public function table_exists()
    {
        global $wpdb;

        // Use the table name from the class property instead of hardcoded value
        $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name));

        return ! empty($result);
    }

    /**
     * Get consent statistics
     * 
     * @param string $period Period to get statistics for (day, week, month, year, all).
     * @return array Statistics data
     */
    public function get_consent_statistics(string $period = 'month'): array
    {
        global $wpdb;

        // Make sure table exists
        if (! $this->table_exists()) {
            $this->create_database_table();

            // Return empty statistics if table was just created
            return array(
                'total'      => 0,
                'categories' => array(
                    'necessary'  => 0,
                    'analytics'  => 0,
                    'functional' => 0,
                    'marketing'  => 0,
                ),
                'trend'      => array_fill_keys(array_map(function ($i) {
                    return date('Y-m-d', strtotime("-{$i} days"));
                }, range(0, 29)), 0),
                'percentage' => array(
                    'necessary'  => 0,
                    'analytics'  => 0,
                    'functional' => 0,
                    'marketing'  => 0,
                ),
            );
        }

        // Define the date range based on period
        $date_condition = '';
        switch ($period) {
            case 'day':
                $date_condition = "consent_timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $date_condition = "consent_timestamp >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "consent_timestamp >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $date_condition = "consent_timestamp >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            case 'all':
            default:
                $date_condition = "1=1";
                break;
        }

        // Get total records
        $total_logs = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE {$date_condition}");

        // Get consent counts by category
        $necessary_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE {$date_condition} AND consent_categories LIKE '%necessary%'");
        $analytics_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE {$date_condition} AND consent_categories LIKE '%analytics%'");
        $functional_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE {$date_condition} AND consent_categories LIKE '%functional%'");
        $marketing_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE {$date_condition} AND consent_categories LIKE '%marketing%'");

        // Get consent trend (logs per day for the last 30 days)
        $trend_data = $wpdb->get_results(
            "SELECT DATE(consent_timestamp) as date, COUNT(*) as count
			 FROM {$this->table_name}
			 WHERE consent_timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			 GROUP BY DATE(consent_timestamp)
			 ORDER BY date ASC",
            ARRAY_A
        );

        // Ensure data for all 30 days (even if no logs)
        $trend = array();
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $trend[$date] = 0;
        }

        // Fill in actual data
        foreach ($trend_data as $data) {
            if (isset($trend[$data['date']])) {
                $trend[$data['date']] = (int) $data['count'];
            }
        }

        return array(
            'total'      => $total_logs,
            'categories' => array(
                'necessary'  => $necessary_count,
                'analytics'  => $analytics_count,
                'functional' => $functional_count,
                'marketing'  => $marketing_count,
            ),
            'trend'      => $trend,
            'percentage' => array(
                'necessary'  => $total_logs > 0 ? round(($necessary_count / $total_logs) * 100) : 0,
                'analytics'  => $total_logs > 0 ? round(($analytics_count / $total_logs) * 100) : 0,
                'functional' => $total_logs > 0 ? round(($functional_count / $total_logs) * 100) : 0,
                'marketing'  => $total_logs > 0 ? round(($marketing_count / $total_logs) * 100) : 0,
            ),
        );
    }

    /**
     * Get consent logs with pagination
     * 
     * @param int $page Page number.
     * @param int $per_page Items per page.
     * @return array Logs and pagination data
     */
    public function get_consent_logs(int $page = 1, int $per_page = 20): array
    {
        global $wpdb;

        // Make sure table exists
        if (! $this->table_exists()) {
            $this->create_database_table();

            // Return empty logs if table was just created
            return array(
                'logs'       => array(),
                'pagination' => array(
                    'total'        => 0,
                    'per_page'     => $per_page,
                    'current_page' => $page,
                    'total_pages'  => 0,
                ),
            );
        }

        // Calculate offset
        $offset = ($page - 1) * $per_page;

        // Get total records
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");

        // Get logs
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY consent_timestamp DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        // Calculate pagination data
        $total_pages = ceil($total / $per_page);

        return array(
            'logs'       => $logs,
            'pagination' => array(
                'total'       => $total,
                'per_page'    => $per_page,
                'current_page' => $page,
                'total_pages' => $total_pages,
            ),
        );
    }

    /**
     * AJAX handler for exporting logs as CSV
     */
    public function ajax_export_logs(): void
    {
        // Check user capabilities
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'custom-cookie-consent'));
        }

        // Verify nonce
        if (! isset($_GET['_wpnonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cookie_analytics_nonce')) {
            wp_die(esc_html__('Security check failed', 'custom-cookie-consent'));
        }

        // Get period from request
        $period = isset($_GET['period']) ? sanitize_text_field(wp_unslash($_GET['period'])) : 'all';

        // Generate CSV content
        $csv = $this->export_logs_csv($period);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=consent-logs-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV content
        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Export consent logs as CSV
     * 
     * @param string $period Period to export (day, week, month, year, all).
     * @return string CSV content
     */
    public function export_logs_csv(string $period = 'all'): string
    {
        global $wpdb;

        // Make sure table exists
        if (! $this->table_exists()) {
            $this->create_database_table();

            // Return empty CSV if table was just created
            return "ID,User ID,Visitor ID,IP Hash,Consent Version,Consent Timestamp,Consent Categories,Consent Source\n";
        }

        // Define the date range based on period
        $where_clause = '';
        switch ($period) {
            case 'day':
                $where_clause = "WHERE consent_timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $where_clause = "WHERE consent_timestamp >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $where_clause = "WHERE consent_timestamp >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $where_clause = "WHERE consent_timestamp >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            case 'all':
            default:
                $where_clause = "";
                break;
        }

        // Get logs
        $logs = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY consent_timestamp DESC",
            ARRAY_A
        );

        // Create CSV content
        $csv = "ID,User ID,Visitor ID,IP Hash,Consent Version,Consent Timestamp,Consent Categories,Consent Source\n";

        foreach ($logs as $log) {
            $csv .= implode(',', array(
                $log['id'],
                $log['user_id'] ?: 'N/A',
                $log['visitor_id'] ?: 'N/A',
                $log['ip_hash'],
                $log['consent_version'],
                $log['consent_timestamp'],
                '"' . str_replace(',', ';', $log['consent_categories']) . '"',
                $log['consent_source'],
            )) . "\n";
        }

        return $csv;
    }
}
