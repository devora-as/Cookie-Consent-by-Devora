<?php

/**
 * Matomo Integration Class
 *
 * Handles integration with Matomo Analytics and Matomo Tag Manager.
 *
 * @package CustomCookieConsent
 */

namespace CustomCookieConsent;

use \Exception;

class MatomoIntegration
{
    /**
     * @var array
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->settings = get_option('custom_cookie_settings', []);

        // Register hooks only if Matomo is enabled
        if ($this->is_enabled()) {
            add_action('wp_head', [$this, 'output_tracking_code'], 1);
            add_filter('custom_cookie_consent_analytics_state', [$this, 'filter_analytics_state']);

            // Register Matomo cookies
            add_action('init', [$this, 'register_cookies']);
        }
    }

    /**
     * Check if Matomo integration is enabled
     *
     * @return bool
     */
    public function is_enabled(): bool
    {
        return !empty($this->settings['enable_matomo']);
    }

    /**
     * Register Matomo cookies with the consent manager
     */
    public function register_cookies(): void
    {
        // Register Matomo cookies
        WPConsentWrapper::register_cookie(
            '_pk_id',
            'Matomo',
            'analytics',
            __('Used to store a few details about the user such as the unique visitor ID', 'custom-cookie-consent'),
            '13 months'
        );

        WPConsentWrapper::register_cookie(
            '_pk_ses',
            'Matomo',
            'analytics',
            __('Short lived cookie used to temporarily store data for the visit', 'custom-cookie-consent'),
            '30 minutes'
        );

        WPConsentWrapper::register_cookie(
            '_pk_ref',
            'Matomo',
            'analytics',
            __('Used to store the attribution information, the referrer initially used to visit the website', 'custom-cookie-consent'),
            '6 months'
        );

        WPConsentWrapper::register_cookie(
            '_pk_cvar',
            'Matomo',
            'analytics',
            __('Short lived cookie used to temporarily store custom variables for the visit', 'custom-cookie-consent'),
            '30 minutes'
        );

        WPConsentWrapper::register_cookie(
            '_pk_hsr',
            'Matomo',
            'analytics',
            __('Short lived cookie used to temporarily store heatmap and session recording data', 'custom-cookie-consent'),
            '30 minutes'
        );
    }

    /**
     * Output Matomo tracking code
     */
    public function output_tracking_code(): void
    {
        try {
            if (!$this->is_enabled()) {
                return;
            }

            // Debug log
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Matomo Integration: Preparing to output tracking code');
            }

            $type = $this->settings['matomo_type'] ?? '';
            $require_consent = !empty($this->settings['matomo_require_consent']);
            $respect_dnt = !empty($this->settings['matomo_respect_dnt']);
            $no_cookies = !empty($this->settings['matomo_no_cookies']);

            if ($type === 'cloud') {
                $url = $this->settings['matomo_cloud_url'] ?? '';
                $site_id = $this->settings['matomo_site_id'] ?? '';
            } else {
                $url = $this->settings['matomo_url'] ?? '';
                $site_id = $this->settings['matomo_self_hosted_site_id'] ?? '';
            }

            // Validate required settings
            if (empty($url) || empty($site_id)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Matomo Integration: Missing required settings - URL or Site ID');
                    error_log('Matomo Settings: ' . print_r($this->settings, true));
                }
                return;
            }

            // Remove trailing slash from URL
            $url = rtrim($url, '/');

            // Debug log settings
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Matomo Integration Settings: ' . print_r([
                    'type' => $type,
                    'url' => $url,
                    'site_id' => $site_id,
                    'require_consent' => $require_consent,
                    'respect_dnt' => $respect_dnt,
                    'no_cookies' => $no_cookies
                ], true));
            }

            // Start output buffering
            ob_start();
?>
            <!-- Matomo -->
            <script>
                var _paq = window._paq = window._paq || [];

                <?php if ($require_consent): ?>
                    _paq.push(['requireConsent']);
                <?php endif; ?>

                <?php if ($respect_dnt): ?>
                    _paq.push(['respectDoNotTrack', true]);
                <?php endif; ?>

                <?php if ($no_cookies): ?>
                    _paq.push(['disableCookies']);
                <?php endif; ?>

                _paq.push(['trackPageView']);
                _paq.push(['enableLinkTracking']);

                (function() {
                    var u = "<?php echo esc_js($url); ?>/";
                    _paq.push(['setTrackerUrl', u + 'matomo.php']);
                    _paq.push(['setSiteId', '<?php echo esc_js($site_id); ?>']);
                    var d = document,
                        g = d.createElement('script'),
                        s = d.getElementsByTagName('script')[0];
                    g.async = true;
                    g.src = u + 'matomo.js';
                    s.parentNode.insertBefore(g, s);

                    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                        g.onerror = function() {
                            console.error('Failed to load Matomo tracking script from: ' + g.src);
                        };
                    <?php endif; ?>
                })();
            </script>
            <!-- End Matomo Code -->
<?php
            // Get and clean the buffer
            $output = ob_get_clean();

            // Debug log the output if in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Matomo Integration: Successfully generated tracking code');
            }

            // Output the tracking code
            echo $output;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Matomo Integration Error: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
            }
        }
    }

    /**
     * Filter analytics state based on Matomo settings
     *
     * @param bool $state Current analytics state
     * @return bool Modified analytics state
     */
    public function filter_analytics_state(bool $state): bool
    {
        if (!$this->is_enabled()) {
            return $state;
        }

        // If Matomo requires consent and consent hasn't been given, return false
        if (!empty($this->settings['matomo_require_consent'])) {
            $consent_data = get_option('custom_cookie_consent_data', []);
            if (empty($consent_data['categories']['analytics'])) {
                return false;
            }
        }

        // If respecting DNT is enabled and DNT is set, return false
        if (!empty($this->settings['matomo_respect_dnt'])) {
            if (isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == 1) {
                return false;
            }
        }

        return $state;
    }
}
