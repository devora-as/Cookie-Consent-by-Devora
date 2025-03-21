<?php

/**
 * Admin Settings Template
 *
 * Settings page for the cookie consent plugin.
 *
 * @package CustomCookieConsent
 */

// Prevent direct access
if (! defined('ABSPATH')) {
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
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-design'); ?>">
            <?php _e('Styling', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-translations'); ?>">
            <?php _e('Text & Translations', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-analytics'); ?>">
            <?php _e('Analytics & Statistics', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-documentation'); ?>">
            <?php _e('Documentation', 'custom-cookie-consent'); ?>
        </a>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Banner Settings', 'custom-cookie-consent'); ?></h2>

        <form class="cookie-consent-settings-form js-cookie-settings-form" method="post" action="">
            <?php wp_nonce_field('cookie_management', 'nonce'); ?>
            <div class="form-field">
                <label for="position"><?php _e('Banner Position', 'custom-cookie-consent'); ?></label>
                <?php
                // Check both settings keys for backwards compatibility
                $current_position = 'bottom'; // Default position
                if (isset($settings['position']) && in_array($settings['position'], array('bottom', 'top', 'center'), true)) {
                    $current_position = $settings['position'];
                } elseif (isset($settings['banner_position']) && in_array($settings['banner_position'], array('bottom', 'top', 'center'), true)) {
                    $current_position = $settings['banner_position'];
                }
                ?>
                <select id="position" name="position">
                    <option value="bottom" <?php selected($current_position, 'bottom'); ?>><?php _e('Bottom', 'custom-cookie-consent'); ?></option>
                    <option value="top" <?php selected($current_position, 'top'); ?>><?php _e('Top', 'custom-cookie-consent'); ?></option>
                    <option value="center" <?php selected($current_position, 'center'); ?>><?php _e('Center (Modal)', 'custom-cookie-consent'); ?></option>
                </select>
            </div>

            <div class="form-field">
                <label for="plugin_language"><?php _e('Plugin Language', 'custom-cookie-consent'); ?></label>
                <?php
                $current_language = isset($settings['plugin_language']) ? $settings['plugin_language'] : 'en_US';
                $available_languages = array(
                    'en_US' => __('English', 'custom-cookie-consent'),
                    'nb_NO' => __('Norwegian', 'custom-cookie-consent')
                );
                ?>
                <select id="plugin_language" name="plugin_language">
                    <?php foreach ($available_languages as $code => $name) : ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($current_language, $code); ?>><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Select the language for the plugin interface and frontend text. Note: You can still customize all text strings in the Text & Translations section.', 'custom-cookie-consent'); ?></p>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" name="enable_anti_blocker" value="1" <?php checked($settings['enable_anti_blocker'] ?? false); ?>>
                    <?php _e('Enable Anti-Ad-Blocker Protection', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Implements advanced techniques to prevent ad blockers from hiding the cookie consent banner, ensuring GDPR compliance.', 'custom-cookie-consent'); ?></p>
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
            <?php wp_nonce_field('cookie_management', 'nonce'); ?>
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

            <?php
            // Allow other components to add fields to scanner settings
            do_action('custom_cookie_settings_fields', 'scanner_settings');
            ?>

            <input type="submit" name="submit" class="cookie-consent-submit" value="<?php _e('Save Scanner Settings', 'custom-cookie-consent'); ?>">
        </form>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Integration Settings', 'custom-cookie-consent'); ?></h2>

        <form class="cookie-consent-settings-form js-integration-settings-form" method="post" action="">
            <?php wp_nonce_field('cookie_management', 'nonce'); ?>

            <!-- Matomo Integration Section -->
            <div class="settings-section">
                <h3>
                    <span class="dashicons dashicons-chart-area"></span>
                    <?php _e('Matomo Analytics Integration', 'custom-cookie-consent'); ?>
                </h3>
                <p class="description"><?php _e('Configure Matomo Analytics integration settings.', 'custom-cookie-consent'); ?></p>

                <div class="form-field">
                    <label>
                        <input type="checkbox" name="enable_matomo" value="1" <?php checked($settings['enable_matomo'] ?? false); ?>>
                        <?php _e('Enable Matomo Integration', 'custom-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php _e('Enable integration with Matomo Analytics for privacy-focused tracking.', 'custom-cookie-consent'); ?></p>
                </div>

                <div class="form-field matomo-settings" style="display: <?php echo ($settings['enable_matomo'] ?? false) ? 'block' : 'none'; ?>">
                    <label>
                        <input type="radio" name="matomo_type" value="cloud" <?php checked($settings['matomo_type'] ?? '', 'cloud'); ?>>
                        <?php _e('Matomo Cloud', 'custom-cookie-consent'); ?>
                    </label>
                    <label>
                        <input type="radio" name="matomo_type" value="self_hosted" <?php checked($settings['matomo_type'] ?? '', 'self_hosted'); ?>>
                        <?php _e('Self-hosted (On-Premise)', 'custom-cookie-consent'); ?>
                    </label>
                </div>

                <div class="form-field matomo-cloud-settings" style="display: <?php echo ($settings['matomo_type'] ?? '') === 'cloud' ? 'block' : 'none'; ?>">
                    <label for="matomo_cloud_url"><?php _e('Matomo Cloud URL', 'custom-cookie-consent'); ?></label>
                    <input type="url" id="matomo_cloud_url" name="matomo_cloud_url" value="<?php echo esc_attr($settings['matomo_cloud_url'] ?? ''); ?>" class="regular-text">
                    <p class="description"><?php _e('Your Matomo Cloud URL (e.g., https://your-account.matomo.cloud)', 'custom-cookie-consent'); ?></p>

                    <label for="matomo_site_id"><?php _e('Site ID', 'custom-cookie-consent'); ?></label>
                    <input type="number" id="matomo_site_id" name="matomo_site_id" value="<?php echo esc_attr($settings['matomo_site_id'] ?? ''); ?>" class="small-text">
                    <p class="description"><?php _e('Your Matomo Site ID', 'custom-cookie-consent'); ?></p>
                </div>

                <div class="form-field matomo-self-hosted-settings" style="display: <?php echo ($settings['matomo_type'] ?? '') === 'self_hosted' ? 'block' : 'none'; ?>">
                    <label for="matomo_url"><?php _e('Matomo Installation URL', 'custom-cookie-consent'); ?></label>
                    <input type="url" id="matomo_url" name="matomo_url" value="<?php echo esc_attr($settings['matomo_url'] ?? ''); ?>" class="regular-text">
                    <p class="description"><?php _e('URL where your Matomo is installed (e.g., https://analytics.your-domain.com)', 'custom-cookie-consent'); ?></p>

                    <label for="matomo_self_hosted_site_id"><?php _e('Site ID', 'custom-cookie-consent'); ?></label>
                    <input type="number" id="matomo_self_hosted_site_id" name="matomo_self_hosted_site_id" value="<?php echo esc_attr($settings['matomo_self_hosted_site_id'] ?? ''); ?>" class="small-text">
                    <p class="description"><?php _e('Your Matomo Site ID', 'custom-cookie-consent'); ?></p>
                </div>

                <div class="form-field matomo-settings" style="display: <?php echo ($settings['enable_matomo'] ?? false) ? 'block' : 'none'; ?>">
                    <h4><?php _e('Privacy Settings', 'custom-cookie-consent'); ?></h4>

                    <label>
                        <input type="checkbox" name="matomo_require_consent" value="1" <?php checked($settings['matomo_require_consent'] ?? true); ?>>
                        <?php _e('Require Consent Before Tracking', 'custom-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php _e('Only track visitors after they give consent for analytics cookies.', 'custom-cookie-consent'); ?></p>

                    <label>
                        <input type="checkbox" name="matomo_respect_dnt" value="1" <?php checked($settings['matomo_respect_dnt'] ?? true); ?>>
                        <?php _e('Respect Do Not Track (DNT)', 'custom-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php _e('Honor the visitor\'s DNT (Do Not Track) browser setting.', 'custom-cookie-consent'); ?></p>

                    <label>
                        <input type="checkbox" name="matomo_no_cookies" value="1" <?php checked($settings['matomo_no_cookies'] ?? false); ?>>
                        <?php _e('Cookie-less Tracking', 'custom-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php _e('Track without using cookies. Note: This will affect data accuracy for returning visitors.', 'custom-cookie-consent'); ?></p>
                </div>
            </div>

            <!-- Google Services Section -->
            <div class="settings-section">
                <h3>
                    <span class="dashicons dashicons-google"></span>
                    <?php _e('Google Services Integration', 'custom-cookie-consent'); ?>
                </h3>
                <p class="description"><?php _e('Configure Google Analytics and Tag Manager integration settings.', 'custom-cookie-consent'); ?></p>

                <div class="form-field">
                    <label>
                        <input type="checkbox" name="sitekit_integration" value="1" <?php checked($settings['sitekit_integration'] ?? false); ?>>
                        <?php _e('Google Site Kit Integration', 'custom-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php _e('Manage Google Site Kit cookies with consent mode v2.', 'custom-cookie-consent'); ?></p>
                </div>

                <div class="form-field">
                    <label for="direct_tracking_enabled"><?php _e('Enable Direct Tracking Integration', 'custom-cookie-consent'); ?></label>
                    <input type="checkbox" id="direct_tracking_enabled" name="direct_tracking_enabled" value="1" <?php checked($settings['direct_tracking_enabled'] ?? false); ?>>
                    <p class="description"><?php _e('Enable direct integration with tracking scripts (Google Analytics, GTM). This can be used alongside or instead of Google Site Kit.', 'custom-cookie-consent'); ?></p>
                </div>

                <div class="form-field tracking-code-fields" style="<?php echo ($settings['direct_tracking_enabled'] ?? false) ? '' : 'display: none;'; ?>">
                    <label for="ga_tracking_id"><?php _e('Google Analytics 4 Measurement ID', 'custom-cookie-consent'); ?></label>
                    <input type="text" id="ga_tracking_id" name="ga_tracking_id" value="<?php echo esc_attr($settings['ga_tracking_id'] ?? ''); ?>" placeholder="G-XXXXXXXXXX">
                    <p class="description"><?php _e('Your Google Analytics 4 Measurement ID (e.g., G-XXXXXXXXXX).', 'custom-cookie-consent'); ?></p>
                </div>

                <div class="form-field tracking-code-fields" style="<?php echo ($settings['direct_tracking_enabled'] ?? false) ? '' : 'display: none;'; ?>">
                    <label for="gtm_id"><?php _e('Google Tag Manager ID', 'custom-cookie-consent'); ?></label>
                    <input type="text" id="gtm_id" name="gtm_id" value="<?php echo esc_attr($settings['gtm_id'] ?? ''); ?>" placeholder="GTM-XXXXXXX">
                    <p class="description"><?php _e('Your Google Tag Manager container ID (e.g., GTM-XXXXXXX).', 'custom-cookie-consent'); ?></p>
                </div>
            </div>

            <!-- Other Integrations Section -->
            <div class="settings-section">
                <h3>
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('Other Integrations', 'custom-cookie-consent'); ?>
                </h3>
                <p class="description"><?php _e('Configure additional third-party integrations.', 'custom-cookie-consent'); ?></p>

                <div class="form-field">
                    <label>
                        <input type="checkbox" name="wp_consent_api" value="1" <?php checked($settings['wp_consent_api'] ?? false); ?>>
                        <?php _e('WP Consent API Integration', 'custom-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php _e('Register with the WordPress Consent API for enhanced plugin compatibility.', 'custom-cookie-consent'); ?></p>
                    <?php if ($settings['wp_consent_api'] ?? false) : ?>
                        <?php if (! function_exists('wp_has_consent')) : ?>
                            <div class="notice notice-warning inline">
                                <p><?php _e('The WP Consent API plugin is not active. Please install and activate the <a href="https://wordpress.org/plugins/wp-consent-api/" target="_blank">WP Consent API plugin</a> to use this integration.', 'custom-cookie-consent'); ?></p>
                            </div>
                        <?php else : ?>
                            <div class="notice notice-success inline">
                                <p><?php _e('WP Consent API is active and integrated.', 'custom-cookie-consent'); ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label>
                        <input type="checkbox" name="hubspot_integration" value="1" <?php checked($settings['hubspot_integration'] ?? false); ?>>
                        <?php _e('HubSpot Integration', 'custom-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php _e('Control HubSpot tracking features based on user consent.', 'custom-cookie-consent'); ?></p>
                </div>
            </div>

            <div class="form-field">
                <label for="consent_region"><?php _e('Consent Region', 'custom-cookie-consent'); ?></label>
                <select id="consent_region" name="consent_region">
                    <option value="NO" <?php selected($settings['consent_region'] ?? 'NO', 'NO'); ?>><?php _e('Norway Only', 'custom-cookie-consent'); ?></option>
                    <option value="EEA" <?php selected($settings['consent_region'] ?? 'NO', 'EEA'); ?>><?php _e('European Economic Area (EEA)', 'custom-cookie-consent'); ?></option>
                    <option value="GLOBAL" <?php selected($settings['consent_region'] ?? 'NO', 'GLOBAL'); ?>><?php _e('Global (All Countries)', 'custom-cookie-consent'); ?></option>
                </select>
                <p class="description"><?php _e('Select where consent defaults should apply. "Norway Only" applies consent restrictions only to Norwegian visitors. "EEA" applies to all European Economic Area countries. "Global" applies consent restrictions worldwide regardless of location.', 'custom-cookie-consent'); ?></p>
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

<?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
    <!-- Debugging output -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Cookie Integration Settings from PHP:', {
                wp_consent_api: <?php echo json_encode(isset($settings['wp_consent_api']) ? $settings['wp_consent_api'] : false); ?>,
                sitekit_integration: <?php echo json_encode(isset($settings['sitekit_integration']) ? $settings['sitekit_integration'] : false); ?>,
                hubspot_integration: <?php echo json_encode(isset($settings['hubspot_integration']) ? $settings['hubspot_integration'] : false); ?>
            });

            // Check actual DOM state
            console.log('Actual checkbox states in DOM:', {
                wp_consent_api: document.querySelector('input[name="wp_consent_api"]').checked,
                sitekit_integration: document.querySelector('input[name="sitekit_integration"]').checked,
                hubspot_integration: document.querySelector('input[name="hubspot_integration"]').checked
            });
        });
    </script>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const directTrackingCheckbox = document.getElementById('direct_tracking_enabled');
        const trackingCodeFields = document.querySelectorAll('.tracking-code-fields');

        if (directTrackingCheckbox) {
            directTrackingCheckbox.addEventListener('change', function() {
                trackingCodeFields.forEach(field => {
                    field.style.display = this.checked ? 'block' : 'none';
                });
            });
        }
    });
</script>