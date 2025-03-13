<?php

/**
 * Integrations Class
 *
 * Manages integration with WordPress core and other plugins.
 *
 * @package CustomCookieConsent
 */

namespace CustomCookieConsent;

class Integrations
{
    private $known_integrations = [];

    public function __construct()
    {
        // Register integrations
        $this->register_core_integrations();

        // Allow plugins to register their cookies
        add_action('plugins_loaded', [$this, 'init_hooks'], 20);
    }

    public function init_hooks()
    {
        // Plugin integration hook
        do_action('custom_cookie_consent_register_cookies', $this);

        // Detect active plugins and configure special integrations
        $this->auto_detect_plugins();
    }

    public function register_core_integrations()
    {
        // WordPress core
        $this->known_integrations['wordpress'] = [
            'name' => 'WordPress Core',
            'cookies' => [
                ['name' => 'wordpress_test_cookie', 'category' => 'necessary'],
                ['name' => 'wordpress_logged_in_', 'category' => 'necessary', 'pattern' => true],
                ['name' => 'wordpress_sec_', 'category' => 'necessary', 'pattern' => true],
                ['name' => 'wp-settings-', 'category' => 'necessary', 'pattern' => true]
            ]
        ];

        // WooCommerce
        $this->known_integrations['woocommerce'] = [
            'name' => 'WooCommerce',
            'detector' => 'class_exists("WooCommerce")',
            'cookies' => [
                ['name' => 'woocommerce_cart_hash', 'category' => 'necessary'],
                ['name' => 'woocommerce_items_in_cart', 'category' => 'necessary'],
                ['name' => 'wp_woocommerce_session_', 'category' => 'necessary', 'pattern' => true],
                ['name' => 'woocommerce_recently_viewed', 'category' => 'functional']
            ]
        ];

        // Google Analytics
        $this->known_integrations['google-analytics'] = [
            'name' => 'Google Analytics',
            'detector' => 'defined("GOOGLESITEKIT_VERSION") || defined("MONSTERINSIGHTS_VERSION")',
            'cookies' => [
                ['name' => '_ga', 'category' => 'analytics'],
                ['name' => '_gid', 'category' => 'analytics'],
                ['name' => '_gat', 'category' => 'analytics'],
                ['name' => '_ga_', 'category' => 'analytics', 'pattern' => true]
            ]
        ];

        // HubSpot
        $this->known_integrations['hubspot'] = [
            'name' => 'HubSpot',
            'detector' => 'defined("LEADIN_PLUGIN_VERSION")',
            'cookies' => [
                ['name' => '__hssc', 'category' => 'marketing'],
                ['name' => '__hssrc', 'category' => 'marketing'],
                ['name' => '__hstc', 'category' => 'marketing'],
                ['name' => 'hubspotutk', 'category' => 'marketing']
            ]
        ];
    }

    public function auto_detect_plugins()
    {
        foreach ($this->known_integrations as $id => $integration) {
            if (isset($integration['detector'])) {
                $is_active = eval("return " . $integration['detector'] . ";");
                if ($is_active) {
                    $this->register_integration_cookies($integration);
                }
            }
        }
    }

    public function register_integration_cookies($integration)
    {
        $detected = get_option('custom_cookie_detected', []);
        $updated = false;

        foreach ($integration['cookies'] as $cookie) {
            $cookie_key = $cookie['name'];

            if (!isset($detected[$cookie_key])) {
                $detected[$cookie_key] = [
                    'name' => $cookie['name'],
                    'domain' => '*',
                    'category' => $cookie['category'],
                    'status' => 'categorized',
                    'source' => $integration['name'],
                    'pattern' => $cookie['pattern'] ?? false,
                    'first_detected' => current_time('mysql'),
                    'added_by' => 'auto-integration'
                ];
                $updated = true;
            }
        }

        if ($updated) {
            update_option('custom_cookie_detected', $detected);

            // Trigger regeneration of enforcer rules
            do_action('custom_cookie_rules_updated');
        }
    }

    public function register_plugin_cookies($plugin_name, $cookies)
    {
        if (empty($plugin_name) || empty($cookies) || !is_array($cookies)) {
            return false;
        }

        $detected = get_option('custom_cookie_detected', []);
        $updated = false;

        foreach ($cookies as $cookie) {
            if (empty($cookie['name']) || empty($cookie['category'])) {
                continue;
            }

            $cookie_key = $cookie['name'];

            if (!isset($detected[$cookie_key])) {
                $detected[$cookie_key] = [
                    'name' => $cookie['name'],
                    'domain' => $cookie['domain'] ?? '*',
                    'category' => $cookie['category'],
                    'status' => 'categorized',
                    'source' => $plugin_name,
                    'pattern' => $cookie['pattern'] ?? false,
                    'first_detected' => current_time('mysql'),
                    'added_by' => 'plugin-api'
                ];
                $updated = true;
            }
        }

        if ($updated) {
            update_option('custom_cookie_detected', $detected);

            // Trigger regeneration of enforcer rules
            do_action('custom_cookie_rules_updated');
            return true;
        }

        return false;
    }
}
