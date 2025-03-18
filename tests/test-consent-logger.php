<?php

/**
 * Class Test_Consent_Logger
 *
 * @package Custom_Cookie_Consent
 */

use CustomCookieConsent\Consent_Logger;

class Test_Consent_Logger extends WP_UnitTestCase
{

    /**
     * Test instance
     *
     * @var Consent_Logger
     */
    private $logger;

    /**
     * Set up test environment
     */
    public function set_up()
    {
        parent::set_up();
        $this->logger = new Consent_Logger();

        // Make sure the table doesn't exist before tests
        global $wpdb;
        $table_name = $wpdb->prefix . 'consent_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    /**
     * Tear down test environment
     */
    public function tear_down()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'consent_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        parent::tear_down();
    }

    /**
     * Test create_database_table
     */
    public function test_create_database_table()
    {
        // Verify table doesn't exist yet
        $this->assertFalse($this->logger->table_exists());

        // Create the table
        $result = $this->logger->create_database_table();
        $this->assertTrue($result);

        // Verify table exists
        $this->assertTrue($this->logger->table_exists());

        // Verify calling create again returns true (idempotent)
        $result = $this->logger->create_database_table();
        $this->assertTrue($result);
    }

    /**
     * Test table_exists method
     */
    public function test_table_exists()
    {
        // Initially table shouldn't exist
        $this->assertFalse($this->logger->table_exists());

        // Create the table
        $this->logger->create_database_table();

        // Now table should exist
        $this->assertTrue($this->logger->table_exists());
    }

    /**
     * Test log_consent
     */
    public function test_log_consent()
    {
        // Ensure table exists
        $this->logger->create_database_table();

        // Test data
        $ip = '127.0.0.1';
        $user_id = 1;
        $consent_data = json_encode(
            array(
                'categories' => array(
                    'necessary' => true,
                    'analytics' => true,
                    'marketing' => false,
                ),
                'consent_source' => 'banner',
            )
        );

        // Log consent
        $result = $this->logger->log_consent($ip, $user_id, $consent_data);
        $this->assertTrue($result);

        // Verify the log was saved
        global $wpdb;
        $table_name = $wpdb->prefix . 'consent_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name");

        $this->assertCount(1, $logs);
        $this->assertEquals($ip, $logs[0]->ip_address);
        $this->assertEquals($user_id, $logs[0]->user_id);
        $this->assertStringContainsString('"necessary":true', $logs[0]->consent_data);
    }

    /**
     * Test get_logs
     */
    public function test_get_logs()
    {
        // Ensure table exists
        $this->logger->create_database_table();

        // Add test logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'consent_logs';

        $test_data = array(
            array(
                'date_created' => current_time('mysql'),
                'ip_address'   => '127.0.0.1',
                'user_id'      => 1,
                'consent_data' => '{"categories":{"necessary":true,"analytics":true}}',
            ),
            array(
                'date_created' => current_time('mysql'),
                'ip_address'   => '192.168.1.1',
                'user_id'      => 2,
                'consent_data' => '{"categories":{"necessary":true,"marketing":true}}',
            ),
        );

        foreach ($test_data as $data) {
            $wpdb->insert($table_name, $data);
        }

        // Get logs
        $logs = $this->logger->get_logs();

        // Check logs
        $this->assertCount(2, $logs);
        $this->assertEquals('127.0.0.1', $logs[0]->ip_address);
        $this->assertEquals('192.168.1.1', $logs[1]->ip_address);
    }

    /**
     * Test export_logs_csv with existing table
     */
    public function test_export_logs_csv_with_table()
    {
        // Create table and add data
        $this->logger->create_database_table();

        // Add test logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'consent_logs';

        $test_data = array(
            array(
                'date_created' => '2023-01-01 12:00:00',
                'ip_address'   => '127.0.0.1',
                'user_id'      => 1,
                'consent_data' => '{"categories":{"necessary":true,"analytics":true}}',
            ),
        );

        foreach ($test_data as $data) {
            $wpdb->insert($table_name, $data);
        }

        // Test CSV export
        ob_start();
        $this->logger->export_logs_csv();
        $output = ob_get_clean();

        // CSV should contain headers and data
        $this->assertStringContainsString('Date,IP,User ID,Categories', $output);
        $this->assertStringContainsString('2023-01-01 12:00:00,127.0.0.1,1,"necessary,analytics"', $output);
    }

    /**
     * Test export_logs_csv with non-existent table
     */
    public function test_export_logs_csv_without_table()
    {
        // Drop table to ensure it doesn't exist
        global $wpdb;
        $table_name = $wpdb->prefix . 'consent_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        // Test CSV export
        ob_start();
        $this->logger->export_logs_csv();
        $output = ob_get_clean();

        // Should only have headers
        $this->assertStringContainsString('Date,IP,User ID,Categories', $output);
        $this->assertEquals("Date,IP,User ID,Categories\n", $output);

        // Table should now exist
        $this->assertTrue($this->logger->table_exists());
    }

    /**
     * Test ajax_log_consent method with table check
     */
    public function test_ajax_log_consent_creates_table()
    {
        // Drop table to ensure it doesn't exist
        global $wpdb;
        $table_name = $wpdb->prefix . 'consent_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        // Mock $_POST data
        $_POST['nonce'] = wp_create_nonce('cookie_management');
        $_POST['consent_data'] = json_encode(
            array(
                'categories' => array(
                    'necessary' => true,
                ),
            )
        );
        $_POST['source'] = 'banner';

        // Mock wp_send_json_success and wp_send_json_error
        $this->expectOutputRegex('/.*/');

        // Call the method - in a real environment it would call wp_send_json_success
        $this->logger->ajax_log_consent();

        // Table should now exist
        $this->assertTrue($this->logger->table_exists());
    }
}
