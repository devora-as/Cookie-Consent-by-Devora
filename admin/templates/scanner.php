<?php

/**
 * Admin Scanner Template
 *
 * Cookie scanner interface for the cookie consent plugin.
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
        <h1><?php esc_html_e('Cookie Scanner', 'custom-cookie-consent'); ?></h1>

        <div class="cookie-consent-header-actions">
            <button class="button button-primary js-cookie-scan-button">
                <?php esc_html_e('Scan for Cookies', 'custom-cookie-consent'); ?>
            </button>
        </div>
    </div>

    <div class="cookie-consent-admin-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page=custom-cookie-consent')); ?>">
            <?php esc_html_e('Dashboard', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=custom-cookie-scanner')); ?>" class="active">
            <?php esc_html_e('Cookie Scanner', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=custom-cookie-settings')); ?>">
            <?php esc_html_e('Settings', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=custom-cookie-translations')); ?>">
            <?php esc_html_e('Text & Translations', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=custom-cookie-analytics')); ?>">
            <?php esc_html_e('Analytics & Statistics', 'custom-cookie-consent'); ?>
        </a>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php esc_html_e('Cookie Scanning', 'custom-cookie-consent'); ?></h2>

        <p><?php esc_html_e('The cookie scanner detects cookies used on your website. The scan runs in the background as admin users browse your site.', 'custom-cookie-consent'); ?></p>

        <div class="cookie-scan-actions">
            <button class="cookie-consent-scan-button js-cookie-scan-button">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Scan for Cookies', 'custom-cookie-consent'); ?>
            </button>

            <?php if ($last_scan): ?>
                <p class="last-scan-info">
                    <?php
                    /* translators: %s: formatted date and time of last scan */
                    printf(
                        esc_html__('Last scan: %s', 'custom-cookie-consent'),
                        esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_scan)))
                    );
                    ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="cookie-consent-admin-card">
        <h2>
            <?php esc_html_e('Detected Cookies', 'custom-cookie-consent'); ?>
            <?php if ($uncategorized_count > 0): ?>
                <span class="uncategorized-count">
                    <?php
                    /* translators: %d: number of uncategorized cookies */
                    printf(
                        esc_html__('%d uncategorized', 'custom-cookie-consent'),
                        intval($uncategorized_count)
                    );
                    ?>
                </span>
            <?php endif; ?>
        </h2>

        <?php if (empty($detected)): ?>
            <p><?php esc_html_e('No cookies have been detected yet. Run a cookie scan to detect cookies on your site.', 'custom-cookie-consent'); ?></p>
        <?php else: ?>
            <div class="bulk-actions">
                <select class="js-bulk-category-select">
                    <option value=""><?php esc_html_e('-- Choose Category --', 'custom-cookie-consent'); ?></option>
                    <option value="necessary"><?php esc_html_e('Necessary', 'custom-cookie-consent'); ?></option>
                    <option value="analytics"><?php esc_html_e('Analytics', 'custom-cookie-consent'); ?></option>
                    <option value="functional"><?php esc_html_e('Functional', 'custom-cookie-consent'); ?></option>
                    <option value="marketing"><?php esc_html_e('Marketing', 'custom-cookie-consent'); ?></option>
                </select>

                <button class="button js-cookie-bulk-categorize">
                    <?php esc_html_e('Apply', 'custom-cookie-consent'); ?>
                </button>
            </div>

            <table class="cookie-list-table">
                <thead>
                    <tr>
                        <th width="20"><input type="checkbox" class="js-select-all-cookies"></th>
                        <th><?php esc_html_e('Name', 'custom-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Category', 'custom-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Domain', 'custom-cookie-consent'); ?></th>
                        <th><?php esc_html_e('First Detected', 'custom-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Status', 'custom-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Actions', 'custom-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detected as $cookieName => $cookie):
                        $statusClass = $cookie['status'] === 'categorized' ? 'cookie-status-categorized' : 'cookie-status-uncategorized';
                        $statusText = $cookie['status'] === 'categorized' ? esc_html__('Categorized', 'custom-cookie-consent') : esc_html__('Uncategorized', 'custom-cookie-consent');
                    ?>
                        <tr data-cookie-name="<?php echo esc_attr($cookieName); ?>">
                            <td>
                                <input type="checkbox" class="js-cookie-checkbox" value="<?php echo esc_attr($cookieName); ?>">
                            </td>
                            <td>
                                <?php echo esc_html($cookieName); ?>
                                <?php if (!empty($cookie['pattern']) && $cookie['pattern']): ?>
                                    <span class="dashicons dashicons-admin-generic" title="<?php esc_attr_e('Pattern matching enabled', 'custom-cookie-consent'); ?>"></span>
                                <?php endif; ?>
                                <?php if (!empty($cookie['source'])): ?>
                                    <div class="cookie-source">
                                        <?php
                                        /* translators: %s: source of the cookie */
                                        printf(esc_html__('Source: %s', 'custom-cookie-consent'), esc_html($cookie['source']));
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="cookie-category-selector js-cookie-categorize">
                                    <option value=""><?php esc_html_e('-- Select --', 'custom-cookie-consent'); ?></option>
                                    <option value="necessary" <?php selected($cookie['category'] ?? '', 'necessary'); ?>>
                                        <?php esc_html_e('Necessary', 'custom-cookie-consent'); ?>
                                    </option>
                                    <option value="analytics" <?php selected($cookie['category'] ?? '', 'analytics'); ?>>
                                        <?php esc_html_e('Analytics', 'custom-cookie-consent'); ?>
                                    </option>
                                    <option value="functional" <?php selected($cookie['category'] ?? '', 'functional'); ?>>
                                        <?php esc_html_e('Functional', 'custom-cookie-consent'); ?>
                                    </option>
                                    <option value="marketing" <?php selected($cookie['category'] ?? '', 'marketing'); ?>>
                                        <?php esc_html_e('Marketing', 'custom-cookie-consent'); ?>
                                    </option>
                                </select>
                            </td>
                            <td><?php echo esc_html($cookie['domain'] ?? '*'); ?></td>
                            <td>
                                <?php if (!empty($cookie['first_detected'])): ?>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($cookie['first_detected']))); ?>
                                <?php endif; ?>
                            </td>
                            <td><span class="cookie-status <?php echo esc_attr($statusClass); ?>"><?php echo $statusText; ?></span></td>
                            <td>
                                <button class="cookie-save-btn js-cookie-save-category" style="<?php echo $cookie['status'] === 'uncategorized' ? '' : 'display: none;'; ?>">
                                    <?php esc_html_e('Save', 'custom-cookie-consent'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php esc_html_e('How Cookie Detection Works', 'custom-cookie-consent'); ?></h2>

        <p><?php esc_html_e('The cookie scanner uses multiple approaches to detect cookies on your site:', 'custom-cookie-consent'); ?></p>

        <ol>
            <li><?php echo wp_kses(__('<strong>Passive Scanning:</strong> As an admin user browses the site, cookies are automatically detected and reported.', 'custom-cookie-consent'), ['strong' => []]); ?></li>
            <li><?php echo wp_kses(__('<strong>Manual Scanning:</strong> Trigger a manual scan to actively look for cookies.', 'custom-cookie-consent'), ['strong' => []]); ?></li>
            <li><?php echo wp_kses(__('<strong>Pattern Recognition:</strong> Common cookies are automatically categorized based on naming patterns.', 'custom-cookie-consent'), ['strong' => []]); ?></li>
            <li><?php echo wp_kses(__('<strong>Integration Detection:</strong> Popular plugins and their cookies are automatically recognized.', 'custom-cookie-consent'), ['strong' => []]); ?></li>
        </ol>

        <p><?php esc_html_e('For best results, browse your site as an admin user after enabling all features and plugins, including any external scripts that might set cookies.', 'custom-cookie-consent'); ?></p>
    </div>
</div>