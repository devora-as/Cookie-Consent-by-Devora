<?php

/**
 * WP Consent API Wrapper Class
 *
 * Provides compatibility with the WP Consent API plugin
 * and handles compatibility when the plugin is not active.
 *
 * @package CustomCookieConsent
 */

namespace CustomCookieConsent;

/**
 * WP Consent API compatibility wrapper
 */
class WPConsentWrapper
{
    /**
     * Check if WP Consent API is active
     *
     * @return bool
     */
    public static function is_consent_api_active(): bool
    {
        return function_exists('wp_get_consent_type');
    }

    /**
     * Register a cookie with the WP Consent API if available
     *
     * @param string $name Cookie name
     * @param string $provider Provider name
     * @param string $category Cookie category
     * @param string $description Cookie description
     * @param string $expiry Cookie expiry
     * @return bool Whether the registration was successful
     */
    public static function register_cookie(string $name, string $provider, string $category, string $description = '', string $expiry = ''): bool
    {
        if (function_exists('wp_add_cookie_info')) {
            // Use the native WP Consent API function
            \wp_add_cookie_info(
                $name,
                $provider,
                $category,
                $description,
                $expiry
            );
            return true;
        } else {
            // Store in our own custom option for future use
            $cookies = get_option('custom_cookie_registered', []);
            $cookies[$name] = [
                'name' => $name,
                'provider' => $provider,
                'category' => $category,
                'description' => $description,
                'expiry' => $expiry,
                'registered' => current_time('mysql')
            ];
            update_option('custom_cookie_registered', $cookies);
            return true;
        }
    }

    /**
     * Check if consent has been given for a specific category
     *
     * @param string $category Category to check consent for
     * @return bool Whether consent has been given
     */
    public static function has_consent(string $category): bool
    {
        if (function_exists('wp_has_consent')) {
            // Use the native WP Consent API function
            return \wp_has_consent($category);
        }

        // Fallback to our own consent system when WP Consent API is not active
        $cookie_consent = \CustomCookieConsent\CookieConsent::get_instance();
        $consent_data = $cookie_consent->get_stored_consent();

        if ($consent_data && isset($consent_data['categories'][$category])) {
            return (bool) $consent_data['categories'][$category];
        }

        // Default to false for most categories except 'necessary'
        return $category === 'necessary';
    }

    /**
     * Set consent for a specific category
     *
     * @param string $category Category to set consent for
     * @param bool $consent Consent value
     * @return bool Whether setting consent was successful
     */
    public static function set_consent(string $category, bool $consent): bool
    {
        if (function_exists('wp_set_consent')) {
            // Use the native WP Consent API function
            return \wp_set_consent($category, $consent ? 'allow' : 'deny');
        } else {
            // Store in our own custom option
            $cookie_consent = \CustomCookieConsent\CookieConsent::get_instance();
            $consent_data = $cookie_consent->get_stored_consent() ?: [
                'version' => '1.0.0',
                'timestamp' => current_time('mysql'),
                'categories' => [
                    'necessary' => true,
                    'analytics' => false,
                    'functional' => false,
                    'marketing' => false
                ]
            ];

            // Update the category
            $consent_data['categories'][$category] = $consent;

            // Store the updated consent
            if (is_user_logged_in()) {
                update_user_meta(get_current_user_id(), 'custom_cookie_consent_data', $consent_data);
            }

            return true;
        }
    }

    /**
     * Get active consent type
     *
     * @return string The consent type or empty string if not available
     */
    public static function get_consent_type(): string
    {
        if (function_exists('wp_get_consent_type')) {
            return \wp_get_consent_type();
        }

        // Default to 'optin' for GDPR compliance
        return 'optin';
    }
}
