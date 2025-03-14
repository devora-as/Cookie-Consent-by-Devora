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

            <div class="form-field">
                <label>
                    <input type="checkbox" name="hubspot_integration" value="1" <?php checked($settings['hubspot_integration'] ?? false); ?>>
                    <?php _e('HubSpot Integration', 'custom-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Control HubSpot tracking features based on user consent.', 'custom-cookie-consent'); ?></p>
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