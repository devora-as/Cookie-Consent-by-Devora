<?php

/**
 * Admin Settings Template
 *
 * Settings page for the cookie consent plugin.
 *
 * @package CustomCookieConsent
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cookie-consent-admin-wrap">
    <div class="cookie-consent-admin-header">
        <h1><?php _e('Cookie Consent Settings', 'custom-cookie-consent'); ?></h1>

        <div class="cookie-consent-header-actions">
            <a href="<?php echo admin_url('admin.php?page=custom-cookie-scanner'); ?>" class="button">
                <?php _e('Scan for Cookies', 'custom-cookie-consent'); ?>
            </a>
        </div>
    </div>

    <div class="cookie-consent-admin-nav">
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-consent'); ?>">
            <?php _e('Dashboard', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-scanner'); ?>">
            <?php _e('Cookie Scanner', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-settings'); ?>" class="active">
            <?php _e('Settings', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-translations'); ?>">
            <?php _e('Text & Translations', 'custom-cookie-consent'); ?>
        </a>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Banner Settings', 'custom-cookie-consent'); ?></h2>

        <form class="cookie-consent-settings-form js-cookie-settings-form" method="post" action="">
            <div class="form-field">
                <label for="position"><?php _e('Banner Position', 'custom-cookie-consent'); ?></label>
                <select id="position" name="position">
                    <option value="bottom" <?php selected($settings['position'] ?? '', 'bottom'); ?>><?php _e('Bottom', 'custom-cookie-consent'); ?></option>
                    <option value="top" <?php selected($settings['position'] ?? '', 'top'); ?>><?php _e('Top', 'custom-cookie-consent'); ?></option>
                    <option value="center" <?php selected($settings['position'] ?? '', 'center'); ?>><?php _e('Center (Modal)', 'custom-cookie-consent'); ?></option>
                </select>
            </div>

            <div class="form-field">
                <label for="privacy_url"><?php _e('Privacy Policy URL', 'custom-cookie-consent'); ?></label>
                <input type="text" id="privacy_url" name="privacy_url" value="<?php echo esc_attr($settings['privacy_url'] ?? ''); ?>">
            </div>

            <div class="form-field">
                <label for="cookie_policy_url"><?php _e('Cookie Policy URL', 'custom-cookie-consent'); ?></label>
                <input type="text" id="cookie_policy_url" name="cookie_policy_url" value="<?php echo esc_attr($settings['cookie_policy_url'] ?? ''); ?>">
                <p class="description"><?php _e('Link to your dedicated cookie policy page (if different from privacy policy).', 'custom-cookie-consent'); ?></p>
            </div>

            <div class="form-field">
                <p class="description"><?php _e('For text customization and translation options, please visit the', 'custom-cookie-consent'); ?> <a href="<?php echo admin_url('admin.php?page=custom-cookie-translations'); ?>"><?php _e('Text & Translations', 'custom-cookie-consent'); ?></a> <?php _e('page.', 'custom-cookie-consent'); ?></p>
            </div>

            <input type="submit" name="submit" class="cookie-consent-submit" value="<?php _e('Save Settings', 'custom-cookie-consent'); ?>">
        </form>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Scanner Settings', 'custom-cookie-consent'); ?></h2>

        <form class="cookie-consent-settings-form js-scanner-settings-form" method="post" action="">
            <div class="form-field">
                <label>
                    <input type="checkbox" name="auto_scan" value="1" <?php checked($settings['auto_scan'] ?? true); ?>>
                    <?php _e('Enable Automatic Scanning', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Automatically detect cookies as admin users browse the site.', 'custom-cookie-consent'); ?></p>
            </div>

            <div class="form-field">
                <label for="scan_frequency"><?php _e('Background Scan Frequency', 'custom-cookie-consent'); ?></label>
                <select id="scan_frequency" name="scan_frequency">
                    <option value="hourly" <?php selected($settings['scan_frequency'] ?? '', 'hourly'); ?>><?php _e('Hourly', 'custom-cookie-consent'); ?></option>
                    <option value="daily" <?php selected($settings['scan_frequency'] ?? '', 'daily'); ?>><?php _e('Daily', 'custom-cookie-consent'); ?></option>
                    <option value="weekly" <?php selected($settings['scan_frequency'] ?? '', 'weekly'); ?>><?php _e('Weekly', 'custom-cookie-consent'); ?></option>
                    <option value="monthly" <?php selected($settings['scan_frequency'] ?? '', 'monthly'); ?>><?php _e('Monthly', 'custom-cookie-consent'); ?></option>
                </select>
            </div>

            <input type="submit" name="submit" class="cookie-consent-submit" value="<?php _e('Save Scanner Settings', 'custom-cookie-consent'); ?>">
        </form>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Integration Settings', 'custom-cookie-consent'); ?></h2>

        <form class="cookie-consent-settings-form js-integration-settings-form" method="post" action="">
            <div class="form-field">
                <label>
                    <input type="checkbox" name="wp_consent_api" value="1" <?php checked($settings['wp_consent_api'] ?? false); ?>>
                    <?php _e('WP Consent API Integration', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Register with the WordPress Consent API for enhanced plugin compatibility.', 'custom-cookie-consent'); ?></p>
                <?php if ($settings['wp_consent_api'] ?? false): ?>
                    <?php if (!function_exists('wp_has_consent')): ?>
                        <div class="notice notice-warning inline">
                            <p><?php _e('The WP Consent API plugin is not active. Please install and activate the <a href="https://wordpress.org/plugins/wp-consent-api/" target="_blank">WP Consent API plugin</a> to use this integration.', 'custom-cookie-consent'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="notice notice-success inline">
                            <p><?php _e('WP Consent API is active and integrated.', 'custom-cookie-consent'); ?></p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" name="sitekit_integration" value="1" <?php checked($settings['sitekit_integration'] ?? false); ?>>
                    <?php _e('Google Site Kit Integration', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Manage Google Site Kit cookies with consent mode v2.', 'custom-cookie-consent'); ?></p>
            </div>

            <h3><?php _e('Direct Google Integration', 'custom-cookie-consent'); ?></h3>
            <p class="description"><?php _e('Add your Google tracking codes directly here for optimal consent control. This ensures tracking scripts are only loaded after proper consent, improving GDPR compliance.', 'custom-cookie-consent'); ?></p>

            <style>
                .google-integration-notice {
                    margin: 15px 0;
                    padding: 12px 15px;
                    border-radius: 4px;
                    border-left-width: 4px;
                }

                .google-integration-notice p {
                    margin: 0.5em 0;
                    padding: 0;
                    line-height: 1.5;
                }

                .google-integration-notice strong {
                    font-size: 14px;
                }

                .google-integration-notice code {
                    background: rgba(0, 0, 0, 0.07);
                    padding: 2px 5px;
                    border-radius: 3px;
                }

                .google-integration-notice ul {
                    margin: 5px 0 5px 20px;
                    list-style-type: disc;
                }

                .sitekit-action-list {
                    margin-top: 8px;
                }

                .sitekit-action-list li {
                    margin-bottom: 5px;
                }

                input.sitekit-provided {
                    border-left: 4px solid #2271b1;
                }

                .sitekit-status-icon {
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    background-color: #2271b1;
                    border-radius: 50%;
                    margin-right: 5px;
                    position: relative;
                    top: 3px;
                }

                .sitekit-status-icon:before {
                    content: '✓';
                    color: white;
                    font-size: 11px;
                    position: absolute;
                    left: 4px;
                    top: -1px;
                }
            </style>

            <?php
            // Check if Google Site Kit is active and configured
            $sitekit_active = defined('GOOGLESITEKIT_VERSION');
            $sitekit_has_analytics = false;
            $sitekit_has_tagmanager = false;

            if ($sitekit_active) {
                // Check if Site Kit analytics module is active and configured
                if (
                    class_exists('\Google\Site_Kit\Modules\Analytics_4\Settings') &&
                    class_exists('\Google\Site_Kit\Core\Storage\Options')
                ) {

                    $analytics_option = \get_option('googlesitekit_analytics-4_settings');
                    $sitekit_has_analytics = !empty($analytics_option['measurementID']);

                    // Check if Tag Manager module is active and configured
                    $tagmanager_option = \get_option('googlesitekit_tagmanager_settings');
                    $sitekit_has_tagmanager = !empty($tagmanager_option['containerID']);
                }
            }
            ?>

            <?php if ($sitekit_active && ($sitekit_has_analytics || $sitekit_has_tagmanager)): ?>
                <div class="google-integration-notice notice notice-info inline">
                    <p>
                        <strong><?php _e('Google Site Kit Detected', 'custom-cookie-consent'); ?></strong><br>
                        <?php _e('Google Site Kit is active and providing measurement IDs. The plugin will automatically use these IDs with proper consent handling.', 'custom-cookie-consent'); ?>
                    </p>
                    <ul>
                        <?php if ($sitekit_has_tagmanager): ?>
                            <li><span class="sitekit-status-icon"></span> <?php printf(__('Google Tag Manager ID: %s', 'custom-cookie-consent'), '<code>' . esc_html($tagmanager_option['containerID']) . '</code>'); ?></li>
                        <?php endif; ?>
                        <?php if ($sitekit_has_analytics): ?>
                            <li><span class="sitekit-status-icon"></span> <?php printf(__('Google Analytics 4 ID: %s', 'custom-cookie-consent'), '<code>' . esc_html($analytics_option['measurementID']) . '</code>'); ?></li>
                        <?php endif; ?>
                    </ul>
                    <p><?php _e('You can still manually override these IDs below if needed.', 'custom-cookie-consent'); ?></p>
                </div>
            <?php elseif ($sitekit_active): ?>
                <div class="google-integration-notice notice notice-warning inline">
                    <p>
                        <strong><?php _e('Google Site Kit Not Fully Configured', 'custom-cookie-consent'); ?></strong><br>
                        <?php _e('Google Site Kit is installed but Analytics or Tag Manager modules are not fully configured. You can either:', 'custom-cookie-consent'); ?>
                    </p>
                    <ul class="sitekit-action-list">
                        <li><a href="<?php echo esc_url(admin_url('admin.php?page=googlesitekit-settings')); ?>"><?php _e('Configure Google Site Kit modules', 'custom-cookie-consent'); ?></a></li>
                        <li><?php _e('Enter your tracking IDs manually below', 'custom-cookie-consent'); ?></li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="google-integration-notice notice notice-info inline">
                    <p>
                        <strong><?php _e('Google Site Kit Not Detected', 'custom-cookie-consent'); ?></strong><br>
                        <?php _e('For easier integration, we recommend:', 'custom-cookie-consent'); ?>
                    </p>
                    <ul class="sitekit-action-list">
                        <li><a href="<?php echo esc_url(admin_url('plugin-install.php?s=site+kit+by+google&tab=search&type=term')); ?>"><?php _e('Install Google Site Kit plugin', 'custom-cookie-consent'); ?></a></li>
                        <li><?php _e('Or enter your tracking IDs manually below', 'custom-cookie-consent'); ?></li>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="form-field">
                <label for="gtm_id"><?php _e('Google Tag Manager ID', 'custom-cookie-consent'); ?></label>
                <input type="text" id="gtm_id" name="gtm_id" placeholder="GTM-XXXXXXX"
                    value="<?php echo esc_attr($settings['gtm_id'] ?? ($sitekit_has_tagmanager ? $tagmanager_option['containerID'] : '')); ?>"
                    <?php if ($sitekit_has_tagmanager && empty($settings['gtm_id'])): ?>
                    class="sitekit-provided"
                    style="background-color: #f9f9f9; border-color: #ddd;"
                    <?php endif; ?>>
                <?php if ($sitekit_has_tagmanager && empty($settings['gtm_id'])): ?>
                    <p class="description"><?php _e('Using Tag Manager ID from Google Site Kit. Enter a value to override.', 'custom-cookie-consent'); ?></p>
                <?php else: ?>
                    <p class="description"><?php _e('Enter your GTM container ID (e.g., GTM-XXXXXXX). This will load GTM with proper consent signals.', 'custom-cookie-consent'); ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="ga4_id"><?php _e('Google Analytics 4 Measurement ID', 'custom-cookie-consent'); ?></label>
                <input type="text" id="ga4_id" name="ga4_id" placeholder="G-XXXXXXXXXX"
                    value="<?php echo esc_attr($settings['ga4_id'] ?? ($sitekit_has_analytics ? $analytics_option['measurementID'] : '')); ?>"
                    <?php if ($sitekit_has_analytics && empty($settings['ga4_id'])): ?>
                    class="sitekit-provided"
                    style="background-color: #f9f9f9; border-color: #ddd;"
                    <?php endif; ?>>
                <?php if ($sitekit_has_analytics && empty($settings['ga4_id'])): ?>
                    <p class="description"><?php _e('Using Analytics 4 ID from Google Site Kit. Enter a value to override.', 'custom-cookie-consent'); ?></p>
                <?php else: ?>
                    <p class="description"><?php _e('Enter your GA4 Measurement ID (e.g., G-XXXXXXXXXX). Only needed if not using Google Tag Manager.', 'custom-cookie-consent'); ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" name="use_head_tag" value="1" <?php checked($settings['use_head_tag'] ?? false); ?>>
                    <?php _e('Load Google tags in <head> (recommended)', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Loads Google consent code in the <head> section for better performance and consent handling. Highly recommended for proper consent handling.', 'custom-cookie-consent'); ?></p>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" name="use_eu_consent_regions" value="1" <?php checked($settings['use_eu_consent_regions'] ?? false); ?>>
                    <?php _e('Apply consent to all EU/EEA regions', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Extends consent requirements to all EU/EEA countries, not just Norway. Recommended for sites with international visitors.', 'custom-cookie-consent'); ?></p>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" name="hubspot_integration" value="1" <?php checked($settings['hubspot_integration'] ?? false); ?>>
                    <?php _e('HubSpot Integration', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Control HubSpot tracking features based on user consent.', 'custom-cookie-consent'); ?></p>
            </div>

            <h3><?php _e('Matomo Analytics Integration', 'custom-cookie-consent'); ?></h3>
            <p class="description"><?php _e('Configure your Matomo tracking with proper consent management.', 'custom-cookie-consent'); ?></p>

            <?php
            // Check if Matomo WordPress plugin is active
            $matomo_wp_active = defined('MATOMO_PLUGIN_FILE') || class_exists('\Matomo\WpMatomo');

            // Check if Matomo Tag Manager is configured
            $matomo_tagmanager_active = $matomo_wp_active && function_exists('matomo_has_tag_manager');
            ?>

            <?php if ($matomo_wp_active): ?>
                <div class="google-integration-notice notice notice-info inline">
                    <p>
                        <strong><?php _e('Matomo WordPress Plugin Detected', 'custom-cookie-consent'); ?></strong><br>
                        <?php _e('Matomo Analytics WordPress plugin is active. The plugin will automatically manage consent for Matomo tracking.', 'custom-cookie-consent'); ?>
                    </p>
                    <?php if ($matomo_tagmanager_active): ?>
                        <p>
                            <span class="sitekit-status-icon"></span> <?php _e('Matomo Tag Manager is also configured.', 'custom-cookie-consent'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="form-field">
                <label>
                    <input type="checkbox" name="matomo_integration" value="1" <?php checked($settings['matomo_integration'] ?? false); ?>>
                    <?php _e('Enable Matomo Integration', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Manage Matomo tracking features based on user consent.', 'custom-cookie-consent'); ?></p>
            </div>

            <div class="form-field">
                <label for="matomo_site_id"><?php _e('Matomo Site ID', 'custom-cookie-consent'); ?></label>
                <input type="text" id="matomo_site_id" name="matomo_site_id" placeholder="1"
                    value="<?php echo esc_attr($settings['matomo_site_id'] ?? ''); ?>">
                <p class="description"><?php _e('Enter your Matomo Site ID (usually 1 for a single site).', 'custom-cookie-consent'); ?></p>
            </div>

            <div class="form-field">
                <label for="matomo_url"><?php _e('Matomo URL', 'custom-cookie-consent'); ?></label>
                <input type="url" id="matomo_url" name="matomo_url" placeholder="https://your-matomo-instance.com/"
                    value="<?php echo esc_attr($settings['matomo_url'] ?? ''); ?>">
                <p class="description"><?php _e('Enter your Matomo instance URL (e.g., https://analytics.example.com/ or https://cloud.matomo.com/).', 'custom-cookie-consent'); ?></p>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" name="matomo_track_without_cookies" value="1" <?php checked($settings['matomo_track_without_cookies'] ?? false); ?>>
                    <?php _e('Enable cookieless tracking when consent not given', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('When enabled, Matomo will still track basic pageviews when analytics cookies are not accepted, but without setting cookies.', 'custom-cookie-consent'); ?></p>
            </div>

            <input type="submit" name="submit" class="cookie-consent-submit" value="<?php _e('Save Integration Settings', 'custom-cookie-consent'); ?>">
        </form>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Shortcodes', 'custom-cookie-consent'); ?></h2>

        <table class="wp-list-table widefat fixed">
            <thead>
                <tr>
                    <th><?php _e('Shortcode', 'custom-cookie-consent'); ?></th>
                    <th><?php _e('Description', 'custom-cookie-consent'); ?></th>
                    <th><?php _e('Example', 'custom-cookie-consent'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[cookie_settings]</code></td>
                    <td><?php _e('Adds a cookie settings link that opens the consent banner', 'custom-cookie-consent'); ?></td>
                    <td><code>[cookie_settings text="Manage Cookies" class="my-custom-class"]</code></td>
                </tr>
                <tr>
                    <td><code>[show_my_consent_data]</code></td>
                    <td><?php _e('Displays a detailed overview of user consent preferences and active cookies', 'custom-cookie-consent'); ?></td>
                    <td><code>[show_my_consent_data]</code></td>
                </tr>
                <?php
                // The 'cookie_consent_data' shortcode doesn't appear to be registered in the code, remove it
                // or register it if needed
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
    <!-- Debugging output -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Cookie Integration Settings from PHP:', {
                wp_consent_api: <?php echo json_encode(isset($settings['wp_consent_api']) ? $settings['wp_consent_api'] : false); ?>,
                sitekit_integration: <?php echo json_encode(isset($settings['sitekit_integration']) ? $settings['sitekit_integration'] : false); ?>,
                hubspot_integration: <?php echo json_encode(isset($settings['hubspot_integration']) ? $settings['hubspot_integration'] : false); ?>,
                matomo_integration: <?php echo json_encode(isset($settings['matomo_integration']) ? $settings['matomo_integration'] : false); ?>,
                matomo_site_id: <?php echo json_encode(isset($settings['matomo_site_id']) ? $settings['matomo_site_id'] : ''); ?>,
                matomo_url: <?php echo json_encode(isset($settings['matomo_url']) ? $settings['matomo_url'] : ''); ?>,
                matomo_track_without_cookies: <?php echo json_encode(isset($settings['matomo_track_without_cookies']) ? $settings['matomo_track_without_cookies'] : false); ?>
            });

            // Check actual DOM state
            console.log('Actual checkbox states in DOM:', {
                wp_consent_api: document.querySelector('input[name="wp_consent_api"]').checked,
                sitekit_integration: document.querySelector('input[name="sitekit_integration"]').checked,
                hubspot_integration: document.querySelector('input[name="hubspot_integration"]').checked,
                matomo_integration: document.querySelector('input[name="matomo_integration"]')?.checked || false,
                matomo_track_without_cookies: document.querySelector('input[name="matomo_track_without_cookies"]')?.checked || false
            });
        });
    </script>
<?php endif; ?>