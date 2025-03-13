<?php

/**
 * Admin Dashboard Template
 *
 * Main admin dashboard for the cookie consent plugin.
 *
 * @package CustomCookieConsent
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Count cookies by category
$categoryCounts = [
    'necessary' => 0,
    'analytics' => 0,
    'functional' => 0,
    'marketing' => 0,
    'uncategorized' => 0
];

foreach ($detected as $cookie) {
    if ($cookie['status'] === 'uncategorized') {
        $categoryCounts['uncategorized']++;
    } else {
        $category = $cookie['category'] ?? 'uncategorized';
        if (isset($categoryCounts[$category])) {
            $categoryCounts[$category]++;
        } else {
            $categoryCounts['uncategorized']++;
        }
    }
}

// Total cookie count
$totalCookies = count($detected);
$categorizedCookies = $totalCookies - $categoryCounts['uncategorized'];
?>

<div class="wrap cookie-consent-admin-wrap">
    <div class="cookie-consent-admin-header">
        <h1><?php _e('Cookie Consent Dashboard', 'custom-cookie-consent'); ?></h1>
        <div class="devora-branding">
            <span><?php _e('by', 'custom-cookie-consent'); ?></span>
            <a href="https://devora.no" target="_blank" rel="noopener noreferrer">
                <span class="devora-logo">Devora</span>
            </a>
        </div>

        <div class="cookie-consent-header-actions">
            <a href="<?php echo admin_url('admin.php?page=custom-cookie-scanner'); ?>" class="button">
                <?php _e('Scan for Cookies', 'custom-cookie-consent'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=custom-cookie-settings'); ?>" class="button button-primary">
                <?php _e('Settings', 'custom-cookie-consent'); ?>
            </a>
        </div>
    </div>

    <div class="cookie-consent-admin-nav">
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-consent'); ?>" class="active">
            <?php _e('Dashboard', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-scanner'); ?>">
            <?php _e('Cookie Scanner', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-settings'); ?>">
            <?php _e('Settings', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-translations'); ?>">
            <?php _e('Text & Translations', 'custom-cookie-consent'); ?>
        </a>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Cookie Overview', 'custom-cookie-consent'); ?></h2>

        <div class="cookie-consent-stats">
            <div class="cookie-consent-stat-card">
                <h3><?php _e('Total Cookies', 'custom-cookie-consent'); ?></h3>
                <div class="stat-value js-total-cookies"><?php echo $totalCookies; ?></div>
            </div>

            <div class="cookie-consent-stat-card">
                <h3><?php _e('Categorized', 'custom-cookie-consent'); ?></h3>
                <div class="stat-value js-categorized-cookies"><?php echo $categorizedCookies; ?></div>
            </div>

            <div class="cookie-consent-stat-card">
                <h3><?php _e('Uncategorized', 'custom-cookie-consent'); ?></h3>
                <div class="stat-value js-uncategorized-cookies"><?php echo $categoryCounts['uncategorized']; ?></div>
                <?php if ($categoryCounts['uncategorized'] > 0): ?>
                    <a href="<?php echo admin_url('admin.php?page=custom-cookie-scanner'); ?>" class="button button-small">
                        <?php _e('Categorize', 'custom-cookie-consent'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="cookie-category-stats">
            <h3><?php _e('Cookies by Category', 'custom-cookie-consent'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Category', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Cookie Count', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Required', 'custom-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $categoryId => $categoryData): ?>
                        <tr>
                            <td><?php echo esc_html($categoryData['title']); ?></td>
                            <td><?php echo isset($categoryCounts[$categoryId]) ? $categoryCounts[$categoryId] : 0; ?></td>
                            <td><?php echo $categoryData['required'] ? __('Yes', 'custom-cookie-consent') : __('No', 'custom-cookie-consent'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Recent Cookies', 'custom-cookie-consent'); ?></h2>

        <?php if (empty($detected)): ?>
            <p><?php _e('No cookies have been detected yet. Run a cookie scan to detect cookies on your site.', 'custom-cookie-consent'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=custom-cookie-scanner'); ?>" class="button button-primary">
                <?php _e('Scan for Cookies', 'custom-cookie-consent'); ?>
            </a>
        <?php else: ?>
            <table class="cookie-list-table">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Category', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Domain', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Status', 'custom-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Sort cookies by detection time, newest first
                    uasort($detected, function ($a, $b) {
                        $timeA = strtotime($a['first_detected'] ?? 0);
                        $timeB = strtotime($b['first_detected'] ?? 0);
                        return $timeB - $timeA; // Descending order
                    });

                    // Show only the 10 most recent
                    $recentCookies = array_slice($detected, 0, 10, true);

                    foreach ($recentCookies as $cookieName => $cookie):
                        $statusClass = $cookie['status'] === 'categorized' ? 'cookie-status-categorized' : 'cookie-status-uncategorized';
                        $statusText = $cookie['status'] === 'categorized' ? __('Categorized', 'custom-cookie-consent') : __('Uncategorized', 'custom-cookie-consent');
                    ?>
                        <tr data-cookie-name="<?php echo esc_attr($cookieName); ?>">
                            <td>
                                <?php echo esc_html($cookieName); ?>
                                <?php if (!empty($cookie['pattern']) && $cookie['pattern']): ?>
                                    <span class="dashicons dashicons-admin-generic" title="<?php _e('Pattern matching enabled', 'custom-cookie-consent'); ?>"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html(ucfirst($cookie['category'] ?? 'uncategorized')); ?>
                            </td>
                            <td><?php echo esc_html($cookie['domain'] ?? '*'); ?></td>
                            <td><span class="cookie-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="table-footer">
                <a href="<?php echo admin_url('admin.php?page=custom-cookie-scanner'); ?>" class="button">
                    <?php _e('View All Cookies', 'custom-cookie-consent'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Quick Actions', 'custom-cookie-consent'); ?></h2>

        <div class="cookie-consent-quick-actions">
            <a href="<?php echo admin_url('admin.php?page=custom-cookie-scanner'); ?>" class="button button-primary">
                <span class="dashicons dashicons-search"></span>
                <?php _e('Scan for Cookies', 'custom-cookie-consent'); ?>
            </a>

            <a href="<?php echo admin_url('admin.php?page=custom-cookie-settings'); ?>" class="button">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Configure Banner', 'custom-cookie-consent'); ?>
            </a>

            <a href="<?php echo admin_url('admin.php?page=custom-cookie-translations'); ?>" class="button">
                <span class="dashicons dashicons-translation"></span>
                <?php _e('Customize Text', 'custom-cookie-consent'); ?>
            </a>

            <?php if (function_exists('wp_get_consent_type') && current_user_can('manage_options')): ?>
                <a href="<?php echo admin_url('options-general.php?page=wp-consent-api'); ?>" class="button">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('WP Consent API', 'custom-cookie-consent'); ?>
                </a>
            <?php elseif (function_exists('wp_get_consent_type') && !current_user_can('manage_options')): ?>
                <button class="button disabled" disabled title="<?php _e('You do not have permission to access WP Consent API settings', 'custom-cookie-consent'); ?>">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('WP Consent API (No Access)', 'custom-cookie-consent'); ?>
                </button>
            <?php else: ?>
                <button class="button disabled" disabled title="<?php _e('WP Consent API plugin is not active', 'custom-cookie-consent'); ?>">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('WP Consent API (Not Active)', 'custom-cookie-consent'); ?>
                </button>
            <?php endif; ?>
        </div>

        <div class="cookie-consent-help">
            <h3><?php _e('Need Help?', 'custom-cookie-consent'); ?></h3>
            <p>
                <?php _e('Check out the documentation for information on how to use the cookie consent plugin and customize it to your needs.', 'custom-cookie-consent'); ?>
            </p>
            <a href="https://github.com/devora-as/Cookie-Consent-by-Devora" target="_blank" class="button">
                <span class="dashicons dashicons-book"></span>
                <?php _e('Documentation', 'custom-cookie-consent'); ?>
            </a>
        </div>
    </div>
</div>